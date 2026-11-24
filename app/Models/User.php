<?php
/**
 * User Model
 * Handles user data access, authentication, and department/unit assignments.
 */

class User {

    private static bool $schemaChecked = false;

    /**
     * Ensure database table schema has required department & unit assignment columns.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        // The concern master tables must exist before any query joins them.
        if (!class_exists('Concern') && file_exists(__DIR__ . '/Concern.php')) {
            require_once __DIR__ . '/Concern.php';
        }
        if (class_exists('Concern')) {
            Concern::ensureSchema();
        }

        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'department'");
            if (!$col) {
                Database::execute("ALTER TABLE users ADD COLUMN department VARCHAR(50) DEFAULT 'GENERAL' AFTER role_id");
                Database::execute("ALTER TABLE users ADD COLUMN production_unit_id INT UNSIGNED NULL AFTER department");
                Database::execute("ALTER TABLE users ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER production_unit_id");
                Database::execute("ALTER TABLE users ADD COLUMN shop_id INT UNSIGNED NULL AFTER warehouse_id");
                Database::execute("ALTER TABLE users ADD COLUMN reports_access VARCHAR(50) DEFAULT 'ALL' AFTER shop_id");
            }
            $empCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'emp_id'");
            if (!$empCol) {
                Database::execute("ALTER TABLE users ADD COLUMN emp_id VARCHAR(50) NULL AFTER username");
            }
            $chanCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'channel_id'");
            if (!$chanCol) {
                Database::execute("ALTER TABLE users ADD COLUMN channel_id INT UNSIGNED NULL AFTER shop_id");
            }
            $staffRoleCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'staff_role'");
            if (!$staffRoleCol) {
                Database::execute("ALTER TABLE users ADD COLUMN staff_role VARCHAR(50) NULL DEFAULT NULL AFTER department");
                Database::execute("ALTER TABLE users ADD INDEX idx_users_staff_role (staff_role)");
            }
            $defConcernCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'default_concern_id'");
            if (!$defConcernCol) {
                Database::execute("ALTER TABLE users ADD COLUMN default_concern_id INT NULL AFTER channel_id");
            }
            Database::execute("CREATE TABLE IF NOT EXISTS concerns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(50) NOT NULL UNIQUE,
                legal_name VARCHAR(255) NULL,
                gst_number VARCHAR(15) NULL,
                pan_number VARCHAR(10) NULL,
                address TEXT NULL,
                city VARCHAR(100) NULL,
                state VARCHAR(100) NULL,
                pincode VARCHAR(10) NULL,
                phone VARCHAR(20) NULL,
                email VARCHAR(150) NULL,
                logo_url VARCHAR(255) NULL,
                invoice_prefix VARCHAR(20) DEFAULT 'INV',
                po_prefix VARCHAR(20) DEFAULT 'PO',
                dispatch_prefix VARCHAR(20) DEFAULT 'DSP',
                is_active TINYINT(1) DEFAULT 1,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_concerns_code (code),
                INDEX idx_concerns_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            Database::execute("CREATE TABLE IF NOT EXISTS concern_modules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                concern_id INT NOT NULL,
                module_key VARCHAR(50) NOT NULL,
                is_enabled TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_concern_module (concern_id, module_key),
                INDEX idx_cm_concern (concern_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            Database::execute("CREATE TABLE IF NOT EXISTS user_concerns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                concern_id INT NOT NULL,
                is_default TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_concern (user_id, concern_id),
                INDEX idx_uc_user (user_id),
                INDEX idx_uc_concern (concern_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $concernsList = Database::fetchAll("SELECT id FROM concerns");
            $stdModules = ['production', 'purchases', 'warehouse', 'retail_pos', 'digital_sales', 'returns', 'expenses', 'assets'];
            foreach ($concernsList as $cRow) {
                $cId = (int)$cRow['id'];
                $cModulesCount = Database::fetchOne("SELECT COUNT(*) as c FROM concern_modules WHERE concern_id = ?", [$cId]);
                if (empty($cModulesCount['c'])) {
                    foreach ($stdModules as $m) {
                        Database::execute("INSERT IGNORE INTO concern_modules (concern_id, module_key, is_enabled) VALUES (?, ?, 1)", [$cId, $m]);
                    }
                }
            }
            Database::execute("CREATE TABLE IF NOT EXISTS user_permissions (
                user_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (user_id, permission_id),
                INDEX idx_user_perms (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            Database::execute("CREATE TABLE IF NOT EXISTS user_permission_overrides (
                user_id INT UNSIGNED NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Ensure core transactional tables have concern_id for Multi-Concern scoping
            $partitionTables = [
                'production_units', 'warehouses', 'shops',
                'production_purchases', 'production_programs', 'cutting_batches',
                'stitching_batches', 'qc_batches', 'production_batches', 'production_outwards',
                'production_bills', 'production_returns', 'production_expenses',
                'wastage_sales', 'stitching_wastage_sales', 'stock_balances', 'stock_transactions'
            ];
            foreach ($partitionTables as $pTbl) {
                try {
                    $hasCol = Database::fetchOne("SHOW COLUMNS FROM `{$pTbl}` LIKE 'concern_id'");
                    if (!$hasCol) {
                        Database::execute("ALTER TABLE `{$pTbl}` ADD COLUMN `concern_id` INT NULL DEFAULT 1 AFTER `id`, ADD INDEX `idx_{$pTbl}_concern` (`concern_id`)");
                        Database::execute("UPDATE `{$pTbl}` SET `concern_id` = 1 WHERE `concern_id` IS NULL");
                    }
                } catch (Throwable $pe) {}
            }

        } catch (Throwable $e) {}
    }

    /**
     * Find user by username or email.
     */
    public static function findByUsername(string $username): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug,
                    pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                    sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN production_units pu ON u.production_unit_id = pu.id
             LEFT JOIN warehouses w ON u.warehouse_id = w.id
             LEFT JOIN shops s ON u.shop_id = s.id
             LEFT JOIN sales_channels sc ON u.channel_id = sc.id
             WHERE u.username = ? OR u.email = ?",
            [$username, $username]
        );
    }

    /**
     * Find user by ID.
     */
    public static function findById(int $id): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug,
                    pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                    sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type,
                    c_def.name as default_concern_name, c_def.code as default_concern_code
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN production_units pu ON u.production_unit_id = pu.id
             LEFT JOIN warehouses w ON u.warehouse_id = w.id
             LEFT JOIN shops s ON u.shop_id = s.id
             LEFT JOIN sales_channels sc ON u.channel_id = sc.id
             LEFT JOIN concerns c_def ON u.default_concern_id = c_def.id
             WHERE u.id = ?",
            [$id]
        );
    }

    /**
     * Get all users with their roles and unit assignments.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug,
                       pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                       sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type,
                       c_def.name as default_concern_name, c_def.code as default_concern_code,
                       (
                           SELECT GROUP_CONCAT(CONCAT(c.code, ':', c.name) ORDER BY uc.is_default DESC, c.name ASC SEPARATOR '||')
                           FROM user_concerns uc
                           JOIN concerns c ON uc.concern_id = c.id
                           WHERE uc.user_id = u.id
                       ) as assigned_concerns_str
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN production_units pu ON u.production_unit_id = pu.id
                LEFT JOIN warehouses w ON u.warehouse_id = w.id
                LEFT JOIN shops s ON u.shop_id = s.id
                LEFT JOIN sales_channels sc ON u.channel_id = sc.id
                LEFT JOIN concerns c_def ON u.default_concern_id = c_def.id
                WHERE 1=1";
        $params = [];

        // Exclude root Super Administrator from staff directory
        $includeSuperAdmin = $filters['include_superadmin'] ?? false;
        if (!$includeSuperAdmin) {
            $sql .= " AND (u.role_id != 1 AND u.username != 'admin' AND (r.slug NOT IN ('admin', 'super_admin') OR r.slug IS NULL))";
        }

        if (!empty($filters['concern_id'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id = ?)";
            $params[] = (int)$filters['concern_id'];
        }
        if (!empty($filters['role_id'])) {
            $sql .= " AND u.role_id = ?";
            $params[] = $filters['role_id'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND (u.department = ? OR u.department = 'ALL')";
            $params[] = strtoupper($filters['department']);
        }
        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND u.production_unit_id = ?";
            $params[] = $filters['production_unit_id'];
        }
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND u.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        if (!empty($filters['shop_id'])) {
            $sql .= " AND u.shop_id = ?";
            $params[] = $filters['shop_id'];
        }
        if (!empty($filters['channel_id'])) {
            $sql .= " AND u.channel_id = ?";
            $params[] = $filters['channel_id'];
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND u.is_active = ?";
            $params[] = $filters['is_active'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $sql .= " ORDER BY u.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a new user.
     */
    public static function create(array $data): int {
        self::ensureSchema();
        $phone = !empty(trim((string)($data['phone'] ?? ''))) ? trim((string)$data['phone']) : null;
        $email = !empty(trim((string)($data['email'] ?? ''))) ? trim((string)$data['email']) : null;
        $dept = !empty($data['department']) ? strtoupper($data['department']) : 'GENERAL';
        
        $rawChannel = $data['channel_id'] ?? ($data['whatsapp_channel_id'] ?? ($data['website_channel_id'] ?? null));
        $channelId = in_array($dept, ['WHATSAPP', 'WEBSITE']) ? (!empty($rawChannel) ? (int)$rawChannel : null) : null;
        $puId = ($dept === 'PRODUCTION') ? (!empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null) : null;
        $whId = ($dept === 'WAREHOUSE') ? (!empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null) : null;
        $shopId = ($dept === 'SHOP') ? (!empty($data['shop_id']) ? (int)$data['shop_id'] : null) : null;
        $staffRole = !empty($data['staff_role']) ? strtoupper(trim((string)$data['staff_role'])) : null;
        $defaultConcernId = !empty($data['default_concern_id']) ? (int)$data['default_concern_id'] : null;

        Database::execute(
            "INSERT INTO users (username, email, password_hash, full_name, phone, role_id, department, staff_role, production_unit_id, warehouse_id, shop_id, channel_id, default_concern_id, reports_access, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $data['username'],
                $email,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['full_name'],
                $phone,
                $data['role_id'],
                $dept,
                $staffRole,
                $puId,
                $whId,
                $shopId,
                $channelId,
                $defaultConcernId,
                $data['reports_access'] ?? 'ALL',
            ]
        );
        $userId = (int) Database::lastInsertId();

        // Save assigned business concerns if provided
        if (isset($data['concerns']) && is_array($data['concerns'])) {
            self::saveConcerns($userId, $data['concerns'], $defaultConcernId);
        } elseif ($defaultConcernId) {
            self::saveConcerns($userId, [$defaultConcernId], $defaultConcernId);
        }

        // Save customized permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $cleanPerms = array_values(array_filter(array_map('intval', $data['permissions'])));
            self::savePermissions($userId, $cleanPerms);
        }

        return $userId;
    }

    /**
     * Update user details.
     */
    public static function update(int $id, array $data): void {
        self::ensureSchema();

        $targetDept = !empty($data['department']) ? strtoupper($data['department']) : null;
        if (empty($targetDept)) {
            $existing = self::findById($id);
            $targetDept = $existing['department'] ?? 'GENERAL';
        }

        if (isset($data['whatsapp_channel_id']) || isset($data['website_channel_id'])) {
            $data['channel_id'] = $data['whatsapp_channel_id'] ?? ($data['website_channel_id'] ?? null);
        }

        // Clean up out-of-scope facility IDs if department is set
        if ($targetDept === 'PRODUCTION') {
            $data['warehouse_id'] = null;
            $data['shop_id'] = null;
            $data['channel_id'] = null;
        } elseif ($targetDept === 'WAREHOUSE') {
            $data['production_unit_id'] = null;
            $data['shop_id'] = null;
            $data['channel_id'] = null;
        } elseif (in_array($targetDept, ['WHATSAPP', 'WEBSITE'])) {
            $data['production_unit_id'] = null;
            $data['warehouse_id'] = null;
            $data['shop_id'] = null;
        } elseif ($targetDept === 'SHOP') {
            $data['production_unit_id'] = null;
            $data['warehouse_id'] = null;
            $data['channel_id'] = null;
        }

        $fields = [];
        $params = [];

        foreach (['full_name', 'email', 'phone', 'role_id', 'department', 'staff_role', 'production_unit_id', 'warehouse_id', 'shop_id', 'channel_id', 'reports_access', 'is_active', 'default_concern_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $val = $data[$field];
                if ($field === 'department' && !empty($val)) $val = strtoupper($val);
                if (in_array($field, ['email', 'phone']) && empty(trim((string)$val))) $val = null;
                if (in_array($field, ['production_unit_id', 'warehouse_id', 'shop_id', 'channel_id', 'default_concern_id']) && empty($val)) $val = null;
                $params[] = $val;
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $fields[] = "updated_at = NOW()";
        $params[] = $id;

        Database::execute(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );

        // Update assigned business concerns if provided
        if (isset($data['concerns']) && is_array($data['concerns'])) {
            $defCid = !empty($data['default_concern_id']) ? (int)$data['default_concern_id'] : null;
            self::saveConcerns($id, $data['concerns'], $defCid);
        }

        // Update customized permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $cleanPerms = array_values(array_filter(array_map('intval', $data['permissions'])));
            self::savePermissions($id, $cleanPerms);
        }
    }

    /**
     * Save user-specific permissions.
     */
    public static function savePermissions(int $userId, array $permissionIds): void {
        self::ensureSchema();
        Database::execute("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        $cleanIds = Role::filterCrudPermissionIds($permissionIds);
        foreach ($cleanIds as $pid) {
            Database::execute("INSERT IGNORE INTO user_permissions (user_id, permission_id) VALUES (?, ?)", [$userId, $pid]);
        }
        Database::execute("INSERT IGNORE INTO user_permission_overrides (user_id) VALUES (?)", [$userId]);
    }

    /**
     * Update last login timestamp.
     */
    public static function updateLastLogin(int $id): void {
        Database::execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    /**
     * Change password.
     */
    public static function changePassword(int $id, string $newPassword): void {
        Database::execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $id]
        );
    }

    /**
     * Get all permission keys for a user (via custom user permissions, or fallback to role).
     */
    public static function getPermissions(int $userId): array {
        self::ensureSchema();

        // If user has explicit custom permissions / overrides, respect them strictly
        if (self::hasPermissionOverride($userId)) {
            $customRows = Database::fetchAll(
                "SELECT CONCAT(p.module, '.', p.action) as permission_key 
                 FROM user_permissions up 
                 JOIN permissions p ON up.permission_id = p.id 
                 WHERE up.user_id = ?",
                [$userId]
            );
            return !empty($customRows) ? array_column($customRows, 'permission_key') : [];
        }

        // Fallback to role permissions when no explicit custom override is set
        $roleRows = Database::fetchAll(
            "SELECT CONCAT(p.module, '.', p.action) as permission_key 
             FROM users u 
             JOIN role_permissions rp ON u.role_id = rp.role_id 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE u.id = ?",
            [$userId]
        );
        return !empty($roleRows) ? array_column($roleRows, 'permission_key') : [];
    }

    /**
     * Get permission IDs assigned to a user (or inherited from role).
     */
    public static function getPermissionIds(int $userId): array {
        self::ensureSchema();

        // If user has explicit custom permissions / overrides, return them strictly
        if (self::hasPermissionOverride($userId)) {
            $customRows = Database::fetchAll(
                "SELECT permission_id FROM user_permissions WHERE user_id = ?",
                [$userId]
            );
            return !empty($customRows) ? array_map('intval', array_column($customRows, 'permission_id')) : [];
        }

        // Fallback to role permissions when no explicit custom override is set
        $roleRows = Database::fetchAll(
            "SELECT rp.permission_id 
             FROM users u 
             JOIN role_permissions rp ON u.role_id = rp.role_id 
             WHERE u.id = ?",
            [$userId]
        );
        return !empty($roleRows) ? array_map('intval', array_column($roleRows, 'permission_id')) : [];
    }

    public static function hasPermissionOverride(int $userId): bool {
        $hasOverrideRecord = (bool)Database::fetchOne(
            "SELECT user_id FROM user_permission_overrides WHERE user_id = ?",
            [$userId]
        );
        if ($hasOverrideRecord) {
            return true;
        }
        $hasCustomPerms = (bool)Database::fetchOne(
            "SELECT user_id FROM user_permissions WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        return $hasCustomPerms;
    }

    /**
     * Toggle user active status.
     */
    public static function toggleActive(int $id): void {
        Database::execute(
            "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /**
     * Delete user by ID.
     */
    public static function delete(int $id): void {
        Database::execute("DELETE FROM user_concerns WHERE user_id = ?", [$id]);
        Database::execute("DELETE FROM user_permissions WHERE user_id = ?", [$id]);
        Database::execute("DELETE FROM user_permission_overrides WHERE user_id = ?", [$id]);
        Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    /**
     * Get all concerns mapped to a user with details and default session indicator.
     */
    public static function getConcerns(int $userId): array {
        self::ensureSchema();
        return Database::fetchAll(
            "SELECT c.*, uc.is_default
             FROM user_concerns uc
             JOIN concerns c ON uc.concern_id = c.id
             WHERE uc.user_id = ?
             ORDER BY uc.is_default DESC, c.name ASC",
            [$userId]
        ) ?: [];
    }

    /**
     * Get array of integer concern IDs assigned to a user.
     */
    public static function getConcernIds(int $userId): array {
        self::ensureSchema();
        $rows = Database::fetchAll(
            "SELECT concern_id FROM user_concerns WHERE user_id = ?",
            [$userId]
        );
        return !empty($rows) ? array_map('intval', array_column($rows, 'concern_id')) : [];
    }

    /**
     * Sync authorized business concerns for a user and designate primary default concern.
     */
    public static function saveConcerns(int $userId, array $concernIds, ?int $defaultConcernId = null): void {
        self::ensureSchema();
        Database::execute("DELETE FROM user_concerns WHERE user_id = ?", [$userId]);

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $concernIds))));
        if (empty($cleanIds)) {
            Database::execute("UPDATE users SET default_concern_id = NULL WHERE id = ?", [$userId]);
            return;
        }

        if (!$defaultConcernId || !in_array($defaultConcernId, $cleanIds, true)) {
            $defaultConcernId = $cleanIds[0];
        }

        foreach ($cleanIds as $cid) {
            $isDefault = ($cid === $defaultConcernId) ? 1 : 0;
            Database::execute(
                "INSERT INTO user_concerns (user_id, concern_id, is_default, created_at) VALUES (?, ?, ?, NOW())",
                [$userId, $cid, $isDefault]
            );
        }

        Database::execute(
            "UPDATE users SET default_concern_id = ? WHERE id = ?",
            [$defaultConcernId, $userId]
        );
    }

    /**
     * Count users by criteria.
     */
    public static function count(array $filters = []): int {
        self::ensureSchema();
        $sql = "SELECT COUNT(*) as cnt FROM users WHERE 1=1";
        $params = [];
        if (!empty($filters['department'])) {
            $sql .= " AND (department = ? OR department = 'ALL')";
            $params[] = strtoupper($filters['department']);
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'];
        }
        return (int)(Database::fetchOne($sql, $params)['cnt'] ?? 0);
    }
}
