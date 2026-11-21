<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>New Mapping Invoice</h2>
        <span class="text-muted small">Save the supplier and invoice now. Products can be mapped, QR-labeled, and stocked before the purchase entry is recorded.</span>
    </div>
    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Mapping
    </a>
</div>

<div class="card border shadow-sm" style="max-width: 760px;">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Supplier Invoice Details</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= url('purchases/mapping/create') ?>">
            <?= CSRF::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Supplier / Person <span class="text-danger">*</span></label>
                    <select class="form-select" name="seller_id" required>
                        <option value="">-- Select supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>"><?= e($seller['company'] ? $seller['company'] . ' (' . $seller['name'] . ')' : $seller['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Supplier Invoice Number <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" class="form-control font-monospace" maxlength="100" required>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        This does not create a Purchases bill. When the Purchases team later records the same supplier and invoice number, saved products will be added to that bill automatically after you choose <strong>Move to Purchase</strong>.
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2-circle me-1"></i>Save Invoice in Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>
