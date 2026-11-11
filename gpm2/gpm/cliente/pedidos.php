<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
$cliente=exigir_cliente();$cid=(int)$cliente['id'];
$st=$pdo->prepare("SELECT o.*, (SELECT p.proforma_number FROM proformas p WHERE p.order_id=o.id ORDER BY p.id DESC LIMIT 1) proforma_number FROM orders o WHERE o.customer_id=? ORDER BY o.created_at DESC,o.id DESC");$st->execute([$cid]);$pedidos=$st->fetchAll();
$tituloPagina='Os meus pedidos';require __DIR__.'/includes/header-marketplace.php';
?>
<div class="container section"><h1 class="page-title">Os meus pedidos</h1><?=cliente_flash_html()?><?php if(!$pedidos):?><div class="empty"><h2>Ainda não tens pedidos</h2><a class="btn-primary" href="index.php">Explorar produtos</a></div><?php else:?><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Data</th><th>Total</th><th>Estado</th><th>Proforma</th><th></th></tr></thead><tbody><?php foreach($pedidos as $p):?><tr><td><strong><?=e($p['order_number'])?></strong></td><td><?=e(date('d/m/Y',strtotime($p['created_at']??'now')))?></td><td><?=money($p['total'])?></td><td><span class="status <?=e($p['status']??'pending')?>"><?=e($p['status']??'Pendente')?></span></td><td><?=e($p['proforma_number']??'Por emitir')?></td><td><a class="btn-outline" href="pedido.php?id=<?=(int)$p['id']?>">Detalhes</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></div>
<?php require __DIR__.'/includes/footer-marketplace.php';?>
