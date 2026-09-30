<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

echo "Cadastrando times padrão (T1 a T9)...\n";
Times::cadastrarPadrao();

echo "Carregando calendário fixo da classificatória...\n";
Jogos::carregarCalendarioClassificatoria();

$totalAdmins = (int) Database::get()->query('SELECT COUNT(*) c FROM admins')->fetch()['c'];
if ($totalAdmins === 0) {
    echo "Criando admin padrão (usuario: admin / senha: torneio123 - troque depois!)...\n";
    Auth::criarAdmin('admin', 'torneio123');
} else {
    echo "Já existe pelo menos um admin cadastrado, mantendo como está.\n";
}

echo "Seed concluído. Times: " . count(Times::todos()) . " · Jogos: " . count(Jogos::listar()) . "\n";
echo "Use scripts/simular.php para gerar resultados de teste.\n";
