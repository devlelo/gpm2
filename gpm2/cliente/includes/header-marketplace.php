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

<!-- Header Principal -->
<header class="bg-white sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-site mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-4">
        <!-- Logo -->
        <a href="<?= $baseMarketplace ?>/index.php" class="flex items-center gap-2 cursor-pointer flex-shrink-0"><img class="gpm-market-logo" src="<?= e(BASE_URL) ?>/assets/img/GPMlogo.png?v=3" alt=""><span class="gpm-market-wordmark">gpm-conecta</span></a>

        <!-- Barra de Pesquisa (Desktop) -->
        <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="hidden md:flex flex-1 max-w-md bg-gray-50 border border-gray-200 focus-within:border-[#2F776F] transition">
            <input type="search" name="q" placeholder="Pesquisar equipamentos, materiais, ferramentas..." class="w-full px-4 py-2 text-sm text-[#0A2945] bg-transparent focus:outline-none placeholder-gray-400" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
            <button type="submit" class="bg-[#0A2945] hover:bg-[#123653] text-white px-5 flex items-center justify-center transition" aria-label="Pesquisar">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <!-- Menu Mobile Toggle -->
        <button
            type="button"
            class="lg:hidden text-[#0A2945] text-2xl p-2 focus:outline-none flex-shrink-0"
            id="gpm-market-menu-btn"
            aria-label="Abrir menu"
            aria-expanded="false">
            ☰
        </button>

        <!-- Navegação Desktop -->
        <nav class="hidden lg:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider text-[#0A2945] flex-shrink-0">
            <a href="<?= $baseMarketplace ?>/index.php" class="hover:text-[#2F776F] transition">Início</a>
            <a href="<?= e(BASE_URL) ?>/contacto.php" class="hover:text-[#2F776F] transition">Contactos</a>
            <?php if (function_exists('cliente_atual') && cliente_atual()): ?>
                <a href="<?= $baseMarketplace ?>/painel-cliente/index.php" class="hover:text-[#2F776F] transition">Minha Conta</a>
            <?php else: ?>
                <a href="<?= $baseMarketplace ?>/login.php" class="hover:text-[#2F776F] transition">Entrar</a>
            <?php endif; ?>
        </nav>

        <!-- Ações (Carrinho) -->
        <div class="flex items-center space-x-5 flex-shrink-0">
            <a href="<?= $baseMarketplace ?>/carrinho.php" class="flex items-center gap-2 text-[#0A2945] hover:text-[#2F776F] transition group">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span class="bg-[#2F776F] text-white text-[10px] font-bold px-2 py-0.5" id="contador-carrinho">
                    <?= $quantidadeCarrinho ?>
                </span>
            </a>
        </div>
    </div>

    <!-- Navegação Mobile (Escondida por defeito) -->
    <nav class="hidden lg:hidden bg-white border-t border-gray-100" id="gpm-market-nav">
        <div class="px-4 py-2 flex flex-col text-sm font-bold text-[#0A2945]">
            <!-- Barra de Pesquisa Mobile -->
            <form action="<?= $baseMarketplace ?>/index.php" method="GET" class="flex my-3 bg-gray-50 border border-gray-200 focus-within:border-[#2F776F] transition">
                <input type="search" name="q" placeholder="Pesquisar..." class="w-full px-4 py-2 text-sm text-[#0A2945] bg-transparent focus:outline-none placeholder-gray-400" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>">
                <button type="submit" class="bg-[#0A2945] hover:bg-[#123653] text-white px-4 flex items-center justify-center transition" aria-label="Pesquisar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <a href="<?= $baseMarketplace ?>/index.php" class="py-3 border-b border-gray-100 hover:text-[#2F776F]">Início</a>
            <a href="<?= e(BASE_URL) ?>/contacto.php" class="py-3 border-b border-gray-100 hover:text-[#2F776F]">Contactos</a>
            <?php if (function_exists('cliente_atual') && cliente_atual()): ?>
                <a href="<?= $baseMarketplace ?>/painel-cliente/index.php" class="py-3 border-b border-gray-100 hover:text-[#2F776F]">Minha Conta</a>
            <?php else: ?>
                <a href="<?= $baseMarketplace ?>/login.php" class="py-3 border-b border-gray-100 hover:text-[#2F776F]">Entrar</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="gpm-market-main flex-1">