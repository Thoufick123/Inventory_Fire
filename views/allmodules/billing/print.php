<?php
$prodUnit = $prodUnit ?? (class_exists('ProductionUnit') ? ProductionUnit::getActiveFacility($bill['production_unit_id'] ?? null) : []);
$sellerCompany = $sellerCompany ?? [];
$concernBrand = function_exists('getConcernBranding')
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit)
    : [];

$storeName = trim((string)($concernBrand['name'] ?? ($sellerCompany['company_name'] ?? ($prodUnit['name'] ?? 'Retail Store'))));
$storeAddress = trim((string)($concernBrand['address'] ?? ($sellerCompany['company_address'] ?? ($prodUnit['address'] ?? ''))));
$storePhone = trim((string)($concernBrand['phone'] ?? ($sellerCompany['phone'] ?? ($prodUnit['phone'] ?? ''))));
$storeEmail = trim((string)($concernBrand['email'] ?? ($sellerCompany['email'] ?? ($prodUnit['email'] ?? ''))));
$storeGstin = trim((string)($concernBrand['gst_number'] ?? ($sellerCompany['gst_number'] ?? ($prodUnit['gst_number'] ?? ''))));
$storeLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : '');

// Staff name
$staffName = trim((string)($bill['staff_name'] ?? ''));
if ($staffName === '' && !empty($bill['created_by'])) {
    $staffUser = Database::fetchOne("SELECT full_name, username FROM users WHERE id = ?", [(int)$bill['created_by']]);
    if ($staffUser) {
        $staffName = $staffUser['full_name'] ?: $staffUser['username'];
    }
} elseif ($staffName === '' && Session::user()) {
    $staffName = Session::user()['full_name'] ?: (Session::user()['username'] ?: 'System Administrator');
}

// Buyer details
$isCustomer = (($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER');
$buyerName = $isCustomer ? ($bill['customer_name'] ?: 'Walk-in Customer') : ($bill['company_name'] ?: 'Company');
$buyerPhone = $isCustomer ? ($bill['customer_phone'] ?: '—') : ($bill['company_phone'] ?: '—');
$buyerEmail = $isCustomer ? '' : ($bill['company_email'] ?? '');
$buyerAddress = $isCustomer
    ? trim(($bill['customer_address'] ?? '') . ' ' . ($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))
    : ($bill['company_address'] ?? '');
if ($buyerAddress === '') {
    $buyerAddress = '—';
}
$buyerGstin = $isCustomer ? '' : ($bill['company_gstin'] ?? '');

$billDateFormatted = !empty($bill['bill_date']) ? date('d M Y', strtotime($bill['bill_date'])) : date('d M Y');
$purchasedAt = !empty($bill['created_at']) ? date('d M Y, h:i A', strtotime($bill['created_at'])) : $billDateFormatted;
$payMode = strtoupper((string)($bill['payment_method'] ?: 'CASH'));
$payStatus = strtoupper((string)($bill['payment_status'] ?: 'PAID'));

$totalQty = 0;
foreach ($billItems as $it) {
    $totalQty += (int)($it['quantity'] ?? 0);
}
$grossAmt = (float)($bill['gross_amount'] ?? 0);
$discount = (float)($bill['discount_amount'] ?? 0);
$couponDiscount = (float)($bill['coupon_discount_amount'] ?? 0);
$subtotal = (float)($bill['subtotal'] ?? 0);
$taxAmount = (float)($bill['tax_amount'] ?? 0);
$cgst = (float)($bill['cgst_amount'] ?? 0);
$sgst = (float)($bill['sgst_amount'] ?? 0);
$igst = (float)($bill['igst_amount'] ?? 0);
$totalAmount = (float)($bill['total_amount'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Invoice <?= htmlspecialchars($bill['bill_number']) ?> — A4 Format</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 16px;
            background: #f1f5f9;
            font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.45;
        }
        .no-print {
            max-width: 210mm;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }
        .a4-sheet {
            width: 210mm;
            min-height: 280mm;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
            padding: 14mm 14mm 12mm 14mm;
            display: flex;
            flex-direction: column;
        }
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 14px;
            border-bottom: 2px solid #0f172a;
        }
        .brand-block {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .brand-logo {
            max-width: 84px;
            max-height: 64px;
            object-fit: contain;
            border-radius: 6px;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .muted { color: #475569; }
        .small { font-size: 11.5px; }
        .inv-badge-box {
            text-align: right;
        }
        .inv-title {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: 0.04em;
        }
        .inv-copy-tag {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            font-size: 10.5px;
            font-weight: 700;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 14px;
            margin: 14px 0;
        }
        .meta-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .meta-card h4 {
            margin: 0 0 6px 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.items-table th {
            background: #0f172a;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 8px;
            text-align: left;
        }
        table.items-table th.num, table.items-table td.num { text-align: right; }
        table.items-table th.ctr, table.items-table td.ctr { text-align: center; }
        table.items-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 12px;
        }
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            margin-top: 16px;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            background: #f8fafc;
        }
        .sum-line {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 12.5px;
        }
        .sum-total {
            display: flex;
            justify-content: space-between;
            padding: 8px 0 2px 0;
            margin-top: 6px;
            border-top: 2px solid #0f172a;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .terms-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .inv-footer {
            margin-top: auto;
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #cbd5e1;
        }
        .sign-line {
            width: 190px;
            border-top: 1px solid #475569;
            padding-top: 4px;
            text-align: center;
            font-size: 11.5px;
            font-weight: 600;
        }
        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .a4-sheet {
                width: 100%;
                min-height: auto;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print A4 Invoice</button>
        <button type="button" class="btn" onclick="window.close()">Close</button>
    </div>

    <div class="a4-sheet">
        <div class="inv-header">
            <div class="brand-block">
                <?php if ($storeLogo !== ''): ?>
                    <img src="<?= htmlspecialchars($storeLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?>" class="brand-logo">
                <?php endif; ?>
                <div>
                    <div class="brand-name"><?= htmlspecialchars($storeName) ?></div>
                    <?php if ($storeAddress !== ''): ?>
                        <div class="muted small"><?= nl2br(htmlspecialchars($storeAddress)) ?></div>
                    <?php endif; ?>
                    <div class="muted small">
                        <?php if ($storePhone !== ''): ?>Ph: <?= htmlspecialchars($storePhone) ?><?php endif; ?>
                        <?php if ($storePhone !== '' && $storeEmail !== ''): ?> &bull; <?php endif; ?>
                        <?php if ($storeEmail !== ''): ?>Email: <?= htmlspecialchars($storeEmail) ?><?php endif; ?>
                    </div>
                    <?php if ($storeGstin !== ''): ?>
                        <div class="small" style="font-weight:700; margin-top:2px;">GSTIN: <?= htmlspecialchars($storeGstin) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="inv-badge-box">
                <div class="inv-title">TAX INVOICE</div>
                <div class="inv-copy-tag">A4 Software Copy</div>
                <div class="small muted" style="margin-top:8px;">Invoice #: <strong style="color:#0f172a;"><?= htmlspecialchars($bill['bill_number']) ?></strong></div>
                <div class="small muted">Date: <strong style="color:#0f172a;"><?= htmlspecialchars($billDateFormatted) ?></strong></div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <h4><?= $isCustomer ? 'Billed To (Customer)' : 'Billed To (B2B Company)' ?></h4>
                <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($buyerName) ?></div>
                <div class="small muted"><?= nl2br(htmlspecialchars($buyerAddress)) ?></div>
                <div class="small muted" style="margin-top:3px;">
                    Phone: <?= htmlspecialchars($buyerPhone) ?>
                    <?php if ($buyerEmail !== ''): ?> &bull; <?= htmlspecialchars($buyerEmail) ?><?php endif; ?>
                </div>
                <?php if ($buyerGstin !== ''): ?>
                    <div class="small" style="font-weight:700; margin-top:2px;">GSTIN: <?= htmlspecialchars($buyerGstin) ?></div>
                <?php endif; ?>
            </div>
            <div class="meta-card">
                <h4>Invoice &amp; Payment Details</h4>
                <div class="meta-row small"><span class="muted">Bill ID:</span><strong>#<?= (int)$bill['id'] ?></strong></div>
                <div class="meta-row small"><span class="muted">Generated At:</span><span><?= htmlspecialchars($purchasedAt) ?></span></div>
                <div class="meta-row small"><span class="muted">Payment Mode:</span><strong><?= htmlspecialchars($payMode) ?> (<?= htmlspecialchars($payStatus) ?>)</strong></div>
                <div class="meta-row small"><span class="muted">Billed By:</span><span><?= htmlspecialchars($staffName) ?></span></div>
                <?php if (!empty($bill['upi_reference'])): ?>
                    <div class="meta-row small"><span class="muted">UPI Ref:</span><span><?= htmlspecialchars($bill['upi_reference']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:32px;">#</th>
                    <th>Product Description &amp; QR / Variant</th>
                    <th style="width:72px;">HSN</th>
                    <th class="ctr" style="width:48px;">Qty</th>
                    <th class="num" style="width:82px;">MRP</th>
                    <th class="num" style="width:76px;">Disc</th>
                    <th class="num" style="width:86px;">Taxable</th>
                    <th class="num" style="width:76px;">GST</th>
                    <th class="num" style="width:92px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billItems as $idx => $item):
                    $qty = (int)($item['quantity'] ?? 1);
                    $rowMrp = (float)($item['mrp'] ?? 0) * $qty;
                    $rowDisc = (float)($item['discount_amount'] ?? 0);
                    $rowTaxable = (float)($item['taxable_amount'] ?? 0);
                    $rowTax = (float)($item['tax_amount'] ?? 0);
                    $rowTotal = (float)($item['total_amount'] ?? 0);
                    $metaParts = array_filter([
                        !empty($item['size_name']) ? 'Size: ' . $item['size_name'] : '',
                        !empty($item['color_name']) ? 'Color: ' . $item['color_name'] : '',
                        !empty($item['sku']) ? 'SKU: ' . $item['sku'] : '',
                    ]);
                    $tags = !empty($item['tags']) ? implode(', ', $item['tags']) : '';
                ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <?php if (!empty($metaParts)): ?>
                            <div class="small muted"><?= htmlspecialchars(implode(' · ', $metaParts)) ?></div>
                        <?php endif; ?>
                        <?php if ($tags !== ''): ?>
                            <div class="small muted" style="font-family: ui-monospace, monospace;">QR: <?= htmlspecialchars($tags) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['hsn_code'] ?? '—') ?></td>
                    <td class="ctr"><?= $qty ?></td>
                    <td class="num"><?= formatCurrency($rowMrp) ?></td>
                    <td class="num" style="color:#dc2626;"><?= $rowDisc > 0 ? '-' . formatCurrency($rowDisc) : '—' ?></td>
                    <td class="num"><?= formatCurrency($rowTaxable) ?></td>
                    <td class="num"><?= formatCurrency($rowTax) ?></td>
                    <td class="num" style="font-weight:700;"><?= formatCurrency($rowTotal) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="bottom-grid">
            <div class="terms-box">
                <div style="font-weight:700; font-size:12px; margin-bottom:6px;">Terms &amp; Conditions</div>
                <ol class="small muted" style="margin:0; padding-left:16px;">
                    <li>Goods once sold are exchangeable within 7 days with original tag &amp; invoice.</li>
                    <li>All disputes are subject to local jurisdiction only.</li>
                    <?php if (!empty($bill['notes'])): ?>
                        <li>Note: <?= htmlspecialchars($bill['notes']) ?></li>
                    <?php endif; ?>
                </ol>
                <?php if ((float)($bill['cash_given'] ?? 0) > 0): ?>
                    <div class="small" style="margin-top:10px; padding-top:8px; border-top:1px dashed #cbd5e1;">
                        Cash Tendered: <strong><?= formatCurrency((float)$bill['cash_given']) ?></strong> &bull;
                        Change Returned: <strong><?= formatCurrency((float)($bill['change_returned'] ?? 0)) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="summary-box">
                <div class="sum-line"><span class="muted">Total Quantity</span><span><?= $totalQty ?> pcs</span></div>
                <div class="sum-line"><span class="muted">Gross / MRP</span><span><?= formatCurrency($grossAmt) ?></span></div>
                <?php if ($discount > 0): ?>
                    <div class="sum-line"><span class="muted">Item Discount</span><span style="color:#dc2626;">-<?= formatCurrency($discount) ?></span></div>
                <?php endif; ?>
                <div class="sum-line"><span class="muted">Taxable Subtotal</span><span><?= formatCurrency($subtotal) ?></span></div>
                <?php if ($cgst > 0): ?>
                    <div class="sum-line"><span class="muted">CGST</span><span><?= formatCurrency($cgst) ?></span></div>
                <?php endif; ?>
                <?php if ($sgst > 0): ?>
                    <div class="sum-line"><span class="muted">SGST</span><span><?= formatCurrency($sgst) ?></span></div>
                <?php endif; ?>
                <?php if ($igst > 0): ?>
                    <div class="sum-line"><span class="muted">IGST</span><span><?= formatCurrency($igst) ?></span></div>
                <?php endif; ?>
                <div class="sum-line"><span class="muted">Total GST (Incl.)</span><span><?= formatCurrency($taxAmount) ?></span></div>
                <?php if ($couponDiscount > 0): ?>
                    <div class="sum-line" style="color:#2563eb;">
                        <span>Coupon (<?= htmlspecialchars($bill['applied_coupon_code'] ?? '') ?>)</span>
                        <span>-<?= formatCurrency($couponDiscount) ?></span>
                    </div>
                <?php endif; ?>
                <div class="sum-total">
                    <span>NET AMOUNT</span>
                    <span><?= formatCurrency($totalAmount) ?></span>
                </div>
                <?php if ($payStatus === 'COD'): ?>
                    <div class="sum-line small" style="margin-top:4px;">
                        <span style="color:#16a34a;">Paid: <?= formatCurrency((float)($bill['paid_amount'] ?? 0)) ?></span>
                        <span style="color:#dc2626;">Balance COD: <?= formatCurrency((float)($bill['remaining_amount'] ?? 0)) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="inv-footer">
            <div class="small muted">Thank you for your business! This is a computer-generated A4 Tax Invoice.</div>
            <div class="sign-line">For <?= htmlspecialchars($storeName) ?><br><span class="muted" style="font-weight:400;">Authorized Signatory</span></div>
        </div>
    </div>
</body>
</html>
