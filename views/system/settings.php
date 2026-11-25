<?php
// views/system/settings.php
$activeTab = $_GET['tab'] ?? 'company';
$canEditSettings = hasPermission('settings', 'edit') || hasPermission('settings', 'create');
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-sliders text-primary me-2"></i>Unified System Settings Hub</h3>
            <p class="text-muted small mb-0">Manage enterprise store profile, tax details, operational switches, security rules, and alert policies.</p>
        </div>
    </div>

    <?php if (!$canEditSettings): ?>
        <div class="alert alert-secondary border-0 shadow-sm py-2 px-3 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-primary fs-5"></i>
            <div class="small">
                <strong>Read-Only Mode:</strong> Your assigned staff account has view-only access. Modifying company profiles, switches, and policies is restricted.
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs ms-3 border-0" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'company' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-pane">
                        <i class="bi bi-building me-2"></i>Company & Billing
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'switches' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="switches-tab" data-bs-toggle="tab" data-bs-target="#switches-pane">
                        <i class="bi bi-toggle-on me-2"></i>Master Switches
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'inventory' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-pane">
                        <i class="bi bi-boxes me-2"></i>Inventory Rules
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'security' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-pane">
                        <i class="bi bi-shield-lock me-2"></i>Security Policies
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'theme' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="theme-tab" data-bs-toggle="tab" data-bs-target="#theme-pane">
                        <i class="bi bi-palette me-2"></i>Theme &amp; Colors
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- TAB 1: COMPANY & BILLING -->
                <div class="tab-pane fade <?= $activeTab === 'company' ? 'show active' : '' ?>" id="company-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="group" value="company">
                        <fieldset <?= !$canEditSettings ? 'disabled' : '' ?>>
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shop me-2 text-primary"></i>Company Profile & Billing Details</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company / Brand Name</label>
                                <input type="text" name="settings[company_name]" class="form-control" value="<?= e($settings['company']['company_name'] ?? 'GRM Textiles') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tagline / Subtitle</label>
                                <input type="text" name="settings[company_tagline]" class="form-control" value="<?= e($settings['company']['company_tagline'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Support Email</label>
                                <input type="email" name="settings[company_email]" class="form-control" value="<?= e($settings['company']['company_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Support Phone</label>
                                <input type="text" name="settings[company_phone]" class="form-control" value="<?= e($settings['company']['company_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">GSTIN Number</label>
                                <input type="text" name="settings[company_gstin]" class="form-control font-monospace" value="<?= e($settings['company']['company_gstin'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PAN Number</label>
                                <input type="text" name="settings[company_pan]" class="form-control font-monospace" value="<?= e($settings['company']['company_pan'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Currency Symbol</label>
                                <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= e($settings['company']['currency_symbol'] ?? '₹') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Registered Office Address</label>
                                <textarea name="settings[company_address]" class="form-control" rows="2"><?= e($settings['company']['company_address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Invoice Footer Notes / Terms</label>
                                <textarea name="settings[invoice_footer]" class="form-control" rows="2"><?= e($settings['company']['invoice_footer'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top text-end">
                            <?php if ($canEditSettings): ?>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Company Profile</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary px-4" disabled><i class="bi bi-lock me-1"></i> Edit Restricted (View Only)</button>
                            <?php endif; ?>
                        </div>
                        </fieldset>
                    </form>
                </div>

                <!-- TAB 2: MASTER SWITCHES -->
                <div class="tab-pane fade <?= $activeTab === 'switches' ? 'show active' : '' ?>" id="switches-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="group" value="switches">
                        <fieldset <?= !$canEditSettings ? 'disabled' : '' ?>>
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-toggle2-on me-2 text-primary"></i>Operational Master Switches</h5>
                        
                        <div class="list-group list-group-flush mb-3">
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">System Maintenance Mode</div>
                                    <div class="small text-muted">Disables non-admin POS and shop access while performing database maintenance.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[maintenance_mode]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[maintenance_mode]" value="true" <?= ($settings['switches']['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Disable POS Guest Billing</div>
                                    <div class="small text-muted">Requires shop staff to record a valid customer phone number before completing sales.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[disable_pos_guest]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[disable_pos_guest]" value="true" <?= ($settings['switches']['disable_pos_guest'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Auto-Approve Production Programs</div>
                                    <div class="small text-muted">Automatically marks newly created production programs as APPROVED without manager sign-off.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[auto_approve_program]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[auto_approve_program]" value="true" <?= ($settings['switches']['auto_approve_program'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Allow Negative Stock Movement</div>
                                    <div class="small text-muted">Permits inventory deduction even when recorded balance is 0 (Not recommended).</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[allow_negative_stock]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[allow_negative_stock]" value="true" <?= ($settings['switches']['allow_negative_stock'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <?php if ($canEditSettings): ?>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Master Switches</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary px-4" disabled><i class="bi bi-lock me-1"></i> Edit Restricted (View Only)</button>
                            <?php endif; ?>
                        </div>
                        </fieldset>
                    </form>
                </div>

                <!-- TAB 3: INVENTORY RULES -->
                <div class="tab-pane fade <?= $activeTab === 'inventory' ? 'show active' : '' ?>" id="inventory-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="group" value="inventory">
                        <fieldset <?= !$canEditSettings ? 'disabled' : '' ?>>
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2 text-primary"></i>Stock & Inventory Threshold Rules</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Default Low Stock Alert Threshold (Units)</label>
                                <input type="number" name="settings[low_stock_threshold]" class="form-control" value="<?= e($settings['inventory']['low_stock_threshold'] ?? '10') ?>" min="1">
                                <div class="form-text">Triggers automated low-stock warnings when available inventory drops below this quantity.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Default Receiving Warehouse ID</label>
                                <input type="number" name="settings[default_warehouse_id]" class="form-control" value="<?= e($settings['inventory']['default_warehouse_id'] ?? '1') ?>">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="settings[audit_stock_movement]" value="false">
                                    <input class="form-check-input" type="checkbox" name="settings[audit_stock_movement]" value="true" id="auditStockCheck" <?= ($settings['inventory']['audit_stock_movement'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="auditStockCheck">Strict Stock Movement Audit Logging</label>
                                    <div class="form-text">Logs every individual stock increase, decrease, reserve, and transfer in `stock_transactions`.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <?php if ($canEditSettings): ?>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Inventory Rules</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary px-4" disabled><i class="bi bi-lock me-1"></i> Edit Restricted (View Only)</button>
                            <?php endif; ?>
                        </div>
                        </fieldset>
                    </form>
                </div>

                <!-- TAB 4: SECURITY POLICIES -->
                <div class="tab-pane fade <?= $activeTab === 'security' ? 'show active' : '' ?>" id="security-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="group" value="security">
                        <fieldset <?= !$canEditSettings ? 'disabled' : '' ?>>
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Security & Authentication Policies</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Session Timeout Duration (Minutes)</label>
                                <input type="number" name="settings[session_timeout_mins]" class="form-control" value="<?= e($settings['security']['session_timeout_mins'] ?? '60') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Max Invalid Login Attempts</label>
                                <input type="number" name="settings[max_login_attempts]" class="form-control" value="<?= e($settings['security']['max_login_attempts'] ?? '5') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Lockout Duration (Minutes)</label>
                                <input type="number" name="settings[lockout_duration_mins]" class="form-control" value="<?= e($settings['security']['lockout_duration_mins'] ?? '15') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input type="hidden" name="settings[force_password_change]" value="false">
                                    <input class="form-check-input" type="checkbox" name="settings[force_password_change]" value="true" id="forcePassCheck" <?= ($settings['security']['force_password_change'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="forcePassCheck">Force Password Change on Admin Reset</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <?php if ($canEditSettings): ?>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Security Policies</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary px-4" disabled><i class="bi bi-lock me-1"></i> Edit Restricted (View Only)</button>
                            <?php endif; ?>
                        </div>
                        </fieldset>
                    </form>
                </div>

                <!-- TAB 5: THEME & COLORS -->
                <?php
                $themeCfg = SettingsService::getThemeConfig();
                $presetsList = $themePresets ?? SettingsService::getThemePresets();
                $currentPreset = $themeCfg['preset'] ?? 'indigo';
                ?>
                <div class="tab-pane fade <?= $activeTab === 'theme' ? 'show active' : '' ?>" id="theme-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>" id="themeSettingsForm">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="group" value="theme">
                        <input type="hidden" name="settings[theme_preset]" id="themePresetInput" value="<?= e($currentPreset) ?>">
                        <fieldset <?= !$canEditSettings ? 'disabled' : '' ?>>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-palette-fill me-2 text-primary"></i>Software Theme &amp; Color Customizer</h5>
                                <p class="text-muted small mb-0">Choose a curated preset or pick custom brand colors. Changes preview live immediately before saving.</p>
                            </div>
                        </div>

                        <!-- Quick Color Presets -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Quick Color Presets</label>
                            <div class="row g-2">
                                <?php foreach ($presetsList as $presetId => $preset): ?>
                                    <?php $isPresetActive = ($currentPreset === $presetId); ?>
                                    <div class="col-sm-6 col-lg-4">
                                        <button type="button"
                                                class="btn w-100 text-start p-3 border rounded-3 bg-white shadow-xs theme-preset-btn <?= $isPresetActive ? 'border-primary border-2 bg-light' : '' ?>"
                                                data-preset="<?= e($presetId) ?>"
                                                data-primary="<?= e($preset['primary']) ?>"
                                                data-sidebar-bg="<?= e($preset['sidebar_bg']) ?>"
                                                data-sidebar-text="<?= e($preset['sidebar_text']) ?>"
                                                data-navbar-bg="<?= e($preset['navbar_bg']) ?>"
                                                data-page-bg="<?= e($preset['page_bg']) ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-semibold text-dark small"><?= e($preset['label']) ?></span>
                                                <i class="bi bi-check-circle-fill text-primary preset-check <?= $isPresetActive ? '' : 'd-none' ?>"></i>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="rounded-circle border" style="width: 20px; height: 20px; background: <?= e($preset['sidebar_bg']) ?>;" title="Sidebar"></span>
                                                <span class="rounded-circle border" style="width: 20px; height: 20px; background: <?= e($preset['primary']) ?>;" title="Primary Accent"></span>
                                                <span class="rounded-circle border" style="width: 20px; height: 20px; background: <?= e($preset['navbar_bg']) ?>;" title="Top Bar"></span>
                                                <span class="rounded-circle border" style="width: 20px; height: 20px; background: <?= e($preset['page_bg']) ?>;" title="Page Background"></span>
                                            </div>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-4 align-items-start">
                            <!-- Custom Color Pickers -->
                            <div class="col-lg-7">
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="row g-3">
                                        <?php
                                        $colorFields = [
                                            ['key' => 'theme_primary',      'label' => 'Primary Accent Color',       'desc' => 'Buttons, links, badges, active highlights', 'val' => $themeCfg['primary']],
                                            ['key' => 'theme_sidebar_bg',   'label' => 'Sidebar Background',         'desc' => 'Left navigation menu background',           'val' => $themeCfg['sidebar_bg']],
                                            ['key' => 'theme_sidebar_text', 'label' => 'Sidebar Menu Text',          'desc' => 'Inactive navigation links and icons',       'val' => $themeCfg['sidebar_text']],
                                            ['key' => 'theme_navbar_bg',    'label' => 'Top Header Background',      'desc' => 'Top breadcrumb and user toolbar',           'val' => $themeCfg['navbar_bg']],
                                            ['key' => 'theme_page_bg',      'label' => 'Page Content Background',    'desc' => 'Main workspace canvas background',          'val' => $themeCfg['page_bg']],
                                        ];
                                        foreach ($colorFields as $cf):
                                            $fieldId = 'picker_' . $cf['key'];
                                        ?>
                                            <div class="col-sm-6">
                                                <label class="form-label fw-semibold mb-1" for="<?= e($fieldId) ?>"><?= e($cf['label']) ?></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="color"
                                                           class="form-control form-control-color theme-color-picker"
                                                           id="<?= e($fieldId) ?>"
                                                           name="settings[<?= e($cf['key']) ?>]"
                                                           value="<?= e($cf['val']) ?>"
                                                           data-theme-key="<?= e($cf['key']) ?>"
                                                           title="Choose <?= e($cf['label']) ?>">
                                                    <input type="text"
                                                           class="form-control font-monospace text-uppercase theme-hex-input"
                                                           maxlength="7"
                                                           value="<?= e($cf['val']) ?>"
                                                           data-hex-for="<?= e($fieldId) ?>"
                                                           aria-label="<?= e($cf['label']) ?> hex code">
                                                </div>
                                                <div class="form-text small"><?= e($cf['desc']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Mini UI Preview -->
                            <div class="col-lg-5">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Live Preview</label>
                                <div class="border rounded-3 overflow-hidden shadow-sm" id="themeMiniPreview" style="min-height: 230px; background: <?= e($themeCfg['page_bg']) ?>;">
                                    <div class="d-flex" style="min-height: 230px;">
                                        <div id="previewSidebar" class="p-3 d-flex flex-column gap-2" style="width: 115px; background: <?= e($themeCfg['sidebar_bg']) ?>; color: <?= e($themeCfg['sidebar_text']) ?>;">
                                            <div class="fw-bold small text-white d-flex align-items-center gap-1 mb-1">
                                                <i class="bi bi-boxes" id="previewBrandIcon" style="color: <?= e($themeCfg['primary']) ?>;"></i>
                                                <span>GRM</span>
                                            </div>
                                            <div class="small px-2 py-1 rounded text-white fw-semibold" id="previewActiveLink" style="background: rgba(<?= e($themeCfg['primary_rgb']) ?>, 0.22); border-left: 3px solid <?= e($themeCfg['primary']) ?>; font-size: 11px;">Dashboard</div>
                                            <div class="small px-2 py-1" style="font-size: 11px;">All Modules</div>
                                            <div class="small px-2 py-1" style="font-size: 11px;">Billing</div>
                                            <div class="small px-2 py-1" style="font-size: 11px;">Settings</div>
                                        </div>
                                        <div class="flex-grow-1 d-flex flex-column">
                                            <div id="previewNavbar" class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center" style="background: <?= e($themeCfg['navbar_bg']) ?>;">
                                                <span class="small fw-semibold text-dark" style="font-size: 11px;"><i class="bi bi-house-door me-1" id="previewHomeIcon" style="color: <?= e($themeCfg['primary']) ?>;"></i>Home / Settings</span>
                                                <span class="badge" id="previewBadge" style="background: <?= e($themeCfg['primary']) ?>; font-size: 10px;">ADMIN</span>
                                            </div>
                                            <div class="p-3 flex-grow-1">
                                                <div class="card border-0 shadow-xs mb-2">
                                                    <div class="card-body p-2">
                                                        <div class="fw-bold small text-dark mb-1">Sample Card</div>
                                                        <div class="text-muted" style="font-size: 11px;">Live color styling across buttons &amp; links</div>
                                                        <div class="mt-2 d-flex gap-2">
                                                            <span class="btn btn-sm py-0 px-2 text-white" id="previewPrimaryBtn" style="background: <?= e($themeCfg['primary']) ?>; font-size: 11px;">Primary Action</span>
                                                            <span class="btn btn-sm py-0 px-2 border" id="previewOutlineBtn" style="color: <?= e($themeCfg['primary']) ?>; border-color: <?= e($themeCfg['primary']) ?> !important; font-size: 11px;">Outline</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <?php if ($canEditSettings): ?>
                                <button type="submit" name="reset_theme_defaults" value="1" class="btn btn-outline-secondary" onclick="return confirm('Reset software colors back to Indigo Classic defaults?');">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                                </button>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Theme &amp; Colors</button>
                            <?php else: ?>
                                <span></span>
                                <button type="button" class="btn btn-outline-secondary px-4" disabled><i class="bi bi-lock me-1"></i> Edit Restricted (View Only)</button>
                            <?php endif; ?>
                        </div>
                        </fieldset>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(tabBtn => {
        tabBtn.addEventListener('shown.bs.tab', (e) => {
            document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(btn => {
                btn.classList.remove('active', 'text-primary', 'border-bottom', 'border-primary', 'border-3');
                btn.classList.add('text-muted');
            });
            e.target.classList.add('active', 'text-primary', 'border-bottom', 'border-primary', 'border-3');
            e.target.classList.remove('text-muted');
        });
    });

    const pickers = {
        theme_primary: document.getElementById('picker_theme_primary'),
        theme_sidebar_bg: document.getElementById('picker_theme_sidebar_bg'),
        theme_sidebar_text: document.getElementById('picker_theme_sidebar_text'),
        theme_navbar_bg: document.getElementById('picker_theme_navbar_bg'),
        theme_page_bg: document.getElementById('picker_theme_page_bg')
    };
    const presetInput = document.getElementById('themePresetInput');
    const presetButtons = Array.from(document.querySelectorAll('.theme-preset-btn'));
    const hexInputs = Array.from(document.querySelectorAll('.theme-hex-input'));

    function hexToRgbTuple(hex) {
        const clean = String(hex || '#4f46e5').replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(clean)) return '79, 70, 229';
        const r = parseInt(clean.slice(0, 2), 16);
        const g = parseInt(clean.slice(2, 4), 16);
        const b = parseInt(clean.slice(4, 6), 16);
        return `${r}, ${g}, ${b}`;
    }

    function shadeHex(hex, delta) {
        const clean = String(hex || '#4f46e5').replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(clean)) return '#4338ca';
        const clamp = v => Math.max(0, Math.min(255, v));
        const r = clamp(parseInt(clean.slice(0, 2), 16) + delta).toString(16).padStart(2, '0');
        const g = clamp(parseInt(clean.slice(2, 4), 16) + delta).toString(16).padStart(2, '0');
        const b = clamp(parseInt(clean.slice(4, 6), 16) + delta).toString(16).padStart(2, '0');
        return `#${r}${g}${b}`;
    }

    function mixWithWhite(hex, ratio) {
        const clean = String(hex || '#4f46e5').replace('#', '');
        if (!/^[0-9a-fA-F]{6}$/.test(clean)) return '#eef2ff';
        const mix = c => Math.round(c * (1 - ratio) + 255 * ratio).toString(16).padStart(2, '0');
        return `#${mix(parseInt(clean.slice(0, 2), 16))}${mix(parseInt(clean.slice(2, 4), 16))}${mix(parseInt(clean.slice(4, 6), 16))}`;
    }

    function applyLiveThemePreview() {
        if (!pickers.theme_primary) return;
        const primary = pickers.theme_primary.value;
        const primaryHover = shadeHex(primary, -18);
        const primaryLight = mixWithWhite(primary, 0.90);
        const primaryRgb = hexToRgbTuple(primary);
        const sidebarBg = pickers.theme_sidebar_bg.value;
        const sidebarText = pickers.theme_sidebar_text.value;
        const navbarBg = pickers.theme_navbar_bg.value;
        const pageBg = pickers.theme_page_bg.value;

        // Update root CSS variables for real-time preview across the actual page
        const root = document.documentElement;
        root.style.setProperty('--primary', primary);
        root.style.setProperty('--primary-hover', primaryHover);
        root.style.setProperty('--primary-light', primaryLight);
        root.style.setProperty('--primary-rgb', primaryRgb);
        root.style.setProperty('--bs-primary', primary);
        root.style.setProperty('--bs-primary-rgb', primaryRgb);
        root.style.setProperty('--sidebar-bg', sidebarBg);
        root.style.setProperty('--sidebar-text', sidebarText);
        root.style.setProperty('--navbar-bg', navbarBg);
        root.style.setProperty('--page-bg', pageBg);

        // Update mini preview card
        const mini = document.getElementById('themeMiniPreview');
        if (mini) mini.style.background = pageBg;
        const pSidebar = document.getElementById('previewSidebar');
        if (pSidebar) {
            pSidebar.style.background = sidebarBg;
            pSidebar.style.color = sidebarText;
        }
        const pActive = document.getElementById('previewActiveLink');
        if (pActive) {
            pActive.style.background = `rgba(${primaryRgb}, 0.22)`;
            pActive.style.borderLeftColor = primary;
        }
        const pNavbar = document.getElementById('previewNavbar');
        if (pNavbar) pNavbar.style.background = navbarBg;
        const pPrimaryIds = ['previewBrandIcon', 'previewHomeIcon'];
        pPrimaryIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.color = primary;
        });
        const pBadge = document.getElementById('previewBadge');
        if (pBadge) pBadge.style.background = primary;
        const pBtn = document.getElementById('previewPrimaryBtn');
        if (pBtn) pBtn.style.background = primary;
        const pOutline = document.getElementById('previewOutlineBtn');
        if (pOutline) {
            pOutline.style.color = primary;
            pOutline.style.borderColor = primary;
        }
    }

    function syncPresetSelection(activeId) {
        if (presetInput) presetInput.value = activeId;
        presetButtons.forEach(btn => {
            const isMatch = btn.dataset.preset === activeId;
            btn.classList.toggle('border-primary', isMatch);
            btn.classList.toggle('border-2', isMatch);
            btn.classList.toggle('bg-light', isMatch);
            const chk = btn.querySelector('.preset-check');
            if (chk) chk.classList.toggle('d-none', !isMatch);
        });
    }

    presetButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const mapping = {
                theme_primary: btn.dataset.primary,
                theme_sidebar_bg: btn.dataset.sidebarBg,
                theme_sidebar_text: btn.dataset.sidebarText,
                theme_navbar_bg: btn.dataset.navbarBg,
                theme_page_bg: btn.dataset.pageBg
            };
            Object.entries(mapping).forEach(([key, val]) => {
                if (pickers[key] && val) {
                    pickers[key].value = val;
                    const hexEl = document.querySelector(`[data-hex-for="${pickers[key].id}"]`);
                    if (hexEl) hexEl.value = val.toUpperCase();
                }
            });
            syncPresetSelection(btn.dataset.preset || 'custom');
            applyLiveThemePreview();
        });
    });

    Object.values(pickers).forEach(picker => {
        if (!picker) return;
        picker.addEventListener('input', () => {
            const hexEl = document.querySelector(`[data-hex-for="${picker.id}"]`);
            if (hexEl) hexEl.value = picker.value.toUpperCase();
            syncPresetSelection('custom');
            applyLiveThemePreview();
        });
    });

    hexInputs.forEach(hexEl => {
        hexEl.addEventListener('input', () => {
            let val = hexEl.value.trim();
            if (val && !val.startsWith('#')) val = '#' + val;
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                const targetPicker = document.getElementById(hexEl.dataset.hexFor);
                if (targetPicker) {
                    targetPicker.value = val.toLowerCase();
                    syncPresetSelection('custom');
                    applyLiveThemePreview();
                }
            }
        });
        hexEl.addEventListener('blur', () => {
            const targetPicker = document.getElementById(hexEl.dataset.hexFor);
            if (targetPicker) hexEl.value = targetPicker.value.toUpperCase();
        });
    });
});
</script>
