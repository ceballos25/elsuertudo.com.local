<?php

namespace App\Services;

use App\Core\RaffleMode;
use App\Core\SiteConfig;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Support\ParticipationRules;

/**
 * Participación pública en rifas gratis: venta directa, 1 número por persona.
 */
class FreeParticipationService
{
    private Raffle $raffleModel;
    private Ticket $ticketModel;
    private CustomerService $customerService;
    private SaleFulfillmentService $fulfillment;

    public function __construct()
    {
        $this->raffleModel = new Raffle();
        $this->ticketModel = new Ticket();
        $this->customerService = new CustomerService();
        $this->fulfillment = new SaleFulfillmentService();
    }

    public function register(array $data): array
    {
        if (empty($data['name_customer']) || empty($data['phone_customer'])) {
            return ['success' => false, 'message' => 'Nombre y teléfono obligatorios'];
        }

        if (!SiteConfig::isActive()) {
            return ['success' => false, 'message' => 'Sitio temporalmente inactivo'];
        }

        $idRaffle = (int) ($data['id_raffle'] ?? 0);
        if ($idRaffle <= 0) {
            return ['success' => false, 'message' => 'Rifa no válida'];
        }

        $raffle = $this->raffleModel->find($idRaffle);
        if (!$raffle) {
            return ['success' => false, 'message' => 'Rifa no encontrada'];
        }

        if (!RaffleMode::isFree($raffle)) {
            return ['success' => false, 'message' => 'Esta rifa no es gratuita'];
        }

        if ((int) $raffle->status_raffle !== 1) {
            return ['success' => false, 'message' => 'Rifa temporalmente detenida'];
        }

        $ticketIds = ParticipationRules::parseTicketIds($data['tickets'] ?? null);
        $validation = ParticipationRules::assertSelection(
            $this->ticketModel,
            $ticketIds,
            $idRaffle,
            RaffleMode::maxTicketsPerPublicOrder($raffle)
        );

        if (!$validation['ok']) {
            $response = ['success' => false, 'message' => $validation['message']];
            if (!empty($validation['ticket_ids'])) {
                $response['unavailable'] = $validation['ticket_ids'];
            }

            return $response;
        }

        try {
            $idCustomer = $this->customerService->findOrCreate([
                'name_customer'  => $data['name_customer'],
                'phone_customer' => $data['phone_customer'],
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error al gestionar el cliente'];
        }

        $existing = $this->ticketModel->findParticipationByCustomerInRaffle($idCustomer, $idRaffle);
        if ($existing !== null) {
            return [
                'success'  => false,
                'message'  => 'Ya participaste en esta rifa con el número ' . $existing->number_ticket,
                'existing' => true,
                'data'     => [
                    'number_ticket' => $existing->number_ticket,
                    'id_ticket'     => (int) $existing->id_ticket,
                ],
            ];
        }

        $result = $this->fulfillment->fulfill([
            'id_raffle'       => $idRaffle,
            'id_customer'     => $idCustomer,
            'ticket_ids'      => $ticketIds,
            'total'           => 0,
            'payment_method'  => RaffleMode::PAYMENT_FREE,
            'id_admin'        => null,
            'code_sale'       => 'FREE-' . strtoupper(bin2hex(random_bytes(6))),
        ]);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success'       => true,
            'message'       => '¡Tu número quedó registrado!',
            'code_sale'     => $result['code_sale'],
            'numbers'       => $result['numbers'],
            'id_sale'       => $result['id_sale'],
        ];
    }
}
