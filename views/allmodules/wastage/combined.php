<?php
/**
 * Unified Wastage Selling View
 * Combines Fabric & Cutting Wastage Selling and Stitching Scrap & Wastage Selling into a single tabbed page.
 */
$activeTab = ($activeTab ?? 'cutting') === 'stitching' ? 'stitching' : 'cutting';
$cuttingCount = (int)($cuttingData['stats']['total_records'] ?? 0);
$stitchingCount = (int)($stitchingData['stats']['total_records'] ?? 0);
?>
<style>
.wastage-switcher-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
.wastage-switcher-bar .nav-link {
    border: none;
    border-radius: 8px;
    padding: 9px 18px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.18s ease;
}
.wastage-switcher-bar .nav-link:hover:not(.active) {
    background: #f8fafc;
    color: #0f172a;
}
.wastage-switcher-bar .nav-link.active {
    background: var(--bs-primary, #8b1538);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(139, 21, 56, 0.25);
}
.wastage-switcher-bar .nav-link.active i {
    color: #ffffff !important;
}
.wastage-switcher-bar .nav-link .wastage-tab-badge {
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
}
.wastage-switcher-bar .nav-link.active .wastage-tab-badge {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
}
</style>

<div class="container-fluid pt-3 pb-1">
    <div class="wastage-switcher-bar nav nav-pills" id="unifiedWastageTabs" role="tablist">
        <button class="nav-link <?= $activeTab === 'cutting' ? 'active' : '' ?>"
                id="tab-cutting-wastage"
                data-bs-toggle="tab"
                data-bs-target="#pane-cutting-wastage"
                type="button"
                role="tab"
                aria-controls="pane-cutting-wastage"
                aria-selected="<?= $activeTab === 'cutting' ? 'true' : 'false' ?>">
            <i class="bi bi-recycle text-success"></i>
            <span>Fabric &amp; Cutting Wastage Selling</span>
            <span class="wastage-tab-badge"><?= number_format($cuttingCount) ?></span>
        </button>
        <button class="nav-link <?= $activeTab === 'stitching' ? 'active' : '' ?>"
                id="tab-stitching-wastage"
                data-bs-toggle="tab"
                data-bs-target="#pane-stitching-wastage"
                type="button"
                role="tab"
                aria-controls="pane-stitching-wastage"
                aria-selected="<?= $activeTab === 'stitching' ? 'true' : 'false' ?>">
            <i class="bi bi-scissors text-warning"></i>
            <span>Stitching Scrap &amp; Wastage Selling</span>
            <span class="wastage-tab-badge"><?= number_format($stitchingCount) ?></span>
        </button>
    </div>
</div>

<div class="tab-content" id="unifiedWastageTabContent">
    <div class="tab-pane fade <?= $activeTab === 'cutting' ? 'show active' : '' ?>"
         id="pane-cutting-wastage"
         role="tabpanel"
         aria-labelledby="tab-cutting-wastage">
        <?php
        (static function (array $data): void {
            extract($data, EXTR_SKIP);
            require BASE_PATH . '/views/allmodules/wastage/index.php';
        })($cuttingData);
        ?>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'stitching' ? 'show active' : '' ?>"
         id="pane-stitching-wastage"
         role="tabpanel"
         aria-labelledby="tab-stitching-wastage">
        <?php
        (static function (array $data): void {
            extract($data, EXTR_SKIP);
            require BASE_PATH . '/views/allmodules/stitching_wastage/index.php';
        })($stitchingData);
        ?>
    </div>
</div>
