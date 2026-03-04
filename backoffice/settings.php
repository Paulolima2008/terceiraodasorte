<?php
declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/admin.php';

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\Middleware\VerifyCsrf;
use App\Models\Setting;

(new Authenticate($app['session'], $app['auth']))->handle();
(new Authorize($app['auth'], $app['permissions'], 'settings.manage'))->handle();

$controller = new SettingsController(new Setting(), $app['auth'], $app['auditLogger'], $app['csrf']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    (new VerifyCsrf($app['csrf']))->handle();
    $controller->update();
    return;
}

$controller->index();
