<?php
// Production Unit & Inter-Module Create Stock Requirement Form
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Create New Stock Requirement
            </h4>
            <span class="text-muted small">Submit an inter-module requisition: ask required product counts from another module/area. The Send Area will receive the request and dispatch the stock.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/stock-requests') ?>" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Requirements
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('production/stock-requests/store') ?>" id="stockReqForm" onsubmit="return validateStockReqForm()">
        <?= CSRF::field() ?>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-11">
                <!-- Requisition Header & Module Routing -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-signpost-split text-primary me-2"></i>1. Module Routing: Requester Area &rarr; Send Area Concern
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1">
                                    <i class="bi bi-box-arrow-up-right text-secondary me-1"></i>Requesting Module (Who is Asking) <span class="text-danger">*</span>
                                </label>
                                <select name="from_module" id="fromModuleSelect" class="form-select fw-semibold" required onchange="checkDifferentModules()">
                                    <option value="Central Warehouse (Main Logistics Hub)">Central Warehouse (Main Logistics Hub)</option>
                                    <option value="Retail Showroom / POS Store">Retail Showroom / POS Store</option>
                                    <option value="Stitching Unit">Stitching Unit</option>
                                    <option value="Cutting Section">Cutting Section</option>
                                    <option value="Quality Control (QC) Department">Quality Control (QC) Department</option>
                                    <option value="Finishing &amp; Packing Section">Finishing &amp; Packing Section</option>
                                    <option value="WhatsApp B2B Sales Hub">WhatsApp B2B Sales Hub</option>
                                    <option value="Production Factory - Unit 1">Production Factory - Unit 1</option>
                                </select>
                                <div class="form-text small text-muted">The module or department needing stock replenishment.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1">
                                    <i class="bi bi-send-check text-primary me-1"></i>Fulfilling Send Area (Concern Being Asked) <span class="text-danger">*</span>
                                </label>
                                <select name="to_module" id="toModuleSelect" class="form-select fw-semibold border-primary" required onchange="checkDifferentModules()">
                                    <option value="Production Factory - Unit 1" selected>Production Factory - Unit 1 (Finished Goods)</option>
                                    <option value="Central Warehouse (Main Logistics Hub)">Central Warehouse (Main Logistics Hub)</option>
                                    <option value="Cutting Section">Cutting Section (Cut Panels)</option>
                                    <option value="Raw Materials &amp; Fabric Store">Raw Materials &amp; Fabric Store</option>
                                    <option value="Stitching Unit">Stitching Unit</option>
                                    <option value="Quality Control (QC) Department">Quality Control (QC) Department</option>
                                </select>
                                <div class="form-text small text-muted">The supplying area that will receive the asked counts and dispatch stock.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1">
                                    <i class="bi bi-person text-secondary me-1"></i>Requested By (Staff / Incharge) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="requested_by_name" class="form-control" value="<?= e(Auth::user()['full_name'] ?? 'Staff Incharge') ?>" required>
                                <div class="form-text small text-muted">Name of the authorized staff submitting the requirement.</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark mb-1">Priority</label>
                                <select name="priority" class="form-select fw-semibold">
                                    <option value="NORMAL" selected>Normal Priority</option>
                                    <option value="HIGH">High Priority</option>
                                    <option value="URGENT">Urgent (Express Requisition)</option>
                                    <option value="LOW">Low Priority</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark mb-1">Required By Date</label>
                                <input type="date" name="required_by_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1">Requirement Purpose / Remarks</label>
                                <input type="text" name="notes" class="form-control" placeholder="e.g. Urgent stock requisition for retail showroom festival display.">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requisition Line Items & Asked Product Counts -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-list-check text-primary me-2"></i>2. Garment Products &amp; Asked Counts
                            </h6>
                            <span class="text-muted small">Specify each product or variant and the exact quantity requested ("asked product count").</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="addRequestRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product Line
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="reqItemsTable">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th style="width: 35%;">Parent Product Style</th>
                                        <th style="width: 35%;">Specific Sellable Variant (SKU / V-ID)</th>
                                        <th style="width: 20%;" class="text-center">Asked Product Count (pcs) <span class="text-danger">*</span></th>
                                        <th style="width: 10%;" class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reqItemsTbody">
                                    <tr id="row-0">
                                        <td>
                                            <select name="items[0][product_id]" class="form-select product-select" onchange="onProductChange(this, 0)" required>
                                                <option value="">-- Choose Product Style --</option>
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>" data-code="<?= e($p['code']) ?>">
                                                        <?= e($p['name']) ?> (<?= e($p['code']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[0][variant_id]" id="variant-select-0" class="form-select variant-select">
                                                <option value="">-- Any / All Variants of Selected Style --</option>
                                                <?php foreach ($variants as $v): ?>
                                                    <option value="<?= $v['id'] ?>" data-product-id="<?= $v['product_id'] ?>" data-sku="<?= e($v['sku']) ?>">
                                                        [<?= e($v['variant_code'] ?: ('V' . str_pad($v['id'], 4, '0', STR_PAD_LEFT))) ?>] <?= e($v['sku']) ?> 
                                                        <?= $v['color_name'] ? '· ' . e($v['color_name']) : '' ?> 
                                                        <?= $v['design_name'] ? '· ' . e($v['design_name']) : '' ?> 
                                                        <?= $v['size_name'] ? '· ' . e($v['size_name']) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm justify-content-center" style="max-width: 160px; margin: 0 auto;">
                                                <input type="number" name="items[0][qty]" min="1" class="form-control text-center fw-bold fs-6 req-qty-input" value="10" required oninput="calculateTotalAsked()">
                                                <span class="input-group-text">pcs</span>
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRequestRow(0)" title="Remove item" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end fw-bold text-dark">Total Asked Pieces across all lines:</th>
                                        <th class="text-center">
                                            <span class="badge bg-primary fs-6 px-3 py-2 fw-bold" id="totalAskedBadge">10 pcs</span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <a href="<?= url('production/stock-requests') ?>" class="btn btn-outline-secondary px-4 fw-semibold">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="bi bi-send-check me-2"></i> Submit Requirement &amp; Notify Send Area
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Raw Variants JSON for Dynamic Client-Side Filtering -->
<script>
const allVariants = <?= json_encode($variants) ?>;
let rowCounter = 1;

function onProductChange(selectElem, rowIndex) {
    const productId = selectElem.value;
    const variantSelect = document.getElementById('variant-select-' + rowIndex);
    if (!variantSelect) return;

    variantSelect.innerHTML = '<option value="">-- Any / All Variants of Selected Style --</option>';

    if (!productId) {
        allVariants.forEach(v => {
            const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
            const desc = `[${vCode}] ${v.sku} ${v.color_name ? '· ' + v.color_name : ''} ${v.design_name ? '· ' + v.design_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = desc;
            variantSelect.appendChild(opt);
        });
        return;
    }

    const filtered = allVariants.filter(v => String(v.product_id) === String(productId));
    if (filtered.length > 0) {
        filtered.forEach(v => {
            const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
            const desc = `[${vCode}] ${v.sku} ${v.color_name ? '· ' + v.color_name : ''} ${v.design_name ? '· ' + v.design_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = desc;
            variantSelect.appendChild(opt);
        });
    } else {
        const opt = document.createElement('option');
        opt.value = "";
        opt.textContent = "No specific variants created for this style (General style request)";
        variantSelect.appendChild(opt);
    }
}

function addRequestRow() {
    const tbody = document.getElementById('reqItemsTbody');
    const newIndex = rowCounter++;

    let productOptions = '<option value="">-- Choose Product Style --</option>';
    <?php foreach ($products as $p): ?>
        productOptions += `<option value="<?= $p['id'] ?>" data-name="<?= addslashes(e($p['name'])) ?>" data-code="<?= addslashes(e($p['code'])) ?>"><?= addslashes(e($p['name'])) ?> (<?= addslashes(e($p['code'])) ?>)</option>`;
    <?php endforeach; ?>

    let variantOptions = '<option value="">-- Any / All Variants of Selected Style --</option>';
    allVariants.forEach(v => {
        const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
        const desc = `[${vCode}] ${v.sku} ${v.color_name ? '· ' + v.color_name : ''} ${v.design_name ? '· ' + v.design_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
        variantOptions += `<option value="${v.id}" data-product-id="${v.product_id}">${desc}</option>`;
    });

    const tr = document.createElement('tr');
    tr.id = 'row-' + newIndex;
    tr.innerHTML = `
        <td>
            <select name="items[${newIndex}][product_id]" class="form-select product-select" onchange="onProductChange(this, ${newIndex})" required>
                ${productOptions}
            </select>
        </td>
        <td>
            <select name="items[${newIndex}][variant_id]" id="variant-select-${newIndex}" class="form-select variant-select">
                ${variantOptions}
            </select>
        </td>
        <td class="text-center">
            <div class="input-group input-group-sm justify-content-center" style="max-width: 160px; margin: 0 auto;">
                <input type="number" name="items[${newIndex}][qty]" min="1" class="form-control text-center fw-bold fs-6 req-qty-input" value="10" required oninput="calculateTotalAsked()">
                <span class="input-group-text">pcs</span>
            </div>
        </td>
        <td class="text-end pe-3">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRequestRow(${newIndex})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    calculateTotalAsked();
    updateDeleteButtons();
}

function removeRequestRow(index) {
    const row = document.getElementById('row-' + index);
    if (row) {
        row.remove();
        calculateTotalAsked();
        updateDeleteButtons();
    }
}

function updateDeleteButtons() {
    const rows = document.querySelectorAll('#reqItemsTbody tr');
    rows.forEach(r => {
        const btn = r.querySelector('.btn-outline-danger');
        if (btn) {
            btn.disabled = (rows.length <= 1);
        }
    });
}

function calculateTotalAsked() {
    const inputs = document.querySelectorAll('.req-qty-input');
    let total = 0;
    inputs.forEach(inp => {
        const val = parseInt(inp.value, 10);
        if (!isNaN(val) && val > 0) {
            total += val;
        }
    });
    const badge = document.getElementById('totalAskedBadge');
    if (badge) {
        badge.textContent = total.toLocaleString() + ' pcs';
    }
}

function checkDifferentModules() {
    const fromVal = document.getElementById('fromModuleSelect').value;
    const toVal = document.getElementById('toModuleSelect').value;
    if (fromVal === toVal) {
        alert("Warning: Requesting Module and Fulfilling Send Area cannot be the same! Please select distinct departments.");
        return false;
    }
    return true;
}

function validateStockReqForm() {
    if (!checkDifferentModules()) {
        return false;
    }
    const inputs = document.querySelectorAll('.req-qty-input');
    let hasQty = false;
    inputs.forEach(inp => {
        if (parseInt(inp.value, 10) > 0) hasQty = true;
    });
    if (!hasQty) {
        alert("Please specify a valid asked count (greater than zero) for at least one product style.");
        return false;
    }
    return true;
}

// Initial calculation
document.addEventListener('DOMContentLoaded', calculateTotalAsked);
</script>
