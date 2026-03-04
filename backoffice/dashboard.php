<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;

(new Authenticate($app['session'], $app['auth']))->handle();
(new Authorize($app['auth'], $app['permissions'], 'dashboard.view'))->handle();

(new DashboardController($app['dashboard'], $app['auth'], $app['csrf']))->index();
