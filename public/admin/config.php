<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin('/admin/index.php');

$mensagem = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_config') {
        Settings::set('pin_ativo', isset($_POST['pin_ativo']) ? '1' : '0');
        Settings::set('pin_valor', trim((string) $_POST['pin_valor']));
        Settings::set('rodape_texto', trim((string) $_POST['rodape_texto']));
        $mensagem = 'Configurações salvas.';
    }

    if ($acao === 'trocar_senha') {
        $atual = (string) $_POST['senha_atual'];
        $nova = (string) $_POST['senha_nova'];
        $confirmacao = (string) $_POST['senha_confirmacao'];

        if (!Auth::attempt(Auth::usuario(), $atual)) {
            $erro = 'Senha atual incorreta.';
        } elseif (strlen($nova) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($nova !== $confirmacao) {
            $erro = 'A confirmação não confere com a nova senha.';
        } else {
            Auth::trocarSenha((int) $_SESSION['admin_id'], $nova);
            $mensagem = 'Senha alterada com sucesso.';
        }
    }
}

$pageTitle = 'Configurações';
$activeAdminNav = 'config';
require __DIR__ . '/../src/views/admin_header.php';
?>

<h1 class="h4 mb-3">Configurações</h1>
<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<div class="card p-3 mb-4" style="max-width: 520px;">
  <h2 class="h6">Lançamento público</h2>
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="salvar_config">
    <div class="form-check mb-2">
      <input type="checkbox" name="pin_ativo" class="form-check-input" id="pinAtivo" <?= Settings::pinAtivo() ? 'checked' : '' ?>>
      <label class="form-check-label" for="pinAtivo">Exigir PIN do dia para lançar resultados</label>
    </div>
    <div class="mb-3">
      <label class="form-label small">PIN do dia</label>
      <input type="text" name="pin_valor" class="form-control" value="<?= e(Settings::pinValor()) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label small">Texto do rodapé (páginas públicas)</label>
      <input type="text" name="rodape_texto" class="form-control" value="<?= e(Settings::rodapeTexto()) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Salvar configurações</button>
  </form>
</div>

<div class="card p-3" style="max-width: 420px;">
  <h2 class="h6">Trocar senha do admin</h2>
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="acao" value="trocar_senha">
    <div class="mb-2">
      <label class="form-label small">Senha atual</label>
      <input type="password" name="senha_atual" class="form-control" required>
    </div>
    <div class="mb-2">
      <label class="form-label small">Nova senha</label>
      <input type="password" name="senha_nova" class="form-control" required minlength="6">
    </div>
    <div class="mb-3">
      <label class="form-label small">Confirmar nova senha</label>
      <input type="password" name="senha_confirmacao" class="form-control" required minlength="6">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Trocar senha</button>
  </form>
</div>

<?php require __DIR__ . '/../src/views/admin_footer.php'; ?>
