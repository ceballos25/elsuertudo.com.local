<?php
require_once __DIR__ . '/config/config.php';

use App\Core\Auth;

Auth::logout();
header('Location: ' . BASE_URL . '/dash.php');
exit;
