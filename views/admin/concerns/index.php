<div class="page-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-buildings text-primary me-2"></i>Business Concerns &amp; Sister Entities</h2>
        <p class="text-muted small mb-0">Multi-concern enterprise architecture for managing independent business entities, GST profiles, and scoped module matrices.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/concerns/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add New Concern
        </a>
    </div>
</div>

<!-- Metrics Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Concerns</div>
                <div class="fs-3 fw-bold text-dark"><?= number_format($totalConcerns) ?></div>
                <div class="small text-muted">Registered business firms</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Active Concerns</div>
                <div class="fs-3 fw-bold text-success"><?= number_format($activeConcerns) ?></div>
                <div class="small text-muted">Operational entities</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Currently Active Concern</div>
                <div class="fs-4 fw-bold text-primary text-truncate"><?= e(currentConcern()['name'] ?? 'Not Selected') ?></div>
                <div class="small text-muted font-monospace"><span class="badge bg-primary text-white"><?= e(currentConcern()['code'] ?? 'CN-01') ?></span> Scoped Session Context</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Enterprise Architecture</div>
                <div class="fs-4 fw-bold text-info"><i class="bi bi-shield-check me-1"></i>Multi-Tenant</div>
                <div class="small text-muted">Data partitioned by Concern ID</div>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card border shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Registered Business Concerns</h6>
                <div class="small text-muted">Switch context anytime from the top navigation bar dropdown or using the quick-switch button below.</div>
            </div>
            <form method="GET" action="<?= url('admin/concerns') ?>" class="d-flex gap-2">
                <div class="btn-group btn-group-sm">
                    <a href="<?= url('admin/concerns') ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                    <a href="<?= url('admin/concerns?status=active') ?>" class="btn <?= ($statusFilter === 'active') ? 'btn-success text-white' : 'btn-outline-secondary' ?>">Active</a>
                    <a href="<?= url('admin/concerns?status=inactive') ?>" class="btn <?= ($statusFilter === 'inactive') ? 'btn-danger text-white' : 'btn-outline-secondary' ?>">Inactive</a>
                </div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <input type="text" name="search" class="form-control" placeholder="Search name, code, GSTIN..." value="<?= e($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-secondary">
                        <th style="width: 45px;" class="text-center">#</th>
                        <th>Concern / Firm Name</th>
                        <th>Legal Details &amp; Tax</th>
                        <th>Prefixes</th>
                        <th class="text-center">Staff</th>
                        <th class="text-center">Units</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="min-width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($concerns)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No business concerns found matching your criteria.</td></tr>
                <?php else: foreach ($concerns as $idx => $c): 
                    $isActiveSession = ((int)$c['id'] === (int)$activeConcernId);
                    $cMods = $modulesByConcern[$c['id']] ?? [];
                    $cStaff = $staffByConcern[$c['id']] ?? [];
                    $cStaffCount = count($cStaff);
                    $assignedMap = array_column($cStaff, 'user_id');
                    $primaryStaff = null;
                    foreach ($cStaff as $cs) {
                        if ((int)$cs['is_default'] === 1) {
                            $primaryStaff = $cs;
                            break;
                        }
                    }
                ?>
                    <tr class="<?= $isActiveSession ? 'table-primary-subtle' : '' ?>">
                        <td class="text-center text-muted small fw-semibold"><?= $idx + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white font-monospace fs-6 px-2"><?= e($c['code']) ?></span>
                                <div>
                                    <strong class="text-dark d-block"><?= e($c['name']) ?></strong>
                                    <div class="d-flex align-items-center gap-1 mt-0.5">
                                        <?php 
                                        $typeBadge = match($c['concern_type'] ?? 'HEAD_OFFICE') {
                                            'PRODUCTION' => ['bg-warning-subtle text-warning-emphasis border-warning-subtle', '🏭 Production'],
                                            'WAREHOUSE'  => ['bg-info-subtle text-info-emphasis border-info-subtle', '🏢 Warehouse'],
                                            'RETAIL'     => ['bg-success-subtle text-success-emphasis border-success-subtle', '🛍️ Retail POS'],
                                            default      => ['bg-secondary-subtle text-secondary-emphasis border-secondary-subtle', '🌐 Head Office']
                                        };
                                        ?>
                                        <span class="badge <?= $typeBadge[0] ?> border" style="font-size: 10px;"><?= $typeBadge[1] ?></span>
                                        <?php if ($isActiveSession): ?>
                                            <span class="badge bg-primary text-white" style="font-size: 10px;"><i class="bi bi-check-circle me-1"></i>Active</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= e($c['legal_name'] ?: '—') ?></div>
                            <div class="small text-muted font-monospace">
                                <?php if (!empty($c['gst_number'])): ?>
                                    <span class="me-2">GST: <strong><?= e($c['gst_number']) ?></strong></span>
                                <?php endif; ?>
                                <?php if (!empty($c['pan_number'])): ?>
                                    <span>PAN: <?= e($c['pan_number']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="small font-monospace text-muted">
                                <div><span class="text-secondary">Inv:</span> <strong><?= e($c['invoice_prefix']) ?></strong></div>
                                <div><span class="text-secondary">PO:</span> <strong><?= e($c['po_prefix']) ?></strong></div>
                                <div><span class="text-secondary">DSP:</span> <strong><?= e($c['dispatch_prefix']) ?></strong></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($cStaffCount > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 py-1 px-2.5 rounded-pill shadow-xs" data-bs-toggle="modal" data-bs-target="#staffModal_<?= $c['id'] ?>">
                                    <i class="bi bi-people-fill text-primary"></i>
                                    <span class="fw-bold"><?= $cStaffCount ?></span> Staff
                                </button>
                                <?php if ($primaryStaff): ?>
                                    <div class="small text-muted mt-1" style="font-size: 10px;" title="Primary Manager">
                                        <i class="bi bi-star-fill text-warning me-0.5"></i><?= e($primaryStaff['full_name']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#staffModal_<?= $c['id'] ?>">
                                    <i class="bi bi-person-plus"></i> Assign
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-bold"><?= (int)$c['production_units_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($c['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border"><i class="bi bi-check-circle me-1"></i>Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <?php if (!$isActiveSession && $c['is_active']): ?>
                                    <a href="<?= url('system/concerns/switch/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary" title="Switch active session to this concern">
                                        <i class="bi bi-shuffle me-1"></i>Switch
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#staffModal_<?= $c['id'] ?>" title="Manage Staff for this Concern">
                                    <i class="bi bi-people"></i>
                                </button>
                                <a href="<?= url('admin/concerns/edit/' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit Concern &amp; Staff">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= url('admin/concerns/toggle/' . $c['id']) ?>" class="btn btn-sm <?= $c['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>" onclick="return confirm('<?= $c['is_active'] ? 'Deactivate this concern?' : 'Activate this concern?' ?>')">
                                    <i class="bi <?= $c['is_active'] ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- QUICK MANAGE STAFF MODALS FOR EACH CONCERN -->
<!-- ========================================================================= -->
<?php if (!empty($concerns)): foreach ($concerns as $c): 
    $cStaff = $staffByConcern[$c['id']] ?? [];
    $assignedIds = array_map('intval', array_column($cStaff, 'user_id'));
    $primaryId = null;
    foreach ($cStaff as $cs) {
        if ((int)$cs['is_default'] === 1) {
            $primaryId = (int)$cs['user_id'];
            break;
        }
    }
?>
<div class="modal fade" id="staffModal_<?= $c['id'] ?>" tabindex="-1" aria-labelledby="staffModalLabel_<?= $c['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="<?= url('admin/concerns/assign-staff/' . $c['id']) ?>" class="modal-content">
            <?= CSRF::field() ?>
            <div class="modal-header bg-light py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-monospace"><?= e($c['code']) ?></span>
                    <h5 class="modal-title fw-bold mb-0" id="staffModalLabel_<?= $c['id'] ?>">
                        Manage Staff — <?= e($c['name']) ?>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold text-dark">Authorized Staff Members</div>
                        <div class="small text-muted">Select staff members permitted to operate under this concern and designate primary manager.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border" id="badge_count_<?= $c['id'] ?>">
                            <span class="count-val"><?= count($assignedIds) ?></span> / <?= count($allStaff ?? []) ?> Staff
                        </span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="batchModalStaff(<?= $c['id'] ?>, true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="batchModalStaff(<?= $c['id'] ?>, false)">Clear</button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search staff by name, role, department or username..." oninput="filterModalStaff(<?= $c['id'] ?>, this.value)">
                    </div>
                </div>

                <div class="row g-2.5" id="modalStaffContainer_<?= $c['id'] ?>">
                    <?php if (empty($allStaff)): ?>
                        <div class="col-12 text-center text-muted py-4">No active staff members found.</div>
                    <?php else: foreach ($allStaff as $st): 
                        $stId = (int)$st['id'];
                        $isAssigned = in_array($stId, $assignedIds, true);
                        $isPrimary = ($stId === $primaryId);
                        $initials = strtoupper(substr($st['full_name'], 0, 1) . (strpos($st['full_name'], ' ') !== false ? substr(strchr($st['full_name'], ' '), 1, 1) : ''));
                    ?>
                        <div class="col-12 col-md-6 modal-staff-item_<?= $c['id'] ?> mb-2" data-search="<?= strtolower(e($st['full_name'] . ' ' . $st['username'] . ' ' . ($st['role_name'] ?? '') . ' ' . ($st['department'] ?? ''))) ?>">
                            <div class="card h-100 border rounded-3 p-3 cursor-pointer transition-all <?= $isAssigned ? 'border-primary bg-primary bg-opacity-10' : 'bg-light bg-opacity-25' ?>" id="mCard_<?= $c['id'] ?>_<?= $stId ?>" onclick="handleModalStaffClick(event, <?= $c['id'] ?>, <?= $stId ?>)">
                                <div class="d-flex align-items-start gap-2.5">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input modal-chk-<?= $c['id'] ?>" type="checkbox" name="staff_ids[]" value="<?= $stId ?>" id="mChk_<?= $c['id'] ?>_<?= $stId ?>" <?= $isAssigned ? 'checked' : '' ?> onchange="syncModalStaff(<?= $c['id'] ?>, <?= $stId ?>)">
                                    </div>
                                    <div class="avatar-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 36px; height: 36px; font-size: 13px;">
                                        <?= e($initials) ?>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center justify-content-between gap-1">
                                            <span class="fw-bold text-dark text-truncate small"><?= e($st['full_name']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary border small" style="font-size: 10px;"><?= e($st['role_name'] ?? 'Staff') ?></span>
                                        </div>
                                        <div class="text-muted small text-truncate" style="font-size: 11px;">
                                            <i class="bi bi-person me-1"></i>@<?= e($st['username']) ?> &bull; <?= e($st['department'] ?? 'GENERAL') ?>
                                        </div>

                                        <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                            <label class="form-check-label small text-muted cursor-pointer mb-0" for="mRadio_<?= $c['id'] ?>_<?= $stId ?>" style="font-size: 11px;">
                                                <input class="form-check-input me-1 modal-radio-<?= $c['id'] ?>" type="radio" name="primary_staff_id" value="<?= $stId ?>" id="mRadio_<?= $c['id'] ?>_<?= $stId ?>" <?= $isPrimary ? 'checked' : '' ?>>
                                                <span class="fw-semibold text-dark"><i class="bi bi-star-fill text-warning me-1"></i>Primary Manager</span>
                                            </label>
                                            <?php if ($isPrimary): ?>
                                                <span class="badge bg-success-subtle text-success border" style="font-size: 9px;"><i class="bi bi-check2 me-1"></i>Primary</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="bi bi-check2-circle me-1"></i> Save Staff Assignments
                </button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>

<script>
function handleModalStaffClick(event, cId, stId) {
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'LABEL') return;
    const chk = document.getElementById('mChk_' + cId + '_' + stId);
    if (chk) {
        chk.checked = !chk.checked;
        syncModalStaff(cId, stId);
    }
}

function syncModalStaff(cId, stId) {
    const chk = document.getElementById('mChk_' + cId + '_' + stId);
    const card = document.getElementById('mCard_' + cId + '_' + stId);
    const radio = document.getElementById('mRadio_' + cId + '_' + stId);

    if (chk && card) {
        if (chk.checked) {
            card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.remove('bg-light', 'bg-opacity-25');
            const curRadio = document.querySelector('.modal-radio-' + cId + ':checked');
            if (!curRadio && radio) radio.checked = true;
        } else {
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.add('bg-light', 'bg-opacity-25');
            if (radio && radio.checked) {
                radio.checked = false;
                const nextChecked = document.querySelector('.modal-chk-' + cId + ':checked');
                if (nextChecked) {
                    const nextRadio = document.getElementById('mRadio_' + cId + '_' + nextChecked.value);
                    if (nextRadio) nextRadio.checked = true;
                }
            }
        }
    }
    updateModalStaffCount(cId);
}

function batchModalStaff(cId, state) {
    document.querySelectorAll('.modal-chk-' + cId).forEach(cb => {
        cb.checked = state;
        const stId = cb.value;
        const card = document.getElementById('mCard_' + cId + '_' + stId);
        if (card) {
            if (state) {
                card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.remove('bg-light', 'bg-opacity-25');
            } else {
                card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.add('bg-light', 'bg-opacity-25');
            }
        }
    });
    if (state) {
        const curRadio = document.querySelector('.modal-radio-' + cId + ':checked');
        if (!curRadio) {
            const first = document.querySelector('.modal-radio-' + cId);
            if (first) first.checked = true;
        }
    } else {
        document.querySelectorAll('.modal-radio-' + cId).forEach(r => r.checked = false);
    }
    updateModalStaffCount(cId);
}

function filterModalStaff(cId, q) {
    const query = q.trim().toLowerCase();
    document.querySelectorAll('.modal-staff-item_' + cId).forEach(item => {
        const s = item.getAttribute('data-search') || '';
        item.style.display = (!query || s.includes(query)) ? '' : 'none';
    });
}

function updateModalStaffCount(cId) {
    const checked = document.querySelectorAll('.modal-chk-' + cId + ':checked');
    const badge = document.getElementById('badge_count_' + cId);
    if (badge) {
        const valSpan = badge.querySelector('.count-val');
        if (valSpan) valSpan.textContent = checked.length;
    }
}
</script>
