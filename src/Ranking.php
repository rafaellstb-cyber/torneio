<?php

final class Ranking
{
    /**
     * Calcula a classificação geral (fase classificatória).
     * Retorna ['linhas' => [...], 'tem_empate_pendente' => bool].
     *
     * Cada linha: id, codigo, nome, j, v, d, pp, pc, sp, posicao, pendente(bool),
     * grupo_empate (ids do grupo, se pendente).
     */
    public static function calcular(): array
    {
        $times = Times::todos();
        $stats = [];
        foreach ($times as $t) {
            $id = (int) $t['id'];
            $stats[$id] = [
                'id' => $id,
                'codigo' => $t['codigo'],
                'nome' => $t['nome'],
                'j' => 0, 'v' => 0, 'd' => 0, 'pp' => 0, 'pc' => 0, 'sp' => 0,
                'desempate_manual' => $t['desempate_manual'],
            ];
        }

        $jogos = Jogos::listar(['fase' => 'classificatoria', 'status' => 'encerrado']);
        foreach ($jogos as $jogo) {
            $t1 = (int) $jogo['time1_id'];
            $t2 = (int) $jogo['time2_id'];
            $p1 = (int) $jogo['pontos1'];
            $p2 = (int) $jogo['pontos2'];

            $stats[$t1]['j']++;
            $stats[$t2]['j']++;
            $stats[$t1]['pp'] += $p1;
            $stats[$t1]['pc'] += $p2;
            $stats[$t2]['pp'] += $p2;
            $stats[$t2]['pc'] += $p1;

            if ($p1 > $p2) {
                $stats[$t1]['v']++;
                $stats[$t2]['d']++;
            } else {
                $stats[$t2]['v']++;
                $stats[$t1]['d']++;
            }
        }

        foreach ($stats as &$s) {
            $s['sp'] = $s['pp'] - $s['pc'];
        }
        unset($s);

        // Agrupa por (V, SP, PP) - times com a mesma chave estão empatados
        // após os três primeiros critérios de desempate.
        $grupos = [];
        foreach ($stats as $s) {
            $chave = sprintf('%05d|%+06d|%05d', $s['v'], $s['sp'], $s['pp']);
            $grupos[$chave][] = $s['id'];
        }

        // Ordena as chaves de grupo da melhor para a pior colocação.
        $chaves = array_keys($grupos);
        usort($chaves, function ($a, $b) {
            [$va, $sa, $pa] = sscanf($a, '%d|%d|%d');
            [$vb, $sb, $pb] = sscanf($b, '%d|%d|%d');
            if ($va !== $vb) return $vb - $va;
            if ($sa !== $sb) return $sb - $sa;
            return $pb - $pa;
        });

        $linhas = [];
        $temEmpatePendente = false;
        $posicao = 1;

        foreach ($chaves as $chave) {
            $idsGrupo = $grupos[$chave];
            $ordenados = $idsGrupo;
            $pendente = false;

            if (count($idsGrupo) > 1) {
                [$ordenados, $pendente] = self::resolverEmpate($idsGrupo, $stats);
            }

            foreach ($ordenados as $id) {
                $linha = $stats[$id];
                $linha['posicao'] = $posicao;
                $linha['pendente'] = $pendente;
                $linha['grupo_empate'] = $pendente ? $idsGrupo : [];
                $linhas[] = $linha;
                $posicao++;
            }

            if ($pendente) {
                $temEmpatePendente = true;
            }
        }

        return ['linhas' => $linhas, 'tem_empate_pendente' => $temEmpatePendente];
    }

    /**
     * Tenta resolver o empate de um grupo de times com o critério de
     * confronto direto (só quando são exatamente 2 times que se
     * enfrentaram) e, por fim, o critério manual do admin.
     * Retorna [idsOrdenados, pendente].
     */
    private static function resolverEmpate(array $idsGrupo, array $stats): array
    {
        if (count($idsGrupo) === 2) {
            $vencedor = self::confrontoDireto($idsGrupo[0], $idsGrupo[1]);
            if ($vencedor !== null) {
                $perdedor = $vencedor === $idsGrupo[0] ? $idsGrupo[1] : $idsGrupo[0];
                return [[$vencedor, $perdedor], false];
            }
        }

        // Critério manual: todos os times do grupo precisam ter uma posição
        // manual distinta definida pelo admin para o empate ser resolvido.
        $manuais = [];
        foreach ($idsGrupo as $id) {
            $manuais[$id] = $stats[$id]['desempate_manual'];
        }
        $definidos = array_filter($manuais, fn ($v) => $v !== null);
        $valoresUnicos = array_unique(array_values($definidos));

        if (count($definidos) === count($idsGrupo) && count($valoresUnicos) === count($idsGrupo)) {
            $ordenados = $idsGrupo;
            usort($ordenados, fn ($a, $b) => $manuais[$a] <=> $manuais[$b]);
            return [$ordenados, false];
        }

        return [$idsGrupo, true];
    }

    /** Retorna o id do vencedor do confronto direto na classificatória, ou null se não jogaram. */
    private static function confrontoDireto(int $idA, int $idB): ?int
    {
        $jogos = Jogos::listar(['fase' => 'classificatoria', 'status' => 'encerrado']);
        foreach ($jogos as $jogo) {
            $t1 = (int) $jogo['time1_id'];
            $t2 = (int) $jogo['time2_id'];
            $par = [$t1, $t2];
            if (in_array($idA, $par, true) && in_array($idB, $par, true)) {
                $p1 = (int) $jogo['pontos1'];
                $p2 = (int) $jogo['pontos2'];
                return $p1 > $p2 ? $t1 : $t2;
            }
        }
        return null;
    }

    public static function classificatoriaCompleta(): bool
    {
        $c = Jogos::contarPorFase('classificatoria');
        return $c['total'] === 18 && $c['encerrados'] === 18;
    }
}
