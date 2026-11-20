<?php
/**
 * HSN Controller — Full CRUD Operations for HSN Code Catalog.
 * Handles listing, server-side search, pagination, creation, editing, deletion, and API queries.
 */

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Request.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class HsnController {

    /**
     * Display paginated list of HSN codes with live search.
     */
    public function index(): void {
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50)));

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(hsn_code LIKE ? OR description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total count for current filter
        $countSql = "SELECT COUNT(*) as total FROM hsn_codes $whereClause";
        $totalRow = Database::fetchOne($countSql, $params);
        $totalItems = (int)($totalRow['total'] ?? 0);

        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // Fetch paginated rows
        $itemsSql = "SELECT id, hsn_code, description, gst_rate, created_at, updated_at 
                     FROM hsn_codes 
                     $whereClause 
                     ORDER BY hsn_code ASC 
                     LIMIT $perPage OFFSET $offset";
        $items = Database::fetchAll($itemsSql, $params);

        // Overall catalog statistics
        $stats = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_codes,
                COUNT(CASE WHEN gst_rate IS NOT NULL AND gst_rate > 0 THEN 1 END) as with_gst_count,
                COUNT(CASE WHEN gst_rate IS NULL OR gst_rate = 0 THEN 1 END) as default_exempt_count
             FROM hsn_codes"
        );

        Response::view('hsn.index', [
            'pageTitle' => 'HSN Code Catalog',
            'activeModule' => 'hsn',
            'activeMenu' => 'hsn-list',
            'items' => $items,
            'totalItems' => $totalItems,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'search' => $search,
            'stats' => $stats,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => url('')],
                ['label' => 'HSN Code Master', 'url' => url('hsn')],
                ['label' => 'Catalog'],
            ],
        ]);
    }

    /**
     * Show form to create a new HSN code.
     */
    public function create(): void {
        Response::view('hsn.create', [
            'pageTitle' => 'Add New HSN Code',
            'activeModule' => 'hsn',
            'activeMenu' => 'hsn-create',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => url('')],
                ['label' => 'HSN Code Master', 'url' => url('hsn')],
                ['label' => 'Add New'],
            ],
        ]);
    }

    /**
     * Store a newly created HSN code.
     */
    public function store(): void {
        if (!CSRF::validateRequest()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }

        $input = $_POST;
        if (empty($input['hsn_code'])) {
            $rawJson = file_get_contents('php://input');
            if ($rawJson) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    $input = array_merge($input, $decoded);
                }
            }
        }

        $hsnCode = strtoupper(trim((string)($input['hsn_code'] ?? '')));
        $description = trim((string)($input['description'] ?? ''));
        $gstRate = isset($input['gst_rate']) && $input['gst_rate'] !== '' ? (float)$input['gst_rate'] : null;

        if ($hsnCode === '') {
            $msg = 'HSN Code is required.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        if ($description === '') {
            $msg = 'Commodity / Description is required.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        // Check uniqueness
        $existing = Database::fetchOne("SELECT id FROM hsn_codes WHERE UPPER(TRIM(hsn_code)) = ?", [$hsnCode]);
        if ($existing) {
            $msg = "HSN Code '{$hsnCode}' already exists in the system.";
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        Database::execute(
            "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
            [$hsnCode, $description, $gstRate]
        );
        $newId = (int)Database::lastInsertId();

        AuditService::log('hsn', 'CREATE', 'hsn_codes', $newId, null, [
            'hsn_code' => $hsnCode,
            'description' => $description,
            'gst_rate' => $gstRate,
        ]);

        if (Request::isAjax()) {
            Response::json([
                'success' => true,
                'message' => "HSN Code '{$hsnCode}' created successfully.",
                'data' => [
                    'id' => $newId,
                    'hsn_code' => $hsnCode,
                    'description' => $description,
                    'gst_rate' => $gstRate,
                ]
            ]);
            return;
        }

        Session::flash('success', "HSN Code '{$hsnCode}' added successfully.");
        Response::redirect(url('hsn'));
    }

    /**
     * Show form to edit an existing HSN code.
     */
    public function edit(string $id): void {
        $idInt = (int)$id;
        $hsn = Database::fetchOne("SELECT * FROM hsn_codes WHERE id = ?", [$idInt]);
        if (!$hsn) {
            Session::flash('error', 'HSN Code record not found.');
            Response::redirect(url('hsn'));
            return;
        }

        Response::view('hsn.edit', [
            'pageTitle' => 'Edit HSN Code: ' . $hsn['hsn_code'],
            'activeModule' => 'hsn',
            'activeMenu' => 'hsn-list',
            'hsn' => $hsn,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => url('')],
                ['label' => 'HSN Code Master', 'url' => url('hsn')],
                ['label' => 'Edit ' . $hsn['hsn_code']],
            ],
        ]);
    }

    /**
     * Update an existing HSN code.
     */
    public function update(string $id): void {
        if (!CSRF::validateRequest()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }

        $idInt = (int)$id;
        $hsn = Database::fetchOne("SELECT * FROM hsn_codes WHERE id = ?", [$idInt]);
        if (!$hsn) {
            $msg = 'HSN Code record not found.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 404);
                return;
            }
            Session::flash('error', $msg);
            Response::redirect(url('hsn'));
            return;
        }

        $input = $_POST;
        if (empty($input['hsn_code'])) {
            $rawJson = file_get_contents('php://input');
            if ($rawJson) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    $input = array_merge($input, $decoded);
                }
            }
        }

        $hsnCode = strtoupper(trim((string)($input['hsn_code'] ?? '')));
        $description = trim((string)($input['description'] ?? ''));
        $gstRate = isset($input['gst_rate']) && $input['gst_rate'] !== '' ? (float)$input['gst_rate'] : null;

        if ($hsnCode === '') {
            $msg = 'HSN Code is required.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        if ($description === '') {
            $msg = 'Commodity / Description is required.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        // Check uniqueness excluding self
        $existing = Database::fetchOne(
            "SELECT id FROM hsn_codes WHERE UPPER(TRIM(hsn_code)) = ? AND id != ?",
            [$hsnCode, $idInt]
        );
        if ($existing) {
            $msg = "HSN Code '{$hsnCode}' is already used by another record.";
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::back();
            return;
        }

        Database::execute(
            "UPDATE hsn_codes SET hsn_code = ?, description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
            [$hsnCode, $description, $gstRate, $idInt]
        );

        AuditService::log('hsn', 'UPDATE', 'hsn_codes', $idInt, $hsn, [
            'hsn_code' => $hsnCode,
            'description' => $description,
            'gst_rate' => $gstRate,
        ]);

        if (Request::isAjax()) {
            Response::json([
                'success' => true,
                'message' => "HSN Code '{$hsnCode}' updated successfully.",
            ]);
            return;
        }

        Session::flash('success', "HSN Code '{$hsnCode}' updated successfully.");
        Response::redirect(url('hsn'));
    }

    /**
     * Delete an existing HSN code.
     */
    public function delete(string $id): void {
        if (!CSRF::validateRequest()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }

        $idInt = (int)$id;
        $hsn = Database::fetchOne("SELECT * FROM hsn_codes WHERE id = ?", [$idInt]);
        if (!$hsn) {
            $msg = 'HSN Code record not found.';
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 404);
                return;
            }
            Session::flash('error', $msg);
            Response::redirect(url('hsn'));
            return;
        }

        $code = $hsn['hsn_code'];

        // Check if referenced in purchases or products
        $usedInPurchases = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM production_purchase_items WHERE hsn_code = ?", [$code])['c'] ?? 0);
        $usedInProducts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE hsn_code = ?", [$code])['c'] ?? 0);
        $usedInVariants = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM product_variants WHERE hsn_code = ?", [$code])['c'] ?? 0);

        if ($usedInPurchases > 0 || $usedInProducts > 0 || $usedInVariants > 0) {
            $totalRefs = $usedInPurchases + $usedInProducts + $usedInVariants;
            $msg = "Cannot delete HSN Code '{$code}' because it is in active use across {$totalRefs} product(s) or purchase item(s).";
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => $msg], 422);
                return;
            }
            Session::flash('error', $msg);
            Response::redirect(url('hsn'));
            return;
        }

        Database::execute("DELETE FROM hsn_codes WHERE id = ?", [$idInt]);

        AuditService::log('hsn', 'DELETE', 'hsn_codes', $idInt, $hsn, null);

        if (Request::isAjax()) {
            Response::json([
                'success' => true,
                'message' => "HSN Code '{$code}' deleted successfully.",
            ]);
            return;
        }

        Session::flash('success', "HSN Code '{$code}' deleted successfully.");
        Response::redirect(url('hsn'));
    }

    /**
     * AJAX Search API endpoint for dynamic typeahead / dropdowns.
     */
    public function apiSearch(): void {
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            $items = Database::fetchAll("SELECT id, hsn_code, description, gst_rate FROM hsn_codes ORDER BY hsn_code ASC LIMIT 50");
        } else {
            $items = Database::fetchAll(
                "SELECT id, hsn_code, description, gst_rate 
                 FROM hsn_codes 
                 WHERE hsn_code LIKE ? OR description LIKE ? 
                 ORDER BY (hsn_code = ?) DESC, (hsn_code LIKE ?) DESC, hsn_code ASC 
                 LIMIT 50",
                ['%' . $q . '%', '%' . $q . '%', $q, $q . '%']
            );
        }

        Response::jsonSuccess('HSN codes retrieved.', ['items' => $items]);
    }
}
