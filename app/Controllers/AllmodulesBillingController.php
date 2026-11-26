<?php

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/VariantService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';

class AllmodulesBillingController
{
    private static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_allmodules_billing_schema_checked'])) {
            $checked = true;
            return;
        }
        $checked = true;

        try {
            Database::ensureColumn('production_bills', 'is_direct_bill', 'TINYINT(1) NOT NULL DEFAULT 0');
            Database::ensureColumn('production_bills', 'upi_payment_screenshot_url', 'VARCHAR(500) DEFAULT NULL');
            Database::ensureColumn('production_bills', 'cash_given', 'DECIMAL(12,2) DEFAULT 0.00');
            Database::ensureColumn('production_bills', 'change_returned', 'DECIMAL(12,2) DEFAULT 0.00');
            Database::ensureColumn('production_bills', 'staff_id', 'INT(10) UNSIGNED DEFAULT NULL');
            Database::ensureColumn('production_bills', 'staff_name', 'VARCHAR(150) DEFAULT NULL');

            $idx = Database::fetchOne("SHOW INDEX FROM production_bills WHERE Key_name = 'idx_production_bills_direct'");
            if (!$idx) {
                Database::execute("ALTER TABLE production_bills ADD INDEX idx_production_bills_direct (is_direct_bill)");
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_allmodules_billing_schema_checked'] = true;
            }
        } catch (\Throwable $e) {}
    }

    public function billList(): void
    {
        self::ensureSchema();
        $cid = currentConcernId();
        $whereConditions = [];
        if ($cid) {
            $whereConditions[] = ($cid === 1) ? "(pb.concern_id = 1 OR pb.concern_id IS NULL)" : "pb.concern_id = " . (int)$cid;
        }

        $activeTab = trim((string)($_GET['tab'] ?? 'normal')); // 'normal', 'customer', 'company', 'direct', or 'all'
        if ($activeTab === 'normal') {
            $whereConditions[] = "COALESCE(pb.is_direct_bill, 0) = 0";
        } elseif ($activeTab === 'customer') {
            $whereConditions[] = "COALESCE(pb.is_direct_bill, 0) = 0 AND pb.buyer_type = 'CUSTOMER'";
        } elseif ($activeTab === 'company') {
            $whereConditions[] = "COALESCE(pb.is_direct_bill, 0) = 0 AND pb.buyer_type = 'COMPANY'";
        } elseif ($activeTab === 'direct') {
            $whereConditions[] = "COALESCE(pb.is_direct_bill, 0) = 1";
        }

        $whereSql = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $bills = Database::fetchAll(
            "SELECT pb.*,
                    (SELECT COUNT(*) FROM production_bill_items pbi WHERE pbi.bill_id = pb.id) AS line_count,
                    (SELECT COUNT(*) FROM production_batches pbat WHERE pbat.production_bill_id = pb.id) AS linked_batch_count,
                    (SELECT COUNT(*) FROM production_batches pbat WHERE pbat.production_bill_id = pb.id AND pbat.status = 'PREPARING') AS preparing_batch_count,
                    (SELECT COUNT(*) FROM production_batches pbat WHERE pbat.production_bill_id = pb.id AND pbat.status = 'APPROVED') AS approved_batch_count,
                    (SELECT COUNT(*) FROM production_batches pbat WHERE pbat.production_bill_id = pb.id AND pbat.status = 'DISPATCHED') AS dispatched_batch_count,
                    (SELECT COUNT(*) FROM production_batches pbat WHERE pbat.production_bill_id = pb.id AND pbat.status = 'RECEIVED') AS received_batch_count
             FROM production_bills pb
             {$whereSql}
             ORDER BY pb.bill_date DESC, pb.id DESC"
        );

        // Fetch counts for all tabs so badges show correct counts
        $baseWhere = "";
        if ($cid) {
            $baseWhere = ($cid === 1) ? "WHERE (concern_id = 1 OR concern_id IS NULL)" : "WHERE concern_id = " . (int)$cid;
        }
        $tabCounts = Database::fetchOne("
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 0 THEN 1 ELSE 0 END) AS normal_count,
                SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 0 AND buyer_type = 'CUSTOMER' THEN 1 ELSE 0 END) AS customer_count,
                SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 0 AND buyer_type = 'COMPANY' THEN 1 ELSE 0 END) AS company_count,
                SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 1 THEN 1 ELSE 0 END) AS direct_count,
                COALESCE(SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 0 THEN total_amount ELSE 0 END), 0) AS normal_revenue,
                COALESCE(SUM(CASE WHEN COALESCE(is_direct_bill, 0) = 1 THEN total_amount ELSE 0 END), 0) AS direct_revenue
            FROM production_bills
            {$baseWhere}
        ") ?: [];

        $stats = [
            'total_revenue'    => 0.0,
            'paid_revenue'     => 0.0,
            'pending_revenue'  => 0.0,
            'total_bills'      => count($bills),
            'unique_customers' => 0,
            'paid_count'       => 0,
            'pending_count'    => 0,
            'tab_counts'       => $tabCounts,
        ];

        $customerSet = [];
        foreach ($bills as $b) {
            $amount = (float)($b['total_amount'] ?? 0);
            $stats['total_revenue'] += $amount;
            if (($b['payment_status'] ?? '') === 'PAID') {
                $stats['paid_revenue'] += $amount;
                $stats['paid_count']++;
            } else {
                $stats['pending_revenue'] += $amount;
                $stats['pending_count']++;
            }
            $buyer = trim(($b['buyer_type'] ?? '') . '_' . (($b['buyer_type'] ?? '') === 'CUSTOMER' ? ($b['customer_name'] ?? '') : ($b['company_name'] ?? '')));
            if ($buyer !== '_' && $buyer !== '') {
                $customerSet[$buyer] = true;
            }
        }
        $stats['unique_customers'] = count($customerSet);

        Response::view('allmodules.billing.index', [
            'pageTitle' => 'Billing / Pos',
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Billing / Pos'],
            ],
            'bills' => $bills,
            'stats' => $stats,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
        ]);
    }

    public function createBill(): void
    {
        self::ensureSchema();
        $qcBatchId = !empty($_REQUEST['qc_batch_id']) ? (int)$_REQUEST['qc_batch_id'] : 0;
        $batchId = !empty($_REQUEST['batch_id']) ? (int)$_REQUEST['batch_id'] : 0;
        $selectedTags = !empty($_POST['selected_tags']) && is_array($_POST['selected_tags']) ? $_POST['selected_tags'] : [];
        if (empty($selectedTags) && !empty($_GET['tags'])) {
            $selectedTags = explode(',', (string)$_GET['tags']);
        }

        $preloadedItems = [];
        $overrideUnitId = null;

        if ($qcBatchId > 0) {
            $preloadedItems = $this->loadQCItemsForBilling($qcBatchId, $selectedTags);
            $qb = Database::fetchOne("SELECT pp.production_unit_id FROM qc_batches qb JOIN production_programs pp ON pp.id = qb.program_id WHERE qb.id = ?", [$qcBatchId]);
            if (!empty($qb['production_unit_id'])) {
                $overrideUnitId = (int)$qb['production_unit_id'];
            }
        } elseif ($batchId > 0) {
            $preloadedItems = $this->loadBatchItemsForBilling($batchId);
            $pb = Database::fetchOne("SELECT pp.production_unit_id FROM production_batches pb JOIN production_programs pp ON pp.id = pb.program_id WHERE pb.id = ?", [$batchId]);
            if (!empty($pb['production_unit_id'])) {
                $overrideUnitId = (int)$pb['production_unit_id'];
            }
        }

        $this->renderForm(null, $preloadedItems, $overrideUnitId);
    }

    public function editBill(string $id): void
    {
        $bill = $this->findBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }
        $linkedBatchCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [(int)$bill['id']])['total'] ?? 0);
        if ($linkedBatchCount > 0) {
            Response::redirect('production/billing', 'error', 'This bill cannot be edited because linked batch fulfillment has started.');
            return;
        }

        $this->renderForm($bill);
    }

    public function moveBillToBatchForm(string $id): void
    {
        if (!isAdmin() && !hasPermission('batches', 'create') && !hasPermission('production_billing', 'create')) {
            Response::error(403, 'You do not have permission to move a billed order to production batches.');
            return;
        }
        $bill = $this->findBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }
        if (($bill['payment_status'] ?? '') !== 'PAID') {
            Response::redirect('production/billing', 'error', 'Only paid bills can move to batch dispatch.');
            return;
        }
        $linkedBatchCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [(int)$bill['id']])['total'] ?? 0);
        if ($linkedBatchCount > 0) {
            Response::redirect('production/billing', 'error', 'This bill has already been moved to a production batch.');
            return;
        }

        $items = $this->loadBillItems((int)$bill['id']);

        $sourceConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$sourceConcernId && !empty($bill['production_unit_id'])) {
            $pu = Database::fetchOne("SELECT concern_id FROM production_units WHERE id = ?", [(int)$bill['production_unit_id']]);
            if (!empty($pu['concern_id'])) {
                $sourceConcernId = (int)$pu['concern_id'];
            }
        }

        // Auto-provision warehouse records for all active concerns if missing
        $activeConcerns = Database::fetchAll("SELECT id, name, code, address, phone, gst_number FROM concerns WHERE is_active = 1");
        foreach ($activeConcerns as $ac) {
            $existingWh = Database::fetchOne("SELECT id FROM warehouses WHERE concern_id = ?", [(int)$ac['id']]);
            if (!$existingWh) {
                $whCode = 'WH-' . $ac['code'];
                $suffix = 1;
                while (Database::fetchOne("SELECT id FROM warehouses WHERE code = ?", [$whCode])) {
                    $whCode = 'WH-' . $ac['code'] . '-' . (++$suffix);
                }
                try {
                    Database::execute(
                        "INSERT INTO warehouses (concern_id, name, code, address, phone, gst_number, is_active, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, 1, NOW())",
                        [(int)$ac['id'], $ac['name'] . ' (Warehouse)', $whCode, $ac['address'] ?: null, $ac['phone'] ?: null, $ac['gst_number'] ?: null]
                    );
                } catch (\Throwable $e) {}
            }
        }

        $whWhere = "WHERE w.is_active = 1";
        $whParams = [];
        if ($sourceConcernId) {
            // Only show OTHER concerns, never the current / same concern
            $whWhere .= " AND (w.concern_id != ? OR w.concern_id IS NULL)";
            $whParams[] = $sourceConcernId;
        }

        $warehouses = Database::fetchAll("
            SELECT w.*, c.name as concern_name, c.code as concern_code 
            FROM warehouses w 
            LEFT JOIN concerns c ON w.concern_id = c.id 
            {$whWhere} 
            ORDER BY COALESCE(c.name, w.name) ASC
        ", $whParams);

        Response::view('allmodules.billing.move_to_batch', [
            'pageTitle' => 'Move Bill to Batch',
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Billing / Pos', 'url' => url('production/billing')],
                ['label' => 'Move Billing ' . (!empty($bill['bill_number']) ? $bill['bill_number'] : '#' . (int)$bill['id']) . ' to Batch'],
            ],
            'bill' => $bill,
            'billItems' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function createBillDispatchBatch(string $id): void
    {
        if (!isAdmin() && !hasPermission('batches', 'create') && !hasPermission('production_billing', 'create')) {
            Response::error(403, 'You do not have permission to move a billed order to production batches.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::redirect('production/billing', 'error', 'Invalid security token.');
            return;
        }

        $bill = $this->findBill((int)$id);
        if (!$bill || ($bill['payment_status'] ?? '') !== 'PAID') {
            Response::redirect('production/billing', 'error', 'A valid paid bill is required for batch dispatch.');
            return;
        }
        $linkedBatchCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [(int)$bill['id']])['total'] ?? 0);
        if ($linkedBatchCount > 0) {
            Response::redirect('production/billing', 'error', 'This bill has already been moved to a production batch.');
            return;
        }

        $warehouseId = (int)($_POST['destination_warehouse_id'] ?? 0);
        $boxCount = filter_var($_POST['box_count'] ?? null, FILTER_VALIDATE_INT);
        $warehouse = $warehouseId > 0
            ? Database::fetchOne("SELECT id FROM warehouses WHERE id = ? AND is_active = 1", [$warehouseId])
            : null;
        if (!$warehouse || $boxCount === false || $boxCount < 1 || $boxCount > 10000) {
            Response::redirect('production/billing/move-to-batch/' . (int)$bill['id'], 'error', 'Select an active warehouse and enter a valid box count.');
            return;
        }

        $sourceRows = Database::fetchAll(
            "SELECT pbi.id AS bill_item_id, pbi.qc_item_id, pbi.variant_id, pbi.quantity,
                    COALESCE(qi.qc_batch_id, (SELECT qb2.id FROM qc_items qi2 JOIN qc_batches qb2 ON qi2.qc_batch_id = qb2.id WHERE qi2.variant_id = pbi.variant_id ORDER BY qb2.id DESC LIMIT 1), (SELECT id FROM qc_batches ORDER BY id DESC LIMIT 1)) AS qc_batch_id,
                    COALESCE(qb.program_id, (SELECT pp2.id FROM qc_items qi2 JOIN qc_batches qb2 ON qi2.qc_batch_id = qb2.id JOIN production_programs pp2 ON qb2.program_id = pp2.id WHERE qi2.variant_id = pbi.variant_id ORDER BY qb2.id DESC LIMIT 1), (SELECT program_id FROM qc_batches ORDER BY id DESC LIMIT 1), 1) AS program_id
             FROM production_bill_items pbi
             LEFT JOIN qc_items qi ON qi.id = pbi.qc_item_id
             LEFT JOIN qc_batches qb ON qb.id = qi.qc_batch_id
             WHERE pbi.bill_id = ?
             ORDER BY pbi.id",
            [(int)$bill['id']]
        );
        if (!$sourceRows) {
            Response::redirect('production/billing', 'error', 'This bill has no QC-linked items to pack.');
            return;
        }

        $billTags = Database::fetchAll(
            "SELECT bill_item_id, tag_code FROM production_bill_item_tags WHERE bill_id = ? ORDER BY id",
            [(int)$bill['id']]
        );
        $tagsByBillItem = [];
        foreach ($billTags as $tagRow) {
            $tagsByBillItem[(int)$tagRow['bill_item_id']][] = trim((string)$tagRow['tag_code']);
        }

        $groups = [];
        foreach ($sourceRows as $row) {
            $billItemId = (int)$row['bill_item_id'];
            $qcItemId = (int)$row['qc_item_id'];
            $qcBatchId = (int)$row['qc_batch_id'];
            $programId = (int)$row['program_id'];
            $variantId = (int)($row['variant_id'] ?? 0);
            $tags = array_values(array_unique(array_filter($tagsByBillItem[$billItemId] ?? [])));
            if ($variantId <= 0 || count($tags) !== (int)$row['quantity']) {
                Response::redirect('production/billing', 'error', 'A billed item is missing its variant or complete QR tag list.');
                return;
            }
            $groupKey = (string)$programId;
            $groups[$groupKey]['program_id'] = $programId;
            $groups[$groupKey]['qc_ids'][$qcBatchId] = $qcBatchId;
            $groups[$groupKey]['items'][] = [
                'qc_item_id' => $qcItemId,
                'variant_id' => $variantId,
                'quantity' => (int)$row['quantity'],
                'piece_tags' => $tags,
                'qc_batch_id' => $qcBatchId,
            ];
        }

        $allTags = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                foreach ($item['piece_tags'] as $tag) {
                    $normalized = strtoupper(trim($tag));
                    if ($normalized === '' || isset($allTags[$normalized])) {
                        Response::redirect('production/billing', 'error', 'Bill QR tags are empty or duplicated.');
                        return;
                    }
                    $allTags[$normalized] = true;
                }
            }
        }

        $warehouseRows = Database::fetchAll("SELECT qr_code FROM warehouse_qr_codes");
        $warehouseTags = array_fill_keys(array_map(static fn(array $row): string => strtoupper(trim((string)$row['qr_code'])), $warehouseRows), true);
        $approvedTagsByQc = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $qcBatchId = (int)$item['qc_batch_id'];
                if (!isset($approvedTagsByQc[$qcBatchId])) {
                    $approvedTagsByQc[$qcBatchId] = ProductionService::getAvailableQCPieceTags($qcBatchId);
                }
                $availableTags = array_fill_keys(array_map('strtoupper', $approvedTagsByQc[$qcBatchId][(int)$item['qc_item_id']] ?? []), true);
                foreach ($item['piece_tags'] as $tag) {
                    $normalized = strtoupper(trim($tag));
                    if (empty($availableTags[$normalized]) || isset($warehouseTags[$normalized])) {
                        Response::redirect('production/billing', 'error', "QR {$tag} is no longer available for dispatch or is already in warehouse stock.");
                        return;
                    }
                }
            }
        }

        $userId = (int)(Auth::id() ?? Session::userId() ?? 0);
        if ($userId <= 0) {
            Response::redirect('production/billing', 'error', 'Unable to identify the administrator creating these batches.');
            return;
        }

        Database::beginTransaction();
        try {
            Database::fetchOne("SELECT id FROM production_bills WHERE id = ? FOR UPDATE", [(int)$bill['id']]);
            $lockedBatchCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [(int)$bill['id']])['total'] ?? 0);
            if ($lockedBatchCount > 0) {
                Database::rollback();
                Response::redirect('production/billing', 'error', 'This bill has already been moved to a production batch.');
                return;
            }
            foreach ($groups as $group) {
                $qcIds = array_values($group['qc_ids']);
                $primaryQcId = array_shift($qcIds);
                $batchItems = array_map(static function (array $item): array {
                    unset($item['qc_batch_id']);
                    return $item;
                }, $group['items']);
                ProductionService::createProductionBatch([
                    'production_bill_id' => (int)$bill['id'],
                    'concern_id' => currentConcernId(),
                    'program_id' => (int)$group['program_id'],
                    'qc_batch_id' => $primaryQcId,
                    'additional_qc_batch_ids' => $qcIds,
                    'is_multi_qc' => !empty($qcIds) ? 1 : 0,
                    'destination_warehouse_id' => $warehouseId,
                    'box_count' => (int)$boxCount,
                    'status' => 'PREPARING',
                    'notes' => 'Customer dispatch for production bill ' . $bill['bill_number'],
                ], $batchItems, $userId);
            }
            Database::commit();
            Response::redirect('production/batches', 'success', 'Bill items packed into Batch (Status: PREPARING). Please approve and dispatch to move stock.');
        } catch (Throwable $e) {
            Database::rollback();
            error_log('ProductionBillingController::createBillDispatchBatch failed: ' . $e->getMessage());
            Response::redirect('production/billing/move-to-batch/' . (int)$bill['id'], 'error', 'Unable to create dispatch batches. Refresh and verify that the bill QR items are still available.');
        }
    }

    public function viewBill(string $id): void
    {
        $bill = $this->loadBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }

        Response::view('allmodules.billing.view', [
            'pageTitle' => 'Production Bill ' . $bill['bill_number'],
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Billing / Pos', 'url' => url('production/billing')],
                ['label' => 'Billing ' . (!empty($bill['bill_number']) ? $bill['bill_number'] : '#' . (int)$bill['id'])],
            ],
            'bill' => $bill,
            'billItems' => $this->loadBillItems((int)$bill['id']),
            'prodUnit' => ProductionUnit::getActiveFacility($bill['production_unit_id'] ?? null),
        ]);
    }

    public function printBill(string $id): void
    {
        $bill = $this->loadBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }

        Response::viewOnly('allmodules.billing.print', [
            'bill' => $bill,
            'billItems' => $this->loadBillItems((int)$bill['id']),
            'sellerCompany' => SettingsService::getGroup('company'),
            'prodUnit' => ProductionUnit::getActiveFacility($bill['production_unit_id'] ?? null),
        ]);
    }

    public function getBatchItemsJson(): void
    {
        $batchId = (int)($_GET['batch_id'] ?? 0);
        if ($batchId <= 0) {
            Response::json(['success' => false, 'message' => 'Invalid batch ID']);
            return;
        }
        $items = $this->loadBatchItemsForBilling($batchId);
        $batch = Database::fetchOne(
            "SELECT pb.*, 
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    w.name AS warehouse_name,
                    w.code AS warehouse_code,
                    c.name AS concern_name,
                    c.code AS concern_code,
                    c.address AS concern_address,
                    c.phone AS concern_phone,
                    c.email AS concern_email,
                    c.gst_number AS concern_gst
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN concerns c ON pb.concern_id = c.id OR w.concern_id = c.id
             WHERE pb.id = ?",
            [$batchId]
        );
        Response::json([
            'success' => true,
            'batch'   => $batch,
            'items'   => $items,
        ]);
    }

    public function searchInventory(): void
    {
        if (!hasPermission('production_billing', 'create') && !hasPermission('production_billing', 'edit')) {
            Response::error(403, 'You do not have permission to search Production Billing inventory.');
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        if ($query === '') {
            Response::jsonSuccess('Inventory items loaded.', ['items' => []]);
            return;
        }

        $billId = max(0, (int)($_GET['bill_id'] ?? 0));
        $productionUnitId = max(0, (int)($_GET['production_unit_id'] ?? 0));
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId !== null) {
            $productionUnitId = (int)$scopedUnitId;
        }
        $needle = strtoupper($query);

        $billedTagParams = [$needle];
        $billExclusion = '';
        if ($billId > 0) {
            $billExclusion = ' AND bill_id <> ?';
            $billedTagParams[] = $billId;
        }
        $billedTag = Database::fetchOne(
            "SELECT id FROM production_bill_item_tags
             WHERE UPPER(TRIM(tag_code)) = ?{$billExclusion}
             LIMIT 1",
            $billedTagParams
        );
        if ($billedTag) {
            Response::jsonSuccess('QR item already billed.', [
                'items' => [],
                'stock_limit_reached' => true,
                'message' => 'This QR item has already been billed and is no longer available.',
            ]);
            return;
        }

        // Check if scanned tag / QR matches a quarantined fabric roll in Damages
        $damagedMatch = Database::fetchOne(
            "SELECT rd.damage_number, rd.damage_reason, rd.qr_code, rd.damaged_weight, rd.status,
                    sr.roll_number
             FROM roll_damages rd
             LEFT JOIN seller_rolls sr ON sr.id = rd.seller_roll_id
             WHERE (UPPER(TRIM(rd.qr_code)) = ? OR UPPER(TRIM(sr.roll_number)) = ? OR UPPER(TRIM(sr.qr_code)) = ?)
               AND rd.status NOT IN ('CANCELLED', 'REJECTED')
               AND (rd.item_type IS NULL OR rd.item_type != 'PRODUCT' OR rd.variant_id IS NULL)",
            [$needle, $needle, $needle]
        );

        if ($damagedMatch) {
            $dmgQr = !empty($damagedMatch['qr_code']) ? $damagedMatch['qr_code'] : $damagedMatch['roll_number'];
            Response::jsonSuccess('Damaged item quarantined.', [
                'items'         => [],
                'is_damaged'    => true,
                'damage_number' => $damagedMatch['damage_number'],
                'damage_reason' => $damagedMatch['damage_reason'],
                'qr_code'       => $dmgQr,
                'message'       => "Cannot bill QR item '{$dmgQr}'! This item is quarantined in the Damages module (Damage #{$damagedMatch['damage_number']} — {$damagedMatch['damage_reason']}). Damaged items cannot be sold or billed!"
            ]);
            return;
        }

        // Check if scanned tag matches Warehouse Damages
        $whDamageMatch = Database::fetchOne(
            "SELECT wd.damage_number, wd.damage_reason, wd.tag_code, w.name as warehouse_name
             FROM warehouse_damages wd
             LEFT JOIN warehouses w ON w.id = wd.warehouse_id
             WHERE UPPER(TRIM(wd.tag_code)) = ?
               AND wd.status NOT IN ('SALVAGED', 'REPAIRED')",
            [$needle]
        );
        if ($whDamageMatch) {
            Response::jsonSuccess('Damaged item in warehouse.', [
                'items'         => [],
                'is_damaged'    => true,
                'damage_number' => $whDamageMatch['damage_number'],
                'damage_reason' => $whDamageMatch['damage_reason'],
                'qr_code'       => $needle,
                'message'       => "Cannot bill QR item '{$needle}'! This item is quarantined in the Warehouse Damage module (Damage #{$whDamageMatch['damage_number']} — {$whDamageMatch['damage_reason']}). Damaged items cannot be sold or billed!"
            ]);
            return;
        }

        $items = $this->getAvailableInventory($billId, $productionUnitId);
        $exactAvailableItems = [];
        $cleanNeedle = ltrim($needle, '#');
        $cleanNum = preg_replace('/^V0*|^#0*/i', '', $needle);
        $needleNum = (int)preg_replace('/[^0-9]/', '', $needle);
        $needleIsVariantCode = (bool)preg_match('/^V0*([0-9]+)$/i', $needle);

        foreach ($items as $item) {
            $tagsUpper = array_map('strtoupper', array_map('trim', $item['available_tags']));
            $vCodeUpper = strtoupper(trim((string)($item['variant_code'] ?? '')));
            $vId = (int)($item['variant_id'] ?? 0);
            $vIdStr = (string)$vId;
            $formattedVCode = $vId > 0 ? sprintf('V%04d', $vId) : '';
            $shortVCode = $vId > 0 ? ('V' . $vId) : '';

            // Load any explicit variant_code or qr_code from product_variants
            $vDbCode = '';
            $vDbQr = '';
            if ($vId > 0) {
                $vMetaRow = Database::fetchOne("SELECT variant_code, qr_code FROM product_variants WHERE id = ?", [$vId]);
                if ($vMetaRow) {
                    $vDbCode = strtoupper(trim((string)($vMetaRow['variant_code'] ?? '')));
                    $vDbQr = strtoupper(trim((string)($vMetaRow['qr_code'] ?? '')));
                }
            }

            $tagPrefixMatch = false;
            if (!empty($tagsUpper)) {
                foreach ($tagsUpper as $t) {
                    if (str_starts_with($t, $needle . '-') || str_starts_with($t, $needle . '_')) {
                        $tagPrefixMatch = true;
                        break;
                    }
                }
            }

            $explicitCodeCandidates = array_values(array_filter([$vCodeUpper, $vDbCode, $vDbQr], static fn(string $c): bool => $c !== ''));
            $hasExplicitVariantCode = !empty($explicitCodeCandidates);
            $matchesExplicitVariantCode = false;
            foreach ($explicitCodeCandidates as $codeCandidate) {
                if ($codeCandidate === $needle || ('V' . $codeCandidate) === $needle || $codeCandidate === ('V' . $needle)) {
                    $matchesExplicitVariantCode = true;
                    break;
                }
            }
            $matchesSyntheticVCode = !$hasExplicitVariantCode
                && (($formattedVCode !== '' && $formattedVCode === $needle) || ($shortVCode !== '' && $shortVCode === $needle));
            $matchesNumericId = !$needleIsVariantCode
                && $vIdStr !== '0'
                && ($vIdStr === $needle || $vIdStr === $cleanNeedle || ('#' . $vIdStr) === $needle || (is_numeric($cleanNum) && (int)$cleanNum === $vId));

            $isExactMatch = in_array($needle, $tagsUpper, true)
                || $matchesExplicitVariantCode
                || $matchesSyntheticVCode
                || $matchesNumericId
                || $tagPrefixMatch;

            if ($isExactMatch) {
                $matchedTag = in_array($needle, $tagsUpper, true) ? $needle : ($item['available_tags'][0] ?? $needle);
                $item['matched_tag'] = $matchedTag;
                $otherTags = array_values(array_filter(
                    $item['available_tags'],
                    static fn(string $tag): bool => strtoupper(trim($tag)) !== strtoupper(trim($matchedTag))
                ));
                $item['available_tags'] = array_merge([$matchedTag], $otherTags);
                $exactAvailableItems[] = $item;
            }
        }
        // Helper to cross-check scanned QR price against active catalog prices and price revision logs
        $checkPriceForItems = static function(array &$itemList, string $scannedQuery) {
            Database::ensureColumn('product_price_logs', 'dont_affect_old_qr', 'TINYINT(1) DEFAULT 0');

            foreach ($itemList as &$it) {
                $tagCode = $it['matched_tag'] ?? $scannedQuery;
                $vId = (int)($it['variant_id'] ?? 0);

                // 1. Check if the printed QR code has a recorded stamped price
                $qrItem = Database::fetchOne(
                    "SELECT qri.mrp, qri.selling_price 
                     FROM qr_generation_items qri 
                     WHERE UPPER(TRIM(qri.qr_code)) = ? LIMIT 1",
                    [strtoupper(trim($tagCode))]
                );

                $priceLog = null;
                if ($vId > 0) {
                    $priceLog = Database::fetchOne(
                        "SELECT * 
                         FROM product_price_logs 
                         WHERE variant_id = ? 
                         ORDER BY id DESC LIMIT 1",
                        [$vId]
                    );
                }

                $catalogPrice = (float)($it['unit_price'] ?? 0);
                $catalogMrp = (float)($it['mrp'] ?? 0);

                if ($qrItem && !empty($qrItem['selling_price'])) {
                    $printedPrice = (float)$qrItem['selling_price'];
                    $printedMrp = (float)($qrItem['mrp'] ?? 0);

                    if (abs($printedPrice - $catalogPrice) > 0.01) {
                        $it['price_edited'] = true;
                        $it['printed_price'] = $printedPrice;
                        $it['printed_mrp'] = $printedMrp;
                        $it['current_catalog_price'] = $catalogPrice;
                        $it['current_catalog_mrp'] = $catalogMrp;

                        $isProtected = $priceLog && !empty($priceLog['dont_affect_old_qr']);
                        if ($isProtected) {
                            $it['unit_price'] = $printedPrice;
                            $it['price_alert'] = "Printed Tag Honored: ₹" . number_format($printedPrice, 2) . " (Current Catalog: ₹" . number_format($catalogPrice, 2) . ")";
                        } else {
                            $it['unit_price'] = $catalogPrice;
                            $it['price_alert'] = "Price Updated: Tag was ₹" . number_format($printedPrice, 2) . " -> Billed at Updated ₹" . number_format($catalogPrice, 2);
                        }
                    }
                } elseif ($priceLog) {
                    $oldLogPrice = (float)$priceLog['old_selling_price'];
                    $newLogPrice = (float)$priceLog['new_selling_price'];
                    if (abs($oldLogPrice - $newLogPrice) > 0.01) {
                        $it['price_edited'] = true;
                        $it['printed_price'] = $oldLogPrice;
                        $it['printed_mrp'] = (float)$priceLog['old_mrp'];
                        $it['current_catalog_price'] = $newLogPrice;
                        $it['current_catalog_mrp'] = (float)$priceLog['new_mrp'];
                        if (!empty($priceLog['dont_affect_old_qr'])) {
                            $it['unit_price'] = $oldLogPrice;
                            $it['price_alert'] = "Printed Tag Honored: ₹" . number_format($oldLogPrice, 2) . " (Active Catalog: ₹" . number_format($newLogPrice, 2) . ")";
                        } else {
                            $it['unit_price'] = $newLogPrice;
                            $it['price_alert'] = "Price Updated: Tag was ₹" . number_format($oldLogPrice, 2) . " -> Billed at Updated ₹" . number_format($newLogPrice, 2);
                        }
                    }
                }
            }
            unset($it);
        };

        if ($exactAvailableItems) {
            $checkPriceForItems($exactAvailableItems, $needle);
            Response::jsonSuccess('Available finished-goods QR found.', ['items' => $exactAvailableItems]);
            return;
        }

        // Check if scanned input matches a Variant QR Code, Variant Code, or Variant ID
        $cleanId = preg_replace('/[^0-9]/', '', $needle);
        $cleanNeedleNum = (int)$cleanId;
        // Only treat as primary key ID if needle explicitly starts with '#' or is purely numeric
        $isPureIdLookup = (str_starts_with($needle, '#') || (is_numeric($cleanNeedle) && !str_starts_with($needle, 'V')));
        $variantIdParam = ($isPureIdLookup && $cleanNeedleNum > 0) ? $cleanNeedleNum : 0;
        $vPadded = $cleanNeedleNum > 0 ? sprintf('V%04d', $cleanNeedleNum) : $needle;
        $vShort = $cleanNeedleNum > 0 ? ('V' . $cleanNeedleNum) : $needle;

        $variantMatch = Database::fetchOne(
            "SELECT pv.*, p.name as product_name, p.hsn_code as prod_hsn, p.gst_percentage as prod_gst,
                    p.is_complementary as prod_is_comp,
                    c.name as category_name, sc.name as subcategory_name,
                    col.name as color_name, d.name as design_name, sz.name as size_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE UPPER(TRIM(pv.variant_code)) = ? 
                OR UPPER(TRIM(pv.qr_code)) = ? 
                OR UPPER(TRIM(pv.variant_code)) = ?
                OR UPPER(TRIM(pv.variant_code)) = ?
                OR UPPER(TRIM(pv.qr_code)) = ?
                OR (? > 0 AND pv.id = ?)
             ORDER BY 
                CASE 
                    WHEN UPPER(TRIM(pv.variant_code)) = ? THEN 1
                    WHEN UPPER(TRIM(pv.qr_code)) = ? THEN 2
                    WHEN UPPER(TRIM(pv.variant_code)) = ? THEN 3
                    WHEN pv.id = ? THEN 4
                    ELSE 5
                END ASC
             LIMIT 1",
            [
                $needle, $needle, $vPadded, $vShort, $vPadded,
                $variantIdParam, $variantIdParam,
                $needle, $needle, $vPadded,
                $variantIdParam
            ]
        );

        if ($variantMatch) {
            if ((int)$variantMatch['is_active'] !== 1) {
                Response::jsonSuccess('Variant is inactive.', [
                    'items'   => [],
                    'message' => "Cannot bill Variant '{$variantMatch['variant_code']}'! This variant is currently inactive in the system."
                ]);
                return;
            }

            $varId = (int)$variantMatch['id'];
            $varCode = strtoupper(trim((string)($variantMatch['variant_code'] ?: sprintf('V%04d', $varId))));

            // Find matching item in available inventory from QC batches
            $variantItems = [];
            foreach ($items as $item) {
                $iVarId = (int)($item['variant_id'] ?? 0);
                $iVarCode = strtoupper(trim((string)($item['variant_code'] ?? '')));
                $iVarPadded = ($iVarCode === '' && $iVarId > 0) ? sprintf('V%04d', $iVarId) : '';
                $itemTagsUpper = array_map('strtoupper', array_map('trim', $item['available_tags']));
                if ($iVarId === $varId
                    || ($iVarCode !== '' && ($iVarCode === $varCode || $iVarCode === $needle))
                    || ($iVarPadded !== '' && $iVarPadded === $varCode)
                    || in_array($needle, $itemTagsUpper, true)
                    || in_array($varCode, $itemTagsUpper, true)) {
                    $variantItems[] = $item;
                }
            }

            if ($variantItems) {
                $checkPriceForItems($variantItems, $needle);
                Response::jsonSuccess('Variant QR item found.', ['items' => $variantItems]);
                return;
            }

            // Direct fallback: check QC items belonging to THIS product & variant across active batches
            $directQcRows = Database::fetchAll("
                SELECT qi.id as qc_item_id, qi.qc_batch_id, qi.variant_id, qi.size_name, qi.color_name,
                       qi.base_price, qi.discount_price, qi.passed_qty,
                       pp.program_number, pp.name as program_name,
                       p.name as product_name, p.code as product_code, p.gst_percentage, p.hsn_code,
                       p.is_complementary as prod_is_comp,
                       pv.mrp as variant_mrp, pv.selling_price, pv.variant_code
                FROM qc_items qi
                JOIN qc_batches qb ON qb.id = qi.qc_batch_id
                JOIN production_programs pp ON pp.id = qb.program_id
                JOIN products p ON p.id = pp.product_id
                JOIN product_variants pv ON pv.id = ?
                WHERE pp.product_id = pv.product_id
                  AND (
                        qi.variant_id = ?
                        OR (
                            (qi.variant_id IS NULL OR qi.variant_id = 0)
                            AND (? = '' OR UPPER(TRIM(COALESCE(qi.size_name, ''))) = UPPER(TRIM(?)))
                            AND (? = '' OR UPPER(TRIM(COALESCE(qi.color_name, ''))) = UPPER(TRIM(?)))
                        )
                  )
                  AND qi.passed_qty > 0
                ORDER BY qb.id DESC
            ", [
                $varId,
                $varId,
                (string)($variantMatch['size_name'] ?? ''),
                (string)($variantMatch['size_name'] ?? ''),
                (string)($variantMatch['color_name'] ?? ''),
                (string)($variantMatch['color_name'] ?? ''),
            ]);

            if (!empty($directQcRows)) {
                $soldFallbackRows = Database::fetchAll(
                    $billId > 0
                        ? "SELECT tag_code FROM production_bill_item_tags WHERE bill_id <> ?"
                        : "SELECT tag_code FROM production_bill_item_tags",
                    $billId > 0 ? [$billId] : []
                );
                $soldFallbackMap = array_fill_keys(array_map(
                    static fn(array $r): string => strtoupper(trim((string)$r['tag_code'])),
                    $soldFallbackRows
                ), true);

                foreach ($directQcRows as $dRow) {
                    $mrp = max(0.0, (float)($dRow['variant_mrp'] ?: ($dRow['base_price'] ?: $variantMatch['mrp'])));
                    $sellingPrice = max(0.0, (float)($dRow['selling_price'] ?: ($dRow['discount_price'] ?: $variantMatch['selling_price'])));
                    if ($sellingPrice <= 0) $sellingPrice = $mrp;

                    $genTagRows = Database::fetchAll("
                        SELECT qri.qr_code
                        FROM qr_generation_items qri
                        JOIN qr_generations qrg ON qrg.id = qri.generation_id
                        WHERE (qrg.qc_batch_id = ?
                               OR qrg.id = (SELECT qr_generation_id FROM qc_batches WHERE id = ?)
                               OR (qrg.stitching_batch_id > 0 AND qrg.stitching_batch_id = (SELECT stitching_batch_id FROM qc_batches WHERE id = ?)))
                          AND (qri.variant_id = ? OR (? = 0 AND UPPER(TRIM(COALESCE(qri.size_name, ''))) = UPPER(TRIM(COALESCE(?, '')))))
                        ORDER BY qri.sequence_num ASC
                    ", [(int)$dRow['qc_batch_id'], (int)$dRow['qc_batch_id'], (int)$dRow['qc_batch_id'], $varId, $varId, (string)($dRow['size_name'] ?? '')]);

                    $vTags = [];
                    foreach ($genTagRows as $gtRow) {
                        $cand = trim((string)($gtRow['qr_code'] ?? ''));
                        if ($cand !== '' && !isset($soldFallbackMap[strtoupper($cand)])) {
                            $vTags[] = $cand;
                        }
                    }
                    if (empty($vTags)) {
                        $qCount = max(1, (int)$dRow['passed_qty']);
                        for ($t = 1; $t <= $qCount; $t++) {
                            $cand = ($varCode ?: 'V' . $varId) . '-' . sprintf('%03d', $t);
                            if (!isset($soldFallbackMap[strtoupper($cand)])) {
                                $vTags[] = $cand;
                            }
                        }
                    }
                    if (empty($vTags)) {
                        continue;
                    }

                    $variantItems[] = [
                        'qc_item_id'       => (int)$dRow['qc_item_id'],
                        'qc_batch_id'      => (int)$dRow['qc_batch_id'],
                        'variant_id'       => $varId,
                        'product_name'     => (string)$dRow['product_name'],
                        'program_number'   => (string)$dRow['program_number'],
                        'sku'              => (string)($dRow['sku'] ?: $dRow['product_code']),
                        'variant_code'     => $varCode,
                        'size_name'        => (string)($dRow['size_name'] ?: ($variantMatch['size_name'] ?? 'STD')),
                        'color_name'       => (string)($dRow['color_name'] ?: ($variantMatch['color_name'] ?? 'STD')),
                        'mrp'              => $mrp,
                        'unit_price'       => $sellingPrice,
                        'is_complementary' => !empty($dRow['prod_is_comp']) ? 1 : 0,
                        'gst_percentage'   => max(0, (float)($dRow['gst_percentage'] ?? 0)),
                        'hsn_code'         => (string)($dRow['hsn_code'] ?? '6109'),
                        'available_tags'   => $vTags,
                    ];
                }
                if (!empty($variantItems)) {
                    $checkPriceForItems($variantItems, $needle);
                    Response::jsonSuccess('Variant QC inventory found.', ['items' => $variantItems]);
                    return;
                }
            }

            // Fallback: check available stock balances across facilities
            $balance = Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) AS quantity
                 FROM stock_balances
                 WHERE variant_id = ? AND stock_state = 'AVAILABLE'",
                [$varId]
            );
            $availableQty = (float)($balance['quantity'] ?? 0);

            if ($availableQty > 0) {
                $mrp = max(0.0, (float)$variantMatch['mrp']);
                $sellingPrice = max(0.0, (float)$variantMatch['selling_price']);
                if ($sellingPrice <= 0) $sellingPrice = $mrp;

                $isComp = (!empty($variantMatch['is_complementary']) || !empty($variantMatch['prod_is_comp']));
                if ($isComp) {
                    $sellingPrice = 0.00;
                }

                $soldBalRows = Database::fetchAll(
                    $billId > 0
                        ? "SELECT tag_code FROM production_bill_item_tags WHERE bill_id <> ?"
                        : "SELECT tag_code FROM production_bill_item_tags",
                    $billId > 0 ? [$billId] : []
                );
                $soldBalMap = array_fill_keys(array_map(
                    static fn(array $r): string => strtoupper(trim((string)$r['tag_code'])),
                    $soldBalRows
                ), true);

                $qrBalRows = Database::fetchAll(
                    "SELECT qr_code FROM qr_generation_items WHERE variant_id = ? ORDER BY sequence_num ASC, id ASC",
                    [$varId]
                );
                $vCode = $variantMatch['variant_code'] ?: ('V' . str_pad((string)$varId, 4, '0', STR_PAD_LEFT));
                $vTags = [];
                foreach ($qrBalRows as $qbRow) {
                    $cand = trim((string)($qbRow['qr_code'] ?? ''));
                    if ($cand !== '' && !isset($soldBalMap[strtoupper($cand)])) {
                        $vTags[] = $cand;
                        if (count($vTags) >= (int)$availableQty) {
                            break;
                        }
                    }
                }
                for ($t = 1; count($vTags) < (int)$availableQty && $t <= ((int)$availableQty * 2 + 20); $t++) {
                    $cand = $vCode . '-' . sprintf('%03d', $t);
                    if (!isset($soldBalMap[strtoupper($cand)]) && !in_array($cand, $vTags, true)) {
                        $vTags[] = $cand;
                    }
                }
                $variantItems = [
                    [
                        'qc_item_id' => 0,
                        'qc_batch_id' => 0,
                        'variant_id' => $varId,
                        'product_name' => (string)$variantMatch['product_name'],
                        'program_number' => '',
                        'sku' => '',
                        'variant_code' => (string)$variantMatch['variant_code'],
                        'size_name' => (string)($variantMatch['size_name'] ?? 'STD'),
                        'color_name' => (string)($variantMatch['color_name'] ?? 'STD'),
                        'mrp' => $mrp,
                        'unit_price' => $sellingPrice,
                        'is_complementary' => $isComp ? 1 : 0,
                        'gst_percentage' => max(0, (float)($variantMatch['gst_rate'] ?? $variantMatch['prod_gst'] ?? 0)),
                        'hsn_code' => (string)($variantMatch['hsn_code'] ?? $variantMatch['prod_hsn'] ?? '6109'),
                        'available_tags' => $vTags,
                    ]
                ];
                $checkPriceForItems($variantItems, $needle);
                Response::jsonSuccess('Variant stock found.', ['items' => $variantItems]);
                return;
            }

            Response::jsonSuccess('No available variant stock.', [
                'items' => [],
                'stock_limit_reached' => true,
                'message' => 'No available stock remains for this product variant at the selected facility.',
            ]);
            return;
        }

        // Check if scanned QR / roll has already been outwarded to warehouse
        $outwardedRoll = null;
        if (preg_match('/^([A-Z]{2}\d{4})(\d+)$/', $needle, $m)) {
            $pfx = $m[1];
            $seq = (int)$m[2];
            $outwardedRoll = Database::fetchOne(
                "SELECT po.outward_number, po.dispatch_date, pb.batch_number, w.name as warehouse_name, sr.roll_number
                 FROM seller_rolls sr
                 JOIN production_program_rolls ppr ON ppr.seller_roll_id = sr.id
                 JOIN production_programs pp ON pp.id = ppr.production_program_id
                 JOIN production_batches pb ON pb.program_id = pp.id
                 JOIN production_outwards po ON po.production_batch_id = pb.id
                 LEFT JOIN warehouses w ON (w.id = po.warehouse_id OR w.id = pb.destination_warehouse_id)
                 WHERE sr.start_code LIKE ? AND sr.end_code LIKE ?
                   AND ? >= sr.start_seq AND ? <= sr.end_seq
                   AND (po.status IN ('DISPATCHED', 'RECEIVED') OR pb.status IN ('DISPATCHED', 'RECEIVED'))
                 LIMIT 1",
                [$pfx . '%', $pfx . '%', $seq, $seq]
            );
        }
        if (!$outwardedRoll) {
            $outwardedRoll = Database::fetchOne(
                "SELECT po.outward_number, po.dispatch_date, pb.batch_number, w.name as warehouse_name, sr.roll_number
                 FROM seller_rolls sr
                 JOIN production_program_rolls ppr ON ppr.seller_roll_id = sr.id
                 JOIN production_programs pp ON pp.id = ppr.production_program_id
                 JOIN production_batches pb ON pb.program_id = pp.id
                 JOIN production_outwards po ON po.production_batch_id = pb.id
                 LEFT JOIN warehouses w ON (w.id = po.warehouse_id OR w.id = pb.destination_warehouse_id)
                 WHERE (UPPER(TRIM(sr.roll_number)) = ? OR UPPER(TRIM(sr.start_code)) = ? OR UPPER(TRIM(sr.qr_code)) = ?)
                   AND (po.status IN ('DISPATCHED', 'RECEIVED') OR pb.status IN ('DISPATCHED', 'RECEIVED'))
                 LIMIT 1",
                [$needle, $needle, $needle]
            );
        }

        $outwardedBatchTag = Database::fetchOne(
            "SELECT po.outward_number, po.dispatch_date, pb.batch_number, w.name as warehouse_name
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             JOIN production_outwards po ON po.production_batch_id = pb.id
             LEFT JOIN warehouses w ON (w.id = po.warehouse_id OR w.id = pb.destination_warehouse_id)
             WHERE (pbi.piece_tags LIKE ? OR pbi.received_qr_codes LIKE ? OR UPPER(TRIM(pb.batch_number)) = ?)
               AND (po.status IN ('DISPATCHED', 'RECEIVED') OR pb.status IN ('DISPATCHED', 'RECEIVED'))
             LIMIT 1",
            ['%' . $needle . '%', '%' . $needle . '%', $needle]
        );

        $warehouseQr = Database::fetchOne(
            "SELECT wq.qr_code, w.name as warehouse_name
             FROM warehouse_qr_codes wq
             LEFT JOIN warehouses w ON w.id = wq.warehouse_id
             WHERE UPPER(TRIM(wq.qr_code)) = ?
             LIMIT 1",
            [$needle]
        );

        $outwardMatch = $outwardedRoll ?: ($outwardedBatchTag ?: null);
        if ($outwardMatch || $warehouseQr) {
            $outNum = $outwardMatch['outward_number'] ?? 'OUTWARD';
            $whName = $outwardMatch['warehouse_name'] ?? ($warehouseQr['warehouse_name'] ?? 'Warehouse');
            Response::jsonSuccess('Product already outwarded.', [
                'items'          => [],
                'is_outwarded'   => true,
                'outward_number' => $outNum,
                'warehouse_name' => $whName,
                'qr_code'        => $needle,
                'message'        => "Cannot bill QR item '{$needle}'! This product/roll has already been outwarded to the warehouse ({$whName} under Outward #{$outNum}). Outwarded products cannot be billed in production!"
            ]);
            return;
        }

        $matches = [];

        foreach ($items as $item) {
            $matchingTags = array_values(array_filter(
                $item['available_tags'],
                static fn(string $tag): bool => str_contains(strtoupper($tag), $needle)
            ));
            $vCodeUpper = strtoupper(trim((string)($item['variant_code'] ?? '')));
            $vId = (int)($item['variant_id'] ?? 0);
            $formattedVCode = ($vCodeUpper === '' && $vId > 0) ? sprintf('V%04d', $vId) : '';
            $shortVCode = ($vCodeUpper === '' && $vId > 0) ? ('V' . $vId) : '';

            $metadataMatch = stripos($item['product_name'], $query) !== false
                || stripos((string)$item['sku'], $query) !== false
                || stripos((string)$item['size_name'], $query) !== false
                || stripos((string)$item['color_name'], $query) !== false
                || stripos((string)$item['program_number'], $query) !== false
                || ($vCodeUpper !== '' && stripos($vCodeUpper, $needle) !== false)
                || ($formattedVCode !== '' && stripos($formattedVCode, $needle) !== false)
                || ($shortVCode !== '' && stripos($shortVCode, $needle) !== false)
                || (!$needleIsVariantCode && $vId > 0 && ($needleNum === $vId || (string)$vId === $cleanNeedle || ('#' . $vId) === $needle || (is_numeric($cleanNum) && (int)$cleanNum === $vId)));

            if ($metadataMatch || $matchingTags) {
                if ($matchingTags) {
                    $item['matched_tag'] = $matchingTags[0];
                    $matchingUpper = array_map('strtoupper', $matchingTags);
                    $otherTags = array_values(array_filter(
                        $item['available_tags'],
                        static fn(string $tag): bool => !in_array(strtoupper($tag), $matchingUpper, true)
                    ));
                    $item['available_tags'] = array_merge($matchingTags, $otherTags);
                }
                $matches[] = $item;
                if (count($matches) >= 20) {
                    break;
                }
            }
        }

        if (!$matches && preg_match('/^([A-Z]{2}\d{4})(\d+)$/', $needle, $fabricCode)) {
            $fabricRoll = Database::fetchOne(
                "SELECT roll_number FROM seller_rolls
                 WHERE UPPER(LEFT(start_code, 6)) = ? AND UPPER(LEFT(end_code, 6)) = ?
                   AND ? BETWEEN start_seq AND end_seq
                 LIMIT 1",
                [$fabricCode[1], $fabricCode[1], (int)$fabricCode[2]]
            );
            if ($fabricRoll) {
                Response::jsonSuccess('Fabric roll QR is not billable finished inventory.', [
                    'items' => [],
                    'is_fabric_roll' => true,
                    'message' => "QR {$needle} is a purchased fabric-roll label ({$fabricRoll['roll_number']}). Production billing accepts only available finished-goods QR labels that passed QC; use the fabric roll in production first.",
                ]);
                return;
            }
        }

        Response::jsonSuccess('Inventory items loaded.', ['items' => $matches]);
    }

    /**
     * Live Customer Auto-Suggest by Phone Number or Name
     */
    public function searchCustomer(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            Response::jsonSuccess('Matches', ['customers' => []]);
            return;
        }

        $wild = '%' . $query . '%';
        $sql = "SELECT 
                    COALESCE(NULLIF(customer_phone, ''), company_phone) AS phone,
                    COALESCE(NULLIF(customer_name, ''), company_name) AS name,
                    COALESCE(NULLIF(customer_address, ''), company_address) AS address,
                    customer_city,
                    customer_state,
                    COALESCE(NULLIF(customer_gstin, ''), company_gstin) AS gstin,
                    buyer_type,
                    COUNT(id) AS purchase_count,
                    COALESCE(SUM(total_amount), 0) AS total_spent,
                    MAX(bill_date) AS last_purchase_date,
                    MAX(id) AS last_bill_id
                FROM production_bills
                WHERE (customer_phone LIKE ? OR company_phone LIKE ? OR customer_name LIKE ? OR company_name LIKE ?)
                  AND (customer_phone IS NOT NULL AND customer_phone <> '' OR company_phone IS NOT NULL AND company_phone <> '')
                GROUP BY phone
                ORDER BY purchase_count DESC, last_bill_id DESC
                LIMIT 8";

        $customers = Database::fetchAll($sql, [$wild, $wild, $wild, $wild]);
        Response::jsonSuccess('Matches', ['customers' => $customers]);
    }

    /**
     * Customer Purchase History & Products Log
     */
    public function customerHistory(): void
    {
        $phone = trim((string)($_GET['phone'] ?? ''));
        if ($phone === '') {
            Response::jsonError('Customer phone is required.', 400);
            return;
        }

        $bills = Database::fetchAll(
            "SELECT id, bill_number, bill_date, buyer_type, customer_name, company_name, 
                    payment_method, total_amount, staff_name, created_at
             FROM production_bills 
             WHERE customer_phone = ? OR company_phone = ? 
             ORDER BY bill_date DESC, id DESC LIMIT 15",
            [$phone, $phone]
        );

        foreach ($bills as &$b) {
            $b['items'] = Database::fetchAll(
                "SELECT pbi.*, 
                        (SELECT GROUP_CONCAT(tag_code SEPARATOR ', ') FROM production_bill_item_tags WHERE bill_item_id = pbi.id) AS tags_csv
                 FROM production_bill_items pbi 
                 WHERE pbi.bill_id = ?
                 ORDER BY pbi.id ASC",
                [$b['id']]
            );
        }
        unset($b);

        $summary = Database::fetchOne(
            "SELECT 
                COALESCE(NULLIF(customer_name, ''), company_name) AS name,
                COALESCE(NULLIF(customer_phone, ''), company_phone) AS phone,
                COALESCE(NULLIF(customer_address, ''), company_address) AS address,
                customer_city,
                customer_state,
                COUNT(id) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS lifetime_spent,
                MAX(bill_date) AS last_order_date
             FROM production_bills
             WHERE customer_phone = ? OR company_phone = ?",
            [$phone, $phone]
        );

        Response::jsonSuccess('Customer History', [
            'summary' => $summary,
            'bills'   => $bills,
        ]);
    }

    public function storeBill(): void
    {
        $this->persistBill(null);
    }

    public function updateBill(string $id): void
    {
        $billId = (int)$id;
        if (!$this->findBill($billId)) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }
        $linkedBatchCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [$billId])['total'] ?? 0);
        if ($linkedBatchCount > 0) {
            Response::redirect('production/billing', 'error', 'This bill cannot be edited because linked batch fulfillment has started.');
            return;
        }
        $this->persistBill($billId);
    }

    public function deleteBill(string $id): void
    {
        $billId = (int)$id;
        $linkedBatches = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM production_batches WHERE production_bill_id = ?", [$billId])['total'] ?? 0);
        if ($linkedBatches > 0) {
            Response::redirect('production/billing', 'error', 'This bill cannot be deleted because it has linked batch or outward records.');
            return;
        }
        try {
            Database::execute("DELETE FROM production_bills WHERE id = ?", [$billId]);
            Response::redirect('production/billing', 'success', 'Production bill deleted.');
        } catch (Throwable $e) {
            error_log('ProductionBillingController::deleteBill failed: ' . $e->getMessage());
            Response::redirect('production/billing', 'error', 'Unable to delete this bill.');
        }
    }

    private function renderForm(?array $bill, array $preloadedItems = [], ?int $overrideUnitId = null): void
    {
        $billItems = $bill ? $this->loadBillItems((int)$bill['id']) : $preloadedItems;
        $activeConcern = function_exists('currentConcern') ? currentConcern() : null;
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;

        // Fetch production units scoped to active concern
        $productionUnits = [];
        if ($cid !== null) {
            $productionUnits = Database::fetchAll(
                "SELECT * FROM production_units WHERE is_active = 1 AND concern_id = ? ORDER BY name ASC",
                [$cid]
            );
        }

        // If no units specifically allocated to this concern, also include shared units (concern_id IS NULL)
        if (empty($productionUnits)) {
            $productionUnits = Database::fetchAll(
                "SELECT * FROM production_units WHERE is_active = 1 AND concern_id IS NULL ORDER BY name ASC"
            );
        }

        // If still empty and we have an active concern, auto-provision a default facility for the concern
        if (empty($productionUnits) && !empty($activeConcern)) {
            $concernCode = !empty($activeConcern['code']) ? $activeConcern['code'] : '01';
            $puCode = 'PU-' . $concernCode;
            $suffix = 1;
            while (Database::fetchOne("SELECT id FROM production_units WHERE code = ?", [$puCode])) {
                $puCode = 'PU-' . $concernCode . '-' . (++$suffix);
            }
            $concernName = !empty($activeConcern['name']) ? $activeConcern['name'] : 'Production Unit';
            $puName = $concernName . ' (Plant)';
            try {
                $newPuId = ProductionUnit::create([
                    'concern_id' => $cid,
                    'name'       => $puName,
                    'code'       => $puCode,
                    'address'    => !empty($activeConcern['address']) ? $activeConcern['address'] : 'Primary Production Facility',
                    'phone'      => !empty($activeConcern['phone']) ? $activeConcern['phone'] : null,
                    'gst_number' => !empty($activeConcern['gst_number']) ? $activeConcern['gst_number'] : null,
                    'is_active'  => 1,
                ]);
                $productionUnits = Database::fetchAll(
                    "SELECT * FROM production_units WHERE is_active = 1 AND (id = ? OR concern_id = ?)",
                    [$newPuId, $cid]
                );
            } catch (\Throwable $e) {
                // If creation fails, fallback to all active production units
                $productionUnits = Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY id ASC");
            }
        }

        // Ultimate fallback to ensure dropdown is never empty
        if (empty($productionUnits)) {
            $productionUnits = Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY id ASC");
        }

        $targetUnitId = !empty($bill['production_unit_id']) ? (int)$bill['production_unit_id'] : ($overrideUnitId ?: 0);
        $savedUnit = $targetUnitId > 0 ? ProductionUnit::find($targetUnitId) : null;
        $prodUnit = $savedUnit ?: (!empty($productionUnits) ? $productionUnits[0] : ProductionUnit::getActiveFacility());
        if ($savedUnit && !array_filter($productionUnits, static fn(array $unit): bool => (int)$unit['id'] === (int)$savedUnit['id'])) {
            $productionUnits[] = $savedUnit;
        }

        $staffUsers = Database::fetchAll("SELECT id, full_name, username, phone, department, staff_role FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        $availablePackingBatches = Database::fetchAll(
            "SELECT pb.*, 
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    w.name AS warehouse_name,
                    w.code AS warehouse_code,
                    c.name AS concern_name,
                    c.code AS concern_code
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN concerns c ON pb.concern_id = c.id OR w.concern_id = c.id
             WHERE pb.production_bill_id IS NULL
               AND pb.status IN ('PREPARING', 'APPROVED')
             ORDER BY pb.id DESC"
        );

        $billLabel = $bill ? (!empty($bill['bill_number']) ? $bill['bill_number'] : '#' . (int)$bill['id']) : '';

        Response::view('allmodules.billing.form', [
            'pageTitle'               => $bill ? 'Edit Production Bill' : 'Create Production Bill',
            'activeModule'            => 'production',
            'activeMenu'              => 'prod-billing',
            'breadcrumb'              => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Billing / Pos', 'url' => url('production/billing')],
                ['label' => $bill ? ('Edit Billing ' . $billLabel) : 'Create Billing'],
            ],
            'bill'                    => $bill,
            'billItems'               => $billItems,
            'prodUnit'                => $prodUnit,
            'productionUnits'         => $productionUnits,
            'activeConcern'           => $activeConcern,
            'staffUsers'              => $staffUsers,
            'availablePackingBatches' => $availablePackingBatches,
            'currentUserId'           => Auth::id(),
        ]);
    }

    /**
     * Load QC items and available piece tags to pre-populate bill.
     */
    private function loadQCItemsForBilling(int $qcBatchId, array $filterTags = []): array
    {
        $rows = Database::fetchAll(
            "SELECT qi.*, 
                    pp.program_number, pp.name AS program_name,
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    p.code AS product_code, p.gst_percentage, p.hsn_code,
                    p.is_complementary AS prod_is_comp,
                    pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                    pv.is_complementary AS var_is_comp
             FROM qc_items qi
             JOIN qc_batches qb ON qb.id = qi.qc_batch_id
             JOIN production_programs pp ON pp.id = qb.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             LEFT JOIN product_variants pv ON pv.id = qi.variant_id
             WHERE qi.qc_batch_id = ? AND qi.passed_qty > 0
             ORDER BY qi.id ASC",
            [$qcBatchId]
        );

        $availableTagsMap = ProductionService::getAvailableQCPieceTags($qcBatchId);
        $cleanFilterTags = array_flip(array_map('strtoupper', array_map('trim', $filterTags)));

        // Exclude already billed tags
        $billedRows = Database::fetchAll("SELECT tag_code FROM production_bill_item_tags");
        $billedTags = array_fill_keys(array_map(static fn($r) => strtoupper(trim((string)$r['tag_code'])), $billedRows), true);

        // Exclude damage tags
        $damagedRows = Database::fetchAll("SELECT qr_code FROM roll_damages WHERE qr_code IS NOT NULL AND status NOT IN ('CANCELLED', 'REJECTED')");
        $damagedTags = array_fill_keys(array_map(static fn($r) => strtoupper(trim((string)$r['qr_code'])), $damagedRows), true);

        $billItems = [];
        foreach ($rows as $r) {
            $qcItemId = (int)$r['id'];
            $itemTags = $availableTagsMap[$qcItemId] ?? [];

            // Filter unbilled and undamaged
            $validTags = [];
            foreach ($itemTags as $tag) {
                $uTag = strtoupper(trim((string)$tag));
                if (isset($billedTags[$uTag]) || isset($damagedTags[$uTag])) {
                    continue;
                }
                if (!empty($cleanFilterTags) && !isset($cleanFilterTags[$uTag])) {
                    continue;
                }
                $validTags[] = $tag;
            }

            if (empty($validTags)) {
                continue;
            }

            $mrp = (float)($r['variant_mrp'] ?: ($r['base_price'] ?: 0));
            $unitPrice = (float)($r['selling_price'] ?: ($r['discount_price'] ?: ($r['base_price'] ?: $mrp)));
            $isComp = (!empty($r['prod_is_comp']) || !empty($r['var_is_comp']));
            if ($isComp) {
                $unitPrice = 0.00;
            }
            $gstRate = $r['gst_percentage'] === null ? 5.0 : (float)$r['gst_percentage'];

            $billItems[] = [
                'qc_item_id'     => $qcItemId,
                'product_name'   => $r['product_name'] ?? 'Finished Product',
                'sku'            => $r['sku'] ?: trim(($r['size_name'] ?? '') . '-' . ($r['color_name'] ?? '')),
                'hsn_code'       => $r['hsn_code'] ?: '6109',
                'size_name'      => $r['size_name'] ?? '',
                'color_name'     => $r['color_name'] ?? '',
                'mrp'            => $mrp,
                'unit_price'     => $unitPrice,
                'is_complementary' => $isComp ? 1 : 0,
                'gst_percentage' => $gstRate,
                'gst_type'       => $gstRate > 0 ? 'BOTH' : 'EXEMPTED',
                'tax_input_type' => 'PERCENT',
                'sgst_rate'      => round($gstRate / 2, 2),
                'cgst_rate'      => round($gstRate / 2, 2),
                'igst_rate'      => 0.0,
                'tags'           => array_values($validTags),
            ];
        }

        return $billItems;
    }

    /**
     * Load packed Batch items and piece tags to pre-populate bill.
     */
    private function loadBatchItemsForBilling(int $batchId): array
    {
        $batch = Database::fetchOne("SELECT * FROM production_batches WHERE id = ?", [$batchId]);
        if (!$batch) return [];

        $pbiRows = Database::fetchAll(
            "SELECT pbi.*,
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    p.code AS product_code, p.gst_percentage, p.hsn_code,
                    p.is_complementary AS prod_is_comp,
                    pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                    pv.is_complementary AS var_is_comp,
                    c.name AS color_name, sz.name AS size_name
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             JOIN production_programs pp ON pp.id = pb.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             LEFT JOIN product_variants pv ON pv.id = pbi.variant_id
             LEFT JOIN colors c ON c.id = pv.color_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE pbi.production_batch_id = ?
             ORDER BY pbi.id ASC",
            [$batchId]
        );

        $billedRows = Database::fetchAll("SELECT tag_code FROM production_bill_item_tags");
        $billedTags = array_fill_keys(array_map(static fn($r) => strtoupper(trim((string)$r['tag_code'])), $billedRows), true);

        $damagedRows = Database::fetchAll("SELECT qr_code FROM roll_damages WHERE qr_code IS NOT NULL AND status NOT IN ('CANCELLED', 'REJECTED')");
        $damagedTags = array_fill_keys(array_map(static fn($r) => strtoupper(trim((string)$r['qr_code'])), $damagedRows), true);

        $billItems = [];
        foreach ($pbiRows as $r) {
            $tags = [];
            if (!empty($r['piece_tags'])) {
                $decoded = json_decode($r['piece_tags'], true);
                $tags = is_array($decoded) ? $decoded : explode(',', $r['piece_tags']);
            }

            $validTags = [];
            foreach ($tags as $t) {
                $tClean = strtoupper(trim((string)$t));
                if ($tClean !== '' && !isset($billedTags[$tClean]) && !isset($damagedTags[$tClean])) {
                    $validTags[] = trim((string)$t);
                }
            }

            if (empty($validTags)) continue;

            $qcItemId = 0;
            if (!empty($batch['qc_batch_id'])) {
                $qi = Database::fetchOne("SELECT id FROM qc_items WHERE qc_batch_id = ? AND variant_id = ? LIMIT 1", [(int)$batch['qc_batch_id'], (int)$r['variant_id']]);
                if ($qi) $qcItemId = (int)$qi['id'];
            }

            $mrp = (float)($r['variant_mrp'] ?: 0);
            $unitPrice = (float)($r['selling_price'] ?: $mrp);
            $isComp = (!empty($r['prod_is_comp']) || !empty($r['var_is_comp']));
            if ($isComp) {
                $unitPrice = 0.00;
            }
            $gstRate = $r['gst_percentage'] === null ? 5.0 : (float)$r['gst_percentage'];

            $billItems[] = [
                'qc_item_id'     => $qcItemId,
                'product_name'   => $r['product_name'] ?? 'Finished Product',
                'sku'            => $r['sku'] ?: trim(($r['size_name'] ?? '') . '-' . ($r['color_name'] ?? '')),
                'hsn_code'       => $r['hsn_code'] ?: '6109',
                'size_name'      => $r['size_name'] ?? '',
                'color_name'     => $r['color_name'] ?? '',
                'mrp'            => $mrp,
                'unit_price'     => $unitPrice,
                'is_complementary' => $isComp ? 1 : 0,
                'gst_percentage' => $gstRate,
                'gst_type'       => $gstRate > 0 ? 'BOTH' : 'EXEMPTED',
                'tax_input_type' => 'PERCENT',
                'sgst_rate'      => round($gstRate / 2, 2),
                'cgst_rate'      => round($gstRate / 2, 2),
                'igst_rate'      => 0.0,
                'tags'           => array_values($validTags),
            ];
        }

        return $billItems;
    }

    private function findBill(int $id): ?array
    {
        return $id > 0
            ? Database::fetchOne("SELECT * FROM production_bills WHERE id = ?", [$id])
            : null;
    }

    private function loadBill(int $id): ?array
    {
        return $id > 0
            ? Database::fetchOne("SELECT * FROM production_bills WHERE id = ?", [$id])
            : null;
    }

    private function loadBillItems(int $billId): array
    {
        $items = Database::fetchAll(
            "SELECT pbi.*,
                    COALESCE(NULLIF(pv.variant_code, ''), IF(pbi.variant_id > 0, CONCAT('V', LPAD(pbi.variant_id, 4, '0')), '')) AS variant_code
             FROM production_bill_items pbi
             LEFT JOIN product_variants pv ON pv.id = pbi.variant_id
             WHERE pbi.bill_id = ?
             ORDER BY pbi.id",
            [$billId]
        );

        $tagsByItem = [];
        if ($items) {
            $tagRows = Database::fetchAll(
                "SELECT bill_item_id, tag_code
                 FROM production_bill_item_tags
                 WHERE bill_id = ?
                 ORDER BY id",
                [$billId]
            );
            foreach ($tagRows as $tagRow) {
                $tagsByItem[(int)$tagRow['bill_item_id']][] = (string)$tagRow['tag_code'];
            }
        }
        foreach ($items as &$item) {
            $item['tags'] = $tagsByItem[(int)$item['id']] ?? [];
            $item['is_complementary'] = (int)($item['is_complementary'] ?? 0);
            if (empty($item['variant_code']) && !empty($item['tags'][0]) && preg_match('/^([A-Z0-9]+)-\d+$/i', (string)$item['tags'][0], $m)) {
                $item['variant_code'] = strtoupper($m[1]);
            }
        }
        unset($item);
        return $items;
    }

    private function getAvailableInventory(int $excludeBillId = 0, int $productionUnitId = 0): array
    {
        Database::ensureColumn('products', 'variants_deferred', 'TINYINT(1) NOT NULL DEFAULT 0');
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($productionUnitId > 0) {
            $unitRow = Database::fetchOne("SELECT concern_id FROM production_units WHERE id = ?", [$productionUnitId]);
            if (!empty($unitRow['concern_id'])) {
                $cid = (int)$unitRow['concern_id'];
            }
        }

        $concernFilter = '';
        $params = [];
        if ($cid) {
            $concernFilter = ' AND (qb.concern_id = ? OR pp.concern_id = ? OR qb.concern_id IS NULL OR pp.concern_id IS NULL OR qb.concern_id = 1 OR pp.concern_id = 1)';
            $params = [$cid, $cid];
        }

        $rows = Database::fetchAll(
            "SELECT qi.id AS qc_item_id, qi.qc_batch_id, qi.variant_id, qi.size_name, qi.color_name,
                    qi.passed_qty,
                    qi.base_price AS qc_base_price, qi.discount_price AS qc_discount_price,
                    pp.program_number, pp.name AS program_name,
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    p.id AS product_id,
                    COALESCE(p.variants_deferred, 0) AS variants_deferred,
                    p.code AS product_code, p.gst_percentage, p.hsn_code,
                    p.is_complementary AS prod_is_comp,
                    COALESCE(NULLIF(pv.variant_code, ''), IF(qi.variant_id > 0, CONCAT('V', LPAD(qi.variant_id, 4, '0')), NULL)) AS variant_code,
                    pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                    pv.is_complementary AS var_is_comp,
                    c.name AS color_from_variant, sz.name AS size_from_variant
             FROM qc_items qi
             JOIN qc_batches qb ON qb.id = qi.qc_batch_id
             JOIN production_programs pp ON pp.id = qb.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             LEFT JOIN product_variants pv ON pv.id = qi.variant_id
             LEFT JOIN colors c ON c.id = pv.color_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE qi.passed_qty > 0{$concernFilter}
             ORDER BY qb.created_at DESC, qi.id DESC",
            $params
        );

        // Resolve variant_id for rows if missing
        foreach ($rows as &$row) {
            $vId = (int)($row['variant_id'] ?? 0);
            if ($vId <= 0 && !empty($row['qc_batch_id'])) {
                // 1. Try to resolve variant_id from linked QR generation items
                $qrItem = Database::fetchOne("
                    SELECT qri.variant_id, pv.variant_code
                    FROM qr_generation_items qri
                    JOIN qr_generations qrg ON qrg.id = qri.generation_id
                    LEFT JOIN product_variants pv ON pv.id = qri.variant_id
                    WHERE (qrg.qc_batch_id = ? 
                           OR qrg.id = (SELECT qr_generation_id FROM qc_batches WHERE id = ?)
                           OR (qrg.stitching_batch_id > 0 AND qrg.stitching_batch_id = (SELECT stitching_batch_id FROM qc_batches WHERE id = ?)))
                      AND (? = '' OR UPPER(TRIM(COALESCE(qri.size_name, ''))) = UPPER(TRIM(?)))
                      AND (? = '' OR UPPER(TRIM(COALESCE(qri.color_name, ''))) = UPPER(TRIM(?)))
                      AND qri.variant_id > 0
                    ORDER BY qri.id ASC LIMIT 1
                ", [
                    (int)$row['qc_batch_id'],
                    (int)$row['qc_batch_id'],
                    (int)$row['qc_batch_id'],
                    (string)($row['size_name'] ?? ''),
                    (string)($row['size_name'] ?? ''),
                    (string)($row['color_name'] ?? ''),
                    (string)($row['color_name'] ?? ''),
                ]);
                if (!empty($qrItem['variant_id'])) {
                    $vId = (int)$qrItem['variant_id'];
                    $row['variant_id'] = $vId;
                    if (!empty($qrItem['variant_code'])) $row['variant_code'] = $qrItem['variant_code'];
                    Database::execute("UPDATE qc_items SET variant_id = ? WHERE id = ?", [$vId, (int)$row['qc_item_id']]);
                }
            }

            if ($vId <= 0 && !empty($row['product_id'])) {
                $sizeName = trim((string)($row['size_name'] ?: ($row['size_from_variant'] ?? '')));
                $colorName = trim((string)($row['color_name'] ?: ($row['color_from_variant'] ?? '')));
                if ($sizeName !== '' || $colorName !== '') {
                    try {
                        $vMatch = VariantService::findOrCreateVariantForInward((int)$row['product_id'], [
                            'size' => $sizeName ?: null,
                            'color' => $colorName ?: null,
                            'mrp' => (float)($row['qc_base_price'] ?? 0),
                            'selling_price' => (float)($row['qc_discount_price'] ?? 0),
                            'is_active' => 1
                        ], true);
                        if (!empty($vMatch['id'])) {
                            $vId = (int)$vMatch['id'];
                            $row['variant_id'] = $vId;
                            $row['variant_code'] = $vMatch['variant_code'] ?? null;
                            Database::execute("UPDATE qc_items SET variant_id = ? WHERE id = ?", [$vId, (int)$row['qc_item_id']]);
                        }
                    } catch (\Throwable $e) {}
                }
            }

            if ($vId > 0 && empty($row['variant_code'])) {
                $vMeta = Database::fetchOne("SELECT variant_code FROM product_variants WHERE id = ?", [$vId]);
                if ($vMeta && !empty($vMeta['variant_code'])) {
                    $row['variant_code'] = $vMeta['variant_code'];
                }
            }
        }
        unset($row);

        $batchIds = array_values(array_unique(array_map(
            static fn(array $row): int => (int)$row['qc_batch_id'],
            $rows
        )));
        $tagsByItem = [];
        foreach ($batchIds as $batchId) {
            $batchTags = ProductionService::getAvailableQCPieceTags($batchId);
            foreach ($batchTags as $itemId => $tags) {
                $tagsByItem[(int)$itemId] = $tags;
            }
        }

        $soldRows = $excludeBillId > 0
            ? Database::fetchAll("SELECT tag_code FROM production_bill_item_tags WHERE bill_id <> ?", [$excludeBillId])
            : Database::fetchAll("SELECT tag_code FROM production_bill_item_tags");
        $soldTags = array_fill_keys(array_map(
            static fn(array $row): string => strtoupper(trim((string)$row['tag_code'])),
            $soldRows
        ), true);

        $damagedRows = Database::fetchAll(
            "SELECT DISTINCT qr_code FROM roll_damages 
             WHERE qr_code IS NOT NULL AND TRIM(qr_code) <> '' 
               AND status NOT IN ('CANCELLED', 'REJECTED')"
        );
        $damagedTags = array_fill_keys(array_map(
            static fn(array $row): string => strtoupper(trim((string)$row['qr_code'])),
            $damagedRows
        ), true);

        // Get all piece tags that were already packed into dispatched batches
        $outwardedTagRows = Database::fetchAll(
            "SELECT pbi.piece_tags, pb.status as batch_status, w.concern_id as dest_concern_id
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             JOIN production_outwards po ON po.production_batch_id = pb.id
             WHERE po.status IN ('DISPATCHED', 'RECEIVED') OR pb.status IN ('DISPATCHED', 'RECEIVED')"
        );
        $outwardedTags = [];
        foreach ($outwardedTagRows as $otRow) {
            // If the batch was received into a warehouse belonging to the active concern, do not mark as outwarded for this concern
            if ($cid && !empty($otRow['dest_concern_id']) && (int)$otRow['dest_concern_id'] === (int)$cid && ($otRow['batch_status'] ?? '') === 'RECEIVED') {
                continue;
            }
            if (!empty($otRow['piece_tags'])) {
                $decoded = json_decode($otRow['piece_tags'], true);
                $tagList = is_array($decoded) ? $decoded : explode(',', $otRow['piece_tags']);
                foreach ($tagList as $t) {
                    $tClean = strtoupper(trim((string)$t));
                    if ($tClean !== '') $outwardedTags[$tClean] = true;
                }
            }
        }
        $whQrRows = Database::fetchAll("SELECT qr_code FROM warehouse_qr_codes");
        foreach ($whQrRows as $wqRow) {
            $t = strtoupper(trim((string)$wqRow['qr_code']));
            if ($t !== '') $outwardedTags[$t] = true;
        }

        $available = [];
        foreach ($rows as $row) {
            if (!empty($row['variant_setup_pending'])) {
                continue;
            }
            $qcItemId = (int)$row['qc_item_id'];
            $tags = array_values(array_filter(
                $tagsByItem[$qcItemId] ?? [],
                static fn(string $tag): bool => !isset($soldTags[strtoupper(trim($tag))])
                    && !isset($damagedTags[strtoupper(trim($tag))])
                    && !isset($outwardedTags[strtoupper(trim($tag))])
            ));

            // If piece tags empty, fetch from qr_generation_items
            if (empty($tags)) {
                $genItems = Database::fetchAll("
                    SELECT qri.qr_code
                    FROM qr_generation_items qri
                    JOIN qr_generations qrg ON qrg.id = qri.generation_id
                    WHERE (qrg.qc_batch_id = ? 
                           OR qrg.id = (SELECT qr_generation_id FROM qc_batches WHERE id = ?)
                           OR (qrg.stitching_batch_id > 0 AND qrg.stitching_batch_id = (SELECT stitching_batch_id FROM qc_batches WHERE id = ?)))
                      AND (qri.variant_id = ? OR (? = 0 AND UPPER(TRIM(COALESCE(qri.size_name, ''))) = UPPER(TRIM(COALESCE(?, '')))))
                    ORDER BY qri.sequence_num ASC
                ", [(int)$row['qc_batch_id'], (int)$row['qc_batch_id'], (int)$row['qc_batch_id'], (int)($row['variant_id'] ?? 0), (int)($row['variant_id'] ?? 0), (string)($row['size_name'] ?? '')]);
                foreach ($genItems as $gi) {
                    $t = strtoupper(trim((string)$gi['qr_code']));
                    if ($t !== '' && !isset($soldTags[$t]) && !isset($damagedTags[$t]) && !isset($outwardedTags[$t])) {
                        $tags[] = $gi['qr_code'];
                    }
                }
            }

            // Fallback generation of piece tags if still empty but passed_qty > 0
            if (empty($tags) && (int)($row['passed_qty'] ?? 0) > 0) {
                $remQty = (int)$row['passed_qty'];
                $vPrefix = !empty($row['variant_code']) ? $row['variant_code'] : ('QC' . $row['qc_batch_id']);
                for ($p = 1; $p <= $remQty; $p++) {
                    $cand = $vPrefix . '-' . sprintf('%03d', $p);
                    if (!isset($soldTags[strtoupper($cand)]) && !isset($damagedTags[strtoupper($cand)]) && !isset($outwardedTags[strtoupper($cand)])) {
                        $tags[] = $cand;
                    }
                }
            }

            if (!$tags) {
                continue;
            }
            $tags = array_values(array_unique($tags));
            natcasesort($tags);
            $tags = array_values($tags);

            $mrp = (float)($row['variant_mrp'] ?? 0);
            if ($mrp <= 0) {
                $mrp = (float)($row['qc_base_price'] ?? 0);
            }
            $defaultPrice = (float)($row['selling_price'] ?? 0);
            if ($defaultPrice <= 0) {
                $defaultPrice = (float)($row['qc_discount_price'] ?? 0);
            }
            if ($defaultPrice <= 0) {
                $defaultPrice = $mrp;
            }

            $isComp = (!empty($row['prod_is_comp']) || !empty($row['var_is_comp']));
            if ($isComp) {
                $defaultPrice = 0.00;
            }

            $available[] = [
                'qc_item_id' => $qcItemId,
                'qc_batch_id' => (int)$row['qc_batch_id'],
                'variant_id' => (int)($row['variant_id'] ?? 0),
                'variant_code' => (string)(!empty($row['variant_code']) ? $row['variant_code'] : ((int)($row['variant_id'] ?? 0) > 0 ? sprintf('V%04d', (int)$row['variant_id']) : '')),
                'product_name' => (string)$row['product_name'],
                'program_number' => (string)($row['program_number'] ?? ''),
                'sku' => (string)(!empty($row['sku']) ? $row['sku'] : ($row['product_code'] ?? '')),
                'size_name' => (string)(!empty($row['size_name']) ? $row['size_name'] : ($row['size_from_variant'] ?? '')),
                'color_name' => (string)(!empty($row['color_name']) ? $row['color_name'] : ($row['color_from_variant'] ?? '')),
                'mrp' => $mrp,
                'unit_price' => $defaultPrice,
                'is_complementary' => $isComp ? 1 : 0,
                'gst_percentage' => max(0, (float)($row['gst_percentage'] ?? 0)),
                'hsn_code' => (string)($row['hsn_code'] ?? '6109'),
                'available_tags' => $tags,
            ];
        }

        // Also fetch inwarded finished goods received in warehouses belonging to this concern
        if ($cid) {
            try {
                $inwardBillingRows = Database::fetchAll(
                    "SELECT pbi.id AS batch_item_id, pbi.production_batch_id, pbi.variant_id, pbi.quantity,
                            pbi.received_qty, pbi.received_qr_codes, pbi.piece_tags,
                            pb.batch_number, pb.program_id,
                            COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                            p.id AS product_id,
                            COALESCE(p.variants_deferred, 0) AS variants_deferred,
                            p.code AS product_code, p.gst_percentage, p.hsn_code,
                            p.is_complementary AS prod_is_comp,
                            COALESCE(NULLIF(pv.variant_code, ''), IF(pbi.variant_id > 0, CONCAT('V', LPAD(pbi.variant_id, 4, '0')), NULL)) AS variant_code,
                            pv.mrp AS variant_mrp, pv.selling_price,
                            pv.is_complementary AS var_is_comp,
                            s.name AS size_name, c.name AS color_name,
                            pp.program_number
                     FROM production_batch_items pbi
                     JOIN production_batches pb ON pb.id = pbi.production_batch_id
                     JOIN warehouses w ON pb.destination_warehouse_id = w.id
                     LEFT JOIN production_programs pp ON pb.program_id = pp.id
                     LEFT JOIN product_variants pv ON pbi.variant_id = pv.id
                     LEFT JOIN products p ON pv.product_id = p.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     WHERE pb.status = 'RECEIVED'
                       AND (w.concern_id = ? OR (w.concern_id IS NULL AND pb.destination_warehouse_id = ?))",
                    [$cid, $cid]
                );

                foreach ($inwardBillingRows as $iRow) {
                    $rawTags = [];
                    if (!empty($iRow['received_qr_codes'])) {
                        $rawTags = array_filter(array_map('trim', explode(',', $iRow['received_qr_codes'])));
                    } elseif (!empty($iRow['piece_tags'])) {
                        $dec = json_decode($iRow['piece_tags'], true);
                        $rawTags = is_array($dec) ? $dec : array_filter(array_map('trim', explode(',', $iRow['piece_tags'])));
                    }
                    
                    // Exclude tags already sold in bills or marked as damaged
                    $availTags = array_values(array_filter(
                        $rawTags,
                        static fn(string $tag): bool => !isset($soldTags[strtoupper(trim($tag))])
                            && !isset($damagedTags[strtoupper(trim($tag))])
                    ));

                    if (empty($availTags)) {
                        continue;
                    }
                    $availTags = array_values(array_unique($availTags));
                    natcasesort($availTags);
                    $availTags = array_values($availTags);

                    $mrp = (float)($iRow['variant_mrp'] ?? 0);
                    $price = (float)($iRow['selling_price'] ?? 0);
                    if ($price <= 0) $price = $mrp;
                    $isComp = (!empty($iRow['prod_is_comp']) || !empty($iRow['var_is_comp']));
                    if ($isComp) $price = 0.00;

                    $available[] = [
                        'qc_item_id' => 0,
                        'qc_batch_id' => (int)$iRow['production_batch_id'],
                        'variant_id' => (int)($iRow['variant_id'] ?? 0),
                        'variant_code' => (string)(!empty($iRow['variant_code']) ? $iRow['variant_code'] : ((int)($iRow['variant_id'] ?? 0) > 0 ? sprintf('V%04d', (int)$iRow['variant_id']) : '')),
                        'product_name' => (string)$iRow['product_name'],
                        'program_number' => (string)($iRow['program_number'] ?? $iRow['batch_number']),
                        'sku' => (string)(!empty($iRow['sku']) ? $iRow['sku'] : ($iRow['product_code'] ?? '')),
                        'size_name' => (string)($iRow['size_name'] ?? 'Std'),
                        'color_name' => (string)($iRow['color_name'] ?? 'Std'),
                        'mrp' => $mrp,
                        'unit_price' => $price,
                        'is_complementary' => $isComp ? 1 : 0,
                        'gst_percentage' => max(0, (float)($iRow['gst_percentage'] ?? 0)),
                        'hsn_code' => (string)($iRow['hsn_code'] ?? '6109'),
                        'available_tags' => $availTags,
                    ];
                }
            } catch (\Throwable $e) {
                error_log('Error loading inward billing inventory: ' . $e->getMessage());
            }
        }

        return $available;
    }

    private function persistBill(?int $billId): void
    {
        self::ensureSchema();
        $isNewBill = $billId === null;
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $companyAddress = trim((string)($_POST['company_address'] ?? ''));
        $companyPhone = trim((string)($_POST['company_phone'] ?? ''));
        $companyEmail = trim((string)($_POST['company_email'] ?? ''));
        $companyGstin = trim((string)($_POST['company_gstin'] ?? ''));
        $customerName = trim((string)($_POST['customer_name'] ?? ''));
        $customerPhone = preg_replace('/\s+/', '', (string)($_POST['customer_phone'] ?? ''));
        $customerAddress = trim((string)($_POST['customer_address'] ?? ''));
        $customerCity = trim((string)($_POST['customer_city'] ?? ''));
        $customerState = trim((string)($_POST['customer_state'] ?? ''));
        $customerGstin = trim((string)($_POST['customer_gstin'] ?? ''));
        $buyerType = strtoupper(trim((string)($_POST['buyer_type'] ?? 'COMPANY')));
        $billDate = trim((string)($_POST['bill_date'] ?? date('Y-m-d')));
        $paymentMethod = strtoupper(trim((string)($_POST['payment_method'] ?? 'CASH')));
        $productionUnitId = (int)($_POST['production_unit_id'] ?? 0);
        $submittedItems = $_POST['items'] ?? [];

        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId !== null) {
            $productionUnitId = $scopedUnitId;
        }
        $productionUnit = $productionUnitId > 0
            ? Database::fetchOne("SELECT id, is_active FROM production_units WHERE id = ?", [$productionUnitId])
            : null;
        if (!$productionUnit || (int)$productionUnit['is_active'] !== 1) {
            $cid = function_exists('currentConcernId') ? currentConcernId() : null;
            if ($cid) {
                $productionUnit = Database::fetchOne("SELECT id, is_active FROM production_units WHERE concern_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1", [$cid]);
            }
            if (!$productionUnit) {
                $productionUnit = Database::fetchOne("SELECT id, is_active FROM production_units WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            }
            if ($productionUnit) {
                $productionUnitId = (int)$productionUnit['id'];
            }
        }
        if (!$productionUnit || (int)$productionUnit['is_active'] !== 1) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Select an active production unit for this bill.');
            return;
        }

        if (!in_array($buyerType, ['COMPANY', 'CUSTOMER'], true)) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Select whether this bill is for a company or customer.');
            return;
        }
        if (($buyerType === 'COMPANY' && $companyName === '') || ($buyerType === 'CUSTOMER' && ($customerName === '' || $customerPhone === ''))) {
            $requiredMessage = $buyerType === 'COMPANY'
                ? 'Company name is required.'
                : 'Customer name and phone are required.';
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', $requiredMessage);
            return;
        }
        $validPhone = static fn(string $phone): bool => $phone === '' || (bool)preg_match('/^\+?[0-9][0-9 ()-]{6,19}$/', $phone);
        $validGstin = static fn(string $gstin): bool => $gstin === '' || (bool)preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', strtoupper($gstin));
        if (
            ($buyerType === 'COMPANY' && (
                strlen($companyName) > 200 ||
                !$validPhone($companyPhone) || strlen($companyPhone) > 30 ||
                ($companyEmail !== '' && (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL) || strlen($companyEmail) > 150)) ||
                !$validGstin($companyGstin) || strlen($companyGstin) > 30
            )) ||
            ($buyerType === 'CUSTOMER' && (
                strlen($customerName) > 150 || !$validPhone($customerPhone) || strlen($customerPhone) > 20 ||
                !$validGstin($customerGstin) || strlen($customerGstin) > 30 ||
                strlen($customerCity) > 100 || strlen($customerState) > 100
            ))
        ) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Check the company or customer contact details, phone, email, and GSTIN.');
            return;
        }
        $companyGstin = strtoupper($companyGstin);
        $customerGstin = strtoupper($customerGstin);
        if ($buyerType === 'COMPANY') {
            $customerName = null;
            $customerPhone = null;
            $customerAddress = null;
            $customerCity = null;
            $customerState = null;
            $customerGstin = null;
        } else {
            $companyName = null;
            $companyAddress = null;
            $companyPhone = null;
            $companyEmail = null;
            $companyGstin = null;
        }
        $dateParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $billDate, $dateMatch) ? $dateMatch : null;
        if (!$dateParts || !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The bill date is invalid.');
            return;
        }
        if (!in_array($paymentMethod, ['CASH', 'UPI', 'CASH_UPI', 'CARD', 'NET_BANKING', 'CREDIT'], true)) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Select a valid payment method.');
            return;
        }
        $upiReference = trim((string)($_POST['upi_reference'] ?? ''));
        if (in_array($paymentMethod, ['UPI', 'CASH_UPI'], true) && $upiReference === '') {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Enter the UPI reference for this payment.');
            return;
        }
        if (strlen($upiReference) > 150) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'UPI reference must be 150 characters or fewer.');
            return;
        }
        $paymentScreenshotFile = $_FILES['upi_payment_screenshot'] ?? null;
        $hasPaymentScreenshot = is_array($paymentScreenshotFile)
            && ($paymentScreenshotFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasPaymentScreenshot) {
            if (!in_array($paymentMethod, ['UPI', 'CASH_UPI'], true)) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'A GPay screenshot can only be attached to a UPI payment.');
                return;
            }
            if (($paymentScreenshotFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The GPay screenshot could not be uploaded. Please try again.');
                return;
            }
            if (empty($paymentScreenshotFile['tmp_name']) || (int)($paymentScreenshotFile['size'] ?? 0) > 5 * 1024 * 1024) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Choose a valid GPay screenshot under 5 MB.');
                return;
            }
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $screenshotMime = $fileInfo->file($paymentScreenshotFile['tmp_name']);
            if (!in_array($screenshotMime, ['image/jpeg', 'image/png', 'image/webp', 'image/avif'], true)) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The GPay proof must be a JPG, PNG, WEBP, or AVIF image.');
                return;
            }
        }

        $inventory = [];
        foreach ($this->getAvailableInventory($billId ?? 0, $productionUnitId) as $entry) {
            $inventory[(int)$entry['qc_item_id']] = $entry;
        }

        $itemPayload = [];
        $usedTags = [];
        $variantRequestedQuantities = [];
        foreach (is_array($submittedItems) ? $submittedItems : [] as $submitted) {
            if (!is_array($submitted)) {
                continue;
            }
            $qcItemId = (int)($submitted['qc_item_id'] ?? 0);
            $variantId = (int)($submitted['variant_id'] ?? 0);
            $tags = array_values(array_unique(array_filter(array_map(
                static fn($tag): string => trim((string)$tag),
                (array)($submitted['tags'] ?? [])
            ))));
            if (!$tags) {
                continue;
            }
            $tags = array_values(array_unique($tags));
            natcasesort($tags);
            $tags = array_values($tags);

            if ($qcItemId === 0 && $variantId > 0) {
                $varRow = Database::fetchOne(
                    "SELECT pv.*, p.name as product_name, p.hsn_code as prod_hsn, p.gst_percentage as prod_gst,
                            p.is_complementary as prod_is_comp,
                            sz.name as size_name, col.name as color_name
                     FROM product_variants pv
                     JOIN products p ON p.id = pv.product_id
                     LEFT JOIN sizes sz ON sz.id = pv.size_id
                     LEFT JOIN colors col ON col.id = pv.color_id
                     WHERE pv.id = ?",
                    [$variantId]
                );
                $balRow = Database::fetchOne(
                    "SELECT COALESCE(SUM(quantity), 0) AS quantity
                     FROM stock_balances
                     WHERE variant_id = ? AND location_type = 'PRODUCTION_UNIT'
                       AND location_id = ? AND stock_state = 'AVAILABLE'",
                    [$variantId, $productionUnitId]
                );
                $availableVariantQty = (float)($balRow['quantity'] ?? 0);
                if ($availableVariantQty <= 0) {
                    $anyBalRow = Database::fetchOne(
                        "SELECT COALESCE(SUM(quantity), 0) AS quantity
                         FROM stock_balances
                         WHERE variant_id = ? AND stock_state = 'AVAILABLE'",
                        [$variantId]
                    );
                    $availableVariantQty = (float)($anyBalRow['quantity'] ?? 0);
                }
                if ($availableVariantQty <= 0) {
                    foreach ($this->getAvailableInventory($billId ?? 0, $productionUnitId) as $availEntry) {
                        if ((int)($availEntry['variant_id'] ?? 0) === $variantId) {
                            $availableVariantQty += count($availEntry['available_tags'] ?? []);
                        }
                    }
                }
                $requestedVariantQty = ($variantRequestedQuantities[$variantId] ?? 0) + count($tags);
                if (!$varRow || $availableVariantQty < $requestedVariantQty) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Insufficient available stock for variant ' . ($varRow['variant_code'] ?? "ID $variantId") . '. Available: ' . $availableVariantQty);
                    return;
                }
                $variantRequestedQuantities[$variantId] = $requestedVariantQty;
                $isComp = (!empty($varRow['is_complementary']) || !empty($varRow['prod_is_comp']));
                $stock = [
                    'qc_item_id' => 0,
                    'qc_batch_id' => 0,
                    'variant_id' => $variantId,
                    'product_name' => $varRow['product_name'],
                    'program_number' => '',
                    'sku' => '',
                    'size_name' => $varRow['size_name'] ?? 'STD',
                    'color_name' => $varRow['color_name'] ?? 'STD',
                    'mrp' => (float)$varRow['mrp'],
                    'unit_price' => $isComp ? 0.00 : (float)$varRow['selling_price'],
                    'is_complementary' => $isComp ? 1 : 0,
                    'gst_percentage' => (float)($varRow['gst_rate'] ?? $varRow['prod_gst'] ?? 0),
                    'hsn_code' => (string)($varRow['hsn_code'] ?? $varRow['prod_hsn'] ?? '6109'),
                    'available_tags' => $tags,
                ];
                $availableTagMap = array_fill_keys(array_map('strtoupper', $tags), true);
            } else {
                if (!isset($inventory[$qcItemId])) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'One or more selected items are no longer available.');
                    return;
                }
                $stock = $inventory[$qcItemId];
                $availableTagMap = array_fill_keys(array_map('strtoupper', $stock['available_tags']), true);
            }

            // Check against Damages records (quarantined fabric rolls)
            $damagedCheckRows = Database::fetchAll(
                "SELECT rd.damage_number, rd.damage_reason, rd.qr_code, sr.roll_number
                 FROM roll_damages rd
                 LEFT JOIN seller_rolls sr ON sr.id = rd.seller_roll_id
                 WHERE rd.status NOT IN ('CANCELLED', 'REJECTED')
                   AND (rd.item_type IS NULL OR rd.item_type != 'PRODUCT' OR rd.variant_id IS NULL)"
            );
            $damagedLookup = [];
            foreach ($damagedCheckRows as $dCR) {
                if (!empty($dCR['qr_code'])) $damagedLookup[strtoupper(trim($dCR['qr_code']))] = $dCR;
                if (!empty($dCR['roll_number'])) $damagedLookup[strtoupper(trim($dCR['roll_number']))] = $dCR;
            }

            // Check against Outward & Dispatch to warehouse
            $outwardCheckRows = Database::fetchAll(
                "SELECT po.outward_number, w.name as warehouse_name, pbi.piece_tags
                 FROM production_batch_items pbi
                 JOIN production_batches pb ON pb.id = pbi.production_batch_id
                 JOIN production_outwards po ON po.production_batch_id = pb.id
                 LEFT JOIN warehouses w ON (w.id = po.warehouse_id OR w.id = pb.destination_warehouse_id)
                 WHERE po.status IN ('DISPATCHED', 'RECEIVED') OR pb.status IN ('DISPATCHED', 'RECEIVED')"
            );
            $outwardLookup = [];
            foreach ($outwardCheckRows as $oCR) {
                if (!empty($oCR['piece_tags'])) {
                    $decoded = json_decode($oCR['piece_tags'], true);
                    $tagList = is_array($decoded) ? $decoded : explode(',', $oCR['piece_tags']);
                    foreach ($tagList as $t) {
                        $tClean = strtoupper(trim((string)$t));
                        if ($tClean !== '') $outwardLookup[$tClean] = $oCR;
                    }
                }
            }
            $whQrCheck = Database::fetchAll("SELECT wq.qr_code, w.name as warehouse_name FROM warehouse_qr_codes wq LEFT JOIN warehouses w ON w.id = wq.warehouse_id");
            foreach ($whQrCheck as $wQC) {
                $tClean = strtoupper(trim((string)$wQC['qr_code']));
                if ($tClean !== '') $outwardLookup[$tClean] = ['outward_number' => 'WAREHOUSE_STOCK', 'warehouse_name' => $wQC['warehouse_name']];
            }

            foreach ($tags as $tag) {
                $normalized = strtoupper($tag);
                if (isset($damagedLookup[$normalized])) {
                    $d = $damagedLookup[$normalized];
                    Session::flash('error', "Cannot bill QR item '{$tag}'! It has been quarantined in Damages (Damage #{$d['damage_number']} — {$d['damage_reason']}).");
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create');
                    return;
                }
                if (isset($outwardLookup[$normalized])) {
                    $o = $outwardLookup[$normalized];
                    Session::flash('error', "Cannot bill QR item '{$tag}'! It has already been outwarded to warehouse ({$o['warehouse_name']} under Outward #{$o['outward_number']}).");
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create');
                    return;
                }
                if (!isset($availableTagMap[$normalized]) || isset($usedTags[$normalized])) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'A scanned QR code is unavailable or duplicated. Please scan the item again.');
                    return;
                }
                $usedTags[$normalized] = true;
            }

            $unitPrice = filter_var($submitted['unit_price'] ?? null, FILTER_VALIDATE_FLOAT);
            if (array_key_exists('discount_percentage', $submitted) && trim((string)$submitted['discount_percentage']) !== '') {
                $submittedDiscount = filter_var($submitted['discount_percentage'], FILTER_VALIDATE_FLOAT);
                if ($submittedDiscount === false || $submittedDiscount < 0 || $submittedDiscount > 100) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Enter a valid numeric discount percentage between 0 and 100.');
                    return;
                }
            }
            $rawHsnCode = trim((string)($submitted['hsn_code'] ?? ''));
            if ($rawHsnCode === '') {
                $rawHsnCode = trim((string)($stock['hsn_code'] ?? '6109'));
            }
            if ($rawHsnCode === '') {
                $rawHsnCode = '6109';
            }
            $hsnCode = preg_replace('/[\s.\/-]+/', '', $rawHsnCode);
            if ($hsnCode === null) {
                $hsnCode = $rawHsnCode;
            }
            $gstType = strtoupper(trim((string)($submitted['gst_type'] ?? 'BOTH')));
            $taxInputType = strtoupper(trim((string)($submitted['tax_input_type'] ?? 'PERCENT')));
            $validGstTypes = ['SGST', 'IGST', 'CGST', 'BOTH', 'EXEMPTED'];
            $validTaxInputTypes = ['PERCENT', 'FIXED'];
            $hasComponentRates = array_key_exists('sgst_rate', $submitted)
                || array_key_exists('cgst_rate', $submitted)
                || array_key_exists('igst_rate', $submitted);
            $enteredGstRate = null;
            if ($hasComponentRates) {
                $sgstRate = filter_var($submitted['sgst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
                $cgstRate = filter_var($submitted['cgst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
                $igstRate = filter_var($submitted['igst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            } else {
                $enteredGstRate = filter_var($submitted['gst_percentage'] ?? 0, FILTER_VALIDATE_FLOAT);
                $sgstRate = $cgstRate = $igstRate = 0.0;
                if ($enteredGstRate !== false) {
                    if ($gstType === 'BOTH') {
                        $sgstRate = $cgstRate = $enteredGstRate / 2;
                    } elseif ($gstType === 'SGST') {
                        $sgstRate = $enteredGstRate;
                    } elseif ($gstType === 'CGST') {
                        $cgstRate = $enteredGstRate;
                    } elseif ($gstType === 'IGST') {
                        $igstRate = $enteredGstRate;
                    }
                }
            }
            if ($unitPrice === false || $unitPrice < 0) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Enter a valid non-negative item price.');
                return;
            }
            if (!preg_match('/^(?:[0-9]{4}|[0-9]{6}|[0-9]{8})$/', $hsnCode)) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The HSN code must contain 4, 6, or 8 digits.');
                return;
            }
            if (
                !in_array($gstType, $validGstTypes, true) ||
                !in_array($taxInputType, $validTaxInputTypes, true) ||
                (!$hasComponentRates && $enteredGstRate === false) ||
                $sgstRate === false || $cgstRate === false || $igstRate === false ||
                min($sgstRate, $cgstRate, $igstRate) < 0 ||
                ($taxInputType === 'PERCENT' && ($sgstRate + $cgstRate + $igstRate) > 100)
            ) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'A GST rate or tax type is invalid.');
                return;
            }

            if ($gstType === 'EXEMPTED') {
                $sgstRate = $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'SGST') {
                $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'CGST') {
                $sgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'IGST') {
                $sgstRate = $cgstRate = 0.0;
            } else {
                $igstRate = 0.0;
            }

            $isComplementary = !empty($submitted['is_complementary']) || !empty($stock['is_complementary']);
            if ($isComplementary) {
                $unitPrice = 0.00;
            }

            $itemPayload[] = [
                'stock' => $stock,
                'tags' => $tags,
                'quantity' => count($tags),
                'unit_price' => round((float)$unitPrice, 2),
                'is_complementary' => $isComplementary ? 1 : 0,
                'hsn_code' => $hsnCode,
                'gst_type' => $gstType,
                'tax_input_type' => $taxInputType,
                'sgst_rate' => round((float)$sgstRate, 3),
                'cgst_rate' => round((float)$cgstRate, 3),
                'igst_rate' => round((float)$igstRate, 3),
            ];
        }
        if (!$itemPayload) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Scan at least one available Production Inventory QR item.');
            return;
        }

        $totals = [
            'gross_amount' => 0.0, 'discount_amount' => 0.0, 'subtotal' => 0.0,
            'cgst_amount' => 0.0, 'sgst_amount' => 0.0, 'igst_amount' => 0.0,
            'tax_amount' => 0.0, 'total_amount' => 0.0,
        ];
        foreach ($itemPayload as &$item) {
            $stock = $item['stock'];
            $qty = $item['quantity'];
            $mrp = max(0, (float)$stock['mrp']);
            $unitPrice = $item['unit_price'];
            $gross = round($mrp * $qty, 2);
            $netLine = round($unitPrice * $qty, 2);
            $discount = max(0, round($gross - $netLine, 2));

            $totalRate = ($item['tax_input_type'] === 'PERCENT')
                ? ($item['cgst_rate'] + $item['sgst_rate'] + $item['igst_rate'])
                : 0.0;

            if ($item['tax_input_type'] === 'PERCENT' && $totalRate > 0) {
                // GST is INCLUSIVE inside selling price
                $taxable = round($netLine / (1 + ($totalRate / 100)), 2);
                $tax = round($netLine - $taxable, 2);
                $cgst = $item['cgst_rate'] > 0 ? round($tax * ($item['cgst_rate'] / $totalRate), 2) : 0.0;
                $sgst = $item['sgst_rate'] > 0 ? round($tax * ($item['sgst_rate'] / $totalRate), 2) : 0.0;
                $igst = round($tax - ($cgst + $sgst), 2);
            } elseif ($item['tax_input_type'] !== 'PERCENT') {
                $tax = round(($item['cgst_rate'] + $item['sgst_rate'] + $item['igst_rate']) * $qty, 2);
                $taxable = max(0, round($netLine - $tax, 2));
                $cgst = round($item['cgst_rate'] * $qty, 2);
                $sgst = round($item['sgst_rate'] * $qty, 2);
                $igst = round($item['igst_rate'] * $qty, 2);
            } else {
                $taxable = $netLine;
                $tax = 0.0;
                $cgst = 0.0;
                $sgst = 0.0;
                $igst = 0.0;
            }

            $lineTotal = $netLine;
            $gstPercentage = $item['tax_input_type'] === 'PERCENT' ? $totalRate : 0.0;
            $item['gst_percentage'] = round($gstPercentage, 3);
            $item['amounts'] = [
                'mrp' => $mrp,
                'discount_percentage' => $gross > 0 ? round(($discount / $gross) * 100, 3) : 0,
                'discount_amount' => $discount,
                'taxable_amount' => $taxable,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'tax_amount' => $tax,
                'total_amount' => $lineTotal,
            ];
            $totals['gross_amount'] += $gross;
            $totals['discount_amount'] += $discount;
            $totals['subtotal'] += $taxable;
            $totals['cgst_amount'] += $cgst;
            $totals['sgst_amount'] += $sgst;
            $totals['igst_amount'] += $igst;
            $totals['tax_amount'] += $tax;
            $totals['total_amount'] += $lineTotal;
        }
        unset($item);
        $taxType = count(array_unique(array_column($itemPayload, 'gst_type'))) === 1
            && $itemPayload[0]['gst_type'] === 'IGST'
            ? 'IGST'
            : 'CGST_SGST';
        foreach ($totals as &$amount) {
            $amount = round($amount, 2);
        }
        unset($amount);

        $cashAmount = 0.0;
        $upiAmount = 0.0;
        if ($paymentMethod === 'CASH') {
            $cashAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'UPI') {
            $upiAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'CASH_UPI') {
            $cashInput = filter_var($_POST['cash_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $upiInput = filter_var($_POST['upi_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($cashInput === false || $upiInput === false || $cashInput < 0 || $upiInput < 0) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Enter valid non-negative cash and UPI amounts.');
                return;
            }
            $cashAmount = round((float)$cashInput, 2);
            $upiAmount = round((float)$upiInput, 2);
            if (abs(($cashAmount + $upiAmount) - $totals['total_amount']) > 0.01) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Cash and UPI amounts must add up to the bill total.');
                return;
            }
        }

        $staffId = (int)($_POST['staff_id'] ?? (Auth::id() ?? 0));
        $staffName = trim((string)($_POST['staff_name'] ?? ''));
        if ($staffId > 0 && empty($staffName)) {
            $u = Database::fetchOne("SELECT full_name FROM users WHERE id = ?", [$staffId]);
            $staffName = $u['full_name'] ?? '';
        }
        $rawCashGiven = trim((string)($_POST['cash_given'] ?? ''));
        $rawChangeReturned = trim((string)($_POST['change_returned'] ?? ''));
        if ($rawCashGiven !== '') {
            $parsedCashGiven = filter_var($rawCashGiven, FILTER_VALIDATE_FLOAT);
            if ($parsedCashGiven === false || $parsedCashGiven < 0) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Cash given by customer must be a valid non-negative number.');
                return;
            }
            $cashGiven = round((float)$parsedCashGiven, 2);
        } else {
            $cashGiven = 0.0;
        }
        if ($rawChangeReturned !== '') {
            $parsedChangeReturned = filter_var($rawChangeReturned, FILTER_VALIDATE_FLOAT);
            if ($parsedChangeReturned === false || $parsedChangeReturned < 0) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Change to return must be a valid non-negative number.');
                return;
            }
            $changeReturned = round((float)$parsedChangeReturned, 2);
        } else {
            $changeReturned = 0.0;
        }

        $existingBill = $billId !== null
            ? Database::fetchOne("SELECT upi_payment_screenshot_url FROM production_bills WHERE id = ?", [$billId])
            : null;
        $upiPaymentScreenshotUrl = in_array($paymentMethod, ['UPI', 'CASH_UPI'], true)
            ? ($existingBill['upi_payment_screenshot_url'] ?? null)
            : null;
        if ($hasPaymentScreenshot) {
            $upiPaymentScreenshotUrl = CloudinaryService::upload(
                $paymentScreenshotFile,
                'grm_inventory/production/billing/payment_proofs',
                'image'
            );
            if (!$upiPaymentScreenshotUrl) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The GPay screenshot could not be saved. Please try again.');
                return;
            }
        }

        $paymentStatus = $paymentMethod === 'CREDIT' ? 'PENDING' : 'PAID';
        $isDirectBill = !empty($_POST['is_direct_bill']) ? 1 : 0;
        $header = [
            $productionUnitId,
            $buyerType,
            $companyName,
            $companyAddress,
            $companyPhone,
            $companyEmail,
            $companyGstin,
            $customerName,
            $customerPhone,
            $customerAddress,
            $customerCity,
            $customerState,
            $customerGstin,
            $billDate,
            $paymentMethod,
            $isDirectBill,
            $paymentStatus,
            $taxType,
            $cashAmount,
            $upiAmount,
            $upiReference,
            $upiPaymentScreenshotUrl,
            $totals['gross_amount'],
            $totals['discount_amount'],
            $totals['subtotal'],
            $totals['cgst_amount'],
            $totals['sgst_amount'],
            $totals['igst_amount'],
            $totals['tax_amount'],
            $totals['total_amount'],
            trim((string)($_POST['notes'] ?? '')),
            $cashGiven,
            $changeReturned,
            $staffId > 0 ? $staffId : null,
            $staffName ?: null,
        ];

        Database::beginTransaction();
        try {
            if ($billId === null) {
                $activeConcernId = currentConcernId();
                $activeConcern = currentConcern();
                $invPrefix = !empty($activeConcern['invoice_prefix']) ? rtrim($activeConcern['invoice_prefix'], '-') . '-' : 'PB-';
                $billNumber = $invPrefix . date('Y') . '-' . date('mdHis') . '-' . random_int(10, 99);
                Database::execute(
                    "INSERT INTO production_bills
                     (concern_id, bill_number, production_unit_id, buyer_type, company_name, company_address, company_phone, company_email, company_gstin,
                      customer_name, customer_phone, customer_address, customer_city, customer_state, customer_gstin,
                      bill_date, payment_method, is_direct_bill, payment_status, tax_type, cash_amount, upi_amount, upi_reference,
                      upi_payment_screenshot_url,
                      gross_amount, discount_amount, subtotal, cgst_amount, sgst_amount, igst_amount, tax_amount,
                      total_amount, notes, cash_given, change_returned, staff_id, staff_name, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$activeConcernId, $billNumber], $header, [(int)(Auth::id() ?? 0) ?: null])
                );
                $billId = (int)Database::lastInsertId();
            } else {
                Database::execute(
                    "UPDATE production_bills SET production_unit_id = ?, buyer_type = ?, company_name = ?, company_address = ?, company_phone = ?, company_email = ?,
                     company_gstin = ?, customer_name = ?, customer_phone = ?, customer_address = ?, customer_city = ?,
                     customer_state = ?, customer_gstin = ?, bill_date = ?, payment_method = ?, is_direct_bill = ?, payment_status = ?,
                     tax_type = ?, cash_amount = ?, upi_amount = ?, upi_reference = ?, upi_payment_screenshot_url = ?,
                     gross_amount = ?, discount_amount = ?,
                     subtotal = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, tax_amount = ?, total_amount = ?, notes = ?,
                     cash_given = ?, change_returned = ?, staff_id = ?, staff_name = ?
                     WHERE id = ?",
                    array_merge($header, [$billId])
                );
                Database::execute("DELETE FROM production_bill_items WHERE bill_id = ?", [$billId]);
                Database::execute("DELETE FROM production_bill_item_tags WHERE bill_id = ?", [$billId]);
            }

            foreach ($itemPayload as $item) {
                $stock = $item['stock'];
                $amounts = $item['amounts'];
                Database::execute(
                    "INSERT INTO production_bill_items
                     (bill_id, qc_item_id, variant_id, product_name, sku, size_name, color_name, quantity, mrp,
                      unit_price, is_complementary, hsn_code, discount_percentage, discount_amount, gst_percentage, gst_type, tax_input_type,
                      sgst_rate, cgst_rate, igst_rate, taxable_amount, cgst_amount, sgst_amount, igst_amount,
                      tax_amount, total_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $billId, $stock['qc_item_id'], $stock['variant_id'] ?: null,
                        $stock['product_name'], $stock['sku'], $stock['size_name'], $stock['color_name'],
                        $item['quantity'], $amounts['mrp'], $item['unit_price'],
                        $item['is_complementary'] ? 1 : 0, $item['hsn_code'],
                        $amounts['discount_percentage'],
                        $amounts['discount_amount'], $item['gst_percentage'], $item['gst_type'], $item['tax_input_type'],
                        $item['sgst_rate'], $item['cgst_rate'], $item['igst_rate'], $amounts['taxable_amount'],
                        $amounts['cgst_amount'], $amounts['sgst_amount'], $amounts['igst_amount'],
                        $amounts['tax_amount'], $amounts['total_amount'],
                    ]
                );
                $billItemId = (int)Database::lastInsertId();
                foreach ($item['tags'] as $tag) {
                    Database::execute(
                        "INSERT INTO production_bill_item_tags (bill_id, bill_item_id, qc_item_id, tag_code) VALUES (?, ?, ?, ?)",
                        [$billId, $billItemId, $stock['qc_item_id'], $tag]
                    );
                }

                // Inventory integration: Deduct stock from stock_balances using StockService
                // Only immediately deduct stock at POS counter for walk-in retail CUSTOMER bills or direct sales.
                // For B2B COMPANY bills, stock remains in production until packed into a batch and OUTWARD DISPATCHED.
                if ($buyerType === 'CUSTOMER' || $isDirectBill) {
                    $vId = (int)($stock['variant_id'] ?? 0);
                    if ($vId > 0 && $productionUnitId > 0) {
                        try {
                            $currentBal = StockService::getBalance($vId, 'PRODUCTION_UNIT', $productionUnitId, 'AVAILABLE');
                            $billQty = (float)$item['quantity'];
                            if ($currentBal < $billQty) {
                                // Ensure the manufactured finished goods balance exists in stock ledger
                                StockService::increase(
                                    $vId,
                                    'PRODUCTION_UNIT',
                                    $productionUnitId,
                                    'AVAILABLE',
                                    ($billQty - $currentBal),
                                    'QC_INSPECTION_PASSED',
                                    'qc_items',
                                    (int)$stock['qc_item_id'],
                                    (int)(Auth::id() ?? 0) ?: null,
                                    "Pre-bill sync for manufactured stock (Bill #{$billNumber})"
                                );
                            }
                            StockService::decrease(
                                $vId,
                                'PRODUCTION_UNIT',
                                $productionUnitId,
                                'AVAILABLE',
                                $billQty,
                                'PRODUCTION_SALE',
                                'production_bills',
                                $billId,
                                (int)(Auth::id() ?? 0) ?: null,
                                "Billed in Production Bill #{$billNumber}"
                            );
                        } catch (Throwable $se) {
                            error_log("StockService production sync note: " . $se->getMessage());
                        }
                    }
                }
            }

            // Link packing batch to this bill if billed from a packing batch
            $batchIdParam = (int)($_POST['production_batch_id'] ?? 0);
            if ($batchIdParam > 0) {
                Database::execute("UPDATE production_batches SET production_bill_id = ? WHERE id = ?", [$billId, $batchIdParam]);
            }

            Database::commit();
            Response::redirect('production/billing/view/' . $billId, 'success', $isNewBill ? 'Production bill created.' : 'Production bill saved.');
        } catch (Throwable $e) {
            Database::rollback();
            error_log('ProductionBillingController::persistBill failed: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'uniq_production_bill_tag')) {
                $message = 'A QR code was just billed in another transaction. Refresh inventory and try again.';
            } elseif (str_contains($e->getMessage(), 'Insufficient stock.')) {
                $message = 'Available stock changed while this bill was being saved. Refresh inventory and reduce the quantity before trying again.';
            } else {
                $message = 'Unable to save the production bill. Please verify the details and try again.';
            }
            Response::redirect((!$isNewBill && $billId) ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', $message);
        }
    }
}

if (!class_exists('ProductionBillingController', false)) {
    class_alias('AllmodulesBillingController', 'ProductionBillingController');
}
