<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\RaffleMode;
use App\Core\SiteConfig;
use App\Core\Database;
use App\Models\Raffle;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Sale;
use App\Models\Ticket;

class ReservationService
{
    const METODO_WEB = 'Página Web';
    const PER_PAGE = 10;
    const MAX_PER_PAGE = 50;

    private Reservation $model;
    private ReservationTicket $pivotModel;
    private Ticket $ticketModel;
    private Raffle $raffleModel;
    private Sale $saleModel;
    private CustomerService $customerService;
    private ReceiptService $receiptService;

    public function __construct()
    {
        $this->model = new Reservation();
        $this->pivotModel = new ReservationTicket();
        $this->ticketModel = new Ticket();
        $this->raffleModel = new Raffle();
        $this->saleModel = new Sale();
        $this->customerService = new CustomerService();
        $this->receiptService = new ReceiptService();
    }

    public function create(array $data): array
    {
        if (empty($data['name_customer']) || empty($data['phone_customer'])) {
            return ['success' => false, 'message' => 'Nombre y teléfono obligatorios'];
        }

        $tickets = $this->parseTickets($data['tickets'] ?? null);
        if (empty($tickets)) {
            return ['success' => false, 'message' => 'No se enviaron tickets válidos'];
        }

        if (count($tickets) > SaleService::MAX_TICKETS_PER_ORDER) {
            return ['success' => false, 'message' => 'Máximo ' . SaleService::MAX_TICKETS_PER_ORDER . ' números por reserva'];
        }

        $idRaffle = (int) ($data['id_raffle'] ?? 0);
        if ($idRaffle <= 0) {
            return ['success' => false, 'message' => 'Rifa no válida'];
        }

        if (!SiteConfig::isActive()) {
            return ['success' => false, 'message' => 'Sitio temporalmente inactivo'];
        }

        $raffle = $this->raffleModel->find($idRaffle);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }
        if ((int) $raffle->status_raffle !== 1) {
            return ['success' => false, 'message' => 'Rifa temporalmente detenida'];
        }

        if (RaffleMode::isFree($raffle)) {
            return [
                'success' => false,
                'message' => 'Esta rifa es gratuita. Confirma tu número directamente en la página.',
            ];
        }

        if ($this->ticketModel->countInRaffle($tickets, $idRaffle) !== count($tickets)) {
            return ['success' => false, 'message' => 'Uno o más números no pertenecen a esta rifa'];
        }

        try {
            $idCustomer = $this->customerService->findOrCreate([
                'name_customer'  => $data['name_customer'],
                'phone_customer' => $data['phone_customer'],
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error al gestionar el cliente'];
        }

        $unavailable = $this->checkAvailability($tickets, $idRaffle);
        if (!empty($unavailable)) {
            return [
                'success'     => false,
                'message'     => 'Los siguientes tickets no están disponibles: ' . implode(', ', $unavailable),
                'unavailable' => $unavailable,
            ];
        }

        $db = Database::getInstance();
        $token = $this->generateUniqueReservationToken();

        try {
            $db->beginTransaction();

            $idReservation = $this->model->create([
                'id_raffle_reservation'    => $idRaffle,
                'id_customer_reservation'  => $idCustomer,
                'token_reservation'        => $token,
                'expires_at_reservation'   => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'status_reservation'       => 1,
                'date_created_reservation' => date('Y-m-d H:i:s'),
            ]);

            foreach ($tickets as $idTicket) {
                $this->pivotModel->create([
                    'id_reservation_reservation_ticket' => $idReservation,
                    'id_ticket_reservation_ticket'      => $idTicket,
                ]);

                if (!$this->ticketModel->claimForReservation($idTicket, $idRaffle)) {
                    throw new \RuntimeException("Ticket {$idTicket} no disponible");
                }
            }

            $db->commit();

            return [
                'success'        => true,
                'message'        => 'Reserva creada',
                'token'          => $token,
                'id_reservation' => $idReservation,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('ReservationService::create - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear la reserva'];
        }
    }

    public function list(array $filters = []): array
    {
        $dateFrom = $filters['fecha_inicio'] ?? '';
        $dateTo = $filters['fecha_fin'] ?? '';
        $periodo = $filters['periodo'] ?? '';

        if (!$dateFrom && !$dateTo && $periodo) {
            [$dateFrom, $dateTo] = SaleService::dateRange('', '', $periodo);
        }

        $searchFilters = [
            'search'    => trim($filters['search'] ?? ''),
            'id_raffle' => $filters['id_raffle'] ?? '',
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'status'    => $filters['status'] ?? 'pendientes',
        ];

        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? self::PER_PAGE)));
        $total = $this->model->countSearch($searchFilters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['page'] ?? 1)), $totalPages);
        $offset = ($page - 1) * $perPage;

        $rows = $this->model->search($searchFilters, $perPage, $offset);
        $reservationIds = array_map(fn($r) => (int) $r->id_reservation, $rows);
        $ticketsByReservation = $this->pivotModel->getTicketNumbersGrouped($reservationIds);

        foreach ($rows as &$reserva) {
            $reserva->tickets = $ticketsByReservation[(int) $reserva->id_reservation] ?? [];
            $reserva->quantity = count($reserva->tickets);
            $reserva->total = $reserva->quantity * (float) ($reserva->price_raffle ?? 0);
        }
        unset($reserva);

        return [
            'success'    => true,
            'data'       => $rows,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function cancel(int $idReservation, bool $manageTransaction = true): array
    {
        if ($idReservation <= 0) {
            return ['success' => false, 'message' => 'ID de reserva obligatorio'];
        }

        $resv = $this->model->find($idReservation);
        if (!$resv) {
            return ['success' => false, 'message' => 'Reserva no encontrada'];
        }

        if ((int) $resv->status_reservation === 3) {
            return ['success' => false, 'message' => 'No se puede eliminar una reserva ya completada'];
        }

        if ((int) $resv->status_reservation !== 1) {
            return ['success' => false, 'message' => 'La reserva no está activa'];
        }

        $db = Database::getInstance();

        try {
            if ($manageTransaction) {
                $db->beginTransaction();
            }

            $ticketIds = $this->pivotModel->getTicketIds($idReservation);
            foreach ($ticketIds as $idTicket) {
                if ($this->ticketModel->getStatus($idTicket) === 2) {
                    $this->ticketModel->update($idTicket, ['status_ticket' => 0]);
                }
            }

            $this->pivotModel->deleteByReservation($idReservation);
            $this->model->delete($idReservation);

            if ($manageTransaction) {
                $db->commit();
            }

            return ['success' => true, 'message' => 'Reserva eliminada'];
        } catch (\Throwable $e) {
            if ($manageTransaction) {
                $db->rollBack();
            } else {
                throw $e;
            }
            error_log('ReservationService::cancel - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar la reserva'];
        }
    }

    public function bulkRelease(?int $idRaffle = null): array
    {
        $active = $this->model->getActive($idRaffle);
        $count = 0;

        foreach ($active as $r) {
            $result = $this->cancel((int) $r->id_reservation);
            if ($result['success']) {
                $count++;
            }
        }

        return [
            'success'   => true,
            'message'   => 'Reservas liberadas correctamente',
            'liberadas' => $count,
        ];
    }

    public function acceptSale(int $idReservation): array
    {
        if ($idReservation <= 0) {
            return ['success' => false, 'message' => 'ID de reserva obligatorio'];
        }

        $resv = $this->model->find($idReservation);
        if (!$resv || (int) $resv->status_reservation !== 1) {
            return ['success' => false, 'message' => 'La reserva no está activa'];
        }

        if ($this->isExpired($resv)) {
            $this->cancel($idReservation);
            return ['success' => false, 'message' => 'La reserva expiró'];
        }

        $ticketIds = $this->pivotModel->getTicketIds($idReservation);
        if (empty($ticketIds)) {
            return ['success' => false, 'message' => 'No hay tickets asociados'];
        }

        foreach ($ticketIds as $tid) {
            if ($this->ticketModel->getStatus($tid) !== 2) {
                return ['success' => false, 'message' => "El ticket {$tid} no está reservado"];
            }
        }

        $raffle = $this->raffleModel->find((int) $resv->id_raffle_reservation);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }

        $cantidad = count($ticketIds);
        $total = $cantidad * (float) $raffle->price_raffle;

        if ($total <= 0) {
            return ['success' => false, 'message' => 'Total inválido'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            if (!$this->model->markCompleted($idReservation)) {
                throw new \RuntimeException('La reserva ya fue procesada');
            }

            $idSale = $this->saleModel->create([
                'id_customer_sale'      => (int) $resv->id_customer_reservation,
                'id_raffle_sale'        => (int) $resv->id_raffle_reservation,
                'quantity_sale'         => $cantidad,
                'total_sale'            => $total,
                'code_sale'             => $resv->token_reservation,
                'payment_method_sale'   => self::METODO_WEB,
                'status_sale'           => 1,
                'id_admin_sale'         => Auth::userId(),
            ]);

            foreach ($ticketIds as $tid) {
                if (!$this->ticketModel->claimFromReservation(
                    $tid,
                    (int) $resv->id_customer_reservation,
                    $idSale
                )) {
                    throw new \RuntimeException("Ticket {$tid} no está reservado");
                }
            }

            $db->commit();

            $htmlRecibo = $this->receiptService->generate($idSale);

            return [
                'success'     => true,
                'message'     => 'Venta aceptada correctamente',
                'id_sale'     => $idSale,
                'html_recibo' => $htmlRecibo,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error al crear la venta'];
        }
    }

    public function detailByToken(string $token): ?array
    {
        $reserva = $this->model->findByTokenWithCustomer($token);
        if (!$reserva) return null;

        $tickets = $this->pivotModel->getTicketNumbers((int) $reserva->id_reservation);
        $quantity = count($tickets);
        $raffle = $this->raffleModel->find((int) $reserva->id_raffle_reservation);
        $total = $quantity * (float) ($raffle?->price_raffle ?? 0);

        return [
            'reserva' => [
                'name_customer'            => $reserva->name_customer,
                'phone_customer'           => $reserva->phone_customer,
                'token_reservation'        => $reserva->token_reservation,
                'expires_at_reservation'   => $reserva->expires_at_reservation,
                'quantity'                 => $quantity,
                'total'                    => $total,
            ],
            'tickets' => array_map(fn($t) => ['number_ticket' => $t->number_ticket], $tickets),
        ];
    }

    public function fullDetail(string $token): ?array
    {
        $reserva = $this->model->findByToken($token);
        if (!$reserva) return null;

        $tickets = $this->pivotModel->findAll(
            ['id_reservation_reservation_ticket' => $reserva->id_reservation]
        );

        return ['reserva' => $reserva, 'tickets' => $tickets];
    }

    private function parseTickets($raw): array
    {
        $tickets = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($tickets)) {
            $tickets = array_filter(array_map('intval', explode(',', (string) $raw)));
        } else {
            $tickets = array_map('intval', $tickets);
        }
        return array_values(array_filter($tickets));
    }

    private function checkAvailability(array $tickets, int $idRaffle): array
    {
        $occupied = $this->ticketModel->getOccupiedInRaffle($tickets, $idRaffle);
        return array_map(fn($t) => (int) $t->id_ticket, $occupied);
    }

    private function isExpired(object $resv): bool
    {
        $expires = $resv->expires_at_reservation ?? null;
        if (!$expires) {
            return false;
        }
        return strtotime((string) $expires) < time();
    }

    private function generateUniqueReservationToken(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $token = str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            if ($this->model->findByToken($token) === null) {
                return $token;
            }
        }

        throw new \RuntimeException('No se pudo generar un código de reserva único');
    }
}
