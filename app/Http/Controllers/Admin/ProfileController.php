<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class ProfileController extends Controller
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
        $this->render('admin.profile.index', [
            'pageTitle' => 'Meu Perfil',
            'currentRoute' => 'profile',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
        ]);
    }

    public function update(): never
    {
        $user = $this->auth->user();
        $userId = (int) ($user['id'] ?? 0);

        try {
            if ($userId <= 0) {
                throw new \RuntimeException('Sessão inválida.');
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Informe nome e email válidos.');
            }

            $this->users->update($userId, [
                'name' => $name,
                'email' => $email,
                'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                'is_active' => 1,
            ]);

            $_SESSION['admin_user_name'] = $name;
            $_SESSION['admin_user_email'] = $email;
            $this->auditLogger->log($userId, 'profile', 'update_profile');
            \setFlash('success', 'Perfil atualizado com sucesso.');
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect(BASE_URL . '/backoffice/profile.php');
    }
}
