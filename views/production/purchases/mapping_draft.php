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
$variantRow = static function (int $pIndex = 0, int $vIndex = 0) use ($categories, $subcategories, $subSubcategories, $hsnList): void { ?>
    <tr class="mapping-variant-row">
        <td><input class="form-control form-control-sm" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][size]" placeholder="Size"></td>
        <td><input class="form-control form-control-sm" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][color]" placeholder="Color"></td>
        <td><input class="form-control form-control-sm map-qty" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][quantity]" type="text" inputmode="numeric" required placeholder="Qty" autocomplete="off"></td>
        <td><input class="form-control form-control-sm map-rate" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][unit_price]" type="text" inputmode="decimal" required placeholder="Rate" autocomplete="off"></td>
        <td><input class="form-control form-control-sm map-mrp" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][mrp]" type="text" inputmode="decimal" required placeholder="MRP" autocomplete="off"></td>
        <td><input class="form-control form-control-sm map-sp" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][selling_price]" type="text" inputmode="decimal" required placeholder="Sale" autocomplete="off"></td>
        <td><input class="form-control form-control-sm map-gst" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][gst_rate]" type="text" inputmode="decimal" value="0" required placeholder="GST %" autocomplete="off"></td>
        <td>
            <select class="form-select form-select-sm mapping-hsn-select font-monospace" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][hsn_code]">
                <option value="">Select HSN</option>
                <?php foreach (($hsnList ?? []) as $hsnItem): ?>
                    <option value="<?= e($hsnItem['hsn_code']) ?>" data-gst="<?= (float)($hsnItem['gst_rate'] ?? 0) ?>">
                        <?= e($hsnItem['hsn_code']) ?><?= !empty($hsnItem['description']) ? ' - ' . e(mb_strimwidth($hsnItem['description'], 0, 24, '...')) : '' ?><?= isset($hsnItem['gst_rate']) && $hsnItem['gst_rate'] !== null ? ' (' . (float)$hsnItem['gst_rate'] . '%)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm mapping-category" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][category_id]" required>
                <option value="">Category *</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm mt-1 mapping-subcategory" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][subcategory_id]">
                <option value="">Subcategory (Optional)</option>
                <?php foreach ($subcategories as $subcategory): ?>
                    <option value="<?= (int)$subcategory['id'] ?>" data-parent="<?= (int)$subcategory['category_id'] ?>"><?= e($subcategory['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm mt-1 mapping-subsubcategory" name="products[<?= $pIndex ?>][variants][<?= $vIndex ?>][sub_subcategory_id]">
                <option value="">Sub-subcategory (Optional)</option>
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

<?php if (!$viewOnly && $bill['status'] === 'READY'): ?>
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
<?php elseif (!$viewOnly && $bill['status'] === 'MOVED'): ?>
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

<?php if ($bill['status'] === 'DRAFT' && !$viewOnly): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Items to Mapping</h5>
            <div class="small text-muted mt-1">Add both Third-Party Product Variants and Fabric Rolls with HSN, GST, quantities and rates.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
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
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btnHeaderAddProduct">
                    <i class="bi bi-plus-circle me-1"></i>+ Add Another Product
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="tab-content" id="mappingEntryTabContent">
            <!-- PANE 1: Third-Party Products -->
            <div class="tab-pane fade show active" id="tabThirdParty" role="tabpanel">
                <div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 mb-3" style="background-color: #f8faff;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                        <div class="small text-muted">
                            <strong>Multi-Product Inward:</strong> You can add multiple products (e.g. <em>Shirt</em>, <em>Pant</em>, etc.) below before saving. Click <strong>"+ Add Another Product"</strong> to add more products to this invoice.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btnBannerAddProduct">
                        <i class="bi bi-plus-circle me-1"></i>+ Add Another Product (e.g. Pant)
                    </button>
                </div>
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftProductForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="entry_type" value="THIRD_PARTY">

                    <div id="tpProductsContainer">
                        <!-- Product Card 0 -->
                        <div class="tp-product-card card border mb-4 shadow-sm" data-product-index="0" style="background-color: #fafbfc;">
                            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 tp-product-badge">Product #1</span>
                                    <strong class="text-dark tp-product-title-display">New Product</strong>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product" style="display: none;" title="Remove this product">
                                        <i class="bi bi-trash me-1"></i>Remove Product
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3 bg-white">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-10">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-semibold mb-0">
                                                Product / Style Name <span class="text-danger">*</span>
                                            </label>
                                            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                                <i class="bi bi-plus-circle me-1"></i>Auto-Created if New
                                            </span>
                                        </div>
                                        <input class="form-control tp-product-name-input" name="products[0][product_name]" list="tpProductSuggestions" required maxlength="255" placeholder="e.g. Shirt, Pant..." autocomplete="off">
                                        <datalist id="tpProductSuggestions">
                                            <?php foreach (($existingProducts ?? []) as $ep): ?>
                                                <option value="<?= e($ep['name']) ?>"><?= !empty($ep['code']) ? '(' . e($ep['code']) . ')' : '' ?></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                        <div class="form-text small text-muted">
                                            <i class="bi bi-info-circle me-1"></i>Type any new product name or pick an existing one from the catalog.
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Unit</label>
                                        <select class="form-select" name="products[0][unit]">
                                            <option value="PCS">PCS</option><option value="SET">SET</option><option value="BOX">BOX</option><option value="MTR">MTR</option><option value="KGS">KGS</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive mb-2">
                                    <table class="table table-bordered align-middle table-sm mb-1" style="min-width: 1200px;">
                                        <thead class="table-light small text-uppercase text-muted">
                                            <tr>
                                                <th style="width: 110px; min-width: 110px;">Size</th>
                                                <th style="width: 120px; min-width: 120px;">Color</th>
                                                <th style="width: 95px; min-width: 90px;">Qty *</th>
                                                <th style="width: 115px; min-width: 110px;">Purchase Rate *</th>
                                                <th style="width: 115px; min-width: 110px;">MRP *</th>
                                                <th style="width: 115px; min-width: 110px;">Selling *</th>
                                                <th style="width: 90px; min-width: 85px;">GST %</th>
                                                <th style="width: 220px; min-width: 200px;">HSN Code</th>
                                                <th style="width: 260px; min-width: 250px;">Category hierarchy *</th>
                                                <th style="width: 50px; min-width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="tp-variant-rows">
                                            <?php $variantRow(0, 0); ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-add-variant-row">
                                        <i class="bi bi-plus-lg me-1"></i>Add Variant Row
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-outline-primary fw-semibold" id="btnAddAnotherProduct">
                            <i class="bi bi-plus-circle me-1"></i>+ Add Another Product (e.g. Pant)
                        </button>
                        <button class="btn btn-success btn-lg px-4 fw-semibold" type="submit" id="btnSaveAllProducts">
                            <i class="bi bi-save me-1"></i>Save All Products to Mapping
                        </button>
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" for="fabricNameInput">
                                    Fabric / Material Name <span class="text-danger">*</span>
                                </label>
                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                    <i class="bi bi-plus-circle me-1"></i>New Roll Product Auto-Created
                                </span>
                            </div>
                            <input class="form-control" name="fabric_name" id="fabricNameInput" list="rollProductSuggestions" required maxlength="255" placeholder="e.g. Cotton Single Jersey 180 GSM, panda..." autocomplete="off">
                            <datalist id="rollProductSuggestions">
                                <?php foreach (($existingRollProducts ?? []) as $rp): ?>
                                    <option value="<?= e($rp['name']) ?>"><?= !empty($rp['code']) ? '(' . e($rp['code']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                                <option value="100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)">
                                <option value="100% COTTON 24S SINGLE JERSEY (180 GSM)">
                                <option value="95% COTTON 5% SPANDEX/LYCRA (190 GSM)">
                                <option value="100% COTTON FRENCH TERRY (240 GSM)">
                                <option value="COTTON-POLYESTER BLEND 60/40 (180 GSM)">
                                <option value="RAYON / VISCOSE PRINTED FABRIC">
                            </datalist>
                            <div class="form-text small text-muted">
                                <i class="bi bi-info-circle me-1"></i>Type any new fabric name to <strong>create a new roll product</strong> in the catalog, or select an existing one.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Default HSN Code</label>
                            <select class="form-select font-monospace" name="default_hsn" id="defaultRollHsn">
                                <option value="">Select Default HSN</option>
                                <?php foreach (($hsnList ?? []) as $hsnItem): ?>
                                    <option value="<?= e($hsnItem['hsn_code']) ?>" data-gst="<?= (float)($hsnItem['gst_rate'] ?? 0) ?>" <?= $hsnItem['hsn_code'] == '5208' ? 'selected' : '' ?>>
                                        <?= e($hsnItem['hsn_code']) ?><?= !empty($hsnItem['description']) ? ' - ' . e(mb_strimwidth($hsnItem['description'], 0, 20, '...')) : '' ?><?= isset($hsnItem['gst_rate']) && $hsnItem['gst_rate'] !== null ? ' (' . (float)$hsnItem['gst_rate'] . '%)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Default GST Rate %</label>
                            <input class="form-control roll-num-dec" type="text" inputmode="decimal" autocomplete="off" id="defaultRollGst" value="5">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-sm" id="mappingRollsTable" style="min-width: 860px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 160px;">Roll Code / #</th>
                                    <th>Color / Shade *</th>
                                    <th style="width: 140px;">Weight (kg) *</th>
                                    <th style="width: 140px;">Rate (₹/kg) *</th>
                                    <th style="width: 90px;">GST %</th>
                                    <th style="width: 200px;">HSN Code</th>
                                    <th style="width: 45px;"></th>
                                </tr>
                            </thead>
                            <tbody id="mappingRollRows">
                                <tr class="mapping-roll-row">
                                    <td><input class="form-control form-control-sm font-monospace" name="rolls[0][roll_code]" placeholder="ROLL-001"></td>
                                    <td><input class="form-control form-control-sm" name="rolls[0][color]" placeholder="Navy Blue" required></td>
                                    <td><input class="form-control form-control-sm roll-num-dec roll-weight" type="text" inputmode="decimal" autocomplete="off" name="rolls[0][weight_kg]" placeholder="22.50" required></td>
                                    <td><input class="form-control form-control-sm roll-num-dec roll-rate" type="text" inputmode="decimal" autocomplete="off" name="rolls[0][rate]" placeholder="150.00" required></td>
                                    <td><input class="form-control form-control-sm roll-gst-input roll-num-dec" type="text" inputmode="decimal" autocomplete="off" name="rolls[0][gst_rate]" value="5"></td>
                                    <td>
                                        <select class="form-select form-select-sm font-monospace roll-hsn-select" name="rolls[0][hsn_code]">
                                            <?php foreach (($hsnList ?? []) as $hsnItem): ?>
                                                <option value="<?= e($hsnItem['hsn_code']) ?>" data-gst="<?= (float)($hsnItem['gst_rate'] ?? 0) ?>" <?= $hsnItem['hsn_code'] == '5208' ? 'selected' : '' ?>>
                                                    <?= e($hsnItem['hsn_code']) ?><?= !empty($hsnItem['description']) ? ' - ' . e(mb_strimwidth($hsnItem['description'], 0, 18, '...')) : '' ?><?= isset($hsnItem['gst_rate']) && $hsnItem['gst_rate'] !== null ? ' (' . (float)$hsnItem['gst_rate'] . '%)' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-roll-row" title="Remove roll"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                        <div class="d-flex align-items-end gap-2">
                            <div>
                                <label class="form-label small mb-1" for="mappingRollRowsToAdd">Roll rows to add</label>
                                <input class="form-control form-control-sm roll-num-int" id="mappingRollRowsToAdd" type="text" inputmode="numeric" autocomplete="off" value="1" style="width: 110px;">
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
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Saved Product Variants</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?= count($items) ?> variants</span>
            <?php if ($bill['status'] === 'DRAFT' && !$viewOnly): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('productNameInput')?.focus(); window.scrollTo({top: 0, behavior: 'smooth'});">
                    <i class="bi bi-plus-lg me-1"></i>+ Add Another Product
                </button>
            <?php endif; ?>
        </div>
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
                            <?php if ($viewOnly): ?>
                                <span class="text-muted small">View only</span>
                            <?php else: ?>
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
                                <?php if ($bill['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'delete')): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/item/' . (int)$item['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this product and its unposted QR batch? This cannot be undone.');">
                                        <?= CSRF::field() ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete product from mapping invoice">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
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
<?php if (!$viewOnly): ?>
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
                        <a href="<?= url('production/qr-generations/create?tab=third_party&purchase_id=map_' . $mappingBillId) ?>" class="btn btn-outline-secondary btn-sm w-100 py-1">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in QR Batch Generator
                        </a>
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
    <?php endif; ?>

    <?php if (!$viewOnly && $bill['status'] !== 'MOVED' && !empty($candidatePurchases)): ?>
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
    const container = document.getElementById('tpProductsContainer');
    const form = document.getElementById('mappingDraftProductForm');
    const addProductBtn = document.getElementById('btnAddAnotherProduct');
    const headerAddBtn = document.getElementById('btnHeaderAddProduct');
    const bannerAddBtn = document.getElementById('btnBannerAddProduct');

    if (!container || !form) return;

    // Helper: update category cascade on any row
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

        // Sub-subcategory options: only show if belongs to chosen subcategory
        Array.from(subSubcatSelect.options).forEach(function (option) {
            if (!option.value) return;
            const matches = Boolean(subcategoryId && option.dataset.parent === subcategoryId);
            option.hidden = !matches;
            if (!matches && option.selected) option.selected = false;
        });
    }

    // Initialize cascade on initial rows
    container.querySelectorAll('.mapping-variant-row').forEach(updateRowCascade);

    // Live update of product title preview in header & numeric masks
    container.addEventListener('input', function (e) {
        if (e.target.classList.contains('tp-product-name-input')) {
            const card = e.target.closest('.tp-product-card');
            const titleDisplay = card?.querySelector('.tp-product-title-display');
            if (titleDisplay) {
                titleDisplay.textContent = e.target.value.trim() || 'New Product';
            }
        } else if (e.target.classList.contains('map-qty')) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        } else if (e.target.classList.contains('map-rate') || e.target.classList.contains('map-mrp') || e.target.classList.contains('map-sp') || e.target.classList.contains('map-gst')) {
            e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
        }
    });

    // Cascades & HSN auto-GST
    container.addEventListener('change', function (e) {
        const row = e.target.closest('tr');
        if (!row) return;

        if (e.target.classList.contains('mapping-category')) {
            const subcat = row.querySelector('.mapping-subcategory');
            const subSubcat = row.querySelector('.mapping-subsubcategory');
            if (subcat) subcat.value = '';
            if (subSubcat) subSubcat.value = '';
            updateRowCascade(row);
        } else if (e.target.classList.contains('mapping-subcategory')) {
            const subSubcat = row.querySelector('.mapping-subsubcategory');
            if (subSubcat) subSubcat.value = '';
            updateRowCascade(row);
        } else if (e.target.classList.contains('mapping-hsn-select')) {
            const opt = e.target.options[e.target.selectedIndex];
            const gst = opt ? opt.getAttribute('data-gst') : null;
            if (gst !== null && gst !== undefined && gst !== '') {
                const gstInput = row.querySelector('.map-gst');
                if (gstInput) gstInput.value = gst;
            }
        }
    });

    // Handle adding variant rows inside any product card
    container.addEventListener('click', function (e) {
        const addVarBtn = e.target.closest('.btn-add-variant-row');
        if (addVarBtn) {
            const card = addVarBtn.closest('.tp-product-card');
            const tbody = card?.querySelector('.tp-variant-rows');
            if (!card || !tbody) return;

            const pIdx = card.getAttribute('data-product-index') || '0';
            const vRows = tbody.querySelectorAll('tr');
            const nextVIdx = vRows.length;
            const firstRow = vRows[0];
            if (!firstRow) return;

            const tempTbody = document.createElement('tbody');
            tempTbody.innerHTML = firstRow.outerHTML.replace(
                new RegExp('products\\[' + pIdx + '\\]\\[variants\\]\\[\\d+\\]', 'g'),
                'products[' + pIdx + '][variants][' + nextVIdx + ']'
            );
            const newRow = tempTbody.firstElementChild;
            newRow.querySelectorAll('input').forEach(function (inp) {
                if (inp.classList.contains('map-gst')) inp.value = '0';
                else inp.value = '';
            });
            tbody.appendChild(newRow);
            updateRowCascade(newRow);
            return;
        }

        // Handle removing a variant row
        const removeVarBtn = e.target.closest('.remove-mapping-variant');
        if (removeVarBtn) {
            const tbody = removeVarBtn.closest('.tp-variant-rows');
            if (tbody && tbody.querySelectorAll('tr').length > 1) {
                removeVarBtn.closest('tr').remove();
            }
            return;
        }

        // Handle removing an entire product card
        const removeProdBtn = e.target.closest('.btn-remove-product');
        if (removeProdBtn) {
            const card = removeProdBtn.closest('.tp-product-card');
            const allCards = container.querySelectorAll('.tp-product-card');
            if (card && allCards.length > 1) {
                card.remove();
                renumberProductCards();
            }
            return;
        }
    });

    // Renumber product badges and adjust remove buttons
    function renumberProductCards() {
        const cards = container.querySelectorAll('.tp-product-card');
        cards.forEach(function (c, idx) {
            const badge = c.querySelector('.tp-product-badge');
            if (badge) badge.textContent = 'Product #' + (idx + 1);
            const removeBtn = c.querySelector('.btn-remove-product');
            if (removeBtn) {
                removeBtn.style.display = (cards.length > 1) ? 'inline-block' : 'none';
            }
        });
    }

    // Add another product card
    let productCounter = 1;
    function addAnotherProductCard() {
        const firstCard = container.querySelector('.tp-product-card');
        if (!firstCard) return;

        const newPIdx = productCounter++;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = firstCard.outerHTML.replace(/products\[\d+\]/g, 'products[' + newPIdx + ']');
        const newCard = tempDiv.firstElementChild;
        newCard.setAttribute('data-product-index', String(newPIdx));

        const nameInput = newCard.querySelector('.tp-product-name-input');
        if (nameInput) nameInput.value = '';
        const titleDisplay = newCard.querySelector('.tp-product-title-display');
        if (titleDisplay) titleDisplay.textContent = 'New Product';

        const tbody = newCard.querySelector('.tp-variant-rows');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            for (let i = 1; i < rows.length; i++) rows[i].remove();
            const r0 = tbody.querySelector('tr');
            if (r0) {
                r0.innerHTML = r0.innerHTML.replace(
                    /products\[\d+\]\[variants\]\[\d+\]/g,
                    'products[' + newPIdx + '][variants][0]'
                );
                r0.querySelectorAll('input').forEach(function (inp) {
                    if (inp.classList.contains('map-gst')) inp.value = '0';
                    else inp.value = '';
                });
                updateRowCascade(r0);
            }
        }

        container.appendChild(newCard);
        renumberProductCards();
        newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        nameInput?.focus();
    }

    addProductBtn?.addEventListener('click', addAnotherProductCard);
    headerAddBtn?.addEventListener('click', addAnotherProductCard);
    bannerAddBtn?.addEventListener('click', addAnotherProductCard);

    // Form submit validation & AJAX
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const cards = container.querySelectorAll('.tp-product-card');
        if (cards.length === 0) {
            alert('Please add at least one product.');
            return;
        }

        for (let p = 0; p < cards.length; p++) {
            const card = cards[p];
            const pName = card.querySelector('.tp-product-name-input')?.value?.trim();
            if (!pName) {
                alert('Validation Error: Please enter a Product Name for Product #' + (p + 1) + '.');
                card.querySelector('.tp-product-name-input')?.focus();
                return;
            }

            const variantRows = card.querySelectorAll('.tp-variant-rows tr');
            if (variantRows.length === 0) {
                alert('Validation Error: Please add at least one variant for Product #' + (p + 1) + ' (' + pName + ').');
                return;
            }

            for (let i = 0; i < variantRows.length; i++) {
                const vRow = variantRows[i];
                const catVal = vRow.querySelector('.mapping-category')?.value;
                if (!catVal) {
                    alert('Validation Error: Please select a Category for Product #' + (p + 1) + ' (' + pName + ') on variant row #' + (i + 1) + '.');
                    vRow.querySelector('.mapping-category')?.focus();
                    return;
                }

                const qVal = parseFloat(vRow.querySelector('.map-qty')?.value || '0') || 0;
                const mrpVal = parseFloat(vRow.querySelector('.map-mrp')?.value || '0') || 0;
                const spVal = parseFloat(vRow.querySelector('.map-sp')?.value || '0') || 0;
                const gstVal = parseFloat(vRow.querySelector('.map-gst')?.value || '0') || 0;

                if (qVal <= 0) {
                    alert('Validation Error: Please enter a valid quantity for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-qty')?.focus();
                    return;
                }
                if (mrpVal <= 0) {
                    alert('Validation Error: Please enter a valid MRP for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-mrp')?.focus();
                    return;
                }
                if (spVal <= 0) {
                    alert('Validation Error: Please enter a valid Selling Price for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-sp')?.focus();
                    return;
                }
                if (mrpVal < spVal) {
                    alert('Validation Error: MRP (₹' + mrpVal.toFixed(2) + ') cannot be lower than Selling Price (₹' + spVal.toFixed(2) + ') for Product #' + (p + 1) + ' on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-mrp')?.focus();
                    return;
                }
                if (gstVal < 0 || gstVal > 100) {
                    alert('Validation Error: GST rate must be between 0 and 100% for Product #' + (p + 1) + ' on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-gst')?.focus();
                    return;
                }
            }
        }

        const submitBtn = document.getElementById('btnSaveAllProducts');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving Products...';
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
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                alert('Success: ' + data.message);
                window.location.reload();
            } else {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save All Products to Mapping';
                }
                alert('Error: ' + (data.message || 'Failed to save products.'));
            }
        })
        .catch(function (err) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save All Products to Mapping';
            }
            alert('Server error: ' + err.message);
        });
    });

    const headerAddProductBtn = document.getElementById('btnHeaderAddProduct');
    const fabricTab = document.getElementById('tabFabricRollsBtn');
    const productTab = document.getElementById('tabThirdPartyBtn');
    fabricTab?.addEventListener('shown.bs.tab', function () {
        headerAddProductBtn?.classList.add('d-none');
    });
    productTab?.addEventListener('shown.bs.tab', function () {
        headerAddProductBtn?.classList.remove('d-none');
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
                    <?php if (!$viewOnly && empty($item['stock_added'])): ?>
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
                    <?php elseif (!empty($item['stock_added'])): ?>
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

    // 4. Fabric Roll Rows Dynamic Addition & AJAX Save
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
            const baseHsnSelect = rollBody.querySelector('.roll-hsn-select');
            const hsnOptionsHtml = baseHsnSelect ? baseHsnSelect.innerHTML : '<option value="5208">5208 (5%)</option>';

            for (let i = 0; i < count; i++) {
                const tr = document.createElement('tr');
                tr.className = 'mapping-roll-row';
                tr.innerHTML = `
                    <td><input class="form-control form-control-sm font-monospace" name="rolls[${nextRollIndex}][roll_code]" placeholder="ROLL-${String(nextRollIndex + 1).padStart(3, '0')}"></td>
                    <td><input class="form-control form-control-sm" name="rolls[${nextRollIndex}][color]" placeholder="Navy Blue" required></td>
                    <td><input class="form-control form-control-sm roll-num-dec roll-weight" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][weight_kg]" placeholder="22.50" required></td>
                    <td><input class="form-control form-control-sm roll-num-dec roll-rate" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][rate]" placeholder="150.00" required></td>
                    <td><input class="form-control form-control-sm roll-gst-input roll-num-dec" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][gst_rate]" value="${gstVal}"></td>
                    <td>
                        <select class="form-select form-select-sm font-monospace roll-hsn-select" name="rolls[${nextRollIndex}][hsn_code]">
                            ${hsnOptionsHtml}
                        </select>
                    </td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-roll-row"><i class="bi bi-x-lg"></i></button></td>
                `;
                const newHsnSelect = tr.querySelector('.roll-hsn-select');
                if (newHsnSelect && hsnVal) {
                    newHsnSelect.value = hsnVal;
                }
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

        // HSN select change inside roll rows auto-updates that roll's GST %
        rollBody.addEventListener('change', function(event) {
            if (event.target.classList.contains('roll-hsn-select')) {
                const opt = event.target.options[event.target.selectedIndex];
                const gst = opt ? opt.getAttribute('data-gst') : null;
                const rRow = event.target.closest('tr');
                if (rRow && gst !== null && gst !== undefined && gst !== '') {
                    const gstInput = rRow.querySelector('.roll-gst-input');
                    if (gstInput) gstInput.value = gst;
                }
            }
        });

        // Default Roll HSN change auto-updates default GST and existing roll rows
        if (defaultRollHsn) {
            defaultRollHsn.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const gst = opt ? opt.getAttribute('data-gst') : null;
                if (gst !== null && gst !== undefined && gst !== '' && defaultRollGst) {
                    defaultRollGst.value = gst;
                }
                const hVal = this.value;
                rollBody.querySelectorAll('.roll-hsn-select').forEach(function(sel) {
                    sel.value = hVal;
                    const rRow = sel.closest('tr');
                    if (rRow && gst !== null && gst !== undefined && gst !== '') {
                        const rGst = rRow.querySelector('.roll-gst-input');
                        if (rGst) rGst.value = gst;
                    }
                });
            });
        }

        // Live input validation on roll rows: numbers only
        rollBody.addEventListener('input', function(event) {
            if (event.target.classList.contains('roll-num-int')) {
                event.target.value = event.target.value.replace(/[^0-9]/g, '');
            } else if (event.target.classList.contains('roll-num-dec')) {
                event.target.value = event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            }
        });

        if (rollRowsToAddInput) {
            rollRowsToAddInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
        if (defaultRollGst) {
            defaultRollGst.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });
        }
    }

    const draftRollsForm = document.getElementById('mappingDraftRollsForm');
    if (draftRollsForm) {
        draftRollsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const fabricNameInput = draftRollsForm.querySelector('[name="fabric_name"]');
            const fabricName = (fabricNameInput?.value || '').trim();
            if (!fabricName) {
                alert('Validation Error: Please enter Fabric / Material Name.');
                fabricNameInput?.focus();
                return;
            }

            const rRows = draftRollsForm.querySelectorAll('#mappingRollRows tr');
            if (rRows.length === 0) {
                alert('Validation Error: Please add at least one fabric roll.');
                return;
            }

            for (let i = 0; i < rRows.length; i++) {
                const rRow = rRows[i];
                const colorInput = rRow.querySelector('[name$="[color]"]');
                const weightInput = rRow.querySelector('[name$="[weight_kg]"]');
                const rateInput = rRow.querySelector('[name$="[rate]"]');
                const gstInput = rRow.querySelector('[name$="[gst_rate]"]');

                const colorVal = (colorInput?.value || '').trim();
                const weightVal = parseFloat(weightInput?.value || '0') || 0;
                const rateVal = parseFloat(rateInput?.value || '0') || 0;
                const gstVal = parseFloat(gstInput?.value || '0') || 0;

                if (!colorVal) {
                    alert('Validation Error: Please enter Color / Shade for roll #' + (i + 1) + '.');
                    colorInput?.focus();
                    return;
                }
                if (weightVal <= 0) {
                    alert('Validation Error: Please enter a valid Weight (kg) for roll #' + (i + 1) + '.');
                    weightInput?.focus();
                    return;
                }
                if (rateVal <= 0) {
                    alert('Validation Error: Please enter a valid Rate (₹/kg) for roll #' + (i + 1) + '.');
                    rateInput?.focus();
                    return;
                }
                if (gstVal < 0 || gstVal > 100) {
                    alert('Validation Error: GST rate must be between 0 and 100% on roll #' + (i + 1) + '.');
                    gstInput?.focus();
                    return;
                }
            }

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
