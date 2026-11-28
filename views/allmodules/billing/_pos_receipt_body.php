<?php
$concernBrand = function_exists('getConcernBranding')
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit ?? null)
    : [];
$displayLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : '');
$storeName = trim((string)($concernBrand['name'] ?? ($sellerCompany['company_name'] ?? ($prodUnit['name'] ?? 'Retail Store'))));
$storeAddress = trim((string)($concernBrand['address'] ?? ($sellerCompany['company_address'] ?? ($prodUnit['address'] ?? ''))));
$storeGstin = trim((string)($concernBrand['gst_number'] ?? ($sellerCompany['gst_number'] ?? ($prodUnit['gst_number'] ?? ''))));
$isCust = ($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER';
$receiptPartyName = $isCust ? ($bill['customer_name'] ?? 'Walk-in Customer') : ($bill['company_name'] ?? 'Company');
$receiptPartyPhone = $isCust ? ($bill['customer_phone'] ?? '') : ($bill['company_phone'] ?? '');
$receiptPayLabel = strtoupper((string)($bill['payment_method'] ?? 'CASH')) . ' (' . strtoupper((string)($bill['payment_status'] ?? 'PAID')) . ')';
?>
<div id="posThermalReceiptContent" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #1e293b;">
    <div class="text-center mb-3">
        <?php if (!empty($displayLogo)): ?>
            <img src="<?= htmlspecialchars($displayLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?>" class="mb-2" style="max-height: 52px; max-width: 140px; object-fit: contain; border-radius: 6px;">
        <?php endif; ?>
        <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($storeName) ?></div>
        <?php if ($storeAddress !== ''): ?>
            <div class="text-muted small"><?= htmlspecialchars($storeAddress) ?></div>
        <?php endif; ?>
        <?php if ($storeGstin !== ''): ?>
            <div class="text-muted small">GSTIN: <?= htmlspecialchars($storeGstin) ?></div>
        <?php endif; ?>
        <div class="fw-semibold small mt-1">TAX INVOICE / RETAIL RECEIPT</div>
    </div>

    <div class="py-2 mb-2" style="border-top: 1px dashed #cbd5e1; border-bottom: 1px dashed #cbd5e1;">
        <div class="d-flex justify-content-between gap-2">
            <div>
                <div class="fw-semibold"><?= $isCust ? 'Customer:' : 'Company:' ?></div>
                <div><?= htmlspecialchars($receiptPartyName) ?></div>
                <?php if ($receiptPartyPhone !== ''): ?>
                    <div><?= htmlspecialchars($receiptPartyPhone) ?></div>
                <?php endif; ?>
                <div class="mt-1">Payment: <?= htmlspecialchars($receiptPayLabel) ?></div>
            </div>
            <div class="text-end">
                <div class="fw-semibold">Inv No:</div>
                <div><?= htmlspecialchars($bill['bill_number']) ?></div>
                <div class="mt-1">Date: <?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?></div>
            </div>
        </div>
    </div>

    <table class="table table-sm table-borderless mb-2" style="font-size: 12px;">
        <thead style="border-bottom: 1px dashed #cbd5e1;">
            <tr class="text-muted">
                <th class="ps-0 fw-semibold">Item</th>
                <th class="text-center fw-semibold">Qty</th>
                <th class="text-end fw-semibold">MRP Price</th>
                <th class="pe-0 text-end fw-semibold">Bill Amount</th>
            </tr>
        </thead>
        <tbody style="border-bottom: 1px dashed #cbd5e1;">
            <?php foreach ($billItems as $item):
                $rowMrp = (float)$item['mrp'] * (int)$item['quantity'];
                $rowAmt = (float)$item['taxable_amount'];
                $codes = !empty($item['tags']) ? implode(', ', $item['tags']) : ($item['sku'] ?? '');
            ?>
            <tr>
                <td class="ps-0 py-1">
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($item['product_name']) ?></div>
                    <?php if (!empty($item['size_name'])): ?>
                        <div class="text-muted" style="font-size: 11px;">Size: <?= htmlspecialchars($item['size_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($codes !== ''): ?>
                        <div class="text-muted" style="font-size: 11px;">Scanned code: <?= htmlspecialchars($codes) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center py-1 align-middle"><?= (int)$item['quantity'] ?></td>
                <td class="text-end py-1 align-middle"><?= formatCurrency($rowMrp) ?></td>
                <td class="pe-0 text-end py-1 align-middle"><?= formatCurrency($rowAmt) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="py-1" style="border-bottom: 1px dashed #cbd5e1;">
        <div class="d-flex justify-content-between py-1">
            <span>Taxable Subtotal:</span>
            <span><?= formatCurrency((float)$bill['subtotal']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
            <span>GST Tax Amount:</span>
            <span><?= formatCurrency((float)$bill['tax_amount']) ?></span>
        </div>
        <?php if ((float)($bill['coupon_discount_amount'] ?? 0) > 0): ?>
        <div class="d-flex justify-content-between py-1 text-primary">
            <span>Coupon (<?= htmlspecialchars($bill['applied_coupon_code'] ?? '') ?>):</span>
            <span>-<?= formatCurrency((float)$bill['coupon_discount_amount']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between py-2 fw-bold fs-6 text-dark" style="border-bottom: 1px dashed #cbd5e1;">
        <span>NET AMOUNT:</span>
        <span><?= formatCurrency((float)$bill['total_amount']) ?></span>
    </div>

    <div class="text-center text-muted small pt-2 pb-1">
        <div>Thank you for shopping with us!</div>
        <div>Goods once sold are exchangeable within 7 days.</div>
    </div>
</div>
