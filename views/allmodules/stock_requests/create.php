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
            <span class="text-muted small">Submit an inter-concern / inter-module requisition: ask required product counts from another concern or facility. The Send Area will receive the request and dispatch the stock.</span>
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
            <div class="col-lg-12">
                <!-- Requisition Header & Module Routing -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-signpost-split text-primary me-2"></i>1. Module Routing: Requester Area &rarr; Send Area Concern
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- 1. Requesting Concern (Disabled / Locked to Current Concern) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-box-arrow-up-right text-secondary me-1"></i>Requesting Concern (Who is Asking) <span class="text-danger">*</span></span>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-lock-fill me-1"></i>Locked
                                    </span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0">
                                        <i class="bi bi-building"></i>
                                    </span>
                                    <input type="text" class="form-control fw-bold bg-light border-start-0 text-dark" 
                                           value="<?= e($activeConcern['name'] ?? 'Active Concern') ?><?= !empty($activeConcern['code']) ? ' (' . e($activeConcern['code']) . ')' : '' ?>" 
                                           disabled readonly tabindex="-1" style="cursor: not-allowed; background-color: #f8f9fa !important;">
                                </div>
                                <input type="hidden" name="from_module" id="fromModuleInput" value="<?= e($activeConcern['name'] ?? 'Active Concern') ?>">
                                <input type="hidden" name="from_concern_id" id="fromConcernId" value="<?= (int)($activeConcernId ?? 1) ?>">
                                <div class="form-text small text-muted">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Your active business concern is automatically set as the requester and locked.
                                </div>
                            </div>

                            <!-- 2. Fulfilling Send Area (Only Other Concerns - Excludes Requesting Concern) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark mb-1">
                                    <i class="bi bi-send-check text-primary me-1"></i>Fulfilling Send Area (Concern Being Asked) <span class="text-danger">*</span>
                                </label>
                                <select name="to_module" id="toModuleSelect" class="form-select fw-semibold border-primary shadow-xs" required onchange="onFulfillingConcernChange(this)">
                                    <option value="">-- Choose Concern to Request From --</option>
                                    <?php if (!empty($fulfillingConcerns)): ?>
                                        <?php foreach ($fulfillingConcerns as $fc): ?>
                                            <option value="<?= e($fc['name']) ?>" data-concern-id="<?= $fc['id'] ?>">
                                                <?= e($fc['name']) ?><?= !empty($fc['code']) ? ' (' . e($fc['code']) . ')' : '' ?> [<?= e($fc['concern_type'] ?? 'CONCERN') ?>]
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Fallback for standalone installs without secondary concerns -->
                                        <?php foreach ($modulesList as $mVal => $mLabel): ?>
                                            <?php if ($mVal !== ($activeConcern['name'] ?? '')): ?>
                                                <option value="<?= e($mVal) ?>"><?= e($mVal) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <input type="hidden" name="to_concern_id" id="toConcernId" value="">
                                <div class="form-text small text-muted">
                                    <i class="bi bi-check2-circle text-success me-1"></i>Lists all other concerns; requesting concern is excluded.
                                </div>
                            </div>

                            <!-- 3. Requested By -->
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
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-list-check text-primary me-2"></i>2. Garment Products &amp; Asked Counts
                            </h6>
                            <span class="text-muted small">Scan product barcode/QR or select by category &amp; style. Live stock is displayed for both Requester and Send Area.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="addRequestRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product Line
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <!-- Product QR Scanner Bar -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <label for="productQrScanInput" class="form-label fw-bold mb-0 text-dark">
                                    <i class="bi bi-upc-scan text-primary me-1"></i>Scan Product / Variant QR to Ask:
                                </label>
                                <span class="badge border border-warning-subtle text-warning-emphasis bg-warning bg-opacity-10 py-1 px-2.5 rounded-pill fw-semibold small">
                                    <i class="bi bi-upc-scan me-1 text-warning"></i>Hardware Barcode Scanner Ready
                                </span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-primary border-end-0">
                                    <i class="bi bi-qr-code"></i>
                                </span>
                                <input type="text" id="productQrScanInput" class="form-control border-start-0" 
                                       placeholder="Scan or type Product Variant QR (e.g. V0013), SKU, or Product Code and press Enter..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-primary fw-semibold px-4" onclick="handleProductQrScan()">
                                    <i class="bi bi-plus-circle me-1"></i>Scan &amp; Add Item
                                </button>
                            </div>
                            <div id="productQrScanStatus" class="small mt-1" role="status" aria-live="polite"></div>
                        </div>

                        <!-- Products Table with Category and Live Stocks -->
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="reqItemsTable">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th style="width: 20%;">Category Selection</th>
                                        <th style="width: 28%;">Parent Product Style</th>
                                        <th style="width: 27%;">Specific Sellable Variant (V-ID)</th>
                                        <th style="width: 19%;">Current Stock in Areas</th>
                                        <th style="width: 6%;" class="text-end pe-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reqItemsTbody">
                                    <tr id="row-0">
                                        <!-- Category -->
                                        <td>
                                            <select class="form-select form-select-sm category-select fw-semibold" id="category-select-0" onchange="onCategoryChange(this, 0)">
                                                <option value="">-- All Categories --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <!-- Product Style -->
                                        <td>
                                            <select name="items[0][product_id]" id="product-select-0" class="form-select form-select-sm product-select" onchange="onProductChange(this, 0)" required>
                                                <option value="">-- Choose Product Style --</option>
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= $p['id'] ?>" data-category-id="<?= (int)($p['category_id'] ?? 0) ?>" data-name="<?= e($p['name']) ?>" data-code="<?= e($p['code']) ?>">
                                                        <?= e($p['name']) ?> (<?= e($p['code']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <!-- Variant -->
                                        <td>
                                            <select name="items[0][variant_id]" id="variant-select-0" class="form-select form-select-sm variant-select" onchange="onVariantChange(this, 0)">
                                                <option value="">-- Any / All Variants of Selected Style --</option>
                                                <?php foreach ($variants as $v): ?>
                                                    <option value="<?= $v['id'] ?>" data-product-id="<?= $v['product_id'] ?>" data-sku="<?= e($v['sku']) ?>">
                                                        [<?= e($v['variant_code'] ?: ('V' . str_pad($v['id'], 4, '0', STR_PAD_LEFT))) ?>] <?= e($v['sku']) ?> 
                                                        <?= $v['color_name'] ? '· ' . e($v['color_name']) : '' ?> 
                                                        <?= $v['size_name'] ? '· ' . e($v['size_name']) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <!-- Live Stock Display -->
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex align-items-center justify-content-between" style="font-size: 11px;">
                                                    <span class="text-secondary fw-semibold"><i class="bi bi-box-seam me-1"></i>Requester:</span>
                                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-0.5 fw-bold" id="req-stock-0">-- pcs</span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between" style="font-size: 11px;">
                                                    <span class="text-primary fw-semibold"><i class="bi bi-send-fill me-1"></i>Send Area:</span>
                                                    <span class="badge bg-primary-subtle text-primary border px-2 py-0.5 fw-bold" id="send-stock-0">-- pcs</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Action -->
                                        <td class="text-end pe-2">
                                            <input type="hidden" name="items[0][qty]" class="req-qty-input" value="1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRequestRow(0)" title="Remove item" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
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

<!-- Raw Data & Client-Side Filtering -->
<script>
const allCategories = <?= json_encode($categories) ?>;
const allProducts = <?= json_encode($products) ?>;
const allVariants = <?= json_encode($variants) ?>;
const currentRequesterConcernId = <?= (int)($activeConcernId ?? 1) ?>;
let rowCounter = 1;

function onFulfillingConcernChange(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const concernId = selectedOption ? (selectedOption.dataset.concernId || '') : '';
    document.getElementById('toConcernId').value = concernId;

    // Refresh stock badges for all rows
    const rows = document.querySelectorAll('#reqItemsTbody tr');
    rows.forEach(r => {
        const idParts = r.id.split('-');
        if (idParts.length === 2) {
            fetchStockForRow(parseInt(idParts[1], 10));
        }
    });
}

function onCategoryChange(selectElem, rowIndex) {
    const categoryId = selectElem.value;
    const productSelect = document.getElementById('product-select-' + rowIndex);
    if (!productSelect) return;

    productSelect.innerHTML = '<option value="">-- Choose Product Style --</option>';

    const filteredProducts = categoryId
        ? allProducts.filter(p => String(p.category_id) === String(categoryId))
        : allProducts;

    filteredProducts.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.dataset.categoryId = p.category_id || 0;
        opt.dataset.name = p.name;
        opt.dataset.code = p.code;
        opt.textContent = `${p.name} (${p.code})`;
        productSelect.appendChild(opt);
    });

    onProductChange(productSelect, rowIndex);
}

function onProductChange(selectElem, rowIndex) {
    const productId = selectElem.value;
    const variantSelect = document.getElementById('variant-select-' + rowIndex);
    const categorySelect = document.getElementById('category-select-' + rowIndex);
    if (!variantSelect) return;

    // Auto-align category if product has one and category is not chosen yet
    if (productId && categorySelect && !categorySelect.value) {
        const prod = allProducts.find(p => String(p.id) === String(productId));
        if (prod && prod.category_id) {
            categorySelect.value = prod.category_id;
        }
    }

    variantSelect.innerHTML = '<option value="">-- Any / All Variants of Selected Style --</option>';

    if (!productId) {
        allVariants.forEach(v => {
            const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
            const desc = `[${vCode}] ${v.color_name ? v.color_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.dataset.productId = v.product_id;
            opt.textContent = desc;
            variantSelect.appendChild(opt);
        });
        resetStockBadge(rowIndex);
        return;
    }

    const filtered = allVariants.filter(v => String(v.product_id) === String(productId));
    if (filtered.length > 0) {
        filtered.forEach(v => {
            const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
            const desc = `[${vCode}] ${v.color_name ? v.color_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.dataset.productId = v.product_id;
            opt.textContent = desc;
            variantSelect.appendChild(opt);
        });
    } else {
        const opt = document.createElement('option');
        opt.value = "";
        opt.textContent = "No specific variants created for this style (General style request)";
        variantSelect.appendChild(opt);
    }

    fetchStockForRow(rowIndex);
}

function onVariantChange(selectElem, rowIndex) {
    const variantId = selectElem.value;
    const productSelect = document.getElementById('product-select-' + rowIndex);

    if (variantId && productSelect && !productSelect.value) {
        const v = allVariants.find(item => String(item.id) === String(variantId));
        if (v && v.product_id) {
            productSelect.value = v.product_id;
            const categorySelect = document.getElementById('category-select-' + rowIndex);
            const prod = allProducts.find(p => String(p.id) === String(v.product_id));
            if (prod && prod.category_id && categorySelect) {
                categorySelect.value = prod.category_id;
            }
        }
    }

    fetchStockForRow(rowIndex);
}

function resetStockBadge(rowIndex) {
    const reqBadge = document.getElementById('req-stock-' + rowIndex);
    const sendBadge = document.getElementById('send-stock-' + rowIndex);
    if (reqBadge) reqBadge.textContent = '-- pcs';
    if (sendBadge) sendBadge.textContent = '-- pcs';
}

function fetchStockForRow(rowIndex) {
    const productSelect = document.getElementById('product-select-' + rowIndex);
    const variantSelect = document.getElementById('variant-select-' + rowIndex);
    const reqBadge = document.getElementById('req-stock-' + rowIndex);
    const sendBadge = document.getElementById('send-stock-' + rowIndex);

    const productId = productSelect ? parseInt(productSelect.value, 10) || 0 : 0;
    const variantId = variantSelect ? parseInt(variantSelect.value, 10) || 0 : 0;
    const toConcernId = parseInt(document.getElementById('toConcernId').value, 10) || 0;

    if (!productId && !variantId) {
        resetStockBadge(rowIndex);
        return;
    }

    if (reqBadge) reqBadge.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" style="width: 10px; height: 10px;"></span>';
    if (sendBadge) sendBadge.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" style="width: 10px; height: 10px;"></span>';

    const params = new URLSearchParams({
        variant_id: variantId,
        product_id: productId,
        from_concern_id: currentRequesterConcernId,
        to_concern_id: toConcernId
    });

    fetch('<?= url('api/production/stock-requests/variant-stock') ?>?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (reqBadge) {
                    reqBadge.textContent = `${data.requester_stock} pcs`;
                    reqBadge.className = data.requester_stock > 0
                        ? 'badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 fw-bold'
                        : 'badge bg-secondary-subtle text-muted border px-2 py-0.5 fw-bold';
                }
                if (sendBadge) {
                    if (toConcernId > 0) {
                        sendBadge.textContent = `${data.fulfilling_stock} pcs`;
                        sendBadge.className = data.fulfilling_stock > 0
                            ? 'badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 fw-bold'
                            : 'badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 fw-bold';
                    } else {
                        sendBadge.textContent = 'Select Area';
                        sendBadge.className = 'badge bg-light text-muted border px-2 py-0.5';
                    }
                }
            } else {
                resetStockBadge(rowIndex);
            }
        })
        .catch(() => resetStockBadge(rowIndex));
}

function handleProductQrScan() {
    const input = document.getElementById('productQrScanInput');
    const status = document.getElementById('productQrScanStatus');
    const code = input ? input.value.trim() : '';

    if (!code) {
        if (status) status.innerHTML = '<span class="text-danger">Please enter or scan a QR label or Variant ID.</span>';
        return;
    }

    if (status) status.innerHTML = '<span class="text-primary"><i class="bi bi-hourglass-split me-1"></i>Scanning QR code...</span>';

    fetch('<?= url('api/production/stock-requests/scan-product') ?>?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Product or Variant QR not found.');
            }

            // Check if this variant or product already exists in one of the rows
            let targetRowIndex = -1;
            const rows = document.querySelectorAll('#reqItemsTbody tr');
            for (const r of rows) {
                const idParts = r.id.split('-');
                if (idParts.length === 2) {
                    const idx = parseInt(idParts[1], 10);
                    const pSel = document.getElementById('product-select-' + idx);
                    const vSel = document.getElementById('variant-select-' + idx);
                    if (data.is_variant && vSel && String(vSel.value) === String(data.variant_id)) {
                        targetRowIndex = idx;
                        break;
                    } else if (!data.is_variant && pSel && String(pSel.value) === String(data.product_id) && (!vSel || !vSel.value)) {
                        targetRowIndex = idx;
                        break;
                    }
                }
            }

            if (targetRowIndex !== -1) {
                // Increment existing row qty
                const qtyInput = document.querySelector(`#row-${targetRowIndex} .req-qty-input`);
                if (qtyInput) {
                    const cur = parseInt(qtyInput.value, 10) || 0;
                    qtyInput.value = cur + 1;
                    calculateTotalAsked();
                }
                if (status) {
                    status.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Incremented requested count for [${data.variant_code || data.product_name}] to ${qtyInput ? qtyInput.value : 1} pcs.</span>`;
                }
            } else {
                // Check if row 0 is empty / pristine
                const row0Product = document.getElementById('product-select-0');
                let useRowIndex = 0;
                if (!row0Product || row0Product.value) {
                    useRowIndex = addRequestRow();
                }

                // Populate row
                const catSel = document.getElementById('category-select-' + useRowIndex);
                if (catSel && data.category_id) {
                    catSel.value = data.category_id;
                    onCategoryChange(catSel, useRowIndex);
                }

                const prodSel = document.getElementById('product-select-' + useRowIndex);
                if (prodSel && data.product_id) {
                    prodSel.value = data.product_id;
                    onProductChange(prodSel, useRowIndex);
                }

                const varSel = document.getElementById('variant-select-' + useRowIndex);
                if (varSel && data.variant_id) {
                    varSel.value = data.variant_id;
                    onVariantChange(varSel, useRowIndex);
                }

                if (status) {
                    const label = data.is_variant ? `[${data.variant_code}] ${data.product_name}` : `${data.product_name}`;
                    status.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Scanned and added: <strong>${label}</strong>.</span>`;
                }
            }

            if (input) {
                input.value = '';
                input.focus();
            }
        })
        .catch(err => {
            if (status) status.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>${err.message || 'Scan error'}</span>`;
        });
}

function addRequestRow() {
    const tbody = document.getElementById('reqItemsTbody');
    const newIndex = rowCounter++;

    let categoryOptions = '<option value="">-- All Categories --</option>';
    allCategories.forEach(c => {
        categoryOptions += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
    });

    let productOptions = '<option value="">-- Choose Product Style --</option>';
    allProducts.forEach(p => {
        productOptions += `<option value="${p.id}" data-category-id="${p.category_id || 0}" data-name="${escapeHtml(p.name)}" data-code="${escapeHtml(p.code)}">${escapeHtml(p.name)} (${escapeHtml(p.code)})</option>`;
    });

    let variantOptions = '<option value="">-- Any / All Variants of Selected Style --</option>';
    allVariants.forEach(v => {
        const vCode = v.variant_code || ('V' + String(v.id).padStart(4, '0'));
        const desc = `[${vCode}] ${v.color_name ? v.color_name : ''} ${v.size_name ? '· ' + v.size_name : ''}`;
        variantOptions += `<option value="${v.id}" data-product-id="${v.product_id}">${escapeHtml(desc)}</option>`;
    });

    const tr = document.createElement('tr');
    tr.id = 'row-' + newIndex;
    tr.innerHTML = `
        <td>
            <select class="form-select form-select-sm category-select fw-semibold" id="category-select-${newIndex}" onchange="onCategoryChange(this, ${newIndex})">
                ${categoryOptions}
            </select>
        </td>
        <td>
            <select name="items[${newIndex}][product_id]" id="product-select-${newIndex}" class="form-select form-select-sm product-select" onchange="onProductChange(this, ${newIndex})" required>
                ${productOptions}
            </select>
        </td>
        <td>
            <select name="items[${newIndex}][variant_id]" id="variant-select-${newIndex}" class="form-select form-select-sm variant-select" onchange="onVariantChange(this, ${newIndex})">
                ${variantOptions}
            </select>
        </td>
        <td>
            <div class="d-flex flex-column gap-1">
                <div class="d-flex align-items-center justify-content-between" style="font-size: 11px;">
                    <span class="text-secondary fw-semibold"><i class="bi bi-box-seam me-1"></i>Requester:</span>
                    <span class="badge bg-secondary-subtle text-dark border px-2 py-0.5 fw-bold" id="req-stock-${newIndex}">-- pcs</span>
                </div>
                <div class="d-flex align-items-center justify-content-between" style="font-size: 11px;">
                    <span class="text-primary fw-semibold"><i class="bi bi-send-fill me-1"></i>Send Area:</span>
                    <span class="badge bg-primary-subtle text-primary border px-2 py-0.5 fw-bold" id="send-stock-${newIndex}">-- pcs</span>
                </div>
            </div>
        </td>
        <td class="text-end pe-2">
            <input type="hidden" name="items[${newIndex}][qty]" class="req-qty-input" value="1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRequestRow(${newIndex})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    calculateTotalAsked();
    updateDeleteButtons();
    return newIndex;
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

function validateStockReqForm() {
    const toModule = document.getElementById('toModuleSelect').value.trim();
    if (!toModule) {
        alert("Please select a Fulfilling Send Area concern to request stock from.");
        return false;
    }

    const prodSelects = document.querySelectorAll('.product-select');
    let hasProduct = false;
    prodSelects.forEach(sel => {
        if (sel.value && String(sel.value).trim() !== '') hasProduct = true;
    });
    if (!hasProduct) {
        alert("Please select or scan at least one product style.");
        return false;
    }
    return true;
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Scanner hardware trigger on Enter
document.addEventListener('DOMContentLoaded', () => {
    calculateTotalAsked();
    const scanInput = document.getElementById('productQrScanInput');
    if (scanInput) {
        scanInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleProductQrScan();
            }
        });
    }
});
</script>
