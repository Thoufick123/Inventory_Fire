<?php
/**
 * Staff Directory / Management List View
 */
$currentDept = strtolower($filters['department'] ?? 'all');
if (empty($currentDept)) $currentDept = 'all';
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Staff Management</h1>
            <p class="text-muted mb-0">Manage department staff members, facility assignments, and module access control.</p>
        </div>
        <div>
            <?php if (hasPermission('staff', 'create')): ?>
            <a href="<?= url('admin/staff/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add Staff Member</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Cards Container -->
    <div id="staffKpiContainer">
        <div class="row g-3 mb-4">
            <?php
            $totalStaff = count($staff);
            $activeCount = count(array_filter($staff, fn($s) => (int)$s['is_active'] === 1));
            $inactiveCount = $totalStaff - $activeCount;
            $totalRoles = count($roles);
            ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="me-2" style="min-width: 0;">
                            <div class="text-uppercase fw-semibold text-muted small"><?= ($currentDept !== 'all' && !empty($currentDept)) ? 'Total ' . ucfirst($currentDept) . ' Staff' : 'Total Staffs' ?></div>
                            <div class="fs-3 fw-bold text-primary mt-1"><?= $totalStaff ?></div>
                            <div class="small text-muted mt-2 text-truncate"><i class="bi bi-people-fill text-primary me-1"></i>Staff directory members</div>
                        </div>
                        <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="me-2" style="min-width: 0;">
                            <div class="text-uppercase fw-semibold text-muted small">Active Accounts</div>
                            <div class="fs-3 fw-bold text-success mt-1"><?= $activeCount ?></div>
                            <div class="small text-muted mt-2 text-truncate"><i class="bi bi-person-check-fill text-success me-1"></i>Currently enabled logins</div>
                        </div>
                        <div class="bg-success-subtle text-success revenue-kpi-icon">
                            <i class="bi bi-person-check-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="me-2" style="min-width: 0;">
                            <div class="text-uppercase fw-semibold text-muted small">Deactivated</div>
                            <div class="fs-3 fw-bold text-warning-emphasis mt-1"><?= $inactiveCount ?></div>
                            <div class="small text-muted mt-2 text-truncate"><i class="bi bi-person-dash-fill text-warning me-1"></i>Disabled staff accounts</div>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                            <i class="bi bi-person-dash-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="me-2" style="min-width: 0;">
                            <div class="text-uppercase fw-semibold text-muted small">Active Roles</div>
                            <div class="fs-3 fw-bold text-info-emphasis mt-1"><?= $totalRoles ?></div>
                            <div class="small text-muted mt-2 text-truncate"><i class="bi bi-shield-check text-info me-1"></i>Configured RBAC roles</div>
                        </div>
                        <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card (Instant Live Filter) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('admin/staff') ?>" id="staffFilterForm" class="row g-3 align-items-center">
                <input type="hidden" name="department" id="filterDepartment" value="<?= e($currentDept) ?>">

                <!-- Search Input -->
                <div class="col-12 col-lg-3 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name, username, or email..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>

                <!-- Concern Filter Dropdown -->
                <div class="col-12 col-lg-3 col-md-4">
                    <select name="concern_id" id="concernSelect" class="form-select">
                        <option value="">All Authorized Concerns</option>
                        <?php foreach (($concerns ?? []) as $concern): ?>
                            <option value="<?= $concern['id'] ?>" <?= (string)($filters['concern_id'] ?? '') === (string)$concern['id'] ? 'selected' : '' ?>>
                                <?= e($concern['code']) ?> - <?= e($concern['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Roles Dropdown -->
                <div class="col-12 col-lg-2 col-md-4">
                    <select name="role_id" id="roleSelect" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= (string)($filters['role_id'] ?? '') === (string)$role['id'] ? 'selected' : '' ?>>
                                <?= e($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Facility / Unit Dropdown -->
                <div class="col-12 col-lg-2 col-md-4">
                    <?php if ($currentDept === 'production'): ?>
                        <select name="production_unit_id" id="unitSelect" class="form-select">
                            <option value="">All Production Units</option>
                            <?php foreach ($productionUnits as $pu): ?>
                                <option value="<?= $pu['id'] ?>" <?= (string)($filters['production_unit_id'] ?? '') === (string)$pu['id'] ? 'selected' : '' ?>>
                                    <?= e($pu['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <select name="status" id="statusSelect" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" <?= isset($filters['is_active']) && (int)$filters['is_active'] === 1 ? 'selected' : '' ?>>Active Only</option>
                            <option value="0" <?= isset($filters['is_active']) && (int)$filters['is_active'] === 0 ? 'selected' : '' ?>>Inactive Only</option>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Reset Button & Live Status Indicator -->
                <div class="col-12 col-lg-2 col-md-4 d-flex gap-2">
                    <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Table Container -->
    <div id="staffTableContainer" style="transition: opacity 0.2s ease;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <?= ($currentDept !== 'all' && !empty($currentDept)) ? ucfirst($currentDept) . ' Department Directory' : 'All Staff Directory' ?>
                </h5>
                <span class="badge bg-light text-dark border"><?= count($staff) ?> Staff Members</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="min-width: 200px;">Staff Member</th>
                            <th style="min-width: 130px;">Department &amp; Scope</th>
                            <th style="min-width: 170px;">Role &amp; Facility</th>
                            <th style="min-width: 160px;">Authorized Concerns</th>
                            <th style="min-width: 90px;">Status</th>
                            <th class="text-end pe-4" style="min-width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staff)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                    No staff members found for this criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($staff as $s): ?>
                                <?php
                                $userDept = strtoupper($s['department'] ?? 'GENERAL');
                                $deptBadgeClass = match($userDept) {
                                    'PRODUCTION' => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'WAREHOUSE'  => 'bg-success-subtle text-success border-success-subtle',
                                    'WHATSAPP'   => 'bg-success-subtle text-success border-success-subtle',
                                    'WEBSITE'    => 'bg-info-subtle text-info border-info-subtle',
                                    'SHOP'       => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                    default      => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                };
                                $deptIcon = match($userDept) {
                                    'PRODUCTION' => 'bi-gear-wide-connected',
                                    'WAREHOUSE'  => 'bi-buildings',
                                    'WHATSAPP'   => 'bi-whatsapp',
                                    'WEBSITE'    => 'bi-globe',
                                    'SHOP'       => 'bi-shop',
                                    default      => 'bi-building'
                                };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3 bg-primary text-white fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 42px; height: 42px; font-size: 16px;">
                                                <?= strtoupper(substr($s['full_name'] ?? $s['username'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= e($s['full_name']) ?></div>
                                                <div class="text-muted small">@<?= e($s['username']) ?> &bull; <?= e($s['email'] ?: 'No email') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge border px-2 py-1 <?= $deptBadgeClass ?>">
                                            <i class="bi <?= $deptIcon ?> me-1"></i><?= e($userDept) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 me-1">
                                                <i class="bi bi-shield-check me-1"></i><?= e($s['role_name'] ?? 'Unassigned') ?>
                                            </span>
                                            <?php if (!empty($s['staff_role'])): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1">
                                                    <?= match($s['staff_role']) {
                                                        'CUTTING_MASTER' => '<i class="bi bi-scissors text-danger me-1"></i>Cutting Master',
                                                        'TAILOR'         => '<i class="bi bi-person-workspace text-primary me-1"></i>Tailor',
                                                        'QC_INSPECTOR'   => '<i class="bi bi-patch-check-fill text-success me-1"></i>QC Inspector',
                                                        default          => e($s['staff_role'])
                                                    } ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php if (!empty($s['production_unit_name'])): ?>
                                                <span class="me-2"><i class="bi bi-gear-wide me-1"></i><?= e($s['production_unit_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['warehouse_name'])): ?>
                                                <span class="me-2"><i class="bi bi-buildings me-1"></i><?= e($s['warehouse_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['channel_name'])): ?>
                                                <?php $chanIcon = strtoupper($s['department'] ?? '') === 'WHATSAPP' ? 'bi-whatsapp text-success' : 'bi-globe text-info'; ?>
                                                <span class="me-2"><i class="bi <?= $chanIcon ?> me-1"></i><?= e($s['channel_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['shop_name'])): ?>
                                                <span><i class="bi bi-shop me-1"></i><?= e($s['shop_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (empty($s['production_unit_name']) && empty($s['warehouse_name']) && empty($s['channel_name']) && empty($s['shop_name'])): ?>
                                                <span class="fst-italic text-muted">All Units / Channels</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['assigned_concerns_str'])): ?>
                                            <?php 
                                            $concernEntries = explode('||', $s['assigned_concerns_str']);
                                            foreach ($concernEntries as $idx => $entry):
                                                if (empty(trim($entry))) continue;
                                                $parts = explode(':', $entry, 2);
                                                $cCode = $parts[0] ?? '';
                                                $cName = $parts[1] ?? '';
                                                $isDefault = ($idx === 0);
                                            ?>
                                                <span class="badge <?= $isDefault ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border' ?> mb-1 me-1" title="<?= e($cName) ?><?= $isDefault ? ' (Default Session)' : '' ?>">
                                                    <i class="bi <?= $isDefault ? 'bi-star-fill text-warning me-1' : 'bi-building me-1' ?>"></i><?= e($cCode) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">None assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['is_active']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-circle-fill me-1 small"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-circle me-1 small"></i>Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4" style="white-space: nowrap;">
                                        <div class="d-flex align-items-center justify-content-end gap-1 flex-nowrap">
                                            <a href="<?= url('admin/staff/view/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Staff Details">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                            <?php if (hasPermission('staff', 'edit')): ?>
                                            <a href="<?= url('admin/staff/edit/' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit Staff Member">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <form method="POST" action="<?= url('admin/staff/toggle/' . $s['id']) ?>" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to <?= $s['is_active'] ? 'deactivate' : 'activate' ?> this staff member?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm <?= $s['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $s['is_active'] ? 'Deactivate Staff Member' : 'Activate Staff Member' ?>">
                                                    <i class="bi <?= $s['is_active'] ? 'bi-person-x' : 'bi-person-check' ?> me-1"></i><?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if (hasPermission('staff', 'delete')): ?>
                                            <form method="POST" action="<?= url('admin/staff/delete/' . $s['id']) ?>" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to permanently delete staff member \'<?= e($s['full_name']) ?>\'?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Staff Member">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('staffFilterForm');
    if (!form) return;

    const searchInput = document.getElementById('searchInput');
    const selects = form.querySelectorAll('select');
    const resetBtn = document.getElementById('resetFiltersBtn');
    let debounceTimeout = null;

    function executeDynamicFilter() {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (const [key, val] of formData.entries()) {
            if (val.trim() !== '') {
                params.set(key, val.trim());
            }
        }

        const baseUrl = form.getAttribute('action');
        const targetUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

        const tableContainer = document.getElementById('staffTableContainer');
        const kpiContainer = document.getElementById('staffKpiContainer');
        if (tableContainer) tableContainer.style.opacity = '0.4';

        fetch(targetUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTable = doc.getElementById('staffTableContainer');
            const newKpi = doc.getElementById('staffKpiContainer');

            if (newTable && tableContainer) {
                tableContainer.innerHTML = newTable.innerHTML;
            }
            if (newKpi && kpiContainer) {
                kpiContainer.innerHTML = newKpi.innerHTML;
            }

            // Update browser URL silently
            window.history.replaceState({}, '', targetUrl);
        })
        .catch(err => {
            console.error('Filter request failed, reloading fallback:', err);
            window.location.href = targetUrl;
        })
        .finally(() => {
            if (tableContainer) tableContainer.style.opacity = '1';
        });
    }

    // Auto trigger on dropdown change
    selects.forEach(select => {
        select.addEventListener('change', executeDynamicFilter);
    });

    // Auto trigger on search typing (debounced)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(executeDynamicFilter, 250);
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            selects.forEach(sel => sel.value = '');
            executeDynamicFilter();
        });
    }

    // Prevent default form submit and use instant live filter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        executeDynamicFilter();
    });
});
</script>
