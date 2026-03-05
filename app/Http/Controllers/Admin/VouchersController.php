<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Models\RouletteOperation;
use App\Services\AuthService;
use App\Services\CsrfService;

final class VouchersController extends Controller
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
        $campaigns = $this->operations->campaigns();
        $campaignId = $this->operations->resolveCampaignId((int) ($_GET['campaign_id'] ?? 0));
        $status = (string) ($_GET['status'] ?? 'all');
        if (!in_array($status, ['all', 'used', 'unused'], true)) {
            $status = 'all';
        }
        $campaign = $campaignId > 0 ? $this->operations->campaign($campaignId) : null;
        $report = $campaignId > 0 ? $this->operations->voucherReport($campaignId) : [
            'sold_count' => 0,
            'unsold_count' => 0,
            'sold_value' => 0.0,
            'unsold_value' => 0.0,
            'unit_value' => 0.0,
        ];

        $this->render('admin.vouchers.index', [
            'pageTitle' => 'Vouchers',
            'currentRoute' => 'vouchers',
            'currentUser' => $this->auth->user(),
            'flash' => \getFlash(),
            'csrf' => $this->csrf,
            'campaigns' => $campaigns,
            'campaignId' => $campaignId,
            'campaign' => $campaign,
            'report' => $report,
            'status' => $status,
            'rows' => $campaignId > 0 ? $this->operations->voucherRows($campaignId, $status) : [],
        ]);
    }
}
