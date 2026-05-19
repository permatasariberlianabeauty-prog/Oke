<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/vip.php';

header('Content-Type: application/json; charset=utf-8');

if (!SessionManager::isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = SessionManager::userId();
$action = clean(getVal('action', 'status'));

match ($action) {
    'status' => apiVipStatus($userId),
    'levels' => apiVipLevels(),
    default  => jsonResponse(['success' => false, 'message' => 'Aksi tidak valid'], 400),
};

function apiVipStatus(int $userId): never
{
    $user         = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    $currentLevel = (int)($user['vip_level'] ?? 0);
    $wallet       = getUserWallet($userId);
    $totalDeposit = (float)$wallet['total_deposit'];
    $vipData      = getVipLevel($currentLevel);
    $levels       = getVipLevels();

    $nextLevel = null;
    foreach ($levels as $lvl) {
        if ((int)$lvl['level'] > $currentLevel) { $nextLevel = $lvl; break; }
    }

    $progressPercent = 0;
    $needed          = 0;
    if ($nextLevel) {
        $currentMin = (float)(getVipLevel($currentLevel)['min_deposit'] ?? 0);
        $nextMin    = (float)$nextLevel['min_deposit'];
        $range      = $nextMin - $currentMin;
        if ($range > 0) {
            $done            = max(0, $totalDeposit - $currentMin);
            $progressPercent = min(100, round($done / $range * 100));
        }
        $needed = max(0, $nextMin - $totalDeposit);
    }

    jsonResponse([
        'success'          => true,
        'current_level'    => $currentLevel,
        'vip_name'         => $vipData['name'] ?? "VIP {$currentLevel}",
        'total_deposit'    => $totalDeposit,
        'progress_percent' => $progressPercent,
        'needed_for_next'  => $needed,
        'needed_fmt'       => formatRupiah($needed),
        'next_level'       => $nextLevel,
        'min_withdraw'     => getMinWithdraw($currentLevel),
        'withdraw_fee'     => getWithdrawFee($currentLevel),
    ]);
}

function apiVipLevels(): never
{
    $levels = getVipLevels();
    jsonResponse(['success' => true, 'levels' => $levels]);
}
