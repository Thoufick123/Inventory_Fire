<?php
$prodUnit = $prodUnit ?? (class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility($purchase['production_unit_id'] ?? null) : []);
$currency = $company['currency_symbol'] ?? '₹';
$concernBrand = function_exists('getConcernBranding') 
    ? getConcernBranding($purchase['concern_id'] ?? null, $prodUnit) 
    : [];
$companyName = !empty($concernBrand['name']) ? $concernBrand['name'] : (!empty($prodUnit['name']) ? $prodUnit['name'] : ($company['company_name'] ?? ''));
$purchaseLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : (!empty($company['company_logo']) ? uploadUrl($company['company_logo']) : ''));
$taxLabels = ['SGST' => 'SGST', 'CGST' => 'CGST', 'IGST' => 'IGST'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Report <?= e($purchase['purchase_number']) ?> — <?= htmlspecialchars($companyName) ?></title>
    <style>
        *{box-sizing:border-box}body{font:14px Arial,sans-serif;color:#1f2937;margin:28px}.toolbar{display:flex;justify-content:flex-end;margin-bottom:16px}.toolbar button{padding:9px 16px;border:0;border-radius:4px;background:#1d4ed8;color:#fff;font-size:14px;cursor:pointer}.report{max-width:1000px;margin:auto}.header{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #1f2937;padding-bottom:18px;margin-bottom:22px}.company h1{font-size:23px;margin:0 0 8px}.company p,.meta p,.party p{margin:4px 0;white-space:pre-line}.title{text-align:right}.title h2{margin:0 0 8px;font-size:21px}.parties{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0}.party{border:1px solid #d1d5db;border-radius:5px;padding:14px}.party h3{margin:0 0 10px;font-size:14px;text-transform:uppercase;color:#4b5563}.meta{margin:10px 0 20px}.meta strong{display:inline-block;min-width:120px}.items{width:100%;border-collapse:collapse;margin:16px 0}.items th,.items td{border:1px solid #d1d5db;padding:9px;text-align:left;vertical-align:top}.items th{background:#f3f4f6}.num{text-align:right!important;white-space:nowrap}.totals{width:360px;max-width:100%;margin:16px 0 0 auto;border-collapse:collapse}.totals td{padding:7px 10px;border-bottom:1px solid #e5e7eb}.grand-total{font-weight:bold;font-size:17px;border-top:2px solid #1f2937}.notes{margin-top:24px;border-top:1px solid #d1d5db;padding-top:12px}.footer{margin-top:36px;text-align:center;color:#6b7280;font-size:12px}@media print{body{margin:12mm}.toolbar{display:none}.report{max-width:none}thead{display:table-header-group}tr{break-inside:avoid}}
    </style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()"><i>Print / Save PDF</i></button></div>
<main class="report">
    <header class="header">
        <div class="company">
            <?php if (!empty($purchaseLogo)): ?>
                <img src="<?= htmlspecialchars($purchaseLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:55px;max-width:180px;object-fit:contain;margin-bottom:8px;display:block;">
            <?php endif; ?>
            <h1><?= htmlspecialchars($companyName ?: 'Production Unit') ?></h1>
            <?php 
            $dispAddr = !empty($concernBrand['address']) ? $concernBrand['address'] : (!empty($prodUnit['address']) ? $prodUnit['address'] : ($company['company_address'] ?? ''));
            $dispPhone = !empty($concernBrand['phone']) ? $concernBrand['phone'] : (!empty($prodUnit['phone']) ? $prodUnit['phone'] : ($company['company_phone'] ?? ''));
            $dispGst = !empty($concernBrand['gst_number']) ? $concernBrand['gst_number'] : (!empty($prodUnit['gst_number']) ? $prodUnit['gst_number'] : ($company['company_gstin'] ?? ''));
            ?>
            <?php if (!empty($dispAddr)): ?><p><?= nl2br(htmlspecialchars($dispAddr)) ?></p><?php endif; ?>
            <?php if (!empty($dispPhone)): ?><p>Phone: <?= htmlspecialchars($dispPhone) ?></p><?php endif; ?>
            <?php if (!empty($prodUnit['code'])): ?><p>Unit Code: <?= htmlspecialchars($prodUnit['code']) ?></p><?php endif; ?>
            <?php if (!empty($dispGst)): ?><p>GSTIN: <?= htmlspecialchars($dispGst) ?></p><?php endif; ?>
        </div>
        <div class="title">
            <h2>Purchase Report</h2>
            <p><strong><?= e($purchase['purchase_number']) ?></strong></p>
        </div>
    </header>

    <div class="parties">
        <section class="party">
            <h3>Production Facility</h3>
            <p><strong><?= htmlspecialchars($companyName ?: '—') ?></strong></p>
            <?php if (!empty($prodUnit['address'])): ?><p><?= nl2br(htmlspecialchars($prodUnit['address'])) ?></p>
            <?php elseif (!empty($company['company_address'])): ?><p><?= e($company['company_address']) ?></p><?php endif; ?>
            <?php if (!empty($prodUnit['phone'])): ?><p>Phone: <?= htmlspecialchars($prodUnit['phone']) ?></p>
            <?php elseif (!empty($company['company_phone'])): ?><p>Phone: <?= e($company['company_phone']) ?></p><?php endif; ?>
            <?php if (!empty($prodUnit['gst_number'])): ?><p>GSTIN: <?= htmlspecialchars($prodUnit['gst_number']) ?></p>
            <?php elseif (!empty($company['company_gstin'])): ?><p>GSTIN: <?= e($company['company_gstin']) ?></p><?php endif; ?>
        </section>
        <section class="party">
            <h3>Purchased From</h3>
            <p><strong><?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?></strong></p>
            <?php if (!empty($purchase['seller_company'])): ?><p>Contact: <?= e($purchase['seller_name']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_address'])): ?><p><?= e($purchase['seller_address']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_city']) || !empty($purchase['seller_state'])): ?><p><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ')) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_phone'])): ?><p>Phone: <?= e($purchase['seller_phone']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_email'])): ?><p>Email: <?= e($purchase['seller_email']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_gst_number'])): ?><p>GSTIN: <?= e($purchase['seller_gst_number']) ?></p><?php endif; ?>
        </section>
    </div>

    <div class="meta">
        <p><strong>Purchase Date:</strong> <?= e(formatDate($purchase['purchase_date'])) ?></p>
        <p><strong>Invoice / Bill No.:</strong> <?= e($purchase['invoice_number'] ?: '—') ?></p>
    </div>

    <?php
    $hasSgst = false;
    $hasCgst = false;
    $hasIgst = false;
    $sgstRates = [];
    $cgstRates = [];
    $igstRates = [];

    foreach ($items as $it) {
        $taxType = $it['gst_type'] ?? 'EXEMPTED';
        $sgAmt = (float)($it['sgst_amount'] ?? 0);
        $cgAmt = (float)($it['cgst_amount'] ?? 0);
        $igAmt = (float)($it['igst_amount'] ?? 0);
        $isPct = (($it['tax_input_type'] ?? 'PERCENT') === 'PERCENT');

        if (in_array($taxType, ['SGST', 'BOTH'], true) || $sgAmt > 0) {
            $hasSgst = true;
            if ($isPct && (float)($it['sgst_value'] ?? 0) > 0) {
                $sgstRates[(string)((float)$it['sgst_value'] + 0)] = true;
            }
        }
        if (in_array($taxType, ['CGST', 'BOTH'], true) || $cgAmt > 0) {
            $hasCgst = true;
            if ($isPct && (float)($it['cgst_value'] ?? 0) > 0) {
                $cgstRates[(string)((float)$it['cgst_value'] + 0)] = true;
            }
        }
        if ($taxType === 'IGST' || $igAmt > 0) {
            $hasIgst = true;
            if ($isPct && (float)($it['igst_value'] ?? 0) > 0) {
                $igstRates[(string)((float)$it['igst_value'] + 0)] = true;
            }
        }
    }

    if (!empty($taxSummary)) {
        if (isset($taxSummary['SGST'])) $hasSgst = true;
        if (isset($taxSummary['CGST'])) $hasCgst = true;
        if (isset($taxSummary['IGST'])) $hasIgst = true;
    }

    $sgstTitle = count($sgstRates) === 1 ? 'SGST (' . array_key_first($sgstRates) . '%)' : 'SGST';
    $cgstTitle = count($cgstRates) === 1 ? 'CGST (' . array_key_first($cgstRates) . '%)' : 'CGST';
    $igstTitle = count($igstRates) === 1 ? 'IGST (' . array_key_first($igstRates) . '%)' : 'IGST';
    $hasAnyTax = $hasSgst || $hasCgst || $hasIgst;
    ?>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Purchased Item</th>
                <th>HSN</th>
                <th>Quantity</th>
                <th class="num">Unit Price</th>
                <th class="num">Subtotal</th>
                <?php if ($hasSgst): ?>
                    <th class="num"><?= e($sgstTitle) ?></th>
                <?php endif; ?>
                <?php if ($hasCgst): ?>
                    <th class="num"><?= e($cgstTitle) ?></th>
                <?php endif; ?>
                <?php if ($hasIgst): ?>
                    <th class="num"><?= e($igstTitle) ?></th>
                <?php endif; ?>
                <?php if (!$hasAnyTax): ?>
                    <th class="num">Tax</th>
                <?php endif; ?>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $index => $item): ?>
            <?php
                $itemTax = (float)($item['sgst_amount'] ?? 0) + (float)($item['cgst_amount'] ?? 0) + (float)($item['igst_amount'] ?? 0);
                $description = $item['item_type'] === 'FABRIC_ROLL'
                    ? trim(($item['item_name'] ?? '') . ' — ' . ($item['roll_number'] ?? '') . ' — ' . ($item['color'] ?? '') . ' / ' . ($item['design_name'] ?? ''), ' —/')
                    : ($item['item_name'] ?? '');

                $isPercent = (($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT');
                $sgstVal = (float)($item['sgst_value'] ?? 0);
                $cgstVal = (float)($item['cgst_value'] ?? 0);
                $igstVal = (float)($item['igst_value'] ?? 0);

                $taxTreatment = $item['tax_treatment'] ?? 'INCLUDED';
                $gross = (float)$item['unit_price'] * (float)$item['quantity'];
                $itemBase = isset($item['base_amount']) ? (float)$item['base_amount'] : ($taxTreatment === 'INCLUDED' ? max(0, (float)$item['total_price'] - $itemTax) : $gross);
            ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <?= e($description) ?><br>
                    <small style="color:#6b7280;"><?= $item['item_type'] === 'FABRIC_ROLL' ? 'Fabric Roll' : ($item['item_type'] === 'RAW_MATERIAL' ? 'Raw Material' : ($item['item_type'] === 'THIRD_PARTY' ? 'Third-Party Product' : 'Own Production Product')) ?></small>
                </td>
                <td><small style="font-family:monospace;"><?= e($item['hsn_code'] ?: '—') ?></small></td>
                <td><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?><?= !empty($item['weight_kg']) ? ' · ' . number_format((float)$item['weight_kg'], 2) . ' kg' : '' ?></td>
                <td class="num">
                    <?= e($currency) ?><?= number_format((float)$item['unit_price'], 2) ?>
                    <small style="color:#6b7280; display:block; font-size:10px;"><?= $taxTreatment === 'EXCLUDED' ? 'GST Excl.' : 'GST Incl.' ?></small>
                </td>
                <td class="num"><?= e($currency) ?><?= number_format($itemBase, 2) ?></td>
                <?php if ($hasSgst): ?>
                    <td class="num">
                        <?= e($currency) ?><?= number_format((float)($item['sgst_amount'] ?? 0), 2) ?>
                        <?php if (count($sgstRates) > 1 && $isPercent && $sgstVal > 0): ?>
                            <small style="color:#6b7280; font-family:monospace; display:block; font-size:10px;">(<?= ($sgstVal + 0) ?>%)</small>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if ($hasCgst): ?>
                    <td class="num">
                        <?= e($currency) ?><?= number_format((float)($item['cgst_amount'] ?? 0), 2) ?>
                        <?php if (count($cgstRates) > 1 && $isPercent && $cgstVal > 0): ?>
                            <small style="color:#6b7280; font-family:monospace; display:block; font-size:10px;">(<?= ($cgstVal + 0) ?>%)</small>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if ($hasIgst): ?>
                    <td class="num">
                        <?= e($currency) ?><?= number_format((float)($item['igst_amount'] ?? 0), 2) ?>
                        <?php if (count($igstRates) > 1 && $isPercent && $igstVal > 0): ?>
                            <small style="color:#6b7280; font-family:monospace; display:block; font-size:10px;">(<?= ($igstVal + 0) ?>%)</small>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if (!$hasAnyTax): ?>
                    <td class="num" style="color:#6b7280;">—</td>
                <?php endif; ?>
                <td class="num"><?= e($currency) ?><?= number_format((float)$item['total_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num"><?= e($currency) ?><?= number_format((float)$subtotal, 2) ?></td></tr>
        <?php if (!empty($taxSummary)): ?>
            <?php foreach ($taxSummary as $taxRow): ?>
                <tr>
                    <td>
                        <?= e($taxRow['name']) ?>
                        <?php if (!empty($taxRow['rate_label'])): ?>
                            <small style="color:#6b7280; font-weight:normal;">(<?= e($taxRow['rate_label']) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= e($currency) ?><?= number_format((float)$taxRow['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($taxes as $type => $amount): if ((float)$amount > 0): ?>
                <tr><td><?= e($type) ?></td><td class="num"><?= e($currency) ?><?= number_format((float)$amount, 2) ?></td></tr>
            <?php endif; endforeach; ?>
        <?php endif; ?>
        <tr><td>Total Taxes</td><td class="num"><?= e($currency) ?><?= number_format((float)($totalTax ?? 0), 2) ?></td></tr>
        <tr class="grand-total"><td>Overall Total</td><td class="num"><?= e($currency) ?><?= number_format((float)($overallTotal ?? $purchase['total_amount'] ?? 0), 2) ?></td></tr>
    </table>
    <?php if (!empty($purchase['notes'])): ?><div class="notes"><strong>Notes:</strong> <?= e($purchase['notes']) ?></div><?php endif; ?>
    <div class="footer"><?= e($company['invoice_footer'] ?? '') ?></div>
</main>
</body>
</html>
