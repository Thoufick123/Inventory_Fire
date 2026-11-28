<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/app/Helpers/Database.php';

class CashFlowService {

    public static function ensureSchema(): void {
        static $initialized = false;
        if ($initialized) return;

        // 1. Shop Shifts table
        Database::execute("CREATE TABLE IF NOT EXISTS shop_shifts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            concern_id INT UNSIGNED NULL,
            shift_code VARCHAR(50) NOT NULL UNIQUE,
            cashier_id INT UNSIGNED NOT NULL,
            opening_float DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            closed_at DATETIME DEFAULT NULL,
            closed_by INT UNSIGNED DEFAULT NULL,
            expected_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            actual_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            variance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            denominations JSON DEFAULT NULL,
            closing_notes TEXT DEFAULT NULL,
            status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_shift_concern_status (concern_id, status),
            INDEX idx_shift_opened_at (opened_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Cash Flow Transactions Ledger table
        Database::execute("CREATE TABLE IF NOT EXISTS cash_flow_transactions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            concern_id INT UNSIGNED NULL,
            shift_id INT UNSIGNED NULL,
            tx_number VARCHAR(50) NOT NULL UNIQUE,
            transaction_type ENUM('FLOAT_OPEN', 'FLOAT_TOPUP', 'PETTY_EXPENSE', 'BANK_DROP', 'SALE_INFLOW', 'REFUND_OUTFLOW') NOT NULL,
            flow ENUM('INFLOW', 'OUTFLOW') NOT NULL,
            payment_mode VARCHAR(50) NOT NULL DEFAULT 'CASH',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            source_or_recipient VARCHAR(255) DEFAULT NULL,
            reference_number VARCHAR(100) DEFAULT NULL,
            reason TEXT DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cftx_concern (concern_id),
            INDEX idx_cftx_shift (shift_id),
            INDEX idx_cftx_type (transaction_type),
            INDEX idx_cftx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Bank Cash Drops & Safe Transfers table
        Database::execute("CREATE TABLE IF NOT EXISTS bank_deposits (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            concern_id INT UNSIGNED NULL,
            shift_id INT UNSIGNED NULL,
            deposit_number VARCHAR(50) NOT NULL UNIQUE,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            bank_name VARCHAR(150) NOT NULL,
            deposit_date DATE NOT NULL,
            slip_number VARCHAR(100) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bd_concern (concern_id),
            INDEX idx_bd_shift (shift_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $initialized = true;
    }

    public static function getActiveShift(?int $concernId): ?array {
        self::ensureSchema();
        $params = [];
        $scope = '';
        if ($concernId !== null) {
            $scope = ($concernId === 1) ? 'AND (s.concern_id = ? OR s.concern_id IS NULL)' : 'AND s.concern_id = ?';
            $params[] = $concernId;
        }

        $shift = Database::fetchOne(
            "SELECT s.*, u.full_name AS cashier_name, c.name AS concern_name
             FROM shop_shifts s
             LEFT JOIN users u ON u.id = s.cashier_id
             LEFT JOIN concerns c ON c.id = s.concern_id
             WHERE s.status = 'OPEN' {$scope}
             ORDER BY s.id DESC LIMIT 1",
            $params
        );

        if (!$shift) return null;

        // Calculate live drawer totals
        $shiftId = (int)$shift['id'];
        $stats = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'FLOAT_TOPUP' THEN amount ELSE 0 END), 0) AS total_topup,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_INFLOW' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'REFUND_OUTFLOW' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS total_refunds,
                COALESCE(SUM(CASE WHEN transaction_type = 'PETTY_EXPENSE' THEN amount ELSE 0 END), 0) AS total_expenses,
                COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DROP' THEN amount ELSE 0 END), 0) AS total_bank_drops
             FROM cash_flow_transactions
             WHERE shift_id = ?",
            [$shiftId]
        );

        $openingFloat = (float)$shift['opening_float'];
        $topUp = (float)($stats['total_topup'] ?? 0);
        $sales = (float)($stats['total_sales'] ?? 0);
        $refunds = (float)($stats['total_refunds'] ?? 0);
        $expenses = (float)($stats['total_expenses'] ?? 0);
        $bankDrops = (float)($stats['total_bank_drops'] ?? 0);

        $expectedDrawer = $openingFloat + $topUp + $sales - $refunds - $expenses - $bankDrops;

        $shift['total_topup'] = $topUp;
        $shift['total_sales'] = $sales;
        $shift['total_refunds'] = $refunds;
        $shift['total_expenses'] = $expenses;
        $shift['total_bank_drops'] = $bankDrops;
        $shift['expected_drawer'] = max(0, $expectedDrawer);

        return $shift;
    }

    /**
     * Fetch the most recently closed shift for a concern so its closing balance
     * and denominations can be carried forward into the next shift's opening float.
     */
    public static function getLastClosedShift(?int $concernId): ?array {
        self::ensureSchema();
        $where = "s.status = 'CLOSED'";
        $params = [];
        if ($concernId) {
            if ($concernId === 1) {
                $where .= " AND (s.concern_id = ? OR s.concern_id IS NULL)";
            } else {
                $where .= " AND s.concern_id = ?";
            }
            $params[] = $concernId;
        }

        $shift = Database::fetchOne(
            "SELECT s.*,
                    u.full_name AS cashier_name,
                    cu.full_name AS closed_by_name
             FROM shop_shifts s
             LEFT JOIN users u ON u.id = s.cashier_id
             LEFT JOIN users cu ON cu.id = s.closed_by
             WHERE {$where}
             ORDER BY COALESCE(s.closed_at, s.opened_at) DESC, s.id DESC
             LIMIT 1",
            $params
        );

        if (!$shift) {
            return null;
        }

        $decodedDenoms = [];
        if (!empty($shift['denominations'])) {
            $parsed = json_decode((string)$shift['denominations'], true);
            if (is_array($parsed)) {
                $decodedDenoms = $parsed;
            }
        }

        $shift['denominations_map'] = [
            'c500' => (int)($decodedDenoms['c500'] ?? 0),
            'c200' => (int)($decodedDenoms['c200'] ?? 0),
            'c100' => (int)($decodedDenoms['c100'] ?? 0),
            'c50'  => (int)($decodedDenoms['c50'] ?? 0),
            'c20'  => (int)($decodedDenoms['c20'] ?? 0),
            'c10'  => (int)($decodedDenoms['c10'] ?? 0),
            'c5'   => (int)($decodedDenoms['c5'] ?? 0),
            'coins'=> (float)($decodedDenoms['coins'] ?? 0),
        ];

        $carriedFloat = $shift['actual_cash'] !== null
            ? (float)$shift['actual_cash']
            : (float)($shift['expected_cash'] ?? 0);
        $shift['carried_float'] = max(0.0, $carriedFloat);

        return $shift;
    }

    public static function openShift(?int $concernId, int $cashierId, float $openingFloat, string $notes = ''): int {
        self::ensureSchema();
        if ($openingFloat < 0) {
            throw new InvalidArgumentException('Opening float cannot be negative.');
        }

        $existing = self::getActiveShift($concernId);
        if ($existing) {
            throw new RuntimeException("A shift is already active ({$existing['shift_code']}). Close it first.");
        }

        $shiftCode = 'REG-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999));
        while (Database::fetchOne("SELECT id FROM shop_shifts WHERE shift_code = ?", [$shiftCode])) {
            $shiftCode = 'REG-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999));
        }

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO shop_shifts (concern_id, shift_code, cashier_id, opening_float, opened_at, expected_cash, status, closing_notes)
                 VALUES (?, ?, ?, ?, NOW(), ?, 'OPEN', ?)",
                [$concernId, $shiftCode, $cashierId, $openingFloat, $openingFloat, $notes]
            );
            $shiftId = (int)Database::lastInsertId();

            // Record initial float in transaction ledger
            $txNumber = 'TX-' . date('Ymd') . '-' . sprintf('%04d', rand(100, 9999));
            Database::execute(
                "INSERT INTO cash_flow_transactions (concern_id, shift_id, tx_number, transaction_type, flow, payment_mode, amount, source_or_recipient, reason, created_by, created_at)
                 VALUES (?, ?, ?, 'FLOAT_OPEN', 'INFLOW', 'CASH', ?, 'Till Open Float', 'Opening cash float placed into drawer', ?, NOW())",
                [$concernId, $shiftId, $txNumber, $openingFloat, $cashierId]
            );

            Database::commit();
            return $shiftId;
        } catch (\Throwable $e) {
            if (Database::inTransaction()) Database::rollback();
            throw $e;
        }
    }

    public static function addTopUp(?int $concernId, int $shiftId, float $amount, string $source, string $reason, int $userId): void {
        self::ensureSchema();
        if ($amount <= 0) {
            throw new InvalidArgumentException('Top-up amount must be greater than zero.');
        }

        $txNumber = 'TX-' . date('Ymd') . '-' . sprintf('%04d', rand(100, 9999));
        Database::execute(
            "INSERT INTO cash_flow_transactions (concern_id, shift_id, tx_number, transaction_type, flow, payment_mode, amount, source_or_recipient, reason, created_by, created_at)
             VALUES (?, ?, ?, 'FLOAT_TOPUP', 'INFLOW', 'CASH', ?, ?, ?, ?, NOW())",
            [$concernId, $shiftId, $txNumber, $amount, $source ?: 'Store Owner', $reason ?: 'Cash float injection', $userId]
        );
    }

    public static function recordBankDrop(?int $concernId, int $shiftId, float $amount, string $bankName, string $depositDate, ?string $slipNumber, string $remarks, int $userId): void {
        self::ensureSchema();
        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }
        if (trim($bankName) === '') {
            throw new InvalidArgumentException('Bank name is required.');
        }

        Database::beginTransaction();
        try {
            $depositNumber = 'BD-' . date('Ymd') . '-' . sprintf('%04d', rand(100, 9999));
            Database::execute(
                "INSERT INTO bank_deposits (concern_id, shift_id, deposit_number, amount, bank_name, deposit_date, slip_number, remarks, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$concernId, $shiftId, $depositNumber, $amount, $bankName, $depositDate, $slipNumber ?: null, $remarks, $userId]
            );

            $txNumber = 'TX-' . date('Ymd') . '-' . sprintf('%04d', rand(100, 9999));
            Database::execute(
                "INSERT INTO cash_flow_transactions (concern_id, shift_id, tx_number, transaction_type, flow, payment_mode, amount, source_or_recipient, reference_number, reason, created_by, created_at)
                 VALUES (?, ?, ?, 'BANK_DROP', 'OUTFLOW', 'CASH', ?, ?, ?, ?, ?, NOW())",
                [$concernId, $shiftId, $txNumber, $amount, $bankName, $slipNumber ?: $depositNumber, $remarks ?: 'Bank Cash Drop / Safe Transfer', $userId]
            );

            Database::commit();
        } catch (\Throwable $e) {
            if (Database::inTransaction()) Database::rollback();
            throw $e;
        }
    }

    public static function recordExpense(?int $concernId, int $shiftId, float $amount, string $category, string $paidTo, ?string $voucher, string $reason, int $userId): void {
        self::ensureSchema();
        if ($amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }

        $txNumber = 'TX-' . date('Ymd') . '-' . sprintf('%04d', rand(100, 9999));
        Database::execute(
            "INSERT INTO cash_flow_transactions (concern_id, shift_id, tx_number, transaction_type, flow, payment_mode, amount, source_or_recipient, reference_number, reason, created_by, created_at)
             VALUES (?, ?, ?, 'PETTY_EXPENSE', 'OUTFLOW', 'CASH', ?, ?, ?, ?, ?, NOW())",
            [$concernId, $shiftId, $txNumber, $amount, $paidTo ?: $category, $voucher, $reason ?: "Petty Expense: {$category}", $userId]
        );
    }

    public static function closeShift(int $shiftId, float $actualCash, array $denominations, string $closingNotes, int $closedBy): array {
        self::ensureSchema();
        $shift = Database::fetchOne("SELECT * FROM shop_shifts WHERE id = ? AND status = 'OPEN'", [$shiftId]);
        if (!$shift) {
            throw new RuntimeException('Shift not found or already closed.');
        }

        // Live calculation of expected cash
        $stats = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'FLOAT_TOPUP' THEN amount ELSE 0 END), 0) AS total_topup,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_INFLOW' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'REFUND_OUTFLOW' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS total_refunds,
                COALESCE(SUM(CASE WHEN transaction_type = 'PETTY_EXPENSE' THEN amount ELSE 0 END), 0) AS total_expenses,
                COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DROP' THEN amount ELSE 0 END), 0) AS total_bank_drops
             FROM cash_flow_transactions
             WHERE shift_id = ?",
            [$shiftId]
        );

        $expectedCash = (float)$shift['opening_float'] + (float)$stats['total_topup'] + (float)$stats['total_sales']
            - (float)$stats['total_refunds'] - (float)$stats['total_expenses'] - (float)$stats['total_bank_drops'];

        $variance = round($actualCash - $expectedCash, 2);

        Database::execute(
            "UPDATE shop_shifts 
             SET status = 'CLOSED', closed_at = NOW(), closed_by = ?, expected_cash = ?, actual_cash = ?, variance = ?, denominations = ?, closing_notes = ?
             WHERE id = ?",
            [$closedBy, $expectedCash, $actualCash, $variance, json_encode($denominations), $closingNotes, $shiftId]
        );

        return [
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'variance' => $variance
        ];
    }

    /**
     * Record a POS sale inflow into cash_flow_transactions (linked to active shift if open).
     */
    public static function recordSaleInflow(
        ?int $concernId,
        float $amount,
        string $paymentMode,
        string $billNumber,
        string $partyName,
        int $userId
    ): void {
        self::ensureSchema();
        if ($amount <= 0) {
            return;
        }
        $shift = self::getActiveShift($concernId);
        $shiftId = $shift ? (int)$shift['id'] : null;
        $mode = strtoupper(trim($paymentMode)) ?: 'CASH';
        $txNumber = 'TX-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        Database::execute(
            "INSERT INTO cash_flow_transactions
             (concern_id, shift_id, tx_number, transaction_type, flow, payment_mode, amount, source_or_recipient, reference_number, reason, created_by, created_at)
             VALUES (?, ?, ?, 'SALE_INFLOW', 'INFLOW', ?, ?, ?, ?, ?, ?, NOW())",
            [
                $concernId,
                $shiftId,
                $txNumber,
                $mode,
                $amount,
                $partyName !== '' ? $partyName : 'POS Customer',
                $billNumber,
                "POS Sale ({$mode}) - {$billNumber}",
                $userId > 0 ? $userId : 1,
            ]
        );
    }

    /**
     * Remove previous POS sale inflow entries for a bill (used on bill edit before re-recording).
     */
    public static function clearSaleInflowByBill(string $billNumber): void {
        if ($billNumber === '') return;
        self::ensureSchema();
        Database::execute(
            "DELETE FROM cash_flow_transactions WHERE transaction_type = 'SALE_INFLOW' AND reference_number = ?",
            [$billNumber]
        );
    }
}
