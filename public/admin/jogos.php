<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$mensagem = null;
$erro = null;
$usuario = Auth::usuario();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'carregar_calendario') {
        if (Jogos::calendarioJaCarregado()) {
            $erro = 'O calendário já foi carregado.';
        } else {
            Jogos::carregarCalendarioClassificatoria();
            $mensagem = 'Calendário da classificatória carregado (18 jogos).';
        }
    }

    if ($acao === 'gerar_matamata') {
        $resultado = MataMata::gerar();
        if ($resultado !== null) {
            $erro = $resultado;
        } else {
            $mensagem = 'Mata-mata gerado com sucesso.';
        }
    }

    if ($acao === 'salvar_jogo') {
        $jogoId = (int) $_POST['jogo_id'];
        $jogo = Jogos::porId($jogoId);
        if (!$jogo) {
            $erro = 'Jogo não encontrado.';
        } else {
            $quadra = (int) $_POST['quadra'];
            if ($quadra !== (int) $jogo['quadra']) {
                Jogos::ajustarQuadra($jogoId, $quadra, $usuario);
            }

            $p1 = Validacao::paraInteiro($_POST['pontos1'] ?? null);
            $p2 = Validacao::paraInteiro($_POST['pontos2'] ?? null);
            if ($p1 !== null && $p2 !== null) {
                $resultado = $jogo['status'] === 'encerrado'
                    ? Jogos::corrigirResultado($jogoId, $p1, $p2, $usuario)
                    : Jogos::salvarResultado($jogoId, $p1, $p2, $usuario);
                if ($resultado !== null) {
                    $erro = $resultado;
                } else {
                    $mensagem = "Jogo {$jogo['numero']} salvo.";
                }
            } else {
                $mensagem = "Jogo {$jogo['numero']}: quadra atualizada.";
            }
        }
    }

    if ($acao === 'reabrir') {
        $jogoId = (int) $_POST['jogo_id'];
        Jogos::reabrir($jogoId, $usuario);
        $mensagem = 'Jogo reaberto. Jogos dependentes do mata-mata foram recalculados, se houver.';
    }
}

$filtroFase = $_GET['fase'] ?? '';
$jogos = Jogos::listar($filtroFase !== '' ? ['fase' => $filtroFase] : []);
$times = Times::mapaPorId();

[$podeGerarMM, $motivoMM] = MataMata::podeGerar();

$pageTitle = 'Jogos';
$activeAdminNav = 'jogos';
require __DIR__ . '/../../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Jogos</h1>
<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="carregar_calendario">
    <button type="submit" class="btn btn-outline-primary btn-sm" <?= Jogos::calendarioJaCarregado() ? 'disabled' : '' ?>>
      <i class="bi bi-calendar-plus me-1"></i>Carregar calendário da classificatória
    </button>
  </form>
  <form method="post" title="<?= e($motivoMM ?? '') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="gerar_matamata">
    <button type="submit" class="btn btn-outline-primary btn-sm" <?= $podeGerarMM ? '' : 'disabled' ?>>
      <i class="bi bi-diagram-3 me-1"></i>Gerar mata-mata
    </button>
  </form>
  <form method="get" class="ms-auto d-flex gap-1">
    <select name="fase" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Todas as fases</option>
      <?php foreach (['classificatoria' => 'Classificatória', 'quartas' => 'Quartas', 'semifinal' => 'Semifinal', 'terceiro_lugar' => '3º lugar', 'final' => 'Final'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= $filtroFase === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php if (!$podeGerarMM && !MataMata::jaGerado()): ?>
  <p class="text-muted small">Mata-mata: <?= e($motivoMM) ?></p>
<?php endif; ?>


<?php // Formulários ficam fora da tabela (um <form> não pode envolver <td>s de uma <tr>);
      // os campos dentro da tabela se associam a eles via atributo form="...". ?>
<?php foreach ($jogos as $jogo): ?>
  <form id="form-jogo-<?= (int) $jogo['id'] ?>" method="post" class="d-none">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="salvar_jogo">
    <input type="hidden" name="jogo_id" value="<?= (int) $jogo['id'] ?>">
  </form>
  <?php if ($jogo['status'] === 'encerrado'): ?>
    <form id="form-reabrir-<?= (int) $jogo['id'] ?>" method="post" class="d-none" onsubmit="return confirm('Reabrir este jogo? O placar será apagado e, se ele alimentava o mata-mata, os jogos seguintes serão recalculados.');">
      <?= Csrf::field() ?>
      <input type="hidden" name="acao" value="reabrir">
      <input type="hidden" name="jogo_id" value="<?= (int) $jogo['id'] ?>">
    </form>
  <?php endif; ?>
<?php endforeach; ?>

<div class="table-responsive">
<table class="table table-sm bg-white align-middle">
<thead class="table-light">
<tr>
  <th>#</th><th>Fase</th><th>Quadra</th><th>Time 1</th><th>Time 2</th><th colspan="2">Placar</th><th>Status</th><th></th>
</tr>
</thead>
<tbody>
<?php foreach ($jogos as $jogo): ?>
  <?php
    $formId = 'form-jogo-' . (int) $jogo['id'];
    $t1 = $jogo['time1_id'] ? ($times[(int) $jogo['time1_id']]['nome'] ?? '?') : ($jogo['origem1'] ?? 'A definir');
    $t2 = $jogo['time2_id'] ? ($times[(int) $jogo['time2_id']]['nome'] ?? '?') : ($jogo['origem2'] ?? 'A definir');
  ?>
  <tr>
    <td><?= (int) $jogo['numero'] ?></td>
    <td class="small"><?= e(Views::faseLabel($jogo['fase'], $jogo['rodada'])) ?></td>
    <td><input form="<?= $formId ?>" type="number" name="quadra" value="<?= (int) $jogo['quadra'] ?>" min="1" max="3" class="form-control form-control-sm" style="width:64px"></td>
    <td class="small"><?= e($t1) ?></td>
    <td class="small"><?= e($t2) ?></td>
    <td><input form="<?= $formId ?>" type="number" name="pontos1" value="<?= e((string) $jogo['pontos1']) ?>" min="0" class="form-control form-control-sm" style="width:60px" <?= (!$jogo['time1_id'] || !$jogo['time2_id']) ? 'disabled' : '' ?>></td>
    <td><input form="<?= $formId ?>" type="number" name="pontos2" value="<?= e((string) $jogo['pontos2']) ?>" min="0" class="form-control form-control-sm" style="width:60px" <?= (!$jogo['time1_id'] || !$jogo['time2_id']) ? 'disabled' : '' ?>></td>
    <td><?= Views::statusBadgeHtml($jogo['status']) ?></td>
    <td class="text-nowrap">
      <button form="<?= $formId ?>" type="submit" class="btn btn-sm btn-primary">Salvar</button>
      <?php if ($jogo['status'] === 'encerrado'): ?>
        <button form="form-reabrir-<?= (int) $jogo['id'] ?>" type="submit" class="btn btn-sm btn-outline-secondary">Reabrir</button>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php require __DIR__ . '/../../src/views/admin_footer.php'; ?>
