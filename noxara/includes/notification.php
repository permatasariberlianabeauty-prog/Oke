<?php
/**
 * NOXARA - Notification System
 */

function createNotification(int $userId, string $type, string $title, string $message, string $actionUrl = '', string $icon = 'bell'): int
{
    db()->execute(
        'INSERT INTO notifications (user_id, type, title, message, action_url, icon) VALUES (?,?,?,?,?,?)',
        'isssss', [$userId, $type, $title, $message, $actionUrl, $icon]
    );
    return db()->lastInsertId();
}

function broadcastNotification(string $type, string $title, string $message, string $actionUrl = ''): bool
{
    return db()->execute(
        'INSERT INTO notifications (user_id, type, title, message, action_url, is_broadcast) VALUES (NULL,?,?,?,?,1)',
        'ssss', [$type, $title, $message, $actionUrl]
    );
}

function getUnreadCount(int $userId): int
{
    $r = db()->fetchOne(
        'SELECT COUNT(*) as cnt FROM notifications WHERE (user_id = ? OR is_broadcast = 1) AND is_read = 0',
        'i', [$userId]
    );
    return (int)($r['cnt'] ?? 0);
}

function getNotifications(int $userId, int $limit = 20, int $offset = 0): array
{
    return db()->fetchAll(
        'SELECT * FROM notifications WHERE (user_id = ? OR is_broadcast = 1) ORDER BY created_at DESC LIMIT ? OFFSET ?',
        'iii', [$userId, $limit, $offset]
    );
}

function markAllRead(int $userId): void
{
    db()->execute(
        'UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR is_broadcast = 1) AND is_read = 0',
        'i', [$userId]
    );
}

function markRead(int $notifId, int $userId): void
{
    db()->execute(
        'UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR is_broadcast = 1)',
        'ii', [$notifId, $userId]
    );
}
