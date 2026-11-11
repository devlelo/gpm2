<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();

if (!isset($_SESSION['gpm_cart']) || !is_array($_SESSION['gpm_cart'])) {
    $_SESSION['gpm_cart'] = [];
}

$erro = '';

/*
|--------------------------------------------------------------------------
| Processar ações do carrinho
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $acao = $_POST['acao'] ?? '';

    /*
    | Atualizar quantidade
    */
    if ($acao === 'atualizar_quantidade') {
        $id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
        $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

        if ($id && isset($_SESSION['gpm_cart'][$id])) {
            $stmt = $pdo->prepare(
                "SELECT stock_quantity
                 FROM products
                 WHERE id = ? AND active = 1"
            );

            $stmt->execute([$id]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$produto || (float) $produto['stock_quantity'] < 1) {
                unset($_SESSION['gpm_cart'][$id]);
            } elseif (
                $quantidade !== false &&
                $quantidade !== null &&
                $quantidade >= 1
            ) {
                $stock = (int) floor((float) $produto['stock_quantity']);

                $_SESSION['gpm_cart'][$id] = min($quantidade, $stock);
            }
        }

        redirect('carrinho.php');
    }

    /*
    | Remover produto
    */
    if ($acao === 'remover') {
        $id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

        if ($id) {
            unset($_SESSION['gpm_cart'][$id]);
        }

        redirect('carrinho.php');
    }
}

/*
|--------------------------------------------------------------------------
| Carregar produtos e validar stock
|--------------------------------------------------------------------------
*/

$itens = [];
$total = 0.0;

if (!empty($_SESSION['gpm_cart'])) {
    $ids = array_values(
        array_filter(
            array_map('intval', array_keys($_SESSION['gpm_cart'])),
            fn($id) => $id > 0
        )
    );

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare(
            "SELECT id, name, price, stock_quantity, unit
             FROM products
             WHERE active = 1
               AND id IN ($placeholders)
             ORDER BY name"
        );

        $stmt->execute($ids);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $produto) {
            $id = (int) $produto['id'];
            $stock = (int) floor((float) $produto['stock_quantity']);

            if ($stock < 1) {
                unset($_SESSION['gpm_cart'][$id]);
                continue;
            }

            $quantidade = (int) ($_SESSION['gpm_cart'][$id] ?? 1);
            $quantidade = max(1, min($quantidade, $stock));

            $_SESSION['gpm_cart'][$id] = $quantidade;

            $produto['quantity'] = $quantidade;
            $produto['stock_limit'] = $stock;
            $produto['subtotal'] = $quantidade * (float) $produto['price'];

            $itens[] = $produto;
            $total += $produto['subtotal'];
        }
    }
}

/*
|--------------------------------------------------------------------------
| Formatar valores
|--------------------------------------------------------------------------
*/

function formatar_valor_carrinho(float|int|string $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' Kz';
}

// Incluir o Header unificado
require_once __DIR__ . '/includes/header-marketplace.php';
?>

<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 w-full relative">

    <!-- Breadcrumb / Voltar -->
    <div class="mb-8 reveal">
        <a href="index.php" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#f96b21] transition">
            <i class="fa-solid fa-arrow-left"></i> Continuar a comprar
        </a>
    </div>

    <h1 class="text-3xl md:text-4xl font-black text-[#0d233f] uppercase tracking-tight mb-8 reveal">O Meu Carrinho</h1>

    <?php if (!$itens): ?>

        <!-- Carrinho Vazio -->
        <section class="bg-[#f8f9fa] border border-gray-100 p-10 text-center reveal">
            <i class="fa-solid fa-cart-shopping text-gray-300 text-6xl mb-6"></i>
            <h2 class="text-xl font-bold text-[#0d233f] mb-2">O seu carrinho está vazio.</h2>
            <p class="text-gray-500 mb-8">Explora o nosso marketplace e adiciona os materiais de que precisa.</p>
            <a href="index.php" class="inline-block bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-8 py-3 transition shadow-md">
                Ver Produtos
            </a>
        </section>

    <?php else: ?>

        <!-- Tabela do Carrinho -->
        <section class="bg-white border border-gray-100 shadow-sm reveal">
            
            <!-- Cabeçalho (Desktop) -->
            <div class="hidden md:grid grid-cols-12 gap-4 border-b border-gray-100 p-5 text-xs font-bold uppercase tracking-wider text-gray-400">
                <div class="col-span-5">Produto</div>
                <div class="col-span-2">Preço</div>
                <div class="col-span-2">Quantidade</div>
                <div class="col-span-2 text-right">Subtotal</div>
                <div class="col-span-1 text-right">Ação</div>
            </div>

            <!-- Itens -->
            <?php foreach ($itens as $item): ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center border-b border-gray-100 p-5 item-carrinho" data-preco="<?= escapar((string) $item['price']) ?>">
                    
                    <!-- Nome -->
                    <div class="col-span-5">
                        <span class="block font-bold text-[#0d233f] text-base mb-1"><?= escapar($item['name']) ?></span>
                        <small class="block text-gray-500 text-xs uppercase tracking-wider"><?= escapar((string) ($item['unit'] ?? 'Unidade')) ?></small>
                    </div>

                    <!-- Preço -->
                    <div class="col-span-2 text-sm font-bold text-gray-600">
                        <?= escapar(formatar_valor_carrinho($item['price'])) ?>
                    </div>

                    <!-- Quantidade -->
                    <div class="col-span-2">
                        <form method="post" action="carrinho.php" class="form-quantidade">
                            <?= csrf_field() ?>
                            <input type="hidden" name="acao" value="atualizar_quantidade">
                            <input type="hidden" name="produto_id" value="<?= (int) $item['id'] ?>">
                            <input 
                                type="number" 
                                class="quantidade w-20 px-3 py-2 border border-gray-300 focus:border-[#f96b21] focus:ring-1 focus:ring-[#f96b21] text-center font-bold text-[#0d233f] text-sm" 
                                name="quantidade" 
                                min="1" 
                                max="<?= (int) $item['stock_limit'] ?>" 
                                value="<?= (int) $item['quantity'] ?>" 
                                step="1" 
                                required 
                                aria-label="Quantidade de <?= escapar($item['name']) ?>"
                            >
                        </form>
                        <small class="block text-[10px] text-gray-400 mt-1 uppercase tracking-wide">
                            Disp: <?= (int) $item['stock_limit'] ?>
                        </small>
                    </div>

                    <!-- Subtotal -->
                    <div class="col-span-2 text-right font-black text-[#f96b21] text-lg subtotal">
                        <?= escapar(formatar_valor_carrinho($item['subtotal'])) ?>
                    </div>

                    <!-- Remover -->
                    <div class="col-span-1 text-right">
                        <form method="post" action="carrinho.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="produto_id" value="<?= (int) $item['id'] ?>">
                            <button 
                                class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 transition border border-transparent hover:border-red-200" 
                                type="submit" 
                                onclick="return confirm('Tens a certeza de que pretendes remover este produto?');"
                                title="Remover"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- Total e Ações -->
            <div class="p-6 bg-[#f8f9fa] border-t border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    
                    <div class="text-center md:text-left">
                        <span class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Total a Pagar</span>
                        <div class="text-3xl font-black text-[#0d233f]" id="total-carrinho">
                            <?= escapar(formatar_valor_carrinho($total)) ?>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <a class="bg-white border border-gray-300 text-[#0d233f] hover:bg-gray-50 font-bold text-xs uppercase tracking-wider px-6 py-3 text-center transition" href="index.php">
                            Continuar a Comprar
                        </a>
                        <a class="bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-3 text-center transition shadow-md flex items-center justify-center gap-2" href="finalizar-pedido.php">
                            Finalizar Compra <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                </div>
            </div>
        </section>

    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campos = document.querySelectorAll('.quantidade');
    const linhas = document.querySelectorAll('.item-carrinho');
    const totalElemento = document.getElementById('total-carrinho');

    const temporizadores = new WeakMap();

    function formatarKz(valor) {
        return valor.toLocaleString('pt-PT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' Kz';
    }

    function atualizarTotais() {
        let total = 0;

        linhas.forEach(function (linha) {
            const preco = Number(linha.dataset.preco);
            const campo = linha.querySelector('.quantidade');

            let quantidade = Math.floor(Number(campo.value) || 1);
            const maximo = Number(campo.max);

            quantidade = Math.max(1, Math.min(quantidade, maximo));
            total += preco * quantidade;

            linha.querySelector('.subtotal').textContent = formatarKz(preco * quantidade);
        });

        if (totalElemento) {
            totalElemento.textContent = formatarKz(total);
        }
    }

    campos.forEach(function (campo) {
        campo.addEventListener('input', function () {
            const maximo = Number(campo.max);

            if (campo.value !== '') {
                let valor = Math.floor(Number(campo.value));

                if (valor > maximo) valor = maximo;
                if (valor < 1) valor = 1;

                campo.value = valor;
            }

            atualizarTotais();

            clearTimeout(temporizadores.get(campo));

            if (campo.value !== '' && campo.checkValidity()) {
                temporizadores.set(campo, setTimeout(function () {
                    campo.form.requestSubmit();
                }, 600));
            }
        });

        campo.addEventListener('change', function () {
            if (campo.value === '' || !campo.checkValidity()) {
                campo.value = campo.defaultValue;
                atualizarTotais();
                return;
            }

            clearTimeout(temporizadores.get(campo));
            campo.form.requestSubmit();
        });
    });

    atualizarTotal();
});
</script>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>