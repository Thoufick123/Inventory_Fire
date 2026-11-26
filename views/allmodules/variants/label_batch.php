<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch QR Labels (<?= $quantity ?> pcs) — <?= e($variant['variant_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .batch-container {
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

        .thermal-label-card {
            background: #fff;
            border: 1.5px solid #000;
            border-radius: 6px;
            padding: 8px 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .thermal-header {
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 4px;
            text-align: center;
        }

        .company-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .qr-section {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-box {
            width: 70px;
            height: 70px;
            flex-shrink: 0;
        }

        .qr-box canvas, .qr-box img {
            width: 70px !important;
            height: 70px !important;
        }

        .meta-list {
            font-size: 10px;
            line-height: 1.3;
        }

        .meta-list strong {
            font-weight: 700;
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
            .thermal-label-card {
                box-shadow: none;
                border: 1.5px solid #000;
            }
            .labels-grid {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="batch-container">
        <!-- Control Header -->
        <div class="no-print d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch Print QR Labels</h5>
                <small class="text-muted">
                    Printing <strong><?= $quantity ?> identical QR labels</strong> for Variant <strong><?= e($variant['variant_code']) ?></strong>.
                </small>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary fw-bold">
                    <i class="bi bi-printer-fill me-1"></i> Print All <?= $quantity ?> Labels
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary">
                    Close
                </button>
            </div>
        </div>

        <!-- Labels Grid -->
        <?php
            $concernName = strtoupper((string)(function_exists('currentConcern') ? (currentConcern()['name'] ?? 'GRM APPARELS') : 'GRM APPARELS'));
            $brandCode = strtoupper((string)(function_exists('currentConcern') ? (currentConcern()['code'] ?? 'GRM') : 'GRM'));
            $vCode = $variant['variant_code'] ?: $variant['qr_code'];
            $mrpVal = (float)($variant['mrp'] ?? 0);
            $spVal = (float)($variant['selling_price'] ?? 0);
            $attrLine = trim(($variant['size_name'] ?? '') . ' / ' . ($variant['color_name'] ?? ''), ' /');
        ?>
        <div class="labels-grid">
            <?php for ($i = 1; $i <= $quantity; $i++): ?>
            <div class="thermal-label-card">
                <div class="text-center fw-bold text-secondary text-uppercase pb-1 mb-2 border-bottom" style="font-size: 9px; letter-spacing: 0.5px;">
                    <?= e($concernName) ?>
                </div>
                <div class="qr-section" style="align-items: center; gap: 8px;">
                    <div class="d-flex flex-column align-items-center flex-shrink-0">
                        <div class="qr-box batch-qr-item" data-qr="<?= e($variant['qr_code'] ?: $variant['variant_code']) ?>"></div>
                        <div class="font-monospace fw-bold text-dark mt-1" style="font-size: 8.5px; letter-spacing: 0.4px;"><?= e($vCode) ?></div>
                    </div>
                    <div class="meta-list flex-grow-1" style="min-width: 0;">
                        <div class="fw-bolder text-uppercase text-truncate" style="font-size: 10px; color: #881337;"><?= e($brandCode) ?></div>
                        <div class="fw-semibold text-dark text-uppercase text-truncate" style="font-size: 8.5px;"><?= e($variant['product_name']) ?></div>
                        <?php if ($attrLine !== ''): ?>
                            <div class="fw-semibold text-dark text-truncate" style="font-size: 9px;"><?= e($attrLine) ?></div>
                        <?php endif; ?>
                        <?php if ($mrpVal > 0): ?>
                            <div class="text-muted text-decoration-line-through" style="font-size: 8.5px;">MRP: ₹<?= number_format($mrpVal, 0) ?></div>
                        <?php endif; ?>
                        <?php if ($spVal > 0): ?>
                            <div class="fw-bold" style="font-size: 10px; color: #15803d;">Disc: ₹<?= number_format($spVal, 0) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qrText = <?= json_encode((string)($variant['qr_code'] ?: $variant['variant_code'])) ?>;
            document.querySelectorAll('.batch-qr-item').forEach(function(box) {
                new QRCode(box, {
                    text: qrText,
                    width: 70,
                    height: 70,
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    </script>
</body>
</html>
