<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';
    protected string $primaryKey = 'id_customer';

    public function findByPhone(string $phone): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM customers WHERE phone_customer = ? LIMIT 1",
            [$phone]
        );
    }

    public function search(string $search = '', ?int $status = null): array
    {
        $sql = "SELECT id_customer, name_customer, phone_customer, status_customer FROM customers WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $phone = preg_replace('/\D/', '', $search);
            $sql .= " AND (name_customer LIKE ? OR phone_customer LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = '%' . ($phone !== '' ? $phone : $search) . '%';
        }

        if ($status !== null) {
            $sql .= " AND status_customer = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id_customer DESC";
        return $this->db->fetchAll($sql, $params);
    }
}
