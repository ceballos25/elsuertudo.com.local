<?php

require_once __DIR__ . '/../config/config.php';

use App\Core\Auth;
use App\Services\RaffleSalesExportService;

if (!Auth::check() || !Auth::refreshSession()) {
    header('Location: ' . BASE_URL . '/dash.php');
    exit;
}

$idRaffle = (int) ($_GET['id_raffle'] ?? 0);

try {
    (new RaffleSalesExportService())->streamExcel($idRaffle);
} catch (Throwable $e) {
    error_log('export-ventas-rifa: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al generar el informe');
}
