<?php
/**
 * NOXARA - Konstanta Aplikasi
 */

// ============================================================
// WALLET TYPES
// ============================================================
define('WALLET_MAIN', 'main');
define('WALLET_FREE', 'free');

// ============================================================
// TRANSACTION TYPES
// ============================================================
define('TX_DEPOSIT',          'deposit');
define('TX_WITHDRAW',         'withdraw');
define('TX_PURCHASE',         'purchase');
define('TX_PROFIT',           'profit');
define('TX_REFERRAL',         'referral_commission');
define('TX_BONUS_REGISTER',   'bonus_register');
define('TX_BONUS_DAILY',      'bonus_daily_reward');
define('TX_BONUS_MISSION',    'bonus_mission');
define('TX_BONUS_AD',         'bonus_ad');
define('TX_VOUCHER',          'voucher_discount');
define('TX_MODAL_RETURN',     'modal_return');
define('TX_ADMIN_ADJUST',     'admin_adjustment');
define('TX_WITHDRAW_FEE',     'withdraw_fee');

// ============================================================
// TRANSACTION DIRECTIONS
// ============================================================
define('DIR_CREDIT', 'credit');
define('DIR_DEBIT',  'debit');

// ============================================================
// STATUS
// ============================================================
define('STATUS_PENDING',    'pending');
define('STATUS_CONFIRMED',  'confirmed');
define('STATUS_REJECTED',   'rejected');
define('STATUS_EXPIRED',    'expired');
define('STATUS_APPROVED',   'approved');
define('STATUS_PROCESSING', 'processing');
define('STATUS_ACTIVE',     'active');
define('STATUS_COMPLETED',  'completed');

// ============================================================
// VIP LEVELS
// ============================================================
define('VIP_MAX_LEVEL', 5);
define('VIP_MIN_LEVEL', 0);

// ============================================================
// ROLES
// ============================================================
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_CS',         'cs');
define('ROLE_FINANCE',    'finance');

// ============================================================
// MISSION ACTION TYPES
// ============================================================
define('MISSION_LOGIN',          'login');
define('MISSION_CLAIM_PROFIT',   'claim_profit');
define('MISSION_WATCH_ADS',      'watch_ads');
define('MISSION_LOGIN_STREAK',   'login_streak');
define('MISSION_CLAIM_STREAK',   'claim_streak');
define('MISSION_FIRST_DEPOSIT',  'first_deposit');
define('MISSION_FIRST_PURCHASE', 'first_purchase');
define('MISSION_REFERRAL',       'referral');
define('MISSION_VIP_LEVEL',      'vip_level');

// ============================================================
// POPUP KEYS
// ============================================================
define('POPUP_LOGIN_SUCCESS',       'login_success');
define('POPUP_LOGIN_FAILED',        'login_failed');
define('POPUP_LOGOUT',              'logout_success');
define('POPUP_REGISTER_SUCCESS',    'register_success');
define('POPUP_REGISTER_FAILED',     'register_failed');
define('POPUP_DEPOSIT_SENT',        'deposit_sent');
define('POPUP_DEPOSIT_FAILED',      'deposit_failed');
define('POPUP_DEPOSIT_APPROVED',    'deposit_approved');
define('POPUP_DEPOSIT_REJECTED',    'deposit_rejected');
define('POPUP_WITHDRAW_SUCCESS',    'withdraw_success');
define('POPUP_WITHDRAW_FAILED',     'withdraw_failed');
define('POPUP_WITHDRAW_APPROVED',   'withdraw_approved');
define('POPUP_WITHDRAW_REJECTED',   'withdraw_rejected');
define('POPUP_PURCHASE_SUCCESS',    'purchase_success');
define('POPUP_PURCHASE_FAILED',     'purchase_failed');
define('POPUP_PROFIT_CLAIMED',      'profit_claimed');
define('POPUP_PROFIT_FAILED',       'profit_failed');
define('POPUP_PIN_CREATED',         'pin_created');
define('POPUP_PIN_CHANGED',         'pin_changed');
define('POPUP_PIN_WRONG',           'pin_wrong');
define('POPUP_BANK_ADDED',          'bank_added');
define('POPUP_BANK_FAILED',         'bank_failed');
define('POPUP_PROFILE_UPDATED',     'profile_updated');
define('POPUP_PASSWORD_CHANGED',    'password_changed');
define('POPUP_DAILY_REWARD',        'daily_reward_claimed');
define('POPUP_MISSION_CLAIMED',     'mission_claimed');
define('POPUP_VOUCHER_APPLIED',     'voucher_applied');
define('POPUP_VOUCHER_FAILED',      'voucher_failed');
define('POPUP_SALDO_INSUFFICIENT',  'saldo_insufficient');
define('POPUP_WITHDRAW_CLOSED',     'withdraw_hour_closed');
define('POPUP_ADMIN_SUCCESS',       'admin_action_success');
define('POPUP_ADMIN_FAILED',        'admin_action_failed');
define('POPUP_BACKUP_SUCCESS',      'backup_success');
define('POPUP_BACKUP_FAILED',       'backup_failed');

// ============================================================
// PAGINATION
// ============================================================
define('PER_PAGE', 20);
define('ADMIN_PER_PAGE', 25);

// ============================================================
// MISC
// ============================================================
define('MASK_CHAR', '*');
define('INSTALL_LOCK_FILE', ROOT_PATH . '/install/.installed');
