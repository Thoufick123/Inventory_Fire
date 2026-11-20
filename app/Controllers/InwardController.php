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

        $poIdValue      = $_POST['purchase_order_id'] ?? '';
        if (!is_scalar($poIdValue)) {
            Response::redirectWithError('production/inwards/create', 'Select a valid purchase order or Direct Delivery.');
            return;
        }
        $poIdRaw        = trim((string)$poIdValue);
        if ($poIdRaw !== '' && (!ctype_digit($poIdRaw) || (int)$poIdRaw <= 0)) {
            Response::redirectWithError('production/inwards/create', 'Select a valid purchase order or Direct Delivery.');
            return;
        }
        $poId           = $poIdRaw === '' ? 0 : (int)$poIdRaw;
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

        $scanLog = json_decode((string)($_POST['scan_log'] ?? ''), true);
        if (!is_array($scanLog) || empty($scanLog)) {
            Response::redirectWithError('production/inwards/create', 'Scan at least one variant QR before saving this inward.');
            return;
        }

        $db = Database::getInstance();
        $poItems = [];
        if ($poId > 0) {
            $poStmt = $db->prepare("SELECT pi.variant_id, SUM(pi.quantity) AS ordered_qty, MAX(pi.unit_price) AS unit_price
                                    FROM purchase_items pi
                                    WHERE pi.purchase_order_id = ?
                                    GROUP BY pi.variant_id
                                    ORDER BY MIN(pi.id) ASC");
            $poStmt->execute([$poId]);
            foreach ($poStmt->fetchAll(PDO::FETCH_ASSOC) as $poItem) {
                $poItems[(int)$poItem['variant_id']] = [
                    'ordered_qty' => (int)$poItem['ordered_qty'],
                    'unit_price'  => (float)$poItem['unit_price'],
                ];
            }
            if (empty($poItems)) {
                Response::redirectWithError('production/inwards/create', 'The selected purchase order has no variant items to receive.');
                return;
            }
        }

        $variantCache = [];
        $scanCounts = [];
        foreach ($scanLog as $scan) {
            if (!is_array($scan)) {
                Response::redirectWithError('production/inwards/create', 'The QR scan list is invalid. Please scan the items again.');
                return;
            }

            $variantIdValue = $scan['variant_id'] ?? 0;
            $scannedQrValue = $scan['qr_code'] ?? '';
            if (!is_scalar($variantIdValue) || !is_scalar($scannedQrValue)) {
                Response::redirectWithError('production/inwards/create', 'The QR scan list contains an invalid variant.');
                return;
            }
            $variantId = (int)$variantIdValue;
            $scannedQr = strtoupper(trim((string)$scannedQrValue));
            if ($variantId <= 0 || $scannedQr === '') {
                Response::redirectWithError('production/inwards/create', 'The QR scan list contains an invalid variant.');
                return;
            }

            if (!array_key_exists($variantId, $variantCache)) {
                $variantStmt = $db->prepare("SELECT id, sku, qr_code, mrp FROM product_variants WHERE id = ?");
                $variantStmt->execute([$variantId]);
                $variantCache[$variantId] = $variantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            $variant = $variantCache[$variantId];
            if (!$variant) {
                Response::redirectWithError('production/inwards/create', 'A scanned product variant no longer exists.');
                return;
            }

            $canonicalQr = strtoupper(trim((string)($variant['qr_code'] ?: $variant['sku'])));
            if ($canonicalQr === '' || $scannedQr !== $canonicalQr) {
                Response::redirectWithError('production/inwards/create', 'A scanned QR does not match its product variant.');
                return;
            }
            if ($poId > 0 && !isset($poItems[$variantId])) {
                Response::redirectWithError('production/inwards/create', 'A scanned variant is not included in the selected purchase order.');
                return;
            }

            $scanCounts[$variantId] = ($scanCounts[$variantId] ?? 0) + 1;
        }

        $itemsRaw = $_POST['items'] ?? [];
        if (!is_array($itemsRaw)) {
            Response::redirectWithError('production/inwards/create', 'The inward item list is invalid.');
            return;
        }

        $formItems = [];
        foreach ($itemsRaw as $row) {
            if (!is_array($row)) {
                Response::redirectWithError('production/inwards/create', 'The inward item list contains an invalid row.');
                return;
            }
            $variantIdValue = $row['variant_id'] ?? 0;
            if (!is_scalar($variantIdValue)) {
                Response::redirectWithError('production/inwards/create', 'The inward item list contains an invalid variant.');
                return;
            }
            $variantId = (int)$variantIdValue;
            if ($variantId <= 0 || isset($formItems[$variantId])) {
                Response::redirectWithError('production/inwards/create', 'The inward item list contains an invalid or duplicate variant.');
                return;
            }
            $formItems[$variantId] = $row;
        }

        foreach ($formItems as $variantId => $row) {
            if ($poId > 0 && !isset($poItems[$variantId])) {
                Response::redirectWithError('production/inwards/create', 'An inward variant is not included in the selected purchase order.');
                return;
            }

            if (!array_key_exists($variantId, $variantCache)) {
                $variantStmt = $db->prepare("SELECT id, sku, qr_code, mrp FROM product_variants WHERE id = ?");
                $variantStmt->execute([$variantId]);
                $variantCache[$variantId] = $variantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            $variant = $variantCache[$variantId];
            $submittedQrValue = $row['qr_code'] ?? '';
            if (!is_scalar($submittedQrValue)) {
                Response::redirectWithError('production/inwards/create', 'An inward row contains an invalid variant QR.');
                return;
            }
            $submittedQr = strtoupper(trim((string)$submittedQrValue));
            $canonicalQr = $variant ? strtoupper(trim((string)($variant['qr_code'] ?: $variant['sku']))) : '';
            if (!$variant || $canonicalQr === '' || $submittedQr !== $canonicalQr) {
                Response::redirectWithError('production/inwards/create', 'An inward row does not match its product variant QR.');
                return;
            }
        }

        $itemVariantIds = $poId > 0 ? array_keys($poItems) : array_keys($scanCounts);
        if ($poId === 0 && array_diff($itemVariantIds, array_keys($formItems))) {
            Response::redirectWithError('production/inwards/create', 'A scanned variant is missing from the inward item list.');
            return;
        }

        $items = [];
        $totalRec = 0;
        $totalAcc = 0;
        $totalRej = 0;

        foreach ($itemVariantIds as $variantId) {
            $row = $formItems[$variantId] ?? [];
            $variant = $variantCache[$variantId] ?? null;
            if (!$variant) {
                $variantStmt = $db->prepare("SELECT id, sku, qr_code, mrp FROM product_variants WHERE id = ?");
                $variantStmt->execute([$variantId]);
                $variantCache[$variantId] = $variantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                $variant = $variantCache[$variantId];
            }
            if (!$variant) {
                Response::redirectWithError('production/inwards/create', 'A purchase-order variant no longer exists.');
                return;
            }

            $ordered  = $poItems[$variantId]['ordered_qty'] ?? 0;
            $received = $scanCounts[$variantId] ?? 0;
            $damagedValue = $row['damaged_qty'] ?? '0';
            $reasonValue = $row['rejection_reason'] ?? '';
            if (!is_scalar($damagedValue) || !is_scalar($reasonValue)) {
                Response::redirectWithError('production/inwards/create', 'Damage quantity or reason is invalid.');
                return;
            }
            $damagedRaw = trim((string)$damagedValue);
            if ($damagedRaw === '' || !preg_match('/^\d+$/', $damagedRaw)) {
                Response::redirectWithError('production/inwards/create', 'Damaged quantities must be whole numbers.');
                return;
            }
            $damaged = (int)$damagedRaw;
            if ($damaged > $received) {
                Response::redirectWithError('production/inwards/create', 'Damaged quantity cannot exceed the number of scanned units.');
                return;
            }
            if ($poId === 0 && $received <= 0) {
                Response::redirectWithError('production/inwards/create', 'Direct inward items must have at least one valid QR scan.');
                return;
            }
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
                'rejection_reason' => trim((string)$reasonValue),
                'mrp'              => $poItems[$variantId]['unit_price'] ?? (float)($variant['mrp'] ?? 0),
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
                'purchase_order_id'  => $poId > 0 ? $poId : null,
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
        $stmt = $db->prepare("SELECT pi.variant_id, SUM(pi.quantity) AS quantity, MAX(pi.unit_price) AS unit_price,
                                     pv.sku AS variant_sku, pv.variant_code, pv.qr_code AS variant_qr,
                                     p.name AS product_name, p.code AS parent_sku,
                                     c.name AS color_name, d.name AS design_name, s.name AS size_name
                              FROM purchase_items pi
                              JOIN product_variants pv ON pv.id = pi.variant_id
                              JOIN products p ON p.id = pv.product_id
                              LEFT JOIN colors c ON c.id = pv.color_id
                              LEFT JOIN designs d ON d.id = pv.design_id
                              LEFT JOIN sizes s ON s.id = pv.size_id
                              WHERE pi.purchase_order_id = ?
                              GROUP BY pi.variant_id, pv.sku, pv.variant_code, pv.qr_code,
                                       p.name, p.code, c.name, d.name, s.name
                              ORDER BY MIN(pi.id) ASC");
        $stmt->execute([(int)$poId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json([
            'success' => true,
            'items'   => $items,
        ]);
    }
}
