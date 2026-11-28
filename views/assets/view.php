<?php
// Asset Detail Passport View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-cpu text-primary me-2"></i><?= e($asset['name']) ?>
                <span class="badge bg-primary-subtle text-primary border font-monospace ms-2"><?= e($asset['asset_code']) ?></span>
            </h4>
            <p class="text-muted small mb-0"><i class="bi <?= e($asset['category_icon'] ?: 'bi-box-seam') ?> me-1"></i><?= e($asset['category_name']) ?> · Location: <?= e($asset['location_type']) ?> (<?= e($asset['floor_area'] ?: 'Floor #1') ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('assets') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Register
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Passport Specs -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Technical Passport &amp; Machine Specifications</h6>
                    <div class="d-flex gap-2">
                        <?php
                        $statusBadge = match ($asset['status']) {
                            'IN_USE'         => 'bg-success text-white',
                            'IN_MAINTENANCE' => 'bg-warning text-dark',
                            'IDLE'           => 'bg-secondary text-white',
                            'DAMAGED'        => 'bg-danger text-white',
                            default          => 'bg-dark text-white',
                        };
                        ?>
                        <span class="badge <?= $statusBadge ?> px-3 py-2"><?= e($asset['status']) ?></span>
                        <span class="badge bg-light text-dark border px-2 py-2">Condition: <strong><?= e($asset['condition_state']) ?></strong></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Brand / Manufacturer</span>
                            <strong><?= e($asset['brand'] ?: '—') ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Model Number</span>
                            <strong><?= e($asset['model_number'] ?: '—') ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Serial Number</span>
                            <strong class="font-monospace"><?= e($asset['serial_number'] ?: '—') ?></strong>
                        </div>

                        <div class="col-md-4">
                            <span class="text-muted small d-block">Location Unit</span>
                            <strong><?= e($asset['location_type']) ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Floor / Line</span>
                            <strong><?= e($asset['floor_area'] ?: 'Floor #1') ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Custodian / In-Charge</span>
                            <strong><?= e($asset['custodian_name'] ?: 'Production Supervisor') ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($asset['specifications'])): ?>
                    <div class="mt-4 pt-3 border-top">
                        <span class="text-muted small d-block fw-semibold">Technical Specifications</span>
                        <div class="p-3 bg-light rounded-3 mt-1 small text-dark">
                            <?= nl2br(e($asset['specifications'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3 mt-2 pt-3 border-top">
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Purchase Date</span>
                            <strong><?= $asset['purchase_date'] ? date('d M Y', strtotime($asset['purchase_date'])) : '—' ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Purchase Cost</span>
                            <h5 class="fw-bold mb-0"><?= formatCurrency((float)($asset['purchase_cost'])) ?></h5>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Current Book Value</span>
                            <h5 class="fw-bold text-primary mb-0"><?= formatCurrency((float)($asset['current_book_value'])) ?></h5>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Warranty Expiry</span>
                            <strong class="<?= ($asset['warranty_expiry'] && strtotime($asset['warranty_expiry']) < time()) ? 'text-danger' : 'text-success' ?>">
                                <?= $asset['warranty_expiry'] ? date('d M Y', strtotime($asset['warranty_expiry'])) : 'None / Expired' ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance History Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-primary me-2"></i>Maintenance &amp; Service History (<?= count($maintenanceLogs) ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Log #</th>
                                    <th>Service Date</th>
                                    <th>Service Type</th>
                                    <th>Technician / Vendor</th>
                                    <th>Service Cost</th>
                                    <th>Downtime</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($maintenanceLogs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-circle fs-3 d-block text-success mb-1"></i>
                                        No maintenance or breakdown repairs recorded for this machine.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($maintenanceLogs as $log): ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary">
                                        <a href="<?= url('assets/maintenance/view/' . $log['id']) ?>" class="text-decoration-none">
                                            <?= e($log['log_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= date('d M Y', strtotime($log['service_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($log['service_type']) ?></span></td>
                                    <td><?= e($log['technician_name'] ?: ($log['serviced_by_vendor'] ?: 'In-House')) ?></td>
                                    <td class="fw-bold"><?= formatCurrency((float)($log['service_cost'])) ?></td>
                                    <td><?= $log['downtime_hours'] ?> hrs</td>
                                    <td>
                                        <span class="badge <?= $log['status'] === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= e($log['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= url('assets/maintenance/view/' . $log['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
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

        <!-- Scannable QR & Quick Identity -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 text-center">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-qr-code text-primary me-2"></i>Scannable Hardware Tag</h6>
                </div>
                <div class="card-body p-4">
                    <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                    <div class="font-monospace fw-bold fs-5 text-dark mb-1"><?= e($asset['barcode_qr']) ?></div>
                    <span class="badge bg-secondary-subtle text-secondary small font-monospace"><?= e($asset['asset_code']) ?></span>

                    <div class="mt-4 pt-3 border-top">
                        <a href="<?= url('assets/print/' . $asset['id']) ?>" target="_blank" class="btn btn-dark w-100">
                            <i class="bi bi-printer me-1"></i> Print Equipment Sticker
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building text-primary me-2"></i>Procurement &amp; Vendor</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <span class="text-muted d-block">Supplier / Vendor:</span>
                        <strong><?= e($asset['vendor_name'] ?: '—') ?></strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Contact Phone:</span>
                        <strong><?= e($asset['vendor_contact'] ?: '—') ?></strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Invoice / Bill #:</span>
                        <strong class="font-monospace"><?= e($asset['invoice_number'] ?: '—') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('assets/js/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= addslashes($asset['barcode_qr']) ?>",
        width: 160,
        height: 160,
        colorDark : "#111827",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
});
</script>
