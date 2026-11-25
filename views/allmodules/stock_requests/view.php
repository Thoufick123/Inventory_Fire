<?php
// Production Unit & Inter-Module Stock Requirement Detail & Workflow Execution View
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold text-dark mb-0 font-monospace">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i><?= e($req['request_number']) ?>
                </h4>
                <!-- Status Badge -->
                <?php if ($req['status'] === 'PENDING'): ?>
                    <span class="badge bg-warning text-dark px-3 py-1 shadow-sm"><i class="bi bi-clock me-1"></i>1. Awaiting Send Area Receipt</span>
                <?php elseif ($req['status'] === 'RECEIVED_BY_SEND_AREA'): ?>
                    <span class="badge bg-info text-white px-3 py-1 shadow-sm"><i class="bi bi-box-arrow-in-down me-1"></i>2. Received by Send Area (Ready to Send)</span>
                <?php elseif ($req['status'] === 'READY_FOR_DISPATCH'): ?>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-1"><i class="bi bi-box-seam me-1"></i>Preparing / Partial Dispatch</span>
                <?php elseif ($req['status'] === 'DISPATCHED'): ?>
                    <span class="badge bg-primary text-white px-3 py-1 shadow-sm"><i class="bi bi-truck me-1"></i>3. Stocks Sent / In Transit</span>
                <?php elseif ($req['status'] === 'FULFILLED'): ?>
                    <span class="badge bg-success text-white px-3 py-1 shadow-sm"><i class="bi bi-check2-all me-1"></i>4. Fulfilled &amp; Received</span>
                <?php elseif ($req['status'] === 'IN_PRODUCTION'): ?>
                    <span class="badge bg-info text-dark border px-3 py-1"><i class="bi bi-gear me-1"></i>Manufacturing Run In Progress</span>
                <?php else: ?>
                    <span class="badge bg-danger text-white px-3 py-1">Rejected</span>
                <?php endif; ?>
            </div>
            <div class="text-muted small mt-1">
                Route: <span class="badge bg-light text-secondary border font-monospace"><?= e($req['from_module']) ?></span>
                &rarr;
                <span class="badge bg-primary-subtle text-primary border font-monospace fw-bold"><?= e($req['to_module']) ?></span>
                · Created <?= date('d M Y, h:i A', strtotime($req['created_at'])) ?> by <?= e($req['requested_by_name'] ?: 'Staff') ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/stock-requests') ?>" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> All Requirements
            </a>
            <a href="javascript:window.print()" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Slip
            </a>
        </div>
    </div>

    <!-- Stepper / Visual 4-Step Workflow Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 text-center align-items-center">
                <!-- Step 1: Asked Counts -->
                <div class="col-md-3">
                    <div class="p-2 rounded-3 border <?= ($workflowStep >= 1) ? 'border-success bg-success-subtle text-success' : 'bg-light text-muted' ?>">
                        <div class="fw-bold small mb-1">
                            <i class="bi bi-check-circle-fill me-1"></i>STEP 1: ASKED COUNTS
                        </div>
                        <div class="fs-6 fw-bold"><?= number_format((int)$req['total_requested_pieces']) ?> pcs Asked</div>
                        <div class="small opacity-75">By <?= e($req['from_module']) ?></div>
                    </div>
                </div>

                <!-- Step 2: Send Area Receipt -->
                <div class="col-md-3">
                    <div class="p-2 rounded-3 border <?= ($workflowStep >= 2) ? 'border-success bg-success-subtle text-success' : (($workflowStep === 1) ? 'border-warning bg-warning-subtle text-dark fw-bold' : 'bg-light text-muted') ?>">
                        <div class="fw-bold small mb-1">
                            <?php if ($workflowStep >= 2): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>STEP 2: RECEIVED BY SEND AREA
                            <?php else: ?>
                                <i class="bi bi-clock-history me-1"></i>STEP 2: AWAITING RECEIPT
                            <?php endif; ?>
                        </div>
                        <div class="fs-6 fw-bold">
                            <?= ($workflowStep >= 2) ? 'Acknowledged' : 'Pending Receipt' ?>
                        </div>
                        <div class="small opacity-75"><?= e($req['to_module']) ?></div>
                    </div>
                </div>

                <!-- Step 3: Stocks Sent -->
                <div class="col-md-3">
                    <div class="p-2 rounded-3 border <?= ($workflowStep >= 3) ? 'border-success bg-success-subtle text-success' : (($workflowStep === 2) ? 'border-primary bg-primary-subtle text-primary fw-bold' : 'bg-light text-muted') ?>">
                        <div class="fw-bold small mb-1">
                            <?php if ($workflowStep >= 3): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>STEP 3: STOCKS SENT
                            <?php elseif ($workflowStep === 2): ?>
                                <i class="bi bi-send-check me-1"></i>STEP 3: READY TO SEND
                            <?php else: ?>
                                <i class="bi bi-lock-fill me-1"></i>STEP 3: SEND STOCKS
                            <?php endif; ?>
                        </div>
                        <div class="fs-6 fw-bold"><?= number_format((int)($req['dispatched_pieces'] ?? 0)) ?> pcs Sent</div>
                        <div class="small opacity-75">
                            <?= ($workflowStep < 2) ? 'Locked until received' : 'In Transit' ?>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Confirm & Fulfill -->
                <div class="col-md-3">
                    <div class="p-2 rounded-3 border <?= ($workflowStep >= 4) ? 'border-success bg-success-subtle text-success' : (($workflowStep === 3) ? 'border-warning bg-warning-subtle text-dark fw-bold' : 'bg-light text-muted') ?>">
                        <div class="fw-bold small mb-1">
                            <?php if ($workflowStep >= 4): ?>
                                <i class="bi bi-check2-all me-1"></i>STEP 4: FULFILLED
                            <?php else: ?>
                                <i class="bi bi-hourglass-split me-1"></i>STEP 4: CONFIRM RECEIPT
                            <?php endif; ?>
                        </div>
                        <div class="fs-6 fw-bold"><?= number_format((int)$req['fulfilled_pieces']) ?> pcs Confirmed</div>
                        <div class="small opacity-75"><?= ($workflowStep >= 4) ? 'Flow Complete' : 'Awaiting Delivery' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Overview Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <span class="text-muted small text-uppercase fw-semibold">Total Asked Pieces</span>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format((int)$req['total_requested_pieces']) ?> pcs</h4>
                <div class="small text-muted mt-1">Requested by <?= e($req['from_module']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <span class="text-muted small text-uppercase fw-semibold">Dispatched / Sent</span>
                <h4 class="fw-bold text-primary mt-1 mb-0"><?= number_format((int)($req['dispatched_pieces'] ?? 0)) ?> pcs</h4>
                <div class="small text-muted mt-1">Sent from <?= e($req['to_module']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <span class="text-muted small text-uppercase fw-semibold">Confirmed Received</span>
                <h4 class="fw-bold text-success mt-1 mb-0"><?= number_format((int)$req['fulfilled_pieces']) ?> pcs</h4>
                <div class="small text-muted mt-1">Acknowledged at destination</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100">
                <span class="text-muted small text-uppercase fw-semibold">Remaining to Send</span>
                <?php 
                    $remainingToSend = max(0, (int)$req['total_requested_pieces'] - (int)($req['dispatched_pieces'] ?? 0));
                ?>
                <h4 class="fw-bold <?= ($remainingToSend > 0) ? 'text-warning' : 'text-success' ?> mt-1 mb-0">
                    <?= number_format($remainingToSend) ?> pcs
                </h4>
                <div class="small text-muted mt-1"><?= ($remainingToSend === 0) ? 'All stocks sent!' : 'Awaiting dispatch' ?></div>
            </div>
        </div>
    </div>

    <!-- STEP 1 DISPLAY: Asked Products & Availability Matrix ("first show the asked product count's") -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-list-stars text-primary me-2"></i>Asked Product Counts &amp; Send Area Inventory Feasibility
                </h6>
                <span class="text-muted small">The requested quantities ("asked product counts") compared against available inventory in the Send Area (<?= e($req['to_module']) ?>).</span>
            </div>
            <div>
                <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                    Required by: <?= $req['required_by_date'] ? date('d M Y', strtotime($req['required_by_date'])) : 'Immediate' ?>
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 25%;">Product Style</th>
                            <th style="width: 20%;">Variant / SKU / Attributes</th>
                            <th class="text-center" style="width: 14%;">Asked Count</th>
                            <th class="text-center" style="width: 14%;">Send Area Stock</th>
                            <th class="text-center" style="width: 13%;">Availability Status</th>
                            <th class="text-center" style="width: 14%;">Dispatched &rarr; Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <?php 
                                $inStock = ($it['available_stock'] ?? 0) >= (int)$it['requested_qty']; 
                                $partialStock = ($it['available_stock'] ?? 0) > 0 && !$inStock;
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-dark fs-6"><?= e($it['product_name']) ?></strong>
                                    <?php if (!empty($it['product_id'])): ?>
                                        <div class="small text-muted">Product ID: #<?= $it['product_id'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($it['sku']): ?>
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($it['sku']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($it['size_name'] || $it['color_name']): ?>
                                        <div class="small text-muted mt-1">
                                            <?= $it['size_name'] ? 'Size: ' . e($it['size_name']) : '' ?> 
                                            <?= $it['color_name'] ? '· Color: ' . e($it['color_name']) : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-muted fst-italic">All standard variants</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 px-3 py-2 fw-bold shadow-sm">
                                        <?= (int)$it['requested_qty'] ?> pcs
                                    </span>
                                    <div class="small text-muted mt-1">Asked Count</div>
                                </td>
                                <td class="text-center">
                                    <strong class="fs-6 <?= ($it['available_stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                                        <?= (int)($it['available_stock'] ?? 0) ?> pcs
                                    </strong>
                                    <div class="small text-muted">Ready in <?= e($req['to_module']) ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($inStock): ?>
                                        <span class="badge bg-success-subtle text-success border px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Full Stock Ready
                                        </span>
                                    <?php elseif ($partialStock): ?>
                                        <span class="badge bg-warning-subtle text-warning border px-2 py-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Partial (<?= (int)$it['available_stock'] ?> pcs)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border px-2 py-1">
                                            <i class="bi bi-x-circle me-1"></i>Needs Production
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-bold">
                                        <span class="text-primary"><?= (int)($it['dispatched_qty'] ?? 0) ?> Sent</span>
                                        &bull;
                                        <span class="text-success"><?= (int)($it['received_qty'] ?? 0) ?> Confirmed</span>
                                    </div>
                                    <?php if ($it['remaining_to_send'] > 0): ?>
                                        <div class="small text-warning fw-semibold mt-1">
                                            <?= $it['remaining_to_send'] ?> pcs pending dispatch
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success small mt-1">Dispatch complete</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- WORKFLOW ACTION STAGES -->

    <!-- STAGE 2 ACTION: Send Area Receives and Acknowledges Asked Count ("that count recive the send area") -->
    <?php if ($req['status'] === 'PENDING'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-warning bg-warning bg-opacity-10">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 flex-wrap flex-md-nowrap">
                    <div class="p-3 bg-warning text-dark rounded-circle shadow-sm">
                        <i class="bi bi-bell-fill fs-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">
                            Step 2 Action: Send Area Concern (<?= e($req['to_module']) ?>) Must Acknowledge &amp; Receive Request
                        </h5>
                        <p class="text-secondary mb-3">
                            The requesting module (<strong><?= e($req['from_module']) ?></strong>) has officially asked for <strong><?= (int)$req['total_requested_pieces'] ?> pieces</strong>. 
                            Under the business process flow, the fulfilling Send Area concern must first receive and acknowledge these asked product counts before any stock can be dispatched.
                        </p>
                        <form method="POST" action="<?= url('production/stock-requests/receive/' . $req['id']) ?>">
                            <?= CSRF::field() ?>
                            <button type="submit" class="btn btn-warning fw-bold px-4 py-2 shadow-sm">
                                <i class="bi bi-box-arrow-in-down me-2"></i> Acknowledge &amp; Receive Asked Counts (Send Area)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- STAGE 3 ACTION: Send Area Dispatches / Sends Stock ("then only send the asked stocks give it") -->
    <?php if (in_array($req['status'], ['RECEIVED_BY_SEND_AREA', 'READY_FOR_DISPATCH', 'IN_PRODUCTION'], true) && $remainingToSend > 0): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-send-check text-primary me-2"></i>Step 3 Action: Send Area &rarr; Dispatch Asked Stocks
                    </h6>
                    <span class="text-muted small">Send Area has acknowledged the requirement. You can now dispatch the asked stocks to <?= e($req['from_module']) ?>.</span>
                </div>
                <div class="d-flex gap-2">
                    <?php if (hasPermission('programs', 'create')): ?>
                        <a href="<?= url('production/programs/create') ?>" class="btn btn-sm btn-outline-secondary" title="Trigger Production Run">
                            <i class="bi bi-gear me-1"></i> Start Production Run
                        </a>
                    <?php endif; ?>
                    <?php if (hasPermission('batches', 'create')): ?>
                        <a href="<?= url('production/batches/create') ?>" class="btn btn-sm btn-outline-success" title="Pack into Outward Box">
                            <i class="bi bi-box-seam me-1"></i> Pack Outward Box
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('production/stock-requests/send-stocks/' . $req['id']) ?>">
                    <?= CSRF::field() ?>

                    <div class="table-responsive mb-3">
                        <table class="table align-middle table-sm mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Style &amp; Variant</th>
                                    <th class="text-center">Asked Count</th>
                                    <th class="text-center">Already Sent</th>
                                    <th class="text-center">Remaining Needed</th>
                                    <th class="text-center" style="width: 22%;">Quantity to Send Now (pcs) <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($it['product_name']) ?></strong>
                                            <?php if ($it['sku']): ?>
                                                <span class="badge bg-light text-dark border font-monospace ms-1"><?= e($it['sku']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark"><?= (int)$it['requested_qty'] ?> pcs</td>
                                        <td class="text-center text-primary fw-semibold"><?= (int)($it['dispatched_qty'] ?? 0) ?> pcs</td>
                                        <td class="text-center fw-bold <?= ($it['remaining_to_send'] > 0) ? 'text-danger' : 'text-success' ?>">
                                            <?= $it['remaining_to_send'] ?> pcs
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm justify-content-center" style="max-width: 160px; margin: 0 auto;">
                                                <input type="number" 
                                                       name="items[<?= $it['id'] ?>][send_qty]" 
                                                       class="form-control text-center fw-bold fs-6" 
                                                       min="0" 
                                                       max="<?= $it['remaining_to_send'] ?>" 
                                                       value="<?= $it['remaining_to_send'] ?>" 
                                                       required>
                                                <span class="input-group-text">pcs</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark mb-1">Dispatch Reference / Challan # / Transporter Notes</label>
                            <input type="text" name="dispatch_notes" class="form-control" placeholder="e.g. Dispatched via Factory Van #TN-38-1234, Challan #DC-902">
                        </div>
                        <div class="col-md-4 text-md-end mt-md-4">
                            <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm w-100">
                                <i class="bi bi-send-check me-2"></i> Send / Dispatch Asked Stocks
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($req['status'] === 'PENDING'): ?>
        <!-- LOCKED SEND AREA DISPATCH PANEL -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <i class="bi bi-lock-fill fs-2 text-muted"></i>
                <div>
                    <h6 class="fw-bold text-muted mb-1">Step 3: Send Stocks (Currently Locked)</h6>
                    <span class="text-secondary small">
                        Stocks cannot be sent yet. The Send Area concern (<strong><?= e($req['to_module']) ?></strong>) must first receive and acknowledge the asked count above before stock dispatch is permitted.
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- STAGE 4 ACTION: Requesting Module Confirms Stocks Received -->
    <?php if ($req['status'] === 'DISPATCHED' || ($req['status'] === 'READY_FOR_DISPATCH' && ($req['dispatched_pieces'] ?? 0) > ($req['fulfilled_pieces'] ?? 0))): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-success">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-box-seam-fill text-success me-2"></i>Step 4 Action: Requesting Module (<?= e($req['from_module']) ?>) Confirm Stock Received
                </h6>
                <span class="text-muted small">Stocks were dispatched by <?= e($req['to_module']) ?>. Verify physical receipt and acknowledge arrival.</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('production/stock-requests/confirm-received/' . $req['id']) ?>">
                    <?= CSRF::field() ?>

                    <div class="table-responsive mb-3">
                        <table class="table align-middle table-sm mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Style &amp; Variant</th>
                                    <th class="text-center">Asked Count</th>
                                    <th class="text-center">Dispatched Count</th>
                                    <th class="text-center">Already Confirmed</th>
                                    <th class="text-center" style="width: 22%;">Received Quantity (pcs) <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <?php $pendingReceive = max(0, (int)($it['dispatched_qty'] ?? 0) - (int)($it['received_qty'] ?? 0)); ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($it['product_name']) ?></strong>
                                            <?php if ($it['sku']): ?>
                                                <span class="badge bg-light text-dark border font-monospace ms-1"><?= e($it['sku']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark"><?= (int)$it['requested_qty'] ?> pcs</td>
                                        <td class="text-center text-primary fw-semibold"><?= (int)($it['dispatched_qty'] ?? 0) ?> pcs</td>
                                        <td class="text-center text-success fw-semibold"><?= (int)($it['received_qty'] ?? 0) ?> pcs</td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm justify-content-center" style="max-width: 160px; margin: 0 auto;">
                                                <input type="number" 
                                                       name="items[<?= $it['id'] ?>][received_qty]" 
                                                       class="form-control text-center fw-bold fs-6" 
                                                       min="0" 
                                                       max="<?= $pendingReceive ?>" 
                                                       value="<?= $pendingReceive ?>" 
                                                       required>
                                                <span class="input-group-text">pcs</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark mb-1">Receiving Remarks / Storage Location</label>
                            <input type="text" name="receiving_notes" class="form-control" placeholder="e.g. Stock received in good condition, shelved in Bin WH-A2.">
                        </div>
                        <div class="col-md-4 text-md-end mt-md-4">
                            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm w-100">
                                <i class="bi bi-check2-circle me-2"></i> Confirm Stocks Received
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- FULFILLED SUCCESS BANNER -->
    <?php if ($req['status'] === 'FULFILLED'): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-success bg-opacity-10 border-start border-4 border-success">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="p-3 bg-success text-white rounded-circle">
                    <i class="bi bi-check-lg fs-2"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-success mb-1">Requirement Flow Completed &amp; Fulfilled!</h5>
                    <div class="text-secondary">
                        All <strong><?= (int)$req['fulfilled_pieces'] ?> pieces</strong> of asked stocks have been successfully sent by the Send Area (<strong><?= e($req['to_module']) ?></strong>) and confirmed received by the requesting module (<strong><?= e($req['from_module']) ?></strong>).
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notes & Audit Trail History -->
    <?php if (!empty($req['notes'])): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-journal-text text-primary me-2"></i>Audit Trail &amp; Movement Notes
                </h6>
            </div>
            <div class="card-body p-3">
                <pre class="bg-light p-3 rounded text-dark font-monospace small mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= e($req['notes']) ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>
