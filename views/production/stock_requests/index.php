<?php
// Production Unit & Inter-Module Stock Requirements View (Two Tabs: Requests Sent vs Requests Received)
$activeTab = $activeTab ?? 'sent';
$isSentTab = ($activeTab === 'sent');
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Stock Requirements &amp; Inter-Module Requests
            </h4>
            <span class="text-muted small">Manage inter-concern requisitions: track requests sent to other facilities and incoming stock requests to fulfill.</span>
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

    <!-- Dual Tab Selector: Requests Sent (Outgoing) vs Requests Received (Incoming) -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <ul class="nav nav-pills gap-2" id="stockRequestPillTabs">
                <!-- Tab 1: Requests Sent -->
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-bold rounded-pill <?= $isSentTab ? 'active bg-primary text-white shadow-sm' : 'bg-light text-dark border' ?>" 
                       href="<?= url('production/stock-requests?tab=sent') ?>">
                        <i class="bi bi-send-fill me-1.5 <?= $isSentTab ? 'text-white' : 'text-primary' ?>"></i>
                        Requests Sent (Outgoing)
                        <span class="badge <?= $isSentTab ? 'bg-white text-primary' : 'bg-primary text-white' ?> rounded-pill ms-1.5 px-2">
                            <?= number_format($sentCount ?? 0) ?>
                        </span>
                    </a>
                </li>

                <!-- Tab 2: Requests Received -->
                <li class="nav-item position-relative">
                    <a class="nav-link py-2 px-3 fw-bold rounded-pill <?= !$isSentTab ? 'active bg-warning text-dark shadow-sm' : 'bg-light text-dark border' ?>" 
                       href="<?= url('production/stock-requests?tab=received') ?>">
                        <i class="bi bi-inbox-fill me-1.5 <?= !$isSentTab ? 'text-dark' : 'text-warning' ?>"></i>
                        Requests Received (To Fulfill)
                        <span class="badge <?= !$isSentTab ? 'bg-dark text-white' : 'bg-warning text-dark' ?> rounded-pill ms-1.5 px-2">
                            <?= number_format($receivedCount ?? 0) ?>
                        </span>
                        <?php if (($pendingReceivedCount ?? 0) > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1 animate__animated animate__pulse animate__infinite" title="<?= $pendingReceivedCount ?> new incoming requirement(s) awaiting your review">
                                <?= $pendingReceivedCount ?> NEW
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <div class="text-muted small px-2">
                <?php if ($isSentTab): ?>
                    <span class="badge bg-primary-subtle text-primary border me-1"><i class="bi bi-arrow-up-right me-1"></i>Outgoing</span>
                    Showing requirements <strong>asked by your active concern</strong> from other supplying facilities.
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-dark border me-1"><i class="bi bi-arrow-down-left me-1"></i>Incoming</span>
                    Showing requirements <strong>received from other concerns</strong> for you to prepare &amp; dispatch.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alert Banners based on Active Tab Context -->
    <?php if (!$isSentTab): ?>
        <!-- Received Tab Alerts -->
        <?php if (($stats['pending_receipt_count'] ?? 0) > 0): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                    <strong>Action Required:</strong> You have <span class="badge bg-warning text-dark fw-bold"><?= $stats['pending_receipt_count'] ?> incoming requirement(s)</span> awaiting your receipt &amp; acknowledgment to prepare stock!
                </div>
                <a href="<?= url('production/stock-requests?tab=received&status=PENDING') ?>" class="btn btn-sm btn-dark fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Review &amp; Acknowledge
                </a>
            </div>
        <?php elseif (($stats['received_send_area_count'] ?? 0) > 0): ?>
            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam fs-5 text-info"></i>
                    <strong>Ready for Dispatch:</strong> There are <span class="badge bg-info text-white fw-bold"><?= $stats['received_send_area_count'] ?> requirement(s)</span> acknowledged and ready to be packed and dispatched to requesting concerns!
                </div>
                <a href="<?= url('production/stock-requests?tab=received&status=RECEIVED_BY_SEND_AREA') ?>" class="btn btn-sm btn-info text-white fw-semibold">
                    <i class="bi bi-truck me-1"></i> Dispatch Stocks
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Sent Tab Alerts -->
        <?php if (($stats['pending_receipt_count'] ?? 0) > 0): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-5 text-warning"></i>
                    <strong>Awaiting Send Area:</strong> You have <span class="badge bg-warning text-dark fw-bold"><?= $stats['pending_receipt_count'] ?> sent requirement(s)</span> waiting for the supplying facility to acknowledge.
                </div>
                <a href="<?= url('production/stock-requests?tab=sent&status=PENDING') ?>" class="btn btn-sm btn-dark fw-semibold">View Pending</a>
            </div>
        <?php elseif (($stats['dispatched_count'] ?? 0) > 0): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-truck fs-5 text-success"></i>
                    <strong>Stocks Dispatched &amp; In Transit:</strong> <span class="badge bg-success text-white fw-bold"><?= $stats['dispatched_count'] ?> requirement(s)</span> have been dispatched by the supplying concern and are on the way to you!
                </div>
                <a href="<?= url('production/stock-requests?tab=sent&status=DISPATCHED') ?>" class="btn btn-sm btn-success fw-semibold">
                    <i class="bi bi-check2-all me-1"></i> View In-Transit Requisitions
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- KPI Summary Analytics Cards (Contextual to active tab) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Requests -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">
                            <?= $isSentTab ? 'Total Sent Requirements' : 'Total Incoming Requirements' ?>
                        </span>
                        <div class="p-2 rounded-circle <?= $isSentTab ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-dark' ?>">
                            <i class="bi <?= $isSentTab ? 'bi-send' : 'bi-inbox' ?> fs-5"></i>
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
                        <span class="text-muted small fw-semibold text-uppercase">
                            <?= $isSentTab ? 'Awaiting Send Area' : 'Awaiting Your Receipt' ?>
                        </span>
                        <div class="p-2 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-1"><?= number_format($stats['pending_receipt_count'] ?? 0) ?></h3>
                    <div class="small text-muted">
                        <?= $isSentTab ? 'Waiting for supplying area' : 'Action needed: Acknowledge &amp; Prep' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Acknowledged / In Preparation -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">
                            <?= $isSentTab ? 'Accepted by Send Area' : 'Acknowledged &amp; In Prep' ?>
                        </span>
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

    <!-- Filter & Search Controls (Preserves Active Tab) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/stock-requests') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Req #, Concern, Requester..." value="<?= e($search ?? '') ?>">
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
                        <option value="">-- Requesting Concern (From) --</option>
                        <?php foreach ($modulesList as $fullName => $shortName): ?>
                            <option value="<?= e($fullName) ?>" <?= ($fromModule === $fullName) ? 'selected' : '' ?>><?= e($shortName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="to_module" class="form-select form-select-sm">
                        <option value="">-- Send Area Concern (To) --</option>
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
                        <a href="<?= url('production/stock-requests?tab=' . e($activeTab)) ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Requirements Data Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $isSentTab ? 'bg-primary' : 'bg-warning text-dark' ?> fs-6 py-1 px-2">
                    <i class="bi <?= $isSentTab ? 'bi-send-fill' : 'bi-inbox-fill' ?> me-1"></i>
                    <?= $isSentTab ? 'Requests Sent' : 'Requests Received' ?>
                </span>
                <span class="text-muted small">
                    <?= count($requests) ?> item(s) listed
                </span>
            </div>
            <div>
                <?php if ($isSentTab): ?>
                    <a href="<?= url('production/stock-requests/create') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> Create New Requisition
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 14%;">Requirement #</th>
                            <th style="width: 26%;">
                                <?= $isSentTab ? 'Route: Requesting Concern &rarr; Send Area' : 'Route: Requester Concern &rarr; Your Facility' ?>
                            </th>
                            <th style="width: 14%;">Requested By</th>
                            <th class="text-center" style="width: 13%;">Asked vs Sent</th>
                            <th class="text-center" style="width: 10%;">Priority</th>
                            <th class="text-center" style="width: 13%;">Workflow Status</th>
                            <th class="text-end pe-3" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi <?= $isSentTab ? 'bi-send-dash' : 'bi-inbox' ?> fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <strong><?= $isSentTab ? 'No Outgoing Stock Requirements Found' : 'No Incoming Stock Requirements Received' ?></strong>
                                    <div class="small mt-1">
                                        <?= $isSentTab 
                                            ? 'Submit a new requirement to request stock from another concern or facility.' 
                                            : 'Your concern currently has no stock requisitions from other facilities waiting to be fulfilled.' 
                                        ?>
                                    </div>
                                    <?php if ($isSentTab): ?>
                                        <a href="<?= url('production/stock-requests/create') ?>" class="btn btn-sm btn-primary mt-3 fw-semibold">
                                            <i class="bi bi-plus-circle me-1"></i> Create First Requirement
                                        </a>
                                    <?php endif; ?>
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
                                            <span class="badge <?= $isSentTab ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary fw-bold' ?> border font-monospace small">
                                                <i class="bi bi-geo-alt me-1"></i><?= e($r['from_module'] ?? 'Warehouse') ?>
                                            </span>
                                            <i class="bi bi-arrow-right text-muted mx-1"></i>
                                            <span class="badge <?= $isSentTab ? 'bg-primary-subtle text-primary fw-bold' : 'bg-warning-subtle text-dark fw-bold' ?> border font-monospace small">
                                                <i class="bi bi-send me-1"></i><?= e($r['to_module'] ?? $r['warehouse_name'] ?? 'Production') ?>
                                            </span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-layers me-1"></i><?= $r['item_count'] ?> product style(s) requested
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
                                        <?php if (!$isSentTab && $r['status'] === 'PENDING'): ?>
                                            <a href="<?= url('production/stock-requests/view/' . $r['id']) ?>" class="btn btn-sm btn-warning text-dark fw-bold shadow-xs">
                                                <i class="bi bi-box-arrow-in-down me-1"></i>Acknowledge
                                            </a>
                                        <?php elseif (!$isSentTab && in_array($r['status'], ['RECEIVED_BY_SEND_AREA', 'READY_FOR_DISPATCH'])): ?>
                                            <a href="<?= url('production/stock-requests/view/' . $r['id']) ?>" class="btn btn-sm btn-info text-white fw-bold shadow-xs">
                                                <i class="bi bi-truck me-1"></i>Dispatch
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= url('production/stock-requests/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary fw-semibold" title="View Details and Process Flow">
                                                Process Flow <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        <?php endif; ?>
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
