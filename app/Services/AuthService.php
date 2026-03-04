<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AdminUser;
use RuntimeException;

final class AuthService
{
    private SessionManager $session;
    private RateLimiter $rateLimiter;
    private AuditLogService $auditLogger;

    public function __construct(SessionManager $session, RateLimiter $rateLimiter, AuditLogService $auditLogger)
    {
        $this->session = $session;
        $this->rateLimiter = $rateLimiter;
        $this->auditLogger = $auditLogger;
    }

    public function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);

        $this->rateLimiter->ensureLoginAllowed($email, $ipAddress);

        $user = (new AdminUser())->findByEmail($email);
        if (!$user || !(bool) $user['is_active'] || !password_verify($password, (string) $user['password_hash'])) {
            $this->rateLimiter->hit($email, $ipAddress);
            $this->auditLogger->log(null, 'auth', 'login_failed', ['email' => $email]);
            throw new RuntimeException('Credenciais inválidas.');
        }

        $this->rateLimiter->clear($email, $ipAddress);
        $this->session->regenerate();
        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_user_name'] = (string) $user['name'];
        $_SESSION['admin_user_email'] = (string) $user['email'];

        (new AdminUser())->markLogin((int) $user['id'], $ipAddress);
        $this->auditLogger->log((int) $user['id'], 'auth', 'login_success');

        return $user;
    }

    public function user(): ?array
    {
        $adminUserId = (int) ($_SESSION['admin_user_id'] ?? 0);
        if ($adminUserId <= 0) {
            return null;
        }

        return (new AdminUser())->find($adminUserId);
    }

    public function logout(): void
    {
        $adminUserId = (int) ($_SESSION['admin_user_id'] ?? 0);
        if ($adminUserId > 0) {
            $this->auditLogger->log($adminUserId, 'auth', 'logout');
        }
        $this->session->destroy();
    }
}
