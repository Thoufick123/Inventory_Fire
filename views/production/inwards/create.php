<?php
// Goods Inward (GRN) Creation View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Create Goods Receipt Note (GRN)</h4>
            <p class="text-muted small mb-0">Record incoming shipments from Purchase Orders or direct factory deliveries</p>
        </div>
        <div>
            <a href="<?= url('production/inwards') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inwards
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('production/inwards/store') ?>" id="inwardForm">
        <?= csrfField() ?>

        <div class="row g-3">
            <!-- Header Details Card -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Shipment &amp; Consignment Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">GRN Number</label>
                                <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-light" value="<?= e($nextGrn) ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Source Purchase Order</label>
                                <select name="purchase_order_id" id="poSelect" class="form-select form-select-sm">
                                    <option value="">-- Direct Delivery (No PO) --</option>
                                    <?php foreach ($purchaseOrders as $po): ?>
                                    <option value="<?= $po['id'] ?>"><?= e($po['po_number']) ?> (<?= date('d-m-Y', strtotime($po['order_date'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Receiving Destination <span class="text-danger">*</span></label>
                                <select name="warehouse_id" class="form-select form-select-sm" required>
                                    <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>" selected><?= e($wh['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Receipt Date <span class="text-danger">*</span></label>
                                <input type="date" name="received_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Supplier Invoice / DC #</label>
                                <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. INV-98745">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Invoice Date</label>
                                <input type="date" name="invoice_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Vehicle Number</label>
                                <input type="text" name="vehicle_number" class="form-control form-control-sm" placeholder="e.g. TN-38-AX-1234">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Gate Pass Number</label>
                                <input type="text" name="gate_pass_number" class="form-control form-control-sm" placeholder="e.g. GP-2026-004">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Driver Name</label>
                                <input type="text" name="driver_name" class="form-control form-control-sm" placeholder="Driver name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Driver Phone</label>
                                <input type="text" name="driver_contact" class="form-control form-control-sm" placeholder="Phone number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Inward Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="STOCKED" selected>Accept &amp; Stock Directly (Immediate Stock +)</option>
                                    <option value="INSPECTED">Inspected (Hold for Stock Inward)</option>
                                    <option value="PENDING">Pending Verification</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-qr-code-scan text-primary me-2"></i>Variant QR Inward Cross-Check</h6>
                        <small class="text-muted">Scan each received unit's variant QR. Purchase-order quantities are shown as expected quantities.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-lg">
                                <div class="border rounded p-2 h-100">
                                    <div class="small text-muted text-uppercase">Expected</div>
                                    <strong class="fs-5" id="expectedQty">0</strong><span class="small text-muted ms-1">pcs</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg">
                                <div class="border rounded p-2 h-100">
                                    <div class="small text-muted text-uppercase">Scanned</div>
                                    <strong class="fs-5 text-primary" id="scannedQty">0</strong><span class="small text-muted ms-1">pcs</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg">
                                <div class="border rounded p-2 h-100">
                                    <div class="small text-muted text-uppercase">Damaged</div>
                                    <strong class="fs-5 text-danger" id="damagedQty">0</strong><span class="small text-muted ms-1">pcs</span>
                                </div>
                            </div>
                            <div class="col-6 col-lg">
                                <div class="border rounded p-2 h-100">
                                    <div class="small text-muted text-uppercase">Pending</div>
                                    <strong class="fs-5 text-warning" id="pendingQty">0</strong><span class="small text-muted ms-1">pcs</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg">
                                <div class="border rounded p-2 h-100">
                                    <div class="d-flex justify-content-between small text-muted text-uppercase">
                                        <span>Verification match</span>
                                        <span id="matchPercent">0%</span>
                                    </div>
                                    <div class="progress mt-2" role="progressbar" aria-label="Verification match">
                                        <div class="progress-bar bg-info" id="matchProgress" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light border rounded">
                            <label for="scanQrInput" class="form-label fw-semibold mb-2">
                                <i class="bi bi-upc-scan me-1 text-primary"></i>Scan Variant QR
                                <span class="badge bg-primary-subtle text-primary border ms-1">QR only</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white"><i class="bi bi-qr-code text-primary"></i></span>
                                <input type="text" id="scanQrInput" class="form-control font-monospace" placeholder="Scan variant QR code..." autocomplete="off" disabled>
                                <button type="button" class="btn btn-primary px-4" id="scanQrButton" disabled>
                                    <i class="bi bi-plus-circle me-1"></i> Scan
                                </button>
                            </div>
                            <div id="scanFeedback" class="small mt-2 text-muted" role="status" aria-live="polite">
                                Select a purchase order or use Direct Delivery, then scan a variant QR.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check text-primary me-2"></i>Variant Breakdown &amp; Inward Reconcile</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="min-width: 250px;">Product &amp; Variant</th>
                                        <th style="min-width: 150px;">Variant QR</th>
                                        <th class="text-center" style="min-width: 95px;">Expected</th>
                                        <th class="text-center" style="min-width: 125px;">Scanned</th>
                                        <th class="text-center" style="min-width: 105px;">Damaged</th>
                                        <th class="text-center" style="min-width: 95px;">Accepted</th>
                                        <th style="min-width: 180px;">Damage / Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="emptyItemsRow">
                                        <td colspan="7" class="text-center py-4 text-muted">Select a purchase order or scan a QR for Direct Delivery.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3">
                        <div class="row align-items-end g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold mb-1">General Inward Notes / Remarks</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Inspection observations, package seal conditions, etc."></textarea>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <button type="submit" id="saveGrnButton" class="btn btn-primary btn-lg px-4 fw-semibold" disabled>
                                    <i class="bi bi-check2-circle me-1"></i> Complete &amp; Save GRN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="scan_log" id="scanLog" value="[]">
    </form>
</div>

<script>
const variantsList = <?= json_encode(array_map(function($v) {
    return [
        'id'    => (int)$v['id'],
        'sku'   => $v['sku'] ?? '',
        'code'  => $v['variant_code'] ?? '',
        'qr'    => !empty($v['qr_code']) ? $v['qr_code'] : ($v['sku'] ?? ''),
        'name'  => $v['product_name'] ?? 'Product',
        'color' => $v['color_name'] ?? '',
        'design'=> $v['design_name'] ?? '',
        'size'  => $v['size_name'] ?? '',
        'mrp'   => $v['mrp'] ?? 0,
    ];
}, $variants), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

let rowIndex = 0;
let poItems = [];
let poMode = false;
let poLoading = false;
let poLoadVersion = 0;
let activePoId = '';
let scanLog = [];
const rowsByVariantId = new Map();
const itemsBody = document.getElementById('itemsBody');
const scanInput = document.getElementById('scanQrInput');
const scanButton = document.getElementById('scanQrButton');
const saveButton = document.getElementById('saveGrnButton');

function normalizeQr(value) {
    return String(value || '').trim().toUpperCase();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

function setFeedback(message, type = 'muted') {
    const feedback = document.getElementById('scanFeedback');
    const classes = {
        muted: 'text-muted',
        success: 'text-success',
        error: 'text-danger',
        info: 'text-primary',
    };
    feedback.className = `small mt-2 ${classes[type] || classes.muted}`;
    feedback.textContent = message;
}

function syncScanLog() {
    document.getElementById('scanLog').value = JSON.stringify(scanLog);
    saveButton.disabled = scanLog.length === 0 || poLoading;
}

function updateSummary() {
    let expected = 0;
    let scanned = 0;
    let damaged = 0;
    itemsBody.querySelectorAll('tr[data-variant-id]').forEach(row => {
        const index = row.dataset.rowIndex;
        expected += Number(document.getElementById(`ordered_${index}`).value) || 0;
        scanned += Number(document.getElementById(`rec_${index}`).value) || 0;
        damaged += Number(document.getElementById(`dam_${index}`).value) || 0;
    });

    const percent = expected > 0 ? Math.min(100, Math.round(scanned / expected * 100)) : (scanned > 0 ? 100 : 0);
    document.getElementById('expectedQty').textContent = expected.toLocaleString();
    document.getElementById('scannedQty').textContent = scanned.toLocaleString();
    document.getElementById('damagedQty').textContent = damaged.toLocaleString();
    document.getElementById('pendingQty').textContent = Math.max(0, expected - scanned).toLocaleString();
    document.getElementById('matchPercent').textContent = `${percent}%`;
    document.getElementById('matchProgress').style.width = `${percent}%`;
}

function addItemRow(data) {
    const tr = document.createElement('tr');
    const index = rowIndex++;
    const variantId = Number(data.id || data.variant_id);
    const qr = String(data.qr || data.variant_qr || data.sku || data.variant_sku || '');
    const variantCode = data.code || data.variant_code || '';
    const productName = data.name || data.product_name || 'Product';
    const sku = data.sku || data.variant_sku || '';
    const color = data.color || data.color_name || '';
    const design = data.design || data.design_name || '';
    const size = data.size || data.size_name || '';
    const orderedQty = Number(data.ordered_qty) || 0;
    const mrp = Number(data.mrp || data.unit_price) || 0;

    document.getElementById('emptyItemsRow')?.remove();
    tr.id = `row_${index}`;
    tr.dataset.rowIndex = String(index);
    tr.dataset.variantId = String(variantId);

    tr.innerHTML = `
        <td class="p-2">
            <input type="hidden" name="items[${index}][variant_id]" value="${variantId}">
            <input type="hidden" name="items[${index}][qr_code]" value="${escapeHtml(qr)}">
            <input type="hidden" name="items[${index}][ordered_qty]" id="ordered_${index}" value="${orderedQty}">
            <input type="hidden" name="items[${index}][mrp]" value="${mrp}">
            <div class="fw-semibold">${escapeHtml(productName)}</div>
            <div class="small text-muted">${escapeHtml([variantCode, color, design, size].filter(Boolean).join(' · '))}</div>
            <div class="small font-monospace text-secondary">${escapeHtml(sku)}</div>
        </td>
        <td class="p-2">
            <span class="badge bg-dark-subtle text-dark font-monospace">${escapeHtml(qr)}</span>
        </td>
        <td class="p-2 text-center fw-semibold">${orderedQty.toLocaleString()}</td>
        <td class="p-2">
            <div class="input-group input-group-sm">
                <button type="button" class="btn btn-outline-secondary" aria-label="Undo one scan" title="Undo one scan" onclick="undoScan(${index})"><i class="bi bi-dash"></i></button>
                <input type="number" id="rec_${index}" class="form-control text-center fw-bold text-primary" value="0" readonly aria-label="Scanned quantity">
            </div>
        </td>
        <td class="p-2 text-center">
            <input type="number" name="items[${index}][damaged_qty]" id="dam_${index}" class="form-control form-control-sm text-center text-danger" value="0" min="0" max="0" oninput="calcAccepted(${index})">
        </td>
        <td class="p-2 text-center fw-bold text-success" id="acc_${index}">0</td>
        <td class="p-2">
            <input type="text" name="items[${index}][rejection_reason]" class="form-control form-control-sm" placeholder="Reason if damaged">
        </td>
    `;

    itemsBody.appendChild(tr);
    rowsByVariantId.set(variantId, index);
    return index;
}

function removeRow(index) {
    const row = document.getElementById(`row_${index}`);
    if (!row) return;
    rowsByVariantId.delete(Number(row.dataset.variantId));
    row.remove();
    updateSummary();
}

function calcAccepted(index) {
    const received = Number(document.getElementById(`rec_${index}`)?.value) || 0;
    const damagedInput = document.getElementById(`dam_${index}`);
    if (!damagedInput) return;
    const damaged = Math.min(received, Math.max(0, Number(damagedInput.value) || 0));
    damagedInput.value = String(damaged);
    damagedInput.max = String(received);
    document.getElementById(`acc_${index}`).textContent = String(received - damaged);
    updateSummary();
}

function scanVariant(qr) {
    const normalizedQr = normalizeQr(qr);
    const lookup = poMode ? poItems : variantsList;
    const matches = lookup.filter(item => normalizeQr(item.qr || item.variant_qr || item.sku || item.variant_sku) === normalizedQr);
    if (matches.length !== 1) {
        setFeedback(matches.length > 1 ? 'This QR matches more than one variant. Please resolve the duplicate QR before inwarding.' : (poMode ? 'This variant QR is not in the selected purchase order.' : 'No active variant was found for this QR.'), 'error');
        return;
    }

    const item = matches[0];
    const variantId = Number(item.id || item.variant_id);
    let index = rowsByVariantId.get(variantId);
    if (poMode && index === undefined) {
        setFeedback('This variant is not part of the selected purchase order.', 'error');
        return;
    }
    if (index === undefined) {
        index = addItemRow(item);
    }

    scanLog.push({ variant_id: variantId, qr_code: String(item.qr || item.variant_qr || item.sku || item.variant_sku) });
    const receivedInput = document.getElementById(`rec_${index}`);
    receivedInput.value = String((Number(receivedInput.value) || 0) + 1);
    calcAccepted(index);
    syncScanLog();
    setFeedback(`QR accepted: ${item.name || item.product_name || 'Product'} · ${item.code || item.variant_code || item.sku || item.variant_sku}`, 'success');
    scanInput.value = '';
    scanInput.focus();
}

function undoScan(index) {
    const row = document.getElementById(`row_${index}`);
    if (!row) return;
    const variantId = Number(row.dataset.variantId);
    let scanIndex = -1;
    for (let i = scanLog.length - 1; i >= 0; i--) {
        if (Number(scanLog[i].variant_id) === variantId) {
            scanIndex = i;
            break;
        }
    }
    if (scanIndex < 0) return;
    scanLog.splice(scanIndex, 1);
    const receivedInput = document.getElementById(`rec_${index}`);
    receivedInput.value = String(Math.max(0, (Number(receivedInput.value) || 0) - 1));
    calcAccepted(index);
    syncScanLog();
    if (!poMode && Number(receivedInput.value) === 0) removeRow(index);
    setFeedback('Last scan for this variant was removed.', 'info');
}

async function onPoSelect(poId) {
    const requestVersion = ++poLoadVersion;
    poMode = Boolean(poId);
    poLoading = poMode;
    poItems = [];
    scanLog = [];
    rowsByVariantId.clear();
    itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="7" class="text-center py-4 text-muted">Loading purchase-order items...</td></tr>';
    syncScanLog();
    updateSummary();
    scanInput.disabled = poLoading;
    scanButton.disabled = poLoading;

    if (!poMode) {
        itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="7" class="text-center py-4 text-muted">Scan a variant QR to add a Direct Delivery item.</td></tr>';
        scanInput.disabled = false;
        scanButton.disabled = false;
        setFeedback('Direct Delivery selected. Scan the QR printed for each received variant.', 'info');
        scanInput.focus();
        return;
    }

    try {
        const response = await fetch('<?= url("api/inwards/po-items/") ?>' + encodeURIComponent(poId), {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Could not load purchase-order items.');
        }
        if (requestVersion !== poLoadVersion) return;
        itemsBody.innerHTML = '';
        poItems = (data.items || []).map(item => ({
            ...item,
            id: Number(item.variant_id),
            qr: item.variant_qr || item.variant_sku || '',
            ordered_qty: Number(item.quantity) || 0,
            mrp: Number(item.mrp || item.unit_price) || 0,
        }));
        if (poItems.length === 0) {
            itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="7" class="text-center py-4 text-muted">This purchase order has no scannable variant items.</td></tr>';
            setFeedback('No variant items were found for this purchase order.', 'error');
            return;
        }
        poItems.forEach(item => addItemRow(item));
        updateSummary();
        setFeedback('Purchase order loaded. Scan each received variant QR to update its count.', 'info');
    } catch (error) {
        if (requestVersion !== poLoadVersion) return;
        console.error('Failed to load purchase-order items:', error);
        itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="7" class="text-center py-4 text-danger">Purchase-order items could not be loaded. Please retry or select Direct Delivery.</td></tr>';
        setFeedback(error.message || 'Purchase-order items could not be loaded.', 'error');
    } finally {
        if (requestVersion === poLoadVersion) {
            poLoading = false;
            scanInput.disabled = false;
            scanButton.disabled = false;
            syncScanLog();
            scanInput.focus();
        }
    }
}

scanButton.addEventListener('click', () => scanVariant(scanInput.value));
scanInput.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        scanVariant(scanInput.value);
    }
});
document.getElementById('poSelect').addEventListener('change', event => {
    const nextPoId = event.target.value;
    if (scanLog.length > 0 && !window.confirm('Changing the purchase order or delivery mode will clear all current scans. Continue?')) {
        event.target.value = activePoId;
        return;
    }
    activePoId = nextPoId;
    onPoSelect(nextPoId);
});
document.getElementById('inwardForm').addEventListener('submit', event => {
    if (scanLog.length === 0) {
        event.preventDefault();
        setFeedback('Scan at least one variant QR before saving this inward.', 'error');
        scanInput.focus();
    }
});

scanInput.disabled = false;
scanButton.disabled = false;
setFeedback('Direct Delivery selected. Scan the QR printed for each received variant.', 'info');
syncScanLog();
updateSummary();
</script>
