<?php

namespace App\Models;

use App\Core\Model;

class Sale extends Model
{
    protected string $table = 'sales';
    protected string $primaryKey = 'id_sale';

    public function countSearch(array $filters): int
    {
        $parts = $this->buildSearchParts($filters);

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) {$parts['from']}",
            $parts['params']
        );
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        $parts = $this->buildSearchParts($filters);

        $sql = "
            SELECT s.id_sale, s.code_sale, s.total_sale, s.payment_method_sale,
                   s.status_sale, s.id_admin_sale, s.date_created_sale, s.quantity_sale,
                   c.name_customer, c.phone_customer,
                   r.title_raffle,
                   a.email_admin
            {$parts['from']}
            ORDER BY s.id_sale DESC
            LIMIT ? OFFSET ?
        ";

        $params = array_merge($parts['params'], [$limit, $offset]);

        return $this->db->fetchAll($sql, $params);
    }

    private function buildSearchParts(array $filters): array
    {
        $params = [];

        $from = "
            FROM sales s
            INNER JOIN customers c ON c.id_customer = s.id_customer_sale
            INNER JOIN raffles r ON r.id_raffle = s.id_raffle_sale
            LEFT JOIN admins a ON a.id_admin = s.id_admin_sale
            WHERE s.status_sale = 1
        ";

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $phoneDigits = preg_replace('/\D/', '', $search);

            $from .= " AND (
                c.name_customer LIKE ?
                OR c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
                OR CAST(s.id_sale AS CHAR) LIKE ?
                OR EXISTS (
                    SELECT 1 FROM tickets t
                    WHERE t.id_sale_ticket = s.id_sale
                      AND t.number_ticket LIKE ?
                )
            )";
            $params[] = "%{$search}%";
            $params[] = '%' . ($phoneDigits !== '' ? $phoneDigits : $search) . '%';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($filters['id_raffle'])) {
            $from .= " AND s.id_raffle_sale = ?";
            $params[] = (int) $filters['id_raffle'];
        }

        if (!empty($filters['id_admin'])) {
            $from .= " AND s.id_admin_sale = ?";
            $params[] = (int) $filters['id_admin'];
        }

        if (!empty($filters['payment_method'])) {
            $from .= " AND s.payment_method_sale = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $from .= " AND s.date_created_sale BETWEEN ? AND ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        return ['from' => $from, 'params' => $params];
    }

    public function findWithDetails(int $idSale): ?object
    {
        return $this->db->fetchOne("
            SELECT s.*, c.name_customer, c.phone_customer, r.title_raffle, a.email_admin
            FROM sales s
            INNER JOIN customers c ON c.id_customer = s.id_customer_sale
            INNER JOIN raffles r ON r.id_raffle = s.id_raffle_sale
            LEFT JOIN admins a ON a.id_admin = s.id_admin_sale
            WHERE s.id_sale = ?
        ", [$idSale]);
    }

    public function findActive(int $idSale): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM sales WHERE id_sale = ? AND status_sale = 1 LIMIT 1",
            [$idSale]
        );
    }

    public function findByCode(string $code): ?object
    {
        return $this->db->fetchOne("
            SELECT s.id_sale, s.code_sale, s.total_sale, s.quantity_sale,
                   s.date_created_sale, s.payment_method_sale,
                   c.name_customer, c.phone_customer, r.title_raffle
            FROM sales s
            INNER JOIN customers c ON c.id_customer = s.id_customer_sale
            INNER JOIN raffles r ON r.id_raffle = s.id_raffle_sale
            WHERE s.code_sale = ?
        ", [$code]);
    }

    public function getDashboardSales(string $from, string $to, ?int $idRaffle = null): array
    {
        $sql = "
            SELECT s.id_sale, s.total_sale, s.quantity_sale, s.date_created_sale,
                   s.payment_method_sale, s.code_sale,
                   c.name_customer, c.phone_customer, r.title_raffle
            FROM sales s
            INNER JOIN customers c ON c.id_customer = s.id_customer_sale
            INNER JOIN raffles r ON r.id_raffle = s.id_raffle_sale
            WHERE s.status_sale = 1
              AND s.date_created_sale BETWEEN ? AND ?
        ";
        $params = [$from . ' 00:00:00', $to . ' 23:59:59'];

        if ($idRaffle) {
            $sql .= " AND s.id_raffle_sale = ?";
            $params[] = $idRaffle;
        }

        $sql .= " ORDER BY s.id_sale DESC LIMIT 10000";
        return $this->db->fetchAll($sql, $params);
    }

    public function deleteByIds(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query("DELETE FROM sales WHERE id_sale IN ({$placeholders})", $ids);
    }
}
