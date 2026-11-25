<div class="qr-generation-ui">
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>QR Code Generations</h2>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#printerSettingsModal">
            <i class="bi bi-sliders me-1 text-primary"></i> QR Format Configuration
        </button>
        <a href="<?= url('production/qr-generations/create?tab=' . e($activeTab)) ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Generate New QR Batch
        </a>
    </div>
</div>

<!-- QR Generation areas are filtered by the active concern. -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 gap-2" id="qrSourceTabs" role="tablist">
    <?php foreach ($tabs as $tab): ?>
        <?php $isActiveTab = $tab['key'] === $activeTab; ?>
        <li class="nav-item" role="presentation">
            <a href="<?= url('production/qr-generations?tab=' . $tab['key']) ?>"
               class="nav-link py-3 border shadow-sm rounded <?= $isActiveTab ? 'active fw-bold' : 'fw-semibold text-muted' ?>"
               role="tab" aria-selected="<?= $isActiveTab ? 'true' : 'false' ?>">
                <i class="bi <?= $tab['icon'] ?> me-2 text-<?= $tab['color'] ?> fs-5"></i><?= e($tab['label']) ?>
                <span class="badge bg-<?= $tab['color'] ?>-subtle text-<?= $tab['color'] ?> border ms-1">
                    <?= number_format((int)($tabCounts[$tab['key']] ?? 0)) ?>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total Batches</div>
                    <div class="fw-bold text-primary fs-3 mt-1"><?= number_format(count($batches)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-collection-fill text-primary me-1"></i>Generated QR batches</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-collection-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Total QR Labels</div>
                    <div class="fw-bold text-success fs-3 mt-1"><?= number_format($totalQrs) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-qr-code text-success me-1"></i>Printed piece labels</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-qr-code fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Manual Batches</div>
                    <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= count(array_filter($batches, fn($b) => $b['generation_type'] === 'MANUAL')) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-pencil-square text-info me-1"></i>Direct label runs</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Catalog-Linked Batches</div>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= count(array_filter($batches, fn($b) => $b['generation_type'] === 'CATALOG_VARIANT')) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-tags-fill text-warning me-1"></i>Catalog variant tags</div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-tags-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/qr-generations') ?>" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small text-muted fw-semibold mb-1">Search Batch / Brand / Prefix</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by batch code, brand, prefix..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Brand Name</label>
                <select name="brand_name" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Brands --</option>
                    <?php foreach ($brands as $b): ?>
                    <option value="<?= e($b['brand_name']) ?>" <?= ($filters['brand_name'] ?? '') === $b['brand_name'] ? 'selected' : '' ?>><?= e($b['brand_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Generation Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Types --</option>
                    <option value="MANUAL" <?= ($filters['type'] ?? '') === 'MANUAL' ? 'selected' : '' ?>>Manual Custom Generation</option>
                    <option value="CATALOG_VARIANT" <?= ($filters['type'] ?? '') === 'CATALOG_VARIANT' ? 'selected' : '' ?>>Catalog / Variant Linked</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center" style="height: 38px;"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="<?= url('production/qr-generations') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="height: 38px; width: 38px; padding: 0;" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-list-task me-2 text-primary"></i>Generated QR Batches</h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold">
            <?= count($batches) ?> Batches Found
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Batch Code</th>
                        <th>Area</th>
                        <th>Type</th>
                        <th>Printed Brand Name</th>
                        <th>Category Hierarchy</th>
                        <th>QR Prefix Pattern</th>
                        <th class="text-center">Labels Count</th>
                        <th>Created Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary"></i>
                            No QR batches generated yet. Click "Generate New QR Batch" above to start.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td class="ps-3">
                            <a href="<?= url('production/qr-generations/view/' . $batch['id'] . '?tab=' . e($activeTab)) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                <?= e($batch['batch_code']) ?>
                            </a>
                        </td>
                        <td>
                            <?php
                                $batchSource = strtoupper((string)($batch['qr_source'] ?? QrGenerationService::SOURCE_THIRD_PARTY));
                                $sourceMeta = null;
                                foreach ($tabs as $tab) {
                                    if ($tab['source'] === $batchSource) { $sourceMeta = $tab; break; }
                                }
                            ?>
                            <span class="badge bg-<?= e($sourceMeta['color'] ?? 'secondary') ?>-subtle text-<?= e($sourceMeta['color'] ?? 'secondary') ?> border">
                                <i class="bi <?= e($sourceMeta['icon'] ?? 'bi-qr-code') ?> me-1"></i><?= e($sourceMeta['label'] ?? str_replace('_', ' ', $batchSource)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($batch['generation_type'] === 'MANUAL'): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-pencil-fill me-1"></i>Manual</span>
                            <?php else: ?>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-tags-fill me-1"></i>Catalog Linked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="text-dark text-uppercase"><?= e($batch['brand_name']) ?></strong>
                        </td>
                        <td>
                            <div class="text-dark fw-semibold"><?= e($batch['category_name'] ?: 'General') ?></div>
                            <?php if (!empty($batch['subcategory_name']) || !empty($batch['sub_subcategory_name'])): ?>
                            <small class="text-muted">
                                <?= e($batch['subcategory_name'] ?? '') ?><?= !empty($batch['sub_subcategory_name']) ? ' › ' . e($batch['sub_subcategory_name']) : '' ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace"><?= e($batch['qr_prefix']) ?>-XXXX</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6 px-3 py-1">
                                <?= number_format($batch['generated_count'] ?: $batch['quantity']) ?> pcs
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?= date('d M Y, h:i A', strtotime($batch['created_at'])) ?>
                        </td>
                        <td class="text-end text-nowrap pe-3">
                            <div class="action-btns-group justify-content-end">
                                <a href="<?= url('production/qr-generations/view/' . $batch['id']) ?>" class="btn btn-action-icon btn-outline-info" title="View Generated Codes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/qr-generations/edit/' . $batch['id']) ?>" class="btn btn-action-icon btn-outline-primary" title="Edit QR Batch">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= url('production/qr-generations/print/' . $batch['id']) ?>" target="_blank" class="btn btn-action-icon btn-outline-secondary" title="Print Sticker Labels">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="<?= url('production/qr-generations/delete/' . $batch['id']) ?>" class="btn btn-action-icon btn-outline-danger" title="Delete Batch" onclick="event.preventDefault(); if (confirm('Delete this QR batch and all its codes?')) { document.getElementById('deleteQrBatchForm_<?= $batch['id'] ?>').submit(); }">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <form id="deleteQrBatchForm_<?= $batch['id'] ?>" method="POST" action="<?= url('production/qr-generations/delete/' . $batch['id']) ?>" class="d-none">
                                    <?= csrf_field() ?>
                                </form>
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

<?php require __DIR__ . '/_qr_format_modal.php'; ?>
