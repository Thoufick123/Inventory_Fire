<?php
// Production Unit Record Return & Exchange Form
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-return-left text-danger me-2"></i>Customer Return &amp; Exchange Module
            </h4>
            <span class="text-muted small">Choose your return flow, scan any piece tag or product barcode, and stage items for inspection.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/returns') ?>" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Returns Log
            </a>
        </div>
    </div>

    <!-- FLOW SELECTION: ONLY TWO FLOWS -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-diagram-2 text-primary me-2"></i>Select Return &amp; Exchange Flow
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Flow 1: Fully Refunded -->
                <div class="col-md-6">
                    <label class="flow-option-card d-block p-3 border rounded-3 position-relative cursor-pointer transition-all active" id="cardFlowRefund" onclick="selectFlow('REFUND')">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flow-icon-circle rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width: 52px; height: 52px;">
                                <i class="bi bi-cash-coin fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0">Flow 1: Fully Refunded</h6>
                                    <span class="badge bg-danger text-white">Full Refund / Credit Note</span>
                                </div>
                                <p class="text-muted small mb-0">Customer returns purchased goods for money back, cash/UPI reimbursement, or a GST credit note voucher.</p>
                            </div>
                            <div>
                                <input type="radio" name="flow_radio" id="radioFlowRefund" value="REFUND" checked class="form-check-input fs-5">
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Flow 2: Return Product Options -->
                <div class="col-md-6">
                    <label class="flow-option-card d-block p-3 border rounded-3 position-relative cursor-pointer transition-all" id="cardFlowExchange" onclick="selectFlow('EXCHANGE')">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flow-icon-circle rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 52px; height: 52px;">
                                <i class="bi bi-arrow-left-right fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0">Flow 2: Return Product Options</h6>
                                    <span class="badge bg-primary text-white">Exchange / Replacement</span>
                                </div>
                                <p class="text-muted small mb-0">Customer returns garment for size replacement, alternate colorway, or product exchange.</p>
                            </div>
                            <div>
                                <input type="radio" name="flow_radio" id="radioFlowExchange" value="EXCHANGE" class="form-check-input fs-5">
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- UNIVERSAL LIVE SCANNER & LOOKUP -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-upc-scan text-primary me-2"></i>Scan Any Product / Tag or Search Bill
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-9 col-md-8">
                    <label class="form-label fw-bold text-dark mb-1">
                        Scan Any Product QR / Piece Tracking Tag / Barcode / SKU / Bill Number
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light text-primary border-primary-subtle">
                            <i class="bi bi-qr-code-scan"></i>
                        </span>
                        <input type="text" id="universalScanInput" class="form-control form-control-lg fw-bold font-monospace border-primary-subtle" 
                               placeholder="Scan piece tag (e.g. PRD2611-0001), variant barcode, SKU, or Bill #..." 
                               autocomplete="off" autofocus>
                        <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnScanSearch" onclick="handleUniversalScan()">
                            <i class="bi bi-search me-1"></i> Scan / Search
                        </button>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div id="lookupStatus" class="small text-muted py-2">
                        <i class="bi bi-info-circle me-1"></i> Hardware scanner ready. Press Enter or click button after scan.
                    </div>
                </div>
            </div>

            <!-- Bill Info Banner (Hidden until loaded) -->
            <div id="billInfoBanner" class="alert alert-light border border-2 border-primary-subtle mt-3 mb-0 rounded-3 shadow-sm d-none">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary px-2 py-1 fs-6 font-monospace" id="bInfoNumber"></span>
                            <span class="badge bg-secondary-subtle text-secondary border" id="bInfoDate"></span>
                            <span class="badge bg-info-subtle text-info border" id="bInfoUnit"></span>
                        </div>
                        <div class="text-muted small">
                            Customer / Company: <strong class="text-dark" id="bInfoCustomer"></strong> | Phone: <span class="fw-semibold" id="bInfoPhone"></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Original Invoice Value</span>
                        <strong class="text-primary fs-4" id="bInfoTotal"></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN RETURN FORM (Hidden until bill loaded) -->
    <form method="POST" action="<?= url('production/returns/store') ?>" id="returnForm" class="d-none">
        <?= CSRF::field() ?>
        <input type="hidden" name="bill_id" id="hiddenBillId" value="">
        <input type="hidden" name="return_type" id="hiddenReturnType" value="REFUND">

        <!-- STEP: SELECT GARMENTS FOR RETURN -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-check2-square text-danger me-2"></i>Select Scanned Pieces / Items to Return
                    </h6>
                    <span class="text-muted small">Garment pieces scanned will be highlighted and checked automatically</span>
                </div>
                <div>
                    <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm" id="returnSelectedBadge">0 pieces selected (₹0.00)</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="fullyReturnedNotice" class="d-none m-3 alert alert-warning border-warning d-flex align-items-center mb-0">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                    <div>
                        <strong>All items on this invoice have already been returned!</strong>
                        <div class="small">No remaining returnable pieces found on this bill.</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="returnItemsTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 50px;" class="text-center">Select</th>
                                <th>Product Style / Details</th>
                                <th>HSN</th>
                                <th class="text-center">Scanned Piece Tag</th>
                                <th class="text-end">Unit Price</th>
                                <th>Return Reason / Fault</th>
                            </tr>
                        </thead>
                        <tbody id="returnItemsTbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STAGING & SETTLEMENT SETTINGS -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-sliders text-primary me-2"></i>Settlement &amp; Staging Details
                </h6>
            </div>
            <div class="card-body p-4">
                <!-- IMPORTANT NOTICE REGARDING TRIAGE FLOW -->
                <div class="alert alert-info border-info d-flex align-items-center mb-4">
                    <i class="bi bi-shield-check fs-3 me-3 text-info"></i>
                    <div>
                        <strong class="text-dark">Staging &amp; Quality Inspection Policy:</strong>
                        <div class="small text-muted">
                            Returned pieces will <strong>not</strong> be credited directly into available stock. Once submitted, they are queued into the <strong>Return Staging Queue</strong>. The warehouse manager will inspect each piece and choose whether to <strong>Add to Stock</strong> (resellable) or move to the <strong>Damage Module</strong>.
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Flow 1 Fields: Fully Refunded -->
                    <div class="col-md-6" id="fieldRefundModeContainer">
                        <label class="form-label fw-bold text-dark mb-1">Refund / Settlement Mode *</label>
                        <select name="refund_mode" class="form-select fw-semibold" id="selectRefundMode" required>
                            <option value="STORE_CREDIT">GST Credit Note (Store Credit for next bill)</option>
                            <option value="CASH">Cash Refund</option>
                            <option value="UPI">UPI / Digital Payment</option>
                            <option value="BANK_TRANSFER">Direct Bank Transfer / NEFT</option>
                        </select>
                        <small class="text-muted">Specifies how the monetary refund or ledger balance is credited.</small>
                    </div>

                    <!-- Flow 2 Fields: Return Product Options (Exchange) -->
                    <div class="col-md-6 d-none" id="fieldExchangeNotesContainer">
                        <label class="form-label fw-bold text-dark mb-1">Exchange Specification / Requested Replacement *</label>
                        <input type="text" name="exchange_notes" class="form-control" id="inputExchangeNotes" 
                               placeholder="e.g. Size M swap requested, or color Navy Blue replacement...">
                        <small class="text-muted">Specify the replacement item or size wanted by the customer.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark mb-1">Supervisor / Management Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Approved by Production Floor Supervisor / Store Manager">
                        <small class="text-muted">Optional notes saved on the return record.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <a href="<?= url('production/returns') ?>" class="btn btn-outline-secondary px-3">Cancel</a>
                <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold shadow-sm" id="btnSubmitReturn">
                    <i class="bi bi-box-arrow-in-down-left me-1"></i> Record Return &amp; Send to Staging
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.flow-option-card {
    border: 2px solid #e9ecef !important;
    background-color: #fff;
    transition: all 0.2s ease-in-out;
}
.flow-option-card:hover {
    border-color: #0d6efd !important;
    background-color: #f8faff;
}
.flow-option-card.active {
    border-color: #dc3545 !important;
    background-color: #fff8f8;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.12);
}
.flow-option-card.active.exchange-active {
    border-color: #0d6efd !important;
    background-color: #f0f7ff;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12);
}
.highlight-row {
    background-color: #e8f5e9 !important;
    transition: background-color 0.5s ease;
}
</style>

<script>
let currentLoadedBill = null;
let currentFlow = 'REFUND';

function selectFlow(flow) {
    currentFlow = flow;
    document.getElementById('hiddenReturnType').value = flow;

    const cardRefund = document.getElementById('cardFlowRefund');
    const cardExchange = document.getElementById('cardFlowExchange');
    const radioRefund = document.getElementById('radioFlowRefund');
    const radioExchange = document.getElementById('radioFlowExchange');
    const refundContainer = document.getElementById('fieldRefundModeContainer');
    const exchangeContainer = document.getElementById('fieldExchangeNotesContainer');
    const btnSubmit = document.getElementById('btnSubmitReturn');

    if (flow === 'EXCHANGE') {
        cardRefund.classList.remove('active');
        cardExchange.classList.add('active', 'exchange-active');
        radioExchange.checked = true;
        refundContainer.classList.add('d-none');
        exchangeContainer.classList.remove('d-none');
        btnSubmit.className = 'btn btn-primary px-4 py-2 fw-semibold shadow-sm';
        btnSubmit.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i> Record Exchange & Send to Staging';
    } else {
        cardExchange.classList.remove('active', 'exchange-active');
        cardRefund.classList.add('active');
        radioRefund.checked = true;
        refundContainer.classList.remove('d-none');
        exchangeContainer.classList.add('d-none');
        btnSubmit.className = 'btn btn-danger px-4 py-2 fw-semibold shadow-sm';
        btnSubmit.innerHTML = '<i class="bi bi-box-arrow-in-down-left me-1"></i> Record Return & Send to Staging';
    }
}

function handleUniversalScan() {
    const q = document.getElementById('universalScanInput').value.trim();
    if (!q) {
        alert('Please scan or enter a piece QR tag, barcode, SKU, or bill number.');
        return;
    }

    // If bill already loaded, first check if tag or SKU matches items currently in table
    if (currentLoadedBill) {
        const upperQ = q.toUpperCase();
        // Check if an individual tag matches
        const tagCheckbox = document.getElementById('chk_tag_' + upperQ) || document.getElementById('chk_tag_' + q);
        if (tagCheckbox) {
            if (tagCheckbox.disabled) {
                showScanAlert('Piece tag ' + q + ' was already returned previously!', 'warning');
            } else {
                tagCheckbox.checked = true;
                const row = tagCheckbox.closest('tr');
                if (row) {
                    row.classList.add('highlight-row');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => row.classList.remove('highlight-row'), 2000);
                }
                calcReturnTotals();
                showScanAlert('Piece tag ' + q + ' scanned and selected!', 'success');
                document.getElementById('universalScanInput').value = '';
                return;
            }
        }
    }

    // Otherwise, perform server lookup
    const statusEl = document.getElementById('lookupStatus');
    statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-1"></span> Searching server...';

    fetch('<?= url("production/returns/lookup") ?>?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                statusEl.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i> ' + (data.message || 'Not found') + '</span>';
                return;
            }

            statusEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Bill loaded successfully!</span>';
            currentLoadedBill = data.bill;

            const b = data.bill;
            document.getElementById('hiddenBillId').value = b.id;
            document.getElementById('bInfoNumber').textContent = b.bill_number;
            document.getElementById('bInfoDate').textContent = b.bill_date;
            document.getElementById('bInfoUnit').textContent = b.production_unit_id ? ('PU-' + b.production_unit_id) : 'Main Plant';
            document.getElementById('bInfoCustomer').textContent = b.customer_name || b.company_name || 'B2B Client';
            document.getElementById('bInfoPhone').textContent = b.customer_phone || b.company_phone || '-';
            document.getElementById('bInfoTotal').textContent = '₹' + parseFloat(b.total_amount).toFixed(2);
            document.getElementById('billInfoBanner').classList.remove('d-none');

            // Render items
            const tbody = document.getElementById('returnItemsTbody');
            tbody.innerHTML = '';

            const fullyReturnedNotice = document.getElementById('fullyReturnedNotice');
            const btnSubmit = document.getElementById('btnSubmitReturn');

            if (data.is_fully_returned) {
                fullyReturnedNotice.classList.remove('d-none');
                btnSubmit.disabled = true;
                btnSubmit.dataset.blocked = "1";
            } else {
                fullyReturnedNotice.classList.add('d-none');
                btnSubmit.disabled = false;
                delete btnSubmit.dataset.blocked;
            }

            let hasItems = false;
            let targetRowToHighlight = null;

            data.items.forEach(item => {
                const tags = item.tags || [];
                if (tags.length > 0) {
                    tags.forEach((tagObj) => {
                        hasItems = true;
                        const tr = document.createElement('tr');
                        const isReturned = !!tagObj.is_returned;
                        const isScannedMatch = (data.scanned_tag && data.scanned_tag.toUpperCase() === tagObj.tag_code.toUpperCase());

                        tr.id = 'row_tag_' + tagObj.tag_code;
                        tr.innerHTML = `
                            <td class="text-center">
                                ${isReturned ? '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-check2"></i> Returned</span>' : `
                                    <input type="checkbox" name="selected_tags[]" value="${tagObj.tag_code}" 
                                           class="form-check-input return-checkbox" id="chk_tag_${tagObj.tag_code}" 
                                           onchange="calcReturnTotals()" data-price="${item.unit_price}" 
                                           ${isScannedMatch ? 'checked' : ''}>
                                    <input type="hidden" name="tags[${tagObj.tag_code}][bill_item_id]" value="${item.id}">
                                    <input type="hidden" name="tags[${tagObj.tag_code}][tag_code]" value="${tagObj.tag_code}">
                                `}
                            </td>
                            <td>
                                <strong class="text-dark">${item.product_name}</strong>
                                <div class="small text-muted">${item.sku || ''} ${item.size_name ? '· Size: ' + item.size_name : ''} ${item.color_name ? '· Color: ' + item.color_name : ''}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace">${item.hsn_code || '6109'}</span></td>
                            <td class="text-center">
                                <span class="font-monospace badge bg-dark text-white px-2 py-1 fs-6">${tagObj.tag_code}</span>
                            </td>
                            <td class="text-end fw-semibold">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>
                                <input type="text" name="tags[${tagObj.tag_code}][reason]" class="form-control form-control-sm" 
                                       placeholder="Reason (e.g. Size fitting, exchange, flaw)" 
                                       value="Customer return" ${isReturned ? 'disabled' : ''}>
                            </td>
                        `;
                        tbody.appendChild(tr);

                        if (isScannedMatch) {
                            targetRowToHighlight = tr;
                        }
                    });
                } else {
                    hasItems = true;
                    const tr = document.createElement('tr');
                    const remQty = parseInt(item.remaining_qty ?? item.quantity);
                    const isFullyReturned = remQty <= 0;
                    const isSkuMatch = (data.scanned_sku && data.scanned_sku.toUpperCase() === (item.sku || '').toUpperCase());

                    tr.id = 'row_item_' + item.id;
                    tr.innerHTML = `
                        <td class="text-center">
                            ${isFullyReturned ? '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-check2"></i> Returned</span>' : `
                                <input type="checkbox" name="selected_items[]" value="${item.id}" 
                                       class="form-check-input return-checkbox" id="chk_item_${item.id}" 
                                       onchange="calcReturnTotals()" data-price="${item.unit_price}"
                                       ${isSkuMatch ? 'checked' : ''}>
                            `}
                        </td>
                        <td>
                            <strong class="text-dark">${item.product_name}</strong>
                            <div class="small text-muted">${item.sku || ''} ${item.size_name ? '· Size: ' + item.size_name : ''} ${item.color_name ? '· Color: ' + item.color_name : ''}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border font-monospace">${item.hsn_code || '6109'}</span></td>
                        <td class="text-center">
                            ${isFullyReturned ? '<span class="text-muted small">0 / ' + item.quantity + ' pcs</span>' : `
                                <div class="input-group input-group-sm" style="width: 110px; margin: 0 auto;">
                                    <input type="number" min="1" max="${remQty}" name="items[${item.id}][qty]" id="qty_item_${item.id}" value="1" class="form-control text-center" onchange="calcReturnTotals()">
                                    <span class="input-group-text small text-muted">/ ${remQty}</span>
                                </div>
                            `}
                        </td>
                        <td class="text-end fw-semibold">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>
                            <input type="text" name="items[${item.id}][reason]" class="form-control form-control-sm" 
                                   placeholder="Reason" value="Customer return" ${isFullyReturned ? 'disabled' : ''}>
                        </td>
                    `;
                    tbody.appendChild(tr);

                    if (isSkuMatch) {
                        targetRowToHighlight = tr;
                    }
                }
            });

            document.getElementById('returnForm').classList.remove('d-none');
            calcReturnTotals();

            if (targetRowToHighlight) {
                targetRowToHighlight.classList.add('highlight-row');
                targetRowToHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => targetRowToHighlight.classList.remove('highlight-row'), 2000);
            }

            document.getElementById('universalScanInput').value = '';
            document.getElementById('universalScanInput').focus();
        })
        .catch(err => {
            statusEl.innerHTML = '<span class="text-danger">Error: ' + err.message + '</span>';
        });
}

function showScanAlert(msg, type) {
    const statusEl = document.getElementById('lookupStatus');
    const colorClass = type === 'success' ? 'text-success' : (type === 'warning' ? 'text-warning' : 'text-danger');
    const icon = type === 'success' ? 'bi-check-circle' : (type === 'warning' ? 'bi-exclamation-triangle' : 'bi-x-circle');
    statusEl.innerHTML = `<span class="${colorClass} fw-semibold"><i class="bi ${icon} me-1"></i> ${msg}</span>`;
}

function calcReturnTotals() {
    let totalPcs = 0;
    let totalAmount = 0.0;

    document.querySelectorAll('input[name="selected_tags[]"]:checked').forEach(cb => {
        totalPcs += 1;
        totalAmount += parseFloat(cb.dataset.price || 0);
    });

    document.querySelectorAll('input[name="selected_items[]"]:checked').forEach(cb => {
        const itemId = cb.value;
        const qtyInput = document.getElementById('qty_item_' + itemId);
        const qty = qtyInput ? parseInt(qtyInput.value || 1) : 1;
        totalPcs += qty;
        totalAmount += qty * parseFloat(cb.dataset.price || 0);
    });

    const badge = document.getElementById('returnSelectedBadge');
    if (badge) {
        badge.textContent = `${totalPcs} piece(s) selected (₹${totalAmount.toFixed(2)})`;
        badge.className = totalPcs > 0 ? 'badge bg-success px-3 py-2 fs-6 shadow-sm' : 'badge bg-secondary px-3 py-2 fs-6 shadow-sm';
    }

    const btnSubmit = document.getElementById('btnSubmitReturn');
    if (btnSubmit && !btnSubmit.dataset.blocked) {
        btnSubmit.disabled = (totalPcs === 0);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const returnForm = document.getElementById('returnForm');
    if (returnForm) {
        returnForm.addEventListener('submit', function(e) {
            const checkedTags = document.querySelectorAll('input[name="selected_tags[]"]:checked');
            const checkedItems = document.querySelectorAll('input[name="selected_items[]"]:checked');
            if (checkedTags.length === 0 && checkedItems.length === 0) {
                e.preventDefault();
                alert('Please select at least one garment piece to return.');
                return false;
            }

            if (currentFlow === 'EXCHANGE') {
                const exNotes = document.getElementById('inputExchangeNotes').value.trim();
                if (!exNotes) {
                    e.preventDefault();
                    alert('Please specify the exchange / replacement product requirement.');
                    document.getElementById('inputExchangeNotes').focus();
                    return false;
                }
            }
        });
    }

    const scanInput = document.getElementById('universalScanInput');
    if (scanInput) {
        scanInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleUniversalScan();
            }
        });
    }
});
</script>
