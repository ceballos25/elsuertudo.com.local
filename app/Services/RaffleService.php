<?php

namespace App\Services;

use App\Core\Database;
use App\Core\RaffleMode;
use App\Models\Raffle;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Ticket;

class RaffleService
{
    private Raffle $model;
    private Ticket $ticketModel;
    private Sale $saleModel;

    public function __construct()
    {
        $this->model = new Raffle();
        $this->ticketModel = new Ticket();
        $this->saleModel = new Sale();
    }

    public function list(array $filters = []): array
    {
        $search = trim($filters['search'] ?? '');
        $status = isset($filters['status']) && $filters['status'] !== ''
            ? (int) $filters['status']
            : null;

        return [
            'success' => true,
            'data'    => $this->model->search($search, $status),
        ];
    }

    public function getById(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        $raffle = $this->model->find($id);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }

        return ['success' => true, 'data' => $raffle];
    }

    public function create(array $data): array
    {
        set_time_limit(0);

        $digits = (int) ($data['digits_raffle'] ?? 2);
        if ($digits < 1 || $digits > 5) {
            return ['success' => false, 'message' => 'Cifras inválidas (1-5)'];
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $mode = RaffleMode::normalizeCreatePayload($data);

            $idRaffle = $this->model->create([
                'title_raffle'       => trim($data['title_raffle']),
                'description_raffle' => trim($data['description_raffle'] ?? ''),
                'promotions_raffle'  => trim($data['promotions_raffle'] ?? ''),
                'price_raffle'       => $mode['price_raffle'],
                'is_free_raffle'     => $mode['is_free_raffle'],
                'digits_raffle'      => $digits,
                'date_raffle'        => $data['date_raffle'],
                'status_raffle'      => (int) ($data['status_raffle'] ?? 1),
            ]);

            $this->ticketModel->bulkCreate($idRaffle, $digits);
            $total = (int) pow(10, $digits);

            $db->commit();
            return ['success' => true, 'message' => "Rifa creada con {$total} boletos."];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('RaffleService::create - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear la rifa'];
        }
    }

    public function update(array $data): array
    {
        $id = (int) ($data['id_raffle'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        unset($data['action'], $data['id_raffle']);

        $allowed = ['title_raffle', 'description_raffle', 'promotions_raffle',
            'price_raffle', 'is_free_raffle', 'digits_raffle', 'date_raffle', 'status_raffle'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (isset($update['is_free_raffle'])) {
            $mode = RaffleMode::normalizeCreatePayload([
                'is_free_raffle' => $update['is_free_raffle'],
                'price_raffle'   => $update['price_raffle'] ?? 0,
            ]);
            $update['is_free_raffle'] = $mode['is_free_raffle'];
            $update['price_raffle'] = $mode['price_raffle'];
        }

        if (isset($update['status_raffle'])) {
            $update['status_raffle'] = (int) $update['status_raffle'];
        }

        $this->model->update($id, $update);
        return ['success' => true, 'message' => 'Rifa actualizada'];
    }

    public function delete(array $data): array
    {
        $id = (int) ($data['id_raffle'] ?? 0);
        if ($id <= 0) {
            return ['success' => false];
        }

        $this->model->delete($id);
        return ['success' => true, 'message' => 'Rifa eliminada'];
    }

    public function reuse(array $data): array
    {
        $idRaffle = (int) ($data['id_raffle'] ?? 0);
        if ($idRaffle <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $this->cancelActiveReservations($idRaffle);

            $saleIds = $this->ticketModel->resetByRaffle($idRaffle);
            $this->saleModel->deleteByIds($saleIds);

            $db->commit();
            return [
                'success' => true,
                'message' => 'Rifa reutilizada correctamente. Se cancelaron las reservas activas, se eliminaron las ventas y todos los números quedaron disponibles.',
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('RaffleService::reuse - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al reutilizar la rifa'];
        }
    }

    private function cancelActiveReservations(int $idRaffle): void
    {
        $reservationService = new ReservationService();

        foreach ((new Reservation())->getActive($idRaffle) as $reservation) {
            $result = $reservationService->cancel((int) $reservation->id_reservation, false);
            if (!$result['success']) {
                throw new \RuntimeException($result['message'] ?? 'No se pudo cancelar una reserva activa');
            }
        }
    }

    public function listTitles(): array
    {
        return ['success' => true, 'data' => $this->model->listTitles()];
    }
}
