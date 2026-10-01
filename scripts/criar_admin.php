<?php

declare(strict_types=1);

/**
 * Cria um usuário administrador.
 * Uso: php scripts/criar_admin.php <usuario> <senha>
 */

require __DIR__ . '/../public/src/bootstrap.php';

$usuario = $argv[1] ?? null;
$senha = $argv[2] ?? null;

if (!$usuario || !$senha) {
    echo "Uso: php scripts/criar_admin.php <usuario> <senha>\n";
    exit(1);
}

if (strlen($senha) < 6) {
    echo "A senha deve ter pelo menos 6 caracteres.\n";
    exit(1);
}

$existente = Database::get()->prepare('SELECT id FROM admins WHERE usuario = ?');
$existente->execute([$usuario]);
if ($existente->fetch()) {
    echo "Já existe um admin com o usuário '{$usuario}'.\n";
    exit(1);
}

Auth::criarAdmin($usuario, $senha);
echo "Admin '{$usuario}' criado com sucesso.\n";
