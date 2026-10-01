<?php

final class Log
{
    public static function registrar(int $jogoId, string $campo, ?string $valorAnterior, ?string $valorNovo, ?string $usuario): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO log_alteracoes (jogo_id, usuario, campo, valor_anterior, valor_novo, criado_em)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$jogoId, $usuario, $campo, $valorAnterior, $valorNovo, date('Y-m-d H:i:s')]);
    }

    public static function doJogo(int $jogoId): array
    {
        $stmt = Database::get()->prepare('SELECT * FROM log_alteracoes WHERE jogo_id = ? ORDER BY id DESC');
        $stmt->execute([$jogoId]);
        return $stmt->fetchAll();
    }

    public static function recentes(int $limite = 50): array
    {
        $stmt = Database::get()->prepare('SELECT * FROM log_alteracoes ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
