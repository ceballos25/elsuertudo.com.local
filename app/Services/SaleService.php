<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RaffleMode;
use App\Models\Raffle;
use App\Models\Sale;
use App\Models\Ticket;
use App\Support\ParticipationRules;

class SaleService
{
    const METODO_MANUAL = 'Manual';
    const MAX_TICKETS_PER_ORDER = 50;
    const PER_PAGE = 10;
    const MAX_PER_PAGE = 50;

    private Sale $model;
    private Ticket $ticketModel;
    private Raffle $raffleModel;
    private CustomerService $customerService;
    private ReceiptService $receiptService;
    private ReceiptShareService $receiptShareService;
    private SaleFulfillmentService $fulfillment;

    public function __construct()
    {
        $this->model = new Sale();
        $this->ticketModel = new Ticket();
        $this->raffleModel = new Raffle();
        $this->customerService = new CustomerService();
        $this->receiptService = new ReceiptService();
        $this->receiptShareService = new ReceiptShareService();
        $this->fulfillment = new SaleFulfillmentService();
    }

    public function list(array $filters = []): array
    {
        $dateFrom = $filters['fecha_inicio'] ?? '';
        $dateTo = $filters['fecha_fin'] ?? '';
        $periodo = $filters['periodo'] ?? '';

        if (!$dateFrom && !$dateTo && $periodo) {
            [$dateFrom, $dateTo] = self::dateRange('', '', $periodo);
        }

        $searchFilters = [
            'search'         => trim($filters['search'] ?? ''),
            'id_raffle'      => $filters['id_raffle'] ?? '',
            'id_admin'       => $filters['id_admin'] ?? '',
            'payment_method' => $filters['payment_method'] ?? '',
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
        ];

        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? self::PER_PAGE)));
        $total = $this->model->countSearch($searchFilters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($filters['page'] ?? 1)), $totalPages);
        $offset = ($page - 1) * $perPage;

        $rows = $this->model->search($searchFilters, $perPage, $offset);
        $saleIds = array_map(fn($s) => (int) $s->id_sale, $rows);
        $ticketsBySale = $this->ticketModel->getNumbersGroupedBySale($saleIds);

        foreach ($rows as &$sale) {
            $sale->tickets = $ticketsBySale[(int) $sale->id_sale] ?? [];
        }
        unset($sale);

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

    public function create(array $data): array
    {
        $tickets = ParticipationRules::parseTicketIds($data['tickets_ids'] ?? []);
        if ($tickets === []) {
            return ['success' => false, 'message' => 'No se enviaron números'];
        }

        if (count($tickets) > self::MAX_TICKETS_PER_ORDER) {
            return ['success' => false, 'message' => 'Máximo ' . self::MAX_TICKETS_PER_ORDER . ' números por venta'];
        }

        $idRaffle = (int) ($data['id_raffle'] ?? 0);
        if ($idRaffle <= 0) {
            return ['success' => false, 'message' => 'Rifa no válida'];
        }

        $raffle = $this->raffleModel->find($idRaffle);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }
        if ((int) $raffle->status_raffle !== 1) {
            return ['success' => false, 'message' => 'Rifa temporalmente detenida'];
        }

        $validation = ParticipationRules::assertSelection(
            $this->ticketModel,
            $tickets,
            $idRaffle,
            self::MAX_TICKETS_PER_ORDER
        );

        if (!$validation['ok']) {
            return [
                'success'  => false,
                'message'  => $validation['message'],
                'ocupados' => $validation['occupied'] ?? [],
            ];
        }

        $idCliente = $this->customerService->findOrCreateFromSale($data);
        if (!$idCliente) {
            return ['success' => false, 'message' => 'Error al procesar cliente'];
        }

        $cantidad = count($tickets);
        $total = (float) $raffle->price_raffle * $cantidad;

        if ($total <= 0 && !RaffleMode::allowsZeroTotal($raffle)) {
            return ['success' => false, 'message' => 'Total inválido'];
        }

        $result = $this->fulfillment->fulfill([
            'id_raffle'       => $idRaffle,
            'id_customer'     => $idCliente,
            'ticket_ids'      => $tickets,
            'total'           => $total,
            'payment_method'  => self::METODO_MANUAL,
            'id_admin'        => Auth::userId(),
            'code_sale'       => $data['code_sale'] ?? ('SALE-' . date('YmdHis') . rand(100, 999)),
        ]);

        if (!$result['success']) {
            return $result;
        }

        return ['success' => true, 'id_sale' => $result['id_sale']];
    }

    public function detail(int $idVenta): array
    {
        $venta = $this->model->findWithDetails($idVenta);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        $tickets = $this->ticketModel->getBySale($idVenta);
        $html = $this->receiptService->generateFromData($venta, $tickets);

        if (!$html) {
            return ['success' => false, 'message' => 'Error al generar recibo'];
        }

        return ['success' => true, 'html_recibo' => $html];
    }

    public function uploadReceiptShare(array $post, array $files): array
    {
        return $this->receiptShareService->storeUpload($post, $files);
    }

    public function findByCode(string $code): array
    {
        $venta = $this->model->findByCode($code);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        $tickets = $this->ticketModel->getBySale((int) $venta->id_sale);

        return ['success' => true, 'venta' => $venta, 'tickets' => $tickets];
    }

    public function soldNumbers(array $filters): array
    {
        $dateFrom = $filters['fecha_inicio'] ?? '';
        $dateTo = $filters['fecha_fin'] ?? '';
        $periodo = $filters['periodo'] ?? '';

        if (!$dateFrom && !$dateTo && $periodo) {
            [$dateFrom, $dateTo] = self::dateRange('', '', $periodo);
        }

        $rows = $this->ticketModel->getSoldReport([
            'search'         => trim($filters['search'] ?? ''),
            'id_raffle'      => $filters['id_raffle'] ?? '',
            'id_admin'       => $filters['id_admin'] ?? '',
            'payment_method' => $filters['payment_method'] ?? '',
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
        ]);

        return ['success' => true, 'data' => array_values($rows)];
    }

    public function listSellers(): array
    {
        $admins = (new \App\Models\Admin())->listAll();
        return ['success' => true, 'data' => $admins];
    }

    public function getForManage(int $idSale): array
    {
        $venta = $this->model->findWithDetails($idSale);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        if ((int) $venta->status_sale !== 1) {
            return ['success' => false, 'message' => 'Esta venta ya fue anulada'];
        }

        $raffle = $this->raffleModel->find((int) $venta->id_raffle_sale);
        $tickets = $this->ticketModel->getSoldBySale($idSale);

        return [
            'success' => true,
            'data'    => [
                'id_sale'          => (int) $venta->id_sale,
                'code_sale'        => $venta->code_sale,
                'quantity_sale'    => (int) $venta->quantity_sale,
                'total_sale'       => (float) $venta->total_sale,
                'title_raffle'     => $venta->title_raffle,
                'name_customer'    => $venta->name_customer,
                'phone_customer'   => $venta->phone_customer,
                'id_customer_sale' => (int) $venta->id_customer_sale,
                'price_unit'       => $raffle ? (float) $raffle->price_raffle : 0,
                'tickets'          => $tickets,
            ],
        ];
    }

    public function changeCustomer(array $data): array
    {
        $idSale = (int) ($data['id_sale'] ?? 0);
        $venta = $this->model->findActive($idSale);

        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no válida o ya anulada'];
        }

        $ventaDetalle = $this->model->findWithDetails($idSale);

        $name = trim($data['name_customer'] ?? '');
        $phone = trim($data['phone_customer'] ?? '');

        if ($name === '' || $phone === '') {
            return ['success' => false, 'message' => 'Nombre y teléfono del cliente son obligatorios'];
        }

        $idCliente = $this->customerService->findOrCreateFromSale([
            'name_customer'  => $name,
            'phone_customer' => $phone,
            'id_customer'    => $data['id_customer'] ?? null,
        ]);

        if (!$idCliente) {
            return ['success' => false, 'message' => 'No se pudo asignar el cliente'];
        }

        if ((int) $venta->id_customer_sale === $idCliente) {
            return ['success' => false, 'message' => 'No hay cambios: el cliente ingresado es el mismo de esta venta'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $this->model->update($idSale, ['id_customer_sale' => $idCliente]);
            $this->ticketModel->updateCustomerBySale($idSale, $idCliente);

            $db->commit();

            $nuevo = $this->customerService->getById($idCliente);

            return [
                'success' => true,
                'message' => 'Cliente de la venta actualizado correctamente',
                'data'    => [
                    'cliente_anterior' => [
                        'nombre'  => $ventaDetalle->name_customer ?? '',
                        'celular' => $ventaDetalle->phone_customer ?? '',
                    ],
                    'cliente_nuevo' => [
                        'nombre'  => $nuevo->name_customer ?? $name,
                        'celular' => $nuevo->phone_customer ?? $phone,
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('SaleService::changeCustomer - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar el cliente'];
        }
    }

    public function releaseTickets(array $data): array
    {
        $idSale = (int) ($data['id_sale'] ?? 0);
        $requested = $this->parseTickets($data['tickets_ids'] ?? []);

        if (empty($requested)) {
            return ['success' => false, 'message' => 'Selecciona al menos un número'];
        }

        $venta = $this->model->findActive($idSale);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no válida o ya anulada'];
        }

        $validIds = $this->ticketModel->filterIdsInSale($requested, $idSale);
        if (count($validIds) !== count($requested)) {
            return ['success' => false, 'message' => 'Uno o más números no pertenecen a esta venta'];
        }

        $totalSold = $this->ticketModel->countSoldBySale($idSale);
        if (count($validIds) >= $totalSold) {
            return [
                'success' => false,
                'message' => 'Para liberar todos los números usa la opción de anular venta completa',
            ];
        }

        $raffle = $this->raffleModel->find((int) $venta->id_raffle_sale);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }

        $released = count($validIds);
        $priceUnit = (float) $raffle->price_raffle;
        $newQty = (int) $venta->quantity_sale - $released;
        $newTotal = max(0, (float) $venta->total_sale - ($priceUnit * $released));

        $numerosLiberados = [];
        foreach ($this->ticketModel->getSoldBySale($idSale) as $t) {
            if (in_array((int) $t->id_ticket, $validIds, true)) {
                $numerosLiberados[] = $t->number_ticket;
            }
        }
        sort($numerosLiberados);

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $this->ticketModel->releaseFromSale($validIds);
            $this->model->update($idSale, [
                'quantity_sale' => $newQty,
                'total_sale'    => $newTotal,
            ]);

            $db->commit();
            return [
                'success' => true,
                'message' => "Se liberaron {$released} número(s). La venta quedó con {$newQty}.",
                'data'    => [
                    'numeros_liberados' => $numerosLiberados,
                    'quantity_restante' => $newQty,
                    'total_restante'    => $newTotal,
                ],
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('SaleService::releaseTickets - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al liberar números'];
        }
    }

    public function cancel(int $idSale): array
    {
        $venta = $this->model->findActive($idSale);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no válida o ya anulada'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $this->ticketModel->releaseAllBySale($idSale);
            $this->model->update($idSale, [
                'status_sale'   => 0,
                'quantity_sale' => 0,
                'total_sale'    => 0,
            ]);

            $db->commit();
            return ['success' => true, 'message' => 'Venta anulada. Los números quedaron disponibles'];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('SaleService::cancel - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al anular la venta'];
        }
    }

    public static function dateRange(string $from, string $to, string $period): array
    {
        if ($from && $to) {
            return [$from, $to];
        }
        if (!$period) {
            return [null, null];
        }

        $today = date('Y-m-d');
        $ranges = [
            'today'     => [$today, $today],
            'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
            'week'      => [date('Y-m-d', strtotime('monday this week')), $today],
            'month'     => [date('Y-m-01'), date('Y-m-t')],
            'year'      => [date('Y-01-01'), date('Y-12-31')],
        ];

        return $ranges[$period] ?? [null, null];
    }

    private function parseTickets($raw): array
    {
        $tickets = is_array($raw) ? $raw : json_decode($raw, true);
        return is_array($tickets) ? array_map('intval', $tickets) : [];
    }

    private function validateAvailable(array $ticketIds, int $idRaffle): array
    {
        if (empty($ticketIds)) return [];

        return $this->ticketModel->getOccupiedInRaffle($ticketIds, $idRaffle);
    }
}
