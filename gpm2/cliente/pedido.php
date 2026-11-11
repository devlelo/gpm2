<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';
exigir_cliente();

$pdo = db();
$clienteId = (int) cliente_atual()['id'];
$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pedidoId) {
    redirect('painel-cliente/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT *
     FROM orders
     WHERE id = ? AND customer_id = ?
     LIMIT 1"
);
$stmt->execute([$pedidoId, $clienteId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    flash('error', 'Pedido não encontrado.');
    redirect('painel-cliente/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT product_name, product_sku, quantity, unit,
            unit_price, subtotal
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC"
);
$stmt->execute([$pedidoId]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id
     FROM conversations
     WHERE order_id = ? AND customer_id = ?
     LIMIT 1"
);
$stmt->execute([$pedidoId, $clienteId]);
$conversa = $stmt->fetch(PDO::FETCH_ASSOC);

function estado_pedido_detalhe(string $estado): string
{
    $estados = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'preparing' => 'Em preparação',
        'shipped' => 'Enviado',
        'completed' => 'Entregue',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    return $estados[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}

require_once __DIR__ . '/includes/header-marketplace.php';
?>

<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative w-full">

    <!-- Voltar (Agora aponta para o painel do cliente) -->
    <div class="mb-8 reveal">
        <a href="painel-cliente/index.php" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#f96b21] transition">
            <i class="fa-solid fa-arrow-left"></i> Ir para o painel de controle de pedidos
        </a>
    </div>

    <!-- Cabeçalho do Pedido -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-4 border-b border-gray-200 pb-6 reveal">
        <div>
            <div class="inline-block bg-[#f96b21]/10 text-[#f96b21] text-[10px] font-bold uppercase tracking-widest px-3 py-1 mb-3">
                Detalhes do Pedido
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-[#0d233f] uppercase tracking-tight">
                <?= escapar($pedido['order_number']) ?>
            </h1>
            <p class="text-gray-500 text-sm mt-2">
                Efetuado em <?= date('d/m/Y \à\s H:i', strtotime($pedido['ordered_at'])) ?>
            </p>
        </div>
        <div class="text-left md:text-right">
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total a Pagar</span>
            <span class="text-3xl font-black text-[#0d233f]"><?= escapar(money($pedido['total'])) ?></span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Linha de Estado Visual -->
            <div class="bg-white border border-gray-100 shadow-sm p-6 reveal">
                <h2 class="text-xs font-bold text-[#0d233f] uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-[#f96b21]"></i> Estado do Pedido
                </h2>
                <div class="flex items-center justify-between relative">
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-100 -z-10"></div>
                    
                    <div class="flex flex-col items-center gap-2 bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs <?= in_array($pedido['status'], ['pending','confirmed','preparing','shipped','completed','completed']) ? 'bg-[#f96b21] text-white' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider <?= in_array($pedido['status'], ['pending','confirmed','preparing','shipped','completed','completed']) ? 'text-[#0d233f]' : 'text-gray-400' ?>">Pendente</span>
                    </div>

                    <div class="flex flex-col items-center gap-2 bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs <?= in_array($pedido['status'], ['preparing','shipped','completed','completed']) ? 'bg-[#f96b21] text-white' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fa-solid fa-box"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider <?= in_array($pedido['status'], ['preparing','shipped','completed','completed']) ? 'text-[#0d233f]' : 'text-gray-400' ?>">Em Preparação</span>
                    </div>

                    <div class="flex flex-col items-center gap-2 bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs <?= in_array($pedido['status'], ['shipped','completed','completed']) ? 'bg-[#f96b21] text-white' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider <?= in_array($pedido['status'], ['shipped','completed','completed']) ? 'text-[#0d233f]' : 'text-gray-400' ?>">Enviado</span>
                    </div>

                    <div class="flex flex-col items-center gap-2 bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs <?= in_array($pedido['status'], ['completed','completed']) ? 'bg-[#f96b21] text-white' : 'bg-gray-200 text-gray-400' ?>">
                            <i class="fa-solid fa-house-chimney"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider <?= in_array($pedido['status'], ['completed','completed']) ? 'text-[#0d233f]' : 'text-gray-400' ?>">Entregue</span>
                    </div>
                </div>
                
                <?php if ($pedido['status'] === 'cancelled'): ?>
                    <div class="mt-6 bg-red-50 text-red-700 p-3 text-xs font-bold text-center uppercase tracking-wider border border-red-100">
                        Este pedido foi cancelado.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Produtos -->
            <div class="bg-white border border-gray-100 shadow-sm reveal">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-black text-[#0d233f] uppercase tracking-wide">Produtos Comprados</h2>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <?php foreach ($itens as $item): ?>
                        <div class="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <span class="block font-bold text-[#0d233f] text-base"><?= escapar($item['product_name']) ?></span>
                                <span class="block text-xs text-gray-500 uppercase tracking-wider mt-1">
                                    <?= escapar($item['quantity']) ?> <?= escapar($item['unit']) ?>
                                    <?php if ($item['product_sku']): ?>
                                        | Ref: <?= escapar($item['product_sku']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="block text-xs text-gray-400 mb-1"><?= escapar(money($item['unit_price'])) ?> / un</span>
                                <span class="block font-black text-[#f96b21] text-lg"><?= escapar(money($item['subtotal'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="p-6 bg-[#f8f9fa] border-t border-gray-100">
                    <div class="space-y-2 text-sm text-gray-600 max-w-xs ml-auto">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-bold text-[#0d233f]"><?= escapar(money($pedido['subtotal'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Taxa de Entrega</span>
                            <span class="font-bold text-[#0d233f]"><?= escapar(money($pedido['delivery_fee'])) ?></span>
                        </div>
                        <?php if ((float)$pedido['discount'] > 0): ?>
                        <div class="flex justify-between text-emerald-600">
                            <span>Desconto</span>
                            <span class="font-bold">- <?= escapar(money($pedido['discount'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between border-t border-gray-200 pt-3 mt-3">
                            <span class="font-bold text-[#0d233f] uppercase tracking-wide">Total</span>
                            <span class="font-black text-[#0d233f] text-xl"><?= escapar(money($pedido['total'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Coluna Lateral -->
        <div class="lg:col-span-1 space-y-8 reveal" style="animation-delay: 0.2s;">
            
            <!-- Dados de Entrega -->
            <div class="bg-[#0d233f] text-white shadow-lg p-6">
                <h2 class="text-xs font-bold text-[#f96b21] uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-location-dot"></i> Dados de Entrega
                </h2>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Endereço</span>
                        <p class="text-gray-200 leading-relaxed"><?= escapar($pedido['delivery_address']) ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Cidade</span>
                            <p class="text-gray-200"><?= escapar($pedido['delivery_city'] ?: 'N/A') ?></p>
                        </div>
                        <div>
                            <span class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Província</span>
                            <p class="text-gray-200"><?= escapar($pedido['delivery_province'] ?: 'N/A') ?></p>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Contacto</span>
                        <p class="text-gray-200"><?= escapar($pedido['customer_phone']) ?></p>
                    </div>
                    <div>
                        <span class="block text-[10px] text-gray-400 uppercase tracking-wider mb-1">Pagamento</span>
                        <p class="text-[#f96b21] font-bold uppercase tracking-wide">
                            <?= escapar($pedido['payment_status'] ?: 'A combinar') ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Apoio ao Cliente -->
            <div class="bg-white border border-gray-100 shadow-sm p-6">
                <h2 class="text-sm font-black text-[#0d233f] uppercase tracking-wide mb-2">Precisa de Ajuda?</h2>
                <p class="text-xs text-gray-500 leading-relaxed mb-6">
                    Utilize a nossa conversa privada para esclarecer dúvidas sobre este pedido ou combinar detalhes de entrega.
                </p>

                <?php if ($conversa): ?>
                    <a href="../cliente/painel-cliente/chat.php?pedido=<?= (int) $pedido['id'] ?>" class="w-full bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider py-3 px-4 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-comments"></i> Abrir Conversa
                    </a>
                <?php else: ?>
                    <div class="bg-gray-50 text-gray-500 text-xs text-center p-3 uppercase tracking-wider">
                        Conversa indisponível
                    </div>
                <?php endif; ?>

                <?php if (!empty($pedido['notes'])): ?>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">As suas Observações</span>
                        <p class="text-xs text-gray-600 italic leading-relaxed">"<?= escapar($pedido['notes']) ?>"</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>