<?php
/**
 * Admin Controller
 * Dashboard, staff management, and role management.
 */

require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Role.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class AdminController {

    private function availableConcernIds(): array {
        return array_values(array_unique(array_map(
            'intval',
            array_column(getAvailableUserConcerns(), 'id')
        )));
    }

    private function normalizeConcernAssignments(array $requestedIds, ?int $defaultConcernId, ?array $existingStaff = null): ?array {
        $allowedIds = $this->availableConcernIds();
        if (empty($allowedIds) || empty($requestedIds)) {
            return null;
        }

        $validatedIds = [];
        foreach ($requestedIds as $requestedId) {
            $validatedId = filter_var($requestedId, FILTER_VALIDATE_INT);
            if ($validatedId === false || $validatedId < 1 || !in_array($validatedId, $allowedIds, true)) {
                return null;
            }
            $validatedIds[] = $validatedId;
        }
        $requestedIds = array_values(array_unique($validatedIds));
        if (!$defaultConcernId || !in_array($defaultConcernId, $requestedIds, true)) {
            return null;
        }

        $preservedIds = [];
        $existingDefaultId = (int)($existingStaff['default_concern_id'] ?? 0);
        if ($existingStaff !== null) {
            $existingIds = User::getConcernIds((int)$existingStaff['id']);
            if ($existingDefaultId > 0 && !in_array($existingDefaultId, $existingIds, true)) {
                $existingIds[] = $existingDefaultId;
            }
            $preservedIds = array_values(array_diff($existingIds, $allowedIds));
        }

        $assignedIds = array_values(array_unique(array_merge($requestedIds, $preservedIds)));
        return [$assignedIds, $defaultConcernId];
    }

    private function staffIsWithinAvailableConcerns(array $staff): bool {
        if (isSuperAdmin()) {
            return true;
        }

        $allowedIds = $this->availableConcernIds();
        $staffConcernIds = User::getConcernIds((int)$staff['id']);
        $defaultConcernId = (int)($staff['default_concern_id'] ?? 0);
        if ($defaultConcernId > 0 && !in_array($defaultConcernId, $staffConcernIds, true)) {
            $staffConcernIds[] = $defaultConcernId;
        }

        return !empty($staffConcernIds) && empty(array_diff($staffConcernIds, $allowedIds));
    }

    private function isProtectedStaffAccount(array $user): bool {
        return (int)($user['role_id'] ?? 0) === 1
            || ($user['username'] ?? '') === 'admin'
            || in_array(strtolower($user['role_slug'] ?? ''), ['admin', 'super_admin'], true);
    }

    /**
     * Resolve authorized dashboard concern scope from query parameter or active session context.
     */
    private function resolveDashboardConcernScope(?string $requestedConcernParam = null): ?array {
        $isGlobalAdmin = isSuperAdmin();
        $availableConcerns = getAvailableUserConcerns();
        $allowedConcernIds = array_values(array_unique(array_map(
            'intval',
            array_column($availableConcerns, 'id')
        )));

        $concernMap = [];
        foreach ($availableConcerns as $concernRow) {
            $concernMap[(int)$concernRow['id']] = $concernRow;
        }

        if (!$isGlobalAdmin && empty($allowedConcernIds)) {
            return null;
        }

        $selectedConcernId = null;
        if ($requestedConcernParam !== null && $requestedConcernParam !== '') {
            $normalized = strtolower(trim($requestedConcernParam));
            if ($normalized === 'all' || $normalized === '0') {
                $selectedConcernId = ($isGlobalAdmin || count($allowedConcernIds) > 1)
                    ? null
                    : $allowedConcernIds[0];
            } else {
                $cid = filter_var($requestedConcernParam, FILTER_VALIDATE_INT);
                if ($cid === false || $cid < 1 || !isset($concernMap[(int)$cid])) {
                    return ['unauthorized' => true];
                }
                $selectedConcernId = (int)$cid;
            }
        } else {
            $activeCid = currentConcernId();
            if ($activeCid !== null && isset($concernMap[$activeCid])) {
                $selectedConcernId = $activeCid;
            } elseif (!$isGlobalAdmin && count($allowedConcernIds) === 1) {
                $selectedConcernId = $allowedConcernIds[0];
            }
        }

        return [
            'unauthorized'      => false,
            'isGlobalAdmin'     => $isGlobalAdmin,
            'availableConcerns' => $availableConcerns,
            'allowedConcernIds' => $allowedConcernIds,
            'concernMap'        => $concernMap,
            'selectedConcernId' => $selectedConcernId,
            'selectedConcern'   => $selectedConcernId !== null ? ($concernMap[$selectedConcernId] ?? null) : null,
        ];
    }

    /**
     * Build SQL concern_id filter fragment and bound parameters.
     */
    private function buildRevenueConcernFilter(
        string $columnRef,
        ?int $selectedConcernId,
        array $allowedConcernIds,
        bool $isGlobalAdmin
    ): array {
        if ($selectedConcernId !== null) {
            if ($selectedConcernId === 1) {
                return [" AND ({$columnRef} = ? OR {$columnRef} IS NULL)", [1]];
            }
            return [" AND {$columnRef} = ?", [$selectedConcernId]];
        }

        if ($isGlobalAdmin) {
            return ['', []];
        }

        if (empty($allowedConcernIds)) {
            return [' AND 1 = 0', []];
        }

        $placeholders = implode(',', array_fill(0, count($allowedConcernIds), '?'));
        if (in_array(1, $allowedConcernIds, true)) {
            return [" AND ({$columnRef} IN ({$placeholders}) OR {$columnRef} IS NULL)", $allowedConcernIds];
        }
        return [" AND {$columnRef} IN ({$placeholders})", $allowedConcernIds];
    }

    /**
     * Validate and normalize dashboard From/To date range.
     */
    private function resolveDashboardDateRange(?string $rawFrom, ?string $rawTo, bool $useDefaultWhenEmpty = true): array {
        $parseDate = static function (?string $val): string {
            $trimmed = trim((string)$val);
            if ($trimmed === '') {
                return '';
            }
            $dt = DateTime::createFromFormat('Y-m-d', $trimmed);
            return ($dt && $dt->format('Y-m-d') === $trimmed) ? $trimmed : '';
        };

        $fromDate = $parseDate($rawFrom);
        $toDate   = $parseDate($rawTo);

        if ($useDefaultWhenEmpty && $rawFrom === null && $rawTo === null) {
            $fromDate = date('Y-m-01', strtotime('-5 months'));
            $toDate   = date('Y-m-d');
        }

        if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [
            'fromDate' => $fromDate,
            'toDate'   => $toDate,
        ];
    }

    /**
     * Build SQL date/datetime range filter fragment and bound parameters.
     */
    private function buildDateRangeFilter(string $columnRef, string $fromDate, string $toDate, bool $isDateTime = false): array {
        $sql = '';
        $params = [];
        if ($fromDate !== '') {
            $sql .= " AND {$columnRef} >= ?";
            $params[] = $isDateTime ? ($fromDate . ' 00:00:00') : $fromDate;
        }
        if ($toDate !== '') {
            $sql .= " AND {$columnRef} <= ?";
            $params[] = $isDateTime ? ($toDate . ' 23:59:59') : $toDate;
        }
        return [$sql, $params];
    }

    /**
     * Compute dashboard KPI cards and revenue chart datasets for the selected concern and date range.
     */
    private function getDashboardRevenueAnalytics(array $scope, string $fromDate = '', string $toDate = ''): array {
        $isGlobalAdmin     = $scope['isGlobalAdmin'];
        $availableConcerns = $scope['availableConcerns'];
        $allowedConcernIds = $scope['allowedConcernIds'];
        $concernMap        = $scope['concernMap'];
        $selectedConcernId = $scope['selectedConcernId'];
        $selectedConcern   = $scope['selectedConcern'];

        // 1. Total Concerns Count (filtered by selected concern & created_at date range)
        [$concernDateSql, $concernDateParams] = $this->buildDateRangeFilter('c.created_at', $fromDate, $toDate, true);
        if ($selectedConcernId !== null) {
            $concernCountRow = Database::fetchOne(
                "SELECT COUNT(*) AS cnt FROM concerns c WHERE c.id = ? AND c.is_active = 1{$concernDateSql}",
                array_merge([$selectedConcernId], $concernDateParams)
            );
            $totalConcerns = (int)($concernCountRow['cnt'] ?? 0);
        } elseif ($isGlobalAdmin) {
            $concernCountRow = Database::fetchOne(
                "SELECT COUNT(*) AS cnt FROM concerns c WHERE c.is_active = 1{$concernDateSql}",
                $concernDateParams
            );
            $totalConcerns = (int)($concernCountRow['cnt'] ?? 0);
        } elseif (!empty($allowedConcernIds)) {
            $placeholders = implode(',', array_fill(0, count($allowedConcernIds), '?'));
            $concernCountRow = Database::fetchOne(
                "SELECT COUNT(*) AS cnt FROM concerns c WHERE c.is_active = 1 AND c.id IN ({$placeholders}){$concernDateSql}",
                array_merge($allowedConcernIds, $concernDateParams)
            );
            $totalConcerns = (int)($concernCountRow['cnt'] ?? 0);
        } else {
            $totalConcerns = 0;
        }
        $concernsSubtitle = $selectedConcern
            ? ($selectedConcern['name'] . ' (' . $selectedConcern['code'] . ')')
            : 'All Active Business Entities';

        // 2. Total Staff Count (active non-superadmin staff within concern & date scope)
        [$staffDateSql, $staffDateParams] = $this->buildDateRangeFilter('u.created_at', $fromDate, $toDate, true);
        $staffSql = "SELECT COUNT(DISTINCT u.id) AS cnt
                     FROM users u
                     LEFT JOIN roles r ON u.role_id = r.id
                     WHERE u.is_active = 1
                       AND (u.role_id != 1 AND u.username != 'admin' AND (r.slug NOT IN ('admin', 'super_admin') OR r.slug IS NULL)){$staffDateSql}";
        $staffParams = $staffDateParams;
        if ($selectedConcernId !== null) {
            $staffSql .= " AND (EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id = ?) OR u.default_concern_id = ?)";
            $staffParams[] = $selectedConcernId;
            $staffParams[] = $selectedConcernId;
        } elseif (!$isGlobalAdmin) {
            if (!empty($allowedConcernIds)) {
                $placeholders = implode(',', array_fill(0, count($allowedConcernIds), '?'));
                $staffSql .= " AND (EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id IN ({$placeholders})) OR u.default_concern_id IN ({$placeholders}))";
                $staffParams = array_merge($staffParams, $allowedConcernIds, $allowedConcernIds);
            } else {
                $staffSql .= " AND 1 = 0";
            }
        }
        $totalStaff = (int)(Database::fetchOne($staffSql, $staffParams)['cnt'] ?? 0);
        $staffSubtitle = $selectedConcern
            ? ('Assigned to ' . $selectedConcern['code'])
            : 'Active Workforce Directory';

        // 3. Total Purchase Amount (production_purchases filtered by concern & purchase_date)
        [$purFilter, $purParams] = $this->buildRevenueConcernFilter('pp.concern_id', $selectedConcernId, $allowedConcernIds, $isGlobalAdmin);
        [$purDateSql, $purDateParams] = $this->buildDateRangeFilter('pp.purchase_date', $fromDate, $toDate, false);
        $purchaseRow = Database::fetchOne(
            "SELECT COUNT(*) AS purchase_count,
                    COALESCE(SUM(pp.total_amount), 0) AS total_purchase_amount
             FROM production_purchases pp
             WHERE 1=1{$purFilter}{$purDateSql}",
            array_merge($purParams, $purDateParams)
        );
        $purchaseCount       = (int)($purchaseRow['purchase_count'] ?? 0);
        $totalPurchaseAmount = round((float)($purchaseRow['total_purchase_amount'] ?? 0), 2);

        // 4. Total Selling Amount (production_bills filtered by concern & bill_date)
        [$billFilter, $billParams] = $this->buildRevenueConcernFilter('pb.concern_id', $selectedConcernId, $allowedConcernIds, $isGlobalAdmin);
        [$billDateSql, $billDateParams] = $this->buildDateRangeFilter('pb.bill_date', $fromDate, $toDate, false);
        $sellingRow = Database::fetchOne(
            "SELECT COUNT(*) AS selling_count,
                    COALESCE(SUM(pb.total_amount), 0) AS total_selling_amount,
                    COALESCE(SUM(CASE WHEN pb.payment_status = 'PAID' THEN pb.total_amount ELSE 0 END), 0) AS paid_selling_amount,
                    COALESCE(SUM(CASE WHEN pb.payment_status != 'PAID' THEN pb.total_amount ELSE 0 END), 0) AS pending_selling_amount
             FROM production_bills pb
             WHERE 1=1{$billFilter}{$billDateSql}",
            array_merge($billParams, $billDateParams)
        );
        $sellingCount         = (int)($sellingRow['selling_count'] ?? 0);
        $totalSellingAmount   = round((float)($sellingRow['total_selling_amount'] ?? 0), 2);
        $paidSellingAmount    = round((float)($sellingRow['paid_selling_amount'] ?? 0), 2);
        $pendingSellingAmount = round((float)($sellingRow['pending_selling_amount'] ?? 0), 2);
        $netRevenueBalance    = round($totalSellingAmount - $totalPurchaseAmount, 2);

        // 5. Revenue Trend Graph (adaptive Daily when <= 45 days, Monthly otherwise)
        $trendEnd   = $toDate !== '' ? $toDate : date('Y-m-d');
        $trendStart = $fromDate !== '' ? $fromDate : date('Y-m-01', strtotime('-11 months', strtotime($trendEnd)));
        if ($trendStart > $trendEnd) {
            $trendStart = date('Y-m-01', strtotime('-11 months', strtotime($trendEnd)));
        }

        $daysDiff = (int)floor((strtotime($trendEnd) - strtotime($trendStart)) / 86400) + 1;
        $isDailyTrend = ($fromDate !== '' && $toDate !== '' && $daysDiff <= 45);

        $bucketKeys = [];
        $bucketLabels = [];
        if ($isDailyTrend) {
            $cursor = new DateTime($trendStart);
            $endObj = new DateTime($trendEnd);
            while ($cursor <= $endObj) {
                $key = $cursor->format('Y-m-d');
                $bucketKeys[]   = $key;
                $bucketLabels[] = $cursor->format('d M');
                $cursor->modify('+1 day');
            }

            $purGroupExpr  = 'DATE(pp.purchase_date)';
            $billGroupExpr = 'DATE(pb.bill_date)';
            $trendPurStart = $trendStart;
            $trendPurEnd   = $trendEnd;
        } else {
            $cursor = new DateTime(date('Y-m-01', strtotime($trendStart)));
            $endObj = new DateTime(date('Y-m-01', strtotime($trendEnd)));
            while ($cursor <= $endObj) {
                $key = $cursor->format('Y-m');
                $bucketKeys[]   = $key;
                $bucketLabels[] = $cursor->format('M Y');
                $cursor->modify('+1 month');
            }

            $purGroupExpr  = "DATE_FORMAT(pp.purchase_date, '%Y-%m')";
            $billGroupExpr = "DATE_FORMAT(pb.bill_date, '%Y-%m')";
            $trendPurStart = $fromDate !== '' ? $fromDate : $trendStart;
            $trendPurEnd   = $toDate !== '' ? $toDate : $trendEnd;
        }

        $monthlyPurchaseRows = Database::fetchAll(
            "SELECT {$purGroupExpr} AS bucket,
                    COALESCE(SUM(pp.total_amount), 0) AS total_amt
             FROM production_purchases pp
             WHERE pp.purchase_date >= ? AND pp.purchase_date <= ?{$purFilter}
             GROUP BY {$purGroupExpr}",
            array_merge([$trendPurStart, $trendPurEnd], $purParams)
        ) ?: [];
        $purchaseBucketMap = [];
        foreach ($monthlyPurchaseRows as $row) {
            $purchaseBucketMap[$row['bucket']] = round((float)$row['total_amt'], 2);
        }

        $monthlySellingRows = Database::fetchAll(
            "SELECT {$billGroupExpr} AS bucket,
                    COALESCE(SUM(pb.total_amount), 0) AS total_amt
             FROM production_bills pb
             WHERE pb.bill_date >= ? AND pb.bill_date <= ?{$billFilter}
             GROUP BY {$billGroupExpr}",
            array_merge([$trendPurStart, $trendPurEnd], $billParams)
        ) ?: [];
        $sellingBucketMap = [];
        foreach ($monthlySellingRows as $row) {
            $sellingBucketMap[$row['bucket']] = round((float)$row['total_amt'], 2);
        }

        $trendPurchases = [];
        $trendSelling = [];
        foreach ($bucketKeys as $key) {
            $trendPurchases[] = $purchaseBucketMap[$key] ?? 0.0;
            $trendSelling[]   = $sellingBucketMap[$key] ?? 0.0;
        }

        // 6. Concern-wise Purchase vs Selling Breakdown (within selected date range)
        $concernsToCompare = $selectedConcern ? [$selectedConcern] : $availableConcerns;
        $hasConcernOne = isset($concernMap[1]);
        $purExpr = $hasConcernOne ? 'COALESCE(pp.concern_id, 1)' : 'pp.concern_id';
        $billExpr = $hasConcernOne ? 'COALESCE(pb.concern_id, 1)' : 'pb.concern_id';

        $purByConcernRows = Database::fetchAll(
            "SELECT {$purExpr} AS cid, COALESCE(SUM(pp.total_amount), 0) AS total_amt
             FROM production_purchases pp
             WHERE 1=1{$purFilter}{$purDateSql}
             GROUP BY {$purExpr}",
            array_merge($purParams, $purDateParams)
        ) ?: [];
        $purByConcern = [];
        foreach ($purByConcernRows as $r) {
            if ($r['cid'] !== null) {
                $purByConcern[(int)$r['cid']] = round((float)$r['total_amt'], 2);
            }
        }

        $sellByConcernRows = Database::fetchAll(
            "SELECT {$billExpr} AS cid, COALESCE(SUM(pb.total_amount), 0) AS total_amt
             FROM production_bills pb
             WHERE 1=1{$billFilter}{$billDateSql}
             GROUP BY {$billExpr}",
            array_merge($billParams, $billDateParams)
        ) ?: [];
        $sellByConcern = [];
        foreach ($sellByConcernRows as $r) {
            if ($r['cid'] !== null) {
                $sellByConcern[(int)$r['cid']] = round((float)$r['total_amt'], 2);
            }
        }

        $comparisonLabels = [];
        $comparisonPurchases = [];
        $comparisonSelling = [];
        foreach ($concernsToCompare as $cRow) {
            $cid = (int)$cRow['id'];
            $comparisonLabels[]    = $cRow['name'] . ' (' . $cRow['code'] . ')';
            $comparisonPurchases[] = $purByConcern[$cid] ?? 0.0;
            $comparisonSelling[]   = $sellByConcern[$cid] ?? 0.0;
        }

        if ($fromDate !== '' && $toDate !== '') {
            $dateRangeLabel = date('d M Y', strtotime($fromDate)) . ' – ' . date('d M Y', strtotime($toDate));
        } elseif ($fromDate !== '') {
            $dateRangeLabel = 'From ' . date('d M Y', strtotime($fromDate));
        } elseif ($toDate !== '') {
            $dateRangeLabel = 'Up to ' . date('d M Y', strtotime($toDate));
        } else {
            $dateRangeLabel = 'All Time';
        }

        return [
            'selectedConcernId' => $selectedConcernId ? (string)$selectedConcernId : 'all',
            'selectedScopeName' => $selectedConcern
                ? ($selectedConcern['name'] . ' (' . $selectedConcern['code'] . ')')
                : 'All Concerns (Overall)',
            'fromDate'          => $fromDate,
            'toDate'            => $toDate,
            'dateRangeLabel'    => $dateRangeLabel,
            'trendSubtitle'     => ($isDailyTrend ? 'Daily' : 'Monthly') . ' Total Purchase vs Selling Amount (' . $dateRangeLabel . ')',
            'cards' => [
                'totalConcerns'        => $totalConcerns,
                'concernsSubtitle'     => $concernsSubtitle,
                'totalStaff'           => $totalStaff,
                'staffSubtitle'        => $staffSubtitle,
                'totalPurchaseAmount'  => $totalPurchaseAmount,
                'purchaseCount'        => $purchaseCount,
                'totalSellingAmount'   => $totalSellingAmount,
                'sellingCount'         => $sellingCount,
                'paidSellingAmount'    => $paidSellingAmount,
                'pendingSellingAmount' => $pendingSellingAmount,
                'netRevenueBalance'    => $netRevenueBalance,
            ],
            'charts' => [
                'monthlyTrend' => [
                    'labels'      => $bucketLabels,
                    'purchases'   => $trendPurchases,
                    'selling'     => $trendSelling,
                    'granularity' => $isDailyTrend ? 'daily' : 'monthly',
                ],
                'revenueSplit' => [
                    'labels' => ['Total Selling', 'Total Purchases'],
                    'values' => [$totalSellingAmount, $totalPurchaseAmount],
                ],
                'concernComparison' => [
                    'labels'    => $comparisonLabels,
                    'purchases' => $comparisonPurchases,
                    'selling'   => $comparisonSelling,
                ],
            ],
        ];
    }

    /**
     * Admin Dashboard.
     */
    public function dashboard(): void {
        if (!hasPermission('dashboard', 'view')) {
            $dashboardRoutes = [
                'sellers'                    => ['production/sellers', 'production/sellers/create'],
                'production_purchases'       => ['production/purchases', 'production/purchases/create'],
                'production_purchase_mapping'=> ['production/purchases/mapping', null],
                'cash_flow'                  => ['production/cash-flow', null],
                'damages'                    => ['production/damages', null],
                'catalog'                    => ['production/catalog', 'production/catalog/create'],
                'variants'                   => ['production/variants', 'production/variants/create'],
                'qr_generation'              => ['production/qr-generations', 'production/qr-generations/create'],
                'programs'                   => ['production/programs', 'production/programs/create'],
                'cutting'                    => ['production/cutting', 'production/cutting/create'],
                'wastage'                    => ['production/wastage', null],
                'stitching'                  => ['production/stitching', 'production/stitching/create'],
                'stitching_wastage'          => ['production/stitching-wastage', null],
                'qc'                         => ['production/qc', 'production/qc/create'],
                'inventory'                  => ['production/inventory', null],
                'inward'                     => ['production/inwards', 'production/inwards/create'],
                'production_expenses'        => ['production/expenses', null],
                'production_billing'         => ['production/billing', 'production/billing/create'],
                'production_returns'         => ['production/returns', 'production/returns/create'],
                'batches'                    => ['production/batches', 'production/batches/create'],
                'outward'                    => ['production/outward', null],
                'production_stock_requests'  => ['production/stock-requests', 'production/stock-requests/create'],
                'warehouse_rework'           => ['production/warehouse-rework', null],
                'production_hsn'             => ['production/hsn-config', 'production/hsn-config/create'],
                'asset_register'             => ['assets', 'assets/create'],
                'asset_maintenance'          => ['assets/maintenance', 'assets/maintenance/create'],
                'reports'                    => ['export', null],
                'concerns'                   => ['admin/concerns', 'admin/concerns/create'],
                'staff'                      => ['admin/staff?department=all', 'admin/staff/create'],
                'roles'                      => ['admin/roles', 'admin/roles/create'],
                'audit'                      => ['system/audit', 'system/audit'],
                'settings'                   => ['system/settings', 'system/settings'],
                'backup'                     => ['system/backup', 'system/backup'],
                'notifications'              => ['system/notifications', 'system/notifications'],
            ];
            $permissionAliases = [
                'production_purchases' => ['production_purchases', 'purchases'],
                'cash_flow' => ['cash_flow', 'shop_cash'],
                'production_hsn' => ['production_hsn', 'hsn_config'],
            ];
            $dashboardGroups = [];

            foreach (Role::getHierarchicalPermissions() as $group) {
                $items = [];
                foreach ($group['submodules'] as $moduleKey => $submodule) {
                    if (!isset($dashboardRoutes[$moduleKey])) {
                        continue;
                    }

                    $permissionModules = $permissionAliases[$moduleKey] ?? [$moduleKey];
                    $hasView = false;
                    $hasCreate = false;
                    foreach ($permissionModules as $permissionModule) {
                        $hasView = $hasView || hasPermission($permissionModule, 'view');
                        $hasCreate = $hasCreate || hasPermission($permissionModule, 'create');
                    }
                    if (!$hasView && !$hasCreate) {
                        continue;
                    }

                    $route = $hasView
                        ? $dashboardRoutes[$moduleKey][0]
                        : $dashboardRoutes[$moduleKey][1];
                    if ($route === null) {
                        continue;
                    }
                    $items[] = [
                        'name' => $submodule['name'],
                        'icon' => $submodule['icon'] ?? 'bi-grid',
                        'url' => url($route),
                    ];
                }

                if (!empty($items)) {
                    $dashboardGroups[] = [
                        'title' => $group['title'],
                        'icon' => $group['icon'] ?? 'bi-grid',
                        'items' => $items,
                    ];
                }
            }

            if (hasPermission('production', 'view')) {
                $hasProductionGroup = false;
                foreach ($dashboardGroups as $group) {
                    if ($group['title'] === 'Production') {
                        $hasProductionGroup = true;
                        break;
                    }
                }
                if (!$hasProductionGroup) {
                    $dashboardGroups[] = [
                        'title' => 'Production',
                        'icon' => 'bi-gear-wide-connected',
                        'items' => [],
                        'emptyMessage' => 'No production submodules are assigned yet. Ask your administrator to assign the production pages you need.',
                    ];
                }
            }

            if (empty($dashboardGroups)) {
                Response::error(403, 'Access denied. No dashboard modules are assigned to your account.');
                return;
            }

            Response::view('admin.module_dashboard', [
                'pageTitle' => 'My Dashboard',
                'activeMenu' => 'dashboard',
                'breadcrumb' => [['label' => 'Dashboard']],
                'dashboardGroups' => $dashboardGroups,
                'dashboardUser' => currentUser(),
            ]);
            return;
        }

        $requestedConcern = isset($_GET['concern_id']) ? (string)$_GET['concern_id'] : null;
        $hasDateParams    = isset($_GET['from_date']) || isset($_GET['to_date']);
        $dateRange        = $this->resolveDashboardDateRange(
            $_GET['from_date'] ?? null,
            $_GET['to_date'] ?? null,
            !$hasDateParams
        );
        $scope = $this->resolveDashboardConcernScope($requestedConcern);

        if ($scope === null || !empty($scope['unauthorized'])) {
            Response::error(403, 'Select an assigned business concern to view its dashboard.');
            return;
        }

        $analytics = $this->getDashboardRevenueAnalytics($scope, $dateRange['fromDate'], $dateRange['toDate']);

        Response::view('admin.dashboard', [
            'pageTitle'           => 'Dashboard',
            'activeMenu'          => 'dashboard',
            'breadcrumb'          => [['label' => 'Dashboard']],
            'availableConcerns'   => $scope['availableConcerns'],
            'canSelectAllConcerns'=> $scope['isGlobalAdmin'] || count($scope['allowedConcernIds']) > 1,
            'analytics'           => $analytics,
        ]);
    }

    /**
     * AJAX endpoint: Return concern-filtered KPI cards and revenue charts JSON.
     */
    public function ajaxDashboardRevenue(): void {
        if (!hasPermission('dashboard', 'view')) {
            Response::jsonError('Access denied: You do not have permission to view dashboard analytics.', [], 403);
        }

        $requestedConcern = isset($_GET['concern_id']) ? (string)$_GET['concern_id'] : 'all';
        $hasDateParams    = isset($_GET['from_date']) || isset($_GET['to_date']);
        $dateRange        = $this->resolveDashboardDateRange(
            $_GET['from_date'] ?? null,
            $_GET['to_date'] ?? null,
            !$hasDateParams
        );
        $scope = $this->resolveDashboardConcernScope($requestedConcern);

        if ($scope === null || !empty($scope['unauthorized'])) {
            Response::jsonError('Unauthorized or invalid concern selection.', [], 403);
        }

        $analytics = $this->getDashboardRevenueAnalytics($scope, $dateRange['fromDate'], $dateRange['toDate']);
        Response::jsonSuccess('Dashboard revenue metrics loaded.', $analytics);
    }

    /**
     * Staff List.
     */
    public function staffList(): void {
        $department = $_GET['department'] ?? 'all';
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
        $filters = [
            'search'             => $_GET['search'] ?? '',
            'role_id'            => $_GET['role_id'] ?? '',
            'concern_id'         => $_GET['concern_id'] ?? '',
            'department'         => strtolower($department) === 'all' ? '' : $department,
            'production_unit_id' => $_GET['production_unit_id'] ?? '',
            'warehouse_id'       => $_GET['warehouse_id'] ?? '',
            'shop_id'            => $_GET['shop_id'] ?? '',
        ];
        if ($status !== null) {
            $filters['is_active'] = $status;
        }

        // Concern scoping: non-superadmin only views authorized concerns
        $availConcerns = getAvailableUserConcerns();
        $availConcernIds = array_column($availConcerns, 'id');

        if (!isSuperAdmin()) {
            if (!empty($filters['concern_id'])) {
                if (!in_array((int)$filters['concern_id'], $availConcernIds)) {
                    $filters['concern_id'] = currentConcernId();
                }
            } else {
                $filters['concern_id'] = currentConcernId();
            }
            $concerns = $availConcerns;
        } else {
            $concerns = Database::fetchAll("SELECT id, name, code FROM concerns WHERE is_active = 1 ORDER BY name ASC") ?: [];
        }

        $staff           = User::getAll($filters);
        if (!isSuperAdmin()) {
            $scopedStaff = [];
            foreach ($staff as $staffMember) {
                if ($this->staffIsWithinAvailableConcerns($staffMember)) {
                    $scopedStaff[] = $staffMember;
                }
            }
            $staff = $scopedStaff;
        }
        $roles           = Role::getActive();
        $productionUnits = ProductionUnit::getWithMetrics();

        $activeMenu = 'staff-all';
        $deptLower = strtolower($department);
        if ($deptLower === 'all') $activeMenu = 'staff-all';
        elseif ($deptLower === 'production') $activeMenu = 'staff-production';
        elseif ($deptLower === 'warehouse') $activeMenu = 'staff-warehouse';
        elseif ($deptLower === 'whatsapp') $activeMenu = 'staff-whatsapp';
        elseif ($deptLower === 'website') $activeMenu = 'staff-website';
        elseif ($deptLower === 'shop') $activeMenu = 'staff-shop';
        elseif ($deptLower === 'reports') $activeMenu = 'staff-reports';

        Response::view('admin.staff_list', [
            'pageTitle'       => 'Staff Management',
            'activeModule'    => 'staff',
            'activeMenu'      => $activeMenu,
            'breadcrumb'      => [['label' => 'Staff']],
            'staff'           => $staff,
            'roles'           => $roles,
            'productionUnits' => $productionUnits,
            'concerns'        => $concerns,
            'filters'         => $filters,
        ]);
    }

    public function viewStaff(string $id): void {
        $staff = User::findById((int)$id);
        if (!$staff || $this->isProtectedStaffAccount($staff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }
        if (!$this->staffIsWithinAvailableConcerns($staff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }

        $assignments = array_filter([
            'Production Unit' => $staff['production_unit_name'] ?? null,
            'Warehouse'       => $staff['warehouse_name'] ?? null,
            'Shop'            => $staff['shop_name'] ?? null,
            'Sales Channel'   => $staff['channel_name'] ?? null,
        ]);

        $userConcerns = User::getConcerns((int)$staff['id']);

        Response::view('admin.staff_view', [
            'pageTitle'    => 'Staff Details',
            'activeModule' => 'staff',
            'activeMenu'   => 'staff-all',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Details'],
            ],
            'staff'        => $staff,
            'assignments'  => $assignments,
            'userConcerns' => $userConcerns,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'staffPermissions'    => User::getPermissionIds((int)$staff['id']),
        ]);
    }

    /**
     * Show create staff form.
     */
    public function createStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        $concerns         = getAvailableUserConcerns();
        $concernIds       = array_map('intval', array_column($concerns, 'id'));
        if (empty($concernIds)) {
            Response::error(403, 'You must be assigned to an active concern before creating staff.');
            return;
        }
        $activeConcernId  = currentConcernId();
        $curConcernId     = $activeConcernId && in_array($activeConcernId, $concernIds, true)
            ? $activeConcernId
            : $concernIds[0];

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Create Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff-create',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Create'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'concerns'            => $concerns,
            'staffConcerns'       => [$curConcernId],
            'defaultConcernId'    => $curConcernId,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => [],
            'sidebarItems'        => SidebarMenu::allModuleItems(),
            'savedSidebarOrder'   => SidebarMenu::getOrderForUser(null),
            'staff'               => null,
        ]);
    }

    /**
     * Store new staff.
     */
    public function storeStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $validator = Validator::make($_POST)
            ->required('username')
            ->required('full_name')
            ->required('password')
            ->required('role_id')
            ->minLength('password', 6)
            ->unique('username', 'users');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', null, 'Phone number');
        }

        if ($validator->fails()) {
            Session::set('_old_input', $_POST);
            Response::back('error', implode(' ', $validator->allErrors()));
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? 'ALL');
        if (empty($department) || $department === 'PRODUCTION') {
            $department = 'ALL';
        }
        $_POST['department'] = $department;

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Session::set('_old_input', $_POST);
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
        }

        try {
            // Support operational concern selection & reset removed production unit / staff role
            if (!empty($_POST['operational_concern_id'])) {
                $opCid = (int)$_POST['operational_concern_id'];
                if (empty($_POST['default_concern_id'])) {
                    $_POST['default_concern_id'] = $opCid;
                }
                if (!isset($_POST['concerns']) || !is_array($_POST['concerns'])) {
                    $_POST['concerns'] = [$opCid];
                } elseif (!in_array($opCid, array_map('intval', $_POST['concerns']), true)) {
                    $_POST['concerns'][] = $opCid;
                }
            }
            $_POST['production_unit_id'] = null;
            $_POST['staff_role'] = null;

            // Process concern assignment and default login session concern
            $assignedConcerns = isset($_POST['concerns']) && is_array($_POST['concerns']) ? $_POST['concerns'] : [];
            $defaultConcernId = !empty($_POST['default_concern_id']) ? (int)$_POST['default_concern_id'] : null;

            $concernAssignment = $this->normalizeConcernAssignments(
                $assignedConcerns,
                $defaultConcernId
            );
            if ($concernAssignment === null) {
                Response::error(403, 'Select only concerns assigned to your account and choose one as the default.');
                return;
            }
            [$assignedConcerns, $defaultConcernId] = $concernAssignment;

            $_POST['concerns'] = $assignedConcerns;
            $_POST['default_concern_id'] = $defaultConcernId;

            $id = User::create($_POST);

            // Save explicitly checked custom permissions for this staff member
            if (isset($_POST['permissions'])) {
                $rawPerms = is_array($_POST['permissions']) ? array_filter($_POST['permissions']) : [];
                User::savePermissions($id, $rawPerms);
            }
            if (!empty($_POST['sidebar_order'])) {
                SidebarMenu::ensureSchema();
                $sanitizedOrder = SidebarMenu::sanitizeOrder($_POST['sidebar_order']);
                Database::execute("UPDATE users SET sidebar_order = ? WHERE id = ?", [json_encode($sanitizedOrder), $id]);
            }

            $newSnapshot = $this->prepareStaffSnapshot($_POST);
            AuditService::log('staff', 'create', 'users', $id, null, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member created successfully.');
        } catch (Exception $e) {
            error_log("Error creating staff: " . $e->getMessage());
            Response::back('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    /**
     * Helper to prepare human-readable staff attribute snapshots for audit logging.
     */
    private function prepareStaffSnapshot(array $data, ?array $userRecord = null): array {
        $roleName = $userRecord['role_name'] ?? null;
        if (!empty($data['role_id'])) {
            $role = Role::findById((int)$data['role_id']);
            $roleName = $role['name'] ?? $roleName;
        }

        $puName = $userRecord['production_unit_name'] ?? null;
        if (array_key_exists('production_unit_id', $data)) {
            $puId = (int)$data['production_unit_id'];
            $pu = $puId ? ProductionUnit::find($puId) : null;
            $puName = $pu['name'] ?? ($puId ? 'Unit #' . $puId : 'None (Unassigned)');
        }

        $whName = $userRecord['warehouse_name'] ?? 'None';
        $shopName = $userRecord['shop_name'] ?? 'None';
        $chanName = $userRecord['channel_name'] ?? 'None';

        $snapshot = [
            'Staff Name'        => $data['full_name'] ?? ($userRecord['full_name'] ?? ''),
            'Username'          => $data['username'] ?? ($userRecord['username'] ?? ''),
            'Role'              => $roleName ?: 'Staff',
            'Department'        => $data['department'] ?? ($userRecord['department'] ?? 'PRODUCTION'),
            'Email'             => !empty($data['email']) ? $data['email'] : ($userRecord['email'] ?? '-'),
            'Phone'             => !empty($data['phone']) ? $data['phone'] : ($userRecord['phone'] ?? '-'),
            'Production Unit'   => $puName ?: 'None',
            'Warehouse'         => $whName ?: 'None',
            'Shop'              => $shopName ?: 'None',
            'Sales Channel'     => $chanName ?: 'None',
            'Reports Access'    => $data['reports_access'] ?? ($userRecord['reports_access'] ?? 'ALL'),
            'Account Status'    => isset($data['is_active']) ? ((int)$data['is_active'] ? 'Active' : 'Deactivated') : ($userRecord ? ((int)($userRecord['is_active'] ?? 1) ? 'Active' : 'Deactivated') : 'Active'),
        ];

        if (!empty($data['password'])) {
            $snapshot['Password'] = '[UPDATED / ENCRYPTED]';
        }

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $snapshot['Custom Permissions'] = count($data['permissions']) . ' specific permissions assigned';
        }

        $concernInfo = '-';
        if (!empty($data['concerns']) && is_array($data['concerns'])) {
            $cids = array_map('intval', $data['concerns']);
            if (!empty($cids)) {
                $inClause = implode(',', $cids);
                $cNames = Database::fetchAll("SELECT CONCAT(code, ': ', name) as ctitle FROM concerns WHERE id IN ($inClause)");
                $concernInfo = implode(', ', array_column($cNames, 'ctitle'));
            }
        } elseif (!empty($userRecord['assigned_concerns_str'])) {
            $concernInfo = str_replace('||', ', ', $userRecord['assigned_concerns_str']);
        }

        $defaultConcernName = '-';
        if (!empty($data['default_concern_id'])) {
            $dc = Database::fetchOne("SELECT name, code FROM concerns WHERE id = ?", [(int)$data['default_concern_id']]);
            if ($dc) $defaultConcernName = "{$dc['code']} - {$dc['name']}";
        } elseif (!empty($userRecord['default_concern_name'])) {
            $defaultConcernName = ($userRecord['default_concern_code'] ?? '') . ' - ' . $userRecord['default_concern_name'];
        }

        $snapshot['Authorized Concerns'] = $concernInfo;
        $snapshot['Default Concern'] = $defaultConcernName;

        return $snapshot;
    }

    /**
     * Show edit staff form.
     */
    public function editStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ($this->isProtectedStaffAccount($staff)) {
            Response::redirect(url('admin/staff'), 'warning', 'Super Administrator account is managed under System Settings.');
            return;
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        if (!$this->staffIsWithinAvailableConcerns($staff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }

        $concerns         = getAvailableUserConcerns();
        $availableIds     = array_map('intval', array_column($concerns, 'id'));
        $staffConcerns    = User::getConcernIds($staffId);
        $defaultConcernId = (int)($staff['default_concern_id'] ?? 0);
        if ($defaultConcernId > 0 && !in_array($defaultConcernId, $staffConcerns, true)) {
            $staffConcerns[] = $defaultConcernId;
        }
        $staffConcerns = array_values(array_intersect($staffConcerns, $availableIds));
        if (!in_array($defaultConcernId, $availableIds, true)) {
            $defaultConcernId = 0;
        }
        if ($defaultConcernId === 0 && !empty($staffConcerns)) {
            $defaultConcernId = $staffConcerns[0];
        }

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Edit Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Edit'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'concerns'            => $concerns,
            'staffConcerns'       => $staffConcerns,
            'defaultConcernId'    => $defaultConcernId,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => User::getPermissionIds($staffId),
            'sidebarItems'        => SidebarMenu::allModuleItems(),
            'savedSidebarOrder'   => SidebarMenu::getOrderForUser($staffId),
            'staff'               => $staff,
        ]);
    }

    /**
     * Update staff.
     */
    public function updateStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $oldStaff = User::findById($staffId);
        if (!$oldStaff) Response::error(404, 'Staff not found.');
        if ($this->isProtectedStaffAccount($oldStaff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }
        if (!$this->staffIsWithinAvailableConcerns($oldStaff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }

        $validator = Validator::make($_POST)
            ->required('full_name')
            ->required('role_id');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', $staffId, 'Phone number');
        }

        if ($validator->fails()) {
            Response::back('error', implode(' ', $validator->allErrors()));
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? ($oldStaff['department'] ?? 'ALL'));
        if (empty($department) || $department === 'PRODUCTION') {
            $department = 'ALL';
        }
        $_POST['department'] = $department;

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
        }

        try {
            // Support operational concern selection & reset removed production unit / staff role
            if (!empty($_POST['operational_concern_id'])) {
                $opCid = (int)$_POST['operational_concern_id'];
                if (empty($_POST['default_concern_id'])) {
                    $_POST['default_concern_id'] = $opCid;
                }
                if (!isset($_POST['concerns']) || !is_array($_POST['concerns'])) {
                    $_POST['concerns'] = [$opCid];
                } elseif (!in_array($opCid, array_map('intval', $_POST['concerns']), true)) {
                    $_POST['concerns'][] = $opCid;
                }
            }
            $_POST['production_unit_id'] = null;
            $_POST['staff_role'] = null;

            // Process concern assignment and default login session concern
            if (isset($_POST['concern_assignment_submitted']) || isset($_POST['concerns'])) {
                $assignedConcerns = isset($_POST['concerns']) && is_array($_POST['concerns']) ? $_POST['concerns'] : [];
                $defaultConcernId = !empty($_POST['default_concern_id']) ? (int)$_POST['default_concern_id'] : null;

                $concernAssignment = $this->normalizeConcernAssignments(
                    $assignedConcerns,
                    $defaultConcernId,
                    $oldStaff
                );
                if ($concernAssignment === null) {
                    Response::error(403, 'Select only concerns assigned to your account and choose one as the default.');
                    return;
                }
                [$assignedConcerns, $defaultConcernId] = $concernAssignment;

                $_POST['concerns'] = $assignedConcerns;
                $_POST['default_concern_id'] = $defaultConcernId;
            }

            $oldSnapshot = $this->prepareStaffSnapshot([], $oldStaff);
            $newSnapshot = $this->prepareStaffSnapshot($_POST, $oldStaff);
            User::update($staffId, $_POST);

            // Sync explicitly checked custom permissions for this staff member
            if (isset($_POST['permissions'])) {
                $rawPerms = is_array($_POST['permissions']) ? array_filter($_POST['permissions']) : [];
                User::savePermissions($staffId, $rawPerms);
            }
            if (!empty($_POST['sidebar_order'])) {
                SidebarMenu::ensureSchema();
                $sanitizedOrder = SidebarMenu::sanitizeOrder($_POST['sidebar_order']);
                Database::execute("UPDATE users SET sidebar_order = ? WHERE id = ?", [json_encode($sanitizedOrder), $staffId]);
            }

            AuditService::log('staff', 'update', 'users', $staffId, $oldSnapshot, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member updated successfully.');
        } catch (Exception $e) {
            error_log("Error updating staff: " . $e->getMessage());
            Response::back('error', 'Failed to update staff member: ' . $e->getMessage());
        }
    }

    /**
     * AJAX check phone format & uniqueness.
     */
    public function checkPhoneAvailability(): void {
        $phone = trim((string)($_GET['phone'] ?? ''));
        $exceptId = !empty($_GET['except_id']) ? (int)$_GET['except_id'] : null;

        if ($phone === '') {
            Response::json(['valid' => true, 'available' => true, 'message' => '']);
            return;
        }

        // Clean phone
        $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', $phone);
        $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);

        // Check if non-digits are present
        if (!ctype_digit($cleanPhone)) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must only contain numeric digits (0-9).'
            ]);
            return;
        }

        // Check length
        if (strlen($cleanPhone) !== 10) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must be exactly 10 digits.'
            ]);
            return;
        }

        // Check Indian starting digit (6-9)
        if (!in_array($cleanPhone[0], ['6', '7', '8', '9'])) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Indian mobile numbers must start with 6, 7, 8, or 9.'
            ]);
            return;
        }

        // Check duplicate in database
        $sql = "SELECT id, full_name, username FROM users WHERE phone = ?";
        $params = [$cleanPhone];
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }

        $existing = Database::fetchOne($sql, $params);
        if ($existing) {
            Response::json([
                'valid' => true,
                'available' => false,
                'message' => "Phone number is already registered to '{$existing['full_name']}' (@{$existing['username']})."
            ]);
            return;
        }

        Response::json([
            'valid' => true,
            'available' => true,
            'message' => 'Phone number is valid and available.'
        ]);
    }

    /**
     * Toggle staff active status.
     */
    public function toggleStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to update staff status.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ($this->isProtectedStaffAccount($staff)) {
            Response::back('error', 'Super Administrator account status cannot be modified here.');
        }
        if (!$this->staffIsWithinAvailableConcerns($staff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }

        User::toggleActive($staffId);
        $action = $staff['is_active'] ? 'deactivated' : 'activated';
        AuditService::log(
            'staff',
            'status_change',
            'users',
            $staffId,
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => $staff['is_active'] ? 'Active' : 'Deactivated'
            ],
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => !$staff['is_active'] ? 'Active' : 'Deactivated'
            ]
        );
        Response::redirect(url('admin/staff'), 'success', "Staff member $action successfully.");
    }

    /**
     * Delete staff member.
     */
    public function deleteStaff(string $id): void {
        if (!hasPermission('staff', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($staffId === $currentUserId) {
            Response::back('error', 'You cannot delete your own account.');
        }

        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ($this->isProtectedStaffAccount($staff)) {
            Response::back('error', 'Super Administrator account cannot be deleted.');
        }
        if (!$this->staffIsWithinAvailableConcerns($staff)) {
            Response::error(404, 'Staff member not found.');
            return;
        }

        try {
            $delSnapshot = $this->prepareStaffSnapshot([], $staff);
            User::delete($staffId);
            AuditService::log('staff', 'delete', 'users', $staffId, $delSnapshot, null);
            Response::redirect(url('admin/staff'), 'success', "Staff member '{$staff['full_name']}' deleted successfully.");
        } catch (Exception $e) {
            error_log("Error deleting staff: " . $e->getMessage());
            Response::back('error', 'Failed to delete staff member. Make sure no dependent records exist.');
        }
    }

    /**
     * Roles List.
     */
    public function roles(): void {
        if (!hasPermission('roles', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view roles.');
            return;
        }

        $roles = Role::getAll();
        $roles = Database::fetchAll("SELECT * FROM roles WHERE id != 1 ORDER BY name ASC");
        // Count users per role
        foreach ($roles as &$role) {
            $role['user_count'] = Role::getUserCount($role['id']);
        }

        Response::view('admin.roles_list', [
            'pageTitle'    => 'Roles & Permissions',
            'activeModule' => 'staff',
            'activeMenu'   => 'roles',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles & Permissions'],
            ],
            'roles'        => $roles,
        ]);
    }

    /**
     * Create role form.
     */
    public function createRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
        }

        Response::view('admin.role_form', [
            'pageTitle'              => 'Add New Role',
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Create'],
            ],
            'role'                   => null,
            'staff'                  => null,
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
            'assignedPermissionIds'  => [],
            'concerns'               => Database::fetchAll("SELECT id, name, code, is_active FROM concerns ORDER BY id ASC"),
        ]);
    }

    /**
     * Store new role.
     */
    public function storeRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $name               = trim($_POST['name'] ?? '');
        $slugInput          = trim($_POST['slug'] ?? '');
        $description        = trim($_POST['description'] ?? '');
        $concernScope       = trim($_POST['concern_scope'] ?? 'ALL');
        $applicableConcerns = $_POST['applicable_concerns'] ?? [];
        $isActive           = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds      = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
        }

        // Prepare unique role slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . time();
        $count = 1;
        while (Database::fetchOne("SELECT id FROM roles WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            $roleId = Role::create([
                'name'               => $name,
                'slug'               => $slug,
                'description'        => $description,
                'concern_scope'      => $concernScope,
                'applicable_concerns'=> $applicableConcerns,
                'is_active'          => $isActive
            ]);

            // Save selected permissions
            if (!empty($permissionIds)) {
                Role::savePermissions($roleId, $permissionIds);
            }

            AuditService::log('roles', 'create', 'roles', $roleId, null, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' created successfully with assigned permissions.");
        } catch (Exception $e) {
            error_log("Error creating role: " . $e->getMessage());
            Response::back('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * View role details and assigned permissions (read-only).
     */
    public function viewRole(string $id): void {
        if (!hasPermission('roles', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view roles.');
            return;
        }

        $role = Role::findById((int)$id);
        if (!$role) {
            Response::redirect(url('admin/roles'), 'error', 'Role not found.');
            return;
        }

        $assignedUsers = Database::fetchAll(
            "SELECT id, full_name, username, is_active FROM users WHERE role_id = ? ORDER BY full_name ASC",
            [(int)$id]
        );

        Response::view('admin.role_view', [
            'pageTitle'           => 'View Role: ' . $role['name'],
            'activeMenu'          => 'roles',
            'role'                => $role,
            'rolePermissions'     => Role::getPermissionIds((int)$id),
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'assignedUsers'       => $assignedUsers,
        ]);
    }

    /**
     * Edit role form.
     */
    public function editRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) Response::error(404, 'Role not found.');

        $assignedPermissionIds = Role::getRolePermissionIds((int) $id);

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
        }

        Response::view('admin.role_form', [
            'pageTitle'              => 'Edit Role: ' . ($role['name'] ?? ''),
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Edit'],
            ],
            'role'                   => $role,
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
            'assignedPermissionIds'  => $assignedPermissionIds,
            'concerns'               => Database::fetchAll("SELECT id, name, code, is_active FROM concerns ORDER BY id ASC"),
        ]);
    }

    /**
     * Update role.
     */
    public function updateRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $roleId             = (int) $id;
        $role               = Role::findById($roleId);
        if (!$role) Response::error(404, 'Role not found.');

        $name               = trim($_POST['name'] ?? '');
        $slugInput          = trim($_POST['slug'] ?? '');
        $description        = trim($_POST['description'] ?? '');
        $concernScope       = trim($_POST['concern_scope'] ?? 'ALL');
        $applicableConcerns = $_POST['applicable_concerns'] ?? [];
        $isActive           = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds      = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
        }

        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . $roleId;
        $count = 1;
        while ($existing = Database::fetchOne("SELECT id FROM roles WHERE slug = ? AND id != ?", [$slug, $roleId])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            Role::update($roleId, [
                'name'               => $name,
                'slug'               => $slug,
                'description'        => $description,
                'concern_scope'      => $concernScope,
                'applicable_concerns'=> $applicableConcerns,
                'is_active'          => $isActive
            ]);

            // Sync updated permissions
            Role::savePermissions($roleId, $permissionIds);

            AuditService::log('roles', 'update', 'roles', $roleId, $role, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' updated successfully.");
        } catch (Exception $e) {
            error_log("Error updating role: " . $e->getMessage());
            Response::back('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Manage role permissions.
     */
    public function managePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to manage permissions.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) Response::error(404, 'Role not found.');

        $groupedPermissions = Role::getPermissionsGrouped();
        $hierarchicalModules = Role::getHierarchicalPermissions();
        $rolePermissionIds = Role::getRolePermissionIds((int) $id);

        Response::view('admin.permissions', [
            'pageTitle'           => 'Manage Permissions — ' . $role['name'],
            'activeModule'        => 'staff',
            'activeMenu'          => 'roles',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Permissions'],
            ],
            'role'                => $role,
            'groupedPermissions'  => $groupedPermissions,
            'hierarchicalModules' => $hierarchicalModules,
            'rolePermissionIds'   => $rolePermissionIds,
        ]);
    }

    /**
     * Save role permissions.
     */
    public function savePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to save permissions.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $roleId = (int) $id;
        $permissionIds = $_POST['permissions'] ?? [];

        try {
            Role::savePermissions($roleId, $permissionIds);
            AuditService::log('roles', 'update_permissions', 'roles', $roleId, null, ['permissions' => $permissionIds]);
            Response::redirect(url('admin/roles'), 'success', 'Permissions updated successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to update permissions.');
        }
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $id): void {
        if (!hasPermission('roles', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid security token.');
        }

        $roleId = (int)$id;
        if ($roleId === 1) {
            Response::back('error', 'Super Admin role cannot be deleted.');
        }

        try {
            Role::delete($roleId);
            AuditService::log('roles', 'delete', 'roles', $roleId);
            Response::redirect(url('admin/roles'), 'success', 'Role deleted successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Universal QR & Barcode Status Finder API endpoint.
     */
    public function ajaxQrStatusFinder(): void {
        $rawCode = trim((string)($_GET['code'] ?? ''));
        if ($rawCode === '') {
            Response::json([
                'success' => false,
                'message' => 'Please provide a valid QR code or barcode to scan.'
            ], 400);
            return;
        }

        try {
            $result = QrFinderService::trace($rawCode);
            if (empty($result['success'])) {
                Response::json($result, 404);
                return;
            }
            Response::json($result);
        } catch (Throwable $e) {
            error_log("Universal QR Finder API error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => "This QR code ({$rawCode}) is not in any inventory sections.",
                'code'    => $rawCode
            ], 404);
        }
    }
}
