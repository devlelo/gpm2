<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/iniciar.php';
require_once __DIR__ . '/../includes/header.php';

$cliente = cliente_atual();
if (!$cliente) { $_SESSION['gpm_redirect_after_login'] = 'pedidos.php'; header('Location: login.php'); exit; }
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
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pedido <?= detalhe_e($pedido['order_number']) ?> | GPM</title><style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033}header{background:#fff;padding:18px max(20px,calc((100% - 1050px)/2));border-bottom:1px solid #e7eaf0;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}a{color:#172033;text-decoration:none;font-weight:700}.brand{font-size:21px}.brand span{color:#FEA619}nav{display:flex;gap:15px;flex-wrap:wrap}main{max-width:1050px;margin:30px auto;padding:0 20px}.grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}.card{background:white;border:1px solid #e7eaf0;border-radius:14px;padding:20px;margin-top:18px}h1{margin:0 0 7px}h2{font-size:18px;margin:0 0 15px}.muted{color:#697386;font-size:14px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px 6px;border-bottom:1px solid #e7eaf0;font-size:14px}th{color:#697386;font-size:12px}.btn{display:inline-block;background:#FEA619;padding:11px 14px;border-radius:8px;margin:5px 5px 0 0}.row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #f0f1f5}.address{white-space:pre-line;line-height:1.6}@media(max-width:750px){.grid{grid-template-columns:1fr}}
</style></head><body><header><a class="brand" href="painel.php">G<span>PM</span> · Área do Cliente</a><nav><a href="painel.php">Painel</a><a href="pedidos.php">Minhas compras</a><a href="dados.php">Meus dados</a><a href="logout.php">Sair</a></nav></header>
<main><h1>Pedido <?= detalhe_e($pedido['order_number']) ?></h1><div class="muted">Feito em <?= detalhe_e(date('d/m/Y H:i',strtotime((string)$pedido['ordered_at']))) ?> · Estado: <strong><?= detalhe_e($labels[$pedido['status']] ?? $pedido['status']) ?></strong></div>
<div class="actions"><a class="btn" href="chat.php?pedido=<?= (int)$pedido['id'] ?>">Abrir chat desta compra</a><a class="btn" href="pedidos.php">Voltar às compras</a></div>
<div class="grid"><section class="card"><h2>Produtos encomendados</h2><div style="overflow-x:auto"><table><thead><tr><th>Produto</th><th>Qtd.</th><th>Preço unitário</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach($itens as $item): ?><tr><td><?= detalhe_e($item['product_name']) ?><?php if($item['product_sku']): ?><div class="muted">Ref.: <?= detalhe_e($item['product_sku']) ?></div><?php endif; ?></td><td><?= detalhe_e($item['quantity']) ?> <?= detalhe_e($item['unit']) ?></td><td><?= detalhe_money($item['unit_price']) ?></td><td><?= detalhe_money($item['subtotal']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><div class="row"><span>Subtotal</span><strong><?= detalhe_money($pedido['subtotal']) ?></strong></div><div class="row"><span>Entrega</span><strong><?= detalhe_money($pedido['delivery_fee']) ?></strong></div><div class="row"><span>Desconto</span><strong><?= detalhe_money($pedido['discount']) ?></strong></div><div class="row"><strong>Total</strong><strong><?= detalhe_money($pedido['total']) ?></strong></div></section>
<aside><section class="card"><h2>Local de entrega</h2><div class="address"><?= detalhe_e($pedido['delivery_address'] ?: 'Endereço não indicado') ?><?php if($pedido['delivery_city']): ?>\n<?= detalhe_e($pedido['delivery_city']) ?><?php endif; ?><?php if($pedido['delivery_province']): ?>, <?= detalhe_e($pedido['delivery_province']) ?><?php endif; ?></div><p class="muted">Telefone: <?= detalhe_e($pedido['customer_phone']) ?></p></section>
<section class="card"><h2>Proforma</h2><?php if($proforma): ?><p><strong><?= detalhe_e($proforma['proforma_number']) ?></strong></p><p class="muted">Emitida em <?= detalhe_e(date('d/m/Y',strtotime((string)$proforma['issue_date']))) ?> · <?= detalhe_e($proforma['status']) ?></p><p>Total: <strong><?= detalhe_money($proforma['total']) ?></strong></p><?php else: ?><p class="muted">Ainda não existe proforma para este pedido.</p><?php endif; ?></section></aside></div></main></body></html>
<?php require __DIR__ . '/../includes/footer.php'; ?>