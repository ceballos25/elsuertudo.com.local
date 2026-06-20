<?php
/**
 * Punto de entrada único AJAX → MVC
 * POST: module, action, + datos del módulo
 */
require_once __DIR__ . '/../../config/config.php';

use App\Controllers\ParticipationController;
use App\Controllers\RaffleController;
use App\Controllers\ReservationController;
use App\Controllers\SaleController;
use App\Controllers\CustomerController;
use App\Controllers\TicketController;
use App\Controllers\DashboardController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Core\Response;

$module = trim($_POST['module'] ?? '');
$action = $_POST['action'] ?? '';

if ($module === '') {
    Response::json(['success' => false, 'message' => 'Módulo no especificado'], 400);
}

try {
    match ($module) {
        'rifas'        => (new RaffleController())->handle($action),
        'reservations' => (new ReservationController())->handle($action),
        'ventas'       => (new SaleController())->handle($action),
        'clientes'     => (new CustomerController())->handle($action),
        'numeros'        => (new TicketController())->handle($action),
        'participacion'  => (new ParticipationController())->handle($action),
        'dashboard'    => (new DashboardController())->handle($action),
        'settings'     => (new SettingsController())->handle($action),
        'usuarios'     => (new UserController())->handle($action),
        default        => Response::json(['success' => false, 'message' => 'Módulo no válido'], 404),
    };
} catch (Throwable $e) {
    error_log("API Error [{$module}/{$action}]: " . $e->getMessage());
    Response::json([
        'success' => false,
        'message' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor',
    ], 500);
}
