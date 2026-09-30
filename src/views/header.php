<?php
/**
 * Cabeçalho comum das páginas públicas.
 * Espera (opcionais): $pageTitle, $activeNav, $bodyClass.
 */
$torneioAtual = Torneio::get();
$pageTitle = $pageTitle ?? $torneioAtual['nome'];
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= e($pageTitle) ?> · <?= e($torneioAtual['nome']) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<nav class="navbar navbar-expand-lg navbar-dark navbar-torneio fixed-top">
  <div class="container">
    <a class="navbar-brand" href="/index.php"><i class="bi bi-sun-fill me-1"></i><?= e($torneioAtual['nome']) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'inicio' ? 'active' : '' ?>" href="/index.php">Início</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'ranking' ? 'active' : '' ?>" href="/ranking.php">Ranking</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'jogos' ? 'active' : '' ?>" href="/jogos.php">Jogos</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'matamata' ? 'active' : '' ?>" href="/mata-mata.php">Mata-mata</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'lancar' ? 'active' : '' ?>" href="/lancar.php">Lançar resultado</a></li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="nav-link btn-admin-nav d-inline-block" href="/admin/">Admin</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
