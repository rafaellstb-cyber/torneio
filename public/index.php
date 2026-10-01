<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$torneio = Torneio::get();
$times = Times::mapaPorId();
$classCount = Jogos::contarPorFase('classificatoria');
$mmCount = Jogos::contarMataMata();
$mmTotalEsperado = 8;

$porQuadra = [];
for ($q = 1; $q <= 3; $q++) {
    $pendentesQuadra = Jogos::listar(['quadra' => $q, 'status' => 'pendente']);
    $porQuadra[$q] = [
        'atual' => $pendentesQuadra[0] ?? null,
        'proximo' => $pendentesQuadra[1] ?? null,
    ];
}

$folgaAtual = null;
$rodadaAtual = null;
$classPendentes = Jogos::listar(['fase' => 'classificatoria', 'status' => 'pendente']);
if ($classPendentes) {
    $rodadaAtual = $classPendentes[0]['rodada'];
    $folgas = Jogos::folgasPorRodada();
    $folgaAtual = $folgas[(int) $rodadaAtual] ?? null;
}

$podio = MataMata::podio();

$encerrados = Jogos::listar(['status' => 'encerrado']);
usort($encerrados, fn ($a, $b) => strcmp($b['encerrado_em'] ?? '', $a['encerrado_em'] ?? ''));
$ultimosResultados = array_slice($encerrados, 0, 5);

$ranking = Ranking::calcular();
$rankingResumo = array_slice($ranking['linhas'], 0, 5);

// Cabeçalho estático: renderizado uma vez, nunca substituído pelo auto-refresh
// (o indicador guarda referências de DOM que ficariam órfãs se fosse recriado a cada 15s).
ob_start();
?>
<section class="hero-torneio">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
    <div class="d-flex align-items-center gap-3">
      <img src="/assets/img/logo.png" alt="" class="logo-hero">
      <div>
        <h1><?= e($torneio['nome']) ?></h1>
        <div class="hero-meta">
          <span><i class="bi bi-calendar-event me-1"></i><?= e(date('d/m/Y', strtotime($torneio['data']))) ?></span>
          <span><i class="bi bi-clock me-1"></i>início <?= e($torneio['hora_inicio']) ?></span>
          <span><i class="bi bi-geo-alt me-1"></i><?= e($torneio['local']) ?></span>
        </div>
      </div>
    </div>
    <span class="selo-status selo-<?= e($torneio['status']) ?>"><?= e(Torneio::statusLabel($torneio['status'])) ?></span>
  </div>

  <div class="progresso-bloco">
    <small>Classificatória: <?= $classCount['encerrados'] ?> de <?= $classCount['total'] ?: 18 ?> jogos</small>
    <div class="progress mb-2">
      <div class="progress-bar" style="width: <?= $classCount['total'] ? round($classCount['encerrados'] / max($classCount['total'], 1) * 100) : 0 ?>%"></div>
    </div>
    <small>Mata-mata: <?= $mmCount['encerrados'] ?> de <?= $mmCount['total'] ?: $mmTotalEsperado ?> jogos</small>
    <div class="progress">
      <div class="progress-bar" style="width: <?= $mmCount['total'] ? round($mmCount['encerrados'] / $mmCount['total'] * 100) : 0 ?>%"></div>
    </div>
  </div>
</section>
<?php
$cabecalho = ob_get_clean();

// Conteúdo dinâmico: isto (e só isto) é o que o fetch de 15s devolve e substitui.
ob_start();
?>
<h2 class="h5 mb-3">Agora nas quadras</h2>
<?php if ($folgaAtual): ?>
  <p class="text-muted small">Rodada <?= e((string) $rodadaAtual) ?> da classificatória · Folgam: <?= e(implode(', ', $folgaAtual)) ?></p>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php for ($q = 1; $q <= 3; $q++): ?>
    <div class="col-12 col-md-4">
      <div class="card quadra-card shadow-sm p-3" data-quadra="<?= $q ?>">
        <h3 class="h6"><?= Views::quadraBadgeHtml($q) ?></h3>
        <?php $atual = $porQuadra[$q]['atual']; ?>
        <?php if ($atual): ?>
          <p class="mb-1 small text-muted">Jogo da vez · Jogo <?= (int) $atual['numero'] ?></p>
          <p class="mb-2 fw-semibold">
            <?= e(Views::nomeTime($times[(int) $atual['time1_id']] ?? null)) ?>
            <span class="text-muted">x</span>
            <?= e(Views::nomeTime($times[(int) $atual['time2_id']] ?? null)) ?>
          </p>
        <?php else: ?>
          <p class="text-muted small mb-2">Nenhum jogo pendente nesta quadra.</p>
        <?php endif; ?>
        <?php $proximo = $porQuadra[$q]['proximo']; ?>
        <?php if ($proximo): ?>
          <p class="mb-0 small text-muted">Próximo: <?= e(Views::nomeTime($times[(int) $proximo['time1_id']] ?? null)) ?> x <?= e(Views::nomeTime($times[(int) $proximo['time2_id']] ?? null)) ?></p>
        <?php endif; ?>
      </div>
    </div>
  <?php endfor; ?>
</div>

<?php if ($podio): ?>
<h2 class="h5 mb-3">Pódio</h2>
<?= Views::podioHtml($podio) ?>
<?php endif; ?>

<div class="row g-4">
  <div class="col-12 col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h5 mb-0">Últimos resultados</h2>
      <a href="/jogos.php?status=encerrado" class="small">ver todos</a>
    </div>
    <?php if (!$ultimosResultados): ?>
      <p class="text-muted small">Ainda não há resultados lançados.</p>
    <?php endif; ?>
    <?php foreach ($ultimosResultados as $jogo): ?>
      <?= Views::cardJogo($jogo, $times) ?>
    <?php endforeach; ?>
  </div>

  <div class="col-12 col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h5 mb-0">Classificação</h2>
      <a href="/ranking.php" class="small">ver completa</a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm tabela-ranking bg-white">
        <thead><tr><th>#</th><th>Time</th><th>V</th><th>D</th><th>SP</th></tr></thead>
        <tbody>
        <?php foreach ($rankingResumo as $linha): ?>
          <tr class="<?= $linha['posicao'] <= 8 ? 'linha-classificada' : 'linha-eliminada' ?>">
            <td><?= $linha['posicao'] ?></td>
            <td>
              <?= e($linha['nome']) ?><?= $linha['pendente'] ? ' <span class="badge badge-pendente">empate pendente</span>' : '' ?>
              <?php if (!empty($linha['atletas'])): ?><span class="atletas-tabela"><?= e($linha['atletas']) ?></span><?php endif; ?>
            </td>
            <td><?= $linha['v'] ?></td>
            <td><?= $linha['d'] ?></td>
            <td class="<?= $linha['sp'] > 0 ? 'saldo-positivo' : ($linha['sp'] < 0 ? 'saldo-negativo' : '') ?>"><?= $linha['sp'] > 0 ? '+' : '' ?><?= $linha['sp'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$conteudoAuto = ob_get_clean();

if (isset($_GET['_frag'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $conteudoAuto;
    exit;
}

$pageTitle = 'Início';
$activeNav = 'inicio';
require __DIR__ . '/src/views/header.php';
echo $cabecalho;
echo Views::indicadorAtualizacao();
echo '<div data-autorefresh id="conteudo-auto">' . $conteudoAuto . '</div>';
require __DIR__ . '/src/views/footer.php';
