<?php
/**
 * Roles & Permissions Directory View
 */
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold">Roles & Permissions</h1>
        </div>
        <div>
            <?php if (hasPermission('roles', 'create')): ?>
            <a href="<?= url('admin/roles/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>Add New Role</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($roles)): ?>
    <!-- Roles Table Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold">Custom Roles</h5>
            <span class="badge bg-light text-dark border"><?= count($roles) ?> Total Roles</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="min-width:140px;">Role Name</th>
                        <th style="min-width:160px;">Identifier (Slug)</th>
                        <th style="min-width:160px;">Description</th>
                        <th style="min-width:120px;">Assigned Users</th>
                        <th style="min-width:90px;">Status</th>
                        <th class="text-end pe-4" style="min-width:270px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody">
                    <?php foreach ($roles as $r): ?>
                        <tr class="role-row">
                            <td class="ps-4">
                                <div class="fw-semibold text-dark"><?= e($r['name']) ?></div>
                            </td>
                            <td>
                                <code class="text-primary small"><?= e($r['slug']) ?></code>
                            </td>
                            <td>
                                <span class="text-muted small"><?= e($r['description'] ?: '—') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-person me-1"></i><?= (int)$r['user_count'] ?> Users
                                </span>
                            </td>
                            <td>
                                <?php if ($r['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex align-items-center justify-content-end gap-2 flex-nowrap">
                                    <?php if (hasPermission('roles', 'view')): ?>
                                    <a href="<?= url('admin/roles/view/' . $r['id']) ?>"
                                       class="btn btn-sm btn-outline-info"
                                       title="View Role">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('roles', 'edit')): ?>
                                    <a href="<?= url('admin/roles/edit/' . $r['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Edit Role">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('roles', 'delete')): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete Role"
                                            onclick="openDeleteRoleModal(<?= (int)$r['id'] ?>, <?= htmlspecialchars(json_encode((string)$r['name']), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    $paginationPrefix = 'roles';
    $paginationItemName = 'roles';
    $paginationTotal = count($roles ?? []);
    $paginationDefaultSize = 10;
    include BASE_PATH . '/views/partials/pagination_bar.php';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.initClientPagination === 'function') {
            window.initClientPagination({
                container: '#rolesTableBody',
                itemSelector: '.role-row',
                footer: '#rolesPaginationFooter',
                infoEl: '#rolesPageInfo',
                paginationList: '#rolesPaginationList',
                pageSizeSelect: '#rolesPageSizeSelect',
                pageSize: 10,
                itemName: 'roles'
            });
        }
    });
    </script>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center p-3 mb-3 text-primary" style="width: 64px; height: 64px;">
                <i class="bi bi-shield-lock fs-2"></i>
            </div>
            <h5 class="fw-bold mb-1">No Custom Staff Roles Found</h5>
            <p class="text-muted mb-3">There are currently no custom roles created. You can create custom roles with tailored module permissions.</p>
            <?php if (hasPermission('roles', 'create')): ?>
            <a href="<?= url('admin/roles/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Add New Role</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Role Confirmation Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="deleteRoleForm" class="modal-content border-0 shadow">
            <?= CSRF::field() ?>
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to delete the <strong id="deleteRoleName">this</strong> role?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>Delete Role
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteRoleModal(roleId, roleName) {
    const form = document.getElementById('deleteRoleForm');
    const nameEl = document.getElementById('deleteRoleName');
    if (form) {
        form.action = '<?= url('admin/roles/delete/') ?>' + roleId;
    }
    if (nameEl) {
        nameEl.textContent = roleName || 'this';
    }
    const modalEl = document.getElementById('deleteRoleModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}
</script>
