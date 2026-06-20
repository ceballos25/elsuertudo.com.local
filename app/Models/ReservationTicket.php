<?php

namespace App\Models;

use App\Core\Model;

class ReservationTicket extends Model
{
    protected string $table = 'reservation_tickets';
    protected string $primaryKey = 'id_reservation_ticket';

    public function getTicketIds(int $idReservation): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id_ticket_reservation_ticket FROM reservation_tickets WHERE id_reservation_reservation_ticket = ?",
            [$idReservation]
        );
        return array_map(fn($r) => (int) $r->id_ticket_reservation_ticket, $rows);
    }

    public function getTicketNumbers(int $idReservation): array
    {
        return $this->db->fetchAll("
            SELECT t.number_ticket
            FROM reservation_tickets rt
            INNER JOIN tickets t ON t.id_ticket = rt.id_ticket_reservation_ticket
            WHERE rt.id_reservation_reservation_ticket = ?
            ORDER BY t.number_ticket ASC
        ", [$idReservation]);
    }

    public function getTicketNumbersGrouped(array $reservationIds): array
    {
        $reservationIds = array_values(array_unique(array_map('intval', $reservationIds)));
        if ($reservationIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT rt.id_reservation_reservation_ticket, t.number_ticket
             FROM reservation_tickets rt
             INNER JOIN tickets t ON t.id_ticket = rt.id_ticket_reservation_ticket
             WHERE rt.id_reservation_reservation_ticket IN ({$placeholders})
             ORDER BY rt.id_reservation_reservation_ticket ASC, t.number_ticket ASC",
            $reservationIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->id_reservation_reservation_ticket][] = $row->number_ticket;
        }

        return $grouped;
    }

    public function deleteByReservation(int $idReservation): void
    {
        $this->db->delete(
            'reservation_tickets',
            'id_reservation_reservation_ticket = ?',
            [$idReservation]
        );
    }

    public function deleteByTicket(int $idTicket): void
    {
        $this->db->delete(
            'reservation_tickets',
            'id_ticket_reservation_ticket = ?',
            [$idTicket]
        );
    }

    public function countByReservation(int $idReservation): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservation_tickets WHERE id_reservation_reservation_ticket = ?",
            [$idReservation]
        );
    }

    public function findLatestByTicket(int $idTicket): ?object
    {
        return $this->db->fetchOne("
            SELECT rt.*, r.status_reservation, c.name_customer, r.date_created_reservation
            FROM reservation_tickets rt
            INNER JOIN reservations r ON r.id_reservation = rt.id_reservation_reservation_ticket
            INNER JOIN customers c ON c.id_customer = r.id_customer_reservation
            WHERE rt.id_ticket_reservation_ticket = ?
            ORDER BY rt.date_created_reservation_ticket DESC
            LIMIT 1
        ", [$idTicket]);
    }
}
