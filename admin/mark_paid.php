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

$spinId = (int) ($_POST['spin_id'] ?? 0);

if ($spinId <= 0) {
    setFlash('error', 'Giro invalido para marcacao de pagamento.');
    header('Location: ' . BASE_URL . '/admin/management.php');
    exit;
}

$stmt = db()->prepare(
    'UPDATE spins
     SET is_paid = 1, paid_at = NOW()
     WHERE id = :id AND is_winner = 1 AND is_paid = 0'
);
$stmt->execute(['id' => $spinId]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Premio marcado como pago.');
} else {
    setFlash('error', 'Nao foi possivel marcar como pago (ja pago ou nao premiado).');
}

header('Location: ' . BASE_URL . '/admin/management.php');
exit;
