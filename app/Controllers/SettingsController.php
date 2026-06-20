<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    private SettingsService $service;

    public function __construct()
    {
        $this->service = new SettingsService();
    }

    public function handle(string $action): void
    {
        if ($action === 'actualizar') {
            Auth::requireLogin();
        }

        Permissions::enforce('settings', $action);

        $result = match ($action) {
            'obtener'   => $this->service->getAll(),
            'actualizar'=> $this->service->update($_POST, $_FILES),
            default     => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
