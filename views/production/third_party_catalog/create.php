<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Third-Party Catalog Style</h2>
        <span class="text-muted small">Register a new third-party product style in the master third-party catalog</span>
    </div>
    <a href="<?= url('production/purchases/mapping/catalog') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<form method="POST" action="<?= url('production/purchases/mapping/catalog/store') ?>" id="formCreateThirdPartyProduct">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Basic Product Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Product / Style Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="tp_name" class="form-control" placeholder="e.g. Mens Casual Polo T-Shirt" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Style Code / SKU</label>
                            <input type="text" name="code" id="tp_code" class="form-control" placeholder="Auto-generated if empty">
                            <div class="form-text small">e.g. TP-POLO-001</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="tp_category_id" class="form-select" required onchange="loadTpSubcategories()">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?> (<?= e($cat['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-link btn-sm px-0" onclick="addTpTaxonomy('category')">+ Add third-party category</button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Subcategory</label>
                            <select name="subcategory_id" id="tp_subcategory_id" class="form-select" disabled onchange="loadTpSubSubcategories()">
                                <option value="">-- Select Subcategory --</option>
                            </select>
                            <button type="button" class="btn btn-link btn-sm px-0" onclick="addTpTaxonomy('subcategory')">+ Add subcategory</button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sub-Subcategory</label>
                            <select name="sub_subcategory_id" id="tp_sub_subcategory_id" class="form-select" disabled>
                                <option value="">-- Select Sub-Subcategory --</option>
                            </select>
                            <button type="button" class="btn btn-link btn-sm px-0" onclick="addTpTaxonomy('subsubcategory')">+ Add sub-subcategory</button>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Color</label>
                            <input type="text" name="color" class="form-control" placeholder="e.g. Navy Blue / Black">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Size</label>
                            <input type="text" name="size" class="form-control" placeholder="e.g. M, L, XL, Free Size">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <select name="unit" class="form-select">
                                <option value="PCS" selected>PCS (Pieces)</option>
                                <option value="BOX">BOX</option>
                                <option value="SET">SET</option>
                                <option value="MTR">MTR (Meters)</option>
                                <option value="KGS">KGS (Kilograms)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Brand</label>
                            <select name="seller_id" class="form-select">
                                <option value="">-- Select Supplier (Optional) --</option>
                                <?php foreach ($sellers as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>">
                                        <?= e($s['company'] ? $s['company'] . ' (' . $s['name'] . ')' : $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">HSN Code</label>
                            <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 6205 or 6109">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description / Notes</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Optional product description or fabric composition notes..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Submission Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-tag me-2 text-success"></i>Pricing &amp; Margins</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Purchase Rate (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" name="purchase_rate" id="tp_purchase_rate" class="form-control text-end" value="0.00" oninput="calcMargins()">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">MRP (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0.01" name="mrp" id="tp_mrp" class="form-control text-end fw-bold" required oninput="calcMargins()">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selling Price (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0.01" name="selling_price" id="tp_selling_price" class="form-control text-end fw-bold text-success" required oninput="calcMargins()">
                        </div>
                        <div class="form-text small text-danger d-none" id="tp_price_warning">Selling price cannot exceed MRP.</div>
                    </div>

                    <div class="alert alert-light border p-3 small mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Estimated Margin:</span>
                            <span class="fw-bold text-success" id="tp_margin_val">₹0.00 (0%)</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-xs">
                        <i class="bi bi-check2-circle me-1"></i> Save to Third-Party Catalog
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function loadTpSubcategories() {
    const catId = document.getElementById('tp_category_id').value;
    const subSelect = document.getElementById('tp_subcategory_id');
    const subSubSelect = document.getElementById('tp_sub_subcategory_id');

    subSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
    subSelect.disabled = true;
    subSubSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
    subSubSelect.disabled = true;

    if (!catId) return;

    fetch('<?= url("api/subcategories") ?>/' + catId + '?catalog_source=THIRD_PARTY')
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                    subSelect.appendChild(opt);
                });
                subSelect.disabled = false;
            } else {
                subSelect.innerHTML = '<option value="">-- No Subcategories --</option>';
            }
        })
        .catch(err => console.error(err));
}

function loadTpSubSubcategories() {
    const subId = document.getElementById('tp_subcategory_id').value;
    const subSubSelect = document.getElementById('tp_sub_subcategory_id');

    subSubSelect.innerHTML = '<option value="">-- Select Sub-Subcategory --</option>';
    subSubSelect.disabled = true;

    if (!subId) return;

    fetch('<?= url("api/sub-subcategories") ?>/' + subId + '?catalog_source=THIRD_PARTY')
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                    subSubSelect.appendChild(opt);
                });
                subSubSelect.disabled = false;
            } else {
                subSubSelect.innerHTML = '<option value="">-- No Sub-Subcategories --</option>';
            }
        })
        .catch(err => console.error(err));
}

async function addTpTaxonomy(level) {
    const labels = {
        category: 'third-party category',
        subcategory: 'subcategory',
        subsubcategory: 'sub-subcategory'
    };
    const name = window.prompt('Enter the ' + labels[level] + ' name:');
    if (!name || !name.trim()) return;

    const category = document.getElementById('tp_category_id');
    const subcategory = document.getElementById('tp_subcategory_id');
    if (level === 'subcategory' && !category.value) {
        window.alert('Select a category first.');
        return;
    }
    if (level === 'subsubcategory' && !subcategory.value) {
        window.alert('Select a subcategory first.');
        return;
    }

    const endpoints = {
        category: '<?= url("api/categories/quick-add") ?>',
        subcategory: '<?= url("api/subcategories/quick-add") ?>',
        subsubcategory: '<?= url("api/sub-subcategories/quick-add") ?>'
    };
    const payload = new URLSearchParams({ name: name.trim(), catalog_source: 'THIRD_PARTY' });
    if (level === 'subcategory') payload.set('category_id', category.value);
    if (level === 'subsubcategory') payload.set('subcategory_id', subcategory.value);

    try {
        const response = await fetch(endpoints[level], {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Could not add taxonomy value.');

        const target = level === 'category' ? category : (level === 'subcategory' ? subcategory : document.getElementById('tp_sub_subcategory_id'));
        const option = new Option(result.name + (result.code ? ' (' + result.code + ')' : ''), result.id, true, true);
        target.add(option);
        target.disabled = false;
        if (level === 'category') loadTpSubcategories();
        if (level === 'subcategory') loadTpSubSubcategories();
    } catch (error) {
        window.alert(error.message);
    }
}

function calcMargins() {
    const cost = parseFloat(document.getElementById('tp_purchase_rate').value) || 0;
    const mrp = parseFloat(document.getElementById('tp_mrp').value) || 0;
    const sp = parseFloat(document.getElementById('tp_selling_price').value) || 0;
    const warning = document.getElementById('tp_price_warning');
    const marginSpan = document.getElementById('tp_margin_val');

    if (sp > mrp && mrp > 0) {
        warning.classList.remove('d-none');
    } else {
        warning.classList.add('d-none');
    }

    if (sp > 0 && cost > 0) {
        const diff = sp - cost;
        const pct = Math.round((diff / sp) * 100);
        marginSpan.textContent = '₹' + diff.toFixed(2) + ' (' + pct + '%)';
    } else {
        marginSpan.textContent = '₹0.00 (0%)';
    }
}
</script>
