<?php
declare(strict_types=1);
?>

<div class="container-fluid py-3 cash-flow-page">
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h2 class="mb-0 fw-bold">Cash Management</h2>
            <span class="badge bg-light text-primary border px-2 py-1"><i class="bi bi-building me-1"></i><?= e($concernName) ?></span>
            <?php if (!empty($activeShift)): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-circle-fill me-1 small"></i>Shift Active: <?= e($activeShift['shift_code']) ?>
                </span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                    <i class="bi bi-pause-circle me-1"></i>Till Closed (No Active Shift)
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="<?= url('production/cash-flow/export-csv') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i>Export report
            </a>
            <?php if (!empty($activeShift)): ?>
                <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCloseShift">
                    <i class="bi bi-lock me-1"></i>Close Shift / End Day
                </button>
                <button type="button" class="btn btn-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalRecordExpense">
                    <i class="bi bi-dash-circle me-1"></i>Record Expense
                </button>
                <button type="button" class="btn btn-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCashTopUp">
                    <i class="bi bi-plus-circle me-1"></i>Cash Top-Up
                </button>
                <button type="button" class="btn btn-info text-white btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalBankDrop">
                    <i class="bi bi-bank me-1"></i>Bank Deposit
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalOpenShift">
                    <i class="bi bi-play-circle me-1"></i>Start Shift / Open Till
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 4 Top Metrics Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Live Drawer Cash -->
    <div class="col-xl-3 col-md-6">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Live Drawer Cash</div>
                    <div class="fw-bold text-primary fs-3 mt-1"><?= formatCurrency((float)((float)($activeShift ? $activeShift['expected_drawer'] : 0))) ?></div>
                    <div class="small text-muted mt-2 text-truncate">
                        <?php if (!empty($activeShift)): ?>
                            <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>Till Open</span> (Shift: <?= e($activeShift['shift_code']) ?>)
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-lock me-1"></i>Drawer Till Closed</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Today's Cash Inflow -->
    <div class="col-xl-3 col-md-6">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Today's Cash Inflow</div>
                    <div class="fw-bold text-success fs-3 mt-1">+<?= formatCurrency((float)($todayCashInflow)) ?></div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-arrow-up-right me-1 text-success"></i>Sales: <?= formatCurrency((float)($todayCashSales)) ?>
                    </div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-arrow-down-left-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Net Cash Position -->
    <div class="col-xl-3 col-md-6">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Net Cash Position</div>
                    <div class="fw-bold <?= $netCashPosition >= 0 ? 'text-info-emphasis' : 'text-danger' ?> fs-3 mt-1">
                        <?= $netCashPosition >= 0 ? '+' : '' ?><?= formatCurrency((float)($netCashPosition)) ?>
                    </div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-bank text-info me-1"></i>Bank Deposits: <?= formatCurrency((float)($todayBankDeposits)) ?>
                    </div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Digital Channels -->
    <div class="col-xl-3 col-md-6">
        <div class="card revenue-kpi-card border-0 border-start border-warning border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Digital Channels</div>
                    <div class="fw-bold text-warning-emphasis fs-3 mt-1"><?= formatCurrency((float)((float)($digitalStats['total'] ?? 0))) ?></div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-credit-card-2-front text-warning me-1"></i>UPI: <?= formatCurrency((float)((float)($digitalStats['upi'] ?? 0))) ?> · Card: <?= formatCurrency((float)((float)($digitalStats['card'] ?? 0))) ?>
                    </div>
                </div>
                <div class="bg-warning-subtle text-warning-emphasis revenue-kpi-icon">
                    <i class="bi bi-credit-card-2-front fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Shift Banner & Balance Equation Card -->
<?php if (!empty($activeShift)): ?>
<div class="card border border-success-subtle shadow-sm mb-4 bg-light-subtle">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 bg-success text-white rounded">
                    <i class="bi bi-calculator fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Active Shift: <span class="font-monospace text-primary"><?= e($activeShift['shift_code']) ?></span></h5>
                    <div class="small text-muted">
                        Cashier: <strong><?= e($activeShift['cashier_name'] ?? 'System') ?></strong> · 
                        Opened at: <strong><?= e(formatDateTime($activeShift['opened_at'])) ?></strong>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCloseShift">
                <i class="bi bi-lock me-1"></i>Close &amp; Reconcile Shift
            </button>
        </div>

        <!-- Formula Balance Row -->
        <div class="row g-2 text-center align-items-center">
            <div class="col-md-2 col-4">
                <div class="small text-muted text-uppercase fw-semibold" style="font-size: 11px;">Opening Float</div>
                <div class="fs-6 fw-bold"><?= formatCurrency((float)((float)$activeShift['opening_float'])) ?></div>
            </div>
            <div class="col-auto text-muted fw-bold d-none d-md-block">+</div>
            <div class="col-md-2 col-4">
                <div class="small text-muted text-uppercase fw-semibold" style="font-size: 11px;">Cash Sales</div>
                <div class="fs-6 fw-bold text-success">+<?= formatCurrency((float)((float)$activeShift['total_sales'])) ?></div>
            </div>
            <div class="col-auto text-muted fw-bold d-none d-md-block">-</div>
            <div class="col-md-2 col-4">
                <div class="small text-muted text-uppercase fw-semibold" style="font-size: 11px;">Cash Refunds</div>
                <div class="fs-6 fw-bold text-danger">-<?= formatCurrency((float)((float)$activeShift['total_refunds'])) ?></div>
            </div>
            <div class="col-auto text-muted fw-bold d-none d-md-block">-</div>
            <div class="col-md-2 col-4">
                <div class="small text-muted text-uppercase fw-semibold" style="font-size: 11px;">Expenses</div>
                <div class="fs-6 fw-bold text-warning-emphasis">-<?= formatCurrency((float)((float)$activeShift['total_expenses'])) ?></div>
            </div>
            <div class="col-auto text-muted fw-bold d-none d-md-block">-</div>
            <div class="col-md-2 col-4">
                <div class="small text-muted text-uppercase fw-semibold" style="font-size: 11px;">Bank Drops</div>
                <div class="fs-6 fw-bold text-info">-<?= formatCurrency((float)((float)$activeShift['total_bank_drops'])) ?></div>
            </div>
            <div class="col-auto text-muted fw-bold d-none d-md-block">=</div>
            <div class="col-md col-12">
                <div class="bg-white border rounded p-2 text-center shadow-sm">
                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 11px;">Expected Drawer Till</div>
                    <div class="fs-5 fw-bold text-primary"><?= formatCurrency((float)((float)$activeShift['expected_drawer'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-2">
        <ul class="nav nav-tabs card-header-tabs" id="cashFlowTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold active" id="tabLedgerBtn" data-bs-toggle="tab" data-bs-target="#tabLedger" type="button" role="tab">
                    <i class="bi bi-journal-text me-1"></i>Cash Flow Ledger <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($transactions) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tabShiftsBtn" data-bs-toggle="tab" data-bs-target="#tabShifts" type="button" role="tab">
                    <i class="bi bi-clock-history me-1"></i>Open/Close Shift Logs <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($shiftLogs) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tabExpensesBtn" data-bs-toggle="tab" data-bs-target="#tabExpenses" type="button" role="tab">
                    <i class="bi bi-wallet2 me-1"></i>Petty Cash Expenses <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($expenses) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tabBankDropsBtn" data-bs-toggle="tab" data-bs-target="#tabBankDrops" type="button" role="tab">
                    <i class="bi bi-bank me-1"></i>Bank Deposits <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($bankDeposits) ?></span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="cashFlowTabContent">
            
            <!-- TAB 1: Cash Flow Ledger -->
            <div class="tab-pane fade show active" id="tabLedger" role="tabpanel">
                <div class="p-3 border-bottom bg-light-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Sales and shift-generated entries are read-only. Manual top-ups, expenses, and bank deposits are tracked with full audit history.</small>
                    <input type="text" id="ledgerSearchInput" class="form-control form-control-sm" placeholder="🔍 Search records..." style="width: 240px;" oninput="filterLedgerTable(this.value)">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="ledgerTable">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-secondary">
                                <th>TX #</th>
                                <th>Date &amp; Time</th>
                                <th>Type / Category</th>
                                <th>Flow</th>
                                <th>Mode</th>
                                <th class="text-end">Amount</th>
                                <th>Shift #</th>
                                <th>Description / Ref</th>
                                <th>Handled By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-5">No cash transactions logged yet.</td></tr>
                            <?php else: foreach ($transactions as $tx): 
                                $isInflow = ($tx['flow'] === 'INFLOW');
                            ?>
                                <tr class="ledger-row" data-search="<?= strtolower(e($tx['tx_number'] . ' ' . $tx['transaction_type'] . ' ' . ($tx['source_or_recipient'] ?? '') . ' ' . ($tx['reason'] ?? '') . ' ' . ($tx['shift_code'] ?? ''))) ?>">
                                    <td class="font-monospace fw-bold text-primary"><?= e($tx['tx_number']) ?></td>
                                    <td class="small text-nowrap"><?= e(formatDateTime($tx['created_at'])) ?></td>
                                    <td>
                                        <?php if ($tx['transaction_type'] === 'FLOAT_OPEN'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Float Opening</span>
                                        <?php elseif ($tx['transaction_type'] === 'FLOAT_TOPUP'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Float Top-Up</span>
                                        <?php elseif ($tx['transaction_type'] === 'BANK_DROP'): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">Bank Cash Drop</span>
                                        <?php elseif ($tx['transaction_type'] === 'PETTY_EXPENSE'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Petty Expense</span>
                                        <?php elseif ($tx['transaction_type'] === 'SALE_INFLOW'): ?>
                                            <span class="badge bg-success text-white">Cash Sale</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= e($tx['transaction_type']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isInflow ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                                            <i class="bi <?= $isInflow ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i><?= e($tx['flow']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($tx['payment_mode']) ?></span></td>
                                    <td class="text-end fw-bold <?= $isInflow ? 'text-success' : 'text-danger' ?>">
                                        <?= $isInflow ? '+' : '-' ?><?= formatCurrency((float)((float)$tx['amount'])) ?>
                                    </td>
                                    <td class="font-monospace small"><?= e($tx['shift_code'] ?? '—') ?></td>
                                    <td>
                                        <div class="fw-semibold small text-dark"><?= e($tx['source_or_recipient'] ?: '—') ?></div>
                                        <div class="small text-muted"><?= e($tx['reason'] ?: '—') ?></div>
                                    </td>
                                    <td class="small text-muted"><?= e($tx['user_name'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Shift Logs -->
            <div class="tab-pane fade" id="tabShifts" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-secondary">
                                <th>Shift Code</th>
                                <th>Status</th>
                                <th>Cashier</th>
                                <th>Opened At</th>
                                <th>Closed At</th>
                                <th class="text-end">Opening Float</th>
                                <th class="text-end">Expected Till</th>
                                <th class="text-end">Actual Counted</th>
                                <th class="text-end">Variance</th>
                                <th>Closing Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shiftLogs)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-5">No shift history found.</td></tr>
                            <?php else: foreach ($shiftLogs as $sh): 
                                $var = (float)$sh['variance'];
                            ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-primary"><?= e($sh['shift_code']) ?></td>
                                    <td>
                                        <?php if ($sh['status'] === 'OPEN'): ?>
                                            <span class="badge bg-success"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>OPEN</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i>CLOSED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold small"><?= e($sh['cashier_name'] ?? '—') ?></td>
                                    <td class="small text-nowrap"><?= e(formatDateTime($sh['opened_at'])) ?></td>
                                    <td class="small text-nowrap"><?= !empty($sh['closed_at']) ? e(formatDateTime($sh['closed_at'])) : '—' ?></td>
                                    <td class="text-end fw-semibold"><?= formatCurrency((float)((float)$sh['opening_float'])) ?></td>
                                    <td class="text-end fw-semibold text-primary"><?= formatCurrency((float)((float)$sh['expected_cash'])) ?></td>
                                    <td class="text-end fw-bold"><?= formatCurrency((float)((float)$sh['actual_cash'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($sh['status'] === 'OPEN'): ?>
                                            <span class="text-muted small">In Progress</span>
                                        <?php elseif ($var == 0): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>₹0.00 Exact</span>
                                        <?php elseif ($var > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-arrow-up me-1"></i>+<?= formatCurrency((float)($var)) ?> Surplus</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-down me-1"></i>-<?= formatCurrency((float)(abs($var))) ?> Short</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= e($sh['closing_notes'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: Petty Expenses -->
            <div class="tab-pane fade" id="tabExpenses" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-secondary">
                                <th>TX #</th>
                                <th>Date &amp; Time</th>
                                <th>Recipient / Category</th>
                                <th>Voucher / Ref</th>
                                <th>Description / Reason</th>
                                <th class="text-end">Amount</th>
                                <th>Handled By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-5">No petty cash expenses recorded yet.</td></tr>
                            <?php else: foreach ($expenses as $ex): ?>
                                <tr>
                                    <td class="font-monospace text-primary fw-bold"><?= e($ex['tx_number']) ?></td>
                                    <td class="small text-nowrap"><?= e(formatDateTime($ex['created_at'])) ?></td>
                                    <td><strong class="text-dark"><?= e($ex['source_or_recipient'] ?: 'Expense') ?></strong></td>
                                    <td class="font-monospace small"><?= e($ex['reference_number'] ?: '—') ?></td>
                                    <td class="small text-muted"><?= e($ex['reason'] ?: '—') ?></td>
                                    <td class="text-end fw-bold text-danger">-<?= formatCurrency((float)((float)$ex['amount'])) ?></td>
                                    <td class="small text-muted"><?= e($ex['user_name'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: Bank Deposits -->
            <div class="tab-pane fade" id="tabBankDrops" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-secondary">
                                <th>Deposit #</th>
                                <th>Deposit Date</th>
                                <th>Bank Name</th>
                                <th>Slip / CDM #</th>
                                <th>Remarks</th>
                                <th class="text-end">Deposit Amount</th>
                                <th>Logged By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bankDeposits)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-5">No bank deposits logged yet.</td></tr>
                            <?php else: foreach ($bankDeposits as $bd): ?>
                                <tr>
                                    <td class="font-monospace text-info fw-bold"><?= e($bd['deposit_number']) ?></td>
                                    <td class="small text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i><?= e(formatDate($bd['deposit_date'])) ?></td>
                                    <td><strong class="text-dark"><i class="bi bi-bank me-1 text-primary"></i><?= e($bd['bank_name']) ?></strong></td>
                                    <td class="font-monospace small"><?= e($bd['slip_number'] ?: '—') ?></td>
                                    <td class="small text-muted"><?= e($bd['remarks'] ?: '—') ?></td>
                                    <td class="text-end fw-bold text-primary"><?= formatCurrency((float)((float)$bd['amount'])) ?></td>
                                    <td class="small text-muted"><?= e($bd['user_name'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

</div>

<!-- ================= MODALS MATCHING SCREENSHOTS ================= -->

<!-- MODAL 1: Cash Injection / Float Top-Up (media_1795340136095_d54264ce.jpg) -->
<div class="modal fade" id="modalCashTopUp" tabindex="-1" aria-labelledby="modalCashTopUpLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-success d-flex align-items-center" id="modalCashTopUpLabel">
                    <i class="bi bi-plus-circle me-2"></i>Cash Injection / Float Top-Up
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/cash-flow/top-up') ?>">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Top-Up Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 fw-bold">₹</span>
                            <input type="number" step="0.01" min="1" class="form-control border-start-0 fw-bold fs-4" name="top_up_amount" required placeholder="0.00" autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Source / Provided By</label>
                        <input type="text" class="form-control" name="source" placeholder="Store Owner" value="Store Owner">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Reason / Description</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g. Added change float for busy weekend">
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Add Cash to Drawer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Bank Cash Drop / Safe Transfer (media_1795340139850_7c29e53a.jpg) -->
<div class="modal fade" id="modalBankDrop" tabindex="-1" aria-labelledby="modalBankDropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-info d-flex align-items-center" id="modalBankDropLabel">
                    <i class="bi bi-bank me-2"></i>Bank Cash Drop / Safe Transfer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/cash-flow/bank-drop') ?>">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Deposit Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 fw-bold">₹</span>
                            <input type="number" step="0.01" min="1" class="form-control border-start-0 fw-bold fs-4" name="deposit_amount" required placeholder="0.00" autofocus>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="bank_name" value="Primary Bank" required placeholder="e.g. HDFC / SBI / ICICI">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Deposit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="deposit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Account / CDM Slip # (Optional)</label>
                        <input type="text" class="form-control font-monospace" name="slip_number" placeholder="e.g. CDM-895123">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Remarks</label>
                        <input type="text" class="form-control" name="remarks" placeholder="e.g. Day cash deposit dropped at CDM machine">
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-semibold"><i class="bi bi-check2-circle me-1"></i>Record Bank Drop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: End Shift & Reconcile Drawer Till (media_1795340144232_64427dce.jpg) -->
<div class="modal fade" id="modalCloseShift" tabindex="-1" aria-labelledby="modalCloseShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center mb-0" id="modalCloseShiftLabel">
                        <i class="bi bi-lock-fill me-2"></i>End Shift &amp; Reconcile Drawer Till
                    </h5>
                    <div class="small text-muted">Shift: <strong class="font-monospace text-primary"><?= e($activeShift['shift_code'] ?? 'NONE') ?></strong></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/cash-flow/close-shift') ?>" id="closeShiftReconciliationForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <!-- Expected Cash Banner -->
                    <div class="p-3 bg-light rounded-3 border mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="text-uppercase small fw-bold text-muted" style="font-size: 11px;">Expected Cash in Drawer Till</div>
                            <div class="fs-4 fw-bold text-primary" id="reconcileExpectedDisplay"><?= formatCurrency((float)((float)($activeShift['expected_drawer'] ?? 0))) ?></div>
                        </div>
                        <div class="text-muted small text-end">
                            Sales: <span class="text-success">+<?= formatCurrency((float)((float)($activeShift['total_sales'] ?? 0))) ?></span> · 
                            Exp: <span class="text-danger">-<?= formatCurrency((float)((float)($activeShift['total_expenses'] ?? 0))) ?></span><br>
                            Refunds: <span class="text-danger">-<?= formatCurrency((float)((float)($activeShift['total_refunds'] ?? 0))) ?></span> · 
                            Drops: <span class="text-info">-<?= formatCurrency((float)((float)($activeShift['total_bank_drops'] ?? 0))) ?></span>
                        </div>
                    </div>

                    <!-- Denomination Grid -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-muted"><i class="bi bi-cash me-1"></i>Physical Cash Denomination Count</span>
                            <span class="badge bg-primary" id="denomCountedBadge">Counted Total: ₹0.00</span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹500</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="500" name="denom_500" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹200</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="200" name="denom_200" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹100</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="100" name="denom_100" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹50</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="50" name="denom_50" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹20</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="20" name="denom_20" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹10</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="10" name="denom_10" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">₹5</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="5" name="denom_5" value="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold" style="width: 55px;">Coins</span>
                                    <input type="number" min="0" step="1" class="form-control text-center denom-input" data-multiplier="1" name="denom_coins" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Counted Total & Discrepancy Display -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Actual Total Physical Cash Counted (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control fw-bold fs-5" name="actual_cash" id="actualCashInput" value="<?= number_format((float)($activeShift['expected_drawer'] ?? 0), 2, '.', '') ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Cash Discrepancy / Variance</label>
                            <div id="varianceBadgeContainer" class="d-flex align-items-center h-75 pt-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 w-100 text-center" id="varianceBadge">
                                    <i class="bi bi-check-circle me-1"></i>₹0.00 (Exact Match)
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">End of Shift Closing Notes</label>
                        <textarea class="form-control" name="closing_notes" rows="2" placeholder="e.g. Register handed over to evening cashier"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold" onclick="return confirm('Are you sure you want to reconcile and close this till shift?')">
                        <i class="bi bi-lock-fill me-1"></i>Confirm &amp; Close Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: Start Shift / Open Till -->
<div class="modal fade" id="modalOpenShift" tabindex="-1" aria-labelledby="modalOpenShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-center" id="modalOpenShiftLabel">
                    <i class="bi bi-play-circle me-2"></i>Start Shift / Open Drawer Till
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/cash-flow/open-shift') ?>">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Opening Cash Float (₹) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 fw-bold">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control border-start-0 fw-bold fs-4" name="opening_float" value="1500.00" required autofocus>
                        </div>
                        <div class="form-text small">Enter initial loose change float placed in drawer.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Opening Notes / Shift Assignment</label>
                        <input type="text" class="form-control" name="opening_notes" placeholder="e.g. Morning shift opened by supervisor">
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-play-circle me-1"></i>Open Till &amp; Start Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 5: Record Petty Expense -->
<div class="modal fade" id="modalRecordExpense" tabindex="-1" aria-labelledby="modalRecordExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-warning-emphasis d-flex align-items-center" id="modalRecordExpenseLabel">
                    <i class="bi bi-dash-circle me-2"></i>Record Petty Cash Expense
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/cash-flow/expense') ?>">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Expense Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0 fw-bold">₹</span>
                            <input type="number" step="0.01" min="0.5" class="form-control border-start-0 fw-bold fs-4" name="amount" required placeholder="0.00" autofocus>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Expense Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" required>
                                <option value="Tea & Refreshments">Tea &amp; Refreshments</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Delivery & Transport">Delivery &amp; Transport</option>
                                <option value="Cleaning & Sanitation">Cleaning &amp; Sanitation</option>
                                <option value="Minor Repairs">Minor Repairs</option>
                                <option value="Miscellaneous">Miscellaneous</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small text-muted">Paid To / Recipient</label>
                            <input type="text" class="form-control" name="paid_to" placeholder="e.g. Tea Vendor">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Voucher / Bill # (Optional)</label>
                        <input type="text" class="form-control font-monospace" name="voucher" placeholder="e.g. VCH-0045">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Reason / Notes</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g. Evening staff refreshments">
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-end gap-2 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-dash-circle me-1"></i>Deduct from Cash Drawer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter Ledger Table
function filterLedgerTable(query) {
    const term = query.toLowerCase().trim();
    document.querySelectorAll('#ledgerTable .ledger-row').forEach(function(row) {
        const text = row.getAttribute('data-search') || '';
        row.style.display = (term === '' || text.includes(term)) ? '' : 'none';
    });
}

// Denomination calculation for End Shift Modal
document.addEventListener('DOMContentLoaded', function() {
    const denomInputs = document.querySelectorAll('.denom-input');
    const actualCashInput = document.getElementById('actualCashInput');
    const countedBadge = document.getElementById('denomCountedBadge');
    const varianceBadge = document.getElementById('varianceBadge');
    const expectedDrawerAmount = <?= (float)($activeShift['expected_drawer'] ?? 0) ?>;

    function recalculateReconciliation() {
        let totalCounted = 0;
        let anyCountedEntered = false;
        denomInputs.forEach(function(input) {
            const count = parseFloat(input.value) || 0;
            const mult = parseFloat(input.dataset.multiplier) || 1;
            if (count > 0) anyCountedEntered = true;
            totalCounted += (count * mult);
        });

        if (countedBadge) {
            countedBadge.textContent = 'Counted Total: ₹' + totalCounted.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        if (anyCountedEntered && actualCashInput) {
            actualCashInput.value = totalCounted.toFixed(2);
        }

        const actualVal = parseFloat(actualCashInput ? actualCashInput.value : 0) || 0;
        const diff = actualVal - expectedDrawerAmount;

        if (varianceBadge) {
            if (Math.abs(diff) < 0.01) {
                varianceBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 w-100 text-center';
                varianceBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i>₹0.00 (Exact Match)';
            } else if (diff > 0) {
                varianceBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6 w-100 text-center';
                varianceBadge.innerHTML = '<i class="bi bi-arrow-up-circle me-1"></i>+₹' + diff.toFixed(2) + ' (Cash Surplus)';
            } else {
                varianceBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 w-100 text-center';
                varianceBadge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>-₹' + Math.abs(diff).toFixed(2) + ' (Cash Shortage)';
            }
        }
    }

    denomInputs.forEach(function(input) {
        input.addEventListener('input', recalculateReconciliation);
    });

    if (actualCashInput) {
        actualCashInput.addEventListener('input', recalculateReconciliation);
    }
});
</script>
