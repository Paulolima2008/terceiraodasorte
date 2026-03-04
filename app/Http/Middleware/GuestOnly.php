<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\SessionManager;

final class GuestOnly implements MiddlewareInterface
{
    private SessionManager $session;
    private AuthService $auth;

    public function __construct(SessionManager $session, AuthService $auth)
    {
        $this->session = $session;
        $this->auth = $auth;
    }

    public function handle(): void
    {
        $this->session->start();
        if ($this->auth->user() !== null) {
            header('Location: ' . BASE_URL . '/backoffice/dashboard.php');
            exit;
        }
    }
}
