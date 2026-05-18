<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo !== null) return self::$pdo;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) throw $e;
            http_response_code(503);
            die(json_encode(['error' => 'Database unavailable']));
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function find(string $sql, array $params = []): ?array {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function findAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): string {
        $cols   = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($places)", array_values($data));
        return self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set  = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $stmt = self::query(
            "UPDATE `$table` SET $set WHERE $where",
            array_merge(array_values($data), $whereParams)
        );
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int {
        return self::query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    public static function count(string $sql, array $params = []): int {
        return (int) self::query($sql, $params)->fetchColumn();
    }

    public static function beginTransaction(): void { self::connect()->beginTransaction(); }
    public static function commit(): void           { self::connect()->commit(); }
    public static function rollback(): void         { self::connect()->rollBack(); }
}
