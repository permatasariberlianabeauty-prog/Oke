<?php
/**
 * NOXARA - VIP System
 */

function getUserVipLevel(int $userId): array
{
    $wallet = db()->fetchOne('SELECT total_deposit FROM user_wallets WHERE user_id = ? LIMIT 1', 'i', [$userId]);
    $totalDeposit = (float)($wallet['total_deposit'] ?? 0);
    $levels = db()->fetchAll('SELECT * FROM vip_levels WHERE is_active = 1 ORDER BY level DESC');
    foreach ($levels as $level) {
        if ($totalDeposit >= (float)$level['min_deposit']) {
            return $level;
        }
    }
    return db()->fetchOne('SELECT * FROM vip_levels WHERE level = 0 LIMIT 1') ?? [];
}

function checkAndUpdateVip(int $userId): bool
{
    $newLevel = getUserVipLevel($userId);
    if (empty($newLevel)) return false;
    $user = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if (!$user) return false;
    $currentLevel = (int)$user['vip_level'];
    $calcLevel    = (int)$newLevel['level'];
    if ($calcLevel > $currentLevel) {
        db()->execute('UPDATE users SET vip_level = ? WHERE id = ?', 'ii', [$calcLevel, $userId]);
        SessionManager::set('user_vip', $calcLevel);
        // Notifikasi naik VIP
        createNotification($userId, 'vip_upgrade', 'Selamat! Level VIP Naik',
            'Anda telah naik ke ' . $newLevel['name'] . '. Nikmati keuntungan lebih besar!');
        updateMissionProgress($userId, MISSION_VIP_LEVEL, $calcLevel);
        return true;
    }
    return false;
}

function getVipLevel(int $level): array
{
    return db()->fetchOne('SELECT * FROM vip_levels WHERE level = ? LIMIT 1', 'i', [$level]) ?? [];
}

function getVipLevels(): array
{
    return db()->fetchAll('SELECT * FROM vip_levels WHERE is_active = 1 ORDER BY level ASC');
}

function getWithdrawFee(int $vipLevel): float
{
    $vip = getVipLevel($vipLevel);
    return (float)($vip['withdraw_fee_percent'] ?? 15.00);
}

function getMinWithdraw(int $vipLevel): float
{
    $vip = getVipLevel($vipLevel);
    return (float)($vip['min_withdraw'] ?? 100000.00);
}
