<?php
/**
 * Permission Middleware
 * Checks if user has required module + action permission.
 */

class PermissionMiddleware {
    /**
     * Handle the permission check.
     *
     * @param string|array $module Module name (e.g., 'production') or list of [module, action] pairs (any match grants access)
     * @param string|null $action Action name (e.g., 'view', 'create', 'edit', 'delete', 'approve', 'print', 'export')
     */
    public function handle(string|array $module, ?string $action = null): void {
        if (is_array($module)) {
            $labels = [];
            foreach ($module as $pair) {
                if (!is_array($pair) || count($pair) < 2) {
                    continue;
                }
                [$mod, $act] = $pair;
                if (hasPermission((string)$mod, (string)$act)) {
                    return;
                }
                $labels[] = ucwords(str_replace('_', ' ', (string)$mod)) . ' (' . strtolower((string)$act) . ')';
            }

            $joined = !empty($labels) ? implode(' or ', array_unique($labels)) : 'this module';
            if ($this->isAjax()) {
                Response::json([
                    'success' => false,
                    'error'   => "Access restricted: You do not have permission for {$joined}.",
                    'message' => "Access restricted: You do not have permission for {$joined}.",
                ], 403);
            }
            Response::error(403, "Your assigned role does not have permission for {$joined}.");
        }

        $action = (string)($action ?? 'view');
        if (!hasPermission($module, $action)) {
            // Allow access to seller edit mode if user has edit permission
            if ($module === 'wh_sellers' && $action === 'create') {
                $isEditReq = (!empty($_GET['mode']) && $_GET['mode'] === 'edit')
                    || !empty($_GET['seller_id'])
                    || (!empty($_POST['edit_supplier_mode']) && $_POST['edit_supplier_mode'] == '1')
                    || !empty($_POST['existing_supplier_id']);
                if ($isEditReq && hasPermission('wh_sellers', 'edit')) {
                    return;
                }
            }

            // Allow access to produce/adjust QR codes if user has create permission
            if ($module === 'wh_labels' && $action === 'edit' && hasPermission('wh_labels', 'create')) {
                return;
            }

            // Allow access to edit QR generation if user has create permission
            if ($module === 'qr_generation' && $action === 'edit' && hasPermission('qr_generation', 'create')) {
                return;
            }

            // Allow batch approval if user has create/edit permission on batches, production billing, or is admin
            if ($module === 'batches' && $action === 'approve' && (hasPermission('batches', 'create') || hasPermission('batches', 'edit') || hasPermission('production_billing', 'create') || isAdmin())) {
                return;
            }

            $readableModule = ucwords(str_replace('_', ' ', $module));
            $readableAction = strtolower($action);

            // For AJAX requests, return JSON error
            if ($this->isAjax()) {
                Response::json([
                    'success' => false,
                    'error'   => "Access restricted: You do not have permission to {$readableAction} in {$readableModule}."
                ], 403);
            }
            
            Response::error(403, "Your assigned role does not have permission to {$readableAction} in {$readableModule}.");
        }
    }

    /**
     * Check if request is AJAX or expects JSON.
     */
    private function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_starts_with(trim((string)($_GET['url'] ?? ''), '/'), 'api/');
    }
}
