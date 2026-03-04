<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AuditLogService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function log(?int $adminUserId, string $module, string $action, array $context = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_audit_logs (admin_user_id, module_name, action_name, ip_address, user_agent, context_json, created_at)
             VALUES (:admin_user_id, :module_name, :action_name, :ip_address, :user_agent, :context_json, NOW())'
        );
        $stmt->execute([
            'admin_user_id' => $adminUserId,
            'module_name' => $module,
            'action_name' => $action,
            'ip_address' => $this->clientIp(),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255),
            'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function clientIp(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}
