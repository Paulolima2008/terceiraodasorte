<?php
declare(strict_types=1);

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function normalizeCode(string $value): string
{
    return strtoupper(trim($value));
}

function isValidCodeFormat(string $code): bool
{
    if (!preg_match('/^[A-Z0-9]{5}$/', $code)) {
        return false;
    }

    $letters = preg_match_all('/[A-Z]/', $code);
    $digits = preg_match_all('/[0-9]/', $code);

    return $letters === 2 && $digits === 3;
}

function prizeSegments(): array
{
    return [
        'R$ 2,00',
        'Tente novamente',
        'R$ 5,00',
        'R$ 10,00',
        'Tente novamente',
        'R$ 20,00',
        'R$ 50,00',
        'R$ 5,00',
        'R$ 10,00',
        'R$ 20,00',
        'R$ 50,00',
        'R$ 2,00',
        'Tente novamente',
    ];
}

function segmentIndexesForLabel(string $label): array
{
    $segments = prizeSegments();
    $indexes = [];

    foreach ($segments as $index => $segmentLabel) {
        if ($segmentLabel === $label) {
            $indexes[] = $index;
        }
    }

    if (count($indexes) > 0) {
        return $indexes;
    }

    // For dynamic campaign labels, keep winner spins in winner slices instead of "Tente novamente".
    if ($label !== 'Tente novamente') {
        $winnerFallback = [];
        foreach ($segments as $index => $segmentLabel) {
            if ($segmentLabel !== 'Tente novamente') {
                $winnerFallback[] = $index;
            }
        }

        if ($winnerFallback !== []) {
            return $winnerFallback;
        }
    }

    $fallback = [];
    foreach ($segments as $index => $segmentLabel) {
        if ($segmentLabel === 'Tente novamente') {
            $fallback[] = $index;
        }
    }

    return $fallback;
}

function segmentIndexForLabel(string $label): int
{
    $indexes = segmentIndexesForLabel($label);
    return count($indexes) > 0 ? $indexes[0] : 0;
}

function prizeLimits(): array
{
    return [
        'R$ 50,00' => 4,
        'R$ 20,00' => 5,
        'R$ 10,00' => 10,
        'R$ 5,00' => 20,
        'R$ 2,00' => 11,
    ];
}

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    startSession();
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
