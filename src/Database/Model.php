<?php

namespace Sura\Database;

use Sura\Container;
use InvalidArgumentException;

abstract class Model
{
    // Имя таблицы (по умолчанию — имя класса во множественном числе)
    protected string $table;

    // Первичный ключ
    protected string $primaryKey = 'id';

    // Поля, которые можно массово присваивать
    protected array $fillable = [];

    // Автоматические временные метки
    protected bool $timestamps = true;

    // Название полей created_at и updated_at
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    // Подключение к БД
    protected QueryBuilder $db;

    public function __construct()
    {
        $this->db = Container::getInstance()->get('db.query');
        if (empty($this->table)) {
            $className = get_called_class();
            $this->table = strtolower($className) . 's'; // User → users, Product → products
        }
    }

    /**
     * Получить все записи
     */
    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->table}");
    }

    /**
     * Найти запись по ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Найти запись по произвольному полю
     */
    public function findBy(string $column, mixed $value): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );
    }

    /**
     * Создать новую запись
     */
    public function create(array $data): int
    {
        $filtered = $this->filterFillable($data);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $filtered[$this->createdAtColumn] = $now;
            $filtered[$this->updatedAtColumn] = $now;
        }

        $columns = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
        $values = array_values($filtered);

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->db->execute($sql, $values);

        return $this->db->lastInsertId();
    }

    /**
     * Обновить запись по ID
     */
    public function update(int $id, array $data): bool
    {
        $filtered = $this->filterFillable($data);

        if ($this->timestamps) {
            $filtered[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        if (empty($filtered)) {
            return false;
        }

        $setParts = [];
        $values = [];
        foreach ($filtered as $column => $value) {
            $setParts[] = "{$column} = ?";
            $values[] = $value;
        }
        $setClause = implode(', ', $setParts);
        $values[] = $id; // для WHERE

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, $values);
    }

    /**
     * Удалить запись по ID
     */
    public function delete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Отфильтровать только разрешённые поля
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            throw new InvalidArgumentException("Property \$fillable must be defined in " . get_called_class());
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Получить имя таблицы
     */
    public function getTable(): string
    {
        return $this->table;
    }
}