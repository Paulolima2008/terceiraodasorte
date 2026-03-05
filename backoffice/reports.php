<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\ReportsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Models\RouletteOperation;

(new Authenticate($app['session'], $app['auth']))->handle();
(new Authorize($app['auth'], $app['permissions'], 'reports.view'))->handle();

(new ReportsController(new RouletteOperation(), $app['auth'], $app['csrf']))->index();
