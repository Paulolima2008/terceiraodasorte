<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Middleware\Authenticate;

(new Authenticate($app['session'], $app['auth']))->handle();
header('Location: ' . BASE_URL . '/backoffice/users.php');
exit;
