<?php
// Asset Register Index View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-tools text-primary me-2"></i>Fixed Assets &amp; Plant Machinery</h4>
        <div class="d-flex gap-2">
            <a href="<?= url('assets/create') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Register New Asset
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Total Assets</div>
                        <div class="fw-bold text-primary fs-3 mt-1"><?= number_format($metrics['total']) ?></div>
                        <div class="small text-muted mt-2 text-truncate"><i class="bi bi-cpu text-primary me-1"></i>Registered equipment</div>
                    </div>
                    <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                        <i class="bi bi-cpu fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Active In-Use</div>
                        <div class="fw-bold text-success fs-3 mt-1"><?= number_format($metrics['in_use']) ?></div>
                        <div class="small text-muted mt-2 text-truncate"><i class="bi bi-check-circle text-success me-1"></i>Operational machinery</div>
                    </div>
                    <div class="bg-success-subtle text-success revenue-kpi-icon">
                        <i class="bi bi-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Under Maintenance</div>
                        <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= number_format($metrics['in_maintenance']) ?></div>
                        <div class="small text-muted mt-2 text-truncate"><i class="bi bi-wrench text-warning me-1"></i>Servicing &amp; repair</div>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                        <i class="bi bi-wrench fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-2" style="min-width: 0;">
                        <div class="text-uppercase fw-semibold text-muted small">Total Book Value</div>
                        <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= formatCurrency((float)($metrics['total_book_val'])) ?></div>
                        <div class="small text-muted mt-2 text-truncate"><i class="bi bi-currency-rupee text-info me-1"></i>Asset valuation</div>
                    </div>
                    <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                        <i class="bi bi-currency-rupee fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('assets') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Code, Name, Brand, Serial #, QR..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="IN_USE" <?= $filters['status'] === 'IN_USE' ? 'selected' : '' ?>>In Use / Operational</option>
                        <option value="IDLE" <?= $filters['status'] === 'IDLE' ? 'selected' : '' ?>>Idle / Standby</option>
                        <option value="IN_MAINTENANCE" <?= $filters['status'] === 'IN_MAINTENANCE' ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="DAMAGED" <?= $filters['status'] === 'DAMAGED' ? 'selected' : '' ?>>Damaged</option>
                        <option value="DISPOSED" <?= $filters['status'] === 'DISPOSED' ? 'selected' : '' ?>>Disposed / Scrapped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="condition_state" class="form-select form-select-sm">
                        <option value="">All Conditions</option>
                        <option value="EXCELLENT" <?= $filters['condition_state'] === 'EXCELLENT' ? 'selected' : '' ?>>Excellent</option>
                        <option value="GOOD" <?= $filters['condition_state'] === 'GOOD' ? 'selected' : '' ?>>Good</option>
                        <option value="FAIR" <?= $filters['condition_state'] === 'FAIR' ? 'selected' : '' ?>>Fair</option>
                        <option value="POOR" <?= $filters['condition_state'] === 'POOR' ? 'selected' : '' ?>>Poor</option>
                        <option value="CRITICAL" <?= $filters['condition_state'] === 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-funnel"></i></button>
                    <a href="<?= url('assets') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Assets Data Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Asset Code</th>
                            <th>Asset Name &amp; Category</th>
                            <th>Brand &amp; Model</th>
                            <th>Serial #</th>
                            <th>Location / Area</th>
                            <th>Status</th>
                            <th>Condition</th>
                            <th>Book Value</th>
                            <th>Concern</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assetsTableBody">
                        <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-tools fs-1 d-block mb-2 text-secondary"></i>
                                No equipment or fixed assets registered yet.
                                <div class="mt-2">
                                    <a href="<?= url('assets/create') ?>" class="btn btn-sm btn-primary">Register First Asset</a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($assets as $ast): ?>
                        <tr class="asset-row">
                            <td class="ps-3 fw-bold text-primary font-monospace">
                                <a href="<?= url('assets/view/' . $ast['id']) ?>" class="text-decoration-none">
                                    <?= e($ast['asset_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($ast['name']) ?></div>
                                <small class="text-muted"><i class="bi <?= e($ast['category_icon'] ?: 'bi-box-seam') ?> me-1"></i><?= e($ast['category_name']) ?></small>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= e($ast['brand'] ?: '—') ?></div>
                                <small class="text-muted"><?= e($ast['model_number'] ?: '') ?></small>
                            </td>
                            <td>
                                <span class="font-monospace small"><?= e($ast['serial_number'] ?: '—') ?></span>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= e($ast['location_type']) ?></div>
                                <small class="text-muted"><?= e($ast['floor_area'] ?: 'Floor #1') ?></small>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match ($ast['status']) {
                                    'IN_USE'         => 'bg-success text-white',
                                    'IN_MAINTENANCE' => 'bg-warning text-dark',
                                    'IDLE'           => 'bg-secondary text-white',
                                    'DAMAGED'        => 'bg-danger text-white',
                                    default          => 'bg-dark text-white',
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= e($ast['status']) ?></span>
                            </td>
                            <td>
                                <?php
                                $condBadge = match ($ast['condition_state']) {
                                    'EXCELLENT' => 'text-success',
                                    'GOOD'      => 'text-primary',
                                    'FAIR'      => 'text-warning',
                                    default     => 'text-danger',
                                };
                                ?>
                                <span class="small fw-bold <?= $condBadge ?>"><?= e($ast['condition_state']) ?></span>
                            </td>
                            <td class="fw-semibold"><?= formatCurrency((float)($ast['current_book_value'])) ?></td>
                            <td><?= renderConcernBadge($ast) ?></td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('assets/view/' . $ast['id']) ?>" class="btn btn-outline-secondary" title="View Passport">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('assets/edit/' . $ast['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('assets/print/' . $ast['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print QR Sticker">
                                        <i class="bi bi-qr-code"></i>
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
    </div>
</div>

<?php
$paginationPrefix = 'assets';
$paginationItemName = 'assets';
$paginationTotal = count($assets ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function') {
        window.initClientPagination({
            container: '#assetsTableBody',
            itemSelector: '.asset-row',
            footer: '#assetsPaginationFooter',
            infoEl: '#assetsPageInfo',
            paginationList: '#assetsPaginationList',
            pageSizeSelect: '#assetsPageSizeSelect',
            pageSize: 10,
            itemName: 'assets'
        });
    }
});
</script>
