<?php
// Production Unit Customer Returns & Exchanges View
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-return-left text-danger me-2"></i>Customer Returns &amp; Exchanges
            </h4>
            <span class="text-muted small">Manage returned goods, quality staging queue, inventory restock, and damage module routing</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/returns/create') ?>" class="btn btn-danger btn-sm fw-semibold shadow-sm px-3">
                <i class="bi bi-plus-circle me-1"></i> Record Return / Exchange
            </a>
        </div>
    </div>

    <!-- KPI Summary Analytics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Returns -->
        <div class="col-xl-3 col-md-6">
            <div class="card revenue-kpi-card border-0 border-start border-danger border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Total Returns</div>
                        <div class="fw-bold text-danger fs-3 mt-1"><?= number_format($stats['total_returns'] ?? 0) ?></div>
                        <div class="small text-muted mt-2 text-truncate">
                            <i class="bi bi-receipt-cutoff text-danger me-1"></i><?= number_format($stats['total_pieces'] ?? 0) ?> total pieces returned
                        </div>
                    </div>
                    <div class="bg-danger-subtle text-danger revenue-kpi-icon">
                        <i class="bi bi-receipt-cutoff fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. In Staging (Awaiting Triage) -->
        <div class="col-xl-3 col-md-6">
            <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Awaiting Triage</div>
                        <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= number_format($stats['pending_pieces'] ?? 0) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                        <div class="small text-muted mt-2 text-truncate">
                            <i class="bi bi-hourglass-split text-warning me-1"></i>Held in staging (Not yet in stock)
                        </div>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Restocked to Inventory -->
        <div class="col-xl-3 col-md-6">
            <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Added to Stock</div>
                        <div class="fw-bold text-success fs-3 mt-1"><?= number_format($stats['restocked_pieces'] ?? 0) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                        <div class="small text-muted mt-2 text-truncate">
                            <i class="bi bi-box-seam text-success me-1"></i>Inspected &amp; returned to stock
                        </div>
                    </div>
                    <div class="bg-success-subtle text-success revenue-kpi-icon">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Damage Module Quarantined -->
        <div class="col-xl-3 col-md-6">
            <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Damage Module</div>
                        <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= number_format($stats['damaged_pieces'] ?? 0) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                        <div class="small text-muted mt-2 text-truncate">
                            <i class="bi bi-shield-slash text-info me-1"></i>Quarantined defect pieces
                        </div>
                    </div>
                    <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                        <i class="bi bi-shield-slash fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/returns') ?>" class="row g-2 align-items-center">
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="returnsSearchInput" class="form-control form-control-sm border-start-0" 
                               placeholder="Search Return #, Bill #, Piece Tag, Customer..." 
                               value="<?= e($search) ?>" oninput="filterReturnsLive(this.value)">
                    </div>
                </div>
                <div class="col-lg-2 col-md-3">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Flows --</option>
                        <option value="REFUND" <?= $type === 'REFUND' ? 'selected' : '' ?>>Flow 1: Fully Refunded</option>
                        <option value="EXCHANGE" <?= $type === 'EXCHANGE' ? 'selected' : '' ?>>Flow 2: Product Options (Exchange)</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="PENDING_INSPECTION" <?= $status === 'PENDING_INSPECTION' ? 'selected' : '' ?>>Awaiting Triage</option>
                        <option value="COMPLETED" <?= $status === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" title="From Date" onchange="this.form.submit()">
                </div>
                <div class="col-lg-2 col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" title="To Date" onchange="this.form.submit()">
                </div>
                <div class="col-lg-1 col-md-3 text-end d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i></button>
                    <?php if ($search || $type || $status || $dateFrom || $dateTo): ?>
                        <a href="<?= url('production/returns') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-list-check text-danger me-2"></i>Returns &amp; Credit Notes Log (<?= count($returns) ?>)
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($returns)): ?>
                <div class="text-center py-5 text-muted">
                    <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                        <i class="bi bi-inbox fs-1 text-secondary"></i>
                    </div>
                    <div class="fw-bold fs-6">No Return or Exchange Records Found</div>
                    <p class="small mb-3">When customers return items, you can scan pieces and stage them for quality triage.</p>
                    <a href="<?= url('production/returns/create') ?>" class="btn btn-sm btn-danger px-3 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Record Return / Exchange
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="returnsTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Return #</th>
                                <th>Original Bill #</th>
                                <th>Customer / Company</th>
                                <th class="text-center">Return Flow</th>
                                <th class="text-center">Triage Status</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Taxable</th>
                                <th class="text-end">GST Reversal</th>
                                <th class="text-end">Net Refund</th>
                                <th>Date</th>
                                <th>Concern</th>
                                <th class="text-end" style="min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="returnsTableBody">
                            <?php foreach ($returns as $r): ?>
                                <tr class="return-row">
                                    <td>
                                        <a href="<?= url('production/returns/view/' . $r['id']) ?>" class="fw-bold font-monospace text-danger text-decoration-none">
                                            <?= e($r['return_number']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($r['bill_id']): ?>
                                            <a href="<?= url('production/billing/view/' . $r['bill_id']) ?>" class="font-monospace text-primary text-decoration-none small">
                                                <?= e($r['bill_number']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small font-monospace"><?= e($r['bill_number'] ?? '-') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($r['customer_name']) ?></div>
                                        <?php if ($r['customer_phone']): ?>
                                            <small class="text-muted"><?= e($r['customer_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['return_type'] === 'EXCHANGE'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <i class="bi bi-arrow-left-right me-1"></i> Product Exchange
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="bi bi-cash-coin me-1"></i> Fully Refunded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['status'] === 'PENDING_INSPECTION'): ?>
                                            <span class="badge bg-warning text-dark border border-warning px-2 py-1">
                                                <i class="bi bi-hourglass-split me-1"></i> Awaiting Triage (<?= (int)($r['pending_triage_count'] ?? $r['total_returned_qty']) ?> pcs)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Completed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= (int)$r['total_returned_qty'] ?> pcs</td>
                                    <td class="text-end"><?= formatCurrency((float)((float)$r['subtotal'])) ?></td>
                                    <td class="text-end text-muted"><?= formatCurrency((float)((float)$r['gst_reversal_amount'])) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= formatCurrency((float)((float)$r['net_amount'])) ?></td>
                                    <td class="small text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                    <td><?= renderConcernBadge($r) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <?php if ($r['status'] === 'PENDING_INSPECTION'): ?>
                                                <a href="<?= url('production/returns/view/' . $r['id']) ?>" class="btn btn-sm btn-primary fw-semibold px-2" title="Inspect and Triage Items">
                                                    <i class="bi bi-clipboard2-check me-1"></i> Triage
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= url('production/returns/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= url('production/returns/print/' . $r['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Credit Note">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$paginationPrefix = 'returns';
$paginationItemName = 'returns';
$paginationTotal = count($returns ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>

<script>
let returnsPagination = null;
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function') {
        returnsPagination = window.initClientPagination({
            container: '#returnsTableBody',
            itemSelector: '.return-row',
            footer: '#returnsPaginationFooter',
            infoEl: '#returnsPageInfo',
            paginationList: '#returnsPaginationList',
            pageSizeSelect: '#returnsPageSizeSelect',
            pageSize: 10,
            itemName: 'returns'
        });
    }
});

function filterReturnsLive(query) {
    const q = (query || '').toLowerCase().trim();
    const matchFn = (r) => {
        const text = (r.textContent || '').toLowerCase();
        return !q || text.includes(q);
    };
    if (returnsPagination) {
        returnsPagination.filter(matchFn);
    } else {
        document.querySelectorAll('#returnsTableBody .return-row').forEach(r => {
            r.style.display = matchFn(r) ? '' : 'none';
        });
    }
}
</script>
