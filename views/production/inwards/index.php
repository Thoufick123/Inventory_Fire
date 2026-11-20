<?php
// Goods Inward (GRN) Index View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Goods Inward (GRN)</h4>
            <p class="text-muted small mb-0">Record and verify incoming shipments, supplier purchase orders, and goods receipts</p>
        </div>
        <div>
            <a href="<?= url('production/inwards/create') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Create Goods Receipt (GRN)
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/inwards') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search GRN #, Invoice #, PO #, Vehicle..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="STOCKED" <?= $filters['status'] === 'STOCKED' ? 'selected' : '' ?>>Stocked / Received</option>
                        <option value="INSPECTED" <?= $filters['status'] === 'INSPECTED' ? 'selected' : '' ?>>Inspected</option>
                        <option value="PENDING" <?= $filters['status'] === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                        <option value="DISCREPANCY" <?= $filters['status'] === 'DISCREPANCY' ? 'selected' : '' ?>>Discrepancy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>" placeholder="Date From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>" placeholder="Date To">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="<?= url('production/inwards') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Inwards Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">GRN #</th>
                            <th>Date</th>
                            <th>PO Reference</th>
                            <th>Invoice / DC</th>
                            <th>Vehicle &amp; Gate Pass</th>
                            <th class="text-center">Rec. Qty</th>
                            <th class="text-center">Accepted</th>
                            <th class="text-center">Rejected</th>
                            <th>Status</th>
                            <th>Received By</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inwards)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No goods inward records found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($inwards as $inw): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-primary font-monospace">
                                <a href="<?= url('production/inwards/view/' . $inw['id']) ?>" class="text-decoration-none">
                                    <?= e($inw['grn_number']) ?>
                                </a>
                            </td>
                            <td><?= date('d M Y', strtotime($inw['received_date'])) ?></td>
                            <td>
                                <?php if (!empty($inw['po_number'])): ?>
                                    <span class="badge bg-light text-dark border font-monospace"><?= e($inw['po_number']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($inw['invoice_number'])): ?>
                                    <div class="fw-semibold small"><?= e($inw['invoice_number']) ?></div>
                                    <small class="text-muted"><?= $inw['invoice_date'] ? date('d-m-Y', strtotime($inw['invoice_date'])) : '' ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= e($inw['vehicle_number'] ?: '—') ?></div>
                                <?php if (!empty($inw['gate_pass_number'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary small">GP: <?= e($inw['gate_pass_number']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold"><?= number_format($inw['total_received_qty']) ?></td>
                            <td class="text-center fw-bold text-success"><?= number_format($inw['total_accepted_qty']) ?></td>
                            <td class="text-center fw-bold <?= $inw['total_rejected_qty'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= number_format($inw['total_rejected_qty']) ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match ($inw['status']) {
                                    'STOCKED'     => 'bg-success text-white',
                                    'INSPECTED'   => 'bg-info text-dark',
                                    'DISCREPANCY' => 'bg-danger text-white',
                                    default       => 'bg-warning text-dark',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($inw['status']) ?></span>
                            </td>
                            <td><small class="text-muted"><?= e($inw['received_by_name'] ?: 'Staff') ?></small></td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('production/inwards/view/' . $inw['id']) ?>" class="btn btn-outline-secondary" title="View GRN">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('production/inwards/print/' . $inw['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print GRN">
                                        <i class="bi bi-printer"></i>
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
