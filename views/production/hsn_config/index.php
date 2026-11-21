<?php
/**
 * Production HSN Config – Index View
 */
?>
<div class="container-fluid py-4">

    <?php if ($success = Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error = Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-semibold"><i class="bi bi-upc-scan me-2 text-primary"></i>HSN Config</h4>
            <small class="text-muted">Manage Production HSN Codes &amp; GST Percentages</small>
        </div>
        <a href="<?= url('production/hsn-config/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add HSN
        </a>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>HSN Code</th>
                            <th>GST %</th>
                            <th>Categories</th>
                            <th>Subcategories</th>
                            <th>Sub-subcategories</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold font-monospace"><?= htmlspecialchars($item['hsn_code']) ?></td>
                            <td><?= number_format((float)$item['gst_percentage'], 2) ?>%</td>
                            <td><?= htmlspecialchars($item['categories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['subcategories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['sub_subcategories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['product'] ?? '—') ?></td>
                            <td>
                                <?php if ($item['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('production/hsn-config/view/' . $item['id']) ?>"
                                   class="btn btn-sm btn-outline-info me-1" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/hsn-config/edit/' . $item['id']) ?>"
                                   class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      action="<?= url('production/hsn-config/delete/' . $item['id']) ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete HSN Code <?= htmlspecialchars(addslashes($item['hsn_code'])) ?>? This cannot be undone.');">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3 mb-1">No HSN codes configured yet.</p>
                <a href="<?= url('production/hsn-config/create') ?>" class="btn btn-primary btn-sm mt-2">
                    <i class="bi bi-plus-lg me-1"></i> Add First HSN Code
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
