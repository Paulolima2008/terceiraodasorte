<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$target = BASE_URL . '/backoffice/users.php';
$userId = (int) ($_GET['user_id'] ?? 0);
if ($userId > 0) {
    $target .= '?permissions=' . $userId;
}

header('Location: ' . $target);
exit;
