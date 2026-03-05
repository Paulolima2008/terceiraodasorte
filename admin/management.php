<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$target = BASE_URL . '/backoffice/operations.php';
$code = strtoupper(trim((string) ($_GET['code'] ?? '')));

if ($code !== '') {
    $target .= '?code=' . rawurlencode($code);
}

header('Location: ' . $target);
exit;
