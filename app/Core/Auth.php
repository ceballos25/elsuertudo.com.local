<?php

namespace App\Core;

use App\Models\Admin;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): string
    {
        $role = trim((string) ($_SESSION['user_role'] ?? ''));
        return self::normalizeRole($role !== '' ? $role : null);
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'administrador';
    }

    public static function isVendedor(): bool
    {
        return self::role() === 'vendedor';
    }

    public static function email(): string
    {
        return (string) ($_SESSION['email_admin'] ?? '');
    }

    public static function normalizeRole(?string $role): string
    {
        $role = trim((string) $role);
        if ($role === 'administrador' || $role === 'vendedor') {
            return $role;
        }
        // Rol desconocido o vacío: mínimo privilegio
        return 'vendedor';
    }

    public static function login(array $admin): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']     = $admin['id_admin'];
        $_SESSION['user_role']   = self::normalizeRole($admin['rol_admin'] ?? null);
        $_SESSION['email_admin'] = $admin['email_admin'];
        $_SESSION['login_at']    = time();
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            if (self::isAjaxRequest()) {
                Response::json([
                    'success' => false,
                    'message' => 'Se requieren permisos de administrador',
                ], 403);
            }
            header('Location: ' . BASE_URL . '/front/dashboard.php');
            exit;
        }
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public static function refreshSession(): bool
    {
        if (!self::check()) {
            return false;
        }

        $admin = (new Admin())->findActiveById((int) $_SESSION['user_id']);
        if (!$admin) {
            self::invalidateSession();
            return false;
        }

        $_SESSION['user_role']   = self::normalizeRole($admin->rol_admin ?? null);
        $_SESSION['email_admin'] = $admin->email_admin;

        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::check() || !self::refreshSession()) {
            if (self::isAjaxRequest()) {
                Response::json(['success' => false, 'message' => 'No autenticado'], 401);
            }
            header('Location: ' . BASE_URL . '/dash.php');
            exit;
        }
    }

    private static function invalidateSession(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || str_contains($_SERVER['REQUEST_URI'] ?? '', '/ajax/api.php');
    }
}
