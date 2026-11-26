<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-tag-fill me-2 text-primary"></i>Product Variants Details</h2>
            <span class="badge bg-dark font-monospace fs-6 px-3 py-1"><?= e($variant['variant_code']) ?></span>
            <?php if ((int)$variant['is_active'] === 1): ?>
            <span class="badge bg-success">Active</span>
            <?php else: ?>
            <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0"><?= e($variant['product_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Product Variants
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Attributes & Master Data -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Hierarchy &amp; Attributes</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted fw-semibold ps-4" style="width: 200px;">Variant ID:</td>
                            <td><span class="badge bg-dark-subtle text-dark font-monospace fs-6 fw-bold"><?= e($variant['variant_code']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Parent Catalog:</td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($variant['product_name']) ?></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Category:</td>
                            <td><?= e($variant['category_name'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Subcategory:</td>
                            <td><?= e($variant['subcategory_name'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Color:</td>
                            <td>
                                <?php if (!empty($variant['color_name'])): ?>
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                    <?php if (!empty($variant['color_hex'])): ?>
                                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= e($variant['color_hex']) ?>;border:1px solid #ccc;"></span>
                                    <?php endif; ?>
                                    <?= e($variant['color_name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Design / Pattern:</td>
                            <td>
                                <?php if (!empty($variant['design_name'])): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                                    <i class="bi bi-palette me-1"></i><?= e($variant['design_name']) ?>
                                    <?php if (!empty($variant['design_code'])): ?>
                                    (<?= e($variant['design_code']) ?>)
                                    <?php endif; ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Size:</td>
                            <td>
                                <?php if (!empty($variant['size_name'])): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold fs-6">
                                    <?= e($variant['size_name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Selling Price:</td>
                            <td><strong class="text-success fs-5"><?= formatCurrency((float)((float)$variant['selling_price'])) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">MRP:</td>
                            <td><?= formatCurrency((float)((float)$variant['mrp'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">Production Cost:</td>
                            <td><?= formatCurrency((float)((float)$variant['production_cost'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold ps-4">HSN &amp; GST:</td>
                            <td><?= e($variant['hsn_code'] ?? '6109') ?> (<?= (float)$variant['gst_rate'] ?>%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock in Production Units -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold text-dark"><i class="bi bi-building me-2 text-primary"></i>Stock at Production Facilities</h5>
                <span class="badge bg-success px-3 py-2 fs-6">
                    Total: <?= number_format($stock['available_qty']) ?> pcs
                </span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Production Unit / Facility</th>
                            <th class="text-end pe-4">Available Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock['breakdown'])): ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">No physical units currently in stock at any production facility.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($stock['breakdown'] as $b): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-dark"><?= e($b['unit_name']) ?></td>
                            <td class="text-end pe-4 fw-bold text-success fs-6"><?= number_format($b['quantity']) ?> pcs</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Scannable QR Identity Card -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 text-center p-4">
            <h5 class="fw-bold text-dark mb-1">Permanent Scannable QR Identity</h5>
            <p class="text-muted small">Every physical unit of this variant produced across any batch bears this exact scannable QR identifier.</p>

            <div class="d-flex justify-content-center my-3">
                <div id="viewQrCanvasBox" class="p-3 bg-white border rounded shadow-sm"></div>
            </div>

            <div class="font-monospace fw-bold fs-5 text-dark mb-2"><?= e($variant['qr_code'] ?: $variant['variant_code']) ?></div>

            <div class="d-flex justify-content-center gap-2 mb-4">
                <a href="<?= url('production/variants/qr-label/' . $variant['id']) ?>" target="_blank" class="btn btn-outline-dark">
                    <i class="bi bi-printer me-1"></i> Print Single Label
                </a>
                <button type="button" class="btn btn-primary" onclick="openBatchPrintModal(<?= (int)$variant['id'] ?>, '<?= e($variant['variant_code']) ?>')">
                    <i class="bi bi-grid-3x2-gap me-1"></i> Print Multi-unit Batch
                </button>
            </div>

            <div class="card bg-light border-0 text-start p-3 small text-muted">
                <div class="fw-bold text-dark mb-1"><i class="bi bi-qr-code-scan me-1 text-primary"></i>Billing &amp; Production Integration:</div>
                When this QR is scanned at the Production Billing desk or POS, it immediately resolves to <strong><?= e($variant['product_name']) ?> (Size <?= e($variant['size_name'] ?? 'STD') ?>)</strong>, verifies stock at the facility, and increments the quantity.
            </div>
        </div>
    </div>
</div>

<!-- Modal: Batch Print QR Labels -->
<div class="modal fade" id="modalBatchPrint" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('production/variants/print-labels') ?>" target="_blank">
                <?= csrf_field() ?>
                <input type="hidden" name="variant_id" id="batchPrintVariantId" value="<?= (int)$variant['id'] ?>">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fs-6"><i class="bi bi-printer-fill me-2"></i>Batch Print QR Labels</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Target Variant</label>
                        <div class="form-control bg-light font-monospace fw-bold"><?= e($variant['variant_code']) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Identical QR Labels to Print <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control form-control-lg fw-bold" min="1" max="500" value="50" required>
                        <div class="form-text small">
                            If 50 units were manufactured, print 50 identical labels to affix onto each garment tag.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-printer-fill me-1"></i> Generate Labels Sheet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrBox = document.getElementById('viewQrCanvasBox');
    new QRCode(qrBox, {
        text: <?= json_encode((string)($variant['qr_code'] ?: $variant['variant_code'])) ?>,
        width: 160,
        height: 160,
        correctLevel: QRCode.CorrectLevel.M
    });
});

function openBatchPrintModal(id, code) {
    new bootstrap.Modal(document.getElementById('modalBatchPrint')).show();
}
</script>
