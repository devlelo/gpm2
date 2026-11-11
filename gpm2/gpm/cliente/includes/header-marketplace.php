<?php
$clienteHeader = cliente_atual();
$tituloPagina = $tituloPagina ?? 'Marketplace';
?>
<!doctype html>
<html lang="pt-AO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tituloPagina) ?> | GPM — Materiais de Construção</title>
<link rel="stylesheet" href="../assets/css/cliente.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="topbar"><div class="container topbar-inner"><span>GPM — Materiais de Construção</span><span>Angola · Preços em Kz</span></div></div>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="index.php"><span class="brand-mark">G</span><span>GPM<span class="brand-dot">.</span></span></a>
    <button class="menu-toggle" type="button" aria-label="Abrir menu" onclick="document.querySelector('.main-nav').classList.toggle('open')"><i class="fa-solid fa-bars"></i></button>
    <nav class="main-nav">
      <a href="index.php">Marketplace</a>
      <a href="index.php#categorias">Categorias</a>
      <a href="index.php#produtos">Produtos</a>
      <?php if ($clienteHeader): ?>
        <a href="pedidos.php">Os meus pedidos</a>
        <a href="perfil.php">Perfil</a>
        <a href="logout.php">Sair</a>
      <?php else: ?>
        <a href="login.php">Entrar</a>
      <?php endif; ?>
    </nav>
    <a class="cart-link" href="carrinho.php"><i class="fa-solid fa-cart-shopping"></i><span>Carrinho</span><b id="contador-carrinho"><?= gpm_cart_count() ?></b></a>
  </div>
</header>
<main>
