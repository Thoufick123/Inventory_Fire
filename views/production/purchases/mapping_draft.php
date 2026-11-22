<?php
$mappingBillId = (int)$bill['id'];
$pendingQrCount = 0;
$firstQrId = 0;
foreach ($items as $savedItem) {
    if (empty($savedItem['qr_generation_id'])) {
        $pendingQrCount++;
    } elseif ($firstQrId === 0) {
        $firstQrId = (int)$savedItem['qr_generation_id'];
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
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Items to Mapping</h5>
            <div class="small text-muted mt-1">Add both Third-Party Product Variants and Fabric Rolls with HSN, GST, quantities and rates.</div>
        </div>
        <ul class="nav nav-pills" id="mappingEntryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-1 px-3 fw-semibold" id="tabThirdPartyBtn" data-bs-toggle="pill" data-bs-target="#tabThirdParty" type="button" role="tab">
                    <i class="bi bi-tag me-1"></i>Third-Party Products &amp; Variants
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 px-3 fw-semibold text-primary border" id="tabFabricRollsBtn" data-bs-toggle="pill" data-bs-target="#tabFabricRolls" type="button" role="tab">
                    <i class="bi bi-layers-half me-1"></i>Fabric Rolls (Multiple Rolls)
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="mappingEntryTabContent">
            <!-- PANE 1: Third-Party Products -->
            <div class="tab-pane fade show active" id="tabThirdParty" role="tabpanel">
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftProductForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="entry_type" value="THIRD_PARTY">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Product / Style Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="product_name" required maxlength="255" placeholder="e.g. TESTING Men Casual Shirt">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Style Code</label>
                            <input class="form-control" name="product_code" maxlength="50" placeholder="e.g. SHT-001">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Unit</label>
                            <select class="form-select" name="unit">
                                <option value="PCS">PCS</option><option value="SET">SET</option><option value="BOX">BOX</option><option value="MTR">MTR</option><option value="KGS">KGS</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-sm" style="min-width: 1360px;">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th style="width: 110px; min-width: 110px;">Size</th>
                                    <th style="width: 120px; min-width: 120px;">Color</th>
                                    <th style="width: 160px; min-width: 150px;">SKU</th>
                                    <th style="width: 95px; min-width: 90px;">Qty</th>
                                    <th style="width: 115px; min-width: 110px;">Purchase Rate</th>
                                    <th style="width: 115px; min-width: 110px;">MRP</th>
                                    <th style="width: 115px; min-width: 110px;">Selling</th>
                                    <th style="width: 90px; min-width: 85px;">GST %</th>
                                    <th style="width: 115px; min-width: 110px;">HSN</th>
                                    <th style="width: 260px; min-width: 250px;">Category hierarchy</th>
                                    <th style="width: 50px; min-width: 50px;"></th>
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

            <!-- PANE 2: Fabric Rolls -->
            <div class="tab-pane fade" id="tabFabricRolls" role="tabpanel">
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftRollsForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="entry_type" value="FABRIC_ROLL">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fabric / Material Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="fabric_name" required maxlength="255" placeholder="e.g. Cotton Single Jersey 180 GSM">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Default HSN Code</label>
                            <input class="form-control font-monospace" name="default_hsn" id="defaultRollHsn" value="5208" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Default GST Rate %</label>
                            <input class="form-control" type="number" step="0.01" min="0" max="100" id="defaultRollGst" value="5">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-sm" id="mappingRollsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">Roll Code / #</th>
                                    <th>Color / Shade *</th>
                                    <th style="width: 120px;">Length (Mtr) *</th>
                                    <th style="width: 120px;">Weight (kg)</th>
                                    <th style="width: 130px;">Rate (₹/Mtr) *</th>
                                    <th style="width: 90px;">GST %</th>
                                    <th style="width: 110px;">HSN</th>
                                    <th style="width: 45px;"></th>
                                </tr>
                            </thead>
                            <tbody id="mappingRollRows">
                                <tr class="mapping-roll-row">
                                    <td><input class="form-control form-control-sm font-monospace" name="rolls[0][roll_code]" placeholder="ROLL-001"></td>
                                    <td><input class="form-control form-control-sm" name="rolls[0][color]" placeholder="Navy Blue" required></td>
                                    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="rolls[0][meters]" placeholder="100.00" required></td>
                                    <td><input class="form-control form-control-sm" type="number" step="0.001" min="0" name="rolls[0][weight_kg]" placeholder="22.50"></td>
                                    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="rolls[0][rate]" placeholder="150.00" required></td>
                                    <td><input class="form-control form-control-sm roll-gst-input" type="number" step="0.01" min="0" max="100" name="rolls[0][gst_rate]" value="5"></td>
                                    <td><input class="form-control form-control-sm font-monospace roll-hsn-input" name="rolls[0][hsn_code]" value="5208"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-roll-row" title="Remove roll"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                        <div class="d-flex align-items-end gap-2">
                            <div>
                                <label class="form-label small mb-1" for="mappingRollRowsToAdd">Roll rows to add</label>
                                <input class="form-control form-control-sm" id="mappingRollRowsToAdd" type="number" min="1" max="50" value="1" style="width: 110px;">
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addMappingRollRows"><i class="bi bi-plus-lg me-1"></i>Add Roll Rows</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addMappingRollOne"><i class="bi bi-plus-lg me-1"></i>Add 1 Roll</button>
                        </div>
                        <button class="btn btn-success" type="submit"><i class="bi bi-save me-1"></i>Save Fabric Rolls to Mapping</button>
                    </div>
                </form>
            </div>
        </div>
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
                        <td><strong><?= e($item['item_name']) ?></strong><div class="small text-muted"><span class="badge bg-secondary-subtle text-secondary border font-monospace me-1">VID: #<?= (int)$item['variant_id'] ?></span><?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?> · <?= e($item['variant_sku'] ?: $item['vendor_sku']) ?></div></td>
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
                                <span class="qr-status-badge-<?= (int)$item['qr_generation_id'] ?>"><?php if (!empty($item['stock_added'])): ?><span class="badge bg-success">Stocked</span><?php else: ?><span class="badge bg-warning text-dark">Not stocked</span><?php endif; ?></span>
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
</div>

<!-- ========================================================================= -->
<!-- 3 ACTION OPTIONS CONTAINER (Scenario 2)                                  -->
<!-- ========================================================================= -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Mapping Actions &amp; Workflows</h5>
        <div class="small text-muted mt-1">Execute one of the three primary paths for this mapping entry.</div>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 align-items-stretch">
            <!-- OPTION 1: Move to Purchase -->
            <div class="col-lg-4 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between shadow-sm">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-success mb-0"><i class="bi bi-box-arrow-in-right me-1"></i>Option 1: Move to Purchase</h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Purchases</span>
                        </div>
                        <p class="small text-muted mb-3">
                            Moves this mapping entry directly into the Purchases module. If a purchase bill for this invoice already exists, items attach directly below it. The purchase person can then review and fill the HSN code and GST tax.
                        </p>
                    </div>
                    <?php if ($bill['status'] === 'MOVED'): ?>
                        <div class="alert alert-success py-2 px-3 mb-0 text-center small fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i>Moved to Purchase <?= e($bill['purchase_number'] ?? ('#' . $bill['purchase_id'])) ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/move') ?>" onsubmit="return confirm('Move this mapping invoice to Purchases? It will create or link the purchase order for the purchase team to fill GST & HSN.')" class="m-0">
                            <?= CSRF::field() ?>
                            <button class="btn btn-success w-100 py-2 fw-semibold" type="submit" <?= !$items ? 'disabled' : '' ?>>
                                <i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- OPTION 2: QR Generations & In-Panel Inventory Addition -->
            <div class="col-lg-4 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between shadow-sm">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-info mb-0"><i class="bi bi-qr-code me-1"></i>Option 2: QR Generations</h6>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Labels &amp; Stock</span>
                        </div>
                        <p class="small text-muted mb-3">
                            Generates QR labels storing product category, price, design, and variant ID. View in popup box and click <strong>Add to Inventory</strong> to credit stock directly to your chosen warehouse/concern.
                        </p>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/generate-all-qr') ?>" onsubmit="return confirm('Generate QR batches for all saved variants that do not already have QR codes?')" class="m-0">
                            <?= CSRF::field() ?>
                            <button class="btn btn-outline-info w-100 py-2 fw-semibold" type="submit" <?= $pendingQrCount === 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-qr-code me-1"></i><?= $pendingQrCount > 0 ? 'Generate QR for ' . $pendingQrCount . ' Variants' : ($items ? 'All Variants Have QR' : 'No Variants to Generate') ?>
                            </button>
                        </form>
                        <?php if (!empty($firstQrId)): ?>
                            <button type="button" class="btn btn-dark btn-sm w-100 py-1" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$firstQrId ?>">
                                <i class="bi bi-eye me-1"></i>View QRs &amp; Stock in Popup
                            </button>
                            <?php if (count($items) > 1): ?>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php foreach ($items as $v): if (!empty($v['qr_generation_id'])): ?>
                                        <button type="button" class="btn btn-outline-dark btn-sm py-0 px-2 flex-grow-1" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$v['qr_generation_id'] ?>" title="Open <?= e($v['item_name']) ?> QR modal">
                                            <i class="bi bi-qr-code me-1"></i><?= e(trim(($v['color'] ?? '') . ' ' . ($v['size'] ?? ''))) ?: e($v['item_name']) ?> (VID: #<?= (int)$v['variant_id'] ?>)
                                        </button>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- OPTION 3: Waiting for Purchase Bill / Link to Bill -->
            <div class="col-lg-4 col-md-12">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between shadow-sm">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-warning mb-0"><i class="bi bi-clock-history me-1"></i>Option 3: Purchase Bill Status</h6>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Mapping Status</span>
                        </div>
                        <p class="small text-muted mb-3">
                            <?php if ($bill['status'] === 'MOVED'): ?>
                                <span class="badge bg-success mb-1">Status: MOVED</span><br>
                                Linked to Purchase Order <strong><?= e($bill['purchase_number'] ?? ('#' . $bill['purchase_id'])) ?></strong>.
                            <?php elseif (!empty($bill['purchase_id'])): ?>
                                <span class="badge bg-info text-dark mb-1">Status: MATCHED</span><br>
                                Matched with existing Purchase Order <strong><?= e($bill['purchase_number'] ?? ('#' . $bill['purchase_id'])) ?></strong>.
                            <?php else: ?>
                                <span class="badge bg-warning text-dark mb-1">Status: WAITING FOR PURCHASE BILL</span><br>
                                Pre-mapped invoice waiting for the Purchases bill. As soon as the Purchases team records invoice <strong class="font-monospace"><?= e($bill['invoice_number']) ?></strong>, it will automatically link.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($bill['status'] !== 'MOVED' && !empty($candidatePurchases)): ?>
                        <button type="button" class="btn btn-outline-primary w-100 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#linkExistingPurchaseModal" <?= !$items ? 'disabled' : '' ?>>
                            <i class="bi bi-link-45deg me-1"></i>Link to Existing Purchase Bill
                        </button>
                    <?php else: ?>
                        <div class="bg-white border rounded p-2 text-center small text-muted">
                            <i class="bi bi-info-circle me-1"></i><?= $bill['status'] === 'MOVED' ? 'Synchronization complete' : 'Automatic linking active on invoice match' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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

    function updateRowCascade(row) {
        const catSelect = row.querySelector('.mapping-category');
        const subcatSelect = row.querySelector('.mapping-subcategory');
        const subSubcatSelect = row.querySelector('.mapping-subsubcategory');
        if (!catSelect || !subcatSelect || !subSubcatSelect) return;

        const categoryId = String(catSelect.value || '');
        const subcategoryId = String(subcatSelect.value || '');

        // Subcategory options: only show if belongs to chosen category
        Array.from(subcatSelect.options).forEach(function (option) {
            if (!option.value) return;
            const matches = Boolean(categoryId && option.dataset.parent === categoryId);
            option.hidden = !matches;
            if (!matches && option.selected) option.selected = false;
        });

        // Sub-subcategory options: only show if belongs to chosen subcategory (and subcategory is chosen)
        Array.from(subSubcatSelect.options).forEach(function (option) {
            if (!option.value) return;
            const matches = Boolean(subcategoryId && option.dataset.parent === subcategoryId);
            option.hidden = !matches;
            if (!matches && option.selected) option.selected = false;
        });
    }

    let nextIndex = 1;
    const template = body.querySelector('tr').outerHTML;
    const addRows = function (count) {
        for (let rowIndex = 0; rowIndex < count; rowIndex++) {
            const rowWrapper = document.createElement('tbody');
            rowWrapper.innerHTML = template.replace(/variants\[0\]/g, 'variants[' + nextIndex + ']');
            const newRow = rowWrapper.firstElementChild;
            body.appendChild(newRow);
            updateRowCascade(newRow);
            nextIndex++;
        }
    };

    // Initial cascade on existing rows
    body.querySelectorAll('tr').forEach(updateRowCascade);

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
        const row = event.target.closest('tr');
        if (!row) return;

        if (event.target.classList.contains('mapping-category')) {
            const subcat = row.querySelector('.mapping-subcategory');
            const subSubcat = row.querySelector('.mapping-subsubcategory');
            if (subcat) subcat.value = '';
            if (subSubcat) subSubcat.value = '';
            updateRowCascade(row);
        } else if (event.target.classList.contains('mapping-subcategory')) {
            const subSubcat = row.querySelector('.mapping-subsubcategory');
            if (subSubcat) subSubcat.value = '';
            updateRowCascade(row);
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
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <h5 class="modal-title mb-0" id="modalQrBatchLabel_<?= (int)$item['qr_generation_id'] ?>">
                        <i class="bi bi-qr-code me-2"></i>QR Batch: <?= e($item['batch_code']) ?>
                    </h5>
                    <span class="badge bg-primary fs-6"><i class="bi bi-tag-fill me-1"></i>Variant ID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (count($items) > 1): ?>
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="small fw-bold text-muted mb-2 text-uppercase d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span><i class="bi bi-collection me-1 text-primary"></i>Switch Variant QRs (<?= count($items) ?> Total Variants):</span>
                            <span class="text-primary fw-semibold"><i class="bi bi-eye me-1"></i>Currently viewing: <?= e($item['item_name']) ?> (VID: #<?= (int)($item['variant_id'] ?? 0) ?>)</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($items as $vItem): if (!empty($vItem['qr_generation_id'])): 
                                $isActiveVariant = ((int)$vItem['id'] === (int)$item['id']);
                                $vCount = count($qrItemMap[(int)$vItem['qr_generation_id']] ?? []);
                            ?>
                                <button type="button" 
                                        class="btn btn-sm <?= $isActiveVariant ? 'btn-primary' : 'btn-outline-secondary' ?>" 
                                        data-bs-target="#modalQrBatch_<?= (int)$vItem['qr_generation_id'] ?>" 
                                        data-bs-toggle="modal">
                                    <i class="bi bi-box-seam me-1"></i><?= e($vItem['item_name']) ?>
                                    <span class="opacity-75">(<?= e(trim(($vItem['color'] ?? '') . ' ' . ($vItem['size'] ?? ''))) ?>)</span>
                                    <span class="badge bg-light text-dark ms-1">VID: #<?= (int)($vItem['variant_id'] ?? 0) ?></span>
                                    <span class="badge <?= $isActiveVariant ? 'bg-dark text-white' : 'bg-secondary text-white' ?> ms-1"><?= $vCount ?> QRs</span>
                                </button>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="text-muted small">Product</div>
                                <div class="fw-bold fs-6"><?= e($item['item_name']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Variant &amp; SKU</div>
                                <div class="fw-semibold">
                                    <?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?> · 
                                    <span class="font-monospace text-primary"><?= e($item['variant_sku'] ?: $item['vendor_sku']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">VID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                                </div>
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
                                <div id="modalStockStatus_<?= (int)$item['qr_generation_id'] ?>">
                                    <?php if (!empty($item['stock_added'])): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Not stocked</span>
                                    <?php endif; ?>
                                </div>
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
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-truncate text-dark"><?= e($item['item_name']) ?></span>
                                    <span class="badge bg-light text-secondary border" style="font-size: 10px;">VID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                                </div>
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
            <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (empty($item['stock_added'])): ?>
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-light"><i class="bi bi-building me-1"></i>Stock to:</span>
                            <select class="form-select" id="whSelect_<?= (int)$item['qr_generation_id'] ?>">
                                <?php foreach (($warehouses ?? []) as $wh): ?>
                                    <option value="<?= (int)$wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['concern_name']) ? ' (' . e($wh['concern_name']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success btn-add-inventory-draft"
                                data-generation-id="<?= (int)$item['qr_generation_id'] ?>"
                                data-mapping-bill-id="<?= $mappingBillId ?>">
                            <i class="bi bi-box-seam me-1"></i>Add to Inventory
                        </button>
                    <?php else: ?>
                        <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Stock already added to warehouse</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('production/qr-generations/print/' . (int)$item['qr_generation_id']) ?>" target="_blank" class="btn btn-primary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print Labels
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; endforeach; endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. QR Code canvas rendering on modal open
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

    // 2. Auto-open QR modal if 'open_qr' parameter is present in URL
    const urlParams = new URLSearchParams(window.location.search);
    const openQrId = urlParams.get('open_qr');
    if (openQrId) {
        const modalTarget = document.getElementById('modalQrBatch_' + openQrId);
        if (modalTarget) {
            const modalInstance = new bootstrap.Modal(modalTarget);
            modalInstance.show();
        }
    }

    // 3. In-panel AJAX Add to Inventory
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-add-inventory-draft');
        if (!btn) return;

        const genId = btn.getAttribute('data-generation-id');
        const mappingBillId = btn.getAttribute('data-mapping-bill-id');
        const whSelect = document.getElementById('whSelect_' + genId);
        const whId = whSelect ? whSelect.value : '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Adding...';

        const formData = new FormData();
        formData.append('generation_id', genId);
        formData.append('warehouse_id', whId);
        formData.append('mapping_bill_id', mappingBillId);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) formData.append('csrf_token', csrfToken);

        fetch('<?= url("api/purchases/mapping/add-to-stock") ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                btn.className = 'btn btn-sm btn-success disabled';
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Stock Added';
                const statusEl = document.getElementById('modalStockStatus_' + genId);
                if (statusEl) {
                    statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span>';
                }
                document.querySelectorAll('.qr-status-badge-' + genId).forEach(function(el) {
                    el.innerHTML = '<span class="badge bg-success">Stocked</span>';
                });
                alert('Success: ' + (data.message || 'Stock added to inventory!'));
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add to Inventory';
                alert('Error: ' + (data.message || 'Could not add stock.'));
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add to Inventory';
            alert('Server error: ' + err.message);
        });
    });

    // 4. AJAX Save Product and Variants Form
    const draftProductForm = document.getElementById('mappingDraftProductForm');
    if (draftProductForm) {
        draftProductForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate category hierarchy on all variant rows
            const variantRows = draftProductForm.querySelectorAll('#mappingVariantRows tr');
            for (let i = 0; i < variantRows.length; i++) {
                const vRow = variantRows[i];
                const catVal = vRow.querySelector('.mapping-category')?.value;
                const subcatVal = vRow.querySelector('.mapping-subcategory')?.value;
                const subSubcatVal = vRow.querySelector('.mapping-subsubcategory')?.value;
                if (!catVal) {
                    alert('Validation Error: Please select a Category for variant row #' + (i + 1) + '.');
                    vRow.querySelector('.mapping-category')?.focus();
                    return;
                }
                if (subSubcatVal && !subcatVal) {
                    alert('Validation Error: Please select a Subcategory for variant row #' + (i + 1) + ' before selecting a Sub-subcategory.');
                    vRow.querySelector('.mapping-subcategory')?.focus();
                    return;
                }
            }

            const submitBtn = draftProductForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving Product...';
            }
            const formData = new FormData(draftProductForm);
            fetch(draftProductForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Success: ' + data.message);
                    window.location.reload();
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Product to Mapping';
                    }
                    alert('Error: ' + (data.message || 'Failed to save product.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Product to Mapping';
                }
                alert('Server error: ' + err.message);
            });
        });
    }

    // 4b. Fabric Roll Rows Dynamic Addition & AJAX Save
    const rollBody = document.getElementById('mappingRollRows');
    const addRollOneBtn = document.getElementById('addMappingRollOne');
    const addRollRowsBtn = document.getElementById('addMappingRollRows');
    const rollRowsToAddInput = document.getElementById('mappingRollRowsToAdd');
    const defaultRollHsn = document.getElementById('defaultRollHsn');
    const defaultRollGst = document.getElementById('defaultRollGst');

    if (rollBody && addRollOneBtn) {
        let nextRollIndex = 1;
        const addRolls = function(count) {
            const hsnVal = defaultRollHsn ? defaultRollHsn.value : '5208';
            const gstVal = defaultRollGst ? defaultRollGst.value : '5';
            for (let i = 0; i < count; i++) {
                const tr = document.createElement('tr');
                tr.className = 'mapping-roll-row';
                tr.innerHTML = `
                    <td><input class="form-control form-control-sm font-monospace" name="rolls[${nextRollIndex}][roll_code]" placeholder="ROLL-${String(nextRollIndex + 1).padStart(3, '0')}"></td>
                    <td><input class="form-control form-control-sm" name="rolls[${nextRollIndex}][color]" placeholder="Navy Blue" required></td>
                    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="rolls[${nextRollIndex}][meters]" placeholder="100.00" required></td>
                    <td><input class="form-control form-control-sm" type="number" step="0.001" min="0" name="rolls[${nextRollIndex}][weight_kg]" placeholder="22.50"></td>
                    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" name="rolls[${nextRollIndex}][rate]" placeholder="150.00" required></td>
                    <td><input class="form-control form-control-sm roll-gst-input" type="number" step="0.01" min="0" max="100" name="rolls[${nextRollIndex}][gst_rate]" value="${gstVal}"></td>
                    <td><input class="form-control form-control-sm font-monospace roll-hsn-input" name="rolls[${nextRollIndex}][hsn_code]" value="${hsnVal}"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-roll-row"><i class="bi bi-x-lg"></i></button></td>
                `;
                rollBody.appendChild(tr);
                nextRollIndex++;
            }
        };

        addRollOneBtn.addEventListener('click', function() { addRolls(1); });
        addRollRowsBtn?.addEventListener('click', function() {
            const n = parseInt(rollRowsToAddInput.value, 10) || 1;
            addRolls(Math.max(1, Math.min(50, n)));
        });
        rollBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-roll-row');
            if (!btn) return;
            if (rollBody.querySelectorAll('tr').length > 1) {
                btn.closest('tr').remove();
            }
        });
    }

    const draftRollsForm = document.getElementById('mappingDraftRollsForm');
    if (draftRollsForm) {
        draftRollsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = draftRollsForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving Rolls...';
            }
            const formData = new FormData(draftRollsForm);
            fetch(draftRollsForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Success: ' + data.message);
                    window.location.reload();
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Fabric Rolls to Mapping';
                    }
                    alert('Error: ' + (data.message || 'Failed to save rolls.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Fabric Rolls to Mapping';
                }
                alert('Server error: ' + err.message);
            });
        });
    }

    // 5. AJAX Move to Purchase Handlers
    document.querySelectorAll('form[action$="/move"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Move this mapping invoice to Purchases?')) return;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Moving...';
            }
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Success: ' + data.message);
                    window.location.href = data.redirect_url || '<?= url("purchases") ?>';
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase';
                    }
                    alert('Error: ' + (data.message || 'Failed to move to purchases.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase';
                }
                alert('Server error: ' + err.message);
            });
        });
    });

    // 6. AJAX Generate All QRs
    document.querySelectorAll('form[action$="/generate-all-qr"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Generate QR batches for all saved variants?')) return;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating...';
            }
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Success: ' + data.message);
                    window.location.reload();
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-qr-code me-1"></i>Generate QR';
                    }
                    alert('Error: ' + (data.message || 'QR generation failed.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-qr-code me-1"></i>Generate QR';
                }
                alert('Server error: ' + err.message);
            });
        });
    });
});
</script>
