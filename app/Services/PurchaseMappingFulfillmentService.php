<?php
/**
 * PurchaseMappingFulfillmentService
 *
 * Completes the post-purchase flow for every production purchase item type:
 *   1. Save category / subcategory / sub-subcategory / attributes mapping
 *   2. Sync the mapped taxonomy back to the product + variant catalog
 *   3. Auto-create (or extend) the Goods Receipt Note (GRN)
 *
 * Mapping and GRN creation run inside one database transaction. QR generation is
 * a separate manual step after mapping. Re-saving a mapping is idempotent.
 */

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Services/VariantService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Models/PurchaseInward.php';

class PurchaseMappingFulfillmentService {

    private static bool $schemaEnsured = false;

    /**
     * Add fulfillment tracking columns without requiring a manual migration first.
     * A matching additive SQL migration is stored in database/migrations/.
     */
    public static function ensureSchema(): void {
        if (self::$schemaEnsured) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_pmf_schema_ensured_20261121_variant_qr'])) {
            self::$schemaEnsured = true;
            return;
        }

        Database::ensureColumn('production_purchase_items', 'qr_generation_id', 'INT UNSIGNED DEFAULT NULL AFTER mapped_by');
        Database::ensureColumn('production_purchase_items', 'qr_batch_code', 'VARCHAR(50) DEFAULT NULL AFTER qr_generation_id');
        Database::ensureColumn('production_purchase_items', 'grn_id', 'INT UNSIGNED DEFAULT NULL AFTER qr_batch_code');
        Database::ensureColumn('production_purchase_items', 'grn_number', 'VARCHAR(50) DEFAULT NULL AFTER grn_id');

        Database::ensureColumn('production_purchases', 'grn_id', 'INT UNSIGNED DEFAULT NULL AFTER total_amount');
        Database::ensureColumn('production_purchases', 'grn_number', 'VARCHAR(50) DEFAULT NULL AFTER grn_id');

        Database::ensureColumn('purchase_orders', 'production_purchase_id', 'INT UNSIGNED DEFAULT NULL AFTER id');
        Database::ensureColumn('purchase_inward_items', 'production_purchase_item_id', 'INT UNSIGNED DEFAULT NULL AFTER purchase_inward_id');

        Database::ensureColumn('products', 'attributes', 'TEXT DEFAULT NULL AFTER description');
        Database::ensureColumn('product_variants', 'attributes', 'TEXT DEFAULT NULL AFTER image');
        Database::execute("CREATE TABLE IF NOT EXISTS third_party_categories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            code VARCHAR(50) DEFAULT NULL,
            slug VARCHAR(200) NOT NULL,
            is_complementary TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_third_party_category_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Database::execute("CREATE TABLE IF NOT EXISTS third_party_subcategories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            name VARCHAR(200) NOT NULL,
            code VARCHAR(50) DEFAULT NULL,
            slug VARCHAR(200) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_third_party_subcategory_slug (category_id, slug),
            CONSTRAINT fk_third_party_subcategories_category
                FOREIGN KEY (category_id) REFERENCES third_party_categories (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Database::execute("CREATE TABLE IF NOT EXISTS third_party_sub_subcategories (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            subcategory_id INT UNSIGNED NOT NULL,
            name VARCHAR(200) NOT NULL,
            code VARCHAR(50) DEFAULT NULL,
            slug VARCHAR(200) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_third_party_sub_subcategory_slug (subcategory_id, slug),
            CONSTRAINT fk_third_party_sub_subcategories_subcategory
                FOREIGN KEY (subcategory_id) REFERENCES third_party_subcategories (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        Database::execute("CREATE TABLE IF NOT EXISTS third_party_products (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            concern_id INT NULL,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(100) NOT NULL,
            vendor_sku VARCHAR(100) DEFAULT NULL,
            category_id INT UNSIGNED DEFAULT NULL,
            subcategory_id INT UNSIGNED DEFAULT NULL,
            sub_subcategory_id INT UNSIGNED DEFAULT NULL,
            third_party_category_id INT UNSIGNED DEFAULT NULL,
            third_party_subcategory_id INT UNSIGNED DEFAULT NULL,
            third_party_sub_subcategory_id INT UNSIGNED DEFAULT NULL,
            category_code VARCHAR(50) DEFAULT NULL,
            subcategory_code VARCHAR(50) DEFAULT NULL,
            sub_subcategory_code VARCHAR(50) DEFAULT NULL,
            seller_id INT UNSIGNED DEFAULT NULL,
            color VARCHAR(100) DEFAULT NULL,
            size VARCHAR(100) DEFAULT NULL,
            unit VARCHAR(30) DEFAULT 'Pieces',
            hsn_code VARCHAR(20) DEFAULT NULL,
            mrp DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            purchase_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            attributes TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            is_complementary TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tp_code (code),
            INDEX idx_tp_name (name),
            INDEX idx_tp_category (third_party_category_id),
            INDEX idx_tp_concern (concern_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        Database::execute("CREATE TABLE IF NOT EXISTS third_party_product_variants (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            third_party_product_id INT UNSIGNED NOT NULL,
            purchase_item_id INT UNSIGNED DEFAULT NULL,
            sku VARCHAR(100) NOT NULL,
            size VARCHAR(50) NOT NULL,
            color VARCHAR(50) DEFAULT NULL,
            allocated_qty DECIMAL(12,3) NOT NULL DEFAULT 0.000,
            purchase_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            mrp DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            barcode VARCHAR(100) DEFAULT NULL,
            third_party_category_id INT UNSIGNED DEFAULT NULL,
            third_party_subcategory_id INT UNSIGNED DEFAULT NULL,
            third_party_sub_subcategory_id INT UNSIGNED DEFAULT NULL,
            hsn_code VARCHAR(20) DEFAULT NULL,
            is_complementary TINYINT(1) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tppv_product (third_party_product_id),
            INDEX idx_tppv_item (purchase_item_id),
            INDEX idx_tppv_sku (sku)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        Database::ensureColumn('third_party_products', 'third_party_category_id', 'INT UNSIGNED DEFAULT NULL AFTER sub_subcategory_id');
        Database::ensureColumn('third_party_products', 'third_party_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER third_party_category_id');
        Database::ensureColumn('third_party_products', 'third_party_sub_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER third_party_subcategory_id');
        Database::ensureColumn('third_party_categories', 'is_complementary', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER slug');
        Database::ensureColumn('third_party_product_variants', 'is_complementary', 'TINYINT(1) DEFAULT NULL AFTER barcode');
        Database::ensureColumn('third_party_product_variants', 'third_party_category_id', 'INT UNSIGNED DEFAULT NULL AFTER barcode');
        Database::ensureColumn('third_party_product_variants', 'third_party_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER third_party_category_id');
        Database::ensureColumn('third_party_product_variants', 'third_party_sub_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER third_party_subcategory_id');
        Database::ensureColumn('third_party_product_variants', 'hsn_code', 'VARCHAR(20) DEFAULT NULL AFTER third_party_sub_subcategory_id');
        Database::ensureColumn('third_party_product_variants', 'product_variant_id', 'INT UNSIGNED DEFAULT NULL AFTER third_party_product_id');
        Database::ensureColumn('third_party_product_variants', 'gst_rate', 'DECIMAL(8,3) NOT NULL DEFAULT 0.000 AFTER purchase_rate');
        Database::ensureColumn('third_party_product_variants', 'qr_generation_id', 'INT UNSIGNED DEFAULT NULL AFTER hsn_code');
        Database::ensureColumn('third_party_product_variants', 'qr_batch_code', 'VARCHAR(100) DEFAULT NULL AFTER qr_generation_id');
        Database::ensureColumn('production_purchase_items', 'third_party_product_id', 'INT UNSIGNED DEFAULT NULL AFTER variant_id');
        Database::ensureColumn('production_purchase_items', 'mapped_third_party_category_id', 'INT UNSIGNED DEFAULT NULL AFTER mapped_sub_subcategory_id');
        Database::ensureColumn('production_purchase_items', 'mapped_third_party_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER mapped_third_party_category_id');
        Database::ensureColumn('production_purchase_items', 'mapped_third_party_sub_subcategory_id', 'INT UNSIGNED DEFAULT NULL AFTER mapped_third_party_subcategory_id');
        Database::ensureColumn('qr_generations', 'source_mapping_item_id', 'INT UNSIGNED DEFAULT NULL AFTER source_purchase_item_id');
        Database::ensureColumn('qr_generations', 'source_variant_id', 'INT UNSIGNED DEFAULT NULL AFTER source_mapping_item_id');
        Database::execute("CREATE TABLE IF NOT EXISTS third_party_mapping_bills (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            concern_id INT UNSIGNED NOT NULL,
            seller_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            status ENUM('DRAFT','READY','MOVED') NOT NULL DEFAULT 'DRAFT',
            purchase_id INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_tpmb_supplier_invoice (concern_id, seller_id, invoice_number),
            KEY idx_tpmb_match (concern_id, seller_id, status),
            KEY idx_tpmb_purchase (purchase_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Database::execute("CREATE TABLE IF NOT EXISTS third_party_mapping_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            mapping_bill_id INT UNSIGNED NOT NULL,
            purchase_item_id INT UNSIGNED DEFAULT NULL,
            product_id INT UNSIGNED NOT NULL,
            variant_id INT UNSIGNED NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            product_code VARCHAR(100) DEFAULT NULL,
            vendor_sku VARCHAR(100) DEFAULT NULL,
            quantity DECIMAL(12,3) NOT NULL,
            unit VARCHAR(30) NOT NULL DEFAULT 'PCS',
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            mrp DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            gst_rate DECIMAL(8,3) NOT NULL DEFAULT 0.000,
            hsn_code VARCHAR(20) NOT NULL,
            color VARCHAR(100) DEFAULT NULL,
            size VARCHAR(50) DEFAULT NULL,
            category_id INT UNSIGNED NOT NULL,
            subcategory_id INT UNSIGNED DEFAULT NULL,
            sub_subcategory_id INT UNSIGNED DEFAULT NULL,
            qr_generation_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_tpmi_bill (mapping_bill_id),
            KEY idx_tpmi_variant (variant_id),
            KEY idx_tpmi_qr (qr_generation_id),
            CONSTRAINT fk_tpmi_bill FOREIGN KEY (mapping_bill_id) REFERENCES third_party_mapping_bills (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        Database::ensureColumn('third_party_mapping_items', 'purchase_item_id', 'INT UNSIGNED DEFAULT NULL AFTER mapping_bill_id');

        // High-Performance Composite Indexes for ERP mapping, purchases, QR and stock ledger
        Database::ensureIndex('production_purchases', 'idx_pp_seller_inv', '`seller_id`, `invoice_number`');
        Database::ensureIndex('production_purchases', 'idx_pp_concern_type', '`concern_id`, `purchase_type`');
        Database::ensureIndex('production_purchase_items', 'idx_ppi_purchase_type', '`purchase_id`, `item_type`');
        Database::ensureIndex('production_purchase_items', 'idx_ppi_variant', '`variant_id`');
        Database::ensureIndex('production_purchase_items', 'idx_ppi_qr', '`qr_generation_id`');
        Database::ensureIndex('third_party_mapping_bills', 'idx_tpmb_match_inv', '`concern_id`, `seller_id`, `invoice_number`');
        Database::ensureIndex('third_party_mapping_bills', 'idx_tpmb_purchase_link', '`purchase_id`');
        Database::ensureIndex('third_party_mapping_items', 'idx_tpmi_purchase_item', '`purchase_item_id`');
        Database::ensureIndex('third_party_mapping_items', 'idx_tpmi_variant_link', '`variant_id`');
        Database::ensureIndex('stock_transactions', 'idx_st_variant_loc_state', '`variant_id`, `location_type`, `location_id`');
        Database::ensureIndex('stock_balances', 'idx_sb_variant_loc_state', '`variant_id`, `location_type`, `location_id`, `stock_state`');
        Database::ensureIndex('qr_generations', 'idx_qrg_source_mapping', '`source_mapping_item_id`');
        Database::ensureIndex('qr_generations', 'idx_qrg_source_purchase', '`source_purchase_item_id`');
        Database::ensureIndex('qr_generations', 'idx_qrg_stock_status', '`stock_added`');
        Database::ensureIndex('product_variants', 'idx_pv_product_active', '`product_id`, `is_active`');

        // Repair legacy rows that were flagged as mapped without any taxonomy.
        Database::execute(
            "UPDATE production_purchase_items
             SET is_mapped = 0
             WHERE is_mapped = 1
               AND mapped_category_id IS NULL
               AND mapped_third_party_category_id IS NULL"
        );

        // Seed default third-party categories if empty so ERP taxonomy is immediately operational
        $tpCatCount = (int)(Database::fetchOne("SELECT COUNT(*) AS cnt FROM third_party_categories")['cnt'] ?? 0);
        if ($tpCatCount === 0) {
            Database::execute("INSERT INTO third_party_categories (name, code, slug, sort_order, is_active) VALUES
                ('Non Feeding', 'NF', 'non-feeding', 1, 1),
                ('Feeding', 'FD', 'feeding', 2, 1),
                ('Kids Wear', 'KW', 'kids-wear', 3, 1),
                ('Women Ethnic', 'WE', 'women-ethnic', 4, 1),
                ('Men Casual', 'MC', 'men-casual', 5, 1)
            ");

            $nfCat = Database::fetchOne("SELECT id FROM third_party_categories WHERE code = 'NF' LIMIT 1");
            if ($nfCat) {
                Database::execute("INSERT INTO third_party_subcategories (category_id, name, code, slug, sort_order, is_active) VALUES
                    (?, 'Full Length', 'FL', 'full-length', 1, 1),
                    (?, 'Midi Dress', 'MD', 'midi-dress', 2, 1),
                    (?, 'Short Top', 'ST', 'short-top', 3, 1)
                ", [(int)$nfCat['id'], (int)$nfCat['id'], (int)$nfCat['id']]);

                $flSubcat = Database::fetchOne("SELECT id FROM third_party_subcategories WHERE code = 'FL' AND category_id = ? LIMIT 1", [(int)$nfCat['id']]);
                if ($flSubcat) {
                    Database::execute("INSERT INTO third_party_sub_subcategories (subcategory_id, name, code, slug, sort_order, is_active) VALUES
                        (?, 'Normal Sleeve', 'NS', 'normal-sleeve', 1, 1),
                        (?, 'Sleeveless', 'SL', 'sleeveless', 2, 1),
                        (?, '3/4th Sleeve', 'TQS', '3-4th-sleeve', 3, 1)
                    ", [(int)$flSubcat['id'], (int)$flSubcat['id'], (int)$flSubcat['id']]);
                }
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_pmf_schema_ensured_20261121_variant_qr'] = 1;
        }
        self::$schemaEnsured = true;
    }

    /**
     * Fetch one purchase item with the context needed by the fulfillment steps.
     */
    public static function getPurchaseItem(int $itemId): ?array {
        return Database::fetchOne(
            "SELECT ppi.*,
                    pp.purchase_number, pp.purchase_date, pp.invoice_number, pp.total_amount,
                    pp.seller_id, pp.concern_id, pp.grn_id AS purchase_grn_id, pp.grn_number AS purchase_grn_number,
                    s.name AS seller_name, s.company AS seller_company,
                    COALESCE(tpc.name, c.name) AS category_name, COALESCE(tpc.code, c.code) AS category_code,
                    COALESCE(tps.name, sc.name) AS subcategory_name, COALESCE(tps.code, sc.code) AS subcategory_code,
                    COALESCE(tpss.name, ssc.name) AS sub_subcategory_name, COALESCE(tpss.code, ssc.code) AS sub_subcategory_code,
                    qg.batch_code AS existing_batch_code
             FROM production_purchase_items ppi
             INNER JOIN production_purchases pp ON pp.id = ppi.purchase_id
             LEFT JOIN sellers s ON s.id = pp.seller_id
             LEFT JOIN categories c ON c.id = ppi.mapped_category_id
             LEFT JOIN subcategories sc ON sc.id = ppi.mapped_subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = ppi.mapped_sub_subcategory_id
             LEFT JOIN third_party_categories tpc ON tpc.id = ppi.mapped_third_party_category_id
             LEFT JOIN third_party_subcategories tps ON tps.id = ppi.mapped_third_party_subcategory_id
             LEFT JOIN third_party_sub_subcategories tpss ON tpss.id = ppi.mapped_third_party_sub_subcategory_id
             LEFT JOIN qr_generations qg ON qg.id = ppi.qr_generation_id
             WHERE ppi.id = ?",
            [$itemId]
        );
    }

    /**
     * Main entry point called by ProductionController::saveThirdPartyMapping().
     *
     * @param array $mapping keys: category_id, subcategory_id, sub_subcategory_id,
     *                       attributes (array|string), is_complementary,
     *                       variant_is_complementary, mrp, selling_price
     */
    public static function fulfill(int $itemId, array $mapping, int $userId): array {
        self::ensureSchema();

        $item = self::getPurchaseItem($itemId);
        if (!$item) {
            throw new InvalidArgumentException('Purchase item not found.');
        }
        if (strtoupper((string)($item['item_type'] ?? '')) !== 'THIRD_PARTY') {
            throw new InvalidArgumentException('Only third-party purchase items can be mapped to the external product catalog.');
        }

        $categoryId = (int)($mapping['category_id'] ?? 0);
        $subcategoryId = !empty($mapping['subcategory_id']) ? (int)$mapping['subcategory_id'] : null;
        $subSubcategoryId = !empty($mapping['sub_subcategory_id']) ? (int)$mapping['sub_subcategory_id'] : null;
        $isComplementary = !empty($mapping['is_complementary']) ? 1 : 0;
        $variantIsComplementary = array_key_exists('variant_is_complementary', $mapping)
            ? (!empty($mapping['variant_is_complementary']) ? 1 : 0)
            : $isComplementary;
        $mrpValue = $mapping['mrp'] ?? null;
        $sellingPriceValue = $mapping['selling_price'] ?? null;

        if (!is_numeric($mrpValue) || !is_numeric($sellingPriceValue)) {
            throw new InvalidArgumentException('Enter valid MRP and selling prices.');
        }
        $mrp = (float)$mrpValue;
        $sellingPrice = (float)$sellingPriceValue;
        if (!is_finite($mrp) || !is_finite($sellingPrice) || $mrp <= 0 || $sellingPrice <= 0) {
            throw new InvalidArgumentException('MRP and selling price must be valid positive amounts.');
        }
        if ($sellingPrice > $mrp) {
            throw new InvalidArgumentException('Selling price cannot be greater than MRP.');
        }

        $category = Database::fetchOne(
            "SELECT id, name, code FROM third_party_categories WHERE id = ? AND is_active = 1",
            [$categoryId]
        );
        if (!$category) {
            throw new InvalidArgumentException('Selected category does not exist.');
        }
        if (!self::isExternalCatalogHierarchy($categoryId, $subcategoryId, $subSubcategoryId)) {
            throw new InvalidArgumentException('Select a valid category hierarchy from the Third-Party Catalog.');
        }
        if ($subcategoryId) {
            $validSub = Database::fetchOne(
                "SELECT id FROM third_party_subcategories WHERE id = ? AND category_id = ? AND is_active = 1",
                [$subcategoryId, $categoryId]
            );
            if (!$validSub) {
                throw new InvalidArgumentException('Selected subcategory does not belong to the chosen category.');
            }

        }
        if ($subSubcategoryId) {
            $validSubSub = Database::fetchOne(
                "SELECT id FROM third_party_sub_subcategories WHERE id = ? AND subcategory_id = ? AND is_active = 1",
                [$subSubcategoryId, $subcategoryId ?: 0]
            );
            if (!$validSubSub) {
                throw new InvalidArgumentException('Selected sub-subcategory does not belong to the chosen subcategory.');
            }
        }

        $rawAttributes = $mapping['attributes'] ?? null;
        if (empty($rawAttributes) && !empty($item['mapped_attributes'])) {
            $rawAttributes = $item['mapped_attributes'];
        }
        $encodedAttributes = null;
        if (!empty($rawAttributes)) {
            $encodedAttributes = is_string($rawAttributes)
                ? $rawAttributes
                : json_encode($rawAttributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE production_purchase_items
                 SET mrp = ?,
                     selling_price = ?,
                     mapped_category_id = NULL,
                     mapped_subcategory_id = NULL,
                     mapped_sub_subcategory_id = NULL,
                     mapped_third_party_category_id = ?,
                     mapped_third_party_subcategory_id = ?,
                     mapped_third_party_sub_subcategory_id = ?,
                     mapped_attributes = ?,
                     is_complementary = ?,
                     is_mapped = 1,
                     mapped_at = NOW(),
                     mapped_by = ?
                 WHERE id = ?",
                [$mrp, $sellingPrice, $categoryId, $subcategoryId, $subSubcategoryId, $encodedAttributes,
                 $isComplementary, $userId ?: null, $itemId]
            );

            $catalog = self::syncCatalog($itemId, $encodedAttributes, $isComplementary, $variantIsComplementary);
            $grn = self::createOrAppendGrn($itemId, $catalog, $userId);

            AuditService::log('purchases', 'UPDATE', 'production_purchase_items', $itemId, null, [
                'action'                => 'purchase_item_mapping_fulfillment',
                'item_type'             => $item['item_type'],
                'category_id'           => $categoryId,
                'subcategory_id'        => $subcategoryId,
                'sub_subcategory_id'    => $subSubcategoryId,
                'mrp'                   => $mrp,
                'selling_price'         => $sellingPrice,
                'is_complementary'      => $isComplementary,
                'grn_id'                => $grn['grn_id'],
                'grn_number'            => $grn['grn_number'],
            ]);

            Database::commit();

            return [
                'item'           => self::getPurchaseItem($itemId),
                'catalog'        => $catalog,
                'grn'            => $grn,
                'grn_line_reused'=> !empty($grn['line_reused']),
            ];
        } catch (Throwable $e) {
            // Inner services open their own nested transactions. Roll back twice
            // so a non-Exception Throwable inside one of them cannot leave the
            // outer transaction open.
            Database::rollback();
            Database::rollback();
            throw $e;
        }
    }

    public static function isExternalCatalogHierarchy(int $categoryId, ?int $subcategoryId = null, ?int $subSubcategoryId = null): bool {
        if ($categoryId <= 0 || !Database::fetchOne(
            "SELECT id FROM third_party_categories WHERE id = ? AND is_active = 1",
            [$categoryId]
        )) {
            return false;
        }

        if ($subcategoryId) {
            if (!Database::fetchOne(
                "SELECT id FROM third_party_subcategories WHERE id = ? AND category_id = ? AND is_active = 1",
                [$subcategoryId, $categoryId]
            )) {
                return false;
            }
        }
        if ($subSubcategoryId) {
            if (!$subcategoryId || !Database::fetchOne(
                "SELECT id FROM third_party_sub_subcategories WHERE id = ? AND subcategory_id = ? AND is_active = 1",
                [$subSubcategoryId, $subcategoryId]
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write the mapped hierarchy/attributes to products + product_variants and
     * make sure the purchase line has a variant that the QR/GRN flow can use.
     */
    private static function syncCatalog(int $itemId, ?string $attributes, int $isComplementary, int $variantIsComplementary): array {
        $item = self::getPurchaseItem($itemId);
        if (!$item) {
            throw new RuntimeException('Purchase item disappeared during mapping.');
        }

        $categoryId = (int)$item['mapped_third_party_category_id'];
        $subcategoryId = !empty($item['mapped_third_party_subcategory_id']) ? (int)$item['mapped_third_party_subcategory_id'] : null;
        $subSubcategoryId = !empty($item['mapped_third_party_sub_subcategory_id']) ? (int)$item['mapped_third_party_sub_subcategory_id'] : null;

        $hierarchy = Database::fetchOne(
            "SELECT c.name AS category_name, c.code AS category_code,
                    sc.name AS subcategory_name, sc.code AS subcategory_code,
                    ssc.name AS sub_subcategory_name, ssc.code AS sub_subcategory_code
             FROM third_party_categories c
             LEFT JOIN third_party_subcategories sc ON sc.id = ?
             LEFT JOIN third_party_sub_subcategories ssc ON ssc.id = ?
             WHERE c.id = ?",
            [$subcategoryId, $subSubcategoryId, $categoryId]
        ) ?: [];

        $productId = (int)($item['product_id'] ?? 0);
        $itemName = mb_strtoupper(trim((string)$item['item_name'])) ?: ('PURCHASE ITEM ' . $itemId);
        $code = '';

        if ($productId > 0) {
            $existingProduct = Database::fetchOne(
                "SELECT id, code, source_type FROM products WHERE id = ?",
                [$productId]
            );
            if (!$existingProduct || strtoupper((string)$existingProduct['source_type']) !== 'EXTERNAL_PURCHASE') {
                $productId = 0;
                $item['product_id'] = 0;
                $item['variant_id'] = 0;
            } else {
                $code = (string)$existingProduct['code'];
            }
        }

        if ($productId <= 0) {
            $baseCode = 'PP-' . substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $itemName)), 0, 36);
            $baseCode = rtrim($baseCode, '-') ?: ('PP-ITEM-' . $itemId);
            $code = $baseCode;
            $suffix = 1;
            while (Database::fetchOne("SELECT id FROM products WHERE code = ?", [$code])) {
                $code = $baseCode . '-' . $suffix++;
            }
            Database::execute(
                "INSERT INTO products
                    (name, code, source_type, hsn_code, attributes, is_complementary, is_active, seller_id)
                 VALUES (?, ?, 'EXTERNAL_PURCHASE', ?, ?, ?, 1, ?)",
                [
                    $itemName, $code,
                    $item['hsn_code'] ?: null,
                    $attributes,
                    $isComplementary,
                    (int)($item['seller_id'] ?? 0) ?: null,
                ]
            );
            $productId = (int)Database::lastInsertId();
        } else {
            Database::execute(
                "UPDATE products
                 SET attributes = ?, is_complementary = ?,
                     hsn_code = COALESCE(hsn_code, ?)
                 WHERE id = ?",
                [
                    $attributes, $isComplementary, $item['hsn_code'] ?: null, $productId,
                ]
            );
        }

        $tpProductId = (int)($item['third_party_product_id'] ?? 0);
        $tpCode = trim((string)($item['vendor_sku'] ?? '')) ?: ('TP-' . $code);
        if ($tpProductId <= 0) {
            $existingTp = Database::fetchOne(
                "SELECT id FROM third_party_products WHERE code = ? LIMIT 1",
                [$tpCode]
            );
            if (!$existingTp) {
                $existingTp = Database::fetchOne(
                    "SELECT id FROM third_party_products
                     WHERE UPPER(name) = UPPER(?) AND third_party_category_id = ?
                     ORDER BY id ASC LIMIT 1",
                    [$itemName, $categoryId]
                );
            }
            if ($existingTp) {
                $tpProductId = (int)$existingTp['id'];
            } else {
                Database::execute(
                    "INSERT INTO third_party_products
                        (concern_id, name, code, vendor_sku, third_party_category_id,
                         third_party_subcategory_id, third_party_sub_subcategory_id,
                         category_code, subcategory_code, sub_subcategory_code,
                         seller_id, color, size, unit, hsn_code, mrp, selling_price,
                         purchase_rate, attributes, is_complementary, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [
                        $item['concern_id'] ?? null,
                        $itemName,
                        $tpCode,
                        $item['vendor_sku'] ?: null,
                        $categoryId,
                        $subcategoryId,
                        $subSubcategoryId,
                        $hierarchy['category_code'] ?? null,
                        $hierarchy['subcategory_code'] ?? null,
                        $hierarchy['sub_subcategory_code'] ?? null,
                        (int)($item['seller_id'] ?? 0) ?: null,
                        $item['color'] ?: null,
                        $item['size'] ?: null,
                        $item['unit'] ?: 'PCS',
                        $item['hsn_code'] ?: null,
                        (float)($item['mrp'] ?? 0),
                        (float)($item['selling_price'] ?? 0),
                        (float)($item['unit_price'] ?? 0),
                        $attributes,
                        $isComplementary,
                    ]
                );
                $tpProductId = (int)Database::lastInsertId();
            }
        }
        if ($tpProductId > 0) {
            Database::execute(
                "UPDATE third_party_products
                 SET name = ?, category_id = NULL, subcategory_id = NULL, sub_subcategory_id = NULL,
                     third_party_category_id = ?, third_party_subcategory_id = ?,
                     third_party_sub_subcategory_id = ?,
                     category_code = COALESCE(?, category_code),
                     subcategory_code = COALESCE(?, subcategory_code),
                     sub_subcategory_code = COALESCE(?, sub_subcategory_code),
                     seller_id = ?, color = ?, size = ?, unit = ?, hsn_code = ?,
                     attributes = ?, is_complementary = ?, mrp = ?, selling_price = ?,
                     purchase_rate = ?, is_active = 1
                 WHERE id = ?",
                [
                    $itemName, $categoryId, $subcategoryId, $subSubcategoryId,
                    $hierarchy['category_code'] ?? null,
                    $hierarchy['subcategory_code'] ?? null,
                    $hierarchy['sub_subcategory_code'] ?? null,
                    (int)($item['seller_id'] ?? 0) ?: null,
                    $item['color'] ?: null,
                    $item['size'] ?: null,
                    $item['unit'] ?: 'PCS',
                    $item['hsn_code'] ?: null,
                    $attributes, $isComplementary, (float)($item['mrp'] ?? 0),
                    (float)($item['selling_price'] ?? 0), (float)($item['unit_price'] ?? 0), $tpProductId,
                ]
            );
        }

        Database::execute(
            "UPDATE production_purchase_items SET third_party_product_id = ? WHERE id = ?",
            [$tpProductId, $itemId]
        );

        $variantId = (int)($item['variant_id'] ?? 0);
        if ($variantId <= 0) {
            $variant = VariantService::findOrCreateVariantForInward($productId, [
                'color'         => $item['color'] ?? null,
                'size'          => $item['size'] ?? null,
                'design_name'   => $item['design_name'] ?? null,
                'hsn_code'      => $item['hsn_code'] ?: null,
                'mrp'           => (float)($item['mrp'] ?? 0),
                'selling_price' => (float)($item['selling_price'] ?? 0),
                'unit_price'    => (float)($item['unit_price'] ?? 0),
            ]);
            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId <= 0) {
                throw new RuntimeException('Unable to create a catalog variant for this purchase item.');
            }
        }

        Database::execute(
            "UPDATE product_variants
             SET mrp = ?, selling_price = ?,
                 is_complementary = ?, attributes = COALESCE(?, attributes),
                 hsn_code = COALESCE(hsn_code, ?)
             WHERE id = ?",
            [(float)$item['mrp'], (float)$item['selling_price'],
             $variantIsComplementary, $attributes, $item['hsn_code'] ?: null, $variantId]
        );

        if ($productId !== (int)($item['product_id'] ?? 0) || $variantId !== (int)($item['variant_id'] ?? 0)) {
            Database::execute(
                "UPDATE production_purchase_items SET product_id = ?, variant_id = ? WHERE id = ?",
                [$productId, $variantId, $itemId]
            );
        }

        $variant = Database::fetchOne(
            "SELECT pv.id, pv.sku, pv.variant_code, pv.mrp, pv.selling_price, pv.color_id, pv.design_id, pv.size_id,
                    col.name AS color_name, d.name AS design_name, sz.name AS size_name
             FROM product_variants pv
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE pv.id = ?",
            [$variantId]
        );

        return [
            'product_id'            => $productId,
            'variant_id'            => $variantId,
            'variant_sku'           => $variant['sku'] ?? null,
            'item_name'             => $itemName,
            'quantity'              => (float)$item['quantity'],
            'mrp'                   => (float)($variant['mrp'] ?? $item['mrp'] ?? 0),
            'selling_price'         => (float)($variant['selling_price'] ?? $item['selling_price'] ?? 0),
            'hsn_code'              => $item['hsn_code'] ?: null,
            'purchase_id'           => (int)$item['purchase_id'],
            'purchase_number'       => $item['purchase_number'],
            'purchase_date'         => $item['purchase_date'],
            'invoice_number'        => $item['invoice_number'] ?: null,
            'item_type'             => $item['item_type'],
            'category_name'         => $hierarchy['category_name'] ?? $item['category_name'] ?? null,
            'category_code'         => $hierarchy['category_code'] ?? $item['category_code'] ?? null,
            'subcategory_name'      => $hierarchy['subcategory_name'] ?? $item['subcategory_name'] ?? null,
            'sub_subcategory_name'  => $hierarchy['sub_subcategory_name'] ?? $item['sub_subcategory_name'] ?? null,
            'brand_name'            => trim((string)($item['seller_company'] ?: ($item['seller_name'] ?? ''))) ?: 'GRM',
            'color_name'            => $variant['color_name'] ?? ($item['color'] ?? null),
            'size_name'             => $variant['size_name'] ?? ($item['size'] ?? null),
            'design_name'           => $variant['design_name'] ?? ($item['design_name'] ?? null),
        ];
    }

    /**
     * Create (once per purchase) a GRN with a matching PO, then append one line
     * per mapped purchase item. Stock is posted separately from a generated QR batch.
     */
    private static function createOrAppendGrn(int $itemId, array $catalog, int $userId): array {
        $item = self::getPurchaseItem($itemId);
        $purchaseId = (int)$catalog['purchase_id'];

        $warehouse = Database::fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1")
            ?: Database::fetchOne("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
        if (!$warehouse) {
            $cid = (int)($catalog['concern_id'] ?? 1) ?: 1;
            Database::execute("INSERT INTO warehouses (name, code, is_active, concern_id) VALUES ('Main Warehouse', 'WH-MAIN', 1, ?)", [$cid]);
            $warehouseId = (int)Database::lastInsertId();
        } else {
            $warehouseId = (int)$warehouse['id'];
        }

        $grnId = (int)($item['purchase_grn_id'] ?? 0);
        $grnNumber = (string)($item['purchase_grn_number'] ?? '');

        if ($grnId <= 0) {
            $poId = self::ensurePurchaseOrder($purchaseId, $catalog, $warehouseId, $userId);

            $grnNumber = PurchaseInward::generateGrnNumber();
            Database::execute(
                "INSERT INTO purchase_inwards
                    (purchase_order_id, grn_number, warehouse_id, received_date,
                     received_by, verified_by, notes, invoice_number, invoice_date,
                     status, total_received_qty, total_accepted_qty, total_rejected_qty)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 'STOCKED', 0, 0, 0)",
                [
                    $poId,
                    $grnNumber,
                    $warehouseId,
                    $catalog['purchase_date'] ?: date('Y-m-d'),
                    $userId ?: null,
                    $userId ?: null,
                    "Auto-generated after taxonomy mapping for production purchase {$catalog['purchase_number']}.",
                    $catalog['invoice_number'] ?: null,
                ]
            );
            $grnId = (int)Database::lastInsertId();

            Database::execute(
                "UPDATE production_purchases SET grn_id = ?, grn_number = ? WHERE id = ?",
                [$grnId, $grnNumber, $purchaseId]
            );
        }

        $lineAlreadyExists = Database::fetchOne(
            "SELECT id FROM purchase_inward_items WHERE purchase_inward_id = ? AND production_purchase_item_id = ?",
            [$grnId, $itemId]
        );

        $lineReused = false;
        if (!$lineAlreadyExists) {
            $qty = max(1, (int)round((float)$catalog['quantity']));
            $batchCode = trim((string)($item['qr_batch_code'] ?? $item['existing_batch_code'] ?? ''));
            Database::execute(
                "INSERT INTO purchase_inward_items
                    (purchase_inward_id, production_purchase_item_id, variant_id,
                     ordered_qty, received_qty, damaged_qty, accepted_qty,
                     received_quantity, accepted_quantity, rejected_quantity,
                     mrp, batch_lot_no, notes)
                 VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 0, ?, ?, ?)",
                [
                    $grnId,
                    $itemId,
                    (int)$catalog['variant_id'],
                    $qty,
                    $qty,
                    $qty,
                    $qty,
                    $qty,
                    (float)$catalog['mrp'],
                    $batchCode !== '' ? $batchCode : null,
                    "Mapped {$catalog['item_type']} purchase item.",
                ]
            );

            Database::execute(
                "UPDATE purchase_inwards
                 SET total_received_qty = total_received_qty + ?,
                     total_accepted_qty = total_accepted_qty + ?
                 WHERE id = ?",
                [$qty, $qty, $grnId]
            );

            // The GRN records receipt/QC data only. Stock is NOT credited and the
            // line is NOT marked inwarded here: that happens exclusively when the
            // admin clicks "Add to Stock" on the generated QR batch.
            Database::execute(
                "UPDATE production_purchase_items
                 SET grn_id = ?, grn_number = ?
                 WHERE id = ?",
                [$grnId, $grnNumber, $itemId]
            );
        } else {
            $lineReused = true;
            Database::execute(
                "UPDATE purchase_inward_items SET mrp = ? WHERE id = ?",
                [(float)$catalog['mrp'], (int)$lineAlreadyExists['id']]
            );
            Database::execute(
                "UPDATE production_purchase_items SET grn_id = ?, grn_number = ? WHERE id = ?",
                [$grnId, $grnNumber, $itemId]
            );
        }

        return [
            'grn_id'       => $grnId,
            'grn_number'   => $grnNumber,
            'line_reused'  => $lineReused,
            'warehouse_id' => $warehouseId,
        ];
    }

    /**
     * Create one matching purchase order per production purchase so the GRN can
     * satisfy the existing purchase_order_id foreign key.
     */
    private static function ensurePurchaseOrder(int $purchaseId, array $catalog, int $warehouseId, int $userId): int {
        $po = Database::fetchOne(
            "SELECT id FROM purchase_orders WHERE production_purchase_id = ? OR po_number = ? ORDER BY id ASC LIMIT 1",
            [$purchaseId, 'PO-' . $catalog['purchase_number']]
        );
        if ($po) {
            return (int)$po['id'];
        }

        $poNumber = 'PO-' . $catalog['purchase_number'];
        $suffix = 1;
        while (Database::fetchOne("SELECT id FROM purchase_orders WHERE po_number = ?", [$poNumber])) {
            $poNumber = 'PO-' . $catalog['purchase_number'] . '-' . $suffix++;
        }

        $purchase = Database::fetchOne(
            "SELECT seller_id, total_amount FROM production_purchases WHERE id = ?",
            [$purchaseId]
        );

        Database::execute(
            "INSERT INTO purchase_orders
                (po_number, production_purchase_id, seller_id, warehouse_id, order_date,
                 total_amount, status, is_direct_purchase, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, 'RECEIVED', 1, ?, ?)",
            [
                $poNumber,
                $purchaseId,
                (int)($purchase['seller_id'] ?? 0) ?: null,
                $warehouseId,
                $catalog['purchase_date'] ?: date('Y-m-d'),
                (float)($purchase['total_amount'] ?? 0),
                "Auto-created from production purchase {$catalog['purchase_number']} for GRN generation.",
                $userId ?: null,
            ]
        );

        return (int)Database::lastInsertId();
    }
}
