<?php
$purchaseType = $purchase['purchase_type'] ?? 'FABRIC_ROLL';
$fabricItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'FABRIC_ROLL'));
$rawMaterialItems = array_values(array_filter($items, static fn(array $item): bool => in_array($item['item_type'], ['RAW_MATERIAL', 'OWN_PRODUCT'], true)));
$thirdPartyItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'THIRD_PARTY'));
$fabricDescription = $fabricItems[0]['item_name'] ?? '';
$unitOptions = ['Pieces', 'Kilograms', 'Metres', 'Litres', 'Boxes', 'Packs', 'Pairs', 'Sets', 'Bundles', 'Other'];

$normalizeUnit = static function(?string $unit): string {
    $u = strtoupper(trim((string)$unit));
    return match ($u) {
        'KG', 'KILOGRAM', 'KILOGRAMS' => 'Kilograms',
        'MTR', 'M', 'METRE', 'METRES', 'METER', 'METERS' => 'Metres',
        'LTR', 'L', 'LITRE', 'LITRES', 'LITER', 'LITERS' => 'Litres',
        'BOX', 'BOXES' => 'Boxes',
        'PACK', 'PACKS', 'PKT', 'PKTS' => 'Packs',
        'PAIR', 'PAIRS', 'PR', 'PRS' => 'Pairs',
        'SET', 'SETS' => 'Sets',
        'BUNDLE', 'BUNDLES', 'BDL', 'BDLS' => 'Bundles',
        'PCS', 'PIECE', 'PIECES', '' => 'Pieces',
        default => in_array($unit, ['Pieces', 'Kilograms', 'Metres', 'Litres', 'Boxes', 'Packs', 'Pairs', 'Sets', 'Bundles'], true) ? $unit : 'Other'
    };
};

$taxTypeForItem = static function (array $item): string {
    $type = $item['gst_type'] ?? '';
    if (in_array($type, ['SGST', 'CGST', 'IGST', 'BOTH', 'EXEMPTED'], true)) return $type;
    if ((float)($item['sgst_amount'] ?? 0) > 0 && (float)($item['cgst_amount'] ?? 0) > 0) return 'BOTH';
    if ((float)($item['igst_amount'] ?? 0) > 0) return 'IGST';
    if ((float)($item['sgst_amount'] ?? 0) > 0) return 'SGST';
    if ((float)($item['cgst_amount'] ?? 0) > 0) return 'CGST';
    return 'EXEMPTED';
};

$taxValueForItem = static function (array $item, string $type): float {
    if ($type === 'IGST') return (float)($item['igst_value'] ?? 0);
    if ($type === 'CGST') return (float)($item['cgst_value'] ?? 0);
    return (float)($item['sgst_value'] ?? 0);
};
?>

<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Record Purchase — <?= e($purchase['purchase_number']) ?></h2>
            <?php if ($purchaseType === 'THIRD_PARTY'): ?>
                <span class="badge bg-info-subtle text-info border fs-6"><i class="bi bi-box-seam me-1"></i>Third-Party Products</span>
            <?php elseif ($purchaseType === 'RAW_MATERIAL'): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border fs-6"><i class="bi bi-boxes me-1"></i>Raw Materials</span>
            <?php elseif ($purchaseType === 'OWN_PRODUCT'): ?>
                <span class="badge bg-success-subtle text-success border fs-6"><i class="bi bi-building-check me-1"></i>Own Production</span>
            <?php else: ?>
                <span class="badge bg-primary-subtle text-primary border fs-6"><i class="bi bi-layers-half me-1"></i>Fabric Roll</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
    </div>
</div>

<form method="POST" action="<?= url('production/purchases/update/' . $purchase['id']) ?>" enctype="multipart/form-data" id="editProductionPurchaseForm">
    <?= CSRF::field() ?>
    <input type="hidden" name="purchase_type" id="purchaseTypeInput" value="<?= e($purchaseType) ?>">

    <!-- 1. PURCHASE & SUPPLIER DETAILS -->
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Supplier Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Supplier *</label>
                    <input class="form-control" value="<?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?><?= $purchase['seller_company'] ? ' — ' . e($purchase['seller_name']) : '' ?>" disabled>
                </div>
                <div class="col-md-3"><label class="form-label required">Purchase Date *</label><input type="date" name="purchase_date" class="form-control" value="<?= e($purchase['purchase_date']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Invoice / Bill Number</label><input name="invoice_number" class="form-control" maxlength="100" value="<?= e($purchase['invoice_number']) ?>"></div>
                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 fw-semibold" for="editPurchaseDocuments">
                            <i class="bi bi-receipt-cutoff me-1 text-primary"></i>Tax Invoice / Bill Attachments
                        </label>
                        <div class="d-flex gap-1 align-items-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;">
                                <i class="bi bi-files me-1"></i>Multiple Allowed (5+)
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;" title="Images convert to AVIF">
                                <i class="bi bi-lightning-charge me-1"></i>Auto AVIF
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($documents)): ?>
                        <div class="mb-2 p-2 bg-light border rounded">
                            <div class="small fw-semibold text-secondary mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip me-1"></i>Attached Invoices (<?= count($documents) ?>)</span>
                                <span class="text-muted" style="font-size:10px;">Check box to remove upon saving</span>
                            </div>
                            <div class="row g-2">
                                <?php foreach ($documents as $doc): 
                                    $docUrl = $doc['document_url'] ?? '';
                                    $isAvif = str_ends_with(strtolower($docUrl), '.avif') || strtolower($doc['file_type'] ?? '') === 'avif';
                                    $isPdf = str_ends_with(strtolower($docUrl), '.pdf') || strtolower($doc['file_type'] ?? '') === 'pdf';
                                    $isImg = $isAvif || preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $docUrl);
                                ?>
                                    <div class="col-sm-6 col-md-4 col-lg-3" id="existingDocCard_<?= (int)$doc['id'] ?>">
                                        <div class="card h-100 border shadow-xs bg-white">
                                            <div class="card-body p-2 d-flex flex-column justify-content-between" style="font-size:11px;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;overflow:hidden;">
                                                        <?php if ($isImg): ?>
                                                            <img src="<?= uploadUrl($docUrl) ?>" alt="Invoice" style="max-width:42px;max-height:42px;object-fit:cover;">
                                                        <?php elseif ($isPdf): ?>
                                                            <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-file-earmark-text text-secondary fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-truncate flex-grow-1">
                                                        <div class="fw-semibold text-truncate" title="<?= e($doc['file_name'] ?: 'Invoice') ?>"><?= e($doc['file_name'] ?: 'Invoice') ?></div>
                                                        <span class="badge <?= $isAvif ? 'bg-success-subtle text-success' : ($isPdf ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary') ?>" style="font-size:9px;">
                                                            <?= $isAvif ? 'AVIF' : ($isPdf ? 'PDF' : strtoupper($doc['file_type'] ?? 'FILE')) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                                    <a href="<?= uploadUrl($docUrl) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:10px;">
                                                        <i class="bi bi-eye me-1"></i>View
                                                    </a>
                                                    <?php if (!empty($doc['id'])): ?>
                                                    <label class="form-check-label text-danger small mb-0" style="font-size:10px;cursor:pointer;">
                                                        <input type="checkbox" name="delete_document_ids[]" value="<?= (int)$doc['id'] ?>" class="form-check-input me-1">Remove
                                                    </label>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Add new/more invoice documents -->
                    <input type="file" name="documents[]" id="editPurchaseDocuments" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif,.pdf" multiple>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted" style="font-size:11px;">
                            Upload 1 or multiple new invoices (5+ supported). JPG, PNG, WEBP, AVIF or PDF up to 10 MB. Images automatically convert to <strong>AVIF</strong> format.
                        </small>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none text-decoration-none" id="btnEditClearAllDocs" onclick="clearAllEditSelectedDocFiles()" style="font-size:11px;">
                            <i class="bi bi-trash3 me-1"></i>Clear New
                        </button>
                    </div>

                    <!-- New multi-document preview container -->
                    <div id="editMultiDocumentPreviewContainer" class="d-none mt-2 p-2 bg-light border rounded">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                            <span class="small fw-bold text-dark" id="editSelectedDocCountLabel">
                                <i class="bi bi-paperclip me-1 text-primary"></i>0 new invoices selected
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">
                                <i class="bi bi-check2-circle me-1"></i>AVIF Conversion Active
                            </span>
                        </div>
                        <div class="row g-2" id="editDocPreviewCardsRow">
                            <!-- Dynamically populated new invoice preview cards -->
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-2"><label class="form-label" for="editPurchaseNotes">Purchase Notes</label><input name="notes" id="editPurchaseNotes" class="form-control" maxlength="500" value="<?= e($purchase['notes']) ?>" placeholder="Optional purchase notes or comments"></div>
                <div class="col-12">
                    <div class="border rounded bg-light p-3">
                        <div class="fw-semibold mb-3"><i class="bi bi-person-vcard me-1 text-primary"></i>Selected Supplier Information</div>
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Contact Person</small><?= e($purchase['seller_name'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Company</small><?= e($purchase['seller_company'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Phone</small><?= e($purchase['seller_phone'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Email</small><?= e($purchase['seller_email'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">GSTIN / Tax ID</small><?= e($purchase['seller_gst_number'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">City / State</small><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ') ?: '—') ?></div>
                            <div class="col-12"><small class="text-muted d-block">Business Address</small><?= e($purchase['seller_address'] ?: '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. RAW MATERIALS SECTION -->
    <?php if ($purchaseType === 'RAW_MATERIAL' || $purchaseType === 'OWN_PRODUCT'): ?>
    <div class="card shadow-sm border mb-4" id="sectionRawMaterial">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2 text-warning"></i>Raw Materials Supply Details</h5>
            </div>
            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" id="btnAddRawMaterialItem">
                <i class="bi bi-plus-circle me-1"></i> Add Raw Material
            </button>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-boxes me-1 text-warning"></i>Individual Raw Materials (<span id="rmCountBadge"><?= count($rawMaterialItems) ?></span>)</h6>
            </div>

            <div id="rawMaterialContainer" class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($rawMaterialItems as $rmIdx => $item): ?>
                    <?php
                        $rmId = (int)$item['id'];
                        $gstType = $item['gst_type'] ?? 'EXEMPTED';
                        $taxTreatment = $item['tax_treatment'] ?? 'INCLUDED';
                        $taxMode = $item['tax_input_type'] ?? 'PERCENT';
                        $sgstVal = (float)($item['sgst_value'] ?? 0);
                        $cgstVal = (float)($item['cgst_value'] ?? 0);
                        $igstVal = (float)($item['igst_value'] ?? 0);
                        $taxVal = ($gstType === 'IGST') ? $igstVal : ($gstType === 'CGST' ? $cgstVal : $sgstVal);
                        $autoSku = $item['vendor_sku'] ?: ($item['product_code'] ?: ('RM-MAT' . sprintf('%02d', $rmIdx + 1)));
                        $currentUnit = $normalizeUnit($item['unit'] ?? 'Kilograms');
                        $isCustomUnit = ($currentUnit === 'Other');
                    ?>
                    <div class="rm-card card border shadow-sm mb-3 rm-row" data-index="<?= $rmIdx ?>" data-item-id="<?= $rmId ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <strong><i class="bi bi-boxes text-warning me-1"></i><span class="rm-title">MATERIAL #<?= $rmIdx + 1 ?></span></strong>
                                <span class="badge bg-secondary-subtle text-secondary border rm-name-badge <?= empty($item['item_name']) ? 'd-none' : '' ?>"><?= e(strtoupper($item['item_name'])) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 rm-card-calc" style="font-size: 12px;">
                                    Base: <span class="rm-badge-base">₹0.00</span> | Tax: <span class="rm-badge-tax">₹0.00</span> | Total: <strong class="rm-badge-total">₹0.00</strong>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-rm"><i class="bi bi-trash me-1"></i>Delete</button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <input type="hidden" class="rm-id" name="items[<?= $rmId ?>][id]" value="<?= $rmId ?>">
                            <input type="hidden" class="rm-sku" name="items[<?= $rmId ?>][vendor_sku]" value="<?= e($autoSku) ?>">
                            <input type="hidden" class="rm-item-type" name="items[<?= $rmId ?>][item_type]" value="RAW_MATERIAL">
                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label required">Material / Item Name *</label>
                                    <input class="form-control rm-name text-uppercase" name="items[<?= $rmId ?>][item_name]" maxlength="255" value="<?= e($item['item_name']) ?>" placeholder="E.G. 30S COMBED COTTON YARN / SEWING THREAD" list="yarnSuggestions" required>
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label">Color / Shade</label>
                                    <input class="form-control rm-color text-uppercase" name="items[<?= $rmId ?>][color]" maxlength="100" value="<?= e($item['color'] ?? '') ?>" placeholder="E.G. RAW WHITE / NAVY BLUE" list="colorDataList">
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label">Count / Size</label>
                                    <input class="form-control rm-size text-uppercase" name="items[<?= $rmId ?>][size]" maxlength="50" value="<?= e($item['size'] ?? '') ?>" placeholder="E.G. 30S / 40/2 / 1 INCH" list="countSizeSuggestions">
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label required">Unit *</label>
                                    <select class="form-select rm-unit fw-semibold" name="items[<?= $rmId ?>][unit]">
                                        <?php foreach ($unitOptions as $uOpt): ?>
                                            <option value="<?= e($uOpt) ?>" <?= ($currentUnit === $uOpt) ? 'selected' : '' ?>><?= e($uOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control form-control-sm rm-custom-unit mt-1 <?= $isCustomUnit ? '' : 'd-none' ?>" name="items[<?= $rmId ?>][custom_unit]" value="<?= $isCustomUnit ? e($item['unit']) : '' ?>" placeholder="Enter custom unit">
                                </div>
                                <div class="col-lg-1 col-md-3">
                                    <label class="form-label required rm-qty-label">Qty (<?= e($isCustomUnit ? ($item['unit'] ?: 'Kilograms') : $currentUnit) ?>) *</label>
                                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" name="items[<?= $rmId ?>][quantity]" class="form-control rm-quantity numeric-text-input fw-bold text-end" value="<?= (float)$item['quantity'] ?>" required>
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label required">Purchase Price (₹) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text p-1">₹</span>
                                        <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" name="items[<?= $rmId ?>][unit_price]" class="form-control rm-cost numeric-text-input fw-semibold text-end" value="<?= number_format((float)$item['unit_price'], 2, '.', '') ?>" required>
                                    </div>
                                </div>

                                <!-- Individual GST & Tax options -->
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <div class="p-2.5 rounded border bg-light-subtle d-flex flex-wrap align-items-center gap-3">
                                        <div style="min-width: 200px; max-width: 260px;" class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label mb-0 small fw-semibold">HSN Code <span class="text-danger">*</span></label>
                                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold btn-add-hsn-trigger" style="font-size: 11px;" title="Add new HSN Code">
                                                    <i class="bi bi-plus-circle me-0.5"></i>+ Add HSN
                                                </button>
                                            </div>
                                            <div class="dropdown hsn-dropdown w-100 position-relative">
                                                <input type="hidden" name="items[<?= $rmId ?>][hsn_code]" class="rm-hsn" value="<?= e($item['hsn_code'] ?? '') ?>">
                                                <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center hsn-dropdown-btn font-monospace py-1.5 px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="font-size: 0.82rem; height: 35px;">
                                                    <span class="hsn-btn-text text-truncate">
                                                        <?php if (!empty($item['hsn_code'])): ?>
                                                            <span class="fw-bold text-primary"><?= e($item['hsn_code']) ?></span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;"><?= (float)($taxVal + $cgstVal > 0 ? ($taxVal + $cgstVal) : 5) ?>% GST</span>
                                                        <?php else: ?>
                                                            -- Select HSN --
                                                        <?php endif; ?>
                                                    </span>
                                                </button>
                                                <div class="dropdown-menu shadow-lg p-2 hsn-dropdown-menu" style="min-width: 280px; max-width: 340px; z-index: 1055;">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                        <input type="text" class="form-control border-start-0 hsn-search-input" placeholder="Search HSN code or item..." autocomplete="off">
                                                    </div>
                                                    <div class="overflow-auto hsn-options-list" style="max-height: 200px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="min-width: 170px;">
                                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                                            <select name="items[<?= $rmId ?>][gst_type]" class="form-select form-select-sm item-gst-type">
                                                <option value="BOTH" <?= $gstType === 'BOTH' ? 'selected' : '' ?>>Both (CGST and SGST)</option>
                                                <option value="IGST" <?= $gstType === 'IGST' ? 'selected' : '' ?>>IGST</option>
                                                <option value="CGST" <?= $gstType === 'CGST' ? 'selected' : '' ?>>CGST</option>
                                                <option value="SGST" <?= $gstType === 'SGST' ? 'selected' : '' ?>>SGST</option>
                                                <option value="EXEMPTED" <?= $gstType === 'EXEMPTED' ? 'selected' : '' ?>>Exempted</option>
                                            </select>
                                        </div>
                                        <div style="min-width: 170px;">
                                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                                            <select name="items[<?= $rmId ?>][tax_treatment]" class="form-select form-select-sm item-tax-treatment rm-tax-treatment">
                                                <option value="INCLUDED" <?= $taxTreatment === 'INCLUDED' ? 'selected' : '' ?>>GST Included</option>
                                                <option value="EXCLUDED" <?= $taxTreatment === 'EXCLUDED' ? 'selected' : '' ?>>GST Excluded</option>
                                            </select>
                                        </div>
                                        <div class="tax-mode-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>" style="min-width: 130px;">
                                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                                            <select name="items[<?= $rmId ?>][tax_mode]" class="form-select form-select-sm item-tax-mode">
                                                <option value="PERCENT" <?= $taxMode === 'PERCENT' ? 'selected' : '' ?>>Percentage (%)</option>
                                                <option value="AMOUNT" <?= $taxMode === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option>
                                            </select>
                                        </div>
                                        <div class="tax-value-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>" style="min-width: 100px;">
                                            <label class="form-label mb-1 small fw-semibold item-tax-label"><?= $gstType === 'IGST' ? 'IGST (%)' : ($gstType === 'CGST' ? 'CGST (%)' : 'SGST (%)') ?></label>
                                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" name="items[<?= $rmId ?>][tax_value]" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold" value="<?= (float)$taxVal ?>">
                                        </div>
                                        <div class="tax-cgst-field <?= $gstType === 'BOTH' ? '' : 'd-none' ?>" style="min-width: 100px;">
                                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" name="items[<?= $rmId ?>][cgst_tax_value]" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold" value="<?= (float)$cgstVal ?>">
                                        </div>
                                        <div class="ms-auto text-end">
                                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                                            <strong class="text-success fs-6 rm-line-total">₹0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Live Details Table -->
            <div class="card border shadow-sm mb-4" id="rmDetailsTableContainer">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-warning"></i>Raw Materials Supply &amp; Details Table</span>
                    <span class="badge bg-secondary-subtle text-secondary border" id="rmTableCountBadge"><?= count($rawMaterialItems) ?> Items</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 35px;">#</th>
                                <th>Material / Item Name</th>
                                <th>Code / SKU</th>
                                <th>Color / Shade</th>
                                <th>Count / Size</th>
                                <th>Unit</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Purchase Price (₹)</th>
                                <th class="text-end">Base Value</th>
                                <th>HSN</th>
                                <th>GST Type</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end bg-success-subtle text-success">Total Value</th>
                            </tr>
                        </thead>
                        <tbody id="rmDetailsTableBody"></tbody>
                        <tfoot id="rmDetailsTableFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>

            <!-- Batch Metrics Summary -->
            <div class="p-3 bg-warning-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong class="text-warning-emphasis"><i class="bi bi-calculator me-1"></i>Raw Materials Summary</strong>
                </div>
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div><small class="text-muted d-block">Total Materials</small><strong id="summaryRmItems">0 Items</strong></div>
                    <div><small class="text-muted d-block">Total Quantity</small><strong id="summaryRmUnits">0 Units</strong></div>
                    <div><small class="text-muted d-block">Base Total</small><strong id="summaryRmBase" class="text-secondary">₹0.00</strong></div>
                    <div><small class="text-muted d-block">Total Tax</small><strong id="summaryRmTax" class="text-secondary">₹0.00</strong></div>
                    <div><small class="text-muted d-block">Total Inward Cost</small><strong id="summaryRmTotal" class="text-success fs-5">₹0.00</strong></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. THIRD-PARTY PRODUCTS SECTION -->
    <?php if ($purchaseType === 'THIRD_PARTY'): ?>
    <div class="card shadow-sm border mb-4" id="sectionThirdParty">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-info"></i>Third-Party Finished Products Inward</h5>
            </div>
            <button type="button" class="btn btn-sm btn-info text-white fw-semibold" id="btnAddThirdPartyItem">
                <i class="bi bi-plus-circle me-1"></i> Add Third-Party Product
            </button>
        </div>
        <div class="card-body">
            <!-- Individual Cards Header -->
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-boxes me-1 text-info"></i>Individual Third-Party Products (<span id="tpCountBadge"><?= count($thirdPartyItems) ?></span>)</h6>
            </div>

            <!-- Pre-rendered Third-Party Cards -->
            <div id="thirdPartyContainer" class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($thirdPartyItems as $tpIdx => $item): ?>
                    <?php
                        $tpId = (int)$item['id'];
                        $gstType = $item['gst_type'] ?? 'EXEMPTED';
                        $taxTreatment = $item['tax_treatment'] ?? 'INCLUDED';
                        $taxMode = $item['tax_input_type'] ?? 'PERCENT';
                        $sgstVal = (float)($item['sgst_value'] ?? 0);
                        $cgstVal = (float)($item['cgst_value'] ?? 0);
                        $igstVal = (float)($item['igst_value'] ?? 0);
                        $taxVal = ($gstType === 'IGST') ? $igstVal : ($gstType === 'CGST' ? $cgstVal : $sgstVal);
                        $autoSku = $item['vendor_sku'] ?: ($item['product_code'] ?: ('TP-PROD' . sprintf('%02d', $tpIdx + 1)));
                        $currentUnit = $normalizeUnit($item['unit'] ?? 'Pieces');
                        $isCustomUnit = ($currentUnit === 'Other');
                    ?>
                    <div class="tp-card card border shadow-sm mb-3 tp-row" data-index="<?= $tpIdx ?>" data-item-id="<?= $tpId ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <strong><i class="bi bi-box-seam text-info me-1"></i><span class="tp-title">PRODUCT #<?= $tpIdx + 1 ?></span></strong>
                                <span class="badge bg-secondary-subtle text-secondary border tp-name-badge <?= empty($item['item_name']) ? 'd-none' : '' ?>"><?= e(strtoupper($item['item_name'])) ?></span>
                                <span class="badge bg-primary-subtle text-primary border font-monospace tp-sku-badge fw-bold">
                                    <i class="bi bi-upc-scan me-1"></i>SKU: <span class="tp-sku-text"><?= e($autoSku) ?></span>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 tp-card-calc" style="font-size: 12px;">
                                    Base: <span class="tp-badge-base">₹0.00</span> | Tax: <span class="tp-badge-tax">₹0.00</span> | Total: <strong class="tp-badge-total">₹0.00</strong>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-tp"><i class="bi bi-trash me-1"></i>Delete</button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <input type="hidden" class="tp-id" name="items[<?= $tpId ?>][id]" value="<?= $tpId ?>">
                            <input type="hidden" class="tp-sku" name="items[<?= $tpId ?>][vendor_sku]" value="<?= e($autoSku) ?>">
                            <input type="hidden" class="tp-item-type" name="items[<?= $tpId ?>][item_type]" value="THIRD_PARTY">
                            <div class="row g-3">
                                <div class="col-lg-5 col-md-4">
                                    <label class="form-label required">Product / Item Name *</label>
                                    <input class="form-control tp-name text-uppercase" name="items[<?= $tpId ?>][item_name]" maxlength="255" value="<?= e($item['item_name']) ?>" placeholder="E.G. MEN SLIM FIT COTTON SHIRT" required>
                                </div>
                                <div class="col-lg-2 col-md-2">
                                    <label class="form-label required">Unit *</label>
                                    <select class="form-select tp-unit fw-semibold" name="items[<?= $tpId ?>][unit]">
                                        <?php foreach ($unitOptions as $uOpt): ?>
                                            <option value="<?= e($uOpt) ?>" <?= ($currentUnit === $uOpt) ? 'selected' : '' ?>><?= e($uOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control form-control-sm tp-custom-unit mt-1 <?= $isCustomUnit ? '' : 'd-none' ?>" name="items[<?= $tpId ?>][custom_unit]" value="<?= $isCustomUnit ? e($item['unit']) : '' ?>" placeholder="Enter custom unit">
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label required tp-qty-label">Qty (<?= e($isCustomUnit ? ($item['unit'] ?: 'Pieces') : $currentUnit) ?>) *</label>
                                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" name="items[<?= $tpId ?>][quantity]" class="form-control tp-quantity numeric-text-input fw-bold text-end" value="<?= (float)$item['quantity'] ?>" required>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <label class="form-label required">Purchase Price (₹) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text p-1">₹</span>
                                        <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" name="items[<?= $tpId ?>][unit_price]" class="form-control tp-cost numeric-text-input fw-semibold text-end" value="<?= number_format((float)$item['unit_price'], 2, '.', '') ?>" required>
                                    </div>
                                </div>

                                <!-- Individual GST & Tax options directly near product fields! -->
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <div class="p-2.5 rounded border bg-light-subtle d-flex flex-wrap align-items-center gap-3">
                                        <div style="min-width: 200px; max-width: 260px;" class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label mb-0 small fw-semibold">HSN Code <span class="text-danger">*</span></label>
                                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold btn-add-hsn-trigger" style="font-size: 11px;" title="Add new HSN Code">
                                                    <i class="bi bi-plus-circle me-0.5"></i>+ Add HSN
                                                </button>
                                            </div>
                                            <div class="dropdown hsn-dropdown w-100 position-relative">
                                                <input type="hidden" name="items[<?= $tpId ?>][hsn_code]" class="tp-hsn" value="<?= e($item['hsn_code'] ?? '') ?>">
                                                <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center hsn-dropdown-btn font-monospace py-1.5 px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="font-size: 0.82rem; height: 35px;">
                                                    <span class="hsn-btn-text text-truncate">
                                                        <?php if (!empty($item['hsn_code'])): ?>
                                                            <span class="fw-bold text-primary"><?= e($item['hsn_code']) ?></span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;"><?= (float)($taxVal + $cgstVal > 0 ? ($taxVal + $cgstVal) : 5) ?>% GST</span>
                                                        <?php else: ?>
                                                            -- Select HSN --
                                                        <?php endif; ?>
                                                    </span>
                                                </button>
                                                <div class="dropdown-menu shadow-lg p-2 hsn-dropdown-menu" style="min-width: 280px; max-width: 340px; z-index: 1055;">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                        <input type="text" class="form-control border-start-0 hsn-search-input" placeholder="Search HSN code or item..." autocomplete="off">
                                                    </div>
                                                    <div class="overflow-auto hsn-options-list" style="max-height: 200px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="min-width: 170px;">
                                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                                            <select name="items[<?= $tpId ?>][gst_type]" class="form-select form-select-sm item-gst-type">
                                                <option value="BOTH" <?= $gstType === 'BOTH' ? 'selected' : '' ?>>Both (CGST and SGST)</option>
                                                <option value="IGST" <?= $gstType === 'IGST' ? 'selected' : '' ?>>IGST</option>
                                                <option value="CGST" <?= $gstType === 'CGST' ? 'selected' : '' ?>>CGST</option>
                                                <option value="SGST" <?= $gstType === 'SGST' ? 'selected' : '' ?>>SGST</option>
                                                <option value="EXEMPTED" <?= $gstType === 'EXEMPTED' ? 'selected' : '' ?>>Exempted</option>
                                            </select>
                                        </div>
                                        <div style="min-width: 170px;">
                                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                                            <select name="items[<?= $tpId ?>][tax_treatment]" class="form-select form-select-sm item-tax-treatment tp-tax-treatment">
                                                <option value="INCLUDED" <?= $taxTreatment === 'INCLUDED' ? 'selected' : '' ?>>GST Included</option>
                                                <option value="EXCLUDED" <?= $taxTreatment === 'EXCLUDED' ? 'selected' : '' ?>>GST Excluded</option>
                                            </select>
                                        </div>
                                        <div class="tax-mode-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>" style="min-width: 130px;">
                                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                                            <select name="items[<?= $tpId ?>][tax_mode]" class="form-select form-select-sm item-tax-mode">
                                                <option value="PERCENT" <?= $taxMode === 'PERCENT' ? 'selected' : '' ?>>Percentage (%)</option>
                                                <option value="AMOUNT" <?= $taxMode === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option>
                                            </select>
                                        </div>
                                        <div class="tax-value-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>" style="min-width: 100px;">
                                            <label class="form-label mb-1 small fw-semibold item-tax-label"><?= $gstType === 'IGST' ? 'IGST (%)' : ($gstType === 'CGST' ? 'CGST (%)' : 'SGST (%)') ?></label>
                                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" name="items[<?= $tpId ?>][tax_value]" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold" value="<?= (float)$taxVal ?>">
                                        </div>
                                        <div class="tax-cgst-field <?= $gstType === 'BOTH' ? '' : 'd-none' ?>" style="min-width: 100px;">
                                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" name="items[<?= $tpId ?>][cgst_tax_value]" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold" value="<?= (float)$cgstVal ?>">
                                        </div>
                                        <div class="ms-auto text-end">
                                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                                            <strong class="text-success fs-6 tp-line-total">₹0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Live Details Table -->
            <div class="card border shadow-sm mb-4" id="tpDetailsTableContainer">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-info"></i>Third-Party Products Inward &amp; Details Table</span>
                    <span class="badge bg-secondary-subtle text-secondary border" id="tpTableCountBadge"><?= count($thirdPartyItems) ?> Products</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 35px;">#</th>
                                <th>Product / Item Name</th>
                                <th>Auto SKU</th>
                                <th>Unit</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Purchase Price (₹)</th>
                                <th class="text-end">Base Value</th>
                                <th>HSN</th>
                                <th>GST Type</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end bg-success-subtle text-success">Total Value</th>
                            </tr>
                        </thead>
                        <tbody id="tpDetailsTableBody"></tbody>
                        <tfoot id="tpDetailsTableFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>

            <!-- Batch Metrics Summary -->
            <div class="p-3 bg-info-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong class="text-info-emphasis"><i class="bi bi-calculator me-1"></i>Third-Party Inward Summary</strong>
                </div>
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div><small class="text-muted d-block">Total Products</small><strong id="summaryTpProducts">0 Products</strong></div>
                    <div><small class="text-muted d-block">Buyed Product Counts</small><strong id="summaryTpUnits">0 Pcs</strong></div>
                    <div><small class="text-muted d-block">Total Buyed Cost</small><strong id="summaryTpTotal" class="text-secondary">₹0.00</strong></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. FABRIC ROLL SECTION -->
    <?php if ($purchaseType === 'FABRIC_ROLL'): ?>
    <div class="card shadow-sm border mb-4" id="sectionFabricRoll">
        <div class="card-header bg-white py-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll &amp; Material Supply Details</h5>
                </div>
                <div class="col-12">
                    <label class="form-label required" for="editFabricDescription">Fabric / Material Description *</label>
                    <input type="text" id="editFabricDescription" class="form-control text-uppercase" maxlength="150" placeholder="E.G. 100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)" value="<?= e($fabricDescription) ?>" <?= $fabricItems ? 'required' : '' ?>>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-stack me-1 text-primary"></i>Individual Fabric Rolls (<span id="rollCountBadge"><?= count($fabricItems) ?></span>)</h6>
            </div>
            <div id="rollsContainer" class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($fabricItems as $index => $item): ?>
                    <?php
                    $key = (int)$item['id'];
                    $gstType = $taxTypeForItem($item);
                    $taxValue = $taxValueForItem($item, $gstType);
                    $cgstValue = $gstType === 'BOTH' ? (float)($item['cgst_value'] ?? 0) : 0;
                    $startCode = $item['start_code'] ?? '';
                    $prefix = '';
                    if (!empty($startCode) && preg_match('/^([A-Za-z]{2}\d{4})/', $startCode, $m)) {
                        $prefix = $m[1];
                    } elseif (!empty($startCode) && preg_match('/^([A-Za-z0-9_-]+?)(\d{2,})$/', $startCode, $m)) {
                        $prefix = $m[1];
                    } else {
                        $prefix = 'FB' . date('my', strtotime($purchase['purchase_date']));
                    }
                    $startSeq = (int)($item['start_seq'] ?? 0);
                    if ($startSeq <= 0 && !empty($startCode) && preg_match('/(\d+)$/', $startCode, $m)) {
                        $startSeq = (int)$m[1];
                    }
                    if ($startSeq <= 0) $startSeq = 1;
                    ?>
                    <div class="roll-card card border shadow-sm" data-item-key="<?= $key ?>" data-item-id="<?= $key ?>" data-sequence-start="<?= $startSeq ?>" data-code-prefix="<?= e($prefix) ?>" data-start-code="<?= e($item['start_code'] ?? '') ?>" data-photo-url="<?= e(!empty($item['photo_url']) ? uploadUrl($item['photo_url']) : '') ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <strong><i class="bi bi-layers text-primary me-1"></i><span class="roll-title">ROLL #<?= $index + 1 ?></span>
                                <span class="badge bg-dark roll-code-text"><?= e($item['start_code'] ?? '') ?><?= !empty($item['end_code']) && $item['end_code'] !== ($item['start_code'] ?? '') ? '–' . e($item['end_code']) : '' ?></span>
                            </strong>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-roll" title="Delete roll"><i class="bi bi-trash me-1"></i>Delete</button>
                        </div>
                        <div class="card-body p-3">
                            <input type="hidden" name="items[<?= $key ?>][id]" value="<?= $key ?>">
                            <input type="hidden" name="items[<?= $key ?>][item_type]" value="FABRIC_ROLL">
                            <input type="hidden" name="items[<?= $key ?>][item_name]" class="roll-item-name" value="<?= e($item['item_name']) ?>">
                            <input type="hidden" name="items[<?= $key ?>][unit]" value="PCS">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Roll No. / Code *</label><input name="items[<?= $key ?>][roll_number]" class="form-control roll-number text-uppercase" maxlength="50" value="<?= e($item['roll_number']) ?>" required></div>
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Color / Shade *</label><input name="items[<?= $key ?>][color]" class="form-control roll-color text-uppercase" maxlength="100" value="<?= e($item['color']) ?>" required></div>
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Design Name / Pattern *</label><input name="items[<?= $key ?>][design_name]" class="form-control roll-design text-uppercase" maxlength="150" value="<?= e($item['design_name']) ?>" required></div>
                                <div class="col-lg-1 col-md-3"><label class="form-label required">Designs *</label><input type="text" inputmode="numeric" pattern="[0-9]+" data-min="1" data-step="1" name="items[<?= $key ?>][design_count]" class="form-control roll-design-count numeric-text-input" value="<?= (int)$item['design_count'] ?>" required></div>
                                <div class="col-lg-1 col-md-3">
                                    <label class="form-label required">RQ *</label>
                                    <input type="text" inputmode="numeric" pattern="[0-9]+" data-min="1" data-step="1" name="items[<?= $key ?>][quantity]" class="form-control roll-quantity numeric-text-input" value="<?= max(1, (int)$item['quantity']) ?>" required>
                                </div>
                                <div class="col-lg-2 col-md-3"><label class="form-label required">Weight (kg) *</label><div class="input-group"><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" name="items[<?= $key ?>][weight_kg]" class="form-control roll-weight numeric-text-input" value="<?= e($item['weight_kg']) ?>" required><span class="input-group-text">kg</span></div></div>
                                <div class="col-lg-2 col-md-3"><label class="form-label required">Purchase Price (₹) *</label><div class="input-group"><span class="input-group-text">₹</span><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" name="items[<?= $key ?>][unit_price]" class="roll-price form-control numeric-text-input" value="<?= e($item['unit_price']) ?>" required></div></div>
                                <div class="col-lg-2 col-md-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">HSN Code</label>
                                        <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none" onclick="openQuickAddHsnModal(this)">
                                            <i class="bi bi-plus-circle"></i> Add HSN
                                        </button>
                                    </div>
                                    <div class="position-relative hsn-dropdown-wrapper">
                                        <input type="hidden" name="items[<?= $key ?>][hsn_code]" class="roll-hsn" value="<?= e($item['hsn_code'] ?? '') ?>">
                                        <button type="button" class="form-select text-start d-flex justify-content-between align-items-center hsn-dropdown-btn">
                                            <span class="hsn-btn-text font-monospace"><?= !empty($item['hsn_code']) ? e($item['hsn_code']) : '-- Select HSN --' ?></span>
                                        </button>
                                        <div class="hsn-dropdown border rounded bg-white shadow-lg p-2 d-none position-absolute" style="z-index: 1060; width: 100%; min-width: 250px;">
                                            <input type="text" class="form-control form-control-sm mb-2 hsn-search-input" placeholder="Search HSN code...">
                                            <div class="hsn-list overflow-auto" style="max-height: 180px;">
                                                <div class="p-1 hsn-opt-item rounded cursor-pointer small" data-hsn="" data-gst="0">-- None --</div>
                                                <?php foreach ($hsnCodes as $h): ?>
                                                    <div class="p-1 hsn-opt-item rounded cursor-pointer small <?= (($item['hsn_code'] ?? '') === $h['hsn_code']) ? 'active bg-primary text-white' : '' ?>" data-hsn="<?= e($h['hsn_code']) ?>" data-gst="<?= (float)$h['gst_rate'] ?>">
                                                        <strong class="font-monospace"><?= e($h['hsn_code']) ?></strong> - <?= (float)$h['gst_rate'] ?>% GST <span class="text-muted d-block" style="font-size:10px"><?= e($h['description'] ?? '') ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label">Tax Treatment</label>
                                    <select name="items[<?= $key ?>][tax_treatment]" class="form-select item-tax-treatment">
                                        <option value="INCLUDED" <?= ($item['tax_treatment'] ?? 'INCLUDED') === 'INCLUDED' ? 'selected' : '' ?>>Included in Price</option>
                                        <option value="EXCLUDED" <?= ($item['tax_treatment'] ?? '') === 'EXCLUDED' ? 'selected' : '' ?>>Excluded (+ GST)</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <label class="form-label">GST</label>
                                    <select name="items[<?= $key ?>][gst_type]" class="form-select item-gst-type">
                                        <option value="EXEMPTED" <?= $gstType === 'EXEMPTED' ? 'selected' : '' ?>>Exempted</option>
                                        <option value="BOTH" <?= $gstType === 'BOTH' ? 'selected' : '' ?>>Both (CGST and SGST)</option>
                                        <option value="SGST" <?= $gstType === 'SGST' ? 'selected' : '' ?>>SGST</option>
                                        <option value="CGST" <?= $gstType === 'CGST' ? 'selected' : '' ?>>CGST</option>
                                        <option value="IGST" <?= $gstType === 'IGST' ? 'selected' : '' ?>>IGST</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-3 tax-mode-field">
                                    <label class="form-label">Tax Input Type</label>
                                    <select name="items[<?= $key ?>][tax_mode]" class="form-select item-tax-mode">
                                        <option value="PERCENT" <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'selected' : '' ?>>Percentage (%)</option>
                                        <option value="AMOUNT" <?= ($item['tax_input_type'] ?? '') === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-3 tax-value-field">
                                    <label class="form-label item-tax-label">Tax</label>
                                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" name="items[<?= $key ?>][tax_value]" class="form-control item-tax-value numeric-text-input" value="<?= $taxValue ?>">
                                </div>
                                <div class="col-lg-2 col-md-3 tax-cgst-field <?= $gstType === 'BOTH' ? '' : 'd-none' ?>">
                                    <label class="form-label item-cgst-label">CGST</label>
                                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" name="items[<?= $key ?>][cgst_tax_value]" class="form-control item-cgst-value numeric-text-input" value="<?= $cgstValue ?>">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Roll Sample Photo <small class="text-muted">(Optional — JPG, PNG, WEBP)</small></label>
                                    <input type="file" name="roll_photo_<?= $key ?>" class="form-control roll-photo" accept=".jpg,.jpeg,.png,.webp">
                                    <?php if (!empty($item['photo_url'])): ?><small class="d-block mt-1"><a href="<?= uploadUrl($item['photo_url']) ?>" target="_blank" rel="noopener">View current roll photo</a></small><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-primary"></i>Fabric Rolls Inventory &amp; Details Table</span>
                    
                </div>
                <div class="table-responsive"><table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                    <thead class="table-light small text-uppercase"><tr><th class="text-center">#</th><th>Photo</th><th>Roll Code</th><th>HSN</th><th>Color / Shade</th><th>Design / Pattern</th><th class="text-center">Designs</th><th class="text-end">Total RQ</th><th class="text-end">Weight</th><th class="text-end">Purchase Price (₹)</th><th>GST Type</th><th class="text-end">Tax</th><th class="text-end bg-success-subtle text-success">Total Value</th><th class="text-center">Status</th></tr></thead>
                    <tbody id="rollDetailsTableBody"></tbody>
                </table></div>
            </div>
            <div class="p-3 bg-primary-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><strong class="text-primary"><i class="bi bi-calculator me-1"></i>Batch Metrics Summary</strong></div>
                <div class="d-flex flex-wrap gap-4">
                    <div><small class="text-muted d-block">Total Rolls</small><strong id="summaryTotalRolls">0 Rolls</strong></div>
                    <div><small class="text-muted d-block">Total RQ</small><strong id="summaryTotalRQ">0 RQ</strong></div>
                    <div><small class="text-muted d-block">Colors / Designs</small><strong id="summaryColorsDesigns">0 Colors • 0 Designs</strong></div>
                    <div><small class="text-muted d-block">Total Weight</small><strong id="summaryTotalWeight">0.00 kg</strong></div>
                    <div><small class="text-muted d-block">Total incl. Tax</small><strong id="summaryTotalPrice" class="text-success">₹0.00</strong></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. TOTAL SUMMARY & ACTIONS -->
    <div class="card border shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2"><span>Subtotal</span><strong id="purchaseSubtotal">₹0.00</strong></div>
            <div class="d-flex justify-content-between align-items-center mb-2"><span>Taxes</span><strong id="purchaseTaxes">₹0.00</strong></div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center"><strong>Total Price</strong><strong class="fs-5 text-success" id="purchaseTotal">₹0.00</strong></div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" id="saveEditPurchaseSubmitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase Changes</button>
    </div>
</form>

<!-- THIRD-PARTY PRODUCT TEMPLATE (For adding new products in edit mode) -->
<template id="thirdPartyTemplate">
    <div class="tp-card card border shadow-sm mb-3 tp-row" data-index="" data-item-id="0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong><i class="bi bi-box-seam text-info me-1"></i><span class="tp-title">PRODUCT #1</span></strong>
                <span class="badge bg-secondary-subtle text-secondary border tp-name-badge d-none"></span>
                <span class="badge bg-primary-subtle text-primary border font-monospace tp-sku-badge fw-bold">
                    <i class="bi bi-upc-scan me-1"></i>SKU: <span class="tp-sku-text">AUTO-GENERATING</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 tp-card-calc" style="font-size: 12px;">
                    Base: <span class="tp-badge-base">₹0.00</span> | Tax: <span class="tp-badge-tax">₹0.00</span> | Total: <strong class="tp-badge-total">₹0.00</strong>
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-tp"><i class="bi bi-trash me-1"></i>Delete</button>
            </div>
        </div>
        <div class="card-body p-3">
            <input type="hidden" class="tp-id" value="0">
            <input type="hidden" class="tp-sku">
            <input type="hidden" class="tp-item-type" value="THIRD_PARTY">
            <div class="row g-3">
                <div class="col-lg-5 col-md-4">
                    <label class="form-label required">Product / Item Name *</label>
                    <input class="form-control tp-name text-uppercase" maxlength="255" placeholder="E.G. MEN SLIM FIT COTTON SHIRT" required>
                </div>
                <div class="col-lg-2 col-md-2">
                    <label class="form-label required">Unit *</label>
                    <select class="form-select tp-unit fw-semibold">
                        <option value="Pieces" selected>Pieces</option>
                        <option value="Kilograms">Kilograms</option>
                        <option value="Metres">Metres</option>
                        <option value="Litres">Litres</option>
                        <option value="Boxes">Boxes</option>
                        <option value="Packs">Packs</option>
                        <option value="Pairs">Pairs</option>
                        <option value="Sets">Sets</option>
                        <option value="Bundles">Bundles</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" class="form-control form-control-sm tp-custom-unit mt-1 d-none" placeholder="Enter custom unit">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label required tp-qty-label">Qty (Pieces) *</label>
                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" class="form-control tp-quantity numeric-text-input fw-bold text-end" value="1" required>
                </div>
                <div class="col-lg-3 col-md-3">
                    <label class="form-label required">Purchase Price (₹) *</label>
                    <div class="input-group">
                        <span class="input-group-text p-1">₹</span>
                        <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" class="form-control tp-cost numeric-text-input fw-semibold text-end" value="0.00" required>
                    </div>
                </div>

                <!-- Individual GST & Tax options directly near product fields! -->
                <div class="col-12 mt-2 pt-2 border-top">
                    <div class="p-2.5 rounded border bg-light-subtle d-flex flex-wrap align-items-center gap-3">
                        <div style="min-width: 200px; max-width: 260px;" class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0 small fw-semibold">HSN Code <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold btn-add-hsn-trigger" style="font-size: 11px;" title="Add new HSN Code">
                                    <i class="bi bi-plus-circle me-0.5"></i>+ Add HSN
                                </button>
                            </div>
                            <div class="dropdown hsn-dropdown w-100 position-relative">
                                <input type="hidden" class="tp-hsn">
                                <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center hsn-dropdown-btn font-monospace py-1.5 px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="font-size: 0.82rem; height: 35px;">
                                    <span class="hsn-btn-text text-truncate">-- Select HSN --</span>
                                </button>
                                <div class="dropdown-menu shadow-lg p-2 hsn-dropdown-menu" style="min-width: 280px; max-width: 340px; z-index: 1055;">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 hsn-search-input" placeholder="Search HSN code or item..." autocomplete="off">
                                    </div>
                                    <div class="overflow-auto hsn-options-list" style="max-height: 200px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="min-width: 170px;">
                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                            <select class="form-select form-select-sm item-gst-type">
                                <option value="BOTH" selected>Both (CGST and SGST)</option>
                                <option value="IGST">IGST</option>
                                <option value="CGST">CGST</option>
                                <option value="SGST">SGST</option>
                                <option value="EXEMPTED">Exempted</option>
                            </select>
                        </div>
                        <div style="min-width: 170px;">
                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                            <select class="form-select form-select-sm item-tax-treatment tp-tax-treatment">
                                <option value="INCLUDED" selected>GST Included</option>
                                <option value="EXCLUDED">GST Excluded</option>
                            </select>
                        </div>
                        <div class="tax-mode-field" style="min-width: 130px;">
                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                            <select class="form-select form-select-sm item-tax-mode">
                                <option value="PERCENT" selected>Percentage (%)</option>
                                <option value="AMOUNT">Amount (₹)</option>
                            </select>
                        </div>
                        <div class="tax-value-field" style="min-width: 100px;">
                            <label class="form-label mb-1 small fw-semibold item-tax-label">SGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold" value="2.50">
                        </div>
                        <div class="tax-cgst-field" style="min-width: 100px;">
                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold" value="2.50">
                        </div>
                        <div class="ms-auto text-end">
                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                            <strong class="text-success fs-6 tp-line-total">₹0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- RAW MATERIAL TEMPLATE (For adding new raw materials in edit mode) -->
<template id="rawMaterialTemplate">
    <div class="rm-card card border shadow-sm mb-3 rm-row" data-index="" data-item-id="0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong><i class="bi bi-boxes text-warning me-1"></i><span class="rm-title">MATERIAL #1</span></strong>
                <span class="badge bg-secondary-subtle text-secondary border rm-name-badge d-none"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 rm-card-calc" style="font-size: 12px;">
                    Base: <span class="rm-badge-base">₹0.00</span> | Tax: <span class="rm-badge-tax">₹0.00</span> | Total: <strong class="rm-badge-total">₹0.00</strong>
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-rm"><i class="bi bi-trash me-1"></i>Delete</button>
            </div>
        </div>
        <div class="card-body p-3">
            <input type="hidden" class="rm-id" value="0">
            <input type="hidden" class="rm-sku">
            <input type="hidden" class="rm-item-type" value="RAW_MATERIAL">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label required">Material / Item Name *</label>
                    <input class="form-control rm-name text-uppercase" maxlength="255" placeholder="E.G. 30S COMBED COTTON YARN / SEWING THREAD" list="yarnSuggestions" required>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">Color / Shade</label>
                    <input class="form-control rm-color text-uppercase" maxlength="100" placeholder="E.G. RAW WHITE / NAVY BLUE" list="colorDataList">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">Count / Size</label>
                    <input class="form-control rm-size text-uppercase" maxlength="50" placeholder="E.G. 30S / 40/2 / 1 INCH" list="countSizeSuggestions">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label required">Unit *</label>
                    <select class="form-select rm-unit fw-semibold">
                        <option value="Kilograms" selected>Kilograms</option>
                        <option value="Pieces">Pieces</option>
                        <option value="Metres">Metres</option>
                        <option value="Litres">Litres</option>
                        <option value="Boxes">Boxes</option>
                        <option value="Packs">Packs</option>
                        <option value="Pairs">Pairs</option>
                        <option value="Sets">Sets</option>
                        <option value="Bundles">Bundles</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" class="form-control form-control-sm rm-custom-unit mt-1 d-none" placeholder="Enter custom unit">
                </div>
                <div class="col-lg-1 col-md-3">
                    <label class="form-label required rm-qty-label">Qty (Kilograms) *</label>
                    <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" class="form-control rm-quantity numeric-text-input fw-bold text-end" value="1" required>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label required">Purchase Price (₹) *</label>
                    <div class="input-group">
                        <span class="input-group-text p-1">₹</span>
                        <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-step="0.01" class="form-control rm-cost numeric-text-input fw-semibold text-end" value="0.00" required>
                    </div>
                </div>

                <!-- Individual GST & Tax options -->
                <div class="col-12 mt-2 pt-2 border-top">
                    <div class="p-2.5 rounded border bg-light-subtle d-flex flex-wrap align-items-center gap-3">
                        <div style="min-width: 200px; max-width: 260px;" class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0 small fw-semibold">HSN Code <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold btn-add-hsn-trigger" style="font-size: 11px;" title="Add new HSN Code">
                                    <i class="bi bi-plus-circle me-0.5"></i>+ Add HSN
                                </button>
                            </div>
                            <div class="dropdown hsn-dropdown w-100 position-relative">
                                <input type="hidden" class="rm-hsn">
                                <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center hsn-dropdown-btn font-monospace py-1.5 px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="font-size: 0.82rem; height: 35px;">
                                    <span class="hsn-btn-text text-truncate">-- Select HSN --</span>
                                </button>
                                <div class="dropdown-menu shadow-lg p-2 hsn-dropdown-menu" style="min-width: 280px; max-width: 340px; z-index: 1055;">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 hsn-search-input" placeholder="Search HSN code or item..." autocomplete="off">
                                    </div>
                                    <div class="overflow-auto hsn-options-list" style="max-height: 200px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="min-width: 170px;">
                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                            <select class="form-select form-select-sm item-gst-type">
                                <option value="BOTH" selected>Both (CGST and SGST)</option>
                                <option value="IGST">IGST</option>
                                <option value="CGST">CGST</option>
                                <option value="SGST">SGST</option>
                                <option value="EXEMPTED">Exempted</option>
                            </select>
                        </div>
                        <div style="min-width: 170px;">
                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                            <select class="form-select form-select-sm item-tax-treatment rm-tax-treatment">
                                <option value="INCLUDED" selected>GST Included</option>
                                <option value="EXCLUDED">GST Excluded</option>
                            </select>
                        </div>
                        <div class="tax-mode-field" style="min-width: 130px;">
                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                            <select class="form-select form-select-sm item-tax-mode">
                                <option value="PERCENT" selected>Percentage (%)</option>
                                <option value="AMOUNT">Amount (₹)</option>
                            </select>
                        </div>
                        <div class="tax-value-field" style="min-width: 100px;">
                            <label class="form-label mb-1 small fw-semibold item-tax-label">SGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold" value="2.50">
                        </div>
                        <div class="tax-cgst-field" style="min-width: 100px;">
                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold" value="2.50">
                        </div>
                        <div class="ms-auto text-end">
                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                            <strong class="text-success fs-6 rm-line-total">₹0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Datalists for autocompletion -->
<datalist id="colorDataList">
    <option value="BLACK"><option value="WHITE"><option value="NAVY BLUE"><option value="RED"><option value="ROYAL BLUE">
    <option value="MAROON"><option value="BOTTLE GREEN"><option value="GREY MELANGE"><option value="CHARCOAL"><option value="YELLOW">
    <option value="SKY BLUE"><option value="ORANGE"><option value="BEIGE"><option value="OLIVE GREEN"><option value="LAVENDER">
</datalist>
<datalist id="sizeDataList">
    <option value="XS"><option value="S"><option value="M"><option value="L"><option value="XL"><option value="2XL"><option value="3XL"><option value="FREE">
</datalist>
<datalist id="yarnSuggestions">
    <option value="30S COMBED COTTON YARN"><option value="40S COMBED COTTON YARN"><option value="20S CARDED YARN">
    <option value="SEWING THREAD 3-PLY (WHITE)"><option value="SEWING THREAD 3-PLY (BLACK)"><option value="POLYESTER SPUN THREAD">
    <option value="POLYESTER BUTTONS 14L"><option value="POLYESTER BUTTONS 18L"><option value="WOVEN WASH LABELS">
    <option value="SATIN BRAND LABELS"><option value="ELASTIC TAPE 1.25 INCH"><option value="CONCEALED ZIPPER 7 INCH">
</datalist>
<datalist id="countSizeSuggestions">
    <option value="20S"><option value="30S"><option value="40S"><option value="50S"><option value="60S">
    <option value="2/20S"><option value="2/30S"><option value="2/40S"><option value="40/2"><option value="50/2">
    <option value="1/2 INCH"><option value="3/4 INCH"><option value="1 INCH"><option value="1.25 INCH"><option value="1.5 INCH">
    <option value="14L"><option value="16L"><option value="18L"><option value="20L"><option value="24L">
</datalist>

<!-- Labels Preview Modal -->
<div class="modal fade" id="editPurchaseLabelsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-qr-code me-2 text-warning"></i>Labels Preview</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body bg-light"><div id="editPurchaseLabelCards" class="d-flex flex-wrap justify-content-center gap-3"></div></div>
            <div class="modal-footer"><span class="small text-muted me-auto" id="editPurchaseLabelCount"></span><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Modal Quick Add HSN -->
<div class="modal fade" id="modalQuickAddHsn" tabindex="-1" aria-labelledby="modalQuickAddHsnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="modalQuickAddHsnLabel">
                    <i class="bi bi-plus-circle me-1"></i> Add New HSN Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">HSN Code <span class="text-danger">*</span></label>
                    <input type="text" id="quickHsnCodeInput" class="form-control text-uppercase fw-bold font-monospace" placeholder="e.g. 6206 or 61091000" maxlength="15" required>
                    <small class="text-muted" style="font-size:0.75rem;">Harmonized System of Nomenclature code for invoices</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">Commodity / Description <span class="text-danger">*</span></label>
                    <input type="text" id="quickHsnDescInput" class="form-control" placeholder="e.g. Cotton Woven Fabric / Sewing Thread / Buttons" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">GST Percentage (%) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" id="quickHsnGstRateInput" class="form-control fw-bold" placeholder="e.g. 5" min="0" max="100" step="0.01" value="5" required>
                        <span class="input-group-text fw-bold">%</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-rate-pill" data-rate="0" style="font-size: 11px;">0% (Exempt)</button>
                        <button type="button" class="btn btn-xs btn-primary py-0 px-2 quick-rate-pill active" data-rate="5" style="font-size: 11px;">5% (Fabric/Apparel)</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-rate-pill" data-rate="12" style="font-size: 11px;">12% (Yarn/Apparel)</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-rate-pill" data-rate="18" style="font-size: 11px;">18% (Trims/Machinery)</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-rate-pill" data-rate="28" style="font-size: 11px;">28%</button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Standard garment rates: 5% (cotton/fabric/apparel), 12% (synthetic yarn), 18% (buttons/zips/machinery)</small>
                </div>
                <div id="quickHsnAlert" class="alert alert-danger py-2 small d-none mb-0"></div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btnSaveQuickHsn">
                    <i class="bi bi-check2-circle me-1"></i> Save &amp; Select HSN
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.roll-label-card {
    width: 275px;
    min-height: 155px;
    background: #ffffff;
    border: 1.5px solid #0f172a;
    border-radius: 8px;
    padding: 10px 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    box-sizing: border-box;
}
.label-header {
    border-bottom: 1px dashed #cbd5e1;
    padding-bottom: 4px;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.company-tag {
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #1e293b;
    text-transform: uppercase;
    line-height: 1.2;
    max-width: 175px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.po-tag {
    font-size: 8px;
    font-weight: 700;
    color: #0284c7;
    font-family: monospace;
    background: #e0f2fe;
    padding: 1px 4px;
    border-radius: 3px;
}
.label-body {
    display: flex;
    align-items: center;
    gap: 10px;
}
.qr-box {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    padding: 2px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
}
.qr-box img, .qr-box canvas {
    width: 100% !important;
    height: 100% !important;
}
.label-meta {
    flex-grow: 1;
    font-size: 9.5px;
    line-height: 1.35;
    min-width: 0;
}
.roll-badge-num {
    font-family: monospace;
    font-size: 11px;
    font-weight: 900;
    color: #0f172a;
    display: block;
    margin-bottom: 2px;
    letter-spacing: 0.5px;
}
.meta-line {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.meta-line strong {
    color: #0f172a;
}
.label-footer {
    border-top: 1px dashed #cbd5e1;
    padding-top: 4px;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8px;
    color: #64748b;
}
.hsn-dropdown .hsn-opt-item:hover,
.hsn-dropdown .hsn-opt-item:focus,
.hsn-dropdown .hsn-opt-item.active,
.hsn-dropdown .hsn-opt-item:active {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}
.hsn-dropdown .hsn-opt-item:hover *,
.hsn-dropdown .hsn-opt-item:focus *,
.hsn-dropdown .hsn-opt-item.active *,
.hsn-dropdown .hsn-opt-item:active * {
    color: #ffffff !important;
}
.hsn-dropdown .hsn-opt-item:hover .badge,
.hsn-dropdown .hsn-opt-item:focus .badge,
.hsn-dropdown .hsn-opt-item.active .badge,
.hsn-dropdown .hsn-opt-item:active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
}
.p-2\.5 {
    padding: 0.65rem !important;
}
</style>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
(() => {
    const form = document.getElementById('editProductionPurchaseForm');
    const currentPurchaseType = <?= json_encode($purchaseType) ?>;
    window.hsnCodesList = <?= json_encode($hsnCodes ?? []) ?>;
    const money = value => '₹' + (Number(value) || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const safe = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    let activeHsnTarget = null;

    // --- SHARED HSN DROPDOWN LOGIC ---
    function initHsnDropdown(container, inputSelector) {
        // Check for modern Bootstrap 5 HSN dropdown (Image 2 & 3 style)
        const bsDropdownEl = container.querySelector('.dropdown.hsn-dropdown') || container.querySelector('.hsn-dropdown.position-relative');
        if (bsDropdownEl && bsDropdownEl.querySelector('.hsn-options-list')) {
            const hiddenInput = container.querySelector(inputSelector) || bsDropdownEl.querySelector(inputSelector);
            const btn = bsDropdownEl.querySelector('.hsn-dropdown-btn');
            const btnText = bsDropdownEl.querySelector('.hsn-btn-text');
            const searchInput = bsDropdownEl.querySelector('.hsn-search-input');
            const listContainer = bsDropdownEl.querySelector('.hsn-options-list');

            const triggerBtn = container.querySelector('.btn-add-hsn-trigger');
            if (triggerBtn) {
                triggerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    openQuickAddHsnModal(bsDropdownEl, hiddenInput);
                });
            }

            function updateBtnDisplay(code, desc, gstRate = null) {
                if (!btnText) return;
                if (code) {
                    const found = (window.hsnCodesList || []).find(h => String(h.hsn_code).toUpperCase() === String(code).toUpperCase());
                    const rate = gstRate !== null ? gstRate : (found && found.gst_rate !== null ? found.gst_rate : 5);
                    btnText.innerHTML = `<span class="fw-bold text-primary">${safe(code)}</span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;">${parseFloat(rate)}% GST</span> ${desc ? `<span class="text-muted small ms-1">(${safe(desc).slice(0, 16)}${desc.length > 16 ? '...' : ''})</span>` : ''}`;
                } else {
                    btnText.textContent = '-- Select HSN --';
                }
            }

            if (hiddenInput && hiddenInput.value) {
                const found = (window.hsnCodesList || []).find(h => String(h.hsn_code).toUpperCase() === hiddenInput.value.toUpperCase());
                updateBtnDisplay(hiddenInput.value, found ? found.description : '', found ? found.gst_rate : null);
            }

            function renderOptions(filter = '') {
                if (!listContainer) return;
                const q = filter.trim().toLowerCase();
                let html = `
                    <button type="button" class="dropdown-item py-1.5 px-2 small border-bottom hsn-opt-item text-muted" data-code="" data-desc="" data-rate="0">
                        <em>-- Clear / No HSN --</em>
                    </button>
                `;
                let matchCount = 0;
                const currentVal = (hiddenInput ? hiddenInput.value : '').trim();

                const MAX_RESULTS = 50;
                const list = window.hsnCodesList || [];
                for (let i = 0; i < list.length; i++) {
                    const h = list[i];
                    const code = String(h.hsn_code || '').trim();
                    const desc = String(h.description || '').trim();
                    const rateText = (h.gst_rate !== null && h.gst_rate !== undefined && h.gst_rate !== '') ? `${parseFloat(h.gst_rate)}% GST` : '5% GST';
                    const searchStr = (code + ' ' + desc).toLowerCase();
                    if (q === '' || searchStr.includes(q)) {
                        matchCount++;
                        if (matchCount <= MAX_RESULTS) {
                            const isSelected = currentVal && currentVal.toUpperCase() === code.toUpperCase();
                            html += `
                                <button type="button" class="dropdown-item py-1.5 px-2 small border-bottom hsn-opt-item ${isSelected ? 'active' : ''}" 
                                        data-code="${safe(code)}" data-desc="${safe(desc)}" data-rate="${h.gst_rate ?? 5}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-monospace fw-bold text-primary">${safe(code)}</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 10px;">${rateText}</span>
                                    </div>
                                    <div class="text-truncate small text-muted" style="max-width: 250px;" title="${safe(desc)}">${safe(desc)}</div>
                                </button>
                            `;
                        }
                    }
                }

                if (matchCount > MAX_RESULTS) {
                    html += `<div class="p-1.5 text-center text-muted small bg-light" style="font-size: 11px;"><i class="bi bi-info-circle me-1"></i>Showing 50 of ${matchCount} matches. Type to refine...</div>`;
                }

                if (q !== '' && !matchCount) {
                    html += `
                        <div class="text-muted p-2 text-center small">No matching standard HSN found.</div>
                        <button type="button" class="dropdown-item py-1.5 px-2 small text-primary fw-semibold hsn-quick-add-btn border-top" data-preset="${safe(q.toUpperCase())}">
                            <i class="bi bi-plus-circle me-1"></i>+ Add "${safe(q.toUpperCase())}" to Database
                        </button>
                    `;
                }

                html += `
                    <div class="border-top pt-1 mt-1 text-center bg-light rounded-bottom">
                        <button type="button" class="dropdown-item py-1.5 px-2 small text-primary fw-semibold text-center hsn-quick-add-btn" data-preset="${safe(q.toUpperCase())}">
                            <i class="bi bi-plus-circle me-1"></i>+ Add New HSN Code
                        </button>
                    </div>
                `;

                listContainer.innerHTML = html;

                listContainer.querySelectorAll('.hsn-quick-add-btn').forEach(btnEl => {
                    btnEl.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (btn) {
                            const bsDropdown = bootstrap.Dropdown.getInstance(btn);
                            if (bsDropdown) bsDropdown.hide();
                        }
                        openQuickAddHsnModal(bsDropdownEl, hiddenInput, btnEl.dataset.preset || '');
                    });
                });

                listContainer.querySelectorAll('.hsn-opt-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const code = item.dataset.code || '';
                        const desc = item.dataset.desc || '';
                        const rate = item.dataset.rate || 5;
                        if (hiddenInput) {
                            hiddenInput.value = code;
                        }
                        updateBtnDisplay(code, desc, rate);
                        if (btn) {
                            const bsDropdown = bootstrap.Dropdown.getInstance(btn);
                            if (bsDropdown) bsDropdown.hide();
                        }

                        const card = container.closest('.roll-card, .rm-card, .tp-card') || container;
                        if (card) {
                            applyHsnTaxRules(card, code);
                        }

                        if (hiddenInput) {
                            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (currentPurchaseType === 'THIRD_PARTY') updateThirdPartySummary();
                        else if (currentPurchaseType === 'FABRIC_ROLL') updateSummary();
                        else if (currentPurchaseType === 'RAW_MATERIAL') updateRawMaterialSummary();
                    });
                });
            }

            renderOptions();

            bsDropdownEl.addEventListener('shown.bs.dropdown', () => {
                if (searchInput) {
                    searchInput.value = '';
                    renderOptions('');
                    searchInput.focus();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    renderOptions(searchInput.value);
                });
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const first = listContainer.querySelector('.hsn-opt-item:not(.text-muted)');
                        if (first) first.click();
                    }
                });
            }
            return;
        }

        // Fallback for legacy .hsn-dropdown-wrapper (e.g. fabric rolls)
        const wrapper = container.querySelector('.hsn-dropdown-wrapper');
        if (!wrapper) return;
        const btn = wrapper.querySelector('.hsn-dropdown-btn');
        const dropdown = wrapper.querySelector('.hsn-dropdown');
        const searchInput = wrapper.querySelector('.hsn-search-input');
        const listContainer = wrapper.querySelector('.hsn-list');
        const hiddenInput = container.querySelector(inputSelector);
        const btnText = wrapper.querySelector('.hsn-btn-text');

        function renderWrapperOptions(query = '') {
            if (!listContainer) return;
            listContainer.innerHTML = '';
            const emptyOpt = document.createElement('div');
            emptyOpt.className = 'p-1 hsn-opt-item rounded cursor-pointer small';
            emptyOpt.dataset.hsn = '';
            emptyOpt.dataset.gst = '0';
            emptyOpt.textContent = '-- None --';
            listContainer.appendChild(emptyOpt);

            const q = query.toLowerCase().trim();
            const filtered = (window.hsnCodesList || []).filter(h => {
                if (!q) return true;
                const hCode = String(h.hsn_code || '').toLowerCase();
                const desc = String(h.description || '').toLowerCase();
                return hCode.includes(q) || desc.includes(q);
            });

            if (filtered.length === 0) {
                const noRes = document.createElement('div');
                noRes.className = 'p-2 text-muted small text-center';
                noRes.textContent = 'No matching HSN codes found';
                listContainer.appendChild(noRes);
                return;
            }

            const MAX_RESULTS = 50;
            const toRender = filtered.slice(0, MAX_RESULTS);
            toRender.forEach(h => {
                const item = document.createElement('div');
                item.className = 'p-1 hsn-opt-item rounded cursor-pointer small border-bottom';
                if (hiddenInput && hiddenInput.value && String(hiddenInput.value).toUpperCase() === String(h.hsn_code).toUpperCase()) {
                    item.classList.add('active', 'bg-primary', 'text-white');
                }
                item.dataset.hsn = h.hsn_code;
                item.dataset.gst = h.gst_rate ?? 5;
                const rateText = (h.gst_rate !== null && h.gst_rate !== undefined && h.gst_rate !== '') ? `${parseFloat(h.gst_rate)}% GST` : '5% GST';
                item.innerHTML = `<div class="d-flex justify-content-between align-items-center"><strong class="font-monospace">${safe(h.hsn_code)}</strong><span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size:10px;">${rateText}</span></div><span class="text-muted d-block" style="font-size:10.5px">${safe(h.description || '')}</span>`;
                listContainer.appendChild(item);
            });
            if (filtered.length > MAX_RESULTS) {
                const moreNote = document.createElement('div');
                moreNote.className = 'p-1 text-center text-muted small bg-light';
                moreNote.style.fontSize = '11px';
                moreNote.textContent = `Showing 50 of ${filtered.length} matches. Type to refine...`;
                listContainer.appendChild(moreNote);
            }
        }

        if (btn && dropdown) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.hsn-dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.add('d-none');
                });
                dropdown.classList.toggle('d-none');
                if (!dropdown.classList.contains('d-none') && searchInput) {
                    searchInput.value = '';
                    renderWrapperOptions('');
                    searchInput.focus();
                }
            });
        }

        if (listContainer) {
            listContainer.addEventListener('click', (e) => {
                const item = e.target.closest('.hsn-opt-item');
                if (!item) return;
                const hsn = item.dataset.hsn || '';
                const gst = parseFloat(item.dataset.gst) || 0;
                if (hiddenInput) hiddenInput.value = hsn;
                const found = (window.hsnCodesList || []).find(h => String(h.hsn_code).toUpperCase() === String(hsn).toUpperCase());
                const rate = found && found.gst_rate !== null ? found.gst_rate : gst;
                if (btnText) {
                    btnText.innerHTML = hsn ? `<span class="fw-bold text-primary">${safe(hsn)}</span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;">${parseFloat(rate)}% GST</span>` : '-- Select HSN --';
                }
                if (btn) btn.classList.remove('is-invalid');
                if (dropdown) dropdown.classList.add('d-none');

                const card = container.closest('.roll-card, .rm-card, .tp-card') || container;
                if (card) {
                    applyHsnTaxRules(card, hsn);
                }

                if (hiddenInput) {
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (currentPurchaseType === 'THIRD_PARTY') updateThirdPartySummary();
                else if (currentPurchaseType === 'FABRIC_ROLL') updateSummary();
                else if (currentPurchaseType === 'RAW_MATERIAL') updateRawMaterialSummary();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                renderWrapperOptions(searchInput.value);
            });
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const first = listContainer.querySelector('.hsn-opt-item:not(.text-muted)');
                    if (first) first.click();
                }
            });
        }
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.hsn-dropdown-wrapper')) {
            document.querySelectorAll('.hsn-dropdown-wrapper .hsn-dropdown').forEach(d => d.classList.add('d-none'));
        }
    });

    // --- QUICK ADD HSN MODAL ---
    window.openQuickAddHsnModal = function(target, hiddenInput = null, presetCode = '') {
        if (target && target.closest) {
            activeHsnTarget = target.closest('.tp-card, .roll-card, .rm-card') || target;
        } else {
            activeHsnTarget = null;
        }
        document.getElementById('quickHsnCodeInput').value = presetCode || '';
        document.getElementById('quickHsnDescInput').value = '';
        const gstInp = document.getElementById('quickHsnGstRateInput');
        if (gstInp) gstInp.value = '5';
        document.querySelectorAll('#modalQuickAddHsn .quick-rate-pill').forEach(p => {
            if (p.dataset.rate === '5') {
                p.classList.remove('btn-outline-secondary');
                p.classList.add('btn-primary', 'active');
            } else {
                p.classList.remove('btn-primary', 'active');
                p.classList.add('btn-outline-secondary');
            }
        });
        document.getElementById('quickHsnAlert').classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddHsn')).show();
        setTimeout(() => {
            const codeInp = document.getElementById('quickHsnCodeInput');
            const descInp = document.getElementById('quickHsnDescInput');
            if (presetCode && descInp) descInp.focus();
            else if (codeInp) codeInp.focus();
        }, 300);
    };

    const btnSaveQuickHsn = document.getElementById('btnSaveQuickHsn');
    if (btnSaveQuickHsn) {
        // Wire quick-rate pills
        document.querySelectorAll('#modalQuickAddHsn .quick-rate-pill').forEach(pill => {
            pill.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#modalQuickAddHsn .quick-rate-pill').forEach(p => {
                    p.classList.remove('btn-primary', 'active');
                    p.classList.add('btn-outline-secondary');
                });
                pill.classList.remove('btn-outline-secondary');
                pill.classList.add('btn-primary', 'active');
                const rateInput = document.getElementById('quickHsnGstRateInput');
                if (rateInput) rateInput.value = pill.dataset.rate || 0;
            });
        });

        btnSaveQuickHsn.addEventListener('click', function() {
            const hsnCode = document.getElementById('quickHsnCodeInput').value.trim().toUpperCase();
            const desc = document.getElementById('quickHsnDescInput').value.trim();
            const rateVal = parseFloat(document.getElementById('quickHsnGstRateInput')?.value || 0);
            const gstRate = isNaN(rateVal) ? 0 : rateVal;
            const alertBox = document.getElementById('quickHsnAlert');

            if (!hsnCode) {
                alertBox.textContent = 'Please enter a valid HSN Code.';
                alertBox.classList.remove('d-none');
                return;
            }

            btnSaveQuickHsn.disabled = true;
            btnSaveQuickHsn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch('<?= url("api/hsn-codes/store") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?= CSRF::token() ?>'
                },
                body: JSON.stringify({ hsn_code: hsnCode, description: desc, gst_rate: gstRate })
            })
            .then(r => r.json())
            .then(data => {
                btnSaveQuickHsn.disabled = false;
                btnSaveQuickHsn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save & Select HSN';
                if (data.success && data.data) {
                    const newHsn = data.data;
                    if (!window.hsnCodesList) window.hsnCodesList = [];
                    const existingIdx = window.hsnCodesList.findIndex(h => String(h.hsn_code).toUpperCase() === String(newHsn.hsn_code).toUpperCase());
                    if (existingIdx >= 0) {
                        window.hsnCodesList[existingIdx] = newHsn;
                    } else {
                        window.hsnCodesList.unshift(newHsn);
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalQuickAddHsn')).hide();

                    if (activeHsnTarget) {
                        const hsnInput = activeHsnTarget.querySelector('.tp-hsn, .roll-hsn, .rm-hsn');
                        const hsnBtnText = activeHsnTarget.querySelector('.hsn-btn-text');
                        if (hsnInput) hsnInput.value = newHsn.hsn_code;
                        if (hsnBtnText) {
                            hsnBtnText.innerHTML = `<span class="fw-bold text-primary">${safe(newHsn.hsn_code)}</span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;">${parseFloat(newHsn.gst_rate ?? 0)}% GST</span>`;
                        }
                        applyHsnTaxRules(activeHsnTarget, newHsn.hsn_code);
                        if (hsnInput) {
                            hsnInput.dispatchEvent(new Event('input', { bubbles: true }));
                            hsnInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    if (currentPurchaseType === 'THIRD_PARTY') updateThirdPartySummary();
                    else if (currentPurchaseType === 'FABRIC_ROLL') updateSummary();
                    else if (currentPurchaseType === 'RAW_MATERIAL') updateRawMaterialSummary();
                } else {
                    alertBox.textContent = data.message || 'Failed to save HSN Code.';
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(() => {
                btnSaveQuickHsn.disabled = false;
                btnSaveQuickHsn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save & Select HSN';
                alertBox.textContent = 'Server communication error while saving HSN.';
                alertBox.classList.remove('d-none');
            });
        });
    }

    function applyHsnTaxRules(card, hsnCode, forcedGstType = null) {
        if (!card) return;
        const list = window.hsnCodesList || [];
        const cleanCode = String(hsnCode || '').trim().toUpperCase();
        const found = list.find(h => String(h.hsn_code).toUpperCase() === cleanCode);
        
        let rate = 5.00;
        if (found && found.gst_rate !== null && found.gst_rate !== undefined && found.gst_rate !== '') {
            rate = parseFloat(found.gst_rate);
        } else if (card.dataset.hsnGstRate !== undefined && card.dataset.hsnGstRate !== '') {
            rate = parseFloat(card.dataset.hsnGstRate);
        }
        if (isNaN(rate)) rate = 5.00;

        card.dataset.hsnGstRate = rate;

        const gstTypeSelect = card.querySelector('.item-gst-type');
        const taxModeSelect = card.querySelector('.item-tax-mode');
        const taxValInput = card.querySelector('.item-tax-value');
        const cgstValInput = card.querySelector('.item-cgst-value');

        let currentType = forcedGstType !== null ? forcedGstType : (gstTypeSelect ? gstTypeSelect.value : 'EXEMPTED');

        if (cleanCode && rate > 0 && currentType === 'EXEMPTED' && forcedGstType === null) {
            currentType = 'BOTH';
            if (gstTypeSelect) gstTypeSelect.value = 'BOTH';
        } else if (cleanCode && rate === 0 && forcedGstType === null) {
            currentType = 'EXEMPTED';
            if (gstTypeSelect) gstTypeSelect.value = 'EXEMPTED';
        }

        if (taxModeSelect) {
            taxModeSelect.value = 'PERCENT';
        }

        if (currentType === 'BOTH') {
            const half = (rate / 2).toFixed(2);
            if (taxValInput) taxValInput.value = half;
            if (cgstValInput) cgstValInput.value = half;
        } else if (currentType === 'IGST' || currentType === 'CGST' || currentType === 'SGST') {
            if (taxValInput) taxValInput.value = rate.toFixed(2);
            if (cgstValInput) cgstValInput.value = '0';
        } else {
            if (taxValInput) taxValInput.value = '0';
            if (cgstValInput) cgstValInput.value = '0';
        }

        if (card.classList.contains('roll-card') && typeof updateTaxFields === 'function') {
            updateTaxFields(card);
        } else if (typeof updateTaxInputs === 'function') {
            updateTaxInputs(card);
        }
    }

    // =========================================================================
    // THIRD-PARTY PRODUCTS INWARD LOGIC
    // =========================================================================
    function bindUnitChangeHandler(card, selectSelector, customInputSelector, qtyLabelSelector, onUpdateCallback) {
        const select = card.querySelector(selectSelector);
        const customInput = card.querySelector(customInputSelector);
        const qtyLabel = card.querySelector(qtyLabelSelector);
        if (!select) return;

        function refreshLabel() {
            let unit = select.value || 'Units';
            if (unit === 'Other') {
                if (customInput) customInput.classList.remove('d-none');
                const customVal = customInput ? customInput.value.trim() : '';
                unit = customVal || 'Units';
            } else {
                if (customInput) customInput.classList.add('d-none');
            }
            if (qtyLabel) {
                qtyLabel.textContent = `Qty (${unit}) *`;
            }
            if (typeof onUpdateCallback === 'function') onUpdateCallback();
        }

        select.addEventListener('change', refreshLabel);
        if (customInput) {
            customInput.addEventListener('input', refreshLabel);
            customInput.addEventListener('keyup', refreshLabel);
        }
        refreshLabel();
    }

    function getSelectedUnitValue(card, selectSelector, customInputSelector) {
        const select = card.querySelector(selectSelector);
        const customInput = card.querySelector(customInputSelector);
        if (!select) return 'Pieces';
        if (select.value === 'Other') {
            const customVal = customInput ? customInput.value.trim() : '';
            return customVal || 'Other';
        }
        return select.value || 'Pieces';
    }

    function updateTaxInputs(card) {
        const gstType = card.querySelector('.item-gst-type')?.value || 'EXEMPTED';
        const isExempted = gstType === 'EXEMPTED';
        const isBoth = gstType === 'BOTH';
        const taxModeVal = card.querySelector('.item-tax-mode')?.value || 'PERCENT';
        const suffix = taxModeVal === 'PERCENT' ? '%' : '₹';

        const modeField = card.querySelector('.tax-mode-field');
        const valueField = card.querySelector('.tax-value-field');
        const cgstField = card.querySelector('.tax-cgst-field');
        const itemTaxLabel = card.querySelector('.item-tax-label');
        const itemCgstLabel = card.querySelector('.item-cgst-label');

        if (modeField) modeField.classList.toggle('d-none', isExempted);
        if (valueField) valueField.classList.toggle('d-none', isExempted);
        if (cgstField) cgstField.classList.toggle('d-none', !isBoth);

        if (itemTaxLabel) {
            itemTaxLabel.textContent = isExempted ? 'Tax' : `${isBoth ? 'SGST' : gstType} (${suffix})`;
        }
        if (itemCgstLabel) {
            itemCgstLabel.textContent = `CGST (${suffix})`;
        }

        const rateCol = card.querySelector('.tax-rate-col');
        const cgstCol = card.querySelector('.cgst-rate-col');
        const rateLabel = card.querySelector('.tax-rate-label');

        if (rateCol) rateCol.classList.toggle('d-none', isExempted);
        if (cgstCol) cgstCol.classList.toggle('d-none', !isBoth);

        if (rateLabel) {
            if (gstType === 'IGST') rateLabel.textContent = `IGST Rate (${suffix})`;
            else if (gstType === 'CGST') rateLabel.textContent = `CGST Rate (${suffix})`;
            else rateLabel.textContent = `SGST Rate (${suffix})`;
        }

        const valueInput = card.querySelector('.item-tax-value');
        const cgstInput = card.querySelector('.item-cgst-value');
        [valueInput, cgstInput].forEach(inp => {
            if (!inp) return;
            if (taxModeVal === 'PERCENT') inp.dataset.max = '100';
            else delete inp.dataset.max;
        });
    }

    // =========================================================================
    // RAW MATERIALS LOGIC
    // =========================================================================
    function generateRawMaterialSku(card, index) {
        const name = (card.querySelector('.rm-name')?.value || '').trim();
        const color = (card.querySelector('.rm-color')?.value || '').trim();
        const size = (card.querySelector('.rm-size')?.value || '').trim();
        const seq = String(index + 1).padStart(2, '0');

        let prefix = 'RM';
        let nameCode = '';
        if (name) {
            const cleanName = name.replace(/[^A-Za-z0-9\s]/g, '').trim();
            const words = cleanName.split(/\s+/).filter(Boolean);
            if (words.length > 1) {
                nameCode = words.map(w => w.substring(0, 3).toUpperCase()).join('');
            } else if (words.length === 1) {
                nameCode = words[0].substring(0, 6).toUpperCase();
            }
        }
        if (!nameCode) {
            nameCode = 'MAT' + seq;
        }

        const parts = [prefix, nameCode];
        if (color) {
            const colClean = color.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();
            if (colClean) parts.push(colClean);
        }
        if (size) {
            const szClean = size.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (szClean) parts.push(szClean);
        }
        parts.push(seq);
        return parts.join('-');
    }

    function initExistingRawMaterialCards() {
        const container = document.getElementById('rawMaterialContainer');
        if (!container) return;
        const cards = container.querySelectorAll('.rm-card');

        cards.forEach((card, idx) => {
            initHsnDropdown(card, '.rm-hsn');
            bindUnitChangeHandler(card, '.rm-unit', '.rm-custom-unit', '.rm-qty-label', updateRawMaterialSummary);

            const delBtn = card.querySelector('.remove-rm');
            if (delBtn) {
                delBtn.addEventListener('click', () => {
                    card.remove();
                    Array.from(container.children).forEach((item, i) => {
                        item.querySelector('.rm-title').textContent = 'MATERIAL #' + (i + 1);
                    });
                    updateRawMaterialSummary();
                });
            }

            const nameInput = card.querySelector('.rm-name');
            if (nameInput) {
                nameInput.addEventListener('input', (e) => {
                    const badge = card.querySelector('.rm-name-badge');
                    if (badge) {
                        const val = e.target.value.trim().toUpperCase();
                        badge.textContent = val;
                        badge.classList.toggle('d-none', !val);
                    }
                });
            }

            card.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('input', updateRawMaterialSummary);
                input.addEventListener('change', () => {
                    if (input.classList.contains('item-gst-type')) {
                        const hsn = card.querySelector('.rm-hsn')?.value || '';
                        applyHsnTaxRules(card, hsn, input.value);
                    } else {
                        updateTaxInputs(card);
                    }
                    updateRawMaterialSummary();
                });
                input.addEventListener('keyup', updateRawMaterialSummary);
            });

            const hsnHidden = card.querySelector('.rm-hsn');
            if (hsnHidden) {
                hsnHidden.addEventListener('change', () => {
                    applyHsnTaxRules(card, hsnHidden.value);
                    updateRawMaterialSummary();
                });
            }

            updateTaxInputs(card);
        });
    }

    let rmItemIndex = <?= count($rawMaterialItems) ?>;
    function addRawMaterialItem() {
        const container = document.getElementById('rawMaterialContainer');
        const template = document.getElementById('rawMaterialTemplate');
        if (!container || !template) return;

        const index = rmItemIndex++;
        const card = template.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        card.dataset.itemId = 0;
        const title = 'MATERIAL #' + (container.children.length + 1);
        card.querySelector('.rm-title').textContent = title;

        initHsnDropdown(card, '.rm-hsn');
        bindUnitChangeHandler(card, '.rm-unit', '.rm-custom-unit', '.rm-qty-label', updateRawMaterialSummary);

        card.querySelector('.remove-rm').addEventListener('click', () => {
            card.remove();
            Array.from(container.children).forEach((item, i) => {
                item.querySelector('.rm-title').textContent = 'MATERIAL #' + (i + 1);
            });
            updateRawMaterialSummary();
        });

        const nameInput = card.querySelector('.rm-name');
        nameInput.addEventListener('input', (e) => {
            const badge = card.querySelector('.rm-name-badge');
            if (badge) {
                const val = e.target.value.trim().toUpperCase();
                badge.textContent = val;
                badge.classList.toggle('d-none', !val);
            }
        });

        card.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', updateRawMaterialSummary);
            input.addEventListener('change', () => {
                if (input.classList.contains('item-gst-type')) {
                    const hsn = card.querySelector('.rm-hsn')?.value || '';
                    applyHsnTaxRules(card, hsn, input.value);
                } else {
                    updateTaxInputs(card);
                }
                updateRawMaterialSummary();
            });
            input.addEventListener('keyup', updateRawMaterialSummary);
        });

        const hsnHidden = card.querySelector('.rm-hsn');
        if (hsnHidden) {
            hsnHidden.addEventListener('change', () => {
                applyHsnTaxRules(card, hsnHidden.value);
                updateRawMaterialSummary();
            });
        }

        updateTaxInputs(card);
        container.appendChild(card);
        updateRawMaterialSummary();
    }

    function updateRawMaterialSummary() {
        const container = document.getElementById('rawMaterialContainer');
        if (!container) return;
        const cards = Array.from(container.querySelectorAll('.rm-card'));
        const countBadge = document.getElementById('rmCountBadge');
        if (countBadge) countBadge.textContent = cards.length;
        const tblCountBadge = document.getElementById('rmTableCountBadge');
        if (tblCountBadge) tblCountBadge.textContent = cards.length + (cards.length === 1 ? ' Item' : ' Items');

        let subtotal = 0;
        let totalTax = 0;
        let totalQty = 0;

        const tableBody = document.getElementById('rmDetailsTableBody');
        const tableFoot = document.getElementById('rmDetailsTableFoot');

        if (!cards.length) {
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="13" class="text-center text-muted py-4">Add raw materials to see their inward details.</td></tr>';
            if (tableFoot) tableFoot.innerHTML = '';
        } else {
            let rowsHtml = '';
            cards.forEach((card, index) => {
                const autoSku = generateRawMaterialSku(card, index);
                const skuInput = card.querySelector('.rm-sku');
                if (skuInput && (!skuInput.value || skuInput.value === 'AUTO-GENERATING')) skuInput.value = autoSku;
                const finalSku = skuInput?.value || autoSku;
                const skuText = card.querySelector('.rm-sku-text');
                if (skuText) skuText.textContent = finalSku;

                const name = card.querySelector('.rm-name')?.value?.trim() || ('MATERIAL #' + (index + 1));
                const color = card.querySelector('.rm-color')?.value?.trim() || '—';
                const size = card.querySelector('.rm-size')?.value?.trim() || '—';
                const unitVal = getSelectedUnitValue(card, '.rm-unit', '.rm-custom-unit');
                const qty = Number(card.querySelector('.rm-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.rm-cost')?.value) || 0;

                const gross = qty * cost;
                const gstType = card.querySelector('.item-gst-type')?.value || 'EXEMPTED';
                const taxMode = card.querySelector('.item-tax-mode')?.value || 'PERCENT';
                const taxTreatment = card.querySelector('.rm-tax-treatment')?.value || card.querySelector('.item-tax-treatment')?.value || 'INCLUDED';
                const taxVal = Number(card.querySelector('.item-tax-value')?.value) || 0;
                const cgstVal = Number(card.querySelector('.item-cgst-value')?.value) || 0;

                let base = gross;
                let lineTax = 0;
                let lineTotal = gross;

                if (gstType !== 'EXEMPTED' && gross > 0) {
                    if (taxTreatment === 'INCLUDED') {
                        if (taxMode === 'PERCENT') {
                            const totalRate = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            if (totalRate > 0) {
                                base = Math.round((gross / (1 + (totalRate / 100))) * 100) / 100;
                                lineTax = Math.round((gross - base) * 100) / 100;
                                lineTotal = gross;
                            }
                        } else {
                            const fixedTax = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            lineTax = Math.min(gross, fixedTax);
                            base = Math.round((gross - lineTax) * 100) / 100;
                            lineTotal = gross;
                        }
                    } else {
                        base = gross;
                        if (taxMode === 'PERCENT') {
                            const totalRate = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            lineTax = Math.round(((base * totalRate) / 100) * 100) / 100;
                        } else {
                            lineTax = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                        }
                        lineTotal = Math.round((base + lineTax) * 100) / 100;
                    }
                }

                subtotal += base;
                totalTax += lineTax;
                totalQty += qty;

                const bBase = card.querySelector('.rm-badge-base');
                const bTax = card.querySelector('.rm-badge-tax');
                const bTotal = card.querySelector('.rm-badge-total');
                if (bBase) bBase.textContent = money(base);
                if (bTax) bTax.textContent = money(lineTax);
                if (bTotal) bTotal.textContent = money(lineTotal);
                const lineTotalEl = card.querySelector('.rm-line-total');
                if (lineTotalEl) lineTotalEl.textContent = money(lineTotal);

                const hsn = card.querySelector('.rm-hsn')?.value || '—';
                const gstLabel = gstType === 'BOTH' ? 'SGST + CGST' : gstType;
                const treatmentBadge = taxTreatment === 'INCLUDED' 
                    ? '<small class="text-muted d-block" style="font-size:10px;">(Incl. Tax)</small>' 
                    : '<small class="text-muted d-block" style="font-size:10px;">(Excl. Tax)</small>';

                rowsHtml += `
                    <tr>
                        <td class="text-center fw-bold text-muted">${index + 1}</td>
                        <td class="fw-bold text-dark">${safe(name)}</td>
                        <td class="font-monospace"><span class="badge bg-light text-dark border">${safe(finalSku)}</span></td>
                        <td>${safe(color)}</td>
                        <td>${safe(size)}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">${safe(unitVal)}</span></td>
                        <td class="text-end fw-bold">${qty}</td>
                        <td class="text-end">${money(cost)} ${treatmentBadge}</td>
                        <td class="text-end">${money(base)}</td>
                        <td class="font-monospace text-muted">${safe(hsn)}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">${safe(gstLabel)}</span></td>
                        <td class="text-end">${money(lineTax)}</td>
                        <td class="text-end fw-bold text-success">${money(lineTotal)}</td>
                    </tr>
                `;
            });

            if (tableBody) tableBody.innerHTML = rowsHtml;
            if (tableFoot) {
                tableFoot.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-end">Totals:</td>
                        <td class="text-end">${totalQty}</td>
                        <td></td>
                        <td class="text-end">${money(subtotal)}</td>
                        <td colspan="2"></td>
                        <td class="text-end">${money(totalTax)}</td>
                        <td class="text-end text-success">${money(subtotal + totalTax)}</td>
                    </tr>
                `;
            }
        }

        const sItems = document.getElementById('summaryRmItems');
        const sUnits = document.getElementById('summaryRmUnits');
        const sBase = document.getElementById('summaryRmBase');
        const sTax = document.getElementById('summaryRmTax');
        const sTotal = document.getElementById('summaryRmTotal');
        if (sItems) sItems.textContent = cards.length + (cards.length === 1 ? ' Item' : ' Items');
        if (sUnits) sUnits.textContent = totalQty + ' Units';
        if (sBase) sBase.textContent = money(subtotal);
        if (sTax) sTax.textContent = money(totalTax);
        if (sTotal) sTotal.textContent = money(subtotal + totalTax);

        const pSubtotal = document.getElementById('purchaseSubtotal');
        const pTaxes = document.getElementById('purchaseTaxes');
        const pTotal = document.getElementById('purchaseTotal');
        if (pSubtotal) pSubtotal.textContent = money(subtotal);
        if (pTaxes) pTaxes.textContent = money(totalTax);
        if (pTotal) pTotal.textContent = money(subtotal + totalTax);
    }

    const btnAddRawMaterial = document.getElementById('btnAddRawMaterialItem');
    if (btnAddRawMaterial) {
        btnAddRawMaterial.addEventListener('click', addRawMaterialItem);
    }

    // =========================================================================
    // THIRD-PARTY PRODUCTS INWARD LOGIC
    // =========================================================================
    function generateThirdPartySku(card, index) {
        const name = (card.querySelector('.tp-name')?.value || '').trim();
        const color = (card.querySelector('.tp-color')?.value || '').trim();
        const size = (card.querySelector('.tp-size')?.value || '').trim();
        const seq = String(index + 1).padStart(2, '0');

        let prefix = 'TP';
        let nameCode = '';
        if (name) {
            const cleanName = name.replace(/[^A-Za-z0-9\s]/g, '').trim();
            const words = cleanName.split(/\s+/).filter(Boolean);
            if (words.length > 1) {
                nameCode = words.map(w => w.substring(0, 3).toUpperCase()).join('');
            } else if (words.length === 1) {
                nameCode = words[0].substring(0, 6).toUpperCase();
            }
        }
        if (!nameCode) {
            nameCode = 'PROD' + seq;
        }

        const parts = [prefix, nameCode];
        if (color) {
            const colClean = color.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();
            if (colClean) parts.push(colClean);
        }
        if (size) {
            const szClean = size.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (szClean) parts.push(szClean);
        }
        parts.push(seq);
        return parts.join('-');
    }

    function initExistingThirdPartyCards() {
        const container = document.getElementById('thirdPartyContainer');
        if (!container) return;
        const cards = container.querySelectorAll('.tp-card');

        cards.forEach((card, idx) => {
            initHsnDropdown(card, '.tp-hsn');
            bindUnitChangeHandler(card, '.tp-unit', '.tp-custom-unit', '.tp-qty-label', updateThirdPartySummary);

            const delBtn = card.querySelector('.remove-tp');
            if (delBtn) {
                delBtn.addEventListener('click', () => {
                    card.remove();
                    Array.from(container.children).forEach((item, i) => {
                        item.querySelector('.tp-title').textContent = 'PRODUCT #' + (i + 1);
                    });
                    updateThirdPartySummary();
                });
            }

            const nameInput = card.querySelector('.tp-name');
            if (nameInput) {
                nameInput.addEventListener('input', (e) => {
                    const badge = card.querySelector('.tp-name-badge');
                    if (badge) {
                        const val = e.target.value.trim().toUpperCase();
                        badge.textContent = val;
                        badge.classList.toggle('d-none', !val);
                    }
                });
            }

            card.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('input', updateThirdPartySummary);
                input.addEventListener('change', () => {
                    if (input.classList.contains('item-gst-type')) {
                        const hsn = card.querySelector('.tp-hsn')?.value || '';
                        applyHsnTaxRules(card, hsn, input.value);
                    } else {
                        updateTaxInputs(card);
                    }
                    updateThirdPartySummary();
                });
                input.addEventListener('keyup', updateThirdPartySummary);
            });

            const hsnHidden = card.querySelector('.tp-hsn');
            if (hsnHidden) {
                hsnHidden.addEventListener('change', () => {
                    applyHsnTaxRules(card, hsnHidden.value);
                    updateThirdPartySummary();
                });
            }

            updateTaxInputs(card);
        });
    }

    let tpItemIndex = <?= count($thirdPartyItems) ?>;
    function addThirdPartyItem() {
        const container = document.getElementById('thirdPartyContainer');
        const template = document.getElementById('thirdPartyTemplate');
        if (!container || !template) return;

        const index = tpItemIndex++;
        const card = template.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        card.dataset.itemId = 0;
        const title = 'PRODUCT #' + (container.children.length + 1);
        card.querySelector('.tp-title').textContent = title;

        initHsnDropdown(card, '.tp-hsn');
        bindUnitChangeHandler(card, '.tp-unit', '.tp-custom-unit', '.tp-qty-label', updateThirdPartySummary);

        card.querySelector('.remove-tp').addEventListener('click', () => {
            card.remove();
            Array.from(container.children).forEach((item, i) => {
                item.querySelector('.tp-title').textContent = 'PRODUCT #' + (i + 1);
            });
            updateThirdPartySummary();
        });

        const nameInput = card.querySelector('.tp-name');
        nameInput.addEventListener('input', (e) => {
            const badge = card.querySelector('.tp-name-badge');
            if (badge) {
                const val = e.target.value.trim().toUpperCase();
                badge.textContent = val;
                badge.classList.toggle('d-none', !val);
            }
        });

        card.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', updateThirdPartySummary);
            input.addEventListener('change', () => {
                if (input.classList.contains('item-gst-type')) {
                    const hsn = card.querySelector('.tp-hsn')?.value || '';
                    applyHsnTaxRules(card, hsn, input.value);
                } else {
                    updateTaxInputs(card);
                }
                updateThirdPartySummary();
            });
            input.addEventListener('keyup', updateThirdPartySummary);
        });

        const hsnHidden = card.querySelector('.tp-hsn');
        if (hsnHidden) {
            hsnHidden.addEventListener('change', () => {
                applyHsnTaxRules(card, hsnHidden.value);
                updateThirdPartySummary();
            });
        }

        updateTaxInputs(card);
        container.appendChild(card);
        updateThirdPartySummary();
    }

    function updateThirdPartySummary() {
        const container = document.getElementById('thirdPartyContainer');
        if (!container) return;
        const cards = Array.from(container.querySelectorAll('.tp-card'));
        const countBadge = document.getElementById('tpCountBadge');
        if (countBadge) countBadge.textContent = cards.length;
        const tblCountBadge = document.getElementById('tpTableCountBadge');
        if (tblCountBadge) tblCountBadge.textContent = cards.length + (cards.length === 1 ? ' Product' : ' Products');

        let subtotal = 0;
        let totalTax = 0;
        let totalQty = 0;

        const tableBody = document.getElementById('tpDetailsTableBody');
        const tableFoot = document.getElementById('tpDetailsTableFoot');

        if (!cards.length) {
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-4">Add third-party products to see their inward details.</td></tr>';
            if (tableFoot) tableFoot.innerHTML = '';
        } else {
            let rowsHtml = '';
            cards.forEach((card, index) => {
                const autoSku = generateThirdPartySku(card, index);
                const skuInput = card.querySelector('.tp-sku');
                if (skuInput && (!skuInput.value || skuInput.value === 'AUTO-GENERATING')) skuInput.value = autoSku;
                const finalSku = skuInput?.value || autoSku;
                const skuText = card.querySelector('.tp-sku-text');
                if (skuText) skuText.textContent = finalSku;

                const name = card.querySelector('.tp-name')?.value?.trim() || ('PRODUCT #' + (index + 1));
                const color = card.querySelector('.tp-color')?.value?.trim() || '—';
                const size = card.querySelector('.tp-size')?.value?.trim() || '—';
                const unitVal = getSelectedUnitValue(card, '.tp-unit', '.tp-custom-unit');
                const qty = Number(card.querySelector('.tp-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.tp-cost')?.value) || 0;

                const gross = qty * cost;
                const gstType = card.querySelector('.item-gst-type')?.value || 'EXEMPTED';
                const taxMode = card.querySelector('.item-tax-mode')?.value || 'PERCENT';
                const taxTreatment = card.querySelector('.tp-tax-treatment')?.value || card.querySelector('.item-tax-treatment')?.value || 'INCLUDED';
                const taxVal = Number(card.querySelector('.item-tax-value')?.value) || 0;
                const cgstVal = Number(card.querySelector('.item-cgst-value')?.value) || 0;

                let base = gross;
                let lineTax = 0;
                let lineTotal = gross;

                if (gstType !== 'EXEMPTED' && gross > 0) {
                    if (taxTreatment === 'INCLUDED') {
                        if (taxMode === 'PERCENT') {
                            const totalRate = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            if (totalRate > 0) {
                                base = Math.round((gross / (1 + (totalRate / 100))) * 100) / 100;
                                lineTax = Math.round((gross - base) * 100) / 100;
                                lineTotal = gross;
                            }
                        } else {
                            const fixedTax = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            lineTax = Math.min(gross, fixedTax);
                            base = Math.round((gross - lineTax) * 100) / 100;
                            lineTotal = gross;
                        }
                    } else {
                        // EXCLUDED
                        base = gross;
                        if (taxMode === 'PERCENT') {
                            const totalRate = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                            lineTax = Math.round(((base * totalRate) / 100) * 100) / 100;
                        } else {
                            lineTax = (gstType === 'BOTH') ? (taxVal + cgstVal) : taxVal;
                        }
                        lineTotal = Math.round((base + lineTax) * 100) / 100;
                    }
                }

                subtotal += base;
                totalTax += lineTax;
                totalQty += qty;

                // Update card header calculation pill
                const bBase = card.querySelector('.tp-badge-base');
                const bTax = card.querySelector('.tp-badge-tax');
                const bTotal = card.querySelector('.tp-badge-total');
                if (bBase) bBase.textContent = money(base);
                if (bTax) bTax.textContent = money(lineTax);
                if (bTotal) bTotal.textContent = money(lineTotal);
                const lineTotalEl = card.querySelector('.tp-line-total');
                if (lineTotalEl) lineTotalEl.textContent = money(lineTotal);

                const hsn = card.querySelector('.tp-hsn')?.value || '—';
                const gstLabel = gstType === 'BOTH' ? 'SGST + CGST' : gstType;
                const treatmentBadge = taxTreatment === 'INCLUDED' 
                    ? '<small class="text-muted d-block" style="font-size:10px;">(Incl. Tax)</small>' 
                    : '<small class="text-muted d-block" style="font-size:10px;">(Excl. Tax)</small>';

                rowsHtml += `
                    <tr>
                        <td class="text-center fw-bold text-muted">${index + 1}</td>
                        <td class="fw-bold text-dark">${safe(name)}</td>
                        <td class="font-monospace"><span class="badge bg-light text-dark border">${safe(finalSku)}</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">${safe(unitVal)}</span></td>
                        <td class="text-end fw-bold">${qty}</td>
                        <td class="text-end">${money(cost)} ${treatmentBadge}</td>
                        <td class="text-end">${money(base)}</td>
                        <td class="font-monospace text-muted">${safe(hsn)}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">${safe(gstLabel)}</span></td>
                        <td class="text-end">${money(lineTax)}</td>
                        <td class="text-end fw-bold text-success">${money(lineTotal)}</td>
                    </tr>
                `;
            });

            if (tableBody) tableBody.innerHTML = rowsHtml;
            if (tableFoot) {
                tableFoot.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-end">Totals:</td>
                        <td class="text-end">${totalQty}</td>
                        <td></td>
                        <td class="text-end">${money(subtotal)}</td>
                        <td colspan="2"></td>
                        <td class="text-end">${money(totalTax)}</td>
                        <td class="text-end text-success">${money(subtotal + totalTax)}</td>
                    </tr>
                `;
            }
        }

        const sProds = document.getElementById('summaryTpProducts');
        const sUnits = document.getElementById('summaryTpUnits');
        const sBase = document.getElementById('summaryTpBase');
        const sTax = document.getElementById('summaryTpTax');
        const sTotal = document.getElementById('summaryTpTotal');
        if (sProds) sProds.textContent = cards.length + (cards.length === 1 ? ' Product' : ' Products');
        if (sUnits) sUnits.textContent = totalQty + ' Units';
        if (sBase) sBase.textContent = money(subtotal);
        if (sTax) sTax.textContent = money(totalTax);
        if (sTotal) sTotal.textContent = money(subtotal + totalTax);

        const pSubtotal = document.getElementById('purchaseSubtotal');
        const pTaxes = document.getElementById('purchaseTaxes');
        const pTotal = document.getElementById('purchaseTotal');
        if (pSubtotal) pSubtotal.textContent = money(subtotal);
        if (pTaxes) pTaxes.textContent = money(totalTax);
        if (pTotal) pTotal.textContent = money(subtotal + totalTax);
    }

    const btnAddThirdParty = document.getElementById('btnAddThirdPartyItem');
    if (btnAddThirdParty) {
        btnAddThirdParty.addEventListener('click', addThirdPartyItem);
    }

    // =========================================================================
    // FABRIC ROLL LOGIC
    // =========================================================================
    const rollsContainer = document.getElementById('rollsContainer');

    function calculateTax(row, grossAmount) {
        const taxTreatment = row.querySelector('.item-tax-treatment')?.value || 'INCLUDED';
        const type = row.querySelector('.item-gst-type').value;
        const mode = row.querySelector('.item-tax-mode').value;
        const value = Number(row.querySelector('.item-tax-value').value) || 0;
        const cgstValue = Number(row.querySelector('.item-cgst-value').value) || 0;

        if (type === 'EXEMPTED' || grossAmount <= 0) {
            return { treatment: taxTreatment, type, sgst: 0, cgst: 0, igst: 0, amount: 0, base: grossAmount, total: grossAmount };
        }

        if (taxTreatment === 'INCLUDED') {
            if (mode === 'PERCENT') {
                const totalRate = (type === 'BOTH') ? (value + cgstValue) : value;
                if (totalRate > 0) {
                    const base = Math.round((grossAmount / (1 + (totalRate / 100))) * 100) / 100;
                    const tax = Math.round((grossAmount - base) * 100) / 100;
                    let sgst = 0, cgst = 0, igst = 0;
                    if (type === 'BOTH') {
                        sgst = Math.round((tax * (value / totalRate)) * 100) / 100;
                        cgst = Math.round((tax - sgst) * 100) / 100;
                    } else if (type === 'IGST') {
                        igst = tax;
                    } else if (type === 'SGST') {
                        sgst = tax;
                    } else if (type === 'CGST') {
                        cgst = tax;
                    }
                    return { treatment: taxTreatment, type, sgst, cgst, igst, amount: tax, base, total: grossAmount };
                }
            } else {
                const fixedTax = (type === 'BOTH') ? (value + cgstValue) : value;
                const tax = Math.min(grossAmount, fixedTax);
                const base = Math.round((grossAmount - tax) * 100) / 100;
                let sgst = 0, cgst = 0, igst = 0;
                if (type === 'BOTH' && fixedTax > 0) {
                    sgst = Math.round((tax * (value / fixedTax)) * 100) / 100;
                    cgst = Math.round((tax - sgst) * 100) / 100;
                } else if (type === 'IGST') {
                    igst = tax;
                } else if (type === 'SGST') {
                    sgst = tax;
                } else if (type === 'CGST') {
                    cgst = tax;
                }
                return { treatment: taxTreatment, type, sgst, cgst, igst, amount: tax, base, total: grossAmount };
            }
        }

        // EXCLUDED mode
        const taxAmount = amount => mode === 'AMOUNT' ? amount : Math.round((grossAmount * amount) / 100 * 100) / 100;
        const sgst = ['SGST', 'BOTH'].includes(type) ? taxAmount(value) : 0;
        const cgst = type === 'CGST' ? taxAmount(value) : (type === 'BOTH' ? taxAmount(cgstValue) : 0);
        const igst = type === 'IGST' ? taxAmount(value) : 0;
        const tax = Math.round((sgst + cgst + igst) * 100) / 100;
        return { treatment: taxTreatment, type, sgst, cgst, igst, amount: tax, base: grossAmount, total: Math.round((grossAmount + tax) * 100) / 100 };
    }

    function updateTaxFields(row) {
        const type = row.querySelector('.item-gst-type').value;
        const mode = row.querySelector('.item-tax-mode').value;
        const active = type !== 'EXEMPTED';
        row.querySelector('.tax-mode-field').classList.toggle('d-none', !active);
        row.querySelector('.tax-value-field').classList.toggle('d-none', !active);
        row.querySelector('.tax-cgst-field').classList.toggle('d-none', type !== 'BOTH');
        row.querySelector('.item-tax-value').required = active;
        row.querySelector('.item-cgst-value').required = type === 'BOTH';
        row.querySelector('.item-tax-label').textContent = `${type === 'BOTH' ? 'SGST' : type} (${mode === 'PERCENT' ? '%' : '₹'})`;
        row.querySelector('.item-cgst-label').textContent = `CGST (${mode === 'PERCENT' ? '%' : '₹'})`;
    }

    function updateSummary() {
        if (!rollsContainer) return;
        const rolls = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        const rollTableBody = document.getElementById('rollDetailsTableBody');
        const rollTableRows = [];
        let subtotal = 0, taxes = 0, total = 0, totalWeight = 0, totalRq = 0;
        const colors = new Set(), designs = new Set();
        const fabricDescInput = document.getElementById('editFabricDescription');
        if (fabricDescInput) fabricDescInput.required = rolls.length > 0;

        rolls.forEach((card, index) => {
            const key = card.dataset.itemKey;
            const nameInput = card.querySelector('.roll-item-name');
            if (nameInput && fabricDescInput) nameInput.value = fabricDescInput.value.trim().toUpperCase();
            const price = Number(card.querySelector('.roll-price').value) || 0;
            const weight = Number(card.querySelector('.roll-weight').value) || 0;
            const quantity = Number(card.querySelector('.roll-quantity')?.value) || Number(card.querySelector(`[name="items[${key}][quantity]"]`)?.value) || 0;
            const effectiveQty = Math.max(1, quantity);
            const lineGross = price * effectiveQty;
            const tax = calculateTax(card, lineGross);

            const seqStart = Number(card.dataset.sequenceStart) || 1;
            const prefix = card.dataset.codePrefix || '';
            const baseStartCode = card.dataset.startCode || '';
            const endSeq = seqStart + Math.max(1, quantity) - 1;
            let endCode = baseStartCode;
            if (prefix && baseStartCode) {
                const padLen = Math.max(2, baseStartCode.length - prefix.length);
                endCode = prefix + String(endSeq).padStart(padLen, '0');
            }
            const codeBadgeText = (quantity > 1 && endCode !== baseStartCode) ? `${baseStartCode}–${endCode}` : baseStartCode;
            const codeBadge = card.querySelector('.roll-code-text');
            if (codeBadge) codeBadge.textContent = codeBadgeText;

            const photoFile = card.querySelector('.roll-photo')?.files[0];
            const photoUrl = card.dataset.photoUrl;
            const photoHtml = photoFile
                ? '<span class="text-muted"><i class="bi bi-image"></i></span>'
                : (photoUrl ? `<a href="${safe(photoUrl)}" target="_blank" rel="noopener"><img src="${safe(photoUrl)}" alt="Roll" style="width:38px;height:38px;object-fit:cover"></a>` : '<span class="text-muted"><i class="bi bi-camera"></i></span>');
            subtotal += tax.base;
            taxes += tax.amount;
            total += tax.total;
            totalWeight += weight;
            totalRq += quantity;
            if (card.querySelector('.roll-color').value.trim()) colors.add(card.querySelector('.roll-color').value.trim().toUpperCase());
            if (card.querySelector('.roll-design').value.trim()) designs.add(card.querySelector('.roll-design').value.trim().toUpperCase());
            const taxLabel = tax.type === 'BOTH' ? 'SGST + CGST' : tax.type;
            const treatmentBadge = tax.treatment === 'INCLUDED' ? '<small class="text-muted d-block" style="font-size:10px;">(Incl. Tax)</small>' : '<small class="text-muted d-block" style="font-size:10px;">(Excl. Tax)</small>';
            card.querySelector('.roll-title').textContent = `ROLL #${index + 1}`;
            const hsn = card.querySelector('.roll-hsn')?.value?.trim() || '—';
            rollTableRows.push(`<tr><td class="text-center">${index + 1}</td><td>${photoHtml}</td><td><strong>${safe(card.querySelector('.roll-number').value)}</strong><small class="d-block font-monospace">${safe(codeBadgeText || '—')}</small></td><td><span class="badge bg-light text-dark border font-monospace">${safe(hsn)}</span></td><td>${safe(card.querySelector('.roll-color').value || '—')}</td><td>${safe(card.querySelector('.roll-design').value || '—')}</td><td class="text-center">${Number(card.querySelector('.roll-design-count').value) || 0}</td><td class="text-end">${quantity.toLocaleString('en-IN')} RQ</td><td class="text-end">${weight.toFixed(2)} kg</td><td class="text-end"><div><strong>${money(lineGross)}</strong> ${treatmentBadge}</div>${effectiveQty > 1 ? `<small class="text-muted d-block font-monospace">(${money(price)} / RQ)</small>` : ''}</td><td>${taxLabel}</td><td class="text-end">${money(tax.amount)}</td><td class="text-end fw-bold text-success">${money(tax.total)}</td><td class="text-center"><span class="badge bg-info-subtle text-info">Existing roll</span></td></tr>`);
        });

        const rBadge = document.getElementById('rollCountBadge');
        const trBadge = document.getElementById('tableRollCountBadge');
        const sRolls = document.getElementById('summaryTotalRolls');
        const sRq = document.getElementById('summaryTotalRQ');
        const sCD = document.getElementById('summaryColorsDesigns');
        const sW = document.getElementById('summaryTotalWeight');
        const sP = document.getElementById('summaryTotalPrice');

        if (rBadge) rBadge.textContent = rolls.length;
        if (trBadge) trBadge.textContent = `${rolls.length} ${rolls.length === 1 ? 'Roll' : 'Rolls'}`;
        if (sRolls) sRolls.textContent = `${rolls.length} ${rolls.length === 1 ? 'Roll' : 'Rolls'}`;
        if (sRq) sRq.textContent = `${totalRq.toLocaleString('en-IN')} RQ`;
        if (sCD) sCD.textContent = `${colors.size} Colors • ${designs.size} Designs`;
        if (sW) sW.textContent = `${totalWeight.toFixed(2)} kg`;
        if (sP) sP.textContent = money(total);
        if (rollTableBody) rollTableBody.innerHTML = rollTableRows.length ? rollTableRows.join('') : '<tr><td colspan="14" class="text-center text-muted py-4">No fabric rolls in this purchase.</td></tr>';

        const pSubtotal = document.getElementById('purchaseSubtotal');
        const pTaxes = document.getElementById('purchaseTaxes');
        const pTotal = document.getElementById('purchaseTotal');
        if (pSubtotal) pSubtotal.textContent = money(subtotal);
        if (pTaxes) pTaxes.textContent = money(taxes);
        if (pTotal) pTotal.textContent = money(total);
    }

    function previewLabels() {
        const cards = document.getElementById('editPurchaseLabelCards');
        if (!cards || !rollsContainer) return;
        cards.innerHTML = '';
        let labelCount = 0;
        const material = document.getElementById('editFabricDescription')?.value?.trim()?.toUpperCase() || 'FABRIC';
        const supplier = <?= json_encode($purchase['seller_company'] ?: ($purchase['seller_name'] ?? 'SUPPLIER'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const poNumber = <?= json_encode($purchase['purchase_number'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const purchaseDate = <?= json_encode(date('d M Y', strtotime($purchase['purchase_date'])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        rollsContainer.querySelectorAll('.roll-card').forEach((card, index) => {
            const quantity = Number(card.querySelector(`[name="items[${card.dataset.itemKey}][quantity]"]`)?.value) || 1;
            const start = Number(card.dataset.sequenceStart) || 1;
            let prefix = card.dataset.codePrefix;
            if (!prefix) {
                const sc = card.dataset.startCode || '';
                const m = sc.match(/^([A-Za-z]{2}\d{4})/);
                prefix = m ? m[1] : (sc.replace(/\d{2,}$/, '') || 'FB');
            }
            const rollNum = card.querySelector('.roll-number')?.value?.trim() || `ROLL #${index + 1}`;
            const color = (card.querySelector('.roll-color')?.value?.trim() || 'STANDARD').toUpperCase();
            const totalWeight = Number(card.querySelector('.roll-weight')?.value) || 0;
            const pieceWeight = quantity > 0 ? (totalWeight / quantity).toFixed(2) : totalWeight.toFixed(2);

            for (let piece = 0; piece < quantity; piece++) {
                const code = `${prefix}${String(start + piece).padStart(2, '0')}`;
                const pieceLabel = quantity > 1 ? `${rollNum} (${piece + 1}/${quantity})` : rollNum;

                const sticker = document.createElement('div');
                sticker.className = 'roll-label-card';
                sticker.innerHTML = `
                    <div class="label-header">
                        <div class="company-tag" title="${safe(supplier)}">${safe(supplier)}</div>
                        <div class="po-tag">${safe(poNumber)}</div>
                    </div>
                    <div class="label-body">
                        <div class="qr-box"></div>
                        <div class="label-meta">
                            <span class="roll-badge-num">${safe(pieceLabel)}</span>
                            <div class="meta-line"><span>Fabric:</span> <strong>${safe(material.length > 16 ? material.substring(0, 16) + '...' : material)}</strong></div>
                            <div class="meta-line"><span>Color:</span> <strong>${safe(color)}</strong></div>
                            <div class="meta-line"><span>Weight:</span> <strong style="color:#0284c7">${pieceWeight} kg</strong></div>
                            <div class="meta-line"><span>Code:</span> <span class="font-monospace text-muted" style="font-size:8px">${safe(code)}</span></div>
                        </div>
                    </div>
                    <div class="label-footer">
                        <span>GRM MFG · ${safe(purchaseDate)}</span>
                    </div>
                `;
                cards.appendChild(sticker);
                const qrTarget = sticker.querySelector('.qr-box');
                if (window.QRCode) {
                    new QRCode(qrTarget, {
                        text: code,
                        width: 72,
                        height: 72,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.L
                    });
                } else {
                    qrTarget.textContent = code;
                }
                labelCount++;
            }
        });
        document.getElementById('editPurchaseLabelCount').textContent = `${labelCount} ${labelCount === 1 ? 'label' : 'labels'}`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editPurchaseLabelsModal')).show();
    }

    if (rollsContainer) {
        rollsContainer.querySelectorAll('.roll-card').forEach(card => {
            initHsnDropdown(card, '.roll-hsn');
            const rollHsnHidden = card.querySelector('.roll-hsn');
            if (rollHsnHidden) {
                rollHsnHidden.addEventListener('change', () => {
                    applyHsnTaxRules(card, rollHsnHidden.value);
                    updateSummary();
                });
            }
            card.querySelectorAll('input,select').forEach(input => {
                input.addEventListener('input', updateSummary);
                input.addEventListener('change', () => {
                    if (input.classList.contains('item-gst-type')) {
                        const hsn = rollHsnHidden ? rollHsnHidden.value : '';
                        applyHsnTaxRules(card, hsn, input.value);
                    }
                    updateSummary();
                });
            });
            const taxModeEl = card.querySelector('.item-tax-mode');
            if (taxModeEl) taxModeEl.addEventListener('change', () => { updateTaxFields(card); updateSummary(); });
            updateTaxFields(card);
        });

        rollsContainer.addEventListener('click', e => {
            const btn = e.target.closest('.remove-roll');
            if (!btn) return;
            const card = btn.closest('.roll-card');
            if (!card) return;
            if (rollsContainer.querySelectorAll('.roll-card').length <= 1) {
                alert('At least one fabric roll is required.');
                return;
            }
            card.remove();
            Array.from(rollsContainer.querySelectorAll('.roll-card')).forEach((item, i) => {
                const t = item.querySelector('.roll-title');
                if (t) t.textContent = 'ROLL #' + (i + 1);
            });
            const badge = document.getElementById('rollCountBadge');
            if (badge) badge.textContent = rollsContainer.querySelectorAll('.roll-card').length;
            updateSummary();
        });

        const editFabDesc = document.getElementById('editFabricDescription');
        if (editFabDesc) editFabDesc.addEventListener('input', updateSummary);
        const prevBtn = document.getElementById('previewPurchaseLabels');
        if (prevBtn) prevBtn.addEventListener('click', previewLabels);
    }

    // =========================================================================
    // FORM SUBMISSION VALIDATION & PAYLOAD PACKING
    // =========================================================================
    function resetEditSubmitState() {
        form.removeAttribute('data-submitting');
        const submitBtn = document.getElementById('saveEditPurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.pointerEvents = '';
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Purchase Changes';
        }
    }

    function lockEditSubmitState() {
        form.dataset.submitting = 'true';
        const submitBtn = document.getElementById('saveEditPurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'none';
            setTimeout(() => {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving Changes...';
                }
            }, 10);
        }
    }

    form.addEventListener('submit', event => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return false;
        }

        if (currentPurchaseType === 'RAW_MATERIAL' || currentPurchaseType === 'OWN_PRODUCT') {
            const container = document.getElementById('rawMaterialContainer');
            const cards = container ? Array.from(container.querySelectorAll('.rm-card')) : [];

            if (cards.length === 0) {
                event.preventDefault();
                alert('Add at least one raw material before saving.');
                return;
            }

            let hasError = false;
            let errorMessage = '';
            const itemsPayload = [];

            cards.forEach((card, idx) => {
                if (hasError) return;

                const itemId = parseInt(card.dataset.itemId || '0', 10);
                const name = card.querySelector('.rm-name')?.value?.trim();
                const sku = card.querySelector('.rm-sku')?.value?.trim() || generateRawMaterialSku(card, idx);
                const color = card.querySelector('.rm-color')?.value?.trim() || '';
                const size = card.querySelector('.rm-size')?.value?.trim() || '';
                const unitVal = getSelectedUnitValue(card, '.rm-unit', '.rm-custom-unit');
                const qty = Number(card.querySelector('.rm-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.rm-cost')?.value) || 0;
                const hsn = card.querySelector('.rm-hsn')?.value?.trim() || '';
                const gstType = card.querySelector('.item-gst-type')?.value || 'EXEMPTED';
                const taxMode = card.querySelector('.item-tax-mode')?.value || 'PERCENT';
                const taxTreatment = card.querySelector('.rm-tax-treatment, .item-tax-treatment')?.value || 'INCLUDED';
                const taxVal = Number(card.querySelector('.item-tax-value')?.value) || 0;
                const cgstVal = Number(card.querySelector('.item-cgst-value')?.value) || 0;

                if (!name) {
                    hasError = true;
                    errorMessage = `Material #${idx + 1}: Item name is required.`;
                    return;
                }
                if (qty <= 0) {
                    hasError = true;
                    errorMessage = `Material #${idx + 1} (${name}): Quantity must be greater than zero.`;
                    return;
                }
                if (cost <= 0) {
                    hasError = true;
                    errorMessage = `Material #${idx + 1} (${name}): Purchase price must be greater than zero.`;
                    return;
                }
                if (!hsn) {
                    hasError = true;
                    errorMessage = `Material #${idx + 1} (${name}): HSN Code is mandatory.`;
                    return;
                }

                itemsPayload.push({
                    id: itemId,
                    item_type: 'RAW_MATERIAL',
                    item_name: name,
                    vendor_sku: sku,
                    product_code: sku,
                    color: color,
                    size: size,
                    quantity: qty,
                    unit_price: cost,
                    mrp: 0,
                    selling_price: 0,
                    hsn_code: hsn,
                    gst_type: gstType,
                    tax_mode: taxMode,
                    tax_treatment: taxTreatment,
                    tax_value: taxVal,
                    cgst_tax_value: cgstVal,
                    unit: unitVal
                });
            });

            if (hasError) {
                event.preventDefault();
                alert(errorMessage);
                resetEditSubmitState();
                return;
            }

            let payloadInput = document.getElementById('itemsPayloadInput');
            if (!payloadInput) {
                payloadInput = document.createElement('input');
                payloadInput.type = 'hidden';
                payloadInput.name = 'items_payload';
                payloadInput.id = 'itemsPayloadInput';
                form.appendChild(payloadInput);
            }
            payloadInput.value = JSON.stringify(itemsPayload);
            lockEditSubmitState();
            return;
        }

        if (currentPurchaseType === 'THIRD_PARTY') {
            const container = document.getElementById('thirdPartyContainer');
            const cards = container ? Array.from(container.querySelectorAll('.tp-card')) : [];

            if (cards.length === 0) {
                event.preventDefault();
                alert('Add at least one third-party product before saving.');
                return;
            }

            let hasError = false;
            let errorMessage = '';
            const itemsPayload = [];

            cards.forEach((card, idx) => {
                if (hasError) return;

                const itemId = parseInt(card.dataset.itemId || '0', 10);
                const name = card.querySelector('.tp-name')?.value?.trim();
                const sku = card.querySelector('.tp-sku')?.value?.trim() || generateThirdPartySku(card, idx);
                const color = card.querySelector('.tp-color')?.value?.trim() || '';
                const size = card.querySelector('.tp-size')?.value?.trim() || '';
                const unitVal = getSelectedUnitValue(card, '.tp-unit', '.tp-custom-unit');
                const qty = Number(card.querySelector('.tp-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.tp-cost')?.value) || 0;
                const hsn = card.querySelector('.tp-hsn')?.value?.trim() || '';
                const gstType = card.querySelector('.item-gst-type')?.value || 'EXEMPTED';
                const taxMode = card.querySelector('.item-tax-mode')?.value || 'PERCENT';
                const taxTreatment = card.querySelector('.tp-tax-treatment, .item-tax-treatment')?.value || 'INCLUDED';
                const taxVal = Number(card.querySelector('.item-tax-value')?.value) || 0;
                const cgstVal = Number(card.querySelector('.item-cgst-value')?.value) || 0;

                if (!name) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1}: Item name is required.`;
                    return;
                }
                if (qty <= 0) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} (${name}): Quantity must be greater than zero.`;
                    return;
                }
                if (cost <= 0) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} (${name}): Purchase price must be greater than zero.`;
                    return;
                }
                if (!hsn) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} (${name}): HSN Code is mandatory.`;
                    return;
                }

                itemsPayload.push({
                    id: itemId,
                    item_type: 'THIRD_PARTY',
                    item_name: name,
                    vendor_sku: sku,
                    product_code: sku,
                    color: color,
                    size: size,
                    quantity: qty,
                    unit_price: cost,
                    mrp: 0,
                    selling_price: 0,
                    hsn_code: hsn,
                    gst_type: gstType,
                    tax_mode: taxMode,
                    tax_treatment: taxTreatment,
                    tax_value: taxVal,
                    cgst_tax_value: cgstVal,
                    unit: unitVal
                });
            });

            if (hasError) {
                event.preventDefault();
                alert(errorMessage);
                resetEditSubmitState();
                return;
            }

            let payloadInput = document.getElementById('itemsPayloadInput');
            if (!payloadInput) {
                payloadInput = document.createElement('input');
                payloadInput.type = 'hidden';
                payloadInput.name = 'items_payload';
                payloadInput.id = 'itemsPayloadInput';
                form.appendChild(payloadInput);
            }
            payloadInput.value = JSON.stringify(itemsPayload);
            lockEditSubmitState();
            return;
        }

        if (currentPurchaseType === 'FABRIC_ROLL') {
            if (!rollsContainer) return;
            const hasRoll = rollsContainer.querySelector('.roll-card');
            if (!hasRoll) {
                event.preventDefault();
                alert('Add at least one fabric roll.');
                resetEditSubmitState();
                return;
            }

            // Clean up empty roll photo inputs so max_file_uploads is never exceeded
            rollsContainer.querySelectorAll('.roll-card').forEach(card => {
                const photoInput = card.querySelector('.roll-photo');
                if (photoInput && (!photoInput.files || photoInput.files.length === 0)) {
                    photoInput.removeAttribute('name');
                }
            });
            lockEditSubmitState();
        }
    });

    // Multiple Invoice Documents Management for Edit
    let editSelectedDocFiles = [];
    const editDocInput = document.getElementById('editPurchaseDocuments');
    const editMultiPreviewContainer = document.getElementById('editMultiDocumentPreviewContainer');
    const editDocPreviewCardsRow = document.getElementById('editDocPreviewCardsRow');
    const editSelectedDocCountLabel = document.getElementById('editSelectedDocCountLabel');
    const btnEditClearAllDocs = document.getElementById('btnEditClearAllDocs');

    function syncEditDocInputFiles() {
        if (!editDocInput) return;
        try {
            const dt = new DataTransfer();
            editSelectedDocFiles.forEach(file => dt.items.add(file));
            editDocInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer sync not supported:', e);
        }
    }

    function renderEditDocumentPreviews() {
        if (!editDocPreviewCardsRow || !editMultiPreviewContainer) return;
        editDocPreviewCardsRow.innerHTML = '';

        if (editSelectedDocFiles.length === 0) {
            editMultiPreviewContainer.classList.add('d-none');
            if (btnEditClearAllDocs) btnEditClearAllDocs.classList.add('d-none');
            return;
        }

        editMultiPreviewContainer.classList.remove('d-none');
        if (btnEditClearAllDocs) btnEditClearAllDocs.classList.remove('d-none');
        if (editSelectedDocCountLabel) {
            editSelectedDocCountLabel.innerHTML = `<i class="bi bi-paperclip me-1 text-primary"></i>${editSelectedDocFiles.length} new invoice${editSelectedDocFiles.length > 1 ? 's' : ''} selected`;
        }

        editSelectedDocFiles.forEach((file, index) => {
            const ext = file.name.split('.').pop().toLowerCase();
            const isImg = ['jpg', 'jpeg', 'png', 'webp', 'avif'].includes(ext) || file.type.startsWith('image/');
            const isPdf = ext === 'pdf' || file.type === 'application/pdf';
            const sizeStr = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                : Math.max(1, Math.round(file.size / 1024)) + ' KB';

            const col = document.createElement('div');
            col.className = 'col-sm-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'card h-100 border shadow-xs bg-white';
            card.style.fontSize = '12px';

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2 d-flex flex-column justify-content-between';

            // Preview Thumbnail
            const thumbBox = document.createElement('div');
            thumbBox.className = 'd-flex align-items-center justify-content-center mb-1 rounded bg-light border';
            thumbBox.style.height = '65px';
            thumbBox.style.overflow = 'hidden';

            if (isImg) {
                const img = document.createElement('img');
                img.className = 'img-fluid';
                img.style.maxHeight = '65px';
                img.style.objectFit = 'contain';
                img.src = URL.createObjectURL(file);
                thumbBox.appendChild(img);
            } else if (isPdf) {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-pdf text-danger fs-3"></i>';
            } else {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-text text-secondary fs-3"></i>';
            }

            // Info
            const infoBox = document.createElement('div');
            infoBox.innerHTML = `
                <div class="fw-semibold text-truncate" title="${file.name}">${file.name}</div>
                <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size:10px;">
                    <span>${sizeStr}</span>
                    <span class="badge ${isImg ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'}" style="font-size:9px;">
                        ${isImg ? 'AVIF Auto' : 'PDF'}
                    </span>
                </div>
            `;

            // Actions
            const actionsBox = document.createElement('div');
            actionsBox.className = 'd-flex justify-content-between align-items-center mt-2 pt-1 border-top';

            const btnView = document.createElement('button');
            btnView.type = 'button';
            btnView.className = 'btn btn-xs btn-outline-primary py-0 px-2';
            btnView.style.fontSize = '10px';
            btnView.innerHTML = '<i class="bi bi-eye me-1"></i>View';
            btnView.onclick = function() {
                const url = URL.createObjectURL(file);
                window.open(url, '_blank', 'noopener,noreferrer');
            };

            const btnDel = document.createElement('button');
            btnDel.type = 'button';
            btnDel.className = 'btn btn-xs btn-outline-danger py-0 px-1';
            btnDel.title = 'Remove invoice';
            btnDel.innerHTML = '<i class="bi bi-trash"></i>';
            btnDel.onclick = function() {
                removeEditSelectedDocFile(index);
            };

            actionsBox.appendChild(btnView);
            actionsBox.appendChild(btnDel);

            cardBody.appendChild(thumbBox);
            cardBody.appendChild(infoBox);
            cardBody.appendChild(actionsBox);
            card.appendChild(cardBody);
            col.appendChild(card);
            editDocPreviewCardsRow.appendChild(col);
        });
    }

    if (editDocInput) {
        editDocInput.addEventListener('change', function() {
            if (editDocInput.files && editDocInput.files.length > 0) {
                Array.from(editDocInput.files).forEach(f => {
                    const exists = editSelectedDocFiles.some(existing => 
                        existing.name === f.name && existing.size === f.size && existing.lastModified === f.lastModified
                    );
                    if (!exists) {
                        editSelectedDocFiles.push(f);
                    }
                });
                syncEditDocInputFiles();
                renderEditDocumentPreviews();
            }
        });
    }

    window.removeEditSelectedDocFile = function(index) {
        if (index >= 0 && index < editSelectedDocFiles.length) {
            editSelectedDocFiles.splice(index, 1);
            syncEditDocInputFiles();
            renderEditDocumentPreviews();
        }
    };

    window.clearAllEditSelectedDocFiles = function() {
        editSelectedDocFiles = [];
        if (editDocInput) editDocInput.value = '';
        renderEditDocumentPreviews();
    };

    window.toggleDocMarkDelete = function(checkbox, docId) {
        const card = document.getElementById('existingDocCard_' + docId);
        if (card) {
            if (checkbox.checked) {
                card.style.opacity = '0.5';
                card.classList.add('border-danger');
            } else {
                card.style.opacity = '1';
                card.classList.remove('border-danger');
            }
        }
    };

    // Initialize based on purchase type
    if (currentPurchaseType === 'THIRD_PARTY') {
        initExistingThirdPartyCards();
        updateThirdPartySummary();
    } else if (currentPurchaseType === 'RAW_MATERIAL' || currentPurchaseType === 'OWN_PRODUCT') {
        initExistingRawMaterialCards();
        updateRawMaterialSummary();
    } else if (currentPurchaseType === 'FABRIC_ROLL') {
        updateSummary();
    }
})();
</script>
