<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Purchased Stock</h2>
    </div>
</div>

<!-- CSRF Token Meta for AJAX -->
<input type="hidden" id="pageCsrfToken" name="_csrf_token" value="<?= CSRF::token() ?>">

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Stock Batches</div>
                    <div class="fs-3 fw-bold text-success mt-1" id="kpi-total-batches"><?= number_format((int)($metrics['total_batches'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-boxes text-success me-1"></i><span id="kpi-total-units"><?= number_format((float)($metrics['total_units'] ?? 0)) ?></span> QR units in stock</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Fabric Rolls in Stock</div>
                    <div class="fs-3 fw-bold text-primary mt-1" id="kpi-fabric-count"><?= number_format((int)($metrics['fabric_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-vinyl text-primary me-1"></i>Stocked fabric rolls</div>
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
                    <div class="text-uppercase fw-semibold text-muted small">Raw Materials in Stock</div>
                    <div class="fs-3 fw-bold text-warning-emphasis mt-1" id="kpi-raw-count"><?= number_format((int)($metrics['raw_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-layers text-warning me-1"></i>Stocked raw materials</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-layers fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Third-Party Products</div>
                    <div class="fs-3 fw-bold text-info-emphasis mt-1" id="kpi-third-party-count"><?= number_format((int)($metrics['third_party_count'] ?? 0)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-shop text-info me-1"></i>Stocked third-party lines</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-shop fs-4"></i>
                </div>
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
                    <th>Concern</th>
                    <th class="text-end pe-3" style="min-width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <?php if (empty($stockItems)): ?>
                    <tr id="emptyStockRow">
                        <td colspan="10" class="text-center py-5 text-muted">
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
                                        <span class="me-1 color-info">Color: <strong><?= e($item['color_name']) ?></strong></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['design_name'])): ?>
                                        <span class="me-1 design-info">· Pattern: <strong><?= e($item['design_name']) ?></strong></span>
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
                                <?php if (!empty($item['used_program_number'])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-success text-white" style="font-size: 10px; font-weight: 600;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Used Roll
                                        </span>
                                        <div class="small text-success fw-semibold mt-0 text-truncate" style="font-size: 10.5px; max-width: 140px;" title="<?= e($item['used_program_number'] . (!empty($item['used_program_name']) ? ' - ' . $item['used_program_name'] : '')) ?>">
                                            <?= e($item['used_program_number']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                            <td><?= renderConcernBadge($item) ?></td>
                            <td class="text-end text-nowrap pe-3">
                                <div class="action-btns-group justify-content-end">
                                    <a href="<?= url('production/purchased-stock/view/' . (int)$item['generation_id']) ?>" class="btn btn-action-icon btn-outline-info" title="View Product Details & QR Codes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('production/purchased-stock/edit/' . (int)$item['generation_id']) ?>" class="btn btn-action-icon btn-outline-primary" title="Edit QR Details">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-action-icon btn-outline-danger" title="Delete Stock Line" onclick="confirmDeleteRow(<?= (int)$item['generation_id'] ?>, '<?= e($item['batch_code']) ?>')">
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
<!-- DYNAMIC DELETE CONFIRMATION MODAL -->
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

<script>
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
                <td colspan="10" class="text-center py-5 text-muted">
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
