<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class AuthController extends Controller
{
    private AuthService $auth;
    private CsrfService $csrf;

    public function __construct(AuthService $auth, CsrfService $csrf)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function showLogin(): void
    {
        $this->render('admin.auth.login', [
            'pageTitle' => 'Login do Painel',
            'bodyClass' => 'admin-auth-page',
            'csrf' => $this->csrf,
            'flash' => \getFlash(),
            'timedOut' => isset($_GET['timeout']),
        ]);
    }

    public function login(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            \setFlash('error', 'Informe email e senha válidos.');
            $this->redirect(BASE_URL . '/backoffice/index.php');
        }

        try {
            $this->auth->attempt($email, $password);
            \setFlash('success', 'Autenticação realizada com sucesso.');
            $this->redirect(BASE_URL . '/backoffice/dashboard.php');
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
            $this->redirect(BASE_URL . '/backoffice/index.php');
        }
    }

    public function logout(): never
    {
        $this->auth->logout();
        \setFlash('success', 'Sessão encerrada com segurança.');
        $this->redirect(BASE_URL . '/backoffice/index.php');
    }
}
