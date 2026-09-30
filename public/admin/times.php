<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$mensagem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $id = (int) $_POST['id'];
    $nome = trim((string) $_POST['nome']);
    $atletas = trim((string) $_POST['atletas']);
    if ($nome !== '') {
        Times::atualizar($id, $nome, $atletas !== '' ? $atletas : null);
        $mensagem = 'Time atualizado.';
    }
}

$times = Times::todos();

$pageTitle = 'Times';
$activeAdminNav = 'times';
require __DIR__ . '/../../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Times</h1>
<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>

<div class="cards-duas-colunas">
<?php foreach ($times as $t): ?>
  <div class="card p-3 mb-3">
    <form method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <div class="mb-2">
        <label class="form-label small">Código</label>
        <input type="text" class="form-control" value="<?= e($t['codigo']) ?>" disabled>
      </div>
      <div class="mb-2">
        <label class="form-label small">Nome do time</label>
        <input type="text" name="nome" class="form-control" value="<?= e($t['nome']) ?>" required>
      </div>
      <div class="mb-2">
        <label class="form-label small">Atletas (opcional)</label>
        <textarea name="atletas" class="form-control" rows="2"><?= e((string) $t['atletas']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
    </form>
  </div>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../src/views/admin_footer.php'; ?>
