<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Product Variants</h2>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('catalog', 'create')): ?>
        <a href="<?= url('production/variants/create') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Create Variant
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalQuickAddDesign">
            <i class="bi bi-palette-fill me-1"></i> Add Design / Pattern
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-primary border-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Variants</div>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= number_format($stats['total_variants']) ?></h3>
                    </div>
                    <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                        <i class="bi bi-tags-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-success border-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Active Variants</div>
                        <h3 class="mb-0 fw-bold text-success mt-1"><?= number_format($stats['active_variants']) ?></h3>
                    </div>
                    <div class="rounded-circle bg-success-subtle p-3 text-success">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-info border-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Parent Catalogs</div>
                        <h3 class="mb-0 fw-bold text-info mt-1"><?= number_format($stats['unique_catalogs']) ?></h3>
                    </div>
                    <div class="rounded-circle bg-info-subtle p-3 text-info">
                        <i class="bi bi-journal-bookmark-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Unit Available Stock</div>
                        <h3 class="mb-0 fw-bold text-warning mt-1"><?= number_format($stats['total_stock']) ?> <small class="fs-6 text-muted">pcs</small></h3>
                    </div>
                    <div class="rounded-circle bg-warning-subtle p-3 text-warning">
                        <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Multi-Attribute Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/variants') ?>" id="variantsFilterForm" class="row g-2 align-items-end">
            <!-- Search -->
            <div class="col-lg-3 col-md-6">
                <label class="form-label small text-muted fw-semibold mb-1">Search Variant / SKU / QR</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by SKU, V-ID, Style, QR..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Category -->
            <div class="col-lg-2 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Category</label>
                <select name="category_id" id="filterCategory" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Parent Catalog -->
            <div class="col-lg-2 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Parent Catalog</label>
                <select name="product_id" id="filterCatalog" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Catalogs --</option>
                    <?php foreach ($catalogs as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filters['product_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['code'] . ' - ' . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Color -->
            <div class="col-lg-1 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Color</label>
                <select name="color_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($colors as $col): ?>
                    <option value="<?= $col['id'] ?>" <?= ($filters['color_id'] ?? '') == $col['id'] ? 'selected' : '' ?>><?= e($col['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Design -->
            <div class="col-lg-1 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Design</label>
                <select name="design_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($designs as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($filters['design_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Size -->
            <div class="col-lg-1 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Size</label>
                <select name="size_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($sizes as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filters['size_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-lg-1 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Status</label>
                <select name="is_active" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="1" <?= ($filters['is_active'] ?? '') === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($filters['is_active'] ?? '') === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <!-- QR Generation Status -->
            <div class="col-lg-2 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">QR Status</label>
                <select name="qr_status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Variants</option>
                    <option value="with_qr" <?= ($filters['qr_status'] ?? '') === 'with_qr' ? 'selected' : '' ?>>QR Generated</option>
                    <option value="no_qr" <?= ($filters['qr_status'] ?? '') === 'no_qr' ? 'selected' : '' ?>>Pending QR</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-lg-1 col-md-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Apply Filters"><i class="bi bi-filter"></i></button>
                <a href="<?= url('production/variants') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Variants Data Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="bi bi-list-columns me-2 text-primary"></i>Variants Roster
        </h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold">
            <?= count($variants) ?> Variants Listed
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 100px;">Variant ID</th>
                        <th>Parent Catalog / SKU</th>
                        <th>Attributes (Color · Design · Size)</th>
                        <th>Variant SKU</th>
                        <th class="text-center">Selling Price</th>
                        <th class="text-center">Unit Stock</th>
                        <th class="text-center">QR Identity</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($variants)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No variants found matching your criteria.
                            <?php if (hasPermission('catalog', 'create')): ?>
                            <div class="mt-3">
                                <a href="<?= url('production/variants/create') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Create First Variant
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($variants as $v): ?>
                    <tr>
                        <!-- Variant ID -->
                        <td class="ps-3">
                            <span class="badge bg-dark-subtle text-dark border font-monospace px-2 py-1 fs-6 fw-bold">
                                <?= e($v['variant_code'] ?? ('V' . str_pad((string)$v['id'], 4, '0', STR_PAD_LEFT))) ?>
                            </span>
                        </td>

                        <!-- Parent Catalog -->
                        <td>
                            <div class="fw-bold text-dark"><?= e($v['product_name']) ?></div>
                            <div class="small font-monospace text-primary fw-semibold">
                                <?= e($v['parent_sku']) ?>
                                <?php if (!empty($v['category_name'])): ?>
                                <span class="badge bg-secondary-subtle text-secondary ms-1 fw-normal"><?= e($v['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Attributes -->
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <!-- Color -->
                                <?php if (!empty($v['color_name'])): ?>
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                    <?php if (!empty($v['color_hex'])): ?>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($v['color_hex']) ?>;border:1px solid #ccc;"></span>
                                    <?php endif; ?>
                                    <?= e($v['color_name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>

                                <!-- Design -->
                                <?php if (!empty($v['design_name'])): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="bi bi-palette me-1"></i><?= e($v['design_name']) ?>
                                </span>
                                <?php endif; ?>

                                <!-- Size -->
                                <?php if (!empty($v['size_name'])): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                                    <?= e($v['size_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Variant SKU -->
                        <td>
                            <span class="font-monospace fw-bold text-dark"><?= e($v['sku']) ?></span>
                        </td>

                        <!-- Pricing -->
                        <td class="text-center">
                            <div class="fw-bold text-dark">₹<?= number_format((float)$v['selling_price'], 2) ?></div>
                            <?php if ((float)$v['mrp'] > (float)$v['selling_price']): ?>
                            <small class="text-muted text-decoration-line-through">₹<?= number_format((float)$v['mrp'], 2) ?></small>
                            <?php endif; ?>
                        </td>

                        <!-- Production Unit Stock -->
                        <td class="text-center">
                            <?php $stk = (float)$v['current_stock']; ?>
                            <?php if ($stk > 10): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                <?= number_format($stk) ?> in stock
                            </span>
                            <?php elseif ($stk > 0): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fw-bold">
                                <?= number_format($stk) ?> low
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                0 pcs
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- QR Identity -->
                        <td class="text-center">
                            <?php 
                            $vQr = !empty($v['qr_code']) ? $v['qr_code'] : (!empty($v['variant_code']) ? $v['variant_code'] : ('V' . str_pad((string)$v['id'], 4, '0', STR_PAD_LEFT)));
                            ?>
                            <button type="button" class="btn btn-sm btn-outline-dark font-monospace py-0 px-2"
                                    title="View & Scan Variant QR"
                                    onclick='viewVariantQr(<?= json_encode([
                                        "id"           => (int)$v["id"],
                                        "variant_code" => (string)($v["variant_code"] ?: $vQr),
                                        "sku"          => (string)$v["sku"],
                                        "qr_code"      => (string)$vQr,
                                        "product_name" => (string)$v["product_name"],
                                        "parent_sku"   => (string)$v["parent_sku"],
                                        "color_name"   => (string)($v["color_name"] ?? "-"),
                                        "design_name"  => (string)($v["design_name"] ?? "-"),
                                        "size_name"    => (string)($v["size_name"] ?? "-"),
                                        "price"        => number_format((float)$v["selling_price"], 2),
                                        "stock"        => (float)$v["current_stock"],
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="bi bi-qr-code me-1 text-primary"></i><?= e($vQr) ?>
                            </button>
                        </td>

                        <!-- Status -->
                        <td class="text-center">
                            <?php if ((int)$v['is_active'] === 1): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="text-end text-nowrap pe-3">
                            <div class="action-btns-group justify-content-end">
                                <a href="<?= url('production/variants/view/' . $v['id']) ?>" class="btn btn-action-icon btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/variants/qr-label/' . $v['id']) ?>" target="_blank" class="btn btn-action-icon btn-outline-secondary" title="Print Single QR Label">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <button type="button" class="btn btn-action-icon btn-outline-success" onclick="openBatchPrintModal(<?= (int)$v['id'] ?>, '<?= e($v['sku']) ?>', '<?= e($v['variant_code']) ?>')" title="Batch Print Labels">
                                    <i class="bi bi-grid-3x2-gap"></i>
                                </button>
                                <?php if (hasPermission('catalog', 'edit')): ?>
                                <a href="<?= url('production/variants/edit/' . $v['id']) ?>" class="btn btn-action-icon btn-outline-primary" title="Edit Details">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= url('production/variants/toggle/' . $v['id']) ?>" class="btn btn-action-icon btn-outline-<?= (int)$v['is_active'] === 1 ? 'danger' : 'success' ?>" title="<?= (int)$v['is_active'] === 1 ? 'Deactivate Variant' : 'Activate Variant' ?>" onclick="event.preventDefault(); if (confirm('Change active status for this variant?')) { document.getElementById('toggleVariantForm_<?= $v['id'] ?>').submit(); }">
                                    <i class="bi bi-power"></i>
                                </a>
                                <form id="toggleVariantForm_<?= $v['id'] ?>" method="POST" action="<?= url('production/variants/toggle/' . $v['id']) ?>" class="d-none">
                                    <?= csrf_field() ?>
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
</div>

<!-- Modal: View Scannable QR Identity -->
<div class="modal fade" id="modalVariantQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6"><i class="bi bi-qr-code-scan me-2 text-warning"></i>Variant QR Identity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="badge bg-primary fs-6 font-monospace mb-3" id="modalVarCode">V0001</div>
                <h5 class="fw-bold text-dark mb-1" id="modalProdName">—</h5>
                <div class="text-muted small font-monospace mb-3" id="modalParentSku">—</div>

                <!-- QR Container -->
                <div class="d-flex justify-content-center my-3">
                    <div id="modalQrCanvasBox" class="p-3 bg-white border rounded shadow-sm"></div>
                </div>

                <div class="font-monospace fw-bold fs-5 text-dark mb-3" id="modalQrText">—</div>

                <!-- Attribute Pills -->
                <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
                    <span class="badge bg-light text-dark border">Color: <strong id="modalColName">—</strong></span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle">Design: <strong id="modalDesName">—</strong></span>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Size: <strong id="modalSzName">—</strong></span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Price: ₹<strong id="modalPrice">0.00</strong></span>
                </div>

                <div class="alert alert-light border small text-muted mb-0">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    All physical units manufactured for this variant share this exact scannable QR. Scanning in Production Billing resolves stock automatically.
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <a href="#" id="modalBtnSinglePrint" target="_blank" class="btn btn-primary">
                        <i class="bi bi-printer me-1"></i> Print Label
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Batch Print QR Labels (for Production Batches) -->
<div class="modal fade" id="modalBatchPrint" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('production/variants/print-labels') ?>" target="_blank">
                <?= csrf_field() ?>
                <input type="hidden" name="variant_id" id="batchPrintVariantId" value="">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fs-6"><i class="bi bi-printer-fill me-2"></i>Batch Print QR Labels</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Target Variant</label>
                        <div class="form-control bg-light font-monospace fw-bold" id="batchPrintVariantDisplay">—</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Identical QR Labels to Print <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control form-control-lg fw-bold" min="1" max="500" value="50" required>
                        <div class="form-text small">
                            Example: If 50 physical units of this variant were stitched or packed in a production batch, enter 50 to generate 50 identical QR stickers.
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

<!-- Modal: Quick Add Design -->
<div class="modal fade" id="modalQuickAddDesign" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6"><i class="bi bi-palette-fill me-2 text-warning"></i>Quick Add Design / Pattern</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Design / Pattern Name <span class="text-danger">*</span></label>
                    <input type="text" id="qaDesignName" class="form-control" placeholder="e.g. Panda, Floral, Checked, Camo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Abbreviation / Code</label>
                    <input type="text" id="qaDesignCode" class="form-control" placeholder="e.g. PAN, FLR, CHK (optional, auto-generated if blank)">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Description</label>
                    <textarea id="qaDesignDesc" class="form-control" rows="2" placeholder="Pattern notes or artwork reference..."></textarea>
                </div>
                <div id="qaDesignError" class="alert alert-danger py-2 small d-none"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickAddDesign()">
                    <i class="bi bi-check-lg me-1"></i> Save Design
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function viewVariantQr(v) {
    document.getElementById('modalVarCode').innerText = v.variant_code || ('V' + v.id);
    document.getElementById('modalProdName').innerText = v.product_name || 'Product Variant';
    document.getElementById('modalParentSku').innerText = 'Parent SKU: ' + (v.parent_sku || '—');
    document.getElementById('modalQrText').innerText = v.qr_code || v.sku;
    document.getElementById('modalColName').innerText = v.color_name || '—';
    document.getElementById('modalDesName').innerText = v.design_name || '—';
    document.getElementById('modalSzName').innerText = v.size_name || '—';
    document.getElementById('modalPrice').innerText = v.price || '0.00';

    const qrBox = document.getElementById('modalQrCanvasBox');
    qrBox.innerHTML = '';
    new QRCode(qrBox, {
        text: v.qr_code || v.sku,
        width: 140,
        height: 140,
        correctLevel: QRCode.CorrectLevel.M
    });

    document.getElementById('modalBtnSinglePrint').href = '<?= url("production/variants/qr-label/") ?>' + v.id;

    new bootstrap.Modal(document.getElementById('modalVariantQr')).show();
}

function openBatchPrintModal(variantId, sku, vCode) {
    document.getElementById('batchPrintVariantId').value = variantId;
    document.getElementById('batchPrintVariantDisplay').innerText = vCode + ' — ' + sku;
    new bootstrap.Modal(document.getElementById('modalBatchPrint')).show();
}

function submitQuickAddDesign() {
    const name = document.getElementById('qaDesignName').value.trim();
    const code = document.getElementById('qaDesignCode').value.trim();
    const desc = document.getElementById('qaDesignDesc').value.trim();
    const errBox = document.getElementById('qaDesignError');

    if (!name) {
        errBox.innerText = 'Design name is required.';
        errBox.classList.remove('d-none');
        return;
    }
    errBox.classList.add('d-none');

    const formData = new FormData();
    formData.append('name', name);
    formData.append('code', code);
    formData.append('description', desc);
    formData.append('_csrf_token', '<?= CSRF::token() ?>');

    fetch('<?= url("api/designs/quick-add") ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalQuickAddDesign')).hide();
            window.location.reload();
        } else {
            errBox.innerText = data.message || 'Error creating design.';
            errBox.classList.remove('d-none');
        }
    })
    .catch(err => {
        errBox.innerText = 'Network error. Please try again.';
        errBox.classList.remove('d-none');
    });
}
</script>
