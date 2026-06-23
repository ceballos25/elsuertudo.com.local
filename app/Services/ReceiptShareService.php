<?php

namespace App\Services;

use App\Models\Sale;

class ReceiptShareService
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const TTL_HOURS = 72;

    public function storeUpload(array $post, array $files): array
    {
        $file = $files['comprobante'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No se recibió la imagen del comprobante'];
        }

        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return ['success' => false, 'message' => 'La imagen es demasiado grande'];
        }

        $mime = $this->detectMime($file['tmp_name'] ?? '');
        if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return ['success' => false, 'message' => 'Formato de imagen no válido'];
        }

        $idSale = (int) ($post['id_sale'] ?? 0);
        if ($idSale > 0) {
            $sale = (new Sale())->findWithDetails($idSale);
            if (!$sale) {
                return ['success' => false, 'message' => 'Venta no encontrada'];
            }
        }

        $token = bin2hex(random_bytes(12));
        $dir = ROOT_PATH . '/storage/comprobantes';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'No se pudo preparar el almacenamiento'];
        }

        $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
        $path = $dir . '/' . $token . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'message' => 'Error al guardar la imagen'];
        }

        $this->cleanupOld($dir);

        $url = rtrim(BASE_URL, '/') . '/comprobante.php?t=' . $token . ($ext === 'jpg' ? '&e=jpg' : '');

        return [
            'success' => true,
            'url'     => $url,
            'token'   => $token,
        ];
    }

    private function detectMime(string $path): string
    {
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return '';
        }

        $mime = finfo_file($finfo, $path) ?: '';
        finfo_close($finfo);

        return $mime;
    }

    private function cleanupOld(string $dir): void
    {
        $maxAge = self::TTL_HOURS * 3600;
        $files = glob($dir . '/*') ?: [];

        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
                @unlink($file);
            }
        }
    }
}
