<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold text-dark"><i class="bi bi-bag-check me-2 text-primary"></i> Purchases</h2>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (hasPermission('production_purchases', 'create') || hasPermission('sellers', 'create')): ?>
        <div class="btn-group shadow-sm">
            <a href="<?= url('purchases/create') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Purchases
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><h6 class="dropdown-header text-uppercase small fw-bold">Quick Purchase Entry</h6></li>
                <li>
                    <a class="dropdown-item py-2" href="<?= url('purchases/create?type=FABRIC_ROLL') ?>">
                        <i class="bi bi-vinyl text-primary me-2"></i><strong>1. Fabric Rolls</strong>
                        <small class="d-block text-muted ps-4">Thermal scannable roll QR tags</small>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= url('purchases/create?type=RAW_MATERIAL') ?>">
                        <i class="bi bi-boxes text-warning me-2"></i><strong>2. Raw Materials</strong>
                        <small class="d-block text-muted ps-4">Yarn, sewing threads, trims</small>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= url('purchases/create?type=THIRD_PARTY') ?>">
                        <i class="bi bi-shop text-info me-2"></i><strong>3. Third-Party Products</strong>
                        <small class="d-block text-muted ps-4">Ready-to-sell SKU items</small>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Purchases</div>
                    <div class="fs-3 fw-bold text-success mt-1"><?= number_format((int)($metrics['total_count'] ?? count($purchases))) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-currency-rupee text-success me-1"></i><?= formatCurrency((float)((float)$totalPurchaseValue)) ?> spend</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-bag-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Fabric Roll Purchases</div>
                    <div class="fs-3 fw-bold text-primary mt-1"><?= number_format((int)($metrics['fabric_roll_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-vinyl text-primary me-1"></i>Fabric roll orders</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-vinyl fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Raw Materials Purchases</div>
                    <div class="fs-3 fw-bold text-warning-emphasis mt-1"><?= number_format((int)($metrics['raw_material_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-boxes text-warning me-1"></i>Yarn, threads &amp; accessories</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Third-Party Purchases</div>
                    <div class="fs-3 fw-bold text-info-emphasis mt-1"><?= number_format((int)($metrics['third_party_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-shop text-info me-1"></i>Third-party orders</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-shop fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.purchase-action-buttons {
    display: inline-flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    white-space: nowrap;
}
.purchase-action-buttons .btn-action-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 32px;
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
}
</style>

<div class="card border shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-receipt me-1 text-primary"></i> Purchase Orders &amp; Supplier Invoices
                </h6>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="btn-group btn-group-sm me-2">
                    <a href="<?= url('purchases') ?>" class="btn <?= empty($filterType) ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                    <a href="<?= url('purchases?type=FABRIC_ROLL') ?>" class="btn <?= ($filterType === 'FABRIC_ROLL') ? 'btn-primary' : 'btn-outline-secondary' ?>">Rolls</a>
                    <a href="<?= url('purchases?type=RAW_MATERIAL') ?>" class="btn <?= ($filterType === 'RAW_MATERIAL') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">Raw Materials</a>
                    <a href="<?= url('purchases?type=THIRD_PARTY') ?>" class="btn <?= ($filterType === 'THIRD_PARTY') ? 'btn-info text-white' : 'btn-outline-secondary' ?>">3rd-Party</a>
                    <a href="<?= url('purchases?type=MIXED') ?>" class="btn <?= ($filterType === 'MIXED') ? 'btn-dark text-white' : 'btn-outline-secondary' ?>" title="Invoices containing multiple product types">Combined</a>
                    <a href="<?= url('purchases?type=MAPPING') ?>" class="btn <?= ($filterType === 'MAPPING') ? 'text-white' : 'btn-outline-secondary' ?>" style="<?= ($filterType === 'MAPPING') ? 'background-color:#6f42c1;' : '' ?>" title="Purchases created or linked from Purchase Item Mapping"><i class="bi bi-diagram-3 me-1"></i>From Mapping<?php if (!empty($pendingMappingCount)): ?> <span class="badge bg-warning text-dark ms-1"><?= (int)$pendingMappingCount ?> Pending</span><?php endif; ?></a>
                </div>
                <input type="text" id="purchasesSearchInput" class="form-control form-control-sm" placeholder="🔍 Search PO #, supplier, invoice..." style="width: 240px;" oninput="filterPurchasesTable(this.value)">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="purchasesTable">
                <thead class="table-light">
                    <tr class="small text-uppercase text-secondary">
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>Purchase / PO #</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Supplier / Vendor</th>
                        <th>Inward Items &amp; Summary</th>
                        <th>Invoice #</th>
                        <th class="text-end">Total Amount (₹)</th>
                        <th>Concern</th>
                        <th class="text-center text-nowrap" style="width: 170px; min-width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="purchasesTableBody">
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5">No purchases recorded yet.</td></tr>
                <?php else: foreach ($purchases as $idx => $purchase): 
                    $pType = $purchase['purchase_type'] ?? 'FABRIC_ROLL';
                    $rollItems = array_filter($purchase['items'] ?? [], fn($it) => ($it['item_type'] ?? '') === 'FABRIC_ROLL');
                    $rawMaterialItems = array_filter($purchase['items'] ?? [], fn($it) => ($it['item_type'] ?? '') === 'RAW_MATERIAL');
                    $thirdPartyItems = array_filter($purchase['items'] ?? [], fn($it) => ($it['item_type'] ?? '') === 'THIRD_PARTY');
                    $ownProductItems = array_filter($purchase['items'] ?? [], fn($it) => ($it['item_type'] ?? '') === 'OWN_PRODUCT');
                    $rollItemCount = count($rollItems);
                    $rollQtySum = (int)array_sum(array_map(fn($it) => max(1, (int)($it['quantity'] ?? 1)), $rollItems));
                    $totalWeight = (float)array_sum(array_column($rollItems, 'weight_kg'));
                    $rmQtySum = (float)array_sum(array_column($rawMaterialItems, 'quantity'));
                    $tpQtySum = (float)array_sum(array_column($thirdPartyItems, 'quantity'));
                    $opQtySum = (float)array_sum(array_column($ownProductItems, 'quantity'));
                ?>
                    <tr class="purchase-row" data-search="<?= strtolower(e($purchase['purchase_number'] . ' ' . $pType . ' ' . ($purchase['company'] ?? '') . ' ' . ($purchase['seller_name'] ?? '') . ' ' . ($purchase['invoice_number'] ?? '') . ' ' . ($purchase['concern_name'] ?? '') . ' ' . ($purchase['concern_code'] ?? ''))) ?>">
                        <td class="text-center text-muted small fw-semibold"><?= $idx + 1 ?></td>
                        <td>
                            <strong class="font-monospace text-primary"><?= e($purchase['purchase_number']) ?></strong>
                            <?php if (!empty($purchase['mapping_bill_id']) || str_contains($purchase['notes'] ?? '', 'Mapping')): ?>
                                <div>
                                    <a href="<?= !empty($purchase['mapping_bill_id']) ? url('purchases/mapping/draft/' . (int)$purchase['mapping_bill_id']) : url('purchases/mapping') ?>" class="badge text-white text-decoration-none mt-1 shadow-sm" style="background-color: #6f42c1;" title="Originates from Purchase Item Mapping Draft">
                                        <i class="bi bi-diagram-3 me-1"></i>From Mapping<?= !empty($purchase['mapping_invoice_number']) ? ': ' . e($purchase['mapping_invoice_number']) : '' ?>
                                    </a>
                                    <?php if (($purchase['accounts_status'] ?? '') === 'PENDING_ACCOUNTS'): ?>
                                        <span class="badge bg-warning text-dark mt-1"><i class="bi bi-hourglass-split me-1"></i>Pending Accounts Bill</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1"><i class="bi bi-shield-check me-1"></i>Accounts Verified</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pType === 'MIXED' || ((int)($rollItemCount > 0) + (int)(count($rawMaterialItems) > 0) + (int)(count($thirdPartyItems) > 0) > 1)): ?>
                                <span class="badge border" style="background-color: #f3e8ff; color: #7e22ce; border-color: #d8b4fe !important;">
                                    <i class="bi bi-collection me-1"></i>Combined (Mixed)
                                </span>
                            <?php elseif ($pType === 'RAW_MATERIAL'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border">
                                    <i class="bi bi-boxes me-1"></i>Raw Material
                                </span>
                            <?php elseif ($pType === 'THIRD_PARTY'): ?>
                                <span class="badge bg-info-subtle text-info border">
                                    <i class="bi bi-box-seam me-1"></i>Third-Party
                                </span>
                            <?php elseif ($pType === 'OWN_PRODUCT'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="bi bi-building-check me-1"></i>Own Production
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border">
                                    <i class="bi bi-layers-half me-1"></i>Fabric Roll
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap small text-dark">
                            <i class="bi bi-calendar3 me-1 text-muted"></i><?= e(formatDate($purchase['purchase_date'])) ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($purchase['company'] ?: $purchase['seller_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-person me-1"></i><?= e($purchase['seller_name']) ?></small>
                        </td>
                        <td>
                            <?php if ($pType === 'MIXED' || ((int)($rollItemCount > 0) + (int)(count($rawMaterialItems) > 0) + (int)(count($thirdPartyItems) > 0) > 1)): ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <?php if ($rollItemCount > 0): ?>
                                        <span class="badge bg-primary-subtle text-primary border">
                                            <i class="bi bi-layers-half me-1"></i><?= $rollItemCount ?> Rolls (<?= $rollQtySum ?> RQ, <?= number_format($totalWeight, 1) ?> kg)
                                        </span>
                                    <?php endif; ?>
                                    <?php if (count($rawMaterialItems) > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">
                                            <i class="bi bi-boxes me-1"></i><?= count($rawMaterialItems) ?> Raw Material(s) (<?= number_format($rmQtySum) ?> Units)
                                        </span>
                                    <?php endif; ?>
                                    <?php if (count($thirdPartyItems) > 0): ?>
                                        <span class="badge bg-info-subtle text-info border">
                                            <i class="bi bi-box-seam me-1"></i><?= count($thirdPartyItems) ?> Product(s) (<?= number_format($tpQtySum) ?> Pcs)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 340px;">
                                    <?= e(implode(', ', array_slice(array_column($purchase['items'] ?? [], 'item_name'), 0, 4))) ?>
                                    <?php if (count($purchase['items'] ?? []) > 4): ?>
                                        <span class="text-secondary fw-normal">+<?= count($purchase['items']) - 4 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($pType === 'RAW_MATERIAL'): ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border fw-semibold">
                                        <i class="bi bi-boxes me-1"></i><?= count($rawMaterialItems) ?> Raw Material(s) (<?= number_format($rmQtySum) ?> Units)
                                    </span>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                    <?= e(implode(', ', array_slice(array_column($rawMaterialItems, 'item_name'), 0, 3))) ?>
                                    <?php if (count($rawMaterialItems) > 3): ?>
                                        <span class="text-secondary fw-normal">+<?= count($rawMaterialItems) - 3 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($pType === 'THIRD_PARTY'): ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="badge bg-info-subtle text-info border fw-semibold">
                                        <i class="bi bi-box-seam me-1"></i><?= count($thirdPartyItems) ?> Product(s) (<?= number_format($tpQtySum) ?> Units)
                                    </span>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                    <?= e(implode(', ', array_slice(array_column($thirdPartyItems, 'item_name'), 0, 3))) ?>
                                    <?php if (count($thirdPartyItems) > 3): ?>
                                        <span class="text-secondary fw-normal">+<?= count($thirdPartyItems) - 3 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($pType === 'OWN_PRODUCT'): ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="badge bg-secondary-subtle text-secondary border fw-semibold">
                                        <i class="bi bi-building-check me-1"></i><?= count($ownProductItems) ?> Inward Line(s) (<?= number_format($opQtySum) ?> Units)
                                    </span>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                    <?= e(implode(', ', array_slice(array_column($ownProductItems, 'item_name'), 0, 3))) ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <?php if ($rollItemCount > 0): ?>
                                        <span class="badge bg-primary-subtle text-primary border fw-semibold">
                                            <i class="bi bi-layers me-1"></i><?= $rollItemCount ?> <?= $rollItemCount === 1 ? 'Roll' : 'Rolls' ?><?php if ($rollQtySum > $rollItemCount): ?> (<?= $rollQtySum ?> Qty)<?php endif; ?>
                                        </span>
                                        <span class="badge bg-light text-dark border">
                                            <?= number_format($totalWeight, 2) ?> kg
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($rollItemCount > 0): 
                                    $rollSummaryLabels = array_map(function($r) {
                                        $name = $r['roll_number'] ?: ('Roll #' . $r['id']);
                                        $qty = (int)($r['quantity'] ?? 1);
                                        return $qty > 1 ? "{$name} ({$qty})" : $name;
                                    }, $rollItems);
                                ?>
                                    <div class="small text-muted font-monospace text-truncate" style="max-width: 320px;" title="<?= e(implode(', ', $rollSummaryLabels)) ?>">
                                        <?= e(implode(', ', array_slice($rollSummaryLabels, 0, 3))) ?>
                                        <?php if (count($rollItems) > 3): ?>
                                            <span class="text-secondary fw-normal font-sans">+<?= count($rollItems) - 3 ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($purchase['invoice_number'])): ?>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($purchase['invoice_number']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                            <?php 
                                $docUrls = [];
                                if (!empty($purchase['document_urls'])) {
                                    $decoded = json_decode($purchase['document_urls'], true);
                                    if (is_array($decoded)) $docUrls = $decoded;
                                }
                                if (empty($docUrls) && !empty($purchase['document_url'])) {
                                    $docUrls = [$purchase['document_url']];
                                }
                                $docCount = count($docUrls);
                            ?>
                            <?php if ($docCount === 1): 
                                $isAvif = str_ends_with(strtolower($docUrls[0]), '.avif');
                                $isPdf = str_ends_with(strtolower($docUrls[0]), '.pdf');
                            ?>
                                <a class="btn btn-xs btn-link p-0 text-decoration-none d-block small mt-1" href="<?= uploadUrl($docUrls[0]) ?>" target="_blank" rel="noopener">
                                    <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-check text-success' ?> me-1"></i>View Bill <?= $isAvif ? '(AVIF)' : '' ?>
                                </a>
                            <?php elseif ($docCount > 1): ?>
                                <div class="dropdown mt-1">
                                    <button class="btn btn-xs btn-outline-primary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:11px;">
                                        <i class="bi bi-images me-1"></i><?= $docCount ?> Invoices
                                    </button>
                                    <ul class="dropdown-menu shadow-sm" style="font-size:12px;">
                                        <li><h6 class="dropdown-header small py-1">Invoices (<?= $docCount ?> files)</h6></li>
                                        <?php foreach ($docUrls as $i => $u): 
                                            $isAvif = str_ends_with(strtolower($u), '.avif');
                                            $isPdf = str_ends_with(strtolower($u), '.pdf');
                                        ?>
                                            <li>
                                                <a class="dropdown-item py-1 d-flex align-items-center justify-content-between gap-2" href="<?= uploadUrl($u) ?>" target="_blank" rel="noopener">
                                                    <span><i class="bi <?= $isPdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary' ?> me-1"></i>Invoice #<?= $i + 1 ?></span>
                                                    <span class="badge <?= $isAvif ? 'bg-success-subtle text-success' : ($isPdf ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle') ?>" style="font-size:9px;">
                                                        <?= $isAvif ? 'AVIF' : ($isPdf ? 'PDF' : 'FILE') ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold text-success fs-6">
                            <?= formatCurrency((float)((float)$purchase['total_amount'])) ?>
                        </td>
                        <td><?= renderConcernBadge($purchase) ?></td>
                        <td class="text-center text-nowrap">
                            <div class="purchase-action-buttons">
                                <?php if (hasPermission('production_purchases', 'view') || hasPermission('sellers', 'view')): ?>
                                    <a class="btn btn-action-icon btn-outline-info" href="<?= url('purchases/view/' . $purchase['id']) ?>" title="View Purchase Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a class="btn btn-action-icon btn-outline-primary" href="<?= url('production/purchases/print/' . $purchase['id']) ?>" target="_blank" rel="noopener" title="Print Purchase Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <button type="button" class="btn btn-action-icon btn-outline-dark" title="Bill Logs &amp; Product Details" onclick="openPurchaseBillLogs(<?= (int)$purchase['id'] ?>, '<?= e(addslashes($purchase['purchase_number'])) ?>')">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (hasPermission('production_purchases', 'edit') || hasPermission('sellers', 'edit')): ?>
                                    <a class="btn btn-action-icon btn-outline-primary" href="<?= url('production/purchases/edit/' . $purchase['id']) ?>" title="Edit Purchase">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (($purchase['accounts_status'] ?? '') === 'PENDING_ACCOUNTS'): ?>
                                        <form method="POST" action="<?= url('allmodules/purchases/verify-accounts/' . (int)$purchase['id']) ?>" class="d-inline" onsubmit="return confirm('Mark bill <?= e(addslashes($purchase['purchase_number'])) ?> as Accounts Verified?')">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-action-icon btn-outline-success" title="Verify &amp; Finalize Accounts Bill">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (hasPermission('production_purchases', 'delete') || hasPermission('sellers', 'delete')): ?>
                                    <button type="button" class="btn btn-action-icon btn-outline-danger" title="Delete Purchase" onclick="confirmDeletePurchase('<?= url('production/purchases/delete/' . $purchase['id']) ?>', '<?= e(addslashes($purchase['purchase_number'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
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

<script>
function filterPurchasesTable(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#purchasesTableBody .purchase-row');
    rows.forEach(r => {
        const text = (r.getAttribute('data-search') || '') + ' ' + (r.textContent || '').toLowerCase();
        r.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}

function confirmDeletePurchase(actionUrl, purchaseNumber) {
    const modalEl = document.getElementById('modalConfirmDeletePurchase');
    const formEl = document.getElementById('deletePurchaseForm');
    const numEl = document.getElementById('deletePurchaseNumber');
    if (formEl) formEl.action = actionUrl;
    if (numEl) numEl.textContent = purchaseNumber;
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}
</script>

<!-- Modal: Modern Confirmation Popup for Deleting Purchases -->
<div class="modal fade" id="modalConfirmDeletePurchase" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width: 68px; height: 68px;">
                    <i class="bi bi-trash3-fill fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Delete Purchase</h5>
                <p class="text-secondary small mb-3">
                    Are you sure you want to delete purchase <strong class="text-dark" id="deletePurchaseNumber"></strong>?
                </p>
                <div class="alert alert-danger border-0 bg-danger-subtle text-start p-3 rounded-3 small mb-4">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong class="text-danger">Warning:</strong>
                            <div class="text-secondary mt-1">
                                This will permanently delete this purchase record and its associated inward items. This action cannot be undone.
                            </div>
                        </div>
                    </div>
                </div>
                <form method="POST" id="deletePurchaseForm" action="">
                    <?= CSRF::field() ?>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold shadow-sm d-flex align-items-center gap-1">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bill Logs & Product Details Modal -->
<div class="modal fade" id="modalPurchaseBillLogs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-clock-history me-2"></i>Bill Logs &amp; Product Details — <span id="billLogsPoTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="billLogsModalBody">
                <div class="text-center py-5 text-muted">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading bill logs...
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function escHtmlBillLog(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function openPurchaseBillLogs(purchaseId, poNumber) {
    const titleEl = document.getElementById('billLogsPoTitle');
    const bodyEl = document.getElementById('billLogsModalBody');
    if (titleEl) titleEl.textContent = poNumber || ('#' + purchaseId);
    if (bodyEl) {
        bodyEl.innerHTML = '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading bill logs...</div>';
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPurchaseBillLogs')).show();

    fetch('<?= url('allmodules/purchases/logs/') ?>' + purchaseId, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Unable to load bill logs.');
            const p = data.purchase || {};
            const items = Array.isArray(data.items) ? data.items : [];
            const logs = Array.isArray(data.logs) ? data.logs : [];

            const supplierName = p.seller_company || p.seller_name || '—';
            let html = `
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="small text-muted">Purchase / PO #</div>
                            <div class="fw-bold text-primary font-monospace">${escHtmlBillLog(p.purchase_number)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="small text-muted">Supplier</div>
                            <div class="fw-bold">${escHtmlBillLog(supplierName)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="small text-muted">Date &amp; Invoice #</div>
                            <div class="fw-semibold">${escHtmlBillLog(p.purchase_date || '—')} · ${escHtmlBillLog(p.invoice_number || '—')}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="small text-muted">Total Bill Amount</div>
                            <div class="fw-bold text-success fs-5">₹${Number(p.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-box-seam text-primary me-1"></i>Created &amp; Mapped Bill Products (${items.length})</h6>
                <div class="table-responsive border rounded mb-4">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th>#</th>
                                <th>Product / SKU</th>
                                <th>Category</th>
                                <th class="text-end">Orig Qty</th>
                                <th class="text-end">Extra Qty</th>
                                <th class="text-end">Final Qty</th>
                                <th class="text-end">Cost / MRP / SP</th>
                                <th class="text-end">Total (₹)</th>
                                <th>Source</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (items.length === 0) {
                html += '<tr><td colspan="10" class="text-center text-muted py-3">No products found in this bill.</td></tr>';
            } else {
                items.forEach((it, idx) => {
                    const origQ = Number(it.original_purchased_qty ?? it.quantity ?? 0);
                    const extraQ = Number(it.extra_added_qty ?? 0);
                    const finalQ = Number(it.quantity ?? 0);
                    const isExtraLine = Number(it.is_extra_purchase_item || 0) === 1;
                    const isStocked = Number(it.is_inwarded || 0) === 1 || Number(it.inwarded_qty || 0) > 0 || Number(it.qr_stock_added || 0) === 1;
                    const isMapped = Number(it.is_mapped || 0) === 1;
                    const catLabel = [it.tp_category_name, it.tp_subcategory_name, it.tp_sub_subcategory_name].filter(Boolean).join(' / ') || '—';
                    const sourceBadge = isExtraLine
                        ? '<span class="badge bg-info text-dark">Added (Not in Original Bill)</span>'
                        : (extraQ > 0 ? '<span class="badge bg-warning text-dark">Bill + Extra Qty</span>' : '<span class="badge bg-light text-secondary border">Original Bill</span>');
                    const statusBadges = (isMapped ? '<span class="badge bg-success me-1">Mapped</span>' : '<span class="badge bg-warning text-dark me-1">Pending</span>')
                        + (isStocked ? '<span class="badge bg-primary">Added Stock</span>' : '<span class="badge bg-light text-secondary border">Not Stocked</span>');

                    html += `
                        <tr>
                            <td class="text-muted">${idx + 1}</td>
                            <td>
                                <div class="fw-bold">${escHtmlBillLog(it.item_name)}</div>
                                <div class="text-muted font-monospace">${escHtmlBillLog(it.vendor_sku || it.product_code || '—')}</div>
                            </td>
                            <td>${escHtmlBillLog(catLabel)}</td>
                            <td class="text-end">${origQ} ${escHtmlBillLog(it.unit || '')}</td>
                            <td class="text-end fw-bold ${extraQ > 0 ? 'text-primary' : 'text-muted'}">${extraQ > 0 ? '+' + extraQ : '0'}</td>
                            <td class="text-end fw-bold">${finalQ} ${escHtmlBillLog(it.unit || '')}</td>
                            <td class="text-end">${formatCurrency(Number(it.unit_price || 0))} / ${formatCurrency(Number(it.mrp || 0))} / ${formatCurrency(Number(it.selling_price || 0))}</td>
                            <td class="text-end fw-bold">${formatCurrency(Number(it.total_price || 0))}</td>
                            <td>${sourceBadge}</td>
                            <td>${statusBadges}</td>
                        </tr>
                    `;
                });
            }

            html += `
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-journal-text text-dark me-1"></i>Audit &amp; Activity Logs (${logs.length})</h6>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light text-uppercase text-muted">
                            <tr>
                                <th>Date / Time</th>
                                <th>Action</th>
                                <th>Module / Table</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (logs.length === 0) {
                html += '<tr><td colspan="4" class="text-center text-muted py-3">No audit log entries recorded for this bill.</td></tr>';
            } else {
                logs.forEach(lg => {
                    html += `
                        <tr>
                            <td class="text-muted">${escHtmlBillLog(lg.created_at || '—')}</td>
                            <td><span class="badge bg-secondary-subtle text-dark border text-uppercase">${escHtmlBillLog(lg.action || 'EVENT')}</span></td>
                            <td class="font-monospace">${escHtmlBillLog((lg.module || '') + ' / ' + (lg.table_name || ''))}</td>
                            <td>${escHtmlBillLog(lg.actor_name || 'System')}</td>
                        </tr>
                    `;
                });
            }

            html += '</tbody></table></div>';
            bodyEl.innerHTML = html;
        })
        .catch(err => {
            bodyEl.innerHTML = `<div class="alert alert-danger mb-0">${escHtmlBillLog(err.message)}</div>`;
        });
}
</script>
