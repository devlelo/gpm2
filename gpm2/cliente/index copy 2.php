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

require_once __DIR__ . '/includes/header-marketplace.php';
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
        $sql .= ' AND (p.name LIKE :pesquisa OR p.description LIKE :pesquisa)';
        $parametros['pesquisa'] = '%' . $pesquisa . '%';
    }

    if ($categoriaId !== null) {
        $sql .= ' AND p.category_id = :categoria';
        $parametros['categoria'] = $categoriaId;
    }

    $sql .= ' ORDER BY p.featured DESC, p.created_at DESC LIMIT 48';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $erro) {
    error_log('Erro no marketplace GPM: ' . $erro->getMessage());
    $erroCatalogo = 'Não foi possível carregar o catálogo. Verifique a base de dados.';
}

$cliente = function_exists('cliente_atual') ? cliente_atual() : null;
$totalCarrinho = array_sum(
    array_map('intval', $_SESSION['gpm_cart'])
);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Marketplace GPM — materiais de construção.">
<title>Marketplace | GPM — Materiais de Construção</title>
<style>
:root{--laranja:#FEA619;--azul:#0d233f;--texto:#596575;--fundo:#f5f7fa;--borda:#e3e8ef;--branco:#fff}
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;color:var(--azul);background:var(--fundo)}
a{color:inherit;text-decoration:none}
.container{width:min(1180px,92%);margin:auto}
.top{background:var(--azul);color:#fff;font-size:12px;padding:10px 0}
.top .container{display:flex;justify-content:space-between;gap:12px}
header{background:#fff;border-bottom:1px solid var(--borda)}
.nav{min-height:75px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.logo{font-size:28px;font-weight:900;letter-spacing:-1px}
.logo span{color:var(--laranja)}
.links{display:flex;gap:18px;align-items:center;flex-wrap:wrap;font-size:14px;font-weight:bold}
.links a:hover{color:#9a6200}
.hero{background:linear-gradient(120deg,#0d233f,#193b60);color:white;padding:42px 0}
.hero h1{font-size:clamp(27px,4vw,42px);margin:0 0 12px}
.hero p{color:#dce5f0;line-height:1.6;margin:0 0 22px}
.search{display:flex;gap:10px;max-width:720px}
.search input{flex:1;min-width:0;padding:14px;border:0;border-radius:7px;font-size:15px}
.button{border:0;border-radius:7px;background:var(--laranja);color:#17212b;font-weight:800;padding:12px 14px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-align:center;font-size:13px}
.button:hover{filter:brightness(.96)}
.button:disabled{opacity:.5;cursor:not-allowed}
.button.secondary{background:#fff;border:1px solid var(--borda);color:var(--azul)}
.layout{display:grid;grid-template-columns:220px minmax(0,1fr);gap:26px;padding:32px 0 55px}
.sidebar,.card{background:white;border:1px solid var(--borda);border-radius:10px;overflow:hidden}
.sidebar{padding:19px;height:max-content}
.sidebar h2{font-size:16px;margin:0 0 14px}
.category-list{display:grid;gap:5px}
.category-list a{padding:10px;border-radius:6px;font-size:13px;color:var(--texto)}
.category-list a:hover,.category-list a.active{background:#fff2d9;color:#805000;font-weight:bold}
.heading{display:flex;justify-content:space-between;align-items:center;gap:15px;margin:0 0 20px}
.heading h2{margin:0 0 6px;font-size:23px}
.heading p{margin:0;color:#6b7280;font-size:13px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}
.card{display:flex;flex-direction:column;min-width:0;transition:transform .15s,box-shadow .15s}
.card:hover{transform:translateY(-2px);box-shadow:0 8px 22px #0d233f12}
.card-image{height:185px;background:#f1f4f7;display:flex;align-items:center;justify-content:center;overflow:hidden}
.card-image img{width:100%;height:100%;object-fit:contain;padding:12px}
.no-image{color:#aeb8c5;font-size:45px}
.card-body{padding:15px;display:flex;flex-direction:column;flex:1}
.category{font-size:10px;font-weight:800;text-transform:uppercase;color:#956000;letter-spacing:.7px;margin-bottom:8px}
.card h3{font-size:16px;line-height:1.4;margin:0 0 8px}
.description{font-size:12px;line-height:1.5;color:#697586;min-height:36px;margin:0 0 10px}
.stock{font-size:11px;color:#667085;margin-bottom:12px}
.price{font-size:20px;font-weight:900;margin-top:auto;margin-bottom:14px}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.actions .button{width:100%;min-height:42px;padding:9px 6px}
.empty,.alert{background:#fff;border:1px solid var(--borda);border-radius:9px;padding:24px;color:var(--texto)}
.alert{color:#8b2c20;margin-bottom:18px}
.notice{position:fixed;right:20px;bottom:20px;z-index:1000;background:var(--azul);color:white;padding:14px 18px;border-radius:8px;box-shadow:0 8px 30px #0002;max-width:90vw;display:none}
.notice.show{display:block}
.notice.error{background:#a83232}
footer{background:var(--azul);color:white;padding:28px 0;font-size:13px}
footer .footer-row{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
footer p{color:#cbd5e1}
@media(max-width:950px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.layout{grid-template-columns:190px minmax(0,1fr)}}
@media(max-width:650px){.top .container{flex-direction:column}.nav{align-items:flex-start;flex-direction:column;padding:15px 0}.links{gap:12px}.hero{padding:32px 0}.search{flex-direction:column}.layout{grid-template-columns:1fr;gap:18px}.category-list{grid-template-columns:repeat(2,minmax(0,1fr))}.grid{gap:10px}.card-image{height:140px}.card-body{padding:11px}.card h3{font-size:14px}.price{font-size:17px}.actions{grid-template-columns:1fr}.heading{align-items:flex-start;flex-direction:column}}
@media(max-width:390px){.grid{grid-template-columns:1fr}.card-image{height:190px}}
</style>
</head>
<body>
<div class="top"><div class="container"><span>GPM — Marketplace de materiais de construção</span><span>Materiais para os seus projetos</span></div></div>

<header>
    <div class="container nav">
        <a class="logo" href="../index.php">GPM<span>.</span></a>
        <nav class="links" aria-label="Menu principal">
            <a href="../index.php">Página inicial</a>
            <a href="index.php">Produtos</a>
            <a href="carrinho.php">Carrinho (<span id="contador-carrinho"><?= (int)$totalCarrinho ?></span>)</a>
            <?php if ($cliente): ?>
                <a href="../cliente/painel-cliente/index.php">Minha conta</a>
            <?php else: ?>
                <a href="login.php">Entrar</a>
                <a href="cadastro.php">Criar conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="container">
        <h1>Encontre os materiais para o seu projeto</h1>
        <p>Consulte os produtos, veja as informações e adicione ao carrinho facilmente.</p>
        <form class="search" action="index.php" method="GET">
            <input type="search" name="q" placeholder="Pesquisar cimento, tinta, ferramentas..." value="<?= e($pesquisa) ?>" aria-label="Pesquisar produtos">
            <?php if ($categoriaId !== null): ?>
                <input type="hidden" name="categoria" value="<?= (int)$categoriaId ?>">
            <?php endif; ?>
            <button class="button" type="submit">Pesquisar produtos</button>
        </form>
    </div>
</section>

<main class="container layout">
    <aside class="sidebar">
        <h2>Categorias</h2>
        <div class="category-list">
            <a href="index.php<?= $pesquisa !== '' ? '?q=' . urlencode($pesquisa) : '' ?>"
               class="<?= $categoriaId === null ? 'active' : '' ?>">Todas as categorias</a>
            <?php foreach ($categorias as $categoria): ?>
                <?php
                $url = 'index.php?categoria=' . (int)$categoria['id'];
                if ($pesquisa !== '') {
                    $url .= '&q=' . urlencode($pesquisa);
                }
                ?>
                <a href="<?= e($url) ?>"
                   class="<?= $categoriaId === (int)$categoria['id'] ? 'active' : '' ?>">
                    <?= e($categoria['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section>
        <div class="heading">
            <div>
                <h2>O nosso catálogo</h2>
                <p><?= count($produtos) ?> produto(s) encontrado(s)</p>
            </div>
            <?php if ($pesquisa !== '' || $categoriaId !== null): ?>
                <a class="button secondary" href="index.php">Limpar filtros</a>
            <?php endif; ?>
        </div>

        <?php if ($erroCatalogo !== ''): ?>
            <div class="alert"><?= e($erroCatalogo) ?></div>
        <?php endif; ?>

        <div class="grid">
            <?php if (!$produtos && $erroCatalogo === ''): ?>
                <div class="empty">
                    <h3>Nenhum produto encontrado</h3>
                    <p>Tente outra pesquisa ou categoria.</p>
                    <a class="button" href="index.php">Ver todos os produtos</a>
                </div>
            <?php endif; ?>

            <?php foreach ($produtos as $produto): ?>
                <?php
                $imagem = gpmImagem($produto['main_image'] ?? null);
                $stock = (int)($produto['stock_quantity'] ?? 0);
                $descricao = trim((string)($produto['description'] ?? ''));
                ?>
                <article class="card">
                    <a class="card-image" href="produto.php?id=<?= (int)$produto['id'] ?>"
                       aria-label="Ver mais informações de <?= e($produto['name']) ?>">
                        <?php if ($imagem !== ''): ?>
                            <img src="<?= e($imagem) ?>" alt="<?= e($produto['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="no-image">▧</span>
                        <?php endif; ?>
                    </a>

                    <div class="card-body">
                        <div class="category"><?= e($produto['category_name'] ?? 'Materiais de construção') ?></div>
                        <h3><?= e($produto['name']) ?></h3>
                        <p class="description"><?= e(mb_strimwidth($descricao, 0, 82, '…')) ?></p>
                        <div class="stock">
                            <?= $stock > 0
                                ? 'Em stock: ' . $stock . ' ' . e($produto['unit'] ?? 'unidade')
                                : 'Sem stock disponível' ?>
                        </div>
                        <div class="price"><?= e(gpmKz($produto['price'])) ?></div>

                        <div class="actions">
                            <a class="button secondary" href="produto.php?id=<?= (int)$produto['id'] ?>">
                                Ver mais informações
                            </a>

                            <form class="form-adicionar" method="post" action="index.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="ajax_adicionar" value="1">
                                <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
                                <input type="hidden" name="quantidade" value="1">
                                <button class="button" type="submit" <?= $stock < 1 ? 'disabled' : '' ?>>
                                    <?= $stock > 0 ? 'Comprar' : 'Indisponível' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<div class="notice" id="aviso-carrinho" role="status" aria-live="polite"></div>

<footer>
    <div class="container footer-row">
        <div><a class="logo" href="../index.php">GPM<span>.</span></a><p>Materiais para construir melhor.</p></div>
        <div><a href="../index.php#contactos">Contactos</a><p>&copy; <?= date('Y') ?> GPM. Todos os direitos reservados.</p></div>
    </div>
</footer>

<script>
(function () {
    const aviso = document.getElementById('aviso-carrinho');
    let temporizador;

    function mostrarAviso(mensagem, erro = false) {
        aviso.textContent = mensagem;
        aviso.classList.toggle('error', erro);
        aviso.classList.add('show');
        clearTimeout(temporizador);
        temporizador = setTimeout(() => aviso.classList.remove('show'), 3200);
    }

    document.querySelectorAll('.form-adicionar').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const botao = form.querySelector('button[type="submit"]');
            const textoOriginal = botao.textContent;

            botao.disabled = true;
            botao.textContent = 'A adicionar...';

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

                document.getElementById('contador-carrinho').textContent = dados.totalCarrinho;
                mostrarAviso(dados.produto + ' foi adicionado ao carrinho.');
                botao.textContent = 'Adicionado ✓';

                setTimeout(function () {
                    botao.textContent = textoOriginal;
                    botao.disabled = false;
                }, 1400);
            } catch (erro) {
                mostrarAviso(erro.message || 'Erro ao adicionar ao carrinho.', true);
                botao.textContent = textoOriginal;
                botao.disabled = false;
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>
