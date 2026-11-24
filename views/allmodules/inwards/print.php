<?php
$concernBrand = function_exists('getConcernBranding') ? getConcernBranding($inward['concern_id'] ?? null) : [];
$grnLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : '';
$grnFirmName = !empty($concernBrand['name']) ? $concernBrand['name'] : 'GRM APPARELS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receipt Note - <?= e($inward['grn_number']) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/bootstrap.min.css') ?>">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #fff; color: #212529; padding: 20px; font-size: 13px; }
        .grn-header { border-bottom: 2px solid #212529; padding-bottom: 15px; margin-bottom: 20px; }
        .table-custom th { background-color: #f8f9fa !important; border-bottom: 2px solid #dee2e6; font-size: 11px; text-transform: uppercase; }
        .signature-box { border-top: 1px dashed #6c757d; width: 180px; text-align: center; padding-top: 5px; margin-top: 50px; font-size: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container my-3">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print GRN</button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Close</button>
        </div>

        <div class="grn-header d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($grnLogo)): ?>
                    <img src="<?= htmlspecialchars($grnLogo) ?>" alt="Logo" style="max-height: 50px; max-width: 150px; object-fit: contain;">
                <?php endif; ?>
                <div>
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($grnFirmName) ?></h3>
                    <p class="text-muted mb-0 small"><?= htmlspecialchars($concernBrand['legal_name'] ?? 'Garment Manufacturing, Processing & Distribution') ?></p>
                    <p class="text-muted mb-0 small">Receiving Hub: <?= e($inward['warehouse_name'] ?: 'Central Warehouse') ?></p>
                </div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-primary mb-1">GOODS RECEIPT NOTE (GRN)</h4>
                <div class="fw-bold fs-6 font-monospace">GRN #: <?= e($inward['grn_number']) ?></div>
                <div class="text-muted small">Date: <?= date('d M Y', strtotime($inward['received_date'])) ?></div>
                <div class="badge bg-dark mt-1"><?= e($inward['status']) ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width: 140px;">PO Reference:</td><td class="fw-bold font-monospace"><?= e($inward['po_number'] ?: 'Direct Consignment') ?></td></tr>
                    <tr><td class="text-muted fw-semibold">Supplier Invoice:</td><td><?= e($inward['invoice_number'] ?: '—') ?> <?= $inward['invoice_date'] ? '('.date('d-m-Y', strtotime($inward['invoice_date'])).')' : '' ?></td></tr>
                    <tr><td class="text-muted fw-semibold">Gate Pass No:</td><td><?= e($inward['gate_pass_number'] ?: '—') ?></td></tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="width: 140px;">Vehicle Number:</td><td class="fw-bold"><?= e($inward['vehicle_number'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted fw-semibold">Driver Name:</td><td><?= e($inward['driver_name'] ?: '—') ?> <?= $inward['driver_contact'] ? '('.e($inward['driver_contact']).')' : '' ?></td></tr>
                    <tr><td class="text-muted fw-semibold">Received By:</td><td><?= e($inward['received_by_name'] ?: 'Warehouse Staff') ?></td></tr>
                </table>
            </div>
        </div>

        <table class="table table-bordered table-custom align-middle mb-4">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Product Description / Style</th>
                    <th>Variant Code / SKU</th>
                    <th>Attributes</th>
                    <th class="text-center" style="width: 10%;">Ordered</th>
                    <th class="text-center" style="width: 10%;">Received</th>
                    <th class="text-center" style="width: 10%;">Damaged</th>
                    <th class="text-center" style="width: 12%;">Accepted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-bold"><?= e($item['product_name']) ?></div>
                        <small class="text-muted"><?= e($item['parent_sku']) ?></small>
                    </td>
                    <td class="font-monospace small">
                        <?= !empty($item['variant_code']) ? '['.e($item['variant_code']).'] ' : '' ?><?= e($item['variant_sku']) ?>
                    </td>
                    <td><?= e($item['color_name']) ?> / <?= e($item['design_name'] ?: 'Plain') ?> · Size: <?= e($item['size_name']) ?></td>
                    <td class="text-center"><?= number_format($item['ordered_qty']) ?></td>
                    <td class="text-center fw-bold"><?= number_format($item['received_qty']) ?></td>
                    <td class="text-center text-danger"><?= number_format($item['damaged_qty']) ?></td>
                    <td class="text-center fw-bold text-success"><?= number_format($item['accepted_qty']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="4" class="text-end">TOTALS:</td>
                    <td class="text-center"><?= number_format(array_sum(array_column($items, 'ordered_qty'))) ?></td>
                    <td class="text-center"><?= number_format($inward['total_received_qty']) ?></td>
                    <td class="text-center text-danger"><?= number_format($inward['total_rejected_qty']) ?></td>
                    <td class="text-center text-success fs-6"><?= number_format($inward['total_accepted_qty']) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($inward['notes'])): ?>
        <div class="mb-4 p-2 bg-light border rounded small">
            <strong>Remarks / Verification Notes:</strong> <?= nl2br(e($inward['notes'])) ?>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-end mt-5 pt-4">
            <div class="signature-box">
                Driver Signature / Delivery Agent
            </div>
            <div class="signature-box">
                Inspected &amp; Verified By
            </div>
            <div class="signature-box">
                Store In-Charge / Authorized Signatory
            </div>
        </div>
    </div>
</body>
</html>
