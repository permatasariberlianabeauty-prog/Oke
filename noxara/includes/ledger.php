<?php
/**
 * NOXARA - Transaction Ledger
 * Semua perubahan saldo WAJIB melalui fungsi ini
 */

/**
 * Catat perubahan saldo ke ledger dan update wallet
 * Ini adalah satu-satunya cara saldo berubah
 */
function recordLedger(
    int    $userId,
    string $walletType,
    string $transactionType,
    string $direction,
    float  $amount,
    string $referenceType = '',
    int    $referenceId   = 0,
    string $description   = '',
    ?int   $adminId       = null
): bool {
    if ($amount <= 0) return false;

    $db = db();
    $db->beginTransaction();

    try {
        // Lock row untuk keamanan concurrency
        $wallet = $db->fetchOne(
            'SELECT main_balance, free_balance FROM user_wallets WHERE user_id = ? FOR UPDATE',
            'i', [$userId]
        );

        if (!$wallet) {
            // Buat wallet jika belum ada
            $db->execute('INSERT INTO user_wallets (user_id) VALUES (?)', 'i', [$userId]);
            $wallet = ['main_balance' => 0.00, 'free_balance' => 0.00];
        }

        $balanceBefore = (float)($walletType === WALLET_FREE
            ? $wallet['free_balance']
            : $wallet['main_balance']);

        if ($direction === DIR_DEBIT && $balanceBefore < $amount) {
            $db->rollback();
            return false; // Saldo tidak cukup
        }

        $balanceAfter = $direction === DIR_CREDIT
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        // Update wallet
        $col = $walletType === WALLET_FREE ? 'free_balance' : 'main_balance';
        if ($direction === DIR_CREDIT) {
            $db->execute("UPDATE user_wallets SET {$col} = {$col} + ? WHERE user_id = ?", 'di', [$amount, $userId]);
        } else {
            $db->execute("UPDATE user_wallets SET {$col} = {$col} - ? WHERE user_id = ?", 'di', [$amount, $userId]);
        }

        // Update aggregat
        match ($transactionType) {
            TX_DEPOSIT       => $db->execute('UPDATE user_wallets SET total_deposit = total_deposit + ? WHERE user_id = ?', 'di', [$amount, $userId]),
            TX_WITHDRAW      => $db->execute('UPDATE user_wallets SET total_withdraw = total_withdraw + ? WHERE user_id = ?', 'di', [$amount, $userId]),
            TX_PROFIT        => $db->execute('UPDATE user_wallets SET total_profit = total_profit + ? WHERE user_id = ?', 'di', [$amount, $userId]),
            TX_REFERRAL      => $db->execute('UPDATE user_wallets SET total_referral = total_referral + ? WHERE user_id = ?', 'di', [$amount, $userId]),
            default          => null,
        };

        // Catat ke ledger
        $db->execute(
            'INSERT INTO transaction_ledger
             (user_id, wallet_type, transaction_type, direction, amount, balance_before, balance_after,
              reference_type, reference_id, admin_id, description, ip_address, user_agent)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            'isssdddssisss',
            [
                $userId, $walletType, $transactionType, $direction,
                $amount, $balanceBefore, $balanceAfter,
                $referenceType, $referenceId, $adminId,
                $description, getClientIp(), getUserAgent()
            ]
        );

        // Catat ke transactions (ringkas)
        $db->execute(
            'INSERT INTO transactions (user_id, type, wallet_type, direction, amount, balance_before, balance_after, reference_type, reference_id, description)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            'isssdddsis',
            [$userId, $transactionType, $walletType, $direction, $amount, $balanceBefore, $balanceAfter, $referenceType, $referenceId, $description]
        );

        $db->commit();
        return true;

    } catch (Throwable $e) {
        $db->rollback();
        writeLog('ledger_errors.log', 'Ledger error user_id=' . $userId . ' | ' . $e->getMessage());
        return false;
    }
}

/**
 * Tambah saldo (credit)
 */
function creditBalance(int $userId, string $walletType, float $amount, string $txType, string $refType = '', int $refId = 0, string $desc = '', ?int $adminId = null): bool
{
    return recordLedger($userId, $walletType, $txType, DIR_CREDIT, $amount, $refType, $refId, $desc, $adminId);
}

/**
 * Kurangi saldo (debit) - return false jika saldo tidak cukup
 */
function debitBalance(int $userId, string $walletType, float $amount, string $txType, string $refType = '', int $refId = 0, string $desc = '', ?int $adminId = null): bool
{
    return recordLedger($userId, $walletType, $txType, DIR_DEBIT, $amount, $refType, $refId, $desc, $adminId);
}

/**
 * Ambil saldo user
 */
function getUserWallet(int $userId): array
{
    $wallet = db()->fetchOne('SELECT * FROM user_wallets WHERE user_id = ? LIMIT 1', 'i', [$userId]);
    if (!$wallet) {
        db()->execute('INSERT INTO user_wallets (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id', 'i', [$userId]);
        return [
            'user_id'        => $userId,
            'main_balance'   => 0.00,
            'free_balance'   => 0.00,
            'total_deposit'  => 0.00,
            'total_withdraw' => 0.00,
            'total_profit'   => 0.00,
            'total_referral' => 0.00,
        ];
    }
    return $wallet;
}

/**
 * Koreksi saldo oleh admin - wajib masuk admin_logs
 */
function adminAdjustBalance(int $userId, string $walletType, string $direction, float $amount, string $reason, int $adminId): bool
{
    $result = recordLedger(
        $userId, $walletType, TX_ADMIN_ADJUST, $direction, $amount,
        'admin_adjustment', 0, $reason, $adminId
    );
    if ($result) {
        logActivity('adjust_balance', 'user', $userId,
            "Koreksi saldo {$walletType} {$direction} " . formatRupiah($amount) . " | Alasan: {$reason}");
    }
    return $result;
}
