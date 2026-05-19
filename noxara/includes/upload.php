<?php
/**
 * NOXARA - Secure File Upload
 */

function handleUpload(array $file, string $type = 'avatar'): array
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload gagal. Kode error: ' . ($file['error'] ?? 'unknown')];
    }

    $maxSize    = MAX_UPLOAD_SIZE;
    $allowedExt = match($type) {
        'deposit' => ALLOWED_DEPOSIT_TYPES,
        'chat'    => ALLOWED_IMAGE_TYPES,
        default   => ALLOWED_IMAGE_TYPES,
    };

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . ($maxSize / 1024 / 1024) . 'MB.'];
    }

    $originalName = $file['name'];
    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan. Gunakan: ' . implode(', ', $allowedExt)];
    }

    // Validasi MIME
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMime = [
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',  'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
    ];
    if (!isset($allowedMime[$ext]) || $allowedMime[$ext] !== $mimeType) {
        return ['success' => false, 'message' => 'Tipe file tidak valid.'];
    }

    // Jangan izinkan PHP/executable tersembunyi
    $dangerousPatterns = ['php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'sh', 'py'];
    if (in_array($ext, $dangerousPatterns, true)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan.'];
    }

    $subDir = match($type) {
        'deposit' => 'deposits',
        'avatar'  => 'avatars',
        'product' => 'products',
        'banner'  => 'banners',
        'ad'      => 'ads',
        'chat'    => 'chat',
        default   => 'misc',
    };

    $destDir = UPLOADS_PATH . '/' . $subDir . '/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $newFilename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $destPath    = $destDir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file.'];
    }

    return ['success' => true, 'path' => $subDir . '/' . $newFilename, 'filename' => $newFilename];
}

function deleteUploadedFile(string $relativePath): void
{
    $fullPath = UPLOADS_PATH . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
}
