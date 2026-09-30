<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

final class T
{
    public static int $passou = 0;
    public static int $falhou = 0;
    public static string $secaoAtual = '';

    public static function secao(string $nome): void
    {
        self::$secaoAtual = $nome;
        echo "\n== {$nome} ==\n";
    }

    public static function ok(bool $condicao, string $descricao): void
    {
        if ($condicao) {
            self::$passou++;
            echo "  OK  - {$descricao}\n";
        } else {
            self::$falhou++;
            echo "  FALHOU - {$descricao}\n";
        }
    }

    public static function igual(mixed $esperado, mixed $obtido, string $descricao): void
    {
        $condicao = $esperado === $obtido;
        self::ok($condicao, $descricao . ($condicao ? '' : " (esperado " . var_export($esperado, true) . ", obtido " . var_export($obtido, true) . ")"));
    }

    public static function resumo(): int
    {
        echo "\n----------------------------------------\n";
        echo "Total: " . (self::$passou + self::$falhou) . " | OK: " . self::$passou . " | Falhas: " . self::$falhou . "\n";
        return self::$falhou > 0 ? 1 : 0;
    }
}
