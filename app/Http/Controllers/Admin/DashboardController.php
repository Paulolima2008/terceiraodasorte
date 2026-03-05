<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\RouletteOperation;
use App\Services\AuthService;
use App\Services\CsrfService;
use App\Services\DashboardService;

final class DashboardController extends Controller
{
    private DashboardService $dashboard;
    private AuthService $auth;
    private CsrfService $csrf;

    public function __construct(DashboardService $dashboard, AuthService $auth, CsrfService $csrf)
    {
        $this->dashboard = $dashboard;
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function index(): void
    {
        $campaignId = (new RouletteOperation())->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));
        $metrics = $this->dashboard->metrics($campaignId);

        $this->render('admin.dashboard.index', [
            'pageTitle' => 'Dashboard',
            'currentRoute' => 'dashboard',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'metrics' => $metrics,
            'campaignId' => $campaignId,
        ]);
    }

    public function metricsApi(): never
    {
        $campaignId = (new RouletteOperation())->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));
        $this->json($this->dashboard->metrics($campaignId));
    }
}
