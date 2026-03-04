<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function generateCode(): string
{
    $positions = [0, 1, 2, 3, 4];
    shuffle($positions);
    $letterPositions = array_flip(array_slice($positions, 0, 2));

    $result = [];
    for ($i = 0; $i < 5; $i++) {
        if (isset($letterPositions[$i])) {
            $result[] = chr(random_int(65, 90));
        } else {
            $result[] = (string) random_int(0, 9);
        }
    }

    return implode('', $result);
}

$messages = [];
$error = null;

try {
    $serverPdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $serverPdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $messages[] = 'Banco "' . DB_NAME . '" verificado/criado.';

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS prizes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(20) NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code CHAR(5) NOT NULL UNIQUE,
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS system_state (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            next_spin_position INT UNSIGNED NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS spins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code_id INT UNSIGNED NOT NULL UNIQUE,
            spin_position INT UNSIGNED NOT NULL UNIQUE,
            prize_id INT UNSIGNED NULL,
            is_winner TINYINT(1) NOT NULL DEFAULT 0,
            is_paid TINYINT(1) NOT NULL DEFAULT 0,
            paid_at DATETIME NULL,
            result_label VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_spins_code FOREIGN KEY (code_id) REFERENCES codes(id) ON DELETE CASCADE,
            CONSTRAINT fk_spins_prize FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS prize_schedule (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            spin_position INT UNSIGNED NOT NULL UNIQUE,
            prize_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_schedule_prize FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    $messages[] = 'Tabelas verificadas/criadas.';

    $pdo->exec(
        "INSERT INTO system_state (id, next_spin_position) VALUES (1, 1)
         ON DUPLICATE KEY UPDATE id = id"
    );

    $prizeSeed = [
        ['R$ 2,00', 2.00],
        ['R$ 5,00', 5.00],
        ['R$ 10,00', 10.00],
        ['R$ 20,00', 20.00],
        ['R$ 50,00', 50.00],
    ];

    $stmtPrize = $pdo->prepare(
        'INSERT INTO prizes (name, amount) VALUES (:name, :amount)
         ON DUPLICATE KEY UPDATE amount = VALUES(amount)'
    );
    foreach ($prizeSeed as [$name, $amount]) {
        $stmtPrize->execute(['name' => $name, 'amount' => $amount]);
    }
    $messages[] = 'Prêmios base inseridos.';

    $existingRows = $pdo->query('SELECT code FROM codes')->fetchAll(PDO::FETCH_COLUMN);
    $existing = [];
    foreach ($existingRows as $existingCode) {
        $existing[$existingCode] = true;
    }

    $missing = 500 - count($existing);
    if ($missing > 0) {
        $insertCode = $pdo->prepare('INSERT INTO codes (code) VALUES (:code)');
        while ($missing > 0) {
            $newCode = generateCode();
            if (isset($existing[$newCode])) {
                continue;
            }

            $insertCode->execute(['code' => $newCode]);
            $existing[$newCode] = true;
            $missing--;
        }
        $messages[] = 'Códigos gerados até completar 500.';
    } else {
        $messages[] = 'Já existem 500 códigos (ou mais). Nenhum novo código foi criado.';
    }
} catch (Throwable $t) {
    $error = $t->getMessage();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <title>Instalação - <?= APP_NAME ?></title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f6fa;padding:30px}
        .box{max-width:760px;margin:0 auto;background:#fff;border-radius:10px;padding:20px;border:1px solid #ddd}
        .ok{color:#0b6b35}
        .err{color:#8f0a0a}
        code{background:#f0f0f0;padding:2px 5px;border-radius:4px}
    </style>
</head>
<body>
<div class="box">
    <h1>Instalação <?= APP_NAME ?></h1>
    <?php if ($error !== null): ?>
        <p class="err"><strong>Erro:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <ul class="ok">
            <?php foreach ($messages as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Código de acesso do admin:</strong> <code><?= ADMIN_ACCESS_CODE ?></code></p>
        <p><a href="<?= BASE_URL ?>/index.php">Ir para validação de código</a></p>
        <p><a href="<?= BASE_URL ?>/admin/index.php">Ir para painel administrativo</a></p>
    <?php endif; ?>
</div>
</body>
</html>
