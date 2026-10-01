<?php

declare(strict_types=1);

/**
 * Simula resultados válidos para os jogos pendentes.
 *
 * Uso:
 *   php scripts/simular.php              → resultados aleatórios (classificatória + mata-mata)
 *   php scripts/simular.php --empates     → margens fixas que geram empates pendentes de propósito
 */

require __DIR__ . '/../public/src/bootstrap.php';

$modoEmpates = in_array('--empates', $argv, true);

function pontosAleatorios(): array
{
    if (mt_rand(1, 10) <= 2) {
        $vencedor = 21 + mt_rand(1, 3);
        $perdedor = $vencedor - 2;
    } else {
        $vencedor = 21;
        $perdedor = mt_rand(0, 19);
    }
    return [$vencedor, $perdedor];
}

function simularFase(string $fase, bool $modoEmpates): void
{
    foreach (Jogos::listar(['fase' => $fase, 'status' => 'pendente']) as $jogo) {
        if (!$jogo['time1_id'] || !$jogo['time2_id']) {
            continue;
        }

        if ($modoEmpates && $fase === 'classificatoria') {
            // Margem fixa (21x15) com vitória sempre do time de menor id:
            // times com o mesmo número de vitórias terminam com saldo e
            // pontos pró idênticos, criando empates propositalmente.
            $vencePrimeiro = (int) $jogo['time1_id'] < (int) $jogo['time2_id'];
            [$p1, $p2] = $vencePrimeiro ? [21, 15] : [15, 21];
        } else {
            [$pv, $pp] = pontosAleatorios();
            $primeiroVence = mt_rand(0, 1) === 1;
            [$p1, $p2] = $primeiroVence ? [$pv, $pp] : [$pp, $pv];
        }

        $erro = Jogos::salvarResultado((int) $jogo['id'], $p1, $p2, 'simulação');
        echo "Jogo {$jogo['numero']}: {$p1} x {$p2}" . ($erro ? " -> ERRO: {$erro}" : '') . "\n";
    }
}

if (!Jogos::calendarioJaCarregado()) {
    echo "Calendário não carregado ainda. Execute scripts/seed.php primeiro.\n";
    exit(1);
}

echo "Simulando fase classificatória" . ($modoEmpates ? ' (modo empates)' : '') . "...\n";
simularFase('classificatoria', $modoEmpates);

$ranking = Ranking::calcular();
if ($ranking['tem_empate_pendente']) {
    echo "\nHá empate(s) pendente(s) na classificação geral (esperado no modo --empates).\n";
    echo "Resolva manualmente em Admin > Desempate antes de gerar o mata-mata.\n";
    exit(0);
}

if (!MataMata::jaGerado()) {
    echo "Gerando mata-mata...\n";
    $erro = MataMata::gerar();
    if ($erro !== null) {
        echo "Não foi possível gerar o mata-mata: {$erro}\n";
        exit(0);
    }
}

foreach (['quartas', 'semifinal', 'terceiro_lugar', 'final'] as $fase) {
    // Precisa simular fase a fase, pois o avanço de uma alimenta a próxima.
    $seguiu = true;
    while ($seguiu) {
        $pendentesComTimes = array_filter(
            Jogos::listar(['fase' => $fase, 'status' => 'pendente']),
            fn ($j) => $j['time1_id'] && $j['time2_id']
        );
        if (!$pendentesComTimes) {
            $seguiu = false;
            break;
        }
        echo "Simulando fase '{$fase}'...\n";
        simularFase($fase, false);
    }
}

$podio = MataMata::podio();
if ($podio) {
    echo "\nPódio final:\n";
    echo "  Campeão: {$podio['campeao']['nome']}\n";
    echo "  Vice: {$podio['vice']['nome']}\n";
    echo "  3º lugar: {$podio['terceiro']['nome']}\n";
}

echo "\nSimulação concluída.\n";
