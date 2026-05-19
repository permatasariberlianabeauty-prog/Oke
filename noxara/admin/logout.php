<?php
/**
 * NOXARA Admin - Logout
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';

if (SessionManager::isAdminLoggedIn()) {
    $adminId = SessionManager::adminId();
    db()->execute(
        'INSERT INTO admin_security_logs (admin_id, event_type, ip_address, user_agent, details) VALUES (?,?,?,?,?)',
        'issss', [$adminId, 'logout', getClientIp(), getUserAgent(), 'Admin logout']
    );
    SessionManager::logoutAdmin();
}

header('Location: ' . BASE_URL . '/admin/login.php');
exit;
