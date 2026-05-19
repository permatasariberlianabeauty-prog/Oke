<?php
/**
 * NOXARA - Wallet & Deposit/Withdraw
 */

function submitDeposit(int $userId, float $amount, int $adminBankId, ?string $proofImage, ?int $voucherId = null, string $note = ''): array
{
    $minDeposit = (float)getSetting('deposit_min', '50000');
    $maxDeposit = (float)getSetting('deposit_max', '100000000');
    if ($amount < $minDeposit) return ['success' => false, 'message' => 'Minimal deposit ' . formatRupiah($minDeposit)];
    if ($amount > $maxDeposit) return ['success' => false, 'message' => 'Maksimal deposit ' . formatRupiah($maxDeposit)];

    $adminBank = db()->fetchOne('SELECT * FROM admin_bank_accounts WHERE id = ? AND is_active = 1 LIMIT 1', 'i', [$adminBankId]);
    if (!$adminBank) return ['success' => false, 'message' => 'Rekening tujuan tidak valid.'];

    // Kode unik
    $uniqueCode  = random_int(1, (int)getSetting('deposit_unique_code_max', '999'));
    $totalAmount = $amount + $uniqueCode;

    // Voucher
    $voucherDiscount = 0.00;
    if ($voucherId) {
        $v = applyVoucher($userId, $voucherId, 'deposit', $amount);
        $voucherDiscount = $v['discount'] ?? 0;
        $totalAmount    -= $voucherDiscount;
    }

    $expiredHours = (int)getSetting('deposit_expired_hours', '3');

    db()->execute(
        'INSERT INTO deposits (user_id, admin_bank_id, amount, unique_code, total_amount, voucher_id, voucher_discount, proof_image, note, expired_at, ip_address)
         VALUES (?,?,?,?,?,?,?,?,?,DATE_ADD(NOW(), INTERVAL ? HOUR),?)',
        'iiiddiddsss',
        [$userId, $adminBankId, $amount, $uniqueCode, $totalAmount, $voucherId, $voucherDiscount, $proofImage, $note, $expiredHours, getClientIp()]
    );

    return ['success' => true, 'unique_code' => $uniqueCode, 'total_amount' => $totalAmount];
}

function confirmDeposit(int $depositId, int $adminId, string $adminNote = ''): array
{
    $deposit = db()->fetchOne('SELECT * FROM deposits WHERE id = ? AND status = "pending" LIMIT 1', 'i', [$depositId]);
    if (!$deposit) return ['success' => false, 'message' => 'Deposit tidak ditemukan atau sudah diproses.'];

    db()->beginTransaction();
    try {
        db()->execute(
            'UPDATE deposits SET status = "confirmed", admin_id = ?, admin_note = ?, confirmed_at = NOW() WHERE id = ?',
            'isi', [$adminId, $adminNote, $depositId]
        );

        $userId = (int)$deposit['user_id'];
        $amount = (float)$deposit['amount'];

        creditBalance($userId, WALLET_MAIN, $amount, TX_DEPOSIT, 'deposit', $depositId,
            'Deposit dikonfirmasi - ' . formatRupiah($amount), $adminId);

        processReferralCommission($userId, $amount, 'deposit', $depositId);
        checkAndUpdateVip($userId);
        updateMissionProgress($userId, MISSION_FIRST_DEPOSIT, 1);

        createNotification($userId, 'deposit_approved', 'Deposit Dikonfirmasi',
            'Deposit Anda sebesar ' . formatRupiah($amount) . ' telah dikonfirmasi.');

        db()->commit();
        return ['success' => true];
    } catch (Throwable $e) {
        db()->rollback();
        return ['success' => false, 'message' => 'Gagal konfirmasi deposit: ' . $e->getMessage()];
    }
}

function rejectDeposit(int $depositId, int $adminId, string $reason): array
{
    $deposit = db()->fetchOne('SELECT * FROM deposits WHERE id = ? AND status = "pending" LIMIT 1', 'i', [$depositId]);
    if (!$deposit) return ['success' => false, 'message' => 'Deposit tidak ditemukan.'];
    db()->execute(
        'UPDATE deposits SET status = "rejected", admin_id = ?, admin_note = ? WHERE id = ?',
        'isi', [$adminId, $reason, $depositId]
    );
    createNotification((int)$deposit['user_id'], 'deposit_rejected', 'Deposit Ditolak',
        'Deposit Anda ditolak. Alasan: ' . $reason);
    return ['success' => true];
}

function submitWithdraw(int $userId, float $amount, int $bankAccountId, string $pin): array
{
    if (!getSetting('withdraw_enabled', '1')) return ['success' => false, 'message' => 'Fitur withdraw sedang ditutup.'];

    // Jam operasional
    $openHour  = (int)getSetting('withdraw_open_hour', '8');
    $closeHour = (int)getSetting('withdraw_close_hour', '20');
    $currentH  = (int)date('H');
    if ($currentH < $openHour || $currentH >= $closeHour) {
        return ['success' => false, 'message' => "Layanan withdraw hanya tersedia pukul {$openHour}:00 - {$closeHour}:00 WIB."];
    }

    // Cek PIN
    if (!verifyPin($userId, $pin)) return ['success' => false, 'message' => 'PIN transaksi salah.'];

    $user = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    $vip  = (int)($user['vip_level'] ?? 0);

    $minWd = getMinWithdraw($vip);
    $fee   = getWithdrawFee($vip);
    if ($amount < $minWd) return ['success' => false, 'message' => 'Minimal withdraw ' . formatRupiah($minWd) . ' untuk VIP ' . $vip];

    $feeAmount = $amount * ($fee / 100);
    $netAmount = $amount - $feeAmount;

    $wallet = getUserWallet($userId);
    if ($wallet['main_balance'] < $amount) return ['success' => false, 'message' => 'Saldo utama tidak mencukupi.'];

    // Cek maks per hari
    $maxDaily = (int)getSetting('withdraw_max_daily', '1');
    $todayWd  = db()->fetchOne(
        'SELECT COUNT(*) as cnt FROM withdrawals WHERE user_id = ? AND DATE(created_at) = CURDATE() AND status NOT IN ("rejected")',
        'i', [$userId]
    );
    if ((int)($todayWd['cnt'] ?? 0) >= $maxDaily) {
        return ['success' => false, 'message' => 'Maksimal ' . $maxDaily . ' kali withdraw per hari.'];
    }

    $bank = db()->fetchOne('SELECT * FROM bank_accounts WHERE id = ? AND user_id = ? LIMIT 1', 'ii', [$bankAccountId, $userId]);
    if (!$bank) return ['success' => false, 'message' => 'Rekening bank tidak valid.'];

    db()->beginTransaction();
    try {
        db()->execute(
            'INSERT INTO withdrawals (user_id, bank_account_id, amount, fee, net_amount, ip_address) VALUES (?,?,?,?,?,?)',
            'iiddds', [$userId, $bankAccountId, $amount, $feeAmount, $netAmount, getClientIp()]
        );
        $wdId = db()->lastInsertId();

        debitBalance($userId, WALLET_MAIN, $amount, TX_WITHDRAW, 'withdraw', $wdId,
            'Penarikan ' . formatRupiah($amount) . ' (fee ' . formatRupiah($feeAmount) . ')');

        db()->commit();
        return ['success' => true, 'net_amount' => $netAmount, 'fee' => $feeAmount];
    } catch (Throwable $e) {
        db()->rollback();
        return ['success' => false, 'message' => 'Gagal mengajukan withdraw.'];
    }
}

function approveWithdraw(int $wdId, int $adminId, string $note = ''): array
{
    $wd = db()->fetchOne('SELECT * FROM withdrawals WHERE id = ? AND status = "pending" LIMIT 1', 'i', [$wdId]);
    if (!$wd) return ['success' => false, 'message' => 'Data tidak ditemukan.'];
    db()->execute(
        'UPDATE withdrawals SET status = "approved", admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?',
        'isi', [$adminId, $note, $wdId]
    );
    createNotification((int)$wd['user_id'], 'withdraw_approved', 'Withdraw Disetujui',
        'Penarikan Anda sebesar ' . formatRupiah((float)$wd['net_amount']) . ' telah disetujui.');
    return ['success' => true];
}

function rejectWithdraw(int $wdId, int $adminId, string $reason): array
{
    $wd = db()->fetchOne('SELECT * FROM withdrawals WHERE id = ? AND status = "pending" LIMIT 1', 'i', [$wdId]);
    if (!$wd) return ['success' => false, 'message' => 'Data tidak ditemukan.'];
    db()->beginTransaction();
    try {
        db()->execute(
            'UPDATE withdrawals SET status = "rejected", admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?',
            'isi', [$adminId, $reason, $wdId]
        );
        // Kembalikan dana ke saldo utama
        creditBalance((int)$wd['user_id'], WALLET_MAIN, (float)$wd['amount'], TX_ADMIN_ADJUST,
            'withdraw', $wdId, 'Withdraw ditolak - dana dikembalikan. Alasan: ' . $reason, $adminId);
        createNotification((int)$wd['user_id'], 'withdraw_rejected', 'Withdraw Ditolak',
            'Penarikan Anda ditolak. Alasan: ' . $reason . '. Dana telah dikembalikan.');
        db()->commit();
        return ['success' => true];
    } catch (Throwable $e) {
        db()->rollback();
        return ['success' => false, 'message' => 'Gagal menolak withdraw.'];
    }
}

function applyVoucher(int $userId, int $voucherId, string $type, float $amount): array
{
    $v = db()->fetchOne(
        'SELECT * FROM vouchers WHERE id = ? AND is_active = 1 AND type = ? AND (expired_at IS NULL OR expired_at > NOW()) LIMIT 1',
        'is', [$voucherId, $type]
    );
    if (!$v) return ['success' => false, 'discount' => 0];
    if ($v['usage_limit'] > 0 && $v['used_count'] >= $v['usage_limit']) return ['success' => false, 'discount' => 0];
    if ($amount < (float)$v['min_amount']) return ['success' => false, 'discount' => 0];

    $user = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if ((int)($user['vip_level'] ?? 0) < (int)$v['min_vip_level']) return ['success' => false, 'discount' => 0];

    $discount = $v['discount_type'] === 'percent'
        ? ($amount * $v['discount_value'] / 100)
        : (float)$v['discount_value'];
    if ($v['max_discount'] > 0) $discount = min($discount, (float)$v['max_discount']);

    db()->execute('UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?', 'i', [$v['id']]);
    return ['success' => true, 'discount' => $discount];
}
