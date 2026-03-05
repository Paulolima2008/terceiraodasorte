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

final class UsersController extends Controller
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
        $currentUser = $this->auth->user();
        $currentUserId = (int) ($currentUser['id'] ?? 0);
        $canManagePermissions = $currentUserId > 0 && $this->permissions->allowed($currentUserId, 'permissions.manage');

        $editId = (int) ($_GET['edit'] ?? 0);
        $editUser = $editId > 0 ? $this->users->find($editId) : null;

        $permissionUserId = 0;
        $permissionUser = null;
        $allPermissions = [];
        $permissionUserIds = [];
        if ($canManagePermissions) {
            $permissionUserId = (int) ($_GET['permissions'] ?? 0);
            $permissionUser = $permissionUserId > 0 ? $this->users->find($permissionUserId) : null;
            $permissionUserId = (int) ($permissionUser['id'] ?? 0);
            $allPermissions = $this->permissions->all();
            $permissionUserIds = $permissionUserId > 0 ? $this->permissions->userPermissionIds($permissionUserId) : [];
        }

        $createFormData = $_SESSION['admin_users_create_form'] ?? [];
        unset($_SESSION['admin_users_create_form']);

        $this->render('admin.users.index', [
            'pageTitle' => 'Usuarios',
            'currentRoute' => 'users',
            'currentUser' => $currentUser,
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'users' => $this->users->all(),
            'editUser' => $editUser,
            'createFormData' => is_array($createFormData) ? $createFormData : [],
            'createModalOpen' => (string) ($_GET['modal'] ?? '') === 'create',
            'canManagePermissions' => $canManagePermissions,
            'permissions' => $allPermissions,
            'permissionUser' => $permissionUser,
            'permissionModalOpen' => $permissionUserId > 0,
            'permissionUserIds' => $permissionUserIds,
        ]);
    }

    public function handlePost(): never
    {
        $action = (string) ($_POST['action'] ?? '');
        $actor = $this->auth->user();
        $actorId = (int) ($actor['id'] ?? 0);
        $redirectUrl = BASE_URL . '/backoffice/users.php';

        try {
            if ($action === 'create') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                $_SESSION['admin_users_create_form'] = [
                    'name' => $name,
                    'email' => $email,
                    'is_active' => $isActive,
                ];

                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                    throw new \RuntimeException('Preencha nome, email valido e senha com ao menos 6 caracteres.');
                }

                $userId = $this->users->create([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'is_active' => $isActive,
                ]);

                $this->auditLogger->log($actorId, 'users', 'create_user', ['target_user_id' => $userId]);
                \setFlash('success', 'Usuario criado com sucesso.');
                unset($_SESSION['admin_users_create_form']);
            } elseif ($action === 'update') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Dados invalidos para atualizacao.');
                }

                $this->users->update($id, [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                    'is_active' => $isActive,
                ]);

                $this->auditLogger->log($actorId, 'users', 'update_user', ['target_user_id' => $id]);
                \setFlash('success', 'Usuario atualizado com sucesso.');
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0 || $id === $actorId) {
                    throw new \RuntimeException('Exclusao invalida.');
                }

                $this->users->delete($id);
                $this->auditLogger->log($actorId, 'users', 'delete_user', ['target_user_id' => $id]);
                \setFlash('success', 'Usuario removido com sucesso.');
            } elseif ($action === 'permissions_update') {
                $targetUserId = (int) ($_POST['user_id'] ?? 0);
                $permissionIds = array_map('intval', (array) ($_POST['permission_ids'] ?? []));
                $targetUser = $this->users->find($targetUserId);

                if (!$targetUser) {
                    throw new \RuntimeException('Usuario invalido para atualizar permissoes.');
                }

                $this->permissions->sync($targetUserId, $permissionIds);
                $this->auditLogger->log($actorId, 'permissions', 'sync_permissions_from_users', [
                    'target_user_id' => $targetUserId,
                    'permission_ids' => $permissionIds,
                ]);
                \setFlash('success', 'Permissoes atualizadas com sucesso.');
            }
        } catch (Throwable $exception) {
            if ($action === 'create') {
                $redirectUrl .= '?modal=create';
            } elseif ($action === 'update') {
                $redirectUrl .= '?edit=' . (int) ($_POST['id'] ?? 0);
            } elseif ($action === 'permissions_update') {
                $redirectUrl .= '?permissions=' . (int) ($_POST['user_id'] ?? 0);
            }

            \setFlash('error', $exception->getMessage());
        }

        $this->redirect($redirectUrl);
    }
}
