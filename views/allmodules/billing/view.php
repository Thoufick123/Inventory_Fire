<?php
$concernBrand = function_exists('getConcernBranding') 
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit ?? null) 
    : [];
$displayLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : '');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <?php if (!empty($displayLogo)): ?>
            <img src="<?= htmlspecialchars($displayLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($concernBrand['name'] ?? ($prodUnit['name'] ?? 'Production unit'), ENT_QUOTES, 'UTF-8') ?> logo" class="mb-2" style="max-width:180px;max-height:64px;object-fit:contain;">
        <?php endif; ?>
        <h4 class="mb-1 fw-bold d-flex align-items-center gap-2">
            <span><?= htmlspecialchars($bill['bill_number']) ?></span>
            <?php if (!empty($bill['is_direct_bill'])): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6"><i class="bi bi-box-arrow-up-right me-1"></i>Direct Bill</span>
            <?php endif; ?>
        </h4>
        <div class="text-muted small">Production bill · <?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?> · <?= htmlspecialchars($concernBrand['name'] ?? '') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <a class="btn btn-outline-primary" href="<?= url('production/billing/print/' . (int)$bill['id']) ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print Invoice</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-4">
            <?php if (($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER'): ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Customer</div>
                <h5><?= htmlspecialchars($bill['customer_name'] ?? '') ?></h5>
                <div><?= htmlspecialchars($bill['customer_phone'] ?? '') ?></div>
                <div><?= nl2br(htmlspecialchars($bill['customer_address'] ?? '')) ?></div>
                <div><?= htmlspecialchars(trim(($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))) ?></div>
                <?php if (!empty($bill['customer_gstin'])): ?><div class="small text-muted">GSTIN: <?= htmlspecialchars($bill['customer_gstin']) ?></div><?php endif; ?>
            </div>
            <?php else: ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Buyer company</div>
                <h5><?= htmlspecialchars($bill['company_name'] ?? '') ?></h5>
                <div><?= nl2br(htmlspecialchars($bill['company_address'] ?? '')) ?></div>
                <div><?= htmlspecialchars($bill['company_phone'] ?? '') ?> <?= htmlspecialchars($bill['company_email'] ?? '') ?></div>
                <?php if (!empty($bill['company_gstin'])): ?><div class="small text-muted">GSTIN: <?= htmlspecialchars($bill['company_gstin']) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Item / QR codes</th><th>HSN</th><th class="text-center">Qty</th><th class="text-end">MRP</th><th class="text-end">Discount</th><th class="text-end">Selling</th><th>GST details</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <?php foreach ($billItems as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($item['product_name']) ?>
                                <?php if (!empty($item['is_complementary'])): ?>
                                    <span class="badge bg-info-subtle text-info border ms-1"><i class="bi bi-gift-fill me-1"></i>Complementary</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted"><?= htmlspecialchars(trim(($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) ?></div>
                            <div class="small font-monospace text-muted"><?= htmlspecialchars(implode(', ', $item['tags'])) ?></div></td>
                        <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($item['hsn_code'] ?? '6109') ?></span></td><td class="text-center"><?= (int)$item['quantity'] ?></td>
                        <td class="text-end"><?= formatCurrency((float)((float)$item['mrp'] * (int)$item['quantity'])) ?></td>
                        <td class="text-end"><?= formatCurrency((float)((float)$item['discount_amount'])) ?></td>
                        <td class="text-end"><?= formatCurrency((float)((float)$item['taxable_amount'])) ?></td>
                        <td>
                            <div><?= htmlspecialchars($item['gst_type'] ?? 'BOTH') ?> · <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'Percentage' : 'Fixed' ?></div>
                            <?php if (($item['gst_type'] ?? '') === 'BOTH'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?> · CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'IGST'): ?>
                                <small class="text-muted">IGST <?= number_format((float)$item['igst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'SGST'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'CGST'): ?>
                                <small class="text-muted">CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= formatCurrency((float)((float)$item['tax_amount'])) ?></td>
                        <td class="text-end fw-semibold"><?= formatCurrency((float)((float)$item['total_amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="d-flex justify-content-between py-1"><span>Gross / MRP</span><strong><?= formatCurrency((float)((float)$bill['gross_amount'])) ?></strong></div>
            <div class="d-flex justify-content-between py-1"><span>Discount</span><strong><?= formatCurrency((float)((float)$bill['discount_amount'])) ?></strong></div>
            <div class="d-flex justify-content-between py-1"><span>Taxable Base</span><strong><?= formatCurrency((float)((float)$bill['subtotal'])) ?></strong></div>
            <?php if ((float)$bill['cgst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>CGST</span><strong><?= formatCurrency((float)((float)$bill['cgst_amount'])) ?></strong></div>
            <?php endif; ?>
            <?php if ((float)$bill['sgst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>SGST</span><strong><?= formatCurrency((float)((float)$bill['sgst_amount'])) ?></strong></div>
            <?php endif; ?>
            <?php if ((float)$bill['igst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>IGST</span><strong><?= formatCurrency((float)((float)$bill['igst_amount'])) ?></strong></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between py-1"><span>Total Taxes (Incl.)</span><strong><?= formatCurrency((float)((float)$bill['tax_amount'])) ?></strong></div>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong><?= formatCurrency((float)((float)$bill['total_amount'])) ?></strong></div>
            <div class="small text-muted mt-2">Payment: <?= htmlspecialchars($bill['payment_method']) ?> · <?= htmlspecialchars($bill['payment_status']) ?></div>
            <?php if (!empty($bill['staff_name'])): ?>
                <div class="small text-muted"><i class="bi bi-person-badge me-1"></i>Billed By: <strong><?= htmlspecialchars($bill['staff_name']) ?></strong></div>
            <?php endif; ?>
            <?php if ((float)($bill['cash_given'] ?? 0) > 0): ?>
                <div class="small text-muted mt-1">
                    Cash Tendered: <strong><?= formatCurrency((float)((float)$bill['cash_given'])) ?></strong> · 
                    Change Returned: <strong class="text-success"><?= formatCurrency((float)((float)($bill['change_returned'] ?? 0))) ?></strong>
                </div>
            <?php endif; ?>
            <?php if ($bill['payment_method'] === 'CASH_UPI'): ?><div class="small text-muted">Cash <?= formatCurrency((float)((float)$bill['cash_amount'])) ?> · UPI <?= formatCurrency((float)((float)$bill['upi_amount'])) ?></div><?php endif; ?>
            <?php if (!empty($bill['upi_payment_screenshot_url'])): ?>
                <div class="small mt-2">
                    <a href="<?= htmlspecialchars(uploadUrl($bill['upi_payment_screenshot_url']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <i class="bi bi-image me-1"></i>View GPay payment screenshot
                    </a>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
