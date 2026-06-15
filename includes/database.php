<?php
/**
 * Database Connection — PDO Singleton
 * Smart Inventory & Billing Management System
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    /** Run a query with optional bound params and return PDOStatement */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row */
    public function fetchOne(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    /** Fetch all rows */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Insert and return last insert ID */
    public function insert(string $sql, array $params = []): int|string {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /** Execute update/delete, return affected rows */
    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    /** Get count of a query */
    public function count(string $sql, array $params = []): int {
        $row = $this->fetchOne($sql, $params);
        return $row ? (int) reset($row) : 0;
    }

    /** Begin transaction */
    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    /** Commit transaction */
    public function commit(): void {
        $this->pdo->commit();
    }

    /** Rollback transaction */
    public function rollback(): void {
        $this->pdo->rollBack();
    }
}

// Convenience global helper
function db(): Database {
    return Database::getInstance();
}
