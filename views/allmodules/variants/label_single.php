<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Variant QR Label — <?= e($variant['variant_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .label-sheet {
            max-width: 400px;
            margin: 0 auto;
        }

        .thermal-label {
            width: 320px;
            background: #fff;
            border: 2px solid #000;
            border-radius: 6px;
            padding: 10px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            page-break-inside: avoid;
            margin: 0 auto 20px auto;
        }

        .thermal-header {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-align: center;
        }

        .company-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .qr-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qr-box {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }

        .qr-box canvas, .qr-box img {
            width: 80px !important;
            height: 80px !important;
        }

        .meta-list {
            font-size: 11px;
            line-height: 1.35;
        }

        .meta-list strong {
            font-weight: 700;
        }

        .price-badge {
            font-size: 13px;
            font-weight: 800;
            color: #000;
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
            .thermal-label {
                box-shadow: none;
                border: 1.5px solid #000;
                margin: 0;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="label-sheet">
        <div class="no-print d-flex justify-content-between align-items-center mb-3">
            <button onclick="window.print()" class="btn btn-primary fw-bold">
                <i class="bi bi-printer me-1"></i> Print Label
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">
                Close
            </button>
        </div>

        <?php
            $concernName = strtoupper((string)(function_exists('currentConcern') ? (currentConcern()['name'] ?? 'GRM APPARELS') : 'GRM APPARELS'));
            $brandCode = strtoupper((string)(function_exists('currentConcern') ? (currentConcern()['code'] ?? 'GRM') : 'GRM'));
            $vCode = $variant['variant_code'] ?: $variant['qr_code'];
            $mrpVal = (float)($variant['mrp'] ?? 0);
            $spVal = (float)($variant['selling_price'] ?? 0);
            $attrLine = trim(($variant['size_name'] ?? '') . ' / ' . ($variant['color_name'] ?? ''), ' /');
        ?>
        <div class="thermal-label">
            <div class="text-center fw-bold text-secondary text-uppercase pb-1 mb-2 border-bottom" style="font-size: 10px; letter-spacing: 0.5px;">
                <?= e($concernName) ?>
            </div>
            <div class="qr-section" style="align-items: center; gap: 10px;">
                <div class="d-flex flex-column align-items-center flex-shrink-0">
                    <div id="qrCanvas" class="qr-box"></div>
                    <div class="font-monospace fw-bold text-dark mt-1" style="font-size: 9.5px; letter-spacing: 0.4px;"><?= e($vCode) ?></div>
                </div>
                <div class="meta-list flex-grow-1" style="min-width: 0;">
                    <div class="fw-bolder text-uppercase text-truncate" style="font-size: 11px; color: #881337;"><?= e($brandCode) ?></div>
                    <div class="fw-semibold text-dark text-uppercase text-truncate" style="font-size: 9.5px;"><?= e($variant['product_name']) ?></div>
                    <?php if ($attrLine !== ''): ?>
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 10px;"><?= e($attrLine) ?></div>
                    <?php endif; ?>
                    <?php if ($mrpVal > 0): ?>
                        <div class="text-muted text-decoration-line-through" style="font-size: 9.5px;">MRP: ₹<?= number_format($mrpVal, 0) ?></div>
                    <?php endif; ?>
                    <?php if ($spVal > 0): ?>
                        <div class="fw-bold" style="font-size: 11px; color: #15803d;">Disc: ₹<?= number_format($spVal, 0) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new QRCode(document.getElementById('qrCanvas'), {
                text: <?= json_encode((string)($variant['qr_code'] ?: $variant['variant_code'])) ?>,
                width: 80,
                height: 80,
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    </script>
</body>
</html>
