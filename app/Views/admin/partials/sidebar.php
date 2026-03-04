<?php
declare(strict_types=1);
?>
<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= e(APP_NAME) ?>">
        <div>
            <strong><?= e(APP_NAME) ?></strong>
            <span>Backoffice</span>
        </div>
    </div>

    <nav class="admin-nav">
        <a class="<?= $currentRoute === 'dashboard' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/dashboard.php">Dashboard</a>
        <a class="<?= $currentRoute === 'users' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/users.php">Usuários</a>
        <a class="<?= $currentRoute === 'permissions' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/permissions.php">Permissões</a>
        <a class="<?= $currentRoute === 'logs' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/logs.php">Logs</a>
        <a class="<?= $currentRoute === 'settings' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/settings.php">Configurações</a>
        <a class="<?= $currentRoute === 'profile' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/backoffice/profile.php">Meu Perfil</a>
        <a href="<?= BASE_URL ?>/admin/management.php">Gerenciamento legado</a>
    </nav>
</aside>
