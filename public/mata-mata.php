<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$times = Times::mapaPorId();
$gerado = MataMata::jaGerado();
$podio = MataMata::podio();

$quartas = $gerado ? [Jogos::porNumero(19), Jogos::porNumero(20), Jogos::porNumero(21), Jogos::porNumero(22)] : [];
$semis = $gerado ? [Jogos::porNumero(23), Jogos::porNumero(24)] : [];
$final = $gerado ? Jogos::porNumero(26) : null;
$terceiro = $gerado ? Jogos::porNumero(25) : null;

ob_start();
?>
<h1 class="h4 mb-2">Mata-mata</h1>
<?php
$cabecalho = ob_get_clean();

ob_start();
?>
<?php if (!$gerado): ?>
  <p class="text-muted">O mata-mata ainda não foi gerado. Ele será montado automaticamente com os 8 melhores times assim que a fase classificatória terminar.</p>
<?php else: ?>

  <?php if ($podio): ?>
  <?= Views::podioHtml($podio) ?>
  <?php endif; ?>

  <div class="chave-scroll">
    <div class="chave-colunas">
      <div class="chave-coluna">
        <h6>Quartas de Final</h6>
        <?php foreach ($quartas as $jogo): ?>
          <?= Views::cardChave($jogo, $times) ?>
        <?php endforeach; ?>
      </div>
      <div class="chave-coluna">
        <h6>Semifinais</h6>
        <?php foreach ($semis as $jogo): ?>
          <?= Views::cardChave($jogo, $times) ?>
        <?php endforeach; ?>
      </div>
      <div class="chave-coluna">
        <h6>Final</h6>
        <?= Views::cardChave($final, $times) ?>
        <div>
          <h6 class="mt-3">Disputa de 3º lugar</h6>
          <?= Views::cardChave($terceiro, $times) ?>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>
<?php
$conteudoAuto = ob_get_clean();

if (isset($_GET['_frag'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $conteudoAuto;
    exit;
}

$pageTitle = 'Mata-mata';
$activeNav = 'matamata';
require __DIR__ . '/../src/views/header.php';
echo $cabecalho;
echo Views::indicadorAtualizacao();
echo '<div data-autorefresh id="conteudo-auto">' . $conteudoAuto . '</div>';
require __DIR__ . '/../src/views/footer.php';
