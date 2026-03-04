<?php
declare(strict_types=1);

namespace App\Services;

final class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->touch();
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_name('terceirao_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->touch();
    }

    public function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }

        session_regenerate_id(true);
        $_SESSION['last_regenerated_at'] = time();
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public function touch(): void
    {
        $_SESSION['last_activity_at'] = time();
    }

    public function hasTimedOut(): bool
    {
        $lastActivityAt = (int) ($_SESSION['last_activity_at'] ?? 0);
        return $lastActivityAt > 0 && (time() - $lastActivityAt) > ADMIN_SESSION_TIMEOUT;
    }
}
