<?php
/**
 * Production HSN Config – Create View
 */
?>
<div class="container-fluid py-4">

    <?php if ($error = Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add HSN Code</h4>
            <small class="text-muted">Create a new Production HSN Config entry</small>
        </div>
        <a href="<?= url('production/hsn-config') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <!-- Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold text-primary"><i class="bi bi-upc-scan me-2"></i>HSN Code Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="<?= url('production/hsn-config/store') ?>"
                          onsubmit="return validateHsnForm(this)">
                        <?= CSRF::field() ?>

                        <!-- HSN Code -->
                        <div class="mb-3">
                            <label for="hsn_code" class="form-label fw-semibold">
                                HSN Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control font-monospace"
                                   id="hsn_code"
                                   name="hsn_code"
                                   maxlength="8"
                                   pattern="[0-9]{8}"
                                   inputmode="numeric"
                                   placeholder="Enter exactly 8 digit number e.g. 61091000"
                                   value="<?= htmlspecialchars($_POST['hsn_code'] ?? '') ?>"
                                   required autofocus>
                            <div class="form-text">Exactly 8 digits, numbers only.</div>
                        </div>

                        <!-- GST Percentage -->
                        <div class="mb-3">
                            <label for="gst_percentage" class="form-label fw-semibold">GST Percentage <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text"
                                   class="form-control"
                                   id="gst_percentage"
                                   name="gst_percentage"
                                   placeholder="e.g. 5, 12, 18"
                                   value="<?= htmlspecialchars($_POST['gst_percentage'] ?? '') ?>">
                        </div>

                        <!-- Categories -->
                        <div class="mb-3">
                            <label for="categories" class="form-label fw-semibold">Categories</label>
                            <input type="text"
                                   class="form-control"
                                   id="categories"
                                   name="categories"
                                   placeholder="e.g. Garments"
                                   value="<?= htmlspecialchars($_POST['categories'] ?? '') ?>">
                        </div>

                        <!-- Subcategories -->
                        <div class="mb-3">
                            <label for="subcategories" class="form-label fw-semibold">Subcategories</label>
                            <input type="text"
                                   class="form-control"
                                   id="subcategories"
                                   name="subcategories"
                                   placeholder="e.g. Kids Wear"
                                   value="<?= htmlspecialchars($_POST['subcategories'] ?? '') ?>">
                        </div>

                        <!-- Sub-subcategories -->
                        <div class="mb-3">
                            <label for="sub_subcategories" class="form-label fw-semibold">Sub-subcategories</label>
                            <input type="text"
                                   class="form-control"
                                   id="sub_subcategories"
                                   name="sub_subcategories"
                                   placeholder="e.g. Baby Dress"
                                   value="<?= htmlspecialchars($_POST['sub_subcategories'] ?? '') ?>">
                        </div>

                        <!-- Product -->
                        <div class="mb-3">
                            <label for="product" class="form-label fw-semibold">Product</label>
                            <input type="text"
                                   class="form-control"
                                   id="product"
                                   name="product"
                                   placeholder="e.g. Cotton Dress"
                                   value="<?= htmlspecialchars($_POST['product'] ?? '') ?>">
                        </div>

                        <!-- Active / Inactive Toggle -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isActiveToggle"
                                       name="is_active"
                                       value="1"
                                       checked
                                       role="switch">
                                <label class="form-check-label" for="isActiveToggle" id="toggleLabel">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Save HSN Code
                            </button>
                            <a href="<?= url('production/hsn-config') ?>" class="btn btn-outline-secondary">
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
    if (!/^\d{8}$/.test(val)) {
        alert('HSN Code must be exactly 8 digits (numbers only).');
        form.hsn_code.focus();
        return false;
    }
    return true;
}
</script>
