<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

startSession();

if (!empty($_SESSION['admin_authenticated'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessCode = trim((string) ($_POST['access_code'] ?? ''));

    if (hash_equals(ADMIN_ACCESS_CODE, $accessCode)) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }

    $error = 'Código de acesso inválido.';
}

$flash = getFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<main class="page">
    <section class="card">
        <div class="login-brand">
            <img class="login-brand-logo" src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>">
        </div>
        <h1>Painel Administrativo</h1>
        <p class="subtitle">Informe o código de acesso.</p>

        <?php if ($flash !== null): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack">
            <label for="access_code">Código de acesso</label>
            <input id="access_code" name="access_code" type="password" required>
            <button type="submit">Entrar</button>
        </form>

        <a class="link" href="<?= BASE_URL ?>/index.php">Voltar para validação</a>
    </section>
</main>
</body>
</html>
