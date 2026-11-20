<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Purchased Stock Inventory</h2>
        <span class="text-muted small">Warehouse purchased inventory received after purchase mapping and QR code generations</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-qr-code-scan me-1"></i> QR Generations
        </a>
        <a href="<?= url('production/purchases/mapping') ?>" class="btn btn-outline-primary">
            <i class="bi bi-diagram-3 me-1"></i> Third-Party Mapping
        </a>
        <a href="<?= url('production/purchases') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-cart-check me-1"></i> Purchases
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Stock Batches</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($metrics['total_batches'] ?? 0)) ?></div>
                <div class="small text-muted"><?= number_format((float)($metrics['total_units'] ?? 0)) ?> QR units in stock</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Fabric Rolls in Stock</div>
                <div class="fs-3 fw-bold text-primary"><?= number_format((int)($metrics['fabric_count'] ?? 0)) ?></div>
                <div class="small text-muted">Stocked fabric rolls</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Raw Materials in Stock</div>
                <div class="fs-3 fw-bold text-warning"><?= number_format((int)($metrics['raw_count'] ?? 0)) ?></div>
                <div class="small text-muted">Stocked raw materials</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Third-Party Products</div>
                <div class="fs-3 fw-bold text-info"><?= number_format((int)($metrics['third_party_count'] ?? 0)) ?></div>
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
                        <i class="bi bi-layers-half me-1"></i>Fabric Rolls (<?= (int)($metrics['fabric_count'] ?? 0) ?>)
                    </a>
                    <a href="<?= url('production/purchased-stock?type=RAW_MATERIAL' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'RAW_MATERIAL') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-boxes me-1"></i>Raw Materials (<?= (int)($metrics['raw_count'] ?? 0) ?>)
                    </a>
                    <a href="<?= url('production/purchased-stock?type=THIRD_PARTY' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($typeFilter === 'THIRD_PARTY') ? 'btn-info text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-box-seam me-1"></i>Third-Party (<?= (int)($metrics['third_party_count'] ?? 0) ?>)
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
        <span class="badge bg-secondary-subtle text-secondary border"><?= count($stockItems) ?> Batches in Warehouse Stock</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
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
                    <th class="text-center" style="min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockItems)): ?>
                    <tr>
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
                        <tr>
                            <td class="text-muted"><?= $idx++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($item['item_name']) ?></div>
                                <div class="small text-muted">
                                    <?php if (!empty($item['roll_number'])): ?>
                                        <span class="badge bg-light text-dark border me-1"><?= e($item['roll_number']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['color_name'])): ?>
                                        <span class="me-1">Color: <?= e($item['color_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['size_name'])): ?>
                                        <span>Size: <?= e($item['size_name']) ?></span>
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
                            <td class="text-end">
                                <div class="fw-bold text-dark"><?= number_format((float)$item['purchase_qty'], 2) ?> <?= e($item['unit'] ?: 'PCS') ?></div>
                                <?php if ((float)($item['weight_kg'] ?? 0) > 0): ?>
                                    <div class="small text-muted"><?= number_format((float)$item['weight_kg'], 2) ?> kg</div>
                                <?php endif; ?>
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
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('production/qr-generations/view/' . $item['generation_id']) ?>" class="btn btn-outline-primary" title="View QR Batch">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    <a href="<?= url('production/qr-generations/print/' . $item['generation_id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print Labels">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="<?= url('purchases/view/' . $item['purchase_id']) ?>" class="btn btn-outline-info" title="View Purchase Bill">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
