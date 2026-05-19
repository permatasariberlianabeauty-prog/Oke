<?php
/**
 * NOXARA - Bootstrap
 * File ini diinclude di setiap halaman sebagai entry point
 */

// Cegah akses langsung jika belum define ROOT
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// ============================================================
// 1. Load Config & Constants
// ============================================================
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/constants.php';

// ============================================================
// 2. Set Timezone
// ============================================================
date_default_timezone_set(APP_TIMEZONE);

// ============================================================
// 3. Load Database
// ============================================================
require_once ROOT_PATH . '/config/database.php';

// ============================================================
// 4. Load Session Manager
// ============================================================
require_once ROOT_PATH . '/config/session.php';
SessionManager::start();

// ============================================================
// 5. Load CSRF
// ============================================================
require_once ROOT_PATH . '/config/csrf.php';

// ============================================================
// 6. Load Core Functions
// ============================================================
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/ledger.php';

// ============================================================
// 7. Check Install Lock
// ============================================================
if (!defined('SKIP_INSTALL_CHECK')) {
    if (!file_exists(ROOT_PATH . '/config/config_active.php') && !isInstalled()) {
        // Arahkan ke install wizard jika belum diinstall
        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($currentUri, '/install') === false) {
            redirect(BASE_URL . '/install/');
        }
    }
}

// ============================================================
// 8. Check Maintenance Mode
// ============================================================
if (!defined('SKIP_MAINTENANCE_CHECK') && !defined('IS_ADMIN')) {
    $maintenanceMode = getSetting('maintenance_mode', '0');
    if ($maintenanceMode === '1') {
        require_once ROOT_PATH . '/errors/maintenance.php';
        exit;
    }
}

/**
 * Cek apakah sudah terinstall
 */
function isInstalled(): bool
{
    return file_exists(ROOT_PATH . '/install/.installed');
}
