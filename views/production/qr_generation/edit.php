<div class="qr-generation-ui">
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-pencil-square text-warning me-2"></i>Edit QR Batch: <span class="font-monospace text-primary"><?= e($batch['batch_code']) ?></span></h2>
            <?php if (($batch['generation_type'] ?? '') === 'MANUAL'): ?>
                <span class="badge bg-info-subtle text-info border border-info-subtle">Manual Custom</span>
            <?php else: ?>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Catalog Linked</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">
            Brand: <strong class="text-dark"><?= e($batch['brand_name'] ?: 'GRM GROUP') ?></strong> · <?= count($items) ?> QR Unit Labels · Created <?= date('d M Y, h:i A', strtotime($batch['created_at'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($listUrl) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to QR Generations
        </a>
    </div>
</div>

<form action="<?= url('production/qr-generations/edit/' . (int)$batch['id']) ?>" method="POST" id="editQrBatchForm">
    <?= CSRF::field() ?>
    <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

    <!-- 1. Batch Details & Default Attributes -->
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-2 px-3 fw-bold small text-uppercase text-secondary d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-sliders me-1 text-primary"></i> 1. Batch Details &amp; Default Attributes
            </div>
            <span class="badge bg-primary-subtle text-primary border"><?= e($batch['qr_source'] ?? 'GENERAL') ?></span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Batch Code</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold text-primary" value="<?= e($batch['batch_code']) ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">QR Prefix Pattern</label>
                    <input type="text" class="form-control form-control-sm bg-light font-monospace" value="<?= e($batch['qr_prefix'] ?? 'QR') ?>-XXXX" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Printed Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="brand_name" class="form-control form-control-sm" value="<?= e($batch['brand_name'] ?? '') ?>" list="brandsDatalist" required>
                    <datalist id="brandsDatalist">
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= e($b['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Category Hierarchy</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= e($batch['category_name'] ?: 'General') ?><?= !empty($batch['subcategory_name']) ? ' › ' . e($batch['subcategory_name']) : '' ?><?= !empty($batch['sub_subcategory_name']) ? ' › ' . e($batch['sub_subcategory_name']) : '' ?>" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Default Color</label>
                    <input type="text" name="color_name" class="form-control form-control-sm" value="<?= e($batch['color_name'] ?? '') ?>" placeholder="e.g. Blue, Red..." list="colorsDatalist">
                    <datalist id="colorsDatalist">
                        <?php foreach ($colors as $c): ?>
                            <option value="<?= e($c['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Default Design / Pattern</label>
                    <input type="text" name="design_name" class="form-control form-control-sm" value="<?= e($batch['design_name'] ?? '') ?>" placeholder="e.g. Plain, Checked..." list="designsDatalist">
                    <datalist id="designsDatalist">
                        <?php foreach ($designs as $d): ?>
                            <option value="<?= e($d['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Default Size</label>
                    <input type="text" name="size_name" class="form-control form-control-sm" value="<?= e($batch['size_name'] ?? '') ?>" placeholder="e.g. M, L, XL..." list="sizesDatalist">
                    <datalist id="sizesDatalist">
                        <?php foreach ($sizes as $s): ?>
                            <option value="<?= e($s['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Default MRP (₹)</label>
                    <input type="number" step="0.01" name="mrp" class="form-control form-control-sm" value="<?= (float)($batch['mrp'] ?? 0) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Default Selling Price (₹)</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control form-control-sm" value="<?= (float)($batch['selling_price'] ?? 0) ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-semibold text-muted">Notes &amp; Remarks</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="<?= e($batch['notes'] ?? '') ?>" placeholder="Optional notes for this QR batch...">
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
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="qrFilterInput" class="form-control form-control-sm" placeholder="Filter QR codes..." style="max-width: 200px;" oninput="filterQrRows(this.value)">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyDefaultsToAll()" title="Apply default Color, Design &amp; Size to all items below">
                    <i class="bi bi-arrow-down-square me-1"></i> Sync Defaults
                </button>
            </div>
        </div>
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-sm table-hover align-middle mb-0" id="qrItemsTable" style="font-size: 13.5px;">
                <thead class="table-light text-uppercase small sticky-top">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="min-width: 130px;">QR Label</th>
                        <th style="min-width: 140px;">Color</th>
                        <th style="min-width: 150px;">Design / Pattern</th>
                        <th style="min-width: 100px;">Size</th>
                        <th style="min-width: 110px;">MRP (₹)</th>
                        <th style="min-width: 110px;">Selling Price (₹)</th>
                        <th style="min-width: 120px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $brandDisplay = strtoupper((string)($batch['brand_name'] ?: 'GRM GROUP'));
                        $dateSrc = !empty($batch['source_purchase_date']) ? $batch['source_purchase_date'] : $batch['created_at'];
                        $dateLabel = !empty($dateSrc) ? date('m/Y', strtotime($dateSrc)) : '';
                    ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $cVal = $item['color_name'] ?: ($batch['color_name'] ?? '');
                            $dVal = $item['design_name'] ?: ($batch['design_name'] ?? '');
                            $sVal = $item['size_name'] ?: ($batch['size_name'] ?? '');
                            $mVal = (float)($item['mrp'] ?: ($batch['mrp'] ?? 0));
                            $spVal = (float)($item['selling_price'] ?: ($batch['selling_price'] ?? 0));
                            $statVal = strtoupper((string)($item['status'] ?? 'ACTIVE'));
                        ?>
                        <tr class="qr-item-row" data-code="<?= strtolower(e($item['qr_code'])) ?>">
                            <td class="text-muted small fw-semibold"><?= (int)$item['sequence_num'] ?></td>
                            <td>
                                <div class="d-flex flex-column align-items-center p-2 border rounded bg-white text-center shadow-xs" style="width: 110px; min-width: 110px;">
                                    <div class="fw-bold text-dark text-uppercase mb-1" style="font-size: 8.5px; letter-spacing: 0.03em;">
                                        <?= e($brandDisplay) ?>
                                    </div>
                                    <div class="qr-canvas-holder" id="qr-canvas-<?= (int)$item['id'] ?>" data-code="<?= e($item['qr_code']) ?>"></div>
                                    <div class="font-monospace fw-bold text-primary mt-1" style="font-size: 10px;">
                                        <?= $dateLabel !== '' ? e($dateLabel) : e($item['qr_code']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="items[<?= (int)$item['id'] ?>][color_name]" class="form-control form-control-sm item-color-input" value="<?= e($cVal) ?>" placeholder="Color" list="colorsDatalist">
                            </td>
                            <td>
                                <input type="text" name="items[<?= (int)$item['id'] ?>][design_name]" class="form-control form-control-sm item-design-input" value="<?= e($dVal) ?>" placeholder="Pattern" list="designsDatalist">
                            </td>
                            <td>
                                <input type="text" name="items[<?= (int)$item['id'] ?>][size_name]" class="form-control form-control-sm item-size-input" value="<?= e($sVal) ?>" placeholder="Size" list="sizesDatalist">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="items[<?= (int)$item['id'] ?>][mrp]" class="form-control form-control-sm item-mrp-input" value="<?= $mVal ?>">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="items[<?= (int)$item['id'] ?>][selling_price]" class="form-control form-control-sm item-sp-input" value="<?= $spVal ?>">
                            </td>
                            <td>
                                <select name="items[<?= (int)$item['id'] ?>][status]" class="form-select form-select-sm">
                                    <option value="ACTIVE" <?= $statVal === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                    <option value="DAMAGED" <?= $statVal === 'DAMAGED' ? 'selected' : '' ?>>Damaged</option>
                                    <option value="CANCELLED" <?= $statVal === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="USED" <?= $statVal === 'USED' ? 'selected' : '' ?>>Used</option>
                                </select>
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
                width: 52,
                height: 52,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
});

function filterQrRows(query) {
    query = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.qr-item-row');
    rows.forEach(function(row) {
        const code = row.getAttribute('data-code') || '';
        if (query === '' || code.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function applyDefaultsToAll() {
    const defColor = document.querySelector('input[name="color_name"]').value.trim();
    const defDesign = document.querySelector('input[name="design_name"]').value.trim();
    const defSize = document.querySelector('input[name="size_name"]').value.trim();
    const defMrp = document.querySelector('input[name="mrp"]').value.trim();
    const defSp = document.querySelector('input[name="selling_price"]').value.trim();

    if (!confirm('Apply default Color, Design, Size, MRP and Selling Price to all individual labels in the table?')) {
        return;
    }

    if (defColor) {
        document.querySelectorAll('.item-color-input').forEach(el => el.value = defColor);
    }
    if (defDesign) {
        document.querySelectorAll('.item-design-input').forEach(el => el.value = defDesign);
    }
    if (defSize) {
        document.querySelectorAll('.item-size-input').forEach(el => el.value = defSize);
    }
    if (defMrp) {
        document.querySelectorAll('.item-mrp-input').forEach(el => el.value = defMrp);
    }
    if (defSp) {
        document.querySelectorAll('.item-sp-input').forEach(el => el.value = defSp);
    }
}
</script>
