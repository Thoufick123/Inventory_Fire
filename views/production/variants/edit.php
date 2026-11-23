<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Product Variants <?= e($variant['variant_code']) ?></h2>
    </div>
    <div>
        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Product Variants
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="bi bi-sliders me-2 text-primary"></i>Variant Properties
                </h5>
                <span class="badge bg-dark-subtle text-dark font-monospace px-3 py-2 fw-bold">
                    <?= e($variant['variant_code']) ?>
                </span>
            </div>
            <div class="card-body p-4">
                <?php if ($hasHistory): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2 small mb-4">
                    <i class="bi bi-shield-lock-fill fs-5 text-warning-emphasis"></i>
                    <div>
                        <strong>Immutable Identity:</strong> This variant already has historical stock, batch, or billing records. Color, Design, Size, SKU, and QR code are locked to preserve audit integrity. Pricing and active status remain editable.
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('production/variants/update/' . $variant['id']) ?>">
                    <?= csrf_field() ?>

                    <!-- Read-only Catalog & Identifiers -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">Parent Catalog / Style</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= e($variant['parent_sku'] . ' — ' . $variant['product_name']) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small fw-semibold">Variant Code</label>
                            <input type="text" class="form-control bg-light font-monospace fw-bold" value="<?= e($variant['variant_code']) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small fw-semibold">Variant SKU</label>
                            <input type="text" class="form-control bg-light font-monospace fw-bold" value="<?= e($variant['sku']) ?>" readonly>
                        </div>
                    </div>

                    <!-- Attribute Selection -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Color</label>
                            <select name="color_id" class="form-select" <?= $hasHistory ? 'disabled' : '' ?>>
                                <option value="">-- No Color / Standard --</option>
                                <?php foreach ($colors as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $variant['color_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Design / Pattern</label>
                            <select name="design_id" class="form-select" <?= $hasHistory ? 'disabled' : '' ?>>
                                <option value="">-- No Design / Solid --</option>
                                <?php foreach ($designs as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $variant['design_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Size</label>
                            <select name="size_id" class="form-select" <?= $hasHistory ? 'disabled' : '' ?>>
                                <option value="">-- No Size / Free Size --</option>
                                <?php foreach ($sizes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $variant['size_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Pricing & Tax Details -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">MRP (₹)</label>
                            <input type="number" step="0.01" min="0" name="mrp" class="form-control" value="<?= e($variant['mrp']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Selling Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="selling_price" class="form-control fw-bold" value="<?= e($variant['selling_price']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Production Cost (₹)</label>
                            <input type="number" step="0.01" min="0" name="production_cost" class="form-control" value="<?= e($variant['production_cost']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">HSN Code</label>
                            <input type="text" name="hsn_code" class="form-control font-monospace" value="<?= e($variant['hsn_code'] ?? '6109') ?>">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Active Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?= (int)$variant['is_active'] === 1 ? 'selected' : '' ?>>Active (Can be produced &amp; billed)</option>
                            <option value="0" <?= (int)$variant['is_active'] === 0 ? 'selected' : '' ?>>Inactive (Suspended from production &amp; billing)</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-save me-1"></i> Update Variant
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR Code & Stock Summary -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 text-center p-4">
            <h6 class="fw-bold text-dark mb-2">Scannable QR Identity</h6>
            <div class="d-flex justify-content-center my-3">
                <div id="editQrContainer" class="p-2 border rounded bg-white shadow-sm"></div>
            </div>
            <div class="font-monospace fw-bold fs-6 text-dark mb-3"><?= e($variant['qr_code'] ?: $variant['sku']) ?></div>

            <a href="<?= url('production/variants/qr-label/' . $variant['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i> Print Thermal Label
            </a>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrBox = document.getElementById('editQrContainer');
    new QRCode(qrBox, {
        text: <?= json_encode((string)($variant['qr_code'] ?: $variant['sku'])) ?>,
        width: 140,
        height: 140,
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
