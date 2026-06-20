<?php

namespace App\Core;

/**
 * Permisos por rol.
 * Vendedor: operación diaria (vender, reservas, clientes, consultas).
 * Administrador: acceso completo.
 */
class Permissions
{
    /** Acciones que solo puede ejecutar un administrador */
    private const ADMIN_ONLY = [
        'ventas' => [
            'cancelar_venta',
            'cambiar_cliente',
            'liberar_numeros',
        ],
        'rifas' => [
            'crear',
            'actualizar',
            'eliminar',
            'reutilizar',
        ],
        'numeros' => [
            'cambiar_estado',
        ],
        'settings' => [
            'actualizar',
        ],
        'reservations' => [
            'liberar_reservas_masivo',
        ],
        'usuarios' => [
            'obtener',
            'crear',
            'actualizar',
            'desactivar',
            'cambiar_password',
        ],
    ];

    public static function requiresAdmin(string $module, string $action): bool
    {
        return in_array($action, self::ADMIN_ONLY[$module] ?? [], true);
    }

    public static function enforce(string $module, string $action): void
    {
        if (self::requiresAdmin($module, $action) && !Auth::isAdmin()) {
            Response::json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción',
            ], 403);
        }
    }
}
