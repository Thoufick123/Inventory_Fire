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
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#posReceiptModal"><i class="bi bi-receipt me-1"></i>POS Receipt</button>
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
            <?php if ((float)($bill['coupon_discount_amount'] ?? 0) > 0): ?>
                <div class="d-flex justify-content-between py-1 text-success">
                    <span>Coupon (<?= htmlspecialchars($bill['applied_coupon_code'] ?? '') ?>)</span>
                    <strong>-<?= formatCurrency((float)$bill['coupon_discount_amount']) ?></strong>
                </div>
            <?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong><?= formatCurrency((float)((float)$bill['total_amount'])) ?></strong></div>
            <div class="small text-muted mt-2">
                Payment: <?= htmlspecialchars($bill['payment_method']) ?> ·
                <span class="badge <?= ($bill['payment_status'] ?? '') === 'PAID' ? 'bg-success-subtle text-success' : (($bill['payment_status'] ?? '') === 'COD' ? 'bg-info-subtle text-info-emphasis' : 'bg-warning-subtle text-warning-emphasis') ?>">
                    <?= htmlspecialchars($bill['payment_status']) ?>
                </span>
            </div>
            <?php if (($bill['payment_status'] ?? '') === 'COD'): ?>
                <div class="small mt-1">
                    Paid: <strong class="text-success"><?= formatCurrency((float)($bill['paid_amount'] ?? 0)) ?></strong> ·
                    Remaining (COD): <strong class="text-danger"><?= formatCurrency((float)($bill['remaining_amount'] ?? 0)) ?></strong>
                </div>
            <?php endif; ?>
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

<?php
$storeName = trim((string)($concernBrand['name'] ?? ($sellerCompany['company_name'] ?? ($prodUnit['name'] ?? 'Retail Store'))));
$storeAddress = trim((string)($concernBrand['address'] ?? ($sellerCompany['company_address'] ?? ($prodUnit['address'] ?? ''))));
$storeGstin = trim((string)($concernBrand['gst_number'] ?? ($sellerCompany['gst_number'] ?? ($prodUnit['gst_number'] ?? ''))));
$isCust = ($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER';
$receiptPartyName = $isCust ? ($bill['customer_name'] ?? 'Walk-in Customer') : ($bill['company_name'] ?? 'Company');
$receiptPartyPhone = $isCust ? ($bill['customer_phone'] ?? '') : ($bill['company_phone'] ?? '');
$receiptPayLabel = strtoupper((string)($bill['payment_method'] ?? 'CASH')) . ' (' . strtoupper((string)($bill['payment_status'] ?? 'PAID')) . ')';
?>

<!-- POS Receipt / Sale Completed Modal -->
<div class="modal fade" id="posReceiptModal" tabindex="-1" aria-labelledby="posReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 470px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                <span class="badge bg-success px-3 py-2 rounded-pill fw-semibold" id="posReceiptModalLabel">
                    <i class="bi bi-check-circle me-1"></i>SALE COMPLETED
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
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
            </div>
            <div class="modal-footer border-0 pt-2 pb-3 px-4 d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <a href="<?= url('production/billing/print/' . (int)$bill['id']) ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-file-earmark-text me-1"></i>A4 Format
                </a>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnPrintPosThermal">
                    <i class="bi bi-printer me-1"></i>POS Format
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($showCompletedModal)): ?>
    const modalEl = document.getElementById('posReceiptModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const receiptModal = new bootstrap.Modal(modalEl);
        receiptModal.show();
    }
    <?php endif; ?>

    const printPosBtn = document.getElementById('btnPrintPosThermal');
    if (printPosBtn) {
        printPosBtn.addEventListener('click', function () {
            const contentEl = document.getElementById('posThermalReceiptContent');
            if (!contentEl) return;
            const win = window.open('', '_blank', 'width=380,height=650');
            if (!win) return;
            win.document.write(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>POS Receipt - <?= htmlspecialchars($bill['bill_number'], ENT_QUOTES, 'UTF-8') ?></title>' +
                '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">' +
                '<style>@page { size: 80mm auto; margin: 4mm; } body { width: 76mm; margin: 0 auto; padding: 6px; font-family: ui-monospace, monospace; }</style>' +
                '</head><body>' + contentEl.innerHTML +
                '<script>window.onload = function(){ setTimeout(function(){ window.print(); }, 250); };<\/script></body></html>'
            );
            win.document.close();
        });
    }
});
</script>
</div>
