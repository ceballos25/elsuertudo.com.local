<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    private DashboardService $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    public function handle(string $action): void
    {
        Auth::requireLogin();

        $result = match ($action) {
            'obtener_dashboard' => $this->service->getData($_POST),
            default             => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
