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

function splitAmounts(int $quantity, float $totalValue): array
{
    $totalCents = (int) round($totalValue * 100);
    if ($quantity <= 0 || $totalCents <= 0) {
        return [];
    }

    $base = intdiv($totalCents, $quantity);
    $remainder = $totalCents % $quantity;

    $values = array_fill(0, $quantity, $base);
    for ($i = 0; $i < $remainder; $i++) {
        $values[$i]++;
    }

    shuffle($values);

    return array_map(static fn (int $cents): float => $cents / 100, $values);
}

function generateAutomaticSchedule(PDO $pdo, int $campaignId): int
{
    $voucherQuantityStmt = $pdo->prepare('SELECT voucher_quantity FROM campaigns WHERE id = :id LIMIT 1');
    $voucherQuantityStmt->execute(['id' => $campaignId]);
    $voucherQuantity = (int) ($voucherQuantityStmt->fetchColumn() ?: 0);

    if ($voucherQuantity <= 0) {
        return 0;
    }

    $prizeStmt = $pdo->prepare('SELECT id FROM campaign_prizes WHERE campaign_id = :campaign_id ORDER BY id ASC');
    $prizeStmt->execute(['campaign_id' => $campaignId]);
    $prizeIds = array_map('intval', $prizeStmt->fetchAll(PDO::FETCH_COLUMN));

    if ($prizeIds === []) {
        return 0;
    }

    if (count($prizeIds) > $voucherQuantity) {
        $prizeIds = array_slice($prizeIds, 0, $voucherQuantity);
    }

    $delete = $pdo->prepare('DELETE FROM campaign_prize_schedule WHERE campaign_id = :campaign_id');
    $delete->execute(['campaign_id' => $campaignId]);

    $positions = range(1, $voucherQuantity);
    shuffle($positions);
    $selectedPositions = array_slice($positions, 0, count($prizeIds));
    sort($selectedPositions);

    $insert = $pdo->prepare(
        'INSERT INTO campaign_prize_schedule (campaign_id, spin_position, prize_id, created_at)
         VALUES (:campaign_id, :spin_position, :prize_id, NOW())'
    );

    foreach ($prizeIds as $index => $prizeId) {
        $insert->execute([
            'campaign_id' => $campaignId,
            'spin_position' => $selectedPositions[$index],
            'prize_id' => $prizeId,
        ]);
    }

    return count($prizeIds);
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

    // Legado (mantido para compatibilidade)
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

    $messages[] = 'Tabelas legadas verificadas/criadas.';

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

    $existingRows = $pdo->query('SELECT code FROM codes')->fetchAll(PDO::FETCH_COLUMN);
    $existing = [];
    foreach ($existingRows as $existingCode) {
        $existing[(string) $existingCode] = true;
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
    }

    // Novo modelo de campanhas
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            voucher_quantity INT UNSIGNED NOT NULL,
            voucher_unit_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            prize_quantity INT UNSIGNED NOT NULL,
            prize_total_value DECIMAL(12,2) NOT NULL,
            award_mode ENUM("manual", "automatic") NOT NULL DEFAULT "manual",
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_campaigns_active (is_active)
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaign_vouchers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            code CHAR(5) NOT NULL UNIQUE,
            is_used TINYINT(1) NOT NULL DEFAULT 0,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign_vouchers_campaign_status (campaign_id, is_used),
            CONSTRAINT fk_campaign_vouchers_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaign_prizes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign_prizes_campaign (campaign_id),
            CONSTRAINT fk_campaign_prizes_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaign_states (
            campaign_id INT UNSIGNED PRIMARY KEY,
            next_spin_position INT UNSIGNED NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_campaign_states_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaign_prize_schedule (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            spin_position INT UNSIGNED NOT NULL,
            prize_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_campaign_position (campaign_id, spin_position),
            UNIQUE KEY uniq_campaign_prize (prize_id),
            CONSTRAINT fk_campaign_prize_schedule_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
            CONSTRAINT fk_campaign_prize_schedule_prize FOREIGN KEY (prize_id) REFERENCES campaign_prizes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS campaign_spins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            voucher_id INT UNSIGNED NOT NULL UNIQUE,
            spin_position INT UNSIGNED NOT NULL,
            prize_id INT UNSIGNED NULL,
            is_winner TINYINT(1) NOT NULL DEFAULT 0,
            is_paid TINYINT(1) NOT NULL DEFAULT 0,
            paid_at DATETIME NULL,
            result_label VARCHAR(120) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_campaign_spin_position (campaign_id, spin_position),
            INDEX idx_campaign_spins_status (campaign_id, is_winner, is_paid),
            CONSTRAINT fk_campaign_spins_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
            CONSTRAINT fk_campaign_spins_voucher FOREIGN KEY (voucher_id) REFERENCES campaign_vouchers(id) ON DELETE CASCADE,
            CONSTRAINT fk_campaign_spins_prize FOREIGN KEY (prize_id) REFERENCES campaign_prizes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );

    $messages[] = 'Tabelas de campanhas verificadas/criadas.';

    $campaignCount = (int) $pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
    if ($campaignCount === 0) {
        $legacyVoucherCount = (int) $pdo->query('SELECT COUNT(*) FROM codes')->fetchColumn();
        $legacyPrizeCount = (int) $pdo->query('SELECT COUNT(*) FROM prizes')->fetchColumn();
        $legacyPrizeTotal = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM prizes')->fetchColumn();
        $legacyScheduleCount = (int) $pdo->query('SELECT COUNT(*) FROM prize_schedule')->fetchColumn();

        $insertCampaign = $pdo->prepare(
            'INSERT INTO campaigns
                (name, voucher_quantity, voucher_unit_value, prize_quantity, prize_total_value, award_mode, is_active, created_at, updated_at)
             VALUES
                (:name, :voucher_quantity, :voucher_unit_value, :prize_quantity, :prize_total_value, :award_mode, 1, NOW(), NOW())'
        );
        $insertCampaign->execute([
            'name' => 'Campanha Inicial',
            'voucher_quantity' => max($legacyVoucherCount, 500),
            'voucher_unit_value' => 0,
            'prize_quantity' => max($legacyPrizeCount, 50),
            'prize_total_value' => $legacyPrizeTotal > 0 ? $legacyPrizeTotal : 500.00,
            'award_mode' => $legacyScheduleCount > 0 ? 'manual' : 'automatic',
        ]);
        $messages[] = 'Campanha inicial criada.';
    }

    $campaignId = (int) $pdo->query('SELECT id FROM campaigns ORDER BY is_active DESC, id ASC LIMIT 1')->fetchColumn();
    if ($campaignId <= 0) {
        throw new RuntimeException('Nao foi possivel obter uma campanha valida para inicializacao.');
    }

    $stateExistsStmt = $pdo->prepare('SELECT 1 FROM campaign_states WHERE campaign_id = :campaign_id LIMIT 1');
    $stateExistsStmt->execute(['campaign_id' => $campaignId]);
    if (!$stateExistsStmt->fetchColumn()) {
        $legacyNext = (int) $pdo->query('SELECT COALESCE(next_spin_position, 1) FROM system_state WHERE id = 1')->fetchColumn();
        $insertState = $pdo->prepare(
            'INSERT INTO campaign_states (campaign_id, next_spin_position, updated_at)
             VALUES (:campaign_id, :next_spin_position, NOW())'
        );
        $insertState->execute([
            'campaign_id' => $campaignId,
            'next_spin_position' => max(1, $legacyNext),
        ]);
    }

    $newVoucherCount = (int) $pdo->query('SELECT COUNT(*) FROM campaign_vouchers')->fetchColumn();
    if ($newVoucherCount === 0) {
        $legacyCodes = $pdo->query('SELECT id, code, is_used, used_at, created_at FROM codes ORDER BY id ASC')->fetchAll();

        if ($legacyCodes !== []) {
            $insertVoucher = $pdo->prepare(
                'INSERT INTO campaign_vouchers (id, campaign_id, code, is_used, used_at, created_at)
                 VALUES (:id, :campaign_id, :code, :is_used, :used_at, :created_at)'
            );

            foreach ($legacyCodes as $legacyCode) {
                $insertVoucher->execute([
                    'id' => (int) $legacyCode['id'],
                    'campaign_id' => $campaignId,
                    'code' => (string) $legacyCode['code'],
                    'is_used' => (int) $legacyCode['is_used'],
                    'used_at' => $legacyCode['used_at'],
                    'created_at' => (string) $legacyCode['created_at'],
                ]);
            }
            $messages[] = 'Vouchers legados migrados para a campanha inicial.';
        } else {
            $voucherQuantity = (int) $pdo->query('SELECT voucher_quantity FROM campaigns WHERE id = ' . $campaignId)->fetchColumn();
            $existingCodesNew = [];
            foreach ($pdo->query('SELECT code FROM campaign_vouchers')->fetchAll(PDO::FETCH_COLUMN) as $code) {
                $existingCodesNew[(string) $code] = true;
            }

            $insertVoucher = $pdo->prepare(
                'INSERT INTO campaign_vouchers (campaign_id, code, is_used, used_at, created_at)
                 VALUES (:campaign_id, :code, 0, NULL, NOW())'
            );

            $remaining = max(1, $voucherQuantity);
            while ($remaining > 0) {
                $code = generateCode();
                if (isset($existingCodesNew[$code])) {
                    continue;
                }

                $insertVoucher->execute([
                    'campaign_id' => $campaignId,
                    'code' => $code,
                ]);
                $existingCodesNew[$code] = true;
                $remaining--;
            }
            $messages[] = 'Vouchers iniciais gerados para a campanha.';
        }
    }

    $newPrizeCount = (int) $pdo->query('SELECT COUNT(*) FROM campaign_prizes')->fetchColumn();
    if ($newPrizeCount === 0) {
        $legacyPrizes = $pdo->query('SELECT id, name, amount, created_at FROM prizes ORDER BY id ASC')->fetchAll();

        if ($legacyPrizes !== []) {
            $insertPrize = $pdo->prepare(
                'INSERT INTO campaign_prizes (id, campaign_id, name, amount, created_at)
                 VALUES (:id, :campaign_id, :name, :amount, :created_at)'
            );

            foreach ($legacyPrizes as $legacyPrize) {
                $insertPrize->execute([
                    'id' => (int) $legacyPrize['id'],
                    'campaign_id' => $campaignId,
                    'name' => substr((string) $legacyPrize['name'], 0, 120),
                    'amount' => (float) $legacyPrize['amount'],
                    'created_at' => (string) $legacyPrize['created_at'],
                ]);
            }
            $messages[] = 'Premios legados migrados para a campanha inicial.';
        } else {
            $campaignRow = $pdo->query('SELECT prize_quantity, prize_total_value FROM campaigns WHERE id = ' . $campaignId)->fetch();
            $prizeQuantity = max(1, (int) ($campaignRow['prize_quantity'] ?? 1));
            $prizeTotal = max(0.01, (float) ($campaignRow['prize_total_value'] ?? 1));

            $amounts = splitAmounts($prizeQuantity, $prizeTotal);
            $insertPrize = $pdo->prepare(
                'INSERT INTO campaign_prizes (campaign_id, name, amount, created_at)
                 VALUES (:campaign_id, :name, :amount, NOW())'
            );

            foreach ($amounts as $index => $amount) {
                $insertPrize->execute([
                    'campaign_id' => $campaignId,
                    'name' => 'Premio #' . ($index + 1) . ' - R$ ' . number_format($amount, 2, ',', '.'),
                    'amount' => $amount,
                ]);
            }
            $messages[] = 'Premios iniciais gerados para a campanha.';
        }
    }

    $newScheduleCount = (int) $pdo->query('SELECT COUNT(*) FROM campaign_prize_schedule')->fetchColumn();
    if ($newScheduleCount === 0) {
        $legacySchedule = $pdo->query('SELECT spin_position, prize_id FROM prize_schedule ORDER BY spin_position ASC')->fetchAll();

        if ($legacySchedule !== []) {
            $existingPrizeIds = $pdo->query('SELECT id FROM campaign_prizes WHERE campaign_id = ' . $campaignId)->fetchAll(PDO::FETCH_COLUMN);
            $existingPrizeMap = [];
            foreach ($existingPrizeIds as $prizeId) {
                $existingPrizeMap[(int) $prizeId] = true;
            }

            $insertSchedule = $pdo->prepare(
                'INSERT INTO campaign_prize_schedule (campaign_id, spin_position, prize_id, created_at)
                 VALUES (:campaign_id, :spin_position, :prize_id, NOW())'
            );

            foreach ($legacySchedule as $row) {
                $prizeId = (int) $row['prize_id'];
                if (!isset($existingPrizeMap[$prizeId])) {
                    continue;
                }

                try {
                    $insertSchedule->execute([
                        'campaign_id' => $campaignId,
                        'spin_position' => (int) $row['spin_position'],
                        'prize_id' => $prizeId,
                    ]);
                } catch (Throwable $ignored) {
                }
            }
            $messages[] = 'Agenda legada de premios migrada para a campanha inicial.';
        } else {
            $awardMode = (string) $pdo->query('SELECT award_mode FROM campaigns WHERE id = ' . $campaignId)->fetchColumn();
            if ($awardMode === 'automatic') {
                $generated = generateAutomaticSchedule($pdo, $campaignId);
                $messages[] = 'Agenda automatica gerada com ' . $generated . ' premios.';
            }
        }
    }

    $newSpinCount = (int) $pdo->query('SELECT COUNT(*) FROM campaign_spins')->fetchColumn();
    if ($newSpinCount === 0) {
        $legacySpins = $pdo->query(
            'SELECT id, code_id, spin_position, prize_id, is_winner, is_paid, paid_at, result_label, created_at
             FROM spins
             ORDER BY id ASC'
        )->fetchAll();

        if ($legacySpins !== []) {
            $existingVoucherIds = $pdo->query('SELECT id FROM campaign_vouchers WHERE campaign_id = ' . $campaignId)->fetchAll(PDO::FETCH_COLUMN);
            $voucherMap = [];
            foreach ($existingVoucherIds as $voucherId) {
                $voucherMap[(int) $voucherId] = true;
            }
            $existingPrizeIds = $pdo->query('SELECT id FROM campaign_prizes WHERE campaign_id = ' . $campaignId)->fetchAll(PDO::FETCH_COLUMN);
            $prizeMap = [];
            foreach ($existingPrizeIds as $prizeId) {
                $prizeMap[(int) $prizeId] = true;
            }

            $insertSpin = $pdo->prepare(
                'INSERT INTO campaign_spins
                    (id, campaign_id, voucher_id, spin_position, prize_id, is_winner, is_paid, paid_at, result_label, created_at)
                 VALUES
                    (:id, :campaign_id, :voucher_id, :spin_position, :prize_id, :is_winner, :is_paid, :paid_at, :result_label, :created_at)'
            );

            foreach ($legacySpins as $spin) {
                $voucherId = (int) $spin['code_id'];
                if (!isset($voucherMap[$voucherId])) {
                    continue;
                }

                $prizeId = $spin['prize_id'] !== null ? (int) $spin['prize_id'] : null;
                if ($prizeId !== null && !isset($prizeMap[$prizeId])) {
                    $prizeId = null;
                }

                try {
                    $insertSpin->execute([
                        'id' => (int) $spin['id'],
                        'campaign_id' => $campaignId,
                        'voucher_id' => $voucherId,
                        'spin_position' => (int) $spin['spin_position'],
                        'prize_id' => $prizeId,
                        'is_winner' => (int) $spin['is_winner'],
                        'is_paid' => (int) $spin['is_paid'],
                        'paid_at' => $spin['paid_at'],
                        'result_label' => substr((string) $spin['result_label'], 0, 120),
                        'created_at' => (string) $spin['created_at'],
                    ]);
                } catch (Throwable $ignored) {
                }
            }
            $messages[] = 'Historico de giros legado migrado para a campanha inicial.';
        }
    }

    $syncStats = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM campaign_vouchers WHERE campaign_id = ' . $campaignId . ') AS voucher_count,
            (SELECT COUNT(*) FROM campaign_prizes WHERE campaign_id = ' . $campaignId . ') AS prize_count,
            (SELECT COALESCE(SUM(amount), 0) FROM campaign_prizes WHERE campaign_id = ' . $campaignId . ') AS prize_total'
    )->fetch();

    $updateCampaign = $pdo->prepare(
        'UPDATE campaigns
         SET voucher_quantity = :voucher_quantity,
             prize_quantity = :prize_quantity,
             prize_total_value = :prize_total_value,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateCampaign->execute([
        'voucher_quantity' => max(1, (int) ($syncStats['voucher_count'] ?? 1)),
        'prize_quantity' => max(1, (int) ($syncStats['prize_count'] ?? 1)),
        'prize_total_value' => max(0.01, (float) ($syncStats['prize_total'] ?? 0.01)),
        'id' => $campaignId,
    ]);

    $messages[] = 'Modelo de campanhas inicializado com sucesso.';
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
    <title>Instalacao - <?= APP_NAME ?></title>
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
    <h1>Instalacao <?= APP_NAME ?></h1>
    <?php if ($error !== null): ?>
        <p class="err"><strong>Erro:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <ul class="ok">
            <?php foreach ($messages as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Codigo de acesso do admin:</strong> <code><?= ADMIN_ACCESS_CODE ?></code></p>
        <p><a href="<?= BASE_URL ?>/index.php">Ir para validacao de codigo</a></p>
        <p><a href="<?= BASE_URL ?>/backoffice/index.php">Ir para backoffice administrativo</a></p>
    <?php endif; ?>
</div>
</body>
</html>
