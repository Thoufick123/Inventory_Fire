<?php
$purchaseType = $purchase['purchase_type'] ?? 'FABRIC_ROLL';
$fabricItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'FABRIC_ROLL'));
$rawMaterialItems = array_values(array_filter($items, static fn(array $item): bool => in_array($item['item_type'], ['RAW_MATERIAL', 'OWN_PRODUCT'], true)));
$thirdPartyItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'THIRD_PARTY'));
$fabricDescription = $fabricItems[0]['item_name'] ?? '';
$selectedSellerLabel = '';
foreach ($sellers as $s) {
    if ((int)$s['id'] === (int)($purchase['seller_id'] ?? 0)) {
        $selectedSellerLabel = ($s['company'] ?: $s['name']) . ($s['company'] ? ' — ' . $s['name'] : '');
        break;
    }
}
if ($selectedSellerLabel === '' && !empty($purchase['seller_name'])) {
    $selectedSellerLabel = ($purchase['seller_company'] ?: $purchase['seller_name']) . (!empty($purchase['seller_company']) ? ' — ' . $purchase['seller_name'] : '');
}
?>
<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Purchase — <?= e($purchase['purchase_number']) ?></h2>
    </div>
    <a href="<?= url('purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
</div>

<form method="POST" action="<?= url('production/purchases/update/' . $purchase['id']) ?>" enctype="multipart/form-data" id="editProductionPurchaseForm" novalidate>
    <?= CSRF::field() ?>
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Supplier Details</h5>
            <button type="button" class="btn btn-outline-secondary" id="btnTogglePurchaseDetailsCompact" onclick="togglePurchaseDetailsCompact()">
                <i class="bi bi-arrows-collapse me-1"></i>Compact
            </button>
        </div>
        <div class="card-body p-4" id="purchaseSupplierDetailsBody">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="purchaseSeller">Supplier / Vendor</label>
                    <select class="d-none" name="seller_id" id="purchaseSeller">
                        <option value="">-- Select registered supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>" <?= ((int)$seller['id'] === (int)($purchase['seller_id'] ?? 0)) ? 'selected' : '' ?>
                                data-supplier-name="<?= e($seller['name'] ?? '') ?>"
                                data-supplier-company="<?= e($seller['company'] ?? '') ?>"
                                data-supplier-phone="<?= e($seller['phone'] ?? '') ?>"
                                data-supplier-email="<?= e($seller['email'] ?? '') ?>"
                                data-supplier-gst="<?= e($seller['gst_number'] ?? '') ?>"
                                data-supplier-city="<?= e($seller['city'] ?? '') ?>"
                                data-supplier-state="<?= e($seller['state'] ?? '') ?>"
                                data-supplier-address="<?= e($seller['address'] ?? '') ?>"><?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Searchable Supplier Dropdown UI -->
                    <div class="dropdown w-100" id="supplierSearchDropdown">
                        <button type="button" class="form-select text-start d-flex justify-content-between align-items-center" id="supplierDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                            <span id="supplierDropdownSelectedText" class="text-truncate"><?= e($selectedSellerLabel ?: '-- Select registered supplier --') ?></span>
                        </button>
                        <div class="dropdown-menu shadow w-100 p-2" id="supplierDropdownMenu" style="max-height: 360px;">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="supplierSearchInput" placeholder="Search supplier by name, company, phone..." autocomplete="off">
                            </div>
                            <div class="overflow-auto" id="supplierListContainer" style="max-height: 250px;">
                                <button type="button" class="dropdown-item py-2 border-bottom supplier-opt-item <?= empty($purchase['seller_id']) ? 'active' : '' ?>" data-id="" data-display="-- Select registered supplier --">
                                    <span class="text-muted">-- Select registered supplier --</span>
                                </button>
                                <?php foreach ($sellers as $seller): ?>
                                    <button type="button" class="dropdown-item py-2 border-bottom supplier-opt-item <?= ((int)$seller['id'] === (int)($purchase['seller_id'] ?? 0)) ? 'active' : '' ?>"
                                            data-id="<?= (int)$seller['id'] ?>"
                                            data-display="<?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?>"
                                            data-search="<?= e(strtolower(($seller['company'] ?? '') . ' ' . ($seller['name'] ?? '') . ' ' . ($seller['phone'] ?? '') . ' ' . ($seller['city'] ?? '') . ' ' . ($seller['gst_number'] ?? ''))) ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark"><?= e($seller['company'] ?: $seller['name']) ?></strong>
                                            <?php if (!empty($seller['city'])): ?>
                                                <span class="badge bg-light text-secondary border"><?= e($seller['city']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted d-flex justify-content-between flex-wrap gap-1">
                                            <span><i class="bi bi-person me-1"></i><?= e($seller['name']) ?></span>
                                            <span><i class="bi bi-telephone me-1"></i><?= e($seller['phone']) ?></span>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center text-muted py-3 small d-none" id="supplierNoResults">
                                <i class="bi bi-inbox d-block fs-5 mb-1"></i> No suppliers match your search.
                            </div>
                        </div>
                    </div>
                    <?php if (empty($sellers)): ?>
                        <small class="text-danger d-block mt-1">
                            <i class="bi bi-exclamation-circle me-1"></i>No registered suppliers found for this business concern. 
                            <a href="<?= url('production/sellers/create') ?>" class="fw-bold text-primary text-decoration-underline">Add New Supplier</a> before recording a purchase.
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="purchaseDate">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date" id="purchaseDate" value="<?= e($purchase['purchase_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invoiceNumber">Invoice / Bill Number</label>
                    <input type="text" class="form-control" name="invoice_number" id="invoiceNumber" maxlength="100" value="<?= e($purchase['invoice_number'] ?? '') ?>">
                </div>
                <div class="col-md-6 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 fw-semibold" for="purchaseDocuments">
                            <i class="bi bi-receipt-cutoff me-1 text-primary"></i>Tax Invoice / Bill Attachments (Optional)
                        </label>
                        <div class="d-flex gap-1 align-items-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;">
                                <i class="bi bi-files me-1"></i>Multiple Supported (5+)
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;" title="All images are converted to AVIF format for high compression and quality">
                                <i class="bi bi-lightning-charge me-1"></i>Auto AVIF
                            </span>
                        </div>
                    </div>
                    <input type="file" class="form-control" name="documents[]" id="purchaseDocuments" accept=".jpg,.jpeg,.png,.webp,.avif,.pdf" multiple>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted" style="font-size:11px;">
                            Select 1 or multiple invoices (5+ supported). JPG, PNG, WEBP, AVIF or PDF up to 10 MB each. Images automatically convert to <strong>AVIF</strong> format.
                        </small>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none text-decoration-none" id="btnClearAllDocs" onclick="clearAllSelectedDocumentFiles()" style="font-size:11px;">
                            <i class="bi bi-trash3 me-1"></i>Clear All
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label fw-semibold mb-1" for="purchaseNotes">Purchase Notes</label>
                    <input type="text" class="form-control" name="notes" id="purchaseNotes" maxlength="500" value="<?= e($purchase['notes'] ?? '') ?>" placeholder="Optional notes regarding this purchase order or delivery...">
                </div>
                <div class="col-12 mt-2">
                    <?php if (!empty($documents)): ?>
                        <div class="mb-2 p-2 bg-light border rounded">
                            <div class="small fw-semibold text-secondary mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip me-1"></i>Attached Invoices (<?= count($documents) ?>)</span>
                                <span class="text-muted" style="font-size:10px;">Check box to remove upon saving</span>
                            </div>
                            <div class="row g-2">
                                <?php foreach ($documents as $doc):
                                    $docUrl = $doc['document_url'] ?? '';
                                    $isAvif = str_ends_with(strtolower($docUrl), '.avif') || strtolower($doc['file_type'] ?? '') === 'avif';
                                    $isPdf = str_ends_with(strtolower($docUrl), '.pdf') || strtolower($doc['file_type'] ?? '') === 'pdf';
                                    $isImg = $isAvif || preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $docUrl);
                                ?>
                                    <div class="col-sm-6 col-md-4 col-lg-3">
                                        <div class="card h-100 border shadow-xs bg-white">
                                            <div class="card-body p-2 d-flex flex-column justify-content-between" style="font-size:11px;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;overflow:hidden;">
                                                        <?php if ($isImg): ?>
                                                            <img src="<?= uploadUrl($docUrl) ?>" alt="Invoice" style="max-width:42px;max-height:42px;object-fit:cover;">
                                                        <?php elseif ($isPdf): ?>
                                                            <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-file-earmark-text text-secondary fs-4"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-truncate flex-grow-1">
                                                        <div class="fw-semibold text-truncate" title="<?= e($doc['file_name'] ?: 'Invoice') ?>"><?= e($doc['file_name'] ?: 'Invoice') ?></div>
                                                        <span class="badge <?= $isAvif ? 'bg-success-subtle text-success' : ($isPdf ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary') ?>" style="font-size:9px;">
                                                            <?= $isAvif ? 'AVIF' : ($isPdf ? 'PDF' : strtoupper($doc['file_type'] ?? 'FILE')) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                                    <a href="<?= uploadUrl($docUrl) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:10px;">
                                                        <i class="bi bi-eye me-1"></i>View
                                                    </a>
                                                    <?php if (!empty($doc['id'])): ?>
                                                    <label class="form-check-label text-danger small mb-0" style="font-size:10px;cursor:pointer;">
                                                        <input type="checkbox" name="delete_document_ids[]" value="<?= (int)$doc['id'] ?>" class="form-check-input me-1">Remove
                                                    </label>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Multi-document preview container -->
                    <div id="multiDocumentPreviewContainer" class="d-none p-2 bg-light border rounded">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                            <span class="small fw-bold text-dark" id="selectedDocCountLabel">
                                <i class="bi bi-paperclip me-1 text-primary"></i>0 invoices selected
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">
                                <i class="bi bi-check2-circle me-1"></i>AVIF Conversion Active
                            </span>
                        </div>
                        <div class="row g-2" id="docPreviewCardsRow">
                            <!-- Dynamically populated invoice preview cards -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . '/views/partials/purchase_mapping_workspace.php'; ?>
</form>

<script>
(() => {
    const form = document.getElementById('editProductionPurchaseForm');
    const supplierSelect = document.getElementById('purchaseSeller');

    window.togglePurchaseDetailsCompact = function() {
        const body = document.getElementById('purchaseSupplierDetailsBody');
        const btn = document.getElementById('btnTogglePurchaseDetailsCompact');
        if (!body || !btn) return;
        const collapsed = body.classList.toggle('d-none');
        btn.innerHTML = collapsed
            ? '<i class="bi bi-arrows-expand me-1"></i>Expand'
            : '<i class="bi bi-arrows-collapse me-1"></i>Compact';
    };

    const supplierDropdownBtn = document.getElementById('supplierDropdownBtn');
    const supplierDropdownSelectedText = document.getElementById('supplierDropdownSelectedText');
    const supplierSearchInput = document.getElementById('supplierSearchInput');
    const supplierOptItems = document.querySelectorAll('.supplier-opt-item');
    const supplierNoResults = document.getElementById('supplierNoResults');
    const supplierDropdown = document.getElementById('supplierSearchDropdown');

    if (supplierDropdown && supplierSearchInput) {
        supplierDropdown.addEventListener('shown.bs.dropdown', function () {
            supplierSearchInput.focus();
            supplierSearchInput.select();
        });

        supplierSearchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            supplierOptItems.forEach(item => {
                const searchData = item.dataset.search || '';
                const isDefault = item.dataset.id === '';
                if (isDefault) {
                    item.style.display = query === '' ? '' : 'none';
                    return;
                }
                if (query === '' || searchData.includes(query)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (supplierNoResults) {
                supplierNoResults.classList.toggle('d-none', visibleCount > 0 || query === '');
            }
        });

        supplierOptItems.forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id || '';
                const displayText = this.dataset.display || '-- Select registered supplier --';

                supplierOptItems.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');

                if (supplierDropdownSelectedText) {
                    supplierDropdownSelectedText.textContent = displayText;
                }

                supplierSelect.value = id;
                supplierSelect.dispatchEvent(new Event('change', { bubbles: true }));

                if (supplierDropdownBtn) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(supplierDropdownBtn) || new bootstrap.Dropdown(supplierDropdownBtn);
                    bsDropdown.hide();
                }
            });
        });
    }

    function resetPurchaseSubmitState() {
        form.removeAttribute('data-submitting');
        const submitBtn = document.getElementById('savePurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.pointerEvents = '';
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Purchase Changes';
        }
    }

    function lockPurchaseSubmitState() {
        form.dataset.submitting = 'true';
        const submitBtn = document.getElementById('savePurchaseSubmitBtn') || form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'none';
            setTimeout(() => {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
                }
            }, 10);
        }
    }

    form.addEventListener('submit', event => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return false;
        }
        let payloadInput = document.getElementById('itemsPayloadInput');
        if (!payloadInput) {
            payloadInput = document.createElement('input');
            payloadInput.type = 'hidden';
            payloadInput.name = 'items_payload';
            payloadInput.id = 'itemsPayloadInput';
            form.appendChild(payloadInput);
        }
        payloadInput.value = '[]';
        lockPurchaseSubmitState();
    });

    // Multiple Invoice Documents Management with AVIF Conversion indicator
    let selectedDocFiles = [];
    const docInput = document.getElementById('purchaseDocuments');
    const multiPreviewContainer = document.getElementById('multiDocumentPreviewContainer');
    const docPreviewCardsRow = document.getElementById('docPreviewCardsRow');
    const selectedDocCountLabel = document.getElementById('selectedDocCountLabel');
    const btnClearAllDocs = document.getElementById('btnClearAllDocs');

    function syncDocInputFiles() {
        if (!docInput) return;
        try {
            const dt = new DataTransfer();
            selectedDocFiles.forEach(file => dt.items.add(file));
            docInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer sync not supported:', e);
        }
    }

    function renderDocumentPreviews() {
        if (!docPreviewCardsRow || !multiPreviewContainer) return;
        docPreviewCardsRow.innerHTML = '';

        if (selectedDocFiles.length === 0) {
            multiPreviewContainer.classList.add('d-none');
            if (btnClearAllDocs) btnClearAllDocs.classList.add('d-none');
            return;
        }

        multiPreviewContainer.classList.remove('d-none');
        if (btnClearAllDocs) btnClearAllDocs.classList.remove('d-none');
        if (selectedDocCountLabel) {
            selectedDocCountLabel.innerHTML = `<i class="bi bi-paperclip me-1 text-primary"></i>${selectedDocFiles.length} invoice${selectedDocFiles.length > 1 ? 's' : ''} selected`;
        }

        selectedDocFiles.forEach((file, index) => {
            const ext = file.name.split('.').pop().toLowerCase();
            const isImg = ['jpg', 'jpeg', 'png', 'webp', 'avif'].includes(ext) || file.type.startsWith('image/');
            const isPdf = ext === 'pdf' || file.type === 'application/pdf';
            const sizeStr = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                : Math.max(1, Math.round(file.size / 1024)) + ' KB';

            const col = document.createElement('div');
            col.className = 'col-sm-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'card h-100 border shadow-xs bg-white';
            card.style.fontSize = '12px';

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2 d-flex flex-column justify-content-between';

            // Preview Thumbnail
            const thumbBox = document.createElement('div');
            thumbBox.className = 'd-flex align-items-center justify-content-center mb-1 rounded bg-light border';
            thumbBox.style.height = '75px';
            thumbBox.style.overflow = 'hidden';
            thumbBox.style.position = 'relative';

            if (isImg) {
                const img = document.createElement('img');
                img.className = 'img-fluid';
                img.style.maxHeight = '75px';
                img.style.objectFit = 'contain';
                const blobUrl = URL.createObjectURL(file);
                img.src = blobUrl;
                thumbBox.appendChild(img);
            } else if (isPdf) {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-pdf text-danger fs-2"></i>';
            } else {
                thumbBox.innerHTML = '<i class="bi bi-file-earmark-text text-secondary fs-2"></i>';
            }

            // Info
            const infoBox = document.createElement('div');
            infoBox.innerHTML = `
                <div class="fw-semibold text-truncate" title="${file.name}">${file.name}</div>
                <div class="d-flex align-items-center justify-content-between text-muted mt-1" style="font-size:10px;">
                    <span>${sizeStr}</span>
                    <span class="badge ${isImg ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'}" style="font-size:9px;">
                        ${isImg ? 'AVIF Auto' : 'PDF'}
                    </span>
                </div>
            `;

            // Actions
            const actionsBox = document.createElement('div');
            actionsBox.className = 'd-flex justify-content-between align-items-center mt-2 pt-1 border-top';

            const btnView = document.createElement('button');
            btnView.type = 'button';
            btnView.className = 'btn btn-xs btn-outline-primary py-0 px-2';
            btnView.style.fontSize = '11px';
            btnView.innerHTML = '<i class="bi bi-eye me-1"></i>View';
            btnView.onclick = function() {
                const url = URL.createObjectURL(file);
                window.open(url, '_blank', 'noopener,noreferrer');
            };

            const btnDel = document.createElement('button');
            btnDel.type = 'button';
            btnDel.className = 'btn btn-xs btn-outline-danger py-0 px-1';
            btnDel.title = 'Remove invoice';
            btnDel.innerHTML = '<i class="bi bi-trash"></i>';
            btnDel.onclick = function() {
                removeSelectedDocFile(index);
            };

            actionsBox.appendChild(btnView);
            actionsBox.appendChild(btnDel);

            cardBody.appendChild(thumbBox);
            cardBody.appendChild(infoBox);
            cardBody.appendChild(actionsBox);
            card.appendChild(cardBody);
            col.appendChild(card);
            docPreviewCardsRow.appendChild(col);
        });
    }

    if (docInput) {
        docInput.addEventListener('change', function() {
            if (docInput.files && docInput.files.length > 0) {
                Array.from(docInput.files).forEach(f => {
                    // Avoid duplicate additions with same name, size, lastModified
                    const exists = selectedDocFiles.some(existing => 
                        existing.name === f.name && existing.size === f.size && existing.lastModified === f.lastModified
                    );
                    if (!exists) {
                        selectedDocFiles.push(f);
                    }
                });
                syncDocInputFiles();
                renderDocumentPreviews();
            }
        });
    }

    window.removeSelectedDocFile = function(index) {
        if (index >= 0 && index < selectedDocFiles.length) {
            selectedDocFiles.splice(index, 1);
            syncDocInputFiles();
            renderDocumentPreviews();
        }
    };

    window.clearAllSelectedDocumentFiles = function() {
        selectedDocFiles = [];
        if (docInput) docInput.value = '';
        renderDocumentPreviews();
    };
})();
</script>
