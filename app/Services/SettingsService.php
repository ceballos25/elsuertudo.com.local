<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\SiteConfig;
use App\Models\Setting;

class SettingsService
{
    private const PUBLIC_KEYS = [
        'site_name',
        'site_logo',
        'site_favicon',
        'whatsapp_line_main',
        'whatsapp_line_support',
        'facebook_url',
        'instagram_url',
        'site_active',
    ];

    private Setting $model;

    public function __construct()
    {
        $this->model = new Setting();
    }

    public function getAll(): array
    {
        if (Auth::check()) {
            return ['success' => true, 'data' => $this->model->getAll()];
        }

        return $this->getPublic();
    }

    public function getPublic(): array
    {
        $all = $this->model->getAll();
        $filtered = array_values(array_filter(
            $all,
            fn($s) => in_array($s->key_setting, self::PUBLIC_KEYS, true)
        ));

        return ['success' => true, 'data' => $filtered];
    }

    public function update(array $data, array $files = []): array
    {
        $id = (int) ($data['id_setting'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        $setting = $this->model->find($id);
        if (!$setting) {
            return ['success' => false, 'message' => 'Configuración no encontrada'];
        }

        if (!empty($files['file']['tmp_name'])) {
            $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'svg'];
            if (!in_array(strtolower($ext), $allowed)) {
                return ['success' => false, 'message' => 'Formato de imagen no permitido'];
            }

            $name = 'setting_' . time() . '.' . $ext;
            $dir = ROOT_PATH . '/uploads/settings';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $path = 'uploads/settings/' . $name;
            if (!move_uploaded_file($files['file']['tmp_name'], ROOT_PATH . '/' . $path)) {
                return ['success' => false, 'message' => 'Error al subir archivo'];
            }

            $payload = ['value_setting' => $path, 'extra_setting' => $ext];
        } else {
            $payload = ['value_setting' => $data['value_setting'] ?? ''];
        }

        $this->model->update($id, $payload);
        SiteConfig::clearCache();

        return ['success' => true, 'message' => 'Configuración actualizada'];
    }
}
