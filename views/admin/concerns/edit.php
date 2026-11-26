<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Concern: <?= e($concern['name']) ?></h2>
        <p class="text-muted small mb-0">Update corporate profile, tax registrations, document series prefixes, and active status.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/concerns/modules/' . $concern['id']) ?>" class="btn btn-outline-info">
            <i class="bi bi-shield-check me-1"></i> Configure Modules
        </a>
        <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Concerns
        </a>
    </div>
</div>

<form method="POST" action="<?= url('admin/concerns/update/' . $concern['id']) ?>" id="concernEditForm" enctype="multipart/form-data">
    <?= CSRF::field() ?>

    <div class="row g-4">
        <!-- Left Column: Business & Tax Credentials -->
        <div class="col-lg-8">
            <!-- Basic Identity -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>1. Legal Identity &amp; Branding</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Concern / Firm Display Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($concern['name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Short Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" value="<?= e($concern['code']) ?>" maxlength="20" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Concern Archetype <span class="text-danger">*</span></label>
                            <select name="concern_type" class="form-select fw-semibold" required>
                                <option value="HEAD_OFFICE" <?= ($concern['concern_type'] ?? 'HEAD_OFFICE') === 'HEAD_OFFICE' ? 'selected' : '' ?>>Corporate Head Office</option>
                                <option value="PRODUCTION" <?= ($concern['concern_type'] ?? '') === 'PRODUCTION' ? 'selected' : '' ?>>Manufacturing Unit</option>
                                <option value="WAREHOUSE" <?= ($concern['concern_type'] ?? '') === 'WAREHOUSE' ? 'selected' : '' ?>>Central Warehouse</option>
                                <option value="RETAIL" <?= ($concern['concern_type'] ?? '') === 'RETAIL' ? 'selected' : '' ?>>Retail Store / POS</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Legal Company Name</label>
                            <input type="text" name="legal_name" class="form-control" value="<?= e($concern['legal_name'] ?? '') ?>" placeholder="Registered corporate name">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-image text-primary me-1"></i>Concern Logo / Brand Image</span>
                                <span class="text-muted small fw-normal">Shown on all Invoices &amp; Bills</span>
                            </label>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div id="logoPreviewContainer" class="d-flex align-items-center justify-content-center bg-white border rounded-3 p-2 text-center" style="width: 140px; height: 90px; overflow: hidden;">
                                        <?php if (!empty($concern['logo_url'])): ?>
                                            <img id="logoPreviewImg" src="<?= uploadUrl($concern['logo_url']) ?>" alt="Concern Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            <div id="logoPlaceholder" class="text-muted small d-none">
                                                <i class="bi bi-cloud-arrow-up fs-2 d-block text-secondary"></i>
                                                <span>No Logo</span>
                                            </div>
                                        <?php else: ?>
                                            <img id="logoPreviewImg" src="" alt="Preview" class="d-none" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            <div id="logoPlaceholder" class="text-muted small">
                                                <i class="bi bi-cloud-arrow-up fs-2 d-block text-secondary"></i>
                                                <span>No Logo</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="logo" id="concernLogoInput" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml" onchange="previewConcernLogo(this)">
                                        <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">
                                        <div class="form-text mt-1 text-muted small">
                                            <i class="bi bi-info-circle me-1"></i>Upload PNG, JPG, JPEG, WEBP or SVG (max 5MB). This image automatically displays on all POS bills, tax invoices, purchase orders, and receipts under this concern.
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" id="clearLogoBtn" class="btn btn-sm btn-outline-secondary d-none" onclick="clearSelectedLogo()">
                                                <i class="bi bi-x-circle me-1"></i>Cancel Selected File
                                            </button>
                                            <?php if (!empty($concern['logo_url'])): ?>
                                                <button type="button" id="deleteExistingLogoBtn" class="btn btn-sm btn-outline-danger" onclick="toggleRemoveLogo()">
                                                    <i class="bi bi-trash me-1"></i>Remove Existing Logo
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Identifiers -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt me-2 text-success"></i>2. Tax &amp; Registration Identifiers</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN (15-Digit Goods &amp; Services Tax Number)</label>
                            <input type="text" name="gst_number" class="form-control text-uppercase font-monospace" value="<?= e($concern['gst_number'] ?? '') ?>" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Permanent Account Number (PAN)</label>
                            <input type="text" name="pan_number" class="form-control text-uppercase font-monospace" value="<?= e($concern['pan_number'] ?? '') ?>" maxlength="10">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address & Contact -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i>3. Contact &amp; Operating Address</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Phone / Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="concernPhone" class="form-control" value="<?= e($concern['phone'] ?? '') ?>" maxlength="10" inputmode="numeric" required>
                            <div class="invalid-feedback">Phone number must be exactly 10 numeric digits.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="concernEmail" class="form-control" value="<?= e($concern['email'] ?? '') ?>" required>
                            <div class="invalid-feedback">Enter a valid email address (e.g. billing@company.com).</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Physical Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="concernAddress" class="form-control" rows="2" minlength="10" maxlength="500" required><?= e($concern['address'] ?? '') ?></textarea>
                            <div class="invalid-feedback">Address is required (minimum 10 characters).</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" id="concernCity" class="form-control" value="<?= e($concern['city'] ?? '') ?>" maxlength="100" required>
                            <div class="invalid-feedback" id="concernCityFeedback">Enter a valid city name (do not enter a state name).</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                            <?php $curState = trim((string)($concern['state'] ?? '')); ?>
                            <select name="state" id="concernState" class="form-select" required>
                                <option value="">-- Select State --</option>
                                <?php foreach (ConcernController::indianStates() as $stName): ?>
                                    <option value="<?= e($stName) ?>" <?= strcasecmp($curState, $stName) === 0 ? 'selected' : '' ?>><?= e($stName) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a valid State.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" id="concernPincode" class="form-control font-monospace" value="<?= e($concern['pincode'] ?? '') ?>" maxlength="6" inputmode="numeric">
                            <div class="invalid-feedback">Pincode must be 6 digits.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Document Prefixes & Status -->
        <div class="col-lg-4">
            <!-- Document Prefixes -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-hash me-2 text-info"></i>4. Document Number Prefixes</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['invoice_prefix'] ?? 'INV-') ?>" required>
                        <div class="form-text">Prefix for POS sales and tax bills.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purchase Order Prefix</label>
                        <input type="text" name="po_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['po_prefix'] ?? 'PO-') ?>" required>
                        <div class="form-text">Prefix for fabric and yarn purchases.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispatch / Gate Pass Prefix</label>
                        <input type="text" name="dispatch_prefix" class="form-control text-uppercase font-monospace" value="<?= e($concern['dispatch_prefix'] ?? 'DSP-') ?>" required>
                        <div class="form-text">Prefix for outward gate passes.</div>
                    </div>
                </div>
            </div>

            <!-- Status Control -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-toggle-on me-2 text-success"></i>5. Operating Status</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch fs-6">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= $concern['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActiveSwitch">Entity Active &amp; Operational</label>
                    </div>
                    <div class="small text-muted mt-2">When deactivated, users cannot record new transactions under this concern.</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm bg-white p-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                    <i class="bi bi-check2-circle me-1"></i> Save Changes
                </button>
                <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary w-100 mt-2">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
const originalLogoUrl = <?= json_encode(!empty($concern['logo_url']) ? uploadUrl($concern['logo_url']) : '') ?>;

function previewConcernLogo(input) {
    const previewImg = document.getElementById('logoPreviewImg');
    const placeholder = document.getElementById('logoPlaceholder');
    const clearBtn = document.getElementById('clearLogoBtn');
    const removeInput = document.getElementById('removeLogoInput');

    if (removeInput) removeInput.value = '0';

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller image.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            if (placeholder) placeholder.classList.add('d-none');
            if (clearBtn) clearBtn.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}

function clearSelectedLogo() {
    const input = document.getElementById('concernLogoInput');
    const previewImg = document.getElementById('logoPreviewImg');
    const placeholder = document.getElementById('logoPlaceholder');
    const clearBtn = document.getElementById('clearLogoBtn');
    const removeInput = document.getElementById('removeLogoInput');

    if (input) input.value = '';
    if (clearBtn) clearBtn.classList.add('d-none');

    if (originalLogoUrl && (!removeInput || removeInput.value !== '1')) {
        previewImg.src = originalLogoUrl;
        previewImg.classList.remove('d-none');
        if (placeholder) placeholder.classList.add('d-none');
    } else {
        previewImg.src = '';
        previewImg.classList.add('d-none');
        if (placeholder) placeholder.classList.remove('d-none');
    }
}

function toggleRemoveLogo() {
    const removeInput = document.getElementById('removeLogoInput');
    const previewImg = document.getElementById('logoPreviewImg');
    const placeholder = document.getElementById('logoPlaceholder');
    const input = document.getElementById('concernLogoInput');
    const deleteBtn = document.getElementById('deleteExistingLogoBtn');

    if (removeInput.value === '1') {
        // Undo removal
        removeInput.value = '0';
        if (originalLogoUrl) {
            previewImg.src = originalLogoUrl;
            previewImg.classList.remove('d-none');
            if (placeholder) placeholder.classList.add('d-none');
        }
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Remove Existing Logo';
            deleteBtn.className = 'btn btn-sm btn-outline-danger';
        }
    } else {
        // Mark for removal
        if (confirm('Are you sure you want to remove the current logo for this concern? It will be deleted when you save changes.')) {
            removeInput.value = '1';
            if (input) input.value = '';
            previewImg.src = '';
            previewImg.classList.add('d-none');
            if (placeholder) placeholder.classList.remove('d-none');
            if (deleteBtn) {
                deleteBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Undo Remove Logo';
                deleteBtn.className = 'btn btn-sm btn-warning';
            }
        }
    }
}

(function () {
    const form = document.getElementById('concernEditForm');
    if (!form) return;

    const norm = v => v.trim().toLowerCase().replace(/[\s.\-]+/g, '');
    const stateSet = new Set(<?= json_encode(ConcernController::indianStates()) ?>.map(norm));

    const rules = [
        {
            id: 'concernPhone',
            live: true,
            check: v => /^[0-9]{10}$/.test(v)
        },
        {
            id: 'concernEmail',
            live: false,
            check: v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)
        },
        {
            id: 'concernAddress',
            live: false,
            check: v => v.length >= 10 && v.length <= 500
        },
        {
            id: 'concernCity',
            live: true,
            check: v => /^[a-zA-Z\s.\-]{2,100}$/.test(v) && !stateSet.has(norm(v)),
            onUpdate: (el, v) => {
                const fb = document.getElementById('concernCityFeedback');
                if (!fb) return;
                if (stateSet.has(norm(v))) {
                    fb.textContent = 'City cannot be a state name. Enter a valid city (e.g. Tirupur).';
                } else {
                    fb.textContent = 'Enter a valid city name (letters only, minimum 2 characters).';
                }
            }
        },
        {
            id: 'concernState',
            live: true,
            check: v => stateSet.has(norm(v))
        },
        {
            id: 'concernPincode',
            live: true,
            check: v => v === '' || /^[0-9]{6}$/.test(v)
        }
    ];

    const validateField = (rule) => {
        const el = document.getElementById(rule.id);
        if (!el) return true;
        const val = el.value.trim();
        const ok = rule.check(val);
        if (rule.onUpdate) rule.onUpdate(el, val);
        el.classList.toggle('is-invalid', !ok);
        return ok;
    };

    rules.forEach(rule => {
        const el = document.getElementById(rule.id);
        if (!el) return;
        const ev = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(ev, () => {
            if (rule.live || el.classList.contains('is-invalid')) validateField(rule);
        });
        el.addEventListener('blur', () => validateField(rule));
    });

    form.addEventListener('submit', function (e) {
        let firstInvalid = null;
        rules.forEach(rule => {
            if (!validateField(rule) && !firstInvalid) {
                firstInvalid = document.getElementById(rule.id);
            }
        });
        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
