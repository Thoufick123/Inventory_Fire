<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0"><i class="bi bi-shield-check text-primary me-2"></i>Module Entitlement Matrix</h2>
            <span class="badge bg-primary text-white font-monospace fs-6"><?= e($concern['code']) ?></span>
            <span class="badge <?= $concern['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> border">
                <?= $concern['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>
        <p class="text-muted small mb-0">Configure operational capabilities and functional access for <strong><?= e($concern['name']) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/concerns/edit/' . $concern['id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit Concern Info
        </a>
        <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Concerns
        </a>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-primary"></i>Operational Modules Matrix</h6>
            <div class="small text-muted">Toggle capabilities enabled for this Concern entity. Unchecked modules will be automatically restricted in sidebar navigation.</div>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllModules(true)">Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllModules(false)">Clear All</button>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="POST" action="<?= url('admin/concerns/modules/' . $concern['id']) ?>">
            <?= CSRF::field() ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-secondary">
                            <th style="width: 50px;" class="text-center">#</th>
                            <th style="width: 260px;">Module Name</th>
                            <th style="width: 150px;">Module Key</th>
                            <th>Description &amp; Workflows</th>
                            <th style="width: 140px;" class="text-center">Entitlement</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $idx = 0;
                    foreach ($standardModules as $mKey => $m): 
                        $idx++;
                        $isEnabled = !empty($currentStates[$mKey]);
                    ?>
                        <tr>
                            <td class="text-center text-muted small fw-semibold"><?= $idx ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle p-2 bg-<?= e($m['badge_color']) ?>-subtle text-<?= e($m['badge_color']) ?>">
                                        <i class="<?= e($m['icon']) ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark"><?= e($m['name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($mKey) ?></span>
                            </td>
                            <td>
                                <div class="small text-muted"><?= e($m['desc']) ?></div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block fs-5 mb-0">
                                    <input class="form-check-input module-checkbox" type="checkbox" name="modules[]" value="<?= e($mKey) ?>" id="mod_<?= e($mKey) ?>" <?= $isEnabled ? 'checked' : '' ?>>
                                    <label class="form-check-label visually-hidden" for="mod_<?= e($mKey) ?>">Enable <?= e($m['name']) ?></label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Changes take effect immediately for all staff logged into this Concern context.
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('admin/concerns') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Save Module Entitlements
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAllModules(state) {
    document.querySelectorAll('.module-checkbox').forEach(cb => cb.checked = state);
}
</script>
