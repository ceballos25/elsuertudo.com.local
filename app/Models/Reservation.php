<?php

namespace App\Models;

use App\Core\Model;

class Reservation extends Model
{
    protected string $table = 'reservations';
    protected string $primaryKey = 'id_reservation';

    public function findByToken(string $token): ?object
    {
        return $this->findBy('token_reservation', $token);
    }

    public function getActive(?int $idRaffle = null): array
    {
        $sql = "
            SELECT r.id_reservation, r.token_reservation, r.expires_at_reservation,
                   r.status_reservation, r.date_created_reservation,
                   c.name_customer, c.phone_customer,
                   rf.title_raffle
            FROM reservations r
            INNER JOIN customers c ON c.id_customer = r.id_customer_reservation
            INNER JOIN raffles rf ON rf.id_raffle = r.id_raffle_reservation
            WHERE r.status_reservation = 1
        ";
        $params = [];

        if ($idRaffle) {
            $sql .= " AND r.id_raffle_reservation = ?";
            $params[] = $idRaffle;
        }

        $sql .= " ORDER BY r.id_reservation DESC";
        return $this->db->fetchAll($sql, $params);
    }

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
            SELECT r.id_reservation, r.token_reservation, r.expires_at_reservation,
                   r.status_reservation, r.date_created_reservation,
                   c.name_customer, c.phone_customer, rf.title_raffle, rf.price_raffle
            {$parts['from']}
            ORDER BY r.id_reservation DESC
            LIMIT ? OFFSET ?
        ";

        $params = array_merge($parts['params'], [$limit, $offset]);

        return $this->db->fetchAll($sql, $params);
    }

    private function buildSearchParts(array $filters): array
    {
        $params = [];

        $from = "
            FROM reservations r
            INNER JOIN customers c ON c.id_customer = r.id_customer_reservation
            INNER JOIN raffles rf ON rf.id_raffle = r.id_raffle_reservation
            WHERE 1=1
        ";

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $phone = preg_replace('/\D/', '', $search);
            $from .= " AND (
                c.name_customer LIKE ?
                OR c.phone_customer LIKE ?
                OR r.token_reservation LIKE ?
                OR EXISTS (
                    SELECT 1 FROM reservation_tickets rt
                    INNER JOIN tickets t ON t.id_ticket = rt.id_ticket_reservation_ticket
                    WHERE rt.id_reservation_reservation_ticket = r.id_reservation
                      AND t.number_ticket LIKE ?
                )
            )";
            $params[] = "%{$search}%";
            $params[] = '%' . ($phone !== '' ? $phone : $search) . '%';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($filters['id_raffle'])) {
            $from .= " AND r.id_raffle_reservation = ?";
            $params[] = (int) $filters['id_raffle'];
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $from .= " AND DATE(r.date_created_reservation) BETWEEN ? AND ?";
            $params[] = $filters['date_from'];
            $params[] = $filters['date_to'];
        }

        $status = $filters['status'] ?? 'pendientes';
        if ($status === 'completadas') {
            $from .= " AND r.status_reservation = 3";
        } elseif ($status === 'todas') {
            $from .= " AND r.status_reservation != 2";
        } else {
            $from .= " AND r.status_reservation = 1";
        }

        return ['from' => $from, 'params' => $params];
    }

    public function findWithCustomer(int $id): ?object
    {
        return $this->db->fetchOne("
            SELECT r.*, c.name_customer, c.phone_customer
            FROM reservations r
            INNER JOIN customers c ON c.id_customer = r.id_customer_reservation
            WHERE r.id_reservation = ?
        ", [$id]);
    }

    public function findByTokenWithCustomer(string $token): ?object
    {
        return $this->db->fetchOne("
            SELECT r.*, c.name_customer, c.phone_customer
            FROM reservations r
            INNER JOIN customers c ON c.id_customer = r.id_customer_reservation
            WHERE r.token_reservation = ?
        ", [$token]);
    }

    public function markCompleted(int $idReservation): bool
    {
        return $this->db->update(
            'reservations',
            ['status_reservation' => 3],
            'id_reservation = ? AND status_reservation = 1',
            [$idReservation]
        ) > 0;
    }
}
