<?php

declare(strict_types=1);

foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'bootstrap.php') {
        require_once $file;
    }
}

$config = Database::config();
date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name'] ?? 'torneio_admin_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
