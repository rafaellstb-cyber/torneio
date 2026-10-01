<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$mensagemErro = null;
$mensagemSucesso = null;

// Portão do PIN do dia (uma vez por sessão do navegador).
if (Settings::pinAtivo() && empty($_SESSION['pin_validado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin_gate'])) {
        Csrf::requireValid();
        if (trim((string) $_POST['pin']) === Settings::pinValor()) {
            $_SESSION['pin_validado'] = true;
        } else {
            $mensagemErro = 'PIN incorreto. Confira com a organização.';
        }
    }
}

$pinBloqueado = Settings::pinAtivo() && empty($_SESSION['pin_validado']);

if (!$pinBloqueado && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jogo_id'])) {
    Csrf::requireValid();
    $jogoId = (int) $_POST['jogo_id'];
    $p1 = Validacao::paraInteiro($_POST['pontos1'] ?? null);
    $p2 = Validacao::paraInteiro($_POST['pontos2'] ?? null);
    if ($p1 === null || $p2 === null) {
        $mensagemErro = 'Informe os dois placares (apenas números inteiros).';
    } else {
        $erro = Jogos::salvarResultado($jogoId, $p1, $p2, 'lançamento público');
        if ($erro !== null) {
            $mensagemErro = $erro;
        } else {
            $mensagemSucesso = 'Resultado registrado com sucesso!';
        }
    }
}

$aba = ($_GET['aba'] ?? 'pendentes') === 'encerrados' ? 'encerrados' : 'pendentes';
$filtroFase = $_GET['fase'] ?? '';
$filtroRodada = $_GET['rodada'] ?? '';
$filtroQuadra = $_GET['quadra'] ?? '';
$filtroBusca = trim((string) ($_GET['busca'] ?? ''));

$filtros = array_filter([
    'fase' => $filtroFase, 'rodada' => $filtroRodada, 'quadra' => $filtroQuadra,
    'busca' => $filtroBusca, 'status' => $aba === 'pendentes' ? 'pendente' : 'encerrado',
], fn ($v) => $v !== '');

$jogos = $pinBloqueado ? [] : Jogos::listar($filtros);
$times = Times::mapaPorId();

ob_start();
?>

<h1 class="h4 mb-2">Lançar resultado</h1>
<div class="alert alert-light border small">
  1 set de 21 pontos. Vence quem fizer 21 com diferença mínima de 2 pontos (ex.: 21×19, 22×20, 23×21).
  Depois de salvo, o resultado só pode ser corrigido pelo administrador.
</div>

<?php if ($mensagemErro): ?><div class="alert alert-danger"><?= e($mensagemErro) ?></div><?php endif; ?>
<?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= e($mensagemSucesso) ?></div><?php endif; ?>

<?php if ($pinBloqueado): ?>

  <form method="post" class="card p-4 shadow-sm" style="max-width:360px">
    <?= Csrf::field() ?>
    <input type="hidden" name="pin_gate" value="1">
    <label class="form-label">PIN do dia</label>
    <input type="text" name="pin" class="form-control mb-3" inputmode="numeric" autofocus required>
    <button type="submit" class="btn btn-primary">Entrar</button>
  </form>

<?php else: ?>

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $aba === 'pendentes' ? 'active' : '' ?>" href="?aba=pendentes">Pendentes</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'encerrados' ? 'active' : '' ?>" href="?aba=encerrados">Encerrados</a></li>
  </ul>

  <form method="get" class="card p-3 mb-3 shadow-sm">
    <input type="hidden" name="aba" value="<?= e($aba) ?>">
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Fase</label>
        <select name="fase" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach (['classificatoria' => 'Classificatória', 'quartas' => 'Quartas', 'semifinal' => 'Semifinal', 'terceiro_lugar' => '3º lugar', 'final' => 'Final'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $filtroFase === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Rodada</label>
        <input type="text" name="rodada" class="form-control form-control-sm" value="<?= e($filtroRodada) ?>" placeholder="1-6">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Quadra</label>
        <select name="quadra" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php for ($q = 1; $q <= 3; $q++): ?>
            <option value="<?= $q ?>" <?= $filtroQuadra === (string) $q ? 'selected' : '' ?>>Quadra <?= $q ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Buscar time</label>
        <input type="text" name="busca" class="form-control form-control-sm" value="<?= e($filtroBusca) ?>">
      </div>
      <div class="col-12 col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
      </div>
    </div>
  </form>

  <?php if (!$jogos): ?>
    <p class="text-muted">Nenhum jogo <?= $aba === 'pendentes' ? 'pendente' : 'encerrado' ?> encontrado.</p>
  <?php endif; ?>

  <div class="cards-duas-colunas">
  <?php foreach ($jogos as $jogo): ?>
    <?php
    $t1 = $jogo['time1_id'] ? ($times[(int) $jogo['time1_id']] ?? null) : null;
    $t2 = $jogo['time2_id'] ? ($times[(int) $jogo['time2_id']] ?? null) : null;
    ?>
    <div class="card jogo-card shadow-sm">
      <div class="jogo-cabecalho">
        <span><strong>Jogo <?= (int) $jogo['numero'] ?></strong> · <?= e(Views::faseLabel($jogo['fase'], $jogo['rodada'])) ?></span>
        <span class="d-flex align-items-center gap-2">
          <?= Views::quadraBadgeHtml((int) $jogo['quadra']) ?>
        </span>
      </div>

      <?php if ($aba === 'encerrados'): ?>
        <div class="p-3">
          <p class="mb-1">
            <span class="nome-time"><?= e(Views::nomeTime($t1)) ?></span> <strong><?= (int) $jogo['pontos1'] ?></strong>
            x
            <strong><?= (int) $jogo['pontos2'] ?></strong> <span class="nome-time"><?= e(Views::nomeTime($t2)) ?></span>
          </p>
          <?php if (Views::atletas($t1) !== '' || Views::atletas($t2) !== ''): ?>
            <p class="atletas-time mb-1">
              <?= e(Views::atletas($t1)) ?><?= Views::atletas($t1) !== '' && Views::atletas($t2) !== '' ? ' · ' : '' ?><?= e(Views::atletas($t2)) ?>
            </p>
          <?php endif; ?>
          <p class="cadeado-resultado small mb-0"><i class="bi bi-lock-fill me-1"></i>Resultado registrado. Correções somente pelo administrador.</p>
        </div>
      <?php elseif (!$t1 || !$t2): ?>
        <div class="p-3 text-muted small">Times ainda não definidos para este jogo.</div>
      <?php else: ?>
        <form method="post" class="p-3" data-confirmar-placar data-time1="<?= e($t1['nome']) ?>" data-time2="<?= e($t2['nome']) ?>">
          <?= Csrf::field() ?>
          <input type="hidden" name="jogo_id" value="<?= (int) $jogo['id'] ?>">
          <div class="d-flex align-items-center justify-content-center gap-3">
            <div class="text-center placar-coluna-time">
              <label class="form-label d-block nome-time nome-time-grande"><?= e($t1['nome']) ?></label>
              <?php if (Views::atletas($t1) !== ''): ?><span class="atletas-time mb-1"><?= e(Views::atletas($t1)) ?></span><?php endif; ?>
              <input type="number" min="0" max="99" name="pontos1" class="form-control placar-input mt-1" required>
            </div>
            <span class="fs-4 text-muted">x</span>
            <div class="text-center placar-coluna-time">
              <label class="form-label d-block nome-time nome-time-grande"><?= e($t2['nome']) ?></label>
              <?php if (Views::atletas($t2) !== ''): ?><span class="atletas-time mb-1"><?= e(Views::atletas($t2)) ?></span><?php endif; ?>
              <input type="number" min="0" max="99" name="pontos2" class="form-control placar-input mt-1" required>
            </div>
          </div>
          <button type="submit" class="btn btn-success w-100 mt-3"><i class="bi bi-check-lg me-1"></i>Salvar resultado</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php
$conteudo = ob_get_clean();

$pageTitle = 'Lançar resultado';
$activeNav = 'lancar';
require __DIR__ . '/src/views/header.php';
echo $conteudo;
require __DIR__ . '/src/views/footer.php';
