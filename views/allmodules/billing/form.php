<?php
$isEdit = !empty($bill);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$existingItems = array_map(static function (array $item): array {
    return [
        'qc_item_id' => (int)$item['qc_item_id'],
        'variant_id' => (int)($item['variant_id'] ?? 0),
        'variant_code' => (string)($item['variant_code'] ?? ''),
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
$selectedPaymentStatus = (($bill['payment_status'] ?? 'PAID') === 'COD') ? 'COD' : 'PAID';
$usesUpiPayment = in_array($bill['payment_method'] ?? 'CASH', ['UPI', 'CASH_UPI'], true);
$usesSplitPayment = ($bill['payment_method'] ?? '') === 'CASH_UPI';
$concernBrand = function_exists('getConcernBranding') 
    ? getConcernBranding($bill['concern_id'] ?? null, $prodUnit ?? null) 
    : [];
$activeLogo = !empty($concernBrand['logo_url']) ? $concernBrand['logo_url'] : (!empty($prodUnit['logo_url']) ? $prodUnit['logo_url'] : '');
$defaultStaffId = (int)($bill['staff_id'] ?? $currentUserId ?? (Auth::user()['id'] ?? 0));
$defaultStaffName = (string)($bill['staff_name'] ?? (Auth::user()['full_name'] ?? ''));
$defaultStaffRole = '';
foreach ($staffUsers ?? [] as $st) {
    if ((int)$st['id'] === $defaultStaffId) {
        $defaultStaffName = (string)($st['full_name'] ?? $defaultStaffName);
        $defaultStaffRole = (string)($st['staff_role'] ?: $st['department'] ?: $st['username'] ?? '');
        break;
    }
}
$defaultStaffDisplay = $defaultStaffName . ($defaultStaffRole !== '' ? ' (' . $defaultStaffRole . ')' : '');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <img id="productionUnitLogo" src="<?= $escape($activeLogo) ?>" alt="<?= $escape($concernBrand['name'] ?? ($prodUnit['name'] ?? 'Production unit')) ?> logo" class="mb-2 <?= empty($activeLogo) ? 'd-none' : '' ?>" style="max-width:180px;max-height:64px;object-fit:contain;">
        <h4 class="mb-1 fw-bold"><?= $isEdit ? 'Edit Production POS Bill' : 'Production POS Billing' ?></h4>
        <div class="text-muted small">
            Scan QR labels from available inventory, auto-fetch customer history, and record staff attribution.
            <?php if ($defaultStaffDisplay !== ''): ?>
                <span class="ms-2">&bull; Billed by: <strong class="text-dark"><?= $escape($defaultStaffDisplay) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="d-flex align-items-center gap-2 px-3 py-2 bg-white border rounded-3 shadow-xs mb-0" style="cursor: pointer;" for="isDirectBill" title="Checked bills appear in the Direct Bills tab and are excluded from normal billing counts and totals.">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="is_direct_bill" id="isDirectBill" form="productionBillForm" value="1" <?= !empty($bill['is_direct_bill']) ? 'checked' : '' ?> style="cursor: pointer;">
            </div>
            <span class="fw-semibold text-dark small text-nowrap">Direct Bill</span>
        </label>
        <a class="btn btn-outline-secondary" href="<?= url('production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" action="<?= url($isEdit ? 'production/billing/update/' . (int)$bill['id'] : 'production/billing/store') ?>" id="productionBillForm">
    <?= CSRF::input() ?>
    <input type="hidden" name="production_unit_id" id="productionUnitSelect" value="<?= (int)($bill['production_unit_id'] ?? $prodUnit['id'] ?? 0) ?>">
    <input type="hidden" name="staff_id" id="staffSelect" value="<?= $defaultStaffId ?>">
    <input type="hidden" name="staff_name" id="staffNameInput" value="<?= $escape($defaultStaffName) ?>">
    <?php if (empty($isEdit)) { $concernSelectWrapCard = true; include BASE_PATH . '/views/partials/admin_concern_select.php'; unset($concernSelectWrapCard); } ?>
    <div class="row g-3">
        <!-- LEFT COLUMN: Buyer, Customer & Invoice Settings -->
        <div class="col-xl-4">

            <!-- 2. Buyer Type Selection -->
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
                <div class="card-header bg-white fw-semibold py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-building me-2 text-primary"></i>Company (Buyer) Details</span>
                    <span class="badge bg-primary-subtle text-primary border">B2B Order Mode</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" for="destinationConcernSelect">
                            <i class="bi bi-diagram-3 me-1 text-primary"></i>Send To Concern
                        </label>
                        <select class="form-select fw-semibold" name="destination_concern_id" id="destinationConcernSelect" onchange="onDestinationConcernChange(this)">
                            <option value="">-- Custom / External Company --</option>
                            <?php
                            $currentCid = (int)($activeConcern['id'] ?? 0);
                            $savedDestCid = (int)($bill['destination_concern_id'] ?? 0);
                            foreach ($targetConcerns ?? [] as $tc):
                                if ($currentCid > 0 && (int)$tc['id'] === $currentCid) continue;
                                $tcLegal = !empty($tc['legal_name']) ? $tc['legal_name'] : $tc['name'];
                            ?>
                                <option value="<?= (int)$tc['id'] ?>"
                                    <?= $savedDestCid === (int)$tc['id'] ? 'selected' : '' ?>
                                    data-name="<?= $escape($tcLegal) ?>"
                                    data-address="<?= $escape($tc['address'] ?? '') ?>"
                                    data-phone="<?= $escape($tc['phone'] ?? '') ?>"
                                    data-email="<?= $escape($tc['email'] ?? '') ?>"
                                    data-gstin="<?= $escape($tc['gst_number'] ?? '') ?>">
                                    <?= $escape($tc['name']) ?><?= !empty($tc['code']) ? ' (' . $escape($tc['code']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted">Select which concern to send this B2B outward to, or enter a custom company below.</div>
                    </div>
                    <div class="mb-3"><label class="form-label">Company name <span class="text-danger">*</span></label><input class="form-control fw-semibold" name="company_name" id="companyNameInput" data-required-for="COMPANY" value="<?= $escape($bill['company_name'] ?? '') ?>" placeholder="Company Legal Name"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address" id="companyAddressInput" rows="2"><?= $escape($bill['company_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control font-monospace" name="company_phone" id="companyPhoneInput" value="<?= $escape($bill['company_phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="company_email" id="companyEmailInput" value="<?= $escape($bill['company_email'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase font-monospace" name="company_gstin" id="companyGstinInput" maxlength="30" value="<?= $escape($bill['company_gstin'] ?? '') ?>"></div>
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
                            <input id="inventorySearch" class="form-control fw-bold font-monospace border-success-subtle" autocomplete="off" placeholder="Scan finished-goods piece tag (e.g. PRD2611-0001) or Variant ID / Barcode...">
                            <button class="btn btn-success fw-bold px-4" type="button" id="scanAddButton"><i class="bi bi-plus-circle me-1"></i>Scan / Add</button>
                        </div>
                    </div>
                    <div id="searchResults" class="list-group mt-2"></div>
                    <div id="cartMessage" class="small text-muted mt-2">Only unbilled, available finished-goods QR labels that passed QC can be added.</div>
                </div>
            </div>

            <!-- 2. Cart Table Card -->
            <style>
                #billingCartTable {
                    min-width: 1140px;
                }
                #billingCartTable th,
                #billingCartTable td {
                    padding: 0.7rem 0.45rem;
                    vertical-align: middle;
                }
                #billingCartTable .billing-product-cell {
                    min-width: 220px;
                    max-width: 290px;
                    white-space: normal;
                    word-break: break-word;
                }
                #billingCartTable .billing-qty-group {
                    width: 124px;
                    min-width: 124px;
                    flex-wrap: nowrap !important;
                }
                #billingCartTable .billing-qty-group .btn {
                    flex: 0 0 32px;
                    padding: 0.25rem 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }
                #billingCartTable .billing-qty-input {
                    min-width: 48px;
                    width: 52px;
                    flex: 1 1 auto;
                    padding-left: 0.25rem;
                    padding-right: 0.25rem;
                }
                #billingCartTable .billing-rate-input {
                    min-width: 74px;
                    padding-left: 0.4rem;
                    padding-right: 0.4rem;
                }
                #billingCartTable .billing-price-input {
                    min-width: 96px;
                    padding-left: 0.4rem;
                    padding-right: 0.4rem;
                }
                #billingCartTable .billing-hsn-input {
                    min-width: 78px;
                    max-width: 92px;
                    margin-left: auto;
                    margin-right: auto;
                    padding-left: 0.35rem;
                    padding-right: 0.35rem;
                }
                #billingCartTable .billing-gst-select {
                    min-width: 148px;
                    width: 100%;
                    padding-right: 2rem !important;
                    font-size: 12px;
                }
                #billingCartTable .billing-gst-rate-group {
                    min-width: 112px;
                    width: 100%;
                    flex-wrap: nowrap !important;
                }
                #billingCartTable .billing-gst-rate-input {
                    min-width: 64px;
                    font-size: 12px;
                    padding: 0.25rem 0.4rem !important;
                }
                #billingCartTable tr.billing-piece-row td {
                    padding-top: 0.55rem;
                    padding-bottom: 0.55rem;
                    background-color: #ffffff;
                    border-top: 1px dashed #e2e8f0;
                }
                #billingCartTable tr.billing-piece-row.billing-piece-row-alt td {
                    background-color: #f8fafc;
                }
            </style>
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="billingCartTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="min-width: 220px;">Product &amp; Details</th>
                                <th class="text-center" style="min-width: 138px;">Qty</th>
                                <th class="text-end text-nowrap" style="min-width: 90px;">MRP</th>
                                <th class="text-end text-nowrap" style="min-width: 90px;">Disc %</th>
                                <th class="text-end text-nowrap" style="min-width: 112px;">Price / Pc<br><small class="text-muted fw-normal">incl. tax</small></th>
                                <th class="text-center text-nowrap" style="min-width: 96px;">HSN</th>
                                <th style="min-width: 168px;">GST Details</th>
                                <th class="text-end text-nowrap" style="min-width: 88px;">Tax</th>
                                <th class="text-end text-nowrap" style="min-width: 100px;">Total</th>
                                <th style="min-width: 46px;"></th>
                            </tr>
                        </thead>
                        <tbody id="billCart">
                            <tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 d-block mb-2 text-secondary"></i>Scan or search for items to add to this bill.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Single Unified Invoice, Payment & Settlement Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-start">
                        <!-- Left Side: Invoice & Payment + Cash Calculator + Notes -->
                        <div class="col-lg-7 border-end-lg pe-lg-4">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-calendar3 text-primary me-1"></i>Bill date</label>
                                    <input class="form-control" type="date" name="bill_date" required value="<?= $escape($bill['bill_date'] ?? date('Y-m-d')) ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-credit-card text-primary me-1"></i>Payment method</label>
                                    <select class="form-select fw-semibold" name="payment_method" id="paymentMethod">
                                        <?php foreach (['CASH' => 'Cash', 'UPI' => 'UPI', 'CASH_UPI' => 'Cash + UPI', 'CARD' => 'Card', 'NET_BANKING' => 'Net Banking', 'CREDIT' => 'Credit'] as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= ($bill['payment_method'] ?? 'CASH') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Payment Status: Fully Paid or COD -->
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-wallet2 text-primary me-1"></i>Payment Status</label>
                                <div class="btn-group w-100" role="group" aria-label="Payment Status">
                                    <input type="radio" class="btn-check" name="payment_status" id="paymentStatusPaid" value="PAID" <?= $selectedPaymentStatus === 'PAID' ? 'checked' : '' ?> onchange="onPaymentStatusChange('PAID')">
                                    <label class="btn btn-outline-success fw-semibold" for="paymentStatusPaid"><i class="bi bi-check-circle-fill me-1"></i>Fully Paid</label>

                                    <input type="radio" class="btn-check" name="payment_status" id="paymentStatusCod" value="COD" <?= $selectedPaymentStatus === 'COD' ? 'checked' : '' ?> onchange="onPaymentStatusChange('COD')">
                                    <label class="btn btn-outline-warning text-dark fw-semibold" for="paymentStatusCod"><i class="bi bi-truck me-1"></i>COD (Partial / Cash on Delivery)</label>
                                </div>
                            </div>

                            <!-- COD Paid & Remaining Auto-Calculation -->
                            <div id="codPaymentFields" class="p-3 bg-warning-subtle bg-opacity-50 rounded-3 border border-warning-subtle mb-3 <?= $selectedPaymentStatus === 'COD' ? '' : 'd-none' ?>">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="paidAmountInput">
                                            <i class="bi bi-cash-coin text-success me-1"></i>Paid Amount (₹)
                                        </label>
                                        <input type="text" inputmode="decimal" autocomplete="off" name="paid_amount" id="paidAmountInput"
                                               class="form-control form-control-lg text-end fw-bold font-monospace fs-5 border-warning"
                                               placeholder="0.00" value="<?= $escape(number_format((float)($bill['paid_amount'] ?? 0), 2, '.', '')) ?>"
                                               oninput="updateCodAmounts()">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="remainingAmountInput">
                                            <i class="bi bi-hourglass-split text-danger me-1"></i>Remaining Amount (₹)
                                        </label>
                                        <input type="text" inputmode="decimal" name="remaining_amount" id="remainingAmountInput"
                                               class="form-control form-control-lg text-end fw-bold font-monospace text-danger fs-5 bg-white border-2 border-warning"
                                               readonly value="<?= $escape(number_format((float)($bill['remaining_amount'] ?? 0), 2, '.', '')) ?>">
                                    </div>
                                    <div class="col-12 mt-1">
                                        <div id="codStatusHelp" class="small text-muted fw-semibold">
                                            Enter the amount paid now; remaining COD balance is auto-calculated.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="splitPaymentFields" class="row g-2 mb-3 <?= $usesSplitPayment ? '' : 'd-none' ?>">
                                <div class="col-6"><label class="form-label small" for="cashAmount">Cash amount</label><input class="form-control font-monospace text-end" type="text" inputmode="decimal" autocomplete="off" name="cash_amount" id="cashAmount" value="<?= $escape($bill['cash_amount'] ?? '0.00') ?>"></div>
                                <div class="col-6"><label class="form-label small" for="upiAmount">UPI / GPay amount</label><input class="form-control font-monospace text-end" type="text" inputmode="decimal" autocomplete="off" name="upi_amount" id="upiAmount" value="<?= $escape($bill['upi_amount'] ?? '0.00') ?>"></div>
                                <div class="col-12"><small class="text-muted">Cash and UPI / GPay amounts must equal the bill total.</small></div>
                            </div>

                            <div id="upiPaymentFields" class="mb-3 p-3 bg-light rounded-3 border <?= $usesUpiPayment ? '' : 'd-none' ?>">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted" for="upiReference">UPI / GPay reference</label>
                                        <input class="form-control font-monospace" name="upi_reference" id="upiReference" maxlength="150" value="<?= $escape($bill['upi_reference'] ?? '') ?>" placeholder="e.g. 12-digit UTR number">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted" for="upiPaymentScreenshot">GPay payment screenshot <span class="fw-normal">(optional)</span></label>
                                        <input class="form-control" type="file" name="upi_payment_screenshot" id="upiPaymentScreenshot" accept="image/jpeg,image/png,image/webp,image/avif">
                                        <small class="text-muted">JPG, PNG, WEBP, or AVIF; max 5 MB.</small>
                                    </div>
                                </div>
                                <div id="upiScreenshotPreview" class="mt-2 <?= empty($bill['upi_payment_screenshot_url']) ? 'd-none' : '' ?>">
                                    <?php if (!empty($bill['upi_payment_screenshot_url'])): ?>
                                        <a href="<?= $escape(uploadUrl($bill['upi_payment_screenshot_url'])) ?>" target="_blank" rel="noopener">View current GPay screenshot</a>
                                    <?php endif; ?>
                                    <div id="upiScreenshotSelectedLabel" class="small text-primary d-none">New screenshot selected; it will replace the current proof.</div>
                                    <img id="upiScreenshotPreviewImage" class="img-thumbnail mt-2 <?= empty($bill['upi_payment_screenshot_url']) ? 'd-none' : '' ?>" <?= !empty($bill['upi_payment_screenshot_url']) ? 'src="' . $escape(uploadUrl($bill['upi_payment_screenshot_url'])) . '"' : '' ?> data-existing-src="<?= $escape(uploadUrl($bill['upi_payment_screenshot_url'] ?? '')) ?>" alt="GPay payment screenshot" style="max-width:180px;max-height:180px;object-fit:contain;">
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="cashGivenInput">
                                            <i class="bi bi-cash-stack text-success me-1"></i>Cash Given by Customer (₹)
                                        </label>
                                        <input type="text" inputmode="decimal" autocomplete="off" name="cash_given" id="cashGivenInput" 
                                               class="form-control form-control-lg text-end fw-bold font-monospace fs-5 border-success-subtle" 
                                               placeholder="0.00" value="<?= $escape($bill['cash_given'] ?? '') ?>" 
                                               oninput="updateCashAndChange()">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold text-dark mb-1" for="changeToReturnInput">
                                            <i class="bi bi-arrow-return-left text-primary me-1"></i>Change to Return (₹)
                                        </label>
                                        <input type="text" inputmode="decimal" name="change_returned" id="changeToReturnInput" 
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

                            <!-- Saved Coupons Card (Reference UI - visible once product is scanned) -->
                            <div class="mt-3 rounded-4 overflow-hidden border shadow-xs bg-white <?= empty($existingItems) ? 'd-none' : '' ?>" id="savedCouponsCard">
                                <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #eef4ff 0%, #f7faff 100%); border-bottom: 1px solid #e2e8f0;">
                                    <div class="fw-bold text-primary small lh-sm">
                                        Save extra by applying<br>coupons on every order
                                    </div>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 34px; height: 34px; flex-shrink: 0;">
                                        <i class="bi bi-percent fs-6"></i>
                                    </div>
                                </div>
                                <div class="px-3 py-2">
                                    <?php if (empty($availableCoupons)): ?>
                                        <div class="small text-muted py-2 text-center">
                                            No active coupons available. Create one in <strong>All Modules &rarr; Coupons</strong>.
                                        </div>
                                    <?php else: ?>
                                        <div id="couponCardList">
                                            <?php foreach ($availableCoupons as $cIdx => $cp): ?>
                                                <?php
                                                $cpType = strtoupper((string)($cp['discount_type'] ?? 'FIXED'));
                                                $cpVal = (float)($cp['discount_value'] ?? 0);
                                                $cpMin = (float)($cp['min_redemption_amount'] ?? 0);
                                                $cpRequired = max($cpMin, $cpType === 'FIXED' ? $cpVal : 0);
                                                $cpSaveText = $cpType === 'PERCENT'
                                                    ? 'Save ' . rtrim(rtrim(number_format($cpVal, 2), '0'), '.') . "% with '" . $cp['coupon_code'] . "'"
                                                    : 'Save ₹' . number_format($cpVal, 0) . " with '" . $cp['coupon_code'] . "'";
                                                $isCurrentlyApplied = ($bill['applied_coupon_code'] ?? '') === $cp['coupon_code'];
                                                ?>
                                                <div class="coupon-offer-item py-2 <?= $cIdx > 0 ? 'border-top border-dashed' : '' ?> <?= $cIdx >= 2 ? 'd-none extra-coupon-item' : '' ?>"
                                                     data-id="<?= (int)$cp['id'] ?>"
                                                     data-code="<?= e($cp['coupon_code']) ?>"
                                                     data-type="<?= e($cpType) ?>"
                                                     data-value="<?= number_format($cpVal, 2, '.', '') ?>"
                                                     data-min="<?= number_format($cpMin, 2, '.', '') ?>"
                                                     data-required="<?= number_format($cpRequired, 2, '.', '') ?>">
                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                        <div class="d-flex align-items-start gap-2 min-w-0">
                                                            <i class="bi bi-patch-check text-secondary mt-1 flex-shrink-0"></i>
                                                            <div class="min-w-0">
                                                                <div class="fw-bold text-dark small text-truncate"><?= e($cpSaveText) ?></div>
                                                                <div class="text-muted" style="font-size: 11px;">
                                                                    <?php if (!empty($cp['title'])): ?>
                                                                        <span><?= e($cp['title']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($cpRequired > 0): ?>
                                                                        <span><?= !empty($cp['title']) ? ' &bull; ' : '' ?>Min bill ₹<?= number_format($cpRequired, 2) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                                class="btn btn-sm <?= $isCurrentlyApplied ? 'btn-success' : 'btn-outline-danger' ?> fw-bold px-3 rounded-3 coupon-apply-btn flex-shrink-0"
                                                                onclick="toggleCouponCard('<?= e($cp['coupon_code']) ?>')">
                                                            <?= $isCurrentlyApplied ? 'REMOVE' : 'APPLY' ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($availableCoupons) > 2): ?>
                                            <div class="pt-1 pb-1 border-top mt-1">
                                                <a href="javascript:void(0)" id="viewAllCouponsLink" class="small fw-semibold text-secondary text-decoration-none" onclick="toggleAllCouponsList()">
                                                    View all coupons (<?= count($availableCoupons) ?>) <i class="bi bi-caret-right-fill"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <input type="hidden" name="applied_coupon_id" id="appliedCouponId" value="<?= (int)($bill['applied_coupon_id'] ?? 0) ?: '' ?>">
                                    <input type="hidden" name="applied_coupon_code" id="appliedCouponCode" value="<?= $escape($bill['applied_coupon_code'] ?? '') ?>">
                                    <div id="couponStatusHelp" class="small mt-1 <?= empty($bill['applied_coupon_code']) ? 'd-none' : '' ?>">
                                        <?php if (!empty($bill['applied_coupon_code'])): ?>
                                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Coupon <?= $escape($bill['applied_coupon_code']) ?> applied (-<?= formatCurrency((float)($bill['coupon_discount_amount'] ?? 0)) ?>)</span>
                                        <?php endif; ?>
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
                            <div class="d-flex justify-content-between py-1 text-success small <?= (float)($bill['coupon_discount_amount'] ?? 0) > 0 ? '' : 'd-none' ?>" id="couponDiscountRow">
                                <span><i class="bi bi-ticket-perforated me-1"></i>Coupon Discount</span>
                                <strong id="couponDiscountAmount" class="text-success font-monospace">-₹<?= number_format((float)($bill['coupon_discount_amount'] ?? 0), 2) ?></strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold fs-5 text-dark">Total</span>
                                <strong id="grandTotal" class="text-primary fs-3 font-monospace">₹0.00</strong>
                            </div>
                            <div id="splitBalance" class="small text-muted text-end mb-3"></div>

                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3 flex-wrap">
                                <a class="btn btn-outline-secondary px-3 py-2 fw-semibold" href="<?= url('production/billing') ?>">Cancel</a>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary btn-lg px-4 py-2 fw-bold shadow-sm" id="saveBillButton">
                                        <i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Bill' ?>
                                    </button>
                                    <button type="submit" name="move_to_outward" value="1" class="btn btn-success btn-lg px-4 py-2 fw-bold shadow-sm <?= $selectedBuyerType === 'COMPANY' ? '' : 'd-none' ?>" id="moveToOutwardButton">
                                        <i class="bi bi-truck me-1"></i>Move to Outward
                                    </button>
                                </div>
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
let lastPreCouponTotal = 0;
let appliedCouponState = <?= !empty($bill['applied_coupon_code']) ? json_encode([
    'coupon_id' => (int)($bill['applied_coupon_id'] ?? 0),
    'coupon_code' => (string)($bill['applied_coupon_code'] ?? ''),
    'discount_type' => 'FIXED',
    'discount_value' => (float)($bill['coupon_discount_amount'] ?? 0),
    'min_redemption_amount' => 0,
]) : 'null' ?>;
let customerSuggestTimer = null;
let currentCustomerPhone = '<?= $escape($bill['customer_phone'] ?? '') ?>';
let historyModalInstance = null;

function sanitizeNumericValue(rawValue, allowDecimal = true) {
    let cleaned = String(rawValue ?? '').replace(allowDecimal ? /[^0-9.]/g : /[^0-9]/g, '');
    if (!allowDecimal) {
        return cleaned;
    }
    const dotIndex = cleaned.indexOf('.');
    if (dotIndex !== -1) {
        cleaned = cleaned.slice(0, dotIndex + 1) + cleaned.slice(dotIndex + 1).replace(/\./g, '');
    }
    return cleaned;
}

function isValidNumericString(rawValue, allowDecimal = true) {
    const val = String(rawValue ?? '').trim();
    if (val === '') return false;
    return allowDecimal ? /^(?:\d+\.?\d*|\.\d+)$/.test(val) : /^\d+$/.test(val);
}

function bindNumericInputValidation(input, options = {}) {
    if (!input) return;
    const allowDecimal = options.allowDecimal !== false;
    const maxDecimals = typeof options.maxDecimals === 'number' ? options.maxDecimals : 2;
    const min = typeof options.min === 'number' ? options.min : 0;
    const max = typeof options.max === 'number' ? options.max : null;
    const allowEmpty = Boolean(options.allowEmpty);
    const formatOnBlur = options.formatOnBlur !== false;
    const defaultValue = options.defaultValue !== undefined
        ? options.defaultValue
        : (allowEmpty ? '' : (allowDecimal ? '0.00' : '1'));

    input.setAttribute('inputmode', allowDecimal ? 'decimal' : 'numeric');
    if (!allowDecimal) {
        input.setAttribute('pattern', '[0-9]*');
    }

    input.addEventListener('beforeinput', (e) => {
        if (e.data == null) return;
        if (!/^[0-9.]+$/.test(e.data) || (!allowDecimal && e.data.includes('.'))) {
            e.preventDefault();
            return;
        }
        if (allowDecimal && e.data.includes('.')) {
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            const remaining = input.value.slice(0, start) + input.value.slice(end);
            if (remaining.includes('.') || (e.data.match(/\./g) || []).length > 1) {
                e.preventDefault();
            }
        }
    });

    input.addEventListener('input', () => {
        const prev = input.value;
        let next = sanitizeNumericValue(prev, allowDecimal);
        if (allowDecimal && maxDecimals >= 0 && next.includes('.')) {
            const parts = next.split('.');
            if (parts[1].length > maxDecimals) {
                next = parts[0] + '.' + parts[1].slice(0, maxDecimals);
            }
        }
        if (next !== prev) {
            const pos = input.selectionStart ?? next.length;
            const diff = prev.length - next.length;
            input.value = next;
            const newPos = Math.max(0, pos - diff);
            if (document.activeElement === input && typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(newPos, newPos);
            }
        }
        input.classList.remove('is-invalid');
    });

    input.addEventListener('blur', () => {
        const trimmed = sanitizeNumericValue(input.value.trim(), allowDecimal);
        if (trimmed === '' || trimmed === '.') {
            input.value = allowEmpty ? '' : String(defaultValue);
            if (typeof options.onNormalize === 'function') {
                options.onNormalize(input.value);
            }
            return;
        }
        let num = allowDecimal ? parseFloat(trimmed) : parseInt(trimmed, 10);
        if (!Number.isFinite(num)) {
            input.value = String(defaultValue);
        } else {
            if (num < min) num = min;
            if (max !== null && num > max) num = max;
            if (!formatOnBlur) {
                input.value = allowDecimal ? String(num) : String(num);
            } else {
                input.value = allowDecimal ? num.toFixed(maxDecimals) : String(num);
            }
        }
        if (typeof options.onNormalize === 'function') {
            options.onNormalize(input.value);
        }
    });
}

function updateStaffName(sel) {
    const opt = sel.options[sel.selectedIndex];
    const name = opt ? opt.getAttribute('data-name') : '';
    document.getElementById('staffNameInput').value = name || '';
}

function onDestinationConcernChange(sel) {
    const opt = sel && sel.selectedOptions ? sel.selectedOptions[0] : null;
    if (!opt || !opt.value) {
        return;
    }
    const nameEl = document.getElementById('companyNameInput');
    const addrEl = document.getElementById('companyAddressInput');
    const phoneEl = document.getElementById('companyPhoneInput');
    const emailEl = document.getElementById('companyEmailInput');
    const gstEl = document.getElementById('companyGstinInput');
    if (nameEl) nameEl.value = opt.dataset.name || '';
    if (addrEl) addrEl.value = opt.dataset.address || '';
    if (phoneEl) phoneEl.value = opt.dataset.phone || '';
    if (emailEl) emailEl.value = opt.dataset.email || '';
    if (gstEl) gstEl.value = opt.dataset.gstin || '';
}

function onBuyerTypeChange(type) {
    document.getElementById('buyerType').value = type;
    const companyFields = document.getElementById('companyFields');
    const customerFields = document.getElementById('customerFields');
    const moveOutwardBtn = document.getElementById('moveToOutwardButton');
    if (type === 'COMPANY') {
        companyFields.classList.remove('d-none');
        customerFields.classList.add('d-none');
        if (moveOutwardBtn) moveOutwardBtn.classList.remove('d-none');
    } else {
        companyFields.classList.add('d-none');
        customerFields.classList.remove('d-none');
        if (moveOutwardBtn) moveOutwardBtn.classList.add('d-none');
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
                            <div class="small text-muted">Total Spent: <strong class="text-dark">${formatCurrency(summary.lifetime_spent || 0)}</strong></div>
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
                                        <strong class="text-primary fs-6">${formatCurrency(b.total_amount)}</strong>
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
                                <div class="small text-muted font-monospace">${it.variant_code || ''}</div>
                            </td>
                            <td>
                                ${it.size_name ? `<span class="badge bg-light text-dark border me-1">${it.size_name}</span>` : ''}
                                ${it.color_name ? `<span class="badge bg-light text-dark border">${it.color_name}</span>` : ''}
                            </td>
                            <td class="text-center fw-bold">${it.quantity}</td>
                            <td class="text-end">${formatCurrency(it.unit_price)}</td>
                            <td class="text-end fw-bold text-dark">${formatCurrency(it.total_amount)}</td>
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

function toggleAllCouponsList() {
    const extras = document.querySelectorAll('.extra-coupon-item');
    const link = document.getElementById('viewAllCouponsLink');
    if (!extras.length || !link) return;
    const isHidden = extras[0].classList.contains('d-none');
    extras.forEach(el => el.classList.toggle('d-none', !isHidden));
    link.innerHTML = isHidden
        ? 'Show fewer coupons <i class="bi bi-caret-up-fill"></i>'
        : `View all coupons (${document.querySelectorAll('.coupon-offer-item').length}) <i class="bi bi-caret-right-fill"></i>`;
}

function refreshCouponButtonsUI() {
    const activeCode = appliedCouponState ? appliedCouponState.coupon_code : '';
    document.querySelectorAll('.coupon-offer-item').forEach(itemEl => {
        const btn = itemEl.querySelector('.coupon-apply-btn');
        if (!btn) return;
        const isThis = activeCode !== '' && itemEl.dataset.code === activeCode;
        btn.textContent = isThis ? 'REMOVE' : 'APPLY';
        btn.className = isThis
            ? 'btn btn-sm btn-success fw-bold px-3 rounded-3 coupon-apply-btn flex-shrink-0'
            : 'btn btn-sm btn-outline-danger fw-bold px-3 rounded-3 coupon-apply-btn flex-shrink-0';
    });
}

function toggleCouponCard(code) {
    if (appliedCouponState && appliedCouponState.coupon_code === code) {
        removeAppliedCoupon();
        return;
    }
    const itemEl = document.querySelector(`.coupon-offer-item[data-code="${CSS.escape(code)}"]`);
    const statusHelp = document.getElementById('couponStatusHelp');
    if (!itemEl) return;

    const dType = String(itemEl.dataset.type || 'FIXED').toUpperCase();
    const dVal = parseFloat(itemEl.dataset.value || '0') || 0;
    const minReq = parseFloat(itemEl.dataset.min || '0') || 0;
    const requiredTotal = Math.max(minReq, dType === 'FIXED' ? dVal : 0);

    if (requiredTotal > 0 && lastPreCouponTotal < requiredTotal) {
        const shortfall = Math.max(0, requiredTotal - lastPreCouponTotal);
        if (statusHelp) {
            statusHelp.classList.remove('d-none');
            statusHelp.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Must reach ${formatCurrency(requiredTotal)} (add ${formatCurrency(shortfall)} more) to apply coupon ${code}.</span>`;
        }
        return;
    }

    const computedDiscount = dType === 'PERCENT'
        ? Math.round((lastPreCouponTotal * (dVal / 100)) * 100) / 100
        : Math.min(lastPreCouponTotal, dVal);

    appliedCouponState = {
        coupon_id: parseInt(itemEl.dataset.id || '0', 10),
        coupon_code: code,
        discount_type: dType,
        discount_value: dVal,
        min_redemption_amount: minReq,
    };
    document.getElementById('appliedCouponId').value = appliedCouponState.coupon_id;
    document.getElementById('appliedCouponCode').value = code;
    refreshCouponButtonsUI();
    if (statusHelp) {
        statusHelp.classList.remove('d-none');
        statusHelp.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Coupon ${code} applied (-${formatCurrency(computedDiscount)}).</span>`;
    }
    if (typeof window.__recalcBillingTotals === 'function') window.__recalcBillingTotals();
}

function removeAppliedCoupon() {
    appliedCouponState = null;
    const idEl = document.getElementById('appliedCouponId');
    if (idEl) idEl.value = '';
    const codeEl = document.getElementById('appliedCouponCode');
    if (codeEl) codeEl.value = '';
    refreshCouponButtonsUI();
    const statusHelp = document.getElementById('couponStatusHelp');
    if (statusHelp) {
        statusHelp.classList.add('d-none');
        statusHelp.innerHTML = '';
    }
    if (typeof window.__recalcBillingTotals === 'function') window.__recalcBillingTotals();
}

function getSelectedPaymentStatus() {
    const codRadio = document.getElementById('paymentStatusCod');
    return (codRadio && codRadio.checked) ? 'COD' : 'PAID';
}

function getEffectivePayableTarget(total) {
    if (getSelectedPaymentStatus() === 'COD') {
        const paidEl = document.getElementById('paidAmountInput');
        const paidVal = parseFloat(sanitizeNumericValue(paidEl?.value || '0', true)) || 0;
        return Math.max(0, Math.min(total, paidVal));
    }
    return total;
}

function onPaymentStatusChange(status) {
    const codFields = document.getElementById('codPaymentFields');
    const paidInput = document.getElementById('paidAmountInput');
    const isCod = status === 'COD';
    if (codFields) {
        codFields.classList.toggle('d-none', !isCod);
    }
    if (isCod && paidInput) {
        if (paidInput.value.trim() === '') {
            paidInput.value = '0.00';
        }
        updateCodAmounts(lastComputedGrandTotal);
        setTimeout(() => {
            paidInput.focus();
            paidInput.select();
        }, 50);
    } else {
        updateCodAmounts(lastComputedGrandTotal);
    }
    updateCashAndChange(lastComputedGrandTotal);
}

function updateCodAmounts(total) {
    if (total !== undefined) {
        lastComputedGrandTotal = total;
    } else {
        total = lastComputedGrandTotal;
    }
    const paidInput = document.getElementById('paidAmountInput');
    const remainingInput = document.getElementById('remainingAmountInput');
    const codStatusHelp = document.getElementById('codStatusHelp');
    if (!paidInput || !remainingInput) return;

    if (getSelectedPaymentStatus() !== 'COD') {
        remainingInput.value = '0.00';
        return;
    }

    const sanitizedPaid = sanitizeNumericValue(paidInput.value, true);
    if (paidInput.value !== sanitizedPaid) {
        paidInput.value = sanitizedPaid;
    }
    const paid = parseFloat(sanitizedPaid) || 0;
    const remaining = total - paid;

    if (paid > total + 0.009) {
        remainingInput.value = '0.00';
        remainingInput.className = 'form-control form-control-lg text-end fw-bold font-monospace text-danger fs-5 bg-white border-2 border-danger';
        if (codStatusHelp) {
            codStatusHelp.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Paid amount cannot exceed the bill total (${formatCurrency(total)}).</span>`;
        }
    } else {
        const clampedRem = Math.max(0, remaining);
        remainingInput.value = clampedRem.toFixed(2);
        remainingInput.className = clampedRem > 0
            ? 'form-control form-control-lg text-end fw-bold font-monospace text-danger fs-5 bg-white border-2 border-warning'
            : 'form-control form-control-lg text-end fw-bold font-monospace text-success fs-5 bg-white border-2 border-success';
        if (codStatusHelp) {
            codStatusHelp.innerHTML = clampedRem > 0
                ? `<span class="text-dark"><i class="bi bi-truck text-warning-emphasis me-1"></i> Remaining COD balance to collect: <strong class="text-danger">${formatCurrency(clampedRem)}</strong></span>`
                : `<span class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i> Full bill amount covered (₹0.00 remaining).</span>`;
        }
    }
    updateCashAndChange(total);
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
    const sanitizedCash = sanitizeNumericValue(cashGivenInput.value, true);
    if (cashGivenInput.value !== sanitizedCash) {
        cashGivenInput.value = sanitizedCash;
    }
    const cashGivenRaw = sanitizedCash.trim();
    if (cashGivenRaw === '' || cashGivenRaw === '.') {
        changeToReturnInput.value = '0.00';
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold font-monospace fs-5 bg-white border-2';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = paymentMethod === 'CASH_UPI'
                ? 'Enter the cash tendered to calculate change on the cash portion.'
                : 'Any cash above the payable amount is calculated as change to return.';
        }
        return;
    }

    const cashGiven = parseFloat(cashGivenRaw) || 0;
    const cashTarget = paymentMethod === 'CASH_UPI'
        ? Number(sanitizeNumericValue(document.getElementById('cashAmount')?.value || 0, true) || 0)
        : getEffectivePayableTarget(total);
    const change = cashGiven - cashTarget;

    if (change >= 0) {
        changeToReturnInput.value = change.toFixed(2);
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold font-monospace text-success fs-5 bg-white border-2 border-success';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = change > 0 
                ? `<span class="text-success fw-bold"><i class="bi bi-arrow-return-left me-1"></i> Return ${formatCurrency(change)} change to customer</span>` 
                : `<span class="text-success fw-bold"><i class="bi bi-check2-circle me-1"></i> Exact amount received</span>`;
        }
    } else {
        changeToReturnInput.value = '0.00';
        changeToReturnInput.className = 'form-control form-control-lg text-end fw-bold font-monospace text-danger fs-5 bg-white border-2 border-danger';
        if (changeStatusHelp) {
            changeStatusHelp.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i> ${formatCurrency(Math.abs(change))} remaining to collect</span>`;
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
    const money = value => window.formatCurrency(value);
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
    const cashGivenField = document.getElementById('cashGivenInput');
    bindNumericInputValidation(cashGivenField, {
        allowDecimal: true,
        maxDecimals: 2,
        min: 0,
        allowEmpty: true,
        onNormalize: () => updateCashAndChange()
    });
    const paidAmountField = document.getElementById('paidAmountInput');
    bindNumericInputValidation(paidAmountField, {
        allowDecimal: true,
        maxDecimals: 2,
        min: 0,
        allowEmpty: false,
        defaultValue: '0.00',
        onNormalize: () => updateCodAmounts()
    });
    bindNumericInputValidation(cashAmountInput, {
        allowDecimal: true,
        maxDecimals: 2,
        min: 0,
        allowEmpty: false,
        defaultValue: '0.00',
        onNormalize: updateTotals
    });
    bindNumericInputValidation(upiAmountInput, {
        allowDecimal: true,
        maxDecimals: 2,
        min: 0,
        allowEmpty: false,
        defaultValue: '0.00',
        onNormalize: updateTotals
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

    function getGroupKey(item) {
        return (item.qc_item_id && item.qc_item_id > 0)
            ? `qc_${item.qc_item_id}`
            : `var_${item.variant_id || 0}`;
    }

    function getItemKey(item, tag = null) {
        const t = tag || (Array.isArray(item.tags) && item.tags[0]) || '';
        return `${getGroupKey(item)}__${String(t).toUpperCase()}`;
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

    function poolTagsForGroup(item) {
        const groupKey = getGroupKey(item);
        const seen = new Set();
        const pool = [];
        const addTag = (t) => {
            const clean = String(t || '').trim();
            const upper = clean.toUpperCase();
            if (clean && !seen.has(upper)) {
                seen.add(upper);
                pool.push(clean);
            }
        };
        (item.available_tags || []).forEach(addTag);
        cart.forEach((entry) => {
            if (getGroupKey(entry) === groupKey) {
                (entry.available_tags || []).forEach(addTag);
                (entry.tags || []).forEach(addTag);
            }
        });
        return pool;
    }

    function selectableTags(item, excludeSelf = false) {
        const selfKey = excludeSelf ? getItemKey(item) : '';
        const assigned = new Set(assignedTags(selfKey).map(t => String(t).toUpperCase()));
        return poolTagsForGroup(item).filter(tag => !assigned.has(String(tag).toUpperCase()));
    }

    function updateUnitDetails() {
        if (!unitDetails || !unitSelect || !unitSelect.selectedOptions) {
            return;
        }
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

    function resolveVariantCode(item) {
        const rawCode = String(item.variant_code || '').trim().toUpperCase();
        if (rawCode !== '') return rawCode;
        const vId = parseInt(item.variant_id || 0, 10);
        if (vId > 0) return 'V' + String(vId).padStart(4, '0');
        const firstTag = Array.isArray(item.tags) && item.tags[0] ? String(item.tags[0]).trim() : '';
        const m = firstTag.match(/^([A-Z0-9]+)-\d+$/i);
        return m ? m[1].toUpperCase() : '';
    }

    function resolveDiscountPercentage(item) {
        const rawDisc = item.discount_percentage;
        if (rawDisc !== undefined && rawDisc !== null && String(rawDisc).trim() !== '') {
            const parsed = Number(rawDisc);
            if (Number.isFinite(parsed) && parsed > 0) {
                return Math.min(100, Math.max(0, parsed));
            }
        }
        const mrpVal = Number(item.mrp || 0);
        const unitVal = Number(item.unit_price || 0);
        if (mrpVal > 0 && unitVal >= 0 && unitVal < mrpVal) {
            return Math.min(100, Math.max(0, 100 - (unitVal / mrpVal) * 100));
        }
        return 0;
    }

    function expandRowByPieces(sourceKey, addCount) {
        const sourceItem = cart.get(sourceKey);
        if (!sourceItem || addCount <= 0) return;

        const availableToAssign = selectableTags(sourceItem);
        const remainingCount = availableToAssign.length;
        if (remainingCount <= 0) {
            showInventoryLimit(
                'Available stock limit reached',
                `All available piece(s) in stock for ${sourceItem.product_name} are already added to this bill.`
            );
            return;
        }

        const actualAdd = Math.min(addCount, remainingCount);
        if (addCount > remainingCount) {
            showInventoryLimit(
                'Available stock limit reached',
                `Only ${remainingCount} more piece(s) are available in stock for ${sourceItem.product_name}.`
            );
        }

        const pool = poolTagsForGroup(sourceItem);
        sourceItem.available_tags = [...pool];
        if (!Array.isArray(sourceItem.tags)) sourceItem.tags = [];
        sourceItem.tags.push(...availableToAssign.slice(0, actualAdd));

        renderCart();
        showSuccessToast();
    }

    function updateRowTotals(itemKey) {
        const item = cart.get(itemKey);
        if (!item) return;
        const row = cartBody.querySelector(`tr[data-row-key="${itemKey}"]`);
        if (row) {
            const qty = Math.max(1, Array.isArray(item.tags) ? item.tags.length : 1);
            const netLine = Number(item.unit_price || 0) * qty;
            const gstRate = Number(item.gst_percentage || 0);
            let taxable = netLine;
            let lineTax = 0;
            if (gstRate > 0) {
                taxable = netLine / (1 + gstRate / 100);
                lineTax = netLine - taxable;
            }
            const lineTotalCell = row.querySelector(`[data-total-for="${itemKey}"]`);
            const taxCell = row.querySelector(`[data-tax-for="${itemKey}"]`);
            if (lineTotalCell) lineTotalCell.textContent = money(netLine);
            if (taxCell) taxCell.textContent = money(lineTax);
        }
        updateTotals();
    }

    function renderCart() {
        cartBody.innerHTML = '';
        if (!cart.size) {
            cartBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 d-block mb-2 text-secondary"></i>Scan or search for items to add to this bill.</td></tr>';
            updateTotals();
            return;
        }

        let index = 0;
        cart.forEach((item, itemKey) => {
            const row = cartBody.insertRow();
            row.dataset.rowKey = itemKey;
            row.dataset.rowIndex = String(index);
            const pieceTag = (Array.isArray(item.tags) && item.tags[0]) ? String(item.tags[0]) : '';
            const qty = Math.max(1, Array.isArray(item.tags) ? item.tags.length : 1);
            const remainingStock = selectableTags(item).length;

            // 1. Product & Details (1 piece = 1 full editable row with Variant ID + Piece QR Tag)
            const productCell = row.insertCell();
            productCell.className = 'billing-product-cell';
            const productTitle = document.createElement('strong');
            productTitle.className = 'text-dark d-block';
            productTitle.textContent = item.product_name;
            productCell.append(productTitle);

            const badgeWrap = document.createElement('div');
            badgeWrap.className = 'd-flex flex-wrap align-items-center gap-1 my-1';
            const mainVarCode = resolveVariantCode(item);
            if (mainVarCode) {
                const mainVarBadge = document.createElement('span');
                mainVarBadge.className = 'badge bg-primary-subtle text-primary border font-monospace';
                mainVarBadge.textContent = `Variant: ${mainVarCode}`;
                badgeWrap.append(mainVarBadge);
            }
            const qrDisplayCode = mainVarCode || pieceTag;
            if (qrDisplayCode) {
                const tagBadge = document.createElement('span');
                tagBadge.className = 'badge bg-dark text-white font-monospace';
                tagBadge.textContent = `QR: ${qrDisplayCode}`;
                badgeWrap.append(tagBadge);
            }
            if (item.price_edited) {
                const priceBadge = document.createElement('span');
                priceBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning';
                priceBadge.title = item.price_alert || 'Price was recently revised';
                priceBadge.innerHTML = '<i class="bi bi-tag-fill me-1"></i>Price Checked';
                badgeWrap.append(priceBadge);
            }
            if (badgeWrap.childNodes.length) {
                productCell.append(badgeWrap);
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

            // 2. Quantity cell (each piece row is Qty 1; + or typing >1 spawns independent editable piece rows)
            const quantityCell = row.insertCell();
            quantityCell.className = 'text-center';

            const qtyGroup = document.createElement('div');
            qtyGroup.className = 'input-group input-group-sm mx-auto shadow-xs flex-nowrap billing-qty-group';

            const minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.className = 'btn btn-outline-secondary';
            minusBtn.innerHTML = '<i class="bi bi-dash"></i>';
            minusBtn.addEventListener('click', () => {
                if (Array.isArray(item.tags) && item.tags.length > 1) {
                    item.tags.pop();
                    renderCart();
                } else {
                    showStockToast('Minimum quantity is 1', 'To remove this item, click the trash button on the right.');
                }
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'text';
            quantityInput.inputMode = 'numeric';
            quantityInput.autocomplete = 'off';
            quantityInput.setAttribute('aria-label', 'Quantity');
            quantityInput.value = String(qty);
            quantityInput.className = 'form-control form-control-sm text-center fw-bold font-monospace billing-qty-input';
            quantityInput.dataset.qtyInputFor = itemKey;

            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'btn btn-outline-secondary';
            plusBtn.innerHTML = '<i class="bi bi-plus"></i>';
            plusBtn.addEventListener('click', () => {
                expandRowByPieces(itemKey, 1);
            });

            const applyRequestedQty = (rawVal) => {
                const requestedQuantity = parseInt(rawVal || '1', 10);
                const curQty = Array.isArray(item.tags) ? item.tags.length : 1;
                if (isNaN(requestedQuantity) || requestedQuantity < 1) {
                    quantityInput.value = String(curQty);
                    return;
                }
                if (requestedQuantity > curQty) {
                    expandRowByPieces(itemKey, requestedQuantity - curQty);
                } else if (requestedQuantity < curQty) {
                    item.tags = item.tags.slice(0, requestedQuantity);
                    renderCart();
                }
            };

            bindNumericInputValidation(quantityInput, {
                allowDecimal: false,
                min: 1,
                max: Math.max(1, qty + remainingStock),
                allowEmpty: false,
                defaultValue: String(qty),
                onNormalize: (val) => applyRequestedQty(val)
            });

            quantityInput.addEventListener('input', () => {
                const rawVal = quantityInput.value.trim();
                if (rawVal === '') return;
                const requestedQuantity = parseInt(rawVal, 10);
                if (isNaN(requestedQuantity) || requestedQuantity < 1) return;

                if (!Array.isArray(item.tags)) item.tags = [];
                const curQty = item.tags.length;
                if (requestedQuantity > curQty) {
                    const availableToAssign = selectableTags(item);
                    const maxTotal = curQty + availableToAssign.length;
                    if (requestedQuantity > maxTotal) {
                        showInventoryLimit(
                            'Available stock limit reached',
                            `Only ${availableToAssign.length} more piece(s) are available in stock for ${item.product_name}.`,
                            quantityInput
                        );
                    }
                    const actualAdd = Math.min(requestedQuantity - curQty, availableToAssign.length);
                    if (actualAdd > 0) {
                        item.tags.push(...availableToAssign.slice(0, actualAdd));
                    }
                    quantityInput.value = String(item.tags.length);
                } else if (requestedQuantity < curQty) {
                    item.tags = item.tags.slice(0, requestedQuantity);
                }

                hiddenTagsContainer.innerHTML = '';
                item.tags.forEach(tag => {
                    const hiddenTag = document.createElement('input');
                    hiddenTag.type = 'hidden';
                    hiddenTag.name = `items[${index}][tags][]`;
                    hiddenTag.value = tag;
                    hiddenTagsContainer.append(hiddenTag);
                });

                const stockEl = quantityCell.querySelector(`[data-stock-for="${itemKey}"]`);
                if (stockEl) {
                    stockEl.textContent = String(selectableTags(item).length);
                }
                updateTotals();
            });

            quantityInput.addEventListener('change', () => {
                applyRequestedQty(quantityInput.value);
            });

            qtyGroup.append(minusBtn, quantityInput, plusBtn);
            quantityCell.append(qtyGroup);

            const limitHelper = document.createElement('div');
            limitHelper.className = 'small text-muted text-center mt-1';
            limitHelper.innerHTML = `Stock: <strong class="text-dark" data-stock-for="${itemKey}">${remainingStock}</strong> pcs`;
            quantityCell.append(limitHelper);

            // Hidden tag input for this piece row
            const hiddenTagsContainer = document.createElement('div');
            hiddenTagsContainer.className = 'hidden-tags-container';
            (Array.isArray(item.tags) ? item.tags : []).forEach(tag => {
                const hiddenTag = document.createElement('input');
                hiddenTag.type = 'hidden';
                hiddenTag.name = `items[${index}][tags][]`;
                hiddenTag.value = tag;
                hiddenTagsContainer.append(hiddenTag);
            });
            quantityCell.append(hiddenTagsContainer);

            // 3. MRP cell
            const mrpCell = row.insertCell();
            mrpCell.className = 'text-end fw-semibold text-muted font-monospace text-nowrap';
            mrpCell.textContent = money(item.mrp);

            const isComp = Number(item.is_complementary || 0) === 1;
            if (isComp) {
                item.unit_price = 0;
            }

            // 4. Discount cell
            const discountCell = row.insertCell();
            const discountInput = document.createElement('input');
            discountInput.type = 'text';
            discountInput.inputMode = 'decimal';
            discountInput.autocomplete = 'off';
            discountInput.setAttribute('aria-label', 'Discount percentage');
            discountInput.className = 'form-control form-control-sm text-end font-monospace billing-rate-input';
            item.discount_percentage = isComp ? 100 : resolveDiscountPercentage(item);
            discountInput.value = item.discount_percentage.toFixed(2);
            discountInput.name = `items[${index}][discount_percentage]`;
            if (isComp) {
                discountInput.readOnly = true;
            }
            const applyDiscountValue = (rawDiscount) => {
                if (isComp) return;
                const parsed = parseFloat(rawDiscount);
                const discount = Number.isFinite(parsed) ? Math.min(100, Math.max(0, parsed)) : 0;
                item.discount_percentage = discount;
                item.unit_price = Number(item.mrp || 0) * (1 - discount / 100);
                priceInput.value = item.unit_price.toFixed(2);
                updateRowTotals(itemKey);
            };
            bindNumericInputValidation(discountInput, {
                allowDecimal: true,
                maxDecimals: 2,
                min: 0,
                max: 100,
                allowEmpty: false,
                defaultValue: '0.00',
                onNormalize: (val) => applyDiscountValue(val)
            });
            discountInput.addEventListener('input', () => {
                applyDiscountValue(discountInput.value);
            });
            discountCell.append(discountInput);

            // 5. Price / piece cell
            const priceCell = row.insertCell();
            const priceInput = document.createElement('input');
            priceInput.type = 'text';
            priceInput.inputMode = 'decimal';
            priceInput.autocomplete = 'off';
            priceInput.setAttribute('aria-label', 'Price per piece');
            priceInput.className = 'form-control form-control-sm text-end font-monospace fw-semibold billing-price-input';
            priceInput.value = isComp ? '0.00' : Number(item.unit_price || 0).toFixed(2);
            priceInput.name = `items[${index}][unit_price]`;
            if (isComp) {
                priceInput.readOnly = true;
                priceInput.classList.add('bg-light', 'text-info-emphasis');
                const compBadge = document.createElement('div');
                compBadge.className = 'small text-info-emphasis fw-semibold text-end mt-1';
                compBadge.innerHTML = '<i class="bi bi-gift-fill me-1"></i>Complementary';
                priceCell.append(priceInput, compBadge);
                const hiddenComp = document.createElement('input');
                hiddenComp.type = 'hidden';
                hiddenComp.name = `items[${index}][is_complementary]`;
                hiddenComp.value = '1';
                priceCell.append(hiddenComp);
            } else {
                const applyPriceValue = (rawPrice) => {
                    const parsed = parseFloat(rawPrice);
                    item.unit_price = Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
                    item.discount_percentage = Number(item.mrp || 0) > 0 ? Math.max(0, 100 - (item.unit_price / item.mrp) * 100) : 0;
                    discountInput.value = item.discount_percentage.toFixed(2);
                    updateRowTotals(itemKey);
                };
                bindNumericInputValidation(priceInput, {
                    allowDecimal: true,
                    maxDecimals: 2,
                    min: 0,
                    allowEmpty: false,
                    defaultValue: '0.00',
                    onNormalize: (val) => applyPriceValue(val)
                });
                priceInput.addEventListener('input', () => {
                    applyPriceValue(priceInput.value);
                });
                priceCell.append(priceInput);
            }

            // 6. HSN cell (next to Price / Pc incl. tax)
            const hsnCell = row.insertCell();
            hsnCell.className = 'text-center';
            const hsnInput = document.createElement('input');
            hsnInput.type = 'text';
            hsnInput.id = `billing-hsn-${index}`;
            hsnInput.maxLength = 10;
            hsnInput.required = true;
            hsnInput.inputMode = 'numeric';
            hsnInput.setAttribute('aria-label', 'HSN code');
            hsnInput.className = 'form-control form-control-sm text-center font-monospace billing-hsn-input';
            hsnInput.placeholder = 'HSN';
            hsnInput.name = `items[${index}][hsn_code]`;
            hsnInput.value = item.hsn_code || '6109';
            bindNumericInputValidation(hsnInput, {
                allowDecimal: false,
                min: 0,
                allowEmpty: true,
                formatOnBlur: false,
                defaultValue: ''
            });
            hsnInput.addEventListener('input', () => {
                item.hsn_code = hsnInput.value.trim();
            });
            hsnCell.append(hsnInput);

            // 7. GST details cell (compact stacked layout)
            const gstCell = row.insertCell();
            const taxGroup = document.createElement('div');
            taxGroup.className = 'd-flex flex-column gap-1';

            const taxModeSelect = document.createElement('select');
            taxModeSelect.className = 'form-select form-select-sm py-1 billing-gst-select';
            taxModeSelect.name = `items[${index}][gst_type]`;
            taxModeSelect.innerHTML = '<option value="BOTH">CGST + SGST</option><option value="IGST">IGST only</option><option value="EXEMPTED">Exempted (0%)</option>';
            taxModeSelect.value = item.gst_type || 'BOTH';

            const taxRateGroup = document.createElement('div');
            taxRateGroup.className = 'input-group input-group-sm flex-nowrap billing-gst-rate-group';

            const taxRateInput = document.createElement('input');
            taxRateInput.type = 'text';
            taxRateInput.inputMode = 'decimal';
            taxRateInput.autocomplete = 'off';
            taxRateInput.setAttribute('aria-label', 'GST percentage');
            taxRateInput.className = 'form-control form-control-sm text-end font-monospace billing-gst-rate-input';
            taxRateInput.name = `items[${index}][gst_percentage]`;
            taxRateInput.value = Number(item.gst_percentage || 0).toFixed(2);

            const taxRateAddon = document.createElement('span');
            taxRateAddon.className = 'input-group-text py-0 px-2 small text-muted';
            taxRateAddon.textContent = '%';
            taxRateGroup.append(taxRateInput, taxRateAddon);

            const applyGstRateValue = (rawRate) => {
                const parsed = parseFloat(rawRate);
                item.gst_percentage = Number.isFinite(parsed) ? Math.min(100, Math.max(0, parsed)) : 0;
                updateRowTotals(itemKey);
            };
            bindNumericInputValidation(taxRateInput, {
                allowDecimal: true,
                maxDecimals: 2,
                min: 0,
                max: 100,
                allowEmpty: false,
                defaultValue: '0.00',
                onNormalize: (val) => applyGstRateValue(val)
            });

            taxModeSelect.addEventListener('change', () => {
                item.gst_type = taxModeSelect.value;
                if (taxModeSelect.value === 'EXEMPTED') {
                    taxRateInput.value = '0.00';
                    item.gst_percentage = 0;
                } else if (Number(taxRateInput.value || 0) === 0) {
                    taxRateInput.value = '5.00';
                    item.gst_percentage = 5;
                }
                updateRowTotals(itemKey);
            });

            taxRateInput.addEventListener('input', () => {
                applyGstRateValue(taxRateInput.value);
            });

            taxGroup.append(taxModeSelect, taxRateGroup);
            gstCell.append(taxGroup);

            // 8. Tax amount cell
            const netLine = Number(item.unit_price || 0);
            const gstRate = Number(item.gst_percentage || 0);
            const lineTax = gstRate > 0 ? (netLine - (netLine / (1 + gstRate / 100))) : 0;

            const taxAmountCell = row.insertCell();
            taxAmountCell.className = 'text-end font-monospace text-muted text-nowrap';
            taxAmountCell.dataset.taxFor = itemKey;
            taxAmountCell.textContent = money(lineTax);

            // 9. Line Total cell
            const totalCell = row.insertCell();
            totalCell.className = 'text-end fw-bold font-monospace text-primary text-nowrap';
            totalCell.dataset.totalFor = itemKey;
            totalCell.textContent = money(netLine);

            // 10. Remove item cell
            const removeCell = row.insertCell();
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger btn-sm';
            removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
            removeBtn.title = 'Remove piece from cart';
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

        const savedCouponsCard = document.getElementById('savedCouponsCard');
        if (savedCouponsCard) {
            savedCouponsCard.classList.toggle('d-none', cart.size === 0);
            if (cart.size === 0 && appliedCouponState) {
                removeAppliedCoupon();
                return;
            }
        }

        lastPreCouponTotal = grandTotal;
        let couponDiscount = 0;
        const couponRow = document.getElementById('couponDiscountRow');
        const couponAmtEl = document.getElementById('couponDiscountAmount');
        const couponStatusEl = document.getElementById('couponStatusHelp');
        if (appliedCouponState && appliedCouponState.coupon_code) {
            const dType = String(appliedCouponState.discount_type || 'FIXED').toUpperCase();
            const dVal = Number(appliedCouponState.discount_value || 0);
            const minReq = Number(appliedCouponState.min_redemption_amount || 0);
            const requiredTotal = Math.max(minReq, dType === 'FIXED' ? dVal : 0);
            if (requiredTotal > 0 && grandTotal < requiredTotal) {
                couponDiscount = 0;
                const shortfall = Math.max(0, requiredTotal - grandTotal);
                if (couponStatusEl) {
                    couponStatusEl.classList.remove('d-none');
                    couponStatusEl.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Must reach ${money(requiredTotal)} (add ${money(shortfall)} more) to apply coupon ${appliedCouponState.coupon_code}.</span>`;
                }
            } else {
                couponDiscount = dType === 'PERCENT'
                    ? Math.round((grandTotal * (dVal / 100)) * 100) / 100
                    : Math.min(grandTotal, dVal);
                if (couponStatusEl) {
                    couponStatusEl.classList.remove('d-none');
                    couponStatusEl.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Coupon ${appliedCouponState.coupon_code} applied (-${money(couponDiscount)}).</span>`;
                }
            }
        }
        grandTotal = Math.max(0, Math.round((grandTotal - couponDiscount) * 100) / 100);
        if (couponRow && couponAmtEl) {
            couponRow.classList.toggle('d-none', couponDiscount <= 0);
            couponAmtEl.textContent = '-' + money(couponDiscount);
        }

        document.getElementById('grossTotal').textContent = money(gross);
        document.getElementById('discountTotal').textContent = money(discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('taxTotal').textContent = money(taxes);
        document.getElementById('grandTotal').textContent = money(grandTotal);

        const cash = Number(form.elements.cash_amount?.value || 0);
        const upi = Number(form.elements.upi_amount?.value || 0);
        const splitTarget = getEffectivePayableTarget(grandTotal);
        document.getElementById('splitBalance').textContent = document.getElementById('paymentMethod').value === 'CASH_UPI'
            ? `Cash + UPI: ${money(cash + upi)} / ${money(splitTarget)}`
            : '';

        // Auto update COD Remaining & Cash Tendered / Change Return
        updateCodAmounts(grandTotal);
        updateCashAndChange(grandTotal);
    }

    function addResult(item, selectedTag) {
        const availableTags = Array.isArray(item.available_tags) ? item.available_tags : [];
        const pool = poolTagsForGroup({ ...item, available_tags: availableTags });
        const unassigned = selectableTags({ ...item, available_tags: pool });

        if (!unassigned.length) {
            showInventoryLimit(
                'Available stock limit reached',
                `All available piece(s) in stock for ${item.product_name} are already in this bill.`
            );
            return;
        }

        let chosenTag = unassigned[0];
        if (selectedTag) {
            const upperSelected = String(selectedTag).toUpperCase();
            const matchUnassigned = unassigned.find(t => String(t).toUpperCase() === upperSelected);
            if (matchUnassigned) {
                chosenTag = matchUnassigned;
            } else {
                // Tag already in cart; allocate next available unassigned piece as a new editable row
                chosenTag = unassigned[0];
            }
        }

        const groupKey = getGroupKey(item);
        const existing = cart.get(groupKey);
        if (existing) {
            existing.available_tags = [...pool];
            if (!Array.isArray(existing.tags)) existing.tags = [];
            existing.tags.push(chosenTag);
        } else {
            const entry = {
                ...item,
                discount_percentage: resolveDiscountPercentage(item),
                available_tags: [...pool],
                tags: [chosenTag]
            };
            cart.set(groupKey, entry);
        }
        renderCart();
        showSuccessToast();

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
            .then(async res => {
                if (!res.ok) {
                    const text = await res.text();
                    let errMsg = `Server returned error (${res.status})`;
                    try {
                        const parsed = JSON.parse(text);
                        if (parsed.message) errMsg = parsed.message;
                    } catch(e) {}
                    showStockToast('Server Error', errMsg);
                    search.disabled = false;
                    search.focus();
                    return null;
                }
                return res.json();
            })
            .then(res => {
                if (!res) return;
                search.disabled = false;
                search.focus();
                if (!res.data || !res.data.items || !res.data.items.length) {
                    const msg = (res.data && res.data.message) ? res.data.message : `No finished-goods inventory matching "<strong>${needle}</strong>".`;
                    showStockToast('Item not found', msg);
                    return;
                }
                const exactMatch = res.data.items.find(it => {
                    const available = Array.isArray(it.available_tags) ? it.available_tags : [];
                    const vCode = (it.variant_code || '').toUpperCase().trim();
                    const vId = String(it.variant_id || '');
                    const uNeedle = needle.toUpperCase().trim();
                    const cleanNeedle = uNeedle.replace(/^#/, '');
                    const isVCodeQuery = /^V0*[0-9]+$/i.test(uNeedle);
                    const cleanNum = uNeedle.replace(/^V0*|^#0*/i, '');
                    const cleanNeedleNum = parseInt(cleanNum, 10);
                    const itVIdNum = parseInt(vId, 10);
                    const formattedVCode = (vCode === '' && itVIdNum > 0) ? ('V' + String(itVIdNum).padStart(4, '0')) : '';
                    const shortVCode = (vCode === '' && itVIdNum > 0) ? ('V' + itVIdNum) : '';

                    return available.some(t => t.toUpperCase() === uNeedle || t.toUpperCase().startsWith(uNeedle + '-') || t.toUpperCase().startsWith(uNeedle + '_'))
                        || (vCode !== '' && (vCode === uNeedle || ('V' + vCode) === uNeedle || vCode === ('V' + uNeedle)))
                        || (formattedVCode !== '' && formattedVCode === uNeedle)
                        || (shortVCode !== '' && shortVCode === uNeedle)
                        || (!isVCodeQuery && vId !== '0' && (vId === cleanNeedle || (cleanNeedleNum > 0 && cleanNeedleNum === itVIdNum)));
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
                        ${it.variant_code ? `<span class="badge bg-dark text-white font-monospace ms-1">${it.variant_code}</span>` : ''}
                        <span class="small text-muted ms-2">${it.size_name ? it.size_name : ''} ${it.color_name ? '· ' + it.color_name : ''}</span>
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

    // Populate initial items (1 editable row per piece)
    initialItems.forEach(it => {
        const tags = Array.isArray(it.tags) && it.tags.length ? it.tags : [''];
        tags.forEach(tag => {
            const entry = {
                ...it,
                discount_percentage: resolveDiscountPercentage(it),
                tags: tag ? [tag] : [],
                available_tags: Array.isArray(it.available_tags) && it.available_tags.length ? [...it.available_tags] : [...tags]
            };
            cart.set(getItemKey(entry, tag), entry);
        });
    });

    window.__recalcBillingTotals = updateTotals;
    const couponInputEl = document.getElementById('couponCodeInput');
    if (couponInputEl) {
        couponInputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCouponCode();
            }
        });
    }

    updateUnitDetails();
    if (unitSelect) unitSelect.addEventListener('change', updateUnitDetails);
    updatePaymentFields();
    renderCart();

    // Form submit validation
    form.addEventListener('submit', (e) => {
        if (!cart.size) {
            e.preventDefault();
            alert('Please add at least one finished-goods piece to this bill.');
            return;
        }
        if (lastComputedGrandTotal <= 0) {
            e.preventDefault();
            alert('Cannot create a POS bill with ₹0.00 total amount. Please add at least one chargeable item.');
            return;
        }
        if (appliedCouponState && appliedCouponState.coupon_code) {
            const dType = String(appliedCouponState.discount_type || 'FIXED').toUpperCase();
            const dVal = Number(appliedCouponState.discount_value || 0);
            const minReq = Number(appliedCouponState.min_redemption_amount || 0);
            const requiredTotal = Math.max(minReq, dType === 'FIXED' ? dVal : 0);
            if (requiredTotal > 0 && lastPreCouponTotal < requiredTotal) {
                e.preventDefault();
                alert(`Must reach ${money(requiredTotal)} to apply coupon ${appliedCouponState.coupon_code}, or remove the coupon.`);
                return;
            }
        }

        const numericChecks = [
            ...Array.from(cartBody.querySelectorAll('.billing-qty-input')).map(el => ({ el, decimal: false, label: 'Qty', min: 1 })),
            ...Array.from(cartBody.querySelectorAll('.billing-rate-input')).map(el => ({ el, decimal: true, label: 'Disc %', min: 0, max: 100 })),
            ...Array.from(cartBody.querySelectorAll('.billing-price-input')).map(el => ({ el, decimal: true, label: 'Price / Pc', min: 0 })),
            ...Array.from(cartBody.querySelectorAll('.billing-gst-rate-input')).map(el => ({ el, decimal: true, label: 'GST %', min: 0, max: 100 })),
        ];
        if (cashGivenField && cashGivenField.value.trim() !== '') {
            numericChecks.push({ el: cashGivenField, decimal: true, label: 'Cash Given by Customer', min: 0 });
        }
        const changeField = document.getElementById('changeToReturnInput');
        if (changeField) {
            numericChecks.push({ el: changeField, decimal: true, label: 'Change to Return', min: 0 });
        }
        if (paymentMethodSelect.value === 'CASH_UPI') {
            numericChecks.push({ el: cashAmountInput, decimal: true, label: 'Cash amount', min: 0 });
            numericChecks.push({ el: upiAmountInput, decimal: true, label: 'UPI / GPay amount', min: 0 });
        }
        if (getSelectedPaymentStatus() === 'COD' && paidAmountField) {
            numericChecks.push({ el: paidAmountField, decimal: true, label: 'Paid Amount', min: 0 });
        }

        for (const check of numericChecks) {
            const rawVal = check.el.value.trim();
            const validFormat = isValidNumericString(rawVal, check.decimal);
            const numVal = check.decimal ? parseFloat(rawVal) : parseInt(rawVal, 10);
            const inRange = Number.isFinite(numVal)
                && (check.min === undefined || numVal >= check.min)
                && (check.max === undefined || numVal <= check.max);
            if (!validFormat || !inRange) {
                e.preventDefault();
                check.el.classList.add('is-invalid');
                check.el.focus();
                alert(`Please enter a valid number for ${check.label}.`);
                return;
            }
        }

        if (getSelectedPaymentStatus() === 'COD' && paidAmountField) {
            const paidAmt = parseFloat(paidAmountField.value || '0') || 0;
            if (paidAmt > lastComputedGrandTotal + 0.01) {
                e.preventDefault();
                paidAmountField.classList.add('is-invalid');
                paidAmountField.focus();
                alert('COD Paid Amount cannot exceed the bill total.');
                return;
            }
        }

        if (paymentMethodSelect.value === 'CASH_UPI') {
            const cash = Number(cashAmountInput.value || 0);
            const upi = Number(upiAmountInput.value || 0);
            const splitTarget = getEffectivePayableTarget(lastComputedGrandTotal);
            if (Math.abs(cash + upi - splitTarget) > 0.01) {
                e.preventDefault();
                alert(getSelectedPaymentStatus() === 'COD'
                    ? 'Cash and UPI / GPay amounts must equal the COD Paid Amount.'
                    : 'Cash and UPI / GPay amounts must equal the bill total.');
            }
        }
    });
});
</script>
