<?php

final class Horarios
{
    /**
     * Índice do bloco de horário de cada etapa. A classificatória usa um
     * bloco por rodada (0 a 5); o mata-mata usa os blocos seguintes.
     */
    public static function blocoDaEtapa(string $fase, ?string $rodada): int
    {
        if ($fase === 'classificatoria') {
            return ((int) $rodada) - 1;
        }
        return match (true) {
            $fase === 'quartas' && $rodada === 'leva1' => 6,
            $fase === 'quartas' && $rodada === 'leva2' => 7,
            $fase === 'semifinal' => 8,
            $fase === 'final' || $fase === 'terceiro_lugar' => 9,
            default => 9,
        };
    }

    public static function horaDoBloco(int $bloco, string $horaInicio, int $duracaoMin): string
    {
        [$h, $m] = array_map('intval', explode(':', $horaInicio));
        $minutosTotais = $h * 60 + $m + $bloco * $duracaoMin;
        $minutosTotais = $minutosTotais % (24 * 60);
        $novaHora = intdiv($minutosTotais, 60);
        $novoMinuto = $minutosTotais % 60;
        return sprintf('%02d:%02d', $novaHora, $novoMinuto);
    }

    public static function horarioPrevisto(string $fase, ?string $rodada, string $horaInicio, int $duracaoMin): string
    {
        $bloco = self::blocoDaEtapa($fase, $rodada);
        return self::horaDoBloco($bloco, $horaInicio, $duracaoMin);
    }

    /** Recalcula o horário previsto de todos os jogos (ex.: após mudar hora de início/duração). */
    public static function recalcularTodos(): void
    {
        $torneio = Torneio::get();
        $pdo = Database::get();
        $jogos = $pdo->query('SELECT id, fase, rodada FROM jogos')->fetchAll();
        $stmt = $pdo->prepare('UPDATE jogos SET horario_previsto = ? WHERE id = ?');
        foreach ($jogos as $jogo) {
            $horario = self::horarioPrevisto($jogo['fase'], $jogo['rodada'], $torneio['hora_inicio'], (int) $torneio['duracao_rodada_min']);
            $stmt->execute([$horario, $jogo['id']]);
        }
    }
}
