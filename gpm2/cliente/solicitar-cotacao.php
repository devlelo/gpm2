<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pdo = db();

if (!cliente_atual()) {
    $_SESSION['gpm_redirect_after_login'] = 'solicitar-cotacao.php';
    flash('error', 'Inicia sessão ou cria uma conta para enviar o pedido de cotação.');
    redirect('login.php');
    exit;
}

$clienteId = (int) cliente_atual()['id'];
$stmt = $pdo->prepare('SELECT id, customer_type, name, nif, phone, email, active FROM customers WHERE id = ? LIMIT 1');
$stmt->execute([$clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente || !(int)$cliente['active']) {
    unset($_SESSION['gpm_customer']);
    flash('error', 'A tua conta não está disponível. Inicia sessão novamente.');
    redirect('login.php');
    exit;
}

$carrinho = $_SESSION['gpm_cart'] ?? [];
if (!is_array($carrinho) || !$carrinho) {
    flash('error', 'O carrinho está vazio.');
    redirect('carrinho.php');
    exit;
}

$erro = '';
$telefone = trim((string)($_POST['customer_phone'] ?? $cliente['phone'] ?? ''));
$notas = trim((string)($_POST['notes'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if ($telefone === '' || strlen($telefone) > 40) {
        $erro = 'Indica um número de WhatsApp válido.';
    } elseif (strlen($notas) > 5000) {
        $erro = 'As observações ultrapassam o limite permitido.';
    } else {
        try {
            $pdo->beginTransaction();
            $linhas = [];

            foreach ($carrinho as $produtoId => $quantidade) {
                $produtoId = filter_var($produtoId, FILTER_VALIDATE_INT);
                $quantidade = filter_var($quantidade, FILTER_VALIDATE_INT);

                if (!$produtoId || $produtoId < 1 || !$quantidade || $quantidade < 1) {
                    throw new RuntimeException('Existe um produto ou quantidade inválida no carrinho.');
                }

                $stmt = $pdo->prepare(
                    'SELECT id, name, sku, unit, min_order_quantity, active
                     FROM products WHERE id = ? LIMIT 1'
                );
                $stmt->execute([$produtoId]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$produto || !(int)$produto['active']) {
                    throw new RuntimeException('Um dos produtos já não está disponível.');
                }

                $minimo = max(1, (int)ceil((float)($produto['min_order_quantity'] ?? 1)));
                if ($quantidade < $minimo) {
                    throw new RuntimeException('A quantidade mínima de "' . $produto['name'] . '" é ' . $minimo . '.');
                }

                $linhas[] = [
                    'id' => (int)$produto['id'],
                    'name' => (string)$produto['name'],
                    'sku' => $produto['sku'],
                    'unit' => (string)($produto['unit'] ?: 'unidade'),
                    'quantity' => $quantidade
                ];
            }

            if (!$linhas) {
                throw new RuntimeException('O carrinho não contém produtos válidos.');
            }

            $numeroPedido = 'COT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare(
                "INSERT INTO orders (
                    order_number, customer_id, customer_type, customer_name,
                    customer_nif, customer_phone, customer_email, delivery_fee,
                    discount, subtotal, total, payment_status, status,
                    stock_deducted, notes, order_type, request_status, ordered_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 'pending', 'pending', 0, ?, 'quote_request', 'submitted', NOW())"
            );
            $stmt->execute([
                $numeroPedido, $clienteId, $cliente['customer_type'], $cliente['name'],
                $cliente['nif'], $telefone, $cliente['email'], $notas !== '' ? $notas : null
            ]);
            $pedidoId = (int)$pdo->lastInsertId();

            $stmtItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, product_sku, unit, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, 0, 0)'
            );
            foreach ($linhas as $linha) {
                $stmtItem->execute([
                    $pedidoId, $linha['id'], $linha['name'], $linha['sku'],
                    $linha['unit'], $linha['quantity']
                ]);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO conversations (order_id, customer_id, status, last_message_at)
                 VALUES (?, ?, 'open', NOW())"
            );
            $stmt->execute([$pedidoId, $clienteId]);
            $conversaId = (int)$pdo->lastInsertId();

            $mensagem = 'Olá. Enviei o pedido de cotação ' . $numeroPedido . '. Aguardo a análise e a proposta comercial da GPM.';
            $stmt = $pdo->prepare(
                "INSERT INTO messages (conversation_id, sender_type, customer_sender_id, body)
                 VALUES (?, 'customer', ?, ?)"
            );
            $stmt->execute([$conversaId, $clienteId, $mensagem]);

            $pdo->commit();

            unset($_SESSION['gpm_cart']);
            $_SESSION['gpm_ultimo_pedido_id'] = $pedidoId;
            flash('success', 'Pedido de cotação enviado com sucesso. A equipa GPM irá analisar o teu pedido.');
            redirect('pedido.php?id=' . $pedidoId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[GPM cotação] ' . $e->getMessage());
            $erro = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível enviar o pedido. Tenta novamente.';
        }
    }
}

require_once __DIR__ . '/includes/header-marketplace.php';
?>
<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative w-full">
    <div class="mb-8">
        <a href="carrinho.php" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#f96b21] transition">
            <i class="fa-solid fa-arrow-left"></i> Voltar ao carrinho
        </a>
    </div>

    <div class="mb-10">
        <span class="inline-block bg-[#f96b21]/10 text-[#f96b21] text-[10px] font-bold uppercase tracking-widest px-3 py-1 mb-3">Cotação</span>
        <h1 class="text-3xl md:text-5xl font-black text-[#0d233f] uppercase tracking-tight">Solicitar Cotação</h1>
        <p class="text-gray-500 text-sm mt-2">Confirma os teus contactos. A equipa GPM irá verificar disponibilidade, quantidades e condições antes de enviar a proposta.</p>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm" role="alert"><?= escapar($erro) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <section class="lg:col-span-2 bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
            <form method="post" class="space-y-6">
                <?= csrf_field() ?>
                <div>
                    <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-100 pb-3 mb-5">Dados de contacto</h2>
                    <label for="customer_name" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Nome</label>
                    <input id="customer_name" value="<?= escapar($cliente['name']) ?>" disabled class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50">
                    <label for="customer_phone" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mt-5 mb-2">WhatsApp *</label>
                    <input id="customer_phone" name="customer_phone" value="<?= escapar($telefone) ?>" maxlength="40" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] bg-gray-50">
                    <label for="customer_email" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mt-5 mb-2">Email</label>
                    <input id="customer_email" value="<?= escapar($cliente['email']) ?>" disabled class="w-full px-4 py-3 text-sm border border-gray-300 bg-gray-50">
                </div>
                <div>
                    <label for="notes" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Observações do pedido</label>
                    <textarea id="notes" name="notes" rows="4" maxlength="5000" class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] bg-gray-50"><?= escapar($notas) ?></textarea>
                </div>
                <div class="bg-[#f8f9fa] border-l-4 border-[#f96b21] p-4 text-sm text-gray-600 leading-relaxed">
                    <i class="fa-solid fa-circle-info text-[#f96b21] mr-2"></i>
                    Este envio não é uma compra nem uma confirmação de stock. Não será feito qualquer pagamento ou desconto de stock nesta fase. A proposta comercial será preparada pela GPM.
                </div>
                <button type="submit" class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition shadow-md flex items-center justify-center gap-2">
                    Enviar pedido de cotação <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </section>

        <aside class="bg-[#f8f9fa] border border-gray-100 p-6 sm:p-8 h-fit">
            <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-200 pb-3 mb-5">Resumo do pedido</h2>
            <div class="space-y-4 mb-6">
                <?php foreach ($carrinho as $id => $quantidade): ?>
                    <?php
                    $stmt = $pdo->prepare('SELECT name, unit FROM products WHERE id = ? AND active = 1 LIMIT 1');
                    $stmt->execute([(int)$id]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <?php if ($item): ?>
                        <div class="flex justify-between gap-4 text-sm border-b border-gray-200 pb-3">
                            <span class="font-bold text-[#0d233f]"><?= escapar($item['name']) ?><small class="block text-gray-500 font-normal"><?= escapar($item['unit'] ?? 'unidade') ?></small></span>
                            <span class="font-black text-[#0d233f]">× <?= (int)$quantidade ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-gray-500">Itens: <?= array_sum(array_map('intval', $carrinho)) ?> unidades solicitadas</p>
            <p class="text-xs text-gray-500 mt-2">Os preços, disponibilidade e custos de entrega serão confirmados na proposta.</p>
        </aside>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>
