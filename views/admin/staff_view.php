<?php
$staffPermissionIds = array_map('intval', $staffPermissions);
$permissionGroups = [];
$visiblePermissionCount = 0;

foreach ($hierarchicalModules as $module) {
    $accessibleSubmodules = [];
    foreach ($module['submodules'] ?? [] as $submodule) {
        $grantedActions = [];
        foreach ($submodule['permissions'] ?? [] as $action => $permission) {
            if (in_array((int)$permission['id'], $staffPermissionIds, true)) {
                $grantedActions[] = $action;
            }
        }
        if ($grantedActions) {
            $accessibleSubmodules[] = [
                'name' => $submodule['name'] ?? 'Module',
                'actions' => $grantedActions,
            ];
            $visiblePermissionCount += count($grantedActions);
        }
    }

    if ($accessibleSubmodules) {
        $permissionGroups[] = [
            'title' => $module['title'] ?? 'Module',
            'icon' => $module['icon'] ?? 'bi-grid',
            'submodules' => $accessibleSubmodules,
        ];
    }
}
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i>Staff Details</h2>
        <span class="text-muted">Read-only profile and access assignment</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/staff') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Staff
        </a>
        <?php if (hasPermission('staff', 'edit')): ?>
            <a href="<?= url('admin/staff/edit/' . $staff['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><?= e($staff['full_name']) ?></h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Employee ID</div>
                <div><?= e($staff['emp_id'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Username</div>
                <div><?= e($staff['username'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Email</div>
                <div><?= e($staff['email'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Phone</div>
                <div><?= e($staff['phone'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Role</div>
                <div><?= e($staff['role_name'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Department</div>
                <div><?= e($staff['department'] ?? '—') ?></div>
            </div>
            <?php foreach ($assignments as $label => $value): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="text-muted small fw-semibold"><?= e($label) ?></div>
                    <div><?= e($value) ?></div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Reports Access</div>
                <div><?= !empty($staff['reports_access']) ? 'Enabled' : 'Disabled' ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Status</div>
                <span class="badge <?= !empty($staff['is_active']) ? 'bg-success' : 'bg-secondary' ?>">
                    <?= !empty($staff['is_active']) ? 'Active' : 'Inactive' ?>
                </span>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Created</div>
                <div><?= e($staff['created_at'] ?? '—') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="text-muted small fw-semibold">Last Updated</div>
                <div><?= e($staff['updated_at'] ?? '—') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Authorized Business Concerns & Scoping Profile Card -->
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-buildings-fill me-2 text-primary"></i>Authorized Business Concerns &amp; Scoping</h5>
            <small class="text-muted">Sister concerns and operational entities this staff member is permitted to access</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border">
            <?= count($userConcerns ?? []) ?> Authorized <?= count($userConcerns ?? []) === 1 ? 'Concern' : 'Concerns' ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (empty($userConcerns)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-building-slash fs-1 d-block mb-2"></i>
                <span>No specific concerns assigned. Staff operates under default business context.</span>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($userConcerns as $uc): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100 border rounded-3 p-3 <?= !empty($uc['is_default']) ? 'border-primary bg-primary bg-opacity-10' : 'bg-light bg-opacity-25' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-secondary-subtle text-primary border fw-bold font-monospace"><?= e($uc['code']) ?></span>
                                <?php if (!empty($uc['is_default'])): ?>
                                    <span class="badge bg-warning text-dark border border-warning-subtle">
                                        <i class="bi bi-star-fill me-1"></i>Default Session
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold text-dark fs-6 mb-1"><?= e($uc['name']) ?></div>
                            <?php if (!empty($uc['legal_name'])): ?>
                                <div class="text-muted small mb-1"><?= e($uc['legal_name']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($uc['gst_number'])): ?>
                                <div class="text-muted small font-monospace"><i class="bi bi-receipt me-1"></i>GSTIN: <?= e($uc['gst_number']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Access Permissions</h5>
            <small class="text-muted">Effective module permissions for this staff member</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border">
            <?= $visiblePermissionCount ?> permissions
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($permissionGroups as $module): ?>
                <div class="col-12 col-xl-6">
                    <div class="border rounded-3 h-100">
                        <div class="px-3 py-2 bg-light border-bottom fw-semibold">
                            <i class="bi <?= e($module['icon']) ?> me-2 text-primary"></i><?= e($module['title']) ?>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($module['submodules'] as $submodule): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
                                    <span class="fw-medium"><?= e($submodule['name']) ?></span>
                                    <span class="d-flex flex-wrap gap-1">
                                        <?php foreach ($submodule['actions'] as $action): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= e(ucfirst(str_replace('_', ' ', $action))) ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$permissionGroups): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-shield-x fs-3 d-block mb-2"></i>
                No module permissions are currently assigned.
            </div>
        <?php endif; ?>
    </div>
</div>
