<?php
/**
 * Inward Controller
 * Goods Receipt Notes (GRN), Production Inward Verification, and Warehouse Cross-Check Workstation
 */

require_once BASE_PATH . '/app/Services/WarehouseService.php';
require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Models/ProductionBatch.php';
require_once BASE_PATH . '/app/Models/ProductionOutward.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/WarehouseDamage.php';
require_once BASE_PATH . '/app/Models/ProductVariant.php';

class InwardController {

    // =========================================================================
    // 1. PRODUCTION INWARD LEDGER / DISPATCHES LIST (Screen 1)
    // =========================================================================

    public function index(): void {
        if (!hasPermission('inward', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to view inward shipments.');
            Response::redirect(url('production/dashboard'));
            return;
        }

        $scopedWarehouseId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;

        // If the user's scoped warehouse does not belong to the active concern, do not force it
        if ($scopedWarehouseId && $cid) {
            $whRow = Database::fetchOne("SELECT concern_id FROM warehouses WHERE id = ?", [$scopedWarehouseId]);
            if ($whRow && !empty($whRow['concern_id']) && (int)$whRow['concern_id'] !== $cid) {
                $scopedWarehouseId = null;
            }
        }

        $warehouseId = $scopedWarehouseId ? (string)$scopedWarehouseId : ($_GET['warehouse_id'] ?? '');
        $unitId = trim($_GET['production_unit_id'] ?? $_GET['unit_id'] ?? '');
        $status = $_GET['status'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT pb.*, 
                       pp.program_number, pp.target_date, pp.name as program_name, pp.production_unit_id as program_unit_id,
                       p.name as product_name, p.code as product_code,
                       w.name as warehouse_name, w.code as warehouse_code,
                       po.outward_number, po.dispatch_date, po.vehicle_number, po.driver_name, po.notes as outward_notes,
                       po.production_unit_id as outward_unit_id,
                       COALESCE(pu_out.name, pu_prog.name, 'Main Production Facility') as production_unit_name,
                       COALESCE(pu_out.code, pu_prog.code, 'PU1') as production_unit_code,
                       COALESCE(pu_out.manager_name, pu_prog.manager_name, '') as production_unit_manager,
                       COALESCE(pu_out.phone, pu_prog.phone, '') as production_unit_phone,
                       COALESCE(pu_out.address, pu_prog.address, '') as production_unit_address,
                       u_prep.full_name as prepared_by_name
                FROM production_batches pb
                LEFT JOIN production_programs pp ON pb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                JOIN warehouses w ON pb.destination_warehouse_id = w.id
                LEFT JOIN production_outwards po ON (po.id = pb.production_outward_id OR po.production_batch_id = pb.id)
                LEFT JOIN production_units pu_prog ON pp.production_unit_id = pu_prog.id
                LEFT JOIN production_units pu_out ON po.production_unit_id = pu_out.id
                LEFT JOIN users u_prep ON pb.prepared_by = u_prep.id
                WHERE pb.status IN ('DISPATCHED', 'RECEIVED')";
        $params = [];

        // Multi-concern isolation: Allow batches whose destination warehouse belongs to this concern, OR created by this concern, OR outwarded by this concern
        if ($cid !== null) {
            $sql .= " AND (
                w.concern_id = ? 
                OR pb.destination_warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?) 
                OR pb.concern_id = ? 
                OR po.concern_id = ?
            )";
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
        }

        if (!empty($warehouseId)) {
            $sql .= " AND pb.destination_warehouse_id = ?";
            $params[] = (int)$warehouseId;
        }

        if (!empty($unitId)) {
            $sql .= " AND (pp.production_unit_id = ? OR po.production_unit_id = ?)";
            $params[] = (int)$unitId;
            $params[] = (int)$unitId;
        }

        if (!empty($status)) {
            $sql .= " AND pb.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (
                pb.batch_number LIKE ? 
                OR pp.program_number LIKE ? 
                OR po.outward_number LIKE ? 
                OR p.name LIKE ? 
                OR po.vehicle_number LIKE ?
                OR pu_prog.name LIKE ?
                OR pu_out.name LIKE ?
                OR pu_prog.code LIKE ?
                OR pu_out.code LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY pb.id DESC";
        $batches = Database::fetchAll($sql, $params);
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : Warehouse::getAll(['is_active' => 1]);
        $productionUnits = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : ProductionUnit::getAll(['is_active' => 1]);

        // Fetch variant items for all retrieved batches for rich batch-wise card cards UI
        $batchIds = array_column($batches, 'id');
        $batchItemsMap = [];
        if (!empty($batchIds)) {
            $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
            $itemsSql = "SELECT pbi.*, pv.variant_code, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price,
                                s.name as size_name, c.name as color_name, c.hex_code as color_hex,
                                p.name as product_name, p.image_url as product_image, cat.name as category_name
                         FROM production_batch_items pbi
                         JOIN product_variants pv ON pbi.variant_id = pv.id
                         JOIN products p ON pv.product_id = p.id
                         LEFT JOIN categories cat ON p.category_id = cat.id
                         LEFT JOIN sizes s ON pv.size_id = s.id
                         LEFT JOIN colors c ON pv.color_id = c.id
                         WHERE pbi.production_batch_id IN ($placeholders)
                         ORDER BY pbi.id ASC";
            $rawItems = Database::fetchAll($itemsSql, $batchIds);
            foreach ($rawItems as $row) {
                $batchItemsMap[$row['production_batch_id']][] = $row;
            }
        }

        $pendingBatchesCount = 0;
        $totalPendingMissingQty = 0;
        $receivedBatchesCount = 0;
        $totalReceivedQty = 0;

        foreach ($batches as &$b) {
            $b['items'] = $batchItemsMap[$b['id']] ?? [];
            $b['total_variants'] = count($b['items']);

            $batchExpected = (int)($b['total_qty'] ?? $b['quantity'] ?? 0);
            $itemsExpected = 0;
            $itemsReceived = 0;
            $itemsDamaged = 0;
            $itemsMissing = 0;

            foreach ($b['items'] as &$item) {
                $iExp = (int)($item['quantity'] ?? 0);
                $iRec = (int)($item['received_qty'] ?? 0);
                $iDmg = (int)($item['damaged_qty'] ?? 0);
                $iMis = isset($item['missing_qty']) ? (int)$item['missing_qty'] : max(0, $iExp - ($iRec + $iDmg));

                if ($b['status'] === 'RECEIVED' && $iRec === 0 && $iDmg === 0 && $iMis === 0) {
                    $iRec = $iExp;
                    $iMis = 0;
                    $item['received_qty'] = $iRec;
                    $item['missing_qty'] = 0;
                }

                $itemsExpected += $iExp;
                $itemsReceived += $iRec;
                $itemsDamaged += $iDmg;
                $itemsMissing += $iMis;
            }
            unset($item);

            if ($itemsExpected > 0) {
                $batchExpected = $itemsExpected;
                $b['total_qty'] = $itemsExpected;
            }

            if ($b['status'] === 'RECEIVED') {
                $b['received_qty'] = isset($b['received_qty']) && (int)$b['received_qty'] > 0 ? (int)$b['received_qty'] : $itemsReceived;
                $b['damaged_qty'] = isset($b['damaged_qty']) ? (int)$b['damaged_qty'] : $itemsDamaged;
                $b['missing_qty'] = isset($b['missing_qty']) ? (int)$b['missing_qty'] : max(0, $batchExpected - ($b['received_qty'] + $b['damaged_qty']));

                $receivedBatchesCount++;
                $totalReceivedQty += $b['received_qty'];
                if ($b['missing_qty'] > 0) {
                    $totalPendingMissingQty += $b['missing_qty'];
                }
            } else {
                $b['received_qty'] = $itemsReceived;
                $b['damaged_qty'] = $itemsDamaged;
                $b['missing_qty'] = max(0, $batchExpected - ($itemsReceived + $itemsDamaged));
                $pendingBatchesCount++;
                $totalPendingMissingQty += $b['missing_qty'];
            }
        }
        unset($b);

        Response::view('allmodules.inwards.index', [
            'pageTitle'              => 'Inward',
            'activeModule'           => 'production',
            'activeMenu'             => 'prod-inwards',
            'batches'                => $batches,
            'warehouses'             => $warehouses,
            'productionUnits'        => $productionUnits,
            'warehouseId'            => $warehouseId,
            'scopedWarehouseId'      => $scopedWarehouseId,
            'unitId'                 => $unitId,
            'status'                 => $status,
            'search'                 => $search,
            'pendingBatchesCount'    => $pendingBatchesCount,
            'totalPendingMissingQty' => $totalPendingMissingQty,
            'receivedBatchesCount'   => $receivedBatchesCount,
            'totalReceivedQty'       => $totalReceivedQty,
            'breadcrumb'             => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Inward'],
            ],
        ]);
    }

    // =========================================================================
    // 2. INWARD SHIPMENT CROSS-CHECK WORKSTATION (Screens 2 & 3)
    // =========================================================================

    /**
     * Check if a production batch can be accessed / inwarded under the active concern context.
     */
    private function canAccessBatch(array $batch, ?int $cid): bool {
        if ($cid === null) {
            return true;
        }

        $batchCid = !empty($batch['concern_id']) ? (int)$batch['concern_id'] : null;
        if ($batchCid === $cid) {
            return true;
        }

        $destWhId = !empty($batch['destination_warehouse_id']) ? (int)$batch['destination_warehouse_id'] : 0;
        if ($destWhId > 0) {
            $wh = Warehouse::find($destWhId);
            if ($wh) {
                if (empty($wh['concern_id']) || (int)$wh['concern_id'] === $cid) {
                    return true;
                }
                // Check if warehouse is assigned to users in this concern
                $assignedInConcern = Database::fetchOne(
                    "SELECT 1 FROM users u 
                     WHERE u.warehouse_id = ? 
                       AND (u.default_concern_id = ? OR EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id = ?)) 
                     LIMIT 1",
                    [$destWhId, $cid, $cid]
                );
                if ($assignedInConcern) {
                    return true;
                }
            }
        }

        // Check if outward dispatch belongs to this concern or destination warehouse belongs to this concern
        if (!empty($batch['id'])) {
            $outward = ProductionOutward::findByBatchId((int)$batch['id']);
            if ($outward) {
                if (!empty($outward['concern_id']) && (int)$outward['concern_id'] === $cid) {
                    return true;
                }
                if (!empty($outward['warehouse_id'])) {
                    $outWh = Warehouse::find((int)$outward['warehouse_id']);
                    if ($outWh && ((int)($outWh['concern_id'] ?? 0) === $cid || empty($outWh['concern_id']))) {
                        return true;
                    }
                }
            }
        }

        // Check if program belongs to this concern
        if (!empty($batch['program_id'])) {
            $prog = Database::fetchOne("SELECT concern_id FROM production_programs WHERE id = ?", [(int)$batch['program_id']]);
            if ($prog && !empty($prog['concern_id']) && (int)$prog['concern_id'] === $cid) {
                return true;
            }
        }

        return false;
    }

    public function view(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to view inward shipments.');
            Response::redirect(url('production/dashboard'));
            return;
        }

        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::redirect(url('production/inwards'), 'error', 'Production batch not found.');
            return;
        }

        // Multi-concern isolation check
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$this->canAccessBatch($batch, $cid)) {
            Response::redirect(url('production/inwards'), 'error', 'This production batch belongs to another concern.');
            return;
        }

        $outward = ProductionOutward::findByBatchId($batchId);
        $items = ProductionBatch::getItems($batchId);
        $lifecycle = ProductionBatch::getLifecycleHistory($batchId);
        $damages = WarehouseDamage::getByBatchId($batchId);

        $totalExpected = 0;
        $totalReceived = 0;
        $totalDamaged = 0;
        $totalMissing = 0;
        foreach ($items as &$item) {
            $exp = (int)($item['quantity'] ?? 0);
            $rec = (int)($item['received_qty'] ?? 0);
            $dmg = (int)($item['damaged_qty'] ?? 0);
            $mis = isset($item['missing_qty']) ? (int)$item['missing_qty'] : max(0, $exp - ($rec + $dmg));

            if ($batch['status'] === 'RECEIVED' && $rec === 0 && $dmg === 0 && $mis === 0) {
                $rec = $exp;
                $mis = 0;
                $item['received_qty'] = $rec;
                $item['missing_qty'] = 0;
            }

            $totalExpected += $exp;
            $totalReceived += $rec;
            $totalDamaged += $dmg;
            $totalMissing += $mis;
        }
        unset($item);

        if ($batch['status'] === 'RECEIVED') {
            if (isset($batch['received_qty']) && (int)$batch['received_qty'] > 0) {
                $totalReceived = (int)$batch['received_qty'];
            }
            if (isset($batch['damaged_qty'])) {
                $totalDamaged = (int)$batch['damaged_qty'];
            }
            if (isset($batch['missing_qty'])) {
                $totalMissing = (int)$batch['missing_qty'];
            }
        }

        Response::view('allmodules.inwards.view', [
            'pageTitle'     => 'Cross-Check Inward Batch #' . $batch['batch_number'],
            'activeModule'  => 'production',
            'activeMenu'    => 'prod-inwards',
            'batch'         => $batch,
            'outward'       => $outward,
            'items'         => $items,
            'lifecycle'     => $lifecycle,
            'damages'       => $damages,
            'totalExpected' => $totalExpected,
            'totalReceived' => $totalReceived,
            'totalDamaged'  => $totalDamaged,
            'totalMissing'  => $totalMissing,
            'breadcrumb'    => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Inward', 'url' => url('production/inwards')],
                ['label' => 'Cross-Check Batch #' . $batch['batch_number']],
            ],
        ]);
    }

    // =========================================================================
    // 3. INWARD VERIFY & STOCK CREDITING
    // =========================================================================

    public function verifyInward(string $id): void {
        $batchId = (int)$id;
        if (!hasPermission('inward', 'verify')) {
            Session::flash('error', 'Access denied: You do not have permission to verify or inward stock.');
            Response::redirect(url('production/inwards/view/' . $batchId));
            return;
        }

        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::redirect(url('production/inwards'), 'error', 'Production batch not found.');
            return;
        }

        // Multi-concern isolation check
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$this->canAccessBatch($batch, $cid)) {
            Response::redirect(url('production/inwards'), 'error', 'This production batch belongs to another concern.');
            return;
        }

        $notes = trim($_POST['notes'] ?? '');

        $receivedItems = null;
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            $receivedItems = [];
            foreach ($_POST['items'] as $varId => $data) {
                $receivedItems[(int)$varId] = [
                    'received_qty'  => (float)($data['received_qty'] ?? 0),
                    'damaged_qty'   => (float)($data['damaged_qty'] ?? 0),
                    'received_tags' => trim($data['received_tags'] ?? ''),
                    'damaged_tags'  => trim($data['damaged_tags'] ?? ''),
                    'notes'         => trim($data['notes'] ?? ''),
                ];
            }
        }

        try {
            $userId = Session::userId() ?? 1;
            WarehouseService::receiveProductionBatch($batchId, $userId, $notes, $receivedItems);

            $batchNum = $batch['batch_number'] ?? "#$batchId";
            Response::redirect(url('production/inwards/view/' . $batchId), 'success', "Batch $batchNum successfully verified and credited to warehouse inventory!");
        } catch (Exception $e) {
            Response::redirect(url('production/inwards/view/' . $batchId), 'error', "Inward verification failed: " . $e->getMessage());
        }
    }

    public function store(): void {
        $batchId = (int)($_POST['production_batch_id'] ?? $_POST['batch_id'] ?? 0);
        if ($batchId > 0) {
            $this->verifyInward((string)$batchId);
            return;
        }
        Response::redirect(url('production/inwards'));
    }

    public function create(): void {
        Response::redirect(url('production/inwards'));
    }

    // =========================================================================
    // 4. PRINTING LABELS & GATE PASS
    // =========================================================================

    public function printBatchQRLabel(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to print batch labels.');
            Response::redirect(url('production/inwards'));
            return;
        }
        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::error(404, 'Production batch not found.');
            return;
        }
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$this->canAccessBatch($batch, $cid)) {
            Response::error(403, 'Access denied: Production batch belongs to another concern.');
            return;
        }
        $items = ProductionBatch::getItems($batchId);
        Response::viewOnly('allmodules.inwards.label', [
            'batch' => $batch,
            'items' => $items,
        ]);
    }

    public function printOutwardGatePass(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to print outward gatepasses.');
            Response::redirect(url('production/inwards'));
            return;
        }
        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::error(404, 'Production batch not found.');
            return;
        }
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$this->canAccessBatch($batch, $cid)) {
            Response::error(403, 'Access denied: Production batch belongs to another concern.');
            return;
        }
        $outward = ProductionOutward::findByBatchId($batchId);
        $items = ProductionBatch::getItems($batchId);
        Response::viewOnly('allmodules.inwards.gatepass', [
            'batch'   => $batch,
            'outward' => $outward,
            'items'   => $items,
        ]);
    }

    // =========================================================================
    // 5. SCAN & CROSS-CHECK AJAX ENDPOINTS
    // =========================================================================

    public function ajaxCrossCheckScan(): void {
        if (!hasPermission('inward', 'verify')) {
            Response::jsonError('Access denied: You do not have permission to verify inward scans.', [], 403);
            return;
        }
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $scannedCode = trim($_POST['qr_code'] ?? '');
        $rawScannedMap = $_POST['scanned_map'] ?? [];

        if (is_string($rawScannedMap)) {
            $scannedMap = json_decode($rawScannedMap, true) ?? [];
        } else {
            $scannedMap = (array)$rawScannedMap;
        }

        if ($batchId <= 0) {
            Response::jsonError("Invalid batch identifier.");
            return;
        }

        if (empty($scannedCode)) {
            Response::jsonError("QR code cannot be empty.");
            return;
        }

        // Multi-concern validation
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        $batch = ProductionBatch::find($batchId);
        if (!$batch || !$this->canAccessBatch($batch, $cid)) {
            Response::jsonError("Batch belongs to another concern and cannot be accessed.", [], 403);
            return;
        }

        if (str_starts_with($scannedCode, '{') && str_ends_with($scannedCode, '}')) {
            $json = json_decode($scannedCode, true);
            if (!empty($json['sku'])) {
                $scannedCode = $json['sku'];
            } elseif (!empty($json['qr_code'])) {
                $scannedCode = $json['qr_code'];
            } elseif (!empty($json['batch_number'])) {
                $scannedCode = $json['batch_number'];
            }
        }

        try {
            $result = WarehouseService::processCrossCheckScan($batchId, $scannedCode, $scannedMap);
            if (!$result['success']) {
                Response::jsonError($result['message'], $result, 404);
                return;
            }
            Response::jsonSuccess($result['message'], $result);
        } catch (Exception $e) {
            Response::jsonError("Cross-check scan error: " . $e->getMessage());
        }
    }

    public function ajaxLookupBatch(string $code): void {
        if (!hasPermission('inward', 'view')) {
            Response::jsonError('Access denied: You do not have permission to look up batches.', [], 403);
            return;
        }
        $code = trim($code);
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;

        $sql = "SELECT pb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name, p.code as product_code,
                       w.name as warehouse_name, w.code as warehouse_code,
                       po.outward_number, po.dispatch_date, po.vehicle_number
                FROM production_batches pb
                LEFT JOIN production_programs pp ON pb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                JOIN warehouses w ON pb.destination_warehouse_id = w.id
                LEFT JOIN production_outwards po ON (po.id = pb.production_outward_id OR po.production_batch_id = pb.id)
                WHERE (pb.batch_number = ? OR po.outward_number = ?)";
        $params = [$code, $code];

        if ($cid !== null) {
            $sql .= " AND (w.concern_id = ? OR pb.destination_warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?) OR pb.concern_id = ? OR po.concern_id = ?)";
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
        }

        $batch = Database::fetchOne($sql, $params);

        if (!$batch) {
            Response::jsonError("No batch found matching '$code' for the active concern. Please check QR/Batch code.", [], 404);
            return;
        }

        $items = ProductionBatch::getItems((int)$batch['id']);
        Response::jsonSuccess("Batch found.", [
            'batch'    => $batch,
            'items'    => $items,
            'viewUrl'  => url('production/inwards/view/' . $batch['id']),
            'labelUrl' => url('production/inwards/label/' . $batch['id']),
        ]);
    }

    public function ajaxScanInward(): void {
        if (!hasPermission('inward', 'verify')) {
            Response::jsonError('Access denied: You do not have permission to receive or inward stock via scan.', [], 403);
            return;
        }
        $code = trim($_POST['qr_code'] ?? '');
        $autoReceive = isset($_POST['auto_receive']) && ($_POST['auto_receive'] === '1' || $_POST['auto_receive'] === 'true');
        $notes = trim($_POST['notes'] ?? 'Scanned & Received via Inward QR Scanner');

        if (empty($code)) {
            Response::jsonError("QR code or batch identifier cannot be empty.");
            return;
        }

        $cid = function_exists('currentConcernId') ? currentConcernId() : null;

        // Clean code and handle JSON / Pipe formats
        $parsedCode = $code;
        $extractedSku = '';
        $extractedTag = '';

        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $json = json_decode($code, true);
            if (!empty($json['batch_number'])) {
                $parsedCode = $json['batch_number'];
            } elseif (!empty($json['outward_number'])) {
                $parsedCode = $json['outward_number'];
            }
        } elseif (str_contains($code, '|')) {
            $parts = explode('|', $code);
            $extractedTag = trim($parts[0]);
        }

        // Search for matching batch
        $batchSql = "SELECT pb.*, pp.program_number, p.name as product_name, w.name as warehouse_name, w.code as warehouse_code
                     FROM production_batches pb
                     LEFT JOIN production_programs pp ON pb.program_id = pp.id
                     LEFT JOIN products p ON pp.product_id = p.id
                     JOIN warehouses w ON pb.destination_warehouse_id = w.id
                     LEFT JOIN production_outwards po ON (po.id = pb.production_outward_id OR po.production_batch_id = pb.id)
                     LEFT JOIN production_batch_items pbi ON pbi.production_batch_id = pb.id
                     LEFT JOIN product_variants pv ON pbi.variant_id = pv.id
                     WHERE (
                         pb.batch_number = ?
                         OR po.outward_number = ?
                         OR pv.variant_code = ?
                         OR pv.qr_code = ?
                         OR ? IN (pb.batch_number, po.outward_number)
                     )";
        $params = [$code, $code, $parsedCode, $parsedCode, $parsedCode];

        if ($cid !== null) {
            $batchSql .= " AND (w.concern_id = ? OR pb.destination_warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?) OR pb.concern_id = ? OR po.concern_id = ?)";
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
            $params[] = $cid;
        }

        $batchSql .= " ORDER BY pb.id DESC LIMIT 1";
        $batch = Database::fetchOne($batchSql, $params);

        if (!$batch) {
            Response::jsonError("No production batch found matching code '$code' in this concern.", [], 404);
            return;
        }

        $batchId = (int)$batch['id'];

        if ($autoReceive) {
            try {
                $userId = Session::userId() ?? 1;
                WarehouseService::receiveProductionBatch($batchId, $userId, $notes);
                Response::jsonSuccess("Batch #{$batch['batch_number']} scanned & successfully received into warehouse!", [
                    'batch'     => $batch,
                    'viewUrl'   => url('production/inwards/view/' . $batchId),
                    'received'  => true,
                ]);
                return;
            } catch (Exception $e) {
                Response::jsonError("Failed to auto-receive batch: " . $e->getMessage());
                return;
            }
        }

        Response::jsonSuccess("Batch identified. Redirecting to cross-check workstation...", [
            'batch'    => $batch,
            'viewUrl'  => url('production/inwards/view/' . $batchId),
            'received' => false,
        ]);
    }

    public function ajaxBatchHistory(string $id): void {
        if (!hasPermission('inward', 'view')) {
            Response::jsonError('Access denied: You do not have permission to view batch history.', [], 403);
            return;
        }
        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($batch && !$this->canAccessBatch($batch, $cid)) {
            Response::jsonError('Access denied: Batch belongs to another concern.', [], 403);
            return;
        }
        $history = ProductionBatch::getLifecycleHistory($batchId);
        if (!$history) {
            Response::jsonError('Batch not found or no traceability history recorded.', [], 404);
            return;
        }
        Response::jsonSuccess('Batch lifecycle history loaded successfully', $history);
    }
}
