<?php

namespace App\Support;

use App\Models\Ticket;

/**
 * Validaciones compartidas para selección de números (venta, reserva, participación gratis).
 */
class ParticipationRules
{
    public static function parseTicketIds(mixed $raw): array
    {
        $tickets = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (!is_array($tickets)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $tickets)));
    }

    /**
     * @return array{ok: bool, message?: string, occupied?: string[]}
     */
    public static function assertSelection(
        Ticket $ticketModel,
        array $ticketIds,
        int $idRaffle,
        int $maxTickets
    ): array {
        if ($ticketIds === []) {
            return ['ok' => false, 'message' => 'No se enviaron números válidos'];
        }

        if (count($ticketIds) > $maxTickets) {
            return [
                'ok'      => false,
                'message' => $maxTickets === 1
                    ? 'Solo puedes elegir 1 número en esta rifa'
                    : "Máximo {$maxTickets} números por pedido",
            ];
        }

        if ($ticketModel->countInRaffle($ticketIds, $idRaffle) !== count($ticketIds)) {
            return ['ok' => false, 'message' => 'Uno o más números no pertenecen a esta rifa'];
        }

        $occupied = $ticketModel->getOccupiedInRaffle($ticketIds, $idRaffle);
        if ($occupied !== []) {
            $nums = array_map(fn($t) => $t->number_ticket, $occupied);

            return [
                'ok'        => false,
                'message'   => 'Los siguientes números no están disponibles: ' . implode(', ', $nums),
                'occupied'  => $nums,
                'ticket_ids'=> array_map(fn($t) => (int) $t->id_ticket, $occupied),
            ];
        }

        return ['ok' => true];
    }
}
