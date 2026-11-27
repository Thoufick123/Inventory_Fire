<?php
$pgPrefix = $paginationPrefix ?? 'list';
$pgItemName = $paginationItemName ?? 'entries';
$pgTotal = (int)($paginationTotal ?? 0);
$pgDefaultSize = (int)($paginationDefaultSize ?? 10);
$pgSizes = [10, 25, 50, 100];
$pgShowTo = min($pgDefaultSize, $pgTotal);
?>
<div class="card bg-white mt-4 shadow-sm border" id="<?= e($pgPrefix) ?>PaginationFooter">
    <div class="card-body py-3 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="<?= e($pgPrefix) ?>PageInfo">
                Showing <?= $pgTotal > 0 ? 1 : 0 ?> to <?= $pgShowTo ?> of <?= $pgTotal ?> <?= e($pgItemName) ?>
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium" for="<?= e($pgPrefix) ?>PageSizeSelect">Show:</label>
                <select id="<?= e($pgPrefix) ?>PageSizeSelect" class="form-select form-select-sm" style="width: auto; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; color: #334155; padding-left: 0.75rem; padding-right: 2rem;">
                    <?php foreach ($pgSizes as $sz): ?>
                        <option value="<?= $sz ?>"<?= $sz === $pgDefaultSize ? ' selected' : '' ?>><?= $sz ?> per page</option>
                    <?php endforeach; ?>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="<?= e(ucfirst($pgItemName)) ?> Pagination">
            <ul class="pagination pagination-modern mb-0" id="<?= e($pgPrefix) ?>PaginationList"></ul>
        </nav>
    </div>
</div>
