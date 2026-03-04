<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use App\Services\PermissionService;
use Throwable;

final class PermissionsController extends Controller
{
    private AdminUser $users;
    private PermissionService $permissions;
    private AuthService $auth;
    private AuditLogService $auditLogger;
    private CsrfService $csrf;

    public function __construct(AdminUser $users, PermissionService $permissions, AuthService $auth, AuditLogService $auditLogger, CsrfService $csrf)
    {
        $this->users = $users;
        $this->permissions = $permissions;
        $this->auth = $auth;
        $this->auditLogger = $auditLogger;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $selectedUserId = (int) ($_GET['user_id'] ?? 0);
        if ($selectedUserId <= 0) {
            $users = $this->users->all();
            $selectedUserId = (int) ($users[0]['id'] ?? 0);
        }

        $this->render('admin.permissions.index', [
            'pageTitle' => 'Permissões',
            'currentRoute' => 'permissions',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'users' => $this->users->all(),
            'selectedUserId' => $selectedUserId,
            'permissions' => $this->permissions->all(),
            'selectedPermissionIds' => $selectedUserId > 0 ? $this->permissions->userPermissionIds($selectedUserId) : [],
        ]);
    }

    public function update(): never
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $permissionIds = array_map('intval', (array) ($_POST['permission_ids'] ?? []));
        $actorId = (int) (($this->auth->user())['id'] ?? 0);

        try {
            if ($userId <= 0) {
                throw new \RuntimeException('Usuário inválido para permissão.');
            }

            $this->permissions->sync($userId, $permissionIds);
            $this->auditLogger->log($actorId, 'permissions', 'sync_permissions', [
                'target_user_id' => $userId,
                'permission_ids' => $permissionIds,
            ]);
            \setFlash('success', 'Permissões atualizadas com sucesso.');
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect(BASE_URL . '/backoffice/permissions.php?user_id=' . $userId);
    }
}
