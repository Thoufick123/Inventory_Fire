<div class="page-header mb-4">
    <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>My Dashboard</h2>
    <p class="text-muted mb-0">
        Welcome, <?= e($dashboardUser['full_name'] ?? 'Staff member') ?>.
        Your dashboard shows the modules assigned to your account.
        <?php if (!empty($dashboardUser['production_unit_name'])): ?>
            <span class="ms-1">Production unit: <strong><?= e($dashboardUser['production_unit_name']) ?></strong></span>
        <?php endif; ?>
    </p>
</div>

<?php foreach ($dashboardGroups as $group): ?>
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="bi <?= e($group['icon']) ?> text-primary me-2"></i><?= e($group['title']) ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($group['items'] as $item): ?>
                    <div class="col-sm-6 col-xl-4">
                        <a href="<?= e($item['url']) ?>" class="card h-100 text-decoration-none border module-dashboard-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <span class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center">
                                    <i class="bi <?= e($item['icon']) ?> fs-5"></i>
                                </span>
                                <span class="fw-semibold text-dark"><?= e($item['name']) ?></span>
                                <i class="bi bi-arrow-up-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($group['items']) && !empty($group['emptyMessage'])): ?>
                    <div class="col-12 text-muted small">
                        <i class="bi bi-info-circle me-1"></i><?= e($group['emptyMessage']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>

<style>
.module-dashboard-card {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.module-dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .35rem .8rem rgba(0, 0, 0, .08);
}
</style>
