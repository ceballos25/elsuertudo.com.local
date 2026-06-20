<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\TicketService;

class TicketController extends Controller
{
    private TicketService $service;

    public function __construct()
    {
        $this->service = new TicketService();
    }

    public function handle(string $action): void
    {
        $publicActions = ['obtener_inventario', 'obtener_info_ticket'];

        if (!in_array($action, $publicActions)) {
            Auth::requireLogin();
        }

        if ($action === 'cambiar_estado') {
            Permissions::enforce('numeros', $action);
        }

        $result = match ($action) {
            'obtener_inventario'  => $this->service->inventory($_POST),
            'obtener_info_ticket' => $this->service->customerInfo(
                (int) ($_POST['id_ticket'] ?? 0)
            ),
            'cambiar_estado'      => $this->service->changeStatus(
                (int) ($_POST['id_ticket'] ?? 0),
                (int) ($_POST['status'] ?? 0)
            ),
            default               => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
