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
    /*
     * Obter as colunas existentes.
     */
    $colunasCategorias = $pdo
        ->query("SHOW COLUMNS FROM categories")
        ->fetchAll(PDO::FETCH_COLUMN);

    $colunasProdutos = $pdo
        ->query("SHOW COLUMNS FROM products")
        ->fetchAll(PDO::FETCH_COLUMN);

    /*
     * Carregar categorias.
     */
    $sqlCategorias = "SELECT id, name FROM categories";

    if (in_array('active', $colunasCategorias, true)) {
        $sqlCategorias .= " WHERE active = 1";
    }

    $sqlCategorias .= " ORDER BY name";

    $categorias = $pdo
        ->query($sqlCategorias)
        ->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Verificar as colunas obrigatórias do catálogo.
     */
    $obrigatorias = [
        'id',
        'category_id',
        'name',
        'price',
        'active',
    ];

    foreach ($obrigatorias as $coluna) {
        if (!in_array($coluna, $colunasProdutos, true)) {
            throw new RuntimeException(
                "A coluna obrigatória '{$coluna}' não existe na tabela products."
            );
        }
    }

    /*
     * Colunas opcionais: utilizar valores padrão se não existirem.
     */
    $descricaoSQL = in_array('description', $colunasProdutos, true)
        ? 'p.description'
        : "'' AS description";

    $slugSQL = in_array('slug', $colunasProdutos, true)
        ? 'p.slug'
        : "'' AS slug";

    $stockSQL = in_array('stock_quantity', $colunasProdutos, true)
        ? 'p.stock_quantity'
        : '0 AS stock_quantity';

    $unitSQL = in_array('unit', $colunasProdutos, true)
        ? 'p.unit'
        : "'unidade' AS unit";

    $imagemSQL = in_array('main_image', $colunasProdutos, true)
        ? 'p.main_image'
        : 'NULL AS main_image';

    /*
     * Montar a consulta principal.
     */
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

    /*
     * Pesquisa por nome ou descrição.
     */
    if ($pesquisa !== '') {
        $condicoesPesquisa = ['p.name LIKE :pesquisa_nome'];

        $parametros['pesquisa_nome'] = '%' . $pesquisa . '%';

        if (in_array('description', $colunasProdutos, true)) {
            $condicoesPesquisa[] = 'p.description LIKE :pesquisa_descricao';
            $parametros['pesquisa_descricao'] = '%' . $pesquisa . '%';
        }

        $sql .= ' AND (' . implode(' OR ', $condicoesPesquisa) . ')';
    }

    /*
     * Filtrar por categoria.
     */
    if ($categoriaId !== null) {
        $sql .= ' AND p.category_id = :categoria';
        $parametros['categoria'] = $categoriaId;
    }

    /*
     * Ordenação compatível com as colunas disponíveis.
     */
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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Compre materiais de construção na loja online GPM. Consulte produtos e preços.">

    <title>Marketplace | GPM</title>

    <style>
        :root {
            --laranja: #FEA619;
            --escuro: #17212b;
            --texto: #596575;
            --fundo: #f6f7f9;
            --borda: #e4e8ee;
            --branco: #fff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--escuro);
            background: var(--fundo);
            line-height: 1.5;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button, input {
            font: inherit;
        }

        .container {
            width: min(1200px, 94%);
            margin: auto;
        }

        .top {
            background: var(--escuro);
            color: white;
            padding: 9px 0;
            font-size: 13px;
        }

        .top .container {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        header {
            background: white;
            border-bottom: 1px solid var(--borda);
        }

        .nav {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            padding: 12px 0;
        }

        .logo {
            font-size: 29px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--laranja);
        }

        .links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 600;
        }

        .links a:hover {
            color: #a96300;
        }

        .button {
            border: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--laranja);
            color: #171717;
            font-weight: 700;
            padding: 11px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
        }

        .button:hover {
            filter: brightness(.96);
        }

        .hero {
            padding: 42px 0;
            background: linear-gradient(120deg, #1b2835, #334657);
            color: white;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 43px);
            line-height: 1.2;
        }

        .hero p {
            margin: 0 0 24px;
            color: #e0e6ec;
            max-width: 650px;
        }

        .search {
            display: flex;
            gap: 10px;
            max-width: 720px;
        }

        .search input {
            flex: 1;
            min-width: 0;
            padding: 14px;
            border: 1px solid #dce2e9;
            border-radius: 8px;
            background: white;
            color: var(--escuro);
        }

        .layout {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            gap: 25px;
            padding-top: 32px;
            padding-bottom: 60px;
        }

        .sidebar {
            background: white;
            border: 1px solid var(--borda);
            border-radius: 12px;
            padding: 20px;
            align-self: start;
        }

        .sidebar h2 {
            font-size: 18px;
            margin: 0 0 14px;
        }

        .category-list {
            display: grid;
            gap: 5px;
        }

        .category-list a {
            display: block;
            padding: 10px;
            border-radius: 7px;
            color: var(--texto);
            font-size: 14px;
        }

        .category-list a:hover,
        .category-list a.active {
            background: #fff1d9;
            color: #724500;
            font-weight: 700;
        }

        .catalog-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .catalog-heading h2 {
            margin: 0;
            font-size: 26px;
        }

        .catalog-heading p {
            margin: 3px 0 0;
            color: var(--texto);
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: white;
            border: 1px solid var(--borda);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform .2s, box-shadow .2s;
            min-width: 0;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px #17212b12;
        }

        .image {
            height: 190px;
            background: #eef1f4;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .placeholder {
            font-size: 44px;
            color: #a6afb9;
        }

        .details {
            padding: 15px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .category-label {
            color: #916000;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .details h3 {
            font-size: 16px;
            margin: 0 0 8px;
            overflow-wrap: anywhere;
        }

        .description {
            color: var(--texto);
            font-size: 13px;
            margin: 0 0 12px;
        }

        .stock {
            color: #647181;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .price {
            font-size: 18px;
            font-weight: 800;
            margin-top: auto;
            margin-bottom: 13px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-direction: column;
        }

        .button.secondary {
            background: white;
            border: 1px solid var(--borda);
        }

        .button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .alert, .empty {
            padding: 18px;
            background: white;
            border: 1px solid var(--borda);
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .alert {
            color: #9d2822;
            background: #fff0ef;
        }

        .empty {
            color: var(--texto);
            text-align: center;
            grid-column: 1 / -1;
        }

        footer {
            background: #111a22;
            color: white;
            padding: 30px 0;
        }

        footer p {
            color: #c4cbd3;
            font-size: 14px;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        @media (max-width: 1000px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .layout {
                grid-template-columns: 190px minmax(0, 1fr);
                gap: 15px;
            }
        }

        @media (max-width: 650px) {
            .top .container {
                justify-content: center;
                text-align: center;
                flex-wrap: wrap;
            }

            .nav {
                align-items: flex-start;
            }

            .links {
                gap: 12px;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                padding: 15px;
            }

            .category-list {
                display: flex;
                flex-wrap: wrap;
            }

            .category-list a {
                border: 1px solid var(--borda);
            }

            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .image {
                height: 135px;
            }

            .details {
                padding: 11px;
            }

            .details h3 {
                font-size: 14px;
            }

            .price {
                font-size: 15px;
            }

            .search {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="top">
    <div class="container">
        <span>GPM — Marketplace de materiais de construção</span>
        <span>Produtos para os seus projetos</span>
    </div>
</div>

<header>
    <div class="container nav">
        <a class="logo" href="../index.php">GPM<span>.</span></a>

        <nav class="links" aria-label="Menu do marketplace">
            <a href="../index.php">Página inicial</a>
            <a href="index.php">Produtos</a>
            <a href="carrinho.php">Carrinho (<?= (int) $totalCarrinho ?>)</a>

            <?php if ($cliente): ?>
                <a href="../painel-cliente/index.php">Minha conta</a>
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

        <p>
            Consulte o nosso catálogo, compare preços e adicione os produtos
            de que precisa ao carrinho.
        </p>

        <form class="search" action="index.php" method="GET">
            <input
                type="search"
                name="q"
                placeholder="Pesquisar cimento, tinta, ferramentas..."
                aria-label="Pesquisar produtos"
                value="<?= e($pesquisa) ?>"
            >

            <?php if ($categoriaId !== null): ?>
                <input
                    type="hidden"
                    name="categoria"
                    value="<?= (int) $categoriaId ?>"
                >
            <?php endif; ?>

            <button class="button" type="submit">
                Pesquisar produtos
            </button>
        </form>
    </div>
</section>

<main class="container layout">

    <aside class="sidebar">
        <h2>Categorias</h2>

        <div class="category-list">
            <a
                href="index.php<?= $pesquisa !== '' ? '?q=' . urlencode($pesquisa) : '' ?>"
                class="<?= $categoriaId === null ? 'active' : '' ?>"
            >
                Todas as categorias
            </a>

            <?php foreach ($categorias as $categoria): ?>
                <?php
                $urlCategoria = 'index.php?categoria=' . (int) $categoria['id'];

                if ($pesquisa !== '') {
                    $urlCategoria .= '&q=' . urlencode($pesquisa);
                }
                ?>

                <a
                    href="<?= e($urlCategoria) ?>"
                    class="<?= $categoriaId === (int) $categoria['id'] ? 'active' : '' ?>"
                >
                    <?= e($categoria['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section>
        <div class="catalog-heading">
            <div>
                <h2>O nosso catálogo</h2>

                <p>
                    <?= count($produtos) ?> produto(s) encontrado(s)

                    <?php if ($pesquisa !== ''): ?>
                        para “<?= e($pesquisa) ?>”
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($pesquisa !== '' || $categoriaId !== null): ?>
                <a class="button secondary" href="index.php">
                    Limpar filtros
                </a>
            <?php endif; ?>
        </div>

        <?php if ($erro !== ''): ?>
            <div class="alert">
                <?= e($erro) ?>
                <p>
                    Consulte o registo de erros do PHP para identificar
                    a coluna ou consulta que falhou.
                </p>
            </div>
        <?php endif; ?>

        <div class="grid">

            <?php if (!$produtos && $erro === ''): ?>
                <div class="empty">
                    <h3>Nenhum produto encontrado</h3>
                    <p>
                        Tente outra pesquisa ou escolha uma categoria diferente.
                    </p>
                    <a class="button" href="index.php">
                        Ver todos os produtos
                    </a>
                </div>
            <?php endif; ?>

            <?php foreach ($produtos as $produto): ?>
                <?php
                $imagem = imagemProduto($produto['main_image'] ?? null);
                $descricao = trim((string) ($produto['description'] ?? ''));
                $stock = (float) ($produto['stock_quantity'] ?? 0);
                ?>

                <article class="card">
                    <a
                        class="image"
                        href="produto.php?id=<?= (int) $produto['id'] ?>"
                        aria-label="Ver <?= e($produto['name']) ?>"
                    >
                        <?php if ($imagem !== ''): ?>
                            <img
                                src="<?= e($imagem) ?>"
                                alt="<?= e($produto['name']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <span class="placeholder" aria-hidden="true">▧</span>
                        <?php endif; ?>
                    </a>

                    <div class="details">
                        <div class="category-label">
                            <?= e($produto['category_name'] ?? 'Materiais de construção') ?>
                        </div>

                        <h3><?= e($produto['name']) ?></h3>

                        <p class="description">
                            <?= e(function_exists('mb_strimwidth')
                                ? mb_strimwidth($descricao, 0, 80, '…', 'UTF-8')
                                : substr($descricao, 0, 80)) ?>
                        </p>

                        <div class="stock">
                            <?php if ($stock > 0): ?>
                                Disponível · Unidade:
                                <?= e($produto['unit'] ?? 'unidade') ?>
                            <?php else: ?>
                                Consulte a disponibilidade
                            <?php endif; ?>
                        </div>

                        <div class="price">
                            <?= e(kz($produto['price'])) ?>
                        </div>

                        <div class="actions">
                            <a
                                class="button secondary"
                                href="produto.php?id=<?= (int) $produto['id'] ?>"
                            >
                                Ver detalhes
                            </a>

                            <form action="carrinho.php" method="POST">
                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="adicionar"
                                >

                                <input
                                    type="hidden"
                                    name="produto_id"
                                    value="<?= (int) $produto['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="quantidade"
                                    value="1"
                                >

                                <button
                                    class="button"
                                    type="submit"
                                    style="width:100%"
                                    <?= $stock <= 0 ? 'disabled' : '' ?>
                                >
                                    <?= $stock > 0
                                        ? 'Adicionar ao carrinho'
                                        : 'Indisponível' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>

            <?php endforeach; ?>

        </div>
    </section>

</main>

<footer>
    <div class="container footer-row">
        <div>
            <a class="logo" href="../index.php">GPM<span>.</span></a>
            <p>Materiais para construir melhor.</p>
        </div>

        <div>
            <p><a href="../index.php">Sobre a empresa</a></p>
            <p><a href="../index.php#contactos">Contactos</a></p>
            <p><a href="carrinho.php">O meu carrinho</a></p>
        </div>
    </div>

    <div class="container">
        <p>&copy; <?= date('Y') ?> GPM. Todos os direitos reservados.</p>
    </div>
</footer>

</body>
</html>
