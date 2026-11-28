<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-building-add text-primary me-2"></i>Add New Business Concern</h2>
    </div>
    <div>
        <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Concerns
        </a>
    </div>
</div>

<form method="POST" action="<?= url('admin/concerns/store') ?>" id="concernForm" enctype="multipart/form-data">
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Concern / Firm Display Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. GRM Apparels Unit 2 / GRM Tex Trading" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Short Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="e.g. CN-02, GRM-TEX" maxlength="20" required>
                            
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Legal Company Name</label>
                            <input type="text" name="legal_name" class="form-control" placeholder="e.g. GRM Apparels Private Limited">
                            
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-image text-primary me-1"></i>Concern Logo / Brand Image</span>
                                <span class="text-muted small fw-normal">Shown on all Invoices &amp; Bills</span>
                            </label>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div id="logoPreviewContainer" class="d-flex align-items-center justify-content-center bg-white border rounded-3 p-2 text-center" style="width: 140px; height: 90px; overflow: hidden;">
                                        <img id="logoPreviewImg" src="" alt="Preview" class="d-none" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                        <div id="logoPlaceholder" class="text-muted small">
                                            <i class="bi bi-cloud-arrow-up fs-2 d-block text-secondary"></i>
                                            <span>No Logo</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="logo" id="concernLogoInput" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml" onchange="previewConcernLogo(this)">
                                       
                                        <button type="button" id="clearLogoBtn" class="btn btn-sm btn-outline-danger mt-2 d-none" onclick="clearSelectedLogo()">
                                            <i class="bi bi-x-circle me-1"></i>Remove Selected File
                                        </button>
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
                            <input type="text" name="gst_number" class="form-control text-uppercase font-monospace" placeholder="e.g. 33AAAAA0000A1Z5" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Permanent Account Number (PAN)</label>
                            <input type="text" name="pan_number" class="form-control text-uppercase font-monospace" placeholder="e.g. AAAAA0000A" maxlength="10">
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
                            <input type="text" name="phone" id="concernPhone" class="form-control" placeholder="e.g. 9876543210" maxlength="10" inputmode="numeric" required>
                            <div class="invalid-feedback">Phone number must be exactly 10 numeric digits.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="concernEmail" class="form-control" placeholder="e.g. billing@grmapparels.com" required>
                            <div class="invalid-feedback">Enter a valid email address (e.g. billing@company.com).</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Physical Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="concernAddress" class="form-control" rows="2" placeholder="Building, Street, Industrial Estate..." minlength="10" maxlength="500" required></textarea>
                            <div class="invalid-feedback">Address is required (minimum 10 characters).</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" id="concernCity" class="form-control" placeholder="e.g. Tirupur" maxlength="100" required>
                            <div class="invalid-feedback" id="concernCityFeedback">Enter a valid city name (do not enter a state name).</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                            <select name="state" id="concernState" class="form-select" required>
                                <option value="">-- Select State --</option>
                                <?php foreach (ConcernController::indianStates() as $stName): ?>
                                    <option value="<?= e($stName) ?>"><?= e($stName) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a valid State.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" id="concernPincode" class="form-control font-monospace" placeholder="641602" maxlength="6" inputmode="numeric">
                            <div class="invalid-feedback">Pincode must be 6 digits.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Document Prefixes & Actions -->
        <div class="col-lg-4">
            <!-- Document Prefixes -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-hash me-2 text-info"></i>4. Document Number Prefixes</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control text-uppercase font-monospace" value="INV-" placeholder="e.g. GRM2-INV-">
                        <div class="form-text">Prefix for POS sales and tax bills.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purchase Order Prefix</label>
                        <input type="text" name="po_prefix" class="form-control text-uppercase font-monospace" value="PO-" placeholder="e.g. PO-U2-">
                        <div class="form-text">Prefix for fabric and yarn purchases.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Dispatch / Gate Pass Prefix</label>
                        <input type="text" name="dispatch_prefix" class="form-control text-uppercase font-monospace" value="DSP-" placeholder="e.g. DSP-U2-">
                        <div class="form-text">Prefix for outward gate passes.</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm bg-white p-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                    <i class="bi bi-check2-circle me-1"></i> Create Business Concern
                </button>
                <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary w-100 mt-2">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
function previewConcernLogo(input) {
    const previewImg = document.getElementById('logoPreviewImg');
    const placeholder = document.getElementById('logoPlaceholder');
    const clearBtn = document.getElementById('clearLogoBtn');

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

    if (input) input.value = '';
    if (previewImg) {
        previewImg.src = '';
        previewImg.classList.add('d-none');
    }
    if (placeholder) placeholder.classList.remove('d-none');
    if (clearBtn) clearBtn.classList.add('d-none');
}

(function () {
    const form = document.getElementById('concernForm');
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
