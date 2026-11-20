<?php
// Stock Adjustment View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-sliders2 text-primary me-2"></i>Stock Adjustment: <span class="font-monospace text-primary"><?= e($adjustment['adjustment_number']) ?></span>
            </h4>
            <p class="text-muted small mb-0">Recorded on <?= date('d M Y, h:i A', strtotime($adjustment['created_at'])) ?> · <?= e($adjustment['warehouse_name'] ?: 'Central Warehouse') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/stock-adjustments') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
            </a>
            <a href="<?= url('production/stock-adjustments/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Adjustment
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Adjustment Details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Adjustment Details</h6>
                    <div>
                        <?php if ($adjustment['adjustment_type'] === 'INCREASE'): ?>
                            <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-arrow-up-circle me-1"></i> INCREASE (+<?= number_format($adjustment['quantity']) ?> pcs)</span>
                        <?php else: ?>
                            <span class="badge bg-danger px-3 py-2 fs-6"><i class="bi bi-arrow-down-circle me-1"></i> DECREASE (-<?= number_format($adjustment['quantity']) ?> pcs)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Product Style / Catalog</span>
                            <h5 class="fw-bold text-dark mb-1"><?= e($adjustment['product_name']) ?></h5>
                            <span class="font-monospace small text-muted">SKU: <?= e($adjustment['parent_sku']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Variant Identifier</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <?php if (!empty($adjustment['variant_code'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace fs-6"><?= e($adjustment['variant_code']) ?></span>
                                <?php endif; ?>
                                <span class="font-monospace fw-semibold"><?= e($adjustment['variant_sku']) ?></span>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <span class="text-muted small d-block">Color</span>
                            <strong><?= e($adjustment['color_name']) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Design</span>
                            <strong><?= e($adjustment['design_name'] ?: 'Plain') ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Size</span>
                            <span class="badge bg-secondary"><?= e($adjustment['size_name']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Current Available Stock</span>
                            <strong class="text-primary fs-6"><?= number_format($currentStock) ?> pcs</strong>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <span class="text-muted small d-block fw-semibold">Reason &amp; Classification</span>
                        <div class="p-3 bg-light rounded-3 mt-1 text-dark fw-semibold">
                            <?= e($adjustment['reason']) ?>
                        </div>
                    </div>

                    <?php if (!empty($adjustment['notes'])): ?>
                    <div class="mt-3">
                        <span class="text-muted small d-block fw-semibold">Additional Remarks</span>
                        <p class="mb-0 text-secondary"><?= nl2br(e($adjustment['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Audit Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Audit Trail</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Adjustment Status:</span>
                            <span class="badge bg-success-subtle text-success"><?= e($adjustment['status']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Warehouse / Hub:</span>
                            <strong><?= e($adjustment['warehouse_name'] ?: 'Central Warehouse') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Adjusted By:</span>
                            <strong><?= e($adjustment['requested_by_name'] ?: 'Staff') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Timestamp:</span>
                            <span><?= date('d M Y, h:i:s A', strtotime($adjustment['created_at'])) ?></span>
                        </li>
                        <?php if (!empty($adjustment['qr_codes'])): ?>
                        <li class="list-group-item px-0">
                            <span class="text-muted d-block mb-1">Scanned QR Code Identity:</span>
                            <span class="badge bg-white text-dark border font-monospace text-break d-block p-2">
                                <?= e($adjustment['qr_codes']) ?>
                            </span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
