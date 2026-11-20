<?php
// Create Maintenance Ticket View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Log Asset Maintenance Ticket</h4>
            <p class="text-muted small mb-0">Record breakdown repairs, periodic servicing, calibrations, or AMC inspections</p>
        </div>
        <div>
            <a href="<?= url('assets/maintenance') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Logs
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('assets/maintenance/store') ?>">
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
                                <input type="text" name="log_number" class="form-control font-monospace fw-bold bg-light" value="<?= e($nextLogNumber) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Target Machine / Equipment <span class="text-danger">*</span></label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">-- Choose Machine --</option>
                                    <?php foreach ($assets as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $selectedAssetId == $a['id'] ? 'selected' : '' ?>>
                                        [<?= e($a['asset_code']) ?>] <?= e($a['name']) ?> (<?= e($a['brand']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service Type <span class="text-danger">*</span></label>
                                <select name="service_type" class="form-select" required>
                                    <option value="ROUTINE_PREVENTIVE" selected>Routine Preventive Servicing</option>
                                    <option value="BREAKDOWN_REPAIR">Breakdown Repair &amp; Troubleshooting</option>
                                    <option value="CALIBRATION">Equipment Calibration &amp; Alignment</option>
                                    <option value="PART_REPLACEMENT">Spare Part Replacement</option>
                                    <option value="ANNUAL_AMC">Annual Maintenance Contract (AMC) Audit</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service Date <span class="text-danger">*</span></label>
                                <input type="date" name="service_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Fault / Issue Description</label>
                                <textarea name="issue_description" class="form-control" rows="2" placeholder="e.g. Thread breaking frequently, motor heating up, oil leakage, blade dull"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Corrective Action Taken</label>
                                <textarea name="action_taken" class="form-control" rows="2" placeholder="e.g. Replaced tension spring, lubricated gears, sharpened cutting blade, rewired pedal"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Parts Replaced (if any)</label>
                                <input type="text" name="parts_replaced" class="form-control" placeholder="e.g. Needle bar clamp, timing belt, feed dog">
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
                                <input type="text" name="serviced_by_vendor" class="form-control" placeholder="e.g. Juki Authorized Service / In-House Maintenance">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Technician Name</label>
                                <input type="text" name="technician_name" class="form-control" placeholder="Technician name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Technician Contact Phone</label>
                                <input type="text" name="technician_contact" class="form-control" placeholder="Phone #">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Service / Repair Cost (₹)</label>
                                <input type="number" step="0.01" name="service_cost" class="form-control fw-bold" placeholder="0.00" value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Machine Downtime (Hours)</label>
                                <input type="number" step="0.5" name="downtime_hours" class="form-control" placeholder="0.0" value="0.0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Next Service Due Date</label>
                                <input type="date" name="next_due_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Ticket Status</label>
                                <select name="status" class="form-select">
                                    <option value="COMPLETED" selected>COMPLETED (Machine Restored to Service)</option>
                                    <option value="IN_PROGRESS">IN_PROGRESS (Currently Under Repair)</option>
                                    <option value="PENDING">PENDING (Waiting for Parts / Technician)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Maintenance Record
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
