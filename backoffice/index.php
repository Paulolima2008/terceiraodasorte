<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\AuthController;
use App\Http\Middleware\GuestOnly;
use App\Http\Middleware\VerifyCsrf;

(new GuestOnly($app['session'], $app['auth']))->handle();

$controller = new AuthController($app['auth'], $app['csrf']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    (new VerifyCsrf($app['csrf']))->handle();
    $controller->login();
    return;
}

$controller->showLogin();
