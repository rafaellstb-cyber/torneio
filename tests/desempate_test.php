<?php

declare(strict_types=1);

require_once __DIR__ . '/harness.php';

function novoBanco(): array
{
    Database::resetForTests();
    Times::cadastrarPadrao();
    $mapa = [];
    foreach (Times::todos() as $t) {
        $mapa[$t['codigo']] = (int) $t['id'];
    }
    return $mapa;
}

function inserirJogoEncerrado(int $numero, int $t1, int $t2, int $p1, int $p2): void
{
    $stmt = Database::get()->prepare(
        "INSERT INTO jogos (numero, fase, rodada, quadra, time1_id, time2_id, pontos1, pontos2, status, encerrado_em)
         VALUES (?, 'classificatoria', '1', 1, ?, ?, ?, ?, 'encerrado', ?)"
    );
    $stmt->execute([$numero, $t1, $t2, $p1, $p2, date('Y-m-d H:i:s')]);
}

function linhaDoCodigo(array $linhas, string $codigo): array
{
    foreach ($linhas as $l) {
        if ($l['codigo'] === $codigo) {
            return $l;
        }
    }
    throw new RuntimeException("Time {$codigo} não encontrado no ranking");
}

// --- 1. Mais vitórias decide a ordem -----------------------------------
T::secao('Critério 1: mais vitórias');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T2'], 21, 10);
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT2 = linhaDoCodigo($r['linhas'], 'T2');
T::ok($linT1['posicao'] < $linT2['posicao'], 'T1 (vencedor) fica acima de T2 (perdedor)');
T::igual(1, $linT1['posicao'], 'T1 fica em 1º (única vitória do grupo)');
T::igual(9, $linT2['posicao'], 'T2 fica em último (saldo negativo isolado)');

// --- 2. Saldo de pontos decide quando as vitórias empatam ---------------
T::secao('Critério 2: saldo de pontos (quando V empata)');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T2'], 21, 10); // T1: V1 SP11
inserirJogoEncerrado(2, $t['T3'], $t['T4'], 21, 15); // T3: V1 SP6
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT3 = linhaDoCodigo($r['linhas'], 'T3');
T::ok($linT1['posicao'] < $linT3['posicao'], 'T1 (saldo 11) fica acima de T3 (saldo 6), mesmo com 1 vitória cada');

// --- 3. Pontos pró decide quando V e saldo empatam -----------------------
T::secao('Critério 3: pontos pró (quando V e saldo empatam)');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T2'], 21, 19); // T1: V1 SP2 PP21
inserirJogoEncerrado(2, $t['T3'], $t['T4'], 23, 21); // T3: V1 SP2 PP23
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT3 = linhaDoCodigo($r['linhas'], 'T3');
T::igual($linT1['v'], $linT3['v'], 'T1 e T3 têm o mesmo número de vitórias');
T::igual($linT1['sp'], $linT3['sp'], 'T1 e T3 têm o mesmo saldo de pontos');
T::ok($linT3['posicao'] < $linT1['posicao'], 'T3 (23 pontos pró) fica acima de T1 (21 pontos pró)');

// --- 4. Confronto direto resolve empate de 2 times que se enfrentaram ---
T::secao('Critério 4: confronto direto (2 times empatados que jogaram entre si)');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T2'], 21, 19); // confronto direto: T1 vence T2
inserirJogoEncerrado(2, $t['T7'], $t['T1'], 21, 19); // T1 perde, fecha com V1 D1 SP0 PP40 PC40
inserirJogoEncerrado(3, $t['T2'], $t['T8'], 21, 19); // T2 vence, fecha com V1 D1 SP0 PP40 PC40
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT2 = linhaDoCodigo($r['linhas'], 'T2');
T::igual($linT1['v'], $linT2['v'], 'T1 e T2 têm o mesmo número de vitórias (1)');
T::igual($linT1['sp'], $linT2['sp'], 'T1 e T2 têm o mesmo saldo (0)');
T::igual($linT1['pp'], $linT2['pp'], 'T1 e T2 têm os mesmos pontos pró (40)');
T::ok(!$linT1['pendente'] && !$linT2['pendente'], 'O empate é resolvido (não fica pendente)');
T::ok($linT1['posicao'] < $linT2['posicao'], 'T1 (venceu o confronto direto) fica acima de T2');

// --- 5. Empate pendente: 2 times empatados que NÃO se enfrentaram -------
T::secao('Critério 5a: empate pendente (2 times que não jogaram entre si)');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T9'], 21, 10); // T1: V1 SP11 PP21
inserirJogoEncerrado(2, $t['T4'], $t['T6'], 21, 10); // T4: V1 SP11 PP21
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT4 = linhaDoCodigo($r['linhas'], 'T4');
T::ok($linT1['pendente'] && $linT4['pendente'], 'T1 e T4 ficam marcados como empate pendente (nunca se enfrentaram)');
T::ok($r['tem_empate_pendente'], 'O ranking sinaliza que há empate pendente');

// --- 5b. Empate pendente: 3 ou mais times sem solução --------------------
T::secao('Critério 5b: empate pendente (3+ times sem solução)');
$t = novoBanco();
inserirJogoEncerrado(1, $t['T1'], $t['T7'], 21, 10);
inserirJogoEncerrado(2, $t['T2'], $t['T8'], 21, 10);
inserirJogoEncerrado(3, $t['T3'], $t['T9'], 21, 10);
$r = Ranking::calcular();
foreach (['T1', 'T2', 'T3'] as $codigo) {
    $lin = linhaDoCodigo($r['linhas'], $codigo);
    T::ok($lin['pendente'], "{$codigo} fica marcado como empate pendente (grupo de 3 times)");
}

// --- 6. Desempate manual do admin resolve o empate pendente -------------
T::secao('Critério 6: posição manual definida pelo admin resolve o empate de 3+ times');
Times::definirDesempateManual($t['T1'], 1);
Times::definirDesempateManual($t['T2'], 2);
Times::definirDesempateManual($t['T3'], 3);
$r = Ranking::calcular();
$linT1 = linhaDoCodigo($r['linhas'], 'T1');
$linT2 = linhaDoCodigo($r['linhas'], 'T2');
$linT3 = linhaDoCodigo($r['linhas'], 'T3');
T::ok(!$linT1['pendente'] && !$linT2['pendente'] && !$linT3['pendente'], 'O empate deixa de ser pendente após a definição manual');
T::ok($linT1['posicao'] < $linT2['posicao'] && $linT2['posicao'] < $linT3['posicao'], 'A ordem manual (T1, T2, T3) é respeitada');

exit(T::resumo());
