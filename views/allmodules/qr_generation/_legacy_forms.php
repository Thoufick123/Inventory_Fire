<?php
/** Preserved custom Manual / Catalog QR forms. Available under Third-Party. */
?>
<div class="accordion mt-4" id="legacyQrForms">
    <div class="accordion-item">
        <h2 class="accordion-header" id="legacyManualHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#legacyManualCollapse" aria-expanded="false" aria-controls="legacyManualCollapse">
                <i class="bi bi-pencil-square me-2 text-primary"></i>Advanced: Manual QR Generation (Custom Hierarchy &amp; Brand)
            </button>
        </h2>
        <div id="legacyManualCollapse" class="accordion-collapse collapse" aria-labelledby="legacyManualHeading" data-bs-parent="#legacyQrForms">
            <div class="accordion-body">
                <form method="POST" action="<?= url('production/qr-generations/store-manual') ?>" id="formManualQr">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="third_party">

                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label fw-bold text-dark fs-6">Brand Name to Print on Labels <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-award-fill"></i></span>
                            <input type="text" name="brand_name" id="manBrandName" class="form-control text-uppercase fw-bold" placeholder="e.g. GRM APPARELS, MEKHA, RMT" value="GRM APPARELS" required oninput="updateManualPreview()">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" id="manCategoryId" class="form-select" onchange="onManualCategoryChanged()">
                                <option value="">-- Select Existing Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-code="<?= e($c['code'] ?? '') ?>"><?= e($c['name']) ?> (<?= e($c['code'] ?? '—') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">...or Create New Category Name</label>
                            <input type="text" name="new_category_name" id="manNewCatName" class="form-control" placeholder="e.g. Kids Wear, Men's Wear" oninput="updateManualPreview()">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subcategory</label>
                            <select name="subcategory_id" id="manSubcategoryId" class="form-select" onchange="onManualSubcategoryChanged()">
                                <option value="">-- Select Existing Subcategory --</option>
                                <?php foreach ($subcategories as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" data-cat-id="<?= $sc['category_id'] ?>" data-code="<?= e($sc['code'] ?? '') ?>"><?= e($sc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">...or Create New Subcategory Name</label>
                            <input type="text" name="new_subcategory_name" id="manNewSubcatName" class="form-control" placeholder="e.g. T-Shirt, Shirt, Jeans" oninput="updateManualPreview()">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub-Subcategory</label>
                            <select name="sub_subcategory_id" id="manSubSubcategoryId" class="form-select" onchange="updateManualPreview()">
                                <option value="">-- Select Existing Sub-Subcategory --</option>
                                <?php foreach ($subSubcategories as $ssc): ?>
                                    <option value="<?= $ssc['id'] ?>" data-subcat-id="<?= $ssc['subcategory_id'] ?>" data-code="<?= e($ssc['code'] ?? '') ?>"><?= e($ssc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold">...or Create New Sub-Subcategory Name</label>
                            <input type="text" name="new_sub_subcategory_name" id="manNewSubSubcatName" class="form-control" placeholder="e.g. Round Neck, Full Sleeve" oninput="updateManualPreview()">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Color</label>
                            <select name="color_id" class="form-select">
                                <option value="">-- None / Standard --</option>
                                <?php foreach ($colors as $col): ?><option value="<?= $col['id'] ?>"><?= e($col['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Design / Pattern</label>
                            <select name="design_id" class="form-select">
                                <option value="">-- None / Solid --</option>
                                <?php foreach ($designs as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Size</label>
                            <select name="size_id" class="form-select">
                                <option value="">-- None / Free Size --</option>
                                <?php foreach ($sizes as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Number of QR Labels <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="manQuantity" class="form-control form-control-lg fw-bold" min="1" max="1000" value="50" required oninput="updateManualPreview()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">MRP (₹)</label>
                            <input type="number" step="0.01" min="0" name="mrp" id="manMrp" class="form-control form-control-lg" value="499.00" oninput="updateManualPreview()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Selling Price (₹)</label>
                            <input type="number" step="0.01" min="0" name="selling_price" id="manSellingPrice" class="form-control form-control-lg" value="299.00" oninput="updateManualPreview()">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted font-monospace" id="formatFormula">[BRAND]-[CATEGORY]-[SEQ]</span>
                        <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Generate &amp; Save QR Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="legacyCatalogHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#legacyCatalogCollapse" aria-expanded="false" aria-controls="legacyCatalogCollapse">
                <i class="bi bi-journal-bookmark me-2 text-warning"></i>Advanced: From Existing Catalog / Product Variant
            </button>
        </h2>
        <div id="legacyCatalogCollapse" class="accordion-collapse collapse" aria-labelledby="legacyCatalogHeading" data-bs-parent="#legacyQrForms">
            <div class="accordion-body">
                <form method="POST" action="<?= url('production/qr-generations/store-catalog') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="third_party">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Brand Name to Print on Labels <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" class="form-control form-control-lg text-uppercase fw-bold" placeholder="e.g. GRM APPARELS" value="GRM APPARELS" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Parent Catalog / Style <span class="text-danger">*</span></label>
                        <select name="product_id" id="catModeProduct" class="form-select form-select-lg" required onchange="onCatalogModeSelected()">
                            <option value="">-- Choose Catalog / Style --</option>
                            <?php foreach ($catalogs as $p): ?>
                                <option value="<?= $p['id'] ?>" data-code="<?= e($p['code']) ?>"><?= e($p['code']) ?> — <?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Specific Variant (Optional)</label>
                        <select name="variant_id" id="catModeVariant" class="form-select">
                            <option value="">-- Whole Catalog / Base Style --</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Number of QR Labels <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control form-control-lg fw-bold" min="1" max="1000" value="50" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-lg fw-bold"><i class="bi bi-qr-code me-2"></i>Generate Catalog QR Batch</button>
                </form>
            </div>
        </div>
    </div>
</div>
