<?php

final class MataMata
{
    /**
     * Mapa de propagação: número do jogo de origem => lista de destinos
     * (número do jogo, campo a preencher, se usa o vencedor ou o perdedor).
     */
    private const DEPENDENCIAS = [
        19 => [['destino' => 23, 'campo' => 'time1_id', 'resultado' => 'vencedor']],
        22 => [['destino' => 23, 'campo' => 'time2_id', 'resultado' => 'vencedor']],
        20 => [['destino' => 24, 'campo' => 'time1_id', 'resultado' => 'vencedor']],
        21 => [['destino' => 24, 'campo' => 'time2_id', 'resultado' => 'vencedor']],
        23 => [
            ['destino' => 26, 'campo' => 'time1_id', 'resultado' => 'vencedor'],
            ['destino' => 25, 'campo' => 'time1_id', 'resultado' => 'perdedor'],
        ],
        24 => [
            ['destino' => 26, 'campo' => 'time2_id', 'resultado' => 'vencedor'],
            ['destino' => 25, 'campo' => 'time2_id', 'resultado' => 'perdedor'],
        ],
    ];

    private const CONFRONTOS_QUARTAS = [
        ['numero' => 19, 'quadra' => 1, 'rodada' => 'leva1', 'rotulo' => 'Q1', 'posA' => 1, 'posB' => 8],
        ['numero' => 20, 'quadra' => 2, 'rodada' => 'leva1', 'rotulo' => 'Q2', 'posA' => 2, 'posB' => 7],
        ['numero' => 21, 'quadra' => 3, 'rodada' => 'leva1', 'rotulo' => 'Q3', 'posA' => 3, 'posB' => 6],
        ['numero' => 22, 'quadra' => 1, 'rodada' => 'leva2', 'rotulo' => 'Q4', 'posA' => 4, 'posB' => 5],
    ];

    public static function jaGerado(): bool
    {
        $stmt = Database::get()->query("SELECT COUNT(*) c FROM jogos WHERE fase != 'classificatoria'");
        return ((int) $stmt->fetch()['c']) > 0;
    }

    /** [bool podeGerar, ?string motivoSeNao] */
    public static function podeGerar(): array
    {
        if (self::jaGerado()) {
            return [false, 'O mata-mata já foi gerado.'];
        }
        if (!Ranking::classificatoriaCompleta()) {
            return [false, 'A fase classificatória ainda não terminou (os 18 jogos precisam estar encerrados).'];
        }
        $ranking = Ranking::calcular();
        if ($ranking['tem_empate_pendente']) {
            return [false, 'Há empate pendente na classificação geral. Resolva em "Desempate" antes de gerar o mata-mata.'];
        }
        return [true, null];
    }

    public static function gerar(): ?string
    {
        [$ok, $motivo] = self::podeGerar();
        if (!$ok) {
            return $motivo;
        }

        $ranking = Ranking::calcular();
        $porPosicao = [];
        foreach ($ranking['linhas'] as $linha) {
            $porPosicao[$linha['posicao']] = $linha;
        }

        $torneio = Torneio::get();
        $pdo = Database::get();
        $stmt = $pdo->prepare(
            'INSERT INTO jogos (numero, fase, rodada, quadra, horario_previsto, time1_id, time2_id, rotulo_slot, origem1, origem2, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach (self::CONFRONTOS_QUARTAS as $c) {
            $tA = $porPosicao[$c['posA']];
            $tB = $porPosicao[$c['posB']];
            $horario = Horarios::horarioPrevisto('quartas', $c['rodada'], $torneio['hora_inicio'], (int) $torneio['duracao_rodada_min']);
            $stmt->execute([
                $c['numero'], 'quartas', $c['rodada'], $c['quadra'], $horario,
                $tA['id'], $tB['id'], $c['rotulo'],
                $c['posA'] . 'º geral', $c['posB'] . 'º geral', 'pendente',
            ]);
        }

        $horarioSemi = Horarios::horarioPrevisto('semifinal', 'semifinal', $torneio['hora_inicio'], (int) $torneio['duracao_rodada_min']);
        $stmt->execute([23, 'semifinal', 'semifinal', 1, $horarioSemi, null, null, 'Semifinal 1', 'Vencedor Q1', 'Vencedor Q4', 'pendente']);
        $stmt->execute([24, 'semifinal', 'semifinal', 2, $horarioSemi, null, null, 'Semifinal 2', 'Vencedor Q2', 'Vencedor Q3', 'pendente']);

        $horarioFinal = Horarios::horarioPrevisto('final', 'final', $torneio['hora_inicio'], (int) $torneio['duracao_rodada_min']);
        $stmt->execute([25, 'terceiro_lugar', 'final', 2, $horarioFinal, null, null, 'Disputa de 3º lugar', 'Perdedor Semifinal 1', 'Perdedor Semifinal 2', 'pendente']);
        $stmt->execute([26, 'final', 'final', 1, $horarioFinal, null, null, 'Grande Final', 'Vencedor Semifinal 1', 'Vencedor Semifinal 2', 'pendente']);

        return null;
    }

    /** Propaga vencedor/perdedor de um jogo encerrado para os jogos dependentes. */
    public static function avancar(int $jogoId): void
    {
        $jogo = Jogos::porId($jogoId);
        if (!$jogo || $jogo['status'] !== 'encerrado') {
            return;
        }
        $numero = (int) $jogo['numero'];
        if (!isset(self::DEPENDENCIAS[$numero])) {
            return;
        }

        $vencedorId = (int) $jogo['pontos1'] > (int) $jogo['pontos2'] ? (int) $jogo['time1_id'] : (int) $jogo['time2_id'];
        $perdedorId = $vencedorId === (int) $jogo['time1_id'] ? (int) $jogo['time2_id'] : (int) $jogo['time1_id'];

        foreach (self::DEPENDENCIAS[$numero] as $dep) {
            $idTime = $dep['resultado'] === 'vencedor' ? $vencedorId : $perdedorId;
            $destino = Jogos::porNumero($dep['destino']);
            if (!$destino) {
                continue;
            }

            $valorAtual = $destino[$dep['campo']] !== null ? (int) $destino[$dep['campo']] : null;
            if ($valorAtual === $idTime) {
                continue;
            }

            $campo = $dep['campo'] === 'time2_id' ? 'time2_id' : 'time1_id';
            $stmt = Database::get()->prepare("UPDATE jogos SET {$campo} = ? WHERE id = ?");
            $stmt->execute([$idTime, $destino['id']]);

            if ($destino['status'] === 'encerrado') {
                // O time que alimentava esse slot mudou: o resultado registrado não é mais válido.
                Jogos::reabrir((int) $destino['id'], 'sistema (recálculo automático)');
            }
        }
    }

    /** Limpa e reabre em cascata os jogos que dependiam do jogo informado. */
    public static function recalcularDependentes(int $jogoId): void
    {
        $jogo = Jogos::porId($jogoId);
        if (!$jogo) {
            return;
        }
        $numero = (int) $jogo['numero'];
        if (!isset(self::DEPENDENCIAS[$numero])) {
            return;
        }

        foreach (self::DEPENDENCIAS[$numero] as $dep) {
            $destino = Jogos::porNumero($dep['destino']);
            if (!$destino) {
                continue;
            }

            $campo = $dep['campo'] === 'time2_id' ? 'time2_id' : 'time1_id';
            $stmt = Database::get()->prepare("UPDATE jogos SET {$campo} = NULL WHERE id = ?");
            $stmt->execute([$destino['id']]);

            if ($destino['status'] === 'encerrado') {
                Jogos::reabrir((int) $destino['id'], 'sistema (recálculo automático)');
            } else {
                self::recalcularDependentes((int) $destino['id']);
            }
        }
    }

    public static function podio(): ?array
    {
        $final = Jogos::porNumero(26);
        $terceiro = Jogos::porNumero(25);
        if (!$final || $final['status'] !== 'encerrado' || !$terceiro || $terceiro['status'] !== 'encerrado') {
            return null;
        }
        $times = Times::mapaPorId();

        $campeaoId = (int) $final['pontos1'] > (int) $final['pontos2'] ? (int) $final['time1_id'] : (int) $final['time2_id'];
        $viceId = $campeaoId === (int) $final['time1_id'] ? (int) $final['time2_id'] : (int) $final['time1_id'];
        $terceiroId = (int) $terceiro['pontos1'] > (int) $terceiro['pontos2'] ? (int) $terceiro['time1_id'] : (int) $terceiro['time2_id'];

        return [
            'campeao' => $times[$campeaoId] ?? null,
            'vice' => $times[$viceId] ?? null,
            'terceiro' => $times[$terceiroId] ?? null,
        ];
    }
}
