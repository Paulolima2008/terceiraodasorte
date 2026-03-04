<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class UsersController extends Controller
{
    private AdminUser $users;
    private AuthService $auth;
    private AuditLogService $auditLogger;
    private CsrfService $csrf;

    public function __construct(AdminUser $users, AuthService $auth, AuditLogService $auditLogger, CsrfService $csrf)
    {
        $this->users = $users;
        $this->auth = $auth;
        $this->auditLogger = $auditLogger;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $editId = (int) ($_GET['edit'] ?? 0);
        $editUser = $editId > 0 ? $this->users->find($editId) : null;

        $this->render('admin.users.index', [
            'pageTitle' => 'Usuários',
            'currentRoute' => 'users',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'users' => $this->users->all(),
            'editUser' => $editUser,
        ]);
    }

    public function handlePost(): never
    {
        $action = (string) ($_POST['action'] ?? '');
        $actor = $this->auth->user();
        $actorId = (int) ($actor['id'] ?? 0);

        try {
            if ($action === 'create') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                    throw new \RuntimeException('Preencha nome, email válido e senha com ao menos 6 caracteres.');
                }

                $userId = $this->users->create([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'is_active' => $isActive,
                ]);

                $this->auditLogger->log($actorId, 'users', 'create_user', ['target_user_id' => $userId]);
                \setFlash('success', 'Usuário criado com sucesso.');
            } elseif ($action === 'update') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Dados inválidos para atualização.');
                }

                $this->users->update($id, [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                    'is_active' => $isActive,
                ]);

                $this->auditLogger->log($actorId, 'users', 'update_user', ['target_user_id' => $id]);
                \setFlash('success', 'Usuário atualizado com sucesso.');
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0 || $id === $actorId) {
                    throw new \RuntimeException('Exclusão inválida.');
                }

                $this->users->delete($id);
                $this->auditLogger->log($actorId, 'users', 'delete_user', ['target_user_id' => $id]);
                \setFlash('success', 'Usuário removido com sucesso.');
            }
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect(BASE_URL . '/backoffice/users.php');
    }
}
