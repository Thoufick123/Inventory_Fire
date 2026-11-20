<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($batch['brand_name']) ?> — QR Labels (<?= count($batch['items']) ?> pcs)</title>
    <link href="<?= asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .print-sheet-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .labels-grid {
            display: grid;
            grid-template-columns: repeat(2, 40mm);
            grid-auto-rows: 25mm;
            gap: 2mm;
            width: max-content;
        }

        .garment-label-card {
            position: relative;
            box-sizing: border-box;
            width: 40mm;
            height: 25mm;
            background: #fff;
            border: 0.3mm solid #000;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
            color: #000;
        }

        .qr-canvas-box {
            position: absolute;
            overflow: hidden;
        }

        .qr-canvas-box canvas, .qr-canvas-box img {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .label-code,
        .label-title,
        .label-sku,
        .label-mrp,
        .label-price {
            position: absolute;
            overflow: hidden;
            line-height: 1.05;
            white-space: nowrap;
        }

        .label-code {
            font-family: monospace;
            font-weight: 700;
        }

        .label-title {
            font-weight: 800;
            text-transform: uppercase;
        }

        .label-sku {
            font-weight: 600;
            text-transform: uppercase;
        }

        .label-mrp del {
            text-decoration-thickness: 0.2mm;
        }

        .no-print,
        .no-print .btn,
        .no-print .badge {
            border-radius: 0 !important;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
            .garment-label-card {
                box-shadow: none;
                border-radius: 0;
            }
            .print-sheet-container { max-width: none; width: max-content; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="print-sheet-container">
        <!-- Control Header (hidden during print) -->
        <div class="no-print d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch Print Thermal Labels</h5>
                <small class="text-muted">
                    Batch: <strong><?= e($batch['batch_code']) ?></strong> · Brand: <strong class="text-uppercase"><?= e($batch['brand_name']) ?></strong> · Total: <strong><?= count($batch['items']) ?> labels</strong>
                </small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-dark fw-bold" data-bs-toggle="modal" data-bs-target="#printerSettingsModal">
                    <i class="bi bi-sliders me-1 text-primary"></i> QR Format Config
                </button>
                <button onclick="window.print()" class="btn btn-primary fw-bold">
                    <i class="bi bi-printer-fill me-1"></i> Print All <?= count($batch['items']) ?> Labels
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary">
                    Close
                </button>
            </div>
        </div>

        <!-- Labels Grid -->
        <?php
            $isFabricBatch = strtoupper((string)($batch['qr_source'] ?? '')) === QrGenerationService::SOURCE_FABRIC;
            $sourcePurchaseDate = trim((string)($batch['source_purchase_date'] ?? ''));
            if ($sourcePurchaseDate === '') {
                $sourcePurchaseDate = trim((string)($batch['created_at'] ?? ''));
            }
            $sourcePurchaseTimestamp = $sourcePurchaseDate !== '' ? strtotime($sourcePurchaseDate) : false;
            $fabricMonthYear = $sourcePurchaseTimestamp !== false ? date('m/Y', $sourcePurchaseTimestamp) : '';
        ?>
        <div class="labels-grid">
            <?php foreach ($batch['items'] as $item): ?>
            <?php
                $itemQrCode = (string)($item['qr_code'] ?? '');
                $displayQrLabel = $isFabricBatch ? ($fabricMonthYear ?: $itemQrCode) : $itemQrCode;
                $sizeName = trim((string)($item['size_name'] ?: ($batch['size_name'] ?? '')));
                $colorName = trim((string)($item['color_name'] ?: ($batch['color_name'] ?? '')));
                $variantSku = trim((string)($item['variant_code'] ?? ($item['sku'] ?? ($item['variant_sku'] ?? ''))));
                $subtitleParts = array_filter([
                    trim((string)($batch['category_name'] ?: 'APPAREL')),
                    trim((string)($batch['subcategory_name'] ?? '')),
                    $variantSku !== '' && $variantSku !== $displayQrLabel ? $variantSku : '',
                    $colorName !== '' ? 'COL ' . $colorName : '',
                    !empty($batch['design_name']) ? 'DES ' . trim((string)$batch['design_name']) : '',
                    $sizeName !== '' ? 'SIZE ' . $sizeName : '',
                ]);
                $labelSubtitle = implode(' / ', $subtitleParts);
            ?>
            <div class="garment-label-card">
                <div class="qr-canvas-box print-qr-item" data-code="<?= e($itemQrCode) ?>"></div>
                <div class="label-code"><?= e($displayQrLabel) ?></div>
                <div class="label-title"><?= e($batch['brand_name']) ?></div>
                <div class="label-sku"><?= e($labelSubtitle) ?></div>
                <?php if ((float)($item['mrp'] ?? 0) > 0): ?>
                <div class="label-mrp"><strong>MRP:</strong> <del>₹<?= number_format((float)$item['mrp'], 2) ?></del></div>
                <?php endif; ?>
                <?php if ((float)($item['selling_price'] ?? 0) > 0): ?>
                <div class="label-price"><strong>SALE:</strong> ₹<?= number_format((float)$item['selling_price'], 2) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const PRINT_QR_CONFIG_KEY = 'grm_qr_printer_config';
        const PRINT_QR_DEFAULTS = {
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
            textFont: '2',
            titleY: 35,
            skuY: 62,
            mrpY: 90,
            discY: 118
        };
        const PRINT_DOTS_PER_MM = 8.12;

        function readQrPrinterConfig() {
            try {
                const saved = localStorage.getItem(PRINT_QR_CONFIG_KEY);
                const storedConfig = saved ? JSON.parse(saved) : {};
                const config = { ...PRINT_QR_DEFAULTS, ...storedConfig };
                const finiteNumber = (key, min, max) => {
                    const value = Number(config[key]);
                    return Number.isFinite(value)
                        ? Math.min(max, Math.max(min, value))
                        : PRINT_QR_DEFAULTS[key];
                };
                const columns = Number(config.columns);

                return {
                    labelWidthMm: finiteNumber('labelWidthMm', 20, 150),
                    labelHeightMm: finiteNumber('labelHeightMm', 15, 150),
                    columns: [1, 2, 4].includes(columns) ? columns : PRINT_QR_DEFAULTS.columns,
                    gapMm: finiteNumber('gapMm', 0, 20),
                    col2ShiftDots: finiteNumber('col2ShiftDots', -500, 500),
                    qrX: finiteNumber('qrX', -500, 500),
                    qrY: finiteNumber('qrY', -500, 1000),
                    qrCellSize: finiteNumber('qrCellSize', 2, 4),
                    codeX: finiteNumber('codeX', -500, 500),
                    codeY: finiteNumber('codeY', -500, 1000),
                    textX: finiteNumber('textX', -500, 1000),
                    textFont: ['1', '2', '3'].includes(String(config.textFont))
                        ? String(config.textFont)
                        : PRINT_QR_DEFAULTS.textFont,
                    titleY: finiteNumber('titleY', -500, 1000),
                    skuY: finiteNumber('skuY', -500, 1000),
                    mrpY: finiteNumber('mrpY', -500, 1000),
                    discY: finiteNumber('discY', -500, 1000)
                };
            } catch (error) {
                console.error('Could not read saved QR printer settings; using standard defaults.', error);
                return { ...PRINT_QR_DEFAULTS };
            }
        }

        function applyPrintSheetSettings(config) {
            if (!config) {
                config = readQrPrinterConfig();
            }
            const grid = document.querySelector('.labels-grid');
            if (!grid) {
                return;
            }

            const effectiveConfig = { ...PRINT_QR_DEFAULTS, ...config };
            const cardWidth = Number(effectiveConfig.labelWidthMm);
            const cardHeight = Number(effectiveConfig.labelHeightMm);
            const columns = Number(effectiveConfig.columns);
            const gap = Number(effectiveConfig.gapMm);
            const shiftMm = Number(effectiveConfig.col2ShiftDots) / PRINT_DOTS_PER_MM;
            const qrSizeMm = Number(effectiveConfig.qrCellSize) * 2.125;
            const fontSizeMm = { 1: 1.2, 2: 1.5, 3: 1.9 }[effectiveConfig.textFont] || 1.5;

            grid.style.gridTemplateColumns = `repeat(${columns}, ${cardWidth}mm)`;
            grid.style.gridAutoRows = `${cardHeight}mm`;
            grid.style.columnGap = `${gap}mm`;
            grid.style.rowGap = `${gap}mm`;

            grid.querySelectorAll('.garment-label-card').forEach((card, index) => {
                const columnIndex = index % columns;
                const shift = columnIndex === 1 ? shiftMm : 0;
                card.style.width = `${cardWidth}mm`;
                card.style.height = `${cardHeight}mm`;

                const position = (selector, xDots, yDots) => {
                    const element = card.querySelector(selector);
                    if (!element) return;
                    element.style.left = `${(Number(xDots) / PRINT_DOTS_PER_MM) + shift}mm`;
                    element.style.top = `${Number(yDots) / PRINT_DOTS_PER_MM}mm`;
                    element.style.fontSize = `${fontSizeMm}mm`;
                    element.style.maxWidth = `${Math.max(1, cardWidth - (Number(xDots) / PRINT_DOTS_PER_MM) - shift)}mm`;
                };

                const qr = card.querySelector('.qr-canvas-box');
                if (qr) {
                    qr.style.left = `${(Number(effectiveConfig.qrX) / PRINT_DOTS_PER_MM) + shift}mm`;
                    qr.style.top = `${Number(effectiveConfig.qrY) / PRINT_DOTS_PER_MM}mm`;
                    qr.style.width = `${qrSizeMm}mm`;
                    qr.style.height = `${qrSizeMm}mm`;
                }

                position('.label-code', effectiveConfig.codeX, effectiveConfig.codeY);
                position('.label-title', effectiveConfig.textX, effectiveConfig.titleY);
                position('.label-sku', effectiveConfig.textX, effectiveConfig.skuY);
                position('.label-mrp', effectiveConfig.textX, effectiveConfig.mrpY);
                position('.label-price', effectiveConfig.textX, effectiveConfig.discY);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyPrintSheetSettings(readQrPrinterConfig());

            document.querySelectorAll('.print-qr-item').forEach(function(el) {
                new QRCode(el, {
                    text: el.dataset.code,
                    width: 256,
                    height: 256,
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });

        window.addEventListener('beforeprint', function() {
            applyPrintSheetSettings(readQrPrinterConfig());
        });
    </script>

    <?php require __DIR__ . '/_qr_format_modal.php'; ?>
</body>
</html>
