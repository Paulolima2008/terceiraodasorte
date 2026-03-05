<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\RouletteOperation;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class OperationsController extends Controller
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
        $campaigns = $this->operations->campaigns();
        $campaignId = $this->operations->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));
        $campaign = $campaignId > 0 ? $this->operations->campaign($campaignId) : null;
        $searchCode = \normalizeCode((string) ($_GET['code'] ?? ''));

        $this->render('admin.operations.index', [
            'pageTitle' => 'Operacoes da Roleta',
            'currentRoute' => 'operations',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'campaigns' => $campaigns,
            'campaignId' => $campaignId,
            'campaign' => $campaign,
            'searchCode' => $searchCode,
            'codeDetails' => ($campaignId > 0 && $searchCode !== '') ? $this->operations->findCodeDetails($searchCode, $campaignId) : null,
            'summary' => $campaignId > 0 ? $this->operations->summary($campaignId) : [],
            'prizes' => $campaignId > 0 ? $this->operations->prizes($campaignId) : [],
            'scheduleRows' => $campaignId > 0 ? $this->operations->scheduleRows($campaignId) : [],
        ]);
    }

    public function handlePost(): never
    {
        $campaignId = $this->operations->resolveCampaignId((int) ($_POST['campaign_id'] ?? 0));
        $action = (string) ($_POST['action'] ?? '');
        $actorId = (int) (($this->auth->user())['id'] ?? 0);
        $redirectTo = BASE_URL . '/backoffice/operations.php?campaign_id=' . $campaignId;

        try {
            if ($action === 'mark_paid') {
                $spinId = (int) ($_POST['spin_id'] ?? 0);
                $returnCode = \normalizeCode((string) ($_POST['return_code'] ?? ''));

                if ($spinId <= 0) {
                    throw new \RuntimeException('Giro invalido para marcacao de pagamento.');
                }

                $updated = $this->operations->markWinnerAsPaid($campaignId, $spinId);
                if (!$updated) {
                    throw new \RuntimeException('Nao foi possivel marcar como pago (ja pago ou nao premiado).');
                }

                $this->auditLogger->log($actorId, 'operations', 'mark_paid', [
                    'campaign_id' => $campaignId,
                    'spin_id' => $spinId,
                ]);
                \setFlash('success', 'Premio marcado como pago.');

                if ($returnCode !== '') {
                    $redirectTo .= '&code=' . rawurlencode($returnCode);
                }
            } elseif ($action === 'schedule_add') {
                $position = (int) ($_POST['spin_position'] ?? 0);
                $prizeId = (int) ($_POST['prize_id'] ?? 0);

                $this->operations->addSchedule($campaignId, $position, $prizeId);
                $this->auditLogger->log($actorId, 'operations', 'schedule_add', [
                    'campaign_id' => $campaignId,
                    'spin_position' => $position,
                    'prize_id' => $prizeId,
                ]);
                \setFlash('success', 'Posicao premiada adicionada com sucesso.');
                $redirectTo .= '#agenda-premios';
            } elseif ($action === 'schedule_remove') {
                $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
                if ($scheduleId <= 0) {
                    throw new \RuntimeException('Registro invalido para remocao.');
                }

                $removed = $this->operations->removeSchedule($campaignId, $scheduleId);
                if (!$removed) {
                    throw new \RuntimeException('Registro de agenda nao encontrado.');
                }

                $this->auditLogger->log($actorId, 'operations', 'schedule_remove', [
                    'campaign_id' => $campaignId,
                    'schedule_id' => $scheduleId,
                ]);
                \setFlash('success', 'Posicao premiada removida.');
                $redirectTo .= '#agenda-premios';
            } elseif ($action === 'auto_schedule') {
                $this->operations->regenerateAutomaticSchedule($campaignId);
                $this->auditLogger->log($actorId, 'operations', 'auto_schedule', ['campaign_id' => $campaignId]);
                \setFlash('success', 'Agenda automatica gerada com sucesso.');
                $redirectTo .= '#agenda-premios';
            } else {
                throw new \RuntimeException('Acao invalida.');
            }
        } catch (Throwable $exception) {
            \setFlash('error', $exception->getMessage());
            if (str_starts_with($action, 'schedule_') || $action === 'auto_schedule') {
                $redirectTo .= '#agenda-premios';
            }
        }

        $this->redirect($redirectTo);
    }
}
