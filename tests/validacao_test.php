<?php

declare(strict_types=1);

require_once __DIR__ . '/harness.php';

T::secao('Validação de placar (1 set de 21, diferença mínima de 2)');

$validos = [[21, 19], [22, 20], [23, 21], [21, 0], [21, 10]];
foreach ($validos as [$a, $b]) {
    T::ok(Validacao::validarPlacar($a, $b) === null, "{$a} x {$b} deve ser válido");
    T::ok(Validacao::validarPlacar($b, $a) === null, "{$b} x {$a} deve ser válido");
}

$invalidos = [
    [21, 20],   // diferença de 1
    [20, 18],   // ninguém chegou a 21
    [24, 21],   // passou de 21 com diferença 3
    [21, 21],   // empate
    [22, 21],   // acima de 21 com diferença 1
    [-1, 21],   // negativo
];
foreach ($invalidos as [$a, $b]) {
    T::ok(Validacao::validarPlacar($a, $b) !== null, "{$a} x {$b} deve ser inválido");
}

exit(T::resumo());
