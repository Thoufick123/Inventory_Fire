<!-- Thermal Printer QR Format & Layout Configuration Modal -->
<style>
#printerSettingsModal .modal-content,
#printerSettingsModal .card,
#printerSettingsModal .btn,
#printerSettingsModal .form-control,
#printerSettingsModal .form-select,
#printerSettingsModal .badge,
#printerSettingsModal .rounded,
#printerSettingsModal .rounded-3,
#printerSettingsModal .rounded-pill,
#liveStickerContainer,
#liveStickerCard1,
#liveStickerCard2,
#prevQRBox1,
#prevQRBox2 {
    border-radius: 0 !important;
}
</style>
<div class="modal fade" id="printerSettingsModal" tabindex="-1" aria-labelledby="printerSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1240px; width: 95%;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="printerSettingsModalLabel">
                        <i class="bi bi-sliders me-2 text-warning"></i>QR Format &amp; Thermal Printer Layout Configuration
                    </h5>
                    <div class="text-white-50 small">
                        Calibrate sticker dimensions, roll columns, QR code sizes, and dot positions for TSC TE244 &amp; thermal label printers.
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Left Side: Interactive Calibration Controls -->
                    <div class="col-lg-5 col-md-6">
                        <!-- 1. Dimensions -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2 d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-bounding-box me-2 text-primary"></i>1. Sticker Dimensions</span>
                                <span class="badge bg-primary-subtle text-primary border">Roll Specs</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Width (mm)</label>
                                        <input type="number" id="cfgWidth" class="form-control form-control-sm" value="40" min="20" max="150" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Height (mm)</label>
                                        <input type="number" id="cfgHeight" class="form-control form-control-sm" value="25" min="15" max="150" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Roll Columns</label>
                                        <select id="cfgColumns" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">2 Columns (Dual Roll)</option>
                                            <option value="1">1 Column (Single Roll)</option>
                                            <option value="4">4 Columns (A4 / Sheet Grid)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Row Gap (mm)</label>
                                        <input type="number" id="cfgGap" class="form-control form-control-sm" value="2" min="0" max="20" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-semibold mb-1 text-primary">
                                            <i class="bi bi-arrow-left-right me-1"></i>2nd Sticker (Right Column) X Shift (dots)
                                        </label>
                                        <input type="number" id="cfgCol2ShiftDots" class="form-control form-control-sm border-primary" value="0" placeholder="e.g. -10 to move left, +10 to move right" oninput="updateLivePreview()">
                                        <div class="form-text small" style="font-size: 11px;">Fine-tune right column alignment for dual-roll thermal printers.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. QR Code Alignment -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2 d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-qr-code me-2 text-success"></i>2. QR Code Sizing &amp; Position</span>
                                <span class="badge bg-success-subtle text-success border">Left Column</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR X (dots)</label>
                                        <input type="number" id="cfgQrX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Y (dots)</label>
                                        <input type="number" id="cfgQrY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Size</label>
                                        <select id="cfgQrCellSize" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="3">Size 3 (Medium)</option>
                                            <option value="2">Size 2 (Small)</option>
                                            <option value="4">Size 4 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle X</label>
                                        <input type="number" id="cfgCodeX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle Y</label>
                                        <input type="number" id="cfgCodeY" class="form-control form-control-sm" value="125" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Text Positions -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-bold py-2 d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-fonts me-2 text-info"></i>3. Text Positions &amp; Fonts</span>
                                <span class="badge bg-info-subtle text-info border">Right Column</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Right Text X Offset</label>
                                        <input type="number" id="cfgTextX" class="form-control form-control-sm" value="115" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">TSPL Font Size</label>
                                        <select id="cfgTextFont" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">Font 2 (Standard)</option>
                                            <option value="1">Font 1 (Small)</option>
                                            <option value="3">Font 3 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Title Y</label>
                                        <input type="number" id="cfgTitleY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">SKU Y</label>
                                        <input type="number" id="cfgSkuY" class="form-control form-control-sm" value="62" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">MRP Y</label>
                                        <input type="number" id="cfgMrpY" class="form-control form-control-sm" value="90" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Price Y</label>
                                        <input type="number" id="cfgDiscY" class="form-control form-control-sm" value="118" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Live Visual Preview Canvas -->
                    <div class="col-lg-7 col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold py-2 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-eye me-2 text-primary"></i>Live Sticker Layout Preview</span>
                                <span class="badge bg-primary-subtle text-primary border" id="previewDimensionBadge">Dual Roll 40mm × 25mm</span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center bg-secondary-subtle rounded m-3 overflow-auto" style="min-height: 380px;">
                                <div id="liveStickerContainer" class="d-flex align-items-center gap-3 p-3 bg-white rounded border shadow-sm flex-wrap justify-content-center">
                                    <!-- Left Sticker #1 -->
                                    <div id="liveStickerCard1" class="bg-white border border-dark rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 150px; font-family: -apple-system, BlinkMacSystemFont, monospace; overflow: hidden; background: #ffffff;">
                                        <div id="badgeSticker1" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.9;">Left Sticker #1</div>
                                        <div id="prevQRBox1" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-dark fw-bold" style="left: 15px; top: 20px; width: 44px; height: 44px; font-size: 10px;">
                                            <i class="bi bi-qr-code fs-4"></i>
                                        </div>
                                        <div id="prevCodeTxt1" class="position-absolute fw-bold font-monospace text-dark" style="left: 15px; top: 90px; font-size: 9px;">PRD2611-0001</div>
                                        <div id="prevTitleTxt1" class="position-absolute fw-bold text-dark text-uppercase" style="left: 80px; top: 20px; font-size: 9px;">GRM APPARELS</div>
                                        <div id="prevSkuTxt1" class="position-absolute fw-semibold text-muted text-uppercase" style="left: 80px; top: 40px; font-size: 8.5px;">MEN / SHIRT</div>
                                        <div id="prevMrpTxt1" class="position-absolute text-muted" style="left: 80px; top: 60px; font-size: 8.5px;">MRP: <del>₹1,000</del></div>
                                        <div id="prevDiscTxt1" class="position-absolute fw-bold text-success" style="left: 80px; top: 80px; font-size: 8.5px;">SALE: ₹500</div>
                                    </div>

                                    <!-- Right Sticker #2 (Dual roll second column) -->
                                    <div id="liveStickerCard2" class="bg-white border border-dark rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 150px; font-family: -apple-system, BlinkMacSystemFont, monospace; overflow: hidden; background: #ffffff;">
                                        <div id="badgeSticker2" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.9;">Right Sticker #2</div>
                                        <div id="prevQRBox2" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-dark fw-bold" style="left: 15px; top: 20px; width: 44px; height: 44px; font-size: 10px;">
                                            <i class="bi bi-qr-code fs-4"></i>
                                        </div>
                                        <div id="prevCodeTxt2" class="position-absolute fw-bold font-monospace text-dark" style="left: 15px; top: 90px; font-size: 9px;">PRD2611-0002</div>
                                        <div id="prevTitleTxt2" class="position-absolute fw-bold text-dark text-uppercase" style="left: 80px; top: 20px; font-size: 9px;">GRM APPARELS</div>
                                        <div id="prevSkuTxt2" class="position-absolute fw-semibold text-muted text-uppercase" style="left: 80px; top: 40px; font-size: 8.5px;">MEN / SHIRT</div>
                                        <div id="prevMrpTxt2" class="position-absolute text-muted" style="left: 80px; top: 60px; font-size: 8.5px;">MRP: <del>₹1,000</del></div>
                                        <div id="prevDiscTxt2" class="position-absolute fw-bold text-success" style="left: 80px; top: 80px; font-size: 8.5px;">SALE: ₹500</div>
                                    </div>
                                </div>
                                <div class="text-muted small mt-2 text-center" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle me-1"></i>Simulating physical thermal sticker layout at 203 DPI (8.12 dots/mm).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetQrPrinterDefaults()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Standard Defaults
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="saveQrPrinterSettings()">
                        <i class="bi bi-check2-circle me-1"></i> Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const QR_CONFIG_KEY = 'grm_qr_printer_config';

const QR_DEFAULTS = {
    labelWidthMm: 40,
    labelHeightMm: 25,
    columns: 2,
    gapMm: 2,
    col2ShiftDots: 0,
    qrX: 12,
    qrY: 35,
    qrCellSize: 3,
    codeX: 12,
    codeY: 125,
    textX: 115,
    textFont: "2",
    titleY: 35,
    skuY: 62,
    mrpY: 90,
    discY: 118
};

function readCurrentSettingsForm() {
    return {
        labelWidthMm: parseFloat(document.getElementById('cfgWidth')?.value || QR_DEFAULTS.labelWidthMm),
        labelHeightMm: parseFloat(document.getElementById('cfgHeight')?.value || QR_DEFAULTS.labelHeightMm),
        columns: parseInt(document.getElementById('cfgColumns')?.value || QR_DEFAULTS.columns),
        gapMm: parseFloat(document.getElementById('cfgGap')?.value || QR_DEFAULTS.gapMm),
        col2ShiftDots: parseInt(document.getElementById('cfgCol2ShiftDots')?.value || QR_DEFAULTS.col2ShiftDots),
        qrX: parseInt(document.getElementById('cfgQrX')?.value || QR_DEFAULTS.qrX),
        qrY: parseInt(document.getElementById('cfgQrY')?.value || QR_DEFAULTS.qrY),
        qrCellSize: parseInt(document.getElementById('cfgQrCellSize')?.value || QR_DEFAULTS.qrCellSize),
        codeX: parseInt(document.getElementById('cfgCodeX')?.value || QR_DEFAULTS.codeX),
        codeY: parseInt(document.getElementById('cfgCodeY')?.value || QR_DEFAULTS.codeY),
        textX: parseInt(document.getElementById('cfgTextX')?.value || QR_DEFAULTS.textX),
        textFont: document.getElementById('cfgTextFont')?.value || QR_DEFAULTS.textFont,
        titleY: parseInt(document.getElementById('cfgTitleY')?.value || QR_DEFAULTS.titleY),
        skuY: parseInt(document.getElementById('cfgSkuY')?.value || QR_DEFAULTS.skuY),
        mrpY: parseInt(document.getElementById('cfgMrpY')?.value || QR_DEFAULTS.mrpY),
        discY: parseInt(document.getElementById('cfgDiscY')?.value || QR_DEFAULTS.discY)
    };
}

function loadQrPrinterSettings() {
    try {
        const saved = localStorage.getItem(QR_CONFIG_KEY);
        const config = saved ? JSON.parse(saved) : QR_DEFAULTS;
        
        if (document.getElementById('cfgWidth')) document.getElementById('cfgWidth').value = config.labelWidthMm ?? QR_DEFAULTS.labelWidthMm;
        if (document.getElementById('cfgHeight')) document.getElementById('cfgHeight').value = config.labelHeightMm ?? QR_DEFAULTS.labelHeightMm;
        if (document.getElementById('cfgColumns')) document.getElementById('cfgColumns').value = config.columns ?? QR_DEFAULTS.columns;
        if (document.getElementById('cfgGap')) document.getElementById('cfgGap').value = config.gapMm ?? QR_DEFAULTS.gapMm;
        if (document.getElementById('cfgCol2ShiftDots')) document.getElementById('cfgCol2ShiftDots').value = config.col2ShiftDots ?? QR_DEFAULTS.col2ShiftDots;
        if (document.getElementById('cfgQrX')) document.getElementById('cfgQrX').value = config.qrX ?? QR_DEFAULTS.qrX;
        if (document.getElementById('cfgQrY')) document.getElementById('cfgQrY').value = config.qrY ?? QR_DEFAULTS.qrY;
        if (document.getElementById('cfgQrCellSize')) document.getElementById('cfgQrCellSize').value = config.qrCellSize ?? QR_DEFAULTS.qrCellSize;
        if (document.getElementById('cfgCodeX')) document.getElementById('cfgCodeX').value = config.codeX ?? QR_DEFAULTS.codeX;
        if (document.getElementById('cfgCodeY')) document.getElementById('cfgCodeY').value = config.codeY ?? QR_DEFAULTS.codeY;
        if (document.getElementById('cfgTextX')) document.getElementById('cfgTextX').value = config.textX ?? QR_DEFAULTS.textX;
        if (document.getElementById('cfgTextFont')) document.getElementById('cfgTextFont').value = config.textFont ?? QR_DEFAULTS.textFont;
        if (document.getElementById('cfgTitleY')) document.getElementById('cfgTitleY').value = config.titleY ?? QR_DEFAULTS.titleY;
        if (document.getElementById('cfgSkuY')) document.getElementById('cfgSkuY').value = config.skuY ?? QR_DEFAULTS.skuY;
        if (document.getElementById('cfgMrpY')) document.getElementById('cfgMrpY').value = config.mrpY ?? QR_DEFAULTS.mrpY;
        if (document.getElementById('cfgDiscY')) document.getElementById('cfgDiscY').value = config.discY ?? QR_DEFAULTS.discY;
    } catch (e) {
        console.warn('Could not load saved QR printer settings:', e);
    }
    updateLivePreview();
}

function updateLivePreview() {
    const wMm = parseFloat(document.getElementById('cfgWidth')?.value || 40);
    const hMm = parseFloat(document.getElementById('cfgHeight')?.value || 25);
    const cols = parseInt(document.getElementById('cfgColumns')?.value || 2);
    const gap = parseFloat(document.getElementById('cfgGap')?.value || 2);
    const col2ShiftDots = parseInt(document.getElementById('cfgCol2ShiftDots')?.value || 0);

    const badge = document.getElementById('previewDimensionBadge');
    if (badge) {
        badge.textContent = `${cols === 2 ? 'Dual Roll' : (cols === 1 ? 'Single Roll' : 'Sheet Grid')} ${wMm}mm × ${hMm}mm (Gap ${gap}mm)`;
    }

    const scale = 6.0; // px per mm
    const cardPxWidth = Math.round(wMm * scale);
    const cardPxHeight = Math.round(hMm * scale);

    const card1 = document.getElementById('liveStickerCard1');
    const card2 = document.getElementById('liveStickerCard2');
    if (card1) {
        card1.style.width = `${cardPxWidth}px`;
        card1.style.height = `${cardPxHeight}px`;
    }
    if (card2) {
        if (cols === 1) {
            card2.style.display = 'none';
        } else {
            card2.style.display = 'block';
            card2.style.width = `${cardPxWidth}px`;
            card2.style.height = `${cardPxHeight}px`;
        }
    }

    const badge2 = document.getElementById('badgeSticker2');
    if (badge2) {
        if (col2ShiftDots !== 0) {
            badge2.textContent = `Right Sticker #2 (${col2ShiftDots > 0 ? '+' : ''}${col2ShiftDots} dots)`;
            badge2.className = 'badge bg-primary text-white position-absolute top-0 start-0 m-1 border';
        } else {
            badge2.textContent = 'Right Sticker #2';
            badge2.className = 'badge bg-light text-dark position-absolute top-0 start-0 m-1 border';
        }
    }

    // 8.12 dots/mm at 203 DPI -> dots to preview px scale factor = scale / 8.12
    const dotScale = scale / 8.12;

    const qrX = parseFloat(document.getElementById('cfgQrX')?.value || 12) * dotScale;
    const qrY = parseFloat(document.getElementById('cfgQrY')?.value || 35) * dotScale;
    const qrSize = parseInt(document.getElementById('cfgQrCellSize')?.value || 3);
    const qrPxDim = Math.max(22, Math.round(qrSize * 8.5 * (scale / 4)));

    const codeX = parseFloat(document.getElementById('cfgCodeX')?.value || 12) * dotScale;
    const codeY = parseFloat(document.getElementById('cfgCodeY')?.value || 125) * dotScale;

    const textX = parseFloat(document.getElementById('cfgTextX')?.value || 115) * dotScale;
    const titleY = parseFloat(document.getElementById('cfgTitleY')?.value || 35) * dotScale;
    const skuY = parseFloat(document.getElementById('cfgSkuY')?.value || 62) * dotScale;
    const mrpY = parseFloat(document.getElementById('cfgMrpY')?.value || 90) * dotScale;
    const discY = parseFloat(document.getElementById('cfgDiscY')?.value || 118) * dotScale;

    const col2ShiftPx = col2ShiftDots * dotScale;

    const setPos = (id, x, y, w = null, h = null) => {
        const el = document.getElementById(id);
        if (el) {
            el.style.left = `${Math.round(x)}px`;
            el.style.top = `${Math.round(y)}px`;
            if (w !== null) el.style.width = `${Math.round(w)}px`;
            if (h !== null) el.style.height = `${Math.round(h)}px`;
        }
    };

    setPos('prevQRBox1', qrX, qrY, qrPxDim, qrPxDim);
    setPos('prevCodeTxt1', codeX, codeY);
    setPos('prevTitleTxt1', textX, titleY);
    setPos('prevSkuTxt1', textX, skuY);
    setPos('prevMrpTxt1', textX, mrpY);
    setPos('prevDiscTxt1', textX, discY);

    setPos('prevQRBox2', qrX + col2ShiftPx, qrY, qrPxDim, qrPxDim);
    setPos('prevCodeTxt2', codeX + col2ShiftPx, codeY);
    setPos('prevTitleTxt2', textX + col2ShiftPx, titleY);
    setPos('prevSkuTxt2', textX + col2ShiftPx, skuY);
    setPos('prevMrpTxt2', textX + col2ShiftPx, mrpY);
    setPos('prevDiscTxt2', textX + col2ShiftPx, discY);
}

function saveQrPrinterSettings() {
    const config = readCurrentSettingsForm();
    localStorage.setItem(QR_CONFIG_KEY, JSON.stringify(config));
    
    // If on print sheet, apply dynamic grid styling
    if (typeof applyPrintSheetSettings === 'function') {
        applyPrintSheetSettings(config);
    }

    // Close modal
    const modalEl = document.getElementById('printerSettingsModal');
    if (modalEl && window.bootstrap && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
    }
    
    alert('QR Format & Thermal Printer calibration saved successfully!');
}

function resetQrPrinterDefaults() {
    if (!confirm('Reset all alignment and dimensions back to standard defaults (40mm × 25mm dual roll)?')) return;
    localStorage.removeItem(QR_CONFIG_KEY);
    loadQrPrinterSettings();
}

document.addEventListener('DOMContentLoaded', function() {
    loadQrPrinterSettings();
});
</script>
