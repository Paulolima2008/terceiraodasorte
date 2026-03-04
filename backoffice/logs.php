<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\LogsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Models\AuditLog;

(new Authenticate($app['session'], $app['auth']))->handle();
(new Authorize($app['auth'], $app['permissions'], 'logs.view'))->handle();

(new LogsController(new AuditLog(), $app['auth'], $app['csrf']))->index();
