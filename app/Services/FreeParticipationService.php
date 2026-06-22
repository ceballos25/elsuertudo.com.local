<?php

namespace App\Services;

use App\Core\RaffleMode;
use App\Core\SiteConfig;
use App\Models\Raffle;

/**
 * Participación pública en rifas gratis: reserva pendiente + confirmación manual del asesor.
 */
class FreeParticipationService
{
    private Raffle $raffleModel;
    private ReservationService $reservationService;

    public function __construct()
    {
        $this->raffleModel = new Raffle();
        $this->reservationService = new ReservationService();
    }

    public function register(array $data): array
    {
        if (empty($data['name_customer']) || empty($data['phone_customer'])) {
            return ['success' => false, 'message' => 'Nombre y teléfono obligatorios'];
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

        if (!SiteConfig::isActive()) {
            return ['success' => false, 'message' => 'Sitio temporalmente inactivo'];
        }

        $result = $this->reservationService->create($data);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success'        => true,
            'message'        => 'Reserva creada. Un asesor confirmará tu participación.',
            'token'          => $result['token'],
            'id_reservation' => $result['id_reservation'],
        ];
    }
}
