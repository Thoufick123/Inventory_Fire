<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-shield-check text-primary me-2"></i><?= e($role['name']) ?></h2>
        <p class="text-muted small mb-0">
            Identifier: <code class="text-primary"><?= e($role['slug']) ?></code>
            <span class="mx-2">&bull;</span>
            <?php if (!empty($role['is_active'])): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Inactive</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('roles', 'edit')): ?>
            <a href="<?= url('admin/roles/edit/' . $role['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit Role
            </a>
        <?php endif; ?>
        <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Role Summary</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small fw-semibold text-uppercase">Description</div>
                    <div class="mt-1"><?= e($role['description'] ?: 'No description provided.') ?></div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small fw-semibold text-uppercase">Assigned Permissions</div>
                        <div class="fs-5 fw-bold text-primary"><?= count($rolePermissions ?? []) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-semibold text-uppercase">Assigned Staff</div>
                        <div class="fs-5 fw-bold text-dark"><?= count($assignedUsers ?? []) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Assigned Staff Members</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($assignedUsers)): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($assignedUsers as $u): ?>
                            <a href="<?= url('admin/staff/view/' . $u['id']) ?>" class="badge bg-light text-dark border text-decoration-none py-2 px-2.5">
                                <i class="bi bi-person me-1"></i><?= e($u['full_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">No staff members are currently assigned to this role.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Module &amp; Submodule Permissions</h6>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        <?php $assignedSet = array_flip(array_map('intval', $rolePermissions ?? [])); ?>
        <?php foreach ($hierarchicalModules as $modKey => $module): ?>
            <?php $submodules = $module['submodules'] ?? []; ?>
            <div class="card border border-light-subtle rounded-3">
                <div class="card-header bg-light bg-opacity-50 py-2 px-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= e($module['icon'] ?? 'bi-grid') ?> text-primary"></i>
                        <strong><?= e($module['title'] ?? ucfirst($modKey)) ?></strong>
                    </div>
                    <span class="badge bg-light text-dark border"><?= count($submodules) ?> submodules</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-uppercase small text-muted" style="font-size: 11px;">
                                <th class="ps-3 py-2" style="width: 35%;">Submodule</th>
                                <th class="text-center py-2" style="width: 13%;">View</th>
                                <th class="text-center py-2" style="width: 13%;">Create</th>
                                <th class="text-center py-2" style="width: 13%;">Verify</th>
                                <th class="text-center py-2" style="width: 13%;">Edit</th>
                                <th class="text-center py-2" style="width: 13%;">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submodules as $subKey => $sub): ?>
                                <?php $crudColumns = Role::getCrudPermissionsByAction($sub['permissions'] ?? []); ?>
                                <tr>
                                    <td class="ps-3 py-2">
                                        <div class="fw-semibold text-dark"><?= e($sub['name'] ?? ucfirst($subKey)) ?></div>
                                        <?php if (!empty($sub['desc'])): ?>
                                            <div class="text-muted small" style="font-size: 11px;"><?= e($sub['desc']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach (['view', 'create', 'verify', 'edit', 'delete'] as $crudAction): ?>
                                        <td class="text-center py-2">
                                            <?php $colPerms = $crudColumns[$crudAction] ?? []; ?>
                                            <?php if (!empty($colPerms)): ?>
                                                <?php
                                                $checked = false;
                                                foreach ($colPerms as $p) {
                                                    if (isset($assignedSet[(int)$p['id']])) {
                                                        $checked = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($checked): ?>
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-dash-circle text-muted opacity-25"></i>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted opacity-25">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
