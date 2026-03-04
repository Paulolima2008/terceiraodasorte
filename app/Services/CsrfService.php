<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class CsrfService
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function input(): string
    {
        $token = htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    public function validate(?string $token): void
    {
        $sessionToken = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        if ($sessionToken === '' || $token === null || !hash_equals($sessionToken, $token)) {
            throw new RuntimeException('Falha na validacao CSRF.');
        }
    }
}
