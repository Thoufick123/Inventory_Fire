<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-collection me-2 text-primary"></i>Third-Party Product Catalog</h2>
        <span class="text-muted small">Dedicated catalog for third-party purchased products and styles, separated from fabric/production items.</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('production/purchases/mapping') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> Product Mapping
        </a>
        <a href="<?= url('production/purchases/mapping/catalog/create') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Third-Party Style
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Catalog Styles</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($metrics['total_styles'] ?? 0)) ?></div>
                <div class="small text-muted">Configured third-party products</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Categories</div>
                <div class="fs-3 fw-bold text-info"><?= number_format((int)($metrics['total_categories'] ?? 0)) ?></div>
                <div class="small text-muted">Distinct categories used</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Avg. Selling Price</div>
                <div class="fs-3 fw-bold text-primary">₹<?= number_format((float)($metrics['avg_selling'] ?? 0), 2) ?></div>
                <div class="small text-muted">Average selling value</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Avg. MRP</div>
                <div class="fs-3 fw-bold text-success">₹<?= number_format((float)($metrics['avg_mrp'] ?? 0), 2) ?></div>
                <div class="small text-muted">Average maximum retail price</div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/purchases/mapping/catalog') ?>" class="row g-2 align-items-center">
            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by style code, product name, or SKU..." value="<?= e($search ?? '') ?>">
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6">
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ((int)($categoryId ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= e($cat['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-6">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
            </div>
            <?php if (!empty($search) || !empty($categoryId)): ?>
            <div class="col-auto">
                <a href="<?= url('production/purchases/mapping/catalog') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Third-Party Products Table -->
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Third-Party Catalog Items</h5>
        <span class="badge bg-secondary-subtle text-secondary border"><?= count($products) ?> Styles Registered</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="min-width: 200px;">Style / Product</th>
                    <th style="min-width: 140px;">SKU / Code</th>
                    <th style="min-width: 180px;">Category &amp; Taxonomy</th>
                    <th style="min-width: 120px;">Specs (Color / Size)</th>
                    <th class="text-end" style="min-width: 110px;">Purchase Rate</th>
                    <th class="text-end" style="min-width: 100px;">MRP</th>
                    <th class="text-end" style="min-width: 110px;">Selling Price</th>
                    <th class="text-center" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No third-party catalog styles found.
                            <div class="mt-2">
                                <a href="<?= url('production/purchases/mapping/catalog/create') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Style
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = 1; foreach ($products as $p): ?>
                        <tr>
                            <td class="text-muted"><?= $idx++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($p['name']) ?></div>
                                <?php if (!empty($p['seller_company']) || !empty($p['seller_name'])): ?>
                                    <div class="small text-muted">Supplier: <?= e($p['seller_company'] ?: $p['seller_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($p['code']) ?></span>
                                <?php if (!empty($p['vendor_sku']) && $p['vendor_sku'] !== $p['code']): ?>
                                    <div class="small text-muted font-monospace mt-1"><?= e($p['vendor_sku']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><span class="badge bg-primary-subtle text-primary border"><?= e($p['category_name'] ?: 'No Category') ?></span></div>
                                <?php if (!empty($p['subcategory_name'])): ?>
                                    <div class="small text-muted mt-1">&rarr; <?= e($p['subcategory_name']) ?><?= !empty($p['sub_subcategory_name']) ? ' &rarr; ' . e($p['sub_subcategory_name']) : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['color'])): ?>
                                    <span class="badge bg-secondary-subtle text-dark border me-1"><?= e($p['color']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['size'])): ?>
                                    <span class="badge bg-light text-dark border"><?= e($p['size']) ?></span>
                                <?php endif; ?>
                                <span class="small text-muted ms-1">(<?= e($p['unit'] ?: 'PCS') ?>)</span>
                            </td>
                            <td class="text-end font-monospace">₹<?= number_format((float)$p['purchase_rate'], 2) ?></td>
                            <td class="text-end font-monospace text-muted">₹<?= number_format((float)$p['mrp'], 2) ?></td>
                            <td class="text-end font-monospace fw-bold text-success">₹<?= number_format((float)$p['selling_price'], 2) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('production/purchases/mapping/catalog/edit/' . $p['id']) ?>" class="btn btn-outline-primary" title="Edit Style">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= url('production/purchases/mapping/catalog/delete/' . $p['id']) ?>" class="d-inline" onsubmit="return confirm('Remove style <?= e($p['name']) ?> from catalog?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Remove Style">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
