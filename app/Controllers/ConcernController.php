<?php
/**
 * ConcernController
 * Manages Multi-Concern Enterprise Architecture (business entities, module matrices, and scoping context).
 */

class ConcernController {
    /**
     * Standard module definitions with metadata for assignment matrix.
     */
    public static array $STANDARD_MODULES = [
        'production' => [
            'name'        => 'Manufacturing & Production',
            'icon'        => 'bi bi-gear-wide-connected',
            'badge_color' => 'primary',
            'desc'        => 'Fabric rolls, production programs, cutting, stitching, labels, QC, and packing batch outward.'
        ],
        'purchases' => [
            'name'        => 'Purchases & Suppliers',
            'icon'        => 'bi bi-truck',
            'badge_color' => 'info',
            'desc'        => 'Supplier sourcing, purchase orders (Fabric rolls, raw materials, third-party), and mapping.'
        ],
        'warehouse' => [
            'name'        => 'Central Warehousing',
            'icon'        => 'bi bi-boxes',
            'badge_color' => 'success',
            'desc'        => 'GRN goods inwards, inventory stock storage, bin/rack allocations, and outward dispatch.'
        ],
        'retail_pos' => [
            'name'        => 'Retail Store & POS',
            'icon'        => 'bi bi-shop',
            'badge_color' => 'warning',
            'desc'        => 'Direct factory showroom billing, barcode scanning, thermal receipts, and cash drawers.'
        ],
        'digital_sales' => [
            'name'        => 'Digital Order Fulfillment',
            'icon'        => 'bi bi-phone',
            'badge_color' => 'primary',
            'desc'        => 'WhatsApp and eCommerce website order packing, shipping, and digital fulfillment tracking.'
        ],
        'returns' => [
            'name'        => 'Returns & Exchanges',
            'icon'        => 'bi bi-arrow-return-left',
            'badge_color' => 'danger',
            'desc'        => 'RMA customer returns, replacement exchanges, refund reconciliations, and garment audits.'
        ],
        'expenses' => [
            'name'        => 'Factory Expenses',
            'icon'        => 'bi bi-cash-stack',
            'badge_color' => 'secondary',
            'desc'        => 'Petty cash vouchers, operational unit expenses, overhead allocations, and unit ledgers.'
        ],
        'assets' => [
            'name'        => 'Plant Machinery & Assets',
            'icon'        => 'bi bi-tools',
            'badge_color' => 'dark',
            'desc'        => 'Asset registry, equipment calibration, preventive maintenance schedules, and breakdown logs.'
        ]
    ];

    /**
     * Check if user is authorized to manage concerns.
     */
    private function authorizeAdmin(): void {
        if (!isSuperAdmin() && !hasPermission('settings', 'view')) {
            Session::flash('error', 'Unauthorized access to Concern Management.');
            Response::redirect(url('admin/dashboard'));
            exit;
        }
    }

    /**
     * List all concerns.
     */
    public function index(): void {
        $this->authorizeAdmin();

        $search = trim($_GET['search'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM concern_modules cm WHERE cm.concern_id = c.id AND cm.is_enabled = 1) AS enabled_modules_count,
                   (SELECT COUNT(*)
                    FROM user_concerns uc
                    JOIN users assigned_user ON assigned_user.id = uc.user_id
                    LEFT JOIN roles assigned_role ON assigned_role.id = assigned_user.role_id
                    WHERE uc.concern_id = c.id
                      AND (assigned_user.role_id IS NULL OR assigned_user.role_id != 1)
                      AND (assigned_role.slug IS NULL OR assigned_role.slug != 'super_admin')) AS assigned_users_count,
                   (SELECT COUNT(*) FROM production_units pu WHERE pu.concern_id = c.id) AS production_units_count
            FROM concerns c
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR c.code LIKE ? OR c.legal_name LIKE ? OR c.gst_number LIKE ?)";
            $wildcard = "%{$search}%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($statusFilter === 'active') {
            $sql .= " AND c.is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $sql .= " AND c.is_active = 0";
        }

        $sql .= " ORDER BY c.id ASC";
        $concerns = Database::fetchAll($sql, $params);

        // Fetch enabled modules map for preview badges
        $allModules = Database::fetchAll("SELECT concern_id, module_key FROM concern_modules WHERE is_enabled = 1");
        $modulesByConcern = [];
        foreach ($allModules as $m) {
            $modulesByConcern[$m['concern_id']][] = $m['module_key'];
        }

        // Fetch all active staff members for quick modal assignment & preview
        $allStaff = Database::fetchAll("
            SELECT u.id, u.username, u.full_name, u.email, u.phone, u.department, u.staff_role,
                   r.name as role_name, r.slug as role_slug
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.is_active = 1
              AND (u.role_id IS NULL OR u.role_id != 1)
              AND (r.slug IS NULL OR r.slug != 'super_admin')
            ORDER BY u.full_name ASC
        ");

        // Fetch assigned staff by concern
        $allUserConcerns = Database::fetchAll("
            SELECT uc.concern_id, uc.user_id, uc.is_default, u.full_name, u.username, u.email, u.phone, u.department,
                   r.name as role_name
            FROM user_concerns uc
            JOIN users u ON uc.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.is_active = 1
              AND (u.role_id IS NULL OR u.role_id != 1)
              AND (r.slug IS NULL OR r.slug != 'super_admin')
            ORDER BY uc.is_default DESC, u.full_name ASC
        ");
        $staffByConcern = [];
        foreach ($allUserConcerns as $uc) {
            $staffByConcern[$uc['concern_id']][] = $uc;
        }

        $totalConcerns = (int)Database::fetchColumn("SELECT COUNT(*) FROM concerns");
        $activeConcerns = (int)Database::fetchColumn("SELECT COUNT(*) FROM concerns WHERE is_active = 1");
        $activeConcernId = currentConcernId();

        Response::view('admin.concerns.index', [
            'pageTitle'        => 'Business Concerns & Entities',
            'activeModule'     => 'concerns',
            'activeMenu'       => 'concerns-list',
            'concerns'         => $concerns,
            'modulesByConcern' => $modulesByConcern,
            'allStaff'         => $allStaff,
            'staffByConcern'   => $staffByConcern,
            'totalConcerns'    => $totalConcerns,
            'activeConcerns'   => $activeConcerns,
            'activeConcernId'  => $activeConcernId,
            'search'           => $search,
            'statusFilter'     => $statusFilter,
            'breadcrumb'       => [
                ['label' => 'Dashboard', 'url' => url('admin/dashboard')],
                ['label' => 'Concerns', 'url' => null]
            ]
        ]);
    }

    /**
     * Concern registration form.
     */
    public function create(): void {
        $this->authorizeAdmin();

        $roles = Role::getActive();

        Response::view('admin.concerns.create', [
            'pageTitle'       => 'Add New Business Concern',
            'activeModule'    => 'concerns',
            'activeMenu'      => 'concerns-create',
            'roles'           => $roles,
            'breadcrumb'      => [
                ['label' => 'Dashboard', 'url' => url('admin/dashboard')],
                ['label' => 'Concerns', 'url' => url('admin/concerns')],
                ['label' => 'Create Concern', 'url' => null]
            ]
        ]);
    }

    /**
     * Store new concern.
     */
    public function store(): void {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect(url('admin/concerns'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $concernType = 'HEAD_OFFICE';
        $legalName = trim($_POST['legal_name'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));
        $panNumber = strtoupper(trim($_POST['pan_number'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $invoicePrefix = trim($_POST['invoice_prefix'] ?? 'INV-');
        $poPrefix = trim($_POST['po_prefix'] ?? 'PO-');
        $dispatchPrefix = trim($_POST['dispatch_prefix'] ?? 'DSP-');
        $selectedModules = $_POST['modules'] ?? [];

        if (empty($name)) {
            Session::flash('error', 'Concern Display Name is required.');
            Response::redirect(url('admin/concerns/create'));
            return;
        }

        if (empty($code)) {
            Session::flash('error', 'Short Concern Code (e.g. CN-02, GRM-TEX) is required.');
            Response::redirect(url('admin/concerns/create'));
            return;
        }

        if ($contactError = $this->validateConcernContactFields($phone, $email, $address, $city, $state, $pincode)) {
            Session::flash('error', $contactError);
            Response::redirect(url('admin/concerns/create'));
            return;
        }

        // Check unique code
        $exists = Database::fetchOne("SELECT id FROM concerns WHERE code = ?", [$code]);
        if ($exists) {
            Session::flash('error', "Concern code '{$code}' is already registered. Please choose a unique code.");
            Response::redirect(url('admin/concerns/create'));
            return;
        }

        $logoUrl = null;
        try {
            $logoUrl = $this->handleLogoUpload(null);
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('admin/concerns/create'));
            return;
        }

        $creatorId = Auth::id() ?? 1;

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO concerns (
                    name, code, concern_type, legal_name, gst_number, pan_number,
                    phone, email, address, city, state, pincode,
                    logo_url, invoice_prefix, po_prefix, dispatch_prefix,
                    is_active, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    1, ?, NOW()
                )
            ");
            $stmt->execute([
                $name, $code, $concernType, $legalName ?: null, $gstNumber ?: null, $panNumber ?: null,
                $phone ?: null, $email ?: null, $address ?: null, $city ?: null, $state ?: null, $pincode ?: null,
                $logoUrl, $invoicePrefix, $poPrefix, $dispatchPrefix,
                $creatorId
            ]);
            $concernId = (int)$db->lastInsertId();

            // Provision all standard modules for the new concern (module access is governed by staff role/permissions)
            $modStmt = $db->prepare("
                INSERT INTO concern_modules (concern_id, module_key, is_enabled)
                VALUES (?, ?, 1)
            ");
            foreach (array_keys(self::$STANDARD_MODULES) as $mKey) {
                $modStmt->execute([$concernId, $mKey]);
            }

            // Auto-link/create Production Unit if production module enabled
            if (in_array('production', $selectedModules, true)) {
                $existingPu = Database::fetchOne("SELECT id FROM production_units WHERE concern_id = ?", [$concernId]);
                if (!$existingPu) {
                    $puCode = 'PU-' . $code;
                    $suffix = 1;
                    while (Database::fetchOne("SELECT id FROM production_units WHERE code = ?", [$puCode])) {
                        $puCode = 'PU-' . $code . '-' . (++$suffix);
                    }
                    $db->prepare("
                        INSERT INTO production_units (concern_id, name, code, address, phone, gst_number, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ")->execute([$concernId, $name . ' (Plant)', $puCode, $address ?: null, $phone ?: null, $gstNumber ?: null]);
                }
            }

            // Auto-link/create Warehouse if warehouse module enabled
            if (in_array('warehouse', $selectedModules, true)) {
                $existingWh = Database::fetchOne("SELECT id FROM warehouses WHERE concern_id = ?", [$concernId]);
                if (!$existingWh) {
                    $whCode = 'WH-' . $code;
                    $suffix = 1;
                    while (Database::fetchOne("SELECT id FROM warehouses WHERE code = ?", [$whCode])) {
                        $whCode = 'WH-' . $code . '-' . (++$suffix);
                    }
                    $db->prepare("
                        INSERT INTO warehouses (concern_id, name, code, address, phone, gst_number, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ")->execute([$concernId, $name . ' (Warehouse)', $whCode, $address ?: null, $phone ?: null, $gstNumber ?: null]);
                }
            }

            $db->commit();

            Session::flash('success', "Business Concern '{$name}' ({$code}) created successfully.");
            Response::redirect(url('admin/concerns'));
        } catch (Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Error creating concern: ' . $e->getMessage());
            Response::redirect(url('admin/concerns/create'));
        }
    }

    /**
     * Edit concern form.
     */
    public function edit(string $id): void {
        $this->authorizeAdmin();

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        Response::view('admin.concerns.edit', [
            'pageTitle'         => 'Edit Concern: ' . $concern['name'],
            'activeModule'      => 'concerns',
            'activeMenu'        => 'concerns-list',
            'concern'           => $concern,
            'breadcrumb'        => [
                ['label' => 'Dashboard', 'url' => url('admin/dashboard')],
                ['label' => 'Concerns', 'url' => url('admin/concerns')],
                ['label' => 'Edit Concern', 'url' => null]
            ]
        ]);
    }

    /**
     * Update concern settings.
     */
    public function update(string $id): void {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect(url('admin/concerns'));
            return;
        }

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $validTypes = ['HEAD_OFFICE', 'PRODUCTION', 'WAREHOUSE', 'RETAIL'];
        $concernType = strtoupper(trim($_POST['concern_type'] ?? 'HEAD_OFFICE'));
        if (!in_array($concernType, $validTypes, true)) {
            $concernType = 'HEAD_OFFICE';
        }
        $legalName = trim($_POST['legal_name'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));
        $panNumber = strtoupper(trim($_POST['pan_number'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $invoicePrefix = trim($_POST['invoice_prefix'] ?? 'INV-');
        $poPrefix = trim($_POST['po_prefix'] ?? 'PO-');
        $dispatchPrefix = trim($_POST['dispatch_prefix'] ?? 'DSP-');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            Session::flash('error', 'Concern Display Name is required.');
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
            return;
        }

        if (empty($code)) {
            Session::flash('error', 'Concern Code is required.');
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
            return;
        }

        if ($contactError = $this->validateConcernContactFields($phone, $email, $address, $city, $state, $pincode)) {
            Session::flash('error', $contactError);
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
            return;
        }

        if ($isActive === 0 && (int)($concern['is_active'] ?? 1) === 1) {
            $otherActive = (int)Database::fetchColumn("SELECT COUNT(*) FROM concerns WHERE is_active = 1 AND id != ?", [$concernId]);
            if ($otherActive <= 0) {
                Session::flash('error', 'Cannot deactivate the only active business concern.');
                Response::redirect(url("admin/concerns/edit/{$concernId}"));
                return;
            }
        }

        // Check unique code excluding this concern
        $exists = Database::fetchOne("SELECT id FROM concerns WHERE code = ? AND id != ?", [$code, $concernId]);
        if ($exists) {
            Session::flash('error', "Concern code '{$code}' is already registered by another concern.");
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
            return;
        }

        $logoUrl = $concern['logo_url'] ?? null;
        try {
            $logoUrl = $this->handleLogoUpload($concern['logo_url'] ?? null);
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                UPDATE concerns SET
                    name = ?, code = ?, concern_type = ?, legal_name = ?, gst_number = ?, pan_number = ?,
                    phone = ?, email = ?, address = ?, city = ?, state = ?, pincode = ?,
                    logo_url = ?, invoice_prefix = ?, po_prefix = ?, dispatch_prefix = ?, is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $code, $concernType, $legalName ?: null, $gstNumber ?: null, $panNumber ?: null,
                $phone ?: null, $email ?: null, $address ?: null, $city ?: null, $state ?: null, $pincode ?: null,
                $logoUrl, $invoicePrefix, $poPrefix, $dispatchPrefix, $isActive,
                $concernId
            ]);

            $db->commit();
            Session::flash('success', "Concern '{$name}' ({$code}) updated successfully.");
            Response::redirect(url('admin/concerns'));
        } catch (Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Error updating concern: ' . $e->getMessage());
            Response::redirect(url("admin/concerns/edit/{$concernId}"));
        }
    }

    /**
     * Quick update of staff assignments for a concern.
     */
    public function assignStaff(string $id): void {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect(url('admin/concerns'));
            return;
        }

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        $rawStaffIds = $_POST['staff_ids'] ?? [];
        if (!is_array($rawStaffIds)) {
            Session::flash('error', 'Invalid staff assignment list.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        $staffIds = [];
        foreach ($rawStaffIds as $rawStaffId) {
            $staffId = filter_var($rawStaffId, FILTER_VALIDATE_INT);
            if ($staffId === false || $staffId < 1) {
                Session::flash('error', 'Invalid staff assignment list.');
                Response::redirect(url('admin/concerns'));
                return;
            }
            $staffIds[] = $staffId;
        }
        $staffIds = array_values(array_unique($staffIds));
        if (!empty($staffIds)) {
            $inClause = implode(',', array_fill(0, count($staffIds), '?'));
            $assignableStaff = Database::fetchAll("
                SELECT u.id
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                WHERE u.id IN ($inClause)
                  AND u.is_active = 1
                  AND (u.role_id IS NULL OR u.role_id != 1)
                  AND (r.slug IS NULL OR r.slug != 'super_admin')
            ", $staffIds) ?: [];
            $assignableIds = array_map('intval', array_column($assignableStaff, 'id'));
            if (count($assignableIds) !== count($staffIds)) {
                Session::flash('error', 'Only active non-Global-Admin staff can be assigned to a concern.');
                Response::redirect(url('admin/concerns'));
                return;
            }
            $staffIds = $assignableIds;
        }
        $requestedPrimaryStaffId = null;
        if (!empty($_POST['primary_staff_id'])) {
            $requestedPrimaryStaffId = filter_var($_POST['primary_staff_id'], FILTER_VALIDATE_INT);
            if (
                $requestedPrimaryStaffId === false
                || $requestedPrimaryStaffId < 1
                || !in_array($requestedPrimaryStaffId, $staffIds, true)
            ) {
                Session::flash('error', 'Choose a primary staff member from the assigned staff list.');
                Response::redirect(url('admin/concerns'));
                return;
            }
        }
        $primaryStaffId = $requestedPrimaryStaffId ?? ($staffIds[0] ?? null);

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            if (!empty($staffIds)) {
                $inClause = implode(',', array_fill(0, count($staffIds), '?'));
                $db->prepare("DELETE FROM user_concerns WHERE concern_id = ? AND user_id NOT IN ($inClause)")
                   ->execute(array_merge([$concernId], $staffIds));
            } else {
                $db->prepare("DELETE FROM user_concerns WHERE concern_id = ?")->execute([$concernId]);
            }

            $ucStmt = $db->prepare("
                INSERT INTO user_concerns (user_id, concern_id, is_default, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_default = VALUES(is_default)
            ");

            foreach ($staffIds as $sId) {
                $isDef = ($sId === $primaryStaffId) ? 1 : 0;
                $ucStmt->execute([$sId, $concernId, $isDef]);
                if ($isDef) {
                    $db->prepare("UPDATE users SET default_concern_id = ? WHERE id = ?")
                       ->execute([$concernId, $sId]);
                }
            }

            $db->commit();
            Session::flash('success', "Staff assignments for concern '{$concern['name']}' updated successfully (" . count($staffIds) . " staff assigned).");
        } catch (Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Error updating staff assignments: ' . $e->getMessage());
        }

        Response::redirect(url('admin/concerns'));
    }

    /**
     * AJAX endpoint to quickly create a staff member from inside Concern Create/Edit.
     */
    public function quickCreateStaff(): void {
        $this->authorizeAdmin();

        $fullName = trim($_POST['full_name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');

        if (empty($fullName) || empty($username) || empty($roleId) || empty($password)) {
            Response::json(['success' => false, 'message' => 'Full Name, Username, Role, and Password are required.'], 422);
            return;
        }

        // Validate username uniqueness
        $exists = Database::fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) {
            Response::json(['success' => false, 'message' => "Username '@{$username}' is already in use."], 422);
            return;
        }

        // Validate phone uniqueness if provided
        if (!empty($phone)) {
            $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanedPhone) !== 10) {
                Response::json(['success' => false, 'message' => 'Mobile number must be exactly 10 digits.'], 422);
                return;
            }
            $phoneExists = Database::fetchOne("SELECT id FROM users WHERE phone = ?", [$cleanedPhone]);
            if ($phoneExists) {
                Response::json(['success' => false, 'message' => "Phone number '{$cleanedPhone}' is already registered."], 422);
                return;
            }
            $phone = $cleanedPhone;
        }

        try {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Staff';
            $department = $role['primary_department'] ?? 'GENERAL';

            $newUserId = User::create([
                'full_name'     => $fullName,
                'username'      => $username,
                'email'         => $email ?: null,
                'phone'         => $phone ?: null,
                'role_id'       => $roleId,
                'department'    => $department,
                'password'      => $password,
                'is_active'     => 1,
            ]);

            Response::json([
                'success' => true,
                'message' => "Staff member '{$fullName}' created successfully and ready for assignment.",
                'staff'   => [
                    'id'         => $newUserId,
                    'full_name'  => $fullName,
                    'username'   => $username,
                    'email'      => $email,
                    'phone'      => $phone,
                    'role_name'  => $roleName,
                    'department' => $department,
                ]
            ]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => 'Failed to create staff: ' . $e->getMessage()], 500);
        }
    }

    /**
     * View and configure module entitlement matrix.
     */
    public function modules(string $id): void {
        $this->authorizeAdmin();

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        // Fetch current states
        $currentStates = [];
        $rows = Database::fetchAll("SELECT module_key, is_enabled FROM concern_modules WHERE concern_id = ?", [$concernId]);
        foreach ($rows as $r) {
            $currentStates[$r['module_key']] = (bool)$r['is_enabled'];
        }

        $standardModules = self::$STANDARD_MODULES;

        Response::view('admin.concerns.modules', [
            'pageTitle'       => 'Module Entitlement Matrix — ' . $concern['name'],
            'activeModule'    => 'concerns',
            'activeMenu'      => 'concerns-list',
            'concern'         => $concern,
            'currentStates'   => $currentStates,
            'standardModules' => $standardModules,
            'breadcrumb'      => [
                ['label' => 'Dashboard', 'url' => url('admin/dashboard')],
                ['label' => 'Concerns', 'url' => url('admin/concerns')],
                ['label' => 'Module Matrix', 'url' => null]
            ]
        ]);
    }

    /**
     * Save enabled module matrix into concern_modules.
     */
    public function updateModules(string $id): void {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect(url('admin/concerns'));
            return;
        }

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        $enabledModules = $_POST['modules'] ?? [];

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO concern_modules (concern_id, module_key, is_enabled)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)
        ");

        foreach (array_keys(self::$STANDARD_MODULES) as $mKey) {
            $isEnabled = in_array($mKey, $enabledModules, true) ? 1 : 0;
            $stmt->execute([$concernId, $mKey, $isEnabled]);
        }

        // Auto-link/create Production Unit if production module enabled
        if (in_array('production', $enabledModules, true)) {
            $existingPu = Database::fetchOne("SELECT id FROM production_units WHERE concern_id = ?", [$concernId]);
            if (!$existingPu) {
                $puCode = 'PU-' . $concern['code'];
                $suffix = 1;
                while (Database::fetchOne("SELECT id FROM production_units WHERE code = ?", [$puCode])) {
                    $puCode = 'PU-' . $concern['code'] . '-' . (++$suffix);
                }
                $db->prepare("
                    INSERT INTO production_units (concern_id, name, code, address, phone, gst_number, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ")->execute([$concernId, $concern['name'] . ' (Plant)', $puCode, $concern['address'] ?: null, $concern['phone'] ?: null, $concern['gst_number'] ?: null]);
            }
        }

        // Auto-link/create Warehouse if warehouse module enabled
        if (in_array('warehouse', $enabledModules, true)) {
            $existingWh = Database::fetchOne("SELECT id FROM warehouses WHERE concern_id = ?", [$concernId]);
            if (!$existingWh) {
                $whCode = 'WH-' . $concern['code'];
                $suffix = 1;
                while (Database::fetchOne("SELECT id FROM warehouses WHERE code = ?", [$whCode])) {
                    $whCode = 'WH-' . $concern['code'] . '-' . (++$suffix);
                }
                $db->prepare("
                    INSERT INTO warehouses (concern_id, name, code, address, phone, gst_number, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ")->execute([$concernId, $concern['name'] . ' (Warehouse)', $whCode, $concern['address'] ?: null, $concern['phone'] ?: null, $concern['gst_number'] ?: null]);
            }
        }

        Session::flash('success', "Module entitlements for '{$concern['name']}' updated successfully.");
        Response::redirect(url('admin/concerns'));
    }

    /**
     * Switch active concern in session.
     */
    public function switch(string $id): void {
        $concernId = (int)$id;
        $success = switchConcern($concernId);

        if ($success) {
            if ($concernId === 0) {
                Session::flash('success', "Switched to Global Admin (All Concerns) view.");
            } else {
                $c = currentConcern();
                $cName = trim((string)($c['name'] ?? 'Concern #' . $concernId));
                $cCode = trim((string)($c['code'] ?? ''));
                Session::flash('success', "Switched active business concern to {$cName}" . ($cCode !== '' ? " ({$cCode})" : '') . ".");
            }
        } else {
            Session::flash('error', 'Unable to switch to the selected concern. Access denied or entity inactive.');
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? url('admin/dashboard');
        // Prevent redirect loop if referer was switch URL itself
        if (str_contains($redirectUrl, 'switch')) {
            $redirectUrl = url('admin/dashboard');
        }
        Response::redirect($redirectUrl);
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(string $id): void {
        $this->authorizeAdmin();

        $concernId = (int)$id;
        $concern = Database::fetchOne("SELECT * FROM concerns WHERE id = ?", [$concernId]);
        if (!$concern) {
            Session::flash('error', 'Concern not found.');
            Response::redirect(url('admin/concerns'));
            return;
        }

        $newStatus = $concern['is_active'] ? 0 : 1;

        // Prevent deactivating if it's the only active concern
        if ($newStatus === 0) {
            $activeCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM concerns WHERE is_active = 1");
            if ($activeCount <= 1) {
                Session::flash('error', 'Cannot deactivate the only active business concern.');
                Response::redirect(url('admin/concerns'));
                return;
            }
        }

        Database::getInstance()->prepare("UPDATE concerns SET is_active = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $concernId]);

        $msg = $newStatus ? "Concern '{$concern['name']}' activated." : "Concern '{$concern['name']}' deactivated.";
        Session::flash('success', $msg);
        Response::redirect(url('admin/concerns'));
    }

    /**
     * Validate contact and operating address fields for concern create/update.
     */
    private function validateConcernContactFields(
        string $phone,
        string $email,
        string $address,
        string $city,
        string $state,
        string $pincode
    ): ?string {
        $compactPhone = preg_replace('/[\s\-]/', '', $phone);
        $digitsOnly = preg_replace('/\D/', '', $compactPhone);
        if ($phone === '' || !preg_match('/^\+?[0-9]+$/', $compactPhone) || strlen($digitsOnly) < 10 || strlen($digitsOnly) > 15) {
            return 'Official Phone / Mobile must be a valid 10 to 15 digit number.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid Official Email address.';
        }

        $addrLen = function_exists('mb_strlen') ? mb_strlen($address) : strlen($address);
        if ($addrLen < 10 || $addrLen > 500) {
            return 'Registered Physical Address is required (10 to 500 characters).';
        }

        if ($city === '' || !preg_match('/^[a-zA-Z\s.\-]{2,100}$/', $city)) {
            return 'City is required and must contain only valid letters (minimum 2 characters).';
        }

        if ($state === '' || !preg_match('/^[a-zA-Z\s.\-]{2,100}$/', $state)) {
            return 'State is required and must contain only valid letters (minimum 2 characters).';
        }

        if ($pincode !== '' && !preg_match('/^[0-9]{6}$/', $pincode)) {
            return 'Pincode must be a valid 6-digit number.';
        }

        return null;
    }

    /**
     * Handle logo file upload for concern branding.
     */
    private function handleLogoUpload(?string $existingLogo = null): ?string {
        if (!empty($_POST['remove_logo'])) {
            if ($existingLogo) {
                $clean = ltrim(str_replace('public/', '', $existingLogo), '/');
                $target = BASE_PATH . '/public/' . $clean;
                if (file_exists($target) && is_file($target)) {
                    @unlink($target);
                }
            }
            return null;
        }

        if (!isset($_FILES['logo']) || empty($_FILES['logo']['name']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
            return $existingLogo;
        }

        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Image upload error (Code: ' . $file['error'] . ')');
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception('Invalid logo file format. Allowed types: PNG, JPG, JPEG, WEBP, SVG.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Logo image file exceeds 5MB size limit.');
        }

        $uploadDir = BASE_PATH . '/public/uploads/concerns';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'concern_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to save uploaded concern logo.');
        }

        // Clean up previous logo file if replacing
        if ($existingLogo) {
            $cleanOld = ltrim(str_replace('public/', '', $existingLogo), '/');
            $oldPath = BASE_PATH . '/public/' . $cleanOld;
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return 'uploads/concerns/' . $newFileName;
    }
}
