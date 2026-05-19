<?php
/**
 * NOXARA - Auth Functions
 */

function loginUser(string $username, string $password): array
{
    $ip = getClientIp();

    // Cek rate limit
    $lockCheck = db()->fetchOne(
        'SELECT COUNT(*) as cnt FROM user_login_logs WHERE ip_address = ? AND status = "failed" AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
        'si', [$ip, LOGIN_LOCK_DURATION]
    );
    if ((int)($lockCheck['cnt'] ?? 0) >= MAX_LOGIN_ATTEMPTS) {
        return ['success' => false, 'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 30 menit.'];
    }

    $user = db()->fetchOne(
        'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1',
        'ss', [$username, $username]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        db()->execute(
            'INSERT INTO user_login_logs (username_attempt, ip_address, user_agent, status) VALUES (?,?,?,?)',
            'ssss', [$username, $ip, getUserAgent(), 'failed']
        );
        return ['success' => false, 'message' => 'Username atau password salah.'];
    }

    if ($user['is_blocked']) {
        db()->execute(
            'INSERT INTO user_login_logs (user_id, ip_address, user_agent, status) VALUES (?,?,?,?)',
            'isss', [$user['id'], $ip, getUserAgent(), 'blocked']
        );
        return ['success' => false, 'message' => 'Akun Anda diblokir. ' . ($user['block_reason'] ?? '')];
    }

    db()->execute(
        'INSERT INTO user_login_logs (user_id, username_attempt, ip_address, user_agent, status) VALUES (?,?,?,?,?)',
        'issss', [$user['id'], $username, $ip, getUserAgent(), 'success']
    );

    SessionManager::loginUser($user);
    updateMissionProgress($user['id'], MISSION_LOGIN, 1);

    return ['success' => true, 'user' => $user];
}

function registerUser(array $data): array
{
    $username  = sanitizeUsername($data['username'] ?? '');
    $email     = trim($data['email'] ?? '');
    $phone     = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
    $fullName  = clean($data['full_name'] ?? '');
    $password  = $data['password'] ?? '';
    $refCode   = strtoupper(trim($data['referral_code'] ?? ''));

    // Validasi
    if (strlen($username) < 4 || strlen($username) > 20) return ['success' => false, 'message' => 'Username harus 4-20 karakter.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Email tidak valid.'];
    if (strlen($phone) < 9 || strlen($phone) > 15) return ['success' => false, 'message' => 'Nomor HP tidak valid.'];
    if (strlen($password) < 6) return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    if (empty($fullName)) return ['success' => false, 'message' => 'Nama lengkap wajib diisi.'];

    // Cek duplikat
    if (db()->fetchOne('SELECT id FROM users WHERE username = ? LIMIT 1', 's', [$username])) {
        return ['success' => false, 'message' => 'Username sudah digunakan.'];
    }
    if (db()->fetchOne('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email])) {
        return ['success' => false, 'message' => 'Email sudah terdaftar.'];
    }

    $referredBy = null;
    if (!empty($refCode)) {
        $referrer = db()->fetchOne('SELECT id FROM users WHERE referral_code = ? LIMIT 1', 's', [$refCode]);
        if ($referrer) $referredBy = (int)$referrer['id'];
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
    $referralCode   = generateReferralCode();

    db()->beginTransaction();
    try {
        db()->execute(
            'INSERT INTO users (username, email, phone, full_name, password, referral_code, referred_by) VALUES (?,?,?,?,?,?,?)',
            'ssssssi', [$username, $email, $phone, $fullName, $hashedPassword, $referralCode, $referredBy]
        );
        $userId = db()->lastInsertId();

        // Buat wallet
        db()->execute('INSERT INTO user_wallets (user_id) VALUES (?)', 'i', [$userId]);

        // Saldo gratis pendaftaran
        $freeEnabled = getSetting('free_balance_register_enabled', '1');
        $freeAmount  = (float)getSetting('free_balance_register', '10000');
        if ($freeEnabled === '1' && $freeAmount > 0) {
            creditBalance($userId, WALLET_FREE, $freeAmount, TX_BONUS_REGISTER, 'register', $userId, 'Bonus saldo gratis pendaftaran');
        }

        // Referral chain
        if ($referredBy) {
            processReferralChain($userId, $referredBy);
        }

        // Mission: first register
        updateMissionProgress($userId, MISSION_FIRST_DEPOSIT, 0);

        db()->commit();
        return ['success' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        db()->rollback();
        writeLog('register_errors.log', $e->getMessage());
        return ['success' => false, 'message' => 'Pendaftaran gagal. Silakan coba lagi.'];
    }
}

function changePassword(int $userId, string $oldPassword, string $newPassword): array
{
    $user = db()->fetchOne('SELECT password FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if (!$user) return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
    if (!password_verify($oldPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Password lama tidak sesuai.'];
    }
    if (strlen($newPassword) < 6) return ['success' => false, 'message' => 'Password baru minimal 6 karakter.'];
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
    db()->execute('UPDATE users SET password = ? WHERE id = ?', 'si', [$hash, $userId]);
    return ['success' => true];
}

function setPin(int $userId, string $pin, string $confirmPin, ?string $oldPin = null): array
{
    if (!preg_match('/^\d{6}$/', $pin)) return ['success' => false, 'message' => 'PIN harus 6 digit angka.'];
    if ($pin !== $confirmPin) return ['success' => false, 'message' => 'Konfirmasi PIN tidak cocok.'];
    $user = db()->fetchOne('SELECT pin FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if (!$user) return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
    if (!empty($user['pin'])) {
        if (empty($oldPin)) return ['success' => false, 'message' => 'PIN lama wajib diisi untuk mengganti PIN.'];
        if (!password_verify($oldPin, $user['pin'])) return ['success' => false, 'message' => 'PIN lama salah.'];
    }
    $hashed = password_hash($pin, PASSWORD_BCRYPT, ['cost' => PIN_HASH_COST]);
    db()->execute('UPDATE users SET pin = ? WHERE id = ?', 'si', [$hashed, $userId]);
    return ['success' => true];
}

function verifyPin(int $userId, string $pin): bool
{
    if (!preg_match('/^\d{6}$/', $pin)) return false;
    $user = db()->fetchOne('SELECT pin FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    return $user && !empty($user['pin']) && password_verify($pin, $user['pin']);
}

function createPasswordReset(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    db()->execute('DELETE FROM password_resets WHERE user_id = ?', 'i', [$userId]);
    db()->execute(
        'INSERT INTO password_resets (user_id, token, ip_address, expired_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL ? SECOND))',
        'issi', [$userId, $token, getClientIp(), RESET_TOKEN_LIFETIME]
    );
    return $token;
}

function resetPasswordByToken(string $token, string $newPassword): array
{
    $reset = db()->fetchOne(
        'SELECT * FROM password_resets WHERE token = ? AND is_used = 0 AND expired_at > NOW() LIMIT 1',
        's', [$token]
    );
    if (!$reset) return ['success' => false, 'message' => 'Link reset tidak valid atau sudah kadaluarsa.'];
    if (strlen($newPassword) < 6) return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
    db()->execute('UPDATE users SET password = ? WHERE id = ?', 'si', [$hash, $reset['user_id']]);
    db()->execute('UPDATE password_resets SET is_used = 1 WHERE id = ?', 'i', [$reset['id']]);
    return ['success' => true];
}
