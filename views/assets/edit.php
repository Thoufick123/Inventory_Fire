<?php
// Edit Asset View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Asset: <span class="font-monospace text-primary"><?= e($asset['asset_code']) ?></span></h4>
            <p class="text-muted small mb-0">Update technical specifications, location, custodian, and maintenance status</p>
        </div>
        <div>
            <a href="<?= url('assets/view/' . $asset['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Asset Passport
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('assets/update/' . $asset['id']) ?>">
        <?= csrfField() ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-text text-primary me-2"></i>Asset Master</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Asset Code</label>
                                <input type="text" class="form-control font-monospace fw-bold bg-light" value="<?= e($asset['asset_code']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $asset['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Asset Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= e($asset['name']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Brand</label>
                                <input type="text" name="brand" class="form-control" value="<?= e($asset['brand']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Model Number</label>
                                <input type="text" name="model_number" class="form-control" value="<?= e($asset['model_number']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Serial Number</label>
                                <input type="text" name="serial_number" class="form-control font-monospace" value="<?= e($asset['serial_number']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Location Type</label>
                                <select name="location_type" class="form-select">
                                    <option value="PRODUCTION" <?= $asset['location_type'] === 'PRODUCTION' ? 'selected' : '' ?>>Factory / Production Unit</option>
                                    <option value="WAREHOUSE" <?= $asset['location_type'] === 'WAREHOUSE' ? 'selected' : '' ?>>Central Warehouse</option>
                                    <option value="OFFICE" <?= $asset['location_type'] === 'OFFICE' ? 'selected' : '' ?>>Administrative Office</option>
                                    <option value="SHOP" <?= $asset['location_type'] === 'SHOP' ? 'selected' : '' ?>>Retail Outlet</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Floor / Department</label>
                                <input type="text" name="floor_area" class="form-control" value="<?= e($asset['floor_area']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Custodian / In-Charge</label>
                                <input type="text" name="custodian_name" class="form-control" value="<?= e($asset['custodian_name']) ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Technical Specifications</label>
                                <textarea name="specifications" class="form-control form-control-sm" rows="2"><?= e($asset['specifications']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders text-primary me-2"></i>Status &amp; Financials</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Status</label>
                                <select name="status" class="form-select">
                                    <option value="IN_USE" <?= $asset['status'] === 'IN_USE' ? 'selected' : '' ?>>IN_USE (Operational)</option>
                                    <option value="IDLE" <?= $asset['status'] === 'IDLE' ? 'selected' : '' ?>>IDLE (Standby)</option>
                                    <option value="IN_MAINTENANCE" <?= $asset['status'] === 'IN_MAINTENANCE' ? 'selected' : '' ?>>IN_MAINTENANCE (Under Repair)</option>
                                    <option value="DAMAGED" <?= $asset['status'] === 'DAMAGED' ? 'selected' : '' ?>>DAMAGED (Unusable)</option>
                                    <option value="DISPOSED" <?= $asset['status'] === 'DISPOSED' ? 'selected' : '' ?>>DISPOSED (Scrapped)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Condition</label>
                                <select name="condition_state" class="form-select">
                                    <option value="EXCELLENT" <?= $asset['condition_state'] === 'EXCELLENT' ? 'selected' : '' ?>>Excellent</option>
                                    <option value="GOOD" <?= $asset['condition_state'] === 'GOOD' ? 'selected' : '' ?>>Good</option>
                                    <option value="FAIR" <?= $asset['condition_state'] === 'FAIR' ? 'selected' : '' ?>>Fair</option>
                                    <option value="POOR" <?= $asset['condition_state'] === 'POOR' ? 'selected' : '' ?>>Poor</option>
                                    <option value="CRITICAL" <?= $asset['condition_state'] === 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Purchase Cost (₹)</label>
                                <input type="number" step="0.01" name="purchase_cost" class="form-control" value="<?= e($asset['purchase_cost']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Current Book Value (₹)</label>
                                <input type="number" step="0.01" name="current_book_value" class="form-control fw-bold text-primary" value="<?= e($asset['current_book_value']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Vendor Name</label>
                                <input type="text" name="vendor_name" class="form-control" value="<?= e($asset['vendor_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Vendor Phone</label>
                                <input type="text" name="vendor_contact" class="form-control" value="<?= e($asset['vendor_contact']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Invoice Number</label>
                                <input type="text" name="invoice_number" class="form-control" value="<?= e($asset['invoice_number']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Warranty Expiry</label>
                                <input type="date" name="warranty_expiry" class="form-control" value="<?= e($asset['warranty_expiry']) ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Internal Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="3"><?= e($asset['notes']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
