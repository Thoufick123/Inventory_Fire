<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-3 me-2 text-primary"></i>Third-Party Product Mapping</h2>
        <span class="text-muted small">Create products and prepare invoice mapping before the Purchases entry is available. Existing purchase lines can still be mapped below.</span>
    </div>
    <a href="<?= url('purchases/mapping/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Mapping Invoice
    </a>
</div>

<!-- Mapping entries saved before purchase entry -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Mapping Invoice Log</h5>
        <span class="badge bg-secondary"><?= count($mappingDrafts ?? []) ?> Entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Supplier Invoice</th><th>Supplier</th><th>Products</th><th>Qty</th><th>Status</th><th>Purchase</th><th class="text-end">Action</th></tr></thead>
            <tbody>
                <?php if (empty($mappingDrafts)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No Mapping invoices have been saved.</td></tr>
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
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('purchases/mapping/draft/' . (int)$draft['id']) ?>" title="Open Mapping Invoice">
                                    <i class="bi bi-pencil-square me-1"></i>Open
                                </a>
                                <?php if ($draft['status'] !== 'MOVED' && (int)$draft['product_count'] > 0): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/move') ?>" class="d-inline" onsubmit="return confirm('Move mapping invoice <?= e(addslashes($draft['invoice_number'])) ?> to Purchases?')">
                                        <?= CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm btn-success" title="Create / Move to Purchase">
                                            <i class="bi bi-box-arrow-in-right me-1"></i>Move to Purchase
                                        </button>
                                    </form>
                                <?php elseif (!empty($draft['purchase_id'])): ?>
                                    <a class="btn btn-sm btn-outline-success" href="<?= url('purchases/view/' . (int)$draft['purchase_id']) ?>" title="View in Purchases">
                                        <i class="bi bi-eye me-1"></i>View
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

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Third-Party Products</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($metrics['total_items'] ?? 0)) ?></div>
                <div class="small text-muted"><?= number_format((float)($metrics['total_quantity'] ?? 0)) ?> purchased units (pcs)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Pending Unmapped</div>
                <div class="fs-3 fw-bold text-warning"><?= number_format((int)($metrics['unmapped_count'] ?? 0)) ?></div>
                <div class="small text-muted">Awaiting category &amp; attribute mapping</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Successfully Mapped</div>
                <div class="fs-3 fw-bold text-success"><?= number_format((int)($metrics['mapped_count'] ?? 0)) ?></div>
                <div class="small text-muted">Configured in Third-Party Catalog</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Mapping Completion</div>
                <?php
                    $tot = (int)($metrics['total_items'] ?? 0);
                    $map = (int)($metrics['mapped_count'] ?? 0);
                    $pct = $tot > 0 ? round(($map / $tot) * 100, 1) : 100;
                ?>
                <div class="fs-3 fw-bold text-primary"><?= $pct ?>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted mt-2">
                    QR batches: <?= number_format((int)($metrics['qr_count'] ?? 0)) ?> &nbsp;·&nbsp; GRNs: <?= number_format((int)($metrics['grn_count'] ?? 0)) ?>
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
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($billGroups)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
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

<script>
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
