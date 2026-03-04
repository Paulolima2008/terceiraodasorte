<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class RateLimiter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function ensureLoginAllowed(string $identifier, string $ipAddress): void
    {
        $record = $this->findRecord($identifier, $ipAddress);
        if (!$record) {
            return;
        }

        if (!empty($record['blocked_until']) && strtotime((string) $record['blocked_until']) > time()) {
            throw new RuntimeException('Muitas tentativas de login. Aguarde alguns minutos e tente novamente.');
        }
    }

    public function hit(string $identifier, string $ipAddress): void
    {
        $record = $this->findRecord($identifier, $ipAddress);
        $now = new DateTimeImmutable('now');
        $blockedUntil = null;

        if (!$record) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO admin_login_attempts (identifier_key, ip_address, attempts, blocked_until, last_attempt_at, created_at, updated_at)
                 VALUES (:identifier_key, :ip_address, 1, NULL, NOW(), NOW(), NOW())'
            );
            $stmt->execute([
                'identifier_key' => $identifier,
                'ip_address' => $ipAddress,
            ]);
            return;
        }

        $attempts = (int) $record['attempts'] + 1;
        if ($attempts >= ADMIN_RATE_LIMIT_MAX_ATTEMPTS) {
            $blockedUntil = $now->add(new DateInterval('PT' . ADMIN_RATE_LIMIT_WINDOW_MINUTES . 'M'))->format('Y-m-d H:i:s');
            $attempts = 0;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE admin_login_attempts
             SET attempts = :attempts, blocked_until = :blocked_until, last_attempt_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
            'id' => (int) $record['id'],
        ]);
    }

    public function clear(string $identifier, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_login_attempts WHERE identifier_key = :identifier_key AND ip_address = :ip_address');
        $stmt->execute([
            'identifier_key' => $identifier,
            'ip_address' => $ipAddress,
        ]);
    }

    private function findRecord(string $identifier, string $ipAddress): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, attempts, blocked_until
             FROM admin_login_attempts
             WHERE identifier_key = :identifier_key AND ip_address = :ip_address
             LIMIT 1'
        );
        $stmt->execute([
            'identifier_key' => $identifier,
            'ip_address' => $ipAddress,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}
