<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller']);

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT c.id, c.customer_type, c.name, c.nif, c.phone, c.email, c.active,
               COUNT(o.id) AS orders_count
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id';
$args = [];
if ($q !== '') {
    $sql .= ' WHERE c.name LIKE ? OR c.nif LIKE ? OR c.phone LIKE ? OR c.email LIKE ?';
    $term = '%' . $q . '%';
    $args = [$term, $term, $term, $term];
}
$sql .= ' GROUP BY c.id, c.customer_type, c.name, c.nif, c.phone, c.email, c.active
          ORDER BY c.id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

layout_start('Clientes');
?>
<section class="panel">
    <div class="toolbar">
        <h2>Clientes registados</h2>
        <form method="get">
            <input name="q" value="<?= e($q) ?>" placeholder="Nome, NIF, telefone ou email" style="min-width:250px">
            <button type="submit">Pesquisar</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cliente</th><th>Tipo</th><th>NIF</th><th>Telefone</th><th>Email</th><th>Pedidos</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="7" class="empty">Nenhum cliente encontrado.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= $row['customer_type'] === 'company' ? 'Empresa' : 'Particular' ?></td>
                    <td><?= e($row['nif'] ?: '—') ?></td>
                    <td><?= e($row['phone']) ?></td>
                    <td><?= e($row['email'] ?: '—') ?></td>
                    <td><?= (int)$row['orders_count'] ?></td>
                    <td><span class="badge"><?= (int)$row['active'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>
