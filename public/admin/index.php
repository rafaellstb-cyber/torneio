<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

if (Auth::check()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    if (Auth::attempt($usuario, $senha)) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $erro = 'Usuário ou senha inválidos.';
}

$torneio = Torneio::get();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login administrativo · <?= e($torneio['nome']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh; background: linear-gradient(135deg, var(--navy-1), var(--navy-2));">
<div class="card shadow p-4" style="max-width: 380px; width: 100%;">
  <h1 class="h4 mb-1 text-center">Área administrativa</h1>
  <p class="text-muted small text-center mb-3"><?= e($torneio['nome']) ?></p>
  <?php if ($erro): ?><div class="alert alert-danger small"><?= e($erro) ?></div><?php endif; ?>
  <form method="post">
    <?= Csrf::field() ?>
    <div class="mb-3">
      <label class="form-label">Usuário</label>
      <input type="text" name="usuario" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Senha</label>
      <input type="password" name="senha" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Entrar</button>
  </form>
  <p class="text-center small mt-3"><a href="/index.php">&larr; voltar ao site público</a></p>
</div>
</body>
</html>
