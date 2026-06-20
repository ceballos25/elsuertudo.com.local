<?php

namespace App\Core;

abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1",
            [$id]
        );
    }

    public function findBy(string $column, $value): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1",
            [$value]
        );
    }

    public function findAll(array $conditions = [], string $orderBy = '', int $limit = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $col => $val) {
                $where[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert($this->table, $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->update($this->table, $data, "{$this->primaryKey} = ?", [$id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]);
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $col => $val) {
                $where[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }
}
