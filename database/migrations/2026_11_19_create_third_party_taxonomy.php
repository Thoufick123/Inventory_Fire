<?php
/**
 * Create an independent category hierarchy for the third-party catalog and
 * migrate only categories already used by legacy third-party catalog entries.
 */
require_once __DIR__ . '/../../config/app.php';
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Services/PurchaseMappingFulfillmentService.php';

PurchaseMappingFulfillmentService::ensureSchema();

$slugify = static function (string $value): string {
    return strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $value), '-'));
};
$getOrCreateCategory = static function (array $source) use ($slugify): int {
    $name = trim((string)$source['name']);
    $slug = $slugify((string)($source['slug'] ?? $name)) ?: 'category';
    $existing = Database::fetchOne("SELECT id FROM third_party_categories WHERE slug = ?", [$slug]);
    if ($existing) {
        return (int)$existing['id'];
    }

    Database::execute(
        "INSERT INTO third_party_categories (name, code, slug, is_active, sort_order)
         VALUES (?, ?, ?, ?, ?)",
        [$name, $source['code'] ?? null, $slug, (int)($source['is_active'] ?? 1), (int)($source['sort_order'] ?? 0)]
    );
    return (int)Database::lastInsertId();
};
$getOrCreateSubcategory = static function (int $categoryId, array $source) use ($slugify): int {
    $slug = $slugify((string)($source['slug'] ?? $source['name'])) ?: 'subcategory';
    $existing = Database::fetchOne(
        "SELECT id FROM third_party_subcategories WHERE category_id = ? AND slug = ?",
        [$categoryId, $slug]
    );
    if ($existing) {
        return (int)$existing['id'];
    }

    Database::execute(
        "INSERT INTO third_party_subcategories (category_id, name, code, slug, is_active, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$categoryId, $source['name'], $source['code'] ?? null, $slug,
         (int)($source['is_active'] ?? 1), (int)($source['sort_order'] ?? 0)]
    );
    return (int)Database::lastInsertId();
};
$getOrCreateSubSubcategory = static function (int $subcategoryId, array $source) use ($slugify): int {
    $slug = $slugify((string)($source['slug'] ?? $source['name'])) ?: 'sub-subcategory';
    $existing = Database::fetchOne(
        "SELECT id FROM third_party_sub_subcategories WHERE subcategory_id = ? AND slug = ?",
        [$subcategoryId, $slug]
    );
    if ($existing) {
        return (int)$existing['id'];
    }

    Database::execute(
        "INSERT INTO third_party_sub_subcategories (subcategory_id, name, code, slug, is_active, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$subcategoryId, $source['name'], $source['code'] ?? null, $slug,
         (int)($source['is_active'] ?? 1), (int)($source['sort_order'] ?? 0)]
    );
    return (int)Database::lastInsertId();
};

$legacyProducts = Database::fetchAll(
    "SELECT tp.id, tp.category_id, tp.subcategory_id, tp.sub_subcategory_id,
            c.name AS category_name, c.code AS category_code, c.slug AS category_slug,
            c.is_active AS category_active, c.sort_order AS category_sort_order,
            sc.name AS subcategory_name, sc.code AS subcategory_code, sc.slug AS subcategory_slug,
            sc.is_active AS subcategory_active, sc.sort_order AS subcategory_sort_order,
            ssc.name AS sub_subcategory_name, ssc.code AS sub_subcategory_code, ssc.slug AS sub_subcategory_slug,
            ssc.is_active AS sub_subcategory_active, ssc.sort_order AS sub_subcategory_sort_order
     FROM third_party_products tp
     LEFT JOIN categories c ON c.id = tp.category_id
     LEFT JOIN subcategories sc ON sc.id = tp.subcategory_id
     LEFT JOIN sub_subcategories ssc ON ssc.id = tp.sub_subcategory_id
     WHERE tp.third_party_category_id IS NULL AND tp.category_id IS NOT NULL
     ORDER BY tp.id ASC"
);

$migrated = 0;
foreach ($legacyProducts as $product) {
    if (empty($product['category_name'])) {
        continue;
    }

    Database::beginTransaction();
    try {
        $categoryId = $getOrCreateCategory([
            'name' => $product['category_name'],
            'code' => $product['category_code'],
            'slug' => $product['category_slug'],
            'is_active' => $product['category_active'],
            'sort_order' => $product['category_sort_order'],
        ]);
        $subcategoryId = null;
        if (!empty($product['subcategory_name'])) {
            $subcategoryId = $getOrCreateSubcategory($categoryId, [
                'name' => $product['subcategory_name'],
                'code' => $product['subcategory_code'],
                'slug' => $product['subcategory_slug'],
                'is_active' => $product['subcategory_active'],
                'sort_order' => $product['subcategory_sort_order'],
            ]);
        }
        $subSubcategoryId = null;
        if ($subcategoryId && !empty($product['sub_subcategory_name'])) {
            $subSubcategoryId = $getOrCreateSubSubcategory($subcategoryId, [
                'name' => $product['sub_subcategory_name'],
                'code' => $product['sub_subcategory_code'],
                'slug' => $product['sub_subcategory_slug'],
                'is_active' => $product['sub_subcategory_active'],
                'sort_order' => $product['sub_subcategory_sort_order'],
            ]);
        }

        Database::execute(
            "UPDATE third_party_products
             SET category_id = NULL,
                 subcategory_id = NULL,
                 sub_subcategory_id = NULL,
                 third_party_category_id = ?,
                 third_party_subcategory_id = ?,
                 third_party_sub_subcategory_id = ?
             WHERE id = ?",
            [$categoryId, $subcategoryId, $subSubcategoryId, (int)$product['id']]
        );
        Database::commit();
        $migrated++;
    } catch (Throwable $e) {
        Database::rollback();
        throw new RuntimeException(
            "Failed migrating taxonomy for third-party catalog item {$product['id']}: " . $e->getMessage(),
            0,
            $e
        );
    }
}

echo "Third-party taxonomy ready; {$migrated} legacy catalog item(s) assigned to the independent category hierarchy.\n";
