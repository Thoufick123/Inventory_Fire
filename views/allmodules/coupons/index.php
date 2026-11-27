<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>POS Coupons</h4>
        <div class="text-muted small">Create and manage discount coupons for POS Billing</div>
    </div>
    <?php if (hasPermission('coupons', 'create')): ?>
        <a class="btn btn-primary" href="<?= url('allmodules/coupons/create') ?>">
            <i class="bi bi-plus-lg me-1"></i>Create Coupon
        </a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 border-start border-primary border-4 p-3 shadow-sm h-100">
            <div class="text-uppercase fw-semibold text-muted small">Total Coupons</div>
            <div class="fw-bold text-primary fs-3 mt-1"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 border-start border-success border-4 p-3 shadow-sm h-100">
            <div class="text-uppercase fw-semibold text-muted small">Active Coupons</div>
            <div class="fw-bold text-success fs-3 mt-1"><?= number_format((int)($stats['active'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 border-start border-info border-4 p-3 shadow-sm h-100">
            <div class="text-uppercase fw-semibold text-muted small">Total Redemptions</div>
            <div class="fw-bold text-info-emphasis fs-3 mt-1"><?= number_format((int)($stats['total_uses'] ?? 0)) ?></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-ul text-primary me-2"></i>Saved Coupons</h6>
        <input type="text" id="couponSearchInput" class="form-control form-control-sm" placeholder="Search coupon code, title..." style="width: 240px;" oninput="filterCouponsLive(this.value)">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Coupon Code</th>
                    <th>Title</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Min Bill Amount</th>
                    <th class="text-center">Usage</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Concern</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="couponsTableBody">
                <?php if (empty($coupons)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No coupons created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($coupons as $c): ?>
                        <?php
                        $st = strtoupper((string)($c['status'] ?? 'ACTIVE'));
                        $isExpired = !empty($c['expires_at']) && $c['expires_at'] < date('Y-m-d');
                        $displayStatus = ($st === 'ACTIVE' && $isExpired) ? 'EXPIRED' : $st;
                        $badgeClass = match ($displayStatus) {
                            'ACTIVE' => 'bg-success-subtle text-success',
                            'INACTIVE' => 'bg-secondary-subtle text-secondary',
                            'REDEEMED' => 'bg-info-subtle text-info-emphasis',
                            default => 'bg-danger-subtle text-danger',
                        };
                        $maxUses = (int)($c['max_uses'] ?? 0);
                        $usedCount = (int)($c['used_count'] ?? 0);
                        ?>
                        <tr class="coupon-row">
                            <td class="fw-bold font-monospace text-primary"><?= e($c['coupon_code']) ?></td>
                            <td><?= e($c['title'] ?: '—') ?></td>
                            <td class="text-end fw-semibold">
                                <?php if (strtoupper((string)$c['discount_type']) === 'PERCENT'): ?>
                                    <?= rtrim(rtrim(number_format((float)$c['discount_value'], 2), '0'), '.') ?>%
                                <?php else: ?>
                                    <?= formatCurrency((float)$c['discount_value']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= (float)$c['min_redemption_amount'] > 0 ? formatCurrency((float)$c['min_redemption_amount']) : 'No Min' ?></td>
                            <td class="text-center font-monospace">
                                <?= $usedCount ?> / <?= $maxUses > 0 ? $maxUses : '∞' ?>
                            </td>
                            <td><?= !empty($c['expires_at']) ? e(date('d M Y', strtotime($c['expires_at']))) : 'No Expiry' ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= e($displayStatus) ?></span></td>
                            <td><?= renderConcernBadge($c) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="action-btns-group justify-content-end">
                                    <?php if (hasPermission('coupons', 'edit')): ?>
                                        <a class="btn btn-outline-primary btn-action-icon" title="Edit" href="<?= url('allmodules/coupons/edit/' . (int)$c['id']) ?>"><i class="bi bi-pencil"></i></a>
                                        <form class="d-inline" method="POST" action="<?= url('allmodules/coupons/toggle/' . (int)$c['id']) ?>">
                                            <?= CSRF::input() ?>
                                            <button class="btn btn-outline-warning btn-action-icon" title="<?= $st === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>">
                                                <i class="bi <?= $st === 'ACTIVE' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (hasPermission('coupons', 'delete')): ?>
                                        <form class="d-inline" method="POST" action="<?= url('allmodules/coupons/delete/' . (int)$c['id']) ?>" onsubmit="return confirm('Delete this coupon?')">
                                            <?= CSRF::input() ?>
                                            <button class="btn btn-outline-danger btn-action-icon" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$paginationPrefix = 'coupons';
$paginationItemName = 'coupons';
$paginationTotal = count($coupons ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>

<script>
let couponsPagination = null;
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function') {
        couponsPagination = window.initClientPagination({
            container: '#couponsTableBody',
            itemSelector: '.coupon-row',
            footer: '#couponsPaginationFooter',
            infoEl: '#couponsPageInfo',
            paginationList: '#couponsPaginationList',
            pageSizeSelect: '#couponsPageSizeSelect',
            pageSize: 10,
            itemName: 'coupons'
        });
    }
});

function filterCouponsLive(query) {
    const q = (query || '').toLowerCase().trim();
    const matchFn = (r) => !q || (r.textContent || '').toLowerCase().includes(q);
    if (couponsPagination) {
        couponsPagination.filter(matchFn);
    } else {
        document.querySelectorAll('#couponsTableBody .coupon-row').forEach(r => {
            r.style.display = matchFn(r) ? '' : 'none';
        });
    }
}
</script>
