<?php
$isEdit = !empty($bill);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$existingItems = array_map(static function (array $item): array {
    return [
        'qc_item_id' => (int)$item['qc_item_id'],
        'variant_id' => (int)($item['variant_id'] ?? 0),
        'product_name' => (string)$item['product_name'],
        'sku' => (string)($item['sku'] ?? ''),
        'hsn_code' => (string)($item['hsn_code'] ?? '6109'),
        'size_name' => (string)($item['size_name'] ?? ''),
        'color_name' => (string)($item['color_name'] ?? ''),
        'mrp' => (float)$item['mrp'],
        'unit_price' => (float)$item['unit_price'],
        'is_complementary' => (int)($item['is_complementary'] ?? 0),
        'gst_percentage' => (float)$item['gst_percentage'],
        'gst_type' => (string)($item['gst_type'] ?? ((float)$item['gst_percentage'] > 0 ? 'BOTH' : 'EXEMPTED')),
        'tax_input_type' => (string)($item['tax_input_type'] ?? 'PERCENT'),
        'sgst_rate' => (float)($item['sgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'cgst_rate' => (float)($item['cgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'igst_rate' => (float)($item['igst_rate'] ?? 0),
        'tags' => $item['tags'],
        'available_tags' => $item['tags'],
    ];
}, $billItems ?? []);
$selectedBuyerType = $bill['buyer_type'] ?? 'CUSTOMER';
$usesUpiPayment = in_array($bill['payment_method'] ?? 'CASH', ['UPI', 'CASH_UPI'], true);
$usesSplitPayment = ($bill['payment_method'] ?? '') === 'CASH_UPI';
$concernBrand = function_exists('getConcernBranding') 
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit ?? null) 
    : [];
$activeLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : '');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <img id="productionUnitLogo" src="<?= $escape($activeLogo) ?>" alt="<?= $escape($concernBrand['name'] ?? ($prodUnit['name'] ?? 'Production unit')) ?> logo" class="mb-2 <?= empty($activeLogo) ? 'd-none' : '' ?>" style="max-width:180px;max-height:64px;object-fit:contain;">
        <h4 class="mb-1 fw-bold"><?= $isEdit ? 'Edit Production POS Bill' : 'Production POS Billing' ?></h4>
        <div class="text-muted small">Scan QR labels from available inventory, auto-fetch customer history, and record staff attribution.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" enctype="multipart/form-data" action="<?= url($isEdit ? 'production/billing/update/' . (int)$bill['id'] : 'production/billing/store') ?>" id="productionBillForm">
    <?= CSRF::input() ?>
    <div class="row g-3">
        <!-- LEFT COLUMN: Staff, Customer & Invoice Settings -->
        <div class="col-xl-4">
            <!-- 1. Billed By Staff (Cashier / Salesperson) -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <label class="form-label fw-bold text-dark mb-1" for="staffSelect">
                        <i class="bi bi-person-badge-fill text-primary me-1"></i>Billed by (Staff / Cashier) <span class="text-danger">*</span>
                    </label>
                    <select class="form-select fw-semibold" name="staff_id" id="staffSelect" required onchange="updateStaffName(this)">
                        <?php foreach ($staffUsers ?? [] as $st): ?>
                            <?php $selectedStaff = (int)($bill['staff_id'] ?? $currentUserId ?? 0) === (int)$st['id']; ?>
                            <option value="<?= (int)$st['id'] ?>" <?= $selectedStaff ? 'selected' : '' ?> data-name="<?= $escape($st['full_name']) ?>">
                                <?= $escape($st['full_name']) ?> (<?= $escape($st['staff_role'] ?: $st['department'] ?: $st['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="staff_name" id="staffNameInput" value="<?= $escape($bill['staff_name'] ?? (Auth::user()['full_name'] ?? '')) ?>">
                </div>
            </div>

            <!-- 2. Issuing Concern & Production Facility -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between flex-wrap gap-1 py-3">
                    <span><i class="bi bi-building-gear me-2 text-primary"></i>Issuing Facility</span>
                    <?php if (!empty($activeConcern['name'])): ?>
                        <span class="badge bg-primary text-white px-2 py-1 fs-7">
                            <i class="bi bi-diagram-3 me-1"></i><?= $escape($activeConcern['name']) ?><?= !empty($activeConcern['code']) ? ' (' . $escape($activeConcern['code']) . ')' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($activeConcern)): ?>
                        <div class="mb-3 p-2 rounded bg-light border border-light-subtle small">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Concern:</span>
                                <strong class="text-dark"><?= $escape(!empty($activeConcern['legal_name']) ? $activeConcern['legal_name'] : ($activeConcern['name'] ?? 'Primary Concern')) ?></strong>
                            </div>
                            <?php if (!empty($activeConcern['gst_number'])): ?>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">GSTIN:</span>
                                    <span class="fw-semibold text-uppercase font-monospace"><?= $escape($activeConcern['gst_number']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <label class="form-label small fw-bold text-muted mb-1" for="productionUnitSelect">Production Unit / Plant</label>
                    <select class="form-select" name="production_unit_id" id="productionUnitSelect" required>
                        <?php foreach ($productionUnits ?? [] as $unit): ?>
                            <option value="<?= (int)$unit['id'] ?>" <?= (int)($bill['production_unit_id'] ?? $prodUnit['id'] ?? 0) === (int)$unit['id'] ? 'selected' : '' ?> data-name="<?= $escape($unit['name'] ?? '') ?>" data-code="<?= $escape($unit['code'] ?? '') ?>" data-manager="<?= $escape($unit['manager_name'] ?? '') ?>" data-address="<?= $escape($unit['address'] ?? '') ?>" data-phone="<?= $escape($unit['phone'] ?? '') ?>" data-gstin="<?= $escape($unit['gst_number'] ?? '') ?>" data-logo="<?= $escape($unit['logo_url'] ?? '') ?>">
                                <?= $escape($unit['name'] ?? 'Production unit') ?><?= !empty($unit['code']) ? ' (' . $escape($unit['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="productionUnitDetails" class="small text-muted mt-2" aria-live="polite"></div>
                </div>
            </div>

            <!-- 3. Buyer Type Selection -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <label class="form-label fw-bold text-dark mb-2" for="buyerType">Bill To (Buyer Category)</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="buyer_type_radio" id="buyerTypeCustomer" value="CUSTOMER" <?= $selectedBuyerType === 'CUSTOMER' ? 'checked' : '' ?> onchange="onBuyerTypeChange('CUSTOMER')">
                        <label class="btn btn-outline-primary fw-semibold" for="buyerTypeCustomer"><i class="bi bi-person me-1"></i> Customer</label>

                        <input type="radio" class="btn-check" name="buyer_type_radio" id="buyerTypeCompany" value="COMPANY" <?= $selectedBuyerType === 'COMPANY' ? 'checked' : '' ?> onchange="onBuyerTypeChange('COMPANY')">
                        <label class="btn btn-outline-primary fw-semibold" for="buyerTypeCompany"><i class="bi bi-building me-1"></i> B2B Company</label>
                    </div>
                    <input type="hidden" name="buyer_type" id="buyerType" value="<?= $escape($selectedBuyerType) ?>">
                </div>
            </div>

            <!-- 4. Customer Fields with Auto-Suggest & History -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 <?= $selectedBuyerType === 'CUSTOMER' ? '' : 'd-none' ?>" id="customerFields">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
                    <span><i class="bi bi-person-vcard me-2 text-primary"></i>Customer / Contact</span>
                    <!-- Customer Stats & History Button -->
                    <div id="customerStatsContainer" class="d-none d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="custPurchaseBadge">
                            <i class="bi bi-bag-check-fill me-1"></i><span id="custPurchaseCount">0</span> Orders
                        </span>
                        <button type="button" class="btn btn-xs btn-outline-primary fw-semibold px-2 py-1 shadow-xs" id="btnCustomerHistory" onclick="openCustomerHistoryModal()" title="View past products & orders">
                            <i class="bi bi-clock-history me-1"></i>History
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Phone Number with Live Auto-Suggest & Auto-Fetch -->
                    <div class="mb-3 position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">Phone <span class="text-danger">*</span></label>
                            <span class="small text-muted" style="font-size: 0.75rem;"><i class="bi bi-lightning-charge text-warning"></i> Auto-fetch customer</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-telephone-fill"></i></span>
                            <input class="form-control fw-bold font-monospace" name="customer_phone" id="customerPhoneInput" 
                                   data-required-for="CUSTOMER" placeholder="Enter mobile number..." 
                                   autocomplete="off" value="<?= $escape($bill['customer_phone'] ?? '') ?>" 
                                   oninput="onCustomerPhoneInput(this.value)">
                            <button type="button" class="btn btn-outline-secondary" onclick="checkCustomerPhone()" title="Lookup customer details"><i class="bi bi-search"></i></button>
                        </div>
                        <!-- Live Search Auto-Suggest Dropdown -->
                        <div id="customerSuggestDropdown" class="dropdown-menu shadow-lg p-0 w-100 position-absolute start-0" style="z-index: 1050; max-height: 280px; overflow-y: auto; display: none;"></div>
                    </div>

                    <!-- Customer Last Purchase Info Banner -->
                    <div id="custLastPurchaseBanner" class="alert alert-light border border-primary-subtle py-2 px-3 mb-3 small d-none rounded-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate pe-2">
                                <i class="bi bi-clock-history text-primary me-1"></i>
                                <span id="custLastPurchaseText" class="fw-semibold text-dark"></span>
                            </div>
                            <a href="javascript:void(0)" class="fw-bold text-primary text-decoration-none flex-shrink-0" onclick="openCustomerHistoryModal()">View Log &rarr;</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Customer name <span class="text-danger">*</span></label>
                        <input class="form-control fw-semibold" name="customer_name" id="customerNameInput" data-required-for="CUSTOMER" value="<?= $escape($bill['customer_name'] ?? '') ?>" placeholder="Customer full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="customer_address" id="customerAddressInput" rows="2" placeholder="Street / Door No / Area"><?= $escape($bill['customer_address'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input class="form-control" name="customer_city" id="customerCityInput" value="<?= $escape($bill['customer_city'] ?? '') ?>" placeholder="City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input class="form-control" name="customer_state" id="customerStateInput" value="<?= $escape($bill['customer_state'] ?? '') ?>" placeholder="State">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">GSTIN</label>
                        <input class="form-control text-uppercase font-monospace" name="customer_gstin" id="customerGstinInput" maxlength="30" value="<?= $escape($bill['customer_gstin'] ?? '') ?>" placeholder="Customer GSTIN (if registered)">
                    </div>
                </div>
            </div>

            <!-- 5. Company Fields (when B2B selected) -->
            <div class="card border-0 shadow-sm rounded-3 mb-3 <?= $selectedBuyerType === 'COMPANY' ? '' : 'd-none' ?>" id="companyFields">
                <div class="card-header bg-white fw-semibold py-3"><i class="bi bi-building me-2 text-primary"></i>Company (Buyer)</div>
                <div class="card-body p-3">
                    <div class="mb-3"><label class="form-label">Company name <span class="text-danger">*</span></label><input class="form-control fw-semibold" name="company_name" data-required-for="COMPANY" value="<?= $escape($bill['company_name'] ?? '') ?>" placeholder="Company Legal Name"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address" rows="2"><?= $escape($bill['company_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control font-monospace" name="company_phone" value="<?= $escape($bill['company_phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="company_email" value="<?= $escape($bill['company_email'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase font-monospace" name="company_gstin" maxlength="30" value="<?= $escape($bill['company_gstin'] ?? '') ?>"></div>
                </div>
            </div>

            <!-- 6. Invoice & Payment Method -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white fw-semibold py-3"><i class="bi bi-credit-card me-2 text-primary"></i>Invoice &amp; Payment</div>
                <div class="card-body p-3">
                    <div class="mb-3"><label class="form-label fw-bold">Bill date</label><input class="form-control" type="date" name="bill_date" required value="<?= $escape($bill['bill_date'] ?? date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label class="form-label fw-bold">Payment method</label>
                        <select class="form-select fw-semibold" name="payment_method" id="paymentMethod">
                            <?php foreach (['CASH' => 'Cash', 'UPI' => 'UPI', 'CASH_UPI' => 'Cash + UPI', 'CARD' => 'Card', 'NET_BANKING' => 'Net Banking', 'CREDIT' => 'Credit'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($bill['payment_method'] ?? 'CASH') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Separate as Direct Bill Option (Reference Box) -->
                    <div class="p-3 border rounded-3 bg-light mb-3">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="is_direct_bill" id="isDirectBill" value="1" <?= !empty($bill['is_direct_bill']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-dark" for="isDirectBill">
                                Separate as Direct Bill
                            </label>
                        </div>
                        <div class="small text-muted ps-4">
                            Checked bills appear in the Direct Bills tab and are excluded from normal billing counts and totals.
                        </div>
                    </div>
                    <div id="splitPaymentFields" class="row g-2 <?= $usesSplitPayment ? '' : 'd-none' ?>">
                        <div class="col-6"><label class="form-label small" for="cashAmount">Cash amount</label><input class="form-control font-monospace" type="number" step="0.01" min="0" name="cash_amount" id="cashAmount" value="<?= $escape($bill['cash_amount'] ?? '0.00') ?>"></div>
                        <div class="col-6"><label class="form-label small" for="upiAmount">UPI / GPay amount</label><input class="form-control font-monospace" type="number" step="0.01" min="0" name="upi_amount" id="upiAmount" value="<?= $escape($bill['upi_amount'] ?? '0.00') ?>"></div>
                        <div class="col-12"><small class="text-muted">Cash and UPI / GPay amounts must equal the bill total.</small></div>
                    </div>
                    <div id="upiPaymentFields" class="<?= $usesUpiPayment ? '' : 'd-none' ?>">
                        <div class="mt-3"><label class="form-label small text-muted" for="upiReference">UPI / GPay reference</label><input class="form-control font-monospace" name="upi_reference" id="upiReference" maxlength="150" value="<?= $escape($bill['upi_reference'] ?? '') ?>" placeholder="e.g. 12-digit UTR number"></div>
                        <div class="mt-3">
                            <label class="form-label small text-muted" for="upiPaymentScreenshot">GPay payment screenshot <span class="fw-normal">(optional)</span></label>
                            <input class="form-control" type="file" name="upi_payment_screenshot" id="upiPaymentScreenshot" accept="image/jpeg,image/png,image/webp,image/avif">
                            <small class="text-muted">JPG, PNG, WEBP, or AVIF; maximum 5 MB.</small>
                            <div id="upiScreenshotPreview" class="mt-2 <?= empty($bill['upi_payment_screenshot_url']) ? 'd-none' : '' ?>">
                                <?php if (!empty($bill['upi_payment_screenshot_url'])): ?>
                                    <a href="<?= $escape(uploadUrl($bill['upi_payment_screenshot_url'])) ?>" target="_blank" rel="noopener">View current GPay screenshot</a>
                                <?php endif; ?>
                                <div id="upiScreenshotSelectedLabel" class="small text-primary d-none">New screenshot selected; it will replace the current proof.</div>
                                <img id="upiScreenshotPreviewImage" class="img-thumbnail mt-2 <?= empty($bill['upi_payment_screenshot_url']) ? 'd-none' : '' ?>" <?= !empty($bill['upi_payment_screenshot_url']) ? 'src="' . $escape(uploadUrl($bill['upi_payment_screenshot_url'])) . '"' : '' ?> data-existing-src="<?= $escape(uploadUrl($bill['upi_payment_screenshot_url'] ?? '')) ?>" alt="GPay payment screenshot" style="max-width:180px;max-height:180px;object-fit:contain;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Scanner, Cart & Settlement (Image 2) -->
        <div class="col-xl-8">
            <!-- 1. Scanner Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white fw-semibold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-upc-scan me-2 text-primary"></i>Scan Production Inventory QR</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Hardware Scanner Ready</span>
                </div>
                <div class="card-body p-3">
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label fw-bold mb-0 text-dark" for="inventorySearch">
                                <i class="bi bi-qr-code-scan text-success me-1"></i>Scan Finished Goods Barcode / Piece QR Tag
                            </label>
                            <span class="small text-muted">Auto-adds on hardware scanner enter</span>
                        </div>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white text-success border-success-subtle"><i class="bi bi-qr-code fs-4"></i></span>
                            <input id="inventorySearch" class="form-control fw-bold font-monospace border-success-subtle" autocomplete="off" placeholder="Scan finished-goods piece tag (e.g. PRD2611-0001) or SKU / Barcode...">
                            <button class="btn btn-success fw-bold px-4" type="button" id="scanAddButton"><i class="bi bi-plus-circle me-1"></i>Scan / Add</button>
                        </div>
                    </div>
                    <div id="searchResults" class="list-group mt-2"></div>
                    <div id="cartMessage" class="small text-muted mt-2">Only unbilled, available finished-goods QR labels that passed QC can be added.</div>
                </div>
            </div>

            <!-- 2. Cart Table Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="billingCartTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Product / QR Codes</th>
                                <th class="text-center" style="width:130px">Qty</th>
                                <th class="text-end">MRP</th>
                                <th style="width:100px">Discount %</th>
                                <th style="width:120px">Price / Pc<br><small>incl. tax</small></th>
                                <th style="min-width:300px">GST / Tax Details</th>
                                <th class="text-end">Tax (Incl.)</th>
                                <th class="text-end">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="billCart">
                            <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 d-block mb-2 text-secondary"></i>Scan or search for items to add to this bill.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Settlement & Checkout Card (Image 2 Matching Flow) -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <!-- Left Side: Cash Given by Customer & Change to Return (Ref: Image 2) -->
                        <div class="col-lg-7 border-end-lg pe-lg-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="cashGivenInput">
                                            <i class="bi bi-cash-stack text-success me-1"></i>Cash Given by Customer (₹)
                                        </label>
                                        <input type="number" step="0.01" min="0" name="cash_given" id="cashGivenInput" 
                                               class="form-control form-control-lg text-end fw-bold font-monospace fs-5 border-success-subtle" 
                                               placeholder="0.00" value="<?= $escape($bill['cash_given'] ?? '') ?>" 
                                               oninput="updateCashAndChange()">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="changeToReturnInput">
                                            <i class="bi bi-arrow-return-left text-primary me-1"></i>Change to Return (₹)
                                        </label>
                                        <input type="text" name="change_returned" id="changeToReturnInput" 
                                               class="form-control form-control-lg text-end fw-bold font-monospace text-success fs-5 bg-white border-2" 
                                               readonly value="<?= number_format((float)($bill['change_returned'] ?? 0), 2) ?>">
                                    </div>
                                    <div class="col-12 mt-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div id="changeStatusHelp" class="small text-muted">
                                                Any cash above the bill total is calculated as change to return.
                                            </div>
                                            <!-- Quick cash tender buttons -->
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-xs btn-outline-success px-2 py-1 shadow-xs fw-semibold" onclick="setQuickCash('EXACT')">Exact Total</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-xs font-monospace" onclick="setQuickCash(500)">₹500</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-xs font-monospace" onclick="setQuickCash(1000)">₹1000</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-xs font-monospace" onclick="setQuickCash(2000)">₹2000</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes / Terms / Reference (Ref: Image 2) -->
                            <div class="mt-3">
                                <label class="form-label fw-semibold text-dark mb-1">Notes / Terms / Reference</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Customer order notes, PO reference, delivery instructions..."><?= $escape($bill['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Right Side: Invoice Financial Breakdown -->
                        <div class="col-lg-5 ps-lg-3">
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Gross / MRP</span>
                                <strong id="grossTotal" class="text-dark font-monospace">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Discount</span>
                                <strong id="discountTotal" class="text-success font-monospace">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Taxable Base</span>
                                <strong id="subtotal" class="text-dark font-monospace">₹0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted small">
                                <span>Total GST (Incl.)</span>
                                <strong id="taxTotal" class="text-info font-monospace">₹0.00</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold fs-5 text-dark">Total</span>
                                <strong id="grandTotal" class="text-primary fs-3 font-monospace">₹0.00</strong>
                            </div>
                            <div id="splitBalance" class="small text-muted text-end mb-3"></div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <a class="btn btn-outline-secondary px-3 py-2 fw-semibold" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>">Cancel</a>
                                <button class="btn btn-primary btn-lg px-4 py-2 fw-bold shadow-sm" id="saveBillButton">
                                    <i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Bill' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modern Customer Purchase History & Products Log Modal -->
<div class="modal fade" id="customerHistoryModal" tabindex="-1" aria-labelledby="customerHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold mb-0" id="customerHistoryModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Customer Purchase History &amp; Products Log
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="customerHistoryBody">
                <!-- Dynamically rendered via JS -->
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modern Floating POS Stock Notification Toast -->
<div id="modernStockAlertToast" class="position-fixed top-0 start-50 translate-middle-x mt-4 d-none shadow-lg rounded-4" style="z-index: 9999; min-width: 340px; max-width: 92vw; transition: all 0.3s ease;">
    <div class="card border-0 rounded-4 overflow-hidden shadow" style="background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(12px); border-left: 6px solid #fd7e14 !important;">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 46px; height: 46px;">
                <i class="bi bi-box-seam-fill fs-4 text-warning"></i>
            </div>
            <div class="flex-grow-1 pe-2">
                <div class="fw-bold text-dark mb-0 fs-6" id="stockAlertToastTitle">Available stock limit reached</div>
                <div class="text-secondary small mb-0 lh-sm mt-1" id="stockAlertToastMessage"></div>
            </div>
            <button type="button" class="btn-close flex-shrink-0" id="stockAlertToastClose" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Modern Inventory Limit Modal -->
<div class="modal fade" id="inventoryLimitModal" tabindex="-1" aria-labelledby="inventoryLimitTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-warning-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>POS Limit Alert</span>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width:64px;height:64px">
                    <i class="bi bi-box-seam-fill fs-2 text-warning"></i>
                </div>
                <h5 class="fw-bold mb-2 text-dark" id="inventoryLimitTitle">Available Stock Limit Reached</h5>
                <p class="text-secondary small mb-0 lh-base" id="inventoryLimitMessage" aria-live="polite"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-3">
                <button type="button" class="btn btn-primary px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<script>
let lastComputedGrandTotal = 0;
let customerSuggestTimer = null;
let currentCustomerPhone = '<?= $escape($bill['customer_phone'] ?? '') ?>';
let historyModalInstance = null;

function updateStaffName(sel) {
    const opt = sel.options[sel.selectedIndex];
    const name = opt ? opt.getAttribute('data-name') : '';
    document.getElementById('staffNameInput').value = name || '';
}

function onBuyerTypeChange(type) {
    document.getElementById('buyerType').value = type;
    const companyFields = document.getElementById('companyFields');
    const customerFields = document.getElementById('customerFields');
    if (type === 'COMPANY') {
        companyFields.classList.remove('d-none');
        customerFields.classList.add('d-none');
    } else {
        companyFields.classList.add('d-none');
        customerFields.classList.remove('d-none');
    }
}

function onCustomerPhoneInput(val) {
    const q = (val || '').trim();
    clearTimeout(customerSuggestTimer);

    const dropdown = document.getElementById('customerSuggestDropdown');
    if (!dropdown) return;

    if (q.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
    }

    customerSuggestTimer = setTimeout(() => {
        fetch('<?= url("api/production/billing/customer-search") ?>?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(res => {
                const customers = (res.data && res.data.customers) || [];
                if (!customers.length) {
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                    return;
                }

                let html = '<div class="list-group list-group-flush">';
                customers.forEach(c => {
                    const safeName = (c.name || 'Unnamed').replace(/'/g, "\\'");
                    const safePhone = (c.phone || '').replace(/'/g, "\\'");
                    const safeAddr = (c.address || '').replace(/'/g, "\\'");
                    const safeCity = (c.customer_city || '').replace(/'/g, "\\'");
                    const safeState = (c.customer_state || '').replace(/'/g, "\\'");
                    const safeGstin = (c.gstin || '').replace(/'/g, "\\'");

                    html += `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2" 
                           onclick="applyCustomerData('${safeName}', '${safePhone}', '${safeAddr}', '${safeCity}', '${safeState}', '${safeGstin}', ${c.purchase_count || 0}, '${c.last_purchase_date || ''}')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark">${c.name || 'Customer'}</strong>
                                    <span class="badge bg-light text-primary border font-monospace ms-1">${c.phone}</span>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-bag-check me-1"></i>${c.purchase_count} orders
                                </span>
                            </div>
                            ${c.address || c.customer_city ? `<div class="small text-muted text-truncate mt-1"><i class="bi bi-geo-alt me-1"></i>${[c.address, c.customer_city].filter(Boolean).join(', ')}</div>` : ''}
                        </a>
                    `;
                });
                html += '</div>';
                dropdown.innerHTML = html;
                dropdown.style.display = 'block';
            })
            .catch(() => {
                dropdown.style.display = 'none';
            });
    }, 250);
}

function applyCustomerData(name, phone, address, city, state, gstin, orderCount, lastDate) {
    document.getElementById('customerNameInput').value = name;
    document.getElementById('customerPhoneInput').value = phone;
    if (document.getElementById('customerAddressInput')) document.getElementById('customerAddressInput').value = address;
    if (document.getElementById('customerCityInput')) document.getElementById('customerCityInput').value = city;
    if (document.getElementById('customerStateInput')) document.getElementById('customerStateInput').value = state;
    if (document.getElementById('customerGstinInput')) document.getElementById('customerGstinInput').value = gstin;

    const dropdown = document.getElementById('customerSuggestDropdown');
    if (dropdown) dropdown.style.display = 'none';

    currentCustomerPhone = phone;

    // Show purchase count badge & history button
    const statsContainer = document.getElementById('customerStatsContainer');
    const badgeCount = document.getElementById('custPurchaseCount');
    if (statsContainer && badgeCount) {
        badgeCount.textContent = orderCount || '1';
        statsContainer.classList.remove('d-none');
    }

    // Show last purchase callout
    const lastBanner = document.getElementById('custLastPurchaseBanner');
    const lastText = document.getElementById('custLastPurchaseText');
    if (lastBanner && lastText) {
        lastText.textContent = `${orderCount} previous purchase(s) · Last bought on ${lastDate || 'recent date'}`;
        lastBanner.classList.remove('d-none');
    }
}

function checkCustomerPhone() {
    const phone = document.getElementById('customerPhoneInput').value.trim();
    if (!phone) return;
    onCustomerPhoneInput(phone);
}

function openCustomerHistoryModal() {
    const phone = currentCustomerPhone || document.getElementById('customerPhoneInput')?.value.trim();
    if (!phone) {
        alert('Please enter or select a customer phone number first.');
        return;
    }

    if (!historyModalInstance) {
        historyModalInstance = new bootstrap.Modal(document.getElementById('customerHistoryModal'));
    }
    historyModalInstance.show();

    const body = document.getElementById('customerHistoryBody');
    body.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <div class="text-muted small">Loading customer past orders & items...</div>
        </div>
    `;

    fetch('<?= url("api/production/billing/customer-history") ?>?phone=' + encodeURIComponent(phone))
        .then(res => res.json())
        .then(res => {
            if (!res.data || !res.data.bills || !res.data.bills.length) {
                body.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold">No Previous Purchases Found</h6>
                        <p class="small mb-0">This customer has not placed any prior bills in the system.</p>
                    </div>
                `;
                return;
            }

            const summary = res.data.summary || {};
            const bills = res.data.bills || [];

            let html = `
                <div class="card border-0 bg-primary-subtle rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold text-primary mb-1">${summary.name || 'Customer'}</h5>
                            <span class="font-monospace text-dark fw-semibold"><i class="bi bi-telephone-fill me-1 small"></i>${summary.phone}</span>
                            ${summary.address ? `<div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1"></i>${summary.address}</div>` : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6 px-3 py-2 mb-1">${summary.total_orders || bills.length} Orders Placed</span>
                            <div class="small text-muted">Total Spent: <strong class="text-dark">₹${parseFloat(summary.lifetime_spent || 0).toFixed(2)}</strong></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-clock-history text-primary me-2"></i>Past Orders &amp; Garments Purchased:</h6>
                <div class="accordion" id="customerBillsAccordion">
            `;

            bills.forEach((b, idx) => {
                const isFirst = idx === 0;
                const items = b.items || [];
                html += `
                    <div class="accordion-item border rounded-3 mb-2 shadow-xs">
                        <h2 class="accordion-header" id="heading_b_${b.id}">
                            <button class="accordion-button ${isFirst ? '' : 'collapsed'} py-2 px-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_b_${b.id}">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3 flex-wrap gap-1">
                                    <div>
                                        <span class="badge bg-dark font-monospace me-2">${b.bill_number}</span>
                                        <span class="text-muted small">${b.bill_date}</span>
                                        ${isFirst ? '<span class="badge bg-warning text-dark ms-2"><i class="bi bi-star-fill me-1"></i>Most Recent</span>' : ''}
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary-subtle text-secondary border small">${b.payment_method}</span>
                                        <strong class="text-primary fs-6">₹${parseFloat(b.total_amount).toFixed(2)}</strong>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse_b_${b.id}" class="accordion-collapse collapse ${isFirst ? 'show' : ''}" data-bs-parent="#customerBillsAccordion">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light small text-uppercase">
                                            <tr>
                                                <th>Product Style</th>
                                                <th>Size &amp; Color</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total</th>
                                                <th>QR Tags</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;

                items.forEach(it => {
                    html += `
                        <tr>
                            <td>
                                <strong class="text-dark">${it.product_name}</strong>
                                <div class="small text-muted font-monospace">${it.sku || ''}</div>
                            </td>
                            <td>
                                ${it.size_name ? `<span class="badge bg-light text-dark border me-1">${it.size_name}</span>` : ''}
                                ${it.color_name ? `<span class="badge bg-light text-dark border">${it.color_name}</span>` : ''}
                            </td>
                            <td class="text-center fw-bold">${it.quantity}</td>
                            <td class="text-end">₹${parseFloat(it.unit_price).toFixed(2)}</td>
                            <td class="text-end fw-bold text-dark">₹${parseFloat(it.total_amount).toFixed(2)}</td>
                            <td>
                                ${it.tags_csv ? `<span class="small font-monospace badge bg-secondary-subtle text-dark border">${it.tags_csv}</span>` : '<span class="text-muted small">-</span>'}
                            </td>
                        </tr>
                    `;
                });

                html += `
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-2 bg-light d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">${b.staff_name ? 'Billed by: ' + b.staff_name : ''}</span>
                                    <a href="<?= url('production/billing/view/') ?>/${b.id}" target="_blank" class="fw-semibold text-primary text-decoration-none">
                                        View Invoice Details <i class="bi bi-box-arrow-up-right small"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = `<div class="alert alert-danger mb-0">Error loading customer history: ${err.message}</div>`;
        });
}

function updateCashAndChange(total) {
    if (total !== undefined) {
        lastComputedGrandTotal = total;
    } else {
        total = lastComputedGrandTotal;
    }

    const cashGivenInput = document.getElementById('cashGivenInput');
    const changeToReturnInput = document.getElementById('changeToReturnInput');
    const changeStatusHelp = document.getElementById('changeStatusHelp');
    if (!cashGivenInput || !changeToReturnInput) return;

    const paymentMethod = document.getElementById('paymentMethod')?.value;
    const cashGivenRaw = cashGivenInput.value.trim();
    if (cashGivenRaw === '') {
        changeToReturnInput.value = '0.00';
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold fs-5 bg-white border-2';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = paymentMethod === 'CASH_UPI'
                ? 'Enter the cash tendered to calculate change on the cash portion.'
                : 'Any cash above the bill total is calculated as change to return.';
        }
        return;
    }

    const cashGiven = parseFloat(cashGivenRaw) || 0;
    const cashTarget = paymentMethod === 'CASH_UPI'
        ? Number(document.getElementById('cashAmount')?.value || 0)
        : total;
    const change = cashGiven - cashTarget;

    if (change >= 0) {
        changeToReturnInput.value = change.toFixed(2);
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold text-success fs-5 bg-white border-2 border-success';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = change > 0 
                ? `<span class="text-success fw-bold"><i class="bi bi-arrow-return-left me-1"></i> Return ₹${change.toFixed(2)} change to customer</span>` 
                : `<span class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i> Exact amount received</span>`;
        }
    } else {
        changeToReturnInput.value = '0.00';
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold text-danger fs-5 bg-white border-2 border-danger';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i> ₹${Math.abs(change).toFixed(2)} remaining to collect</span>`;
        }
    }
}

function setQuickCash(amount) {
    const cashGivenInput = document.getElementById('cashGivenInput');
    if (!cashGivenInput) return;
    if (amount === 'EXACT') {
        cashGivenInput.value = lastComputedGrandTotal.toFixed(2);
    } else {
        cashGivenInput.value = Number(amount).toFixed(2);
    }
    updateCashAndChange(lastComputedGrandTotal);
}

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('customerSuggestDropdown');
    const input = document.getElementById('customerPhoneInput');
    if (dropdown && input && !dropdown.contains(e.target) && e.target !== input) {
        dropdown.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('productionBillForm');
    const initialItems = <?= json_encode($existingItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const billId = <?= (int)($bill['id'] ?? 0) ?>;
    const cart = new Map();
    const search = document.getElementById('inventorySearch');
    const resultBox = document.getElementById('searchResults');
    const cartBody = document.getElementById('billCart');
    const buyerType = document.getElementById('buyerType');
    const unitSelect = document.getElementById('productionUnitSelect');
    const unitDetails = document.getElementById('productionUnitDetails');
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const splitPaymentFields = document.getElementById('splitPaymentFields');
    const upiPaymentFields = document.getElementById('upiPaymentFields');
    const cashAmountInput = document.getElementById('cashAmount');
    const upiAmountInput = document.getElementById('upiAmount');
    const upiReferenceInput = document.getElementById('upiReference');
    const upiPaymentScreenshotInput = document.getElementById('upiPaymentScreenshot');
    const upiScreenshotPreview = document.getElementById('upiScreenshotPreview');
    const upiScreenshotPreviewImage = document.getElementById('upiScreenshotPreviewImage');
    const upiScreenshotSelectedLabel = document.getElementById('upiScreenshotSelectedLabel');
    const money = value => '₹' + Number(value || 0).toFixed(2);
    let searchTimer;
    let toastTimer = null;
    let limitModalInstance = null;

    function updatePaymentFields() {
        const usesUpi = ['UPI', 'CASH_UPI'].includes(paymentMethodSelect.value);
        const usesSplit = paymentMethodSelect.value === 'CASH_UPI';
        splitPaymentFields.classList.toggle('d-none', !usesSplit);
        upiPaymentFields.classList.toggle('d-none', !usesUpi);
        cashAmountInput.required = usesSplit;
        upiAmountInput.required = usesSplit;
        upiReferenceInput.required = usesUpi;
        if (!usesUpi) {
            upiPaymentScreenshotInput.value = '';
            const existingScreenshot = upiScreenshotPreviewImage.dataset.existingSrc || '';
            if (existingScreenshot) {
                upiScreenshotPreviewImage.src = existingScreenshot;
            } else {
                upiScreenshotPreviewImage.removeAttribute('src');
            }
            upiScreenshotPreviewImage.classList.toggle('d-none', !existingScreenshot);
            upiScreenshotPreview.classList.toggle('d-none', !existingScreenshot);
            upiScreenshotSelectedLabel.classList.add('d-none');
        }
    }

    paymentMethodSelect.addEventListener('change', () => {
        updatePaymentFields();
        updateTotals();
    });
    cashAmountInput.addEventListener('input', updateTotals);
    upiAmountInput.addEventListener('input', updateTotals);
    upiPaymentScreenshotInput.addEventListener('change', () => {
        const file = upiPaymentScreenshotInput.files && upiPaymentScreenshotInput.files[0];
        if (!file) return;
        upiScreenshotPreviewImage.src = URL.createObjectURL(file);
        upiScreenshotPreviewImage.classList.remove('d-none');
        upiScreenshotPreview.classList.remove('d-none');
        upiScreenshotSelectedLabel.classList.remove('d-none');
    });

    // Check customer phone on load if editing
    if (currentCustomerPhone) {
        onCustomerPhoneInput(currentCustomerPhone);
    }

    // POS Audio feedback using Web Audio API
    function playBeep(type = 'success') {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.12);
            } else if (type === 'warning') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                gain.gain.setValueAtTime(0.18, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.22);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.22);
            }
        } catch (e) {}
    }

    // Modern floating toast alert
    function showStockToast(title, message) {
        const toastEl = document.getElementById('modernStockAlertToast');
        if (!toastEl) return;
        document.getElementById('stockAlertToastTitle').textContent = title || 'Available stock limit reached';
        document.getElementById('stockAlertToastMessage').innerHTML = message;
        toastEl.classList.remove('d-none');
        toastEl.style.transform = 'translate(-50%, 0)';
        playBeep('warning');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toastEl.classList.add('d-none');
        }, 4500);
    }

    // Modern Inventory Limit Modal
    function showInventoryLimit(title, message, targetInput = null) {
        showStockToast(title, message);
        const modalEl = document.getElementById('inventoryLimitModal');
        if (modalEl) {
            document.getElementById('inventoryLimitTitle').textContent = title;
            document.getElementById('inventoryLimitMessage').textContent = message;
            if (!limitModalInstance && window.bootstrap && window.bootstrap.Modal) {
                limitModalInstance = new bootstrap.Modal(modalEl);
            }
            if (limitModalInstance) {
                limitModalInstance.show();
            }
        }
        if (targetInput) {
            targetInput.classList.add('is-invalid', 'border-danger');
            setTimeout(() => targetInput.classList.remove('is-invalid', 'border-danger'), 2500);
            targetInput.focus();
        }
    }

    const toastCloseBtn = document.getElementById('stockAlertToastClose');
    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', () => {
            const toastEl = document.getElementById('modernStockAlertToast');
            if (toastEl) toastEl.classList.add('d-none');
            if (toastTimer) clearTimeout(toastTimer);
        });
    }

    function showSuccessToast(message) {
        playBeep('success');
    }

    function getItemKey(item) {
        return (item.qc_item_id && item.qc_item_id > 0)
            ? `qc_${item.qc_item_id}`
            : `var_${item.variant_id}_${item.unit_price}_${item.gst_percentage}`;
    }

    function assignedTags(excludeKey = '') {
        const list = [];
        cart.forEach((entry, key) => {
            if (key !== excludeKey) {
                list.push(...(entry.tags || []));
            }
        });
        return list;
    }

    function selectableTags(item, excludeSelf = false) {
        const assigned = new Set(assignedTags(excludeSelf ? getItemKey(item) : ''));
        return (item.available_tags || []).filter(tag => !assigned.has(tag));
    }

    function updateUnitDetails() {
        const option = unitSelect.selectedOptions[0];
        if (!option) {
            unitDetails.textContent = '';
            return;
        }
        const fields = [
            option.dataset.code ? `Code: ${option.dataset.code}` : '',
            option.dataset.manager ? `Manager: ${option.dataset.manager}` : '',
            option.dataset.phone ? `Phone: ${option.dataset.phone}` : '',
            option.dataset.gstin ? `GSTIN: ${option.dataset.gstin}` : '',
            option.dataset.address ? `Address: ${option.dataset.address}` : '',
        ].filter(Boolean);
        unitDetails.textContent = fields.join(' · ');
        const logo = document.getElementById('productionUnitLogo');
        if (logo) {
            logo.src = option.dataset.logo || '';
            logo.classList.toggle('d-none', !option.dataset.logo);
        }
    }

    function renderCart() {
        cartBody.innerHTML = '';
        if (!cart.size) {
            cartBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 d-block mb-2 text-secondary"></i>Scan or search for items to add to this bill.</td></tr>';
            updateTotals();
            return;
        }

        let index = 0;
        cart.forEach((item, itemKey) => {
            const row = cartBody.insertRow();
            row.dataset.rowKey = itemKey;
            const currentQty = item.tags.length;
            const allAvailable = selectableTags(item);
            const maxStock = allAvailable.length;

            // 1. Product details & HSN
            const productCell = row.insertCell();
            const productTitle = document.createElement('strong');
            productTitle.className = 'text-dark';
            productTitle.textContent = item.product_name;
            productCell.append(productTitle);

            if (item.price_edited) {
                const priceBadge = document.createElement('span');
                priceBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning ms-1';
                priceBadge.title = item.price_alert || 'Price was recently revised';
                priceBadge.innerHTML = '<i class="bi bi-tag-fill me-1"></i>Price Checked';
                productCell.append(priceBadge);
            }

            const details = document.createElement('div');
            details.className = 'small text-muted';
            details.textContent = [item.sku, item.size_name ? `Size: ${item.size_name}` : '', item.color_name ? `Color: ${item.color_name}` : ''].filter(Boolean).join(' · ');
            productCell.append(details);

            const hiddenQc = document.createElement('input');
            hiddenQc.type = 'hidden';
            hiddenQc.name = `items[${index}][qc_item_id]`;
            hiddenQc.value = item.qc_item_id || 0;
            productCell.append(hiddenQc);

            const hiddenVar = document.createElement('input');
            hiddenVar.type = 'hidden';
            hiddenVar.name = `items[${index}][variant_id]`;
            hiddenVar.value = item.variant_id || 0;
            productCell.append(hiddenVar);

            const hsnLabel = document.createElement('label');
            hsnLabel.className = 'form-label small fw-semibold text-muted mb-1 mt-2';
            hsnLabel.textContent = 'HSN Code';

            const hsnGroup = document.createElement('div');
            hsnGroup.className = 'input-group input-group-sm';

            const hsnInput = document.createElement('input');
            hsnInput.type = 'text';
            hsnInput.id = `billing-hsn-${index}`;
            hsnInput.maxLength = 20;
            hsnInput.required = true;
            hsnInput.readOnly = true;
            hsnInput.inputMode = 'numeric';
            hsnInput.className = 'form-control';
            hsnInput.placeholder = '4, 6 or 8 digits';
            hsnInput.name = `items[${index}][hsn_code]`;
            hsnInput.value = item.hsn_code || '6109';
            hsnInput.addEventListener('input', () => { item.hsn_code = hsnInput.value.trim(); });
            hsnLabel.htmlFor = hsnInput.id;

            const hsnEditButton = document.createElement('button');
            hsnEditButton.type = 'button';
            hsnEditButton.className = 'btn btn-outline-secondary';
            hsnEditButton.innerHTML = '<i class="bi bi-pencil-square me-1"></i>Edit';
            hsnEditButton.setAttribute('aria-label', 'Edit HSN code');
            hsnEditButton.addEventListener('click', () => {
                const startEditing = hsnInput.readOnly;
                hsnInput.readOnly = !startEditing;
                hsnEditButton.innerHTML = startEditing
                    ? '<i class="bi bi-check-lg me-1"></i>Done'
                    : '<i class="bi bi-pencil-square me-1"></i>Edit';
                hsnEditButton.setAttribute('aria-label', startEditing ? 'Finish editing HSN code' : 'Edit HSN code');
                if (startEditing) {
                    hsnInput.focus();
                    hsnInput.select();
                }
            });

            hsnGroup.append(hsnInput, hsnEditButton);
            productCell.append(hsnLabel, hsnGroup);

            // Tags badges
            const tagsWrap = document.createElement('div');
            tagsWrap.className = 'd-flex flex-wrap gap-1 mt-1';
            item.tags.forEach(tag => {
                const badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'badge border-0 bg-light text-dark font-monospace shadow-xs';
                badge.title = 'Remove QR piece from bill';
                badge.textContent = `${tag} ×`;
                badge.addEventListener('click', () => {
                    item.tags = item.tags.filter(existing => existing !== tag);
                    if (!item.tags.length) {
                        cart.delete(itemKey);
                    }
                    renderCart();
                });
                tagsWrap.append(badge);
            });
            productCell.append(tagsWrap);

            // 2. Quantity cell
            const quantityCell = row.insertCell();
            quantityCell.className = 'text-center';

            const qtyGroup = document.createElement('div');
            qtyGroup.className = 'input-group input-group-sm mx-auto shadow-xs';
            qtyGroup.style.maxWidth = '115px';

            const minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.className = 'btn btn-outline-secondary px-2';
            minusBtn.innerHTML = '<i class="bi bi-dash"></i>';
            minusBtn.addEventListener('click', () => {
                if (item.tags.length > 1) {
                    item.tags.pop();
                    renderCart();
                } else {
                    showStockToast('Minimum quantity is 1', 'To remove this product completely, click the trash button on the right.');
                }
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';
            quantityInput.min = '1';
            quantityInput.step = '1';
            quantityInput.max = String(maxStock);
            quantityInput.value = String(currentQty);
            quantityInput.className = 'form-control form-control-sm text-center fw-bold px-1';

            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'btn btn-outline-secondary px-2';
            plusBtn.innerHTML = '<i class="bi bi-plus"></i>';
            plusBtn.addEventListener('click', () => {
                if (item.tags.length < maxStock) {
                    const assignedUpper = item.tags.map(t => t.toUpperCase());
                    const nextTag = allAvailable.find(t => !assignedUpper.includes(t.toUpperCase()));
                    if (nextTag) {
                        item.tags.push(nextTag);
                        renderCart();
                        showSuccessToast();
                    }
                } else {
                    showInventoryLimit(
                        'Available stock limit reached',
                        `All ${maxStock} available piece(s) in stock for ${item.product_name} are already added to this bill.`,
                        quantityInput
                    );
                }
            });

            quantityInput.addEventListener('change', () => {
                const requestedQuantity = parseInt(quantityInput.value, 10);
                if (isNaN(requestedQuantity) || requestedQuantity < 1) {
                    quantityInput.value = '1';
                    item.tags = allAvailable.slice(0, 1);
                    showInventoryLimit('Invalid quantity', 'Enter a whole-number quantity of at least 1.', quantityInput);
                    renderCart();
                    return;
                }
                if (requestedQuantity > maxStock) {
                    quantityInput.value = String(maxStock);
                    item.tags = allAvailable.slice(0, maxStock);
                    showInventoryLimit(
                        'Available stock limit reached',
                        `Only ${maxStock} piece(s) are available in stock for ${item.product_name}. Cannot enter ${requestedQuantity}.`,
                        quantityInput
                    );
                    renderCart();
                    return;
                }
                item.tags = allAvailable.slice(0, requestedQuantity);
                renderCart();
            });

            qtyGroup.append(minusBtn, quantityInput, plusBtn);
            quantityCell.append(qtyGroup);

            const limitHelper = document.createElement('div');
            limitHelper.className = 'small text-muted text-center mt-1';
            limitHelper.innerHTML = `Stock: <strong class="text-dark">${maxStock}</strong> pcs`;
            quantityCell.append(limitHelper);

            // Hidden tags
            item.tags.forEach(tag => {
                const hiddenTag = document.createElement('input');
                hiddenTag.type = 'hidden';
                hiddenTag.name = `items[${index}][tags][]`;
                hiddenTag.value = tag;
                quantityCell.append(hiddenTag);
            });

            // 3. MRP cell
            const mrpCell = row.insertCell();
            mrpCell.className = 'text-end fw-semibold text-muted font-monospace';
            mrpCell.textContent = money(item.mrp);

            // 4. Discount cell
            const discountCell = row.insertCell();
            const discountInput = document.createElement('input');
            discountInput.type = 'number';
            discountInput.step = '0.01';
            discountInput.min = '0';
            discountInput.max = '100';
            discountInput.className = 'form-control form-control-sm text-end font-monospace';
            discountInput.value = Number(item.discount_percentage || 0).toFixed(2);
            discountInput.name = `items[${index}][discount_percentage]`;
            discountInput.addEventListener('input', () => {
                const discount = Math.min(100, Math.max(0, Number(discountInput.value || 0)));
                item.discount_percentage = discount;
                item.unit_price = Number(item.mrp || 0) * (1 - discount / 100);
                priceInput.value = item.unit_price.toFixed(2);
                updateTotals();
            });
            discountCell.append(discountInput);

            // 5. Price / piece cell
            const priceCell = row.insertCell();
            const priceInput = document.createElement('input');
            priceInput.type = 'number';
            priceInput.step = '0.01';
            priceInput.min = '0';
            priceInput.className = 'form-control form-control-sm text-end font-monospace fw-semibold';
            priceInput.value = Number(item.unit_price || 0).toFixed(2);
            priceInput.name = `items[${index}][unit_price]`;
            priceInput.addEventListener('input', () => {
                item.unit_price = Math.max(0, Number(priceInput.value || 0));
                item.discount_percentage = Number(item.mrp || 0) > 0 ? Math.max(0, 100 - (item.unit_price / item.mrp) * 100) : 0;
                discountInput.value = item.discount_percentage.toFixed(2);
                updateTotals();
            });
            priceCell.append(priceInput);

            // 6. GST details cell
            const gstCell = row.insertCell();
            const taxGroup = document.createElement('div');
            taxGroup.className = 'd-flex gap-2 align-items-center flex-wrap';

            const taxModeSelect = document.createElement('select');
            taxModeSelect.className = 'form-select form-select-sm';
            taxModeSelect.name = `items[${index}][gst_type]`;
            taxModeSelect.innerHTML = '<option value="BOTH">CGST + SGST</option><option value="IGST">IGST only</option><option value="EXEMPTED">Exempted (0%)</option>';
            taxModeSelect.value = item.gst_type || 'BOTH';

            const taxRateInput = document.createElement('input');
            taxRateInput.type = 'number';
            taxRateInput.step = '0.01';
            taxRateInput.min = '0';
            taxRateInput.max = '40';
            taxRateInput.className = 'form-control form-control-sm text-end font-monospace';
            taxRateInput.style.maxWidth = '85px';
            taxRateInput.name = `items[${index}][gst_percentage]`;
            taxRateInput.value = Number(item.gst_percentage || 0).toFixed(2);

            taxModeSelect.addEventListener('change', () => {
                item.gst_type = taxModeSelect.value;
                if (taxModeSelect.value === 'EXEMPTED') {
                    taxRateInput.value = '0.00';
                    item.gst_percentage = 0;
                } else if (Number(taxRateInput.value || 0) === 0) {
                    taxRateInput.value = '5.00';
                    item.gst_percentage = 5;
                }
                updateTotals();
            });

            taxRateInput.addEventListener('input', () => {
                item.gst_percentage = Math.max(0, Number(taxRateInput.value || 0));
                updateTotals();
            });

            taxGroup.append(taxModeSelect, taxRateInput);
            gstCell.append(taxGroup);

            // 7. Tax amount cell
            const taxAmountCell = row.insertCell();
            taxAmountCell.className = 'text-end font-monospace text-muted';
            taxAmountCell.dataset.taxFor = itemKey;
            taxAmountCell.textContent = money(0);

            // 8. Line Total cell
            const totalCell = row.insertCell();
            totalCell.className = 'text-end fw-bold font-monospace text-primary';
            totalCell.dataset.totalFor = itemKey;
            totalCell.textContent = money(0);

            // 9. Remove item cell
            const removeCell = row.insertCell();
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger btn-sm';
            removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
            removeBtn.title = 'Remove item from cart';
            removeBtn.addEventListener('click', () => {
                cart.delete(itemKey);
                renderCart();
            });
            removeCell.append(removeBtn);

            index++;
        });

        updateTotals();
    }

    function updateTotals() {
        let gross = 0;
        let discount = 0;
        let subtotal = 0;
        let taxes = 0;
        let grandTotal = 0;

        cart.forEach((item, itemKey) => {
            const qty = item.tags.length;
            const price = Number(item.unit_price || 0);
            const mrp = Number(item.mrp || 0);
            const lineGross = mrp * qty;
            const netLine = price * qty;
            const gstRate = Number(item.gst_percentage || 0);

            let taxable = netLine;
            let lineTax = 0;
            if (gstRate > 0) {
                taxable = netLine / (1 + gstRate / 100);
                lineTax = netLine - taxable;
            }

            const lineTotal = netLine;
            gross += lineGross;
            discount += Math.max(0, lineGross - netLine);
            subtotal += taxable;
            taxes += lineTax;
            grandTotal += lineTotal;

            const lineTotalCell = cartBody.querySelector(`[data-total-for="${itemKey}"]`);
            const taxCell = cartBody.querySelector(`[data-tax-for="${itemKey}"]`);
            if (lineTotalCell) lineTotalCell.textContent = money(lineTotal);
            if (taxCell) taxCell.textContent = money(lineTax);
        });

        document.getElementById('grossTotal').textContent = money(gross);
        document.getElementById('discountTotal').textContent = money(discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('taxTotal').textContent = money(taxes);
        document.getElementById('grandTotal').textContent = money(grandTotal);

        const cash = Number(form.elements.cash_amount?.value || 0);
        const upi = Number(form.elements.upi_amount?.value || 0);
        document.getElementById('splitBalance').textContent = document.getElementById('paymentMethod').value === 'CASH_UPI'
            ? `Cash + UPI: ${money(cash + upi)} / ${money(grandTotal)}`
            : '';

        // Auto update Cash Tendered & Change Return (Image 2)
        updateCashAndChange(grandTotal);
    }

    function addResult(item, selectedTag) {
        const assigned = assignedTags();
        const availableTags = Array.isArray(item.available_tags) ? item.available_tags : [];
        const key = getItemKey(item);

        if (cart.has(key)) {
            const cartItem = cart.get(key);
            cartItem.available_tags = [...new Set([...(cartItem.available_tags || []), ...availableTags])];
            const allAvailable = selectableTags(cartItem);
            const currentCount = cartItem.tags.length;
            const maxAllowed = allAvailable.length;

            if (selectedTag) {
                const upperSelected = selectedTag.toUpperCase();
                const alreadyInThisItem = cartItem.tags.some(t => t.toUpperCase() === upperSelected);
                if (alreadyInThisItem) {
                    showStockToast('Piece already in cart', `Tag <strong>${selectedTag}</strong> is already added.`);
                    return;
                }
                const alreadyInOther = assigned.some(t => t.toUpperCase() === upperSelected);
                if (alreadyInOther) {
                    showStockToast('Tag already allocated', `Tag <strong>${selectedTag}</strong> is assigned to another cart item.`);
                    return;
                }
                cartItem.tags.push(selectedTag);
                showSuccessToast();
            } else {
                if (currentCount >= maxAllowed) {
                    showInventoryLimit(
                        'Available stock limit reached',
                        `All ${maxAllowed} piece(s) in stock for ${cartItem.product_name} are already in this bill.`
                    );
                    return;
                }
                const assignedUpper = cartItem.tags.map(t => t.toUpperCase());
                const nextTag = allAvailable.find(t => !assignedUpper.includes(t.toUpperCase()));
                if (nextTag) {
                    cartItem.tags.push(nextTag);
                    showSuccessToast();
                }
            }
        } else {
            const entry = { ...item };
            entry.tags = [];
            const allAvailable = selectableTags(entry);
            if (!allAvailable.length) {
                showInventoryLimit(
                    'No stock available',
                    `No unassigned pieces available for ${entry.product_name}.`
                );
                return;
            }
            if (selectedTag) {
                entry.tags = [selectedTag];
            } else {
                entry.tags = [allAvailable[0]];
            }
            cart.set(key, entry);
            showSuccessToast();
        }

        renderCart();

        if (item.price_edited && item.price_alert) {
            showStockToast('Price Cross-Check', item.price_alert);
        }
    }

    // Hardware Scanner & Input Handling
    function handleScanAdd(query) {
        const needle = (query || '').trim();
        if (!needle) return;

        search.disabled = true;
        const targetUnitId = unitSelect.value || 0;

        fetch(`<?= url('api/production/billing/search') ?>?q=${encodeURIComponent(needle)}&bill_id=${billId}&production_unit_id=${targetUnitId}`)
            .then(res => res.json())
            .then(res => {
                search.disabled = false;
                search.focus();
                if (!res.data || !res.data.items || !res.data.items.length) {
                    showStockToast('Item not found', `No finished-goods inventory matching "<strong>${needle}</strong>".`);
                    return;
                }
                const exactMatch = res.data.items.find(it => {
                    const available = Array.isArray(it.available_tags) ? it.available_tags : [];
                    const vCode = (it.variant_code || '').toUpperCase();
                    const vId = String(it.variant_id || '');
                    const uNeedle = needle.toUpperCase();
                    return available.some(t => t.toUpperCase() === uNeedle)
                        || (vCode !== '' && vCode === uNeedle)
                        || (vId !== '0' && (vId === uNeedle || vId === uNeedle.replace('#', '')));
                });

                if (exactMatch) {
                    addResult(exactMatch, exactMatch.matched_tag || (exactMatch.available_tags && exactMatch.available_tags[0]) || needle);
                    search.value = '';
                    resultBox.innerHTML = '';
                } else if (res.data.items.length === 1) {
                    const single = res.data.items[0];
                    addResult(single, single.matched_tag || (single.available_tags && single.available_tags[0]) || null);
                    search.value = '';
                    resultBox.innerHTML = '';
                } else {
                    renderSearchResults(res.data.items, needle);
                }
            })
            .catch(() => {
                search.disabled = false;
                search.focus();
            });
    }

    function renderSearchResults(items, query) {
        resultBox.innerHTML = '';
        if (!items.length) return;

        items.forEach(it => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action p-2';
            btn.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="text-dark">${it.product_name}</strong>
                        <span class="small text-muted ms-2">${it.sku || ''} ${it.size_name ? '· ' + it.size_name : ''}</span>
                    </div>
                    <span class="badge bg-primary">${money(it.unit_price)}</span>
                </div>
            `;
            btn.addEventListener('click', () => {
                addResult(it, null);
                search.value = '';
                resultBox.innerHTML = '';
                search.focus();
            });
            resultBox.append(btn);
        });
    }

    search.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleScanAdd(search.value);
        }
    });

    document.getElementById('scanAddButton').addEventListener('click', () => {
        handleScanAdd(search.value);
    });

    // Populate initial items
    initialItems.forEach(it => {
        const key = getItemKey(it);
        cart.set(key, { ...it });
    });

    updateUnitDetails();
    unitSelect.addEventListener('change', updateUnitDetails);
    updatePaymentFields();
    renderCart();

    // Form submit validation
    form.addEventListener('submit', (e) => {
        if (!cart.size) {
            e.preventDefault();
            alert('Please add at least one finished-goods piece to this bill.');
            return;
        }
        if (paymentMethodSelect.value === 'CASH_UPI') {
            const cash = Number(cashAmountInput.value || 0);
            const upi = Number(upiAmountInput.value || 0);
            if (Math.abs(cash + upi - lastComputedGrandTotal) > 0.01) {
                e.preventDefault();
                alert('Cash and UPI / GPay amounts must equal the bill total.');
            }
        }
    });
});
</script>
