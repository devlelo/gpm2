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
        <a class="btn secondary" href="orders.php">Voltar aos pedidos</a>
    </div>
    <div class="form-grid">
        <p><strong>Cliente:</strong> <?= e($order['customer_name']) ?></p>
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
            <thead><tr><th>Produto</th><th>Quantidade</th><th>Preço unitário</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['quantity']) ?> <?= e($item['unit']) ?></td>
                    <td><?= e(money($item['unit_price'])) ?></td>
                    <td><?= e(money($item['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="4" class="empty">Este pedido não tem linhas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align:right;margin-top:18px">
        <p>Subtotal: <strong><?= e(money($order['subtotal'])) ?></strong></p>
        <p>Desconto: <strong><?= e(money($order['discount'])) ?></strong></p>
        <p>Entrega: <strong><?= e(money($order['delivery_fee'])) ?></strong></p>
        <h2>Total: <?= e(money($order['total'])) ?></h2>
    </div>
</section>
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
