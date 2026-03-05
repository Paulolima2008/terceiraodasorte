<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\OperationsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\VerifyCsrf;
use App\Models\RouletteOperation;

(new Authenticate($app['session'], $app['auth']))->handle();

$controller = new OperationsController(new RouletteOperation(), $app['auth'], $app['auditLogger'], $app['csrf']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    (new Authorize($app['auth'], $app['permissions'], 'operations.manage'))->handle();
    (new VerifyCsrf($app['csrf']))->handle();
    $controller->handlePost();
    return;
}

(new Authorize($app['auth'], $app['permissions'], 'operations.view'))->handle();
$controller->index();
