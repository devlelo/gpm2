<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';


$cliente = cliente_atual();
if (!$cliente) {
    $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/index.php';
    header('Location: ../login.php');
    exit;
}
$pdo = db();
$clienteId = (int)$cliente['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$stmt->execute([$clienteId]);
$totalPedidos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN ('pending','confirmed','preparing','shipped')");
$stmt->execute([$clienteId]);
$pedidosAtivos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, order_number, total, status, ordered_at FROM orders WHERE customer_id = ? ORDER BY ordered_at DESC, id DESC LIMIT 5");
$stmt->execute([$clienteId]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function estado_pedido_cliente(string $status): string {
    $estados = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'preparing' => 'Em preparação',
        'shipped' => 'Enviado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado'
    ];
    return $estados[$status] ?? $status;
}
function dinheiro_cliente($valor): string {
    return number_format((float)$valor, 2, ',', '.') . ' Kz';
}
?>
<?php $tituloPagina = 'Painel do Cliente'; require __DIR__ . '/../includes/header.php'; ?>

<div class="welcome">
<div><h1>Olá, <?= htmlspecialchars((string)($cliente['name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8') ?>!</h1><div class="muted">Acompanha as tuas compras e fala com a equipa GPM.</div></div>
<div class="actions"><a class="btn" href="../index.php">Continuar a comprar</a><a class="btn alt" href="dados.php">Editar os meus dados</a></div>
</div>
<div class="cards">
<div class="card"><div class="muted">Total de compras</div><div class="metric"><?= $totalPedidos ?></div></div>
<div class="card"><div class="muted">Compras em andamento</div><div class="metric"><?= $pedidosAtivos ?></div></div>
<div class="card"><div class="muted">Conta</div><div class="metric" style="font-size:20px">Ativa</div><div class="muted" style="margin-top:8px"><?= htmlspecialchars((string)($cliente['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
</div>
<section class="card">
<div class="section-title"><h2>Compras recentes</h2><a class="link" href="pedidos.php">Ver todas →</a></div>
<?php if (!$pedidos): ?>
<div class="empty">Ainda não tens compras. <a href="../index.php">Explora os produtos</a> e faz a tua primeira encomenda.</div>
<?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Data</th><th>Total</th><th>Estado</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($pedidos as $p): ?>
<tr>
<td><strong><?= htmlspecialchars((string)$p['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$p['ordered_at'])), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= dinheiro_cliente($p['total']) ?></td>
<td><span class="badge"><?= htmlspecialchars(estado_pedido_cliente((string)$p['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
<td><a class="link" href="pedido.php?id=<?= (int)$p['id'] ?>">Detalhes</a> · <a class="link" href="chat.php?pedido=<?= (int)$p['id'] ?>">Chat</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
