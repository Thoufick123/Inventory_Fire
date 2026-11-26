<?php
/**
 * ProductVariant Model
 * Primary model for Variant ID records in the inventory system.
 */

class ProductVariant {

    /**
     * Find a variant by ID with full joined hierarchy.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT pv.*,
                    p.name as product_name, p.category_id, p.subcategory_id, p.sub_subcategory_id,
                    p.brand_id, p.hsn_code as product_hsn, p.gst_percentage as product_gst,
                    COALESCE(c.name, tc.name) as category_name, COALESCE(c.code, tc.code) as category_code,
                    COALESCE(sc.name, tsc.name) as subcategory_name, COALESCE(sc.code, tsc.code) as subcategory_code,
                    COALESCE(ssc.name, tssc.name) as sub_subcategory_name, COALESCE(ssc.code, tssc.code) as sub_subcategory_code,
                    b.name as brand_name,
                    col.name as color_name, col.hex_code as color_hex,
                    d.name as design_name, d.code as design_code,
                    s.name as size_name, s.code as size_code
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
             LEFT JOIN third_party_product_variants tpv ON tpv.id = (
                 SELECT id FROM third_party_product_variants WHERE product_variant_id = pv.id ORDER BY id DESC LIMIT 1
             )
             LEFT JOIN third_party_categories tc ON tc.id = tpv.third_party_category_id
             LEFT JOIN third_party_subcategories tsc ON tsc.id = tpv.third_party_subcategory_id
             LEFT JOIN third_party_sub_subcategories tssc ON tssc.id = tpv.third_party_sub_subcategory_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             WHERE pv.id = ?",
            [$id]
        );
    }

    public static function findJoined(int $id): ?array {
        return self::find($id);
    }

    /**
     * Get all active variants with joined details.
     */
    public static function getAllJoined(): array {
        return Database::fetchAll(
            "SELECT pv.*,
                    p.name as product_name,
                    c.name as category_name,
                    col.name as color_name,
                    d.name as design_name,
                    s.name as size_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             WHERE pv.is_active = 1
             ORDER BY pv.id DESC"
        );
    }

    /**
     * Find variant by Variant Code (e.g. V001).
     */
    public static function findByCode(string $code): ?array {
        $c = strtoupper(trim($code));
        return Database::fetchOne(
            "SELECT pv.*, p.name as product_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.variant_code = ?",
            [$c]
        );
    }

    /**
     * Find variant by QR Code.
     */
    public static function findByQr(string $qrCode): ?array {
        $q = strtoupper(trim($qrCode));
        return Database::fetchOne(
            "SELECT pv.*,
                    p.name as product_name, p.category_id, p.subcategory_id,
                    c.name as category_name, sc.name as subcategory_name,
                    col.name as color_name, d.name as design_name, s.name as size_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN designs d ON d.id = pv.design_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             WHERE pv.qr_code = ? OR pv.variant_code = ?",
            [$q, $q]
        );
    }

    /**
     * Check if a variant has any transaction or sales history.
     */
    public static function hasHistory(int $variantId): bool {
        $hasTx = (int)Database::fetchOne(
            "SELECT COUNT(*) as c FROM stock_transactions WHERE variant_id = ?",
            [$variantId]
        )['c'] > 0;
        if ($hasTx) return true;

        $hasBills = (int)Database::fetchOne(
            "SELECT COUNT(*) as c FROM production_bill_items WHERE variant_id = ?",
            [$variantId]
        )['c'] > 0;
        if ($hasBills) return true;

        $hasBatches = (int)Database::fetchOne(
            "SELECT COUNT(*) as c FROM production_batch_items WHERE variant_id = ?",
            [$variantId]
        )['c'] > 0;
        if ($hasBatches) return true;

        return false;
    }
}
