<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdminUser
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, email, is_active, last_login_at, created_at FROM admin_users ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, password_hash, is_active, last_login_at, created_at FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, password_hash, is_active FROM admin_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (name, email, password_hash, is_active, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'name = :name',
            'email = :email',
            'is_active = :is_active',
        ];

        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ];

        if (!empty($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE admin_users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function markLogin(int $id, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_users SET last_login_at = NOW(), last_login_ip = :last_login_ip, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'last_login_ip' => $ipAddress,
        ]);
    }
}
