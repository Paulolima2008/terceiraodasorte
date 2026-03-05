<?php
declare(strict_types=1);
?>
<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= e(APP_NAME) ?>">
        <div class="admin-brand-copy">
            <strong><?= e(APP_NAME) ?></strong>
            <span>Backoffice</span>
        </div>
    </div>

    <nav class="admin-nav">
        <a class="<?= $currentRoute === 'dashboard' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/dashboard.php" title="Dashboard">
            <i class="bi bi-speedometer2 admin-nav-icon"></i>
            <span class="admin-nav-label">Dashboard</span>
        </a>
        <a class="<?= $currentRoute === 'campaigns' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/campaigns.php" title="Campanhas">
            <i class="bi bi-bullseye admin-nav-icon"></i>
            <span class="admin-nav-label">Campanhas</span>
        </a>
        <a class="<?= $currentRoute === 'vouchers' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/vouchers.php" title="Vouchers">
            <i class="bi bi-ticket-perforated admin-nav-icon"></i>
            <span class="admin-nav-label">Vouchers</span>
        </a>
        <a class="<?= $currentRoute === 'payments' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/payments.php" title="Pagamentos">
            <i class="bi bi-cash-coin admin-nav-icon"></i>
            <span class="admin-nav-label">Pagamentos</span>
        </a>
        <a class="<?= $currentRoute === 'reports' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/reports.php" title="Relatorios">
            <i class="bi bi-bar-chart-line admin-nav-icon"></i>
            <span class="admin-nav-label">Relatorios</span>
        </a>
        <a class="<?= $currentRoute === 'users' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/users.php" title="Usuarios">
            <i class="bi bi-people admin-nav-icon"></i>
            <span class="admin-nav-label">Usuarios</span>
        </a>
        <a class="<?= $currentRoute === 'logs' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/logs.php" title="Logs">
            <i class="bi bi-journal-text admin-nav-icon"></i>
            <span class="admin-nav-label">Logs</span>
        </a>
        <a class="<?= $currentRoute === 'settings' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/settings.php" title="Configuracoes">
            <i class="bi bi-sliders admin-nav-icon"></i>
            <span class="admin-nav-label">Configuracoes</span>
        </a>
    </nav>
</aside>
