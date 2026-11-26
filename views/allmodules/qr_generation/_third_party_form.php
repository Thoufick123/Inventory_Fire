<?php
/** Mapped catalog item QR generation: brand name is entered by the admin. */
$purchaseData = json_encode(array_values($purchases), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="bi <?= e($sourceMeta['icon']) ?> me-2 text-<?= e($sourceMeta['color']) ?>"></i>
                    Generate <?= e($sourceMeta['label']) ?> QR Batch
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('production/qr-generations/store-purchase') ?>" id="formCatalogPurchaseQr_<?= e($sourceKey) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="<?= e($sourceKey) ?>">
                    <input type="hidden" name="qr_source" value="<?= e($sourceMeta['source']) ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Bill <span class="text-danger">*</span></label>
                            <select name="purchase_id" id="tpPurchase_<?= e($sourceKey) ?>" class="form-select form-select-lg" required onchange="onTpPurchaseChanged('<?= e($sourceKey) ?>')">
                                <option value="">-- Select Purchase Bill --</option>
                                <?php foreach ($purchases as $purchase): ?>
                                    <option value="<?= e($purchase['id']) ?>"
                                        <?= $activeTab === 'third_party' && (string)($preselectedPurchaseId ?? '') === (string)$purchase['id'] ? 'selected' : '' ?>>
                                        <?= e($purchase['purchase_number']) ?> · <?= e(date('d M Y', strtotime($purchase['purchase_date']))) ?> · <?= e($purchase['seller_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mapped Product <span class="text-danger">*</span></label>
                            <select name="purchase_item_id" id="tpItem_<?= e($sourceKey) ?>" class="form-select form-select-lg" required onchange="onTpItemChanged('<?= e($sourceKey) ?>')">
                                <option value="">-- Select product after choosing a bill --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label fw-bold text-dark fs-6">
                            Brand Name to Show on QR Label <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-info"><i class="bi bi-award-fill"></i></span>
                            <input type="text" name="brand_name" id="tpBrandName_<?= e($sourceKey) ?>" class="form-control fw-bold"
                                   placeholder="Type the brand exactly as it should appear on the QR label"
                                   value="" required maxlength="150" oninput="updateTpPreview('<?= e($sourceKey) ?>')">
                        </div>
                        <div class="form-text small">Field starts empty. The text you type is printed exactly as entered on every QR label.</div>
                    </div>

                    <div class="alert alert-light border mb-4" id="tpSummary_<?= e($sourceKey) ?>">
                        <div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Notes (optional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Batch for retail packing">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-<?= e($sourceMeta['color']) ?> btn-lg w-100 fw-bold">
                                <i class="bi bi-qr-code-scan me-2"></i> Generate QR Batch
                            </button>
                        </div>
                    </div>

                    <div class="border-top pt-3 small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Only mapped items are available. Quantity and variant are taken from the purchase line; the variant ID below is for reference.
                    </div>
                </form>
            </div>
        </div>

        <?php if ($sourceKey === 'third_party') require __DIR__ . '/_legacy_forms.php'; ?>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-eye-fill me-2 text-warning"></i><?= e($sourceMeta['label']) ?> Label Preview</h6>
            </div>
            <div class="card-body p-4 text-center">
                <div class="thermal-preview-card p-3 border border-dark rounded bg-white shadow-sm mx-auto text-center" style="max-width: 280px; border-width: 2px !important;">
                    <div class="fw-bold text-uppercase fs-6 pb-1 border-bottom border-dark text-truncate" id="tpStickerBrand_<?= e($sourceKey) ?>">YOUR BRAND</div>
                    <div class="small fw-semibold text-muted text-uppercase mt-1" id="tpStickerItem_<?= e($sourceKey) ?>">MAPPED PRODUCT</div>
                    <div class="d-flex justify-content-center my-2">
                        <div id="tpStickerQrBox_<?= e($sourceKey) ?>" class="p-1 bg-white border rounded"></div>
                    </div>
                    <div class="font-monospace fw-bold text-dark" style="font-size: 11px;" id="tpStickerCode_<?= e($sourceKey) ?>">BRAND-SKU-0001</div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-dark small fw-bold">
                        <div>MRP: <del>₹<span id="tpStickerMrp_<?= e($sourceKey) ?>">0.00</span></del></div>
                        <div class="text-primary">SALE: ₹<span id="tpStickerPrice_<?= e($sourceKey) ?>">0.00</span></div>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 text-start small text-muted mb-0">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    The brand text above is printed exactly as typed in the form.
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="tpPurchaseData_<?= e($sourceKey) ?>"><?= $purchaseData ?></script>
<script>
(function () {
    const key = '<?= e($sourceKey) ?>';
    const node = document.getElementById('tpPurchaseData_' + key);
    window.tpPurchasesByKey = window.tpPurchasesByKey || {};
    window.tpPurchasesByKey[key] = node ? JSON.parse(node.textContent || '[]') : [];
})();

function onTpPurchaseChanged(key) {
    const purchaseId = document.getElementById('tpPurchase_' + key).value;
    const itemSelect = document.getElementById('tpItem_' + key);
    const summary = document.getElementById('tpSummary_' + key);
    itemSelect.innerHTML = '<option value="">-- Select product --</option>';
    summary.innerHTML = '<div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>';
    window.tpCurrentItemsByKey = window.tpCurrentItemsByKey || {};
    delete window.tpCurrentItemsByKey[key];
    updateTpPreview(key);

    const purchase = (window.tpPurchasesByKey[key] || []).find(p => String(p.id) === String(purchaseId));
    if (!purchase) return;

    const brandInput = document.getElementById('tpBrandName_' + key);
    if (brandInput && !brandInput.value && purchase.seller_label) {
        brandInput.value = purchase.seller_label.replace(/\s*\(.*?\)\s*/g, '').trim();
    }

    (purchase.items || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.item_id;
        const variantLabel = Number(item.variant_id || 0) > 0 ? 'Variant ID ' + Number(item.variant_id) : 'Variant not assigned';
        opt.textContent = item.item_name + ' — ' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs') + ' · ' + variantLabel;
        opt.dataset.item = JSON.stringify(item);
        itemSelect.appendChild(opt);
    });
}

function onTpItemChanged(key) {
    const select = document.getElementById('tpItem_' + key);
    const summary = document.getElementById('tpSummary_' + key);
    const option = select.options[select.selectedIndex];
    const raw = option && option.dataset.item;
    if (!raw) {
        summary.innerHTML = '<div class="small text-muted">Select a purchased product to view mapping, quantity and GRN status.</div>';
        window.tpCurrentItemsByKey = window.tpCurrentItemsByKey || {};
        delete window.tpCurrentItemsByKey[key];
        updateTpPreview(key);
        return;
    }
    const item = JSON.parse(raw);
    const variantId = Number(item.variant_id || 0);
    const qr = item.qr_batch_code
        ? '<a href="<?= url('production/qr-generations/view/') ?>' + Number(item.qr_generation_id) + '" class="badge bg-dark-subtle text-dark border text-decoration-none"><i class="bi bi-qr-code me-1"></i>' + item.qr_batch_code + '</a>'
        : '<span class="badge bg-secondary-subtle text-secondary border">No QR batch</span>';
    const grn = item.grn_number
        ? '<a href="<?= url('production/inwards/view/') ?>' + Number(item.grn_id || 0) + '" class="badge bg-primary-subtle text-primary border text-decoration-none"><i class="bi bi-truck me-1"></i>' + item.grn_number + '</a>'
        : '<span class="badge bg-secondary-subtle text-secondary border">No GRN</span>';

    summary.innerHTML =
        '<div class="row g-2 small align-items-center">' +
            '<div class="col-md-3"><span class="text-muted">Product</span><div class="fw-bold">' + (item.item_name || '') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Qty</span><div class="fw-bold text-primary">' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Variant ID</span><div class="fw-bold font-monospace">' + (item.variant_code || '—') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Variant ID</span><div class="fw-bold font-monospace">' + (variantId > 0 ? variantId : 'Not assigned') + '</div></div>' +
            '<div class="col-md-3"><span class="text-muted">QR / GRN</span><div>' + qr + ' ' + grn + '</div></div>' +
        '</div>';
    window.tpCurrentItemsByKey = window.tpCurrentItemsByKey || {};
    window.tpCurrentItemsByKey[key] = item;
    updateTpPreview(key);
}

function updateTpPreview(key) {
    const brand = (document.getElementById('tpBrandName_' + key).value || '').trim();
    document.getElementById('tpStickerBrand_' + key).textContent = brand || 'YOUR BRAND';
    const item = (window.tpCurrentItemsByKey || {})[key] || {};
    document.getElementById('tpStickerItem_' + key).textContent = (item.item_name || 'MAPPED PRODUCT').toUpperCase();
    document.getElementById('tpStickerMrp_' + key).textContent = Number(item.mrp || 0).toFixed(2);
    document.getElementById('tpStickerPrice_' + key).textContent = Number(item.selling_price || 0).toFixed(2);
    document.getElementById('tpStickerCode_' + key).textContent = (brand ? brand.replace(/[^A-Za-z0-9]/g, '').substr(0, 4).toUpperCase() : 'BRAND') + '-SKU-0001';

    const qrBox = document.getElementById('tpStickerQrBox_' + key);
    if (!qrBox || typeof QRCode === 'undefined') return;
    qrBox.innerHTML = '';
    new QRCode(qrBox, {
        text: document.getElementById('tpStickerCode_' + key).textContent,
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const purchaseId = <?= json_encode((string)($preselectedPurchaseId ?? '')) ?>;
    const itemId = <?= json_encode((string)($preselectedPurchaseItemId ?? '')) ?>;
    const purchaseSelect = document.getElementById('tpPurchase_third_party');
    const itemSelect = document.getElementById('tpItem_third_party');
    if (!purchaseSelect || !itemSelect || !purchaseId) return;
    purchaseSelect.value = String(purchaseId);
    onTpPurchaseChanged('third_party');
    if (itemId) {
        itemSelect.value = String(itemId);
        onTpItemChanged('third_party');
    }
});
    if (itemId) {
        itemSelect.value = String(itemId);
        onTpItemChanged('third_party');
    }
});
</script>
