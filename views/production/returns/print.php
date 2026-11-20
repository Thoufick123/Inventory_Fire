<?php
$prodUnit = $prodUnit ?? (class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility($return['production_unit_id'] ?? null) : []);
$sellerCompany = $sellerCompany ?? [];
$seller = static fn(string $key): string => htmlspecialchars((string)($sellerCompany[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$concernBrand = function_exists('getConcernBranding') 
    ? getConcernBranding($return['concern_id'] ?? null, $prodUnit) 
    : [];
$returnLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : (!empty($sellerCompany['company_logo']) ? uploadUrl($sellerCompany['company_logo']) : ''));
$returnFirmName = !empty($concernBrand['name']) ? $concernBrand['name'] : ($prodUnit['name'] ?? ($seller('company_name') ?: 'Production Facility'));
$returnFirmAddress = !empty($concernBrand['address']) ? $concernBrand['address'] : (!empty($prodUnit['address']) ? $prodUnit['address'] : $seller('address'));
$returnFirmPhone = !empty($concernBrand['phone']) ? $concernBrand['phone'] : (!empty($prodUnit['phone']) ? $prodUnit['phone'] : $seller('phone'));
$returnFirmGstin = !empty($concernBrand['gst_number']) ? $concernBrand['gst_number'] : (!empty($prodUnit['gst_number']) ? $prodUnit['gst_number'] : $seller('gstin'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Credit Note <?= htmlspecialchars($return['return_number']) ?> — <?= htmlspecialchars($returnFirmName) ?></title>
    <style>
        body{font:14px Arial,sans-serif;color:#222;margin:24px}.invoice{max-width:900px;margin:auto}.header{display:flex;justify-content:space-between;border-bottom:2px solid #222;padding-bottom:16px}.section{margin:18px 0}.label{font-size:11px;text-transform:uppercase;color:#666;font-weight:bold;margin-bottom:6px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f3f4f6}.right{text-align:right}.center{text-align:center}.summary{width:360px;margin-left:auto}.summary td{border:0;padding:5px 0}.total{font-weight:bold;font-size:17px;border-top:1px solid #222!important;color:#dc3545}.tags{font-size:10px;color:#666}.actions{max-width:900px;margin:0 auto 15px;text-align:right}.actions button{padding:8px 14px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer}@media print{body{margin:0}.actions{display:none}.invoice{max-width:none}}
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print Credit Note</button></div>
<main class="invoice">
    <div class="header">
        <div>
            <?php if (!empty($returnLogo)): ?>
                <img src="<?= htmlspecialchars($returnLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:55px;max-width:180px;object-fit:contain;margin-bottom:6px;display:block;">
            <?php endif; ?>
            <h2 style="margin:0 0 6px"><?= htmlspecialchars($returnFirmName) ?></h2>
            <?php if (!empty($returnFirmAddress)): ?><div><?= nl2br(htmlspecialchars($returnFirmAddress)) ?></div><?php endif; ?>
            <div>
                <?php if (!empty($returnFirmPhone)): ?><span>Phone: <?= htmlspecialchars($returnFirmPhone) ?></span><?php endif; ?>
                <?php if (!empty($prodUnit['code'])): ?><span> · Unit Code: <?= htmlspecialchars($prodUnit['code']) ?></span><?php endif; ?>
            </div>
            <?php if (!empty($returnFirmGstin)): ?><div>GSTIN: <?= htmlspecialchars($returnFirmGstin) ?></div><?php endif; ?>
        </div>
        <div class="right">
            <h2 style="margin:0 0 6px;color:#dc3545">GST CREDIT NOTE</h2>
            <div><strong>Credit Note #:</strong> <?= htmlspecialchars($return['return_number']) ?></div>
            <div><strong>Original Bill #:</strong> <?= htmlspecialchars($return['bill_number'] ?? '-') ?></div>
            <div><strong>Date:</strong> <?= htmlspecialchars(date('d M Y', strtotime($return['created_at']))) ?></div>
        </div>
    </div>
    <div class="section">
        <div class="label">Customer / Party</div>
        <strong><?= htmlspecialchars($return['customer_name'] ?? '') ?></strong>
        <div><?= htmlspecialchars($return['customer_phone'] ?? '') ?></div>
    </div>
    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>HSN</th>
                    <th class="center">Tag Code</th>
                    <th class="center">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Taxable</th>
                    <th class="right">GST Reversal</th>
                    <th class="right">Total Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <div class="tags"><?= htmlspecialchars($item['sku'] ?? '') ?> · <?= htmlspecialchars($item['reason']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($item['hsn_code'] ?? '6109') ?></td>
                        <td class="center font-monospace"><?= htmlspecialchars($item['tag_code'] ?: '-') ?></td>
                        <td class="center"><?= (int)$item['quantity'] ?></td>
                        <td class="right">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td class="right">₹<?= number_format((float)$item['taxable_amount'], 2) ?></td>
                        <td class="right">₹<?= number_format((float)$item['tax_amount'], 2) ?></td>
                        <td class="right">₹<?= number_format((float)$item['total_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="section">
        <table class="summary">
            <tr><td>Taxable Value Reversal</td><td class="right">₹<?= number_format((float)($return['subtotal'] ?? $return['subtotal_taxable'] ?? 0), 2) ?></td></tr>
            <tr><td>GST Reversal Amount</td><td class="right">₹<?= number_format((float)($return['gst_reversal_amount'] ?? $return['total_gst_reversal'] ?? 0), 2) ?></td></tr>
            <tr class="total"><td>Net Credit Note Amount</td><td class="right">₹<?= number_format((float)($return['net_amount'] ?? $return['total_credit_amount'] ?? 0), 2) ?></td></tr>
        </table>
    </div>
    <div class="section">
        <strong>Return Type:</strong> <?= htmlspecialchars((string)($return['return_type'] ?? 'SALES_RETURN')) ?> | 
        <strong>Stock Disposition:</strong> <?= htmlspecialchars((string)($return['disposition'] ?? 'RESTOCK')) ?> | 
        <strong>Settlement Mode:</strong> <?= htmlspecialchars((string)($return['refund_mode'] ?? 'CASH')) ?>
    </div>
    <?php if (!empty($return['notes'])): ?>
        <div class="section"><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($return['notes'])) ?></div>
    <?php endif; ?>
    <div class="section" style="text-align:center;border-top:1px solid #ddd;padding-top:12px;font-size:12px;color:#666">
        This is a computer-generated GST Credit Note issued under Rule 54 of the CGST Rules, 2017.
    </div>
</main>
</body>
</html>
