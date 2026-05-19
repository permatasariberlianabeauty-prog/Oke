<?php
/**
 * NOXARA - Referral System (3 Level)
 */

function processReferralChain(int $newUserId, int $referrerId): void
{
    // Level 1
    db()->execute(
        'INSERT IGNORE INTO referrals (referrer_id, referred_id, level) VALUES (?,?,1)',
        'ii', [$referrerId, $newUserId]
    );
    // Misi referral
    updateMissionProgress($referrerId, MISSION_REFERRAL, 1);

    // Level 2
    $level2 = db()->fetchOne('SELECT referred_by FROM users WHERE id = ? LIMIT 1', 'i', [$referrerId]);
    if ($level2 && $level2['referred_by']) {
        db()->execute(
            'INSERT IGNORE INTO referrals (referrer_id, referred_id, level) VALUES (?,?,2)',
            'ii', [$level2['referred_by'], $newUserId]
        );
        updateMissionProgress((int)$level2['referred_by'], MISSION_REFERRAL, 1);

        // Level 3
        $level3 = db()->fetchOne('SELECT referred_by FROM users WHERE id = ? LIMIT 1', 'i', [$level2['referred_by']]);
        if ($level3 && $level3['referred_by']) {
            db()->execute(
                'INSERT IGNORE INTO referrals (referrer_id, referred_id, level) VALUES (?,?,3)',
                'ii', [$level3['referred_by'], $newUserId]
            );
        }
    }
}

function processReferralCommission(int $fromUserId, float $amount, string $type, int $referenceId): void
{
    if (!getSetting('referral_enabled', '1')) return;

    $user = db()->fetchOne('SELECT referred_by FROM users WHERE id = ? LIMIT 1', 'i', [$fromUserId]);
    if (!$user || !$user['referred_by']) return;

    $level1 = (int)$user['referred_by'];
    $level2row = db()->fetchOne('SELECT referred_by FROM users WHERE id = ? LIMIT 1', 'i', [$level1]);
    $level2 = $level2row ? (int)$level2row['referred_by'] : 0;
    $level3 = 0;
    if ($level2) {
        $level3row = db()->fetchOne('SELECT referred_by FROM users WHERE id = ? LIMIT 1', 'i', [$level2]);
        $level3 = $level3row ? (int)$level3row['referred_by'] : 0;
    }

    $levels = [$level1, $level2, $level3];
    for ($i = 0; $i < 3; $i++) {
        if (!$levels[$i]) continue;
        $lvl    = $i + 1;
        $setting = db()->fetchOne(
            'SELECT percent FROM commission_settings WHERE type = ? AND level = ? AND is_active = 1 LIMIT 1',
            'si', [$type, $lvl]
        );
        if (!$setting) continue;
        $percent    = (float)$setting['percent'];
        $commission = round($amount * ($percent / 100), 2);
        if ($commission <= 0) continue;

        db()->execute(
            'INSERT INTO commissions (user_id, from_user_id, type, level, base_amount, percent, commission_amount, reference_id)
             VALUES (?,?,?,?,?,?,?,?)',
            'iisiiddi',
            [$levels[$i], $fromUserId, $type, $lvl, $amount, $percent, $commission, $referenceId]
        );

        creditBalance($levels[$i], WALLET_MAIN, $commission, TX_REFERRAL,
            'commission', db()->lastInsertId(),
            "Komisi referral level {$lvl} dari transaksi " . formatRupiah($amount));
    }
}

function getReferralTree(int $userId, int $depth = 3): array
{
    $tree = [];
    $queue = [[$userId, 0]];
    while (!empty($queue)) {
        [$currentId, $currentDepth] = array_shift($queue);
        if ($currentDepth >= $depth) continue;
        $children = db()->fetchAll(
            'SELECT u.id, u.username, u.full_name, u.vip_level, u.created_at, u.is_active
             FROM users u
             JOIN referrals r ON r.referred_id = u.id
             WHERE r.referrer_id = ? AND r.level = 1',
            'i', [$currentId]
        );
        foreach ($children as $child) {
            $child['depth']  = $currentDepth + 1;
            $child['parent'] = $currentId;
            $tree[]          = $child;
            $queue[]         = [$child['id'], $currentDepth + 1];
        }
    }
    return $tree;
}

function getReferralStats(int $userId): array
{
    $total = db()->fetchOne('SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = ? AND level = 1', 'i', [$userId]);
    $active = db()->fetchOne(
        'SELECT COUNT(*) as cnt FROM referrals r JOIN users u ON u.id = r.referred_id WHERE r.referrer_id = ? AND r.level = 1 AND u.is_active = 1',
        'i', [$userId]
    );
    $earned = db()->fetchOne('SELECT COALESCE(SUM(commission_amount),0) as total FROM commissions WHERE user_id = ?', 'i', [$userId]);
    return [
        'total_referral'  => (int)($total['cnt'] ?? 0),
        'active_referral' => (int)($active['cnt'] ?? 0),
        'total_earned'    => (float)($earned['total'] ?? 0),
    ];
}
