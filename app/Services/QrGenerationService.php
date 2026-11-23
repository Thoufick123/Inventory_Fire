<?php
/**
 * QrGenerationService
 * Handles automated & manual QR generation with custom brand formatting and batch sticker generation.
 */

if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Helpers/Auth.php')) {
    require_once BASE_PATH . '/app/Helpers/Auth.php';
}
if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Helpers/Database.php')) {
    require_once BASE_PATH . '/app/Helpers/Database.php';
}
if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Services/StockService.php')) {
    require_once BASE_PATH . '/app/Services/StockService.php';
}
if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Services/VariantService.php')) {
    require_once BASE_PATH . '/app/Services/VariantService.php';
}
if (defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Services/AuditService.php')) {
    require_once BASE_PATH . '/app/Services/AuditService.php';
}

class QrGenerationService {

    public const SOURCE_FABRIC      = 'FABRIC_ROLL';
    public const SOURCE_RAW         = 'RAW_MATERIAL';
    public const SOURCE_THIRD_PARTY = 'THIRD_PARTY';
    public const SOURCE_PRODUCTION  = 'PRODUCTION_PRODUCT';
    /**
     * The QR Generation areas. Keys are used in ?tab= URLs.
     */
    public static function sourceTabs(?int $concernId = null): array {
        $tabs = [
            'fabric'             => ['key' => 'fabric',             'label' => 'Fabric Rolls',        'source' => self::SOURCE_FABRIC,      'icon' => 'bi-layers-half',    'color' => 'primary'],
            'raw_material'       => ['key' => 'raw_material',       'label' => 'Raw Materials',       'source' => self::SOURCE_RAW,         'icon' => 'bi-boxes',          'color' => 'warning'],
            'third_party'        => ['key' => 'third_party',        'label' => 'Third-Party Products','source' => self::SOURCE_THIRD_PARTY, 'icon' => 'bi-box-seam',       'color' => 'info'],
            'production_product' => ['key' => 'production_product', 'label' => 'Production Products', 'source' => self::SOURCE_PRODUCTION,  'icon' => 'bi-scissors',       'color' => 'success'],
        ];

        $cid = $concernId ?: (function_exists('currentConcernId') ? currentConcernId() : null);
        if ($cid && function_exists('isProductionConcern') && isProductionConcern($cid)) {
            return [
                'fabric'             => $tabs['fabric'],
                'raw_material'       => $tabs['raw_material'],
                'third_party'        => $tabs['third_party'],
                'production_product' => $tabs['production_product'],
            ];
        }

        return $tabs;
    }

    public static function normalizeTab(?string $tab, ?int $concernId = null): string {
        $tab = strtolower(trim((string)$tab));
        $validTabs = self::sourceTabs($concernId);
        return array_key_exists($tab, $validTabs) ? $tab : array_key_first($validTabs);
    }

    /**
     * Add source-tracking columns used by QR generation areas.
     */
    public static function ensureSourceSchema(): void {
        static $done = false;
        if ($done) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE && (int)($_SESSION['_qr_schema_ensured'] ?? 0) >= 3) {
            $done = true;
            return;
        }

        Database::ensureColumn('qr_generations', 'qr_source', "VARCHAR(30) NOT NULL DEFAULT 'THIRD_PARTY' AFTER generation_type");
        Database::ensureColumn('qr_generations', 'source_purchase_id', 'INT UNSIGNED DEFAULT NULL AFTER qr_source');
        Database::ensureColumn('qr_generations', 'source_purchase_item_id', 'INT UNSIGNED DEFAULT NULL AFTER source_purchase_id');
        Database::ensureColumn('qr_generations', 'stitching_batch_id', 'INT UNSIGNED DEFAULT NULL AFTER source_purchase_item_id');
        Database::ensureColumn('qr_generations', 'program_id', 'INT UNSIGNED DEFAULT NULL AFTER stitching_batch_id');
        Database::ensureColumn('qr_generations', 'qc_batch_id', 'INT UNSIGNED DEFAULT NULL AFTER program_id');
        Database::ensureColumn('qr_generations', 'stock_added', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER qc_batch_id');
        Database::ensureColumn('qr_generations', 'stock_added_at', 'DATETIME DEFAULT NULL AFTER stock_added');
        Database::ensureColumn('qr_generations', 'stock_added_by', 'INT UNSIGNED DEFAULT NULL AFTER stock_added_at');
        Database::ensureColumn('products', 'variants_deferred', 'TINYINT(1) NOT NULL DEFAULT 0');

        // Auto-heal any mapping roll rows that had item_type set to THIRD_PARTY
        try {
            Database::execute("
                UPDATE third_party_mapping_items 
                SET item_type = 'FABRIC_ROLL'
                WHERE item_type != 'FABRIC_ROLL'
                  AND (
                      (roll_code IS NOT NULL AND TRIM(roll_code) != '')
                      OR (weight_kg IS NOT NULL AND weight_kg > 0)
                      OR (meters IS NOT NULL AND meters > 0)
                      OR UPPER(TRIM(unit)) IN ('ROLL', 'ROLLS', 'MTR', 'MTRS', 'METER', 'METERS', 'KG', 'KGS')
                      OR LOWER(item_name) LIKE '%roll%'
                      OR LOWER(item_name) LIKE '%fabric%'
                  )
            ");
        } catch (\Throwable $e) {}

        Database::ensureColumn('stitching_batches', 'qr_generation_id', 'INT UNSIGNED DEFAULT NULL AFTER status');
        Database::ensureColumn('stitching_batches', 'qr_batch_code', 'VARCHAR(50) DEFAULT NULL AFTER qr_generation_id');
        Database::ensureColumn('qc_batches', 'qr_generation_id', 'INT UNSIGNED DEFAULT NULL AFTER label_batch_id');
        Database::ensureColumn('qr_generation_items', 'variant_id', 'INT UNSIGNED DEFAULT NULL AFTER generation_id');
        Database::ensureColumn('qr_generation_items', 'size_name', 'VARCHAR(100) DEFAULT NULL AFTER variant_id');
        Database::ensureColumn('qr_generation_items', 'color_name', 'VARCHAR(100) DEFAULT NULL AFTER size_name');
        Database::ensureColumn('qr_generation_items', 'design_name', 'VARCHAR(150) DEFAULT NULL AFTER color_name');
        Database::ensureColumn('qr_generation_items', 'sequence_num', 'INT NOT NULL DEFAULT 0 AFTER qr_code');
        Database::ensureColumn('qr_generation_items', 'status', "ENUM('ACTIVE','USED','DAMAGED','CANCELLED') DEFAULT 'ACTIVE' AFTER sequence_num");
        Database::ensureColumn('qr_generation_items', 'mrp', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER color_name');
        Database::ensureColumn('qr_generation_items', 'selling_price', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER mrp');

        foreach (Database::fetchAll("SHOW COLUMNS FROM qr_generation_items") as $column) {
            if ($column['Field'] === 'item_code' && $column['Null'] === 'NO') {
                Database::execute("ALTER TABLE qr_generation_items MODIFY item_code VARCHAR(100) NULL DEFAULT NULL");
            } elseif ($column['Field'] === 'sequence_number' && $column['Null'] === 'NO') {
                Database::execute("ALTER TABLE qr_generation_items MODIFY sequence_number INT UNSIGNED NULL DEFAULT NULL");
            }
        }

        foreach ([
            'ALTER TABLE qr_generations ADD KEY idx_qrg_source (qr_source)',
            'ALTER TABLE qr_generations ADD KEY idx_qrg_source_purchase (source_purchase_id, qr_source)',
        ] as $ddl) {
            try {
                Database::execute($ddl);
            } catch (Throwable $e) {
                // Index already present.
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_qr_schema_ensured'] = 3;
        }
        $done = true;
    }

    /**
     * Generate next batch code (e.g. QRG-20261115-0001).
     */
    public static function generateBatchCode(): string {
        $prefix = 'QRG-' . date('Ymd') . '-';
        $max = (int)Database::fetchOne(
            "SELECT COUNT(*) as c FROM qr_generations WHERE batch_code LIKE ?",
            [$prefix . '%']
        )['c'];
        return $prefix . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format a clean uppercase code from text (e.g. "Kids Wear" -> "KW" or "KID").
     */
    public static function extractCode(string $text, int $maxLen = 4): string {
        $cleaned = strtoupper(trim(preg_replace('/[^A-Za-z0-9\s]/', '', $text)));
        $words = preg_split('/\s+/', $cleaned);

        if (count($words) >= 2 && $maxLen <= 4) {
            $initials = '';
            foreach ($words as $w) {
                if ($w !== '') $initials .= $w[0];
            }
            if (strlen($initials) >= 2) {
                return substr($initials, 0, $maxLen);
            }
        }

        $alphanumeric = preg_replace('/[^A-Z0-9]/', '', $cleaned);
        return substr($alphanumeric, 0, $maxLen) ?: 'GEN';
    }

    /**
     * Generate Manual QR Batch:
     * Format: [BRAND]-[CATEGORY]-[SUBCATEGORY]-[SUBSUBCATEGORY]-[SEQ]
     */
    public static function generateManualQrs(array $data): array {
        self::ensureSourceSchema();

        // Keep the exact text entered by the admin: it is printed verbatim on
        // every sticker. Only the technical prefix is converted to a code.
        $brandName = trim((string)($data['brand_name'] ?? 'GRM'));
        if ($brandName === '') {
            $brandName = 'GRM';
        }

        // 1. Resolve or Quick-create Category
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $categoryName = '';
        $categoryCode = '';

        if (!empty($data['new_category_name'])) {
            $newCatName = trim($data['new_category_name']);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $newCatName), '-'));
            $existing = Database::fetchOne("SELECT id, name, code FROM categories WHERE name = ? OR slug = ?", [$newCatName, $slug]);
            if ($existing) {
                $categoryId = (int)$existing['id'];
                $categoryName = $existing['name'];
                $categoryCode = $existing['code'] ?: self::extractCode($newCatName);
            } else {
                $categoryCode = self::extractCode($newCatName, 3);
                Database::execute(
                    "INSERT INTO categories (name, code, slug, is_active) VALUES (?, ?, ?, 1)",
                    [$newCatName, $categoryCode, $slug]
                );
                $categoryId = (int)Database::lastInsertId();
                $categoryName = $newCatName;
            }
        } elseif ($categoryId) {
            $c = Database::fetchOne("SELECT id, name, code FROM categories WHERE id = ?", [$categoryId]);
            if ($c) {
                $categoryName = $c['name'];
                $categoryCode = $c['code'] ?: self::extractCode($c['name'], 3);
            }
        }

        // 2. Resolve or Quick-create Subcategory
        $subcategoryId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;
        $subcategoryName = '';
        $subcategoryCode = '';

        if (!empty($data['new_subcategory_name']) && $categoryId) {
            $newSubName = trim($data['new_subcategory_name']);
            $existing = Database::fetchOne("SELECT id, name, code FROM subcategories WHERE category_id = ? AND name = ?", [$categoryId, $newSubName]);
            if ($existing) {
                $subcategoryId = (int)$existing['id'];
                $subcategoryName = $existing['name'];
                $subcategoryCode = $existing['code'] ?: self::extractCode($newSubName);
            } else {
                $subcategoryCode = self::extractCode($newSubName, 3);
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $newSubName));
                Database::execute(
                    "INSERT INTO subcategories (category_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                    [$categoryId, $newSubName, $subcategoryCode, $slug]
                );
                $subcategoryId = (int)Database::lastInsertId();
                $subcategoryName = $newSubName;
            }
        } elseif ($subcategoryId) {
            $sc = Database::fetchOne("SELECT id, name, code FROM subcategories WHERE id = ?", [$subcategoryId]);
            if ($sc) {
                $subcategoryName = $sc['name'];
                $subcategoryCode = $sc['code'] ?: self::extractCode($sc['name'], 3);
            }
        }

        // 3. Resolve or Quick-create Sub-Subcategory
        $subSubcategoryId = !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null;
        $subSubcategoryName = '';
        $subSubcategoryCode = '';

        if (!empty($data['new_sub_subcategory_name']) && $subcategoryId) {
            $newSscName = trim($data['new_sub_subcategory_name']);
            $existing = Database::fetchOne("SELECT id, name, code FROM sub_subcategories WHERE subcategory_id = ? AND name = ?", [$subcategoryId, $newSscName]);
            if ($existing) {
                $subSubcategoryId = (int)$existing['id'];
                $subSubcategoryName = $existing['name'];
                $subSubcategoryCode = $existing['code'] ?: self::extractCode($newSscName);
            } else {
                $subSubcategoryCode = self::extractCode($newSscName, 3);
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $newSscName));
                Database::execute(
                    "INSERT INTO sub_subcategories (subcategory_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                    [$subcategoryId, $newSscName, $subSubcategoryCode, $slug]
                );
                $subSubcategoryId = (int)Database::lastInsertId();
                $subSubcategoryName = $newSscName;
            }
        } elseif ($subSubcategoryId) {
            $ssc = Database::fetchOne("SELECT id, name, code FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
            if ($ssc) {
                $subSubcategoryName = $ssc['name'];
                $subSubcategoryCode = $ssc['code'] ?: self::extractCode($ssc['name'], 3);
            }
        }

        // 4. Resolve Brand Code
        $brandCode = self::extractCode($brandName, 4);

        // Build Prefix: [BRAND]-[CATEGORY]-[SUBCATEGORY]-[SUBSUBCATEGORY]
        $prefixParts = [$brandCode];
        if (!empty($categoryCode)) $prefixParts[] = $categoryCode;
        if (!empty($subcategoryCode)) $prefixParts[] = $subcategoryCode;
        if (!empty($subSubcategoryCode)) $prefixParts[] = $subSubcategoryCode;

        $qrPrefix = implode('-', $prefixParts);

        // 5. Quantity & Prices
        $quantity = max(1, min(1000, (int)($data['quantity'] ?? 1)));
        $mrp = max(0.0, (float)($data['mrp'] ?? 0));
        $sellingPrice = max(0.0, (float)($data['selling_price'] ?? 0));
        if ($sellingPrice <= 0) $sellingPrice = $mrp;

        $colorId = !empty($data['color_id']) ? (int)$data['color_id'] : null;
        $colorName = $colorId ? (Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId])['name'] ?? null) : null;

        $designId = !empty($data['design_id']) ? (int)$data['design_id'] : null;
        $designName = $designId ? (Database::fetchOne("SELECT name FROM designs WHERE id = ?", [$designId])['name'] ?? null) : null;

        $sizeId = !empty($data['size_id']) ? (int)$data['size_id'] : null;
        $sizeName = $sizeId ? (Database::fetchOne("SELECT name FROM sizes WHERE id = ?", [$sizeId])['name'] ?? null) : null;

        $batchCode = self::generateBatchCode();

        // 6. Calculate starting sequence number
        $lastSeqRow = Database::fetchOne(
            "SELECT MAX(sequence_num) as max_seq FROM qr_generation_items qri
             JOIN qr_generations qrg ON qrg.id = qri.generation_id
             WHERE qrg.qr_prefix = ?",
            [$qrPrefix]
        );
        $startSeq = (int)($lastSeqRow['max_seq'] ?? 0) + 1;
        $concernId = !empty($data['concern_id']) ? (int)$data['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO qr_generations 
                 (batch_code, generation_type, qr_source, concern_id, brand_name, category_id, subcategory_id, sub_subcategory_id,
                  category_name, subcategory_name, sub_subcategory_name, color_id, design_id, size_id,
                  color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, 'MANUAL', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode,
                    self::SOURCE_THIRD_PARTY,
                    $concernId,
                    $brandName,
                    $categoryId,
                    $subcategoryId,
                    $subSubcategoryId,
                    $categoryName,
                    $subcategoryName,
                    $subSubcategoryName,
                    $colorId,
                    $designId,
                    $sizeId,
                    $colorName,
                    $designName,
                    $sizeName,
                    $mrp,
                    $sellingPrice,
                    $quantity,
                    $qrPrefix,
                    trim($data['notes'] ?? ''),
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $generatedCodes = [];
            $insertItemStmt = Database::getInstance()->prepare(
                "INSERT INTO qr_generation_items (generation_id, qr_code, sequence_num, status) VALUES (?, ?, ?, 'ACTIVE')"
            );

            for ($i = 0; $i < $quantity; $i++) {
                $currSeq = $startSeq + $i;
                $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                $qrCode = $qrPrefix . '-' . $seqStr;

                // Handle any unexpected collision
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$qrCode])) {
                    $currSeq++;
                    $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                    $qrCode = $qrPrefix . '-' . $seqStr;
                }

                $insertItemStmt->execute([$generationId, $qrCode, $currSeq]);
                $generatedCodes[] = $qrCode;
            }

            if ($variantId > 0 && !empty($generatedCodes[0])) {
                Database::execute(
                    "UPDATE product_variants SET qr_code = ? WHERE id = ? AND (qr_code IS NULL OR qr_code = '')",
                    [$generatedCodes[0], $variantId]
                );
            }

            Database::commit();

            return [
                'generation_id'   => $generationId,
                'batch_code'      => $batchCode,
                'brand_name'      => $brandName,
                'qr_prefix'       => $qrPrefix,
                'quantity'        => $quantity,
                'generated_codes' => $generatedCodes,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Generate QR Batch from existing Catalog / Variant:
     */
    public static function generateCatalogQrs(array $data): array {
        self::ensureSourceSchema();

        $productId = (int)($data['product_id'] ?? 0);
        $variantId = !empty($data['variant_id']) ? (int)$data['variant_id'] : null;

        $product = Database::fetchOne(
            "SELECT p.*, c.name as category_name, c.code as category_code,
                    sc.name as subcategory_name, sc.code as subcategory_code,
                    ssc.name as sub_subcategory_name, ssc.code as sub_subcategory_code,
                    b.name as brand_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.id = ?",
            [$productId]
        );
        if (!$product) {
            throw new InvalidArgumentException("Catalog / Product not found.");
        }
        if (in_array(strtoupper((string)($product['source_type'] ?? '')), ['OWN_PRODUCT', 'OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE'], true)) {
            throw new InvalidArgumentException('Catalog QR generation is not available for Own Product items.');
        }

        // Preserve exactly what the admin typed so the label shows the same text.
        $brandName = !empty($data['brand_name']) ? trim((string)$data['brand_name']) : ($product['brand_name'] ?: 'GRM');
        $brandCode = self::extractCode($brandName, 4);

        $variant = null;
        if ($variantId) {
            $variant = Database::fetchOne(
                "SELECT pv.*, col.name as color_name, d.name as design_name, sz.name as size_name
                 FROM product_variants pv
                 LEFT JOIN colors col ON col.id = pv.color_id
                 LEFT JOIN designs d ON d.id = pv.design_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 WHERE pv.id = ?",
                [$variantId]
            );
        }

        // Form Prefix
        $catCode = $product['category_code'] ?: self::extractCode($product['category_name'] ?? '', 3);
        $subcatCode = $product['subcategory_code'] ?: self::extractCode($product['subcategory_name'] ?? '', 3);
        $subSubcatCode = $product['sub_subcategory_code'] ?: self::extractCode($product['sub_subcategory_name'] ?? '', 3);

        $prefixParts = [$brandCode];
        if ($catCode) $prefixParts[] = $catCode;
        if ($subcatCode) $prefixParts[] = $subcatCode;
        if ($subSubcatCode) $prefixParts[] = $subSubcatCode;

        if ($variant && !empty($variant['sku'])) {
            $qrPrefix = $brandCode . '-' . $variant['sku'];
        } else {
            $qrPrefix = implode('-', $prefixParts) . '-' . $product['code'];
        }

        $quantity = max(1, min(1000, (int)($data['quantity'] ?? 1)));
        $mrp = $variant ? (float)$variant['mrp'] : 0.00;
        $sellingPrice = $variant ? (float)$variant['selling_price'] : 0.00;

        $batchCode = self::generateBatchCode();

        $lastSeqRow = Database::fetchOne(
            "SELECT MAX(sequence_num) as max_seq FROM qr_generation_items qri
             JOIN qr_generations qrg ON qrg.id = qri.generation_id
             WHERE qrg.qr_prefix = ?",
            [$qrPrefix]
        );
        $startSeq = (int)($lastSeqRow['max_seq'] ?? 0) + 1;
        $concernId = !empty($data['concern_id']) ? (int)$data['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO qr_generations 
                 (batch_code, generation_type, qr_source, concern_id, brand_name, category_id, subcategory_id, sub_subcategory_id,
                  category_name, subcategory_name, sub_subcategory_name, product_id, variant_id,
                  color_id, design_id, size_id, color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, 'CATALOG_VARIANT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode,
                    self::SOURCE_THIRD_PARTY,
                    $concernId,
                    $brandName,
                    $product['category_id'],
                    $product['subcategory_id'],
                    $product['sub_subcategory_id'],
                    $product['category_name'],
                    $product['subcategory_name'],
                    $product['sub_subcategory_name'],
                    $productId,
                    $variantId,
                    $variant['color_id'] ?? null,
                    $variant['design_id'] ?? null,
                    $variant['size_id'] ?? null,
                    $variant['color_name'] ?? null,
                    $variant['design_name'] ?? null,
                    $variant['size_name'] ?? null,
                    $mrp,
                    $sellingPrice,
                    $quantity,
                    $qrPrefix,
                    trim($data['notes'] ?? ''),
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $generatedCodes = [];
            $insertItemStmt = Database::getInstance()->prepare(
                "INSERT INTO qr_generation_items (generation_id, qr_code, sequence_num, status) VALUES (?, ?, ?, 'ACTIVE')"
            );

            for ($i = 0; $i < $quantity; $i++) {
                $currSeq = $startSeq + $i;
                $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                $qrCode = $qrPrefix . '-' . $seqStr;

                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$qrCode])) {
                    $currSeq++;
                    $seqStr = str_pad((string)$currSeq, 4, '0', STR_PAD_LEFT);
                    $qrCode = $qrPrefix . '-' . $seqStr;
                }

                $insertItemStmt->execute([$generationId, $qrCode, $currSeq]);
                $generatedCodes[] = $qrCode;
            }

            if ($variantId > 0 && !empty($generatedCodes[0])) {
                Database::execute(
                    "UPDATE product_variants SET qr_code = ? WHERE id = ? AND (qr_code IS NULL OR qr_code = '')",
                    [$generatedCodes[0], $variantId]
                );
            }

            Database::commit();

            return [
                'generation_id'   => $generationId,
                'batch_code'      => $batchCode,
                'brand_name'      => $brandName,
                'qr_prefix'       => $qrPrefix,
                'quantity'        => $quantity,
                'generated_codes' => $generatedCodes,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Generate a QR batch from an actual purchase line.
     *
     * Fabric / Raw Material areas reuse the exact prefix and sequence rules of
     * the existing roll QR printer. Catalog-linked items use the mapped variant
     * identity and print the brand name exactly as entered by the admin.
     */
    public static function generatePurchaseBatch(array $data): array {
        $source = strtoupper(trim((string)($data['qr_source'] ?? '')));
        if (!in_array($source, [self::SOURCE_FABRIC, self::SOURCE_RAW, self::SOURCE_THIRD_PARTY], true)) {
            throw new InvalidArgumentException('Invalid QR generation area.');
        }

         self::ensureSourceSchema();
         if (class_exists('PurchaseMappingFulfillmentService')) {
             PurchaseMappingFulfillmentService::ensureSchema();
         }

        $rawItemId = trim((string)($data['purchase_item_id'] ?? ''));
        $isMapping = str_starts_with($rawItemId, 'map_') || !empty($data['is_mapping']);
        $mappingItemId = $isMapping ? (int)preg_replace('/^map_/', '', $rawItemId) : 0;
        $itemId = !$isMapping ? (int)$rawItemId : 0;

        if ($isMapping) {
            return self::generateMappingBatch($mappingItemId, $source, $data);
        }

        if ($itemId <= 0) {
            throw new InvalidArgumentException('Select a purchase item.');
        }

        $item = Database::fetchOne(
            "SELECT ppi.*, pp.purchase_number, pp.purchase_date, pp.invoice_number, pp.concern_id,
                    s.name AS seller_name, s.company AS seller_company,
                    sr.roll_number AS sr_roll_number, sr.start_code, sr.end_code, sr.start_seq, sr.end_seq,
                    sr.weight AS sr_weight, sr.color AS sr_color, sr.design_name AS sr_design_name,
                    sr.quantity AS sr_quantity,
                    pv.variant_code, pv.sku, pv.mrp AS variant_mrp, pv.selling_price AS variant_selling_price,
                    COALESCE(tpc.name, c.name) AS category_name,
                    COALESCE(tpc.code, c.code) AS category_code,
                    COALESCE(tpsc.name, sc.name) AS subcategory_name,
                    COALESCE(tpssc.name, ssc.name) AS sub_subcategory_name
             FROM production_purchase_items ppi
             INNER JOIN production_purchases pp ON pp.id = ppi.purchase_id
             LEFT JOIN sellers s ON s.id = pp.seller_id
             LEFT JOIN seller_rolls sr ON sr.id = ppi.seller_roll_id
             LEFT JOIN product_variants pv ON pv.id = ppi.variant_id
             LEFT JOIN categories c ON c.id = ppi.mapped_category_id
             LEFT JOIN subcategories sc ON sc.id = ppi.mapped_subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = ppi.mapped_sub_subcategory_id
             LEFT JOIN third_party_categories tpc ON tpc.id = ppi.mapped_third_party_category_id
             LEFT JOIN third_party_subcategories tpsc ON tpsc.id = ppi.mapped_third_party_subcategory_id
             LEFT JOIN third_party_sub_subcategories tpssc ON tpssc.id = ppi.mapped_third_party_sub_subcategory_id
             WHERE ppi.id = ?",
            [$itemId]
        );
        if (!$item) {
            throw new InvalidArgumentException('Purchase item not found.');
        }

        $activeCid = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($activeCid && function_exists('isProductionConcern') && isProductionConcern($activeCid)) {
            if (!in_array($source, [self::SOURCE_FABRIC, self::SOURCE_RAW, self::SOURCE_THIRD_PARTY], true)) {
                throw new InvalidArgumentException('This QR area is not available for Production concerns.');
            }
        }
        if (strtoupper((string)($item['item_type'] ?? '')) !== $source) {
            throw new InvalidArgumentException('This item does not belong to the selected QR area.');
        }
        if ($source === self::SOURCE_THIRD_PARTY) {
            if (empty($item['is_mapped'])) {
                throw new InvalidArgumentException('Map this purchase item before generating QR codes.');
            }
            if ((int)($item['variant_id'] ?? 0) <= 0) {
                throw new InvalidArgumentException('This mapped purchase item has no catalog variant. Complete its mapping first.');
            }
        }
        if (!empty($item['qr_generation_id']) || trim((string)($item['qr_batch_code'] ?? '')) !== '') {
            throw new RuntimeException('A QR batch has already been generated for this purchase item.');
        }
        if (Database::fetchOne(
            "SELECT id FROM qr_generations WHERE source_purchase_item_id = ? LIMIT 1",
            [$itemId]
        )) {
            throw new RuntimeException('A QR batch has already been generated for this purchase item.');
        }

        $brandInput = trim((string)($data['brand_name'] ?? ''));
        if ($source === self::SOURCE_THIRD_PARTY) {
            if ($brandInput === '') {
                throw new InvalidArgumentException('Enter the brand name exactly as it should appear on the QR labels.');
            }
            $brandName = $brandInput;
        } else {
            $brandName = mb_strtoupper(trim((string)($item['seller_company'] ?: ($item['seller_name'] ?? 'GRM'))));
        }

        $codes = [];
        $mrp = 0.0;
        $sellingPrice = 0.0;
        $productId = (int)($item['product_id'] ?? 0);
        $variantId = (int)($item['variant_id'] ?? 0);
        $categoryId = $source === self::SOURCE_THIRD_PARTY
            ? null
            : ((int)($item['mapped_category_id'] ?? 0) ?: null);
        $subcategoryId = $source === self::SOURCE_THIRD_PARTY
            ? null
            : (!empty($item['mapped_subcategory_id']) ? (int)$item['mapped_subcategory_id'] : null);
        $subSubcategoryId = $source === self::SOURCE_THIRD_PARTY
            ? null
            : (!empty($item['mapped_sub_subcategory_id']) ? (int)$item['mapped_sub_subcategory_id'] : null);

        if ($source === self::SOURCE_THIRD_PARTY) {
            if ($productId <= 0 || $variantId <= 0) {
                throw new InvalidArgumentException('Map this product before generating QR codes.');
            }
            $qrPrefix = self::catalogPrefix($productId, $variantId, $brandName);
            $qty = max(1, min(1000, (int)round((float)($item['quantity'] ?? 1))));
            $startSeq = self::nextSequence($qrPrefix);
            for ($i = 0; $i < $qty; $i++) {
                $seq = $startSeq + $i;
                $code = $qrPrefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$code])) {
                    $seq++;
                    $code = $qrPrefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
                }
                $codes[] = ['code' => $code, 'sequence' => $seq, 'status' => 'ACTIVE'];
            }
            $mrp = (float)($item['mrp'] ?: ($item['variant_mrp'] ?? 0));
            $sellingPrice = (float)($item['selling_price'] ?: ($item['variant_selling_price'] ?? 0));
        } else {
            // Identical prefix/sequence rules to ProductionController::productionPurchaseRollQr().
            $purchaseDate = $item['purchase_date'] ?: date('Y-m-d');
            $defaultPrefix = ($source === self::SOURCE_FABRIC ? 'FB' : 'RM') . date('my', strtotime($purchaseDate));
            $startCode = trim((string)($item['start_code'] ?? ''));
            $padLen = 2;

            if ($startCode !== '' && preg_match('/^([A-Za-z]{2}\d{4})(\d+)$/', $startCode, $m)) {
                $qrPrefix = $m[1];
                $padLen = max(2, strlen($m[2]));
            } elseif ($startCode !== '' && preg_match('/^([A-Za-z0-9_-]+?)(\d+)$/', $startCode, $m)) {
                $qrPrefix = $m[1];
                $padLen = max(2, strlen($m[2]));
            } else {
                $qrPrefix = $defaultPrefix;
            }

            $startSeq = !empty($item['start_seq']) ? (int)$item['start_seq'] : null;
            $endSeq = !empty($item['end_seq']) ? (int)$item['end_seq'] : null;
            if ($startSeq === null && $startCode !== '' && preg_match('/(\d+)$/', $startCode, $m)) {
                $startSeq = (int)$m[1];
            }
            if ($startSeq === null) {
                $startSeq = 1;
            }
            $itemQty = max(1, (int)round((float)($item['quantity'] ?: ($item['sr_quantity'] ?? 1))));
            if ($endSeq === null || $endSeq < $startSeq) {
                $endSeq = $startSeq + $itemQty - 1;
            }

            $deletedSeqs = [];
            $damagedCodes = [];
            if (!empty($item['seller_roll_id'])) {
                $deletedRows = Database::fetchAll(
                    "SELECT `sequence` FROM seller_roll_deleted_labels WHERE seller_roll_id = ?",
                    [(int)$item['seller_roll_id']]
                );
                foreach ($deletedRows as $row) {
                    $deletedSeqs[(int)$row['sequence']] = true;
                }
                $damagedRows = Database::fetchAll(
                    "SELECT qr_code FROM roll_damages WHERE seller_roll_id = ? AND qr_code IS NOT NULL AND TRIM(qr_code) <> ''",
                    [(int)$item['seller_roll_id']]
                );
                foreach ($damagedRows as $row) {
                    $damagedCodes[strtoupper(trim((string)$row['qr_code']))] = true;
                }
            }

            for ($seq = $startSeq; $seq <= $endSeq; $seq++) {
                if (isset($deletedSeqs[$seq])) {
                    continue;
                }
                $code = sprintf('%s%0' . $padLen . 'd', $qrPrefix, $seq);
                $codes[] = [
                    'code'     => $code,
                    'sequence' => $seq,
                    'status'   => isset($damagedCodes[strtoupper($code)]) ? 'DAMAGED' : 'ACTIVE',
                ];
            }
            $mrp = (float)($item['mrp'] ?? 0);
            $sellingPrice = (float)($item['unit_price'] ?? 0);
        }

        if (empty($codes)) {
            throw new InvalidArgumentException('No QR labels could be derived from this purchase item.');
        }

        $batchCode = self::generateBatchCode();
        $generationType = $source === self::SOURCE_THIRD_PARTY
            ? 'CATALOG_VARIANT'
            : 'MANUAL';
        $notes = trim((string)($data['notes'] ?? ''));
        if ($notes === '') {
            $notes = "Generated from purchase {$item['purchase_number']} — " . str_replace('_', ' ', $source);
        }

        Database::beginTransaction();
        try {
            $lockedItem = Database::fetchOne(
                "SELECT qr_generation_id, qr_batch_code
                 FROM production_purchase_items
                 WHERE id = ?
                 FOR UPDATE",
                [$itemId]
            );
            $existingBatch = Database::fetchOne(
                "SELECT id FROM qr_generations WHERE source_purchase_item_id = ? LIMIT 1",
                [$itemId]
            );
            if (!$lockedItem) {
                throw new RuntimeException('Purchase item not found.');
            }
            if (
                !empty($lockedItem['qr_generation_id'])
                || trim((string)($lockedItem['qr_batch_code'] ?? '')) !== ''
                || $existingBatch
            ) {
                throw new RuntimeException('A QR batch has already been generated for this purchase item.');
            }

            $generationConcernId = !empty($item['concern_id']) ? (int)$item['concern_id'] : $activeCid;

            Database::execute(
                "INSERT INTO qr_generations
                    (batch_code, generation_type, qr_source, concern_id, source_purchase_id, source_purchase_item_id,
                     brand_name, category_id, subcategory_id, sub_subcategory_id,
                     category_name, subcategory_name, sub_subcategory_name,
                     product_id, variant_id, color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode, $generationType, $source, $generationConcernId,
                    (int)$item['purchase_id'], $itemId,
                    $brandName, $categoryId, $subcategoryId, $subSubcategoryId,
                    $item['category_name'] ?? null, $item['subcategory_name'] ?? null, $item['sub_subcategory_name'] ?? null,
                    $productId ?: null, $variantId ?: null,
                    $item['color'] ?? null, $item['design_name'] ?? null, $item['size'] ?? null,
                    $mrp, $sellingPrice, count($codes), $qrPrefix, $notes,
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null,
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $inserted = 0;
            $qrItemColumns = array_column(Database::fetchAll("SHOW COLUMNS FROM qr_generation_items"), 'Field');
            $insertColumns = ['generation_id', 'qr_code', 'sequence_num', 'status', 'color_name', 'design_name', 'size_name'];
            if (in_array('item_code', $qrItemColumns, true)) {
                $insertColumns[] = 'item_code';
            }
            if (in_array('sequence_number', $qrItemColumns, true)) {
                $insertColumns[] = 'sequence_number';
            }
            $insertSql = "INSERT INTO qr_generation_items (`" . implode('`, `', $insertColumns) . "`) VALUES ("
                . implode(', ', array_fill(0, count($insertColumns), '?')) . ")";
            foreach ($codes as $codeRow) {
                try {
                    Database::execute(
                        $insertSql,
                        array_merge(
                            [
                                $generationId,
                                $codeRow['code'],
                                (int)$codeRow['sequence'],
                                $codeRow['status'],
                                $item['color'] ?? null,
                                $item['design_name'] ?? null,
                                $item['size'] ?? null,
                            ],
                            in_array('item_code', $qrItemColumns, true) ? [$codeRow['code']] : [],
                            in_array('sequence_number', $qrItemColumns, true) ? [(int)$codeRow['sequence']] : []
                        )
                    );
                    $inserted++;
                } catch (PDOException $e) {
                    if ((int)($e->errorInfo[1] ?? 0) !== 1062) {
                        throw $e;
                    }
                }
            }
            if ($inserted === 0) {
                throw new RuntimeException("No QR labels were inserted. Every label in this purchase item's code range already exists; verify the purchase item and QR range.");
            }

            Database::execute("UPDATE qr_generations SET quantity = ? WHERE id = ?", [$inserted, $generationId]);
            Database::execute(
                "UPDATE production_purchase_items SET qr_generation_id = ?, qr_batch_code = ? WHERE id = ?",
                [$generationId, $batchCode, $itemId]
            );

            if ($variantId > 0 && !empty($codes[0]['code'])) {
                Database::execute(
                    "UPDATE product_variants SET qr_code = ? WHERE id = ? AND (qr_code IS NULL OR qr_code = '')",
                    [$codes[0]['code'], $variantId]
                );
            }
            Database::execute(
                "UPDATE purchase_inward_items SET batch_lot_no = ? WHERE production_purchase_item_id = ?",
                [$batchCode, $itemId]
            );

            // Move roll directly to purchased stock upon QR generation and ensure seller_rolls sync
            if ($source === self::SOURCE_FABRIC) {
                $curUserId = (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null;
                Database::execute(
                    "UPDATE qr_generations SET stock_added = 1, stock_added_at = NOW(), stock_added_by = ? WHERE id = ?",
                    [$curUserId, $generationId]
                );

                $rollNumber = trim((string)($item['roll_number'] ?: 'ROLL #1'));
                $startCode = !empty($codes[0]['code']) ? (string)$codes[0]['code'] : '';
                $endCode = !empty($codes[count($codes) - 1]['code']) ? (string)$codes[count($codes) - 1]['code'] : $startCode;
                $startSeq = !empty($codes[0]['sequence']) ? (int)$codes[0]['sequence'] : 1;
                $endSeq = !empty($codes[count($codes) - 1]['sequence']) ? (int)$codes[count($codes) - 1]['sequence'] : count($codes);
                $qrCodeRange = $startCode ? ($startCode . ($endCode !== $startCode ? '-' . $endCode : '')) : '';

                if (empty($item['seller_roll_id'])) {
                    Database::execute(
                        "INSERT INTO seller_rolls 
                            (concern_id, seller_id, roll_number, color, design_name, weight, price, material_type,
                             start_seq, end_seq, start_code, end_code, qr_code, hsn_code, quantity, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                        [
                            $generationConcernId ?: 1,
                            !empty($item['seller_id']) ? (int)$item['seller_id'] : 1,
                            $rollNumber,
                            $item['color'] ?? null,
                            $item['design_name'] ?? null,
                            (float)($item['weight_kg'] ?? 0),
                            (float)($item['unit_price'] ?? 0),
                            $item['item_name'] ?? 'Fabric Roll',
                            $startSeq,
                            $endSeq,
                            $startCode,
                            $endCode,
                            $qrCodeRange,
                            $item['hsn_code'] ?? null,
                            count($codes)
                        ]
                    );
                    $newSrId = (int)Database::lastInsertId();
                    Database::execute("UPDATE production_purchase_items SET seller_roll_id = ? WHERE id = ?", [$newSrId, $itemId]);
                } else {
                    Database::execute(
                        "UPDATE seller_rolls 
                         SET start_seq = ?, end_seq = ?, start_code = ?, end_code = ?, qr_code = ?, quantity = ?, updated_at = NOW()
                         WHERE id = ?",
                        [
                            $startSeq,
                            $endSeq,
                            $startCode,
                            $endCode,
                            $qrCodeRange,
                            count($codes),
                            (int)$item['seller_roll_id']
                        ]
                    );
                }
            }

            Database::commit();

            return [
                'generation_id' => $generationId,
                'batch_code'    => $batchCode,
                'brand_name'    => $brandName,
                'qr_prefix'     => $qrPrefix,
                'quantity'      => $inserted,
                'source'        => $source,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Generate QR code batch directly from a Purchase Item Mapping creation (mapping invoice/items).
     */
    public static function generateMappingBatch(int $mappingItemId, string $source, array $data): array {
        if ($mappingItemId <= 0) {
            throw new InvalidArgumentException('Select a mapping item.');
        }

        $item = Database::fetchOne(
            "SELECT mi.*, mb.invoice_number, mb.concern_id, mb.seller_id, mb.status AS bill_status, mb.purchase_id,
                    s.name AS seller_name, s.company AS seller_company,
                    pv.variant_code, pv.sku, pv.mrp AS variant_mrp, pv.selling_price AS variant_selling_price,
                    c.name AS category_name, c.code AS category_code,
                    sc.name AS subcategory_name, ssc.name AS sub_subcategory_name
             FROM third_party_mapping_items mi
             INNER JOIN third_party_mapping_bills mb ON mb.id = mi.mapping_bill_id
             LEFT JOIN sellers s ON s.id = mb.seller_id
             LEFT JOIN product_variants pv ON pv.id = mi.variant_id
             LEFT JOIN categories c ON c.id = mi.category_id
             LEFT JOIN subcategories sc ON sc.id = mi.subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = mi.sub_subcategory_id
             WHERE mi.id = ?",
            [$mappingItemId]
        );
        if (!$item) {
            throw new InvalidArgumentException('Mapping item not found.');
        }

        if (strtoupper((string)($item['item_type'] ?? '')) !== $source) {
            throw new InvalidArgumentException('This item does not belong to the selected QR area.');
        }

        if (!empty($item['qr_generation_id'])) {
            throw new RuntimeException('A QR batch has already been generated for this item.');
        }
        if (Database::fetchOne("SELECT id FROM qr_generations WHERE source_mapping_item_id = ? LIMIT 1", [$mappingItemId])) {
            throw new RuntimeException('A QR batch has already been generated for this item.');
        }

        $activeCid = function_exists('currentConcernId') ? currentConcernId() : null;
        $generationConcernId = !empty($item['concern_id']) ? (int)$item['concern_id'] : $activeCid;

        $brandInput = trim((string)($data['brand_name'] ?? ''));
        if ($source === self::SOURCE_THIRD_PARTY) {
            if ($brandInput === '') {
                $brandInput = trim((string)($item['seller_company'] ?: ($item['seller_name'] ?? 'GRM')));
            }
            if ($brandInput === '') {
                throw new InvalidArgumentException('Enter the brand name exactly as it should appear on the QR labels.');
            }
            $brandName = $brandInput;
        } else {
            $brandName = mb_strtoupper(trim((string)($item['seller_company'] ?: ($item['seller_name'] ?? 'GRM'))));
        }

        $productId = (int)($item['product_id'] ?? 0);
        $variantId = (int)($item['variant_id'] ?? 0);
        $categoryId = !empty($item['category_id']) ? (int)$item['category_id'] : null;
        $subcategoryId = !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null;
        $subSubcategoryId = !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null;

        // Ensure product & variant exist
        if (($variantId <= 0 || $productId <= 0) && class_exists('VariantService')) {
            try {
                if ($productId <= 0) {
                    $prodName = trim((string)($item['item_name'] ?: 'Mapping Item ' . $mappingItemId));
                    $newProd = Database::fetchOne("SELECT id FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1", [$prodName]);
                    if ($newProd) {
                        $productId = (int)$newProd['id'];
                    } else {
                        Database::execute(
                            "INSERT INTO products (name, code, category_id, subcategory_id, concern_id, is_active, created_at)
                             VALUES (?, ?, ?, ?, ?, 1, NOW())",
                            [$prodName, 'MAP-' . $mappingItemId, $categoryId, $subcategoryId, $generationConcernId]
                        );
                        $productId = (int)Database::lastInsertId();
                    }
                    Database::execute("UPDATE third_party_mapping_items SET product_id = ? WHERE id = ?", [$productId, $mappingItemId]);
                }
                $resolvedVariant = VariantService::findOrCreateVariantForInward($productId, [
                    'color'         => $item['color'] ?? null,
                    'size'          => $item['size'] ?? null,
                    'design_name'   => $item['item_name'] ?? null,
                    'hsn_code'      => $item['hsn_code'] ?: null,
                    'mrp'           => (float)($item['mrp'] ?? 0),
                    'selling_price' => (float)($item['selling_price'] ?? 0),
                    'unit_price'    => (float)($item['unit_price'] ?? 0),
                ]);
                if (!empty($resolvedVariant['id'])) {
                    $variantId = (int)$resolvedVariant['id'];
                    Database::execute("UPDATE third_party_mapping_items SET variant_id = ? WHERE id = ?", [$variantId, $mappingItemId]);
                }
            } catch (\Throwable $e) {}
        }

        $codes = [];
        $mrp = (float)($item['mrp'] ?: ($item['variant_mrp'] ?? 0));
        $sellingPrice = (float)($item['selling_price'] ?: ($item['variant_selling_price'] ?? ($item['unit_price'] ?? 0)));

        if ($source === self::SOURCE_THIRD_PARTY) {
            $qrPrefix = self::catalogPrefix($productId ?: 1, $variantId ?: 1, $brandName);
            $qty = max(1, min(1000, (int)round((float)($item['quantity'] ?? 1))));
            $startSeq = self::nextSequence($qrPrefix);
            for ($i = 0; $i < $qty; $i++) {
                $seq = $startSeq + $i;
                $code = $qrPrefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$code])) {
                    $seq++;
                    $code = $qrPrefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
                }
                $codes[] = ['code' => $code, 'sequence' => $seq, 'status' => 'ACTIVE'];
            }
        } else {
            // Fabric roll
            $defaultPrefix = 'FB' . date('my');
            $startCode = trim((string)($item['roll_code'] ?? ''));
            $padLen = 2;
            if ($startCode !== '' && preg_match('/^([A-Za-z]{2}\d{4})(\d+)$/', $startCode, $m)) {
                $qrPrefix = $m[1];
                $padLen = max(2, strlen($m[2]));
            } elseif ($startCode !== '' && preg_match('/^([A-Za-z0-9_-]+?)(\d+)$/', $startCode, $m)) {
                $qrPrefix = $m[1];
                $padLen = max(2, strlen($m[2]));
            } else {
                $qrPrefix = $defaultPrefix;
            }
            $startSeq = self::nextSequence($qrPrefix);
            $qty = max(1, (int)round((float)($item['quantity'] ?? 1)));
            for ($i = 0; $i < $qty; $i++) {
                $seq = $startSeq + $i;
                $code = sprintf('%s%0' . $padLen . 'd', $qrPrefix, $seq);
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$code])) {
                    $seq++;
                    $code = sprintf('%s%0' . $padLen . 'd', $qrPrefix, $seq);
                }
                $codes[] = ['code' => $code, 'sequence' => $seq, 'status' => 'ACTIVE'];
            }
        }

        if (empty($codes)) {
            throw new InvalidArgumentException('No QR labels could be derived from this mapping item.');
        }

        $batchCode = self::generateBatchCode();
        $generationType = $source === self::SOURCE_THIRD_PARTY ? 'CATALOG_VARIANT' : 'MANUAL';
        $notes = trim((string)($data['notes'] ?? ''));
        if ($notes === '') {
            $notes = "Generated from Mapping Bill {$item['invoice_number']} — {$item['item_name']}";
        }

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO qr_generations
                    (batch_code, generation_type, qr_source, concern_id, source_purchase_id, source_purchase_item_id,
                     source_mapping_item_id, source_variant_id,
                     brand_name, category_id, subcategory_id, sub_subcategory_id,
                     category_name, subcategory_name, sub_subcategory_name,
                     product_id, variant_id, color_name, design_name, size_name, mrp, selling_price, quantity, qr_prefix, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $batchCode, $generationType, $source, $generationConcernId,
                    !empty($item['purchase_id']) ? (int)$item['purchase_id'] : null,
                    !empty($item['purchase_item_id']) ? (int)$item['purchase_item_id'] : null,
                    $mappingItemId, $variantId ?: null,
                    $brandName, $categoryId, $subcategoryId, $subSubcategoryId,
                    $item['category_name'] ?? null, $item['subcategory_name'] ?? null, $item['sub_subcategory_name'] ?? null,
                    $productId ?: null, $variantId ?: null,
                    $item['color'] ?? null, $item['item_name'] ?? null, $item['size'] ?? null,
                    $mrp, $sellingPrice, count($codes), $qrPrefix, $notes,
                    (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null,
                ]
            );
            $generationId = (int)Database::lastInsertId();

            $inserted = 0;
            $qrItemColumns = array_column(Database::fetchAll("SHOW COLUMNS FROM qr_generation_items"), 'Field');
            $insertColumns = ['generation_id', 'qr_code', 'sequence_num', 'status', 'color_name', 'design_name', 'size_name'];
            if (in_array('item_code', $qrItemColumns, true)) {
                $insertColumns[] = 'item_code';
            }
            if (in_array('sequence_number', $qrItemColumns, true)) {
                $insertColumns[] = 'sequence_number';
            }
            $insertSql = "INSERT INTO qr_generation_items (`" . implode('`, `', $insertColumns) . "`) VALUES ("
                . implode(', ', array_fill(0, count($insertColumns), '?')) . ")";

            foreach ($codes as $codeRow) {
                try {
                    Database::execute(
                        $insertSql,
                        array_merge(
                            [
                                $generationId,
                                $codeRow['code'],
                                (int)$codeRow['sequence'],
                                $codeRow['status'],
                                $item['color'] ?? null,
                                $item['item_name'] ?? null,
                                $item['size'] ?? null,
                            ],
                            in_array('item_code', $qrItemColumns, true) ? [$codeRow['code']] : [],
                            in_array('sequence_number', $qrItemColumns, true) ? [(int)$codeRow['sequence']] : []
                        )
                    );
                    $inserted++;
                } catch (PDOException $e) {
                    if ((int)($e->errorInfo[1] ?? 0) !== 1062) {
                        throw $e;
                    }
                }
            }
            if ($inserted === 0) {
                throw new RuntimeException("No QR labels were inserted. Every label in this code range already exists.");
            }

            Database::execute("UPDATE qr_generations SET quantity = ? WHERE id = ?", [$inserted, $generationId]);
            Database::execute(
                "UPDATE third_party_mapping_items SET qr_generation_id = ? WHERE id = ?",
                [$generationId, $mappingItemId]
            );

            if (!empty($item['purchase_item_id'])) {
                Database::execute(
                    "UPDATE production_purchase_items SET qr_generation_id = ?, qr_batch_code = ? WHERE id = ?",
                    [$generationId, $batchCode, (int)$item['purchase_item_id']]
                );
            }

            if ($variantId > 0 && !empty($codes[0]['code'])) {
                Database::execute(
                    "UPDATE product_variants SET qr_code = ? WHERE id = ? AND (qr_code IS NULL OR qr_code = '')",
                    [$codes[0]['code'], $variantId]
                );
            }

            // Move roll directly to purchased stock upon QR generation
            if ($source === self::SOURCE_FABRIC) {
                $curUserId = (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : null;
                Database::execute(
                    "UPDATE qr_generations SET stock_added = 1, stock_added_at = NOW(), stock_added_by = ? WHERE id = ?",
                    [$curUserId, $generationId]
                );

                // Create or link seller_rolls record
                try {
                    $rollNumber = trim((string)($item['roll_code'] ?: ($codes[0]['code'] ?? 'ROLL-1')));
                    $existingSr = Database::fetchOne(
                        "SELECT id FROM seller_rolls WHERE qr_code = ? OR (seller_id = ? AND roll_number = ?) LIMIT 1",
                        [$codes[0]['code'] ?? '', !empty($item['seller_id']) ? (int)$item['seller_id'] : 1, $rollNumber]
                    );
                    if (!$existingSr) {
                        Database::execute(
                            "INSERT INTO seller_rolls 
                                (concern_id, seller_id, roll_number, color, design_name, weight, price, material_type, qr_code, hsn_code, quantity, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                            [
                                $generationConcernId ?: 1,
                                !empty($item['seller_id']) ? (int)$item['seller_id'] : 1,
                                $rollNumber,
                                $item['color'] ?? null,
                                $item['item_name'] ?? null,
                                (float)($item['weight_kg'] ?? 0),
                                (float)($item['unit_price'] ?? 0),
                                $item['item_name'] ?? null,
                                $codes[0]['code'] ?? null,
                                $item['hsn_code'] ?? null,
                            ]
                        );
                    }
                } catch (\Throwable $e) {}

                // Increase warehouse stock
                if ($variantId > 0 && class_exists('StockService')) {
                    $warehouse = Database::fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1")
                        ?: Database::fetchOne("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
                    $warehouseId = $warehouse ? (int)$warehouse['id'] : 1;
                    try {
                        StockService::increase(
                            $variantId,
                            'WAREHOUSE',
                            $warehouseId,
                            'AVAILABLE',
                            (float)($item['quantity'] ?: 1),
                            'INWARD',
                            'QR_BATCH',
                            $generationId,
                            $curUserId,
                            "QR batch {$batchCode} — fabric roll credited to purchased stock"
                        );
                    } catch (\Throwable $e) {}
                }
            }

            Database::commit();

            return [
                'generation_id' => $generationId,
                'batch_code'    => $batchCode,
                'quantity'      => $inserted,
                'brand_name'    => $brandName,
                'qr_prefix'     => $qrPrefix,
                'source'        => $source,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Handle QR generation from a completed Stitching Unit batch.
     * Generates QR codes for stitched pieces and automatically registers a Quality Control (QC) batch.
     */
    public static function generateStitchingBatch(array $data): array {
        self::ensureSourceSchema();

        $stitchingBatchId = (int)($data['stitching_batch_id'] ?? 0);
        if ($stitchingBatchId <= 0) {
            throw new InvalidArgumentException('Please select a Stitching Batch.');
        }

        $stitchingBatch = Database::fetchOne("
            SELECT sb.*, pp.program_number, pp.name AS program_name, pp.product_id AS prog_product_id,
                   p.name AS product_name, p.code AS product_code, p.gst_percentage,
                   COALESCE(p.variants_deferred, 0) AS variants_deferred,
                   c.name AS category_name, sc.name AS subcategory_name, ssc.name AS sub_subcategory_name
            FROM stitching_batches sb
            LEFT JOIN production_programs pp ON pp.id = sb.program_id
            LEFT JOIN products p ON p.id = pp.product_id
            LEFT JOIN categories c ON c.id = COALESCE(sb.category_id, p.category_id)
            LEFT JOIN subcategories sc ON sc.id = COALESCE(sb.subcategory_id, p.subcategory_id)
            LEFT JOIN sub_subcategories ssc ON ssc.id = COALESCE(sb.sub_subcategory_id, p.sub_subcategory_id)
            WHERE sb.id = ?
        ", [$stitchingBatchId]);

        if (!$stitchingBatch) {
            throw new InvalidArgumentException('Stitching batch record not found.');
        }

        // Auto-complete stitching batch if not yet marked COMPLETED
        if (strtoupper(trim((string)($stitchingBatch['status'] ?? ''))) !== 'COMPLETED') {
            Database::execute("UPDATE stitching_batches SET status = 'COMPLETED', updated_at = NOW() WHERE id = ?", [$stitchingBatchId]);
            $stitchingBatch['status'] = 'COMPLETED';
        }

        if (!empty($stitchingBatch['qr_generation_id'])) {
            throw new RuntimeException('QR batch has already been generated for this Stitching Batch.');
        }

        $items = Database::fetchAll("
            SELECT si.*, v.variant_code, v.sku, v.mrp AS variant_mrp, v.selling_price AS variant_selling_price,
                   col.name AS variant_color_name, sz.name AS variant_size_name,
                   v.product_id AS variant_product_id
            FROM stitching_items si
            LEFT JOIN product_variants v ON v.id = si.variant_id
            LEFT JOIN colors col ON col.id = v.color_id
            LEFT JOIN sizes sz ON sz.id = v.size_id
            WHERE si.stitching_batch_id = ?
        ", [$stitchingBatchId]);

        $totalQty = 0;
        foreach ($items as $it) {
            $totalQty += max(0, (int)($it['stitched_quantity'] ?: ($it['stitched_qty'] ?: $it['received_qty'])));
        }
        if ($totalQty <= 0) {
            $totalQty = max(1, (int)($stitchingBatch['stitched_qty'] ?: $stitchingBatch['received_qty']));
        }
        if (!empty($stitchingBatch['variants_deferred']) && empty($items)) {
            throw new RuntimeException('Add size and color quantities to the completed stitching batch before generating garment variants.');
        }

        $batchCode = self::generateBatchCode();
        $brandName = trim((string)($data['brand_name'] ?? '')) ?: 'GRM APPARELS';
        $prefix = trim((string)($data['qr_prefix'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $cid = function_exists('currentConcernId') ? currentConcernId() : ($stitchingBatch['concern_id'] ?? null);
        $userId = (session_status() === PHP_SESSION_ACTIVE && class_exists('Auth') && Auth::check()) ? Auth::id() : 1;

        // Unified piece tracking QR for Production Pieces:
        // Prefers Variant ID (e.g. V0001, V0002) as the piece QR prefix.
        $cleanPrefix = strtoupper(trim(preg_replace('/[^A-Za-z0-9_-]+/', '', $prefix)));
        $useVariantPrefix = empty($cleanPrefix) || str_starts_with($cleanPrefix, 'PRD');

        $productId = (int)($stitchingBatch['prog_product_id'] ?? 0);
        if ($productId <= 0 && !empty($stitchingBatch['program_id'])) {
            $progRow = Database::fetchOne("SELECT product_id FROM production_programs WHERE id = ?", [(int)$stitchingBatch['program_id']]);
            $productId = (int)($progRow['product_id'] ?? 0);
        }

        // Auto-resolve or create variant_id for each stitching item if missing
        if (!empty($items)) {
            foreach ($items as $index => &$it) {
                $variantId = (int)($it['variant_id'] ?? 0);
                $sizeName = trim((string)($it['variant_size_name'] ?: ($it['size_name'] ?? '')));
                $colorName = trim((string)($it['variant_color_name'] ?: ($it['color_name'] ?? '')));
                if (in_array(strtoupper($sizeName), ['STANDARD', 'ALL'], true)) {
                    $sizeName = '';
                }
                if (in_array(strtoupper($colorName), ['STANDARD', 'ASSORTED', 'ALL'], true)) {
                    $colorName = '';
                }
                $mrp = (float)(($it['variant_mrp'] ?? 0) > 0 ? ($it['variant_mrp'] ?? 0) : ($it['base_price'] ?? 0));
                $sellingPrice = (float)(($it['variant_selling_price'] ?? 0) > 0 ? ($it['variant_selling_price'] ?? 0) : ($it['discount_price'] ?? 0));

                if ($variantId <= 0 && $productId > 0) {
                    try {
                        $variant = VariantService::findOrCreateVariantForInward($productId, [
                            'size' => $sizeName ?: null,
                            'color' => $colorName ?: null,
                            'mrp' => $mrp,
                            'selling_price' => $sellingPrice,
                            'is_active' => 1,
                        ], true);
                        $variantId = (int)($variant['id'] ?? 0);
                        if ($variantId > 0) {
                            $it['variant_id'] = $variantId;
                            $it['variant_code'] = $variant['variant_code'] ?? null;
                            $it['sku'] = $variant['sku'] ?? null;
                            $it['variant_mrp'] = $variant['mrp'] ?? $mrp;
                            $it['variant_selling_price'] = $variant['selling_price'] ?? $sellingPrice;
                            Database::execute("UPDATE stitching_items SET variant_id = ? WHERE id = ?", [$variantId, (int)$it['id']]);
                        }
                    } catch (\Throwable $e) {
                        // ignore error
                    }
                }
            }
            unset($it);
        }

        $codes = [];
        $sequence = self::nextSequence($cleanPrefix);
        $uniqueVariants = [];
        $firstProductId = $productId;
        if (!empty($items)) {
            foreach ($items as $it) {
                $variantId = (int)($it['variant_id'] ?? 0);
                $itemQty = max(0, (int)($it['stitched_quantity'] ?: ($it['stitched_qty'] ?: $it['received_qty'])));
                if ($itemQty <= 0) continue;

                $sizeName = trim((string)($it['variant_size_name'] ?: ($it['size_name'] ?? '')));
                $colorName = trim((string)($it['variant_color_name'] ?: ($it['color_name'] ?? '')));
                $mrp = (float)(($it['variant_mrp'] ?? 0) > 0 ? ($it['variant_mrp'] ?? 0) : ($it['base_price'] ?? 0));
                $sellingPrice = (float)(($it['variant_selling_price'] ?? 0) > 0 ? ($it['variant_selling_price'] ?? 0) : ($it['discount_price'] ?? 0));

                if ($variantId > 0) {
                    $uniqueVariants[$variantId] = true;
                    if (!$firstProductId) $firstProductId = (int)($it['variant_product_id'] ?? $productId);
                }

                // Resolve variant_code
                $vCode = $it['variant_code'] ?? null;
                if (!$vCode && $variantId > 0) {
                    $vRow = Database::fetchOne("SELECT variant_code FROM product_variants WHERE id = ?", [$variantId]);
                    $vCode = $vRow['variant_code'] ?? null;
                }
                $vCode = $vCode ?: ($variantId > 0 ? ('V' . str_pad((string)$variantId, 4, '0', STR_PAD_LEFT)) : ($cleanPrefix ?: 'V0001'));
                $itemPrefix = $useVariantPrefix ? $vCode : $cleanPrefix;

                for ($i = 0; $i < $itemQty; $i++) {
                    $code = $itemPrefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
                    while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$code])) {
                        $sequence++;
                        $code = $itemPrefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
                    }
                    $codes[] = [
                        'code' => $code,
                        'sequence' => $sequence,
                        'status' => 'ACTIVE',
                        'variant_id' => $variantId ?: null,
                        'stitching_item_id' => (int)$it['id'],
                        'size_name' => $sizeName ?: null,
                        'color_name' => $colorName ?: null,
                        'mrp' => $mrp,
                        'selling_price' => $sellingPrice,
                    ];
                    $sequence++;
                }

                if ($variantId > 0) {
                    try {
                        Database::execute(
                            "UPDATE product_variants SET qr_code = ? WHERE id = ? AND (qr_code IS NULL OR qr_code = '')",
                            [$vCode, $variantId]
                        );
                    } catch (\Throwable $e) {}
                }
            }
        } else {
            for ($i = 0; $i < $totalQty; $i++) {
                $code = $cleanPrefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
                while (Database::fetchOne("SELECT id FROM qr_generation_items WHERE qr_code = ?", [$code])) {
                    $sequence++;
                    $code = $cleanPrefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
                }
                $codes[] = [
                    'code' => $code,
                    'sequence' => $sequence,
                    'status' => 'ACTIVE',
                    'variant_id' => null,
                    'stitching_item_id' => null,
                    'size_name' => null,
                    'color_name' => null,
                    'mrp' => 0.0,
                    'selling_price' => 0.0,
                ];
                $sequence++;
            }
        }
        if (empty($codes)) {
            throw new RuntimeException('No stitched pieces are available for QR generation.');
        }
        $totalQty = count($codes);
        Database::beginTransaction();
        try {
            if ($productId > 0) {
                foreach ($items as $index => $it) {
                    $itemQty = max(0, (int)($it['stitched_quantity'] ?: ($it['stitched_qty'] ?: $it['received_qty'])));
                    if ($itemQty <= 0) {
                        continue;
                    }

                    $variantId = (int)($it['variant_id'] ?? 0);
                    $sizeName = trim((string)($it['variant_size_name'] ?: ($it['size_name'] ?? '')));
                    $colorName = trim((string)($it['variant_color_name'] ?: ($it['color_name'] ?? '')));
                    if (in_array(strtoupper($sizeName), ['STANDARD', 'ALL'], true)) {
                        $sizeName = '';
                    }
                    if (in_array(strtoupper($colorName), ['STANDARD', 'ASSORTED', 'ALL'], true)) {
                        $colorName = '';
                    }
                    $mrp = (float)(($it['variant_mrp'] ?? 0) > 0 ? ($it['variant_mrp'] ?? 0) : ($it['base_price'] ?? 0));
                    $sellingPrice = (float)(($it['variant_selling_price'] ?? 0) > 0 ? ($it['variant_selling_price'] ?? 0) : ($it['discount_price'] ?? 0));

                    if ($variantId <= 0) {
                        try {
                            $variant = VariantService::findOrCreateVariantForInward($productId, [
                                'size' => $sizeName ?: null,
                                'color' => $colorName ?: null,
                                'mrp' => $mrp,
                                'selling_price' => $sellingPrice,
                                'is_active' => 1,
                            ], true);
                            $variantId = (int)($variant['id'] ?? 0);
                            if ($variantId > 0) {
                                Database::execute("UPDATE stitching_items SET variant_id = ? WHERE id = ?", [$variantId, (int)$it['id']]);
                            }
                        } catch (\Throwable $e) {}
                    }

                    if ($variantId > 0) {
                        $items[$index]['variant_id'] = $variantId;
                        $items[$index]['variant_product_id'] = $productId;
                        $uniqueVariants[$variantId] = true;
                        foreach ($codes as $codeIndex => $codeItem) {
                            if ((int)($codeItem['stitching_item_id'] ?? 0) === (int)$it['id']) {
                                $codes[$codeIndex]['variant_id'] = $variantId;
                            }
                        }
                    }
                }
                $firstProductId = $productId;
            }

            $batchVariantId = count($uniqueVariants) === 1 ? (int)array_key_first($uniqueVariants) : null;
            Database::execute("
                INSERT INTO qr_generations (
                    batch_code, generation_type, qr_source, stitching_batch_id, program_id,
                    product_id, variant_id, category_name, subcategory_name, sub_subcategory_name,
                    brand_name, quantity, qr_prefix, notes, stock_added, created_by, concern_id
                ) VALUES (?, 'CATALOG_VARIANT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ", [
                $batchCode,
                self::SOURCE_PRODUCTION,
                $stitchingBatchId,
                (int)$stitchingBatch['program_id'],
                $firstProductId ?: ((int)($stitchingBatch['prog_product_id'] ?? 0) ?: null),
                $batchVariantId,
                $stitchingBatch['category_name'] ?? null,
                $stitchingBatch['subcategory_name'] ?? null,
                $stitchingBatch['sub_subcategory_name'] ?? null,
                $brandName,
                $totalQty,
                $cleanPrefix,
                $notes,
                $userId,
                $cid
            ]);
            $generationId = (int)Database::lastInsertId();

            $itemStmt = Database::getInstance()->prepare("
                INSERT INTO qr_generation_items (
                    generation_id, variant_id, size_name, color_name, mrp, selling_price,
                    qr_code, sequence_num, status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($codes as $c) {
                $itemStmt->execute([
                    $generationId,
                    $c['variant_id'],
                    $c['size_name'],
                    $c['color_name'],
                    $c['mrp'],
                    $c['selling_price'],
                    $c['code'],
                    $c['sequence'],
                    $c['status']
                ]);
            }

            Database::execute("
                UPDATE stitching_batches
                SET qr_generation_id = ?, qr_batch_code = ?, status = 'COMPLETED'
                WHERE id = ?
            ", [$generationId, $batchCode, $stitchingBatchId]);

            // Link existing QC batch if one was already registered for this stitching batch
            $existingQc = Database::fetchOne("SELECT id FROM qc_batches WHERE stitching_batch_id = ? AND qr_generation_id IS NULL", [$stitchingBatchId]);
            $qcBatchId = null;
            if ($existingQc) {
                $qcBatchId = (int)$existingQc['id'];
                Database::execute("UPDATE qc_batches SET qr_generation_id = ? WHERE id = ?", [$generationId, $qcBatchId]);
                Database::execute("UPDATE qr_generations SET qc_batch_id = ? WHERE id = ?", [$qcBatchId, $generationId]);
                if (!empty($stitchingBatch['variants_deferred'])) {
                    Database::execute("
                        UPDATE qc_items qi
                        JOIN stitching_items si
                          ON si.stitching_batch_id = ?
                         AND UPPER(TRIM(COALESCE(qi.size_name, ''))) = UPPER(TRIM(COALESCE(si.size_name, '')))
                         AND UPPER(TRIM(COALESCE(qi.color_name, ''))) = UPPER(TRIM(COALESCE(si.color_name, '')))
                        SET qi.variant_id = si.variant_id
                        WHERE qi.qc_batch_id = ? AND qi.variant_id IS NULL AND si.variant_id IS NOT NULL
                    ", [$stitchingBatchId, $qcBatchId]);
                }
            }

            Database::commit();

            return [
                'generation_id' => $generationId,
                'batch_code'    => $batchCode,
                'brand_name'    => $brandName,
                'quantity'      => $totalQty,
                'qc_batch_id'   => $qcBatchId,
                'source'        => self::SOURCE_PRODUCTION,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Next sequence number for a prefix (shared by catalog and purchase flows).
     */
    private static function nextSequence(string $prefix): int {
        $row = Database::fetchOne(
            "SELECT MAX(qri.sequence_num) AS max_seq
             FROM qr_generation_items qri
             JOIN qr_generations qrg ON qrg.id = qri.generation_id
             WHERE qrg.qr_prefix = ?",
            [$prefix]
        );
        return (int)($row['max_seq'] ?? 0) + 1;
    }

    /**
     * Build the QR prefix for a mapped third-party product/variant.
     */
    private static function catalogPrefix(int $productId, int $variantId, string $brandName): string {
        $product = Database::fetchOne(
            "SELECT p.code, c.code AS category_code, c.name AS category_name,
                    sc.code AS subcategory_code, sc.name AS subcategory_name,
                    ssc.code AS sub_subcategory_code, ssc.name AS sub_subcategory_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
             WHERE p.id = ?",
            [$productId]
        );
        $variant = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
        $brandCode = self::extractCode($brandName, 4);

        if (!empty($variant['sku'])) {
            return $brandCode . '-' . $variant['sku'];
        }
        $parts = [$brandCode];
        if (!empty($product['category_code'])) {
            $parts[] = $product['category_code'];
        } elseif (!empty($product['category_name'])) {
            $parts[] = self::extractCode($product['category_name'], 3);
        }
        if (!empty($product['subcategory_code'])) {
            $parts[] = $product['subcategory_code'];
        } elseif (!empty($product['subcategory_name'])) {
            $parts[] = self::extractCode($product['subcategory_name'], 3);
        }
        if (!empty($product['code'])) {
            $parts[] = $product['code'];
        }
        return implode('-', $parts);
    }

    /**
     * Add a generated QR batch to warehouse inventory.
     *
     * Stock is NEVER credited automatically on QR generation or mapping.
     * Fabric rolls are excluded: they must pass QC before entering inventory.
     */
    public static function addToStock(int $generationId, int $userId = 0, ?int $targetWarehouseId = null): array {
        self::ensureSourceSchema();

        $batch = self::getBatch($generationId);
        if (!$batch) {
            throw new InvalidArgumentException('QR batch not found.');
        }

        $source = strtoupper((string)($batch['qr_source'] ?? ''));
        if (!in_array($source, [self::SOURCE_FABRIC, self::SOURCE_RAW, self::SOURCE_THIRD_PARTY], true)) {
            throw new RuntimeException('This QR batch type cannot be added to purchased stock directly.');
        }
        if (!empty($batch['stock_added'])) {
            throw new RuntimeException('Stock for this QR batch has already been added.');
        }

        $itemId = (int)($batch['source_purchase_item_id'] ?? 0);
        if ($itemId <= 0) {
            $mappingItemId = (int)($batch['source_mapping_item_id'] ?? 0);
            if ($mappingItemId <= 0) {
                throw new RuntimeException('This QR batch is not linked to a purchase or Mapping product.');
            }
            $mappingItem = Database::fetchOne(
                "SELECT mi.*, mb.concern_id
                 FROM third_party_mapping_items mi
                 JOIN third_party_mapping_bills mb ON mb.id = mi.mapping_bill_id
                 WHERE mi.id = ? AND mi.qr_generation_id = ?",
                [$mappingItemId, $generationId]
            );
            if (!$mappingItem || (int)$mappingItem['variant_id'] <= 0) {
                if (!empty($mappingItem['product_id']) && class_exists('VariantService')) {
                    try {
                        $resolved = VariantService::findOrCreateVariantForInward((int)$mappingItem['product_id'], [
                            'color' => $mappingItem['color'] ?? null,
                            'size' => $mappingItem['size'] ?? null,
                            'mrp' => (float)($mappingItem['mrp'] ?? 0),
                            'selling_price' => (float)($mappingItem['selling_price'] ?? 0),
                            'unit_price' => (float)($mappingItem['unit_price'] ?? 0),
                        ]);
                        if (!empty($resolved['id'])) {
                            Database::execute("UPDATE third_party_mapping_items SET variant_id = ? WHERE id = ?", [(int)$resolved['id'], $mappingItemId]);
                            $mappingItem['variant_id'] = (int)$resolved['id'];
                        }
                    } catch (\Throwable $e) {}
                }
            }
            if (!$mappingItem || (int)$mappingItem['variant_id'] <= 0) {
                throw new RuntimeException('The Mapping product variant for this QR batch could not be found.');
            }
            $alreadyPostedMapping = Database::fetchOne(
                "SELECT id FROM qr_generations
                 WHERE source_mapping_item_id = ? AND stock_added = 1 AND id <> ?
                 LIMIT 1",
                [$mappingItemId, $generationId]
            );
            if ($alreadyPostedMapping) {
                throw new RuntimeException('Stock for this Mapping product was already added through another QR batch.');
            }

            $quantity = max(1.0, (float)($batch['quantity'] ?: count($batch['items'])));
            $warehouse = Database::fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1")
                ?: Database::fetchOne("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
            $warehouseId = $targetWarehouseId ?: ($warehouse ? (int)$warehouse['id'] : 1);
            if (!class_exists('StockService')) {
                throw new RuntimeException('Stock service is unavailable; the QR batch was not posted to stock.');
            }
            Database::beginTransaction();
            try {
                StockService::increase(
                    (int)$mappingItem['variant_id'],
                    'WAREHOUSE',
                    $warehouseId,
                    'AVAILABLE',
                    $quantity,
                    'INWARD',
                    'QR_BATCH',
                    $generationId,
                    $userId ?: null,
                    "QR batch {$batch['batch_code']} — stock added from Mapping invoice"
                );
                Database::execute(
                    "UPDATE qr_generations SET stock_added = 1, stock_added_at = NOW(), stock_added_by = ? WHERE id = ?",
                    [$userId ?: null, $generationId]
                );
                Database::commit();
                return [
                    'batch_code' => $batch['batch_code'],
                    'quantity' => $quantity,
                    'stock_added' => true,
                ];
            } catch (Throwable $e) {
                Database::rollback();
                throw $e;
            }
        }
        $item = Database::fetchOne(
            "SELECT ppi.*, pp.purchase_number
             FROM production_purchase_items ppi
             INNER JOIN production_purchases pp ON pp.id = ppi.purchase_id
             WHERE ppi.id = ?",
            [$itemId]
        );
        if (!$item) {
            throw new RuntimeException('Linked purchase item not found.');
        }

        // One purchase line may only be received into inventory once, even if
        // several QR batches were generated for it.
        $alreadyPosted = Database::fetchOne(
            "SELECT id FROM qr_generations
             WHERE source_purchase_item_id = ? AND stock_added = 1 AND id <> ?
             LIMIT 1",
            [$itemId, $generationId]
        );
        if ($alreadyPosted) {
            throw new RuntimeException('Stock for this purchase item was already added through another QR batch.');
        }

        $quantity = max(1.0, (float)($batch['quantity'] ?: count($batch['items'])));
        $variantId = (int)($batch['variant_id'] ?? 0) ?: (int)($item['variant_id'] ?? 0);
        if ($source === self::SOURCE_THIRD_PARTY && $variantId <= 0) {
            $productId = (int)($batch['product_id'] ?? 0) ?: (int)($item['product_id'] ?? 0);
            if ($productId <= 0) {
                throw new RuntimeException('Map this product before adding it to stock.');
            }
            $variant = VariantService::findOrCreateVariantForInward($productId, [
                'color'         => $item['color'] ?? null,
                'size'          => $item['size'] ?? null,
                'design_name'   => $item['design_name'] ?? null,
                'hsn_code'      => $item['hsn_code'] ?: null,
                'mrp'           => (float)($batch['mrp'] ?: ($item['mrp'] ?? 0)),
                'selling_price' => (float)($batch['selling_price'] ?: ($item['selling_price'] ?? 0)),
                'unit_price'    => (float)($item['unit_price'] ?? 0),
            ]);
            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId <= 0) {
                throw new RuntimeException('Unable to resolve a stock variant for this item.');
            }
            Database::execute("UPDATE production_purchase_items SET variant_id = ? WHERE id = ?", [$variantId, $itemId]);
        }

        $warehouse = Database::fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1")
            ?: Database::fetchOne("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
        $warehouseId = $targetWarehouseId ?: ($warehouse ? (int)$warehouse['id'] : 1);

        Database::beginTransaction();
        try {
            if ($variantId > 0 && class_exists('StockService')) {
                StockService::increase(
                    $variantId,
                    'WAREHOUSE',
                    $warehouseId,
                    'AVAILABLE',
                    $quantity,
                    'INWARD',
                    'QR_BATCH',
                    $generationId,
                    $userId ?: null,
                    "QR batch {$batch['batch_code']} — stock added for purchase {$item['purchase_number']}"
                );
            }

            Database::execute(
                "UPDATE qr_generations SET stock_added = 1, stock_added_at = NOW(), stock_added_by = ? WHERE id = ?",
                [$userId ?: null, $generationId]
            );

            $grnId = (int)($item['grn_id'] ?? 0);
            Database::execute(
                "UPDATE production_purchase_items
                 SET is_inwarded = 1,
                     inwarded_qty = COALESCE(inwarded_qty, 0) + ?,
                     inwarded_at = NOW(),
                     inwarded_by = ?
                 WHERE id = ?",
                [$quantity, $userId ?: null, $itemId]
            );
            Database::execute(
                "UPDATE production_purchases
                 SET is_inwarded = 1, inwarded_at = NOW()
                 WHERE id = ?",
                [(int)$item['purchase_id']]
            );
            if ($grnId > 0) {
                // Mark the GRN as posted to stock. Warehouse is already stored on
                // purchase_inwards.warehouse_id, so do not overwrite the separate
                // location_id column here.
                Database::execute(
                    "UPDATE purchase_inwards SET status = 'STOCKED' WHERE id = ?",
                    [$grnId]
                );
            }

            if (class_exists('AuditService')) {
                AuditService::log('qr_generation', 'ADD_TO_STOCK', 'qr_generations', $generationId, null, [
                    'batch_code'  => $batch['batch_code'],
                    'source'      => $source,
                    'quantity'    => $quantity,
                    'warehouse_id'=> $warehouseId,
                ]);
            }

            Database::commit();

            return [
                'quantity'     => $quantity,
                'warehouse_id' => $warehouseId,
                'variant_id'   => $variantId,
            ];
        } catch (Throwable $e) {
            Database::rollback();
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Get a generation batch with items.
     */
    public static function getBatch(int $id): ?array {
        self::ensureSourceSchema();

        $gen = Database::fetchOne(
            "SELECT qrg.*, pv.variant_code, pv.sku as variant_sku, p.name as product_name, p.code as product_code,
                    sb.batch_number as stitching_batch_number, sb.name as stitching_batch_name,
                    pp.program_number, pp.name as program_name,
                    qb.id as qc_batch_id_resolved, qb.batch_number as qc_batch_number, qb.status as qc_batch_status, qb.passed_qty as qc_passed_qty,
                    purchase.purchase_date AS source_purchase_date
             FROM qr_generations qrg
             LEFT JOIN production_purchases purchase ON purchase.id = qrg.source_purchase_id
             LEFT JOIN product_variants pv ON pv.id = qrg.variant_id
             LEFT JOIN products p ON p.id = qrg.product_id
             LEFT JOIN stitching_batches sb ON sb.id = qrg.stitching_batch_id
             LEFT JOIN production_programs pp ON pp.id = qrg.program_id
             LEFT JOIN qc_batches qb ON qb.id = qrg.qc_batch_id
             WHERE qrg.id = ?
               AND UPPER(COALESCE(qrg.qr_source, '')) <> 'OWN_PRODUCT'",
            [$id]
        );
        if (!$gen) return null;

        $items = Database::fetchAll("SELECT * FROM qr_generation_items WHERE generation_id = ? ORDER BY sequence_num ASC", [$id]);

        $stitchingBatchId = (int)($gen['stitching_batch_id'] ?? 0);
        $productId = (int)($gen['product_id'] ?? 0);
        if ($productId <= 0 && $stitchingBatchId > 0) {
            $progProduct = Database::fetchOne("
                SELECT pp.product_id
                FROM stitching_batches sb
                JOIN production_programs pp ON pp.id = sb.program_id
                WHERE sb.id = ?
            ", [$stitchingBatchId]);
            if (!empty($progProduct['product_id'])) {
                $productId = (int)$progProduct['product_id'];
                $gen['product_id'] = $productId;
                Database::execute("UPDATE qr_generations SET product_id = ? WHERE id = ?", [$productId, $id]);
            }
        }

        if ($stitchingBatchId > 0) {
            $stitchingItems = Database::fetchAll("
                SELECT si.id, si.variant_id, si.size_name, si.color_name, si.base_price, si.discount_price,
                       COALESCE(NULLIF(si.stitched_qty, 0), NULLIF(si.stitched_quantity, 0), NULLIF(si.received_qty, 0), 0) AS stitched_qty,
                       pv.variant_code, pv.sku, pv.mrp AS variant_mrp, pv.selling_price AS variant_selling_price,
                       col.name AS variant_color_name, sz.name AS variant_size_name
                FROM stitching_items si
                LEFT JOIN product_variants pv ON pv.id = si.variant_id
                LEFT JOIN colors col ON col.id = pv.color_id
                LEFT JOIN sizes sz ON sz.id = pv.size_id
                WHERE si.stitching_batch_id = ?
                ORDER BY si.id ASC
            ", [$stitchingBatchId]);

            // If stitching items are missing variant_id, resolve them now
            foreach ($stitchingItems as &$si) {
                if (empty($si['variant_id']) && $productId > 0) {
                    try {
                        $sName = trim((string)($si['variant_size_name'] ?: ($si['size_name'] ?? '')));
                        $cName = trim((string)($si['variant_color_name'] ?: ($si['color_name'] ?? '')));
                        $resolved = VariantService::findOrCreateVariantForInward($productId, [
                            'size' => $sName ?: null,
                            'color' => $cName ?: null,
                            'mrp' => (float)($si['base_price'] ?? 0),
                            'selling_price' => (float)($si['discount_price'] ?? 0),
                            'is_active' => 1
                        ], true);
                        if (!empty($resolved['id'])) {
                            $si['variant_id'] = (int)$resolved['id'];
                            $si['variant_code'] = $resolved['variant_code'] ?? null;
                            $si['sku'] = $resolved['sku'] ?? null;
                            $si['variant_mrp'] = $resolved['mrp'] ?? $si['base_price'];
                            $si['variant_selling_price'] = $resolved['selling_price'] ?? $si['discount_price'];
                            if (!empty($si['id'])) {
                                Database::execute("UPDATE stitching_items SET variant_id = ? WHERE id = ?", [$si['variant_id'], (int)$si['id']]);
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }
            unset($si);

            $variantMap = [];
            $remainingByItem = [];
            foreach ($stitchingItems as $si) {
                if (!empty($si['variant_id'])) {
                    $variantMap[(int)$si['variant_id']] = $si;
                }
                $remainingByItem[] = max(0, (int)$si['stitched_qty']);
            }

            $itemCursor = 0;
            $usedInCursor = 0;
            $variantIds = [];
            foreach ($items as &$item) {
                $itemVariantId = (int)($item['variant_id'] ?? 0);
                if ($itemVariantId > 0 && isset($variantMap[$itemVariantId])) {
                    $si = $variantMap[$itemVariantId];
                } else {
                    while ($itemCursor < count($stitchingItems)
                        && $usedInCursor >= (int)$remainingByItem[$itemCursor]) {
                        $itemCursor++;
                        $usedInCursor = 0;
                    }
                    $si = $stitchingItems[$itemCursor] ?? null;
                    $usedInCursor++;
                }

                $sizeName = trim((string)($item['size_name'] ?: ($si['variant_size_name'] ?? ($si['size_name'] ?? ''))));
                $colorName = trim((string)($item['color_name'] ?: ($si['variant_color_name'] ?? ($si['color_name'] ?? ''))));
                $variantId = (int)($item['variant_id'] ?: ($si['variant_id'] ?? 0));

                if ($variantId <= 0 && $productId > 0 && ($sizeName !== '' || $colorName !== '')) {
                    try {
                        $resolved = VariantService::findOrCreateVariantForInward($productId, [
                            'size' => $sizeName ?: null,
                            'color' => $colorName ?: null,
                            'mrp' => (float)($item['mrp'] ?? 0),
                            'selling_price' => (float)($item['selling_price'] ?? 0),
                            'is_active' => 1
                        ], true);
                        if (!empty($resolved['id'])) {
                            $variantId = (int)$resolved['id'];
                            $item['variant_code'] = $resolved['variant_code'] ?? null;
                            $item['variant_sku'] = $resolved['sku'] ?? null;
                        }
                    } catch (\Throwable $e) {}
                }

                $mrp = (float)($item['mrp'] ?: (($si['variant_mrp'] ?? 0) > 0 ? $si['variant_mrp'] : ($si['base_price'] ?? 0)));
                $sellingPrice = (float)($item['selling_price'] ?: (($si['variant_selling_price'] ?? 0) > 0 ? $si['variant_selling_price'] : ($si['discount_price'] ?? 0)));

                $item['variant_id'] = $variantId ?: null;
                if (empty($item['variant_code']) && !empty($si['variant_code'])) {
                    $item['variant_code'] = $si['variant_code'];
                }
                if (empty($item['variant_sku']) && !empty($si['sku'])) {
                    $item['variant_sku'] = $si['sku'];
                }
                $item['size_name'] = $sizeName ?: null;
                $item['color_name'] = $colorName ?: null;
                $item['mrp'] = $mrp;
                $item['selling_price'] = $sellingPrice;

                if ($variantId > 0) $variantIds[$variantId] = true;

                Database::execute("
                    UPDATE qr_generation_items
                    SET variant_id = ?, size_name = ?, color_name = ?, mrp = ?, selling_price = ?
                    WHERE id = ?
                ", [
                    $variantId ?: null,
                    $sizeName ?: null,
                    $colorName ?: null,
                    $mrp,
                    $sellingPrice,
                    (int)$item['id']
                ]);
            }
            unset($item);

            if (!empty($variantIds)) {
                $uniqueKeys = array_keys($variantIds);
                $gen['unique_variant_ids'] = $uniqueKeys;
                if (empty($gen['variant_id']) || count($uniqueKeys) === 1) {
                    $gen['variant_id'] = (int)$uniqueKeys[0];
                    Database::execute("UPDATE qr_generations SET variant_id = ? WHERE id = ?", [$gen['variant_id'], $id]);
                }
            }
            $gen['stitching_variant_items'] = $stitchingItems;
        }

        // Global hydration across all items
        $allVariantIds = [];
        foreach ($items as &$it) {
            if (empty($it['variant_id']) && !empty($gen['variant_id'])) {
                $it['variant_id'] = (int)$gen['variant_id'];
                Database::execute("UPDATE qr_generation_items SET variant_id = ? WHERE id = ?", [$it['variant_id'], (int)$it['id']]);
            }
            if (!empty($it['variant_id'])) {
                $allVariantIds[(int)$it['variant_id']] = true;
            }
        }
        unset($it);

        if (!empty($allVariantIds)) {
            $vList = implode(',', array_map('intval', array_keys($allVariantIds)));
            $vRows = Database::fetchAll("SELECT id, variant_code, sku, mrp, selling_price FROM product_variants WHERE id IN ($vList)");
            $vMeta = [];
            foreach ($vRows as $vr) {
                $vMeta[(int)$vr['id']] = $vr;
            }
            foreach ($items as &$it) {
                $vid = (int)($it['variant_id'] ?? 0);
                if ($vid > 0 && isset($vMeta[$vid])) {
                    if (empty($it['variant_code'])) $it['variant_code'] = $vMeta[$vid]['variant_code'];
                    if (empty($it['variant_sku'])) $it['variant_sku'] = $vMeta[$vid]['sku'];
                    if ((float)($it['mrp'] ?? 0) <= 0) $it['mrp'] = (float)$vMeta[$vid]['mrp'];
                    if ((float)($it['selling_price'] ?? 0) <= 0) $it['selling_price'] = (float)$vMeta[$vid]['selling_price'];
                }
            }
            unset($it);
            $gen['unique_variant_ids'] = array_keys($allVariantIds);
            if (empty($gen['variant_id'])) {
                $gen['variant_id'] = (int)array_key_first($allVariantIds);
            }
        }

        return array_merge($gen, ['items' => $items]);
    }

    /**
     * List generation batches with filtering.
     */
    public static function listBatches(array $filters = []): array {
        self::ensureSourceSchema();

        $sql = "SELECT qrg.*,
                       u.full_name as created_by_name,
                       COUNT(qri.id) as generated_count
                FROM qr_generations qrg
                LEFT JOIN users u ON u.id = qrg.created_by
                LEFT JOIN qr_generation_items qri ON qri.generation_id = qrg.id
                WHERE UPPER(COALESCE(qrg.qr_source, '')) <> 'OWN_PRODUCT'";
        $params = [];

        $cid = !empty($filters['concern_id']) ? (int)$filters['concern_id'] : (function_exists('currentConcernId') ? currentConcernId() : null);
        if ($cid) {
            if ($cid === 1) {
                $sql .= " AND (qrg.concern_id = ? OR qrg.concern_id IS NULL)";
            } else {
                $sql .= " AND qrg.concern_id = ?";
            }
            $params[] = $cid;
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $sql .= " AND (qrg.batch_code LIKE ? OR qrg.brand_name LIKE ? OR qrg.qr_prefix LIKE ? OR qrg.category_name LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        if (!empty($filters['brand_name'])) {
            $sql .= " AND qrg.brand_name = ?";
            $params[] = trim($filters['brand_name']);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND qrg.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND qrg.generation_type = ?";
            $params[] = trim($filters['type']);
        }

        if (!empty($filters['qr_source'])) {
            $sql .= " AND qrg.qr_source = ?";
            $params[] = trim($filters['qr_source']);
        }

        $sql .= " GROUP BY qrg.id ORDER BY qrg.id DESC";

        return Database::fetchAll($sql, $params);
    }
}
