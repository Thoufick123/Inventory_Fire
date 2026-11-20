<?php
/**
 * Stock Adjustment Controller
 * Handles QR-based stock adjustments (Increases & Decreases)
 */

require_once BASE_PATH . '/app/Models/StockAdjustment.php';
require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Services/VariantService.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class StockAdjustmentController {

    public function index(): void {
        if (!hasPermission('stock_adjustments', 'view')) {
            Response::error(403, 'Access denied. You do not have permission to view stock adjustments.');
            return;
        }

        $filters = [
            'search'          => trim($_GET['search'] ?? ''),
            'adjustment_type' => trim($_GET['adjustment_type'] ?? ''),
            'status'          => trim($_GET['status'] ?? ''),
            'date_from'       => trim($_GET['date_from'] ?? ''),
            'date_to'         => trim($_GET['date_to'] ?? ''),
        ];

        $adjustments = StockAdjustment::getAll($filters);

        Response::view('production.stock_adjustments.index', [
            'pageTitle'    => 'Stock Adjustments',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-stock-adjustments',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Stock Adjustments'],
            ],
            'adjustments'  => $adjustments,
            'filters'      => $filters,
        ]);
    }

    public function create(): void {
        if (!hasPermission('stock_adjustments', 'create')) {
            Response::error(403, 'Access denied. You do not have permission to create stock adjustments.');
            return;
        }

        $db = Database::getInstance();
        $warehouses = $db->query("SELECT id, name FROM warehouses WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        $nextAdjNum = StockAdjustment::generateAdjustmentNumber();

        // Also fetch variants for direct selection dropdown if user doesn't scan QR
        $variants = ProductVariant::getAllJoined();

        Response::view('production.stock_adjustments.create', [
            'pageTitle'    => 'Create Stock Adjustment (QR)',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-stock-adjustments',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Stock Adjustments', 'url' => url('production/stock-adjustments')],
                ['label' => 'New Adjustment'],
            ],
            'warehouses'   => $warehouses,
            'nextAdjNum'   => $nextAdjNum,
            'variants'     => $variants,
        ]);
    }

    public function store(): void {
        if (!hasPermission('stock_adjustments', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $variantId      = (int)($_POST['variant_id'] ?? 0);
        $warehouseId    = (int)($_POST['warehouse_id'] ?? 1);
        $adjustmentType = in_array($_POST['adjustment_type'] ?? '', ['INCREASE', 'DECREASE']) ? $_POST['adjustment_type'] : '';
        $quantity       = (float)($_POST['quantity'] ?? 0);
        $reasonCategory = trim($_POST['reason_category'] ?? 'OTHER');
        $reasonNotes    = trim($_POST['reason_notes'] ?? '');
        $qrCodesRaw     = trim($_POST['scanned_qr_codes'] ?? '');

        if ($variantId <= 0) {
            Response::redirectWithError('production/stock-adjustments/create', 'Please scan or select a valid product variant.');
            return;
        }

        if (empty($adjustmentType)) {
            Response::redirectWithError('production/stock-adjustments/create', 'Please select an adjustment type (Increase or Decrease).');
            return;
        }

        if ($quantity <= 0) {
            Response::redirectWithError('production/stock-adjustments/create', 'Adjustment quantity must be greater than zero.');
            return;
        }

        $fullReason = $reasonCategory . ($reasonNotes !== '' ? ' — ' . $reasonNotes : '');
        $userId = Auth::id() ?? 1;

        // Verify stock availability if DECREASE to prevent negative inventory
        if ($adjustmentType === 'DECREASE') {
            $currentStock = StockService::getBalance($variantId, 'WAREHOUSE', $warehouseId, 'AVAILABLE');
            if ($currentStock < $quantity) {
                Response::redirectWithError('production/stock-adjustments/create', "Cannot decrease stock by {$quantity}! Current available stock is only {$currentStock} pcs.");
                return;
            }
        }

        $adjNumber = StockAdjustment::generateAdjustmentNumber();

        Database::beginTransaction();
        try {
            $adjId = StockAdjustment::create([
                'adjustment_number' => $adjNumber,
                'adj_number'        => $adjNumber,
                'warehouse_id'      => $warehouseId,
                'variant_id'        => $variantId,
                'adjustment_type'   => $adjustmentType,
                'quantity'          => $quantity,
                'reason'            => $fullReason,
                'requested_by'      => $userId,
                'approved_by'       => $userId,
                'status'            => 'APPROVED',
                'notes'             => $reasonNotes,
                'qr_codes'          => $qrCodesRaw,
            ]);

            // Execute transactional balance change
            if ($adjustmentType === 'INCREASE') {
                StockService::increase(
                    $variantId,
                    'WAREHOUSE',
                    $warehouseId,
                    'AVAILABLE',
                    $quantity,
                    'ADJUSTMENT',
                    'STOCK_ADJUSTMENT',
                    $adjId,
                    $userId,
                    "Stock Increase #{$adjNumber}: {$fullReason}"
                );
            } else {
                StockService::decrease(
                    $variantId,
                    'WAREHOUSE',
                    $warehouseId,
                    'AVAILABLE',
                    $quantity,
                    'ADJUSTMENT',
                    'STOCK_ADJUSTMENT',
                    $adjId,
                    $userId,
                    "Stock Decrease #{$adjNumber}: {$fullReason}"
                );
            }

            Database::commit();
            Response::redirectWithSuccess('production/stock-adjustments/view/' . $adjId, "Stock adjustment #{$adjNumber} applied successfully!");
        } catch (Exception $e) {
            Database::rollback();
            Response::redirectWithError('production/stock-adjustments/create', 'Failed to apply adjustment: ' . $e->getMessage());
        }
    }

    public function view(string $id): void {
        if (!hasPermission('stock_adjustments', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $adjustment = StockAdjustment::find((int)$id);
        if (!$adjustment) {
            Response::error(404, 'Stock adjustment record not found.');
            return;
        }

        // Fetch current live stock
        $currentStock = StockService::getBalance($adjustment['variant_id'], 'WAREHOUSE', (int)$adjustment['warehouse_id'], 'AVAILABLE');

        Response::view('production.stock_adjustments.view', [
            'pageTitle'    => 'Adjustment: ' . $adjustment['adjustment_number'],
            'activeModule' => 'production',
            'activeMenu'   => 'prod-stock-adjustments',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Stock Adjustments', 'url' => url('production/stock-adjustments')],
                ['label' => $adjustment['adjustment_number']],
            ],
            'adjustment'   => $adjustment,
            'currentStock' => $currentStock,
        ]);
    }

    public function apiResolveItem(): void {
        $query = trim($_POST['query'] ?? $_GET['query'] ?? '');
        $warehouseId = (int)($_POST['warehouse_id'] ?? $_GET['warehouse_id'] ?? 1);

        if ($query === '') {
            Response::json(['success' => false, 'message' => 'Empty query provided']);
            return;
        }

        $variant = VariantService::resolveQrCode($query);
        if (!$variant) {
            // Also attempt direct variant ID lookup
            if (is_numeric($query)) {
                $variant = ProductVariant::findJoined((int)$query);
            }
        }

        if (!$variant) {
            Response::json(['success' => false, 'message' => "No product variant found matching '{$query}'."]);
            return;
        }

        $availableStock = StockService::getBalance((int)$variant['id'], 'WAREHOUSE', $warehouseId, 'AVAILABLE');

        Response::json([
            'success' => true,
            'variant' => [
                'id'              => (int)$variant['id'],
                'variant_code'    => $variant['variant_code'] ?? '',
                'sku'             => $variant['sku'],
                'qr_code'         => $variant['qr_code'] ?? $variant['sku'],
                'product_id'      => (int)$variant['product_id'],
                'product_name'    => $variant['product_name'] ?? 'Product #' . $variant['product_id'],
                'parent_sku'      => $variant['parent_sku'] ?? '',
                'category_name'   => $variant['category_name'] ?? 'General',
                'color_name'      => $variant['color_name'] ?? 'N/A',
                'design_name'     => $variant['design_name'] ?? 'Plain',
                'size_name'       => $variant['size_name'] ?? 'N/A',
                'available_stock' => $availableStock,
            ]
        ]);
    }
}
