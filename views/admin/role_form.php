<?php
/**
 * Role & Staff Form with Comprehensive Hierarchical Permissions Matrix
 */
$isEdit = !empty($role['id']);
$formAction = $isEdit ? url('admin/roles/update/' . $role['id']) : url('admin/roles/store');
$assigned = Role::filterCrudPermissionIds(array_map('intval', $assignedPermissionIds ?? []));
$hierarchicalModules = $hierarchicalModules ?? Role::getHierarchicalPermissions();

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
.permissions-table .form-check-input:not(.row-switch),
.role-concern-card .role-concern-checkbox {
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid #64748b !important;
    background-color: #ffffff;
    cursor: pointer;
}
.permissions-table .form-check-input:not(.row-switch):hover,
.role-concern-card .role-concern-checkbox:hover {
    border-color: #0d6efd !important;
}
.permissions-table .form-check-input:not(.row-switch):checked,
.role-concern-card .role-concern-checkbox:checked {
    border-color: #0d6efd !important;
    background-color: #0d6efd !important;
}
.permissions-table .row-switch,
.module-switch {
    border: 2px solid #64748b !important;
    cursor: pointer;
}
.permissions-table .row-switch:checked,
.module-switch:checked {
    border-color: #0d6efd !important;
    background-color: #0d6efd !important;
}
</style>
<div class="container-fluid py-4 px-3 px-md-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold"><?= $isEdit ? 'Edit Role: ' . e($role['name'] ?? '') : 'Add New Role' ?></h1>
            <div class="text-muted small mt-1">Define the role and assign View, Create, Edit, or Delete access by module.</div>
        </div>
        <div>
            <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Roles</span>
            </a>
        </div>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="roleForm" autocomplete="off">
        <?= CSRF::field() ?>

        <!-- ============================================ -->
        <!-- ROLE DETAILS SECTION -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary"></i>
                    <span>Role Information</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- 1. Role Name -->
                    <div class="col-12 col-md-5">
                        <label for="role_name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                            <input type="text" id="role_name" name="name" class="form-control" autocomplete="off" value="<?= e($role['name'] ?? '') ?>" placeholder="e.g. Warehouse Manager or Cutting Master" required autofocus>
                        </div>
                    </div>

                    <!-- 2. Role Slug / Code -->
                    <div class="col-12 col-md-4">
                        <label for="role_slug" class="form-label fw-semibold">Role Identifier (Slug) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light font-monospace">#</span>
                            <input type="text" id="role_slug" name="slug" class="form-control font-monospace" autocomplete="off" value="<?= e($role['slug'] ?? '') ?>" placeholder="e.g. warehouse-manager" required>
                        </div>
                        <div class="form-text text-muted">Unique slug identifier used for routing &amp; permissions.</div>
                    </div>

                    <!-- 3. Status -->
                    <div class="col-12 col-md-3">
                        <label for="is_active" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select id="is_active" name="is_active" class="form-select">
                            <option value="1" <?= (!isset($role['is_active']) || $role['is_active'] == 1) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (isset($role['is_active']) && $role['is_active'] == 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- 4. Description -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Role Description &amp; Scope</label>
                        <textarea id="description" name="description" class="form-control" rows="2" placeholder="Briefly describe the operational responsibilities and authority of this role..."><?= e($role['description'] ?? '') ?></textarea>
                    </div>

                    <!-- 5. Business Concern Scope -->
                    <div class="col-12 mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div>
                                <label class="form-label fw-semibold mb-0 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-buildings text-primary"></i>
                                    <span>Business Concern Scope &amp; Availability</span>
                                </label>
                                <div class="text-muted small">Specify whether this role can be assigned to staff across all concerns or is restricted to specific business entities.</div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill">
                                <i class="bi bi-shield-check me-1"></i>Multi-Entity Scoped
                            </span>
                        </div>
                        <?php 
                        $curScope = $role['concern_scope'] ?? 'ALL';
                        $applicableCids = [];
                        if (!empty($role['applicable_concerns'])) {
                            $decoded = json_decode($role['applicable_concerns'], true);
                            $applicableCids = is_array($decoded) ? $decoded : [];
                        }
                        ?>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 cursor-pointer" onclick="document.getElementById('scope_all').click()">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="concern_scope" id="scope_all" value="ALL" <?= $curScope === 'ALL' ? 'checked' : '' ?> onchange="toggleConcernScope(this.value)">
                                        <label class="form-check-label fw-bold d-block text-dark cursor-pointer" for="scope_all">
                                            <i class="bi bi-globe me-1 text-primary"></i> Global / All Business Concerns
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1 ps-4">Role is universal and can be assigned to staff operating in any registered sister firm.</small>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100 cursor-pointer" onclick="document.getElementById('scope_specific').click()">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="concern_scope" id="scope_specific" value="SPECIFIC" <?= $curScope === 'SPECIFIC' ? 'checked' : '' ?> onchange="toggleConcernScope(this.value)">
                                        <label class="form-check-label fw-bold d-block text-dark cursor-pointer" for="scope_specific">
                                            <i class="bi bi-building-lock me-1 text-info"></i> Specific Concerns Only
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1 ps-4">Restrict this role to designated operating entities (e.g. Unit 2 processing staff only).</small>
                                </div>
                            </div>
                        </div>

                        <div id="specificConcernsContainer" class="mt-3 p-3 p-md-4 border rounded-4 bg-light bg-opacity-50 <?= $curScope === 'SPECIFIC' ? '' : 'd-none' ?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <label class="form-label fw-bold small text-uppercase text-dark mb-0 d-flex align-items-center gap-1.5">
                                        <i class="bi bi-buildings-fill text-primary"></i>
                                        <span>Select Permitted Business Entities</span>
                                    </label>
                                    <div class="text-muted small">Click a concern card to grant or revoke role availability for that entity.</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" onclick="toggleAllRoleConcerns(true)">Select All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold" onclick="toggleAllRoleConcerns(false)">Clear</button>
                                </div>
                            </div>
                            <div class="row g-3">
                                <?php foreach (($concerns ?? []) as $c): 
                                    $cid = (int)$c['id'];
                                    $checked = in_array($cid, $applicableCids, true);
                                ?>
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <label class="card h-100 rounded-3 p-3 cursor-pointer mb-0 role-concern-card transition-all <?= $checked ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border bg-white' ?>" style="transition: all 0.15s ease;">
                                            <div class="d-flex align-items-start gap-2.5">
                                                <input class="form-check-input role-concern-checkbox mt-1 flex-shrink-0" type="checkbox" name="applicable_concerns[]" value="<?= $cid ?>" <?= $checked ? 'checked' : '' ?> onchange="syncRoleConcernCard(this)">
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold"><?= e($c['code']) ?></span>
                                                        <?php if (!empty($c['legal_name'])): ?>
                                                            <small class="text-muted text-truncate" style="max-width: 150px;" title="<?= e($c['legal_name']) ?>"><?= e($c['legal_name']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="fw-bold text-dark text-truncate"><?= e($c['name']) ?></div>
                                                    <?php if (!empty($c['gst_number'])): ?>
                                                        <small class="text-muted d-block font-monospace mt-0.5" style="font-size: 11px;">GSTIN: <?= e($c['gst_number']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <script>
                        function syncRoleConcernCard(cb) {
                            const card = cb.closest('.role-concern-card');
                            if (!card) return;
                            if (cb.checked) {
                                card.classList.remove('border', 'bg-white');
                                card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
                            } else {
                                card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
                                card.classList.add('border', 'bg-white');
                            }
                        }
                        function toggleAllRoleConcerns(selectAll) {
                            document.querySelectorAll('.role-concern-checkbox').forEach(cb => {
                                cb.checked = selectAll;
                                syncRoleConcernCard(cb);
                            });
                        }
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- HIERARCHICAL PERMISSIONS MATRIX SECTION -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3 sticky-top" style="top: 70px; z-index: 1010; backdrop-filter: blur(8px); background: rgba(255,255,255,0.95);">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Module & Submodule Permissions Matrix</span>
                    </h5>
                    <div class="text-muted small mt-0.5">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 fw-semibold" id="activeCountBadge">
                            <span id="activeCount"><?= count($assigned) ?></span> / <?= $totalPossiblePerms ?> Active Permissions
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Live Search Input -->
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="moduleFilter" class="form-control bg-light border-start-0 ps-0" placeholder="Search modules..." autocomplete="off">
                    </div>

                    <!-- Global Select / Deselect -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                        <i class="bi bi-check-all me-1"></i>Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                        <i class="bi bi-x-lg me-1"></i>Deselect All
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="d-flex flex-column gap-3 p-3 p-md-4" id="modulesWrapper">
                    <?php foreach ($hierarchicalModules as $modKey => $module): ?>
                        <?php
                        $submodules = $module['submodules'] ?? [];
                        $modIcon = $module['icon'] ?? 'bi-grid';
                        $modTitle = $module['title'] ?? ucfirst($modKey);
                        $modBadgeClass = $module['badge'] ?? 'bg-primary-subtle text-primary border-primary';
                        ?>
                        <div class="card border border-light-subtle rounded-3 shadow-xs module-card-block" data-module-key="<?= e($modKey) ?>">
                            
                            <!-- Module Header -->
                            <div class="card-header bg-light bg-opacity-50 py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= $modIcon ?> text-primary fs-5"></i>
                                    <strong class="text-dark module-header-title"><?= e($modTitle) ?></strong>
                                    <span class="badge <?= $modBadgeClass ?> border px-2 py-0.5 rounded-pill small">
                                        <?= count($submodules) ?> submodules
                                    </span>
                                </div>
                                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input module-switch cursor-pointer ms-0" 
                                           type="checkbox" 
                                           id="form_mod_toggle_<?= e($modKey) ?>" 
                                           data-module="<?= e($modKey) ?>"
                                           title="Select / Deselect all in <?= e($modTitle) ?>">
                                    <label class="form-check-label small text-dark fw-semibold cursor-pointer mb-0" for="form_mod_toggle_<?= e($modKey) ?>">
                                        Toggle Module
                                    </label>
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
                                            $subPerms = $sub['permissions'] ?? [];
                                            $subName = $sub['name'] ?? ucfirst($subKey);
                                            $subIcon = $sub['icon'] ?? 'bi-circle';
                                            $subDesc = $sub['desc'] ?? '';

                                            $crudColumns = Role::getCrudPermissionsByAction($subPerms);
                                            ?>
                                            <tr class="submodule-row-item" data-submodule-key="<?= e($subKey) ?>">
                                                <!-- Submodule Info -->
                                                <td class="ps-3 py-2.5">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi <?= $subIcon ?> text-primary"></i>
                                                        <div>
                                                            <div class="fw-semibold text-dark submodule-title"><?= e($subName) ?></div>
                                                            <div class="text-muted small" style="font-size: 11px;"><?= e($subDesc) ?></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <?php foreach (['view', 'create', 'verify', 'edit', 'delete'] as $crudAction): ?>
                                                    <td class="text-center py-2.5">
                                                        <?php $columnPermissions = $crudColumns[$crudAction]; ?>
                                                        <?php if (!empty($columnPermissions)): ?>
                                                            <div class="d-flex flex-column align-items-center gap-1">
                                                                <?php foreach ($columnPermissions as $permissionAction => $permission): ?>
                                                                    <label class="form-check-label d-inline-flex align-items-center justify-content-center gap-1 small" title="<?= e($permission['description'] ?? ucfirst($permissionAction)) ?>">
                                                                        <input class="form-check-input perm-check perm-<?= e($crudAction) ?> mod-<?= e($modKey) ?> row-<?= e($subKey) ?>"
                                                                               type="checkbox"
                                                                               name="permissions[]"
                                                                               value="<?= $permission['id'] ?>"
                                                                               data-module="<?= e($modKey) ?>"
                                                                               data-submodule="<?= e($subKey) ?>"
                                                                               data-action="<?= e($permissionAction) ?>"
                                                                               <?= in_array((int)$permission['id'], $assigned, true) ? 'checked' : '' ?>>
                                                                        <?php if ($permissionAction !== $crudAction): ?>
                                                                            <span class="text-capitalize"><?= e(str_replace('_', ' ', $permissionAction)) ?></span>
                                                                        <?php endif; ?>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small opacity-50">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>

                                                <!-- Row Toggle -->
                                                <td class="text-center pe-3 py-2.5">
                                                    <div class="form-check form-switch d-inline-block mb-0">
                                                        <input class="form-check-input row-switch cursor-pointer" 
                                                               type="checkbox" 
                                                               data-row="row-<?= e($subKey) ?>" 
                                                               title="Toggle all actions for <?= e($subName) ?>">
                                                    </div>
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

            <!-- Form Actions Footer -->
            <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $isEdit ? 'Update Staff Role & Permissions' : 'Save Staff Role & Permissions' ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.permissions-table .form-check-input {
    width: 1.2em;
    height: 1.2em;
    cursor: pointer;
}
.perm-create:checked { background-color: #198754; border-color: #198754; }
.perm-edit:checked { background-color: #ffc107; border-color: #ffc107; }
.perm-delete:checked { background-color: #dc3545; border-color: #dc3545; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Prevent browser autofill on create
    <?php if (!$isEdit): ?>
    setTimeout(function() {
        ['staff_name', 'emp_id', 'email', 'password', 'mobile_number'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
    }, 50);
    <?php endif; ?>

    const permBoxes = document.querySelectorAll('.perm-check');
    const moduleSwitches = document.querySelectorAll('.module-switch');
    const rowSwitches = document.querySelectorAll('.row-switch');
    const activeSpan = document.getElementById('activeCount');
    const moduleFilter = document.getElementById('moduleFilter');

    function syncState() {
        const activeCount = document.querySelectorAll('.perm-check:checked').length;
        if (activeSpan) activeSpan.textContent = activeCount;

        // Sync Row switches
        rowSwitches.forEach(function (sw) {
            const rowClass = sw.getAttribute('data-row');
            const boxes = document.querySelectorAll('.' + rowClass);
            const checked = document.querySelectorAll('.' + rowClass + ':checked').length;
            if (boxes.length > 0) {
                sw.checked = (checked === boxes.length);
                sw.indeterminate = (checked > 0 && checked < boxes.length);
            }
        });

        // Sync Module switches
        moduleSwitches.forEach(function (sw) {
            const modKey = sw.getAttribute('data-module');
            const boxes = document.querySelectorAll('.mod-' + modKey);
            const checked = document.querySelectorAll('.mod-' + modKey + ':checked').length;
            if (boxes.length > 0) {
                sw.checked = (checked === boxes.length);
                sw.indeterminate = (checked > 0 && checked < boxes.length);
            }
        });
    }

    permBoxes.forEach(function (cb) {
        cb.addEventListener('change', syncState);
    });

    rowSwitches.forEach(function (sw) {
        sw.addEventListener('change', function () {
            const rowClass = this.getAttribute('data-row');
            const state = this.checked;
            document.querySelectorAll('.' + rowClass).forEach(cb => cb.checked = state);
            syncState();
        });
    });

    moduleSwitches.forEach(function (sw) {
        sw.addEventListener('change', function () {
            const modKey = this.getAttribute('data-module');
            const state = this.checked;
            document.querySelectorAll('.mod-' + modKey).forEach(cb => cb.checked = state);
            syncState();
        });
    });

    // Global Select/Deselect
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        permBoxes.forEach(cb => cb.checked = true);
        syncState();
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        permBoxes.forEach(cb => cb.checked = false);
        syncState();
    });

    // Live search filter
    if (moduleFilter) {
        moduleFilter.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.module-card-block').forEach(function (block) {
                const headerTitle = block.querySelector('.module-header-title')?.textContent.toLowerCase() || '';
                const rows = block.querySelectorAll('.submodule-row-item');
                let visRows = 0;

                rows.forEach(function (row) {
                    const title = row.querySelector('.submodule-title')?.textContent.toLowerCase() || '';
                    if (!q || headerTitle.includes(q) || title.includes(q)) {
                        row.style.display = '';
                        visRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                block.style.display = (visRows > 0) ? '' : 'none';
            });
        });
    }

    // Auto slug generator from Role Name
    const roleNameInput = document.getElementById('role_name');
    const roleSlugInput = document.getElementById('role_slug');
    let isSlugManual = <?= $isEdit ? 'true' : 'false' ?>;

    if (roleSlugInput) {
        roleSlugInput.addEventListener('input', function () {
            isSlugManual = true;
        });
    }

    if (roleNameInput && roleSlugInput) {
        roleNameInput.addEventListener('input', function () {
            if (!isSlugManual || !roleSlugInput.value) {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                roleSlugInput.value = slug;
            }
        });
    }

    // Initial sync
    syncState();
});

function toggleConcernScope(val) {
    const container = document.getElementById('specificConcernsContainer');
    if (!container) return;
    if (val === 'SPECIFIC') {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}
</script>
