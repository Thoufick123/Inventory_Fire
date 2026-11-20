<?php
$isEdit = !empty($bill);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$existingItems = array_map(static function (array $item): array {
    return [
        'qc_item_id' => (int)$item['qc_item_id'],
        'variant_id' => (int)($item['variant_id'] ?? 0),
        'product_name' => (string)$item['product_name'],
        'sku' => (string)($item['sku'] ?? ''),
        'hsn_code' => (string)($item['hsn_code'] ?? '6109'),
        'size_name' => (string)($item['size_name'] ?? ''),
        'color_name' => (string)($item['color_name'] ?? ''),
        'mrp' => (float)$item['mrp'],
        'unit_price' => (float)$item['unit_price'],
        'is_complementary' => (int)($item['is_complementary'] ?? 0),
        'gst_percentage' => (float)$item['gst_percentage'],
        'gst_type' => (string)($item['gst_type'] ?? ((float)$item['gst_percentage'] > 0 ? 'BOTH' : 'EXEMPTED')),
        'tax_input_type' => (string)($item['tax_input_type'] ?? 'PERCENT'),
        'sgst_rate' => (float)($item['sgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'cgst_rate' => (float)($item['cgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'igst_rate' => (float)($item['igst_rate'] ?? 0),
        'tags' => $item['tags'],
        'available_tags' => $item['tags'],
    ];
}, $billItems ?? []);
$selectedBuyerType = $bill['buyer_type'] ?? 'COMPANY';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <img id="productionUnitLogo" src="<?= $escape($prodUnit['logo_url'] ?? '') ?>" alt="<?= $escape($prodUnit['name'] ?? 'Production unit') ?> logo" class="mb-2 <?= empty($prodUnit['logo_url']) ? 'd-none' : '' ?>" style="max-width:180px;max-height:64px;object-fit:contain;">
        <h4 class="mb-1 fw-bold"><?= $isEdit ? 'Edit Production Bill' : 'Create Production Bill' ?></h4>
        <div class="text-muted small">Scan QR labels from available QC-passed Production Inventory.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="<?= url($isEdit ? 'production/billing/update/' . (int)$bill['id'] : 'production/billing/store') ?>" id="productionBillForm">
    <?= CSRF::input() ?>
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-building-gear me-2 text-primary"></i>Production Unit</div>
                <div class="card-body">
                    <label class="form-label" for="productionUnitSelect">Bill issued by</label>
                    <select class="form-select" name="production_unit_id" id="productionUnitSelect" required>
                        <?php foreach ($productionUnits ?? [] as $unit): ?>
                            <option value="<?= (int)$unit['id'] ?>" <?= (int)($bill['production_unit_id'] ?? $prodUnit['id'] ?? 0) === (int)$unit['id'] ? 'selected' : '' ?> data-name="<?= $escape($unit['name'] ?? '') ?>" data-code="<?= $escape($unit['code'] ?? '') ?>" data-manager="<?= $escape($unit['manager_name'] ?? '') ?>" data-address="<?= $escape($unit['address'] ?? '') ?>" data-phone="<?= $escape($unit['phone'] ?? '') ?>" data-gstin="<?= $escape($unit['gst_number'] ?? '') ?>" data-logo="<?= $escape($unit['logo_url'] ?? '') ?>">
                                <?= $escape($unit['name'] ?? 'Production unit') ?><?= !empty($unit['code']) ? ' (' . $escape($unit['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="productionUnitDetails" class="small text-muted mt-2" aria-live="polite"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold" for="buyerType">Bill To</label>
                    <select class="form-select fw-semibold text-primary" name="buyer_type" id="buyerType">
                        <option value="COMPANY" <?= ($bill['buyer_type'] ?? 'COMPANY') === 'COMPANY' ? 'selected' : '' ?>>Company</option>
                        <option value="CUSTOMER" <?= ($bill['buyer_type'] ?? '') === 'CUSTOMER' ? 'selected' : '' ?>>Customer</option>
                    </select>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3 <?= $selectedBuyerType === 'COMPANY' ? '' : 'd-none' ?>" id="companyFields">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2 text-primary"></i>Company (Buyer)</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Company name <span class="text-danger">*</span></label><input class="form-control" name="company_name" data-required-for="COMPANY" value="<?= $escape($bill['company_name'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address" rows="2"><?= $escape($bill['company_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="company_phone" value="<?= $escape($bill['company_phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="company_email" value="<?= $escape($bill['company_email'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase" name="company_gstin" maxlength="30" value="<?= $escape($bill['company_gstin'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3 <?= $selectedBuyerType === 'CUSTOMER' ? '' : 'd-none' ?>" id="customerFields">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-2 text-primary"></i>Customer / Contact</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Customer name <span class="text-danger">*</span></label><input class="form-control" name="customer_name" data-required-for="CUSTOMER" value="<?= $escape($bill['customer_name'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Phone <span class="text-danger">*</span></label><input class="form-control" name="customer_phone" data-required-for="CUSTOMER" value="<?= $escape($bill['customer_phone'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="customer_address" rows="2"><?= $escape($bill['customer_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">City</label><input class="form-control" name="customer_city" value="<?= $escape($bill['customer_city'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">State</label><input class="form-control" name="customer_state" value="<?= $escape($bill['customer_state'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase" name="customer_gstin" maxlength="30" value="<?= $escape($bill['customer_gstin'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-credit-card me-2 text-primary"></i>Invoice &amp; Payment</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Bill date</label><input class="form-control" type="date" name="bill_date" required value="<?= $escape($bill['bill_date'] ?? date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label class="form-label">Payment method</label>
                        <select class="form-select" name="payment_method" id="paymentMethod">
                            <?php foreach (['CASH' => 'Cash', 'UPI' => 'UPI', 'CASH_UPI' => 'Cash + UPI', 'CARD' => 'Card', 'NET_BANKING' => 'Net Banking', 'CREDIT' => 'Credit'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($bill['payment_method'] ?? 'CASH') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="splitPaymentFields" class="row g-2 <?= ($bill['payment_method'] ?? '') === 'CASH_UPI' ? '' : 'd-none' ?>">
                        <div class="col-6"><label class="form-label">Cash amount</label><input class="form-control" type="number" step="0.01" min="0" name="cash_amount" value="<?= $escape($bill['cash_amount'] ?? '0.00') ?>"></div>
                        <div class="col-6"><label class="form-label">UPI amount</label><input class="form-control" type="number" step="0.01" min="0" name="upi_amount" value="<?= $escape($bill['upi_amount'] ?? '0.00') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">UPI reference</label><input class="form-control" name="upi_reference" id="upiReference" maxlength="150" value="<?= $escape($bill['upi_reference'] ?? '') ?>"></div>
                    <div class="mt-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"><?= $escape($bill['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-upc-scan me-2 text-primary"></i>Scan Production Inventory QR</div>
                <div class="card-body">
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label fw-semibold mb-0" for="inventorySearch"><i class="bi bi-qr-code-scan text-success me-1"></i>Scan Inventory Barcode / QR Tag</label>
                            <span class="small text-muted"><i class="bi bi-upc me-1"></i>Hardware barcode scanner</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-qr-code text-success"></i></span>
                            <input id="inventorySearch" class="form-control" autocomplete="off" placeholder="Scan a finished-goods QR or enter product / SKU">
                            <button class="btn btn-success fw-semibold" type="button" id="scanAddButton"><i class="bi bi-plus-circle me-1"></i>Scan / Add</button>
                            <button class="btn btn-outline-primary" type="button" id="openCameraScanner" aria-label="Scan QR with camera" title="Scan QR with camera"><i class="bi bi-camera"></i></button>
                        </div>
                    </div>
                    <div id="searchResults" class="list-group mt-2"></div>
                    <div id="cartMessage" class="small text-muted mt-2">Only unbilled, available finished-goods QR labels that passed QC can be added.</div>
                </div>
            </div>

            <div class="modal fade" id="cameraScanModal" tabindex="-1" aria-labelledby="cameraScanModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cameraScanModalLabel">Scan Production QR</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="productionBillQrReader" class="w-100"></div>
                            <div id="cameraScanMessage" class="small text-muted mt-2" aria-live="polite">Allow camera access to scan a production label.</div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Product / QR codes</th><th class="text-center" style="width:130px">Qty</th><th class="text-end">MRP</th><th style="width:100px">Discount %</th><th style="width:120px">Price / piece<br><small>incl. tax</small></th><th style="min-width:330px">GST / Tax details</th><th class="text-end">Tax (Incl.)</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody id="billCart"><tr><td colspan="9" class="text-center text-muted py-5">Scan or search for items to add to this bill.</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row justify-content-end">
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Gross / MRP</span><strong id="grossTotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Discount</span><strong id="discountTotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Taxable Base</span><strong id="subtotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Total GST (Incl.)</span><strong id="taxTotal">₹0.00</strong></div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong id="grandTotal">₹0.00</strong></div>
                            <div id="splitBalance" class="small text-muted text-end mt-2"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>">Cancel</a>
                        <button class="btn btn-primary" id="saveBillButton"><i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Bill' ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modern Floating POS Stock Notification Toast -->
<div id="modernStockAlertToast" class="position-fixed top-0 start-50 translate-middle-x mt-4 d-none shadow-lg rounded-4" style="z-index: 9999; min-width: 340px; max-width: 92vw; transition: all 0.3s ease;">
    <div class="card border-0 rounded-4 overflow-hidden shadow" style="background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); border-left: 6px solid #fd7e14 !important;">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 46px; height: 46px;">
                <i class="bi bi-box-seam-fill fs-4 text-warning"></i>
            </div>
            <div class="flex-grow-1 pe-2">
                <div class="fw-bold text-dark mb-0 fs-6" id="stockAlertToastTitle">Available stock limit reached</div>
                <div class="text-secondary small mb-0 lh-sm mt-1" id="stockAlertToastMessage"></div>
            </div>
            <button type="button" class="btn-close flex-shrink-0" id="stockAlertToastClose" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Modern Inventory Limit Modal -->
<div class="modal fade" id="inventoryLimitModal" tabindex="-1" aria-labelledby="inventoryLimitTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-warning-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>POS Limit Alert</span>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width:64px;height:64px">
                    <i class="bi bi-box-seam-fill fs-2 text-warning"></i>
                </div>
                <h5 class="fw-bold mb-2 text-dark" id="inventoryLimitTitle">Available Stock Limit Reached</h5>
                <p class="text-secondary small mb-0 lh-base" id="inventoryLimitMessage" aria-live="polite"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-3">
                <button type="button" class="btn btn-primary px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('productionBillForm');
    const initialItems = <?= json_encode($existingItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const billId = <?= (int)($bill['id'] ?? 0) ?>;
    const cart = new Map();
    const search = document.getElementById('inventorySearch');
    const resultBox = document.getElementById('searchResults');
    const cartBody = document.getElementById('billCart');
    const buyerType = document.getElementById('buyerType');
    const unitSelect = document.getElementById('productionUnitSelect');
    const unitDetails = document.getElementById('productionUnitDetails');
    const money = value => '₹' + Number(value || 0).toFixed(2);
    let searchTimer;
    let cameraScanner = null;
    let cameraScanBusy = false;
    let toastTimer = null;
    let limitModalInstance = null;

    // POS Audio feedback using Web Audio API
    function playBeep(type = 'success') {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);
                osc.start();
                osc.stop(ctx.currentTime + 0.12);
            } else {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(320, ctx.currentTime);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.28);
                osc.start();
                osc.stop(ctx.currentTime + 0.28);
            }
        } catch (e) {
            // Audio context not allowed without prior user gesture, fail gracefully
        }
    }

    function getLimitModal() {
        if (!limitModalInstance && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const el = document.getElementById('inventoryLimitModal');
            if (el) limitModalInstance = bootstrap.Modal.getOrCreateInstance(el);
        }
        return limitModalInstance;
    }

    function showInventoryLimit(title, message, targetInput = null) {
        playBeep('warning');
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            try { navigator.vibrate([80, 40, 80]); } catch (e) {}
        }

        // 1. Floating Modern Toast Notification
        const toastEl = document.getElementById('modernStockAlertToast');
        const toastTitle = document.getElementById('stockAlertToastTitle');
        const toastMsg = document.getElementById('stockAlertToastMessage');
        if (toastEl && toastTitle && toastMsg) {
            toastTitle.textContent = title;
            toastMsg.textContent = message;
            toastEl.classList.remove('d-none');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toastEl.classList.add('d-none');
            }, 4500);
        }

        // 2. Modern Bootstrap Modal
        const modal = getLimitModal();
        const modalTitle = document.getElementById('inventoryLimitTitle');
        const modalMsg = document.getElementById('inventoryLimitMessage');
        if (modalTitle) modalTitle.textContent = title;
        if (modalMsg) modalMsg.textContent = message;
        if (modal) {
            modal.show();
        }

        // 3. Highlight Target Input
        if (targetInput) {
            targetInput.classList.add('is-invalid', 'border-warning');
            setTimeout(() => {
                targetInput.classList.remove('is-invalid', 'border-warning');
            }, 2000);
            targetInput.focus();
        }

        // 4. Cart Message Box
        const cartMsg = document.getElementById('cartMessage');
        if (cartMsg) {
            cartMsg.innerHTML = `<div class="alert alert-warning py-1.5 px-3 fw-semibold small mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${message}</div>`;
        }
    }

    function showSuccessToast(message) {
        playBeep('success');
        const cartMsg = document.getElementById('cartMessage');
        if (cartMsg) {
            cartMsg.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i> ${message}</span>`;
        }
    }

    // Dismiss toast button
    const toastCloseBtn = document.getElementById('stockAlertToastClose');
    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', () => {
            const toastEl = document.getElementById('modernStockAlertToast');
            if (toastEl) toastEl.classList.add('d-none');
        });
    }

    function updateProductionUnitDetails() {
        const option = unitSelect.selectedOptions[0];
        if (!option) {
            unitDetails.textContent = 'No active production unit is available.';
            return;
        }
        const unitLogo = document.getElementById('productionUnitLogo');
        unitLogo.classList.toggle('d-none', !option.dataset.logo);
        if (option.dataset.logo) unitLogo.src = option.dataset.logo;
        unitLogo.alt = `${option.dataset.name || 'Production unit'} logo`;
        unitDetails.textContent = [option.dataset.name, option.dataset.code, option.dataset.manager, option.dataset.address, option.dataset.phone, option.dataset.gstin ? `GSTIN: ${option.dataset.gstin}` : '']
            .filter(Boolean).join(' · ');
    }

    // Dynamically toggle Bill To sections: Company vs Customer
    function updateBuyerFields() {
        const selectedType = buyerType ? buyerType.value : 'COMPANY';
        const companyFields = document.getElementById('companyFields');
        const customerFields = document.getElementById('customerFields');
        const showCompany = selectedType === 'COMPANY';
        const showCustomer = selectedType === 'CUSTOMER';

        if (companyFields) {
            companyFields.classList.toggle('d-none', !showCompany);
            companyFields.querySelectorAll('input, textarea, select').forEach(input => {
                input.disabled = !showCompany;
            });
        }
        if (customerFields) {
            customerFields.classList.toggle('d-none', !showCustomer);
            customerFields.querySelectorAll('input, textarea, select').forEach(input => {
                input.disabled = !showCustomer;
            });
        }
        form.querySelectorAll('[data-required-for]').forEach(input => {
            input.required = (input.dataset.requiredFor === selectedType);
        });
    }

    function updatePaymentFields() {
        const method = document.getElementById('paymentMethod').value;
        const isSplit = method === 'CASH_UPI';
        const needsUpiReference = method === 'UPI' || isSplit;
        document.getElementById('splitPaymentFields').classList.toggle('d-none', !isSplit);
        form.elements.cash_amount.required = isSplit;
        form.elements.upi_amount.required = isSplit;
        document.getElementById('upiReference').required = needsUpiReference;
        updateTotals();
    }

    const getItemKey = (item) => item.qc_item_id
        ? ('qc_' + item.qc_item_id)
        : (item.variant_id ? ('var_' + item.variant_id) : 'qc_0');

    initialItems.forEach(item => {
        const key = getItemKey(item);
        cart.set(key, {
            ...item,
            variant_id: Number(item.variant_id || 0),
            qc_item_id: Number(item.qc_item_id || 0),
            available_tags: Array.isArray(item.available_tags) ? item.available_tags : [...item.tags],
            is_complementary: Number(item.is_complementary || 0),
            tags: Array.isArray(item.tags) ? item.tags : [],
            unit_price: Number(item.unit_price),
            mrp: Number(item.mrp),
            gst_percentage: Number(item.gst_percentage),
            gst_type: item.gst_type || (Number(item.gst_percentage) > 0 ? 'BOTH' : 'EXEMPTED'),
            tax_input_type: item.tax_input_type || 'PERCENT',
            sgst_rate: Number(item.sgst_rate ?? Number(item.gst_percentage || 0) / 2),
            cgst_rate: Number(item.cgst_rate ?? Number(item.gst_percentage || 0) / 2),
            igst_rate: Number(item.igst_rate || 0)
        });
    });

    const assignedTags = () => new Set([...cart.values()].flatMap(item => (item.tags || []).map(tag => tag.toUpperCase())));

    function selectableTags(item) {
        const assignedElsewhere = new Set(
            [...cart.values()]
                .filter(cartItem => cartItem !== item)
                .flatMap(cartItem => (cartItem.tags || []).map(tag => tag.toUpperCase()))
        );
        const tags = [...new Set([...(item.tags || []), ...(item.available_tags || [])])];
        const currentTags = (item.tags || []).filter(tag => !assignedElsewhere.has(tag.toUpperCase()));
        const currentSet = new Set(currentTags.map(tag => tag.toUpperCase()));
        return [
            ...currentTags,
            ...tags.filter(tag => !assignedElsewhere.has(tag.toUpperCase()) && !currentSet.has(tag.toUpperCase()))
        ];
    }

    // Match code to an existing item in the billing cart
    function findCartItemForCode(code) {
        const needle = (code || '').trim().toUpperCase();
        if (!needle) return null;
        for (const [key, item] of cart.entries()) {
            if (item.tags && item.tags.some(t => t.toUpperCase() === needle)) return item;
            if (item.available_tags && item.available_tags.some(t => t.toUpperCase() === needle)) return item;
            if (item.sku && item.sku.toUpperCase() === needle) return item;
            if (item.variant_code && item.variant_code.toUpperCase() === needle) return item;
            if (item.product_name && item.product_name.toUpperCase() === needle) return item;
        }
        return null;
    }

    function renderCart() {
        cartBody.replaceChildren();
        if (!cart.size) {
            const row = cartBody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 9;
            cell.className = 'text-center text-muted py-5';
            cell.textContent = 'Scan or search for items to add to this bill.';
            updateTotals();
            return;
        }

        let index = 0;
        [...cart.values()].forEach(item => {
            const itemKey = getItemKey(item);
            const allAvailable = selectableTags(item);
            const maxStock = Math.max(item.tags.length, allAvailable.length);
            const currentQty = item.tags.length;

            const row = cartBody.insertRow();

            // 1. Product & QR codes cell
            const productCell = row.insertCell();
            const name = document.createElement('div');
            name.className = 'fw-semibold d-flex align-items-center flex-wrap gap-1';
            const nameText = document.createElement('span');
            nameText.textContent = item.product_name;
            name.append(nameText);
            if (Number(item.is_complementary) === 1) {
                const compBadge = document.createElement('span');
                compBadge.className = 'badge bg-info-subtle text-info border';
                compBadge.innerHTML = '<i class="bi bi-gift-fill me-1"></i>COMPLEMENTARY';
                name.append(compBadge);
            }
            productCell.append(name);

            const meta = document.createElement('div');
            meta.className = 'small text-muted';
            meta.textContent = [item.sku, item.size_name, item.color_name, `${currentQty} piece(s)`].filter(Boolean).join(' · ');
            productCell.append(meta);

            const hsnInput = document.createElement('input');
            hsnInput.type = 'text';
            hsnInput.inputMode = 'numeric';
            hsnInput.maxLength = 8;
            hsnInput.pattern = '(?:[0-9]{4}|[0-9]{6}|[0-9]{8})';
            hsnInput.required = true;
            hsnInput.className = 'form-control form-control-sm mt-2';
            hsnInput.placeholder = 'HSN (4, 6 or 8 digits)';
            hsnInput.setAttribute('aria-label', `HSN code for ${item.product_name}`);
            hsnInput.name = `items[${index}][hsn_code]`;
            hsnInput.value = item.hsn_code || '6109';
            hsnInput.addEventListener('input', () => { item.hsn_code = hsnInput.value.trim(); });
            productCell.append(hsnInput);

            // Tags badges
            const tagsWrap = document.createElement('div');
            tagsWrap.className = 'd-flex flex-wrap gap-1 mt-1';
            item.tags.forEach(tag => {
                const badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'badge border-0 bg-light text-dark font-monospace shadow-xs';
                badge.title = 'Remove QR piece from bill';
                badge.textContent = `${tag} ×`;
                badge.addEventListener('click', () => {
                    item.tags = item.tags.filter(existing => existing !== tag);
                    if (!item.tags.length) {
                        cart.delete(itemKey);
                    }
                    renderCart();
                });
                tagsWrap.append(badge);
            });
            productCell.append(tagsWrap);

            // 2. Quantity cell (Interactive Qty with -, +, input & stock validation)
            const quantityCell = row.insertCell();
            quantityCell.className = 'text-center';

            const qtyGroup = document.createElement('div');
            qtyGroup.className = 'input-group input-group-sm mx-auto shadow-xs';
            qtyGroup.style.maxWidth = '115px';

            const minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.className = 'btn btn-outline-secondary px-2';
            minusBtn.innerHTML = '<i class="bi bi-dash"></i>';
            minusBtn.title = 'Decrease quantity';
            minusBtn.addEventListener('click', () => {
                if (item.tags.length > 1) {
                    item.tags.pop();
                    renderCart();
                } else {
                    showSuccessToast('Minimum quantity is 1. To remove this product, click the trash button.');
                }
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';
            quantityInput.min = '1';
            quantityInput.step = '1';
            quantityInput.max = String(maxStock);
            quantityInput.value = String(currentQty);
            quantityInput.className = 'form-control form-control-sm text-center fw-bold px-1';
            quantityInput.dataset.quantityKey = itemKey;
            quantityInput.setAttribute('aria-label', `Quantity for ${item.product_name}`);

            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'btn btn-outline-secondary px-2';
            plusBtn.innerHTML = '<i class="bi bi-plus"></i>';
            plusBtn.title = 'Increase quantity';
            plusBtn.addEventListener('click', () => {
                if (item.tags.length < maxStock) {
                    const assignedUpper = item.tags.map(t => t.toUpperCase());
                    const nextTag = allAvailable.find(t => !assignedUpper.includes(t.toUpperCase()));
                    if (nextTag) {
                        item.tags.push(nextTag);
                        renderCart();
                        showSuccessToast(`Quantity increased to ${item.tags.length} for ${item.product_name}.`);
                    }
                } else {
                    showInventoryLimit(
                        'Available stock limit reached',
                        `All ${maxStock} available piece(s) in stock for ${item.product_name} are already added to this bill.`,
                        quantityInput
                    );
                }
            });

            // Quantity input change validation
            quantityInput.addEventListener('change', () => {
                const requestedQuantity = parseInt(quantityInput.value, 10);
                if (isNaN(requestedQuantity) || requestedQuantity < 1) {
                    quantityInput.value = '1';
                    item.tags = allAvailable.slice(0, 1);
                    showInventoryLimit('Invalid quantity', 'Enter a whole-number quantity of at least 1.', quantityInput);
                    renderCart();
                    return;
                }
                if (requestedQuantity > maxStock) {
                    quantityInput.value = String(maxStock);
                    item.tags = allAvailable.slice(0, maxStock);
                    showInventoryLimit(
                        'Available stock limit reached',
                        `Only ${maxStock} piece(s) are available in stock for ${item.product_name}. Cannot enter ${requestedQuantity}.`,
                        quantityInput
                    );
                    renderCart();
                    return;
                }
                item.tags = allAvailable.slice(0, requestedQuantity);
                renderCart();
            });

            qtyGroup.append(minusBtn, quantityInput, plusBtn);
            quantityCell.append(qtyGroup);

            const stockInfo = document.createElement('div');
            stockInfo.className = 'text-muted small mt-1';
            stockInfo.style.fontSize = '0.72rem';
            stockInfo.textContent = `Stock: ${maxStock}`;
            quantityCell.append(stockInfo);

            // 3. MRP cell
            const mrpCell = row.insertCell();
            mrpCell.className = 'text-end text-nowrap';
            mrpCell.textContent = money(item.mrp);

            // 4. Discount cell
            const discountCell = row.insertCell();
            const discountInput = document.createElement('input');
            discountInput.type = 'number';
            discountInput.min = '0';
            discountInput.max = '100';
            discountInput.step = '0.01';
            discountInput.className = 'form-control form-control-sm';
            if (Number(item.is_complementary) === 1) {
                discountInput.value = '100.00';
                discountInput.readOnly = true;
                discountInput.classList.add('bg-light', 'text-muted');
            } else {
                discountInput.value = item.mrp > 0 ? Math.max(0, (1 - item.unit_price / item.mrp) * 100).toFixed(2) : '0';
                discountInput.addEventListener('input', () => {
                    item.unit_price = Math.round(item.mrp * (1 - Math.min(100, Math.max(0, Number(discountInput.value) || 0)) / 100) * 100) / 100;
                    priceInput.value = item.unit_price.toFixed(2);
                    updateTotals();
                });
            }
            discountCell.append(discountInput);

            // 5. Price / piece cell
            const priceCell = row.insertCell();
            const priceInput = document.createElement('input');
            priceInput.type = 'number';
            priceInput.min = '0';
            priceInput.step = '0.01';
            priceInput.className = 'form-control form-control-sm';
            priceInput.name = `items[${index}][unit_price]`;
            if (Number(item.is_complementary) === 1) {
                priceInput.value = '0.00';
                priceInput.readOnly = true;
                priceInput.classList.add('bg-light', 'text-muted', 'fw-bold');
            } else {
                priceInput.value = Number(item.unit_price).toFixed(2);
                priceInput.addEventListener('input', () => {
                    item.unit_price = Math.max(0, Number(priceInput.value) || 0);
                    discountInput.value = item.mrp > 0 ? Math.max(0, (1 - item.unit_price / item.mrp) * 100).toFixed(2) : '0';
                    updateTotals();
                });
            }
            priceCell.append(priceInput);

            // 6. GST / Tax details controls
            const gstCell = row.insertCell();
            const gstControls = document.createElement('div');
            gstControls.className = 'd-flex flex-column gap-1';
            const gstTopRow = document.createElement('div');
            gstTopRow.className = 'd-flex gap-1';
            const gstTypeSelect = document.createElement('select');
            gstTypeSelect.className = 'form-select form-select-sm';
            gstTypeSelect.name = `items[${index}][gst_type]`;
            gstTypeSelect.setAttribute('aria-label', 'GST type');
            [
                ['SGST', 'SGST'],
                ['IGST', 'IGST'],
                ['CGST', 'CGST'],
                ['BOTH', 'Both (CGST and SGST)'],
                ['EXEMPTED', 'Exempted']
            ].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                gstTypeSelect.append(option);
            });
            gstTypeSelect.value = item.gst_type || 'BOTH';

            const taxInputTypeSelect = document.createElement('select');
            taxInputTypeSelect.className = 'form-select form-select-sm';
            taxInputTypeSelect.name = `items[${index}][tax_input_type]`;
            taxInputTypeSelect.setAttribute('aria-label', 'Tax input type');
            [['PERCENT', 'Percentage (%)'], ['FIXED', 'Fixed amount / piece']].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                taxInputTypeSelect.append(option);
            });
            taxInputTypeSelect.value = item.tax_input_type || 'PERCENT';
            gstTopRow.append(gstTypeSelect, taxInputTypeSelect);
            gstControls.append(gstTopRow);

            const ratesRow = document.createElement('div');
            ratesRow.className = 'd-flex gap-1';
            const rateInputs = {};
            [['sgst_rate', 'SGST', 'SGST'], ['igst_rate', 'IGST', 'IGST'], ['cgst_rate', 'CGST', 'CGST']].forEach(([key, label, type]) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex-fill';
                wrapper.dataset.gstRateType = type;
                const rateInput = document.createElement('input');
                rateInput.type = 'number';
                rateInput.min = '0';
                rateInput.step = '0.01';
                rateInput.className = 'form-control form-control-sm';
                rateInput.placeholder = label;
                rateInput.name = `items[${index}][${key}]`;
                rateInput.value = Number(item[key] || 0);
                rateInput.addEventListener('input', () => {
                    item[key] = Math.max(0, Number(rateInput.value) || 0);
                    updateTotals();
                });
                wrapper.append(rateInput);
                rateInputs[key] = {wrapper, input: rateInput};
                ratesRow.append(wrapper);
            });
            gstControls.append(ratesRow);
            const updateGstControls = () => {
                const type = gstTypeSelect.value;
                const visibleTypes = type === 'BOTH' ? ['SGST', 'CGST'] : (type === 'EXEMPTED' ? [] : [type]);
                Object.entries(rateInputs).forEach(([key, entry]) => {
                    entry.wrapper.classList.toggle('d-none', !visibleTypes.includes(entry.wrapper.dataset.gstRateType));
                    const unit = taxInputTypeSelect.value === 'PERCENT' ? '(%)' : '(₹ / piece)';
                    entry.input.placeholder = `${entry.wrapper.dataset.gstRateType} ${unit}`;
                    entry.input.setAttribute('aria-label', `${entry.wrapper.dataset.gstRateType} rate ${unit}`);
                    entry.input.max = taxInputTypeSelect.value === 'PERCENT' ? '100' : '';
                });
            };
            gstTypeSelect.addEventListener('change', () => {
                item.gst_type = gstTypeSelect.value;
                updateGstControls();
                updateTotals();
            });
            taxInputTypeSelect.addEventListener('change', () => {
                item.tax_input_type = taxInputTypeSelect.value;
                updateGstControls();
                updateTotals();
            });
            updateGstControls();
            gstCell.append(gstControls);

            // 7. Tax (Incl.) cell
            const taxCell = row.insertCell();
            taxCell.className = 'text-end text-nowrap';
            taxCell.dataset.taxFor = itemKey;

            // 8. Total cell
            const lineTotalCell = row.insertCell();
            lineTotalCell.className = 'text-end text-nowrap fw-semibold';
            lineTotalCell.dataset.totalFor = itemKey;

            // 9. Remove button cell
            const removeCell = row.insertCell();
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger';
            removeButton.innerHTML = '<i class="bi bi-trash"></i>';
            removeButton.title = 'Remove product';
            removeButton.addEventListener('click', () => {
                cart.delete(itemKey);
                renderCart();
            });
            removeCell.append(removeButton);

            // Hidden inputs for form submit
            [['qc_item_id', item.qc_item_id || 0], ['variant_id', item.variant_id || 0], ['is_complementary', item.is_complementary || 0]].forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = value;
                row.append(input);
            });
            item.tags.forEach(tag => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][tags][]`;
                input.value = tag;
                row.append(input);
            });
            index++;
        });
        updateTotals();
    }

    function updateTotals() {
        let gross = 0;
        let discount = 0;
        let subtotal = 0;
        let taxes = 0;
        let grandTotal = 0;
        [...cart.values()].forEach(item => {
            const quantity = item.tags.length;
            const lineGross = Number(item.mrp || 0) * quantity;
            const netLine = Number(item.unit_price || 0) * quantity;
            const inputType = item.tax_input_type || 'PERCENT';
            const gstType = item.gst_type || 'BOTH';
            const rates = {
                SGST: Number(item.sgst_rate || 0),
                CGST: Number(item.cgst_rate || 0),
                IGST: Number(item.igst_rate || 0)
            };
            const includedTypes = gstType === 'BOTH' ? ['SGST', 'CGST'] : (gstType === 'EXEMPTED' ? [] : [gstType]);

            let totalRatePct = 0;
            includedTypes.forEach(type => {
                totalRatePct += rates[type] || 0;
            });

            // GST is INCLUSIVE inside selling price
            let taxable = netLine;
            let lineTax = 0;
            if (inputType === 'PERCENT' && totalRatePct > 0) {
                taxable = Math.round((netLine / (1 + (totalRatePct / 100)) + Number.EPSILON) * 100) / 100;
                lineTax = Math.round((netLine - taxable + Number.EPSILON) * 100) / 100;
            } else if (inputType !== 'PERCENT') {
                const perPieceTax = includedTypes.reduce((sum, type) => sum + (rates[type] || 0), 0);
                lineTax = Math.round((perPieceTax * quantity + Number.EPSILON) * 100) / 100;
                taxable = Math.max(0, netLine - lineTax);
            }

            const lineTotal = netLine;
            gross += lineGross;
            discount += Math.max(0, lineGross - netLine);
            subtotal += taxable;
            taxes += lineTax;
            grandTotal += lineTotal;
            const itemKey = getItemKey(item);
            const lineTotalCell = cartBody.querySelector(`[data-total-for="${itemKey}"]`);
            const taxCell = cartBody.querySelector(`[data-tax-for="${itemKey}"]`);
            if (lineTotalCell) lineTotalCell.textContent = money(lineTotal);
            if (taxCell) taxCell.textContent = money(lineTax);
        });
        document.getElementById('grossTotal').textContent = money(gross);
        document.getElementById('discountTotal').textContent = money(discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('taxTotal').textContent = money(taxes);
        document.getElementById('grandTotal').textContent = money(grandTotal);
        const cash = Number(form.elements.cash_amount?.value || 0);
        const upi = Number(form.elements.upi_amount?.value || 0);
        document.getElementById('splitBalance').textContent = document.getElementById('paymentMethod').value === 'CASH_UPI'
            ? `Cash + UPI: ${money(cash + upi)} / ${money(grandTotal)}`
            : '';
    }

    function addResult(item, selectedTag) {
        const assigned = assignedTags();
        const availableTags = Array.isArray(item.available_tags) ? item.available_tags : [];
        const key = getItemKey(item);

        if (cart.has(key)) {
            // Already in cart: increase quantity
            const cartItem = cart.get(key);
            cartItem.available_tags = [...new Set([...(cartItem.available_tags || []), ...availableTags])];
            const allAvailable = selectableTags(cartItem);
            const maxStock = allAvailable.length;
            const currentUpper = cartItem.tags.map(t => t.toUpperCase());
            const unassigned = allAvailable.filter(t => !currentUpper.includes(t.toUpperCase()));

            if (!unassigned.length) {
                const rowInput = cartBody.querySelector(`input[data-quantity-key="${key}"]`);
                showInventoryLimit(
                    'Available stock limit reached',
                    `All ${maxStock} available piece(s) in stock for ${cartItem.product_name} are already added to this bill.`,
                    rowInput
                );
                return;
            }

            const tagToAdd = (selectedTag && !currentUpper.includes(selectedTag.toUpperCase()))
                ? selectedTag
                : unassigned[0];

            cartItem.tags.push(tagToAdd);
            showSuccessToast(`Quantity increased to ${cartItem.tags.length} for ${cartItem.product_name}.`);
        } else {
            // New item to cart
            const selectedTagIsAvailable = selectedTag && !assigned.has(selectedTag.toUpperCase());
            const tag = selectedTagIsAvailable
                ? selectedTag
                : availableTags.find(code => !assigned.has(code.toUpperCase()));

            if (!tag || assigned.has(tag.toUpperCase())) {
                showInventoryLimit('Available stock limit reached', `No unbilled piece is available for ${item.product_name}.`);
                return;
            }

            const isComp = Number(item.is_complementary || 0) === 1;
            cart.set(key, {
                qc_item_id: Number(item.qc_item_id || 0),
                variant_id: Number(item.variant_id || 0),
                available_tags: [...availableTags],
                is_complementary: isComp ? 1 : 0,
                product_name: item.product_name,
                sku: item.sku || '',
                variant_code: item.variant_code || '',
                hsn_code: item.hsn_code || '6109',
                size_name: item.size_name || '',
                color_name: item.color_name || '',
                mrp: Number(item.mrp || 0),
                unit_price: isComp ? 0 : Number(item.unit_price || item.mrp || 0),
                gst_percentage: Number(item.gst_percentage || 0),
                gst_type: Number(item.gst_percentage || 0) > 0 ? 'BOTH' : 'EXEMPTED',
                tax_input_type: 'PERCENT',
                sgst_rate: Number(item.gst_percentage || 0) / 2,
                cgst_rate: Number(item.gst_percentage || 0) / 2,
                igst_rate: 0,
                tags: [tag]
            });
            showSuccessToast(`Added ${item.product_name} (Qty: 1)`);
        }

        search.value = '';
        resultBox.replaceChildren();
        renderCart();
        search.focus();
    }

    async function lookup(showStockLimitAlert = false) {
        const query = search.value.trim();
        resultBox.replaceChildren();
        if (!query) return;
        try {
            const params = new URLSearchParams({
                q: query,
                production_unit_id: unitSelect.value
            });
            if (billId) params.set('bill_id', String(billId));
            const response = await fetch('<?= url('api/production/billing/search') ?>?' + params.toString(), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Inventory search failed.');

            // Check damaged
            if (payload.data && payload.data.is_damaged) {
                const alertEl = document.createElement('div');
                alertEl.className = 'list-group-item list-group-item-danger border-danger fw-bold small p-3 d-flex align-items-center gap-2';
                alertEl.innerHTML = `<i class="bi bi-shield-x text-danger fs-4"></i><div><span class="d-block text-danger">${payload.data.message}</span><small class="text-dark fw-normal">Damage Record: <strong>${payload.data.damage_number || 'N/A'}</strong> · Reason: ${payload.data.damage_reason || 'Quarantined'}</small></div>`;
                resultBox.append(alertEl);
                document.getElementById('cartMessage').innerHTML = `<div class="alert alert-danger py-1.5 px-3 fw-bold small mb-0"><i class="bi bi-exclamation-octagon-fill me-1"></i> ${payload.data.message}</div>`;
                return;
            }

            // Check outwarded
            if (payload.data && payload.data.is_outwarded) {
                const alertEl = document.createElement('div');
                alertEl.className = 'list-group-item list-group-item-warning border-warning fw-bold small p-3 d-flex align-items-center gap-2';
                alertEl.innerHTML = `<i class="bi bi-truck text-warning fs-4"></i><div><span class="d-block text-warning-emphasis">${payload.data.message}</span><small class="text-dark fw-normal">Outward Dispatch: <strong>${payload.data.outward_number || 'N/A'}</strong> · Destination: ${payload.data.warehouse_name || 'Warehouse'}</small></div>`;
                resultBox.append(alertEl);
                document.getElementById('cartMessage').innerHTML = `<div class="alert alert-warning py-1.5 px-3 fw-bold small mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${payload.data.message}</div>`;
                return;
            }

            if (payload.data && payload.data.is_fabric_roll) {
                const alertEl = document.createElement('div');
                alertEl.className = 'list-group-item list-group-item-warning border-warning small';
                alertEl.textContent = payload.data.message;
                resultBox.append(alertEl);
                document.getElementById('cartMessage').textContent = payload.data.message;
                return;
            }

            if (payload.data && payload.data.stock_limit_reached) {
                document.getElementById('cartMessage').textContent = payload.data.message;
                if (showStockLimitAlert) {
                    showInventoryLimit('Available stock limit reached', payload.data.message);
                }
                return;
            }

            const assigned = assignedTags();
            let resultCount = 0;
            let stockAlreadyAssigned = false;
            payload.data.items.forEach(item => {
                const itemTags = Array.isArray(item.available_tags) ? item.available_tags : [];
                const available = itemTags.filter(tag => !assigned.has(tag.toUpperCase()));
                if (!available.length) {
                    if (itemTags.length) stockAlreadyAssigned = true;
                    return;
                }
                const exactTag = available.find(tag => tag.toUpperCase() === query.toUpperCase());
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                const details = document.createElement('span');
                const title = document.createElement('strong');
                title.textContent = item.product_name;
                const subtitle = document.createElement('small');
                subtitle.className = 'd-block text-muted';
                subtitle.textContent = [item.sku, item.size_name, item.color_name, `${available.length} available`].filter(Boolean).join(' · ');
                details.append(title, subtitle);
                const code = document.createElement('span');
                code.className = 'badge text-bg-light font-monospace';
                code.textContent = exactTag || available[0];
                button.append(details, code);
                button.addEventListener('click', () => addResult(item, exactTag || available[0]));
                resultBox.append(button);
                resultCount++;
            });
            if (!resultCount) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-muted small';
                empty.textContent = stockAlreadyAssigned
                    ? 'All available pieces for this item are already in the bill.'
                    : 'No available matching Production Inventory items.';
                resultBox.append(empty);
                if (showStockLimitAlert && stockAlreadyAssigned) {
                    showInventoryLimit('Available stock limit reached', 'All available pieces for this item are already in this bill.');
                }
            }
        } catch (error) {
            const message = document.createElement('div');
            message.className = 'list-group-item text-danger small';
            message.textContent = error.message || 'Unable to search Production Inventory.';
            resultBox.append(message);
        }
    }

    // Handles scanning or manual adding: increases Qty if same QR, alerts if stock limit reached
    async function handleScanOrAdd(rawCode) {
        clearTimeout(searchTimer);
        const code = (rawCode || '').trim();
        if (!code) return;

        // 1. Check if item already exists in cart: increment Qty
        const existingItem = findCartItemForCode(code);
        if (existingItem) {
            const available = selectableTags(existingItem);
            const maxStock = available.length;
            const currentQty = existingItem.tags.length;
            const rowInput = cartBody.querySelector(`input[data-quantity-key="${getItemKey(existingItem)}"]`);

            if (currentQty >= maxStock) {
                showInventoryLimit(
                    'Available stock limit reached',
                    `All ${maxStock} available piece(s) in stock for ${existingItem.product_name} are already added to this bill.`,
                    rowInput
                );
                search.value = '';
                search.focus();
                return;
            }

            const assignedUpper = existingItem.tags.map(t => t.toUpperCase());
            const unassigned = available.filter(t => !assignedUpper.includes(t.toUpperCase()));
            if (!unassigned.length) {
                showInventoryLimit(
                    'Available stock limit reached',
                    `All ${maxStock} available piece(s) in stock for ${existingItem.product_name} are already added to this bill.`,
                    rowInput
                );
                search.value = '';
                search.focus();
                return;
            }

            const nextTag = unassigned[0];
            existingItem.tags.push(nextTag);
            showSuccessToast(`Quantity increased to ${existingItem.tags.length} for ${existingItem.product_name}.`);
            search.value = '';
            resultBox.replaceChildren();
            renderCart();
            search.focus();
            return;
        }

        // 2. Item not yet in cart: lookup inventory and add
        await lookup(true);
        const availableButtons = [...resultBox.querySelectorAll('button')];
        if (!availableButtons.length) return;
        const scannedUpper = code.toUpperCase();
        const exactMatch = availableButtons.find(button => {
            const badge = button.querySelector('.badge');
            return badge && badge.textContent.trim().toUpperCase() === scannedUpper;
        });
        (exactMatch || availableButtons[0]).click();
    }

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(lookup, 250);
    });
    document.getElementById('scanAddButton').addEventListener('click', () => handleScanOrAdd(search.value));
    search.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            handleScanOrAdd(search.value);
        }
    });

    const cameraModalElement = document.getElementById('cameraScanModal');
    const cameraModal = bootstrap.Modal.getOrCreateInstance(cameraModalElement);
    const cameraMessage = document.getElementById('cameraScanMessage');
    async function stopCameraScanner() {
        if (!cameraScanner) return;
        try {
            if (cameraScanner.isScanning) await cameraScanner.stop();
            await cameraScanner.clear();
        } catch (error) {
            cameraMessage.textContent = 'Camera stopped.';
        }
        cameraScanner = null;
        cameraScanBusy = false;
    }
    document.getElementById('openCameraScanner').addEventListener('click', () => cameraModal.show());
    cameraModalElement.addEventListener('shown.bs.modal', async () => {
        cameraMessage.textContent = 'Starting camera…';
        if (typeof Html5Qrcode === 'undefined') {
            cameraMessage.textContent = 'Camera scanner is unavailable in this browser.';
            return;
        }
        try {
            cameraScanner = new Html5Qrcode('productionBillQrReader');
            await cameraScanner.start(
                {facingMode: 'environment'},
                {fps: 10, qrbox: {width: 250, height: 250}},
                async decodedText => {
                    if (cameraScanBusy) return;
                    cameraScanBusy = true;
                    cameraMessage.textContent = 'QR detected. Processing…';
                    try {
                        await handleScanOrAdd(decodedText.trim());
                        cameraMessage.textContent = 'QR processed successfully.';
                        cameraModal.hide();
                    } catch (err) {
                        cameraMessage.textContent = err.message || 'QR processing failed.';
                    } finally {
                        cameraScanBusy = false;
                    }
                },
                () => {}
            );
            cameraMessage.textContent = 'Point the camera at a production inventory QR label.';
        } catch (error) {
            cameraMessage.textContent = error.message || 'Unable to start camera. Check camera permission and use HTTPS.';
            cameraScanner = null;
        }
    });
    cameraModalElement.addEventListener('hidden.bs.modal', stopCameraScanner);

    unitSelect.addEventListener('change', updateProductionUnitDetails);
    document.getElementById('paymentMethod').addEventListener('change', updatePaymentFields);
    buyerType.addEventListener('change', updateBuyerFields);

    form.elements.company_phone.pattern = '\\+?[0-9][0-9 ()-]{6,19}';
    form.elements.customer_phone.pattern = '\\+?[0-9][0-9 ()-]{6,19}';
    form.elements.company_gstin.pattern = '[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]';
    form.elements.customer_gstin.pattern = '[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]';
    form.elements.company_gstin.maxLength = 15;
    form.elements.customer_gstin.maxLength = 15;
    form.elements.bill_date.required = true;
    document.getElementById('paymentMethod').required = true;

    updatePaymentFields();
    ['cash_amount', 'upi_amount'].forEach(name => form.elements[name]?.addEventListener('input', updateTotals));

    form.addEventListener('submit', event => {
        if (!cart.size || [...cart.values()].some(item => !item.tags.length)) {
            event.preventDefault();
            document.getElementById('cartMessage').textContent = 'Scan at least one available QR item before saving.';
            return;
        }

        const cartItems = [...cart.values()];
        const paidItems = cartItems.filter(it => (Number(it.is_complementary) !== 1) && (Number(it.unit_price) > 0));
        const compItems = cartItems.filter(it => Number(it.is_complementary) === 1);

        if (compItems.length > 0 && paidItems.length === 0) {
            event.preventDefault();
            document.getElementById('cartMessage').innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> A complementary product cannot be billed alone. At least one paid product must be added to the bill to proceed.</span>';
            return;
        }
        if ([...cart.values()].some(item => item.unit_price < 0 || item.gst_percentage < 0 || item.gst_percentage > 100)) {
            event.preventDefault();
            document.getElementById('cartMessage').textContent = 'Check the item price and GST values.';
        }
    });

    updateBuyerFields();
    updateProductionUnitDetails();
    renderCart();
});
</script>
