<?php
/**
 * NOXARA - Mission System
 */

function updateMissionProgress(int $userId, string $actionType, int $value = 1): void
{
    $missions = db()->fetchAll(
        'SELECT m.* FROM missions m WHERE m.action_type = ? AND m.is_active = 1',
        's', [$actionType]
    );
    foreach ($missions as $mission) {
        $period = getMissionPeriod($mission['type']);
        $um = db()->fetchOne(
            'SELECT * FROM user_missions WHERE user_id = ? AND mission_id = ? AND status != "claimed"
             AND (period_start IS NULL OR period_start = ?) LIMIT 1',
            'iis', [$userId, $mission['id'], $period['start']]
        );

        if (!$um) {
            if ($mission['type'] === 'milestone') {
                $exists = db()->fetchOne(
                    'SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ? AND status = "claimed" LIMIT 1',
                    'ii', [$userId, $mission['id']]
                );
                if ($exists) continue;
            }
            db()->execute(
                'INSERT INTO user_missions (user_id, mission_id, current_value, status, period_start, period_end)
                 VALUES (?,?,?,?,?,?)',
                'iiisss', [$userId, $mission['id'], 0, 'in_progress', $period['start'], $period['end']]
            );
            $um = db()->fetchOne('SELECT * FROM user_missions WHERE user_id = ? AND mission_id = ? AND status = "in_progress" LIMIT 1', 'ii', [$userId, $mission['id']]);
        }
        if (!$um || $um['status'] !== 'in_progress') continue;

        $newValue = $actionType === MISSION_VIP_LEVEL ? $value : ((int)$um['current_value'] + $value);
        $target   = (int)$mission['target_value'];
        $status   = $newValue >= $target ? 'completed' : 'in_progress';
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;

        db()->execute(
            'UPDATE user_missions SET current_value = ?, status = ?, completed_at = ? WHERE id = ?',
            'issi', [$newValue, $status, $completedAt, $um['id']]
        );
    }
}

function claimMissionReward(int $userId, int $userMissionId): array
{
    $um = db()->fetchOne(
        'SELECT um.*, m.title, m.reward_type, m.reward_value, m.reward_voucher_id
         FROM user_missions um JOIN missions m ON m.id = um.mission_id
         WHERE um.id = ? AND um.user_id = ? AND um.status = "completed" LIMIT 1',
        'ii', [$userMissionId, $userId]
    );
    if (!$um) return ['success' => false, 'message' => 'Misi tidak ditemukan atau belum selesai.'];

    db()->execute('UPDATE user_missions SET status = "claimed", claimed_at = NOW() WHERE id = ?', 'i', [$userMissionId]);

    if ($um['reward_type'] === 'free_balance') {
        creditBalance($userId, WALLET_FREE, (float)$um['reward_value'], TX_BONUS_MISSION,
            'mission', $userMissionId, 'Reward misi: ' . $um['title']);
    } elseif ($um['reward_type'] === 'voucher' && $um['reward_voucher_id']) {
        db()->execute(
            'INSERT INTO user_vouchers (user_id, voucher_id) VALUES (?,?)',
            'ii', [$userId, $um['reward_voucher_id']]
        );
    }
    return ['success' => true, 'reward_type' => $um['reward_type'], 'reward_value' => $um['reward_value']];
}

function getMissionPeriod(string $type): array
{
    return match($type) {
        'daily'   => ['start' => date('Y-m-d'), 'end' => date('Y-m-d')],
        'weekly'  => ['start' => date('Y-m-d', strtotime('monday this week')), 'end' => date('Y-m-d', strtotime('sunday this week'))],
        default   => ['start' => null, 'end' => null],
    };
}

function claimDailyReward(int $userId): array
{
    $settings = db()->fetchOne('SELECT * FROM daily_reward_settings LIMIT 1');
    if (!$settings || !$settings['is_enabled']) return ['success' => false, 'message' => 'Hadiah harian tidak aktif.'];

    $today = date('Y-m-d');
    $claimed = db()->fetchOne(
        'SELECT id FROM user_daily_claims WHERE user_id = ? AND claim_date = ? LIMIT 1',
        'is', [$userId, $today]
    );
    if ($claimed) return ['success' => false, 'message' => 'Sudah klaim hadiah harian hari ini.'];

    $items = db()->fetchAll('SELECT * FROM daily_reward_items WHERE is_active = 1 ORDER BY sort_order ASC');
    if (empty($items)) return ['success' => false, 'message' => 'Belum ada item hadiah.'];

    $rand = mt_rand(1, 10000) / 100;
    $cumulative = 0;
    $selected = $items[0];
    foreach ($items as $item) {
        $cumulative += (float)$item['probability'];
        if ($rand <= $cumulative) { $selected = $item; break; }
    }

    db()->execute(
        'INSERT INTO user_daily_claims (user_id, reward_item_id, reward_value, claim_date) VALUES (?,?,?,?)',
        'iids', [$userId, $selected['id'], $selected['value'], $today]
    );

    if ($selected['type'] === 'free_balance' || $selected['type'] === 'jackpot') {
        creditBalance($userId, WALLET_FREE, (float)$selected['value'], TX_BONUS_DAILY,
            'daily_reward', db()->lastInsertId(), 'Hadiah harian: ' . $selected['name']);
    }

    return ['success' => true, 'item' => $selected];
}
