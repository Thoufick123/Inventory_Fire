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
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1100px) {
            .labels-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            }
        }

        /* 50x30mm Standard Garment Thermal Sticker Card */
        .garment-label-card {
            background: #fff;
            border: 2px solid #000;
            border-radius: 0;
            padding: 8px 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .label-header {
            border-bottom: 1.5px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
            text-align: center;
        }

        /* GIVEN BRAND NAME PROMINENTLY PRINTED AT TOP */
        .brand-name-title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #000;
            line-height: 1.2;
        }

        .hierarchy-sub {
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            margin-top: 1px;
        }

        .qr-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qr-canvas-box {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
        }

        .qr-canvas-box canvas, .qr-canvas-box img {
            width: 72px !important;
            height: 72px !important;
        }

        .label-meta {
            font-size: 9.5px;
            line-height: 1.35;
        }

        .label-meta strong {
            font-weight: 800;
        }

        .label-mrp del {
            text-decoration-thickness: 1.5px;
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
            }
            .no-print {
                display: none !important;
            }
            .garment-label-card {
                box-shadow: none;
                border: 1.5px solid #000;
                border-radius: 0;
            }
            .labels-grid {
                gap: 8px;
            }
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
            ?>
            <div class="garment-label-card">
                <!-- Top Brand Header: Given Brand Name Print -->
                <div class="label-header">
                    <div class="brand-name-title"><?= e($batch['brand_name']) ?></div>
                    <div class="hierarchy-sub">
                        <?= e($batch['category_name'] ?: 'APPAREL') ?>
                        <?php if (!empty($batch['subcategory_name'])): ?>
                        › <?= e($batch['subcategory_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Middle: QR and Attributes -->
                <div class="qr-row">
                    <div class="qr-canvas-box print-qr-item" data-code="<?= e($item['qr_code']) ?>"></div>
                    <div class="label-meta flex-grow-1">
                        <div class="font-monospace fw-bold text-dark" style="font-size: 11px; word-break: break-all;">
                            <?= e($displayQrLabel) ?>
                        </div>
                        <?php if ($variantSku !== '' && $variantSku !== $displayQrLabel): ?>
                        <div class="font-monospace text-muted" style="font-size: 8.5px;"><?= e($variantSku) ?></div>
                        <?php endif; ?>
                        <?php if ($colorName !== ''): ?>
                        <div><strong>Col:</strong> <?= e($colorName) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($batch['design_name'])): ?>
                        <div><strong>Des:</strong> <?= e($batch['design_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($sizeName !== ''): ?>
                        <div class="mt-1"><strong>Size:</strong> <span class="badge bg-dark text-white px-2 py-0" style="font-size: 10px; font-weight: 800;"><?= e($sizeName) ?></span></div>
                        <?php endif; ?>
                        <?php if ((float)($item['mrp'] ?? 0) > 0): ?>
                        <div class="label-mrp"><strong>MRP:</strong> <del>₹<?= number_format((float)$item['mrp'], 2) ?></del></div>
                        <?php endif; ?>
                        <?php if ((float)($item['selling_price'] ?? 0) > 0): ?>
                        <div><strong class="text-dark">SALE:</strong> ₹<?= number_format((float)$item['selling_price'], 2) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function applyPrintSheetSettings(config) {
            if (!config) return;
            const grid = document.querySelector('.labels-grid');
            if (grid && config.columns) {
                grid.style.gridTemplateColumns = `repeat(${config.columns}, 1fr)`;
                grid.style.gap = `${config.gapMm || 2}mm`;
            }
            const cards = document.querySelectorAll('.garment-label-card');
            cards.forEach(card => {
                if (config.labelWidthMm) card.style.minWidth = `${config.labelWidthMm}mm`;
                if (config.labelHeightMm) card.style.minHeight = `${config.labelHeightMm}mm`;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedConfig = localStorage.getItem('grm_qr_printer_config');
            if (savedConfig) {
                try {
                    applyPrintSheetSettings(JSON.parse(savedConfig));
                } catch(e) {}
            }

            document.querySelectorAll('.print-qr-item').forEach(function(el) {
                new QRCode(el, {
                    text: el.dataset.code,
                    width: 72,
                    height: 72,
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    </script>

    <?php require __DIR__ . '/_qr_format_modal.php'; ?>
</body>
</html>
