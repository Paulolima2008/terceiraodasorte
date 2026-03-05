<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

startSession();

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo nao permitido.']);
    exit;
}

if (empty($_SESSION['validated_code'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Codigo nao validado na sessao.']);
    exit;
}

$code = normalizeCode((string) $_SESSION['validated_code']);
$sessionCampaignId = (int) ($_SESSION['validated_campaign_id'] ?? 0);
$pdo = db();

try {
    $pdo->beginTransaction();

    $stmtVoucher = $pdo->prepare(
        'SELECT id, campaign_id, is_used
         FROM campaign_vouchers
         WHERE code = :code
         FOR UPDATE'
    );
    $stmtVoucher->execute(['code' => $code]);
    $voucher = $stmtVoucher->fetch();

    if (!$voucher) {
        $pdo->rollBack();
        unset($_SESSION['validated_code'], $_SESSION['validated_campaign_id']);
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Codigo nao encontrado.']);
        exit;
    }

    $campaignId = (int) $voucher['campaign_id'];
    if ($sessionCampaignId > 0 && $sessionCampaignId !== $campaignId) {
        $pdo->rollBack();
        unset($_SESSION['validated_code'], $_SESSION['validated_campaign_id']);
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Codigo fora da campanha esperada.']);
        exit;
    }

    if ((int) $voucher['is_used'] === 1) {
        $pdo->rollBack();
        unset($_SESSION['validated_code'], $_SESSION['validated_campaign_id']);
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Este codigo ja foi utilizado.']);
        exit;
    }

    $stmtCampaign = $pdo->prepare('SELECT voucher_quantity FROM campaigns WHERE id = :id LIMIT 1');
    $stmtCampaign->execute(['id' => $campaignId]);
    $campaign = $stmtCampaign->fetch();
    if (!$campaign) {
        $pdo->rollBack();
        throw new RuntimeException('Campanha vinculada ao voucher nao encontrada.');
    }

    $stmtState = $pdo->prepare(
        'SELECT next_spin_position
         FROM campaign_states
         WHERE campaign_id = :campaign_id
         FOR UPDATE'
    );
    $stmtState->execute(['campaign_id' => $campaignId]);
    $state = $stmtState->fetch();

    if (!$state) {
        $insertState = $pdo->prepare(
            'INSERT INTO campaign_states (campaign_id, next_spin_position, updated_at)
             VALUES (:campaign_id, 1, NOW())'
        );
        $insertState->execute(['campaign_id' => $campaignId]);
        $spinPosition = 1;
    } else {
        $spinPosition = (int) $state['next_spin_position'];
    }

    $voucherLimit = max(1, (int) $campaign['voucher_quantity']);
    if ($spinPosition > $voucherLimit) {
        $pdo->rollBack();
        unset($_SESSION['validated_code'], $_SESSION['validated_campaign_id']);
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A campanha ja atingiu o limite de giros configurado.']);
        exit;
    }

    $updateState = $pdo->prepare(
        'UPDATE campaign_states
         SET next_spin_position = :next_spin_position, updated_at = NOW()
         WHERE campaign_id = :campaign_id'
    );
    $updateState->execute([
        'next_spin_position' => $spinPosition + 1,
        'campaign_id' => $campaignId,
    ]);

    $stmtSchedule = $pdo->prepare(
        'SELECT p.id, p.name
         FROM campaign_prize_schedule ps
         INNER JOIN campaign_prizes p ON p.id = ps.prize_id
         WHERE ps.campaign_id = :campaign_id
           AND ps.spin_position = :spin_position
         LIMIT 1'
    );
    $stmtSchedule->execute([
        'campaign_id' => $campaignId,
        'spin_position' => $spinPosition,
    ]);
    $scheduledPrize = $stmtSchedule->fetch();

    $isWinner = $scheduledPrize ? 1 : 0;
    $prizeId = $scheduledPrize ? (int) $scheduledPrize['id'] : null;
    $resultLabel = $scheduledPrize ? (string) $scheduledPrize['name'] : 'Tente novamente';

    $insertSpin = $pdo->prepare(
        'INSERT INTO campaign_spins
            (campaign_id, voucher_id, spin_position, prize_id, is_winner, is_paid, paid_at, result_label, created_at)
         VALUES
            (:campaign_id, :voucher_id, :spin_position, :prize_id, :is_winner, 0, NULL, :result_label, NOW())'
    );
    $insertSpin->execute([
        'campaign_id' => $campaignId,
        'voucher_id' => (int) $voucher['id'],
        'spin_position' => $spinPosition,
        'prize_id' => $prizeId,
        'is_winner' => $isWinner,
        'result_label' => $resultLabel,
    ]);

    $updateVoucher = $pdo->prepare(
        'UPDATE campaign_vouchers
         SET is_used = 1, used_at = NOW()
         WHERE id = :id'
    );
    $updateVoucher->execute(['id' => (int) $voucher['id']]);

    $pdo->commit();
    unset($_SESSION['validated_code'], $_SESSION['validated_campaign_id']);

    echo json_encode([
        'ok' => true,
        'is_winner' => (bool) $isWinner,
        'result_label' => $resultLabel,
        'segment_index' => segmentIndexForLabel($resultLabel),
        'segment_indexes' => segmentIndexesForLabel($resultLabel),
        'spin_position' => $spinPosition,
        'campaign_id' => $campaignId,
    ]);
} catch (Throwable $t) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erro interno ao processar giro.',
    ]);
}
