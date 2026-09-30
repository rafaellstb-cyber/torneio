<?php

declare(strict_types=1);

require_once __DIR__ . '/harness.php';

T::secao('Geração e avanço automático do mata-mata');

Database::resetForTests();
Times::cadastrarPadrao();
Jogos::carregarCalendarioClassificatoria();

// Completa a classificatória com margens variadas (margem = número do jogo)
// para garantir uma ordem 1º-9º totalmente distinta, sem empates pendentes.
foreach (Jogos::listar(['fase' => 'classificatoria']) as $jogo) {
    $margem = (int) $jogo['numero'] + 1;
    $erro = Jogos::salvarResultado((int) $jogo['id'], 21, 21 - $margem, 'teste');
    T::ok($erro === null, "Jogo {$jogo['numero']} salvo sem erro" . ($erro ? " ({$erro})" : ''));
}

$ranking = Ranking::calcular();
T::ok(!$ranking['tem_empate_pendente'], 'Classificação final não tem empates pendentes');

$ordemEsperada = ['T1', 'T2', 'T4', 'T3', 'T5', 'T7', 'T6', 'T8', 'T9'];
$ordemObtida = array_map(fn ($l) => $l['codigo'], $ranking['linhas']);
T::igual($ordemEsperada, $ordemObtida, 'Ordem geral confere com o cálculo esperado (V > saldo)');

[$pode, $motivo] = MataMata::podeGerar();
T::ok($pode, 'Mata-mata pode ser gerado com a classificatória completa: ' . ($motivo ?? ''));

$erroGerar = MataMata::gerar();
T::ok($erroGerar === null, 'Geração do mata-mata sem erro');

$times = Times::mapaPorId();
$cod = fn ($id) => $times[(int) $id]['codigo'];

$q1 = Jogos::porNumero(19);
$q2 = Jogos::porNumero(20);
$q3 = Jogos::porNumero(21);
$q4 = Jogos::porNumero(22);

T::igual('T1', $cod($q1['time1_id']), 'Q1: 1º geral (T1) definido');
T::igual('T8', $cod($q1['time2_id']), 'Q1: 8º geral (T8) definido');
T::igual('T2', $cod($q2['time1_id']), 'Q2: 2º geral (T2) definido');
T::igual('T6', $cod($q2['time2_id']), 'Q2: 7º geral (T6) definido');
T::igual('T4', $cod($q3['time1_id']), 'Q3: 3º geral (T4) definido');
T::igual('T7', $cod($q3['time2_id']), 'Q3: 6º geral (T7) definido');
T::igual('T3', $cod($q4['time1_id']), 'Q4: 4º geral (T3) definido');
T::igual('T5', $cod($q4['time2_id']), 'Q4: 5º geral (T5) definido');

$sf1Antes = Jogos::porNumero(23);
T::ok($sf1Antes['time1_id'] === null && $sf1Antes['time2_id'] === null, 'Semifinal 1 começa com "a definir"');

// Quartas: o time1 (melhor posição) vence em todos os jogos.
foreach ([19, 20, 21, 22] as $numero) {
    $jogo = Jogos::porNumero($numero);
    Jogos::salvarResultado((int) $jogo['id'], 21, 10, 'teste');
}

$sf1 = Jogos::porNumero(23);
$sf2 = Jogos::porNumero(24);
T::igual('T1', $cod($sf1['time1_id']), 'Semifinal 1 recebe o vencedor de Q1 (T1)');
T::igual('T3', $cod($sf1['time2_id']), 'Semifinal 1 recebe o vencedor de Q4 (T3)');
T::igual('T2', $cod($sf2['time1_id']), 'Semifinal 2 recebe o vencedor de Q2 (T2)');
T::igual('T4', $cod($sf2['time2_id']), 'Semifinal 2 recebe o vencedor de Q3 (T4)');

// Semifinais: o time1 vence em ambas.
Jogos::salvarResultado((int) $sf1['id'], 21, 10, 'teste');
Jogos::salvarResultado((int) $sf2['id'], 21, 10, 'teste');

$final = Jogos::porNumero(26);
$terceiro = Jogos::porNumero(25);
T::igual('T1', $cod($final['time1_id']), 'Grande Final recebe o vencedor da Semifinal 1 (T1)');
T::igual('T2', $cod($final['time2_id']), 'Grande Final recebe o vencedor da Semifinal 2 (T2)');
T::igual('T3', $cod($terceiro['time1_id']), 'Disputa de 3º recebe o perdedor da Semifinal 1 (T3)');
T::igual('T4', $cod($terceiro['time2_id']), 'Disputa de 3º recebe o perdedor da Semifinal 2 (T4)');

T::ok(MataMata::podio() === null, 'Pódio ainda não existe (final/3º não encerrados)');

Jogos::salvarResultado((int) $final['id'], 21, 10, 'teste');
Jogos::salvarResultado((int) $terceiro['id'], 21, 10, 'teste');

$podio = MataMata::podio();
T::ok($podio !== null, 'Pódio calculado após final e disputa de 3º lugar');
T::igual('T1', $podio['campeao']['codigo'], 'Campeão é T1');
T::igual('T2', $podio['vice']['codigo'], 'Vice-campeão é T2');
T::igual('T3', $podio['terceiro']['codigo'], 'Terceiro colocado é T3');

T::secao('Correção/reabertura em cascata (mata-mata)');

// Reabre Q1: deve reabrir Semifinal 1, a Final e a Disputa de 3º em cascata.
Jogos::reabrir((int) $q1['id'], 'admin-teste');

$q1Depois = Jogos::porNumero(19);
$sf1Depois = Jogos::porNumero(23);
$finalDepois = Jogos::porNumero(26);
$terceiroDepois = Jogos::porNumero(25);
$sf2Depois = Jogos::porNumero(24);

T::igual('pendente', $q1Depois['status'], 'Q1 volta a ficar pendente');
T::igual('pendente', $sf1Depois['status'], 'Semifinal 1 é reaberta em cascata');
T::ok($sf1Depois['time1_id'] === null, 'Semifinal 1 perde o time que vinha de Q1');
T::igual('T3', $cod($sf1Depois['time2_id']), 'Semifinal 1 mantém o time que vinha de Q4 (T3)');
T::igual('pendente', $finalDepois['status'], 'Grande Final é reaberta em cascata');
T::ok($finalDepois['time1_id'] === null, 'Grande Final perde o time que vinha da Semifinal 1');
T::igual('T2', $cod($finalDepois['time2_id']), 'Grande Final mantém o time da Semifinal 2 (T2)');
T::igual('pendente', $terceiroDepois['status'], 'Disputa de 3º é reaberta em cascata');
T::ok($terceiroDepois['time1_id'] === null, 'Disputa de 3º perde o time que vinha da Semifinal 1');
T::igual('encerrado', $sf2Depois['status'], 'Semifinal 2 não é afetada pela reabertura de Q1');

exit(T::resumo());
