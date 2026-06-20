<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Ticket;

class TicketService
{
    private Ticket $model;
    private ReservationTicket $reservationTicketModel;

    public function __construct()
    {
        $this->model = new Ticket();
        $this->reservationTicketModel = new ReservationTicket();
    }

    public function inventory(array $filters): array
    {
        $idRaffle = (int) ($filters['id_raffle'] ?? 0);
        if ($idRaffle <= 0) {
            return ['success' => true, 'data' => []];
        }

        $status = isset($filters['status']) && $filters['status'] !== ''
            ? (int) $filters['status']
            : null;
        $search = trim($filters['search'] ?? '');

        $data = $this->model->getByRaffle($idRaffle, $status, $search);

        foreach ($data as &$t) {
            $t->name_customer = $this->resolveCustomerName((int) $t->status_ticket, (int) $t->id_ticket);
        }
        unset($t);

        return ['success' => true, 'data' => $data];
    }

    public function changeStatus(int $idTicket, int $newStatus): array
    {
        $status = $this->model->getStatus($idTicket);
        if ($status === null) {
            return ['success' => false, 'message' => 'Ticket no encontrado'];
        }

        if (!in_array($newStatus, [0, 2], true)) {
            return ['success' => false, 'message' => 'Estado no permitido'];
        }

        if ($status === 1) {
            return ['success' => false, 'message' => 'No se puede modificar un número vendido.'];
        }

        if ($newStatus === $status) {
            return ['success' => true];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            if ($status === 2 && $newStatus === 0) {
                $this->releaseReservationLink($idTicket);
            }

            $this->model->update($idTicket, ['status_ticket' => $newStatus]);
            $db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('TicketService::changeStatus - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar el estado'];
        }
    }

    public function customerInfo(int $idTicket): array
    {
        $status = $this->model->getStatus($idTicket);
        if ($status === null) {
            return ['success' => false, 'message' => 'Ticket no encontrado'];
        }

        $data = match ((int) $status) {
            1 => $this->getSoldInfo($idTicket),
            2 => $this->getReservedInfo($idTicket),
            default => null,
        };

        if (!$data) {
            return ['success' => false, 'message' => 'Sin información'];
        }

        return ['success' => true, 'data' => $data];
    }

    private function resolveCustomerName(int $status, int $idTicket): ?string
    {
        $info = match ($status) {
            1 => $this->getSoldInfo($idTicket),
            2 => $this->getReservedInfo($idTicket),
            default => null,
        };

        if (!$info || empty($info['name_customer'])) {
            return null;
        }

        return trim((string) $info['name_customer']);
    }

    private function releaseReservationLink(int $idTicket): void
    {
        $rel = $this->reservationTicketModel->findLatestByTicket($idTicket);
        if (!$rel || (int) $rel->status_reservation !== 1) {
            return;
        }

        $idReservation = (int) $rel->id_reservation_reservation_ticket;
        $this->reservationTicketModel->deleteByTicket($idTicket);

        if ($this->reservationTicketModel->countByReservation($idReservation) === 0) {
            (new Reservation())->delete($idReservation);
        }
    }

    private function getSoldInfo(int $idTicket): ?array
    {
        $row = $this->model->getSoldCustomerInfo($idTicket);
        if (!$row) return null;

        return [
            'tipo'          => 'vendido',
            'name_customer' => $row->name_customer,
            'datetime'      => $row->date_created_sale,
        ];
    }

    private function getReservedInfo(int $idTicket): ?array
    {
        $rel = $this->reservationTicketModel->findLatestByTicket($idTicket);
        if (!$rel || !in_array((int) $rel->status_reservation, [1, 3])) {
            return null;
        }

        return [
            'tipo'          => 'reservado',
            'name_customer' => $rel->name_customer,
            'datetime'      => $rel->date_created_reservation,
        ];
    }
}
