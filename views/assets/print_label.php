<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Sticker - <?= e($asset['asset_code']) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/bootstrap.min.css') ?>">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fff; color: #000; padding: 20px; font-size: 12px; }
        .label-sticker {
            width: 75mm;
            height: 50mm;
            border: 2px solid #000;
            padding: 8px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            background: #fff;
        }
        .org-header { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid #000; padding-bottom: 3px; margin-bottom: 4px; display: flex; justify-content: space-between; }
        .asset-title { font-size: 12px; font-weight: 800; line-height: 1.2; text-transform: uppercase; }
        .asset-code { font-size: 14px; font-weight: 900; font-family: monospace; letter-spacing: 0.5px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            .label-sticker { border: 1.5px solid #000; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print Sticker</button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Close</button>
    </div>

    <div class="label-sticker">
        <div class="org-header">
            <span>GRM APPARELS</span>
            <span>PLANT ASSET</span>
        </div>

        <div class="d-flex align-items-center justify-content-between my-1">
            <div style="width: 58%;">
                <div class="asset-title text-truncate"><?= e($asset['name']) ?></div>
                <div class="small fw-semibold text-truncate"><?= e($asset['brand']) ?> <?= e($asset['model_number']) ?></div>
                <div class="small text-muted font-monospace">SN: <?= e($asset['serial_number'] ?: 'N/A') ?></div>
                <div class="asset-code mt-1"><?= e($asset['asset_code']) ?></div>
            </div>
            <div style="width: 40%; text-align: center;">
                <div id="qrcode" class="d-flex justify-content-center"></div>
                <div style="font-size: 9px; font-family: monospace; margin-top: 2px;" class="text-truncate"><?= e($asset['barcode_qr']) ?></div>
            </div>
        </div>

        <div style="border-top: 1px solid #000; padding-top: 2px; font-size: 9px; display: flex; justify-content: space-between;">
            <span>Loc: <?= e($asset['location_type']) ?> (<?= e($asset['floor_area'] ?: 'Floor #1') ?>)</span>
            <span>Cond: <?= e($asset['condition_state']) ?></span>
        </div>
    </div>

    <script src="<?= asset('assets/js/qrcode.min.js') ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= addslashes($asset['barcode_qr']) ?>",
            width: 75,
            height: 75,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    });
    </script>
</body>
</html>
