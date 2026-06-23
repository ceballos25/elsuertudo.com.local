<?php

require_once __DIR__ . '/config/config.php';

$t = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['t'] ?? ''));
if (strlen($t) !== 24) {
    http_response_code(404);
    exit('Comprobante no encontrado');
}

$ext = (($_GET['e'] ?? '') === 'jpg') ? 'jpg' : 'png';
$path = ROOT_PATH . '/storage/comprobantes/' . $t . '.' . $ext;

if (!is_file($path)) {
    http_response_code(404);
    exit('Comprobante no encontrado o expirado');
}

header('Content-Type: ' . ($ext === 'jpg' ? 'image/jpeg' : 'image/png'));
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline; filename="comprobante.' . $ext . '"');
readfile($path);
