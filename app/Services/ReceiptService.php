<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Ticket;

use App\Core\SiteConfig;
use App\Core\ThemeConfig;

class ReceiptService
{
    private Sale $saleModel;
    private Ticket $ticketModel;

    public function __construct()
    {
        $this->saleModel = new Sale();
        $this->ticketModel = new Ticket();
    }

    public function generate(int $idSale): string
    {
        $venta = $this->saleModel->findWithDetails($idSale);
        if (!$venta) {
            return $this->fallbackHtml($idSale, '', 0, 0);
        }

        $tickets = $this->ticketModel->getBySale($idSale);
        return $this->generateFromData($venta, $tickets);
    }

    public function generateFromData(object $venta, array $tickets): string
    {
        $templatePath = ROOT_PATH . '/includes/template-ticket.php';
        if (!file_exists($templatePath)) {
            return $this->fallbackHtml(
                $venta->id_sale ?? 0,
                $venta->code_sale ?? '',
                $venta->total_sale ?? 0,
                $venta->quantity_sale ?? 0
            );
        }

        $fecha = date('d/m/Y h:i A');
        if (!empty($venta->date_created_sale)) {
            try {
                $dt = new \DateTime($venta->date_created_sale);
                $dt->setTimezone(new \DateTimeZone('America/Bogota'));
                $fecha = $dt->format('d/m/Y h:i A');
            } catch (\Exception $e) {}
        }

        $numerosHTML = '';
        $chipBg = ThemeConfig::get('raffle-color-ticket-chip-bg');
        $chipText = ThemeConfig::get('raffle-color-ticket-chip-text');
        $chipRadius = ThemeConfig::get('raffle-number-radius', '12px');
        foreach ($tickets as $t) {
            $num = is_object($t) ? $t->number_ticket : $t['number_ticket'];
            $numerosHTML .= '<span style="display:inline-block;margin:4px;padding:6px 12px;background:'
                . $this->esc($chipBg) . ';color:' . $this->esc($chipText)
                . ';border-radius:' . $this->esc($chipRadius) . ';font-weight:bold;">'
                . $this->esc($num) . '</span>';
        }

        $template = file_get_contents($templatePath);
        $reemplazos = [
            '{Logo}'                  => $this->safeLogoUrl(),
            '{Nombre Cliente}'        => $this->esc($venta->name_customer ?? '---'),
            '{TituloRifa}'            => $this->esc($venta->title_raffle ?? ''),
            '{ID}'                    => $this->esc($venta->id_sale ?? ''),
            '{Fecha}'                 => $this->esc($fecha),
            '{Cantidad}'              => $this->esc($venta->quantity_sale ?? count($tickets)),
            '{Codigo}'                => $this->esc($venta->code_sale ?? ''),
            '{NumerosHTML}'           => $numerosHTML,
            '{Total}'                 => '$' . number_format($venta->total_sale ?? 0, 0, ',', '.'),
            '{ColorTicketAccent}'     => $this->esc(ThemeConfig::get('raffle-color-ticket-accent')),
            '{ColorTicketText}'       => $this->esc(ThemeConfig::get('raffle-color-ticket-text')),
            '{ColorTicketShareBg}'    => $this->esc(ThemeConfig::get('raffle-color-ticket-share-bg')),
            '{ColorTicketShareBorder}' => $this->esc(ThemeConfig::get('raffle-color-ticket-share-border')),
            '{ColorTicketCodeBg}'     => $this->esc(ThemeConfig::get('raffle-color-ticket-code-bg')),
            '{ColorTicketCodeBorder}' => $this->esc(ThemeConfig::get('raffle-color-ticket-code-border')),
            '{ColorTicketNumbersBg}'  => $this->esc(ThemeConfig::get('raffle-color-ticket-numbers-bg')),
        ];

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $template);
    }

    private function fallbackHtml($id, $code, $total, $qty): string
    {
        return '<div>Venta #' . $this->esc($id) . ' - Código: ' . $this->esc($code)
            . ' - Total: $' . number_format($total, 0, ',', '.') . '</div>';
    }

    private function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function safeLogoUrl(): string
    {
        $logo = SiteConfig::logo();
        if ($logo === '') {
            $logo = rtrim((string) env('CDN_LOGOS_URL', 'https://cdn-el.elsuertudo.com.co/logos'), '/')
                . '/' . ltrim((string) env('BRAND_LOGO', 'logo.jpg'), '/');
        }

        if (preg_match('#^https?://#i', $logo) && filter_var($logo, FILTER_VALIDATE_URL)) {
            $inlined = $this->inlineLogoFromRemote($logo);
            if ($inlined !== null) {
                return $inlined;
            }
        }

        return $this->esc($logo);
    }

    private function inlineLogoFromRemote(string $url): ?string
    {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $bytes = @file_get_contents($url, false, $context);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            default => 'image/jpeg',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }
}
