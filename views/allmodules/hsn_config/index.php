<?php
/**
 * Production HSN Config – Index View
 */
?>
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-0 fw-semibold"><i class="bi bi-upc-scan me-2 text-primary"></i>HSN Config</h4>
            <small class="text-muted">Manage Production HSN Codes &amp; GST Percentages</small>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="POST" action="<?= url('allmodules/hsn-config/seed-master') ?>" class="d-inline">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-database-add me-1"></i> Feed Standard HSN Master
                </button>
            </form>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImportHsnPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> Import HSN PDF
            </button>
            <a href="<?= url('production/hsn-config/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add HSN
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>HSN Code</th>
                            <th>GST %</th>
                            <th>Categories</th>
                            <th>Subcategories</th>
                            <th>Sub-subcategories</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold font-monospace"><?= htmlspecialchars($item['hsn_code']) ?></td>
                            <td><?= number_format((float)$item['gst_percentage'], 2) ?>%</td>
                            <td><?= htmlspecialchars($item['categories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['subcategories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['sub_subcategories'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($item['product'] ?? '—') ?></td>
                            <td>
                                <?php if ($item['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('production/hsn-config/view/' . $item['id']) ?>"
                                   class="btn btn-sm btn-outline-info me-1" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('production/hsn-config/edit/' . $item['id']) ?>"
                                   class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      action="<?= url('production/hsn-config/delete/' . $item['id']) ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete HSN Code <?= htmlspecialchars(addslashes($item['hsn_code'])) ?>? This cannot be undone.');">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3 mb-1">No HSN codes configured yet.</p>
                <a href="<?= url('production/hsn-config/create') ?>" class="btn btn-primary btn-sm mt-2">
                    <i class="bi bi-plus-lg me-1"></i> Add First HSN Code
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import HSN PDF Modal -->
<div class="modal fade" id="modalImportHsnPdf" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-pdf me-2"></i>Import HSN, Description &amp; GST from PDF</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-7">
                        <label for="hsnPdfFileInput" class="form-label fw-semibold">Select HSN / GST PDF File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="hsnPdfFileInput" accept=".pdf,application/pdf">
                        <small class="text-muted">Extracts HSN codes (4–8 digits), descriptions, and GST % directly in your browser for review before saving.</small>
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <button type="button" class="btn btn-primary fw-semibold" id="btnParseHsnPdf">
                            <i class="bi bi-magic me-1"></i> Extract Data from PDF
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnAddManualImportRow">
                            <i class="bi bi-plus-lg me-1"></i> Add Row
                        </button>
                    </div>
                </div>

                <div id="hsnPdfStatus" class="alert alert-info d-none small py-2"></div>

                <div class="table-responsive border rounded" style="max-height: 400px;">
                    <table class="table table-sm table-hover align-middle mb-0" id="tableHsnPdfPreview">
                        <thead class="table-light sticky-top small text-uppercase text-muted">
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th style="width: 130px;">HSN Code *</th>
                                <th>Description</th>
                                <th style="width: 180px;">Category</th>
                                <th style="width: 110px;">GST %</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHsnPdfPreview">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4 small">Upload a PDF and click <strong>Extract Data from PDF</strong> to preview rows here.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                <span class="small text-muted" id="hsnPdfRowCount">0 row(s) ready</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success fw-semibold" id="btnSaveImportedHsn" disabled>
                        <i class="bi bi-cloud-arrow-up me-1"></i> Save to Database
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    const fileInput = document.getElementById('hsnPdfFileInput');
    const btnParse = document.getElementById('btnParseHsnPdf');
    const btnAddRow = document.getElementById('btnAddManualImportRow');
    const btnSave = document.getElementById('btnSaveImportedHsn');
    const statusEl = document.getElementById('hsnPdfStatus');
    const tbody = document.getElementById('tbodyHsnPdfPreview');
    const countEl = document.getElementById('hsnPdfRowCount');

    function setStatus(msg, type = 'info') {
        if (!statusEl) return;
        statusEl.className = 'alert alert-' + type + ' small py-2';
        statusEl.textContent = msg;
        statusEl.classList.remove('d-none');
    }

    function updateCount() {
        const rows = tbody.querySelectorAll('tr.hsn-import-row');
        rows.forEach((tr, i) => {
            const num = tr.querySelector('.imp-num');
            if (num) num.textContent = String(i + 1);
        });
        countEl.textContent = rows.length + ' row(s) ready';
        btnSave.disabled = rows.length === 0;
    }

    function addPreviewRow(hsn = '', desc = '', cat = '', gst = '') {
        if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
        const tr = document.createElement('tr');
        tr.className = 'hsn-import-row';
        tr.innerHTML = `
            <td class="text-muted small imp-num"></td>
            <td><input type="text" class="form-control form-control-sm font-monospace imp-hsn" value="${String(hsn).replace(/"/g, '&quot;')}" maxlength="8" placeholder="6109"></td>
            <td><input type="text" class="form-control form-control-sm imp-desc" value="${String(desc).replace(/"/g, '&quot;')}" placeholder="Description"></td>
            <td><input type="text" class="form-control form-control-sm imp-cat" value="${String(cat).replace(/"/g, '&quot;')}" placeholder="Category (optional)"></td>
            <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-end imp-gst" value="${gst !== null && gst !== undefined ? String(gst) : ''}" placeholder="5"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-imp"><i class="bi bi-x-lg"></i></button></td>
        `;
        tbody.appendChild(tr);
        updateCount();
    }

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-imp');
        if (!btn) return;
        btn.closest('tr').remove();
        updateCount();
    });

    if (btnAddRow) {
        btnAddRow.addEventListener('click', () => addPreviewRow('', '', '', '5'));
    }

    function parseHsnLines(lines) {
        const results = [];
        const seen = new Set();
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].replace(/\s+/g, ' ').trim();
            if (!line) continue;
            // Match lines containing a 4-8 digit HSN/SAC code at start or after serial no.
            const m = line.match(/^(?:\d{1,3}[\.\)\s]+)?(\d{4,8})\b(.*)$/);
            if (!m) continue;
            const code = m[1];
            let rest = (m[2] || '').trim();

            // Extract GST % (e.g. "5%", "12 %", "18.00%", or trailing number 0/3/5/12/18/28)
            let gst = '';
            const pctMatch = rest.match(/(\d{1,2}(?:\.\d{1,2})?)\s*%/);
            if (pctMatch) {
                gst = pctMatch[1];
                rest = rest.replace(pctMatch[0], ' ').trim();
            } else {
                const trailMatch = rest.match(/\b(0|3|5|12|18|28)(?:\.00?)?\s*$/);
                if (trailMatch) {
                    gst = trailMatch[1];
                    rest = rest.slice(0, trailMatch.index).trim();
                }
            }

            // Clean separators
            rest = rest.replace(/^[\-\:\|\s]+|[\-\:\|\s]+$/g, '').trim();
            const dedupeKey = code + '|' + gst;
            if (seen.has(dedupeKey)) continue;
            seen.add(dedupeKey);
            results.push({ hsn_code: code, description: rest, category: '', gst_rate: gst });
        }
        return results;
    }

    if (btnParse) {
        btnParse.addEventListener('click', async function () {
            const file = fileInput?.files?.[0];
            if (!file) {
                setStatus('Please select a PDF file first.', 'warning');
                return;
            }
            if (!window.pdfjsLib) {
                setStatus('PDF parser library failed to load. Check internet connection.', 'danger');
                return;
            }
            btnParse.disabled = true;
            setStatus('Reading PDF pages and extracting HSN / GST rows...', 'info');
            try {
                const buf = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
                const allLines = [];
                for (let p = 1; p <= pdf.numPages; p++) {
                    const page = await pdf.getPage(p);
                    const content = await page.getTextContent();
                    // Group text items by Y coordinate into visual lines
                    const byY = new Map();
                    for (const item of content.items) {
                        const y = Math.round(item.transform[5] / 3) * 3;
                        if (!byY.has(y)) byY.set(y, []);
                        byY.get(y).push({ x: item.transform[4], str: item.str });
                    }
                    const ys = Array.from(byY.keys()).sort((a, b) => b - a);
                    for (const y of ys) {
                        const parts = byY.get(y).sort((a, b) => a.x - b.x).map(it => it.str);
                        allLines.push(parts.join(' '));
                    }
                }
                const extracted = parseHsnLines(allLines);
                tbody.innerHTML = '';
                if (extracted.length === 0) {
                    setStatus('No HSN rows matched automatically. You can add rows manually below.', 'warning');
                    updateCount();
                } else {
                    extracted.forEach(r => addPreviewRow(r.hsn_code, r.description, r.category, r.gst_rate));
                    setStatus(`Extracted ${extracted.length} HSN row(s) from ${pdf.numPages} PDF page(s). Review and click Save to Database.`, 'success');
                }
            } catch (err) {
                setStatus('PDF extraction error: ' + err.message, 'danger');
            } finally {
                btnParse.disabled = false;
            }
        });
    }

    if (btnSave) {
        btnSave.addEventListener('click', async function () {
            const rows = [];
            tbody.querySelectorAll('tr.hsn-import-row').forEach(tr => {
                const hsn_code = tr.querySelector('.imp-hsn')?.value.trim() || '';
                const description = tr.querySelector('.imp-desc')?.value.trim() || '';
                const category = tr.querySelector('.imp-cat')?.value.trim() || '';
                const gst_rate = tr.querySelector('.imp-gst')?.value.trim() || '';
                if (hsn_code) rows.push({ hsn_code, description, category, gst_rate });
            });
            if (!rows.length) {
                setStatus('Add at least one valid HSN code before saving.', 'warning');
                return;
            }
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
            try {
                const res = await fetch('<?= url('allmodules/hsn-config/import') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?= CSRF::token() ?>'
                    },
                    body: JSON.stringify({ rows: rows, csrf_token: '<?= CSRF::token() ?>' })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Import failed.');
                window.location.reload();
            } catch (err) {
                setStatus('Save error: ' + err.message, 'danger');
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Save to Database';
            }
        });
    }
});
</script>
