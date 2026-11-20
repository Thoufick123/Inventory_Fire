<?php
// Stock Adjustments Index View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-sliders2 text-primary me-2"></i>Stock Adjustments (QR)</h4>
            <p class="text-muted small mb-0">Reconcile physical stock counts, log inventory write-offs, damages, and found items</p>
        </div>
        <div>
            <a href="<?= url('production/stock-adjustments/create') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> New Stock Adjustment
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/stock-adjustments') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Adjustment #, Variant SKU, Product..." value="<?= e($filters['search']) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="adjustment_type" class="form-select form-select-sm">
                        <option value="">All Types (Increase / Decrease)</option>
                        <option value="INCREASE" <?= $filters['adjustment_type'] === 'INCREASE' ? 'selected' : '' ?>>+ Increase Stock</option>
                        <option value="DECREASE" <?= $filters['adjustment_type'] === 'DECREASE' ? 'selected' : '' ?>>- Decrease Stock</option>
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
                    <a href="<?= url('production/stock-adjustments') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Adjustments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Adjustment #</th>
                            <th>Date &amp; Time</th>
                            <th>Product / Style</th>
                            <th>Variant Code &amp; SKU</th>
                            <th>Attributes</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Quantity</th>
                            <th>Reason / Category</th>
                            <th>Adjusted By</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($adjustments)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                No stock adjustments recorded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($adjustments as $adj): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-primary font-monospace">
                                <a href="<?= url('production/stock-adjustments/view/' . $adj['id']) ?>" class="text-decoration-none">
                                    <?= e($adj['adjustment_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div><?= date('d M Y', strtotime($adj['created_at'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($adj['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($adj['product_name']) ?></div>
                                <small class="text-muted font-monospace"><?= e($adj['parent_sku']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($adj['variant_code'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace me-1"><?= e($adj['variant_code']) ?></span>
                                <?php endif; ?>
                                <span class="font-monospace small"><?= e($adj['variant_sku']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border me-1"><?= e($adj['color_name']) ?></span>
                                <span class="badge bg-light text-dark border me-1"><?= e($adj['design_name'] ?: 'Plain') ?></span>
                                <span class="badge bg-secondary text-white"><?= e($adj['size_name']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($adj['adjustment_type'] === 'INCREASE'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1">
                                        <i class="bi bi-arrow-up-circle me-1"></i> INCREASE
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger fw-bold px-2 py-1">
                                        <i class="bi bi-arrow-down-circle me-1"></i> DECREASE
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold fs-6 <?= $adj['adjustment_type'] === 'INCREASE' ? 'text-success' : 'text-danger' ?>">
                                <?= $adj['adjustment_type'] === 'INCREASE' ? '+' : '-' ?><?= number_format($adj['quantity']) ?> pcs
                            </td>
                            <td>
                                <div class="small fw-semibold text-truncate" style="max-width: 250px;" title="<?= e($adj['reason']) ?>">
                                    <?= e($adj['reason']) ?>
                                </div>
                            </td>
                            <td><small class="text-muted"><?= e($adj['requested_by_name'] ?: 'Staff') ?></small></td>
                            <td class="text-end pe-3">
                                <a href="<?= url('production/stock-adjustments/view/' . $adj['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View Audit Details">
                                    <i class="bi bi-eye"></i> View
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
