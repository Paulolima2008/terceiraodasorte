<?php
declare(strict_types=1);

$campaignOptions = [];
$selectedCampaignId = (int) ($_SESSION['admin_campaign_id'] ?? 0);

try {
    $campaignOptions = (new \App\Models\Campaign())->allBasic();
} catch (\Throwable $ignored) {
    $campaignOptions = [];
}

if ($selectedCampaignId <= 0 && $campaignOptions !== []) {
    $selectedCampaignId = (int) $campaignOptions[0]['id'];
}
?>
<header class="admin-topbar">
    <div class="admin-topbar-main">
        <button class="btn btn-light admin-sidebar-toggle" type="button" data-sidebar-toggle>
            <span class="navbar-toggler-icon"></span>
        </button>
        <button class="btn btn-light admin-sidebar-collapse-toggle" type="button" data-sidebar-expand-toggle aria-label="Minimizar ou maximizar menu lateral">
            <i class="bi bi-layout-sidebar"></i>
        </button>
        <div class="admin-topbar-copy">
            <h1><?= e($pageTitle) ?></h1>
            <p>Painel administrativo seguro, modular e pronto para producao.</p>
        </div>
    </div>
    <div class="admin-userbox">
        <?php if ($campaignOptions !== []): ?>
            <div class="admin-campaign-switcher">
                <select id="adminCampaignSwitcher" class="form-select" data-campaign-switcher>
                    <?php foreach ($campaignOptions as $campaign): ?>
                        <option value="<?= (int) $campaign['id'] ?>" <?= (int) $campaign['id'] === $selectedCampaignId ? 'selected' : '' ?>>
                            <?= e((string) $campaign['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="admin-userbox-meta">
            <strong><?= e((string) ($currentUser['name'] ?? 'Administrador')) ?></strong>
            <span><?= e((string) ($currentUser['email'] ?? '')) ?></span>
        </div>
        <a class="btn btn-danger" href="<?= BASE_URL ?>/backoffice/logout.php">Sair</a>
    </div>
</header>
