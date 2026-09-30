<?php

declare(strict_types=1);

require_once __DIR__ . '/harness.php';

T::secao('Calendário fixo da fase classificatória');

Database::resetForTests();
Times::cadastrarPadrao();
Jogos::carregarCalendarioClassificatoria();

$jogos = Jogos::listar(['fase' => 'classificatoria']);
T::igual(18, count($jogos), 'Devem existir exatamente 18 jogos na classificatória');

$numeros = array_map(fn ($j) => (int) $j['numero'], $jogos);
sort($numeros);
T::igual(range(1, 18), $numeros, 'Numeração sequencial de 1 a 18');

$jogosPorTime = [];
$jogosPorRodadaETime = [];
foreach ($jogos as $j) {
    $rodada = $j['rodada'];
    foreach (['time1_id', 'time2_id'] as $campo) {
        $id = (int) $j[$campo];
        $jogosPorTime[$id] = ($jogosPorTime[$id] ?? 0) + 1;
        $jogosPorRodadaETime[$rodada][$id] = ($jogosPorRodadaETime[$rodada][$id] ?? 0) + 1;
    }
}

$todos4 = true;
foreach (Times::todos() as $t) {
    $qtd = $jogosPorTime[(int) $t['id']] ?? 0;
    if ($qtd !== 4) {
        $todos4 = false;
        echo "  -> {$t['codigo']} tem {$qtd} jogos (esperado 4)\n";
    }
}
T::ok($todos4, 'Cada time joga exatamente 4 vezes na classificatória');

$semRepeticao = true;
foreach ($jogosPorRodadaETime as $rodada => $contagens) {
    foreach ($contagens as $idTime => $qtd) {
        if ($qtd > 1) {
            $semRepeticao = false;
            echo "  -> Time id {$idTime} joga {$qtd} vezes na rodada {$rodada}\n";
        }
    }
}
T::ok($semRepeticao, 'Nenhum time joga duas vezes na mesma rodada');

$folgas = Jogos::folgasPorRodada();
T::igual(6, count($folgas), 'Existem 6 rodadas com folgas calculadas');
foreach ($folgas as $rodada => $lista) {
    T::ok(count($lista) === 3, "Rodada {$rodada} tem 3 times de folga (calculado: " . implode(',', $lista) . ')');
}

// Confere o conteúdo exato do calendário do briefing (rodada 1 e rodada 6, por amostragem).
$porRodadaQuadra = [];
foreach ($jogos as $j) {
    $times = Times::mapaPorId();
    $c1 = $times[(int) $j['time1_id']]['codigo'];
    $c2 = $times[(int) $j['time2_id']]['codigo'];
    $porRodadaQuadra[$j['rodada']][(int) $j['quadra']] = "{$c1} x {$c2}";
}
T::igual('T1 x T2', $porRodadaQuadra['1'][1], 'Rodada 1, Quadra 1 = T1 x T2');
T::igual('T4 x T5', $porRodadaQuadra['1'][2], 'Rodada 1, Quadra 2 = T4 x T5');
T::igual('T7 x T8', $porRodadaQuadra['1'][3], 'Rodada 1, Quadra 3 = T7 x T8');
T::igual('T1 x T7', $porRodadaQuadra['6'][1], 'Rodada 6, Quadra 1 = T1 x T7');
T::igual('T2 x T8', $porRodadaQuadra['6'][2], 'Rodada 6, Quadra 2 = T2 x T8');
T::igual('T3 x T9', $porRodadaQuadra['6'][3], 'Rodada 6, Quadra 3 = T3 x T9');

exit(T::resumo());
