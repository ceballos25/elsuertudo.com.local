<?php

namespace App\Models;

use App\Core\Model;

class Ticket extends Model
{
    protected string $table = 'tickets';
    protected string $primaryKey = 'id_ticket';

    public function getByRaffle(int $idRaffle, ?int $status = null, string $search = ''): array
    {
        $sql = "SELECT id_ticket, number_ticket, status_ticket, id_raffle_ticket FROM tickets WHERE id_raffle_ticket = ?";
        $params = [$idRaffle];

        if ($status !== null) {
            $sql .= " AND status_ticket = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND number_ticket LIKE ?";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY number_ticket ASC LIMIT 10000";
        return $this->db->fetchAll($sql, $params);
    }

    public function getStatus(int $idTicket): ?int
    {
        $row = $this->find($idTicket);
        return $row ? (int) $row->status_ticket : null;
    }

    public function countAvailable(?int $idRaffle = null): int
    {
        return $this->countByStatus(0, $idRaffle);
    }

    public function countByStatus(int $status, ?int $idRaffle = null): int
    {
        $sql = "SELECT COUNT(*) FROM tickets WHERE status_ticket = ?";
        $params = [$status];

        if ($idRaffle) {
            $sql .= " AND id_raffle_ticket = ?";
            $params[] = $idRaffle;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function getBySale(int $idSale): array
    {
        return $this->db->fetchAll(
            "SELECT number_ticket FROM tickets WHERE id_sale_ticket = ? ORDER BY number_ticket ASC",
            [$idSale]
        );
    }

    public function getNumbersGroupedBySale(array $saleIds): array
    {
        $saleIds = array_values(array_unique(array_map('intval', $saleIds)));
        if ($saleIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id_sale_ticket, number_ticket
             FROM tickets
             WHERE id_sale_ticket IN ({$placeholders}) AND status_ticket = 1
             ORDER BY id_sale_ticket ASC, number_ticket ASC",
            $saleIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->id_sale_ticket][] = $row->number_ticket;
        }

        return $grouped;
    }

    public function getSoldBySale(int $idSale): array
    {
        return $this->db->fetchAll(
            "SELECT id_ticket, number_ticket FROM tickets
             WHERE id_sale_ticket = ? AND status_ticket = 1
             ORDER BY number_ticket ASC",
            [$idSale]
        );
    }

    public function countSoldBySale(int $idSale): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM tickets WHERE id_sale_ticket = ? AND status_ticket = 1",
            [$idSale]
        );
    }

    public function releaseFromSale(array $ticketIds): void
    {
        if (empty($ticketIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $this->db->query(
            "UPDATE tickets SET status_ticket = 0, id_customer_ticket = NULL, id_sale_ticket = NULL
             WHERE id_ticket IN ({$placeholders})",
            $ticketIds
        );
    }

    public function releaseAllBySale(int $idSale): void
    {
        $this->db->query(
            "UPDATE tickets SET status_ticket = 0, id_customer_ticket = NULL, id_sale_ticket = NULL
             WHERE id_sale_ticket = ?",
            [$idSale]
        );
    }

    public function updateCustomerBySale(int $idSale, int $idCustomer): void
    {
        $this->db->query(
            "UPDATE tickets SET id_customer_ticket = ? WHERE id_sale_ticket = ? AND status_ticket = 1",
            [$idCustomer, $idSale]
        );
    }

    public function filterIdsInSale(array $ticketIds, int $idSale): array
    {
        if (empty($ticketIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $params = array_merge($ticketIds, [$idSale]);

        $rows = $this->db->fetchAll(
            "SELECT id_ticket FROM tickets
             WHERE id_ticket IN ({$placeholders})
               AND id_sale_ticket = ?
               AND status_ticket = 1",
            $params
        );

        return array_map(fn($r) => (int) $r->id_ticket, $rows);
    }

    public function getSoldReport(array $filters = []): array
    {
        $idRaffle = (int) ($filters['id_raffle'] ?? 0);
        $search = trim((string) ($filters['search'] ?? ''));

        $sql = "
            SELECT t.id_ticket, t.number_ticket, t.id_raffle_ticket,
                   s.id_sale, s.date_created_sale, s.code_sale,
                   s.total_sale, s.quantity_sale, s.payment_method_sale,
                   c.name_customer, c.phone_customer,
                   r.title_raffle, r.price_raffle,
                   a.email_admin
            FROM tickets t
            INNER JOIN sales s ON s.id_sale = t.id_sale_ticket
            INNER JOIN customers c ON c.id_customer = s.id_customer_sale
            INNER JOIN raffles r ON r.id_raffle = t.id_raffle_ticket
            LEFT JOIN admins a ON a.id_admin = s.id_admin_sale
            WHERE t.status_ticket = 1 AND s.status_sale = 1
        ";
        $params = [];

        if ($idRaffle > 0) {
            $sql .= " AND t.id_raffle_ticket = ?";
            $params[] = $idRaffle;
        }

        if (!empty($filters['id_admin'])) {
            $sql .= " AND s.id_admin_sale = ?";
            $params[] = (int) $filters['id_admin'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND s.payment_method_sale = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $sql .= " AND s.date_created_sale BETWEEN ? AND ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($search !== '') {
            $phone = preg_replace('/\D/', '', $search);
            $sql .= " AND (
                t.number_ticket LIKE ?
                OR c.name_customer LIKE ?
                OR c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
                OR CAST(s.id_sale AS CHAR) LIKE ?
            )";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = '%' . ($phone !== '' ? $phone : $search) . '%';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY s.id_sale DESC, t.number_ticket ASC LIMIT 10000";
        return $this->db->fetchAll($sql, $params);
    }

    public function bulkCreate(int $idRaffle, int $digits): void
    {
        $total = (int) pow(10, $digits);
        $batchSize = 100;
        $values = [];
        $params = [];

        for ($i = 0; $i < $total; $i++) {
            $num = str_pad((string) $i, $digits, '0', STR_PAD_LEFT);
            $values[] = "(?, ?, 0)";
            $params[] = $num;
            $params[] = $idRaffle;

            if (count($values) >= $batchSize) {
                $this->insertBatch($values, $params);
                $values = [];
                $params = [];
            }
        }

        if (!empty($values)) {
            $this->insertBatch($values, $params);
        }
    }

    private function insertBatch(array $values, array $params): void
    {
        $sql = "INSERT INTO tickets (number_ticket, id_raffle_ticket, status_ticket) VALUES "
            . implode(', ', $values);
        $this->db->query($sql, $params);
    }

    public function getOccupiedInRaffle(array $ticketIds, int $idRaffle): array
    {
        if (empty($ticketIds)) return [];

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $params = array_merge($ticketIds, [$idRaffle]);

        return $this->db->fetchAll(
            "SELECT id_ticket, number_ticket, status_ticket FROM tickets
             WHERE id_ticket IN ({$placeholders})
               AND id_raffle_ticket = ?
               AND status_ticket != 0",
            $params
        );
    }

    public function countInRaffle(array $ticketIds, int $idRaffle): int
    {
        if (empty($ticketIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $params = array_merge($ticketIds, [$idRaffle]);

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM tickets
             WHERE id_ticket IN ({$placeholders}) AND id_raffle_ticket = ?",
            $params
        );
    }

    public function claimForSale(int $idTicket, int $idRaffle, int $idCustomer, int $idSale): bool
    {
        return $this->db->update(
            'tickets',
            [
                'status_ticket'      => 1,
                'id_customer_ticket' => $idCustomer,
                'id_sale_ticket'     => $idSale,
            ],
            'id_ticket = ? AND id_raffle_ticket = ? AND status_ticket = 0',
            [$idTicket, $idRaffle]
        ) > 0;
    }

    public function claimForReservation(int $idTicket, int $idRaffle): bool
    {
        return $this->db->update(
            'tickets',
            ['status_ticket' => 2],
            'id_ticket = ? AND id_raffle_ticket = ? AND status_ticket = 0',
            [$idTicket, $idRaffle]
        ) > 0;
    }

    public function claimFromReservation(int $idTicket, int $idCustomer, int $idSale): bool
    {
        return $this->db->update(
            'tickets',
            [
                'status_ticket'      => 1,
                'id_customer_ticket' => $idCustomer,
                'id_sale_ticket'     => $idSale,
            ],
            'id_ticket = ? AND status_ticket = 2',
            [$idTicket]
        ) > 0;
    }

    public function getSoldCustomerInfo(int $idTicket): ?object
    {
        return $this->db->fetchOne("
            SELECT c.name_customer, s.date_created_sale
            FROM tickets t
            LEFT JOIN sales s ON s.id_sale = t.id_sale_ticket
            LEFT JOIN customers c ON c.id_customer = COALESCE(s.id_customer_sale, t.id_customer_ticket)
            WHERE t.id_ticket = ?
              AND c.id_customer IS NOT NULL
        ", [$idTicket]);
    }

    public function findParticipationByCustomerInRaffle(int $idCustomer, int $idRaffle): ?object
    {
        return $this->db->fetchOne("
            SELECT t.id_ticket, t.number_ticket, t.status_ticket
            FROM tickets t
            WHERE t.id_raffle_ticket = ?
              AND t.status_ticket IN (1, 2)
              AND (
                  t.id_customer_ticket = ?
                  OR EXISTS (
                      SELECT 1 FROM sales s
                      WHERE s.id_sale = t.id_sale_ticket
                        AND s.id_customer_sale = ?
                        AND s.status_sale = 1
                  )
                  OR EXISTS (
                      SELECT 1 FROM reservation_tickets rt
                      INNER JOIN reservations r ON r.id_reservation = rt.id_reservation_reservation_ticket
                      WHERE rt.id_ticket_reservation_ticket = t.id_ticket
                        AND r.id_customer_reservation = ?
                        AND r.status_reservation = 1
                  )
              )
            ORDER BY t.id_ticket ASC
            LIMIT 1
        ", [$idRaffle, $idCustomer, $idCustomer, $idCustomer]);
    }

    public function resetByRaffle(int $idRaffle): array
    {
        $tickets = $this->db->fetchAll(
            "SELECT id_ticket, id_sale_ticket FROM tickets WHERE id_raffle_ticket = ?",
            [$idRaffle]
        );

        $saleIds = [];
        foreach ($tickets as $t) {
            if (!empty($t->id_sale_ticket)) {
                $saleIds[] = (int) $t->id_sale_ticket;
            }
        }

        $this->db->query(
            "UPDATE tickets SET status_ticket = 0, id_customer_ticket = NULL, id_sale_ticket = NULL WHERE id_raffle_ticket = ?",
            [$idRaffle]
        );

        return array_values(array_unique($saleIds));
    }
}
