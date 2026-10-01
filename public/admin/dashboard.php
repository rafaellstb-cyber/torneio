<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$torneio = Torneio::get();
$classCount = Jogos::contarPorFase('classificatoria');
$mmCount = Jogos::contarMataMata();
$logRecente = Log::recentes(15);
$times = Times::mapaPorId();

$pageTitle = 'Painel';
$activeAdminNav = 'dashboard';
require __DIR__ . '/../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Painel do torneio</h1>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="stat-numero h3 mb-0"><?= e(Torneio::statusLabel($torneio['status'])) ?></div>
      <small class="text-muted">Status</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="stat-numero h3 mb-0"><?= $classCount['encerrados'] ?>/<?= $classCount['total'] ?: 18 ?></div>
      <small class="text-muted">Classificatória</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="stat-numero h3 mb-0"><?= $mmCount['encerrados'] ?>/<?= $mmCount['total'] ?: 8 ?></div>
      <small class="text-muted">Mata-mata</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="stat-numero h3 mb-0"><?= count($times) ?></div>
      <small class="text-muted">Times cadastrados</small>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-6">
    <div class="card p-3 h-100">
      <h2 class="h6">Calendário da classificatória</h2>
      <?php if (Jogos::calendarioJaCarregado()): ?>
        <p class="text-success small mb-0"><i class="bi bi-check-circle-fill me-1"></i>Calendário já carregado.</p>
      <?php else: ?>
        <p class="small text-muted">Carrega os 18 jogos fixos da fase classificatória (seção 5.1 do briefing).</p>
        <form method="post" action="/admin/jogos.php">
          <?= Csrf::field() ?>
          <input type="hidden" name="acao" value="carregar_calendario">
          <button type="submit" class="btn btn-primary btn-sm">Carregar calendário da classificatória</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="card p-3 h-100">
      <h2 class="h6">Mata-mata</h2>
      <?php if (MataMata::jaGerado()): ?>
        <p class="text-success small mb-0"><i class="bi bi-check-circle-fill me-1"></i>Mata-mata já gerado.</p>
      <?php else: ?>
        <?php [$pode, $motivo] = MataMata::podeGerar(); ?>
        <p class="small text-muted"><?= $pode ? 'A classificatória terminou e a ordem está definida. Pode gerar o mata-mata.' : e($motivo) ?></p>
        <form method="post" action="/admin/jogos.php">
          <?= Csrf::field() ?>
          <input type="hidden" name="acao" value="gerar_matamata">
          <button type="submit" class="btn btn-primary btn-sm" <?= $pode ? '' : 'disabled' ?>>Gerar mata-mata</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<h2 class="h6">Últimas alterações registradas</h2>
<div class="table-responsive">
  <table class="table table-sm bg-white">
    <thead><tr><th>Quando</th><th>Jogo</th><th>Campo</th><th>De</th><th>Para</th><th>Usuário</th></tr></thead>
    <tbody>
    <?php foreach ($logRecente as $l): ?>
      <tr>
        <td class="small"><?= e($l['criado_em']) ?></td>
        <td class="small">#<?= (int) $l['jogo_id'] ?></td>
        <td class="small"><?= e($l['campo']) ?></td>
        <td class="small"><?= e((string) $l['valor_anterior']) ?></td>
        <td class="small"><?= e((string) $l['valor_novo']) ?></td>
        <td class="small"><?= e((string) $l['usuario']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logRecente): ?>
      <tr><td colspan="6" class="text-muted small">Nenhuma alteração registrada ainda.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../src/views/admin_footer.php'; ?>
