<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';
$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'pedidos.php'; header('Location: ../login.php'); exit; }
$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Pedido inválido.'); }
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ? LIMIT 1");
$stmt->execute([$id, (int)$cliente['id']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { http_response_code(404); exit('Pedido não encontrado.'); }
$stmt = $pdo->prepare("SELECT product_name, product_sku, quantity, unit, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id");
$stmt->execute([$id]); $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT proforma_number, status, issue_date, valid_until, total FROM proformas WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]); $proforma = $stmt->fetch(PDO::FETCH_ASSOC);
$labels = ['pending'=>'Pendente','confirmed'=>'Confirmado','preparing'=>'Em preparação','shipped'=>'Enviado','completed'=>'Concluído','cancelled'=>'Cancelado'];
function detalhe_money($v): string { return number_format((float)$v, 2, ',', '.') . ' Kz'; }
function detalhe_e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<?php $tituloPagina = 'Detalhes do pedido'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Pedido <?= detalhe_e($pedido['order_number']) ?></h1><div class="muted">Feito em <?= detalhe_e(date('d/m/Y H:i',strtotime((string)$pedido['ordered_at']))) ?> · Estado: <strong><?= detalhe_e($labels[$pedido['status']] ?? $pedido['status']) ?></strong></div>
<div class="actions"><a class="btn" href="chat.php?pedido=<?= (int)$pedido['id'] ?>">Abrir chat desta compra</a><a class="btn" href="pedidos.php">Voltar às compras</a></div>
<div class="grid"><section class="card"><h2>Produtos encomendados</h2><div style="overflow-x:auto"><table><thead><tr><th>Produto</th><th>Qtd.</th><th>Preço unitário</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach($itens as $item): ?><tr><td><?= detalhe_e($item['product_name']) ?><?php if($item['product_sku']): ?><div class="muted">Ref.: <?= detalhe_e($item['product_sku']) ?></div><?php endif; ?></td><td><?= detalhe_e($item['quantity']) ?> <?= detalhe_e($item['unit']) ?></td><td><?= detalhe_money($item['unit_price']) ?></td><td><?= detalhe_money($item['subtotal']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><div class="row"><span>Subtotal</span><strong><?= detalhe_money($pedido['subtotal']) ?></strong></div><div class="row"><span>Entrega</span><strong><?= detalhe_money($pedido['delivery_fee']) ?></strong></div><div class="row"><span>Desconto</span><strong><?= detalhe_money($pedido['discount']) ?></strong></div><div class="row"><strong>Total</strong><strong><?= detalhe_money($pedido['total']) ?></strong></div></section>
<aside><section class="card"><h2>Local de entrega</h2><div class="address"><?= detalhe_e($pedido['delivery_address'] ?: 'Endereço não indicado') ?><?php if($pedido['delivery_city']): ?>\n<?= detalhe_e($pedido['delivery_city']) ?><?php endif; ?><?php if($pedido['delivery_province']): ?>, <?= detalhe_e($pedido['delivery_province']) ?><?php endif; ?></div><p class="muted">Telefone: <?= detalhe_e($pedido['customer_phone']) ?></p></section>
<section class="card"><h2>Proforma</h2><?php if($proforma): ?><p><strong><?= detalhe_e($proforma['proforma_number']) ?></strong></p><p class="muted">Emitida em <?= detalhe_e(date('d/m/Y',strtotime((string)$proforma['issue_date']))) ?> · <?= detalhe_e($proforma['status']) ?></p><p>Total: <strong><?= detalhe_money($proforma['total']) ?></strong></p><?php else: ?><p class="muted">Ainda não existe proforma para este pedido.</p><?php endif; ?></section></aside></div><?php require __DIR__ . '/../includes/footer.php'; ?>
