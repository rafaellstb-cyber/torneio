<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$mensagem = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    if (($_POST['acao'] ?? '') === 'atualizar') {
        $dados = [
            'nome' => trim((string) $_POST['nome']),
            'data' => (string) $_POST['data'],
            'local' => trim((string) $_POST['local']),
            'hora_inicio' => (string) $_POST['hora_inicio'],
            'duracao_rodada_min' => max(1, (int) $_POST['duracao_rodada_min']),
            'status' => in_array($_POST['status'], ['nao_iniciado', 'em_andamento', 'finalizado'], true) ? $_POST['status'] : 'nao_iniciado',
        ];
        if ($dados['nome'] === '' || $dados['local'] === '') {
            $erro = 'Nome e local são obrigatórios.';
        } else {
            Torneio::update($dados);
            Horarios::recalcularTodos();
            $mensagem = 'Dados do torneio atualizados. Os horários previstos foram recalculados.';
        }
    }

    if (($_POST['acao'] ?? '') === 'reiniciar') {
        $confirmacao = trim((string) ($_POST['confirmacao'] ?? ''));
        if ($confirmacao === 'REINICIAR') {
            Torneio::reiniciar();
            $mensagem = 'Torneio reiniciado: todos os jogos, placares e o log foram apagados.';
        } else {
            $erro = 'Digite exatamente REINICIAR para confirmar.';
        }
    }
}

$torneio = Torneio::get();

$pageTitle = 'Torneio';
$activeAdminNav = 'torneio';
require __DIR__ . '/../../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Dados do torneio</h1>

<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card p-3 mb-4" style="max-width: 560px;">
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="atualizar">
    <div class="mb-3">
      <label class="form-label">Nome do torneio</label>
      <input type="text" name="nome" class="form-control" value="<?= e($torneio['nome']) ?>" required>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-6">
        <label class="form-label">Data</label>
        <input type="date" name="data" class="form-control" value="<?= e($torneio['data']) ?>" required>
      </div>
      <div class="col-6">
        <label class="form-label">Início dos jogos</label>
        <input type="time" name="hora_inicio" class="form-control" value="<?= e($torneio['hora_inicio']) ?>" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Local</label>
      <input type="text" name="local" class="form-control" value="<?= e($torneio['local']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Duração de cada rodada (minutos)</label>
      <input type="number" min="1" name="duracao_rodada_min" class="form-control" value="<?= (int) $torneio['duracao_rodada_min'] ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="nao_iniciado" <?= $torneio['status'] === 'nao_iniciado' ? 'selected' : '' ?>>Não iniciado</option>
        <option value="em_andamento" <?= $torneio['status'] === 'em_andamento' ? 'selected' : '' ?>>Em andamento</option>
        <option value="finalizado" <?= $torneio['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
      </select>
      <div class="form-text">O status também é atualizado automaticamente conforme os jogos são encerrados.</div>
    </div>
    <button type="submit" class="btn btn-primary">Salvar</button>
  </form>
</div>

<div class="card p-3 border-danger" style="max-width: 560px;">
  <h2 class="h6 text-danger">Reiniciar torneio</h2>
  <p class="small text-muted">Apaga todos os jogos, placares e o log de alterações. Times e configurações são mantidos. Esta ação não pode ser desfeita.</p>
  <form method="post" onsubmit="return confirm('Tem certeza? Todos os jogos e placares serão apagados permanentemente.');">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="reiniciar">
    <div class="mb-2">
      <label class="form-label small">Digite <strong>REINICIAR</strong> para confirmar</label>
      <input type="text" name="confirmacao" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-danger btn-sm">Reiniciar torneio</button>
  </form>
</div>

<?php require __DIR__ . '/../../src/views/admin_footer.php'; ?>
