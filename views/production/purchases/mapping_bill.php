<?php
/**
 * Modern ERP Product Mapping Workspace — Map Purchase Bill Lines
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

// Parse multiple colors if present
$activeColors = [];
if (!empty($parsedAttrs['colors']) && is_array($parsedAttrs['colors'])) {
    $activeColors = $parsedAttrs['colors'];
} elseif (!empty($activeItem['color'])) {
    $activeColors = array_filter(array_map('trim', explode(',', $activeItem['color'])));
} elseif (!empty($existingVariants)) {
    foreach ($existingVariants as $ev) {
        if (!empty($ev['color']) && !in_array($ev['color'], $activeColors, true)) {
            $activeColors[] = $ev['color'];
        }
    }
}
if (empty($activeColors)) {
    $activeColors = [!empty($activeItem['color']) ? trim($activeItem['color']) : 'Blue'];
}

// Parse sizes
$selectedSizes = [];
if (!empty($parsedAttrs['sizes']) && is_array($parsedAttrs['sizes'])) {
    $selectedSizes = $parsedAttrs['sizes'];
} elseif (!empty($activeItem['size'])) {
    $selectedSizes = array_map('trim', explode(',', $activeItem['size']));
} elseif (!empty($existingVariants)) {
    foreach ($existingVariants as $ev) {
        if (!empty($ev['size']) && !in_array($ev['size'], $selectedSizes, true)) {
            $selectedSizes[] = $ev['size'];
        }
    }
}
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
                <li class="breadcrumb-item"><a href="<?= url('purchases') ?>" class="text-decoration-none text-muted">Purchases</a></li>
                <li class="breadcrumb-item"><a href="<?= url('purchases/mapping') ?>" class="text-decoration-none text-muted">Product Mapping</a></li>
                <li class="breadcrumb-item"><a href="<?= url('purchases/mapping/bill/' . (int)$purchase['id']) ?>" class="text-decoration-none text-muted"><?= e($purchase['purchase_number']) ?></a></li>
                <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Map Finished Good</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
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
                <i class="bi bi-arrow-left me-1"></i>Back to Mapping List
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
                <span class="small text-muted"><i class="bi bi-info-circle me-1"></i>Procurement baseline from Supplier Bill</span>
            </div>
            <div class="card-body p-4">
                <!-- 3 Top Reference Panels -->
                <div class="row g-3 mb-4">
                    <!-- Column 1: Product Identity -->
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="d-flex align-items-center gap-1.5 text-uppercase text-muted fw-bold small mb-2">
                                <i class="bi bi-box-seam text-primary"></i> Product Identity
                            </div>
                            <div class="fw-bold fs-5 text-dark mb-1"><?= e($activeItem['item_name']) ?></div>
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
                                <span class="text-muted">Bill Total:</span> <strong class="text-success">₹<?= number_format((float)$purchase['total_amount'], 2) ?></strong>
                            </div>
                            <div class="small text-muted">
                                <span class="text-muted">Vendor Inv:</span> <strong><?= e($purchase['invoice_number'] ?: '—') ?></strong>
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
                                <td class="fw-semibold text-dark fs-6">₹<?= number_format($unitPrice, 2) ?></td>
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
                                <td class="fw-semibold text-dark">₹<?= number_format($taxAmount, 2) ?></td>
                                <td class="fw-bold fs-5 text-success" style="background-color: #e8f5e9;">
                                    ₹<?= number_format($totalValuation, 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 2: CATEGORY HIERARCHY (3-TIER) & HSN CLASSIFICATION (Image 1 Refined) -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">2</span>
                    <h5 class="mb-0 fw-bold text-dark">Category Hierarchy (3-Tier) &amp; HSN Classification</h5>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Required</span>
            </div>
            <div class="card-body p-4">
                <!-- 3-Tier Hierarchy Table (Level Purpose removed as per Image 1) -->
                <div class="table-responsive rounded-3 border mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width: 260px;"><i class="bi bi-diagram-2 me-1"></i>Hierarchy Tier</th>
                                <th><i class="bi bi-check2-square me-1"></i>Category Selection</th>
                                <th style="width: 180px;"><i class="bi bi-upc-scan me-1"></i>Short Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Tier 1: Main Category -->
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 me-1">Tier 1</span>
                                    <strong class="text-dark">Main Category</strong> <span class="text-danger">*</span>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <select class="form-select" id="selectTier1" name="category_id" required>
                                            <option value="">-- Select Main Category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int)$cat['id'] ?>"
                                                        data-code="<?= e($cat['code'] ?? '') ?>"
                                                        data-complementary="<?= (int)($cat['is_complementary'] ?? 0) ?>"
                                                        <?= ($activeCatId === (int)$cat['id']) ? 'selected' : '' ?>>
                                                    <?= e($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalQuickAddCategory" title="Quick Add Category">
                                            <i class="bi bi-plus-circle me-1"></i>New
                                        </button>
                                    </div>
                                    <div class="form-text" id="categoryComplementaryHelp">The selected category sets the default for newly generated variants; you can change each variant below.</div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-uppercase font-monospace text-center fw-bold"
                                           id="inputTier1Code" name="category_code" maxlength="10" placeholder="e.g. NF"
                                           value="<?= e($activeCatCode) ?>">
                                </td>
                            </tr>

                            <!-- Tier 2: Sub Category -->
                            <tr>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 me-1">Tier 2</span>
                                    <strong class="text-dark">Sub Category</strong>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <select class="form-select" id="selectTier2" name="subcategory_id">
                                            <option value="">-- Select Sub Category --</option>
                                            <?php foreach ($subcategories as $sc): ?>
                                                <option value="<?= (int)$sc['id'] ?>"
                                                        data-code="<?= e($sc['code'] ?? '') ?>"
                                                        <?= ($activeSubcatId === (int)$sc['id']) ? 'selected' : '' ?>>
                                                    <?= e($sc['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-info" type="button" data-bs-toggle="modal" data-bs-target="#modalQuickAddSubcategory" title="Quick Add Subcategory">
                                            <i class="bi bi-plus-circle me-1"></i>New
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-uppercase font-monospace text-center fw-bold"
                                           id="inputTier2Code" name="subcategory_code" maxlength="10" placeholder="e.g. FL"
                                           value="<?= e($activeSubcatCode) ?>">
                                </td>
                            </tr>

                            <!-- Tier 3: Sub Sub Category -->
                            <tr>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 me-1">Tier 3</span>
                                    <strong class="text-dark">Sub Sub Category</strong>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <select class="form-select" id="selectTier3" name="sub_subcategory_id">
                                            <option value="">-- Select Sub Sub Category --</option>
                                            <?php foreach ($subSubcategories as $ssc): ?>
                                                <option value="<?= (int)$ssc['id'] ?>"
                                                        data-code="<?= e($ssc['code'] ?? '') ?>"
                                                        <?= ($activeSubSubcatId === (int)$ssc['id']) ? 'selected' : '' ?>>
                                                    <?= e($ssc['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#modalQuickAddSubSubcategory" title="Quick Add Sub-Subcategory">
                                            <i class="bi bi-plus-circle me-1"></i>New
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control text-uppercase font-monospace text-center fw-bold"
                                           id="inputTier3Code" name="sub_subcategory_code" maxlength="10" placeholder="e.g. NS"
                                           value="<?= e($activeSubSubcatCode) ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Hierarchy Summary Strip -->
                <div class="rounded-3 p-3 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-folder2-open text-primary fs-5"></i>
                        <span class="fw-bold text-dark small text-uppercase">Full Hierarchy Path:</span>
                        <span class="text-primary fw-semibold" id="labelHierarchyPath">
                            <?= e($activeCatCode ? ($activeCatCode . ' > ' . ($activeSubcatCode ?: '—') . ' > ' . ($activeSubSubcatCode ?: '—')) : 'Select categories to build path') ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="inputQrKey" class="fw-bold text-dark small text-uppercase mb-0">QR Key:</label>
                        <input type="text" class="form-control form-control-sm font-monospace fw-bold text-uppercase text-center"
                               style="width: 140px; letter-spacing: 1.5px;" id="inputQrKey" name="qr_key"
                               value="<?= e($initialQrKey) ?>" placeholder="e.g. NFFLNS">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 3: PRODUCT ATTRIBUTES & VARIATIONS (Image 2 Refined)              -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">3</span>
                    <h5 class="mb-0 fw-bold text-dark">Product Attributes &amp; Variations</h5>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Required</span>
            </div>
            <div class="card-body p-4">
                <!-- 2-Column Table (Attribute Description & Usage removed as per Image 2) -->
                <div class="table-responsive rounded-3 border mb-0">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width: 280px;"><i class="bi bi-sliders me-1"></i>Attribute Name</th>
                                <th><i class="bi bi-input-cursor me-1"></i>Configured Value / Selection Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Product Style Code -->
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">Product Style Code <span class="text-danger">*</span></div>
                                    <small class="text-muted">(Master Parent SKU Ref)</small>
                                </td>
                                <td>
                                    <input type="text" class="form-control font-monospace fw-bold text-uppercase"
                                           id="inputStyleCode" name="style_code" required
                                           value="<?= e($activeStyleCode) ?>" placeholder="e.g. GRM-STY-0001">
                                </td>
                            </tr>

                            <!-- Product Style Name -->
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">Product Style Name <span class="text-danger">*</span></div>
                                    <small class="text-muted">(Retail Catalog Title)</small>
                                </td>
                                <td>
                                    <input type="text" class="form-control fw-semibold"
                                           id="inputStyleName" name="style_name" required
                                           value="<?= e($activeStyleName) ?>" placeholder="e.g. Maxi Dress">
                                </td>
                            </tr>

                            <!-- HSN Code -->
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">HSN Code</div>
                                    <small class="text-muted">(GST Classification)</small>
                                </td>
                                <td>
                                    <input type="text" class="form-control font-monospace"
                                           id="inputHsnCode" name="hsn_code"
                                           value="<?= e($activeHsn) ?>" placeholder="e.g. 6543">
                                </td>
                            </tr>

                            <!-- Color / Fabric Variation (Multi-select + Custom Add Input) -->
                            <tr>
                                <td class="py-3 align-top">
                                    <div class="fw-bold text-dark">Color / Fabric Variation <span class="text-danger">*</span></div>
                                    <small class="text-muted">(Visual / Aesthetic Attr)</small>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="small fw-bold text-uppercase text-muted">Choose Colors (Multiple Selection):</div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnSelectAllColors">Select All</button>
                                            <span class="text-muted">|</span>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" id="btnClearColors">Clear</button>
                                        </div>
                                    </div>

                                    <!-- Standard Color Toggle Buttons -->
                                    <div class="d-flex flex-wrap my-3" id="containerColorPills" style="row-gap: 12px; column-gap: 8px;">
                                        <?php foreach ($standardColors as $col): ?>
                                            <?php $isColChecked = in_array(strtolower($col), array_map('strtolower', $activeColors), true); ?>
                                            <div class="form-check form-check-inline m-0">
                                                <input class="btn-check color-checkbox" type="checkbox"
                                                       id="color_pill_<?= md5($col) ?>" name="colors[]"
                                                       value="<?= e($col) ?>" autocomplete="off"
                                                       <?= $isColChecked ? 'checked' : '' ?>>
                                                <label class="btn btn-sm <?= $isColChecked ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-1 fw-bold"
                                                       for="color_pill_<?= md5($col) ?>">
                                                    <?= e($col) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Custom Color Adder Input Field -->
                                    <div class="input-group input-group-sm mt-3 mb-1" style="max-width: 420px;">
                                        <input type="text" class="form-control" id="inputCustomColor" placeholder="Add custom color name (e.g. Lavender, Mint, Peach)">
                                        <button class="btn btn-outline-primary fw-semibold" type="button" id="btnAddCustomColor">
                                            <i class="bi bi-plus-circle me-1"></i>Add Color
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Size Attributes & Sizing Matrix -->
                            <tr>
                                <td class="py-3 align-top">
                                    <div class="fw-bold text-dark">Size Attributes <span class="text-danger">*</span></div>
                                    <small class="text-muted">(Variant Sizing Matrix)</small>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="small fw-bold text-uppercase text-muted">Choose Sizes:</div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btnSelectAllSizes">Select All</button>
                                            <span class="text-muted">|</span>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" id="btnClearSizes">Clear</button>
                                        </div>
                                    </div>

                                    <!-- Standard Size Toggle Buttons -->
                                    <div class="d-flex flex-wrap my-3" id="containerSizePills" style="row-gap: 12px; column-gap: 8px;">
                                        <?php foreach ($standardSizes as $size): ?>
                                            <?php $isSizeChecked = in_array($size, $selectedSizes, true); ?>
                                            <div class="form-check form-check-inline m-0">
                                                <input class="btn-check size-checkbox" type="checkbox"
                                                       id="size_pill_<?= md5($size) ?>" name="sizes[]"
                                                       value="<?= e($size) ?>" autocomplete="off"
                                                       <?= $isSizeChecked ? 'checked' : '' ?>>
                                                <label class="btn btn-sm <?= $isSizeChecked ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-1 fw-bold"
                                                       for="size_pill_<?= md5($size) ?>">
                                                    <?= e($size) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Custom Size Adder -->
                                    <div class="input-group input-group-sm mt-3 mb-1" style="max-width: 420px;">
                                        <input type="text" class="form-control" id="inputCustomSize" placeholder="Add custom size (e.g. 4XL, 32, 34)">
                                        <button class="btn btn-outline-primary fw-semibold" type="button" id="btnAddCustomSize">
                                            <i class="bi bi-plus-circle me-1"></i>Add Size
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 4: PRODUCT VARIANTS MATRIX TABLE CONTAINER                        -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">4</span>
                    <h5 class="mb-0 fw-bold text-dark">Product Variants Matrix Table Container</h5>
                </div>
                <!-- Batch Price Replicator Controls -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="width: 170px;">
                        <span class="input-group-text bg-light text-muted">MRP ₹</span>
                        <input type="text" inputmode="decimal" class="form-control text-end numeric-text-input" id="batchInputMrp" value="<?= number_format($defaultMrp, 2, '.', '') ?>" placeholder="0.00">
                    </div>
                    <div class="input-group input-group-sm" style="width: 180px;">
                        <span class="input-group-text bg-light text-muted">SP ₹</span>
                        <input type="text" inputmode="decimal" class="form-control text-end numeric-text-input" id="batchInputSp" value="<?= number_format($defaultSp, 2, '.', '') ?>" placeholder="0.00">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btnReplicatePricing" title="Copy MRP & Selling Price to all variant rows">
                        <i class="bi bi-arrow-repeat me-1"></i>Replicate to All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnHeaderAutoDistribute" onclick="autoDistributeQuantities()" title="Evenly distribute purchased bill quantity (<?= (int)$purchasedQty ?> pcs) across all active variants">
                        <i class="bi bi-magic me-1"></i>Auto-Distribute Qty
                    </button>
                    <div id="batchPriceError" class="d-none w-100 text-end text-danger small fw-semibold" style="font-size: 11px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>MRP cannot be lower than Selling Price!
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center" id="tableVariantMatrix">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 100px;">Size</th>
                                <th style="width: 130px;">Color</th>
                                <th style="min-width: 190px;">Variant SKU</th>
                                <th style="width: 120px;">Allocated Qty</th>
                                <th style="width: 130px;">Purchase Rate (₹)</th>
                                <th style="width: 140px;">MRP (₹) <span class="text-danger">*</span></th>
                                <th style="width: 140px;">Selling Price (₹) <span class="text-danger">*</span></th>
                                <th style="width: 110px;">Complementary</th>
                                <th style="width: 115px;">Margin (%)</th>
                                <th style="min-width: 140px;">Barcode / QR Status</th>
                                <th style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVariantMatrix">
                            <!-- Populated dynamically via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Live Quantity & Margin Allocation Verification Bar -->
                <div class="p-3 bg-light-subtle border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="small fw-bold text-uppercase text-muted">Allocation Summary:</div>
                        <div class="fs-6" id="labelAllocationStatus">
                            <!-- JS updates this -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="btnAutoDistributeQty" onclick="autoDistributeQuantities()" title="Evenly distribute purchased quantity across active variants">
                            <i class="bi bi-distribute-vertical me-1"></i>Auto-Distribute Exact Qty
                        </button>
                        <div id="labelPricingStatus" class="fs-6 d-none">
                            <!-- JS updates this with pricing error badge -->
                        </div>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>Variant SKUs, pricing, and complementary settings will sync to the Third-Party Master Catalog upon save.
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- ACTION FOOTER BAR (Image 3: Save Draft removed)                           -->
        <!-- ========================================================================= -->
        <div class="card border shadow-sm mb-4 bg-white sticky-bottom" style="z-index: 10;">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="small text-muted">
                    <i class="bi bi-gift text-info me-1"></i>Choose complementary status per variant in the matrix above.
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary px-3">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSaveComplete">
                        <i class="bi bi-check-circle-fill me-1"></i>Save &amp; Complete Mapping
                    </button>
                </div>
            </div>
        </div>
    </form>
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

<!-- ========================================================================= -->
<!-- CLIENT-SIDE ENGINE (JAVASCRIPT)                                           -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const BASE_URL = '<?= rtrim(url(), '/') ?>';
    const purchasedQty = <?= json_encode($purchasedQty) ?>;
    const supplierUnitCost = <?= json_encode($unitPrice) ?>;

    // Existing variants loaded from database
    const preExistingVariants = <?= json_encode($existingVariants) ?>;
    const legacyItemComplementary = <?= json_encode(!empty($activeItem['is_complementary'])) ?>;

    // Elements
    const selectTier1 = document.getElementById('selectTier1');
    const selectTier2 = document.getElementById('selectTier2');
    const selectTier3 = document.getElementById('selectTier3');
    const inputTier1Code = document.getElementById('inputTier1Code');
    const inputTier2Code = document.getElementById('inputTier2Code');
    const inputTier3Code = document.getElementById('inputTier3Code');
    const inputQrKey = document.getElementById('inputQrKey');
    const labelHierarchyPath = document.getElementById('labelHierarchyPath');

    const inputStyleCode = document.getElementById('inputStyleCode');
    const inputStyleName = document.getElementById('inputStyleName');
    const containerColorPills = document.getElementById('containerColorPills');
    const containerSizePills = document.getElementById('containerSizePills');

    const tbodyVariantMatrix = document.getElementById('tbodyVariantMatrix');
    const labelAllocationStatus = document.getElementById('labelAllocationStatus');
    const categoryComplementaryHelp = document.getElementById('categoryComplementaryHelp');

    const batchInputMrp = document.getElementById('batchInputMrp');
    const batchInputSp = document.getElementById('batchInputSp');
    const btnReplicatePricing = document.getElementById('btnReplicatePricing');

    // 1. Hierarchy Change Handlers
    function selectedCategoryIsComplementary() {
        const selectedOption = selectTier1.options[selectTier1.selectedIndex];
        return selectedOption && selectedOption.dataset.complementary === '1';
    }

    function updateCategoryComplementaryHelp() {
        if (!categoryComplementaryHelp) return;
        categoryComplementaryHelp.textContent = selectedCategoryIsComplementary()
            ? 'This category defaults new variants to complementary. You can change each variant below.'
            : 'The selected category defaults new variants to regular. You can mark individual variants complementary below.';
    }

    function applyCategoryComplementaryDefault() {
        const categoryDefault = Boolean(selectedCategoryIsComplementary());
        tbodyVariantMatrix.querySelectorAll('.matrix-complementary').forEach(input => {
            input.checked = categoryDefault;
        });
        updateCategoryComplementaryHelp();
    }

    function updateHierarchySummary() {
        const t1Text = selectTier1.options[selectTier1.selectedIndex]?.text?.replace(/\s*\([^)]*\)/, '') || '';
        const t2Text = selectTier2.options[selectTier2.selectedIndex]?.text?.replace(/\s*\([^)]*\)/, '') || '';
        const t3Text = selectTier3.options[selectTier3.selectedIndex]?.text?.replace(/\s*\([^)]*\)/, '') || '';

        const parts = [];
        if (t1Text && selectTier1.value) parts.push(t1Text.trim());
        if (t2Text && selectTier2.value) parts.push(t2Text.trim());
        if (t3Text && selectTier3.value) parts.push(t3Text.trim());

        labelHierarchyPath.textContent = parts.length > 0 ? parts.join(' > ') : 'Select categories to build path';

        const c1 = (inputTier1Code.value || '').trim();
        const c2 = (inputTier2Code.value || '').trim();
        const c3 = (inputTier3Code.value || '').trim();
        inputQrKey.value = (c1 + c2 + c3).toUpperCase();
    }

    selectTier1.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const code = opt ? (opt.getAttribute('data-code') || '') : '';
        inputTier1Code.value = code;

        const catId = this.value;
        selectTier2.innerHTML = '<option value="">-- Select Sub Category --</option>';
        selectTier3.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
        inputTier2Code.value = '';
        inputTier3Code.value = '';

        if (catId) {
            loadSubcategories(catId);
        }
        applyCategoryComplementaryDefault();
        updateHierarchySummary();
    });

    selectTier2.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const code = opt ? (opt.getAttribute('data-code') || '') : '';
        inputTier2Code.value = code;

        const subcatId = this.value;
        selectTier3.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
        inputTier3Code.value = '';

        if (subcatId) {
            loadSubSubcategories(subcatId);
        }
        updateHierarchySummary();
    });

    selectTier3.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const code = opt ? (opt.getAttribute('data-code') || '') : '';
        inputTier3Code.value = code;
        updateHierarchySummary();
    });

    inputTier1Code.addEventListener('input', updateHierarchySummary);
    inputTier2Code.addEventListener('input', updateHierarchySummary);
    inputTier3Code.addEventListener('input', updateHierarchySummary);

    function loadSubcategories(catId, preselectId, callback) {
        fetch(`${BASE_URL}/api/subcategories/${catId}?catalog_source=THIRD_PARTY`)
            .then(res => res.json())
            .then(data => {
                selectTier2.innerHTML = '<option value="">-- Select Sub Category --</option>';
                if (Array.isArray(data)) {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        opt.setAttribute('data-code', item.code || '');
                        if (preselectId && parseInt(item.id, 10) === parseInt(preselectId, 10)) {
                            opt.selected = true;
                        }
                        selectTier2.appendChild(opt);
                    });
                }
                if (callback) callback();
            })
            .catch(err => console.error('Error loading subcategories:', err));
    }

    function loadSubSubcategories(subcatId, preselectId, callback) {
        fetch(`${BASE_URL}/api/sub-subcategories/${subcatId}?catalog_source=THIRD_PARTY`)
            .then(res => res.json())
            .then(data => {
                selectTier3.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
                if (Array.isArray(data)) {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        opt.setAttribute('data-code', item.code || '');
                        if (preselectId && parseInt(item.id, 10) === parseInt(preselectId, 10)) {
                            opt.selected = true;
                        }
                        selectTier3.appendChild(opt);
                    });
                }
                if (callback) callback();
            })
            .catch(err => console.error('Error loading sub-subcategories:', err));
    }

    // 2. Color Selection & Custom Color Add Handlers
    function getSelectedColors() {
        const colors = [];
        containerColorPills.querySelectorAll('.color-checkbox:checked').forEach(cb => {
            colors.push(cb.value);
        });
        return colors;
    }

    function updateColorPillStyles() {
        containerColorPills.querySelectorAll('.color-checkbox').forEach(cb => {
            const label = containerColorPills.querySelector(`label[for="${cb.id}"]`);
            if (label) {
                if (cb.checked) {
                    label.classList.remove('btn-outline-secondary');
                    label.classList.add('btn-primary');
                } else {
                    label.classList.remove('btn-primary');
                    label.classList.add('btn-outline-secondary');
                }
            }
        });
    }

    containerColorPills.addEventListener('change', function (e) {
        if (e.target.classList.contains('color-checkbox')) {
            updateColorPillStyles();
            rebuildVariantMatrix();
        }
    });

    document.getElementById('btnSelectAllColors').addEventListener('click', function () {
        containerColorPills.querySelectorAll('.color-checkbox').forEach(cb => { cb.checked = true; });
        updateColorPillStyles();
        rebuildVariantMatrix();
    });

    document.getElementById('btnClearColors').addEventListener('click', function () {
        containerColorPills.querySelectorAll('.color-checkbox').forEach(cb => { cb.checked = false; });
        updateColorPillStyles();
        rebuildVariantMatrix();
    });

    // Custom Color Adder
    document.getElementById('btnAddCustomColor').addEventListener('click', function () {
        const input = document.getElementById('inputCustomColor');
        const val = input.value.trim();
        if (!val) return;

        // Proper capitalization
        const formatted = val.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');

        let exists = false;
        containerColorPills.querySelectorAll('.color-checkbox').forEach(cb => {
            if (cb.value.toLowerCase() === formatted.toLowerCase()) exists = true;
        });

        if (!exists) {
            const safeId = 'color_pill_' + btoa(formatted).replace(/=/g, '');
            const div = document.createElement('div');
            div.className = 'form-check form-check-inline m-0';
            div.innerHTML = `
                <input class="btn-check color-checkbox" type="checkbox" id="${safeId}" name="colors[]" value="${formatted}" checked autocomplete="off">
                <label class="btn btn-sm btn-primary px-3 py-1 fw-bold" for="${safeId}">${formatted}</label>
            `;
            containerColorPills.appendChild(div);
            input.value = '';
            updateColorPillStyles();
            rebuildVariantMatrix();
        } else {
            input.value = '';
        }
    });

    // 3. Size Selection Handlers
    function getSelectedSizes() {
        const sizes = [];
        containerSizePills.querySelectorAll('.size-checkbox:checked').forEach(cb => {
            sizes.push(cb.value);
        });
        return sizes;
    }

    function updateSizePillStyles() {
        containerSizePills.querySelectorAll('.size-checkbox').forEach(cb => {
            const label = containerSizePills.querySelector(`label[for="${cb.id}"]`);
            if (label) {
                if (cb.checked) {
                    label.classList.remove('btn-outline-secondary');
                    label.classList.add('btn-primary');
                } else {
                    label.classList.remove('btn-primary');
                    label.classList.add('btn-outline-secondary');
                }
            }
        });
    }

    containerSizePills.addEventListener('change', function (e) {
        if (e.target.classList.contains('size-checkbox')) {
            updateSizePillStyles();
            rebuildVariantMatrix();
        }
    });

    document.getElementById('btnSelectAllSizes').addEventListener('click', function () {
        containerSizePills.querySelectorAll('.size-checkbox').forEach(cb => { cb.checked = true; });
        updateSizePillStyles();
        rebuildVariantMatrix();
    });

    document.getElementById('btnClearSizes').addEventListener('click', function () {
        containerSizePills.querySelectorAll('.size-checkbox').forEach(cb => { cb.checked = false; });
        updateSizePillStyles();
        rebuildVariantMatrix();
    });

    // Custom Size Adder
    document.getElementById('btnAddCustomSize').addEventListener('click', function () {
        const input = document.getElementById('inputCustomSize');
        const val = input.value.trim().toUpperCase();
        if (!val) return;

        let exists = false;
        containerSizePills.querySelectorAll('.size-checkbox').forEach(cb => {
            if (cb.value.toUpperCase() === val) exists = true;
        });

        if (!exists) {
            const safeId = 'size_pill_' + btoa(val).replace(/=/g, '');
            const div = document.createElement('div');
            div.className = 'form-check form-check-inline m-0';
            div.innerHTML = `
                <input class="btn-check size-checkbox" type="checkbox" id="${safeId}" name="sizes[]" value="${val}" checked autocomplete="off">
                <label class="btn btn-sm btn-primary px-3 py-1 fw-bold" for="${safeId}">${val}</label>
            `;
            containerSizePills.appendChild(div);
            input.value = '';
            updateSizePillStyles();
            rebuildVariantMatrix();
        } else {
            input.value = '';
        }
    });

    inputStyleCode.addEventListener('input', rebuildVariantMatrix);

    // 4. Variant Matrix Generator & Calculations
    function computeMargin(cost, selling) {
        if (!selling || selling <= 0) return 0;
        return (((selling - cost) / selling) * 100);
    }

    function rebuildVariantMatrix() {
        const sizes = getSelectedSizes();
        const colors = getSelectedColors();
        const baseStyleCode = (inputStyleCode.value || 'STY').trim();
        const defaultRate = supplierUnitCost > 0 ? supplierUnitCost : 0;
        const defaultMrp = parseFloat(batchInputMrp.value) || 0;
        const defaultSp = parseFloat(batchInputSp.value) || 0;

        // Preserve current user-entered values keyed by `${size}__${color}`
        const stateMap = {};
        tbodyVariantMatrix.querySelectorAll('tr[data-key]').forEach(tr => {
            const key = tr.getAttribute('data-key');
            const qtyRaw = tr.querySelector('.matrix-qty')?.value;
            stateMap[key] = {
                sku: tr.querySelector('.matrix-sku')?.value || '',
                rawQty: qtyRaw,
                qty: (qtyRaw !== undefined && qtyRaw !== null && qtyRaw.trim() !== '' && !isNaN(parseInt(qtyRaw, 10))) ? parseInt(qtyRaw, 10) : null,
                rate: parseFloat(tr.querySelector('.matrix-rate')?.value) || defaultRate,
                mrp: parseFloat(tr.querySelector('.matrix-mrp')?.value) || defaultMrp,
                sp: parseFloat(tr.querySelector('.matrix-sp')?.value) || defaultSp,
                barcode: tr.querySelector('.matrix-barcode')?.value || '',
                isComplementary: tr.querySelector('.matrix-complementary')?.checked || false,
            };
        });

        tbodyVariantMatrix.innerHTML = '';

        if (sizes.length === 0 || colors.length === 0) {
            tbodyVariantMatrix.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-circle text-warning me-1"></i>
                        ${sizes.length === 0 ? 'No sizes selected. ' : ''}${colors.length === 0 ? 'No colors selected. ' : ''}Check options above to generate the variant matrix table.
                    </td>
                </tr>
            `;
            updateAllocationStatus();
            return;
        }

        const totalCombinations = sizes.length * colors.length;
        const totalIntQty = Math.round(purchasedQty);
        const baseQty = totalCombinations > 0 ? Math.floor(totalIntQty / totalCombinations) : 0;
        const remainderQty = totalCombinations > 0 ? (totalIntQty % totalCombinations) : 0;

        let rowIndex = 0;
        colors.forEach(col => {
            const colShort = col.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase();
            sizes.forEach(sz => {
                const rowKey = `${sz}__${col}`;
                const existingState = stateMap[rowKey];
                const preExisting = preExistingVariants.find(v => v.size === sz && (!v.color || v.color.toLowerCase() === col.toLowerCase()));

                let defaultSku = `${baseStyleCode}-${sz}`;
                if (colors.length > 1) {
                    defaultSku = `${baseStyleCode}-${colShort}-${sz}`;
                }

                const defaultAlloc = baseQty + (rowIndex < remainderQty ? 1 : 0);
                const vSku = existingState?.sku || preExisting?.sku || defaultSku;
                const vQty = (existingState && existingState.qty !== null && !isNaN(existingState.qty)) 
                    ? existingState.qty 
                    : (existingState && existingState.rawQty === '' ? '' : (preExisting?.allocated_qty !== undefined ? preExisting.allocated_qty : defaultAlloc));
                const vRate = existingState?.rate || preExisting?.purchase_rate || defaultRate;
                const vMrp = existingState?.mrp || preExisting?.mrp || defaultMrp;
                const vSp = existingState?.sp || preExisting?.selling_price || defaultSp;
                const vBarcode = existingState?.barcode || preExisting?.barcode || vSku;
                const hasSavedComplementaryValue = preExisting
                    && preExisting.is_complementary !== null
                    && preExisting.is_complementary !== undefined;
                const vIsComplementary = existingState
                    ? existingState.isComplementary
                    : (hasSavedComplementaryValue
                        ? Number(preExisting.is_complementary) === 1
                        : (Boolean(selectedCategoryIsComplementary()) || legacyItemComplementary));

                const margin = computeMargin(vRate, vSp);
                let marginClass = 'bg-success-subtle text-success border border-success-subtle';
                if (margin <= 0) {
                    marginClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                } else if (margin < 20) {
                    marginClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                }

                const tr = document.createElement('tr');
                tr.setAttribute('data-key', rowKey);
                tr.setAttribute('data-size', sz);
                tr.setAttribute('data-color', col);
                tr.innerHTML = `
                    <td class="text-muted fw-bold">${rowIndex + 1}</td>
                    <td>
                        <span class="badge bg-primary px-2.5 py-1 fw-bold fs-7">${sz}</span>
                        <input type="hidden" name="variants[${rowIndex}][size]" value="${sz}">
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1 fw-semibold">${col}</span>
                        <input type="hidden" name="variants[${rowIndex}][color]" value="${col}">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm font-monospace matrix-sku"
                               name="variants[${rowIndex}][sku]" value="${vSku}" required>
                    </td>
                    <td>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm text-end fw-bold text-primary matrix-qty"
                               name="variants[${rowIndex}][allocated_qty]" value="${vQty}" required
                               placeholder="0" autocomplete="off" onfocus="this.select()">
                    </td>
                    <td>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-end matrix-rate numeric-text-input"
                               name="variants[${rowIndex}][purchase_rate]" value="${parseFloat(vRate).toFixed(2)}" required>
                    </td>
                    <td>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-end fw-semibold matrix-mrp numeric-text-input"
                               name="variants[${rowIndex}][mrp]" value="${parseFloat(vMrp).toFixed(2)}" required>
                        <div class="matrix-mrp-error text-danger fw-semibold small d-none" style="font-size:10px; line-height:1.1; margin-top:3px;">
                            <i class="bi bi-exclamation-triangle-fill me-0.5"></i>MRP &lt; SP
                        </div>
                    </td>
                    <td>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-end fw-bold text-dark matrix-sp numeric-text-input"
                               name="variants[${rowIndex}][selling_price]" value="${parseFloat(vSp).toFixed(2)}" required>
                        <div class="matrix-sp-error text-danger fw-semibold small d-none" style="font-size:10px; line-height:1.1; margin-top:3px;">
                            <i class="bi bi-exclamation-triangle-fill me-0.5"></i>SP &gt; MRP
                        </div>
                    </td>
                    <td>
                        <input type="hidden" name="variants[${rowIndex}][complementary_selected]" value="1">
                        <div class="form-check d-flex justify-content-center">
                            <input type="checkbox" class="form-check-input matrix-complementary"
                                   id="variantComplementary${rowIndex}"
                                   name="variants[${rowIndex}][is_complementary]" value="1"
                                   ${vIsComplementary ? 'checked' : ''}>
                            <label class="visually-hidden" for="variantComplementary${rowIndex}">Mark this variant as complementary</label>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${marginClass} matrix-margin fw-bold px-2 py-1">${margin.toFixed(2)}%</span>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info border border-info-subtle small">
                            <i class="bi bi-barcode me-1"></i>Auto on Save
                        </span>
                        <input type="hidden" class="matrix-barcode" name="variants[${rowIndex}][barcode]" value="${vBarcode}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-1.5 btn-remove-variant" title="Remove Variant">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbodyVariantMatrix.appendChild(tr);
                rowIndex++;
            });
        });

        attachMatrixEventListeners();
        updateAllocationStatus();
        validateAllRowsPricing();
    }

    // Row-level MRP vs SP validation
    function validateRowPricing(tr) {
        if (!tr) return true;
        const mrpInput = tr.querySelector('.matrix-mrp');
        const spInput = tr.querySelector('.matrix-sp');
        const mrpErr = tr.querySelector('.matrix-mrp-error');
        const spErr = tr.querySelector('.matrix-sp-error');
        const marginBadge = tr.querySelector('.matrix-margin');

        if (!mrpInput || !spInput) return true;

        const mrp = parseFloat(String(mrpInput.value || '').trim()) || 0;
        const sp = parseFloat(String(spInput.value || '').trim()) || 0;
        const rate = parseFloat(String(tr.querySelector('.matrix-rate')?.value || '').trim()) || supplierUnitCost;

        // ONLY invalid when both are positive numbers AND MRP is strictly lower than SP
        const isInvalid = (mrp > 0 && sp > 0 && mrp < sp);

        // Always calculate and display normal margin percentage (never replace with error text)
        const margin = computeMargin(rate, sp);
        if (marginBadge) {
            marginBadge.textContent = `${margin.toFixed(2)}%`;
            marginBadge.className = 'badge matrix-margin fw-bold px-2 py-1 ' +
                (margin <= 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' :
                (margin < 20 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle'));
        }

        if (isInvalid) {
            mrpInput.classList.add('is-invalid', 'border-danger');
            spInput.classList.add('is-invalid', 'border-danger');
            tr.classList.add('table-danger');
            if (mrpErr) mrpErr.classList.remove('d-none');
            if (spErr) spErr.classList.remove('d-none');
        } else {
            mrpInput.classList.remove('is-invalid', 'border-danger');
            spInput.classList.remove('is-invalid', 'border-danger');
            tr.classList.remove('table-danger');
            if (mrpErr) mrpErr.classList.add('d-none');
            if (spErr) spErr.classList.add('d-none');
        }

        return !isInvalid;
    }

    // Overall Matrix Pricing Validation
    function validateAllRowsPricing() {
        let allValid = true;
        let invalidCount = 0;
        const rows = tbodyVariantMatrix.querySelectorAll('tr[data-key]');
        rows.forEach(tr => {
            const valid = validateRowPricing(tr);
            if (!valid) {
                allValid = false;
                invalidCount++;
            }
        });

        const labelPricingStatus = document.getElementById('labelPricingStatus');
        if (labelPricingStatus) {
            if (!allValid) {
                labelPricingStatus.classList.remove('d-none');
                labelPricingStatus.innerHTML = `
                    <span class="badge bg-danger text-white py-1.5 px-3">
                        <i class="bi bi-exclamation-octagon-fill me-1.5"></i>Pricing Validation Error: MRP is lower than Selling Price in ${invalidCount} variant(s)
                    </span>
                `;
            } else {
                labelPricingStatus.classList.add('d-none');
                labelPricingStatus.innerHTML = '';
            }
        }

        return allValid;
    }

    function attachMatrixEventListeners() {
        tbodyVariantMatrix.querySelectorAll('.numeric-text-input').forEach(input => {
            if (input.classList.contains('matrix-qty')) return;
            input.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });
        });

        tbodyVariantMatrix.querySelectorAll('.matrix-qty').forEach(input => {
            // Strict numeric validation: only digits (0-9) allowed on keypress
            input.addEventListener('keypress', function (e) {
                if (e.which !== 0 && e.charCode !== 0) {
                    if (e.which < 48 || e.which > 57) {
                        e.preventDefault();
                    }
                }
            });

            // Prevent pasting non-digits
            input.addEventListener('paste', function (e) {
                const pasteText = (e.clipboardData || window.clipboardData)?.getData('text') || '';
                if (!/^\d+$/.test(pasteText.trim())) {
                    e.preventDefault();
                    const digits = pasteText.replace(/[^0-9]/g, '');
                    if (digits) {
                        document.execCommand('insertText', false, digits);
                    }
                }
            });

            // Sanitize input in real-time (e.g. mobile keypad, drag & drop)
            input.addEventListener('input', function () {
                const cleaned = this.value.replace(/[^0-9]/g, '');
                if (this.value !== cleaned) {
                    this.value = cleaned;
                }
                updateAllocationStatus();
            });

            // Ensure valid number on change / blur
            input.addEventListener('change', function () {
                if (this.value.trim() === '' || isNaN(parseInt(this.value, 10))) {
                    this.value = '0';
                } else {
                    this.value = String(parseInt(this.value, 10));
                }
                updateAllocationStatus();
            });

            input.addEventListener('keyup', updateAllocationStatus);
        });

        tbodyVariantMatrix.querySelectorAll('.matrix-rate, .matrix-sp, .matrix-mrp').forEach(input => {
            input.addEventListener('input', function () {
                const tr = this.closest('tr');
                validateRowPricing(tr);
                validateAllRowsPricing();
            });
            input.addEventListener('change', function () {
                const tr = this.closest('tr');
                validateRowPricing(tr);
                validateAllRowsPricing();
            });
        });

        tbodyVariantMatrix.querySelectorAll('.btn-remove-variant').forEach(btn => {
            btn.addEventListener('click', function () {
                const tr = this.closest('tr');
                tr.remove();
                updateAllocationStatus();
                validateAllRowsPricing();
            });
        });
    }

    function updateAllocationStatus() {
        let totalAllocated = 0;
        tbodyVariantMatrix.querySelectorAll('.matrix-qty').forEach(input => {
            totalAllocated += (parseInt(input.value, 10) || 0);
        });

        const targetQty = Math.round(purchasedQty);
        const diff = totalAllocated - targetQty;
        const isMatch = (diff === 0);

        if (isMatch) {
            labelAllocationStatus.innerHTML = `
                <span class="badge bg-success text-white py-1.5 px-3">
                    <i class="bi bi-check-circle-fill me-1.5"></i>Allocated: ${totalAllocated} / ${targetQty} Pieces (Exact Match)
                </span>
            `;
        } else {
            labelAllocationStatus.innerHTML = `
                <span class="badge bg-warning text-dark py-1.5 px-3">
                    <i class="bi bi-exclamation-triangle-fill me-1.5"></i>Allocated: ${totalAllocated} / ${targetQty} Pieces (${(diff > 0 ? '+' : '') + diff} variance)
                </span>
            `;
        }
    }

    // Auto-Distribute Quantities evenly across active variants
    window.autoDistributeQuantities = function () {
        const qtyInputs = Array.from(tbodyVariantMatrix.querySelectorAll('.matrix-qty'));
        const count = qtyInputs.length;
        if (count === 0) return;

        const totalInt = Math.round(purchasedQty);
        const base = Math.floor(totalInt / count);
        const remainder = totalInt % count;

        qtyInputs.forEach((inp, idx) => {
            inp.value = base + (idx < remainder ? 1 : 0);
        });

        updateAllocationStatus();
    };

    // Batch Replicate Pricing & Validation
    [batchInputMrp, batchInputSp].forEach(input => {
        if (!input) return;
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
        });
    });

    function validateBatchReplicator() {
        const mrp = parseFloat(String(batchInputMrp.value || '').trim()) || 0;
        const sp = parseFloat(String(batchInputSp.value || '').trim()) || 0;
        const errDiv = document.getElementById('batchPriceError');

        if (mrp > 0 && sp > 0 && mrp < sp) {
            batchInputMrp.classList.add('is-invalid', 'border-danger');
            batchInputSp.classList.add('is-invalid', 'border-danger');
            if (errDiv) errDiv.classList.remove('d-none');
            return false;
        } else {
            batchInputMrp.classList.remove('is-invalid', 'border-danger');
            batchInputSp.classList.remove('is-invalid', 'border-danger');
            if (errDiv) errDiv.classList.add('d-none');
            return true;
        }
    }

    batchInputMrp.addEventListener('input', validateBatchReplicator);
    batchInputSp.addEventListener('input', validateBatchReplicator);
    batchInputMrp.addEventListener('change', validateBatchReplicator);
    batchInputSp.addEventListener('change', validateBatchReplicator);

    btnReplicatePricing.addEventListener('click', function () {
        const mrp = parseFloat(batchInputMrp.value) || 0;
        const sp = parseFloat(batchInputSp.value) || 0;

        if (mrp > 0 && sp > 0 && mrp < sp) {
            validateBatchReplicator();
            alert(`Validation Error: MRP (₹${mrp.toFixed(2)}) cannot be lower than Selling Price (₹${sp.toFixed(2)}). Please adjust the batch pricing before replicating.`);
            batchInputMrp.focus();
            return;
        }

        tbodyVariantMatrix.querySelectorAll('tr').forEach(tr => {
            const mrpInput = tr.querySelector('.matrix-mrp');
            const spInput = tr.querySelector('.matrix-sp');
            if (mrpInput && mrp > 0) mrpInput.value = mrp.toFixed(2);
            if (spInput && sp > 0) spInput.value = sp.toFixed(2);

            validateRowPricing(tr);
        });

        validateAllRowsPricing();
    });

    // 5. Quick Add Modals (AJAX Integration)
    // Tier 1 Modal
    document.getElementById('btnSubmitModalTier1').addEventListener('click', function () {
        const nameInput = document.getElementById('modalTier1Name');
        const codeInput = document.getElementById('modalTier1Code');
        const name = nameInput.value.trim();
        const code = codeInput.value.trim().toUpperCase();
        if (!name) {
            alert('Please enter a Category name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('code', code);
        formData.append('catalog_source', 'THIRD_PARTY');
        const isComplementary = document.getElementById('modalTier1Complementary').checked ? '1' : '0';
        formData.append('is_complementary', isComplementary);
        formData.append('_csrf_token', '<?= CSRF::token() ?>');
        formData.append('csrf_token', '<?= CSRF::token() ?>');

        fetch(`${BASE_URL}/api/categories/quick-add`, {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.name;
                opt.setAttribute('data-code', data.code || code);
                opt.setAttribute('data-complementary', data.is_complementary ? '1' : '0');
                opt.selected = true;
                selectTier1.appendChild(opt);

                inputTier1Code.value = data.code || code;
                applyCategoryComplementaryDefault();
                updateHierarchySummary();
                bootstrap.Modal.getInstance(document.getElementById('modalQuickAddCategory')).hide();
                nameInput.value = '';
                codeInput.value = '';
                document.getElementById('modalTier1Complementary').checked = false;
            } else {
                alert(data.message || 'Error creating category.');
            }
        })
        .catch(err => alert('Failed to create category: ' + err.message));
    });

    // Tier 2 Modal
    document.getElementById('btnSubmitModalTier2').addEventListener('click', function () {
        const catId = selectTier1.value;
        if (!catId) {
            alert('Please select a Main Category (Tier 1) first.');
            return;
        }
        const nameInput = document.getElementById('modalTier2Name');
        const codeInput = document.getElementById('modalTier2Code');
        const name = nameInput.value.trim();
        const code = codeInput.value.trim().toUpperCase();
        if (!name) {
            alert('Please enter a Sub Category name.');
            return;
        }

        const formData = new FormData();
        formData.append('category_id', catId);
        formData.append('name', name);
        formData.append('code', code);
        formData.append('catalog_source', 'THIRD_PARTY');
        formData.append('_csrf_token', '<?= CSRF::token() ?>');
        formData.append('csrf_token', '<?= CSRF::token() ?>');

        fetch(`${BASE_URL}/api/subcategories/quick-add`, {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.name;
                opt.setAttribute('data-code', data.code || code);
                opt.selected = true;
                selectTier2.appendChild(opt);

                inputTier2Code.value = data.code || code;
                updateHierarchySummary();
                bootstrap.Modal.getInstance(document.getElementById('modalQuickAddSubcategory')).hide();
                nameInput.value = '';
                codeInput.value = '';
            } else {
                alert(data.message || 'Error creating subcategory.');
            }
        })
        .catch(err => alert('Failed to create subcategory: ' + err.message));
    });

    // Tier 3 Modal
    document.getElementById('btnSubmitModalTier3').addEventListener('click', function () {
        const subcatId = selectTier2.value;
        if (!subcatId) {
            alert('Please select a Sub Category (Tier 2) first.');
            return;
        }
        const nameInput = document.getElementById('modalTier3Name');
        const codeInput = document.getElementById('modalTier3Code');
        const name = nameInput.value.trim();
        const code = codeInput.value.trim().toUpperCase();
        if (!name) {
            alert('Please enter a Sub Sub Category name.');
            return;
        }

        const formData = new FormData();
        formData.append('subcategory_id', subcatId);
        formData.append('name', name);
        formData.append('code', code);
        formData.append('catalog_source', 'THIRD_PARTY');
        formData.append('_csrf_token', '<?= CSRF::token() ?>');
        formData.append('csrf_token', '<?= CSRF::token() ?>');

        fetch(`${BASE_URL}/api/sub-subcategories/quick-add`, {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.name;
                opt.setAttribute('data-code', data.code || code);
                opt.selected = true;
                selectTier3.appendChild(opt);

                inputTier3Code.value = data.code || code;
                updateHierarchySummary();
                bootstrap.Modal.getInstance(document.getElementById('modalQuickAddSubSubcategory')).hide();
                nameInput.value = '';
                codeInput.value = '';
            } else {
                alert(data.message || 'Error creating sub-subcategory.');
            }
        })
        .catch(err => alert('Failed to create sub-subcategory: ' + err.message));
    });

    // 6. Submit Validation
    const formMapProductBill = document.getElementById('formMapProductBill');

    formMapProductBill.addEventListener('submit', function (e) {
        if (!selectTier1.value) {
            e.preventDefault();
            alert('Please select Tier 1 Main Category.');
            selectTier1.focus();
            return false;
        }

        const variants = tbodyVariantMatrix.querySelectorAll('tr[data-key]');
        if (variants.length === 0) {
            e.preventDefault();
            alert('Please configure at least one variant combination in the matrix.');
            return false;
        }

        const pricingValid = validateAllRowsPricing();
        if (!pricingValid) {
            e.preventDefault();
            const firstInvalidTr = tbodyVariantMatrix.querySelector('tr.table-danger');
            if (firstInvalidTr) {
                const sz = firstInvalidTr.getAttribute('data-size') || '';
                const col = firstInvalidTr.getAttribute('data-color') || '';
                const mrp = parseFloat(firstInvalidTr.querySelector('.matrix-mrp')?.value) || 0;
                const sp = parseFloat(firstInvalidTr.querySelector('.matrix-sp')?.value) || 0;
                const invalidInput = (mrp < sp) ? (firstInvalidTr.querySelector('.matrix-mrp') || firstInvalidTr.querySelector('.matrix-sp')) : firstInvalidTr.querySelector('.matrix-sp');
                invalidInput?.focus();
                alert(`Pricing Validation Error: MRP (₹${mrp.toFixed(2)}) cannot be lower than Selling Price (₹${sp.toFixed(2)}) for variant ${sz} - ${col}. Please correct the pricing.`);
            } else {
                alert('Pricing Validation Error: MRP cannot be lower than Selling Price for one or more variants.');
            }
            return false;
        }

        let totalAllocated = 0;
        let zeroPricingFound = false;
        let zeroPricingTr = null;

        variants.forEach(tr => {
            const q = parseFloat(tr.querySelector('.matrix-qty')?.value) || 0;
            const mrp = parseFloat(tr.querySelector('.matrix-mrp')?.value) || 0;
            const sp = parseFloat(tr.querySelector('.matrix-sp')?.value) || 0;
            totalAllocated += q;

            if (mrp <= 0 || sp <= 0) {
                zeroPricingFound = true;
                if (!zeroPricingTr) zeroPricingTr = tr;
            }
        });

        if (zeroPricingFound && zeroPricingTr) {
            e.preventDefault();
            const sz = zeroPricingTr.getAttribute('data-size') || '';
            const col = zeroPricingTr.getAttribute('data-color') || '';
            alert(`Pricing Validation Error: Please enter valid MRP and Selling Price (greater than 0) for variant ${sz} - ${col}.`);
            zeroPricingTr.querySelector('.matrix-mrp')?.focus();
            return false;
        }

        if (Math.abs(totalAllocated - purchasedQty) > 0.001) {
            const proceed = confirm(`The allocated variant quantity (${totalAllocated}) differs from the purchased bill quantity (${purchasedQty}). Proceed anyway?`);
            if (!proceed) {
                e.preventDefault();
                return false;
            }
        }

        return true;
    });

    // Initial Matrix Render
    updateColorPillStyles();
    updateSizePillStyles();
    rebuildVariantMatrix();
    updateCategoryComplementaryHelp();
    updateHierarchySummary();
});
</script>
