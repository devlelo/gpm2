<?php
require_once __DIR__ . '/iniciar.php';

$empresaMarketplace = db()->query('SELECT phone, email FROM company_settings ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$telefoneMarketplace = (string)($empresaMarketplace['phone'] ?? '');
$emailMarketplace = (string)($empresaMarketplace['email'] ?? '');

$baseMarketplace = rtrim(BASE_URL, '/') . '/cliente';
$baseMarketplace = htmlspecialchars($baseMarketplace, ENT_QUOTES, 'UTF-8');

$quantidadeCarrinho = 0;
$carrinho = $_SESSION['gpm_cart'] ?? [];

if (is_array($carrinho)) {
    foreach ($carrinho as $quantidade) {
        $quantidadeCarrinho += max(0, (int) $quantidade);
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><style>.gpm-market-logo{display:block;width:52px;height:52px;object-fit:contain;flex:none}.gpm-market-wordmark{font-size:clamp(21px,2vw,27px);font-weight:900;letter-spacing:-1px;color:#0A2945;white-space:nowrap;line-height:1}@media(max-width:640px){.gpm-market-logo{width:44px;height:44px}.gpm-market-wordmark{font-size:21px}}</style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A2945"><link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png?v=3">

    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') . ' | GPM' : 'GPM — Equipamentos e Suprimentos' ?></title>

    <meta name="description" content="GPM — Marketplace de equipamentos, materiais, ferramentas e suprimentos para empresas e profissionais.">

    <!-- Tailwind + Font Awesome + Google Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#0A2945',
                            navyLight: '#123653',
                            teal: '#2F776F',
                            navyHover: '#123653',
                            offWhite: '#f8f9fa'
                        }
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'sans-serif'],
                    },
                    maxWidth: {
                        'site': '1280px'
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #333;
            margin: 0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- Top Bar -->
<div class="bg-[#0A2945] text-white text-[11px] py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-6 flex justify-between items-center">
        <div class="flex items-center gap-6">
            <?php if ($telefoneMarketplace !== ''): ?><a href="tel:<?= htmlspecialchars($telefoneMarketplace, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-phone text-[#2F776F] mr-2"></i><?= htmlspecialchars($telefoneMarketplace, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
            <?php if ($emailMarketplace !== ''): ?><a class="hidden sm:inline" href="mailto:<?= htmlspecialchars($emailMarketplace, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-envelope text-[#2F776F] mr-2"></i><?= htmlspecialchars($emailMarketplace, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
        </div>
        <div class="flex items-center gap-4">
            <span class="hidden md:inline">Seg - Sáb: 08:00 - 18:00</span>
        </div>
    </div>
</div>

<!-- Navegação única do marketplace: desktop e mobile usam os mesmos links -->
<header class="bg-white sticky top-0 z-50 border-b border-gray-100" id="gpm-market-header">
    <div class="max-w-site mx-auto px-4 sm:px-6 min-h-20 flex items-center justify-between gap-4">
        <a href="<?= $baseMarketplace ?>/index.php" class="flex items-center gap-2 cursor-pointer flex-shrink-0" aria-label="GPM Conecta - Início">
            <img class="gpm-market-logo" src="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png?v=3" alt="Logótipo GPM Conecta">
            <span class="gpm-market-wordmark">GPM Conecta</span>
        </a>

        <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="hidden lg:flex flex-1 max-w-sm bg-gray-50 border border-gray-200 focus-within:border-[#2F776F] transition">
            <input type="search" name="q" placeholder="Pesquisar produtos e materiais..." class="w-full px-4 py-2 text-sm text-[#0A2945] bg-transparent focus:outline-none placeholder-gray-400" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
            <button type="submit" class="bg-[#0A2945] hover:bg-[#123653] text-white px-4 flex items-center justify-center transition" aria-label="Pesquisar">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <button type="button" class="gpm-market-menu-btn lg:hidden" id="gpm-market-menu-btn" aria-label="Abrir menu" aria-controls="gpm-market-nav" aria-expanded="false">
            <span class="gpm-menu-open-icon" aria-hidden="true">☰</span>
            <span class="gpm-menu-close-icon" aria-hidden="true">✕</span>
        </button>

        <nav class="gpm-market-nav" id="gpm-market-nav" aria-label="Navegação principal">
            <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="gpm-market-mobile-search">
                <input type="search" name="q" placeholder="Pesquisar produtos e materiais..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
                <button type="submit" aria-label="Pesquisar"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <a href="<?= $baseMarketplace ?>/index.php" class="gpm-market-nav-link">Início</a>
            <a href="<?= e(BASE_URL) ?>/servicos.php" class="gpm-market-nav-link">Serviços</a>
            <a href="<?= $baseMarketplace ?>/index.php#categorias" class="gpm-market-nav-link">Categorias</a>
            <a href="<?= $baseMarketplace ?>/index.php#catalogo" class="gpm-market-nav-link">Produtos</a>
            <a href="<?= $baseMarketplace ?>/index.php#mais-vendidos" class="gpm-market-nav-link">Destaques</a>
            <a href="<?= e(BASE_URL) ?>/contacto.php" class="gpm-market-nav-link">Contactos</a>
            <?php if (function_exists('cliente_atual') && cliente_atual()): ?>
                <a href="<?= $baseMarketplace ?>/painel-cliente/index.php" class="gpm-market-nav-link">Minha Conta</a>
            <?php else: ?>
                <a href="<?= $baseMarketplace ?>/login.php" class="gpm-market-nav-link">Entrar</a>
            <?php endif; ?>
        </nav>

        <a href="<?= $baseMarketplace ?>/carrinho.php" class="flex items-center gap-2 text-[#0A2945] hover:text-[#2F776F] transition group flex-shrink-0" aria-label="Abrir carrinho">
            <i class="fa-solid fa-cart-shopping text-lg"></i>
            <span class="bg-[#2F776F] text-white text-[10px] font-bold px-2 py-0.5" id="contador-carrinho"><?= $quantidadeCarrinho ?></span>
        </a>
    </div>
</header>

<style>
#gpm-market-header{position:sticky;top:0;z-index:100}
.gpm-market-nav{display:flex;align-items:center;justify-content:center;gap:clamp(12px,1.6vw,24px);padding:0 10px;flex:1;min-width:0}
.gpm-market-nav-link{display:inline-flex;align-items:center;min-height:44px;color:#0A2945;font-size:11px;font-weight:800;letter-spacing:.055em;text-transform:uppercase;white-space:nowrap;transition:color .18s ease}
.gpm-market-nav-link:hover,.gpm-market-nav-link:focus-visible{color:#2F776F}
.gpm-market-menu-btn{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:1px solid #e2e8ee;background:#fff;color:#0A2945;font-size:21px;line-height:1;cursor:pointer}
.gpm-menu-close-icon{display:none}
.gpm-market-mobile-search{display:none}
@media(max-width:1023px){
 .gpm-market-nav{display:none;position:absolute;top:100%;left:0;right:0;z-index:101;align-items:stretch;justify-content:flex-start;flex-direction:column;gap:0;padding:10px 20px 18px;background:#fff;border-top:1px solid #edf1f4;border-bottom:1px solid #dce3e9;box-shadow:0 16px 28px rgba(10,41,69,.13);max-height:calc(100vh - 80px);overflow-y:auto}
 .gpm-market-nav.is-open{display:flex}
 .gpm-market-nav-link{width:100%;min-height:48px;border-bottom:1px solid #edf1f4;font-size:12px}
 .gpm-market-menu-btn{display:inline-flex}
 .gpm-market-menu-btn[aria-expanded="true"] .gpm-menu-open-icon{display:none}
 .gpm-market-menu-btn[aria-expanded="true"] .gpm-menu-close-icon{display:inline}
 .gpm-market-mobile-search{display:flex;width:100%;margin:4px 0 10px;border:1px solid #dce3e9;background:#f8fafb}
 .gpm-market-mobile-search input{min-width:0;flex:1;padding:12px;border:0;outline:0;background:transparent;color:#0A2945;font-size:14px}
 .gpm-market-mobile-search button{width:46px;flex:none;background:#0A2945;color:#fff}
}
@media(min-width:1024px) and (max-width:1240px){.gpm-market-nav{gap:10px}.gpm-market-nav-link{font-size:10px}}
@media(prefers-reduced-motion:reduce){.gpm-market-nav-link{transition:none}}
</style>
<script>
(function(){
 const header=document.getElementById('gpm-market-header');
 const button=document.getElementById('gpm-market-menu-btn');
 const menu=document.getElementById('gpm-market-nav');
 if(!header||!button||!menu||button.dataset.menuReady==='1')return;
 button.dataset.menuReady='1';
 const mobile=window.matchMedia('(max-width: 1023px)');
 const close=()=>{
   menu.classList.remove('is-open');
   button.setAttribute('aria-expanded','false');
   button.setAttribute('aria-label','Abrir menu');
 };
 const open=()=>{
   menu.classList.add('is-open');
   button.setAttribute('aria-expanded','true');
   button.setAttribute('aria-label','Fechar menu');
 };
 button.addEventListener('click',()=>menu.classList.contains('is-open')?close():open());
 menu.querySelectorAll('a').forEach(link=>link.addEventListener('click',close));
 document.addEventListener('click',event=>{if(mobile.matches&&!header.contains(event.target))close();});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&menu.classList.contains('is-open')){close();button.focus();}});
 mobile.addEventListener('change',close);
})();
</script>

<main class="gpm-market-main flex-1">