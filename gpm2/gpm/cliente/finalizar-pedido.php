<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
require_once __DIR__.'/includes/funcoes-cliente.php';
$cart=$_SESSION['gpm_cart']??[];
if(!$cart){flash('error','O carrinho está vazio.');redirect('carrinho.php');}
if(!cliente_atual()){
 $_SESSION['gpm_after_login']='finalizar-pedido.php';
 flash('success','Entra ou cria uma conta para concluir a compra. O carrinho será mantido.');
 redirect('login.php');
}
$cid=(int)cliente_atual()['id'];
$st=$pdo->prepare("SELECT id,customer_type,name,nif,phone,email,address,city,province,active FROM customers WHERE id=? LIMIT 1");$st->execute([$cid]);$cliente=$st->fetch();
if(!$cliente||!(int)$cliente['active']){unset($_SESSION['gpm_customer']);redirect('login.php');}
$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$morada=trim($_POST['delivery_address']??'');$cidade=trim($_POST['delivery_city']??'');$provincia=trim($_POST['delivery_province']??'');$telefone=trim($_POST['customer_phone']??'');$metodo=trim($_POST['payment_method']??'');$notas=trim($_POST['notes']??'');
 if($morada===''||$telefone==='')$erro='A morada de entrega e o telefone são obrigatórios.';
 else try{
  $pdo->beginTransaction();$linhas=[];$subtotal=0.0;
  foreach($cart as $pid=>$qty){$pid=(int)$pid;$qty=(int)$qty;if($pid<1||$qty<1)throw new RuntimeException('Item inválido no carrinho.');$s=$pdo->prepare("SELECT id,name,sku,price,stock_quantity,unit,active FROM products WHERE id=? FOR UPDATE");$s->execute([$pid]);$p=$s->fetch();if(!$p||!(int)$p['active'])throw new RuntimeException('Um produto deixou de estar disponível.');if($qty>(float)$p['stock_quantity'])throw new RuntimeException('Stock insuficiente para '.$p['name'].'.');$line=$qty*(float)$p['price'];$subtotal+=$line;$linhas[]=['id'=>$pid,'name'=>$p['name'],'sku'=>$p['sku']??null,'unit'=>$p['unit']??'unidade','quantity'=>$qty,'price'=>(float)$p['price'],'subtotal'=>$line];}
  if(!$linhas)throw new RuntimeException('Carrinho sem produtos válidos.');
  $orderNo='GPM-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));$total=$subtotal;
  $s=$pdo->prepare("INSERT INTO orders (order_number,customer_id,responsible_user_id,customer_type,customer_name,customer_nif,customer_phone,customer_email,delivery_address,delivery_city,delivery_province,delivery_fee,discount,subtotal,total,payment_method,notes,ordered_at,created_at,updated_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, NOW(), NOW(), NOW())");
  $s->execute([$orderNo,$cid,$cliente['customer_type'],$cliente['name'],$cliente['nif'],$telefone,$cliente['email'],$morada,$cidade?:null,$provincia?:null,$subtotal,$total,$metodo?:null,$notas?:null]);$orderId=(int)$pdo->lastInsertId();
  $item=$pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,product_sku,quantity,unit,unit_price,subtotal,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
  foreach($linhas as $l)$item->execute([$orderId,$l['id'],$l['name'],$l['sku'],$l['quantity'],$l['unit'],$l['price'],$l['subtotal']]);
  $pfNo='PF-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
  $pf=$pdo->prepare("INSERT INTO proformas (order_id,proforma_number,customer_name,customer_nif,customer_phone,customer_email,customer_address,subtotal,delivery_fee,discount,total,status,issue_date,notes,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,0,0,?,'draft',CURDATE(),?,NULL,NOW(),NOW())");
  $pf->execute([$orderId,$pfNo,$cliente['name'],$cliente['nif'],$telefone,$cliente['email'],$morada,$subtotal,$total,'Proforma criada a partir do pedido '.$orderNo]);
  $cv=$pdo->prepare("INSERT INTO conversations (order_id,customer_id,assigned_user_id,status,last_message_at,created_at,updated_at) VALUES (?,?,NULL,'open',NOW(),NOW(),NOW())");$cv->execute([$orderId,$cid]);$conversationId=(int)$pdo->lastInsertId();
  $msg=$pdo->prepare("INSERT INTO messages (conversation_id,sender_type,customer_sender_id,user_sender_id,body,created_at) VALUES (?,'customer',?,NULL,?,NOW())");$msg->execute([$conversationId,$cid,'Olá. Fiz o pedido '.$orderNo.' e gostaria de acompanhar o seu estado.']);
  $pdo->commit();unset($_SESSION['gpm_cart']);flash('success','Pedido enviado. A equipa GPM irá analisar e contactar-te.');redirect('pedido.php?id='.$orderId);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('GPM checkout: '.$e->getMessage());$erro=$e instanceof RuntimeException?$e->getMessage():'Não foi possível concluir o pedido. Verifica os registos do servidor.';}
}
$subtotal=0;foreach($cart as $pid=>$q){$s=$pdo->prepare("SELECT price FROM products WHERE id=?");$s->execute([(int)$pid]);$subtotal+=(float)$s->fetchColumn()*(int)$q;}
$tituloPagina='Finalizar pedido';require __DIR__.'/includes/header-marketplace.php';
?>
<div class="container"><h1 class="page-title">Finalizar pedido</h1><?=cliente_flash_html()?><?php if($erro):?><div class="alert error"><?=e($erro)?></div><?php endif;?><div class="two-col"><form class="form-panel" method="post" style="margin:0;max-width:none"><?=csrf_field()?><h2>Dados de entrega</h2><p><strong><?=e($cliente['name'])?></strong> · <?=e($cliente['email'])?></p><div class="field"><label>Telefone de contacto *</label><input name="customer_phone" required value="<?=e($_POST['customer_phone']??$cliente['phone'])?>"></div><div class="field"><label>Morada de entrega *</label><textarea name="delivery_address" rows="3" required><?=e($_POST['delivery_address']??$cliente['address'])?></textarea></div><div class="form-grid"><div class="field"><label>Cidade</label><input name="delivery_city" value="<?=e($_POST['delivery_city']??$cliente['city'])?>"></div><div class="field"><label>Província</label><input name="delivery_province" value="<?=e($_POST['delivery_province']??$cliente['province'])?>"></div></div><div class="field"><label>Pagamento preferido</label><select name="payment_method"><option value="">A combinar</option><option value="transferencia">Transferência bancária</option><option value="dinheiro">Dinheiro</option><option value="multicaixa">Multicaixa Express</option></select></div><div class="field"><label>Observações</label><textarea name="notes" rows="3"><?=e($_POST['notes']??'')?></textarea></div><button class="btn-primary full" type="submit">Confirmar pedido</button></form><aside class="summary"><h3>Resumo da compra</h3><?php foreach($cart as $pid=>$q):$s=$pdo->prepare("SELECT name,price FROM products WHERE id=?");$s->execute([(int)$pid]);$p=$s->fetch();if($p):?><div class="summary-row"><span><?=e($p['name'])?> × <?= (int)$q ?></span><strong><?=money((float)$p['price']*(int)$q)?></strong></div><?php endif;endforeach;?><div class="summary-row summary-total"><span>Total estimado</span><strong><?=money($subtotal)?></strong></div><p class="muted" style="font-size:12px">O custo de entrega será combinado. O pedido fica pendente até validação comercial.</p></aside></div></div>
<?php require __DIR__.'/includes/footer-marketplace.php';?>
