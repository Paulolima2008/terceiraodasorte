<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Painel';
$bodyClass = $bodyClass ?? 'admin-shell-page';
$currentRoute = $currentRoute ?? '';
$currentUser = $currentUser ?? null;
$flash = $flash ?? null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/admin/css/panel.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($bodyClass === 'admin-auth-page'): ?>
    <?= $content ?>
<?php else: ?>
    <div class="admin-shell" id="adminShell">
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class="admin-main">
            <?php require __DIR__ . '/../partials/topbar.php'; ?>
            <main class="admin-content container-fluid py-4">
                <?php if (is_array($flash)): ?>
                    <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : 'success') ?> border-0 shadow-sm">
                        <?= e((string) $flash['message']) ?>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= BASE_URL ?>/assets/admin/js/panel.js"></script>
</body>
</html>
