<?php
/**
 * Production HSN Config – Edit View
 */
?>
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit HSN Code</h4>
            <small class="text-muted">Update Production HSN Config entry</small>
        </div>
        <a href="<?= url('production/hsn-config') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <!-- Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="bi bi-pencil-square me-2"></i>Editing: <?= htmlspecialchars($record['hsn_code']) ?>
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST"
                          action="<?= url('production/hsn-config/update/' . $record['id']) ?>"
                          onsubmit="return validateHsnForm(this)">
                        <?= CSRF::field() ?>

                        <div class="row g-3">
                            <!-- Field 1: HSN Code -->
                            <div class="col-md-6">
                                <label for="hsn_code" class="form-label fw-semibold">
                                    HSN Code <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control font-monospace"
                                       id="hsn_code"
                                       name="hsn_code"
                                       maxlength="8"
                                       pattern="[0-9]{1,8}"
                                       inputmode="numeric"
                                       placeholder="Enter HSN code e.g. 5208, 6109, 61091000"
                                       value="<?= htmlspecialchars($record['hsn_code']) ?>"
                                       required autofocus>
                                <div class="form-text">Numbers only, up to 8 digits (e.g. 3, 5, 6, or 8 digits).</div>
                            </div>

                            <!-- Field 2: GST Percentage -->
                            <div class="col-md-6">
                                <label for="gst_percentage" class="form-label fw-semibold">GST Percentage</label>
                                <input type="text"
                                       class="form-control"
                                       id="gst_percentage"
                                       name="gst_percentage"
                                       placeholder="e.g. 5, 12, 18"
                                       value="<?= htmlspecialchars($record['gst_percentage'] ?? '') ?>">
                            </div>

                            <!-- Field 3: Categories -->
                            <div class="col-md-6">
                                <label for="categories" class="form-label fw-semibold">Categories</label>
                                <input type="text"
                                       class="form-control"
                                       id="categories"
                                       name="categories"
                                       placeholder="e.g. Garments"
                                       value="<?= htmlspecialchars($record['categories'] ?? '') ?>">
                            </div>

                            <!-- Field 4: Subcategories -->
                            <div class="col-md-6">
                                <label for="subcategories" class="form-label fw-semibold">Subcategories</label>
                                <input type="text"
                                       class="form-control"
                                       id="subcategories"
                                       name="subcategories"
                                       placeholder="e.g. Kids Wear"
                                       value="<?= htmlspecialchars($record['subcategories'] ?? '') ?>">
                            </div>

                            <!-- Field 5: Sub-subcategories -->
                            <div class="col-md-6">
                                <label for="sub_subcategories" class="form-label fw-semibold">Sub-subcategories</label>
                                <input type="text"
                                       class="form-control"
                                       id="sub_subcategories"
                                       name="sub_subcategories"
                                       placeholder="e.g. Baby Dress"
                                       value="<?= htmlspecialchars($record['sub_subcategories'] ?? '') ?>">
                            </div>

                            <!-- Field 6: Product -->
                            <div class="col-md-6">
                                <label for="product" class="form-label fw-semibold">Product</label>
                                <input type="text"
                                       class="form-control"
                                       id="product"
                                       name="product"
                                       placeholder="e.g. Cotton Dress"
                                       value="<?= htmlspecialchars($record['product'] ?? '') ?>">
                            </div>

                            <!-- Status Toggle -->
                            <div class="col-12">
                                <label class="form-label fw-semibold d-block">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="isActiveToggle"
                                           name="is_active"
                                           value="1"
                                           <?= !empty($record['is_active']) ? 'checked' : '' ?>
                                           role="switch">
                                    <label class="form-check-label" for="isActiveToggle" id="toggleLabel">
                                        <?= !empty($record['is_active']) ? 'Active' : 'Inactive' ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Update HSN Code
                            </button>
                            <a href="<?= url('production/hsn-config/view/' . $record['id']) ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('isActiveToggle').addEventListener('change', function () {
    document.getElementById('toggleLabel').textContent = this.checked ? 'Active' : 'Inactive';
});

function validateHsnForm(form) {
    var val = form.hsn_code.value.trim();
    if (!/^\d{1,8}$/.test(val)) {
        alert('HSN Code must be numbers only and up to 8 digits.');
        form.hsn_code.focus();
        return false;
    }
    return true;
}
</script>
