<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Production Analytics & Reports</h2>
        <span class="text-muted">Efficiency metrics, cutting yield, wastage rate, and QC pass percentages</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('export') ?>" class="btn btn-primary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" title="Open Data Export Center">
            <i class="bi bi-file-earmark-arrow-down"></i> Export Center
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm px-3" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Top Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Planned Production</div>
                    <div class="fw-bold text-primary fs-3 mt-1"><?= number_format($totalPrograms['planned'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-kanban me-1 text-primary"></i><?= number_format($totalPrograms['cnt'] ?? 0) ?> total programs</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-kanban fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Finished &amp; Delivered</div>
                    <div class="fw-bold text-success fs-3 mt-1"><?= number_format($totalPrograms['completed'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <?php 
                        $compRate = ($totalPrograms['planned'] ?? 0) > 0 ? round((($totalPrograms['completed'] ?? 0) / $totalPrograms['planned']) * 100, 1) : 0;
                    ?>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-check-circle me-1 text-success"></i><?= $compRate ?>% overall completion rate</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Cutting Wastage Rate</div>
                    <?php 
                        $cutTotal = ($totalCutting['cut'] ?? 0) + ($totalCutting['wastage'] ?? 0);
                        $wasteRate = $cutTotal > 0 ? round((($totalCutting['wastage'] ?? 0) / $cutTotal) * 100, 1) : 0;
                    ?>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= $wasteRate ?>%</div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-scissors me-1 text-warning"></i><?= number_format($totalCutting['wastage'] ?? 0) ?> pcs wastage / <?= number_format($totalCutting['cut'] ?? 0) ?> cut</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-scissors fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">QC Pass Rate</div>
                    <?php 
                        $qcTotal = $totalQC['inspected'] ?? 0;
                        $passRate = $qcTotal > 0 ? round((($totalQC['passed'] ?? 0) / $qcTotal) * 100, 1) : 0;
                    ?>
                    <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= $passRate ?>%</div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-patch-check me-1 text-info"></i><?= number_format($totalQC['passed'] ?? 0) ?> passed / <?= number_format($totalQC['rejected'] ?? 0) ?> rejected</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-patch-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Production Unit Performance -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-buildings me-2 text-primary"></i>Output by Production Unit</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Unit Name</th>
                        <th>Programs Run</th>
                        <th>Planned Output</th>
                        <th>Completed Output</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    <?php if (empty($unitStats)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No unit metrics recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($unitStats as $u): ?>
                            <?php 
                                $uPct = $u['total_planned'] > 0 ? round(($u['total_completed'] / $u['total_planned']) * 100) : 0;
                            ?>
                            <tr class="report-unit-row">
                                <td class="fw-bold"><?= e($u['unit_name']) ?></td>
                                <td><?= number_format($u['program_count']) ?></td>
                                <td><?= number_format($u['total_planned']) ?> pcs</td>
                                <td class="text-success fw-bold"><?= number_format($u['total_completed']) ?> pcs</td>
                                <td style="min-width: 150px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, $uPct) ?>%"></div>
                                        </div>
                                        <span class="small fw-bold"><?= $uPct ?>%</span>
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

<?php
$paginationPrefix = 'reportsUnit';
$paginationItemName = 'units';
$paginationTotal = count($unitStats ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function') {
        window.initClientPagination({
            container: '#reportsTableBody',
            itemSelector: '.report-unit-row',
            footer: '#reportsUnitPaginationFooter',
            infoEl: '#reportsUnitPageInfo',
            paginationList: '#reportsUnitPaginationList',
            pageSizeSelect: '#reportsUnitPageSizeSelect',
            pageSize: 10,
            itemName: 'units'
        });
    }
});
</script>

<!-- Manufacturing Pipeline Queue Status -->
<div class="card">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-kanban me-2 text-primary"></i>Live Pipeline Work-in-Progress</h5>
    </div>
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-secondary"><?= $stageCounts['draft_programs'] ?? 0 ?></div>
                    <small class="text-muted">Draft Programs</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-primary"><?= $stageCounts['scheduled_programs'] ?? 0 ?></div>
                    <small class="text-muted">Scheduled</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-info"><?= $stageCounts['active_cutting'] ?? 0 ?></div>
                    <small class="text-muted">Active Cutting</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-warning"><?= $stageCounts['active_stitching'] ?? 0 ?></div>
                    <small class="text-muted">Active Stitching</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-danger"><?= $stageCounts['active_qc'] ?? 0 ?></div>
                    <small class="text-muted">Pending QC</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-success"><?= $stageCounts['ready_batches'] ?? 0 ?></div>
                    <small class="text-muted">Ready to Dispatch</small>
                </div>
            </div>
        </div>
    </div>
</div>
