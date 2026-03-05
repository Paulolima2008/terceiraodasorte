<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\UsersController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\VerifyCsrf;
use App\Models\AdminUser;

(new Authenticate($app['session'], $app['auth']))->handle();

$controller = new UsersController(new AdminUser(), $app['permissions'], $app['auth'], $app['auditLogger'], $app['csrf']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $requiredPermission = $action === 'permissions_update' ? 'permissions.manage' : 'users.manage';

    (new Authorize($app['auth'], $app['permissions'], $requiredPermission))->handle();
    (new VerifyCsrf($app['csrf']))->handle();
    $controller->handlePost();
    return;
}

(new Authorize($app['auth'], $app['permissions'], 'users.view'))->handle();
$controller->index();
