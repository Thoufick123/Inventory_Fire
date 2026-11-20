<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-upc-scan me-2 text-primary"></i>HSN Code Catalog</h2>
        <p class="text-muted small mb-0">Harmonized System of Nomenclature (HSN) directory for GST compliance, procurement &amp; billing.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddHsn">
            <i class="bi bi-plus-circle me-1"></i>Add New HSN Code
        </button>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total HSN Directory</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($stats['total_codes'] ?? 0)) ?></div>
                <div class="small text-muted">Official GST tariff commodities &amp; codes</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Filtered Results</div>
                <div class="fs-3 fw-bold text-info"><?= number_format((int)$totalItems) ?></div>
                <div class="small text-muted">
                    <?= $search !== '' ? ('Matching query: "' . e($search) . '"') : 'Showing all active catalog codes' ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Page View Range</div>
                <div class="fs-3 fw-bold text-success">
                    <?= $totalItems > 0 ? (number_format(($page - 1) * $perPage + 1) . ' – ' . number_format(min($page * $perPage, $totalItems))) : '0' ?>
                </div>
                <div class="small text-muted">Page <?= $page ?> of <?= $totalPages ?> pages</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="card border shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('hsn') ?>" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by HSN code (e.g. 6109, 5208) or commodity description..." value="<?= e($search) ?>">
                    <?php if ($search !== ''): ?>
                        <a href="<?= url('hsn') ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted text-nowrap mb-0">Show:</label>
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 per page</option>
                        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 per page</option>
                        <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 per page</option>
                        <option value="200" <?= $perPage === 200 ? 'selected' : '' ?>>200 per page</option>
                    </select>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-auto ms-auto text-muted small">
                Showing <strong><?= count($items) ?></strong> of <strong><?= number_format($totalItems) ?></strong> records
            </div>
        </form>
    </div>
</div>

<!-- HSN Table -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>HSN Directory Entries</h5>
        <span class="badge bg-secondary-subtle text-secondary border font-monospace">Page <?= $page ?> / <?= $totalPages ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th style="width: 70px;">#</th>
                    <th style="width: 160px;">HSN Code</th>
                    <th>Commodity / Description</th>
                    <th style="width: 140px;" class="text-center">GST Rate</th>
                    <th style="width: 150px;">Last Updated</th>
                    <th style="width: 130px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-upc-scan fs-1 d-block mb-2 text-muted"></i>
                            No HSN codes found matching your search "<?= e($search) ?>".
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $startRank = ($page - 1) * $perPage;
                    foreach ($items as $idx => $item): 
                    ?>
                        <tr>
                            <td class="text-muted"><?= $startRank + $idx + 1 ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border font-monospace fs-6 px-2 py-1">
                                    <?= e($item['hsn_code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($item['description']) ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($item['gst_rate'] !== null && (float)$item['gst_rate'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border">
                                        <?= number_format((float)$item['gst_rate'], 2) ?>% GST
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">
                                        Default / Exempt
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= !empty($item['updated_at']) ? date('d M Y, h:i A', strtotime($item['updated_at'])) : '—' ?></small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-edit-hsn" 
                                            title="Edit HSN"
                                            data-id="<?= (int)$item['id'] ?>"
                                            data-code="<?= e($item['hsn_code']) ?>"
                                            data-desc="<?= e($item['description']) ?>"
                                            data-gst="<?= $item['gst_rate'] !== null ? e($item['gst_rate']) : '' ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-delete-hsn" 
                                            title="Delete HSN"
                                            data-id="<?= (int)$item['id'] ?>"
                                            data-code="<?= e($item['hsn_code']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                Showing <strong><?= ($totalItems > 0 ? ($page - 1) * $perPage + 1 : 0) ?></strong> to <strong><?= min($page * $perPage, $totalItems) ?></strong> of <strong><?= number_format($totalItems) ?></strong> entries
            </div>
            <nav aria-label="HSN Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <!-- First -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('hsn?page=1&per_page=' . $perPage . ($search ? '&search=' . urlencode($search) : '')) ?>">&laquo; First</a>
                    </li>
                    <!-- Prev -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('hsn?page=' . ($page - 1) . '&per_page=' . $perPage . ($search ? '&search=' . urlencode($search) : '')) ?>">&lsaquo; Prev</a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage   = min($totalPages, $page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('hsn?page=' . $p . '&per_page=' . $perPage . ($search ? '&search=' . urlencode($search) : '')) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('hsn?page=' . ($page + 1) . '&per_page=' . $perPage . ($search ? '&search=' . urlencode($search) : '')) ?>">Next &rsaquo;</a>
                    </li>
                    <!-- Last -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('hsn?page=' . $totalPages . '&per_page=' . $perPage . ($search ? '&search=' . urlencode($search) : '')) ?>">Last &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add New HSN Code -->
<div class="modal fade" id="modalAddHsn" tabindex="-1" aria-labelledby="modalAddHsnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="modalAddHsnLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New HSN Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('hsn/store') ?>" id="formAddHsn">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label required fw-bold small text-dark">HSN Code *</label>
                        <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 5208, 61091000" maxlength="20" required autofocus>
                        <div class="form-text small">Harmonized System of Nomenclature code (2 to 8 digits).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required fw-bold small text-dark">Commodity / Description *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="e.g. Woven fabrics of cotton, containing 85% or more by weight of cotton..." required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark">Default GST Rate (%) <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="gst_rate" class="form-control" placeholder="e.g. 5.00, 12.00, 18.00 (leave empty if variable)">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-check-circle me-1"></i>Save HSN Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit HSN Code -->
<div class="modal fade" id="modalEditHsn" tabindex="-1" aria-labelledby="modalEditHsnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="modalEditHsnLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit HSN Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="formEditHsn">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label required fw-bold small text-dark">HSN Code *</label>
                        <input type="text" name="hsn_code" id="editHsnCode" class="form-control font-monospace fw-bold" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required fw-bold small text-dark">Commodity / Description *</label>
                        <textarea name="description" id="editHsnDesc" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark">Default GST Rate (%) <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="gst_rate" id="editHsnGst" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-check-circle me-1"></i>Update HSN Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delete HSN Confirmation -->
<div class="modal fade" id="modalDeleteHsn" tabindex="-1" aria-labelledby="modalDeleteHsnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fs-6 fw-bold" id="modalDeleteHsnLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="formDeleteHsn">
                <?= CSRF::field() ?>
                <div class="modal-body p-4 text-center">
                    <p class="mb-1">Are you sure you want to delete HSN code:</p>
                    <p class="fs-5 fw-bold text-danger font-monospace mb-2" id="deleteHsnCodeDisplay">—</p>
                    <small class="text-muted">This action cannot be undone.</small>
                </div>
                <div class="modal-footer bg-light py-2 px-4 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('modalEditHsn'));
    const deleteModal = new bootstrap.Modal(document.getElementById('modalDeleteHsn'));

    // Edit button click
    document.querySelectorAll('.btn-edit-hsn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const code = this.dataset.code;
            const desc = this.dataset.desc;
            const gst = this.dataset.gst;

            document.getElementById('formEditHsn').action = '<?= url("hsn/update/") ?>' + id;
            document.getElementById('editHsnCode').value = code;
            document.getElementById('editHsnDesc').value = desc;
            document.getElementById('editHsnGst').value = gst;

            editModal.show();
        });
    });

    // Delete button click
    document.querySelectorAll('.btn-delete-hsn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const code = this.dataset.code;

            document.getElementById('formDeleteHsn').action = '<?= url("hsn/delete/") ?>' + id;
            document.getElementById('deleteHsnCodeDisplay').textContent = code;

            deleteModal.show();
        });
    });
});
</script>
