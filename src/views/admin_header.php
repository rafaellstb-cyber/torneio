<?php
/**
 * Cabeçalho comum das páginas administrativas (autenticadas).
 * Espera: $pageTitle, $activeAdminNav.
 */
$torneioAtual = Torneio::get();
$activeAdminNav = $activeAdminNav ?? '';
$rankingAlerta = Ranking::calcular();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= e($pageTitle ?? 'Admin') ?> · Admin · <?= e($torneioAtual['nome']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-torneio fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="/admin/dashboard.php"><i class="bi bi-shield-lock-fill me-1"></i>Admin · <?= e($torneioAtual['nome']) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navAdmin">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/index.php" target="_blank">Ver site público <i class="bi bi-box-arrow-up-right"></i></a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/logout.php">Sair (<?= e(Auth::usuario() ?? '') ?>)</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid" style="padding-top: 62px;">
  <div class="row">
    <div class="col-12 col-md-3 col-lg-2 admin-sidebar py-3 px-0">
      <ul class="nav flex-md-column">
        <?php
        $itens = [
            'dashboard' => ['Painel', '/admin/dashboard.php', 'bi-speedometer2'],
            'torneio' => ['Torneio', '/admin/torneio.php', 'bi-info-circle'],
            'times' => ['Times', '/admin/times.php', 'bi-people'],
            'jogos' => ['Jogos', '/admin/jogos.php', 'bi-list-ol'],
            'desempate' => ['Desempate', '/admin/desempate.php', 'bi-arrow-down-up'],
            'config' => ['Configurações', '/admin/config.php', 'bi-gear'],
        ];
        foreach ($itens as $chave => [$rotulo, $url, $icone]):
        ?>
          <li class="nav-item">
            <a class="nav-link px-3 <?= $activeAdminNav === $chave ? 'active' : '' ?>" href="<?= $url ?>">
              <i class="bi <?= $icone ?> me-2"></i><?= $rotulo ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="col-12 col-md-9 col-lg-10 py-4">
      <?php if ($rankingAlerta['tem_empate_pendente'] && $activeAdminNav !== 'desempate'): ?>
        <div class="card card-alerta-empate p-3 mb-3">
          <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
          Há empate pendente na classificação geral que afeta a ordem das posições 1 a 9 (e, portanto, o mata-mata).
          <a href="/admin/desempate.php" class="ms-1 fw-semibold">Resolver agora</a>
        </div>
      <?php endif; ?>
