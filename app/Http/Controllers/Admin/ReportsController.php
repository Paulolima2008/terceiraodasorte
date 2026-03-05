<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\RouletteOperation;
use App\Services\AuthService;
use App\Services\CsrfService;

final class ReportsController extends Controller
{
    private RouletteOperation $operations;
    private AuthService $auth;
    private CsrfService $csrf;

    public function __construct(RouletteOperation $operations, AuthService $auth, CsrfService $csrf)
    {
        $this->operations = $operations;
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $campaignId = $this->operations->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));

        $this->render('admin.reports.index', [
            'pageTitle' => 'Relatorios',
            'currentRoute' => 'reports',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'campaignId' => $campaignId,
            'campaign' => $campaignId > 0 ? $this->operations->campaign($campaignId) : null,
            'voucherReport' => $campaignId > 0 ? $this->operations->voucherReport($campaignId) : [],
            'prizeReport' => $campaignId > 0 ? $this->operations->prizePaymentReport($campaignId) : [],
        ]);
    }
}
