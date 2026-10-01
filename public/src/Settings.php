<?php

final class Settings
{
    private const DEFAULTS = [
        'pin_ativo' => '0',
        'pin_valor' => '',
        'rodape_texto' => 'Vôlei de praia · 1 set de 21 pontos, diferença mínima de 2.',
    ];

    public static function get(string $chave, ?string $default = null): ?string
    {
        $stmt = Database::get()->prepare('SELECT valor FROM configuracoes WHERE chave = ?');
        $stmt->execute([$chave]);
        $row = $stmt->fetch();
        if ($row !== false) {
            return $row['valor'];
        }
        return $default ?? self::DEFAULTS[$chave] ?? null;
    }

    public static function set(string $chave, string $valor): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT chave FROM configuracoes WHERE chave = ?');
        $stmt->execute([$chave]);
        if ($stmt->fetch()) {
            $upd = $pdo->prepare('UPDATE configuracoes SET valor = ? WHERE chave = ?');
            $upd->execute([$valor, $chave]);
        } else {
            $ins = $pdo->prepare('INSERT INTO configuracoes (chave, valor) VALUES (?, ?)');
            $ins->execute([$chave, $valor]);
        }
    }

    public static function pinAtivo(): bool
    {
        return self::get('pin_ativo') === '1';
    }

    public static function pinValor(): string
    {
        return (string) self::get('pin_valor', '');
    }

    public static function rodapeTexto(): string
    {
        return (string) self::get('rodape_texto');
    }
}
