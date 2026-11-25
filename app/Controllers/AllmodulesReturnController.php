<?php

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Models/WarehouseDamage.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';

class AllmodulesReturnController
{
    public function index(): void
    {
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $type = trim((string)($_GET['type'] ?? ''));
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));

        $where = ['1=1'];
        $params = [];

        // Multi-concern isolation
        if (function_exists('currentConcernId')) {
            $scopedConcernId = currentConcernId();
            if ($scopedConcernId !== null) {
                $where[] = '(pr.concern_id = ? OR pr.concern_id IS NULL)';
                $params[] = $scopedConcernId;
            }
        }

        if ($search !== '') {
            $where[] = '(pr.return_number LIKE ? OR pr.bill_number LIKE ? OR pr.customer_name LIKE ? OR pr.customer_phone LIKE ? OR EXISTS (SELECT 1 FROM production_return_items pri WHERE pri.return_id = pr.id AND (pri.tag_code LIKE ? OR pri.sku LIKE ? OR pri.product_name LIKE ?)))';
            $wild = '%' . $search . '%';
            $params = array_merge($params, [$wild, $wild, $wild, $wild, $wild, $wild, $wild]);
        }

        if ($status !== '') {
            $where[] = 'pr.status = ?';
            $params[] = $status;
        }

        if ($type !== '') {
            $where[] = 'pr.return_type = ?';
            $params[] = $type;
        }

        if ($dateFrom !== '') {
            $where[] = 'DATE(pr.created_at) >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'DATE(pr.created_at) <= ?';
            $params[] = $dateTo;
        }

        $sql = "SELECT pr.*, 
                       (SELECT COUNT(*) FROM production_return_items WHERE return_id = pr.id) AS item_count,
                       (SELECT COUNT(*) FROM production_return_items WHERE return_id = pr.id AND action_taken = 'PENDING_INSPECTION') AS pending_triage_count,
                       (SELECT COUNT(*) FROM production_return_items WHERE return_id = pr.id AND action_taken = 'RESTOCKED') AS restocked_count,
                       (SELECT COUNT(*) FROM production_return_items WHERE return_id = pr.id AND action_taken = 'DAMAGED') AS damaged_count
                FROM production_returns pr
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pr.id DESC";

        $returns = Database::fetchAll($sql, $params);

        // Stats summary
        $statsWhere = ['1=1'];
        $statsParams = [];
        if (function_exists('currentConcernId')) {
            $scopedConcernId = currentConcernId();
            if ($scopedConcernId !== null) {
                $statsWhere[] = '(concern_id = ? OR concern_id IS NULL)';
                $statsParams[] = $scopedConcernId;
            }
        }
        $statsWhereSql = implode(' AND ', $statsWhere);

        $allStats = Database::fetchOne(
            "SELECT COUNT(*) AS total_returns,
                    COALESCE(SUM(total_returned_qty), 0) AS total_pieces,
                    COALESCE(SUM(net_amount), 0) AS total_amount,
                    COALESCE(SUM(gst_reversal_amount), 0) AS total_gst_reversal
             FROM production_returns
             WHERE {$statsWhereSql}",
            $statsParams
        ) ?: [];

        // Additional triage stats
        $triageStats = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN pri.action_taken = 'PENDING_INSPECTION' THEN pri.quantity ELSE 0 END), 0) AS pending_pieces,
                COALESCE(SUM(CASE WHEN pri.action_taken = 'RESTOCKED' THEN pri.quantity ELSE 0 END), 0) AS restocked_pieces,
                COALESCE(SUM(CASE WHEN pri.action_taken = 'DAMAGED' THEN pri.quantity ELSE 0 END), 0) AS damaged_pieces
             FROM production_return_items pri
             JOIN production_returns pr ON pri.return_id = pr.id
             WHERE {$statsWhereSql}",
            $statsParams
        ) ?: [];

        $allStats = array_merge($allStats, $triageStats);

        Response::view('allmodules.returns.index', [
            'pageTitle'    => 'Customer Returns & Exchanges | Production Unit',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-returns',
            'returns'      => $returns,
            'stats'        => $allStats,
            'search'       => $search,
            'status'       => $status,
            'type'         => $type,
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
        ]);
    }

    public function create(): void
    {
        Response::view('allmodules.returns.create', [
            'pageTitle'    => 'Record Return & Exchange | Production Unit',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-returns',
        ]);
    }

    /**
     * Universal Live Scanner & Bill Lookup
     * Handles scanning:
     * 1. Individual piece tracking QR code (e.g., PRD2611-0001, KBD1126101)
     * 2. Variant Barcode / SKU / QR code
     * 3. Explicit invoice-number and customer-phone searches
     */
    public function ajaxLookupBill(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $lookupType = strtolower(trim((string)($_GET['type'] ?? 'scan')));
        if ($lookupType !== 'select' && $query === '') {
            Response::json(['success' => false, 'message' => 'Enter a value to search.']);
            return;
        }

        $scannedTag = null;
        $scannedSku = null;
        $matchedBillId = null;

        if ($lookupType === 'bill' || $lookupType === 'phone') {
            $where = [];
            $params = [];

            if ($lookupType === 'bill') {
                $where[] = 'pb.bill_number LIKE ?';
                $params[] = '%' . $query . '%';
            } else {
                $phoneDigits = preg_replace('/\D+/', '', $query);
                if (strlen($phoneDigits) < 7) {
                    Response::json(['success' => false, 'message' => 'Enter at least 7 phone digits to search.']);
                    return;
                }
                $phoneSuffix = strlen($phoneDigits) > 10 ? substr($phoneDigits, -10) : $phoneDigits;
                $customerPhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(pb.customer_phone, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
                $companyPhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(pb.company_phone, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
                $where[] = "({$customerPhone} LIKE ? OR {$companyPhone} LIKE ?)";
                $params[] = '%' . $phoneSuffix;
                $params[] = '%' . $phoneSuffix;
            }

            if (function_exists('currentConcernId') && currentConcernId() !== null) {
                $where[] = '(pb.concern_id = ? OR pb.concern_id IS NULL)';
                $params[] = currentConcernId();
            }

            $billOptions = Database::fetchAll(
                "SELECT pb.id, pb.bill_number, pb.bill_date, pb.customer_name, pb.company_name,
                        pb.customer_phone, pb.company_phone, pb.total_amount,
                        (SELECT pr.id FROM production_returns pr
                         WHERE pr.bill_id = pb.id AND pr.status <> 'CANCELLED'
                         ORDER BY pr.id DESC LIMIT 1) AS active_return_id,
                        (SELECT pr.return_number FROM production_returns pr
                         WHERE pr.bill_id = pb.id AND pr.status <> 'CANCELLED'
                         ORDER BY pr.id DESC LIMIT 1) AS active_return_number
                 FROM production_bills pb
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY pb.id DESC
                 LIMIT 50",
                $params
            );

            if (!$billOptions) {
                Response::json(['success' => false, 'message' => 'No invoices found for that search.']);
                return;
            }

            Response::json([
                'success' => true,
                'bill_options' => $billOptions,
            ]);
            return;
        }

        if ($lookupType === 'select') {
            $matchedBillId = (int)($_GET['bill_id'] ?? 0);
            if ($matchedBillId <= 0) {
                Response::json(['success' => false, 'message' => 'Select a valid invoice.']);
                return;
            }
        } else {
        // 1. Check if the scanned string is an individual piece tag code in production_bill_item_tags
        $tagRow = Database::fetchOne(
            "SELECT pbit.bill_id, pbit.tag_code, pbit.bill_item_id, pb.bill_number 
             FROM production_bill_item_tags pbit
             JOIN production_bills pb ON pbit.bill_id = pb.id
             WHERE UPPER(TRIM(pbit.tag_code)) = UPPER(?) LIMIT 1",
            [$query]
        );
        if ($tagRow) {
            $scannedTag = $tagRow['tag_code'];
            $matchedBillId = (int)$tagRow['bill_id'];
        } else {
            // Also check if tag was already recorded in previous production_return_items
            $retTagRow = Database::fetchOne(
                "SELECT pr.bill_id, pri.tag_code, pb.bill_number
                 FROM production_return_items pri
                 JOIN production_returns pr ON pri.return_id = pr.id
                 JOIN production_bills pb ON pr.bill_id = pb.id
                 WHERE UPPER(TRIM(pri.tag_code)) = UPPER(?) LIMIT 1",
                [$query]
            );
            if ($retTagRow) {
                $scannedTag = $retTagRow['tag_code'];
                $matchedBillId = (int)$retTagRow['bill_id'];
            }
        }

        // 2. If not a specific piece tag, check if it matches a product variant SKU, Barcode, or QR code in billed items
        if (!$matchedBillId) {
            $skuRow = Database::fetchOne(
                "SELECT pbi.bill_id, pbi.sku
                 FROM production_bill_items pbi
                 JOIN production_bills pb ON pbi.bill_id = pb.id
                 LEFT JOIN product_variants pv ON pbi.variant_id = pv.id
                 WHERE UPPER(TRIM(pbi.sku)) = UPPER(?) 
                    OR UPPER(TRIM(pv.sku)) = UPPER(?) 
                    OR UPPER(TRIM(pv.qr_code)) = UPPER(?) 
                    OR UPPER(TRIM(pv.barcode)) = UPPER(?)
                 ORDER BY pb.id DESC LIMIT 1",
                [$query, $query, $query, $query]
            );
            if ($skuRow) {
                $scannedSku = $skuRow['sku'];
                $matchedBillId = (int)$skuRow['bill_id'];
            }
        }

        // Keep exact bill-number scans supported; use the explicit search modes for phone lookup.
        if (!$matchedBillId) {
            $bill = Database::fetchOne(
                "SELECT id FROM production_bills 
                 WHERE bill_number = ?
                 ORDER BY id DESC LIMIT 1",
                [$query]
            );
            if ($bill) {
                $matchedBillId = (int)$bill['id'];
            }
        }
        }

        if (!$matchedBillId) {
            Response::json([
                'success' => false, 
                'message' => 'No production bill or sold piece found matching scan: ' . htmlspecialchars($query)
            ]);
            return;
        }

        $billSql = "SELECT * FROM production_bills WHERE id = ?";
        $billParams = [$matchedBillId];
        if (function_exists('currentConcernId') && currentConcernId() !== null) {
            $billSql .= " AND (concern_id = ? OR concern_id IS NULL)";
            $billParams[] = currentConcernId();
        }
        $bill = Database::fetchOne($billSql, $billParams);
        if (!$bill) {
            Response::json(['success' => false, 'message' => 'Production bill record could not be loaded.']);
            return;
        }

        $billId = (int)$bill['id'];
        $activeReturn = Database::fetchOne(
            "SELECT id, return_number FROM production_returns
             WHERE bill_id = ? AND status <> 'CANCELLED'
             ORDER BY id DESC LIMIT 1",
            [$billId]
        );
        if ($activeReturn) {
            Response::json([
                'success' => false,
                'message' => "This invoice already has a return/exchange recorded ({$activeReturn['return_number']}).",
            ]);
            return;
        }

        $billItems = Database::fetchAll(
            "SELECT * FROM production_bill_items WHERE bill_id = ? ORDER BY id",
            [$billId]
        );

        // Fetch piece tags belonging to this bill from production_bill_item_tags
        // UNION with any tags that were previously returned for this bill in production_return_items
        $tagRows = Database::fetchAll(
            "SELECT bill_item_id, tag_code, 0 as is_returned
             FROM production_bill_item_tags
             WHERE bill_id = ?
             UNION
             SELECT pri.bill_item_id, pri.tag_code, 1 as is_returned
             FROM production_return_items pri
             JOIN production_returns pr ON pr.id = pri.return_id
             WHERE pr.bill_id = ? AND pri.tag_code IS NOT NULL AND pri.tag_code <> ''",
            [$billId, $billId]
        );

        // Already returned tags for this bill
        $returnedTagRows = Database::fetchAll(
            "SELECT pri.tag_code 
             FROM production_return_items pri 
             JOIN production_returns pr ON pr.id = pri.return_id 
             WHERE pr.bill_id = ? AND pr.status <> 'CANCELLED'",
            [$billId]
        );
        $alreadyReturnedTags = array_fill_keys(array_map('strtoupper', array_column($returnedTagRows, 'tag_code')), true);

        // Already returned quantity per bill_item_id
        $returnedQtyRows = Database::fetchAll(
            "SELECT pri.bill_item_id, SUM(pri.quantity) as total_returned_qty
             FROM production_return_items pri
             JOIN production_returns pr ON pr.id = pri.return_id
             WHERE pr.bill_id = ? AND pr.status <> 'CANCELLED'
             GROUP BY pri.bill_item_id",
            [$billId]
        );
        $returnedQtyByItem = [];
        foreach ($returnedQtyRows as $rqr) {
            $returnedQtyByItem[(int)$rqr['bill_item_id']] = (int)$rqr['total_returned_qty'];
        }

        // Group tags by item ID, avoiding duplicates
        $seenTags = [];
        $tagsByItem = [];
        foreach ($tagRows as $t) {
            $tagCode = strtoupper(trim((string)$t['tag_code']));
            if (empty($tagCode) || isset($seenTags[$tagCode])) {
                continue;
            }
            $seenTags[$tagCode] = true;
            $isReturned = isset($alreadyReturnedTags[$tagCode]) || !empty($t['is_returned']);
            $tagsByItem[(int)$t['bill_item_id']][] = [
                'tag_code'    => $t['tag_code'],
                'is_returned' => $isReturned,
            ];
        }

        $totalBillPieces = 0;
        $totalReturnedPieces = 0;
        $hasReturnablePieces = false;

        foreach ($billItems as &$bi) {
            $itemId = (int)$bi['id'];
            $biTags = $tagsByItem[$itemId] ?? [];
            $bi['tags'] = $biTags;
            $qty = (int)$bi['quantity'];
            $totalBillPieces += $qty;

            $alreadyReturnedCount = $returnedQtyByItem[$itemId] ?? 0;
            $bi['returned_qty'] = $alreadyReturnedCount;
            $bi['remaining_qty'] = max(0, $qty - $alreadyReturnedCount);
            $totalReturnedPieces += min($qty, $alreadyReturnedCount);

            if (!empty($biTags)) {
                foreach ($biTags as $t) {
                    if (empty($t['is_returned'])) {
                        $hasReturnablePieces = true;
                        break;
                    }
                }
            } else {
                if ($bi['remaining_qty'] > 0) {
                    $hasReturnablePieces = true;
                }
            }
        }
        unset($bi);

        // Fetch previous returns for this bill
        $prevReturns = Database::fetchAll(
            "SELECT id, return_number, return_type, total_returned_qty, net_amount, status, created_at
             FROM production_returns
             WHERE bill_id = ? AND status <> 'CANCELLED'
             ORDER BY id DESC",
            [$billId]
        );

        Response::json([
            'success'            => true,
            'bill'               => $bill,
            'items'              => $billItems,
            'scanned_tag'        => $scannedTag,
            'scanned_sku'        => $scannedSku,
            'is_fully_returned'  => !$hasReturnablePieces,
            'total_bill_pieces'  => $totalBillPieces,
            'total_returned'     => $totalReturnedPieces,
            'previous_returns'   => $prevReturns,
        ]);
    }

    /**
     * Store Return & Exchange Record
     * Only 2 Flows:
     * 1. FULL_REFUND (Fully Refunded)
     * 2. PRODUCT_EXCHANGE (Return Product Options)
     * 
     * IMPORTANT: Returned goods are NOT directly added to active inventory here.
     * They are stored in Staging (PENDING_INSPECTION) so warehouse/production staff
     * can inspect them and choose: Add to Stock or Move to Damage Module.
     */
    public function store(): void
    {
        $billId = (int)($_POST['bill_id'] ?? 0);
        if ($billId <= 0) {
            Session::setFlash('error', 'Select a valid original production bill.');
            Response::redirect(url('production/returns/create'));
            return;
        }

        // Flow: Fully Refunded (REFUND) vs Return Product Options (EXCHANGE)
        $rawFlow = strtoupper(trim((string)($_POST['return_type'] ?? 'REFUND')));
        $returnType = ($rawFlow === 'EXCHANGE' || $rawFlow === 'PRODUCT_EXCHANGE') ? 'EXCHANGE' : 'REFUND';

        $refundMode = strtoupper(trim((string)($_POST['refund_mode'] ?? 'STORE_CREDIT')));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $exchangeNotes = trim((string)($_POST['exchange_notes'] ?? ''));
        if ($returnType === 'EXCHANGE' && !empty($exchangeNotes)) {
            $notes = trim("[Exchange Requirement]: " . $exchangeNotes . ($notes ? "\n" . $notes : ''));
        }

        // Selected tags and selected non-tagged items
        $selectedTags = $_POST['selected_tags'] ?? [];
        $selectedItemIds = $_POST['selected_items'] ?? [];
        $tagsData = $_POST['tags'] ?? [];
        $itemsData = $_POST['items'] ?? [];

        if (empty($selectedTags) && empty($selectedItemIds)) {
            Session::setFlash('error', 'Please select at least one garment piece to return.');
            Response::redirect(url('production/returns/create'));
            return;
        }

        Database::beginTransaction();
        try {
            $billSql = "SELECT * FROM production_bills WHERE id = ?";
            $billParams = [$billId];
            if (function_exists('currentConcernId') && currentConcernId() !== null) {
                $billSql .= " AND (concern_id = ? OR concern_id IS NULL)";
                $billParams[] = currentConcernId();
            }
            $bill = Database::fetchOne($billSql . " FOR UPDATE", $billParams);
            if (!$bill) {
                Database::rollBack();
                Session::setFlash('error', 'Original Production Bill not found in the active concern.');
                Response::redirect(url('production/returns/create'));
                return;
            }

            $existingReturn = Database::fetchOne(
                "SELECT id, return_number FROM production_returns
                 WHERE bill_id = ? AND status <> 'CANCELLED'
                 ORDER BY id DESC LIMIT 1 FOR UPDATE",
                [$billId]
            );
            if ($existingReturn) {
                Database::rollBack();
                Session::setFlash('error', "This bill already has a return/exchange record ({$existingReturn['return_number']}).");
                Response::redirect(url('production/returns/view/' . $existingReturn['id']));
                return;
            }

            $returnedTagRows = Database::fetchAll(
                "SELECT pri.tag_code
                 FROM production_return_items pri
                 JOIN production_returns pr ON pr.id = pri.return_id
                 WHERE pr.bill_id = ? AND pr.status <> 'CANCELLED'",
                [$billId]
            );
            $alreadyReturnedTags = array_fill_keys(array_map('strtoupper', array_column($returnedTagRows, 'tag_code')), true);

            $returnedQtyRows = Database::fetchAll(
                "SELECT pri.bill_item_id, SUM(pri.quantity) as total_returned_qty
                 FROM production_return_items pri
                 JOIN production_returns pr ON pr.id = pri.return_id
                 WHERE pr.bill_id = ? AND pr.status <> 'CANCELLED'
                 GROUP BY pri.bill_item_id",
                [$billId]
            );
            $returnedQtyByItem = [];
            foreach ($returnedQtyRows as $rqr) {
                $returnedQtyByItem[(int)$rqr['bill_item_id']] = (int)$rqr['total_returned_qty'];
            }

            $totalQty = 0;
            $subtotal = 0.0;
            $gstReversal = 0.0;
            $netAmount = 0.0;
            $itemsToInsert = [];

            // 1. Process strictly selected piece tags
            if (!empty($selectedTags) && is_array($selectedTags)) {
                foreach ($selectedTags as $tagCodeRaw) {
                    $tagCode = trim((string)$tagCodeRaw);
                    if ($tagCode === '') {
                        continue;
                    }

                    $upperTag = strtoupper($tagCode);
                    if (isset($alreadyReturnedTags[$upperTag])) {
                        Database::rollBack();
                        Session::setFlash('error', "Piece tag '{$tagCode}' has already been returned for this bill.");
                        Response::redirect(url('production/returns/create'));
                        return;
                    }

                    $tagInfo = $tagsData[$tagCode] ?? [];
                    $billItemId = (int)($tagInfo['bill_item_id'] ?? 0);

                    if ($billItemId <= 0) {
                        $pbitRow = Database::fetchOne("SELECT bill_item_id FROM production_bill_item_tags WHERE bill_id = ? AND tag_code = ?", [$billId, $tagCode]);
                        if ($pbitRow) {
                            $billItemId = (int)$pbitRow['bill_item_id'];
                        }
                    }

                    $bi = Database::fetchOne("SELECT * FROM production_bill_items WHERE id = ? AND bill_id = ?", [$billItemId, $billId]);
                    if (!$bi) {
                        Database::rollBack();
                        Session::setFlash('error', "Item for tag '{$tagCode}' not found on this bill.");
                        Response::redirect(url('production/returns/create'));
                        return;
                    }

                    $reason = trim((string)($tagInfo['reason'] ?? ($returnType === 'EXCHANGE' ? 'Size/Product Exchange' : 'Customer Return')));

                    $unitPrice = (float)$bi['unit_price'];
                    $gstPct = (float)$bi['gst_percentage'];
                    $itemTaxable = round($unitPrice, 2);
                    $itemTax = round($itemTaxable * ($gstPct / 100), 2);
                    $itemTotal = round($itemTaxable + $itemTax, 2);

                    $totalQty += 1;
                    $subtotal += $itemTaxable;
                    $gstReversal += $itemTax;
                    $netAmount += $itemTotal;

                    // Items start as PENDING_INSPECTION - not directly in stock
                    $itemsToInsert[] = [
                        'bill_item_id'   => (int)$bi['id'],
                        'qc_item_id'     => (int)$bi['qc_item_id'],
                        'variant_id'     => (int)($bi['variant_id'] ?? 0) ?: null,
                        'product_name'   => $bi['product_name'],
                        'sku'            => $bi['sku'],
                        'hsn_code'       => $bi['hsn_code'] ?? '6109',
                        'tag_code'       => $tagCode,
                        'quantity'       => 1,
                        'unit_price'     => $unitPrice,
                        'gst_percentage' => $gstPct,
                        'taxable_amount' => $itemTaxable,
                        'tax_amount'     => $itemTax,
                        'total_amount'   => $itemTotal,
                        'reason'         => $reason,
                        'item_condition' => 'GOOD',
                        'action_taken'   => 'PENDING_INSPECTION',
                    ];
                }
            }

            // 2. Process non-tagged items
            if (!empty($selectedItemIds) && is_array($selectedItemIds)) {
                foreach ($selectedItemIds as $billItemIdRaw) {
                    $billItemId = (int)$billItemIdRaw;
                    if ($billItemId <= 0) {
                        continue;
                    }

                    $bi = Database::fetchOne("SELECT * FROM production_bill_items WHERE id = ? AND bill_id = ?", [$billItemId, $billId]);
                    if (!$bi) {
                        continue;
                    }

                    $itemInfo = $itemsData[$billItemId] ?? [];
                    $alreadyReturned = $returnedQtyByItem[$billItemId] ?? 0;
                    $remainingQty = max(0, (int)$bi['quantity'] - $alreadyReturned);

                    $requestedQty = max(1, (int)($itemInfo['qty'] ?? 1));
                    if ($requestedQty > $remainingQty) {
                        Database::rollBack();
                        Session::setFlash('error', "Return quantity ({$requestedQty}) exceeds remaining returnable quantity ({$remainingQty}) for item '{$bi['product_name']}'.");
                        Response::redirect(url('production/returns/create'));
                        return;
                    }

                    $reason = trim((string)($itemInfo['reason'] ?? ($returnType === 'EXCHANGE' ? 'Size/Product Exchange' : 'Customer Return')));

                    $unitPrice = (float)$bi['unit_price'];
                    $gstPct = (float)$bi['gst_percentage'];
                    $itemTaxable = round($unitPrice * $requestedQty, 2);
                    $itemTax = round($itemTaxable * ($gstPct / 100), 2);
                    $itemTotal = round($itemTaxable + $itemTax, 2);

                    $totalQty += $requestedQty;
                    $subtotal += $itemTaxable;
                    $gstReversal += $itemTax;
                    $netAmount += $itemTotal;

                    $itemsToInsert[] = [
                        'bill_item_id'   => (int)$bi['id'],
                        'qc_item_id'     => (int)$bi['qc_item_id'],
                        'variant_id'     => (int)($bi['variant_id'] ?? 0) ?: null,
                        'product_name'   => $bi['product_name'],
                        'sku'            => $bi['sku'],
                        'hsn_code'       => $bi['hsn_code'] ?? '6109',
                        'tag_code'       => null,
                        'quantity'       => $requestedQty,
                        'unit_price'     => $unitPrice,
                        'gst_percentage' => $gstPct,
                        'taxable_amount' => $itemTaxable,
                        'tax_amount'     => $itemTax,
                        'total_amount'   => $itemTotal,
                        'reason'         => $reason,
                        'item_condition' => 'GOOD',
                        'action_taken'   => 'PENDING_INSPECTION',
                    ];
                }
            }

            if ($totalQty === 0) {
                Database::rollBack();
                Session::setFlash('error', 'No valid items selected for return.');
                Response::redirect(url('production/returns/create'));
                return;
            }

            // Generate Return Number: PR-RET-YYYY-XXXX (or PR-CRN-YYYY-XXXX)
            $count = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_returns")['c'] ?? 0);
            $prefix = ($returnType === 'EXCHANGE') ? 'PR-EXC-' : 'PR-RET-';
            $returnNumber = $prefix . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

            Database::execute(
                "INSERT INTO production_returns 
                 (return_number, bill_id, bill_number, production_unit_id, concern_id, customer_name, customer_phone, return_type, disposition,
                  total_returned_qty, subtotal, gst_reversal_amount, net_amount, status, refund_mode, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING_INSPECTION', ?, ?, ?, ?, 'PENDING_INSPECTION', ?, ?, ?)",
                [
                    $returnNumber,
                    $billId,
                    $bill['bill_number'],
                    $bill['production_unit_id'] ?? null,
                    $bill['concern_id'] ?? 1,
                    $bill['customer_name'] ?: ($bill['company_name'] ?? 'B2B Client'),
                    $bill['customer_phone'] ?: ($bill['company_phone'] ?? ''),
                    $returnType,
                    $totalQty,
                    $subtotal,
                    $gstReversal,
                    $netAmount,
                    $refundMode,
                    $notes,
                    (int)(Auth::id() ?? 0) ?: null,
                ]
            );
            $returnId = (int)Database::lastInsertId();

            foreach ($itemsToInsert as $ins) {
                Database::execute(
                    "INSERT INTO production_return_items
                     (return_id, bill_item_id, qc_item_id, variant_id, product_name, sku, hsn_code, tag_code,
                      quantity, unit_price, gst_percentage, taxable_amount, tax_amount, total_amount,
                      reason, item_condition, action_taken)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $returnId, $ins['bill_item_id'], $ins['qc_item_id'], $ins['variant_id'],
                        $ins['product_name'], $ins['sku'], $ins['hsn_code'], $ins['tag_code'],
                        $ins['quantity'], $ins['unit_price'], $ins['gst_percentage'],
                        $ins['taxable_amount'], $ins['tax_amount'], $ins['total_amount'],
                        $ins['reason'], $ins['item_condition'], $ins['action_taken'],
                    ]
                );
            }

            // NOTE: We DO NOT delete from production_bill_item_tags and DO NOT add to stock here!
            // Items are held in Staging until the warehouse manager inspects them and decides Add to Stock vs Damage.

            Database::commit();
            $flowLabel = ($returnType === 'EXCHANGE') ? 'Product Exchange' : 'Fully Refunded Return';
            Session::setFlash('success', "{$flowLabel} #{$returnNumber} recorded successfully! {$totalQty} piece(s) held in Return Staging. Please inspect below to Add to Stock or Move to Damage.");
            Response::redirect(url('production/returns/view/' . $returnId));
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('ProductionReturnController::store failed: ' . $e->getMessage());
            Session::setFlash('error', 'Error processing return: ' . $e->getMessage());
            Response::redirect(url('production/returns/create'));
        }
    }

    /**
     * Triage Single Return Item:
     * - Decision: 'ADD_TO_STOCK' -> Restocks into active inventory, releases tag code
     * - Decision: 'DAMAGE_MODULE' -> Logs case in warehouse_damages and quarantines stock
     */
    public function triageItem(): void
    {
        $returnId = (int)($_POST['return_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $decision = strtoupper(trim((string)($_POST['decision'] ?? '')));
        $damageReason = trim((string)($_POST['damage_reason'] ?? ''));
        $defectNotes = trim((string)($_POST['defect_notes'] ?? ''));

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || (!empty($_GET['ajax']));

        $return = Database::fetchOne("SELECT * FROM production_returns WHERE id = ?", [$returnId]);
        if (!$return) {
            $msg = 'Return record not found.';
            if ($isAjax) { Response::json(['success' => false, 'message' => $msg]); return; }
            Session::setFlash('error', $msg);
            Response::redirect(url('production/returns'));
            return;
        }

        $item = Database::fetchOne("SELECT * FROM production_return_items WHERE id = ? AND return_id = ?", [$itemId, $returnId]);
        if (!$item) {
            $msg = 'Return item record not found.';
            if ($isAjax) { Response::json(['success' => false, 'message' => $msg]); return; }
            Session::setFlash('error', $msg);
            Response::redirect(url('production/returns/view/' . $returnId));
            return;
        }

        if ($item['action_taken'] !== 'PENDING_INSPECTION') {
            $msg = 'This piece has already been triaged (' . $item['action_taken'] . ').';
            if ($isAjax) { Response::json(['success' => false, 'message' => $msg]); return; }
            Session::setFlash('warning', $msg);
            Response::redirect(url('production/returns/view/' . $returnId));
            return;
        }

        Database::beginTransaction();
        try {
            $variantId = (int)($item['variant_id'] ?? 0);
            $prodUnitId = (int)($return['production_unit_id'] ?? 0);
            if ($prodUnitId <= 0 && $return['bill_id']) {
                $pb = Database::fetchOne("SELECT production_unit_id FROM production_bills WHERE id = ?", [$return['bill_id']]);
                $prodUnitId = (int)($pb['production_unit_id'] ?? 1);
            }
            if ($prodUnitId <= 0) {
                $prodUnitId = 1;
            }

            if ($decision === 'ADD_TO_STOCK') {
                // 1. Release piece tag so it can be scanned and billed again
                if (!empty($item['tag_code']) && $return['bill_id']) {
                    Database::execute(
                        "DELETE FROM production_bill_item_tags WHERE bill_id = ? AND tag_code = ?",
                        [$return['bill_id'], $item['tag_code']]
                    );
                }

                // 2. Add back to AVAILABLE stock via Central StockService
                if ($variantId > 0) {
                    try {
                        StockService::increase(
                            $variantId,
                            'PRODUCTION_UNIT',
                            $prodUnitId,
                            'AVAILABLE',
                            (float)$item['quantity'],
                            'PRODUCTION_RETURN',
                            'production_returns',
                            $returnId,
                            (int)(Auth::id() ?? 0) ?: null,
                            "Returned garment added to stock from Return #{$return['return_number']}" . (!empty($item['tag_code']) ? " (Tag: {$item['tag_code']})" : "")
                        );
                    } catch (Throwable $se) {
                        error_log("StockService::increase failed in return triage: " . $se->getMessage());
                    }
                }

                Database::execute(
                    "UPDATE production_return_items 
                     SET action_taken = 'RESTOCKED', item_condition = 'GOOD', triaged_at = NOW(), triaged_by = ?
                     WHERE id = ?",
                    [(int)(Auth::id() ?? 0) ?: null, $itemId]
                );

                $msg = "Garment '{$item['product_name']}' successfully verified and added to active stock!";

            } elseif ($decision === 'DAMAGE_MODULE') {
                // 1. Get warehouse for damage routing
                $whId = function_exists('currentWarehouseId') ? (currentWarehouseId() ?: 1) : 1;
                if ($whId <= 0) {
                    $wh = Database::fetchOne("SELECT id FROM warehouses LIMIT 1");
                    $whId = (int)($wh['id'] ?? 1);
                }

                $reason = $damageReason !== '' ? $damageReason : 'Customer Return Defect';
                $notes = $defectNotes !== '' ? $defectNotes : ("Defective return from Return #{$return['return_number']}" . (!empty($item['tag_code']) ? " (Tag: {$item['tag_code']})" : ""));

                // 2. Create damage record in warehouse_damages
                $damageId = WarehouseDamage::create([
                    'warehouse_id'       => $whId,
                    'variant_id'         => $variantId > 0 ? $variantId : 1,
                    'tag_code'           => !empty($item['tag_code']) ? $item['tag_code'] : null,
                    'source_type'        => 'CUSTOMER_RETURN',
                    'reference_id'       => $returnId,
                    'quantity'           => (int)$item['quantity'],
                    'cost_per_unit'      => (float)$item['unit_price'],
                    'total_loss'         => (float)$item['total_amount'],
                    'damage_reason'      => $reason,
                    'defect_notes'       => $notes,
                    'status'             => 'LOGGED',
                    'reported_by'        => (int)(Auth::id() ?? 1),
                    'production_unit_id' => $prodUnitId,
                ]);

                // 3. Track in DAMAGED stock state
                if ($variantId > 0) {
                    try {
                        StockService::increase(
                            $variantId,
                            'PRODUCTION_UNIT',
                            $prodUnitId,
                            'DAMAGED',
                            (float)$item['quantity'],
                            'PRODUCTION_RETURN',
                            'production_returns',
                            $returnId,
                            (int)(Auth::id() ?? 0) ?: null,
                            "Customer return quarantined to Damage Module (Case #{$damageId})"
                        );
                    } catch (Throwable $se) {
                        error_log("StockService::increase DAMAGED in return triage: " . $se->getMessage());
                    }
                }

                Database::execute(
                    "UPDATE production_return_items 
                     SET action_taken = 'DAMAGED', item_condition = 'DAMAGED', damage_id = ?, triaged_at = NOW(), triaged_by = ?
                     WHERE id = ?",
                    [$damageId, (int)(Auth::id() ?? 0) ?: null, $itemId]
                );

                $msg = "Garment '{$item['product_name']}' routed to Damage Module (Case #{$damageId})!";
            } else {
                throw new InvalidArgumentException("Invalid triage decision: '{$decision}'");
            }

            // Check if all items in this return are triaged
            $remaining = (int)(Database::fetchOne(
                "SELECT COUNT(*) AS c FROM production_return_items WHERE return_id = ? AND action_taken = 'PENDING_INSPECTION'",
                [$returnId]
            )['c'] ?? 0);

            if ($remaining === 0) {
                $hasRestocked = Database::fetchOne("SELECT id FROM production_return_items WHERE return_id = ? AND action_taken = 'RESTOCKED' LIMIT 1", [$returnId]);
                $hasDamaged = Database::fetchOne("SELECT id FROM production_return_items WHERE return_id = ? AND action_taken = 'DAMAGED' LIMIT 1", [$returnId]);
                $finalDisp = ($hasRestocked && $hasDamaged) ? 'MIXED' : ($hasDamaged ? 'SCRAP_DAMAGE' : 'RESTOCK_INVENTORY');

                Database::execute(
                    "UPDATE production_returns SET status = 'COMPLETED', disposition = ?, updated_at = NOW() WHERE id = ?",
                    [$finalDisp, $returnId]
                );
            }

            Database::commit();

            if ($isAjax) {
                Response::json([
                    'success'   => true, 
                    'message'   => $msg, 
                    'remaining' => $remaining,
                    'decision'  => $decision,
                    'damage_id' => $damageId ?? null
                ]);
                return;
            }

            Session::setFlash('success', $msg);
            Response::redirect(url('production/returns/view/' . $returnId));
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('ProductionReturnController::triageItem failed: ' . $e->getMessage());
            $errMsg = 'Error triaging item: ' . $e->getMessage();
            if ($isAjax) { Response::json(['success' => false, 'message' => $errMsg]); return; }
            Session::setFlash('error', $errMsg);
            Response::redirect(url('production/returns/view/' . $returnId));
        }
    }

    /**
     * Bulk Triage:
     * Restock All Pending or Move All Pending to Damage
     */
    public function bulkTriage(): void
    {
        $returnId = (int)($_POST['return_id'] ?? 0);
        $decision = strtoupper(trim((string)($_POST['decision'] ?? 'ADD_TO_STOCK')));
        $damageReason = trim((string)($_POST['damage_reason'] ?? 'Bulk Customer Return Defect'));
        $defectNotes = trim((string)($_POST['defect_notes'] ?? ''));

        $return = Database::fetchOne("SELECT * FROM production_returns WHERE id = ?", [$returnId]);
        if (!$return) {
            Session::setFlash('error', 'Return record not found.');
            Response::redirect(url('production/returns'));
            return;
        }

        $pendingItems = Database::fetchAll(
            "SELECT * FROM production_return_items WHERE return_id = ? AND action_taken = 'PENDING_INSPECTION'",
            [$returnId]
        );

        if (empty($pendingItems)) {
            Session::setFlash('info', 'No pending items found in staging for this return.');
            Response::redirect(url('production/returns/view/' . $returnId));
            return;
        }

        Database::beginTransaction();
        try {
            $prodUnitId = (int)($return['production_unit_id'] ?? 0);
            if ($prodUnitId <= 0 && $return['bill_id']) {
                $pb = Database::fetchOne("SELECT production_unit_id FROM production_bills WHERE id = ?", [$return['bill_id']]);
                $prodUnitId = (int)($pb['production_unit_id'] ?? 1);
            }
            if ($prodUnitId <= 0) {
                $prodUnitId = 1;
            }

            $count = 0;
            foreach ($pendingItems as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $itemId = (int)$item['id'];

                if ($decision === 'ADD_TO_STOCK') {
                    if (!empty($item['tag_code']) && $return['bill_id']) {
                        Database::execute(
                            "DELETE FROM production_bill_item_tags WHERE bill_id = ? AND tag_code = ?",
                            [$return['bill_id'], $item['tag_code']]
                        );
                    }
                    if ($variantId > 0) {
                        try {
                            StockService::increase(
                                $variantId,
                                'PRODUCTION_UNIT',
                                $prodUnitId,
                                'AVAILABLE',
                                (float)$item['quantity'],
                                'PRODUCTION_RETURN',
                                'production_returns',
                                $returnId,
                                (int)(Auth::id() ?? 0) ?: null,
                                "Bulk restocked from Return #{$return['return_number']}"
                            );
                        } catch (Throwable $e) {}
                    }
                    Database::execute(
                        "UPDATE production_return_items 
                         SET action_taken = 'RESTOCKED', item_condition = 'GOOD', triaged_at = NOW(), triaged_by = ?
                         WHERE id = ?",
                        [(int)(Auth::id() ?? 0) ?: null, $itemId]
                    );
                } else {
                    $whId = function_exists('currentWarehouseId') ? (currentWarehouseId() ?: 1) : 1;
                    $damageId = WarehouseDamage::create([
                        'warehouse_id'       => $whId,
                        'variant_id'         => $variantId > 0 ? $variantId : 1,
                        'tag_code'           => !empty($item['tag_code']) ? $item['tag_code'] : null,
                        'source_type'        => 'CUSTOMER_RETURN',
                        'reference_id'       => $returnId,
                        'quantity'           => (int)$item['quantity'],
                        'cost_per_unit'      => (float)$item['unit_price'],
                        'total_loss'         => (float)$item['total_amount'],
                        'damage_reason'      => $damageReason,
                        'defect_notes'       => $defectNotes ?: "Bulk return inspection damage",
                        'status'             => 'LOGGED',
                        'reported_by'        => (int)(Auth::id() ?? 1),
                        'production_unit_id' => $prodUnitId,
                    ]);
                    if ($variantId > 0) {
                        try {
                            StockService::increase(
                                $variantId,
                                'PRODUCTION_UNIT',
                                $prodUnitId,
                                'DAMAGED',
                                (float)$item['quantity'],
                                'PRODUCTION_RETURN',
                                'production_returns',
                                $returnId,
                                (int)(Auth::id() ?? 0) ?: null,
                                "Customer return bulk quarantined to Damage Module"
                            );
                        } catch (Throwable $e) {}
                    }
                    Database::execute(
                        "UPDATE production_return_items 
                         SET action_taken = 'DAMAGED', item_condition = 'DAMAGED', damage_id = ?, triaged_at = NOW(), triaged_by = ?
                         WHERE id = ?",
                        [$damageId, (int)(Auth::id() ?? 0) ?: null, $itemId]
                    );
                }
                $count++;
            }

            // Finalize status
            $finalDisp = ($decision === 'ADD_TO_STOCK') ? 'RESTOCK_INVENTORY' : 'SCRAP_DAMAGE';
            Database::execute(
                "UPDATE production_returns SET status = 'COMPLETED', disposition = ?, updated_at = NOW() WHERE id = ?",
                [$finalDisp, $returnId]
            );

            Database::commit();
            $actionLabel = ($decision === 'ADD_TO_STOCK') ? 'added to stock' : 'routed to Damage Module';
            Session::setFlash('success', "All {$count} pending piece(s) successfully {$actionLabel}!");
            Response::redirect(url('production/returns/view/' . $returnId));
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('ProductionReturnController::bulkTriage failed: ' . $e->getMessage());
            Session::setFlash('error', 'Error in bulk triage: ' . $e->getMessage());
            Response::redirect(url('production/returns/view/' . $returnId));
        }
    }

    public function view(string $id): void
    {
        $returnId = (int)$id;
        $return = Database::fetchOne("SELECT * FROM production_returns WHERE id = ?", [$returnId]);
        if (!$return) {
            Session::setFlash('error', 'Return / Credit Note record not found.');
            Response::redirect(url('production/returns'));
            return;
        }

        $items = Database::fetchAll("SELECT * FROM production_return_items WHERE return_id = ? ORDER BY id", [$returnId]);
        $bill = $return['bill_id'] ? Database::fetchOne("SELECT * FROM production_bills WHERE id = ?", [$return['bill_id']]) : null;

        $pendingCount = 0;
        $restockedCount = 0;
        $damagedCount = 0;
        foreach ($items as $it) {
            if ($it['action_taken'] === 'PENDING_INSPECTION') {
                $pendingCount += (int)$it['quantity'];
            } elseif ($it['action_taken'] === 'RESTOCKED') {
                $restockedCount += (int)$it['quantity'];
            } elseif ($it['action_taken'] === 'DAMAGED') {
                $damagedCount += (int)$it['quantity'];
            }
        }

        Response::view('allmodules.returns.view', [
            'pageTitle'      => 'Return ' . $return['return_number'],
            'activeModule'   => 'production',
            'activeMenu'     => 'prod-returns',
            'return'         => $return,
            'items'          => $items,
            'bill'           => $bill,
            'pendingCount'   => $pendingCount,
            'restockedCount' => $restockedCount,
            'damagedCount'   => $damagedCount,
        ]);
    }

    public function print(string $id): void
    {
        $returnId = (int)$id;
        $return = Database::fetchOne("SELECT * FROM production_returns WHERE id = ?", [$returnId]);
        if (!$return) {
            Session::setFlash('error', 'Return / Credit Note not found.');
            Response::redirect(url('production/returns'));
            return;
        }

        $items = Database::fetchAll("SELECT * FROM production_return_items WHERE return_id = ? ORDER BY id", [$returnId]);
        $bill = $return['bill_id'] ? Database::fetchOne("SELECT * FROM production_bills WHERE id = ?", [$return['bill_id']]) : null;

        Response::viewOnly('allmodules.returns.print', [
            'return'        => $return,
            'items'         => $items,
            'bill'          => $bill,
            'sellerCompany' => SettingsService::getGroup('company'),
            'prodUnit'      => ProductionUnit::getActiveFacility($return['production_unit_id'] ?? null),
        ]);
    }
}

if (!class_exists('ProductionReturnController', false)) {
    class_alias('AllmodulesReturnController', 'ProductionReturnController');
}
