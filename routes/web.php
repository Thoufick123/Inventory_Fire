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

        // Backward compatibility: seamlessly route legacy production/ to allmodules/
        if (str_starts_with($url, 'production/')) {
            $url = 'allmodules/' . substr($url, 11);
        } elseif (str_starts_with($url, 'api/production/')) {
            $url = 'api/allmodules/' . substr($url, 15);
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

                // Enforce CSRF token validation on all POST routes
                if ($method === 'POST' && class_exists('CSRF') && !CSRF::validateRequest()) {
                    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                        || str_starts_with($url, 'api/');
                    if ($isAjax) {
                        Response::jsonError('Invalid or expired security token. Please refresh and try again.', [], 403);
                    }
                    Session::flash('error', 'Invalid security token or session expired. Please try submitting again.');
                    if (!empty($_SERVER['HTTP_REFERER'])) {
                        Response::redirect($_SERVER['HTTP_REFERER']);
                    }
                    Response::redirect(url('login'));
                    return;
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
        $this->get('api/admin/dashboard/revenue', 'AdminController', 'ajaxDashboardRevenue', ['AuthMiddleware']);
        $this->get('admin/staff', 'AdminController', 'staffList', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
        $this->get('admin/staff/check-phone', 'AdminController', 'checkPhoneAvailability', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
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

        $this->get('api/qr/status-finder', 'AdminController', 'ajaxQrStatusFinder', ['AuthMiddleware']);


        // ==========================================
        // MASTER DATA / CATALOG AJAX ROUTES
        // ==========================================
        $taxonomyViewPerms   = [['catalog', 'view'], ['variants', 'view'], ['programs', 'view'], ['production_purchases', 'view'], ['production_purchase_mapping', 'view'], ['qr_generation', 'view']];
        $taxonomyCreatePerms = [['catalog', 'create'], ['catalog', 'edit'], ['variants', 'create'], ['variants', 'edit'], ['programs', 'create'], ['production_purchases', 'create'], ['production_purchase_mapping', 'create'], ['production_purchase_mapping', 'edit']];
        $taxonomyEditPerms   = [['catalog', 'edit'], ['variants', 'edit'], ['production_purchase_mapping', 'edit']];
        $taxonomyDeletePerms = [['catalog', 'delete'], ['catalog', 'edit'], ['variants', 'delete'], ['production_purchase_mapping', 'delete']];
        $hsnCreatePerms      = [['production_hsn', 'create'], ['hsn_config', 'create'], ['production_purchases', 'create'], ['production_purchases', 'edit'], ['catalog', 'create']];

        $this->get('api/categories', 'AllmodulesController', 'ajaxCategories', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyViewPerms]]);
        $this->post('api/categories/quick-add', 'AllmodulesController', 'ajaxQuickAddCategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/categories/update/{id}', 'AllmodulesController', 'ajaxUpdateCategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyEditPerms]]);
        $this->post('api/categories/delete/{id}', 'AllmodulesController', 'ajaxDeleteCategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);
        $this->get('api/subcategories/{category_id}', 'AllmodulesController', 'ajaxSubcategories', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyViewPerms]]);
        $this->post('api/subcategories/quick-add', 'AllmodulesController', 'ajaxQuickAddSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/subcategories/update/{id}', 'AllmodulesController', 'ajaxUpdateSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyEditPerms]]);
        $this->post('api/subcategories/delete/{id}', 'AllmodulesController', 'ajaxDeleteSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);
        $this->get('api/sub-subcategories/{subcategory_id}', 'AllmodulesController', 'ajaxSubSubcategories', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyViewPerms]]);
        $this->post('api/sub-subcategories/quick-add', 'AllmodulesController', 'ajaxQuickAddSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/sub-subcategories/update/{id}', 'AllmodulesController', 'ajaxUpdateSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyEditPerms]]);
        $this->post('api/sub-subcategories/delete/{id}', 'AllmodulesController', 'ajaxDeleteSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);
        $this->get('api/categories/attributes/{category_id}', 'AllmodulesController', 'ajaxGetCategoryAttributes', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyViewPerms]]);
        $this->post('api/categories/attributes/quick-add', 'AllmodulesController', 'ajaxQuickAddCategoryAttribute', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/categories/attributes/add-option', 'AllmodulesController', 'ajaxAddCategoryAttributeOption', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/categories/attributes/delete-option', 'AllmodulesController', 'ajaxDeleteCategoryAttributeOption', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);

        $this->post('api/brands/quick-add', 'AllmodulesController', 'ajaxQuickAddBrand', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/sizes/quick-add', 'AllmodulesController', 'ajaxQuickAddSize', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/sizes/delete/{id}', 'AllmodulesController', 'ajaxDeleteSize', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);
        $this->post('api/colors/quick-add', 'AllmodulesController', 'ajaxQuickAddColor', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyCreatePerms]]);
        $this->post('api/colors/delete/{id}', 'AllmodulesController', 'ajaxDeleteColor', ['AuthMiddleware', ['PermissionMiddleware', $taxonomyDeletePerms]]);
        $this->get('api/products/search', 'AllmodulesController', 'searchProducts', ['AuthMiddleware']);
        $this->get('api/variants/search', 'AllmodulesController', 'searchVariants', ['AuthMiddleware']);
        $this->get('api/stock/check/{variant_id}', 'AllmodulesController', 'checkStock', ['AuthMiddleware']);
        $this->get('api/programs/{id}/details', 'AllmodulesController', 'ajaxProgramDetails', ['AuthMiddleware']);
        $this->get('api/cutting/{id}/details', 'AllmodulesController', 'ajaxCuttingDetails', ['AuthMiddleware']);
        $this->get('api/catalog/variants', 'AllmodulesController', 'ajaxCatalogVariants', ['AuthMiddleware']);
        $this->get('api/catalog/check-qr-code', 'AllmodulesController', 'ajaxCheckQRCode', ['AuthMiddleware']);
        $this->get('api/hsn-codes', 'AllmodulesController', 'ajaxHsnCodesList', ['AuthMiddleware']);
        $this->post('api/hsn-codes/store', 'AllmodulesController', 'ajaxStoreHsnCode', ['AuthMiddleware', ['PermissionMiddleware', $hsnCreatePerms]]);
        $this->post('allmodules/hsn/store-ajax', 'AllmodulesController', 'ajaxStoreHsnCode', ['AuthMiddleware', ['PermissionMiddleware', $hsnCreatePerms]]);
        $this->post('api/operators/quick-add', 'AllmodulesController', 'ajaxQuickAddOperator', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->post('api/tailors/quick-add', 'AllmodulesController', 'ajaxQuickAddTailor', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);

        // PRODUCTION ROUTES
        // ==========================================
        $this->get('allmodules/dashboard', 'AllmodulesController', 'dashboard', ['AuthMiddleware']);
        
        // Production Sellers
        $this->get('allmodules/sellers', 'AllmodulesController', 'sellerList', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('allmodules/sellers/create', 'AllmodulesController', 'createSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->post('allmodules/sellers/store', 'AllmodulesController', 'storeSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->get('allmodules/sellers/view/{id}', 'AllmodulesController', 'viewSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('allmodules/sellers/edit/{id}', 'AllmodulesController', 'editSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('allmodules/sellers/update/{id}', 'AllmodulesController', 'updateSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->get('allmodules/sellers/delete/{id}', 'AllmodulesController', 'confirmDeleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->post('allmodules/sellers/delete/{id}', 'AllmodulesController', 'deleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->post('allmodules/sellers/delete-roll/{id}', 'AllmodulesController', 'deleteSellerRoll', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('allmodules/sellers/download-bill/{id}', 'AllmodulesController', 'downloadBill', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('allmodules/sellers/download-roll-photo/{id}', 'AllmodulesController', 'downloadRollPhoto', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('allmodules/sellers/{id}/labels', 'AllmodulesController', 'printSellerLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('allmodules/sellers/roll-labels/{id}', 'AllmodulesController', 'printSellerRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->post('allmodules/sellers/add-extra-label/{id}', 'AllmodulesController', 'addExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('allmodules/sellers/delete-extra-label/{id}', 'AllmodulesController', 'deleteExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('allmodules/sellers/export-replaced', 'AllmodulesController', 'exportReplacedRolls', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/sellers/{id}/roll-labels-ajax', 'AllmodulesController', 'ajaxSellerRollLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/sellers/next-roll-codes', 'AllmodulesController', 'ajaxNextFabricRollCodes', ['AuthMiddleware']);
        $this->get('api/sellers/{id}/rolls', 'AllmodulesController', 'ajaxSellerRolls', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/allmodules/sellers/check-duplicate', 'AllmodulesController', 'ajaxCheckSupplierDuplicate', ['AuthMiddleware']);
        $this->get('api/allmodules/sellers/check-deletable', 'AllmodulesController', 'ajaxCheckSupplierDeletable', ['AuthMiddleware']);

        // Unified Common Purchases (Fabric Rolls, Third-Party Products & Own Production Products)
        $this->get('allmodules/purchases', 'AllmodulesController', 'productionPurchaseList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('allmodules/purchases/create', 'AllmodulesController', 'createProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->post('allmodules/purchases/store', 'AllmodulesController', 'storeProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->get('allmodules/purchases/view/{id}', 'AllmodulesController', 'viewProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('allmodules/purchases/logs/{id}', 'AllmodulesController', 'ajaxPurchaseBillLogs', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->post('allmodules/purchases/verify-accounts/{id}', 'AllmodulesController', 'verifyPurchaseAccounts', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->get('allmodules/purchases/edit/{id}', 'AllmodulesController', 'editProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->post('allmodules/purchases/update/{id}', 'AllmodulesController', 'updateProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->post('allmodules/purchases/delete/{id}', 'AllmodulesController', 'deleteProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'delete']]);
        $this->get('allmodules/purchases/print/{id}', 'AllmodulesController', 'printProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('allmodules/purchases/roll-qr/{id}', 'AllmodulesController', 'productionPurchaseRollQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('allmodules/purchases/supply-qr/{id}', 'AllmodulesController', 'productionPurchaseSupplyQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->post('allmodules/purchases/supply-inward/{id}', 'AllmodulesController', 'productionPurchaseSupplyInward', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);

        // Third-Party Product Mapping Module
        $this->get('allmodules/purchases/mapping', 'AllmodulesController', 'thirdPartyMappingList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('allmodules/purchases/mapping/create', 'AllmodulesController', 'createThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('allmodules/purchases/mapping/create', 'AllmodulesController', 'storeThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('allmodules/purchases/mapping/draft/{id}', 'AllmodulesController', 'viewThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('allmodules/purchases/mapping/draft/{id}/view', 'AllmodulesController', 'viewThirdPartyMappingDraftReadOnly', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->post('allmodules/purchases/mapping/draft/{id}/add-product', 'AllmodulesController', 'addThirdPartyMappingDraftProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('allmodules/purchases/mapping/draft/{id}/delete', 'AllmodulesController', 'deleteThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('allmodules/purchases/mapping/draft/item/{id}/delete', 'AllmodulesController', 'deleteThirdPartyMappingDraftItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('allmodules/purchases/mapping/draft/{id}/move', 'AllmodulesController', 'moveThirdPartyMappingDraftToPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/draft/item/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyMappingDraftQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/draft/{id}/generate-all-qr', 'AllmodulesController', 'generateAllThirdPartyMappingDraftQrs', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('api/purchases/mapping/add-to-stock', 'AllmodulesController', 'ajaxAddMappingStockToInventory', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/add-to-stock', 'AllmodulesController', 'ajaxAddMappingStockToInventory', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);

        // Shop & Production Cash & Cash Flow Management
        $this->get('allmodules/cash-flow', 'CashFlowController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'view']]);
        $this->get('shop/cash-flow', 'CashFlowController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'view']]);
        $this->post('allmodules/cash-flow/open-shift', 'CashFlowController', 'openShift', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'create']]);
        $this->post('allmodules/cash-flow/top-up', 'CashFlowController', 'topUp', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'create']]);
        $this->post('allmodules/cash-flow/expense', 'CashFlowController', 'recordExpense', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'create']]);
        $this->post('allmodules/cash-flow/bank-drop', 'CashFlowController', 'bankDrop', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'create']]);
        $this->post('allmodules/cash-flow/close-shift', 'CashFlowController', 'closeShift', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'delete']]);
        $this->get('allmodules/cash-flow/export-csv', 'CashFlowController', 'exportCsv', ['AuthMiddleware', ['PermissionMiddleware', 'cash_flow', 'view']]);
        $this->get('allmodules/purchases/mapping/bill/{id}', 'AllmodulesController', 'thirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/bill/variant/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyBillVariantQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/bill/item/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyBillItemQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/bill/{id}/generate-all-qr', 'AllmodulesController', 'generateAllThirdPartyBillVariantsQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('allmodules/purchases/mapping/edit/{id}', 'AllmodulesController', 'thirdPartyMappingEdit', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/save', 'AllmodulesController', 'saveThirdPartyMapping', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/edit-roll', 'AllmodulesController', 'ajaxUpdateFabricRoll', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/add-product', 'AllmodulesController', 'ajaxAddBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('allmodules/purchases/mapping/delete-product/{id}', 'AllmodulesController', 'ajaxDeleteBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('allmodules/purchases/mapping/unmap/{id}', 'AllmodulesController', 'unmapPurchaseItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('allmodules/purchases/mapping/unmap-bill/{id}', 'AllmodulesController', 'unmapPurchaseBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->get('api/purchases/mapping/item/{id}', 'AllmodulesController', 'ajaxGetThirdPartyItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('allmodules/purchases/mapping/catalog', 'AllmodulesController', 'thirdPartyCatalogList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('allmodules/purchases/mapping/catalog/create', 'AllmodulesController', 'createThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('allmodules/purchases/mapping/catalog/store', 'AllmodulesController', 'storeThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('allmodules/purchases/mapping/catalog/edit/{id}', 'AllmodulesController', 'editThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/catalog/update/{id}', 'AllmodulesController', 'updateThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('allmodules/purchases/mapping/catalog/delete/{id}', 'AllmodulesController', 'deleteThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);

        // Purchased Stock Sub-Module (Fabric Rolls, Raw Materials, Third-Party)
        $purchasedStockViewPerms   = [['qr_generation', 'view'], ['catalog', 'view'], ['production_purchases', 'view']];
        $purchasedStockEditPerms   = [['qr_generation', 'create'], ['qr_generation', 'edit'], ['catalog', 'edit'], ['production_purchases', 'edit']];
        $purchasedStockDeletePerms = [['qr_generation', 'delete'], ['catalog', 'delete'], ['production_purchases', 'delete']];
        $this->get('allmodules/purchased-stock', 'AllmodulesController', 'purchasedStockIndex', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockViewPerms]]);
        $this->get('allmodules/purchased-stock/view/{id}', 'AllmodulesController', 'purchasedStockView', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockViewPerms]]);
        $this->get('allmodules/purchased-stock/edit/{id}', 'AllmodulesController', 'purchasedStockEdit', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockEditPerms]]);
        $this->post('allmodules/purchased-stock/edit/{id}', 'AllmodulesController', 'purchasedStockUpdate', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockEditPerms]]);
        $this->get('allmodules/purchased-stock/details/{id}', 'AllmodulesController', 'ajaxPurchasedStockDetails', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockViewPerms]]);
        $this->post('allmodules/purchased-stock/update/{id}', 'AllmodulesController', 'ajaxPurchasedStockUpdate', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockEditPerms]]);
        $this->post('allmodules/purchased-stock/delete/{id}', 'AllmodulesController', 'ajaxPurchasedStockDelete', ['AuthMiddleware', ['PermissionMiddleware', $purchasedStockDeletePerms]]);

        // Common Purchases Clean Aliases
        $this->get('purchases', 'AllmodulesController', 'productionPurchaseList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('purchases/create', 'AllmodulesController', 'createProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->post('purchases/store', 'AllmodulesController', 'storeProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'create']]);
        $this->get('purchases/view/{id}', 'AllmodulesController', 'viewProductionPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->get('purchases/logs/{id}', 'AllmodulesController', 'ajaxPurchaseBillLogs', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'view']]);
        $this->post('purchases/verify-accounts/{id}', 'AllmodulesController', 'verifyPurchaseAccounts', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchases', 'edit']]);
        $this->get('purchases/mapping', 'AllmodulesController', 'thirdPartyMappingList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('purchases/mapping/create', 'AllmodulesController', 'createThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/create', 'AllmodulesController', 'storeThirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('purchases/mapping/draft/{id}', 'AllmodulesController', 'viewThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('purchases/mapping/draft/{id}/view', 'AllmodulesController', 'viewThirdPartyMappingDraftReadOnly', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->post('purchases/mapping/draft/{id}/add-product', 'AllmodulesController', 'addThirdPartyMappingDraftProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/draft/{id}/delete', 'AllmodulesController', 'deleteThirdPartyMappingDraft', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/draft/item/{id}/delete', 'AllmodulesController', 'deleteThirdPartyMappingDraftItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/draft/{id}/move', 'AllmodulesController', 'moveThirdPartyMappingDraftToPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/draft/item/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyMappingDraftQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/draft/{id}/generate-all-qr', 'AllmodulesController', 'generateAllThirdPartyMappingDraftQrs', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('purchases/mapping/bill/{id}', 'AllmodulesController', 'thirdPartyMappingBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/bill/variant/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyBillVariantQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/bill/item/{id}/generate-qr', 'AllmodulesController', 'generateThirdPartyBillItemQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/bill/{id}/generate-all-qr', 'AllmodulesController', 'generateAllThirdPartyBillVariantsQr', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->get('purchases/mapping/edit/{id}', 'AllmodulesController', 'thirdPartyMappingEdit', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/save', 'AllmodulesController', 'saveThirdPartyMapping', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/edit-roll', 'AllmodulesController', 'ajaxUpdateFabricRoll', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/add-product', 'AllmodulesController', 'ajaxAddBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/delete-product/{id}', 'AllmodulesController', 'ajaxDeleteBillMappingProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/unmap/{id}', 'AllmodulesController', 'unmapPurchaseItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->post('purchases/mapping/unmap-bill/{id}', 'AllmodulesController', 'unmapPurchaseBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);
        $this->get('purchases/mapping/catalog', 'AllmodulesController', 'thirdPartyCatalogList', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'view']]);
        $this->get('purchases/mapping/catalog/create', 'AllmodulesController', 'createThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->post('purchases/mapping/catalog/store', 'AllmodulesController', 'storeThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'create']]);
        $this->get('purchases/mapping/catalog/edit/{id}', 'AllmodulesController', 'editThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/catalog/update/{id}', 'AllmodulesController', 'updateThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'edit']]);
        $this->post('purchases/mapping/catalog/delete/{id}', 'AllmodulesController', 'deleteThirdPartyCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'production_purchase_mapping', 'delete']]);

        // Production Billing
        $this->get('allmodules/billing', 'AllmodulesBillingController', 'billList', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('allmodules/billing/move-to-batch/{id}', 'AllmodulesBillingController', 'moveBillToBatchForm', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->post('allmodules/billing/move-to-batch/{id}', 'AllmodulesBillingController', 'createBillDispatchBatch', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->get('allmodules/billing/create', 'AllmodulesBillingController', 'createBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->post('allmodules/billing/create', 'AllmodulesBillingController', 'createBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->post('allmodules/billing/store', 'AllmodulesBillingController', 'storeBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'create']]);
        $this->get('allmodules/billing/view/{id}', 'AllmodulesBillingController', 'viewBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('allmodules/billing/edit/{id}', 'AllmodulesBillingController', 'editBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'edit']]);
        $this->post('allmodules/billing/update/{id}', 'AllmodulesBillingController', 'updateBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'edit']]);
        $this->post('allmodules/billing/delete/{id}', 'AllmodulesBillingController', 'deleteBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'delete']]);
        $this->get('allmodules/billing/print/{id}', 'AllmodulesBillingController', 'printBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('api/allmodules/billing/search', 'AllmodulesBillingController', 'searchInventory', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('api/allmodules/billing/batch-items', 'AllmodulesBillingController', 'getBatchItemsJson', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('api/allmodules/billing/customer-search', 'AllmodulesBillingController', 'searchCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);
        $this->get('api/allmodules/billing/customer-history', 'AllmodulesBillingController', 'customerHistory', ['AuthMiddleware', ['PermissionMiddleware', 'production_billing', 'view']]);

        // Production Customer Returns & Exchanges
        $this->get('allmodules/returns', 'AllmodulesReturnController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->get('allmodules/returns/create', 'AllmodulesReturnController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->get('allmodules/returns/lookup', 'AllmodulesReturnController', 'ajaxLookupBill', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->post('allmodules/returns/store', 'AllmodulesReturnController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->post('allmodules/returns/triage-item', 'AllmodulesReturnController', 'triageItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->post('allmodules/returns/bulk-triage', 'AllmodulesReturnController', 'bulkTriage', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'create']]);
        $this->get('allmodules/returns/view/{id}', 'AllmodulesReturnController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);
        $this->get('allmodules/returns/print/{id}', 'AllmodulesReturnController', 'print', ['AuthMiddleware', ['PermissionMiddleware', 'production_returns', 'view']]);

        // Inter-Module Stock Requirements & Requisitions Flow
        $this->get('allmodules/stock-requests', 'AllmodulesStockRequestController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('allmodules/stock-requests/create', 'AllmodulesStockRequestController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->post('allmodules/stock-requests/store', 'AllmodulesStockRequestController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->get('api/allmodules/stock-requests/variant-stock', 'AllmodulesStockRequestController', 'ajaxGetStock', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('api/allmodules/stock-requests/scan-product', 'AllmodulesStockRequestController', 'ajaxScanProduct', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('allmodules/stock-requests/view/{id}', 'AllmodulesStockRequestController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->post('allmodules/stock-requests/receive/{id}', 'AllmodulesStockRequestController', 'receiveBySendArea', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('allmodules/stock-requests/send-stocks/{id}', 'AllmodulesStockRequestController', 'sendStocks', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('allmodules/stock-requests/confirm-received/{id}', 'AllmodulesStockRequestController', 'confirmReceived', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);
        $this->post('allmodules/stock-requests/update-status/{id}', 'AllmodulesStockRequestController', 'updateStatus', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'edit']]);

        // Stock Requirements Aliases
        $this->get('allmodules/stock-requirements', 'AllmodulesStockRequestController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'view']]);
        $this->get('allmodules/stock-requirements/create', 'AllmodulesStockRequestController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        $this->post('allmodules/stock-requirements/store', 'AllmodulesStockRequestController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_stock_requests', 'create']]);
        // ==========================================
        // GOODS INWARD (GRN) & PRODUCTION INWARD WORKSTATION
        // ==========================================
        $this->get('allmodules/inwards', 'InwardController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('allmodules/inwards/create', 'InwardController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('allmodules/inwards/store', 'InwardController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('allmodules/inwards/view/{id}', 'InwardController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->post('allmodules/inwards/verify/{id}', 'InwardController', 'verifyInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('allmodules/inwards/label/{id}', 'InwardController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('allmodules/inwards/gatepass/{id}', 'InwardController', 'printOutwardGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Warehouse Inward scheme compatibility aliases
        $this->get('warehouse/dashboard', 'AllmodulesController', 'dashboard', ['AuthMiddleware']);
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
        $this->post('api/allmodules/inward/scan', 'InwardController', 'ajaxScanInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->post('api/allmodules/inward/crosscheck-scan', 'InwardController', 'ajaxCrossCheckScan', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('api/allmodules/inward/lookup/{code}', 'InwardController', 'ajaxLookupBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('api/allmodules/inward/batch-history/{id}', 'InwardController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Production Reports & CSV / Excel Export (Legacy forward to ExportController)
        $this->get('allmodules/reports/export', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->post('allmodules/reports/export', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);

        // Production Fabric & Roll Damages
        $this->get('allmodules/damages', 'AllmodulesController', 'damageList', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->get('api/allmodules/damages/scan-roll', 'AllmodulesController', 'ajaxLookupDamageRoll', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->get('api/allmodules/damages/scan-roll-edit', 'AllmodulesController', 'ajaxLookupEditDamageRoll', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('allmodules/damages/reasons/store', 'AllmodulesController', 'storeDamageReason', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->post('allmodules/damages/store', 'AllmodulesController', 'storeDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->get('allmodules/damages/view/{id}', 'AllmodulesController', 'viewDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->post('allmodules/damages/update/{id}', 'AllmodulesController', 'updateDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('allmodules/damages/resolve/{id}', 'AllmodulesController', 'resolveDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('allmodules/damages/delete/{id}', 'AllmodulesController', 'deleteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'delete']]);

        // Production Catalog
        $this->get('allmodules/catalog', 'AllmodulesController', 'catalogList', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('allmodules/catalog/create', 'AllmodulesController', 'createCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->post('allmodules/catalog/store', 'AllmodulesController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->get('allmodules/catalog/view/{id}', 'AllmodulesController', 'viewCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('allmodules/catalog/edit/{id}', 'AllmodulesController', 'editCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('allmodules/catalog/update/{id}', 'AllmodulesController', 'updateCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('allmodules/catalog/delete/{id}', 'AllmodulesController', 'deleteCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'delete']]);

        // Product Variants & QR Management
        $this->get('allmodules/variants', 'VariantController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('allmodules/variants/reports', 'VariantController', 'reports', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('allmodules/variants/create', 'VariantController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'create']]);
        $this->post('allmodules/variants/store', 'VariantController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'create']]);
        $this->get('allmodules/variants/view/{id}', 'VariantController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->get('allmodules/variants/edit/{id}', 'VariantController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->post('allmodules/variants/update/{id}', 'VariantController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->post('allmodules/variants/toggle/{id}', 'VariantController', 'toggle', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'edit']]);
        $this->get('allmodules/variants/qr-label/{id}', 'VariantController', 'printQrLabel', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);
        $this->post('allmodules/variants/print-labels', 'VariantController', 'printBatchQrLabels', ['AuthMiddleware', ['PermissionMiddleware', 'variants', 'view']]);

        // QR Code Generation Module (Manual & Catalog/Variant Modes)
        $this->get('allmodules/qr-generations', 'QrGenerationController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->get('allmodules/qr-generations/create', 'QrGenerationController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/store-manual', 'QrGenerationController', 'storeManual', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/store-catalog', 'QrGenerationController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/store-purchase', 'QrGenerationController', 'storePurchaseBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/store-stitching', 'QrGenerationController', 'storeStitchingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/add-to-stock/{id}', 'QrGenerationController', 'addToStock', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->get('allmodules/qr-generations/view/{id}', 'QrGenerationController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->get('allmodules/qr-generations/edit/{id}', 'QrGenerationController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->post('allmodules/qr-generations/edit/{id}', 'QrGenerationController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'create']]);
        $this->get('allmodules/qr-generations/print/{id}', 'QrGenerationController', 'printLabels', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'view']]);
        $this->post('allmodules/qr-generations/delete/{id}', 'QrGenerationController', 'delete', ['AuthMiddleware', ['PermissionMiddleware', 'qr_generation', 'delete']]);

        // Variant & Design AJAX APIs
        $variantViewPerms = [['variants', 'view'], ['catalog', 'view'], ['qr_generation', 'view'], ['production_billing', 'create']];
        $designCreatePerms = [['variants', 'create'], ['variants', 'edit'], ['catalog', 'create'], ['catalog', 'edit']];
        $this->get('api/variants/catalogs', 'VariantController', 'ajaxCatalogs', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->get('api/variants/preview-sku', 'VariantController', 'ajaxPreviewSku', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->get('api/variants/check-duplicate', 'VariantController', 'ajaxCheckDuplicate', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->get('api/variants/resolve-qr', 'VariantController', 'ajaxResolveQr', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->post('api/variants/resolve-qr', 'VariantController', 'ajaxResolveQr', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->get('api/designs', 'VariantController', 'ajaxDesignsList', ['AuthMiddleware', ['PermissionMiddleware', $variantViewPerms]]);
        $this->post('api/designs/quick-add', 'VariantController', 'ajaxQuickAddDesign', ['AuthMiddleware', ['PermissionMiddleware', $designCreatePerms]]);

        // Production Programs
        $this->get('allmodules/programs', 'AllmodulesController', 'programList', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->get('allmodules/programs/create', 'AllmodulesController', 'createProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->post('allmodules/programs/store', 'AllmodulesController', 'storeProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->get('allmodules/programs/edit/{id}', 'AllmodulesController', 'editProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('allmodules/programs/update/{id}', 'AllmodulesController', 'updateProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->get('allmodules/programs/view/{id}', 'AllmodulesController', 'viewProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->post('allmodules/programs/approve/{id}', 'AllmodulesController', 'approveProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'approve']]);
        $this->post('allmodules/programs/status/{id}', 'AllmodulesController', 'updateProgramStatus', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('allmodules/programs/delete/{id}', 'AllmodulesController', 'deleteProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'delete']]);

        // Cutting
        $this->get('allmodules/cutting', 'AllmodulesController', 'cuttingList', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('allmodules/cutting/view/{id}', 'AllmodulesController', 'viewCuttingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('allmodules/cutting/create', 'AllmodulesController', 'createCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->post('allmodules/cutting/store', 'AllmodulesController', 'storeCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->get('allmodules/cutting/edit/{id}', 'AllmodulesController', 'editCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('allmodules/cutting/update/{id}', 'AllmodulesController', 'updateCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('allmodules/cutting/delete/{id}', 'AllmodulesController', 'deleteCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'delete']]);
        $this->get('api/programs/{id}/details', 'AllmodulesController', 'ajaxProgramDetails', ['AuthMiddleware']);

        // Wastage Selling
        $this->get('allmodules/wastage', 'AllmodulesController', 'wastageList', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);
        $this->post('allmodules/wastage/store', 'AllmodulesController', 'storeWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'create']]);
        $this->post('allmodules/wastage/update/{id}', 'AllmodulesController', 'updateWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'edit']]);
        $this->post('allmodules/wastage/delete/{id}', 'AllmodulesController', 'deleteWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'delete']]);
        $this->get('allmodules/wastage/receipt/{id}', 'AllmodulesController', 'wastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);

        // Stitching
        $this->get('allmodules/stitching', 'AllmodulesController', 'stitchingList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('allmodules/stitching/view/{id}', 'AllmodulesController', 'viewStitchingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('allmodules/stitching/create', 'AllmodulesController', 'createStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->post('allmodules/stitching/store', 'AllmodulesController', 'storeStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->get('allmodules/stitching/edit/{id}', 'AllmodulesController', 'editStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('allmodules/stitching/update/{id}', 'AllmodulesController', 'updateStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('allmodules/stitching/delete/{id}', 'AllmodulesController', 'deleteStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'delete']]);

        // Stitching Wastage Selling
        $this->get('allmodules/stitching-wastage', 'AllmodulesController', 'stitchingWastageList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);
        $this->post('allmodules/stitching-wastage/store', 'AllmodulesController', 'storeStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'create']]);
        $this->post('allmodules/stitching-wastage/update/{id}', 'AllmodulesController', 'updateStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'edit']]);
        $this->post('allmodules/stitching-wastage/delete/{id}', 'AllmodulesController', 'deleteStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'delete']]);
        $this->get('allmodules/stitching-wastage/receipt/{id}', 'AllmodulesController', 'stitchingWastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);

        // Production Expenses Management (Unit 1 & Unit 2)
        $this->get('allmodules/expenses', 'AllmodulesController', 'expensesList', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->get('allmodules/expenses/view/{id}', 'AllmodulesController', 'viewExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('allmodules/expenses/store', 'AllmodulesController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->post('allmodules/expenses/update/{id}', 'AllmodulesController', 'updateExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'edit']]);
        $this->post('allmodules/expenses/delete/{id}', 'AllmodulesController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'delete']]);
        $this->get('allmodules/expenses/receipt/{id}', 'AllmodulesController', 'expenseReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('api/allmodules/expenses/quick-add-item', 'AllmodulesController', 'ajaxQuickAddExpenseItem', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->post('api/allmodules/expenses/quick-add-category', 'AllmodulesController', 'ajaxQuickAddExpenseCategory', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->post('api/allmodules/expenses/quick-add-unit', 'AllmodulesController', 'ajaxQuickAddUnit', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->get('api/allmodules/expenses/items', 'AllmodulesController', 'ajaxGetExpenseItems', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);

        // QC
        $this->get('allmodules/qc', 'AllmodulesController', 'qcList', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('allmodules/qc/view/{id}', 'AllmodulesController', 'viewQCBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('allmodules/qc/create', 'AllmodulesController', 'createQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->post('allmodules/qc/store', 'AllmodulesController', 'storeQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->post('allmodules/qc/ajax-add-inspector', 'AllmodulesController', 'ajaxAddQcInspector', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->get('allmodules/qc/inspect/{id}', 'AllmodulesController', 'inspectQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('allmodules/qc/update/{id}', 'AllmodulesController', 'updateQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('allmodules/qc/save-ajax/{id}', 'AllmodulesController', 'saveQCAjax', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('allmodules/qc/delete/{id}', 'AllmodulesController', 'deleteQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'delete']]);

        // Production Inventory (Post-QC Passed Inventory)
        $this->get('allmodules/inventory', 'AllmodulesController', 'productionInventory', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->post('allmodules/inventory/update-price', 'AllmodulesController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('api/allmodules/inventory/update-price', 'AllmodulesController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->get('allmodules/inventory/batch/{id}', 'AllmodulesController', 'productionInventoryBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->get('allmodules/inventory/supply/{id}', 'AllmodulesController', 'productionInventorySupplyBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->post('allmodules/inventory/delete-piece', 'AllmodulesController', 'deleteProductionInventoryPiece', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);
        $this->post('allmodules/inventory/supply/update-stock', 'AllmodulesController', 'updateProductionInventorySupplyStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('allmodules/inventory/supply/delete', 'AllmodulesController', 'deleteProductionInventorySupplyStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);
        $this->post('allmodules/inventory/qc/update-stock', 'AllmodulesController', 'updateProductionInventoryQcStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'edit']]);
        $this->post('allmodules/inventory/qc/delete', 'AllmodulesController', 'deleteProductionInventoryQcStock', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);

        // Batch & Outward
        $this->get('allmodules/batches', 'AllmodulesController', 'batchList', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('allmodules/batches/create', 'AllmodulesController', 'createBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->post('allmodules/batches/store', 'AllmodulesController', 'storeBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->get('allmodules/batches/view/{id}', 'AllmodulesController', 'viewBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('allmodules/batches/bill/{id}', 'AllmodulesController', 'printBatchBill', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('allmodules/batches/invoice/{id}', 'AllmodulesController', 'printBatchInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->post('allmodules/batches/approve/{id}', 'AllmodulesController', 'approveBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'approve']]);
        $this->get('allmodules/batches/label/{id}', 'AllmodulesController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'print']]);
        $this->get('allmodules/batches/view-ajax/{id}', 'AllmodulesController', 'batchDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->post('allmodules/batches/update/{id}', 'AllmodulesController', 'updateBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'edit']]);
        $this->post('allmodules/batches/delete/{id}', 'AllmodulesController', 'deleteBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'delete']]);
        $this->get('allmodules/outward', 'AllmodulesController', 'outwardList', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);
        $this->post('allmodules/outward/store', 'AllmodulesController', 'storeOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->post('allmodules/outward/dispatch/{id}', 'AllmodulesController', 'dispatchOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->get('allmodules/outward/view-ajax/{id}', 'AllmodulesController', 'outwardDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);
        $this->post('allmodules/outward/update/{id}', 'AllmodulesController', 'updateOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'edit']]);
        $this->post('allmodules/outward/delete/{id}', 'AllmodulesController', 'deleteOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'delete']]);

        // Rework from Warehouse
        $this->get('allmodules/warehouse-rework', 'AllmodulesController', 'warehouseReworkList', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('allmodules/warehouse-rework/store', 'AllmodulesController', 'storeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'create']]);
        $this->post('allmodules/warehouse-rework/status/{id}', 'AllmodulesController', 'updateWarehouseReworkStatus', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('allmodules/warehouse-rework/complete/{id}', 'AllmodulesController', 'completeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('allmodules/warehouse-rework/redispatch/{id}', 'AllmodulesController', 'redispatchWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'dispatch']]);
        $this->get('allmodules/warehouse-rework/view-ajax/{id}', 'AllmodulesController', 'viewWarehouseReworkAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('allmodules/warehouse-rework/delete/{id}', 'AllmodulesController', 'deleteWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'delete']]);
        $this->get('allmodules/warehouse-rework/lookup-batch/{batchId}', 'AllmodulesController', 'lookupBatchVariantsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);




        // Production Reports
        $this->get('allmodules/reports', 'AllmodulesController', 'reports', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);
        $this->get('allmodules/reports/export', 'ExportController', 'download', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);

        // ==========================================
        // PRODUCTION HSN CONFIG MODULE
        // ==========================================
        $this->get('allmodules/hsn-config', 'AllmodulesHsnController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'view']]);
        $this->get('allmodules/hsn-config/create', 'AllmodulesHsnController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'create']]);
        $this->post('allmodules/hsn-config/store', 'AllmodulesHsnController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'create']]);
        $this->get('allmodules/hsn-config/view/{id}', 'AllmodulesHsnController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'view']]);
        $this->get('allmodules/hsn-config/edit/{id}', 'AllmodulesHsnController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'edit']]);
        $this->post('allmodules/hsn-config/update/{id}', 'AllmodulesHsnController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'edit']]);
        $this->post('allmodules/hsn-config/delete/{id}', 'AllmodulesHsnController', 'delete', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'delete']]);
        $this->post('allmodules/hsn-config/import', 'AllmodulesHsnController', 'importBulk', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'create']]);
        $this->post('allmodules/hsn-config/seed-master', 'AllmodulesHsnController', 'seedMaster', ['AuthMiddleware', ['PermissionMiddleware', 'production_hsn', 'create']]);


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
        $this->post('system/settings/presets/save', 'SystemController', 'saveThemePreset', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'edit']]);
        $this->post('system/settings/presets/delete', 'SystemController', 'deleteThemePreset', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'edit']]);

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
        $this->get('admin/concerns', 'ConcernController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->get('admin/concerns/create', 'ConcernController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/store', 'ConcernController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->get('admin/concerns/edit/{id}', 'ConcernController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/update/{id}', 'ConcernController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/assign-staff/{id}', 'ConcernController', 'assignStaff', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/quick-create-staff', 'ConcernController', 'quickCreateStaff', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->get('admin/concerns/modules/{id}', 'ConcernController', 'modules', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/modules/{id}', 'ConcernController', 'updateModules', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->get('admin/concerns/toggle/{id}', 'ConcernController', 'toggleStatus', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->post('admin/concerns/toggle/{id}', 'ConcernController', 'toggleStatus', ['AuthMiddleware', ['PermissionMiddleware', 'settings', 'view']]);
        $this->get('system/concerns/switch/{id}', 'ConcernController', 'switch', ['AuthMiddleware']);
    }
}
