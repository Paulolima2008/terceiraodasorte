<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\AuthController;
use App\Http\Middleware\Authenticate;

(new Authenticate($app['session'], $app['auth']))->handle();
(new AuthController($app['auth'], $app['csrf']))->logout();
