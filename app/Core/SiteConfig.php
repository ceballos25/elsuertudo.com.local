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
        $logo = self::get('site_logo');
        if ($logo && !str_starts_with($logo, 'http')) {
            return BASE_URL . '/' . ltrim($logo, '/');
        }
        return $logo ?: ASSETS_URL . '/images/logos/logo.jpg';
    }

    public static function favicon(): string
    {
        $icon = self::get('site_favicon');
        if ($icon && !str_starts_with($icon, 'http')) {
            return BASE_URL . '/' . ltrim($icon, '/');
        }
        return $icon ?: ASSETS_URL . '/images/logos/logo.ico';
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
}
