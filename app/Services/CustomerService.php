<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_BLACKLIST = 0;

    public const PURCHASE_BLOCKED_MESSAGE =
        'Lo sentimos, estamos presentando problemas técnicos. Inténtalo de nuevo más tarde.';

    private Customer $model;

    public function __construct()
    {
        $this->model = new Customer();
    }

    public function findOrCreate(array $data): int
    {
        if (empty($data['phone_customer'])) {
            throw new \InvalidArgumentException('Teléfono del cliente obligatorio');
        }

        $phone = $this->normalizePhone(trim($data['phone_customer']));
        if ($phone === '') {
            throw new \InvalidArgumentException('Teléfono del cliente obligatorio');
        }

        $existing = $this->model->findByPhone($phone);

        if ($existing) {
            if ($this->isBlacklisted($existing)) {
                throw new \RuntimeException(self::PURCHASE_BLOCKED_MESSAGE);
            }
            return (int) $existing->id_customer;
        }

        if (empty($data['name_customer'])) {
            throw new \InvalidArgumentException('Nombre del cliente obligatorio');
        }

        return $this->model->create([
            'name_customer'   => trim($data['name_customer']),
            'phone_customer'  => $phone,
            'status_customer' => 1,
        ]);
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '57') && strlen($phone) === 12) {
            $phone = substr($phone, 2);
        }
        return $phone;
    }

    public function getById(int $id): ?object
    {
        return $this->model->find($id) ?: null;
    }

    public function findOrCreateFromSale(array $data): ?int
    {
        if (!empty($data['id_customer'])) {
            $existing = $this->model->find((int) $data['id_customer']);
            if (!$existing || $this->isBlacklisted($existing)) {
                return null;
            }
            return (int) $existing->id_customer;
        }

        $phone = $this->normalizePhone($data['phone_customer'] ?? '');
        $existing = $this->model->findByPhone($phone);

        if ($existing) {
            if ($this->isBlacklisted($existing)) {
                return null;
            }
            return (int) $existing->id_customer;
        }

        return $this->model->create([
            'name_customer'   => ucwords(strtolower($data['name_customer'])),
            'phone_customer'  => $phone,
            'status_customer' => 1,
        ]);
    }

    public function findByPhone(array $data): array
    {
        $phone = $this->normalizePhone($data['phone_customer'] ?? '');

        if (strlen($phone) !== 10) {
            return ['success' => false, 'message' => 'El celular debe tener 10 dígitos'];
        }

        $existing = $this->model->findByPhone($phone);

        if ($existing) {
            return [
                'success' => true,
                'found'   => true,
                'data'    => [
                    'id_customer'    => (int) $existing->id_customer,
                    'name_customer'  => $existing->name_customer,
                    'phone_customer' => $existing->phone_customer,
                ],
            ];
        }

        return ['success' => true, 'found' => false];
    }

    public function list(array $filters = []): array
    {
        $search = trim($filters['search'] ?? '');
        $status = isset($filters['status']) && $filters['status'] !== ''
            ? (int) $filters['status']
            : null;

        return [
            'success' => true,
            'data'    => $this->model->search($search, $status),
        ];
    }

    public function create(array $data): array
    {
        if (empty($data['name_customer']) || empty($data['phone_customer'])) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        $id = $this->model->create([
            'name_customer'   => trim($data['name_customer']),
            'phone_customer'  => trim($data['phone_customer']),
            'status_customer' => 1,
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Cliente creado correctamente'];
    }

    public function update(array $data): array
    {
        if (empty($data['id_customer'])) {
            return ['success' => false, 'message' => 'ID de cliente obligatorio'];
        }

        $this->model->update((int) $data['id_customer'], [
            'name_customer'   => trim($data['name_customer']),
            'phone_customer'  => trim($data['phone_customer']),
            'status_customer' => (int) $data['status_customer'],
        ]);

        return ['success' => true, 'message' => 'Cliente actualizado correctamente'];
    }

    public function isBlacklisted(?object $customer): bool
    {
        return $customer !== null && (int) ($customer->status_customer ?? self::STATUS_ACTIVE) !== self::STATUS_ACTIVE;
    }

    /**
     * @return array{success: false, message: string}|null
     */
    public function assertCanPurchase(array $data): ?array
    {
        if (!empty($data['id_customer'])) {
            $existing = $this->model->find((int) $data['id_customer']);
            if ($this->isBlacklisted($existing)) {
                return $this->purchaseBlockedResponse();
            }
            return null;
        }

        $phone = $this->normalizePhone($data['phone_customer'] ?? '');
        if ($phone === '') {
            return null;
        }

        $existing = $this->model->findByPhone($phone);
        if ($this->isBlacklisted($existing)) {
            return $this->purchaseBlockedResponse();
        }

        return null;
    }

    public function toggleBlacklist(int $idCustomer): array
    {
        if ($idCustomer <= 0) {
            return ['success' => false, 'message' => 'Cliente no válido'];
        }

        $customer = $this->model->find($idCustomer);
        if (!$customer) {
            return ['success' => false, 'message' => 'Cliente no encontrado'];
        }

        $newStatus = (int) $customer->status_customer === self::STATUS_ACTIVE
            ? self::STATUS_BLACKLIST
            : self::STATUS_ACTIVE;

        $this->model->update($idCustomer, ['status_customer' => $newStatus]);

        return [
            'success'         => true,
            'message'         => $newStatus === self::STATUS_BLACKLIST
                ? 'Cliente agregado a lista negra'
                : 'Cliente quitado de lista negra',
            'status_customer' => $newStatus,
        ];
    }

    /**
     * @return array{success: false, message: string}
     */
    private function purchaseBlockedResponse(): array
    {
        return [
            'success' => false,
            'message' => self::PURCHASE_BLOCKED_MESSAGE,
        ];
    }

    public function delete(array $data): array
    {
        if (empty($data['id_customer'])) {
            return ['success' => false];
        }

        $this->model->delete((int) $data['id_customer']);
        return ['success' => true];
    }
}
