<?php
/**
 * Global Admin Target Concern Selector Partial
 * Shown ONLY when Super Admin is in the Global Admin context (currentConcernId() === null).
 */
if (function_exists('isSuperAdmin') && isSuperAdmin() && function_exists('currentConcernId') && currentConcernId() === null):
    $adminConcerns = function_exists('allActiveConcerns') ? allActiveConcerns() : [];
    if (!empty($adminConcerns)):
        $selectedCid = (int)(function_exists('old') && old('concern_id') !== '' ? old('concern_id') : ($adminConcerns[0]['id'] ?? 1));
        $colClass = $concernSelectColClass ?? 'col-md-4';
        $labelText = $concernSelectLabel ?? 'Target Concern';
        $fieldId = $concernSelectId ?? 'adminTargetConcernSelect';
        $wrapCard = !empty($concernSelectWrapCard);
?>
<?php if ($wrapCard): ?>
<div class="card shadow-sm border mb-3">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <label class="form-label fw-semibold mb-0" for="<?= e($fieldId) ?>">
            <i class="bi bi-building me-1 text-primary"></i><?= e($labelText) ?>
        </label>
        <div style="min-width: 260px; max-width: 420px; flex: 1 1 260px;">
            <select name="concern_id" id="<?= e($fieldId) ?>" class="form-select form-select-sm">
                <?php foreach ($adminConcerns as $ac): ?>
                    <option value="<?= (int)$ac['id'] ?>" <?= ((int)$ac['id'] === $selectedCid) ? 'selected' : '' ?>>
                        <?= e($ac['name']) ?><?= !empty($ac['code']) ? ' (' . e($ac['code']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php else: ?>
<div class="<?= e($colClass) ?>">
    <label class="form-label fw-semibold" for="<?= e($fieldId) ?>">
        <i class="bi bi-building me-1 text-primary"></i><?= e($labelText) ?>
    </label>
    <select name="concern_id" id="<?= e($fieldId) ?>" class="form-select">
        <?php foreach ($adminConcerns as $ac): ?>
            <option value="<?= (int)$ac['id'] ?>" <?= ((int)$ac['id'] === $selectedCid) ? 'selected' : '' ?>>
                <?= e($ac['name']) ?><?= !empty($ac['code']) ? ' (' . e($ac['code']) . ')' : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>
<?php
    endif;
endif;
?>
