<?php

namespace App\Services;

use App\Core\Auth;
use App\Models\Admin;

class AuthService
{
    private Admin $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
    }

    public function login(string $email, string $password): array
    {
        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Credenciales incompletas'];
        }

        $admin = $this->adminModel->findByEmail($email);
        if (!$admin) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }

        if (!password_verify($password, $admin->password_admin)) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }

        Auth::login((array) $admin);

        return ['success' => true, 'redirect' => BASE_URL . '/front/dashboard.php'];
    }
}
