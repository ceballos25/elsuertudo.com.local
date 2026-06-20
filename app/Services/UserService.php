<?php

namespace App\Services;

use App\Core\Auth;
use App\Models\Admin;

class UserService
{
    private Admin $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
    }

    public function list(): array
    {
        $users = $this->adminModel->listForManagement();

        foreach ($users as $user) {
            $user->rol_admin = Auth::normalizeRole($user->rol_admin ?? null);
        }

        return ['success' => true, 'data' => $users];
    }

    public function create(array $data): array
    {
        $email    = trim($data['email_admin'] ?? '');
        $password = (string) ($data['password_admin'] ?? '');
        $roleInput = trim((string) ($data['rol_admin'] ?? 'vendedor'));
        $role = ($roleInput === 'administrador') ? 'administrador' : 'vendedor';

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Email y contraseña son obligatorios'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        if ($this->adminModel->emailExists($email)) {
            return ['success' => false, 'message' => 'Ya existe un usuario con ese nombre o email'];
        }

        try {
            $id = $this->adminModel->createAdmin(
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            );
        } catch (\Throwable $e) {
            error_log('UserService::create - ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo crear el usuario. Verifica que el nombre no esté duplicado.'];
        }

        if ($id <= 0) {
            return ['success' => false, 'message' => 'No se pudo crear el usuario'];
        }

        return [
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data'    => ['id_admin' => $id],
        ];
    }

    public function update(array $data): array
    {
        $id    = (int) ($data['id_admin'] ?? 0);
        $email = trim($data['email_admin'] ?? '');
        $roleInput = trim((string) ($data['rol_admin'] ?? 'vendedor'));
        $role = ($roleInput === 'administrador') ? 'administrador' : 'vendedor';

        if ($id <= 0 || $email === '') {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        $user = $this->adminModel->findActiveById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        if ($this->adminModel->emailExists($email, $id)) {
            return ['success' => false, 'message' => 'Ya existe otro usuario con ese email'];
        }

        if ($id === Auth::userId() && $role !== 'administrador') {
            return ['success' => false, 'message' => 'No puedes quitarte el rol de administrador a ti mismo'];
        }

        if (Auth::normalizeRole($user->rol_admin ?? null) === 'administrador' && $role === 'vendedor') {
            $admins = $this->adminModel->countActiveAdminsByRole('administrador');
            if ($admins <= 1) {
                return ['success' => false, 'message' => 'Debe existir al menos un administrador activo'];
            }
        }

        $ok = $this->adminModel->updateAdmin($id, [
            'email_admin' => $email,
            'rol_admin'   => $role,
        ]);

        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo actualizar el usuario'];
        }

        if ($id === Auth::userId()) {
            $_SESSION['email_admin'] = $email;
            $_SESSION['user_role']   = $role;
        }

        return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
    }

    public function changePassword(array $data): array
    {
        $id       = (int) ($data['id_admin'] ?? 0);
        $password = (string) ($data['password_admin'] ?? '');

        if ($id <= 0 || $password === '') {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        $user = $this->adminModel->findActiveById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        $ok = $this->adminModel->setPassword($id, password_hash($password, PASSWORD_DEFAULT));
        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo cambiar la contraseña'];
        }

        return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
    }

    public function changeOwnPassword(array $data): array
    {
        $current  = (string) ($data['password_actual'] ?? '');
        $password = (string) ($data['password_nueva'] ?? '');

        if ($current === '' || $password === '') {
            return ['success' => false, 'message' => 'Completa la contraseña actual y la nueva'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
        }

        $userId = Auth::userId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Sesión no válida'];
        }

        $user = $this->adminModel->findActiveById($userId);
        if (!$user || !password_verify($current, $user->password_admin)) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
        }

        $ok = $this->adminModel->setPassword($userId, password_hash($password, PASSWORD_DEFAULT));
        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo cambiar la contraseña'];
        }

        return ['success' => true, 'message' => 'Tu contraseña fue actualizada correctamente'];
    }

    public function deactivate(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Usuario no válido'];
        }

        if ($id === Auth::userId()) {
            return ['success' => false, 'message' => 'No puedes desactivar tu propia cuenta'];
        }

        $user = $this->adminModel->findActiveById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado o ya inactivo'];
        }

        if (Auth::normalizeRole($user->rol_admin ?? null) === 'administrador') {
            $admins = $this->adminModel->countActiveAdminsByRole('administrador');
            if ($admins <= 1) {
                return ['success' => false, 'message' => 'No puedes desactivar al único administrador'];
            }
        }

        $ok = $this->adminModel->deactivate($id);
        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo desactivar el usuario'];
        }

        return ['success' => true, 'message' => 'Usuario desactivado correctamente'];
    }
}
