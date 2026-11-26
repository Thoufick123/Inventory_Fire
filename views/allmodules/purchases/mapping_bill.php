<?php
/**
 * Modern ERP QR Generations Workspace — Map Purchase Bill Lines
 * Matches Image 2, 3, 4 & 5 Format with User Refinements:
 *  1. Purchased Product Baseline Details (Financial Valuation & Supplier Profile)
 *  2. Category Hierarchy (3-Tier) & HSN Classification (Tier 1/2/3, Short Codes, QR Key) - Level Purpose removed
 *  3. Product Attributes & Variations (Style Code, Title, Multi-Color Pills, Size Pills) - Description column removed
 *  4. Product Variants Matrix Table Container (Auto SKU, Allocation, MRP, SP, Margin %)
 */

// Active Item Baseline Metrics
$activeItemId = (int)($activeItem['id'] ?? 0);
$purchasedQty = (float)($activeItem['quantity'] ?? 0);
$unitPrice = (float)($activeItem['unit_price'] ?? 0);

// Accurately compute GST Rate
$gstRate = (float)($activeItem['cgst_value'] ?? 0) + (float)($activeItem['sgst_value'] ?? 0) + (float)($activeItem['igst_value'] ?? 0);

// Accurately compute Tax Amount from database columns or valuation difference
$taxAmount = (float)($activeItem['tax_amount'] ?? 0);
if ($taxAmount <= 0) {
    $taxAmount = (float)($activeItem['sgst_amount'] ?? 0) + (float)($activeItem['cgst_amount'] ?? 0) + (float)($activeItem['igst_amount'] ?? 0);
}
if ($taxAmount <= 0) {
    $subtotal = $purchasedQty * $unitPrice;
    $totalVal = (float)($activeItem['total_price'] ?? 0);
    $taxTreatment = strtoupper($activeItem['tax_treatment'] ?? 'EXCLUDED');
    if ($taxTreatment === 'EXCLUDED' && $totalVal > $subtotal) {
        $taxAmount = round($totalVal - $subtotal, 2);
    } elseif ($taxTreatment === 'EXCLUDED' && $gstRate > 0 && $subtotal > 0) {
        $taxAmount = round($subtotal * ($gstRate / 100), 2);
    } elseif ($taxTreatment === 'INCLUDED' && $gstRate > 0 && $totalVal > 0) {
        $taxAmount = round($totalVal - ($totalVal / (1 + ($gstRate / 100))), 2);
    }
}
if ($gstRate <= 0 && $taxAmount > 0 && ($purchasedQty * $unitPrice) > 0) {
    $gstRate = round(($taxAmount / ($purchasedQty * $unitPrice)) * 100, 2);
}

$totalValuation = (float)($activeItem['total_price'] ?? 0);
if ($totalValuation <= 0) {
    $totalValuation = ($purchasedQty * $unitPrice) + $taxAmount;
}

$taxModeLabel = ($activeItem['tax_treatment'] ?? '') === 'INCLUDED' ? 'Include GST' : 'Exclude GST';
$activeHsn = !empty($activeItem['hsn_code']) ? trim((string)$activeItem['hsn_code']) : '';

// Active Item Existing Mapped Configuration
$activeCatId = (int)($activeItem['mapped_third_party_category_id'] ?? 0);
$activeSubcatId = (int)($activeItem['mapped_third_party_subcategory_id'] ?? 0);
$activeSubSubcatId = (int)($activeItem['mapped_third_party_sub_subcategory_id'] ?? 0);

// Find short codes
$activeCatCode = '';
foreach ($categories as $cat) {
    if ((int)$cat['id'] === $activeCatId) {
        $activeCatCode = (string)($cat['code'] ?? '');
        break;
    }
}
$activeSubcatCode = '';
foreach ($subcategories as $sc) {
    if ((int)$sc['id'] === $activeSubcatId) {
        $activeSubcatCode = (string)($sc['code'] ?? '');
        break;
    }
}
$activeSubSubcatCode = '';
foreach ($subSubcategories as $ssc) {
    if ((int)$ssc['id'] === $activeSubSubcatId) {
        $activeSubSubcatCode = (string)($ssc['code'] ?? '');
        break;
    }
}

$initialQrKey = strtoupper(trim($activeCatCode . $activeSubcatCode . $activeSubSubcatCode));

// Attributes
$parsedAttrs = $activeItem['parsed_attributes'] ?? [];
$activeStyleCode = $parsedAttrs['style_code'] ?? ($activeItem['product_code'] ?: ($activeItem['vendor_sku'] ?: 'GRM-STY-' . sprintf('%04d', $activeItemId)));
$activeStyleName = $parsedAttrs['style_name'] ?? $activeItem['item_name'];

// Parse multiple colors if present - extract from all available sources
$activeColors = [];
if (!empty($parsedAttrs['colors']) && is_array($parsedAttrs['colors'])) {
    foreach ($parsedAttrs['colors'] as $c) {
        $c = trim((string)$c);
        if ($c !== '' && !in_array($c, $activeColors, true)) {
            $activeColors[] = $c;
        }
    }
}
if (!empty($activeItem['color'])) {
    foreach (explode(',', (string)$activeItem['color']) as $c) {
        $c = trim($c);
        if ($c !== '' && !in_array($c, $activeColors, true)) {
            $activeColors[] = $c;
        }
    }
}
if (!empty($existingVariants)) {
    foreach ($existingVariants as $ev) {
        $c = trim((string)($ev['color'] ?? ''));
        if ($c !== '' && !in_array($c, $activeColors, true)) {
            $activeColors[] = $c;
        }
    }
}
$activeColors = array_values(array_filter(array_map('trim', $activeColors), fn($v) => $v !== ''));
if (empty($activeColors)) {
    $activeColors = ['Blue'];
}

// Parse sizes - extract from all available sources
$selectedSizes = [];
if (!empty($parsedAttrs['sizes']) && is_array($parsedAttrs['sizes'])) {
    foreach ($parsedAttrs['sizes'] as $s) {
        $s = trim((string)$s);
        if ($s !== '' && !in_array($s, $selectedSizes, true)) {
            $selectedSizes[] = $s;
        }
    }
}
if (!empty($activeItem['size'])) {
    foreach (explode(',', (string)$activeItem['size']) as $s) {
        $s = trim($s);
        if ($s !== '' && !in_array($s, $selectedSizes, true)) {
            $selectedSizes[] = $s;
        }
    }
}
if (!empty($existingVariants)) {
    foreach ($existingVariants as $ev) {
        $s = trim((string)($ev['size'] ?? ''));
        if ($s !== '' && !in_array($s, $selectedSizes, true)) {
            $selectedSizes[] = $s;
        }
    }
}
$selectedSizes = array_values(array_filter(array_map('trim', $selectedSizes), fn($v) => $v !== ''));
if (empty($selectedSizes)) {
    $selectedSizes = ['S', 'M', 'L'];
}

$defaultMrp = (float)($activeItem['mrp'] ?? 0);
if ($defaultMrp <= 0 && !empty($existingVariants[0]['mrp'])) {
    $defaultMrp = (float)$existingVariants[0]['mrp'];
}
if ($defaultMrp <= 0) {
    $defaultMrp = round($unitPrice > 0 ? ($unitPrice * 1.8) : 599, 2);
}

$defaultSp = (float)($activeItem['selling_price'] ?? 0);
if ($defaultSp <= 0 && !empty($existingVariants[0]['selling_price'])) {
    $defaultSp = (float)$existingVariants[0]['selling_price'];
}
if ($defaultSp <= 0) {
    $defaultSp = round($unitPrice > 0 ? ($unitPrice * 1.5) : 499, 2);
}

// Check if all lines on this purchase bill are fully mapped
$allLinesMapped = true;
foreach ($items as $it) {
    if (empty($it['is_mapped'])) {
        $allLinesMapped = false;
        break;
    }
}
?>

<div class="container-fluid px-3 px-md-4 py-3">
<style>
.matrix-qty::-webkit-inner-spin-button,
.matrix-qty::-webkit-outer-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}
.matrix-qty {
    -moz-appearance: textfield !important;
}
.matrix-mrp.is-invalid, .matrix-sp.is-invalid, #batchInputMrp.is-invalid, #batchInputSp.is-invalid {
    border-color: #dc3545 !important;
    background-color: #fff8f8 !important;
    box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.25) !important;
}
tr.table-danger, tr.table-danger > td {
    background-color: #fff1f2 !important;
}
#containerColorPills, #containerSizePills {
    display: flex;
    flex-wrap: wrap;
    row-gap: 12px !important;
    column-gap: 8px !important;
}
#containerColorPills .form-check, #containerSizePills .form-check {
    min-height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    display: inline-flex !important;
}
#containerColorPills .btn, #containerSizePills .btn {
    line-height: 1.4 !important;
    white-space: nowrap !important;
}
</style>

    <!-- Top Breadcrumbs & Action Bar (Image 4: Scan/Labels button removed) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= url('purchases') ?>" class="text-decoration-none text-muted">All Modules</a></li>
                <li class="breadcrumb-item"><a href="<?= url('purchases/mapping') ?>" class="text-decoration-none text-muted">QR Generations</a></li>
                <li class="breadcrumb-item"><a href="<?= url('purchases/mapping/bill/' . (int)$purchase['id']) ?>" class="text-decoration-none text-muted"><?= e($purchase['purchase_number']) ?></a></li>
                <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">QR Generation Finished</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= url('purchases/view/' . (int)$purchase['id']) ?>" class="btn btn-outline-info btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" title="View Purchase Bill">
                <i class="bi bi-eye"></i> View Purchase
            </a>
            <?php if (!empty($activeItem['is_mapped']) && empty($activeItem['qr_generation_id']) && empty($activeItem['qr_batch_code'])): ?>
                <a href="<?= url('production/qr-generations/create?tab=third_party&purchase_id=' . (int)$purchase['id'] . '&purchase_item_id=' . $activeItemId) ?>"
                   class="btn btn-outline-info btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-qr-code"></i> Generate QR
                </a>
            <?php endif; ?>
            <?php if (!empty($purchase['id']) && hasPermission('production_purchase_mapping', 'delete')): ?>
                <form method="POST" action="<?= url('purchases/mapping/unmap-bill/' . (int)$purchase['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to reset and unmap all items on this bill?');">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-outline-warning btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm text-dark" title="Reset all mappings for this bill">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Mapping
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Register
            </a>
        </div>
    </div>

    <!-- Banner when all bill lines are already mapped -->
    <?php if (!empty($allLinesMapped)): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm border border-success-subtle">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                <div>
                    <strong class="fs-6 text-success-emphasis">All Product Lines Mapped!</strong>
                    <div class="small text-muted">All <?= count($items) ?> product lines on this purchase bill have been successfully mapped to the catalog. You can review any line or return to the register.</div>
                </div>
            </div>
            <a href="<?= url('purchases/mapping') ?>" class="btn btn-success btn-sm px-3 py-1.5 fw-semibold shadow-xs">
                <i class="bi bi-arrow-left me-1"></i>Back to QR Generations
            </a>
        </div>
    <?php endif; ?>

    <!-- Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%); border-left: 4px solid #0d6efd !important;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-diagram-3-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="mb-0 fw-bold text-dark">Map Finished Good to Catalog: <span class="text-primary"><?= e($activeItem['item_name']) ?></span></h4>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Master Catalog Mapping</span>
                        </div>
                        <p class="text-muted small mb-0">Define 3-tier category hierarchy, attributes (sizes, colors), HSN classification, retail pricing, and variant matrix for purchased product <strong><?= e($activeItem['item_name']) ?></strong>.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 font-monospace fs-7">
                        <i class="bi bi-receipt me-1"></i>Purchase Bill #<?= e($purchase['purchase_number']) ?>
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 fs-7">
                        <i class="bi bi-file-earmark-text me-1"></i>Supplier Inv: <?= e($purchase['invoice_number'] ?: '—') ?>
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fs-7">
                        <i class="bi bi-check-circle-fill me-1"></i>Paid
                    </span>
                    <span class="badge <?= !empty($purchase['is_inwarded']) ? 'bg-info-subtle text-info border-info-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' ?> border px-2.5 py-1.5 fs-7">
                        <i class="bi bi-box-seam me-1"></i><?= !empty($purchase['is_inwarded']) ? 'Inwarded' : 'Pending Inward' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Item Bill Line Stepper (when bill has multiple items) with Breathable Spacing -->
    <?php if (count($items) > 1): ?>
        <div class="card border shadow-sm mb-4 bg-white" style="border-radius: 10px;">
            <div class="card-body py-3 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <span class="text-uppercase text-muted fw-bold small me-2 d-inline-flex align-items-center">
                        <i class="bi bi-list-ol me-1.5 text-primary fs-6"></i>Bill Lines (<?= count($items) ?>):
                    </span>
                    <?php foreach ($items as $idx => $lineItem): ?>
                        <?php
                            $isThisActive = ((int)$lineItem['id'] === $activeItemId);
                            $isLineMapped = !empty($lineItem['is_mapped']);
                            $isLineStocked = !empty($lineItem['has_stock_added']);
                            $isExtraLine = !empty($lineItem['is_extra_purchase_item']);
                        ?>
                        <a href="<?= url('purchases/mapping/bill/' . (int)$purchase['id'] . '?item_id=' . (int)$lineItem['id']) ?>"
                           class="btn d-inline-flex align-items-center gap-2 rounded-3 shadow-xs transition-all <?= $isThisActive ? 'btn-primary fw-bold shadow-sm' : 'btn-outline-secondary bg-white text-dark border' ?>"
                           style="padding: 8px 16px; margin: 4px 3px; font-size: 0.875rem;">
                            <span class="badge rounded-pill <?= $isThisActive ? 'bg-white text-primary fw-bold' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.75rem; padding: 4px 8px;">
                                <?= $idx + 1 ?>
                            </span>
                            <span class="fw-semibold"><?= e($lineItem['item_name']) ?></span>
                            <span class="small <?= $isThisActive ? 'text-white-50' : 'text-muted' ?> ms-0.5">(<?= (float)$lineItem['quantity'] ?> <?= e($lineItem['unit']) ?>)</span>
                            <?php if ($isLineMapped): ?>
                                <i class="bi bi-check2-circle <?= $isThisActive ? 'text-white' : 'text-success' ?> fs-6" title="Mapped"></i>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark py-0.5 px-2 rounded-pill" style="font-size: 0.65rem;">Pending</span>
                            <?php endif; ?>
                            <?php if ($isLineStocked): ?>
                                <span class="badge bg-success py-0.5 px-2 rounded-pill" style="font-size: 0.65rem;">Added Stock</span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border py-0.5 px-2 rounded-pill" style="font-size: 0.65rem;">Not Stocked</span>
                            <?php endif; ?>
                            <?php if ($isExtraLine): ?>
                                <span class="badge bg-info text-dark py-0.5 px-2 rounded-pill" style="font-size: 0.65rem;" title="Added outside original purchase list">Extra Added</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="small text-muted d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    <span>Switch product lines to map them individually</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Mapping Form -->
    <form method="POST" action="<?= url('purchases/mapping/save') ?>" id="formMapProductBill">
        <?= CSRF::field() ?>
        <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
        <input type="hidden" name="purchase_id" value="<?= (int)$purchase['id'] ?>">
        <input type="hidden" name="item_id" id="hiddenItemId" value="<?= $activeItemId ?>">
        <input type="hidden" name="mapping_action" value="create_new">
        <input type="hidden" name="save_mode" id="hiddenSaveMode" value="complete">

        <!-- ========================================================================= -->
        <!-- SECTION 1: PURCHASED PRODUCT BASELINE DETAILS (Image 2)                  -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">1</span>
                    <h5 class="mb-0 fw-bold text-dark">Purchased Product Baseline Details</h5>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="toggleBaselineDetails" title="Collapse or expand baseline details">
                    <i class="bi bi-arrows-collapse"></i> Compact
                </button>
            </div>
            <div class="card-body p-4" id="baselineDetailsBody">
                <!-- 3 Top Reference Panels -->
                <div class="row g-3 mb-4">
                    <!-- Column 1: Product Identity -->
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="d-flex align-items-center justify-content-between gap-1.5 text-uppercase text-muted fw-bold small mb-2">
                                <span><i class="bi bi-box-seam text-primary"></i> Product Identity</span>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;"><i class="bi bi-pencil me-1"></i>Editable</span>
                            </div>
                            <input type="text"
                                   class="form-control form-control-sm fw-bold fs-6 text-dark mb-1"
                                   id="inputPurchasedItemName"
                                   name="item_name"
                                   value="<?= e($activeItem['item_name']) ?>"
                                   required
                                   maxlength="255"
                                   placeholder="Purchased Product Name">
                            <div class="d-flex flex-wrap gap-1.5 align-items-center mt-2">
                                <span class="badge bg-light text-dark border font-monospace">
                                    Item Code: <?= e($activeItem['product_code'] ?: ($activeItem['vendor_sku'] ?: 'FG-' . sprintf('%04d', $activeItemId))) ?>
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary border">Unit: <?= e($activeItem['unit'] ?: 'Pieces') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Supplier Profile -->
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="d-flex align-items-center gap-1.5 text-uppercase text-muted fw-bold small mb-2">
                                <i class="bi bi-building text-info"></i> Supplier Profile
                            </div>
                            <div class="fw-bold fs-6 text-dark mb-1">
                                <?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?>
                            </div>
                            <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
                                <?php if (!empty($purchase['seller_phone'])): ?>
                                    <span><i class="bi bi-telephone me-1"></i><?= e($purchase['seller_phone']) ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-shield-check me-1"></i>GSTIN: <?= e($purchase['seller_gst'] ?: 'Unregistered') ?></span>
                            </div>
                            <div class="small text-muted mt-1 text-truncate" title="<?= e($purchase['seller_address'] ?: '—') ?>">
                                <i class="bi bi-geo-alt me-1"></i><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ') ?: ($purchase['seller_address'] ?: '—')) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Invoice & Accounting References -->
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="d-flex align-items-center gap-1.5 text-uppercase text-muted fw-bold small mb-2">
                                <i class="bi bi-receipt-cutoff text-success"></i> Invoice &amp; Accounting References
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Purchase Date:</span> <strong><?= date('d M Y', strtotime($purchase['purchase_date'])) ?></strong>
                                <span class="mx-1 text-muted">·</span>
                                <span class="text-muted">Bill Ref:</span> <strong class="text-primary">#<?= e($purchase['purchase_number']) ?></strong>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Payment:</span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-1.5">Cash / Paid</span>
                                <span class="mx-1 text-muted">·</span>
                                <span class="text-muted">Bill Total:</span> <strong class="text-success"><?= formatCurrency((float)((float)$purchase['total_amount'])) ?></strong>
                            </div>
                            <div class="mt-2">
                                <label for="inputInvoiceNumber" class="form-label small text-muted mb-1">Vendor Invoice Number</label>
                                <input type="text" class="form-control form-control-sm font-monospace"
                                       id="inputInvoiceNumber" name="invoice_number" maxlength="100"
                                       value="<?= e($purchase['invoice_number'] ?? '') ?>"
                                       placeholder="Enter if missing">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8-Column Valuation Table (Image 2) -->
                <div class="table-responsive rounded-3 border">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="min-width: 110px;">Purchased Qty</th>
                                <th style="min-width: 90px;">Unit</th>
                                <th style="min-width: 140px;">Supplier Unit Cost</th>
                                <th style="min-width: 120px;">Tax Mode</th>
                                <th style="min-width: 110px;">HSN Code</th>
                                <th style="min-width: 110px;">GST Rate</th>
                                <th style="min-width: 120px;">Tax Amount</th>
                                <th style="min-width: 170px;" class="bg-success-subtle text-success fw-bold">Total Line Valuation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold fs-5 text-primary"><?= number_format($purchasedQty, 3) ?></td>
                                <td class="fw-semibold text-secondary"><?= e($activeItem['unit'] ?: 'Pieces') ?></td>
                                <td class="fw-semibold text-dark fs-6"><?= formatCurrency((float)($unitPrice)) ?></td>
                                <td>
                                    <span class="badge <?= ($activeItem['tax_treatment'] ?? '') === 'INCLUDED' ? 'bg-info-subtle text-info border-info-subtle' : 'bg-secondary-subtle text-secondary' ?> border">
                                        <?= e($taxModeLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="font-monospace fw-semibold"><?= e($activeHsn ?: '—') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= $gstRate > 0 ? number_format($gstRate, 2) . '% GST' : 'Exempted' ?></span>
                                </td>
                                <td class="fw-semibold text-dark"><?= formatCurrency((float)($taxAmount)) ?></td>
                                <td class="fw-bold fs-5 text-success" style="background-color: #e8f5e9;">
                                    <?= formatCurrency((float)($totalValuation)) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- LOGICAL CATEGORY HIERARCHY (SECTION 2 HIDDEN FROM UI AS REQUESTED)          -->
        <!-- ========================================================================= -->
        <div id="mappingHiddenCategoryFields" class="d-none">
            <select id="selectTier1" name="category_id">
                <option value="">-- Select Main Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"
                            data-code="<?= e($cat['code'] ?? '') ?>"
                            data-complementary="<?= (int)($cat['is_complementary'] ?? 0) ?>"
                            <?= ($activeCatId === (int)$cat['id']) || ($activeCatId === 0 && (int)$cat['id'] === (int)($categories[0]['id'] ?? 1)) ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="selectTier2" name="subcategory_id">
                <option value="">-- Select Sub Category --</option>
                <?php foreach ($subcategories as $sc): ?>
                    <option value="<?= (int)$sc['id'] ?>"
                            data-code="<?= e($sc['code'] ?? '') ?>"
                            <?= ($activeSubcatId === (int)$sc['id']) ? 'selected' : '' ?>>
                        <?= e($sc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="selectTier3" name="sub_subcategory_id">
                <option value="">-- Select Sub Sub Category --</option>
                <?php foreach ($subSubcategories as $ssc): ?>
                    <option value="<?= (int)$ssc['id'] ?>"
                            data-code="<?= e($ssc['code'] ?? '') ?>"
                            <?= ($activeSubSubcatId === (int)$ssc['id']) ? 'selected' : '' ?>>
                        <?= e($ssc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="inputTier1Code" name="category_code" value="<?= e($activeCatCode) ?>">
            <input type="text" id="inputTier2Code" name="subcategory_code" value="<?= e($activeSubcatCode) ?>">
            <input type="text" id="inputTier3Code" name="sub_subcategory_code" value="<?= e($activeSubSubcatCode) ?>">
            <input type="text" id="inputQrKey" name="qr_key" value="<?= e($initialQrKey) ?>">
            <span id="labelHierarchyPath"><?= e($activeCatCode ? ($activeCatCode . ' > ' . ($activeSubcatCode ?: '—') . ' > ' . ($activeSubSubcatCode ?: '—')) : '') ?></span>
            <div id="categoryComplementaryHelp"></div>
        </div>

        <!-- ========================================================================= -->
        <!-- LOGICAL PRODUCT ATTRIBUTES (SECTION 3 HIDDEN FROM UI AS REQUESTED)          -->
        <!-- ========================================================================= -->
        <div id="mappingHiddenAttributeFields" class="d-none">
            <input type="text" id="inputStyleCode" name="style_code" value="<?= e($activeStyleCode) ?>">
            <input type="text" id="inputStyleName" name="style_name" value="<?= e($activeStyleName) ?>">
            <input type="text" id="inputHsnCode" name="hsn_code" value="<?= e($activeHsn) ?>">

            <div id="containerColorPills">
                <?php foreach ($standardColors as $col): ?>
                    <?php $isColChecked = in_array(strtolower(trim($col)), array_map('strtolower', array_map('trim', $activeColors)), true); ?>
                    <input class="btn-check color-checkbox" type="checkbox" id="color_pill_<?= md5($col) ?>" name="colors[]" value="<?= e($col) ?>" <?= $isColChecked ? 'checked' : '' ?>>
                <?php endforeach; ?>
                <?php foreach ($activeColors as $ac): ?>
                    <?php if (!in_array(strtolower(trim($ac)), array_map('strtolower', array_map('trim', $standardColors)), true) && trim($ac) !== ''): ?>
                        <input class="btn-check color-checkbox" type="checkbox" id="color_pill_<?= md5($ac) ?>" name="colors[]" value="<?= e($ac) ?>" checked>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div id="containerSizePills">
                <?php foreach ($standardSizes as $size): ?>
                    <?php $isSizeChecked = in_array(strtolower(trim($size)), array_map('strtolower', array_map('trim', $selectedSizes)), true); ?>
                    <input class="btn-check size-checkbox" type="checkbox" id="size_pill_<?= md5($size) ?>" name="sizes[]" value="<?= e($size) ?>" <?= $isSizeChecked ? 'checked' : '' ?>>
                <?php endforeach; ?>
                <?php foreach ($selectedSizes as $ss): ?>
                    <?php if (!in_array(strtolower(trim($ss)), array_map('strtolower', array_map('trim', $standardSizes)), true) && trim($ss) !== ''): ?>
                        <input class="btn-check size-checkbox" type="checkbox" id="size_pill_<?= md5($ss) ?>" name="sizes[]" value="<?= e($ss) ?>" checked>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 2: MANUAL PRODUCT VARIANTS ENTRY                                  -->
        <!-- ========================================================================= -->
        <div class="alert alert-light border border-primary-subtle d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 py-2 px-3 shadow-sm rounded-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge bg-primary px-2 py-1"><i class="bi bi-receipt me-1"></i>Purchase Bill Base Tax</span>
                <div><strong>Base HSN:</strong> <span class="badge bg-light text-dark border font-monospace"><?= e($activeHsn ?: 'Not Specified') ?></span></div>
                <div><strong>Base GST:</strong> <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><?= number_format($gstRate, 2) ?>%</span></div>
            </div>
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>Product variants automatically inherit and show this bill's HSN and GST rate.
            </div>
        </div>
        <?php
        $dedupeCi = static function (array $vals): array {
            $seen = [];
            $out = [];
            foreach ($vals as $v) {
                $v = trim((string)$v);
                if ($v === '') continue;
                $k = mb_strtolower($v);
                if (isset($seen[$k])) continue;
                $seen[$k] = true;
                $out[] = $v;
            }
            return $out;
        };
        $allSizeNames = $dedupeCi(array_merge(
            array_map(static fn($s) => (string)($s['name'] ?? ''), $masterSizes ?? []),
            $standardSizes ?? ['Free Size']
        ));
        $allColorNames = $dedupeCi(array_merge(
            array_map(static fn($c) => (string)($c['name'] ?? ''), $masterColors ?? []),
            $standardColors ?? ['Default']
        ));

        $renderVariantRow = static function (int $index, array $v = []) use ($categories, $allSubcategories, $allSubSubcategories, $activeItem, $unitPrice, $defaultMrp, $defaultSp, $gstRate, $activeHsn, $activeCatId, $activeSubcatId, $activeSubSubcatId): void {
            $vSize = !empty($v['size']) ? trim((string)$v['size']) : trim((string)($activeItem['size'] ?? ''));
            $vColor = !empty($v['color']) ? trim((string)$v['color']) : trim((string)($activeItem['color'] ?? ''));
            $vSku = !empty($v['sku']) ? $v['sku'] : ($activeItem['product_code'] ?: ($activeItem['vendor_sku'] ?: 'TP-' . $activeItem['id']));
            $vQty = isset($v['allocated_qty']) ? (float)$v['allocated_qty'] : (isset($v['quantity']) ? (float)$v['quantity'] : (float)$activeItem['quantity']);
            $vRate = isset($v['purchase_rate']) ? (float)$v['purchase_rate'] : (isset($v['unit_price']) ? (float)$v['unit_price'] : $unitPrice);
            $vMrp = isset($v['mrp']) && (float)$v['mrp'] > 0 ? (float)$v['mrp'] : $defaultMrp;
            $vSp = isset($v['selling_price']) && (float)$v['selling_price'] > 0 ? (float)$v['selling_price'] : $defaultSp;
            $vGst = isset($v['gst_rate']) ? (float)$v['gst_rate'] : $gstRate;
            $vHsn = !empty($v['hsn_code']) ? $v['hsn_code'] : $activeHsn;
            $vCatId = !empty($v['third_party_category_id']) ? (int)$v['third_party_category_id'] : (!empty($v['category_id']) ? (int)$v['category_id'] : $activeCatId);
            if ($vCatId <= 0 && !empty($categories)) {
                $vCatId = (int)$categories[0]['id'];
            }
            $vSubcatId = !empty($v['third_party_subcategory_id']) ? (int)$v['third_party_subcategory_id'] : (!empty($v['subcategory_id']) ? (int)$v['subcategory_id'] : $activeSubcatId);
            $vSubSubcatId = !empty($v['third_party_sub_subcategory_id']) ? (int)$v['third_party_sub_subcategory_id'] : (!empty($v['sub_subcategory_id']) ? (int)$v['sub_subcategory_id'] : $activeSubSubcatId);
        ?>
        <tr class="variant-row" data-index="<?= $index ?>">
            <td class="text-center text-muted fw-bold row-num" style="width: 45px; min-width: 45px;"><?= $index + 1 ?></td>
            <td style="width: 280px; min-width: 265px;">
                <div class="input-group input-group-sm mb-1">
                    <select class="form-select form-select-sm var-category" name="variants[<?= $index ?>][category_id]" required>
                        <option value="">-- Main Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $vCatId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary btn-quick-add-tier1" title="Create Main Category"><i class="bi bi-plus-lg"></i></button>
                </div>
                <div class="input-group input-group-sm mb-1">
                    <select class="form-select form-select-sm var-subcategory" name="variants[<?= $index ?>][subcategory_id]">
                        <option value="">-- Sub Category --</option>
                        <?php foreach ($allSubcategories as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>" data-parent="<?= (int)$sc['category_id'] ?>" <?= $vSubcatId === (int)$sc['id'] ? 'selected' : '' ?> <?= (int)$sc['category_id'] !== $vCatId ? 'hidden' : '' ?>><?= e($sc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-info btn-quick-add-tier2" title="Create Sub Category"><i class="bi bi-plus-lg"></i></button>
                </div>
                <div class="input-group input-group-sm">
                    <select class="form-select form-select-sm var-subsubcategory" name="variants[<?= $index ?>][sub_subcategory_id]">
                        <option value="">-- Sub Sub Category --</option>
                        <?php foreach ($allSubSubcategories as $ssc): ?>
                            <option value="<?= (int)$ssc['id'] ?>" data-parent="<?= (int)$ssc['subcategory_id'] ?>" <?= $vSubSubcatId === (int)$ssc['id'] ? 'selected' : '' ?> <?= (int)$ssc['subcategory_id'] !== $vSubcatId ? 'hidden' : '' ?>><?= e($ssc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-success btn-quick-add-tier3" title="Create Sub Sub Category"><i class="bi bi-plus-lg"></i></button>
                </div>
            </td>
            <td style="width: 155px; min-width: 145px;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm var-size" name="variants[<?= $index ?>][size]" value="<?= e($vSize) ?>" placeholder="Type size..." required autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary btn-quick-add-size" title="Create New Size"><i class="bi bi-plus-lg"></i></button>
                </div>
            </td>
            <td style="width: 165px; min-width: 155px;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm var-color" name="variants[<?= $index ?>][color]" value="<?= e($vColor) ?>" placeholder="Type color..." required autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary btn-quick-add-color" title="Create New Color"><i class="bi bi-plus-lg"></i></button>
                </div>
            </td>
            <td style="width: 95px; min-width: 90px;">
                <input class="form-control form-control-sm text-end fw-bold text-primary var-qty" name="variants[<?= $index ?>][allocated_qty]" type="text" inputmode="numeric" value="<?= max(1, (int)$vQty) ?>" required placeholder="Qty" style="min-width: 80px;" autocomplete="off">
            </td>
            <td style="width: 130px; min-width: 125px;">
                <input class="form-control form-control-sm text-end var-rate" name="variants[<?= $index ?>][purchase_rate]" type="text" inputmode="decimal" value="<?= number_format($vRate, 2, '.', '') ?>" required placeholder="Rate" style="min-width: 95px;" autocomplete="off">
            </td>
            <td style="width: 115px; min-width: 110px;">
                <input class="form-control form-control-sm text-end fw-semibold var-mrp" name="variants[<?= $index ?>][mrp]" type="text" inputmode="decimal" value="<?= number_format($vMrp, 2, '.', '') ?>" required placeholder="MRP" style="min-width: 95px;" autocomplete="off">
            </td>
            <td style="width: 115px; min-width: 110px;">
                <input class="form-control form-control-sm text-end fw-bold text-dark var-sp" name="variants[<?= $index ?>][selling_price]" type="text" inputmode="decimal" value="<?= number_format($vSp, 2, '.', '') ?>" required placeholder="Sale" style="min-width: 95px;" autocomplete="off">
            </td>
            <td style="width: 90px; min-width: 85px;">
                <input class="form-control form-control-sm text-end var-gst" name="variants[<?= $index ?>][gst_rate]" type="text" inputmode="decimal" value="<?= number_format($vGst, 2, '.', '') ?>" required placeholder="GST %" style="min-width: 75px;" autocomplete="off">
            </td>
            <td style="width: 115px; min-width: 110px;">
                <input class="form-control form-control-sm font-monospace var-hsn" name="variants[<?= $index ?>][hsn_code]" value="<?= e($vHsn) ?>" maxlength="20" required placeholder="HSN" style="min-width: 95px;">
            </td>
            <td class="text-center" style="width: 50px; min-width: 50px;">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove variant"><i class="bi bi-x-lg"></i></button>
            </td>
        </tr>
        <?php }; ?>

        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">2</span>
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Add Variants to Mapping</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($activeItem['is_mapped'])): ?>
                        <button type="button" class="btn btn-sm btn-warning fw-semibold" id="btnUnlockMappingEdit" title="Unlock product details for editing">
                            <i class="bi bi-pencil-square me-1"></i>Edit
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="border rounded-3 p-3 bg-white" id="billVariantGeneratorCard">
                    <?php $isRollItem = strtoupper((string)($activeItem['item_type'] ?? '')) === 'FABRIC_ROLL'; ?>
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1"><?= $isRollItem ? 'Roll' : 'Product' ?></span>
                            <strong class="text-dark"><?= e($activeItem['item_name']) ?></strong>
                        </div>
                        <span class="badge <?= $isRollItem ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                            <i class="bi <?= $isRollItem ? 'bi-layers-half' : 'bi-tag' ?> me-1"></i><?= $isRollItem ? 'Fabric Roll' : 'Third-Party Product' ?>
                        </span>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Product / Style Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="billGenProductName" value="<?= e($activeItem['item_name']) ?>" placeholder="e.g. Shirt, Pant..." readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Unit</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="<?= e($activeItem['unit'] ?: 'PCS') ?>" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Category <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="billGenCategory">
                                <option value="">-- Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)($activeItem['mapped_third_party_category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Sub Category</label>
                            <select class="form-select form-select-sm" id="billGenSubcategory">
                                <option value="">-- Sub Category --</option>
                                <?php foreach ($allSubcategories as $sc): ?>
                                    <option value="<?= (int)$sc['id'] ?>" data-parent="<?= (int)$sc['category_id'] ?>" <?= ((int)($activeItem['mapped_third_party_subcategory_id'] ?? 0) === (int)$sc['id']) ? 'selected' : '' ?> hidden><?= e($sc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Sub Sub Category</label>
                            <select class="form-select form-select-sm" id="billGenSubSubcategory">
                                <option value="">-- Sub Sub --</option>
                                <?php foreach ($allSubSubcategories as $ssc): ?>
                                    <option value="<?= (int)$ssc['id'] ?>" data-parent="<?= (int)$ssc['subcategory_id'] ?>" <?= ((int)($activeItem['mapped_third_party_sub_subcategory_id'] ?? 0) === (int)$ssc['id']) ? 'selected' : '' ?> hidden><?= e($ssc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">HSN Code</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm font-monospace" id="billGenHsn" value="<?= e($activeHsn ?? '') ?>" placeholder="Type or select HSN (e.g. 6109)..." maxlength="20" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnBillHsnDropdown" title="Browse all HSN codes" tabindex="-1">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">GST %</label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm" id="billGenGst" value="<?= number_format((float)($gstRate ?? 5), 2, '.', '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">GST Mode</label>
                            <?php $initGstMode = strtoupper((string)($activeItem['tax_treatment'] ?? 'EXCLUDED')) === 'INCLUDED' ? 'INCLUDED' : 'EXCLUDED'; ?>
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <input type="radio" class="btn-check bill-gen-gst-mode" name="bill_gen_gst_mode" id="billGstInc" value="INCLUDED" autocomplete="off" <?= $initGstMode === 'INCLUDED' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary fw-semibold" for="billGstInc">GST Included</label>
                                <input type="radio" class="btn-check bill-gen-gst-mode" name="bill_gen_gst_mode" id="billGstExc" value="EXCLUDED" autocomplete="off" <?= $initGstMode === 'EXCLUDED' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary fw-semibold" for="billGstExc">GST Excluded</label>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" id="btnGenerateSaveBillVariants">
                                <i class="bi bi-lightning-charge me-1"></i>Generate &amp; Save
                            </button>
                        </div>

                        <!-- Category-loaded Attributes (hidden until Category is selected) -->
                        <div class="col-md-6 d-none bill-gen-attributes-col">
                            <label class="form-label small fw-semibold mb-1">Sizes <span class="text-muted fw-normal">(auto-loaded from Category)</span></label>
                            <div class="d-flex flex-wrap gap-1 mb-1" id="billGenSizeChips"></div>
                            <input type="text" class="form-control form-control-sm" id="billGenSizesInput" placeholder="Select chips above or type comma-separated">
                        </div>
                        <div class="col-md-6 d-none bill-gen-attributes-col">
                            <label class="form-label small fw-semibold mb-1">Colors <span class="text-muted fw-normal">(auto-loaded from Category)</span></label>
                            <div class="d-flex flex-wrap gap-1 mb-1" id="billGenColorChips"></div>
                            <input type="text" class="form-control form-control-sm" id="billGenColorsInput" placeholder="Select chips above or type comma-separated">
                        </div>

                        <!-- Per-Attribute Rate Table (appears when Sizes / Colors are selected) -->
                        <div class="col-12 mt-2 d-none" id="billGenVariantRatesWrap">
                            <div class="border rounded bg-light-subtle p-2">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <span class="small fw-bold text-dark"><i class="bi bi-sliders me-1 text-primary"></i>Selected Attributes — Qty &amp; Pricing</span>
                                    <span class="text-muted" style="font-size: 11px;">Purchased Qty: <strong><?= number_format($purchasedQty, 0) ?></strong> · Default Rate: <strong>₹<?= number_format($baseRate, 2) ?></strong></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white align-middle mb-0" style="font-size: 12px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Attribute (Size / Color)</th>
                                                <th style="width: 110px;">Qty *</th>
                                                <th style="width: 140px;">Purchase Rate *</th>
                                                <th style="width: 140px;">MRP (₹) *</th>
                                                <th style="width: 140px;">Selling Price (₹) *</th>
                                            </tr>
                                        </thead>
                                        <tbody id="billGenVariantRatesBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden Variant Payload Table (populated only when user clicks Generate & Save — no auto-loaded rows) -->
                <div class="d-none">
                    <table id="tableVariantRows">
                        <tbody id="tbodyVariantRows">
                            <?php $renderVariantRow(0, ['allocated_qty' => 1]); ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="small" id="labelAllocationStatus"></div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- ACTION FOOTER BAR                                                         -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4 bg-white sticky-bottom" style="z-index: 10;">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="small text-muted">
                    <i class="bi bi-check2-circle text-success me-1"></i>Save &amp; Complete Mapping to generate stock and QR label codes.
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary px-3">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <?php if (!empty($activeItem['is_mapped'])): ?>
                        <button type="button" class="btn btn-outline-danger px-3 fw-bold" id="btnUnmapItem" onclick="unmapActiveItem()">
                            <i class="bi bi-trash me-1"></i>Delete Mapping (Unmap)
                        </button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSaveComplete">
                        <i class="bi bi-check-circle-fill me-1"></i>Save &amp; Complete Mapping
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- SECTION 3: SAVED PRODUCT VARIANTS & QR GENERATIONS (IN-PANEL VIEW)       -->
    <!-- ========================================================================= -->
    <?php 
    $pendingQrCount = 0;
    $unstockedQrIds = [];
    foreach ($savedVariants as $sv) {
        if (empty($sv['qr_generation_id'])) {
            $pendingQrCount++;
        } elseif (empty($sv['stock_added'])) {
            $unstockedQrIds[(int)$sv['qr_generation_id']] = true;
        }
    }
    $unstockedQrIds = array_keys($unstockedQrIds);
    ?>
    <div class="card border shadow-sm mb-4" id="sectionSavedVariantsQr">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success text-white rounded-pill px-2.5 py-1"><i class="bi bi-qr-code"></i></span>
                <h5 class="mb-0 fw-bold text-dark">Saved Product Variants &amp; QR Generations</h5>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (!empty($unstockedQrIds)): ?>
                    <div class="input-group input-group-sm" style="width: auto; min-width: 240px;">
                        <span class="input-group-text bg-light"><i class="bi bi-building me-1"></i>Concern / WH</span>
                        <select class="form-select" id="bulkInwardWarehouseSelect">
                            <?php foreach (($warehouses ?? []) as $wh): ?>
                                <option value="<?= (int)$wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['concern_name']) ? ' (' . e($wh['concern_name']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnInwardSelectedQr" disabled>
                        <i class="bi bi-box-arrow-in-down me-1"></i>Add Selected to Stock (<span id="selectedQrCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-semibold" id="btnInwardAllQr">
                        <i class="bi bi-box-seam me-1"></i>Add All Generated to Stock (<?= count($unstockedQrIds) ?>)
                    </button>
                <?php endif; ?>
                <span class="badge bg-secondary"><?= count($savedVariants) ?> variant<?= count($savedVariants) === 1 ? '' : 's' ?></span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                    <tr>
                        <th style="width: 38px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="chkSelectAllQr" title="Select all generated unstocked QRs" <?= empty($unstockedQrIds) ? 'disabled' : '' ?>>
                        </th>
                        <th>Product / Variant</th>
                        <th>Category</th>
                        <th>HSN</th>
                        <th>Qty</th>
                        <th>Purchase Rate</th>
                        <th>MRP / Sale</th>
                        <th>QR / Stock</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($savedVariants)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-info-circle me-1"></i>No variants saved yet. Save mapping above to record variants and enable QR generation.
                            </td>
                        </tr>
                    <?php else: foreach ($savedVariants as $itemVar):
                        $canInwardRow = !empty($itemVar['qr_generation_id']) && empty($itemVar['stock_added']);
                    ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($canInwardRow): ?>
                                    <input type="checkbox" class="form-check-input chk-qr-inward" value="<?= (int)$itemVar['qr_generation_id'] ?>" title="Select to inward to stock">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($activeItem['item_name']) ?></strong>
                                <div class="small text-muted">
                                    <?= e(trim(($itemVar['color'] ?? '') . ' ' . ($itemVar['size'] ?? ''))) ?>
                                </div>
                            </td>
                            <td>
                                <?= e($itemVar['category_name'] ?? '—') ?>
                                <?php if (!empty($itemVar['subcategory_name'])): ?>
                                    <div class="small text-muted"><?= e($itemVar['subcategory_name']) ?><?= !empty($itemVar['sub_subcategory_name']) ? ' / ' . e($itemVar['sub_subcategory_name']) : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace"><?= e($itemVar['hsn_code'] ?: $activeHsn) ?></td>
                            <td class="fw-bold"><?= number_format((float)($itemVar['allocated_qty'] ?? $itemVar['quantity']), 3) ?> <?= e($activeItem['unit'] ?: 'PCS') ?></td>
                            <td><?= formatCurrency((float)((float)($itemVar['purchase_rate'] ?? $itemVar['unit_price']))) ?></td>
                            <td class="text-success fw-semibold"><?= formatCurrency((float)((float)$itemVar['mrp'])) ?> / <?= formatCurrency((float)((float)$itemVar['selling_price'])) ?></td>
                            <td>
                                <?php if (!empty($itemVar['batch_code'])): ?>
                                    <button type="button" class="badge bg-dark border-0 cursor-pointer p-2" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$itemVar['qr_generation_id'] ?>" title="Click to view QR codes in this panel">
                                        <i class="bi bi-qr-code me-1"></i><?= e($itemVar['batch_code']) ?>
                                    </button>
                                    <?php if (!empty($itemVar['stock_added'])): ?>
                                        <span class="badge bg-success ms-1">Stocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark ms-1">Not stocked</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No QR</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (empty($itemVar['qr_generation_id'])): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/bill/' . (!empty($itemVar['id']) ? 'variant/' . (int)$itemVar['id'] : 'item/' . (int)$activeItem['id']) . '/generate-qr') ?>" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <button class="btn btn-sm btn-outline-info" type="submit">
                                            <i class="bi bi-qr-code me-1"></i>Generate QR
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalQrBatch_<?= (int)$itemVar['qr_generation_id'] ?>" title="View QR in this panel">
                                            <i class="bi bi-eye me-1"></i>View QR
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-print-tsc-batch" data-generation-id="<?= (int)$itemVar['qr_generation_id'] ?>" title="Print Labels">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>Purchase bill <strong><?= e($purchase['purchase_number']) ?></strong> is already entered in Purchases. Generate QR labels and print directly from this panel.
            </div>
            <?php if (!empty($savedVariants) && $pendingQrCount > 0): ?>
                <form method="POST" action="<?= url('purchases/mapping/bill/' . (int)$activeItem['id'] . '/generate-all-qr') ?>" id="formGenerateAllVariantQr">
                    <?= CSRF::field() ?>
                    <button class="btn btn-sm btn-outline-info fw-semibold" type="button" id="btnGenerateAllVariantQr">
                        <i class="bi bi-qr-code me-1"></i>Generate QR for All Pending Variants (<?= $pendingQrCount ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add a new purchased product line without leaving the bill mapping flow. -->
<div class="modal fade" id="modalAddBillProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Add Product to Purchase Bill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="newBillItemName" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newBillItemName" required maxlength="255">
                    </div>
                    <div class="col-md-3">
                        <label for="newBillQuantity" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control" id="newBillQuantity" required>
                    </div>
                    <div class="col-md-3">
                        <label for="newBillUnit" class="form-label fw-semibold">Unit</label>
                        <input type="text" class="form-control" id="newBillUnit" value="PCS" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label for="newBillUnitPrice" class="form-label fw-semibold">Purchase Rate (₹) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control" id="newBillUnitPrice" required>
                    </div>
                    <div class="col-md-3">
                        <label for="newBillGstRate" class="form-label fw-semibold">GST Rate (%)</label>
                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control" id="newBillGstRate" value="5">
                    </div>
                    <div class="col-md-4">
                        <label for="newBillMrp" class="form-label fw-semibold">MRP (₹) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control" id="newBillMrp" required>
                    </div>
                    <div class="col-md-4">
                        <label for="newBillSellingPrice" class="form-label fw-semibold">Selling Price (₹) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" autocomplete="off" class="form-control" id="newBillSellingPrice" required>
                    </div>
                    <div class="col-md-4">
                        <label for="newBillHsn" class="form-label fw-semibold">HSN Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="newBillHsn" maxlength="20" required>
                    </div>
                    <div class="col-md-6">
                        <label for="newBillColor" class="form-label fw-semibold">Color</label>
                        <input type="text" class="form-control" id="newBillColor" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label for="newBillSize" class="form-label fw-semibold">Size</label>
                        <input type="text" class="form-control" id="newBillSize" maxlength="100">
                    </div>
                </div>
                <div id="newBillProductError" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnCreateBillProduct">
                    <i class="bi bi-plus-circle me-1"></i>Add Product
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODALS FOR QUICK ADDING 3-TIER CATEGORIES (AJAX)                          -->
<!-- ========================================================================= -->

<!-- Quick Add Tier 1 Category Modal -->
<div class="modal fade" id="modalQuickAddCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>New Main Category (Tier 1)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="modalTier1Name" placeholder="e.g. Non Feeding, Menswear">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Short Code (2-4 Characters)</label>
                    <input type="text" class="form-control text-uppercase font-monospace" id="modalTier1Code" placeholder="e.g. NF, MW">
                    <small class="text-muted">Used for the QR Key and SKU prefix.</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="modalTier1Complementary" value="1">
                    <label class="form-check-label fw-semibold" for="modalTier1Complementary">
                        <i class="bi bi-gift text-info me-1"></i>Set as complementary category by default
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSubmitModalTier1">Add Main Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Tier 2 Subcategory Modal -->
<div class="modal fade" id="modalQuickAddSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>New Sub Category (Tier 2)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="modalTier2Name" placeholder="e.g. Full Length, Shirt, Trousers">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Short Code (2-4 Characters)</label>
                    <input type="text" class="form-control text-uppercase font-monospace" id="modalTier2Code" placeholder="e.g. FL, SH">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info text-white" id="btnSubmitModalTier2">Add Sub Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Tier 3 Sub-Subcategory Modal -->
<div class="modal fade" id="modalQuickAddSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>New Sub Sub Category (Tier 3)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="modalTier3Name" placeholder="e.g. Normal Sleeve, Slim Fit, Party Wear">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Short Code (2-4 Characters)</label>
                    <input type="text" class="form-control text-uppercase font-monospace" id="modalTier3Code" placeholder="e.g. NS, SF">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSubmitModalTier3">Add Sub Sub Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Size Modal -->
<div class="modal fade" id="modalQuickAddSize" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-rulers me-2"></i>New Size</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Size Code / Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm text-uppercase" id="modalNewSizeCode" placeholder="e.g. XL, 32, Free Size">
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSubmitModalSize">Add Size</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Color Modal -->
<div class="modal fade" id="modalQuickAddColor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-palette me-2"></i>New Color</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Color Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm text-uppercase" id="modalNewColorName" placeholder="e.g. Navy Blue, Olive">
                </div>
                <div>
                    <label class="form-label small fw-semibold">Color Swatch</label>
                    <input type="color" class="form-control form-control-color form-control-sm" id="modalNewColorHex" value="#2563eb">
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSubmitModalColor">Add Color</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QR CODE MODALS FOR GENERATED BATCHES (IN-PANEL VIEW)                      -->
<!-- ========================================================================= -->
<?php if (!empty($savedVariants)): foreach ($savedVariants as $itemVar): if (!empty($itemVar['qr_generation_id'])): 
    $batchQrItems = $qrItemMap[(int)$itemVar['qr_generation_id']] ?? [];
?>
<div class="modal fade" id="modalQrBatch_<?= (int)$itemVar['qr_generation_id'] ?>" tabindex="-1" aria-labelledby="modalQrBatchLabel_<?= (int)$itemVar['qr_generation_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalQrBatchLabel_<?= (int)$itemVar['qr_generation_id'] ?>">
                    <i class="bi bi-qr-code me-2"></i>QR Batch: <?= e($itemVar['batch_code']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="text-muted small">Product</div>
                                <div class="fw-bold fs-6"><?= e($activeItem['item_name']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Variant</div>
                                <div class="fw-semibold"><?= e(trim(($itemVar['color'] ?? '') . ' ' . ($itemVar['size'] ?? ''))) ?></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">Total Qty</div>
                                <div class="fw-bold"><?= number_format((float)($itemVar['allocated_qty'] ?? $itemVar['quantity']), 3) ?> <?= e($activeItem['unit'] ?: 'PCS') ?></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">MRP / Selling Price</div>
                                <div class="fw-bold text-success"><?= formatCurrency((float)((float)$itemVar['mrp'])) ?> / <?= formatCurrency((float)((float)$itemVar['selling_price'])) ?></div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">Inventory Status</div>
                                <div id="modalBillStockStatus_<?= (int)$itemVar['qr_generation_id'] ?>">
                                    <?php if (!empty($itemVar['stock_added'])): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Not stocked</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-upc-scan me-1 text-primary"></i>Generated Labels (<?= count($batchQrItems) ?>)</h6>
                <div class="row g-3">
                    <?php if (empty($batchQrItems)): ?>
                        <div class="col-12 text-center text-muted py-3">No QR label records found for this batch.</div>
                    <?php else: foreach ($batchQrItems as $q): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="border rounded p-3 text-center bg-white shadow-sm h-100 d-flex flex-column justify-content-between bill-qr-label-card"
                                 data-qr-code="<?= e($q['qr_code']) ?>"
                                 data-vid="<?= !empty($itemVar['variant_id']) ? 'VID: #' . (int)$itemVar['variant_id'] : '' ?>"
                                 data-product-name="<?= e($activeItem['item_name']) ?>"
                                 data-attributes="<?= e(trim(implode(' / ', array_filter([$itemVar['color'] ?? '', $itemVar['size'] ?? ''])))) ?>"
                                 data-mrp="<?= e(formatCurrency((float)($itemVar['mrp'] ?? 0))) ?>"
                                 data-selling-price="<?= e(formatCurrency((float)($itemVar['selling_price'] ?? 0))) ?>">
                                <div class="small fw-bold text-truncate text-dark mb-1"><?= e($activeItem['item_name']) ?></div>
                                <div class="small text-muted mb-2"><?= e(trim(($itemVar['color'] ?? '') . ' ' . ($itemVar['size'] ?? ''))) ?></div>
                                <div class="d-flex justify-content-center my-2">
                                    <div class="panel-qr-canvas p-1 bg-white border rounded" data-code="<?= e($q['qr_code']) ?>"></div>
                                </div>
                                <div class="font-monospace fw-bold text-primary small mt-1"><?= e($q['qr_code']) ?></div>
                                <div class="small text-muted mt-1"><?= formatCurrency((float)((float)$itemVar['selling_price'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (empty($itemVar['stock_added'])): ?>
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-light"><i class="bi bi-building me-1"></i>Warehouse:</span>
                            <select class="form-select" id="billWhSelect_<?= (int)$itemVar['qr_generation_id'] ?>">
                                <?php foreach (($warehouses ?? []) as $wh): ?>
                                    <option value="<?= (int)$wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['concern_name']) ? ' (' . e($wh['concern_name']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success btn-add-inventory-bill"
                                data-generation-id="<?= (int)$itemVar['qr_generation_id'] ?>"
                                data-purchase-id="<?= (int)$purchase['id'] ?>">
                            <i class="bi bi-box-seam me-1"></i>Add to Inventory
                        </button>
                    <?php else: ?>
                        <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Stock posted to warehouse inventory</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm btn-print-tsc-batch" data-generation-id="<?= (int)$itemVar['qr_generation_id'] ?>">
                        <i class="bi bi-printer me-1"></i>Print Labels
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; endforeach; endif; ?>

<!-- Modern UI Alert Modal (replaces native browser showModernAlert()) -->
<div class="modal fade" id="mappingModernAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4 text-center">
                <div id="mappingModernAlertIconWrap" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 58px; height: 58px; background: #fef3c7; color: #d97706;">
                    <i id="mappingModernAlertIcon" class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="mappingModernAlertTitle">Notice</h5>
                <p class="text-secondary small mb-3" id="mappingModernAlertMsg"></p>
                <button type="button" class="btn btn-primary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('toggleBaselineDetails');
    const body = document.getElementById('baselineDetailsBody');
    if (btn && body) {
        btn.addEventListener('click', function () {
            const isHidden = body.classList.toggle('d-none');
            this.innerHTML = isHidden
                ? '<i class="bi bi-arrows-expand"></i> Expand'
                : '<i class="bi bi-arrows-collapse"></i> Compact';
        });
    }
})();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- ========================================================================= -->
<!-- CLIENT-SIDE ENGINE (JAVASCRIPT)                                           -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const BASE_URL = '<?= rtrim(url(), '/') ?>';
    const purchasedQty = <?= json_encode($purchasedQty) ?>;
    const supplierUnitCost = <?= json_encode($unitPrice) ?>;

    function showModernAlert(message, title = 'Notice', type = 'warning') {
        const modalEl = document.getElementById('mappingModernAlertModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        const iconWrap = document.getElementById('mappingModernAlertIconWrap');
        const iconEl = document.getElementById('mappingModernAlertIcon');
        const titleEl = document.getElementById('mappingModernAlertTitle');
        const msgEl = document.getElementById('mappingModernAlertMsg');
        const styles = {
            success: { bg: '#dcfce7', color: '#16a34a', icon: 'bi bi-check-circle-fill fs-3' },
            danger:  { bg: '#fee2e2', color: '#dc2626', icon: 'bi bi-x-circle-fill fs-3' },
            info:    { bg: '#dbeafe', color: '#2563eb', icon: 'bi bi-info-circle-fill fs-3' },
            warning: { bg: '#fef3c7', color: '#d97706', icon: 'bi bi-exclamation-triangle-fill fs-3' },
        };
        const st = styles[type] || styles.warning;
        if (iconWrap) { iconWrap.style.background = st.bg; iconWrap.style.color = st.color; }
        if (iconEl) iconEl.className = st.icon;
        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function showModernConfirmDialog(title, message, onConfirm, confirmText = 'Confirm', btnClass = 'btn-primary') {
        if (typeof window.showModernConfirm === 'function') {
            window.showModernConfirm({
                title: title,
                message: message,
                confirmText: confirmText,
                btnClass: btnClass,
                iconClass: 'bi bi-question-circle-fill text-primary',
                onConfirm: onConfirm
            });
        } else {
            onConfirm();
        }
    }

    const tbodyVariantRows = document.getElementById('tbodyVariantRows');
    const labelAllocationStatus = document.getElementById('labelAllocationStatus');
    const btnAddOneVariant = document.getElementById('btnAddOneVariant');
    const btnAddVariantRows = document.getElementById('btnAddVariantRows');
    const variantRowsToAdd = document.getElementById('variantRowsToAdd');

    const batchInputMrp = document.getElementById('batchInputMrp');
    const batchInputSp = document.getElementById('batchInputSp');
    const btnReplicatePricing = document.getElementById('btnReplicatePricing');
    const btnHeaderAutoDistribute = document.getElementById('btnHeaderAutoDistribute');

    // 1. Dynamic Row Management
    function getNextIndex() {
        let maxIndex = -1;
        if (!tbodyVariantRows) return 0;
        tbodyVariantRows.querySelectorAll('.variant-row').forEach(tr => {
            const idx = parseInt(tr.getAttribute('data-index') || '-1', 10);
            if (idx > maxIndex) maxIndex = idx;
        });
        return maxIndex + 1;
    }

    function renumberRows() {
        if (!tbodyVariantRows) return;
        tbodyVariantRows.querySelectorAll('.variant-row').forEach((tr, i) => {
            const numEl = tr.querySelector('.row-num');
            if (numEl) numEl.textContent = String(i + 1);
        });
    }

    function addVariantRows(count) {
        if (!tbodyVariantRows) return;
        const firstRow = tbodyVariantRows.querySelector('.variant-row');
        if (!firstRow) return;

        for (let i = 0; i < count; i++) {
            const nextIdx = getNextIndex();
            const clone = firstRow.cloneNode(true);
            clone.setAttribute('data-index', String(nextIdx));

            // Replace input names
            clone.querySelectorAll('input, select').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/variants\[\d+\]/, 'variants[' + nextIdx + ']');
                }
            });

            // Adjust SKU for cloned row if based on default format
            const skuInput = clone.querySelector('.var-sku');
            if (skuInput && skuInput.value) {
                const parts = skuInput.value.split('-');
                if (parts.length > 1 && !isNaN(parseInt(parts[parts.length - 1], 10))) {
                    parts[parts.length - 1] = String(nextIdx + 1).padStart(2, '0');
                    skuInput.value = parts.join('-');
                } else {
                    skuInput.value = skuInput.value + '-' + (nextIdx + 1);
                }
            }

            // Reset quantity to 1 for new rows
            const qtyInput = clone.querySelector('.var-qty');
            if (qtyInput) qtyInput.value = '1';

            // Ensure HSN and GST on cloned rows are explicitly inherited from this purchase bill
            const hsnInput = clone.querySelector('.var-hsn');
            if (hsnInput) hsnInput.value = <?= json_encode($activeHsn) ?>;
            const gstInput = clone.querySelector('.var-gst');
            if (gstInput) gstInput.value = <?= json_encode(number_format($gstRate, 2, '.', '')) ?>;

            tbodyVariantRows.appendChild(clone);
        }

        renumberRows();
        updateAllocationStatus();
    }

    if (btnAddOneVariant) {
        btnAddOneVariant.addEventListener('click', function () {
            addVariantRows(1);
        });
    }

    if (btnAddVariantRows && variantRowsToAdd) {
        btnAddVariantRows.addEventListener('click', function () {
            const count = Math.max(1, Math.min(20, parseInt(variantRowsToAdd.value, 10) || 1));
            addVariantRows(count);
        });
    }

    // Row Removal
    if (tbodyVariantRows) {
        tbodyVariantRows.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-remove-row');
            if (!btn) return;

            const allRows = tbodyVariantRows.querySelectorAll('.variant-row');
            if (allRows.length <= 1) {
                showModernAlert('At least one variant row is required.');
                return;
            }

            btn.closest('.variant-row').remove();
            renumberRows();
            updateAllocationStatus();
        });

        // 2. Cascade Hierarchy Dropdowns per Row
        tbodyVariantRows.addEventListener('change', function (e) {
            if (e.target.classList.contains('var-category')) {
                const row = e.target.closest('.variant-row');
                const catId = e.target.value;
                const subcatSelect = row.querySelector('.var-subcategory');
                const subSubcatSelect = row.querySelector('.var-subsubcategory');

                Array.from(subcatSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = opt.dataset.parent !== catId;
                    if (opt.hidden && opt.selected) opt.selected = false;
                });
                subcatSelect.value = '';

                Array.from(subSubcatSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = true;
                    if (opt.selected) opt.selected = false;
                });
                subSubcatSelect.value = '';
            } else if (e.target.classList.contains('var-subcategory')) {
                const row = e.target.closest('.variant-row');
                const subcatId = e.target.value;
                const subSubcatSelect = row.querySelector('.var-subsubcategory');

                Array.from(subSubcatSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = opt.dataset.parent !== subcatId;
                    if (opt.hidden && opt.selected) opt.selected = false;
                });
                subSubcatSelect.value = '';
            }
        });

        tbodyVariantRows.addEventListener('input', function (e) {
            if (e.target.classList.contains('var-qty')) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                updateAllocationStatus();
            } else if (e.target.classList.contains('var-rate') || e.target.classList.contains('var-mrp') || e.target.classList.contains('var-sp') || e.target.classList.contains('var-gst')) {
                e.target.value = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            }
        });

        [batchInputMrp, batchInputSp].forEach(input => {
            if (input) {
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
                });
            }
        });

        if (variantRowsToAdd) {
            variantRowsToAdd.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }

        ['newBillQuantity', 'newBillUnitPrice', 'newBillGstRate', 'newBillMrp', 'newBillSellingPrice'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
                });
            }
        });
    }

    // 3. Live Quantity Allocation Calculation
    function updateAllocationStatus() {
        if (!tbodyVariantRows || !labelAllocationStatus) return;
        let totalAllocated = 0;
        tbodyVariantRows.querySelectorAll('.var-qty').forEach(inp => {
            totalAllocated += (parseFloat(inp.value) || 0);
        });

        const targetQty = Math.round(purchasedQty);
        const diff = totalAllocated - targetQty;
        const isMatch = (Math.abs(diff) < 0.001);

        if (isMatch) {
            labelAllocationStatus.innerHTML = `
                <span class="badge bg-success text-white py-1.5 px-3">
                    <i class="bi bi-check-circle-fill me-1.5"></i>Allocated: ${totalAllocated} / ${targetQty} Pieces (Exact Match)
                </span>
            `;
        } else if (diff > 0) {
            labelAllocationStatus.innerHTML = `
                <span class="badge bg-info text-dark py-1.5 px-3">
                    <i class="bi bi-arrow-up-circle-fill me-1.5"></i>Allocated: ${totalAllocated} / ${targetQty} Pieces (+${diff} extra — will auto-update Purchased List)
                </span>
            `;
        } else {
            labelAllocationStatus.innerHTML = `
                <span class="badge bg-warning text-dark py-1.5 px-3">
                    <i class="bi bi-exclamation-triangle-fill me-1.5"></i>Allocated: ${totalAllocated} / ${targetQty} Pieces (${diff} variance)
                </span>
            `;
        }
    }

    // 4. Auto-Distribute Quantities
    function autoDistributeQuantities() {
        if (!tbodyVariantRows) return;
        const qtyInputs = Array.from(tbodyVariantRows.querySelectorAll('.var-qty'));
        const count = qtyInputs.length;
        if (count === 0) return;

        const totalInt = Math.round(purchasedQty);
        const base = Math.floor(totalInt / count);
        const remainder = totalInt % count;

        qtyInputs.forEach((inp, idx) => {
            inp.value = String(base + (idx < remainder ? 1 : 0));
        });

        updateAllocationStatus();
    }

    if (btnHeaderAutoDistribute) {
        btnHeaderAutoDistribute.addEventListener('click', autoDistributeQuantities);
    }

    // 4b. Overall Batch Apply (Category Hierarchy + Size + Color)
    const batchInputSize = document.getElementById('batchInputSize');
    const batchSelectColor = document.getElementById('batchSelectColor');
    const batchSelectCategory = document.getElementById('batchSelectCategory');
    const batchSelectSubcategory = document.getElementById('batchSelectSubcategory');
    const batchSelectSubSubcategory = document.getElementById('batchSelectSubSubcategory');
    const btnApplyAllAttributes = document.getElementById('btnApplyAllAttributes');

    if (batchSelectCategory && batchSelectSubcategory && batchSelectSubSubcategory) {
        batchSelectCategory.addEventListener('change', function () {
            const catId = this.value;
            Array.from(batchSelectSubcategory.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = opt.dataset.parent !== catId;
                if (opt.hidden && opt.selected) opt.selected = false;
            });
            batchSelectSubcategory.value = '';
            Array.from(batchSelectSubSubcategory.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = true;
                if (opt.selected) opt.selected = false;
            });
            batchSelectSubSubcategory.value = '';
        });

        batchSelectSubcategory.addEventListener('change', function () {
            const subId = this.value;
            Array.from(batchSelectSubSubcategory.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = opt.dataset.parent !== subId;
                if (opt.hidden && opt.selected) opt.selected = false;
            });
            batchSelectSubSubcategory.value = '';
        });
    }

    if (btnApplyAllAttributes && tbodyVariantRows) {
        btnApplyAllAttributes.addEventListener('click', function () {
            const sizeVal = (batchInputSize?.value || '').trim();
            const colorVal = (batchSelectColor?.value || '').trim();
            const catVal = batchSelectCategory?.value || '';
            const subVal = batchSelectSubcategory?.value || '';
            const subSubVal = batchSelectSubSubcategory?.value || '';

            if (!sizeVal && !colorVal && !catVal && !subVal && !subSubVal) {
                showModernAlert('Select a Category, Size, or Color to apply to all rows.');
                return;
            }

            tbodyVariantRows.querySelectorAll('.variant-row').forEach(row => {
                if (catVal) {
                    const catSel = row.querySelector('.var-category');
                    if (catSel) {
                        catSel.value = catVal;
                        catSel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (subVal) {
                        const subSel = row.querySelector('.var-subcategory');
                        if (subSel) {
                            subSel.value = subVal;
                            subSel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (subSubVal) {
                            const ssSel = row.querySelector('.var-subsubcategory');
                            if (ssSel) {
                                ssSel.value = subSubVal;
                                ssSel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    }
                }
                if (sizeVal) {
                    const szInp = row.querySelector('.var-size');
                    if (szInp) szInp.value = sizeVal;
                }
                if (colorVal) {
                    const colInp = row.querySelector('.var-color');
                    if (colInp) colInp.value = colorVal;
                }
            });
        });
    }

    // 5. Auto-Apply Overall MRP / SP on change
    function applyOverallPricingToAll() {
        const mrp = parseFloat(batchInputMrp?.value || '0') || 0;
        const sp = parseFloat(batchInputSp?.value || '0') || 0;
        if (mrp > 0 && sp > 0 && sp > mrp) {
            return;
        }
        if (tbodyVariantRows) {
            tbodyVariantRows.querySelectorAll('.variant-row').forEach(row => {
                const mrpInp = row.querySelector('.var-mrp');
                const spInp = row.querySelector('.var-sp');
                if (mrpInp && mrp > 0) mrpInp.value = mrp.toFixed(2);
                if (spInp && sp > 0) spInp.value = sp.toFixed(2);
                validateMrpSpPair(mrpInp, spInp);
            });
        }
    }
    if (batchInputMrp) batchInputMrp.addEventListener('change', applyOverallPricingToAll);
    if (batchInputSp) batchInputSp.addEventListener('change', applyOverallPricingToAll);

    // 6. Form Submission Validation
    const formMapProductBill = document.getElementById('formMapProductBill');
    if (formMapProductBill && tbodyVariantRows) {
        formMapProductBill.addEventListener('submit', function (e) {
            const rows = tbodyVariantRows.querySelectorAll('.variant-row');
            if (rows.length === 0) {
                e.preventDefault();
                showModernAlert('Please add at least one variant row.');
                return false;
            }

            let pricingError = false;
            let zeroError = false;
            let totalAllocated = 0;

            rows.forEach((row, i) => {
                const sz = row.querySelector('.var-size')?.value.trim();
                const col = row.querySelector('.var-color')?.value.trim();
                const q = parseFloat(row.querySelector('.var-qty')?.value || '0') || 0;
                const mrp = parseFloat(row.querySelector('.var-mrp')?.value || '0') || 0;
                const sp = parseFloat(row.querySelector('.var-sp')?.value || '0') || 0;
                const cat = row.querySelector('.var-category')?.value;

                totalAllocated += q;

                if (!sz || !col || !cat) {
                    zeroError = true;
                }
                if (mrp <= 0 || sp <= 0 || q <= 0) {
                    zeroError = true;
                }
                if (mrp > 0 && sp > 0 && mrp < sp) {
                    pricingError = true;
                }
            });

            if (pricingError) {
                e.preventDefault();
                showModernAlert('Validation Error: MRP cannot be lower than Selling Price on one or more variants.');
                return false;
            }

            if (zeroError) {
                e.preventDefault();
                showModernAlert('Validation Error: Please fill in all required fields (Size, Color, Qty, MRP, Selling Price, Category) on all variant rows.');
                return false;
            }

            if (totalAllocated < purchasedQty - 0.001 && formMapProductBill.dataset.confirmedShortfall !== '1') {
                e.preventDefault();
                showModernConfirmDialog(
                    'Confirm Quantity',
                    'The allocated variant quantity (' + totalAllocated + ') is less than the purchased bill quantity (' + purchasedQty + '). Proceed anyway?',
                    function () {
                        formMapProductBill.dataset.confirmedShortfall = '1';
                        formMapProductBill.submit();
                    },
                    'Proceed'
                );
                return false;
            }

            return true;
        });
    }

    // 7. QR Code Canvas Rendering & Printing on Modal Open (uses saved TSC TE244 config if present)
    function getBillTscConfig() {
        let cfg = { widthMm: 40, heightMm: 25, columns: 2, gapMm: 2, col2Shift: 0, qrX: 12, qrY: 35, qrSize: 3, subX: 12, subY: 125, textX: 115, fontSize: 2, titleY: 35, varY: 62, mrpY: 90, spY: 118 };
        try {
            const raw = localStorage.getItem('grm_tsc_label_config_v1');
            if (raw) cfg = Object.assign(cfg, JSON.parse(raw));
        } catch (e) {}
        return cfg;
    }

    function renderBillQrModal(modalEl) {
        if (!modalEl) return;
        const cfg = getBillTscConfig();
        const scale = 0.82;
        const stickerW = Math.max(180, Math.round(cfg.widthMm * 6.4));
        const stickerH = Math.max(120, Math.round(cfg.heightMm * 6.4));
        const qrPx = Math.max(36, Math.min(88, cfg.qrSize * 16));
        const fontPx = Math.max(10, 7.5 + cfg.fontSize * 1.6);
        const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        const cards = modalEl.querySelectorAll('.bill-qr-label-card');
        if (cards.length) {
            const gridRow = cards[0].closest('.row');
            if (gridRow) gridRow.className = 'd-flex flex-wrap gap-3 align-items-start';
        }
        cards.forEach(function(card, idx) {
            const colWrap = card.closest('[class*="col-"]');
            if (colWrap) colWrap.className = 'd-inline-flex';
            const d = card.dataset;
            const shiftPx = (cfg.columns === 2 && idx % 2 === 1) ? Math.round(cfg.col2Shift * scale) : 0;
            const qrLeft = Math.round(cfg.qrX * scale) + shiftPx;
            const qrTop = Math.round(cfg.qrY * scale);
            const subLeft = Math.round(cfg.subX * scale) + shiftPx;
            const subTop = Math.round(cfg.subY * scale);
            const txtLeft = Math.round(cfg.textX * scale) + shiftPx;
            const txtMaxW = Math.max(70, stickerW - txtLeft - 8);
            card.className = 'bill-qr-label-card';
            const concern = esc(<?= json_encode(strtoupper((string)(function_exists('currentConcern') ? (currentConcern()['name'] ?? 'GRM APPARELS') : 'GRM APPARELS'))) ?>);
            card.innerHTML = `
                <div class="border rounded bg-white position-relative overflow-hidden shadow-sm" style="width:${stickerW}px;height:${stickerH}px;">
                    ${concern ? `<div class="position-absolute fw-bold text-secondary text-uppercase text-truncate text-center px-2" style="left:0;right:0;top:4px;font-size:${Math.max(8, fontPx - 2)}px;">${concern}</div>` : ''}
                    <div class="position-absolute border rounded d-flex align-items-center justify-content-center bg-white tsc-bill-qr-target" data-code="${esc(d.qrCode)}" style="left:${qrLeft}px;top:${qrTop}px;width:${qrPx}px;height:${qrPx}px;"></div>
                    <div class="position-absolute font-monospace fw-bold text-dark" style="left:${subLeft}px;top:${subTop}px;font-size:9.5px;">${esc(d.qrCode)}</div>
                    <div class="position-absolute fw-bold text-uppercase text-truncate" style="left:${txtLeft}px;top:${Math.round(cfg.titleY * scale)}px;font-size:${fontPx}px;max-width:${txtMaxW}px;color:#881337;">${esc((d.productName || '').slice(0, 24))}</div>
                    <div class="position-absolute fw-semibold text-dark text-truncate" style="left:${txtLeft}px;top:${Math.round(cfg.varY * scale)}px;font-size:${fontPx}px;max-width:${txtMaxW}px;">${esc((d.attributes || '').slice(0, 24))}</div>
                    ${d.mrp ? `<div class="position-absolute text-muted text-decoration-line-through" style="left:${txtLeft}px;top:${Math.round(cfg.mrpY * scale)}px;font-size:${Math.max(8.5, fontPx - 1.5)}px;">MRP: ${esc(d.mrp)}</div>` : ''}
                    ${d.sellingPrice ? `<div class="position-absolute fw-bold" style="left:${txtLeft}px;top:${Math.round(cfg.spY * scale)}px;font-size:${fontPx}px;color:#15803d;">Disc: ${esc(d.sellingPrice)}</div>` : ''}
                </div>
            `;
            card.querySelectorAll('.tsc-bill-qr-target').forEach(function(el) {
                el.innerHTML = '';
                new QRCode(el, {
                    text: el.dataset.code || '',
                    width: Math.max(28, qrPx - 4),
                    height: Math.max(28, qrPx - 4),
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        });
    }

    document.querySelectorAll('[id^="modalQrBatch_"]').forEach(function(modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            renderBillQrModal(modalEl);
        });
    });

    document.addEventListener('click', function (e) {
        const printBtn = e.target.closest('.btn-print-tsc-batch');
        if (!printBtn) return;
        const genId = printBtn.getAttribute('data-generation-id');
        const modalEl = document.getElementById('modalQrBatch_' + genId);
        if (!modalEl) return;
        renderBillQrModal(modalEl);

        setTimeout(() => {
            const cfg = getBillTscConfig();
            const cards = Array.from(modalEl.querySelectorAll('.bill-qr-label-card'));
            if (!cards.length) return;
            const pageW = cfg.columns === 2 ? (cfg.widthMm * 2 + cfg.gapMm) : cfg.widthMm;
            const esc = (v) => String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const stickersHtml = cards.map((card, idx) => {
                const d = card.dataset;
                const qrEl = card.querySelector('.tsc-bill-qr-target canvas, .tsc-bill-qr-target img');
                const qrSrc = qrEl ? (qrEl.tagName === 'CANVAS' ? qrEl.toDataURL() : qrEl.src) : '';
                const shiftMm = (cfg.columns === 2 && idx % 2 === 1) ? (cfg.col2Shift * 0.125) : 0;
                const qrMm = Math.max(8, Math.min(18, cfg.qrSize * 3.4));
                const qrLeftMm = (cfg.qrX * 0.125) + shiftMm;
                const qrTopMm = cfg.qrY * 0.125;
                const subLeftMm = (cfg.subX * 0.125) + shiftMm;
                const subTopMm = cfg.subY * 0.125;
                const txtLeftMm = (cfg.textX * 0.125) + shiftMm;
                const fontPt = Math.max(6, 4.5 + cfg.fontSize * 1.2);
                return `
                    <div class="sticker">
                        ${qrSrc ? `<img src="${qrSrc}" style="position:absolute;left:${qrLeftMm}mm;top:${qrTopMm}mm;width:${qrMm}mm;height:${qrMm}mm;">` : ''}
                        <div style="position:absolute;left:${subLeftMm}mm;top:${subTopMm}mm;font-family:monospace;font-size:6pt;font-weight:700;">${esc(d.qrCode)}</div>
                        <div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.titleY * 0.125}mm;font-size:${fontPt}pt;font-weight:700;color:#1d4ed8;white-space:nowrap;overflow:hidden;max-width:${Math.max(12, cfg.widthMm - txtLeftMm - 1)}mm;">${esc(d.productName)}</div>
                        <div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.varY * 0.125}mm;font-size:${fontPt}pt;font-weight:600;white-space:nowrap;overflow:hidden;max-width:${Math.max(12, cfg.widthMm - txtLeftMm - 1)}mm;">${esc(d.attributes)}</div>
                        ${d.mrp ? `<div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.mrpY * 0.125}mm;font-size:${Math.max(5.5, fontPt - 1)}pt;color:#666;text-decoration:line-through;">MRP: ${esc(d.mrp)}</div>` : ''}
                        ${d.sellingPrice ? `<div style="position:absolute;left:${txtLeftMm}mm;top:${cfg.spY * 0.125}mm;font-size:${fontPt}pt;font-weight:700;color:#15803d;">Disc: ${esc(d.sellingPrice)}</div>` : ''}
                    </div>
                `;
            }).join('');

            const win = window.open('', '_blank', 'width=760,height=600');
            if (!win) return;
            win.document.write(`<!doctype html><html><head><title>Print QR Labels</title><style>@page{size:${pageW}mm ${cfg.heightMm}mm;margin:0}body{margin:0;font-family:sans-serif;display:flex;flex-wrap:wrap;gap:${cfg.gapMm}mm;width:${pageW}mm}.sticker{position:relative;width:${cfg.widthMm}mm;height:${cfg.heightMm}mm;overflow:hidden;box-sizing:border-box;page-break-inside:avoid}@media screen{body{padding:12px;background:#f3f4f6}.sticker{background:#fff;border:1px dashed #cbd5e1}}</style></head><body>${stickersHtml}<script>window.onload=function(){window.print();}<\/script></body></html>`);
            win.document.close();
        }, 80);
    });

    // In-panel AJAX Add to Inventory for Purchase Bill Mapping
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-add-inventory-bill');
        if (!btn) return;

        const genId = btn.getAttribute('data-generation-id');
        const purchaseId = btn.getAttribute('data-purchase-id');
        const whSelect = document.getElementById('billWhSelect_' + genId);
        const whId = whSelect ? whSelect.value : '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Adding...';

        const formData = new FormData();
        formData.append('generation_id', genId);
        formData.append('warehouse_id', whId);
        formData.append('csrf_token', '<?= CSRF::token() ?>');

        fetch('<?= url("api/purchases/mapping/add-to-stock") ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                btn.className = 'btn btn-sm btn-success disabled';
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Stock Added';
                const statusEl = document.getElementById('modalBillStockStatus_' + genId);
                if (statusEl) {
                    statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Stocked</span>';
                }
                const billModalEl = btn.closest('.modal');
                if (billModalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(billModalEl)?.hide();
                }
                showModernAlert('Success: ' + (data.message || 'Stock added to inventory!'));
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add to Inventory';
                showModernAlert('Error: ' + (data.message || 'Could not add stock.'));
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-seam me-1"></i>Add to Inventory';
            showModernAlert('Server error: ' + err.message);
        });
    });

    // 8. Add Product Modal Handlers
    const addBillProductButton = document.getElementById('btnCreateBillProduct');
    if (addBillProductButton) {
        addBillProductButton.addEventListener('click', function () {
            const errorBox = document.getElementById('newBillProductError');
            const formData = new FormData();
            formData.append('purchase_id', '<?= (int)$purchase['id'] ?>');
            formData.append('invoice_number', document.getElementById('inputInvoiceNumber') ? document.getElementById('inputInvoiceNumber').value.trim() : '');
            formData.append('new_item_name', document.getElementById('newBillItemName').value.trim());
            const vSkuInput = document.getElementById('newBillVendorSku');
    if (vSkuInput) formData.append('new_vendor_sku', vSkuInput.value.trim());
            formData.append('new_quantity', document.getElementById('newBillQuantity').value);
            formData.append('new_unit', document.getElementById('newBillUnit').value.trim());
            formData.append('new_unit_price', document.getElementById('newBillUnitPrice').value);
            formData.append('new_gst_rate', document.getElementById('newBillGstRate').value);
            formData.append('new_mrp', document.getElementById('newBillMrp').value);
            formData.append('new_selling_price', document.getElementById('newBillSellingPrice').value);
            formData.append('new_hsn_code', document.getElementById('newBillHsn').value.trim());
            formData.append('new_color', document.getElementById('newBillColor').value.trim());
            formData.append('new_size', document.getElementById('newBillSize').value.trim());
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('csrf_token', '<?= CSRF::token() ?>');

            errorBox.classList.add('d-none');
            addBillProductButton.disabled = true;
            fetch(`${BASE_URL}/purchases/mapping/add-product`, { method: 'POST', body: formData })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Unable to add this product.');
                    }
                    window.location.href = data.next_url;
                })
                .catch(error => {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('d-none');
                    addBillProductButton.disabled = false;
                });
        });
    }

    // 8b. Live MRP vs Selling Price Validation (Selling Price must not exceed MRP)
    function validateMrpSpPair(mrpEl, spEl) {
        if (!mrpEl || !spEl) return true;
        const mrp = parseFloat(mrpEl.value || '0') || 0;
        const sp = parseFloat(spEl.value || '0') || 0;
        const invalid = (mrp > 0 && sp > 0 && sp > mrp);
        mrpEl.classList.toggle('is-invalid', invalid);
        spEl.classList.toggle('is-invalid', invalid);
        const msg = invalid ? `Selling Price (${formatCurrency(sp)}) must be less than or equal to MRP (${formatCurrency(mrp)}).` : '';
        mrpEl.title = msg;
        spEl.title = msg;
        return !invalid;
    }

    if (tbodyVariantRows) {
        tbodyVariantRows.addEventListener('input', function (e) {
            if (e.target.classList.contains('var-mrp') || e.target.classList.contains('var-sp')) {
                const row = e.target.closest('.variant-row');
                if (row) validateMrpSpPair(row.querySelector('.var-mrp'), row.querySelector('.var-sp'));
            }
        });
    }
    if (batchInputMrp && batchInputSp) {
        [batchInputMrp, batchInputSp].forEach(el => {
            el.addEventListener('input', () => validateMrpSpPair(batchInputMrp, batchInputSp));
        });
    }
    const newBillMrpEl = document.getElementById('newBillMrp');
    const newBillSpEl = document.getElementById('newBillSellingPrice');
    if (newBillMrpEl && newBillSpEl) {
        [newBillMrpEl, newBillSpEl].forEach(el => {
            el.addEventListener('input', () => validateMrpSpPair(newBillMrpEl, newBillSpEl));
        });
    }

    // 9. Quick Add Modals (AJAX Integration for Tier 1, Tier 2 & Tier 3)
    let activeQuickAddRow = null;

    document.addEventListener('click', function (e) {
        const btn1 = e.target.closest('.btn-quick-add-tier1');
        if (btn1) {
            activeQuickAddRow = btn1.closest('.variant-row');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddCategory')).show();
            return;
        }
        const btn2 = e.target.closest('.btn-quick-add-tier2');
        if (btn2) {
            activeQuickAddRow = btn2.closest('.variant-row');
            const catVal = activeQuickAddRow?.querySelector('.var-category')?.value || '';
            if (!catVal) {
                showModernAlert('Please select a Main Category (Tier 1) first.');
                return;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddSubcategory')).show();
            return;
        }
        const btn3 = e.target.closest('.btn-quick-add-tier3');
        if (btn3) {
            activeQuickAddRow = btn3.closest('.variant-row');
            const subVal = activeQuickAddRow?.querySelector('.var-subcategory')?.value || '';
            if (!subVal) {
                showModernAlert('Please select a Sub Category (Tier 2) first.');
                return;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddSubSubcategory')).show();
        }
    });

    const btnSubmitModalTier1 = document.getElementById('btnSubmitModalTier1');
    if (btnSubmitModalTier1) {
        btnSubmitModalTier1.addEventListener('click', function () {
            const nameInput = document.getElementById('modalTier1Name');
            const codeInput = document.getElementById('modalTier1Code');
            const name = nameInput.value.trim();
            const code = codeInput.value.trim().toUpperCase();
            if (!name) {
                showModernAlert('Please enter a Category name.');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('code', code);
            formData.append('catalog_source', 'THIRD_PARTY');
            const isComplementary = document.getElementById('modalTier1Complementary')?.checked ? '1' : '0';
            formData.append('is_complementary', isComplementary);
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('csrf_token', '<?= CSRF::token() ?>');

            fetch(`${BASE_URL}/api/categories/quick-add`, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (tbodyVariantRows) {
                            tbodyVariantRows.querySelectorAll('.var-category').forEach(sel => {
                                if (!Array.from(sel.options).some(o => String(o.value) === String(data.id))) {
                                    const opt = document.createElement('option');
                                    opt.value = data.id;
                                    opt.textContent = data.name;
                                    sel.appendChild(opt);
                                }
                            });
                        }
                        if (activeQuickAddRow) {
                            const sel = activeQuickAddRow.querySelector('.var-category');
                            if (sel) {
                                sel.value = String(data.id);
                                sel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                        bootstrap.Modal.getInstance(document.getElementById('modalQuickAddCategory')).hide();
                        nameInput.value = '';
                        codeInput.value = '';
                    } else {
                        showModernAlert(data.message || 'Error creating category.');
                    }
                })
                .catch(err => showModernAlert('Failed to create category: ' + err.message));
        });
    }

    const btnSubmitModalTier2 = document.getElementById('btnSubmitModalTier2');
    if (btnSubmitModalTier2) {
        btnSubmitModalTier2.addEventListener('click', function () {
            const nameInput = document.getElementById('modalTier2Name');
            const codeInput = document.getElementById('modalTier2Code');
            const name = nameInput.value.trim();
            const code = codeInput.value.trim().toUpperCase();
            const categoryId = activeQuickAddRow?.querySelector('.var-category')?.value || '';
            if (!categoryId) {
                showModernAlert('Please select a Main Category first.');
                return;
            }
            if (!name) {
                showModernAlert('Please enter a Sub Category name.');
                return;
            }

            const formData = new FormData();
            formData.append('category_id', categoryId);
            formData.append('name', name);
            formData.append('code', code);
            formData.append('catalog_source', 'THIRD_PARTY');
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('csrf_token', '<?= CSRF::token() ?>');

            fetch(`${BASE_URL}/api/subcategories/quick-add`, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (tbodyVariantRows) {
                            tbodyVariantRows.querySelectorAll('.variant-row').forEach(row => {
                                const subSel = row.querySelector('.var-subcategory');
                                const rowCatId = row.querySelector('.var-category')?.value || '';
                                if (subSel && !Array.from(subSel.options).some(o => String(o.value) === String(data.id))) {
                                    const opt = document.createElement('option');
                                    opt.value = data.id;
                                    opt.textContent = data.name;
                                    opt.dataset.parent = String(categoryId);
                                    opt.hidden = (String(rowCatId) !== String(categoryId));
                                    subSel.appendChild(opt);
                                }
                            });
                        }
                        if (activeQuickAddRow) {
                            const subSel = activeQuickAddRow.querySelector('.var-subcategory');
                            if (subSel) {
                                subSel.value = String(data.id);
                                subSel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                        bootstrap.Modal.getInstance(document.getElementById('modalQuickAddSubcategory')).hide();
                        nameInput.value = '';
                        codeInput.value = '';
                    } else {
                        showModernAlert(data.message || 'Error creating sub category.');
                    }
                })
                .catch(err => showModernAlert('Failed to create sub category: ' + err.message));
        });
    }

    const btnSubmitModalTier3 = document.getElementById('btnSubmitModalTier3');
    if (btnSubmitModalTier3) {
        btnSubmitModalTier3.addEventListener('click', function () {
            const nameInput = document.getElementById('modalTier3Name');
            const codeInput = document.getElementById('modalTier3Code');
            const name = nameInput.value.trim();
            const code = codeInput.value.trim().toUpperCase();
            const subcategoryId = activeQuickAddRow?.querySelector('.var-subcategory')?.value || '';
            if (!subcategoryId) {
                showModernAlert('Please select a Sub Category first.');
                return;
            }
            if (!name) {
                showModernAlert('Please enter a Sub Sub Category name.');
                return;
            }

            const formData = new FormData();
            formData.append('subcategory_id', subcategoryId);
            formData.append('name', name);
            formData.append('code', code);
            formData.append('catalog_source', 'THIRD_PARTY');
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('csrf_token', '<?= CSRF::token() ?>');

            fetch(`${BASE_URL}/api/sub-subcategories/quick-add`, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (tbodyVariantRows) {
                            tbodyVariantRows.querySelectorAll('.variant-row').forEach(row => {
                                const ssSel = row.querySelector('.var-subsubcategory');
                                const rowSubId = row.querySelector('.var-subcategory')?.value || '';
                                if (ssSel && !Array.from(ssSel.options).some(o => String(o.value) === String(data.id))) {
                                    const opt = document.createElement('option');
                                    opt.value = data.id;
                                    opt.textContent = data.name;
                                    opt.dataset.parent = String(subcategoryId);
                                    opt.hidden = (String(rowSubId) !== String(subcategoryId));
                                    ssSel.appendChild(opt);
                                }
                            });
                        }
                        if (activeQuickAddRow) {
                            const ssSel = activeQuickAddRow.querySelector('.var-subsubcategory');
                            if (ssSel) {
                                ssSel.value = String(data.id);
                            }
                        }
                        bootstrap.Modal.getInstance(document.getElementById('modalQuickAddSubSubcategory')).hide();
                        nameInput.value = '';
                        codeInput.value = '';
                    } else {
                        showModernAlert(data.message || 'Error creating sub sub category.');
                    }
                })
                .catch(err => showModernAlert('Failed to create sub sub category: ' + err.message));
        });
    }

    // 10. Sync Variant Rows to Purchased Qty (1 row per unit)
    const btnSyncRowsToPurchasedQty = document.getElementById('btnSyncRowsToPurchasedQty');
    if (btnSyncRowsToPurchasedQty && tbodyVariantRows) {
        btnSyncRowsToPurchasedQty.addEventListener('click', function () {
            const targetRows = Math.max(1, Math.min(200, Math.round(purchasedQty) || 1));
            const currentRows = tbodyVariantRows.querySelectorAll('.variant-row');
            if (currentRows.length < targetRows) {
                addVariantRows(targetRows - currentRows.length);
            } else if (currentRows.length > targetRows) {
                showModernConfirmDialog(
                    'Sync Rows to Purchased Qty',
                    `Reduce rows from ${currentRows.length} to ${targetRows} to match Purchased Qty?`,
                    function () {
                        for (let i = currentRows.length - 1; i >= targetRows; i--) {
                            currentRows[i].remove();
                        }
                        renumberRows();
                        tbodyVariantRows.querySelectorAll('.var-qty').forEach(inp => { inp.value = '1'; });
                        updateAllocationStatus();
                    },
                    'Reduce Rows',
                    'btn-warning'
                );
                return;
            }
            tbodyVariantRows.querySelectorAll('.var-qty').forEach(inp => {
                inp.value = '1';
            });
            updateAllocationStatus();
        });
    }

    // 11. HSN Code Prefix Auto-Suggestion (from /api/hsn-codes)
    let hsnMasterList = [];
    fetch(`${BASE_URL}/api/hsn-codes`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(payload => {
            if (payload && payload.success && Array.isArray(payload.data)) {
                const seen = new Set();
                hsnMasterList = payload.data.filter(item => {
                    const code = String(item.hsn_code || '').trim();
                    if (!code || seen.has(code)) return false;
                    seen.add(code);
                    return true;
                });
            }
        })
        .catch(() => {});

    const hsnDropdown = document.createElement('div');
    hsnDropdown.id = 'hsnSuggestDropdown';
    hsnDropdown.className = 'list-group shadow-lg border';
    hsnDropdown.style.cssText = 'position:fixed;z-index:2050;display:none;max-height:240px;overflow-y:auto;min-width:260px;background:#fff;border-radius:0.5rem;';
    document.body.appendChild(hsnDropdown);

    let activeHsnInput = null;
    let activeHsnIndex = -1;
    let currentHsnMatches = [];

    function hideHsnDropdown() {
        hsnDropdown.style.display = 'none';
        hsnDropdown.innerHTML = '';
        activeHsnInput = null;
        activeHsnIndex = -1;
        currentHsnMatches = [];
    }

    function applyCategoryHierarchyFromHsn(row, hsnItem) {
        if (!row || !hsnItem) return;
        const catName = String(hsnItem.category_name || '').trim().toLowerCase();
        const subName = String(hsnItem.subcategory_name || '').trim().toLowerCase();
        const subSubName = String(hsnItem.sub_subcategory_name || '').trim().toLowerCase();
        if (!catName) return;

        const catSel = row.querySelector('.var-category');
        const subSel = row.querySelector('.var-subcategory');
        const ssSel = row.querySelector('.var-subsubcategory');
        if (!catSel) return;

        const catOpt = Array.from(catSel.options).find(o => o.value && o.textContent.trim().toLowerCase() === catName);
        if (!catOpt) return;
        catSel.value = catOpt.value;
        catSel.dispatchEvent(new Event('change', { bubbles: true }));

        if (subName && subSel) {
            const subOpt = Array.from(subSel.options).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === subName);
            if (subOpt) {
                subSel.value = subOpt.value;
                subSel.dispatchEvent(new Event('change', { bubbles: true }));

                if (subSubName && ssSel) {
                    const ssOpt = Array.from(ssSel.options).find(o => o.value && !o.hidden && o.textContent.trim().toLowerCase() === subSubName);
                    if (ssOpt) {
                        ssSel.value = ssOpt.value;
                        ssSel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        }
    }

    function applyHsnSelection(item) {
        if (!activeHsnInput || !item) return;
        activeHsnInput.value = item.hsn_code;
        const row = activeHsnInput.closest('.variant-row');
        if (item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') {
            if (row) {
                const gstEl = row.querySelector('.var-gst');
                if (gstEl) gstEl.value = Number(item.gst_rate).toFixed(2);
            } else if (activeHsnInput.id === 'newBillHsn') {
                const modalGst = document.getElementById('newBillGstRate');
                if (modalGst) modalGst.value = String(item.gst_rate);
            }
        }
        if (row) {
            applyCategoryHierarchyFromHsn(row, item);
        }
        activeHsnInput.dispatchEvent(new Event('change', { bubbles: true }));
        hideHsnDropdown();
    }

    // Also auto-load Category Hierarchy when typing/changing a full HSN code directly
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('var-hsn')) return;
        const code = e.target.value.trim().toUpperCase();
        if (!code || hsnMasterList.length === 0) return;
        const matched = hsnMasterList.find(h => String(h.hsn_code || '').toUpperCase() === code && h.category_name);
        if (matched) {
            const row = e.target.closest('.variant-row');
            if (row) {
                if (matched.gst_rate !== null && matched.gst_rate !== undefined && matched.gst_rate !== '') {
                    const gstEl = row.querySelector('.var-gst');
                    if (gstEl) gstEl.value = Number(matched.gst_rate).toFixed(2);
                }
                applyCategoryHierarchyFromHsn(row, matched);
            }
        }
    });

    // 12. Category-Based Size & Color Auto-Suggest (Case-Insensitive Deduplicated) + Quick-Add
    const categoryAttributesMap = <?= json_encode($categoryAttributesMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const dedupeCi = (list) => {
        const seen = new Set();
        const out = [];
        for (const v of (list || [])) {
            const s = String(v || '').trim();
            if (!s) continue;
            const k = s.toLowerCase();
            if (seen.has(k)) continue;
            seen.add(k);
            out.push(s);
        }
        return out;
    };
    const globalSizeList = dedupeCi(<?= json_encode($allSizeNames ?? [], JSON_UNESCAPED_UNICODE) ?>);
    const globalColorList = dedupeCi(<?= json_encode($allColorNames ?? [], JSON_UNESCAPED_UNICODE) ?>);

    function getAttributeCandidates(input) {
        const isSize = input.classList.contains('var-size') || input.id === 'batchInputSize';
        const baseList = isSize ? globalSizeList : globalColorList;
        const row = input.closest('.variant-row');
        if (!row) return baseList;

        const catSel = row.querySelector('.var-category');
        const subSel = row.querySelector('.var-subcategory');
        const ssSel = row.querySelector('.var-subsubcategory');
        const cName = (catSel && catSel.selectedOptions[0]?.value ? catSel.selectedOptions[0].textContent.trim().toLowerCase() : '');
        const sName = (subSel && subSel.selectedOptions[0]?.value ? subSel.selectedOptions[0].textContent.trim().toLowerCase() : '');
        const ssName = (ssSel && ssSel.selectedOptions[0]?.value ? ssSel.selectedOptions[0].textContent.trim().toLowerCase() : '');

        let attr = null;
        if (cName && sName && ssName && categoryAttributesMap[`${cName}|${sName}|${ssName}`]) {
            attr = categoryAttributesMap[`${cName}|${sName}|${ssName}`];
        } else if (cName && sName && categoryAttributesMap[`${cName}|${sName}`]) {
            attr = categoryAttributesMap[`${cName}|${sName}`];
        } else if (cName && categoryAttributesMap[cName]) {
            attr = categoryAttributesMap[cName];
        }
        const linked = attr ? (isSize ? attr.sizes : attr.colors) : [];
        return (Array.isArray(linked) && linked.length > 0) ? dedupeCi([...linked, ...baseList]) : baseList;
    }

    const attrDropdown = document.createElement('div');
    attrDropdown.id = 'attrSuggestDropdown';
    attrDropdown.className = 'list-group shadow-lg border';
    attrDropdown.style.cssText = 'position:fixed;z-index:2050;display:none;max-height:220px;overflow-y:auto;min-width:170px;background:#fff;border-radius:0.5rem;';
    document.body.appendChild(attrDropdown);

    let activeAttrInput = null;
    let activeAttrIdx = -1;
    let currentAttrMatches = [];

    function hideAttrDropdown() {
        attrDropdown.style.display = 'none';
        attrDropdown.innerHTML = '';
        activeAttrInput = null;
        activeAttrIdx = -1;
        currentAttrMatches = [];
    }

    function applyAttrSelection(val) {
        if (!activeAttrInput || !val) return;
        activeAttrInput.value = val;
        activeAttrInput.dispatchEvent(new Event('change', { bubbles: true }));
        hideAttrDropdown();
    }

    function renderAttrDropdown(input, showAllOnFocus = false) {
        const q = input.value.trim().toLowerCase();
        const candidates = getAttributeCandidates(input);
        let matches = [];
        if (!q && showAllOnFocus) {
            matches = candidates.slice(0, 15);
        } else {
            const prefix = [];
            const partial = [];
            for (const c of candidates) {
                const lc = c.toLowerCase();
                if (lc.startsWith(q)) prefix.push(c);
                else if (lc.includes(q)) partial.push(c);
            }
            matches = prefix.concat(partial).slice(0, 15);
        }
        currentAttrMatches = matches;
        if (currentAttrMatches.length === 0) {
            hideAttrDropdown();
            return;
        }
        activeAttrInput = input;
        activeAttrIdx = 0;
        const rect = input.getBoundingClientRect();
        attrDropdown.style.top = `${rect.bottom + 2}px`;
        attrDropdown.style.left = `${Math.min(rect.left, window.innerWidth - 200)}px`;
        attrDropdown.style.width = `${Math.max(rect.width, 165)}px`;
        attrDropdown.innerHTML = '';

        currentAttrMatches.forEach((val, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1 px-2.5 text-start small' + (idx === 0 ? ' active' : '');
            btn.textContent = val;
            btn.addEventListener('mousedown', function (ev) {
                ev.preventDefault();
                applyAttrSelection(val);
            });
            attrDropdown.appendChild(btn);
        });
        attrDropdown.style.display = 'block';
    }

    const isAttrSuggestInput = (el) => el && (el.classList.contains('var-size') || el.classList.contains('var-color') || el.id === 'batchInputSize' || el.id === 'batchSelectColor');
    document.addEventListener('input', function (e) {
        if (isAttrSuggestInput(e.target)) renderAttrDropdown(e.target, true);
    });
    document.addEventListener('focusin', function (e) {
        if (isAttrSuggestInput(e.target)) renderAttrDropdown(e.target, true);
    });
    document.addEventListener('keydown', function (e) {
        if (!activeAttrInput || attrDropdown.style.display === 'none') return;
        const items = attrDropdown.querySelectorAll('.list-group-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[activeAttrIdx]?.classList.remove('active');
            activeAttrIdx = (activeAttrIdx + 1) % items.length;
            items[activeAttrIdx]?.classList.add('active');
            items[activeAttrIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[activeAttrIdx]?.classList.remove('active');
            activeAttrIdx = (activeAttrIdx - 1 + items.length) % items.length;
            items[activeAttrIdx]?.classList.add('active');
            items[activeAttrIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeAttrIdx >= 0) {
            e.preventDefault();
            applyAttrSelection(currentAttrMatches[activeAttrIdx]);
        } else if (e.key === 'Escape') {
            hideAttrDropdown();
        }
    });
    document.addEventListener('click', function (e) {
        if (e.target !== activeAttrInput && !attrDropdown.contains(e.target)) {
            hideAttrDropdown();
        }
    });
    window.addEventListener('scroll', hideAttrDropdown, true);

    let activeAttrRow = null;
    document.addEventListener('click', function (e) {
        const sizeBtn = e.target.closest('.btn-quick-add-size');
        if (sizeBtn) {
            activeAttrRow = sizeBtn.closest('.variant-row');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddSize')).show();
            return;
        }
        const colorBtn = e.target.closest('.btn-quick-add-color');
        if (colorBtn) {
            activeAttrRow = colorBtn.closest('.variant-row');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddColor')).show();
        }
    });

    const btnSubmitModalSize = document.getElementById('btnSubmitModalSize');
    if (btnSubmitModalSize) {
        btnSubmitModalSize.addEventListener('click', function () {
            const input = document.getElementById('modalNewSizeCode');
            const code = (input?.value || '').trim().toUpperCase();
            if (!code) {
                showModernAlert('Please enter a Size name/code.');
                return;
            }
            const fd = new FormData();
            fd.append('code', code);
            fd.append('name', code);
            fd.append('_csrf_token', '<?= CSRF::token() ?>');
            fd.append('csrf_token', '<?= CSRF::token() ?>');

            fetch(`${BASE_URL}/api/sizes/quick-add`, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const newSize = data.name || code;
                        if (!globalSizeList.some(s => s.toLowerCase() === newSize.toLowerCase())) {
                            globalSizeList.push(newSize);
                        }
                        if (activeAttrRow) {
                            const rowInp = activeAttrRow.querySelector('.var-size');
                            if (rowInp) rowInp.value = newSize;
                        }
                        bootstrap.Modal.getInstance(document.getElementById('modalQuickAddSize')).hide();
                        input.value = '';
                    } else {
                        showModernAlert(data.message || 'Unable to create size.');
                    }
                })
                .catch(err => showModernAlert('Failed to create size: ' + err.message));
        });
    }

    const btnSubmitModalColor = document.getElementById('btnSubmitModalColor');
    if (btnSubmitModalColor) {
        btnSubmitModalColor.addEventListener('click', function () {
            const nameInput = document.getElementById('modalNewColorName');
            const hexInput = document.getElementById('modalNewColorHex');
            const name = (nameInput?.value || '').trim().toUpperCase();
            const hex = (hexInput?.value || '#000000').trim();
            if (!name) {
                showModernAlert('Please enter a Color name.');
                return;
            }
            const fd = new FormData();
            fd.append('name', name);
            fd.append('hex_code', hex);
            fd.append('_csrf_token', '<?= CSRF::token() ?>');
            fd.append('csrf_token', '<?= CSRF::token() ?>');

            fetch(`${BASE_URL}/api/colors/quick-add`, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const newColor = data.name || name;
                        if (!globalColorList.some(c => c.toLowerCase() === newColor.toLowerCase())) {
                            globalColorList.push(newColor);
                        }
                        if (activeAttrRow) {
                            const rowInp = activeAttrRow.querySelector('.var-color');
                            if (rowInp) rowInp.value = newColor;
                        }
                        bootstrap.Modal.getInstance(document.getElementById('modalQuickAddColor')).hide();
                        nameInput.value = '';
                    } else {
                        showModernAlert(data.message || 'Unable to create color.');
                    }
                })
                .catch(err => showModernAlert('Failed to create color: ' + err.message));
        });
    }

    function renderHsnDropdown(input) {
        const q = input.value.trim().toUpperCase();
        if (!q || hsnMasterList.length === 0) {
            hideHsnDropdown();
            return;
        }
        const prefixMatches = [];
        const otherMatches = [];
        for (const item of hsnMasterList) {
            const code = String(item.hsn_code || '').toUpperCase();
            const desc = String(item.description || '').toUpperCase();
            if (code.startsWith(q)) {
                prefixMatches.push(item);
            } else if (code.includes(q) || desc.includes(q)) {
                otherMatches.push(item);
            }
        }
        currentHsnMatches = prefixMatches.concat(otherMatches).slice(0, 12);
        if (currentHsnMatches.length === 0) {
            hideHsnDropdown();
            return;
        }

        activeHsnInput = input;
        activeHsnIndex = 0;
        const rect = input.getBoundingClientRect();
        hsnDropdown.style.top = `${rect.bottom + 2}px`;
        hsnDropdown.style.left = `${Math.min(rect.left, window.innerWidth - 300)}px`;
        hsnDropdown.style.width = `${Math.max(rect.width, 280)}px`;
        hsnDropdown.innerHTML = '';

        currentHsnMatches.forEach((item, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1.5 px-2.5 text-start small' + (idx === 0 ? ' active' : '');
            const gstText = (item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '')
                ? `${Number(item.gst_rate)}%`
                : 'N/A';
            const descText = item.description ? String(item.description).slice(0, 38) : '';
            btn.innerHTML = `<div class="d-flex justify-content-between align-items-center gap-2">
                <span class="font-monospace fw-bold">${item.hsn_code}</span>
                <span class="badge bg-light text-dark border">${gstText}</span>
            </div>${descText ? `<div class="text-truncate opacity-75" style="font-size:0.75rem;">${descText}</div>` : ''}`;
            btn.addEventListener('mousedown', function (ev) {
                ev.preventDefault();
                applyHsnSelection(item);
            });
            hsnDropdown.appendChild(btn);
        });
        hsnDropdown.style.display = 'block';
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('var-hsn') || e.target.id === 'newBillHsn') {
            renderHsnDropdown(e.target);
        }
    });
    document.addEventListener('focusin', function (e) {
        if (e.target.classList.contains('var-hsn') || e.target.id === 'newBillHsn') {
            if (e.target.value.trim()) renderHsnDropdown(e.target);
        }
    });
    document.addEventListener('keydown', function (e) {
        if (!activeHsnInput || hsnDropdown.style.display === 'none') return;
        const items = hsnDropdown.querySelectorAll('.list-group-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[activeHsnIndex]?.classList.remove('active');
            activeHsnIndex = (activeHsnIndex + 1) % items.length;
            items[activeHsnIndex]?.classList.add('active');
            items[activeHsnIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[activeHsnIndex]?.classList.remove('active');
            activeHsnIndex = (activeHsnIndex - 1 + items.length) % items.length;
            items[activeHsnIndex]?.classList.add('active');
            items[activeHsnIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeHsnIndex >= 0) {
            e.preventDefault();
            applyHsnSelection(currentHsnMatches[activeHsnIndex]);
        } else if (e.key === 'Escape') {
            hideHsnDropdown();
        }
    });
    document.addEventListener('click', function (e) {
        if (e.target !== activeHsnInput && !hsnDropdown.contains(e.target)) {
            hideHsnDropdown();
        }
    });
    window.addEventListener('scroll', hideHsnDropdown, true);

    // 13. Lock Product Details After Save + Top-Right Edit Unlock Toggle
    const isItemAlreadyMapped = <?= json_encode(!empty($activeItem['is_mapped'])) ?>;
    const btnUnlockMappingEdit = document.getElementById('btnUnlockMappingEdit');
    const formMapForLock = document.getElementById('formMapProductBill');

    function setMappingFormLocked(locked) {
        if (!formMapForLock) return;
        formMapForLock.querySelectorAll('input, select, button').forEach(el => {
            if (el.id === 'btnUnlockMappingEdit') return;
            if (el.getAttribute('onclick') && el.getAttribute('onclick').includes('unmapActiveItem')) return;
            if (el.type === 'hidden') return;
            if (el.classList.contains('btn-outline-secondary') && el.tagName === 'A') return;
            el.disabled = locked;
        });
        if (btnUnlockMappingEdit) {
            btnUnlockMappingEdit.classList.toggle('d-none', !locked);
        }
    }

    if (isItemAlreadyMapped && formMapForLock) {
        setMappingFormLocked(true);
        if (btnUnlockMappingEdit) {
            btnUnlockMappingEdit.addEventListener('click', function () {
                setMappingFormLocked(false);
            });
        }
    }

    // 14. Single Modern UI Confirm for Generate All Variant QR (no double native alert)
    const btnGenAllQr = document.getElementById('btnGenerateAllVariantQr');
    const formGenAllQr = document.getElementById('formGenerateAllVariantQr');
    if (btnGenAllQr && formGenAllQr) {
        btnGenAllQr.addEventListener('click', function () {
            if (typeof window.showModernConfirm === 'function') {
                window.showModernConfirm({
                    title: 'Generate Variant QR',
                    message: 'Generate QR batches for all pending variants?',
                    confirmText: 'Confirm',
                    btnClass: 'btn-primary',
                    iconClass: 'bi bi-qr-code text-primary',
                    onConfirm: function () {
                        formGenAllQr.submit();
                    }
                });
            } else {
                formGenAllQr.submit();
            }
        });
    }

    // 15. Multi-Select & Bulk Inward Generated QRs to Concern Inventory Stock
    const chkSelectAllQr = document.getElementById('chkSelectAllQr');
    const btnInwardSelectedQr = document.getElementById('btnInwardSelectedQr');
    const btnInwardAllQr = document.getElementById('btnInwardAllQr');
    const selectedQrCountEl = document.getElementById('selectedQrCount');
    const bulkInwardWhSelect = document.getElementById('bulkInwardWarehouseSelect');
    const allUnstockedQrIds = <?= json_encode($unstockedQrIds ?? []) ?>;

    function getSelectedUniqueQrIds() {
        const set = new Set();
        document.querySelectorAll('.chk-qr-inward:checked').forEach(cb => {
            if (cb.value) set.add(cb.value);
        });
        return Array.from(set);
    }

    function updateBulkInwardSelectionUI() {
        const selected = getSelectedUniqueQrIds();
        if (selectedQrCountEl) selectedQrCountEl.textContent = String(selected.length);
        if (btnInwardSelectedQr) btnInwardSelectedQr.disabled = selected.length === 0;
        if (chkSelectAllQr) {
            const allCbs = document.querySelectorAll('.chk-qr-inward');
            chkSelectAllQr.checked = allCbs.length > 0 && Array.from(allCbs).every(cb => cb.checked);
        }
    }

    if (chkSelectAllQr) {
        chkSelectAllQr.addEventListener('change', function () {
            document.querySelectorAll('.chk-qr-inward').forEach(cb => {
                cb.checked = chkSelectAllQr.checked;
            });
            updateBulkInwardSelectionUI();
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('chk-qr-inward')) {
            updateBulkInwardSelectionUI();
        }
    });

    function executeBulkInwardToStock(genIds, triggerBtn) {
        if (!genIds || genIds.length === 0) return;
        const whId = bulkInwardWhSelect ? bulkInwardWhSelect.value : '';
        const origHtml = triggerBtn ? triggerBtn.innerHTML : '';
        if (triggerBtn) {
            triggerBtn.disabled = true;
            triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding to Stock...';
        }
        const fd = new FormData();
        fd.append('generation_ids', genIds.join(','));
        fd.append('warehouse_id', whId);
        fd.append('csrf_token', '<?= CSRF::token() ?>');

        fetch('<?= url("api/purchases/mapping/add-to-stock") ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                if (triggerBtn) {
                    triggerBtn.disabled = false;
                    triggerBtn.innerHTML = origHtml;
                }
                showModernAlert('Error: ' + (data.message || 'Could not add selected QRs to stock.'));
            }
        })
        .catch(err => {
            if (triggerBtn) {
                triggerBtn.disabled = false;
                triggerBtn.innerHTML = origHtml;
            }
            showModernAlert('Server error: ' + err.message);
        });
    }

    if (btnInwardSelectedQr) {
        btnInwardSelectedQr.addEventListener('click', function () {
            const ids = getSelectedUniqueQrIds();
            if (!ids.length) return;
            if (typeof window.showModernConfirm === 'function') {
                window.showModernConfirm({
                    title: 'Add Selected QRs to Stock',
                    message: `Inward ${ids.length} selected QR batch(es) into the chosen warehouse / concern inventory?`,
                    confirmText: 'Add to Stock',
                    btnClass: 'btn-success',
                    iconClass: 'bi bi-box-seam text-success',
                    onConfirm: () => executeBulkInwardToStock(ids, btnInwardSelectedQr)
                });
            } else {
                executeBulkInwardToStock(ids, btnInwardSelectedQr);
            }
        });
    }

    if (btnInwardAllQr) {
        btnInwardAllQr.addEventListener('click', function () {
            if (!allUnstockedQrIds.length) return;
            if (typeof window.showModernConfirm === 'function') {
                window.showModernConfirm({
                    title: 'Add All Generated QRs to Stock',
                    message: `Inward all ${allUnstockedQrIds.length} generated QR batch(es) into the chosen warehouse / concern inventory?`,
                    confirmText: 'Add All to Stock',
                    btnClass: 'btn-success',
                    iconClass: 'bi bi-box-seam text-success',
                    onConfirm: () => executeBulkInwardToStock(allUnstockedQrIds, btnInwardAllQr)
                });
            } else {
                executeBulkInwardToStock(allUnstockedQrIds, btnInwardAllQr);
            }
        });
    }

    // Initial allocation check
    updateAllocationStatus();

    // Unmap helpers
    window.unmapActiveItem = function() {
        showModernConfirmDialog(
            'Delete Mapping',
            "Are you sure you want to delete the mapping for '<?= e(addslashes($activeItem['item_name'])) ?>'? It will automatically be marked as unmapped.",
            function () {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url('purchases/mapping/unmap/' . $activeItemId) ?>';
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= CSRF::token() ?>';
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            },
            'Yes, Unmap',
            'btn-danger'
        );
    };

    // =========================================================================
    // BILL MAPPING VARIANT GENERATOR (Matches mapping_draft UI — no auto-loaded rows)
    // =========================================================================
    const billCategoryAttrMap = <?= json_encode($categoryAttributesMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const billMasterSizes = <?= json_encode(array_values(array_filter(array_map(static fn($s) => trim((string)($s['name'] ?? '')), $masterSizes ?? []))), JSON_UNESCAPED_UNICODE) ?>;
    const billMasterColors = <?= json_encode(array_values(array_filter(array_map(static fn($c) => trim((string)($c['name'] ?? '')), $masterColors ?? []))), JSON_UNESCAPED_UNICODE) ?>;
    const billHsnList = <?= json_encode($hsnList ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const billCatSel = document.getElementById('billGenCategory');
    const billSubSel = document.getElementById('billGenSubcategory');
    const billSsSel = document.getElementById('billGenSubSubcategory');
    const billSizeChips = document.getElementById('billGenSizeChips');
    const billColorChips = document.getElementById('billGenColorChips');
    const billSizesInput = document.getElementById('billGenSizesInput');
    const billColorsInput = document.getElementById('billGenColorsInput');
    const billAttrCols = document.querySelectorAll('.bill-gen-attributes-col');
    const billRatesWrap = document.getElementById('billGenVariantRatesWrap');
    const billRatesBody = document.getElementById('billGenVariantRatesBody');
    const billHsnInput = document.getElementById('billGenHsn');
    const billHsnBtn = document.getElementById('btnBillHsnDropdown');
    const billGstInput = document.getElementById('billGenGst');
    const btnGenSaveBill = document.getElementById('btnGenerateSaveBillVariants');

    const dedupeBillList = (arr) => {
        const seen = new Set();
        const out = [];
        for (const v of (arr || [])) {
            const s = String(v || '').trim();
            if (!s) continue;
            const k = s.toLowerCase();
            if (seen.has(k)) continue;
            seen.add(k);
            out.push(s);
        }
        return out;
    };

    function renderBillAttributeChips() {
        if (!billCatSel || !billSizeChips || !billColorChips) return;
        const cName = billCatSel.selectedOptions[0]?.value ? billCatSel.selectedOptions[0].textContent.trim().toLowerCase() : '';
        const sName = billSubSel?.selectedOptions[0]?.value ? billSubSel.selectedOptions[0].textContent.trim().toLowerCase() : '';
        const ssName = billSsSel?.selectedOptions[0]?.value ? billSsSel.selectedOptions[0].textContent.trim().toLowerCase() : '';

        if (!cName) {
            billAttrCols.forEach(col => col.classList.add('d-none'));
            billSizeChips.innerHTML = '';
            billColorChips.innerHTML = '';
            return;
        }
        billAttrCols.forEach(col => col.classList.remove('d-none'));

        let attr = null;
        if (cName && sName && ssName && billCategoryAttrMap[`${cName}|${sName}|${ssName}`]) attr = billCategoryAttrMap[`${cName}|${sName}|${ssName}`];
        else if (cName && sName && billCategoryAttrMap[`${cName}|${sName}`]) attr = billCategoryAttrMap[`${cName}|${sName}`];
        else if (cName && billCategoryAttrMap[cName]) attr = billCategoryAttrMap[cName];

        const sizeList = dedupeBillList((attr && attr.sizes && attr.sizes.length) ? attr.sizes : billMasterSizes);
        const colorList = dedupeBillList((attr && attr.colors && attr.colors.length) ? attr.colors : billMasterColors);
        const activeSizes = new Set((billSizesInput?.value || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean));
        const activeColors = new Set((billColorsInput?.value || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean));

        billSizeChips.innerHTML = sizeList.length
            ? sizeList.map(sz => `<button type="button" class="btn btn-xs ${activeSizes.has(sz.toLowerCase()) ? 'btn-primary' : 'btn-outline-secondary'} py-0 px-2 small bill-gen-size-chip" data-val="${sz.replace(/"/g, '&quot;')}">${sz}</button>`).join('')
            : '<span class="small text-muted">No catalog sizes for this category — type below</span>';
        billColorChips.innerHTML = colorList.length
            ? colorList.map(col => `<button type="button" class="btn btn-xs ${activeColors.has(col.toLowerCase()) ? 'btn-primary' : 'btn-outline-secondary'} py-0 px-2 small bill-gen-color-chip" data-val="${col.replace(/"/g, '&quot;')}">${col}</button>`).join('')
            : '<span class="small text-muted">No catalog colors for this category — type below</span>';
    }

    function renderBillVariantRateTable() {
        if (!billRatesWrap || !billRatesBody) return;
        const sizes = dedupeBillList((billSizesInput?.value || '').split(','));
        const colors = dedupeBillList((billColorsInput?.value || '').split(','));
        if (!sizes.length && !colors.length) {
            billRatesWrap.classList.add('d-none');
            billRatesBody.innerHTML = '';
            return;
        }
        const existingVals = {};
        billRatesBody.querySelectorAll('tr').forEach(tr => {
            existingVals[tr.dataset.key || ''] = {
                qty: tr.querySelector('.bill-rate-qty')?.value ?? '',
                rate: tr.querySelector('.bill-rate-cost')?.value ?? '',
                mrp: tr.querySelector('.bill-rate-mrp')?.value ?? '',
                sp: tr.querySelector('.bill-rate-sp')?.value ?? '',
            };
        });
        const sList = sizes.length ? sizes : [''];
        const cList = colors.length ? colors : [''];
        const rowsHtml = [];
        const defRate = <?= json_encode(number_format($baseRate, 2, '.', '')) ?>;
        const defMrp = <?= json_encode(number_format($defaultMrp > 0 ? $defaultMrp : round($baseRate * 1.3, 2), 2, '.', '')) ?>;
        const defSp = <?= json_encode(number_format($defaultSp > 0 ? $defaultSp : round($baseRate * 1.2, 2), 2, '.', '')) ?>;

        for (const sz of sList) {
            for (const col of cList) {
                const key = `${sz}|||${col}`;
                const prev = existingVals[key] || {};
                const label = [sz, col].filter(Boolean).join(' / ') || 'Standard';
                rowsHtml.push(`
                    <tr data-key="${key.replace(/"/g, '&quot;')}">
                        <td class="fw-semibold text-dark">${label}</td>
                        <td><input type="number" min="1" step="1" class="form-control form-control-sm bill-rate-qty" value="${prev.qty || '1'}"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm bill-rate-cost" value="${prev.rate || defRate}"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm bill-rate-mrp" value="${prev.mrp || defMrp}"></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm bill-rate-sp" value="${prev.sp || defSp}"></td>
                    </tr>
                `);
            }
        }
        billRatesBody.innerHTML = rowsHtml.join('');
        billRatesWrap.classList.remove('d-none');
    }

    billCatSel?.addEventListener('change', function () {
        const catId = this.value;
        if (billSubSel) {
            Array.from(billSubSel.options).forEach(o => { if (o.value) o.hidden = o.dataset.parent !== catId; });
            billSubSel.value = '';
        }
        if (billSsSel) {
            Array.from(billSsSel.options).forEach(o => { if (o.value) o.hidden = true; });
            billSsSel.value = '';
        }
        renderBillAttributeChips();
    });
    billSubSel?.addEventListener('change', function () {
        const subId = this.value;
        if (billSsSel) {
            Array.from(billSsSel.options).forEach(o => { if (o.value) o.hidden = o.dataset.parent !== subId; });
            billSsSel.value = '';
        }
        renderBillAttributeChips();
    });
    billSsSel?.addEventListener('change', renderBillAttributeChips);
    billSizesInput?.addEventListener('input', () => { renderBillAttributeChips(); renderBillVariantRateTable(); });
    billColorsInput?.addEventListener('input', () => { renderBillAttributeChips(); renderBillVariantRateTable(); });

    document.addEventListener('click', function (e) {
        const szChip = e.target.closest('.bill-gen-size-chip');
        const colChip = e.target.closest('.bill-gen-color-chip');
        if (szChip && billSizesInput) {
            const cur = dedupeBillList(billSizesInput.value.split(','));
            const val = szChip.dataset.val || '';
            const idx = cur.findIndex(v => v.toLowerCase() === val.toLowerCase());
            if (idx >= 0) cur.splice(idx, 1); else cur.push(val);
            billSizesInput.value = cur.join(', ');
            renderBillAttributeChips();
            renderBillVariantRateTable();
        } else if (colChip && billColorsInput) {
            const cur = dedupeBillList(billColorsInput.value.split(','));
            const val = colChip.dataset.val || '';
            const idx = cur.findIndex(v => v.toLowerCase() === val.toLowerCase());
            if (idx >= 0) cur.splice(idx, 1); else cur.push(val);
            billColorsInput.value = cur.join(', ');
            renderBillAttributeChips();
            renderBillVariantRateTable();
        }
    });

    // Multi-purpose scrollable HSN dropdown
    const billHsnDropdown = document.createElement('div');
    billHsnDropdown.className = 'list-group shadow-lg border';
    billHsnDropdown.style.cssText = 'position:fixed;z-index:2050;display:none;max-height:240px;overflow-y:auto;overscroll-behavior:contain;min-width:290px;background:#fff;border-radius:0.5rem;';
    document.body.appendChild(billHsnDropdown);

    function showBillHsnDropdown(forceAll = false) {
        if (!billHsnInput || !billHsnList.length) { billHsnDropdown.style.display = 'none'; return; }
        const q = forceAll ? '' : billHsnInput.value.trim().toUpperCase();
        const matches = (!q ? billHsnList : billHsnList.filter(it =>
            String(it.hsn_code || '').toUpperCase().includes(q) || String(it.description || '').toUpperCase().includes(q)
        )).slice(0, 100);
        if (!matches.length) { billHsnDropdown.style.display = 'none'; return; }
        const rect = (billHsnInput.closest('.input-group') || billHsnInput).getBoundingClientRect();
        billHsnDropdown.style.top = `${rect.bottom + 2}px`;
        billHsnDropdown.style.left = `${Math.min(rect.left, window.innerWidth - 310)}px`;
        billHsnDropdown.style.width = `${Math.max(rect.width, 290)}px`;
        billHsnDropdown.innerHTML = '';
        matches.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1 px-2.5 text-start small';
            const gstText = (item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') ? `${Number(item.gst_rate)}%` : 'N/A';
            btn.innerHTML = `<div class="d-flex justify-content-between"><span class="font-monospace fw-bold">${item.hsn_code}</span><span class="badge bg-light text-dark border">${gstText}</span></div>`
                + (item.description ? `<div class="text-truncate text-muted" style="font-size:0.75rem;">${String(item.description).slice(0, 48)}</div>` : '');
            btn.addEventListener('mousedown', ev => {
                ev.preventDefault();
                billHsnInput.value = item.hsn_code;
                if (billGstInput && item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') {
                    billGstInput.value = String(item.gst_rate);
                }
                billHsnDropdown.style.display = 'none';
            });
            billHsnDropdown.appendChild(btn);
        });
        billHsnDropdown.style.display = 'block';
    }
    billHsnInput?.addEventListener('input', () => showBillHsnDropdown(false));
    billHsnInput?.addEventListener('focus', () => showBillHsnDropdown(billHsnInput.value.trim() === ''));
    billHsnBtn?.addEventListener('click', () => {
        if (billHsnDropdown.style.display === 'block') billHsnDropdown.style.display = 'none';
        else { billHsnInput?.focus(); showBillHsnDropdown(true); }
    });
    document.addEventListener('click', e => {
        if (e.target !== billHsnInput && !billHsnBtn?.contains(e.target) && !billHsnDropdown.contains(e.target)) {
            billHsnDropdown.style.display = 'none';
        }
    });

    if (billCatSel?.value) renderBillAttributeChips();

    // Generate & Save -> populate hidden tbodyVariantRows and submit formMapProductBill
    btnGenSaveBill?.addEventListener('click', function () {
        if (!billCatSel?.value) {
            showModernAlert('Please select a Category first.');
            billCatSel?.focus();
            return;
        }
        const rateRows = Array.from(billRatesBody?.querySelectorAll('tr') || []);
        if (!rateRows.length) {
            showModernAlert('Please select at least one Size or Color attribute.');
            return;
        }
        const templateRow = tbodyVariantRows?.querySelector('.variant-row')?.cloneNode(true);
        if (!templateRow || !tbodyVariantRows) return;

        const gstVal = billGstInput?.value.trim() || '5';
        const hsnVal = billHsnInput?.value.trim() || '6109';
        const gstMode = document.querySelector('.bill-gen-gst-mode:checked')?.value || 'EXCLUDED';
        const gstNum = parseFloat(gstVal) || 0;

        tbodyVariantRows.innerHTML = '';
        for (let i = 0; i < rateRows.length; i++) {
            const rEl = rateRows[i];
            const [sz, col] = (rEl.dataset.key || '|||').split('|||');
            const q = parseFloat(rEl.querySelector('.bill-rate-qty')?.value || '0');
            const rawRate = parseFloat(rEl.querySelector('.bill-rate-cost')?.value || '0');
            const mrp = parseFloat(rEl.querySelector('.bill-rate-mrp')?.value || '0');
            const sp = parseFloat(rEl.querySelector('.bill-rate-sp')?.value || '0');
            if (q <= 0 || mrp <= 0 || sp <= 0 || sp > mrp) {
                showModernAlert('Please check Qty, MRP, and Selling Price (Selling Price cannot exceed MRP).');
                return;
            }
            const effRate = (gstMode === 'INCLUDED' && gstNum > 0 && rawRate > 0) ? (rawRate / (1 + gstNum / 100)).toFixed(2) : rawRate.toFixed(2);
            const row = templateRow.cloneNode(true);
            row.querySelectorAll('input, select').forEach(el => {
                if (el.name) el.name = el.name.replace(/variants\[\d+\]/, `variants[${i}]`);
            });
            const set = (sel, v) => { const el = row.querySelector(sel); if (el) el.value = v; };
            set('.var-category', billCatSel.value);
            set('.var-subcategory', billSubSel?.value || '');
            set('.var-subsubcategory', billSsSel?.value || '');
            set('.var-size', sz || 'Standard');
            set('.var-color', col || 'Standard');
            set('.var-qty', String(Math.round(q)));
            set('.var-rate', effRate);
            set('.var-mrp', mrp.toFixed(2));
            set('.var-sp', sp.toFixed(2));
            set('.var-gst', gstVal);
            set('.var-hsn', hsnVal);
            tbodyVariantRows.appendChild(row);
        }
        formMapProductBill?.requestSubmit ? formMapProductBill.requestSubmit() : formMapProductBill?.submit();
    });
});
</script>
