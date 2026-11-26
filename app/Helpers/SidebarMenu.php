<?php
/**
 * SidebarMenu Helper
 * Defines the canonical "All Modules" left-sidebar items and resolves
 * per-staff custom ordering stored in users.sidebar_order.
 */
class SidebarMenu {
    private static bool $schemaChecked = false;

    /**
     * Ensure users.sidebar_order column exists.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        try {
            Database::ensureColumn('users', 'sidebar_order', 'TEXT DEFAULT NULL');
        } catch (\Throwable $e) {
            error_log('SidebarMenu::ensureSchema failed: ' . $e->getMessage());
        }
    }

    /**
     * Canonical list of "All Modules" sidebar items in default order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allModuleItems(): array {
        return [
            [
                'key'          => 'sellers',
                'label'        => 'Sellers',
                'url'          => 'allmodules/sellers',
                'active_menus' => ['prod-sellers'],
                'module'       => 'purchases',
                'permissions'  => ['sellers'],
                'icon'         => '',
            ],
            [
                'key'          => 'purchases',
                'label'        => 'Purchases',
                'url'          => 'allmodules/purchases',
                'active_menus' => ['purchases', 'prod-purchases'],
                'module'       => 'purchases',
                'permissions'  => ['production_purchases', 'purchases'],
                'icon'         => '',
            ],
            [
                'key'          => 'mapping',
                'label'        => 'Bill Creations',
                'url'          => 'allmodules/purchases/mapping',
                'active_menus' => ['purchases-mapping', 'prod-purchases-mapping'],
                'module'       => 'purchases',
                'permissions'  => ['production_purchase_mapping'],
                'icon'         => '',
            ],
            [
                'key'          => 'cash_flow',
                'label'        => 'Cash Management',
                'url'          => 'allmodules/cash-flow',
                'active_menus' => ['cash-flow', 'shop-cash-flow'],
                'module'       => 'production',
                'permissions'  => ['cash_flow', 'shop_cash'],
                'icon'         => 'bi bi-cash-coin me-1',
            ],
            [
                'key'          => 'damages',
                'label'        => 'Damages',
                'url'          => 'allmodules/damages',
                'active_menus' => ['prod-damages'],
                'module'       => 'production',
                'permissions'  => ['damages'],
                'icon'         => '',
            ],
            [
                'key'          => 'catalog',
                'label'        => 'Catalog',
                'url'          => 'allmodules/catalog',
                'active_menus' => ['prod-catalog'],
                'module'       => 'production',
                'permissions'  => ['catalog'],
                'icon'         => '',
            ],
            [
                'key'          => 'variants',
                'label'        => 'Product Variants',
                'url'          => 'allmodules/variants',
                'active_menus' => ['prod-variants'],
                'module'       => 'production',
                'permissions'  => ['variants'],
                'icon'         => '',
            ],
            [
                'key'          => 'qr_generations',
                'label'        => 'QR Generations',
                'url'          => 'allmodules/qr-generations',
                'active_menus' => ['prod-qr-generation'],
                'module'       => 'production',
                'permissions'  => ['qr_generation'],
                'icon'         => '',
            ],
            [
                'key'          => 'purchased_stock',
                'label'        => 'Purchased Stock',
                'url'          => 'allmodules/purchased-stock',
                'active_menus' => ['prod-purchased-stock'],
                'module'       => 'production',
                'permissions'  => ['qr_generation', 'catalog'],
                'icon'         => '',
            ],
            [
                'key'          => 'programs',
                'label'        => 'Programs',
                'url'          => 'allmodules/programs',
                'active_menus' => ['programs'],
                'module'       => 'production',
                'permissions'  => ['programs'],
                'icon'         => '',
            ],
            [
                'key'          => 'cutting',
                'label'        => 'Cutting',
                'url'          => 'allmodules/cutting',
                'active_menus' => ['cutting'],
                'module'       => 'production',
                'permissions'  => ['cutting'],
                'icon'         => '',
            ],
            [
                'key'          => 'wastage',
                'label'        => 'Cutting Wastage',
                'url'          => 'allmodules/wastage',
                'active_menus' => ['wastage-selling'],
                'module'       => 'production',
                'permissions'  => ['wastage'],
                'icon'         => '',
            ],
            [
                'key'          => 'stitching',
                'label'        => 'Stitching',
                'url'          => 'allmodules/stitching',
                'active_menus' => ['stitching'],
                'module'       => 'production',
                'permissions'  => ['stitching'],
                'icon'         => '',
            ],
            [
                'key'          => 'stitching_wastage',
                'label'        => 'Stitching Wastage',
                'url'          => 'allmodules/stitching-wastage',
                'active_menus' => ['stitching-wastage'],
                'module'       => 'production',
                'permissions'  => ['stitching_wastage'],
                'icon'         => '',
            ],
            [
                'key'          => 'qc',
                'label'        => 'Quality Check',
                'url'          => 'allmodules/qc',
                'active_menus' => ['qc'],
                'module'       => 'production',
                'permissions'  => ['qc'],
                'icon'         => '',
            ],
            [
                'key'          => 'inventory',
                'label'        => 'Inventory',
                'url'          => 'allmodules/inventory',
                'active_menus' => ['prod-inventory'],
                'module'       => 'warehouse',
                'permissions'  => ['inventory'],
                'icon'         => '',
            ],
            [
                'key'          => 'inwards',
                'label'        => 'Inward',
                'url'          => 'allmodules/inwards',
                'active_menus' => ['inwards', 'prod-inwards'],
                'module'       => 'warehouse',
                'permissions'  => ['inward'],
                'icon'         => '',
            ],
            [
                'key'          => 'stock_requests',
                'label'        => 'Stock Request',
                'url'          => 'allmodules/stock-requests',
                'active_menus' => ['prod-stock-requests', 'stock-requests', 'stock-requirements'],
                'module'       => 'warehouse',
                'permissions'  => ['production_stock_requests', 'stock_requests'],
                'icon'         => '',
            ],
            [
                'key'          => 'expenses',
                'label'        => 'Expenses',
                'url'          => 'allmodules/expenses',
                'active_menus' => ['production-expenses'],
                'module'       => 'expenses',
                'permissions'  => ['production_expenses'],
                'icon'         => '',
            ],
            [
                'key'          => 'billing',
                'label'        => 'Billing / Pos',
                'url'          => 'allmodules/billing',
                'active_menus' => ['prod-billing'],
                'module'       => 'retail_pos',
                'permissions'  => ['production_billing'],
                'icon'         => '',
            ],
            [
                'key'          => 'returns',
                'label'        => 'Returns & Exchanges',
                'url'          => 'allmodules/returns',
                'active_menus' => ['prod-returns'],
                'module'       => 'returns',
                'permissions'  => ['production_returns'],
                'icon'         => '',
            ],
            [
                'key'          => 'batches',
                'label'        => 'Batches',
                'url'          => 'allmodules/batches',
                'active_menus' => ['batches', 'prod-batches'],
                'module'       => 'warehouse',
                'permissions'  => ['batches'],
                'icon'         => '',
            ],
            [
                'key'          => 'outward',
                'label'        => 'Outward & Dispatch',
                'url'          => 'allmodules/outward',
                'active_menus' => ['outward', 'prod-outward', 'batch-outward'],
                'module'       => 'warehouse',
                'permissions'  => ['outward'],
                'icon'         => '',
            ],
            [
                'key'          => 'rework',
                'label'        => 'Rework',
                'url'          => 'allmodules/warehouse-rework',
                'active_menus' => ['warehouse-rework'],
                'module'       => 'warehouse',
                'permissions'  => ['warehouse_rework'],
                'icon'         => '',
            ],
            [
                'key'          => 'hsn_config',
                'label'        => 'HSN Config',
                'url'          => 'allmodules/hsn-config',
                'active_menus' => ['production-hsn-config'],
                'module'       => 'production',
                'permissions'  => ['production_hsn', 'hsn_config'],
                'icon'         => '',
            ],
        ];
    }

    /**
     * Sanitize a submitted sidebar order array or JSON string into valid menu keys.
     *
     * @param string|array|null $input
     * @return array<int, string>
     */
    public static function sanitizeOrder($input): array {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($input)) {
            return [];
        }
        $validKeys = array_column(self::allModuleItems(), 'key');
        $validMap = array_flip($validKeys);
        $ordered = [];
        foreach ($input as $key) {
            if (is_string($key) && isset($validMap[$key]) && !in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }
        foreach ($validKeys as $k) {
            if (!in_array($k, $ordered, true)) {
                $ordered[] = $k;
            }
        }
        return $ordered;
    }

    /**
     * Sanitize a submitted sidebar order payload (either flat key array or concern-id => key array map).
     *
     * @param string|array|null $input
     * @return array<int|string, mixed>
     */
    public static function sanitizeOrderPayload($input): array {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($input) || empty($input)) {
            return [];
        }
        if (array_is_list($input)) {
            return self::sanitizeOrder($input);
        }
        $map = [];
        foreach ($input as $concernKey => $keys) {
            $cid = (int)$concernKey;
            if ($cid > 0 && is_array($keys)) {
                $map[(string)$cid] = self::sanitizeOrder($keys);
            }
        }
        return !empty($map) ? $map : self::sanitizeOrder($input);
    }

    /**
     * Load raw per-concern sidebar order map for a user ID (used by Staff form).
     *
     * @return array<string, array<int, string>>
     */
    public static function getOrderMapForUser(?int $userId): array {
        self::ensureSchema();
        if (!$userId || $userId <= 0) {
            return [];
        }
        try {
            $row = Database::fetchOne("SELECT sidebar_order FROM users WHERE id = ? LIMIT 1", [$userId]);
            if ($row && !empty($row['sidebar_order'])) {
                $decoded = json_decode((string)$row['sidebar_order'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    if (array_is_list($decoded)) {
                        return ['_default' => self::sanitizeOrder($decoded)];
                    }
                    $map = [];
                    foreach ($decoded as $k => $v) {
                        $cid = (int)$k;
                        if ($cid > 0 && is_array($v)) {
                            $map[(string)$cid] = self::sanitizeOrder($v);
                        }
                    }
                    return $map;
                }
            }
        } catch (\Throwable $e) {
            error_log('SidebarMenu::getOrderMapForUser failed: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Load custom sidebar order keys for a user ID (optionally scoped to active concern ID).
     *
     * @return array<int, string>
     */
    public static function getOrderForUser(?int $userId, ?int $concernId = null): array {
        self::ensureSchema();
        $defaultKeys = array_column(self::allModuleItems(), 'key');
        if (!$userId || $userId <= 0) {
            return $defaultKeys;
        }
        try {
            $row = Database::fetchOne("SELECT sidebar_order FROM users WHERE id = ? LIMIT 1", [$userId]);
            if ($row && !empty($row['sidebar_order'])) {
                $decoded = json_decode((string)$row['sidebar_order'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    if (!array_is_list($decoded)) {
                        if ($concernId && isset($decoded[(string)$concernId]) && is_array($decoded[(string)$concernId])) {
                            return self::sanitizeOrder($decoded[(string)$concernId]);
                        }
                        foreach ($decoded as $firstList) {
                            if (is_array($firstList)) {
                                return self::sanitizeOrder($firstList);
                            }
                        }
                    }
                    $sanitized = self::sanitizeOrder($decoded);
                    if (!empty($sanitized)) {
                        return $sanitized;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('SidebarMenu::getOrderForUser failed: ' . $e->getMessage());
        }
        return $defaultKeys;
    }

    /**
     * Return All Modules items sorted according to the user's saved sidebar_order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getOrderedItemsForUser(?int $userId, ?int $concernId = null): array {
        $items = self::allModuleItems();
        $byKey = [];
        foreach ($items as $item) {
            $byKey[$item['key']] = $item;
        }
        $orderKeys = self::getOrderForUser($userId, $concernId);
        $sorted = [];
        foreach ($orderKeys as $key) {
            if (isset($byKey[$key])) {
                $sorted[] = $byKey[$key];
            }
        }
        return $sorted;
    }
}
