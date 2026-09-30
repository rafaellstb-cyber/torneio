<?php

declare(strict_types=1);

$arquivos = glob(__DIR__ . '/*_test.php');
sort($arquivos);

$falhouAlgum = false;
foreach ($arquivos as $arquivo) {
    echo "\n########################################\n";
    echo "# " . basename($arquivo) . "\n";
    echo "########################################\n";
    $php = PHP_BINARY;
    passthru($php . ' ' . escapeshellarg($arquivo), $codigo);
    if ($codigo !== 0) {
        $falhouAlgum = true;
    }
}

echo "\n========================================\n";
echo $falhouAlgum ? "RESULTADO FINAL: HÁ TESTES COM FALHA\n" : "RESULTADO FINAL: TODOS OS TESTES PASSARAM\n";
exit($falhouAlgum ? 1 : 0);
