<?php
declare(strict_types=1);
$paginaInstitucionalAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<div class="topline"><span>GPM Conecta · Fornecimento empresarial</span><span>Angola · Empresas &amp; projetos</span></div>
<header id="header">
    <a class="brand" href="index.php" aria-label="GPM Conecta, início">
        <img src="assets/img/GPMlogo.png" alt="GPM Conecta">
    </a>
    <nav class="nav" id="nav" aria-label="Navegação principal">
        <a href="index.php" <?= $paginaInstitucionalAtual === 'index.php' ? 'aria-current="page"' : '' ?>>Início</a>
        <a href="sobre.php" <?= $paginaInstitucionalAtual === 'sobre.php' ? 'aria-current="page"' : '' ?>>A empresa</a>
        <a href="servicos.php" <?= $paginaInstitucionalAtual === 'servicos.php' ? 'aria-current="page"' : '' ?>>Serviços/produtos</a>
        <a href="projetos.php" <?= $paginaInstitucionalAtual === 'projetos.php' ? 'aria-current="page"' : '' ?>>Portfólio</a>
        <a href="localizacao.php" <?= $paginaInstitucionalAtual === 'localizacao.php' ? 'aria-current="page"' : '' ?>>Localização</a>
        <a href="contacto.php" <?= $paginaInstitucionalAtual === 'contacto.php' ? 'aria-current="page"' : '' ?>>Contacto</a>
        <a href="cliente/index.php" class="nav-cta">Marketplace ↗</a>
        <a href="cliente/login.php">Área do cliente</a>
    </nav>
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menu" aria-controls="nav" aria-expanded="false">☰</button>
</header>
<script>
(function(){
 const header=document.getElementById('header');
 const toggle=document.getElementById('menuToggle');
 const nav=document.getElementById('nav');
 if(!header||!toggle||!nav||toggle.dataset.menuReady==='1')return;
 toggle.dataset.menuReady='1';
 const mobile=window.matchMedia('(max-width: 850px)');
 const close=()=>{
   header.classList.remove('menu-open');
   document.body.classList.remove('menu-open');
   toggle.setAttribute('aria-expanded','false');
   toggle.setAttribute('aria-label','Abrir menu');
   toggle.textContent='☰';
 };
 const open=()=>{
   header.classList.add('menu-open');
   document.body.classList.add('menu-open');
   toggle.setAttribute('aria-expanded','true');
   toggle.setAttribute('aria-label','Fechar menu');
   toggle.textContent='×';
 };
 toggle.addEventListener('click',()=>header.classList.contains('menu-open')?close():open());
 nav.querySelectorAll('a').forEach(link=>link.addEventListener('click',close));
 document.addEventListener('keydown',event=>{
   if(event.key==='Escape'&&header.classList.contains('menu-open')){close();toggle.focus();}
 });
 mobile.addEventListener('change',close);
})();
</script>
