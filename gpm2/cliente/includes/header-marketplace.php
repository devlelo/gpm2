<?php
require_once __DIR__ . '/iniciar.php';

$empresaMarketplace = db()->query('SELECT phone, email FROM company_settings ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$telefoneMarketplace = (string)($empresaMarketplace['phone'] ?? '');
$emailMarketplace = (string)($empresaMarketplace['email'] ?? '');

$baseMarketplace = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($baseMarketplace === '.' || $baseMarketplace === '/') {
    $baseMarketplace = '';
}

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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
                            navy: '#0d233f',
                            navyLight: '#163154',
                            orange: '#f96b21',
                            orangeHover: '#e05812',
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
<div class="bg-[#0d233f] text-white text-[11px] py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-6 flex justify-between items-center">
        <div class="flex items-center gap-6">
            <?php if ($telefoneMarketplace !== ''): ?><a href="tel:<?= htmlspecialchars($telefoneMarketplace, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-phone text-[#f96b21] mr-2"></i><?= htmlspecialchars($telefoneMarketplace, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
            <?php if ($emailMarketplace !== ''): ?><a class="hidden sm:inline" href="mailto:<?= htmlspecialchars($emailMarketplace, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-envelope text-[#f96b21] mr-2"></i><?= htmlspecialchars($emailMarketplace, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
        </div>
        <div class="flex items-center gap-4">
            <span class="hidden md:inline">Seg - Sáb: 08:00 - 18:00</span>
        </div>
    </div>
</div>

<!-- Header Principal -->
<header class="bg-white sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-site mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
        <!-- Logo -->
        <a href="<?= $baseMarketplace ?>/index.php" class="flex items-center gap-2 cursor-pointer flex-shrink-0">
            <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span class="text-2xl font-black text-[#0d233f] tracking-tighter block leading-none">GPM.</span>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mt-1">Equipamentos &amp; Suprimentos</span>
            </div>
        </a>

        <!-- Barra de Pesquisa (Desktop) -->
        <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="hidden md:flex flex-1 max-w-md bg-gray-50 border border-gray-200 focus-within:border-[#f96b21] transition">
            <input type="search" name="q" placeholder="Pesquisar equipamentos, materiais, ferramentas..." class="w-full px-4 py-2 text-sm text-[#0d233f] bg-transparent focus:outline-none placeholder-gray-400" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
            <button type="submit" class="bg-[#0d233f] hover:bg-[#163154] text-white px-5 flex items-center justify-center transition" aria-label="Pesquisar">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <!-- Menu Mobile Toggle -->
        <button
            type="button"
            class="lg:hidden text-[#0d233f] text-2xl p-2 focus:outline-none flex-shrink-0"
            id="gpm-market-menu-btn"
            aria-label="Abrir menu"
            aria-expanded="false">
            ☰
        </button>

        <!-- Navegação Desktop -->
        <nav class="hidden lg:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider text-[#0d233f] flex-shrink-0">
            <a href="<?= $baseMarketplace ?>/index.php" class="hover:text-[#f96b21] transition">Início</a>
            <a href="<?= $baseMarketplace ?>/index.php#contactos" class="hover:text-[#f96b21] transition">Contactos</a>
            <?php if (function_exists('cliente_atual') && cliente_atual()): ?>
                <a href="../cliente/painel-cliente/index.php" class="hover:text-[#f96b21] transition">Minha Conta</a>
            <?php else: ?>
                <a href="<?= $baseMarketplace ?>/login.php" class="hover:text-[#f96b21] transition">Entrar</a>
            <?php endif; ?>
        </nav>

        <!-- Ações (Carrinho) -->
        <div class="flex items-center space-x-5 flex-shrink-0">
            <a href="<?= $baseMarketplace ?>/carrinho.php" class="flex items-center gap-2 text-[#0d233f] hover:text-[#f96b21] transition group">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span class="bg-[#f96b21] text-white text-[10px] font-bold px-2 py-0.5" id="contador-carrinho">
                    <?= $quantidadeCarrinho ?>
                </span>
            </a>
        </div>
    </div>

    <!-- Navegação Mobile (Escondida por defeito) -->
    <nav class="hidden lg:hidden bg-white border-t border-gray-100" id="gpm-market-nav">
        <div class="px-4 py-2 flex flex-col text-sm font-bold text-[#0d233f]">
            <!-- Barra de Pesquisa Mobile -->
            <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="flex my-3 bg-gray-50 border border-gray-200 focus-within:border-[#f96b21] transition">
                <input type="search" name="q" placeholder="Pesquisar..." class="w-full px-4 py-2 text-sm text-[#0d233f] bg-transparent focus:outline-none placeholder-gray-400" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
                <button type="submit" class="bg-[#0d233f] hover:bg-[#163154] text-white px-4 flex items-center justify-center transition" aria-label="Pesquisar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <a href="<?= $baseMarketplace ?>/index.php" class="py-3 border-b border-gray-100 hover:text-[#f96b21]">Início</a>
            <a href="<?= $baseMarketplace ?>/index.php#contactos" class="py-3 border-b border-gray-100 hover:text-[#f96b21]">Contactos</a>
            <?php if (function_exists('cliente_atual') && cliente_atual()): ?>
                <a href="../cliente/painel-cliente/index.php" class="py-3 border-b border-gray-100 hover:text-[#f96b21]">Minha Conta</a>
            <?php else: ?>
                <a href="<?= $baseMarketplace ?>/login.php" class="py-3 border-b border-gray-100 hover:text-[#f96b21]">Entrar</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="gpm-market-main flex-1">