<style>
.stage-card {
    transition: all 0.25s ease-in-out;
    border: 1px solid #e2e8f0;
    cursor: pointer;
}
.stage-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    border-color: #94a3b8;
}
.stage-icon-circle {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,0.06);
    background: #ffffff;
    border-radius: 12px;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.07);
}
</style>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h2 class="mb-0 fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Production Dashboard</h2>
            <?php 
            $currUser = currentUser();
            $assignedPU = $currUser['production_unit_name'] ?? null;
            if ($assignedPU): ?>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1.5 rounded-pill fs-7 d-inline-flex align-items-center">
                    <i class="bi bi-buildings-fill me-1"></i> Unit: <?= e($assignedPU) ?>
                </span>
            <?php elseif (isSuperAdmin()): ?>
            <?php endif; ?>
        </div>
        </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#qrStatusFinderModal"><i class="bi bi-qr-code-scan text-primary"></i><span>QR Status Finder</span></button>
        <a href="<?= url('export') ?>" class="btn btn-primary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" title="Open Data Export Center">
            <i class="bi bi-file-earmark-arrow-down"></i> Export Reports
        </a>
        <span class="text-muted small d-none d-sm-inline ms-1">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
        </span>
    </div>
</div>

<?php if (!empty($productionUnitProfile)): ?>
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <?php if (!empty($productionUnitProfile['logo_url'])): ?>
            <img src="<?= e($productionUnitProfile['logo_url']) ?>" alt="<?= e($productionUnitProfile['name'] ?? 'Production unit') ?> logo" style="max-width:140px;max-height:64px;object-fit:contain;">
        <?php else: ?>
            <div class="rounded-circle bg-primary-subtle text-primary p-3"><i class="bi bi-gear-wide-connected fs-4"></i></div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 fw-bold"><?= e($productionUnitProfile['name'] ?? 'Production Unit') ?></h5>
                <?php if (!empty($productionUnitProfile['code'])): ?><span class="badge bg-light text-dark border"><?= e($productionUnitProfile['code']) ?></span><?php endif; ?>
            </div>
            <div class="small text-muted mt-1">
                <?php if (!empty($productionUnitProfile['manager_name'])): ?>Manager: <?= e($productionUnitProfile['manager_name']) ?> · <?php endif; ?>
                <?php if (!empty($productionUnitProfile['address'])): ?><?= e($productionUnitProfile['address']) ?><?php endif; ?>
                <?php if (!empty($productionUnitProfile['phone'])): ?><?= !empty($productionUnitProfile['address']) ? ' · ' : '' ?><?= e($productionUnitProfile['phone']) ?><?php endif; ?>
                <?php if (!empty($productionUnitProfile['gst_number'])): ?><?= (!empty($productionUnitProfile['address']) || !empty($productionUnitProfile['phone'])) ? ' · ' : '' ?>GSTIN: <?= e($productionUnitProfile['gst_number']) ?><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($productionUnitProfile['id']) && hasPermission('dashboard', 'view') && (hasPermission('dashboard', 'edit') || hasPermission('production', 'edit'))): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/dashboard?edit_type=production_unit&edit_id=' . (int)$productionUnitProfile['id']) ?>"><i class="bi bi-pencil me-1"></i>Edit details</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- 1. KPI Stats Row -->
<div class="row g-3 mb-4">
    <!-- 1. Programs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-primary bg-primary-subtle text-primary p-2 rounded-circle">
                    <i class="bi bi-kanban fs-5"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary font-monospace">Programs</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($activePrograms['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($activePrograms['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">Programs</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Finished Pieces" class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i><?= number_format($activePrograms['total_completed'] ?? 0) ?></span>
                <span title="Total Planned Pieces" class="text-muted">/ <?= number_format($activePrograms['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- 2. Cutting -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-info bg-info-subtle text-info p-2 rounded-circle">
                    <i class="bi bi-scissors fs-5"></i>
                </div>
                <span class="badge bg-info-subtle text-info font-monospace">Cutting</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($pendingCutting['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($pendingCutting['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">Cutting Batches</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Cut Pieces" class="text-success fw-bold"><i class="bi bi-scissors me-1"></i><?= number_format($pendingCutting['total_completed'] ?? 0) ?></span>
                <span title="Planned Pieces" class="text-muted">/ <?= number_format($pendingCutting['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- 3. Stitching -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-warning bg-warning-subtle text-warning p-2 rounded-circle">
                    <i class="bi bi-threads fs-5"></i>
                </div>
                <span class="badge bg-warning-subtle text-warning font-monospace">Stitching</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($pendingStitching['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($pendingStitching['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">Stitching Batches</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Stitched Pieces" class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i><?= number_format($pendingStitching['total_completed'] ?? 0) ?></span>
                <span title="Total Received Pieces" class="text-muted">/ <?= number_format($pendingStitching['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- 4. QC -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-danger bg-danger-subtle text-danger p-2 rounded-circle">
                    <i class="bi bi-patch-check fs-5"></i>
                </div>
                <span class="badge bg-danger-subtle text-danger font-monospace">QC</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($pendingQC['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($pendingQC['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">QC Inspections</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Passed Pieces" class="text-success fw-bold"><i class="bi bi-shield-check me-1"></i><?= number_format($pendingQC['total_completed'] ?? 0) ?></span>
                <span title="Total Inspected Pieces" class="text-muted">/ <?= number_format($pendingQC['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- 5. Packed / Ready Dispatch -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-success bg-success-subtle text-success p-2 rounded-circle">
                    <i class="bi bi-box-seam fs-5"></i>
                </div>
                <span class="badge bg-success-subtle text-success font-monospace">Packed</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($readyDispatch['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($readyDispatch['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">Packed Batches</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Dispatched Pieces" class="text-success fw-bold"><i class="bi bi-box-seam me-1"></i><?= number_format($readyDispatch['total_completed'] ?? 0) ?></span>
                <span title="Total Packed Pieces" class="text-muted">/ <?= number_format($readyDispatch['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>

    <!-- 6. Dispatches -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card p-3 shadow-sm h-100 bg-white border-0 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stat-icon icon-primary bg-primary-subtle text-primary p-2 rounded-circle">
                    <i class="bi bi-truck fs-5"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary font-monospace">Outward</span>
            </div>
            <div class="d-flex align-items-center justify-content-between my-2">
                <div>
                    <div class="fs-4 fw-bold text-dark mb-0"><?= number_format($monthOutward['count'] ?? 0) ?></div>
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 10px;">Total</div>
                </div>
                <div class="border-start ps-2 text-end">
                    <div class="fs-4 fw-bold text-success mb-0"><?= number_format($monthOutward['finished_count'] ?? 0) ?></div>
                    <div class="text-success small text-uppercase fw-semibold" style="font-size: 10px;">Finished</div>
                </div>
            </div>
            <div class="stat-label text-muted small text-uppercase fw-semibold mb-1">Dispatches to WH</div>
            <div class="small text-secondary pt-1 border-top d-flex justify-content-between align-items-center">
                <span title="Delivered Pieces" class="text-success fw-bold"><i class="bi bi-truck me-1"></i><?= number_format($monthOutward['total_completed'] ?? 0) ?></span>
                <span title="Total Dispatched Pieces" class="text-muted">/ <?= number_format($monthOutward['total_planned'] ?? 0) ?> pcs</span>
            </div>
        </div>
    </div>
</div>



<!-- ========================================================================= -->
<!-- 3. PRODUCTION ANALYTICS GRAPH UI (EVERY DETAIL VISUALIZED) -->
<!-- ========================================================================= -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Manufacturing Throughput Trends (Last 7 Days) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>Manufacturing Throughput Trends
                    </h5>
                  </div>
                <span class="badge bg-light text-secondary border font-monospace">7-Day Trend</span>
            </div>
            <div class="card-body p-3">
                <div style="height: 300px; position: relative;">
                    <canvas id="productionThroughputChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: QC Inspection & Quality Yield Distribution -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-shield-check text-success me-2"></i>Quality &amp; Yield Rate
                    </h5>
                   </div>
                <?php
                $inspectedTotal = (int)($qcBreakdown['total_inspected'] ?? 0);
                $passedTotal = (int)($qcBreakdown['total_passed'] ?? 0);
                $reworkTotal = (int)($qcBreakdown['total_rework'] ?? 0);
                $rejectedTotal = (int)($qcBreakdown['total_rejected'] ?? 0);
                $yieldPct = $inspectedTotal > 0 ? round(($passedTotal / $inspectedTotal) * 100, 1) : 100;
                ?>
                <span class="badge <?= $yieldPct >= 95 ? 'bg-success' : ($yieldPct >= 85 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                    <?= $yieldPct ?>% Yield
                </span>
            </div>
            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                <div style="height: 200px; width: 100%; position: relative;">
                    <canvas id="qcYieldChart"></canvas>
                </div>
                <div class="row w-100 text-center mt-3 g-2 border-top pt-2">
                    <div class="col-4">
                        <div class="text-muted small">Passed</div>
                        <strong class="text-success"><?= number_format($passedTotal) ?></strong>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Rework</div>
                        <strong class="text-warning"><?= number_format($reworkTotal) ?></strong>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Rejected</div>
                        <strong class="text-danger"><?= number_format($rejectedTotal) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- ========================================================================= -->
<!-- 5. CHART.JS INITIALIZATION SCRIPTS -->
<!-- ========================================================================= -->
<?php
$trendLabels = array_column($dailyTrends ?? [], 'label');
$trendCut = array_column($dailyTrends ?? [], 'cut');
$trendStitched = array_column($dailyTrends ?? [], 'stitched');
$trendQc = array_column($dailyTrends ?? [], 'qc_passed');
$trendOutward = array_column($dailyTrends ?? [], 'outward');

$wipLabels = array_keys($wipDistribution ?? []);
$wipValues = array_values($wipDistribution ?? []);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded');
        return;
    }

    // Common Chart Defaults
    Chart.defaults.font.family = "system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    Chart.defaults.color = '#64748b';

    // -------------------------------------------------------------
    // Chart 1: Manufacturing Throughput Trends (Line / Area Chart)
    // -------------------------------------------------------------
    const ctxThroughput = document.getElementById('productionThroughputChart');
    if (ctxThroughput) {
        new Chart(ctxThroughput, {
            type: 'line',
            data: {
                labels: <?= json_encode($trendLabels) ?>,
                datasets: [
                    {
                        label: 'Pieces Cut',
                        data: <?= json_encode($trendCut) ?>,
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Pieces Stitched',
                        data: <?= json_encode($trendStitched) ?>,
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'QC Passed',
                        data: <?= json_encode($trendQc) ?>,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Dispatched to WH',
                        data: <?= json_encode($trendOutward) ?>,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, usePointStyle: true, padding: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' pcs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            precision: 0,
                            callback: function(value) { return value + ' pcs'; }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Chart 2: QC Inspection & Quality Yield (Doughnut Chart)
    // -------------------------------------------------------------
    const ctxQc = document.getElementById('qcYieldChart');
    if (ctxQc) {
        const passedQty = <?= (int)($qcBreakdown['total_passed'] ?? 0) ?>;
        const reworkQty = <?= (int)($qcBreakdown['total_rework'] ?? 0) ?>;
        const rejectedQty = <?= (int)($qcBreakdown['total_rejected'] ?? 0) ?>;
        const hasQcData = (passedQty + reworkQty + rejectedQty) > 0;

        new Chart(ctxQc, {
            type: 'doughnut',
            data: {
                labels: hasQcData ? ['Passed', 'Rework Required', 'Defect Rejected'] : ['No Inspection Records'],
                datasets: [{
                    data: hasQcData ? [passedQty, reworkQty, rejectedQty] : [1],
                    backgroundColor: hasQcData ? ['#16a34a', '#f59e0b', '#dc2626'] : ['#e2e8f0'],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: hasQcData,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + Number(context.raw).toLocaleString() + ' pcs';
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Chart 3: Pipeline WIP Stage Distribution (Horizontal Bar Chart)
    // -------------------------------------------------------------
    const ctxWip = document.getElementById('wipDistributionChart');
    if (ctxWip) {
        new Chart(ctxWip, {
            type: 'bar',
            data: {
                labels: <?= json_encode($wipLabels) ?>,
                datasets: [{
                    label: 'Garments in Pipeline',
                    data: <?= json_encode($wipValues) ?>,
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.85)',
                        'rgba(6, 182, 212, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                        'rgba(34, 197, 94, 0.85)',
                        'rgba(99, 102, 241, 0.85)'
                    ],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Volume: ' + Number(context.raw).toLocaleString() + ' pcs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            precision: 0,
                            callback: function(value) { return value + ' pcs'; }
                        }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Chart 4: Active Program Target Fulfillment (Doughnut Gauge)
    // -------------------------------------------------------------
    const ctxProg = document.getElementById('programFulfillmentChart');
    if (ctxProg) {
        const completedPcs = <?= (int)($completedPcs ?? 0) ?>;
        const pendingPcs = <?= (int)($pendingPcs ?? 0) ?>;
        const hasProgData = (completedPcs + pendingPcs) > 0;

        new Chart(ctxProg, {
            type: 'doughnut',
            data: {
                labels: hasProgData ? ['Completed & QC Approved', 'Pending Production'] : ['No Active Targets'],
                datasets: [{
                    data: hasProgData ? [completedPcs, pendingPcs] : [1],
                    backgroundColor: hasProgData ? ['#16a34a', '#0284c7'] : ['#e2e8f0'],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '74%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true, padding: 12 }
                    },
                    tooltip: {
                        enabled: hasProgData,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + Number(context.raw).toLocaleString() + ' pcs';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

