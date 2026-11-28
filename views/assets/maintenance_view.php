<?php
// Maintenance Ticket View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-wrench-adjustable text-primary me-2"></i>Service Ticket: <span class="font-monospace text-primary"><?= e($log['log_number']) ?></span>
            </h4>
            <p class="text-muted small mb-0">Serviced on <?= date('d M Y', strtotime($log['service_date'])) ?> · <?= e($log['asset_name']) ?> (<?= e($log['asset_code']) ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('assets/maintenance') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Logs
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Service &amp; Repair Report</h6>
                    <div>
                        <span class="badge <?= $log['status'] === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-2 fs-6">
                            <?= e($log['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Machine / Asset</span>
                            <h5 class="fw-bold text-dark mb-0"><?= e($log['asset_name']) ?></h5>
                            <span class="font-monospace small text-primary"><?= e($log['asset_code']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Service Classification</span>
                            <span class="badge bg-light text-dark border fs-6 mt-1"><?= e($log['service_type']) ?></span>
                        </div>

                        <div class="col-md-12">
                            <span class="text-muted small d-block fw-semibold">Reported Problem / Issue</span>
                            <div class="p-3 bg-light rounded-3 mt-1 small text-dark">
                                <?= nl2br(e($log['issue_description'] ?: 'Routine periodic preventive checkup.')) ?>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <span class="text-muted small d-block fw-semibold">Action Taken &amp; Work Done</span>
                            <div class="p-3 bg-light rounded-3 mt-1 small text-dark">
                                <?= nl2br(e($log['action_taken'] ?: 'General cleaning, greasing, oil replacement and calibration performed.')) ?>
                            </div>
                        </div>

                        <?php if (!empty($log['parts_replaced'])): ?>
                        <div class="col-md-12">
                            <span class="text-muted small d-block fw-semibold">Parts Replaced</span>
                            <div class="badge bg-secondary-subtle text-secondary fs-6 p-2">
                                <i class="bi bi-gear-wide me-1"></i><?= e($log['parts_replaced']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-wallet2 text-primary me-2"></i>Cost &amp; Downtime</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Total Service Expense</span>
                        <h4 class="fw-bold text-dark mb-0"><?= formatCurrency((float)($log['service_cost'])) ?></h4>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Equipment Downtime</span>
                        <h5 class="fw-bold text-danger mb-0"><?= $log['downtime_hours'] ?> Hours</h5>
                    </div>
                    <?php if ($log['next_due_date']): ?>
                    <div>
                        <span class="text-muted small d-block">Next Service Due</span>
                        <strong class="text-primary"><?= date('d M Y', strtotime($log['next_due_date'])) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge text-primary me-2"></i>Service Technician</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <span class="text-muted d-block">Company / Vendor:</span>
                        <strong><?= e($log['serviced_by_vendor'] ?: 'In-House Maintenance') ?></strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Technician:</span>
                        <strong><?= e($log['technician_name'] ?: 'Maintenance Team') ?></strong>
                    </div>
                    <?php if ($log['technician_contact']): ?>
                    <div class="mb-2">
                        <span class="text-muted d-block">Contact Phone:</span>
                        <strong><?= e($log['technician_contact']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-muted d-block">Logged By:</span>
                        <span><?= e($log['creator_name'] ?: 'Staff') ?> on <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
