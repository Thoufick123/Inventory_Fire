<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Create QR Generation</h2>
    </div>
    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to QR Generations
    </a>
</div>

<div class="card border shadow-sm w-100">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Supplier Invoice Details</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= url('purchases/mapping/create') ?>">
            <?= CSRF::field() ?>
            <div class="row g-3">
                <?php $concernSelectColClass = 'col-12'; include BASE_PATH . '/views/partials/admin_concern_select.php'; ?>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <label class="form-label fw-semibold mb-2">Supplier / Person <span class="text-danger">*</span></label>
                        <?php if (hasPermission('sellers', 'create')): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#createMappingSupplierModal">
                                <i class="bi bi-person-plus me-1"></i>Create Supplier
                            </button>
                        <?php endif; ?>
                    </div>
                    <select class="form-select" name="seller_id" id="mappingSellerSelect" required>
                        <option value="">-- Select supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>" <?= (int)($selectedSellerId ?? 0) === (int)$seller['id'] ? 'selected' : '' ?>><?= e($seller['company'] ? $seller['company'] . ' (' . $seller['name'] . ')' : $seller['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Supplier Invoice Number</label>
                    <div class="input-group">
                        <input type="text" name="invoice_number" id="mappingInvoiceNumber" class="form-control font-monospace" maxlength="100" placeholder="Optional — auto-generated if blank" value="<?= e($invoiceNumber ?? '') ?>" autocomplete="off">
                        <a href="#" id="btnUseExistingMappingInvoice" class="btn btn-warning fw-semibold d-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Use Existing Invoice
                        </a>
                    </div>
                    <div id="mappingInvoiceError" class="invalid-feedback d-none fw-semibold"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="mappingInvoiceDate">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="invoice_date" id="mappingInvoiceDate" class="form-control" required value="<?= e($_GET['invoice_date'] ?? date('Y-m-d')) ?>">
                </div>

                <!-- Existing Invoice All Data Preview Card -->
                <div class="col-12 d-none" id="existingInvoicePreviewCard">
                    <div class="card border-warning shadow-sm">
                        <div class="card-header bg-warning-subtle text-dark py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="fw-bold small"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Existing Invoice Found — All Recorded Data</span>
                            <a href="#" id="btnUseExistingMappingInvoiceCard" class="btn btn-sm btn-warning fw-semibold">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Use Existing Invoice
                            </a>
                        </div>
                        <div class="card-body p-3" id="existingInvoicePreviewBody"></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        This does not create a Purchases bill. When the Purchases team later records the same supplier and invoice number, saved products will be added to that bill automatically after you choose <strong>Move to Purchase</strong>.
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary px-4" type="submit" id="btnSaveNewMappingInvoice"><i class="bi bi-check2-circle me-1"></i>Save Invoice in Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sellerSel = document.getElementById('mappingSellerSelect');
    const invInput = document.getElementById('mappingInvoiceNumber');
    const errEl = document.getElementById('mappingInvoiceError');
    const btnUse = document.getElementById('btnUseExistingMappingInvoice');
    const btnUseCard = document.getElementById('btnUseExistingMappingInvoiceCard');
    const previewCard = document.getElementById('existingInvoicePreviewCard');
    const previewBody = document.getElementById('existingInvoicePreviewBody');
    const btnSave = document.getElementById('btnSaveNewMappingInvoice');
    let debounceTimer = null;

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function clearExistingState() {
        invInput?.classList.remove('is-invalid');
        if (errEl) { errEl.classList.add('d-none'); errEl.textContent = ''; }
        btnUse?.classList.add('d-none');
        previewCard?.classList.add('d-none');
        if (previewBody) previewBody.innerHTML = '';
        if (btnSave) btnSave.disabled = false;
    }

    function checkExistingInvoice() {
        // Purchases and QR Generations are independent; do not show duplicate-invoice errors or block entry here.
        clearExistingState();
    }

    function scheduleCheck() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkExistingInvoice, 250);
    }

    sellerSel?.addEventListener('change', scheduleCheck);
    invInput?.addEventListener('input', scheduleCheck);
    invInput?.addEventListener('blur', checkExistingInvoice);
    if (sellerSel?.value && invInput?.value.trim()) {
        checkExistingInvoice();
    }
});
</script>

<?php if (hasPermission('sellers', 'create')): ?>
<div class="modal fade" id="createMappingSupplierModal" tabindex="-1" aria-labelledby="createMappingSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('production/sellers/store') ?>" id="createMappingSupplierForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="supplier_profile_only" value="1">
                <input type="hidden" name="return_to" value="purchases/mapping/create">
                <input type="hidden" name="return_invoice_number" id="mappingReturnInvoiceNumber" value="<?= e($invoiceNumber ?? '') ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createMappingSupplierModalLabel">
                        <i class="bi bi-person-plus me-2 text-primary"></i>Create Supplier
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Person / Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control text-uppercase" required minlength="2" maxlength="200" autocomplete="name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="company" class="form-control text-uppercase" required minlength="2" maxlength="300" autocomplete="organization">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" autocomplete="tel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" maxlength="255" autocomplete="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN / Tax ID</label>
                            <input type="text" name="gst_number" class="form-control text-uppercase font-monospace" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City / District <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control text-uppercase" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                            <input type="text" name="state" class="form-control text-uppercase" required maxlength="100" value="TAMIL NADU">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Full Business Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control text-uppercase" required maxlength="500" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('createMappingSupplierForm').addEventListener('submit', function() {
    document.getElementById('mappingReturnInvoiceNumber').value =
        document.getElementById('mappingInvoiceNumber').value;
});
</script>
<?php endif; ?>
