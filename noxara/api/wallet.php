<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!SessionManager::isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = SessionManager::userId();
$action = clean(getVal('action', 'balance'));

match ($action) {
    'balance'  => apiGetBalance($userId),
    'history'  => apiGetHistory($userId),
    default    => jsonResponse(['success' => false, 'message' => 'Aksi tidak valid'], 400),
};

function apiGetBalance(int $userId): never
{
    $wallet = getUserWallet($userId);
    jsonResponse([
        'success'          => true,
        'main_balance'     => (float)$wallet['main_balance'],
        'free_balance'     => (float)$wallet['free_balance'],
        'total_deposit'    => (float)$wallet['total_deposit'],
        'total_withdraw'   => (float)$wallet['total_withdraw'],
        'total_profit'     => (float)$wallet['total_profit'],
        'total_referral'   => (float)$wallet['total_referral'],
        'main_balance_fmt' => formatRupiah((float)$wallet['main_balance']),
        'free_balance_fmt' => formatRupiah((float)$wallet['free_balance']),
    ]);
}

function apiGetHistory(int $userId): never
{
    $limit  = min(50, (int)getVal('limit', 20));
    $offset = max(0, (int)getVal('offset', 0));
    $type   = clean(getVal('type', ''));

    $where  = 'WHERE user_id = ?';
    $params = [$userId];
    $types  = 'i';
    if ($type) {
        $where  .= ' AND transaction_type = ?';
        $params[] = $type;
        $types  .= 's';
    }
    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';

    $rows = db()->fetchAll(
        "SELECT * FROM transaction_ledger {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        $types, $params
    );
    jsonResponse(['success' => true, 'transactions' => $rows]);
}
