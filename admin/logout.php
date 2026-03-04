<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

startSession();
unset($_SESSION['admin_authenticated']);
setFlash('success', 'Sessão encerrada.');

header('Location: ' . BASE_URL . '/admin/index.php');
exit;

