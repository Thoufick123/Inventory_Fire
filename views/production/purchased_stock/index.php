<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Purchased Stock Inventory</h2>
    </div>
</div>

<!-- CSRF Token Meta for AJAX -->
<input type="hidden" id="pageCsrfToken" name="_csrf_token" value="<?= CSRF::token() ?>">

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Stock Batches</div>
                <div class="fs-3 fw-bold text-dark" id="kpi-total-batches"><?= number_format((int)($metrics['total_batches'] ?? 0)) ?></div>
                <div class="small text-muted"><span id="kpi-total-units"><?= number_format((float)($metrics['total_units'] ?? 0)) ?></span> QR units in stock</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Fabric Rolls in Stock</div>
                <div class="fs-3 fw-bold text-primary" id="kpi-fabric-count"><?= number_format((int)($metrics['fabric_count'] ?? 0)) ?></div>
                <div class="small text-muted">Stocked fabric rolls</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Raw Materials in Stock</div>
                <div class="fs-3 fw-bold text-warning" id="kpi-raw-count"><?= number_format((int)($metrics['raw_count'] ?? 0)) ?></div>
                <div class="small text-muted">Stocked raw materials</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Third-Party Products</div>
                <div class="fs-3 fw-bold text-info" id="kpi-third-party-count"><?= number_format((int)($metrics['third_party_count'] ?? 0)) ?></div>
                <div class="small text-muted">Stocked third-party lines</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/purchased-stock') ?>" class="row g-2 align-items-center">
            <div class="col-auto">
                <div class="btn-group" role="group">
                    <a href="<?= url('production/purchased-stock?type=ALL' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'ALL') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                        All Purchased Stock
                    </a>
                    <a href="<?= url('production/purchased-stock?type=FABRIC_ROLL' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'FABRIC_ROLL') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-layers-half me-1"></i>Fabric Rolls (<span id="tab-fabric-count"><?= (int)($metrics['fabric_count'] ?? 0) ?></span>)
                    </a>
                    <a href="<?= url('production/purchased-stock?type=RAW_MATERIAL' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'RAW_MATERIAL') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-boxes me-1"></i>Raw Materials (<span id="tab-raw-count"><?= (int)($metrics['raw_count'] ?? 0) ?></span>)
                    </a>
                    <a href="<?= url('production/purchased-stock?type=THIRD_PARTY' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'THIRD_PARTY') ? 'btn-info text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-box-seam me-1"></i>Third-Party (<span id="tab-third-party-count"><?= (int)($metrics['third_party_count'] ?? 0) ?></span>)
                    </a>
                </div>
            </div>
            <div class="col-md-4 ms-auto">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search item, QR batch, bill #, vendor, roll..." value="<?= e($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    <?php if ($search || $typeFilter !== 'ALL'): ?>
                        <a href="<?= url('production/purchased-stock') ?>" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Stock Roster Table -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Stocked Inventory Lines</h5>
        <span class="badge bg-secondary-subtle text-secondary border" id="table-batch-count"><?= count($stockItems) ?> Batches in Warehouse Stock</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="stockTable" style="font-size: 13.5px;">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="min-width: 200px;">Item / Material Name</th>
                    <th style="min-width: 130px;">Stock Type</th>
                    <th style="min-width: 170px;">Purchase Details</th>
                    <th style="min-width: 140px;">QR Batch Code</th>
                    <th class="text-end" style="min-width: 110px;">Stocked Qty</th>
                    <th style="min-width: 160px;">Taxonomy / Category</th>
                    <th style="min-width: 140px;">Stocked Date</th>
                    <th class="text-center" style="min-width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <?php if (empty($stockItems)): ?>
                    <tr id="emptyStockRow">
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No purchased items have been added to stock yet.
                            <div class="mt-2 text-muted small">
                                When you generate QR batches and click <strong>"Add to Purchased Stock"</strong>, they will be listed here.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = 1; foreach ($stockItems as $item): ?>
                        <tr id="stock-row-<?= (int)$item['generation_id'] ?>" 
                            data-generation-id="<?= (int)$item['generation_id'] ?>" 
                            data-batch-code="<?= e($item['batch_code']) ?>"
                            data-item-type="<?= e($item['item_type']) ?>" 
                            data-units="<?= (float)$item['qr_quantity'] ?>">
                            <td class="text-muted row-index"><?= $idx++ ?></td>
                            <td id="cell-item-<?= (int)$item['generation_id'] ?>">
                                <div class="fw-bold text-dark item-name"><?= e($item['item_name']) ?></div>
                                <div class="small text-muted item-meta">
                                    <?php if (!empty($item['roll_number'])): ?>
                                        <span class="badge bg-light text-dark border me-1 roll-badge"><?= e($item['roll_number']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['color_name'])): ?>
                                        <span class="me-1 color-info">Color: <?= e($item['color_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['size_name'])): ?>
                                        <span class="size-info">Size: <?= e($item['size_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['vendor_sku'])): ?>
                                        <span class="font-monospace ms-1">[<?= e($item['vendor_sku']) ?>]</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($item['item_type'] === 'FABRIC_ROLL'): ?>
                                    <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-layers-half me-1"></i>Fabric Roll</span>
                                <?php elseif ($item['item_type'] === 'RAW_MATERIAL'): ?>
                                    <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-boxes me-1"></i>Raw Material</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-dark border"><i class="bi bi-box-seam me-1"></i>Third-Party</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary font-monospace"><?= e($item['purchase_number']) ?></div>
                                <div class="small text-muted"><?= e($item['seller_company'] ?: $item['seller_name']) ?></div>
                                <?php if (!empty($item['purchase_date'])): ?>
                                    <div class="small text-muted"><?= e(date('d M Y', strtotime($item['purchase_date']))) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($item['batch_code']) ?></span>
                                <div class="small text-muted mt-1"><?= (int)$item['qr_quantity'] ?> QR Labels</div>
                            </td>
                            <td class="text-end" id="cell-qty-<?= (int)$item['generation_id'] ?>">
                                <div class="fw-bold text-dark"><?= number_format((float)$item['purchase_qty'], 2) ?> <?= e($item['unit'] ?: 'PCS') ?></div>
                                <div class="small text-muted weight-display">
                                    <?php if ((float)($item['weight_kg'] ?? 0) > 0): ?>
                                        <?= number_format((float)$item['weight_kg'], 2) ?> kg
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($item['category_name'])): ?>
                                    <span class="badge bg-light text-dark border"><?= e($item['category_name']) ?></span>
                                    <?php if (!empty($item['subcategory_name'])): ?>
                                        <div class="small text-muted mt-1">&rarr; <?= e($item['subcategory_name']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= !empty($item['stock_added_at']) ? date('d M Y H:i', strtotime($item['stock_added_at'])) : '—' ?></div>
                                <?php if (!empty($item['stock_added_by_name'])): ?>
                                    <div class="small text-muted">by <?= e($item['stock_added_by_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm shadow-xs" role="group">
                                    <button type="button" class="btn btn-outline-primary" title="View Product Details & QR Codes" onclick="openViewModal(<?= (int)$item['generation_id'] ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning text-dark" title="Edit QR Details" onclick="openEditModal(<?= (int)$item['generation_id'] ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Stock Line" onclick="confirmDeleteRow(<?= (int)$item['generation_id'] ?>, '<?= e($item['batch_code']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. DYNAMIC VIEW MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="viewStockModal" tabindex="-1" aria-labelledby="viewStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="viewStockModalLabel">
                        <i class="bi bi-qr-code text-primary me-2"></i>Purchased Stock Details: <span id="vModalBatchCode" class="font-monospace text-primary"></span>
                    </h5>
                    <span id="vModalTypeBadge" class="badge bg-primary-subtle text-primary border"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 position-relative">
                <!-- Loading Spinner -->
                <div id="viewModalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Loading complete stock &amp; QR details...</div>
                </div>

                <!-- Content Container -->
                <div id="viewModalContent" style="display: none;">
                    <!-- Product & Purchase Baseline Card -->
                    <div class="card border shadow-sm mb-4">
                        <div class="card-header bg-white py-2 px-3 fw-bold small text-uppercase text-secondary">
                            <i class="bi bi-info-circle me-1 text-primary"></i> Product &amp; Procurement Summary
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">ITEM / MATERIAL NAME</div>
                                    <div class="fw-bold fs-6 text-dark mt-1" id="vItemName">—</div>
                                    <div class="small text-muted mt-1" id="vItemAttributes">—</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">PURCHASE ORDER &amp; VENDOR</div>
                                    <div class="mt-1">
                                        <a href="#" id="vPurchaseLink" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none">—</a>
                                    </div>
                                    <div class="small text-dark fw-semibold" id="vVendorName">—</div>
                                    <div class="small text-muted" id="vPurchaseDate">—</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">QUANTITY &amp; VALUATION</div>
                                    <div class="fs-6 fw-bold text-dark mt-1" id="vStockQty">—</div>
                                    <div class="small text-muted">Rate: <span id="vUnitRate" class="fw-semibold text-dark">—</span> · Total: <span id="vTotalPrice" class="fw-semibold text-success">—</span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">CATEGORY HIERARCHY</div>
                                    <div class="small fw-semibold text-dark mt-1" id="vCategoryTree">—</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">STOCK RECEIPT &amp; AUDIT</div>
                                    <div class="small fw-semibold text-dark mt-1" id="vStockAddedAt">—</div>
                                    <div class="small text-muted">Received by: <span id="vStockAddedBy" class="text-dark">—</span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small fw-semibold">BRAND &amp; NOTES</div>
                                    <div class="small fw-bold text-dark text-uppercase mt-1" id="vBrandName">—</div>
                                    <div class="small text-muted fst-italic" id="vNotes">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Codes & Labels Grid Card -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Generated QR Items (<span id="vQrItemsCount">0</span>)</h6>
                                <span class="small text-muted">All individual QR barcodes generated for this stock line</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="text" id="vQrSearchInput" class="form-control form-control-sm" placeholder="Filter QR codes..." style="max-width: 200px;" oninput="filterViewQrCards(this.value)">
                                <a href="#" id="vModalPrintBtnTop" target="_blank" class="btn btn-sm btn-outline-dark fw-semibold">
                                    <i class="bi bi-printer me-1"></i> Print Labels
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="vQrCardsContainer">
                                <!-- QR Cards will be inserted dynamically here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                <div>
                    <a href="#" id="vModalPrintBtn" target="_blank" class="btn btn-outline-dark btn-sm fw-semibold">
                        <i class="bi bi-printer-fill me-1"></i> Print All Labels
                    </a>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold text-dark ms-2" id="vModalSwitchEditBtn">
                        <i class="bi bi-pencil-square me-1"></i> Edit Details
                    </button>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. DYNAMIC EDIT MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="editStockModalLabel">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Edit QR &amp; Stock Details: <span id="eModalBatchCode" class="font-monospace text-primary"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 position-relative">
                <!-- Loading Spinner -->
                <div id="editModalLoading" class="text-center py-5">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Loading editable details...</div>
                </div>

                <!-- Edit Form -->
                <form id="editStockForm" onsubmit="event.preventDefault(); submitEditStockForm();" style="display: none;">
                    <input type="hidden" id="eGenerationId" name="generation_id" value="">

                    <!-- Alert message area -->
                    <div id="editFormAlert" class="alert alert-danger py-2 small mb-3" style="display: none;"></div>

                    <!-- Batch Level Settings -->
                    <div class="card border shadow-sm mb-4">
                        <div class="card-header bg-white py-2 px-3 fw-bold small text-uppercase text-secondary">
                            <i class="bi bi-sliders me-1 text-primary"></i> 1. Stock Line &amp; Roll Metadata
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Item / Material</label>
                                    <input type="text" id="eItemName" class="form-control form-control-sm bg-light" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Roll Number / Identifier</label>
                                    <input type="text" id="eRollNumber" name="roll_number" class="form-control form-control-sm" placeholder="e.g. ROLL #1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Weight (kg)</label>
                                    <input type="number" step="0.01" id="eWeightKg" name="weight_kg" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Brand Name</label>
                                    <input type="text" id="eBrandName" name="brand_name" class="form-control form-control-sm" placeholder="Brand Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Color</label>
                                    <input type="text" id="eColorName" name="color_name" class="form-control form-control-sm" placeholder="Color Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Size</label>
                                    <input type="text" id="eSizeName" name="size_name" class="form-control form-control-sm" placeholder="Size Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">MRP (₹)</label>
                                    <input type="number" step="0.01" id="eMrp" name="mrp" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Selling Price (₹)</label>
                                    <input type="number" step="0.01" id="eSellingPrice" name="selling_price" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Notes &amp; Remarks</label>
                                    <input type="text" id="eNotes" name="notes" class="form-control form-control-sm" placeholder="Remarks...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual QR Items Table -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-uppercase text-secondary">
                                <i class="bi bi-upc-scan me-1 text-primary"></i> 2. Individual QR Labels (<span id="eQrItemsCount">0</span>)
                            </span>
                            <span class="small text-muted">Update status or pricing per QR code</span>
                        </div>
                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-light text-uppercase small sticky-top">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th style="min-width: 140px;">QR Code</th>
                                        <th style="min-width: 130px;">Status</th>
                                        <th style="min-width: 110px;">Color</th>
                                        <th style="min-width: 90px;">Size</th>
                                        <th style="min-width: 100px;">MRP (₹)</th>
                                        <th style="min-width: 100px;">Selling Price (₹)</th>
                                    </tr>
                                </thead>
                                <tbody id="eQrItemsTableBody">
                                    <!-- Rows populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3" id="saveEditBtn" onclick="submitEditStockForm()">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. DYNAMIC DELETE CONFIRMATION MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="deleteStockModal" tabindex="-1" aria-labelledby="deleteStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger-subtle text-danger py-3 border-bottom">
                <h5 class="modal-title fw-bold" id="deleteStockModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Stocked Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">Are you sure you want to delete stocked batch <strong id="delBatchCode" class="font-monospace text-danger"></strong>?</p>
                <div class="alert alert-warning small mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    This action will <strong>delete all associated QR barcodes</strong> and <strong>reverse the received units from warehouse inventory</strong>.
                </div>
                <input type="hidden" id="delGenerationId" value="">
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm fw-bold px-3" id="confirmDeleteBtn" onclick="executeDeleteStockRow()">
                    <i class="bi bi-trash-fill me-1"></i> Yes, Delete Stock Line
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Live Notification Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="dynamicToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Action completed successfully.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- QR Code Generator JS Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let currentViewStockData = null;

function showToast(message, isSuccess = true) {
    const toastEl = document.getElementById('dynamicToast');
    const toastMsg = document.getElementById('toastMessage');
    toastMsg.textContent = message;
    toastEl.className = 'toast align-items-center text-white border-0 shadow ' + (isSuccess ? 'bg-success' : 'bg-danger');
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}

/**
 * ====================================================
 * VIEW ACTION: OPEN DYNAMIC VIEW MODAL
 * ====================================================
 */
function openViewModal(generationId) {
    const modalEl = document.getElementById('viewStockModal');
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById('viewModalLoading').style.display = 'block';
    document.getElementById('viewModalContent').style.display = 'none';
    modal.show();

    fetch('<?= url("production/purchased-stock/details/") ?>' + generationId, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Unable to load stock details.');
            modal.hide();
            return;
        }

        currentViewStockData = res.data;
        const s = res.data.stock;
        const items = res.data.items || [];

        // Header
        document.getElementById('vModalBatchCode').textContent = s.batch_code;
        const typeBadge = document.getElementById('vModalTypeBadge');
        if (s.item_type === 'FABRIC_ROLL') {
            typeBadge.className = 'badge bg-primary-subtle text-primary border';
            typeBadge.textContent = 'Fabric Roll';
        } else if (s.item_type === 'RAW_MATERIAL') {
            typeBadge.className = 'badge bg-warning-subtle text-dark border';
            typeBadge.textContent = 'Raw Material';
        } else {
            typeBadge.className = 'badge bg-info-subtle text-dark border';
            typeBadge.textContent = 'Third-Party';
        }

        // Summary details
        document.getElementById('vItemName').textContent = s.item_name || '—';
        let attrParts = [];
        if (s.roll_number) attrParts.push('<span class="badge bg-light text-dark border me-1">' + escapeHtml(s.roll_number) + '</span>');
        if (s.color_name) attrParts.push('Color: ' + escapeHtml(s.color_name));
        if (s.size_name) attrParts.push('Size: ' + escapeHtml(s.size_name));
        if (s.vendor_sku) attrParts.push('<span class="font-monospace text-muted">[' + escapeHtml(s.vendor_sku) + ']</span>');
        document.getElementById('vItemAttributes').innerHTML = attrParts.join(' · ') || '—';

        const pLink = document.getElementById('vPurchaseLink');
        pLink.textContent = s.purchase_number || '—';
        pLink.href = res.data.purchase_url || '#';

        document.getElementById('vVendorName').textContent = s.seller_company || s.seller_name || '—';
        document.getElementById('vPurchaseDate').textContent = s.purchase_date ? 'Date: ' + s.purchase_date : '';

        const qtyNum = parseFloat(s.purchase_qty || 0).toFixed(2);
        const weightNum = parseFloat(s.weight_kg || 0);
        let qtyText = qtyNum + ' ' + (s.unit || 'PCS');
        if (weightNum > 0) qtyText += ' (' + weightNum.toFixed(2) + ' kg)';
        document.getElementById('vStockQty').textContent = qtyText;

        document.getElementById('vUnitRate').textContent = '₹' + parseFloat(s.unit_price || 0).toFixed(2);
        document.getElementById('vTotalPrice').textContent = '₹' + parseFloat(s.total_price || 0).toFixed(2);

        let catTree = s.category_name || 'General';
        if (s.subcategory_name) catTree += ' › ' + s.subcategory_name;
        if (s.sub_subcategory_name) catTree += ' › ' + s.sub_subcategory_name;
        document.getElementById('vCategoryTree').textContent = catTree;

        document.getElementById('vStockAddedAt').textContent = s.stock_added_at || '—';
        document.getElementById('vStockAddedBy').textContent = s.stock_added_by_name || '—';
        document.getElementById('vBrandName').textContent = s.brand_name || '—';
        document.getElementById('vNotes').textContent = s.notes || 'No remarks recorded.';

        // Print buttons
        document.getElementById('vModalPrintBtn').href = res.data.print_url;
        document.getElementById('vModalPrintBtnTop').href = res.data.print_url;

        // Switch to Edit
        document.getElementById('vModalSwitchEditBtn').onclick = function() {
            modal.hide();
            setTimeout(() => openEditModal(generationId), 300);
        };

        // Render QR Cards
        document.getElementById('vQrItemsCount').textContent = items.length;
        renderViewQrCards(items, s);

        document.getElementById('viewModalLoading').style.display = 'none';
        document.getElementById('viewModalContent').style.display = 'block';
    })
    .catch(err => {
        alert('Network error while loading details.');
        modal.hide();
    });
}

function renderViewQrCards(items, stock) {
    const container = document.getElementById('vQrCardsContainer');
    container.innerHTML = '';

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted small">No QR items recorded for this batch.</div>';
        return;
    }

    items.forEach(function(item) {
        const col = document.createElement('div');
        col.className = 'col qr-card-item';
        col.dataset.code = item.qr_code.toLowerCase();

        let statusBadge = '<span class="badge bg-success-subtle text-success border">ACTIVE</span>';
        if (item.status === 'USED') statusBadge = '<span class="badge bg-info-subtle text-info border">USED</span>';
        else if (item.status === 'DAMAGED') statusBadge = '<span class="badge bg-danger-subtle text-danger border">DAMAGED</span>';
        else if (item.status === 'CANCELLED') statusBadge = '<span class="badge bg-secondary-subtle text-secondary border">CANCELLED</span>';

        const colorStr = item.color_name || stock.color_name || '';
        const sizeStr = item.size_name || stock.size_name || '';
        const mrpVal = parseFloat(item.mrp || stock.mrp || 0);
        const spVal = parseFloat(item.selling_price || stock.selling_price || 0);

        let pricingHtml = '';
        if (mrpVal > 0 || spVal > 0) {
            pricingHtml = '<div class="small fw-semibold mt-1">';
            if (mrpVal > 0) pricingHtml += '<del class="text-muted me-1">₹' + mrpVal.toFixed(2) + '</del>';
            if (spVal > 0) pricingHtml += '<span class="text-primary fw-bold">₹' + spVal.toFixed(2) + '</span>';
            pricingHtml += '</div>';
        }

        col.innerHTML = `
            <div class="p-3 border rounded bg-white text-center shadow-xs h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-light text-muted border">#${item.sequence_num}</span>
                        ${statusBadge}
                    </div>
                    <div class="d-flex justify-content-center my-2">
                        <div class="qr-canvas-holder p-1 bg-white border rounded" id="qr-canvas-${item.id}"></div>
                    </div>
                    <div class="font-monospace fw-bold text-primary small text-break user-select-all" title="Click to copy" onclick="copyToClipboard('${item.qr_code}', this)">
                        ${escapeHtml(item.qr_code)}
                    </div>
                    ${(colorStr || sizeStr) ? `<div class="small text-muted mt-1">${escapeHtml(colorStr)}${colorStr && sizeStr ? ' · ' : ''}${sizeStr ? '<span class="badge bg-dark text-white px-1 py-0 ms-1">' + escapeHtml(sizeStr) + '</span>' : ''}</div>` : ''}
                    ${pricingHtml}
                </div>
            </div>
        `;
        container.appendChild(col);

        // Generate QR code canvas
        setTimeout(() => {
            const canvasEl = document.getElementById('qr-canvas-' + item.id);
            if (canvasEl && window.QRCode) {
                new QRCode(canvasEl, {
                    text: item.qr_code,
                    width: 76,
                    height: 76,
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }, 10);
    });
}

function filterViewQrCards(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('#vQrCardsContainer .qr-card-item').forEach(el => {
        if (!q || el.dataset.code.includes(q)) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
}

function copyToClipboard(text, el) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = el.textContent;
            el.textContent = 'Copied!';
            setTimeout(() => el.textContent = orig, 1200);
        });
    }
}

/**
 * ====================================================
 * EDIT ACTION: OPEN DYNAMIC EDIT MODAL
 * ====================================================
 */
function openEditModal(generationId) {
    const modalEl = document.getElementById('editStockModal');
    const modal = new bootstrap.Modal(modalEl);

    document.getElementById('editModalLoading').style.display = 'block';
    document.getElementById('editStockForm').style.display = 'none';
    document.getElementById('editFormAlert').style.display = 'none';
    modal.show();

    fetch('<?= url("production/purchased-stock/details/") ?>' + generationId, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Unable to load stock details.');
            modal.hide();
            return;
        }

        const s = res.data.stock;
        const items = res.data.items || [];

        document.getElementById('eModalBatchCode').textContent = s.batch_code;
        document.getElementById('eGenerationId').value = s.generation_id;
        document.getElementById('eItemName').value = s.item_name || '';
        document.getElementById('eRollNumber').value = s.roll_number || '';
        document.getElementById('eWeightKg').value = s.weight_kg ? parseFloat(s.weight_kg) : '';
        document.getElementById('eBrandName').value = s.brand_name || '';
        document.getElementById('eColorName').value = s.color_name || '';
        document.getElementById('eSizeName').value = s.size_name || '';
        document.getElementById('eMrp').value = s.mrp ? parseFloat(s.mrp) : '';
        document.getElementById('eSellingPrice').value = s.selling_price ? parseFloat(s.selling_price) : '';
        document.getElementById('eNotes').value = s.notes || '';

        // Populate QR items table
        document.getElementById('eQrItemsCount').textContent = items.length;
        const tbody = document.getElementById('eQrItemsTableBody');
        tbody.innerHTML = '';

        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-muted small">${item.sequence_num}</td>
                <td class="font-monospace fw-semibold small text-primary">${escapeHtml(item.qr_code)}</td>
                <td>
                    <select name="qr_items[${item.id}][status]" class="form-select form-select-sm">
                        <option value="ACTIVE" ${item.status === 'ACTIVE' ? 'selected' : ''}>ACTIVE</option>
                        <option value="USED" ${item.status === 'USED' ? 'selected' : ''}>USED</option>
                        <option value="DAMAGED" ${item.status === 'DAMAGED' ? 'selected' : ''}>DAMAGED</option>
                        <option value="CANCELLED" ${item.status === 'CANCELLED' ? 'selected' : ''}>CANCELLED</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="qr_items[${item.id}][color_name]" class="form-control form-control-sm" value="${escapeHtml(item.color_name || '')}" placeholder="Color">
                </td>
                <td>
                    <input type="text" name="qr_items[${item.id}][size_name]" class="form-control form-control-sm" value="${escapeHtml(item.size_name || '')}" placeholder="Size">
                </td>
                <td>
                    <input type="number" step="0.01" name="qr_items[${item.id}][mrp]" class="form-control form-control-sm" value="${item.mrp ? parseFloat(item.mrp) : ''}" placeholder="0.00">
                </td>
                <td>
                    <input type="number" step="0.01" name="qr_items[${item.id}][selling_price]" class="form-control form-control-sm" value="${item.selling_price ? parseFloat(item.selling_price) : ''}" placeholder="0.00">
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('editModalLoading').style.display = 'none';
        document.getElementById('editStockForm').style.display = 'block';
    })
    .catch(err => {
        alert('Network error while loading editable data.');
        modal.hide();
    });
}

function submitEditStockForm() {
    const form = document.getElementById('editStockForm');
    const saveBtn = document.getElementById('saveEditBtn');
    const alertBox = document.getElementById('editFormAlert');
    const generationId = document.getElementById('eGenerationId').value;
    const csrfToken = document.getElementById('pageCsrfToken').value;

    alertBox.style.display = 'none';
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';

    const formData = new FormData(form);
    formData.append('_csrf_token', csrfToken);

    fetch('<?= url("production/purchased-stock/update/") ?>' + generationId, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Changes';

        if (!res.success) {
            alertBox.textContent = res.message || 'Error updating stock line.';
            alertBox.style.display = 'block';
            return;
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editStockModal'));
        if (modal) modal.hide();

        // Dynamically update the table row without page refresh!
        const d = res.data;
        const row = document.getElementById('stock-row-' + generationId);
        if (row) {
            const metaContainer = row.querySelector('.item-meta');
            if (metaContainer) {
                let parts = [];
                if (d.roll_number) {
                    parts.push(`<span class="badge bg-light text-dark border me-1 roll-badge">${escapeHtml(d.roll_number)}</span>`);
                }
                if (d.color_name) {
                    parts.push(`<span class="me-1 color-info">Color: ${escapeHtml(d.color_name)}</span>`);
                }
                if (d.size_name) {
                    parts.push(`<span class="size-info">Size: ${escapeHtml(d.size_name)}</span>`);
                }
                metaContainer.innerHTML = parts.join(' ');
            }

            const weightContainer = row.querySelector('.weight-display');
            if (weightContainer) {
                if (d.weight_kg && parseFloat(d.weight_kg) > 0) {
                    weightContainer.textContent = parseFloat(d.weight_kg).toFixed(2) + ' kg';
                } else {
                    weightContainer.textContent = '';
                }
            }

            // Flash row green briefly
            row.style.transition = 'background-color 0.4s ease';
            row.style.backgroundColor = '#d1e7dd';
            setTimeout(() => { row.style.backgroundColor = ''; }, 1200);
        }

        showToast(res.message || 'Stock line updated successfully.', true);
    })
    .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Changes';
        alertBox.textContent = 'Network or server error during update.';
        alertBox.style.display = 'block';
    });
}

/**
 * ====================================================
 * DELETE ACTION: DYNAMIC CONFIRMATION & LIVE DOM REMOVAL
 * ====================================================
 */
function confirmDeleteRow(generationId, batchCode) {
    document.getElementById('delGenerationId').value = generationId;
    document.getElementById('delBatchCode').textContent = batchCode;
    const modal = new bootstrap.Modal(document.getElementById('deleteStockModal'));
    modal.show();
}

function executeDeleteStockRow() {
    const generationId = document.getElementById('delGenerationId').value;
    const delBtn = document.getElementById('confirmDeleteBtn');
    const csrfToken = document.getElementById('pageCsrfToken').value;

    delBtn.disabled = true;
    delBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Deleting...';

    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);

    fetch('<?= url("production/purchased-stock/delete/") ?>' + generationId, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        delBtn.disabled = false;
        delBtn.innerHTML = '<i class="bi bi-trash-fill me-1"></i> Yes, Delete Stock Line';

        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteStockModal'));
        if (modal) modal.hide();

        if (!res.success) {
            alert(res.message || 'Failed to delete stock line.');
            return;
        }

        // Live Row Removal from Table with fade animation
        const row = document.getElementById('stock-row-' + generationId);
        if (row) {
            const itemType = row.dataset.itemType;
            const units = parseFloat(row.dataset.units || 1);

            row.style.transition = 'all 0.5s ease';
            row.style.backgroundColor = '#f8d7da';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';

            setTimeout(() => {
                row.remove();
                reindexTableRows();
                decrementKpis(itemType, units);
            }, 500);
        }

        showToast(res.message || 'Stock line removed successfully.', true);
    })
    .catch(err => {
        delBtn.disabled = false;
        delBtn.innerHTML = '<i class="bi bi-trash-fill me-1"></i> Yes, Delete Stock Line';
        alert('Network or server error during delete.');
    });
}

function decrementKpis(itemType, units) {
    // Total batches
    const totalBatchesEl = document.getElementById('kpi-total-batches');
    if (totalBatchesEl) {
        const cur = Math.max(0, parseInt(totalBatchesEl.textContent.replace(/,/g, '')) - 1);
        totalBatchesEl.textContent = cur.toLocaleString();
    }

    // Total units
    const totalUnitsEl = document.getElementById('kpi-total-units');
    if (totalUnitsEl) {
        const curUnits = Math.max(0, parseFloat(totalUnitsEl.textContent.replace(/,/g, '')) - units);
        totalUnitsEl.textContent = curUnits.toLocaleString();
    }

    // Sub-counts
    if (itemType === 'FABRIC_ROLL') {
        const fEl = document.getElementById('kpi-fabric-count');
        const tabF = document.getElementById('tab-fabric-count');
        if (fEl) fEl.textContent = Math.max(0, parseInt(fEl.textContent.replace(/,/g, '')) - 1).toLocaleString();
        if (tabF) tabF.textContent = fEl ? fEl.textContent : '0';
    } else if (itemType === 'RAW_MATERIAL') {
        const rEl = document.getElementById('kpi-raw-count');
        const tabR = document.getElementById('tab-raw-count');
        if (rEl) rEl.textContent = Math.max(0, parseInt(rEl.textContent.replace(/,/g, '')) - 1).toLocaleString();
        if (tabR) tabR.textContent = rEl ? rEl.textContent : '0';
    } else if (itemType === 'THIRD_PARTY') {
        const tEl = document.getElementById('kpi-third-party-count');
        const tabT = document.getElementById('tab-third-party-count');
        if (tEl) tEl.textContent = Math.max(0, parseInt(tEl.textContent.replace(/,/g, '')) - 1).toLocaleString();
        if (tabT) tabT.textContent = tEl ? tEl.textContent : '0';
    }

    // Table header badge
    const badgeEl = document.getElementById('table-batch-count');
    const remainingRows = document.querySelectorAll('#stockTableBody tr[id^="stock-row-"]').length;
    if (badgeEl) badgeEl.textContent = remainingRows + ' Batches in Warehouse Stock';

    // If no rows remain, show empty state
    if (remainingRows === 0) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = `
            <tr id="emptyStockRow">
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                    No purchased items have been added to stock yet.
                    <div class="mt-2 text-muted small">
                        When you generate QR batches and click <strong>"Add to Purchased Stock"</strong>, they will be listed here.
                    </div>
                </td>
            </tr>
        `;
    }
}

function reindexTableRows() {
    const rows = document.querySelectorAll('#stockTableBody tr[id^="stock-row-"]');
    rows.forEach((r, i) => {
        const idxCell = r.querySelector('.row-index');
        if (idxCell) idxCell.textContent = i + 1;
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
