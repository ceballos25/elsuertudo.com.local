<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Ticket;

class DashboardService
{
    private Sale $saleModel;
    private Ticket $ticketModel;
    private Customer $customerModel;

    public function __construct()
    {
        $this->saleModel = new Sale();
        $this->ticketModel = new Ticket();
        $this->customerModel = new Customer();
    }

    public function getData(array $filters = []): array
    {
        $fechaDesde = $filters['fechaDesde'] ?? date('Y-m-01');
        $fechaHasta = $filters['fechaHasta'] ?? date('Y-m-d');
        $idRaffle = !empty($filters['id_raffle']) ? (int) $filters['id_raffle'] : null;
        $onlyActiveRaffles = $idRaffle === null;

        $response = [
            'kpis' => [
                'totalVentas'        => 0,
                'numerosVendidos'    => 0,
                'numerosReservados'  => 0,
                'numerosDisponibles' => 0,
                'totalClientes'      => 0,
                'totalNumeros'       => 0,
                'totalTransacciones' => 0,
            ],
            'graficas' => [
                'tendencia'               => [],
                'mediosPagoTransacciones' => [],
                'mediosPagoTickets'       => [],
                'mediosPagoDinero'        => [],
                'mediosPagoLabels'        => [],
                'topClientes'             => [],
                'heatmap'                 => [],
                'paquetes'                => [],
            ],
            'ultimasVentas' => [],
        ];

        $ventas = $this->saleModel->getDashboardSales($fechaDesde, $fechaHasta, $idRaffle, $onlyActiveRaffles);

        $tendenciaMap = [];
        $mediosTransaccionesMap = [];
        $mediosTicketsMap = [];
        $mediosDineroMap = [];
        $clientesDetalle = [];
        $heatmapRaw = [];
        $paquetesMap = [];

        for ($d = 1; $d <= 7; $d++) {
            for ($h = 0; $h <= 23; $h++) {
                $heatmapRaw[$d][$h] = 0;
            }
        }

        foreach ($ventas as $v) {
            $monto = (float) $v->total_sale;
            $cantidad = (int) $v->quantity_sale;
            $timestamp = strtotime($v->date_created_sale);

            $response['kpis']['totalVentas'] += $monto;

            $fecha = date('Y-m-d', $timestamp);
            $tendenciaMap[$fecha] = ($tendenciaMap[$fecha] ?? 0) + $monto;

            $metodo = $v->payment_method_sale ?: 'Otros';
            $mediosDineroMap[$metodo] = ($mediosDineroMap[$metodo] ?? 0) + $monto;
            $mediosTicketsMap[$metodo] = ($mediosTicketsMap[$metodo] ?? 0) + $cantidad;
            $mediosTransaccionesMap[$metodo] = ($mediosTransaccionesMap[$metodo] ?? 0) + 1;

            $nombre = trim($v->name_customer) ?: 'Cliente';
            if (!isset($clientesDetalle[$nombre])) {
                $clientesDetalle[$nombre] = [
                    'total'    => 0,
                    'cantidad' => 0,
                    'telefono' => $v->phone_customer ?: 'N/A',
                ];
            }
            $clientesDetalle[$nombre]['total'] += $monto;
            $clientesDetalle[$nombre]['cantidad'] += $cantidad;

            $diaSemana = (int) date('N', $timestamp);
            $horaDia = (int) date('G', $timestamp);
            $heatmapRaw[$diaSemana][$horaDia]++;

            $keyPaquete = $cantidad . ' Ticket' . ($cantidad > 1 ? 's' : '');
            $paquetesMap[$keyPaquete] = ($paquetesMap[$keyPaquete] ?? 0) + 1;
        }

        $response['ultimasVentas'] = array_slice($ventas, 0, 10);
        $response['kpis']['totalTransacciones'] = count($ventas);
        $response['kpis']['numerosVendidos']    = $this->ticketModel->countByStatus(1, $idRaffle, $onlyActiveRaffles);
        $response['kpis']['numerosReservados']  = $this->ticketModel->countByStatus(2, $idRaffle, $onlyActiveRaffles);
        $response['kpis']['numerosDisponibles'] = $this->ticketModel->countByStatus(0, $idRaffle, $onlyActiveRaffles);
        $response['kpis']['totalNumeros']       = $response['kpis']['numerosVendidos']
            + $response['kpis']['numerosReservados']
            + $response['kpis']['numerosDisponibles'];
        $response['kpis']['totalClientes']      = $this->customerModel->count();

        ksort($tendenciaMap);
        foreach ($tendenciaMap as $f => $monto) {
            $response['graficas']['tendencia'][] = ['fecha' => $f, 'total' => $monto];
        }

        foreach ($mediosDineroMap as $m => $dinero) {
            $response['graficas']['mediosPagoDinero'][] = $dinero;
            $response['graficas']['mediosPagoTickets'][] = $mediosTicketsMap[$m] ?? 0;
            $response['graficas']['mediosPagoTransacciones'][] = $mediosTransaccionesMap[$m] ?? 0;
            $response['graficas']['mediosPagoLabels'][] = $m;
        }

        uasort($clientesDetalle, fn($a, $b) => $b['total'] <=> $a['total']);
        $i = 0;
        foreach ($clientesDetalle as $nombre => $datos) {
            if ($i++ >= 5) break;
            $response['graficas']['topClientes'][] = [
                'name'     => $nombre,
                'total'    => $datos['total'],
                'cantidad' => $datos['cantidad'],
                'telefono' => $datos['telefono'],
            ];
        }

        $diasLabels = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        foreach ($diasLabels as $num => $nombreDia) {
            $dataDia = [];
            for ($h = 0; $h <= 23; $h++) {
                $dataDia[] = ['x' => $h . ':00', 'y' => $heatmapRaw[$num][$h]];
            }
            $response['graficas']['heatmap'][] = ['name' => $nombreDia, 'data' => $dataDia];
        }

        arsort($paquetesMap);
        $k = 0;
        foreach ($paquetesMap as $label => $cant) {
            if ($k++ >= 10) break;
            $response['graficas']['paquetes'][] = ['name' => $label, 'data' => $cant];
        }

        return ['success' => true, 'data' => $response];
    }
}
