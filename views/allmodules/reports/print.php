<?php
$prodUnit = $prodUnit ?? (class_exists('ProductionUnit') ? \ProductionUnit::getActiveFacility() : []);
$sellerCompany = $sellerCompany ?? [];
$summary = $data['summary'] ?? [];
$fin = $summary['financial'] ?? [];
$stk = $summary['stock'] ?? [];

$isProfit = ($fin['net_profit_loss'] ?? 0) >= 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($prodUnit['name'] ?? 'Production Unit') ?> — Master Performance &amp; Financial Report (<?= htmlspecialchars($periodLabel) ?>)</title>
    <style>
        * { box-sizing: border-box; }
        body { font: 12px/1.4 "Segoe UI", Arial, sans-serif; color: #1e293b; background: #f8fafc; margin: 0; padding: 24px; }
        .report-wrap { max-width: 1200px; margin: 0 auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .actions { max-width: 1200px; margin: 0 auto 16px; text-align: right; }
        .btn-print { padding: 9px 20px; background: #2563eb; color: #fff; font-weight: 600; font-size: 13px; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background .15s; }
        .btn-print:hover { background: #1d4ed8; }
        
        /* Header */
        .facility-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 18px; margin-bottom: 22px; }
        .facility-title { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 6px; letter-spacing: -0.02em; }
        .facility-meta { font-size: 12px; color: #475569; line-height: 1.5; }
        .period-box { text-align: right; }
        .period-badge { display: inline-block; background: #0f172a; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 4px 10px; border-radius: 4px; margin-bottom: 6px; }
        .period-dates { font-size: 14px; font-weight: 700; color: #0f172a; }
        .period-time { font-size: 11px; color: #64748b; margin-top: 4px; }

        /* Executive Financial & Stock Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
        .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
        .kpi-card.revenue { border-left: 4px solid #16a34a; background: #f0fdf4; }
        .kpi-card.cost { border-left: 4px solid #dc2626; background: #fef2f2; }
        .kpi-card.profit-pos { border-left: 4px solid #059669; background: #ecfdf5; }
        .kpi-card.profit-neg { border-left: 4px solid #b91c1c; background: #fff1f2; }
        .kpi-card.stock { border-left: 4px solid #2563eb; background: #eff6ff; }
        .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 4px; }
        .kpi-val { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 4px; }
        .kpi-sub { font-size: 11px; color: #475569; }

        /* Executive Summary Tables */
        .summary-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .panel-header { background: #f1f5f9; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; letter-spacing: 0.03em; }
        .panel-body { padding: 12px 14px; }
        .summary-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        .summary-table td { padding: 5px 4px; border-bottom: 1px dashed #e2e8f0; }
        .summary-table tr:last-child td { border-bottom: none; }
        .summary-table td.strong { font-weight: 700; color: #0f172a; }
        .summary-table tr.total-row td { border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; font-weight: 800; background: #f8fafc; padding: 7px 4px; }

        /* Section Headings */
        .section { margin: 26px 0; page-break-inside: avoid; }
        .section-header { display: flex; justify-content: space-between; align-items: center; background: #f1f5f9; padding: 8px 12px; border-left: 4px solid #2563eb; border-radius: 0 4px 4px 0; margin-bottom: 10px; }
        .section-title { font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0; }
        .section-count { font-size: 11px; font-weight: 600; color: #64748b; }

        /* Data Tables */
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.data-table th { background: #f8fafc; font-weight: 700; color: #334155; text-transform: uppercase; font-size: 10px; letter-spacing: 0.03em; }
        table.data-table tbody tr:nth-child(even) { background: #fcfcfd; }
        table.data-table tbody tr:hover { background: #f1f5f9; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .fw-bold { font-weight: 700; }
        .text-success { color: #16a34a !important; }
        .text-danger { color: #dc2626 !important; }
        .text-warning { color: #d97706 !important; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 9.5px; font-weight: 700; border-radius: 4px; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .badge-secondary { background: #f1f5f9; color: #475569; }

        /* Print media styles */
        @media print {
            body { margin: 0; padding: 0; background: #fff; font-size: 10.5px; color: #000; }
            .report-wrap { max-width: none; width: 100%; padding: 0; box-shadow: none; border-radius: 0; }
            .actions { display: none !important; }
            .section { page-break-inside: avoid; }
            table.data-table th { background: #eee !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .kpi-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #ccc !important; }
            .panel { -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body>

<div class="actions">
    <button class="btn-print" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print Report
    </button>
</div>

<main class="report-wrap">
    <!-- Facility Header -->
    <header class="facility-header">
        <div>
            <h1 class="facility-title"><?= htmlspecialchars($prodUnit['name'] ?? ($sellerCompany['company_name'] ?? 'Production Manufacturing Facility')) ?></h1>
            <div class="facility-meta">
                <?php if (!empty($prodUnit['address'])): ?>
                    <div><?= nl2br(htmlspecialchars((string)$prodUnit['address'])) ?></div>
                <?php endif; ?>
                <div style="margin-top: 3px;">
                    <?php if (!empty($prodUnit['code'])): ?><span><strong>Unit Code:</strong> <?= htmlspecialchars($prodUnit['code']) ?></span> &nbsp;·&nbsp; <?php endif; ?>
                    <?php if (!empty($prodUnit['phone'])): ?><span><strong>Phone:</strong> <?= htmlspecialchars($prodUnit['phone']) ?></span> &nbsp;·&nbsp; <?php endif; ?>
                    <?php if (!empty($prodUnit['gst_number'])): ?><span><strong>GSTIN:</strong> <?= htmlspecialchars($prodUnit['gst_number']) ?></span><?php endif; ?>
                </div>
                <div style="color: #64748b; font-size: 11px; margin-top: 2px;">Comprehensive Production &amp; Financial Intelligence Report</div>
                <div style="margin-top: 5px;">
                    <?php if (!empty($selectedConcerns)): ?>
                        <span style="font-size: 11px; font-weight: 700; color: #0f172a; margin-right: 4px;">Concern Scope:</span>
                        <?php foreach ($selectedConcerns as $sc): ?>
                            <span class="badge badge-info" style="font-size: 10px; margin-right: 3px;">[<?= htmlspecialchars($sc['code'] ?? '') ?>] <?= htmlspecialchars($sc['name'] ?? '') ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="badge badge-secondary" style="font-size: 10px;">All Concerns (Consolidated Enterprise)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="period-box">
            <span class="period-badge">Report Period</span>
            <div class="period-dates"><?= htmlspecialchars($periodLabel) ?></div>
            <div style="font-size: 11.5px; color: #475569; margin-top: 2px;"><?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?></div>
            <div class="period-time">Generated on <?= date('d M Y, h:i A') ?> IST</div>
        </div>
    </header>

    <!-- Executive KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card revenue">
            <div class="kpi-label text-success">Total Gross Revenue</div>
            <div class="kpi-val text-success"><?= formatCurrency((float)((float)($fin['gross_revenue'] ?? 0))) ?></div>
            <div class="kpi-sub">Bills: <?= formatCurrency((float)((float)($fin['billing_total'] ?? 0))) ?> + Scrap: <?= formatCurrency((float)((float)($fin['wastage_total'] ?? 0))) ?></div>
        </div>
        <div class="kpi-card cost">
            <div class="kpi-label text-danger">Total Expenditures</div>
            <div class="kpi-val text-danger"><?= formatCurrency((float)((float)($fin['total_expenditure'] ?? 0))) ?></div>
            <div class="kpi-sub">Purchases: <?= formatCurrency((float)((float)($fin['purchases_total'] ?? 0))) ?> + Exp: <?= formatCurrency((float)((float)($fin['expenses_total'] ?? 0))) ?></div>
        </div>
        <div class="kpi-card <?= $isProfit ? 'profit-pos' : 'profit-neg' ?>">
            <div class="kpi-label <?= $isProfit ? 'text-success' : 'text-danger' ?>">Net Income (<?= $isProfit ? 'Profit' : 'Loss' ?>)</div>
            <div class="kpi-val <?= $isProfit ? 'text-success' : 'text-danger' ?>"><?= formatCurrency((float)((float)($fin['net_profit_loss'] ?? 0))) ?></div>
            <div class="kpi-sub">Operating Margin: <strong><?= number_format((float)($fin['profit_margin_pct'] ?? 0), 2) ?>%</strong></div>
        </div>
        <div class="kpi-card stock">
            <div class="kpi-label text-primary">Production &amp; Stock Output</div>
            <div class="kpi-val text-primary"><?= number_format((int)($stk['batches_pieces'] ?? 0)) ?> Pcs</div>
            <div class="kpi-sub">Cut: <?= number_format((int)($stk['cuttings_pieces'] ?? 0)) ?> | Stitched: <?= number_format((int)($stk['stitching_pieces'] ?? 0)) ?> | Out: <?= number_format((int)($stk['outward_pieces'] ?? 0)) ?></div>
        </div>
    </div>

    <!-- Executive Summary Panels: Financial P&L + Operational Reconciliation -->
    <div class="summary-panels">
        <!-- Panel 1: Financial Inflow vs Outflow -->
        <div class="panel">
            <div class="panel-header">1. Executive Financial Performance (Income &amp; Loss Breakdown)</div>
            <div class="panel-body">
                <table class="summary-table">
                    <tr>
                        <td>Direct Factory Billing (Sales)</td>
                        <td class="center"><?= (int)($fin['billing_count'] ?? 0) ?> bills (<?= (int)($fin['billing_pieces'] ?? 0) ?> pcs)</td>
                        <td class="right strong text-success"><?= formatCurrency((float)((float)($fin['billing_total'] ?? 0))) ?></td>
                    </tr>
                    <tr>
                        <td>Cutting &amp; Stitching Scrap Sales</td>
                        <td class="center">Wastage Disposals</td>
                        <td class="right strong text-success"><?= formatCurrency((float)((float)($fin['wastage_total'] ?? 0))) ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL GROSS INFLOW (REVENUE)</strong></td>
                        <td class="center"><strong>All Sales</strong></td>
                        <td class="right strong text-success"><strong><?= formatCurrency((float)((float)($fin['gross_revenue'] ?? 0))) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Raw Fabric &amp; Material Purchases</td>
                        <td class="center"><?= (int)($fin['purchases_count'] ?? 0) ?> Purchase Orders</td>
                        <td class="right strong text-danger"><?= formatCurrency((float)((float)($fin['purchases_total'] ?? 0))) ?></td>
                    </tr>
                    <tr>
                        <td>Factory Operating &amp; Machinery Expenses</td>
                        <td class="center"><?= (int)($fin['expenses_count'] ?? 0) ?> Expense Items</td>
                        <td class="right strong text-danger"><?= formatCurrency((float)((float)($fin['expenses_total'] ?? 0))) ?></td>
                    </tr>
                    <tr>
                        <td>Customer Returns &amp; Credit Reversals</td>
                        <td class="center"><?= (int)($fin['returns_count'] ?? 0) ?> returns (<?= (int)($fin['returns_pieces'] ?? 0) ?> pcs)</td>
                        <td class="right strong text-danger"><?= formatCurrency((float)((float)($fin['returns_total'] ?? 0))) ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL OPERATING EXPENDITURES</strong></td>
                        <td class="center"><strong>All Outflows</strong></td>
                        <td class="right strong text-danger"><strong><?= formatCurrency((float)((float)($fin['total_expenditure'] ?? 0))) ?></strong></td>
                    </tr>
                    <tr style="background: <?= $isProfit ? '#ecfdf5' : '#fff1f2' ?>;">
                        <td style="font-weight:800; font-size:12px; color: <?= $isProfit ? '#15803d' : '#b91c1c' ?>;">NET OPERATING <?= $isProfit ? 'PROFIT' : 'LOSS' ?></td>
                        <td class="center fw-bold" style="color: <?= $isProfit ? '#15803d' : '#b91c1c' ?>;">Margin: <?= number_format((float)($fin['profit_margin_pct'] ?? 0), 2) ?>%</td>
                        <td class="right" style="font-weight:800; font-size:13px; color: <?= $isProfit ? '#15803d' : '#b91c1c' ?>;"><?= formatCurrency((float)((float)($fin['net_profit_loss'] ?? 0))) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Panel 2: Production Pipeline & Stock Inventory Reconciliation -->
        <div class="panel">
            <div class="panel-header">2. Production Pipeline &amp; Stock Reconciliation</div>
            <div class="panel-body">
                <table class="summary-table">
                    <tr>
                        <td>Fabric Inwarded (Rolls)</td>
                        <td class="center"><?= (int)($stk['inward_rolls_count'] ?? 0) ?> Rolls</td>
                        <td class="right strong"><?= number_format((float)($stk['inward_weight_kg'] ?? 0), 2) ?> kg</td>
                    </tr>
                    <tr>
                        <td>Garment Cutting Batches</td>
                        <td class="center"><?= (int)($stk['cuttings_batches_count'] ?? 0) ?> Batches</td>
                        <td class="right strong"><?= number_format((int)($stk['cuttings_pieces'] ?? 0)) ?> Cut Pcs</td>
                    </tr>
                    <tr>
                        <td>Stitching Completed</td>
                        <td class="center"><?= (int)($stk['stitching_batches_count'] ?? 0) ?> Batches</td>
                        <td class="right strong"><?= number_format((int)($stk['stitching_pieces'] ?? 0)) ?> Stitched Pcs</td>
                    </tr>
                    <tr>
                        <td>Quality Control (QC Passed)</td>
                        <td class="center">Inspected: <?= number_format((int)($stk['qc_inspected_pieces'] ?? 0)) ?></td>
                        <td class="right strong text-success"><?= number_format((int)($stk['qc_passed_pieces'] ?? 0)) ?> Pcs (<?= number_format((float)($stk['qc_yield_pct'] ?? 0), 1) ?>%)</td>
                    </tr>
                    <tr>
                        <td>Finished Goods Packed into Batches</td>
                        <td class="center"><?= (int)($stk['batches_boxes'] ?? 0) ?> Master Cartons</td>
                        <td class="right strong"><?= number_format((int)($stk['batches_pieces'] ?? 0)) ?> Packed Pcs</td>
                    </tr>
                    <tr>
                        <td>Dispatched to Warehouses (Outward)</td>
                        <td class="center"><?= (int)($stk['outward_boxes'] ?? 0) ?> Cartons Dispatched</td>
                        <td class="right strong"><?= number_format((int)($stk['outward_pieces'] ?? 0)) ?> Dispatched Pcs</td>
                    </tr>
                    <tr>
                        <td>Direct Factory Sales (Billed)</td>
                        <td class="center"><?= (int)($fin['billing_count'] ?? 0) ?> Direct Invoices</td>
                        <td class="right strong"><?= number_format((int)($stk['billing_pieces'] ?? 0)) ?> Sold Pcs</td>
                    </tr>
                    <tr class="total-row" style="background:#eff6ff;">
                        <td><strong>ESTIMATED ON-HAND FACTORY STOCK</strong></td>
                        <td class="center"><strong>Finished Balance</strong></td>
                        <td class="right strong" style="color:#1d4ed8; font-size:12px;"><strong><?= number_format((int)($stk['stock_on_hand_pieces'] ?? 0)) ?> Finished Pcs</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DETAILED MODULE DATA TABLES -->
    <!-- ========================================================================= -->

    <!-- 1. Purchases -->
    <?php if (in_array('purchases', $selectedModules, true) && !empty($data['purchases'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">1. Fabric &amp; Material Purchases (PO Orders)</h3>
                <span class="section-count"><?= count($data['purchases']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Purchase / PO #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Invoice #</th>
                        <th class="center">Total Items</th>
                        <th class="center">Rolls Count</th>
                        <th class="right">Total Weight (kg)</th>
                        <th class="right">Total Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['purchases'] as $pur): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pur['purchase_number']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($pur['purchase_date'])) ?></td>
                            <td><?= htmlspecialchars($pur['supplier_company'] ?: ($pur['supplier_name'] ?? 'Direct Supplier')) ?></td>
                            <td><?= htmlspecialchars($pur['invoice_number'] ?? '-') ?></td>
                            <td class="center"><?= (int)$pur['total_items'] ?></td>
                            <td class="center fw-bold"><?= (int)$pur['total_rolls'] ?></td>
                            <td class="right"><?= number_format((float)$pur['total_weight'], 2) ?> kg</td>
                            <td class="right fw-bold text-success"><?= formatCurrency((float)((float)$pur['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 2. Fabric Rolls & Inward Stocks -->
    <?php if (in_array('rolls', $selectedModules, true) && !empty($data['rolls'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">2. Fabric Rolls &amp; Inwards</h3>
                <span class="section-count"><?= count($data['rolls']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Roll #</th>
                        <th>Inward Date</th>
                        <th>Supplier</th>
                        <th>Fabric Type</th>
                        <th>Color</th>
                        <th class="right">Weight (kg)</th>
                        <th class="right">Cost / kg (₹)</th>
                        <th class="right">Total Cost (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['rolls'] as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['roll_number'] ?? '-') ?></strong></td>
                            <td><?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-' ?></td>
                            <td><?= htmlspecialchars(($r['supplier_company'] ?? '') ?: ($r['supplier_name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($r['fabric_type'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['color'] ?? '-') ?></td>
                            <td class="right"><?= number_format((float)($r['weight'] ?? 0), 2) ?></td>
                            <td class="right"><?= isset($r['cost_per_kg']) && (float)$r['cost_per_kg'] > 0 ? formatCurrency((float)((float)$r['cost_per_kg'])) . '/kg' : '-' ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($r['total_cost'] ?? 0))) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($r['status'] ?? 'IN_STOCK') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 3. Production Programs -->
    <?php if (in_array('programs', $selectedModules, true) && !empty($data['programs'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">3. Production Programs (Planned Targets)</h3>
                <span class="section-count"><?= count($data['programs']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Program #</th>
                        <th>Program Name</th>
                        <th>Style Code</th>
                        <th class="center">Target Pcs</th>
                        <th class="center">Completed Pcs</th>
                        <th class="center">Status</th>
                        <th>Target Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['programs'] as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['program_number']) ?></strong></td>
                            <td><?= htmlspecialchars($p['program_name']) ?></td>
                            <td><?= htmlspecialchars($p['style_code'] ?? '-') ?></td>
                            <td class="center fw-bold"><?= number_format((int)$p['target_pieces']) ?></td>
                            <td class="center"><?= number_format((int)($p['completed_quantity'] ?? 0)) ?></td>
                            <td class="center"><span class="badge badge-info"><?= htmlspecialchars($p['status']) ?></span></td>
                            <td><?= !empty($p['target_date']) ? date('d M Y', strtotime($p['target_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 4. Cutting Batches -->
    <?php if (in_array('cuttings', $selectedModules, true) && !empty($data['cuttings'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">4. Cutting Batches &amp; Fabric Output</h3>
                <span class="section-count"><?= count($data['cuttings']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Program #</th>
                        <th class="right">Roll Weight (kg)</th>
                        <th class="center">Planned Qty</th>
                        <th class="center">Cut Pieces</th>
                        <th class="right">Wastage (kg)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['cuttings'] as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['batch_number']) ?></strong></td>
                            <td><?= htmlspecialchars($c['program_number'] ?? '-') ?></td>
                            <td class="right"><?= number_format((float)($c['roll_weight'] ?? 0), 2) ?> kg</td>
                            <td class="center"><?= number_format((int)$c['planned_qty']) ?></td>
                            <td class="center fw-bold text-success"><?= number_format((int)$c['actual_pieces']) ?></td>
                            <td class="right"><?= number_format((float)($c['wastage_qty'] ?? 0), 2) ?> kg</td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($c['status']) ?></span></td>
                            <td><?= !empty($c['created_at']) ? date('d M Y', strtotime($c['created_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 5. Stitching Batches -->
    <?php if (in_array('stitching', $selectedModules, true) && !empty($data['stitching'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">5. Stitching Operations &amp; Assembly</h3>
                <span class="section-count"><?= count($data['stitching']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Program #</th>
                        <th class="center">Target Pcs</th>
                        <th class="center">Completed Pcs</th>
                        <th class="center">Rework Pcs</th>
                        <th class="center">Rejected Pcs</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['stitching'] as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['batch_number']) ?></strong></td>
                            <td><?= htmlspecialchars($s['program_number'] ?? '-') ?></td>
                            <td class="center"><?= number_format((int)$s['target_quantity']) ?></td>
                            <td class="center fw-bold text-success"><?= number_format((int)$s['completed_quantity']) ?></td>
                            <td class="center text-warning"><?= number_format((int)$s['rework_quantity']) ?></td>
                            <td class="center text-danger"><?= number_format((int)$s['rejected_qty']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($s['status']) ?></span></td>
                            <td><?= !empty($s['created_at']) ? date('d M Y', strtotime($s['created_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 6. Quality Control (QC) -->
    <?php if (in_array('qc', $selectedModules, true) && !empty($data['qc'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">6. Quality Control (QC) &amp; Yield</h3>
                <span class="section-count"><?= count($data['qc']) ?> batches</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>QC Batch #</th>
                        <th>Program #</th>
                        <th class="center">Inspected</th>
                        <th class="center">Passed</th>
                        <th class="center">Rework</th>
                        <th class="center">Rejected</th>
                        <th class="center">Yield %</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['qc'] as $q): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($q['batch_number']) ?></strong></td>
                            <td><?= htmlspecialchars($q['program_number'] ?? '-') ?></td>
                            <td class="center"><?= number_format((int)$q['inspected_quantity']) ?></td>
                            <td class="center fw-bold text-success"><?= number_format((int)$q['passed_quantity']) ?></td>
                            <td class="center text-warning"><?= number_format((int)$q['rework_quantity']) ?></td>
                            <td class="center text-danger"><?= number_format((int)$q['rejected_quantity']) ?></td>
                            <td class="center fw-bold"><?= $q['yield_percentage'] ?>%</td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($q['status']) ?></span></td>
                            <td><?= !empty($q['created_at']) ? date('d M Y', strtotime($q['created_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 7. Finished Goods Batches -->
    <?php if (in_array('batches', $selectedModules, true) && !empty($data['batches'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">7. Finished Goods Batches (Master Cartons)</h3>
                <span class="section-count"><?= count($data['batches']) ?> batches</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Program #</th>
                        <th>QC Ref</th>
                        <th>Destination Warehouse</th>
                        <th class="center">Total Pieces</th>
                        <th class="center">Master Cartons</th>
                        <th class="right">Cost/Pc (₹)</th>
                        <th class="right">Total Value (₹)</th>
                        <th>Status</th>
                        <th>Packed Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['batches'] as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['batch_number']) ?></strong></td>
                            <td><?= htmlspecialchars($b['program_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($b['qc_batch_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($b['destination_warehouse'] ?? '-') ?></td>
                            <td class="center fw-bold text-success"><?= number_format((int)$b['total_pieces']) ?></td>
                            <td class="center"><?= (int)$b['box_count'] ?> boxes</td>
                            <td class="right"><?= formatCurrency((float)((float)($b['cost_per_piece'] ?? 0))) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($b['total_cost'] ?? 0))) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($b['status']) ?></span></td>
                            <td><?= !empty($b['packed_date']) ? date('d M Y', strtotime($b['packed_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 8. Production Outward & Dispatches -->
    <?php if (in_array('outward', $selectedModules, true) && !empty($data['outward'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">8. Production Outward &amp; Warehouse Dispatches</h3>
                <span class="section-count"><?= count($data['outward']) ?> dispatches</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Outward #</th>
                        <th>Batch #</th>
                        <th>Warehouse</th>
                        <th class="center">Boxes</th>
                        <th class="center">Total Pieces</th>
                        <th>Transporter</th>
                        <th>LR / Docket #</th>
                        <th>Vehicle #</th>
                        <th>Status</th>
                        <th>Dispatch Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['outward'] as $ow): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ow['outward_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ow['batch_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ow['warehouse_name'] ?? '-') ?></td>
                            <td class="center"><?= (int)$ow['total_boxes'] ?></td>
                            <td class="center fw-bold text-primary"><?= number_format((int)$ow['total_pieces']) ?></td>
                            <td><?= htmlspecialchars($ow['transporter_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ow['lr_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ow['vehicle_number'] ?? '-') ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($ow['status']) ?></span></td>
                            <td><?= !empty($ow['dispatch_date']) ? date('d M Y', strtotime($ow['dispatch_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (in_array('stock_ledger', $selectedModules, true)): ?>
        <?php
            $ledgerByMonth = [];
            foreach (($data['stock_ledger'] ?? []) as $movement) {
                $monthKey = date('Y-m', strtotime($movement['transaction_date']));
                $ledgerByMonth[$monthKey][] = $movement;
            }
        ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Detailed Warehouse Stock Movement Ledger</h3>
                <span class="section-count"><?= count($data['stock_ledger'] ?? []) ?> movements</span>
            </div>
            <p>Each month is shown as a separate table. Positive movements add stock; negative movements remove stock. Balance is recorded after each movement.</p>
            <?php if (!$ledgerByMonth): ?>
                <p>No warehouse stock movements found for the selected period and warehouses.</p>
            <?php else: ?>
                <?php foreach ($ledgerByMonth as $monthKey => $movements): ?>
                    <h4><?= htmlspecialchars(date('F Y', strtotime($monthKey . '-01'))) ?></h4>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Warehouse</th>
                                <th>QR / Variant / SKU</th>
                                <th>Product / Material / Category</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Design / Pattern</th>
                                <th>HSN / GST</th>
                                <th>MRP</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Transaction</th>
                                <th>Stock In</th>
                                <th>Stock Out</th>
                                <th>Balance After</th>
                                <th>Complementary</th>
                                <th>Source / Destination</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d M Y H:i', strtotime($movement['transaction_date']))) ?></td>
                                    <td><?= htmlspecialchars($movement['warehouse_name'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($movement['qr_code'] ?? '-') ?><br>
                                        <small><?= htmlspecialchars($movement['variant_code'] ?? '-') ?> · <?= htmlspecialchars($movement['sku'] ?? '-') ?></small>
                                        <?php if (!empty($movement['series_start_code']) || !empty($movement['series_end_code'])): ?>
                                            <br><small>Series: <?= htmlspecialchars($movement['series_start_code'] ?? '-') ?>&ndash;<?= htmlspecialchars($movement['series_end_code'] ?? '-') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($movement['product_name'] ?? '-') ?><br>
                                        <small>
                                            <?= htmlspecialchars($movement['product_code'] ?? '-') ?> ·
                                            <?= htmlspecialchars($movement['material'] ?? '-') ?> ·
                                            <?= htmlspecialchars($movement['category_name'] ?? '-') ?> /
                                            <?= htmlspecialchars($movement['subcategory_name'] ?? '-') ?> /
                                            <?= htmlspecialchars($movement['sub_subcategory_name'] ?? '-') ?>
                                        </small>
                                        <?php if (!empty($movement['description'])): ?>
                                            <br><small><?= htmlspecialchars($movement['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($movement['size_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($movement['color_name'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($movement['design_name'] ?? '-') ?>
                                        <?= !empty($movement['design_code']) ? ' (' . htmlspecialchars($movement['design_code']) . ')' : '' ?>
                                    </td>
                                    <td><?= htmlspecialchars($movement['hsn_code'] ?? '-') ?> / <?= number_format((float)($movement['gst_rate'] ?? 0), 2) ?>%</td>
                                    <td class="right"><?= formatCurrency((float)((float)($movement['mrp'] ?? 0))) ?></td>
                                    <td class="right"><?= formatCurrency((float)((float)($movement['purchase_price'] ?? 0))) ?></td>
                                    <td class="right"><?= formatCurrency((float)((float)($movement['selling_price'] ?? 0))) ?></td>
                                    <td><?= htmlspecialchars($movement['transaction_type']) ?><br><small><?= htmlspecialchars($movement['stock_state']) ?></small></td>
                                    <td class="right text-success"><?= number_format((float)$movement['stock_in'], 2) ?></td>
                                    <td class="right text-danger"><?= number_format((float)$movement['stock_out'], 2) ?></td>
                                    <td class="right fw-bold"><?= number_format((float)$movement['balance_after'], 2) ?></td>
                                    <td><?= !empty($movement['is_complementary']) ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <?= htmlspecialchars($movement['source_type'] ?? '-') ?>
                                        <?= !empty($movement['source_id']) ? ' #' . (int)$movement['source_id'] : '' ?>
                                        &rarr;
                                        <?= htmlspecialchars($movement['dest_type'] ?? '-') ?>
                                        <?= !empty($movement['dest_id']) ? ' #' . (int)$movement['dest_id'] : '' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($movement['reference_type'] ?? '-') ?>
                                        <?= !empty($movement['reference_id']) ? ' #' . (int)$movement['reference_id'] : '' ?>
                                        <?php if (!empty($movement['notes'])): ?><br><small><?= htmlspecialchars($movement['notes']) ?></small><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 9. Production Billing -->
    <?php if (in_array('billing', $selectedModules, true) && !empty($data['billing'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">9. Factory Direct Billing &amp; Invoices</h3>
                <span class="section-count"><?= count($data['billing']) ?> bills</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Date</th>
                        <th>Client / Customer</th>
                        <th class="center">Sold Pcs</th>
                        <th class="right">Taxable (₹)</th>
                        <th class="right">GST (₹)</th>
                        <th class="right">Total (₹)</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['billing'] as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['bill_number']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($b['bill_date'])) ?></td>
                            <td><?= htmlspecialchars($b['client_name']) ?></td>
                            <td class="center"><?= (int)$b['total_pieces'] ?></td>
                            <td class="right"><?= formatCurrency((float)((float)$b['subtotal'])) ?></td>
                            <td class="right"><?= formatCurrency((float)((float)$b['tax_amount'])) ?></td>
                            <td class="right fw-bold text-success"><?= formatCurrency((float)((float)$b['total_amount'])) ?></td>
                            <td><?= htmlspecialchars($b['payment_method']) ?> (<?= htmlspecialchars($b['payment_status']) ?>)</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 10. Customer Returns -->
    <?php if (in_array('returns', $selectedModules, true) && !empty($data['returns'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">10. Customer Returns &amp; Credit Notes</h3>
                <span class="section-count"><?= count($data['returns']) ?> records</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Credit Note #</th>
                        <th>Original Bill #</th>
                        <th>Customer</th>
                        <th class="center">Returned Pcs</th>
                        <th>Disposition</th>
                        <th class="right">GST Reversal (₹)</th>
                        <th class="right">Net Refund (₹)</th>
                        <th>Refund Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['returns'] as $ret): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ret['return_number']) ?></strong></td>
                            <td><?= htmlspecialchars($ret['bill_number']) ?></td>
                            <td><?= htmlspecialchars($ret['customer_name']) ?></td>
                            <td class="center fw-bold text-danger"><?= (int)$ret['total_returned_qty'] ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($ret['disposition']) ?></span></td>
                            <td class="right"><?= formatCurrency((float)((float)$ret['gst_reversal_amount'])) ?></td>
                            <td class="right fw-bold text-danger"><?= formatCurrency((float)((float)$ret['net_amount'])) ?></td>
                            <td><?= htmlspecialchars($ret['refund_mode'] ?? 'CASH') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 11. Stock Requests -->
    <?php if (in_array('stock_requests', $selectedModules, true) && !empty($data['stock_requests'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">11. Warehouse Stock Requisitions</h3>
                <span class="section-count"><?= count($data['stock_requests']) ?> requests</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Warehouse</th>
                        <th>Priority</th>
                        <th>Required By</th>
                        <th class="center">Requested</th>
                        <th class="center">Fulfilled</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['stock_requests'] as $sr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sr['request_number']) ?></strong></td>
                            <td><?= htmlspecialchars($sr['warehouse_name']) ?></td>
                            <td><span class="badge badge-warning"><?= htmlspecialchars($sr['priority']) ?></span></td>
                            <td><?= !empty($sr['required_by_date']) ? date('d M Y', strtotime($sr['required_by_date'])) : '-' ?></td>
                            <td class="center"><?= number_format((int)$sr['total_requested_pieces']) ?></td>
                            <td class="center fw-bold text-success"><?= number_format((int)$sr['fulfilled_pieces']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($sr['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 12. Wastage Selling -->
    <?php if (in_array('wastage', $selectedModules, true) && !empty($data['wastage'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">12. Cutting &amp; Stitching Scrap / Wastage Selling</h3>
                <span class="section-count"><?= count($data['wastage']) ?> sales</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Wastage Type</th>
                        <th>Buyer Name</th>
                        <th class="right">Weight / Qty (kg)</th>
                        <th class="right">Rate/kg (₹)</th>
                        <th class="right">Total Inflow (₹)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['wastage'] as $w): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['invoice_number']) ?></strong></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($w['wastage_type']) ?></span></td>
                            <td><?= htmlspecialchars($w['buyer_name']) ?></td>
                            <td class="right"><?= number_format((float)($w['weight_kg'] ?? 0), 2) ?> kg</td>
                            <td class="right"><?= formatCurrency((float)((float)($w['rate_per_kg'] ?? 0))) ?></td>
                            <td class="right fw-bold text-success"><?= formatCurrency((float)((float)($w['total_amount'] ?? 0))) ?></td>
                            <td><?= !empty($w['created_at']) ? date('d M Y', strtotime($w['created_at'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- 13. Production Operating Expenses -->
    <?php if (in_array('expenses', $selectedModules, true) && !empty($data['expenses'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">13. Production Operating Expenses</h3>
                <span class="section-count"><?= count($data['expenses']) ?> items</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Expense Title</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th class="right">Total Cost (₹)</th>
                        <th class="right">Paid (₹)</th>
                        <th class="right">Pending (₹)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['expenses'] as $e): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($e['title'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($e['category'] ?? '-') ?></td>
                            <td><?= htmlspecialchars((string)($e['production_unit'] ?? '-')) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($e['total_cost'] ?? 0))) ?></td>
                            <td class="right text-success"><?= formatCurrency((float)((float)($e['paid_amount'] ?? 0))) ?></td>
                            <td class="right text-danger"><?= formatCurrency((float)((float)($e['pending_amount'] ?? 0))) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($e['payment_status'] ?? '-') ?></span></td>
                            <td><?= !empty($e['expense_date']) ? date('d M Y', strtotime($e['expense_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Raw Materials -->
    <?php if (in_array('raw_materials', $selectedModules, true) && !empty($data['raw_materials'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Raw Materials Supply &amp; Procurement</h3>
                <span class="section-count"><?= count($data['raw_materials']) ?> items</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Material Name</th>
                        <th>Color / Size</th>
                        <th class="right">Quantity</th>
                        <th class="right">Price (₹)</th>
                        <th class="right">Total (₹)</th>
                        <th>HSN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['raw_materials'] as $rm): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rm['purchase_number']) ?></strong></td>
                            <td><?= !empty($rm['purchase_date']) ? date('d M Y', strtotime($rm['purchase_date'])) : '-' ?></td>
                            <td><?= htmlspecialchars($rm['supplier_company'] ?: ($rm['supplier_name'] ?? '-')) ?></td>
                            <td><strong><?= htmlspecialchars($rm['item_name']) ?></strong></td>
                            <td><?= htmlspecialchars(trim(($rm['color'] ?? '') . ' ' . ($rm['size'] ?? ''))) ?: '-' ?></td>
                            <td class="right"><?= number_format((float)$rm['quantity'], 2) ?> <?= htmlspecialchars($rm['unit'] ?? '') ?></td>
                            <td class="right"><?= formatCurrency((float)((float)$rm['unit_price'])) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)$rm['total_price'])) ?></td>
                            <td><?= htmlspecialchars($rm['hsn_code'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Third Party Products -->
    <?php if (in_array('third_party_products', $selectedModules, true) && !empty($data['third_party_products'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Third-Party Finished Products &amp; Catalog Mapping</h3>
                <span class="section-count"><?= count($data['third_party_products']) ?> items</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Product / SKU</th>
                        <th>Color / Size</th>
                        <th class="right">Orig Bill Qty</th>
                        <th class="right">Extra Added Qty</th>
                        <th class="right">Final Qty</th>
                        <th class="right">Cost (₹)</th>
                        <th class="right">MRP / SP (₹)</th>
                        <th class="right">Total (₹)</th>
                        <th>Purchase Source</th>
                        <th>Catalog / Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['third_party_products'] as $tp):
                        $origQty = (float)($tp['original_purchased_qty'] ?? $tp['quantity']);
                        $extraQty = (float)($tp['extra_added_qty'] ?? 0);
                        $isExtraLine = !empty($tp['is_extra_purchase_item']);
                        $isStocked = !empty($tp['is_inwarded']) || (float)($tp['inwarded_qty'] ?? 0) > 0 || !empty($tp['qr_stock_added']);
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($tp['purchase_number']) ?></strong></td>
                            <td><?= !empty($tp['purchase_date']) ? date('d M Y', strtotime($tp['purchase_date'])) : '-' ?></td>
                            <td><?= htmlspecialchars($tp['supplier_company'] ?: ($tp['supplier_name'] ?? '-')) ?></td>
                            <td><strong><?= htmlspecialchars($tp['item_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($tp['vendor_sku'] ?: '-') ?></small></td>
                            <td><?= htmlspecialchars(trim(($tp['color'] ?? '') . ' ' . ($tp['size'] ?? ''))) ?: '-' ?></td>
                            <td class="right"><?= number_format($origQty) ?> <?= htmlspecialchars($tp['unit'] ?? '') ?></td>
                            <td class="right"><?= $extraQty > 0 ? '<strong style="color:#0284c7;">+' . number_format($extraQty) . ' ' . htmlspecialchars($tp['unit'] ?? '') . '</strong>' : '0' ?></td>
                            <td class="right fw-bold"><?= number_format((float)$tp['quantity']) ?> <?= htmlspecialchars($tp['unit'] ?? '') ?></td>
                            <td class="right"><?= formatCurrency((float)((float)$tp['unit_price'])) ?></td>
                            <td class="right"><?= formatCurrency((float)((float)$tp['mrp'])) ?> / <?= formatCurrency((float)((float)$tp['selling_price'])) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)$tp['total_price'])) ?></td>
                            <td>
                                <?php if ($isExtraLine): ?>
                                    <span class="badge badge-info">Added (Not in Original Bill)</span>
                                <?php elseif ($extraQty > 0): ?>
                                    <span class="badge badge-warning">Bill + Extra Qty</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Original Bill</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tp['is_mapped'])): ?>
                                    <span class="badge badge-success">Mapped</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                                <?php if ($isStocked): ?>
                                    <span class="badge badge-success">Added Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Not Stocked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Suppliers Directory -->
    <?php if (in_array('suppliers', $selectedModules, true) && !empty($data['suppliers'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Suppliers &amp; Mills Directory</h3>
                <span class="section-count"><?= count($data['suppliers']) ?> vendors</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>GSTIN</th>
                        <th>City / State</th>
                        <th class="center">Total POs</th>
                        <th class="right">Inward Value (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['suppliers'] as $sup): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sup['name']) ?></strong></td>
                            <td><?= htmlspecialchars($sup['company'] ?: '-') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($sup['seller_type'] ?? 'MILL') ?></span></td>
                            <td><?= htmlspecialchars($sup['phone'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($sup['gstin'] ?: ($sup['gst_number'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars(trim(($sup['city'] ?? '') . ' ' . ($sup['state'] ?? ''))) ?: '-' ?></td>
                            <td class="center"><?= (int)($sup['total_orders'] ?? 0) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($sup['total_order_value'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Goods Inward (GRN) -->
    <?php if (in_array('goods_inward', $selectedModules, true) && !empty($data['goods_inward'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Goods Inward Notes (GRN)</h3>
                <span class="section-count"><?= count($data['goods_inward']) ?> receipts</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>GRN #</th>
                        <th>Received Date</th>
                        <th>Invoice / Challan</th>
                        <th>Destination Warehouse</th>
                        <th class="right">Received Qty</th>
                        <th class="right">Accepted Qty</th>
                        <th class="right">Rejected Qty</th>
                        <th>Status</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['goods_inward'] as $grn): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($grn['grn_number']) ?></strong></td>
                            <td><?= !empty($grn['received_date']) ? date('d M Y', strtotime($grn['received_date'])) : '-' ?></td>
                            <td><?= htmlspecialchars($grn['invoice_number'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($grn['warehouse_name'] ?? 'Main Warehouse') ?></td>
                            <td class="right"><?= number_format((float)$grn['total_received_qty'], 2) ?></td>
                            <td class="right text-success"><?= number_format((float)$grn['total_accepted_qty'], 2) ?></td>
                            <td class="right text-danger"><?= number_format((float)$grn['total_rejected_qty'], 2) ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($grn['status'] ?? 'COMPLETED') ?></span></td>
                            <td><?= htmlspecialchars($grn['received_by_name'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Damages -->
    <?php if (in_array('damages', $selectedModules, true) && !empty($data['damages'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Damage &amp; Rejection Management</h3>
                <span class="section-count"><?= count($data['damages']) ?> logs</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Damage Report #</th>
                        <th>Type</th>
                        <th>Item / Roll #</th>
                        <th>Supplier</th>
                        <th class="right">Qty / Weight</th>
                        <th class="right">Loss Amount (₹)</th>
                        <th>Reason / Notes</th>
                        <th>Status</th>
                        <th>Resolution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['damages'] as $dm): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dm['damage_number']) ?></strong></td>
                            <td><span class="badge badge-warning"><?= htmlspecialchars($dm['item_type'] ?? 'ROLL') ?></span></td>
                            <td><?= htmlspecialchars($dm['product_name'] ?: ($dm['roll_number'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($dm['supplier_company'] ?: ($dm['supplier_name'] ?? '-')) ?></td>
                            <td class="right"><?= number_format((float)($dm['damaged_weight'] ?: $dm['damaged_quantity']), 2) ?></td>
                            <td class="right text-danger fw-bold"><?= formatCurrency((float)((float)($dm['loss_amount'] ?? 0))) ?></td>
                            <td><?= htmlspecialchars($dm['damage_reason'] ?: ($dm['defect_notes'] ?? '-')) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($dm['status'] ?? 'PENDING') ?></span></td>
                            <td><?= htmlspecialchars($dm['resolution_type'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Catalog Master -->
    <?php if (in_array('catalog', $selectedModules, true) && !empty($data['catalog'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Product Catalog &amp; Style Master</h3>
                <span class="section-count"><?= count($data['catalog']) ?> styles</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product / Style Name</th>
                        <th>Style Code</th>
                        <th>Source</th>
                        <th>Category Path</th>
                        <th>HSN</th>
                        <th class="center">Variants</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['catalog'] as $cat): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cat['product_name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($cat['product_code'] ?: '-') ?></code></td>
                            <td><?= htmlspecialchars($cat['source_type'] ?? 'MANUFACTURED') ?></td>
                            <td><?= htmlspecialchars(implode(' > ', array_filter([$cat['category_name'] ?? '', $cat['subcategory_name'] ?? '', $cat['sub_subcategory_name'] ?? '']))) ?: '-' ?></td>
                            <td><?= htmlspecialchars($cat['hsn_code'] ?: '-') ?></td>
                            <td class="center"><?= (int)($cat['variant_count'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($cat['is_active'])): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Product Variants & SKUs -->
    <?php if (in_array('variants', $selectedModules, true) && !empty($data['variants'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Product Variants &amp; SKUs</h3>
                <span class="section-count"><?= count($data['variants']) ?> variants</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Variant SKU</th>
                        <th>Product Variant Code</th>
                        <th>Product / Style Name</th>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Category Path</th>
                        <th class="right">MRP</th>
                        <th class="right">Selling Price</th>
                        <th class="right">Available Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['variants'] as $v): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($v['sku'] ?: ($v['variant_code'] ?: '-')) ?></code></td>
                            <td><code><?= htmlspecialchars($v['variant_code'] ?: '-') ?></code></td>
                            <td><strong><?= htmlspecialchars($v['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($v['size_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($v['color_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars(implode(' > ', array_filter([$v['category_name'] ?? '', $v['subcategory_name'] ?? '', $v['sub_subcategory_name'] ?? '']))) ?: '-' ?></td>
                            <td class="right"><?= formatCurrency((float)((float)($v['mrp'] ?? 0))) ?></td>
                            <td class="right"><?= formatCurrency((float)((float)($v['selling_price'] ?? 0))) ?></td>
                            <td class="right"><?= number_format((float)($v['available_quantity'] ?? 0), 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Finished Goods Inventory -->
    <?php if (in_array('inventory', $selectedModules, true) && !empty($data['inventory'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Finished Goods Inventory (On-Hand)</h3>
                <span class="section-count"><?= count($data['inventory']) ?> SKUs</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Size / Color</th>
                        <th>Location</th>
                        <th>Stock State</th>
                        <th class="right">On-Hand Qty</th>
                        <th class="right">Selling Price (₹)</th>
                        <th class="right">MRP (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['inventory'] as $inv): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inv['product_name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($inv['sku']) ?></code></td>
                            <td><?= htmlspecialchars(trim(($inv['size_name'] ?? '') . ' ' . ($inv['color_name'] ?? ''))) ?: '-' ?></td>
                            <td><?= htmlspecialchars($inv['location_name'] ?: 'Central Facility') ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($inv['stock_state'] ?? 'AVAILABLE') ?></span></td>
                            <td class="right fw-bold"><?= number_format((float)$inv['quantity'], 2) ?></td>
                            <td class="right"><?= formatCurrency((float)((float)($inv['selling_price'] ?? 0))) ?></td>
                            <td class="right"><?= formatCurrency((float)((float)($inv['mrp'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Fixed Assets -->
    <?php if (in_array('assets', $selectedModules, true) && !empty($data['assets'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Fixed Assets &amp; Machinery Register</h3>
                <span class="section-count"><?= count($data['assets']) ?> machinery</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Brand / Model</th>
                        <th>Purchase Date</th>
                        <th class="right">Cost (₹)</th>
                        <th class="right">Book Value (₹)</th>
                        <th>Condition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['assets'] as $ast): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ast['asset_code']) ?></strong></td>
                            <td><?= htmlspecialchars($ast['name']) ?></td>
                            <td><?= htmlspecialchars($ast['category_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars(trim(($ast['brand'] ?? '') . ' ' . ($ast['model_number'] ?? ''))) ?: '-' ?></td>
                            <td><?= !empty($ast['purchase_date']) ? date('d M Y', strtotime($ast['purchase_date'])) : '-' ?></td>
                            <td class="right"><?= formatCurrency((float)((float)($ast['purchase_cost'] ?? 0))) ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($ast['current_book_value'] ?? 0))) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($ast['condition_state'] ?? 'GOOD') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Asset Maintenance -->
    <?php if (in_array('asset_maintenance', $selectedModules, true) && !empty($data['asset_maintenance'])): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Asset Maintenance &amp; Service Logs</h3>
                <span class="section-count"><?= count($data['asset_maintenance']) ?> logs</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Log #</th>
                        <th>Asset</th>
                        <th>Service Type</th>
                        <th>Technician / Vendor</th>
                        <th>Service Date</th>
                        <th class="right">Cost (₹)</th>
                        <th class="right">Downtime (hrs)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['asset_maintenance'] as $am): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($am['log_number']) ?></strong></td>
                            <td><?= htmlspecialchars($am['asset_name']) ?> <small class="text-muted">(<?= htmlspecialchars($am['asset_code']) ?>)</small></td>
                            <td><?= htmlspecialchars($am['service_type'] ?? 'PREVENTIVE') ?></td>
                            <td><?= htmlspecialchars($am['technician_name'] ?: ($am['serviced_by_vendor'] ?? '-')) ?></td>
                            <td><?= !empty($am['service_date']) ? date('d M Y', strtotime($am['service_date'])) : '-' ?></td>
                            <td class="right fw-bold"><?= formatCurrency((float)((float)($am['service_cost'] ?? 0))) ?></td>
                            <td class="right"><?= number_format((float)($am['downtime_hours'] ?? 0), 1) ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($am['status'] ?? 'COMPLETED') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <footer style="text-align: center; border-top: 1px solid #cbd5e1; padding-top: 18px; margin-top: 30px; font-size: 11px; color: #64748b;">
        <?= htmlspecialchars($prodUnit['name'] ?? 'Production Facility') ?> &nbsp;·&nbsp; Confidential Production ERP &amp; Financial Intelligence Report &nbsp;·&nbsp; Generated on <?= date('d M Y, h:i A') ?> IST
    </footer>
</main>
</body>
</html>
