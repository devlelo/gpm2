<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
require_once __DIR__.'/includes/funcoes-cliente.php';
if(!isset($_SESSION['gpm_cart'])||!is_array($_SESSION['gpm_cart']))$_SESSION['gpm_cart']=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$acao=$_POST['acao']??'';
 if($acao==='adicionar'){
  $id=(int)($_POST['produto_id']??0);$q=(int)($_POST['quantidade']??1);
  $st=$pdo->prepare("SELECT id,name,stock_quantity,active FROM products WHERE id=? LIMIT 1");$st->execute([$id]);$p=$st->fetch();
  if(!$p||!(int)$p['active'])flash('error','Produto indisponível.');
  elseif($q<1||$q>(int)$p['stock_quantity'])flash('error','Quantidade superior ao stock disponível.');
  else{$_SESSION['gpm_cart'][$id]=min((int)$p['stock_quantity'],(int)($_SESSION['gpm_cart'][$id]??0)+$q);flash('success','Produto adicionado ao carrinho.');}
 }elseif($acao==='atualizar'){
  foreach(($_POST['quantidades']??[]) as $id=>$q){$id=(int)$id;$q=(int)$q;if($id<1)continue;$st=$pdo->prepare("SELECT stock_quantity,active FROM products WHERE id=?");$st->execute([$id]);$p=$st->fetch();if(!$p||!(int)$p['active']||$q<1)unset($_SESSION['gpm_cart'][$id]);else $_SESSION['gpm_cart'][$id]=min($q,(int)$p['stock_quantity']);}
  flash('success','Carrinho atualizado.');
 }elseif($acao==='remover'){unset($_SESSION['gpm_cart'][(int)($_POST['produto_id']??0)]);flash('success','Produto removido.');}
 redirect('carrinho.php');
}
$items=[];$subtotal=0;
foreach($_SESSION['gpm_cart'] as $id=>$q){$st=$pdo->prepare("SELECT id,name,price,stock_quantity,unit,main_image,active FROM products WHERE id=?");$st->execute([(int)$id]);$p=$st->fetch();if(!$p||!(int)$p['active']||(int)$p['stock_quantity']<1){unset($_SESSION['gpm_cart'][$id]);continue;}$q=min((int)$q,(int)$p['stock_quantity']);$_SESSION['gpm_cart'][$id]=$q;$p['quantity']=$q;$p['line_total']=$q*(float)$p['price'];$subtotal+=$p['line_total'];$items[]=$p;}
$tituloPagina='Carrinho';require __DIR__.'/includes/header-marketplace.php';
?>
<div class="container section"><h1 class="page-title">O teu carrinho</h1><?=cliente_flash_html()?><?php if(!$items):?><div class="empty"><h2>O carrinho está vazio</h2><p>Explora o marketplace e adiciona os produtos que precisas.</p><a class="btn-primary" href="index.php">Ver produtos</a></div><?php else:?><div class="two-col"><form method="post" class="table-wrap"><?=csrf_field()?><input type="hidden" name="acao" value="atualizar"><table><thead><tr><th>Produto</th><th>Preço</th><th>Quantidade</th><th>Subtotal</th><th></th></tr></thead><tbody><?php foreach($items as $p):?><tr><td><div style="display:flex;align-items:center;gap:12px"><img src="<?=e(gpm_product_image($p['main_image']??''))?>" style="width:65px;height:55px;object-fit:contain;background:#f4f6f8" alt=""><strong><?=e($p['name'])?></strong></div></td><td><?=money($p['price'])?></td><td><input class="qty" type="number" min="0" max="<?=(int)$p['stock_quantity']?>" name="quantidades[<?=(int)$p['id']?>]" value="<?=(int)$p['quantity']?>"></td><td><strong><?=money($p['line_total'])?></strong></td><td><button class="btn-outline" type="submit" form="remover-<?=(int)$p['id']?>">Remover</button></td></tr><?php endforeach;?></tbody></table><div style="padding:12px"><button class="btn-outline" type="submit">Atualizar quantidades</button></div></form><aside class="summary"><h3>Resumo</h3><div class="summary-row"><span>Subtotal</span><strong><?=money($subtotal)?></strong></div><div class="summary-row"><span>Entrega</span><span>A combinar</span></div><div class="summary-row summary-total"><span>Total estimado</span><strong><?=money($subtotal)?></strong></div><p class="muted" style="font-size:12px">O valor de entrega será confirmado pela equipa GPM.</p><a class="btn-primary full" href="finalizar-pedido.php">Continuar para checkout</a></aside></div><?php foreach($items as $p):?><form id="remover-<?=(int)$p['id']?>" method="post" style="display:none"><?=csrf_field()?><input name="acao" value="remover"><input name="produto_id" value="<?=(int)$p['id']?>"></form><?php endforeach;?><?php endif;?></div>
<?php require __DIR__.'/includes/footer-marketplace.php';?>
