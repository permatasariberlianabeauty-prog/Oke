<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mining.php';
require_once INCLUDES_PATH . '/missions.php';

header('Content-Type: application/json; charset=utf-8');

if (!SessionManager::isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = SessionManager::userId();
$action = clean(getVal('action', postVal('action', '')));

match ($action) {
    'claim' => apiClaimProfit($userId),
    'status' => apiMiningStatus($userId),
    'packages' => apiGetPackages($userId),
    default => jsonResponse(['success' => false, 'message' => 'Aksi tidak valid'], 400),
};

function apiClaimProfit(int $userId): never
{
    CSRF::verifyOrFail();
    $packageId = (int)postVal('package_id', 0);
    if ($packageId <= 0) jsonResponse(['success' => false, 'message' => 'ID paket tidak valid'], 400);

    $result = claimDailyProfit($userId, $packageId);
    if ($result['success']) {
        $wallet = getUserWallet($userId);
        jsonResponse([
            'success'      => true,
            'message'      => 'Profit berhasil diklaim!',
            'profit'       => $result['profit'],
            'profit_fmt'   => formatRupiah($result['profit']),
            'main_balance' => (float)$wallet['main_balance'],
            'main_balance_fmt' => formatRupiah((float)$wallet['main_balance']),
        ]);
    }
    jsonResponse(['success' => false, 'message' => $result['message']], 422);
}

function apiMiningStatus(int $userId): never
{
    $packages  = getUserActivePackages($userId);
    $today     = date('Y-m-d');
    $claimable = getClaimablePackages($userId);
    $todayProfit = getTodayProfit($userId);

    $nextMidnight  = strtotime('tomorrow midnight');
    $remainingSecs = max(0, $nextMidnight - time());

    $data = [];
    foreach ($packages as $p) {
        $alreadyClaimed = ($p['last_claim_date'] === $today);
        $data[] = [
            'id'              => (int)$p['id'],
            'product_name'    => $p['product_name'],
            'profit_per_day'  => (float)$p['profit_per_day'],
            'days_claimed'    => (int)$p['days_claimed'],
            'duration_days'   => (int)$p['duration_days'],
            'already_claimed' => $alreadyClaimed,
            'expired_at'      => $p['expired_at'],
        ];
    }

    jsonResponse([
        'success'          => true,
        'packages'         => $data,
        'claimable_count'  => count($claimable),
        'today_profit'     => $todayProfit,
        'today_profit_fmt' => formatRupiah($todayProfit),
        'countdown_secs'   => $remainingSecs,
    ]);
}

function apiGetPackages(int $userId): never
{
    $packages = getUserActivePackages($userId);
    jsonResponse(['success' => true, 'packages' => $packages]);
}
