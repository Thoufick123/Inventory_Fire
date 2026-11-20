<?php
// Create Stock Adjustment View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-sliders2 text-primary me-2"></i>New Stock Adjustment</h4>
            <p class="text-muted small mb-0">Scan product variant QR or enter SKU to reconcile and update stock balances</p>
        </div>
        <div>
            <a href="<?= url('production/stock-adjustments') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('production/stock-adjustments/store') ?>" id="adjustmentForm">
        <?= CSRF::field() ?>
        <input type="hidden" name="variant_id" id="variantId" value="">
        <input type="hidden" name="scanned_qr_codes" id="scannedQrCodes" value="">

        <div class="row g-4">
            <!-- Left Column: Step 1 QR Lookup -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-qr-code-scan text-primary me-2"></i>Step 1: Scan or Search Product Variant QR
                        </h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label fw-semibold small">Scan Variant QR / Enter Variant SKU / Code</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" id="qrInput" class="form-control font-monospace fw-bold" placeholder="Scan or type e.g. GRM-KID-001-S or V0001" autofocus>
                            <button type="button" class="btn btn-primary" id="btnLookup" onclick="lookupQr()">
                                <i class="bi bi-search me-1"></i> Resolve
                            </button>
                        </div>
                        <div id="lookupStatus" class="small text-muted mb-3">Press Enter or click Resolve to fetch variant details.</div>

                        <div class="text-center my-2 text-muted small">— OR SELECT FROM MASTER LIST —</div>

                        <label class="form-label fw-semibold small">Select Variant from Catalog</label>
                        <select id="variantSelect" class="form-select form-select-sm" onchange="onSelectDropdown(this.value)">
                            <option value="">-- Choose Variant --</option>
                            <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>" data-sku="<?= e($v['sku']) ?>">
                                <?= !empty($v['variant_code']) ? '[' . e($v['variant_code']) . '] ' : '' ?><?= e($v['product_name']) ?> · <?= e($v['color_name']) ?>/<?= e($v['design_name'] ?: 'Plain') ?> · <?= e($v['size_name']) ?> (<?= e($v['sku']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Product Preview Card -->
                <div class="card border-0 shadow-sm border-start border-4 border-primary d-none" id="productCard">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tag text-primary me-2"></i>Resolved Variant Details</h6>
                        <span class="badge bg-primary-subtle text-primary border font-monospace" id="prevVariantCode">—</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="text-muted small d-block">Product Style / Catalog</span>
                            <h5 class="fw-bold text-dark mb-0" id="prevProductName">—</h5>
                            <span class="font-monospace small text-muted" id="prevParentSku">—</span>
                        </div>
                        <div class="row g-2 py-2 my-2 border-top border-bottom bg-light rounded">
                            <div class="col-4">
                                <span class="text-muted small d-block">Category</span>
                                <strong id="prevCategory" class="small">—</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small d-block">Attributes</span>
                                <strong id="prevAttributes" class="small">—</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small d-block">Size</span>
                                <strong id="prevSize" class="badge bg-secondary">—</strong>
                            </div>
                        </div>
                        <div class="p-3 bg-primary-subtle text-primary rounded-3 text-center mt-3">
                            <span class="small fw-semibold text-uppercase d-block mb-1">Current Available Stock</span>
                            <h3 class="fw-bold mb-0" id="prevStock">0 pcs</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Step 2 Adjustment Action -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-sliders text-primary me-2"></i>Step 2: Adjustment Details &amp; Quantity
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Adjustment Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check p-3 border rounded-3 flex-fill bg-light" id="optIncreaseBox">
                                        <input class="form-check-input" type="radio" name="adjustment_type" id="typeIncrease" value="INCREASE" checked onchange="onTypeChange('INCREASE')">
                                        <label class="form-check-label fw-bold text-success cursor-pointer" for="typeIncrease">
                                            <i class="bi bi-arrow-up-circle me-1"></i> + INCREASE Stock
                                        </label>
                                        <div class="small text-muted mt-1">Physical audit surplus, found goods, production excess.</div>
                                    </div>
                                    <div class="form-check p-3 border rounded-3 flex-fill bg-light" id="optDecreaseBox">
                                        <input class="form-check-input" type="radio" name="adjustment_type" id="typeDecrease" value="DECREASE" onchange="onTypeChange('DECREASE')">
                                        <label class="form-check-label fw-bold text-danger cursor-pointer" for="typeDecrease">
                                            <i class="bi bi-arrow-down-circle me-1"></i> - DECREASE Stock
                                        </label>
                                        <div class="small text-muted mt-1">Damages, fabric tear, shrinkage, lost in handling.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Target Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouseId" class="form-select" onchange="refreshStockForSelectedWarehouse()">
                                    <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Adjustment Quantity (pcs) <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="adjQty" class="form-control form-control-lg fw-bold text-center" min="1" step="1" placeholder="e.g. 5" required oninput="validateQuantity()">
                                <div id="qtyWarning" class="small text-danger fw-semibold mt-1 d-none"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Reason Category <span class="text-danger">*</span></label>
                                <select name="reason_category" id="reasonCategory" class="form-select form-select-lg" required>
                                    <option value="PHYSICAL_AUDIT_DISCREPANCY">Physical Count Reconciliation</option>
                                    <option value="DAMAGED_IN_STORE">Damaged / Stained / Fabric Defect</option>
                                    <option value="FOUND_STOCK">Found / Unrecorded Stock</option>
                                    <option value="PRODUCTION_OVERRUN">Production Overrun / Surplus Pieces</option>
                                    <option value="THEFT_OR_SHRINKAGE">Shrinkage / Lost / Discrepancy</option>
                                    <option value="QUALITY_REJECTION">QC Rejection &amp; Scrap</option>
                                    <option value="OTHER">Other Operational Reason</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Detailed Remarks / Justification</label>
                                <textarea name="reason_notes" class="form-control" rows="3" placeholder="Provide context, audit batch reference, inspector notes, etc."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold" id="btnSubmit" disabled>
                            <i class="bi bi-check-circle me-1"></i> Apply Stock Adjustment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let currentVariant = null;

document.getElementById('qrInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        lookupQr();
    }
});

function lookupQr() {
    const query = document.getElementById('qrInput').value.trim();
    if (!query) {
        showStatus('Please enter or scan a QR code.', true);
        return;
    }
    const warehouseId = document.getElementById('warehouseId').value;
    showStatus('Resolving QR identity...', false);

    const formData = new FormData();
    formData.append('query', query);
    formData.append('warehouse_id', warehouseId);

    fetch('<?= url("api/stock-adjustments/resolve-item") ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.variant) {
            populateVariant(data.variant);
            showStatus('Variant resolved successfully!', false);
        } else {
            showStatus(data.message || 'Product variant not found.', true);
            hideProductCard();
        }
    })
    .catch(err => {
        console.error(err);
        showStatus('Error communicating with server.', true);
    });
}

function onSelectDropdown(variantId) {
    if (!variantId) return;
    document.getElementById('qrInput').value = variantId;
    lookupQr();
}

function refreshStockForSelectedWarehouse() {
    if (currentVariant) {
        lookupQr();
    }
}

function populateVariant(v) {
    currentVariant = v;
    document.getElementById('variantId').value = v.id;
    document.getElementById('scannedQrCodes').value = v.qr_code || v.sku;

    document.getElementById('prevVariantCode').textContent = v.variant_code || 'V-ID: ' + v.id;
    document.getElementById('prevProductName').textContent = v.product_name;
    document.getElementById('prevParentSku').textContent = v.parent_sku + ' · ' + v.sku;
    document.getElementById('prevCategory').textContent = v.category_name;
    document.getElementById('prevAttributes').textContent = v.color_name + ' / ' + v.design_name;
    document.getElementById('prevSize').textContent = v.size_name;
    document.getElementById('prevStock').textContent = v.available_stock + ' pcs';

    document.getElementById('productCard').classList.remove('d-none');
    document.getElementById('btnSubmit').disabled = false;
    validateQuantity();
}

function hideProductCard() {
    currentVariant = null;
    document.getElementById('variantId').value = '';
    document.getElementById('productCard').classList.add('d-none');
    document.getElementById('btnSubmit').disabled = true;
}

function showStatus(msg, isError) {
    const el = document.getElementById('lookupStatus');
    el.textContent = msg;
    el.className = 'small mb-3 ' + (isError ? 'text-danger fw-semibold' : 'text-success fw-semibold');
}

function onTypeChange(type) {
    validateQuantity();
}

function validateQuantity() {
    const qtyInput = document.getElementById('adjQty');
    const warning = document.getElementById('qtyWarning');
    const btnSubmit = document.getElementById('btnSubmit');
    const isDecrease = document.getElementById('typeDecrease').checked;
    const qty = parseFloat(qtyInput.value) || 0;

    warning.classList.add('d-none');
    btnSubmit.disabled = !currentVariant || qty <= 0;

    if (isDecrease && currentVariant) {
        const available = parseFloat(currentVariant.available_stock) || 0;
        if (qty > available) {
            warning.textContent = `Cannot decrease by ${qty}! Available stock is only ${available} pcs.`;
            warning.classList.remove('d-none');
            btnSubmit.disabled = true;
        }
    }
}
</script>
