<?php
$pmBillId = (int)($mappingBillId ?? 0);
$pmItems = $mappingItems ?? [];
$pmBrands = $mappingBrands ?? [];
$pmHsnList = $mappingHsnList ?? [];
$pmPurchaseId = (int)($purchase['id'] ?? 0);
?>
<input type="hidden" name="mapping_bill_id" id="purchaseMappingBillId" value="<?= $pmBillId ?>">

<!-- Card 1: Add Items to Mapping -->
<div class="card shadow-sm border mb-4" id="purchaseAddItemsMappingCard">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Add Items to Mapping
        </h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-group-sm" role="group" aria-label="Mapping Entry Mode">
                <button type="button" class="btn btn-primary fw-semibold" id="pmTabFabricRollBtn" onclick="switchPmEntryTab('FABRIC_ROLL')">
                    <i class="bi bi-layers me-1"></i>Fabric &amp; Materials Rolls
                </button>
                <button type="button" class="btn btn-outline-primary fw-semibold" id="pmTabRawMaterialBtn" onclick="switchPmEntryTab('RAW_MATERIAL')">
                    <i class="bi bi-boxes me-1"></i>Raw Materials &amp; Third Party
                </button>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="pmCompactToggleBtn" onclick="togglePmMappingCompact()">
                <i class="bi bi-arrows-collapse me-1"></i>Compact
            </button>
        </div>
    </div>

    <div class="card-body p-4" id="pmMappingCardBody">
        <datalist id="pmBrandDatalist">
            <?php foreach ($pmBrands as $br): ?>
                <option value="<?= e($br['name']) ?>">
            <?php endforeach; ?>
        </datalist>

        <!-- Tab 1: Fabric Rolls (Single container, 2 rows) -->
        <div id="pmPaneFabricRoll">
            <div class="border rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1">Roll Batch</span>
                        <strong class="text-dark">Fabric / Material Rolls</strong>
                    </div>
                </div>

                <!-- Row 1 -->
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Roll Code / #</label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="pmRollCode" placeholder="ROLL-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Fabric / Material Name</label>
                        <input type="text" class="form-control form-control-sm" id="pmRollFabricName" placeholder="e.g. Cotton Single Jersey 180 GSM...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Brand</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="pmRollBrandName" list="pmBrandDatalist" placeholder="e.g. GRM...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Color / Shade</label>
                        <input type="text" class="form-control form-control-sm" id="pmRollColor" placeholder="Navy Blue">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Design / Pattern</label>
                        <input type="text" class="form-control form-control-sm" id="pmRollDesign" placeholder="Solid, Printed...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-semibold mb-1">Qty</label>
                        <input type="text" inputmode="numeric" class="form-control form-control-sm pm-num-int" id="pmRollQty" value="1">
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="row g-2 align-items-end mt-1 flex-md-nowrap">
                    <div class="col-6 col-md-1" style="min-width: 85px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap">Weight (KG)</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRollWeight" placeholder="22.50">
                    </div>
                    <div class="col-6 col-md" style="min-width: 105px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap">Purchase Rate (₹)</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRollRate" placeholder="150.00">
                    </div>
                    <div class="col-6 col-md" style="min-width: 110px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap">HSN Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace" id="pmRollHsn" value="5208" placeholder="HSN..." maxlength="20" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="pmBtnRollHsnDropdown" title="Browse HSN codes" tabindex="-1" style="border: 2px solid var(--border); border-left: 0; background: #fff;">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-6 col-md" style="min-width: 135px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap">GST</label>
                        <select class="form-select form-select-sm" id="pmRollGstType">
                            <option value="EXEMPTED">Exempted</option>
                            <option value="BOTH" selected>Both (CGST and SGST)</option>
                            <option value="SGST">SGST</option>
                            <option value="CGST">CGST</option>
                            <option value="IGST">IGST</option>
                        </select>
                    </div>
                    <div class="col-6 col-md" style="min-width: 210px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap d-block">GST % <span id="pmRollGstSplitPreview" class="text-primary fw-normal" style="font-size:0.68rem;"></span></label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRollGst" value="5">
                    </div>
                    <div class="col-6 col-md-auto" style="min-width: 108px;">
                        <label class="form-label small fw-semibold mb-1 text-nowrap">GST Mode</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check pm-roll-gst-mode" name="pm_roll_gst_mode" id="pmRollGstInc" value="INCLUDED" autocomplete="off">
                            <label class="btn btn-outline-primary fw-semibold" for="pmRollGstInc">Incl.</label>
                            <input type="radio" class="btn-check pm-roll-gst-mode" name="pm_roll_gst_mode" id="pmRollGstExc" value="EXCLUDED" autocomplete="off" checked>
                            <label class="btn btn-outline-primary fw-semibold" for="pmRollGstExc">Excl.</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto ms-md-auto d-flex align-items-end">
                        <button type="button" class="btn btn-primary fw-semibold px-3 text-nowrap" id="pmBtnSaveFabricRoll">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Generate &amp; Save
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Raw Materials -->
        <div id="pmPaneRawMaterial" class="d-none">
            <div class="border rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1">Raw Material</span>
                    <strong class="text-dark">Raw Materials</strong>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Material / Item Name</label>
                        <input type="text" class="form-control form-control-sm" id="pmRawMaterialName" placeholder="e.g. Cotton Yarn 40s, Zipper...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Brand</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="pmRawBrandName" list="pmBrandDatalist" placeholder="e.g. GRM...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Color / Shade</label>
                        <input type="text" class="form-control form-control-sm" id="pmRawColor" placeholder="e.g. White, Navy...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Count / Size</label>
                        <input type="text" class="form-control form-control-sm" id="pmRawSize" placeholder="e.g. 40s, 18 inch...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Unit</label>
                        <select class="form-select form-select-sm" id="pmRawUnit">
                            <option value="Kilograms">Kilograms</option>
                            <option value="Pieces" selected>Pieces</option>
                            <option value="Metres">Metres</option>
                            <option value="Litres">Litres</option>
                            <option value="Boxes">Boxes</option>
                            <option value="Packs">Packs</option>
                            <option value="Pairs">Pairs</option>
                            <option value="Sets">Sets</option>
                            <option value="Bundles">Bundles</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" class="form-control form-control-sm mt-1 d-none" id="pmRawCustomUnit" placeholder="Custom unit...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-semibold mb-1">Qty</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRawQty" value="1">
                    </div>

                    <div class="col-md-2 mt-2">
                        <label class="form-label small fw-semibold mb-1">Purchase Rate (₹)</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRawRate" placeholder="0.00">
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label small fw-semibold mb-1">HSN Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace" id="pmRawHsn" placeholder="Type or select HSN..." maxlength="20" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="pmBtnRawHsnDropdown" title="Browse HSN codes" tabindex="-1" style="border: 2px solid var(--border); border-left: 0; background: #fff;">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label small fw-semibold mb-1">GST Type</label>
                        <select class="form-select form-select-sm" id="pmRawGstType">
                            <option value="EXEMPTED">Exempted</option>
                            <option value="BOTH" selected>Both (CGST and SGST)</option>
                            <option value="SGST">SGST</option>
                            <option value="CGST">CGST</option>
                            <option value="IGST">IGST</option>
                        </select>
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label small fw-semibold mb-1 text-nowrap d-block">GST % <span id="pmRawGstSplitPreview" class="text-primary fw-normal" style="font-size:0.66rem;"></span></label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmRawGst" value="5">
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label small fw-semibold mb-1">GST Mode</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check pm-raw-gst-mode" name="pm_raw_gst_mode" id="pmRawGstInc" value="INCLUDED" autocomplete="off">
                            <label class="btn btn-outline-primary fw-semibold" for="pmRawGstInc">Incl.</label>
                            <input type="radio" class="btn-check pm-raw-gst-mode" name="pm_raw_gst_mode" id="pmRawGstExc" value="EXCLUDED" autocomplete="off" checked>
                            <label class="btn btn-outline-primary fw-semibold" for="pmRawGstExc">Excl.</label>
                        </div>
                    </div>
                    <div class="col-md-2 mt-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary fw-semibold w-100" id="pmBtnSaveRawMaterial">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Generate &amp; Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card 2: Saved Product Variants -->
<div class="card shadow-sm border mb-4" id="pmSavedVariantsCard">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Saved Product Variants</h5>
            <span class="badge bg-secondary rounded-pill" id="pmSavedCountBadge"><?= count($pmItems) ?> variant<?= count($pmItems) === 1 ? '' : 's' ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 65px;">SL. NO</th>
                    <th>ITEM NAME</th>
                    <th>BRAND</th>
                    <th>COLOR</th>
                    <th>SIZE</th>
                    <th style="width: 90px;">HSN</th>
                    <th style="width: 80px;">QTY</th>
                    <th style="width: 90px;">UOM</th>
                    <th style="width: 130px;">PURCHASE RATE</th>
                    <th class="text-end" style="width: 100px;">ACTION</th>
                </tr>
            </thead>
            <tbody id="pmSavedVariantsBody">
                <?php if (empty($pmItems)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No products saved yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 px-4 border-top flex-wrap gap-2">
        <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="text-end">
                <span class="small text-muted fw-semibold text-uppercase d-block" style="font-size: 0.72rem; line-height: 1.1;">Total Amount</span>
                <span class="fs-5 fw-bold text-success font-monospace" id="pmSavedTotalAmount">₹0.00</span>
            </div>
            <button type="submit" id="savePurchaseSubmitBtn" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i><?= $pmPurchaseId > 0 ? 'Save Purchase Changes' : 'Save Purchase' ?>
            </button>
        </div>
    </div>
</div>

<!-- Edit Saved Variant Modal -->
<div class="modal fade" id="pmEditItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Saved Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-3">
                <input type="hidden" id="pmEditItemId" value="0">
                <input type="hidden" id="pmEditItemType" value="RAW_MATERIAL">

                <!-- Edit Fabric / Material Rolls (2-row layout) -->
                <div id="pmEditFabricRollPane" class="border rounded-3 p-3 bg-white d-none">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1">Roll Batch</span>
                        <strong class="text-dark">Fabric / Material Rolls</strong>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Roll Code / #</label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="pmEditRollCode" placeholder="ROLL-01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Fabric / Material Name</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditRollFabricName">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Brand</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="pmEditRollBrandName" list="pmBrandDatalist">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Color / Shade</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditRollColor">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Design / Pattern</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditRollDesign">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small fw-semibold mb-1">Qty</label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm pm-num-int" id="pmEditRollQty" value="1">
                        </div>
                    </div>
                    <div class="row g-2 align-items-end mt-1 flex-md-nowrap">
                        <div class="col-6 col-md-1" style="min-width: 85px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap">Weight (KG)</label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditRollWeight">
                        </div>
                        <div class="col-6 col-md" style="min-width: 105px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap">Purchase Rate (₹)</label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditRollRate">
                        </div>
                        <div class="col-6 col-md" style="min-width: 110px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap">HSN Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm font-monospace" id="pmEditRollHsn" maxlength="20" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="pmBtnEditRollHsnDropdown" title="Browse HSN codes" tabindex="-1" style="border: 2px solid var(--border); border-left: 0; background: #fff;">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-md" style="min-width: 135px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap">GST</label>
                            <select class="form-select form-select-sm" id="pmEditRollGstType">
                                <option value="EXEMPTED">Exempted</option>
                                <option value="BOTH">Both (CGST and SGST)</option>
                                <option value="SGST">SGST</option>
                                <option value="CGST">CGST</option>
                                <option value="IGST">IGST</option>
                            </select>
                        </div>
                        <div class="col-6 col-md" style="min-width: 210px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap d-block">GST % <span id="pmEditRollGstSplitPreview" class="text-primary fw-normal" style="font-size:0.68rem;"></span></label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditRollGst" value="5">
                        </div>
                        <div class="col-6 col-md-auto" style="min-width: 115px;">
                            <label class="form-label small fw-semibold mb-1 text-nowrap">GST Mode</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check pm-edit-roll-gst-mode" name="pm_edit_roll_gst_mode" id="pmEditRollGstInc" value="INCLUDED" autocomplete="off">
                                <label class="btn btn-outline-primary fw-semibold" for="pmEditRollGstInc">Incl.</label>
                                <input type="radio" class="btn-check pm-edit-roll-gst-mode" name="pm_edit_roll_gst_mode" id="pmEditRollGstExc" value="EXCLUDED" autocomplete="off" checked>
                                <label class="btn btn-outline-primary fw-semibold" for="pmEditRollGstExc">Excl.</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Raw Materials (2-row layout) -->
                <div id="pmEditRawMaterialPane" class="border rounded-3 p-3 bg-white d-none">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1">Raw Material</span>
                        <strong class="text-dark">Raw Materials</strong>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Material / Item Name</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditItemName">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Brand</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="pmEditBrandName" list="pmBrandDatalist">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Color / Shade</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditColor">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Count / Size</label>
                            <input type="text" class="form-control form-control-sm" id="pmEditSize">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Unit</label>
                            <select class="form-select form-select-sm" id="pmEditUnit">
                                <option value="Kilograms">Kilograms</option>
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
                            <input type="text" class="form-control form-control-sm mt-1 d-none" id="pmEditCustomUnit" placeholder="Custom unit...">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small fw-semibold mb-1">Qty</label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditQty">
                        </div>
                        <div class="col-md-2 mt-2">
                            <label class="form-label small fw-semibold mb-1">Purchase Rate (₹)</label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditRate">
                        </div>
                        <div class="col-md-2 mt-2">
                            <label class="form-label small fw-semibold mb-1">HSN Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm font-monospace" id="pmEditHsn" maxlength="20" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="pmBtnEditRawHsnDropdown" title="Browse HSN codes" tabindex="-1" style="border: 2px solid var(--border); border-left: 0; background: #fff;">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label class="form-label small fw-semibold mb-1">GST Type</label>
                            <select class="form-select form-select-sm" id="pmEditRawGstType">
                                <option value="EXEMPTED">Exempted</option>
                                <option value="BOTH">Both (CGST and SGST)</option>
                                <option value="SGST">SGST</option>
                                <option value="CGST">CGST</option>
                                <option value="IGST">IGST</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="form-label small fw-semibold mb-1 text-nowrap d-block">GST % <span id="pmEditRawGstSplitPreview" class="text-primary fw-normal" style="font-size:0.66rem;"></span></label>
                            <input type="text" inputmode="decimal" class="form-control form-control-sm pm-num-decimal" id="pmEditRawGst" value="5">
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="form-label small fw-semibold mb-1">GST Mode</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check pm-edit-raw-gst-mode" name="pm_edit_raw_gst_mode" id="pmEditRawGstInc" value="INCLUDED" autocomplete="off">
                                <label class="btn btn-outline-primary fw-semibold" for="pmEditRawGstInc">Incl.</label>
                                <input type="radio" class="btn-check pm-edit-raw-gst-mode" name="pm_edit_raw_gst_mode" id="pmEditRawGstExc" value="EXCLUDED" autocomplete="off" checked>
                                <label class="btn btn-outline-primary fw-semibold" for="pmEditRawGstExc">Excl.</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold px-4" id="pmBtnSaveEditItem">
                    <i class="bi bi-check-circle me-1"></i>Update
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pmHsnList = <?= json_encode($pmHsnList, JSON_UNESCAPED_UNICODE) ?>;
    const pmInitialItems = <?= json_encode(array_values($pmItems), JSON_UNESCAPED_UNICODE) ?>;
    const pmCsrfToken = <?= json_encode(CSRF::token()) ?>;
    const pmPurchaseId = <?= (int)$pmPurchaseId ?>;

    const billIdInput = document.getElementById('purchaseMappingBillId');
    const savedBody = document.getElementById('pmSavedVariantsBody');
    const savedCountBadge = document.getElementById('pmSavedCountBadge');

    const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    window.switchPmEntryTab = function (tab) {
        const isFr = tab === 'FABRIC_ROLL';
        const isRm = tab === 'RAW_MATERIAL';
        document.getElementById('pmPaneFabricRoll')?.classList.toggle('d-none', !isFr);
        document.getElementById('pmPaneRawMaterial')?.classList.toggle('d-none', !isRm);
        const btnFr = document.getElementById('pmTabFabricRollBtn');
        const btnRm = document.getElementById('pmTabRawMaterialBtn');
        if (btnFr) {
            btnFr.className = isFr ? 'btn btn-primary fw-semibold' : 'btn btn-outline-primary fw-semibold';
        }
        if (btnRm) {
            btnRm.className = isRm ? 'btn btn-primary fw-semibold' : 'btn btn-outline-primary fw-semibold';
        }
    };

    window.togglePmMappingCompact = function () {
        const body = document.getElementById('pmMappingCardBody');
        const btn = document.getElementById('pmCompactToggleBtn');
        if (!body || !btn) return;
        const collapsed = body.classList.toggle('d-none');
        btn.innerHTML = collapsed
            ? '<i class="bi bi-arrows-expand me-1"></i>Expand'
            : '<i class="bi bi-arrows-collapse me-1"></i>Compact';
    };

    const rawUnitSel = document.getElementById('pmRawUnit');
    const rawCustomUnitInput = document.getElementById('pmRawCustomUnit');
    rawUnitSel?.addEventListener('change', function () {
        if (!rawCustomUnitInput) return;
        const isOther = this.value === 'Other';
        rawCustomUnitInput.classList.toggle('d-none', !isOther);
        if (!isOther) rawCustomUnitInput.value = '';
    });

    const rollHsnInput = document.getElementById('pmRollHsn');
    const rollHsnBtn = document.getElementById('pmBtnRollHsnDropdown');
    const rollGstInput = document.getElementById('pmRollGst');
    const rollGstTypeSel = document.getElementById('pmRollGstType');
    const rollGstSplitEl = document.getElementById('pmRollGstSplitPreview');
    const rawHsnInput = document.getElementById('pmRawHsn');
    const rawHsnBtn = document.getElementById('pmBtnRawHsnDropdown');
    const rawGstInput = document.getElementById('pmRawGst');
    const rawGstTypeSel = document.getElementById('pmRawGstType');
    const rawGstSplitEl = document.getElementById('pmRawGstSplitPreview');

    function updatePmGstSplitPreview(typeEl, rateEl, previewEl) {
        if (!typeEl || !rateEl || !previewEl) return;
        const type = typeEl.value || 'BOTH';
        if (type === 'EXEMPTED') {
            rateEl.value = '0';
            rateEl.readOnly = true;
            previewEl.textContent = '(0%)';
            return;
        }
        rateEl.readOnly = false;
        const rate = parseFloat(rateEl.value || '0') || 0;
        if (type === 'BOTH') {
            const half = (rate / 2).toFixed(2).replace(/\.00$/, '');
            previewEl.textContent = `(CGST ${half}% + SGST ${half}%)`;
        } else {
            previewEl.textContent = `(${type} ${rate}%)`;
        }
    }
    rollGstTypeSel?.addEventListener('change', () => updatePmGstSplitPreview(rollGstTypeSel, rollGstInput, rollGstSplitEl));
    rollGstInput?.addEventListener('input', () => updatePmGstSplitPreview(rollGstTypeSel, rollGstInput, rollGstSplitEl));
    rawGstTypeSel?.addEventListener('change', () => updatePmGstSplitPreview(rawGstTypeSel, rawGstInput, rawGstSplitEl));
    rawGstInput?.addEventListener('input', () => updatePmGstSplitPreview(rawGstTypeSel, rawGstInput, rawGstSplitEl));
    updatePmGstSplitPreview(rollGstTypeSel, rollGstInput, rollGstSplitEl);
    updatePmGstSplitPreview(rawGstTypeSel, rawGstInput, rawGstSplitEl);

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('pm-num-decimal')) {
            let v = e.target.value.replace(/[^0-9.]/g, '');
            const parts = v.split('.');
            if (parts.length > 2) {
                v = parts[0] + '.' + parts.slice(1).join('');
            }
            if (e.target.value !== v) e.target.value = v;
        } else if (e.target.classList.contains('pm-num-int')) {
            const v = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value !== v) e.target.value = v;
        }
    });

    // HSN dropdown (shared for Fabric Roll & Raw Material tabs)
    const pmHsnDrop = document.createElement('div');
    pmHsnDrop.className = 'list-group shadow-lg border';
    pmHsnDrop.style.cssText = 'position:fixed;z-index:2050;display:none;max-height:240px;overflow-y:auto;min-width:280px;background:#fff;border-radius:0.5rem;';
    document.body.appendChild(pmHsnDrop);
    function showPmHsnDropdown(targetInput, targetGstInput, forceAll = false) {
        if (!targetInput || !pmHsnList.length) { pmHsnDrop.style.display = 'none'; return; }
        const q = forceAll ? '' : targetInput.value.trim().toUpperCase();
        const matches = (!q ? pmHsnList : pmHsnList.filter(it =>
            String(it.hsn_code || '').toUpperCase().includes(q) || String(it.description || '').toUpperCase().includes(q)
        )).slice(0, 80);
        if (!matches.length) { pmHsnDrop.style.display = 'none'; return; }
        const rect = (targetInput.closest('.input-group') || targetInput).getBoundingClientRect();
        pmHsnDrop.style.top = `${rect.bottom + 2}px`;
        pmHsnDrop.style.left = `${Math.min(rect.left, window.innerWidth - 300)}px`;
        pmHsnDrop.style.width = `${Math.max(rect.width, 280)}px`;
        pmHsnDrop.innerHTML = '';
        matches.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1 px-2 text-start small';
            btn.innerHTML = `<div class="d-flex justify-content-between"><span class="font-monospace fw-bold">${esc(item.hsn_code)}</span><span class="badge bg-light text-dark border">${item.gst_rate ?? 0}%</span></div>`
                + (item.description ? `<div class="text-truncate text-muted" style="font-size:0.75rem;">${esc(String(item.description).slice(0, 48))}</div>` : '');
            btn.addEventListener('mousedown', ev => {
                ev.preventDefault();
                targetInput.value = item.hsn_code;
                if (targetGstInput && item.gst_rate !== null && item.gst_rate !== undefined && item.gst_rate !== '') {
                    targetGstInput.value = String(item.gst_rate);
                    if (targetGstInput === rollGstInput) updatePmGstSplitPreview(rollGstTypeSel, rollGstInput, rollGstSplitEl);
                    if (targetGstInput === rawGstInput) updatePmGstSplitPreview(rawGstTypeSel, rawGstInput, rawGstSplitEl);
                }
                pmHsnDrop.style.display = 'none';
            });
            pmHsnDrop.appendChild(btn);
        });
        pmHsnDrop.style.display = 'block';
    }
    rollHsnInput?.addEventListener('input', () => showPmHsnDropdown(rollHsnInput, rollGstInput, false));
    rollHsnInput?.addEventListener('focus', () => showPmHsnDropdown(rollHsnInput, rollGstInput, rollHsnInput.value.trim() === ''));
    rollHsnBtn?.addEventListener('click', () => {
        if (pmHsnDrop.style.display === 'block') pmHsnDrop.style.display = 'none';
        else { rollHsnInput?.focus(); showPmHsnDropdown(rollHsnInput, rollGstInput, true); }
    });
    rawHsnInput?.addEventListener('input', () => showPmHsnDropdown(rawHsnInput, rawGstInput, false));
    rawHsnInput?.addEventListener('focus', () => showPmHsnDropdown(rawHsnInput, rawGstInput, rawHsnInput.value.trim() === ''));
    rawHsnBtn?.addEventListener('click', () => {
        if (pmHsnDrop.style.display === 'block') pmHsnDrop.style.display = 'none';
        else { rawHsnInput?.focus(); showPmHsnDropdown(rawHsnInput, rawGstInput, true); }
    });
    document.addEventListener('click', e => {
        if (e.target !== rollHsnInput && e.target !== rawHsnInput
            && !rollHsnBtn?.contains(e.target) && !rawHsnBtn?.contains(e.target)
            && !pmHsnDrop.contains(e.target)) {
            pmHsnDrop.style.display = 'none';
        }
    });

    let pmCurrentItems = [];

    function renderSavedItems(items) {
        if (!savedBody) return;
        const list = Array.isArray(items) ? items : [];
        pmCurrentItems = list;
        if (savedCountBadge) {
            savedCountBadge.textContent = `${list.length} variant${list.length === 1 ? '' : 's'}`;
        }
        const totalAmtEl = document.getElementById('pmSavedTotalAmount');
        if (totalAmtEl) {
            const grandTotal = list.reduce((sum, it) => {
                const qty = Number(it.quantity || 0);
                const rate = Number(it.unit_price || 0);
                const gst = Number(it.gst_rate || 0);
                const base = qty * rate;
                const tax = base * (gst / 100);
                return sum + Math.round((base + tax) * 100) / 100;
            }, 0);
            totalAmtEl.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (!list.length) {
            savedBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">No products saved yet.</td></tr>';
            return;
        }
        savedBody.innerHTML = list.map((it, idx) => {
            return `
                <tr>
                    <td>${idx + 1}</td>
                    <td class="fw-semibold">${esc(it.item_name)}</td>
                    <td>${esc(it.resolved_brand_name || it.brand_name || '—')}</td>
                    <td>${esc(it.color || '—')}</td>
                    <td>${esc(it.size || '—')}</td>
                    <td class="font-monospace">${esc(it.hsn_code || '—')}</td>
                    <td class="fw-bold">${Number(it.quantity || 0).toFixed(it.item_type === 'FABRIC_ROLL' ? 2 : 0)}</td>
                    <td>${esc(it.unit || 'PCS')}</td>
                    <td>₹${Number(it.unit_price || 0).toFixed(2)}</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 me-1" onclick="editPmDraftItem(${Number(it.id)})" title="Edit Item">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deletePmDraftItem(${Number(it.id)})" title="Delete Item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function ensureMappingDraftId() {
        const cur = parseInt(billIdInput?.value || '0', 10);
        if (cur > 0) return cur;

        const sellerId = document.getElementById('purchaseSeller')?.value || '0';
        const invoiceNumber = document.querySelector('input[name="invoice_number"]')?.value || '';
        const concernId = document.querySelector('select[name="concern_id"]')?.value || '';

        const fd = new FormData();
        fd.append('csrf_token', pmCsrfToken);
        fd.append('seller_id', sellerId);
        fd.append('invoice_number', invoiceNumber);
        fd.append('source_module', 'PURCHASE');
        if (pmPurchaseId > 0) {
            fd.append('purchase_id', String(pmPurchaseId));
        }
        if (concernId) fd.append('concern_id', concernId);

        const res = await fetch('<?= url("api/purchases/mapping/ensure-draft") ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        });
        const data = await res.json();
        if (!data.success || !data.mapping_bill_id) {
            alert(data.message || 'Could not initialize mapping draft.');
            return 0;
        }
        if (billIdInput) billIdInput.value = String(data.mapping_bill_id);
        if (pmPurchaseId === 0) {
            try { sessionStorage.setItem('pm_create_draft_bill_id', String(data.mapping_bill_id)); } catch (e) {}
        }
        return parseInt(data.mapping_bill_id, 10);
    }

    document.getElementById('pmBtnSaveFabricRoll')?.addEventListener('click', async function () {
        const fabricName = document.getElementById('pmRollFabricName')?.value.trim() || 'FABRIC ROLL';
        const brandName = document.getElementById('pmRollBrandName')?.value.trim() || '';
        const rollCode = document.getElementById('pmRollCode')?.value.trim() || '';
        const color = document.getElementById('pmRollColor')?.value.trim() || 'Standard';
        const design = document.getElementById('pmRollDesign')?.value.trim() || '';
        const qtyRaw = document.getElementById('pmRollQty')?.value.trim() || '1';
        const weightRaw = document.getElementById('pmRollWeight')?.value.trim() || '0';
        const rateRaw = document.getElementById('pmRollRate')?.value.trim() || '0';
        const gstType = rollGstTypeSel?.value || 'BOTH';
        const gstRaw = gstType === 'EXEMPTED' ? '0' : (document.getElementById('pmRollGst')?.value.trim() || '5');
        const gstMode = document.querySelector('.pm-roll-gst-mode:checked')?.value || 'EXCLUDED';
        const qtyVal = parseInt(qtyRaw || '1', 10);
        const qty = qtyVal > 0 ? qtyVal : 1;
        const weight = parseFloat(weightRaw || '0') || 0;
        const rate = parseFloat(rateRaw || '0') || 0;
        const gstNum = parseFloat(gstRaw || '0') || 0;
        const gstRate = String(gstNum);
        const hsn = document.getElementById('pmRollHsn')?.value || '5208';

        const draftId = await ensureMappingDraftId();
        if (!draftId) return;

        const fd = new FormData();
        fd.append('csrf_token', pmCsrfToken);
        fd.append('entry_type', 'FABRIC_ROLL');
        fd.append('fabric_name', fabricName);
        fd.append('brand_name', brandName);
        fd.append('rolls[0][roll_code]', rollCode);
        fd.append('rolls[0][brand_name]', brandName);
        fd.append('rolls[0][color]', color);
        fd.append('rolls[0][design_name]', design);
        fd.append('rolls[0][quantity]', String(qty));
        fd.append('rolls[0][weight_kg]', String(weight));
        fd.append('rolls[0][rate]', String(rate));
        fd.append('rolls[0][gst_rate]', gstRate);
        fd.append('rolls[0][gst_mode]', gstMode);
        fd.append('rolls[0][gst_type]', gstType);
        fd.append('rolls[0][hsn_code]', hsn);

        this.disabled = true;
        try {
            const res = await fetch('<?= url("purchases/mapping/draft/") ?>' + draftId + '/add-product', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Could not save fabric roll.');
            } else {
                renderSavedItems(data.items || []);
                document.getElementById('pmRollCode').value = '';
                document.getElementById('pmRollWeight').value = '';
            }
        } catch (err) {
            alert('Error saving fabric roll: ' + err.message);
        } finally {
            this.disabled = false;
        }
    });


    document.getElementById('pmBtnSaveRawMaterial')?.addEventListener('click', async function () {
        const materialName = document.getElementById('pmRawMaterialName')?.value.trim() || 'RAW MATERIAL';
        const brandName = document.getElementById('pmRawBrandName')?.value.trim() || '';
        const color = document.getElementById('pmRawColor')?.value.trim() || '';
        const size = document.getElementById('pmRawSize')?.value.trim() || '';
        let unit = rawUnitSel?.value || 'Pieces';
        if (unit === 'Other') {
            unit = rawCustomUnitInput?.value.trim() || 'Pieces';
        }
        const qtyRaw = document.getElementById('pmRawQty')?.value.trim() || '1';
        const rateRaw = document.getElementById('pmRawRate')?.value.trim() || '0';
        const hsn = rawHsnInput?.value.trim() || '';
        const gstType = rawGstTypeSel?.value || 'BOTH';
        const gstRaw = gstType === 'EXEMPTED' ? '0' : (rawGstInput?.value.trim() || '5');
        const gstMode = document.querySelector('.pm-raw-gst-mode:checked')?.value || 'EXCLUDED';

        const qtyVal = parseFloat(qtyRaw || '1');
        const qty = qtyVal > 0 ? qtyVal : 1;
        const rawRate = parseFloat(rateRaw || '0') || 0;
        const gstNum = parseFloat(gstRaw || '0') || 0;

        const draftId = await ensureMappingDraftId();
        if (!draftId) return;

        const effRate = (gstMode === 'INCLUDED' && gstNum > 0 && rawRate > 0)
            ? (rawRate / (1 + gstNum / 100)).toFixed(2)
            : rawRate.toFixed(2);

        const fd = new FormData();
        fd.append('csrf_token', pmCsrfToken);
        fd.append('entry_type', 'RAW_MATERIAL');
        fd.append('item_name', materialName);
        fd.append('brand_name', brandName);
        fd.append('color', color);
        fd.append('size', size);
        fd.append('unit', unit);
        fd.append('quantity', String(qty));
        fd.append('unit_price', effRate);
        fd.append('hsn_code', hsn);
        fd.append('gst_type', gstType);
        fd.append('gst_rate', gstRaw);
        fd.append('gst_mode', gstMode);

        this.disabled = true;
        try {
            const res = await fetch('<?= url("purchases/mapping/draft/") ?>' + draftId + '/add-product', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Could not save raw material.');
            } else {
                renderSavedItems(data.items || []);
                document.getElementById('pmRawMaterialName').value = '';
                document.getElementById('pmRawColor').value = '';
                document.getElementById('pmRawSize').value = '';
                document.getElementById('pmRawQty').value = '1';
                document.getElementById('pmRawRate').value = '';
            }
        } catch (err) {
            alert('Error saving raw material: ' + err.message);
        } finally {
            this.disabled = false;
        }
    });

    const editRollGstTypeSel = document.getElementById('pmEditRollGstType');
    const editRollGstInput = document.getElementById('pmEditRollGst');
    const editRollGstSplitEl = document.getElementById('pmEditRollGstSplitPreview');
    const editRawGstTypeSel = document.getElementById('pmEditRawGstType');
    const editRawGstInput = document.getElementById('pmEditRawGst');
    const editRawGstSplitEl = document.getElementById('pmEditRawGstSplitPreview');
    const editUnitSel = document.getElementById('pmEditUnit');
    const editCustomUnitInput = document.getElementById('pmEditCustomUnit');

    editRollGstTypeSel?.addEventListener('change', () => updatePmGstSplitPreview(editRollGstTypeSel, editRollGstInput, editRollGstSplitEl));
    editRollGstInput?.addEventListener('input', () => updatePmGstSplitPreview(editRollGstTypeSel, editRollGstInput, editRollGstSplitEl));
    editRawGstTypeSel?.addEventListener('change', () => updatePmGstSplitPreview(editRawGstTypeSel, editRawGstInput, editRawGstSplitEl));
    editRawGstInput?.addEventListener('input', () => updatePmGstSplitPreview(editRawGstTypeSel, editRawGstInput, editRawGstSplitEl));
    editUnitSel?.addEventListener('change', function () {
        if (!editCustomUnitInput) return;
        const isOther = this.value === 'Other';
        editCustomUnitInput.classList.toggle('d-none', !isOther);
        if (isOther) editCustomUnitInput.focus();
    });

    const editRollHsnInput = document.getElementById('pmEditRollHsn');
    const editRollHsnBtn = document.getElementById('pmBtnEditRollHsnDropdown');
    const editRawHsnInput = document.getElementById('pmEditHsn');
    const editRawHsnBtn = document.getElementById('pmBtnEditRawHsnDropdown');
    editRollHsnInput?.addEventListener('input', () => showPmHsnDropdown(editRollHsnInput, editRollGstInput, false));
    editRollHsnInput?.addEventListener('focus', () => showPmHsnDropdown(editRollHsnInput, editRollGstInput, editRollHsnInput.value.trim() === ''));
    editRollHsnBtn?.addEventListener('click', (e) => { e.preventDefault(); editRollHsnInput?.focus(); showPmHsnDropdown(editRollHsnInput, editRollGstInput, true); });
    editRawHsnInput?.addEventListener('input', () => showPmHsnDropdown(editRawHsnInput, editRawGstInput, false));
    editRawHsnInput?.addEventListener('focus', () => showPmHsnDropdown(editRawHsnInput, editRawGstInput, editRawHsnInput.value.trim() === ''));
    editRawHsnBtn?.addEventListener('click', (e) => { e.preventDefault(); editRawHsnInput?.focus(); showPmHsnDropdown(editRawHsnInput, editRawGstInput, true); });

    window.editPmDraftItem = function (itemId) {
        const it = pmCurrentItems.find(row => Number(row.id) === Number(itemId));
        if (!it) return;
        const isRoll = (String(it.item_type || '').toUpperCase() === 'FABRIC_ROLL');
        document.getElementById('pmEditItemId').value = String(it.id);
        document.getElementById('pmEditItemType').value = it.item_type || 'RAW_MATERIAL';
        document.getElementById('pmEditFabricRollPane')?.classList.toggle('d-none', !isRoll);
        document.getElementById('pmEditRawMaterialPane')?.classList.toggle('d-none', isRoll);

        const gstTypeVal = String(it.gst_type || 'BOTH').toUpperCase();
        const gstRateVal = String(it.gst_rate ?? '5');
        const gstModeVal = String(it.gst_mode || 'EXCLUDED').toUpperCase();

        if (isRoll) {
            document.getElementById('pmEditRollCode').value = it.roll_code || '';
            document.getElementById('pmEditRollFabricName').value = it.item_name || '';
            document.getElementById('pmEditRollBrandName').value = it.resolved_brand_name || it.brand_name || '';
            document.getElementById('pmEditRollColor').value = it.color || '';
            document.getElementById('pmEditRollDesign').value = it.design_name || '';
            document.getElementById('pmEditRollQty').value = String(parseInt(it.quantity ?? '1', 10) || 1);
            document.getElementById('pmEditRollWeight').value = String(it.weight_kg ?? '0');
            document.getElementById('pmEditRollRate').value = String(it.unit_price ?? '0');
            document.getElementById('pmEditRollHsn').value = it.hsn_code || '5208';
            if (editRollGstTypeSel) editRollGstTypeSel.value = ['EXEMPTED','BOTH','SGST','CGST','IGST'].includes(gstTypeVal) ? gstTypeVal : 'BOTH';
            if (editRollGstInput) editRollGstInput.value = gstRateVal;
            document.getElementById(gstModeVal === 'INCLUDED' ? 'pmEditRollGstInc' : 'pmEditRollGstExc').checked = true;
            updatePmGstSplitPreview(editRollGstTypeSel, editRollGstInput, editRollGstSplitEl);
        } else {
            document.getElementById('pmEditItemName').value = it.item_name || '';
            document.getElementById('pmEditBrandName').value = it.resolved_brand_name || it.brand_name || '';
            document.getElementById('pmEditColor').value = it.color || '';
            document.getElementById('pmEditSize').value = it.size || '';
            const rawUnitVal = it.unit || 'Pieces';
            const stdUnits = Array.from(editUnitSel?.options || []).map(o => o.value);
            if (editUnitSel && stdUnits.includes(rawUnitVal)) {
                editUnitSel.value = rawUnitVal;
                editCustomUnitInput?.classList.add('d-none');
            } else if (editUnitSel) {
                editUnitSel.value = 'Other';
                if (editCustomUnitInput) {
                    editCustomUnitInput.value = rawUnitVal;
                    editCustomUnitInput.classList.remove('d-none');
                }
            }
            document.getElementById('pmEditQty').value = String(it.quantity ?? '1');
            document.getElementById('pmEditRate').value = String(it.unit_price ?? '0');
            document.getElementById('pmEditHsn').value = it.hsn_code || '';
            if (editRawGstTypeSel) editRawGstTypeSel.value = ['EXEMPTED','BOTH','SGST','CGST','IGST'].includes(gstTypeVal) ? gstTypeVal : 'BOTH';
            if (editRawGstInput) editRawGstInput.value = gstRateVal;
            document.getElementById(gstModeVal === 'INCLUDED' ? 'pmEditRawGstInc' : 'pmEditRawGstExc').checked = true;
            updatePmGstSplitPreview(editRawGstTypeSel, editRawGstInput, editRawGstSplitEl);
        }

        const modalEl = document.getElementById('pmEditItemModal');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    document.getElementById('pmBtnSaveEditItem')?.addEventListener('click', async function () {
        const itemId = document.getElementById('pmEditItemId')?.value || '0';
        if (!itemId || itemId === '0') return;
        const isRoll = (String(document.getElementById('pmEditItemType')?.value || '').toUpperCase() === 'FABRIC_ROLL');
        const fd = new FormData();
        fd.append('csrf_token', pmCsrfToken);
        fd.append('mrp', '0');
        fd.append('selling_price', '0');

        if (isRoll) {
            const gstType = editRollGstTypeSel?.value || 'BOTH';
            const gstRate = gstType === 'EXEMPTED' ? '0' : (editRollGstInput?.value.trim() || '5');
            const gstMode = document.querySelector('.pm-edit-roll-gst-mode:checked')?.value || 'EXCLUDED';
            fd.append('roll_code', document.getElementById('pmEditRollCode')?.value.trim() || '');
            fd.append('item_name', document.getElementById('pmEditRollFabricName')?.value.trim() || 'FABRIC ROLL');
            fd.append('brand_name', document.getElementById('pmEditRollBrandName')?.value.trim() || '');
            fd.append('color', document.getElementById('pmEditRollColor')?.value.trim() || '');
            fd.append('design_name', document.getElementById('pmEditRollDesign')?.value.trim() || '');
            fd.append('quantity', document.getElementById('pmEditRollQty')?.value.trim() || '1');
            fd.append('weight_kg', document.getElementById('pmEditRollWeight')?.value.trim() || '0');
            fd.append('unit', 'KGS');
            fd.append('unit_price', document.getElementById('pmEditRollRate')?.value.trim() || '0');
            fd.append('hsn_code', document.getElementById('pmEditRollHsn')?.value.trim() || '');
            fd.append('gst_type', gstType);
            fd.append('gst_rate', gstRate);
            fd.append('gst_mode', gstMode);
        } else {
            let unitVal = editUnitSel?.value || 'Pieces';
            if (unitVal === 'Other') unitVal = editCustomUnitInput?.value.trim() || 'Pieces';
            const gstType = editRawGstTypeSel?.value || 'BOTH';
            const gstRate = gstType === 'EXEMPTED' ? '0' : (editRawGstInput?.value.trim() || '5');
            const gstMode = document.querySelector('.pm-edit-raw-gst-mode:checked')?.value || 'EXCLUDED';
            fd.append('item_name', document.getElementById('pmEditItemName')?.value.trim() || 'RAW MATERIAL');
            fd.append('brand_name', document.getElementById('pmEditBrandName')?.value.trim() || '');
            fd.append('color', document.getElementById('pmEditColor')?.value.trim() || '');
            fd.append('size', document.getElementById('pmEditSize')?.value.trim() || '');
            fd.append('unit', unitVal);
            fd.append('quantity', document.getElementById('pmEditQty')?.value.trim() || '1');
            fd.append('unit_price', document.getElementById('pmEditRate')?.value.trim() || '0');
            fd.append('hsn_code', document.getElementById('pmEditHsn')?.value.trim() || '');
            fd.append('gst_type', gstType);
            fd.append('gst_rate', gstRate);
            fd.append('gst_mode', gstMode);
        }

        this.disabled = true;
        try {
            const res = await fetch('<?= url("purchases/mapping/draft/item/") ?>' + itemId + '/update', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.message || 'Could not update variant.');
            } else {
                renderSavedItems(data.items || []);
                const modalEl = document.getElementById('pmEditItemModal');
                if (modalEl && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
            }
        } catch (err) {
            alert('Error updating variant: ' + err.message);
        } finally {
            this.disabled = false;
        }
    });

    window.deletePmDraftItem = async function (itemId) {
        if (!confirm('Delete this saved item?')) return;
        const fd = new FormData();
        fd.append('csrf_token', pmCsrfToken);
        await fetch('<?= url("purchases/mapping/draft/item/") ?>' + itemId + '/delete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const draftId = parseInt(billIdInput?.value || '0', 10);
        if (draftId > 0) {
            const res = await fetch('<?= url("api/purchases/mapping/draft/") ?>' + draftId + '/items', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success) renderSavedItems(data.items || []);
        }
    };

    renderSavedItems(pmInitialItems);

    const pmHeaderStorageKey = 'pm_purchase_header_' + (pmPurchaseId > 0 ? 'edit_' + pmPurchaseId : 'create');
    const pmSellerEl = document.getElementById('purchaseSeller');
    const pmDateEl = document.getElementById('purchaseDate');
    const pmInvEl = document.getElementById('invoiceNumber');
    const pmNotesEl = document.getElementById('purchaseNotes');
    const pmConcernEl = document.querySelector('select[name="concern_id"]');

    function savePmHeaderState() {
        try {
            sessionStorage.setItem(pmHeaderStorageKey, JSON.stringify({
                seller_id: pmSellerEl?.value || '',
                purchase_date: pmDateEl?.value || '',
                invoice_number: pmInvEl?.value || '',
                notes: pmNotesEl?.value || '',
                concern_id: pmConcernEl?.value || ''
            }));
        } catch (e) {}
    }

    (function restorePmHeaderState() {
        try {
            const raw = sessionStorage.getItem(pmHeaderStorageKey);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (!saved || typeof saved !== 'object') return;
            if (pmSellerEl && typeof saved.seller_id === 'string') {
                const hasOpt = Array.from(pmSellerEl.options).some(o => o.value === saved.seller_id);
                if (hasOpt) {
                    pmSellerEl.value = saved.seller_id;
                    const optBtn = document.querySelector(`#supplierListContainer .supplier-opt-item[data-id="${CSS.escape(saved.seller_id)}"]`);
                    const selTextEl = document.getElementById('supplierDropdownSelectedText');
                    if (optBtn && selTextEl) {
                        selTextEl.textContent = optBtn.dataset.display || '-- Select registered supplier --';
                        document.querySelectorAll('#supplierListContainer .supplier-opt-item').forEach(b => b.classList.remove('active'));
                        optBtn.classList.add('active');
                    }
                }
            }
            if (pmDateEl && saved.purchase_date) pmDateEl.value = saved.purchase_date;
            if (pmInvEl && typeof saved.invoice_number === 'string') pmInvEl.value = saved.invoice_number;
            if (pmNotesEl && typeof saved.notes === 'string') pmNotesEl.value = saved.notes;
            if (pmConcernEl && saved.concern_id) {
                const hasConcern = Array.from(pmConcernEl.options).some(o => o.value === saved.concern_id);
                if (hasConcern) pmConcernEl.value = saved.concern_id;
            }
        } catch (e) {}
    })();

    pmSellerEl?.addEventListener('change', savePmHeaderState);
    pmDateEl?.addEventListener('change', savePmHeaderState);
    pmInvEl?.addEventListener('input', savePmHeaderState);
    pmNotesEl?.addEventListener('input', savePmHeaderState);
    pmConcernEl?.addEventListener('change', savePmHeaderState);

    const formEl = billIdInput?.closest('form');
    formEl?.addEventListener('submit', function () {
        try {
            sessionStorage.removeItem(pmHeaderStorageKey);
            if (pmPurchaseId === 0) sessionStorage.removeItem('pm_create_draft_bill_id');
        } catch (e) {}
    });
    document.querySelector('#pmSavedVariantsCard .card-footer a.btn-outline-secondary')?.addEventListener('click', function () {
        try {
            sessionStorage.removeItem(pmHeaderStorageKey);
            if (pmPurchaseId === 0) sessionStorage.removeItem('pm_create_draft_bill_id');
        } catch (e) {}
    });

    if (pmPurchaseId === 0) {
        let savedDraftId = 0;
        try { savedDraftId = parseInt(sessionStorage.getItem('pm_create_draft_bill_id') || '0', 10); } catch (e) {}
        if (savedDraftId > 0 && (!billIdInput || parseInt(billIdInput.value || '0', 10) <= 0)) {
            if (billIdInput) billIdInput.value = String(savedDraftId);
            fetch('<?= url("api/purchases/mapping/draft/") ?>' + savedDraftId + '/items', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        renderSavedItems(data.items || []);
                    }
                })
                .catch(() => {});
        }
    }
});
</script>
