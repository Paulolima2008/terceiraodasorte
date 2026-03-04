<?php
declare(strict_types=1);
?>
<header class="admin-topbar">
    <div>
        <button class="btn btn-light admin-sidebar-toggle" type="button" data-sidebar-toggle>
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="admin-topbar-copy">
            <h1><?= e($pageTitle) ?></h1>
            <p>Painel administrativo seguro, modular e pronto para produção.</p>
        </div>
    </div>
    <div class="admin-userbox">
        <div class="admin-userbox-meta">
            <strong><?= e((string) ($currentUser['name'] ?? 'Administrador')) ?></strong>
            <span><?= e((string) ($currentUser['email'] ?? '')) ?></span>
        </div>
        <a class="btn btn-danger" href="<?= BASE_URL ?>/backoffice/logout.php">Sair</a>
    </div>
</header>
