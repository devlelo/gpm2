<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin', 'seller', 'stock_manager']);

$pdo = db();
$stats = [];
$low = 0;
$recent = [];
$dbError = null;

try {
    $stats = [
        'Produtos' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn(),
        'Clientes' => (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
        'Pedidos' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'Pendentes' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    ];
    $low = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock_quantity <= min_stock')->fetchColumn();
    $recent = $pdo->query(
        'SELECT id, order_number, customer_name, total, status, created_at
         FROM orders ORDER BY created_at DESC, id DESC LIMIT 8'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[GPM dashboard] ' . $e->getMessage());
    $dbError = 'Não foi possível carregar os indicadores. Verifica a ligação e a estrutura da base de dados.';
}

layout_start('Visão geral');
if ($dbError !== null) {
    echo '<div class="alert error" role="alert">' . e($dbError) . '</div>';
}
echo '<section class="grid">';
foreach ($stats as $label => $value) {
    echo '<article class="stat"><span>' . e($label) . '</span><strong>' . number_format($value, 0, ',', '.') . '</strong></article>';
}
echo '<article class="stat"><span>Produtos com stock baixo</span><strong>' . number_format($low, 0, ',', '.') . '</strong></article>';
echo '</section>';
echo '<section class="panel"><div class="toolbar"><h2>Pedidos recentes</h2><a class="btn" href="orders.php">Ver todos</a></div>';
echo '<div class="table-wrap"><table><thead><tr><th>Número</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Data</th><th></th></tr></thead><tbody>';
if (!$recent) {
    echo '<tr><td colspan="6" class="empty">Ainda não existem pedidos.</td></tr>';
}
foreach ($recent as $row) {
    echo '<tr><td>' . e($row['order_number']) . '</td><td>' . e($row['customer_name']) . '</td><td>' . e(money($row['total'])) . '</td><td><span class="badge">' . e($row['status']) . '</span></td><td>' . e($row['created_at']) . '</td><td><a class="btn small secondary" href="order.php?id=' . (int)$row['id'] . '">Abrir</a></td></tr>';
}
echo '</tbody></table></div></section>';
layout_end();
