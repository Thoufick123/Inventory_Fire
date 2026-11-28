<?php
/**
 * Asset Controller
 * Asset Register, Machinery & Equipment Tracking, Maintenance & Servicing
 */

require_once BASE_PATH . '/app/Models/Asset.php';
require_once BASE_PATH . '/app/Models/AssetCategory.php';
require_once BASE_PATH . '/app/Models/AssetMaintenance.php';

class AssetController {

    public function index(): void {
        if (!hasPermission('asset_register', 'view') && !hasPermission('assets', 'view')) {
            Response::error(403, 'Access denied. You do not have permission to view the Asset Register.');
            return;
        }

        $filters = [
            'search'          => trim($_GET['search'] ?? ''),
            'category_id'     => trim($_GET['category_id'] ?? ''),
            'status'          => trim($_GET['status'] ?? ''),
            'condition_state' => trim($_GET['condition_state'] ?? ''),
        ];

        $assets = Asset::getAll($filters);
        $metrics = Asset::getMetrics();
        $categories = AssetCategory::getAll();

        Response::view('assets.index', [
            'pageTitle'    => 'Asset Register & Plant Machinery',
            'activeModule' => 'assets',
            'activeMenu'   => 'assets-list',
            'breadcrumb'   => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Asset Register'],
            ],
            'assets'       => $assets,
            'metrics'      => $metrics,
            'categories'   => $categories,
            'filters'      => $filters,
        ]);
    }

    public function create(): void {
        if (!hasPermission('asset_register', 'create') && !hasPermission('assets', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $categories = AssetCategory::getAll();
        $nextCode = Asset::generateAssetCode();
        $locationConcerns = function_exists('allActiveConcerns') ? allActiveConcerns() : (Database::fetchAll("SELECT id, name, code FROM concerns WHERE is_active = 1 ORDER BY name ASC") ?: []);

        Response::view('assets.create', [
            'pageTitle'        => 'Register New Asset',
            'activeModule'     => 'assets',
            'activeMenu'       => 'assets-create',
            'breadcrumb'       => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Register Asset'],
            ],
            'categories'       => $categories,
            'nextCode'         => $nextCode,
            'locationConcerns' => $locationConcerns,
        ]);
    }

    public function ajaxStoreCategory(): void {
        if (!hasPermission('asset_register', 'create') && !hasPermission('assets', 'create')) {
            Response::json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::json(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.'], 403);
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            Response::json(['success' => false, 'message' => 'Please enter a valid category name (max 150 characters).'], 422);
            return;
        }
        try {
            $category = AssetCategory::createQuick($name);
            Response::json([
                'success'  => true,
                'message'  => 'Category saved successfully.',
                'category' => [
                    'id'   => (int)$category['id'],
                    'name' => (string)$category['name'],
                    'code' => (string)($category['code'] ?? ''),
                ],
            ]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Could not save category: ' . $e->getMessage()], 500);
        }
    }

    private function ensureAssetLocationTypeVarchar(): void {
        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM assets LIKE 'location_type'");
            if ($col && stripos((string)($col['Type'] ?? ''), 'enum') !== false) {
                Database::execute("ALTER TABLE assets MODIFY COLUMN location_type VARCHAR(150) NOT NULL DEFAULT 'PRODUCTION'");
            }
        } catch (\Throwable $e) {}
    }

    public function store(): void {
        if (!hasPermission('asset_register', 'create') && !hasPermission('assets', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }
        $this->ensureAssetLocationTypeVarchar();

        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);

        if ($name === '' || $categoryId <= 0) {
            Response::redirectWithError('assets/create', 'Asset Name and Category are required.');
            return;
        }

        $assetCode = trim($_POST['asset_code'] ?? '') ?: Asset::generateAssetCode();
        $barcodeQr = trim($_POST['barcode_qr'] ?? '') ?: ($assetCode . '-QR');

        $data = [
            'concern_id'         => resolveSubmittedConcernId(),
            'asset_code'         => $assetCode,
            'name'               => $name,
            'category_id'        => $categoryId,
            'location_type'      => $_POST['location_type'] ?? 'PRODUCTION',
            'location_id'        => (int)($_POST['location_id'] ?? 1),
            'floor_area'         => trim($_POST['floor_area'] ?? ''),
            'brand'              => trim($_POST['brand'] ?? ''),
            'model_number'       => trim($_POST['model_number'] ?? ''),
            'serial_number'      => trim($_POST['serial_number'] ?? ''),
            'barcode_qr'         => $barcodeQr,
            'specifications'     => trim($_POST['specifications'] ?? ''),
            'purchase_date'      => trim($_POST['purchase_date'] ?? ''),
            'purchase_cost'      => (float)($_POST['purchase_cost'] ?? 0),
            'vendor_name'        => trim($_POST['vendor_name'] ?? ''),
            'vendor_contact'     => trim($_POST['vendor_contact'] ?? ''),
            'invoice_number'     => trim($_POST['invoice_number'] ?? ''),
            'warranty_expiry'    => trim($_POST['warranty_expiry'] ?? ''),
            'useful_life_years'  => (int)($_POST['useful_life_years'] ?? 5),
            'salvage_value'      => (float)($_POST['salvage_value'] ?? 0),
            'current_book_value' => (float)($_POST['current_book_value'] ?? ($_POST['purchase_cost'] ?? 0)),
            'status'             => $_POST['status'] ?? 'IN_USE',
            'condition_state'    => $_POST['condition_state'] ?? 'GOOD',
            'custodian_name'     => trim($_POST['custodian_name'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
            'created_by'         => Auth::id() ?? 1,
        ];

        try {
            $id = Asset::create($data);
            Response::redirectWithSuccess('assets', "Asset '{$name}' ({$assetCode}) registered successfully!");
        } catch (Exception $e) {
            Response::redirectWithError('assets/create', 'Failed to register asset: ' . $e->getMessage());
        }
    }

    public function view(string $id): void {
        if (!hasPermission('asset_register', 'view') && !hasPermission('assets', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $asset = Asset::find((int)$id);
        if (!$asset) {
            Response::error(404, 'Asset not found.');
            return;
        }

        $maintenanceLogs = Asset::getMaintenanceHistory((int)$id);

        Response::view('assets.view', [
            'pageTitle'       => 'Asset: ' . $asset['name'] . ' (' . $asset['asset_code'] . ')',
            'activeModule'    => 'assets',
            'activeMenu'      => 'assets-list',
            'breadcrumb'      => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => $asset['asset_code']],
            ],
            'asset'           => $asset,
            'maintenanceLogs' => $maintenanceLogs,
        ]);
    }

    public function edit(string $id): void {
        if (!hasPermission('asset_register', 'edit') && !hasPermission('assets', 'edit')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $asset = Asset::find((int)$id);
        if (!$asset) {
            Response::error(404, 'Asset not found.');
            return;
        }

        $categories = AssetCategory::getAll();
        $locationConcerns = function_exists('allActiveConcerns') ? allActiveConcerns() : (Database::fetchAll("SELECT id, name, code FROM concerns WHERE is_active = 1 ORDER BY name ASC") ?: []);

        Response::view('assets.edit', [
            'pageTitle'        => 'Edit Asset: ' . $asset['asset_code'],
            'activeModule'     => 'assets',
            'activeMenu'       => 'assets-list',
            'breadcrumb'       => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => $asset['asset_code'], 'url' => url('assets/view/' . $asset['id'])],
                ['label' => 'Edit'],
            ],
            'asset'            => $asset,
            'categories'       => $categories,
            'locationConcerns' => $locationConcerns,
        ]);
    }

    public function update(string $id): void {
        if (!hasPermission('asset_register', 'edit') && !hasPermission('assets', 'edit')) {
            Response::error(403, 'Access denied.');
            return;
        }
        $this->ensureAssetLocationTypeVarchar();

        $assetId = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);

        if ($name === '' || $categoryId <= 0) {
            Response::redirectWithError('assets/edit/' . $assetId, 'Asset Name and Category are required.');
            return;
        }

        $data = [
            'name'               => $name,
            'category_id'        => $categoryId,
            'location_type'      => $_POST['location_type'] ?? 'PRODUCTION',
            'location_id'        => (int)($_POST['location_id'] ?? 1),
            'floor_area'         => trim($_POST['floor_area'] ?? ''),
            'brand'              => trim($_POST['brand'] ?? ''),
            'model_number'       => trim($_POST['model_number'] ?? ''),
            'serial_number'      => trim($_POST['serial_number'] ?? ''),
            'specifications'     => trim($_POST['specifications'] ?? ''),
            'purchase_date'      => trim($_POST['purchase_date'] ?? ''),
            'purchase_cost'      => (float)($_POST['purchase_cost'] ?? 0),
            'vendor_name'        => trim($_POST['vendor_name'] ?? ''),
            'vendor_contact'     => trim($_POST['vendor_contact'] ?? ''),
            'invoice_number'     => trim($_POST['invoice_number'] ?? ''),
            'warranty_expiry'    => trim($_POST['warranty_expiry'] ?? ''),
            'useful_life_years'  => (int)($_POST['useful_life_years'] ?? 5),
            'salvage_value'      => (float)($_POST['salvage_value'] ?? 0),
            'current_book_value' => (float)($_POST['current_book_value'] ?? 0),
            'status'             => $_POST['status'] ?? 'IN_USE',
            'condition_state'    => $_POST['condition_state'] ?? 'GOOD',
            'custodian_name'     => trim($_POST['custodian_name'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
        ];

        try {
            Asset::update($assetId, $data);
            Response::redirectWithSuccess('assets', 'Asset details updated successfully!');
        } catch (Exception $e) {
            Response::redirectWithError('assets/edit/' . $assetId, 'Failed to update asset: ' . $e->getMessage());
        }
    }

    public function delete(string $id): void {
        if (!hasPermission('asset_register', 'delete') && !hasPermission('assets', 'delete') && !isSuperAdmin()) {
            Response::error(403, 'Access denied.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::redirectWithError('assets', 'Invalid security token. Please try again.');
            return;
        }
        $assetId = (int)$id;
        $asset = Asset::find($assetId);
        if (!$asset) {
            Response::redirectWithError('assets', 'Asset not found.');
            return;
        }
        try {
            Database::beginTransaction();
            try {
                Database::execute("DELETE FROM asset_maintenance_logs WHERE asset_id = ?", [$assetId]);
            } catch (\Throwable $e) {}
            Database::execute("DELETE FROM assets WHERE id = ?", [$assetId]);
            Database::commit();
            try {
                AuditService::log('assets', 'DELETE', 'assets', $assetId, $asset, null);
            } catch (\Throwable $e) {}
            Response::redirectWithSuccess('assets', "Asset '{$asset['asset_code']}' deleted successfully.");
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Response::redirectWithError('assets', 'Could not delete asset: ' . $e->getMessage());
        }
    }

    public function printLabel(string $id): void {
        if (!hasPermission('asset_register', 'view') && !hasPermission('assets', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $asset = Asset::find((int)$id);
        if (!$asset) {
            Response::error(404, 'Asset not found.');
            return;
        }

        Response::viewOnly('assets.print_label', [
            'asset' => $asset,
        ]);
    }

    public function maintenanceList(): void {
        if (!hasPermission('asset_maintenance', 'view') && !hasPermission('assets', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'status'       => trim($_GET['status'] ?? ''),
            'service_type' => trim($_GET['service_type'] ?? ''),
            'asset_id'     => trim($_GET['asset_id'] ?? ''),
        ];

        $logs = AssetMaintenance::getAll($filters);

        Response::view('assets.maintenance_list', [
            'pageTitle'    => 'Asset Maintenance & Repair Logs',
            'activeModule' => 'assets',
            'activeMenu'   => 'assets-maintenance',
            'breadcrumb'   => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Maintenance Logs'],
            ],
            'logs'         => $logs,
            'filters'      => $filters,
        ]);
    }

    public function maintenanceCreate(): void {
        if (!hasPermission('asset_maintenance', 'create') && !hasPermission('assets', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $assets = Asset::getAll();
        $nextLogNumber = AssetMaintenance::generateLogNumber();
        $selectedAssetId = (int)($_GET['asset_id'] ?? 0);

        Response::view('assets.maintenance_create', [
            'pageTitle'       => 'Log Asset Maintenance Ticket',
            'activeModule'    => 'assets',
            'activeMenu'      => 'assets-maintenance-create',
            'breadcrumb'      => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Maintenance', 'url' => url('assets/maintenance')],
                ['label' => 'Log Ticket'],
            ],
            'assets'          => $assets,
            'nextLogNumber'   => $nextLogNumber,
            'selectedAssetId' => $selectedAssetId,
        ]);
    }

    public function maintenanceStore(): void {
        if (!hasPermission('asset_maintenance', 'create') && !hasPermission('assets', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $assetId = (int)($_POST['asset_id'] ?? 0);
        if ($assetId <= 0) {
            Response::redirectWithError('assets/maintenance/create', 'Please select an asset.');
            return;
        }

        $logNumber = trim($_POST['log_number'] ?? '') ?: AssetMaintenance::generateLogNumber();

        $data = [
            'log_number'         => $logNumber,
            'asset_id'           => $assetId,
            'service_type'       => $_POST['service_type'] ?? 'ROUTINE_PREVENTIVE',
            'issue_description'  => trim($_POST['issue_description'] ?? ''),
            'action_taken'       => trim($_POST['action_taken'] ?? ''),
            'serviced_by_vendor' => trim($_POST['serviced_by_vendor'] ?? ''),
            'technician_name'    => trim($_POST['technician_name'] ?? ''),
            'technician_contact' => trim($_POST['technician_contact'] ?? ''),
            'service_cost'       => (float)($_POST['service_cost'] ?? 0),
            'parts_replaced'     => trim($_POST['parts_replaced'] ?? ''),
            'service_date'       => trim($_POST['service_date'] ?? date('Y-m-d')),
            'next_due_date'      => trim($_POST['next_due_date'] ?? ''),
            'downtime_hours'     => (float)($_POST['downtime_hours'] ?? 0),
            'status'             => $_POST['status'] ?? 'COMPLETED',
            'created_by'         => Auth::id() ?? 1,
        ];

        try {
            $logId = AssetMaintenance::create($data);
            Response::redirectWithSuccess('assets/maintenance', "Maintenance log {$logNumber} recorded successfully!");
        } catch (Exception $e) {
            Response::redirectWithError('assets/maintenance/create', 'Failed to save maintenance ticket: ' . $e->getMessage());
        }
    }

    public function maintenanceView(string $id): void {
        if (!hasPermission('asset_maintenance', 'view') && !hasPermission('assets', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $log = AssetMaintenance::find((int)$id);
        if (!$log) {
            Response::error(404, 'Maintenance log not found.');
            return;
        }

        Response::view('assets.maintenance_view', [
            'pageTitle'    => 'Maintenance Ticket: ' . $log['log_number'],
            'activeModule' => 'assets',
            'activeMenu'   => 'assets-maintenance',
            'breadcrumb'   => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Maintenance', 'url' => url('assets/maintenance')],
                ['label' => $log['log_number']],
            ],
            'log'          => $log,
        ]);
    }

    public function maintenanceEdit(string $id): void {
        if (!hasPermission('asset_maintenance', 'edit') && !hasPermission('assets', 'edit') && !isSuperAdmin()) {
            Response::error(403, 'Access denied.');
            return;
        }
        $log = AssetMaintenance::find((int)$id);
        if (!$log) {
            Response::error(404, 'Maintenance log not found.');
            return;
        }
        $assets = Asset::getAll();
        Response::view('assets.maintenance_edit', [
            'pageTitle'    => 'Edit Maintenance Ticket: ' . $log['log_number'],
            'activeModule' => 'assets',
            'activeMenu'   => 'assets-maintenance',
            'breadcrumb'   => [
                ['label' => 'Asset Management', 'url' => url('assets')],
                ['label' => 'Maintenance', 'url' => url('assets/maintenance')],
                ['label' => 'Edit ' . $log['log_number']],
            ],
            'log'          => $log,
            'assets'       => $assets,
        ]);
    }

    public function maintenanceUpdate(string $id): void {
        if (!hasPermission('asset_maintenance', 'edit') && !hasPermission('assets', 'edit') && !isSuperAdmin()) {
            Response::error(403, 'Access denied.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::redirectWithError('assets/maintenance', 'Invalid security token.');
            return;
        }
        $logId = (int)$id;
        $log = AssetMaintenance::find($logId);
        if (!$log) {
            Response::redirectWithError('assets/maintenance', 'Maintenance log not found.');
            return;
        }
        $assetId = (int)($_POST['asset_id'] ?? 0);
        if ($assetId <= 0) {
            Response::redirectWithError('assets/maintenance/edit/' . $logId, 'Please select an asset.');
            return;
        }
        try {
            $nextDue = trim((string)($_POST['next_due_date'] ?? ''));
            $serviceDate = trim((string)($_POST['service_date'] ?? $log['service_date']));
            Database::execute(
                "UPDATE asset_maintenance_logs
                 SET asset_id = ?, service_type = ?, issue_description = ?, action_taken = ?,
                     serviced_by_vendor = ?, technician_name = ?, technician_contact = ?,
                     service_cost = ?, parts_replaced = ?, service_date = ?, next_due_date = ?,
                     downtime_hours = ?, status = ?
                 WHERE id = ?",
                [
                    $assetId,
                    $_POST['service_type'] ?? 'ROUTINE_PREVENTIVE',
                    trim((string)($_POST['issue_description'] ?? '')),
                    trim((string)($_POST['action_taken'] ?? '')),
                    trim((string)($_POST['serviced_by_vendor'] ?? '')),
                    trim((string)($_POST['technician_name'] ?? '')),
                    trim((string)($_POST['technician_contact'] ?? '')),
                    (float)($_POST['service_cost'] ?? 0),
                    trim((string)($_POST['parts_replaced'] ?? '')),
                    $serviceDate,
                    $nextDue !== '' ? $nextDue : null,
                    (float)($_POST['downtime_hours'] ?? 0),
                    $_POST['status'] ?? 'COMPLETED',
                    $logId,
                ]
            );
            Response::redirectWithSuccess('assets/maintenance', "Maintenance log {$log['log_number']} updated successfully!");
        } catch (\Throwable $e) {
            Response::redirectWithError('assets/maintenance/edit/' . $logId, 'Failed to update maintenance ticket: ' . $e->getMessage());
        }
    }

    public function maintenanceDelete(string $id): void {
        if (!hasPermission('asset_maintenance', 'delete') && !hasPermission('assets', 'delete') && !isSuperAdmin()) {
            Response::error(403, 'Access denied.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::redirectWithError('assets/maintenance', 'Invalid security token.');
            return;
        }
        $logId = (int)$id;
        $log = AssetMaintenance::find($logId);
        if (!$log) {
            Response::redirectWithError('assets/maintenance', 'Maintenance log not found.');
            return;
        }
        try {
            Database::execute("DELETE FROM asset_maintenance_logs WHERE id = ?", [$logId]);
            try {
                AuditService::log('assets', 'DELETE', 'asset_maintenance_logs', $logId, $log, null);
            } catch (\Throwable $e) {}
            Response::redirectWithSuccess('assets/maintenance', "Maintenance log '{$log['log_number']}' deleted successfully.");
        } catch (\Throwable $e) {
            Response::redirectWithError('assets/maintenance', 'Could not delete maintenance log: ' . $e->getMessage());
        }
    }
}
