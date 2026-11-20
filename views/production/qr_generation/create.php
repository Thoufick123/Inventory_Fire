<?php
$activeMeta = $tabs[$activeTab] ?? reset($tabs);
?>
<div class="qr-generation-ui">
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code me-2 text-primary"></i>Generate QR Code Batch</h2>
        <p class="text-muted small mb-0">Choose the product area, then generate QR labels from a purchase or a custom catalog hierarchy.</p>
    </div>
    <div>
        <a href="<?= url('production/qr-generations?tab=' . e($activeTab)) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Generations List
        </a>
    </div>
</div>

<!-- QR generation areas -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 gap-2" id="generatorSourceTabs" role="tablist">
    <?php foreach ($tabs as $tab): ?>
        <?php $isActiveTab = $tab['key'] === $activeTab; ?>
        <li class="nav-item" role="presentation">
            <a href="<?= url('production/qr-generations/create?tab=' . $tab['key']) ?>"
               class="nav-link py-3 border shadow-sm rounded <?= $isActiveTab ? 'active fw-bold' : 'fw-semibold text-muted' ?>"
               role="tab" aria-selected="<?= $isActiveTab ? 'true' : 'false' ?>">
                <i class="bi <?= $tab['icon'] ?> me-2 text-<?= $tab['color'] ?> fs-5"></i><?= e($tab['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="generatorTabsContent">
    <!-- 1. FABRIC ROLLS -->
    <div class="tab-pane fade <?= $activeTab === 'fabric' ? 'show active' : '' ?>" id="fabricMode" role="tabpanel">
        <?php
            $sourceKey = 'fabric';
            $sourceMeta = $tabs['fabric'];
            $purchases = $purchaseGroups[QrGenerationService::SOURCE_FABRIC] ?? [];
            require __DIR__ . '/_purchase_batch_form.php';
        ?>
    </div>

    <!-- 2. RAW MATERIALS -->
    <div class="tab-pane fade <?= $activeTab === 'raw_material' ? 'show active' : '' ?>" id="rawMaterialMode" role="tabpanel">
        <?php
            $sourceKey = 'raw_material';
            $sourceMeta = $tabs['raw_material'];
            $purchases = $purchaseGroups[QrGenerationService::SOURCE_RAW] ?? [];
            require __DIR__ . '/_purchase_batch_form.php';
        ?>
    </div>

    <?php if (isset($tabs['third_party'])): ?>
    <!-- 3. THIRD-PARTY PRODUCTS -->
    <div class="tab-pane fade <?= $activeTab === 'third_party' ? 'show active' : '' ?>" id="thirdPartyMode" role="tabpanel">
        <?php
            $sourceKey = 'third_party';
            $sourceMeta = $tabs['third_party'];
            $purchases = $purchaseGroups[QrGenerationService::SOURCE_THIRD_PARTY] ?? [];
            require __DIR__ . '/_third_party_form.php';
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($tabs['production_product'])): ?>
    <!-- 4. PRODUCTION PRODUCTS (POST-STITCHING) -->
    <div class="tab-pane fade <?= $activeTab === 'production_product' ? 'show active' : '' ?>" id="productionProductMode" role="tabpanel">
        <?php
            $sourceKey = 'production_product';
            $sourceMeta = $tabs['production_product'];
            require __DIR__ . '/_production_product_form.php';
        ?>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = value;
}

function onManualCategoryChanged() {
    const catId = document.getElementById('manCategoryId').value;
    const subcatSelect = document.getElementById('manSubcategoryId');
    for (let opt of subcatSelect.options) {
        if (!opt.value) continue;
        const optCat = opt.getAttribute('data-cat-id');
        opt.style.display = (!catId || optCat === catId) ? '' : 'none';
    }
    subcatSelect.value = '';
    updateManualPreview();
}

function onManualSubcategoryChanged() {
    const subcatId = document.getElementById('manSubcategoryId').value;
    const sscSelect = document.getElementById('manSubSubcategoryId');
    for (let opt of sscSelect.options) {
        if (!opt.value) continue;
        const optSubcat = opt.getAttribute('data-subcat-id');
        opt.style.display = (!subcatId || optSubcat === subcatId) ? '' : 'none';
    }
    sscSelect.value = '';
    updateManualPreview();
}

function updateManualPreview() {
    const brand = document.getElementById('manBrandName').value.trim() || 'BRAND';
    const catSelect = document.getElementById('manCategoryId');
    const newCat = document.getElementById('manNewCatName').value.trim();
    const catName = newCat || (catSelect.selectedIndex > 0 ? catSelect.options[catSelect.selectedIndex].text.split('(')[0].trim() : 'CAT');

    const subcatSelect = document.getElementById('manSubcategoryId');
    const newSubcat = document.getElementById('manNewSubcatName').value.trim();
    const subcatName = newSubcat || (subcatSelect.selectedIndex > 0 ? subcatSelect.options[subcatSelect.selectedIndex].text : '');

    const newSsc = document.getElementById('manNewSubSubcatName').value.trim();
    const sscSelect = document.getElementById('manSubSubcategoryId');
    const sscName = newSsc || (sscSelect.selectedIndex > 0 ? sscSelect.options[sscSelect.selectedIndex].text : '');

    setText('stickerBrand', brand.toUpperCase());
    let hierParts = [catName];
    if (subcatName) hierParts.push(subcatName);
    if (sscName) hierParts.push(sscName);
    setText('stickerHierarchy', hierParts.join(' > ').toUpperCase());

    function toCode(txt) {
        return txt.replace(/[^A-Za-z0-9]/g, '').substr(0, 3).toUpperCase() || 'GEN';
    }
    const bCode = brand.replace(/[^A-Za-z0-9]/g, '').substr(0, 4).toUpperCase() || 'BRD';
    const cCode = toCode(catName);
    const scCode = subcatName ? toCode(subcatName) : '';
    const sscCode = sscName ? toCode(sscName) : '';
    let codeParts = [bCode, cCode];
    if (scCode) codeParts.push(scCode);
    if (sscCode) codeParts.push(sscCode);
    const fullCode = codeParts.join('-') + '-0001';
    setText('stickerCode', fullCode);
    setText('stickerMrp', parseFloat(document.getElementById('manMrp').value || 0).toFixed(2));
    setText('stickerPrice', parseFloat(document.getElementById('manSellingPrice').value || 0).toFixed(2));
    setText('formatFormula', '[' + bCode + ']-[' + cCode + ']' + (scCode ? '-[' + scCode + ']' : '') + '-[SEQ]');

    const qrBox = document.getElementById('stickerQrBox');
    if (qrBox && typeof QRCode !== 'undefined') {
        qrBox.innerHTML = '';
        new QRCode(qrBox, { text: fullCode, width: 90, height: 90, correctLevel: QRCode.CorrectLevel.M });
    }
}

function onCatalogModeSelected() {
    const prodId = document.getElementById('catModeProduct').value;
    const varSelect = document.getElementById('catModeVariant');
    varSelect.innerHTML = '<option value="">-- Whole Catalog / Base Style --</option>';
    if (!prodId) return;

    fetch('<?= url("api/catalog/variants?product_id=") ?>' + prodId)
        .then(r => r.json())
        .then(res => {
            const variants = (res.data && res.data.variants) ? res.data.variants : (res.variants || []);
            (variants || []).forEach(v => {
                if (!v.id) return;
                const opt = document.createElement('option');
                opt.value = v.id;
                const vAttrs = [v.color_name, v.design_name, v.size_name].filter(Boolean).join(' · ');
                opt.text = (v.variant_code ? '[' + v.variant_code + '] ' : '') + (v.sku || ('Variant #' + v.id)) + (vAttrs ? ' (' + vAttrs + ')' : '');
                varSelect.appendChild(opt);
            });
        })
        .catch(e => console.error(e));
}

document.addEventListener('DOMContentLoaded', function () {
    updateManualPreview();
    if (typeof updateTpPreview === 'function') {
        updateTpPreview('third_party');
    }
});
</script>
</div>
