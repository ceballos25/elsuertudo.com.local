<?php

namespace App\Core;

use App\Models\Setting;

class SiteConfig
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = (new Setting())->asKeyValue();
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function name(): string
    {
        return self::get('site_name', SITE_NAME) ?: SITE_NAME;
    }

    public static function logo(): string
    {
        return self::resolveBrandAsset(
            self::get('site_logo'),
            env('BRAND_LOGO', 'logo.jpg'),
            env('CDN_LOGOS_URL', 'https://cdn-el.elsuertudo.com.co/logos')
        );
    }

    public static function favicon(): string
    {
        return self::resolveBrandAsset(
            self::get('site_favicon'),
            env('BRAND_FAVICON', 'logo.ico'),
            env('CDN_LOGOS_URL', 'https://cdn-el.elsuertudo.com.co/logos')
        );
    }

    public static function isActive(): bool
    {
        return (bool) (int) self::get('site_active', '1');
    }

    public static function whatsapp(): string
    {
        return self::get('whatsapp_line_main', '');
    }

    public static function whatsappSupport(): string
    {
        return self::get('whatsapp_line_support', '');
    }

    public static function instagram(): string
    {
        return self::get('instagram_url', '');
    }

    public static function facebook(): string
    {
        return self::get('facebook_url', '');
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Resuelve logo/favicon: URL absoluta, upload local o filename en CDN.
     */
    private static function resolveBrandAsset(?string $setting, string $envDefault, ?string $cdnBase): string
    {
        $value = trim((string) ($setting ?: $envDefault));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, 'uploads/')) {
            return BASE_URL . '/' . ltrim($value, '/');
        }

        $filename = basename($value);
        $cdnBase = rtrim((string) $cdnBase, '/');

        return $cdnBase !== '' ? "{$cdnBase}/{$filename}" : '';
    }
}
