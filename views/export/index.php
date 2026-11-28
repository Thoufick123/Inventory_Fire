<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2">
            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-arrow-down-fill fs-4"></i>
            </span>
            <span>Data Export Center</span>
        </h2>
        <span class="text-muted">Export comprehensive production pipelines, supplier procurement, financial P&amp;L, and warehouse logistics.</span>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= url('export/direct-bills-csv') ?>" class="btn btn-outline-warning text-dark btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" title="Export only Direct / Separated Sales Bills into a separate CSV file">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Direct Bills CSV</span>
        </a>
        <a href="<?= url('export/quick-csv') ?>" class="btn btn-outline-primary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" title="Instantly download full CSV for current month">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Current Month CSV</span>
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm px-3 d-flex align-items-center gap-1.5" onclick="submitQuickPrint()">
            <i class="bi bi-printer"></i>
            <span>Print Current Month</span>
        </button>
    </div>
</div>

<!-- Quick Stats & Facility Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card revenue-kpi-card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Active Business Concern</div>
                    <div class="fw-bold text-primary fs-5 mt-1 d-flex align-items-center gap-2 flex-wrap">
                        <span><?= e($activeConcern['name'] ?? ($activeFacility['name'] ?? 'Consolidated Enterprise')) ?></span>
                        <?php if (!empty($activeConcern['code'])): ?>
                            <span class="badge bg-primary text-white" style="font-size: 10px;"><?= e($activeConcern['code']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-buildings text-primary me-1"></i><?= !empty($activeConcern['legal_name']) ? e($activeConcern['legal_name']) : (!empty($activeFacility['name']) ? 'Facility: ' . e($activeFacility['name']) : 'Multi-Concern Architecture') ?>
                    </div>
                </div>
                <div class="bg-primary-subtle text-primary revenue-kpi-icon">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card revenue-kpi-card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Export Format Support</div>
                    <div class="fw-bold text-success fs-5 mt-1">Excel CSV &amp; Printable Summary</div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i>Native decimal formatting with UTF-8 BOM
                    </div>
                </div>
                <div class="bg-success-subtle text-success revenue-kpi-icon">
                    <i class="bi bi-file-earmark-spreadsheet fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card revenue-kpi-card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="me-2" style="min-width: 0;">
                    <div class="text-uppercase fw-semibold text-muted small">Query Engine Optimization</div>
                    <div class="fw-bold text-info-emphasis fs-5 mt-1">High-Speed SARGable Range Scan</div>
                    <div class="small text-muted mt-2 text-truncate">
                        <i class="bi bi-lightning-charge text-info me-1"></i>Indexed date predicates &amp; pre-joined aggregations
                    </div>
                </div>
                <div class="bg-info-subtle text-info-emphasis revenue-kpi-icon">
                    <i class="bi bi-lightning-charge fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Export Configuration Form -->
<form method="GET" action="<?= url('export/download') ?>" target="_blank" id="exportMainForm">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary"></i>
                    <span>Configure Export Dataset</span>
                </h5>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1.5 rounded-pill">
                    <?= count($modules) ?> Data Modules Available
                </span>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- 1. Report Period Selection -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label fw-bold text-dark mb-0 fs-6">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">1</span>
                        Select Export Date Period
                    </label>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="period_type" id="periodTypeMonth" value="MONTH" checked onchange="togglePeriodInputs()">
                        <label class="btn btn-outline-primary fw-semibold" for="periodTypeMonth">Month-Wise</label>

                        <input type="radio" class="btn-check" name="period_type" id="periodTypeCustom" value="CUSTOM" onchange="togglePeriodInputs()">
                        <label class="btn btn-outline-primary fw-semibold" for="periodTypeCustom">Custom Date Range</label>
                    </div>
                </div>

                <!-- Month-wise Picker -->
                <div class="row g-3" id="monthPickerRow">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">Select Month</label>
                        <select name="selected_month" class="form-select fw-semibold" onchange="refreshExportModuleCounts()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($curMonth === $m) ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">Select Year</label>
                        <select name="selected_year" class="form-select fw-semibold" onchange="refreshExportModuleCounts()">
                            <?php for ($y = $curYear - 3; $y <= $curYear + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $curYear) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Custom Date Picker -->
                <div class="row g-3 d-none" id="customPickerRow">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">From Date (Start)</label>
                        <input type="date" name="date_from" class="form-control" value="<?= e($firstDayOfMonth) ?>" onchange="refreshExportModuleCounts()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1 fw-semibold">To Date (End)</label>
                        <input type="date" name="date_to" class="form-control" value="<?= e($today) ?>" onchange="refreshExportModuleCounts()">
                    </div>
                </div>
            </div>

            <!-- 1b. Business Concern Scoping & Filtering -->
            <?php if (!empty($showConcernFilter)): ?>
            <div class="mb-4 pb-3 border-bottom">
                <div class="concern-filter-panel border rounded-3 bg-light bg-opacity-50 p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <label class="form-label fw-bold text-dark mb-1 fs-6">
                                <i class="bi bi-buildings-fill me-1 text-primary"></i>
                                Filter by Business Concern
                            </label>
                            <div class="small text-muted">Choose one or more entities, or leave all unselected for a consolidated export.</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <?php if (!empty($isAdminUser)): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" onclick="selectConcernFilter('ALL')">
                                <i class="bi bi-globe me-1"></i>All concerns
                            </button>
                            <?php endif; ?>
                            <?php if (!empty($activeConcern['id'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="selectConcernFilter('<?= (int)$activeConcern['id'] ?>')">
                                    <i class="bi bi-crosshair me-1"></i>Active: <?= e($activeConcern['code']) ?>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none" onclick="clearConcernFilter()">
                                Clear
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($concerns)): ?>
                        <div class="row g-2" id="exportConcernOptions" role="group" aria-label="Business concern filters">
                            <?php foreach ($concerns as $concern): ?>
                                <?php $preCheck = empty($isAdminUser) && !empty($activeConcern['id']) && (int)$activeConcern['id'] === (int)$concern['id']; ?>
                                <div class="col-sm-6 col-xl-4">
                                    <label class="concern-filter-card d-flex align-items-start gap-3 border rounded-3 bg-white p-3 h-100" for="exportConcern<?= (int)$concern['id'] ?>">
                                        <input type="checkbox"
                                               class="form-check-input mt-1 concern-filter-checkbox"
                                               name="concern_ids[]"
                                               id="exportConcern<?= (int)$concern['id'] ?>"
                                               value="<?= (int)$concern['id'] ?>"
                                               data-code="<?= e($concern['code']) ?>"
                                               data-modules="<?= e($concern['enabled_modules'] ?? '') ?>"
                                               <?= $preCheck ? 'checked' : '' ?>
                                               onchange="handleConcernSelectionChange()">
                                        <span class="flex-grow-1">
                                            <span class="d-flex justify-content-between align-items-center gap-2">
                                                <span class="fw-semibold text-dark"><?= e($concern['name']) ?></span>
                                                <span class="badge bg-primary-subtle text-primary border"><?= e($concern['code']) ?></span>
                                            </span>
                                            <?php if (!empty($concern['legal_name'])): ?>
                                                <span class="d-block small text-muted mt-1"><?= e($concern['legal_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($concern['gst_number'])): ?>
                                                <span class="d-block small text-muted mt-1">GSTIN <?= e($concern['gst_number']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0 small">No business concerns are available to filter.</div>
                    <?php endif; ?>

                    <div id="concernFilterHelp" class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <span class="small text-muted">The selected concerns scope procurement, manufacturing, warehousing, and financial records.</span>
                        <span id="concernSelectionBadge" class="badge rounded-pill bg-primary-subtle text-primary border px-3 py-2" aria-live="polite">All Business Concerns (Consolidated)</span>
                    </div>
                </div>
            </div>
            <?php elseif (!empty($concerns)): ?>
                <?php foreach ($concerns as $onlyConcern): ?>
                    <input type="hidden" name="concern_ids[]" class="concern-filter-hidden" value="<?= (int)$onlyConcern['id'] ?>">
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- 1c. Destination Warehouse Filter -->
            <div class="mb-4 pb-3 border-bottom">
                <label class="form-label fw-bold text-dark mb-2 fs-6">
                    <i class="bi bi-building me-1 text-primary"></i>
                    Filter by Consignee / Destination Warehouse
                </label>
                <select name="warehouse_ids[]" id="exportWarehouseSelect" class="form-select" multiple size="4" aria-describedby="warehouseFilterHelp">
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= (int)$warehouse['id'] ?>" data-concern-id="<?= (int)($warehouse['concern_id'] ?? 0) ?>">
                            <?= e($warehouse['name']) ?><?= !empty($warehouse['code']) ? ' (' . e($warehouse['code']) . ')' : '' ?><?= !empty($warehouse['concern_code']) ? ' [' . e($warehouse['concern_code']) . ']' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="warehouseFilterEmpty" class="alert alert-warning d-none small mt-2 mb-0" role="status" aria-live="polite"></div>
                <div id="warehouseFilterHelp" class="form-text">
                    Select one or more warehouses to filter the outward dispatch and detailed stock ledger data. Leave unselected to include every warehouse.
                </div>
            </div>

            <!-- 2. Data Modules Selection -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <label class="form-label fw-bold text-dark mb-0 fs-6">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">2</span>
                        Choose Data Modules to Include
                    </label>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="form-check form-switch mb-0 me-2">
                            <input class="form-check-input cursor-pointer" type="checkbox" id="toggleOnlyWithData" onchange="applyExportModuleVisibility()">
                            <label class="form-check-label small fw-semibold cursor-pointer" for="toggleOnlyWithData">Only With Data</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 fw-semibold fs-7" onclick="selectEnabledConcernModules()" id="btnAutoConcernModules" title="Automatically check only modules authorized for the selected concern(s)">
                            <i class="bi bi-shield-check me-1"></i>Select Concern Modules
                        </button>
                        <span class="text-muted">|</span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold" onclick="toggleAllExportModules(true)">Select All</button>
                        <span class="text-muted">|</span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-secondary fw-semibold" onclick="toggleAllExportModules(false)">Clear</button>
                    </div>
                </div>

                <div class="row g-3">
                    <?php 
                    $categories = [];
                    foreach ($modules as $modKey => $modInfo) {
                        $cat = $modInfo['category'] ?? 'General';
                        $categories[$cat][$modKey] = $modInfo;
                    }
                    ?>
                    <?php foreach ($categories as $catName => $catModules): ?>
                        <div class="col-12 mt-3 export-cat-header" data-export-cat="<?= e($catName) ?>">
                            <span class="text-uppercase tracking-wider text-muted fw-bold small" style="letter-spacing: 0.05em; font-size: 11px;">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> <?= e($catName) ?>
                            </span>
                        </div>
                        <?php foreach ($catModules as $modKey => $mod): ?>
                            <div class="col-md-6 col-lg-4 export-mod-card" data-export-mod="<?= e($modKey) ?>" data-export-cat="<?= e($catName) ?>">
                                <div class="p-3 bg-light bg-opacity-50 rounded-3 border h-100 hover-shadow transition">
                                    <div class="form-check d-flex align-items-start gap-2">
                                        <input class="form-check-input export-mod-cb mt-1" type="checkbox" name="modules[]" value="<?= e($modKey) ?>" id="mod_<?= e($modKey) ?>" checked>
                                        <label class="form-check-label w-100 cursor-pointer" for="mod_<?= e($modKey) ?>">
                                            <div class="fw-bold text-dark d-flex align-items-center justify-content-between gap-1.5 mb-1">
                                                <span class="d-flex align-items-center gap-1.5">
                                                    <i class="<?= e($mod['icon']) ?> text-<?= e($mod['color']) ?>"></i>
                                                    <span><?= e($mod['name']) ?></span>
                                                </span>
                                                <span class="badge bg-secondary-subtle text-secondary border export-mod-count" data-mod="<?= e($modKey) ?>" style="font-size: 10px;">...</span>
                                            </div>
                                            <div class="text-muted small" style="font-size: 11.5px; line-height: 1.35;">
                                                <?= e($mod['desc']) ?>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Export Format Selection -->
            <div>
                <label class="form-label fw-bold text-dark mb-3 fs-6">
                    <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">3</span>
                    Choose Export Format
                </label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="card border p-3 rounded-3 cursor-pointer h-100 transition format-card active" for="formatCsv" style="border-width: 2px !important;">
                            <div class="form-check d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1" type="radio" name="format" id="formatCsv" value="CSV" checked onchange="highlightFormat(this)">
                                <div>
                                    <div class="fw-bold text-success fs-6 d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                                        <span>CSV Spreadsheet (Microsoft Excel Compatible)</span>
                                    </div>
                                    <div class="text-muted small">
                                        Exports raw dataset tables, executive summaries, numeric totals, and clean decimal numbers compatible with Microsoft Excel, Google Sheets, and ERP imports.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="card border p-3 rounded-3 cursor-pointer h-100 transition format-card" for="formatPrint" style="border-width: 2px !important;">
                            <div class="form-check d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1" type="radio" name="format" id="formatPrint" value="PRINT" onchange="highlightFormat(this)">
                                <div>
                                    <div class="fw-bold text-primary fs-6 d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-printer-fill fs-5"></i>
                                        <span>Printable Master Summary Report (HTML / PDF)</span>
                                    </div>
                                    <div class="text-muted small">
                                        Generates a formatted executive visual presentation with KPI cards, P&amp;L margin tables, operational yields, and ready-to-print PDF layout.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i> Data is retrieved with high-speed indexed range scans.
            </span>
            <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-download"></i>
                <span>Generate &amp; Download Export</span>
            </button>
        </div>
    </div>
</form>

<script>
const CONCERN_MODULE_MAPPING = {
    'purchases': ['purchases', 'rolls', 'raw_materials', 'third_party_products', 'qr_generations', 'suppliers', 'goods_inward'],
    'production': ['programs', 'cuttings', 'cutting_outwards', 'stitching', 'qc', 'damages', 'damage_outwards', 'catalog', 'variants', 'qr_generations'],
    'warehouse': ['batches', 'outward', 'inventory', 'stock_ledger', 'stock_requests'],
    'retail_pos': ['billing', 'direct_billing', 'coupons'],
    'digital_sales': ['billing', 'direct_billing', 'outward'],
    'returns': ['returns'],
    'expenses': ['expenses', 'wastage'],
    'assets': ['assets', 'asset_maintenance']
};
let latestExportCounts = {};

function togglePeriodInputs() {
    const isMonth = document.getElementById('periodTypeMonth').checked;
    const mRow = document.getElementById('monthPickerRow');
    const cRow = document.getElementById('customPickerRow');
    if (isMonth) {
        mRow.classList.remove('d-none');
        cRow.classList.add('d-none');
    } else {
        mRow.classList.add('d-none');
        cRow.classList.remove('d-none');
    }
    refreshExportModuleCounts();
}

function toggleAllExportModules(check) {
    document.querySelectorAll('.export-mod-cb').forEach(cb => cb.checked = check);
}

function highlightFormat(radio) {
    document.querySelectorAll('.format-card').forEach(c => c.classList.remove('active', 'border-primary', 'border-success'));
    if (radio.value === 'CSV') {
        radio.closest('.format-card').classList.add('active', 'border-success');
    } else {
        radio.closest('.format-card').classList.add('active', 'border-primary');
    }
}

function submitQuickPrint() {
    const form = document.getElementById('exportMainForm');
    const oldFormat = form.querySelector('input[name="format"]:checked').value;
    document.getElementById('formatPrint').checked = true;
    form.submit();
    setTimeout(() => {
        document.getElementById('format' + (oldFormat === 'CSV' ? 'Csv' : 'Print')).checked = true;
    }, 100);
}

function handleConcernSelectionChange() {
    const badge = document.getElementById('concernSelectionBadge');
    if (!badge) return;

    const concernCheckboxes = Array.from(document.querySelectorAll('.concern-filter-checkbox'));
    const selectedOptions = concernCheckboxes.filter(checkbox => checkbox.checked);
    const selectedValues = selectedOptions.map(checkbox => checkbox.value).filter(value => value !== '' && value !== '0');

    concernCheckboxes.forEach(checkbox => {
        const card = checkbox.closest('.concern-filter-card');
        if (card) {
            card.classList.toggle('border-primary', checkbox.checked);
            card.classList.toggle('bg-primary-subtle', checkbox.checked);
            card.classList.toggle('shadow-sm', checkbox.checked);
        }
    });

    if (selectedValues.length === 0) {
        badge.className = 'badge rounded-pill bg-primary-subtle text-primary border px-3 py-2';
        badge.textContent = 'All Business Concerns (Consolidated)';
        filterWarehousesByConcerns([]);
        const onlyDataEl = document.getElementById('toggleOnlyWithData');
        if (onlyDataEl) onlyDataEl.checked = false;
        refreshExportModuleCounts();
        return;
    }

    const codes = selectedOptions.map(option => option.dataset.code).filter(Boolean);
    badge.className = 'badge rounded-pill bg-success-subtle text-success border px-3 py-2';
    badge.textContent = selectedValues.length === 1 
        ? `Scoped Concern: ${codes[0] || '1 Selected'}` 
        : `Scoped: ${selectedValues.length} Concerns (${codes.join(', ')})`;

    filterWarehousesByConcerns(selectedValues);
    const onlyDataEl = document.getElementById('toggleOnlyWithData');
    if (onlyDataEl) onlyDataEl.checked = true;
    refreshExportModuleCounts();
}

function refreshExportModuleCounts() {
    const params = new URLSearchParams();
    const isMonth = document.getElementById('periodTypeMonth')?.checked;
    params.set('period_type', isMonth ? 'MONTH' : 'CUSTOM');
    if (isMonth) {
        params.set('selected_month', document.querySelector('[name="selected_month"]')?.value || '');
        params.set('selected_year', document.querySelector('[name="selected_year"]')?.value || '');
    } else {
        params.set('date_from', document.querySelector('[name="date_from"]')?.value || '');
        params.set('date_to', document.querySelector('[name="date_to"]')?.value || '');
    }
    document.querySelectorAll('.concern-filter-checkbox:checked, .concern-filter-hidden').forEach(cb => {
        if (cb.value) params.append('concern_ids[]', cb.value);
    });

    fetch(`<?= url('api/export/concern-summary') ?>?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            if (!res || !res.success || !res.counts) return;
            latestExportCounts = res.counts;
            document.querySelectorAll('.export-mod-count').forEach(badge => {
                const mod = badge.dataset.mod;
                const cnt = Number(latestExportCounts[mod] ?? 0);
                badge.textContent = cnt === 1 ? '1 record' : `${cnt.toLocaleString()} records`;
                badge.className = cnt > 0
                    ? 'badge bg-success-subtle text-success border border-success-subtle export-mod-count'
                    : 'badge bg-secondary-subtle text-secondary border export-mod-count';
            });
            applyExportModuleVisibility();
        })
        .catch(() => {});
}

function applyExportModuleVisibility() {
    const onlyWithData = Boolean(document.getElementById('toggleOnlyWithData')?.checked);
    const hasScopedConcern = document.querySelectorAll('.concern-filter-checkbox:checked, .concern-filter-hidden').length > 0;
    const catVisibleCount = {};

    document.querySelectorAll('.export-mod-card').forEach(card => {
        const mod = card.dataset.exportMod;
        const cat = card.dataset.exportCat || '';
        const cnt = Number(latestExportCounts[mod] ?? 0);
        const cb = card.querySelector('.export-mod-cb');
        const shouldHide = onlyWithData && Object.prototype.hasOwnProperty.call(latestExportCounts, mod) && cnt === 0;
        card.classList.toggle('d-none', shouldHide);
        if (hasScopedConcern && Object.prototype.hasOwnProperty.call(latestExportCounts, mod) && cb) {
            cb.checked = cnt > 0;
        }
        if (!shouldHide) {
            catVisibleCount[cat] = (catVisibleCount[cat] || 0) + 1;
        }
    });

    document.querySelectorAll('.export-cat-header').forEach(hdr => {
        const cat = hdr.dataset.exportCat || '';
        hdr.classList.toggle('d-none', (catVisibleCount[cat] || 0) === 0);
    });
}

function selectConcernFilter(val) {
    const concernCheckboxes = Array.from(document.querySelectorAll('.concern-filter-checkbox'));
    concernCheckboxes.forEach(checkbox => {
        checkbox.checked = val !== 'ALL' && checkbox.value === String(val);
    });
    handleConcernSelectionChange();
}

function clearConcernFilter() {
    selectConcernFilter('ALL');
}

function filterWarehousesByConcerns(concernIds) {
    const whSelect = document.getElementById('exportWarehouseSelect');
    if (!whSelect) return;

    const cIds = concernIds.map(id => parseInt(id, 10));
    const options = Array.from(whSelect.options);
    let visibleCount = 0;
    options.forEach(opt => {
        const whCid = parseInt(opt.dataset.concernId || 0, 10);
        if (cIds.length === 0 || cIds.includes(whCid) || whCid === 0) {
            opt.style.display = '';
            opt.disabled = false;
            visibleCount++;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
            opt.selected = false;
        }
    });

    const emptyState = document.getElementById('warehouseFilterEmpty');
    if (emptyState) {
        if (visibleCount > 0) {
            emptyState.classList.add('d-none');
            emptyState.textContent = '';
        } else {
            emptyState.classList.remove('d-none');
            emptyState.textContent = options.length === 0
                ? 'No active warehouses are available. Create or activate a warehouse to use this filter.'
                : 'No active warehouses are linked to the selected concern. Choose All concerns or assign a warehouse to the concern.';
        }
    }
}

function selectEnabledConcernModules() {
    const selectedOptions = Array.from(document.querySelectorAll('.concern-filter-checkbox:checked'))
        .filter(checkbox => checkbox.value !== '' && checkbox.value !== '0');
    if (selectedOptions.length === 0) {
        // If all concerns selected, check all
        toggleAllExportModules(true);
        return;
    }

    // Collect all enabled concern module keys
    const enabledConcernModules = new Set();
    selectedOptions.forEach(opt => {
        const raw = opt.dataset.modules || '';
        raw.split(',').map(s => s.trim()).filter(Boolean).forEach(m => enabledConcernModules.add(m));
    });

    // Determine target export modules
    const targetExportModules = new Set();
    enabledConcernModules.forEach(cMod => {
        const mapped = CONCERN_MODULE_MAPPING[cMod] || [];
        mapped.forEach(m => targetExportModules.add(m));
    });

    // Update checkboxes
    let checkedCount = 0;
    document.querySelectorAll('.export-mod-cb').forEach(cb => {
        if (targetExportModules.has(cb.value)) {
            cb.checked = true;
            checkedCount++;
        } else {
            cb.checked = false;
        }
    });

    const btn = document.getElementById('btnAutoConcernModules');
    if (btn) {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = `<i class="bi bi-check-lg me-1 text-success"></i>Selected (${checkedCount}) Modules`;
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2500);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const hasConcernCheckboxes = document.querySelectorAll('.concern-filter-checkbox').length > 0;
    if (hasConcernCheckboxes) {
        handleConcernSelectionChange();
    } else {
        const onlyDataEl = document.getElementById('toggleOnlyWithData');
        if (onlyDataEl) onlyDataEl.checked = true;
        refreshExportModuleCounts();
    }
});
</script>

<style>
.concern-filter-card {
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}

.concern-filter-card:hover,
.concern-filter-card:focus-within {
    border-color: var(--bs-primary) !important;
}
</style>
