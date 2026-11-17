<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/cliente/includes/iniciar.php';
$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/index.php'; header('Location: ../login.php'); exit; }
if (!function_exists('pc_e')) { function pc_e($valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8'); } }
$tituloPagina = $tituloPagina ?? 'Painel do Cliente';
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$nomeClientePainel = (string)($cliente['name'] ?? 'Cliente');
$inicialClientePainel = strtoupper(substr($nomeClientePainel, 0, 1));
?>
<!doctype html><html lang="pt-AO"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0A2945"><link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png"><link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png"><title><?= pc_e($tituloPagina) ?> | GPM Conecta</title><link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/customer-panel.css"></head><body>
<div class="customer-layout">
<div class="customer-overlay" id="customerOverlay"></div>
<aside class="customer-sidebar" id="customerSidebar" aria-label="Navegação da área do cliente">
<a class="customer-brand" href="index.php"><img src="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png?v=3" alt="Logótipo GPM Conecta"><span class="brand-wordmark">GPM Conecta<small>ÁREA DO CLIENTE</small></span></a>
<div class="customer-nav-label">A MINHA CONTA</div>
<nav class="customer-nav">
<a class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php"><span class="customer-nav-icon">⌂</span><span>Painel</span></a>
<a class="<?= in_array($paginaAtual, ['pedidos.php','pedido.php','chat.php'], true) ? 'active' : '' ?>" href="pedidos.php"><span class="customer-nav-icon">▤</span><span>Minhas compras</span></a>
<a class="<?= $paginaAtual === 'dados.php' ? 'active' : '' ?>" href="dados.php"><span class="customer-nav-icon">◎</span><span>Meus dados</span></a>
<a class="<?= $paginaAtual === 'alterar-senha.php' ? 'active' : '' ?>" href="alterar-senha.php"><span class="customer-nav-icon">◇</span><span>Segurança</span></a>
</nav>
<div class="customer-sidebar-bottom">
<a class="customer-market-link" href="<?= e(BASE_URL) ?>/cliente/index.php"><span class="customer-nav-icon">↗</span><span>Voltar ao marketplace</span></a>
<div class="customer-user"><span class="customer-avatar"><?= pc_e($inicialClientePainel) ?></span><span><strong><?= pc_e($nomeClientePainel) ?></strong><small>Cliente GPM Conecta</small></span></div>
<form class="customer-logout-form" method="post" action="../logout.php">
<?= csrf_field() ?>
<button class="customer-logout" type="submit">Terminar sessão <span aria-hidden="true">↗</span></button>
</form>
</div>
</aside>
<div class="customer-workspace">
<header class="customer-topbar">
<button type="button" class="customer-menu-toggle" id="customerMenuToggle" aria-label="Abrir menu" aria-controls="customerSidebar" aria-expanded="false">☰</button>
<div><span class="customer-eyebrow">GPM CONECTA / ÁREA RESERVADA</span><h1><?= pc_e($tituloPagina) ?></h1></div>
<div class="customer-topbar-user"><span class="customer-avatar"><?= pc_e($inicialClientePainel) ?></span><span><?= pc_e($nomeClientePainel) ?></span></div>
</header>
<main class="gpm-main">
<script>
(function(){
 const init=()=>{
  const sidebar=document.getElementById('customerSidebar');
  const overlay=document.getElementById('customerOverlay');
  const toggle=document.getElementById('customerMenuToggle');
  if(!sidebar||!overlay||!toggle||toggle.dataset.menuReady==='1')return;
  toggle.dataset.menuReady='1';
  const mobile=window.matchMedia('(max-width: 1024px)');
  const setOpen=(open)=>{
   sidebar.classList.toggle('is-open',open);
   overlay.classList.toggle('is-visible',open);
   document.body.classList.toggle('customer-menu-open',open);
   toggle.setAttribute('aria-expanded',open?'true':'false');
   toggle.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');
   toggle.textContent=open?'✕':'☰';
   sidebar.setAttribute('aria-hidden',open?'false':(mobile.matches?'true':'false'));
  };
  const close=(returnFocus=false)=>{
   setOpen(false);
   if(returnFocus&&mobile.matches)toggle.focus({preventScroll:true});
  };
  toggle.addEventListener('click',()=>setOpen(!sidebar.classList.contains('is-open')));
  overlay.addEventListener('click',()=>close(true));
  sidebar.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{if(mobile.matches)close();}));
  document.addEventListener('keydown',event=>{
   if(event.key==='Escape'&&sidebar.classList.contains('is-open'))close(true);
  });
  mobile.addEventListener('change',()=>close());
  setOpen(false);
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
 else init();
})();
</script>
