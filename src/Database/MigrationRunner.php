<?php

namespace Sura\Database;

use Sura\Container;
use mysqli;

class MigrationRunner
{
    private QueryBuilder $db;
    private string $migrationsPath;

    public function __construct(string $migrationsPath = __DIR__ . '/../../database/migrations')
    {
        $this->db = Container::getInstance()->get('db.query');
        $this->migrationsPath = $migrationsPath;
    }

    /**
     * Запустить все незапущенные миграции
     */
    public function run(): void
    {
        $this->ensureMigrationsTable();

        $pending = $this->getPendingMigrations();
        if (empty($pending)) {
            echo "No pending migrations.\n";
            return;
        }

        foreach ($pending as $migration) {
            $this->runMigration($migration);
        }
    }

    /**
     * Откатить последнюю миграцию
     */
    public function rollback(): void
    {
        $executed = $this->getExecutedMigrations();
        if (empty($executed)) {
            echo "No migrations to rollback.\n";
            return;
        }

        // Берём последнюю
        $last = array_pop($executed);
        $this->rollbackMigration($last);
    }

    /**
     * Получить список выполненных миграций
     */
    public function getExecutedMigrations(): array
    {
        $rows = $this->db->query("SELECT migration_name FROM migrations ORDER BY executed_at");
        return array_column($rows, 'migration_name');
    }

    /**
     * Получить список ожидающих миграций
     */
    public function getPendingMigrations(): array
    {
        $files = scandir($this->migrationsPath);
        $all = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
            $all[] = $file;
        }

        sort($all);

        $executed = $this->getExecutedMigrations();
        return array_diff($all, $executed);
    }

    /**
     * Убедиться, что таблица migrations существует
     */
    public function ensureMigrationsTable(): void
    {
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Загрузить миграцию по имени файла.
     * Автоматически извлекает имя класса: удаляет timestamp и конвертирует snake_case → PascalCase.
     *
     * @param string $file Например: 20250405120000_create_users_table.php
     * @return \Sura\Database\Migration
     * @throws \RuntimeException Если проверка не пройдена
     */
    public function loadMigration(string $file): \Sura\Database\Migration
    {
        $path = $this->migrationsPath . '/' . $file;

        if (!file_exists($path)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }

        require_once $path;

        // Извлекаем имя класса: удаляем timestamp и преобразуем snake_case → PascalCase
        $className = $this->convertToClassName($file);

        if (!class_exists($className)) {
            throw new \RuntimeException(
                "Migration class '{$className}' not found in '{$file}'.\n" .
                "Expected class name (auto-generated from filename): {$className}\n" .
                "Ensure the class is defined and spelled correctly."
            );
        }

        $reflection = new \ReflectionClass($className);
        if (!$reflection->isSubclassOf(\Sura\Database\Migration::class)) {
            throw new \RuntimeException(
                "Migration class '{$className}' must extend \\Sura\\Database\\Migration."
            );
        }

        return new $className();
    }

    /**
     * Преобразует имя файла миграции в PascalCase имя класса.
     *
     * Пример: 20250405120000_create_users_table.php → CreateUsersTable
     *
     * @param string $filename
     * @return string
     */
    public function convertToClassName(string $filename): string
    {
        // Удаляем расширение .php
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Удаляем timestamp в начале (14 цифр + подчёркивание)
        $name = preg_replace('/^\d{14}_/', '', $name);

        if (!$name) {
            throw new \RuntimeException("Invalid migration filename format: {$filename}. Expected: YYYYMMDDHHMMSS_snake_case_name.php");
        }

        // Преобразуем snake_case → PascalCase
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public function runMigration(string $file): void
    {
        echo "Running migration: $file...\n";
        $migration = $this->loadMigration($file);
        $migration->up();

        $this->db->execute("INSERT INTO migrations (migration_name) VALUES (?)", [$file]);
        echo "✅ Done.\n";
    }

    public function rollbackMigration(string $file): void
    {
        echo "Rolling back: $file...\n";
        $migration = $this->loadMigration($file);
        $migration->down();

        $this->db->execute("DELETE FROM migrations WHERE migration_name = ?", [$file]);
        echo "✅ Rolled back.\n";
    }

}