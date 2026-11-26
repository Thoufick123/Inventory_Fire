<div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Generate Piece QR Codes</h2>
        <span class="text-muted">Stitching Batch <strong><?= e($batch['batch_number'] ?: ('#' . $batch['id'])) ?></strong> &mdash; <?= e($batch['name'] ?: $batch['program_number']) ?></span>
    </div>
    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Stitching
    </a>
</div>

<!-- Stitching Workflow Stepper -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small fw-semibold">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>1. Stitching Completed
            </span>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <span class="badge bg-primary text-white px-3 py-2 shadow-sm">
                <i class="bi bi-qr-code-scan me-1"></i>2. Generate Piece QR Codes
            </span>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <span class="badge bg-light text-muted border px-3 py-2">
                <i class="bi bi-printer me-1"></i>3. Print Garment Labels
            </span>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <span class="badge bg-light text-muted border px-3 py-2">
                <i class="bi bi-patch-check me-1"></i>4. Quality Check (QC)
            </span>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: QR Generation Settings -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-sliders me-2 text-primary"></i>QR Label Configuration</h5>
                <?= function_exists('renderConcernBadge') ? renderConcernBadge($batch) : '' ?>
            </div>
            <form method="POST" action="<?= url('production/stitching/qr/' . (int)$batch['id'] . '/generate') ?>" onsubmit="var btn=this.querySelector('button[type=submit]'); if(btn.disabled) return false; btn.disabled=true; btn.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Generating...';">
                <?= CSRF::field() ?>
                <div class="card-body p-4">
                    <div class="mb-3 p-3 rounded bg-light border">
                        <div class="small text-muted mb-1">Stitching Batch Summary</div>
                        <div class="fw-bold text-dark fs-6"><?= e($batch['name'] ?: $batch['batch_number']) ?></div>
                        <div class="small text-muted mt-1">
                            Program: <strong class="text-dark"><?= e($batch['program_number'] ?: '—') ?></strong>
                            &bull; Product: <strong class="text-dark"><?= e($batch['product_name'] ?: ($batch['program_name'] ?: 'Garment')) ?></strong>
                        </div>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span class="badge bg-success-subtle text-success border">
                                <i class="bi bi-check2-circle me-1"></i><?= number_format((int)($batch['stitched_qty'] ?: $batch['received_qty'])) ?> Stitched Pieces
                            </span>
                            <span class="badge bg-info-subtle text-info border">
                                <i class="bi bi-speedometer2 me-1"></i><?= number_format((float)($batch['total_weight'] ?? 0), 2) ?> kg
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="brandNameInput">Label Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" id="brandNameInput" class="form-control fw-semibold" value="<?= e($defaultBrand) ?>" required>
                        <div class="form-text">Printed at the top of every garment QR sticker.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="qrPrefixInput">QR Code Prefix <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="text" name="qr_prefix" id="qrPrefixInput" class="form-control font-monospace text-uppercase" placeholder="Leave blank to use Variant ID (e.g. V0001)">
                        <div class="form-text">When blank, each piece receives its Variant-ID serial code (e.g. <code>V0001-0001</code>) for unified QC and POS tracking.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="qrNotesInput">Notes <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="notes" id="qrNotesInput" class="form-control" rows="2" placeholder="Optional QR batch remarks..."><?= e($batch['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-qr-code-scan me-1"></i> Generate Piece QRs &amp; Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Variant Piece Breakdown Preview -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-tags me-2 text-primary"></i>Stitched Variant Breakdown</h5>
                <span class="badge bg-primary-subtle text-primary border"><?= count($items ?? []) ?> Variant Line(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Catalog Hierarchy</th>
                                <th>Size &amp; Color</th>
                                <th class="text-center">QR Pieces</th>
                                <th class="text-end">MRP (₹)</th>
                                <th class="text-end pe-3">Discount Price (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Single-lot stitched quantity (<?= number_format((int)($batch['stitched_qty'] ?: $batch['received_qty'])) ?> pcs) will be generated.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $idx => $it): ?>
                                    <?php
                                        $qty = (int)($it['stitched_qty'] ?: ($it['stitched_quantity'] ?: ($it['received_qty'] ?? 0)));
                                        $sizeLabel = trim((string)($it['display_size'] ?? ($it['size_name'] ?? 'Standard'))) ?: 'Standard';
                                        $colorLabel = trim((string)($it['display_color'] ?? ($it['color_name'] ?? 'Assorted'))) ?: 'Assorted';
                                    ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <span class="small fw-semibold text-dark"><?= e($it['catalog_hierarchy'] ?: ($batch['category_name'] ?: 'Apparel')) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark border"><?= e($sizeLabel) ?></span>
                                            <span class="badge bg-light text-dark border"><?= e($colorLabel) ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= number_format($qty) ?> pcs</td>
                                        <td class="text-end font-monospace"><?= number_format((float)($it['base_price'] ?? 0), 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-success pe-3"><?= number_format((float)($it['discount_price'] ?? 0), 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
