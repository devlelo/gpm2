<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$carrinho = $_SESSION['gpm_cart'] ?? [];
$totalCarrinho = array_sum(array_map('intval', $carrinho));

$pesquisa = trim((string) ($_GET['q'] ?? ''));

$categoriaId = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$categoriaId = ($categoriaId && $categoriaId > 0) ? $categoriaId : null;

$categorias = [];
$produtos = [];
$erro = '';

function kz(mixed $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' Kz';
}

function imagemProduto(?string $imagem): string
{
    if (!$imagem) {
        return '';
    }

    $imagem = str_replace('\\', '/', trim($imagem));
    $imagem = ltrim($imagem, '/');

    if (
        str_contains($imagem, '..') ||
        preg_match('/^[a-z]+:/i', $imagem)
    ) {
        return '';
    }

    $raiz = dirname(__DIR__);

    $caminhos = [
        $imagem,
        'uploads/produtos/' . basename($imagem),
        'uploads/products/' . basename($imagem),
        'public/uploads/produtos/' . basename($imagem),
        'public/uploads/products/' . basename($imagem),
    ];

    foreach (array_unique($caminhos) as $caminho) {
        $caminhoCompleto = $raiz . '/' . $caminho;

        if (is_file($caminhoCompleto)) {
            return '../' . str_replace('\\', '/', $caminho);
        }
    }

    return '';
}

try {
    $colunasCategorias = $pdo
        ->query("SHOW COLUMNS FROM categories")
        ->fetchAll(PDO::FETCH_COLUMN);

    $colunasProdutos = $pdo
        ->query("SHOW COLUMNS FROM products")
        ->fetchAll(PDO::FETCH_COLUMN);

    $sqlCategorias = "SELECT id, name FROM categories";

    if (in_array('active', $colunasCategorias, true)) {
        $sqlCategorias .= " WHERE active = 1";
    }

    $sqlCategorias .= " ORDER BY name";

    $categorias = $pdo
        ->query($sqlCategorias)
        ->fetchAll(PDO::FETCH_ASSOC);

    $obrigatorias = ['id', 'category_id', 'name', 'price', 'active'];

    foreach ($obrigatorias as $coluna) {
        if (!in_array($coluna, $colunasProdutos, true)) {
            throw new RuntimeException(
                "A coluna obrigatória '{$coluna}' não existe na tabela products."
            );
        }
    }

    $descricaoSQL = in_array('description', $colunasProdutos, true)
        ? 'p.description' : "'' AS description";

    $slugSQL = in_array('slug', $colunasProdutos, true)
        ? 'p.slug' : "'' AS slug";

    $stockSQL = in_array('stock_quantity', $colunasProdutos, true)
        ? 'p.stock_quantity' : '0 AS stock_quantity';

    $unitSQL = in_array('unit', $colunasProdutos, true)
        ? 'p.unit' : "'unidade' AS unit";

    $imagemSQL = in_array('main_image', $colunasProdutos, true)
        ? 'p.main_image' : 'NULL AS main_image';

    $sql = "
        SELECT
            p.id,
            p.category_id,
            p.name,
            {$slugSQL},
            {$descricaoSQL},
            p.price,
            {$stockSQL},
            {$unitSQL},
            {$imagemSQL},
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.active = 1
    ";

    $parametros = [];

    if ($pesquisa !== '') {
        $condicoesPesquisa = ['p.name LIKE :pesquisa_nome'];
        $parametros['pesquisa_nome'] = '%' . $pesquisa . '%';

        if (in_array('description', $colunasProdutos, true)) {
            $condicoesPesquisa[] = 'p.description LIKE :pesquisa_descricao';
            $parametros['pesquisa_descricao'] = '%' . $pesquisa . '%';
        }

        $sql .= ' AND (' . implode(' OR ', $condicoesPesquisa) . ')';
    }

    if ($categoriaId !== null) {
        $sql .= ' AND p.category_id = :categoria';
        $parametros['categoria'] = $categoriaId;
    }

    $ordenacoes = [];

    if (in_array('featured', $colunasProdutos, true)) {
        $ordenacoes[] = 'p.featured DESC';
    }

    if (in_array('created_at', $colunasProdutos, true)) {
        $ordenacoes[] = 'p.created_at DESC';
    }

    $ordenacoes[] = 'p.name ASC';

    $sql .= ' ORDER BY ' . implode(', ', $ordenacoes);
    $sql .= ' LIMIT 48';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('Erro no marketplace GPM: ' . $e->getMessage());
    $erro = 'Não foi possível carregar o catálogo. Verifique a estrutura da base de dados.';
}

$cliente = cliente_atual();

/* ---------- SEO ---------- */
$seoTitle = 'Marketplace GPM Construção | Materiais, Ferramentas & Equipamentos';
$seoDescription = 'Compre materiais de construção, ferramentas elétricas e manuais, EPI e equipamentos industriais na loja online GPM Construção.';
$seoKeywords = 'materiais de construção, ferramentas elétricas, equipamentos industriais, EPI, cimento, GPM Construção';
$seoCanonical = 'https://www.gpmconstrucao.ao/loja/index.php';
$seoImage = 'https://www.gpmconstrucao.ao/assets/og-gpm.jpg';

if ($pesquisa !== '') {
    $seoTitle = 'Pesquisa: "' . $pesquisa . '" | GPM Construção';
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    
    <!-- SEO Básico -->
    <meta name="description" content="<?= e($seoDescription) ?>">
    <meta name="keywords" content="<?= e($seoKeywords) ?>">
    <meta name="robots" content="<?= $pesquisa !== '' ? 'noindex, follow' : 'index, follow' ?>">
    <link rel="canonical" href="<?= e($seoCanonical) ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($seoTitle) ?>">
    <meta property="og:description" content="<?= e($seoDescription) ?>">
    <meta property="og:image" content="<?= e($seoImage) ?>">
    <meta property="og:url" content="<?= e($seoCanonical) ?>">
    <meta property="og:type" content="website">

    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

    <!-- Tailwind Config (Cores do Layout Anterior) -->
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
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #ffffff; 
            color: #333; 
            overflow-x: hidden; 
        }
        
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

        /* Custom Scrollbar para a sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Cards */
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
    </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- Top Bar -->
<div class="bg-[#0d233f] text-white text-[10px] sm:text-xs py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-4 sm:px-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <span>Marketplace GPM</span>
            <span class="text-gray-500">|</span>
            <span>Angola</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="hover:text-[#f96b21] cursor-pointer transition">Português</span>
            <span class="hover:text-[#f96b21] cursor-pointer transition">AOA (Kz)</span>
        </div>
    </div>
</div>

<!-- Header -->
<header class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-site mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2 cursor-pointer">
            <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-2xl font-black text-[#0d233f] tracking-tighter">GPM.</span>
        </a>

        <!-- Nav -->
        <nav class="hidden md:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider text-[#0d233f]">
            <a href="#" class="text-[#f96b21]">Produtos</a>
            <a href="#" class="hover:text-[#f96b21] transition">Serviços</a>
            <a href="#" class="hover:text-[#f96b21] transition">Garantia</a>
            <a href="#" class="hover:text-[#f96b21] transition">Sobre Nós</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center space-x-5">
            <form action="index.php" method="GET" class="relative hidden lg:block">
                <input type="search" name="q" placeholder="Pesquisar..." value="<?= e($pesquisa) ?>" class="w-48 xl:w-64 pl-4 pr-10 py-2 text-sm bg-gray-50 border border-gray-200 rounded-full focus:outline-none focus:border-[#f96b21] focus:ring-1 focus:ring-[#f96b21] transition">
                <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-[#f96b21]">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
            
            <a href="carrinho.php" class="flex items-center gap-2 text-[#0d233f] hover:text-[#f96b21] transition group">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span class="bg-[#f96b21] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <?= (int) $totalCarrinho ?>
                </span>
            </a>
            
            <button class="md:hidden text-[#0d233f] text-xl">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Hero Section (Estilo Banner Menor) -->
<section class="relative bg-[#0d233f] text-white overflow-hidden py-12 md:py-16">
    <div class="max-w-site mx-auto px-4 sm:px-6 relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="md:w-2/3 space-y-4">
            <span class="inline-block bg-white/10 px-3 py-1 text-[10px] font-bold tracking-widest uppercase text-[#f96b21]">
                Catálogo Completo
            </span>
            <h1 class="text-3xl md:text-5xl font-black leading-[1.1] uppercase tracking-tight">
                <?php if ($pesquisa !== ''): ?>
                    Resultados para: <span class="text-[#f96b21]">"<?= e($pesquisa) ?>"</span>
                <?php else: ?>
                    Explore as Nossas <br>Soluções
                <?php endif; ?>
            </h1>
            <p class="text-gray-400 text-sm max-w-lg leading-relaxed">
                Encontre materiais, ferramentas e equipamentos de alto rendimento para o seu canteiro de obras.
            </p>
        </div>
        
        <!-- Barra de pesquisa no Hero (Mobile) -->
        <div class="md:w-1/3 w-full">
             <form action="index.php" method="GET" class="flex bg-white rounded shadow-lg overflow-hidden">
                <input type="search" name="q" placeholder="O que procura?" value="<?= e($pesquisa) ?>" class="flex-1 px-4 py-3 text-[#0d233f] text-sm focus:outline-none">
                <button type="submit" class="bg-[#f96b21] hover:bg-[#e05812] text-white px-6 font-bold text-sm transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Main Content -->
<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative">
    <div class="flex flex-col lg:flex-row gap-10">

        <!-- Sidebar (Sticky no Desktop) -->
        <aside class="w-full lg:w-64 flex-shrink-0 space-y-8 lg:sticky lg:top-28 h-fit reveal">
            <!-- Categories -->
            <div>
                <h2 class="text-lg font-black text-[#0d233f] mb-4 border-b-2 border-[#f96b21] inline-block pb-1 uppercase tracking-wide">Categorias</h2>
                <ul class="space-y-1 text-sm max-h-[60vh] overflow-y-auto sidebar-scroll pr-2">
                    <li>
                        <a href="index.php<?= $pesquisa !== '' ? '?q=' . urlencode($pesquisa) : '' ?>" 
                           class="block py-2 px-3 rounded transition <?= $categoriaId === null ? 'bg-[#f96b21]/10 text-[#f96b21] font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-[#0d233f]' ?>">
                            Todas as Categorias
                        </a>
                    </li>
                    <?php foreach ($categorias as $categoria): ?>
                        <?php
                        $urlCategoria = 'index.php?categoria=' . (int) $categoria['id'];
                        if ($pesquisa !== '') {
                            $urlCategoria .= '&q=' . urlencode($pesquisa);
                        }
                        $isActive = $categoriaId === (int) $categoria['id'];
                        ?>
                        <li>
                            <a href="<?= e($urlCategoria) ?>" 
                               class="block py-2 px-3 rounded transition <?= $isActive ? 'bg-[#f96b21]/10 text-[#f96b21] font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-[#0d233f]' ?>">
                                <?= e($categoria['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Promo Banner -->
            <div class="bg-[#f8f9fa] p-6 border border-gray-100 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-gray-200 opacity-50 group-hover:scale-110 transition duration-500">
                    <i class="fa-solid fa-truck-fast text-6xl"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-sm font-bold text-[#0d233f] mb-1">Entrega Segura</h3>
                    <p class="text-xs text-gray-500">Distribuição em todo o país com rapidez e segurança.</p>
                </div>
            </div>
        </aside>

        <!-- Product Grid -->
        <div class="flex-1 min-w-0">
            <div class="flex justify-between items-end mb-8 border-b border-gray-200 pb-2 reveal">
                <h2 class="text-xl font-black text-[#0d233f] uppercase">Top Vendas</h2>
                <span class="text-xs font-bold text-gray-400"><?= count($produtos) ?> resultados</span>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm reveal" role="alert">
                    <?= e($erro) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

                <?php if (!$produtos && $erro === ''): ?>
                    <div class="col-span-full text-center py-16 bg-gray-50 border border-gray-100 reveal">
                        <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-lg font-bold text-[#0d233f] mb-2">Nenhum produto encontrado.</h3>
                        <p class="text-gray-500 text-sm mb-6">Tente ajustar os filtros ou pesquisar por outro termo.</p>
                        <a href="index.php" class="inline-block bg-[#0d233f] text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-[#163154] transition">Ver todos os produtos</a>
                    </div>
                <?php endif; ?>

                <?php foreach ($produtos as $produto): ?>
                    <?php
                    $imagem = imagemProduto($produto['main_image'] ?? null);
                    $descricao = trim((string) ($produto['description'] ?? ''));
                    $stock = (float) ($produto['stock_quantity'] ?? 0);
                    ?>

                    <article class="product-card flex flex-col justify-between group reveal">
                        <div>
                            <!-- Imagem -->
                            <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="block relative bg-[#f8f9fa] h-56 overflow-hidden flex items-center justify-center border-b border-gray-100">
                                <?php if ($imagem !== ''): ?>
                                    <img src="<?= e($imagem) ?>" alt="<?= e($produto['name']) ?>" loading="lazy" class="product-img max-h-full max-w-full object-contain p-4 mix-blend-multiply">
                                <?php else: ?>
                                    <i class="fa-solid fa-box text-gray-300 text-5xl"></i>
                                <?php endif; ?>
                            </a>

                            <!-- Info -->
                            <div class="p-5 text-left">
                                <div class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest mb-2">
                                    <?= e($produto['category_name'] ?? 'GPM') ?>
                                </div>
                                <h3 class="text-sm font-bold text-[#0d233f] mb-2 line-clamp-1 group-hover:text-[#f96b21] transition">
                                    <?= e($produto['name']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mb-4 line-clamp-2 leading-relaxed">
                                    <?= e(function_exists('mb_strimwidth') ? mb_strimwidth($descricao, 0, 60, '…', 'UTF-8') : substr($descricao, 0, 60)) ?>
                                </p>
                                
                                <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                                    <span class="text-lg font-black text-[#0d233f]">
                                        <?= e(kz($produto['price'])) ?>
                                    </span>
                                    <?php if ($stock > 0): ?>
                                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 uppercase tracking-wide">
                                            Em Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 uppercase tracking-wide">
                                            Esgotado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="px-5 pb-5 pt-0 flex gap-2">
                            <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="flex-1 text-center bg-white border border-gray-200 text-[#0d233f] text-xs font-bold py-2.5 uppercase tracking-wider hover:bg-gray-50 transition">
                                Detalhes
                            </a>
                            
                            <form action="carrinho.php" method="POST" class="flex-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="acao" value="adicionar">
                                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                                <input type="hidden" name="quantidade" value="1">
                                <button type="submit" class="w-full bg-[#0d233f] hover:bg-[#163154] text-white text-xs font-bold py-2.5 uppercase tracking-wider transition flex items-center justify-center gap-2 <?= $stock <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-cart-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    </article>

                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="bg-[#0d233f] text-white pt-16 pb-8 border-t-4 border-[#f96b21]">
    <div class="max-w-site mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
            
            <!-- Logo & Desc -->
            <div class="md:col-span-2">
                <a href="index.php" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter">GPM.</span>
                </a>
                <p class="text-gray-400 text-sm max-w-sm leading-relaxed mb-6">
                    Parceiro estratégico no fornecimento de equipamentos, materiais, insumos e serviços para empresas do setor de obras, engenharia e construção civil.
                </p>
                <div class="flex space-x-4 text-lg text-gray-400">
                    <span class="hover:text-white cursor-pointer transition"><i class="fa-brands fa-facebook-f"></i></span>
                    <span class="hover:text-white cursor-pointer transition"><i class="fa-brands fa-instagram"></i></span>
                    <span class="hover:text-white cursor-pointer transition"><i class="fa-brands fa-linkedin-in"></i></span>
                </div>
            </div>

            <!-- Links Rápidos -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Navegação</h3>
                <ul class="space-y-3 text-sm text-gray-300">
                    <li><a href="index.php" class="hover:text-white transition">Início</a></li>
                    <li><a href="index.php" class="hover:text-white transition">Produtos</a></li>
                    <li><a href="carrinho.php" class="hover:text-white transition">Carrinho</a></li>
                    <li><a href="#" class="hover:text-white transition">Contactos</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Newsletter</h3>
                <p class="text-sm text-gray-400 mb-4">Receba atualizações sobre novos produtos e ofertas.</p>
                <form class="flex bg-[#163154] rounded overflow-hidden" onsubmit="event.preventDefault();">
                    <input type="email" placeholder="O seu e-mail" class="flex-1 bg-transparent px-4 py-3 text-white text-sm focus:outline-none placeholder-gray-500" required>
                    <button type="submit" class="bg-[#f96b21] hover:bg-[#e05812] text-white px-4 transition">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
            <p>&copy; <?= date('Y') ?> GPM Construção. Todos os direitos reservados.</p>
            <p>Desenvolvido com foco em performance e B2B.</p>
        </div>
    </div>
</footer>

<!-- Scripts de Animação -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const reveals = document.querySelectorAll(".reveal");

        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("active");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        });

        reveals.forEach(reveal => {
            revealOnScroll.observe(reveal);
        });
    });
</script>

</body>
</html>