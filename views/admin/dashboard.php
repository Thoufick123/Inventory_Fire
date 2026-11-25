<?php
$cards = $analytics['cards'] ?? [];
$charts = $analytics['charts'] ?? [];
$selectedConcernId = $analytics['selectedConcernId'] ?? 'all';
$selectedScopeName = $analytics['selectedScopeName'] ?? 'All Concerns (Overall)';
$selectedMonths = (int)($analytics['months'] ?? 12);
?>

<style>
.revenue-kpi-card {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    border-radius: 12px;
}
.revenue-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1.15rem rgba(15, 23, 42, 0.08) !important;
}
.dashboard-chart-card {
    border-radius: 12px;
}
.chart-canvas-wrap {
    position: relative;
    height: 310px;
}
.chart-canvas-wrap-sm {
    position: relative;
    height: 245px;
}
</style>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
        <p class="text-muted small mb-0">Concern-wise revenue overview, procurement vs selling performance, and workforce summary.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill" id="activeScopeBadge">
            <i class="bi bi-buildings me-1"></i>
            <span id="activeScopeLabel"><?= e($selectedScopeName) ?></span>
        </span>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. CONCERN & PERIOD FILTER BAR (BEFORE REVENUE CARDS)                     -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4 rounded-3">
    <div class="card-body py-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label for="dashboardConcernFilter" class="form-label small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-funnel me-1 text-primary"></i>Select Business Concern
                </label>
                <select id="dashboardConcernFilter" class="form-select shadow-xs">
                    <?php if (!empty($canSelectAllConcerns)): ?>
                        <option value="all" <?= $selectedConcernId === 'all' ? 'selected' : '' ?>>All Concerns (Overall)</option>
                    <?php endif; ?>
                    <?php foreach ($availableConcerns ?? [] as $concernItem): ?>
                        <?php $cid = (string)$concernItem['id']; ?>
                        <option value="<?= e($cid) ?>" <?= (string)$selectedConcernId === $cid ? 'selected' : '' ?>>
                            <?= e($concernItem['name']) ?> (<?= e($concernItem['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label for="dashboardMonthsFilter" class="form-label small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-calendar3 me-1 text-primary"></i>Graph Timeframe
                </label>
                <select id="dashboardMonthsFilter" class="form-select shadow-xs">
                    <option value="6" <?= $selectedMonths === 6 ? 'selected' : '' ?>>Last 6 Months</option>
                    <option value="12" <?= $selectedMonths === 12 ? 'selected' : '' ?>>Last 12 Months</option>
                </select>
            </div>
            <div class="col-md-3 col-lg-5 d-flex align-items-center justify-content-md-end gap-2">
                <div id="dashboardLoadingIndicator" class="d-none text-primary small fw-semibold">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Updating metrics...
                </div>
                <?php if (!empty($canSelectAllConcerns)): ?>
                <button type="button" id="btnResetConcernFilter" class="btn btn-outline-secondary btn-sm <?= $selectedConcernId === 'all' ? 'd-none' : '' ?>">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Show All Concerns
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. FOUR CORE REVENUE & ENTITY KPI CARDS                                   -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Concerns -->
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Concerns</div>
                    <div class="fw-bold text-primary fs-3 mt-1" id="kpiTotalConcerns">
                        <?= number_format((int)($cards['totalConcerns'] ?? 0)) ?>
                    </div>
                    <div class="small text-muted mt-2 text-truncate" id="kpiConcernsSubtitle" title="<?= e($cards['concernsSubtitle'] ?? '') ?>">
                        <i class="bi bi-buildings text-primary me-1"></i><?= e($cards['concernsSubtitle'] ?? 'All Active Business Entities') ?>
                    </div>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle flex-shrink-0">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Staff -->
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Staff</div>
                    <div class="fw-bold text-success fs-3 mt-1" id="kpiTotalStaff">
                        <?= number_format((int)($cards['totalStaff'] ?? 0)) ?>
                    </div>
                    <div class="small text-muted mt-2 text-truncate" id="kpiStaffSubtitle">
                        <i class="bi bi-people text-success me-1"></i><?= e($cards['staffSubtitle'] ?? 'Active Workforce Directory') ?>
                    </div>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle flex-shrink-0">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Purchase Amount -->
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Purchase Amount</div>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1" id="kpiTotalPurchaseAmount">
                        ₹<?= number_format((float)($cards['totalPurchaseAmount'] ?? 0), 2) ?>
                    </div>
                    <div class="small text-muted mt-2" id="kpiPurchaseSubtitle">
                        <i class="bi bi-cart-check text-warning me-1"></i><?= number_format((int)($cards['purchaseCount'] ?? 0)) ?> Purchase Orders
                    </div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis p-3 rounded-circle flex-shrink-0">
                    <i class="bi bi-cart-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Selling Amount -->
    <div class="col-sm-6 col-xl-3">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Selling Amount</div>
                    <div class="fw-bold text-info-emphasis fs-3 mt-1" id="kpiTotalSellingAmount">
                        ₹<?= number_format((float)($cards['totalSellingAmount'] ?? 0), 2) ?>
                    </div>
                    <div class="small text-muted mt-2" id="kpiSellingSubtitle">
                        <i class="bi bi-currency-rupee text-info me-1"></i><?= number_format((int)($cards['sellingCount'] ?? 0)) ?> Sales Bills
                    </div>
                </div>
                <div class="bg-info-subtle text-info-emphasis p-3 rounded-circle flex-shrink-0">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. REVENUE GRAPH & CHART DIAGRAMS                                         -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Monthly Purchase vs Selling Revenue Graph -->
    <div class="col-lg-8">
        <div class="card dashboard-chart-card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i>Revenue Trend Graph</h5>
                    <small class="text-muted">Monthly Total Purchase vs Selling Amount</small>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Trend Chart Type">
                    <button type="button" class="btn btn-primary active" id="btnChartBar" data-chart-type="bar">
                        <i class="bi bi-bar-chart me-1"></i>Bar
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnChartLine" data-chart-type="line">
                        <i class="bi bi-graph-up me-1"></i>Line
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-canvas-wrap">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
                <div id="revenueTrendFallback" class="d-none text-center text-muted py-5 small">
                    Chart library could not be loaded. Check internet access to load Chart.js.
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Distribution Chart Diagram -->
    <div class="col-lg-4">
        <div class="card dashboard-chart-card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-info me-2"></i>Revenue Split Diagram</h5>
                <small class="text-muted">Selling vs Purchase Share &amp; Margin</small>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="chart-canvas-wrap-sm">
                    <canvas id="revenueSplitChart"></canvas>
                </div>
                <div class="border-top pt-3 mt-3 small">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Paid Sales Collected</span>
                        <span class="fw-semibold text-success" id="summaryPaidSelling">₹<?= number_format((float)($cards['paidSellingAmount'] ?? 0), 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Pending Sales Dues</span>
                        <span class="fw-semibold text-warning-emphasis" id="summaryPendingSelling">₹<?= number_format((float)($cards['pendingSellingAmount'] ?? 0), 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top mt-2">
                        <span class="fw-semibold text-dark">Net Balance (Selling - Purchase)</span>
                        <?php $netBal = (float)($cards['netRevenueBalance'] ?? 0); ?>
                        <span class="fw-bold <?= $netBal >= 0 ? 'text-success' : 'text-danger' ?>" id="summaryNetBalance">
                            <?= $netBal < 0 ? '-' : '' ?>₹<?= number_format(abs($netBal), 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Concern-wise Purchase vs Selling Comparison Diagram -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card dashboard-chart-card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3 text-success me-2"></i>Concern-wise Purchase vs Selling Comparison</h5>
                    <small class="text-muted">Side-by-side revenue comparison across active business concerns</small>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-canvas-wrap">
                    <canvas id="concernComparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js & Dynamic Concern Filter Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const initialAnalytics = <?= json_encode($analytics, JSON_UNESCAPED_UNICODE) ?>;
    const revenueApiUrl = '<?= url("api/admin/dashboard/revenue") ?>';

    let trendChartInstance = null;
    let splitChartInstance = null;
    let comparisonChartInstance = null;
    let currentTrendType = 'bar';
    let currentAnalytics = initialAnalytics;

    function formatInr(value) {
        const num = Number(value || 0);
        const sign = num < 0 ? '-' : '';
        return sign + '₹' + Math.abs(num).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatCompactInr(value) {
        const num = Number(value || 0);
        if (Math.abs(num) >= 10000000) return '₹' + (num / 10000000).toFixed(1) + 'Cr';
        if (Math.abs(num) >= 100000) return '₹' + (num / 100000).toFixed(1) + 'L';
        if (Math.abs(num) >= 1000) return '₹' + (num / 1000).toFixed(1) + 'K';
        return '₹' + num.toFixed(0);
    }

    function renderCards(data) {
        const cards = data.cards || {};
        document.getElementById('activeScopeLabel').textContent = data.selectedScopeName || 'All Concerns (Overall)';

        document.getElementById('kpiTotalConcerns').textContent = Number(cards.totalConcerns || 0).toLocaleString('en-IN');
        const concernsSub = document.getElementById('kpiConcernsSubtitle');
        concernsSub.innerHTML = '<i class="bi bi-buildings text-primary me-1"></i>' + escapeHtml(cards.concernsSubtitle || 'All Active Business Entities');
        concernsSub.title = cards.concernsSubtitle || '';

        document.getElementById('kpiTotalStaff').textContent = Number(cards.totalStaff || 0).toLocaleString('en-IN');
        document.getElementById('kpiStaffSubtitle').innerHTML = '<i class="bi bi-people text-success me-1"></i>' + escapeHtml(cards.staffSubtitle || 'Active Workforce Directory');

        document.getElementById('kpiTotalPurchaseAmount').textContent = formatInr(cards.totalPurchaseAmount || 0);
        document.getElementById('kpiPurchaseSubtitle').innerHTML = '<i class="bi bi-cart-check text-warning me-1"></i>' + Number(cards.purchaseCount || 0).toLocaleString('en-IN') + ' Purchase Orders';

        document.getElementById('kpiTotalSellingAmount').textContent = formatInr(cards.totalSellingAmount || 0);
        document.getElementById('kpiSellingSubtitle').innerHTML = '<i class="bi bi-currency-rupee text-info me-1"></i>' + Number(cards.sellingCount || 0).toLocaleString('en-IN') + ' Sales Bills';

        document.getElementById('summaryPaidSelling').textContent = formatInr(cards.paidSellingAmount || 0);
        document.getElementById('summaryPendingSelling').textContent = formatInr(cards.pendingSellingAmount || 0);

        const netVal = Number(cards.netRevenueBalance || 0);
        const netEl = document.getElementById('summaryNetBalance');
        netEl.textContent = formatInr(netVal);
        netEl.classList.remove('text-success', 'text-danger');
        netEl.classList.add(netVal >= 0 ? 'text-success' : 'text-danger');

        const resetBtn = document.getElementById('btnResetConcernFilter');
        if (resetBtn) {
            resetBtn.classList.toggle('d-none', data.selectedConcernId === 'all');
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderCharts(data) {
        if (typeof Chart === 'undefined') {
            document.getElementById('revenueTrendFallback')?.classList.remove('d-none');
            return;
        }

        const charts = data.charts || {};
        const trend = charts.monthlyTrend || { labels: [], purchases: [], selling: [] };
        const split = charts.revenueSplit || { labels: ['Total Selling', 'Total Purchases'], values: [0, 0] };
        const comparison = charts.concernComparison || { labels: [], purchases: [], selling: [] };

        // 1. Monthly Trend Chart (Bar / Line)
        const trendCtx = document.getElementById('revenueTrendChart');
        if (trendCtx) {
            if (trendChartInstance) {
                trendChartInstance.destroy();
            }
            const isLine = currentTrendType === 'line';
            trendChartInstance = new Chart(trendCtx, {
                type: currentTrendType,
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Total Selling Amount',
                            data: trend.selling,
                            backgroundColor: isLine ? 'rgba(13, 110, 253, 0.14)' : 'rgba(13, 110, 253, 0.85)',
                            borderColor: '#0d6efd',
                            borderWidth: 2,
                            borderRadius: isLine ? 0 : 6,
                            tension: 0.35,
                            fill: isLine
                        },
                        {
                            label: 'Total Purchase Amount',
                            data: trend.purchases,
                            backgroundColor: isLine ? 'rgba(245, 158, 11, 0.14)' : 'rgba(245, 158, 11, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 2,
                            borderRadius: isLine ? 0 : 6,
                            tension: 0.35,
                            fill: isLine
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + formatInr(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: val => formatCompactInr(val)
                            },
                            grid: { color: 'rgba(148, 163, 184, 0.15)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Revenue Split Doughnut Chart
        const splitCtx = document.getElementById('revenueSplitChart');
        if (splitCtx) {
            const totalSum = Number(split.values[0] || 0) + Number(split.values[1] || 0);
            const hasValues = totalSum > 0;
            if (splitChartInstance) {
                splitChartInstance.destroy();
            }
            splitChartInstance = new Chart(splitCtx, {
                type: 'doughnut',
                data: {
                    labels: hasValues ? split.labels : ['No Revenue Data'],
                    datasets: [{
                        data: hasValues ? split.values : [1],
                        backgroundColor: hasValues ? ['#0dcaf0', '#f59e0b'] : ['#e2e8f0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            enabled: hasValues,
                            callbacks: {
                                label: function(ctx) {
                                    const pct = totalSum > 0 ? ((ctx.parsed / totalSum) * 100).toFixed(1) : '0.0';
                                    return ctx.label + ': ' + formatInr(ctx.parsed) + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Concern-wise Comparison Chart
        const compCtx = document.getElementById('concernComparisonChart');
        if (compCtx) {
            if (comparisonChartInstance) {
                comparisonChartInstance.destroy();
            }
            comparisonChartInstance = new Chart(compCtx, {
                type: 'bar',
                data: {
                    labels: comparison.labels,
                    datasets: [
                        {
                            label: 'Total Selling Amount',
                            data: comparison.selling,
                            backgroundColor: 'rgba(25, 135, 84, 0.85)',
                            borderColor: '#198754',
                            borderWidth: 1,
                            borderRadius: 6
                        },
                        {
                            label: 'Total Purchase Amount',
                            data: comparison.purchases,
                            backgroundColor: 'rgba(255, 193, 7, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + formatInr(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: val => formatCompactInr(val)
                            },
                            grid: { color: 'rgba(148, 163, 184, 0.15)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    }

    async function fetchAndUpdateDashboard() {
        const concernSelect = document.getElementById('dashboardConcernFilter');
        const monthsSelect = document.getElementById('dashboardMonthsFilter');
        const loader = document.getElementById('dashboardLoadingIndicator');

        const concernVal = concernSelect ? concernSelect.value : 'all';
        const monthsVal = monthsSelect ? monthsSelect.value : '12';

        if (loader) loader.classList.remove('d-none');

        try {
            const query = new URLSearchParams({
                concern_id: concernVal,
                months: monthsVal
            });
            const response = await fetch(revenueApiUrl + '?' + query.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to load dashboard metrics.');
            }

            currentAnalytics = payload.data;
            renderCards(currentAnalytics);
            renderCharts(currentAnalytics);

            const pageUrl = new URL(window.location.href);
            if (concernVal === 'all') {
                pageUrl.searchParams.delete('concern_id');
            } else {
                pageUrl.searchParams.set('concern_id', concernVal);
            }
            pageUrl.searchParams.set('months', monthsVal);
            window.history.replaceState({}, '', pageUrl.toString());
        } catch (err) {
            console.error('Dashboard update error:', err);
        } finally {
            if (loader) loader.classList.add('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderCards(currentAnalytics);
        renderCharts(currentAnalytics);

        const concernSelect = document.getElementById('dashboardConcernFilter');
        const monthsSelect = document.getElementById('dashboardMonthsFilter');
        const resetBtn = document.getElementById('btnResetConcernFilter');
        const btnBar = document.getElementById('btnChartBar');
        const btnLine = document.getElementById('btnChartLine');

        if (concernSelect) {
            concernSelect.addEventListener('change', fetchAndUpdateDashboard);
        }
        if (monthsSelect) {
            monthsSelect.addEventListener('change', fetchAndUpdateDashboard);
        }
        if (resetBtn && concernSelect) {
            resetBtn.addEventListener('click', function() {
                concernSelect.value = 'all';
                fetchAndUpdateDashboard();
            });
        }

        function setTrendChartType(type) {
            currentTrendType = type;
            if (btnBar && btnLine) {
                btnBar.className = 'btn ' + (type === 'bar' ? 'btn-primary active' : 'btn-outline-primary');
                btnLine.className = 'btn ' + (type === 'line' ? 'btn-primary active' : 'btn-outline-primary');
            }
            renderCharts(currentAnalytics);
        }

        if (btnBar) btnBar.addEventListener('click', () => setTrendChartType('bar'));
        if (btnLine) btnLine.addEventListener('click', () => setTrendChartType('line'));
    });
})();
</script>
