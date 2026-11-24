<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/iniciar.php';
exigir_cliente();

$pdo = db();
$clienteId = (int)cliente_atual()['id'];
$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ? AND order_type = 'quote_request' LIMIT 1");
$stmt->execute([$orderId, $clienteId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Pedido de cotação não encontrado.');
}

$stmt = $pdo->prepare("SELECT * FROM quote_proposals WHERE order_id = ? AND status IN ('sent','accepted') ORDER BY version DESC LIMIT 1");
$stmt->execute([$orderId]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$proposal) {
    flash('error', 'A proposta ainda está a ser preparada pela equipa GPM.');
    redirect('pedido.php?id=' . $orderId);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM quote_proposal_items WHERE proposal_id = ? ORDER BY id');
$stmt->execute([(int)$proposal['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = '';
$deliveryLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if (!in_array($action, ['accept','reject'], true)) {
        $error = 'Ação inválida.';
    } elseif ($proposal['status'] !== 'sent') {
        $error = 'Esta proposta já recebeu uma resposta.';
    } else {
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE quote_proposals SET status = ?, responded_at = NOW() WHERE id = ? AND status = \'sent\'')
                ->execute([$newStatus, (int)$proposal['id']]);
            $pdo->prepare('UPDATE orders SET request_status = ? WHERE id = ?')
                ->execute([$newStatus === 'accepted' ? 'customer_accepted' : 'customer_rejected', $orderId]);

            if ($newStatus === 'accepted') {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);
                $pdo->prepare('UPDATE delivery_location_tokens SET revoked_at = NOW() WHERE order_id = ? AND used_at IS NULL')
                    ->execute([$orderId]);
                $pdo->prepare('INSERT INTO delivery_location_tokens (order_id, customer_id, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))')
                    ->execute([$orderId, $clienteId, $hash]);
                $deliveryLink = 'localizacao.php?token=' . rawurlencode($token);
                $_SESSION['gpm_delivery_link_' . $orderId] = $deliveryLink;
            }
            $pdo->commit();
            flash($newStatus === 'accepted' ? 'Proposta aceite. Envia agora a localização de entrega.' : 'Proposta recusada.', 'success');
            redirect('proposta.php?id=' . $orderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[GPM proposal response] ' . $e->getMessage());
            $error = 'Não foi possível registar a tua resposta.';
        }
    }
}

$deliveryLink = (string)($_SESSION['gpm_delivery_link_' . $orderId] ?? '');
require_once __DIR__ . '/includes/header-marketplace.php';
?>
<main class="max-w-site mx-auto px-4 sm:px-6 py-12 flex-1 relative w-full">
    <div class="mb-8"><a href="pedido.php?id=<?= $orderId ?>" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#f96b21] transition"><i class="fa-solid fa-arrow-left"></i> Voltar ao pedido</a></div>
    <div class="mb-10">
        <span class="inline-block bg-[#f96b21]/10 text-[#f96b21] text-[10px] font-bold uppercase tracking-widest px-3 py-1 mb-3">Proposta comercial · Versão <?= (int)$proposal['version'] ?></span>
        <h1 class="text-3xl md:text-5xl font-black text-[#0d233f] uppercase tracking-tight"><?= escapar($order['order_number']) ?></h1>
        <p class="text-gray-500 text-sm mt-2">Analisa os produtos, quantidades e condições apresentados pela GPM.</p>
    </div>
    <?php if ($error !== ''): ?><div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm"><?= escapar($error) ?></div><?php endif; ?>
    <section class="bg-white border border-gray-100 shadow-sm">
        <div class="divide-y divide-gray-100">
            <?php foreach ($items as $item): ?>
                <div class="p-5 flex flex-col sm:flex-row justify-between gap-3">
                    <div><strong class="text-[#0d233f]"><?= escapar($item['product_name']) ?></strong><span class="block text-xs text-gray-500 mt-1">Solicitado: <?= escapar($item['requested_quantity']) ?> · Aprovado: <?= escapar($item['approved_quantity']) ?> <?= escapar($item['unit']) ?></span></div>
                    <div class="sm:text-right"><span class="block text-xs text-gray-500"><?= escapar(money($item['unit_price'])) ?> / unidade</span><strong class="text-lg text-[#f96b21]"><?= escapar(money($item['subtotal'])) ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="p-6 bg-[#f8f9fa]">
            <div class="max-w-sm ml-auto space-y-2 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><strong><?= escapar(money($proposal['subtotal'])) ?></strong></div>
                <div class="flex justify-between"><span>Entrega</span><strong><?= escapar(money($proposal['delivery_fee'])) ?></strong></div>
                <div class="flex justify-between"><span>Desconto</span><strong><?= escapar(money($proposal['discount'])) ?></strong></div>
                <div class="flex justify-between border-t border-gray-200 pt-3 text-lg"><strong>Total</strong><strong class="text-[#0d233f]"><?= escapar(money($proposal['total'])) ?></strong></div>
                <?php if ($proposal['valid_until']): ?><p class="text-xs text-gray-500">Válida até <?= date('d/m/Y', strtotime($proposal['valid_until'])) ?></p><?php endif; ?>
            </div>
            <?php if ($proposal['conditions']): ?><p class="mt-5 text-sm text-gray-600"><?= nl2br(escapar($proposal['conditions'])) ?></p><?php endif; ?>
        </div>
    </section>
    <?php if ($proposal['status'] === 'sent'): ?>
        <section class="mt-6 flex flex-col sm:flex-row gap-3">
            <form method="post" class="flex-1"><?= csrf_field() ?><input type="hidden" name="action" value="accept"><button class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-4">Aceitar proposta</button></form>
            <form method="post" class="flex-1" onsubmit="return confirm('Tens a certeza de que queres recusar esta proposta?');"><?= csrf_field() ?><input type="hidden" name="action" value="reject"><button class="w-full bg-white border border-gray-300 text-[#0d233f] font-bold text-xs uppercase tracking-wider px-8 py-4">Recusar proposta</button></form>
        </section>
    <?php elseif ($proposal['status'] === 'accepted'): ?>
        <section class="mt-6 bg-[#f8f9fa] border-l-4 border-[#f96b21] p-6">
            <h2 class="font-black text-[#0d233f] uppercase mb-2">Proposta aceite</h2>
            <p class="text-sm text-gray-600 mb-4">Agora envia a localização onde pretendes receber os produtos através do link seguro abaixo.</p>
            <?php if ($deliveryLink): ?><a class="inline-block bg-[#0d233f] text-white font-bold text-xs uppercase tracking-wider px-6 py-3" href="<?= escapar($deliveryLink) ?>">Indicar localização de entrega</a><?php else: ?><a class="inline-block bg-[#0d233f] text-white font-bold text-xs uppercase tracking-wider px-6 py-3" href="pedido.php?id=<?= $orderId ?>">Continuar</a><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer-marketplace.php'; ?>
</body>
</html>
