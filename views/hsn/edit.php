<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit HSN Code: <?= e($hsn['hsn_code']) ?></h2>
        <p class="text-muted small mb-0">Modify tariff classification code and description details.</p>
    </div>
    <div>
        <a href="<?= url('hsn') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Catalog</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card border shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan me-2 text-primary"></i>Update HSN Details</h5>
            </div>
            <form method="POST" action="<?= url('hsn/update/' . $hsn['id']) ?>">
                <?= CSRF::field() ?>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label required fw-bold text-dark">HSN Code *</label>
                        <input type="text" name="hsn_code" class="form-control font-monospace" value="<?= e($hsn['hsn_code']) ?>" maxlength="20" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required fw-bold text-dark">Commodity / Item Description *</label>
                        <textarea name="description" class="form-control" rows="4" required><?= e($hsn['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Default GST Rate (%) <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="gst_rate" class="form-control" value="<?= $hsn['gst_rate'] !== null ? e($hsn['gst_rate']) : '' ?>" placeholder="e.g. 5.00, 12.00, 18.00">
                    </div>
                </div>
                <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
                    <a href="<?= url('hsn') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i>Update HSN Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
