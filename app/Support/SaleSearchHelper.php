<?php

namespace App\Support;

/**
 * Búsqueda en ventas y números vendidos: nombre, celular, código o número exacto.
 */
class SaleSearchHelper
{
    /**
     * Filtro para listado de ventas (una fila por venta).
     *
     * @return array{sql: string, params: array}
     */
    public static function forSalesQuery(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return ['sql' => '', 'params' => []];
        }

        if (self::isDigitsOnly($search)) {
            return self::digitsOnlyForSales($search);
        }

        return self::textSearchForSales($search);
    }

    /**
     * Filtro para reporte de números vendidos (una fila por ticket).
     *
     * @return array{sql: string, params: array}
     */
    public static function forSoldTicketsQuery(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return ['sql' => '', 'params' => []];
        }

        if (self::isDigitsOnly($search)) {
            return self::digitsOnlyForTickets($search);
        }

        return self::textSearchForTickets($search);
    }

    private static function isDigitsOnly(string $search): bool
    {
        return ctype_digit($search);
    }

    /**
     * @return array{parts: string[], params: array}
     */
    private static function ticketExactExistsParts(string $search): array
    {
        $parts = [];
        $params = [];

        $parts[] = 'EXISTS (
            SELECT 1 FROM tickets t
            WHERE t.id_sale_ticket = s.id_sale
              AND t.number_ticket = ?
        )';
        $params[] = $search;

        if (strlen($search) === 1) {
            $padded = str_pad($search, 2, '0', STR_PAD_LEFT);
            if ($padded !== $search) {
                $parts[] = 'EXISTS (
                    SELECT 1 FROM tickets t
                    WHERE t.id_sale_ticket = s.id_sale
                      AND t.number_ticket = ?
                )';
                $params[] = $padded;
            }
        }

        return ['parts' => $parts, 'params' => $params];
    }

    /**
     * @return array{parts: string[], params: array}
     */
    private static function ticketExactRowParts(string $search, string $ticketAlias = 't'): array
    {
        $parts = ["{$ticketAlias}.number_ticket = ?"];
        $params = [$search];

        if (strlen($search) === 1) {
            $padded = str_pad($search, 2, '0', STR_PAD_LEFT);
            if ($padded !== $search) {
                $parts[] = "{$ticketAlias}.number_ticket = ?";
                $params[] = $padded;
            }
        }

        return ['parts' => $parts, 'params' => $params];
    }

    private static function isRaffleTicketNumberSearch(string $search): bool
    {
        return strlen($search) <= 2;
    }

    private static function raffleNumberOnlyForSales(string $search): array
    {
        $ticket = self::ticketExactExistsParts($search);

        return [
            'sql' => ' AND (' . implode(' OR ', $ticket['parts']) . ')',
            'params' => $ticket['params'],
        ];
    }

    private static function raffleNumberOnlyForTickets(string $search): array
    {
        $ticket = self::ticketExactRowParts($search);

        return [
            'sql' => ' AND (' . implode(' OR ', $ticket['parts']) . ')',
            'params' => $ticket['params'],
        ];
    }

    private static function digitsOnlyForSales(string $search): array
    {
        $len = strlen($search);

        if (self::isRaffleTicketNumberSearch($search)) {
            return self::raffleNumberOnlyForSales($search);
        }

        if ($len <= 6) {
            $ticket = self::ticketExactExistsParts($search);
            $parts = $ticket['parts'];
            $params = $ticket['params'];

            if ((int) $search > 0) {
                $parts[] = 's.id_sale = ?';
                $params[] = (int) $search;
            }

            return [
                'sql' => ' AND (' . implode(' OR ', $parts) . ')',
                'params' => $params,
            ];
        }

        if ($len === 10) {
            return [
                'sql' => ' AND c.phone_customer = ?',
                'params' => [$search],
            ];
        }

        $ticket = self::ticketExactExistsParts($search);

        return [
            'sql' => ' AND (
                c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
                OR ' . implode(' OR ', $ticket['parts']) . '
            )',
            'params' => array_merge(
                ["%{$search}%", "%{$search}%"],
                $ticket['params']
            ),
        ];
    }

    private static function digitsOnlyForTickets(string $search): array
    {
        $len = strlen($search);

        if (self::isRaffleTicketNumberSearch($search)) {
            return self::raffleNumberOnlyForTickets($search);
        }

        if ($len <= 6) {
            $ticket = self::ticketExactRowParts($search);
            $parts = $ticket['parts'];
            $params = $ticket['params'];

            if ((int) $search > 0) {
                $parts[] = 's.id_sale = ?';
                $params[] = (int) $search;
            }

            return [
                'sql' => ' AND (' . implode(' OR ', $parts) . ')',
                'params' => $params,
            ];
        }

        if ($len === 10) {
            return [
                'sql' => ' AND c.phone_customer = ?',
                'params' => [$search],
            ];
        }

        $ticket = self::ticketExactRowParts($search);

        return [
            'sql' => ' AND (
                c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
                OR ' . implode(' OR ', $ticket['parts']) . '
            )',
            'params' => array_merge(
                ["%{$search}%", "%{$search}%"],
                $ticket['params']
            ),
        ];
    }

    private static function textSearchForSales(string $search): array
    {
        $phoneDigits = preg_replace('/\D/', '', $search);
        $like = "%{$search}%";
        $phoneLike = '%' . ($phoneDigits !== '' ? $phoneDigits : $search) . '%';

        return [
            'sql' => ' AND (
                c.name_customer LIKE ?
                OR c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
            )',
            'params' => [$like, $phoneLike, $like],
        ];
    }

    private static function textSearchForTickets(string $search): array
    {
        $phoneDigits = preg_replace('/\D/', '', $search);
        $like = "%{$search}%";
        $phoneLike = '%' . ($phoneDigits !== '' ? $phoneDigits : $search) . '%';

        return [
            'sql' => ' AND (
                c.name_customer LIKE ?
                OR c.phone_customer LIKE ?
                OR s.code_sale LIKE ?
            )',
            'params' => [$like, $phoneLike, $like],
        ];
    }
}
