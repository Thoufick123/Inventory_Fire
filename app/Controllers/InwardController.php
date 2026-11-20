<?php
/**
 * Inward Controller
 * Goods Receipt Notes (GRN), Supplier delivery inwarding, and verification
 */

require_once BASE_PATH . '/app/Models/PurchaseInward.php';
require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class InwardController {

    public function index(): void {
        if (!hasPermission('inward', 'view')) {
            Response::error(403, 'Access denied. You do not have permission to view Goods Inward.');
            return;
        }

        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'status'    => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];

        $inwards = PurchaseInward::getAll($filters);

        Response::view('production.inwards.index', [
            'pageTitle'    => 'Goods Inward (GRN)',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-inwards',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Goods Inward (GRN)'],
            ],
            'inwards'      => $inwards,
            'filters'      => $filters,
        ]);
    }

    public function create(): void {
        if (!hasPermission('inward', 'create')) {
            Response::error(403, 'Access denied. You do not have permission to create Inward GRNs.');
            return;
        }

        $db = Database::getInstance();
        $pos = $db->query("SELECT id, po_number, order_date, total_amount, status FROM purchase_orders ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        $warehouses = $db->query("SELECT id, name FROM warehouses WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        $nextGrn = PurchaseInward::generateGrnNumber();

        // Also fetch variants for direct addition if needed
        $variants = ProductVariant::getAllJoined();

        Response::view('production.inwards.create', [
            'pageTitle'    => 'Create Goods Inward (GRN)',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-inwards',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Goods Inward', 'url' => url('production/inwards')],
                ['label' => 'Create GRN'],
            ],
            'purchaseOrders' => $pos,
            'warehouses'     => $warehouses,
            'nextGrn'        => $nextGrn,
            'variants'       => $variants,
        ]);
    }

    public function store(): void {
        if (!hasPermission('inward', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $poId           = (int)($_POST['purchase_order_id'] ?? 0);
        $warehouseId    = (int)($_POST['warehouse_id'] ?? 1);
        $receivedDate   = trim($_POST['received_date'] ?? date('Y-m-d'));
        $invoiceNumber  = trim($_POST['invoice_number'] ?? '');
        $invoiceDate    = trim($_POST['invoice_date'] ?? '');
        $vehicleNumber  = trim($_POST['vehicle_number'] ?? '');
        $driverName     = trim($_POST['driver_name'] ?? '');
        $driverContact  = trim($_POST['driver_contact'] ?? '');
        $gatePassNumber = trim($_POST['gate_pass_number'] ?? '');
        $notes          = trim($_POST['notes'] ?? '');
        $inwardStatus   = in_array($_POST['status'] ?? '', ['PENDING', 'INSPECTED', 'STOCKED', 'DISCREPANCY']) ? $_POST['status'] : 'STOCKED';

        $itemsRaw = $_POST['items'] ?? [];

        if (empty($itemsRaw) || !is_array($itemsRaw)) {
            Response::redirectWithError('production/inwards/create', 'Please add at least one line item to inward.');
            return;
        }

        $items = [];
        $totalRec = 0;
        $totalAcc = 0;
        $totalRej = 0;

        foreach ($itemsRaw as $row) {
            $variantId = (int)($row['variant_id'] ?? 0);
            if ($variantId <= 0) continue;

            $ordered  = (int)($row['ordered_qty'] ?? 0);
            $received = (int)($row['received_qty'] ?? 0);
            $damaged  = (int)($row['damaged_qty'] ?? 0);
            $accepted = max(0, $received - $damaged);

            $totalRec += $received;
            $totalAcc += $accepted;
            $totalRej += $damaged;

            $items[] = [
                'variant_id'       => $variantId,
                'ordered_qty'      => $ordered,
                'received_qty'     => $received,
                'damaged_qty'      => $damaged,
                'accepted_qty'     => $accepted,
                'rejection_reason' => trim($row['rejection_reason'] ?? ''),
                'mrp'              => (float)($row['mrp'] ?? 0),
                'batch_lot_no'     => trim($row['batch_lot_no'] ?? ''),
                'notes'            => trim($row['notes'] ?? ''),
            ];
        }

        if (empty($items)) {
            Response::redirectWithError('production/inwards/create', 'No valid line items found.');
            return;
        }

        $grnNumber = PurchaseInward::generateGrnNumber();
        $userId = Auth::id() ?? 1;

        Database::beginTransaction();
        try {
            $inwardId = PurchaseInward::create([
                'concern_id'         => currentConcernId(),
                'purchase_order_id'  => $poId,
                'grn_number'         => $grnNumber,
                'warehouse_id'       => $warehouseId,
                'received_date'      => $receivedDate,
                'received_by'        => $userId,
                'verified_by'        => $userId,
                'notes'              => $notes,
                'invoice_number'     => $invoiceNumber,
                'invoice_date'       => $invoiceDate ?: null,
                'status'             => $inwardStatus,
                'total_received_qty' => $totalRec,
                'total_accepted_qty' => $totalAcc,
                'total_rejected_qty' => $totalRej,
                'vehicle_number'     => $vehicleNumber,
                'driver_name'        => $driverName,
                'driver_contact'     => $driverContact,
                'gate_pass_number'   => $gatePassNumber,
            ], $items);

            // If STOCKED, increase inventory for all accepted quantities
            if ($inwardStatus === 'STOCKED' && $totalAcc > 0) {
                foreach ($items as $item) {
                    if ($item['accepted_qty'] > 0) {
                        StockService::increase(
                            $item['variant_id'],
                            'WAREHOUSE',
                            $warehouseId,
                            'AVAILABLE',
                            (float)$item['accepted_qty'],
                            'INWARD',
                            'PURCHASE_INWARD',
                            $inwardId,
                            $userId,
                            "GRN Inward #{$grnNumber}"
                        );
                    }
                }
            }

            Database::commit();
            Response::redirectWithSuccess('production/inwards/view/' . $inwardId, "Goods Receipt Note {$grnNumber} created successfully!");
        } catch (Exception $e) {
            Database::rollback();
            Response::redirectWithError('production/inwards/create', 'Failed to save GRN: ' . $e->getMessage());
        }
    }

    public function view(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $inward = PurchaseInward::find((int)$id);
        if (!$inward) {
            Response::error(404, 'Inward GRN not found.');
            return;
        }

        $items = PurchaseInward::getItems((int)$id);

        Response::view('production.inwards.view', [
            'pageTitle'    => 'GRN: ' . $inward['grn_number'],
            'activeModule' => 'production',
            'activeMenu'   => 'prod-inwards',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'Goods Inward', 'url' => url('production/inwards')],
                ['label' => $inward['grn_number']],
            ],
            'inward'       => $inward,
            'items'        => $items,
        ]);
    }

    public function print(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Response::error(403, 'Access denied.');
            return;
        }

        $inward = PurchaseInward::find((int)$id);
        if (!$inward) {
            Response::error(404, 'Inward GRN not found.');
            return;
        }

        $items = PurchaseInward::getItems((int)$id);

        Response::viewOnly('production.inwards.print', [
            'inward' => $inward,
            'items'  => $items,
        ]);
    }

    public function apiPoItems(string $poId): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT pi.*, 
                                     pv.sku AS variant_sku, pv.variant_code, pv.qr_code AS variant_qr,
                                     p.name AS product_name, p.code AS parent_sku,
                                     c.name AS color_name, d.name AS design_name, s.name AS size_name
                              FROM purchase_items pi
                              JOIN product_variants pv ON pv.id = pi.variant_id
                              JOIN products p ON p.id = pv.product_id
                              LEFT JOIN colors c ON c.id = pv.color_id
                              LEFT JOIN designs d ON d.id = pv.design_id
                              LEFT JOIN sizes s ON s.id = pv.size_id
                              WHERE pi.purchase_order_id = ?");
        $stmt->execute([(int)$poId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json([
            'success' => true,
            'items'   => $items,
        ]);
    }
}
