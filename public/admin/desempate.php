<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$mensagem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $posicoes = $_POST['posicao'] ?? [];
    foreach ($posicoes as $timeId => $valor) {
        $valor = trim((string) $valor);
        Times::definirDesempateManual((int) $timeId, $valor === '' ? null : (int) $valor);
    }
    $mensagem = 'Ordem de desempate salva.';
}

$ranking = Ranking::calcular();
$times = Times::mapaPorId();

$grupos = [];
foreach ($ranking['linhas'] as $linha) {
    if (!$linha['pendente']) {
        continue;
    }
    $chave = implode('-', $linha['grupo_empate']);
    if (!isset($grupos[$chave])) {
        $grupos[$chave] = $linha['grupo_empate'];
    }
}

$pageTitle = 'Desempate';
$activeAdminNav = 'desempate';
require __DIR__ . '/../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Desempate manual</h1>
<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>

<p class="text-muted small">
  Os critérios automáticos (vitórias, saldo de pontos, pontos pró e confronto direto) não foram suficientes
  para desempatar os grupos abaixo. Defina uma posição manual para cada time do grupo — a ordem numérica
  (1 = melhor colocado do grupo) decide o desempate. Todos os times do grupo precisam ter uma posição
  distinta para o empate ser resolvido.
</p>

<?php if (!$grupos): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i>Não há empates pendentes no momento.</div>
<?php endif; ?>

<?php foreach ($grupos as $grupo): ?>
  <div class="card p-3 mb-3" style="max-width: 560px;">
    <h2 class="h6">Grupo empatado</h2>
    <form method="post">
      <?= Csrf::field() ?>
      <table class="table table-sm">
        <thead><tr><th>Time</th><th>V</th><th>SP</th><th>PP</th><th>Posição manual</th></tr></thead>
        <tbody>
        <?php foreach ($grupo as $timeId): ?>
          <?php
            $linha = null;
            foreach ($ranking['linhas'] as $l) {
                if ((int) $l['id'] === (int) $timeId) { $linha = $l; break; }
            }
          ?>
          <tr>
            <td><?= e($times[$timeId]['nome']) ?></td>
            <td><?= $linha['v'] ?? '-' ?></td>
            <td><?= $linha['sp'] ?? '-' ?></td>
            <td><?= $linha['pp'] ?? '-' ?></td>
            <td style="width:110px">
              <input type="number" min="1" name="posicao[<?= (int) $timeId ?>]" class="form-control form-control-sm"
                     value="<?= e((string) ($times[$timeId]['desempate_manual'] ?? '')) ?>">
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <button type="submit" class="btn btn-sm btn-primary">Salvar ordem deste grupo</button>
    </form>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../src/views/admin_footer.php'; ?>
