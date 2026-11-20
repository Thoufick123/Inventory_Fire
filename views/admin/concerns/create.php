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

<!-- Entity Archetype Presets -->
<div class="card border-0 bg-primary-subtle border-start border-4 border-primary shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div>
                <span class="fw-bold text-dark"><i class="bi bi-magic me-1.5 text-primary"></i>Concern Archetype Presets</span>
                <span class="text-muted small ms-1">— Click a preset to auto-configure this concern for Production, Warehouse, or Retail:</span>
            </div>
            <span class="badge bg-white text-primary border border-primary-subtle small"><i class="bi bi-lightning-charge me-1"></i>1-Click Preset</span>
        </div>
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-white w-100 p-2.5 text-start border shadow-xs rounded-3 archetype-btn h-100" onclick="applyConcernArchetype('PRODUCTION')">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-5">🏭</span>
                        <strong class="small text-dark">Production Entity</strong>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Manufacturing, Cutting, Stitching, Purchases &amp; Outwards.</div>
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-white w-100 p-2.5 text-start border shadow-xs rounded-3 archetype-btn h-100" onclick="applyConcernArchetype('WAREHOUSE')">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-5">🏢</span>
                        <strong class="small text-dark">Central Warehouse</strong>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">GRN Inwards, Inventory Stock, Bin Allocation &amp; Gate Passes.</div>
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-white w-100 p-2.5 text-start border shadow-xs rounded-3 archetype-btn h-100" onclick="applyConcernArchetype('RETAIL')">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-5">🛍️</span>
                        <strong class="small text-dark">Retail Store / POS</strong>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Factory Showroom, Barcode Billing, Cash Drawers &amp; Exchanges.</div>
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-white w-100 p-2.5 text-start border shadow-xs rounded-3 archetype-btn h-100" onclick="applyConcernArchetype('ENTERPRISE')">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-5">🌐</span>
                        <strong class="small text-dark">Corporate Head Office</strong>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">All operational modules enabled with global visibility.</div>
                </button>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('admin/concerns/store') ?>" id="concernForm">
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
// Archetype fast setup presets
function applyConcernArchetype(type) {
    const nameInput = document.querySelector('input[name="name"]');
    const codeInput = document.querySelector('input[name="code"]');
    const invPrefix = document.querySelector('input[name="invoice_prefix"]');
    const poPrefix = document.querySelector('input[name="po_prefix"]');
    const dspPrefix = document.querySelector('input[name="dispatch_prefix"]');
    const typeSelect = document.getElementById('concernTypeSelect');

    if (typeSelect) {
        typeSelect.value = (type === 'ENTERPRISE') ? 'HEAD_OFFICE' : type;
    }

    if (type === 'PRODUCTION') {
        if (!nameInput.value || nameInput.value.includes('Warehouse') || nameInput.value.includes('Store')) {
            nameInput.value = 'GRM Apparels Production Unit';
        }
        if (!codeInput.value || codeInput.value.includes('WH') || codeInput.value.includes('RET')) {
            codeInput.value = 'CN-PRD';
        }
        if (invPrefix) invPrefix.value = 'PRD-INV-';
        if (poPrefix) poPrefix.value = 'PO-';
        if (dspPrefix) dspPrefix.value = 'DSP-';
    } else if (type === 'WAREHOUSE') {
        if (!nameInput.value || nameInput.value.includes('Production') || nameInput.value.includes('Store')) {
            nameInput.value = 'GRM Central Logistics & Warehouse';
        }
        if (!codeInput.value || codeInput.value.includes('PRD') || codeInput.value.includes('RET')) {
            codeInput.value = 'CN-WH';
        }
        if (invPrefix) invPrefix.value = 'WH-INV-';
        if (poPrefix) poPrefix.value = 'WH-PO-';
        if (dspPrefix) dspPrefix.value = 'DSP-';
    } else if (type === 'RETAIL') {
        if (!nameInput.value || nameInput.value.includes('Production') || nameInput.value.includes('Warehouse')) {
            nameInput.value = 'GRM Factory Showroom & Store';
        }
        if (!codeInput.value || codeInput.value.includes('PRD') || codeInput.value.includes('WH')) {
            codeInput.value = 'CN-RET';
        }
        if (invPrefix) invPrefix.value = 'POS-';
        if (poPrefix) poPrefix.value = 'RET-PO-';
        if (dspPrefix) dspPrefix.value = 'DSP-';
    } else if (type === 'ENTERPRISE') {
        if (invPrefix) invPrefix.value = 'INV-';
        if (poPrefix) poPrefix.value = 'PO-';
        if (dspPrefix) dspPrefix.value = 'DSP-';
    }

    // Animate button feedback
    document.querySelectorAll('.archetype-btn').forEach(b => b.classList.remove('border-primary', 'bg-primary-subtle'));
    if (window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('border-primary', 'bg-primary-subtle');
    }
}
</script>
