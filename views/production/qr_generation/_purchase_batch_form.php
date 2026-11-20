<?php
/**
 * Shared QR generation form for mapped purchase items using roll-based codes.
 * Variables: $sourceKey, $sourceMeta, $purchases, $activeTab
 */
$purchaseData = json_encode(array_values($purchases), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="bi <?= e($sourceMeta['icon']) ?> me-2 text-<?= e($sourceMeta['color']) ?>"></i>
            Generate <?= e($sourceMeta['label']) ?> QR Batch from Purchase
        </h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= url('production/qr-generations/store-purchase') ?>" id="formPurchaseBatch_<?= e($sourceKey) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="<?= e($sourceKey) ?>">
            <input type="hidden" name="qr_source" value="<?= e($sourceMeta['source']) ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Purchase Bill <span class="text-danger">*</span></label>
                    <select name="purchase_id" id="purchase_<?= e($sourceKey) ?>" class="form-select form-select-lg" required onchange="onQrPurchaseChanged('<?= e($sourceKey) ?>')">
                        <option value="">-- Select Purchase Bill --</option>
                        <?php foreach ($purchases as $purchase): ?>
                            <option value="<?= (int)$purchase['id'] ?>">
                                <?= e($purchase['purchase_number']) ?> · <?= e(date('d M Y', strtotime($purchase['purchase_date']))) ?> · <?= e($purchase['seller_label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Purchased Item <span class="text-danger">*</span></label>
                    <select name="purchase_item_id" id="item_<?= e($sourceKey) ?>" class="form-select form-select-lg" required onchange="onQrItemChanged('<?= e($sourceKey) ?>')">
                        <option value="">-- Select item after choosing a bill --</option>
                    </select>
                </div>
            </div>

            <div class="alert alert-light border mb-4" id="summary_<?= e($sourceKey) ?>">
                <div class="small text-muted">Select a purchased item to see its QR details.</div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Reprint for damaged labels">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-<?= e($sourceMeta['color']) ?> btn-lg w-100 fw-bold">
                        <i class="bi bi-qr-code-scan me-2"></i> Generate QR Batch
                    </button>
                </div>
            </div>

            <div class="border-top pt-3 small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                QR codes use the same roll prefix, sequence, deleted-label and damaged-label rules as the existing thermal roll QR printer.
                After generation you can still open the original roll QR print view from the purchase page.
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="qrPurchaseData_<?= e($sourceKey) ?>"><?= $purchaseData ?></script>
<script>
(function () {
    const key = '<?= e($sourceKey) ?>';
    const dataNode = document.getElementById('qrPurchaseData_' + key);
    const purchases = dataNode ? JSON.parse(dataNode.textContent || '[]') : [];
    window.qrPurchaseData = window.qrPurchaseData || {};
    window.qrPurchaseData[key] = purchases;
})();

function onQrPurchaseChanged(key) {
    const purchaseId = document.getElementById('purchase_' + key).value;
    const itemSelect = document.getElementById('item_' + key);
    const summary = document.getElementById('summary_' + key);
    itemSelect.innerHTML = '<option value="">-- Select item --</option>';
    summary.innerHTML = '<div class="small text-muted">Select a purchased item to see its QR details.</div>';

    const purchase = (window.qrPurchaseData[key] || []).find(p => String(p.id) === String(purchaseId));
    if (!purchase) return;

    (purchase.items || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.item_id;
        const variantLabel = Number(item.variant_id || 0) > 0 ? 'Variant ID ' + Number(item.variant_id) : 'Variant not assigned';
        const label = [item.item_name, variantLabel, item.roll_number ? ('Roll ' + item.roll_number) : '', item.start_code ? ('Code ' + item.start_code) : ''].filter(Boolean).join(' · ');
        opt.textContent = label + ' — ' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs');
        opt.dataset.item = JSON.stringify(item);
        itemSelect.appendChild(opt);
    });
}

function onQrItemChanged(key) {
    const select = document.getElementById('item_' + key);
    const summary = document.getElementById('summary_' + key);
    const option = select.options[select.selectedIndex];
    const raw = option && option.dataset.item;
    if (!raw) {
        summary.innerHTML = '<div class="small text-muted">Select a purchased item to see its QR details.</div>';
        return;
    }
    const item = JSON.parse(raw);
    const variantId = Number(item.variant_id || 0);
    const existing = item.qr_batch_code
        ? '<a href="<?= url('production/qr-generations/view/') ?>' + Number(item.qr_generation_id) + '" class="badge bg-dark-subtle text-dark border text-decoration-none ms-2"><i class="bi bi-qr-code me-1"></i>' + (item.qr_batch_code || '') + '</a>'
        : '<span class="badge bg-secondary-subtle text-secondary border ms-2">No batch yet</span>';

    summary.innerHTML =
        '<div class="row g-2 small">' +
            '<div class="col-md-3"><span class="text-muted">Item</span><div class="fw-bold">' + (item.item_name || '') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Variant ID</span><div class="fw-bold font-monospace">' + (variantId > 0 ? variantId : 'Not assigned') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Roll / Start code</span><div class="fw-bold font-monospace">' + (item.roll_number || item.start_code || '—') + '</div></div>' +
            '<div class="col-md-2"><span class="text-muted">Qty</span><div class="fw-bold text-primary">' + Number(item.quantity).toLocaleString() + ' ' + (item.unit || 'pcs') + '</div></div>' +
            '<div class="col-md-3"><span class="text-muted">Existing batch</span><div>' + existing + '</div></div>' +
        '</div>';
}
</script>
