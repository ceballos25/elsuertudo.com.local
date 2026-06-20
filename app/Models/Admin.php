<?php

namespace App\Models;

use App\Core\Model;

class Admin extends Model
{
    protected string $table = 'admins';
    protected string $primaryKey = 'id_admin';

    public function findByEmail(string $email): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM admins WHERE email_admin = ? AND status_admin = 1 LIMIT 1",
            [trim($email)]
        );
    }

    public function findById(int $id): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM admins WHERE id_admin = ? LIMIT 1",
            [$id]
        );
    }

    public function findActiveById(int $id): ?object
    {
        return $this->db->fetchOne(
            "SELECT * FROM admins WHERE id_admin = ? AND status_admin = 1 LIMIT 1",
            [$id]
        );
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT id_admin FROM admins WHERE email_admin = ?";
        $params = [trim($email)];

        if ($excludeId !== null) {
            $sql .= " AND id_admin != ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";

        return (bool) $this->db->fetchOne($sql, $params);
    }

    public function listAll(): array
    {
        return $this->db->fetchAll(
            "SELECT id_admin, email_admin, rol_admin, status_admin, date_created_admin, date_updated_admin
             FROM admins
             WHERE status_admin = 1
             ORDER BY email_admin ASC"
        );
    }

    public function listForManagement(): array
    {
        return $this->db->fetchAll(
            "SELECT id_admin, email_admin, rol_admin, status_admin, date_created_admin, date_updated_admin
             FROM admins
             ORDER BY status_admin DESC, email_admin ASC"
        );
    }

    public function createAdmin(string $email, string $passwordHash, string $role): int
    {
        return (int) $this->db->insert('admins', [
            'email_admin'          => trim($email),
            'password_admin'       => $passwordHash,
            'rol_admin'            => $role,
            'status_admin'         => 1,
            'date_created_admin'   => date('Y-m-d'),
        ]);
    }

    public function updateAdmin(int $id, array $data): bool
    {
        return $this->db->update('admins', $data, 'id_admin = ?', [$id]) > 0;
    }

    public function setPassword(int $id, string $passwordHash): bool
    {
        return $this->updateAdmin($id, ['password_admin' => $passwordHash]);
    }

    public function deactivate(int $id): bool
    {
        return $this->updateAdmin($id, ['status_admin' => 0]);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM admins WHERE status_admin = 1"
        )->total;
    }

    public function countActiveAdminsByRole(string $role): int
    {
        return (int) $this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM admins WHERE status_admin = 1 AND rol_admin = ?",
            [$role]
        )->total;
    }
}
