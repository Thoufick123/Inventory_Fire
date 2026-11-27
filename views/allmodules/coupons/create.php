<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Create POS Coupon</h4>
        <div class="text-muted small">Define coupon code, discount amount, and minimum bill usage rules</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('allmodules/coupons') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="<?= url('allmodules/coupons/store') ?>">
    <?= CSRF::input() ?>
    <?php $concernSelectWrapCard = true; include BASE_PATH . '/views/partials/admin_concern_select.php'; unset($concernSelectWrapCard); ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Coupon Code <span class="text-danger">*</span></label>
                    <input type="text" name="coupon_code" class="form-control text-uppercase font-monospace fw-bold" required maxlength="40" placeholder="e.g. SAVE200">
                    <div class="form-text">Letters, numbers, `-` or `_` (no spaces).</div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Title / Description</label>
                    <input type="text" name="title" class="form-control" maxlength="150" placeholder="e.g. Festive Flat ₹200 Off">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="ACTIVE" selected>Active</option>
                        <option value="INACTIVE">Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                    <select name="discount_type" class="form-select" required>
                        <option value="FIXED">Fixed Amount (₹)</option>
                        <option value="PERCENT">Percentage (%)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Discount Amount / Value <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control font-monospace fw-bold" required placeholder="e.g. 200.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Minimum Bill Amount to Use (₹)</label>
                    <input type="number" step="0.01" min="0" name="min_redemption_amount" class="form-control font-monospace" value="0.00" placeholder="0.00">
                    <div class="form-text">0.00 means no minimum bill amount required.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Max Uses Limit</label>
                    <input type="number" min="0" name="max_uses" class="form-control font-monospace" value="0">
                    <div class="form-text">0 = Unlimited uses; 1 = Single use; N = Max N bills.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Expiry Date <span class="text-muted fw-normal">(Optional)</span></label>
                    <input type="date" name="expires_at" class="form-control">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= url('allmodules/coupons') ?>">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check2-circle me-1"></i>Save Coupon</button>
            </div>
        </div>
    </div>
</form>
