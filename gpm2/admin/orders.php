<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller']);

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$allowed = ['pending', 'confirmed', 'preparing', 'shipped', 'completed', 'cancelled'];
$sql = 'SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.total, o.status, o.created_at, c.nif
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE 1 = 1';
$args = [];
if ($q !== '') {
    $sql .= ' AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR c.nif LIKE ?)';
    $term = '%' . $q . '%';
    $args = [$term, $term, $term, $term];
}
if (in_array($status, $allowed, true)) {
    $sql .= ' AND o.status = ?';
    $args[] = $status;
}
$sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = ['pending'=>'Pendente','confirmed'=>'Confirmado','preparing'=>'Em preparação','shipped'=>'Enviado','completed'=>'Concluído','cancelled'=>'Cancelado'];

layout_start('Pedidos');
?>
<section class="panel">
    <div class="toolbar"><h2>Pedidos registados</h2><form method="get"><input name="q" placeholder="Número, cliente, telefone ou NIF" value="<?= e($q) ?>" style="min-width:220px"><select name="status"><option value="">Todos os estados</option><?php foreach ($labels as $key=>$label): ?><option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><button type="submit">Filtrar</button></form></div>
    <div class="table-wrap"><table><thead><tr><th>Número</th><th>Cliente / NIF</th><th>Telefone</th><th>Total</th><th>Estado</th><th>Data</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">Nenhum pedido encontrado.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['order_number']) ?></td><td><?= e($row['customer_name']) ?><br><span class="muted">NIF: <?= e($row['nif'] ?: '—') ?></span></td><td><?= e($row['customer_phone']) ?></td><td><?= e(money($row['total'])) ?></td><td><span class="badge"><?= e($labels[$row['status']] ?? $row['status']) ?></span></td><td><?= e($row['created_at']) ?></td><td><a class="btn small secondary" href="order.php?id=<?= (int)$row['id'] ?>">Detalhes</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php layout_end(); ?>
