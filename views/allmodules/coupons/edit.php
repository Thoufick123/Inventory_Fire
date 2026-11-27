<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit POS Coupon: <?= e($coupon['coupon_code']) ?></h4>
        <div class="text-muted small">Update coupon discount amount, minimum bill rule, or status</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('allmodules/coupons') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="<?= url('allmodules/coupons/update/' . (int)$coupon['id']) ?>">
    <?= CSRF::input() ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Coupon Code <span class="text-danger">*</span></label>
                    <input type="text" name="coupon_code" class="form-control text-uppercase font-monospace fw-bold" required maxlength="40" value="<?= e($coupon['coupon_code']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Title / Description</label>
                    <input type="text" name="title" class="form-control" maxlength="150" value="<?= e($coupon['title'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="ACTIVE" <?= strtoupper((string)$coupon['status']) === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                        <option value="INACTIVE" <?= strtoupper((string)$coupon['status']) !== 'ACTIVE' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                    <select name="discount_type" class="form-select" required>
                        <option value="FIXED" <?= strtoupper((string)$coupon['discount_type']) === 'FIXED' ? 'selected' : '' ?>>Fixed Amount (₹)</option>
                        <option value="PERCENT" <?= strtoupper((string)$coupon['discount_type']) === 'PERCENT' ? 'selected' : '' ?>>Percentage (%)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Discount Amount / Value <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control font-monospace fw-bold" required value="<?= e(number_format((float)$coupon['discount_value'], 2, '.', '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Minimum Bill Amount to Use (₹)</label>
                    <input type="number" step="0.01" min="0" name="min_redemption_amount" class="form-control font-monospace" value="<?= e(number_format((float)($coupon['min_redemption_amount'] ?? 0), 2, '.', '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Max Uses Limit</label>
                    <input type="number" min="0" name="max_uses" class="form-control font-monospace" value="<?= (int)($coupon['max_uses'] ?? 0) ?>">
                    <div class="form-text">Used so far: <strong><?= (int)($coupon['used_count'] ?? 0) ?></strong>. (0 = Unlimited)</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Expiry Date <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="date" name="expires_at" class="form-control" value="<?= e($coupon['expires_at'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= url('allmodules/coupons') ?>">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check2-circle me-1"></i>Update Coupon</button>
            </div>
        </div>
    </div>
</form>
