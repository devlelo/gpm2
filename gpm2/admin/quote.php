<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller']);

$pdo = db();
$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND order_type = 'quote_request' LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Pedido de cotação não encontrado.');
}

$stmt = $pdo->prepare('SELECT id, product_name, quantity, unit FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $deliveryFee = filter_var($_POST['delivery_fee'] ?? '0', FILTER_VALIDATE_FLOAT);
        $discount = filter_var($_POST['discount'] ?? '0', FILTER_VALIDATE_FLOAT);
        $validUntil = trim((string)($_POST['valid_until'] ?? ''));
        $conditions = trim((string)($_POST['conditions'] ?? ''));
        if ($deliveryFee === false || $discount === false || $deliveryFee < 0 || $discount < 0) {
            throw new RuntimeException('Indica valores válidos para entrega e desconto.');
        }
        if ($validUntil !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
            throw new RuntimeException('A data de validade é inválida.');
        }
        if (strlen($conditions) > 5000) {
            throw new RuntimeException('As condições são demasiado longas.');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT proposal_version FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $version = (int)$stmt->fetchColumn() + 1;

        $proposalLines = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $quantity = filter_var($_POST['quantity'][$itemId] ?? $item['quantity'], FILTER_VALIDATE_FLOAT);
            $price = filter_var($_POST['price'][$itemId] ?? null, FILTER_VALIDATE_FLOAT);
            if ($quantity === false || $price === false || $quantity < 0 || $quantity > (float)$item['quantity'] || $price < 0) {
                throw new RuntimeException('Verifica as quantidades aprovadas e os preços de cada produto.');
            }
            if ($quantity <= 0) continue;
            $lineTotal = round($quantity * $price, 2);
            $subtotal += $lineTotal;
            $proposalLines[] = [
                'request_item_id' => $itemId,
                'name' => $item['product_name'],
                'unit' => $item['unit'],
                'requested' => (float)$item['quantity'],
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $lineTotal
            ];
        }
        if (!$proposalLines) {
            throw new RuntimeException('A proposta precisa de pelo menos um produto aprovado.');
        }
        $subtotal = round($subtotal, 2);
        if ($discount > $subtotal + $deliveryFee) {
            throw new RuntimeException('O desconto não pode ser superior ao valor da proposta.');
        }
        $total = round($subtotal + $deliveryFee - $discount, 2);

        $pdo->prepare("UPDATE quote_proposals SET status = 'superseded' WHERE order_id = ? AND status = 'sent'")
            ->execute([$orderId]);
        $stmt = $pdo->prepare(
            "INSERT INTO quote_proposals (order_id, version, status, subtotal, delivery_fee, discount, total, valid_until, conditions, sent_at, created_by)
             VALUES (?, ?, 'sent', ?, ?, ?, ?, ?, ?, NOW(), ?)"
        );
        $stmt->execute([
            $orderId, $version, $subtotal, $deliveryFee, $discount, $total,
            $validUntil !== '' ? $validUntil : null,
            $conditions !== '' ? $conditions : null,
            (int)current_user()['id']
        ]);
        $proposalId = (int)$pdo->lastInsertId();

        $stmtItem = $pdo->prepare(
            'INSERT INTO quote_proposal_items
             (proposal_id, request_item_id, product_name, unit, requested_quantity, approved_quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($proposalLines as $line) {
            $stmtItem->execute([
                $proposalId, $line['request_item_id'], $line['name'], $line['unit'],
                $line['requested'], $line['quantity'], $line['price'], $line['subtotal']
            ]);
        }
        $pdo->prepare("UPDATE orders SET proposal_version = ?, request_status = 'proposal_sent' WHERE id = ?")
            ->execute([$version, $orderId]);
        $pdo->commit();

        $base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
        $proposalUrl = $base . '/cliente/proposta.php?id=' . $orderId;
        $phone = preg_replace('/\D+/', '', (string)$order['customer_phone']);
        $message = 'Olá ' . $order['customer_name'] . '. A proposta comercial da GPM para o pedido ' . $order['order_number'] . ' está disponível aqui: ' . $proposalUrl;
        $whatsappUrl = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
        flash('Proposta comercial enviada. Partilha o link com o cliente pelo WhatsApp.', 'success');
        redirect('quote.php?id=' . $orderId . '&sent=1');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[GPM quote] ' . $e->getMessage());
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível guardar a proposta.';
    }
}

$stmt = $pdo->prepare('SELECT * FROM quote_proposals WHERE order_id = ? ORDER BY version DESC LIMIT 1');
$stmt->execute([$orderId]);
$latest = $stmt->fetch(PDO::FETCH_ASSOC);
$base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
$proposalUrl = $base . '/cliente/proposta.php?id=' . $orderId;
$phone = preg_replace('/\D+/', '', (string)$order['customer_phone']);
$whatsappUrl = 'https://wa.me/' . $phone . '?text=' . rawurlencode('Olá ' . $order['customer_name'] . '. A proposta comercial da GPM para o pedido ' . $order['order_number'] . ' está disponível aqui: ' . $proposalUrl);

layout_start('Preparar proposta comercial');
?>
<section class="panel">
    <div class="toolbar">
        <h2>Proposta para <?= e($order['order_number']) ?></h2>
        <a class="btn secondary" href="order.php?id=<?= $orderId ?>">Voltar ao pedido</a>
    </div>
    <div class="form-grid">
        <p><strong>Cliente:</strong> <?= e($order['customer_name']) ?></p>
        <p><strong>WhatsApp:</strong> <?= e($order['customer_phone']) ?></p>
        <p><strong>Email:</strong> <?= e($order['customer_email'] ?: '—') ?></p>
        <p><strong>Estado:</strong> <?= e($order['request_status'] ?: 'submitted') ?></p>
    </div>
    <?php if ($error !== ''): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['sent'])): ?><p class="alert success">Proposta guardada. Usa o botão WhatsApp para partilhar o link com o cliente.</p><?php endif; ?>
    <?php if ($latest): ?>
        <p><strong>Última versão:</strong> <?= (int)$latest['version'] ?> · <?= e($latest['status']) ?> · <?= e(money($latest['total'])) ?></p>
        <p><a class="btn secondary" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener">Enviar link pelo WhatsApp</a></p>
    <?php endif; ?>
</section>
<section class="panel">
    <h2>Compor proposta</h2>
    <form method="post">
        <?= csrf_field() ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Produto</th><th>Pedido</th><th>Quantidade aprovada</th><th>Preço unitário (Kz)</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?> <span class="muted"><?= e($item['unit']) ?></span></td>
                        <td><?= e($item['quantity']) ?></td>
                        <td><input type="number" name="quantity[<?= (int)$item['id'] ?>]" min="0" max="<?= e($item['quantity']) ?>" step="0.001" value="<?= e($item['quantity']) ?>" required></td>
                        <td><input type="number" name="price[<?= (int)$item['id'] ?>]" min="0" step="0.01" required></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-grid">
            <div class="field"><label for="delivery_fee">Taxa de entrega (Kz)</label><input id="delivery_fee" name="delivery_fee" type="number" min="0" step="0.01" value="0"></div>
            <div class="field"><label for="discount">Desconto (Kz)</label><input id="discount" name="discount" type="number" min="0" step="0.01" value="0"></div>
            <div class="field"><label for="valid_until">Válida até</label><input id="valid_until" name="valid_until" type="date"></div>
            <div class="field"><label for="conditions">Condições e observações</label><textarea id="conditions" name="conditions" rows="3"></textarea></div>
        </div>
        <button type="submit">Guardar e preparar proposta</button>
    </form>
</section>
<?php layout_end(); ?>
