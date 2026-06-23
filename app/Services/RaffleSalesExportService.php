<?php

namespace App\Services;

use App\Models\Raffle;
use App\Models\Ticket;

class RaffleSalesExportService
{
    private Raffle $raffleModel;
    private Ticket $ticketModel;

    public function __construct()
    {
        $this->raffleModel = new Raffle();
        $this->ticketModel = new Ticket();
    }

    public function streamExcel(int $idRaffle): void
    {
        if ($idRaffle <= 0) {
            http_response_code(400);
            exit('Rifa no válida');
        }

        $raffle = $this->raffleModel->find($idRaffle);
        if (!$raffle) {
            http_response_code(404);
            exit('Rifa no encontrada');
        }

        $rows = $this->ticketModel->getSoldReport(['id_raffle' => $idRaffle]);
        $filename = $this->buildFilename($raffle);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        echo $this->buildSpreadsheetXml($rows);
    }

    private function buildFilename(object $raffle): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string) ($raffle->title_raffle ?? 'rifa'));
        $slug = trim(strtolower($slug), '-') ?: 'rifa';

        return 'ventas-' . $slug . '-' . (int) $raffle->id_raffle . '.xls';
    }

    private function buildSpreadsheetXml(array $rows): string
    {
        $headers = [
            'NOMBRE_CLIENTE',
            'CELULAR_CLIENTE',
            'RIFA',
            'NUMERO',
            'TOTAL',
            'DIA',
            'MES',
            'AÑO',
            'HORA',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Ventas"><Table>' . "\n";

        $xml .= '<Row>';
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . $this->xmlEsc($header) . '</Data></Cell>';
        }
        $xml .= '</Row>' . "\n";

        $tz = new \DateTimeZone('America/Bogota');

        foreach ($rows as $row) {
            $dt = $this->parseSaleDate((string) ($row->date_created_sale ?? ''), $tz);
            $total = (float) ($row->price_raffle ?? 0);

            $cells = [
                (string) ($row->name_customer ?? ''),
                (string) ($row->phone_customer ?? ''),
                (string) ($row->title_raffle ?? ''),
                (string) ($row->number_ticket ?? ''),
                (string) (int) round($total),
                $dt ? $dt->format('d') : '',
                $dt ? $dt->format('m') : '',
                $dt ? $dt->format('Y') : '',
                $dt ? $dt->format('H:i:s') : '',
            ];

            $xml .= '<Row>';
            foreach ($cells as $index => $value) {
                $type = ($index === 4 && is_numeric($value)) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . $this->xmlEsc((string) $value) . '</Data></Cell>';
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }

    private function parseSaleDate(string $raw, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($raw))->setTimezone($tz);
        } catch (\Exception) {
            return null;
        }
    }

    private function xmlEsc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
