<?php
// src/Database/QueryBuilder.php

namespace Sura\Database;

use mysqli;
use mysqli_stmt;
use mysqli_result;

/**
 * Класс для работы с базой данных
 * 
 */
// $db = $container->get('db.query');

// Выборка
// $users = $db->query("SELECT * FROM users WHERE age > ?", [18]);

// Одна строка
// $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [1]);

// Счёт
// $count = $db->fetchColumn("SELECT COUNT(*) FROM users");

// Вставка
// $db->execute("INSERT INTO users (name, email) VALUES (?, ?)", ['John', 'john@example.com']);
// $lastId = $db->lastInsertId();

// Обновление
// $db->execute("UPDATE users SET name = ? WHERE id = ?", ['Jane', 1]);

// Транзакции
// $db->beginTransaction();
// try {
//     $db->execute("INSERT INTO logs (message) VALUES (?)", ['Started']);
//     $db->execute("UPDATE counters SET value = value + 1");
//     $db->commit();
// } catch (\Exception $e) {
//     $db->rollback();
//     throw $e;
// }
class QueryBuilder
{
    private string $logFile = __DIR__ . '/../../../../storage/logs/sql.log';

    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
        // В конструкторе
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Выполнение SQL-запроса
     */
    public function query(string $sql, array $params = []): array
    {
        $this->logQuery($sql, $params);
        // try {
            if (empty($params)) {
                $result = $this->connection->query($sql);
                if (!$result) {
                    return array();
                }
            } else {
                $stmt = $this->prepare($sql, $params);
                if (!$stmt) {
                    return array();
                }

                $stmt->execute();
                $result = $stmt->get_result(); // Может быть false при ошибке

                // Если get_result() вернул false — возвращаем пустой массив
                if (!$result) {
                    return array();
                }
            }

            // Преобразуем результат в массив
            return $this->fetchResults($result) ?: [];
        // } catch (\Throwable $e) {
        //     return array();
        // }
    }

    /**
     * Выполнение INSERT, UPDATE, DELETE
     */
    public function execute(string $sql, array $params = []): bool
    {
        $this->logQuery($sql, $params);
        // try {
            
            
            if (empty($params)) {
                $result = $this->connection->query($sql);
                return $result !== false;
            }

            $stmt = $this->prepare($sql, $params);
            return $stmt->execute();
        // } catch (\Throwable $e) {
        //     // Можно добавить логирование ошибки, например:
        //     // error_log('Database execute error: ' . $e->getMessage());
        //     return false;
        // }
    }

    /**
     * Получить одну запись
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $this->logQuery($sql, $params);
        $rows = $this->query($sql, $params);
        return !empty($rows) ? $rows[0] : array();
    }

    /**
     * Получить скалярное значение (например, COUNT)
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $this->logQuery($sql, $params);
        $result = $this->fetchOne($sql, $params);
        return $result ? reset($result) : null;
    }

    /**
     * Подготовка запроса с параметрами
     */
    private function prepare(string $sql, array $params): mysqli_stmt
    {
        $this->logQuery($sql, $params);
        $stmt = $this->connection->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        if ($params) {
            $types = str_repeat('s', count($params)); // Простое предположение: все строки
            $stmt->bind_param($types, ...$params);
        }

        return $stmt;
    }

    /**
     * Преобразование результата в массив
     */
    private function fetchResults(?mysqli_result $result): array
    {
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Последний вставленный ID
     */
    public function lastInsertId(): int
    {
        return $this->connection->insert_id;
    }

    /**
     * Начать транзакцию
     */
    public function beginTransaction(): bool
    {
        return $this->connection->autocommit(false);
    }

    /**
     * Зафиксировать транзакцию
     */
    public function commit(): bool
    {
        $result = $this->connection->commit();
        $this->connection->autocommit(true);
        return $result;
    }

    /**
     * Откатить транзакцию
     */
    public function rollback(): bool
    {
        $result = $this->connection->rollback();
        $this->connection->autocommit(true);
        return $result;
    }

    private function logQuery(string $sql, array $params = []): void
    {
        $logFile = $this->logFile;
        $maxFileSize = 2 * 1024 * 1024; // 10 МБ
        $backupCount = 3; // Храним до 3 архивных файлов: .1, .2, .3

        // Проверяем, существует ли файл и не превышает ли он лимит
        if (file_exists($logFile) && filesize($logFile) >= $maxFileSize) {
            // Удаляем самый старый бэкап, если он есть
            $oldBackup = "{$logFile}.{$backupCount}";
            if (file_exists($oldBackup)) {
                @unlink($oldBackup);
            }

            // Сдвигаем существующие архивы: .2 → .3, .1 → .2
            for ($i = $backupCount - 1; $i >= 1; $i--) {
                $from = $logFile . '.' . $i;
                $to = $logFile . '.' . $i + 1;
                if (file_exists($from)) {
                    @rename($from, $to);
                }
            }

            // Переименовываем текущий лог в .1
            @rename($logFile, "{$logFile}.1");
        }

        // Формируем строку запроса с подставленными параметрами
        $query = $sql;
        $params = array_values($params);

        foreach ($params as $param) {
            if ($param === null) {
                $value = 'NULL';
            } elseif (is_int($param) || is_float($param)) {
                $value = $param;
            } elseif (is_bool($param)) {
                $value = $param ? 'TRUE' : 'FALSE';
            } else {
                $value = "'" . addslashes((string)$param) . "'";
            }

            $pos = strpos($query, '?');
            if ($pos !== false) {
                $query = substr_replace($query, $value, $pos, 1);
            } else {
                break; // Защита от лишних замен
            }
        }

        $time = date('Y-m-d H:i:s');
        $line = "[$time] $query\n";

        // Записываем строку в файл
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}