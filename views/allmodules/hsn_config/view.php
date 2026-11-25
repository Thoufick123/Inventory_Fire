<?php
/**
 * Production HSN Config – View (Read-only)
 */
?>
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-semibold"><i class="bi bi-eye me-2 text-primary"></i>HSN Code Details</h4>
            <small class="text-muted">View Production HSN Config entry</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/hsn-config') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="<?= url('production/hsn-config/edit/' . $record['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        </div>
    </div>

    <!-- Detail Card -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="bi bi-upc-scan me-2"></i>HSN: <?= htmlspecialchars($record['hsn_code']) ?>
                    </h6>
                    <?php if ($record['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">HSN Code</dt>
                        <dd class="col-sm-8 fw-semibold font-monospace"><?= htmlspecialchars($record['hsn_code']) ?></dd>

                        <dt class="col-sm-4 text-muted">GST Percentage</dt>
                        <dd class="col-sm-8"><?= number_format((float)$record['gst_percentage'], 2) ?>%</dd>

                        <dt class="col-sm-4 text-muted">Categories</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($record['categories'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Subcategories</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($record['subcategories'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Sub-subcategories</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($record['sub_subcategories'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Product</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($record['product'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8">
                            <?php if ($record['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Created At</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($record['created_at'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Last Updated</dt>
                        <dd class="col-sm-8 mb-0"><?= htmlspecialchars($record['updated_at'] ?? '—') ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
