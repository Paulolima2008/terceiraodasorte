<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\RouletteOperation;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class PaymentsController extends Controller
{
    private RouletteOperation $operations;
    private AuthService $auth;
    private AuditLogService $auditLogger;
    private CsrfService $csrf;

    public function __construct(RouletteOperation $operations, AuthService $auth, AuditLogService $auditLogger, CsrfService $csrf)
    {
        $this->operations = $operations;
        $this->auth = $auth;
        $this->auditLogger = $auditLogger;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $campaignId = $this->operations->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));

        $this->render('admin.payments.index', [
            'pageTitle' => 'Pagamentos de Premios',
            'currentRoute' => 'payments',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'campaignId' => $campaignId,
            'campaign' => $campaignId > 0 ? $this->operations->campaign($campaignId) : null,
            'rows' => $campaignId > 0 ? $this->operations->paymentRows($campaignId) : [],
        ]);
    }

    public function handlePost(): never
    {
        $campaignId = $this->operations->resolveCampaignId((int) ($_POST['campaign_id'] ?? 0));
        $spinId = (int) ($_POST['spin_id'] ?? 0);
        $actorId = (int) (($this->auth->user())['id'] ?? 0);

        try {
            if ($spinId <= 0) {
                throw new \RuntimeException('Registro de pagamento invalido.');
            }

            $updated = $this->operations->markWinnerAsPaid($campaignId, $spinId);
            if (!$updated) {
                throw new \RuntimeException('Nao foi possivel atualizar o pagamento.');
            }

            $this->auditLogger->log($actorId, 'payments', 'mark_paid', [
                'campaign_id' => $campaignId,
                'spin_id' => $spinId,
            ]);
            \setFlash('success', 'Premio marcado como pago.');
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect(BASE_URL . '/backoffice/payments.php?campaign_id=' . $campaignId);
    }
}
