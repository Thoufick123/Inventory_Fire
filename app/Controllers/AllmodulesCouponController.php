<?php

class AllmodulesCouponController
{
    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            Database::execute(
                "CREATE TABLE IF NOT EXISTS `pos_coupons` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `concern_id` int(10) unsigned DEFAULT NULL,
                    `coupon_code` varchar(40) NOT NULL,
                    `title` varchar(150) DEFAULT NULL,
                    `issued_bill_id` int(10) unsigned DEFAULT NULL,
                    `customer_phone` varchar(20) DEFAULT NULL,
                    `customer_name` varchar(150) DEFAULT NULL,
                    `discount_type` enum('FIXED','PERCENT') NOT NULL DEFAULT 'FIXED',
                    `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `min_redemption_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `max_uses` int(11) NOT NULL DEFAULT 0,
                    `used_count` int(11) NOT NULL DEFAULT 0,
                    `status` enum('ACTIVE','INACTIVE','REDEEMED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
                    `redeemed_bill_id` int(10) unsigned DEFAULT NULL,
                    `redeemed_at` datetime DEFAULT NULL,
                    `expires_at` date DEFAULT NULL,
                    `created_by` int(10) unsigned DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uniq_pos_coupons_code` (`coupon_code`),
                    KEY `idx_pos_coupons_issued_bill` (`issued_bill_id`),
                    KEY `idx_pos_coupons_redeemed_bill` (`redeemed_bill_id`),
                    KEY `idx_pos_coupons_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            Database::ensureColumn('pos_coupons', 'title', 'VARCHAR(150) DEFAULT NULL AFTER `coupon_code`');
            Database::ensureColumn('pos_coupons', 'max_uses', 'INT(11) NOT NULL DEFAULT 0 AFTER `min_redemption_amount`');
            Database::ensureColumn('pos_coupons', 'used_count', 'INT(11) NOT NULL DEFAULT 0 AFTER `max_uses`');
            Database::ensureColumn('pos_coupons', 'created_by', 'INT(10) UNSIGNED DEFAULT NULL AFTER `expires_at`');

            $issuedCol = Database::fetchOne("SHOW COLUMNS FROM pos_coupons LIKE 'issued_bill_id'");
            if ($issuedCol && strtoupper((string)($issuedCol['Null'] ?? 'NO')) === 'NO') {
                Database::execute("ALTER TABLE pos_coupons MODIFY COLUMN issued_bill_id INT(10) UNSIGNED DEFAULT NULL");
            }
            $statusCol = Database::fetchOne("SHOW COLUMNS FROM pos_coupons LIKE 'status'");
            if ($statusCol && stripos((string)($statusCol['Type'] ?? ''), "'INACTIVE'") === false) {
                Database::execute("ALTER TABLE pos_coupons MODIFY COLUMN status ENUM('ACTIVE','INACTIVE','REDEEMED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE'");
            }
        } catch (\Throwable $e) {
            error_log('AllmodulesCouponController::ensureSchema failed: ' . $e->getMessage());
        }
    }

    public function index(): void
    {
        self::ensureSchema();
        $cid = currentConcernId();
        $where = [];
        $params = [];
        if ($cid) {
            $where[] = ($cid === 1) ? "(pc.concern_id = 1 OR pc.concern_id IS NULL)" : "pc.concern_id = ?";
            if ($cid !== 1) {
                $params[] = (int)$cid;
            }
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $coupons = Database::fetchAll(
            "SELECT pc.*, c.name AS concern_name, c.code AS concern_code
             FROM pos_coupons pc
             LEFT JOIN concerns c ON c.id = COALESCE(pc.concern_id, 1)
             {$whereSql}
             ORDER BY pc.id DESC",
            $params
        );

        $stats = [
            'total' => count($coupons),
            'active' => 0,
            'inactive' => 0,
            'total_uses' => 0,
        ];
        foreach ($coupons as $cp) {
            $st = strtoupper((string)($cp['status'] ?? 'ACTIVE'));
            $isExpired = !empty($cp['expires_at']) && $cp['expires_at'] < date('Y-m-d');
            if ($st === 'ACTIVE' && !$isExpired) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }
            $stats['total_uses'] += (int)($cp['used_count'] ?? 0);
        }

        Response::view('allmodules.coupons.index', [
            'pageTitle' => 'POS Coupons',
            'activeModule' => 'retail_pos',
            'activeMenu' => 'prod-coupons',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('allmodules/billing')],
                ['label' => 'Coupons'],
            ],
            'coupons' => $coupons,
            'stats' => $stats,
        ]);
    }

    public function create(): void
    {
        self::ensureSchema();
        Response::view('allmodules.coupons.create', [
            'pageTitle' => 'Create POS Coupon',
            'activeModule' => 'retail_pos',
            'activeMenu' => 'prod-coupons',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('allmodules/billing')],
                ['label' => 'Coupons', 'url' => url('allmodules/coupons')],
                ['label' => 'Create Coupon'],
            ],
        ]);
    }

    public function store(): void
    {
        self::ensureSchema();
        if (!CSRF::validateRequest()) {
            Response::redirect('allmodules/coupons/create', 'error', 'Invalid security token.');
            return;
        }
        $this->persistCoupon(null);
    }

    public function edit(string $id): void
    {
        self::ensureSchema();
        $coupon = $this->findScopedCoupon((int)$id);
        if (!$coupon) {
            Response::redirect('allmodules/coupons', 'error', 'Coupon not found.');
            return;
        }

        Response::view('allmodules.coupons.edit', [
            'pageTitle' => 'Edit Coupon ' . $coupon['coupon_code'],
            'activeModule' => 'retail_pos',
            'activeMenu' => 'prod-coupons',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('allmodules/billing')],
                ['label' => 'Coupons', 'url' => url('allmodules/coupons')],
                ['label' => 'Edit ' . $coupon['coupon_code']],
            ],
            'coupon' => $coupon,
        ]);
    }

    public function update(string $id): void
    {
        self::ensureSchema();
        if (!CSRF::validateRequest()) {
            Response::redirect('allmodules/coupons/edit/' . (int)$id, 'error', 'Invalid security token.');
            return;
        }
        $coupon = $this->findScopedCoupon((int)$id);
        if (!$coupon) {
            Response::redirect('allmodules/coupons', 'error', 'Coupon not found.');
            return;
        }
        $this->persistCoupon((int)$id);
    }

    public function toggleStatus(string $id): void
    {
        self::ensureSchema();
        if (!CSRF::validateRequest()) {
            Response::redirect('allmodules/coupons', 'error', 'Invalid security token.');
            return;
        }
        $coupon = $this->findScopedCoupon((int)$id);
        if (!$coupon) {
            Response::redirect('allmodules/coupons', 'error', 'Coupon not found.');
            return;
        }

        $nextStatus = strtoupper((string)$coupon['status']) === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Database::execute("UPDATE pos_coupons SET status = ? WHERE id = ?", [$nextStatus, (int)$coupon['id']]);
        AuditService::log('coupons', 'update', 'pos_coupons', (int)$coupon['id'], ['status' => $coupon['status']], ['status' => $nextStatus]);
        Response::redirect('allmodules/coupons', 'success', "Coupon {$coupon['coupon_code']} marked as {$nextStatus}.");
    }

    public function delete(string $id): void
    {
        self::ensureSchema();
        if (!CSRF::validateRequest()) {
            Response::redirect('allmodules/coupons', 'error', 'Invalid security token.');
            return;
        }
        $coupon = $this->findScopedCoupon((int)$id);
        if (!$coupon) {
            Response::redirect('allmodules/coupons', 'error', 'Coupon not found.');
            return;
        }

        $usedInBills = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS cnt FROM production_bills WHERE applied_coupon_id = ?",
            [(int)$coupon['id']]
        )['cnt'] ?? 0);

        if ($usedInBills > 0 || (int)($coupon['used_count'] ?? 0) > 0) {
            Database::execute("UPDATE pos_coupons SET status = 'INACTIVE' WHERE id = ?", [(int)$coupon['id']]);
            AuditService::log('coupons', 'deactivate', 'pos_coupons', (int)$coupon['id'], null, ['status' => 'INACTIVE']);
            Response::redirect('allmodules/coupons', 'success', "Coupon {$coupon['coupon_code']} has been used in bills and was marked INACTIVE.");
            return;
        }

        Database::execute("DELETE FROM pos_coupons WHERE id = ?", [(int)$coupon['id']]);
        AuditService::log('coupons', 'delete', 'pos_coupons', (int)$coupon['id'], ['coupon_code' => $coupon['coupon_code']], null);
        Response::redirect('allmodules/coupons', 'success', "Coupon {$coupon['coupon_code']} deleted.");
    }

    private function findScopedCoupon(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $cid = currentConcernId();
        if ($cid) {
            if ($cid === 1) {
                return Database::fetchOne("SELECT * FROM pos_coupons WHERE id = ? AND (concern_id = 1 OR concern_id IS NULL)", [$id]);
            }
            return Database::fetchOne("SELECT * FROM pos_coupons WHERE id = ? AND concern_id = ?", [$id, $cid]);
        }
        return Database::fetchOne("SELECT * FROM pos_coupons WHERE id = ?", [$id]);
    }

    private function persistCoupon(?int $couponId): void
    {
        $redirectTarget = $couponId ? ('allmodules/coupons/edit/' . $couponId) : 'allmodules/coupons/create';
        $couponCode = strtoupper(preg_replace('/\s+/', '', trim((string)($_POST['coupon_code'] ?? ''))));
        $title = trim((string)($_POST['title'] ?? ''));
        $discountType = strtoupper(trim((string)($_POST['discount_type'] ?? 'FIXED')));
        $discountValue = filter_var($_POST['discount_value'] ?? null, FILTER_VALIDATE_FLOAT);
        $minAmount = filter_var($_POST['min_redemption_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $maxUses = filter_var($_POST['max_uses'] ?? 0, FILTER_VALIDATE_INT);
        $expiresAt = trim((string)($_POST['expires_at'] ?? ''));
        $status = strtoupper(trim((string)($_POST['status'] ?? 'ACTIVE')));

        if ($couponCode === '' || !preg_match('/^[A-Z0-9_-]{2,40}$/', $couponCode)) {
            Response::redirect($redirectTarget, 'error', 'Enter a valid Coupon Code (2-40 characters: letters, numbers, - or _).');
            return;
        }
        if (!in_array($discountType, ['FIXED', 'PERCENT'], true)) {
            Response::redirect($redirectTarget, 'error', 'Select a valid discount type.');
            return;
        }
        if ($discountValue === false || $discountValue <= 0) {
            Response::redirect($redirectTarget, 'error', 'Discount amount/value must be greater than 0.');
            return;
        }
        if ($discountType === 'PERCENT' && $discountValue > 100) {
            Response::redirect($redirectTarget, 'error', 'Percentage discount cannot exceed 100%.');
            return;
        }
        if ($minAmount === false || $minAmount < 0) {
            Response::redirect($redirectTarget, 'error', 'Minimum bill amount must be 0 or greater.');
            return;
        }
        if ($maxUses === false || $maxUses < 0) {
            Response::redirect($redirectTarget, 'error', 'Max uses must be 0 (unlimited) or a positive integer.');
            return;
        }
        if ($expiresAt !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
            Response::redirect($redirectTarget, 'error', 'Enter a valid expiry date.');
            return;
        }
        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = 'ACTIVE';
        }

        $dup = Database::fetchOne(
            $couponId ? "SELECT id FROM pos_coupons WHERE UPPER(coupon_code) = ? AND id <> ? LIMIT 1" : "SELECT id FROM pos_coupons WHERE UPPER(coupon_code) = ? LIMIT 1",
            $couponId ? [$couponCode, $couponId] : [$couponCode]
        );
        if ($dup) {
            Response::redirect($redirectTarget, 'error', "Coupon code '{$couponCode}' already exists.");
            return;
        }

        $concernId = resolveSubmittedConcernId();
        $expiresVal = $expiresAt !== '' ? $expiresAt : null;

        if ($couponId === null) {
            Database::execute(
                "INSERT INTO pos_coupons
                 (concern_id, coupon_code, title, discount_type, discount_value, min_redemption_amount, max_uses, used_count, status, expires_at, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)",
                [$concernId, $couponCode, $title ?: null, $discountType, round((float)$discountValue, 2), round((float)$minAmount, 2), (int)$maxUses, $status, $expiresVal, (int)(Auth::id() ?? 0) ?: null]
            );
            $newId = (int)Database::lastInsertId();
            AuditService::log('coupons', 'create', 'pos_coupons', $newId, null, ['coupon_code' => $couponCode, 'discount_type' => $discountType, 'discount_value' => $discountValue]);
            Response::redirect('allmodules/coupons', 'success', "Coupon '{$couponCode}' created successfully.");
            return;
        }

        Database::execute(
            "UPDATE pos_coupons
             SET coupon_code = ?, title = ?, discount_type = ?, discount_value = ?, min_redemption_amount = ?, max_uses = ?, status = ?, expires_at = ?
             WHERE id = ?",
            [$couponCode, $title ?: null, $discountType, round((float)$discountValue, 2), round((float)$minAmount, 2), (int)$maxUses, $status, $expiresVal, $couponId]
        );
        AuditService::log('coupons', 'update', 'pos_coupons', $couponId, null, ['coupon_code' => $couponCode, 'discount_type' => $discountType, 'discount_value' => $discountValue, 'status' => $status]);
        Response::redirect('allmodules/coupons', 'success', "Coupon '{$couponCode}' updated successfully.");
    }
}
