<?php

final class Torneio
{
    public static function get(): array
    {
        $pdo = Database::get();
        $row = $pdo->query('SELECT * FROM torneio ORDER BY id LIMIT 1')->fetch();
        if (!$row) {
            $stmt = $pdo->prepare(
                'INSERT INTO torneio (nome, data, local, hora_inicio, status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute(['3º Torneio Entre Amigos', date('Y-m-d'), 'NOME DO LOCAL', '14:00', 'nao_iniciado']);
            $row = $pdo->query('SELECT * FROM torneio ORDER BY id LIMIT 1')->fetch();
        }
        return $row;
    }

    public static function update(array $dados): void
    {
        $atual = self::get();
        $campos = ['nome', 'data', 'local', 'hora_inicio', 'status'];
        $valores = [];
        foreach ($campos as $campo) {
            $valores[$campo] = $dados[$campo] ?? $atual[$campo];
        }
        $stmt = Database::get()->prepare(
            'UPDATE torneio SET nome = ?, data = ?, local = ?, hora_inicio = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([
            $valores['nome'],
            $valores['data'],
            $valores['local'],
            $valores['hora_inicio'],
            $valores['status'],
            $atual['id'],
        ]);
    }

    public static function reiniciar(): void
    {
        $pdo = Database::get();
        $pdo->exec('DELETE FROM log_alteracoes');
        $pdo->exec('DELETE FROM jogos');
        $pdo->exec('UPDATE times SET desempate_manual = NULL');
        $pdo->exec("UPDATE torneio SET status = 'nao_iniciado'");
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'em_andamento' => 'Em andamento',
            'finalizado' => 'Finalizado',
            default => 'Não iniciado',
        };
    }

    public static function atualizarStatusAutomatico(): void
    {
        $pdo = Database::get();
        $total = (int) $pdo->query('SELECT COUNT(*) c FROM jogos')->fetch()['c'];
        if ($total === 0) {
            return;
        }
        $encerrados = (int) $pdo->query("SELECT COUNT(*) c FROM jogos WHERE status = 'encerrado'")->fetch()['c'];
        $final = $pdo->query("SELECT * FROM jogos WHERE fase = 'final'")->fetch();

        $atual = self::get();
        $novoStatus = $atual['status'];

        if ($final && $final['status'] === 'encerrado') {
            $novoStatus = 'finalizado';
        } elseif ($encerrados > 0) {
            $novoStatus = 'em_andamento';
        }

        if ($novoStatus !== $atual['status']) {
            $stmt = $pdo->prepare('UPDATE torneio SET status = ? WHERE id = ?');
            $stmt->execute([$novoStatus, $atual['id']]);
        }
    }
}
