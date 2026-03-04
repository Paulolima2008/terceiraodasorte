<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PermissionService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function allowed(int $adminUserId, string $permissionKey): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM admin_user_permissions up
             INNER JOIN admin_permissions p ON p.id = up.permission_id
             WHERE up.admin_user_id = :admin_user_id AND p.permission_key = :permission_key
             LIMIT 1'
        );
        $stmt->execute([
            'admin_user_id' => $adminUserId,
            'permission_key' => $permissionKey,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, permission_key, permission_label, module_name FROM admin_permissions ORDER BY module_name, permission_label')->fetchAll();
    }

    public function userPermissionIds(int $adminUserId): array
    {
        $stmt = $this->pdo->prepare('SELECT permission_id FROM admin_user_permissions WHERE admin_user_id = :admin_user_id');
        $stmt->execute(['admin_user_id' => $adminUserId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function sync(int $adminUserId, array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM admin_user_permissions WHERE admin_user_id = :admin_user_id');
            $delete->execute(['admin_user_id' => $adminUserId]);

            if ($permissionIds !== []) {
                $insert = $this->pdo->prepare(
                    'INSERT INTO admin_user_permissions (admin_user_id, permission_id, created_at)
                     VALUES (:admin_user_id, :permission_id, NOW())'
                );
                foreach ($permissionIds as $permissionId) {
                    $insert->execute([
                        'admin_user_id' => $adminUserId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
