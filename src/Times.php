<?php

final class Times
{
    public static function todos(): array
    {
        return Database::get()->query('SELECT * FROM times ORDER BY id')->fetchAll();
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM times WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function mapaPorId(): array
    {
        $mapa = [];
        foreach (self::todos() as $t) {
            $mapa[(int) $t['id']] = $t;
        }
        return $mapa;
    }

    public static function cadastrarPadrao(): void
    {
        $pdo = Database::get();
        $existentes = (int) $pdo->query('SELECT COUNT(*) c FROM times')->fetch()['c'];
        if ($existentes > 0) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO times (codigo, nome) VALUES (?, ?)');
        for ($i = 1; $i <= 9; $i++) {
            $codigo = 'T' . $i;
            $stmt->execute([$codigo, $codigo]);
        }
    }

    public static function atualizar(int $id, string $nome, ?string $atletas): void
    {
        $stmt = Database::get()->prepare('UPDATE times SET nome = ?, atletas = ? WHERE id = ?');
        $stmt->execute([$nome, $atletas, $id]);
    }

    public static function definirDesempateManual(int $id, ?int $posicao): void
    {
        $stmt = Database::get()->prepare('UPDATE times SET desempate_manual = ? WHERE id = ?');
        $stmt->execute([$posicao, $id]);
    }

    public static function porCodigo(string $codigo): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM times WHERE codigo = ?');
        $stmt->execute([$codigo]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
