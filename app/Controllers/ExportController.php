<?php
/**
 * Export Controller — Handles Dedicated Data Export Center, Multi-Module CSV Streaming, and Printable Reports.
 */

require_once BASE_PATH . '/app/Services/ExportService.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';

use App\Services\ExportService;

class ExportController {

    /**
     * Dedicated Data Export Hub Page
     */
    public function index(): void {
        $activeFacility = class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility() : null;

        // Current period defaults
        $curMonth = (int)date('n');
        $curYear  = (int)date('Y');
        $firstDayOfMonth = date('Y-m-01');
        $today = date('Y-m-d');

        // Available Export Modules Metadata
        $modules = [
            // PROCUREMENT & MATERIALS
            'purchases' => [
                'name' => 'Purchases',
                'desc' => 'Procurement orders across fabric rolls, raw materials, third-party goods, supplier invoices & valuations',
                'icon' => 'bi bi-bag-check',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'rolls' => [
                'name' => 'Fabric Rolls & Inwards',
                'desc' => 'Individual fabric roll serials, barcodes, colors, meters, kg & per-kg valuation',
                'icon' => 'bi bi-stack',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'raw_materials' => [
                'name' => 'Raw Materials Supply & Stock',
                'desc' => 'Procured yarn counts, sewing threads, trims, accessories, pricing & tax breakdowns',
                'icon' => 'bi bi-boxes',
                'color' => 'warning',
                'category' => 'Procurement & Materials'
            ],
            'third_party_products' => [
                'name' => 'Third-Party Products & Mapping',
                'desc' => 'Ready-to-sell vendor products, retail catalog mapping, 3-tier taxonomy, sizes, colors, MRP & SP',
                'icon' => 'bi bi-shop',
                'color' => 'info',
                'category' => 'Procurement & Materials'
            ],
            'qr_generations' => [
                'name' => 'QR Generations',
                'desc' => 'QR generation batches across Third-Party, Stitching & QC sources, brand/category, MRP, SP & stock-added status',
                'icon' => 'bi bi-qr-code-scan',
                'color' => 'primary',
                'category' => 'Procurement & Materials'
            ],
            'suppliers' => [
                'name' => 'Sellers',
                'desc' => 'Registered vendors, mill profiles, contact details, GSTIN, payment terms & procurement volumes',
                'icon' => 'bi bi-person-lines-fill',
                'color' => 'secondary',
                'category' => 'Procurement & Materials'
            ],
            'goods_inward' => [
                'name' => 'Inward',
                'desc' => 'Warehouse & factory receipts, challan verification, received, accepted & rejected quantities',
                'icon' => 'bi bi-box-arrow-in-down',
                'color' => 'success',
                'category' => 'Procurement & Materials'
            ],

            // MANUFACTURING
            'programs' => [
                'name' => 'Programs',
                'desc' => 'Manufacturing targets, product styles, scheduled dates & planned volumes',
                'icon' => 'bi bi-kanban',
                'color' => 'info',
                'category' => 'Manufacturing'
            ],
            'cuttings' => [
                'name' => 'Cutting',
                'desc' => 'Cutting master logs, fabric consumption, layer yields & wastage kg',
                'icon' => 'bi bi-scissors',
                'color' => 'secondary',
                'category' => 'Manufacturing'
            ],
            'cutting_outwards' => [
                'name' => 'Cutting Outward',
                'desc' => 'Cutting batches outwarded to other concerns, piece counts, vehicle/driver & inward receipt status',
                'icon' => 'bi bi-truck',
                'color' => 'dark',
                'category' => 'Manufacturing'
            ],
            'stitching' => [
                'name' => 'Stitching',
                'desc' => 'Tailor & operator completions, stitched outputs, reworks & rejections',
                'icon' => 'bi bi-bezier',
                'color' => 'warning',
                'category' => 'Manufacturing'
            ],
            'qc' => [
                'name' => 'Quality Check',
                'desc' => 'Inspection audits, pass percentage yield, defects & rework logging',
                'icon' => 'bi bi-patch-check',
                'color' => 'success',
                'category' => 'Manufacturing'
            ],
            'damages' => [
                'name' => 'Damages',
                'desc' => 'Roll damages, defect logs, loss amounts, resolutions, debit notes & replacement rolls',
                'icon' => 'bi bi-exclamation-octagon',
                'color' => 'danger',
                'category' => 'Manufacturing'
            ],
            'damage_outwards' => [
                'name' => 'Damage Outward',
                'desc' => 'Damaged rolls/items outwarded to other concerns, qty/weight, vehicle/driver & inward receipt status',
                'icon' => 'bi bi-truck-flatbed',
                'color' => 'danger',
                'category' => 'Manufacturing'
            ],
            'catalog' => [
                'name' => 'Catalog',
                'desc' => 'Master finished garment styles, category hierarchy, HSN codes, default specs & active variant counts',
                'icon' => 'bi bi-grid-3x3-gap',
                'color' => 'primary',
                'category' => 'Manufacturing'
            ],
            'variants' => [
                'name' => 'Product Variants',
                'desc' => 'Garment sizes, colors, design names, Variant IDs, barcodes, MRP, selling price & cost per piece',
                'icon' => 'bi bi-tags',
                'color' => 'info',
                'category' => 'Manufacturing'
            ],

            // LOGISTICS & WAREHOUSING
            'outward' => [
                'name' => 'Outward & Dispatch',
                'desc' => 'Warehouse transfers, gate passes, transporter details, vehicle & LR logs',
                'icon' => 'bi bi-truck',
                'color' => 'primary',
                'category' => 'Logistics & Warehousing'
            ],
            'inventory' => [
                'name' => 'Inventory',
                'desc' => 'Real-time available stock levels by variant, style, color, size, location & stock states',
                'icon' => 'bi bi-layers',
                'color' => 'info',
                'category' => 'Logistics & Warehousing'
            ],
            'stock_ledger' => [
                'name' => 'Detailed Warehouse Stock Ledger',
                'desc' => 'Monthly stock-in/out history with QR, variant, size, color, design, and pricing details',
                'icon' => 'bi bi-arrow-left-right',
                'color' => 'success',
                'category' => 'Logistics & Warehousing'
            ],
            'stock_requests' => [
                'name' => 'Stock Request',
                'desc' => 'Warehouse requisition orders, priority levels, fulfillment statuses',
                'icon' => 'bi bi-box-arrow-in-down-right',
                'color' => 'info',
                'category' => 'Logistics & Warehousing'
            ],

            // SALES & FINANCE
            'billing' => [
                'name' => 'Billing / Pos',
                'desc' => 'Normal tax invoices, customer details, HSN codes, GST amounts & payment types (excludes direct bills)',
                'icon' => 'bi bi-receipt',
                'color' => 'primary',
                'category' => 'Sales & Finance'
            ],
            'direct_billing' => [
                'name' => 'Direct Sales Bills (Separated Invoices)',
                'desc' => 'Separated direct sales bills excluded from normal billing totals, client details, payment logs & line items',
                'icon' => 'bi bi-box-arrow-up-right',
                'color' => 'warning',
                'category' => 'Sales & Finance'
            ],
            'coupons' => [
                'name' => 'Coupons',
                'desc' => 'POS discount coupons, fixed/percent offers, usage limits, redeemed counts, expiry & status',
                'icon' => 'bi bi-ticket-perforated',
                'color' => 'primary',
                'category' => 'Sales & Finance'
            ],
            'returns' => [
                'name' => 'Returns & Exchanges',
                'desc' => 'Returned merchandise, condition inspection, refund modes & tax reversals',
                'icon' => 'bi bi-arrow-return-left',
                'color' => 'danger',
                'category' => 'Sales & Finance'
            ],
            'wastage' => [
                'name' => 'Wastage Selling',
                'desc' => 'Cutting scrap & stitching fabric waste monetized sales and buyer invoices',
                'icon' => 'bi bi-recycle',
                'color' => 'success',
                'category' => 'Sales & Finance'
            ],
            'expenses' => [
                'name' => 'Expenses',
                'desc' => 'Plant maintenance, operating costs, category breakdown & settlement status',
                'icon' => 'bi bi-wallet2',
                'color' => 'warning',
                'category' => 'Sales & Finance'
            ],
            'cash_flow' => [
                'name' => 'Cash Management & Shifts',
                'desc' => 'Shop till shifts, opening float carry-forwards, POS cash inflows, petty expenses & bank deposits',
                'icon' => 'bi bi-cash-coin',
                'color' => 'success',
                'category' => 'Sales & Finance'
            ],

            // ASSETS & INFRASTRUCTURE
            'assets' => [
                'name' => 'Fixed Assets Register',
                'desc' => 'Plant machinery, asset codes, serial numbers, purchase value, current book value & condition',
                'icon' => 'bi bi-tools',
                'color' => 'primary',
                'category' => 'Assets & Infrastructure'
            ],
            'asset_maintenance' => [
                'name' => 'Asset Maintenance & Service Logs',
                'desc' => 'Servicing logs, downtime hours, technician info, parts replaced & maintenance expenditures',
                'icon' => 'bi bi-gear',
                'color' => 'secondary',
                'category' => 'Assets & Infrastructure'
            ],
        ];

        // Active business concerns & scoping (Admin sees all; Staff sees only allocated concerns)
        $isAdminUser = function_exists('isAdmin') && isAdmin();
        $concerns = $this->getScopedConcernsForExport();
        $showConcernFilter = $isAdminUser || count($concerns) > 1;
        $modules = $this->filterModulesForCurrentUser($modules);
        $activeConcern = function_exists('currentConcern') ? currentConcern() : null;

        $allowedConcernIds = array_values(array_unique(array_filter(array_map('intval', array_column($concerns, 'id')))));
        $whConcernSql = (!$isAdminUser && !empty($allowedConcernIds))
            ? ' AND w.concern_id IN (' . implode(',', array_fill(0, count($allowedConcernIds), '?')) . ')'
            : '';
        $puConcernSql = (!$isAdminUser && !empty($allowedConcernIds))
            ? ' AND pu.concern_id IN (' . implode(',', array_fill(0, count($allowedConcernIds), '?')) . ')'
            : '';
        $scopedParams = (!$isAdminUser && !empty($allowedConcernIds)) ? $allowedConcernIds : [];

        // Active production facilities and warehouses with concern association
        $facilities = Database::fetchAll("
            SELECT pu.id, pu.name, pu.code, pu.is_active, pu.concern_id, c.code AS concern_code, c.name AS concern_name 
            FROM production_units pu 
            LEFT JOIN concerns c ON c.id = pu.concern_id 
            WHERE pu.is_active = 1 {$puConcernSql}
            ORDER BY pu.name ASC
        ", $scopedParams);
        $warehouses = Database::fetchAll("
            SELECT w.id, w.name, w.code, w.is_active, w.concern_id, c.code AS concern_code, c.name AS concern_name 
            FROM warehouses w 
            LEFT JOIN concerns c ON c.id = w.concern_id 
            WHERE w.is_active = 1 {$whConcernSql}
            ORDER BY w.name ASC
        ", $scopedParams);

        Response::view('export.index', [
            'pageTitle'         => 'Data Export Center',
            'activeModule'      => 'export',
            'activeMenu'        => 'export-hub',
            'breadcrumb'        => [
                ['label' => 'Data Export Center']
            ],
            'modules'           => $modules,
            'concerns'          => $concerns,
            'activeConcern'     => $activeConcern,
            'isAdminUser'       => $isAdminUser,
            'showConcernFilter' => $showConcernFilter,
            'facilities'        => $facilities,
            'warehouses'       => $warehouses,
            'activeFacility'   => $activeFacility,
            'curMonth'         => $curMonth,
            'curYear'          => $curYear,
            'firstDayOfMonth'  => $firstDayOfMonth,
            'today'            => $today,
        ]);
    }

    /**
     * Process export download (CSV or Printable Summary)
     */
    public function download(): void {
        $periodType = trim((string)($_REQUEST['period_type'] ?? 'MONTH'));
        $format     = strtoupper(trim((string)($_REQUEST['format'] ?? 'CSV')));

        if ($periodType === 'MONTH') {
            $month = (int)($_REQUEST['selected_month'] ?? date('n'));
            $year  = (int)($_REQUEST['selected_year'] ?? date('Y'));
            $month = max(1, min(12, $month));
            $year  = max(2020, min(2035, $year));
            $startDate   = sprintf('%04d-%02d-01', $year, $month);
            $endDate     = date('Y-m-t', strtotime($startDate));
            $periodLabel = date('F Y', strtotime($startDate));
        } else {
            $startDate   = trim((string)($_REQUEST['date_from'] ?? date('Y-m-01')));
            $endDate     = trim((string)($_REQUEST['date_to'] ?? date('Y-m-d')));
            $periodLabel = date('d M Y', strtotime($startDate)) . ' to ' . date('d M Y', strtotime($endDate));
        }

        $allModules = [
            'purchases', 'rolls', 'raw_materials', 'third_party_products', 'qr_generations', 'suppliers', 'goods_inward',
            'programs', 'cuttings', 'cutting_outwards', 'stitching', 'qc', 'damages', 'damage_outwards', 'catalog', 'variants',
            'outward', 'inventory', 'stock_ledger', 'stock_requests',
            'billing', 'direct_billing', 'coupons', 'returns', 'wastage', 'expenses', 'cash_flow', 'assets', 'asset_maintenance'
        ];
        if (!function_exists('isAdmin') || !isAdmin()) {
            $allModules = array_keys($this->filterModulesForCurrentUser(array_flip($allModules)));
        }
        $selectedModules = !empty($_REQUEST['modules']) && is_array($_REQUEST['modules'])
            ? array_values(array_intersect($_REQUEST['modules'], $allModules))
            : $allModules;

        if (empty($selectedModules)) {
            $selectedModules = $allModules;
        }

        // Business Concerns Filtering
        $requestedConcernIds = $_REQUEST['concern_ids'] ?? $_REQUEST['concern_id'] ?? [];
        if (!is_array($requestedConcernIds)) {
            $requestedConcernIds = ($requestedConcernIds !== '' && $requestedConcernIds !== null && $requestedConcernIds != '0') ? [(int)$requestedConcernIds] : [];
        }
        $selectedConcernIds = array_values(array_unique(array_filter(array_map('intval', $requestedConcernIds), static function (int $id): bool {
            return $id > 0;
        })));

        if (!function_exists('isAdmin') || !isAdmin()) {
            $allowedConcernIds = array_values(array_unique(array_filter(array_map('intval', array_column($this->getScopedConcernsForExport(), 'id')))));
            if (!empty($allowedConcernIds)) {
                $selectedConcernIds = !empty($selectedConcernIds)
                    ? array_values(array_intersect($selectedConcernIds, $allowedConcernIds))
                    : $allowedConcernIds;
                if (empty($selectedConcernIds)) {
                    $selectedConcernIds = $allowedConcernIds;
                }
            }
        }

        $selectedConcerns = [];
        if (!empty($selectedConcernIds)) {
            $cIn = implode(',', array_fill(0, count($selectedConcernIds), '?'));
            $selectedConcerns = Database::fetchAll("SELECT * FROM concerns WHERE id IN ($cIn) ORDER BY code ASC", $selectedConcernIds) ?: [];
        }

        $activeFacility = class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility() : null;
        $requestedWarehouseIds = $_REQUEST['warehouse_ids'] ?? [];
        $selectedWarehouseIds = is_array($requestedWarehouseIds)
            ? array_values(array_unique(array_filter(array_map('intval', $requestedWarehouseIds), static function (int $id): bool {
                return $id > 0;
            })))
            : [];

        $data = ExportService::getReportData($startDate, $endDate, $selectedModules, null, $selectedWarehouseIds, $selectedConcernIds);

        if ($format === 'PRINT') {
            $printView = file_exists(BASE_PATH . '/views/export/print.php') ? 'export.print' : 'allmodules.reports.print';
            Response::viewOnly($printView, [
                'periodLabel'          => $periodLabel,
                'startDate'            => $startDate,
                'endDate'              => $endDate,
                'selectedModules'      => $selectedModules,
                'selectedWarehouseIds' => $selectedWarehouseIds,
                'selectedConcernIds'   => $selectedConcernIds,
                'selectedConcerns'     => $selectedConcerns,
                'data'                 => $data,
                'prodUnit'             => $activeFacility,
                'sellerCompany'        => SettingsService::getGroup('company'),
            ]);
            return;
        }

        ExportService::streamCsv($periodLabel, $startDate, $endDate, $data, $activeFacility, $selectedConcerns);
    }

    /**
     * AJAX endpoint: Return live per-module record counts for selected concern(s) and date range
     */
    public function ajaxConcernSummary(): void {
        $periodType = trim((string)($_GET['period_type'] ?? 'MONTH'));
        if ($periodType === 'MONTH') {
            $month = max(1, min(12, (int)($_GET['selected_month'] ?? date('n'))));
            $year  = max(2020, min(2035, (int)($_GET['selected_year'] ?? date('Y'))));
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate   = date('Y-m-t', strtotime($startDate));
        } else {
            $startDate = trim((string)($_GET['date_from'] ?? date('Y-m-01')));
            $endDate   = trim((string)($_GET['date_to'] ?? date('Y-m-d')));
        }

        $requestedConcernIds = $_GET['concern_ids'] ?? [];
        if (!is_array($requestedConcernIds)) {
            $requestedConcernIds = ($requestedConcernIds !== '' && $requestedConcernIds != '0') ? [(int)$requestedConcernIds] : [];
        }
        $concernIds = array_values(array_unique(array_filter(array_map('intval', $requestedConcernIds), static fn(int $id): bool => $id > 0)));
        if (!function_exists('isAdmin') || !isAdmin()) {
            $allowedConcernIds = array_values(array_unique(array_filter(array_map('intval', array_column($this->getScopedConcernsForExport(), 'id')))));
            if (!empty($allowedConcernIds)) {
                $concernIds = !empty($concernIds)
                    ? array_values(array_intersect($concernIds, $allowedConcernIds))
                    : $allowedConcernIds;
                if (empty($concernIds)) {
                    $concernIds = $allowedConcernIds;
                }
            }
        }

        $counts = ExportService::getModuleCounts($startDate, $endDate, $concernIds);
        Response::json([
            'success'    => true,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'counts'     => $counts,
        ]);
    }

    private function getScopedConcernsForExport(): array {
        if (function_exists('isAdmin') && isAdmin()) {
            return Database::fetchAll("
                SELECT c.*,
                       (SELECT GROUP_CONCAT(cm.module_key) FROM concern_modules cm WHERE cm.concern_id = c.id AND cm.is_enabled = 1) AS enabled_modules
                FROM concerns c
                WHERE c.is_active = 1
                ORDER BY c.code ASC, c.id ASC
            ") ?: [];
        }
        $userConcerns = function_exists('getAvailableUserConcerns') ? getAvailableUserConcerns() : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', array_column($userConcerns, 'id')))));
        if (empty($ids)) {
            $curCid = function_exists('currentConcernId') ? (int)currentConcernId() : 0;
            if ($curCid > 0) $ids = [$curCid];
        }
        if (empty($ids)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return Database::fetchAll("
            SELECT c.*,
                   (SELECT GROUP_CONCAT(cm.module_key) FROM concern_modules cm WHERE cm.concern_id = c.id AND cm.is_enabled = 1) AS enabled_modules
            FROM concerns c
            WHERE c.is_active = 1 AND c.id IN ({$ph})
            ORDER BY c.code ASC, c.id ASC
        ", $ids) ?: [];
    }

    private function filterModulesForCurrentUser(array $modules): array {
        if (function_exists('isAdmin') && isAdmin()) {
            return $modules;
        }
        $permMap = [
            'purchases'            => ['production_purchases', 'purchases'],
            'rolls'                => ['production_purchases', 'purchases'],
            'raw_materials'        => ['production_purchases', 'purchases'],
            'third_party_products' => ['production_purchase_mapping', 'production_purchases', 'purchases'],
            'qr_generations'       => ['production_purchase_mapping', 'production_purchases'],
            'suppliers'            => ['sellers', 'wh_sellers'],
            'goods_inward'         => ['inward'],
            'programs'             => ['programs'],
            'cuttings'             => ['cutting'],
            'cutting_outwards'     => ['cutting'],
            'stitching'            => ['stitching'],
            'qc'                   => ['qc'],
            'damages'              => ['damages'],
            'damage_outwards'      => ['damages'],
            'catalog'              => ['catalog'],
            'variants'             => ['variants'],
            'outward'              => ['outward'],
            'inventory'            => ['inventory'],
            'stock_ledger'         => ['inventory', 'stock'],
            'stock_requests'       => ['production_stock_requests', 'stock_requests'],
            'billing'              => ['production_billing'],
            'direct_billing'       => ['production_billing'],
            'coupons'              => ['coupons'],
            'returns'              => ['production_returns'],
            'wastage'              => ['wastage', 'stitching_wastage'],
            'expenses'             => ['production_expenses'],
            'cash_flow'            => ['cash_flow', 'shop_cash'],
            'assets'               => ['asset_register'],
            'asset_maintenance'    => ['asset_maintenance'],
        ];
        $filtered = [];
        foreach ($modules as $key => $info) {
            $perms = $permMap[$key] ?? [];
            if (empty($perms)) {
                $filtered[$key] = $info;
                continue;
            }
            foreach ($perms as $pMod) {
                if (function_exists('hasPermission') && hasPermission($pMod, 'view')) {
                    $filtered[$key] = $info;
                    break;
                }
            }
        }
        return $filtered;
    }

    /**
     * Instant 1-click CSV download for current month
     */
    public function quickCsv(): void {
        $_REQUEST['period_type'] = 'MONTH';
        $_REQUEST['selected_month'] = date('n');
        $_REQUEST['selected_year'] = date('Y');
        $_REQUEST['format'] = 'CSV';
        if (function_exists('currentConcernId')) {
            $curCid = currentConcernId();
            if ($curCid > 0 && !isset($_REQUEST['concern_ids'])) {
                $_REQUEST['concern_ids'] = [$curCid];
            }
        }
        $this->download();
    }

    /**
     * Dedicated separate file export for Direct Bills only
     */
    public function directBillsCsv(): void {
        $_REQUEST['period_type'] = $_REQUEST['period_type'] ?? 'MONTH';
        $_REQUEST['selected_month'] = $_REQUEST['selected_month'] ?? date('n');
        $_REQUEST['selected_year'] = $_REQUEST['selected_year'] ?? date('Y');
        $_REQUEST['format'] = 'CSV';
        $_REQUEST['modules'] = ['direct_billing'];
        $_REQUEST['custom_filename'] = 'direct_bills_separated_export';
        if (function_exists('currentConcernId')) {
            $curCid = currentConcernId();
            if ($curCid > 0 && !isset($_REQUEST['concern_ids'])) {
                $_REQUEST['concern_ids'] = [$curCid];
            }
        }
        $this->download();
    }

    /**
     * Dedicated export for Normal/Standard Bills only
     */
    public function normalBillsCsv(): void {
        $_REQUEST['period_type'] = $_REQUEST['period_type'] ?? 'MONTH';
        $_REQUEST['selected_month'] = $_REQUEST['selected_month'] ?? date('n');
        $_REQUEST['selected_year'] = $_REQUEST['selected_year'] ?? date('Y');
        $_REQUEST['format'] = 'CSV';
        $_REQUEST['modules'] = ['billing'];
        $_REQUEST['custom_filename'] = 'normal_standard_bills_export';
        if (function_exists('currentConcernId')) {
            $curCid = currentConcernId();
            if ($curCid > 0 && !isset($_REQUEST['concern_ids'])) {
                $_REQUEST['concern_ids'] = [$curCid];
            }
        }
        $this->download();
    }
}
