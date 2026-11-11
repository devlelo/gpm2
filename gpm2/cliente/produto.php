<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = db();

if (!isset($_SESSION['gpm_cart']) || !is_array($_SESSION['gpm_cart'])) {
    $_SESSION['gpm_cart'] = [];
}

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function gpmKzDetalhe(mixed $valor): string
{
    return number_format((float)$valor, 2, ',', '.') . ' Kz';
}

function gpmImagemDetalhe(?string $imagem): string
{
    if (!$imagem) {
        return '';
    }

    $imagem = ltrim(str_replace('\\', '/', $imagem), '/');

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
        'assets/uploads/' . basename($imagem),
        'storage/' . $imagem,
    ];

    foreach ($caminhos as $caminho) {
        if (is_file($raiz . '/' . $caminho)) {
            $segmentos = explode('/', $caminho);

            return '../' . implode(
                '/',
                array_map('rawurlencode', $segmentos)
            );
        }
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

$produtoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$produtoId || $produtoId < 1) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| AJAX: adicionar ao carrinho
| Funciona no produto principal e nos produtos relacionados.
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (($_POST['ajax_adicionar'] ?? '') === '1')
) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        verify_csrf();

        $idAdicionar = filter_input(
            INPUT_POST,
            'produto_id',
            FILTER_VALIDATE_INT
        );

        $quantidade = filter_input(
            INPUT_POST,
            'quantidade',
            FILTER_VALIDATE_INT
        );

        $idAdicionar = $idAdicionar ?: 0;
        $quantidade = $quantidade ?: 1;

        if ($idAdicionar < 1 || $quantidade < 1) {
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

        $stmt->execute(['id' => $idAdicionar]);

        $produtoAjax = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$produtoAjax ||
            (int)$produtoAjax['active'] !== 1
        ) {
            http_response_code(404);

            echo json_encode([
                'ok' => false,
                'mensagem' => 'Este produto não está disponível.'
            ]);

            exit;
        }

        $stock = max(
            0,
            (int)$produtoAjax['stock_quantity']
        );

        if ($stock < 1) {
            http_response_code(422);

            echo json_encode([
                'ok' => false,
                'mensagem' => 'Produto sem stock disponível.'
            ]);

            exit;
        }

        $quantidadeAtual = (int)(
            $_SESSION['gpm_cart'][$idAdicionar] ?? 0
        );

        $novaQuantidade = min(
            $stock,
            $quantidadeAtual + $quantidade
        );

        $_SESSION['gpm_cart'][$idAdicionar] = $novaQuantidade;

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
        error_log(
            'Erro ao adicionar produto ao carrinho: ' .
            $erro->getMessage()
        );

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'mensagem' => 'Não foi possível adicionar o produto. Tente novamente.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| Carregar produto principal
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->prepare(
        "SELECT
            p.*,
            c.name AS category_name
         FROM products p
         LEFT JOIN categories c
            ON c.id = p.category_id
         WHERE p.id = :id
           AND p.active = 1
         LIMIT 1"
    );

    $stmt->execute(['id' => $produtoId]);

    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        header('Location: index.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Imagens adicionais do produto
    |--------------------------------------------------------------------------
    */

    $stmtImagens = $pdo->prepare(
        "SELECT image_path, sort_order
         FROM product_images
         WHERE product_id = :produto_id
         ORDER BY sort_order ASC, id ASC"
    );

    $stmtImagens->execute([
        'produto_id' => $produtoId
    ]);

    $imagensAdicionais = $stmtImagens->fetchAll(
        PDO::FETCH_ASSOC
    );

    /*
    |--------------------------------------------------------------------------
    | Produtos da mesma categoria
    |--------------------------------------------------------------------------
    */

    $produtosRelacionados = [];

    if (!empty($produto['category_id'])) {
        $stmtRelacionados = $pdo->prepare(
            "SELECT
                id,
                category_id,
                name,
                description,
                price,
                stock_quantity,
                unit,
                main_image
             FROM products
             WHERE category_id = :categoria_id
               AND id != :produto_id
               AND active = 1
             ORDER BY id DESC
             LIMIT 8"
        );

        $stmtRelacionados->execute([
            'categoria_id' => $produto['category_id'],
            'produto_id' => $produtoId
        ]);

        $produtosRelacionados = $stmtRelacionados->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

} catch (Throwable $erro) {
    error_log(
        'Erro na página do produto GPM: ' . $erro->getMessage()
    );

    http_response_code(500);

    exit(
        'Não foi possível carregar este produto. Verifique a base de dados.'
    );
}

/*
|--------------------------------------------------------------------------
| Preparar imagens da galeria
|--------------------------------------------------------------------------
*/

$galeria = [];

$imagemPrincipal = gpmImagemDetalhe(
    $produto['main_image'] ?? null
);

if ($imagemPrincipal !== '') {
    $galeria[] = [
        'imagem' => $imagemPrincipal,
        'alt' => $produto['name']
    ];
}

foreach ($imagensAdicionais as $imagemExtra) {
    $caminho = gpmImagemDetalhe(
        $imagemExtra['image_path'] ?? null
    );

    if ($caminho !== '') {
        $galeria[] = [
            'imagem' => $caminho,
            'alt' => $produto['name']
        ];
    }
}

$stockPrincipal = (int)($produto['stock_quantity'] ?? 0);

$totalCarrinho = array_sum(
    array_map('intval', $_SESSION['gpm_cart'])
);

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header-marketplace.php';
?>

<!-- Produto principal -->
<main class="max-w-site mx-auto px-4 sm:px-6 py-12">

    <div class="mb-8">
        <a href="index.php"
           class="text-sm text-gray-500 hover:text-[#f96b21]">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Voltar ao catálogo
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14">

        <!-- Galeria -->
        <div>

            <div class="bg-[#f8f9fa] border border-gray-100 h-[350px] md:h-[480px] flex items-center justify-center overflow-hidden">

                <?php if (!empty($galeria)): ?>

                    <img
                        id="imagem-principal"
                        src="<?= e($galeria[0]['imagem']) ?>"
                        alt="<?= e($galeria[0]['alt']) ?>"
                        class="max-h-full max-w-full object-contain p-6"
                    >

                <?php else: ?>

                    <i class="fa-solid fa-box text-gray-300 text-7xl"></i>

                <?php endif; ?>

            </div>

            <?php if (count($galeria) > 1): ?>

                <div class="flex flex-wrap gap-3 mt-4">

                    <?php foreach ($galeria as $indice => $imagem): ?>

                        <button
                            type="button"
                            onclick="mudarImagem(<?= $indice ?>)"
                            class="w-20 h-20 border border-gray-200 bg-gray-50 hover:border-[#f96b21] p-2"
                        >
                            <img
                                src="<?= e($imagem['imagem']) ?>"
                                alt="<?= e($imagem['alt']) ?>"
                                class="w-full h-full object-contain"
                            >
                        </button>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <!-- Informações -->
        <div>

            <span class="text-xs font-bold text-[#f96b21] uppercase tracking-widest">
                <?= e($produto['category_name'] ?? 'Materiais de Construção') ?>
            </span>

            <h1 class="text-3xl md:text-4xl font-black text-[#0d233f] mt-3 mb-5">
                <?= e($produto['name']) ?>
            </h1>

            <div class="text-3xl font-black text-[#0d233f] mb-6">
                <?= e(gpmKzDetalhe($produto['price'])) ?>
            </div>

            <div class="border-y border-gray-200 py-5 space-y-3 mb-6">

                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Disponibilidade</span>

                    <?php if ($stockPrincipal > 0): ?>

                        <span class="font-bold text-emerald-600">
                            Em stock
                        </span>

                    <?php else: ?>

                        <span class="font-bold text-red-500">
                            Esgotado
                        </span>

                    <?php endif; ?>
                </div>

                <?php if (!empty($produto['unit'])): ?>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Unidade</span>

                        <span class="font-bold text-[#0d233f]">
                            <?= e($produto['unit']) ?>
                        </span>
                    </div>

                <?php endif; ?>

                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Quantidade disponível</span>

                    <span class="font-bold text-[#0d233f]">
                        <?= $stockPrincipal ?>
                    </span>
                </div>

            </div>

            <h2 class="font-bold text-[#0d233f] mb-3">
                Descrição do produto
            </h2>

            <div class="text-sm text-gray-600 leading-7 mb-8 whitespace-pre-line">
                <?= e($produto['description'] ?: 'Sem descrição disponível.') ?>
            </div>

            <!-- Adicionar produto principal -->
            <?php if ($stockPrincipal > 0): ?>

                <form
                    class="form-adicionar flex flex-col sm:flex-row gap-3"
                    method="post"
                    action="produto.php?id=<?= (int)$produto['id'] ?>"
                >

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="ajax_adicionar"
                        value="1"
                    >

                    <input
                        type="hidden"
                        name="produto_id"
                        value="<?= (int)$produto['id'] ?>"
                    >

                    <div class="flex items-center border border-gray-300 w-fit">

                        <button
                            type="button"
                            onclick="alterarQuantidade(-1)"
                            class="px-4 py-3 text-[#0d233f] hover:bg-gray-100"
                        >
                            -
                        </button>

                        <input
                            id="quantidade-principal"
                            type="number"
                            name="quantidade"
                            value="1"
                            min="1"
                            max="<?= $stockPrincipal ?>"
                            class="w-16 text-center border-x border-gray-300 py-3 outline-none"
                        >

                        <button
                            type="button"
                            onclick="alterarQuantidade(1)"
                            class="px-4 py-3 text-[#0d233f] hover:bg-gray-100"
                        >
                            +
                        </button>

                    </div>

                    <button
                        type="submit"
                        class="flex-1 bg-[#0d233f] hover:bg-[#163154] text-white font-bold py-3 px-6 uppercase text-sm transition"
                    >
                        <i class="fa-solid fa-cart-plus mr-2"></i>
                        Adicionar ao carrinho
                    </button>

                </form>

            <?php else: ?>

                <button
                    type="button"
                    disabled
                    class="w-full bg-gray-300 text-gray-600 font-bold py-3 uppercase text-sm cursor-not-allowed"
                >
                    Produto esgotado
                </button>

            <?php endif; ?>

        </div>

    </div>

    <!-- Produtos relacionados -->
    <?php if (!empty($produtosRelacionados)): ?>

        <section class="mt-20">

            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-8 border-b border-gray-200 pb-4">

                <div>
                    <span class="text-xs font-bold text-[#f96b21] uppercase tracking-widest">
                        Continue a explorar
                    </span>

                    <h2 class="text-2xl md:text-3xl font-black text-[#0d233f] mt-2 uppercase">
                        Produtos da mesma categoria
                    </h2>
                </div>

                <span class="text-xs text-gray-400 font-bold">
                    <?= count($produtosRelacionados) ?> produtos
                </span>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <?php foreach ($produtosRelacionados as $relacionado): ?>

                    <?php
                    $imagemRelacionado = gpmImagemDetalhe(
                        $relacionado['main_image'] ?? null
                    );

                    $stockRelacionado = (int)(
                        $relacionado['stock_quantity'] ?? 0
                    );
                    ?>

                    <article class="flex flex-col justify-between bg-white border border-gray-100 hover:shadow-lg transition group">

                        <div>

                            <!-- Imagem do produto relacionado -->
                            <a
                                href="produto.php?id=<?= (int)$relacionado['id'] ?>"
                                class="block relative bg-[#f8f9fa] h-52 overflow-hidden flex items-center justify-center"
                            >

                                <?php if ($imagemRelacionado !== ''): ?>

                                    <img
                                        src="<?= e($imagemRelacionado) ?>"
                                        alt="<?= e($relacionado['name']) ?>"
                                        loading="lazy"
                                        class="max-h-full max-w-full object-contain p-4 group-hover:scale-105 transition duration-300"
                                    >

                                <?php else: ?>

                                    <i class="fa-solid fa-box text-gray-300 text-5xl"></i>

                                <?php endif; ?>

                            </a>

                            <!-- Informações -->
                            <div class="p-4">

                                <h3 class="text-sm font-bold text-[#0d233f] mb-2 line-clamp-2">
                                    <?= e($relacionado['name']) ?>
                                </h3>

                                <p class="text-lg font-black text-[#0d233f] mb-3">
                                    <?= e(gpmKzDetalhe($relacionado['price'])) ?>
                                </p>

                                <?php if ($stockRelacionado > 0): ?>

                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 uppercase">
                                        Em stock
                                    </span>

                                <?php else: ?>

                                    <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 uppercase">
                                        Esgotado
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- Botões dos produtos relacionados -->
                        <div class="p-4 pt-0 flex flex-col gap-2">

                            <a
                                href="produto.php?id=<?= (int)$relacionado['id'] ?>"
                                class="block text-center border border-gray-200 text-[#0d233f] font-bold text-xs py-2.5 uppercase hover:bg-gray-50 transition"
                            >
                                Ver mais informação
                            </a>

                            <?php if ($stockRelacionado > 0): ?>

                                <form
                                    class="form-adicionar"
                                    method="post"
                                    action="produto.php?id=<?= (int)$produto['id'] ?>"
                                >

                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="ajax_adicionar"
                                        value="1"
                                    >

                                    <input
                                        type="hidden"
                                        name="produto_id"
                                        value="<?= (int)$relacionado['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="quantidade"
                                        value="1"
                                    >

                                    <button
                                        type="submit"
                                        class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs py-2.5 uppercase transition"
                                    >
                                        <i class="fa-solid fa-cart-plus mr-2"></i>
                                        Add ao carrinho
                                    </button>

                                </form>

                            <?php else: ?>

                                <button
                                    type="button"
                                    disabled
                                    class="w-full bg-gray-300 text-gray-600 font-bold text-xs py-2.5 uppercase cursor-not-allowed"
                                >
                                    Esgotado
                                </button>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

</main>

<!-- Notificação do carrinho -->
<div
    id="aviso-carrinho"
    class="fixed bottom-5 right-5 z-50 transform translate-y-24 opacity-0 bg-[#0d233f] text-white px-6 py-4 shadow-2xl border-l-4 border-[#f96b21] pointer-events-none transition-all duration-300 flex items-center gap-4 max-w-sm"
    role="status"
    aria-live="polite"
>

    <div class="w-10 h-10 bg-[#f96b21] flex items-center justify-center flex-shrink-0">
        <i id="aviso-icone" class="fa-solid fa-check text-white text-lg"></i>
    </div>

    <div>
        <h5
            id="aviso-titulo"
            class="text-sm font-bold uppercase tracking-wider mb-1 text-[#f96b21]"
        >
            Adicionado
        </h5>

        <p
            id="aviso-texto"
            class="text-xs text-gray-300 leading-tight"
        ></p>
    </div>

</div>

<script>
/*
|--------------------------------------------------------------------------
| Galeria de imagens
|--------------------------------------------------------------------------
*/

const imagensGaleria = <?= json_encode(
    array_column($galeria, 'imagem'),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) ?>;

function mudarImagem(indice) {
    const imagem = document.getElementById('imagem-principal');

    if (imagem && imagensGaleria[indice]) {
        imagem.src = imagensGaleria[indice];
    }
}

/*
|--------------------------------------------------------------------------
| Quantidade do produto principal
|--------------------------------------------------------------------------
*/

function alterarQuantidade(valor) {
    const campo = document.getElementById('quantidade-principal');

    if (!campo) {
        return;
    }

    const minimo = parseInt(campo.min) || 1;
    const maximo = parseInt(campo.max) || 1;
    let quantidade = parseInt(campo.value) || 1;

    quantidade += valor;

    quantidade = Math.max(minimo, Math.min(maximo, quantidade));

    campo.value = quantidade;
}

/*
|--------------------------------------------------------------------------
| Notificações
|--------------------------------------------------------------------------
*/

(function () {

    const aviso = document.getElementById('aviso-carrinho');
    const avisoTitulo = document.getElementById('aviso-titulo');
    const avisoTexto = document.getElementById('aviso-texto');
    const avisoIcone = document.getElementById('aviso-icone');

    let temporizador;

    function mostrarAviso(mensagem, erro = false) {

        avisoTitulo.textContent = erro ? 'Atenção' : 'Adicionado';
        avisoTexto.textContent = mensagem;

        aviso.classList.toggle('border-red-500', erro);
        aviso.classList.toggle('border-[#f96b21]', !erro);

        avisoTitulo.classList.toggle('text-red-500', erro);
        avisoTitulo.classList.toggle('text-[#f96b21]', !erro);

        avisoIcone.className = erro
            ? 'fa-solid fa-triangle-exclamation text-white text-lg'
            : 'fa-solid fa-check text-white text-lg';

        aviso.classList.remove('translate-y-24', 'opacity-0');

        clearTimeout(temporizador);

        temporizador = setTimeout(function () {
            aviso.classList.add('translate-y-24', 'opacity-0');
        }, 3500);
    }

    /*
    |--------------------------------------------------------------------------
    | Atualizar contador do carrinho
    |--------------------------------------------------------------------------
    */

    function atualizarContador(total) {

        const contador =
            document.getElementById('contador-carrinho') ||
            document.querySelector('.gpm-cart-count');

        if (contador) {
            contador.textContent = total;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Todos os botões Add: produto principal e relacionados
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.form-adicionar').forEach(function (form) {

        form.addEventListener('submit', async function (event) {

            event.preventDefault();

            const botao = form.querySelector('button[type="submit"]');

            if (!botao || botao.disabled) {
                return;
            }

            const htmlOriginal = botao.innerHTML;

            botao.disabled = true;

            botao.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> A adicionar';

            try {

                const resposta = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const dados = await resposta.json();

                if (!resposta.ok || !dados.ok) {
                    throw new Error(
                        dados.mensagem || 'Não foi possível adicionar.'
                    );
                }

                atualizarContador(dados.totalCarrinho);

                mostrarAviso(
                    dados.produto + ' foi adicionado ao carrinho.'
                );

                botao.innerHTML =
                    '<i class="fa-solid fa-check"></i> Adicionado';

                setTimeout(function () {
                    botao.innerHTML = htmlOriginal;
                    botao.disabled = false;
                }, 1400);

            } catch (erro) {

                mostrarAviso(
                    erro.message || 'Erro ao adicionar ao carrinho.',
                    true
                );

                botao.innerHTML = htmlOriginal;
                botao.disabled = false;
            }

        });

    });

})();
</script>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
