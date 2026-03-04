<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

startSession();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (empty($_SESSION['validated_code'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Código não validado na sessão.']);
    exit;
}

$code = normalizeCode($_SESSION['validated_code']);
$pdo = db();

try {
    $pdo->beginTransaction();

    $stmtCode = $pdo->prepare('SELECT id, is_used FROM codes WHERE code = :code FOR UPDATE');
    $stmtCode->execute(['code' => $code]);
    $codeRow = $stmtCode->fetch();

    if (!$codeRow) {
        $pdo->rollBack();
        unset($_SESSION['validated_code']);
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Código não encontrado.']);
        exit;
    }

    if ((int) $codeRow['is_used'] === 1) {
        $pdo->rollBack();
        unset($_SESSION['validated_code']);
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Este código já foi utilizado.']);
        exit;
    }

    $stmtState = $pdo->prepare('SELECT next_spin_position FROM system_state WHERE id = 1 FOR UPDATE');
    $stmtState->execute();
    $state = $stmtState->fetch();

    if (!$state) {
        $pdo->exec("INSERT INTO system_state (id, next_spin_position) VALUES (1, 1)");
        $spinPosition = 1;
    } else {
        $spinPosition = (int) $state['next_spin_position'];
    }

    $stmtUpdateState = $pdo->prepare('UPDATE system_state SET next_spin_position = :next WHERE id = 1');
    $stmtUpdateState->execute(['next' => $spinPosition + 1]);

    $stmtSchedule = $pdo->prepare(
        'SELECT p.id, p.name
         FROM prize_schedule ps
         INNER JOIN prizes p ON p.id = ps.prize_id
         WHERE ps.spin_position = :spin_position
         LIMIT 1'
    );
    $stmtSchedule->execute(['spin_position' => $spinPosition]);
    $scheduledPrize = $stmtSchedule->fetch();

    $isWinner = $scheduledPrize ? 1 : 0;
    $prizeId = $scheduledPrize ? (int) $scheduledPrize['id'] : null;
    $resultLabel = $scheduledPrize ? (string) $scheduledPrize['name'] : 'Tente novamente';

    $stmtInsertSpin = $pdo->prepare(
        'INSERT INTO spins (code_id, spin_position, prize_id, is_winner, result_label)
         VALUES (:code_id, :spin_position, :prize_id, :is_winner, :result_label)'
    );
    $stmtInsertSpin->execute([
        'code_id' => (int) $codeRow['id'],
        'spin_position' => $spinPosition,
        'prize_id' => $prizeId,
        'is_winner' => $isWinner,
        'result_label' => $resultLabel,
    ]);

    $stmtUsed = $pdo->prepare('UPDATE codes SET is_used = 1, used_at = NOW() WHERE id = :id');
    $stmtUsed->execute(['id' => (int) $codeRow['id']]);

    $pdo->commit();
    unset($_SESSION['validated_code']);

    echo json_encode([
        'ok' => true,
        'is_winner' => (bool) $isWinner,
        'result_label' => $resultLabel,
        'segment_index' => segmentIndexForLabel($resultLabel),
        'segment_indexes' => segmentIndexesForLabel($resultLabel),
        'spin_position' => $spinPosition,
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
