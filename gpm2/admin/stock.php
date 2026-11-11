<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'stock_manager']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $type = (string)($_POST['movement_type'] ?? '');
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($productId <= 0 || !is_finite($quantity) || $quantity < 0 || ($type !== 'adjustment' && $quantity <= 0) || !in_array($type, ['entry', 'exit', 'adjustment'], true)) {
            throw new RuntimeException('Confirma o produto, o tipo e a quantidade.');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new RuntimeException('Produto não encontrado.');
        }

        $before = (float)$product['stock_quantity'];
        if ($type === 'adjustment') {
            $after = $quantity;
            $movementType = $after >= $before ? 'adjustment_in' : 'adjustment_out';
            $delta = abs($after - $before);
        } elseif ($type === 'entry') {
            $after = $before + $quantity;
            $movementType = 'entry';
            $delta = $quantity;
        } else {
            $after = $before - $quantity;
            $movementType = 'exit';
            $delta = $quantity;
        }

        if ($after < 0) {
            throw new RuntimeException('Stock insuficiente para esta saída.');
        }

        if ($delta > 0) {
            $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')
                ->execute([$after, $productId]);
            $pdo->prepare(
                'INSERT INTO stock_movements
                 (product_id, user_id, movement_type, quantity, stock_before, stock_after, reason)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $productId,
                (int)current_user()['id'],
                $movementType,
                $delta,
                $before,
                $after,
                $reason !== '' ? $reason : null,
            ]);
        }

        $pdo->commit();
        flash('Movimento de stock registado.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[GPM stock] ' . $e->getMessage());
        flash($e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível registar o movimento.', 'error');
    }
    redirect('stock.php');
}

$products = $pdo->query(
    'SELECT id, name, stock_quantity, unit FROM products WHERE active = 1 ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

$rows = $pdo->query(
    'SELECT sm.*, p.name AS product_name, u.name AS user_name
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     LEFT JOIN users u ON u.id = sm.user_id
     ORDER BY sm.id DESC LIMIT 100'
)->fetchAll(PDO::FETCH_ASSOC);

$movementLabels = [
    'entry' => 'Entrada',
    'exit' => 'Saída',
    'sale' => 'Venda',
    'sale_return' => 'Devolução de venda',
    'adjustment_in' => 'Ajuste positivo',
    'adjustment_out' => 'Ajuste negativo',
];

layout_start('Movimentos de stock');
?>
<section class="panel">
    <h2>Registar movimento</h2>
    <form method="post"><?= csrf_field() ?>
        <div class="form-grid">
            <div class="field">
                <label for="product_id">Produto</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Selecionar produto</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>"><?= e($product['name']) ?> — stock: <?= e($product['stock_quantity']) ?> <?= e($product['unit']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="movement_type">Tipo</label>
                <select id="movement_type" name="movement_type" required>
                    <option value="entry">Entrada</option>
                    <option value="exit">Saída</option>
                    <option value="adjustment">Ajustar para a quantidade indicada</option>
                </select>
            </div>
            <div class="field">
                <label for="quantity">Quantidade</label>
                <input id="quantity" name="quantity" type="number" min="0" step="0.001" required>
            </div>
            <div class="field">
                <label for="reason">Motivo / observação</label>
                <input id="reason" name="reason" maxlength="255" placeholder="Ex.: compra ao fornecedor">
            </div>
        </div>
        <button type="submit">Registar movimento</button>
    </form>
</section>
<section class="panel">
    <h2>Últimos 100 movimentos</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Quantidade</th><th>Stock antes</th><th>Stock depois</th><th>Responsável</th><th>Motivo</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="8" class="empty">Sem movimentos registados.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['product_name']) ?></td>
                    <td><span class="badge"><?= e($movementLabels[$row['movement_type']] ?? $row['movement_type']) ?></span></td>
                    <td><?= e($row['quantity']) ?></td>
                    <td><?= e($row['stock_before']) ?></td>
                    <td><?= e($row['stock_after']) ?></td>
                    <td><?= e($row['user_name'] ?: '—') ?></td>
                    <td><?= e($row['reason'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>
