<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller']);

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$stmt = $pdo->prepare(
    'SELECT o.*, c.nif FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$labels = [
    'pending' => 'Pendente',
    'confirmed' => 'Confirmado',
    'preparing' => 'Em preparação',
    'shipped' => 'Enviado',
    'completed' => 'Concluído',
    'cancelled' => 'Cancelado',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'confirm_quote') {
        if (($order['order_type'] ?? 'sale') !== 'quote_request' || ($order['request_status'] ?? '') !== 'location_received') {
            flash('A proposta precisa estar aceite e a localização recebida antes da confirmação.', 'error');
            redirect('order.php?id=' . $id);
        }
        try {
            $pdo->beginTransaction();
            $proposalStmt = $pdo->prepare("SELECT * FROM quote_proposals WHERE order_id = ? AND status = 'accepted' ORDER BY version DESC LIMIT 1 FOR UPDATE");
            $proposalStmt->execute([$id]);
            $acceptedProposal = $proposalStmt->fetch(PDO::FETCH_ASSOC);
            if (!$acceptedProposal) throw new RuntimeException('Não existe uma proposta aceite para este pedido.');

            $proposalItemsStmt = $pdo->prepare('SELECT request_item_id, approved_quantity, unit_price, subtotal FROM quote_proposal_items WHERE proposal_id = ?');
            $proposalItemsStmt->execute([(int)$acceptedProposal['id']]);
            $proposalItems = $proposalItemsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$proposalItems) throw new RuntimeException('A proposta aceite não contém produtos.');

            $updateItem = $pdo->prepare('UPDATE order_items SET quantity = ?, unit_price = ?, subtotal = ? WHERE id = ? AND order_id = ?');
            foreach ($proposalItems as $proposalItem) {
                $updateItem->execute([
                    $proposalItem['approved_quantity'], $proposalItem['unit_price'],
                    $proposalItem['subtotal'], $proposalItem['request_item_id'], $id
                ]);
            }

            $pdo->prepare("UPDATE orders SET order_type = 'sale', request_status = 'converted', status = 'confirmed', subtotal = ?, delivery_fee = ?, discount = ?, total = ?, confirmed_at = NOW() WHERE id = ?")
                ->execute([
                    $acceptedProposal['subtotal'], $acceptedProposal['delivery_fee'],
                    $acceptedProposal['discount'], $acceptedProposal['total'], $id
                ]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, user_id, old_status, new_status, note) VALUES (?, ?, 'pending', 'confirmed', ?)")
                ->execute([$id, (int)current_user()['id'], 'Cotação aceite pelo cliente, localização recebida e convertida em pedido confirmado.']);
            $pdo->commit();
            flash('Cotação convertida em pedido confirmado. O stock não foi alterado automaticamente.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[GPM quote conversion] ' . $e->getMessage());
            flash($e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível confirmar a cotação.', 'error');
        }
        redirect('order.php?id=' . $id);
    }

    $newStatus = (string)($_POST['status'] ?? '');
    $note = trim((string)($_POST['note'] ?? ''));

    if (!array_key_exists($newStatus, $labels)) {
        flash('Estado inválido.', 'error');
        redirect('order.php?id=' . $id);
    }

    try {
        $pdo->beginTransaction();

        $lock = $pdo->prepare('SELECT status, stock_deducted FROM orders WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        $lockedOrder = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$lockedOrder) {
            throw new RuntimeException('Pedido não encontrado.');
        }

        $oldStatus = (string)$lockedOrder['status'];
        $stockDeducted = (int)$lockedOrder['stock_deducted'] === 1;

        if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            throw new RuntimeException('Um pedido cancelado não pode ser reaberto automaticamente.');
        }

        if ($oldStatus !== $newStatus) {
            if ($newStatus === 'cancelled' && $stockDeducted) {
                $itemsStmt = $pdo->prepare(
                    'SELECT product_id, quantity, product_name
                     FROM order_items WHERE order_id = ?'
                );
                $itemsStmt->execute([$id]);

                foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    $productId = (int)($item['product_id'] ?? 0);
                    $quantity = (float)$item['quantity'];
                    if ($productId <= 0 || $quantity <= 0) {
                        continue;
                    }

                    $productStmt = $pdo->prepare(
                        'SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE'
                    );
                    $productStmt->execute([$productId]);
                    $beforeValue = $productStmt->fetchColumn();
                    if ($beforeValue === false) {
                        continue;
                    }

                    $before = (float)$beforeValue;
                    $after = $before + $quantity;
                    $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')
                        ->execute([$after, $productId]);
                    $pdo->prepare(
                        'INSERT INTO stock_movements
                         (product_id, user_id, order_id, movement_type, quantity, stock_before, stock_after, reason)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    )->execute([
                        $productId,
                        (int)current_user()['id'],
                        $id,
                        'sale_return',
                        $quantity,
                        $before,
                        $after,
                        'Reposição automática por cancelamento do pedido',
                    ]);
                }

                $pdo->prepare('UPDATE orders SET stock_deducted = 0 WHERE id = ?')->execute([$id]);
            }

            $pdo->prepare(
                'UPDATE orders
                 SET status = ?,
                     confirmed_at = IF(? = "confirmed", COALESCE(confirmed_at, NOW()), confirmed_at),
                     completed_at = IF(? = "completed", COALESCE(completed_at, NOW()), completed_at),
                     cancelled_at = IF(? = "cancelled", COALESCE(cancelled_at, NOW()), cancelled_at)
                 WHERE id = ?'
            )->execute([$newStatus, $newStatus, $newStatus, $newStatus, $id]);

            $pdo->prepare(
                'INSERT INTO order_status_history (order_id, user_id, old_status, new_status, note)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $id,
                (int)current_user()['id'],
                $oldStatus,
                $newStatus,
                $note !== '' ? $note : null,
            ]);
        }

        $pdo->commit();
        flash('Estado do pedido atualizado.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[GPM order] ' . $e->getMessage());
        flash($e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível atualizar o pedido.', 'error');
    }

    redirect('order.php?id=' . $id);
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT h.*, u.name AS user_name
     FROM order_status_history h
     LEFT JOIN users u ON u.id = h.user_id
     WHERE h.order_id = ? ORDER BY h.id DESC'
);
$stmt->execute([$id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

layout_start('Detalhes do pedido');
?>
<section class="panel">
    <div class="toolbar">
        <h2><?= e($order['order_number']) ?></h2>
        <a class="btn secondary" href="orders.php">Voltar aos pedidos</a><?php if (($order["order_type"] ?? "sale") === "quote_request"): ?><a class="btn" href="quote.php?id=<?= $id ?>">Preparar proposta comercial</a><?php endif; ?>
    </div>
    <div class="form-grid">
        <?php if (($order["order_type"] ?? "sale") === "quote_request"): ?><p><strong>Tipo:</strong> Pedido de cotação · <strong>Estado:</strong> <?= e($order["request_status"] ?: "submitted") ?></p><?php endif; ?><p><strong>Cliente:</strong> <?= e($order['customer_name']) ?></p>
        <p><strong>Tipo:</strong> <?= $order['customer_type'] === 'company' ? 'Empresa' : 'Particular' ?></p>
        <p><strong>NIF:</strong> <?= e($order['customer_nif'] ?: '—') ?></p>
        <p><strong>Telefone:</strong> <?= e($order['customer_phone']) ?></p>
        <p><strong>Email:</strong> <?= e($order['customer_email'] ?: '—') ?></p>
        <p><strong>Data:</strong> <?= e($order['ordered_at']) ?></p>
        <p><strong>Morada:</strong> <?= e($order['delivery_address'] ?: '—') ?></p>
        <p><strong>Província / Cidade:</strong> <?= e(trim(($order['delivery_province'] ?? '') . ' / ' . ($order['delivery_city'] ?? ''), ' /')) ?></p>
        <p><strong>Rua:</strong> <?= e($order['delivery_street'] ?: '—') ?></p>
        <p><strong>Bairro:</strong> <?= e($order['delivery_neighborhood'] ?: '—') ?></p>
        <p><strong>Referência:</strong> <?= e($order['delivery_reference'] ?: '—') ?></p>
        <p><strong>Pagamento:</strong> <?= e($order['payment_method'] ?: 'A combinar') ?> · <?= e($order['payment_status']) ?></p>
        <p><strong>Observações:</strong> <?= e($order['notes'] ?: '—') ?></p>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produto</th><th>Quantidade</th><?php if (($order["order_type"] ?? "sale") !== "quote_request"): ?><th>Preço unitário</th><th>Total</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['quantity']) ?> <?= e($item['unit']) ?></td><?php if (($order["order_type"] ?? "sale") !== "quote_request"): ?><td><?= e(money($item['unit_price'])) ?></td><td><?= e(money($item['subtotal'])) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="4" class="empty">Este pedido não tem linhas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($order["order_type"] ?? "sale") !== "quote_request"): ?><div style="text-align:right;margin-top:18px">
        <p>Subtotal: <strong><?= e(money($order['subtotal'])) ?></strong></p>
        <p>Desconto: <strong><?= e(money($order['discount'])) ?></strong></p>
        <p>Entrega: <strong><?= e(money($order['delivery_fee'])) ?></strong></p>
        <h2>Total: <?= e(money($order['total'])) ?></h2>
    </div><?php endif; ?>
</section>
<?php if (($order["order_type"] ?? "sale") !== "quote_request"): ?>
<section class="panel">
    <h2>Atualizar estado</h2>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="status">Estado</label>
                <select id="status" name="status">
                    <?php foreach ($labels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $order['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="note">Observação</label>
                <input id="note" name="note" maxlength="500">
            </div>
        </div>
        <button type="submit">Guardar estado</button>
    </form>
</section>
<?php else: ?>
<section class="panel">
    <h2>Pedido de cotação</h2>
    <p>Estado: <?= e($order['request_status'] ?: 'submitted') ?></p>
    <p><a class="btn" href="quote.php?id=<?= $id ?>">Abrir proposta comercial</a></p>
    <?php if (($order['request_status'] ?? '') === 'location_received'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="confirm_quote"><button type="submit">Confirmar pedido e converter em venda</button></form>
    <?php else: ?>
        <p>Para confirmar, o cliente precisa aceitar a proposta e enviar a localização através do link seguro.</p>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Histórico de estados</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Anterior</th><th>Novo</th><th>Responsável</th><th>Nota</th></tr></thead>
            <tbody>
            <?php foreach ($history as $entry): ?>
                <tr>
                    <td><?= e($entry['created_at']) ?></td>
                    <td><?= e($entry['old_status'] ?: '—') ?></td>
                    <td><?= e($labels[$entry['new_status']] ?? $entry['new_status']) ?></td>
                    <td><?= e($entry['user_name'] ?: '—') ?></td>
                    <td><?= e($entry['note'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$history): ?><tr><td colspan="5" class="empty">Sem histórico registado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>
