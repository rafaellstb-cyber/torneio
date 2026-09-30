<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$rodadaLabels = [
    '1' => 'Rodada 1', '2' => 'Rodada 2', '3' => 'Rodada 3',
    '4' => 'Rodada 4', '5' => 'Rodada 5', '6' => 'Rodada 6',
    'leva1' => 'Quartas · 1ª leva', 'leva2' => 'Quartas · 2ª leva',
    'semifinal' => 'Semifinal', 'final' => 'Final / 3º lugar',
];

$filtroFase = $_GET['fase'] ?? '';
$filtroRodada = $_GET['rodada'] ?? '';
$filtroQuadra = $_GET['quadra'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
$filtroBusca = trim((string) ($_GET['busca'] ?? ''));

$filtros = array_filter([
    'fase' => $filtroFase,
    'rodada' => $filtroRodada,
    'quadra' => $filtroQuadra,
    'status' => $filtroStatus,
    'busca' => $filtroBusca,
], fn ($v) => $v !== '');

$jogos = Jogos::listar($filtros);
$times = Times::mapaPorId();
$nomePorCodigo = array_column(Times::todos(), 'nome', 'codigo');
$folgasPorRodada = Jogos::folgasPorRodada();

$secoes = [];
foreach ($jogos as $jogo) {
    $chave = $jogo['fase'] . '|' . $jogo['rodada'];
    if (!isset($secoes[$chave])) {
        $secoes[$chave] = ['fase' => $jogo['fase'], 'rodada' => $jogo['rodada'], 'jogos' => []];
    }
    $secoes[$chave]['jogos'][] = $jogo;
}

ob_start();
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
  <h1 class="h4 mb-0"><?= count($jogos) ?> jogo<?= count($jogos) === 1 ? '' : 's' ?></h1>
</div>

<form method="get" class="card p-3 mb-3 shadow-sm" id="form-filtros">
  <div class="row g-2">
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Fase</label>
      <select name="fase" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach (['classificatoria' => 'Classificatória', 'quartas' => 'Quartas', 'semifinal' => 'Semifinal', 'terceiro_lugar' => '3º lugar', 'final' => 'Final'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $filtroFase === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Rodada</label>
      <select name="rodada" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($rodadaLabels as $v => $l): ?>
          <option value="<?= e((string) $v) ?>" <?= $filtroRodada === (string) $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
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
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Todos</option>
        <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
        <option value="encerrado" <?= $filtroStatus === 'encerrado' ? 'selected' : '' ?>>Encerrado</option>
      </select>
    </div>
    <div class="col-8 col-md-3">
      <label class="form-label small mb-1">Buscar time</label>
      <input type="text" name="busca" class="form-control form-control-sm" value="<?= e($filtroBusca) ?>" placeholder="Nome do time">
    </div>
    <div class="col-4 col-md-1 d-flex align-items-end gap-1">
      <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
    </div>
  </div>
  <div class="mt-2">
    <button type="button" class="btn btn-sm btn-link px-0" data-limpar-filtros>Limpar filtros</button>
  </div>
</form>
<?php
$cabecalho = ob_get_clean();

ob_start();
?>
<?php if (!$secoes): ?>
  <p class="text-muted">Nenhum jogo encontrado com os filtros selecionados.</p>
<?php endif; ?>

<?php foreach ($secoes as $secao): ?>
  <h2 class="h6 text-muted mb-2 mt-4"><?= e(Views::secaoLabel($secao['fase'], $secao['rodada'])) ?></h2>
  <?php if ($secao['fase'] === 'classificatoria' && isset($folgasPorRodada[(int) $secao['rodada']])): ?>
    <p class="small text-muted mb-2">Folgam: <?= e(implode(', ', array_map(fn ($c) => $nomePorCodigo[$c] ?? $c, $folgasPorRodada[(int) $secao['rodada']]))) ?></p>
  <?php endif; ?>
  <div class="cards-duas-colunas">
    <?php foreach ($secao['jogos'] as $jogo): ?>
      <?= Views::cardJogo($jogo, $times) ?>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<?php
$conteudoAuto = ob_get_clean();

if (isset($_GET['_frag'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $conteudoAuto;
    exit;
}

$pageTitle = 'Jogos';
$activeNav = 'jogos';
require __DIR__ . '/../src/views/header.php';
echo $cabecalho;
echo Views::indicadorAtualizacao();
echo '<div data-autorefresh id="conteudo-auto">' . $conteudoAuto . '</div>';
require __DIR__ . '/../src/views/footer.php';
