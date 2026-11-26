<?php
// views/production/inventory/index.php
// Production & Finished Goods Inventory: Stitching Stock & Third-Party Products
$activeTab = $activeTab ?? 'all';
$stockState = $stockState ?? '';
$inventoryVariantCount = array_sum(array_map(
    static fn(array $batch): int => count($batch['variants'] ?? []),
    $batches
));
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-box2-heart me-2 text-success"></i>Production Inventory &amp; Finished Goods</h2>
        <span class="text-muted">Production stitching goods, third-party vendor products, and active inventory valuation</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('batches', 'create')): ?>
        <a href="<?= url('production/batches/create') ?>" class="btn btn-success shadow-sm">
            <i class="bi bi-box-seam me-1"></i> Pack for Central Warehouse
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Available Stock</div>
                    <div class="fw-bold text-success fs-3 mt-1"><?= number_format($stats['total_remaining_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-check2-circle text-success me-1"></i>Ready for Allocation</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <?php 
                        $hasStitching = ($stats['stitching_pieces'] ?? 0) > 0;
                        $hasInward = ($stats['inward_pieces'] ?? 0) > 0;
                        $primaryStockTitle = $hasStitching ? 'Production Stitching Stock' : ($hasInward ? 'Inward Finished Stock' : 'Production Stitching Stock');
                        $primaryStockPieces = $hasStitching ? ($stats['stitching_pieces'] ?? 0) : ($stats['inward_pieces'] ?? 0);
                        $primaryStockVal = $hasStitching ? ($stats['stitching_val'] ?? 0) : ($stats['inward_val'] ?? 0);
                    ?>
                    <div class="text-uppercase fw-semibold text-muted small"><?= $primaryStockTitle ?></div>
                    <div class="fw-bold text-primary fs-3 mt-1"><?= number_format($primaryStockPieces) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi <?= $hasStitching ? 'bi-scissors' : ($hasInward ? 'bi-box-arrow-in-down' : 'bi-scissors') ?> text-primary me-1"></i>Est. Val: <?= formatCurrency((float)($primaryStockVal)) ?></div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi <?= $hasStitching ? 'bi-scissors' : ($hasInward ? 'bi-box-arrow-in-down' : 'bi-scissors') ?> fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Third-Party Products</div>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= number_format($stats['third_party_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-bag-check-fill text-warning me-1"></i>Est. Val: <?= formatCurrency((float)($stats['third_party_val'] ?? 0)) ?></div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-bag-check-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-danger border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Outward / Dispatched</div>
                    <div class="fw-bold text-danger fs-3 mt-1"><?= number_format($stats['total_outwarded_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-truck text-danger me-1"></i>Dispatched to Warehouse/Billed</div>
                </div>
                <div class="bg-danger-subtle text-danger revenue-kpi-icon">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs (Image 2 style) -->
<ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="inventoryNavTabs">
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'all' || empty($activeTab)) ? 'active bg-dark text-white shadow-sm' : 'bg-white text-dark border' ?> fw-semibold px-4 py-2 rounded-pill" href="<?= url('production/inventory?tab=all' . (!empty($categoryFilter) ? '&category_id=' . $categoryFilter : '') . (!empty($stockState) ? '&stock_state=' . $stockState : '') . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>">
            <i class="bi bi-collection me-1"></i> All Stock (<?= ($stats['stitching_count'] ?? 0) + ($stats['third_party_count'] ?? 0) + ($stats['inward_count'] ?? 0) ?>)
        </a>
    </li>
    <?php if (($stats['inward_count'] ?? 0) > 0): ?>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'inward') ? 'active bg-info text-white shadow-sm' : 'bg-white text-dark border' ?> fw-semibold px-4 py-2 rounded-pill" href="<?= url('production/inventory?tab=inward' . (!empty($categoryFilter) ? '&category_id=' . $categoryFilter : '') . (!empty($stockState) ? '&stock_state=' . $stockState : '') . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>">
            <i class="bi bi-box-arrow-in-down me-1"></i> Inwarded Finished Goods (<?= $stats['inward_count'] ?? 0 ?>)
        </a>
    </li>
    <?php endif; ?>
    <?php if (($stats['stitching_count'] ?? 0) > 0 || ($stats['inward_count'] ?? 0) === 0): ?>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'stitching') ? 'active bg-success text-white shadow-sm' : 'bg-white text-dark border' ?> fw-semibold px-4 py-2 rounded-pill" href="<?= url('production/inventory?tab=stitching' . (!empty($categoryFilter) ? '&category_id=' . $categoryFilter : '') . (!empty($stockState) ? '&stock_state=' . $stockState : '') . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>">
            <i class="bi bi-scissors me-1"></i> Production Stitching Stock (<?= $stats['stitching_count'] ?? 0 ?>)
        </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab === 'third_party') ? 'active bg-primary text-white shadow-sm' : 'bg-white text-dark border' ?> fw-semibold px-4 py-2 rounded-pill" href="<?= url('production/inventory?tab=third_party' . (!empty($categoryFilter) ? '&category_id=' . $categoryFilter : '') . (!empty($stockState) ? '&stock_state=' . $stockState : '') . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>">
            <i class="bi bi-box-seam me-1"></i> Third-Party Products (<?= $stats['third_party_count'] ?? 0 ?>)
        </a>
    </li>
</ul>

<!-- Filters & Search Bar (Image 2 style) -->
<div class="card mb-4 shadow-sm border">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/inventory') ?>" class="row g-3 align-items-end" id="inventoryFilterForm">
            <input type="hidden" name="tab" id="filterInvTypeInput" value="<?= e($activeTab ?? 'all') ?>">
            <div class="col-lg-3 col-md-6">
                <label for="inventoryStockStateSelect" class="form-label small text-muted fw-semibold mb-1">Stock State</label>
                <select name="stock_state" id="inventoryStockStateSelect" class="form-select" style="height: 38px; border-color: #cbd5e1; border-radius: 8px; font-size: 0.875rem; padding-right: 2.25rem;" onchange="document.getElementById('inventoryFilterForm').submit()">
                    <option value="">All Stock States</option>
                    <option value="AVAILABLE" <?= ($stockState === 'AVAILABLE') ? 'selected' : '' ?>>AVAILABLE (In-Stock)</option>
                    <option value="PARTIALLY_OUTWARDED" <?= ($stockState === 'PARTIALLY_OUTWARDED') ? 'selected' : '' ?>>Partially Outwarded</option>
                    <option value="FULLY_OUTWARDED" <?= ($stockState === 'FULLY_OUTWARDED') ? 'selected' : '' ?>>Fully Outwarded</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="inventoryCategorySelect" class="form-label small text-muted fw-semibold mb-1">Product Category</label>
                <select name="category_id" id="inventoryCategorySelect" class="form-select" style="height: 38px; border-color: #cbd5e1; border-radius: 8px; font-size: 0.875rem; padding-right: 2.25rem;" onchange="document.getElementById('inventoryFilterForm').submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-4 col-md-8">
                <label for="inventorySearchInput" class="form-label small text-muted fw-semibold mb-1">Search Inventory</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-color: #cbd5e1; border-radius: 8px 0 0 8px; height: 38px;"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" 
                           name="search" 
                           id="inventorySearchInput" 
                           class="form-control border-start-0" 
                           style="height: 38px; border-color: #cbd5e1; border-radius: <?= !empty($search) ? '0' : '0 8px 8px 0' ?>; font-size: 0.875rem;"
                           placeholder="Search QR code tag, Variant ID, product name, program #, batch #, color..." 
                           value="<?= e($search ?? '') ?>"
                           autocomplete="off">
                    <?php if (!empty($search)): ?>
                        <button type="button" class="btn btn-outline-secondary border-start-0" style="height: 38px; border-color: #cbd5e1; border-radius: 0 8px 8px 0;" onclick="clearSearchInput()" title="Clear search">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm px-3" style="height: 38px; border-radius: 8px; font-size: 0.875rem; gap: 6px;" title="Filter Inventory">
                    <i class="bi bi-filter"></i> <span>Filter</span>
                </button>
                <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="height: 38px; width: 38px; min-width: 38px; padding: 0; border-radius: 8px; border-color: #cbd5e1;" title="Reset All Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Products Header & Price Edit Action Bar (Image 2 style) -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="bi bi-boxes me-2 text-primary"></i>
            <span id="inventoryItemsTitle"><?= $activeTab === 'stitching' ? 'Production Stitching Stock' : ($activeTab === 'third_party' ? 'Third-Party Products' : ($activeTab === 'inward' ? 'Inwarded Finished Goods' : 'Finished Goods Products')) ?></span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2" id="totalBatchBadgeCount"><?= count($batches) ?> Batches (<?= $inventoryVariantCount ?> Sizes)</span>
        </h5>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (hasPermission('inventory', 'edit') || (function_exists('isAdmin') && isAdmin())): ?>
            <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
            </button>
            <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
            </button>
        <?php endif; ?>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
            <i class="bi bi-boxes me-1"></i> <?= number_format($stats['total_remaining_pieces'] ?? 0) ?> pcs Available
        </span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
            <?= formatCurrency((float)($stats['total_remaining_valuation'] ?? 0)) ?> Est. Valuation
        </span>
    </div>
</div>

<?php if (empty($batches)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
        <h6 class="fw-semibold mb-1"><?= (!empty($search) || !empty($categoryFilter) || !empty($stockState)) ? 'No Matching Products Found' : 'No Finished Inventory Products' ?></h6>
        <p class="small text-muted mb-3">
            <?= (!empty($search) || !empty($categoryFilter) || !empty($stockState)) 
                ? 'Try adjusting your search terms or clearing filters.' 
                : 'Finished goods from stitching QC or third-party purchases will appear here.' ?>
        </p>
        <?php if (!empty($search) || !empty($categoryFilter) || !empty($stockState)): ?>
            <a href="<?= url('production/inventory') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear All Filters
            </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Live filter empty state (hidden by default) -->
<div id="noMatchingBatchesAlert" class="card border-0 shadow-sm mb-3" style="display: none;">
    <div class="card-body text-center py-5">
        <i class="bi bi-search fs-1 d-block mb-3 text-muted"></i>
        <h6 class="fw-semibold mb-1">No Products Match Your Search</h6>
        <p class="small text-muted mb-3">No inventory items matched the current filter keyword.</p>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearLiveFilter()">
            <i class="bi bi-x-circle me-1"></i> Clear Search
        </button>
    </div>
</div>

<div class="table-responsive card border-0 shadow-sm mb-3" id="inventoryBatchList">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="price-checkbox-col" style="display: none; width: 40px;">
                    <input type="checkbox" class="form-check-input" id="thSelectAllPrice" onchange="toggleSelectAllPrice(this)">
                </th>
                <th>Batch / Source</th>
                <th>Program / PO #</th>
                <th>Product &amp; Category</th>
                <th>Variant (Size / Color)</th>
                <th class="text-center">QC / Inward</th>
                <th class="text-center">In Stock</th>
                <th class="text-center">Outward</th>
                <th>Stock State</th>
                <th class="text-end">MRP (₹)</th>
                <th class="text-end">Discount Price (₹)</th>
                <th class="text-end">Valuation (₹)</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
                <?php
                    $batchStatus = $batch['stock_status'] ?? 'IN_STOCK';
                    $batchVariants = $batch['variants'] ?? [];
                    $allVariantIds = array_filter(array_map('intval', array_column($batchVariants, 'variant_id')));
                    $allVariantIdsStr = implode(',', $allVariantIds);
                    $primaryVariantId = !empty($allVariantIds) ? $allVariantIds[0] : 0;

                    $batchOutwardSearchText = '';
                    foreach ($batch['outward_history'] ?? [] as $outward) {
                        $batchOutwardSearchText .= ' ' . ($outward['outward_number'] ?? '') . ' ' . ($outward['warehouse_name'] ?? '') . ' ' . ($outward['vehicle_number'] ?? '');
                    }

                    $allVariantSearchText = '';
                    foreach ($batchVariants as $bv) {
                        $allVariantSearchText .= ' ' . ($bv['variant_code'] ?? '') . ' ' . ($bv['size_name'] ?? '') . ' ' . ($bv['color_name'] ?? '');
                    }

                    $batchSearchText = strtolower(
                        ($batch['product_name'] ?? '') . ' ' .
                        ($batch['category_name'] ?? '') . ' ' .
                        ($batch['program_number'] ?? '') . ' ' .
                        ($batch['inspector_name'] ?? '') . ' ' .
                        $batch['qc_batch_id'] . ' ' .
                        $allVariantSearchText . ' ' .
                        $batchOutwardSearchText
                    );

                    $mrps = array_unique(array_filter(array_column($batchVariants, 'mrp'), fn($val) => $val > 0));
                    $prices = array_unique(array_filter(array_column($batchVariants, 'selling_price'), fn($val) => $val > 0));
                    $minMrp = !empty($mrps) ? (float)min($mrps) : 0.0;
                    $maxMrp = !empty($mrps) ? (float)max($mrps) : 0.0;
                    $minPrice = !empty($prices) ? (float)min($prices) : 0.0;
                    $maxPrice = !empty($prices) ? (float)max($prices) : 0.0;
                    $firstCost = !empty($batchVariants[0]['cost_price']) ? (float)$batchVariants[0]['cost_price'] : 0.0;
                    $firstVariantCode = (string)($batchVariants[0]['variant_code'] ?? '');

                    $totalPassed = (int)($batch['total_pieces'] ?? 0);
                    $totalInStock = (int)($batch['remaining_pieces'] ?? 0);
                    $totalOutward = (int)($batch['total_outward_pieces'] ?? 0);
                    $batchVal = (float)($batch['remaining_value'] ?? 0);
                ?>
                <tr class="batch-list-row"
                    id="batch-row-<?= e($batch['qc_batch_id']) ?>"
                    data-batch-id="<?= e($batch['qc_batch_id']) ?>"
                    data-variant-id="<?= $primaryVariantId ?>"
                    data-variant-ids="<?= e($allVariantIdsStr) ?>"
                    data-category-id="<?= (int)($batch['category_id'] ?? 0) ?>"
                    data-inv-type="<?= e($batch['tab_type'] ?? 'stitching') ?>"
                    data-search-text="<?= e($batchSearchText) ?>"
                    data-filter-match="true">
                    
                    <!-- Checkbox Column for Price Edit Mode -->
                    <td class="price-checkbox-col" style="display: none;">
                        <input type="checkbox" 
                               class="form-check-input price-item-checkbox" 
                               value="<?= e($allVariantIdsStr ?: $primaryVariantId) ?>" 
                               data-variant-id="<?= $primaryVariantId ?>" 
                               data-variant-ids="<?= e($allVariantIdsStr) ?>"
                               data-variant-code="<?= e($firstVariantCode) ?>" 
                               data-product-name="<?= e($batch['product_name']) ?>" 
                               data-mrp="<?= $minMrp ?>" 
                               data-selling-price="<?= $minPrice ?>" 
                               data-cost-price="<?= $firstCost ?>" 
                               data-stock="<?= $totalInStock ?>" 
                               onchange="updateSelectedPriceCount()">
                    </td>

                    <!-- Batch / Source Column -->
                    <td class="text-nowrap">
                        <?php if (!empty($batch['is_inward']) || ($batch['tab_type'] ?? '') === 'inward'): ?>
                            <div class="fw-semibold text-dark">
                                <a href="<?= url('production/inwards/view/' . ($batch['production_batch_id'] ?? $batch['qc_batch_id'])) ?>" class="text-decoration-none text-dark">
                                    <i class="bi bi-box-arrow-in-down me-1 text-info"></i><?= e($batch['batch_number'] ?? ('Batch #' . $batch['qc_batch_id'])) ?>
                                </a>
                            </div>
                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;">Inwarded Stock</span>
                        <?php elseif (($batch['tab_type'] ?? '') === 'third_party'): ?>
                            <div class="fw-semibold text-primary">
                                <?php if (!empty($batch['supply_item_id'])): ?>
                                    <a href="<?= url('production/inventory/supply/' . $batch['supply_item_id']) ?>" class="text-decoration-none text-primary">
                                        <i class="bi bi-box-seam me-1"></i>Purchase #<?= e($batch['qc_batch_id']) ?>
                                    </a>
                                <?php else: ?>
                                    <i class="bi bi-box-seam me-1"></i>Stock #<?= e($batch['qc_batch_id']) ?>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">Third-Party Product</span>
                        <?php else: ?>
                            <div class="fw-semibold text-dark">
                                <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id']) ?>" class="text-decoration-none text-dark">
                                    <i class="bi bi-patch-check-fill me-1 text-success"></i>QC Batch #<?= (int)$batch['qc_batch_id'] ?>
                                </a>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Stitching QC</span>
                        <?php endif; ?>
                        <small class="text-muted d-block"><?= (new DateTime($batch['batch_created_at'] ?? 'now'))->format('d M Y, g:i A') ?></small>
                    </td>

                    <!-- Program / PO # -->
                    <td>
                        <?php if (!empty($batch['is_inward'])): ?>
                            <span class="fw-semibold text-dark">
                                <?= e($batch['program_number'] ?: ($batch['batch_number'] ?? '')) ?>
                            </span>
                            <?php if (!empty($batch['origin_facility'])): ?>
                                <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>From: <?= e($batch['origin_facility']) ?></small>
                            <?php endif; ?>
                        <?php elseif (!empty($batch['purchase_id'])): ?>
                            <a href="<?= url('production/purchases/supply-qr/' . $batch['purchase_id']) ?>" class="text-decoration-none fw-semibold text-primary">
                                <?= e($batch['program_number']) ?>
                            </a>
                        <?php elseif (!empty($batch['program_id'])): ?>
                            <a href="<?= url('production/programs/view/' . $batch['program_id']) ?>" class="text-decoration-none fw-semibold">
                                <?= e($batch['program_number']) ?>
                            </a>
                        <?php else: ?>
                            <span class="fw-semibold text-secondary"><?= e($batch['program_number']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($firstVariantCode)): ?>
                            <small class="text-muted d-block font-monospace"><?= e($firstVariantCode) ?></small>
                        <?php endif; ?>
                    </td>

                    <!-- Product & Category -->
                    <td>
                        <div class="fw-semibold text-dark"><?= e($batch['product_name']) ?></div>
                        <?php if (!empty($batch['category_name'])): ?>
                            <span class="badge bg-light text-secondary border mt-1"><?= e($batch['category_name']) ?></span>
                        <?php endif; ?>
                    </td>

                    <!-- Variant (Size / Color) - Inside single profile to show size-wise breakdown -->
                    <td>
                        <div class="d-flex flex-column gap-1 py-1">
                            <?php foreach ($batchVariants as $bv): ?>
                                <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="badge bg-secondary-subtle text-secondary border fw-bold px-2 py-0.5">Size: <?= e($bv['size_name'] ?: 'STD') ?></span>
                                    <?php if (!empty($bv['color_name'])): ?>
                                        <span class="badge bg-info-subtle text-info border px-2 py-0.5">Color: <?= e($bv['color_name']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-success-subtle text-success border font-monospace fw-bold px-2 py-0.5"><?= number_format($bv['remaining_qty']) ?> pcs</span>
                                    <?php if (!empty($bv['sku'])): ?>
                                        <small class="text-muted font-monospace d-none d-xl-inline"><?= e($bv['sku']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </td>

                    <!-- QC / Inward Qty -->
                    <td class="text-center">
                        <div class="fw-semibold text-dark"><?= number_format($totalPassed) ?> pcs</div>
                        <?php if (count($batchVariants) > 1): ?>
                            <small class="text-muted d-block" style="font-size: 11px;">
                                <?= implode(' · ', array_map(fn($v) => e($v['size_name'] ?: 'STD') . ': ' . number_format($v['passed_qty']), $batchVariants)) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <!-- In Stock Qty -->
                    <td class="text-center">
                        <div class="fw-bold text-success row-stock-val"><?= number_format($totalInStock) ?> pcs</div>
                        <?php if (count($batchVariants) > 1): ?>
                            <small class="text-success d-block fw-semibold" style="font-size: 11px;">
                                <?= implode(' · ', array_map(fn($v) => e($v['size_name'] ?: 'STD') . ': ' . number_format($v['remaining_qty']), $batchVariants)) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <!-- Outward Qty -->
                    <td class="text-center fw-semibold text-danger"><?= number_format($totalOutward) ?> pcs</td>

                    <!-- Stock State -->
                    <td>
                        <?php if ($totalInStock === 0): ?>
                            <span class="badge bg-secondary-subtle text-secondary border">Fully Outwarded</span>
                        <?php elseif ($totalOutward > 0): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border">Partially Outwarded</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border">In Stock</span>
                        <?php endif; ?>
                    </td>

                    <!-- MRP (₹) -->
                    <td class="text-end text-nowrap">
                        <span class="text-muted font-monospace row-mrp-val">
                            <?= formatCurrency((float)($minMrp)) ?><?= ($maxMrp > $minMrp) ? ' - ₹' . number_format($maxMrp, 2) : '' ?>
                        </span>
                    </td>

                    <!-- Discount Price (₹) -->
                    <td class="text-end text-nowrap">
                        <span class="fw-bold text-success font-monospace row-selling-val">
                            <?= formatCurrency((float)($minPrice)) ?><?= ($maxPrice > $minPrice) ? ' - ₹' . number_format($maxPrice, 2) : '' ?>
                        </span>
                    </td>

                    <!-- Total Valuation (₹) -->
                    <td class="text-end text-nowrap">
                        <span class="fw-bold text-primary font-monospace row-val-val"><?= formatCurrency((float)($batchVal)) ?></span>
                    </td>

                    <!-- Actions -->
                    <td class="text-end">
                        <div class="action-btns-group justify-content-end">
                            <?php if (hasPermission('inventory', 'edit') || (function_exists('isAdmin') && isAdmin())): ?>
                                <button type="button" 
                                        class="btn btn-outline-success btn-action-icon" 
                                        title="Edit Batch / Variant Price"
                                        onclick='openEditPriceModal(<?= json_encode([
                                            'variant_id'   => $allVariantIdsStr ?: $primaryVariantId,
                                            'sku'          => $firstSku,
                                            'product_name' => (string)$batch['product_name'] . " (Batch #" . $batch['qc_batch_id'] . ")",
                                            'cost_price'   => $firstCost,
                                            'selling_price'=> $minPrice,
                                            'mrp'          => $minMrp,
                                            'remaining_qty'=> $totalInStock,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            <?php endif; ?>

                            <?php if (!empty($batch['is_inward'])): ?>
                                <a href="<?= url('production/inventory/batch/INW-' . ($batch['production_batch_id'] ?? $batch['qc_batch_id'])) ?>"
                                   class="btn btn-outline-secondary btn-action-icon" title="View Inwarded Batch Stock Ledger & Pieces">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/inventory/batch/INW-' . ($batch['production_batch_id'] ?? $batch['qc_batch_id']) . '#qrSection') ?>"
                                   class="btn btn-outline-info btn-action-icon" title="View Inward Piece QR Codes">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                            <?php elseif (!empty($batch['is_supply'])): ?>
                                <a href="<?= url('production/inventory/supply/' . $batch['supply_item_id']) ?>"
                                   class="btn btn-outline-secondary btn-action-icon" title="View Supply Batch & Inventory">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/inventory/supply/' . $batch['supply_item_id'] . '#qrSection') ?>"
                                   class="btn btn-outline-info btn-action-icon" title="View Supply QR Labels">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id']) ?>"
                                   class="btn btn-outline-secondary btn-action-icon" title="View Batch Items (Size-wise & Piece-wise)">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id'] . '#qrSection') ?>"
                                   class="btn btn-outline-info btn-action-icon" title="View Garment QR Labels">
                                    <i class="bi bi-qr-code"></i>
                                </a>
                                <?php if (hasPermission('batches', 'create') && $totalInStock > 0): ?>
                                    <a href="<?= url('production/batches/create?qc_batch_id=' . $batch['qc_batch_id']) ?>"
                                       class="btn btn-outline-success btn-action-icon" title="Pack into Boxes for Central Warehouse">
                                       <i class="bi bi-box-seam"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Floating Action Bar for Price Edit (Image 2 style) -->
<div id="priceFloatingActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow-lg rounded-pill p-2 px-4 bg-dark text-white d-flex align-items-center gap-3 z-3" style="display: none !important;">
    <span class="badge bg-success rounded-pill px-3 py-2 fw-semibold">
        <i class="bi bi-check2-circle me-1"></i> <span id="floatingSelectedCount">0</span> selected
    </span>
    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="selectAllPriceItems(true)">
        <i class="bi bi-check-all me-1"></i> Select All
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-white border-secondary" onclick="clearPriceSelection()">
        Clear
    </button>
    <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm" onclick="openBulkPriceModal()">
        <i class="bi bi-pencil-square me-1"></i> Change Price
    </button>
</div>

<!-- Cards Grid Layout -->
<div class="row g-3 inventory-batch-list-mode" id="inventoryBatchesGrid" style="display: none;">
    <?php foreach ($batches as $idx => $batch): ?>
    <?php
        $batchDate = new DateTime($batch['batch_created_at'] ?? 'now');
        $variantKeywords = implode(' ', array_map(function($v) {
            return ($v['size_name'] ?? '') . ' ' . ($v['color_name'] ?? '');
        }, $batch['variants'] ?? []));

        $searchDataText = strtolower(
            ($batch['product_name'] ?? '') . ' ' . 
            ($batch['category_name'] ?? '') . ' ' . 
            ($batch['program_number'] ?? '') . ' ' . 
            ($batch['inspector_name'] ?? '') . ' ' . 
            $batch['qc_batch_id'] . ' ' . 
            $variantKeywords
        );
        $stockStatus = $batch['stock_status'] ?? 'IN_STOCK';
    ?>
    <div class="col-xl-4 col-md-6 batch-card-col" 
         data-batch-id="<?= $batch['qc_batch_id'] ?>"
         data-category-id="<?= $batch['category_id'] ?? '' ?>"
         data-search-text="<?= e($searchDataText) ?>">
        <?php $statusColor = in_array($stockStatus, ['FULLY_OUTWARDED', 'NO_PASSED_STOCK'], true) ? 'secondary' : ($stockStatus === 'PARTIALLY_OUTWARDED' ? 'warning' : 'success'); ?>
        <div class="card border-0 shadow-sm h-100 inventory-batch-card" style="border-left: 4px solid var(--bs-<?= $statusColor ?>) !important; transition: transform 0.18s, box-shadow 0.18s;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between mb-3 batch-list-header">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if (($batch['tab_type'] ?? '') === 'third_party'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 fw-bold text-nowrap">
                                    <i class="bi bi-box-seam me-1"></i>Third-Party #<?= e($batch['qc_batch_id']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold text-nowrap">
                                    <i class="bi bi-patch-check-fill me-1"></i>QC Batch #<?= $batch['qc_batch_id'] ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border small"><?= e(str_replace('_', ' ', $stockStatus)) ?></span>
                        </div>
                        <small class="text-muted"><?= $batchDate->format('d M Y, g:i A') ?></small>
                    </div>
                </div>

                <div class="mb-3 batch-list-product">
                    <h6 class="fw-bold text-dark mb-1"><?= e($batch['product_name']) ?></h6>
                    <div class="small text-muted"><?= e($batch['program_number']) ?> · <?= e($batch['category_name']) ?></div>
                </div>

                <div class="row g-2 mb-3 text-center batch-list-stats">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <span class="small text-muted d-block">QC Inward</span>
                            <span class="fw-bold text-dark"><?= number_format($batch['total_pieces']) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success bg-opacity-10 rounded border border-success-subtle">
                            <span class="small text-success d-block">In Stock</span>
                            <span class="fw-bold text-success"><?= number_format($batch['remaining_pieces']) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-danger bg-opacity-10 rounded border border-danger-subtle">
                            <span class="small text-danger d-block">Outward</span>
                            <span class="fw-bold text-danger"><?= number_format($batch['total_outward_pieces']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-muted fw-semibold">Available Valuation:</span>
                    <span class="fw-bold text-primary"><?= formatCurrency((float)($batch['remaining_value'] ?? 0)) ?></span>
                </div>

                <div class="d-flex gap-1.5 batch-list-actions flex-wrap">
                    <?php if (!empty($batch['is_inward'])): ?>
                        <a href="<?= url('production/inventory/batch/INW-' . ($batch['production_batch_id'] ?? $batch['qc_batch_id'])) ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="<?= url('production/inventory/batch/INW-' . ($batch['production_batch_id'] ?? $batch['qc_batch_id']) . '#qrSection') ?>" class="btn btn-outline-info btn-sm fw-semibold">
                            <i class="bi bi-qr-code me-1"></i> QR
                        </a>
                    <?php elseif (!empty($batch['is_supply'])): ?>
                        <a href="<?= url('production/inventory/supply/' . $batch['supply_item_id']) ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="<?= url('production/inventory/supply/' . $batch['supply_item_id'] . '#qrSection') ?>" class="btn btn-outline-info btn-sm fw-semibold">
                            <i class="bi bi-qr-code me-1"></i> QR
                        </a>
                    <?php else: ?>
                        <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id']) ?>" class="btn btn-outline-secondary btn-sm fw-semibold">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                        <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id'] . '#qrSection') ?>" class="btn btn-outline-info btn-sm fw-semibold">
                            <i class="bi bi-qr-code me-1"></i> QR
                        </a>
                        <?php if (hasPermission('batches', 'create') && $batch['remaining_pieces'] > 0): ?>
                        <a href="<?= url('production/batches/create?qc_batch_id=' . $batch['qc_batch_id']) ?>" class="btn btn-outline-success btn-sm fw-semibold">
                            <i class="bi bi-box-seam me-1"></i> Pack
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination Card Footer -->
<div class="card border-0 shadow-sm mt-4" id="batchPaginationContainer">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="batchPageInfo">
                Showing 1 to <?= min(25, count($batches)) ?> of <?= count($batches) ?> batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label for="batchPageSizeSelect" class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="batchPageSizeSelect" class="form-select form-select-sm" style="width: auto; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; color: #334155; padding-left: 0.75rem; padding-right: 2.25rem;" onchange="changeBatchPageSize(this.value)">
                    <option value="10">10 per page</option>
                    <option value="25" selected>25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="all">All items</option>
                </select>
            </div>
        </div>
        <nav aria-label="Batch Pagination">
            <ul class="pagination pagination-modern mb-0" id="batchPaginationList"></ul>
        </nav>
    </div>
</div>
<?php endif; ?>

<!-- ========================================== -->
<!-- MODAL: Edit Product Price (Images 2 & 3)   -->
<!-- ========================================== -->
<div class="modal fade" id="editProductPriceModal" tabindex="-1" aria-labelledby="editProductPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white py-3 px-4 border-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="editProductPriceModalLabel">
                        <i class="bi bi-tag-fill me-2"></i>Edit Product Price
                    </h5>
                    <small class="opacity-75 font-monospace" id="modalPriceHeaderSku">SKU: —</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Product Summary Box (Image 3) -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <h6 class="fw-bold text-dark mb-1" id="modalPriceProductName">—</h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                        <span class="badge bg-dark font-monospace" id="modalPriceBadgeSku">—</span>
                        <span class="badge bg-light text-dark border font-monospace" id="modalPriceBadgeCost">Cost: ₹0.00</span>
                    </div>
                </div>

                <form id="editProductPriceForm" onsubmit="submitProductPriceEdit(event)">
                    <input type="hidden" id="modalVariantIdsInput" name="variant_ids" value="">

                    <!-- Price Inputs (Image 3) -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="modalMrpInput" class="form-label small fw-bold text-uppercase text-muted">
                                MAXIMUM RETAIL PRICE (MRP ₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-lg fw-bold" id="modalMrpInput" name="mrp" required oninput="calculatePriceMargins()">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Printed sticker / catalog MRP</small>
                        </div>
                        <div class="col-sm-6">
                            <label for="modalSellingPriceInput" class="form-label small fw-bold text-uppercase text-muted">
                                DISCOUNT PRICE (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-success">₹</span>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-lg fw-bold text-success" id="modalSellingPriceInput" name="selling_price" required oninput="calculatePriceMargins()">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Active retail selling rate at POS</small>
                        </div>
                    </div>

                    <div id="modalPriceValidationMessage" class="alert alert-danger py-2 px-3 small mb-3" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> MRP must be greater than or equal to the Discount selling price.
                    </div>

                    <!-- Price Dynamics & Margin Box (Image 3) -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px;">PRICE DYNAMICS &amp; MARGIN</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" id="modalDiscountBadge">0% Discount</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Markup over Cost: <strong id="modalMarkupVal" class="text-success ms-1 font-monospace">+0%</strong></span>
                            <span class="text-muted">Discount on MRP: <strong id="modalDiscountVal" class="text-primary ms-1 font-monospace">0% Off</strong></span>
                        </div>
                    </div>

                    <!-- Safeguard Checkbox: Old QR Code Price Protection (Requested Feature) -->
                    <div class="card border-warning-subtle bg-warning bg-opacity-10 mb-3">
                        <div class="card-body p-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="modalDontAffectOldQr" name="dont_affect_old_qr" value="1" checked>
                                <label class="form-check-label fw-bold text-dark small" for="modalDontAffectOldQr">
                                    <i class="bi bi-shield-lock-fill me-1 text-warning"></i> Don't affect old price on existing printed QR codes
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1 ms-4" style="font-size: 0.76rem;">
                                If enabled, already tagged/printed QR stickers keep their original stamped price. When scanned at POS, they will retain their printed price. If disabled, scanning the old QR automatically bills at the new price.
                            </small>
                        </div>
                    </div>

                    <!-- Stock Reduction Option (Requested Feature) -->
                    <div class="card border-primary-subtle bg-primary bg-opacity-10 mb-3">
                        <div class="card-body p-3">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="modalEnableStockReduction" onchange="toggleStockReductionFields(this.checked)">
                                <label class="form-check-label fw-bold text-dark small" for="modalEnableStockReduction">
                                    <i class="bi bi-dash-circle-fill me-1 text-danger"></i> Reduce In-Stock Quantity (Optional Stock Adjustment)
                                </label>
                            </div>
                            <div id="modalStockReductionContainer" style="display: none;" class="mt-2 pt-2 border-top">
                                <div class="row g-2">
                                    <div class="col-sm-5">
                                        <label for="modalReduceQtyInput" class="form-label small fw-semibold text-muted mb-1">Deduct Qty (Pcs)</label>
                                        <input type="number" step="1" min="0" class="form-control form-control-sm" id="modalReduceQtyInput" name="reduce_stock_qty" value="0" placeholder="0">
                                        <small class="text-muted d-block mt-0.5" id="modalCurrentStockNotice" style="font-size: 0.72rem;">Available: 0 pcs</small>
                                    </div>
                                    <div class="col-sm-7">
                                        <label for="modalReduceReasonSelect" class="form-label small fw-semibold text-muted mb-1">Reduction Reason</label>
                                        <select class="form-select form-select-sm" id="modalReduceReasonSelect" name="reduce_stock_reason">
                                            <option value="Damaged / Quarantined during price revision">Damaged / Quarantined</option>
                                            <option value="Promotional Sample / Giveaway">Promotional Sample</option>
                                            <option value="Inventory Audit Count Correction">Inventory Audit Correction</option>
                                            <option value="Defective / Discarded">Defective / Discarded</option>
                                            <option value="Manual Quantity Adjustment">Manual Adjustment</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revision Notes -->
                    <div class="mb-3">
                        <label for="modalRevisionNotesInput" class="form-label small fw-bold text-uppercase text-muted">REVISION NOTES / REASON (OPTIONAL)</label>
                        <input type="text" class="form-control" id="modalRevisionNotesInput" name="revision_notes" placeholder="e.g. Bulk price revision, seasonal promotion">
                    </div>

                    <!-- Footer Buttons -->
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-light px-4 border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" id="btnApplyPriceChanges">
                            <i class="bi bi-check-circle-fill me-1"></i> Apply Price Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.inventory-batch-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
}
#inventoryBatchList table {
    min-width: 1050px;
}
.batch-list-row > td {
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
}
.batch-list-row.row-selected {
    background-color: rgba(25, 135, 84, 0.08) !important;
}
</style>

<script>
let currentBatchPage = 1;
let batchPageSize = 25;
let visibleBatchCards = [];
let currentBatchView = 'list';
let isPriceEditMode = false;
let currentPriceModalInstance = null;
let currentModalCostPrice = 0;

// Price Edit Mode Toggle (Image 2)
function togglePriceEditMode() {
    isPriceEditMode = !isPriceEditMode;
    const btn = document.querySelector('.btnTogglePriceEdit');
    const label = document.querySelector('.btnPriceEditLabel');
    const cols = document.querySelectorAll('.price-checkbox-col');
    const floatingBar = document.getElementById('priceFloatingActionBar');

    if (isPriceEditMode) {
        if (btn) {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
        }
        if (label) label.textContent = 'Close Price Edit';
        cols.forEach(c => c.style.display = '');
    } else {
        if (btn) {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
        }
        if (label) label.textContent = 'Edit Price';
        cols.forEach(c => c.style.display = 'none');
        clearPriceSelection();
        if (floatingBar) floatingBar.style.setProperty('display', 'none', 'important');
    }
}

function updateSelectedPriceCount() {
    const checked = document.querySelectorAll('.price-item-checkbox:checked');
    const count = checked.length;

    // Update row highlighting
    document.querySelectorAll('.batch-list-row').forEach(tr => {
        const chk = tr.querySelector('.price-item-checkbox');
        if (chk && chk.checked) {
            tr.classList.add('row-selected');
        } else {
            tr.classList.remove('row-selected');
        }
    });

    // Update header button
    const headerBtn = document.querySelector('.btnHeaderApplyPrice');
    const headerCount = document.querySelector('.headerSelectedPriceCount');
    if (headerBtn && headerCount) {
        headerCount.textContent = count;
        if (count > 0 && isPriceEditMode) {
            headerBtn.classList.remove('d-none');
        } else {
            headerBtn.classList.add('d-none');
        }
    }

    // Update floating bottom action bar (Image 2)
    const floatingBar = document.getElementById('priceFloatingActionBar');
    const floatingCount = document.getElementById('floatingSelectedCount');
    if (floatingBar && floatingCount) {
        floatingCount.textContent = count;
        if (count > 0 && isPriceEditMode) {
            floatingBar.style.removeProperty('display');
            floatingBar.style.display = 'flex';
        } else {
            floatingBar.style.setProperty('display', 'none', 'important');
        }
    }

    // Update master checkbox
    const allCheckboxes = document.querySelectorAll('.price-item-checkbox');
    const master = document.getElementById('thSelectAllPrice');
    if (master && allCheckboxes.length > 0) {
        master.checked = (checked.length === allCheckboxes.length);
        master.indeterminate = (checked.length > 0 && checked.length < allCheckboxes.length);
    }
}

function toggleSelectAllPrice(master) {
    const checked = master.checked;
    document.querySelectorAll('.price-item-checkbox').forEach(chk => {
        const row = chk.closest('tr');
        if (!row || row.style.display !== 'none') {
            chk.checked = checked;
        }
    });
    updateSelectedPriceCount();
}

function selectAllPriceItems(state) {
    document.querySelectorAll('.price-item-checkbox').forEach(chk => {
        const row = chk.closest('tr');
        if (!row || row.style.display !== 'none') {
            chk.checked = state;
        }
    });
    updateSelectedPriceCount();
}

function clearPriceSelection() {
    document.querySelectorAll('.price-item-checkbox').forEach(chk => {
        chk.checked = false;
    });
    const master = document.getElementById('thSelectAllPrice');
    if (master) {
        master.checked = false;
        master.indeterminate = false;
    }
    updateSelectedPriceCount();
}

// Open modal for a single product (Image 3)
function openEditPriceModal(data) {
    if (!data) return;
    const modalEl = document.getElementById('editProductPriceModal');
    if (!modalEl) return;

    document.getElementById('modalVariantIdsInput').value = data.variant_id || '';
    document.getElementById('modalPriceHeaderSku').textContent = 'SKU: ' + (data.sku || '—');
    document.getElementById('modalPriceProductName').textContent = data.product_name || 'Product';
    document.getElementById('modalPriceBadgeSku').textContent = data.sku || 'SKU';
    
    currentModalCostPrice = parseFloat(data.cost_price || 0);
    document.getElementById('modalPriceBadgeCost').textContent = 'Cost: ₹' + currentModalCostPrice.toFixed(2);

    const mrp = parseFloat(data.mrp || 0);
    const sellingPrice = parseFloat(data.selling_price || 0);
    document.getElementById('modalMrpInput').value = mrp > 0 ? mrp.toFixed(2) : '';
    document.getElementById('modalSellingPriceInput').value = sellingPrice > 0 ? sellingPrice.toFixed(2) : '';
    document.getElementById('modalRevisionNotesInput').value = '';

    // Stock reduction
    const stockQty = parseInt(data.remaining_qty || 0, 10);
    const noticeEl = document.getElementById('modalCurrentStockNotice');
    if (noticeEl) noticeEl.textContent = 'Available in stock: ' + stockQty + ' pcs';
    const reduceInput = document.getElementById('modalReduceQtyInput');
    if (reduceInput) {
        reduceInput.max = stockQty;
        reduceInput.value = '0';
    }
    const reduceCheck = document.getElementById('modalEnableStockReduction');
    if (reduceCheck) {
        reduceCheck.checked = false;
        toggleStockReductionFields(false);
    }

    calculatePriceMargins();

    if (!currentPriceModalInstance) {
        currentPriceModalInstance = new bootstrap.Modal(modalEl);
    }
    currentPriceModalInstance.show();
}

// Open modal for multiple selected products
function openBulkPriceModal() {
    const checked = Array.from(document.querySelectorAll('.price-item-checkbox:checked'));
    if (!checked.length) return;

    const modalEl = document.getElementById('editProductPriceModal');
    if (!modalEl) return;

    const variantIds = checked.map(c => c.value);
    document.getElementById('modalVariantIdsInput').value = variantIds.join(',');

    const first = checked[0];
    const count = checked.length;
    document.getElementById('modalPriceHeaderSku').textContent = count > 1 
        ? `${count} Products Selected` 
        : ('SKU: ' + (first.getAttribute('data-sku') || '—'));
    document.getElementById('modalPriceProductName').textContent = count > 1 
        ? `Bulk Price Update (${count} Selected Items)` 
        : (first.getAttribute('data-product-name') || 'Product');
    document.getElementById('modalPriceBadgeSku').textContent = count > 1 
        ? `${count} Items` 
        : (first.getAttribute('data-sku') || 'SKU');

    currentModalCostPrice = parseFloat(first.getAttribute('data-cost-price') || 0);
    document.getElementById('modalPriceBadgeCost').textContent = 'Cost: ₹' + currentModalCostPrice.toFixed(2);

    const mrp = parseFloat(first.getAttribute('data-mrp') || 0);
    const sellingPrice = parseFloat(first.getAttribute('data-selling-price') || 0);
    document.getElementById('modalMrpInput').value = mrp > 0 ? mrp.toFixed(2) : '';
    document.getElementById('modalSellingPriceInput').value = sellingPrice > 0 ? sellingPrice.toFixed(2) : '';
    document.getElementById('modalRevisionNotesInput').value = '';

    const totalStock = checked.reduce((acc, c) => acc + parseInt(c.getAttribute('data-stock') || 0, 10), 0);
    const noticeEl = document.getElementById('modalCurrentStockNotice');
    if (noticeEl) noticeEl.textContent = 'Total selected in stock: ' + totalStock + ' pcs';
    const reduceInput = document.getElementById('modalReduceQtyInput');
    if (reduceInput) {
        reduceInput.max = totalStock;
        reduceInput.value = '0';
    }
    const reduceCheck = document.getElementById('modalEnableStockReduction');
    if (reduceCheck) {
        reduceCheck.checked = false;
        toggleStockReductionFields(false);
    }

    calculatePriceMargins();

    if (!currentPriceModalInstance) {
        currentPriceModalInstance = new bootstrap.Modal(modalEl);
    }
    currentPriceModalInstance.show();
}

function toggleStockReductionFields(checked) {
    const container = document.getElementById('modalStockReductionContainer');
    if (container) {
        container.style.display = checked ? 'block' : 'none';
    }
}

// Live calculation of markup and discount percentage (Image 3)
function calculatePriceMargins() {
    const mrpInput = document.getElementById('modalMrpInput');
    const sellingInput = document.getElementById('modalSellingPriceInput');
    const mrp = parseFloat(mrpInput.value || 0);
    const selling = parseFloat(sellingInput.value || 0);
    const cost = currentModalCostPrice;

    const validationMsg = document.getElementById('modalPriceValidationMessage');
    const isInvalid = selling > 0 && mrp > 0 && mrp < selling;
    if (validationMsg) {
        validationMsg.style.display = isInvalid ? 'block' : 'none';
    }

    // Markup over Cost
    const markupEl = document.getElementById('modalMarkupVal');
    if (markupEl) {
        if (cost > 0 && selling > 0) {
            const markup = ((selling - cost) / cost) * 100;
            const sign = markup >= 0 ? '+' : '';
            markupEl.textContent = sign + Math.round(markup) + '%';
            markupEl.className = (markup >= 0 ? 'text-success' : 'text-danger') + ' ms-1 font-monospace';
        } else {
            markupEl.textContent = '+0%';
            markupEl.className = 'text-success ms-1 font-monospace';
        }
    }

    // Discount on MRP
    const discountValEl = document.getElementById('modalDiscountVal');
    const discountBadgeEl = document.getElementById('modalDiscountBadge');
    if (mrp > 0 && mrp >= selling && selling > 0) {
        const disc = ((mrp - selling) / mrp) * 100;
        const discRound = Math.round(disc);
        if (discountValEl) discountValEl.textContent = discRound + '% Off';
        if (discountBadgeEl) discountBadgeEl.textContent = discRound + '% Discount';
    } else {
        if (discountValEl) discountValEl.textContent = '0% Off';
        if (discountBadgeEl) discountBadgeEl.textContent = '0% Discount';
    }
}

// Submit AJAX Price Update
function submitProductPriceEdit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnApplyPriceChanges');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
    }

    const form = document.getElementById('editProductPriceForm');
    const formData = new FormData(form);

    fetch('<?= url('production/inventory/update-price') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Apply Price Changes';
        }

        if (data.success) {
            // Update table rows in place
            if (data.updated_variants && Array.isArray(data.updated_variants)) {
                data.updated_variants.forEach(uv => {
                    const row = document.getElementById('stock-row-' + uv.variant_id);
                    if (row) {
                        const mrpEl = row.querySelector('.row-mrp-val');
                        if (mrpEl) mrpEl.textContent = uv.formatted_mrp || ('₹' + parseFloat(uv.new_mrp).toFixed(2));

                        const sellEl = row.querySelector('.row-selling-val');
                        if (sellEl) sellEl.textContent = uv.formatted_selling_price || ('₹' + parseFloat(uv.new_selling_price).toFixed(2));

                        const stockEl = row.querySelector('.row-stock-val');
                        let remQty = parseInt(row.getAttribute('data-stock') || 0, 10);
                        if (uv.stock_reduced > 0) {
                            remQty = Math.max(0, remQty - parseInt(uv.stock_reduced, 10));
                            if (stockEl) stockEl.textContent = remQty + ' pcs';
                            row.setAttribute('data-stock', remQty);
                        }

                        const valEl = row.querySelector('.row-val-val');
                        if (valEl) {
                            const totalVal = remQty * parseFloat(uv.new_selling_price);
                            valEl.textContent = '₹' + totalVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }

                        // Update checkbox dataset
                        const chk = row.querySelector('.price-item-checkbox');
                        if (chk) {
                            chk.setAttribute('data-mrp', uv.new_mrp);
                            chk.setAttribute('data-selling-price', uv.new_selling_price);
                            chk.setAttribute('data-stock', remQty);
                        }
                    }
                });
            }

            clearPriceSelection();
            if (currentPriceModalInstance) {
                currentPriceModalInstance.hide();
            }

            // Show Toast Alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow-lg z-3';
            alertDiv.style.maxWidth = '420px';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-4 me-2 text-success"></i>
                    <div>
                        <strong>Price Updated!</strong>
                        <div class="small">${data.message || 'Product prices and stock updated successfully.'}</div>
                        ${data.dont_affect_old_qr ? '<div class="small text-muted mt-1"><i class="bi bi-shield-check me-1"></i>Existing printed QR stickers will retain their original price.</div>' : ''}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 250);
                }
            }, 5000);
        } else {
            alert(data.message || 'Failed to update prices. Please try again.');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Apply Price Changes';
        }
        alert('An error occurred while saving price changes: ' + err.message);
    });
}

// Row click to toggle checkbox in Price Edit mode
document.addEventListener('click', function(e) {
    if (!isPriceEditMode) return;
    const tr = e.target.closest('tr[id^="stock-row-"]');
    if (!tr) return;
    if (e.target.closest('a, button, input[type="checkbox"], label')) return;
    const chk = tr.querySelector('.price-item-checkbox');
    if (chk) {
        chk.checked = !chk.checked;
        updateSelectedPriceCount();
    }
});

function switchBatchView(mode) {
    const grid = document.getElementById('inventoryBatchesGrid');
    const table = document.getElementById('inventoryBatchList');
    const cardsButton = document.getElementById('batchCardsViewButton');
    const listButton = document.getElementById('batchListViewButton');
    const showList = mode === 'list';
    currentBatchView = mode;

    if (grid) {
        grid.classList.toggle('inventory-batch-list-mode', showList);
        grid.style.display = showList ? 'none' : '';
    }
    if (table) table.style.display = showList ? '' : 'none';
    if (cardsButton) {
        cardsButton.classList.toggle('btn-primary', !showList);
        cardsButton.classList.toggle('active', !showList);
        cardsButton.classList.toggle('btn-outline-secondary', showList);
    }
    if (listButton) {
        listButton.classList.toggle('btn-primary', showList);
        listButton.classList.toggle('active', showList);
        listButton.classList.toggle('btn-outline-secondary', !showList);
    }
    currentBatchPage = 1;
    renderBatchPagination();
}

function clearSearchInput() {
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        searchInput.value = '';
        document.getElementById('inventoryFilterForm').submit();
    }
}

function clearLiveFilter() {
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        searchInput.value = '';
        filterBatchesLive('');
    }
}

function filterBatchesLive(query) {
    const term = (query || '').trim().toLowerCase();
    const allCards = Array.from(document.querySelectorAll('.batch-card-col'));
    const batchRows = Array.from(document.querySelectorAll('.batch-list-row'));
    const alertEl = document.getElementById('noMatchingBatchesAlert');
    const paginationContainer = document.getElementById('batchPaginationContainer');
    const badgeEl = document.getElementById('totalBatchBadgeCount');

    visibleBatchCards = allCards.filter(card => {
        if (!term) return true;
        const searchText = (card.getAttribute('data-search-text') || '').toLowerCase();
        return searchText.includes(term);
    });
    batchRows.forEach(row => {
        const searchText = (row.getAttribute('data-search-text') || '').toLowerCase();
        row.dataset.filterMatch = String(!term || searchText.includes(term));
    });

    if (badgeEl) {
        const matchingProducts = batchRows.filter(row => row.dataset.filterMatch === 'true').length;
        badgeEl.textContent = `${matchingProducts} Products`;
    }

    if (visibleBatchCards.length === 0 && allCards.length > 0) {
        if (alertEl) alertEl.style.display = 'block';
        if (paginationContainer) paginationContainer.style.display = 'none';
        allCards.forEach(c => c.style.display = 'none');
        batchRows.forEach(row => row.style.display = 'none');
        return;
    }

    if (alertEl) alertEl.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = '';

    currentBatchPage = 1;
    renderBatchPagination();
}

function changeBatchPageSize(newSize) {
    batchPageSize = (newSize === 'all') ? 'all' : parseInt(newSize, 10);
    currentBatchPage = 1;
    renderBatchPagination();
}

function goToBatchPage(page) {
    currentBatchPage = page;
    renderBatchPagination();
    const activeList = currentBatchView === 'list'
        ? document.getElementById('inventoryBatchList')
        : document.getElementById('inventoryBatchesGrid');
    if (activeList) activeList.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderBatchPagination() {
    const allCards = Array.from(document.querySelectorAll('.batch-card-col'));
    const batchRows = Array.from(document.querySelectorAll('.batch-list-row'));
    if (allCards.length === 0 && batchRows.length === 0) return;

    const productsToPaginate = batchRows.filter(row => row.dataset.filterMatch !== 'false');
    const totalEntries = productsToPaginate.length;
    const isAll = (batchPageSize === 'all');
    const effectiveSize = isAll ? totalEntries : parseInt(batchPageSize, 10);
    const totalPages = isAll ? 1 : Math.ceil(totalEntries / effectiveSize) || 1;

    if (currentBatchPage > totalPages) currentBatchPage = totalPages;
    if (currentBatchPage < 1) currentBatchPage = 1;

    const startIdx = isAll ? 0 : (currentBatchPage - 1) * effectiveSize;
    const endIdx = isAll ? totalEntries : Math.min(startIdx + effectiveSize, totalEntries);

    allCards.forEach(card => card.style.display = 'none');
    batchRows.forEach(row => row.style.display = 'none');

    productsToPaginate.forEach((entry, idx) => {
        const isOnPage = idx >= startIdx && idx < endIdx;
        entry.style.display = isOnPage ? '' : 'none';
    });

    const infoEl = document.getElementById('batchPageInfo');
    if (infoEl) {
        if (totalEntries === 0) {
            infoEl.innerHTML = `Showing <strong>0</strong> products`;
        } else {
            infoEl.innerHTML = `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalEntries}</strong> products`;
        }
    }

    const paginationList = document.getElementById('batchPaginationList');
    if (!paginationList) return;

    if (totalPages <= 1) {
        paginationList.innerHTML = `
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left me-1"></i> Prev</span></li>
            <li class="page-item active"><span class="page-link">1</span></li>
            <li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right ms-1"></i></span></li>
        `;
        return;
    }

    let html = '';
    const prevDisabled = (currentBatchPage <= 1) ? 'disabled' : '';
    html += `<li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentBatchPage > 1 ? `goToBatchPage(${currentBatchPage - 1})` : 'return false;'}" aria-label="Previous">
            <i class="bi bi-chevron-left me-1"></i> Prev
        </a>
    </li>`;

    for (let p = 1; p <= totalPages; p++) {
        const isActive = (p === currentBatchPage) ? 'active' : '';
        html += `<li class="page-item ${isActive}">
            <a class="page-link" href="javascript:void(0)" onclick="goToBatchPage(${p})">${p}</a>
        </li>`;
    }

    const nextDisabled = (currentBatchPage >= totalPages) ? 'disabled' : '';
    html += `<li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentBatchPage < totalPages ? `goToBatchPage(${currentBatchPage + 1})` : 'return false;'}" aria-label="Next">
            Next <i class="bi bi-chevron-right ms-1"></i>
        </a>
    </li>`;

    paginationList.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    switchBatchView('list');
    renderBatchPagination();

    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterBatchesLive(e.target.value);
            }, 150);
        });
    }
});
</script>
