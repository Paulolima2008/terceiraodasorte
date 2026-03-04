<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'admin/layouts/base'): void
    {
        $viewFile = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        $layoutFile = __DIR__ . '/../Views/' . str_replace('.', '/', $layout) . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        require $layoutFile;
    }
}
