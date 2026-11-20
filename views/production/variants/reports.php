<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Variant Stock &amp; Sales Reports</h2>
        <p class="text-muted small mb-0">Multi-dimensional inventory analysis by Category, Subcategory, Catalog, Variant, Color, Design, Size, and Batches.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Variants Roster
        </a>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="reportTabs">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'variant_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=variant_stock') ?>">
            <i class="bi bi-tag me-1"></i> Variant Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'category_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=category_stock') ?>">
            <i class="bi bi-folder me-1"></i> Category Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'subcategory_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=subcategory_stock') ?>">
            <i class="bi bi-folder2-open me-1"></i> Subcategory Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'catalog_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=catalog_stock') ?>">
            <i class="bi bi-journal-bookmark me-1"></i> Catalog Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'color_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=color_stock') ?>">
            <i class="bi bi-palette me-1"></i> Color Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'design_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=design_stock') ?>">
            <i class="bi bi-brush me-1"></i> Design Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'size_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=size_stock') ?>">
            <i class="bi bi-aspect-ratio me-1"></i> Size Stock
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'variant_sales' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=variant_sales') ?>">
            <i class="bi bi-graph-up-arrow me-1"></i> Variant Sales
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'category_sales' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=category_sales') ?>">
            <i class="bi bi-pie-chart me-1"></i> Category Sales
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'batch_stock' ? 'active' : '' ?>" href="<?= url('production/variants/reports?tab=batch_stock') ?>">
            <i class="bi bi-boxes me-1"></i> Batch Stock &amp; Traceability
        </a>
    </li>
</ul>

<!-- Tab 1: Variant-wise Stock -->
<?php if ($activeTab === 'variant_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-tag-fill me-2 text-primary"></i>Variant-Wise Available Stock</h5>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Variant ID</th>
                        <th>Parent Catalog / SKU</th>
                        <th>Variant SKU</th>
                        <th>Attributes (Color · Design · Size)</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-center">Available Stock</th>
                        <th class="text-end pe-3">Stock Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variantStock)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No stock data available.</td></tr>
                    <?php else: ?>
                    <?php foreach ($variantStock as $r): ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-dark-subtle text-dark font-monospace"><?= e($r['variant_code']) ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($r['product_name']) ?></div>
                            <small class="text-muted font-monospace"><?= e($r['parent_sku']) ?></small>
                        </td>
                        <td><span class="font-monospace fw-bold text-primary"><?= e($r['sku']) ?></span></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($r['color_name'] ?? '—') ?></span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle"><?= e($r['design_name'] ?? '—') ?></span>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><?= e($r['size_name'] ?? '—') ?></span>
                        </td>
                        <td class="text-end">₹<?= number_format((float)$r['selling_price'], 2) ?></td>
                        <td class="text-center fw-bold <?= (float)$r['available_stock'] > 0 ? 'text-success' : 'text-muted' ?>">
                            <?= number_format((float)$r['available_stock']) ?> pcs
                        </td>
                        <td class="text-end pe-3 fw-bold text-dark">
                            ₹<?= number_format((float)$r['stock_valuation'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 2: Category-wise Stock -->
<?php if ($activeTab === 'category_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-folder-fill me-2 text-primary"></i>Category-Wise Stock Summary</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Category Name</th>
                        <th>Category Code</th>
                        <th class="text-center">Catalogs</th>
                        <th class="text-center">Active Variants</th>
                        <th class="text-center">Total Available Stock</th>
                        <th class="text-end pe-3">Total Inventory Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStock as $cs): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($cs['name']) ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($cs['code'] ?? '—') ?></span></td>
                        <td class="text-center"><?= number_format($cs['catalog_count']) ?></td>
                        <td class="text-center"><?= number_format($cs['variant_count']) ?></td>
                        <td class="text-center fw-bold text-success fs-6"><?= number_format($cs['total_stock']) ?> pcs</td>
                        <td class="text-end pe-3 fw-bold text-dark">₹<?= number_format((float)$cs['stock_valuation'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 3: Subcategory-wise Stock -->
<?php if ($activeTab === 'subcategory_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-folder2-open me-2 text-primary"></i>Subcategory-Wise Stock</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Subcategory Name</th>
                        <th>Parent Category</th>
                        <th class="text-center">Catalogs</th>
                        <th class="text-center">Variants</th>
                        <th class="text-end pe-3">Available Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subcategoryStock as $sc): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($sc['name']) ?></td>
                        <td><?= e($sc['category_name']) ?></td>
                        <td class="text-center"><?= number_format($sc['catalog_count']) ?></td>
                        <td class="text-center"><?= number_format($sc['variant_count']) ?></td>
                        <td class="text-end pe-3 fw-bold text-success fs-6"><?= number_format($sc['total_stock']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 4: Catalog-wise Stock -->
<?php if ($activeTab === 'catalog_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>Catalog / Style-Wise Stock</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Catalog / Product Name</th>
                        <th>Parent SKU</th>
                        <th>Category</th>
                        <th class="text-center">Variants Count</th>
                        <th class="text-center">Total Stock</th>
                        <th class="text-end pe-3">Estimated Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalogStock as $cat): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($cat['name']) ?></td>
                        <td><span class="font-monospace fw-bold text-primary"><?= e($cat['parent_sku']) ?></span></td>
                        <td><?= e($cat['category_name'] ?? '—') ?></td>
                        <td class="text-center"><?= number_format($cat['variant_count']) ?></td>
                        <td class="text-center fw-bold text-success fs-6"><?= number_format($cat['total_stock']) ?> pcs</td>
                        <td class="text-end pe-3 fw-bold text-dark">₹<?= number_format((float)$cat['stock_valuation'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 5: Color-wise Stock -->
<?php if ($activeTab === 'color_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-palette-fill me-2 text-primary"></i>Color-Wise Inventory</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Color</th>
                        <th class="text-center">Variants Using Color</th>
                        <th class="text-end pe-3">Total In-Stock Pieces</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colorStock as $col): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <span class="d-inline-flex align-items-center gap-2">
                                <?php if (!empty($col['hex_code'])): ?>
                                <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?= e($col['hex_code']) ?>;border:1px solid #ccc;"></span>
                                <?php endif; ?>
                                <?= e($col['name']) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= number_format($col['variant_count']) ?></td>
                        <td class="text-end pe-3 fw-bold text-success fs-6"><?= number_format($col['total_stock']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 6: Design-wise Stock -->
<?php if ($activeTab === 'design_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-brush-fill me-2 text-primary"></i>Design / Pattern-Wise Stock</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Design / Pattern Name</th>
                        <th>Code</th>
                        <th class="text-center">Variants Using Design</th>
                        <th class="text-end pe-3">Total In-Stock Pieces</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($designStock as $d): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($d['name']) ?></td>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle"><?= e($d['code'] ?? '—') ?></span></td>
                        <td class="text-center"><?= number_format($d['variant_count']) ?></td>
                        <td class="text-end pe-3 fw-bold text-success fs-6"><?= number_format($d['total_stock']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 7: Size-wise Stock -->
<?php if ($activeTab === 'size_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-aspect-ratio-fill me-2 text-primary"></i>Size-Wise Inventory</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Size</th>
                        <th>Size Code</th>
                        <th class="text-center">Variants Using Size</th>
                        <th class="text-end pe-3">Total In-Stock Pieces</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sizeStock as $sz): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-6 px-3 py-1"><?= e($sz['name']) ?></span></td>
                        <td><span class="font-monospace text-muted"><?= e($sz['code'] ?? '—') ?></span></td>
                        <td class="text-center"><?= number_format($sz['variant_count']) ?></td>
                        <td class="text-end pe-3 fw-bold text-success fs-6"><?= number_format($sz['total_stock']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 8: Variant-wise Sales -->
<?php if ($activeTab === 'variant_sales'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Variant-Wise Sales History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Variant ID</th>
                        <th>Variant SKU</th>
                        <th>Parent Product</th>
                        <th>Attributes</th>
                        <th class="text-center">Total Quantity Billed</th>
                        <th class="text-end pe-3">Gross Sales Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variantSales)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No variant sales records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($variantSales as $vs): ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-dark-subtle text-dark font-monospace"><?= e($vs['variant_code']) ?></span></td>
                        <td><span class="font-monospace fw-bold text-primary"><?= e($vs['sku']) ?></span></td>
                        <td><?= e($vs['product_name']) ?></td>
                        <td>
                            <small class="text-muted"><?= e($vs['color_name'] ?? '—') ?> · <?= e($vs['design_name'] ?? '—') ?> · <?= e($vs['size_name'] ?? '—') ?></small>
                        </td>
                        <td class="text-center fw-bold fs-6 text-dark"><?= number_format((float)$vs['total_sold_qty']) ?> pcs</td>
                        <td class="text-end pe-3 fw-bold text-success fs-6">₹<?= number_format((float)$vs['total_sales_revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 9: Category-wise Sales -->
<?php if ($activeTab === 'category_sales'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Category-Wise Sales Revenue</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Category Name</th>
                        <th class="text-center">Total Pieces Sold</th>
                        <th class="text-end pe-3">Total Sales Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorySales)): ?>
                    <tr><td colspan="3" class="text-center py-4 text-muted">No sales records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($categorySales as $csales): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($csales['name']) ?></td>
                        <td class="text-center fw-bold text-dark fs-6"><?= number_format((float)$csales['total_sold_qty']) ?> pcs</td>
                        <td class="text-end pe-3 fw-bold text-success fs-6">₹<?= number_format((float)$csales['total_revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tab 10: Batch Stock & Traceability -->
<?php if ($activeTab === 'batch_stock'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-boxes me-2 text-primary"></i>Production Batch Traceability</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Batch Number</th>
                        <th>Program Reference</th>
                        <th>Facility / Unit</th>
                        <th class="text-center">Items Registered</th>
                        <th class="text-center">Total Quantity</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batchStockSales)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No batch records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($batchStockSales as $b): ?>
                    <tr>
                        <td class="ps-3 font-monospace fw-bold text-primary"><?= e($b['batch_number']) ?></td>
                        <td><?= e($b['program_number'] . ' — ' . $b['program_name']) ?></td>
                        <td><?= e($b['unit_name'] ?? 'Production Unit') ?></td>
                        <td class="text-center"><?= number_format($b['items_count']) ?></td>
                        <td class="text-center fw-bold text-dark"><?= number_format($b['batch_quantity']) ?> pcs</td>
                        <td class="text-center"><span class="badge bg-secondary"><?= e($b['status']) ?></span></td>
                        <td class="text-end pe-3 text-muted small"><?= e($b['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
