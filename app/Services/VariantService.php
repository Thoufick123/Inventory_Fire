<?php
/**
 * VariantService — Central Product Variant ID & SKU Management Engine
 *
 * Implements:
 * 1. Collision-safe Variant ID generation (e.g. V001, V002...)
 * 2. Variant SKU generation (ParentSKU-[COLOR]-[DESIGN]-[SIZE])
 * 3. Duplicate combination validation
 * 4. QR code resolution & linking
 * 5. Production unit inventory integration
 */

require_once BASE_PATH . '/app/Models/ProductVariant.php';
require_once BASE_PATH . '/app/Models/Design.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class VariantService {

    /**
     * Generate the next collision-safe human-readable Variant ID (e.g. V0001, V0002).
     */
    public static function generateNextVariantCode(): string {
        $db = Database::getInstance();
        
        // Find highest sequence from existing variant_codes
        $rows = Database::fetchAll("SELECT variant_code FROM product_variants WHERE variant_code IS NOT NULL");
        $maxNum = 0;
        foreach ($rows as $r) {
            $code = $r['variant_code'];
            if (preg_match('/^V(\d+)$/i', $code, $m)) {
                $num = (int)$m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        // Also check max id to prevent collisions if variant_code was manually formatted
        $maxId = (int)Database::fetchOne("SELECT COALESCE(MAX(id), 0) as m FROM product_variants")['m'];
        $next = max($maxNum, $maxId) + 1;

        // Verify collision-free loop
        do {
            $candidate = 'V' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
            $exists = Database::fetchOne("SELECT id FROM product_variants WHERE variant_code = ?", [$candidate]);
            if (!$exists) {
                return $candidate;
            }
            $next++;
        } while (true);
    }

    /**
     * Generate a standardized Variant SKU based on Parent Catalog and Selected Attributes.
     * Example: Parent SKU = GRM-KID-001, Color = Red (RED), Design = Panda (PAN), Size = S (S)
     * Result: GRM-KID-001-RED-PAN-S
     */
    public static function generateVariantSku(
        int $productId,
        ?int $colorId = null,
        ?int $designId = null,
        ?int $sizeId = null
    ): string {
        $product = Database::fetchOne("SELECT code FROM products WHERE id = ?", [$productId]);
        if (!$product) {
            throw new InvalidArgumentException("Invalid Catalog / Product ID: $productId");
        }

        $parentSku = strtoupper(trim($product['code']));
        $parts = [$parentSku];

        if ($colorId) {
            $col = Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId]);
            if ($col) {
                $cleanCol = substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $col['name'])), 0, 4);
                if ($cleanCol !== '') {
                    $parts[] = $cleanCol;
                }
            }
        }

        if ($designId) {
            $des = Database::fetchOne("SELECT name, code FROM designs WHERE id = ?", [$designId]);
            if ($des) {
                $cleanDes = !empty($des['code']) 
                    ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $des['code']))
                    : substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $des['name'])), 0, 4);
                if ($cleanDes !== '') {
                    $parts[] = $cleanDes;
                }
            }
        }

        if ($sizeId) {
            $sz = Database::fetchOne("SELECT name, code FROM sizes WHERE id = ?", [$sizeId]);
            if ($sz) {
                $cleanSz = !empty($sz['code']) ? strtoupper(trim($sz['code'])) : strtoupper(trim($sz['name']));
                if ($cleanSz !== '') {
                    $parts[] = $cleanSz;
                }
            }
        }

        $baseSku = implode('-', $parts);
        $candidate = $baseSku;
        $counter = 1;

        // Ensure SKU uniqueness
        while (Database::fetchOne("SELECT id FROM product_variants WHERE sku = ?", [$candidate])) {
            $candidate = $baseSku . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Validate whether an attribute combination already exists under the parent catalog.
     */
    public static function checkDuplicateCombination(
        int $productId,
        ?int $colorId = null,
        ?int $designId = null,
        ?int $sizeId = null,
        ?int $excludeVariantId = null
    ): bool {
        $colorVal = $colorId ? (int)$colorId : 0;
        $designVal = $designId ? (int)$designId : 0;
        $sizeVal = $sizeId ? (int)$sizeId : 0;
        $hash = $productId . ':' . $colorVal . ':' . $designVal . ':' . $sizeVal;

        $sql = "SELECT id FROM product_variants WHERE attr_hash = ?";
        $params = [$hash];

        if ($excludeVariantId) {
            $sql .= " AND id != ?";
            $params[] = $excludeVariantId;
        }

        $existing = Database::fetchOne($sql, $params);
        return !empty($existing);
    }

    /**
     * Create a new Variant record.
     */
    public static function createVariant(array $data, bool $allowDeferredProduction = false): array {
        $productId = (int)($data['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new InvalidArgumentException("Please select a valid Parent Catalog.");
        }

        $product = Database::fetchOne(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?",
            [$productId]
        );
        if (!$product) {
            throw new InvalidArgumentException("Parent Catalog not found.");
        }
        if ((int)($product['variants_deferred'] ?? 0) === 1 && !$allowDeferredProduction) {
            throw new RuntimeException('Variants for this production style are created after stitching is completed and QR codes are generated.');
        }

        $colorId = !empty($data['color_id']) ? (int)$data['color_id'] : null;
        $designId = !empty($data['design_id']) ? (int)$data['design_id'] : null;
        $sizeId = !empty($data['size_id']) ? (int)$data['size_id'] : null;

        // Calculate attribute uniqueness hash
        $colorVal = $colorId ? (int)$colorId : 0;
        $designVal = $designId ? (int)$designId : 0;
        $sizeVal = $sizeId ? (int)$sizeId : 0;
        $attrHash = $productId . ':' . $colorVal . ':' . $designVal . ':' . $sizeVal;

        // Check duplicate combination
        if (self::checkDuplicateCombination($productId, $colorId, $designId, $sizeId)) {
            throw new RuntimeException("A variant with this exact combination of Color, Design, and Size already exists in this Catalog!");
        }

        // Generate variant_code
        $variantCode = !empty($data['variant_code']) ? strtoupper(trim($data['variant_code'])) : self::generateNextVariantCode();
        $codeExists = Database::fetchOne("SELECT id FROM product_variants WHERE variant_code = ?", [$variantCode]);
        if ($codeExists) {
            $variantCode = self::generateNextVariantCode();
        }

        // Generate or validate SKU
        $sku = !empty($data['sku']) ? strtoupper(trim($data['sku'])) : self::generateVariantSku($productId, $colorId, $designId, $sizeId);
        $skuExists = Database::fetchOne("SELECT id FROM product_variants WHERE sku = ?", [$sku]);
        if ($skuExists) {
            throw new RuntimeException("SKU '{$sku}' is already taken. Please customize the SKU.");
        }

        // Stable QR Code: Default directly to Variant ID (e.g. V0001) for single stable variant QR
        $qrCode = !empty($data['qr_code']) ? strtoupper(trim($data['qr_code'])) : $variantCode;
        $qrExists = Database::fetchOne("SELECT id FROM product_variants WHERE qr_code = ?", [$qrCode]);
        if ($qrExists) {
            // If candidate QR exists (e.g. V0001 taken), fallback to SKU or next code
            $qrCode = $sku;
        }

        $mrp = max(0.0, (float)($data['mrp'] ?? 0));
        $sellingPrice = max(0.0, (float)($data['selling_price'] ?? 0));
        $productionCost = max(0.0, (float)($data['production_cost'] ?? 0));
        $purchasePrice = max(0.0, (float)($data['purchase_price'] ?? $productionCost));
        $hsnCode = !empty($data['hsn_code']) ? trim($data['hsn_code']) : ($product['hsn_code'] ?? '6109');
        $gstRate = isset($data['gst_rate']) ? (float)$data['gst_rate'] : (float)($product['gst_percentage'] ?? 5.0);
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO product_variants 
                 (variant_code, product_id, size_id, color_id, design_id, attr_hash, sku, hsn_code, gst_rate, qr_code, mrp, purchase_price, production_cost, selling_price, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $variantCode,
                    $productId,
                    $sizeId,
                    $colorId,
                    $designId,
                    $attrHash,
                    $sku,
                    $hsnCode,
                    $gstRate,
                    $qrCode,
                    $mrp,
                    $purchasePrice,
                    $productionCost,
                    $sellingPrice,
                    $isActive
                ]
            );

            $variantId = (int)Database::lastInsertId();
            Database::commit();

            return ProductVariant::find($variantId);
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update an existing Variant.
     * Prevents altering identity attributes (catalog, color, design, size, SKU) if historical records exist.
     */
    public static function updateVariant(int $id, array $data): array {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            throw new InvalidArgumentException("Variant not found.");
        }

        $hasHistory = ProductVariant::hasHistory($id);

        $colorId = array_key_exists('color_id', $data) ? (!empty($data['color_id']) ? (int)$data['color_id'] : null) : $variant['color_id'];
        $designId = array_key_exists('design_id', $data) ? (!empty($data['design_id']) ? (int)$data['design_id'] : null) : $variant['design_id'];
        $sizeId = array_key_exists('size_id', $data) ? (!empty($data['size_id']) ? (int)$data['size_id'] : null) : $variant['size_id'];

        if ($hasHistory) {
            // Cannot modify structural attributes if history exists
            if ($colorId != $variant['color_id'] || $designId != $variant['design_id'] || $sizeId != $variant['size_id']) {
                throw new RuntimeException("Cannot alter Color, Design, or Size for this variant because it already has inventory, batch, or billing history.");
            }
        } else {
            // Validate duplicate if attributes changed
            if ($colorId != $variant['color_id'] || $designId != $variant['design_id'] || $sizeId != $variant['size_id']) {
                if (self::checkDuplicateCombination((int)$variant['product_id'], $colorId, $designId, $sizeId, $id)) {
                    throw new RuntimeException("Another variant with this exact combination already exists in this Catalog!");
                }
            }
        }

        // Compute updated attribute hash
        $cVal = $colorId ? (int)$colorId : 0;
        $dVal = $designId ? (int)$designId : 0;
        $sVal = $sizeId ? (int)$sizeId : 0;
        $attrHash = (int)$variant['product_id'] . ':' . $cVal . ':' . $dVal . ':' . $sVal;

        $mrp = isset($data['mrp']) ? max(0.0, (float)$data['mrp']) : (float)$variant['mrp'];
        $sellingPrice = isset($data['selling_price']) ? max(0.0, (float)$data['selling_price']) : (float)$variant['selling_price'];
        $productionCost = isset($data['production_cost']) ? max(0.0, (float)$data['production_cost']) : (float)$variant['production_cost'];
        $purchasePrice = isset($data['purchase_price']) ? max(0.0, (float)$data['purchase_price']) : (float)$variant['purchase_price'];
        $hsnCode = isset($data['hsn_code']) ? trim($data['hsn_code']) : $variant['hsn_code'];
        $gstRate = isset($data['gst_rate']) ? (float)$data['gst_rate'] : (float)$variant['gst_rate'];
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : (int)$variant['is_active'];

        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE product_variants SET
                    color_id = ?, design_id = ?, size_id = ?, attr_hash = ?,
                    mrp = ?, selling_price = ?, production_cost = ?, purchase_price = ?,
                    hsn_code = ?, gst_rate = ?, is_active = ?
                 WHERE id = ?",
                [
                    $colorId, $designId, $sizeId, $attrHash,
                    $mrp, $sellingPrice, $productionCost, $purchasePrice,
                    $hsnCode, $gstRate, $isActive,
                    $id
                ]
            );
            Database::commit();

            return ProductVariant::find($id);
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Resolve a scanned QR code to its exact Variant and real-time Production Unit stock.
     */
    public static function resolveQrCode(string $rawQr, ?int $productionUnitId = null): ?array {
        $code = strtoupper(trim($rawQr));
        if ($code === '') return null;

        // Try exact QR match, SKU match, or Variant Code match
        $variant = ProductVariant::findByQr($code);
        if (!$variant) {
            return null;
        }

        // Fetch real-time available stock at production units
        $variantId = (int)$variant['id'];
        $stock = self::getVariantStock($variantId, $productionUnitId);

        return array_merge($variant, [
            'available_stock'   => $stock['available_qty'],
            'production_unit_id'=> $productionUnitId,
            'unit_stock_breakdown' => $stock['breakdown']
        ]);
    }

    /**
     * Get real-time stock balance for a variant at Production Units.
     */
    public static function getVariantStock(int $variantId, ?int $productionUnitId = null): array {
        $params = [$variantId];
        $sql = "SELECT sb.*, pu.name as unit_name
                FROM stock_balances sb
                LEFT JOIN production_units pu ON pu.id = sb.location_id
                WHERE sb.variant_id = ? AND sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE'";

        if ($productionUnitId) {
            $sql .= " AND sb.location_id = ?";
            $params[] = $productionUnitId;
        }

        $rows = Database::fetchAll($sql, $params);
        $total = 0.0;
        $breakdown = [];

        foreach ($rows as $r) {
            $qty = (float)$r['quantity'];
            $total += $qty;
            $breakdown[] = [
                'unit_id'   => (int)$r['location_id'],
                'unit_name' => $r['unit_name'] ?? ('Unit #' . $r['location_id']),
                'quantity'  => $qty
            ];
        }

        return [
            'available_qty' => $total,
            'breakdown'     => $breakdown
        ];
    }

    /**
     * Query variants with rich filtering and pagination.
     */
    public static function listVariants(array $filters = []): array {
        $sql = "SELECT pv.*,
                       p.name as product_name, p.code as parent_sku,
                       c.name as category_name, c.id as category_id,
                       sc.name as subcategory_name, sc.id as subcategory_id,
                       b.name as brand_name,
                       col.name as color_name, col.hex_code as color_hex,
                       d.name as design_name, d.code as design_code,
                       s.name as size_name, s.code as size_code,
                       COALESCE(stk.total_stock, 0) as current_stock
                FROM product_variants pv
                JOIN products p ON p.id = pv.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN colors col ON col.id = pv.color_id
                LEFT JOIN designs d ON d.id = pv.design_id
                LEFT JOIN sizes s ON s.id = pv.size_id
                LEFT JOIN (
                    SELECT variant_id, SUM(quantity) as total_stock
                    FROM stock_balances
                    WHERE location_type = 'PRODUCTION_UNIT' AND stock_state = 'AVAILABLE'
                    GROUP BY variant_id
                ) stk ON stk.variant_id = pv.id
                WHERE 1 = 1";
        
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                pv.variant_code LIKE ? 
                OR pv.sku LIKE ? 
                OR pv.qr_code LIKE ? 
                OR p.name LIKE ? 
                OR p.code LIKE ? 
                OR col.name LIKE ? 
                OR d.name LIKE ? 
                OR s.name LIKE ?
            )";
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['subcategory_id'])) {
            $sql .= " AND p.subcategory_id = ?";
            $params[] = (int)$filters['subcategory_id'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND pv.product_id = ?";
            $params[] = (int)$filters['product_id'];
        }

        if (!empty($filters['hide_unstocked_deferred'])) {
            $sql .= " AND (COALESCE(p.variants_deferred, 0) = 0 OR pv.is_active = 1)";
        }

        if (!empty($filters['color_id'])) {
            $sql .= " AND pv.color_id = ?";
            $params[] = (int)$filters['color_id'];
        }

        if (!empty($filters['design_id'])) {
            $sql .= " AND pv.design_id = ?";
            $params[] = (int)$filters['design_id'];
        }

        if (!empty($filters['size_id'])) {
            $sql .= " AND pv.size_id = ?";
            $params[] = (int)$filters['size_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND pv.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $sql .= " ORDER BY pv.id DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Find or create a Color record by name.
     */
    public static function findOrCreateColor(?string $colorName): ?int {
        $name = trim($colorName ?? '');
        if ($name === '') return null;

        $existing = Database::fetchOne("SELECT id FROM colors WHERE LOWER(name) = LOWER(?)", [$name]);
        if ($existing) return (int)$existing['id'];

        Database::execute("INSERT INTO colors (name, is_active) VALUES (?, 1)", [strtoupper($name)]);
        return (int)Database::lastInsertId();
    }

    /**
     * Find or create a Size record by name / code.
     */
    public static function findOrCreateSize(?string $sizeName): ?int {
        $name = trim($sizeName ?? '');
        if ($name === '') return null;

        $existing = Database::fetchOne("SELECT id FROM sizes WHERE LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?)", [$name, $name]);
        if ($existing) return (int)$existing['id'];

        $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $name), 0, 10)) ?: strtoupper($name);
        Database::execute("INSERT INTO sizes (name, code, is_active) VALUES (?, ?, 1)", [strtoupper($name), $code]);
        return (int)Database::lastInsertId();
    }

    /**
     * Find or create a Design record by name.
     */
    public static function findOrCreateDesign(?string $designName): ?int {
        $name = trim($designName ?? '');
        if ($name === '') return null;

        $existing = Database::fetchOne("SELECT id FROM designs WHERE LOWER(name) = LOWER(?)", [$name]);
        if ($existing) return (int)$existing['id'];

        $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $name), 0, 10)) ?: strtoupper($name);
        Database::execute("INSERT INTO designs (name, code, is_active) VALUES (?, ?, 1)", [strtoupper($name), $code]);
        return (int)Database::lastInsertId();
    }

    /**
     * Find an existing variant matching catalog + attributes, or create a brand new one.
     * Enforces the single stable QR per variant requirement.
     */
    public static function findOrCreateVariantForInward(int $productId, array $attrs = [], bool $allowDeferredProduction = false): array {
        if (!$allowDeferredProduction) {
            $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
            if ((int)($product['variants_deferred'] ?? 0) === 1) {
                throw new RuntimeException('Variants for this production style are created after stitching is completed and QR codes are generated.');
            }
        }

        $colorId = !empty($attrs['color_id']) ? (int)$attrs['color_id'] : self::findOrCreateColor($attrs['color'] ?? null);
        $sizeId = !empty($attrs['size_id']) ? (int)$attrs['size_id'] : self::findOrCreateSize($attrs['size'] ?? null);
        $designId = !empty($attrs['design_id']) ? (int)$attrs['design_id'] : self::findOrCreateDesign($attrs['design_name'] ?? null);

        $colorVal = $colorId ?: 0;
        $designVal = $designId ?: 0;
        $sizeVal = $sizeId ?: 0;
        $hash = $productId . ':' . $colorVal . ':' . $designVal . ':' . $sizeVal;

        // Check if matching variant already exists
        $existing = Database::fetchOne("SELECT * FROM product_variants WHERE attr_hash = ?", [$hash]);
        if ($existing) {
            $mrp = isset($attrs['mrp']) ? (float)$attrs['mrp'] : (float)$existing['mrp'];
            $selling = isset($attrs['selling_price']) ? (float)$attrs['selling_price'] : (float)$existing['selling_price'];
            $cost = isset($attrs['unit_price']) ? (float)$attrs['unit_price'] : (float)$existing['purchase_price'];

            if ($selling > 0 || $mrp > 0) {
                Database::execute("
                    UPDATE product_variants 
                    SET mrp = GREATEST(mrp, ?), 
                        selling_price = CASE WHEN ? > 0 THEN ? ELSE selling_price END,
                        purchase_price = CASE WHEN ? > 0 THEN ? ELSE purchase_price END
                    WHERE id = ?
                ", [$mrp, $selling, $selling, $cost, $cost, $existing['id']]);
            }
            return ProductVariant::find((int)$existing['id']);
        }

        // Otherwise create new variant
        return self::createVariant([
            'product_id'     => $productId,
            'color_id'       => $colorId,
            'design_id'      => $designId,
            'size_id'        => $sizeId,
            'sku'            => $attrs['vendor_sku'] ?? null,
            'hsn_code'       => $attrs['hsn_code'] ?? null,
            'gst_rate'       => $attrs['gst_rate'] ?? 5.0,
            'mrp'            => $attrs['mrp'] ?? 0,
            'selling_price'  => $attrs['selling_price'] ?? 0,
            'purchase_price' => $attrs['unit_price'] ?? 0,
            'production_cost'=> $attrs['unit_price'] ?? 0,
            'is_active'      => isset($attrs['is_active']) ? (int)$attrs['is_active'] : 1
        ], $allowDeferredProduction);
    }
}
