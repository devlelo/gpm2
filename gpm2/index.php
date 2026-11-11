<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pdo = db();

$empresa = [
    'nome'       => 'GPM Conecta',
    'nome_completo' => 'GPM Conecta - Soluções em Suprimentos e Procurement',
    'descricao'  => 'O seu parceiro estratégico no fornecimento de equipamentos, materiais e serviços.',
    'telefone'   => '',
    'whatsapp'   => '',
    'email'      => '',
    'endereco'   => '',
    'cidade'     => '',
    'provincia'  => '',
];

try {
    $stmtEmpresa = $pdo->query('SELECT company_name, phone, email, address, city, province FROM company_settings ORDER BY id LIMIT 1');
    $dadosEmpresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
    if ($dadosEmpresa) {
        $empresa['nome'] = $dadosEmpresa['company_name'] ?: $empresa['nome'];
        $empresa['nome_completo'] = $empresa['nome'];
        $empresa['telefone'] = (string)($dadosEmpresa['phone'] ?? '');
        $empresa['whatsapp'] = preg_replace('/[^0-9]/', '', $empresa['telefone']) ?: '';
        $empresa['email'] = (string)($dadosEmpresa['email'] ?? '');
        $empresa['endereco'] = (string)($dadosEmpresa['address'] ?? '');
        $empresa['cidade'] = (string)($dadosEmpresa['city'] ?? '');
        $empresa['provincia'] = (string)($dadosEmpresa['province'] ?? '');
    }
} catch (PDOException $e) {
    error_log('[GPM homepage company settings] ' . $e->getMessage());
}

$partesLocalizacao = array_values(array_filter([$empresa['endereco'], $empresa['cidade'], $empresa['provincia']]));
$localizacaoEmpresa = $partesLocalizacao ? implode(', ', array_merge($partesLocalizacao, ['Angola'])) : '';
$consultaMapaEmpresa = $localizacaoEmpresa !== '' ? $localizacaoEmpresa : 'Angola';
$urlMapaEmpresa = 'https://maps.google.com/maps?q=' . rawurlencode($consultaMapaEmpresa) . '&t=&z=6&ie=UTF8&iwloc=&output=embed';

$produtos = [];
$erroContacto = '';
$sucessoContacto = isset($_GET['contacto']) &&
    $_GET['contacto'] === 'sucesso';

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['formulario'] ?? '') === 'contacto') {

    verify_csrf();

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $assunto = trim((string) ($_POST['assunto'] ?? ''));
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));

    if (
        $nome === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $mensagem === ''
    ) {
        $erroContacto = 'Preencha o nome, um e-mail válido e a mensagem.';
    } elseif (
        mb_strlen($nome) > 150 ||
        mb_strlen($email) > 190 ||
        mb_strlen($telefone) > 40 ||
        mb_strlen($assunto) > 180 ||
        mb_strlen($mensagem) > 5000
    ) {
        $erroContacto = 'Alguns campos ultrapassam o tamanho permitido.';
    } else {
        try {
            $sql = "INSERT INTO contact_messages
                    (name, email, phone, subject, message, created_at)
                    VALUES
                    (:nome, :email, :telefone, :assunto, :mensagem, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nome'     => $nome,
                'email'    => $email,
                'telefone' => $telefone !== '' ? $telefone : null,
                'assunto'  => $assunto !== '' ? $assunto : null,
                'mensagem' => $mensagem,
            ]);

            header('Location: index.php?contacto=sucesso#contactos');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erroContacto = 'Não foi possível enviar a mensagem. Tente novamente.';
        }
    }
}

// Carregar produtos para a página inicial (ex: os 4 mais recentes ou em destaque)
try {
    $stmt = $pdo->query(
        "SELECT id, name, slug, description, price, main_image
         FROM products
         WHERE active = 1
         ORDER BY featured DESC, created_at DESC
         LIMIT 4"
    );

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$carrinhoHome = $_SESSION['gpm_cart'] ?? [];
$quantidadeCarrinhoHome = is_array($carrinhoHome)
    ? array_sum(array_map('intval', $carrinhoHome))
    : 0;

function imagemPublica(?string $imagem): string
{
    if (!$imagem) return '';
    $imagem = str_replace('\\', '/', $imagem);
    $imagem = ltrim($imagem, '/');
    if (str_contains($imagem, '..') || preg_match('/^[a-z]+:/i', $imagem)) return '';
    $candidatos = [
        $imagem, 'uploads/produtos/' . basename($imagem), 'uploads/products/' . basename($imagem),
        'public/uploads/produtos/' . basename($imagem), 'public/uploads/products/' . basename($imagem),
    ];
    foreach ($candidatos as $caminho) {
        if (is_file(__DIR__ . '/' . $caminho)) return $caminho;
    }
    return '';
}

function precoKz(mixed $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' Kz';
}

$seoTitle = $empresa['nome'] . ' | Parceiro Estratégico em Suprimentos e Procurement';
$seoDescription = 'GPM Conecta: procurement, representação comercial, fornecimento de equipamentos, insumos e serviços para indústria, obras e empresas em Angola.';
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">

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
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; color: #333; overflow-x: hidden; }
        
        /* Efeito Watermark para os títulos */
        .watermark {
            position: absolute;
            top: -20px;
            left: 0;
            font-size: clamp(6rem, 15vw, 14rem);
            font-weight: 900;
            color: #f1f3f6;
            z-index: 0;
            line-height: 1;
            letter-spacing: -0.05em;
            user-select: none;
            white-space: nowrap;
            overflow: hidden;
        }
        .watermark-dark {
            color: rgba(255, 255, 255, 0.03);
        }
        .section-title {
            position: relative;
            z-index: 10;
            font-size: 3rem;
            font-weight: 900;
            color: #0d233f;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }

        /* Animações de entrada */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hover nos cards de serviço */
        .service-card:hover {
            border-bottom-color: #f96b21;
        }

        /* Slider Horizontal */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Faixa de marcas: movimento contínuo, sem saltos */
        @keyframes gpm-brand-scroll {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }
        .brand-marquee-viewport { overflow: hidden; width: 100%; }
        .brand-marquee-track {
            display: flex;
            width: max-content;
            animation: gpm-brand-scroll 42s linear infinite;
            will-change: transform;
        }
        .brand-marquee-group {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: clamp(2.5rem, 5vw, 5rem);
            padding: 0 clamp(1.25rem, 3vw, 3rem);
        }
        .brand-name {
            flex: 0 0 auto;
            color: rgba(255,255,255,.88);
            font-size: clamp(1rem, 1.8vw, 1.45rem);
            font-weight: 900;
            letter-spacing: -.045em;
            white-space: nowrap;
            transition: color .2s ease, transform .2s ease;
        }
        .brand-name:nth-child(even) { color: #f96b21; }
        .brand-name:hover { color: #fff; transform: translateY(-3px); }
        .brand-marquee:hover .brand-marquee-track,
        .brand-marquee-viewport:focus-within .brand-marquee-track { animation-play-state: paused; }

        /* --- GRÁFICOS CIRCULARES ANIMADOS --- */
        .progress-circle {
            transition: stroke-dashoffset 2s ease-out;
        }

        /* Transição suave do conteúdo do projeto */
        .project-content-fade {
            transition: opacity 0.3s ease-in-out;
        }

        /* Acordeão para FAQs */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
            opacity: 0;
        }
        .accordion-content.open {
            opacity: 1;
        }
        .accordion-icon {
            transition: transform 0.3s ease;
        }
        .accordion-icon.rotate {
            transform: rotate(45deg); /* Transforma o + num X */
        }

        /* Hover dos Profissionais */
        .team-image-overlay {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease-in-out;
        }
        .team-card:hover .team-image-overlay {
            opacity: 1;
            transform: translateY(0);
        }
        .team-card:hover img {
            transform: scale(1.05);
        }
        .team-card img {
            transition: transform 0.5s ease;
        }

        /* Cards de Produtos da Home */
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            background: #FFF;
            border: 1px solid #F3F4F6;
        }
        .product-card:hover {
            box-shadow: 0 10px 25px rgba(13,35,63,0.08);
            transform: translateY(-5px);
            border-color: #f96b21;
        }
        .product-card:hover .product-img {
            transform: scale(1.08);
        }
        .product-img {
            transition: transform 0.4s ease;
        }
        /* Acabamento visual: identidade mais consistente e menos elementos genéricos */
        :root {
            --gpm-navy: #0d233f;
            --gpm-orange: #f96b21;
            --gpm-ink: #263548;
            --gpm-muted: #687586;
        }
        ::selection { background: rgba(249,107,33,.2); color: var(--gpm-navy); }
        a, button { -webkit-tap-highlight-color: transparent; }
        a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 3px solid rgba(249,107,33,.45);
            outline-offset: 3px;
        }
        header {
            box-shadow: 0 5px 24px rgba(13,35,63,.035);
            backdrop-filter: saturate(150%) blur(12px);
        }
        .section-title {
            font-size: clamp(2rem, 4.2vw, 3.15rem);
            line-height: 1.05;
            letter-spacing: -.045em;
        }
        .watermark {
            font-size: clamp(5rem, 13vw, 12rem);
            letter-spacing: -.075em;
            opacity: .78;
        }
        .service-card {
            border-radius: 3px;
            transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
        }
        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 38px rgba(13,35,63,.09);
        }
        .product-card {
            border-radius: 4px;
            overflow: hidden;
        }
        .product-card:hover { transform: translateY(-6px); }
        .sector-card img { transition: transform .55s cubic-bezier(.2,.7,.2,1); }
        .sector-card:hover img { transform: scale(1.055); }
        .sector-card > div:first-child { background: #f2f3f4; }
        .accordion-item:last-child { border-bottom: 0; }
        .accordion-header { gap: 1rem; }
        .accordion-header span:first-child { line-height: 1.5; }
        .logos-container { position: relative; }
        .project-content-fade { will-change: opacity; }
        #gpm-home-mobile-nav a { padding: .35rem 0; }
        #gpm-home-mobile-nav a:focus-visible { outline-offset: 5px; }
        @media (max-width: 767px) {
            .watermark { top: 0; font-size: 5rem; }
            .section-title { margin-bottom: 2.5rem; }
            #inicio { padding-top: 3.5rem; padding-bottom: 4.5rem; }
            #inicio h1 { font-size: clamp(2.7rem, 12vw, 4.4rem); }
            #project-image { height: 270px; }
            .team-image-overlay { opacity: 1; transform: none; }
            .logos-container { padding-top: 1.4rem; padding-bottom: 1.4rem; }
        }
        @media (max-width: 767px) {
            .brand-marquee { padding-top: 1.5rem; padding-bottom: 1.5rem; }
            .brand-marquee-group { gap: 2.25rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .brand-marquee-track { animation: none; }
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="bg-[#0d233f] text-white text-[11px] py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-6 flex justify-between items-center">
        <div class="flex items-center gap-6">
            <?php if ($empresa['telefone'] !== ''): ?><a href="tel:<?= e($empresa['telefone']) ?>"><i class="fa-solid fa-phone text-[#f96b21] mr-2"></i><?= e($empresa['telefone']) ?></a><?php endif; ?>
            <?php if ($empresa['email'] !== ''): ?><a class="hidden sm:inline" href="mailto:<?= e($empresa['email']) ?>"><i class="fa-solid fa-envelope text-[#f96b21] mr-2"></i><?= e($empresa['email']) ?></a><?php endif; ?>
        </div>
        <div class="flex items-center gap-4">
            <span class="hidden md:inline">Seg - Sáb: 08:00 - 18:00</span>
            <span class="hidden sm:inline border-l border-gray-600 pl-4 text-gray-300 tracking-wider">SUPRIMENTOS · PROCUREMENT · ANGOLA</span>
        </div>
    </div>
</div>

<!-- Header -->
<header class="bg-white sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-site mx-auto px-6 h-20 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-2xl font-black text-[#0d233f] tracking-tighter">GPM.</span>
        </a>

        <nav class="hidden lg:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider text-[#0d233f]">
            <a href="#inicio" class="text-[#f96b21]">Início</a>
            <a href="#servicos" class="hover:text-[#f96b21] transition">Serviços</a>
            <a href="#solucoes" class="hover:text-[#f96b21] transition">Soluções</a>
            <a href="#projetos" class="hover:text-[#f96b21] transition">Projetos</a>
            <a href="cliente/index.php" class="hover:text-[#f96b21] transition">Ir ao Marketplace</a>
        </nav>

        <div class="flex items-center space-x-5">
            <a href="cliente/carrinho.php" class="flex items-center gap-2 text-[#0d233f] hover:text-[#f96b21] transition">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="bg-[#f96b21] text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= (int) $quantidadeCarrinhoHome ?></span>
            </a>
            <button id="gpm-home-menu-btn" type="button" class="lg:hidden text-[#0d233f] text-xl" aria-label="Abrir menu" aria-controls="gpm-home-mobile-nav" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
        </div>
    </div>
</header>

<nav id="gpm-home-mobile-nav" class="hidden lg:hidden bg-white border-b border-gray-100 px-6 py-5 flex-col gap-5 text-xs font-bold uppercase tracking-wider text-[#0d233f]" aria-label="Menu móvel">
    <a href="#inicio">Início</a>
    <a href="#servicos">Serviços</a>
    <a href="#solucoes">Soluções</a>
    <a href="#projetos">Projetos</a>
    <a href="#contactos">Contactos</a>
    <a href="cliente/index.php" class="text-[#f96b21]">Ir ao Marketplace</a>
</nav>

<main>

    <!-- Hero Section -->
    <section class="relative bg-white pt-20 pb-32 overflow-hidden" id="inicio">
        <div class="absolute top-0 right-0 w-3/4 h-full bg-gray-50 z-0 hidden md:block" style="clip-path: polygon(20% 0, 100% 0, 100% 100%, 0% 100%);"></div>
        <div class="absolute right-0 top-0 w-1/2 h-full z-0 hidden lg:block">
             <img src="https://images.unsplash.com/photo-1581092160562-40aa08e78837?q=80&w=1400&auto=format&fit=crop" 
                  class="w-full h-full object-cover shadow-2xl" 
                  style="clip-path: polygon(25% 0, 100% 0, 100% 100%, 0% 100%);" 
                  alt="Fornecimento e Logística">
        </div>

        <div class="max-w-site mx-auto px-6 relative z-10">
            <div class="max-w-2xl space-y-6 reveal pt-10">
                <span class="inline-flex items-center gap-3 text-[10px] md:text-xs font-extrabold tracking-[.22em] text-[#f96b21] uppercase">
                    <span class="w-8 h-[2px] bg-[#f96b21]"></span> Suprimentos · Procurement · Fornecimento B2B
                </span>
                <h1 class="text-5xl md:text-7xl font-black text-[#0d233f] leading-[1.04] uppercase tracking-tight">
                    Fornecemos <br>soluções para <br><span class="text-[#f96b21]">o seu negócio.</span>
                </h1>
                <p class="text-gray-600 text-sm md:text-base max-w-lg leading-relaxed">
                    Ajudamos empresas a encontrar, comprar e receber o que precisam para trabalhar: materiais, equipamentos, produtos industriais e soluções profissionais, nacionais ou importadas.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <!-- CTA Principal: Levar ao Marketplace -->
                    <a href="cliente/index.php" class="bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition shadow-lg flex items-center gap-2">
                        <i class="fa-solid fa-store"></i> Entrar no Marketplace
                    </a>
                    <a href="#contactos" class="border border-[#0d233f] text-[#0d233f] hover:bg-[#0d233f] hover:text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition">
                        Falar Connosco
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-24 relative overflow-hidden" id="servicos">
        <div class="watermark">SERVIÇOS</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="section-title mb-16 reveal">Serviços</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Procurement e Gestão</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Gerenciamos todo o processo de cotação e negociação para a sua empresa.</p>
                    <a href="cliente/index.php" class="text-[#f96b21] font-bold text-xl hover:text-[#e05812] transition">+</a>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 100ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Fornecimento B2B</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Fornecimento de equipamentos, materiais, insumos e artigos profissionais.</p>
                    <a href="cliente/index.php" class="text-[#f96b21] font-bold text-xl hover:text-[#e05812] transition">+</a>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 200ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Representação</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Representação comercial de equipamentos nacionais e importados.</p>
                    <a href="cliente/index.php" class="text-[#f96b21] font-bold text-xl hover:text-[#e05812] transition">+</a>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 300ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Importação</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Importação de insumos industriais e materiais especializados.</p>
                    <a href="cliente/index.php" class="text-[#f96b21] font-bold text-xl hover:text-[#e05812] transition">+</a>
                </div>
            </div>
        </div>
    </section>

    <!-- NEW: Products Section (Produtos em Destaque na Home) -->
    <section class="py-24 bg-[#f8f9fa] relative">
        <div class="max-w-site mx-auto px-6">
            <!-- Cabeçalho da secção com botão "Ver Mais" -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-10 border-b border-gray-200 pb-2 gap-4 reveal">
                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] uppercase tracking-tight">Produtos em Destaque</h2>
                    <p class="text-gray-500 text-sm mt-1">Veja alguns dos artigos disponíveis no marketplace.</p>
                </div>
                <a href="cliente/index.php" class="bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-6 py-3 transition shadow-md flex items-center gap-2 whitespace-nowrap">
                    Ver Mais Produtos <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Grid de Produtos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if ($produtos): ?>
                    <?php foreach ($produtos as $produto): ?>
                        <?php
                        $imagem = imagemPublica($produto['main_image'] ?? null);
                        $descricao = trim((string) ($produto['description'] ?? ''));
                        ?>
                        <article class="product-card flex flex-col justify-between group reveal">
                            <div>
                                <a href="cliente/produto.php?id=<?= (int) $produto['id'] ?>" class="block relative bg-white h-56 overflow-hidden flex items-center justify-center border-b border-gray-100 p-4">
                                    <?php if ($imagem !== ''): ?>
                                        <img src="<?= e($imagem) ?>" alt="<?= e($produto['name']) ?>" loading="lazy" class="product-img max-h-full max-w-full object-contain mix-blend-multiply">
                                    <?php else: ?>
                                        <i class="fa-solid fa-box text-gray-300 text-5xl"></i>
                                    <?php endif; ?>
                                </a>

                                <div class="p-5 text-left">
                                    <h3 class="text-sm font-bold text-[#0d233f] mb-2 line-clamp-1 group-hover:text-[#f96b21] transition">
                                        <?= e($produto['name']) ?>
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-4 line-clamp-2 leading-relaxed">
                                        <?= e(function_exists('mb_strimwidth') ? mb_strimwidth($descricao, 0, 60, '…', 'UTF-8') : substr($descricao, 0, 60)) ?>
                                    </p>
                                    
                                    <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                                        <span class="text-lg font-black text-[#0d233f]">
                                            <?= e(precoKz($produto['price'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-5 pb-5 pt-0">
                                <a href="cliente/produto.php?id=<?= (int) $produto['id'] ?>" class="w-full block text-center bg-[#0d233f] hover:bg-[#163154] text-white text-xs font-bold py-2.5 uppercase tracking-wider transition">
                                    Ver Detalhes
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12 bg-white border border-gray-100 reveal">
                        <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">Nenhum produto disponível no momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Projects Section (Slider) -->
    <section class="py-24 bg-[#0d233f] relative overflow-hidden" id="projetos">
        <div class="watermark watermark-dark">PROJETOS</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="text-4xl font-black text-white uppercase tracking-tight mb-12 reveal">Soluções e Setores</h2>

            <div class="relative reveal">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center project-content-fade" id="project-display">
                    <div class="lg:col-span-4 space-y-8">
                        <div class="space-y-6">
                            <div>
                                <span id="project-sector" class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest block mb-2"></span>
                                <h3 id="project-title" class="text-2xl font-bold text-white mb-3"></h3>
                                <p id="project-description" class="text-sm text-gray-300 leading-relaxed mb-6"></p>
                                <a href="cliente/index.php" class="inline-block bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-6 py-3 transition">
                                    Ver Produtos no Marketplace
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-8">
                        <img id="project-image" src="" class="w-full h-[400px] object-cover" alt="Projeto">
                    </div>
                </div>

                <div class="flex gap-2 mt-8 justify-start">
                    <button id="prev-project" class="border border-gray-600 text-gray-400 hover:text-white hover:border-[#f96b21] px-4 py-2 text-xs font-bold uppercase transition flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Anterior
                    </button>
                    <button id="next-project" class="bg-[#f96b21] hover:bg-[#e05812] text-white px-4 py-2 text-xs font-bold uppercase transition flex items-center gap-2">
                        Próximo <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Marcas e fabricantes de referência: faixa contínua -->
    <section class="brand-marquee bg-[#0d233f] py-8 overflow-hidden" aria-label="Marcas de referência no mercado">
        <div class="max-w-site mx-auto px-6 mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <span class="text-[10px] font-extrabold tracking-[.22em] uppercase text-[#f96b21]">Universo de fornecimento</span>
                <h2 class="text-white text-lg md:text-xl font-extrabold mt-1">Marcas e fabricantes de referência</h2>
            </div>
            <p class="text-white/55 text-[11px]">Disponibilidade e fornecimento sob consulta.</p>
        </div>
        <div class="brand-marquee-viewport" tabindex="0" aria-label="Faixa de marcas em movimento">
            <div class="brand-marquee-track">
                <div class="brand-marquee-group">
                    <span class="brand-name">BOSCH</span>
                    <span class="brand-name">Makita</span>
                    <span class="brand-name">CATERPILLAR</span>
                    <span class="brand-name">SIEMENS</span>
                    <span class="brand-name">3M</span>
                    <span class="brand-name">Schneider Electric</span>
                    <span class="brand-name">SAMSUNG</span>
                    <span class="brand-name">LG</span>
                    <span class="brand-name">HONEYWELL</span>
                    <span class="brand-name">Kärcher</span>
                    <span class="brand-name">Milwaukee</span>
                    <span class="brand-name">STANLEY</span>
                </div>
                <div class="brand-marquee-group" aria-hidden="true">
                    <span class="brand-name">BOSCH</span>
                    <span class="brand-name">Makita</span>
                    <span class="brand-name">CATERPILLAR</span>
                    <span class="brand-name">SIEMENS</span>
                    <span class="brand-name">3M</span>
                    <span class="brand-name">Schneider Electric</span>
                    <span class="brand-name">SAMSUNG</span>
                    <span class="brand-name">LG</span>
                    <span class="brand-name">HONEYWELL</span>
                    <span class="brand-name">Kärcher</span>
                    <span class="brand-name">Milwaukee</span>
                    <span class="brand-name">STANLEY</span>
                </div>
            </div>
        </div>
        <p class="max-w-site mx-auto px-6 mt-4 text-[10px] text-white/40">Marcas apresentadas como referências de fabricantes do mercado; não representam necessariamente parcerias ou autorizações comerciais.</p>
    </section>

        <!-- Featured Solutions -->
    <section class="py-24 relative overflow-hidden bg-white" id="solucoes">
        <div class="watermark">DESTAQUES</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="section-title mb-16 reveal">Soluções</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 relative group reveal overflow-hidden h-[400px]">
                    <img src="https://images.unsplash.com/photo-1581092334651-ddf26d9a09d0?q=80&w=1200&auto=format&fit=crop" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Equipamentos">
                    <div class="absolute inset-0 bg-[#0d233f]/80 flex flex-col justify-end p-8 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <h3 class="text-2xl font-bold text-white mb-2">Equipamentos Especializados</h3>
                        <p class="text-gray-300 text-sm">As melhores marcas para o seu negócio.</p>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-100 p-8 flex flex-col justify-center items-center text-center reveal h-[400px]" style="transition-delay: 100ms;">
                    <i class="fa-solid fa-fire-burner text-[#f96b21] text-4xl mb-4"></i>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Indústria e Restauração</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-2">Fogões e Equipamentos</h3>
                    <p class="text-xs text-gray-500 mb-4">Soluções completas para cozinhas industriais e restauração.</p>
                    <a href="cliente/index.php" class="text-xs font-bold text-[#f96b21] border-b border-[#f96b21] pb-0.5 hover:text-[#e05812] transition">Ver Produtos</a>
                </div>

                <div class="relative group reveal h-[250px]" style="transition-delay: 200ms;">
                    <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover" alt="Obra">
                </div>

                <div class="bg-white border border-gray-100 p-8 flex flex-col justify-center items-center text-center reveal h-[250px]" style="transition-delay: 300ms;">
                    <i class="fa-solid fa-boxes-stacked text-[#f96b21] text-4xl mb-4"></i>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Materiais</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-2">Materiais e Insumos</h3>
                    <p class="text-xs text-gray-500 mb-4">Tudo o que precisa para as suas operações.</p>
                    <a href="cliente/index.php" class="text-xs font-bold text-[#f96b21] border-b border-[#f96b21] pb-0.5 hover:text-[#e05812] transition">Ver Produtos</a>
                </div>

                <div class="relative group reveal h-[250px]" style="transition-delay: 400ms;">
                    <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=900&auto=format&fit=crop" class="w-full h-full object-cover" alt="Estruturas">
                </div>
            </div>
        </div>
    </section>

    <!-- Full Project Management Banner (Orange) -->
    <section class="relative py-24 bg-[#f96b21] overflow-hidden">
        <div class="max-w-site mx-auto px-6 flex flex-col md:flex-row items-center relative z-10">
            <div class="md:w-1/2 mb-10 md:mb-0 reveal">
                <img src="https://images.unsplash.com/photo-1494412519320-aa613dfb7738?q=80&w=1000&auto=format&fit=crop" class="w-full shadow-2xl" alt="Equipa GPM">
            </div>
            <div class="md:w-1/2 md:pl-16 text-white reveal" style="transition-delay: 150ms;">
                <span class="text-xs font-bold tracking-widest uppercase mb-4 block text-white/80">Explore as Funcionalidades</span>
                <h2 class="text-4xl md:text-5xl font-black mb-6 leading-tight">
                    Gestão completa <br>do seu projeto
                </h2>
                <p class="text-white/90 text-sm leading-relaxed mb-8">
                    Do planeamento à entrega. Acompanhamos o processo de cotação, negociação e entrega com foco em eficiência, prazos e otimização de custos.
                </p>
                <a href="cliente/index.php" class="bg-white text-[#f96b21] font-bold text-xs uppercase tracking-wider px-8 py-3 hover:bg-gray-100 transition inline-block shadow-md">
                    Aceder ao Marketplace
                </a>
            </div>
        </div>
    </section>

    <!-- Como trabalhamos -->
    <section class="py-20 bg-white">
        <div class="max-w-site mx-auto px-6">
            <div class="max-w-2xl mb-12 reveal">
                <span class="text-[10px] font-extrabold tracking-[.2em] text-[#f96b21] uppercase">Um processo simples</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mt-3">Da necessidade à entrega.</h2>
                <p class="text-gray-500 text-sm mt-4 leading-relaxed">Cada pedido começa por perceber exactamente o que a sua empresa procura. Depois tratamos da pesquisa, da cotação e do fornecimento.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-0 border-t border-b border-gray-200">
                <div class="py-7 pr-6 border-b lg:border-b-0 lg:border-r border-gray-200 reveal">
                    <span class="text-3xl font-black text-[#f96b21]">01</span>
                    <h3 class="font-bold text-[#0d233f] mt-4 mb-2">Pedido</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Recebemos a lista, as quantidades e os detalhes do que procura.</p>
                </div>
                <div class="py-7 px-0 sm:px-6 border-b lg:border-b-0 lg:border-r border-gray-200 reveal" style="transition-delay: 80ms;">
                    <span class="text-3xl font-black text-[#f96b21]">02</span>
                    <h3 class="font-bold text-[#0d233f] mt-4 mb-2">Pesquisa</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Procuramos opções e fornecedores adequados ao pedido.</p>
                </div>
                <div class="py-7 px-0 sm:px-6 border-b lg:border-b-0 lg:border-r border-gray-200 reveal" style="transition-delay: 160ms;">
                    <span class="text-3xl font-black text-[#f96b21]">03</span>
                    <h3 class="font-bold text-[#0d233f] mt-4 mb-2">Cotação</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Apresentamos as condições para análise e aprovação.</p>
                </div>
                <div class="py-7 pl-0 sm:pl-6 reveal" style="transition-delay: 240ms;">
                    <span class="text-3xl font-black text-[#f96b21]">04</span>
                    <h3 class="font-bold text-[#0d233f] mt-4 mb-2">Fornecimento</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Acompanhamos o fornecimento até à entrega acordada.</p>
                </div>
            </div>
        </div>
    </section>

        <!-- Pedidos empresariais -->
    <section class="py-20 bg-[#f8f9fa]">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 lg:grid-cols-[.9fr_1.1fr] gap-10 items-center">
            <div class="reveal">
                <span class="text-[10px] font-extrabold tracking-[.2em] text-[#f96b21] uppercase">Atendimento a empresas</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mt-3 mb-5">Tem uma lista de compras?</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Envie-nos os produtos, as quantidades e, se possível, as especificações. A equipa poderá analisar o pedido e preparar uma proposta de fornecimento.</p>
                <a href="#contactos" class="inline-flex items-center gap-3 mt-7 bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-7 py-4 transition">
                    Pedir uma cotação <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 reveal">
                <div class="bg-white p-7 border border-gray-200">
                    <i class="fa-solid fa-clipboard-list text-2xl text-[#f96b21] mb-5"></i>
                    <h3 class="font-bold text-[#0d233f] mb-2">Pedidos pontuais</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Um equipamento, uma ferramenta ou materiais para uma necessidade específica.</p>
                </div>
                <div class="bg-[#0d233f] p-7 text-white sm:translate-y-8">
                    <i class="fa-solid fa-boxes-stacked text-2xl text-[#f96b21] mb-5"></i>
                    <h3 class="font-bold mb-2">Fornecimento regular</h3>
                    <p class="text-sm text-gray-300 leading-relaxed">Materiais e produtos para apoiar as compras e operações da sua empresa.</p>
                </div>
            </div>
        </div>
    </section>

        <!-- Setores e produtos -->
    <section class="py-24 relative overflow-hidden bg-white">
        <div class="watermark">SECTORES</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 mb-12 reveal">
                <div>
                    <span class="text-[10px] font-extrabold tracking-[.2em] text-[#f96b21] uppercase">Áreas de fornecimento</span>
                    <h2 class="section-title mt-3">Diferentes necessidades.<br>Um só contacto.</h2>
                </div>
                <p class="text-sm text-gray-500 max-w-sm leading-relaxed">A GPM trabalha com pedidos de vários sectores. Se não encontrar o artigo no catálogo, pode solicitar uma cotação.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <article class="sector-card reveal">
                    <div class="overflow-hidden h-56"><img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?q=80&w=700&auto=format&fit=crop" class="w-full h-full object-cover" loading="lazy" alt="Materiais para construção"></div>
                    <div class="pt-5 flex items-center justify-between gap-3"><h3 class="font-bold text-[#0d233f]">Construção</h3><span class="text-[#f96b21]">01</span></div>
                    <p class="text-xs text-gray-500 mt-2">Materiais, ferramentas e consumíveis para obras.</p>
                </article>
                <article class="sector-card reveal" style="transition-delay: 80ms;">
                    <div class="overflow-hidden h-56"><img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?q=80&w=700&auto=format&fit=crop" class="w-full h-full object-cover" loading="lazy" alt="Equipamentos industriais"></div>
                    <div class="pt-5 flex items-center justify-between gap-3"><h3 class="font-bold text-[#0d233f]">Indústria</h3><span class="text-[#f96b21]">02</span></div>
                    <p class="text-xs text-gray-500 mt-2">Equipamentos e insumos para operações profissionais.</p>
                </article>
                <article class="sector-card reveal" style="transition-delay: 160ms;">
                    <div class="overflow-hidden h-56"><img src="https://images.unsplash.com/photo-1556911220-bff31c812dba?q=80&w=700&auto=format&fit=crop" class="w-full h-full object-cover" loading="lazy" alt="Cozinha profissional"></div>
                    <div class="pt-5 flex items-center justify-between gap-3"><h3 class="font-bold text-[#0d233f]">Restauração</h3><span class="text-[#f96b21]">03</span></div>
                    <p class="text-xs text-gray-500 mt-2">Fogões industriais e equipamentos para cozinhas.</p>
                </article>
                <article class="sector-card reveal" style="transition-delay: 240ms;">
                    <div class="overflow-hidden h-56"><img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=700&auto=format&fit=crop" class="w-full h-full object-cover" loading="lazy" alt="Armazenamento e logística"></div>
                    <div class="pt-5 flex items-center justify-between gap-3"><h3 class="font-bold text-[#0d233f]">Logística</h3><span class="text-[#f96b21]">04</span></div>
                    <p class="text-xs text-gray-500 mt-2">Artigos e equipamentos para armazenagem e transporte.</p>
                </article>
            </div>
        </div>
    </section>

        <!-- Diferenciais de atendimento -->
    <section class="py-20 bg-[#0d233f] relative overflow-hidden">
        <div class="absolute -right-24 -top-24 w-80 h-80 rounded-full border border-white/10"></div>
        <div class="absolute -right-10 -top-10 w-52 h-52 rounded-full border border-white/10"></div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <div class="max-w-2xl mb-12 reveal">
                <span class="text-[10px] font-extrabold tracking-[.2em] text-[#f96b21] uppercase">O nosso compromisso</span>
                <h2 class="text-3xl md:text-4xl font-black text-white mt-3">Comprar bem começa com informação clara.</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="border-t border-white/20 pt-6 reveal">
                    <i class="fa-solid fa-magnifying-glass text-[#f96b21] text-2xl mb-5"></i>
                    <h3 class="font-bold text-white mb-2">Pesquisa adequada</h3>
                    <p class="text-sm text-gray-300 leading-relaxed">Procuramos produtos e fornecedores de acordo com as especificações do pedido.</p>
                </div>
                <div class="border-t border-white/20 pt-6 reveal" style="transition-delay: 100ms;">
                    <i class="fa-solid fa-file-invoice-dollar text-[#f96b21] text-2xl mb-5"></i>
                    <h3 class="font-bold text-white mb-2">Condições transparentes</h3>
                    <p class="text-sm text-gray-300 leading-relaxed">A cotação permite avaliar preços, características e condições antes de avançar.</p>
                </div>
                <div class="border-t border-white/20 pt-6 reveal" style="transition-delay: 200ms;">
                    <i class="fa-solid fa-truck-fast text-[#f96b21] text-2xl mb-5"></i>
                    <h3 class="font-bold text-white mb-2">Acompanhamento</h3>
                    <p class="text-sm text-gray-300 leading-relaxed">Mantemos o contacto durante o processo de fornecimento e entrega.</p>
                </div>
            </div>
        </div>
    </section>

        <!-- FAQ Section (Antes do Contacto) -->
    <section class="py-24 bg-white" id="faqs">
        <div class="max-w-site mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] text-center mb-12 reveal">Perguntas Frequentes</h2>
            
            <div class="max-w-3xl mx-auto space-y-0 reveal">
                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Como funciona o processo de procurement?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Nós gerimos todo o ciclo de compras da sua empresa, desde a identificação da necessidade até à negociação com fornecedores e entrega final no seu armazém. Isto garante melhores preços e menos burocracia.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Que tipos de equipamentos e materiais fornecem?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Fornecemos uma vasta gama de produtos. Vai desde materiais de construção civil, insumos industriais, ferramentas, até equipamentos especializados e fogões industriais para o setor alimentar.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Fazem importação de produtos?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Sim. Podemos analisar pedidos de produtos importados e apresentar as opções de fornecimento e as condições disponíveis para cada caso.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Como posso solicitar uma cotação (orçamento)?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Pode preencher o formulário de contacto abaixo, enviar-nos um e-mail ou contactar-nos através do WhatsApp. A nossa equipa analisará o pedido e entrará em contacto para dar seguimento à cotação.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final Info & Form (Contactos e Dados) -->
    <section class="py-24 bg-[#f8f9fa]" id="contactos">
        <div class="max-w-site mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                
                <!-- Formulário (Esquerda) -->
                <div class="reveal">
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mb-2">Fale Connosco</h2>
                    <p class="text-gray-500 text-sm mb-8">Precisa de equipamentos ou serviços para a sua empresa? Preencha o formulário e peça já a sua cotação.</p>

                    <form method="POST" action="index.php#contactos" class="space-y-4">
                        <?php if ($sucessoContacto): ?>
                            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 text-sm" role="alert">
                                Mensagem enviada com sucesso. Obrigado!
                            </div>
                        <?php endif; ?>
                        <?php if ($erroContacto !== ''): ?>
                            <div class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 text-sm" role="alert">
                                <?= e($erroContacto) ?>
                            </div>
                        <?php endif; ?>

                        <?= csrf_field() ?>
                        <input type="hidden" name="formulario" value="contacto">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input id="nome" name="nome" type="text" maxlength="150" required placeholder="Nome *" value="<?= e((string) ($_POST['nome'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                            </div>
                            <div>
                                <input id="email" name="email" type="email" maxlength="190" required placeholder="E-mail *" value="<?= e((string) ($_POST['email'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input id="telefone" name="telefone" type="tel" maxlength="40" placeholder="Telefone / WhatsApp" value="<?= e((string) ($_POST['telefone'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                            <input id="assunto" name="assunto" type="text" maxlength="180" placeholder="Assunto do pedido" value="<?= e((string) ($_POST['assunto'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                        </div>
                        <div>
                            <textarea id="mensagem" name="mensagem" maxlength="5000" required placeholder="Conte-nos o que precisa: produto, quantidade e especificações *" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white min-h-[150px]"><?= e((string) ($_POST['mensagem'] ?? '')) ?></textarea>
                        </div>
                        <button type="submit" class="bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition w-full">
                            Enviar Mensagem
                        </button>
                    </form>
                </div>

                <!-- Dados de Contacto (Direita) -->
                <div class="reveal" style="transition-delay: 150ms;">
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mb-2">Os Nossos Contactos</h2>
                    <p class="text-gray-500 text-sm mb-8">Estamos disponíveis para esclarecer qualquer dúvida sobre os nossos produtos e serviços.</p>

                    <div class="space-y-8">
                        <!-- Morada -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-location-dot text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">Localização</h3>
                                <p class="text-sm text-gray-500"><?= $localizacaoEmpresa !== '' ? e($localizacaoEmpresa) : 'Morada a configurar. Contacte a nossa equipa.' ?></p>
                            </div>
                        </div>

                        <!-- Telefone e Email -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-phone text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">Telefone / WhatsApp</h3>
                                <?php if ($empresa['telefone'] !== ''): ?>
                                    <p class="text-sm text-gray-500 mb-1"><a href="tel:<?= e($empresa['telefone']) ?>"><?= e($empresa['telefone']) ?></a></p>
                                    <?php if ($empresa['whatsapp'] !== ''): ?><p class="text-sm text-gray-500"><a href="https://wa.me/<?= e($empresa['whatsapp']) ?>" target="_blank" rel="noopener">Falar pelo WhatsApp</a></p><?php endif; ?>
                                <?php else: ?><p class="text-sm text-gray-500">Telefone a configurar.</p><?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-envelope text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">E-mail</h3>
                                <?php if ($empresa['email'] !== ''): ?><p class="text-sm text-gray-500"><a href="mailto:<?= e($empresa['email']) ?>"><?= e($empresa['email']) ?></a></p><?php else: ?><p class="text-sm text-gray-500">E-mail a configurar.</p><?php endif; ?>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <p class="text-xs text-gray-500 leading-relaxed">Para pedidos de cotação, indique os produtos, quantidades e especificações no formulário. A equipa entrará em contacto através dos dados que fornecer.</p>
                        </div>

                        </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Google Maps: localização configurável da empresa -->
    <section class="bg-white py-16" id="mapa">
        <div class="max-w-site mx-auto px-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5 mb-7">
                <div>
                    <span class="text-[10px] font-extrabold tracking-[.22em] uppercase text-[#f96b21]">Como nos encontrar</span>
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mt-2">A nossa localização</h2>
                    <p class="text-sm text-gray-500 mt-2 max-w-xl">
                        <?= $localizacaoEmpresa !== '' ? e($localizacaoEmpresa) : 'Angola — a morada exacta da empresa será apresentada aqui assim que for configurada nos dados da empresa.' ?>
                    </p>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($consultaMapaEmpresa) ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center justify-center gap-2 bg-[#0d233f] hover:bg-[#163154] text-white text-xs font-bold uppercase tracking-wider px-6 py-4 transition">
                    <i class="fa-solid fa-map-location-dot"></i> Abrir no Google Maps
                </a>
            </div>
            <div class="h-[320px] md:h-[430px] overflow-hidden rounded-sm border border-gray-200 shadow-lg bg-gray-100">
                <iframe
                    src="<?= e($urlMapaEmpresa) ?>"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                    title="Mapa do Google Maps — <?= e($empresa['nome']) ?>">
                </iframe>
            </div>
        </div>
    </section>

</main>

<!-- Footer -->
<footer class="bg-[#0d233f] text-white py-16 border-t-4 border-[#f96b21]">
    <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="md:col-span-2">
            <a href="index.php" class="flex items-center gap-2 mb-6">
                <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <span class="text-2xl font-black tracking-tighter">GPM.</span>
            </a>
            <p class="text-gray-400 text-sm max-w-sm leading-relaxed">
                Parceiro estratégico no fornecimento de equipamentos, materiais, insumos e serviços para empresas de diversos setores.
            </p>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Navegação</h3>
            <ul class="space-y-3 text-sm text-gray-300">
                <li><a href="#inicio" class="hover:text-white transition">Início</a></li>
                <li><a href="#servicos" class="hover:text-white transition">Serviços</a></li>
                <li><a href="#solucoes" class="hover:text-white transition">Soluções</a></li>
                <li><a href="#faqs" class="hover:text-white transition">FAQs</a></li>
                <li><a href="#contactos" class="hover:text-white transition">Contactos</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Marketplace</h3>
            <ul class="space-y-3 text-sm text-gray-300">
                <li><a href="cliente/index.php" class="hover:text-white transition">Entrar</a></li>
                <li><a href="cliente/index.php" class="hover:text-white transition">Ver Catálogo</a></li>
            </ul>
        </div>
    </div>
</footer>

<!-- Scripts de Animações, Slider e Acordeão -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        const homeMenuButton = document.getElementById('gpm-home-menu-btn');
        const homeMobileMenu = document.getElementById('gpm-home-mobile-nav');

        if (homeMenuButton && homeMobileMenu) {
            homeMenuButton.addEventListener('click', function () {
                const isOpen = homeMenuButton.getAttribute('aria-expanded') === 'true';
                homeMenuButton.setAttribute('aria-expanded', String(!isOpen));
                homeMobileMenu.classList.toggle('hidden', isOpen);
                homeMobileMenu.classList.toggle('flex', !isOpen);
                homeMenuButton.innerHTML = isOpen
                    ? '<i class="fa-solid fa-bars"></i>'
                    : '<i class="fa-solid fa-xmark"></i>';
            });

            homeMobileMenu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    homeMenuButton.setAttribute('aria-expanded', 'false');
                    homeMobileMenu.classList.add('hidden');
                    homeMobileMenu.classList.remove('flex');
                    homeMenuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';
                });
            });
        }

        // 1. Animação de Revelação ao Scroll
        const reveals = document.querySelectorAll(".reveal");
        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("active");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
        reveals.forEach(reveal => revealOnScroll.observe(reveal));

        // 2. Contagem Animada de Números
        const counters = document.querySelectorAll('.counter');
        const speed = 200;
        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const updateCount = () => {
                const current = +counter.innerText;
                const inc = target / speed;
                if (current < target) {
                    counter.innerText = Math.ceil(current + inc);
                    setTimeout(updateCount, 15);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        };

        // 3. Animação dos Gráficos Circulares
        const progressCircles = document.querySelectorAll('.progress-circle');
        const animateCircle = (circle) => {
            const percent = circle.getAttribute('data-percent');
            const circumference = 283;
            const offset = circumference - (percent / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        };

        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('counter')) animateCounter(entry.target);
                    if (entry.target.classList.contains('progress-circle')) animateCircle(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(counter => statsObserver.observe(counter));
        progressCircles.forEach(circle => statsObserver.observe(circle));

        // 4. Lógica do Slider de Projetos
        const projectsData = [
            {
                sector: 'SETOR: LOGÍSTICA E OBRAS',
                title: 'Fornecimento e Equipamentos',
                description: 'Fornecemos equipamentos, materiais e insumos para obras e operações. Gerenciamos o processo de cotação, negociação e entrega com foco em eficiência, prazos e otimização de custos.',
                image: 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=1000&auto=format&fit=crop'
            },
            {
                sector: 'SETOR: INDÚSTRIA E RESTAURAÇÃO',
                title: 'Suprimentos, Insumos e Equipamentos',
                description: 'Atuamos como representante comercial e especialista em procurement, conectando a sua empresa às melhores soluções. Desde insumos industriais até fogões profissionais.',
                image: 'https://images.unsplash.com/photo-1581092334651-ddf26d9a09d0?q=80&w=1200&auto=format&fit=crop'
            },
            {
                sector: 'SETOR: ENGENHARIA E EMPRESAS',
                title: 'Equipamentos Especializados',
                description: 'Oferecemos um atendimento corporativo completo para suprir todas as demandas do seu negócio. Desde a importação de equipamentos até à entrega final.',
                image: 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=1000&auto=format&fit=crop'
            }
        ];

        let currentProject = 0;
        const displayContainer = document.getElementById('project-display');
        const sectorEl = document.getElementById('project-sector');
        const titleEl = document.getElementById('project-title');
        const descEl = document.getElementById('project-description');
        const imgEl = document.getElementById('project-image');
        const prevBtn = document.getElementById('prev-project');
        const nextBtn = document.getElementById('next-project');

        function updateProject(index) {
            displayContainer.style.opacity = 0;
            setTimeout(() => {
                const project = projectsData[index];
                sectorEl.textContent = project.sector;
                titleEl.textContent = project.title;
                descEl.textContent = project.description;
                imgEl.src = project.image;
                displayContainer.style.opacity = 1;
            }, 300);
        }

        if (prevBtn && nextBtn && displayContainer && sectorEl && titleEl && descEl && imgEl) {
            const showNextProject = () => {
                currentProject = (currentProject + 1) % projectsData.length;
                updateProject(currentProject);
            };
            const showPreviousProject = () => {
                currentProject = (currentProject - 1 + projectsData.length) % projectsData.length;
                updateProject(currentProject);
            };

            nextBtn.addEventListener('click', showNextProject);
            prevBtn.addEventListener('click', showPreviousProject);

            // Avanço automático; pausa quando o visitante interage com o slider.
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                let projectTimer = window.setInterval(showNextProject, 6500);
                displayContainer.addEventListener('mouseenter', () => window.clearInterval(projectTimer));
                displayContainer.addEventListener('mouseleave', () => {
                    window.clearInterval(projectTimer);
                    projectTimer = window.setInterval(showNextProject, 6500);
                });
                displayContainer.addEventListener('focusin', () => window.clearInterval(projectTimer));
                displayContainer.addEventListener('focusout', () => {
                    window.clearInterval(projectTimer);
                    projectTimer = window.setInterval(showNextProject, 6500);
                });
            }
        }

        const initialProject = projectsData[0];
        sectorEl.textContent = initialProject.sector;
        titleEl.textContent = initialProject.title;
        descEl.textContent = initialProject.description;
        imgEl.src = initialProject.image;

        // 5. Lógica do Acordeão de FAQs
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.accordion-icon');
                
                // Fecha todos os outros abertos (opcional, para efeito sanfona)
                document.querySelectorAll('.accordion-content').forEach(item => {
                    if (item !== content) {
                        item.style.maxHeight = null;
                        item.classList.remove('open');
                        item.previousElementSibling.querySelector('.accordion-icon').classList.remove('rotate');
                    }
                });

                // Alterna o atual
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                    content.classList.remove('open');
                    icon.classList.remove('rotate');
                } else {
                    content.style.maxHeight = content.scrollHeight + "px";
                    content.classList.add('open');
                    icon.classList.add('rotate');
                }
            });
        });
    });
</script>

</body>
</html>