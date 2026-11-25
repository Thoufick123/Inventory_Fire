<?php
// Production Unit Customer Return & Credit Note Detail View with Quality Triage Station
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="bi bi-receipt-cutoff text-danger me-2"></i>Return #<?= e($return['return_number']) ?>
                </h4>
                <?php if ($return['status'] === 'PENDING_INSPECTION'): ?>
                    <span class="badge bg-warning text-dark border border-warning px-2 py-1 fs-6">
                        <i class="bi bi-hourglass-split me-1"></i> Awaiting Quality Triage (<?= (int)$pendingCount ?> pcs)
                    </span>
                <?php else: ?>
                    <span class="badge bg-success text-white px-2 py-1 fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i> <?= e($return['status']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <span class="text-muted small">Generated on <?= date('d M Y, h:i A', strtotime($return['created_at'])) ?></span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/returns') ?>" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Returns Log
            </a>
            <a href="<?= url('production/returns/print/' . $return['id']) ?>" target="_blank" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Credit Note
            </a>
        </div>
    </div>

    <!-- Summary Details Cards -->
    <div class="row g-3 mb-4">
        <!-- Return Overview -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Return Overview</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 170px;">Return / Voucher #:</td>
                            <td><strong class="font-monospace text-danger"><?= e($return['return_number']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Original Invoice #:</td>
                            <td>
                                <?php if ($return['bill_id']): ?>
                                    <a href="<?= url('production/billing/view/' . $return['bill_id']) ?>" class="font-monospace text-primary fw-semibold text-decoration-none">
                                        <?= e($return['bill_number']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="font-monospace"><?= e($return['bill_number'] ?? '-') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Customer / Entity:</td>
                            <td><strong><?= e($return['customer_name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Contact Phone:</td>
                            <td><?= e($return['customer_phone'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Return Flow:</td>
                            <td>
                                <?php if ($return['return_type'] === 'EXCHANGE'): ?>
                                    <span class="badge bg-primary text-white"><i class="bi bi-arrow-left-right me-1"></i> Flow 2: Return Product Options (Exchange)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white"><i class="bi bi-cash-coin me-1"></i> Flow 1: Fully Refunded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Triage Progress:</td>
                            <td>
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning border fw-bold"><?= $pendingCount ?> piece(s) in staging</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border fw-bold">All pieces triaged (<?= $restockedCount ?> Stock, <?= $damagedCount ?> Damage)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Financial &amp; GST Adjustment</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Total Returned Quantity:</span>
                        <strong class="text-dark"><?= (int)$return['total_returned_qty'] ?> pieces</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Taxable Base Reversal:</span>
                        <span class="fw-semibold"><?= formatCurrency((float)((float)$return['subtotal'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">GST Tax Reversal:</span>
                        <span class="fw-semibold text-info"><?= formatCurrency((float)((float)$return['gst_reversal_amount'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="fw-bold fs-6 text-dark">Net Credit / Refund Amount:</span>
                        <strong class="text-danger fs-5"><?= formatCurrency((float)((float)$return['net_amount'])) ?></strong>
                    </div>
                    <div class="small text-muted mt-2">
                        Settlement Mode: <strong class="text-dark"><?= e($return['refund_mode']) ?></strong>
                    </div>
                    <?php if (!empty($return['notes'])): ?>
                        <div class="mt-2 p-2 bg-light rounded text-muted small">
                            <strong>Notes / Instructions:</strong> <?= nl2br(e($return['notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- QUALITY INSPECTION & STOCK TRIAGE STATION -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clipboard2-check text-primary me-2"></i>Quality Inspection &amp; Stock Triage Station
                </h5>
                <span class="text-muted small">Decide per item: <strong>Add to Stock</strong> (resellable) or move to <strong>Damage Module</strong> (quarantined defect)</span>
            </div>
            <?php if ($pendingCount > 0): ?>
                <div class="d-flex gap-2">
                    <form method="POST" action="<?= url('production/returns/bulk-triage') ?>" onsubmit="return confirm('Add all <?= $pendingCount ?> pending pieces to available stock?');">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                        <input type="hidden" name="decision" value="ADD_TO_STOCK">
                        <button type="submit" class="btn btn-success btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-check-all me-1"></i> Add All Pending to Stock
                        </button>
                    </form>
                    <form method="POST" action="<?= url('production/returns/bulk-triage') ?>" onsubmit="return confirm('Route all <?= $pendingCount ?> pending pieces to the Damage Module?');">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                        <input type="hidden" name="decision" value="DAMAGE_MODULE">
                        <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold shadow-sm">
                            <i class="bi bi-shield-slash me-1"></i> Move All to Damage
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-body p-0">
            <?php if ($pendingCount > 0): ?>
                <div class="alert alert-warning border-0 rounded-0 m-0 py-2 px-3 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning me-2"></i>
                    <div>
                        <strong>Items in Staging:</strong> Stock has <strong>not</strong> been added automatically. Inspect each piece below and click <span class="badge bg-success">Add to Stock</span> if good, or <span class="badge bg-danger">Damage Module</span> if flawed.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success border-0 rounded-0 m-0 py-2 px-3 small d-flex align-items-center">
                    <i class="bi bi-check-circle-fill fs-5 text-success me-2"></i>
                    <div>
                        <strong>All Items Triaged:</strong> Every returned piece has been processed into active inventory or quarantined in Damage Management.
                    </div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Garment / Product Details</th>
                            <th>HSN</th>
                            <th class="text-center">Piece Tracking QR</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Refund Value</th>
                            <th>Return Reason</th>
                            <th class="text-center" style="width: 250px;">Triage Decision &amp; Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr>
                                <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($it['product_name']) ?></div>
                                    <div class="small text-muted font-monospace"><?= e($it['sku'] ?? '-') ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($it['hsn_code'] ?? '6109') ?></span></td>
                                <td class="text-center">
                                    <?php if (!empty($it['tag_code'])): ?>
                                        <span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($it['tag_code']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary font-monospace"><?= (int)$it['quantity'] ?> pcs (bulk)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= formatCurrency((float)((float)$it['unit_price'])) ?></td>
                                <td class="text-end fw-bold text-danger"><?= formatCurrency((float)((float)$it['total_amount'])) ?></td>
                                <td>
                                    <span class="text-dark small"><?= e($it['reason'] ?: 'Customer Return') ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($it['action_taken'] === 'PENDING_INSPECTION'): ?>
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- Option A: Add to Stock -->
                                            <form method="POST" action="<?= url('production/returns/triage-item') ?>" class="d-inline">
                                                <?= CSRF::field() ?>
                                                <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                                <input type="hidden" name="decision" value="ADD_TO_STOCK">
                                                <button type="submit" class="btn btn-sm btn-success fw-semibold shadow-sm" title="Verify intact and credit back to available inventory">
                                                    <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                                </button>
                                            </form>

                                            <!-- Option B: Damage Module -->
                                            <button type="button" class="btn btn-sm btn-danger fw-semibold shadow-sm" 
                                                    onclick="openDamageModal(<?= $it['id'] ?>, '<?= e($it['product_name']) ?>', '<?= e($it['tag_code'] ?? '') ?>', '<?= (float)$it['total_amount'] ?>')" 
                                                    title="Mark as damaged and route to Damage Module">
                                                <i class="bi bi-shield-slash me-1"></i> Damage Module
                                            </button>
                                        </div>
                                    <?php elseif ($it['action_taken'] === 'RESTOCKED'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Added to Stock
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-box-seam me-1"></i> In Available Stock
                                        </div>
                                    <?php elseif ($it['action_taken'] === 'DAMAGED'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger fw-bold px-3 py-2">
                                            <i class="bi bi-shield-x me-1"></i> Damage Module
                                        </span>
                                        <?php if (!empty($it['damage_id'])): ?>
                                            <a href="<?= url('production/damages/view/' . $it['damage_id']) ?>" class="small text-danger d-block mt-1 fw-semibold text-decoration-none">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Damage Case #<?= $it['damage_id'] ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= e($it['action_taken']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DAMAGE MODULE ROUTING MODAL -->
<div class="modal fade" id="damageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('production/returns/triage-item') ?>" class="modal-content border-0 shadow">
            <?= CSRF::field() ?>
            <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
            <input type="hidden" name="item_id" id="modalItemId" value="">
            <input type="hidden" name="decision" value="DAMAGE_MODULE">

            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shield-slash me-2"></i>Move Garment to Damage Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3">
                    <div class="small text-muted">Garment to Quarantine:</div>
                    <strong class="text-dark fs-6" id="modalProductName"></strong>
                    <div class="small text-muted font-monospace mt-1" id="modalTagCode"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Defect Reason / Category *</label>
                    <select name="damage_reason" class="form-select fw-semibold" required>
                        <option value="Fabric Tear / Hole">Fabric Tear / Hole / Weaving Defect</option>
                        <option value="Stitching Defective">Stitching Defective / Open Seams</option>
                        <option value="Stain / Oil Mark">Stain / Oil / Dirt Spot</option>
                        <option value="Color Bleed / Shading">Color Bleed / Shading Issue</option>
                        <option value="Broken Trim / Button">Button / Zipper / Elastic Flaw</option>
                        <option value="Customer Damaged / Worn">Customer Damaged / Worn Out</option>
                        <option value="Factory Scrap / Reject">Factory Scrap / Unsellable Reject</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Defect Notes / Inspection Observations</label>
                    <textarea name="defect_notes" class="form-control" rows="3" placeholder="Describe the defect location, severity, or handling instructions..."></textarea>
                </div>

                <div class="alert alert-warning border-0 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> This item will be quarantined from active stock and a case will be opened in the Damage Management module.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-semibold px-4">
                    <i class="bi bi-shield-x me-1"></i> Confirm &amp; Send to Damage
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let damageModalObj = null;

function openDamageModal(itemId, productName, tagCode, totalAmount) {
    document.getElementById('modalItemId').value = itemId;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalTagCode').textContent = tagCode ? ('Piece Tag: ' + tagCode) : '';

    if (!damageModalObj) {
        damageModalObj = new bootstrap.Modal(document.getElementById('damageModal'));
    }
    damageModalObj.show();
}
</script>
