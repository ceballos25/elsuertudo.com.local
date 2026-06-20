<?php

namespace App\Core;

/**
 * Lee los colores del tema desde assets/css/theme-base.css
 * para usarlos en PHP (ticket, comprobantes, etc.).
 */
class ThemeConfig
{
    private static ?array $vars = null;

    public static function get(string $name, string $default = ''): string
    {
        $all = self::all();
        $key = str_starts_with($name, '--') ? substr($name, 2) : $name;

        return $all[$key] ?? $default;
    }

    public static function all(): array
    {
        if (self::$vars === null) {
            self::$vars = self::loadFromCss();
        }

        return self::$vars;
    }

    private static function loadFromCss(): array
    {
        $path = ROOT_PATH . '/assets/css/theme-base.css';
        $vars = self::defaults();

        if (!is_readable($path)) {
            return $vars;
        }

        $css = file_get_contents($path);
        if (preg_match_all('/--raffle-(color-[\w-]+|number-radius)\s*:\s*([^;]+);/', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $vars['raffle-' . $match[1]] = trim($match[2]);
            }
        }

        return self::resolveVars($vars);
    }

    private static function resolveVars(array $vars): array
    {
        $resolved = $vars;

        for ($pass = 0; $pass < 6; $pass++) {
            foreach ($resolved as $key => $value) {
                if (preg_match('/^var\(--([^)]+)\)$/', trim($value), $ref)) {
                    $resolved[$key] = $resolved[$ref[1]] ?? $value;
                }
            }
        }

        return $resolved;
    }

    private static function defaults(): array
    {
        return [
            'raffle-color-primary' => '#198754',
            'raffle-color-primary-dark' => '#157347',
            'raffle-color-primary-light' => '#20c997',
            'raffle-color-primary-rgb' => '25, 135, 84',
            'raffle-color-primary-soft' => '#f0fdf4',
            'raffle-color-primary-muted' => 'rgba(25, 135, 84, 0.12)',
            'raffle-color-available-bg' => '#ffffff',
            'raffle-color-available-border' => '#198754',
            'raffle-color-selected-bg' => '#198754',
            'raffle-color-selected-text' => '#ffffff',
            'raffle-color-selected-border' => '#157347',
            'raffle-color-sold-bg' => '#f1f3f5',
            'raffle-color-sold-text' => '#868e96',
            'raffle-color-sold-border' => '#dee2e6',
            'raffle-color-ticket-accent' => '#19C37D',
            'raffle-color-ticket-text' => '#0f9d58',
            'raffle-color-ticket-chip-bg' => '#198754',
            'raffle-color-ticket-chip-text' => '#ffffff',
            'raffle-color-ticket-share-bg' => '#ecfdf5',
            'raffle-color-ticket-share-border' => '#bbf7d0',
            'raffle-color-ticket-code-bg' => '#f6fbf8',
            'raffle-color-ticket-code-border' => '#a7f3d0',
            'raffle-color-ticket-numbers-bg' => '#f8fffc',
            'raffle-number-radius' => '12px',
        ];
    }
}
