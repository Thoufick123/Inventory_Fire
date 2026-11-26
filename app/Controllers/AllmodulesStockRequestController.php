<?php

class AllmodulesStockRequestController
{
    /**
     * Standard module and facility roster for inter-module requirements
     */
    public const AVAILABLE_MODULES = [
        'Central Warehouse (Main Logistics Hub)'  => 'Central Warehouse',
        'Production Factory - Unit 1'             => 'Production Factory',
        'Retail Showroom / POS Store'             => 'Retail Showroom',
        'Cutting Section'                         => 'Cutting Section',
        'Stitching Unit'                          => 'Stitching Unit',
        'Quality Control (QC) Department'         => 'Quality Control',
        'Finishing & Packing Section'             => 'Finishing & Packing',
        'Raw Materials & Fabric Store'            => 'Fabric Store',
        'WhatsApp B2B Sales Hub'                  => 'WhatsApp Sales Hub',
    ];

    /**
     * Retrieve all identity names associated with a concern (concern name, warehouses, production units)
     */
    private function getConcernIdentityNames(?int $concernId): array
    {
        if (!$concernId) return [];
        $names = [];
        $c = Database::fetchOne("SELECT name FROM concerns WHERE id = ?", [$concernId]);
        if ($c && !empty($c['name'])) {
            $names[] = trim($c['name']);
        }
        $whs = Database::fetchAll("SELECT name FROM warehouses WHERE concern_id = ?", [$concernId]);
        foreach ($whs as $w) {
            if (!empty($w['name'])) $names[] = trim($w['name']);
        }
        $pus = Database::fetchAll("SELECT name FROM production_units WHERE concern_id = ?", [$concernId]);
        foreach ($pus as $p) {
            if (!empty($p['name'])) $names[] = trim($p['name']);
        }
        return array_values(array_unique(array_filter($names)));
    }

    /**
     * Display all Stock Requirements and Requisitions (Separated by Requests Sent vs Requests Received)
     */
    public function index(): void
    {
        $activeTab   = strtolower(trim((string)($_GET['tab'] ?? 'sent')));
        if ($activeTab !== 'received') {
            $activeTab = 'sent';
        }

        $status      = trim((string)($_GET['status'] ?? ''));
        $priority    = trim((string)($_GET['priority'] ?? ''));
        $fromModule  = trim((string)($_GET['from_module'] ?? ''));
        $toModule    = trim((string)($_GET['to_module'] ?? ''));
        $search      = trim((string)($_GET['search'] ?? ''));

        $cid = currentConcernId();
        $identityNames = $this->getConcernIdentityNames($cid);

        // Build Sent Base Condition & Params (where active concern is Requester / Who is Asking)
        $sentCond = '1=1';
        $sentParams = [];
        if ($cid) {
            $sentParts = ['psr.concern_id = ?', 'psr.from_unit_id = ?'];
            $sentParams[] = $cid;
            $sentParams[] = $cid;
            if ($cid === 1) {
                $sentParts[] = 'psr.concern_id IS NULL';
            }
            if (!empty($identityNames)) {
                $placeholders = implode(',', array_fill(0, count($identityNames), '?'));
                $sentParts[] = "psr.from_module IN ($placeholders)";
                $sentParams = array_merge($sentParams, $identityNames);
            }
            $sentCond = '(' . implode(' OR ', $sentParts) . ')';
        }

        // Build Received Base Condition & Params (where active concern is Fulfill / Send Area Being Asked)
        $receivedCond = '1=1';
        $receivedParams = [];
        if ($cid) {
            $recParts = ['psr.to_unit_id = ?'];
            $receivedParams[] = $cid;
            if (!empty($identityNames)) {
                $placeholders = implode(',', array_fill(0, count($identityNames), '?'));
                $recParts[] = "psr.to_module IN ($placeholders)";
                $recParts[] = "psr.warehouse_name IN ($placeholders)";
                $receivedParams = array_merge($receivedParams, $identityNames, $identityNames);
            }
            $receivedCond = '(' . implode(' OR ', $recParts) . ')';
        }

        // Compute Counts for Tab Badges
        $sentCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE $sentCond",
            $sentParams
        )['c'] ?? 0);

        $receivedCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE $receivedCond",
            $receivedParams
        )['c'] ?? 0);

        // Pending received requests count (useful for action notifications)
        $pendingReceivedCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE psr.status = 'PENDING' AND $receivedCond",
            $receivedParams
        )['c'] ?? 0);

        // Select the active base condition & params based on $activeTab
        if ($activeTab === 'received') {
            $baseCond = $receivedCond;
            $baseParams = $receivedParams;
        } else {
            $baseCond = $sentCond;
            $baseParams = $sentParams;
        }

        $where = [$baseCond];
        $params = $baseParams;

        if ($status !== '') {
            $where[] = 'psr.status = ?';
            $params[] = $status;
        }

        if ($priority !== '') {
            $where[] = 'psr.priority = ?';
            $params[] = $priority;
        }

        if ($fromModule !== '') {
            $where[] = 'psr.from_module = ?';
            $params[] = $fromModule;
        }

        if ($toModule !== '') {
            $where[] = 'psr.to_module = ?';
            $params[] = $toModule;
        }

        if ($search !== '') {
            $where[] = '(psr.request_number LIKE ? OR psr.from_module LIKE ? OR psr.to_module LIKE ? OR psr.requested_by_name LIKE ? OR psr.notes LIKE ?)';
            $wild = '%' . $search . '%';
            $params = array_merge($params, [$wild, $wild, $wild, $wild, $wild]);
        }

        $requests = Database::fetchAll(
            "SELECT psr.*,
                    (SELECT COUNT(*) FROM production_stock_request_items WHERE request_id = psr.id) AS item_count
             FROM production_stock_requests psr
             WHERE " . implode(' AND ', $where) . "
             ORDER BY CASE psr.status 
                        WHEN 'PENDING' THEN 1 
                        WHEN 'RECEIVED_BY_SEND_AREA' THEN 2 
                        WHEN 'APPROVED' THEN 3 
                        WHEN 'IN_PRODUCTION' THEN 4 
                        WHEN 'READY_FOR_DISPATCH' THEN 5 
                        WHEN 'DISPATCHED' THEN 6 
                        WHEN 'FULFILLED' THEN 7 
                        ELSE 8 END,
                      psr.id DESC",
            $params
        );

        $stats = [
            'total_requests'          => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE $baseCond", $baseParams)['c'] ?? 0),
            'pending_receipt_count'   => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE psr.status = 'PENDING' AND $baseCond", $baseParams)['c'] ?? 0),
            'received_send_area_count'=> (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE psr.status = 'RECEIVED_BY_SEND_AREA' AND $baseCond", $baseParams)['c'] ?? 0),
            'dispatched_count'        => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE psr.status = 'DISPATCHED' AND $baseCond", $baseParams)['c'] ?? 0),
            'fulfilled_count'         => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests psr WHERE psr.status = 'FULFILLED' AND $baseCond", $baseParams)['c'] ?? 0),
            'total_requested_pieces'  => (int)(Database::fetchOne("SELECT COALESCE(SUM(psr.total_requested_pieces), 0) AS s FROM production_stock_requests psr WHERE $baseCond", $baseParams)['s'] ?? 0),
            'total_dispatched_pieces' => (int)(Database::fetchOne("SELECT COALESCE(SUM(psr.dispatched_pieces), 0) AS s FROM production_stock_requests psr WHERE $baseCond", $baseParams)['s'] ?? 0),
            'total_fulfilled_pieces'  => (int)(Database::fetchOne("SELECT COALESCE(SUM(psr.fulfilled_pieces), 0) AS s FROM production_stock_requests psr WHERE $baseCond", $baseParams)['s'] ?? 0),
        ];

        Response::view('allmodules.stock_requests.index', [
            'pageTitle'            => 'Stock Requirements & Inter-Module Requests | Production Unit',
            'activeModule'         => 'production',
            'activeMenu'           => 'prod-stock-requests',
            'activeTab'            => $activeTab,
            'sentCount'            => $sentCount,
            'receivedCount'        => $receivedCount,
            'pendingReceivedCount' => $pendingReceivedCount,
            'requests'             => $requests,
            'stats'                => $stats,
            'status'               => $status,
            'priority'             => $priority,
            'fromModule'           => $fromModule,
            'toModule'             => $toModule,
            'search'               => $search,
            'modulesList'          => self::AVAILABLE_MODULES,
        ]);
    }

    /**
     * Show form to create a new Stock Requirement (asking another module)
     */
    public function create(): void
    {
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY name ASC");

        $products = Database::fetchAll(
            "SELECT p.id, p.name, p.code, p.category_id, c.name AS category_name, p.material, p.hsn_code 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.is_active = 1 
             ORDER BY p.name ASC"
        );

        $variants = Database::fetchAll(
            "SELECT pv.id, pv.product_id, pv.variant_code, s.name AS size_name, c.name AS color_name, d.name AS design_name, pv.mrp, pv.selling_price
             FROM product_variants pv
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN designs d ON pv.design_id = d.id
             WHERE pv.is_active = 1
             ORDER BY pv.id ASC"
        );

        $activeConcern = currentConcern();
        $activeConcernId = currentConcernId() ?: 1;
        if (!$activeConcern || empty($activeConcern['id'])) {
            $activeConcern = Database::fetchOne("SELECT id, name, code, concern_type FROM concerns WHERE id = ?", [$activeConcernId]) ?: [
                'id'           => 1,
                'name'         => 'GRM Apparels',
                'code'         => 'CN-01',
                'concern_type' => 'HEAD_OFFICE'
            ];
        }

        $allConcerns = Database::fetchAll("SELECT id, name, code, concern_type FROM concerns WHERE is_active = 1 ORDER BY name ASC");
        $fulfillingConcerns = array_values(array_filter($allConcerns, function($c) use ($activeConcernId) {
            return (int)$c['id'] !== (int)$activeConcernId;
        }));

        $productionUnits = Database::fetchAll("SELECT id, name, code, concern_id FROM production_units WHERE is_active = 1");
        $warehouses = Database::fetchAll("SELECT id, name, code, concern_id FROM warehouses WHERE is_active = 1");

        Response::view('allmodules.stock_requests.create', [
            'pageTitle'          => 'Create New Stock Requirement | Inter-Module Flow',
            'activeModule'       => 'production',
            'activeMenu'         => 'prod-stock-requests',
            'categories'         => $categories,
            'products'           => $products,
            'variants'           => $variants,
            'activeConcern'      => $activeConcern,
            'activeConcernId'    => $activeConcernId,
            'fulfillingConcerns' => $fulfillingConcerns,
            'allConcerns'        => $allConcerns,
            'productionUnits'    => $productionUnits,
            'warehouses'         => $warehouses,
            'modulesList'        => self::AVAILABLE_MODULES,
        ]);
    }

    /**
     * AJAX endpoint to return live stock for a product or variant in both requester and fulfilling concerns
     */
    public function ajaxGetStock(): void
    {
        $variantId     = (int)($_GET['variant_id'] ?? 0);
        $productId     = (int)($_GET['product_id'] ?? 0);
        $fromConcernId = (int)($_GET['from_concern_id'] ?? 0);
        $toConcernId   = (int)($_GET['to_concern_id'] ?? 0);

        if (!$fromConcernId) {
            $fromConcernId = currentConcernId() ?: 1;
        }

        $requesterStock = $this->calculateStockForConcern($productId, $variantId, $fromConcernId);
        $fulfillingStock = $toConcernId > 0 ? $this->calculateStockForConcern($productId, $variantId, $toConcernId) : 0;

        Response::json([
            'success'          => true,
            'requester_stock'  => $requesterStock,
            'fulfilling_stock' => $fulfillingStock,
        ]);
    }

    /**
     * AJAX endpoint to scan a Product QR, Variant tag, or SKU
     */
    public function ajaxScanProduct(): void
    {
        $code = strtoupper(trim((string)($_GET['code'] ?? '')));
        $code = ltrim($code, '#');
        $codePrefix = explode('-', $code)[0];
        $varId = 0;
        if (preg_match('/^V0*([1-9]\d*)/i', $code, $m)) {
            $varId = (int)$m[1];
        }

        $sql = "
            SELECT pv.id as variant_id, pv.product_id, pv.variant_code,
                   p.name as product_name, p.category_id,
                   c.name as category_name, s.name as size_name, cl.name as color_name
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN sizes s ON pv.size_id = s.id
            LEFT JOIN colors cl ON pv.color_id = cl.id
            WHERE UPPER(TRIM(pv.variant_code)) = ? 
               OR UPPER(TRIM(pv.qr_code)) = ?
               OR UPPER(TRIM(pv.variant_code)) = ?
        ";
        $params = [$code, $code, $codePrefix];
        if ($varId > 0) {
            $sql .= " OR pv.id = ?";
            $params[] = $varId;
        }
        $sql .= " LIMIT 1";

        $variant = Database::fetchOne($sql, $params);

        if ($variant) {
            Response::json([
                'success'       => true,
                'is_variant'    => true,
                'variant_id'    => (int)$variant['variant_id'],
                'product_id'    => (int)$variant['product_id'],
                'variant_code'  => $variant['variant_code'] ?: ('V' . str_pad($variant['variant_id'], 4, '0', STR_PAD_LEFT)),
                'product_name'  => $variant['product_name'],
                'category_id'   => $variant['category_id'] ? (int)$variant['category_id'] : null,
                'category_name' => $variant['category_name'] ?: 'Apparel',
                'size_name'     => $variant['size_name'],
                'color_name'    => $variant['color_name'],
            ]);
            return;
        }

        // Check by product code or name
        $prod = Database::fetchOne("
            SELECT p.id as product_id, p.name as product_name, p.code as product_code, p.category_id,
                   c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE UPPER(TRIM(p.code)) = ? OR UPPER(TRIM(p.name)) = ?
            LIMIT 1
        ", [$code, $code]);

        if ($prod) {
            Response::json([
                'success'       => true,
                'is_variant'    => false,
                'product_id'    => (int)$prod['product_id'],
                'variant_id'    => null,
                'product_name'  => $prod['product_name'],
                'product_code'  => $prod['product_code'],
                'category_id'   => $prod['category_id'] ? (int)$prod['category_id'] : null,
                'category_name' => $prod['category_name'] ?: 'Apparel',
            ]);
            return;
        }

        Response::json(['success' => false, 'message' => "Product or Variant QR '{$code}' was not found."], 404);
    }

    private function calculateStockForConcern(int $productId, int $variantId, int $concernId): float
    {
        $stock = 0.0;
        if ($variantId > 0) {
            $stock = (float)(Database::fetchColumn("
                SELECT COALESCE(SUM(sb.quantity), 0)
                FROM stock_balances sb
                LEFT JOIN warehouses w ON sb.location_type = 'WAREHOUSE' AND sb.location_id = w.id
                LEFT JOIN production_units pu ON sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id = pu.id
                WHERE sb.variant_id = ?
                  AND sb.stock_state = 'AVAILABLE'
                  AND (sb.concern_id = ? OR w.concern_id = ? OR pu.concern_id = ?)
            ", [$variantId, $concernId, $concernId, $concernId]) ?? 0);

            if ($stock <= 0) {
                $stock = (float)(Database::fetchColumn("
                    SELECT COALESCE(SUM(COALESCE(NULLIF(pbi.received_qty, 0), pbi.quantity)), 0)
                    FROM production_batch_items pbi
                    JOIN production_batches pb ON pb.id = pbi.batch_id
                    WHERE pbi.variant_id = ? 
                      AND (pb.concern_id = ? OR pb.destination_warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?))
                ", [$variantId, $concernId, $concernId]) ?? 0);
            }
        } elseif ($productId > 0) {
            $stock = (float)(Database::fetchColumn("
                SELECT COALESCE(SUM(sb.quantity), 0)
                FROM stock_balances sb
                JOIN product_variants pv ON pv.id = sb.variant_id
                LEFT JOIN warehouses w ON sb.location_type = 'WAREHOUSE' AND sb.location_id = w.id
                LEFT JOIN production_units pu ON sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id = pu.id
                WHERE pv.product_id = ?
                  AND sb.stock_state = 'AVAILABLE'
                  AND (sb.concern_id = ? OR w.concern_id = ? OR pu.concern_id = ?)
            ", [$productId, $concernId, $concernId, $concernId]) ?? 0);

            if ($stock <= 0) {
                $stock = (float)(Database::fetchColumn("
                    SELECT COALESCE(SUM(COALESCE(NULLIF(pbi.received_qty, 0), pbi.quantity)), 0)
                    FROM production_batch_items pbi
                    JOIN production_batches pb ON pb.id = pbi.batch_id
                    WHERE pbi.product_id = ? 
                      AND (pb.concern_id = ? OR pb.destination_warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?))
                ", [$productId, $concernId, $concernId]) ?? 0);
            }
        }

        return max(0, $stock);
    }

    /**
     * Store new Stock Requirement in database
     */
    public function store(): void
    {
        $activeConcern = currentConcern();
        $activeConcernId = currentConcernId() ?: 1;
        $defaultFrom = ($activeConcern && !empty($activeConcern['name'])) ? $activeConcern['name'] : 'Main Business Concern';

        $fromModule     = trim((string)($_POST['from_module'] ?? $defaultFrom));
        if (empty($fromModule)) $fromModule = $defaultFrom;
        $toModule       = trim((string)($_POST['to_module'] ?? ''));
        $fromConcernId  = (int)($_POST['from_concern_id'] ?? $activeConcernId);
        $toConcernId    = (int)($_POST['to_concern_id'] ?? 0);
        $requestedByName= trim((string)($_POST['requested_by_name'] ?? (Auth::user()['full_name'] ?? 'Staff Incharge')));
        $priority       = strtoupper(trim((string)($_POST['priority'] ?? 'NORMAL')));
        $requiredByDate = !empty($_POST['required_by_date']) ? $_POST['required_by_date'] : null;
        $notes          = trim((string)($_POST['notes'] ?? ''));

        if (empty($toModule)) {
            Session::setFlash('error', 'Please choose a fulfilling concern/send area to request stock from.');
            Response::redirect(url('production/stock-requests/create'));
            return;
        }

        if ($fromModule === $toModule || ($fromConcernId > 0 && $toConcernId > 0 && $fromConcernId === $toConcernId)) {
            Session::setFlash('error', 'Requesting concern and Fulfilling Send Area concern cannot be the same. Please choose distinct concerns.');
            Response::redirect(url('production/stock-requests/create'));
            return;
        }

        $items = $_POST['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            Session::setFlash('error', 'Please specify at least one product style and asked count.');
            Response::redirect(url('production/stock-requests/create'));
            return;
        }

        Database::beginTransaction();
        try {
            $totalPieces = 0;
            $itemsToInsert = [];

            foreach ($items as $it) {
                $qty = (int)($it['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $productId = (int)($it['product_id'] ?? 0);
                $variantId = !empty($it['variant_id']) ? (int)$it['variant_id'] : null;

                $prod = $productId > 0 ? Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]) : null;
                $productName = $prod ? $prod['name'] : trim((string)($it['product_name'] ?? 'Garment Style'));
                $sizeName = trim((string)($it['size_name'] ?? ''));
                $colorName = trim((string)($it['color_name'] ?? ''));

                if ($variantId) {
                    $v = Database::fetchOne(
                        "SELECT pv.*, s.name AS size_name, c.name AS color_name, d.name AS design_name 
                         FROM product_variants pv 
                         LEFT JOIN sizes s ON pv.size_id = s.id 
                         LEFT JOIN colors c ON pv.color_id = c.id 
                         LEFT JOIN designs d ON pv.design_id = d.id 
                         WHERE pv.id = ?",
                        [$variantId]
                    );
                    if ($v) {
                        $sizeName = $v['size_name'] ?: $sizeName;
                        $colorName = $v['color_name'] ?: $colorName;
                    }
                }

                $totalPieces += $qty;
                $itemsToInsert[] = [
                    'product_id'   => $productId ?: null,
                    'variant_id'   => $variantId,
                    'product_name' => $productName,
                    'size_name'    => $sizeName,
                    'color_name'   => $colorName,
                    'requested_qty'=> $qty,
                ];
            }

            if ($totalPieces <= 0) {
                Database::rollBack();
                Session::setFlash('error', 'Total asked product count must be greater than zero.');
                Response::redirect(url('production/stock-requests/create'));
                return;
            }

            $count = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM production_stock_requests")['c'] ?? 0);
            $reqNumber = 'STK-REQ-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

            $cid = currentConcernId();

            if ($toConcernId <= 0 && !empty($toModule)) {
                $cFound = Database::fetchOne("SELECT id FROM concerns WHERE name = ? LIMIT 1", [$toModule]);
                if ($cFound) {
                    $toConcernId = (int)$cFound['id'];
                } else {
                    $wFound = Database::fetchOne("SELECT concern_id FROM warehouses WHERE name = ? LIMIT 1", [$toModule]);
                    if ($wFound && !empty($wFound['concern_id'])) {
                        $toConcernId = (int)$wFound['concern_id'];
                    } else {
                        $pFound = Database::fetchOne("SELECT concern_id FROM production_units WHERE name = ? LIMIT 1", [$toModule]);
                        if ($pFound && !empty($pFound['concern_id'])) {
                            $toConcernId = (int)$pFound['concern_id'];
                        }
                    }
                }
            }

            Database::execute(
                "INSERT INTO production_stock_requests
                 (concern_id, request_number, from_module, to_module, warehouse_name, requested_by_name, priority, required_by_date,
                  total_requested_pieces, fulfilled_pieces, dispatched_pieces, status, notes, created_by, created_at, to_unit_id, from_unit_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'PENDING', ?, ?, NOW(), ?, ?)",
                [
                    $fromConcernId ?: $cid,
                    $reqNumber,
                    $fromModule,
                    $toModule,
                    $toModule,
                    $requestedByName,
                    $priority,
                    $requiredByDate,
                    $totalPieces,
                    $notes,
                    (int)(Auth::id() ?? 0) ?: null,
                    $toConcernId > 0 ? $toConcernId : null,
                    $fromConcernId > 0 ? $fromConcernId : ($cid ?: 1),
                ]
            );
            $reqId = (int)Database::lastInsertId();

            foreach ($itemsToInsert as $ins) {
                Database::execute(
                    "INSERT INTO production_stock_request_items
                     (request_id, product_id, variant_id, product_name, size_name, color_name, requested_qty, dispatched_qty, received_qty, fulfilled_qty, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 'PENDING', NOW())",
                    [
                        $reqId, $ins['product_id'], $ins['variant_id'], $ins['product_name'],
                        $ins['size_name'], $ins['color_name'], $ins['requested_qty']
                    ]
                );
            }

            Database::commit();
            Session::setFlash('success', "Stock Requirement #{$reqNumber} created successfully for {$totalPieces} pieces. Awaiting receipt by Send Area ({$toModule}).");
            Response::redirect(url('production/stock-requests/view/' . $reqId));
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('ProductionStockRequestController::store error: ' . $e->getMessage());
            Session::setFlash('error', 'Error creating stock requirement: ' . $e->getMessage());
            Response::redirect(url('production/stock-requests/create'));
        }
    }

    /**
     * View detailed Stock Requirement & Execute Workflow Steps
     */
    public function view(string $id): void
    {
        $reqId = (int)$id;
        $req = Database::fetchOne("SELECT * FROM production_stock_requests WHERE id = ?", [$reqId]);
        if (!$req) {
            Session::setFlash('error', 'Stock requirement not found.');
            Response::redirect(url('production/stock-requests'));
            return;
        }

        $items = Database::fetchAll("SELECT * FROM production_stock_request_items WHERE request_id = ? ORDER BY id ASC", [$reqId]);

        // Calculate available inventory in Send Area and pending counts for each line item
        foreach ($items as &$it) {
            $availableStock = 0;

            if (!empty($it['variant_id'])) {
                $vid = (int)$it['variant_id'];

                // 1. Check stock_balances
                $sb = Database::fetchOne(
                    "SELECT COALESCE(SUM(quantity), 0) AS qty FROM stock_balances WHERE variant_id = ? AND stock_state = 'AVAILABLE'",
                    [$vid]
                );
                $availableStock = (int)($sb['qty'] ?? 0);

                // 2. If 0, check warehouse_qr_codes
                if ($availableStock <= 0) {
                    $qr = Database::fetchOne(
                        "SELECT COUNT(*) AS c FROM warehouse_qr_codes WHERE variant_id = ?",
                        [$vid]
                    );
                    $availableStock = (int)($qr['c'] ?? 0);
                }

                // 3. If 0, check QC passed items
                if ($availableStock <= 0) {
                    $qc = Database::fetchOne(
                        "SELECT COALESCE(SUM(passed_qty), 0) AS passed FROM qc_items WHERE variant_id = ?",
                        [$vid]
                    );
                    $availableStock = (int)($qc['passed'] ?? 0);
                }
            } elseif (!empty($it['product_id'])) {
                $pid = (int)$it['product_id'];
                $qc = Database::fetchOne(
                    "SELECT COALESCE(SUM(qi.passed_qty), 0) AS passed 
                     FROM qc_items qi 
                     JOIN qc_batches qb ON qb.id = qi.qc_batch_id 
                     JOIN production_programs pp ON pp.id = qb.program_id 
                     WHERE pp.product_id = ?",
                    [$pid]
                );
                $availableStock = (int)($qc['passed'] ?? 0);
            }

            $it['available_stock'] = $availableStock;
            $it['remaining_to_send'] = max(0, (int)$it['requested_qty'] - (int)($it['dispatched_qty'] ?? 0));
            $it['remaining_to_receive'] = max(0, (int)($it['dispatched_qty'] ?? 0) - (int)($it['received_qty'] ?? 0));
        }
        unset($it);

        // Determine workflow step active index
        // Step 1: Asked / Pending Send Area Receipt
        // Step 2: Received by Send Area (Ready to Send)
        // Step 3: Stocks Sent / Dispatched (In Transit)
        // Step 4: Stocks Confirmed Received & Fulfilled
        $workflowStep = 1;
        if ($req['status'] === 'RECEIVED_BY_SEND_AREA' || $req['status'] === 'APPROVED' || $req['status'] === 'IN_PRODUCTION' || $req['status'] === 'READY_FOR_DISPATCH') {
            $workflowStep = 2;
        } elseif ($req['status'] === 'DISPATCHED') {
            $workflowStep = 3;
        } elseif ($req['status'] === 'FULFILLED') {
            $workflowStep = 4;
        }

        Response::view('allmodules.stock_requests.view', [
            'pageTitle'    => 'Requirement #' . $req['request_number'] . ' | ' . e($req['from_module']) . ' -> ' . e($req['to_module']),
            'activeModule' => 'production',
            'activeMenu'   => 'prod-stock-requests',
            'req'          => $req,
            'items'        => $items,
            'workflowStep' => $workflowStep,
        ]);
    }

    /**
     * STEP 2: Send Area Receives and Acknowledges the Asked Product Counts
     * ("that count recive the send area")
     */
    public function receiveBySendArea(string $id): void
    {
        $reqId = (int)$id;
        $req = Database::fetchOne("SELECT * FROM production_stock_requests WHERE id = ?", [$reqId]);
        if (!$req) {
            Session::setFlash('error', 'Stock requirement not found.');
            Response::redirect(url('production/stock-requests'));
            return;
        }

        if ($req['status'] !== 'PENDING') {
            Session::setFlash('warning', 'This requirement has already been acknowledged by the Send Area (Status: ' . $req['status'] . ').');
            Response::redirect(url('production/stock-requests/view/' . $reqId));
            return;
        }

        $userName = Auth::user()['full_name'] ?? 'Send Area Staff';
        $timestamp = date('d M Y, h:i A');
        $ackNote = "[{$timestamp}] Acknowledged & Received by Send Area ({$req['to_module']}) by {$userName}. Asked product count of {$req['total_requested_pieces']} pcs confirmed.";

        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE production_stock_requests 
                 SET status = 'RECEIVED_BY_SEND_AREA',
                     received_by_send_area_at = NOW(),
                     received_by_send_area_by = ?,
                     notes = CASE WHEN notes != '' AND notes IS NOT NULL THEN CONCAT(notes, '\n', ?) ELSE ? END,
                     updated_at = NOW()
                 WHERE id = ?",
                [$userName, $ackNote, $ackNote, $reqId]
            );

            Database::execute(
                "UPDATE production_stock_request_items 
                 SET status = 'RECEIVED_BY_SEND_AREA' 
                 WHERE request_id = ? AND status = 'PENDING'",
                [$reqId]
            );

            Database::commit();
            Session::setFlash('success', "Send Area ({$req['to_module']}) has officially received and acknowledged the requirement for {$req['total_requested_pieces']} pcs. You can now dispatch the asked stocks.");
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('receiveBySendArea error: ' . $e->getMessage());
            Session::setFlash('error', 'Failed to acknowledge requirement: ' . $e->getMessage());
        }

        Response::redirect(url('production/stock-requests/view/' . $reqId));
    }

    /**
     * STEP 3: Send Area Dispatches / Sends the Asked Stocks
     * ("then only send the asked stocks give it")
     */
    public function sendStocks(string $id): void
    {
        $reqId = (int)$id;
        $req = Database::fetchOne("SELECT * FROM production_stock_requests WHERE id = ?", [$reqId]);
        if (!$req) {
            Session::setFlash('error', 'Stock requirement not found.');
            Response::redirect(url('production/stock-requests'));
            return;
        }

        // STRICT BUSINESS RULE: Must be received by Send Area first before sending!
        if ($req['status'] === 'PENDING') {
            Session::setFlash('error', 'Stocks CANNOT be sent yet! The Send Area must first receive and acknowledge the asked count before dispatching.');
            Response::redirect(url('production/stock-requests/view/' . $reqId));
            return;
        }

        $itemsInput = $_POST['items'] ?? [];
        $dispatchNotes = trim((string)($_POST['dispatch_notes'] ?? ''));
        $userName = Auth::user()['full_name'] ?? 'Dispatcher';

        if (empty($itemsInput) || !is_array($itemsInput)) {
            Session::setFlash('error', 'Please specify the quantities to dispatch for each line item.');
            Response::redirect(url('production/stock-requests/view/' . $reqId));
            return;
        }

        Database::beginTransaction();
        try {
            $totalSentThisBatch = 0;
            $items = Database::fetchAll("SELECT * FROM production_stock_request_items WHERE request_id = ?", [$reqId]);

            foreach ($items as $item) {
                $itemId = $item['id'];
                $sendQty = isset($itemsInput[$itemId]['send_qty']) ? max(0, (int)$itemsInput[$itemId]['send_qty']) : 0;

                if ($sendQty <= 0) {
                    continue;
                }

                $newDispatched = (int)($item['dispatched_qty'] ?? 0) + $sendQty;
                $newStatus = ($newDispatched >= (int)$item['requested_qty']) ? 'DISPATCHED' : 'PARTIAL';

                Database::execute(
                    "UPDATE production_stock_request_items 
                     SET dispatched_qty = ?, status = ? 
                     WHERE id = ?",
                    [$newDispatched, $newStatus, $itemId]
                );

                $totalSentThisBatch += $sendQty;
            }

            if ($totalSentThisBatch <= 0) {
                Database::rollBack();
                Session::setFlash('error', 'Quantity to dispatch must be greater than zero.');
                Response::redirect(url('production/stock-requests/view/' . $reqId));
                return;
            }

            // Recalculate totals on parent request
            $totals = Database::fetchOne(
                "SELECT COALESCE(SUM(dispatched_qty), 0) AS total_sent, 
                        COALESCE(SUM(requested_qty), 0) AS total_asked 
                 FROM production_stock_request_items WHERE request_id = ?",
                [$reqId]
            );

            $allSent = (int)$totals['total_sent'];
            $allAsked = (int)$totals['total_asked'];
            $reqStatus = ($allSent >= $allAsked) ? 'DISPATCHED' : 'READY_FOR_DISPATCH';

            $timestamp = date('d M Y, h:i A');
            $dispatchLog = "[{$timestamp}] Sent {$totalSentThisBatch} pcs by {$userName} from {$req['to_module']}. " . ($dispatchNotes ? "Ref/Notes: {$dispatchNotes}" : '');

            Database::execute(
                "UPDATE production_stock_requests 
                 SET status = ?,
                     dispatched_pieces = ?,
                     dispatched_at = NOW(),
                     dispatched_by = ?,
                     notes = CASE WHEN notes != '' AND notes IS NOT NULL THEN CONCAT(notes, '\n', ?) ELSE ? END,
                     updated_at = NOW()
                 WHERE id = ?",
                [$reqStatus, $allSent, $userName, $dispatchLog, $dispatchLog, $reqId]
            );

            Database::commit();
            Session::setFlash('success', "Successfully sent {$totalSentThisBatch} pieces of asked stock to {$req['from_module']}. Status updated to {$reqStatus}.");
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('sendStocks error: ' . $e->getMessage());
            Session::setFlash('error', 'Failed to dispatch stocks: ' . $e->getMessage());
        }

        Response::redirect(url('production/stock-requests/view/' . $reqId));
    }

    /**
     * STEP 4: Requesting Module Confirms Stocks Received
     * ("that count receive the send area then only send the asked stocks give it")
     */
    public function confirmReceived(string $id): void
    {
        $reqId = (int)$id;
        $req = Database::fetchOne("SELECT * FROM production_stock_requests WHERE id = ?", [$reqId]);
        if (!$req) {
            Session::setFlash('error', 'Stock requirement not found.');
            Response::redirect(url('production/stock-requests'));
            return;
        }

        if ($req['status'] === 'PENDING' || $req['status'] === 'RECEIVED_BY_SEND_AREA') {
            Session::setFlash('error', 'Cannot confirm receipt before stocks are dispatched by the Send Area.');
            Response::redirect(url('production/stock-requests/view/' . $reqId));
            return;
        }

        $itemsInput = $_POST['items'] ?? [];
        $receivingNotes = trim((string)($_POST['receiving_notes'] ?? ''));
        $userName = Auth::user()['full_name'] ?? 'Receiving Incharge';

        Database::beginTransaction();
        try {
            $totalReceivedThisBatch = 0;
            $items = Database::fetchAll("SELECT * FROM production_stock_request_items WHERE request_id = ?", [$reqId]);

            foreach ($items as $item) {
                $itemId = $item['id'];
                $recQty = isset($itemsInput[$itemId]['received_qty']) ? max(0, (int)$itemsInput[$itemId]['received_qty']) : 0;

                if ($recQty <= 0) {
                    continue;
                }

                $newReceived = (int)($item['received_qty'] ?? 0) + $recQty;
                $newStatus = ($newReceived >= (int)$item['requested_qty']) ? 'FULFILLED' : 'PARTIAL';

                Database::execute(
                    "UPDATE production_stock_request_items 
                     SET received_qty = ?, fulfilled_qty = ?, status = ? 
                     WHERE id = ?",
                    [$newReceived, $newReceived, $newStatus, $itemId]
                );

                $totalReceivedThisBatch += $recQty;
            }

            if ($totalReceivedThisBatch <= 0) {
                Database::rollBack();
                Session::setFlash('error', 'Received quantity must be greater than zero.');
                Response::redirect(url('production/stock-requests/view/' . $reqId));
                return;
            }

            // Recalculate totals
            $totals = Database::fetchOne(
                "SELECT COALESCE(SUM(received_qty), 0) AS total_rec, 
                        COALESCE(SUM(requested_qty), 0) AS total_asked 
                 FROM production_stock_request_items WHERE request_id = ?",
                [$reqId]
            );

            $allRec = (int)$totals['total_rec'];
            $allAsked = (int)$totals['total_asked'];
            $reqStatus = ($allRec >= $allAsked) ? 'FULFILLED' : 'READY_FOR_DISPATCH';

            $timestamp = date('d M Y, h:i A');
            $receiveLog = "[{$timestamp}] Received & confirmed {$totalReceivedThisBatch} pcs by {$userName} at {$req['from_module']}. " . ($receivingNotes ? "Notes: {$receivingNotes}" : '');

            Database::execute(
                "UPDATE production_stock_requests 
                 SET status = ?,
                     fulfilled_pieces = ?,
                     received_at = NOW(),
                     received_by = ?,
                     notes = CASE WHEN notes != '' AND notes IS NOT NULL THEN CONCAT(notes, '\n', ?) ELSE ? END,
                     updated_at = NOW()
                 WHERE id = ?",
                [$reqStatus, $allRec, $userName, $receiveLog, $receiveLog, $reqId]
            );

            Database::commit();
            Session::setFlash('success', "Stocks confirmed received! {$totalReceivedThisBatch} pieces verified. Requirement is now marked as {$reqStatus}.");
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('confirmReceived error: ' . $e->getMessage());
            Session::setFlash('error', 'Failed to confirm receipt: ' . $e->getMessage());
        }

        Response::redirect(url('production/stock-requests/view/' . $reqId));
    }

    /**
     * Administrative generic status update
     */
    public function updateStatus(string $id): void
    {
        $reqId = (int)$id;
        $newStatus = strtoupper(trim((string)($_POST['status'] ?? '')));
        $fulfilledPieces = max(0, (int)($_POST['fulfilled_pieces'] ?? 0));
        $notes = trim((string)($_POST['notes'] ?? ''));

        $validStatuses = ['PENDING', 'RECEIVED_BY_SEND_AREA', 'APPROVED', 'IN_PRODUCTION', 'READY_FOR_DISPATCH', 'DISPATCHED', 'FULFILLED', 'REJECTED'];
        if (!in_array($newStatus, $validStatuses, true)) {
            Session::setFlash('error', 'Invalid requisition status.');
            Response::redirect(url('production/stock-requests/view/' . $reqId));
            return;
        }

        Database::execute(
            "UPDATE production_stock_requests 
             SET status = ?, 
                 fulfilled_pieces = CASE WHEN ? > 0 THEN ? ELSE fulfilled_pieces END,
                 notes = CASE WHEN ? != '' THEN CONCAT(COALESCE(notes, ''), '\n', ?) ELSE notes END,
                 updated_at = NOW()
             WHERE id = ?",
            [$newStatus, $fulfilledPieces, $fulfilledPieces, $notes, $notes, $reqId]
        );

        Session::setFlash('success', "Stock requirement status updated to {$newStatus}.");
        Response::redirect(url('production/stock-requests/view/' . $reqId));
    }
}

if (!class_exists('ProductionStockRequestController', false)) {
    class_alias('AllmodulesStockRequestController', 'ProductionStockRequestController');
}
