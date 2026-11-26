<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-3 me-2 text-primary"></i>Third-Party Bill Creations</h2>
    </div>
    <a href="<?= url('purchases/mapping/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Mapping Invoice
    </a>
</div>

<ul class="nav nav-tabs mb-3" id="mappingPageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="mappingProductsTab" data-bs-toggle="tab" data-bs-target="#mappingProductsPane" type="button" role="tab" aria-controls="mappingProductsPane" aria-selected="true">
            <i class="bi bi-diagram-3 me-1"></i>Bill Creations
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="mappingFabricRollsTab" data-bs-toggle="tab" data-bs-target="#mappingFabricRollsPane" type="button" role="tab" aria-controls="mappingFabricRollsPane" aria-selected="false">
            <i class="bi bi-layers-half me-1"></i>Fabric Rolls
            <span class="badge bg-secondary ms-1"><?= count($fabricRollItems ?? []) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="mappingLogsTab" data-bs-toggle="tab" data-bs-target="#mappingLogsPane" type="button" role="tab" aria-controls="mappingLogsPane" aria-selected="false">
            <i class="bi bi-journal-text me-1"></i>Mapping Invoice Log
            <span class="badge bg-secondary ms-1"><?= count($mappingDrafts ?? []) ?></span>
        </button>
    </li>
</ul>
<div class="tab-content" id="mappingPageTabContent">
    <div class="tab-pane fade show active" id="mappingProductsPane" role="tabpanel" aria-labelledby="mappingProductsTab">

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Third-Party Products</div>
                    <div class="fs-3 fw-bold text-primary mt-1"><?= number_format((int)($metrics['total_items'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-box-seam text-primary me-1"></i><?= number_format((float)($metrics['total_quantity'] ?? 0)) ?> purchased units (pcs)</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Pending Unmapped</div>
                    <div class="fs-3 fw-bold text-warning-emphasis mt-1"><?= number_format((int)($metrics['unmapped_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-hourglass-split text-warning me-1"></i>Awaiting category &amp; attribute mapping</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Successfully Mapped</div>
                    <div class="fs-3 fw-bold text-success mt-1"><?= number_format((int)($metrics['mapped_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-check2-circle text-success me-1"></i>Configured in Third-Party Catalog</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2 flex-grow-1" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Mapping Completion</div>
                    <?php
                        $tot = (int)($metrics['total_items'] ?? 0);
                        $map = (int)($metrics['mapped_count'] ?? 0);
                        $pct = $tot > 0 ? round(($map / $tot) * 100, 1) : 100;
                    ?>
                    <div class="fs-3 fw-bold text-info-emphasis mt-1"><?= $pct ?>%</div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted mt-2 text-truncate">
                        QR batches: <?= number_format((int)($metrics['qr_count'] ?? 0)) ?> &nbsp;·&nbsp; GRNs: <?= number_format((int)($metrics['grn_count'] ?? 0)) ?>
                    </div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon ms-2">
                    <i class="bi bi-diagram-3 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="card border shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('purchases/mapping') ?>" class="row g-2 align-items-center">
            <div class="col-auto">
                <div class="btn-group" role="group">
                    <a href="<?= url('purchases/mapping?status=unmapped' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'unmapped') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-clock-history me-1"></i>Unmapped (<?= number_format((int)($metrics['unmapped_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=mapped' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'mapped') ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-check-circle me-1"></i>Mapped (<?= number_format((int)($metrics['mapped_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=complementary' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'complementary') ? 'btn-info text-dark fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-gift me-1"></i>Complementary (<?= number_format((int)($metrics['complementary_count'] ?? 0)) ?>)
                    </a>
                    <a href="<?= url('purchases/mapping?status=all' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                       class="btn btn-sm <?= ($status === 'all') ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                        All Third-Party (<?= number_format((int)($metrics['total_items'] ?? 0)) ?>)
                    </a>
                </div>
            </div>
            <div class="col-md-4 ms-auto">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search item, vendor SKU, purchase #, vendor..." value="<?= e($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    <?php if ($search || $categoryId > 0 || $status !== 'unmapped'): ?>
                        <a href="<?= url('purchases/mapping') ?>" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
    $billGroups = [];
    foreach ($items as $item) {
        $purchaseId = (int)$item['purchase_id'];
        if (!isset($billGroups[$purchaseId])) {
            $billGroups[$purchaseId] = [
                'purchase_id' => $purchaseId,
                'purchase_number' => $item['purchase_number'],
                'purchase_date' => $item['purchase_date'],
                'invoice_number' => $item['invoice_number'],
                'concern_id' => $item['concern_id'] ?? 1,
                'concern_name' => $item['concern_name'] ?? '',
                'concern_code' => $item['concern_code'] ?? '',
                'seller_name' => $item['seller_name'],
                'seller_company' => $item['seller_company'],
                'items' => [],
                'mapped_count' => 0,
                'qr_count' => 0,
                'grn_count' => 0,
                'total_quantity' => 0,
            ];
        }
        $billGroups[$purchaseId]['items'][] = $item;
        $billGroups[$purchaseId]['mapped_count'] += !empty($item['is_mapped']) ? 1 : 0;
        $billGroups[$purchaseId]['qr_count'] += !empty($item['qr_generation_id']) ? 1 : 0;
        $billGroups[$purchaseId]['grn_count'] += !empty($item['grn_id']) ? 1 : 0;
        $billGroups[$purchaseId]['total_quantity'] += (float)$item['quantity'];
    }
    $billGroups = array_values($billGroups);
?>

<!-- Purchase Bills Table -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Purchase Bills Roster</h5>
        <span class="badge bg-secondary-subtle text-secondary border"><?= count($billGroups) ?> Bills · <?= count($items) ?> Product Lines</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>#</th>
                    <th>Purchase Bill / Date</th>
                    <th>Vendor / Brand</th>
                    <th>Products on Bill</th>
                    <th class="text-end">Total Qty</th>
                    <th>Mapping Progress</th>
                    <th>QR / GRN Progress</th>
                    <th>Concern</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($billGroups)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No purchase items found matching your current filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($billGroups as $idx => $bill): ?>
                        <?php $lineCount = count($bill['items']); ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= url('purchases/view/' . $bill['purchase_id']) ?>" class="fw-bold text-decoration-none font-monospace">
                                    <?= e($bill['purchase_number']) ?>
                                </a>
                                <small class="text-muted d-block"><?= e(date('d M Y', strtotime($bill['purchase_date']))) ?></small>
                                <?php if (!empty($bill['invoice_number'])): ?>
                                    <small class="text-muted d-block">Inv: <?= e($bill['invoice_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($bill['seller_company'] ?: $bill['seller_name']) ?></strong>
                                <?php if (!empty($bill['seller_company'])): ?>
                                    <small class="text-muted d-block"><?= e($bill['seller_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-muted mb-1"><?= $lineCount ?> product<?= $lineCount === 1 ? '' : 's' ?></div>
                                <div class="d-flex flex-column gap-1">
                                    <?php foreach ($bill['items'] as $billItem): ?>
                                        <div class="text-dark">
                                            <strong><?= e($billItem['item_name']) ?></strong>
                                            <small class="text-muted">
                                                · <?= number_format((float)$billItem['quantity']) ?> <?= e($billItem['unit']) ?>
                                                <?php if (!empty($billItem['vendor_sku'])): ?> · SKU <?= e($billItem['vendor_sku']) ?><?php endif; ?>
                                            </small>
                                            <?php if (!empty($billItem['qr_generation_id']) && !empty($billItem['qr_batch_code'])): ?>
                                                <a href="<?= url('production/qr-generations/view/' . (int)$billItem['qr_generation_id']) ?>"
                                                   class="badge bg-dark-subtle text-dark border text-decoration-none ms-1">
                                                    <i class="bi bi-qr-code me-1"></i><?= e($billItem['qr_batch_code']) ?>
                                                </a>
                                            <?php elseif (!empty($billItem['is_mapped']) && empty($billItem['qr_generation_id']) && empty($billItem['qr_batch_code'])): ?>
                                                <a href="<?= url('production/qr-generations/create?tab=third_party&purchase_id=' . (int)$bill['purchase_id'] . '&purchase_item_id=' . (int)$billItem['id']) ?>"
                                                   class="badge bg-info-subtle text-info border text-decoration-none ms-1">
                                                    <i class="bi bi-qr-code-scan me-1"></i>Generate QR
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($billItem['grn_id']) && !empty($billItem['grn_number'])): ?>
                                                <a href="<?= url('production/inwards/view/' . (int)$billItem['grn_id']) ?>"
                                                   class="badge bg-success-subtle text-success border text-decoration-none ms-1">
                                                    <i class="bi bi-truck me-1"></i><?= e($billItem['grn_number']) ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($billItem['is_mapped'])): ?>
                                                <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 align-baseline text-decoration-none"
                                                        onclick="unmapItem(<?= (int)$billItem['id'] ?>, '<?= e(addslashes($billItem['item_name'])) ?>')"
                                                        title="Delete mapping (unmap product)">
                                                    <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="text-end fw-semibold"><?= number_format((float)$bill['total_quantity']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= (int)$bill['mapped_count'] ?> / <?= $lineCount ?> mapped</div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?= $lineCount > 0 ? round(($bill['mapped_count'] / $lineCount) * 100) : 0 ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div><i class="bi bi-qr-code me-1"></i><?= (int)$bill['qr_count'] ?> / <?= $lineCount ?> QR</div>
                                <div><i class="bi bi-truck me-1"></i><?= (int)$bill['grn_count'] ?> / <?= $lineCount ?> GRN</div>
                            </td>
                            <td><?= renderConcernBadge($bill) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1.5 align-items-center">
                                    <a href="<?= url('purchases/mapping/bill/' . $bill['purchase_id']) ?>"
                                       class="btn btn-sm <?= $bill['mapped_count'] === $lineCount ? 'btn-outline-primary' : 'btn-warning text-dark fw-bold' ?>">
                                        <i class="bi <?= $bill['mapped_count'] === $lineCount ? 'bi-pencil-square' : 'bi-diagram-3-fill' ?> me-1"></i>
                                        <?= $bill['mapped_count'] === $lineCount ? 'Edit Bill' : 'Map Bill' ?>
                                    </a>
                                    <?php if ($bill['mapped_count'] > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="unmapBill(<?= (int)$bill['purchase_id'] ?>, '<?= e(addslashes($bill['purchase_number'])) ?>')"
                                                title="Delete mapping and mark all items as unmapped">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
    <div class="tab-pane fade" id="mappingFabricRollsPane" role="tabpanel" aria-labelledby="mappingFabricRollsTab">
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll Purchases</h5>
                    <small class="text-muted">Fabric rolls use their own roll and QR workflow; they are not sent through the third-party catalog mapper.</small>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border"><?= count($fabricRollItems ?? []) ?> Roll Lines</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th>Purchase Bill / Date</th>
                            <th>Supplier</th>
                            <th>Fabric</th>
                            <th>Roll / Color / Design</th>
                            <th class="text-end">Quantity / Weight</th>
                            <th>QR / GRN</th>
                            <th>Concern</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fabricRollItems)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">No fabric-roll purchase lines found.</td></tr>
                        <?php else: foreach ($fabricRollItems as $roll): 
                            $isDraft = !empty($roll['is_mapping_draft']);
                            $rowKey = ($isDraft ? 'draft-' : 'item-') . (int)$roll['id'];
                        ?>
                            <tr id="fabric-roll-row-<?= $rowKey ?>">
                                <td>
                                    <?php if (!empty($roll['purchase_id'])): ?>
                                        <a href="<?= url('purchases/view/' . (int)$roll['purchase_id']) ?>" class="fw-bold text-decoration-none font-monospace">
                                            <?= e($roll['purchase_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-bold font-monospace text-primary"><?= e($roll['purchase_number'] ?: 'Draft Roll') ?></span>
                                        <span class="badge bg-warning-subtle text-warning border ms-1">Draft</span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block"><?= !empty($roll['purchase_date']) ? e(date('d M Y', strtotime($roll['purchase_date']))) : '-' ?></small>
                                    <?php if (!empty($roll['invoice_number'])): ?><small class="text-muted d-block">Inv: <?= e($roll['invoice_number']) ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($roll['seller_company'] ?: $roll['seller_name'] ?: '-') ?></strong>
                                    <?php if (!empty($roll['seller_company']) && !empty($roll['seller_name'])): ?><small class="text-muted d-block"><?= e($roll['seller_name']) ?></small><?php endif; ?>
                                </td>
                                <td id="roll-fabric-<?= $rowKey ?>"><?= e($roll['item_name']) ?></td>
                                <td>
                                    <span class="font-monospace" id="roll-code-<?= $rowKey ?>"><?= e($roll['roll_number'] ?: '-') ?></span>
                                    <small class="text-muted d-block" id="roll-details-<?= $rowKey ?>"><?= e($roll['color'] ?: '-') ?> / <?= e($roll['design_name'] ?: '-') ?></small>
                                </td>
                                <td class="text-end" id="roll-qty-<?= $rowKey ?>">
                                    <?= number_format((float)$roll['quantity'], 0) ?> <?= e($roll['unit'] ?: 'PCS') ?>
                                    <?php if ((float)$roll['weight_kg'] > 0): ?><small class="text-muted d-block"><?= number_format((float)$roll['weight_kg'], 2) ?> kg</small><?php endif; ?>
                                    <?php if ((float)$roll['unit_price'] > 0): ?><small class="text-muted d-block"><?= formatCurrency((float)((float)$roll['unit_price'])) ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($roll['qr_generation_id'])): ?>
                                        <?php if (hasPermission('qr_generation', 'view')): ?>
                                            <a href="<?= url('production/qr-generations/view/' . (int)$roll['qr_generation_id']) ?>" class="badge bg-dark-subtle text-dark border text-decoration-none">
                                                <i class="bi bi-qr-code me-1"></i><?= e($roll['qr_batch_code'] ?: 'View QR') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-dark-subtle text-dark border"><i class="bi bi-qr-code me-1"></i><?= e($roll['qr_batch_code'] ?: 'QR generated') ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">QR pending</span>
                                    <?php endif; ?>
                                    <?php if (!empty($roll['grn_id'])): ?>
                                        <?php if (hasPermission('inward', 'view')): ?>
                                            <a href="<?= url('production/inwards/view/' . (int)$roll['grn_id']) ?>" class="badge bg-success-subtle text-success border text-decoration-none ms-1">
                                                <i class="bi bi-truck me-1"></i><?= e($roll['grn_number'] ?: 'GRN') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border ms-1"><i class="bi bi-truck me-1"></i><?= e($roll['grn_number'] ?: 'GRN received') ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border ms-1">GRN pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= renderConcernBadge($roll) ?></td>
                                <td class="text-end text-nowrap">
                                    <?php if (hasPermission('production_purchase_mapping', 'edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark me-1" onclick="openEditFabricRollModal(<?= htmlspecialchars(json_encode($roll), ENT_QUOTES, 'UTF-8') ?>)" title="Edit Fabric Roll Details">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                    <?php if (empty($roll['purchase_id'])): ?>
                                        <a href="<?= url('purchases/mapping/draft/' . (int)($roll['mapping_bill_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i>View Draft
                                        </a>
                                    <?php elseif (empty($roll['qr_generation_id']) && hasPermission('qr_generation', 'create')): ?>
                                        <a href="<?= url('production/qr-generations/create?tab=fabric&purchase_id=' . (int)$roll['purchase_id'] . '&purchase_item_id=' . (int)$roll['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-qr-code-scan me-1"></i>Generate QR
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('purchases/view/' . (int)$roll['purchase_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i>View Bill
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="mappingLogsPane" role="tabpanel" aria-labelledby="mappingLogsTab">
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Mapping Invoice Log</h5>
                <span class="badge bg-secondary"><?= count($mappingDrafts ?? []) ?> Entries</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Supplier Invoice</th><th>Supplier</th><th>Products</th><th>Qty</th><th>Status</th><th>Purchase</th><th>Concern</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($mappingDrafts)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No Mapping invoices have been saved.</td></tr>
                        <?php else: foreach ($mappingDrafts as $draft): ?>
                            <tr>
                                <td><strong class="font-monospace"><?= e($draft['invoice_number']) ?></strong><div class="small text-muted"><?= e(date('d M Y', strtotime($draft['created_at']))) ?></div></td>
                                <td><?= e($draft['seller_company'] ?: $draft['seller_name']) ?></td>
                                <td><?= (int)$draft['product_count'] ?></td>
                                <td><?= number_format((float)$draft['total_quantity'], 3) ?></td>
                                <td><span class="badge <?= $draft['status'] === 'MOVED' ? 'bg-success' : ($draft['status'] === 'READY' ? 'bg-info text-dark' : 'bg-secondary') ?>"><?= e($draft['status']) ?></span></td>
                                <td>
                                    <?php if (!empty($draft['purchase_number'])): ?>
                                        <a href="<?= url('purchases/view/' . (int)$draft['purchase_id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none" title="View Purchase Bill">
                                            <i class="bi bi-receipt me-1"></i><?= e($draft['purchase_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Not linked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= renderConcernBadge($draft) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/view') ?>" title="View Mapping Invoice">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                        <?php if ($draft['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'edit')): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= url('purchases/mapping/draft/' . (int)$draft['id']) ?>" title="Edit Mapping Invoice">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($draft['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'delete')): ?>
                                            <form method="POST" action="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this draft invoice and all its saved products? This cannot be undone.');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Draft Invoice">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($draft['status'] !== 'MOVED' && (int)$draft['product_count'] > 0): ?>
                                            <form method="POST" action="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/move') ?>" class="d-inline" onsubmit="return confirm('Move mapping invoice <?= e(addslashes($draft['invoice_number'])) ?> to Purchases?')">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm btn-success" title="Create / Move to Purchase">
                                                    <i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase
                                                </button>
                                            </form>
                                        <?php elseif (!empty($draft['purchase_id'])): ?>
                                            <a class="btn btn-sm btn-outline-success" href="<?= url('purchases/view/' . (int)$draft['purchase_id']) ?>" title="View in Purchases">
                                                <i class="bi bi-receipt me-1"></i>Purchase
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Fabric Roll -->
<div class="modal fade" id="editFabricRollModal" tabindex="-1" aria-labelledby="editFabricRollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning-subtle text-dark">
                <h5 class="modal-title fw-bold" id="editFabricRollModalLabel">
                    <i class="bi bi-pencil-square me-2 text-warning"></i>Edit Fabric Roll Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFabricRollForm" onsubmit="submitEditFabricRoll(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="_csrf_token" value="<?= CSRF::token() ?>">
                    <input type="hidden" name="item_id" id="editRollItemId">
                    <input type="hidden" name="is_mapping_draft" id="editRollIsMappingDraft" value="0">

                    <div id="editRollAlert" class="alert alert-danger d-none mb-3 py-2 small"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Fabric / Material Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="editRollItemName" class="form-control" required placeholder="e.g. Cotton 30s">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Roll Number / Code</label>
                            <input type="text" name="roll_number" id="editRollNumber" class="form-control font-monospace" placeholder="e.g. R-261123-001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Color / Shade</label>
                            <input type="text" name="color" id="editRollColor" class="form-control" placeholder="e.g. Navy Blue">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Design Name / Pattern</label>
                            <input type="text" name="design_name" id="editRollDesignName" class="form-control" placeholder="e.g. Plain / Floral">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">HSN Code</label>
                            <input type="text" name="hsn_code" id="editRollHsnCode" class="form-control font-monospace" placeholder="e.g. 5208">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Quantity / Length <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="editRollQuantity" class="form-control" required placeholder="0.00">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Unit</label>
                            <select name="unit" id="editRollUnit" class="form-select">
                                <option value="MTR">MTR</option>
                                <option value="PCS">PCS</option>
                                <option value="KG">KG</option>
                                <option value="YDS">YDS</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Weight (KG)</label>
                            <input type="number" step="0.001" min="0" name="weight_kg" id="editRollWeightKg" class="form-control" placeholder="0.000">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold text-secondary">Purchase Rate / Price (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="unit_price" id="editRollUnitPrice" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editRollSubmitBtn">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editFabricRollModalInstance = null;
let currentEditingRoll = null;

function openEditFabricRollModal(roll) {
    currentEditingRoll = roll;
    const modalEl = document.getElementById('editFabricRollModal');
    if (!editFabricRollModalInstance) {
        editFabricRollModalInstance = new bootstrap.Modal(modalEl);
    }

    const alertBox = document.getElementById('editRollAlert');
    if (alertBox) {
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    document.getElementById('editRollItemId').value = roll.id || '';
    document.getElementById('editRollIsMappingDraft').value = roll.is_mapping_draft ? '1' : '0';
    document.getElementById('editRollItemName').value = roll.item_name || '';
    document.getElementById('editRollNumber').value = roll.roll_number || '';
    document.getElementById('editRollColor').value = roll.color || '';
    document.getElementById('editRollDesignName').value = roll.design_name || '';
    document.getElementById('editRollHsnCode').value = roll.hsn_code || '';
    document.getElementById('editRollQuantity').value = roll.quantity || '';
    document.getElementById('editRollUnit').value = roll.unit || 'MTR';
    document.getElementById('editRollWeightKg').value = (parseFloat(roll.weight_kg) > 0) ? roll.weight_kg : '';
    document.getElementById('editRollUnitPrice').value = (parseFloat(roll.unit_price) > 0) ? roll.unit_price : '';

    editFabricRollModalInstance.show();
}

async function submitEditFabricRoll(e) {
    e.preventDefault();
    const form = document.getElementById('editFabricRollForm');
    const alertBox = document.getElementById('editRollAlert');
    const submitBtn = document.getElementById('editRollSubmitBtn');

    alertBox.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

    const formData = new FormData(form);

    try {
        const response = await fetch('<?= url('purchases/mapping/edit-roll') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const res = await response.json();
        if (res.success) {
            editFabricRollModalInstance.hide();
            // Remember tab hash and reload cleanly to synchronize all QR/Purchased stock views
            window.location.hash = 'mappingFabricRollsPane';
            window.location.reload();
        } else {
            alertBox.textContent = res.message || 'Failed to update roll details.';
            alertBox.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Changes';
        }
    } catch (err) {
        alertBox.textContent = 'Server error: ' + err.message;
        alertBox.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Changes';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    if (hash === '#mappingFabricRollsPane') {
        const tabBtn = document.getElementById('mappingFabricRollsTab');
        if (tabBtn) {
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
        }
    } else if (hash === '#mappingLogsPane') {
        const tabBtn = document.getElementById('mappingLogsTab');
        if (tabBtn) {
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
        }
    }
});

function unmapItem(itemId, itemName) {
    if (!confirm(`Are you sure you want to delete the mapping for '${itemName}'? It will automatically be marked as unmapped.`)) {
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('purchases/mapping/unmap/') ?>' + itemId;
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= CSRF::token() ?>';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

function unmapBill(billId, billNumber) {
    if (!confirm(`Are you sure you want to delete all mappings for purchase bill #${billNumber}? All items will automatically be marked as unmapped.`)) {
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('purchases/mapping/unmap-bill/') ?>' + billId;
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= CSRF::token() ?>';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}
</script>
