<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\Campaign;
use App\Models\RouletteOperation;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CsrfService;
use Throwable;

final class CampaignsController extends Controller
{
    private Campaign $campaigns;
    private RouletteOperation $operations;
    private AuthService $auth;
    private AuditLogService $auditLogger;
    private CsrfService $csrf;

    public function __construct(Campaign $campaigns, RouletteOperation $operations, AuthService $auth, AuditLogService $auditLogger, CsrfService $csrf)
    {
        $this->campaigns = $campaigns;
        $this->operations = $operations;
        $this->auth = $auth;
        $this->auditLogger = $auditLogger;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $createFormData = $_SESSION['admin_campaigns_create_form'] ?? [];
        unset($_SESSION['admin_campaigns_create_form']);
        $scheduleFormData = $_SESSION['admin_campaigns_schedule_form'] ?? [];
        unset($_SESSION['admin_campaigns_schedule_form']);

        $scheduleCampaignId = (int) ($_GET['schedule'] ?? 0);
        $scheduleCampaign = null;
        $schedulePrizes = [];
        $scheduleRows = [];
        $scheduleModalOpen = false;
        if ($scheduleCampaignId > 0) {
            $scheduleCampaign = $this->operations->campaign($scheduleCampaignId);
            if (is_array($scheduleCampaign) && (string) ($scheduleCampaign['award_mode'] ?? '') === 'manual') {
                $schedulePrizes = $this->operations->prizes($scheduleCampaignId);
                $scheduleRows = $this->operations->scheduleRows($scheduleCampaignId);
                $scheduleModalOpen = true;
            }
        }

        $this->render('admin.campaigns.index', [
            'pageTitle' => 'Campanhas',
            'currentRoute' => 'campaigns',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'campaigns' => $this->campaigns->allWithMetrics(),
            'createFormData' => is_array($createFormData) ? $createFormData : [],
            'createModalOpen' => (string) ($_GET['modal'] ?? '') === 'create',
            'scheduleFormData' => is_array($scheduleFormData) ? $scheduleFormData : [],
            'scheduleCampaign' => $scheduleCampaign,
            'schedulePrizes' => is_array($schedulePrizes) ? $schedulePrizes : [],
            'scheduleRows' => is_array($scheduleRows) ? $scheduleRows : [],
            'scheduleModalOpen' => $scheduleModalOpen,
        ]);
    }

    public function handlePost(): never
    {
        $action = (string) ($_POST['action'] ?? '');
        $actorId = (int) (($this->auth->user())['id'] ?? 0);
        $redirectUrl = BASE_URL . '/backoffice/campaigns.php';

        try {
            if ($action === 'create') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $voucherQuantity = (int) ($_POST['voucher_quantity'] ?? 0);
                $voucherUnitValue = (float) ($_POST['voucher_unit_value'] ?? 0);
                $prizeQuantity = (int) ($_POST['prize_quantity'] ?? 0);
                $prizeTotalValue = (float) ($_POST['prize_total_value'] ?? 0);
                $awardMode = (string) ($_POST['award_mode'] ?? 'manual');
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                $_SESSION['admin_campaigns_create_form'] = [
                    'name' => $name,
                    'voucher_quantity' => $voucherQuantity,
                    'voucher_unit_value' => $voucherUnitValue,
                    'prize_quantity' => $prizeQuantity,
                    'prize_total_value' => $prizeTotalValue,
                    'award_mode' => $awardMode,
                    'is_active' => $isActive,
                ];

                $campaignId = $this->campaigns->createAndBootstrap([
                    'name' => $name,
                    'voucher_quantity' => $voucherQuantity,
                    'voucher_unit_value' => $voucherUnitValue,
                    'prize_quantity' => $prizeQuantity,
                    'prize_total_value' => $prizeTotalValue,
                    'award_mode' => $awardMode,
                    'is_active' => $isActive === 1,
                ]);

                $this->auditLogger->log($actorId, 'campaigns', 'create_campaign', ['campaign_id' => $campaignId]);
                \setFlash('success', 'Campanha criada com sucesso.');
                unset($_SESSION['admin_campaigns_create_form']);
            } elseif ($action === 'activate') {
                $campaignId = (int) ($_POST['campaign_id'] ?? 0);
                $this->campaigns->activate($campaignId);
                $this->auditLogger->log($actorId, 'campaigns', 'activate_campaign', ['campaign_id' => $campaignId]);
                \setFlash('success', 'Campanha ativada.');
            } elseif ($action === 'auto_schedule') {
                $campaignId = (int) ($_POST['campaign_id'] ?? 0);
                $this->campaigns->generateAutomaticSchedule($campaignId);
                $this->auditLogger->log($actorId, 'campaigns', 'auto_schedule', ['campaign_id' => $campaignId]);
                \setFlash('success', 'Agenda automatica gerada com sucesso.');
            } elseif ($action === 'schedule_add') {
                $campaignId = (int) ($_POST['campaign_id'] ?? 0);
                $position = (int) ($_POST['spin_position'] ?? 0);
                $prizeId = (int) ($_POST['prize_id'] ?? 0);

                $_SESSION['admin_campaigns_schedule_form'] = [
                    'spin_position' => $position,
                    'prize_id' => $prizeId,
                ];

                $this->operations->addSchedule($campaignId, $position, $prizeId);
                $this->auditLogger->log($actorId, 'campaigns', 'schedule_add', [
                    'campaign_id' => $campaignId,
                    'spin_position' => $position,
                    'prize_id' => $prizeId,
                ]);
                \setFlash('success', 'Posicao premiada adicionada com sucesso.');
                unset($_SESSION['admin_campaigns_schedule_form']);
                $redirectUrl .= '?schedule=' . $campaignId;
            } elseif ($action === 'schedule_remove') {
                $campaignId = (int) ($_POST['campaign_id'] ?? 0);
                $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
                if ($scheduleId <= 0) {
                    throw new \RuntimeException('Registro invalido para remocao.');
                }

                $removed = $this->operations->removeSchedule($campaignId, $scheduleId);
                if (!$removed) {
                    throw new \RuntimeException('Registro de agenda nao encontrado.');
                }

                $this->auditLogger->log($actorId, 'campaigns', 'schedule_remove', [
                    'campaign_id' => $campaignId,
                    'schedule_id' => $scheduleId,
                ]);
                \setFlash('success', 'Posicao premiada removida.');
                $redirectUrl .= '?schedule=' . $campaignId;
            } else {
                throw new \RuntimeException('Acao invalida.');
            }
        } catch (Throwable $exception) {
            if ($action === 'create') {
                $redirectUrl .= '?modal=create';
            } elseif ($action === 'schedule_add' || $action === 'schedule_remove') {
                $redirectUrl .= '?schedule=' . (int) ($_POST['campaign_id'] ?? 0);
            }
            \setFlash('error', $exception->getMessage());
        }

        $this->redirect($redirectUrl);
    }
}
