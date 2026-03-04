<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config.php';

startSession();

if (empty($_SESSION['admin_authenticated'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

