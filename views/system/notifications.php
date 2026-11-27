<?php
// views/system/notifications.php
$unreadTotal = (int)($unreadCount ?? 0);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>System Notifications &amp; Alert Log</h3>
            <p class="text-muted small mb-0">Central inbox for low stock warnings, program approvals, and background system updates.</p>
        </div>
        <?php if ($unreadTotal > 0): ?>
        <form method="POST" action="<?= url('system/notifications/read-all') ?>" class="d-inline">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold">
                <i class="bi bi-check2-all me-1"></i>Mark All as Read (<?= $unreadTotal ?>)
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Status</th>
                        <th>Type</th>
                        <th>Message Title</th>
                        <th>Details</th>
                        <th>Created Time</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody id="notificationsTableBody">
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No system notifications recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <tr class="notification-row <?= empty($n['is_read']) ? 'bg-light' : '' ?>">
                                <td class="ps-3">
                                    <?php if (empty($n['is_read'])): ?>
                                        <span class="badge bg-danger">Unread</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info text-dark"><?= e($n['type'] ?? 'SYSTEM') ?></span></td>
                                <td class="fw-bold text-dark">
                                    <?php if (!empty($n['link'])): ?>
                                        <a href="<?= e(url($n['link'])) ?>" class="text-decoration-none text-dark"><?= e($n['title'] ?? $n['message'] ?? '-') ?></a>
                                    <?php else: ?>
                                        <?= e($n['title'] ?? $n['message'] ?? '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= e($n['message'] ?? '-') ?></small></td>
                                <td><small class="text-muted"><?= formatDateTime($n['created_at']) ?></small></td>
                                <td class="text-end pe-3">
                                    <?php if (empty($n['is_read'])): ?>
                                        <form method="POST" action="<?= url('system/notifications/read/' . (int)$n['id']) ?>" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check2 me-1"></i>Mark Read
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$paginationPrefix = 'notifications';
$paginationItemName = 'notifications';
$paginationTotal = count($notifications ?? []);
$paginationDefaultSize = 10;
include BASE_PATH . '/views/partials/pagination_bar.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function') {
        window.initClientPagination({
            container: '#notificationsTableBody',
            itemSelector: '.notification-row',
            footer: '#notificationsPaginationFooter',
            infoEl: '#notificationsPageInfo',
            paginationList: '#notificationsPaginationList',
            pageSizeSelect: '#notificationsPageSizeSelect',
            pageSize: 10,
            itemName: 'notifications'
        });
    }
});
</script>
