<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();

$pesquisa = trim((string) ($_GET['q'] ?? ''));

/*
|--------------------------------------------------------------------------
| Localizar a imagem do produto
|--------------------------------------------------------------------------
*/
function imagem_produto_cliente(?string $imagem): string
{
    if (!$imagem) {
        return '';
    }

    $imagem = trim(str_replace('\\', '/', $imagem));

    if (
        $imagem === '' ||
        str_contains($imagem, '..') ||
        str_starts_with($imagem, '/') ||
        preg_match('~^(https?:)?//~i', $imagem) ||
        preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*:~', $imagem)
    ) {
        return '';
    }

    $imagem = ltrim($imagem, '/');

    $candidatos = [
        $imagem,
        'uploads/produtos/' . basename($imagem),
        'uploads/products/' . basename($imagem),
        'public/uploads/produtos/' . basename($imagem),
        'public/uploads/products/' . basename($imagem),
        'assets/uploads/' . basename($imagem),
        'storage/' . $imagem,
    ];

    foreach (array_unique($candidatos) as $caminho) {
        $ficheiro = dirname(__DIR__) . '/' . $caminho;

        if (is_file($ficheiro)) {
            return '../' . implode(
                '/',
                array_map('rawurlencode', explode('/', $caminho))
            );
        }
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| Pesquisar produtos ativos
|--------------------------------------------------------------------------
*/
$sql = "SELECT id, name, description, price, stock_quantity, unit,
               main_image, featured
        FROM products
        WHERE active = 1";

$params = [];

if ($pesquisa !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $pesquisa . '%';
    $params[] = '%' . $pesquisa . '%';
}

$sql .= " ORDER BY featured DESC, name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Adicionar produto ao carrinho
|--------------------------------------------------------------------------
*/
$mensagem = take_flash();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $produtoId = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if (!$produtoId || !$quantidade || $quantidade < 1) {
        $erro = 'Seleciona um produto e uma quantidade válida.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, stock_quantity
             FROM products
             WHERE id = ? AND active = 1
             LIMIT 1"
        );

        $stmt->execute([$produtoId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            $erro = 'Este produto não está disponível.';
        } else {
            $stock = (int) floor((float) $produto['stock_quantity']);

            if ($stock < 1) {
                $erro = 'Este produto não tem stock disponível.';
            } else {
                if (
                    !isset($_SESSION['gpm_cart']) ||
                    !is_array($_SESSION['gpm_cart'])
                ) {
                    $_SESSION['gpm_cart'] = [];
                }

                $quantidadeAtual = (int) (
                    $_SESSION['gpm_cart'][$produtoId] ?? 0
                );

                $novaQuantidade = $quantidadeAtual + $quantidade;

                if ($novaQuantidade > $stock) {
                    $erro = 'Só existem ' . $stock .
                        ' unidades disponíveis. Já tens ' .
                        $quantidadeAtual . ' no carrinho.';
                } else {
                    $_SESSION['gpm_cart'][$produtoId] = $novaQuantidade;

                    flash('success', 'Produto adicionado ao carrinho.');
                    redirect('produtos.php' . (
                        $pesquisa !== ''
                            ? '?q=' . urlencode($pesquisa)
                            : ''
                    ));
                }
            }
        }
    }
}

$carrinhoTotalItens = array_sum(
    array_map(
        'intval',
        $_SESSION['gpm_cart'] ?? []
    )
);
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Produtos | GPM Conecta</title>
    <meta name="description" content="Catálogo de materiais de construção, ferramentas e equipamentos profissionais da GPM Conecta.">
    <meta name="robots" content="<?= $pesquisa !== '' ? 'noindex, follow' : 'index, follow' ?>">

    <!-- Tailwind & Fonts -->
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
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #ffffff; 
            color: #333; 
            overflow-x: hidden; 
        }
        
        /* Watermark */
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

        /* Animação de entrada */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Cards de Produto */
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
    </style>
</head>

<body class="min-h-screen flex flex-col">

<!-- Top Bar -->
<div class="bg-[#0d233f] text-white text-[10px] sm:text-xs py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-4 sm:px-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <span>GPM Conecta — Catálogo</span>
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
            <a href="produtos.php" class="text-[#f96b21]">Produtos</a>
            <a href="#" class="hover:text-[#f96b21] transition">Serviços</a>
            <a href="#" class="hover:text-[#f96b21] transition">Garantia</a>
            <a href="#" class="hover:text-[#f96b21] transition">Sobre Nós</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center space-x-5">
            <?php if (cliente_atual()): ?>
                <div class="hidden lg:flex items-center gap-2 text-xs font-bold text-[#0d233f]">
                    <i class="fa-solid fa-user-circle text-[#f96b21] text-lg"></i>
                    <span>Olá, <?= escapar(cliente_atual()['name']) ?></span>
                </div>
                <a href="pedidos.php" class="hidden md:inline-block text-xs font-bold uppercase tracking-wider text-[#0d233f] hover:text-[#f96b21] transition">
                    Pedidos
                </a>
            <?php else: ?>
                <a href="login.php" class="hidden md:inline-block text-xs font-bold uppercase tracking-wider text-[#0d233f] hover:text-[#f96b21] transition">
                    Entrar
                </a>
                <a href="cadastro.php" class="hidden md:inline-block text-xs font-bold uppercase tracking-wider text-[#f96b21] border-b-2 border-[#f96b21] pb-0.5">
                    Criar Conta
                </a>
            <?php endif; ?>

            <a href="carrinho.php" class="flex items-center gap-2 text-[#0d233f] hover:text-[#f96b21] transition group">
                <i class="fa-solid fa-cart-shopping text-lg"></i>
                <span class="bg-[#f96b21] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <?= (int) $carrinhoTotalItens ?>
                </span>
            </a>
            
            <button class="md:hidden text-[#0d233f] text-xl">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Hero Section -->
<section class="relative bg-[#0d233f] text-white overflow-hidden py-16 md:py-20">
    <div class="max-w-site mx-auto px-4 sm:px-6 relative z-10 flex flex-col md:flex-row items-center justify-between gap-10">
        <div class="md:w-2/3 space-y-5">
            <span class="inline-block bg-white/10 px-3 py-1 text-[10px] font-bold tracking-widest uppercase text-[#f96b21]">
                Catálogo Completo
            </span>
            <h1 class="text-3xl md:text-5xl font-black leading-[1.1] uppercase tracking-tight">
                <?php if ($pesquisa !== ''): ?>
                    Resultados para: <br><span class="text-[#f96b21]">"<?= escapar($pesquisa) ?>"</span>
                <?php else: ?>
                    Materiais de Construção <br>e Ferramentas
                <?php endif; ?>
            </h1>
            <p class="text-gray-400 text-sm max-w-lg leading-relaxed">
                Encontre materiais, ferramentas e equipamentos de alto rendimento para o seu canteiro de obras, com a qualidade e confiança da GPM.
            </p>
        </div>
        
        <!-- Barra de pesquisa -->
        <div class="md:w-1/3 w-full">
             <form class="pesquisa flex bg-white rounded shadow-lg overflow-hidden" method="get" action="produtos.php">
                <input
                    type="search"
                    name="q"
                    placeholder="O que procura?"
                    value="<?= escapar($pesquisa) ?>"
                    class="flex-1 px-4 py-3 text-[#0d233f] text-sm focus:outline-none border-none"
                >
                <button type="submit" class="bg-[#f96b21] hover:bg-[#e05812] text-white px-6 font-bold text-sm transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Mensagens Flash / Erros -->
<?php if ($mensagem || $erro): ?>
    <div class="max-w-site mx-auto px-4 sm:px-6 pt-8 w-full">
        <?php if ($mensagem): ?>
            <div class="aviso bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 text-sm reveal" role="alert">
                <i class="fa-solid fa-circle-check mr-2"></i>
                <?= escapar(
                    is_array($mensagem)
                        ? ($mensagem['message'] ?? '')
                        : $mensagem
                ) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="aviso bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 text-sm reveal" role="alert">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                <?= escapar($erro) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Catálogo -->
<main class="max-w-site mx-auto px-4 sm:px-6 py-16 flex-1 relative w-full">

    <!-- Título + Filtro -->
    <div class="flex justify-between items-end mb-10 border-b border-gray-200 pb-2 reveal">
        <h2 class="text-xl font-black text-[#0d233f] uppercase tracking-wide">
            <?= $pesquisa !== '' ? 'Produtos Encontrados' : 'Top Vendas' ?>
        </h2>
        <span class="text-xs font-bold text-gray-400">
            <?= count($produtos) ?> resultado<?= count($produtos) === 1 ? '' : 's' ?>
        </span>
    </div>

    <!-- Grid de Produtos -->
    <div class="produtos grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        <?php if (!$produtos): ?>
            <div class="col-span-full text-center py-16 bg-gray-50 border border-gray-100 reveal">
                <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-4"></i>
                <h3 class="text-lg font-bold text-[#0d233f] mb-2">Nenhum produto encontrado.</h3>
                <p class="text-gray-500 text-sm mb-6">Tente ajustar os filtros ou pesquisar por outro termo.</p>
                <a href="produtos.php" class="inline-block bg-[#0d233f] text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-[#163154] transition">
                    Ver todos os produtos
                </a>
            </div>
        <?php endif; ?>

        <?php foreach ($produtos as $produto): ?>
            <?php
            $imagem = imagem_produto_cliente(
                $produto['main_image'] !== null
                    ? (string) $produto['main_image']
                    : null
            );

            $stock = (int) floor((float) $produto['stock_quantity']);
            ?>

            <article class="product-card flex flex-col justify-between group reveal">

                <div>
                    <!-- Imagem -->
                    <div class="block relative bg-[#f8f9fa] h-56 overflow-hidden flex items-center justify-center border-b border-gray-100">
                        <?php if ($imagem): ?>
                            <img
                                class="imagem-produto product-img max-h-full max-w-full object-contain p-4 mix-blend-multiply"
                                src="<?= escapar($imagem) ?>"
                                alt="<?= escapar($produto['name']) ?>"
                                loading="lazy"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                            >
                            <div class="sem-imagem absolute inset-0 flex-col items-center justify-center text-gray-300 hidden">
                                <i class="fa-solid fa-box text-5xl"></i>
                                <span class="text-xs mt-2">Imagem indisponível</span>
                            </div>
                        <?php else: ?>
                            <div class="sem-imagem flex flex-col items-center justify-center text-gray-300">
                                <i class="fa-solid fa-box text-5xl"></i>
                                <span class="text-xs mt-2">Imagem indisponível</span>
                            </div>
                        <?php endif; ?>

                        <?php if ((int) $produto['featured'] === 1): ?>
                            <span class="absolute top-3 left-3 bg-[#f96b21] text-white text-[9px] font-black uppercase tracking-widest px-2 py-1">
                                Destaque
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="p-5 text-left">
                        <div class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest mb-2">
                            <?= escapar($produto['unit'] ?? 'GPM') ?>
                        </div>
                        <h3 class="text-sm font-bold text-[#0d233f] mb-2 line-clamp-1 group-hover:text-[#f96b21] transition">
                            <?= escapar($produto['name']) ?>
                        </h3>
                        <p class="descricao text-xs text-gray-500 mb-4 line-clamp-2 leading-relaxed min-h-[40px]">
                            <?= escapar(
                                mb_strimwidth(
                                    (string) ($produto['description'] ?? ''),
                                    0,
                                    100,
                                    '...'
                                )
                            ) ?>
                        </p>
                        
                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                            <span class="preco text-lg font-black text-[#0d233f]">
                                <?= escapar(money($produto['price'])) ?>
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
                <div class="px-5 pb-5 pt-0">
                    <?php if ($stock > 0): ?>
                        <form method="post" action="produtos.php<?= $pesquisa !== '' ? '?q=' . urlencode($pesquisa) : '' ?>" class="flex gap-2">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?= (int) $produto['id'] ?>"
                            >

                            <input
                                type="number"
                                name="quantidade"
                                value="1"
                                min="1"
                                max="<?= $stock ?>"
                                step="1"
                                required
                                aria-label="Quantidade de <?= escapar($produto['name']) ?>"
                                class="w-16 px-2 py-2.5 text-xs font-bold text-center text-[#0d233f] border border-gray-200 focus:border-[#f96b21] focus:ring-1 focus:ring-[#f96b21] rounded-sm bg-white"
                            >

                            <button class="flex-1 bg-[#0d233f] hover:bg-[#163154] text-white text-xs font-bold py-2.5 uppercase tracking-wider transition flex items-center justify-center gap-2 rounded-sm" type="submit">
                                <i class="fa-solid fa-cart-plus"></i>
                                Adicionar
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="w-full bg-gray-200 text-gray-500 text-xs font-bold py-2.5 uppercase tracking-wider cursor-not-allowed rounded-sm" type="button" disabled>
                            <i class="fa-solid fa-ban mr-2"></i>
                            Sem Stock
                        </button>
                    <?php endif; ?>
                </div>

            </article>
        <?php endforeach; ?>

    </div>
</main>

<!-- Footer -->
<footer class="bg-[#0d233f] text-white pt-16 pb-8 border-t-4 border-[#f96b21] mt-auto">
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
                    <li><a href="produtos.php" class="hover:text-white transition">Produtos</a></li>
                    <li><a href="carrinho.php" class="hover:text-white transition">Carrinho</a></li>
                    <?php if (cliente_atual()): ?>
                        <li><a href="pedidos.php" class="hover:text-white transition">Meus Pedidos</a></li>
                    <?php endif; ?>
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
            <p>&copy; <?= date('Y') ?> GPM Conecta. Todos os direitos reservados.</p>
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