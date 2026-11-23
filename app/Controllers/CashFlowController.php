<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Services/CashFlowService.php';

class CashFlowController {

    public function index(): void {
        if (!hasPermission('cash_flow', 'view') && !hasPermission('shop_cash', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view Cash Management.');
            return;
        }

        CashFlowService::ensureSchema();
        $cid = currentConcernId();

        $activeShift = CashFlowService::getActiveShift($cid);

        // Fetch Concern name
        $concernName = 'All Concerns';
        if ($cid) {
            $concern = Database::fetchOne("SELECT name FROM concerns WHERE id = ?", [$cid]);
            $concernName = $concern['name'] ?? 'Concern #' . $cid;
        }

        // Concern scope for queries
        $scope = '';
        $params = [];
        if ($cid) {
            $scope = ($cid === 1) ? 'WHERE (c.concern_id = ? OR c.concern_id IS NULL)' : 'WHERE c.concern_id = ?';
            $params[] = $cid;
        }

        // Today's statistics
        $todayWhere = $scope ? ($scope . " AND DATE(c.created_at) = CURDATE()") : "WHERE DATE(c.created_at) = CURDATE()";
        $todayStats = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN flow = 'INFLOW' AND transaction_type = 'SALE_INFLOW' THEN amount ELSE 0 END), 0) AS today_cash_sales,
                COALESCE(SUM(CASE WHEN flow = 'INFLOW' THEN amount ELSE 0 END), 0) AS today_total_inflow,
                COALESCE(SUM(CASE WHEN flow = 'OUTFLOW' THEN amount ELSE 0 END), 0) AS today_total_outflow,
                COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DROP' THEN amount ELSE 0 END), 0) AS today_bank_deposits
             FROM cash_flow_transactions c {$todayWhere}",
            $params
        );

        $todayCashInflow = (float)($todayStats['today_total_inflow'] ?? 0);
        $todayCashSales = (float)($todayStats['today_cash_sales'] ?? 0);
        $todayBankDeposits = (float)($todayStats['today_bank_deposits'] ?? 0);
        $netCashPosition = $todayCashInflow - (float)($todayStats['today_total_outflow'] ?? 0);

        // Digital Channels (UPI, Card from sales if available or mock baseline)
        $digitalStats = [
            'total' => 0.00,
            'upi' => 0.00,
            'card' => 0.00
        ];

        // 1. Shift Logs (All closed and open shifts)
        $shiftScope = '';
        $shiftParams = [];
        if ($cid) {
            $shiftScope = ($cid === 1) ? 'WHERE (s.concern_id = ? OR s.concern_id IS NULL)' : 'WHERE s.concern_id = ?';
            $shiftParams[] = $cid;
        }
        $shiftLogs = Database::fetchAll(
            "SELECT s.*, u.full_name AS cashier_name, cb.full_name AS closed_by_name
             FROM shop_shifts s
             LEFT JOIN users u ON u.id = s.cashier_id
             LEFT JOIN users cb ON cb.id = s.closed_by
             {$shiftScope}
             ORDER BY s.id DESC LIMIT 100",
            $shiftParams
        );

        // 2. Petty Expenses
        $expenseWhere = $scope ? ($scope . " AND c.transaction_type = 'PETTY_EXPENSE'") : "WHERE c.transaction_type = 'PETTY_EXPENSE'";
        $expenses = Database::fetchAll(
            "SELECT c.*, u.full_name AS user_name, s.shift_code
             FROM cash_flow_transactions c
             LEFT JOIN users u ON u.id = c.created_by
             LEFT JOIN shop_shifts s ON s.id = c.shift_id
             {$expenseWhere}
             ORDER BY c.id DESC LIMIT 100",
            $params
        );

        // 3. Bank Deposits
        $bdScope = '';
        $bdParams = [];
        if ($cid) {
            $bdScope = ($cid === 1) ? 'WHERE (b.concern_id = ? OR b.concern_id IS NULL)' : 'WHERE b.concern_id = ?';
            $bdParams[] = $cid;
        }
        $bankDeposits = Database::fetchAll(
            "SELECT b.*, u.full_name AS user_name, s.shift_code
             FROM bank_deposits b
             LEFT JOIN users u ON u.id = b.created_by
             LEFT JOIN shop_shifts s ON s.id = b.shift_id
             {$bdScope}
             ORDER BY b.id DESC LIMIT 100",
            $bdParams
        );

        // 4. Cash Flow Ledger (All transactions)
        $transactions = Database::fetchAll(
            "SELECT c.*, u.full_name AS user_name, s.shift_code
             FROM cash_flow_transactions c
             LEFT JOIN users u ON u.id = c.created_by
             LEFT JOIN shop_shifts s ON s.id = c.shift_id
             {$scope}
             ORDER BY c.id DESC LIMIT 200",
            $params
        );

        Response::view('production.cash_flow.index', [
            'pageTitle' => 'Cash Management',
            'activeModule' => 'production',
            'activeMenu' => 'cash-flow',
            'breadcrumb' => [
                ['label' => 'All Modules', 'url' => url('purchases')],
                ['label' => 'Cash Management'],
            ],
            'concernName' => $concernName,
            'activeShift' => $activeShift,
            'todayCashInflow' => $todayCashInflow,
            'todayCashSales' => $todayCashSales,
            'todayBankDeposits' => $todayBankDeposits,
            'netCashPosition' => $netCashPosition,
            'digitalStats' => $digitalStats,
            'shiftLogs' => $shiftLogs,
            'expenses' => $expenses,
            'bankDeposits' => $bankDeposits,
            'transactions' => $transactions,
        ]);
    }

    public function openShift(): void {
        CashFlowService::ensureSchema();
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }
        $cid = currentConcernId();
        $cashierId = (int)(Session::get('user_id') ?? 1);
        $floatAmount = (float)($_POST['opening_float'] ?? 0);
        $notes = trim((string)($_POST['opening_notes'] ?? ''));

        try {
            $shiftId = CashFlowService::openShift($cid, $cashierId, $floatAmount, $notes);
            Session::flash('success', 'Till shift opened successfully with ₹' . number_format($floatAmount, 2) . ' opening float.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash-flow'));
    }

    public function topUp(): void {
        CashFlowService::ensureSchema();
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }
        $cid = currentConcernId();
        $activeShift = CashFlowService::getActiveShift($cid);
        if (!$activeShift) {
            Session::flash('error', 'No active shift found. Open a shift first.');
            Response::redirect(url('production/cash-flow'));
            return;
        }

        $amount = (float)($_POST['top_up_amount'] ?? 0);
        $source = trim((string)($_POST['source'] ?? 'Store Owner'));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            CashFlowService::addTopUp($cid, (int)$activeShift['id'], $amount, $source, $reason, $userId);
            Session::flash('success', 'Cash float top-up of ₹' . number_format($amount, 2) . ' added to drawer.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash-flow'));
    }

    public function recordExpense(): void {
        CashFlowService::ensureSchema();
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }
        $cid = currentConcernId();
        $activeShift = CashFlowService::getActiveShift($cid);
        if (!$activeShift) {
            Session::flash('error', 'No active shift found. Open a shift first.');
            Response::redirect(url('production/cash-flow'));
            return;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        $category = trim((string)($_POST['category'] ?? 'Miscellaneous'));
        $paidTo = trim((string)($_POST['paid_to'] ?? ''));
        $voucher = trim((string)($_POST['voucher'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            CashFlowService::recordExpense($cid, (int)$activeShift['id'], $amount, $category, $paidTo, $voucher ?: null, $reason, $userId);
            Session::flash('success', 'Expense of ₹' . number_format($amount, 2) . ' recorded and deducted from drawer.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash-flow'));
    }

    public function bankDrop(): void {
        CashFlowService::ensureSchema();
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }
        $cid = currentConcernId();
        $activeShift = CashFlowService::getActiveShift($cid);
        if (!$activeShift) {
            Session::flash('error', 'No active shift found. Open a shift first.');
            Response::redirect(url('production/cash-flow'));
            return;
        }

        $amount = (float)($_POST['deposit_amount'] ?? 0);
        $bankName = trim((string)($_POST['bank_name'] ?? 'Primary Bank'));
        $depositDate = trim((string)($_POST['deposit_date'] ?? date('Y-m-d')));
        $slipNumber = trim((string)($_POST['slip_number'] ?? ''));
        $remarks = trim((string)($_POST['remarks'] ?? ''));
        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            CashFlowService::recordBankDrop($cid, (int)$activeShift['id'], $amount, $bankName, $depositDate, $slipNumber ?: null, $remarks, $userId);
            Session::flash('success', 'Bank cash drop of ₹' . number_format($amount, 2) . ' recorded successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash-flow'));
    }

    public function closeShift(): void {
        CashFlowService::ensureSchema();
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
            return;
        }
        $cid = currentConcernId();
        $activeShift = CashFlowService::getActiveShift($cid);
        if (!$activeShift) {
            Session::flash('error', 'No active shift found to close.');
            Response::redirect(url('production/cash-flow'));
            return;
        }

        $actualCash = (float)($_POST['actual_cash'] ?? 0);
        $closingNotes = trim((string)($_POST['closing_notes'] ?? ''));
        $denominations = [
            'c500' => (int)($_POST['denom_500'] ?? 0),
            'c200' => (int)($_POST['denom_200'] ?? 0),
            'c100' => (int)($_POST['denom_100'] ?? 0),
            'c50'  => (int)($_POST['denom_50'] ?? 0),
            'c20'  => (int)($_POST['denom_20'] ?? 0),
            'c10'  => (int)($_POST['denom_10'] ?? 0),
            'c5'   => (int)($_POST['denom_5'] ?? 0),
            'coins'=> (float)($_POST['denom_coins'] ?? 0)
        ];
        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            $result = CashFlowService::closeShift((int)$activeShift['id'], $actualCash, $denominations, $closingNotes, $userId);
            $varMsg = ($result['variance'] == 0)
                ? 'Exact match with zero variance!'
                : (($result['variance'] > 0) ? ('Cash surplus of ₹' . number_format($result['variance'], 2)) : ('Cash shortage of ₹' . number_format(abs($result['variance']), 2)));
            Session::flash('success', "Shift {$activeShift['shift_code']} closed successfully. Expected: ₹" . number_format($result['expected_cash'], 2) . " | Counted: ₹" . number_format($result['actual_cash'], 2) . " ({$varMsg})");
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash-flow'));
    }

    public function exportCsv(): void {
        CashFlowService::ensureSchema();
        $cid = currentConcernId();
        $scope = '';
        $params = [];
        if ($cid) {
            $scope = ($cid === 1) ? 'WHERE (c.concern_id = ? OR c.concern_id IS NULL)' : 'WHERE c.concern_id = ?';
            $params[] = $cid;
        }
        $transactions = Database::fetchAll(
            "SELECT c.tx_number, c.created_at, c.transaction_type, c.flow, c.payment_mode, c.amount,
                    s.shift_code, c.source_or_recipient, c.reference_number, c.reason, u.full_name AS user_name
             FROM cash_flow_transactions c
             LEFT JOIN users u ON u.id = c.created_by
             LEFT JOIN shop_shifts s ON s.id = c.shift_id
             {$scope}
             ORDER BY c.id DESC",
            $params
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cash_flow_ledger_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['TX #', 'Date & Time', 'Type', 'Flow', 'Mode', 'Amount (₹)', 'Shift #', 'Source / Recipient', 'Ref #', 'Reason', 'Handled By']);

        foreach ($transactions as $t) {
            fputcsv($out, [
                $t['tx_number'],
                $t['created_at'],
                $t['transaction_type'],
                $t['flow'],
                $t['payment_mode'],
                number_format((float)$t['amount'], 2, '.', ''),
                $t['shift_code'] ?? '—',
                $t['source_or_recipient'] ?? '—',
                $t['reference_number'] ?? '—',
                $t['reason'] ?? '—',
                $t['user_name'] ?? '—'
            ]);
        }
        fclose($out);
        exit;
    }
}
