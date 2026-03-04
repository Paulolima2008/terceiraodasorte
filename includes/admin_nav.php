<?php
declare(strict_types=1);

$activePage = $activePage ?? '';
?>
<header class="admin-header">
    <div class="admin-top">
        <div class="admin-brand">
            <img class="admin-brand-logo" src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>">
            <div>
                <h1 class="admin-title">Painel Administrativo</h1>
                <p class="subtitle admin-subtitle">Separe o dashboard das operacoes da roleta.</p>
            </div>
        </div>
        <a class="link admin-logout" href="<?= BASE_URL ?>/admin/logout.php">Sair</a>
    </div>

    <nav class="admin-menu" aria-label="Menu administrativo">
        <a class="admin-menu-link <?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
        <a class="admin-menu-link <?= $activePage === 'management' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/management.php">Gerenciamento</a>
    </nav>
</header>
