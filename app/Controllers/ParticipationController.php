<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\FreeParticipationService;

class ParticipationController extends Controller
{
    private FreeParticipationService $service;

    public function __construct()
    {
        $this->service = new FreeParticipationService();
    }

    public function handle(string $action): void
    {
        $result = match ($action) {
            'registrar_gratis' => $this->service->register($_POST),
            default            => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
