<?php
// Production Unit & Inter-Module Stock Requirements View
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Stock Requirements &amp; Inter-Module Requests
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/stock-requests/create') ?>" class="btn btn-primary btn-sm fw-semibold shadow-sm px-3">
                <i class="bi bi-plus-circle me-1"></i> New Stock Requirement
            </a>
            <a href="<?= url('production/inventory') ?>" class="btn btn-outline-success btn-sm fw-semibold shadow-sm">
                <i class="bi bi-box-seam me-1"></i> Finished Goods Inventory
            </a>
        </div>
    </div>

    <!-- Alert Banner for Pending Send Area Actions -->
    <?php if (($stats['pending_receipt_count'] ?? 0) > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                <strong>Action Required:</strong> There are <span class="badge bg-warning text-dark fw-bold"><?= $stats['pending_receipt_count'] ?> requirement(s)</span> awaiting receipt &amp; acknowledgment by the supplying Send Area!
            </div>
            <a href="<?= url('production/stock-requests?status=PENDING') ?>" class="btn btn-sm btn-dark fw-semibold">Review Asked Counts</a>
        </div>
    <?php elseif (($stats['received_send_area_count'] ?? 0) > 0): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-truck fs-5 text-info"></i>
                <strong>Ready for Dispatch:</strong> There are <span class="badge bg-info text-white fw-bold"><?= $stats['received_send_area_count'] ?> requirement(s)</span> acknowledged by Send Area ready to be dispatched/sent!
            </div>
            <a href="<?= url('production/stock-requests?status=RECEIVED_BY_SEND_AREA') ?>" class="btn btn-sm btn-info text-white fw-semibold">Dispatch Stocks</a>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Analytics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Requests -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Requirements</span>
                        <div class="p-2 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-journal-text fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1"><?= number_format($stats['total_requests'] ?? 0) ?></h3>
                    <div class="small text-muted">Total Asked: <strong class="text-dark"><?= number_format($stats['total_requested_pieces'] ?? 0) ?> pcs</strong></div>
                </div>
            </div>
        </div>

        <!-- 2. Pending Send Area Receipt -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Awaiting Send Area</span>
                        <div class="p-2 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-1"><?= number_format($stats['pending_receipt_count'] ?? 0) ?></h3>
                    <div class="small text-muted">Awaiting Asked Count Receipt</div>
                </div>
            </div>
        </div>

        <!-- 3. Acknowledged / In Preparation -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Received by Send Area</span>
                        <div class="p-2 rounded-circle bg-info-subtle text-info">
                            <i class="bi bi-box-arrow-in-down fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-info mb-1"><?= number_format($stats['received_send_area_count'] ?? 0) ?></h3>
                    <div class="small text-muted">Ready to Dispatch / Prepare</div>
                </div>
            </div>
        </div>

        <!-- 4. Dispatched & Fulfilled -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Fulfilled &amp; In Transit</span>
                        <div class="p-2 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-check2-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-1">
                        <?= number_format($stats['fulfilled_count'] ?? 0) ?>
                        <span class="fs-6 fw-normal text-muted">(<?= number_format($stats['dispatched_count'] ?? 0) ?> In Transit)</span>
                    </h3>
                    <div class="small text-muted">Dispatched: <strong class="text-success"><?= number_format($stats['total_dispatched_pieces'] ?? 0) ?> pcs</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/stock-requests') ?>" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Req #, Module, Requester..." value="<?= e($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="PENDING" <?= ($status === 'PENDING') ? 'selected' : '' ?>>1. Awaiting Send Area Receipt</option>
                        <option value="RECEIVED_BY_SEND_AREA" <?= ($status === 'RECEIVED_BY_SEND_AREA') ? 'selected' : '' ?>>2. Received by Send Area</option>
                        <option value="READY_FOR_DISPATCH" <?= ($status === 'READY_FOR_DISPATCH') ? 'selected' : '' ?>>3. Ready for Dispatch</option>
                        <option value="DISPATCHED" <?= ($status === 'DISPATCHED') ? 'selected' : '' ?>>4. Stocks Dispatched / In Transit</option>
                        <option value="FULFILLED" <?= ($status === 'FULFILLED') ? 'selected' : '' ?>>5. Fulfilled &amp; Received</option>
                        <option value="REJECTED" <?= ($status === 'REJECTED') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="from_module" class="form-select form-select-sm">
                        <option value="">-- Requesting Module (From) --</option>
                        <?php foreach ($modulesList as $fullName => $shortName): ?>
                            <option value="<?= e($fullName) ?>" <?= ($fromModule === $fullName) ? 'selected' : '' ?>><?= e($shortName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="to_module" class="form-select form-select-sm">
                        <option value="">-- Send Area (To) --</option>
                        <?php foreach ($modulesList as $fullName => $shortName): ?>
                            <option value="<?= e($fullName) ?>" <?= ($toModule === $fullName) ? 'selected' : '' ?>><?= e($shortName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">-- All Priorities --</option>
                        <option value="LOW" <?= ($priority === 'LOW') ? 'selected' : '' ?>>Low Priority</option>
                        <option value="NORMAL" <?= ($priority === 'NORMAL') ? 'selected' : '' ?>>Normal Priority</option>
                        <option value="HIGH" <?= ($priority === 'HIGH') ? 'selected' : '' ?>>High Priority</option>
                        <option value="URGENT" <?= ($priority === 'URGENT') ? 'selected' : '' ?>>Urgent Priority</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">Filter</button>
                    <?php if ($search || $status || $priority || $fromModule || $toModule): ?>
                        <a href="<?= url('production/stock-requests') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Requirements Data Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 13%;">Requirement #</th>
                            <th style="width: 25%;">Module Route (From &rarr; Send Area)</th>
                            <th style="width: 14%;">Requested By</th>
                            <th class="text-center" style="width: 14%;">Asked vs Sent</th>
                            <th class="text-center" style="width: 10%;">Priority</th>
                            <th class="text-center" style="width: 14%;">Workflow Status</th>
                            <th class="text-end pe-3" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <strong>No Stock Requirements Found</strong>
                                    <div class="small mt-1">Submit a new requirement to request stock from another module.</div>
                                    <a href="<?= url('production/stock-requests/create') ?>" class="btn btn-sm btn-primary mt-3 fw-semibold">
                                        <i class="bi bi-plus-circle me-1"></i> Create First Requirement
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?= url('production/stock-requests/view/' . $r['id']) ?>" class="fw-bold text-primary text-decoration-none font-monospace">
                                            <?= e($r['request_number']) ?>
                                        </a>
                                        <div class="small text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="badge bg-secondary-subtle text-secondary border font-monospace small">
                                                <i class="bi bi-geo-alt me-1"></i><?= e($r['from_module'] ?? 'Warehouse') ?>
                                            </span>
                                            <i class="bi bi-arrow-right text-primary mx-1"></i>
                                            <span class="badge bg-primary-subtle text-primary border font-monospace small fw-bold">
                                                <i class="bi bi-send me-1"></i><?= e($r['to_module'] ?? $r['warehouse_name'] ?? 'Production') ?>
                                            </span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?= $r['item_count'] ?> product style(s) requested
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= e($r['requested_by_name'] ?: 'Staff') ?></span>
                                        <?php if ($r['required_by_date']): ?>
                                            <div class="small text-muted">Need by: <?= date('d M Y', strtotime($r['required_by_date'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold fs-6 text-dark">
                                            <span class="text-success"><?= number_format((int)($r['dispatched_pieces'] ?? 0)) ?></span> / <?= number_format((int)$r['total_requested_pieces']) ?> pcs
                                        </div>
                                        <?php 
                                            $pct = $r['total_requested_pieces'] > 0 ? min(100, round(($r['dispatched_pieces'] / $r['total_requested_pieces']) * 100)) : 0;
                                        ?>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar <?= ($pct >= 100) ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['priority'] === 'URGENT'): ?>
                                            <span class="badge bg-danger text-white fw-bold"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>
                                        <?php elseif ($r['priority'] === 'HIGH'): ?>
                                            <span class="badge bg-warning text-dark fw-bold">HIGH</span>
                                        <?php elseif ($r['priority'] === 'LOW'): ?>
                                            <span class="badge bg-light text-muted border">LOW</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">NORMAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['status'] === 'PENDING'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1 shadow-sm">
                                                <i class="bi bi-clock me-1"></i>Awaiting Send Area
                                            </span>
                                        <?php elseif ($r['status'] === 'RECEIVED_BY_SEND_AREA'): ?>
                                            <span class="badge bg-info text-white px-2 py-1 shadow-sm">
                                                <i class="bi bi-box-arrow-in-down me-1"></i>Received by Send Area
                                            </span>
                                        <?php elseif ($r['status'] === 'READY_FOR_DISPATCH'): ?>
                                            <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                                                <i class="bi bi-box-seam me-1"></i>Ready for Dispatch
                                            </span>
                                        <?php elseif ($r['status'] === 'DISPATCHED'): ?>
                                            <span class="badge bg-primary text-white px-2 py-1 shadow-sm">
                                                <i class="bi bi-truck me-1"></i>Stocks Sent
                                            </span>
                                        <?php elseif ($r['status'] === 'FULFILLED'): ?>
                                            <span class="badge bg-success text-white px-2 py-1 shadow-sm">
                                                <i class="bi bi-check2-all me-1"></i>Fulfilled
                                            </span>
                                        <?php elseif ($r['status'] === 'IN_PRODUCTION'): ?>
                                            <span class="badge bg-info text-dark border px-2 py-1">
                                                <i class="bi bi-gear me-1"></i>In Production
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger text-white px-2 py-1">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= url('production/stock-requests/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary fw-semibold" title="View Details and Process Flow">
                                            Process Flow <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
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
