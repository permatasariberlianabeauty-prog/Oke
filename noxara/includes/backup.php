<?php
/**
 * NOXARA - Database Backup
 */

function runBackup(string $type = 'manual', ?int $adminId = null): array
{
    $backupDir = BACKUPS_PATH;
    if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);

    $filename  = 'noxara_backup_' . date('Ymd_His') . '_' . $type . '.sql';
    $filepath  = $backupDir . '/' . $filename;

    $cmd = sprintf(
        'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --no-tablespaces --skip-lock-tables %s > %s 2>&1',
        escapeshellarg(DB_HOST),
        DB_PORT,
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );

    exec($cmd, $output, $returnCode);

    if ($returnCode !== 0 || !file_exists($filepath)) {
        db()->execute(
            'INSERT INTO backup_logs (filename, filesize, type, status, admin_id, message) VALUES (?,?,?,?,?,?)',
            'sisiis', [$filename, 0, $type, 'failed', $adminId, implode(' ', $output)]
        );
        return ['success' => false, 'message' => 'Backup gagal: ' . implode(' ', $output)];
    }

    $filesize = filesize($filepath);
    db()->execute(
        'INSERT INTO backup_logs (filename, filesize, type, status, admin_id) VALUES (?,?,?,?,?)',
        'sissi', [$filename, $filesize, $type, 'success', $adminId]
    );

    // Hapus backup lama
    $retentionDays = (int)getSetting('backup_retention_days', '30');
    cleanOldBackups($retentionDays);

    return ['success' => true, 'filename' => $filename, 'filesize' => $filesize];
}

function cleanOldBackups(int $days): void
{
    $cutoff = time() - ($days * 86400);
    $files  = glob(BACKUPS_PATH . '/*.sql');
    if (!$files) return;
    foreach ($files as $f) {
        if (filemtime($f) < $cutoff) @unlink($f);
    }
}

function getBackupList(): array
{
    return db()->fetchAll('SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 50');
}

function downloadBackup(string $filename, int $adminId): void
{
    $filepath = BACKUPS_PATH . '/' . basename($filename);
    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File backup tidak ditemukan.');
    }
    logActivity('download_backup', 'backup', 0, 'Download: ' . $filename);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}
