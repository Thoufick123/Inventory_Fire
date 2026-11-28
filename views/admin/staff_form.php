<?php
/**
 * Staff Form View (Create & Edit) with Dynamic Role Permissions Matrix
 */
$isEdit = !empty($staff['id']);
$formAction = $isEdit ? url('admin/staff/update/' . $staff['id']) : url('admin/staff/store');
$dept = strtoupper($_GET['department'] ?? $staff['department'] ?? 'ALL');
if (!in_array($dept, ['PRODUCTION', 'GENERAL', 'ALL'])) {
    $dept = 'ALL';
}

$hierarchicalModules = $hierarchicalModules ?? Role::getHierarchicalPermissions();
$rolePermissionsMap = $rolePermissionsMap ?? Role::getAllRolePermissionsMap();
$staffPermissions = array_map('intval', $staffPermissions ?? []);
$staffPermissions = Role::filterCrudPermissionIds($staffPermissions);

$totalPossiblePerms = 0;
foreach ($hierarchicalModules as $m) {
    foreach ($m['submodules'] as $s) {
        $totalPossiblePerms += array_sum(array_map(
            'count',
            Role::getCrudPermissionsByAction($s['permissions'] ?? [])
        ));
    }
}
?>
<style>
.perm-checkbox,
.row-all-toggle,
.concern-checkbox {
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid #64748b !important;
    cursor: pointer;
}
.perm-checkbox:hover,
.row-all-toggle:hover,
.concern-checkbox:hover {
    border-color: #0d6efd !important;
}
.perm-checkbox:checked,
.row-all-toggle:checked,
.concern-checkbox:checked {
    border-color: #0d6efd !important;
    background-color: #0d6efd !important;
}
</style>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><?= $isEdit ? 'Edit Staff Member' : 'Create New Staff Member' ?></h1>
            <p class="text-muted mb-0"><?= $isEdit ? 'Update account details, role assignment, and View, Create, Edit, or Delete access.' : 'Add a team member and assign View, Create, Edit, or Delete access.' ?></p>
        </div>
        <div>
            <a href="<?= url('admin/staff') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Staff Directory</span>
            </a>
        </div>
    </div>

    <!-- Main Form -->
    <form method="POST" action="<?= $formAction ?>" id="staffForm">
        <?= CSRF::field() ?>
        <input type="hidden" name="permissions[]" value="">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">

                <!-- 1. Personal Details & Credentials Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bi <?= $isEdit ? 'bi-person-gear' : 'bi-person-plus' ?> text-primary me-2"></i>
                            Personal Details &amp; Credentials
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($staff['full_name'] ?? '') ?>" required>
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">@</span>
                                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. rahul_sharma" value="<?= e($staff['username'] ?? '') ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
                                </div>
                                <?php if ($isEdit): ?>
                                    <div class="form-text text-muted">Username cannot be changed after creation.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="rahul@example.com" value="<?= e($staff['email'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="col-12 col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="phone" class="form-label fw-semibold mb-0">
                                        <i class="bi bi-telephone text-primary me-1"></i>Phone Number
                                    </label>
                                    <span id="phoneCounter" class="badge bg-light text-muted border font-monospace">0 / 10 digits</span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold text-secondary">+91</span>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           class="form-control font-monospace" 
                                           placeholder="10-digit mobile number" 
                                           value="<?= e($staff['phone'] ?? '') ?>"
                                           maxlength="10"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           data-staff-id="<?= e($staff['id'] ?? '') ?>">
                                    <span class="input-group-text bg-light" id="phoneStatusIcon">
                                        <i class="bi bi-hash text-muted"></i>
                                    </span>
                                </div>
                                <div id="phoneFeedback" class="small mt-1 text-danger fw-semibold d-none"></div>
                                <div id="phoneSuccess" class="small mt-1 text-success fw-semibold d-none"></div>
                                <div class="form-text text-muted small">
                                    Only 10-digit numeric mobile numbers (starting with 6-9). Letters and duplicate numbers are rejected.
                                </div>
                            </div>

                            <!-- System Role -->
                            <div class="col-12 col-md-6">
                                <label for="role_id" class="form-label fw-semibold">Assign System Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                                    <select id="role_id" name="role_id" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <?php foreach ($roles as $r): ?>
                                            <?php
                                             $rDept = $r['primary_department'] ?? 'ALL';
                                             $rDepts = $r['departments'] ?? [];
                                             $rDeptsJson = htmlspecialchars(json_encode($rDepts), ENT_QUOTES, 'UTF-8');
                                             $badgeLabel = match($rDept) {
                                                 'PRODUCTION' => '🏭 Production',
                                                 'WAREHOUSE'  => '🏢 Warehouse',
                                                 'WHATSAPP'   => '💬 WhatsApp',
                                                 'WEBSITE'    => '🌐 Website',
                                                 'SHOP'       => '🛍️ Shop',
                                                 default      => '🌐 All Modules'
                                             };
                                             ?>
                                            <option value="<?= $r['id'] ?>" 
                                                    data-primary-dept="<?= e($rDept) ?>"
                                                    data-departments='<?= $rDeptsJson ?>'
                                                    data-concern-scope="<?= e($r['concern_scope'] ?? 'ALL') ?>"
                                                    data-applicable-concerns='<?= e($r['applicable_concerns'] ?? '[]') ?>'
                                                    data-name="<?= e($r['name']) ?>"
                                                    <?= (string)($staff['role_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>>
                                                <?= e($r['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Real-time Department Validation Alert -->
                                <div id="role_mismatch_warning" class="alert alert-warning d-none py-2 px-3 mt-2 small d-flex align-items-center gap-2 mb-0 rounded-3 shadow-xs">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                                    <div>
                                        <strong>Department Mismatch:</strong> 
                                        <span id="role_mismatch_msg">The selected role has no access permissions for this department.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label fw-semibold">
                                    Password <?= $isEdit ? '<span class="text-muted fw-normal">(Leave blank to keep unchanged)</span>' : '<span class="text-danger">*</span>' ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="<?= $isEdit ? '••••••••' : 'Min 6 characters' ?>" <?= $isEdit ? '' : 'required' ?>>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="department" id="selected_department" value="<?= e($dept ?: 'ALL') ?>">

                                <!-- Status Toggle (Only on Edit) -->
                            <?php if ($isEdit): ?>
                                <div class="col-12 pt-2">
                                    <div class="form-check form-switch card p-3 bg-light border-0">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= ($staff['is_active'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="is_active">
                                            Active Account
                                            <span class="d-block text-muted fw-normal small">Inactive users cannot log into the system and are immediately logged out if deactivated.</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 2. BUSINESS CONCERN ASSIGNMENT & ACCESS RESTRICTIONS -->
                <!-- ========================================================================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-1 fw-bold d-flex align-items-center gap-2">
                                    <i class="bi bi-buildings-fill text-primary"></i>
                                    Business Concern Assignment &amp; Access Restrictions
                                </h5>
                                <p class="text-muted small mb-0">Authorize the business entities/sister concerns this staff member is permitted to operate. Staff access is strictly partitioned to checked concerns.</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill">
                                <i class="bi bi-shield-check me-1"></i>Multi-Entity Scoped
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Authorized Concerns Checkboxes -->
                        <label class="form-label fw-semibold d-block mb-2">
                            Authorized Concerns / Entities <span class="text-danger">*</span>
                            <small class="text-muted fw-normal d-block">Check all business firms this staff member is authorized to access.</small>
                        </label>
                        <input type="hidden" name="concern_assignment_submitted" value="1">
                        
                        <div class="row g-3 mb-4" id="concernCheckboxesList">
                            <?php foreach (($concerns ?? []) as $concern): ?>
                                <?php 
                                $cid = (int)$concern['id'];
                                $isAssigned = in_array($cid, $staffConcerns ?? [], true);
                                ?>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100 border rounded-3 p-3 concern-select-card <?= $isAssigned ? 'border-primary bg-primary bg-opacity-10' : 'bg-light bg-opacity-25' ?>" style="cursor: pointer;" onclick="toggleConcernCard(event, <?= $cid ?>)">
                                        <div class="form-check d-flex align-items-start gap-2">
                                            <input class="form-check-input concern-checkbox mt-1" type="checkbox" name="concerns[]" value="<?= $cid ?>" id="concern_<?= $cid ?>" <?= $isAssigned ? 'checked' : '' ?> data-concern-name="<?= e($concern['name']) ?>" data-concern-code="<?= e($concern['code']) ?>" onchange="syncDefaultConcernOptions()">
                                            <label class="form-check-label w-100" for="concern_<?= $cid ?>" style="cursor: pointer;">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="badge bg-secondary-subtle text-primary border fw-bold"><?= e($concern['code']) ?></span>
                                                    <?php if (!empty($concern['legal_name'])): ?>
                                                        <small class="text-muted text-truncate" style="max-width: 140px;"><?= e($concern['legal_name']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="fw-bold text-dark fs-6"><?= e($concern['name']) ?></div>
                                                <?php if (!empty($concern['gst_number'])): ?>
                                                    <small class="text-muted d-block font-monospace">GSTIN: <?= e($concern['gst_number']) ?></small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <input type="hidden" name="default_concern_id" id="default_concern_id" value="<?= (int)($defaultConcernId ?? 0) ?>">

                        <div class="alert alert-info border-0 d-flex align-items-center gap-2 mb-0 py-2 w-100">
                            <i class="bi bi-info-circle fs-5 text-info flex-shrink-0"></i>
                            <div class="small">
                                Staff members will only be able to view documents, records, and switch between entities that are checked above. The first checked concern will be active upon login.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 3. PERMISSIONS MATRIX & MODULE ACCESS (Dynamic Role Sync & Instant Edit) -->
                <!-- ========================================================================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h5 class="card-title mb-1 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock-fill text-primary"></i>
                                Staff Permissions &amp; Module Access
                            </h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" id="permSyncBadge">
                                    Role Sync: <span id="syncRoleName">None</span>
                                </span>
                                <span class="badge bg-light text-dark border px-2.5 py-1" id="activePermCountBadge">
                                    <span id="activePermCount">0</span> / <?= $totalPossiblePerms ?> Active Permissions
                                </span>
                            </div>
                        </div>

                        <!-- Quick Controls -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Module Search Input -->
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="permModuleFilter" class="form-control bg-light border-start-0 ps-0" placeholder="Filter modules..." autocomplete="off">
                            </div>

                            <!-- Global Select / Deselect -->
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                                <i class="bi bi-check-all me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                                <i class="bi bi-x-lg me-1"></i>Clear All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btnResetToRole" title="Restore permissions to the assigned role defaults">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Role Defaults
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="d-flex flex-column gap-3 p-3 p-md-4" id="permModulesWrapper">
                            <?php foreach ($hierarchicalModules as $modKey => $module): ?>
                                <?php
                                $submodules = $module['submodules'] ?? [];
                                $modIcon = $module['icon'] ?? 'bi-grid';
                                $modTitle = $module['title'] ?? ucfirst($modKey);
                                $modBadgeClass = $module['badge'] ?? 'bg-primary-subtle text-primary border-primary';
                                ?>
                                <div class="card border border-light-subtle rounded-3 shadow-xs perm-module-block" 
                                     data-module-key="<?= e($modKey) ?>"
                                     data-dept="<?= e($module['department'] ?? 'ALL') ?>">
                                    
                                    <!-- Module Header -->
                                    <div class="card-header bg-light bg-opacity-50 py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi <?= $modIcon ?> text-primary fs-5"></i>
                                            <strong class="text-dark perm-module-title"><?= e($modTitle) ?></strong>
                                            <span class="badge <?= $modBadgeClass ?> border px-2 py-0.5 rounded-pill small">
                                                <?= count($submodules) ?> submodules
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small perm-dept-lock-badge d-none">
                                                <i class="bi bi-lock-fill me-1"></i>Restricted
                                            </span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small perm-dept-active-badge d-none">
                                                <i class="bi bi-check-circle-fill me-1"></i>Active Scope
                                            </span>
                                        </div>
                                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                            <label class="form-check-label small text-muted cursor-pointer" for="perm_mod_toggle_<?= e($modKey) ?>">
                                                Toggle Module
                                            </label>
                                            <input class="form-check-input perm-module-switch cursor-pointer" 
                                                   type="checkbox" 
                                                   id="perm_mod_toggle_<?= e($modKey) ?>" 
                                                   data-module="<?= e($modKey) ?>"
                                                   title="Select / Deselect all in <?= e($modTitle) ?>">
                                        </div>
                                    </div>

                                    <!-- Submodules Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0 permissions-table">
                                            <thead class="table-light">
                                                <tr class="text-uppercase small text-muted" style="font-size: 11px;">
                                                    <th class="ps-3 py-2" style="width: 34%;">Submodule</th>
                                                    <th class="text-center py-2" style="width: 10%;">View</th>
                                                    <th class="text-center py-2" style="width: 10%;">Create</th>
                                                    <th class="text-center py-2" style="width: 10%;">Verify</th>
                                                    <th class="text-center py-2" style="width: 10%;">Edit</th>
                                                    <th class="text-center py-2" style="width: 10%;">Delete</th>
                                                    <th class="text-center pe-3 py-2" style="width: 16%;">Row All</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($submodules as $subKey => $sub): ?>
                                                    <?php
                                                    $subPerms   = $sub['permissions'] ?? [];
                                                    $subName    = $sub['name'] ?? $sub['title'] ?? ucfirst($subKey);
                                                    $subIcon    = $sub['icon'] ?? 'bi-circle';
                                                    $subDesc    = $sub['desc'] ?? $sub['description'] ?? '';
                                                    $subDept    = $sub['department'] ?? $module['department'] ?? 'ALL';

                                                    $crudColumns = Role::getCrudPermissionsByAction($subPerms);
                                                    ?>
                                                    <tr class="perm-submodule-row" data-submodule-key="<?= e($subKey) ?>" data-dept="<?= e($subDept) ?>">
                                                        <td class="ps-3 py-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi <?= $subIcon ?> text-primary"></i>
                                                                <div>
                                                                    <div class="fw-semibold text-dark submodule-title"><?= e($subName) ?></div>
                                                                    <?php if (!empty($subDesc)): ?>
                                                                        <div class="text-muted small" style="font-size: 11px;"><?= e($subDesc) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <?php foreach (['view', 'create', 'verify', 'edit', 'delete'] as $crudAction): ?>
                                                            <td class="text-center">
                                                                <?php $columnPermissions = $crudColumns[$crudAction]; ?>
                                                                <?php if (!empty($columnPermissions)): ?>
                                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                                        <?php foreach ($columnPermissions as $permissionAction => $permission): ?>
                                                                            <label class="form-check-label d-inline-flex align-items-center justify-content-center gap-1 small" title="<?= e($permission['description'] ?? ucfirst($permissionAction)) ?>">
                                                                                <input class="form-check-input perm-checkbox perm-<?= e($crudAction) ?>"
                                                                                       type="checkbox"
                                                                                       name="permissions[]"
                                                                                       value="<?= $permission['id'] ?>"
                                                                                       data-action="<?= e($permissionAction) ?>"
                                                                                       data-module="<?= e($modKey) ?>"
                                                                                       data-submodule="<?= e($subKey) ?>"
                                                                                       <?= in_array((int)$permission['id'], $staffPermissions) ? 'checked' : '' ?>>
                                                                                <?php if ($permissionAction !== $crudAction): ?>
                                                                                    <span class="text-capitalize"><?= e(str_replace('_', ' ', $permissionAction)) ?></span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted opacity-25">&mdash;</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>

                                                        <!-- Row All -->
                                                        <td class="text-center pe-3">
                                                            <input class="form-check-input row-all-toggle cursor-pointer" 
                                                                   type="checkbox" 
                                                                   data-submodule="<?= e($subKey) ?>"
                                                                   title="Toggle all for this submodule">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 2b. Left Sidebar Menu Order (All Modules) -->
                <?php
                $sidebarItemMap = [];
                foreach (($sidebarItems ?? SidebarMenu::allModuleItems()) as $sItem) {
                    $sidebarItemMap[$sItem['key']] = $sItem;
                }
                $orderedSidebarKeys = $savedSidebarOrder ?? array_keys($sidebarItemMap);
                ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ol me-2 text-primary"></i>Left Sidebar Menu Order</h5>
                            <small class="text-muted">Configure the left sidebar order separately for each authorized concern tab.</small>
                        </div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input cursor-pointer" type="checkbox" id="toggleAllocatedOnly" checked>
                                <label class="form-check-label small fw-semibold cursor-pointer" for="toggleAllocatedOnly">Show Allocated Only</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetSidebarOrder">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Default
                            </button>
                        </div>
                    </div>
                    <ul class="nav nav-pills gap-2 px-3 py-2.5 border-bottom bg-light bg-opacity-50" id="sidebarConcernTabs"></ul>
                    <div class="card-body p-3">
                        <div id="sidebarNoConcernHint" class="text-center text-muted small py-3 d-none">
                            Select at least one authorized concern above to configure sidebar menu order.
                        </div>
                        <input type="hidden" name="sidebar_order" id="sidebarOrderInput" value="<?= e(json_encode($orderedSidebarKeys)) ?>">
                        <div id="sidebarOrderList" class="list-group">
                            <?php foreach ($orderedSidebarKeys as $pos => $sKey):
                                if (!isset($sidebarItemMap[$sKey])) continue;
                                $sItem = $sidebarItemMap[$sKey];
                            ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2 px-3 sidebar-order-item"
                                     draggable="true"
                                     data-key="<?= e($sItem['key']) ?>"
                                     data-perms="<?= e(json_encode($sItem['permissions'])) ?>">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <span class="text-muted cursor-grab" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                                        <span class="badge bg-light text-dark border sidebar-order-pos" style="min-width: 28px;"><?= $pos + 1 ?></span>
                                        <span class="fw-semibold text-dark"><?= e($sItem['label']) ?></span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle sidebar-alloc-badge" style="font-size: 0.7rem;">Allocated</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary btn-move-top" title="Move to top"><i class="bi bi-chevron-bar-up"></i></button>
                                        <button type="button" class="btn btn-outline-secondary btn-move-up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                                        <button type="button" class="btn btn-outline-secondary btn-move-down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="sidebarOrderEmptyHint" class="text-center text-muted small py-3 d-none">
                            No "All Modules" items have View permission allocated yet. Check View permissions above or uncheck "Show Allocated Only".
                        </div>
                    </div>
                </div>

                <!-- 3. Form Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <a href="<?= url('admin/staff') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold">
                            <i class="bi <?= $isEdit ? 'bi-check-circle' : 'bi-person-plus-fill' ?> me-1"></i>
                            <?= $isEdit ? 'Update Staff Member & Permissions' : 'Create Staff Member & Save Permissions' ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // Role Permissions Map & User Permissions Data
    // =========================================================================
    const rolePermissionsMap = <?= json_encode($rolePermissionsMap, JSON_UNESCAPED_UNICODE) ?>;
    const initialStaffPerms = <?= json_encode($staffPermissions, JSON_UNESCAPED_UNICODE) ?>;
    const isEditMode = <?= $isEdit ? 'true' : 'false' ?>;

    const permModuleBlocks = document.querySelectorAll('.perm-module-block');
    const allPermCheckboxes = document.querySelectorAll('.perm-checkbox');
    const activePermCountEl = document.getElementById('activePermCount');
    const syncRoleName = document.getElementById('syncRoleName');
    const permSyncBadge = document.getElementById('permSyncBadge');
    const permModuleFilter = document.getElementById('permModuleFilter');
    const btnResetToRole = document.getElementById('btnResetToRole');

    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwdInput.getAttribute('type') === 'password';
            pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });
    }

    // =========================================================================
    // Phone Number Strict Validation & Uniqueness Check
    // =========================================================================
    const phoneInput = document.getElementById('phone');
    const phoneCounter = document.getElementById('phoneCounter');
    const phoneFeedback = document.getElementById('phoneFeedback');
    const phoneSuccess = document.getElementById('phoneSuccess');
    const phoneStatusIcon = document.getElementById('phoneStatusIcon');
    const currentStaffId = phoneInput ? (phoneInput.getAttribute('data-staff-id') || '') : '';
    let phoneCheckTimeout = null;
    let isPhoneDuplicate = false;

    function setPhoneFeedback(status, message) {
        if (!phoneFeedback || !phoneSuccess || !phoneStatusIcon || !phoneInput) return;

        phoneFeedback.classList.add('d-none');
        phoneSuccess.classList.add('d-none');
        phoneInput.classList.remove('is-invalid', 'is-valid');

        if (status === 'error') {
            phoneFeedback.textContent = message;
            phoneFeedback.classList.remove('d-none');
            phoneInput.classList.add('is-invalid');
            phoneStatusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
        } else if (status === 'success') {
            phoneSuccess.textContent = message;
            phoneSuccess.classList.remove('d-none');
            phoneInput.classList.add('is-valid');
            phoneStatusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        } else if (status === 'checking') {
            phoneStatusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';
        } else {
            phoneStatusIcon.innerHTML = '<i class="bi bi-hash text-muted"></i>';
        }
    }

    function validatePhone() {
        if (!phoneInput) return true;

        const rawVal = phoneInput.value;
        const cleanedVal = rawVal.replace(/\D/g, '').slice(0, 10);
        if (rawVal !== cleanedVal) {
            phoneInput.value = cleanedVal;
        }

        const len = cleanedVal.length;
        if (phoneCounter) {
            phoneCounter.textContent = `${len} / 10 digits`;
            if (len === 10) {
                phoneCounter.className = 'badge bg-success-subtle text-success border border-success font-monospace';
            } else if (len > 0) {
                phoneCounter.className = 'badge bg-warning-subtle text-dark border border-warning font-monospace';
            } else {
                phoneCounter.className = 'badge bg-light text-muted border font-monospace';
            }
        }

        if (len === 0) {
            isPhoneDuplicate = false;
            setPhoneFeedback('neutral', '');
            return true;
        }

        if (len < 10) {
            isPhoneDuplicate = false;
            setPhoneFeedback('error', `Phone number must be exactly 10 digits (currently ${len} digits).`);
            return false;
        }

        const firstDigit = cleanedVal.charAt(0);
        if (!['6', '7', '8', '9'].includes(firstDigit)) {
            isPhoneDuplicate = false;
            setPhoneFeedback('error', 'Mobile number must start with 6, 7, 8, or 9.');
            return false;
        }

        setPhoneFeedback('checking', '');
        clearTimeout(phoneCheckTimeout);
        phoneCheckTimeout = setTimeout(() => {
            fetch(`<?= url('admin/staff/check-phone') ?>?phone=${encodeURIComponent(cleanedVal)}&except_id=${encodeURIComponent(currentStaffId)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.valid) {
                        isPhoneDuplicate = false;
                        setPhoneFeedback('error', data.message);
                    } else if (!data.available) {
                        isPhoneDuplicate = true;
                        setPhoneFeedback('error', `⚠️ Duplicate: ${data.message}`);
                    } else {
                        isPhoneDuplicate = false;
                        setPhoneFeedback('success', '✓ Phone number is valid & available.');
                    }
                })
                .catch(() => {
                    isPhoneDuplicate = false;
                    setPhoneFeedback('success', '✓ 10-digit number format valid.');
                });
        }, 300);

        return true;
    }

    if (phoneInput) {
        phoneInput.addEventListener('keypress', function (e) {
            if (['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
                return false;
            }
            if (this.value.length >= 10 && this.selectionStart === this.selectionEnd) {
                e.preventDefault();
                return false;
            }
        });
        phoneInput.addEventListener('input', validatePhone);
        phoneInput.addEventListener('paste', () => setTimeout(validatePhone, 10));
        if (phoneInput.value) validatePhone();
    }

    // =========================================================================
    // Operational Concern & Role Auto-Choose Logic
    // =========================================================================
    const roleSelect = document.getElementById('role_id');
    const warningBox = document.getElementById('role_mismatch_warning');
    const warningMsg = document.getElementById('role_mismatch_msg');
    const staffForm  = document.getElementById('staffForm');

    function syncRoleAndDepartment(autoSelect = true) {
        // Multi-module enablement: Ensure all modules and submodule rows are visible, active, and selectable
        permModuleBlocks.forEach(block => {
            block.classList.remove('d-none');
            block.classList.remove('perm-block-disabled');
            const modSwitch = block.querySelector('.perm-module-switch');
            if (modSwitch) modSwitch.disabled = false;
            const lockBadge = block.querySelector('.perm-dept-lock-badge');
            if (lockBadge) lockBadge.classList.add('d-none');
            const activeBadge = block.querySelector('.perm-dept-active-badge');
            if (activeBadge) activeBadge.classList.add('d-none');

            const subRows = block.querySelectorAll('.perm-submodule-row');
            subRows.forEach(row => {
                row.classList.remove('d-none');
                row.classList.remove('perm-row-disabled');
                row.querySelectorAll('.perm-checkbox, .row-all-toggle').forEach(cb => {
                    cb.disabled = false;
                });
            });
        });

        if (warningBox) warningBox.classList.add('d-none');
        updateActiveCounterAndSwitches();
    }

    function syncPermissionsScopeWithDepartment(selectedDept) {
        syncRoleAndDepartment();
    }

    function updateActiveCounterAndSwitches() {
        let activeCount = 0;
        allPermCheckboxes.forEach(cb => {
            if (cb.checked && !cb.disabled) activeCount++;
        });
        if (activePermCountEl) activePermCountEl.textContent = activeCount;

        // Sync each row-all toggle
        document.querySelectorAll('.row-all-toggle').forEach(rowToggle => {
            const row = rowToggle.closest('.perm-submodule-row');
            if (row) {
                const cbs = row.querySelectorAll('.perm-checkbox:not(:disabled)');
                const allChecked = cbs.length > 0 && Array.from(cbs).every(c => c.checked);
                rowToggle.checked = allChecked;
            }
        });

        // Sync each module switch
        document.querySelectorAll('.perm-module-switch').forEach(modSwitch => {
            const card = modSwitch.closest('.perm-module-block');
            if (card) {
                const cbs = card.querySelectorAll('.perm-checkbox:not(:disabled)');
                const allChecked = cbs.length > 0 && Array.from(cbs).every(c => c.checked);
                modSwitch.checked = allChecked;
            }
        });
    }

    function applyRolePermissions(roleId, isManualOverride = false) {
        const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
        const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'None';
        
        if (syncRoleName) syncRoleName.textContent = roleName;

        if (!roleId || !rolePermissionsMap[roleId]) {
            if (!isEditMode || !isManualOverride) {
                allPermCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
            }
            if (permSyncBadge) {
                permSyncBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `Role Sync: <span id="syncRoleName">${roleName}</span> (No Role)`;
            }
            updateActiveCounterAndSwitches();
            return;
        }

        const allowedPids = (rolePermissionsMap[roleId] || []).map(Number);
        allPermCheckboxes.forEach(cb => {
            cb.checked = allowedPids.includes(Number(cb.value));
        });

        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-shield-check me-1"></i>Role Defaults: <strong>${roleName}</strong> (${allowedPids.length} active)`;
        }

        updateActiveCounterAndSwitches();
    }

    function applyRoleConcerns(roleOption) {
        if (!roleOption || !roleOption.value) return;
        const scope = (roleOption.getAttribute('data-concern-scope') || 'ALL').toUpperCase();
        let applicableIds = [];
        try {
            const parsed = JSON.parse(roleOption.getAttribute('data-applicable-concerns') || '[]');
            if (Array.isArray(parsed)) {
                applicableIds = parsed.map(id => String(id));
            }
        } catch (e) {}

        const checkboxes = document.querySelectorAll('.concern-checkbox');
        if (scope === 'SPECIFIC' && applicableIds.length > 0) {
            checkboxes.forEach(cb => {
                cb.checked = applicableIds.includes(String(cb.value));
            });
        } else if (scope === 'ALL') {
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
        }
        if (typeof window.syncDefaultConcernOptions === 'function') {
            window.syncDefaultConcernOptions();
        }
    }

    // When role changes -> auto sync department, auto load role's concerns, AND auto load role's permissions!
    roleSelect?.addEventListener('change', function () {
        syncRoleAndDepartment(true);
        const selectedRoleId = this.value;
        const roleOption = this.options[this.selectedIndex];
        applyRoleConcerns(roleOption);
        applyRolePermissions(selectedRoleId, false);
    });

    // Reset to role defaults button
    btnResetToRole?.addEventListener('click', function () {
        const selectedRoleId = roleSelect ? roleSelect.value : '';
        if (!selectedRoleId) {
            return;
        }
        const roleOption = roleSelect.options[roleSelect.selectedIndex];
        applyRoleConcerns(roleOption);
        applyRolePermissions(selectedRoleId, false);
    });

    // When individual permission checkbox is clicked -> mark as customized
    allPermCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateActiveCounterAndSwitches();
            const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
            const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'Custom';
            if (permSyncBadge) {
                permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized: <strong>${roleName}</strong> (Modified)`;
            }
        });
    });

    // Row-all toggles
    document.querySelectorAll('.row-all-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const row = this.closest('.perm-submodule-row');
            if (row) {
                row.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
                updateActiveCounterAndSwitches();
                if (permSyncBadge) {
                    permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                    permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (Modified)`;
                }
            }
        });
    });

    // Module-wide switch toggles
    document.querySelectorAll('.perm-module-switch').forEach(modSwitch => {
        modSwitch.addEventListener('change', function () {
            const card = this.closest('.perm-module-block');
            if (card) {
                card.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
                updateActiveCounterAndSwitches();
                if (permSyncBadge) {
                    permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                    permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (Modified)`;
                }
            }
        });
    });

    // Global Select All / Clear All
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        allPermCheckboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = true;
        });
        updateActiveCounterAndSwitches();
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (All Selected)`;
        }
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        allPermCheckboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = false;
        });
        updateActiveCounterAndSwitches();
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (All Cleared)`;
        }
    });

    // Live Module Search Filter
    permModuleFilter?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        permModuleBlocks.forEach(block => {
            const title = block.querySelector('.perm-module-title')?.textContent.toLowerCase() || '';
            const rows = block.querySelectorAll('.perm-submodule-row');
            let hasVisibleRow = false;

            rows.forEach(row => {
                const subTitle = row.querySelector('.fw-semibold')?.textContent.toLowerCase() || '';
                const match = query === '' || title.includes(query) || subTitle.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) hasVisibleRow = true;
            });

            block.style.display = (query === '' || hasVisibleRow) ? '' : 'none';
        });
    });

    // Form submit guard
    staffForm?.addEventListener('submit', function (e) {
        const checkedRadio = document.querySelector('.dept-radio:checked');
        if (checkedRadio) {
            checkedRadio.disabled = false;
        }

        if (phoneInput && phoneInput.value.trim() !== '') {
            const phoneVal = phoneInput.value.replace(/\D/g, '');
            if (phoneVal.length !== 10) {
                e.preventDefault();
                alert(`⚠️ Invalid Phone Number:\n\nPhone number must be exactly 10 digits. Currently it has ${phoneVal.length} digits.`);
                phoneInput.focus();
                return false;
            }
            if (!['6', '7', '8', '9'].includes(phoneVal.charAt(0))) {
                e.preventDefault();
                alert('⚠️ Invalid Phone Number:\n\nIndian mobile numbers must start with 6, 7, 8, or 9.');
                phoneInput.focus();
                return false;
            }
            if (isPhoneDuplicate) {
                e.preventDefault();
                alert('⚠️ Duplicate Phone Number:\n\nThis phone number is already registered to another staff member. Please use a unique phone number.');
                phoneInput.focus();
                return false;
            }
        }

        // Validate business concern assignment
        const checkedConcerns = document.querySelectorAll('.concern-checkbox:checked');
        if (checkedConcerns.length === 0) {
            e.preventDefault();
            alert('⚠️ Concern Assignment Required:\n\nPlease select at least one authorized Business Concern for this staff member.');
            return false;
        }
    });

    // =========================================================================
    // Concern Multi-Select & Default Sync Helpers
    // =========================================================================
    window.toggleConcernCard = function(event, cid) {
        if (event.target.closest('label') || event.target.closest('input')) {
            return;
        }
        const chk = document.getElementById('concern_' + cid);
        if (chk) {
            chk.checked = !chk.checked;
            syncDefaultConcernOptions();
        }
    };

    window.syncDefaultConcernOptions = function() {
        const hiddenInput = document.getElementById('default_concern_id');
        if (!hiddenInput) return;

        const checkboxes = document.querySelectorAll('.concern-checkbox');
        const preferredVal = parseInt(hiddenInput.value) || <?= json_encode((int)($defaultConcernId ?? 0)) ?>;
        const checkedIds = [];

        checkboxes.forEach(cb => {
            const card = cb.closest('.concern-select-card');
            if (cb.checked) {
                checkedIds.push(parseInt(cb.value));
                if (card) {
                    card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                    card.classList.remove('bg-light', 'bg-opacity-25');
                }
            } else {
                if (card) {
                    card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                    card.classList.add('bg-light', 'bg-opacity-25');
                }
            }
        });

        const activeCid = checkedIds.includes(preferredVal) ? preferredVal : (checkedIds[0] || '');
        hiddenInput.value = activeCid;

        if (typeof window.refreshSidebarConcernTabs === 'function') {
            window.refreshSidebarConcernTabs();
        }
    };

    // Initialize concern hidden default value
    syncDefaultConcernOptions();

    // =========================================================================
    // Initial Load Setup
    // =========================================================================
    syncRoleAndDepartment(false);

    if (isEditMode) {
        // In edit mode: if staff has custom permissions or role permissions
        updateActiveCounterAndSwitches();
        const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
        const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'Assigned Role';
        const roleId = roleSelect ? roleSelect.value : '';
        const rolePerms = (rolePermissionsMap[roleId] || []).map(Number).sort();
        const staffPerms = (initialStaffPerms || []).map(Number).sort();
        const isCustom = rolePerms.length !== staffPerms.length || !rolePerms.every((v, i) => v === staffPerms[i]);
        if (permSyncBadge) {
            if (isCustom) {
                permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Custom Permissions: <strong>${roleName}</strong> (Modified)`;
            } else {
                permSyncBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `<i class="bi bi-shield-check me-1"></i>Role Defaults: <strong>${roleName}</strong>`;
            }
        }
    } else {
        // In create mode: if role already selected (e.g. from old input), sync permissions
        if (roleSelect && roleSelect.value) {
            applyRolePermissions(roleSelect.value, false);
        } else {
            updateActiveCounterAndSwitches();
        }
    }
    // =========================================================================
    // Left Sidebar Menu Order Management (Per-Concern Tabs)
    // =========================================================================
    const sidebarOrderList = document.getElementById('sidebarOrderList');
    const sidebarOrderInput = document.getElementById('sidebarOrderInput');
    const sidebarConcernTabs = document.getElementById('sidebarConcernTabs');
    const sidebarNoConcernHint = document.getElementById('sidebarNoConcernHint');
    const toggleAllocatedOnly = document.getElementById('toggleAllocatedOnly');
    const btnResetSidebarOrder = document.getElementById('btnResetSidebarOrder');
    const sidebarOrderEmptyHint = document.getElementById('sidebarOrderEmptyHint');
    const defaultSidebarKeys = <?= json_encode(array_column($sidebarItems ?? SidebarMenu::allModuleItems(), 'key')) ?>;
    const savedSidebarOrderMap = <?= json_encode($savedSidebarOrderMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const sidebarOrderMap = {};
    let activeSidebarConcernId = null;

    if (savedSidebarOrderMap && typeof savedSidebarOrderMap === 'object') {
        Object.keys(savedSidebarOrderMap).forEach(k => {
            if (k !== '_default' && Array.isArray(savedSidebarOrderMap[k])) {
                sidebarOrderMap[String(k)] = savedSidebarOrderMap[k].slice();
            }
        });
    }

    function getCheckedConcernsList() {
        const list = [];
        document.querySelectorAll('.concern-checkbox:checked').forEach(cb => {
            const cid = String(cb.value);
            const code = cb.getAttribute('data-concern-code') || '';
            const name = cb.getAttribute('data-concern-name') || ('Concern #' + cid);
            list.push({ id: cid, label: code ? `${code} - ${name}` : name });
        });
        return list;
    }

    function ensureConcernOrder(cid) {
        if (!sidebarOrderMap[cid]) {
            const fallback = Array.isArray(savedSidebarOrderMap._default)
                ? savedSidebarOrderMap._default
                : defaultSidebarKeys;
            sidebarOrderMap[cid] = fallback.slice();
        }
        return sidebarOrderMap[cid];
    }

    function applyOrderToDOM(keys) {
        if (!sidebarOrderList) return;
        const itemsByKey = {};
        sidebarOrderList.querySelectorAll('.sidebar-order-item').forEach(el => {
            itemsByKey[el.getAttribute('data-key')] = el;
        });
        keys.forEach(k => {
            if (itemsByKey[k]) sidebarOrderList.appendChild(itemsByKey[k]);
        });
        defaultSidebarKeys.forEach(k => {
            if (!keys.includes(k) && itemsByKey[k]) sidebarOrderList.appendChild(itemsByKey[k]);
        });
    }

    function serializeSidebarOrderMap() {
        if (!sidebarOrderInput) return;
        const checked = getCheckedConcernsList();
        const payload = {};
        checked.forEach(c => {
            payload[c.id] = ensureConcernOrder(c.id);
        });
        sidebarOrderInput.value = JSON.stringify(payload);
    }

    function getAllocatedViewSubmodules() {
        const allocated = new Set();
        document.querySelectorAll('.perm-checkbox[data-action="view"]:checked').forEach(cb => {
            const sub = cb.getAttribute('data-submodule');
            if (sub) allocated.add(sub);
        });
        return allocated;
    }

    function refreshSidebarOrderUI() {
        if (!sidebarOrderList || !sidebarOrderInput) return;
        const checked = getCheckedConcernsList();
        if (checked.length === 0) {
            sidebarOrderList.classList.add('d-none');
            if (sidebarNoConcernHint) sidebarNoConcernHint.classList.remove('d-none');
            if (sidebarOrderEmptyHint) sidebarOrderEmptyHint.classList.add('d-none');
            sidebarOrderInput.value = JSON.stringify({});
            return;
        }
        sidebarOrderList.classList.remove('d-none');
        if (sidebarNoConcernHint) sidebarNoConcernHint.classList.add('d-none');

        const allocatedSet = getAllocatedViewSubmodules();
        const showAllocatedOnly = toggleAllocatedOnly ? toggleAllocatedOnly.checked : true;
        const keys = [];
        let visibleCount = 0;

        sidebarOrderList.querySelectorAll('.sidebar-order-item').forEach((item) => {
            const key = item.getAttribute('data-key');
            if (key) keys.push(key);
            let perms = [];
            try { perms = JSON.parse(item.getAttribute('data-perms') || '[]'); } catch (e) {}
            const isAllocated = perms.some(p => allocatedSet.has(p));

            const badge = item.querySelector('.sidebar-alloc-badge');
            if (badge) {
                badge.className = isAllocated
                    ? 'badge bg-success-subtle text-success border border-success-subtle sidebar-alloc-badge'
                    : 'badge bg-light text-muted border sidebar-alloc-badge';
                badge.textContent = isAllocated ? 'Allocated' : 'Not Allocated';
            }

            const posEl = item.querySelector('.sidebar-order-pos');
            if (showAllocatedOnly && !isAllocated) {
                item.classList.add('d-none');
            } else {
                item.classList.remove('d-none');
                visibleCount++;
                if (posEl) posEl.textContent = String(visibleCount);
            }
        });

        if (activeSidebarConcernId) {
            sidebarOrderMap[activeSidebarConcernId] = keys;
        }
        serializeSidebarOrderMap();

        if (sidebarOrderEmptyHint) {
            sidebarOrderEmptyHint.classList.toggle('d-none', visibleCount > 0);
        }
    }

    window.refreshSidebarConcernTabs = function() {
        if (!sidebarConcernTabs) return;
        const checked = getCheckedConcernsList();
        sidebarConcernTabs.innerHTML = '';

        if (checked.length === 0) {
            activeSidebarConcernId = null;
            sidebarConcernTabs.classList.add('d-none');
            refreshSidebarOrderUI();
            return;
        }
        sidebarConcernTabs.classList.remove('d-none');

        if (!activeSidebarConcernId || !checked.some(c => c.id === activeSidebarConcernId)) {
            activeSidebarConcernId = checked[0].id;
        }

        checked.forEach(c => {
            ensureConcernOrder(c.id);
            const li = document.createElement('li');
            li.className = 'nav-item';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'nav-link btn-sm py-1.5 px-3 ' + (c.id === activeSidebarConcernId ? 'active fw-semibold' : 'bg-white border text-dark');
            btn.innerHTML = `<i class="bi bi-buildings me-1"></i>${c.label}`;
            btn.addEventListener('click', () => {
                activeSidebarConcernId = c.id;
                applyOrderToDOM(ensureConcernOrder(c.id));
                window.refreshSidebarConcernTabs();
            });
            li.appendChild(btn);
            sidebarConcernTabs.appendChild(li);
        });

        applyOrderToDOM(ensureConcernOrder(activeSidebarConcernId));
        refreshSidebarOrderUI();
    };

    if (sidebarOrderList) {
        let draggedItem = null;

        sidebarOrderList.addEventListener('dragstart', function (e) {
            const item = e.target.closest('.sidebar-order-item');
            if (!item) return;
            draggedItem = item;
            item.classList.add('opacity-50');
            e.dataTransfer.effectAllowed = 'move';
        });

        sidebarOrderList.addEventListener('dragend', function () {
            if (draggedItem) {
                draggedItem.classList.remove('opacity-50');
                draggedItem = null;
            }
            refreshSidebarOrderUI();
        });

        sidebarOrderList.addEventListener('dragover', function (e) {
            e.preventDefault();
            const target = e.target.closest('.sidebar-order-item');
            if (!draggedItem || !target || target === draggedItem) return;
            const rect = target.getBoundingClientRect();
            const after = (e.clientY - rect.top) > (rect.height / 2);
            if (after) {
                target.after(draggedItem);
            } else {
                target.before(draggedItem);
            }
        });

        sidebarOrderList.addEventListener('click', function (e) {
            const item = e.target.closest('.sidebar-order-item');
            if (!item) return;
            if (e.target.closest('.btn-move-top')) {
                sidebarOrderList.prepend(item);
                refreshSidebarOrderUI();
            } else if (e.target.closest('.btn-move-up')) {
                let prev = item.previousElementSibling;
                while (prev && prev.classList.contains('d-none')) prev = prev.previousElementSibling;
                if (prev) prev.before(item);
                refreshSidebarOrderUI();
            } else if (e.target.closest('.btn-move-down')) {
                let next = item.nextElementSibling;
                while (next && next.classList.contains('d-none')) next = next.nextElementSibling;
                if (next) next.after(item);
                refreshSidebarOrderUI();
            }
        });
    }

    if (toggleAllocatedOnly) {
        toggleAllocatedOnly.addEventListener('change', refreshSidebarOrderUI);
    }

    if (btnResetSidebarOrder && sidebarOrderList) {
        btnResetSidebarOrder.addEventListener('click', function () {
            const itemsByKey = {};
            sidebarOrderList.querySelectorAll('.sidebar-order-item').forEach(el => {
                itemsByKey[el.getAttribute('data-key')] = el;
            });
            defaultSidebarKeys.forEach(k => {
                if (itemsByKey[k]) sidebarOrderList.appendChild(itemsByKey[k]);
            });
            refreshSidebarOrderUI();
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('.perm-checkbox, .row-all-toggle, .module-master-switch, #role_id')) {
            setTimeout(refreshSidebarOrderUI, 20);
        }
    });
    ['btnSelectAllPerms', 'btnClearAllPerms', 'btnResetRolePerms'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => setTimeout(refreshSidebarOrderUI, 30));
    });

    window.refreshSidebarConcernTabs();
});
</script>

<style>
.dept-card {
    transition: all 0.25s ease-in-out;
    cursor: pointer;
}
.dept-card-disabled {
    opacity: 0.4 !important;
    filter: grayscale(0.85);
    cursor: not-allowed !important;
    pointer-events: none !important;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}
.perm-block-disabled {
    opacity: 0.45 !important;
    filter: grayscale(0.7);
    background-color: #f8f9fa !important;
    border-color: #e2e8f0 !important;
}
.perm-block-disabled .table {
    pointer-events: none !important;
}
.perm-row-disabled {
    opacity: 0.35 !important;
    filter: grayscale(0.8);
    background-color: #f8f9fa !important;
    pointer-events: none !important;
}
.permissions-table th, .permissions-table td {
    vertical-align: middle;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
