<style>
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
    background-color: transparent;
}
.btn-outline-purple:hover,
.btn-outline-purple:focus,
.btn-outline-purple:active {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}
</style>
<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>QR Generations</h2>
    </div>
    <a href="<?= url('purchases/mapping/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New QR Generation
    </a>
</div>

<?php
$qrSellerIds = [];
$qrInvoiceCount = count($mappingDrafts ?? []);
$qrMovedCount = 0;
$qrDraftCount = 0;
$qrTotalProducts = 0;
$qrTotalQty = 0.0;
$qrTotalAmount = 0.0;
foreach (($mappingDrafts ?? []) as $md) {
    if (!empty($md['seller_id'])) {
        $qrSellerIds[(int)$md['seller_id']] = true;
    }
    if (($md['status'] ?? '') === 'MOVED') {
        $qrMovedCount++;
    } else {
        $qrDraftCount++;
    }
    $qrTotalProducts += (int)($md['product_count'] ?? 0);
    $qrTotalQty += (float)($md['total_quantity'] ?? 0);
    $qrTotalAmount += (float)($md['total_amount'] ?? 0);
}
?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Sellers</div>
                    <div class="fs-3 fw-bold text-success mt-1"><?= number_format(count($qrSellerIds)) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-people text-success me-1"></i>Unique suppliers</div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Invoices</div>
                    <div class="fs-3 fw-bold text-primary mt-1"><?= number_format($qrInvoiceCount) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-receipt text-primary me-1"></i><?= $qrMovedCount ?> moved · <?= $qrDraftCount ?> draft</div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-receipt-cutoff fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Products &amp; Qty</div>
                    <div class="fs-3 fw-bold text-warning-emphasis mt-1"><?= number_format($qrTotalProducts) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-box-seam text-warning me-1"></i>Total Qty: <?= number_format($qrTotalQty, 2) ?></div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Purchase Amount</div>
                    <div class="fs-3 fw-bold text-info-emphasis mt-1"><?= formatCurrency($qrTotalAmount) ?></div>
                    <div class="small text-muted mt-2 text-truncate"><i class="bi bi-currency-rupee text-info me-1"></i>Total mapped value</div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>QR Generation Invoice Log</h5>
            <span class="badge bg-secondary" id="mappingInvoiceLogCountBadge"><?= count($mappingDrafts ?? []) ?> Entries</span>
        </div>
        <div class="input-group input-group-sm" style="width: 300px; max-width: 100%;">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" id="mappingInvoiceLogSearch" class="form-control" placeholder="Search invoice, seller, phone, PO #..." autocomplete="off">
            <button type="button" class="btn btn-outline-secondary d-none" id="clearMappingInvoiceLogSearch" title="Clear search">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Supplier Invoice</th>
                    <th>Supplier</th>
                    <th>Products</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Purchase</th>
                    <th>Concern</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody id="mappingInvoiceLogBody">
                <?php if (empty($mappingDrafts)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No Mapping invoices have been saved.</td></tr>
                <?php else: foreach ($mappingDrafts as $draft): ?>
                    <?php
                        $logSearchText = strtolower(trim(implode(' ', [
                            $draft['invoice_number'] ?? '',
                            $draft['seller_company'] ?? '',
                            $draft['seller_name'] ?? '',
                            $draft['seller_phone'] ?? '',
                            $draft['purchase_number'] ?? '',
                            $draft['status'] ?? '',
                            $draft['concern_name'] ?? '',
                            $draft['concern_code'] ?? '',
                        ])));
                    ?>
                    <tr class="mapping-invoice-log-row" data-search="<?= e($logSearchText) ?>">
                        <td><strong class="font-monospace"><?= e($draft['invoice_number']) ?></strong><div class="small text-muted"><?= e(date('d M Y', strtotime($draft['created_at']))) ?></div></td>
                        <td><?= e($draft['seller_company'] ?: $draft['seller_name']) ?></td>
                        <td><?= (int)($draft['product_count'] ?? 0) ?></td>
                        <td><?= number_format((float)($draft['total_quantity'] ?? 0), 3) ?></td>
                        <td><span class="badge <?= $draft['status'] === 'MOVED' ? 'bg-success' : ($draft['status'] === 'READY' ? 'bg-info text-dark' : 'bg-secondary') ?>"><?= e($draft['status']) ?></span></td>
                        <td>
                            <?php if (!empty($draft['purchase_number'])): ?>
                                <a href="<?= url('purchases/view/' . (int)$draft['purchase_id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none" title="View Purchase Bill">
                                    <i class="bi bi-receipt me-1"></i><?= e($draft['purchase_number']) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Not linked</span>
                            <?php endif; ?>
                        </td>
                        <td><?= renderConcernBadge($draft) ?></td>
                        <td class="text-end text-nowrap">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/view') ?>" title="View Mapping History &amp; Stock">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($draft['status'] === 'DRAFT' && hasPermission('production_purchase_mapping', 'edit')): ?>
                                    <a class="btn btn-sm btn-outline-purple" href="<?= url('purchases/mapping/draft/' . (int)$draft['id']) ?>" title="Edit Products &amp; Variants">
                                        <i class="bi bi-diagram-3-fill"></i>
                                    </a>
                                <?php elseif ($draft['status'] === 'MOVED' && hasPermission('production_purchase_mapping', 'edit')): ?>
                                    <?php if ((int)($draft['pending_qr_or_stock_count'] ?? 0) > 0): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= url('purchases/mapping/draft/' . (int)$draft['id']) ?>" title="Continue QR Generation &amp; Add Remaining Stock">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-purple" href="<?= url('purchases/mapping/draft/' . (int)$draft['id']) ?>" title="Edit Variant Details &amp; Pricing">
                                            <i class="bi bi-diagram-3-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (hasPermission('production_purchase_mapping', 'delete')): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/delete') ?>" class="d-inline js-log-confirm-form" data-confirm-title="Confirm Deletion" data-confirm-msg="Delete invoice <?= e($draft['invoice_number']) ?> and all its unstocked products? This cannot be undone." data-confirm-btn="Yes, Delete" data-confirm-delete="1">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="return_to" value="logs">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Invoice">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($draft['status'] !== 'MOVED' && (int)$draft['product_count'] > 0): ?>
                                    <form method="POST" action="<?= url('purchases/mapping/draft/' . (int)$draft['id'] . '/move') ?>" class="d-inline js-log-confirm-form" data-confirm-title="Move to Purchases" data-confirm-msg="Move mapping invoice <?= e($draft['invoice_number']) ?> to Purchases?" data-confirm-btn="Move to Purchase" data-confirm-delete="0">
                                        <?= CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm btn-success" title="Create / Move to Purchase">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$paginationPrefix = 'mappingLog';
$paginationItemName = 'invoices';
$paginationTotal = count($mappingDrafts ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>

<!-- Edit Invoice Info Modal -->
<div class="modal fade" id="editInvoiceLogModal" tabindex="-1" aria-labelledby="editInvoiceLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form id="editInvoiceLogForm" method="POST" action="">
                <?= CSRF::field() ?>
                <input type="hidden" name="return_to" value="logs">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="editInvoiceLogModalLabel">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Invoice Info
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Supplier / Vendor <span class="text-danger">*</span></label>
                        <select name="seller_id" id="editInvoiceSellerId" class="form-select" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($sellers ?? [] as $s): ?>
                                <option value="<?= (int)$s['id'] ?>">
                                    <?= e($s['company'] ? ($s['company'] . ' (' . $s['name'] . ')') : $s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Supplier Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" id="editInvoiceNumber" class="form-control font-monospace" required placeholder="e.g. INV-2026-001">
                    </div>

                    <?php if (!empty($concerns) && count($concerns) > 1): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Concern</label>
                            <select name="concern_id" id="editInvoiceConcernId" class="form-select">
                                <?php foreach ($concerns as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editInvoiceLogModalInstance = null;

function openEditInvoiceLogModal(draft) {
    const modalEl = document.getElementById('editInvoiceLogModal');
    if (!editInvoiceLogModalInstance) {
        editInvoiceLogModalInstance = new bootstrap.Modal(modalEl);
    }
    const form = document.getElementById('editInvoiceLogForm');
    form.action = '<?= url('purchases/mapping/draft/') ?>' + draft.id + '/update';
    document.getElementById('editInvoiceSellerId').value = draft.seller_id || '';
    document.getElementById('editInvoiceNumber').value = draft.invoice_number || '';
    const concernSelect = document.getElementById('editInvoiceConcernId');
    if (concernSelect && draft.concern_id) {
        concernSelect.value = draft.concern_id;
    }
    editInvoiceLogModalInstance.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('mappingInvoiceLogSearch');
    const clearBtn = document.getElementById('clearMappingInvoiceLogSearch');
    const countBadge = document.getElementById('mappingInvoiceLogCountBadge');
    const rows = Array.from(document.querySelectorAll('tr.mapping-invoice-log-row'));

    let mappingLogPagination = null;
    if (typeof window.initClientPagination === 'function') {
        mappingLogPagination = window.initClientPagination({
            container: '#mappingInvoiceLogBody',
            itemSelector: '.mapping-invoice-log-row',
            footer: '#mappingLogPaginationFooter',
            infoEl: '#mappingLogPageInfo',
            paginationList: '#mappingLogPaginationList',
            pageSizeSelect: '#mappingLogPageSizeSelect',
            pageSize: 10,
            itemName: 'invoices'
        });
    }

    function filterInvoiceLog() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        if (clearBtn) clearBtn.classList.toggle('d-none', q === '');
        const matchFn = (row) => {
            const hay = row.dataset.search || '';
            return !q || hay.includes(q);
        };
        let visible = 0;
        rows.forEach(row => {
            if (matchFn(row)) visible++;
        });
        if (mappingLogPagination) {
            mappingLogPagination.filter(matchFn);
        } else {
            rows.forEach(row => {
                row.style.display = matchFn(row) ? '' : 'none';
            });
        }
        if (countBadge) {
            countBadge.textContent = visible + (visible === 1 ? ' Entry' : ' Entries');
        }
    }

    searchInput?.addEventListener('input', filterInvoiceLog);
    clearBtn?.addEventListener('click', function () {
        if (searchInput) {
            searchInput.value = '';
            filterInvoiceLog();
            searchInput.focus();
        }
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.classList || !form.classList.contains('js-log-confirm-form')) return;
        if (form.dataset.confirmed === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        const isDelete = form.dataset.confirmDelete === '1';
        if (typeof window.showModernConfirm === 'function') {
            window.showModernConfirm({
                title: form.dataset.confirmTitle || (isDelete ? 'Confirm Deletion' : 'Please Confirm'),
                message: form.dataset.confirmMsg || 'Are you sure?',
                isDelete: isDelete,
                isSuccess: !isDelete,
                btnClass: isDelete ? 'btn-danger' : 'btn-success',
                btnText: form.dataset.confirmBtn || (isDelete ? 'Yes, Delete' : 'Confirm'),
                onConfirm: function () {
                    form.dataset.confirmed = '1';
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else form.submit();
                }
            });
        } else {
            form.dataset.confirmed = '1';
            form.submit();
        }
    }, true);
});
</script>
