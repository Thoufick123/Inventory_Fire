<?php
// Register Asset View
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-tools text-primary me-2"></i>Register New Equipment / Asset</h4>
            <p class="text-muted small mb-0">Record plant machinery, stitching/cutting machines, IT hardware, and tools</p>
        </div>
        <div>
            <a href="<?= url('assets') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Asset Register
            </a>
        </div>
    </div>

    <form method="POST" action="<?= url('assets/store') ?>">
        <?= CSRF::field() ?>
        <?php $concernSelectWrapCard = true; include BASE_PATH . '/views/partials/admin_concern_select.php'; unset($concernSelectWrapCard); ?>

        <div class="row g-4">
            <!-- Asset Identification Card -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-text text-primary me-2"></i>1. Asset Identification &amp; Master</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Asset Code <span class="text-danger">*</span></label>
                                <input type="text" name="asset_code" class="form-control font-monospace fw-bold bg-light" value="<?= e($nextCode) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold small mb-0">Category <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" id="btnToggleAddAssetCategory" style="font-size: 11px;">
                                        <i class="bi bi-plus-circle me-1"></i>Add Category
                                    </button>
                                </div>
                                <select name="category_id" id="assetCategorySelect" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="addAssetCategoryBox" class="mt-2 p-2 border rounded bg-light d-none">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="newAssetCategoryName" class="form-control" placeholder="Enter new category name..." maxlength="150" autocomplete="off">
                                        <button type="button" class="btn btn-primary fw-semibold" id="btnSaveAssetCategory">
                                            <i class="bi bi-check2 me-1"></i>Save
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="btnCancelAssetCategory" title="Cancel">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <div id="addAssetCategoryError" class="text-danger small mt-1 d-none"></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Asset Name / Machine Description <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Juki DDL-8700 High-Speed Single Needle Lockstitch" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Manufacturer / Brand</label>
                                <input type="text" name="brand" class="form-control" placeholder="e.g. JUKI, Brother, Eastman">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Model Number</label>
                                <input type="text" name="model_number" class="form-control" placeholder="e.g. DDL-8700-7">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Serial Number</label>
                                <input type="text" name="serial_number" class="form-control font-monospace" placeholder="e.g. SN-89214710">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Barcode / QR Code String</label>
                                <input type="text" name="barcode_qr" class="form-control font-monospace" placeholder="Auto-generated if blank" value="<?= e($nextCode) ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Technical Specifications</label>
                                <textarea name="specifications" class="form-control form-control-sm" rows="2" placeholder="Motor wattage, RPM, needle size, voltage rating, etc."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Custodian Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt text-primary me-2"></i>2. Location &amp; Custodian</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Location Unit</label>
                                <select name="location_type" class="form-select">
                                    <?php
                                    $locConcerns = $locationConcerns ?? (function_exists('allActiveConcerns') ? allActiveConcerns() : []);
                                    $activeLocCid = function_exists('currentConcernId') ? currentConcernId() : null;
                                    if (!empty($locConcerns)):
                                        foreach ($locConcerns as $lc):
                                            $lcLabel = $lc['name'] . (!empty($lc['code']) ? ' (' . $lc['code'] . ')' : '');
                                            $isSel = ($activeLocCid !== null && (int)$lc['id'] === (int)$activeLocCid);
                                    ?>
                                        <option value="<?= e($lcLabel) ?>" <?= $isSel ? 'selected' : '' ?>><?= e($lcLabel) ?></option>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <option value="PRODUCTION" selected>Factory / Production Unit</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Floor / Department / Line</label>
                                <input type="text" name="floor_area" class="form-control" placeholder="e.g. Floor 2 - Stitching Line A">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Custodian / Machine Operator</label>
                                <input type="text" name="custodian_name" class="form-control" placeholder="Operator or supervisor in-charge">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Current Condition</label>
                                <select name="condition_state" class="form-select">
                                    <option value="EXCELLENT">Excellent (Brand New / Pristine)</option>
                                    <option value="GOOD" selected>Good (Fully Operational)</option>
                                    <option value="FAIR">Fair (Minor Wear &amp; Tear)</option>
                                    <option value="POOR">Poor (Requires Service)</option>
                                    <option value="CRITICAL">Critical (Breakdown)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Valuation & Procurement -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-currency-rupee text-primary me-2"></i>3. Valuation &amp; Procurement Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Purchase Cost (₹)</label>
                                <input type="number" step="0.01" name="purchase_cost" id="purchaseCost" class="form-control fw-bold" placeholder="0.00" oninput="onCostChange(this.value)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Vendor / Supplier Name</label>
                                <input type="text" name="vendor_name" class="form-control" placeholder="Supplier company name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Vendor Contact Phone</label>
                                <input type="text" name="vendor_contact" class="form-control" placeholder="Phone / Mobile">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Tax Invoice / Bill Number</label>
                                <input type="text" name="invoice_number" class="form-control" placeholder="Invoice #">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Warranty Expiry Date</label>
                                <input type="date" name="warranty_expiry" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Useful Life (Years)</label>
                                <input type="number" name="useful_life_years" class="form-control" value="5" min="1" max="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Salvage / Scrap Value (₹)</label>
                                <input type="number" step="0.01" name="salvage_value" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Current Book Value (₹)</label>
                                <input type="number" step="0.01" name="current_book_value" id="currentBookVal" class="form-control fw-bold text-primary" placeholder="0.00">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Operational Status</label>
                                <select name="status" class="form-select">
                                    <option value="IN_USE" selected>IN_USE (Currently Running in Production)</option>
                                    <option value="IDLE">IDLE (Standby / Spare)</option>
                                    <option value="IN_MAINTENANCE">IN_MAINTENANCE (Under Repair / Servicing)</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Internal Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Additional remarks, supplier terms, AMC details, etc."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Register Asset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function onCostChange(val) {
    const bookValEl = document.getElementById('currentBookVal');
    if (bookValEl && !bookValEl.value) {
        bookValEl.value = val;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btnToggleAddAssetCategory');
    const box = document.getElementById('addAssetCategoryBox');
    const nameInput = document.getElementById('newAssetCategoryName');
    const btnSave = document.getElementById('btnSaveAssetCategory');
    const btnCancel = document.getElementById('btnCancelAssetCategory');
    const errEl = document.getElementById('addAssetCategoryError');
    const selectEl = document.getElementById('assetCategorySelect');

    function showErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg;
        errEl.classList.remove('d-none');
    }

    function hideBox() {
        box?.classList.add('d-none');
        errEl?.classList.add('d-none');
        if (nameInput) nameInput.value = '';
    }

    btnToggle?.addEventListener('click', function () {
        if (!box) return;
        const isHidden = box.classList.toggle('d-none');
        errEl?.classList.add('d-none');
        if (!isHidden) {
            nameInput?.focus();
        }
    });

    btnCancel?.addEventListener('click', hideBox);

    function saveCategory() {
        const catName = (nameInput?.value || '').trim();
        if (!catName) {
            showErr('Please enter a category name.');
            nameInput?.focus();
            return;
        }
        errEl?.classList.add('d-none');
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }
        const form = selectEl?.closest('form');
        const csrfInput = form?.querySelector('input[name="csrf_token"], input[name="_token"]');
        const fd = new FormData();
        if (csrfInput) fd.append(csrfInput.name, csrfInput.value);
        fd.append('name', catName);

        fetch('<?= url('assets/categories/store') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (!data || !data.success || !data.category) {
                showErr((data && data.message) ? data.message : 'Could not save category.');
                return;
            }
            const cat = data.category;
            if (selectEl) {
                let opt = Array.from(selectEl.options).find(o => String(o.value) === String(cat.id));
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = String(cat.id);
                    opt.textContent = cat.name;
                    selectEl.appendChild(opt);
                }
                selectEl.value = String(cat.id);
                selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
            hideBox();
        })
        .catch(() => showErr('Server error while saving category.'))
        .finally(() => {
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="bi bi-check2 me-1"></i>Save';
            }
        });
    }

    btnSave?.addEventListener('click', saveCategory);
    nameInput?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveCategory();
        }
    });
});
</script>
