<?php

/**
 * Bootstrap de la aplicación MVC
 */
require_once __DIR__ . '/envLoader.php';

/**
 * .env-el siempre un nivel arriba de la raíz del proyecto:
 *   proyecto/config/config.php  →  ../../.env-el
 * Ejemplo prod: /home/cliente/public_html/  →  /home/cliente/.env-el
 */
$envPath = realpath(__DIR__ . '/../../.env-el');

if ($envPath === false || !is_readable($envPath)) {
    $projectRoot = realpath(__DIR__ . '/..');
    throw new Exception(
        'No se encontró .env-el. Debe estar en: '
        . dirname($projectRoot) . '/.env-el'
    );
}

$envLoader = new EnvLoader($envPath);
$envLoader->load();

define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('DS', DIRECTORY_SEPARATOR);
define('BASE_URL', rtrim(env('SITE_URL', ''), '/'));
define('ASSETS_URL', BASE_URL . '/assets');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('SITE_NAME', env('SITE_NAME', 'Mi Rifa'));
define('APP_ENV', env('APP_ENV', 'production'));
define('DEBUG_MODE', (bool) env('DEBUG_MODE', false));

define('SALE_PREFIX', env('SALE_PREFIX', 'EDTS-'));
define('SALE_PAD', (int) (env('SALE_PAD', 6)));

date_default_timezone_set(env('TIMEZONE', 'America/Bogota'));

if (env('DISPLAY_ERRORS', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

ini_set('session.cookie_httponly', env('SESSION_COOKIE_HTTPONLY', true) ? '1' : '0');
ini_set('session.cookie_secure', env('SESSION_COOKIE_SECURE', false) ? '1' : '0');
ini_set('session.cookie_samesite', env('SESSION_COOKIE_SAMESITE', 'Lax'));
ini_set('session.cookie_lifetime', (string) env('SESSION_LIFETIME', 28800));
ini_set('session.gc_maxlifetime', (string) env('SESSION_LIFETIME', 28800));

if (env('SESSION_AUTO_START', true) && session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME', 'SAAS_RIFA'));
    session_start();
}

require_once ROOT_PATH . '/vendor/autoload.php';

use App\Core\Auth;

/**
 * Protección de rutas admin (vistas PHP)
 */
if (php_sapi_name() !== 'cli') {
    $currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    $publicPages = ['index.php', 'dash.php', 'logout.php', 'login.php'];

    $isAjax = str_contains($_SERVER['SCRIPT_FILENAME'] ?? '', '/ajax/api.php');
    $isPublicPage = in_array($currentScript, $publicPages) || $isAjax;

    if (!$isPublicPage) {
        if (!Auth::check() || !Auth::refreshSession()) {
            header('Location: ' . BASE_URL . '/dash.php');
            exit;
        }

        $adminOnlyPages = ['settings.php', 'usuarios.php'];
        if (in_array($currentScript, $adminOnlyPages, true) && !Auth::isAdmin()) {
            header('Location: ' . BASE_URL . '/front/dashboard.php');
            exit;
        }
    }
}
