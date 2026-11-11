<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$email=strtolower(trim($_POST['email']??''));$senha=(string)($_POST['password']??'');
 $st=$pdo->prepare("SELECT id,name,email,password,active FROM customers WHERE email=? LIMIT 1");$st->execute([$email]);$c=$st->fetch();
 if(!$c||!(int)$c['active']||!password_verify($senha,$c['password'])){$erro='Email ou palavra-passe incorretos, ou conta inativa.';}
 else{session_regenerate_id(true);$_SESSION['gpm_customer']=['id'=>(int)$c['id'],'name'=>$c['name'],'email'=>$c['email']];$dest=$_SESSION['gpm_after_login']??'pedidos.php';unset($_SESSION['gpm_after_login']);redirect($dest);}
}
$tituloPagina='Entrar';require __DIR__.'/includes/header-marketplace.php';
?>
<div class="container"><form class="form-panel" method="post"><span class="eyebrow">Bem-vindo à GPM</span><h1 class="page-title">Entrar na tua conta</h1><?=cliente_flash_html()?><?php if($erro):?><div class="alert error"><?=e($erro)?></div><?php endif;?><?=csrf_field()?><div class="field"><label>Email</label><input type="email" name="email" required autocomplete="email" value="<?=e($_POST['email']??'')?>"></div><div class="field"><label>Palavra-passe</label><input type="password" name="password" required autocomplete="current-password"></div><button class="btn-primary full" type="submit">Entrar</button><p class="muted">Ainda não tens conta? <a class="text-link" href="cadastro.php">Criar conta</a></p></form></div>
<?php require __DIR__.'/includes/footer-marketplace.php';?>
