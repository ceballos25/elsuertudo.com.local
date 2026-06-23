<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\CustomerService;

class CustomerController extends Controller
{
    private CustomerService $service;

    public function __construct()
    {
        $this->service = new CustomerService();
    }

    public function handle(string $action): void
    {
        $publicActions = ['buscar_por_celular'];

        if (!in_array($action, $publicActions, true)) {
            Auth::requireLogin();
        }

        $result = match ($action) {
            'obtener'          => $this->service->list($_POST),
            'buscar_por_celular' => $this->service->findByPhone($_POST),
            'crear'            => $this->service->create($_POST),
            'actualizar'         => $this->service->update($_POST),
            'toggle_lista_negra' => $this->service->toggleBlacklist((int) ($_POST['id_customer'] ?? 0)),
            'eliminar'           => $this->service->delete($_POST),
            default     => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
