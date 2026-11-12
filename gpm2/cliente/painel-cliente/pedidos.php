<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';

$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/pedidos.php'; header('Location: ../login.php'); exit; }
$pdo = db();
$stmt = $pdo->prepare("SELECT id, order_number, total, status, ordered_at FROM orders WHERE customer_id = ? ORDER BY ordered_at DESC, id DESC");
$stmt->execute([(int)$cliente['id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = ['pending'=>'Pendente','confirmed'=>'Confirmado','preparing'=>'Em preparação','shipped'=>'Enviado','completed'=>'Concluído','cancelled'=>'Cancelado'];
function pedidos_money($v): string { return number_format((float)$v, 2, ',', '.') . ' Kz'; }
?>
<?php $tituloPagina = 'Minhas compras'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Minhas compras</h1><div class="muted">Aqui encontras todas as encomendas feitas com a tua conta.</div><div class="card">
<?php if (!$pedidos): ?><div class="empty">Ainda não tens encomendas. <a href="../index.php">Ver produtos</a></div>
<?php else: ?><table><thead><tr><th>Número</th><th>Data</th><th>Total</th><th>Estado</th><th>Consultar</th></tr></thead><tbody>
<?php foreach($pedidos as $p): ?><tr><td><?= htmlspecialchars((string)$p['order_number'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars(date('d/m/Y H:i',strtotime((string)$p['ordered_at'])),ENT_QUOTES,'UTF-8') ?></td><td><?= pedidos_money($p['total']) ?></td><td><span class="badge"><?= htmlspecialchars($labels[$p['status']] ?? $p['status'],ENT_QUOTES,'UTF-8') ?></span></td><td><a class="btn" href="../pedido.php?id=<?= (int)$p['id'] ?>">Detalhes</a><a class="btn" href="../chat.php?pedido=<?= (int)$p['id'] ?>">Chat</a></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div><?php require __DIR__ . '/../includes/footer.php'; ?>
