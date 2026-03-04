<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class SettingsController extends Controller
{
    private Setting $settings;
    private AuthService $auth;
    private AuditLogService $auditLogger;
    private CsrfService $csrf;

    public function __construct(Setting $settings, AuthService $auth, AuditLogService $auditLogger, CsrfService $csrf)
    {
        $this->settings = $settings;
        $this->auth = $auth;
        $this->auditLogger = $auditLogger;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $this->render('admin.settings.index', [
            'pageTitle' => 'Configurações',
            'currentRoute' => 'settings',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(): never
    {
        $actorId = (int) (($this->auth->user())['id'] ?? 0);

        try {
            $payload = [
                'site_name' => trim((string) ($_POST['site_name'] ?? APP_NAME)),
                'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
                'whatsapp_number' => trim((string) ($_POST['whatsapp_number'] ?? '')),
                'support_message' => trim((string) ($_POST['support_message'] ?? '')),
            ];

            $this->settings->saveMany($payload, $actorId);
            $this->auditLogger->log($actorId, 'settings', 'update_settings', ['keys' => array_keys($payload)]);
            \setFlash('success', 'Configurações salvas com sucesso.');
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect(BASE_URL . '/backoffice/settings.php');
    }
}
