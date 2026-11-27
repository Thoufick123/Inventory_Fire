<?php
$items = $gen['items'] ?? [];
$totalLabels = count($items);
$concernName = trim((string)($concern['name'] ?? ($batch['concern_name'] ?? '')));
$brandLabel = trim((string)($gen['brand_name'] ?? '')) ?: ($concernName ?: 'GRM APPARELS');
$productTitle = trim((string)($gen['product_name'] ?? ($batch['product_name'] ?? ($batch['program_name'] ?? ($batch['name'] ?? 'Finished Garment')))));
$defaultSubSub = trim((string)($batch['sub_subcategory_name'] ?? ''));
$isPrinted = !empty($gen['labels_printed_at']);
$printCount = (int)($gen['labels_print_count'] ?? 0);

// Map stitching batch item sub-subcategories per variant_id or Size|Color
$subSubByVid = [];
$subSubByAttr = [];
$srcBatchItems = $batchItems ?? (class_exists('StitchingBatch') && !empty($batch['id']) ? StitchingBatch::getItems((int)$batch['id']) : []);
foreach ($srcBatchItems as $bi) {
    $subSub = trim((string)($bi['sub_subcategory_name'] ?? ''));
    if ($subSub === '') continue;
    $bVid = (int)($bi['variant_id'] ?? 0);
    if ($bVid > 0) {
        $subSubByVid[$bVid] = $subSub;
    }
    $bKey = strtoupper(trim((string)($bi['size_name'] ?? '')) . '|' . trim((string)($bi['color_name'] ?? '')));
    if ($bKey !== '|') {
        $subSubByAttr[$bKey] = $subSub;
    }
}

// Group items by Variant (same variant -> single shared QR card; different variants -> distinct QR cards)
$variantCards = [];
foreach ($items as $it) {
    $vid = (int)($it['variant_id'] ?? 0);
    $sizeStr = trim((string)($it['size_name'] ?: 'Standard'));
    $colorStr = trim((string)($it['color_name'] ?: 'Standard'));
    $attrKey = strtoupper($sizeStr . '|' . $colorStr);
    $groupKey = $vid > 0 ? ('v_' . $vid) : ('a_' . $attrKey);

    if (!isset($variantCards[$groupKey])) {
        $vCode = trim((string)($it['variant_code'] ?? ''));
        if ($vCode === '' && $vid > 0) {
            $vCode = 'V' . str_pad((string)$vid, 4, '0', STR_PAD_LEFT);
        }
        if ($vCode === '') {
            $rawQr = trim((string)($it['qr_code'] ?? ''));
            $vCode = str_contains($rawQr, '-') ? explode('-', $rawQr)[0] : ($rawQr ?: 'V0001');
        }
        $subSubStr = ($vid > 0 && isset($subSubByVid[$vid]))
            ? $subSubByVid[$vid]
            : ($subSubByAttr[$attrKey] ?? $defaultSubSub);

        $variantCards[$groupKey] = [
            'variant_id'           => $vid,
            'variant_code'         => $vCode,
            'sub_subcategory_name' => $subSubStr,
            'size_name'            => $sizeStr,
            'color_name'           => $colorStr,
            'mrp'                  => (float)($it['mrp'] ?? 0),
            'selling_price'        => (float)($it['selling_price'] ?? 0),
            'qty'                  => 0,
            'sample_qrs'           => [],
        ];
    }
    $variantCards[$groupKey]['qty']++;
    if (!empty($it['qr_code'])) {
        $variantCards[$groupKey]['sample_qrs'][] = $it['qr_code'];
    }
}
$variantCards = array_values($variantCards);
$totalVariants = count($variantCards);
?>
<style>
.variant-qr-card {
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    page-break-inside: avoid;
    break-inside: avoid;
}
.variant-qr-box {
    width: 86px;
    height: 86px;
    padding: 4px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.variant-price-text {
    font-size: 1.05rem;
    font-weight: 800;
    color: #881337;
}
@media print {
    .sidebar, .top-navbar, .app-footer, .no-print, .alert {
        display: none !important;
    }
    .main-content, .page-content, .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background: #fff !important;
    }
    .variant-print-hidden {
        display: none !important;
    }
    #variantQrCardsGrid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
    }
    #variantQrCardsGrid > .variant-card-col {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    .variant-qr-card {
        box-shadow: none !important;
        border: 1.5px solid #0f172a !important;
    }
}
</style>

<div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="mb-0"><i class="bi bi-qr-code me-2 text-primary"></i>Garment QR Labels</h2>
            <span class="badge bg-dark font-monospace fs-6"><?= e($gen['batch_code'] ?? '') ?></span>
            <span id="labelPrintStatusBadge" class="badge <?= $isPrinted ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?php if ($isPrinted): ?>
                    <i class="bi bi-printer-fill me-1"></i>Labels Printed (<?= $printCount ?>x)
                <?php else: ?>
                    <i class="bi bi-hourglass-split me-1"></i>Ready to Print
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Stitching
        </a>
        <button type="button" class="btn btn-outline-primary fw-semibold px-3" id="btnOpenStitchingPrintConfig" data-bs-toggle="modal" data-bs-target="#modalTscPrinterConfig">
            <i class="bi bi-sliders me-1"></i> Print Config
        </button>
        <button type="button" class="btn btn-dark fw-semibold px-3" id="btnPrintStitchingLabels" onclick="triggerStitchingLabelPrint(null)">
            <i class="bi bi-printer-fill me-1"></i> Print All Variant QRs (<?= $totalVariants ?>)
        </button>
    </div>
</div>

<?php if (empty($variantCards)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-qr-code fs-1 d-block mb-2"></i>
            No variant QR labels found for this batch.
        </div>
    </div>
<?php else: ?>
    <div class="row g-3" id="variantQrCardsGrid">
        <?php foreach ($variantCards as $vIdx => $vc): ?>
            <?php
                $vNum = $vIdx + 1;
                $vCode = $vc['variant_code'];
                $subSubName = trim((string)($vc['sub_subcategory_name'] ?? ''));
                $mrpVal = (float)$vc['mrp'];
                $spVal = (float)$vc['selling_price'];
            ?>
            <div class="col-12 col-sm-6 col-lg-3 variant-card-col" id="variant-card-<?= $vNum ?>">
                <div class="card h-100 variant-qr-card"
                     data-qr-code="<?= e($vCode) ?>"
                     data-concern="<?= e($concernName) ?>"
                     data-brand="<?= e($brandLabel) ?>"
                     data-sub-subcategory="<?= e($subSubName) ?>"
                     data-product-name="<?= e($productTitle) ?>"
                     data-size="<?= e($vc['size_name']) ?>"
                     data-color="<?= e($vc['color_name']) ?>"
                     data-attributes="<?= e($vc['size_name'] . ' / ' . $vc['color_name']) ?>"
                     data-mrp="<?= $mrpVal > 0 ? '₹' . number_format($mrpVal, 0) : '' ?>"
                     data-selling-price="<?= $spVal > 0 ? '₹' . number_format($spVal, 0) : '' ?>">
                    <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-secondary">Variant #<?= $vNum ?> of <?= $totalVariants ?></span>
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i><?= number_format((int)$vc['qty']) ?> pcs Ready
                        </span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <?php if ($concernName !== ''): ?>
                                <div class="text-center fw-bold text-secondary text-uppercase text-truncate mb-2 pb-1 border-bottom" style="font-size: 11.5px; letter-spacing: 0.5px;">
                                    <?= e($concernName) ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-center align-items-center gap-3 py-1">
                                <!-- Left Side: QR + Variant ID Below -->
                                <div class="d-flex flex-column align-items-center flex-shrink-0">
                                    <div class="variant-qr-box shadow-xs" id="variant_qr_<?= $vNum ?>" data-qr="<?= e($vCode) ?>"></div>
                                    <div class="font-monospace fw-bold text-dark mt-1" style="font-size: 11.5px; letter-spacing: 0.4px;"><?= e($vCode) ?></div>
                                </div>
                                <!-- Right Side: Brand, Sub-Subcategory, Size / Color, MRP, Disc -->
                                <div class="min-w-0 text-start">
                                    <div class="fw-bolder text-truncate text-uppercase" style="font-size: 13.5px; letter-spacing: 0.3px; color: #881337;">
                                        <?= e($brandLabel) ?>
                                    </div>
                                    <?php if ($subSubName !== ''): ?>
                                        <div class="fw-semibold text-dark text-truncate mb-1" style="font-size: 11.5px; letter-spacing: 0.2px;">
                                            <?= e($subSubName) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                        <?= e($vc['size_name']) ?> / <?= e($vc['color_name']) ?>
                                    </div>
                                    <?php if ($mrpVal > 0): ?>
                                        <div class="text-muted text-decoration-line-through mb-0.5" style="font-size: 12px;">
                                            MRP: ₹<?= number_format($mrpVal, 0) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($spVal > 0): ?>
                                        <div class="fw-bold" style="font-size: 13.5px; color: #15803d;">
                                            Disc: ₹<?= number_format($spVal, 0) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-1 pt-2 mt-2 border-top no-print">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="copyVariantQrCode('<?= e($vCode) ?>', this)" title="Copy Variant QR Code">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="triggerStitchingLabelPrint(<?= $vNum ?>)" title="Print This Variant Label">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- TSC TE244 Printer & Dynamic Label Layout Settings Modal -->
<div class="modal fade no-print" id="modalTscPrinterConfig" tabindex="-1" aria-labelledby="modalTscPrinterConfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: min(96vw, 1180px);">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2.5 px-3" style="background: #1f242d; color: #fff;">
                <h6 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" id="modalTscPrinterConfigLabel">
                    <i class="bi bi-sliders text-warning"></i>
                    <span>TSC TE244 Printer &amp; Dynamic Label Layout Settings</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light-subtle">
                <div class="row g-3">
                    <!-- Left Controls -->
                    <div class="col-lg-5 d-flex flex-column gap-3">
                        <!-- 1. Sticker Dimensions -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-bounding-box text-primary me-1"></i>1. Sticker Dimensions</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscWidthMm">Sticker Width (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscWidthMm" value="40" min="15" max="120">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscHeightMm">Sticker Height (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscHeightMm" value="25" min="15" max="120">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscColumns">Roll Columns</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscColumns">
                                            <option value="2" selected>2 Columns (Dual Roll)</option>
                                            <option value="1">1 Column (Single Roll)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscGapMm">Row Gap (mm)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscGapMm" value="2" min="0" max="20">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-primary mb-1" for="tscCol2Shift"><i class="bi bi-arrow-left-right me-1"></i>2nd Sticker (Right Column) X Shift (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscCol2Shift" value="0" min="-200" max="200">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. QR Code Alignment (Left Column) -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-qr-code text-success me-1"></i>2. QR Code Alignment (Left Column)</div>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1" for="tscQrX">QR X (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscQrX" value="12" min="0" max="300">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1" for="tscQrY">QR Y (dots)</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscQrY" value="35" min="0" max="300">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted mb-1" for="tscQrSize">QR Size</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscQrSize">
                                            <option value="2">Size 2 (Small)</option>
                                            <option value="3" selected>Size 3 (Medium)</option>
                                            <option value="4">Size 4 (Large)</option>
                                            <option value="5">Size 5 (XL)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscSubX">Code Subtitle X</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSubX" value="12" min="0" max="300">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscSubY">Code Subtitle Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSubY" value="125" min="0" max="300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Text Positions (Right Column) -->
                        <div class="card border shadow-xs">
                            <div class="card-body p-3">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-fonts text-info me-1"></i>3. Text Positions (Right Column)</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscTextX">Right Text X Offset</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscTextX" value="115" min="0" max="400">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1" for="tscFontSize">TSPL Font Size</label>
                                        <select class="form-select form-select-sm tsc-cfg-input" id="tscFontSize">
                                            <option value="1">Font 1 (Tiny)</option>
                                            <option value="2" selected>Font 2 (Standard)</option>
                                            <option value="3">Font 3 (Medium)</option>
                                            <option value="4">Font 4 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1" for="tscTitleY">Title Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscTitleY" value="35" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1" for="tscVarY">Variant Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscVarY" value="62" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1" for="tscMrpY">MRP Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscMrpY" value="90" min="0" max="300">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small text-muted mb-1" for="tscSpY">Disc Y</label>
                                        <input type="number" class="form-control form-control-sm tsc-cfg-input" id="tscSpY" value="118" min="0" max="300">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Live Dual-Sticker Preview -->
                    <div class="col-lg-7">
                        <div class="card border h-100">
                            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-dark"><i class="bi bi-eye text-primary me-1"></i>Live Dual-Sticker Preview</span>
                                <span class="badge bg-primary-subtle text-primary border" id="tscPreviewBadge">Dual Roll 40mm × 25mm</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center align-items-center" style="background: #e2e5ea; min-height: 360px;">
                                <div class="bg-white rounded-3 shadow-sm p-3 d-flex justify-content-center align-items-center gap-3 flex-wrap" id="tscLivePreviewStage"></div>
                                <div class="small text-muted mt-3" id="tscPreviewCaption">
                                    <i class="bi bi-info-circle me-1"></i>Simulating physical 2-column TSC thermal sticker roll layout.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnTscTestPrint">
                    <i class="bi bi-printer me-1"></i>Test Print 1 Sticker
                </button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold" id="btnSaveTscConfig">
                    <i class="bi bi-check-lg me-1"></i>Save Printer Configuration
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const TSC_CFG_STORAGE_KEY = 'grm_tsc_label_config_v1';

function getDefaultTscConfig() {
    return {
        widthMm: 40,
        heightMm: 25,
        columns: 2,
        gapMm: 2,
        col2Shift: 0,
        qrX: 12,
        qrY: 35,
        qrSize: 3,
        subX: 12,
        subY: 125,
        textX: 115,
        fontSize: 2,
        titleY: 35,
        varY: 62,
        mrpY: 90,
        spY: 118
    };
}

function getSavedTscConfig() {
    const def = getDefaultTscConfig();
    try {
        const raw = localStorage.getItem(TSC_CFG_STORAGE_KEY);
        if (!raw) return def;
        return Object.assign({}, def, JSON.parse(raw));
    } catch (e) {
        return def;
    }
}

function readTscConfigFromInputs() {
    const num = (id, fb) => {
        const v = parseFloat(document.getElementById(id)?.value);
        return Number.isFinite(v) ? v : fb;
    };
    const def = getDefaultTscConfig();
    return {
        widthMm: num('tscWidthMm', def.widthMm),
        heightMm: num('tscHeightMm', def.heightMm),
        columns: parseInt(document.getElementById('tscColumns')?.value || String(def.columns), 10) || 2,
        gapMm: num('tscGapMm', def.gapMm),
        col2Shift: num('tscCol2Shift', def.col2Shift),
        qrX: num('tscQrX', def.qrX),
        qrY: num('tscQrY', def.qrY),
        qrSize: parseInt(document.getElementById('tscQrSize')?.value || String(def.qrSize), 10) || 3,
        subX: num('tscSubX', def.subX),
        subY: num('tscSubY', def.subY),
        textX: num('tscTextX', def.textX),
        fontSize: parseInt(document.getElementById('tscFontSize')?.value || String(def.fontSize), 10) || 2,
        titleY: num('tscTitleY', def.titleY),
        varY: num('tscVarY', def.varY),
        mrpY: num('tscMrpY', def.mrpY),
        spY: num('tscSpY', def.spY)
    };
}

function writeTscConfigToInputs(cfg) {
    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    setVal('tscWidthMm', cfg.widthMm);
    setVal('tscHeightMm', cfg.heightMm);
    setVal('tscColumns', String(cfg.columns));
    setVal('tscGapMm', cfg.gapMm);
    setVal('tscCol2Shift', cfg.col2Shift);
    setVal('tscQrX', cfg.qrX);
    setVal('tscQrY', cfg.qrY);
    setVal('tscQrSize', String(cfg.qrSize));
    setVal('tscSubX', cfg.subX);
    setVal('tscSubY', cfg.subY);
    setVal('tscTextX', cfg.textX);
    setVal('tscFontSize', String(cfg.fontSize));
    setVal('tscTitleY', cfg.titleY);
    setVal('tscVarY', cfg.varY);
    setVal('tscMrpY', cfg.mrpY);
    setVal('tscSpY', cfg.spY);
}

function renderTscStickerHtml(cfg, data, labelBadge = '') {
    const scale = 0.82;
    const stickerW = Math.max(180, Math.round(cfg.widthMm * 6.4));
    const stickerH = Math.max(120, Math.round(cfg.heightMm * 6.4));
    const qrPx = Math.max(36, Math.min(88, cfg.qrSize * 16));
    const fontPx = Math.max(10, 7.5 + cfg.fontSize * 1.6);
    const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    const concern = esc((data.concern || '').slice(0, 24));
    const title = esc((data.brand || data.productName || 'GRM APPARELS').slice(0, 24));
    const subSub = esc((data.subSubcategory || '').slice(0, 24));
    const attrs = esc((data.attributes || '').slice(0, 24));
    const mrp = esc(data.mrp || '');
    const sp = esc(data.sellingPrice || '');
    const code = esc(data.qrCode || '');
    const isCol2 = data.colIndex === 2;
    const shiftPx = isCol2 ? Math.round(cfg.col2Shift * scale) : 0;
    const qrLeft = Math.round(cfg.qrX * scale) + shiftPx;
    const qrTop = Math.round(cfg.qrY * scale);
    const subLeft = Math.round(cfg.subX * scale) + shiftPx;
    const subTop = Math.round(cfg.subY * scale);
    const txtLeft = Math.round(cfg.textX * scale) + shiftPx;
    const txtMaxW = Math.max(70, stickerW - txtLeft - 8);
    const concernTop = 4;
    const titleTop = Math.round(cfg.titleY * scale);
    const varTop = Math.max(Math.round(cfg.varY * scale), titleTop + (subSub ? 21 : 12));

    return `
        <div class="border rounded bg-white position-relative overflow-hidden shadow-sm" style="width:${stickerW}px;height:${stickerH}px;">
            ${labelBadge ? `<span class="badge bg-light text-secondary border position-absolute" style="top:4px;left:6px;font-size:9px;z-index:2;">${esc(labelBadge)}</span>` : ''}
            ${concern ? `<div class="position-absolute fw-bold text-secondary text-uppercase text-truncate text-center px-2" style="left:0;right:0;top:${concernTop}px;font-size:${Math.max(8, fontPx - 2)}px;">${concern}</div>` : ''}
            <div class="position-absolute border rounded d-flex align-items-center justify-content-center bg-white tsc-qr-target" data-code="${code}" data-qr-px="${qrPx}" style="left:${qrLeft}px;top:${qrTop}px;width:${qrPx}px;height:${qrPx}px;"></div>
            <div class="position-absolute font-monospace fw-bold text-dark" style="left:${subLeft}px;top:${subTop}px;font-size:9.5px;">${code}</div>
            <div class="position-absolute" style="left:${txtLeft}px;top:${titleTop}px;max-width:${txtMaxW}px;line-height:1.12;">
                <div class="fw-bold text-uppercase text-truncate" style="font-size:${fontPx}px;color:#881337;">${title}</div>
                ${subSub ? `<div class="fw-semibold text-dark text-truncate" style="font-size:${Math.max(8, fontPx - 1.5)}px;">${subSub}</div>` : ''}
            </div>
            <div class="position-absolute fw-semibold text-dark text-truncate" style="left:${txtLeft}px;top:${varTop}px;font-size:${fontPx}px;max-width:${txtMaxW}px;">${attrs}</div>
            ${mrp ? `<div class="position-absolute text-muted text-decoration-line-through" style="left:${txtLeft}px;top:${Math.round(cfg.mrpY * scale)}px;font-size:${Math.max(8.5, fontPx - 1.5)}px;">MRP: ${mrp}</div>` : ''}
            ${sp ? `<div class="position-absolute fw-bold" style="left:${txtLeft}px;top:${Math.round(cfg.spY * scale)}px;font-size:${fontPx}px;color:#15803d;">Disc: ${sp}</div>` : ''}
        </div>
    `;
}

function renderTscLivePreview(cfg) {
    const stage = document.getElementById('tscLivePreviewStage');
    const badge = document.getElementById('tscPreviewBadge');
    const caption = document.getElementById('tscPreviewCaption');
    if (!stage) return;

    const isDual = cfg.columns === 2;
    if (badge) badge.textContent = `${isDual ? 'Dual Roll' : 'Single Roll'} ${cfg.widthMm}mm × ${cfg.heightMm}mm`;
    if (caption) caption.innerHTML = `<i class="bi bi-info-circle me-1"></i>Simulating physical ${cfg.columns}-column TSC thermal sticker roll layout.`;

    const cards = document.querySelectorAll('#variantQrCardsGrid .variant-qr-card');
    const c1 = cards[0]?.dataset || {};
    const c2 = cards[1]?.dataset || c1;

    const d1 = {
        concern: c1.concern || '<?= e($concernName) ?>',
        brand: c1.brand || '<?= e($brandLabel) ?>',
        subSubcategory: c1.subSubcategory || '<?= e($defaultSubSub) ?>',
        productName: c1.productName || '<?= e($productTitle) ?>',
        attributes: c1.attributes || 'S / LAVENDER',
        mrp: c1.mrp || '',
        sellingPrice: c1.sellingPrice || '',
        qrCode: c1.qrCode || 'V0012',
        colIndex: 1
    };
    const d2 = {
        concern: c2.concern || d1.concern,
        brand: c2.brand || d1.brand,
        subSubcategory: c2.subSubcategory || d1.subSubcategory,
        productName: c2.productName || d1.productName,
        attributes: c2.attributes || d1.attributes,
        mrp: c2.mrp || d1.mrp,
        sellingPrice: c2.sellingPrice || d1.sellingPrice,
        qrCode: c2.qrCode || d1.qrCode,
        colIndex: 2
    };

    stage.innerHTML = renderTscStickerHtml(cfg, d1, 'Left Sticker #1') + (isDual ? renderTscStickerHtml(cfg, d2, 'Right Sticker #2') : '');
    if (typeof QRCode !== 'undefined') {
        stage.querySelectorAll('.tsc-qr-target').forEach(el => {
            const qrPx = parseInt(el.dataset.qrPx || '48', 10);
            el.innerHTML = '';
            new QRCode(el, {
                text: el.dataset.code || 'QR',
                width: Math.max(28, qrPx - 4),
                height: Math.max(28, qrPx - 4),
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    }
}

function renderVariantQrCards() {
    if (typeof QRCode === 'undefined') return;
    document.querySelectorAll('.variant-qr-box').forEach(el => {
        if (el.dataset.rendered === '1') return;
        const code = el.getAttribute('data-qr') || '';
        if (!code) return;
        el.innerHTML = '';
        new QRCode(el, {
            text: code,
            width: 76,
            height: 76,
            colorDark: '#111827',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
        el.querySelectorAll('img').forEach(img => img.style.setProperty('display', 'none', 'important'));
        el.dataset.rendered = '1';
    });
}

function copyVariantQrCode(code, btn) {
    if (navigator.clipboard && code) {
        navigator.clipboard.writeText(code).then(() => {
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                setTimeout(() => { btn.innerHTML = orig; }, 1200);
            }
        }).catch(() => {});
    }
}

function printThermalHtmlViaFrame(htmlDoc) {
    let frame = document.getElementById('stitchingThermalPrintFrame');
    if (frame) {
        frame.remove();
    }
    frame = document.createElement('iframe');
    frame.id = 'stitchingThermalPrintFrame';
    frame.style.position = 'fixed';
    frame.style.right = '0';
    frame.style.bottom = '0';
    frame.style.width = '0';
    frame.style.height = '0';
    frame.style.border = '0';
    frame.style.visibility = 'hidden';
    document.body.appendChild(frame);

    let printed = false;
    const triggerFramePrint = () => {
        if (printed) return;
        printed = true;
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (err) {
            window.print();
        }
    };

    const doc = frame.contentWindow.document;
    doc.open();
    doc.write(htmlDoc);
    doc.close();

    setTimeout(triggerFramePrint, 120);
}

function renderPrintableStickerMarkup(d, qrSrc, cfg, isCol2) {
    const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const shiftMm = isCol2 ? (cfg.col2Shift * 0.125) : 0;
    const qrMm = Math.max(8, Math.min(cfg.heightMm - 8, cfg.qrSize * 3.4));
    const qrLeftMm = (cfg.qrX * 0.125) + shiftMm;
    const qrTopMm = Math.max(4.4, cfg.qrY * 0.125);
    const subLeftMm = (cfg.subX * 0.125) + shiftMm;
    const subTopMm = Math.min(cfg.heightMm - 3.2, qrTopMm + qrMm + 0.6);
    const txtLeftMm = (cfg.textX * 0.125) + shiftMm;
    const fontPt = Math.max(5.5, 4.2 + cfg.fontSize * 1.0);
    const concernTxt = d.concern || '';
    const brandTxt = d.brand || '';
    const subSubTxt = d.subSubcategory || '';

    return `
        <div class="sticker">
            ${concernTxt ? `<div style="position:absolute;left:${shiftMm}mm;width:${cfg.widthMm}mm;top:0.8mm;text-align:center;font-size:${Math.max(4.8, fontPt - 1.2)}pt;font-weight:700;color:#475569;text-transform:uppercase;white-space:nowrap;overflow:hidden;">${esc(concernTxt)}</div>` : ''}
            ${qrSrc ? `<img src="${qrSrc}" style="position:absolute;left:${qrLeftMm}mm;top:${qrTopMm}mm;width:${qrMm}mm;height:${qrMm}mm;">` : ''}
            <div style="position:absolute;left:${subLeftMm}mm;width:${qrMm}mm;top:${subTopMm}mm;text-align:center;font-family:monospace;font-size:5.5pt;font-weight:700;line-height:1;">${esc(d.qrCode)}</div>
            <div style="position:absolute;left:${txtLeftMm}mm;top:${qrTopMm}mm;width:${Math.max(12, cfg.widthMm - txtLeftMm - 1)}mm;display:flex;flex-direction:column;gap:0.45mm;line-height:1.15;">
                <div style="font-size:${fontPt}pt;font-weight:700;color:#881337;text-transform:uppercase;white-space:nowrap;overflow:hidden;">${esc(brandTxt)}</div>
                ${subSubTxt ? `<div style="font-size:${Math.max(4.8, fontPt - 1.0)}pt;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;">${esc(subSubTxt)}</div>` : ''}
                <div style="font-size:${Math.max(5, fontPt - 0.4)}pt;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;">${esc(d.attributes)}</div>
                ${d.mrp ? `<div style="font-size:${Math.max(4.8, fontPt - 1.1)}pt;color:#64748b;text-decoration:line-through;white-space:nowrap;">MRP: ${esc(d.mrp)}</div>` : ''}
                ${d.sellingPrice ? `<div style="font-size:${fontPt}pt;font-weight:700;color:#15803d;white-space:nowrap;">Disc: ${esc(d.sellingPrice)}</div>` : ''}
            </div>
        </div>
    `;
}

function buildThermalPrintWindow(cardNodes, cfg) {
    const effectiveCols = (cfg.columns === 2 && cardNodes.length > 1) ? 2 : 1;
    const pageW = effectiveCols === 2 ? (cfg.widthMm * 2 + cfg.gapMm) : cfg.widthMm;
    const stickersHtml = cardNodes.map((wrap, idx) => {
        const card = wrap.querySelector('.variant-qr-card') || wrap;
        const qrEl = wrap.querySelector('.variant-qr-box canvas, .variant-qr-box img');
        const qrSrc = qrEl ? (qrEl.tagName === 'CANVAS' ? qrEl.toDataURL() : qrEl.src) : '';
        return renderPrintableStickerMarkup(card.dataset, qrSrc, cfg, effectiveCols === 2 && idx % 2 === 1);
    }).join('');

    const htmlDoc = `<!doctype html><html><head><title></title><style>@page{size:${pageW}mm ${cfg.heightMm}mm;margin:0}html,body{margin:0;padding:0;font-family:sans-serif;width:${pageW}mm}body{display:flex;flex-wrap:wrap;gap:${cfg.gapMm}mm}.sticker{position:relative;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm;overflow:hidden;box-sizing:border-box;page-break-inside:avoid}</style></head><body>${stickersHtml}</body></html>`;
    printThermalHtmlViaFrame(htmlDoc);
    return true;
}

function triggerStitchingLabelPrint(singleCardNum) {
    const cards = Array.from(document.querySelectorAll('#variantQrCardsGrid .variant-card-col'));
    const selectedCards = singleCardNum
        ? cards.filter(c => c.id === 'variant-card-' + singleCardNum)
        : cards;

    cards.forEach(c => {
        if (singleCardNum && c.id !== 'variant-card-' + singleCardNum) {
            c.classList.add('variant-print-hidden');
        } else {
            c.classList.remove('variant-print-hidden');
        }
    });

    const formData = new FormData();
    formData.append('_csrf_token', '<?= e(CSRF::token()) ?>');

    fetch('<?= url('production/stitching/labels/' . (int)$batch['id'] . '/mark-printed') ?>', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.success) {
            const badge = document.getElementById('labelPrintStatusBadge');
            if (badge) {
                badge.className = 'badge bg-success';
                badge.innerHTML = `<i class="bi bi-printer-fill me-1"></i>Labels Printed (${res.labels_print_count}x)`;
            }
        }
    })
    .catch(() => {})
    .finally(() => {
        const cfg = getSavedTscConfig();
        const printedThermal = selectedCards.length > 0 && buildThermalPrintWindow(selectedCards, cfg);
        if (!printedThermal) {
            window.print();
        }
        setTimeout(() => {
            cards.forEach(c => c.classList.remove('variant-print-hidden'));
        }, 300);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    renderVariantQrCards();

    const tscConfigModalEl = document.getElementById('modalTscPrinterConfig');
    if (tscConfigModalEl) {
        tscConfigModalEl.addEventListener('shown.bs.modal', function () {
            const cfg = getSavedTscConfig();
            writeTscConfigToInputs(cfg);
            renderTscLivePreview(cfg);
        });
        tscConfigModalEl.addEventListener('input', function (e) {
            if (e.target.classList.contains('tsc-cfg-input')) {
                renderTscLivePreview(readTscConfigFromInputs());
            }
        });
        tscConfigModalEl.addEventListener('change', function (e) {
            if (e.target.classList.contains('tsc-cfg-input')) {
                renderTscLivePreview(readTscConfigFromInputs());
            }
        });
    }

    document.getElementById('btnSaveTscConfig')?.addEventListener('click', function () {
        const cfg = readTscConfigFromInputs();
        try {
            localStorage.setItem(TSC_CFG_STORAGE_KEY, JSON.stringify(cfg));
        } catch (err) {}
        const cfgInst = bootstrap.Modal.getInstance(tscConfigModalEl);
        cfgInst?.hide();
    });

    document.getElementById('btnTscTestPrint')?.addEventListener('click', function () {
        const cfg = readTscConfigFromInputs();
        const sampleCard = document.querySelector('#variantQrCardsGrid .variant-qr-card');
        const qrEl = document.querySelector('#tscLivePreviewStage .tsc-qr-target canvas, #tscLivePreviewStage .tsc-qr-target img');
        const qrSrc = qrEl ? (qrEl.tagName === 'CANVAS' ? qrEl.toDataURL() : qrEl.src) : '';
        const sampleData = {
            concern: sampleCard?.dataset.concern || '<?= e($concernName) ?>',
            brand: sampleCard?.dataset.brand || '<?= e($brandLabel) ?>',
            subSubcategory: sampleCard?.dataset.subSubcategory || '<?= e($defaultSubSub) ?>',
            attributes: sampleCard?.dataset.attributes || 'S / LAVENDER',
            mrp: sampleCard?.dataset.mrp || '',
            sellingPrice: sampleCard?.dataset.sellingPrice || '',
            qrCode: sampleCard?.dataset.qrCode || 'V0012'
        };
        const stickerHtml = renderPrintableStickerMarkup(sampleData, qrSrc, cfg, false);
        const htmlDoc = `<!doctype html><html><head><title></title><style>@page{size:${cfg.widthMm}mm ${cfg.heightMm}mm;margin:0}html,body{margin:0;padding:0;font-family:sans-serif;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm}.sticker{position:relative;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm;overflow:hidden;box-sizing:border-box}</style></head><body>${stickerHtml}</body></html>`;
        printThermalHtmlViaFrame(htmlDoc);
    });
});
</script>
