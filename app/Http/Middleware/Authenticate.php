<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Campaign;
use App\Services\AuthService;
use App\Services\SessionManager;
use Throwable;

final class Authenticate implements MiddlewareInterface
{
    private const CAMPAIGN_SESSION_KEY = 'admin_campaign_id';

    private SessionManager $session;
    private AuthService $auth;

    public function __construct(SessionManager $session, AuthService $auth)
    {
        $this->session = $session;
        $this->auth = $auth;
    }

    public function handle(): void
    {
        $this->session->start();

        if ($this->session->hasTimedOut()) {
            $this->session->destroy();
            header('Location: ' . BASE_URL . '/backoffice/index.php?timeout=1');
            exit;
        }

        if ($this->auth->user() === null) {
            header('Location: ' . BASE_URL . '/backoffice/index.php');
            exit;
        }

        $this->syncCampaignSelection();
        $this->session->touch();
    }

    private function syncCampaignSelection(): void
    {
        try {
            $campaigns = (new Campaign())->allBasic();
        } catch (Throwable $ignored) {
            return;
        }

        if ($campaigns === []) {
            unset($_SESSION[self::CAMPAIGN_SESSION_KEY]);
            return;
        }

        $validIds = [];
        foreach ($campaigns as $campaign) {
            $validIds[] = (int) ($campaign['id'] ?? 0);
        }

        $requestedCampaignId = (int) ($_GET['campaign_id'] ?? 0);
        if ($requestedCampaignId <= 0) {
            $requestedCampaignId = (int) ($_POST['campaign_id'] ?? 0);
        }

        if ($requestedCampaignId > 0 && in_array($requestedCampaignId, $validIds, true)) {
            $_SESSION[self::CAMPAIGN_SESSION_KEY] = $requestedCampaignId;
            return;
        }

        $sessionCampaignId = (int) ($_SESSION[self::CAMPAIGN_SESSION_KEY] ?? 0);
        if ($sessionCampaignId > 0 && in_array($sessionCampaignId, $validIds, true)) {
            return;
        }

        $_SESSION[self::CAMPAIGN_SESSION_KEY] = (int) $validIds[0];
    }
}
