<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Permissions;
use App\Services\SaleService;

class SaleController extends Controller
{
    private SaleService $service;

    public function __construct()
    {
        $this->service = new SaleService();
    }

    public function handle(string $action): void
    {
        Auth::requireLogin();

        Permissions::enforce('ventas', $action);

        $result = match ($action) {
            'obtener'              => $this->service->list($_POST),
            'crear_venta'          => $this->service->create($_POST),
            'detalle_venta'              => $this->service->detail((int) ($_POST['id_sale'] ?? 0)),
            'subir_comprobante_whatsapp' => $this->service->uploadReceiptShare($_POST, $_FILES),
            'gestion_venta'              => $this->service->getForManage((int) ($_POST['id_sale'] ?? 0)),
            'cambiar_cliente'      => $this->service->changeCustomer($_POST),
            'liberar_numeros'      => $this->service->releaseTickets($_POST),
            'cancelar_venta'       => $this->service->cancel((int) ($_POST['id_sale'] ?? 0)),
            'obtener_por_codigo'   => $this->service->findByCode($_POST['code_sale'] ?? ''),
            'listar_vendedores'    => $this->service->listSellers(),
            'numeros_vendidos'     => $this->service->soldNumbers($_POST),
            default                => ['success' => false, 'message' => 'Acción no válida'],
        };

        $this->json($result);
    }
}
