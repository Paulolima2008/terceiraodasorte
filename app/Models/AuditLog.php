<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AuditLog
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function latest(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.module_name, l.action_name, l.ip_address, l.created_at, u.name AS admin_name
             FROM admin_audit_logs l
             LEFT JOIN admin_users u ON u.id = l.admin_user_id
             ORDER BY l.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
