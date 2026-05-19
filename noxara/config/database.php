<?php
/**
 * NOXARA - Database Singleton (MySQLi)
 * Semua query wajib menggunakan prepared statement
 */

class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct()
    {
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->connection = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        if ($this->connection->connect_error) {
            $this->handleConnectionError($this->connection->connect_error);
        }

        $this->connection->set_charset(DB_CHARSET);
        $this->connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    }

    private function handleConnectionError(string $error): void
    {
        if (APP_ENV === 'development') {
            die('Koneksi database gagal: ' . htmlspecialchars($error));
        }
        error_log('[NOXARA] Database connection error: ' . $error);
        die('Layanan sedang tidak tersedia. Silakan coba lagi nanti.');
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        // Reconnect jika koneksi terputus
        if (!$this->connection->ping()) {
            $this->connection->close();
            self::$instance = null;
            self::$instance = new self();
        }
        return $this->connection;
    }

    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }

    public function lastInsertId(): int
    {
        return (int)$this->connection->insert_id;
    }

    public function affectedRows(): int
    {
        return (int)$this->connection->affected_rows;
    }

    public function beginTransaction(): void
    {
        $this->connection->begin_transaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * Jalankan prepared statement dan kembalikan result
     * @return mysqli_result|bool
     */
    public function query(string $sql, string $types = '', array $params = []): mysqli_result|bool
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            $this->logQueryError($sql, $this->connection->error);
            return false;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result !== false ? $result : true;
    }

    /**
     * Fetch single row
     */
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        $result = $this->query($sql, $types, $params);
        if ($result instanceof mysqli_result) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row ?: null;
        }
        return null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $result = $this->query($sql, $types, $params);
        if ($result instanceof mysqli_result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $rows;
        }
        return [];
    }

    /**
     * Execute INSERT/UPDATE/DELETE
     */
    public function execute(string $sql, string $types = '', array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            $this->logQueryError($sql, $this->connection->error);
            return false;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    private function logQueryError(string $sql, string $error): void
    {
        error_log('[NOXARA] Query error: ' . $error . ' | SQL: ' . substr($sql, 0, 200));
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup(): never
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}

/**
 * Helper global: ambil instance DB
 */
function db(): Database
{
    return Database::getInstance();
}
