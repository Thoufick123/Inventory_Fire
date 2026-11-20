<?php
/**
 * QrGenerationController
 * Dedicated Controller for Manual and Catalog-linked QR Code Generations.
 */

require_once BASE_PATH . '/app/Services/QrGenerationService.php';
require_once BASE_PATH . '/app/Services/PurchaseMappingFulfillmentService.php';
require_once BASE_PATH . '/app/Models/Design.php';

class QrGenerationController {

    /**
     * List all generated QR batches.
     */
    public function index(): void {
        QrGenerationService::ensureSourceSchema();
        PurchaseMappingFulfillmentService::ensureSchema();

        $tabs = QrGenerationService::sourceTabs();
        $activeTab = QrGenerationService::normalizeTab($_GET['tab'] ?? null);

        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'brand_name'  => trim($_GET['brand_name'] ?? ''),
            'category_id' => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'type'        => trim($_GET['type'] ?? ''),
            'qr_source'   => $tabs[$activeTab]['source'],
        ];

        $batches = QrGenerationService::listBatches($filters);
        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

        $cid = currentConcernId();
        $brandSql = "SELECT DISTINCT brand_name
                     FROM qr_generations
                     WHERE brand_name IS NOT NULL
                       AND brand_name != ''
                       AND UPPER(COALESCE(qr_source, '')) <> 'OWN_PRODUCT'";
        $brandParams = [];
        if ($cid) {
            $brandSql .= ($cid === 1) ? " AND (concern_id = ? OR concern_id IS NULL)" : " AND concern_id = ?";
            $brandParams[] = $cid;
        }
        $brandSql .= " ORDER BY brand_name ASC";
        $brands = Database::fetchAll($brandSql, $brandParams);

        $tabCounts = [];
        foreach ($tabs as $key => $tab) {
            $countSql = "SELECT COUNT(*) AS c FROM qr_generations WHERE qr_source = ?";
            $countParams = [$tab['source']];
            if ($cid) {
                $countSql .= ($cid === 1) ? " AND (concern_id = ? OR concern_id IS NULL)" : " AND concern_id = ?";
                $countParams[] = $cid;
            }
            $tabCounts[$key] = (int)(Database::fetchOne($countSql, $countParams)['c'] ?? 0);
        }

        $totalSql = "SELECT COUNT(*) as c
                     FROM qr_generation_items qri
                     JOIN qr_generations qrg ON qrg.id = qri.generation_id
                     WHERE UPPER(COALESCE(qrg.qr_source, '')) <> 'OWN_PRODUCT'";
        $totalParams = [];
        if ($cid) {
            $totalSql .= ($cid === 1) ? " AND (qrg.concern_id = ? OR qrg.concern_id IS NULL)" : " AND qrg.concern_id = ?";
            $totalParams[] = $cid;
        }
        $totalQrs = (int)Database::fetchOne($totalSql, $totalParams)['c'];

        Response::view('production.qr_generation.index', [
            'pageTitle'    => 'QR Code Generation | GRM Inventory',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-qr-generation',
            'batches'      => $batches,
            'filters'      => $filters,
            'categories'   => $categories,
            'brands'       => $brands,
            'totalQrs'     => $totalQrs,
            'tabs'         => $tabs,
            'activeTab'    => $activeTab,
            'tabCounts'    => $tabCounts,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'QR Code Generations'],
            ],
        ]);
    }

    /**
     * Show generator interface with Manual and Catalog/Variant modes.
     */
    public function create(): void {
        QrGenerationService::ensureSourceSchema();
        PurchaseMappingFulfillmentService::ensureSchema();

        $tabs = QrGenerationService::sourceTabs();
        $requestedTab = $_GET['tab'] ?? (!empty($_GET['mode']) ? 'third_party' : null);
        $activeTab = QrGenerationService::normalizeTab($requestedTab);

        $categories = Database::fetchAll("SELECT id, name, code FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = Database::fetchAll("SELECT id, category_id, name, code FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subSubcategories = Database::fetchAll("SELECT id, subcategory_id, name, code FROM sub_subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $catalogs = Database::fetchAll(
            "SELECT id, category_id, subcategory_id, name, code, brand_id
             FROM products
             WHERE is_active = 1
               AND (source_type IS NULL OR source_type NOT IN ('OWN_PRODUCT', 'OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE'))
             ORDER BY name ASC"
        );
        $brands = Database::fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name ASC");
        $colors = Database::fetchAll("SELECT id, name, hex_code FROM colors WHERE is_active = 1 ORDER BY name ASC");
        $designs = Design::allActive();
        $sizes = Database::fetchAll("SELECT id, name, code FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

        Response::view('production.qr_generation.create', [
            'pageTitle'        => 'Generate QR Labels | GRM Inventory',
            'activeModule'     => 'production',
            'activeMenu'       => 'prod-qr-generation',
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'catalogs'         => $catalogs,
            'brands'           => $brands,
            'colors'           => $colors,
            'designs'          => $designs,
            'sizes'            => $sizes,
            'tabs'             => $tabs,
            'activeTab'        => $activeTab,
            'purchaseGroups'   => self::getPurchaseGroups(),
            'stitchingBatches' => self::getStitchingBatches(),
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'QR Generations', 'url' => url('production/qr-generations')],
                ['label' => 'Generate QR Labels'],
            ],
        ]);
    }

    /**
     * Group purchasable lines by the QR tab they belong to.
     */
    private static function getPurchaseGroups(): array {
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        $groups = [
            QrGenerationService::SOURCE_FABRIC      => [],
            QrGenerationService::SOURCE_RAW         => [],
            QrGenerationService::SOURCE_THIRD_PARTY => [],
        ];
        $itemTypesSql = "'FABRIC_ROLL', 'RAW_MATERIAL', 'THIRD_PARTY'";

        $concernWhere = "";
        $params = [];
        if ($cid) {
            if ($cid === 1) {
                $concernWhere = " AND (pp.concern_id = ? OR pp.concern_id IS NULL)";
            } else {
                $concernWhere = " AND pp.concern_id = ?";
            }
            $params[] = $cid;
        }

        $rows = Database::fetchAll(
            "SELECT ppi.id AS item_id, ppi.item_type, ppi.item_name, ppi.quantity, ppi.unit,
                    ppi.vendor_sku, ppi.product_code, ppi.color, ppi.size, ppi.start_code,
                   ppi.mrp, ppi.selling_price, ppi.is_mapped, ppi.variant_id,
                   pv.variant_code, pv.sku AS variant_sku,
                    ppi.qr_generation_id, ppi.qr_batch_code, ppi.grn_id, ppi.grn_number,
                    pp.id AS purchase_id, pp.purchase_number, pp.purchase_date, pp.invoice_number,
                    s.name AS seller_name, s.company AS seller_company,
                    sr.roll_number
             FROM production_purchase_items ppi
             INNER JOIN production_purchases pp ON pp.id = ppi.purchase_id
             LEFT JOIN sellers s ON s.id = pp.seller_id
             LEFT JOIN seller_rolls sr ON sr.id = ppi.seller_roll_id
             LEFT JOIN product_variants pv ON pv.id = ppi.variant_id
             WHERE ppi.item_type IN ({$itemTypesSql})
               AND (
                   (ppi.item_type IN ('FABRIC_ROLL', 'RAW_MATERIAL'))
                   OR
                   (ppi.item_type = 'THIRD_PARTY' AND ppi.is_mapped = 1)
               )
               AND ppi.qr_generation_id IS NULL
               AND (ppi.qr_batch_code IS NULL OR TRIM(ppi.qr_batch_code) = '')
               AND NOT EXISTS (
                   SELECT 1
                   FROM qr_generations qrg
                   WHERE qrg.source_purchase_item_id = ppi.id
               )
               {$concernWhere}
             ORDER BY pp.purchase_date DESC, pp.id DESC, ppi.id ASC
             LIMIT 1000",
            $params
        );

        foreach ($rows as $row) {
            $source = strtoupper((string)$row['item_type']);
            if (!isset($groups[$source])) {
                continue;
            }
            $purchaseId = (int)$row['purchase_id'];
            if (!isset($groups[$source][$purchaseId])) {
                $groups[$source][$purchaseId] = [
                    'id'             => $purchaseId,
                    'purchase_number'=> $row['purchase_number'],
                    'purchase_date'  => $row['purchase_date'],
                    'invoice_number' => $row['invoice_number'],
                    'seller_label'   => trim((string)($row['seller_company'] ?: ($row['seller_name'] ?? 'Supplier'))),
                    'items'          => [],
                ];
            }
            $groups[$source][$purchaseId]['items'][] = $row;
        }

        foreach ($groups as &$purchases) {
            $purchases = array_values($purchases);
        }
        unset($purchases);

        return $groups;
    }

    /**
     * Handle Manual QR Generation.
     */
    public function storeManual(): void {
        try {
            $result = QrGenerationService::generateManualQrs($_POST);

            Session::flash('success', "Batch {$result['batch_code']} generated successfully with {$result['quantity']} QR labels!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id']));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create'));
        }
    }

    /**
     * Handle Catalog / Variant QR Generation.
     */
    public function storeCatalog(): void {
        try {
            $result = QrGenerationService::generateCatalogQrs($_POST);

            Session::flash('success', "Catalog QR Batch {$result['batch_code']} generated with {$result['quantity']} QR labels!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id']));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create?mode=catalog'));
        }
    }

    /**
     * Handle QR generation from a mapped purchase line.
     */
    public function storePurchaseBatch(): void {
        $tab = QrGenerationService::normalizeTab($_POST['tab'] ?? null);
        $tabs = QrGenerationService::sourceTabs();
        $source = strtoupper(trim((string)($_POST['qr_source'] ?? '')));
        $itemId = (int)($_POST['purchase_item_id'] ?? 0);

        if ($itemId <= 0 || $source === '' || ($tabs[$tab]['source'] ?? null) !== $source) {
            Session::flash('error', 'Select a purchase item from the selected tab.');
            Response::redirect(url('production/qr-generations/create?tab=' . $tab));
            return;
        }
        $selectedItem = Database::fetchOne(
            "SELECT item_type FROM production_purchase_items WHERE id = ?",
            [$itemId]
        );
        if (!$selectedItem || strtoupper((string)$selectedItem['item_type']) !== $source) {
            Session::flash('error', 'The selected product does not belong to this QR tab. Choose a product from the tab-specific list.');
            Response::redirect(url('production/qr-generations/create?tab=' . $tab));
            return;
        }

        try {
            $result = QrGenerationService::generatePurchaseBatch($_POST);
            Session::flash('success', "Batch {$result['batch_code']} generated with {$result['quantity']} QR labels!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id'] . '?tab=' . $tab));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create?tab=' . $tab));
        }
    }

    /**
     * Handle QR generation from a completed Stitching Unit batch.
     */
    public function storeStitchingBatch(): void {
        $stitchingBatchId = (int)($_POST['stitching_batch_id'] ?? 0);
        if ($stitchingBatchId <= 0) {
            Session::flash('error', 'Please select a Stitching Batch.');
            Response::redirect(url('production/qr-generations/create?tab=production_product'));
            return;
        }

        try {
            $result = QrGenerationService::generateStitchingBatch($_POST);
            Session::flash('success', "Production Product QR Batch {$result['batch_code']} generated with {$result['quantity']} pieces and moved to Quality Control (QC)!");
            Response::redirect(url('production/qr-generations/view/' . $result['generation_id'] . '?tab=production_product'));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('production/qr-generations/create?tab=production_product'));
        }
    }

    /**
     * Get completed stitching batches ready for production QR generation.
     */
    public static function getStitchingBatches(): array {
        $cid = function_exists('currentConcernId') ? currentConcernId() : null;
        $concernWhere = "";
        $params = [];
        if ($cid) {
            if ($cid === 1) {
                $concernWhere = " AND (sb.concern_id = ? OR sb.concern_id IS NULL)";
            } else {
                $concernWhere = " AND sb.concern_id = ?";
            }
            $params[] = $cid;
        }

        $batches = Database::fetchAll("
            SELECT sb.id, sb.batch_number, sb.name, sb.program_id, sb.category_id,
                   sb.stitched_qty, sb.received_qty, sb.created_at, sb.status,
                   pp.program_number, pp.name AS program_name,
                   p.name AS product_name, p.code AS product_code,
                   c.name AS category_name, sc.name AS subcategory_name, ssc.name AS sub_subcategory_name
            FROM stitching_batches sb
            INNER JOIN production_programs pp ON pp.id = sb.program_id
            LEFT JOIN products p ON p.id = pp.product_id
            LEFT JOIN categories c ON c.id = COALESCE(sb.category_id, p.category_id)
            LEFT JOIN subcategories sc ON sc.id = COALESCE(sb.subcategory_id, p.subcategory_id)
            LEFT JOIN sub_subcategories ssc ON ssc.id = COALESCE(sb.sub_subcategory_id, p.sub_subcategory_id)
            WHERE (sb.qr_generation_id IS NULL OR sb.qr_generation_id = 0)
              AND UPPER(COALESCE(sb.status, '')) = 'COMPLETED'
              {$concernWhere}
            ORDER BY sb.id DESC
            LIMIT 200
        ", $params);

        if (empty($batches)) {
            return [];
        }

        // Optimized Eager-Loading: Fetch all stitching items in 1 batch query instead of N loop queries
        $batchIds = array_column($batches, 'id');
        $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
        $allItems = Database::fetchAll("
            SELECT si.id, si.stitching_batch_id, si.size_name, si.color_name,
                   COALESCE(NULLIF(si.stitched_qty, 0), NULLIF(si.stitched_quantity, 0), si.received_qty, 0) as qty,
                   si.variant_id, si.catalog_hierarchy, si.base_price, si.discount_price,
                   pv.variant_code, pv.sku, pv.mrp AS variant_mrp, pv.selling_price AS variant_selling_price,
                   col.name AS variant_color_name, sz.name AS variant_size_name
            FROM stitching_items si
            LEFT JOIN product_variants pv ON pv.id = si.variant_id
            LEFT JOIN colors col ON col.id = pv.color_id
            LEFT JOIN sizes sz ON sz.id = pv.size_id
            WHERE si.stitching_batch_id IN ({$placeholders})
        ", $batchIds);

        $itemsByBatch = [];
        foreach ($allItems as $it) {
            $itemsByBatch[$it['stitching_batch_id']][] = $it;
        }

        foreach ($batches as &$b) {
            $items = $itemsByBatch[$b['id']] ?? [];
            $b['items'] = $items;
            $calcTotal = 0;
            foreach ($items as $it) {
                $calcTotal += (int)$it['qty'];
            }
            $b['total_pieces'] = $calcTotal > 0 ? $calcTotal : max(1, (int)($b['stitched_qty'] ?: $b['received_qty']));
        }
        unset($b);

        return $batches;
    }

    /**
     * Explicitly add a generated QR batch to inventory.
     */
    public function addToStock(string $id): void {
        $generationId = (int)$id;
        try {
            $result = QrGenerationService::addToStock($generationId, Auth::id());
            Session::flash('success', "Added {$result['quantity']} pieces to warehouse stock.");
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $tab = QrGenerationService::normalizeTab($_POST['tab'] ?? $_GET['tab'] ?? null);
        Response::redirect(url('production/qr-generations/view/' . $generationId . '?tab=' . $tab));
    }

    /**
     * View batch details and item list.
     */
    public function view(string $id): void {
        $batch = QrGenerationService::getBatch((int)$id);
        if (!$batch) {
            Response::error(404, 'QR Generation batch not found.');
            return;
        }

        Response::view('production.qr_generation.view', [
            'pageTitle'    => 'QR Batch ' . $batch['batch_code'],
            'activeModule' => 'production',
            'activeMenu'   => 'prod-qr-generation',
            'batch'        => $batch,
            'activeTab'    => QrGenerationService::normalizeTab($_GET['tab'] ?? $batch['qr_source'] ?? null),
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('purchases')],
                ['label' => 'QR Generations', 'url' => url('production/qr-generations')],
                ['label' => $batch['batch_code']],
            ],
        ]);
    }

    /**
     * Print thermal QR stickers for this batch.
     * Brand Name given by user is printed prominently at the top.
     */
    public function printLabels(string $id): void {
        $batch = QrGenerationService::getBatch((int)$id);
        if (!$batch) {
            Response::error(404, 'QR Generation batch not found.');
            return;
        }

        Response::viewOnly('production.qr_generation.print', [
            'batch' => $batch,
        ]);
    }

    /**
     * Delete a generation batch.
     */
    public function delete(string $id): void {
        $batchId = (int)$id;
        $batch = Database::fetchOne("SELECT batch_code FROM qr_generations WHERE id = ?", [$batchId]);
        if (!$batch) {
            Response::error(404, 'Batch not found.');
            return;
        }

        Database::execute("DELETE FROM qr_generations WHERE id = ?", [$batchId]);

        Session::flash('success', "QR Batch {$batch['batch_code']} deleted successfully.");
        Response::redirect(url('production/qr-generations'));
    }
}
