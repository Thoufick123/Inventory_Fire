<?php
/**
 * Router â€” URL to Controller Mapping
 * Simple routing system for the inventory management application.
 */

class Router {
    private array $routes = [];

    public function __construct() {
        $this->registerRoutes();
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, string $controller, string $method, array $middleware = []): void {
        $this->routes[] = [
            'method'     => 'GET',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $method,
            'middleware'  => $middleware,
        ];
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, string $controller, string $method, array $middleware = []): void {
        $this->routes[] = [
            'method'     => 'POST',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $method,
            'middleware'  => $middleware,
        ];
    }

    /**
     * Dispatch a request to the appropriate controller.
     */
    public function dispatch(string $url, string $method): void {
        while (str_starts_with($url, 'grm_inventory/')) {
            $url = substr($url, strlen('grm_inventory/'));
        }
        if ($url === 'grm_inventory') {
            $url = '';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $url, $matches)) {
                // Remove the full match
                array_shift($matches);

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    if (is_array($mw)) {
                        $mwClass = $mw[0];
                        $mwParams = array_slice($mw, 1);
                        call_user_func_array([new $mwClass, 'handle'], $mwParams);
                    } else {
                        (new $mw)->handle();
                    }
                }

                // Load and instantiate controller
                $controllerFile = BASE_PATH . '/app/Controllers/' . $route['controller'] . '.php';
                if (!file_exists($controllerFile)) {
                    Response::error(500, "Controller not found: {$route['controller']}");
                    return;
                }
                require_once $controllerFile;

                $controllerClass = $route['controller'];
                if (!class_exists($controllerClass)) {
                    if (class_exists("App\\Controllers\\{$controllerClass}")) {
                        $controllerClass = "App\\Controllers\\{$controllerClass}";
                    }
                }
                $controller = new $controllerClass();
                $action = $route['action'];

                if (!method_exists($controller, $action)) {
                    Response::error(500, "Action not found: {$route['controller']}@$action");
                    return;
                }

                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        // No route matched
        Response::error(404, "Page not found");
    }

    /**
     * Convert route path with {param} placeholders to regex.
     */
    private function convertToRegex(string $path): string {
        $path = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }

    /**
     * Register all application routes.
     */
    private function registerRoutes(): void {
        // ==========================================
        // AUTH ROUTES (no auth middleware)
        // ==========================================
        $this->get('', 'AuthController', 'loginForm');
        $this->get('login', 'AuthController', 'loginForm');
        $this->post('login', 'AuthController', 'login');
        $this->get('logout', 'AuthController', 'logout');
        $this->get('api/auth/check-status', 'AuthController', 'checkSessionStatus');

        // ==========================================
        // ADMIN ROUTES
        // ==========================================
        $this->get('admin/dashboard', 'AdminController', 'dashboard', ['AuthMiddleware']);
        $this->get('admin/staff', 'AdminController', 'staffList', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
        $this->get('admin/staff/check-phone', 'AdminController', 'checkPhoneAvailability', ['AuthMiddleware']);
        $this->get('admin/staff/create', 'AdminController', 'createStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'create']]);
        $this->post('admin/staff/store', 'AdminController', 'storeStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'create']]);
        $this->get('admin/staff/view/{id}', 'AdminController', 'viewStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
        $this->get('admin/staff/edit/{id}', 'AdminController', 'editStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/update/{id}', 'AdminController', 'updateStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/toggle/{id}', 'AdminController', 'toggleStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/delete/{id}', 'AdminController', 'deleteStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'delete']]);
        $this->get('admin/roles', 'AdminController', 'roles', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'view']]);
        $this->get('admin/roles/create', 'AdminController', 'createRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'create']]);
        $this->post('admin/roles/store', 'AdminController', 'storeRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'create']]);
        $this->get('admin/roles/edit/{id}', 'AdminController', 'editRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/update/{id}', 'AdminController', 'updateRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->get('admin/roles/permissions/{id}', 'AdminController', 'managePermissions', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/permissions/{id}', 'AdminController', 'savePermissions', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/delete/{id}', 'AdminController', 'deleteRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'delete']]);
        $this->get('admin/audit', 'SystemController', 'auditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'view']]);
        $this->get('admin/audit-logs', 'SystemController', 'auditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'view']]);
        $this->get('admin/change-password', 'AuthController', 'changePasswordForm', ['AuthMiddleware']);
        $this->post('admin/change-password', 'AuthController', 'changePassword', ['AuthMiddleware']);

        // Production Units Management
        $this->post('admin/production-units/store', 'AdminController', 'storeProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/update/{id}', 'AdminController', 'updateProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/delete/{id}', 'AdminController', 'deleteProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/toggle/{id}', 'AdminController', 'toggleProductionUnit', ['AuthMiddleware']);
        $this->post('api/admin/production-units/quick-add', 'AdminController', 'ajaxAddProductionUnit', ['AuthMiddleware']);
        $this->get('api/admin/production-units/check-duplicate', 'AdminController', 'ajaxCheckProductionUnitDuplicate', ['AuthMiddleware']);
        $this->get('api/qr/status-finder', 'AdminController', 'ajaxQrStatusFinder', ['AuthMiddleware']);


        // ==========================================
        // MASTER DATA / CATALOG AJAX ROUTES
        // ==========================================
        $this->get('api/categories', 'ProductionController', 'ajaxCategories', ['AuthMiddleware']);
        $this->post('api/categories/quick-add', 'ProductionController', 'ajaxQuickAddCategory', ['AuthMiddleware']);
        $this->post('api/categories/update/{id}', 'ProductionController', 'ajaxUpdateCategory', ['AuthMiddleware']);
        $this->post('api/categories/delete/{id}', 'ProductionController', 'ajaxDeleteCategory', ['AuthMiddleware']);
        $this->get('api/subcategories/{category_id}', 'ProductionController', 'ajaxSubcategories', ['AuthMiddleware']);
        $this->post('api/subcategories/quick-add', 'ProductionController', 'ajaxQuickAddSubcategory', ['AuthMiddleware']);
        $this->post('api/subcategories/update/{id}', 'ProductionController', 'ajaxUpdateSubcategory', ['AuthMiddleware']);
        $this->post('api/subcategories/delete/{id}', 'ProductionController', 'ajaxDeleteSubcategory', ['AuthMiddleware']);
        $this->get('api/sub-subcategories/{subcategory_id}', 'ProductionController', 'ajaxSubSubcategories', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/quick-add', 'ProductionController', 'ajaxQuickAddSubSubcategory', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/update/{id}', 'ProductionController', 'ajaxUpdateSubSubcategory', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/delete/{id}', 'ProductionController', 'ajaxDeleteSubSubcategory', ['AuthMiddleware']);
        $this->get('api/categories/attributes/{category_id}', 'ProductionController', 'ajaxGetCategoryAttributes', ['AuthMiddleware']);
        $this->post('api/categories/attributes/quick-add', 'ProductionController', 'ajaxQuickAddCategoryAttribute', ['AuthMiddleware']);
        $this->post('api/categories/attributes/add-option', 'ProductionController', 'ajaxAddCategoryAttributeOption', ['AuthMiddleware']);
        $this->post('api/categories/attributes/delete-option', 'ProductionController', 'ajaxDeleteCategoryAttributeOption', ['AuthMiddleware']);

        $this->post('api/brands/quick-add', 'ProductionController', 'ajaxQuickAddBrand', ['AuthMiddleware']);
        $this->post('api/sizes/quick-add', 'ProductionController', 'ajaxQuickAddSize', ['AuthMiddleware']);
        $this->post('api/sizes/delete/{id}', 'ProductionController', 'ajaxDeleteSize', ['AuthMiddleware']);
        $this->post('api/colors/quick-add', 'ProductionController', 'ajaxQuickAddColor', ['AuthMiddleware']);
        $this->post('api/colors/delete/{id}', 'ProductionController', 'ajaxDeleteColor', ['AuthMiddleware']);
        $this->get('api/products/search', 'ProductionController', 'searchProducts', ['AuthMiddleware']);
        $this->get('api/variants/search', 'ProductionController', 'searchVariants', ['AuthMiddleware']);
        $this->get('api/stock/check/{variant_id}', 'ProductionController', 'checkStock', ['AuthMiddleware']);
        $this->get('api/programs/{id}/details', 'ProductionController', 'ajaxProgramDetails', ['AuthMiddleware']);
        $this->get('api/cutting/{id}/details', 'ProductionController', 'ajaxCuttingDetails', ['AuthMiddleware']);
        $this->get('api/catalog/variants', 'ProductionController', 'ajaxCatalogVariants', ['AuthMiddleware']);
        $this->get('api/catalog/check-qr-code', 'ProductionController', 'ajaxCheckQRCode', ['AuthMiddleware']);
        $this->get('api/hsn-codes', 'ProductionController', 'ajaxHsnCodesList', ['AuthMiddleware']);
        $this->post('api/hsn-codes/store', 'ProductionController', 'ajaxStoreHsnCode', ['AuthMiddleware']);
        $this->post('production/hsn/store-ajax', 'ProductionController', 'ajaxStoreHsnCode', ['AuthMiddleware']);
        $this->post('api/operators/quick-add', 'ProductionController', 'ajaxQuickAddOperator', ['AuthMiddleware']);
        $this->post('api/tailors/quick-add', 'ProductionController', 'ajaxQuickAddTailor', ['AuthMiddleware']);

        // PRODUCTION ROUTES
        // ==========================================
        $this->get('production/dashboard', 'ProductionController', 'dashboard', ['AuthMiddleware']);
        
        // Production Sellers
        $this->get('production/sellers', 'ProductionController', 'sellerList', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/create', 'ProductionController', 'createSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->post('production/sellers/store', 'ProductionController', 'storeSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->get('production/sellers/view/{id}', 'ProductionController', 'viewSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/edit/{id}', 'ProductionController', 'editSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('production/sellers/update/{id}', 'ProductionController', 'updateSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->get('production/sellers/delete/{id}', 'ProductionController', 'confirmDeleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->post('production/sellers/delete/{id}', 'ProductionController', 'deleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->post('production/sellers/delete-roll/{id}', 'ProductionController', 'deleteSellerRoll', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('production/sellers/download-bill/{id}', 'ProductionController', 'downloadBill', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/download-roll-photo/{id}', 'ProductionController', 'downloadRollPhoto', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/{id}/labels', 'ProductionController', 'printSellerLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/roll-labels/{id}', 'ProductionController', 'printSellerRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->post('production/sellers/add-extra-label/{id}', 'ProductionController', 'addExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('production/sellers/delete-extra-label/{id}', 'ProductionController', 'deleteExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('production/sellers/export-replaced', 'ProductionController', 'exportReplacedRolls', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/sellers/{id}/roll-labels-ajax', 'ProductionController', 'ajaxSellerRollLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/sellers/next-roll-codes', 'ProductionController', 'ajaxNextFabricRollCodes', ['AuthMiddleware']);
        $this->get('api/sellers/{id}/rolls', 'ProductionController', 'ajaxSellerRolls', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/production/sellers/check-duplicate', 'ProductionController', 'ajaxCheckSupplierDuplicate', ['AuthMiddleware']);
        $this->get('api/production/sellers/check-deletable', 'ProductionController', 'ajaxCheckSupplierDeletable', ['AuthMiddleware']);

        // Unified Common Purchases (Fabric Rolls, Third-Party Products & Own Production Products)
        $this->get('production/purchases', 'ProductionController', 'productionPurchaseList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('production/purchases/create', 'ProductionController', 'createProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->post('production/purchases/store', 'ProductionController', 'storeProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->get('production/purchases/view/{id}', 'ProductionController', 'viewProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('production/purchases/edit/{id}', 'ProductionController', 'editProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->post('production/purchases/update/{id}', 'ProductionController', 'updateProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->post('production/purchases/delete/{id}', 'ProductionController', 'deleteProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'delete']]);
        $this->get('production/purchases/print/{id}', 'ProductionController', 'printProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('production/purchases/roll-qr/{id}', 'ProductionController', 'productionPurchaseRollQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('production/purchases/supply-qr/{id}', 'ProductionController', 'productionPurchaseSupplyQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->post('production/purchases/supply-inward/{id}', 'ProductionController', 'productionPurchaseSupplyInward', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);

        // Third-Party Product Mapping Module
        $this->get('production/purchases/mapping', 'ProductionController', 'thirdPartyMappingList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('production/purchases/mapping/create', 'ProductionController', 'createThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('production/purchases/mapping/create', 'ProductionController', 'storeThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('production/purchases/mapping/draft/{id}', 'ProductionController', 'viewThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/draft/{id}/add-product', 'ProductionController', 'addThirdPartyMappingDraftProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('production/purchases/mapping/draft/{id}/move', 'ProductionController', 'moveThirdPartyMappingDraftToPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/draft/item/{id}/generate-qr', 'ProductionController', 'generateThirdPartyMappingDraftQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('production/purchases/mapping/bill/{id}', 'ProductionController', 'thirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('production/purchases/mapping/edit/{id}', 'ProductionController', 'thirdPartyMappingEdit', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/save', 'ProductionController', 'saveThirdPartyMapping', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/add-product', 'ProductionController', 'ajaxAddBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('production/purchases/mapping/delete-product/{id}', 'ProductionController', 'ajaxDeleteBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('production/purchases/mapping/unmap/{id}', 'ProductionController', 'unmapPurchaseItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('production/purchases/mapping/unmap-bill/{id}', 'ProductionController', 'unmapPurchaseBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->get('api/purchases/mapping/item/{id}', 'ProductionController', 'ajaxGetThirdPartyItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('production/purchases/mapping/catalog', 'ProductionController', 'thirdPartyCatalogList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('production/purchases/mapping/catalog/create', 'ProductionController', 'createThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('production/purchases/mapping/catalog/store', 'ProductionController', 'storeThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('production/purchases/mapping/catalog/edit/{id}', 'ProductionController', 'editThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/catalog/update/{id}', 'ProductionController', 'updateThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('production/purchases/mapping/catalog/delete/{id}', 'ProductionController', 'deleteThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);

        // Purchased Stock Sub-Module (Fabric Rolls, Raw Materials, Third-Party)
        $this->get('production/purchased-stock', 'ProductionController', 'purchasedStockIndex', ['AuthMiddleware']);
        $this->get('production/purchased-stock/view/{id}', 'ProductionController', 'purchasedStockView', ['AuthMiddleware']);
        $this->get('production/purchased-stock/edit/{id}', 'ProductionController', 'purchasedStockEdit', ['AuthMiddleware']);
        $this->post('production/purchased-stock/edit/{id}', 'ProductionController', 'purchasedStockUpdate', ['AuthMiddleware']);
        $this->get('production/purchased-stock/details/{id}', 'ProductionController', 'ajaxPurchasedStockDetails', ['AuthMiddleware']);
        $this->post('production/purchased-stock/update/{id}', 'ProductionController', 'ajaxPurchasedStockUpdate', ['AuthMiddleware']);
        $this->post('production/purchased-stock/delete/{id}', 'ProductionController', 'ajaxPurchasedStockDelete', ['AuthMiddleware']);

        // Common Purchases Clean Aliases
        $this->get('purchases', 'ProductionController', 'productionPurchaseList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('purchases/create', 'ProductionController', 'createProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->post('purchases/store', 'ProductionController', 'storeProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->get('purchases/view/{id}', 'ProductionController', 'viewProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('purchases/mapping', 'ProductionController', 'thirdPartyMappingList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('purchases/mapping/create', 'ProductionController', 'createThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/create', 'ProductionController', 'storeThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('purchases/mapping/draft/{id}', 'ProductionController', 'viewThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/draft/{id}/add-product', 'ProductionController', 'addThirdPartyMappingDraftProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/draft/{id}/move', 'ProductionController', 'moveThirdPartyMappingDraftToPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/draft/item/{id}/generate-qr', 'ProductionController', 'generateThirdPartyMappingDraftQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('purchases/mapping/bill/{id}', 'ProductionController', 'thirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('purchases/mapping/edit/{id}', 'ProductionController', 'thirdPartyMappingEdit', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/save', 'ProductionController', 'saveThirdPartyMapping', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/add-product', 'ProductionController', 'ajaxAddBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/delete-product/{id}', 'ProductionController', 'ajaxDeleteBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/unmap/{id}', 'ProductionController', 'unmapPurchaseItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/unmap-bill/{id}', 'ProductionController', 'unmapPurchaseBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->get('purchases/mapping/catalog', 'ProductionController', 'thirdPartyCatalogList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('purchases/mapping/catalog/create', 'ProductionController', 'createThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/catalog/store', 'ProductionController', 'storeThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('purchases/mapping/catalog/edit/{id}', 'ProductionController', 'editThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/catalog/update/{id}', 'ProductionController', 'updateThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/catalog/delete/{id}', 'ProductionController', 'deleteThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);

        // Production Billing
        $this->get('production/billing', 'ProductionBillingController', 'billList', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('production/billing/move-to-batch/{id}', 'ProductionBillingController', 'moveBillToBatchForm', ['AuthMiddleware']);
        $this->post('production/billing/move-to-batch/{id}', 'ProductionBillingController', 'createBillDispatchBatch', ['AuthMiddleware']);
        $this->get('production/billing/create', 'ProductionBillingController', 'createBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->post('production/billing/create', 'ProductionBillingController', 'createBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->post('production/billing/store', 'ProductionBillingController', 'storeBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->get('production/billing/view/{id}', 'ProductionBillingController', 'viewBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('production/billing/edit/{id}', 'ProductionBillingController', 'editBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'edit']]);
        $this->post('production/billing/update/{id}', 'ProductionBillingController', 'updateBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'edit']]);
        $this->post('production/billing/delete/{id}', 'ProductionBillingController', 'deleteBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'delete']]);
        $this->get('production/billing/print/{id}', 'ProductionBillingController', 'printBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
                $this->get('api/production/billing/search', 'ProductionBillingController', 'searchInventory', ['AuthMiddleware']);
        $this->get('api/production/billing/customer-search', 'ProductionBillingController', 'searchCustomer', ['AuthMiddleware']);
        $this->get('api/production/billing/customer-history', 'ProductionBillingController', 'customerHistory', ['AuthMiddleware']);

        // Production Customer Returns & Exchanges
        $this->get('production/returns', 'ProductionReturnController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->get('production/returns/create', 'ProductionReturnController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->get('production/returns/lookup', 'ProductionReturnController', 'ajaxLookupBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->post('production/returns/store', 'ProductionReturnController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->post('production/returns/triage-item', 'ProductionReturnController', 'triageItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->post('production/returns/bulk-triage', 'ProductionReturnController', 'bulkTriage', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->get('production/returns/view/{id}', 'ProductionReturnController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->get('production/returns/print/{id}', 'ProductionReturnController', 'print', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);

        // Inter-Module Stock Requirements & Requisitions Flow
        $this->get('production/stock-requests', 'ProductionStockRequestController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('production/stock-requests/create', 'ProductionStockRequestController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->post('production/stock-requests/store', 'ProductionStockRequestController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->get('production/stock-requests/view/{id}', 'ProductionStockRequestController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->post('production/stock-requests/receive/{id}', 'ProductionStockRequestController', 'receiveBySendArea', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('production/stock-requests/send-stocks/{id}', 'ProductionStockRequestController', 'sendStocks', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('production/stock-requests/confirm-received/{id}', 'ProductionStockRequestController', 'confirmReceived', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('production/stock-requests/update-status/{id}', 'ProductionStockRequestController', 'updateStatus', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);

        // Stock Requirements Aliases
        $this->get('production/stock-requirements', 'ProductionStockRequestController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('production/stock-requirements/create', 'ProductionStockRequestController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->post('production/stock-requirements/store', 'ProductionStockRequestController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        // ==========================================
        // GOODS INWARD (GRN) & PRODUCTION INWARD WORKSTATION
        // ==========================================
        $this->get('production/inwards', 'InwardController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('production/inwards/create', 'InwardController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('production/inwards/store', 'InwardController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('production/inwards/view/{id}', 'InwardController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->post('production/inwards/verify/{id}', 'InwardController', 'verifyInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('production/inwards/label/{id}', 'InwardController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('production/inwards/gatepass/{id}', 'InwardController', 'printOutwardGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Warehouse Inward scheme compatibility aliases
        $this->get('warehouse/dashboard', 'ProductionController', 'dashboard', ['AuthMiddleware']);
        $this->get('warehouse/inward', 'InwardController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('warehouse/inward/create', 'InwardController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('warehouse/inward/store', 'InwardController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('warehouse/inward/view/{id}', 'InwardController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->post('warehouse/inward/verify/{id}', 'InwardController', 'verifyInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('warehouse/inward/label/{id}', 'InwardController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('warehouse/inward/gatepass/{id}', 'InwardController', 'printOutwardGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Inward Ajax APIs
        $this->post('api/warehouse/inward/scan', 'InwardController', 'ajaxScanInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->post('api/warehouse/inward/crosscheck-scan', 'InwardController', 'ajaxCrossCheckScan', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('api/warehouse/inward/lookup/{code}', 'InwardController', 'ajaxLookupBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('api/warehouse/inward/batch-history/{id}', 'InwardController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Production Inward API aliases
        $this->post('api/production/inward/scan', 'InwardController', 'ajaxScanInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->post('api/production/inward/crosscheck-scan', 'InwardController', 'ajaxCrossCheckScan', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('api/production/inward/lookup/{code}', 'InwardController', 'ajaxLookupBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('api/production/inward/batch-history/{id}', 'InwardController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Production Reports & CSV / Excel Export (Legacy forward to ExportController)
        $this->get('production/reports/export', 'ExportController', 'download', ['AuthMiddleware']);
        $this->post('production/reports/export', 'ExportController', 'download', ['AuthMiddleware']);

        // Production Fabric & Roll Damages
        $this->get('production/damages', 'ProductionController', 'damageList', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->get('api/production/damages/scan-roll', 'ProductionController', 'ajaxLookupDamageRoll', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->get('api/production/damages/scan-roll-edit', 'ProductionController', 'ajaxLookupEditDamageRoll', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('production/damages/reasons/store', 'ProductionController', 'storeDamageReason', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->post('production/damages/store', 'ProductionController', 'storeDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->get('production/damages/view/{id}', 'ProductionController', 'viewDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->post('production/damages/update/{id}', 'ProductionController', 'updateDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('production/damages/resolve/{id}', 'ProductionController', 'resolveDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('production/damages/delete/{id}', 'ProductionController', 'deleteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'delete']]);

        // Production Catalog
        $this->get('production/catalog', 'ProductionController', 'catalogList', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('production/catalog/create', 'ProductionController', 'createCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->post('production/catalog/store', 'ProductionController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->get('production/catalog/view/{id}', 'ProductionController', 'viewCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('production/catalog/edit/{id}', 'ProductionController', 'editCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('production/catalog/update/{id}', 'ProductionController', 'updateCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('production/catalog/delete/{id}', 'ProductionController', 'deleteCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'delete']]);

        // Product Variants & QR Management
        $this->get('production/variants', 'VariantController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('production/variants/reports', 'VariantController', 'reports', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('production/variants/create', 'VariantController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'create']]);
        $this->post('production/variants/store', 'VariantController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'create']]);
        $this->get('production/variants/view/{id}', 'VariantController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('production/variants/edit/{id}', 'VariantController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->post('production/variants/update/{id}', 'VariantController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->post('production/variants/toggle/{id}', 'VariantController', 'toggle', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->get('production/variants/qr-label/{id}', 'VariantController', 'printQrLabel', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->post('production/variants/print-labels', 'VariantController', 'printBatchQrLabels', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);

        // QR Code Generation Module (Manual & Catalog/Variant Modes)
        $this->get('production/qr-generations', 'QrGenerationController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->get('production/qr-generations/create', 'QrGenerationController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('production/qr-generations/store-manual', 'QrGenerationController', 'storeManual', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('production/qr-generations/store-catalog', 'QrGenerationController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('production/qr-generations/store-purchase', 'QrGenerationController', 'storePurchaseBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('production/qr-generations/store-stitching', 'QrGenerationController', 'storeStitchingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('production/qr-generations/add-to-stock/{id}', 'QrGenerationController', 'addToStock', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->get('production/qr-generations/view/{id}', 'QrGenerationController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->get('production/qr-generations/print/{id}', 'QrGenerationController', 'printLabels', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->post('production/qr-generations/delete/{id}', 'QrGenerationController', 'delete', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'delete']]);

        // Variant & Design AJAX APIs
        $this->get('api/variants/catalogs', 'VariantController', 'ajaxCatalogs', ['AuthMiddleware']);
        $this->get('api/variants/preview-sku', 'VariantController', 'ajaxPreviewSku', ['AuthMiddleware']);
        $this->get('api/variants/check-duplicate', 'VariantController', 'ajaxCheckDuplicate', ['AuthMiddleware']);
        $this->get('api/variants/resolve-qr', 'VariantController', 'ajaxResolveQr', ['AuthMiddleware']);
        $this->post('api/variants/resolve-qr', 'VariantController', 'ajaxResolveQr', ['AuthMiddleware']);
        $this->get('api/designs', 'VariantController', 'ajaxDesignsList', ['AuthMiddleware']);
        $this->post('api/designs/quick-add', 'VariantController', 'ajaxQuickAddDesign', ['AuthMiddleware']);

        // Production Programs
        $this->get('production/programs', 'ProductionController', 'programList', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->get('production/programs/create', 'ProductionController', 'createProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->post('production/programs/store', 'ProductionController', 'storeProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->get('production/programs/edit/{id}', 'ProductionController', 'editProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('production/programs/update/{id}', 'ProductionController', 'updateProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->get('production/programs/view/{id}', 'ProductionController', 'viewProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->post('production/programs/approve/{id}', 'ProductionController', 'approveProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'approve']]);
        $this->post('production/programs/status/{id}', 'ProductionController', 'updateProgramStatus', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('production/programs/delete/{id}', 'ProductionController', 'deleteProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'delete']]);

        // Cutting
        $this->get('production/cutting', 'ProductionController', 'cuttingList', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('production/cutting/view/{id}', 'ProductionController', 'viewCuttingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('production/cutting/create', 'ProductionController', 'createCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->post('production/cutting/store', 'ProductionController', 'storeCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->get('production/cutting/edit/{id}', 'ProductionController', 'editCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('production/cutting/update/{id}', 'ProductionController', 'updateCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('production/cutting/delete/{id}', 'ProductionController', 'deleteCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'delete']]);
        $this->get('api/programs/{id}/details', 'ProductionController', 'ajaxProgramDetails', ['AuthMiddleware']);

        // Wastage Selling
        $this->get('production/wastage', 'ProductionController', 'wastageList', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);
        $this->post('production/wastage/store', 'ProductionController', 'storeWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'create']]);
        $this->post('production/wastage/update/{id}', 'ProductionController', 'updateWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'edit']]);
        $this->post('production/wastage/delete/{id}', 'ProductionController', 'deleteWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'delete']]);
        $this->get('production/wastage/receipt/{id}', 'ProductionController', 'wastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);

        // Stitching
        $this->get('production/stitching', 'ProductionController', 'stitchingList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('production/stitching/view/{id}', 'ProductionController', 'viewStitchingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('production/stitching/create', 'ProductionController', 'createStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->post('production/stitching/store', 'ProductionController', 'storeStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->get('production/stitching/edit/{id}', 'ProductionController', 'editStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('production/stitching/update/{id}', 'ProductionController', 'updateStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('production/stitching/delete/{id}', 'ProductionController', 'deleteStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'delete']]);

        // Stitching Wastage Selling
        $this->get('production/stitching-wastage', 'ProductionController', 'stitchingWastageList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);
        $this->post('production/stitching-wastage/store', 'ProductionController', 'storeStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'create']]);
        $this->post('production/stitching-wastage/update/{id}', 'ProductionController', 'updateStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'edit']]);
        $this->post('production/stitching-wastage/delete/{id}', 'ProductionController', 'deleteStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'delete']]);
        $this->get('production/stitching-wastage/receipt/{id}', 'ProductionController', 'stitchingWastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);

        // Production Expenses Management (Unit 1 & Unit 2)
        $this->get('production/expenses', 'ProductionController', 'expensesList', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->get('production/expenses/view/{id}', 'ProductionController', 'viewExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('production/expenses/store', 'ProductionController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->post('production/expenses/update/{id}', 'ProductionController', 'updateExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'edit']]);
        $this->post('production/expenses/delete/{id}', 'ProductionController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'delete']]);
        $this->get('production/expenses/receipt/{id}', 'ProductionController', 'expenseReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('api/production/expenses/quick-add-item', 'ProductionController', 'ajaxQuickAddExpenseItem', ['AuthMiddleware']);
        $this->post('api/production/expenses/quick-add-category', 'ProductionController', 'ajaxQuickAddExpenseCategory', ['AuthMiddleware']);
        $this->post('api/production/expenses/quick-add-unit', 'ProductionController', 'ajaxQuickAddUnit', ['AuthMiddleware']);
        $this->get('api/production/expenses/items', 'ProductionController', 'ajaxGetExpenseItems', ['AuthMiddleware']);

        // QC
        $this->get('production/qc', 'ProductionController', 'qcList', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('production/qc/view/{id}', 'ProductionController', 'viewQCBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('production/qc/create', 'ProductionController', 'createQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->post('production/qc/store', 'ProductionController', 'storeQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->post('production/qc/ajax-add-inspector', 'ProductionController', 'ajaxAddQcInspector', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->get('production/qc/inspect/{id}', 'ProductionController', 'inspectQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/update/{id}', 'ProductionController', 'updateQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/save-ajax/{id}', 'ProductionController', 'saveQCAjax', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/delete/{id}', 'ProductionController', 'deleteQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'delete']]);

        // Production Inventory (Post-QC Passed Inventory)
        $this->get('production/inventory', 'ProductionController', 'productionInventory', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->post('production/inventory/update-price', 'ProductionController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('api/production/inventory/update-price', 'ProductionController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->get('production/inventory/batch/{id}', 'ProductionController', 'productionInventoryBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->get('production/inventory/supply/{id}', 'ProductionController', 'productionInventorySupplyBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->post('production/inventory/delete-piece', 'ProductionController', 'deleteProductionInventoryPiece', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);
        $this->post('production/inventory/supply/update-stock', 'ProductionController', 'updateProductionInventorySupplyStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('production/inventory/supply/delete', 'ProductionController', 'deleteProductionInventorySupplyStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);
        $this->post('production/inventory/qc/update-stock', 'ProductionController', 'updateProductionInventoryQcStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('production/inventory/qc/delete', 'ProductionController', 'deleteProductionInventoryQcStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);

        // Batch & Outward
        $this->get('production/batches', 'ProductionController', 'batchList', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('production/batches/create', 'ProductionController', 'createBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->post('production/batches/store', 'ProductionController', 'storeBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->get('production/batches/view/{id}', 'ProductionController', 'viewBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('production/batches/bill/{id}', 'ProductionController', 'printBatchBill', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('production/batches/invoice/{id}', 'ProductionController', 'printBatchInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->post('production/batches/approve/{id}', 'ProductionController', 'approveBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'approve']]);
        $this->get('production/batches/label/{id}', 'ProductionController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'print']]);
        $this->get('production/batches/view-ajax/{id}', 'ProductionController', 'batchDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->post('production/batches/update/{id}', 'ProductionController', 'updateBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'edit']]);
        $this->post('production/batches/delete/{id}', 'ProductionController', 'deleteBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'delete']]);
        $this->get('production/outward', 'ProductionController', 'outwardList', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);
        $this->post('production/outward/store', 'ProductionController', 'storeOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->post('production/outward/dispatch/{id}', 'ProductionController', 'dispatchOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->get('production/outward/view-ajax/{id}', 'ProductionController', 'outwardDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);
        $this->post('production/outward/update/{id}', 'ProductionController', 'updateOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'edit']]);
        $this->post('production/outward/delete/{id}', 'ProductionController', 'deleteOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'delete']]);

        // Rework from Warehouse
        $this->get('production/warehouse-rework', 'ProductionController', 'warehouseReworkList', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('production/warehouse-rework/store', 'ProductionController', 'storeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'create']]);
        $this->post('production/warehouse-rework/status/{id}', 'ProductionController', 'updateWarehouseReworkStatus', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('production/warehouse-rework/complete/{id}', 'ProductionController', 'completeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('production/warehouse-rework/redispatch/{id}', 'ProductionController', 'redispatchWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'dispatch']]);
        $this->get('production/warehouse-rework/view-ajax/{id}', 'ProductionController', 'viewWarehouseReworkAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('production/warehouse-rework/delete/{id}', 'ProductionController', 'deleteWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'delete']]);
        $this->get('production/warehouse-rework/lookup-batch/{batchId}', 'ProductionController', 'lookupBatchVariantsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);




        // Production Reports
        $this->get('production/reports', 'ProductionController', 'reports', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('production/reports/export', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);

        // ==========================================
        // PRODUCTION HSN CONFIG MODULE
        // ==========================================
        $this->get('production/hsn-config', 'ProductionHsnController', 'index', ['AuthMiddleware']);
        $this->get('production/hsn-config/create', 'ProductionHsnController', 'create', ['AuthMiddleware']);
        $this->post('production/hsn-config/store', 'ProductionHsnController', 'store', ['AuthMiddleware']);
        $this->get('production/hsn-config/view/{id}', 'ProductionHsnController', 'view', ['AuthMiddleware']);
        $this->get('production/hsn-config/edit/{id}', 'ProductionHsnController', 'edit', ['AuthMiddleware']);
        $this->post('production/hsn-config/update/{id}', 'ProductionHsnController', 'update', ['AuthMiddleware']);
        $this->post('production/hsn-config/delete/{id}', 'ProductionHsnController', 'delete', ['AuthMiddleware']);


        // ==========================================
        // EXPORT MODULE (STANDALONE)
        // ==========================================
        $this->get('export', 'ExportController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('export/download', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->post('export/download', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('export/quick-csv', 'ExportController', 'quickCsv', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('export/direct-bills-csv', 'ExportController', 'directBillsCsv', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('export/normal-bills-csv', 'ExportController', 'normalBillsCsv', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);

        // ==========================================
        // ASSETS MANAGEMENT
        // ==========================================
        $this->get('assets', 'AssetController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'view']]);
        $this->get('assets/create', 'AssetController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'create']]);
        $this->post('assets/store', 'AssetController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'create']]);
        $this->get('assets/view/{id}', 'AssetController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'view']]);
        $this->get('assets/edit/{id}', 'AssetController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'edit']]);
        $this->post('assets/update/{id}', 'AssetController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'edit']]);
        $this->get('assets/print/{id}', 'AssetController', 'printLabel', ['AuthMiddleware', ['PermissionMiddleware', 'asset_register', 'view']]);
        $this->get('assets/maintenance', 'AssetController', 'maintenanceList', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'view']]);
        $this->get('assets/maintenance/create', 'AssetController', 'maintenanceCreate', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'create']]);
        $this->post('assets/maintenance/store', 'AssetController', 'maintenanceStore', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'create']]);
        $this->get('assets/maintenance/view/{id}', 'AssetController', 'maintenanceView', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'view']]);

        // ==========================================
        // SYSTEM ROUTES
        // ==========================================
        $this->get('system/notifications', 'SystemController', 'notifications', ['AuthMiddleware']);
        $this->post('system/notifications/read/{id}', 'SystemController', 'markRead', ['AuthMiddleware']);
        $this->post('system/notifications/read-all', 'SystemController', 'markAllRead', ['AuthMiddleware']);
        $this->get('api/notifications/unread', 'SystemController', 'unreadNotifications', ['AuthMiddleware']);

        // System: Audit Logs (Role-Based Access)
        $this->get('system/audit', 'SystemController', 'auditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'view']]);
        $this->get('system/audit/export', 'SystemController', 'exportAuditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'create']]);
        $this->post('system/audit/purge', 'SystemController', 'purgeAuditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'delete']]);

        // System: Settings Hub (Role-Based Access)
        $this->get('system/settings', 'SystemController', 'settings', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('system/settings/update', 'SystemController', 'updateSettings', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'edit']]);

        // System: Backup & Recovery (Role-Based Access)
        $this->get('system/backup', 'SystemController', 'backupList', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'view']]);
        $this->get('system/backup/export-sql', 'SystemController', 'exportSqlDirect', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->get('system/backup/export-zip', 'SystemController', 'exportZipDirect', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->post('system/backup/create', 'SystemController', 'createBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->post('system/backup/import', 'SystemController', 'importBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->get('system/backup/download/{id}', 'SystemController', 'downloadBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'view']]);
        $this->post('system/backup/restore', 'SystemController', 'restoreBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'edit']]);
        $this->post('system/backup/delete/{id}', 'SystemController', 'deleteBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'delete']]);
        $this->post('system/backup/wipe-test-data', 'SystemController', 'wipeTestData', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'delete']]);
        $this->post('system/backup/email-latest', 'SystemController', 'emailLatestBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->get('system/backup/send-email/{id}', 'SystemController', 'emailSpecificBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);
        $this->post('system/backup/send-email/{id}', 'SystemController', 'emailSpecificBackup', ['AuthMiddleware', ['PermissionMiddleware', 'backup', 'create']]);

        // Multi-Concern Enterprise Architecture (concern.pdf)
        $this->get('admin/concerns', 'ConcernController', 'index', ['AuthMiddleware']);
        $this->get('admin/concerns/create', 'ConcernController', 'create', ['AuthMiddleware']);
        $this->post('admin/concerns/store', 'ConcernController', 'store', ['AuthMiddleware']);
        $this->get('admin/concerns/edit/{id}', 'ConcernController', 'edit', ['AuthMiddleware']);
        $this->post('admin/concerns/update/{id}', 'ConcernController', 'update', ['AuthMiddleware']);
        $this->post('admin/concerns/assign-staff/{id}', 'ConcernController', 'assignStaff', ['AuthMiddleware']);
        $this->post('admin/concerns/quick-create-staff', 'ConcernController', 'quickCreateStaff', ['AuthMiddleware']);
        $this->get('admin/concerns/modules/{id}', 'ConcernController', 'modules', ['AuthMiddleware']);
        $this->post('admin/concerns/modules/{id}', 'ConcernController', 'updateModules', ['AuthMiddleware']);
        $this->get('admin/concerns/toggle/{id}', 'ConcernController', 'toggleStatus', ['AuthMiddleware']);
        $this->post('admin/concerns/toggle/{id}', 'ConcernController', 'toggleStatus', ['AuthMiddleware']);
        $this->get('system/concerns/switch/{id}', 'ConcernController', 'switch', ['AuthMiddleware']);
    }
}
