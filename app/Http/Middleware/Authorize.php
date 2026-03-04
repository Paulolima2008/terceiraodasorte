<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\PermissionService;

final class Authorize implements MiddlewareInterface
{
    private AuthService $auth;
    private PermissionService $permissions;
    private string $permissionKey;

    public function __construct(AuthService $auth, PermissionService $permissions, string $permissionKey)
    {
        $this->auth = $auth;
        $this->permissions = $permissions;
        $this->permissionKey = $permissionKey;
    }

    public function handle(): void
    {
        $user = $this->auth->user();
        $adminUserId = (int) ($user['id'] ?? 0);

        if ($adminUserId <= 0 || !$this->permissions->allowed($adminUserId, $this->permissionKey)) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }
}
