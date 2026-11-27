<?php
$prodUnit = $prodUnit ?? (class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility($bill['production_unit_id'] ?? null) : []);
$sellerCompany = $sellerCompany ?? [];
$concernBrand = function_exists('getConcernBranding')
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit)
    : [];
$storeName = $concernBrand['name'] ?? ($prodUnit['name'] ?? ($sellerCompany['company_name'] ?? 'GRM APPARELS'));
$couponCode = (string)($coupon['coupon_code'] ?? '');
$discountType = strtoupper((string)($coupon['discount_type'] ?? 'FIXED'));
$discountVal = (float)($coupon['discount_value'] ?? 0);
$discountLabel = $discountType === 'PERCENT'
    ? rtrim(rtrim(number_format($discountVal, 2), '0'), '.') . '% OFF'
    : '₹ ' . number_format($discountVal, 2) . ' OFF';
$minRedeem = (float)($coupon['min_redemption_amount'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coupon <?= htmlspecialchars($couponCode) ?> — Print Card</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #e9ecef;
            font-family: 'Courier New', Courier, monospace;
            padding: 20px 10px;
            color: #000;
        }
        .no-print {
            max-width: 360px;
            margin: 0 auto 14px auto;
            display: flex;
            gap: 10px;
        }
        .btn-print {
            flex: 1;
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-close {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .coupon-card {
            max-width: 340px;
            width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 2px dashed #000;
            border-radius: 8px;
            padding: 14px 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .store-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .coupon-subtitle {
            font-size: 11px;
            margin-top: 2px;
            margin-bottom: 8px;
        }
        .offer-banner {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 8px 4px;
            margin: 8px 0;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .barcode-wrap {
            margin: 8px 0 4px 0;
        }
        .barcode-wrap svg {
            max-width: 100%;
            height: 60px;
        }
        .qr-wrap {
            display: inline-block;
            margin: 6px auto 4px auto;
        }
        .code-text {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .meta {
            text-align: left;
            font-size: 11px;
            line-height: 1.45;
            border-top: 1px dashed #000;
            padding-top: 8px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        .terms {
            font-size: 10px;
            margin-top: 8px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            text-align: left;
            line-height: 1.35;
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .coupon-card { box-shadow: none; max-width: 100%; }
            @page { size: auto; margin: 2mm 3mm; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Print Coupon Card</button>
    <button type="button" class="btn-close" onclick="window.close()">Close</button>
</div>

<main class="coupon-card">
    <div class="store-name"><?= htmlspecialchars($storeName) ?></div>
    <div class="coupon-subtitle">PRIVILEGE DISCOUNT COUPON CARD</div>

    <div class="offer-banner"><?= htmlspecialchars($discountLabel) ?></div>

    <div class="barcode-wrap">
        <svg id="couponBarcode"></svg>
    </div>
    <div class="code-text"><?= htmlspecialchars($couponCode) ?></div>
    <div class="qr-wrap" id="couponQr"></div>

    <div class="meta">
        <div class="meta-row"><strong>Customer:</strong><span><?= htmlspecialchars($coupon['customer_name'] ?: ($bill['customer_name'] ?? 'Valued Customer')) ?></span></div>
        <div class="meta-row"><strong>Phone:</strong><span><?= htmlspecialchars($coupon['customer_phone'] ?: ($bill['customer_phone'] ?? '—')) ?></span></div>
        <div class="meta-row"><strong>Issued Bill:</strong><span><?= htmlspecialchars($bill['bill_number'] ?? '') ?></span></div>
        <?php if ($minRedeem > 0): ?>
        <div class="meta-row"><strong>Min Next Bill:</strong><span>₹ <?= number_format($minRedeem, 2) ?></span></div>
        <?php endif; ?>
        <div class="meta-row"><strong>Valid Until:</strong><span><?= !empty($coupon['expires_at']) ? htmlspecialchars(date('d M Y', strtotime($coupon['expires_at']))) : 'No Expiry' ?></span></div>
        <div class="meta-row"><strong>Status:</strong><span><?= htmlspecialchars($coupon['status'] ?? 'ACTIVE') ?></span></div>
    </div>

    <div class="terms">
        1. Present &amp; scan this barcode at POS billing on your next purchase.<br>
        2. Valid for single use only before expiry date.
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const code = <?= json_encode($couponCode) ?>;
    if (window.JsBarcode && code) {
        try {
            JsBarcode('#couponBarcode', code, {
                format: 'CODE128',
                width: 1.6,
                height: 52,
                displayValue: false,
                margin: 4
            });
        } catch (e) {}
    }
    if (window.QRCode && code) {
        try {
            new QRCode(document.getElementById('couponQr'), {
                text: code,
                width: 68,
                height: 68
            });
        } catch (e) {}
    }
    if (window.location.search.includes('autoprint=1')) {
        window.print();
    }
});
</script>
</body>
</html>
