<?php
require_once __DIR__ . '/../config/config.php';

use App\Services\AuthService;

$email = trim($_POST['email'] ?? '');
$pass  = trim($_POST['password'] ?? '');

$result = (new AuthService())->login($email, $pass);

if ($result['success']) {
    header('Location: ' . $result['redirect']);
    exit;
}

header('Location: ' . BASE_URL . '/dash.php?error=bad_credentials');
exit;
