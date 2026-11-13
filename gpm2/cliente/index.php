<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['gpm_cart']) || !is_array($_SESSION['gpm_cart'])) {
    $_SESSION['gpm_cart'] = [];
}

/*
|--------------------------------------------------------------------------
| AJAX: adicionar produto ao carrinho sem recarregar a página
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (($_POST['ajax_adicionar'] ?? '') === '1')) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        verify_csrf();

        $produtoId = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
        $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

        $produtoId = $produtoId ?: 0;
        $quantidade = $quantidade ?: 1;

        if ($produtoId < 1 || $quantidade < 1) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'mensagem' => 'Produto ou quantidade inválida.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id, name, stock_quantity, active
             FROM products
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $produtoId]);
        $produtoAjax = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produtoAjax || (int)$produtoAjax['active'] !== 1) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'mensagem' => 'Este produto não está disponível.'
            ]);
            exit;
        }

        $stock = max(0, (int)$produtoAjax['stock_quantity']);

        if ($stock < 1) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'mensagem' => 'Produto sem stock disponível.'
            ]);
            exit;
        }

        $quantidadeAtual = (int)($_SESSION['gpm_cart'][$produtoId] ?? 0);
        $novaQuantidade = min($stock, $quantidadeAtual + $quantidade);

        $_SESSION['gpm_cart'][$produtoId] = $novaQuantidade;

        echo json_encode([
            'ok' => true,
            'mensagem' => 'Produto adicionado ao carrinho.',
            'produto' => (string)$produtoAjax['name'],
            'totalCarrinho' => array_sum(
                array_map('intval', $_SESSION['gpm_cart'])
            ),
            'quantidadeProduto' => $novaQuantidade
        ]);
    } catch (Throwable $erro) {
        error_log('Erro no carrinho GPM: ' . $erro->getMessage());
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'mensagem' => 'Não foi possível adicionar o produto. Tente novamente.'
        ]);
    }

    exit;
}

$pesquisa = trim((string)($_GET['q'] ?? ''));
$categoriaId = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$categoriaId = ($categoriaId && $categoriaId > 0) ? $categoriaId : null;

$categorias = [];
$produtos = [];
$maisVendidos = [];
$novidades = [];
$erroCatalogo = '';

function gpmKz(mixed $valor): string
{
    return number_format((float)$valor, 2, ',', '.') . ' Kz';
}

function gpmImagem(?string $imagem): string
{
    if (!$imagem) {
        return '';
    }

    $imagem = ltrim(str_replace('\\', '/', $imagem), '/');

    if (str_contains($imagem, '..') || preg_match('/^[a-z]+:/i', $imagem)) {
        return '';
    }

    $raiz = dirname(__DIR__);
    $caminhos = [
        $imagem,
        'uploads/produtos/' . basename($imagem),
        'uploads/products/' . basename($imagem),
        'public/uploads/produtos/' . basename($imagem),
        'public/uploads/products/' . basename($imagem),
        'assets/uploads/' . basename($imagem),
        'storage/' . $imagem,
    ];

    foreach ($caminhos as $caminho) {
        if (is_file($raiz . '/' . $caminho)) {
            $segmentos = explode('/', $caminho);
            return '../' . implode('/', array_map('rawurlencode', $segmentos));
        }
    }

    return '';
}

try {
    $stmt = $pdo->query(
        'SELECT id, name FROM categories WHERE active = 1 ORDER BY name'
    );
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT p.id, p.category_id, p.name, p.description, p.price,
               p.stock_quantity, p.unit, p.main_image,
               c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.active = 1
    ";

    $parametros = [];

    if ($pesquisa !== '') {
        // Usar parâmetros distintos evita problemas com prepares PDO nativos.
        $sql .= ' AND (p.name LIKE :pesquisa_nome OR p.description LIKE :pesquisa_descricao)';

        $termoPesquisa = '%' . $pesquisa . '%';
        $parametros['pesquisa_nome'] = $termoPesquisa;
        $parametros['pesquisa_descricao'] = $termoPesquisa;
    }

    if ($categoriaId !== null) {
        $sql .= ' AND p.category_id = :categoria';
        $parametros['categoria'] = $categoriaId;
    }

    $sql .= ' ORDER BY p.featured DESC, p.created_at DESC LIMIT 48';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtMaisVendidos = $pdo->query(
        "SELECT p.id, p.category_id, p.name, p.description, p.price,
                p.stock_quantity, p.unit, p.main_image,
                c.name AS category_name, SUM(oi.quantity) AS unidades_vendidas
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         INNER JOIN products p ON p.id = oi.product_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.active = 1
           AND o.status IN ('confirmed', 'preparing', 'shipped', 'completed')
         GROUP BY p.id, p.category_id, p.name, p.description, p.price,
                  p.stock_quantity, p.unit, p.main_image, c.name
         ORDER BY unidades_vendidas DESC, p.name ASC
         LIMIT 4"
    );
    $maisVendidos = $stmtMaisVendidos->fetchAll(PDO::FETCH_ASSOC);

    $stmtNovidades = $pdo->query(
        "SELECT p.id, p.name, p.description, p.price, p.stock_quantity,
                p.main_image, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.active = 1
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT 4"
    );
    $novidades = $stmtNovidades->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $erro) {
    error_log('Erro no marketplace GPM: ' . $erro->getMessage());
    $erroCatalogo = 'Não foi possível carregar o catálogo. Verifique a base de dados.';
}

$cliente = function_exists('cliente_atual') ? cliente_atual() : null;
$totalCarrinho = array_sum(
    array_map('intval', $_SESSION['gpm_cart'])
);

// Incluir o Header (Menu) unificado do Marketplace
require_once __DIR__ . '/includes/header-marketplace.php';
?>

<style>
.gpm-sales-section{background:#fff;padding:38px 0 42px;border-bottom:1px solid #e3eaf0}
.gpm-sales-inner{max-width:1280px;margin:auto;padding:0 24px}
.gpm-sales-heading{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:22px}
.gpm-sales-kicker{font-size:10px;font-weight:800;letter-spacing:.16em;color:#2F776F;text-transform:uppercase;margin:0 0 8px}
.gpm-sales-heading h2{margin:0;color:#0A2945;font-size:clamp(23px,3vw,32px);font-weight:900;letter-spacing:-.04em}
.gpm-sales-heading p{margin:0;color:#607285;font-size:12px}
.gpm-sales-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.gpm-sales-card{min-width:0;background:#fff;border:1px solid #e3eaf0;display:flex;flex-direction:column;transition:transform .22s ease,box-shadow .22s ease}
.gpm-sales-card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(10,41,69,.10)}
.gpm-sales-card-image{height:175px;background:#fff;border-bottom:1px solid #edf1f4;display:grid;place-items:center;padding:16px}
.gpm-sales-card-image img{width:100%;height:100%;object-fit:contain}
.gpm-sales-card-body{padding:16px;display:flex;flex:1;flex-direction:column}
.gpm-sales-card-category{color:#2F776F;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.gpm-sales-card h3{margin:8px 0;color:#0A2945;font-size:14px;font-weight:800;line-height:1.45}
.gpm-sales-card-price{margin-top:auto;padding-top:10px;border-top:1px solid #edf1f4;color:#0A2945;font-size:17px;font-weight:900}
.gpm-sales-card-link{display:block;margin-top:12px;padding:10px;text-align:center;background:#0A2945;color:#fff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.gpm-sales-card-link:hover{background:#123653}
.product-card{background:#fff!important;border:1px solid #e3eaf0!important;box-shadow:0 2px 8px rgba(10,41,69,.035)}
.product-card [class*="text-[#f96b21]"]{color:#CE9E53!important}
.product-card [class*="group-hover:text-[#f96b21]"]:hover{color:#CE9E53!important}
.product-card [class*="bg-[#f8f9fa]"]{background:#fff!important}
.product-card [class*="text-emerald-600"]{color:#2F776F!important}
.product-card [class*="bg-emerald-50"]{background:#EAF3F1!important}
.product-card [class*="text-red-500"]{color:#607285!important}
.product-card [class*="bg-red-50"]{background:#F1F4F6!important}

@media(max-width:900px){.gpm-sales-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.gpm-sales-inner{padding:0 14px}.gpm-sales-grid{gap:10px}.gpm-sales-card-image{height:135px;padding:10px}.gpm-sales-card-body{padding:11px}.gpm-sales-card h3{font-size:12px}.gpm-sales-card-price{font-size:14px}.gpm-sales-heading{align-items:flex-start;flex-direction:column}}
</style>
<?php if ($maisVendidos): ?>
<section class="gpm-sales-section" aria-labelledby="gpm-sales-title">
 <div class="gpm-sales-inner">
  <div class="gpm-sales-heading">
   <div><p class="gpm-sales-kicker">Escolhas dos clientes</p><h2 id="gpm-sales-title">Mais vendidos</h2></div>
   <p>Ordenados pelas unidades vendidas em pedidos confirmados.</p>
  </div>
  <div class="gpm-sales-grid">
   <?php foreach ($maisVendidos as $produto): $imagemMaisVendido = gpmImagem($produto['main_image'] ?? null); ?>
    <article class="gpm-sales-card">
     <a class="gpm-sales-card-image" href="produto.php?id=<?= (int)$produto['id'] ?>">
      <?php if ($imagemMaisVendido !== ''): ?><img src="<?= e($imagemMaisVendido) ?>" alt="<?= e($produto['name']) ?>" loading="lazy"><?php else: ?><i class="fa-solid fa-box text-4xl" style="color:#B9C9D6"></i><?php endif; ?>
     </a>
     <div class="gpm-sales-card-body">
      <span class="gpm-sales-card-category"><?= e($produto['category_name'] ?? 'GPM Conecta') ?></span>
      <h3><?= e($produto['name']) ?></h3>
      <div class="gpm-sales-card-price"><?= e(gpmKz($produto['price'])) ?></div>
      <a class="gpm-sales-card-link" href="produto.php?id=<?= (int)$produto['id'] ?>">Ver produto ↗</a>
     </div>
    </article>
   <?php endforeach; ?>
  </div>
 </div>
</section>
<?php endif; ?>

<?php if ($novidades): ?>
<section class="gpm-sales-section" aria-labelledby="gpm-new-title">
 <div class="gpm-sales-inner">
  <div class="gpm-sales-heading">
   <div><p class="gpm-sales-kicker">Acabados de chegar</p><h2 id="gpm-new-title">Novidades</h2></div>
   <p>Os produtos adicionados mais recentemente ao catálogo.</p>
  </div>
  <div class="gpm-sales-grid">
   <?php foreach ($novidades as $produtoNovo): $imagemNovo = gpmImagem($produtoNovo['main_image'] ?? null); ?>
    <article class="gpm-sales-card">
     <a class="gpm-sales-card-image" href="produto.php?id=<?= (int)$produtoNovo['id'] ?>">
      <?php if ($imagemNovo !== ''): ?><img src="<?= e($imagemNovo) ?>" alt="<?= e($produtoNovo['name']) ?>" loading="lazy"><?php else: ?><i class="fa-solid fa-box text-4xl" style="color:#B9C9D6"></i><?php endif; ?>
     </a>
     <div class="gpm-sales-card-body">
      <span class="gpm-sales-card-category"><?= e($produtoNovo['category_name'] ?? 'GPM Conecta') ?></span>
      <h3><?= e($produtoNovo['name']) ?></h3>
      <div class="gpm-sales-card-price"><?= e(gpmKz($produtoNovo['price'])) ?></div>
      <a class="gpm-sales-card-link" href="produto.php?id=<?= (int)$produtoNovo['id'] ?>">Ver produto ↗</a>
     </div>
    </article>
   <?php endforeach; ?>
  </div>
 </div>
</section>
<?php endif; ?>

<!-- Hero Section Interna -->
<section class="relative bg-[#0A2945] text-white overflow-hidden py-12 md:py-16">
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
                Encontre materiais, ferramentas e equipamentos de alto rendimento para o seu negócio.
            </p>
        </div>
        
        <!-- Barra de pesquisa -->
        <div class="md:w-1/3 w-full">
             <form action="index.php" method="GET" class="flex bg-white shadow-lg overflow-hidden">
                <input type="search" name="q" placeholder="O que procura?" value="<?= e($pesquisa) ?>" class="flex-1 px-4 py-3 text-[#0A2945] text-sm focus:outline-none">
                <?php if ($categoriaId !== null): ?>
                    <input type="hidden" name="categoria" value="<?= (int)$categoriaId ?>">
                <?php endif; ?>
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
                <h2 class="text-lg font-black text-[#0A2945] mb-4 border-b-2 border-[#f96b21] inline-block pb-1 uppercase tracking-wide">Categorias</h2>
                <ul class="space-y-1 text-sm max-h-[60vh] overflow-y-auto sidebar-scroll pr-2">
                    <li>
                        <a href="index.php<?= $pesquisa !== '' ? '?q=' . urlencode($pesquisa) : '' ?>" 
                           class="block py-2 px-3 transition <?= $categoriaId === null ? 'bg-[#f96b21]/10 text-[#f96b21] font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-[#0A2945]' ?>">
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
                               class="block py-2 px-3 transition <?= $isActive ? 'bg-[#f96b21]/10 text-[#f96b21] font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-[#0A2945]' ?>">
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
                    <h3 class="text-sm font-bold text-[#0A2945] mb-1">Entrega Segura</h3>
                    <p class="text-xs text-gray-500">Distribuição em todo o país com rapidez e segurança.</p>
                </div>
            </div>
        </aside>

        <!-- Product Grid -->
        <div class="flex-1 min-w-0">
            <div class="flex justify-between items-end mb-8 border-b border-gray-200 pb-2 reveal">
                <h2 class="text-xl font-black text-[#0A2945] uppercase">Catálogo completo</h2>
                <span class="text-xs font-bold text-gray-400"><?= count($produtos) ?> resultados</span>
            </div>

            <?php if ($erroCatalogo !== ''): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm reveal" role="alert">
                    <?= e($erroCatalogo) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

                <?php if (!$produtos && $erroCatalogo === ''): ?>
                    <div class="col-span-full text-center py-16 bg-gray-50 border border-gray-100 reveal">
                        <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-lg font-bold text-[#0A2945] mb-2">Nenhum produto encontrado.</h3>
                        <p class="text-gray-500 text-sm mb-6">Tente ajustar os filtros ou pesquisar por outro termo.</p>
                        <a href="index.php" class="inline-block bg-[#0A2945] text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider hover:bg-[#123653] transition">Ver todos os produtos</a>
                    </div>
                <?php endif; ?>

                <?php foreach ($produtos as $produto): ?>
                    <?php
                    $imagem = gpmImagem($produto['main_image'] ?? null);
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
                                <h3 class="text-sm font-bold text-[#0A2945] mb-2 line-clamp-1 group-hover:text-[#f96b21] transition">
                                    <?= e($produto['name']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 mb-4 line-clamp-2 leading-relaxed">
                                    <?= e(function_exists('mb_strimwidth') ? mb_strimwidth($descricao, 0, 60, '…', 'UTF-8') : substr($descricao, 0, 60)) ?>
                                </p>
                                
                                <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                                    <span class="text-lg font-black text-[#0A2945]">
                                        <?= e(gpmKz($produto['price'])) ?>
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
                            <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="flex-1 text-center bg-white border border-gray-200 text-[#0A2945] text-xs font-bold py-2.5 uppercase tracking-wider hover:bg-gray-50 transition">
                                Detalhes
                            </a>
                            
                            <form class="form-adicionar flex-1" method="post" action="index.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ajax_adicionar" value="1">
                                <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
                                <input type="hidden" name="quantidade" value="1">
                                <button type="submit" class="w-full bg-[#0A2945] hover:bg-[#123653] text-white text-xs font-bold py-2.5 uppercase tracking-wider transition flex items-center justify-center gap-2 <?= $stock < 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $stock < 1 ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-cart-plus"></i> <?= $stock > 0 ? 'Add' : 'Esgotado' ?>
                                </button>
                            </form>
                        </div>
                    </article>

                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- Notificação Estilizada (Toast) -->
<div id="aviso-carrinho" class="fixed bottom-5 right-5 z-50 transform translate-y-24 opacity-0 bg-[#0A2945] text-white px-6 py-4 shadow-2xl border-l-4 border-[#f96b21] pointer-events-none transition-all duration-300 flex items-center gap-4 max-w-sm" role="status" aria-live="polite">
    <div class="w-10 h-10 bg-[#f96b21] flex items-center justify-center flex-shrink-0">
        <i class="fa-solid fa-check text-white text-lg"></i>
    </div>
    <div>
        <h5 class="text-sm font-bold uppercase tracking-wider mb-1" id="aviso-titulo">Adicionado</h5>
        <p class="text-xs text-gray-300 leading-tight" id="aviso-texto"></p>
    </div>
</div>

<script>
(function () {
    const aviso = document.getElementById('aviso-carrinho');
    const avisoTitulo = document.getElementById('aviso-titulo');
    const avisoTexto = document.getElementById('aviso-texto');
    let temporizador;

    function mostrarAviso(mensagem, erro = false) {
        if (erro) {
            aviso.classList.remove('border-[#f96b21]');
            aviso.classList.add('border-red-500');
            avisoTitulo.textContent = 'Atenção';
            avisoTitulo.classList.remove('text-[#f96b21]');
            avisoTitulo.classList.add('text-red-500');
            aviso.querySelector('.fa-check').className = 'fa-solid fa-triangle-exclamation text-white text-lg';
        } else {
            aviso.classList.remove('border-red-500');
            aviso.classList.add('border-[#f96b21]');
            avisoTitulo.textContent = 'Adicionado';
            avisoTitulo.classList.remove('text-red-500');
            avisoTitulo.classList.add('text-[#f96b21]');
            aviso.querySelector('i').className = 'fa-solid fa-check text-white text-lg';
        }

        avisoTexto.textContent = mensagem;

        aviso.classList.remove('translate-y-24', 'opacity-0');
        clearTimeout(temporizador);
        temporizador = setTimeout(() => {
            aviso.classList.add('translate-y-24', 'opacity-0');
        }, 3500);
    }

    document.querySelectorAll('.form-adicionar').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const botao = form.querySelector('button[type="submit"]');
            const htmlOriginal = botao.innerHTML;

            botao.disabled = true;
            botao.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const resposta = await fetch('index.php', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const dados = await resposta.json();

                if (!resposta.ok || !dados.ok) {
                    throw new Error(dados.mensagem || 'Não foi possível adicionar.');
                }

                const contadorCarrinho =
                    document.getElementById('contador-carrinho') ||
                    document.querySelector('.gpm-cart-count');

                if (contadorCarrinho) {
                    contadorCarrinho.textContent = dados.totalCarrinho;
                }
                
                // Mensagem estilizada
                mostrarAviso(dados.produto + ' foi adicionado ao carrinho.');
                
                botao.innerHTML = '<i class="fa-solid fa-check"></i>';

                setTimeout(function () {
                    botao.innerHTML = htmlOriginal;
                    botao.disabled = false;
                }, 1400);
            } catch (erro) {
                mostrarAviso(erro.message || 'Erro ao adicionar ao carrinho.', true);
                botao.innerHTML = htmlOriginal;
                botao.disabled = false;
            }
        });
    });

    // Animação de entrada dos cards
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
})();
</script>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>