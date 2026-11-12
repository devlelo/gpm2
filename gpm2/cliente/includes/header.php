<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/cliente/includes/iniciar.php';
$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/index.php'; header('Location: ../login.php'); exit; }
if (!function_exists('pc_e')) { function pc_e($valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8'); } }
$tituloPagina = $tituloPagina ?? 'Painel do Cliente';
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!doctype html><html lang="pt-AO"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= pc_e($tituloPagina) ?> | GPM Conecta</title><link rel="stylesheet" href="../../assets/css/customer-panel.css"></head><body>
<header class="gpm-header"><div class="gpm-header-inner">
<a href="index.php" class="gpm-logo" aria-label="GPM Conecta — painel do cliente"><img src="../../assets/img/gpm-conecta.png" alt="GPM Conecta"></a>
<button type="button" class="gpm-menu-btn" id="gpm-menu-btn" aria-label="Abrir menu" aria-controls="gpm-nav" aria-expanded="false">☰</button>
<nav class="gpm-nav" id="gpm-nav" aria-label="Navegação do cliente">
<a class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php">Painel</a>
<a class="<?= in_array($paginaAtual, ['pedidos.php','pedido.php'], true) ? 'active' : '' ?>" href="pedidos.php">Minhas compras</a>
<a class="<?= $paginaAtual === 'dados.php' ? 'active' : '' ?>" href="dados.php">Meus dados</a>
<a class="<?= $paginaAtual === 'alterar-senha.php' ? 'active' : '' ?>" href="alterar-senha.php">Segurança</a>
<a href="../../gpm/cliente/index.php">Marketplace</a><a class="gpm-sair" href="../logout.php">Sair</a>
</nav></div></header><main class="gpm-main">
<script>document.addEventListener('DOMContentLoaded',function(){const b=document.getElementById('gpm-menu-btn'),n=document.getElementById('gpm-nav');if(!b||!n)return;b.addEventListener('click',function(){const o=n.classList.toggle('aberto');b.setAttribute('aria-expanded',String(o));b.setAttribute('aria-label',o?'Fechar menu':'Abrir menu')})});</script>
