<?php
/**
 * NOXARA - Mining / Investment Package System
 */

function purchaseProduct(int $userId, int $productId, ?string $voucherCode = null): array
{
    $product = db()->fetchOne('SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1', 'i', [$productId]);
    if (!$product) return ['success' => false, 'message' => 'Paket tidak ditemukan atau tidak aktif.'];

    $user = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if ((int)($user['vip_level'] ?? 0) < (int)$product['min_vip_level']) {
        return ['success' => false, 'message' => 'Level VIP Anda tidak memenuhi syarat untuk paket ini.'];
    }

    $price = (float)$product['price'];
    $wallet = getUserWallet($userId);

    // Voucher diskon
    $discount = 0.00;
    if (!empty($voucherCode)) {
        $v = db()->fetchOne('SELECT * FROM vouchers WHERE code = ? AND is_active = 1 AND type = "product" LIMIT 1', 's', [$voucherCode]);
        if ($v) {
            $vResult  = applyVoucher($userId, (int)$v['id'], 'product', $price);
            $discount = $vResult['discount'] ?? 0;
        }
    }
    $finalPrice = max(0, $price - $discount);

    // Pakai saldo gratis dulu, lalu saldo utama
    $freeBalance = (float)$wallet['free_balance'];
    $mainBalance = (float)$wallet['main_balance'];

    $freeUsed = min($freeBalance, $finalPrice);
    $mainUsed = $finalPrice - $freeUsed;

    if ($mainUsed > $mainBalance) {
        return ['success' => false, 'message' => 'Saldo tidak mencukupi. Saldo utama kurang ' . formatRupiah($mainUsed - $mainBalance)];
    }

    $walletUsed = match(true) {
        $freeUsed > 0 && $mainUsed > 0 => 'mixed',
        $freeUsed > 0                   => 'free',
        default                         => 'main',
    };

    db()->beginTransaction();
    try {
        $expiredAt = date('Y-m-d H:i:s', strtotime('+' . $product['duration_days'] . ' days'));

        db()->execute(
            'INSERT INTO user_products (user_id, product_id, price_paid, profit_per_day, duration_days, wallet_used, free_amount_used, main_amount_used, expired_at)
             VALUES (?,?,?,?,?,?,?,?,?)',
            'iiiddiids',
            [$userId, $productId, $finalPrice, $product['profit_per_day'], $product['duration_days'],
             $walletUsed, $freeUsed, $mainUsed, $expiredAt]
        );
        $upId = db()->lastInsertId();

        if ($freeUsed > 0) {
            debitBalance($userId, WALLET_FREE, $freeUsed, TX_PURCHASE, 'user_product', $upId,
                'Beli paket ' . $product['name'] . ' (saldo gratis)');
        }
        if ($mainUsed > 0) {
            debitBalance($userId, WALLET_MAIN, $mainUsed, TX_PURCHASE, 'user_product', $upId,
                'Beli paket ' . $product['name']);
        }

        // Komisi referral untuk pembelian
        processReferralCommission($userId, $finalPrice, 'purchase', $upId);
        updateMissionProgress($userId, MISSION_FIRST_PURCHASE, 1);

        createNotification($userId, 'purchase_success', 'Pembelian Berhasil',
            'Paket ' . $product['name'] . ' berhasil dibeli. Mulai klaim profit harian!');

        db()->commit();
        return ['success' => true, 'user_product_id' => $upId];
    } catch (Throwable $e) {
        db()->rollback();
        writeLog('purchase_errors.log', $e->getMessage());
        return ['success' => false, 'message' => 'Pembelian gagal. Silakan coba lagi.'];
    }
}

function claimDailyProfit(int $userId, int $userProductId): array
{
    $up = db()->fetchOne(
        'SELECT up.*, p.name as product_name FROM user_products up
         JOIN products p ON p.id = up.product_id
         WHERE up.id = ? AND up.user_id = ? AND up.status = "active" LIMIT 1',
        'ii', [$userProductId, $userId]
    );
    if (!$up) return ['success' => false, 'message' => 'Paket tidak ditemukan atau tidak aktif.'];

    $today = date('Y-m-d');
    if ($up['last_claim_date'] === $today) {
        $nextClaim = strtotime('tomorrow midnight');
        $remaining = $nextClaim - time();
        $hh = floor($remaining / 3600);
        $mm = floor(($remaining % 3600) / 60);
        $ss = $remaining % 60;
        return ['success' => false, 'message' => "Sudah klaim hari ini. Klaim berikutnya: {$hh}j {$mm}m {$ss}d", 'countdown' => $remaining];
    }

    if (strtotime($up['expired_at']) < time()) {
        db()->execute('UPDATE user_products SET status = "expired" WHERE id = ?', 'i', [$userProductId]);
        return ['success' => false, 'message' => 'Paket sudah kadaluarsa.'];
    }

    $profit = (float)$up['profit_per_day'];

    db()->beginTransaction();
    try {
        db()->execute(
            'UPDATE user_products SET last_claim_date = ?, days_claimed = days_claimed + 1, total_profit_earned = total_profit_earned + ? WHERE id = ?',
            'sdi', [$today, $profit, $userProductId]
        );

        creditBalance($userId, WALLET_MAIN, $profit, TX_PROFIT, 'user_product', $userProductId,
            'Profit harian paket ' . $up['product_name']);

        db()->execute(
            'INSERT INTO mining_logs (user_id, user_product_id, amount, claim_date, ip_address) VALUES (?,?,?,?,?)',
            'iidss', [$userId, $userProductId, $profit, $today, getClientIp()]
        );

        updateMissionProgress($userId, MISSION_CLAIM_PROFIT, 1);

        // Cek apakah durasi selesai
        if ((int)$up['days_claimed'] + 1 >= (int)$up['duration_days']) {
            db()->execute('UPDATE user_products SET status = "completed" WHERE id = ?', 'i', [$userProductId]);
            // Kembalikan modal
            if (!$up['modal_returned']) {
                creditBalance($userId, WALLET_MAIN, (float)$up['price_paid'], TX_MODAL_RETURN,
                    'user_product', $userProductId, 'Modal paket ' . $up['product_name'] . ' dikembalikan');
                db()->execute('UPDATE user_products SET modal_returned = 1 WHERE id = ?', 'i', [$userProductId]);
            }
        }

        db()->commit();
        return ['success' => true, 'profit' => $profit];
    } catch (Throwable $e) {
        db()->rollback();
        return ['success' => false, 'message' => 'Gagal klaim profit.'];
    }
}

function getUserActivePackages(int $userId): array
{
    return db()->fetchAll(
        'SELECT up.*, p.name as product_name, p.image, pc.name as category_name
         FROM user_products up
         JOIN products p ON p.id = up.product_id
         JOIN product_categories pc ON pc.id = p.category_id
         WHERE up.user_id = ? AND up.status = "active"
         ORDER BY up.created_at DESC',
        'i', [$userId]
    );
}

function getTodayProfit(int $userId): float
{
    $today = date('Y-m-d');
    $r = db()->fetchOne(
        'SELECT COALESCE(SUM(amount), 0) as total FROM mining_logs WHERE user_id = ? AND claim_date = ?',
        'is', [$userId, $today]
    );
    return (float)($r['total'] ?? 0);
}

function getClaimablePackages(int $userId): array
{
    $today = date('Y-m-d');
    return db()->fetchAll(
        'SELECT up.*, p.name as product_name FROM user_products up
         JOIN products p ON p.id = up.product_id
         WHERE up.user_id = ? AND up.status = "active"
         AND (up.last_claim_date IS NULL OR up.last_claim_date < ?)
         AND up.expired_at > NOW()',
        'is', [$userId, $today]
    );
}
