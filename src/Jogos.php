<?php

final class Jogos
{
    /** Calendário fixo da fase classificatória (seção 5.1 do briefing). */
    private const CALENDARIO = [
        ['rodada' => 1, 'quadra' => 1, 't1' => 'T1', 't2' => 'T2'],
        ['rodada' => 1, 'quadra' => 2, 't1' => 'T4', 't2' => 'T5'],
        ['rodada' => 1, 'quadra' => 3, 't1' => 'T7', 't2' => 'T8'],
        ['rodada' => 2, 'quadra' => 1, 't1' => 'T2', 't2' => 'T3'],
        ['rodada' => 2, 'quadra' => 2, 't1' => 'T5', 't2' => 'T6'],
        ['rodada' => 2, 'quadra' => 3, 't1' => 'T8', 't2' => 'T9'],
        ['rodada' => 3, 'quadra' => 1, 't1' => 'T1', 't2' => 'T3'],
        ['rodada' => 3, 'quadra' => 2, 't1' => 'T4', 't2' => 'T6'],
        ['rodada' => 3, 'quadra' => 3, 't1' => 'T7', 't2' => 'T9'],
        ['rodada' => 4, 'quadra' => 1, 't1' => 'T1', 't2' => 'T4'],
        ['rodada' => 4, 'quadra' => 2, 't1' => 'T2', 't2' => 'T5'],
        ['rodada' => 4, 'quadra' => 3, 't1' => 'T3', 't2' => 'T6'],
        ['rodada' => 5, 'quadra' => 1, 't1' => 'T4', 't2' => 'T7'],
        ['rodada' => 5, 'quadra' => 2, 't1' => 'T5', 't2' => 'T8'],
        ['rodada' => 5, 'quadra' => 3, 't1' => 'T6', 't2' => 'T9'],
        ['rodada' => 6, 'quadra' => 1, 't1' => 'T1', 't2' => 'T7'],
        ['rodada' => 6, 'quadra' => 2, 't1' => 'T2', 't2' => 'T8'],
        ['rodada' => 6, 'quadra' => 3, 't1' => 'T3', 't2' => 'T9'],
    ];

    public static function calendarioFixo(): array
    {
        return self::CALENDARIO;
    }

    public static function calendarioJaCarregado(): bool
    {
        $stmt = Database::get()->prepare("SELECT COUNT(*) c FROM jogos WHERE fase = 'classificatoria'");
        $stmt->execute();
        return ((int) $stmt->fetch()['c']) > 0;
    }

    public static function carregarCalendarioClassificatoria(): void
    {
        if (self::calendarioJaCarregado()) {
            return;
        }

        $pdo = Database::get();
        $codigos = Times::todos();
        $porCodigo = [];
        foreach ($codigos as $t) {
            $porCodigo[$t['codigo']] = (int) $t['id'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO jogos (numero, fase, rodada, quadra, time1_id, time2_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        $numero = 1;
        foreach (self::CALENDARIO as $jogo) {
            $stmt->execute([
                $numero,
                'classificatoria',
                (string) $jogo['rodada'],
                $jogo['quadra'],
                $porCodigo[$jogo['t1']] ?? null,
                $porCodigo[$jogo['t2']] ?? null,
                'pendente',
            ]);
            $numero++;
        }
    }

    /** Times que folgam em cada rodada da classificatória (calculado, não digitado). */
    public static function folgasPorRodada(): array
    {
        $todos = array_column(Times::todos(), 'codigo', 'id');
        $folgas = [];
        for ($r = 1; $r <= 6; $r++) {
            $jogam = [];
            foreach (self::listar(['fase' => 'classificatoria', 'rodada' => (string) $r]) as $jogo) {
                if ($jogo['time1_id']) {
                    $jogam[(int) $jogo['time1_id']] = true;
                }
                if ($jogo['time2_id']) {
                    $jogam[(int) $jogo['time2_id']] = true;
                }
            }
            $folgas[$r] = [];
            foreach ($todos as $id => $codigo) {
                if (!isset($jogam[$id])) {
                    $folgas[$r][] = $codigo;
                }
            }
        }
        return $folgas;
    }

    public static function porId(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM jogos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function porNumero(int $numero): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM jogos WHERE numero = ?');
        $stmt->execute([$numero]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM jogos WHERE 1=1';
        $params = [];

        if (!empty($filtros['fase'])) {
            $sql .= ' AND fase = ?';
            $params[] = $filtros['fase'];
        }
        if (!empty($filtros['rodada'])) {
            $sql .= ' AND rodada = ?';
            $params[] = (string) $filtros['rodada'];
        }
        if (!empty($filtros['quadra'])) {
            $sql .= ' AND quadra = ?';
            $params[] = (int) $filtros['quadra'];
        }
        if (!empty($filtros['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filtros['status'];
        }

        $sql .= ' ORDER BY numero';

        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);
        $jogos = $stmt->fetchAll();

        if (!empty($filtros['busca'])) {
            $times = Times::mapaPorId();
            $busca = mb_strtolower($filtros['busca']);
            $jogos = array_values(array_filter($jogos, function ($jogo) use ($times, $busca) {
                $n1 = isset($times[(int) $jogo['time1_id']]) ? mb_strtolower($times[(int) $jogo['time1_id']]['nome']) : '';
                $n2 = isset($times[(int) $jogo['time2_id']]) ? mb_strtolower($times[(int) $jogo['time2_id']]['nome']) : '';
                return str_contains($n1, $busca) || str_contains($n2, $busca);
            }));
        }

        return $jogos;
    }

    public static function contarPorFase(string $fase): array
    {
        $stmt = Database::get()->prepare("SELECT COUNT(*) total, SUM(status = 'encerrado') encerrados FROM jogos WHERE fase = ?");
        $stmt->execute([$fase]);
        $r = $stmt->fetch();
        return ['total' => (int) ($r['total'] ?? 0), 'encerrados' => (int) ($r['encerrados'] ?? 0)];
    }

    public static function contarMataMata(): array
    {
        $stmt = Database::get()->query("SELECT COUNT(*) total, SUM(status = 'encerrado') encerrados FROM jogos WHERE fase != 'classificatoria'");
        $r = $stmt->fetch();
        return ['total' => (int) ($r['total'] ?? 0), 'encerrados' => (int) ($r['encerrados'] ?? 0)];
    }

    /**
     * Salva o resultado de um jogo pendente (uso público/admin).
     * Retorna null em caso de sucesso ou uma mensagem de erro.
     */
    public static function salvarResultado(int $jogoId, int $pontos1, int $pontos2, ?string $usuario): ?string
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM jogos WHERE id = ?');
            $stmt->execute([$jogoId]);
            $jogo = $stmt->fetch();

            if (!$jogo) {
                $pdo->rollBack();
                return 'Jogo não encontrado.';
            }
            if ($jogo['status'] === 'encerrado') {
                $pdo->rollBack();
                return 'Este jogo já foi encerrado por outra pessoa. Atualize a página.';
            }
            if (!$jogo['time1_id'] || !$jogo['time2_id']) {
                $pdo->rollBack();
                return 'Os times deste jogo ainda não foram definidos.';
            }

            $erro = Validacao::validarPlacar($pontos1, $pontos2);
            if ($erro !== null) {
                $pdo->rollBack();
                return $erro;
            }

            $agora = date('Y-m-d H:i:s');
            $upd = $pdo->prepare(
                "UPDATE jogos SET pontos1 = ?, pontos2 = ?, status = 'encerrado', encerrado_em = ? WHERE id = ? AND status = 'pendente'"
            );
            $upd->execute([$pontos1, $pontos2, $agora, $jogoId]);

            if ($upd->rowCount() === 0) {
                $pdo->rollBack();
                return 'Este jogo já foi encerrado por outra pessoa. Atualize a página.';
            }

            Log::registrar($jogoId, 'pontos', '—', "{$pontos1} x {$pontos2}", $usuario);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Torneio::atualizarStatusAutomatico();
        if ($jogo['fase'] !== 'classificatoria') {
            MataMata::avancar($jogoId);
        }

        return null;
    }

    /** Correção pelo administrador: reescreve um placar já encerrado. */
    public static function corrigirResultado(int $jogoId, int $pontos1, int $pontos2, ?string $usuario): ?string
    {
        $jogo = self::porId($jogoId);
        if (!$jogo) {
            return 'Jogo não encontrado.';
        }
        $erro = Validacao::validarPlacar($pontos1, $pontos2);
        if ($erro !== null) {
            return $erro;
        }

        $anterior = $jogo['status'] === 'encerrado' ? "{$jogo['pontos1']} x {$jogo['pontos2']}" : '—';
        $pdo = Database::get();
        $upd = $pdo->prepare(
            "UPDATE jogos SET pontos1 = ?, pontos2 = ?, status = 'encerrado', encerrado_em = ? WHERE id = ?"
        );
        $upd->execute([$pontos1, $pontos2, $jogo['encerrado_em'] ?? date('Y-m-d H:i:s'), $jogoId]);

        Log::registrar($jogoId, 'pontos (correção admin)', $anterior, "{$pontos1} x {$pontos2}", $usuario);

        Torneio::atualizarStatusAutomatico();
        if ($jogo['fase'] !== 'classificatoria') {
            MataMata::avancar($jogoId);
        }

        return null;
    }

    public static function reabrir(int $jogoId, ?string $usuario): void
    {
        $jogo = self::porId($jogoId);
        if (!$jogo) {
            return;
        }
        $anterior = $jogo['status'] === 'encerrado' ? "{$jogo['pontos1']} x {$jogo['pontos2']}" : '—';
        $stmt = Database::get()->prepare("UPDATE jogos SET status = 'pendente', pontos1 = NULL, pontos2 = NULL, encerrado_em = NULL WHERE id = ?");
        $stmt->execute([$jogoId]);
        Log::registrar($jogoId, 'reabertura', $anterior, 'pendente', $usuario);

        if ($jogo['fase'] !== 'classificatoria') {
            MataMata::recalcularDependentes($jogoId);
        }
        Torneio::atualizarStatusAutomatico();
    }

    public static function ajustarQuadra(int $jogoId, int $quadra, ?string $usuario): void
    {
        $jogo = self::porId($jogoId);
        if (!$jogo) {
            return;
        }
        $stmt = Database::get()->prepare('UPDATE jogos SET quadra = ? WHERE id = ?');
        $stmt->execute([$quadra, $jogoId]);
        Log::registrar($jogoId, 'quadra', "Q{$jogo['quadra']}", "Q{$quadra}", $usuario);
    }

    public static function excluirTodos(): void
    {
        Database::get()->exec('DELETE FROM jogos');
    }
}
