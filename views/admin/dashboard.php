<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
        <p class="text-muted small mb-0">Production facilities, workforce directory, and system health overview.</p>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. TOP HIGHLIGHT STATS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Production Units -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Production Facilities</div>
                    <div class="stat-value fw-bold text-primary fs-3 mt-1"><?= number_format($totalProductionUnits ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Units</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-gear-wide-connected text-primary me-1"></i>Apparel Manufacturing
                    </div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary p-3 rounded-circle">
                    <i class="bi bi-gear-wide-connected fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Staff -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Total Staff</div>
                    <div class="stat-value fw-bold text-success fs-3 mt-1"><?= number_format($totalStaff ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Members</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-people text-success me-1"></i>Workforce Directory
                    </div>
                </div>
                <div class="stat-icon bg-success-subtle text-success p-3 rounded-circle">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Production Programs -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Active Programs</div>
                    <div class="stat-value fw-bold text-warning fs-3 mt-1"><?= number_format($pendingProduction ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Lots</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-calendar-check text-warning me-1"></i>Scheduled &amp; In-Progress
                    </div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning p-3 rounded-circle">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending QC Batches -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-semibold text-muted small">Pending QC</div>
                    <div class="stat-value fw-bold text-info fs-3 mt-1"><?= number_format($pendingQC ?? 0) ?> <span class="fs-6 fw-normal text-secondary">Batches</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-shield-check text-info me-1"></i>Quality Inspections
                    </div>
                </div>
                <div class="stat-icon bg-info-subtle text-info p-3 rounded-circle">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. SECONDARY PRODUCTION METRICS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-scissors fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($activeCutting ?? 0) ?></div>
                <div class="text-muted small">Active Cutting Batches</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-success bg-opacity-10 text-success">
                <i class="bi bi-bounding-box fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($activeStitching ?? 0) ?></div>
                <div class="text-muted small">Active Stitching Batches</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-3 d-flex flex-row align-items-center gap-3">
            <div class="p-2.5 rounded-circle bg-info bg-opacity-10 text-info">
                <i class="bi bi-journal-bookmark fs-4"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark"><?= number_format($totalProducts ?? 0) ?></div>
                <div class="text-muted small">Registered Product Styles</div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. RECENT SYSTEM AUDIT ACTIVITY -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary fs-5"></i>
            <h5 class="mb-0 fw-bold">Recent System Activity</h5>
        </div>
        <a href="<?= url('system/audit') ?>" class="btn btn-sm btn-outline-primary">View Full Audit Log</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentAuditLogs)): ?>
                        <?php foreach ($recentAuditLogs as $log): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= e(date('d M Y, h:i A', strtotime($log['created_at'] ?? 'now'))) ?></td>
                            <td class="fw-semibold text-dark"><?= e($log['user_name'] ?? $log['username'] ?? 'System') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e(strtoupper($log['action'] ?? 'ACTIVITY')) ?></span>
                            </td>
                            <td><?= e($log['entity_type'] ?? '-') ?> <?= !empty($log['entity_id']) ? '#' . e($log['entity_id']) : '' ?></td>
                            <td class="text-muted small font-monospace"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No recent audit logs available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
