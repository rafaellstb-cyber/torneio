<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$classCount = Jogos::contarPorFase('classificatoria');
$ranking = Ranking::calcular();

ob_start();
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
  <div>
    <h1 class="h4 mb-1">Ranking Geral</h1>
    <p class="text-muted small mb-0">Classificatória: <?= $classCount['encerrados'] ?>/<?= $classCount['total'] ?: 18 ?> jogos<?= $classCount['encerrados'] < ($classCount['total'] ?: 18) ? ' · tabela parcial' : '' ?></p>
  </div>
</div>
<?php
$cabecalho = ob_get_clean();

ob_start();
?>
<?php if ($ranking['tem_empate_pendente']): ?>
  <p class="text-muted small"><i class="bi bi-info-circle me-1"></i>Há empate pendente entre times que ainda não tiveram a ordem definida. O administrador precisa resolver o desempate.</p>
<?php endif; ?>

<div class="table-responsive">
  <table class="table tabela-ranking bg-white shadow-sm align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th><th>Time</th><th>J</th><th>V</th><th>PP</th><th>PC</th><th>SP</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($ranking['linhas'] as $linha): ?>
      <?php $classificado = $linha['posicao'] <= 8; ?>
      <tr class="<?= $classificado ? 'linha-classificada' : 'linha-eliminada' ?><?= $linha['posicao'] === 9 ? ' linha-corte' : '' ?>">
        <td class="<?= $classificado ? 'posicao-classificada' : '' ?>"><?= $linha['posicao'] ?>º</td>
        <td>
          <span class="nome-ranking"><?= e($linha['nome']) ?></span>
          <?php if ($linha['pendente']): ?><span class="badge badge-pendente ms-1">empate pendente</span><?php endif; ?>
          <?php if (!empty($linha['atletas'])): ?><span class="atletas-tabela"><?= e($linha['atletas']) ?></span><?php endif; ?>
        </td>
        <td><?= $linha['j'] ?></td>
        <td class="vitorias-ranking"><?= $linha['v'] ?></td>
        <td><?= $linha['pp'] ?></td>
        <td><?= $linha['pc'] ?></td>
        <td class="<?= $linha['sp'] > 0 ? 'saldo-positivo' : ($linha['sp'] < 0 ? 'saldo-negativo' : '') ?>"><?= $linha['sp'] > 0 ? '+' : '' ?><?= $linha['sp'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="legenda-ranking text-muted small mt-3">
  <strong>J</strong> = jogos · <strong>V</strong> = vitórias · <strong>PP</strong> = pontos pró ·
  <strong>PC</strong> = pontos contra · <strong>SP</strong> = saldo de pontos (PP − PC)
</div>
<p class="text-muted small mt-1">Desempate: vitórias &gt; saldo de pontos &gt; pontos pró &gt; confronto direto.</p>
<?php
$conteudoAuto = ob_get_clean();

if (isset($_GET['_frag'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $conteudoAuto;
    exit;
}

$pageTitle = 'Ranking';
$activeNav = 'ranking';
require __DIR__ . '/../src/views/header.php';
echo $cabecalho;
echo Views::indicadorAtualizacao();
echo '<div data-autorefresh id="conteudo-auto">' . $conteudoAuto . '</div>';
require __DIR__ . '/../src/views/footer.php';
