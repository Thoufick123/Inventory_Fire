<div class="qr-generation-ui">
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-qr-code me-2 text-primary"></i>Batch <?= e($batch['batch_code']) ?></h2>
            <?php if ($batch['generation_type'] === 'MANUAL'): ?>
            <span class="badge bg-info-subtle text-info border border-info-subtle">Manual Custom</span>
            <?php else: ?>
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Catalog Linked</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Brand: <strong class="text-dark"><?= e($batch['brand_name']) ?></strong> · Generated <?= count($batch['items']) ?> QR Codes</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php
            $batchSource = strtoupper((string)($batch['qr_source'] ?? QrGenerationService::SOURCE_THIRD_PARTY));
            $isProduction = $batchSource === QrGenerationService::SOURCE_PRODUCTION;
            $isFabricBatch = $batchSource === QrGenerationService::SOURCE_FABRIC;
            $sourcePurchaseDate = trim((string)($batch['source_purchase_date'] ?? ''));
            if ($sourcePurchaseDate === '') {
                $sourcePurchaseDate = trim((string)($batch['created_at'] ?? ''));
            }
            $sourcePurchaseTimestamp = $sourcePurchaseDate !== '' ? strtotime($sourcePurchaseDate) : false;
            $fabricMonthYear = $sourcePurchaseTimestamp !== false ? date('m/Y', $sourcePurchaseTimestamp) : '';
            $canAddToStock = !$isProduction && $batchSource !== 'OWN_PRODUCT'
                && in_array($batchSource, [QrGenerationService::SOURCE_FABRIC, QrGenerationService::SOURCE_RAW, QrGenerationService::SOURCE_THIRD_PARTY], true);
            $alreadyAdded = !empty($batch['stock_added']);
            $qcStatus = strtoupper((string)($batch['qc_batch_status'] ?? 'PENDING'));
            $isQcPassed = in_array($qcStatus, ['PASSED', 'COMPLETED'], true);
            $qcBatchId = (int)($batch['qc_batch_id_resolved'] ?? ($batch['qc_batch_id'] ?? 0));
        ?>
        <?php if ($isProduction): ?>
            <?php if ($isQcPassed): ?>
                <span class="btn btn-success fw-bold disabled qr-square-action" title="Quality Control inspection passed">
                    <i class="bi bi-patch-check-fill me-1"></i> Passed QC & Added to Finished Goods
                </span>
                <a href="<?= url('production/inventory') ?>" class="btn btn-outline-success fw-bold qr-square-action">
                    <i class="bi bi-boxes me-1"></i> Finished Goods Inventory
                </a>
            <?php else: ?>
                <?php if ($qcBatchId > 0): ?>
                    <a href="<?= url('production/qc/inspect/' . $qcBatchId) ?>" class="btn btn-warning text-dark fw-bold qr-square-action">
                        <i class="bi bi-shield-check me-1"></i> Inspect in Quality Control (QC)
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($canAddToStock): ?>
           
        <?php endif; ?>
        <button type="button" class="btn btn-outline-dark fw-bold qr-square-action" data-bs-toggle="modal" data-bs-target="#printerSettingsModal">
            <i class="bi bi-sliders me-1 text-primary"></i> QR Format Config
        </button>
        <a href="<?= url('production/qr-generations/print/' . $batch['id']) ?>" target="_blank" class="btn btn-primary fw-bold qr-square-action">
            <i class="bi bi-printer-fill me-1"></i> Print All <?= count($batch['items']) ?> Labels
        </a>
        <a href="<?= url('production/qr-generations?tab=' . e($activeTab)) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<?php if ($isProduction): ?>
<div class="alert alert-info border mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="bi bi-scissors me-2 fs-5 text-primary"></i>
            <strong>Production Product Batch:</strong>
            Generated from Stitching Batch <strong class="text-dark"><?= e($batch['stitching_batch_number'] ?? '—') ?></strong>
            (Program: <span class="fw-semibold text-dark"><?= e($batch['program_number'] ?? '—') ?> · <?= e($batch['program_name'] ?? 'Production') ?></span>).
            <div class="small text-muted mt-1">
                QC Batch: <strong class="font-monospace text-dark"><?= e($batch['qc_batch_number'] ?? ('QC-' . $qcBatchId)) ?></strong>
                · Status: <span class="badge bg-<?= $isQcPassed ? 'success' : 'warning text-dark' ?>"><?= e($qcStatus) ?></span>
                · Passed Pieces: <strong><?= number_format((int)($batch['qc_passed_qty'] ?? 0)) ?></strong> / <?= count($batch['items']) ?>
                · <em>Pieces enter Finished Goods Inventory automatically upon QC approval.</em>
            </div>
        </div>
        <div>
            <?php if ($isQcPassed): ?>
                <a href="<?= url('production/inventory') ?>" class="btn btn-success fw-bold btn-sm">
                    <i class="bi bi-arrow-right-circle me-1"></i> View in Finished Goods
                </a>
            <?php elseif ($qcBatchId > 0): ?>
                <a href="<?= url('production/qc/inspect/' . $qcBatchId) ?>" class="btn btn-warning text-dark fw-bold btn-sm">
                    <i class="bi bi-clipboard-check me-1"></i> Open QC Inspection
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($batchSource === QrGenerationService::SOURCE_FABRIC): ?>
<div class="alert alert-warning border mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Fabric QC rule:</strong> QR generation does not add inventory. Fabric rolls enter stock only after QC approval through the production QC flow.
</div>
<?php endif; ?>

<!-- Batch Metadata Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <?php if ($alreadyAdded): ?>
            <div class="col-12">
                <span class="badge bg-success text-white px-3 py-2">
                    <i class="bi bi-check2-circle me-1"></i> This product has moved to Purchased Stock
                </span>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold text-uppercase">Printed Brand Name</div>
                <div class="fs-5 fw-bold text-dark mt-1 text-uppercase"><?= e($batch['brand_name']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold text-uppercase">Category Hierarchy</div>
                <div class="fw-semibold text-dark mt-1">
                    <?= e($batch['category_name'] ?: 'General') ?>
                    <?php if (!empty($batch['subcategory_name'])): ?>
                    › <?= e($batch['subcategory_name']) ?>
                    <?php endif; ?>
                    <?php if (!empty($batch['sub_subcategory_name'])): ?>
                    › <?= e($batch['sub_subcategory_name']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold text-uppercase"><?= $isFabricBatch ? 'Month/Year' : 'QR Code Prefix' ?></div>
                <div class="font-monospace fw-bold text-primary mt-1"><?= e($isFabricBatch ? ($fabricMonthYear ?: '—') : $batch['qr_prefix']) ?></div>
            </div>
            <?php if ($isProduction): ?>
            <div class="col-12 pt-3 border-top d-flex gap-3 flex-wrap align-items-center">
                <span class="badge bg-success-subtle text-success border fs-6"><i class="bi bi-scissors me-1"></i> Stitching Batch: <?= e($batch['stitching_batch_number'] ?? '—') ?></span>
                <span class="badge bg-primary-subtle text-primary border fs-6"><i class="bi bi-kanban me-1"></i> Program: <?= e($batch['program_number'] ?? '—') ?> (<?= e($batch['program_name'] ?? 'Production') ?>)</span>
                <?php if (!empty($batch['product_name'])): ?><span class="badge bg-light text-dark border">Product: <?= e($batch['product_name']) ?></span><?php endif; ?>
            </div>
            <?php elseif (!empty($batch['variant_id']) || !empty($batch['variant_code']) || !empty($batch['variant_sku'])): ?>
            <div class="col-12 pt-3 border-top d-flex gap-3 flex-wrap align-items-center">
                <span class="badge bg-primary fs-6 font-monospace">Variant ID: <?= (int)$batch['variant_id'] ?></span>
                <span class="badge bg-secondary-subtle text-dark border font-monospace">Variant: <?= e($batch['variant_code'] ?? '—') ?></span>
                <span class="badge bg-dark-subtle text-dark border font-monospace">SKU: <?= e($batch['variant_sku'] ?? '—') ?></span>
                <?php if (!empty($batch['color_name'])): ?><span class="badge bg-light text-dark border">Color: <?= e($batch['color_name']) ?></span><?php endif; ?>
                <?php if (!empty($batch['design_name'])): ?><span class="badge bg-light text-dark border">Design: <?= e($batch['design_name']) ?></span><?php endif; ?>
                <?php if (!empty($batch['size_name'])): ?><span class="badge bg-light text-dark border">Size: <?= e($batch['size_name']) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($batch['stitching_variant_items'])): ?>
            <div class="col-12 pt-3 border-top">
                <div class="text-muted small fw-semibold text-uppercase mb-2">Stitched Variant Details</div>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($batch['stitching_variant_items'] as $variantItem): ?>
                    <div class="border px-3 py-2 bg-light">
                        <div class="fw-bold font-monospace">
                            Variant ID: <?= (int)$variantItem['variant_id'] ?> · SKU: <?= e($variantItem['sku'] ?: ($variantItem['variant_code'] ?: '—')) ?>
                        </div>
                        <div class="small mt-1">
                            Size: <?= e($variantItem['variant_size_name'] ?: ($variantItem['size_name'] ?: '—')) ?>
                            · Color: <?= e($variantItem['variant_color_name'] ?: ($variantItem['color_name'] ?: '—')) ?>
                        </div>
                        <div class="small fw-bold mt-1">
                            MRP: <del>₹<?= number_format((float)(($variantItem['variant_mrp'] ?? 0) > 0 ? $variantItem['variant_mrp'] : ($variantItem['base_price'] ?? 0)), 2) ?></del>
                            · Selling: ₹<?= number_format((float)(($variantItem['variant_selling_price'] ?? 0) > 0 ? $variantItem['variant_selling_price'] : ($variantItem['discount_price'] ?? 0)), 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Generated QR Code Items Grid -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Generated QR Items (<?= count($batch['items']) ?>)</h5>
        <a href="<?= url('production/qr-generations/print/' . $batch['id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Thermal Sheet
        </a>
    </div>
    <div class="card-body p-4">
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($batch['items'] as $item): ?>
            <?php
                $itemQrCode = (string)($item['qr_code'] ?? '');
                $displayQrLabel = $isFabricBatch ? ($fabricMonthYear ?: $itemQrCode) : $itemQrCode;
                $sizeName = trim((string)($item['size_name'] ?: ($batch['size_name'] ?? '')));
                $colorName = trim((string)($item['color_name'] ?: ($batch['color_name'] ?? '')));
            ?>
            <div class="col">
                <div class="p-3 border rounded bg-white text-center shadow-sm h-100 d-flex flex-direction-column justify-content-between align-items-center">
                    <div class="w-100">
                        <div class="fw-bold text-uppercase small text-dark pb-1 border-bottom text-truncate">
                            <?= e($batch['brand_name']) ?>
                        </div>
                        <div class="d-flex justify-content-center my-2">
                            <div class="batch-qr-canvas p-1 bg-white border rounded" data-code="<?= e($item['qr_code']) ?>"></div>
                        </div>
                        <div class="font-monospace fw-bold text-primary text-truncate" style="font-size: 11px;">
                            <?= e($displayQrLabel) ?>
                        </div>
                        <?php if ($colorName !== '' || $sizeName !== ''): ?>
                        <div class="small text-muted mt-1">
                            <?= e($colorName) ?><?= $colorName !== '' && $sizeName !== '' ? ' · ' : '' ?><?php if ($sizeName !== ''): ?><span class="badge bg-dark text-white px-2 py-0" style="font-size: 10px;"><?= e($sizeName) ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ((float)($item['mrp'] ?? 0) > 0 || (float)($item['selling_price'] ?? 0) > 0): ?>
                        <div class="small fw-bold mt-1">
                            <?php if ((float)($item['mrp'] ?? 0) > 0): ?>
                            MRP: <del>₹<?= number_format((float)$item['mrp'], 2) ?></del>
                            <?php endif; ?>
                            <?php if ((float)($item['selling_price'] ?? 0) > 0): ?>
                            <span class="text-primary ms-1">SALE: ₹<?= number_format((float)$item['selling_price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isFabricBatch): ?>
                    <div class="w-100 d-flex justify-content-start align-items-center mt-2 pt-1 border-top small">
                        <span class="badge bg-light text-muted border">#<?= $item['sequence_num'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add to Stock action below the generated QR labels -->
<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h6 class="mb-1 fw-bold text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Purchased Stock Posting</h6>
            <div class="small text-muted mb-0">
                <?php if ($canAddToStock && !$alreadyAdded): ?>
                    Items are added to purchased stock only when you click the button below. QR generation alone does not change stock.
                <?php elseif ($alreadyAdded): ?>
                    Stock for this batch has already been posted to the warehouse purchased stock.
                <?php else: ?>
                    This batch type does not post to purchased stock directly.
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($canAddToStock): ?>
                <?php if ($alreadyAdded): ?>
                    <span class="btn btn-success fw-bold disabled"><i class="bi bi-check2-circle me-1"></i> Moved to Purchased Stock</span>
                    <a href="<?= url('production/purchased-stock') ?>" class="btn btn-outline-primary fw-bold">
                        <i class="bi bi-boxes me-1"></i> View in Purchased Stock
                    </a>
                <?php else: ?>
                    <form method="POST" action="<?= url('production/qr-generations/add-to-stock/' . $batch['id']) ?>" class="d-inline" data-confirm="Add <?= count($batch['items']) ?> pieces from this QR batch to purchased warehouse inventory?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                        <button type="submit" class="btn btn-success btn-lg fw-bold shadow-xs">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Add to Purchased Stock
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.batch-qr-canvas').forEach(function(el) {
        new QRCode(el, {
            text: el.dataset.code,
            width: 80,
            height: 80,
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});
</script>

<?php require __DIR__ . '/_qr_format_modal.php'; ?>
</div>
