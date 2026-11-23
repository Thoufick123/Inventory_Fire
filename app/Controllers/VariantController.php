<?php
/**
 * VariantController
 * Manages Product Variants, Unique Variant IDs, Variant SKUs, and Scannable QR Identities.
 */

require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Models/Design.php';
require_once BASE_PATH . '/app/Services/VariantService.php';

class VariantController {

    /**
     * List all variants with rich filtering and stock levels.
     */
    public function index(): void {
        Database::ensureColumn('products', 'variants_deferred', 'TINYINT(1) NOT NULL DEFAULT 0');
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'category_id'    => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'subcategory_id' => !empty($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null,
            'product_id'     => !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null,
            'color_id'       => !empty($_GET['color_id']) ? (int)$_GET['color_id'] : null,
            'design_id'      => !empty($_GET['design_id']) ? (int)$_GET['design_id'] : null,
            'size_id'        => !empty($_GET['size_id']) ? (int)$_GET['size_id'] : null,
            'is_active'      => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (int)$_GET['is_active'] : null,
            'qr_status'      => trim($_GET['qr_status'] ?? ''),
            'hide_unstocked_deferred' => true,
        ];

        $variants = VariantService::listVariants($filters);

        // Filter dropdown data
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = Database::fetchAll("SELECT id, category_id, name, code FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $catalogs = Database::fetchAll("SELECT id, category_id, subcategory_id, name, code FROM products WHERE is_active = 1 ORDER BY name ASC");
        $colors = Database::fetchAll("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name ASC");
        $designs = Design::allActive();
        $sizes = Database::fetchAll("SELECT id, name, code FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

        // Summary statistics
        $stats = [
            'total_variants'   => count($variants),
            'active_variants'  => count(array_filter($variants, static fn($v) => (int)$v['is_active'] === 1)),
            'total_stock'      => array_sum(array_column($variants, 'current_stock')),
            'unique_catalogs'  => count(array_unique(array_column($variants, 'product_id'))),
        ];

        Response::view('production.variants.index', [
            'pageTitle'     => 'Product Variants & QR Management',
            'activeModule'  => 'production',
            'activeMenu'    => 'prod-variants',
            'variants'      => $variants,
            'filters'       => $filters,
            'categories'    => $categories,
            'subcategories' => $subcategories,
            'catalogs'      => $catalogs,
            'colors'        => $colors,
            'designs'       => $designs,
            'sizes'         => $sizes,
            'stats'         => $stats,
            'breadcrumb'    => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Product Variants'],
            ],
        ]);
    }

    /**
     * Show form / wizard to create a new variant.
     */
    public function create(): void {
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = Database::fetchAll("SELECT id, category_id, name, code FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $catalogs = Database::fetchAll("SELECT id, category_id, subcategory_id, name, code, hsn_code, gst_percentage FROM products WHERE is_active = 1 ORDER BY name ASC");
        $colors = Database::fetchAll("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name ASC");
        $designs = Design::allActive();
        $sizes = Database::fetchAll("SELECT id, name, code FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

        $suggestedVariantCode = VariantService::generateNextVariantCode();

        Response::view('production.variants.create', [
            'pageTitle'            => 'Add Product Variants | GRM Inventory',
            'activeModule'         => 'production',
            'activeMenu'           => 'prod-variants',
            'categories'           => $categories,
            'subcategories'        => $subcategories,
            'catalogs'             => $catalogs,
            'colors'               => $colors,
            'designs'              => $designs,
            'sizes'                => $sizes,
            'suggestedVariantCode' => $suggestedVariantCode,
            'breadcrumb'           => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Product Variants', 'url' => url('production/variants')],
                ['label' => 'Add Product Variants'],
            ],
        ]);
    }

    /**
     * Store newly created variant.
     */
    public function store(): void {
        try {
            $variant = VariantService::createVariant($_POST);

            if (Request::isAjax()) {
                Response::jsonSuccess('Product variant created successfully.', [
                    'variant'  => $variant,
                    'redirect' => url('production/variants')
                ]);
                return;
            }

            Session::flash('success', "Variant {$variant['variant_code']} ({$variant['sku']}) created successfully!");
            Response::redirect(url('production/variants'));
        } catch (Throwable $e) {
            if (Request::isAjax()) {
                Response::jsonError($e->getMessage(), 422);
                return;
            }

            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/variants/create'));
        }
    }

    /**
     * View variant details, full hierarchy, QR identity, and stock breakdown.
     */
    public function view(string $id): void {
        $variant = ProductVariant::find((int)$id);
        if (!$variant) {
            Response::error(404, 'Product Variant not found.');
            return;
        }

        $stock = VariantService::getVariantStock((int)$id);
        $hasHistory = ProductVariant::hasHistory((int)$id);

        Response::view('production.variants.view', [
            'pageTitle'    => 'Variant ' . ($variant['variant_code'] ?? $variant['sku']),
            'activeModule' => 'production',
            'activeMenu'   => 'prod-variants',
            'variant'      => $variant,
            'stock'        => $stock,
            'hasHistory'   => $hasHistory,
            'breadcrumb'   => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Product Variants', 'url' => url('production/variants')],
                ['label' => 'Product Variants ' . ($variant['variant_code'] ?: ($variant['sku'] ?: $variant['id']))],
            ],
        ]);
    }

    /**
     * Show edit form for permitted details.
     */
    public function edit(string $id): void {
        $variant = ProductVariant::find((int)$id);
        if (!$variant) {
            Response::error(404, 'Product Variant not found.');
            return;
        }

        $colors = Database::fetchAll("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name ASC");
        $designs = Design::allActive();
        $sizes = Database::fetchAll("SELECT id, name, code FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $hasHistory = ProductVariant::hasHistory((int)$id);

        Response::view('production.variants.edit', [
            'pageTitle'    => 'Edit Variant ' . ($variant['variant_code'] ?? $variant['sku']),
            'activeModule' => 'production',
            'activeMenu'   => 'prod-variants',
            'variant'      => $variant,
            'colors'       => $colors,
            'designs'      => $designs,
            'sizes'        => $sizes,
            'hasHistory'   => $hasHistory,
            'breadcrumb'   => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Product Variants', 'url' => url('production/variants')],
                ['label' => 'Edit Product Variants ' . ($variant['variant_code'] ?: ($variant['sku'] ?: $variant['id']))],
            ],
        ]);
    }

    /**
     * Update permitted details of a variant.
     */
    public function update(string $id): void {
        $variantId = (int)$id;
        try {
            $updated = VariantService::updateVariant($variantId, $_POST);

            if (Request::isAjax()) {
                Response::jsonSuccess('Variant updated successfully.', [
                    'variant'  => $updated,
                    'redirect' => url('production/variants')
                ]);
                return;
            }

            Session::flash('success', "Variant {$updated['variant_code']} updated successfully!");
            Response::redirect(url('production/variants'));
        } catch (Throwable $e) {
            if (Request::isAjax()) {
                Response::jsonError($e->getMessage(), 422);
                return;
            }

            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/variants/edit/' . $variantId));
        }
    }

    /**
     * Toggle variant active / inactive status.
     */
    public function toggle(string $id): void {
        $variantId = (int)$id;
        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            Response::error(404, 'Variant not found.');
            return;
        }

        $newStatus = (int)$variant['is_active'] === 1 ? 0 : 1;
        Database::execute("UPDATE product_variants SET is_active = ? WHERE id = ?", [$newStatus, $variantId]);

        $statusLabel = $newStatus === 1 ? 'activated' : 'deactivated';
        if (Request::isAjax()) {
            Response::jsonSuccess("Variant {$variant['variant_code']} has been {$statusLabel}.", ['is_active' => $newStatus]);
            return;
        }

        Session::flash('success', "Variant {$variant['variant_code']} {$statusLabel} successfully.");
        Response::redirect(url('production/variants'));
    }

    /**
     * Render a single printable thermal QR label for a variant.
     */
    public function printQrLabel(string $id): void {
        $variant = ProductVariant::find((int)$id);
        if (!$variant) {
            Response::error(404, 'Variant not found.');
            return;
        }

        Response::viewOnly('production.variants.label_single', [
            'variant' => $variant,
        ]);
    }

    /**
     * Batch print identical QR labels for a variant (e.g. 50 units produced in a batch).
     */
    public function printBatchQrLabels(): void {
        $variantId = (int)($_POST['variant_id'] ?? $_GET['variant_id'] ?? 0);
        $qty = max(1, min(500, (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1)));

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            Response::error(404, 'Variant not found.');
            return;
        }

        Response::viewOnly('production.variants.label_batch', [
            'variant'  => $variant,
            'quantity' => $qty,
        ]);
    }

    // =========================================================================
    // AJAX & API ENDPOINTS
    // =========================================================================

    /**
     * AJAX: Get catalogs filtered by Category / Subcategory.
     */
    public function ajaxCatalogs(): void {
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $subcategoryId = !empty($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;

        $sql = "SELECT id, name, code, hsn_code, gst_percentage FROM products WHERE is_active = 1";
        $params = [];

        if ($subcategoryId) {
            $sql .= " AND subcategory_id = ?";
            $params[] = $subcategoryId;
        } elseif ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY name ASC";
        $catalogs = Database::fetchAll($sql, $params);

        Response::jsonSuccess('Catalogs retrieved.', ['catalogs' => $catalogs]);
    }

    /**
     * AJAX: Live preview of auto-generated SKU, Variant ID, and duplicate check.
     */
    public function ajaxPreviewSku(): void {
        $productId = (int)($_GET['product_id'] ?? 0);
        $colorId = !empty($_GET['color_id']) ? (int)$_GET['color_id'] : null;
        $designId = !empty($_GET['design_id']) ? (int)$_GET['design_id'] : null;
        $sizeId = !empty($_GET['size_id']) ? (int)$_GET['size_id'] : null;
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        if ($productId <= 0) {
            Response::jsonSuccess('No catalog selected.', [
                'sku'          => '',
                'variant_code' => VariantService::generateNextVariantCode(),
                'is_duplicate' => false,
            ]);
            return;
        }

        try {
            $sku = VariantService::generateVariantSku($productId, $colorId, $designId, $sizeId);
            $isDuplicate = VariantService::checkDuplicateCombination($productId, $colorId, $designId, $sizeId, $excludeId);
            $variantCode = VariantService::generateNextVariantCode();

            Response::jsonSuccess('Preview generated.', [
                'sku'          => $sku,
                'variant_code' => $variantCode,
                'qr_code'      => $variantCode,
                'is_duplicate' => $isDuplicate,
            ]);
        } catch (Throwable $e) {
            Response::jsonError($e->getMessage(), 400);
        }
    }

    /**
     * AJAX: Check if combination exists.
     */
    public function ajaxCheckDuplicate(): void {
        $productId = (int)($_GET['product_id'] ?? 0);
        $colorId = !empty($_GET['color_id']) ? (int)$_GET['color_id'] : null;
        $designId = !empty($_GET['design_id']) ? (int)$_GET['design_id'] : null;
        $sizeId = !empty($_GET['size_id']) ? (int)$_GET['size_id'] : null;
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $isDuplicate = VariantService::checkDuplicateCombination($productId, $colorId, $designId, $sizeId, $excludeId);
        Response::jsonSuccess('Duplicate check completed.', ['is_duplicate' => $isDuplicate]);
    }

    /**
     * AJAX: Universal QR Code Resolver (for Billing & Scanners).
     */
    public function ajaxResolveQr(): void {
        $qr = trim($_GET['qr'] ?? $_POST['qr'] ?? '');
        $unitId = !empty($_GET['production_unit_id']) ? (int)$_GET['production_unit_id'] : null;

        if ($qr === '') {
            Response::jsonError('Please provide a QR code to resolve.', 400);
            return;
        }

        $variant = VariantService::resolveQrCode($qr, $unitId);
        if (!$variant) {
            Response::jsonError("No product variant found matching QR code '{$qr}'.", 404);
            return;
        }

        if ((int)$variant['is_active'] !== 1) {
            Response::jsonError("Variant '{$variant['variant_code']}' ({$variant['sku']}) is inactive and cannot be billed or processed.", 403);
            return;
        }

        Response::jsonSuccess('Variant resolved successfully.', ['variant' => $variant]);
    }

    /**
     * AJAX: Quick Add Design.
     */
    public function ajaxQuickAddDesign(): void {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $code = !empty($_POST['code']) ? mb_strtoupper(trim($_POST['code'])) : null;
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            Response::jsonError('Design name is required.', 422);
            return;
        }

        $existing = Database::fetchOne("SELECT id FROM designs WHERE name = ?", [$name]);
        if ($existing) {
            Response::jsonError("A design named '{$name}' already exists.", 422);
            return;
        }

        try {
            $id = Design::create([
                'name'        => $name,
                'code'        => $code,
                'description' => $description,
            ]);
            $design = Design::find($id);

            Response::jsonSuccess('Design added successfully.', ['design' => $design]);
        } catch (Throwable $e) {
            Response::jsonError($e->getMessage(), 500);
        }
    }

    /**
     * AJAX: Get all active designs.
     */
    public function ajaxDesignsList(): void {
        $designs = Design::allActive();
        Response::jsonSuccess('Designs loaded.', ['designs' => $designs]);
    }

    /**
     * Variant & Stock / Sales Comprehensive Reports.
     */
    public function reports(): void {
        $activeTab = trim($_GET['tab'] ?? 'variant_stock');

        // 1. Category-wise stock
        $categoryStock = Database::fetchAll("
            SELECT c.id, c.name, c.code,
                   COUNT(DISTINCT p.id) as catalog_count,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock,
                   COALESCE(SUM(sb.quantity * pv.selling_price), 0) as stock_valuation
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY c.id, c.name, c.code
            ORDER BY total_stock DESC, c.name ASC
        ");

        // 2. Subcategory-wise stock
        $subcategoryStock = Database::fetchAll("
            SELECT sc.id, sc.name, sc.code, c.name as category_name,
                   COUNT(DISTINCT p.id) as catalog_count,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock
            FROM subcategories sc
            JOIN categories c ON c.id = sc.category_id
            LEFT JOIN products p ON p.subcategory_id = sc.id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY sc.id, sc.name, sc.code, c.name
            ORDER BY total_stock DESC, sc.name ASC
        ");

        // 3. Catalog-wise stock
        $catalogStock = Database::fetchAll("
            SELECT p.id, p.name, p.code as parent_sku, c.name as category_name,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock,
                   COALESCE(SUM(sb.quantity * pv.selling_price), 0) as stock_valuation
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY p.id, p.name, p.code, c.name
            ORDER BY total_stock DESC, p.name ASC
        ");

        // 4. Variant-wise stock
        $variantStock = Database::fetchAll("
            SELECT pv.id, pv.variant_code, pv.sku, pv.qr_code, p.name as product_name, p.code as parent_sku,
                   c.name as category_name,
                   col.name as color_name, d.name as design_name, sz.name as size_name,
                   pv.selling_price, pv.mrp,
                   COALESCE(SUM(sb.quantity), 0) as available_stock,
                   COALESCE(SUM(sb.quantity * pv.selling_price), 0) as stock_valuation
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN colors col ON col.id = pv.color_id
            LEFT JOIN designs d ON d.id = pv.design_id
            LEFT JOIN sizes sz ON sz.id = pv.size_id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY pv.id, pv.variant_code, pv.sku, pv.qr_code, p.name, p.code, c.name, col.name, d.name, sz.name, pv.selling_price, pv.mrp
            ORDER BY available_stock DESC, pv.id DESC
        ");

        // 5. Color-wise stock
        $colorStock = Database::fetchAll("
            SELECT col.id, col.name, col.hex_code,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock
            FROM colors col
            LEFT JOIN product_variants pv ON pv.color_id = col.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY col.id, col.name, col.hex_code
            ORDER BY total_stock DESC, col.name ASC
        ");

        // 6. Design-wise stock
        $designStock = Database::fetchAll("
            SELECT d.id, d.name, d.code,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock
            FROM designs d
            LEFT JOIN product_variants pv ON pv.design_id = d.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY d.id, d.name, d.code
            ORDER BY total_stock DESC, d.name ASC
        ");

        // 7. Size-wise stock
        $sizeStock = Database::fetchAll("
            SELECT sz.id, sz.name, sz.code,
                   COUNT(DISTINCT pv.id) as variant_count,
                   COALESCE(SUM(sb.quantity), 0) as total_stock
            FROM sizes sz
            LEFT JOIN product_variants pv ON pv.size_id = sz.id
            LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'
            GROUP BY sz.id, sz.name, sz.code, sz.sort_order
            ORDER BY sz.sort_order ASC, sz.id ASC
        ");

        // 8. Variant-wise sales
        $variantSales = Database::fetchAll("
            SELECT pv.id, pv.variant_code, pv.sku, p.name as product_name, p.code as parent_sku,
                   c.name as category_name,
                   col.name as color_name, d.name as design_name, sz.name as size_name,
                   COALESCE(SUM(pbi.quantity), 0) as total_sold_qty,
                   COALESCE(SUM(pbi.total_amount), 0) as total_sales_revenue
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN colors col ON col.id = pv.color_id
            LEFT JOIN designs d ON d.id = pv.design_id
            LEFT JOIN sizes sz ON sz.id = pv.size_id
            JOIN production_bill_items pbi ON pbi.variant_id = pv.id
            JOIN production_bills pb ON pb.id = pbi.bill_id
            GROUP BY pv.id, pv.variant_code, pv.sku, p.name, p.code, c.name, col.name, d.name, sz.name
            ORDER BY total_sold_qty DESC
        ");

        // 9. Category-wise sales
        $categorySales = Database::fetchAll("
            SELECT c.id, c.name, c.code,
                   COUNT(DISTINCT pbi.id) as bill_items_count,
                   COALESCE(SUM(pbi.quantity), 0) as total_sold_qty,
                   COALESCE(SUM(pbi.total_amount), 0) as total_revenue
            FROM categories c
            JOIN products p ON p.category_id = c.id
            JOIN product_variants pv ON pv.product_id = p.id
            JOIN production_bill_items pbi ON pbi.variant_id = pv.id
            JOIN production_bills pb ON pb.id = pbi.bill_id
            GROUP BY c.id, c.name, c.code
            ORDER BY total_revenue DESC
        ");

        // 10. Subcategory-wise sales
        $subcategorySales = Database::fetchAll("
            SELECT sc.id, sc.name, sc.code, c.name as category_name,
                   COALESCE(SUM(pbi.quantity), 0) as total_sold_qty,
                   COALESCE(SUM(pbi.total_amount), 0) as total_revenue
            FROM subcategories sc
            JOIN categories c ON c.id = sc.category_id
            JOIN products p ON p.subcategory_id = sc.id
            JOIN product_variants pv ON pv.product_id = p.id
            JOIN production_bill_items pbi ON pbi.variant_id = pv.id
            JOIN production_bills pb ON pb.id = pbi.bill_id
            GROUP BY sc.id, sc.name, sc.code, c.name
            ORDER BY total_revenue DESC
        ");

        // 11. Batch-wise stock & sales
        $batchStockSales = Database::fetchAll("
            SELECT pb.id, pb.batch_number, pb.status, pb.created_at,
                   pp.program_number, pp.name as program_name,
                   pu.name as unit_name,
                   COUNT(pbi.id) as items_count,
                   COALESCE(SUM(pbi.quantity), 0) as batch_quantity
            FROM production_batches pb
            JOIN production_programs pp ON pp.id = pb.program_id
            LEFT JOIN production_units pu ON pu.id = pp.production_unit_id
            LEFT JOIN production_batch_items pbi ON pbi.production_batch_id = pb.id
            GROUP BY pb.id, pb.batch_number, pb.status, pb.created_at, pp.program_number, pp.name, pu.name
            ORDER BY pb.id DESC
            LIMIT 50
        ");

        Response::view('production.variants.reports', [
            'pageTitle'         => 'Variant Stock & Sales Reports',
            'activeModule'      => 'production',
            'activeMenu'        => 'prod-variants',
            'activeTab'         => $activeTab,
            'categoryStock'     => $categoryStock,
            'subcategoryStock'  => $subcategoryStock,
            'catalogStock'      => $catalogStock,
            'variantStock'      => $variantStock,
            'colorStock'        => $colorStock,
            'designStock'       => $designStock,
            'sizeStock'         => $sizeStock,
            'variantSales'      => $variantSales,
            'categorySales'     => $categorySales,
            'subcategorySales'  => $subcategorySales,
            'batchStockSales'   => $batchStockSales,
            'breadcrumb'        => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Product Variants', 'url' => url('production/variants')],
                ['label' => 'Reports'],
            ],
        ]);
    }
}
