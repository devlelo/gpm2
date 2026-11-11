<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

/*
|--------------------------------------------------------------------------
| 1. PROVÍNCIAS DE ANGOLA
|--------------------------------------------------------------------------
*/

$provinciasAngola = [
    'Bengo', 'Benguela', 'Bié', 'Cabinda', 'Cuando', 'Cubango',
    'Cuanza-Norte', 'Cuanza-Sul', 'Cunene', 'Huambo', 'Huíla',
    'Icolo e Bengo', 'Luanda', 'Lunda-Norte', 'Lunda-Sul',
    'Malanje', 'Moxico', 'Moxico Leste', 'Namibe', 'Uíge', 'Zaire'
];

/*
|--------------------------------------------------------------------------
| 2. EXIGIR LOGIN
|--------------------------------------------------------------------------
*/

if (!cliente_atual()) {
    $_SESSION['gpm_redirect_after_login'] = 'finalizar-pedido.php';
    flash('error', 'Inicia sessão ou cria uma conta para finalizar a compra.');
    redirect('login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. OBTER CLIENTE
|--------------------------------------------------------------------------
*/

$pdo = db();
$clienteId = (int) cliente_atual()['id'];

$stmt = $pdo->prepare(
    "SELECT id, customer_type, name, nif, phone, email,
            address, city, province, active
     FROM customers
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente || !(int) $cliente['active']) {
    unset($_SESSION['gpm_customer']);
    $_SESSION['gpm_redirect_after_login'] = 'finalizar-pedido.php';
    flash('error', 'A tua conta não está disponível. Verifica os teus dados.');
    redirect('login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. CARREGAR LOCALIZAÇÕES DO CLIENTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, street, neighborhood, reference_point, city, province, is_default
     FROM customer_addresses
     WHERE customer_id = ?
     ORDER BY is_default DESC, id DESC"
);

$stmt->execute([$clienteId]);
$localizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 5. VERIFICAR CARRINHO
|--------------------------------------------------------------------------
*/

$carrinho = $_SESSION['gpm_cart'] ?? [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (!$carrinho || !is_array($carrinho))
    && !empty($_SESSION['gpm_ultimo_pedido_id'])
) {
    redirect('pedido.php?id=' . (int) $_SESSION['gpm_ultimo_pedido_id']);
    exit;
}

if (!$carrinho || !is_array($carrinho)) {
    flash('error', 'O teu carrinho está vazio.');
    redirect('index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 6. LOCALIZAÇÃO INICIAL
|--------------------------------------------------------------------------
*/

$localizacaoSelecionada = 'nova';
$localizacaoInicial = null;

foreach ($localizacoes as $localizacao) {
    if ((int) $localizacao['is_default'] === 1) {
        $localizacaoSelecionada = (string) $localizacao['id'];
        $localizacaoInicial = $localizacao;
        break;
    }
}

if ($localizacaoInicial === null && !empty($localizacoes)) {
    $localizacaoInicial = $localizacoes[0];
    $localizacaoSelecionada = (string) $localizacaoInicial['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $localizacaoSelecionada = (string) ($_POST['delivery_address_id'] ?? 'nova');
    $localizacaoInicial = null;

    foreach ($localizacoes as $localizacao) {
        if ((string) $localizacao['id'] === $localizacaoSelecionada) {
            $localizacaoInicial = $localizacao;
            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| 7. VALORES INICIAIS DOS CAMPOS
|--------------------------------------------------------------------------
*/

$telefoneFormulario = $_POST['customer_phone'] ?? ($cliente['phone'] ?? '');

$ruaFormulario = $_POST['delivery_street'] ?? ($localizacaoInicial['street'] ?? '');
$bairroFormulario = $_POST['delivery_neighborhood'] ?? ($localizacaoInicial['neighborhood'] ?? '');
$referenciaFormulario = $_POST['delivery_reference'] ?? ($localizacaoInicial['reference_point'] ?? '');
$cidadeFormulario = $_POST['delivery_city'] ?? ($localizacaoInicial['city'] ?? '');
$provinciaFormulario = $_POST['delivery_province'] ?? ($localizacaoInicial['province'] ?? '');

$pagamentoAtual = $_POST['payment_method'] ?? '';
$notasFormulario = $_POST['notes'] ?? '';
$guardarLocalizacao = isset($_POST['save_delivery_address']);

$erro = '';

/*
|--------------------------------------------------------------------------
| 8. PROCESSAR FINALIZAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $localizacaoId = trim($_POST['delivery_address_id'] ?? 'nova');
    $telefone = trim($_POST['customer_phone'] ?? '');
    $rua = trim($_POST['delivery_street'] ?? '');
    $bairro = trim($_POST['delivery_neighborhood'] ?? '');
    $referencia = trim($_POST['delivery_reference'] ?? '');
    $cidade = trim($_POST['delivery_city'] ?? '');
    $provincia = trim($_POST['delivery_province'] ?? '');
    $metodoPagamento = trim($_POST['payment_method'] ?? '');
    $notas = trim($_POST['notes'] ?? '');
    $guardarLocalizacao = isset($_POST['save_delivery_address']);

    if ($localizacaoId !== 'nova') {
        $idLocalizacao = filter_var($localizacaoId, FILTER_VALIDATE_INT);

        if (!$idLocalizacao || $idLocalizacao < 1) {
            $erro = 'A localização selecionada é inválida.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1"
            );
            $stmt->execute([$idLocalizacao, $clienteId]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $erro = 'A localização selecionada não pertence à tua conta.';
            }
        }
    }

    if ($erro === '' && !in_array($provincia, $provinciasAngola, true)) {
        $erro = 'Seleciona uma província válida.';
    }

    if (
        $erro === ''
        && ($telefone === '' || $rua === '' || $bairro === '' || $cidade === '' || $provincia === '')
    ) {
        $erro = 'Preenche o telefone, a cidade, a rua, o bairro e a província.';
    }

    if (
        $erro === ''
        && (
            strlen($telefone) > 40 || strlen($cidade) > 100 || strlen($rua) > 180
            || strlen($bairro) > 120 || strlen($referencia) > 255
        )
    ) {
        $erro = 'Um dos campos ultrapassou o limite permitido.';
    }

    if (
        $erro === ''
        && !in_array($metodoPagamento, ['', 'transferencia', 'dinheiro', 'multicaixa'], true)
    ) {
        $erro = 'O método de pagamento selecionado é inválido.';
    }

    if ($erro === '' && strlen($notas) > 5000) {
        $erro = 'As observações são demasiado longas.';
    }

    if ($localizacaoId !== 'nova') {
        $guardarLocalizacao = false;
    }

    if ($erro === '') {

        try {

            $pdo->beginTransaction();
            $linhas = [];
            $subtotal = 0.0;

            foreach ($carrinho as $id => $quantidade) {

                $id = filter_var($id, FILTER_VALIDATE_INT);
                $quantidade = filter_var($quantidade, FILTER_VALIDATE_INT);

                if (!$id || $id < 1 || !$quantidade || $quantidade < 1) {
                    throw new RuntimeException('O carrinho contém um produto ou uma quantidade inválida.');
                }

                $stmt = $pdo->prepare(
                    "SELECT id, name, sku, price, stock_quantity, unit, active
                     FROM products WHERE id = ? FOR UPDATE"
                );
                $stmt->execute([$id]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$produto || !(int) $produto['active']) {
                    throw new RuntimeException('Um dos produtos já não está disponível.');
                }

                if ((float) $produto['stock_quantity'] < $quantidade) {
                    throw new RuntimeException('Stock insuficiente para o produto: ' . $produto['name']);
                }

                $stmtStock = $pdo->prepare(
                    "UPDATE products SET stock_quantity = stock_quantity - ?
                     WHERE id = ? AND stock_quantity >= ? AND active = 1"
                );
                $stmtStock->execute([$quantidade, $id, $quantidade]);

                if ($stmtStock->rowCount() !== 1) {
                    throw new RuntimeException(
                        'O stock do produto ' . $produto['name'] . ' foi alterado. Atualiza o carrinho e tenta novamente.'
                    );
                }

                $preco = (float) $produto['price'];
                $valorLinha = round($quantidade * $preco, 2);
                $subtotal += $valorLinha;

                $linhas[] = [
                    'id' => (int) $produto['id'],
                    'name' => $produto['name'],
                    'sku' => $produto['sku'],
                    'unit' => $produto['unit'],
                    'quantity' => $quantidade,
                    'price' => $preco,
                    'subtotal' => $valorLinha,
                    'stock_before' => (float)$produto['stock_quantity'],
                    'stock_after' => (float)$produto['stock_quantity'] - $quantidade
                ];
            }

            if (!$linhas) {
                throw new RuntimeException('Não existem produtos válidos no carrinho.');
            }

            $subtotal = round($subtotal, 2);
            $stmtTaxaEntrega = $pdo->query('SELECT delivery_fee FROM company_settings ORDER BY id LIMIT 1');
            $taxaEntrega = max(0, (float)($stmtTaxaEntrega->fetchColumn() ?: 0));
            $desconto = 0.00;
            $total = round($subtotal + $taxaEntrega - $desconto, 2);

            $moradaCompleta = $rua . ', Bairro ' . $bairro . ', ' . $cidade . ', ' . $provincia;
            if ($referencia !== '') {
                $moradaCompleta .= '. Referência: ' . $referencia;
            }

            $numeroPedido = 'GPM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $stmt = $pdo->prepare(
                "INSERT INTO orders (
                    order_number, customer_id, responsible_user_id, customer_type,
                    customer_name, customer_nif, customer_phone, customer_email,
                    delivery_address, delivery_city, delivery_province,
                    delivery_street, delivery_neighborhood, delivery_reference,
                    delivery_fee, discount, subtotal, total,
                    payment_method, payment_status, status, stock_deducted, notes, ordered_at, created_at, updated_at
                ) VALUES (
                    ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'pending', 'pending', 1, ?, NOW(), NOW(), NOW()
                )"
            );

            $stmt->execute([
                $numeroPedido, $clienteId, $cliente['customer_type'], $cliente['name'],
                $cliente['nif'], $telefone, $cliente['email'], $moradaCompleta,
                $cidade, $provincia, $rua, $bairro,
                $referencia !== '' ? $referencia : null,
                $taxaEntrega, $desconto, $subtotal, $total,
                $metodoPagamento !== '' ? $metodoPagamento : null,
                $notas !== '' ? $notas : null
            ]);

            $pedidoId = (int) $pdo->lastInsertId();

            $stmtItem = $pdo->prepare(
                "INSERT INTO order_items (
                    order_id, product_id, product_name, product_sku,
                    quantity, unit, unit_price, subtotal, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            foreach ($linhas as $linha) {
                $stmtItem->execute([
                    $pedidoId, $linha['id'], $linha['name'], $linha['sku'],
                    $linha['quantity'], $linha['unit'], $linha['price'], $linha['subtotal']
                ]);
            }

            $stmtMovimento = $pdo->prepare(
                "INSERT INTO stock_movements (
                    product_id, user_id, order_id, movement_type,
                    quantity, stock_before, stock_after, reason, created_at
                ) VALUES (?, NULL, ?, 'sale', ?, ?, ?, ?, NOW())"
            );

            foreach ($linhas as $linha) {
                $stmtMovimento->execute([
                    $linha['id'],
                    $pedidoId,
                    $linha['quantity'],
                    $linha['stock_before'],
                    $linha['stock_after'],
                    'Venda registada no pedido ' . $numeroPedido
                ]);
            }

            $numeroProforma = 'PF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $stmtProforma = $pdo->prepare(
                "INSERT INTO proformas (
                    order_id, proforma_number, customer_name, customer_nif,
                    customer_phone, customer_email, customer_address,
                    subtotal, delivery_fee, discount, total, status, issue_date,
                    notes, created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'issued', CURDATE(), ?, NULL, NOW(), NOW()
                )"
            );

            $stmtProforma->execute([
                $pedidoId, $numeroProforma, $cliente['name'], $cliente['nif'],
                $telefone, $cliente['email'], $moradaCompleta, $subtotal,
                $taxaEntrega, $desconto, $total,
                'Proforma criada a partir do pedido ' . $numeroPedido
            ]);

            $stmtConversa = $pdo->prepare(
                "INSERT INTO conversations (
                    order_id, customer_id, assigned_user_id, status,
                    last_message_at, created_at, updated_at
                ) VALUES (?, ?, NULL, 'open', NOW(), NOW(), NOW())"
            );
            $stmtConversa->execute([$pedidoId, $clienteId]);
            $conversaId = (int) $pdo->lastInsertId();

            $stmtMensagem = $pdo->prepare(
                "INSERT INTO messages (
                    conversation_id, sender_type, customer_sender_id,
                    user_sender_id, body, created_at
                ) VALUES (?, 'customer', ?, NULL, ?, NOW())"
            );
            $stmtMensagem->execute([
                $conversaId, $clienteId,
                'Olá. Fiz o pedido ' . $numeroPedido . ' e gostaria de acompanhar o seu estado.'
            ]);

            $stmtHistorico = $pdo->prepare(
                "INSERT INTO order_status_history (
                    order_id, user_id, old_status, new_status, note, created_at
                ) VALUES (?, NULL, NULL, 'pending', ?, NOW())"
            );
            $stmtHistorico->execute([$pedidoId, 'Pedido criado pelo marketplace.']);

            if ($guardarLocalizacao) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM customer_addresses WHERE customer_id = ?"
                );
                $stmt->execute([$clienteId]);
                $totalLocalizacoes = (int) $stmt->fetchColumn();
                $principal = $totalLocalizacoes === 0 ? 1 : 0;

                $stmt = $pdo->prepare(
                    "INSERT INTO customer_addresses (
                        customer_id, label, address, street, neighborhood,
                        reference_point, city, province, is_default, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
                );

                $stmt->execute([
                    $clienteId, $bairro, $moradaCompleta, $rua, $bairro,
                    $referencia !== '' ? $referencia : null,
                    $cidade, $provincia, $principal
                ]);
            }

            $pdo->commit();

            $_SESSION['gpm_ultimo_pedido_id'] = $pedidoId;
            unset($_SESSION['gpm_cart']);

            flash('success', 'Pedido criado com sucesso.');

            redirect('pedido.php?id=' . $pedidoId);
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Erro no checkout GPM: ' . $e->getMessage());

            $erro = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível finalizar o pedido. Tenta novamente.';
        }
    }
}

require_once __DIR__ . '/includes/header-marketplace.php';
?>

<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative w-full">

    <!-- Voltar -->
    <div class="mb-8 reveal">
        <a href="carrinho.php" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#f96b21] transition">
            <i class="fa-solid fa-arrow-left"></i> Voltar ao carrinho
        </a>
    </div>

    <!-- Cabeçalho -->
    <div class="mb-10 reveal">
        <span class="inline-block bg-[#f96b21]/10 text-[#f96b21] text-[10px] font-bold uppercase tracking-widest px-3 py-1 mb-3">
            Checkout
        </span>
        <h1 class="text-3xl md:text-5xl font-black text-[#0d233f] uppercase tracking-tight">Finalizar Compra</h1>
        <p class="text-gray-500 text-sm mt-2">Escolha a localização de entrega e confirme os dados para concluir o seu pedido.</p>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm reveal" role="alert">
            <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Formulário -->
        <div class="lg:col-span-2 reveal">
            <form method="post" id="form-checkout" class="bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                <?= csrf_field() ?>

                <!-- Secção 1: Localização -->
                <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-100 pb-3 mb-6">Localização de Entrega</h2>

                <div class="space-y-4 mb-8">
                    <?php if (!empty($localizacoes)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($localizacoes as $loc): ?>
                                <label class="relative cursor-pointer block">
                                    <input
                                        type="radio"
                                        name="delivery_address_id"
                                        value="<?= (int) $loc['id'] ?>"
                                        class="peer sr-only localizacao-radio"
                                        data-street="<?= escapar($loc['street'] ?? '') ?>"
                                        data-neighborhood="<?= escapar($loc['neighborhood'] ?? '') ?>"
                                        data-reference="<?= escapar($loc['reference_point'] ?? '') ?>"
                                        data-city="<?= escapar($loc['city'] ?? '') ?>"
                                        data-province="<?= escapar($loc['province'] ?? '') ?>"
                                        <?= $localizacaoSelecionada === (string) $loc['id'] ? 'checked' : '' ?>
                                    >
                                    <div class="border border-gray-300 p-4 transition peer-checked:border-[#f96b21] peer-checked:bg-[#f96b21]/5 hover:border-gray-400">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="font-bold text-[#0d233f] text-sm"><?= escapar($loc['neighborhood'] ?: 'Localização') ?></span>
                                            <?php if ((int) $loc['is_default'] === 1): ?>
                                                <span class="text-[9px] font-bold text-[#f96b21] bg-[#f96b21]/10 px-1.5 py-0.5 uppercase">Principal</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-gray-500 line-clamp-1"><?= escapar($loc['street'] ?? '') ?></p>
                                        <p class="text-[10px] text-gray-400 mt-1"><?= escapar($loc['city'] ?? '') ?><?= $loc['province'] ? ' / ' . escapar($loc['province']) : '' ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <label class="relative cursor-pointer block">
                        <input
                            type="radio"
                            name="delivery_address_id"
                            value="nova"
                            class="peer sr-only localizacao-radio"
                            <?= $localizacaoSelecionada === 'nova' ? 'checked' : '' ?>
                        >
                        <div class="border border-dashed border-gray-400 p-4 transition peer-checked:border-[#f96b21] peer-checked:bg-[#f96b21]/5 hover:border-gray-500">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-plus text-[#f96b21]"></i>
                                <span class="font-bold text-[#0d233f] text-sm">Introduzir nova localização</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Preencha os campos abaixo com a nova morada de entrega.</p>
                        </div>
                    </label>
                </div>

                <!-- Secção 2: Dados -->
                <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-100 pb-3 mb-6">Dados de Contacto & Morada</h2>

                <div class="space-y-5">

                    <!-- Telefone -->
                    <div>
                        <label for="customer_phone" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Telefone *</label>
                        <input
                            type="text"
                            name="customer_phone"
                            id="customer_phone"
                            maxlength="40"
                            required
                            value="<?= escapar($telefoneFormulario) ?>"
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                        >
                    </div>

                    <!-- Província e Cidade -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="delivery_province" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Província *</label>
                            <select
                                name="delivery_province"
                                id="delivery_province"
                                required
                                class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            >
                                <option value="">Seleciona a província</option>
                                <?php foreach ($provinciasAngola as $provincia): ?>
                                    <option value="<?= escapar($provincia) ?>" <?= $provinciaFormulario === $provincia ? 'selected' : '' ?>>
                                        <?= escapar($provincia) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="delivery_city" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Cidade / Município *</label>
                            <input
                                type="text"
                                name="delivery_city"
                                id="delivery_city"
                                maxlength="100"
                                required
                                value="<?= escapar($cidadeFormulario) ?>"
                                class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            >
                        </div>
                    </div>

                    <!-- Rua e Bairro -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="delivery_street" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Rua *</label>
                            <input
                                type="text"
                                name="delivery_street"
                                id="delivery_street"
                                maxlength="180"
                                required
                                value="<?= escapar($ruaFormulario) ?>"
                                class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            >
                        </div>
                        <div>
                            <label for="delivery_neighborhood" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Bairro *</label>
                            <input
                                type="text"
                                name="delivery_neighborhood"
                                id="delivery_neighborhood"
                                maxlength="120"
                                required
                                value="<?= escapar($bairroFormulario) ?>"
                                class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            >
                        </div>
                    </div>

                    <!-- Referência -->
                    <div>
                        <label for="delivery_reference" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Ponto de Referência</label>
                        <textarea
                            name="delivery_reference"
                            id="delivery_reference"
                            rows="2"
                            maxlength="255"
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                        ><?= escapar($referenciaFormulario) ?></textarea>
                    </div>

                    <!-- Guardar Localização -->
                    <div id="guardar_localizacao_container" class="bg-[#f8f9fa] border border-gray-100 p-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                id="save_delivery_address"
                                name="save_delivery_address"
                                value="1"
                                <?= $guardarLocalizacao ? 'checked' : '' ?>
                                class="w-4 h-4 text-[#f96b21] focus:ring-[#f96b21] border-gray-300"
                            >
                            <span class="text-sm font-bold text-[#0d233f]">Guardar esta localização para próximas compras</span>
                        </label>
                    </div>

                </div>

                <!-- Secção 3: Pagamento -->
                <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-100 pb-3 mb-6 mt-10">Pagamento</h2>

                <div class="space-y-5">
                    <div>
                        <label for="payment_method" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Método de Pagamento</label>
                        <select
                            id="payment_method"
                            name="payment_method"
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                        >
                            <option value="" <?= $pagamentoAtual === '' ? 'selected' : '' ?>>A combinar</option>
                            <option value="transferencia" <?= $pagamentoAtual === 'transferencia' ? 'selected' : '' ?>>Transferência bancária</option>
                            <option value="dinheiro" <?= $pagamentoAtual === 'dinheiro' ? 'selected' : '' ?>>Pagamento em dinheiro</option>
                            <option value="multicaixa" <?= $pagamentoAtual === 'multicaixa' ? 'selected' : '' ?>>Multicaixa Express</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Observações</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                        ><?= escapar($notasFormulario) ?></textarea>
                    </div>

                    <div class="bg-[#f8f9fa] border-l-4 border-[#f96b21] p-4 text-sm text-gray-600 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-[#f96b21] mr-2"></i>
                        O pedido será registado como pendente. A equipa GPM entrará em contacto através da conversa privada para combinar os detalhes de pagamento e entrega.
                    </div>
                </div>

            </form>
        </div>

        <!-- Resumo do Cliente -->
        <div class="lg:col-span-1 reveal" style="animation-delay: 0.2s;">
            <div class="bg-[#f8f9fa] border border-gray-100 p-6 sm:p-8 sticky top-28">

                <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide border-b border-gray-200 pb-3 mb-6">Dados do Cliente</h2>

                <div class="space-y-4 text-sm mb-8">
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Nome</span>
                        <span class="font-bold text-[#0d233f]"><?= escapar($cliente['name']) ?></span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">E-mail</span>
                        <span class="text-gray-600"><?= escapar($cliente['email']) ?></span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">NIF</span>
                        <span class="text-gray-600"><?= escapar($cliente['nif'] ?: 'Não informado') ?></span>
                    </div>
                </div>

                <button
                    type="submit"
                    form="form-checkout"
                    class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider py-4 transition shadow-md flex items-center justify-center gap-2"
                >
                    Confirmar Pedido <i class="fa-solid fa-check"></i>
                </button>

                <p class="text-[10px] text-gray-400 text-center mt-4 leading-tight">
                    Ao confirmar, concorda com os nossos <br>termos e condições de compra.
                </p>
            </div>
        </div>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const radiosLocalizacao = document.querySelectorAll('.localizacao-radio');
    const provincia = document.getElementById('delivery_province');
    const cidade = document.getElementById('delivery_city');
    const rua = document.getElementById('delivery_street');
    const bairro = document.getElementById('delivery_neighborhood');
    const referencia = document.getElementById('delivery_reference');

    const checkboxGuardar = document.getElementById('save_delivery_address');
    const containerGuardar = document.getElementById('guardar_localizacao_container');

    radiosLocalizacao.forEach(function(radio) {
        radio.addEventListener('change', function () {
            if (this.value === 'nova') {
                provincia.value = '';
                cidade.value = '';
                rua.value = '';
                bairro.value = '';
                referencia.value = '';

                if (containerGuardar) containerGuardar.style.display = 'block';
                if (checkboxGuardar) checkboxGuardar.checked = false;

                rua.focus();
                return;
            }

            provincia.value = this.dataset.province || '';
            cidade.value = this.dataset.city || '';
            rua.value = this.dataset.street || '';
            bairro.value = this.dataset.neighborhood || '';
            referencia.value = this.dataset.reference || '';

            if (checkboxGuardar) checkboxGuardar.checked = false;
            if (containerGuardar) containerGuardar.style.display = 'none';
        });
    });

    if (containerGuardar) {
        const selecionada = document.querySelector('.localizacao-radio:checked');
        if (selecionada && selecionada.value !== 'nova') {
            containerGuardar.style.display = 'none';
        }
    }

});
</script>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>