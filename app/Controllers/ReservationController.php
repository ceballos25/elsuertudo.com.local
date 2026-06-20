<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\ReservationService;

class ReservationController extends Controller
{
    private ReservationService $service;

    public function __construct()
    {
        $this->service = new ReservationService();
    }

    public function handle(string $action): void
    {
        $publicActions = ['crear_reserva', 'detalle'];

        if (!in_array($action, $publicActions)) {
            Auth::requireLogin();
        }

        Permissions::enforce('reservations', $action);

        $result = match ($action) {
            'crear_reserva' => $this->service->create($_POST),
            'obtener'       => $this->service->list($_POST),
            'cancelar'      => $this->service->cancel((int) ($_POST['id_reservation'] ?? 0)),
            'aceptar_venta' => $this->service->acceptSale((int) ($_POST['id_reservation'] ?? 0)),
            'detalle'       => $this->detailAction(),
            'liberar_reservas_masivo' => $this->service->bulkRelease(
                !empty($_POST['id_raffle']) ? (int) $_POST['id_raffle'] : null
            ),
            default => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }

    private function detailAction(): array
    {
        $token = $_POST['token'] ?? '';
        $data = $this->service->detailByToken($token);
        if (!$data) {
            return ['success' => false, 'message' => 'Reserva no encontrada'];
        }
        return ['success' => true, 'data' => $data];
    }
}
