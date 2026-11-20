<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-building-add text-primary me-2"></i>Add New Business Concern</h2>
        <p class="text-muted small mb-0">Register a new company entity, independent manufacturing unit, or sister firm with separate tax credentials and document numbering.</p>
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
                            <div class="form-text">Common business name shown across dashboard and invoice headers.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Short Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="e.g. CN-02, GRM-TEX" maxlength="20" required>
                            <div class="form-text">Used in QR labels and barcodes.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Concern Archetype <span class="text-danger">*</span></label>
                            <select name="concern_type" id="concernTypeSelect" class="form-select fw-semibold" required>
                                <option value="HEAD_OFFICE">Corporate Head Office</option>
                                <option value="PRODUCTION">Manufacturing Unit</option>
                                <option value="WAREHOUSE">Central Warehouse</option>
                                <option value="RETAIL">Retail Store / POS</option>
                            </select>
                            <div class="form-text">Operational role.</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Legal Company Name</label>
                            <input type="text" name="legal_name" class="form-control" placeholder="e.g. GRM Apparels Private Limited">
                            <div class="form-text">Official registered corporate title for GST tax invoices and legal dispatches.</div>
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
                                        <div class="form-text mt-1 text-muted small">
                                            <i class="bi bi-info-circle me-1"></i>Upload PNG, JPG, JPEG, WEBP or SVG (max 5MB). This image automatically displays on all POS bills, tax invoices, purchase orders, and receipts under this concern.
                                        </div>
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
                            <label class="form-label fw-semibold">Official Phone / Mobile</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. billing@grmapparels.com">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Registered Physical Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Building, Street, Industrial Estate..."></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Tirupur">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" placeholder="e.g. Tamil Nadu">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Pincode</label>
                            <input type="text" name="pincode" class="form-control font-monospace" placeholder="641602" maxlength="10">
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
</script>
