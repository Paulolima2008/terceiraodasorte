<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/management.php');
    exit;
}

$action = (string) ($_POST['action'] ?? '');

try {
    if ($action === 'add') {
        $position = (int) ($_POST['spin_position'] ?? 0);
        $prizeId = (int) ($_POST['prize_id'] ?? 0);

        if ($position < 1 || $position > 500) {
            throw new RuntimeException('A posicao deve estar entre 1 e 500.');
        }

        $stmtPrize = db()->prepare('SELECT id, name FROM prizes WHERE id = :id LIMIT 1');
        $stmtPrize->execute(['id' => $prizeId]);
        $prize = $stmtPrize->fetch();

        if (!$prize) {
            throw new RuntimeException('Premio invalido.');
        }

        $limits = prizeLimits();
        $name = (string) $prize['name'];
        if (!isset($limits[$name])) {
            throw new RuntimeException('Premio nao permitido na agenda.');
        }

        $stmtCountByPrize = db()->prepare(
            'SELECT COUNT(*) AS total
             FROM prize_schedule ps
             INNER JOIN prizes p ON p.id = ps.prize_id
             WHERE p.name = :name'
        );
        $stmtCountByPrize->execute(['name' => $name]);
        $countByPrize = (int) $stmtCountByPrize->fetch()['total'];

        if ($countByPrize >= $limits[$name]) {
            throw new RuntimeException('Limite atingido para ' . $name . '.');
        }

        $stmtTotal = db()->query('SELECT COUNT(*) AS total FROM prize_schedule');
        $totalScheduled = (int) $stmtTotal->fetch()['total'];
        if ($totalScheduled >= 50) {
            throw new RuntimeException('Ja existem 50 posicoes premiadas cadastradas.');
        }

        $stmtInsert = db()->prepare(
            'INSERT INTO prize_schedule (spin_position, prize_id) VALUES (:spin_position, :prize_id)'
        );
        $stmtInsert->execute([
            'spin_position' => $position,
            'prize_id' => $prizeId,
        ]);

        setFlash('success', 'Posicao premiada adicionada com sucesso.');
    } elseif ($action === 'remove') {
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        if ($scheduleId <= 0) {
            throw new RuntimeException('Registro invalido para remocao.');
        }

        $stmtDelete = db()->prepare('DELETE FROM prize_schedule WHERE id = :id');
        $stmtDelete->execute(['id' => $scheduleId]);
        setFlash('success', 'Posicao premiada removida.');
    } else {
        throw new RuntimeException('Acao invalida.');
    }
} catch (Throwable $t) {
    setFlash('error', $t->getMessage());
}

header('Location: ' . BASE_URL . '/admin/management.php');
exit;
