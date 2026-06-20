<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';
    protected string $primaryKey = 'id_setting';

    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM settings ORDER BY key_setting ASC"
        );
    }

    public function asKeyValue(): array
    {
        $rows = $this->getAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->key_setting] = $row->value_setting;
        }
        return $map;
    }

    public function findByKey(string $key): ?object
    {
        return $this->findBy('key_setting', $key);
    }
}
