<?php
// Edit Maintenance Ticket View (matches Create Maintenance Ticket layout & fields)
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Maintenance Ticket: <span class="font-monospace text-primary"><?= e($log['log_number']) ?></span></h4>
            <p class="text-muted small mb-0">Update breakdown repairs, periodic servicing, calibrations, or AMC inspections</p>
        </div>
        <div>
            <a href="<?= url('assets/maintenance') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Logs
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('assets/maintenance/update/' . $log['id']) ?>">
        <?= CSRF::field() ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tools text-primary me-2"></i>Service &amp; Machine Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Log Number</label>
                                <input type="text" name="log_number" class="form-control font-monospace fw-bold bg-light" value="<?= e($log['log_number']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Target Machine / Equipment <span class="text-danger">*</span></label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">-- Choose Machine --</option>
                                    <?php foreach ($assets as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= (int)$log['asset_id'] === (int)$a['id'] ? 'selected' : '' ?>>
                                        [<?= e($a['asset_code']) ?>] <?= e($a['name']) ?> (<?= e($a['brand']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service Type <span class="text-danger">*</span></label>
                                <select name="service_type" class="form-select" required>
                                    <option value="ROUTINE_PREVENTIVE" <?= $log['service_type'] === 'ROUTINE_PREVENTIVE' ? 'selected' : '' ?>>Routine Preventive Servicing</option>
                                    <option value="BREAKDOWN_REPAIR" <?= $log['service_type'] === 'BREAKDOWN_REPAIR' ? 'selected' : '' ?>>Breakdown Repair &amp; Troubleshooting</option>
                                    <option value="CALIBRATION" <?= $log['service_type'] === 'CALIBRATION' ? 'selected' : '' ?>>Equipment Calibration &amp; Alignment</option>
                                    <option value="PART_REPLACEMENT" <?= $log['service_type'] === 'PART_REPLACEMENT' ? 'selected' : '' ?>>Spare Part Replacement</option>
                                    <option value="ANNUAL_AMC" <?= $log['service_type'] === 'ANNUAL_AMC' ? 'selected' : '' ?>>Annual Maintenance Contract (AMC) Audit</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service Date <span class="text-danger">*</span></label>
                                <input type="date" name="service_date" class="form-control" value="<?= e($log['service_date'] ?: date('Y-m-d')) ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Fault / Issue Description</label>
                                <textarea name="issue_description" class="form-control" rows="2" placeholder="e.g. Thread breaking frequently, motor heating up, oil leakage, blade dull"><?= e($log['issue_description']) ?></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Corrective Action Taken</label>
                                <textarea name="action_taken" class="form-control" rows="2" placeholder="e.g. Replaced tension spring, lubricated gears, sharpened cutting blade, rewired pedal"><?= e($log['action_taken']) ?></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Parts Replaced (if any)</label>
                                <input type="text" name="parts_replaced" class="form-control" value="<?= e($log['parts_replaced']) ?>" placeholder="e.g. Needle bar clamp, timing belt, feed dog">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-gear text-primary me-2"></i>Technician &amp; Financials</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Service Provider / Vendor Company</label>
                                <input type="text" name="serviced_by_vendor" class="form-control" value="<?= e($log['serviced_by_vendor']) ?>" placeholder="e.g. Juki Authorized Service / In-House Maintenance">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Technician Name</label>
                                <input type="text" name="technician_name" class="form-control" value="<?= e($log['technician_name']) ?>" placeholder="Technician name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Technician Contact Phone</label>
                                <input type="text" name="technician_contact" class="form-control" value="<?= e($log['technician_contact']) ?>" placeholder="Phone #">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service / Repair Cost (₹)</label>
                                <input type="number" step="0.01" name="service_cost" class="form-control fw-bold" value="<?= e($log['service_cost'] ?? '0.00') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Machine Downtime (Hours)</label>
                                <input type="number" step="0.5" name="downtime_hours" class="form-control" value="<?= e($log['downtime_hours'] ?? '0.0') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Next Service Due Date</label>
                                <input type="date" name="next_due_date" class="form-control" value="<?= e($log['next_due_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Ticket Status</label>
                                <select name="status" class="form-select">
                                    <option value="COMPLETED" <?= $log['status'] === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED (Machine Restored to Service)</option>
                                    <option value="IN_PROGRESS" <?= $log['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>IN_PROGRESS (Currently Under Repair)</option>
                                    <option value="PENDING" <?= $log['status'] === 'PENDING' ? 'selected' : '' ?>>PENDING (Waiting for Parts / Technician)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
