<?php
// Goods Inward (GRN) View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-box-arrow-in-down text-primary me-2"></i>Goods Receipt Note: <span class="font-monospace text-primary"><?= e($inward['grn_number']) ?></span>
            </h4>
            <p class="text-muted small mb-0">Received on <?= date('d M Y', strtotime($inward['received_date'])) ?> · <?= e($inward['warehouse_name'] ?: 'Central Warehouse') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/inwards') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inwards
            </a>
            <a href="<?= url('production/inwards/print/' . $inward['id']) ?>" target="_blank" class="btn btn-dark">
                <i class="bi bi-printer me-1"></i> Print GRN
            </a>
        </div>
    </div>

    <!-- Summary Details Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">Consignment Overview</h6>
            <div>
                <?php
                $badgeClass = match ($inward['status']) {
                    'STOCKED'     => 'bg-success text-white',
                    'INSPECTED'   => 'bg-info text-dark',
                    'DISCREPANCY' => 'bg-danger text-white',
                    default       => 'bg-warning text-dark',
                };
                ?>
                <span class="badge <?= $badgeClass ?> px-3 py-2 fs-6"><?= e($inward['status']) ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small d-block">PO Number</span>
                    <strong class="font-monospace"><?= e($inward['po_number'] ?: 'Direct Consignment') ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Supplier Invoice / DC #</span>
                    <strong><?= e($inward['invoice_number'] ?: '—') ?></strong>
                    <?php if ($inward['invoice_date']): ?>
                    <small class="text-muted d-block"><?= date('d M Y', strtotime($inward['invoice_date'])) ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Vehicle &amp; Gate Pass</span>
                    <strong><?= e($inward['vehicle_number'] ?: '—') ?></strong>
                    <?php if ($inward['gate_pass_number']): ?>
                    <small class="text-muted d-block">GP: <?= e($inward['gate_pass_number']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Driver / Logistics</span>
                    <strong><?= e($inward['driver_name'] ?: '—') ?></strong>
                    <?php if ($inward['driver_contact']): ?>
                    <small class="text-muted d-block"><?= e($inward['driver_contact']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <span class="text-muted small d-block">Total Received</span>
                    <h5 class="fw-bold mb-0"><?= number_format($inward['total_received_qty']) ?> pcs</h5>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Accepted / Stocked</span>
                    <h5 class="fw-bold text-success mb-0"><?= number_format($inward['total_accepted_qty']) ?> pcs</h5>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Rejected / Damaged</span>
                    <h5 class="fw-bold <?= $inward['total_rejected_qty'] > 0 ? 'text-danger' : 'text-muted' ?> mb-0">
                        <?= number_format($inward['total_rejected_qty']) ?> pcs
                    </h5>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Received By</span>
                    <strong><?= e($inward['received_by_name'] ?: 'Staff') ?></strong>
                </div>
            </div>

            <?php if (!empty($inward['notes'])): ?>
            <div class="mt-3 pt-3 border-top">
                <span class="text-muted small d-block fw-semibold">Remarks &amp; Inspection Notes</span>
                <p class="mb-0 text-secondary"><?= nl2br(e($inward['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">Inward Itemized Breakdown (<?= count($items) ?> items)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Product / Style</th>
                            <th>Variant Code &amp; SKU</th>
                            <th>Attributes</th>
                            <th class="text-center">Ordered</th>
                            <th class="text-center">Received</th>
                            <th class="text-center">Rejected</th>
                            <th class="text-center">Accepted</th>
                            <th class="pe-3">Notes / Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($it['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= e($it['parent_sku']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($it['variant_code'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace me-1"><?= e($it['variant_code']) ?></span>
                                <?php endif; ?>
                                <span class="font-monospace small"><?= e($it['variant_sku']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border me-1"><?= e($it['color_name']) ?></span>
                                <span class="badge bg-light text-dark border me-1"><?= e($it['design_name'] ?: 'Plain') ?></span>
                                <span class="badge bg-secondary text-white"><?= e($it['size_name']) ?></span>
                            </td>
                            <td class="text-center"><?= number_format($it['ordered_qty']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= number_format($it['received_qty']) ?></td>
                            <td class="text-center fw-bold <?= $it['damaged_qty'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= number_format($it['damaged_qty']) ?>
                            </td>
                            <td class="text-center fw-bold text-success fs-6"><?= number_format($it['accepted_qty']) ?></td>
                            <td class="pe-3 small text-muted"><?= e($it['rejection_reason'] ?: ($it['notes'] ?: '—')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
