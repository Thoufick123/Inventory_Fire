<?php
/**
 * Production Product QR generation form:
 * Generates QR codes from completed Stitching Unit batches, then automatically registers
 * a Quality Control (QC) batch. Finished goods inventory is added only after passing QC.
 */
$stitchingData = json_encode(array_values($stitchingBatches ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="bi bi-scissors me-2 text-success"></i>
                    Generate Production Product QR Batch
                </h5>
                <span class="badge bg-success-subtle text-success border">
                    <i class="bi bi-check2-circle me-1"></i> Post-Stitching Flow
                </span>
            </div>
            <div class="card-body p-4">
                <!-- Flow summary banner -->
                <div class="alert alert-success-subtle border border-success-subtle rounded-3 p-3 mb-4">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-diagram-3-fill text-success fs-5 mt-1"></i>
                        <div>
                            <strong class="text-success-emphasis">Production Product Workflow:</strong>
                            <div class="small text-muted mt-1">
                                <strong>Stitching Unit</strong> (Completed) &rarr;
                                <strong class="text-success">Generate QR Batch</strong> (Current) &rarr;
                                <strong>Quality Control (QC)</strong> &rarr;
                                <strong>Finished Goods Inventory</strong> (Automatic upon QC pass)
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?= url('production/qr-generations/store-stitching') ?>" id="formStitchingQr">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="production_product">
                    <input type="hidden" name="qr_source" value="PRODUCTION_PRODUCT">

                    <div class="mb-4">
                        <label class="form-label fw-bold fs-6">
                            Select Completed Stitching Batch <span class="text-danger">*</span>
                        </label>
                        <select name="stitching_batch_id" id="prodStitchingBatch" class="form-select form-select-lg" required onchange="onStitchingBatchChanged()">
                            <option value="">-- Choose Stitching Batch --</option>
                            <?php foreach (($stitchingBatches ?? []) as $sb): ?>
                                <option value="<?= (int)$sb['id'] ?>" <?= ((int)($_GET['stitching_batch_id'] ?? 0) === (int)$sb['id']) ? 'selected' : '' ?>>
                                    <?= e($sb['batch_number']) ?> · <?= e($sb['program_number'] ?? 'PROG') ?> (<?= e($sb['program_name'] ?? 'Production') ?>) · <?= (int)$sb['total_pieces'] ?> Pcs
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Showing batches from Stitching Unit waiting for piece QR identities.</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-dark">
                                Brand Name to Show on QR Label <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success"><i class="bi bi-award-fill"></i></span>
                                <input type="text" name="brand_name" id="prodBrandName" class="form-control fw-bold"
                                       placeholder="e.g. GRM APPARELS"
                                       value="GRM APPARELS" required maxlength="150" oninput="updateProdPreview()">
                            </div>
                            <div class="form-text small">Printed prominently across the top of thermal piece labels.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-dark">
                                QR Code Prefix <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light font-monospace">#</span>
                                <input type="text" name="qr_prefix" id="prodQrPrefix" class="form-control font-monospace text-uppercase"
                                       placeholder="Auto (Variant ID e.g. V0001)"
                                       value="" maxlength="30" oninput="updateProdPreview()">
                            </div>
                            <div class="form-text small">Leave empty to auto-use Variant ID (e.g. V0001-0001, V0002-0001).</div>
                        </div>
                    </div>

                    <!-- Selected Stitching Batch Details Card -->
                    <div class="card bg-light border mb-4" id="prodBatchDetailsCard" style="display: none;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0 fw-bold text-dark" id="prodBatchTitle">Stitching Batch Details</h6>
                                <span class="badge bg-success" id="prodBatchTotalBadge">0 Total Pieces</span>
                            </div>
                            <div class="row g-2 small text-muted mb-3">
                                <div class="col-sm-6">
                                    <strong>Program:</strong> <span id="prodProgName" class="text-dark">—</span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Hierarchy:</strong> <span id="prodHierarchy" class="text-dark">—</span>
                                </div>
                            </div>

                            <div class="table-responsive bg-white rounded border">
                                <table class="table table-sm table-bordered align-middle mb-0 text-center" id="prodBreakdownTable">
                                    <thead class="table-light small">
                                        <tr>
                                            <th>#</th>
                                            <th>Variant / SKU</th>
                                            <th>Size</th>
                                            <th>Color</th>
                                            <th class="text-end pe-3">Stitched Pieces</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prodBreakdownBody">
                                        <!-- Dynamically populated -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Production Notes (optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Stitched batch ready for quality inspection">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg px-4 fw-bold">
                            <i class="bi bi-scissors me-2"></i> Generate QR Batch & Move to QC
                        </button>
                        <a href="<?= url('production/qr-generations?tab=production_product') ?>" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Label Preview & Pipeline Info -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-eye-fill me-2 text-warning"></i>Piece Label Preview</h6>
            </div>
            <div class="card-body p-4 text-center">
                <div class="thermal-preview-card p-3 border border-dark rounded bg-white shadow-sm mx-auto" style="max-width: 330px; border-width: 2px !important;">
                    <div class="fw-bold text-uppercase fs-6 pb-2 mb-2 border-bottom border-dark text-truncate text-center" id="prodStickerBrand">GRM APPARELS</div>
                    
                    <div class="d-flex align-items-center justify-content-between gap-3 text-start">
                        <!-- Left Side: QR Code & QR Code (Variant ID) below -->
                        <div class="text-center pe-2 border-end border-dark-subtle d-flex flex-column align-items-center justify-content-center" style="flex: 0 0 115px;">
                            <div class="d-flex justify-content-center mb-1">
                                <div id="prodStickerQrBox" class="p-1 bg-white border rounded"></div>
                            </div>
                            <div class="font-monospace fw-bold text-dark text-truncate w-100" style="font-size: 11px; letter-spacing: -0.2px;" id="prodStickerCode">PRD<?= date('ym') ?>-0001</div>
                            <div class="font-monospace text-muted fw-bold text-truncate w-100" style="font-size: 10px;" id="prodStickerVariantId">VID: —</div>
                        </div>

                        <!-- Right Side: Product Name, Size, MRP, Selling Amount -->
                        <div class="ps-1 flex-grow-1 overflow-hidden" style="min-width: 0;">
                            <div class="fw-bold text-dark text-truncate text-uppercase mb-1" style="font-size: 12px; line-height: 1.25;" id="prodStickerItem" title="Product Name">
                                FINISHED GARMENT
                            </div>
                            <div class="fw-semibold text-secondary mb-1" style="font-size: 11px;" id="prodStickerVariant">
                                SIZE: M
                            </div>
                            <div class="text-muted mb-1" style="font-size: 11px;" id="prodStickerMrp">
                                MRP: <del>₹100.00</del>
                            </div>
                            <div class="fw-bold text-success mb-1" style="font-size: 12px;" id="prodStickerSale">
                                SALE: ₹80.00
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0" style="font-size: 9px;">
                                    <i class="bi bi-patch-check me-1"></i>QC REQ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-success-subtle bg-light text-start mt-4 p-3 rounded-3">
                    <h6 class="fw-bold text-success mb-2"><i class="bi bi-shield-check me-1"></i> Quality Control Policy</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">QR batch generates unique tracking codes for every stitched piece.</li>
                        <li class="mb-1">The batch is automatically queued into <strong>Quality Control (QC)</strong>.</li>
                        <li><strong>Finished Goods Inventory</strong> is credited only for pieces that pass QC inspection.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const STITCHING_BATCHES = <?= $stitchingData ?: '[]' ?>;
let prodQrCodeObj = null;

function onStitchingBatchChanged() {
    const selId = parseInt(document.getElementById('prodStitchingBatch').value, 10);
    const card = document.getElementById('prodBatchDetailsCard');
    const tbody = document.getElementById('prodBreakdownBody');
    const batch = STITCHING_BATCHES.find(b => parseInt(b.id, 10) === selId);

    if (!batch) {
        if (card) card.style.display = 'none';
        updateProdPreview();
        return;
    }

    if (card) card.style.display = 'block';
    const batchTitle = document.getElementById('prodBatchTitle');
    if (batchTitle) batchTitle.textContent = 'Batch: ' + (batch.batch_number || '') + ' (' + (batch.name || 'Stitching') + ')';
    
    const totalBadge = document.getElementById('prodBatchTotalBadge');
    if (totalBadge) totalBadge.textContent = (batch.total_pieces || 0) + ' Total Pieces';

    const progName = document.getElementById('prodProgName');
    if (progName) progName.textContent = (batch.program_number || '') + ' - ' + (batch.program_name || 'Production');

    const hier = document.getElementById('prodHierarchy');
    let hierText = batch.category_name || 'Apparel';
    if (batch.subcategory_name) hierText += ' › ' + batch.subcategory_name;
    if (batch.sub_subcategory_name) hierText += ' › ' + batch.sub_subcategory_name;
    if (hier) hierText ? hier.textContent = hierText : hier.textContent = '—';

    // Populate Breakdown Table
    if (tbody) {
        tbody.innerHTML = '';
        const items = batch.items || [];
        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Total Batch: ${batch.total_pieces} Pcs</td></tr>`;
        } else {
            items.forEach((it, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${idx + 1}</td>
                    <td><span class="badge bg-light text-dark border">${it.sku || it.variant_code || 'Not assigned'}</span></td>
                    <td><span class="badge bg-light text-dark border">${it.variant_size_name || it.size_name || 'Standard'}</span></td>
                    <td><span class="badge bg-light text-dark border">${it.variant_color_name || it.color_name || 'Default'}</span></td>
                    <td class="text-end pe-3 fw-bold">${it.qty || 0}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    const itemLabel = document.getElementById('prodStickerItem');
    if (itemLabel) {
        itemLabel.textContent = (batch.product_name || hierText || 'FINISHED GARMENT').substring(0, 30);
        itemLabel.title = batch.product_name || hierText || 'FINISHED GARMENT';
    }

    const firstVariant = (batch.items || []).find(it => it.variant_id || it.sku || it.variant_code);
    const variantEl = document.getElementById('prodStickerVariant');
    if (variantEl) {
        const sizeStr = firstVariant ? (firstVariant.variant_size_name || firstVariant.size_name || '') : '';
        variantEl.textContent = sizeStr ? ('SIZE: ' + sizeStr) : 'SIZE: Standard';
    }
    const mrpEl = document.getElementById('prodStickerMrp');
    if (mrpEl) {
        const mrp = Number(firstVariant && (firstVariant.variant_mrp || firstVariant.base_price) || 0);
        mrpEl.innerHTML = mrp > 0 ? 'MRP: <del>₹' + mrp.toFixed(2) + '</del>' : 'MRP: ₹0.00';
    }
    const saleEl = document.getElementById('prodStickerSale');
    if (saleEl) {
        const sale = Number(firstVariant && (firstVariant.variant_selling_price || firstVariant.discount_price) || 0);
        saleEl.textContent = sale > 0 ? 'SALE: ₹' + sale.toFixed(2) : 'SALE: ₹0.00';
    }
    const vidEl = document.getElementById('prodStickerVariantId');
    if (vidEl) {
        const vId = firstVariant ? (firstVariant.variant_id || '') : '';
        const vCode = firstVariant ? (firstVariant.variant_code || firstVariant.sku || '') : '';
        if (vId) {
            vidEl.textContent = 'VID: #' + vId;
        } else if (vCode) {
            vidEl.textContent = vCode;
        } else {
            vidEl.textContent = 'VID: —';
        }
    }

    const prefixInput = document.getElementById('prodQrPrefix');
    if (prefixInput && (!prefixInput.value || prefixInput.value.trim() === '')) {
        prefixInput.value = 'PRD<?= date('ym') ?>';
    }

    updateProdPreview();
}

function updateProdPreview() {
    const brandInput = document.getElementById('prodBrandName');
    const prefixInput = document.getElementById('prodQrPrefix');
    const brand = (brandInput && brandInput.value.trim()) ? brandInput.value.trim().toUpperCase() : 'GRM APPARELS';
    const prefix = (prefixInput && prefixInput.value.trim()) ? prefixInput.value.trim().toUpperCase() : 'PRD<?= date('ym') ?>';
    const previewCode = prefix + '-0001';

    const brandEl = document.getElementById('prodStickerBrand');
    if (brandEl) brandEl.textContent = brand;

    const codeEl = document.getElementById('prodStickerCode');
    if (codeEl) codeEl.textContent = previewCode;

    const qrBox = document.getElementById('prodStickerQrBox');
    if (qrBox && typeof QRCode !== 'undefined') {
        qrBox.innerHTML = '';
        new QRCode(qrBox, {
            text: previewCode,
            width: 85,
            height: 85,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateProdPreview();
    if (document.getElementById('prodStitchingBatch') && document.getElementById('prodStitchingBatch').value) {
        onStitchingBatchChanged();
    }
});
</script>
