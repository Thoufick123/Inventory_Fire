<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-bag-plus me-2 text-primary"></i>Record Purchase</h2>
    </div>
    <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
</div>

<form method="POST" action="<?= url('purchases/store') ?>" enctype="multipart/form-data" id="productionPurchaseForm" novalidate>
    <?= CSRF::field() ?>
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Supplier Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label required" for="purchaseSeller">Supplier / Vendor *</label>
                    <select class="d-none" name="seller_id" id="purchaseSeller" required>
                        <option value="">-- Select registered supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>"
                                data-supplier-name="<?= e($seller['name'] ?? '') ?>"
                                data-supplier-company="<?= e($seller['company'] ?? '') ?>"
                                data-supplier-phone="<?= e($seller['phone'] ?? '') ?>"
                                data-supplier-email="<?= e($seller['email'] ?? '') ?>"
                                data-supplier-gst="<?= e($seller['gst_number'] ?? '') ?>"
                                data-supplier-city="<?= e($seller['city'] ?? '') ?>"
                                data-supplier-state="<?= e($seller['state'] ?? '') ?>"
                                data-supplier-address="<?= e($seller['address'] ?? '') ?>"><?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Searchable Supplier Dropdown UI -->
                    <div class="dropdown w-100" id="supplierSearchDropdown">
                        <button type="button" class="form-select text-start d-flex justify-content-between align-items-center" id="supplierDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                            <span id="supplierDropdownSelectedText" class="text-truncate">-- Select registered supplier --</span>
                        </button>
                        <div class="dropdown-menu shadow w-100 p-2" id="supplierDropdownMenu" style="max-height: 360px;">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="supplierSearchInput" placeholder="Search supplier by name, company, phone..." autocomplete="off">
                            </div>
                            <div class="overflow-auto" id="supplierListContainer" style="max-height: 250px;">
                                <button type="button" class="dropdown-item py-2 border-bottom supplier-opt-item active" data-id="" data-display="-- Select registered supplier --">
                                    <span class="text-muted">-- Select registered supplier --</span>
                                </button>
                                <?php foreach ($sellers as $seller): ?>
                                    <button type="button" class="dropdown-item py-2 border-bottom supplier-opt-item"
                                            data-id="<?= (int)$seller['id'] ?>"
                                            data-display="<?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?>"
                                            data-search="<?= e(strtolower(($seller['company'] ?? '') . ' ' . ($seller['name'] ?? '') . ' ' . ($seller['phone'] ?? '') . ' ' . ($seller['city'] ?? '') . ' ' . ($seller['gst_number'] ?? ''))) ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark"><?= e($seller['company'] ?: $seller['name']) ?></strong>
                                            <?php if (!empty($seller['city'])): ?>
                                                <span class="badge bg-light text-secondary border"><?= e($seller['city']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted d-flex justify-content-between flex-wrap gap-1">
                                            <span><i class="bi bi-person me-1"></i><?= e($seller['name']) ?></span>
                                            <span><i class="bi bi-telephone me-1"></i><?= e($seller['phone']) ?></span>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center text-muted py-3 small d-none" id="supplierNoResults">
                                <i class="bi bi-inbox d-block fs-5 mb-1"></i> No suppliers match your search.
                            </div>
                        </div>
                    </div>
                    <?php if (empty($sellers)): ?>
                        <small class="text-danger d-block mt-1">
                            <i class="bi bi-exclamation-circle me-1"></i>No registered suppliers found for this business concern. 
                            <a href="<?= url('production/sellers/create') ?>" class="fw-bold text-primary text-decoration-underline">Add New Supplier</a> before recording a purchase.
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label required" for="purchaseDate">Purchase Date *</label>
                    <input type="date" class="form-control" name="purchase_date" id="purchaseDate" required value="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invoiceNumber">Invoice / Bill Number</label>
                    <input type="text" class="form-control" name="invoice_number" id="invoiceNumber" maxlength="100">
                </div>
                <div class="col-12">
                    <div class="form-check border rounded bg-light p-3 ps-5">
                        <input class="form-check-input" type="checkbox" name="use_mapping_invoice" id="useMappingInvoice" value="1">
                        <label class="form-check-label fw-semibold" for="useMappingInvoice">Use products saved in Purchase Item Mapping</label>
                        <div class="small text-muted">Mark the entry <strong>Move to Purchase</strong> in Mapping first, then select the same supplier and enter the same invoice number here. Saved products will be added automatically.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 fw-semibold" for="purchaseDocuments">
                            <i class="bi bi-receipt-cutoff me-1 text-primary"></i>Tax Invoice / Bill Attachments (Optional)
                        </label>
                        <div class="d-flex gap-1 align-items-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;">
                                <i class="bi bi-files me-1"></i>Multiple Supported (5+)
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;" title="All images are converted to AVIF format for high compression and quality">
                                <i class="bi bi-lightning-charge me-1"></i>Auto AVIF
                            </span>
                        </div>
                    </div>
                    <input type="file" class="form-control" name="documents[]" id="purchaseDocuments" accept=".jpg,.jpeg,.png,.webp,.avif,.pdf" multiple>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted" style="font-size:11px;">
                            Select 1 or multiple invoices (5+ supported). JPG, PNG, WEBP, AVIF or PDF up to 10 MB each. Images automatically convert to <strong>AVIF</strong> format.
                        </small>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none text-decoration-none" id="btnClearAllDocs" onclick="clearAllSelectedDocumentFiles()" style="font-size:11px;">
                            <i class="bi bi-trash3 me-1"></i>Clear All
                        </button>
                    </div>

                    <!-- Multi-document preview container -->
                    <div id="multiDocumentPreviewContainer" class="d-none mt-2 p-2 bg-light border rounded">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                            <span class="small fw-bold text-dark" id="selectedDocCountLabel">
                                <i class="bi bi-paperclip me-1 text-primary"></i>0 invoices selected
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">
                                <i class="bi bi-check2-circle me-1"></i>AVIF Conversion Active
                            </span>
                        </div>
                        <div class="row g-2" id="docPreviewCardsRow">
                            <!-- Dynamically populated invoice preview cards -->
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="purchaseNotes">Purchase Notes</label>
                    <input type="text" class="form-control" name="notes" id="purchaseNotes" maxlength="500">
                </div>
                <div class="col-12 d-none" id="selectedSupplierDetails" aria-live="polite">
                    <div class="border rounded bg-light p-3">
                        <div class="fw-semibold mb-3"><i class="bi bi-person-vcard me-1 text-primary"></i>Selected Supplier Information</div>
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Contact Person</small><span data-supplier-detail="name">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Company</small><span data-supplier-detail="company">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Phone</small><span data-supplier-detail="phone">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Email</small><span data-supplier-detail="email">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">GSTIN / Tax ID</small><span data-supplier-detail="gst">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">City / State</small><span data-supplier-detail="location">—</span></div>
                            <div class="col-12"><small class="text-muted d-block">Business Address</small><span data-supplier-detail="address">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Category Inward Selector (Single Invoice with Multiple Product Categories) -->
    <div class="card shadow-sm border mb-4" id="purchaseCategorySelectorCard">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-collection-play me-2 text-primary"></i>Select Inward &amp; Purchase Categories *</h5>
                <small class="text-muted">A single supplier invoice can contain Fabric Rolls, Raw Materials, and Third-Party Products simultaneously. Check one or more categories below.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllCategories" onclick="selectAllPurchaseCategories()">
                    <i class="bi bi-check-all me-1"></i>Select All 3 Categories
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100 cursor-pointer type-card active border-primary shadow-sm" id="cardTypeFabricRoll" onclick="togglePurchaseCategory('FABRIC_ROLL')">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="form-check p-0 m-0 d-flex align-items-center">
                                <input class="form-check-input me-2 mt-0 cursor-pointer category-checkbox" type="checkbox" id="chkTypeFabricRoll" data-type="FABRIC_ROLL" checked onclick="event.stopPropagation(); togglePurchaseCategory('FABRIC_ROLL', this.checked)">
                                <label class="form-check-label fw-bold text-dark cursor-pointer fs-6" for="chkTypeFabricRoll">
                                    <i class="bi bi-layers-half text-primary me-1"></i> 1. Fabric Rolls
                                </label>
                            </div>
                            <span class="badge bg-primary text-white" id="badgeFabricRollStatus">Active</span>
                        </div>
                        <small class="text-muted d-block ps-4 mb-2">Add raw fabric rolls with RQ, weight, color, design, and thermal scannable QR tags.</small>
                        <div class="ps-4">
                            <span class="badge bg-light text-primary border" id="badgeFabricRollCount">1 Roll</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100 cursor-pointer type-card" id="cardTypeRawMaterial" onclick="togglePurchaseCategory('RAW_MATERIAL')">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="form-check p-0 m-0 d-flex align-items-center">
                                <input class="form-check-input me-2 mt-0 cursor-pointer category-checkbox" type="checkbox" id="chkTypeRawMaterial" data-type="RAW_MATERIAL" onclick="event.stopPropagation(); togglePurchaseCategory('RAW_MATERIAL', this.checked)">
                                <label class="form-check-label fw-bold text-dark cursor-pointer fs-6" for="chkTypeRawMaterial">
                                    <i class="bi bi-boxes text-warning me-1"></i> 2. Raw Materials
                                </label>
                            </div>
                            <span class="badge bg-light text-muted border" id="badgeRawMaterialStatus">Inactive</span>
                        </div>
                        <small class="text-muted d-block ps-4 mb-2">Manage buying counts for yarn, sewing threads, trims, accessories, and production raw materials.</small>
                        <div class="ps-4">
                            <span class="badge bg-light text-warning-emphasis border" id="badgeRawMaterialCount">0 Items</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100 cursor-pointer type-card" id="cardTypeThirdParty" onclick="togglePurchaseCategory('THIRD_PARTY')">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="form-check p-0 m-0 d-flex align-items-center">
                                <input class="form-check-input me-2 mt-0 cursor-pointer category-checkbox" type="checkbox" id="chkTypeThirdParty" data-type="THIRD_PARTY" onclick="event.stopPropagation(); togglePurchaseCategory('THIRD_PARTY', this.checked)">
                                <label class="form-check-label fw-bold text-dark cursor-pointer fs-6" for="chkTypeThirdParty">
                                    <i class="bi bi-box-seam text-info me-1"></i> 3. Third-Party Products
                                </label>
                            </div>
                            <span class="badge bg-light text-muted border" id="badgeThirdPartyStatus">Inactive</span>
                        </div>
                        <small class="text-muted d-block ps-4 mb-2">External vendor ready-to-sell goods (SKU, Size, Unit, Cost).</small>
                        <div class="ps-4">
                            <span class="badge bg-light text-info border" id="badgeThirdPartyCount">0 Products</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Section Navigation & Jump Links -->
            <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small fw-semibold text-secondary"><i class="bi bi-arrow-down-circle me-1"></i>Jump to Section:</span>
                    <a href="#sectionFabricRoll" class="btn btn-xs btn-outline-primary py-0 px-2 category-jump-btn" id="jumpFabricRoll"><i class="bi bi-layers-half me-1"></i>Fabric Rolls</a>
                    <a href="#sectionRawMaterial" class="btn btn-xs btn-outline-warning text-dark py-0 px-2 category-jump-btn d-none" id="jumpRawMaterial"><i class="bi bi-boxes me-1"></i>Raw Materials</a>
                    <a href="#sectionThirdParty" class="btn btn-xs btn-outline-info text-dark py-0 px-2 category-jump-btn d-none" id="jumpThirdParty"><i class="bi bi-box-seam me-1"></i>Third-Party Products</a>
                </div>
                <div class="small text-muted">
                    <span class="badge bg-primary-subtle text-primary border" id="activeCategoryPill">1 Category Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. FABRIC ROLL SECTION -->
    <div id="sectionFabricRoll">
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll &amp; Material Supply Details</h5>
                   </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-primary" id="addFabricRoll"><i class="bi bi-plus-circle me-1"></i>Add Fabric Roll</button>
                </div>
                <div class="col-12">
                    <label class="form-label required" for="fabricDescription">Fabric / Material Description *</label>
                    <input type="text" name="fabric_description" id="fabricDescription" class="form-control text-uppercase" maxlength="150" placeholder="E.G. 100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)" list="materialTypeSuggestions">
                    <datalist id="materialTypeSuggestions">
                        <option value="100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)">
                        <option value="100% COTTON 24S SINGLE JERSEY (180 GSM)">
                        <option value="95% COTTON 5% SPANDEX/LYCRA (190 GSM)">
                        <option value="100% COTTON FRENCH TERRY (240 GSM)">
                        <option value="100% COTTON 3-THREAD FLEECE (320 GSM)">
                        <option value="COTTON-POLYESTER BLEND 60/40 (180 GSM)">
                        <option value="100% COTTON PIQUE POLO KNIT (220 GSM)">
                        <option value="100% COTTON 2X2 RIB KNIT (220 GSM)">
                        <option value="DENIM TWILL 12OZ INDIGO">
                        <option value="RAYON / VISCOSE PRINTED FABRIC">
                    </datalist>
                    <datalist id="sizeDataList">
                        <option value="XS">
                        <option value="S">
                        <option value="M">
                        <option value="L">
                        <option value="XL">
                        <option value="XXL">
                        <option value="3XL">
                        <option value="28">
                        <option value="30">
                        <option value="32">
                        <option value="34">
                        <option value="36">
                        <option value="38">
                        <option value="40">
                        <option value="42">
                        <option value="FREE SIZE">
                        <?php foreach (($sizes ?? []) as $sz): ?>
                            <option value="<?= e($sz['name'] ?? '') ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <datalist id="colorDataList">
                        <option value="BLACK">
                        <option value="WHITE">
                        <option value="NAVY BLUE">
                        <option value="ROYAL BLUE">
                        <option value="RED">
                        <option value="MAROON">
                        <option value="OLIVE GREEN">
                        <option value="BOTTLE GREEN">
                        <option value="GREY / HEATHER">
                        <option value="CHARCOAL">
                        <option value="BEIGE">
                        <option value="YELLOW">
                        <option value="ORANGE">
                        <option value="PINK">
                        <option value="LAVENDER">
                        <?php foreach (($colors ?? []) as $cl): ?>
                            <option value="<?= e($cl['name'] ?? '') ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <datalist id="yarnSuggestions">
                        <option value="30S COMBED COTTON YARN">
                        <option value="40S COMBED COTTON YARN">
                        <option value="24S CARDED COTTON YARN">
                        <option value="34S COMBED COTTON YARN">
                        <option value="POLYESTER SEWING THREAD 40/2">
                        <option value="SPUN POLYESTER THREAD 20/2">
                        <option value="OVERLOCK LOOPER THREAD">
                        <option value="HIGH TENACITY EMBROIDERY THREAD">
                        <option value="ELASTIC TAPE 1 INCH">
                        <option value="ELASTIC TAPE 1.5 INCH">
                        <option value="COTTON DRAWCORD / ROPE">
                        <option value="TWILL NECK TAPE 12MM">
                        <option value="BUTTONS 18L 4-HOLE POLYESTER">
                        <option value="BUTTONS 24L 2-HOLE">
                        <option value="ZIPPERS #5 NYLON COIL">
                        <option value="ZIPPERS #3 CONCEALED">
                        <option value="WOVEN MAIN LABELS">
                        <option value="PRINTED CARE &amp; WASH LABELS">
                        <option value="FUSIBLE INTERLINING (WHITE)">
                    </datalist>
                    <datalist id="countSizeSuggestions">
                        <option value="20s">
                        <option value="24s">
                        <option value="30s">
                        <option value="34s">
                        <option value="40s">
                        <option value="2/40">
                        <option value="2/30">
                        <option value="1/20">
                        <option value="1 inch">
                        <option value="1.25 inch">
                        <option value="1.5 inch">
                        <option value="2 inch">
                        <option value="12mm">
                        <option value="18L">
                        <option value="24L">
                        <option value="32L">
                        <option value="#3">
                        <option value="#5">
                        <option value="1000m">
                        <option value="5000m">
                    </datalist>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-stack me-1 text-primary"></i>Individual Fabric Rolls (<span id="rollCountBadge">0</span>)</h6>
            </div>
            <div id="rollsContainer" class="d-flex flex-column gap-3 mb-4"></div>
            <div class="card border shadow-sm mb-4" id="rollDetailsTableContainer">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-primary"></i>Fabric Rolls Inventory &amp; Details Table</span>
                    
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                        <thead class="table-light small text-uppercase">
                            <tr><th class="text-center">#</th><th>Photo</th><th>Roll Code</th><th>HSN</th><th>Color / Shade</th><th>Design / Pattern</th><th class="text-center">Designs</th><th class="text-end">Total RQ</th><th class="text-end">Weight</th><th class="text-end">Purchase Price (₹)</th><th>GST Type</th><th class="text-end">Tax</th><th class="text-end bg-success-subtle text-success">Total Value</th><th class="text-center">Status</th></tr>
                        </thead>
                        <tbody id="rollDetailsTableBody"><tr><td colspan="14" class="text-center text-muted py-4">Add fabric rolls to see their inventory details.</td></tr></tbody>
                        <tfoot id="rollDetailsTableFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
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
    </div> <!-- /#sectionFabricRoll -->

    <!-- 2. RAW MATERIALS SECTION -->
    <div id="sectionRawMaterial" class="d-none">
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2 text-warning"></i>Raw Materials Procurement (Yarn, Thread &amp; Accessories)</h5>
                </div>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold" id="btnAddRawMaterialItem">
                    <i class="bi bi-plus-circle me-1"></i> Add Raw Material
                </button>
            </div>
            <div class="card-body">
                <!-- Individual Cards Container -->
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-1 text-warning"></i>Individual Raw Materials (<span id="rmCountBadge">0</span>)</h6>
                </div>
                <div id="rawMaterialContainer" class="d-flex flex-column gap-3 mb-4"></div>

                <!-- Live Details Table -->
                <div class="card border shadow-sm mb-4" id="rmDetailsTableContainer">
                    <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold small"><i class="bi bi-table me-1 text-warning"></i>Raw Materials Inward &amp; Details Table</span>
                        <span class="badge bg-secondary-subtle text-secondary border" id="rmTableCountBadge">0 Items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 35px;">#</th>
                                    <th>Material / Item Name</th>
                                    <th>Color / Shade</th>
                                    <th>Count / Size</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase Price (₹)</th>
                                    <th class="text-end">Base Value</th>
                                    <th>HSN</th>
                                    <th>GST Type</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end bg-success-subtle text-success">Total Value</th>
                                </tr>
                            </thead>
                            <tbody id="rmDetailsTableBody">
                                <tr><td colspan="12" class="text-center text-muted py-4">Add raw materials to see their inward details.</td></tr>
                            </tbody>
                            <tfoot id="rmDetailsTableFoot" class="table-light fw-bold"></tfoot>
                        </table>
                    </div>
                </div>

                <!-- Batch Metrics Summary -->
                <div class="p-3 bg-warning-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <strong class="text-dark"><i class="bi bi-calculator me-1"></i>Raw Materials Procurement Summary</strong>
                    </div>
                    <div class="d-flex flex-wrap gap-4 align-items-center">
                        <div><small class="text-muted d-block">Total Items</small><strong id="summaryRmItems">0 Items</strong></div>
                        <div><small class="text-muted d-block">Total Quantity</small><strong id="summaryRmQuantity">0</strong></div>
                        <div><small class="text-muted d-block">Base Total</small><strong id="summaryRmBase" class="text-secondary">₹0.00</strong></div>
                        <div><small class="text-muted d-block">Total Tax</small><strong id="summaryRmTax" class="text-secondary">₹0.00</strong></div>
                        <div><small class="text-muted d-block">Total Purchase Cost</small><strong id="summaryRmTotal" class="text-success fs-5">₹0.00</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. THIRD-PARTY PRODUCTS SECTION -->
    <div id="sectionThirdParty" class="d-none">
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-info"></i>Third-Party Finished Products Inward</h5>
                </div>
                <button type="button" class="btn btn-sm btn-info text-white fw-semibold" id="btnAddThirdPartyItem">
                    <i class="bi bi-plus-circle me-1"></i> Add Third-Party Product
                </button>
            </div>
            <div class="card-body">
                <!-- Individual Cards Container -->
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-boxes me-1 text-info"></i>Individual Third-Party Products (<span id="tpCountBadge">0</span>)</h6>
                </div>
                <div id="thirdPartyContainer" class="d-flex flex-column gap-3 mb-4"></div>

                <!-- Live Details Table -->
                <div class="card border shadow-sm mb-4" id="tpDetailsTableContainer">
                    <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold small"><i class="bi bi-table me-1 text-info"></i>Third-Party Products Inward &amp; Details Table</span>
                        <span class="badge bg-secondary-subtle text-secondary border" id="tpTableCountBadge">0 Products</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 35px;">#</th>
                                    <th>Product / Item Name</th>
                                    <th>Auto SKU</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase Price (₹)</th>
                                    <th class="text-end">Base Value</th>
                                    <th>HSN</th>
                                    <th>GST Type</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end bg-success-subtle text-success">Total Value</th>
                                </tr>
                            </thead>
                            <tbody id="tpDetailsTableBody">
                                <tr><td colspan="11" class="text-center text-muted py-4">Add third-party products to see their inward details.</td></tr>
                            </tbody>
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
                        <div><small class="text-muted d-block">Total Quantity</small><strong id="summaryTpUnits">0 Pcs</strong></div>
                        <div><small class="text-muted d-block">Base Total</small><strong id="summaryTpBase" class="text-secondary">₹0.00</strong></div>
                        <div><small class="text-muted d-block">Total Tax</small><strong id="summaryTpTax" class="text-secondary">₹0.00</strong></div>
                        <div><small class="text-muted d-block">Total Purchase Cost</small><strong id="summaryTpTotal" class="text-success fs-5">₹0.00</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Financial Breakdown Summary (Unified Across Selected Categories) -->
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-2 px-3 fw-bold small text-muted text-uppercase d-flex justify-content-between align-items-center">
            <span><i class="bi bi-receipt me-1 text-primary"></i>Invoice Financial Breakdown</span>
            <span id="activeCategoriesCountBadge" class="badge bg-primary-subtle text-primary border">1 Category Selected</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7 border-end">
                    <div id="rowSummaryRoll" class="d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-light">
                        <span class="text-dark"><i class="bi bi-layers-half text-primary me-2"></i>1. Fabric Rolls (<span id="summaryRollItemsCount">0</span> rolls, <span id="summaryRollWeight">0.00 kg</span>)</span>
                        <span class="fw-bold font-monospace text-primary" id="summaryRollVal">₹0.00</span>
                    </div>
                    <div id="rowSummaryRm" class="d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-light d-none">
                        <span class="text-dark"><i class="bi bi-boxes text-warning me-2"></i>2. Raw Materials (<span id="summaryRmItemsCount">0</span> items, <span id="summaryRmUnits">0</span> qty)</span>
                        <span class="fw-bold font-monospace text-warning-emphasis" id="summaryRmVal">₹0.00</span>
                    </div>
                    <div id="rowSummaryTp" class="d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-light d-none">
                        <span class="text-dark"><i class="bi bi-box-seam text-info me-2"></i>3. Third-Party Products (<span id="summaryTpItemsCount">0</span> products, <span id="summaryTpUnitsCount">0</span> pcs)</span>
                        <span class="fw-bold font-monospace text-info" id="summaryTpVal">₹0.00</span>
                    </div>
                </div>
                <div class="col-md-5 ps-md-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center mb-2 text-muted small">
                        <span>Taxable Base Subtotal</span><span class="font-monospace fw-semibold fs-6" id="purchaseSubtotal">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 text-muted small">
                        <span>Total Taxes (GST)</span><span class="font-monospace fw-semibold text-danger fs-6" id="purchaseTaxes">₹0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="fs-6 text-dark">Invoice Grand Total</strong><strong class="fs-4 text-success font-monospace" id="purchaseTotal">₹0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" id="savePurchaseSubmitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase</button>
    </div>
</form>

<div class="modal fade" id="purchaseLabelsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-qr-code me-2 text-warning"></i>Fabric Roll Labels Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light"><div id="purchaseLabelCards" class="d-flex flex-wrap justify-content-center gap-3"></div></div>
            <div class="modal-footer"><span class="small text-muted me-auto" id="purchaseLabelCount"></span><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

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
#supplierSearchDropdown .supplier-opt-item:hover,
#supplierSearchDropdown .supplier-opt-item:focus,
#supplierSearchDropdown .supplier-opt-item.active,
#supplierSearchDropdown .supplier-opt-item:active {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}
#supplierSearchDropdown .supplier-opt-item:hover *,
#supplierSearchDropdown .supplier-opt-item:focus *,
#supplierSearchDropdown .supplier-opt-item.active *,
#supplierSearchDropdown .supplier-opt-item:active * {
    color: #ffffff !important;
}
#supplierSearchDropdown .supplier-opt-item:hover .badge,
#supplierSearchDropdown .supplier-opt-item:focus .badge,
#supplierSearchDropdown .supplier-opt-item.active .badge,
#supplierSearchDropdown .supplier-opt-item:active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
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

.tax-strip-container {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.5rem !important;
    padding: 14px 18px !important;
    margin-top: 8px !important;
    margin-bottom: 8px !important;
}
.tax-strip-container .item-gst-type,
.item-gst-type {
    min-width: 195px;
    padding-right: 1.85rem !important;
    font-size: 0.84rem;
}
.tax-strip-container .item-tax-treatment {
    min-width: 160px;
    padding-right: 1.85rem !important;
    font-size: 0.84rem;
}
.tax-strip-container .item-tax-mode {
    min-width: 130px;
    padding-right: 1.85rem !important;
    font-size: 0.84rem;
}
.tax-strip-container .item-tax-value,
.tax-strip-container .item-cgst-value {
    font-size: 0.84rem;
    text-align: center;
}
</style>

<template id="fabricRollTemplate">
    <div class="roll-card card border shadow-sm" data-index="">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <strong><i class="bi bi-layers text-primary me-1"></i><span class="roll-title">ROLL #1</span> <span class="badge bg-dark roll-code-text"></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-roll"><i class="bi bi-trash me-1"></i>Delete</button>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4"><label class="form-label required">Roll No. / Code *</label><input class="form-control roll-number text-uppercase" maxlength="50" required></div>
                <div class="col-lg-2 col-md-4"><label class="form-label required">Color / Shade *</label><input class="form-control roll-color text-uppercase" maxlength="100" required placeholder="E.G. NAVY BLUE"></div>
                <div class="col-lg-2 col-md-4"><label class="form-label required">Design Name / Pattern *</label><input class="form-control roll-design text-uppercase" maxlength="150" required placeholder="E.G. FLORAL 01"></div>
                <div class="col-lg-1 col-md-3"><label class="form-label required">Designs *</label><input type="text" inputmode="numeric" pattern="[0-9]+" data-min="1" data-step="1" class="form-control roll-design-count numeric-text-input" value="1" required></div>
                <div class="col-lg-1 col-md-3"><label class="form-label required">RQ *</label><input type="text" inputmode="numeric" pattern="[0-9]+" data-min="1" data-step="1" class="form-control roll-quantity numeric-text-input" value="1" required></div>
                <div class="col-lg-2 col-md-3"><label class="form-label required">Weight (kg) *</label><div class="input-group"><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" class="form-control roll-weight numeric-text-input" required><span class="input-group-text">kg</span></div></div>
                <div class="col-lg-2 col-md-3"><label class="form-label required">Purchase Price (₹) *</label><div class="input-group"><span class="input-group-text">₹</span><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0.01" data-step="0.01" class="form-control roll-price numeric-text-input" required></div></div>
                <div class="col-lg-2 col-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">HSN Code</label>
                        <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold btn-add-hsn-trigger" style="font-size: 11px;" title="Add new HSN Code">
                            <i class="bi bi-plus-circle me-0.5"></i>+ Add HSN
                        </button>
                    </div>
                    <div class="dropdown hsn-dropdown w-100 position-relative">
                        <input type="hidden" class="roll-hsn">
                        <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center hsn-dropdown-btn font-monospace py-1.5 px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="font-size: 0.82rem; height: 38px;">
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
                <div class="col-lg-2 col-md-3"><label class="form-label">GST</label><select class="form-select item-gst-type"><option value="SGST">SGST</option><option value="IGST">IGST</option><option value="CGST">CGST</option><option value="BOTH">Both (CGST and SGST)</option><option value="EXEMPTED" selected>Exempted</option></select></div>
                <div class="col-lg-2 col-md-3"><label class="form-label">GST Included / Excluded</label><select class="form-select item-tax-treatment"><option value="INCLUDED" selected>GST Included</option><option value="EXCLUDED">GST Excluded</option></select></div>
                <div class="col-lg-2 col-md-3 tax-mode-field d-none"><label class="form-label">Tax Input Type</label><select class="form-select item-tax-mode"><option value="PERCENT">Percentage (%)</option><option value="AMOUNT">Amount (₹)</option></select></div>
                <div class="col-lg-2 col-md-3 tax-value-field d-none"><label class="form-label item-tax-label">Tax</label><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control item-tax-value numeric-text-input" value="0"></div>
                <div class="col-lg-2 col-md-3 tax-cgst-field d-none"><label class="form-label item-cgst-label">CGST</label><input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control item-cgst-value numeric-text-input" value="0"></div>
                <div class="col-md-6"><label class="form-label">Roll Sample Photo <small class="text-muted">(Optional — JPG, PNG, WEBP)</small></label><input type="file" class="form-control roll-photo" accept=".jpg,.jpeg,.png,.webp"></div>
                <div class="col-md-6 roll-preview d-none"><img class="img-thumbnail" style="height:54px" alt="Roll photo preview"></div>
            </div>
        </div>
    </div>
</template>

<template id="rawMaterialTemplate">
    <div class="rm-card card border shadow-sm mb-3 rm-row" data-index="">
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
                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="tax-strip-container rounded-3 border bg-light-subtle d-flex flex-wrap align-items-center gap-2 gap-lg-3 p-3 px-md-4 my-2">
                        <div style="min-width: 220px; flex: 1 1 240px; max-width: 330px;">
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
                        <div style="min-width: 195px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                            <select class="form-select form-select-sm item-gst-type" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="BOTH" selected>Both (CGST and SGST)</option>
                                <option value="IGST">IGST</option>
                                <option value="CGST">CGST</option>
                                <option value="SGST">SGST</option>
                                <option value="EXEMPTED">Exempted</option>
                            </select>
                        </div>
                        <div style="min-width: 160px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                            <select class="form-select form-select-sm item-tax-treatment" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="INCLUDED" selected>GST Included</option>
                                <option value="EXCLUDED">GST Excluded</option>
                            </select>
                        </div>
                        <div class="tax-mode-field" style="min-width: 130px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                            <select class="form-select form-select-sm item-tax-mode" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="PERCENT" selected>Percentage (%)</option>
                                <option value="AMOUNT">Amount (₹)</option>
                            </select>
                        </div>
                        <div class="tax-value-field" style="min-width: 90px; max-width: 105px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold item-tax-label">SGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold text-center" style="font-size: 0.84rem;" value="2.50">
                        </div>
                        <div class="tax-cgst-field" style="min-width: 90px; max-width: 105px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold text-center" style="font-size: 0.84rem;" value="2.50">
                        </div>
                        <div class="ms-auto text-end flex-shrink-0 ps-3 pe-1">
                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                            <strong class="text-success fs-6 rm-line-total">₹0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="thirdPartyTemplate">
    <div class="tp-card card border shadow-sm mb-3 tp-row" data-index="">
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
            <input type="hidden" class="tp-sku">
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
                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="tax-strip-container rounded-3 border bg-light-subtle d-flex flex-wrap align-items-center gap-2 gap-lg-3 p-3 px-md-4 my-2">
                        <div style="min-width: 220px; flex: 1 1 240px; max-width: 330px;">
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
                        <div style="min-width: 195px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">GST Type</label>
                            <select class="form-select form-select-sm item-gst-type" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="BOTH" selected>Both (CGST and SGST)</option>
                                <option value="IGST">IGST</option>
                                <option value="CGST">CGST</option>
                                <option value="SGST">SGST</option>
                                <option value="EXEMPTED">Exempted</option>
                            </select>
                        </div>
                        <div style="min-width: 160px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">GST Included / Excluded</label>
                            <select class="form-select form-select-sm item-tax-treatment" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="INCLUDED" selected>GST Included</option>
                                <option value="EXCLUDED">GST Excluded</option>
                            </select>
                        </div>
                        <div class="tax-mode-field" style="min-width: 130px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold">Tax Input Type</label>
                            <select class="form-select form-select-sm item-tax-mode" style="padding-right: 1.85rem; font-size: 0.84rem;">
                                <option value="PERCENT" selected>Percentage (%)</option>
                                <option value="AMOUNT">Amount (₹)</option>
                            </select>
                        </div>
                        <div class="tax-value-field" style="min-width: 90px; max-width: 105px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold item-tax-label">SGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-tax-value numeric-text-input fw-semibold text-center" style="font-size: 0.84rem;" value="2.50">
                        </div>
                        <div class="tax-cgst-field" style="min-width: 90px; max-width: 105px; flex: 0 0 auto;">
                            <label class="form-label mb-1 small fw-semibold item-cgst-label">CGST (%)</label>
                            <input type="text" inputmode="decimal" pattern="([0-9]+([.][0-9]*)?|[.][0-9]+)" data-min="0" data-max="100" data-step="0.01" class="form-control form-control-sm item-cgst-value numeric-text-input fw-semibold text-center" style="font-size: 0.84rem;" value="2.50">
                        </div>
                        <div class="ms-auto text-end flex-shrink-0 ps-3 pe-1">
                            <small class="text-muted d-block" style="font-size:11px">Item Total incl. Tax</small>
                            <strong class="text-success fs-6 tp-line-total">₹0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
(() => {
    const form = document.getElementById('productionPurchaseForm');
    const rollsContainer = document.getElementById('rollsContainer');
    const rollTemplate = document.getElementById('fabricRollTemplate');
    const supplierSelect = document.getElementById('purchaseSeller');
    const supplierDetails = document.getElementById('selectedSupplierDetails');
    const useMappingInvoice = document.getElementById('useMappingInvoice');
    const purchaseCategorySelectorCard = document.getElementById('purchaseCategorySelectorCard');
    const purchaseItemSections = ['sectionFabricRoll', 'sectionRawMaterial', 'sectionThirdParty']
        .map(id => document.getElementById(id)).filter(Boolean);
    let rollIndex = 0;
    let nextSequence = 1;
    let sequencePrefix = 'FB<?= date('my') ?>';
    window.hsnCodesList = <?= json_encode($hsnCodes ?? []) ?>;
    window.MASTER_PRODUCTS = <?= json_encode($masterProducts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.MASTER_VARIANTS = <?= json_encode($masterVariants ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.MASTER_SIZES = <?= json_encode($sizes ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.MASTER_COLORS = <?= json_encode($colors ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const activePurchaseTypes = new Set(['FABRIC_ROLL']);
    let currentPurchaseType = 'FABRIC_ROLL';
    const money = value => '₹' + (Number(value) || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    const safe = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const numericTextPattern = /^(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/;
    let activeHsnTarget = null;

    useMappingInvoice?.addEventListener('change', function () {
        const useMapping = useMappingInvoice.checked;
        purchaseCategorySelectorCard?.classList.toggle('d-none', useMapping);
        const typeBySection = {
            sectionFabricRoll: 'FABRIC_ROLL',
            sectionRawMaterial: 'RAW_MATERIAL',
            sectionThirdParty: 'THIRD_PARTY'
        };
        purchaseItemSections.forEach(section => {
            section.classList.toggle('d-none', useMapping || !activePurchaseTypes.has(typeBySection[section.id]));
        });
    });

    function sanitizeNumericInput(input) {
        if (!input || !input.matches('.numeric-text-input')) return;
        const isIntegerOnly = input.matches('.roll-design-count, .roll-quantity') || input.dataset.step === '1' || input.getAttribute('step') === '1' || input.getAttribute('inputmode') === 'numeric';
        const raw = input.value;
        let clean = '';
        if (isIntegerOnly) {
            clean = raw.replace(/\D/g, '');
        } else {
            clean = raw.replace(/[^0-9.]/g, '');
            const parts = clean.split('.');
            if (parts.length > 2) {
                clean = parts[0] + '.' + parts.slice(1).join('');
            }
        }
        if (raw !== clean) {
            let start = null;
            try { start = input.selectionStart; } catch (_) {}
            input.value = clean;
            if (start !== null) {
                try {
                    const newPos = Math.min(start - (raw.length - clean.length), clean.length);
                    input.setSelectionRange(Math.max(0, newPos), Math.max(0, newPos));
                } catch (_) {}
            }
        }
    }

    function handleNumericKeyDown(event) {
        const input = event.target;
        if (!input || !input.matches('.numeric-text-input, input[type="number"]')) return;

        if (
            event.ctrlKey || event.metaKey || event.altKey ||
            ['Backspace', 'Delete', 'Tab', 'Enter', 'Escape', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)
        ) {
            return;
        }

        // Strictly block exponential notation 'e', 'E' and sign keys '+', '-'
        if (['e', 'E', '+', '-'].includes(event.key)) {
            event.preventDefault();
            return;
        }

        const isIntegerOnly = input.matches('.roll-design-count, .roll-quantity') || input.dataset.step === '1' || input.getAttribute('step') === '1' || input.getAttribute('inputmode') === 'numeric';

        if (isIntegerOnly) {
            if (!/^[0-9]$/.test(event.key)) {
                event.preventDefault();
            }
        } else {
            if (event.key === '.') {
                if (input.value && input.value.includes('.')) {
                    let selectedText = '';
                    try {
                        const selStart = input.selectionStart;
                        const selEnd = input.selectionEnd;
                        selectedText = (selStart !== null && selEnd !== null) ? input.value.substring(selStart, selEnd) : '';
                    } catch (_) {}
                    if (!selectedText.includes('.')) {
                        event.preventDefault();
                    }
                }
            } else if (!/^[0-9]$/.test(event.key)) {
                event.preventDefault();
            }
        }
    }

    function handleNumericBeforeInput(event) {
        const input = event.target;
        if (!input || !input.matches('.numeric-text-input, input[type="number"]')) return;
        if (!event.data) return;

        if (['e', 'E', '+', '-'].some(ch => event.data.includes(ch))) {
            event.preventDefault();
            return;
        }

        const isIntegerOnly = input.matches('.roll-design-count, .roll-quantity') || input.dataset.step === '1' || input.getAttribute('step') === '1' || input.getAttribute('inputmode') === 'numeric';

        if (isIntegerOnly) {
            if (/[^0-9]/.test(event.data)) {
                event.preventDefault();
            }
        } else {
            if (/[^0-9.]/.test(event.data)) {
                event.preventDefault();
            } else if (event.data.includes('.') && input.value && input.value.includes('.')) {
                let selectedText = '';
                try {
                    const selStart = input.selectionStart;
                    const selEnd = input.selectionEnd;
                    selectedText = (selStart !== null && selEnd !== null) ? input.value.substring(selStart, selEnd) : '';
                } catch (_) {}
                if (!selectedText.includes('.')) {
                    event.preventDefault();
                }
            }
        }
    }

    function handleNumericPaste(event) {
        const input = event.target;
        if (!input || !input.matches('.numeric-text-input')) return;
        setTimeout(() => {
            sanitizeNumericInput(input);
            validateNumericTextInput(input);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }, 0);
    }

    document.addEventListener('keydown', handleNumericKeyDown);
    document.addEventListener('beforeinput', handleNumericBeforeInput);
    document.addEventListener('paste', handleNumericPaste);

    function validateNumericTextInput(input) {
        if (!input || input.disabled || input.closest('.d-none') || input.offsetParent === null) {
            if (input) input.setCustomValidity('');
            return true;
        }
        const value = input.value.trim();
        let message = '';
        if (value === '') {
            message = input.required ? 'Enter a value.' : '';
        } else if (!numericTextPattern.test(value) || !Number.isFinite(Number(value))) {
            message = 'Enter a valid number.';
        } else {
            const number = Number(value);
            const minAttribute = input.dataset.min;
            const maxAttribute = input.dataset.max;
            const stepAttribute = input.dataset.step;
            if (minAttribute !== undefined && number < Number(minAttribute)) {
                message = `Value must be at least ${minAttribute}.`;
            } else if (maxAttribute !== undefined && number > Number(maxAttribute)) {
                message = `Value must be no more than ${maxAttribute}.`;
            } else if (stepAttribute && stepAttribute !== 'any') {
                const step = Number(stepAttribute);
                const base = minAttribute !== undefined ? Number(minAttribute) : 0;
                const stepCount = (number - base) / step;
                if (step > 0 && Math.abs(stepCount - Math.round(stepCount)) > 1e-8) {
                    message = `Value must use increments of ${stepAttribute}.`;
                }
            }
        }
        input.setCustomValidity(message);
        return message === '';
    }

    form.addEventListener('input', event => {
        if (event.target.matches('.numeric-text-input')) {
            sanitizeNumericInput(event.target);
            validateNumericTextInput(event.target);
        }
    });
    form.addEventListener('submit', event => {
        if (useMappingInvoice?.checked) return;
        const invalidInput = Array.from(form.querySelectorAll('.numeric-text-input'))
            .filter(input => !input.disabled && !input.closest('.d-none') && input.offsetParent !== null)
            .find(input => !validateNumericTextInput(input));
        if (invalidInput) {
            event.preventDefault();
            invalidInput.reportValidity();
            form.removeAttribute('data-submitting');
            const submitBtn = document.getElementById('savePurchaseSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.pointerEvents = '';
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Purchase';
            }
        }
    }, true);

    function openQuickAddHsnModal(dropdownEl, hiddenInput, presetCode = '') {
        activeHsnTarget = {
            dropdownEl,
            hiddenInput,
            btn: dropdownEl.querySelector('.hsn-dropdown-btn'),
            btnText: dropdownEl.querySelector('.hsn-btn-text'),
            card: dropdownEl.closest('.roll-card, .rm-card, .tp-card')
        };
        const codeInp = document.getElementById('quickHsnCodeInput');
        const descInp = document.getElementById('quickHsnDescInput');
        const gstInp = document.getElementById('quickHsnGstRateInput');
        const alertBox = document.getElementById('quickHsnAlert');
        if (codeInp) codeInp.value = presetCode || '';
        if (descInp) descInp.value = '';
        if (gstInp) gstInp.value = '5';
        if (alertBox) alertBox.classList.add('d-none');
        document.querySelectorAll('#modalQuickAddHsn .quick-rate-pill').forEach(p => {
            if (p.dataset.rate === '5') {
                p.classList.remove('btn-outline-secondary');
                p.classList.add('btn-primary', 'active');
            } else {
                p.classList.remove('btn-primary', 'active');
                p.classList.add('btn-outline-secondary');
            }
        });
        const modalEl = document.getElementById('modalQuickAddHsn');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            setTimeout(() => {
                if (presetCode && descInp) descInp.focus();
                else if (codeInp) codeInp.focus();
            }, 300);
        }
    }

    function initQuickAddHsnModalHandler() {
        const btnSave = document.getElementById('btnSaveQuickHsn');
        if (!btnSave) return;

        // Wire quick-rate pills inside modal
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

        btnSave.addEventListener('click', () => {
            const code = (document.getElementById('quickHsnCodeInput')?.value || '').trim().toUpperCase();
            const desc = (document.getElementById('quickHsnDescInput')?.value || '').trim();
            const rateVal = parseFloat(document.getElementById('quickHsnGstRateInput')?.value || 0);
            const gstRate = isNaN(rateVal) ? 0 : rateVal;
            const alertBox = document.getElementById('quickHsnAlert');

            if (!code || code.length < 2) {
                if (alertBox) { alertBox.textContent = 'Please enter a valid HSN code (at least 2 characters).'; alertBox.classList.remove('d-none'); }
                return;
            }
            if (!desc) {
                if (alertBox) { alertBox.textContent = 'Please enter a commodity / item description.'; alertBox.classList.remove('d-none'); }
                return;
            }

            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            const fd = new FormData();
            fd.append('hsn_code', code);
            fd.append('description', desc);
            fd.append('gst_rate', gstRate);
            const csrf = document.querySelector('input[name="csrf_token"]');
            if (csrf) fd.append('csrf_token', csrf.value);

            fetch('<?= url("api/hsn-codes/store") ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save &amp; Select HSN';

                if (res.success && res.data) {
                    const newHsn = res.data;
                    const existingIdx = (window.hsnCodesList || []).findIndex(h => String(h.hsn_code).toUpperCase() === String(newHsn.hsn_code).toUpperCase());
                    if (existingIdx >= 0) {
                        window.hsnCodesList[existingIdx] = newHsn;
                    } else {
                        if (!window.hsnCodesList) window.hsnCodesList = [];
                        window.hsnCodesList.unshift(newHsn);
                    }

                    if (activeHsnTarget && activeHsnTarget.hiddenInput) {
                        activeHsnTarget.hiddenInput.value = newHsn.hsn_code;
                        if (activeHsnTarget.btnText) {
                            activeHsnTarget.btnText.innerHTML = `<span class="fw-bold text-primary">${safe(newHsn.hsn_code)}</span> <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1 ms-1" style="font-size:10px;">${parseFloat(newHsn.gst_rate ?? 0)}% GST</span>`;
                        }
                        const bsDropdown = bootstrap.Dropdown.getInstance(activeHsnTarget.btn);
                        if (bsDropdown) bsDropdown.hide();
                        
                        // Automatically apply tax rules to the row
                        const card = activeHsnTarget.card || activeHsnTarget.hiddenInput.closest('.roll-card, .rm-card, .tp-card');
                        if (card) {
                            applyHsnTaxRules(card, newHsn.hsn_code);
                        }

                        activeHsnTarget.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        activeHsnTarget.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    const modalEl = document.getElementById('modalQuickAddHsn');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }
                } else {
                    if (alertBox) {
                        alertBox.textContent = res.message || 'Failed to save HSN Code.';
                        alertBox.classList.remove('d-none');
                    }
                }
            })
            .catch(err => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save &amp; Select HSN';
                if (alertBox) {
                    alertBox.textContent = 'Server error occurred while saving HSN Code.';
                    alertBox.classList.remove('d-none');
                }
            });
        });
    }
    initQuickAddHsnModalHandler();

    function initHsnDropdown(container, hiddenSelector) {
        const dropdownEl = container.querySelector('.hsn-dropdown');
        if (!dropdownEl) return;
        const hiddenInput = dropdownEl.querySelector(hiddenSelector);
        const btn = dropdownEl.querySelector('.hsn-dropdown-btn');
        const btnText = dropdownEl.querySelector('.hsn-btn-text');
        const searchInput = dropdownEl.querySelector('.hsn-search-input');
        const listContainer = dropdownEl.querySelector('.hsn-options-list');

        const triggerBtn = container.querySelector('.btn-add-hsn-trigger');
        if (triggerBtn) {
            triggerBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openQuickAddHsnModal(dropdownEl, hiddenInput);
            });
        }

        function updateBtnDisplay(code, desc, gstRate = null) {
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
                    const bsDropdown = bootstrap.Dropdown.getInstance(btn);
                    if (bsDropdown) bsDropdown.hide();
                    openQuickAddHsnModal(dropdownEl, hiddenInput, btnEl.dataset.preset || '');
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
                    const bsDropdown = bootstrap.Dropdown.getInstance(btn);
                    if (bsDropdown) bsDropdown.hide();

                    const card = container.closest('.roll-card, .rm-card, .tp-card') || container;
                    if (card) {
                        applyHsnTaxRules(card, code);
                    }

                    if (hiddenInput) {
                        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        }

        renderOptions();

        dropdownEl.addEventListener('shown.bs.dropdown', () => {
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

        // If an HSN code with rate > 0 is selected and current type is EXEMPTED, auto-switch to BOTH
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
            // EXEMPTED
            if (taxValInput) taxValInput.value = '0';
            if (cgstValInput) cgstValInput.value = '0';
        }

        updateTaxInputs(card);
    }
    function calculateItemTax(item, grossAmount) {
        const taxTreatment = item.querySelector('.item-tax-treatment')?.value || 'INCLUDED';
        const type = item.querySelector('.item-gst-type')?.value || 'EXEMPTED';
        const mode = item.querySelector('.item-tax-mode')?.value || 'PERCENT';
        const value = Number(item.querySelector('.item-tax-value')?.value) || 0;
        const cgstValue = Number(item.querySelector('.item-cgst-value')?.value) || 0;

        if (type === 'EXEMPTED' || grossAmount <= 0) {
            return {
                treatment: taxTreatment,
                type, mode, value,
                base: grossAmount,
                tax: 0,
                sgstAmount: 0,
                cgstAmount: 0,
                igstAmount: 0,
                amount: 0,
                total: grossAmount
            };
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
                    return {
                        treatment: taxTreatment,
                        type, mode, value,
                        base,
                        tax,
                        sgstAmount: sgst,
                        cgstAmount: cgst,
                        igstAmount: igst,
                        amount: tax,
                        total: grossAmount
                    };
                }
            } else {
                // Fixed amount
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
                return {
                    treatment: taxTreatment,
                    type, mode, value,
                    base,
                    tax,
                    sgstAmount: sgst,
                    cgstAmount: cgst,
                    igstAmount: igst,
                    amount: tax,
                    total: grossAmount
                };
            }
        }

        // EXCLUDED mode
        const toAmount = taxVal => mode === 'AMOUNT' ? taxVal : Math.round((grossAmount * taxVal / 100) * 100) / 100;
        const sgstAmount = ['SGST', 'BOTH'].includes(type) ? toAmount(value) : 0;
        const cgstAmount = ['CGST', 'BOTH'].includes(type) ? toAmount(type === 'BOTH' ? cgstValue : value) : 0;
        const igstAmount = type === 'IGST' ? toAmount(value) : 0;
        const tax = Math.round((sgstAmount + cgstAmount + igstAmount) * 100) / 100;
        return {
            treatment: taxTreatment,
            type, mode, value,
            base: grossAmount,
            tax,
            sgstAmount,
            cgstAmount,
            igstAmount,
            amount: tax,
            total: Math.round((grossAmount + tax) * 100) / 100
        };
    }

    function updateTaxInputs(item) {
        const type = item.querySelector('.item-gst-type').value;
        const active = type !== 'EXEMPTED';
        const modeField = item.querySelector('.tax-mode-field');
        const valueField = item.querySelector('.tax-value-field');
        const cgstField = item.querySelector('.tax-cgst-field');
        const valueInput = item.querySelector('.item-tax-value');
        const cgstInput = item.querySelector('.item-cgst-value');
        modeField.classList.toggle('d-none', !active);
        valueField.classList.toggle('d-none', !active);
        cgstField.classList.toggle('d-none', type !== 'BOTH');
        valueInput.required = active;
        cgstInput.required = type === 'BOTH';
        item.querySelector('.item-tax-label').textContent = type === 'EXEMPTED'
            ? 'Tax'
            : `${type === 'BOTH' ? 'SGST' : type} (${item.querySelector('.item-tax-mode').value === 'PERCENT' ? '%' : '₹'})`;
        item.querySelector('.item-cgst-label').textContent = `CGST (${item.querySelector('.item-tax-mode').value === 'PERCENT' ? '%' : '₹'})`;
        [valueInput, cgstInput].forEach(input => {
            if (item.querySelector('.item-tax-mode').value === 'PERCENT') input.dataset.max = '100';
            else delete input.dataset.max;
        });
    }

    function updateSupplierDetails() {
        const selected = supplierSelect.options[supplierSelect.selectedIndex];
        if (!selected || !selected.value) {
            supplierDetails.classList.add('d-none');
            return;
        }

        const fields = {
            name: selected.dataset.supplierName,
            company: selected.dataset.supplierCompany,
            phone: selected.dataset.supplierPhone,
            email: selected.dataset.supplierEmail,
            gst: selected.dataset.supplierGst,
            location: [selected.dataset.supplierCity, selected.dataset.supplierState].filter(Boolean).join(', '),
            address: selected.dataset.supplierAddress
        };
        Object.entries(fields).forEach(([field, value]) => {
            const el = supplierDetails.querySelector(`[data-supplier-detail="${field}"]`);
            if (el) el.textContent = value || '—';
        });
        supplierDetails.classList.remove('d-none');
    }

    function updateSummary() {
        const cards = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        const fabDesc = document.getElementById('fabricDescription');
        if (fabDesc) fabDesc.required = (activePurchaseTypes.has('FABRIC_ROLL') && cards.length > 0);
        const totalWeight = cards.reduce((sum, card) => sum + (Number(card.querySelector('.roll-weight').value) || 0), 0);
        const totalPrice = cards.reduce((sum, card) => {
            const price = Number(card.querySelector('.roll-price').value) || 0;
            const qty = Math.max(1, Number(card.querySelector('.roll-quantity')?.value) || 1);
            const lineGross = price * qty;
            const tax = calculateItemTax(card, lineGross);
            return sum + tax.total;
        }, 0);
        const totalRq = cards.reduce((sum, card) => sum + (Number(card.querySelector('.roll-quantity').value) || 0), 0);
        const colors = new Set(cards.map(card => card.querySelector('.roll-color').value.trim().toUpperCase()).filter(Boolean));
        const designs = new Set(cards.map(card => card.querySelector('.roll-design').value.trim().toUpperCase()).filter(Boolean));
        const countBadge = document.getElementById('rollCountBadge');
        if (countBadge) countBadge.textContent = cards.length;
        const tblCountBadge = document.getElementById('tableRollCountBadge');
        if (tblCountBadge) tblCountBadge.textContent = cards.length + (cards.length === 1 ? ' Roll' : ' Rolls');
        const sumRolls = document.getElementById('summaryTotalRolls');
        if (sumRolls) sumRolls.textContent = cards.length + (cards.length === 1 ? ' Roll' : ' Rolls');
        const sumRQ = document.getElementById('summaryTotalRQ');
        if (sumRQ) sumRQ.textContent = totalRq.toLocaleString('en-IN') + ' RQ';
        const sumColDes = document.getElementById('summaryColorsDesigns');
        if (sumColDes) sumColDes.textContent = colors.size + ' Colors • ' + designs.size + ' Designs';
        const sumWeight = document.getElementById('summaryTotalWeight');
        if (sumWeight) sumWeight.textContent = totalWeight.toFixed(2) + ' kg';
        const sumPrice = document.getElementById('summaryTotalPrice');
        if (sumPrice) sumPrice.textContent = money(totalPrice);

        const body = document.getElementById('rollDetailsTableBody');
        if (body) {
            if (!cards.length) {
                body.innerHTML = '<tr><td colspan="13" class="text-center text-muted py-4">Add fabric rolls to see their inventory details.</td></tr>';
            } else {
                body.innerHTML = cards.map((card, index) => {
                    const rollNum = card.querySelector('.roll-number')?.value || ('ROLL #' + (index + 1));
                    const hsn = card.querySelector('.roll-hsn')?.value?.trim() || '—';
                    const color = card.querySelector('.roll-color')?.value || '—';
                    const design = card.querySelector('.roll-design')?.value || '—';
                    const designCount = Number(card.querySelector('.roll-design-count')?.value) || 1;
                    const qty = Math.max(1, Number(card.querySelector('.roll-quantity')?.value) || 1);
                    const weight = Number(card.querySelector('.roll-weight')?.value) || 0;
                    const price = Number(card.querySelector('.roll-price')?.value) || 0;
                    const lineGross = price * qty;
                    const tax = calculateItemTax(card, lineGross);
                    const totalValue = tax.total;
                    const start = Number(card.dataset.startSeq) || 0;
                    const end = start + Math.max(qty, 1) - 1;
                    const codeStart = start ? sequencePrefix + String(start).padStart(2, '0') : '—';
                    const codeEnd = start ? sequencePrefix + String(end).padStart(2, '0') : '—';
                    const photo = card.querySelector('.roll-photo')?.files[0];
                    const previewImg = card.querySelector('.roll-preview img');
                    const photoHtml = photo && previewImg && previewImg.src ? `<img src="${previewImg.src}" alt="Roll" style="width:38px;height:38px;object-fit:cover;border-radius:4px;">` : '<span class="text-muted"><i class="bi bi-camera"></i></span>';
                    const taxLabel = tax.type === 'BOTH' ? 'SGST + CGST' : tax.type;
                    const treatmentBadge = tax.treatment === 'INCLUDED' ? '<small class="text-muted d-block" style="font-size:10px;">(Incl. Tax)</small>' : '<small class="text-muted d-block" style="font-size:10px;">(Excl. Tax)</small>';
                    return `<tr>
                        <td class="text-center fw-bold">${index + 1}</td>
                        <td class="text-center">${photoHtml}</td>
                        <td><strong>${safe(rollNum)}</strong><small class="d-block font-monospace text-primary">${safe(codeStart)}${codeStart !== codeEnd ? '–' + safe(codeEnd) : ''}</small></td>
                        <td><span class="badge bg-light text-dark border font-monospace">${safe(hsn)}</span></td>
                        <td><span class="badge bg-light text-dark border">${safe(color)}</span></td>
                        <td>${safe(design)}</td>
                        <td class="text-center">${designCount}</td>
                        <td class="text-end fw-semibold">${qty.toLocaleString('en-IN')} RQ</td>
                        <td class="text-end fw-bold" style="color:#0284c7">${weight.toFixed(2)} kg</td>
                        <td class="text-end">
                            <div><strong>${money(lineGross)}</strong> ${treatmentBadge}</div>
                            ${qty > 1 ? `<small class="text-muted d-block font-monospace">(${money(price)} / RQ)</small>` : ''}
                        </td>
                        <td><small class="badge bg-secondary-subtle text-secondary">${taxLabel}</small></td>
                        <td class="text-end">${money(tax.amount)}</td>
                        <td class="text-end fw-bold text-success">${money(totalValue)}</td>
                        <td class="text-center"><span class="badge bg-success-subtle text-success border border-success">Ready (${qty} RQ)</span></td>
                    </tr>`;
                }).join('');
            }
        }
        updateTotal();
    }

    function bindUnitChangeHandler(card, unitSelectSelector, customUnitSelector, qtyLabelSelector) {
        const unitSelect = card.querySelector(unitSelectSelector);
        const customUnitInput = card.querySelector(customUnitSelector);
        const qtyLabel = card.querySelector(qtyLabelSelector);
        if (!unitSelect || !qtyLabel) return;

        function refreshLabel() {
            const val = unitSelect.value;
            if (val === 'Other') {
                if (customUnitInput) {
                    customUnitInput.classList.remove('d-none');
                    const customText = customUnitInput.value.trim();
                    qtyLabel.textContent = `Qty (${customText || 'Other'}) *`;
                } else {
                    qtyLabel.textContent = 'Qty (Other) *';
                }
            } else {
                if (customUnitInput) customUnitInput.classList.add('d-none');
                qtyLabel.textContent = `Qty (${val}) *`;
            }
        }

        unitSelect.addEventListener('change', () => {
            refreshLabel();
            if (unitSelect.value === 'Other' && customUnitInput) {
                customUnitInput.focus();
            }
        });
        if (customUnitInput) {
            customUnitInput.addEventListener('input', refreshLabel);
        }
        refreshLabel();
    }

    function getSelectedUnitValue(card, unitSelectSelector, customUnitSelector) {
        const unitSelect = card.querySelector(unitSelectSelector);
        const customUnitInput = card.querySelector(customUnitSelector);
        if (!unitSelect) return 'Pieces';
        if (unitSelect.value === 'Other' && customUnitInput && customUnitInput.value.trim()) {
            return customUnitInput.value.trim();
        }
        return unitSelect.value || 'Pieces';
    }

    window.isCategoryActive = function(type) {
        return activePurchaseTypes.has(type);
    };

    function syncCategoryCardUI(type, isActive) {
        let cardId = '', chkId = '', statusBadgeId = '', countBadgeId = '', jumpId = '', sectionId = '';
        if (type === 'FABRIC_ROLL') {
            cardId = 'cardTypeFabricRoll'; chkId = 'chkTypeFabricRoll'; statusBadgeId = 'badgeFabricRollStatus';
            countBadgeId = 'badgeFabricRollCount'; jumpId = 'jumpFabricRoll'; sectionId = 'sectionFabricRoll';
        } else if (type === 'RAW_MATERIAL') {
            cardId = 'cardTypeRawMaterial'; chkId = 'chkTypeRawMaterial'; statusBadgeId = 'badgeRawMaterialStatus';
            countBadgeId = 'badgeRawMaterialCount'; jumpId = 'jumpRawMaterial'; sectionId = 'sectionRawMaterial';
        } else if (type === 'THIRD_PARTY') {
            cardId = 'cardTypeThirdParty'; chkId = 'chkTypeThirdParty'; statusBadgeId = 'badgeThirdPartyStatus';
            countBadgeId = 'badgeThirdPartyCount'; jumpId = 'jumpThirdParty'; sectionId = 'sectionThirdParty';
        }

        const card = document.getElementById(cardId);
        const chk = document.getElementById(chkId);
        const statusBadge = document.getElementById(statusBadgeId);
        const jump = document.getElementById(jumpId);
        const section = document.getElementById(sectionId);

        if (chk) chk.checked = isActive;
        if (card) {
            card.classList.toggle('active', isActive);
            card.classList.toggle('shadow-sm', isActive);
            if (type === 'FABRIC_ROLL') card.classList.toggle('border-primary', isActive);
            if (type === 'RAW_MATERIAL') card.classList.toggle('border-warning', isActive);
            if (type === 'THIRD_PARTY') card.classList.toggle('border-info', isActive);
        }
        if (statusBadge) {
            statusBadge.textContent = isActive ? 'Active' : 'Inactive';
            statusBadge.className = isActive
                ? (type === 'FABRIC_ROLL' ? 'badge bg-primary text-white' : (type === 'RAW_MATERIAL' ? 'badge bg-warning text-dark' : 'badge bg-info text-white'))
                : 'badge bg-light text-muted border';
        }
        if (jump) {
            jump.classList.toggle('d-none', !isActive);
        }

        if (section) {
            section.classList.toggle('d-none', !isActive);
            section.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = !isActive;
                if (!isActive) {
                    if (el.hasAttribute('required')) {
                        el.dataset.wasRequired = 'true';
                        el.removeAttribute('required');
                    }
                } else {
                    if (el.dataset.wasRequired === 'true') {
                        el.setAttribute('required', '');
                    }
                }
            });
        }

        const fabDesc = document.getElementById('fabricDescription');
        if (fabDesc && type === 'FABRIC_ROLL') {
            fabDesc.disabled = !isActive;
            if (isActive) {
                fabDesc.setAttribute('required', '');
            } else {
                fabDesc.removeAttribute('required');
            }
        }

        // Auto-seed first item if newly activated and empty
        if (isActive) {
            if (type === 'RAW_MATERIAL') {
                const rmCards = document.querySelectorAll('#rawMaterialContainer .rm-card');
                if (rmCards.length === 0) addRawMaterialItem();
            } else if (type === 'THIRD_PARTY') {
                const tpCards = document.querySelectorAll('#thirdPartyContainer .tp-card');
                if (tpCards.length === 0) addThirdPartyItem();
            } else if (type === 'FABRIC_ROLL') {
                const rollCards = document.querySelectorAll('#rollsContainer .roll-card');
                if (rollCards.length === 0) addRoll();
            }
        }
    }

    window.togglePurchaseCategory = function(type, forceState = null) {
        const isCurrent = activePurchaseTypes.has(type);
        const nextState = (forceState !== null) ? !!forceState : !isCurrent;

        // Prevent unselecting the only remaining active category
        if (!nextState && activePurchaseTypes.size <= 1 && isCurrent) {
            alert('At least one purchase category must remain selected for this invoice.');
            syncCategoryCardUI(type, true);
            return;
        }

        if (nextState) {
            activePurchaseTypes.add(type);
        } else {
            activePurchaseTypes.delete(type);
        }

        syncCategoryCardUI(type, nextState);
        updateTotal();
    };

    window.selectAllPurchaseCategories = function() {
        ['FABRIC_ROLL', 'RAW_MATERIAL', 'THIRD_PARTY'].forEach(t => {
            activePurchaseTypes.add(t);
            syncCategoryCardUI(t, true);
        });
        updateTotal();
    };

    // Backwards-compatible alias
    window.switchPurchaseType = function(type) {
        togglePurchaseCategory(type);
    };

    let rmItemIndex = 0;
    function addRawMaterialItem() {
        const container = document.getElementById('rawMaterialContainer');
        const template = document.getElementById('rawMaterialTemplate');
        if (!container || !template) return;

        const index = rmItemIndex++;
        const card = template.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        const title = 'MATERIAL #' + (container.children.length + 1);
        card.querySelector('.rm-title').textContent = title;

        bindUnitChangeHandler(card, '.rm-unit', '.rm-custom-unit', '.rm-qty-label');
        initHsnDropdown(card, '.rm-hsn');

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
        card.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !activePurchaseTypes.has('RAW_MATERIAL');
        });
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
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">Add raw materials to see their inward details.</td></tr>';
            if (tableFoot) tableFoot.innerHTML = '';
        } else {
            let rowsHtml = '';
            cards.forEach((card, index) => {
                const name = card.querySelector('.rm-name')?.value?.trim() || ('MATERIAL #' + (index + 1));
                const color = card.querySelector('.rm-color')?.value?.trim() || '—';
                const size = card.querySelector('.rm-size')?.value?.trim() || '—';
                const unit = getSelectedUnitValue(card, '.rm-unit', '.rm-custom-unit');
                const qty = Number(card.querySelector('.rm-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.rm-cost')?.value) || 0;

                const gross = qty * cost;
                const hsn = card.querySelector('.rm-hsn')?.value?.trim() || '—';
                const tax = calculateItemTax(card, gross);
                const base = tax.base;
                const lineTotal = tax.total;

                subtotal += base;
                totalTax += tax.tax;
                totalQty += qty;

                const baseSpan = card.querySelector('.rm-badge-base');
                if (baseSpan) baseSpan.textContent = money(base);
                const taxSpan = card.querySelector('.rm-badge-tax');
                if (taxSpan) taxSpan.textContent = money(tax.tax);
                const totalSpan = card.querySelector('.rm-badge-total');
                if (totalSpan) totalSpan.textContent = money(lineTotal);
                const lineTotalEl = card.querySelector('.rm-line-total');
                if (lineTotalEl) lineTotalEl.textContent = money(lineTotal);

                const taxLabel = tax.type === 'BOTH' ? 'SGST + CGST' : (tax.type === 'EXEMPTED' ? 'Exempt' : tax.type);
                const treatmentLabel = tax.treatment === 'INCLUDED' ? '<small class="text-muted d-block" style="font-size:10px;">(Incl.)</small>' : '<small class="text-muted d-block" style="font-size:10px;">(Excl.)</small>';

                rowsHtml += `<tr>
                    <td class="text-center fw-bold">${index + 1}</td>
                    <td><strong>${safe(name)}</strong></td>
                    <td><span class="badge bg-light text-dark border">${safe(color)}</span></td>
                    <td><span class="badge bg-light text-dark border font-monospace">${safe(size)}</span></td>
                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary border">${safe(unit)}</span></td>
                    <td class="text-end fw-semibold">${qty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3})}</td>
                    <td class="text-end">${money(cost)} ${treatmentLabel}</td>
                    <td class="text-end">${money(base)}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${safe(hsn)}</span></td>
                    <td><small class="badge bg-secondary-subtle text-secondary">${taxLabel}</small></td>
                    <td class="text-end">${money(tax.tax)}</td>
                    <td class="text-end fw-bold text-success">${money(lineTotal)}</td>
                </tr>`;
            });

            if (tableBody) tableBody.innerHTML = rowsHtml;
            if (tableFoot) {
                tableFoot.innerHTML = `<tr>
                    <td colspan="5" class="text-end">Totals:</td>
                    <td class="text-end">${totalQty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3})}</td>
                    <td class="text-end"></td>
                    <td class="text-end">${money(subtotal)}</td>
                    <td colspan="2"></td>
                    <td class="text-end">${money(totalTax)}</td>
                    <td class="text-end text-success">${money(subtotal + totalTax)}</td>
                </tr>`;
            }
        }

        const sItems = document.getElementById('summaryRmItems');
        if (sItems) sItems.textContent = cards.length + (cards.length === 1 ? ' Item' : ' Items');
        const sQty = document.getElementById('summaryRmQuantity');
        if (sQty) sQty.textContent = totalQty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3});
        const sBase = document.getElementById('summaryRmBase');
        if (sBase) sBase.textContent = money(subtotal);
        const sTax = document.getElementById('summaryRmTax');
        if (sTax) sTax.textContent = money(totalTax);
        const sTotal = document.getElementById('summaryRmTotal');
        if (sTotal) sTotal.textContent = money(subtotal + totalTax);

        updateTotal();
    }

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

    let tpItemIndex = 0;
    function addThirdPartyItem() {
        const container = document.getElementById('thirdPartyContainer');
        const template = document.getElementById('thirdPartyTemplate');
        if (!container || !template) return;

        const index = tpItemIndex++;
        const card = template.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        const title = 'PRODUCT #' + (container.children.length + 1);
        card.querySelector('.tp-title').textContent = title;

        bindUnitChangeHandler(card, '.tp-unit', '.tp-custom-unit', '.tp-qty-label');
        initHsnDropdown(card, '.tp-hsn');

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
        card.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !activePurchaseTypes.has('THIRD_PARTY');
        });
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
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="13" class="text-center text-muted py-4">Add third-party products to see their inward details.</td></tr>';
            if (tableFoot) tableFoot.innerHTML = '';
        } else {
            let rowsHtml = '';
            cards.forEach((card, index) => {
                const autoSku = generateThirdPartySku(card, index);
                const skuInput = card.querySelector('.tp-sku');
                if (skuInput) skuInput.value = autoSku;
                const skuText = card.querySelector('.tp-sku-text');
                if (skuText) skuText.textContent = autoSku;

                const name = card.querySelector('.tp-name')?.value?.trim() || ('PRODUCT #' + (index + 1));
                const color = card.querySelector('.tp-color')?.value?.trim() || '—';
                const size = card.querySelector('.tp-size')?.value?.trim() || '—';
                const unit = getSelectedUnitValue(card, '.tp-unit', '.tp-custom-unit');
                const qty = Number(card.querySelector('.tp-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.tp-cost')?.value) || 0;

                const gross = qty * cost;
                const hsn = card.querySelector('.tp-hsn')?.value?.trim() || '—';
                const tax = calculateItemTax(card, gross);
                const base = tax.base;
                const lineTotal = tax.total;

                subtotal += base;
                totalTax += tax.tax;
                totalQty += qty;

                const baseSpan = card.querySelector('.tp-badge-base');
                if (baseSpan) baseSpan.textContent = money(base);
                const taxSpan = card.querySelector('.tp-badge-tax');
                if (taxSpan) taxSpan.textContent = money(tax.tax);
                const totalSpan = card.querySelector('.tp-badge-total');
                if (totalSpan) totalSpan.textContent = money(lineTotal);
                const lineTotalEl = card.querySelector('.tp-line-total');
                if (lineTotalEl) lineTotalEl.textContent = money(lineTotal);

                const taxLabel = tax.type === 'BOTH' ? 'SGST + CGST' : (tax.type === 'EXEMPTED' ? 'Exempt' : tax.type);
                const treatmentLabel = tax.treatment === 'INCLUDED' ? '<small class="text-muted d-block" style="font-size:10px;">(Incl.)</small>' : '<small class="text-muted d-block" style="font-size:10px;">(Excl.)</small>';

                rowsHtml += `<tr>
                    <td class="text-center fw-bold">${index + 1}</td>
                    <td><strong>${safe(name)}</strong></td>
                    <td><span class="badge bg-light text-dark border font-monospace fw-semibold">${safe(autoSku)}</span></td>
                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary border">${safe(unit)}</span></td>
                    <td class="text-end fw-semibold">${qty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3})}</td>
                    <td class="text-end">${money(cost)} ${treatmentLabel}</td>
                    <td class="text-end">${money(base)}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${safe(hsn)}</span></td>
                    <td><small class="badge bg-secondary-subtle text-secondary">${taxLabel}</small></td>
                    <td class="text-end">${money(tax.tax)}</td>
                    <td class="text-end fw-bold text-success">${money(lineTotal)}</td>
                </tr>`;
            });

            if (tableBody) tableBody.innerHTML = rowsHtml;
            if (tableFoot) {
                tableFoot.innerHTML = `<tr>
                    <td colspan="6" class="text-end">Totals:</td>
                    <td class="text-end">${totalQty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3})}</td>
                    <td class="text-end"></td>
                    <td class="text-end">${money(subtotal)}</td>
                    <td colspan="2"></td>
                    <td class="text-end">${money(totalTax)}</td>
                    <td class="text-end text-success">${money(subtotal + totalTax)}</td>
                </tr>`;
            }
        }

        const sProd = document.getElementById('summaryTpProducts');
        if (sProd) sProd.textContent = cards.length + (cards.length === 1 ? ' Product' : ' Products');
        const sUnits = document.getElementById('summaryTpUnits');
        if (sUnits) sUnits.textContent = totalQty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 3});
        const sBase = document.getElementById('summaryTpBase');
        if (sBase) sBase.textContent = money(subtotal);
        const sTax = document.getElementById('summaryTpTax');
        if (sTax) sTax.textContent = money(totalTax);
        const sTotal = document.getElementById('summaryTpTotal');
        if (sTotal) sTotal.textContent = money(subtotal + totalTax);

        updateTotal();
    }

    const btnAddRawMaterial = document.getElementById('btnAddRawMaterialItem');
    if (btnAddRawMaterial) {
        btnAddRawMaterial.addEventListener('click', addRawMaterialItem);
    }

    const btnAddThirdParty = document.getElementById('btnAddThirdPartyItem');
    if (btnAddThirdParty) {
        btnAddThirdParty.addEventListener('click', addThirdPartyItem);
    }

    function updateTotal() {
        let grandBase = 0;
        let grandTax = 0;
        let grandTotal = 0;

        // 1. Fabric Rolls
        if (activePurchaseTypes.has('FABRIC_ROLL')) {
            const rollTotals = Array.from(rollsContainer.querySelectorAll('.roll-card')).reduce((totals, card) => {
                const price = Number(card.querySelector('.roll-price')?.value) || 0;
                const qty = Math.max(1, Number(card.querySelector('.roll-quantity')?.value) || 1);
                const lineGross = price * qty;
                const tax = calculateItemTax(card, lineGross);
                totals.base += tax.base;
                totals.tax += tax.tax;
                totals.total += tax.total;
                totals.weight += (Number(card.querySelector('.roll-weight')?.value) || 0);
                totals.count++;
                return totals;
            }, {base: 0, tax: 0, total: 0, weight: 0, count: 0});

            grandBase += rollTotals.base;
            grandTax += rollTotals.tax;
            grandTotal += rollTotals.total;

            const rowRoll = document.getElementById('rowSummaryRoll');
            if (rowRoll) rowRoll.classList.remove('d-none');
            const rollValEl = document.getElementById('summaryRollVal');
            if (rollValEl) rollValEl.textContent = money(rollTotals.total);
            const rollCntEl = document.getElementById('summaryRollItemsCount');
            if (rollCntEl) rollCntEl.textContent = rollTotals.count;
            const rollWtEl = document.getElementById('summaryRollWeight');
            if (rollWtEl) rollWtEl.textContent = rollTotals.weight.toFixed(2) + ' kg';
            const cardBadge = document.getElementById('badgeFabricRollCount');
            if (cardBadge) cardBadge.textContent = rollTotals.count + (rollTotals.count === 1 ? ' Roll' : ' Rolls');
        } else {
            const rowRoll = document.getElementById('rowSummaryRoll');
            if (rowRoll) rowRoll.classList.add('d-none');
            const cardBadge = document.getElementById('badgeFabricRollCount');
            if (cardBadge) cardBadge.textContent = '0 Rolls';
        }

        // 2. Raw Materials
        if (activePurchaseTypes.has('RAW_MATERIAL')) {
            const rmCards = Array.from(document.querySelectorAll('#rawMaterialContainer .rm-card'));
            const rmTotals = rmCards.reduce((totals, card) => {
                const qty = Number(card.querySelector('.rm-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.rm-cost')?.value) || 0;
                const gross = qty * cost;
                const tax = calculateItemTax(card, gross);
                totals.base += tax.base;
                totals.tax += tax.tax;
                totals.total += tax.total;
                totals.qty += qty;
                totals.count++;
                return totals;
            }, {base: 0, tax: 0, total: 0, qty: 0, count: 0});

            grandBase += rmTotals.base;
            grandTax += rmTotals.tax;
            grandTotal += rmTotals.total;

            const rowRm = document.getElementById('rowSummaryRm');
            if (rowRm) rowRm.classList.remove('d-none');
            const rmValEl = document.getElementById('summaryRmVal');
            if (rmValEl) rmValEl.textContent = money(rmTotals.total);
            const rmCntEl = document.getElementById('summaryRmItemsCount');
            if (rmCntEl) rmCntEl.textContent = rmTotals.count;
            const rmUnitEl = document.getElementById('summaryRmUnits');
            if (rmUnitEl) rmUnitEl.textContent = rmTotals.qty.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            const cardBadge = document.getElementById('badgeRawMaterialCount');
            if (cardBadge) cardBadge.textContent = rmTotals.count + (rmTotals.count === 1 ? ' Item' : ' Items');
        } else {
            const rowRm = document.getElementById('rowSummaryRm');
            if (rowRm) rowRm.classList.add('d-none');
            const cardBadge = document.getElementById('badgeRawMaterialCount');
            if (cardBadge) cardBadge.textContent = '0 Items';
        }

        // 3. Third-Party Products
        if (activePurchaseTypes.has('THIRD_PARTY')) {
            const tpCards = Array.from(document.querySelectorAll('#thirdPartyContainer .tp-card'));
            const tpTotals = tpCards.reduce((totals, card) => {
                const qty = Number(card.querySelector('.tp-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.tp-cost')?.value) || 0;
                const gross = qty * cost;
                const tax = calculateItemTax(card, gross);
                totals.base += tax.base;
                totals.tax += tax.tax;
                totals.total += tax.total;
                totals.units += qty;
                totals.count++;
                return totals;
            }, {base: 0, tax: 0, total: 0, units: 0, count: 0});

            grandBase += tpTotals.base;
            grandTax += tpTotals.tax;
            grandTotal += tpTotals.total;

            const rowTp = document.getElementById('rowSummaryTp');
            if (rowTp) rowTp.classList.remove('d-none');
            const tpValEl = document.getElementById('summaryTpVal');
            if (tpValEl) tpValEl.textContent = money(tpTotals.total);
            const tpCntEl = document.getElementById('summaryTpItemsCount');
            if (tpCntEl) tpCntEl.textContent = tpTotals.count;
            const tpUnitEl = document.getElementById('summaryTpUnitsCount');
            if (tpUnitEl) tpUnitEl.textContent = tpTotals.units.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            const cardBadge = document.getElementById('badgeThirdPartyCount');
            if (cardBadge) cardBadge.textContent = tpTotals.count + (tpTotals.count === 1 ? ' Product' : ' Products');
        } else {
            const rowTp = document.getElementById('rowSummaryTp');
            if (rowTp) rowTp.classList.add('d-none');
            const cardBadge = document.getElementById('badgeThirdPartyCount');
            if (cardBadge) cardBadge.textContent = '0 Products';
        }

        // Update overall summary fields
        const subEl = document.getElementById('purchaseSubtotal');
        if (subEl) subEl.textContent = money(grandBase);
        const taxEl = document.getElementById('purchaseTaxes');
        if (taxEl) taxEl.textContent = money(grandTax);
        const totEl = document.getElementById('purchaseTotal');
        if (totEl) totEl.textContent = money(grandTotal);

        const actCount = activePurchaseTypes.size;
        const countBadge = document.getElementById('activeCategoriesCountBadge');
        if (countBadge) countBadge.textContent = actCount + (actCount === 1 ? ' Category Selected' : ' Categories Combined');
        const pillBadge = document.getElementById('activeCategoryPill');
        if (pillBadge) pillBadge.textContent = actCount + (actCount === 1 ? ' Category Active' : ' Categories Combined');
    }

    function updateCodes() {
        let sequence = nextSequence;
        rollsContainer.querySelectorAll('.roll-card').forEach(card => {
            card.dataset.startSeq = sequence;
            const quantity = Math.max(1, Number(card.querySelector('.roll-quantity').value) || 1);
            const first = sequencePrefix + String(sequence).padStart(2, '0');
            const last = sequencePrefix + String(sequence + quantity - 1).padStart(2, '0');
            card.querySelector('.roll-code-text').textContent = first === last ? first : first + '–' + last;
            sequence += quantity;
        });
        updateSummary();
    }

    function addRoll() {
        const index = rollIndex++;
        const card = rollTemplate.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        const rollCode = 'ROLL #' + (rollsContainer.children.length + 1);
        card.querySelector('.roll-title').textContent = rollCode;
        card.querySelector('.roll-number').value = rollCode;
        const names = {
            '.roll-number': 'roll_number', '.roll-color': 'color', '.roll-design': 'design_name',
            '.roll-design-count': 'design_count', '.roll-quantity': 'quantity', '.roll-weight': 'weight_kg',
            '.roll-price': 'unit_price', '.roll-hsn': 'hsn_code', '.item-gst-type': 'gst_type', '.item-tax-treatment': 'tax_treatment', '.item-tax-mode': 'tax_mode',
            '.item-tax-value': 'tax_value', '.item-cgst-value': 'cgst_tax_value'
        };
        Object.entries(names).forEach(([selector, field]) => {
            const input = card.querySelector(selector);
            if (input && field) input.name = `items[${index}][${field}]`;
        });
        const type = document.createElement('input');
        type.type = 'hidden';
        type.name = `items[${index}][item_type]`;
        type.value = 'FABRIC_ROLL';
        card.appendChild(type);

        initHsnDropdown(card, '.roll-hsn');

        const rollHsnHidden = card.querySelector('.roll-hsn');
        if (rollHsnHidden) {
            rollHsnHidden.addEventListener('change', () => {
                applyHsnTaxRules(card, rollHsnHidden.value);
                updateCodes();
            });
        }

        card.querySelector('.remove-roll').addEventListener('click', () => {
            card.remove();
            Array.from(rollsContainer.children).forEach((item, i) => item.querySelector('.roll-title').textContent = 'ROLL #' + (i + 1));
            updateCodes();
        });
        card.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', updateCodes);
            input.addEventListener('change', () => {
                if (input.classList.contains('item-gst-type')) {
                    const hsn = card.querySelector('.roll-hsn')?.value || '';
                    applyHsnTaxRules(card, hsn, input.value);
                } else {
                    updateTaxInputs(card);
                }
                updateCodes();
            });
            input.addEventListener('keyup', updateCodes);
        });
        updateTaxInputs(card);
        card.querySelector('.roll-photo').addEventListener('change', event => {
            const file = event.target.files[0];
            const preview = card.querySelector('.roll-preview');
            if (!file) {
                event.target.removeAttribute('name');
                preview.classList.add('d-none');
                updateSummary();
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Roll photo must not exceed 5 MB.');
                event.target.value = '';
                event.target.removeAttribute('name');
                preview.classList.add('d-none');
                updateSummary();
                return;
            }
            event.target.name = `roll_photo_${index}`;
            const reader = new FileReader();
            reader.onload = () => { card.querySelector('.roll-preview img').src = reader.result; preview.classList.remove('d-none'); updateSummary(); };
            reader.readAsDataURL(file);
        });
        card.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !activePurchaseTypes.has('FABRIC_ROLL');
        });
        rollsContainer.appendChild(card);
        updateCodes();
    }

    function previewLabels() {
        const material = document.getElementById('fabricDescription').value.trim().toUpperCase();
        if (!material) { document.getElementById('fabricDescription').focus(); return; }
        const rolls = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        if (!rolls.length) { alert('Add a fabric roll before previewing labels.'); return; }
        const labelCards = document.getElementById('purchaseLabelCards');
        labelCards.innerHTML = '';
        let labelCount = 0;
        const supplierName = supplier.options[supplier.selectedIndex]?.text?.trim() || 'SUPPLIER';

        rolls.forEach((card, index) => {
            const qty = Number(card.querySelector('.roll-quantity').value) || 1;
            const start = Number(card.dataset.startSeq) || nextSequence;
            const roll = card.querySelector('.roll-number').value || 'ROLL #' + (index + 1);
            const weight = Number(card.querySelector('.roll-weight').value) || 0;
            const pieceWeight = qty > 0 ? (weight / qty).toFixed(2) : weight.toFixed(2);
            const color = (card.querySelector('.roll-color')?.value?.trim() || 'STANDARD').toUpperCase();
            const design = (card.querySelector('.roll-design')?.value?.trim() || '').toUpperCase();

            for (let piece = 0; piece < qty; piece++) {
                const code = sequencePrefix + String(start + piece).padStart(2, '0');
                const pieceLabel = qty > 1 ? `${roll} (${piece + 1}/${qty})` : roll;

                const sticker = document.createElement('div');
                sticker.className = 'roll-label-card';
                sticker.innerHTML = `
                    <div class="label-header">
                        <div class="company-tag" title="${safe(supplierName)}">${safe(supplierName)}</div>
                        <div class="po-tag">PO PREVIEW</div>
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
                        <span>GRM MFG</span>
                    </div>
                `;
                labelCards.appendChild(sticker);
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
        document.getElementById('purchaseLabelCount').textContent = labelCount + (labelCount === 1 ? ' label' : ' labels');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('purchaseLabelsModal')).show();
    }

    document.getElementById('addFabricRoll').addEventListener('click', addRoll);
    document.getElementById('previewLabels')?.addEventListener('click', previewLabels);
    supplierSelect.addEventListener('change', updateSupplierDetails);
    updateSupplierDetails();

    // Searchable Supplier Dropdown Controller
    const supplierDropdownBtn = document.getElementById('supplierDropdownBtn');
    const supplierDropdownSelectedText = document.getElementById('supplierDropdownSelectedText');
    const supplierSearchInput = document.getElementById('supplierSearchInput');
    const supplierOptItems = document.querySelectorAll('.supplier-opt-item');
    const supplierNoResults = document.getElementById('supplierNoResults');
    const supplierDropdown = document.getElementById('supplierSearchDropdown');

    if (supplierDropdown && supplierSearchInput) {
        supplierDropdown.addEventListener('shown.bs.dropdown', function () {
            supplierSearchInput.focus();
            supplierSearchInput.select();
        });

        supplierSearchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            supplierOptItems.forEach(item => {
                const searchData = item.dataset.search || '';
                const isDefault = item.dataset.id === '';
                if (isDefault) {
                    item.style.display = query === '' ? '' : 'none';
                    return;
                }
                if (query === '' || searchData.includes(query)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (supplierNoResults) {
                supplierNoResults.classList.toggle('d-none', visibleCount > 0 || query === '');
            }
        });

        supplierOptItems.forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id || '';
                const displayText = this.dataset.display || '-- Select registered supplier --';

                supplierOptItems.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');

                if (supplierDropdownSelectedText) {
                    supplierDropdownSelectedText.textContent = displayText;
                }

                supplierSelect.value = id;
                supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));

                if (supplierDropdownBtn) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(supplierDropdownBtn) || new bootstrap.Dropdown(supplierDropdownBtn);
                    bsDropdown.hide();
                }
            });
        });
    }
    fetch('<?= url("api/sellers/next-roll-codes") ?>')
        .then(response => { if (!response.ok) throw new Error('Unable to fetch roll code sequence.'); return response.json(); })
        .then(response => {
            if (!response.success || !response.data || !response.data.prefix || !Number.isInteger(Number(response.data.start_seq))) throw new Error('Invalid roll code sequence response.');
            sequencePrefix = response.data.prefix;
            nextSequence = Number(response.data.start_seq);
            updateCodes();
        })
        .catch(error => { console.error(error); alert('Could not load the next fabric roll code. Refresh the page before recording the purchase.'); });

    function resetPurchaseSubmitState() {
        form.removeAttribute('data-submitting');
        const submitBtn = document.getElementById('savePurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.pointerEvents = '';
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Purchase';
        }
    }

    function lockPurchaseSubmitState() {
        form.dataset.submitting = 'true';
        const submitBtn = document.getElementById('savePurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'none';
            setTimeout(() => {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
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

        if (!supplierSelect || !supplierSelect.value) {
            event.preventDefault();
            alert('Please select a registered supplier before saving.');
            document.getElementById('supplierDropdownBtn')?.focus();
            resetPurchaseSubmitState();
            return;
        }

        const purchaseDateInput = document.getElementById('purchaseDate');
        if (!purchaseDateInput || !purchaseDateInput.value) {
            event.preventDefault();
            alert('Please choose a valid purchase date.');
            purchaseDateInput?.focus();
            resetPurchaseSubmitState();
            return;
        }

        if (useMappingInvoice?.checked) {
            const invoiceNumber = document.getElementById('invoiceNumber')?.value?.trim() || '';
            if (!invoiceNumber) {
                event.preventDefault();
                alert('Enter the invoice number saved in Purchase Item Mapping.');
                document.getElementById('invoiceNumber')?.focus();
                resetPurchaseSubmitState();
                return;
            }
            let purchaseTypeHidden = document.getElementById('purchaseTypeHiddenInput');
            if (!purchaseTypeHidden) {
                purchaseTypeHidden = document.createElement('input');
                purchaseTypeHidden.type = 'hidden';
                purchaseTypeHidden.name = 'purchase_type';
                purchaseTypeHidden.id = 'purchaseTypeHiddenInput';
                form.appendChild(purchaseTypeHidden);
            }
            purchaseTypeHidden.value = 'THIRD_PARTY';
            let payloadInput = document.getElementById('itemsPayloadInput');
            if (!payloadInput) {
                payloadInput = document.createElement('input');
                payloadInput.type = 'hidden';
                payloadInput.name = 'items_payload';
                payloadInput.id = 'itemsPayloadInput';
                form.appendChild(payloadInput);
            }
            payloadInput.value = '[]';
            lockPurchaseSubmitState();
            return;
        }

        if (activePurchaseTypes.size === 0) {
            event.preventDefault();
            alert('Please select at least one purchase category (Fabric Rolls, Raw Materials, or Third-Party Products).');
            resetPurchaseSubmitState();
            return;
        }

        const itemsPayload = [];
        let hasError = false;
        let errorMessage = '';

        // 1. Validate & collect Fabric Rolls (if active)
        if (activePurchaseTypes.has('FABRIC_ROLL')) {
            const rollCards = Array.from(rollsContainer.querySelectorAll('.roll-card'));
            if (rollCards.length === 0) {
                event.preventDefault();
                alert('Add at least one fabric roll or uncheck the Fabric Rolls category.');
                resetPurchaseSubmitState();
                return;
            }
            const fabDesc = document.getElementById('fabricDescription')?.value?.trim();
            if (!fabDesc) {
                event.preventDefault();
                alert('Please enter a fabric / material description for Fabric Rolls.');
                document.getElementById('fabricDescription')?.focus();
                resetPurchaseSubmitState();
                return;
            }

            rollCards.forEach((card, idx) => {
                if (hasError) return;
                const rollNum = card.querySelector('.roll-number')?.value?.trim() || ('ROLL #' + (idx + 1));
                const color = card.querySelector('.roll-color')?.value?.trim();
                const design = card.querySelector('.roll-design')?.value?.trim();
                const weight = Number(card.querySelector('.roll-weight')?.value) || 0;
                const price = Number(card.querySelector('.roll-price')?.value) || 0;

                if (!color) {
                    hasError = true;
                    errorMessage = `Roll #${idx + 1}: Color / Shade is required.`;
                    card.querySelector('.roll-color')?.focus();
                    return;
                }
                if (!design) {
                    hasError = true;
                    errorMessage = `Roll #${idx + 1}: Design Name / Pattern is required.`;
                    card.querySelector('.roll-design')?.focus();
                    return;
                }
                if (weight <= 0) {
                    hasError = true;
                    errorMessage = `Roll #${idx + 1}: Weight (kg) must be greater than zero.`;
                    card.querySelector('.roll-weight')?.focus();
                    return;
                }
                if (price <= 0) {
                    hasError = true;
                    errorMessage = `Roll #${idx + 1}: Purchase price must be greater than zero.`;
                    card.querySelector('.roll-price')?.focus();
                    return;
                }

                const photoInput = card.querySelector('.roll-photo');
                let formIdx = String(idx);
                if (photoInput && photoInput.files && photoInput.files.length > 0) {
                    photoInput.name = `roll_photo_${idx}`;
                } else if (photoInput) {
                    photoInput.removeAttribute('name');
                }

                itemsPayload.push({
                    item_type: 'FABRIC_ROLL',
                    roll_number: rollNum,
                    color: color,
                    design_name: design,
                    design_count: Number(card.querySelector('.roll-design-count')?.value) || 1,
                    quantity: Number(card.querySelector('.roll-quantity')?.value) || 1,
                    weight_kg: weight,
                    unit_price: price,
                    hsn_code: card.querySelector('.roll-hsn')?.value?.trim() || '',
                    tax_treatment: card.querySelector('.item-tax-treatment')?.value || 'INCLUDED',
                    gst_type: card.querySelector('.item-gst-type')?.value || 'EXEMPTED',
                    tax_mode: card.querySelector('.item-tax-mode')?.value || 'PERCENT',
                    tax_value: card.querySelector('.item-tax-value')?.value || '0',
                    cgst_tax_value: card.querySelector('.item-cgst-value')?.value || '0',
                    form_index: formIdx
                });
            });
        }

        if (hasError) {
            event.preventDefault();
            alert(errorMessage);
            resetPurchaseSubmitState();
            return;
        }

        // 2. Validate & collect Raw Materials (if active)
        if (activePurchaseTypes.has('RAW_MATERIAL')) {
            const rmCards = Array.from(document.querySelectorAll('#rawMaterialContainer .rm-card'));
            if (rmCards.length === 0) {
                event.preventDefault();
                alert('Add at least one raw material or uncheck the Raw Materials category.');
                resetPurchaseSubmitState();
                return;
            }

            rmCards.forEach((card, idx) => {
                if (hasError) return;
                const name = card.querySelector('.rm-name')?.value?.trim();
                const qty = Number(card.querySelector('.rm-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.rm-cost')?.value) || 0;
                const unit = getSelectedUnitValue(card, '.rm-unit', '.rm-custom-unit');
                const hsn = card.querySelector('.rm-hsn')?.value?.trim() || '';
                const hsnBtn = card.querySelector('.hsn-dropdown-btn');

                if (!name) {
                    hasError = true;
                    errorMessage = `Raw Material #${idx + 1}: Material / Item name is required.`;
                    card.querySelector('.rm-name')?.focus();
                    return;
                }
                if (qty <= 0) {
                    hasError = true;
                    errorMessage = `Raw Material #${idx + 1} ("${name}"): Quantity must be at least 0.01.`;
                    card.querySelector('.rm-quantity')?.focus();
                    return;
                }
                if (cost <= 0) {
                    hasError = true;
                    errorMessage = `Raw Material #${idx + 1} ("${name}"): Purchase price must be greater than zero.`;
                    card.querySelector('.rm-cost')?.focus();
                    return;
                }
                if (!hsn) {
                    hasError = true;
                    errorMessage = `Raw Material #${idx + 1} ("${name}"): HSN code is mandatory. Please select or add an HSN code.`;
                    if (hsnBtn) {
                        hsnBtn.classList.add('border-danger', 'is-invalid');
                        hsnBtn.focus();
                    }
                    return;
                } else if (hsnBtn) {
                    hsnBtn.classList.remove('border-danger', 'is-invalid');
                }

                itemsPayload.push({
                    item_type: 'RAW_MATERIAL',
                    item_name: name,
                    color: card.querySelector('.rm-color')?.value?.trim() || '',
                    size: card.querySelector('.rm-size')?.value?.trim() || '',
                    unit: unit,
                    quantity: qty,
                    unit_price: cost,
                    mrp: 0,
                    selling_price: 0,
                    hsn_code: hsn,
                    tax_treatment: card.querySelector('.item-tax-treatment')?.value || 'INCLUDED',
                    gst_type: card.querySelector('.item-gst-type')?.value || 'EXEMPTED',
                    tax_mode: card.querySelector('.item-tax-mode')?.value || 'PERCENT',
                    tax_value: Number(card.querySelector('.item-tax-value')?.value) || 0,
                    cgst_tax_value: Number(card.querySelector('.item-cgst-value')?.value) || 0,
                    form_index: String(itemsPayload.length)
                });
            });
        }

        if (hasError) {
            event.preventDefault();
            alert(errorMessage);
            resetPurchaseSubmitState();
            return;
        }

        // 3. Validate & collect Third-Party Products (if active)
        if (activePurchaseTypes.has('THIRD_PARTY')) {
            const tpCards = Array.from(document.querySelectorAll('#thirdPartyContainer .tp-card'));
            if (tpCards.length === 0) {
                event.preventDefault();
                alert('Add at least one third-party product or uncheck the Third-Party Products category.');
                resetPurchaseSubmitState();
                return;
            }

            tpCards.forEach((card, idx) => {
                if (hasError) return;
                const name = card.querySelector('.tp-name')?.value?.trim();
                const qty = Number(card.querySelector('.tp-quantity')?.value) || 0;
                const cost = Number(card.querySelector('.tp-cost')?.value) || 0;
                const unit = getSelectedUnitValue(card, '.tp-unit', '.tp-custom-unit');
                const hsn = card.querySelector('.tp-hsn')?.value?.trim() || '';
                const hsnBtn = card.querySelector('.hsn-dropdown-btn');

                if (!name) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1}: Item name is required.`;
                    card.querySelector('.tp-name')?.focus();
                    return;
                }
                if (qty <= 0) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} ("${name}"): Quantity must be at least 1.`;
                    card.querySelector('.tp-quantity')?.focus();
                    return;
                }
                if (cost <= 0) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} ("${name}"): Purchase price must be greater than zero.`;
                    card.querySelector('.tp-cost')?.focus();
                    return;
                }
                if (!hsn) {
                    hasError = true;
                    errorMessage = `Product #${idx + 1} ("${name}"): HSN code is mandatory. Please select or add an HSN code.`;
                    if (hsnBtn) {
                        hsnBtn.classList.add('border-danger', 'is-invalid');
                        hsnBtn.focus();
                    }
                    return;
                } else if (hsnBtn) {
                    hsnBtn.classList.remove('border-danger', 'is-invalid');
                }

                itemsPayload.push({
                    item_type: 'THIRD_PARTY',
                    item_name: name,
                    vendor_sku: card.querySelector('.tp-sku')?.value?.trim() || '',
                    color: card.querySelector('.tp-color')?.value?.trim() || '',
                    size: card.querySelector('.tp-size')?.value?.trim() || '',
                    unit: unit,
                    quantity: qty,
                    unit_price: cost,
                    mrp: 0,
                    selling_price: 0,
                    hsn_code: hsn,
                    tax_treatment: card.querySelector('.item-tax-treatment')?.value || 'INCLUDED',
                    gst_type: card.querySelector('.item-gst-type')?.value || 'EXEMPTED',
                    tax_mode: card.querySelector('.item-tax-mode')?.value || 'PERCENT',
                    tax_value: Number(card.querySelector('.item-tax-value')?.value) || 0,
                    cgst_tax_value: Number(card.querySelector('.item-cgst-value')?.value) || 0,
                    form_index: String(itemsPayload.length)
                });
            });
        }

        if (hasError) {
            event.preventDefault();
            alert(errorMessage);
            resetPurchaseSubmitState();
            return;
        }

        if (itemsPayload.length === 0) {
            event.preventDefault();
            alert('Add at least one purchase item before saving.');
            resetPurchaseSubmitState();
            return;
        }

        // Set overall purchase_type hidden input (MIXED if multiple categories present)
        let purchaseTypeHidden = document.getElementById('purchaseTypeHiddenInput');
        if (!purchaseTypeHidden) {
            purchaseTypeHidden = document.createElement('input');
            purchaseTypeHidden.type = 'hidden';
            purchaseTypeHidden.name = 'purchase_type';
            purchaseTypeHidden.id = 'purchaseTypeHiddenInput';
            form.appendChild(purchaseTypeHidden);
        }
        const distinctTypes = new Set(itemsPayload.map(i => i.item_type));
        if (distinctTypes.size > 1) {
            purchaseTypeHidden.value = 'MIXED';
        } else {
            purchaseTypeHidden.value = Array.from(distinctTypes)[0] || 'FABRIC_ROLL';
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
        lockPurchaseSubmitState();
    });

    // Multiple Invoice Documents Management with AVIF Conversion indicator
    let selectedDocFiles = [];
    const docInput = document.getElementById('purchaseDocuments');
    const multiPreviewContainer = document.getElementById('multiDocumentPreviewContainer');
    const docPreviewCardsRow = document.getElementById('docPreviewCardsRow');
    const selectedDocCountLabel = document.getElementById('selectedDocCountLabel');
    const btnClearAllDocs = document.getElementById('btnClearAllDocs');

    function syncDocInputFiles() {
        if (!docInput) return;
        try {
            const dt = new DataTransfer();
            selectedDocFiles.forEach(file => dt.items.add(file));
            docInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer sync not supported:', e);
        }
    }

    function renderDocumentPreviews() {
        if (!docPreviewCardsRow || !multiPreviewContainer) return;
        docPreviewCardsRow.innerHTML = '';

        if (selectedDocFiles.length === 0) {
            multiPreviewContainer.classList.add('d-none');
            if (btnClearAllDocs) btnClearAllDocs.classList.add('d-none');
            return;
        }

        multiPreviewContainer.classList.remove('d-none');
        if (btnClearAllDocs) btnClearAllDocs.classList.remove('d-none');
        if (selectedDocCountLabel) {
            selectedDocCountLabel.innerHTML = `<i class="bi bi-paperclip me-1 text-primary"></i>${selectedDocFiles.length} invoice${selectedDocFiles.length > 1 ? 's' : ''} selected`;
        }

        selectedDocFiles.forEach((file, index) => {
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
            thumbBox.style.height = '75px';
            thumbBox.style.overflow = 'hidden';
            thumbBox.style.position = 'relative';

            if (isImg) {
                const img = document.createElement('img');
                img.className = 'img-fluid';
                img.style.maxHeight = '75px';
                img.style.objectFit = 'contain';
                const blobUrl = URL.createObjectURL(file);
                img.src = blobUrl;
                thumbBox.appendChild(img);
            } else if (isPdf) {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-pdf text-danger fs-2"></i>';
            } else {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-text text-secondary fs-2"></i>';
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
            btnView.style.fontSize = '11px';
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
                removeSelectedDocFile(index);
            };

            actionsBox.appendChild(btnView);
            actionsBox.appendChild(btnDel);

            cardBody.appendChild(thumbBox);
            cardBody.appendChild(infoBox);
            cardBody.appendChild(actionsBox);
            card.appendChild(cardBody);
            col.appendChild(card);
            docPreviewCardsRow.appendChild(col);
        });
    }

    if (docInput) {
        docInput.addEventListener('change', function() {
            if (docInput.files && docInput.files.length > 0) {
                Array.from(docInput.files).forEach(f => {
                    // Avoid duplicate additions with same name, size, lastModified
                    const exists = selectedDocFiles.some(existing => 
                        existing.name === f.name && existing.size === f.size && existing.lastModified === f.lastModified
                    );
                    if (!exists) {
                        selectedDocFiles.push(f);
                    }
                });
                syncDocInputFiles();
                renderDocumentPreviews();
            }
        });
    }

    window.removeSelectedDocFile = function(index) {
        if (index >= 0 && index < selectedDocFiles.length) {
            selectedDocFiles.splice(index, 1);
            syncDocInputFiles();
            renderDocumentPreviews();
        }
    };

    window.clearAllSelectedDocumentFiles = function() {
        selectedDocFiles = [];
        if (docInput) docInput.value = '';
        renderDocumentPreviews();
    };

    const urlParams = new URLSearchParams(window.location.search);
    const requestedType = (urlParams.get('type') || 'FABRIC_ROLL').toUpperCase();
    if (requestedType === 'ALL' || requestedType === 'MIXED') {
        selectAllPurchaseCategories();
    } else if (requestedType === 'RAW_MATERIAL') {
        activePurchaseTypes.clear();
        activePurchaseTypes.add('RAW_MATERIAL');
        syncCategoryCardUI('RAW_MATERIAL', true);
        syncCategoryCardUI('FABRIC_ROLL', false);
        syncCategoryCardUI('THIRD_PARTY', false);
    } else if (requestedType === 'THIRD_PARTY') {
        activePurchaseTypes.clear();
        activePurchaseTypes.add('THIRD_PARTY');
        syncCategoryCardUI('THIRD_PARTY', true);
        syncCategoryCardUI('FABRIC_ROLL', false);
        syncCategoryCardUI('RAW_MATERIAL', false);
    } else {
        addRoll();
        activePurchaseTypes.clear();
        activePurchaseTypes.add('FABRIC_ROLL');
        syncCategoryCardUI('FABRIC_ROLL', true);
        syncCategoryCardUI('RAW_MATERIAL', false);
        syncCategoryCardUI('THIRD_PARTY', false);
    }
    updateTotal();
})();
</script>
