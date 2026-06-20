<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Sale;
use App\Models\Ticket;

/**
 * Crea una venta y asigna tickets de forma atómica.
 * Reutilizable desde ventas admin, participación pública gratis, etc.
 */
class SaleFulfillmentService
{
    private Sale $saleModel;
    private Ticket $ticketModel;

    public function __construct()
    {
        $this->saleModel = new Sale();
        $this->ticketModel = new Ticket();
    }

    /**
     * @param  int[]  $ticketIds
     * @return array{success: bool, message?: string, id_sale?: int, code_sale?: string, numbers?: string[]}
     */
    public function fulfill(array $params): array
    {
        $idRaffle    = (int) ($params['id_raffle'] ?? 0);
        $idCustomer  = (int) ($params['id_customer'] ?? 0);
        $ticketIds   = array_values(array_unique(array_map('intval', $params['ticket_ids'] ?? [])));
        $total       = (float) ($params['total'] ?? 0);
        $payment     = trim((string) ($params['payment_method'] ?? 'Manual'));
        $idAdmin     = isset($params['id_admin']) ? (int) $params['id_admin'] : null;
        $codeSale    = trim((string) ($params['code_sale'] ?? ''));

        if ($idRaffle <= 0 || $idCustomer <= 0 || $ticketIds === []) {
            return ['success' => false, 'message' => 'Datos incompletos para registrar la venta'];
        }

        if ($total < 0) {
            return ['success' => false, 'message' => 'Total inválido'];
        }

        if ($codeSale === '') {
            $codeSale = 'SALE-' . date('YmdHis') . random_int(100, 999);
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $idSale = $this->saleModel->create([
                'id_customer_sale'    => $idCustomer,
                'id_raffle_sale'      => $idRaffle,
                'code_sale'           => $codeSale,
                'quantity_sale'       => count($ticketIds),
                'total_sale'          => $total,
                'payment_method_sale' => $payment,
                'status_sale'         => 1,
                'id_admin_sale'       => $idAdmin,
            ]);

            foreach ($ticketIds as $idTicket) {
                if (!$this->ticketModel->claimForSale($idTicket, $idRaffle, $idCustomer, $idSale)) {
                    throw new \RuntimeException("Ticket {$idTicket} no disponible");
                }
            }

            $db->commit();

            $numbers = array_map(
                fn($row) => $row->number_ticket,
                $this->ticketModel->getSoldBySale($idSale)
            );

            return [
                'success'   => true,
                'message'   => 'Participación registrada',
                'id_sale'   => $idSale,
                'code_sale' => $codeSale,
                'numbers'   => $numbers,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('SaleFulfillmentService::fulfill - ' . $e->getMessage());

            return ['success' => false, 'message' => 'Error al registrar la venta'];
        }
    }
}
