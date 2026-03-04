<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Services\CsrfService;

final class LogsController extends Controller
{
    private AuditLog $logs;
    private AuthService $auth;
    private CsrfService $csrf;

    public function __construct(AuditLog $logs, AuthService $auth, CsrfService $csrf)
    {
        $this->logs = $logs;
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->render('admin.logs.index', [
            'pageTitle' => 'Logs do Sistema',
            'currentRoute' => 'logs',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'logs' => $this->logs->latest(),
        ]);
    }
}
