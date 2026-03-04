<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/../includes/helpers.php';

use App\Services\AdminInstaller;
use App\Services\AuthService;
use App\Services\AuditLogService;
use App\Services\CsrfService;
use App\Services\DashboardService;
use App\Services\PermissionService;
use App\Services\RateLimiter;
use App\Services\SessionManager;

$session = new SessionManager();
$session->start();

$installer = new AdminInstaller();
$installer->ensure();

$auditLogger = new AuditLogService();
$rateLimiter = new RateLimiter();
$csrf = new CsrfService();
$permissionService = new PermissionService();
$auth = new AuthService($session, $rateLimiter, $auditLogger);
$dashboardService = new DashboardService();

return [
    'session' => $session,
    'installer' => $installer,
    'auditLogger' => $auditLogger,
    'rateLimiter' => $rateLimiter,
    'csrf' => $csrf,
    'permissions' => $permissionService,
    'auth' => $auth,
    'dashboard' => $dashboardService,
];
