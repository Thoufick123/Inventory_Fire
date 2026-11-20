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
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/style.css') ? filemtime(BASE_PATH . '/public/assets/css/style.css') : '1.1' ?>" rel="stylesheet">
    <link href="<?= asset('css/reports.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/reports.css') ? filemtime(BASE_PATH . '/public/assets/css/reports.css') : '1' ?>" rel="stylesheet">
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

                    <!-- Production -->
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
                        || ($modProduction && (hasPermission('production', 'view') || hasPermission('damages', 'view') || hasPermission('catalog', 'view') || hasPermission('variants', 'view') || hasPermission('qr_generation', 'view') || hasPermission('programs', 'view') || hasPermission('cutting', 'view') || hasPermission('wastage', 'view') || hasPermission('stitching', 'view') || hasPermission('stitching_wastage', 'view') || hasPermission('qc', 'view')))
                        || ($modWarehouse && (hasPermission('inventory', 'view') || hasPermission('inward', 'view') || hasPermission('batches', 'view') || hasPermission('outward', 'view') || hasPermission('production_stock_requests', 'view') || hasPermission('warehouse_rework', 'view')))
                        || ($modExpenses && hasPermission('production_expenses', 'view'))
                        || ($modRetailPos && hasPermission('production_billing', 'view'))
                        || ($modReturns && hasPermission('production_returns', 'view'))
                    );
                    ?>
                    <?php if ($canViewProduction): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'production' ? 'active' : '' ?>">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>Production</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'production' ? 'show' : '' ?>">
                            <?php if ($modPurchases && hasPermission('sellers', 'view')): ?>
                            <li><a href="<?= url('production/sellers') ?>" class="<?= ($activeMenu ?? '') === 'prod-sellers' ? 'active' : '' ?>">Suppliers &amp; Mills</a></li>
                            <?php endif; ?>
                            <?php if ($modPurchases && (hasPermission('production_purchases', 'view') || hasPermission('purchases', 'view'))): ?>
                            <li><a href="<?= url('production/purchases') ?>" class="<?= in_array($activeMenu ?? '', ['purchases', 'prod-purchases']) ? 'active' : '' ?>">Purchases</a></li>
                            <?php endif; ?>
                            <?php if ($modPurchases && hasPermission('production_purchase_mapping', 'view')): ?>
                            <li><a href="<?= url('production/purchases/mapping') ?>" class="<?= in_array($activeMenu ?? '', ['purchases-mapping', 'prod-purchases-mapping']) ? 'active' : '' ?>">Product Mapping</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('damages', 'view')): ?>
                            <li><a href="<?= url('production/damages') ?>" class="<?= ($activeMenu ?? '') === 'prod-damages' ? 'active' : '' ?>">Damage Management</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('catalog', 'view')): ?>
                            <li><a href="<?= url('production/catalog') ?>" class="<?= ($activeMenu ?? '') === 'prod-catalog' ? 'active' : '' ?>">Product Catalog</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && (hasPermission('variants', 'view') || hasPermission('catalog', 'view'))): ?>
                            <li><a href="<?= url('production/variants') ?>" class="<?= ($activeMenu ?? '') === 'prod-variants' ? 'active' : '' ?>">Product Variants</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && (hasPermission('qr_generation', 'view') || hasPermission('catalog', 'view'))): ?>
                            <li><a href="<?= url('production/qr-generations') ?>" class="<?= ($activeMenu ?? '') === 'prod-qr-generation' ? 'active' : '' ?>">QR Generations</a></li>
                            <li><a href="<?= url('production/purchased-stock') ?>" class="<?= ($activeMenu ?? '') === 'prod-purchased-stock' ? 'active' : '' ?>">Purchased Stock</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('programs', 'view')): ?>
                            <li><a href="<?= url('production/programs') ?>" class="<?= ($activeMenu ?? '') === 'programs' ? 'active' : '' ?>">Production Programs</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('cutting', 'view')): ?>
                            <li><a href="<?= url('production/cutting') ?>" class="<?= ($activeMenu ?? '') === 'cutting' ? 'active' : '' ?>">Cutting Unit</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('wastage', 'view')): ?>
                            <li><a href="<?= url('production/wastage') ?>" class="<?= ($activeMenu ?? '') === 'wastage-selling' ? 'active' : '' ?>">Fabric Wastage Sales</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('stitching', 'view')): ?>
                            <li><a href="<?= url('production/stitching') ?>" class="<?= ($activeMenu ?? '') === 'stitching' ? 'active' : '' ?>">Stitching Unit</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('stitching_wastage', 'view')): ?>
                            <li><a href="<?= url('production/stitching-wastage') ?>" class="<?= ($activeMenu ?? '') === 'stitching-wastage' ? 'active' : '' ?>">Stitching Wastage Sales</a></li>
                            <?php endif; ?>
                            <?php if ($modProduction && hasPermission('qc', 'view')): ?>
                            <li><a href="<?= url('production/qc') ?>" class="<?= ($activeMenu ?? '') === 'qc' ? 'active' : '' ?>">Quality Control (QC)</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && hasPermission('inventory', 'view')): ?>
                            <li><a href="<?= url('production/inventory') ?>" class="<?= ($activeMenu ?? '') === 'prod-inventory' ? 'active' : '' ?>">Finished Goods Inventory</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && (hasPermission('inward', 'view') || hasPermission('inventory', 'view'))): ?>
                            <li><a href="<?= url('production/inwards') ?>" class="<?= in_array($activeMenu ?? '', ['inwards', 'prod-inwards']) ? 'active' : '' ?>">Goods Inward (GRN)</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && (hasPermission('production_stock_requests', 'view') || hasPermission('stock_requests', 'view') || hasPermission('inventory', 'view'))): ?>
                            <li><a href="<?= url('production/stock-requests') ?>" class="<?= in_array($activeMenu ?? '', ['prod-stock-requests', 'stock-requests', 'stock-requirements']) ? 'active' : '' ?>">Stock Requirements</a></li>
                            <?php endif; ?>
                            <?php if ($modExpenses && hasPermission('production_expenses', 'view')): ?>
                            <li><a href="<?= url('production/expenses') ?>" class="<?= ($activeMenu ?? '') === 'production-expenses' ? 'active' : '' ?>">Production Expenses</a></li>
                            <?php endif; ?>
                            <?php if ($modRetailPos && hasPermission('production_billing', 'view')): ?>
                            <li><a href="<?= url('production/billing') ?>" class="<?= ($activeMenu ?? '') === 'prod-billing' ? 'active' : '' ?>">Production Billing / POS</a></li>
                            <?php endif; ?>
                            <?php if ($modReturns && hasPermission('production_returns', 'view')): ?>
                            <li><a href="<?= url('production/returns') ?>" class="<?= ($activeMenu ?? '') === 'prod-returns' ? 'active' : '' ?>">Customer Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && hasPermission('batches', 'view')): ?>
                            <li><a href="<?= url('production/batches') ?>" class="<?= in_array($activeMenu ?? '', ['batches', 'prod-batches']) ? 'active' : '' ?>">Packing Batches</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && hasPermission('outward', 'view')): ?>
                            <li><a href="<?= url('production/outward') ?>" class="<?= in_array($activeMenu ?? '', ['outward', 'prod-outward', 'batch-outward']) ? 'active' : '' ?>">Warehouse Outward Dispatch</a></li>
                            <?php endif; ?>
                            <?php if ($modWarehouse && hasPermission('warehouse_rework', 'view')): ?>
                            <li><a href="<?= url('production/warehouse-rework') ?>" class="<?= ($activeMenu ?? '') === 'warehouse-rework' ? 'active' : '' ?>">Warehouse Rework</a></li>
                            <?php endif; ?>
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

                    <!-- Concerns & Staff Module -->
                    <?php 
                    $canViewConcerns = isSuperAdmin() || hasPermission('settings', 'view') || hasPermission('concerns', 'view');
                    $canViewStaff = hasPermission('staff', 'view') || hasPermission('roles', 'view') || hasPermission('staff', 'create');
                    ?>
                    <?php if ($canViewConcerns || $canViewStaff): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= in_array(($activeModule ?? ''), ['concerns', 'staff']) ? 'active' : '' ?>">
                            <i class="bi bi-buildings"></i>
                            <span>Concerns</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= in_array(($activeModule ?? ''), ['concerns', 'staff']) ? 'show' : '' ?>">
                            <?php if ($canViewConcerns): ?>
                            <li><a href="<?= url('admin/concerns') ?>" class="<?= in_array(($activeMenu ?? ''), ['concerns', 'admin-concerns', 'concerns-list']) ? 'active' : '' ?>">All Concerns</a></li>
                            <li><a href="<?= url('admin/concerns/create') ?>" class="<?= ($activeMenu ?? '') === 'concerns-create' ? 'active' : '' ?>">Add New Concern</a></li>
                            <?php endif; ?>

                            <?php if ($canViewStaff): ?>
                            <li class="sidebar-sub-heading text-uppercase px-3 pt-2 pb-1 text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">Staff &amp; Permissions</li>
                            <?php if (hasPermission('staff', 'create')): ?>
                            <li><a href="<?= url('admin/staff/create') ?>" class="<?= ($activeMenu ?? '') === 'staff-create' ? 'active' : '' ?>">Add Staff</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('staff', 'view')): ?>
                            <li><a href="<?= url('admin/staff?department=all') ?>" class="<?= in_array(($activeMenu ?? ''), ['staff', 'staff-all', 'staff-production', 'staff-general']) ? 'active' : '' ?>">Total Staffs</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('roles', 'view')): ?>
                            <li><a href="<?= url('admin/roles') ?>" class="<?= ($activeMenu ?? '') === 'roles' ? 'active' : '' ?>">Role Assign / Permissions</a></li>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php 
                            $sideConcerns = getAvailableUserConcerns();
                            if (!empty($sideConcerns)):
                            ?>
                            <li class="sidebar-sub-heading text-uppercase px-3 pt-2 pb-1 text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">Switch Entity</li>
                            <?php foreach ($sideConcerns as $sc): 
                                $isCurSc = ((int)currentConcernId() === (int)$sc['id']);
                            ?>
                            <li>
                                <a href="<?= url('system/concerns/switch/' . $sc['id']) ?>" class="d-flex align-items-center justify-content-between py-1 px-3 <?= $isCurSc ? 'active text-primary fw-bold' : '' ?>" title="Switch context to <?= e($sc['name']) ?>">
                                    <span class="text-truncate" style="max-width: 130px;"><i class="bi bi-building me-1.5 opacity-75"></i><?= e($sc['name']) ?></span>
                                    <span class="badge <?= $isCurSc ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>" style="font-size: 9px;"><?= e($sc['code']) ?></span>
                                </a>
                            </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </li>

                    <li class="nav-divider"></li>
                    <?php endif; ?>

                    <li class="nav-divider"></li>

                    <!-- System -->
                    <?php 
                    $canViewSystem = hasPermission('settings', 'view') 
                        || hasPermission('audit', 'view')
                        || hasPermission('backup', 'view');
                    ?>
                    <?php if ($canViewSystem): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'system' ? 'active' : '' ?>">
                            <i class="bi bi-sliders"></i>
                            <span>System</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'system' ? 'show' : '' ?>">
                            <?php if (isSuperAdmin() || hasPermission('settings', 'view')): ?>
                            <li><a href="<?= url('admin/concerns') ?>" class="<?= in_array($activeMenu ?? '', ['concerns', 'admin-concerns']) ? 'active' : '' ?>"><i class="bi bi-buildings me-2 text-primary"></i>Business Concerns</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('audit', 'view')): ?>
                            <li><a href="<?= url('system/audit') ?>">Audit Logs</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('settings', 'view')): ?>
                            <li><a href="<?= url('system/settings') ?>">Settings</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('backup', 'view')): ?>
                            <li><a href="<?= url('system/backup') ?>">Backup</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
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
                    ?>
                    <div class="dropdown me-1">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-2 shadow-xs fw-semibold"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Switch Business Concern">
                            <i class="bi bi-buildings"></i>
                            <span class="d-none d-sm-inline"><?= e($currConcern['name'] ?? 'Select Concern') ?></span>
                            <span class="badge bg-primary text-white ms-1 font-monospace"><?= e($currConcern['code'] ?? 'CN-01') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border py-2" style="min-width: 270px;">
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted py-1"><i class="bi bi-shuffle me-1"></i>Switch Business Concern</h6></li>
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

            <div class="content-wrapper p-3 p-md-4">
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
