<?php

final class Auth
{
    public static function attempt(string $usuario, string $senha): bool
    {
        $stmt = Database::get()->prepare('SELECT * FROM admins WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_usuario'] = $admin['usuario'];
        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function usuario(): ?string
    {
        return $_SESSION['admin_usuario'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(string $loginUrl = '/admin/login.php'): void
    {
        if (!self::check()) {
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    public static function trocarSenha(int $adminId, string $novaSenha): void
    {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = Database::get()->prepare('UPDATE admins SET senha_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $adminId]);
    }

    public static function criarAdmin(string $usuario, string $senha): void
    {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = Database::get()->prepare('INSERT INTO admins (usuario, senha_hash) VALUES (?, ?)');
        $stmt->execute([$usuario, $hash]);
    }
}
