<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\RaffleService;

class RaffleController extends Controller
{
    private RaffleService $service;

    public function __construct()
    {
        $this->service = new RaffleService();
    }

    public function handle(string $action): void
    {
        $publicActions = ['obtener_activas', 'obtener_por_id', 'obtener_rifas'];

        if (!in_array($action, $publicActions)) {
            Auth::requireLogin();
        }

        Permissions::enforce('rifas', $action);

        $result = match ($action) {
            'obtener', 'obtener_activas' => $this->listAction(),
            'obtener_rifas' => $this->service->listTitles(),
            'crear'         => $this->service->create($_POST),
            'actualizar'    => $this->service->update($_POST),
            'eliminar'      => $this->service->delete($_POST),
            'obtener_por_id'=> $this->service->getById((int) ($_POST['id_raffle'] ?? 0)),
            'reutilizar'    => $this->service->reuse($_POST),
            default         => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }

    private function listAction(): array
    {
        $filters = $_POST;
        if (($_POST['action'] ?? '') === 'obtener_activas') {
            $filters['status'] = 1;
        }
        return $this->service->list($filters);
    }
}
