<?php
// Maintenance Logs List View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Asset Maintenance &amp; Repair Logs</h4>
            <p class="text-muted small mb-0">Record and monitor equipment breakdowns, routine servicing, AMC, and parts replaced</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('assets/maintenance/create') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Log Maintenance Ticket
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('assets/maintenance') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Log #, Asset Code, Machine, Technician..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="service_type" class="form-select">
                        <option value="">All Service Types</option>
                        <option value="ROUTINE_PREVENTIVE" <?= $filters['service_type'] === 'ROUTINE_PREVENTIVE' ? 'selected' : '' ?>>Routine Preventive</option>
                        <option value="BREAKDOWN_REPAIR" <?= $filters['service_type'] === 'BREAKDOWN_REPAIR' ? 'selected' : '' ?>>Breakdown Repair</option>
                        <option value="CALIBRATION" <?= $filters['service_type'] === 'CALIBRATION' ? 'selected' : '' ?>>Calibration</option>
                        <option value="PART_REPLACEMENT" <?= $filters['service_type'] === 'PART_REPLACEMENT' ? 'selected' : '' ?>>Part Replacement</option>
                        <option value="ANNUAL_AMC" <?= $filters['service_type'] === 'ANNUAL_AMC' ? 'selected' : '' ?>>Annual AMC</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="COMPLETED" <?= $filters['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                        <option value="IN_PROGRESS" <?= $filters['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress / Servicing</option>
                        <option value="PENDING" <?= $filters['status'] === 'PENDING' ? 'selected' : '' ?>>Pending Review</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold flex-grow-1 shadow-xs">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="<?= url('assets/maintenance') ?>" class="btn btn-outline-secondary shadow-xs" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Log #</th>
                            <th>Service Date</th>
                            <th>Machine / Equipment</th>
                            <th>Service Type</th>
                            <th>Issue / Action</th>
                            <th>Technician / Vendor</th>
                            <th class="text-center">Cost (₹)</th>
                            <th class="text-center">Downtime</th>
                            <th>Status</th>
                            <th>Concern</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-tools fs-1 d-block mb-2 text-secondary"></i>
                                No maintenance service tickets logged yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="ps-3 fw-bold font-monospace text-primary">
                                <a href="<?= url('assets/maintenance/view/' . $l['id']) ?>" class="text-decoration-none">
                                    <?= e($l['log_number']) ?>
                                </a>
                            </td>
                            <td><?= date('d M Y', strtotime($l['service_date'])) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($l['asset_name']) ?></div>
                                <small class="text-muted font-monospace"><?= e($l['asset_code']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($l['service_type']) ?></span></td>
                            <td>
                                <div class="small fw-semibold text-truncate" style="max-width: 200px;" title="<?= e($l['issue_description']) ?>">
                                    <?= e($l['issue_description'] ?: 'Routine Service') ?>
                                </div>
                            </td>
                            <td>
                                <div class="small"><?= e($l['technician_name'] ?: ($l['serviced_by_vendor'] ?: 'In-House')) ?></div>
                            </td>
                            <td class="text-center fw-bold"><?= formatCurrency((float)($l['service_cost'])) ?></td>
                            <td class="text-center"><?= $l['downtime_hours'] ?> hrs</td>
                            <td>
                                <span class="badge <?= $l['status'] === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= e($l['status']) ?>
                                </span>
                            </td>
                            <td><?= renderConcernBadge($l, 'concern_name', 'concern_code', 'asset_concern_id') ?></td>
                            <td class="text-end pe-3">
                                <a href="<?= url('assets/maintenance/view/' . $l['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i> View
                                </a>
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
