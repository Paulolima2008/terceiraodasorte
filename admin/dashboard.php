<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = db();
$flash = getFlash();

$totals = $pdo->query(
    'SELECT
        (SELECT COUNT(*) FROM codes) AS total_codes,
        (SELECT COUNT(*) FROM codes WHERE is_used = 1) AS used_codes,
        (SELECT COUNT(*) FROM spins WHERE is_winner = 1) AS winner_spins,
        (SELECT COUNT(*) FROM spins WHERE is_winner = 1 AND is_paid = 1) AS paid_winners,
        (SELECT COUNT(*) FROM prize_schedule) AS scheduled_count'
)->fetch();

$latestSpins = $pdo->query(
    'SELECT
        s.id,
        c.code,
        s.spin_position,
        s.result_label,
        s.is_winner,
        s.is_paid,
        s.created_at
     FROM spins s
     INNER JOIN codes c ON c.id = s.code_id
     ORDER BY s.id DESC
     LIMIT 20'
)->fetchAll();

$activePage = 'dashboard';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<main class="page page-wide">
    <section class="card">
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>

        <?php if ($flash !== null): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-item">
                <span>Total de codigos</span>
                <strong><?= (int) $totals['total_codes'] ?></strong>
            </div>
            <div class="stat-item">
                <span>Codigos usados</span>
                <strong><?= (int) $totals['used_codes'] ?></strong>
            </div>
            <div class="stat-item">
                <span>Giros premiados</span>
                <strong><?= (int) $totals['winner_spins'] ?></strong>
            </div>
            <div class="stat-item">
                <span>Premios pagos</span>
                <strong><?= (int) $totals['paid_winners'] ?></strong>
            </div>
            <div class="stat-item">
                <span>Posicoes premiadas</span>
                <strong><?= (int) $totals['scheduled_count'] ?>/50</strong>
            </div>
        </div>

        <div class="admin-shortcuts">
            <a class="shortcut-card" href="<?= BASE_URL ?>/admin/management.php">
                <strong>Consultar codigos</strong>
                <span>Veja resultados, pagamento e historico por codigo.</span>
            </a>
            <a class="shortcut-card" href="<?= BASE_URL ?>/admin/management.php#agenda-premios">
                <strong>Agendar premiacoes</strong>
                <span>Cadastre e remova posicoes premiadas em uma area separada.</span>
            </a>
        </div>
    </section>

    <section class="card">
        <h2>Ultimos Giros</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Posicao</th>
                    <th>Resultado</th>
                    <th>Pago</th>
                    <th>Data</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latestSpins as $spin): ?>
                    <tr>
                        <td><?= e($spin['code']) ?></td>
                        <td>#<?= (int) $spin['spin_position'] ?></td>
                        <td><?= e($spin['result_label']) ?></td>
                        <td><?= (int) $spin['is_paid'] ? 'Sim' : 'Nao' ?></td>
                        <td><?= e($spin['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
