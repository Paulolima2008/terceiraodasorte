<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\VerifyCsrf;
use App\Models\AdminUser;

(new Authenticate($app['session'], $app['auth']))->handle();

$controller = new PermissionsController(new AdminUser(), $app['permissions'], $app['auth'], $app['auditLogger'], $app['csrf']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    (new Authorize($app['auth'], $app['permissions'], 'permissions.manage'))->handle();
    (new VerifyCsrf($app['csrf']))->handle();
    $controller->update();
    return;
}

(new Authorize($app['auth'], $app['permissions'], 'permissions.view'))->handle();
$controller->index();
