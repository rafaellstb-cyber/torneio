<?php

final class Validacao
{
    /** Retorna null se o placar for válido, ou uma mensagem de erro. */
    public static function validarPlacar(int $pontos1, int $pontos2): ?string
    {
        if ($pontos1 < 0 || $pontos2 < 0) {
            return 'Os pontos não podem ser negativos.';
        }
        if ($pontos1 === $pontos2) {
            return 'Empate não é permitido. Um set de vôlei de praia sempre tem vencedor.';
        }

        $max = max($pontos1, $pontos2);
        $min = min($pontos1, $pontos2);

        if ($max < 21) {
            return 'O time vencedor precisa ter pelo menos 21 pontos.';
        }

        $diferenca = $max - $min;
        if ($diferenca < 2) {
            return 'A diferença mínima entre os placares é de 2 pontos.';
        }

        if ($max > 21 && $diferenca !== 2) {
            return 'Acima de 21 pontos, a diferença deve ser exatamente 2 (ex.: 22×20, 23×21).';
        }

        return null;
    }

    /** Converte entrada de formulário em inteiro, ou null se inválida/ausente. */
    public static function paraInteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_numeric($valor)) {
            return null;
        }
        $num = (int) $valor;
        if ((string) $num !== (string) (int) $valor && (float) $valor != $num) {
            return null;
        }
        return $num;
    }
}
