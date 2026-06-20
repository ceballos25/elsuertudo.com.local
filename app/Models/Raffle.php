<?php

namespace App\Models;

use App\Core\Model;

class Raffle extends Model
{
    protected string $table = 'raffles';
    protected string $primaryKey = 'id_raffle';

    public function search(string $search = '', ?int $status = null): array
    {
        $sql = "SELECT * FROM raffles WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (title_raffle LIKE ? OR description_raffle LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($status !== null) {
            $sql .= " AND status_raffle = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id_raffle DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getActive(): array
    {
        return $this->search('', 1);
    }

    public function listTitles(): array
    {
        return $this->db->fetchAll(
            "SELECT id_raffle, title_raffle FROM raffles ORDER BY id_raffle DESC"
        );
    }
}
