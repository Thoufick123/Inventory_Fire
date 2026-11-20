<?php
/**
 * Admin Controller
 * Dashboard, staff management, and role management.
 */

require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Role.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class AdminController {

    private function isValidGstNumber(string $gstNumber): bool {
        return $gstNumber === '' || preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstNumber) === 1;
    }

    /**
     * Admin Dashboard.
     */
    public function dashboard(): void {
        if (!hasPermission('dashboard', 'view')) {
            if (hasPermission('production', 'view') || hasPermission('cutting', 'view') || hasPermission('programs', 'view')) {
                Response::redirect(url('purchases'));
                return;
            }
            if (hasPermission('staff', 'view')) {
                Response::redirect(url('admin/staff?department=all'));
                return;
            }
            if (hasPermission('settings', 'view')) {
                Response::redirect(url('system/settings'));
                return;
            }
            Response::error(403, 'Access denied. You do not have permission to view the executive dashboard.');
            return;
        }

        // Aggregate KPIs
        $data = [
            'pageTitle'    => 'Dashboard',
            'activeMenu'   => 'dashboard',
            'breadcrumb'   => [['label' => 'Dashboard']],
        ];

        // Production Units with rich metrics
        $productionUnits = ProductionUnit::getWithMetrics();
        $managersList    = User::getAll(['is_active' => 1]);

        $data['productionUnits']      = $productionUnits;
        $data['managersList']         = $managersList;
        $data['totalProductionUnits'] = count($productionUnits);
        $data['totalStaff']           = count($managersList);

        // Total products
        $result = Database::fetchOne("SELECT COUNT(*) as cnt FROM products WHERE is_active = 1");
        $data['totalProducts'] = $result['cnt'] ?? 0;

        // Pending production programs
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM production_programs WHERE status IN ('DRAFT', 'APPROVED', 'SCHEDULED', 'IN_PROGRESS')"
        );
        $data['pendingProduction'] = $result['cnt'] ?? 0;

        // Pending QC
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM qc_batches WHERE status = 'PENDING'"
        );
        $data['pendingQC'] = $result['cnt'] ?? 0;

        // Active cutting batches
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM cutting_batches WHERE status IN ('PENDING', 'IN_PROGRESS')"
        );
        $data['activeCutting'] = $result['cnt'] ?? 0;

        // Active stitching batches
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM stitching_batches WHERE status IN ('PENDING', 'IN_PROGRESS')"
        );
        $data['activeStitching'] = $result['cnt'] ?? 0;

        // Recent Audit Activity Logs
        $data['recentAuditLogs'] = AuditService::getLogs([], 6, 0);
        $data['auditStats'] = AuditService::getStats();

        Response::view('admin.dashboard', $data);
    }

    /**
     * Staff List.
     */
    public function staffList(): void {
        $department = $_GET['department'] ?? 'all';
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
        $filters = [
            'search'             => $_GET['search'] ?? '',
            'role_id'            => $_GET['role_id'] ?? '',
            'concern_id'         => $_GET['concern_id'] ?? '',
            'department'         => strtolower($department) === 'all' ? '' : $department,
            'production_unit_id' => $_GET['production_unit_id'] ?? '',
            'warehouse_id'       => $_GET['warehouse_id'] ?? '',
            'shop_id'            => $_GET['shop_id'] ?? '',
        ];
        if ($status !== null) {
            $filters['is_active'] = $status;
        }

        // Concern scoping: non-superadmin only views authorized concerns
        $availConcerns = getAvailableUserConcerns();
        $availConcernIds = array_column($availConcerns, 'id');

        if (!isSuperAdmin()) {
            if (!empty($filters['concern_id'])) {
                if (!in_array((int)$filters['concern_id'], $availConcernIds)) {
                    $filters['concern_id'] = currentConcernId();
                }
            } else {
                $filters['concern_id'] = currentConcernId();
            }
            $concerns = $availConcerns;
        } else {
            $concerns = Database::fetchAll("SELECT id, name, code FROM concerns WHERE is_active = 1 ORDER BY name ASC") ?: [];
        }

        $staff           = User::getAll($filters);
        $roles           = Role::getActive();
        $productionUnits = ProductionUnit::getWithMetrics();

        $activeMenu = 'staff-all';
        $deptLower = strtolower($department);
        if ($deptLower === 'all') $activeMenu = 'staff-all';
        elseif ($deptLower === 'production') $activeMenu = 'staff-production';
        elseif ($deptLower === 'warehouse') $activeMenu = 'staff-warehouse';
        elseif ($deptLower === 'whatsapp') $activeMenu = 'staff-whatsapp';
        elseif ($deptLower === 'website') $activeMenu = 'staff-website';
        elseif ($deptLower === 'shop') $activeMenu = 'staff-shop';
        elseif ($deptLower === 'reports') $activeMenu = 'staff-reports';

        Response::view('admin.staff_list', [
            'pageTitle'       => 'Staff Management',
            'activeModule'    => 'staff',
            'activeMenu'      => $activeMenu,
            'breadcrumb'      => [['label' => 'Staff']],
            'staff'           => $staff,
            'roles'           => $roles,
            'productionUnits' => $productionUnits,
            'concerns'        => $concerns,
            'filters'         => $filters,
        ]);
    }

    public function viewStaff(string $id): void {
        $staff = User::findById((int)$id);
        if (!$staff || (int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::error(404, 'Staff member not found.');
            return;
        }

        $assignments = array_filter([
            'Production Unit' => $staff['production_unit_name'] ?? null,
            'Warehouse'       => $staff['warehouse_name'] ?? null,
            'Shop'            => $staff['shop_name'] ?? null,
            'Sales Channel'   => $staff['channel_name'] ?? null,
        ]);

        $userConcerns = User::getConcerns((int)$staff['id']);

        Response::view('admin.staff_view', [
            'pageTitle'    => 'Staff Details',
            'activeModule' => 'staff',
            'activeMenu'   => 'staff-all',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Details'],
            ],
            'staff'        => $staff,
            'assignments'  => $assignments,
            'userConcerns' => $userConcerns,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'staffPermissions'    => User::getPermissionIds((int)$staff['id']),
        ]);
    }

    /**
     * Show create staff form.
     */
    public function createStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        $concerns         = Database::fetchAll("SELECT * FROM concerns WHERE is_active = 1 ORDER BY id ASC") ?: [];
        $curConcernId     = currentConcernId() ?? (!empty($concerns) ? (int)$concerns[0]['id'] : 1);

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Create Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff-create',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Create'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'concerns'            => $concerns,
            'staffConcerns'       => [$curConcernId],
            'defaultConcernId'    => $curConcernId,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => [],
            'staff'               => null,
        ]);
    }

    /**
     * Store new staff.
     */
    public function storeStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $validator = Validator::make($_POST)
            ->required('username')
            ->required('full_name')
            ->required('password')
            ->required('role_id')
            ->minLength('password', 6)
            ->unique('username', 'users');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', null, 'Phone number');
        }

        if ($validator->fails()) {
            Session::set('_old_input', $_POST);
            Response::back('error', implode(' ', $validator->allErrors()));
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? 'PRODUCTION');

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Session::set('_old_input', $_POST);
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
        }

        try {
            // Process concern assignment and default login session concern
            $assignedConcerns = isset($_POST['concerns']) && is_array($_POST['concerns']) ? array_filter($_POST['concerns']) : [];
            $defaultConcernId = !empty($_POST['default_concern_id']) ? (int)$_POST['default_concern_id'] : null;

            if (!isSuperAdmin()) {
                $allowedCids = array_column(Database::fetchAll("SELECT id FROM concerns WHERE is_active = 1") ?: [], 'id');
                $assignedConcerns = array_values(array_intersect(array_map('intval', $assignedConcerns), $allowedCids));
                if (empty($assignedConcerns)) {
                    $assignedConcerns = [currentConcernId() ?? 1];
                }
                if (!$defaultConcernId || !in_array($defaultConcernId, $allowedCids)) {
                    $defaultConcernId = $assignedConcerns[0];
                }
            }

            if (empty($assignedConcerns)) {
                $fallbackCid = currentConcernId() ?? 1;
                $assignedConcerns = [$fallbackCid];
                $defaultConcernId = $fallbackCid;
            }

            if (!$defaultConcernId || !in_array($defaultConcernId, array_map('intval', $assignedConcerns))) {
                $defaultConcernId = (int)$assignedConcerns[0];
            }

            $_POST['concerns'] = $assignedConcerns;
            $_POST['default_concern_id'] = $defaultConcernId;

            $id = User::create($_POST);

            // Save explicitly checked custom permissions for this staff member
            if (isset($_POST['permissions'])) {
                $rawPerms = is_array($_POST['permissions']) ? array_filter($_POST['permissions']) : [];
                User::savePermissions($id, $rawPerms);
            }

            $newSnapshot = $this->prepareStaffSnapshot($_POST);
            AuditService::log('staff', 'create', 'users', $id, null, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member created successfully.');
        } catch (Exception $e) {
            error_log("Error creating staff: " . $e->getMessage());
            Response::back('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    /**
     * Helper to prepare human-readable staff attribute snapshots for audit logging.
     */
    private function prepareStaffSnapshot(array $data, ?array $userRecord = null): array {
        $roleName = $userRecord['role_name'] ?? null;
        if (!empty($data['role_id'])) {
            $role = Role::findById((int)$data['role_id']);
            $roleName = $role['name'] ?? $roleName;
        }

        $puName = $userRecord['production_unit_name'] ?? null;
        if (array_key_exists('production_unit_id', $data)) {
            $puId = (int)$data['production_unit_id'];
            $pu = $puId ? ProductionUnit::find($puId) : null;
            $puName = $pu['name'] ?? ($puId ? 'Unit #' . $puId : 'None (Unassigned)');
        }

        $whName = $userRecord['warehouse_name'] ?? 'None';
        $shopName = $userRecord['shop_name'] ?? 'None';
        $chanName = $userRecord['channel_name'] ?? 'None';

        $snapshot = [
            'Staff Name'        => $data['full_name'] ?? ($userRecord['full_name'] ?? ''),
            'Username'          => $data['username'] ?? ($userRecord['username'] ?? ''),
            'Role'              => $roleName ?: 'Staff',
            'Department'        => $data['department'] ?? ($userRecord['department'] ?? 'PRODUCTION'),
            'Email'             => !empty($data['email']) ? $data['email'] : ($userRecord['email'] ?? '-'),
            'Phone'             => !empty($data['phone']) ? $data['phone'] : ($userRecord['phone'] ?? '-'),
            'Production Unit'   => $puName ?: 'None',
            'Warehouse'         => $whName ?: 'None',
            'Shop'              => $shopName ?: 'None',
            'Sales Channel'     => $chanName ?: 'None',
            'Reports Access'    => $data['reports_access'] ?? ($userRecord['reports_access'] ?? 'ALL'),
            'Account Status'    => isset($data['is_active']) ? ((int)$data['is_active'] ? 'Active' : 'Deactivated') : ($userRecord ? ((int)($userRecord['is_active'] ?? 1) ? 'Active' : 'Deactivated') : 'Active'),
        ];

        if (!empty($data['password'])) {
            $snapshot['Password'] = '[UPDATED / ENCRYPTED]';
        }

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $snapshot['Custom Permissions'] = count($data['permissions']) . ' specific permissions assigned';
        }

        $concernInfo = '-';
        if (!empty($data['concerns']) && is_array($data['concerns'])) {
            $cids = array_map('intval', $data['concerns']);
            if (!empty($cids)) {
                $inClause = implode(',', $cids);
                $cNames = Database::fetchAll("SELECT CONCAT(code, ': ', name) as ctitle FROM concerns WHERE id IN ($inClause)");
                $concernInfo = implode(', ', array_column($cNames, 'ctitle'));
            }
        } elseif (!empty($userRecord['assigned_concerns_str'])) {
            $concernInfo = str_replace('||', ', ', $userRecord['assigned_concerns_str']);
        }

        $defaultConcernName = '-';
        if (!empty($data['default_concern_id'])) {
            $dc = Database::fetchOne("SELECT name, code FROM concerns WHERE id = ?", [(int)$data['default_concern_id']]);
            if ($dc) $defaultConcernName = "{$dc['code']} - {$dc['name']}";
        } elseif (!empty($userRecord['default_concern_name'])) {
            $defaultConcernName = ($userRecord['default_concern_code'] ?? '') . ' - ' . $userRecord['default_concern_name'];
        }

        $snapshot['Authorized Concerns'] = $concernInfo;
        $snapshot['Default Concern'] = $defaultConcernName;

        return $snapshot;
    }

    /**
     * Show edit staff form.
     */
    public function editStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::redirect(url('admin/staff'), 'warning', 'Super Administrator account is managed under System Settings.');
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        $concerns         = Database::fetchAll("SELECT * FROM concerns WHERE is_active = 1 ORDER BY id ASC") ?: [];
        $staffConcerns    = User::getConcernIds($staffId);
        $defaultConcernId = (int)($staff['default_concern_id'] ?? 0);
        if ($defaultConcernId === 0 && !empty($staffConcerns)) {
            $defaultConcernId = $staffConcerns[0];
        }

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Edit Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Edit'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'concerns'            => $concerns,
            'staffConcerns'       => $staffConcerns,
            'defaultConcernId'    => $defaultConcernId,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => User::getPermissionIds($staffId),
            'staff'               => $staff,
        ]);
    }

    /**
     * Update staff.
     */
    public function updateStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $oldStaff = User::findById($staffId);
        if (!$oldStaff) Response::error(404, 'Staff not found.');

        $validator = Validator::make($_POST)
            ->required('full_name')
            ->required('role_id');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', $staffId, 'Phone number');
        }

        if ($validator->fails()) {
            Response::back('error', implode(' ', $validator->allErrors()));
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? ($oldStaff['department'] ?? 'PRODUCTION'));

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
        }

        try {
            // Process concern assignment and default login session concern
            if (isset($_POST['concerns'])) {
                $assignedConcerns = is_array($_POST['concerns']) ? array_filter($_POST['concerns']) : [];
                $defaultConcernId = !empty($_POST['default_concern_id']) ? (int)$_POST['default_concern_id'] : null;

                if (!isSuperAdmin()) {
                    $allowedCids = array_column(Database::fetchAll("SELECT id FROM concerns WHERE is_active = 1") ?: [], 'id');
                    $assignedConcerns = array_values(array_intersect(array_map('intval', $assignedConcerns), $allowedCids));
                    if (empty($assignedConcerns)) {
                        $assignedConcerns = [currentConcernId() ?? 1];
                    }
                    if (!$defaultConcernId || !in_array($defaultConcernId, $allowedCids)) {
                        $defaultConcernId = $assignedConcerns[0];
                    }
                }

                if (empty($assignedConcerns)) {
                    $fallbackCid = currentConcernId() ?? 1;
                    $assignedConcerns = [$fallbackCid];
                    $defaultConcernId = $fallbackCid;
                }

                if (!$defaultConcernId || !in_array($defaultConcernId, array_map('intval', $assignedConcerns))) {
                    $defaultConcernId = (int)$assignedConcerns[0];
                }

                $_POST['concerns'] = $assignedConcerns;
                $_POST['default_concern_id'] = $defaultConcernId;
            }

            $oldSnapshot = $this->prepareStaffSnapshot([], $oldStaff);
            $newSnapshot = $this->prepareStaffSnapshot($_POST, $oldStaff);
            User::update($staffId, $_POST);

            // Sync explicitly checked custom permissions for this staff member
            if (isset($_POST['permissions'])) {
                $rawPerms = is_array($_POST['permissions']) ? array_filter($_POST['permissions']) : [];
                User::savePermissions($staffId, $rawPerms);
            }

            AuditService::log('staff', 'update', 'users', $staffId, $oldSnapshot, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member updated successfully.');
        } catch (Exception $e) {
            error_log("Error updating staff: " . $e->getMessage());
            Response::back('error', 'Failed to update staff member: ' . $e->getMessage());
        }
    }

    /**
     * AJAX check phone format & uniqueness.
     */
    public function checkPhoneAvailability(): void {
        $phone = trim((string)($_GET['phone'] ?? ''));
        $exceptId = !empty($_GET['except_id']) ? (int)$_GET['except_id'] : null;

        if ($phone === '') {
            Response::json(['valid' => true, 'available' => true, 'message' => '']);
            return;
        }

        // Clean phone
        $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', $phone);
        $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);

        // Check if non-digits are present
        if (!ctype_digit($cleanPhone)) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must only contain numeric digits (0-9).'
            ]);
            return;
        }

        // Check length
        if (strlen($cleanPhone) !== 10) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must be exactly 10 digits.'
            ]);
            return;
        }

        // Check Indian starting digit (6-9)
        if (!in_array($cleanPhone[0], ['6', '7', '8', '9'])) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Indian mobile numbers must start with 6, 7, 8, or 9.'
            ]);
            return;
        }

        // Check duplicate in database
        $sql = "SELECT id, full_name, username FROM users WHERE phone = ?";
        $params = [$cleanPhone];
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }

        $existing = Database::fetchOne($sql, $params);
        if ($existing) {
            Response::json([
                'valid' => true,
                'available' => false,
                'message' => "Phone number is already registered to '{$existing['full_name']}' (@{$existing['username']})."
            ]);
            return;
        }

        Response::json([
            'valid' => true,
            'available' => true,
            'message' => 'Phone number is valid and available.'
        ]);
    }

    /**
     * Toggle staff active status.
     */
    public function toggleStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to update staff status.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::back('error', 'Super Administrator account status cannot be modified here.');
        }

        User::toggleActive($staffId);
        $action = $staff['is_active'] ? 'deactivated' : 'activated';
        AuditService::log(
            'staff',
            'status_change',
            'users',
            $staffId,
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => $staff['is_active'] ? 'Active' : 'Deactivated'
            ],
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => !$staff['is_active'] ? 'Active' : 'Deactivated'
            ]
        );
        Response::redirect(url('admin/staff'), 'success', "Staff member $action successfully.");
    }

    /**
     * Delete staff member.
     */
    public function deleteStaff(string $id): void {
        if (!hasPermission('staff', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($staffId === $currentUserId) {
            Response::back('error', 'You cannot delete your own account.');
        }

        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::back('error', 'Super Administrator account cannot be deleted.');
        }

        try {
            $delSnapshot = $this->prepareStaffSnapshot([], $staff);
            User::delete($staffId);
            AuditService::log('staff', 'delete', 'users', $staffId, $delSnapshot, null);
            Response::redirect(url('admin/staff'), 'success', "Staff member '{$staff['full_name']}' deleted successfully.");
        } catch (Exception $e) {
            error_log("Error deleting staff: " . $e->getMessage());
            Response::back('error', 'Failed to delete staff member. Make sure no dependent records exist.');
        }
    }

    /**
     * Roles List.
     */
    public function roles(): void {
        if (!hasPermission('roles', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view roles.');
            return;
        }

        $roles = Role::getAll();
        $roles = Database::fetchAll("SELECT * FROM roles WHERE id != 1 ORDER BY name ASC");
        // Count users per role
        foreach ($roles as &$role) {
            $role['user_count'] = Role::getUserCount($role['id']);
        }

        Response::view('admin.roles_list', [
            'pageTitle'    => 'Roles & Permissions',
            'activeModule' => 'staff',
            'activeMenu'   => 'roles',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles & Permissions'],
            ],
            'roles'        => $roles,
        ]);
    }

    /**
     * Create role form.
     */
    public function createRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
        }

        Response::view('admin.role_form', [
            'pageTitle'              => 'Add New Role',
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Create'],
            ],
            'role'                   => null,
            'staff'                  => null,
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
            'assignedPermissionIds'  => [],
            'concerns'               => Database::fetchAll("SELECT id, name, code, is_active FROM concerns ORDER BY id ASC"),
        ]);
    }

    /**
     * Store new role.
     */
    public function storeRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $name               = trim($_POST['name'] ?? '');
        $slugInput          = trim($_POST['slug'] ?? '');
        $description        = trim($_POST['description'] ?? '');
        $concernScope       = trim($_POST['concern_scope'] ?? 'ALL');
        $applicableConcerns = $_POST['applicable_concerns'] ?? [];
        $isActive           = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds      = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
        }

        // Prepare unique role slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . time();
        $count = 1;
        while (Database::fetchOne("SELECT id FROM roles WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            $roleId = Role::create([
                'name'               => $name,
                'slug'               => $slug,
                'description'        => $description,
                'concern_scope'      => $concernScope,
                'applicable_concerns'=> $applicableConcerns,
                'is_active'          => $isActive
            ]);

            // Save selected permissions
            if (!empty($permissionIds)) {
                Role::savePermissions($roleId, $permissionIds);
            }

            AuditService::log('roles', 'create', 'roles', $roleId, null, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' created successfully with assigned permissions.");
        } catch (Exception $e) {
            error_log("Error creating role: " . $e->getMessage());
            Response::back('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Edit role form.
     */
    public function editRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) Response::error(404, 'Role not found.');

        $assignedPermissionIds = Role::getRolePermissionIds((int) $id);

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
        }

        Response::view('admin.role_form', [
            'pageTitle'              => 'Edit Role: ' . ($role['name'] ?? ''),
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Edit'],
            ],
            'role'                   => $role,
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
            'assignedPermissionIds'  => $assignedPermissionIds,
            'concerns'               => Database::fetchAll("SELECT id, name, code, is_active FROM concerns ORDER BY id ASC"),
        ]);
    }

    /**
     * Update role.
     */
    public function updateRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $roleId             = (int) $id;
        $role               = Role::findById($roleId);
        if (!$role) Response::error(404, 'Role not found.');

        $name               = trim($_POST['name'] ?? '');
        $slugInput          = trim($_POST['slug'] ?? '');
        $description        = trim($_POST['description'] ?? '');
        $concernScope       = trim($_POST['concern_scope'] ?? 'ALL');
        $applicableConcerns = $_POST['applicable_concerns'] ?? [];
        $isActive           = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds      = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
        }

        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . $roleId;
        $count = 1;
        while ($existing = Database::fetchOne("SELECT id FROM roles WHERE slug = ? AND id != ?", [$slug, $roleId])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            Role::update($roleId, [
                'name'               => $name,
                'slug'               => $slug,
                'description'        => $description,
                'concern_scope'      => $concernScope,
                'applicable_concerns'=> $applicableConcerns,
                'is_active'          => $isActive
            ]);

            // Sync updated permissions
            Role::savePermissions($roleId, $permissionIds);

            AuditService::log('roles', 'update', 'roles', $roleId, $role, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' updated successfully.");
        } catch (Exception $e) {
            error_log("Error updating role: " . $e->getMessage());
            Response::back('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Manage role permissions.
     */
    public function managePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to manage permissions.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) Response::error(404, 'Role not found.');

        $groupedPermissions = Role::getPermissionsGrouped();
        $hierarchicalModules = Role::getHierarchicalPermissions();
        $rolePermissionIds = Role::getRolePermissionIds((int) $id);

        Response::view('admin.permissions', [
            'pageTitle'           => 'Manage Permissions — ' . $role['name'],
            'activeModule'        => 'staff',
            'activeMenu'          => 'roles',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Permissions'],
            ],
            'role'                => $role,
            'groupedPermissions'  => $groupedPermissions,
            'hierarchicalModules' => $hierarchicalModules,
            'rolePermissionIds'   => $rolePermissionIds,
        ]);
    }

    /**
     * Save role permissions.
     */
    public function savePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to save permissions.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $roleId = (int) $id;
        $permissionIds = $_POST['permissions'] ?? [];

        try {
            Role::savePermissions($roleId, $permissionIds);
            AuditService::log('roles', 'update_permissions', 'roles', $roleId, null, ['permissions' => $permissionIds]);
            Response::redirect(url('admin/roles'), 'success', 'Permissions updated successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to update permissions.');
        }
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $id): void {
        if (!hasPermission('roles', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid security token.');
        }

        $roleId = (int)$id;
        if ($roleId === 1) {
            Response::back('error', 'Super Admin role cannot be deleted.');
        }

        try {
            Role::delete($roleId);
            AuditService::log('roles', 'delete', 'roles', $roleId);
            Response::redirect(url('admin/roles'), 'success', 'Role deleted successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRODUCTION UNITS MANAGEMENT
    // =========================================================================

    public function storeProductionUnit(): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        if (empty($code)) {
            $num = 1;
            do {
                $code = 'PU-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = ProductionUnit::findByCode($code);
                if ($exists) {
                    $num++;
                } else {
                    break;
                }
            } while (true);
        } else {
            $existing = ProductionUnit::findByCode($code);
            if ($existing) {
                Response::redirect(url('admin/dashboard?tab=production-units'), 'error', "Production Unit code '{$code}' already exists (used by '{$existing['name']}'). Please use a unique code (e.g. PU-02) or leave it blank for auto-generation.");
                return;
            }
        }

        try {
            $logoUrl = $this->uploadConcernLogo($_FILES['logo'] ?? [], 'production/units');
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'logo_url'     => $logoUrl,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ]);

            AuditService::log('production_units', 'create', 'production_units', $id, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', "Production Unit '{$name}' created successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error creating unit: ' . $e->getMessage());
        }
    }

    public function updateProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        $unit = ProductionUnit::find($unitId);
        if (!$unit) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit not found.');
            return;
        }

        try {
            $logoUrl = $this->uploadConcernLogo($_FILES['logo'] ?? [], 'production/units') ?? ($unit['logo_url'] ?? null);
            ProductionUnit::update($unitId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'logo_url'     => $logoUrl,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ]);

            AuditService::log('production_units', 'update', 'production_units', $unitId, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', "Production Unit '{$name}' updated successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error updating unit: ' . $e->getMessage());
        }
    }

    public function deleteProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        try {
            $unit = ProductionUnit::find($unitId);
            $unitName = $unit['name'] ?? "ID #{$unitId}";

            try { Database::execute("SET FOREIGN_KEY_CHECKS = 0"); } catch (\Throwable $e) {}
            Database::beginTransaction();

            // Unlink all programs, batches, outwards, reworks, damages, and users referencing this production unit
            try { Database::execute("UPDATE production_programs SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE cutting_batches SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE stitching_batches SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE production_outwards SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE production_warehouse_reworks SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE warehouse_damages SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}
            try { Database::execute("UPDATE users SET production_unit_id = NULL WHERE production_unit_id = ?", [$unitId]); } catch (\Throwable $e) {}

            ProductionUnit::delete($unitId);

            Database::commit();
            try { Database::execute("SET FOREIGN_KEY_CHECKS = 1"); } catch (\Throwable $e) {}

            AuditService::log('production_units', 'delete', 'production_units', $unitId, "Deleted Production Facility: {$unitName}");
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', "Production Unit \"{$unitName}\" deleted successfully. Action permanently recorded in Live Staff Activity & System Change Logs.");
        } catch (Exception $e) {
            Database::rollback();
            try { Database::execute("SET FOREIGN_KEY_CHECKS = 1"); } catch (Exception $ignored) {}
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error deleting production unit: ' . $e->getMessage());
        }
    }

    public function toggleProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        ProductionUnit::toggleActive($unitId);
        AuditService::log('production_units', 'toggle_active', 'production_units', $unitId);
        Response::redirect(url('admin/dashboard?tab=production-units'), 'success', 'Production Unit status updated.');
    }

    public function ajaxAddProductionUnit(): void {
        if (!CSRF::validateRequest()) {
            Response::jsonError('Invalid security token.', [], 403);
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::jsonError('Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::jsonError('Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::jsonError('Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::jsonError('Enter a valid 15-character GSTIN.');
        }

        if (empty($code)) {
            $num = 1;
            do {
                $code = 'PU-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = ProductionUnit::findByCode($code);
                if ($exists) {
                    $num++;
                } else {
                    break;
                }
            } while (true);
        } else {
            $existing = ProductionUnit::findByCode($code);
            if ($existing) {
                Response::jsonError("Production Unit code '{$code}' already exists (used by '{$existing['name']}'). Please use a unique code (e.g. PU-02) or leave it blank for auto-generation.");
                return;
            }
        }

        try {
            $logoUrl = $this->uploadConcernLogo($_FILES['logo'] ?? [], 'production/units');
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'logo_url'     => $logoUrl,
                'is_active'    => 1,
            ]);

            $unit = ProductionUnit::find($id);
            AuditService::log('production_units', 'create_ajax', 'production_units', $id, null, $_POST);
            Response::jsonSuccess('Production Unit added successfully!', ['unit' => $unit]);
        } catch (Exception $e) {
            Response::jsonError('Database error: ' . $e->getMessage());
        }
    }

    private function uploadConcernLogo(array $file, string $folder): ?string {
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($uploadError !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('The logo could not be received. Please select the image again.');
        }

        $fileSize = filesize($file['tmp_name']);
        if ($fileSize === false || $fileSize > 5 * 1024 * 1024) {
            throw new RuntimeException('The logo must be 5 MB or smaller.');
        }

        $imageInfo = getimagesize($file['tmp_name']);
        $mimeType = $imageInfo['mime'] ?? '';
        if (!$imageInfo || !in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/avif'], true)) {
            throw new RuntimeException('Upload a valid JPG, PNG, WEBP, or AVIF logo image.');
        }

        $file['type'] = $mimeType;
        $logoUrl = CloudinaryService::upload($file, 'grm_inventory/' . trim($folder, '/'), 'image');
        if (!$logoUrl) {
            throw new RuntimeException('The logo could not be uploaded to Cloudinary. Check the Cloudinary configuration and try again.');
        }

        return $logoUrl;
    }

        /**
     * Universal QR & Barcode Status Finder API endpoint.
     */
    public function ajaxQrStatusFinder(): void {
        $rawCode = trim((string)($_GET['code'] ?? ''));
        if ($rawCode === '') {
            Response::json([
                'success' => false,
                'message' => 'Please provide a valid QR code or barcode to scan.'
            ], 400);
            return;
        }

        try {
            $result = QrFinderService::trace($rawCode);
            if (empty($result['success'])) {
                Response::json($result, 404);
                return;
            }
            Response::json($result);
        } catch (Throwable $e) {
            error_log("Universal QR Finder API error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => "This QR code ({$rawCode}) is not in any inventory sections.",
                'code'    => $rawCode
            ], 404);
        }
    }

    /**
     * Real-time duplicate & validation check for Production Unit creation/editing.
     */
    public function ajaxCheckProductionUnitDuplicate(): void {
        $field = trim((string)($_GET['field'] ?? ''));
        $value = trim((string)($_GET['value'] ?? ''));
        $exceptId = !empty($_GET['except_id']) ? (int)$_GET['except_id'] : null;

        if ($field === '' || $value === '') {
            Response::json(['valid' => true, 'available' => true, 'message' => '']);
            return;
        }

        if ($field === 'name') {
            if (mb_strlen($value) < 2) {
                Response::json(['valid' => false, 'available' => false, 'message' => 'Unit Name must be at least 2 characters.']);
                return;
            }
            $sql = "SELECT id, name FROM production_units WHERE LOWER(name) = LOWER(?)";
            $params = [$value];
            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            $existing = Database::fetchOne($sql, $params);
            if ($existing) {
                Response::json([
                    'valid' => true,
                    'available' => false,
                    'message' => "Production unit name '{$existing['name']}' is already in use."
                ]);
                return;
            }
            Response::json(['valid' => true, 'available' => true, 'message' => 'Unit name is available.']);
            return;
        }

        if ($field === 'code') {
            if (mb_strlen($value) < 2) {
                Response::json(['valid' => false, 'available' => false, 'message' => 'Unit Code must be at least 2 characters.']);
                return;
            }
            $sql = "SELECT id, code FROM production_units WHERE LOWER(code) = LOWER(?)";
            $params = [$value];
            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            $existing = Database::fetchOne($sql, $params);
            if ($existing) {
                Response::json([
                    'valid' => true,
                    'available' => false,
                    'message' => "Unit code '{$existing['code']}' is already assigned to another unit."
                ]);
                return;
            }
            Response::json(['valid' => true, 'available' => true, 'message' => 'Unit code is available.']);
            return;
        }

        if ($field === 'phone') {
            $clean = preg_replace('/\D+/', '', $value);
            if (strlen($clean) === 12 && substr($clean, 0, 2) === '91') $clean = substr($clean, 2);
            if (strlen($clean) !== 10) {
                Response::json(['valid' => false, 'available' => false, 'message' => 'Phone number must be exactly 10 digits.']);
                return;
            }
            if (!in_array($clean[0], ['6','7','8','9'])) {
                Response::json(['valid' => false, 'available' => false, 'message' => 'Indian mobile number must start with 6, 7, 8, or 9.']);
                return;
            }
            $sql = "SELECT id, name FROM production_units WHERE phone = ?";
            $params = [$clean];
            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            $existing = Database::fetchOne($sql, $params);
            if ($existing) {
                Response::json([
                    'valid' => true,
                    'available' => false,
                    'message' => "Phone number is already associated with production unit '{$existing['name']}'."
                ]);
                return;
            }
            Response::json(['valid' => true, 'available' => true, 'message' => 'Phone number is available.']);
            return;
        }

        if ($field === 'gst_number') {
            $gst = strtoupper($value);
            if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gst)) {
                Response::json(['valid' => false, 'available' => false, 'message' => 'Invalid GSTIN format (15 characters alphanumeric).']);
                return;
            }
            $sql = "SELECT id, name FROM production_units WHERE UPPER(gst_number) = ?";
            $params = [$gst];
            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            $existing = Database::fetchOne($sql, $params);
            if ($existing) {
                Response::json([
                    'valid' => true,
                    'available' => false,
                    'message' => "GSTIN is already registered to production unit '{$existing['name']}'."
                ]);
                return;
            }
            Response::json(['valid' => true, 'available' => true, 'message' => 'GSTIN is valid and available.']);
            return;
        }

        Response::json(['valid' => true, 'available' => true, 'message' => '']);
    }
}
