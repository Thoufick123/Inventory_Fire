<?php
/**
 * Production HSN Config Controller
 * Full CRUD for HSN codes under Production module.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Request.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class AllmodulesHsnController {

    private static bool $schemaEnsured = false;

    private static function ensureSchema(): void {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;
        try {
            $tableCheck = Database::fetchOne("SHOW TABLES LIKE 'production_hsn_config'");
            if (!$tableCheck) {
                Database::execute("CREATE TABLE production_hsn_config (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    hsn_code VARCHAR(8) NOT NULL,
                    gst_percentage DECIMAL(5,2) DEFAULT 0.00,
                    categories VARCHAR(255) DEFAULT NULL,
                    subcategories VARCHAR(255) DEFAULT NULL,
                    sub_subcategories VARCHAR(255) DEFAULT NULL,
                    product VARCHAR(255) DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_by INT UNSIGNED DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_hsn_code (hsn_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (\Throwable $e) {
            error_log('ProductionHsnController schema error: ' . $e->getMessage());
        }
    }

    /**
     * List all HSN config records.
     */
    public function index(): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'view') && !hasPermission('hsn_config', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view HSN Config.');
            return;
        }

        $items = Database::fetchAll(
            "SELECT * FROM production_hsn_config ORDER BY created_at DESC"
        );
        Response::view('allmodules.hsn_config.index', [
            'items'        => $items,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): void {
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create HSN Config.');
            return;
        }

        Response::view('allmodules.hsn_config.create', [
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Store a new HSN config record.
     */
    public function store(): void {
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $hsnCode         = trim($_POST['hsn_code'] ?? '');
        $gstPercentage   = $_POST['gst_percentage'] ?? null;
        $categories      = trim($_POST['categories'] ?? '') ?: null;
        $subcategories   = trim($_POST['subcategories'] ?? '') ?: null;
        $subSubcategories = trim($_POST['sub_subcategories'] ?? '') ?: null;
        $product         = trim($_POST['product'] ?? '') ?: null;
        $isActive        = isset($_POST['is_active']) ? 1 : 0;

        // Validate HSN code: required, numbers only, up to 8 digits (e.g. 3, 5, 6, 8 digits)
        if ($hsnCode === '' || !preg_match('/^\d{1,8}$/', $hsnCode)) {
            Session::flash('error', 'HSN Code must be numbers only and up to 8 digits.');
            Response::back();
            return;
        }

        // Check for duplicate
        $existing = Database::fetchOne(
            "SELECT id FROM production_hsn_config WHERE hsn_code = ?",
            [$hsnCode]
        );
        if ($existing) {
            Session::flash('error', 'HSN Code "' . htmlspecialchars($hsnCode) . '" already exists.');
            Response::back();
            return;
        }

        $gst = ($gstPercentage !== null && $gstPercentage !== '') ? (float)$gstPercentage : 0.00;

        Database::execute(
            "INSERT INTO production_hsn_config
                (hsn_code, gst_percentage, categories, subcategories, sub_subcategories, product, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$hsnCode, $gst, $categories, $subcategories, $subSubcategories, $product, $isActive]
        );

        // Sync to system-wide hsn_codes table so every dropdown dynamically has this HSN code
        $descParts = array_filter([$product, $subSubcategories, $subcategories, $categories]);
        $description = !empty($descParts) ? implode(' - ', $descParts) : ('Production HSN ' . $hsnCode);

        try {
            $existingHsn = Database::fetchOne("SELECT id FROM hsn_codes WHERE hsn_code = ?", [$hsnCode]);
            if ($existingHsn) {
                Database::execute(
                    "UPDATE hsn_codes SET description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
                    [$description, $gst, (int)$existingHsn['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$hsnCode, $description, $gst]
                );
            }
        } catch (\Throwable $e) {
            error_log('HSN sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($hsnCode) . '" added successfully.');
        Response::redirect(url('production/hsn-config'));
    }

    /**
     * Show a single HSN config record (read-only).
     */
    public function view(string $id): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'view') && !hasPermission('hsn_config', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view HSN Config.');
            return;
        }

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }
        Response::view('allmodules.hsn_config.view', [
            'record'       => $record,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(string $id): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'edit') && !hasPermission('hsn_config', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit HSN Config.');
            return;
        }

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }
        Response::view('allmodules.hsn_config.edit', [
            'record'       => $record,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Update an existing HSN config record.
     */
    public function update(string $id): void {
        if (!hasPermission('production_hsn', 'edit') && !hasPermission('hsn_config', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }

        $hsnCode          = trim($_POST['hsn_code'] ?? '');
        $gstPercentage    = $_POST['gst_percentage'] ?? null;
        $categories       = trim($_POST['categories'] ?? '') ?: null;
        $subcategories    = trim($_POST['subcategories'] ?? '') ?: null;
        $subSubcategories = trim($_POST['sub_subcategories'] ?? '') ?: null;
        $product          = trim($_POST['product'] ?? '') ?: null;
        $isActive         = isset($_POST['is_active']) ? 1 : 0;

        // Validate HSN code: required, numbers only, up to 8 digits (e.g. 3, 5, 6, 8 digits)
        if ($hsnCode === '' || !preg_match('/^\d{1,8}$/', $hsnCode)) {
            Session::flash('error', 'HSN Code must be numbers only and up to 8 digits.');
            Response::back();
            return;
        }

        // Check for duplicate (excluding current record)
        $duplicate = Database::fetchOne(
            "SELECT id FROM production_hsn_config WHERE hsn_code = ? AND id != ?",
            [$hsnCode, (int)$id]
        );
        if ($duplicate) {
            Session::flash('error', 'HSN Code "' . htmlspecialchars($hsnCode) . '" already exists.');
            Response::back();
            return;
        }

        $gst = ($gstPercentage !== null && $gstPercentage !== '') ? (float)$gstPercentage : 0.00;

        Database::execute(
            "UPDATE production_hsn_config
             SET hsn_code = ?, gst_percentage = ?, categories = ?, subcategories = ?,
                 sub_subcategories = ?, product = ?, is_active = ?
             WHERE id = ?",
            [$hsnCode, $gst, $categories, $subcategories, $subSubcategories, $product, $isActive, (int)$id]
        );

        // Sync update to system-wide hsn_codes table
        $descParts = array_filter([$product, $subSubcategories, $subcategories, $categories]);
        $description = !empty($descParts) ? implode(' - ', $descParts) : ('Production HSN ' . $hsnCode);

        try {
            $existingHsn = Database::fetchOne("SELECT id FROM hsn_codes WHERE hsn_code = ? OR hsn_code = ?", [$hsnCode, $record['hsn_code']]);
            if ($existingHsn) {
                Database::execute(
                    "UPDATE hsn_codes SET hsn_code = ?, description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
                    [$hsnCode, $description, $gst, (int)$existingHsn['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$hsnCode, $description, $gst]
                );
            }
        } catch (\Throwable $e) {
            error_log('HSN sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($hsnCode) . '" updated successfully.');
        Response::redirect(url('production/hsn-config'));
    }

    /**
     * Delete a HSN config record.
     */
    public function delete(string $id): void {
        if (!hasPermission('production_hsn', 'delete') && !hasPermission('hsn_config', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }

        Database::execute(
            "DELETE FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );

        // Also clean up from hsn_codes if exists
        try {
            Database::execute("DELETE FROM hsn_codes WHERE hsn_code = ?", [$record['hsn_code']]);
        } catch (\Throwable $e) {
            error_log('HSN delete sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($record['hsn_code']) . '" deleted successfully.');
        Response::redirect(url('production/hsn-config'));
    }
}

if (!class_exists('ProductionHsnController', false)) {
    class_alias('AllmodulesHsnController', 'ProductionHsnController');
}
