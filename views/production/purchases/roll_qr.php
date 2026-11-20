<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Thermal QR Labels') ?> — GRM Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --bs-body-bg: #f8fafc;
            --bs-body-color: #0f172a;
        }
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .roll-qr-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top Action Bar */
        .action-bar {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 16px 24px;
            margin-bottom: 24px;
        }

        /* Labels Grid */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .labels-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 850px) {
            .labels-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 550px) {
            .labels-grid {
                grid-template-columns: 1fr;
            }
        }

        /* 50x30mm Standard Garment Thermal Sticker Card (Unified Variant Style) */
        .thermal-label-card {
            background: #fff;
            border: 1.5px solid #000;
            border-radius: 6px;
            padding: 8px 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 155px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .thermal-label-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
        }

        .thermal-header {
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .company-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
            color: #000;
        }

        .category-sub {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-top: 1px;
        }

        .qr-section {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-box {
            width: 70px;
            height: 70px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .qr-box canvas, .qr-box img {
            width: 70px !important;
            height: 70px !important;
        }

        .meta-list {
            font-size: 9.5px;
            line-height: 1.35;
            min-width: 0;
            color: #1e293b;
        }

        .meta-list strong {
            font-weight: 800;
            color: #0f172a;
        }

        .product-name-row {
            font-size: 9.5px;
            font-weight: 800;
            font-family: monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .thermal-footer {
            border-top: 1px solid #000;
            margin-top: 5px;
            padding-top: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
            font-weight: 800;
        }

        .code-display {
            text-align: center;
            font-family: monospace;
            font-size: 8px;
            margin-top: 2px;
            letter-spacing: 0.5px;
            word-break: break-all;
            color: #334155;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .action-bar, .no-print, .roll-select-box, .btn, .d-print-none {
                display: none !important;
            }
            .roll-qr-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .labels-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 8px !important;
            }
            .thermal-label-card {
                border: 1.5px solid #000 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

<div class="roll-qr-container">
    <?php
    $canManageRolls = hasPermission('production_purchases', 'create') 
        || hasPermission('production_purchases', 'edit') 
        || hasPermission('sellers', 'create') 
        || hasPermission('sellers', 'edit');

    $pType = $purchase['purchase_type'] ?? 'FABRIC_ROLL';
    $pTypeLabel = $pType === 'RAW_MATERIAL'
        ? 'Raw Materials'
        : ($pType === 'THIRD_PARTY' ? 'Third-Party Products' : ($pType === 'FABRIC_ROLL' ? 'Fabric Roll' : 'Purchase Items'));
    ?>

    <!-- Top Action & Info Bar (Hidden in Print) -->
    <div class="action-bar no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark fs-6 px-2.5 py-1 text-white border"><i class="bi bi-qr-code text-warning me-1"></i> Thermal QR Labels</span>
                    <h3 class="mb-0 fw-bold fs-4 text-dark"><?= htmlspecialchars($purchase['purchase_number']) ?></h3>
                    <span class="badge bg-primary-subtle text-primary border"><?= e($pTypeLabel) ?></span>
                </div>
                <div class="text-muted small">
                    <strong>Supplier:</strong> <?= htmlspecialchars(($purchase['supplier_company'] ?? null) ?: (($purchase['seller_company'] ?? null) ?: (($purchase['supplier_name'] ?? null) ?: ($purchase['seller_name'] ?? 'Direct Supplier')))) ?>
                    · <strong>PO Date:</strong> <?= date('d M Y', strtotime($purchase['purchase_date'])) ?>
                    · <strong>Invoice:</strong> <?= htmlspecialchars($purchase['invoice_number'] ?: 'N/A') ?>
                    · <strong>Total Labels:</strong> <span class="badge bg-success-subtle text-success border border-success"><?= count($stickers) ?> Labels</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Purchases
                </a>
                <div class="form-check form-switch d-inline-flex align-items-center gap-2 mb-0 bg-light border px-2 py-1 rounded">
                    <input class="form-check-input ms-0" type="checkbox" id="selectAllRolls" <?= $canManageRolls ? 'checked onchange="toggleSelectAllRolls(this.checked)" style="cursor: pointer;"' : 'disabled style="cursor: not-allowed; opacity: 0.5;"' ?>>
                    <label class="form-check-label small fw-bold text-dark <?= $canManageRolls ? 'cursor-pointer' : '' ?> mb-0" for="selectAllRolls">Select All (<span id="selectedCount"><?= count($stickers) ?></span>/<?= count($stickers) ?>)<?= !$canManageRolls ? ' <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5 ms-1" style="font-size:10px;">View Only</span>' : '' ?></label>
                </div>
                <button type="button" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm" id="printSelectedBtn" <?= $canManageRolls ? 'onclick="printSelectedLabels()"' : 'disabled style="opacity: 0.5; cursor: not-allowed;"' ?>>
                    <i class="bi bi-printer me-1 text-warning"></i> Print Selected QR Labels (<span id="btnSelectedCount"><?= count($stickers) ?></span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <?php if (empty($stickers)): ?>
        <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-3">
            <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-3"></i>
            <h4 class="fw-bold text-dark">No Items In This Purchase</h4>
            <p class="text-muted">No inwarded items found to generate QR labels.</p>
            <div class="mt-3 d-flex justify-content-center gap-2">
                <a href="<?= url('production/purchases/edit/' . $purchase['id']) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Edit Purchase &amp; Add Items
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="mb-2 d-flex justify-content-between align-items-center no-print">
            <span class="text-muted small fw-semibold">
                <i class="bi bi-layers me-1 text-primary"></i> Displaying <strong><?= count($stickers) ?></strong> unified thermal QR label stickers (50x30mm standard):
            </span>
            <span class="badge bg-secondary-subtle text-secondary border">Standard Garment Thermal Layout</span>
        </div>

        <!-- Unified Labels Grid -->
        <div class="labels-grid" id="stickersContainer">
            <?php foreach ($stickers as $stk): ?>
                <div class="thermal-label-card" id="card-<?= $stk['index'] ?>">
                    <!-- Top Brand Header: Given Brand Name Print -->
                    <div class="thermal-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <input type="checkbox" class="form-check-input roll-select-box no-print me-1" id="check-roll-<?= $stk['index'] ?>" data-index="<?= $stk['index'] ?>" checked <?= $canManageRolls ? 'onchange="updateSelectedCount()"' : 'disabled' ?>>
                            <div class="company-title flex-grow-1 text-center"><?= e($stk['brand_title']) ?></div>
                            <div class="no-print" style="width: 16px;"></div>
                        </div>
                        <div class="category-sub text-center">
                            <?= e($stk['category']) ?>
                        </div>
                    </div>

                    <!-- Middle: QR and Attributes -->
                    <div class="qr-section">
                        <div class="qr-box sticker-qr-item" data-code="<?= e($stk['qr_payload']) ?>"></div>
                        <div class="meta-list flex-grow-1">
                            <div class="product-name-row" title="<?= e($stk['item_name']) ?>">
                                <?= e($stk['item_name']) ?>
                            </div>
                            <div><strong>V-ID:</strong> <span class="font-monospace fw-bold text-dark"><?= e($stk['v_id']) ?></span></div>
                            <div><strong>SKU:</strong> <span class="font-monospace fw-bold text-dark" style="font-size: 8.5px;"><?= e($stk['sku']) ?></span></div>
                            <?php if (($stk['item_type'] ?? '') === 'FABRIC_ROLL'): ?>
                                <?php if (!empty($stk['color']) && $stk['color'] !== '—'): ?>
                                <div><strong>Col:</strong> <?= e($stk['color']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($stk['weight']) && (float)$stk['weight'] > 0): ?>
                                <div><strong>Weight:</strong> <?= number_format((float)$stk['weight'], 2) ?> kg</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer: Pricing -->
                    <div class="thermal-footer">
                        <?php if ((float)$stk['mrp'] > 0): ?>
                            <div class="text-muted">MRP: ₹<?= number_format((float)$stk['mrp'], 2) ?></div>
                        <?php else: ?>
                            <div class="text-muted" style="font-size: 8px;">PO: <?= e($stk['po_number']) ?></div>
                        <?php endif; ?>
                        <div class="text-dark">PRICE: ₹<?= number_format((float)$stk['price'], 2) ?></div>
                    </div>

                    <!-- Sub-footer: Scannable string -->
                    <div class="code-display">
                        <?= e($stk['qr_payload']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.sticker-qr-item').forEach(function(el) {
            const code = el.getAttribute('data-code');
            if (code) {
                new QRCode(el, {
                    text: code,
                    width: 70,
                    height: 70,
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        });
        updateSelectedCount();
    });

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.roll-select-box');
        const checkedBoxes = document.querySelectorAll('.roll-select-box:checked');
        const total = checkboxes.length;
        const count = checkedBoxes.length;

        const selCountSpan = document.getElementById('selectedCount');
        const btnCountSpan = document.getElementById('btnSelectedCount');
        const selectAllSwitch = document.getElementById('selectAllRolls');

        if (selCountSpan) selCountSpan.textContent = count;
        if (btnCountSpan) btnCountSpan.textContent = count;

        if (selectAllSwitch && total > 0) {
            selectAllSwitch.checked = (count === total);
            selectAllSwitch.indeterminate = (count > 0 && count < total);
        }

        checkboxes.forEach(cb => {
            const card = document.getElementById('card-' + cb.getAttribute('data-index'));
            if (card) {
                if (cb.checked) {
                    card.classList.remove('d-print-none', 'opacity-50');
                } else {
                    card.classList.add('d-print-none', 'opacity-50');
                }
            }
        });
    }

    function toggleSelectAllRolls(checked) {
        document.querySelectorAll('.roll-select-box').forEach(cb => {
            cb.checked = checked;
        });
        updateSelectedCount();
    }

    function printSelectedLabels() {
        const count = document.querySelectorAll('.roll-select-box:checked').length;
        if (count === 0) {
            alert('Please select at least one label to print.');
            return;
        }
        window.print();
    }
</script>
</body>
</html>
