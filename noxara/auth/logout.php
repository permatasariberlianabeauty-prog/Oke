<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
SessionManager::logoutUser();
SessionManager::flash('success_message', 'Anda berhasil keluar dari akun NOXARA.');
redirect(BASE_URL . '/auth/login.php');
