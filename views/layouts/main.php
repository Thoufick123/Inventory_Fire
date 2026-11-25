<?php
// Layout initialization & user/unit context
$authUser = currentUser() ?? Session::user() ?? [];
$isSuperAdminUser = isSuperAdmin();
$userRoleName = $authUser['role_name'] ?? ($isSuperAdminUser ? 'Super Admin' : 'Staff');
$userRoleSlug = strtolower($authUser['role_slug'] ?? ($isSuperAdminUser ? 'super-admin' : ''));
$userDept = $authUser['department'] ?? '';
$isAdminRole = $isSuperAdminUser || ($authUser['role_id'] ?? 0) === 1 || str_contains($userRoleSlug, 'admin');

// Extract all assigned units defined by admin
$userAssignedUnits = [];
if (!empty($authUser['production_unit_name'])) {
    $userAssignedUnits[] = [
        'type'        => 'Production Unit',
        'short_type'  => 'Unit',
        'name'        => $authUser['production_unit_name'],
        'icon'        => 'bi bi-gear-wide-connected',
        'badge_class' => 'bg-primary text-white'
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — GRM Inventory</title>
    <meta name="description" content="GRM Production & Inventory Management System">
    <?= CSRF::metaTag() ?>

    <!-- Fonts & Icons & Bootstrap CSS (Fast Local Assets with Zero External Latency) -->
    <link href="<?= asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/style.css') ? filemtime(BASE_PATH . '/public/assets/css/style.css') : '1.1' ?>" rel="stylesheet">
    <link href="<?= asset('css/reports.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/reports.css') ? filemtime(BASE_PATH . '/public/assets/css/reports.css') : '1' ?>" rel="stylesheet">
    <?php
    if (!class_exists('SettingsService') && defined('BASE_PATH') && file_exists(BASE_PATH . '/app/Services/SettingsService.php')) {
        require_once BASE_PATH . '/app/Services/SettingsService.php';
    }
    $activeTheme = class_exists('SettingsService') ? SettingsService::getThemeConfig() : [
        'primary'       => '#4f46e5',
        'primary_hover' => '#3d34d3',
        'primary_light' => '#ededfc',
        'primary_tint'  => '#9e99f1',
        'primary_rgb'   => '79, 70, 229',
        'sidebar_bg'    => '#1e293b',
        'sidebar_text'  => '#94a3b8',
        'navbar_bg'     => '#ffffff',
        'page_bg'       => '#f8fafc',
    ];
    ?>
    <style id="appDynamicThemeStyles">
        :root {
            --primary: <?= e($activeTheme['primary']) ?>;
            --primary-hover: <?= e($activeTheme['primary_hover']) ?>;
            --primary-light: <?= e($activeTheme['primary_light']) ?>;
            --primary-tint: <?= e($activeTheme['primary_tint']) ?>;
            --primary-rgb: <?= e($activeTheme['primary_rgb']) ?>;
            --sidebar-bg: <?= e($activeTheme['sidebar_bg']) ?>;
            --sidebar-text: <?= e($activeTheme['sidebar_text']) ?>;
            --navbar-bg: <?= e($activeTheme['navbar_bg']) ?>;
            --page-bg: <?= e($activeTheme['page_bg']) ?>;
            --bs-primary: var(--primary);
            --bs-primary-rgb: var(--primary-rgb);
            --bs-link-color: var(--primary);
            --bs-link-hover-color: var(--primary-hover);
            --bs-link-color-rgb: var(--primary-rgb);
        }
        body {
            background-color: var(--page-bg) !important;
        }
        .sidebar {
            background: var(--sidebar-bg) !important;
        }
        .sidebar-brand i,
        .submenu li a.active,
        .sidebar .submenu li a.active.text-primary {
            color: var(--primary-tint, var(--primary)) !important;
        }
        .sidebar .nav-item .nav-link,
        .submenu li a {
            color: var(--sidebar-text);
        }
        .sidebar .sidebar-sub-heading {
            color: var(--sidebar-text) !important;
            opacity: 0.78;
        }
        .sidebar .nav-item .nav-link.active {
            color: #ffffff;
            background: rgba(var(--primary-rgb), 0.22) !important;
            border-left-color: var(--primary) !important;
        }
        .top-navbar {
            background: var(--navbar-bg) !important;
        }
        .btn-primary {
            --bs-btn-bg: var(--primary);
            --bs-btn-border-color: var(--primary);
            --bs-btn-hover-bg: var(--primary-hover);
            --bs-btn-hover-border-color: var(--primary-hover);
            --bs-btn-active-bg: var(--primary-hover);
            --bs-btn-active-border-color: var(--primary-hover);
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
            box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.3);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--primary);
            --bs-btn-border-color: var(--primary);
            --bs-btn-hover-bg: var(--primary);
            --bs-btn-hover-border-color: var(--primary);
            --bs-btn-active-bg: var(--primary);
            --bs-btn-active-border-color: var(--primary);
            color: var(--primary);
            border-color: var(--primary);
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:active {
            color: #ffffff !important;
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .bg-primary {
            background-color: var(--primary) !important;
        }
        .bg-primary-subtle {
            background-color: var(--primary-light) !important;
        }
        .border-primary {
            border-color: var(--primary) !important;
        }
        .text-primary {
            color: var(--primary) !important;
        }
        .nav-pills .nav-link.active,
        .nav-pills .show > .nav-link {
            background-color: var(--primary) !important;
        }
        .page-item.active .page-link {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
        .form-check-input:checked {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
        }
    </style>
    <script>
        window.formatINR = function(value, decimals = 2) {
            const num = Number(value || 0);
            if (!Number.isFinite(num)) return '0';
            const neg = num < 0 ? '-' : '';
            const fixed = Math.abs(num).toFixed(decimals);
            const parts = fixed.split('.');
            const intPart = parts[0];
            const decPart = parts[1] || '';
            const lastThree = intPart.slice(-3);
            const rest = intPart.slice(0, -3);
            const grouped = rest ? rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + lastThree : lastThree;
            if (decimals > 0 && decPart && Number(decPart) !== 0) {
                return neg + grouped + '.' + decPart;
            }
            return neg + grouped;
        };
        window.formatCurrency = function(value, decimals = 2) {
            const num = Number(value || 0);
            if (!Number.isFinite(num)) return '₹0';
            return (num < 0 ? '-₹' : '₹') + window.formatINR(Math.abs(num), decimals);
        };
    </script>
</head>
<body>
    <div class="app-layout">
        <!-- ============================================ -->
        <!-- SIDEBAR -->
        <!-- ============================================ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="bi bi-boxes text-primary fs-4 me-2"></i>
                    <span class="brand-text fw-bold">GRM Inventory</span>
                </div>
                <button class="sidebar-toggle-btn d-lg-none" id="sidebarClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?= url('admin/dashboard') ?>" class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-divider"></li>

                    <!-- All Modules (formerly Production) -->
                    <?php 
                    $modProduction = isSuperAdmin() || hasConcernModule('production');
                    $modPurchases  = isSuperAdmin() || hasConcernModule('purchases');
                    $modWarehouse  = isSuperAdmin() || hasConcernModule('warehouse');
                    $modRetailPos  = isSuperAdmin() || hasConcernModule('retail_pos');
                    $modReturns    = isSuperAdmin() || hasConcernModule('returns');
                    $modExpenses   = isSuperAdmin() || hasConcernModule('expenses');
                    $modAssets     = isSuperAdmin() || hasConcernModule('assets');

                    $canViewProduction = (
                        ($modPurchases && (hasPermission('sellers', 'view') || hasPermission('production_purchases', 'view') || hasPermission('production_purchase_mapping', 'view') || hasPermission('purchases', 'view')))
                        || ($modProduction && (hasPermission('production', 'view') || hasPermission('damages', 'view') || hasPermission('catalog', 'view') || hasPermission('variants', 'view') || hasPermission('qr_generation', 'view') || hasPermission('programs', 'view') || hasPermission('cutting', 'view') || hasPermission('wastage', 'view') || hasPermission('stitching', 'view') || hasPermission('stitching_wastage', 'view') || hasPermission('qc', 'view') || hasPermission('cash_flow', 'view') || hasPermission('shop_cash', 'view') || hasPermission('production_hsn', 'view') || hasPermission('hsn_config', 'view')))
                        || ($modWarehouse && (hasPermission('inventory', 'view') || hasPermission('inward', 'view') || hasPermission('batches', 'view') || hasPermission('outward', 'view') || hasPermission('production_stock_requests', 'view') || hasPermission('warehouse_rework', 'view')))
                        || ($modExpenses && hasPermission('production_expenses', 'view'))
                        || ($modRetailPos && hasPermission('production_billing', 'view'))
                        || ($modReturns && hasPermission('production_returns', 'view'))
                    );
                    ?>
                    <?php if ($canViewProduction): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= in_array($activeModule ?? '', ['allmodules', 'production']) ? 'active' : '' ?>">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>All Modules</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= in_array($activeModule ?? '', ['allmodules', 'production']) ? 'show' : '' ?>">
                            <?php
                            $moduleFlagMap = [
                                'purchases'  => $modPurchases,
                                'production' => $modProduction,
                                'warehouse'  => $modWarehouse,
                                'expenses'   => $modExpenses,
                                'retail_pos' => $modRetailPos,
                                'returns'    => $modReturns,
                            ];
                            foreach (SidebarMenu::getOrderedItemsForUser(Auth::id()) as $navItem):
                                $modEnabled = $moduleFlagMap[$navItem['module']] ?? false;
                                if (!$modEnabled) {
                                    continue;
                                }
                                $canSeeItem = false;
                                foreach ($navItem['permissions'] as $permKey) {
                                    if (hasPermission($permKey, 'view')) {
                                        $canSeeItem = true;
                                        break;
                                    }
                                }
                                if (!$canSeeItem) {
                                    continue;
                                }
                                $isActiveItem = in_array($activeMenu ?? '', $navItem['active_menus'], true);
                            ?>
                            <li><a href="<?= url($navItem['url']) ?>" class="<?= $isActiveItem ? 'active' : '' ?>"><?php if (!empty($navItem['icon'])): ?><i class="<?= e($navItem['icon']) ?>" aria-hidden="true"></i><?php endif; ?><?= e($navItem['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Assets -->
                    <?php
                    $canViewAssets = $modAssets && (
                        hasPermission('asset_register', 'view')
                        || hasPermission('assets', 'view')
                        || hasPermission('asset_register', 'create')
                        || hasPermission('assets', 'create')
                        || hasPermission('asset_maintenance', 'view')
                        || hasPermission('asset_maintenance', 'create')
                    );
                    ?>
                    <?php if ($canViewAssets): ?>
                    <li class="nav-divider"></li>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'assets' ? 'active' : '' ?>">
                            <i class="bi bi-tools"></i>
                            <span>Assets</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'assets' ? 'show' : '' ?>">
                            <?php if (hasPermission('asset_register', 'view') || hasPermission('assets', 'view')): ?>
                            <li><a href="<?= url('assets') ?>" class="<?= ($activeMenu ?? '') === 'assets-list' ? 'active' : '' ?>">Asset Register</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_register', 'create') || hasPermission('assets', 'create')): ?>
                            <li><a href="<?= url('assets/create') ?>" class="<?= ($activeMenu ?? '') === 'assets-create' ? 'active' : '' ?>">Register New Asset</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_maintenance', 'view') || hasPermission('assets', 'view')): ?>
                            <li><a href="<?= url('assets/maintenance') ?>" class="<?= ($activeMenu ?? '') === 'assets-maintenance' ? 'active' : '' ?>">Maintenance &amp; Services</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_maintenance', 'create') || hasPermission('assets', 'create')): ?>
                            <li><a href="<?= url('assets/maintenance/create') ?>" class="<?= ($activeMenu ?? '') === 'assets-maintenance-create' ? 'active' : '' ?>">Record Maintenance</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li class="nav-divider"></li>

                    <!-- Export -->
                    <?php 
                    $canViewExport = hasPermission('reports', 'view') 
                        || hasPermission('reports', 'export') 
                        || hasPermission('reports_production', 'export')
                        || isSuperAdmin();
                    ?>
                    <?php if ($canViewExport): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'export' ? 'active' : '' ?>">
                            <i class="bi bi-file-earmark-arrow-down"></i>
                            <span>Export</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'export' ? 'show' : '' ?>">
                            <li><a href="<?= url('export') ?>" class="<?= in_array(($activeMenu ?? ''), ['export', 'export-hub']) ? 'active' : '' ?>">Data Export Center</a></li>
                            <li><a href="<?= url('export/quick-csv') ?>" class="<?= ($activeMenu ?? '') === 'export-quick' ? 'active' : '' ?>">Current Month CSV</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li class="nav-divider"></li>

                    <!-- Admin & Concerns Module -->
                    <?php 
                    $canViewAdmin = isSuperAdmin() 
                        || hasPermission('settings', 'view') 
                        || hasPermission('concerns', 'view') 
                        || hasPermission('staff', 'view') 
                        || hasPermission('roles', 'view') 
                        || hasPermission('staff', 'create')
                        || hasPermission('audit', 'view')
                        || hasPermission('backup', 'view');
                    $canViewConcerns = isSuperAdmin() || hasPermission('settings', 'view') || hasPermission('concerns', 'view');
                    $canViewStaff    = isSuperAdmin() || hasPermission('staff', 'view') || hasPermission('roles', 'view') || hasPermission('staff', 'create');
                    $canViewSystem   = isSuperAdmin() || hasPermission('settings', 'view') || hasPermission('audit', 'view') || hasPermission('backup', 'view');
                    ?>
                    <?php if ($canViewAdmin): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= in_array(($activeModule ?? ''), ['admin', 'concerns', 'staff', 'system']) ? 'active' : '' ?>">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= in_array(($activeModule ?? ''), ['admin', 'concerns', 'staff', 'system']) ? 'show' : '' ?>">
                            <?php if ($canViewConcerns): ?>
                            <li class="sidebar-sub-heading text-uppercase px-3 pt-2 pb-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">Business Concerns</li>
                            <li><a href="<?= url('admin/concerns') ?>" class="<?= in_array(($activeMenu ?? ''), ['concerns', 'admin-concerns', 'concerns-list']) ? 'active' : '' ?>">All Concerns</a></li>
                            <li><a href="<?= url('admin/concerns/create') ?>" class="<?= ($activeMenu ?? '') === 'concerns-create' ? 'active' : '' ?>">Add New Concern</a></li>
                            <?php endif; ?>

                            <?php if ($canViewStaff): ?>
                            <li class="sidebar-sub-heading text-uppercase px-3 pt-2 pb-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">Staff &amp; Permissions</li>
                            <li><a href="<?= url('admin/staff?department=all') ?>" class="<?= in_array(($activeMenu ?? ''), ['staff', 'staff-all', 'staff-production', 'staff-general']) ? 'active' : '' ?>">Total Staffs</a></li>
                            <li><a href="<?= url('admin/staff/create') ?>" class="<?= ($activeMenu ?? '') === 'staff-create' ? 'active' : '' ?>">Add Staff</a></li>
                            <li><a href="<?= url('admin/roles') ?>" class="<?= ($activeMenu ?? '') === 'roles' ? 'active' : '' ?>">Role Assign / Permissions</a></li>
                            <?php endif; ?>

                            <?php if ($canViewSystem): ?>
                            <li class="sidebar-sub-heading text-uppercase px-3 pt-2 pb-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">System &amp; Settings</li>
                            <li><a href="<?= url('system/audit') ?>" class="<?= in_array(($activeMenu ?? ''), ['audit', 'system-audit']) ? 'active' : '' ?>">Audit Logs</a></li>
                            <li><a href="<?= url('system/settings') ?>" class="<?= in_array(($activeMenu ?? ''), ['settings', 'system-settings']) ? 'active' : '' ?>">Settings</a></li>
                            <li><a href="<?= url('system/backup') ?>" class="<?= in_array(($activeMenu ?? ''), ['backup', 'system-backup']) ? 'active' : '' ?>">Backup</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-divider"></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- ============================================ -->
        <!-- MAIN CONTENT -->
        <!-- ============================================ -->
        <main class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <!-- Breadcrumbs -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-1">
                            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none"><i class="bi bi-house-door text-primary"></i></a></li>
                            <?php if (!empty($breadcrumb) && is_array($breadcrumb)): ?>
                                <?php foreach ($breadcrumb as $bc): ?>
                                    <?php if (!empty($bc['url'])): ?>
                                        <li class="breadcrumb-item"><a href="<?= $bc['url'] ?>" class="text-decoration-none"><?= e($bc['label']) ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active" aria-current="page"><?= e($bc['label']) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>

                <div class="navbar-right d-flex align-items-center gap-2">
                    <!-- Concern Switcher Dropdown in Top Navbar (concern.pdf Section 3.2) -->
                    <?php 
                    $currConcern = currentConcern();
                    $availConcerns = getAvailableUserConcerns();
                    $isGlobalActive = (currentConcernId() === null);
                    ?>
                    <div class="dropdown me-1">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-2 shadow-xs fw-semibold"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch Business Concern">
                            <?php if ($isGlobalActive): ?>
                                <i class="bi bi-shield-lock-fill text-warning"></i>
                                <span class="d-none d-sm-inline">Global Admin</span>
                                <span class="badge bg-dark text-white ms-1 font-monospace">ADMIN</span>
                            <?php else: ?>
                                <i class="bi bi-buildings"></i>
                                <span class="d-none d-sm-inline"><?= e($currConcern['name'] ?? 'Select Concern') ?></span>
                                <span class="badge bg-primary text-white ms-1 font-monospace"><?= e($currConcern['code'] ?? 'CN-01') ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border py-2" style="min-width: 270px;">
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted py-1"><i class="bi bi-shuffle me-1"></i>Switch Context / Concern</h6></li>
                            <?php if (isSuperAdmin()): ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center py-2 px-3 <?= $isGlobalActive ? 'active bg-dark text-white fw-bold' : '' ?>"
                                       href="<?= url('system/concerns/switch/0') ?>">
                                        <span class="text-truncate me-2"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Global Admin (All Concerns)</span>
                                        <span class="badge <?= $isGlobalActive ? 'bg-light text-dark' : 'bg-secondary text-white' ?> font-monospace">ADMIN</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <?php foreach ($availConcerns as $c): 
                                $isActiveC = ((int)currentConcernId() === (int)$c['id']);
                            ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center py-2 px-3 <?= $isActiveC ? 'active bg-primary text-white fw-bold' : '' ?>"
                                       href="<?= url('system/concerns/switch/' . $c['id']) ?>">
                                        <span class="text-truncate me-2"><i class="bi bi-building me-2"></i><?= e($c['name']) ?></span>
                                        <span class="badge <?= $isActiveC ? 'bg-light text-primary' : 'bg-secondary-subtle text-secondary border' ?> font-monospace"><?= e($c['code']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (isSuperAdmin() || hasPermission('settings', 'view')): ?>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item small text-primary py-1.5 px-3 fw-semibold" href="<?= url('admin/concerns') ?>">
                                        <i class="bi bi-gear me-2"></i>Manage Business Concerns
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-dark shadow-sm fw-semibold d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#qrStatusFinderModal">
                        <i class="bi bi-qr-code-scan text-primary"></i>
                        <span class="d-none d-md-inline">QR Finder</span>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2.5 shadow-xs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px; font-weight: 600;">
                                <?= strtoupper(substr($authUser['full_name'] ?? $authUser['username'] ?? 'A', 0, 1)) ?>
                            </div>
                            <span class="d-none d-md-inline fw-semibold text-dark small"><?= e($authUser['full_name'] ?? $authUser['username'] ?? 'Admin') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border py-1.5 mt-1">
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted"><?= e($userRoleName) ?></h6></li>
                            <li><a class="dropdown-item py-1.5" href="<?= url('admin/change-password') ?>"><i class="bi bi-key me-2 text-primary"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-1.5 text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="content-wrapper flex-grow-1 p-3 p-md-4 px-xl-5">
                <!-- Flash Alerts -->
                <?php
                $alertTypes = ['success', 'error', 'warning', 'info'];
                foreach ($alertTypes as $type):
                    $messages = Session::getFlash($type);
                    if (!$messages) continue;
                    if (!is_array($messages)) $messages = [$messages];
                    foreach ($messages as $message):
                        $alertConfig = match ($type) {
                            'success' => ['icon' => 'check-circle-fill', 'class' => 'alert-success', 'title' => 'Success'],
                            'error'   => ['icon' => 'exclamation-octagon-fill', 'class' => 'alert-danger', 'title' => 'Error'],
                            'warning' => ['icon' => 'exclamation-triangle-fill', 'class' => 'alert-warning', 'title' => 'Warning'],
                            default   => ['icon' => 'info-circle-fill', 'class' => 'alert-info', 'title' => 'System Information'],
                        };
                ?>
                    <div class="alert <?= $alertConfig['class'] ?> alert-dismissible fade show shadow-sm mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-<?= $alertConfig['icon'] ?> fs-5 me-2"></i>
                            <div>
                                <strong><?= $alertConfig['title'] ?>:</strong> <?= e($message) ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Main Content Slot -->
                <?= $content ?? '' ?>
            </div>

            <!-- Footer -->
            <footer class="app-footer">
                <span>© <?= date('Y') ?> GRM Inventory Management. All rights reserved.</span>
                <span>v1.0</span>
            </footer>
        </main>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Bootstrap 5 JS -->
    <script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script>if (typeof bootstrap === 'undefined') document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"><\/script>');</script>
    <!-- QRCode Generator JS -->
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>if (typeof QRCode === 'undefined') document.write('<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>');</script>
    <!-- HTML5 QR / Barcode Scanner JS -->
    <script src="<?= asset('js/html5-qrcode.min.js') ?>"></script>
    <script>if (typeof Html5Qrcode === 'undefined') document.write('<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"><\/script>');</script>
    <!-- Custom JS -->
    <script src="<?= asset('js/app.js') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/js/app.js') ? filemtime(BASE_PATH . '/public/assets/js/app.js') : '1.1' ?>"></script>
    <!-- Ultra-Fast Client Image Compressor -->
    <script src="<?= asset('js/image-compressor.js') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/js/image-compressor.js') ? filemtime(BASE_PATH . '/public/assets/js/image-compressor.js') : '1.0' ?>"></script>
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= asset('js/' . $script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <!-- Global Universal QR Status Finder Modal -->
    <?php require BASE_PATH . '/views/partials/qr_status_finder_modal.php'; ?>
    <!-- Global Modern Confirmation Modal -->
    <?php require BASE_PATH . '/views/partials/confirm_modal.php'; ?>
</body>
</html>
