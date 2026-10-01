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

/**
 * Data de modificação de um arquivo em /public, usada como query string de
 * cache-busting (ex.: style.css?v=173...) para que o navegador baixe a
 * versão nova assim que o arquivo mudar, sem precisar limpar o cache.
 */
function asset_v(string $publicRelativePath): string
{
    $full = __DIR__ . '/../' . ltrim($publicRelativePath, '/');
    return file_exists($full) ? (string) filemtime($full) : '1';
}
