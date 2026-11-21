<?php
$mappingBillId = (int)$bill['id'];
$pendingQrCount = 0;
foreach ($items as $savedItem) {
    if (empty($savedItem['qr_generation_id'])) {
        $pendingQrCount++;
    }
}
$variantRow = static function (int $index = 0) use ($categories, $subcategories, $subSubcategories): void { ?>
    <tr class="mapping-variant-row">
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][size]" placeholder="Size"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][color]" placeholder="Color"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][sku]" placeholder="SKU"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][quantity]" type="number" step="1" min="1" required placeholder="Qty"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][unit_price]" type="number" step="0.01" min="0" required placeholder="Rate"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][mrp]" type="number" step="0.01" min="0.01" required placeholder="MRP"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][selling_price]" type="number" step="0.01" min="0.01" required placeholder="Sale"></td>
        <td><input class="form-control form-control-sm" name="variants[<?= $index ?>][gst_rate]" type="number" step="0.001" min="0" max="100" value="0" required placeholder="GST %"></td>
        <td><input class="form-control form-control-sm font-monospace" name="variants[<?= $index ?>][hsn_code]" maxlength="20" required placeholder="HSN"></td>
        <td>
            <select class="form-select form-select-sm mapping-category" name="variants[<?= $index ?>][category_id]" required>
                <option value="">Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm mt-1 mapping-subcategory" name="variants[<?= $index ?>][subcategory_id]">
                <option value="">Subcategory</option>
                <?php foreach ($subcategories as $subcategory): ?>
                    <option value="<?= (int)$subcategory['id'] ?>" data-parent="<?= (int)$subcategory['category_id'] ?>"><?= e($subcategory['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm mt-1 mapping-subsubcategory" name="variants[<?= $index ?>][sub_subcategory_id]">
                <option value="">Sub-subcategory</option>
                <?php foreach ($subSubcategories as $subSubcategory): ?>
                    <option value="<?= (int)$subSubcategory['id'] ?>" data-parent="<?= (int)$subSubcategory['subcategory_id'] ?>"><?= e($subSubcategory['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-mapping-variant" title="Remove variant"><i class="bi bi-x-lg"></i></button></td>
    </tr>
<?php };
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-3 me-2 text-primary"></i>Mapping Invoice</h2>
        <div class="text-muted small">
            Supplier: <strong><?= e($bill['seller_company'] ?: $bill['seller_name']) ?></strong>
            <span class="mx-1">·</span> Invoice: <strong class="font-monospace"><?= e($bill['invoice_number']) ?></strong>
            <span class="mx-1">·</span>
            <span class="badge <?= $bill['status'] === 'MOVED' ? 'bg-success' : ($bill['status'] === 'READY' ? 'bg-info text-dark' : 'bg-secondary') ?>"><?= e($bill['status']) ?></span>
            <?php if (!empty($bill['purchase_number'])): ?>
                <span class="ms-1">Purchase <a href="<?= url('purchases/view/' . (int)$bill['purchase_id']) ?>"><?= e($bill['purchase_number']) ?></a></span>
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Mapping Log</a>
</div>

<?php if ($bill['status'] === 'READY'): ?>
    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="bi bi-info-circle-fill me-2"></i>This mapping entry is ready. Click <strong>Move to Purchase Now</strong> to create the purchase bill in the Purchases module immediately.
        </div>
        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/move') ?>" class="m-0">
            <?= CSRF::field() ?>
            <button class="btn btn-sm btn-success" type="submit" <?= !$items ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase Now
            </button>
        </form>
    </div>
<?php elseif ($bill['status'] === 'MOVED'): ?>
    <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="bi bi-check-circle-fill me-2"></i>Products were moved and linked to Purchase <strong><?= e($bill['purchase_number'] ?? ('#' . $bill['purchase_id'])) ?></strong>.
        </div>
        <?php if (!empty($bill['purchase_id'])): ?>
            <a href="<?= url('purchases/view/' . (int)$bill['purchase_id']) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-eye me-1"></i>View Purchase Bill
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($bill['status'] === 'DRAFT'): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Product and Variants</h5>
        <div class="small text-muted mt-1">Each variant has its own quantity, prices, category hierarchy and HSN code.</div>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftProductForm">
            <?= CSRF::field() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Product / Style Name <span class="text-danger">*</span></label>
                    <input class="form-control" name="product_name" required maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Style Code</label>
                    <input class="form-control" name="product_code" maxlength="50">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Unit</label>
                    <select class="form-select" name="unit">
                        <option value="PCS">PCS</option><option value="SET">SET</option><option value="BOX">BOX</option><option value="MTR">MTR</option><option value="KGS">KGS</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Size</th><th>Color</th><th>SKU</th><th>Qty</th><th>Purchase Rate</th><th>MRP</th><th>Selling</th><th>GST %</th><th>HSN</th><th>Category hierarchy</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="mappingVariantRows"><?php $variantRow(0); ?></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                <div class="d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label small mb-1" for="mappingVariantRowsToAdd">Variant rows to add</label>
                        <input class="form-control form-control-sm" id="mappingVariantRowsToAdd" type="number" min="1" max="50" value="1" style="width: 110px;">
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addMappingVariantRows"><i class="bi bi-plus-lg me-1"></i>Add Rows</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addMappingVariant"><i class="bi bi-plus-lg me-1"></i>Add 1 Variant</button>
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Product to Mapping</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Saved Product Variants</h5>
        <span class="badge bg-secondary"><?= count($items) ?> variants</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Product / Variant</th><th>Category</th><th>HSN</th><th>Qty</th><th>Purchase Rate</th><th>MRP / Sale</th><th>QR / Stock</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No products saved yet.</td></tr>
                <?php else: foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e($item['item_name']) ?></strong><div class="small text-muted"><?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?> · <?= e($item['variant_sku'] ?: $item['vendor_sku']) ?></div></td>
                        <td><?= e($item['category_name'] ?? '—') ?><?php if (!empty($item['subcategory_name'])): ?><div class="small text-muted"><?= e($item['subcategory_name']) ?><?= !empty($item['sub_subcategory_name']) ? ' / ' . e($item['sub_subcategory_name']) : '' ?></div><?php endif; ?></td>
                        <td class="font-monospace"><?= e($item['hsn_code']) ?></td>
                        <td><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?></td>
                        <td>₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td>₹<?= number_format((float)$item['mrp'], 2) ?> / ₹<?= number_format((float)$item['selling_price'], 2) ?></td>
                        <td>
                            <?php if (!empty($item['batch_code'])): ?>
                                <button type="button" class="badge bg-dark border-0 cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$item['qr_generation_id'] ?>" title="Click to view QR codes in this panel">
                                    <i class="bi bi-qr-code me-1"></i><?= e($item['batch_code']) ?>
                                </button>
                                <?php if (!empty($item['stock_added'])): ?><span class="badge bg-success">Stocked</span><?php else: ?><span class="badge bg-warning text-dark">Not stocked</span><?php endif; ?>
                            <?php else: ?><span class="badge bg-secondary">No QR</span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (empty($item['qr_generation_id'])): ?>
                                <form method="POST" action="<?= url('purchases/mapping/draft/item/' . (int)$item['id'] . '/generate-qr') ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="mapping_bill_id" value="<?= $mappingBillId ?>">
                                    <button class="btn btn-sm btn-outline-info" type="submit"><i class="bi bi-qr-code me-1"></i>Generate QR</button>
                                </form>
                            <?php else: ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$item['qr_generation_id'] ?>" title="View QR in this panel">
                                        <i class="bi bi-eye me-1"></i>View QR
                                    </button>
                                    <a class="btn btn-outline-secondary" href="<?= url('production/qr-generations/print/' . (int)$item['qr_generation_id']) ?>" target="_blank" title="Print Labels">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($bill['status'] !== 'MOVED' || $pendingQrCount > 0): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                <?php if ($bill['status'] === 'MOVED'): ?>
                    Linked to the purchase already. You can still generate QR batches for variants that do not have one.
                <?php else: ?>
                    Click <strong>Move to Purchase</strong> to create or link this bill in the Purchases module.
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/generate-all-qr') ?>" onsubmit="return confirm('Generate QR batches for all saved variants that do not already have QR codes?')">
                    <?= CSRF::field() ?>
                    <button class="btn btn-outline-info" type="submit" <?= $pendingQrCount === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-qr-code me-1"></i><?= $pendingQrCount > 0 ? 'Generate QR for ' . $pendingQrCount . ' Variants' : ($items ? 'All Variants Have QR' : 'No Variants to Generate') ?>
                    </button>
                </form>
                <?php if ($bill['status'] !== 'MOVED'): ?>
                    <?php if (!empty($candidatePurchases)): ?>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkExistingPurchaseModal" <?= !$items ? 'disabled' : '' ?>>
                            <i class="bi bi-link-45deg me-1"></i>Link to Existing Purchase
                        </button>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/move') ?>" onsubmit="return confirm('Move this mapping invoice to Purchases? It will create or link the purchase order.')">
                        <?= CSRF::field() ?>
                        <button class="btn btn-success" type="submit" <?= !$items ? 'disabled' : '' ?>><i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($bill['status'] !== 'MOVED' && !empty($candidatePurchases)): ?>
<div class="modal fade" id="linkExistingPurchaseModal" tabindex="-1" aria-labelledby="linkExistingPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/move') ?>">
            <?= CSRF::field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="linkExistingPurchaseModalLabel"><i class="bi bi-link-45deg me-1 text-primary"></i>Link to Existing Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Select an existing purchase order from <strong><?= e($bill['seller_company'] ?: $bill['seller_name']) ?></strong> to attach these mapped products to:</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Existing Purchase Order</label>
                        <select name="target_purchase_id" class="form-select" required>
                            <option value="">-- Choose Purchase Order --</option>
                            <?php foreach ($candidatePurchases as $cand): ?>
                                <option value="<?= (int)$cand['id'] ?>">
                                    <?= e($cand['purchase_number']) ?> — <?= e(date('d M Y', strtotime($cand['purchase_date']))) ?><?= !empty($cand['invoice_number']) ? ' (Inv: ' . e($cand['invoice_number']) . ')' : '' ?> — ₹<?= number_format((float)$cand['total_amount'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Link Products to Selected Purchase</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const body = document.getElementById('mappingVariantRows');
    const addButton = document.getElementById('addMappingVariant');
    const addRowsButton = document.getElementById('addMappingVariantRows');
    const rowsToAddInput = document.getElementById('mappingVariantRowsToAdd');
    if (!body || !addButton) return;
    let nextIndex = 1;
    const template = body.querySelector('tr').outerHTML;
    const addRows = function (count) {
        for (let rowIndex = 0; rowIndex < count; rowIndex++) {
            const row = document.createElement('tbody');
            row.innerHTML = template.replace(/variants\[0\]/g, 'variants[' + nextIndex + ']');
            body.appendChild(row.firstElementChild);
            nextIndex++;
        }
    };
    addButton.addEventListener('click', function () { addRows(1); });
    addRowsButton?.addEventListener('click', function () {
        if (!rowsToAddInput.reportValidity()) return;
        addRows(Number(rowsToAddInput.value));
    });
    body.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-mapping-variant');
        if (!button) return;
        if (body.querySelectorAll('tr').length > 1) button.closest('tr').remove();
    });
    body.addEventListener('change', function (event) {
        if (event.target.classList.contains('mapping-category')) {
            const row = event.target.closest('tr');
            const categoryId = event.target.value;
            const subcategory = row.querySelector('.mapping-subcategory');
            const previousSubcategory = subcategory.value;
            Array.from(subcategory.options).forEach(function (option) {
                if (!option.value) return;
                option.hidden = option.dataset.parent !== categoryId;
                if (option.hidden && option.selected) option.selected = false;
            });
            if (previousSubcategory !== subcategory.value) {
                const subSubcategory = row.querySelector('.mapping-subsubcategory');
                Array.from(subSubcategory.options).forEach(function (option) {
                    if (!option.value) return;
                    option.hidden = option.dataset.parent !== subcategory.value;
                    if (option.hidden && option.selected) option.selected = false;
                });
            }
        } else if (event.target.classList.contains('mapping-subcategory')) {
            const subcategoryId = event.target.value;
            const subSubcategory = event.target.closest('tr').querySelector('.mapping-subsubcategory');
            Array.from(subSubcategory.options).forEach(function (option) {
                if (!option.value) return;
                option.hidden = option.dataset.parent !== subcategoryId;
                if (option.hidden && option.selected) option.selected = false;
            });
        }
    });
})();
</script>

<!-- QR Code Modals for Generated Batches (In-Panel View) -->
<?php if (!empty($items)): foreach ($items as $item): if (!empty($item['qr_generation_id'])): 
    $batchQrItems = $qrItemMap[(int)$item['qr_generation_id']] ?? [];
?>
<div class="modal fade" id="modalQrBatch_<?= (int)$item['qr_generation_id'] ?>" tabindex="-1" aria-labelledby="modalQrBatchLabel_<?= (int)$item['qr_generation_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalQrBatchLabel_<?= (int)$item['qr_generation_id'] ?>">
                    <i class="bi bi-qr-code me-2"></i>QR Batch: <?= e($item['batch_code']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="text-muted small">Product</div>
                                <div class="fw-bold fs-6"><?= e($item['item_name']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Variant &amp; SKU</div>
                                <div class="fw-semibold"><?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?> · <span class="font-monospace text-primary"><?= e($item['variant_sku'] ?: $item['vendor_sku']) ?></span></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">Total Qty</div>
                                <div class="fw-bold"><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">MRP / Selling Price</div>
                                <div class="fw-bold text-success">₹<?= number_format((float)$item['mrp'], 2) ?> / ₹<?= number_format((float)$item['selling_price'], 2) ?></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">Inventory Status</div>
                                <div><?php if (!empty($item['stock_added'])): ?><span class="badge bg-success">Stocked</span><?php else: ?><span class="badge bg-warning text-dark">Not stocked</span><?php endif; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-upc-scan me-1 text-primary"></i>Generated Labels (<?= count($batchQrItems) ?>)</h6>
                <div class="row g-3">
                    <?php if (empty($batchQrItems)): ?>
                        <div class="col-12 text-center text-muted py-3">No QR label records found for this batch.</div>
                    <?php else: foreach ($batchQrItems as $q): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="border rounded p-3 text-center bg-white shadow-sm h-100 d-flex flex-column justify-content-between">
                                <div class="small fw-bold text-truncate text-dark mb-1"><?= e($item['item_name']) ?></div>
                                <div class="small text-muted mb-2"><?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?></div>
                                <div class="d-flex justify-content-center my-2">
                                    <div class="panel-qr-canvas p-1 bg-white border rounded" data-code="<?= e($q['qr_code']) ?>"></div>
                                </div>
                                <div class="font-monospace fw-bold text-primary small mt-1"><?= e($q['qr_code']) ?></div>
                                <div class="small text-muted mt-1">₹<?= number_format((float)$item['selling_price'], 2) ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= url('production/qr-generations/print/' . (int)$item['qr_generation_id']) ?>" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>Print Labels
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; endforeach; endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="modalQrBatch_"]').forEach(function(modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            modalEl.querySelectorAll('.panel-qr-canvas').forEach(function(el) {
                if (el.dataset.rendered === '1') return;
                el.innerHTML = '';
                new QRCode(el, {
                    text: el.dataset.code,
                    width: 90,
                    height: 90,
                    correctLevel: QRCode.CorrectLevel.M
                });
                el.dataset.rendered = '1';
            });
        });
    });
});
</script>
