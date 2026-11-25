<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Purchased Stock: <span class="font-monospace text-primary"><?= e($stock['batch_code']) ?></span></h2>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($listUrl) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchased Stock
        </a>
    </div>
</div>

<form action="<?= url('production/purchased-stock/edit/' . (int)$stock['generation_id']) ?>" method="POST" id="editStockForm">
    <?= CSRF::field() ?>

    <!-- 1. Stock Line & Roll Metadata (View Only) -->
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-light py-2 px-3 fw-bold small text-uppercase text-secondary d-flex align-items-center gap-2">
            <i class="bi bi-info-circle me-1 text-primary"></i> 1. Stock Line &amp; Roll Metadata
            <span class="badge bg-secondary-subtle text-secondary border ms-1">View Only</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Item / Material</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($stock['item_name']) ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Roll Number / Identifier</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($stock['roll_number'] ?: '—') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Weight (kg)</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= (float)($stock['weight_kg'] ?? 0) > 0 ? number_format((float)$stock['weight_kg'], 2) . ' kg' : '—' ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Brand Name</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($stock['brand_name'] ?: 'GRM GROUP') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Color</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($stock['color_name'] ?: '—') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Design Name / Pattern</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($stock['design_name'] ?: '—') ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Purchased Price (₹)</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="₹<?= number_format((float)($stock['purchase_price'] ?: $stock['unit_price']), 2) ?>" readonly>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold text-muted">Notes &amp; Remarks</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="<?= e($stock['notes'] ?? '') ?>" placeholder="Optional remarks...">
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Individual QR Labels Table -->
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold small text-uppercase text-secondary">
                <i class="bi bi-upc-scan me-1 text-primary"></i> 2. Individual QR Labels (<?= count($items) ?>)
            </span>
            <span class="small text-muted">Total Qty: <strong class="text-dark"><?= number_format((float)$stock['purchase_qty'], 2) ?> <?= e($stock['unit'] ?: 'PCS') ?></strong></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13.5px;">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="min-width: 140px;">QR Label</th>
                        <th style="min-width: 160px;">Color</th>
                        <th style="min-width: 180px;">Design Pattern</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $dateSrc = !empty($stock['purchase_date']) ? $stock['purchase_date'] : $stock['stock_added_at'];
                        $dateLabel = !empty($dateSrc) ? date('m/Y', strtotime($dateSrc)) : '';
                        $brandDisplay = strtoupper((string)($stock['brand_name'] ?: 'GRM GROUP'));
                        $rollColor = $stock['color_name'] ?? '';
                        $rollDesign = $stock['design_name'] ?? '';
                    ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $itemColor = $item['color_name'] ?: $rollColor;
                            $itemDesign = $item['design_name'] ?: $rollDesign;
                        ?>
                        <tr>
                            <td class="text-muted small fw-semibold"><?= (int)$item['sequence_num'] ?></td>
                            <td>
                                <div class="d-flex flex-column align-items-center p-2 border rounded bg-white text-center shadow-xs" style="width: 120px; min-width: 120px;">
                                    <div class="fw-bold text-dark text-uppercase mb-1" style="font-size: 9px; letter-spacing: 0.03em;">
                                        <?= e($brandDisplay) ?>
                                    </div>
                                    <div class="qr-canvas-holder" id="qr-canvas-<?= (int)$item['id'] ?>" data-code="<?= e($item['qr_code']) ?>"></div>
                                    <div class="mt-1 font-monospace text-primary" style="font-size: 10px; font-weight: bold;">
                                        <?= e($item['qr_code']) ?>
                                        <?php if ($dateLabel !== ''): ?>
                                            <span class="text-muted fw-normal" style="font-size: 9px;">(<?= e($dateLabel) ?>)</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Used Roll / In Stock indicator in second column -->
                                    <?php if (!empty($item['is_used_in_program'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-success text-white px-2 py-1" style="font-size: 10px; font-weight: 600;">
                                                <i class="bi bi-check-circle-fill me-1"></i>Used Roll
                                            </span>
                                            <?php if (!empty($item['used_program_label'])): ?>
                                                <div class="text-success fw-bold mt-1 text-truncate" style="font-size: 9px; max-width: 110px;" title="<?= e($item['used_program_label']) ?>">
                                                    <?= e($item['used_program_label']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 9px;">
                                                In Stock
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= e($itemColor ?: '—') ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= e($itemDesign ?: '—') ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="<?= e($viewUrl) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary fw-bold px-4" id="saveEditBtn">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
        </button>
    </div>
</form>

<!-- QR Code Generator JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.qr-canvas-holder').forEach(function(el) {
        const code = el.getAttribute('data-code');
        if (code && window.QRCode) {
            new QRCode(el, {
                text: code,
                width: 58,
                height: 58,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
});
</script>
