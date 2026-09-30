<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

/**
 * Instalação inicial via navegador, para hospedagens sem acesso a SSH/CLI
 * (onde não dá pra rodar scripts/seed.php). Cadastra os times, carrega o
 * calendário fixo e cria o primeiro admin. Roda uma única vez: depois que
 * os times existem, esta página só mostra um aviso e não faz nada.
 *
 * Por segurança, apague este arquivo do servidor depois de usá-lo.
 */

$jaInstalado = (int) Database::get()->query('SELECT COUNT(*) c FROM times')->fetch()['c'] > 0;

$erro = null;
$sucesso = false;

if (!$jaInstalado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $nome = trim((string) ($_POST['nome_torneio'] ?? ''));
    $data = (string) ($_POST['data'] ?? '');
    $local = trim((string) ($_POST['local'] ?? ''));
    $horaInicio = (string) ($_POST['hora_inicio'] ?? '14:00');
    $duracaoRodada = max(1, (int) ($_POST['duracao_rodada_min'] ?? 30));
    $adminUsuario = trim((string) ($_POST['admin_usuario'] ?? ''));
    $adminSenha = (string) ($_POST['admin_senha'] ?? '');
    $adminSenha2 = (string) ($_POST['admin_senha2'] ?? '');

    if ($nome === '' || $data === '' || $local === '' || $adminUsuario === '') {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (strlen($adminSenha) < 6) {
        $erro = 'A senha do admin deve ter pelo menos 6 caracteres.';
    } elseif ($adminSenha !== $adminSenha2) {
        $erro = 'A confirmação de senha não confere.';
    } else {
        Torneio::update([
            'nome' => $nome,
            'data' => $data,
            'local' => $local,
            'hora_inicio' => $horaInicio,
            'duracao_rodada_min' => $duracaoRodada,
            'status' => 'nao_iniciado',
        ]);
        Times::cadastrarPadrao();
        Jogos::carregarCalendarioClassificatoria();
        Horarios::recalcularTodos();
        Auth::criarAdmin($adminUsuario, $adminSenha);
        $sucesso = true;
        $jaInstalado = true;
    }
}

$torneio = Torneio::get();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalação · <?= e($torneio['nome']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?= asset_v('/assets/css/style.css') ?>">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh; background: linear-gradient(135deg, var(--navy-1), var(--navy-2)); padding: 1rem 0;">
<div class="card shadow p-4" style="max-width: 460px; width: 100%;">
  <img src="/assets/img/logo.png" alt="" class="d-block mx-auto mb-2" style="height: 80px; width: auto;">
  <h1 class="h4 mb-3 text-center">Instalação inicial</h1>

  <?php if ($jaInstalado && !$sucesso): ?>
    <div class="alert alert-info small">
      Este torneio já foi instalado (os times já existem). Esta página não faz mais nada
      por segurança. Acesse a <a href="/admin/">área administrativa</a> para gerenciar o torneio,
      ou vá para o <a href="/index.php">site público</a>.
    </div>
    <p class="text-muted small mb-0"><strong>Por segurança, apague o arquivo <code>public/instalar.php</code> do servidor agora.</strong></p>
  <?php elseif ($sucesso): ?>
    <div class="alert alert-success small">
      Instalação concluída! Times T1–T9 cadastrados, calendário da classificatória carregado
      e o admin <strong><?= e($adminUsuario) ?></strong> criado.
    </div>
    <a href="/admin/" class="btn btn-primary w-100 mb-2">Entrar na área administrativa</a>
    <a href="/index.php" class="btn btn-outline-secondary w-100 mb-3">Ver site público</a>
    <p class="text-muted small mb-0"><strong>Por segurança, apague o arquivo <code>public/instalar.php</code> do servidor agora.</strong></p>
  <?php else: ?>
    <?php if ($erro): ?><div class="alert alert-danger small"><?= e($erro) ?></div><?php endif; ?>
    <p class="text-muted small">Preencha os dados do torneio e crie o primeiro usuário administrador. Isso só pode ser feito uma vez.</p>
    <form method="post">
      <?= Csrf::field() ?>
      <div class="mb-2">
        <label class="form-label small">Nome do torneio</label>
        <input type="text" name="nome_torneio" class="form-control" required value="<?= e($_POST['nome_torneio'] ?? $torneio['nome']) ?>">
      </div>
      <div class="row g-2 mb-2">
        <div class="col-6">
          <label class="form-label small">Data</label>
          <input type="date" name="data" class="form-control" required value="<?= e($_POST['data'] ?? $torneio['data']) ?>">
        </div>
        <div class="col-6">
          <label class="form-label small">Início dos jogos</label>
          <input type="time" name="hora_inicio" class="form-control" required value="<?= e($_POST['hora_inicio'] ?? $torneio['hora_inicio']) ?>">
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label small">Local</label>
        <input type="text" name="local" class="form-control" required value="<?= e($_POST['local'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label small">Duração de cada rodada (minutos)</label>
        <input type="number" min="1" name="duracao_rodada_min" class="form-control" required value="<?= e((string) ($_POST['duracao_rodada_min'] ?? 30)) ?>">
      </div>
      <hr>
      <div class="mb-2">
        <label class="form-label small">Usuário do admin</label>
        <input type="text" name="admin_usuario" class="form-control" required value="<?= e($_POST['admin_usuario'] ?? '') ?>">
      </div>
      <div class="mb-2">
        <label class="form-label small">Senha do admin</label>
        <input type="password" name="admin_senha" class="form-control" required minlength="6">
      </div>
      <div class="mb-3">
        <label class="form-label small">Confirmar senha</label>
        <input type="password" name="admin_senha2" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary w-100">Instalar</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
