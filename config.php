<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

define('APP_NAME', "Terceir\u{00E3}o da Sorte");
define('BASE_URL', '/terceiraodasorte');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'roletasolidaria');
define('DB_USER', 'root');
define('DB_PASS', '');

// Codigo de acesso do painel administrativo.
define('ADMIN_ACCESS_CODE', '847291');
define('ADMIN_DEFAULT_EMAIL', 'admin@terceiraodasorte.local');
define('ADMIN_SESSION_TIMEOUT', 1800);
define('ADMIN_RATE_LIMIT_MAX_ATTEMPTS', 5);
define('ADMIN_RATE_LIMIT_WINDOW_MINUTES', 15);
