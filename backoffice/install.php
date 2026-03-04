<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalação do Backoffice</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-3">Backoffice instalado</h1>
            <p class="text-muted">As tabelas do novo painel já foram verificadas/criadas automaticamente.</p>
            <ul>
                <li>Email inicial: <code><?= e(ADMIN_DEFAULT_EMAIL) ?></code></li>
                <li>Senha inicial: <code><?= e(ADMIN_ACCESS_CODE) ?></code></li>
                <li>Login: <a href="<?= BASE_URL ?>/backoffice/index.php"><?= BASE_URL ?>/backoffice/index.php</a></li>
            </ul>
        </div>
    </div>
</main>
</body>
</html>
