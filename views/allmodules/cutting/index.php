<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-scissors me-2 text-info"></i>Cutting Batches</h2>
    </div>
    <?php if (hasPermission('cutting', 'create')): ?>
    <div class="d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-outline-dark px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#cuttingOutwardModal">
            <i class="bi bi-truck me-1"></i> Cutting Outward
        </button>
        <a href="<?= url('production/cutting/create') ?>" class="btn btn-primary px-3 py-2 fw-semibold">
            <i class="bi bi-plus-circle me-1"></i> New Cutting Batch
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Summary Metrics Bar -->
<?php
    $totalBatches = count($batches);
    $totalCutPieces = 0;
    $totalFabricWastageKg = 0;
    $totalCostSum = 0;
    $validCostCount = 0;

    foreach ($batches as $b) {
        $totalCutPieces += (int)($b['cut_qty'] ?? 0);
        $totalFabricWastageKg += (float)($b['wastage_weight'] ?? 0);
        if ((float)($b['cost_per_piece'] ?? 0) > 0) {
            $totalCostSum += (float)$b['cost_per_piece'];
            $validCostCount++;
        }
    }
    $avgCostPerPiece = $validCostCount > 0 ? ($totalCostSum / $validCostCount) : 0;
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Cutting Batches</div>
                    <div class="fw-bold text-primary fs-3 mt-1"><?= number_format($totalBatches) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-scissors text-primary me-1"></i>Recorded cutting runs</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-scissors fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Cut Output</div>
                    <div class="fw-bold text-success fs-3 mt-1"><?= number_format($totalCutPieces) ?> <small class="fs-6 text-muted fw-normal">pcs</small></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-check2-all text-success me-1"></i>Approved garment cuts</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-check2-all fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Fabric Scrap / Wastage</div>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= number_format($totalFabricWastageKg, 2) ?> <small class="fs-6 text-muted fw-normal">kg</small></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-trash3 text-warning me-1"></i>Cutting room scrap</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-trash3 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Avg Fabric Cost/Pc</div>
                    <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= formatCurrency((float)($avgCostPerPiece)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-currency-rupee text-info me-1"></i>Per garment piece</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/cutting') ?>" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Status</label>
                <select name="status" class="form-select" style="height: 38px; border-color: #cbd5e1; border-radius: 8px; font-size: 0.875rem;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="HOLD" <?= ($filters['status'] ?? '') === 'HOLD' ? 'selected' : '' ?>>On Hold</option>
                    <option value="OUTWARDED" <?= ($filters['status'] ?? '') === 'OUTWARDED' ? 'selected' : '' ?>>Outwarded</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-lg-7 col-md-4 col-sm-12">
                <label class="form-label small text-muted fw-semibold mb-1">Search</label>
                <input type="text" name="search" id="cuttingSearchInput" class="form-control" style="height: 38px; border-color: #cbd5e1; border-radius: 8px; font-size: 0.875rem;" placeholder="Cutting name, batch #, program #, product name, roll #, color, design..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-lg-2 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center fw-semibold px-3" style="height: 38px; border-radius: 8px; font-size: 0.875rem; gap: 6px;" title="Filter Records">
                    <i class="bi bi-filter"></i>
                    <span>Filter</span>
                </button>
                <a href="<?= url('production/cutting') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="height: 38px; width: 38px; min-width: 38px; padding: 0; border-radius: 8px; border-color: #cbd5e1;" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-scissors me-2 text-primary"></i>Cutting Batches List</h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold">
            <?= count($batches) ?> Batches Found
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="cuttingTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 170px;">Cutting Details</th>
                        <th style="min-width: 180px;">Program & Catalog Hierarchy</th>
                        <th style="min-width: 150px;">Fabric Roll Specs</th>
                        <th style="min-width: 140px;">Cut Qty</th>
                        <th style="min-width: 130px;">Scrap / Wastage</th>
                        <th style="min-width: 110px;">Fabric Cost</th>
                        <th>Status</th>
                        <th>Concern</th>
                        <th class="text-end" style="min-width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="cuttingTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-scissors fs-1 text-secondary d-block mb-3"></i>
                                <h5 class="fw-bold text-secondary">No Cutting Batches Found</h5>
                                <p class="text-muted mb-3">Start by creating a new cutting batch to process fabric rolls and pattern variants.</p>
                                <a href="<?= url('production/cutting/create') ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> New Cutting Batch
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <?php 
                                $statusClass = match($b['status']) {
                                    'COMPLETED'   => 'bg-success',
                                    'IN_PROGRESS' => 'bg-warning text-dark',
                                    'OUTWARDED'   => 'bg-dark text-white',
                                    default       => 'bg-secondary'
                                };
                                $isOutwarded = strtoupper((string)($b['status'] ?? '')) === 'OUTWARDED' || !empty($b['cutting_outward_id']);

                                // Build Category Path
                                $catParts = array_filter([
                                    $b['category_name'] ?? '',
                                    $b['subcategory_name'] ?? '',
                                    $b['sub_subcategory_name'] ?? ''
                                ]);
                                $catBreadcrumb = !empty($catParts) ? implode(' › ', $catParts) : ($b['product_name'] ?? 'General Catalog');

                                $rollWeight = (float)($b['roll_weight'] ?? 0);
                                $wastageWeight = (float)($b['wastage_weight'] ?? 0);
                                $wastagePct = $rollWeight > 0 ? (($wastageWeight / $rollWeight) * 100) : 0;
                            ?>
                            <tr class="cutting-table-row">
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($b['name'] ?: $b['batch_number']) ?></div>
                                    <div class="small font-monospace text-primary"><?= e($b['batch_number']) ?></div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatDate($b['created_at']) ?></small>
                                </td>
                                <td>
                                    <a href="<?= url('production/programs/view/' . $b['program_id']) ?>" class="fw-semibold text-decoration-none text-dark">
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i><?= e($b['program_number']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($b['product_name'] ?: 'Apparel Product') ?></div>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                                            <?= e($catBreadcrumb) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $cRolls = $cuttingRollsMap[$b['id']] ?? [];
                                        if (empty($cRolls) && (!empty($b['roll_number']) || !empty($b['seller_roll_id']))) {
                                            $cRolls = [[
                                                'seller_roll_id' => $b['seller_roll_id'] ?? null,
                                                'roll_number'    => $b['roll_number'] ?? ('Roll #' . ($b['seller_roll_id'] ?? '')),
                                                'roll_color'     => $b['roll_color'] ?? '',
                                                'roll_design'    => $b['roll_design'] ?? '',
                                                'roll_weight'    => $b['roll_weight'] ?? 0,
                                                'roll_price'     => $b['roll_price'] ?? 0,
                                            ]];
                                        }
                                    ?>
                                    <?php if (!empty($cRolls)): ?>
                                        <?php if (count($cRolls) === 1): ?>
                                             <?php $r = $cRolls[0]; ?>
                                            <div class="fw-semibold text-dark">
                                                <i class="bi bi-circle-square text-info me-1"></i><?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('Roll #' . ($r['seller_roll_id'] ?? ''))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= e($r['roll_color'] ?: 'Standard') ?> &bull; <?= e($r['roll_design'] ?: 'Plain') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= number_format($rollWeight, 2) ?> kg / <?= formatCurrency((float)((float)($b['roll_price'] ?? 0))) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark mb-1">
                                                <i class="bi bi-layers-fill text-info me-1"></i><?= count($cRolls) ?> Rolls Linked
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <?php foreach ($cRolls as $r): ?>
                                                    <span class="badge bg-light text-dark border small" title="<?= e($r['seller_name'] ?? 'Supplier') ?> - <?= number_format((float)($r['roll_weight'] ?? 0), 2) ?> kg">
                                                        <?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('#' . ($r['seller_roll_id'] ?? ''))) ?>
                                                        <?php if (!empty($r['roll_color'])): ?> (<?= e($r['roll_color']) ?>)<?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="small text-muted">
                                                Total: <?= number_format($rollWeight, 2) ?> kg / <?= formatCurrency((float)((float)($b['roll_price'] ?? 0))) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">No roll linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-6"><?= number_format((int)$b['cut_qty']) ?> <small class="text-muted">pcs</small></div>
                                    <div class="small text-muted">
                                        <?php if ((int)($b['wastage_qty'] ?? 0) > 0): ?>
                                            <span class="text-warning"><i class="bi bi-trash3 me-1"></i><?= number_format((int)$b['wastage_qty']) ?> cutting wastage</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div><strong class="text-danger"><?= number_format($wastageWeight, 2) ?></strong> <small class="text-muted">kg</small></div>
                                    <?php 
                                        $wCost = (float)($b['wastage_cost'] ?? 0);
                                        if ($wCost <= 0 && $rollWeight > 0) {
                                            $wCost = $wastageWeight * ((float)($b['roll_price'] ?? 0) / $rollWeight);
                                        }
                                    ?>
                                    <div class="small text-danger fw-semibold">
                                        <?= formatCurrency((float)($wCost)) ?> <span class="text-muted fw-normal">(<?= number_format($wastagePct, 1) ?>%)</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary"><?= formatCurrency((float)((float)($b['cost_per_piece'] ?? 0))) ?></div>
                                    <small class="text-muted">per cut piece</small>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span>
                                </td>
                                <td><?= renderConcernBadge($b) ?></td>
                                <td class="text-end">
                                    <div class="action-btns-group justify-content-end">
                                        <a href="<?= url('production/cutting/view/' . $b['id']) ?>" class="btn btn-action-icon btn-outline-info" title="View Cutting Batch">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('cutting', 'edit')): ?>
                                        <a href="<?= url('production/cutting/edit/' . $b['id']) ?>" class="btn btn-action-icon btn-outline-primary" title="Edit Batch">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$isOutwarded && hasPermission('stitching', 'create')): ?>
                                        <a href="<?= url('production/stitching/create?cutting_batch_id=' . $b['id']) ?>" class="btn btn-action-icon btn-outline-warning" title="Create Stitching Job">
                                            <i class="bi bi-threads"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$isOutwarded && hasPermission('cutting', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/cutting/delete/' . $b['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Cutting Batch \'<?= e(addslashes($b['name'] ?: $b['batch_number'])) ?>\'?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-action-icon btn-outline-danger" title="Delete Cutting Batch">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

<!-- Cutting Pagination Controls Card -->
<div class="card bg-white mt-4 shadow-sm border" id="cuttingPaginationFooter">
    <div class="card-body py-3 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="cuttingPageInfo">
                Showing 1 to <?= min(10, count($batches)) ?> of <?= count($batches) ?> cutting batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="cuttingPageSizeSelect" class="form-select form-select-sm" style="width: auto; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; color: #334155; padding-left: 0.75rem; padding-right: 2.25rem;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Cutting Batches Pagination">
            <ul class="pagination pagination-modern mb-0" id="cuttingPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let cuttingPagination;
function initCuttingPagination() {
    const searchInput = document.getElementById('cuttingSearchInput');
    const tableBody = document.getElementById('cuttingTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        cuttingPagination = window.initClientPagination({
            container: '#cuttingTableBody',
            itemSelector: 'tr.cutting-table-row',
            footer: '#cuttingPaginationFooter',
            infoEl: '#cuttingPageInfo',
            paginationList: '#cuttingPaginationList',
            pageSizeSelect: '#cuttingPageSizeSelect',
            emptyStateEl: '#noLiveCuttingResultsRow',
            pageSize: 10,
            itemName: 'cutting batches'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('tr.cutting-table-row');
            const totalCount = rows.length;

            if (cuttingPagination) {
                visibleCount = cuttingPagination.filter(row => {
                    if (!query) return true;
                    const text = (row.textContent || '').toLowerCase();
                    return text.includes(query);
                });
            } else {
                rows.forEach(function(row) {
                    const text = (row.textContent || '').toLowerCase();
                    if (!query || text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            let noResultRow = document.getElementById('noLiveCuttingResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveCuttingResultsRow';
                    noResultRow.innerHTML = `<td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No cutting batches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No cutting batches matching "${searchInput.value}"`;
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        searchInput.addEventListener('input', filterRows);
        searchInput.addEventListener('keyup', filterRows);
        if (searchInput.value.trim() !== '') {
            filterRows();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCuttingPagination);
} else {
    initCuttingPagination();
}
</script>

<!-- Cutting Outwards History Card -->
<?php if (!empty($cuttingOutwards)): ?>
<div class="card shadow-sm border mt-4 mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Cutting Outwards to Other Concerns (<?= count($cuttingOutwards) ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Outward #</th>
                    <th>Date</th>
                    <th>Destination Concern</th>
                    <th>Cutting Batches</th>
                    <th class="text-center">Batches</th>
                    <th class="text-end">Total Cut Qty</th>
                    <th>Vehicle / Driver</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuttingOutwards as $co): ?>
                    <tr>
                        <td class="fw-bold font-monospace text-primary"><?= e($co['outward_number']) ?></td>
                        <td><?= e(date('d M Y', strtotime($co['outward_date']))) ?></td>
                        <td><span class="badge bg-info-subtle text-info-emphasis border"><?= e($co['destination_concern_name'] ?? 'Concern') ?></span></td>
                        <td class="font-monospace text-muted"><?= e($co['batch_numbers_csv'] ?? '') ?></td>
                        <td class="text-center fw-bold"><?= (int)$co['total_batches'] ?></td>
                        <td class="text-end fw-bold text-success"><?= number_format((int)$co['total_cut_qty']) ?> pcs</td>
                        <td><?= e(trim(($co['vehicle_number'] ?? '') . ' ' . ($co['driver_name'] ?? '')) ?: '—') ?></td>
                        <td><?= e($co['notes'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (hasPermission('cutting', 'create')): ?>
<!-- Modal: Cutting Outward (Send Cutting Batches -> Other Concern) -->
<div class="modal fade" id="cuttingOutwardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-truck me-2"></i>Cutting Outward to Other Concern</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('allmodules/cutting/outward/store') ?>" id="cuttingOutwardForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Destination Concern <span class="text-danger">*</span></label>
                            <select name="destination_concern_id" class="form-select fw-semibold" required>
                                <option value="">-- Select Target Concern --</option>
                                <?php foreach ($targetConcerns ?? [] as $tc): ?>
                                    <option value="<?= (int)$tc['id'] ?>"><?= e($tc['name']) ?><?= !empty($tc['code']) ? ' (' . e($tc['code']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Vehicle / Carrier</label>
                            <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. TN39...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Driver</label>
                            <input type="text" name="driver_name" class="form-control" placeholder="Driver name">
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <label class="form-label fw-bold text-dark mb-1" for="cuttingOutwardScanInput">
                            <i class="bi bi-scissors text-primary me-1"></i>Scan / Select Cutting Batch
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <select id="cuttingOutwardSelect" class="form-select" onchange="selectCuttingBatchForOutward(this.value)">
                                    <option value="">-- Quick Select Eligible Cutting Batch --</option>
                                    <?php foreach ($availableOutwardBatches ?? [] as $ab): ?>
                                        <option value="<?= e($ab['batch_number']) ?>">
                                            <?= e($ab['batch_number']) ?> — <?= e($ab['name'] ?: $ab['batch_number']) ?> (<?= number_format((int)$ab['cut_qty']) ?> pcs)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="cuttingOutwardScanInput" class="form-control font-monospace fw-bold" placeholder="Or scan / enter Cutting Batch # or Name..." autocomplete="off">
                                    <button type="button" class="btn btn-dark fw-semibold" onclick="scanCuttingForOutward()">Add</button>
                                </div>
                            </div>
                        </div>
                        <div id="cuttingOutwardScanMsg" class="small mt-2 text-muted">Only active cutting batches not yet stitched or outwarded can be added.</div>
                    </div>

                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch #</th>
                                    <th>Batch Name</th>
                                    <th>Program #</th>
                                    <th>Product</th>
                                    <th class="text-end">Cut Qty</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cuttingOutwardItemsBody">
                                <tr id="cuttingOutwardEmptyRow"><td colspan="6" class="text-center text-muted py-4">No cutting batches added yet.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Outward remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-semibold px-4"><i class="bi bi-truck me-1"></i>Create Cutting Outward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const cuttingOutwardMap = new Map();

function selectCuttingBatchForOutward(batchNumber) {
    if (!batchNumber) return;
    const input = document.getElementById('cuttingOutwardScanInput');
    if (input) input.value = batchNumber;
    scanCuttingForOutward();
    const sel = document.getElementById('cuttingOutwardSelect');
    if (sel) sel.value = '';
}

function scanCuttingForOutward() {
    const input = document.getElementById('cuttingOutwardScanInput');
    const msgEl = document.getElementById('cuttingOutwardScanMsg');
    const q = (input?.value || '').trim();
    if (!q) return;

    if (msgEl) msgEl.innerHTML = '<span class="text-muted">Checking Cutting Batch records...</span>';
    fetch(`<?= url('api/allmodules/cutting/outward-scan') ?>?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(res => {
            if (!res || !res.success || !res.data) {
                if (msgEl) msgEl.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>${res?.message || 'Cutting batch not eligible.'}</span>`;
                return;
            }
            const d = res.data;
            if (cuttingOutwardMap.has(d.cutting_batch_id)) {
                if (msgEl) msgEl.innerHTML = `<span class="text-warning-emphasis fw-semibold">Batch ${d.batch_number} is already added to this outward.</span>`;
                input.value = '';
                return;
            }
            cuttingOutwardMap.set(d.cutting_batch_id, d);
            renderCuttingOutwardRows();
            input.value = '';
            input.focus();
            if (msgEl) msgEl.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Added ${d.batch_number} (${d.cut_qty} pcs).</span>`;
        })
        .catch(() => {
            if (msgEl) msgEl.innerHTML = '<span class="text-danger">Error checking Cutting Batch records.</span>';
        });
}

function removeCuttingOutwardItem(id) {
    cuttingOutwardMap.delete(id);
    renderCuttingOutwardRows();
}

function renderCuttingOutwardRows() {
    const tbody = document.getElementById('cuttingOutwardItemsBody');
    if (!tbody) return;
    if (!cuttingOutwardMap.size) {
        tbody.innerHTML = '<tr id="cuttingOutwardEmptyRow"><td colspan="6" class="text-center text-muted py-4">No cutting batches added yet.</td></tr>';
        return;
    }
    let html = '';
    cuttingOutwardMap.forEach((d, id) => {
        html += `<tr>
            <td class="fw-bold font-monospace">${d.batch_number}<input type="hidden" name="cutting_batch_ids[]" value="${id}"></td>
            <td>${d.batch_name || ''}</td>
            <td class="font-monospace small">${d.program_number || '—'}</td>
            <td>${d.product_name || ''}</td>
            <td class="text-end font-monospace fw-bold">${Number(d.cut_qty || 0).toLocaleString()} pcs</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCuttingOutwardItem(${id})"><i class="bi bi-x"></i></button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

document.getElementById('cuttingOutwardScanInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        scanCuttingForOutward();
    }
});

document.getElementById('cuttingOutwardForm')?.addEventListener('submit', (e) => {
    if (!cuttingOutwardMap.size) {
        e.preventDefault();
        alert('Please add at least one Cutting Batch to outward.');
    }
});
</script>
<?php endif; ?>
