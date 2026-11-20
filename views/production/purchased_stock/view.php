<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-qr-code text-primary me-2"></i>Purchased Stock Details: <span class="font-monospace text-primary"><?= e($stock['batch_code']) ?></span></h2>
            <?php if ($stock['item_type'] === 'FABRIC_ROLL'): ?>
                <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-layers-half me-1"></i>Fabric Roll</span>
            <?php elseif ($stock['item_type'] === 'RAW_MATERIAL'): ?>
                <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-boxes me-1"></i>Raw Material</span>
            <?php else: ?>
                <span class="badge bg-info-subtle text-dark border"><i class="bi bi-box-seam me-1"></i>Third-Party</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Brand: <strong class="text-dark"><?= e($stock['brand_name'] ?: 'GRM GROUP') ?></strong> · <?= count($items) ?> QR Unit Labels</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($listUrl) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
        <a href="<?= e($editUrl) ?>" class="btn btn-warning text-dark fw-bold">
            <i class="bi bi-pencil-square me-1"></i> Edit Details
        </a>
        <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-primary fw-bold">
            <i class="bi bi-printer-fill me-1"></i> Print All Labels
        </a>
    </div>
</div>

<!-- Product & Procurement Summary Card -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-2 px-3 fw-bold small text-uppercase text-secondary">
        <i class="bi bi-info-circle me-1 text-primary"></i> Product &amp; Procurement Summary
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">ITEM / MATERIAL NAME</div>
                <div class="fw-bold fs-6 text-dark mt-1"><?= e($stock['item_name']) ?></div>
                <div class="small text-muted mt-1">
                    <?php if (!empty($stock['roll_number'])): ?>
                        <span class="badge bg-light text-dark border me-1"><?= e($stock['roll_number']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($stock['color_name'])): ?>
                        <span class="me-1">Color: <strong><?= e($stock['color_name']) ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($stock['design_name'])): ?>
                        <span class="me-1">· Pattern: <strong><?= e($stock['design_name']) ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($stock['vendor_sku'])): ?>
                        <span class="font-monospace text-muted">[<?= e($stock['vendor_sku']) ?>]</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">PURCHASE ORDER &amp; VENDOR</div>
                <div class="mt-1">
                    <a href="<?= e($purchaseUrl) ?>" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none">
                        <?= e($stock['purchase_number']) ?>
                    </a>
                </div>
                <div class="small text-dark fw-semibold"><?= e($stock['seller_company'] ?: $stock['seller_name']) ?></div>
                <?php if (!empty($stock['purchase_date'])): ?>
                    <div class="small text-muted">Date: <?= date('d M Y', strtotime($stock['purchase_date'])) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">QUANTITY &amp; VALUATION</div>
                <div class="fs-6 fw-bold text-dark mt-1">
                    <?= number_format((float)$stock['purchase_qty'], 2) ?> <?= e($stock['unit'] ?: 'PCS') ?>
                    <?php if ((float)($stock['weight_kg'] ?? 0) > 0): ?>
                        <span class="text-muted fw-normal fs-6">(<?= number_format((float)$stock['weight_kg'], 2) ?> kg)</span>
                    <?php endif; ?>
                </div>
                <div class="small text-muted">
                    Purchased Price: <span class="fw-semibold text-dark">₹<?= number_format((float)($stock['purchase_price'] ?: $stock['unit_price']), 2) ?></span>
                    · Total: <span class="fw-semibold text-success">₹<?= number_format((float)$stock['total_price'], 2) ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">CATEGORY HIERARCHY</div>
                <div class="small fw-semibold text-dark mt-1">
                    <?= e($stock['category_name'] ?: 'General') ?>
                    <?php if (!empty($stock['subcategory_name'])): ?> › <?= e($stock['subcategory_name']) ?><?php endif; ?>
                    <?php if (!empty($stock['sub_subcategory_name'])): ?> › <?= e($stock['sub_subcategory_name']) ?><?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">STOCK RECEIPT &amp; AUDIT</div>
                <div class="small fw-semibold text-dark mt-1"><?= !empty($stock['stock_added_at']) ? date('d M Y H:i', strtotime($stock['stock_added_at'])) : '—' ?></div>
                <div class="small text-muted">Received by: <span class="text-dark"><?= e($stock['stock_added_by_name'] ?: 'System') ?></span></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-semibold">BRAND &amp; NOTES</div>
                <div class="small fw-bold text-dark text-uppercase mt-1"><?= e($stock['brand_name'] ?: 'GRM GROUP') ?></div>
                <div class="small text-muted fst-italic"><?= e($stock['notes'] ?: 'No remarks recorded.') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Generated QR Items Grid Card -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Generated QR Items (<?= count($items) ?>)
            </h6>
            <span class="small text-muted">All individual QR barcodes generated for this stock line</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="qrSearchInput" class="form-control form-control-sm" placeholder="Filter QR codes..." style="max-width: 220px;" oninput="filterQrCards(this.value)">
        </div>
    </div>
    <div class="card-body p-3">
        <?php if (empty($items)): ?>
            <div class="text-center py-4 text-muted small">No QR items recorded for this batch.</div>
        <?php else: ?>
            <?php
                // Format MM/YYYY date code
                $dateSrc = !empty($stock['purchase_date']) ? $stock['purchase_date'] : $stock['stock_added_at'];
                $dateLabel = !empty($dateSrc) ? date('m/Y', strtotime($dateSrc)) : '';
                $brandDisplay = strtoupper((string)($stock['brand_name'] ?: 'GRM GROUP'));
            ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="qrCardsContainer">
                <?php foreach ($items as $item): ?>
                    <div class="col qr-card-item" data-code="<?= strtolower(e($item['qr_code'])) ?>">
                        <div class="p-3 border rounded bg-white text-center shadow-xs h-100 d-flex flex-column align-items-center justify-content-between" style="min-height: 200px;">
                            <div class="w-100">
                                <!-- Brand Name at top (Bold uppercase) -->
                                <div class="fw-bold text-dark text-uppercase pb-1 border-bottom" style="font-size: 11px; letter-spacing: 0.04em;">
                                    <?= e($brandDisplay) ?>
                                </div>
                                <!-- Large QR Canvas Center -->
                                <div class="d-flex justify-content-center my-2">
                                    <div class="qr-canvas-holder p-1 bg-white border rounded" id="qr-canvas-<?= (int)$item['id'] ?>" data-code="<?= e($item['qr_code']) ?>"></div>
                                </div>
                                <!-- MM/YYYY Date at Bottom in Monospace Blue -->
                                <?php if ($dateLabel !== ''): ?>
                                    <div class="font-monospace fw-bold text-primary" style="font-size: 12px;">
                                        <?= e($dateLabel) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="font-monospace fw-bold text-primary" style="font-size: 11px;">
                                        <?= e($item['qr_code']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Color & Design Pattern -->
                                <?php
                                    $cName = trim((string)($item['color_name'] ?: ($stock['color_name'] ?? '')));
                                    $dName = trim((string)($item['design_name'] ?: ($stock['design_name'] ?? '')));
                                ?>
                                <?php if ($cName !== '' || $dName !== ''): ?>
                                    <div class="small text-muted mt-1" style="font-size: 11px;">
                                        <?= e($cName) ?><?= ($cName !== '' && $dName !== '') ? ' · ' : '' ?><?= e($dName) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="w-100 d-flex justify-content-between align-items-center mt-2 pt-1 border-top" style="font-size: 10px;">
                                <span class="badge bg-light text-muted border">#<?= $item['sequence_num'] ?></span>
                                <span class="font-monospace text-muted"><?= e($item['qr_code']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= e($listUrl) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Stock List
    </a>
    <div class="d-flex gap-2">
        <a href="<?= e($editUrl) ?>" class="btn btn-warning text-dark fw-bold">
            <i class="bi bi-pencil-square me-1"></i> Edit Details
        </a>
        <a href="<?= e($printUrl) ?>" target="_blank" class="btn btn-primary fw-bold">
            <i class="bi bi-printer-fill me-1"></i> Print All Labels
        </a>
    </div>
</div>

<!-- QR Code Generator JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.qr-canvas-holder').forEach(function(el) {
        const code = el.getAttribute('data-code');
        if (code && window.QRCode) {
            new QRCode(el, {
                text: code,
                width: 112,
                height: 112,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
});

function filterQrCards(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.qr-card-item').forEach(function(el) {
        if (!q || el.getAttribute('data-code').includes(q)) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
}
</script>
