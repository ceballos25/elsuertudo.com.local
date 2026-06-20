<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function handle(string $action): void
    {
        Auth::requireLogin();

        if ($action === 'cambiar_mi_password') {
            $this->json($this->service->changeOwnPassword($_POST));
            return;
        }

        Permissions::enforce('usuarios', $action);

        $result = match ($action) {
            'obtener'           => $this->service->list(),
            'crear'             => $this->service->create($_POST),
            'actualizar'        => $this->service->update($_POST),
            'cambiar_password'  => $this->service->changePassword($_POST),
            'desactivar'        => $this->service->deactivate((int) ($_POST['id_admin'] ?? 0)),
            default             => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
