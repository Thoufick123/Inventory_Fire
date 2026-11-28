<?php
$qrCodeVal = (string)($asset['barcode_qr'] ?: $asset['asset_code']);
$concernObj = function_exists('currentConcern') ? currentConcern() : null;
$companyTitle = 'GRM APPARELS';
if (!empty($concernObj['name']) && strtoupper(trim((string)($concernObj['code'] ?? ''))) !== 'ADMIN') {
    $companyTitle = strtoupper(trim((string)$concernObj['name']));
}
$brandTitle = strtoupper(trim((string)($asset['brand'] ?? '')));
$assetTitle = strtoupper(trim((string)($asset['name'] ?? '')));
$locParts = array_filter([
    trim((string)($asset['location_type'] ?? '')),
    trim((string)($asset['floor_area'] ?? '')),
]);
$locLine = $locParts ? implode(' / ', $locParts) : 'Standard';
$costVal = (float)($asset['purchase_cost'] ?? 0);
$statusLabel = trim((string)($asset['status'] ?? 'IN_USE'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Sticker - <?= e($asset['asset_code']) ?></title>
    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            padding: 28px 16px;
        }
        .asset-label-card {
            max-width: 390px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; margin: 0; }
            .asset-label-card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="card border border-light-subtle shadow-sm asset-label-card">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center gap-2">
            <span class="small fw-bold text-secondary">Asset #1 of 1</span>
            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i><?= e($statusLabel) ?>
            </span>
        </div>
        <div class="card-body p-3 d-flex flex-column justify-content-between">
            <div>
                <div class="text-center fw-bold text-secondary text-uppercase text-truncate mb-2 pb-1 border-bottom" style="font-size: 11.5px; letter-spacing: 0.5px;">
                    <?= e($companyTitle) ?>
                </div>
                <div class="d-flex justify-content-center align-items-center gap-3 py-1">
                    <div class="d-flex flex-column align-items-center flex-shrink-0">
                        <div id="qrcode" class="p-1 bg-white border rounded" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center;"></div>
                        <div class="font-monospace fw-bold text-dark mt-1" style="font-size: 11.5px; letter-spacing: 0.4px;"><?= e($qrCodeVal) ?></div>
                    </div>
                    <div class="min-w-0 text-start">
                        <?php if ($brandTitle !== ''): ?>
                            <div class="fw-bolder text-truncate text-uppercase" style="font-size: 13.5px; letter-spacing: 0.3px; color: #881337;">
                                <?= e($brandTitle) ?>
                            </div>
                        <?php endif; ?>
                        <div class="fw-semibold text-dark text-truncate mb-1" style="font-size: 12px;">
                            <?= e($assetTitle) ?>
                        </div>
                        <div class="fw-semibold text-dark mb-1 text-truncate" style="font-size: 12px;">
                            <?= e($locLine) ?>
                        </div>
                        <?php if ($costVal > 0): ?>
                            <div class="fw-bold" style="font-size: 13.5px; color: #15803d;">
                                Cost: <?= formatCurrency($costVal) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 pt-2 mt-2 border-top no-print">
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="window.close()">
                    Close
                </button>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="copyAssetQr('<?= e(addslashes($qrCodeVal)) ?>', this)" title="Copy Asset QR Code">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="window.print()" title="Print Label">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>
    if (typeof QRCode === 'undefined') {
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
    }
    </script>
    <script>
    function copyAssetQr(code, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function() {
                if (btn) {
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                    setTimeout(function() { btn.innerHTML = orig; }, 1200);
                }
            }).catch(function() {});
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var qrBox = document.getElementById('qrcode');
        if (qrBox && typeof QRCode !== 'undefined') {
            qrBox.innerHTML = '';
            new QRCode(qrBox, {
                text: "<?= addslashes($qrCodeVal) ?>",
                width: 76,
                height: 76,
                colorDark: "#111827",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            qrBox.querySelectorAll('img').forEach(function(im) {
                im.style.setProperty('display', 'none', 'important');
            });
        }
    });
    </script>
</body>
</html>
