<?php
// views/production/inventory/batch.php
// Stock Ledger Detail & Piece-by-piece QR Tracking matching multi-tab Warehouse Stock Ledger
$batchDate = new DateTime($batchInfo['batch_created_at'] ?? 'now');
$primarySkuDisplay = $primarySku ?? ($batchInfo['variant_code'] ?? ($batchInfo['product_name'] ?? 'Batch'));
$activeWhName = $activeWarehouse['name'] ?? ($batchInfo['warehouse_name'] ?? 'warehouse');
$totalPcs = count($pieceItems ?? []);
$inStockPcs = $remainingPieces ?? 0;
$soldPcs = $totalBilledPieces ?? 0;
$damagedList = $damagedPiecesList ?? [];
if (empty($damagedList) && !empty($qcOutcomeItems)) {
    foreach ($qcOutcomeItems as $qo) {
        $damagedList[] = [
            'tag_code'      => $qo['variant_code'] ?? 'QC-DEFECT',
            'variant_sku'   => $qo['variant_sku'] ?? '',
            'size_name'     => $qo['size_name'] ?? 'Std',
            'color_name'    => $qo['color_name'] ?? 'Std',
            'defect_reason' => ($qo['defect_reason'] ?? '') ?: ($qo['action_taken'] ?? 'QC Defect'),
            'created_at'    => $qo['batch_created_at'] ?? null,
            'status'        => ((int)($qo['rework_qty'] ?? 0) > 0) ? 'REWORK_PENDING' : 'QUARANTINED',
        ];
    }
}
$soldList = $soldPiecesList ?? [];
$returnsList = $returnPiecesList ?? [];
$movementsList = $movementLogs ?? ($outwards ?? []);
?>

<!-- QR Code Library CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #qrPrintModal, #qrPrintModal * {
        visibility: visible !important;
    }
    #qrPrintModal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }
    .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    .modal-header, .modal-footer, .no-print, .btn-close {
        display: none !important;
    }
    .modal-body {
        padding: 0 !important;
    }
    .qr-sticker-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border: 1px solid #000 !important;
        margin-bottom: 8px !important;
    }
}

/* Custom Tabs Styling matching Reference */
.stock-ledger-nav .nav-link {
    border-radius: 8px !important;
    padding: 0.6rem 1.1rem;
    font-size: 0.92rem;
    transition: all 0.2s ease-in-out;
}
.tab-pill-green {
    color: #198754;
    border: 1.5px solid rgba(25, 135, 84, 0.35);
    background: #f4fbf7;
}
.tab-pill-green.active, .tab-pill-green:hover {
    background-color: #198754 !important;
    color: #fff !important;
    border-color: #198754 !important;
}
.tab-pill-green.active .badge {
    background-color: #fff !important;
    color: #198754 !important;
}

.tab-pill-red {
    color: #dc3545;
    border: 1.5px solid rgba(220, 53, 69, 0.35);
    background: #fdf5f5;
}
.tab-pill-red.active, .tab-pill-red:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}
.tab-pill-red.active .badge {
    background-color: #fff !important;
    color: #dc3545 !important;
}

.tab-pill-pink {
    color: #d63384;
    border: 1.5px solid rgba(214, 51, 132, 0.35);
    background: #fdf5f9;
}
.tab-pill-pink.active, .tab-pill-pink:hover {
    background-color: #d63384 !important;
    color: #fff !important;
    border-color: #d63384 !important;
}
.tab-pill-pink.active .badge {
    background-color: #fff !important;
    color: #d63384 !important;
}

.tab-pill-orange {
    color: #fd7e14;
    border: 1.5px solid rgba(253, 126, 20, 0.35);
    background: #fef8f3;
}
.tab-pill-orange.active, .tab-pill-orange:hover {
    background-color: #fd7e14 !important;
    color: #fff !important;
    border-color: #fd7e14 !important;
}
.tab-pill-orange.active .badge {
    background-color: #fff !important;
    color: #fd7e14 !important;
}

.tab-pill-purple {
    color: #6f42c1;
    border: 1.5px solid rgba(111, 66, 193, 0.35);
    background: #f8f6fc;
}
.tab-pill-purple.active, .tab-pill-purple:hover {
    background-color: #6f42c1 !important;
    color: #fff !important;
    border-color: #6f42c1 !important;
}
.tab-pill-purple.active .badge {
    background-color: #fff !important;
    color: #6f42c1 !important;
}

/* Piece QR Sticker Card Styling */
.inventory-piece-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.inventory-piece-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08) !important;
}
.piece-qr-target {
    display: flex !important;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.piece-qr-target canvas {
    display: block !important;
    margin: 0 auto;
    max-width: 100%;
    height: auto;
}
/* QRCode.js creates both a <canvas> and a fallback <img>. Hide <img> so it doesn't show 2 QRs in the same box! */
.piece-qr-target img {
    display: none !important;
}
.qr-sticker-card canvas {
    display: block !important;
    margin: 0 auto;
}
.qr-sticker-card img {
    display: none !important;
}
.inventory-search-highlight {
    outline: 3px solid #f59e0b;
    outline-offset: -2px;
    background-color: #fffbeb !important;
}
</style>

<!-- Top Breadcrumbs matching Reference -->
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small fw-semibold">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('production/inventory') ?>" class="text-decoration-none text-muted">Warehouse</a></li>
            <li class="breadcrumb-item"><a href="<?= url('production/inventory') ?>" class="text-decoration-none text-muted">Stock Ledger</a></li>
            <li class="breadcrumb-item active font-monospace text-primary" aria-current="page"><?= e($primarySkuDisplay) ?></li>
        </ol>
    </nav>
</div>

<!-- Stock by Warehouse Location Banner matching Reference -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-graph-up-arrow text-primary fs-5"></i>
                <h5 class="fw-bold mb-0 text-dark">Stock by Warehouse Location</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('production/inventory') ?>" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <a href="<?= url('production/inventory') ?>" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold">View All</a>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <?php foreach (($locationCards ?? []) as $loc): ?>
            <div class="card border-2 <?= !empty($loc['is_active']) ? 'border-primary' : 'border-light-subtle' ?> shadow-sm" style="min-width: 280px; border-radius: 10px; background: <?= !empty($loc['is_active']) ? '#f6faff' : '#ffffff' ?>;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold fs-6 text-dark"><?= e($loc['name'] ?? $loc['warehouse_name'] ?? $loc['location_name'] ?? 'Main Warehouse') ?></span>
                        <?php if (!empty($loc['is_active'])): ?>
                            <span class="badge bg-primary rounded-pill px-2.5 py-1 small fw-semibold">Active View</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mt-2">
                        <h2 class="fw-bolder text-dark mb-0"><?= number_format($loc['available_qty'] ?? $inStockPcs) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h2>
                        <span class="badge bg-success text-white px-2.5 py-1 text-uppercase fw-bold rounded-1" style="font-size: 11px; letter-spacing: 0.5px;">AVAILABLE</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <a href="<?= url('production/inventory') ?>" class="text-primary text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-globe"></i> View All Warehouses Combined (<?= number_format($combinedTotalPieces ?? $inStockPcs) ?> pcs)
            </a>
            <span class="text-muted small">
                Program / Batch: <strong class="text-dark font-monospace"><?= e($batchInfo['program_number'] ?? $batchInfo['batch_number'] ?? 'Batch') ?></strong> &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i><?= $batchDate->format('d M Y') ?>
            </span>
        </div>
    </div>
</div>

        <!-- Warehouse Pieces & QR Labels -->
        <div id="pill-warehouse">

        <!-- Sub-header & Action Buttons matching Reference -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <h5 class="fw-bold mb-0 text-dark">Warehouse Pieces (Available &amp; Reserved)</h5>
                </div>
                <small class="text-muted d-block mt-1">Reserved allocation pieces remain visible until dispatch; dispatched pieces are removed from warehouse stock.</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-secondary fw-semibold shadow-sm" onclick="openPrintSettingsModal()">
                    <i class="bi bi-gear me-1.5"></i> Printer Settings
                </button>
                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="printAllBatchLabels()">
                    <i class="bi bi-printer-fill me-1.5"></i> Print Available Stickers (<?= number_format($inStockPcs) ?>)
                </button>
            </div>
        </div>

        <!-- Search & Scanner Row matching Reference -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
            <div class="card-body p-2.5">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-6 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="qrInventorySearch" class="form-control border-start-0 ps-1" 
                                   placeholder="Find product by piece tag code, Variant ID, size, color... (e.g. <?= $pieceItems[0]['tag_code'] ?? 'TAG' ?>)" 
                                   autofocus oninput="filterInventoryPieces()" onkeyup="if(event.key==='Enter') filterInventoryPieces()">
                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearInventorySearch()" title="Clear">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 text-center text-md-start">
                        <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold rounded-pill" id="pieceCountsBadge">
                            <?= $totalPcs ?> pieces &nbsp;·&nbsp; <strong class="text-success"><?= $inStockPcs ?> available</strong> &nbsp;·&nbsp; <?= $soldPcs ?> reserved/sold
                        </span>
                    </div>
                    <div class="col-lg-2 col-md-3 text-end d-flex align-items-center justify-content-end gap-2">
                        <span class="badge border border-warning-subtle text-warning-emphasis bg-warning bg-opacity-10 py-2 px-2.5 rounded-pill fw-semibold small">
                            <i class="bi bi-upc-scan me-1 text-warning"></i> Barcode scanner ready
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Mode Switch -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span class="text-muted small">
                Showing <strong id="visiblePieceCount"><?= count($rawItems ?? []) ?></strong> variant card(s) &middot; <strong class="text-success"><?= $inStockPcs ?> available pcs</strong>
            </span>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-primary active fw-semibold" id="btnCardView" onclick="switchView('card')">
                    <i class="bi bi-grid-3x3-gap me-1"></i> Variant Cards (<?= count($rawItems ?? []) ?>)
                </button>
            </div>
        </div>

        <!-- Cards Container (Default Grid matching Image 8) -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4" id="pieceCardsContainer" style="display: flex;"></div>

        <!-- List Container -->
        <div class="list-group list-group-flush gap-2 mb-4" id="pieceListContainer" style="display: none;"></div>

        <!-- Empty Search State -->
        <div class="text-center text-muted py-5" id="emptyPieceCardsSearchState" style="display: none;">
            <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
            <h6 class="fw-semibold mb-1">No Matching Garment Tag Found</h6>
            <p class="small mb-0">No piece in this batch matches the scanned or searched QR code.</p>
        </div>

        <!-- Group View (By Variant) -->
        <div id="groupViewContainer" style="display: none;">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variant ID</th>
                                <th>Size &amp; Color</th>
                                <th class="text-end">Passed Qty</th>
                                <th class="text-end">Base MRP</th>
                                <th class="text-end">Selling Price</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($rawItems ?? []) as $it): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-dark"><?= e($it['variant_code'] ?? ('V' . str_pad((string)($it['variant_id'] ?? 0), 4, '0', STR_PAD_LEFT))) ?></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border me-1"><?= e($it['size_name'] ?? 'Std') ?></span>
                                    <span class="badge bg-info-subtle text-info border"><?= e($it['color_name'] ?? 'Std') ?></span>
                                </td>
                                <td class="text-end fw-bold text-success"><?= number_format($it['passed_qty'] ?? $it['quantity'] ?? 0) ?> pcs</td>
                                <td class="text-end font-monospace text-muted"><?= formatCurrency((float)($it['base_price'] ?? $it['mrp'] ?? 0)) ?></td>
                                <td class="text-end font-monospace fw-bold text-primary"><?= formatCurrency((float)($it['discount_price'] ?? $it['selling_price'] ?? 0)) ?></td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle">IN STOCK</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="printVariantGroupLabels('<?= e($it['size_name'] ?? '') ?>', '<?= e($it['color_name'] ?? '') ?>')">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Hidden table container for list fallback -->
        <div class="table-responsive" id="pieceViewContainer" style="display: none;">
            <table class="table table-hover align-middle mb-0" id="pieceInventoryTable">
                <tbody>
                    <?php foreach ($pieceItems as $it): ?>
                    <tr class="inventory-piece-row" 
                        id="rowPiece_<?= $it['piece_no'] ?>"
                        data-piece-no="<?= $it['piece_no'] ?>"
                        data-tag="<?= strtoupper($it['tag_code']) ?>"
                        data-sku="<?= strtoupper($it['product_sku'] ?: ($it['variant_sku'] ?: '')) ?>"
                        data-size="<?= strtoupper($it['size_name'] ?? '') ?>"
                        data-color="<?= strtoupper($it['color_name'] ?? '') ?>"
                        data-product="<?= strtoupper($it['product_name'] ?? '') ?>">
                        <td>#<?= $it['piece_no'] ?></td>
                        <td><?= e($it['tag_code']) ?></td>
                        <td><?= e($it['product_name'] ?? '') ?></td>
                        <td><?= e($it['size_name'] ?? '') ?> / <?= e($it['color_name'] ?? '') ?></td>
                        <td>Batch</td>
                        <td><?= formatCurrency((float)($it['discount_price'] ?? 0)) ?></td>
                        <td><?= !empty($it['is_billed']) ? 'Sold' : 'Available' ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4 pt-3 border-top" id="piecePaginationFooter">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Items per page:</span>
                <select class="form-select form-select-sm" id="inventoryPageSizeSelect" style="width: 80px;" onchange="changeInventoryPageSize(this.value)">
                    <option value="6">6</option>
                    <option value="12" selected>12</option>
                    <option value="24">24</option>
                    <option value="all">All</option>
                </select>
                <span class="text-muted small ms-2" id="paginationRangeInfo"></span>
            </div>
            <ul class="pagination pagination-sm mb-0" id="piecePaginationNav"></ul>
        </div>
    </div>

<!-- Printer Settings Modal -->
<div class="modal fade" id="printerSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear me-2 text-primary"></i>Barcode &amp; QR Printer Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sticker Label Size</label>
                    <select class="form-select" id="stickerLabelSizeSelect">
                        <option value="50x25" selected>50mm × 25mm (Standard Barcode/QR)</option>
                        <option value="40x30">40mm × 30mm (Compact Tag)</option>
                        <option value="38x25">38mm × 25mm (Jewelry / Small Apparel)</option>
                        <option value="100x50">100mm × 50mm (Shipping Box / Carton)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Label Content Options</label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="showMrpCheck" checked>
                        <label class="form-check-label" for="showMrpCheck">Show Base MRP (Strikethrough)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="showDiscountPriceCheck" checked>
                        <label class="form-check-label" for="showDiscountPriceCheck">Show Selling / Discount Price</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="showCompanyTitleCheck" checked>
                        <label class="form-check-label" for="showCompanyTitleCheck">Show Warehouse / Concern Header</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary fw-semibold" data-bs-dismiss="modal">Save Preferences</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Stickers Print Modal -->
<div class="modal fade" id="qrPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-2.5 no-print">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-printer-fill me-2 text-success"></i>Print Product QR Stickers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom no-print">
                    <span class="text-muted small">Ready to print <strong id="printModalCount">0</strong> sticker label(s)</span>
                </div>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" id="qrStickersGrid"></div>
            </div>
            <div class="modal-footer bg-light border-top no-print py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success fw-bold px-4" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.inventoryPieceItems = <?= json_encode($pieceItems ?? []) ?>;
window.inventoryRawItems = <?= json_encode($rawItems ?? []) ?>;
const COMPANY_TITLE = <?= json_encode($activeWhName) ?>;
let currentInventoryPage = 1;
let inventoryPageSize = 12;
let matchingPieceElements = [];
let currentInventoryView = 'card';
const inventoryPieceCardsByNo = new Map();

document.addEventListener('DOMContentLoaded', function() {
    buildInventoryPieceCards();
    buildInventoryPieceList();
    filterInventoryPieces();
    switchView('card');
});

function openPrintSettingsModal() {
    const el = document.getElementById('printerSettingsModal');
    if (el && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
}

function switchView(mode) {
    const cardsContainer = document.getElementById('pieceCardsContainer');
    const listContainer = document.getElementById('pieceListContainer');
    const groupContainer = document.getElementById('groupViewContainer');
    const paginationFooter = document.getElementById('piecePaginationFooter');
    const btnCard = document.getElementById('btnCardView');
    const btnPiece = document.getElementById('btnPieceView');
    const btnGroup = document.getElementById('btnGroupView');
    currentInventoryView = mode;

    if (cardsContainer) cardsContainer.style.display = mode === 'card' ? 'flex' : 'none';
    if (listContainer) listContainer.style.display = mode === 'list' ? 'flex' : 'none';
    if (groupContainer) groupContainer.style.display = mode === 'group' ? 'block' : 'none';
    if (paginationFooter) paginationFooter.style.display = mode === 'group' ? 'none' : 'flex';

    [btnCard, btnPiece, btnGroup].forEach(b => {
        if (!b) return;
        const active = (mode === 'card' && b === btnCard)
            || (mode === 'list' && b === btnPiece)
            || (mode === 'group' && b === btnGroup);
        b.className = `btn ${active ? 'btn-primary active' : 'btn-outline-secondary'} fw-semibold`;
    });

    if (mode !== 'group') renderInventoryPagination();
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function buildInventoryPieceCards() {
    const grid = document.getElementById('pieceCardsContainer');
    if (!grid) return;
    grid.innerHTML = '';
    inventoryPieceCardsByNo.clear();

    const pieces = window.inventoryPieceItems || [];
    
    // Group pieces strictly by Variant: ONE VARIANT = EXACTLY ONE QR CODE CARD
    const variantMap = new Map();

    pieces.forEach(p => {
        const vKey = String(p.variant_id || p.variant_code || (p.tag_code ? p.tag_code.split('-')[0] : 'V0001'));
        if (!variantMap.has(vKey)) {
            const variantDisplayCode = p.variant_code || (p.variant_id ? ('V' + String(p.variant_id).padStart(4, '0')) : (p.tag_code ? p.tag_code.split('-')[0] : 'V0001'));
            variantMap.set(vKey, {
                variantKey: vKey,
                variantCode: variantDisplayCode,
                variantSku: variantDisplayCode,
                productName: p.product_name || 'Finished Product',
                categoryName: p.category_name || 'Apparel',
                sizeName: p.size_name || 'Std',
                colorName: p.color_name || 'Std',
                basePrice: parseFloat(p.base_price || 0),
                discountPrice: parseFloat(p.discount_price || p.base_price || 0),
                totalQty: 0,
                availableQty: 0,
                soldQty: 0,
                damagedQty: 0,
                pieces: []
            });
        }
        const vData = variantMap.get(vKey);
        vData.totalQty++;
        if (p.is_billed) {
            vData.soldQty++;
        } else if (p.is_damaged) {
            vData.damagedQty++;
        } else {
            vData.availableQty++;
        }
        vData.pieces.push(p);
    });

    // Fallback if pieces array is empty but rawItems exist
    if (variantMap.size === 0 && window.inventoryRawItems && window.inventoryRawItems.length > 0) {
        window.inventoryRawItems.forEach(it => {
            const vKey = String(it.variant_id || it.variant_code || 'V0001');
            const vCode = it.variant_code || (it.variant_id ? ('V' + String(it.variant_id).padStart(4, '0')) : 'V0001');
            const qty = parseInt(it.received_qty || it.dispatched_qty || it.quantity || 0, 10);
            variantMap.set(vKey, {
                variantKey: vKey,
                variantCode: vCode,
                variantSku: vCode,
                productName: it.product_name || 'Finished Product',
                categoryName: it.category_name || 'Apparel',
                sizeName: it.size_name || 'Std',
                colorName: it.color_name || 'Std',
                basePrice: parseFloat(it.variant_mrp || it.base_price || 0),
                discountPrice: parseFloat(it.variant_selling_price || it.discount_price || it.base_price || 0),
                totalQty: qty,
                availableQty: qty,
                soldQty: 0,
                damagedQty: 0,
                pieces: []
            });
        });
    }

    const totalVariants = variantMap.size;
    let vIndex = 1;

    variantMap.forEach((vData, vKey) => {
        const card = document.createElement('article');
        card.className = 'col inventory-piece-card';
        card.dataset.variantKey = vKey;
        card.dataset.variantCode = (vData.variantCode || '').toUpperCase();
        card.dataset.sku = (vData.variantSku || '').toUpperCase();
        card.dataset.size = (vData.sizeName || '').toUpperCase();
        card.dataset.color = (vData.colorName || '').toUpperCase();
        card.dataset.product = (vData.productName || '').toUpperCase();

        const mrpHtml = (vData.basePrice > vData.discountPrice)
            ? `<span class="text-muted text-decoration-line-through small me-1">MRP: ${formatCurrency(vData.basePrice)}</span>`
            : '';
        const priceHtml = `<span class="fw-bold text-primary">${formatCurrency(vData.discountPrice)}</span>`;

        const statusBadge = vData.availableQty > 0
            ? `<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold"><i class="bi bi-check-circle-fill me-1"></i>${vData.availableQty} pcs Available</span>`
            : `<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-bag-check me-1"></i>All Sold</span>`;

        card.innerHTML = `
            <div class="card h-100 border border-light-subtle shadow-sm" style="border-radius: 12px; overflow: hidden; background: #fff;">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="small fw-bold text-secondary">Variant #${vIndex} of ${totalVariants}</span>
                    ${statusBadge}
                </div>
                <div class="card-body p-3">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="flex-shrink-0 text-center piece-qr-target p-1 bg-white border rounded shadow-xs" id="card_variant_qr_${vIndex}" style="width: 86px; height: 86px;"></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bolder text-dark text-truncate text-uppercase" style="font-size: 13px; letter-spacing: 0.3px;">${escapeHtml(COMPANY_TITLE)}</div>
                            <div class="text-muted small text-truncate mb-1" style="font-size: 11px;">
                                <i class="bi bi-grid-fill text-warning me-1"></i>${escapeHtml(vData.categoryName)}
                            </div>
                            <div class="font-monospace fw-bold text-dark small text-truncate mb-0.5">${escapeHtml(vData.variantSku)}</div>
                            <div class="small text-muted mb-1" style="font-size: 11.5px;">
                                Size: <strong class="text-dark">${escapeHtml(vData.sizeName)}</strong> &middot; Color: <strong class="text-dark">${escapeHtml(vData.colorName)}</strong>
                            </div>
                            <div class="d-flex align-items-baseline gap-1 mb-1.5">
                                ${mrpHtml}
                                ${priceHtml}
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-1 pt-1 border-top">
                                <span class="badge bg-dark text-warning font-monospace px-2 py-1 shadow-xs" style="font-size: 11px; letter-spacing: 0.5px;">${escapeHtml(vData.variantCode)}</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5" onclick="copyTagCode('${escapeHtml(vData.variantCode)}', this)" title="Copy Variant QR Code">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1.5" onclick="printVariantGroupLabels('${escapeHtml(vData.sizeName)}', '${escapeHtml(vData.colorName)}')" title="Print Labels">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        grid.appendChild(card);
        inventoryPieceCardsByNo.set(String(vKey), card);

        const qrBox = card.querySelector('#card_variant_qr_' + vIndex);
        if (qrBox && typeof QRCode !== 'undefined') {
            qrBox.innerHTML = '';
            new QRCode(qrBox, {
                text: String(vData.variantCode),
                width: 76,
                height: 76,
                colorDark: "#111827",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            // Ensure QRCode.js duplicate fallback img is removed/hidden so only 1 QR displays
            const imgs = qrBox.querySelectorAll('img');
            imgs.forEach(im => im.style.setProperty('display', 'none', 'important'));
        }

        vIndex++;
    });
}

function buildInventoryPieceList() {
    const list = document.getElementById('pieceListContainer');
    if (!list) return;
    list.innerHTML = '';

    const pieces = window.inventoryPieceItems || [];
    pieces.forEach(p => {
        const variantDisplayCode = p.variant_code || (p.variant_id ? ('V' + String(p.variant_id).padStart(4, '0')) : (p.tag_code ? p.tag_code.split('-')[0] : 'V0001'));
        const item = document.createElement('article');
        item.className = 'list-group-item inventory-piece-list-item d-flex flex-wrap align-items-center justify-content-between gap-3 p-3';
        item.dataset.pieceNo = p.piece_no;
        item.dataset.variantCode = (variantDisplayCode || '').toUpperCase();
        item.dataset.tag = (p.tag_code || '').toUpperCase();
        item.dataset.sku = (p.variant_sku || p.product_sku || '').toUpperCase();
        item.dataset.size = (p.size_name || '').toUpperCase();
        item.dataset.color = (p.color_name || '').toUpperCase();
        item.dataset.product = (p.product_name || '').toUpperCase();

        const isBilled = Boolean(p.is_billed);
        const statusBadge = isBilled
            ? `<span class="badge bg-danger text-white">Sold</span>`
            : `<span class="badge bg-success text-white">Available</span>`;

        item.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary-subtle text-secondary">#${p.piece_no}</span>
                <span class="badge bg-dark text-warning font-monospace">${escapeHtml(variantDisplayCode)}</span>
                <div>
                    <span class="fw-bold text-dark font-monospace">${escapeHtml(p.variant_sku || p.product_sku || 'SKU')}</span>
                    <span class="text-muted small ms-2">${escapeHtml(p.size_name || 'Std')} / ${escapeHtml(p.color_name || 'Std')}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold text-primary font-monospace">${formatCurrency(parseFloat(p.discount_price || p.base_price || 0))}</span>
                ${statusBadge}
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="printSinglePieceLabel(${p.piece_no})">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        `;
        list.appendChild(item);
    });
}

function filterInventoryPieces() {
    const input = document.getElementById('qrInventorySearch');
    const rawVal = input ? input.value.trim().toUpperCase() : '';
    const cards = Array.from(document.querySelectorAll('.inventory-piece-card'));
    matchingPieceElements = [];

    cards.forEach(card => {
        const vcode = card.dataset.variantCode || '';
        const tag = card.dataset.tag || '';
        const sku = card.dataset.sku || '';
        const size = card.dataset.size || '';
        const color = card.dataset.color || '';
        const prod = card.dataset.product || '';

        const matched = !rawVal || vcode.includes(rawVal) || tag.includes(rawVal) || sku.includes(rawVal) || prod.includes(rawVal) || (size.includes(rawVal) && color.includes(rawVal));
        if (matched) {
            matchingPieceElements.push(card);
            card.classList.toggle('inventory-search-highlight', Boolean(rawVal) && (tag === rawVal || vcode === rawVal));
        }
    });

    const emptyCards = document.getElementById('emptyPieceCardsSearchState');
    if (emptyCards) emptyCards.style.display = matchingPieceElements.length === 0 ? 'block' : 'none';

    const visibleCountEl = document.getElementById('visiblePieceCount');
    if (visibleCountEl) visibleCountEl.textContent = matchingPieceElements.length;

    currentInventoryPage = 1;
    renderInventoryPagination();
}

function clearInventorySearch() {
    const input = document.getElementById('qrInventorySearch');
    if (input) input.value = '';
    filterInventoryPieces();
}

function changeInventoryPageSize(val) {
    inventoryPageSize = val === 'all' ? 'all' : parseInt(val, 10);
    currentInventoryPage = 1;
    renderInventoryPagination();
}

function renderInventoryPagination() {
    const totalMatching = matchingPieceElements.length;
    const isAll = (inventoryPageSize === 'all');
    const effectivePageSize = isAll ? totalMatching : parseInt(inventoryPageSize, 10);
    const totalPages = isAll ? 1 : Math.ceil(totalMatching / effectivePageSize) || 1;

    if (currentInventoryPage > totalPages) currentInventoryPage = totalPages;
    if (currentInventoryPage < 1) currentInventoryPage = 1;

    const startIndex = isAll ? 0 : (currentInventoryPage - 1) * effectivePageSize;
    const endIndex = isAll ? totalMatching : Math.min(startIndex + effectivePageSize, totalMatching);

    const allCards = document.querySelectorAll('.inventory-piece-card');
    allCards.forEach(c => c.style.display = 'none');

    matchingPieceElements.forEach((card, idx) => {
        if (idx >= startIndex && idx < endIndex) {
            card.style.display = '';
        }
    });

    const allListItems = document.querySelectorAll('.inventory-piece-list-item');
    allListItems.forEach((li, idx) => {
        li.style.display = (idx >= startIndex && idx < endIndex) ? 'flex' : 'none';
    });

    const rangeInfo = document.getElementById('paginationRangeInfo');
    if (rangeInfo) {
        rangeInfo.textContent = totalMatching > 0 ? `Showing ${startIndex + 1}–${endIndex} of ${totalMatching}` : '0 pieces';
    }

    const nav = document.getElementById('piecePaginationNav');
    if (!nav) return;
    nav.innerHTML = '';
    if (totalPages <= 1) return;

    // Prev
    nav.innerHTML += `
        <li class="page-item ${currentInventoryPage === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="goToPage(${currentInventoryPage - 1})">&laquo;</button>
        </li>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= currentInventoryPage - 1 && p <= currentInventoryPage + 1)) {
            nav.innerHTML += `
                <li class="page-item ${p === currentInventoryPage ? 'active' : ''}">
                    <button class="page-link" onclick="goToPage(${p})">${p}</button>
                </li>
            `;
        } else if (p === currentInventoryPage - 2 || p === currentInventoryPage + 2) {
            nav.innerHTML += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }
    }

    // Next
    nav.innerHTML += `
        <li class="page-item ${currentInventoryPage === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="goToPage(${currentInventoryPage + 1})">&raquo;</button>
        </li>
    `;
}

function goToPage(p) {
    currentInventoryPage = p;
    renderInventoryPagination();
}

function printSinglePieceLabel(pieceNo) {
    const piece = (window.inventoryPieceItems || []).find(p => p.piece_no == pieceNo);
    if (!piece) return alert('Piece not found');
    renderStickersToModal([piece]);
}

function printAllBatchLabels() {
    const available = (window.inventoryPieceItems || []).filter(p => !p.is_billed && !p.is_damaged);
    if (available.length === 0) return alert('No available pieces in stock to print.');
    renderStickersToModal(available);
}

function printVariantGroupLabels(size, color) {
    const matched = (window.inventoryPieceItems || []).filter(p => 
        (p.size_name || '').toUpperCase() === size.toUpperCase() && 
        (p.color_name || '').toUpperCase() === color.toUpperCase()
    );
    if (matched.length === 0) return alert('No pieces found for this variant.');
    renderStickersToModal(matched);
}

function renderStickersToModal(pieces) {
    const grid = document.getElementById('qrStickersGrid');
    const modalEl = document.getElementById('qrPrintModal');
    const countEl = document.getElementById('printModalCount');
    if (!grid || !modalEl) return;

    grid.innerHTML = '';
    if (countEl) countEl.textContent = pieces.length;

    pieces.forEach((p, idx) => {
        const variantDisplayCode = p.variant_code || (p.variant_id ? ('V' + String(p.variant_id).padStart(4, '0')) : (p.tag_code ? p.tag_code.split('-')[0] : 'V0001'));
        const basePrice = parseFloat(p.base_price || 0);
        const discPrice = parseFloat(p.discount_price || p.base_price || 0);
        const mrpHtml = (basePrice > discPrice)
            ? `<span class="text-muted text-decoration-line-through small me-1">${formatCurrency(basePrice)}</span>`
            : '';
        const col = document.createElement('div');
        col.className = 'col qr-sticker-card-col';
        col.innerHTML = `
            <div class="card qr-sticker-card p-3 border shadow-xs bg-white" style="border-radius: 10px;">
                <div class="d-flex gap-3 align-items-center">
                    <div class="flex-shrink-0 text-center p-1 bg-white border rounded" id="print_qr_${idx}" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center;"></div>
                    <div class="flex-grow-1 min-w-0 text-start">
                        <div class="fw-bolder text-dark text-truncate text-uppercase" style="font-size: 13px; letter-spacing: 0.3px;">${escapeHtml(COMPANY_TITLE)}</div>
                        <div class="text-muted small text-truncate mb-1" style="font-size: 11px;">
                            <i class="bi bi-grid-fill text-warning me-1"></i>${escapeHtml(p.category_name || 'Apparel')}
                        </div>
                        <div class="font-monospace fw-bold text-dark small text-truncate mb-0.5">${escapeHtml(variantDisplayCode)}</div>
                        <div class="small text-muted mb-1" style="font-size: 11.5px;">
                            Size: <strong class="text-dark">${escapeHtml(p.size_name || 'Std')}</strong> &middot; Color: <strong class="text-dark">${escapeHtml(p.color_name || 'Std')}</strong>
                        </div>
                        <div class="d-flex align-items-baseline gap-1 mb-1">
                            ${mrpHtml}
                            <span class="fw-bold text-primary">${formatCurrency(discPrice)}</span>
                        </div>
                        <div class="pt-1 border-top">
                            <span class="badge bg-dark text-warning font-monospace px-2 py-1" style="font-size: 11px; letter-spacing: 0.5px;">${escapeHtml(variantDisplayCode)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        grid.appendChild(col);

        const qrTarget = col.querySelector('#print_qr_' + idx);
        if (qrTarget && typeof QRCode !== 'undefined') {
            qrTarget.innerHTML = '';
            new QRCode(qrTarget, {
                text: String(variantDisplayCode),
                width: 74,
                height: 74,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
            const imgs = qrTarget.querySelectorAll('img');
            imgs.forEach(im => im.style.setProperty('display', 'none', 'important'));
        }
    });

    if (typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function copyTagCode(code, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(() => btn.innerHTML = original, 1500);
        });
    } else {
        alert('Copied tag: ' + code);
    }
}
</script>
