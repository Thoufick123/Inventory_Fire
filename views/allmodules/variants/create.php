<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add Product Variants</h2>
    </div>
    <div>
        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Product Variants
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Main Form Column -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="bi bi-diagram-3-fill me-2 text-primary"></i>Variant Creation Flow
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('production/variants/store') ?>" id="formCreateVariant">
                    <?= csrf_field() ?>

                    <!-- STEP 1 & 2: Category & Subcategory -->
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary rounded-circle px-2 py-1">1-2</span>
                            <h6 class="mb-0 fw-bold text-dark">Category Hierarchy</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Step 1: Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="varCategory" class="form-select form-select-lg" required onchange="onCategoryChanged()">
                                    <option value="">-- Choose Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?> (<?= e($cat['code'] ?? 'CAT') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Step 2: Subcategory</label>
                                <select name="subcategory_id" id="varSubcategory" class="form-select form-select-lg" onchange="onSubcategoryChanged()">
                                    <option value="">-- Choose Subcategory (Optional) --</option>
                                    <?php foreach ($subcategories as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" data-cat-id="<?= $sc['category_id'] ?>"><?= e($sc['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Parent Catalog -->
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary rounded-circle px-2 py-1">3</span>
                            <h6 class="mb-0 fw-bold text-dark">Parent Catalog / Product Style</h6>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Select Parent Catalog <span class="text-danger">*</span></label>
                            <select name="product_id" id="varCatalog" class="form-select form-select-lg" required onchange="onCatalogSelected()">
                                <option value="">-- Choose Catalog / Style --</option>
                                <?php foreach ($catalogs as $prod): ?>
                                <option value="<?= $prod['id'] ?>" 
                                        data-cat-id="<?= $prod['category_id'] ?>" 
                                        data-subcat-id="<?= $prod['subcategory_id'] ?>"
                                        data-name="<?= e($prod['name']) ?>"
                                        data-hsn="<?= e($prod['hsn_code'] ?? '6109') ?>"
                                        data-gst="<?= (float)($prod['gst_percentage'] ?? 5) ?>">
                                    <?= e($prod['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Even if multiple catalogs share identical colors and sizes, each catalog creates separate variant records and separate QR codes.</div>
                        </div>
                    </div>

                    <!-- STEP 4, 5, 6, 7: Applicable Attributes (Color, Design, Size) -->
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary rounded-circle px-2 py-1">4-7</span>
                                <h6 class="mb-0 fw-bold text-dark">Applicable Variant Attributes</h6>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalQuickAddDesign">
                                <i class="bi bi-plus-circle me-1"></i> Quick Add Design
                            </button>
                        </div>
                        <div class="row g-3">
                            <!-- Step 5: Color -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Step 5: Color</label>
                                <select name="color_id" id="varColor" class="form-select" onchange="triggerSkuPreview()">
                                    <option value="">-- No Color / Standard --</option>
                                    <?php foreach ($colors as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>"><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Step 6: Design / Pattern -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Step 6: Design / Pattern</label>
                                <select name="design_id" id="varDesign" class="form-select" onchange="triggerSkuPreview()">
                                    <option value="">-- No Design / Solid --</option>
                                    <?php foreach ($designs as $d): ?>
                                    <option value="<?= $d['id'] ?>" data-name="<?= e($d['name']) ?>" data-code="<?= e($d['code'] ?? '') ?>"><?= e($d['name']) ?> (<?= e($d['code'] ?? '—') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Step 7: Size -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Step 7: Size</label>
                                <select name="size_id" id="varSize" class="form-select" onchange="triggerSkuPreview()">
                                    <option value="">-- No Size / Free Size --</option>
                                    <?php foreach ($sizes as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-code="<?= e($s['code'] ?? '') ?>"><?= e($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Step 8: Live Duplicate Status Banner -->
                        <div class="mt-3">
                            <div id="duplicateStatusBox" class="alert alert-secondary py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-info-circle text-primary"></i>
                                <span>Select a Catalog and attributes to validate uniqueness.</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 9 & 11: Auto-generated Identifiers -->
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary rounded-circle px-2 py-1">9-11</span>
                            <h6 class="mb-0 fw-bold text-dark">Generated Identifiers &amp; QR Code</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Variant ID <span class="badge bg-secondary-subtle text-secondary ms-1">Auto</span></label>
                                <input type="text" name="variant_code" id="varVariantCode" class="form-control font-monospace fw-bold bg-light" value="<?= e($suggestedVariantCode) ?>" readonly>
                                <div class="form-text small">Human-readable internal identity (e.g. V001).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stable QR Identity <span class="badge bg-secondary-subtle text-secondary ms-1">Auto</span></label>
                                <input type="text" name="qr_code" id="varQrCode" class="form-control font-monospace fw-bold bg-light" placeholder="Auto-linked to Variant ID">
                                <div class="form-text small">Scannable QR identifier text.</div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 10: Pricing & GST -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary rounded-circle px-2 py-1">10</span>
                            <h6 class="mb-0 fw-bold text-dark">Pricing &amp; Tax Details</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">MRP (₹)</label>
                                <input type="number" step="0.01" min="0" name="mrp" id="varMrp" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Selling Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="selling_price" id="varSellingPrice" class="form-control fw-bold" value="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Production Cost (₹)</label>
                                <input type="number" step="0.01" min="0" name="production_cost" id="varProdCost" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">HSN Code</label>
                                <input type="text" name="hsn_code" id="varHsn" class="form-control font-monospace" value="6109">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" id="btnSubmitVariant" class="btn btn-primary btn-lg px-4 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Save Variant &amp; Generate QR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Preview Sidebar Column -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-eye-fill me-2 text-warning"></i>Live Variant Card Preview</h6>
            </div>
            <div class="card-body p-4 text-center">
                <div class="badge bg-primary px-3 py-1 font-monospace fs-6 mb-2" id="previewBadgeId"><?= e($suggestedVariantCode) ?></div>
                <h5 class="fw-bold text-dark mb-1" id="previewProdName">Select a Catalog</h5>

                <!-- QR Vector Mockup -->
                <div class="d-flex justify-content-center my-3">
                    <div id="previewQrBox" class="p-2 border rounded bg-white shadow-sm" style="width:140px;height:140px;display:flex;align-items:center;justify-content:center;">
                        <span class="text-muted small">QR Preview</span>
                    </div>
                </div>

                <div class="font-monospace fw-bold text-dark fs-6 mb-3" id="previewQrText">V-QR-PREVIEW</div>

                <!-- Attributes Tree -->
                <ul class="list-group list-group-flush text-start small mb-3">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Color:</span>
                        <strong id="previewColor">—</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Design:</span>
                        <strong id="previewDesign">—</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Size:</span>
                        <strong id="previewSize">—</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Selling Price:</span>
                        <strong class="text-success">₹<span id="previewPrice">0.00</span></strong>
                    </li>
                </ul>

                <div class="card bg-light border-0 text-start p-3 small text-muted">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    <strong>Collision Safe:</strong> Even if another catalog uses identical Red, Panda, and Size S, separate variant IDs and separate QR identities are preserved.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
let qrGenerator = null;

function onCategoryChanged() {
    const catId = document.getElementById('varCategory').value;
    const subcatSelect = document.getElementById('varSubcategory');
    const catalogSelect = document.getElementById('varCatalog');

    // Filter subcategories
    for (let opt of subcatSelect.options) {
        if (!opt.value) continue;
        const optCat = opt.getAttribute('data-cat-id');
        opt.style.display = (!catId || optCat === catId) ? '' : 'none';
    }
    subcatSelect.value = '';

    // Filter catalogs
    for (let opt of catalogSelect.options) {
        if (!opt.value) continue;
        const optCat = opt.getAttribute('data-cat-id');
        opt.style.display = (!catId || optCat === catId) ? '' : 'none';
    }
    catalogSelect.value = '';

    triggerSkuPreview();
}

function onSubcategoryChanged() {
    const subcatId = document.getElementById('varSubcategory').value;
    const catalogSelect = document.getElementById('varCatalog');

    for (let opt of catalogSelect.options) {
        if (!opt.value) continue;
        const optSubcat = opt.getAttribute('data-subcat-id');
        opt.style.display = (!subcatId || optSubcat === subcatId) ? '' : 'none';
    }
    catalogSelect.value = '';

    triggerSkuPreview();
}

function onCatalogSelected() {
    const catSelect = document.getElementById('varCatalog');
    const opt = catSelect.options[catSelect.selectedIndex];
    if (opt && opt.value) {
        const catId = opt.getAttribute('data-cat-id');
        const subcatId = opt.getAttribute('data-subcat-id');
        if (catId) document.getElementById('varCategory').value = catId;
        if (subcatId) document.getElementById('varSubcategory').value = subcatId;

        const hsn = opt.getAttribute('data-hsn');
        if (hsn) document.getElementById('varHsn').value = hsn;

        document.getElementById('previewProdName').innerText = opt.getAttribute('data-name');
    }
    triggerSkuPreview();
}

function triggerSkuPreview() {
    const productId = document.getElementById('varCatalog').value;
    const colorId = document.getElementById('varColor').value;
    const designId = document.getElementById('varDesign').value;
    const sizeId = document.getElementById('varSize').value;

    const colSelect = document.getElementById('varColor');
    const desSelect = document.getElementById('varDesign');
    const szSelect = document.getElementById('varSize');

    document.getElementById('previewColor').innerText = colSelect.selectedIndex > 0 ? colSelect.options[colSelect.selectedIndex].text : '—';
    document.getElementById('previewDesign').innerText = desSelect.selectedIndex > 0 ? desSelect.options[desSelect.selectedIndex].text : '—';
    document.getElementById('previewSize').innerText = szSelect.selectedIndex > 0 ? szSelect.options[szSelect.selectedIndex].text : '—';
    document.getElementById('previewPrice').innerText = parseFloat(document.getElementById('varSellingPrice').value || 0).toFixed(2);

    if (!productId) {
        document.getElementById('varQrCode').value = '';
        updateDuplicateBanner(false, 'Select a Catalog and attributes to validate uniqueness.', 'secondary');
        return;
    }

    const url = '<?= url("api/variants/preview-sku") ?>?product_id=' + productId +
                '&color_id=' + colorId +
                '&design_id=' + designId +
                '&size_id=' + sizeId;

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success' || res.success) {
            const data = res.data;
            document.getElementById('varQrCode').value = data.qr_code;
            document.getElementById('varVariantCode').value = data.variant_code;
            document.getElementById('previewBadgeId').innerText = data.variant_code;
            document.getElementById('previewQrText').innerText = data.qr_code;

            // Render vector QR
            renderPreviewQr(data.qr_code);

            if (data.is_duplicate) {
                updateDuplicateBanner(true, 'DUPLICATE ALERT: A variant with this exact combination of Color, Design, and Size already exists in this Catalog!', 'danger');
                document.getElementById('btnSubmitVariant').disabled = true;
            } else {
                updateDuplicateBanner(false, '✓ Combination available: Unique variant identity ready to save.', 'success');
                document.getElementById('btnSubmitVariant').disabled = false;
            }
        }
    })
    .catch(e => console.error(e));
}

function updateDuplicateBanner(isDup, message, alertClass) {
    const box = document.getElementById('duplicateStatusBox');
    box.className = 'alert alert-' + alertClass + ' py-2 px-3 small d-flex align-items-center gap-2 mb-0';
    box.innerHTML = (isDup ? '<i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>' : '<i class="bi bi-check-circle-fill text-' + alertClass + '"></i>') + '<span>' + message + '</span>';
}

function renderPreviewQr(text) {
    const qrBox = document.getElementById('previewQrBox');
    qrBox.innerHTML = '';
    if (!text) {
        qrBox.innerHTML = '<span class="text-muted small">QR Preview</span>';
        return;
    }
    new QRCode(qrBox, {
        text: text,
        width: 120,
        height: 120,
        correctLevel: QRCode.CorrectLevel.M
    });
}

document.getElementById('varSellingPrice').addEventListener('input', function() {
    document.getElementById('previewPrice').innerText = parseFloat(this.value || 0).toFixed(2);
});
</script>
