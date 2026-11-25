<?php
$concernBrand = function_exists('getConcernBranding') ? getConcernBranding($purchase['concern_id'] ?? null) : [];
$purchaseLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : '';
?>
<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <?php if (!empty($purchaseLogo)): ?>
            <img src="<?= htmlspecialchars($purchaseLogo) ?>" alt="Logo" class="mb-2" style="max-height: 48px; max-width: 160px; object-fit: contain;">
        <?php endif; ?>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0"><i class="bi bi-receipt me-2 text-primary"></i><?= e($purchase['purchase_number']) ?></h2>
            <?php
                $pType = $purchase['purchase_type'] ?? 'FABRIC_ROLL';
                if ($pType === 'MIXED'):
            ?>
                <span class="badge border fs-6" style="background-color: #f3e8ff; color: #7e22ce; border-color: #d8b4fe !important;"><i class="bi bi-collection me-1"></i>Combined (Mixed Invoice)</span>
            <?php elseif ($pType === 'RAW_MATERIAL'): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border fs-6"><i class="bi bi-boxes me-1"></i>Raw Material</span>
            <?php elseif ($pType === 'THIRD_PARTY'): ?>
                <span class="badge bg-info-subtle text-info border fs-6"><i class="bi bi-box-seam me-1"></i>Third-Party</span>
            <?php elseif ($pType === 'OWN_PRODUCT'): ?>
                <span class="badge bg-success-subtle text-success border fs-6"><i class="bi bi-building-check me-1"></i>Own Production</span>
            <?php else: ?>
                <span class="badge bg-primary-subtle text-primary border fs-6"><i class="bi bi-layers-half me-1"></i>Fabric Roll</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white py-3 px-4 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Order Details</div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6"><small class="text-muted d-block">Purchase Number</small><strong><?= e($purchase['purchase_number']) ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Purchase Type</small><strong class="text-uppercase"><?= str_replace('_', ' ', $pType) ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Purchase Date</small><strong><?= e(formatDate($purchase['purchase_date'])) ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Invoice / Bill Number</small><strong><?= e($purchase['invoice_number'] ?: '—') ?></strong></div>
                    <div class="col-sm-12"><small class="text-muted d-block">Notes</small><strong><?= e($purchase['notes'] ?: '—') ?></strong></div>
                    <?php if (!empty($documents)): ?>
                        <div class="col-12">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                <i class="bi bi-paperclip me-1"></i><?= count($documents) ?> Invoice Attachment<?= count($documents) > 1 ? 's' : '' ?> (AVIF/PDF)
                            </span>
                        </div>
                    <?php elseif (!empty($purchase['document_url'])): ?>
                        <div class="col-12">
                            <a href="<?= uploadUrl($purchase['document_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-file-earmark-text me-1"></i>View Invoice Attachment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white py-3 px-4 fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Supplier / Vendor Details</div>
            <div class="card-body p-4">
                <strong><?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?></strong>
                <?php if (!empty($purchase['seller_company'])): ?><div><?= e($purchase['seller_name']) ?></div><?php endif; ?>
                <div class="mt-2"><?= e($purchase['seller_phone'] ?: '—') ?><?= !empty($purchase['seller_email']) ? ' · ' . e($purchase['seller_email']) : '' ?></div>
                <?php if (!empty($purchase['seller_gst_number'])): ?><div>GSTIN: <?= e($purchase['seller_gst_number']) ?></div><?php endif; ?>
                <?php if (!empty($purchase['seller_address'])): ?><div class="text-muted mt-2"><?= e($purchase['seller_address']) ?></div><?php endif; ?>
                <?php if (!empty($purchase['seller_city']) || !empty($purchase['seller_state'])): ?><div class="text-muted"><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($documents)): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Attached Invoices &amp; Bills</h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= count($documents) ?> Invoice<?= count($documents) > 1 ? 's' : '' ?></span>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle small">
            <i class="bi bi-filetype-avif me-1"></i>Optimized in AVIF Format
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <?php foreach ($documents as $idx => $doc): 
                $docUrl = $doc['document_url'] ?? '';
                $isAvif = str_ends_with(strtolower($docUrl), '.avif') || strtolower($doc['file_type'] ?? '') === 'avif';
                $isPdf = str_ends_with(strtolower($docUrl), '.pdf') || strtolower($doc['file_type'] ?? '') === 'pdf';
                $isImg = $isAvif || preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $docUrl);
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border shadow-xs bg-white">
                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div class="text-center mb-2 rounded bg-light border d-flex align-items-center justify-content-center" style="height:110px;overflow:hidden;">
                            <?php if ($isImg): ?>
                                <a href="<?= uploadUrl($docUrl) ?>" target="_blank" rel="noopener">
                                    <img src="<?= uploadUrl($docUrl) ?>" alt="Invoice #<?= $idx + 1 ?>" style="max-height:110px;max-width:100%;object-fit:contain;">
                                </a>
                            <?php else: ?>
                                <a href="<?= uploadUrl($docUrl) ?>" target="_blank" rel="noopener" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-pdf text-danger display-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="small fw-semibold text-truncate" title="<?= e($doc['file_name'] ?: ('Invoice #' . ($idx + 1))) ?>">
                                <?= e($doc['file_name'] ?: ('Invoice #' . ($idx + 1))) ?>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top">
                                <span class="badge <?= $isAvif ? 'bg-success-subtle text-success border border-success-subtle' : ($isPdf ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-secondary border') ?>" style="font-size:10px;">
                                    <?= $isAvif ? 'AVIF Image' : ($isPdf ? 'PDF Bill' : strtoupper($doc['file_type'] ?? 'FILE')) ?>
                                </span>
                                <a href="<?= uploadUrl($docUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View Full
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Inwarded Products &amp; Lines</h5>
            <span class="badge bg-secondary-subtle text-secondary border" id="itemCountBadge"><?= count($items) ?> Items</span>
        </div>
        <div class="d-flex align-items-center gap-2" style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="purchasedItemsSearch" class="form-control border-start-0" placeholder="Search item, SKU, roll, HSN..." autocomplete="off">
                <button type="button" class="btn btn-outline-secondary border-start-0 d-none" id="clearItemSearch" title="Clear search"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
    </div>
    <?php
        $hasSgst = false;
        $hasCgst = false;
        $hasIgst = false;
        $sgstRates = [];
        $cgstRates = [];
        $igstRates = [];

        foreach ($items as $it) {
            $tType = $it['gst_type'] ?? 'EXEMPTED';
            $sgAmt = (float)($it['sgst_amount'] ?? 0);
            $cgAmt = (float)($it['cgst_amount'] ?? 0);
            $igAmt = (float)($it['igst_amount'] ?? 0);
            $isPct = (($it['tax_input_type'] ?? 'PERCENT') === 'PERCENT');

            if (in_array($tType, ['SGST', 'BOTH'], true) || $sgAmt > 0) {
                $hasSgst = true;
                if ($isPct && (float)($it['sgst_value'] ?? 0) > 0) {
                    $sgstRates[(string)((float)$it['sgst_value'] + 0)] = true;
                }
            }
            if (in_array($tType, ['CGST', 'BOTH'], true) || $cgAmt > 0) {
                $hasCgst = true;
                if ($isPct && (float)($it['cgst_value'] ?? 0) > 0) {
                    $cgstRates[(string)((float)$it['cgst_value'] + 0)] = true;
                }
            }
            if ($tType === 'IGST' || $igAmt > 0) {
                $hasIgst = true;
                if ($isPct && (float)($it['igst_value'] ?? 0) > 0) {
                    $igstRates[(string)((float)$it['igst_value'] + 0)] = true;
                }
            }
        }

        $sgstTitle = count($sgstRates) === 1 ? 'SGST (' . array_key_first($sgstRates) . '%)' : 'SGST';
        $cgstTitle = count($cgstRates) === 1 ? 'CGST (' . array_key_first($cgstRates) . '%)' : 'CGST';
        $igstTitle = count($igstRates) === 1 ? 'IGST (' . array_key_first($igstRates) . '%)' : 'IGST';
        $hasAnyTax = $hasSgst || $hasCgst || $hasIgst;
        $taxColCount = ($hasSgst ? 1 : 0) + ($hasCgst ? 1 : 0) + ($hasIgst ? 1 : 0) + (!$hasAnyTax ? 1 : 0);
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="purchasedItemsTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Item Details</th>
                    <th>HSN</th>
                    <th>Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Subtotal</th>
                    <?php if ($hasSgst): ?><th class="text-end"><?= e($sgstTitle) ?></th><?php endif; ?>
                    <?php if ($hasCgst): ?><th class="text-end"><?= e($cgstTitle) ?></th><?php endif; ?>
                    <?php if ($hasIgst): ?><th class="text-end"><?= e($igstTitle) ?></th><?php endif; ?>
                    <?php if (!$hasAnyTax): ?><th class="text-end">Tax</th><?php endif; ?>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody id="purchasedItemsBody">
            <?php
                $subtotal = 0.0;
                $taxTotal = 0.0;
                $sgstTotal = 0.0;
                $cgstTotal = 0.0;
                $igstTotal = 0.0;
                foreach ($items as $index => $item):
                    $gross = (float)$item['unit_price'] * (float)$item['quantity'];
                    $sgAmt = (float)($item['sgst_amount'] ?? 0);
                    $cgAmt = (float)($item['cgst_amount'] ?? 0);
                    $igAmt = (float)($item['igst_amount'] ?? 0);
                    $tax = $sgAmt + $cgAmt + $igAmt;
                    $sgstTotal += $sgAmt;
                    $cgstTotal += $cgAmt;
                    $igstTotal += $igAmt;

                    $taxTreatment = $item['tax_treatment'] ?? 'INCLUDED';
                    $base = ($taxTreatment === 'INCLUDED') ? max(0, (float)$item['total_price'] - $tax) : $gross;
                    $subtotal += $base;
                    $taxTotal += $tax;
                    $taxType = $item['gst_type'] ?? 'LEGACY';
                    if ($taxType === 'BOTH') $taxType = 'SGST + CGST';
                    if ($taxType === 'LEGACY') $taxType = $tax > 0 ? 'Legacy SGST + CGST' : 'Exempted';

                    // Tax Rate calculation
                    $isPercent = (($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT');
                    $sgstVal = (float)($item['sgst_value'] ?? 0);
                    $cgstVal = (float)($item['cgst_value'] ?? 0);
                    $igstVal = (float)($item['igst_value'] ?? 0);
                    $totalRate = $sgstVal + $cgstVal + $igstVal;

                    if ($isPercent && $totalRate > 0) {
                        if ($igstVal > 0) {
                            $itemTaxRateText = ($igstVal + 0) . '%';
                        } elseif ($sgstVal > 0 && $cgstVal > 0) {
                            $itemTaxRateText = ($totalRate + 0) . '% (' . ($sgstVal + 0) . '% + ' . ($cgstVal + 0) . '%)';
                        } else {
                            $itemTaxRateText = ($totalRate + 0) . '%';
                        }
                    } elseif ($tax > 0) {
                        $itemTaxRateText = 'Fixed';
                    } else {
                        $itemTaxRateText = '0%';
                    }
                    $searchData = strtolower(implode(' ', [
                        $item['item_name'] ?? '',
                        $item['item_type'] ?? '',
                        $item['vendor_sku'] ?? '',
                        $item['product_code'] ?? '',
                        $item['roll_number'] ?? '',
                        $item['color'] ?? '',
                        $item['size'] ?? '',
                        $item['design_name'] ?? '',
                        $item['start_code'] ?? '',
                        $item['end_code'] ?? '',
                        $item['hsn_code'] ?? '',
                        $item['unit'] ?? '',
                        $item['unit_price'] ?? '',
                        $item['total_price'] ?? '',
                        $taxType
                    ]));
            ?>
                <tr class="item-row" data-search="<?= htmlspecialchars($searchData, ENT_QUOTES) ?>">
                    <td><?= $index + 1 ?></td>
                    <td>
                        <?php if ($item['item_type'] === 'RAW_MATERIAL'): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border mb-1"><i class="bi bi-boxes me-1"></i>Raw Material</span>
                            <strong class="d-block text-dark"><?= e($item['item_name']) ?></strong>
                            <div class="small text-muted">
                                <?php if (!empty($item['vendor_sku'])): ?><span class="font-monospace me-2">SKU: <?= e($item['vendor_sku']) ?></span><?php endif; ?>
                                <?php if (!empty($item['size'])): ?><span class="me-2">Count/Size: <?= e($item['size']) ?></span><?php endif; ?>
                                <?php if (!empty($item['color'])): ?><span>Color/Shade: <?= e($item['color']) ?></span><?php endif; ?>
                            </div>
                        <?php elseif ($item['item_type'] === 'THIRD_PARTY'): ?>
                            <span class="badge bg-info-subtle text-info border mb-1"><i class="bi bi-box-seam me-1"></i>Third-Party</span>
                            <strong class="d-block text-dark"><?= e($item['item_name']) ?></strong>
                            <div class="small text-muted">
                                <?php if (!empty($item['vendor_sku'])): ?><span class="font-monospace me-2">SKU: <?= e($item['vendor_sku']) ?></span><?php endif; ?>
                            </div>
                        <?php elseif ($item['item_type'] === 'OWN_PRODUCT'): ?>
                            <span class="badge bg-success-subtle text-success border mb-1"><i class="bi bi-building-check me-1"></i>Own Production</span>
                            <strong class="d-block text-dark"><?= e($item['item_name']) ?></strong>
                            <div class="small text-muted">
                                <?php if (!empty($item['product_code'])): ?><span class="font-monospace me-2">Code: <?= e($item['product_code']) ?></span><?php endif; ?>
                                <?php if (!empty($item['size'])): ?><span class="me-2">Size: <?= e($item['size']) ?></span><?php endif; ?>
                                <?php if (!empty($item['color'])): ?><span>Color: <?= e($item['color']) ?></span><?php endif; ?>
                            </div>
                        <?php elseif ($item['item_type'] === 'FABRIC_ROLL'): ?>
                            <span class="badge bg-primary-subtle text-primary border mb-1"><i class="bi bi-layers-half me-1"></i>Fabric Roll</span>
                            <strong class="d-block"><?= e($item['item_name']) ?></strong>
                            <small class="text-muted"><?= e($item['roll_number'] ?: '—') ?> · <?= e($item['color'] ?: '—') ?> · <?= e($item['design_name'] ?: '—') ?></small>
                            <?php if (!empty($item['start_code'])): ?><small class="d-block font-monospace text-primary"><?= e($item['start_code']) ?><?= $item['end_code'] !== $item['start_code'] ? '–' . e($item['end_code']) : '' ?></small><?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border mb-1">Supply</span>
                            <strong class="d-block"><?= e($item['item_name']) ?></strong>
                            <?php if (!empty($item['product_code'])): ?><small class="text-muted font-monospace"><span class="badge bg-light text-dark border me-1">Code: <?= e($item['product_code']) ?></span></small><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border font-monospace"><?= e($item['hsn_code'] ?: '—') ?></span></td>
                    <td><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?><?= !empty($item['weight_kg']) ? '<br><small class="text-muted">' . number_format((float)$item['weight_kg'], 2) . ' kg</small>' : '' ?></td>
                    <td class="text-end">
                        <?= formatCurrency((float)((float)$item['unit_price'])) ?>
                        <small class="text-muted d-block" style="font-size:10px;"><?= (($item['tax_treatment'] ?? 'INCLUDED') === 'EXCLUDED') ? 'GST Excl.' : 'GST Incl.' ?></small>
                    </td>
                    <td class="text-end font-monospace"><?= formatCurrency((float)($base)) ?></td>
                    <?php if ($hasSgst): ?>
                        <td class="text-end font-monospace">
                            <?= formatCurrency((float)($sgAmt)) ?>
                            <?php if (count($sgstRates) > 1 && $isPercent && $sgstVal > 0): ?>
                                <small class="text-muted d-block" style="font-size:10px;">(<?= ($sgstVal + 0) ?>%)</small>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($hasCgst): ?>
                        <td class="text-end font-monospace">
                            <?= formatCurrency((float)($cgAmt)) ?>
                            <?php if (count($cgstRates) > 1 && $isPercent && $cgstVal > 0): ?>
                                <small class="text-muted d-block" style="font-size:10px;">(<?= ($cgstVal + 0) ?>%)</small>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($hasIgst): ?>
                        <td class="text-end font-monospace">
                            <?= formatCurrency((float)($igAmt)) ?>
                            <?php if (count($igstRates) > 1 && $isPercent && $igstVal > 0): ?>
                                <small class="text-muted d-block" style="font-size:10px;">(<?= ($igstVal + 0) ?>%)</small>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if (!$hasAnyTax): ?>
                        <td class="text-end text-muted">—</td>
                    <?php endif; ?>
                    <td class="text-end fw-bold font-monospace"><?= formatCurrency((float)((float)$item['total_price'])) ?></td>
                </tr>
            <?php endforeach; ?>
                <tr id="noItemsMatchRow" class="d-none">
                    <td colspan="<?= 7 + $taxColCount ?>" class="text-center text-muted py-4">
                        <i class="bi bi-search me-1"></i> No matching purchased items found.
                    </td>
                </tr>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="5" class="text-end">Subtotal</th>
                    <th class="text-end font-monospace"><?= formatCurrency((float)($subtotal)) ?></th>
                    <?php if ($hasSgst): ?><th class="text-end font-monospace"><?= formatCurrency((float)($sgstTotal)) ?></th><?php endif; ?>
                    <?php if ($hasCgst): ?><th class="text-end font-monospace"><?= formatCurrency((float)($cgstTotal)) ?></th><?php endif; ?>
                    <?php if ($hasIgst): ?><th class="text-end font-monospace"><?= formatCurrency((float)($igstTotal)) ?></th><?php endif; ?>
                    <?php if (!$hasAnyTax): ?><th class="text-end">—</th><?php endif; ?>
                    <th class="text-end font-monospace"><?= formatCurrency((float)((float)$purchase['total_amount'])) ?></th>
                </tr>
                <?php if ($hasAnyTax): ?>
                <tr>
                    <th colspan="<?= 6 + $taxColCount ?>" class="text-end text-muted small">Total Taxes</th>
                    <th class="text-end font-monospace text-muted small"><?= formatCurrency((float)($taxTotal)) ?></th>
                </tr>
                <?php endif; ?>
                <tr>
                    <th colspan="<?= 6 + $taxColCount ?>" class="text-end">Overall Total</th>
                    <th class="text-end text-success font-monospace fs-6"><?= formatCurrency((float)((float)$purchase['total_amount'])) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('purchasedItemsSearch');
    const clearBtn = document.getElementById('clearItemSearch');
    const rows = document.querySelectorAll('#purchasedItemsBody .item-row');
    const noMatchRow = document.getElementById('noItemsMatchRow');
    const countBadge = document.getElementById('itemCountBadge');
    const totalCount = rows.length;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            if (clearBtn) {
                clearBtn.classList.toggle('d-none', query.length === 0);
            }

            let visibleCount = 0;
            rows.forEach(function (row) {
                const searchData = (row.getAttribute('data-search') || '') + ' ' + (row.textContent || '').toLowerCase();
                const matches = !query || searchData.includes(query);
                row.classList.toggle('d-none', !matches);
                if (matches) visibleCount++;
            });

            if (noMatchRow) {
                noMatchRow.classList.toggle('d-none', visibleCount > 0);
            }

            if (countBadge) {
                countBadge.textContent = query ? (visibleCount + ' of ' + totalCount + ' Items') : (totalCount + ' Items');
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }
    }
});
</script>
