<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\CsrfService;
use RuntimeException;

final class VerifyCsrf implements MiddlewareInterface
{
    private CsrfService $csrf;

    public function __construct(CsrfService $csrf)
    {
        $this->csrf = $csrf;
    }

    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        try {
            $this->csrf->validate($_POST['_token'] ?? null);
        } catch (RuntimeException $exception) {
            http_response_code(419);
            echo 'Sessao expirada. Recarregue a pagina e tente novamente.';
            exit;
        }
    }
}
