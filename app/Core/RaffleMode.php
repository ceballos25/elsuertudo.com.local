<?php

namespace App\Core;

/**
 * Reglas reutilizables según el modo de la rifa (paga / gratis).
 */
class RaffleMode
{
    public const PAYMENT_FREE = 'Gratis';
    public const PAYMENT_WEB  = 'Página Web';

    public static function isFree(?object $raffle): bool
    {
        return $raffle !== null && (int) ($raffle->is_free_raffle ?? 0) === 1;
    }

    public static function maxTicketsPerPublicOrder(?object $raffle): int
    {
        return self::isFree($raffle) ? 1 : 50;
    }

    public static function allowsZeroTotal(?object $raffle): bool
    {
        return self::isFree($raffle);
    }

    public static function publicPaymentMethod(?object $raffle): string
    {
        return self::isFree($raffle) ? self::PAYMENT_FREE : self::PAYMENT_WEB;
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalizeCreatePayload(array $data): array
    {
        $isFree = !empty($data['is_free_raffle']) && (int) $data['is_free_raffle'] === 1;

        return [
            'is_free_raffle' => $isFree ? 1 : 0,
            'price_raffle'   => $isFree ? 0 : ($data['price_raffle'] ?? 0),
        ];
    }
}
