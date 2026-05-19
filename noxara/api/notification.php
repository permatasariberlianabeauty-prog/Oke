<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/notification.php';

header('Content-Type: application/json; charset=utf-8');

if (!SessionManager::isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = SessionManager::userId();
$action = clean(getVal('action', postVal('action', 'list')));

match ($action) {
    'list'      => apiListNotifications($userId),
    'unread'    => apiUnreadCount($userId),
    'mark_read' => apiMarkRead($userId),
    'mark_all'  => apiMarkAll($userId),
    default     => jsonResponse(['success' => false, 'message' => 'Aksi tidak valid'], 400),
};

function apiListNotifications(int $userId): never
{
    $limit  = min(50, (int)getVal('limit', 20));
    $offset = max(0, (int)getVal('offset', 0));
    $notifs = getNotifications($userId, $limit, $offset);
    $unread = getUnreadCount($userId);
    jsonResponse(['success' => true, 'notifications' => $notifs, 'unread_count' => $unread]);
}

function apiUnreadCount(int $userId): never
{
    jsonResponse(['success' => true, 'count' => getUnreadCount($userId)]);
}

function apiMarkRead(int $userId): never
{
    CSRF::verifyOrFail();
    $notifId = (int)postVal('notif_id', 0);
    if ($notifId > 0) markRead($notifId, $userId);
    jsonResponse(['success' => true, 'unread_count' => getUnreadCount($userId)]);
}

function apiMarkAll(int $userId): never
{
    CSRF::verifyOrFail();
    markAllRead($userId);
    jsonResponse(['success' => true, 'message' => 'Semua notifikasi ditandai dibaca.']);
}
