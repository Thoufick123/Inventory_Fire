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
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>QR Generations<?= !empty($viewOnly) ? ' — View' : ' — Edit' ?></h2>
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
    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to QR Generations</a>
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
            </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <ul class="nav nav-pills" id="mappingEntryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-3 fw-semibold" id="tabThirdPartyBtn" data-bs-toggle="pill" data-bs-target="#tabThirdParty" type="button" role="tab">
                        <i class="bi bi-tag me-1"></i>Third-Party Products &amp; Variants
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 fw-semibold border" id="tabFabricRollsBtn" data-bs-toggle="pill" data-bs-target="#tabFabricRolls" type="button" role="tab">
                        <i class="bi bi-layers-half me-1"></i>Fabric Rolls (Multiple Rolls)
                    </button>
                </li>
            </ul>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleAddItemsBox" title="Collapse / Expand Add Items box">
                <i class="bi bi-arrows-collapse me-1"></i>Compact
            </button>
        </div>
    </div>
    <div class="card-body" id="addItemsMappingBody">
        <div class="tab-content" id="mappingEntryTabContent">
            <!-- PANE 1: Third-Party Products -->
            <div class="tab-pane fade show active" id="tabThirdParty" role="tabpanel">
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftProductForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="entry_type" value="THIRD_PARTY">

                    <div id="tpProductsContainer">
                        <!-- Product Block 0 -->
                        <div class="tp-product-card border rounded-3 mb-3 p-3 bg-white" data-product-index="0">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 tp-product-badge">Product #1</span>
                                    <strong class="text-dark tp-product-title-display">New Product</strong>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product" style="display: none;" title="Remove this product">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </div>

                            <!-- Step 1: Compact Product, Category & Attributes Generator -->
                            <div class="row g-2 align-items-end mb-3 p-2.5 rounded-3 bg-light-subtle border">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Product / Style Name <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-sm tp-product-name-input" name="products[0][product_name]" list="tpProductSuggestions" required maxlength="255" placeholder="e.g. Shirt, Pant..." autocomplete="off">
                                    <datalist id="tpProductSuggestions">
                                        <?php foreach (($existingProducts ?? []) as $ep): ?>
                                            <option value="<?= e($ep['name']) ?>"><?= !empty($ep['code']) ? '(' . e($ep['code']) . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">Unit</label>
                                    <select class="form-select form-select-sm" name="products[0][unit]">
                                        <option value="PCS">PCS</option><option value="SET">SET</option><option value="BOX">BOX</option><option value="MTR">MTR</option><option value="KGS">KGS</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">Category <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm gen-category">
                                        <option value="">-- Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">Sub Category</label>
                                    <select class="form-select form-select-sm gen-subcategory">
                                        <option value="">-- Sub Category --</option>
                                        <?php foreach ($subcategories as $sc): ?>
                                            <option value="<?= (int)$sc['id'] ?>" data-parent="<?= (int)$sc['category_id'] ?>" hidden><?= e($sc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">Sub Sub Category</label>
                                    <select class="form-select form-select-sm gen-subsubcategory">
                                        <option value="">-- Sub Sub --</option>
                                        <?php foreach ($subSubcategories as $ssc): ?>
                                            <option value="<?= (int)$ssc['id'] ?>" data-parent="<?= (int)$ssc['subcategory_id'] ?>" hidden><?= e($ssc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">HSN Code</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm gen-hsn font-monospace" placeholder="Type or select HSN (e.g. 6109)..." maxlength="20" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary btn-hsn-dropdown-toggle" title="Browse all HSN codes" tabindex="-1">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold mb-1">GST %</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm gen-gst" value="5">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">GST Mode</label>
                                    <div class="btn-group btn-group-sm w-100 gen-gst-mode-group" role="group">
                                        <input type="radio" class="btn-check gen-gst-mode" name="gen_gst_mode_0" id="gstInc0" value="INCLUDED" autocomplete="off">
                                        <label class="btn btn-outline-primary fw-semibold" for="gstInc0">GST Included</label>
                                        <input type="radio" class="btn-check gen-gst-mode" name="gen_gst_mode_0" id="gstExc0" value="EXCLUDED" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary fw-semibold" for="gstExc0">GST Excluded</label>
                                    </div>
                                </div>

                                <div class="col-md-5 d-none gen-attributes-col">
                                    <label class="form-label small fw-semibold mb-1">Sizes <span class="text-muted fw-normal">(auto-loaded from Category)</span></label>
                                    <div class="d-flex flex-wrap gap-1 mb-1 gen-size-chips"></div>
                                    <input type="hidden" class="gen-sizes-input">
                                </div>
                                <div class="col-md-5 d-none gen-attributes-col">
                                    <label class="form-label small fw-semibold mb-1">Colors <span class="text-muted fw-normal">(auto-loaded from Category)</span></label>
                                    <div class="d-flex flex-wrap gap-1 mb-1 gen-color-chips"></div>
                                    <input type="hidden" class="gen-colors-input">
                                </div>
                                <div class="col-md-2 ms-auto text-end">
                                    <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold btn-generate-variants">
                                        <i class="bi bi-lightning-charge me-1"></i>Generate &amp; Save
                                    </button>
                                </div>
                                <div class="col-12 d-none gen-custom-attrs-wrap"></div>

                                <!-- Per-Attribute Rate Table (appears when Sizes / Colors are selected) -->
                                <div class="col-12 mt-2 d-none gen-variant-rates-wrap">
                                    <div class="border rounded bg-light-subtle p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                                            <span class="small fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i>Selected Attributes — Qty &amp; Pricing</span>
                                            <span class="text-muted" style="font-size: 11px;">Customize Qty, Purchase Rate, MRP &amp; Selling Price per attribute before saving.</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered bg-white align-middle mb-0" style="font-size: 12px;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Attribute (Size / Color)</th>
                                                        <th style="width: 110px;">Qty *</th>
                                                        <th style="width: 140px;">Purchase Rate *</th>
                                                        <th style="width: 140px;">MRP (₹) *</th>
                                                        <th style="width: 140px;">Selling Price (₹) *</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="gen-variant-rates-body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden Variant Payload Table (populated & submitted automatically by Generate & Save) -->
                            <div class="d-none">
                                <table>
                                    <tbody class="tp-variant-rows">
                                        <?php $variantRow(0, 0); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- PANE 2: Fabric Rolls -->
            <div class="tab-pane fade" id="tabFabricRolls" role="tabpanel">
                <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/add-product') ?>" id="mappingDraftRollsForm">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="entry_type" value="FABRIC_ROLL">
                    <div class="border rounded-3 p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1">Roll Batch</span>
                                <strong class="text-dark">Fabric / Material Rolls</strong>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                <i class="bi bi-plus-circle me-1"></i>New Roll Product Auto-Created
                            </span>
                        </div>

                        <div class="table-responsive">
                        <table class="table table-bordered align-middle table-sm mb-2" id="mappingRollsTable" style="min-width: 860px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 210px;">Fabric / Material Name <span class="text-danger">*</span></th>
                                    <th style="width: 115px;">Roll Code / #</th>
                                    <th style="min-width: 130px;">Color / Shade <span class="text-danger">*</span></th>
                                    <th style="width: 145px;">Design / Pattern</th>
                                    <th style="width: 105px;">Weight (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 105px;">Rate (₹/kg) <span class="text-danger">*</span></th>
                                    <th style="width: 70px;">GST %</th>
                                    <th style="width: 165px;">HSN Code</th>
                                </tr>
                            </thead>
                            <tbody id="mappingRollRows">
                                <tr class="mapping-roll-row">
                                    <td>
                                        <input class="form-control form-control-sm" name="fabric_name" id="fabricNameInput" list="rollProductSuggestions" required maxlength="255" placeholder="e.g. Cotton Single Jersey 180 GSM..." autocomplete="off">
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
                                    </td>
                                    <td><input class="form-control form-control-sm font-monospace" name="rolls[0][roll_code]" placeholder="ROLL-001"></td>
                                    <td><input class="form-control form-control-sm" name="rolls[0][color]" placeholder="Navy Blue" required></td>
                                    <td><input class="form-control form-control-sm" name="rolls[0][design_name]" placeholder="Solid, Printed..."></td>
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
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end align-items-center mt-2">
                        <button class="btn btn-primary btn-sm px-4 fw-semibold" type="submit" id="btnGenerateSaveRolls">
                            <i class="bi bi-lightning-charge me-1"></i>Generate &amp; Save
                        </button>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .saved-variants-scroll {
        max-height: 360px;
        overflow: auto;
        border-top: 1px solid #dee2e6;
    }
    .saved-variants-scroll.is-expanded {
        max-height: none;
        overflow-y: visible;
    }
    .saved-variants-table {
        font-size: 12px;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .saved-variants-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 6px 8px;
        white-space: nowrap;
        border-bottom: 2px solid #cbd5e1;
        border-right: 1px solid #e2e8f0;
    }
    .saved-variants-table tbody td {
        padding: 4px 8px;
        white-space: nowrap;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
    }
    .saved-variants-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }
    .saved-variants-table tbody tr:hover {
        background-color: #eef2ff;
    }
    .saved-variants-table .btn-xs {
        padding: 1px 6px;
        font-size: 11px;
        line-height: 1.4;
    }
</style>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-list-ul me-2 text-primary"></i>Saved Product Variants</h5>
            <span class="badge bg-secondary" id="savedVariantsCountBadge"><?= count($items) ?> variants</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($items): ?>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" id="savedVariantSearch" class="form-control" placeholder="Filter item, VID, color, size..." autocomplete="off">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleSavedVariantsHeight" title="Collapse / Expand Saved Product Variants">
                    <i class="bi bi-arrows-collapse me-1"></i>Compact
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="saved-variants-scroll is-expanded" id="savedVariantsScroll">
        <table class="table table-hover align-middle saved-variants-table" id="savedVariantsTable">
            <thead>
                <tr>
                    <th class="text-center">Sl. No</th>
                    <th>Category</th>
                    <th>Item Name</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>HSN</th>
                    <th class="text-end">Qty</th>
                    <th>Uom</th>
                    <th class="text-end">Purchase Rate</th>
                    <th class="text-end">MRP / Sale</th>
                    <th>QR / Stock</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="12" class="text-center text-muted py-4">No products saved yet.</td></tr>
                <?php else: foreach ($items as $idx => $item): ?>
                    <?php
                        $catParts = array_filter([
                            $item['category_name'] ?? '',
                            $item['subcategory_name'] ?? '',
                            $item['sub_subcategory_name'] ?? ''
                        ]);
                        $catLabel = $catParts ? implode(' / ', $catParts) : '—';
                        $searchText = strtolower(trim(implode(' ', [
                            $item['item_name'] ?? '',
                            'vid ' . (int)$item['variant_id'],
                            $catLabel,
                            $item['color'] ?? '',
                            $item['size'] ?? '',
                            $item['hsn_code'] ?? '',
                            $item['batch_code'] ?? ''
                        ])));
                    ?>
                    <tr class="saved-variant-row" data-search="<?= e($searchText) ?>">
                        <td class="text-center text-muted fw-semibold font-monospace"><?= $idx + 1 ?></td>
                        <td title="<?= e($catLabel) ?>"><?= e(mb_strimwidth($catLabel, 0, 28, '...')) ?></td>
                        <td>
                            <strong class="text-dark"><?= e($item['item_name']) ?></strong>
                            <span class="badge bg-secondary-subtle text-secondary border font-monospace ms-1">VID: #<?= (int)$item['variant_id'] ?></span>
                        </td>
                        <td><?= e($item['color'] ?: '—') ?></td>
                        <td><?= e($item['size'] ?: '—') ?></td>
                        <td class="font-monospace"><?= e($item['hsn_code'] ?: '—') ?></td>
                        <td class="text-end font-monospace fw-semibold"><?= number_format((float)$item['quantity'], 2) ?></td>
                        <td><?= e($item['unit'] ?: 'PCS') ?></td>
                        <td class="text-end font-monospace"><?= formatCurrency((float)((float)$item['unit_price'])) ?></td>
                        <td class="text-end font-monospace"><?= formatCurrency((float)((float)$item['mrp'])) ?> / <?= formatCurrency((float)((float)$item['selling_price'])) ?></td>
                        <td>
                            <?php if (!empty($item['qr_generation_id'])): ?>
                                <span class="qr-status-badge-<?= (int)$item['qr_generation_id'] ?>"><?php if (!empty($item['stock_added'])): ?><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span><?php else: ?><span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Not stocked</span><?php endif; ?></span>
                            <?php else: ?><span class="badge bg-secondary">No QR</span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($viewOnly): ?>
                                <span class="text-muted small">View only</span>
                            <?php else: ?>
                                <?php if (empty($item['qr_generation_id'])): ?>
                                <?php if ($bill['status'] === 'MOVED' || !empty($bill['purchase_id'])): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/item/' . (int)$item['id'] . '/generate-qr') ?>" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="mapping_bill_id" value="<?= $mappingBillId ?>">
                                        <button class="btn btn-xs btn-outline-info" type="submit"><i class="bi bi-qr-code me-1"></i>Generate QR</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-xs btn-outline-secondary" type="button" disabled title="Move to Purchase first to generate QR"><i class="bi bi-lock me-1"></i>Generate QR</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-xs btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$item['qr_generation_id'] ?>" title="View QR in this panel">
                                        <i class="bi bi-eye me-1"></i>View QR
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-print-tsc-batch" data-generation-id="<?= (int)$item['qr_generation_id'] ?>" title="Print Labels">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                <?php if ($bill['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'edit')): ?>
                                    <button type="button"
                                            class="btn btn-xs btn-outline-primary btn-edit-saved-item"
                                            title="Edit saved variant"
                                            data-id="<?= (int)$item['id'] ?>"
                                            data-name="<?= e($item['item_name']) ?>"
                                            data-color="<?= e($item['color'] ?? '') ?>"
                                            data-size="<?= e($item['size'] ?? '') ?>"
                                            data-hsn="<?= e($item['hsn_code'] ?? '') ?>"
                                            data-unit="<?= e($item['unit'] ?: 'PCS') ?>"
                                            data-qty="<?= e((string)(float)$item['quantity']) ?>"
                                            data-rate="<?= e((string)(float)$item['unit_price']) ?>"
                                            data-mrp="<?= e((string)(float)$item['mrp']) ?>"
                                            data-selling="<?= e((string)(float)$item['selling_price']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($bill['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'delete')): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/item/' . (int)$item['id'] . '/delete') ?>" class="d-inline js-draft-confirm-form" data-confirm-title="Delete Saved Product" data-confirm-msg="Delete this product and its unposted QR batch? This cannot be undone." data-confirm-btn="Yes, Delete" data-confirm-class="btn-danger">
                                        <?= CSRF::field() ?>
                                        <button class="btn btn-xs btn-outline-danger" type="submit" title="Delete product from mapping invoice">
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
<!-- Edit Saved Variant Modal -->
<div class="modal fade" id="editSavedVariantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="editSavedVariantForm" class="modal-content border-0 shadow-lg rounded-3">
            <?= CSRF::field() ?>
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Saved Variant: <span id="editVarTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2.5">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Color</label>
                        <input type="text" name="color" id="editVarColor" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Size</label>
                        <input type="text" name="size" id="editVarSize" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">HSN Code</label>
                        <input type="text" name="hsn_code" id="editVarHsn" class="form-control form-control-sm font-monospace">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="quantity" id="editVarQty" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">UOM</label>
                        <input type="text" name="unit" id="editVarUnit" class="form-control form-control-sm text-uppercase" value="PCS">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Purchase Rate <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="editVarRate" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">MRP (₹)</label>
                        <input type="number" step="0.01" min="0" name="mrp" id="editVarMrp" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Selling Price (₹)</label>
                        <input type="number" step="0.01" min="0" name="selling_price" id="editVarSelling" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-none" id="editVarPriceError">
                        <div class="alert alert-danger py-1.5 px-2.5 small mb-0">MRP cannot be less than Selling Price.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modern UI Alert Modal (replaces native browser showDraftModernAlert()) -->
<div class="modal fade" id="draftModernAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4 text-center">
                <div id="draftModernAlertIconWrap" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 56px; height: 56px; background: #fef3c7; color: #d97706;">
                    <i id="draftModernAlertIcon" class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="draftModernAlertTitle">Notice</h5>
                <p class="text-secondary small mb-3" id="draftModernAlertMsg"></p>
                <button type="button" class="btn btn-primary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
window.showDraftModernAlert = function (message, title = 'Notice', type = 'warning') {
    const modalEl = document.getElementById('draftModernAlertModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    const msgStr = String(message ?? '');
    if (type === 'warning' && msgStr.startsWith('Success:')) type = 'success';
    else if (type === 'warning' && (msgStr.startsWith('Error:') || msgStr.startsWith('Server error:'))) type = 'danger';
    const styles = {
        success: { bg: '#dcfce7', color: '#16a34a', icon: 'bi bi-check-circle-fill fs-3', title: 'Success' },
        danger:  { bg: '#fee2e2', color: '#dc2626', icon: 'bi bi-x-circle-fill fs-3', title: 'Error' },
        warning: { bg: '#fef3c7', color: '#d97706', icon: 'bi bi-exclamation-triangle-fill fs-3', title: title },
    };
    const st = styles[type] || styles.warning;
    const iconWrap = document.getElementById('draftModernAlertIconWrap');
    const iconEl = document.getElementById('draftModernAlertIcon');
    const titleEl = document.getElementById('draftModernAlertTitle');
    const msgEl = document.getElementById('draftModernAlertMsg');
    if (iconWrap) { iconWrap.style.background = st.bg; iconWrap.style.color = st.color; }
    if (iconEl) iconEl.className = st.icon;
    if (titleEl) titleEl.textContent = st.title;
    if (msgEl) msgEl.textContent = msgStr;
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
};

window.showDraftModernConfirm = function (title, message, onConfirm, confirmText = 'Confirm', btnClass = 'btn-primary') {
    if (typeof window.showModernConfirm === 'function') {
        window.showModernConfirm({
            title: title,
            message: message,
            confirmText: confirmText,
            btnClass: btnClass,
            iconClass: btnClass === 'btn-danger' ? 'bi bi-trash3-fill text-danger' : 'bi bi-question-circle-fill text-primary',
            onConfirm: onConfirm
        });
    } else {
        onConfirm();
    }
};

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.classList || !form.classList.contains('js-draft-confirm-form')) return;
    if (form.dataset.confirmed === '1') return;
    e.preventDefault();
    e.stopImmediatePropagation();
    window.showDraftModernConfirm(
        form.dataset.confirmTitle || 'Please Confirm',
        form.dataset.confirmMsg || 'Are you sure?',
        function () {
            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        },
        form.dataset.confirmBtn || 'Confirm',
        form.dataset.confirmClass || 'btn-primary'
    );
}, true);
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('savedVariantSearch');
    const scrollBox = document.getElementById('savedVariantsScroll');
    const toggleBtn = document.getElementById('toggleSavedVariantsHeight');
    const countBadge = document.getElementById('savedVariantsCountBadge');
    const rows = Array.from(document.querySelectorAll('#savedVariantsTable tbody tr.saved-variant-row'));

    if (searchInput && rows.length) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(row => {
                const match = !q || (row.dataset.search || '').includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (countBadge) {
                countBadge.textContent = q ? `${visible} / ${rows.length} variants` : `${rows.length} variants`;
            }
        });
    }

    if (toggleBtn && scrollBox) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = scrollBox.classList.toggle('d-none');
            if (!isHidden) scrollBox.classList.add('is-expanded');
            toggleBtn.innerHTML = isHidden
                ? '<i class="bi bi-arrows-expand me-1"></i>Expand'
                : '<i class="bi bi-arrows-collapse me-1"></i>Compact';
        });
    }

    const toggleAddItemsBtn = document.getElementById('toggleAddItemsBox');
    const addItemsBody = document.getElementById('addItemsMappingBody');
    if (toggleAddItemsBtn && addItemsBody) {
        toggleAddItemsBtn.addEventListener('click', () => {
            const isHidden = addItemsBody.classList.toggle('d-none');
            toggleAddItemsBtn.innerHTML = isHidden
                ? '<i class="bi bi-arrows-expand me-1"></i>Expand'
                : '<i class="bi bi-arrows-collapse me-1"></i>Compact';
        });
    }

    const editModalEl = document.getElementById('editSavedVariantModal');
    const editForm = document.getElementById('editSavedVariantForm');
    document.querySelectorAll('.btn-edit-saved-item').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!editForm || !editModalEl) return;
            editForm.action = '<?= url('purchases/mapping/draft/item/') ?>' + btn.dataset.id + '/update';
            document.getElementById('editVarTitle').textContent = btn.dataset.name || '';
            document.getElementById('editVarColor').value = btn.dataset.color || '';
            document.getElementById('editVarSize').value = btn.dataset.size || '';
            document.getElementById('editVarHsn').value = btn.dataset.hsn || '';
            document.getElementById('editVarQty').value = btn.dataset.qty || '1';
            document.getElementById('editVarUnit').value = btn.dataset.unit || 'PCS';
            document.getElementById('editVarRate').value = btn.dataset.rate || '0';
            document.getElementById('editVarMrp').value = btn.dataset.mrp || '0';
            document.getElementById('editVarSelling').value = btn.dataset.selling || '0';
            document.getElementById('editVarPriceError')?.classList.add('d-none');
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            }
        });
    });

    editForm?.addEventListener('submit', (e) => {
        const mrp = parseFloat(document.getElementById('editVarMrp')?.value || '0') || 0;
        const sell = parseFloat(document.getElementById('editVarSelling')?.value || '0') || 0;
        const errBox = document.getElementById('editVarPriceError');
        if (mrp > 0 && sell > mrp) {
            e.preventDefault();
            errBox?.classList.remove('d-none');
            return;
        }
        errBox?.classList.add('d-none');
    });
});
</script>

<!-- ========================================================================= -->
<!-- 3 ACTION OPTIONS CONTAINER (Scenario 2)                                  -->
<!-- ========================================================================= -->
<?php if (!$viewOnly): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Mapping Actions &amp; Workflows</h5>
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
                       
                    </div>
                    <?php if ($bill['status'] === 'MOVED'): ?>
                        <div class="alert alert-success py-2 px-3 mb-0 text-center small fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i>Moved to Purchase <?= e($bill['purchase_number'] ?? ('#' . $bill['purchase_id'])) ?>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/move') ?>" class="m-0 js-draft-confirm-form" data-confirm-title="Move to Purchases" data-confirm-msg="Move this mapping invoice to Purchases? It will create or link the purchase order for the purchase team to fill GST & HSN." data-confirm-btn="Move to Purchase" data-confirm-class="btn-success">
                            <?= CSRF::field() ?>
                            <button class="btn btn-success w-100 py-2 fw-semibold" type="submit" <?= !$items ? 'disabled' : '' ?>>
                                <i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php $isMovedToPurchase = ($bill['status'] === 'MOVED' || !empty($bill['purchase_id'])); ?>
            <!-- OPTION 2: QR Generations & In-Panel Inventory Addition -->
            <div class="col-lg-4 col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between shadow-sm <?= !$isMovedToPurchase ? 'opacity-75' : '' ?>">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2 gap-1 flex-wrap">
                            <h6 class="fw-bold text-info mb-0"><i class="bi bi-qr-code me-1"></i>Option 2: QR Generations</h6>
                            <div class="d-flex align-items-center gap-1">
                                <?php if ($isMovedToPurchase): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#modalTscPrinterConfig" title="Configure QR &amp; Label Formation">
                                        <i class="bi bi-sliders me-1"></i>QR Config
                                    </button>
                                <?php endif; ?>
                                <span class="badge <?= $isMovedToPurchase ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-secondary border' ?>">
                                    <?= $isMovedToPurchase ? 'Labels &amp; Stock' : 'Locked' ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!$isMovedToPurchase): ?>
                            <p class="small text-muted mb-2"><i class="bi bi-lock me-1"></i>Move this mapping to Purchase first to enable QR generation.</p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <form method="POST" action="<?= url('purchases/mapping/draft/' . $mappingBillId . '/generate-all-qr') ?>" class="m-0 js-draft-confirm-form" data-confirm-title="Generate All Variant QRs" data-confirm-msg="Generate QR batches for all saved variants that do not already have QR codes?" data-confirm-btn="Generate QRs" data-confirm-class="btn-primary">
                            <?= CSRF::field() ?>
                            <button class="btn <?= $isMovedToPurchase ? 'btn-outline-info' : 'btn-outline-secondary' ?> w-100 py-2 fw-semibold" type="submit" <?= (!$isMovedToPurchase || $pendingQrCount === 0) ? 'disabled' : '' ?>>
                                <i class="bi <?= $isMovedToPurchase ? 'bi-qr-code' : 'bi-lock' ?> me-1"></i><?= !$isMovedToPurchase ? 'Move to Purchase First' : ($pendingQrCount > 0 ? 'Generate QR for ' . $pendingQrCount . ' Variants' : ($items ? 'All Variants Have QR' : 'No Variants to Generate')) ?>
                            </button>
                        </form>
                        <?php if ($isMovedToPurchase && !empty($firstQrId)): ?>
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
                <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between shadow-sm <?= !$isMovedToPurchase ? 'opacity-75' : '' ?>">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-warning mb-0"><i class="bi bi-clock-history me-1"></i>Option 3: Purchase Bill Status</h6>
                            <span class="badge <?= $isMovedToPurchase ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-secondary border' ?>">
                                <?= $isMovedToPurchase ? 'Mapping Status' : 'Locked' ?>
                            </span>
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
                                Move this mapping to Purchase first to enable Purchase Bill Status &amp; linking for invoice <strong class="font-monospace"><?= e($bill['invoice_number']) ?></strong>.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($bill['status'] !== 'MOVED' && !empty($candidatePurchases)): ?>
                        <button type="button" class="btn btn-outline-primary w-100 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#linkExistingPurchaseModal" <?= (!$isMovedToPurchase || !$items) ? 'disabled' : '' ?>>
                            <i class="bi <?= $isMovedToPurchase ? 'bi-link-45deg' : 'bi-lock' ?> me-1"></i>Link to Existing Purchase Bill
                        </button>
                    <?php else: ?>
                        <div class="bg-white border rounded p-2 text-center small text-muted">
                            <i class="bi <?= $isMovedToPurchase ? 'bi-info-circle' : 'bi-lock' ?> me-1"></i><?= $bill['status'] === 'MOVED' ? 'Synchronization complete' : 'Move to Purchase first to unlock' ?>
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
                                    <?= e($cand['purchase_number']) ?> — <?= e(date('d M Y', strtotime($cand['purchase_date']))) ?><?= !empty($cand['invoice_number']) ? ' (Inv: ' . e($cand['invoice_number']) . ')' : '' ?> — <?= formatCurrency((float)((float)$cand['total_amount'])) ?>
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
            showDraftModernAlert('Please add at least one product.');
            return;
        }

        for (let p = 0; p < cards.length; p++) {
            const card = cards[p];
            const pName = card.querySelector('.tp-product-name-input')?.value?.trim();
            if (!pName) {
                showDraftModernAlert('Validation Error: Please enter a Product Name for Product #' + (p + 1) + '.');
                card.querySelector('.tp-product-name-input')?.focus();
                return;
            }

            const variantRows = card.querySelectorAll('.tp-variant-rows tr');
            if (variantRows.length === 0) {
                showDraftModernAlert('Validation Error: Please add at least one variant for Product #' + (p + 1) + ' (' + pName + ').');
                return;
            }

            for (let i = 0; i < variantRows.length; i++) {
                const vRow = variantRows[i];
                const catVal = vRow.querySelector('.mapping-category')?.value;
                if (!catVal) {
                    showDraftModernAlert('Validation Error: Please select a Category for Product #' + (p + 1) + ' (' + pName + ') on variant row #' + (i + 1) + '.');
                    vRow.querySelector('.mapping-category')?.focus();
                    return;
                }

                const qVal = parseFloat(vRow.querySelector('.map-qty')?.value || '0') || 0;
                const mrpVal = parseFloat(vRow.querySelector('.map-mrp')?.value || '0') || 0;
                const spVal = parseFloat(vRow.querySelector('.map-sp')?.value || '0') || 0;
                const gstVal = parseFloat(vRow.querySelector('.map-gst')?.value || '0') || 0;

                if (qVal <= 0) {
                    showDraftModernAlert('Validation Error: Please enter a valid quantity for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-qty')?.focus();
                    return;
                }
                if (mrpVal <= 0) {
                    showDraftModernAlert('Validation Error: Please enter a valid MRP for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-mrp')?.focus();
                    return;
                }
                if (spVal <= 0) {
                    showDraftModernAlert('Validation Error: Please enter a valid Selling Price for Product #' + (p + 1) + ' (' + pName + ') on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-sp')?.focus();
                    return;
                }
                if (mrpVal < spVal) {
                    showDraftModernAlert('Validation Error: MRP (₹' + mrpVal.toFixed(2) + ') cannot be lower than Selling Price (₹' + spVal.toFixed(2) + ') for Product #' + (p + 1) + ' on row #' + (i + 1) + '.');
                    vRow.querySelector('.map-mrp')?.focus();
                    return;
                }
                if (gstVal < 0 || gstVal > 100) {
                    showDraftModernAlert('Validation Error: GST rate must be between 0 and 100% for Product #' + (p + 1) + ' on row #' + (i + 1) + '.');
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
                showDraftModernAlert('Success: ' + data.message);
                window.location.reload();
            } else {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save All Products to Mapping';
                }
                showDraftModernAlert('Error: ' + (data.message || 'Failed to save products.'));
            }
        })
        .catch(function (err) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save All Products to Mapping';
            }
            showDraftModernAlert('Server error: ' + err.message);
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: min(96vw, 1280px);">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <h6 class="modal-title mb-0 fw-bold" id="modalQrBatchLabel_<?= (int)$item['qr_generation_id'] ?>">
                        <i class="bi bi-qr-code me-2"></i>QR Labels
                    </h6>
                    <span class="badge bg-primary"><i class="bi bi-tag-fill me-1"></i>Variant ID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <?php if (count($items) > 1): ?>
                    <div class="mb-2 p-2 bg-light rounded-3 border">
                        <div class="small fw-bold text-muted mb-1 text-uppercase d-flex justify-content-between align-items-center flex-wrap gap-1" style="font-size: 11px;">
                            <span><i class="bi bi-collection me-1 text-primary"></i>Switch Variant QRs (<?= count($items) ?> Total Variants):</span>
                            <span class="text-primary fw-semibold"><i class="bi bi-eye me-1"></i>Currently viewing: <?= e($item['item_name']) ?> (VID: #<?= (int)($item['variant_id'] ?? 0) ?>)</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($items as $vItem): if (!empty($vItem['qr_generation_id'])): 
                                $isActiveVariant = ((int)$vItem['id'] === (int)$item['id']);
                                $vCount = count($qrItemMap[(int)$vItem['qr_generation_id']] ?? []);
                                $vGenId = (int)$vItem['qr_generation_id'];
                                $vStocked = !empty($vItem['stock_added']);
                            ?>
                                <div class="btn-group btn-group-sm align-items-center">
                                    <?php if (!$viewOnly && !$vStocked): ?>
                                        <label class="btn btn-sm btn-outline-secondary d-flex align-items-center px-2" title="Select to add to inventory">
                                            <input type="checkbox"
                                                   class="form-check-input m-0 qr-stock-select-chk"
                                                   value="<?= $vGenId ?>"
                                                   <?= $isActiveVariant ? 'checked' : '' ?>>
                                        </label>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm <?= $isActiveVariant ? 'btn-primary' : 'btn-outline-secondary' ?>" 
                                            data-bs-target="#modalQrBatch_<?= $vGenId ?>" 
                                            data-bs-toggle="modal">
                                        <i class="bi bi-box-seam me-1"></i><?= e($vItem['item_name']) ?>
                                        <span class="opacity-75">(<?= e(trim(($vItem['color'] ?? '') . ' ' . ($vItem['size'] ?? ''))) ?>)</span>
                                        <span class="badge bg-light text-dark ms-1">VID: #<?= (int)($vItem['variant_id'] ?? 0) ?></span>
                                        <span class="badge <?= $isActiveVariant ? 'bg-dark text-white' : 'bg-secondary text-white' ?> ms-1"><?= $vCount ?> QRs</span>
                                        <?php if ($vStocked): ?>
                                            <span class="badge bg-success ms-1">Stocked</span>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-2 px-3">
                        <div class="row row-cols-2 row-cols-md-5 g-2 align-items-center">
                            <div class="col">
                                <div class="text-muted" style="font-size: 11px;">Product</div>
                                <div class="fw-bold text-truncate"><?= e($item['item_name']) ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted" style="font-size: 11px;">Variant</div>
                                <div class="fw-semibold">
                                    <?= e(trim(($item['color'] ?? '') . ' ' . ($item['size'] ?? ''))) ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">VID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-muted" style="font-size: 11px;">Total Qty</div>
                                <div class="fw-bold"><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted" style="font-size: 11px;">MRP / Selling Price</div>
                                <div class="fw-bold text-success"><?= formatCurrency((float)((float)$item['mrp'])) ?> / <?= formatCurrency((float)((float)$item['selling_price'])) ?></div>
                            </div>
                            <div class="col">
                                <div class="text-muted" style="font-size: 11px;">Inventory Status</div>
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

                <!-- Active QR & Printer Config Bar -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-2 px-3 mb-3 bg-light border rounded-3">
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi bi-sliders text-primary"></i>
                        <span class="fw-bold text-dark">QR &amp; Label Config:</span>
                        <span class="badge bg-primary-subtle text-primary border tsc-active-summary">Dual Roll 40mm × 25mm · QR Size 3</span>
                        <span class="badge bg-success-subtle text-success border d-none qr-config-saved-badge">Saved &amp; Applied</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold btn-open-tsc-config" data-bs-toggle="modal" data-bs-target="#modalTscPrinterConfig">
                        <i class="bi bi-gear-fill me-1"></i>TSC TE244 QR &amp; Label Config
                    </button>
                </div>

                <h6 class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-upc-scan me-1 text-primary"></i>Generated Labels (<?= count($batchQrItems) ?>)</h6>
                <div class="row g-2">
                    <?php if (empty($batchQrItems)): ?>
                        <div class="col-12 text-center text-muted py-3">No QR label records found for this batch.</div>
                    <?php else:
                        $catTrail = implode(' / ', array_filter([
                            trim((string)($item['category_name'] ?? '')),
                            trim((string)($item['subcategory_name'] ?? '')),
                            trim((string)($item['sub_subcategory_name'] ?? '')),
                        ]));
                        $attrText = trim(implode(' / ', array_filter([
                            trim((string)($item['color'] ?? '')),
                            trim((string)($item['size'] ?? '')),
                        ])));
                        $designText = trim((string)($item['design_name'] ?? ''));
                        foreach ($batchQrItems as $q): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-2 text-center bg-white shadow-sm h-100 d-flex flex-column justify-content-between qr-label-card"
                                 data-qr-code="<?= e($q['qr_code']) ?>"
                                 data-vid="VID: #<?= (int)($item['variant_id'] ?? 0) ?>"
                                 data-product-name="<?= e($item['item_name']) ?>"
                                 data-category="<?= e($catTrail) ?>"
                                 data-attributes="<?= e($attrText) ?>"
                                 data-design="<?= e($designText) ?>"
                                 data-mrp="<?= e(formatCurrency((float)$item['mrp'])) ?>"
                                 data-selling-price="<?= e(formatCurrency((float)$item['selling_price'])) ?>"
                                 data-hsn="<?= e($item['hsn_code'] ?? '') ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                                    <span class="small fw-bold text-truncate text-dark qr-lbl-product"><?= e($item['item_name']) ?></span>
                                    <span class="badge bg-light text-secondary border qr-lbl-vid" style="font-size: 10px;">VID: #<?= (int)($item['variant_id'] ?? 0) ?></span>
                                </div>
                                <div class="text-muted qr-lbl-category" style="font-size: 10px;"><?= e($catTrail) ?></div>
                                <div class="small text-muted mb-1 qr-lbl-attrs" style="font-size: 11px;"><?= e($attrText) ?></div>
                                <div class="small text-muted mb-1 d-none qr-lbl-design" style="font-size: 10px;"><?= e($designText) ?></div>
                                <div class="d-flex justify-content-center my-1">
                                    <div class="panel-qr-canvas p-1 bg-white border rounded" data-code="<?= e($q['qr_code']) ?>"></div>
                                </div>
                                <div class="font-monospace fw-bold text-primary small mt-1 qr-lbl-code"><?= e($q['qr_code']) ?></div>
                                <div class="small text-muted qr-lbl-price" style="font-size: 11px;"><?= formatCurrency((float)$item['selling_price']) ?></div>
                                <div class="text-muted d-none qr-lbl-hsn" style="font-size: 10px;">HSN: <?= e($item['hsn_code'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!$viewOnly && empty($item['stock_added'])): ?>
                        <div class="input-group input-group-sm" style="max-width: 340px;">
                            <span class="input-group-text bg-light"><i class="bi bi-building me-1"></i>Stock to:</span>
                            <select class="form-select" id="whSelect_<?= (int)$item['qr_generation_id'] ?>">
                                <?php
                                $listedConcernIds = [];
                                foreach (($warehouses ?? []) as $wh):
                                    if (!empty($wh['concern_id'])) {
                                        $listedConcernIds[(int)$wh['concern_id']] = true;
                                    }
                                ?>
                                    <option value="wh:<?= (int)$wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['concern_name']) ? ' (' . e($wh['concern_name']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                                <?php foreach (($concerns ?? []) as $con):
                                    if (isset($listedConcernIds[(int)$con['id']])) continue;
                                ?>
                                    <option value="concern:<?= (int)$con['id'] ?>"><?= e(!empty($con['code']) ? $con['code'] . ' - ' . $con['name'] : $con['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success btn-add-inventory-draft"
                                data-generation-id="<?= (int)$item['qr_generation_id'] ?>"
                                data-mapping-bill-id="<?= $mappingBillId ?>">
                            <i class="bi bi-box-seam me-1"></i>Add Selected to Inventory
                        </button>
                    <?php elseif (!empty($item['stock_added'])): ?>
                        <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Stock already added to warehouse</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm btn-print-tsc-batch" data-generation-id="<?= (int)$item['qr_generation_id'] ?>">
                        <i class="bi bi-printer me-1"></i>Print Labels
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; endforeach; endif; ?>

<!-- TSC TE244 Printer & Dynamic Label Layout Settings Modal -->
<div class="modal fade" id="modalTscPrinterConfig" tabindex="-1" aria-labelledby="modalTscPrinterConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: min(96vw, 1180px);">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2.5 px-3" style="background: #1f242d; color: #fff;">
                <h6 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" id="modalTscPrinterConfigLabel">
                    <i class="bi bi-sliders text-warning"></i>
                    <span>TSC TE244 Printer &amp; Dynamic Label Layout Settings</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light-subtle">
                <div class="row g-3">
                    <!-- Left Controls -->
                    <div class="col-lg-5 d-flex flex-column gap-3">
                        <!-- 1. Sticker Dimensions -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-bounding-box text-primary me-1"></i>1. Sticker Dimensions</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Sticker Width (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscWidthMm" value="40" min="15" max="120">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Sticker Height (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscHeightMm" value="25" min="15" max="120">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Roll Columns</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscColumns">
                                            <option value="2" selected>2 Columns (Dual Roll)</option>
                                            <option value="1">1 Column (Single Roll)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Row Gap (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscGapMm" value="2" min="0" max="20">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-primary mb-1"><i class="bi bi-arrow-left-right me-1"></i>2nd Sticker (Right Column) X Shift (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscCol2Shift" value="0" min="-200" max="200">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. QR Code Alignment (Left Column) -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-qr-code text-success me-1"></i>2. QR Code Alignment (Left Column)</div>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1">QR X (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscQrX" value="12" min="0" max="300">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1">QR Y (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscQrY" value="35" min="0" max="300">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1">QR Size</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscQrSize">
                                            <option value="2">Size 2 (Small)</option>
                                            <option value="3" selected>Size 3 (Medium)</option>
                                            <option value="4">Size 4 (Large)</option>
                                            <option value="5">Size 5 (XL)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Code Subtitle X</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSubX" value="12" min="0" max="300">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Code Subtitle Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSubY" value="125" min="0" max="300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Text Positions (Right Column) -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-fonts text-info me-1"></i>3. Text Positions (Right Column)</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">Right Text X Offset</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscTextX" value="115" min="0" max="400">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">TSPL Font Size</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscFontSize">
                                            <option value="1">Font 1 (Tiny)</option>
                                            <option value="2" selected>Font 2 (Standard)</option>
                                            <option value="3">Font 3 (Medium)</option>
                                            <option value="4">Font 4 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1">Title Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscTitleY" value="35" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1">Variant Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscVarY" value="62" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1">MRP Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscMrpY" value="90" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1">Disc Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSpY" value="118" min="0" max="300">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Live Dual-Sticker Preview -->
                    <div class="col-lg-7">
                        <div class="card border h-100">
                            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-dark"><i class="bi bi-eye text-primary me-1"></i>Live Dual-Sticker Preview</span>
                                <span class="badge bg-primary-subtle text-primary border" id="tscPreviewBadge">Dual Roll 40mm × 25mm</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center align-items-center" style="background: #e2e5ea; min-height: 360px;">
                                <div class="bg-white rounded-3 shadow-sm p-3 d-flex justify-content-center align-items-center gap-3 flex-wrap" id="tscLivePreviewStage"></div>
                                <div class="small text-muted mt-3" id="tscPreviewCaption">
                                    <i class="bi bi-info-circle me-1"></i>Simulating physical 2-column TSC thermal sticker roll layout.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnTscTestPrint">
                    <i class="bi bi-printer me-1"></i>Test Print 1 Sticker
                </button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold" id="btnSaveTscConfig">
                    <i class="bi bi-check-lg me-1"></i>Save Printer Configuration
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. TSC TE244 Printer & Dynamic Label Layout Config + Canvas Rendering
    const TSC_CFG_STORAGE_KEY = 'grm_tsc_label_config_v1';
    let lastActiveQrBatchModal = null;

    function getDefaultTscConfig() {
        return {
            widthMm: 40,
            heightMm: 25,
            columns: 2,
            gapMm: 2,
            col2Shift: 0,
            qrX: 12,
            qrY: 35,
            qrSize: 3,
            subX: 12,
            subY: 125,
            textX: 115,
            fontSize: 2,
            titleY: 35,
            varY: 62,
            mrpY: 90,
            spY: 118
        };
    }

    function getSavedTscConfig() {
        const def = getDefaultTscConfig();
        try {
            const raw = localStorage.getItem(TSC_CFG_STORAGE_KEY);
            if (!raw) return def;
            return Object.assign({}, def, JSON.parse(raw));
        } catch (e) {
            return def;
        }
    }

    function readTscConfigFromInputs() {
        const num = (id, fb) => {
            const v = parseFloat(document.getElementById(id)?.value);
            return Number.isFinite(v) ? v : fb;
        };
        const def = getDefaultTscConfig();
        return {
            widthMm: num('tscWidthMm', def.widthMm),
            heightMm: num('tscHeightMm', def.heightMm),
            columns: parseInt(document.getElementById('tscColumns')?.value || String(def.columns), 10) || 2,
            gapMm: num('tscGapMm', def.gapMm),
            col2Shift: num('tscCol2Shift', def.col2Shift),
            qrX: num('tscQrX', def.qrX),
            qrY: num('tscQrY', def.qrY),
            qrSize: parseInt(document.getElementById('tscQrSize')?.value || String(def.qrSize), 10) || 3,
            subX: num('tscSubX', def.subX),
            subY: num('tscSubY', def.subY),
            textX: num('tscTextX', def.textX),
            fontSize: parseInt(document.getElementById('tscFontSize')?.value || String(def.fontSize), 10) || 2,
            titleY: num('tscTitleY', def.titleY),
            varY: num('tscVarY', def.varY),
            mrpY: num('tscMrpY', def.mrpY),
            spY: num('tscSpY', def.spY)
        };
    }

    function writeTscConfigToInputs(cfg) {
        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
        setVal('tscWidthMm', cfg.widthMm);
        setVal('tscHeightMm', cfg.heightMm);
        setVal('tscColumns', String(cfg.columns));
        setVal('tscGapMm', cfg.gapMm);
        setVal('tscCol2Shift', cfg.col2Shift);
        setVal('tscQrX', cfg.qrX);
        setVal('tscQrY', cfg.qrY);
        setVal('tscQrSize', String(cfg.qrSize));
        setVal('tscSubX', cfg.subX);
        setVal('tscSubY', cfg.subY);
        setVal('tscTextX', cfg.textX);
        setVal('tscFontSize', String(cfg.fontSize));
        setVal('tscTitleY', cfg.titleY);
        setVal('tscVarY', cfg.varY);
        setVal('tscMrpY', cfg.mrpY);
        setVal('tscSpY', cfg.spY);
    }

    function renderTscStickerHtml(cfg, data, labelBadge = '') {
        const scale = 0.82;
        const stickerW = Math.max(180, Math.round(cfg.widthMm * 6.4));
        const stickerH = Math.max(120, Math.round(cfg.heightMm * 6.4));
        const qrPx = Math.max(36, Math.min(88, cfg.qrSize * 16));
        const fontPx = Math.max(10, 7.5 + cfg.fontSize * 1.6);
        const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        const title = esc((data.productName || 'PRODUCT').slice(0, 24));
        const attrs = esc((data.attributes || '').slice(0, 24));
        const mrp = esc(data.mrp || '');
        const sp = esc(data.sellingPrice || '');
        const code = esc(data.qrCode || '');
        const vid = esc(data.vid || '');
        const isCol2 = data.colIndex === 2;
        const shiftPx = isCol2 ? Math.round(cfg.col2Shift * scale) : 0;
        const qrLeft = Math.round(cfg.qrX * scale) + shiftPx;
        const qrTop = Math.round(cfg.qrY * scale);
        const subLeft = Math.round(cfg.subX * scale) + shiftPx;
        const subTop = Math.round(cfg.subY * scale);
        const txtLeft = Math.round(cfg.textX * scale) + shiftPx;
        const txtMaxW = Math.max(70, stickerW - txtLeft - 8);

        return `
            <div class="border rounded bg-white position-relative overflow-hidden shadow-sm" style="width:${stickerW}px;height:${stickerH}px;">
                ${labelBadge ? `<span class="badge bg-light text-secondary border position-absolute" style="top:4px;left:6px;font-size:9px;">${esc(labelBadge)}</span>` : ''}
                ${vid ? `<span class="badge bg-light text-secondary border position-absolute" style="top:4px;right:6px;font-size:9px;">${vid}</span>` : ''}
                <div class="position-absolute border rounded d-flex align-items-center justify-content-center bg-white tsc-qr-target" data-code="${code}" data-qr-px="${qrPx}" style="left:${qrLeft}px;top:${qrTop}px;width:${qrPx}px;height:${qrPx}px;"></div>
                <div class="position-absolute font-monospace fw-bold text-dark" style="left:${subLeft}px;top:${subTop}px;font-size:9.5px;">${code}</div>
                <div class="position-absolute fw-bold text-primary text-truncate" style="left:${txtLeft}px;top:${Math.round(cfg.titleY * scale)}px;font-size:${fontPx}px;max-width:${txtMaxW}px;">${title}</div>
                <div class="position-absolute fw-semibold text-dark text-truncate" style="left:${txtLeft}px;top:${Math.round(cfg.varY * scale)}px;font-size:${fontPx}px;max-width:${txtMaxW}px;">${attrs}</div>
                ${mrp ? `<div class="position-absolute text-muted text-decoration-line-through" style="left:${txtLeft}px;top:${Math.round(cfg.mrpY * scale)}px;font-size:${Math.max(8.5, fontPx - 1.5)}px;">MRP: ${mrp}</div>` : ''}
                ${sp ? `<div class="position-absolute fw-bold text-success" style="left:${txtLeft}px;top:${Math.round(cfg.spY * scale)}px;font-size:${fontPx}px;">Disc: ${sp}</div>` : ''}
            </div>
        `;
    }

    function renderTscLivePreview(cfg) {
        const stage = document.getElementById('tscLivePreviewStage');
        const badge = document.getElementById('tscPreviewBadge');
        const caption = document.getElementById('tscPreviewCaption');
        if (!stage) return;

        const isDual = cfg.columns === 2;
        if (badge) badge.textContent = `${isDual ? 'Dual Roll' : 'Single Roll'} ${cfg.widthMm}mm × ${cfg.heightMm}mm`;
        if (caption) caption.innerHTML = `<i class="bi bi-info-circle me-1"></i>Simulating physical ${cfg.columns}-column TSC thermal sticker roll layout.`;

        const sampleContainer = lastActiveQrBatchModal || document.querySelector('[id^="modalQrBatch_"]');
        const sampleCards = sampleContainer ? sampleContainer.querySelectorAll('.qr-label-card') : [];
        const c1 = sampleCards[0]?.dataset || {};
        const c2 = sampleCards[1]?.dataset || c1;

        const d1 = {
            productName: c1.productName || 'GRM WAREHOUSE',
            attributes: c1.attributes || 'S-BLUE',
            mrp: c1.mrp || '₹1,000',
            sellingPrice: c1.sellingPrice || '₹500',
            qrCode: c1.qrCode || 'WMF92601',
            colIndex: 1
        };
        const d2 = {
            productName: c2.productName || 'GRM WAREHOUSE',
            attributes: c2.attributes || 'S-BLUE',
            mrp: c2.mrp || '₹1,000',
            sellingPrice: c2.sellingPrice || '₹500',
            qrCode: c2.qrCode || 'WMF92602',
            colIndex: 2
        };

        stage.innerHTML = renderTscStickerHtml(cfg, d1, 'Left Sticker #1') + (isDual ? renderTscStickerHtml(cfg, d2, 'Right Sticker #2') : '');
        stage.querySelectorAll('.tsc-qr-target').forEach(el => {
            const qrPx = parseInt(el.dataset.qrPx || '48', 10);
            el.innerHTML = '';
            new QRCode(el, {
                text: el.dataset.code || 'QR',
                width: Math.max(28, qrPx - 4),
                height: Math.max(28, qrPx - 4),
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    }

    function applyTscConfigToModal(modalEl) {
        if (!modalEl) return;
        const cfg = getSavedTscConfig();
        const summary = modalEl.querySelector('.tsc-active-summary');
        if (summary) {
            summary.textContent = `${cfg.columns === 2 ? 'Dual Roll' : 'Single Roll'} ${cfg.widthMm}mm × ${cfg.heightMm}mm · QR Size ${cfg.qrSize}`;
        }
        const labelCards = modalEl.querySelectorAll('.qr-label-card');
        if (labelCards.length) {
            const gridRow = labelCards[0].closest('.row');
            if (gridRow) {
                gridRow.className = 'd-flex flex-wrap gap-3 align-items-start';
            }
        }
        labelCards.forEach((card, idx) => {
            const colWrap = card.closest('[class*="col-"]');
            if (colWrap) {
                colWrap.className = 'd-inline-flex';
            }
            const d = card.dataset;
            card.className = 'qr-label-card';
            card.innerHTML = renderTscStickerHtml(cfg, {
                productName: d.productName || '',
                attributes: d.attributes || '',
                mrp: d.mrp || '',
                sellingPrice: d.sellingPrice || '',
                qrCode: d.qrCode || '',
                vid: d.vid || '',
                colIndex: (cfg.columns === 2 && idx % 2 === 1) ? 2 : 1
            });
            card.querySelectorAll('.tsc-qr-target').forEach(el => {
                const qrPx = parseInt(el.dataset.qrPx || '48', 10);
                el.innerHTML = '';
                new QRCode(el, {
                    text: el.dataset.code || '',
                    width: Math.max(28, qrPx - 4),
                    height: Math.max(28, qrPx - 4),
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    }

    document.querySelectorAll('[id^="modalQrBatch_"]').forEach(function(modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            lastActiveQrBatchModal = modalEl;
            applyTscConfigToModal(modalEl);
        });
    });

    const tscConfigModalEl = document.getElementById('modalTscPrinterConfig');
    if (tscConfigModalEl) {
        tscConfigModalEl.addEventListener('shown.bs.modal', function () {
            const cfg = getSavedTscConfig();
            writeTscConfigToInputs(cfg);
            renderTscLivePreview(cfg);
        });
        tscConfigModalEl.addEventListener('input', function (e) {
            if (e.target.classList.contains('tsc-cfg-input')) {
                renderTscLivePreview(readTscConfigFromInputs());
            }
        });
        tscConfigModalEl.addEventListener('change', function (e) {
            if (e.target.classList.contains('tsc-cfg-input')) {
                renderTscLivePreview(readTscConfigFromInputs());
            }
        });
    }

    document.getElementById('btnSaveTscConfig')?.addEventListener('click', function () {
        const cfg = readTscConfigFromInputs();
        try {
            localStorage.setItem(TSC_CFG_STORAGE_KEY, JSON.stringify(cfg));
        } catch (err) {}
        document.querySelectorAll('[id^="modalQrBatch_"]').forEach(m => applyTscConfigToModal(m));
        if (lastActiveQrBatchModal) {
            const badge = lastActiveQrBatchModal.querySelector('.qr-config-saved-badge');
            if (badge) {
                badge.classList.remove('d-none');
                setTimeout(() => badge.classList.add('d-none'), 3000);
            }
        }
        const cfgInst = bootstrap.Modal.getInstance(tscConfigModalEl);
        if (tscConfigModalEl && lastActiveQrBatchModal) {
            tscConfigModalEl.addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(lastActiveQrBatchModal).show();
            }, { once: true });
        }
        cfgInst?.hide();
    });

    document.getElementById('btnTscTestPrint')?.addEventListener('click', function () {
        const cfg = readTscConfigFromInputs();
        const sampleCard = (lastActiveQrBatchModal || document.querySelector('[id^="modalQrBatch_"]'))?.querySelector('.qr-label-card');
        const qrImg = document.querySelector('#tscLivePreviewStage .tsc-preview-qr img, #tscLivePreviewStage .tsc-preview-qr canvas');
        const qrSrc = qrImg ? (qrImg.src || (qrImg.toDataURL ? qrImg.toDataURL() : '')) : '';
        const title = sampleCard?.dataset.productName || 'GRM WAREHOUSE';
        const attrs = sampleCard?.dataset.attributes || 'S-BLUE';
        const sp = sampleCard?.dataset.sellingPrice || '₹500';
        const code = sampleCard?.dataset.qrCode || 'WMF92601';
        const win = window.open('', '_blank', 'width=420,height=320');
        if (!win) return;
        win.document.write(`<!doctype html><html><head><title>Test Print</title><style>@page{size:${cfg.widthMm}mm ${cfg.heightMm}mm;margin:0}body{margin:0;font-family:sans-serif;padding:4px;display:flex;align-items:center;gap:8px;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm;box-sizing:border-box}</style></head><body><div style="text-align:center">${qrSrc ? `<img src="${qrSrc}" style="width:48px;height:48px">` : ''}<div style="font-size:8px;font-family:monospace;font-weight:700">${code}</div></div><div style="font-size:9px;line-height:1.25"><div style="font-weight:700">${title}</div><div>${attrs}</div><div style="font-weight:700;color:#15803d">${sp}</div></div><script>window.onload=function(){window.print();}<\/script></body></html>`);
        win.document.close();
    });

    document.addEventListener('click', function (e) {
        const printBtn = e.target.closest('.btn-print-tsc-batch');
        if (!printBtn) return;
        const genId = printBtn.getAttribute('data-generation-id');
        const modalEl = document.getElementById('modalQrBatch_' + genId);
        if (!modalEl) return;
        applyTscConfigToModal(modalEl);

        setTimeout(() => {
            const cfg = getSavedTscConfig();
            const cards = Array.from(modalEl.querySelectorAll('.qr-label-card'));
            if (!cards.length) {
                showDraftModernAlert('No QR labels found to print.');
                return;
            }
            const pageW = cfg.columns === 2 ? (cfg.widthMm * 2 + cfg.gapMm) : cfg.widthMm;
            const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const stickerScale = 0.45; // dots (~203dpi) to mm-friendly print px
            const stickersHtml = cards.map((card, idx) => {
                const d = card.dataset;
                const qrEl = card.querySelector('.tsc-qr-target canvas, .tsc-qr-target img');
                const qrSrc = qrEl ? (qrEl.tagName === 'CANVAS' ? qrEl.toDataURL() : qrEl.src) : '';
                const shiftMm = (cfg.columns === 2 && idx % 2 === 1) ? (cfg.col2Shift * 0.125) : 0;
                const qrMm = Math.max(8, Math.min(18, cfg.qrSize * 3.4));
                const qrLeftMm = (cfg.qrX * 0.125) + shiftMm;
                const qrTopMm = cfg.qrY * 0.125;
                const subLeftMm = (cfg.subX * 0.125) + shiftMm;
                const subTopMm = cfg.subY * 0.125;
                const txtLeftMm = (cfg.textX * 0.125) + shiftMm;
                const fontPt = Math.max(6, 4.5 + cfg.fontSize * 1.2);
                return `
                    <div class="sticker">
                        ${qrSrc ? `<img src="${qrSrc}" style="position:absolute;left:${qrLeftMm}mm;top:${qrTopMm}mm;width:${qrMm}mm;height:${qrMm}mm;">` : ''}
                        <div style="position:absolute;left:${subLeftMm}mm;top:${subTopMm}mm;font-family:monospace;font-size:6pt;font-weight:700;">${esc(d.qrCode)}</div>
                        <div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.titleY * 0.125}mm;font-size:${fontPt}pt;font-weight:700;color:#1d4ed8;white-space:nowrap;overflow:hidden;max-width:${Math.max(12, cfg.widthMm - txtLeftMm - 1)}mm;">${esc(d.productName)}</div>
                        <div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.varY * 0.125}mm;font-size:${fontPt}pt;font-weight:600;white-space:nowrap;overflow:hidden;max-width:${Math.max(12, cfg.widthMm - txtLeftMm - 1)}mm;">${esc(d.attributes)}</div>
                        ${d.mrp ? `<div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.mrpY * 0.125}mm;font-size:${Math.max(5.5, fontPt - 1)}pt;color:#666;text-decoration:line-through;">MRP: ${esc(d.mrp)}</div>` : ''}
                        ${d.sellingPrice ? `<div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.spY * 0.125}mm;font-size:${fontPt}pt;font-weight:700;color:#15803d;">Disc: ${esc(d.sellingPrice)}</div>` : ''}
                    </div>
                `;
            }).join('');

            const win = window.open('', '_blank', 'width=760,height=600');
            if (!win) return;
            win.document.write(`<!doctype html><html><head><title>Print QR Labels</title><style>@page{size:${pageW}mm ${cfg.heightMm}mm;margin:0}body{margin:0;font-family:sans-serif;display:flex;flex-wrap:wrap;gap:${cfg.gapMm}mm;width:${pageW}mm}.sticker{position:relative;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm;overflow:hidden;box-sizing:border-box;page-break-inside:avoid}@media screen{body{padding:12px;background:#f3f4f6}.sticker{background:#fff;border:1px dashed #cbd5e1}}</style></head><body>${stickersHtml}<script>window.onload=function(){window.print();}<\/script></body></html>`);
            win.document.close();
        }, 80);
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

    // 3. In-panel AJAX Add to Inventory (supports single or multiple selected QR batches)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-add-inventory-draft');
        if (!btn) return;

        const genId = btn.getAttribute('data-generation-id');
        const mappingBillId = btn.getAttribute('data-mapping-bill-id');
        const modalEl = btn.closest('.modal');
        const whSelect = document.getElementById('whSelect_' + genId);
        const stockTarget = whSelect ? whSelect.value : '';
        if (!stockTarget) {
            showDraftModernAlert('Please select a Concern / Warehouse in "Stock to:" first.');
            return;
        }

        const checkedGenIds = modalEl
            ? Array.from(modalEl.querySelectorAll('.qr-stock-select-chk:checked')).map(cb => cb.value).filter(Boolean)
            : [];
        const targetGenIds = checkedGenIds.length ? checkedGenIds : [genId];

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Adding...';

        const formData = new FormData();
        formData.append('generation_id', targetGenIds[0]);
        formData.append('generation_ids', targetGenIds.join(','));
        formData.append('stock_target', stockTarget);
        if (stockTarget.startsWith('wh:')) {
            formData.append('warehouse_id', stockTarget.slice(3));
        }
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
                targetGenIds.forEach(function(gid) {
                    const statusEl = document.getElementById('modalStockStatus_' + gid);
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span>';
                    }
                    document.querySelectorAll('.qr-status-badge-' + gid).forEach(function(el) {
                        el.innerHTML = '<span class="badge bg-success">Stocked</span>';
                    });
                });
                if (modalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                showDraftModernAlert('Success: ' + (data.message || 'Stock added to inventory!'));
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add Selected to Inventory';
                showDraftModernAlert('Error: ' + (data.message || 'Could not add stock.'));
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add Selected to Inventory';
            showDraftModernAlert('Server error: ' + err.message);
        });
    });

    // 4. Fabric Roll Rows Dynamic Addition & AJAX Save
    const rollBody = document.getElementById('mappingRollRows');
    const defaultRollHsn = document.getElementById('defaultRollHsn');
    const defaultRollGst = document.getElementById('defaultRollGst');

    if (rollBody) {
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
                    <td></td>
                    <td><input class="form-control form-control-sm font-monospace" name="rolls[${nextRollIndex}][roll_code]" placeholder="ROLL-${String(nextRollIndex + 1).padStart(3, '0')}"></td>
                    <td><input class="form-control form-control-sm" name="rolls[${nextRollIndex}][color]" placeholder="Navy Blue" required></td>
                    <td><input class="form-control form-control-sm" name="rolls[${nextRollIndex}][design_name]" placeholder="Solid, Printed..."></td>
                    <td><input class="form-control form-control-sm roll-num-dec roll-weight" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][weight_kg]" placeholder="22.50" required></td>
                    <td><input class="form-control form-control-sm roll-num-dec roll-rate" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][rate]" placeholder="150.00" required></td>
                    <td><input class="form-control form-control-sm roll-gst-input roll-num-dec" type="text" inputmode="decimal" autocomplete="off" name="rolls[${nextRollIndex}][gst_rate]" value="${gstVal}"></td>
                    <td>
                        <select class="form-select form-select-sm font-monospace roll-hsn-select" name="rolls[${nextRollIndex}][hsn_code]">
                            ${hsnOptionsHtml}
                        </select>
                    </td>
                `;
                const newHsnSelect = tr.querySelector('.roll-hsn-select');
                if (newHsnSelect && hsnVal) {
                    newHsnSelect.value = hsnVal;
                }
                rollBody.appendChild(tr);
                nextRollIndex++;
            }
        };

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

        if (defaultRollGst) {
            defaultRollGst.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });
        }

        // Fabric Rolls: Category / Subcategory cascade & auto-loaded Color chips
        const rollCatSel = document.getElementById('rollCategory');
        const rollSubSel = document.getElementById('rollSubcategory');
        const rollSsSel = document.getElementById('rollSubSubcategory');
        const rollChipsWrap = document.getElementById('rollColorChipsWrap');
        const rollChipsEl = document.getElementById('rollColorChips');

        function renderRollColorChips() {
            if (!rollCatSel || !rollChipsWrap || !rollChipsEl) return;
            const cName = rollCatSel.selectedOptions[0]?.value ? rollCatSel.selectedOptions[0].textContent.trim().toLowerCase() : '';
            const sName = rollSubSel?.selectedOptions[0]?.value ? rollSubSel.selectedOptions[0].textContent.trim().toLowerCase() : '';
            const ssName = rollSsSel?.selectedOptions[0]?.value ? rollSsSel.selectedOptions[0].textContent.trim().toLowerCase() : '';

            if (!cName) {
                rollChipsWrap.classList.add('d-none');
                rollChipsEl.innerHTML = '';
                return;
            }
            rollChipsWrap.classList.remove('d-none');

            let attr = null;
            if (cName && sName && ssName && typeof draftCategoryAttrMap !== 'undefined' && draftCategoryAttrMap[`${cName}|${sName}|${ssName}`]) {
                attr = draftCategoryAttrMap[`${cName}|${sName}|${ssName}`];
            } else if (cName && sName && typeof draftCategoryAttrMap !== 'undefined' && draftCategoryAttrMap[`${cName}|${sName}`]) {
                attr = draftCategoryAttrMap[`${cName}|${sName}`];
            } else if (cName && typeof draftCategoryAttrMap !== 'undefined' && draftCategoryAttrMap[cName]) {
                attr = draftCategoryAttrMap[cName];
            }

            const masterColors = typeof draftMasterColors !== 'undefined' ? draftMasterColors : [];
            const colorList = (attr && attr.colors && attr.colors.length) ? attr.colors : masterColors;
            rollChipsEl.innerHTML = colorList.length
                ? colorList.map(col => `<button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small roll-color-chip" data-val="${String(col).replace(/"/g, '&quot;')}">${col}</button>`).join('')
                : '<span class="small text-muted">No catalog colors for this category yet — type directly in Color / Shade below.</span>';
        }

        rollCatSel?.addEventListener('change', function() {
            const catId = this.value;
            if (rollSubSel) {
                Array.from(rollSubSel.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = opt.dataset.parent !== catId;
                    if (opt.hidden && opt.selected) opt.selected = false;
                });
                rollSubSel.value = '';
            }
            if (rollSsSel) {
                Array.from(rollSsSel.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = true;
                    if (opt.selected) opt.selected = false;
                });
                rollSsSel.value = '';
            }
            renderRollColorChips();
        });

        rollSubSel?.addEventListener('change', function() {
            const subId = this.value;
            if (rollSsSel) {
                Array.from(rollSsSel.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = opt.dataset.parent !== subId;
                    if (opt.hidden && opt.selected) opt.selected = false;
                });
                rollSsSel.value = '';
            }
            renderRollColorChips();
        });

        rollSsSel?.addEventListener('change', renderRollColorChips);

        rollChipsEl?.addEventListener('click', function(e) {
            const chip = e.target.closest('.roll-color-chip');
            if (!chip) return;
            const colorVal = chip.dataset.val || '';
            const emptyColorInp = Array.from(rollBody.querySelectorAll('[name$="[color]"]')).find(inp => !inp.value.trim());
            if (emptyColorInp) {
                emptyColorInp.value = colorVal;
                emptyColorInp.focus();
            } else {
                addRolls(1);
                const lastRow = rollBody.querySelector('tr:last-child [name$="[color]"]');
                if (lastRow) lastRow.value = colorVal;
            }
        });
    }

    const draftRollsForm = document.getElementById('mappingDraftRollsForm');
    if (draftRollsForm) {
        draftRollsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const fabricNameInput = draftRollsForm.querySelector('[name="fabric_name"]');
            const fabricName = (fabricNameInput?.value || '').trim();
            if (!fabricName) {
                showDraftModernAlert('Validation Error: Please enter Fabric / Material Name.');
                fabricNameInput?.focus();
                return;
            }

            const rRows = draftRollsForm.querySelectorAll('#mappingRollRows tr');
            if (rRows.length === 0) {
                showDraftModernAlert('Validation Error: Please add at least one fabric roll.');
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
                    showDraftModernAlert('Validation Error: Please enter Color / Shade for roll #' + (i + 1) + '.');
                    colorInput?.focus();
                    return;
                }
                if (weightVal <= 0) {
                    showDraftModernAlert('Validation Error: Please enter a valid Weight (kg) for roll #' + (i + 1) + '.');
                    weightInput?.focus();
                    return;
                }
                if (rateVal <= 0) {
                    showDraftModernAlert('Validation Error: Please enter a valid Rate (₹/kg) for roll #' + (i + 1) + '.');
                    rateInput?.focus();
                    return;
                }
                if (gstVal < 0 || gstVal > 100) {
                    showDraftModernAlert('Validation Error: GST rate must be between 0 and 100% on roll #' + (i + 1) + '.');
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
                    showDraftModernAlert('Success: ' + data.message);
                    window.location.reload();
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Generate &amp; Save';
                    }
                    showDraftModernAlert('Error: ' + (data.message || 'Failed to save rolls.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Generate &amp; Save';
                }
                showDraftModernAlert('Server error: ' + err.message);
            });
        });
    }

    // 5. AJAX Move to Purchase Handlers
    document.querySelectorAll('form[action$="/move"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
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
                    const alertModalEl = document.getElementById('draftModernAlertModal');
                    if (alertModalEl) {
                        alertModalEl.addEventListener('hidden.bs.modal', function () {
                            window.location.reload();
                        }, { once: true });
                    }
                    showDraftModernAlert('Success: ' + (data.message || 'Moved to Purchases successfully.'), 'Success', 'success');
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase';
                    }
                    showDraftModernAlert('Error: ' + (data.message || 'Failed to move to purchases.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase';
                }
                showDraftModernAlert('Server error: ' + err.message);
            });
        });
    });

    // 6. AJAX Generate All QRs
    document.querySelectorAll('form[action$="/generate-all-qr"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
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
                    showDraftModernAlert('Success: ' + data.message);
                    window.location.reload();
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-qr-code me-1"></i>Generate QR';
                    }
                    showDraftModernAlert('Error: ' + (data.message || 'QR generation failed.'));
                }
            })
            .catch(function(err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-qr-code me-1"></i>Generate QR';
                }
                showDraftModernAlert('Server error: ' + err.message);
            });
        });
    });
    // Compact Variant Attribute Generator (Category -> Subcategory -> Sub-Subcategory + Dynamic Attributes + HSN Auto-Suggest + Direct Save)
    const draftCategoryAttrMap = <?= json_encode($categoryAttributesMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const draftMasterSizes = <?= json_encode(array_values(array_filter(array_map(static fn($s) => trim((string)($s['name'] ?? '')), $masterSizes ?? []))), JSON_UNESCAPED_UNICODE) ?>;
    const draftMasterColors = <?= json_encode(array_values(array_filter(array_map(static fn($c) => trim((string)($c['name'] ?? '')), $masterColors ?? []))), JSON_UNESCAPED_UNICODE) ?>;
    const draftHsnList = <?= json_encode($hsnList ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const draftCatalogProductsMap = <?= json_encode((static function () use ($existingProducts) {
        $map = [];
        foreach (($existingProducts ?? []) as $ep) {
            $k = mb_strtolower(trim((string)($ep['name'] ?? '')));
            if ($k === '') continue;
            $map[$k] = [
                'name' => $ep['name'] ?? '',
                'category_name' => $ep['category_name'] ?? '',
                'subcategory_name' => $ep['subcategory_name'] ?? '',
                'sub_subcategory_name' => $ep['sub_subcategory_name'] ?? '',
                'hsn_code' => $ep['hsn_code'] ?? '',
                'gst_percentage' => $ep['gst_percentage'] ?? '',
            ];
        }
        return $map;
    })(), JSON_UNESCAPED_UNICODE) ?>;
    const draftRollProductsMap = <?= json_encode((static function () use ($existingRollProducts) {
        $map = [];
        foreach (($existingRollProducts ?? []) as $rp) {
            $k = mb_strtolower(trim((string)($rp['name'] ?? '')));
            if ($k === '') continue;
            $map[$k] = [
                'name' => $rp['name'] ?? '',
                'hsn_code' => $rp['hsn_code'] ?? '',
                'gst_percentage' => $rp['gst_percentage'] ?? '',
            ];
        }
        return $map;
    })(), JSON_UNESCAPED_UNICODE) ?>;
    const savedExistingVariantKeys = new Set(<?= json_encode(array_values(array_map(static function ($it) {
        return mb_strtolower(trim((string)($it['item_name'] ?? '')) . '|' . trim((string)($it['size'] ?? '')) . '|' . trim((string)($it['color'] ?? '')) . '|' . (int)($it['category_id'] ?? 0));
    }, $items ?? [])), JSON_UNESCAPED_UNICODE) ?>);

    const dedupeDraftList = (arr) => {
        const seen = new Set();
        const out = [];
        for (const v of (arr || [])) {
            const s = String(v || '').trim();
            if (!s) continue;
            const k = s.toLowerCase();
            if (seen.has(k)) continue;
            seen.add(k);
            out.push(s);
        }
        return out;
    };

    function renderDraftAttributeChips(card) {
        if (!card) return;
        const catSel = card.querySelector('.gen-category');
        const subSel = card.querySelector('.gen-subcategory');
        const ssSel = card.querySelector('.gen-subsubcategory');
        const sizeWrap = card.querySelector('.gen-size-chips');
        const colorWrap = card.querySelector('.gen-color-chips');
        if (!sizeWrap || !colorWrap) return;

        const cName = (catSel && catSel.selectedOptions[0]?.value ? catSel.selectedOptions[0].textContent.trim().toLowerCase() : '');
        const sName = (subSel && subSel.selectedOptions[0]?.value ? subSel.selectedOptions[0].textContent.trim().toLowerCase() : '');
        const ssName = (ssSel && ssSel.selectedOptions[0]?.value ? ssSel.selectedOptions[0].textContent.trim().toLowerCase() : '');

        const attrCols = card.querySelectorAll('.gen-attributes-col');
        const customWrap = card.querySelector('.gen-custom-attrs-wrap');
        if (!cName) {
            attrCols.forEach(col => col.classList.add('d-none'));
            sizeWrap.innerHTML = '';
            colorWrap.innerHTML = '';
            if (customWrap) {
                customWrap.classList.add('d-none');
                customWrap.innerHTML = '';
            }
            return;
        }
        attrCols.forEach(col => col.classList.remove('d-none'));

        let attr = null;
        if (cName && sName && ssName && draftCategoryAttrMap[`${cName}|${sName}|${ssName}`]) {
            attr = draftCategoryAttrMap[`${cName}|${sName}|${ssName}`];
        } else if (cName && sName && draftCategoryAttrMap[`${cName}|${sName}`]) {
            attr = draftCategoryAttrMap[`${cName}|${sName}`];
        } else if (cName && draftCategoryAttrMap[cName]) {
            attr = draftCategoryAttrMap[cName];
        }

        const sizeList = dedupeDraftList((attr && attr.sizes && attr.sizes.length) ? attr.sizes : draftMasterSizes);
        const colorList = dedupeDraftList((attr && attr.colors && attr.colors.length) ? attr.colors : draftMasterColors);
        const customGroups = (attr && Array.isArray(attr.custom_attributes)) ? attr.custom_attributes : [];

        const activeSizes = new Set((card.querySelector('.gen-sizes-input')?.value || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean));
        const activeColors = new Set((card.querySelector('.gen-colors-input')?.value || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean));

        sizeWrap.innerHTML = sizeList.length
            ? sizeList.map(sz => `<button type="button" class="btn btn-xs ${activeSizes.has(sz.toLowerCase()) ? 'btn-primary' : 'btn-outline-secondary'} py-0 px-2 small gen-size-chip" data-val="${sz.replace(/"/g, '&quot;')}">${sz}</button>`).join('')
            : '<span class="small text-muted">No catalog sizes for this category yet</span>';

        colorWrap.innerHTML = colorList.length
            ? colorList.map(col => `<button type="button" class="btn btn-xs ${activeColors.has(col.toLowerCase()) ? 'btn-primary' : 'btn-outline-secondary'} py-0 px-2 small gen-color-chip" data-val="${col.replace(/"/g, '&quot;')}">${col}</button>`).join('')
            : '<span class="small text-muted">No catalog colors for this category yet</span>';

        if (customWrap) {
            const escAttr = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            if (customGroups.length) {
                customWrap.innerHTML = '<div class="row g-2 mt-1">' + customGroups.map(g => {
                    const gName = escAttr(g.name);
                    const chips = (g.values || []).map(val => {
                        const vEsc = escAttr(val);
                        return `<button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small gen-custom-attr-chip" data-attr-name="${gName}" data-val="${vEsc}">${vEsc}</button>`;
                    }).join('');
                    return `
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">${gName} <span class="text-muted fw-normal">(auto-loaded from Category)</span></label>
                            <div class="d-flex flex-wrap gap-1">${chips}</div>
                        </div>
                    `;
                }).join('') + '</div>';
                customWrap.classList.remove('d-none');
            } else {
                customWrap.innerHTML = '';
                customWrap.classList.add('d-none');
            }
        }
    }

    document.querySelectorAll('.tp-product-card').forEach(renderDraftAttributeChips);

    // HSN Prefix Auto-Suggest for .gen-hsn
    const draftHsnDropdown = document.createElement('div');
    draftHsnDropdown.id = 'draftHsnSuggestDropdown';
    draftHsnDropdown.className = 'list-group shadow-lg border';
    draftHsnDropdown.style.cssText = 'position:fixed;z-index:2050;display:none;max-height:220px;overflow-y:auto;overscroll-behavior:contain;min-width:260px;background:#fff;border-radius:0.5rem;';
    document.body.appendChild(draftHsnDropdown);

    let activeDraftHsnInput = null;
    function hideDraftHsnDropdown() {
        draftHsnDropdown.style.display = 'none';
        draftHsnDropdown.innerHTML = '';
        activeDraftHsnInput = null;
    }

    function applyDraftHsnItem(input, item) {
        if (!input || !item) return;
        input.value = item.hsn_code;
        const card = input.closest('.tp-product-card');
        if (card) {
            const gstInp = card.querySelector('.gen-gst');
            if (gstInp && item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') {
                gstInp.value = String(item.gst_rate);
            }
            const catName = String(item.category_name || '').trim().toLowerCase();
            if (catName) {
                const catSel = card.querySelector('.gen-category');
                const subSel = card.querySelector('.gen-subcategory');
                const ssSel = card.querySelector('.gen-subsubcategory');
                const catOpt = Array.from(catSel?.options || []).find(o => o.value && o.textContent.trim().toLowerCase() === catName);
                if (catOpt && catSel) {
                    catSel.value = catOpt.value;
                    catSel.dispatchEvent(new Event('change', { bubbles: true }));
                    const subName = String(item.subcategory_name || '').trim().toLowerCase();
                    const subOpt = Array.from(subSel?.options || []).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === subName);
                    if (subOpt && subSel) {
                        subSel.value = subOpt.value;
                        subSel.dispatchEvent(new Event('change', { bubbles: true }));
                        const ssName = String(item.sub_subcategory_name || '').trim().toLowerCase();
                        const ssOpt = Array.from(ssSel?.options || []).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === ssName);
                        if (ssOpt && ssSel) ssSel.value = ssOpt.value;
                    }
                }
            }
        }
        hideDraftHsnDropdown();
    }

    function showDraftHsnDropdown(input, forceShowAll = false) {
        if (!input || !draftHsnList.length) {
            hideDraftHsnDropdown();
            return;
        }
        const q = forceShowAll ? '' : input.value.trim().toUpperCase();
        let matches;
        if (!q) {
            matches = draftHsnList.slice(0, 120);
        } else {
            const prefix = [];
            const other = [];
            for (const it of draftHsnList) {
                const c = String(it.hsn_code || '').toUpperCase();
                const d = String(it.description || '').toUpperCase();
                if (c.startsWith(q)) prefix.push(it);
                else if (c.includes(q) || d.includes(q)) other.push(it);
            }
            matches = prefix.concat(other).slice(0, 80);
        }
        if (!matches.length) {
            hideDraftHsnDropdown();
            return;
        }
        activeDraftHsnInput = input;
        const rect = (input.closest('.input-group') || input).getBoundingClientRect();
        draftHsnDropdown.style.top = `${rect.bottom + 2}px`;
        draftHsnDropdown.style.left = `${Math.min(rect.left, window.innerWidth - 310)}px`;
        draftHsnDropdown.style.width = `${Math.max(rect.width, 290)}px`;
        draftHsnDropdown.style.maxHeight = '240px';
        draftHsnDropdown.style.overflowY = 'auto';
        draftHsnDropdown.innerHTML = '';
        matches.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1 px-2.5 text-start small';
            const gstText = (item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') ? `${Number(item.gst_rate)}%` : 'N/A';
            btn.innerHTML = `<div class="d-flex justify-content-between"><span class="font-monospace fw-bold">${item.hsn_code}</span><span class="badge bg-light text-dark border">${gstText}</span></div>`
                + (item.description ? `<div class="text-truncate text-muted" style="font-size:0.75rem;">${String(item.description).slice(0, 48)}</div>` : '');
            btn.addEventListener('mousedown', ev => { ev.preventDefault(); applyDraftHsnItem(input, item); });
            draftHsnDropdown.appendChild(btn);
        });
        draftHsnDropdown.style.display = 'block';
    }

    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('gen-hsn')) return;
        showDraftHsnDropdown(e.target, false);
    });
    document.addEventListener('focusin', function (e) {
        if (!e.target.classList.contains('gen-hsn')) return;
        showDraftHsnDropdown(e.target, e.target.value.trim() === '');
    });
    document.addEventListener('click', function (e) {
        const toggleBtn = e.target.closest('.btn-hsn-dropdown-toggle');
        if (toggleBtn) {
            const inp = toggleBtn.closest('.input-group')?.querySelector('.gen-hsn');
            if (inp) {
                if (draftHsnDropdown.style.display === 'block' && activeDraftHsnInput === inp) {
                    hideDraftHsnDropdown();
                } else {
                    inp.focus();
                    showDraftHsnDropdown(inp, true);
                }
            }
            return;
        }
        if (e.target !== activeDraftHsnInput && !draftHsnDropdown.contains(e.target)) {
            hideDraftHsnDropdown();
        }
    });
    window.addEventListener('scroll', function (ev) {
        if (ev.target === draftHsnDropdown || (ev.target && draftHsnDropdown.contains(ev.target))) return;
        hideDraftHsnDropdown();
    }, true);

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('tp-product-name-input')) {
            const card = e.target.closest('.tp-product-card');
            const prod = draftCatalogProductsMap[String(e.target.value || '').trim().toLowerCase()];
            if (card && prod) {
                const catSel = card.querySelector('.gen-category');
                const subSel = card.querySelector('.gen-subcategory');
                const ssSel = card.querySelector('.gen-subsubcategory');
                const catName = String(prod.category_name || '').trim().toLowerCase();
                if (catSel && catName) {
                    const catOpt = Array.from(catSel.options).find(o => o.value && o.textContent.trim().toLowerCase() === catName);
                    if (catOpt) {
                        catSel.value = catOpt.value;
                        catSel.dispatchEvent(new Event('change', { bubbles: true }));
                        const subName = String(prod.subcategory_name || '').trim().toLowerCase();
                        const subOpt = subName ? Array.from(subSel?.options || []).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === subName) : null;
                        if (subOpt && subSel) {
                            subSel.value = subOpt.value;
                            subSel.dispatchEvent(new Event('change', { bubbles: true }));
                            const ssName = String(prod.sub_subcategory_name || '').trim().toLowerCase();
                            const ssOpt = ssName ? Array.from(ssSel?.options || []).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === ssName) : null;
                            if (ssOpt && ssSel) {
                                ssSel.value = ssOpt.value;
                                ssSel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    }
                }
                const hsnInp = card.querySelector('.gen-hsn');
                const gstInp = card.querySelector('.gen-gst');
                if (hsnInp && prod.hsn_code) hsnInp.value = String(prod.hsn_code);
                if (gstInp && prod.gst_percentage !== '' && prod.gst_percentage !== null && prod.gst_percentage !== undefined) {
                    gstInp.value = String(parseFloat(prod.gst_percentage) || 0);
                }
            }
        } else if (e.target.id === 'fabricNameInput') {
            const roll = draftRollProductsMap[String(e.target.value || '').trim().toLowerCase()];
            if (roll) {
                const row = e.target.closest('tr') || document.querySelector('#mappingRollRows tr.mapping-roll-row');
                const gstInp = row?.querySelector('.roll-gst-input');
                const hsnSel = row?.querySelector('.roll-hsn-select');
                if (hsnSel && roll.hsn_code) {
                    const matchOpt = Array.from(hsnSel.options).find(o => String(o.value).trim() === String(roll.hsn_code).trim());
                    if (matchOpt) hsnSel.value = matchOpt.value;
                }
                if (gstInp && roll.gst_percentage !== '' && roll.gst_percentage !== null && roll.gst_percentage !== undefined) {
                    gstInp.value = String(parseFloat(roll.gst_percentage) || 0);
                }
            }
        } else if (e.target.classList.contains('gen-category')) {
            const card = e.target.closest('.tp-product-card');
            const catId = String(e.target.value || '');
            const subSel = card?.querySelector('.gen-subcategory');
            const ssSel = card?.querySelector('.gen-subsubcategory');
            if (subSel) {
                Array.from(subSel.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = Boolean(catId && String(opt.dataset.parent || '') === catId);
                    opt.hidden = !match;
                    opt.disabled = !match;
                    if (!match && opt.selected) opt.selected = false;
                });
                subSel.value = '';
            }
            if (ssSel) {
                Array.from(ssSel.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = true;
                    opt.disabled = true;
                    if (opt.selected) opt.selected = false;
                });
                ssSel.value = '';
            }
            renderDraftAttributeChips(card);
        } else if (e.target.classList.contains('gen-subcategory')) {
            const card = e.target.closest('.tp-product-card');
            const subId = String(e.target.value || '');
            const ssSel = card?.querySelector('.gen-subsubcategory');
            if (ssSel) {
                Array.from(ssSel.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = Boolean(subId && String(opt.dataset.parent || '') === subId);
                    opt.hidden = !match;
                    opt.disabled = !match;
                    if (!match && opt.selected) opt.selected = false;
                });
                ssSel.value = '';
            }
            renderDraftAttributeChips(card);
        } else if (e.target.classList.contains('gen-subsubcategory')) {
            renderDraftAttributeChips(e.target.closest('.tp-product-card'));
        }
    });

    function getSelectedDraftCustomAttributes(card) {
        if (!card) return [];
        const groupsMap = new Map();
        card.querySelectorAll('.gen-custom-attr-chip.btn-primary').forEach(ch => {
            const attrName = (ch.getAttribute('data-attr-name') || '').trim();
            const val = (ch.getAttribute('data-val') || '').trim();
            if (!attrName || !val) return;
            if (!groupsMap.has(attrName)) groupsMap.set(attrName, []);
            const arr = groupsMap.get(attrName);
            if (!arr.some(v => v.toLowerCase() === val.toLowerCase())) arr.push(val);
        });
        const out = [];
        groupsMap.forEach((values, name) => {
            if (values.length) out.push({ name, values });
        });
        return out;
    }

    function renderVariantRateTable(card) {
        if (!card) return;
        const wrap = card.querySelector('.gen-variant-rates-wrap');
        const rateBody = card.querySelector('.gen-variant-rates-body');
        if (!wrap || !rateBody) return;

        const sizes = dedupeDraftList((card.querySelector('.gen-sizes-input')?.value || '').split(','));
        const colors = dedupeDraftList((card.querySelector('.gen-colors-input')?.value || '').split(','));
        const customGroups = getSelectedDraftCustomAttributes(card);
        if (!sizes.length && !colors.length && !customGroups.length) {
            wrap.classList.add('d-none');
            rateBody.innerHTML = '';
            return;
        }

        const existingMap = {};
        rateBody.querySelectorAll('tr.gen-attr-rate-row').forEach(tr => {
            existingMap[tr.dataset.key || ''] = {
                qty: tr.querySelector('.attr-qty')?.value ?? '',
                rate: tr.querySelector('.attr-rate')?.value ?? '',
                mrp: tr.querySelector('.attr-mrp')?.value ?? '',
                sp: tr.querySelector('.attr-sp')?.value ?? ''
            };
        });

        const defQty = '1';
        const defRate = '';
        const defMrp = '';
        const defSp = '';

        const sList = sizes.length ? sizes : [''];
        const cList = colors.length ? colors : [''];
        let combos = [];
        sList.forEach(sz => {
            cList.forEach(col => {
                combos.push({ size: sz, color: col, customVals: [] });
            });
        });
        customGroups.forEach(g => {
            const next = [];
            combos.forEach(base => {
                g.values.forEach(val => {
                    next.push({
                        size: base.size,
                        color: base.color,
                        customVals: [...base.customVals, val]
                    });
                });
            });
            combos = next;
        });

        const rowsHtml = combos.map(combo => {
            const key = [combo.size, combo.color, ...combo.customVals].join('|||');
            const prev = existingMap[key] || { qty: defQty, rate: defRate, mrp: defMrp, sp: defSp };
            const label = [combo.size, combo.color, ...combo.customVals].filter(Boolean).join(' / ') || 'Standard';
            const customLabel = combo.customVals.join(' / ');
            return `
                <tr class="gen-attr-rate-row" data-key="${key.replace(/"/g, '&quot;')}" data-size="${combo.size.replace(/"/g, '&quot;')}" data-color="${combo.color.replace(/"/g, '&quot;')}" data-custom-label="${customLabel.replace(/"/g, '&quot;')}">
                    <td class="fw-semibold text-dark">${label}</td>
                    <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm attr-qty" value="${String(prev.qty || '1').replace(/"/g, '&quot;')}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm attr-rate" placeholder="0.00" value="${String(prev.rate || '').replace(/"/g, '&quot;')}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm attr-mrp" placeholder="0.00" value="${String(prev.mrp || '').replace(/"/g, '&quot;')}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm attr-sp" placeholder="0.00" value="${String(prev.sp || '').replace(/"/g, '&quot;')}"></td>
                </tr>
            `;
        });

        rateBody.innerHTML = rowsHtml.join('');
        wrap.classList.remove('d-none');
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('gen-sizes-input') || e.target.classList.contains('gen-colors-input')) {
            renderVariantRateTable(e.target.closest('.tp-product-card'));
        }
    });

    document.addEventListener('click', function (e) {
        const customChip = e.target.closest('.gen-custom-attr-chip');
        if (customChip) {
            customChip.classList.toggle('btn-primary');
            customChip.classList.toggle('btn-outline-secondary');
            renderVariantRateTable(customChip.closest('.tp-product-card'));
            return;
        }
        const chip = e.target.closest('.gen-size-chip, .gen-color-chip');
        if (chip) {
            const isSize = chip.classList.contains('gen-size-chip');
            const card = chip.closest('.tp-product-card');
            const input = card?.querySelector(isSize ? '.gen-sizes-input' : '.gen-colors-input');
            if (!input) return;
            const val = chip.getAttribute('data-val') || '';
            chip.classList.toggle('btn-primary');
            chip.classList.toggle('btn-outline-secondary');
            const current = dedupeDraftList(input.value.split(','));
            const existsIdx = current.findIndex(s => s.toLowerCase() === val.toLowerCase());
            if (chip.classList.contains('btn-primary')) {
                if (existsIdx === -1) current.push(val);
            } else if (existsIdx !== -1) {
                current.splice(existsIdx, 1);
            }
            input.value = current.join(', ');
            renderVariantRateTable(card);
            return;
        }

        const genBtn = e.target.closest('.btn-generate-variants');
        if (!genBtn) return;
        const card = genBtn.closest('.tp-product-card');
        if (!card) return;

        const pIndex = card.getAttribute('data-product-index') || '0';
        const pName = (card.querySelector('.tp-product-name-input')?.value || '').trim();
        if (!pName) {
            showDraftModernAlert('Please enter Product / Style Name first.');
            card.querySelector('.tp-product-name-input')?.focus();
            return;
        }

        const tbody = card.querySelector('.tp-variant-rows');
        const templateRow = tbody?.querySelector('.mapping-variant-row');
        if (!tbody || !templateRow) return;

        const catVal = card.querySelector('.gen-category')?.value || '';
        const subVal = card.querySelector('.gen-subcategory')?.value || '';
        const ssVal = card.querySelector('.gen-subsubcategory')?.value || '';
        if (!catVal) {
            showDraftModernAlert('Please select a Category first.');
            card.querySelector('.gen-category')?.focus();
            return;
        }

        const hsnVal = (card.querySelector('.gen-hsn')?.value || '').trim();
        const gstVal = card.querySelector('.gen-gst')?.value || '5';
        const qtyVal = '1';
        const rateVal = '';
        const mrpVal = '';
        const spVal = '';

        const attrRateRows = Array.from(card.querySelectorAll('.gen-attr-rate-row'));
        if (attrRateRows.length === 0) {
            showDraftModernAlert('Please select or type at least one Size or Color attribute to enter Qty, Purchase Rate, MRP & Selling Price.');
            card.querySelector('.gen-sizes-input')?.focus();
            return;
        }
        const attrRateMap = {};
        for (const rEl of attrRateRows) {
            const rMrpInp = rEl.querySelector('.attr-mrp');
            const rSpInp = rEl.querySelector('.attr-sp');
            const rQty = rEl.querySelector('.attr-qty')?.value || qtyVal;
            const rRate = rEl.querySelector('.attr-rate')?.value ?? rateVal;
            const rMrp = parseFloat(rMrpInp?.value || '0') || 0;
            const rSp = parseFloat(rSpInp?.value || '0') || 0;
            if (rMrp <= 0 || rSp <= 0) {
                showDraftModernAlert('Please enter valid MRP and Selling Price greater than 0 for each selected attribute.');
                (rMrp <= 0 ? rMrpInp : rSpInp)?.focus();
                return;
            }
            if (rSp > rMrp) {
                rMrpInp?.classList.add('is-invalid');
                rSpInp?.classList.add('is-invalid');
                showDraftModernAlert(`Validation Error: Selling Price (${formatCurrency(rSp)}) cannot exceed MRP (${formatCurrency(rMrp)}).`);
                rSpInp?.focus();
                return;
            }
            rMrpInp?.classList.remove('is-invalid');
            rSpInp?.classList.remove('is-invalid');
            attrRateMap[rEl.dataset.key || ''] = {
                qty: rQty,
                rate: rRate,
                mrp: String(rMrp),
                sp: String(rSp)
            };
        }

        const combos = [];
        const batchComboKeys = new Set();
        for (const rEl of attrRateRows) {
            const rawSz = (rEl.dataset.size || '').trim();
            const col = (rEl.dataset.color || '').trim();
            const customLabel = (rEl.dataset.customLabel || '').trim();
            const sz = [rawSz, customLabel].filter(Boolean).join(' / ');
            const comboKey = `${pName.toLowerCase()}|${sz.toLowerCase()}|${col.toLowerCase()}|${catVal}`;
            if (batchComboKeys.has(comboKey)) continue;
            if (savedExistingVariantKeys.has(comboKey)) {
                showDraftModernAlert(`Duplicate Variant: "${pName}" (${sz || 'Std'} / ${col || 'Std'}) is already in Saved Product Variants.`);
                return;
            }
            batchComboKeys.add(comboKey);
            const rowRates = attrRateMap[rEl.dataset.key || ''] || { qty: qtyVal, rate: rateVal, mrp: mrpVal, sp: spVal };
            combos.push({ size: sz, color: col, ...rowRates });
        }

        const rowCloneBase = templateRow.cloneNode(true);
        tbody.innerHTML = '';

        combos.forEach((combo, vIdx) => {
            const row = rowCloneBase.cloneNode(true);
            row.querySelectorAll('input, select').forEach(el => {
                if (el.name) {
                    el.name = el.name.replace(/products\[\d+\]\[variants\]\[\d+\]/, `products[${pIndex}][variants][${vIdx}]`);
                }
            });
            const szInp = row.querySelector('input[name*="[size]"]');
            const colInp = row.querySelector('input[name*="[color]"]');
            const qtyInp = row.querySelector('.map-qty');
            const rateInp = row.querySelector('.map-rate');
            const mrpInp = row.querySelector('.map-mrp');
            const spInp = row.querySelector('.map-sp');
            const gstInp = row.querySelector('.map-gst');
            const hsnSel = row.querySelector('.mapping-hsn-select');
            const catSel = row.querySelector('.mapping-category');
            const subSel = row.querySelector('.mapping-subcategory');
            const ssSel = row.querySelector('.mapping-subsubcategory');

            if (szInp) szInp.value = combo.size;
            if (colInp) colInp.value = combo.color;
            if (qtyInp) qtyInp.value = combo.qty;
            if (rateInp && combo.rate !== '') {
                const gstMode = card.querySelector('.gen-gst-mode:checked')?.value || 'EXCLUDED';
                const rawRate = parseFloat(combo.rate) || 0;
                const gstNum = parseFloat(gstVal) || 0;
                rateInp.value = (gstMode === 'INCLUDED' && gstNum > 0 && rawRate > 0)
                    ? (rawRate / (1 + (gstNum / 100))).toFixed(2)
                    : combo.rate;
            }
            if (mrpInp && combo.mrp !== '') mrpInp.value = combo.mrp;
            if (spInp && combo.sp !== '') spInp.value = combo.sp;
            if (gstInp) gstInp.value = gstVal;
            if (hsnSel && hsnVal) {
                if (!Array.from(hsnSel.options).some(o => o.value === hsnVal)) {
                    const opt = document.createElement('option');
                    opt.value = hsnVal;
                    opt.textContent = hsnVal;
                    hsnSel.appendChild(opt);
                }
                hsnSel.value = hsnVal;
            }

            if (catSel && catVal) {
                catSel.value = catVal;
                catSel.dispatchEvent(new Event('change', { bubbles: true }));
                if (subSel && subVal) {
                    subSel.value = subVal;
                    subSel.dispatchEvent(new Event('change', { bubbles: true }));
                    if (ssSel && ssVal) {
                        ssSel.value = ssVal;
                    }
                }
            }
            tbody.appendChild(row);
        });

        // Directly save into Saved Product Variants
        const form = document.getElementById('mappingDraftProductForm');
        if (form) {
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });

    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('gen-mrp') && !e.target.classList.contains('gen-sp')) return;
        const card = e.target.closest('.tp-product-card');
        if (!card) return;
        const mrpEl = card.querySelector('.gen-mrp');
        const spEl = card.querySelector('.gen-sp');
        const mrp = parseFloat(mrpEl?.value || '0') || 0;
        const sp = parseFloat(spEl?.value || '0') || 0;
        const invalid = (mrp > 0 && sp > 0 && sp > mrp);
        mrpEl?.classList.toggle('is-invalid', invalid);
        spEl?.classList.toggle('is-invalid', invalid);
    });
});
</script>
