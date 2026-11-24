<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Production Billing</h4>
        <div class="text-muted small">Bills from QC-passed Production Inventory</div>
    </div>
    <?php if (hasPermission('production_billing', 'create')): ?>
        <a class="btn btn-primary" href="<?= url('production/billing/create') ?>"><i class="bi bi-plus-lg me-1"></i>Create Bill</a>
    <?php endif; ?>
</div>

<!-- Production Billing KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase"><?= ($activeTab ?? 'normal') === 'direct' ? 'Direct Bills Revenue' : (($activeTab ?? 'normal') === 'all' ? 'All Billed Revenue' : 'Standard Billed Revenue') ?></span>
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="bi bi-currency-rupee fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">₹<?= number_format($stats['total_revenue'] ?? 0, 2) ?></h3>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <span class="text-success fw-semibold">₹<?= number_format($stats['paid_revenue'] ?? 0, 2) ?></span> Paid
                    <span class="text-muted">&bull;</span>
                    <span class="text-warning fw-semibold">₹<?= number_format($stats['pending_revenue'] ?? 0, 2) ?></span> Pending
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Bills -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase"><?= ($activeTab ?? 'normal') === 'direct' ? 'Direct Invoices' : (($activeTab ?? 'normal') === 'all' ? 'Total Invoices' : 'Standard Invoices') ?></span>
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1"><?= number_format($stats['total_bills'] ?? 0) ?></h3>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <span class="badge bg-success-subtle text-success"><?= $stats['paid_count'] ?? 0 ?> Settled</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis"><?= $stats['pending_count'] ?? 0 ?> Unsettled</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Bill-based Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Billed Customers</span>
                    <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1"><?= number_format($stats['unique_customers'] ?? 0) ?></h3>
                <div class="small text-muted">
                    <span class="fw-semibold text-info">Unique Clients</span> billed across QC inventory
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Realization Ratio -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Realization Ratio</span>
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle">
                        <i class="bi bi-check2-circle fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1"><?= !empty($stats['total_revenue']) ? round(($stats['paid_revenue'] / $stats['total_revenue']) * 100, 1) . '%' : '0%' ?></h3>
                <div class="small text-muted">
                    <span>Payment collection conversion rate</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for Bills Filtering (All, Customer, B2B Company, Direct) -->
<ul class="nav nav-pills mb-3 gap-2" id="billingTypeTabs">
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab ?? 'normal') === 'normal' ? 'active bg-primary' : 'bg-white border text-dark' ?> fw-semibold py-2 px-3 d-flex align-items-center gap-2" href="<?= url('production/billing?tab=normal') ?>">
            <i class="bi bi-receipt"></i>
            <span>Standard Bills</span>
            <span class="badge <?= ($activeTab ?? 'normal') === 'normal' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?> rounded-pill">
                <?= (int)($tabCounts['normal_count'] ?? 0) ?>
            </span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab ?? '') === 'customer' ? 'active bg-info text-white' : 'bg-white border text-dark' ?> fw-semibold py-2 px-3 d-flex align-items-center gap-2" href="<?= url('production/billing?tab=customer') ?>">
            <i class="bi bi-person"></i>
            <span>Customer Bills</span>
            <span class="badge <?= ($activeTab ?? '') === 'customer' ? 'bg-white text-info' : 'bg-info-subtle text-info' ?> rounded-pill">
                <?= (int)($tabCounts['customer_count'] ?? 0) ?>
            </span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab ?? '') === 'company' ? 'active bg-primary text-white' : 'bg-white border text-dark' ?> fw-semibold py-2 px-3 d-flex align-items-center gap-2" href="<?= url('production/billing?tab=company') ?>">
            <i class="bi bi-building"></i>
            <span>Company Bills</span>
            <span class="badge <?= ($activeTab ?? '') === 'company' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?> rounded-pill">
                <?= (int)($tabCounts['company_count'] ?? 0) ?>
            </span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab ?? '') === 'direct' ? 'active bg-warning text-dark' : 'bg-white border text-dark' ?> fw-semibold py-2 px-3 d-flex align-items-center gap-2" href="<?= url('production/billing?tab=direct') ?>">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Direct Bills (Separated)</span>
            <span class="badge <?= ($activeTab ?? '') === 'direct' ? 'bg-dark text-white' : 'bg-warning-subtle text-warning-emphasis' ?> rounded-pill">
                <?= (int)($tabCounts['direct_count'] ?? 0) ?>
            </span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($activeTab ?? '') === 'all' ? 'active bg-secondary text-white' : 'bg-white border text-dark' ?> fw-semibold py-2 px-3 d-flex align-items-center gap-2" href="<?= url('production/billing?tab=all') ?>">
            <i class="bi bi-layers-half"></i>
            <span>All Bills</span>
            <span class="badge <?= ($activeTab ?? '') === 'all' ? 'bg-white text-secondary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill">
                <?= (int)($tabCounts['total_count'] ?? 0) ?>
            </span>
        </a>
    </li>
</ul>

<?php if (($activeTab ?? '') === 'direct'): ?>
    <div class="alert alert-warning border border-warning-subtle py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 rounded-3">
        <div class="d-flex align-items-center gap-2 small">
            <i class="bi bi-info-circle-fill text-warning fs-5"></i>
            <span><strong>Direct Bills Tab Active:</strong> Showing only bills marked as Direct Bills. These are handled separately and excluded from normal billing counts and standard exports.</span>
        </div>
        <a href="<?= url('export/direct-bills-csv') ?>" class="btn btn-sm btn-outline-dark fw-semibold" target="_blank">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Direct Bills CSV
        </a>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Production Invoices &amp; Bills</h6>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="billingSearchInput" class="form-control form-control-sm" placeholder="🔍 Search bill #, buyer, amount..." style="width: 250px;" oninput="filterBillingLive(this.value)">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="billingTable">
            <thead class="table-light">
                <tr>
                    <th>Bill No.</th>
                    <th>Date</th>
                    <th>Bill To</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="billingTableBody">
                <?php if (empty($bills)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No Production Bills have been created.</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr class="billing-row">
                            <td class="fw-semibold">
                                <div><?= htmlspecialchars($bill['bill_number']) ?></div>
                                <?php if (!empty($bill['is_direct_bill'])): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Direct Bill
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="badge <?= ($bill['buyer_type'] ?? 'COMPANY') === 'COMPANY' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'text-bg-light' ?>">
                                        <i class="bi <?= ($bill['buyer_type'] ?? 'COMPANY') === 'COMPANY' ? 'bi-building' : 'bi-person' ?> me-1"></i><?= htmlspecialchars($bill['buyer_type'] ?? 'COMPANY') ?>
                                    </span>
                                    <?php if (($bill['buyer_type'] ?? 'COMPANY') === 'COMPANY' && (int)($bill['linked_batch_count'] ?? 0) === 0): ?>
                                        <a href="<?= url('production/billing/move-to-batch/' . (int)$bill['id']) ?>" class="badge bg-success text-white text-decoration-none shadow-sm" title="Directly move to batch creation for this company bill">
                                            <i class="bi bi-box-seam me-1"></i>Pack Batch
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-semibold"><?= htmlspecialchars(($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER' ? ($bill['customer_name'] ?? '') : ($bill['company_name'] ?? '')) ?></div>
                                <small class="text-muted"><?= htmlspecialchars(($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER' ? ($bill['customer_phone'] ?? '') : ($bill['company_phone'] ?? '')) ?></small>
                            </td>
                            <td class="text-center"><?= (int)$bill['line_count'] ?></td>
                            <td class="text-end fw-semibold">₹<?= number_format((float)$bill['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $bill['payment_status'] === 'PAID' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                    <?= htmlspecialchars($bill['payment_status']) ?>
                                </span>
                                <?php if ((int)($bill['linked_batch_count'] ?? 0) > 0): ?>
                                    <?php if ((int)($bill['preparing_batch_count'] ?? 0) > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Batch approval pending</span>
                                    <?php elseif ((int)($bill['approved_batch_count'] ?? 0) > 0): ?>
                                        <span class="badge bg-primary-subtle text-primary">Ready for outward</span>
                                    <?php elseif ((int)($bill['dispatched_batch_count'] ?? 0) > 0): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis">Dispatched</span>
                                    <?php elseif ((int)($bill['received_batch_count'] ?? 0) === (int)$bill['linked_batch_count']): ?>
                                        <span class="badge bg-success-subtle text-success">Received</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="action-btns-group justify-content-end">
                                    <?php if ((int)($bill['linked_batch_count'] ?? 0) === 0): ?>
                                        <a class="btn btn-outline-success btn-action-icon" title="Directly move to batch creation for this bill" href="<?= url('production/billing/move-to-batch/' . (int)$bill['id']) ?>"><i class="bi bi-box-seam"></i></a>
                                    <?php elseif ((int)($bill['preparing_batch_count'] ?? 0) > 0): ?>
                                        <a class="btn btn-outline-warning btn-action-icon" title="Review linked production batches" href="<?= url('production/batches') ?>"><i class="bi bi-box-seam"></i></a>
                                    <?php elseif ((int)($bill['approved_batch_count'] ?? 0) > 0): ?>
                                        <a class="btn btn-outline-primary btn-action-icon" title="Continue to outward dispatch" href="<?= url('production/outward') ?>"><i class="bi bi-truck"></i></a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('production_billing', 'view')): ?>
                                        <a class="btn btn-outline-info btn-action-icon" title="View" href="<?= url('production/billing/view/' . (int)$bill['id']) ?>"><i class="bi bi-eye"></i></a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('production_billing', 'edit') && (int)($bill['linked_batch_count'] ?? 0) === 0): ?>
                                        <a class="btn btn-outline-primary btn-action-icon" title="Edit" href="<?= url('production/billing/edit/' . (int)$bill['id']) ?>"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('production_billing', 'print')): ?>
                                        <a class="btn btn-outline-success btn-action-icon" title="Print" target="_blank" href="<?= url('production/billing/print/' . (int)$bill['id']) ?>"><i class="bi bi-printer"></i></a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('production_billing', 'delete') && (int)($bill['linked_batch_count'] ?? 0) === 0): ?>
                                        <form class="d-inline" method="POST" action="<?= url('production/billing/delete/' . (int)$bill['id']) ?>" onsubmit="return confirm('Delete this bill? Its billed QR pieces will become available again.')">
                                            <?= CSRF::input() ?>
                                            <button class="btn btn-outline-danger btn-action-icon" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterBillingLive(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#billingTableBody .billing-row');
    rows.forEach(r => {
        const text = (r.textContent || '').toLowerCase();
        r.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}
</script>
