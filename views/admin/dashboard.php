<?php
$canCreateUnit = hasPermission('dashboard', 'create') || hasPermission('production', 'create');
$canEditUnit   = hasPermission('dashboard', 'edit')   || hasPermission('production', 'edit');
$canDeleteUnit = hasPermission('dashboard', 'delete') || hasPermission('production', 'delete');
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
        <p class="text-muted small mb-0">Production facilities, workforce directory, and system health overview.</p>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. TOP HIGHLIGHT STATS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Production Units -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Production Facilities</div>
                    <div class="stat-value fw-bold text-primary fs-3 mt-1"><?= number_format($totalProductionUnits ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Units</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-gear-wide-connected text-primary me-1"></i>Apparel Manufacturing
                    </div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary p-3 rounded-circle">
                    <i class="bi bi-gear-wide-connected fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Staff -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Total Staff</div>
                    <div class="stat-value fw-bold text-success fs-3 mt-1"><?= number_format($totalStaff ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Members</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-people text-success me-1"></i>Workforce Directory
                    </div>
                </div>
                <div class="stat-icon bg-success-subtle text-success p-3 rounded-circle">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Production Programs -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Active Programs</div>
                    <div class="stat-value fw-bold text-warning fs-3 mt-1"><?= number_format($pendingProduction ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Lots</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-calendar-check text-warning me-1"></i>Scheduled &amp; In-Progress
                    </div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning p-3 rounded-circle">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending QC Batches -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Pending QC</div>
                    <div class="stat-value fw-bold text-info fs-3 mt-1"><?= number_format($pendingQC ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Batches</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-shield-check text-info me-1"></i>Quality Inspections
                    </div>
                </div>
                <div class="stat-icon bg-info-subtle text-info p-3 rounded-circle">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. SECONDARY PRODUCTION METRICS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-scissors fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($activeCutting ?? 0) ?></div>
                <div class="text-muted small">Active Cutting Batches</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-success bg-opacity-10 text-success">
                <i class="bi bi-bounding-box fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($activeStitching ?? 0) ?></div>
                <div class="text-muted small">Active Stitching Batches</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-info bg-opacity-10 text-info">
                <i class="bi bi-journal-bookmark fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($totalProducts ?? 0) ?></div>
                <div class="text-muted small">Registered Product Styles</div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. PRODUCTION FACILITIES MANAGEMENT TABLE -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-gear-wide-connected text-primary fs-5"></i>
            <h5 class="mb-0 fw-bold">Production Facilities</h5>
            <span class="badge bg-primary-subtle text-primary ms-2"><?= count($productionUnits ?? []) ?> Total</span>
        </div>
        <?php if ($canCreateUnit): ?>
        <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProductionUnit" onclick="resetUnitModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Production Unit
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3">Unit Details</th>
                        <th>Manager &amp; Contact</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productionUnits)): ?>
                        <?php foreach ($productionUnits as $unit): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($unit['logo_url'])): ?>
                                        <img src="<?= e($unit['logo_url']) ?>" alt="<?= e($unit['name']) ?> logo" class="rounded border bg-white p-1" style="width:40px;height:40px;object-fit:contain;">
                                    <?php endif; ?>
                                    <span class="badge bg-primary-subtle text-primary font-monospace fw-bold px-2 py-1"><?= e($unit['code']) ?></span>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($unit['name']) ?></div>
                                        <?php if (!empty($unit['gst_number'])): ?>
                                            <small class="text-muted font-monospace">GSTIN: <?= e($unit['gst_number']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($unit['manager_name'] ?: 'Unassigned') ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($unit['phone'] ?: '-') ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= e($unit['address'] ?: 'No address specified') ?></small>
                            </td>
                            <td>
                                <?php if ((int)($unit['is_active'] ?? 1) === 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($canEditUnit): ?>
                                    <button type="button" class="btn btn-outline-primary" title="Edit Unit" 
                                            onclick="editUnit(<?= htmlspecialchars(json_encode($unit), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($canDeleteUnit): ?>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Production Unit"
                                            onclick="confirmDeleteUnit(<?= (int)$unit['id'] ?>, '<?= addslashes(e($unit['name'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-gear-wide-connected fs-2 d-block mb-2 text-secondary"></i>
                                No production facilities registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. RECENT SYSTEM AUDIT ACTIVITY -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary fs-5"></i>
            <h5 class="mb-0 fw-bold">Recent System Activity</h5>
        </div>
        <a href="<?= url('system/audit') ?>" class="btn btn-sm btn-outline-primary">View Full Audit Log</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentAuditLogs)): ?>
                        <?php foreach ($recentAuditLogs as $log): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= e(date('d M Y, h:i A', strtotime($log['created_at'] ?? 'now'))) ?></td>
                            <td class="fw-semibold text-dark"><?= e($log['user_name'] ?? $log['username'] ?? 'System') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e(strtoupper($log['action'] ?? 'ACTIVITY')) ?></span>
                            </td>
                            <td><?= e($log['entity_type'] ?? '-') ?> <?= !empty($log['entity_id']) ? '#' . e($log['entity_id']) : '' ?></td>
                            <td class="text-muted small font-monospace"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No recent audit logs available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PRODUCTION UNIT (ADD & EDIT) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalProductionUnit" tabindex="-1" aria-labelledby="modalProductionUnitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form method="POST" id="formProductionUnit" action="<?= url('admin/production-units/store') ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalProductionUnitLabel"><i class="bi bi-gear-wide-connected me-2"></i>Add Production Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="unit_name" class="form-control" placeholder="e.g. Tirupur Main Unit 1" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Code</label>
                            <input type="text" name="code" id="unit_code" class="form-control font-monospace" placeholder="Auto-generated e.g. PU-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Manager</label>
                            <input type="text" name="manager_name" id="unit_manager_name" class="form-control" placeholder="e.g. Ramesh Kumar">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Phone</label>
                        <input type="tel" name="phone" id="unit_phone" class="form-control" placeholder="10-digit number" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">GST Number</label>
                        <input type="text" name="gst_number" id="unit_gst_number" class="form-control font-monospace text-uppercase" placeholder="33AAAAA0000A1Z5" maxlength="15">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Facility Address</label>
                        <textarea name="address" id="unit_address" class="form-control" rows="2" placeholder="Full address of the production unit..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="unit_logo">Unit Logo</label>
                        <input type="file" name="logo" id="unit_logo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="btnSubmitUnit">Save Production Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Production Unit Scripts -->
<script>
function resetUnitModal() {
    document.getElementById('formProductionUnit').action = '<?= url("admin/production-units/store") ?>';
    document.getElementById('modalProductionUnitLabel').innerHTML = '<i class="bi bi-gear-wide-connected me-2"></i>Add Production Unit';
    document.getElementById('unit_name').value = '';
    document.getElementById('unit_code').value = '';
    document.getElementById('unit_manager_name').value = '';
    document.getElementById('unit_phone').value = '';
    document.getElementById('unit_gst_number').value = '';
    document.getElementById('unit_address').value = '';
    document.getElementById('unit_logo').value = '';
}

function editUnit(unit) {
    document.getElementById('formProductionUnit').action = '<?= url("admin/production-units/update/") ?>' + unit.id;
    document.getElementById('modalProductionUnitLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Production Unit';
    document.getElementById('unit_name').value = unit.name || '';
    document.getElementById('unit_code').value = unit.code || '';
    document.getElementById('unit_manager_name').value = unit.manager_name || '';
    document.getElementById('unit_phone').value = unit.phone || '';
    document.getElementById('unit_gst_number').value = unit.gst_number || '';
    document.getElementById('unit_address').value = unit.address || '';
    document.getElementById('unit_logo').value = '';
    new bootstrap.Modal(document.getElementById('modalProductionUnit')).show();
}

function confirmDeleteUnit(id, name) {
    showConfirmModal({
        title: 'Delete Production Unit',
        message: 'Are you sure you want to delete production facility "' + name + '"? This action cannot be undone.',
        confirmText: 'Delete Facility',
        confirmBtnClass: 'btn-danger',
        actionUrl: '<?= url("admin/production-units/delete/") ?>' + id
    });
}
</script>
