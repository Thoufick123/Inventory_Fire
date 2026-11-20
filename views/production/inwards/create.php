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
        <?= CSRF::field() ?>

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
                                <select name="purchase_order_id" id="poSelect" class="form-select form-select-sm" onchange="onPoSelect(this.value)">
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

            <!-- Items Table Card -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-primary me-2"></i>Inward Product Items</h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="addItemRow()">
                                <i class="bi bi-plus-lg me-1"></i> Add Custom Item
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="itemsTable">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 32%;">Product / Variant</th>
                                        <th style="width: 12%;" class="text-center">Ordered Qty</th>
                                        <th style="width: 12%;" class="text-center">Received Qty</th>
                                        <th style="width: 12%;" class="text-center">Damaged / Rej</th>
                                        <th style="width: 12%;" class="text-center">Accepted Qty</th>
                                        <th style="width: 14%;">Rejection Reason / Notes</th>
                                        <th style="width: 6%;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">General Inward Notes / Remarks</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Inspection observations, package seal conditions, etc."></textarea>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> Complete &amp; Save GRN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const variantsList = <?= json_encode(array_map(function($v) {
    return [
        'id'           => $v['id'],
        'sku'          => $v['sku'],
        'code'         => $v['variant_code'] ?? '',
        'name'         => $v['product_name'] ?? 'Product',
        'color'        => $v['color_name'] ?? '',
        'design'       => $v['design_name'] ?? '',
        'size'         => $v['size_name'] ?? '',
        'mrp'          => $v['retail_price'] ?? 0,
    ];
}, $variants)) ?>;

let rowIndex = 0;

function addItemRow(data = {}) {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowIndex;

    let variantOptions = '<option value="">-- Select Product Variant --</option>';
    variantsList.forEach(v => {
        const isSelected = data.variant_id && Number(data.variant_id) === Number(v.id) ? 'selected' : '';
        const label = `${v.code ? '[' + v.code + '] ' : ''}${v.name} · ${v.color} / ${v.design} · Size: ${v.size} (${v.sku})`;
        variantOptions += `<option value="${v.id}" data-mrp="${v.mrp}" ${isSelected}>${label}</option>`;
    });

    tr.innerHTML = `
        <td class="p-2">
            <select name="items[${rowIndex}][variant_id]" class="form-select form-select-sm" required onchange="onVariantChange(this, ${rowIndex})">
                ${variantOptions}
            </select>
            <input type="hidden" name="items[${rowIndex}][mrp]" id="mrp_${rowIndex}" value="${data.mrp || 0}">
        </td>
        <td class="p-2 text-center">
            <input type="number" name="items[${rowIndex}][ordered_qty]" class="form-control form-control-sm text-center" value="${data.ordered_qty || 0}" min="0">
        </td>
        <td class="p-2 text-center">
            <input type="number" name="items[${rowIndex}][received_qty]" id="rec_${rowIndex}" class="form-control form-control-sm text-center fw-bold text-primary" value="${data.received_qty || 0}" min="1" required oninput="calcAccepted(${rowIndex})">
        </td>
        <td class="p-2 text-center">
            <input type="number" name="items[${rowIndex}][damaged_qty]" id="dam_${rowIndex}" class="form-control form-control-sm text-center text-danger" value="${data.damaged_qty || 0}" min="0" oninput="calcAccepted(${rowIndex})">
        </td>
        <td class="p-2 text-center">
            <input type="number" name="items[${rowIndex}][accepted_qty]" id="acc_${rowIndex}" class="form-control form-control-sm text-center fw-bold text-success bg-light" value="${data.accepted_qty || 0}" readonly>
        </td>
        <td class="p-2">
            <input type="text" name="items[${rowIndex}][rejection_reason]" class="form-control form-control-sm" placeholder="Reason if damaged / notes" value="${data.rejection_reason || ''}">
        </td>
        <td class="p-2 text-center">
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(${rowIndex})">&times;</button>
        </td>
    `;

    tbody.appendChild(tr);
    calcAccepted(rowIndex);
    rowIndex++;
}

function removeRow(idx) {
    const row = document.getElementById('row_' + idx);
    if (row) row.remove();
}

function calcAccepted(idx) {
    const recEl = document.getElementById('rec_' + idx);
    const damEl = document.getElementById('dam_' + idx);
    const accEl = document.getElementById('acc_' + idx);
    if (!recEl || !accEl) return;

    const rec = parseInt(recEl.value) || 0;
    const dam = parseInt(damEl ? damEl.value : 0) || 0;
    accEl.value = Math.max(0, rec - dam);
}

function onVariantChange(selectEl, idx) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const mrp = selectedOption.getAttribute('data-mrp') || 0;
    const mrpEl = document.getElementById('mrp_' + idx);
    if (mrpEl) mrpEl.value = mrp;
}

function onPoSelect(poId) {
    if (!poId) return;

    fetch('<?= url("api/inwards/po-items/") ?>' + poId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.items && data.items.length > 0) {
                document.getElementById('itemsBody').innerHTML = '';
                data.items.forEach(item => {
                    addItemRow({
                        variant_id: item.variant_id,
                        ordered_qty: item.quantity,
                        received_qty: item.quantity,
                        damaged_qty: 0,
                        accepted_qty: item.quantity,
                        mrp: item.mrp || item.unit_price,
                    });
                });
            }
        })
        .catch(err => console.error(err));
}

// Add an initial row on page load
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('itemsBody').children.length === 0) {
        addItemRow();
    }
});
</script>
