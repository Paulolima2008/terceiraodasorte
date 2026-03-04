<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo = db();
$flash = getFlash();

$searchCode = normalizeCode((string) ($_GET['code'] ?? ''));
$codeDetails = null;

if ($searchCode !== '') {
    $stmtSearch = $pdo->prepare(
        'SELECT
            c.code,
            c.is_used,
            c.used_at,
            s.id AS spin_id,
            s.spin_position,
            s.result_label,
            s.is_winner,
            s.is_paid,
            s.paid_at
         FROM codes c
         LEFT JOIN spins s ON s.code_id = c.id
         WHERE c.code = :code
         LIMIT 1'
    );
    $stmtSearch->execute(['code' => $searchCode]);
    $codeDetails = $stmtSearch->fetch();
}

$prizes = $pdo->query('SELECT id, name FROM prizes ORDER BY amount ASC')->fetchAll();

$scheduleRows = $pdo->query(
    'SELECT ps.id, ps.spin_position, p.name
     FROM prize_schedule ps
     INNER JOIN prizes p ON p.id = ps.prize_id
     ORDER BY ps.spin_position ASC'
)->fetchAll();

$countsByPrize = $pdo->query(
    'SELECT p.name, COUNT(*) AS total
     FROM prize_schedule ps
     INNER JOIN prizes p ON p.id = ps.prize_id
     GROUP BY p.name'
)->fetchAll();

$scheduleUsage = [];
foreach ($countsByPrize as $row) {
    $scheduleUsage[(string) $row['name']] = (int) $row['total'];
}

$limits = prizeLimits();
$activePage = 'management';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerenciamento Admin - <?= APP_NAME ?></title>
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
    </section>

    <section class="card">
        <h2>Consultar Codigo e Premio</h2>
        <form method="get" class="inline-form">
            <input type="text" name="code" maxlength="5" value="<?= e($searchCode) ?>" placeholder="Ex: A1B23">
            <button type="submit">Consultar</button>
        </form>

        <?php if ($searchCode !== ''): ?>
            <?php if (!$codeDetails): ?>
                <p class="muted">Codigo nao encontrado.</p>
            <?php else: ?>
                <div class="result-box">
                    <p><strong>Codigo:</strong> <?= e($codeDetails['code']) ?></p>
                    <p><strong>Usado:</strong> <?= (int) $codeDetails['is_used'] ? 'Sim' : 'Nao' ?></p>
                    <p><strong>Resultado:</strong> <?= e((string) ($codeDetails['result_label'] ?? '-')) ?></p>
                    <p><strong>Posicao do giro:</strong> <?= e((string) ($codeDetails['spin_position'] ?? '-')) ?></p>
                    <p><strong>Pago:</strong> <?= (int) ($codeDetails['is_paid'] ?? 0) ? 'Sim' : 'Nao' ?></p>

                    <?php if ((int) ($codeDetails['is_winner'] ?? 0) === 1 && (int) ($codeDetails['is_paid'] ?? 0) === 0): ?>
                        <form method="post" action="<?= BASE_URL ?>/admin/mark_paid.php">
                            <input type="hidden" name="spin_id" value="<?= (int) $codeDetails['spin_id'] ?>">
                            <button type="submit">Marcar premio pago</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="card" id="agenda-premios">
        <h2>Agendar Posicoes Premiadas</h2>
        <form method="post" action="<?= BASE_URL ?>/admin/schedule_action.php" class="inline-form">
            <input type="hidden" name="action" value="add">
            <input type="number" name="spin_position" min="1" max="500" required placeholder="Posicao do giro">
            <select name="prize_id" required>
                <option value="">Selecione o premio</option>
                <?php foreach ($prizes as $prize): ?>
                    <option value="<?= (int) $prize['id'] ?>"><?= e($prize['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Adicionar posicao</button>
        </form>

        <div class="limit-grid">
            <?php foreach ($limits as $prizeName => $limit): ?>
                <?php $used = $scheduleUsage[$prizeName] ?? 0; ?>
                <div class="limit-item">
                    <span><?= e($prizeName) ?></span>
                    <strong><?= $used ?>/<?= $limit ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Posicao</th>
                    <th>Premio</th>
                    <th>Acao</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($scheduleRows as $row): ?>
                    <tr>
                        <td>#<?= (int) $row['spin_position'] ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/admin/schedule_action.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="schedule_id" value="<?= (int) $row['id'] ?>">
                                <button class="btn-danger" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
