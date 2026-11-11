<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
require_once __DIR__.'/includes/funcoes-cliente.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$produto=$id?cliente_produto($pdo,(int)$id):null;
if(!$produto){http_response_code(404);exit('Produto não encontrado.');}
$imagens=cliente_imagens($pdo,$produto);
if(!$imagens)$imagens=[$produto['main_image']??''];
$rel=$pdo->prepare("SELECT id,name,price,stock_quantity,main_image FROM products WHERE active=1 AND category_id=? AND id<>? ORDER BY id DESC LIMIT 4");
$rel->execute([(int)$produto['category_id'],(int)$produto['id']]);$relacionados=$rel->fetchAll();
$tituloPagina=$produto['name'];
require __DIR__.'/includes/header-marketplace.php';
?>
<div class="container"><div class="breadcrumbs"><a href="index.php">Marketplace</a> / <?=e($produto['category_name']??'Produto')?> / <?=e($produto['name'])?></div><?=cliente_flash_html()?>
<section class="product-detail">
<div><div class="gallery-main"><img id="imagem-principal" src="<?=e(gpm_product_image($imagens[0]??''))?>" alt="<?=e($produto['name'])?>"></div><div class="gallery-thumbs"><?php foreach($imagens as $i=>$img): ?><button type="button" data-gallery-image="<?=e(gpm_product_image($img))?>" class="<?=$i===0?'active':''?>" aria-label="Ver imagem <?=($i+1)?>"><img src="<?=e(gpm_product_image($img))?>" alt=""></button><?php endforeach; ?></div></div>
<div><span class="eyebrow"><?=e($produto['category_name']??'GPM')?></span><h1 class="page-title"><?=e($produto['name'])?></h1><div class="price"><?=money($produto['price'])?></div><p class="muted"><?=nl2br(e($produto['description']??'Sem descrição adicional.'))?></p><p class="stock <?=((float)$produto['stock_quantity']<=0?'out':'')?>"><?=((float)$produto['stock_quantity']>0?'Em stock: '.e($produto['stock_quantity']).' '.e($produto['unit']??'unidade'):'Produto sem stock disponível')?></p><div class="panel" style="padding:18px;margin-top:20px"><form method="post" action="carrinho.php"><input type="hidden" name="acao" value="adicionar"><?=csrf_field()?><input type="hidden" name="produto_id" value="<?=(int)$produto['id']?>"><div class="field"><label for="quantidade">Quantidade</label><input id="quantidade" type="number" class="qty" name="quantidade" min="1" max="<?=max(1,(int)$produto['stock_quantity'])?>" value="1" required></div><button class="btn-primary full" type="submit" <?=((float)$produto['stock_quantity']<=0?'disabled':'')?>><i class="fa-solid fa-cart-plus"></i> Adicionar ao carrinho</button></form></div><p class="muted">O pedido será confirmado pela equipa GPM após validação de disponibilidade e condições comerciais.</p></div>
</section>
<?php if($relacionados): ?><section class="section"><div class="section-heading"><h2>Produtos relacionados</h2><a class="text-link" href="index.php">Ver catálogo</a></div><div class="product-grid"><?php foreach($relacionados as $r): ?><article class="product-card"><a class="product-image" href="produto.php?id=<?=(int)$r['id']?>"><img src="<?=e(gpm_product_image($r['main_image']??''))?>" alt="<?=e($r['name'])?>"></a><div class="product-info"><h3><?=e($r['name'])?></h3><div class="price"><?=money($r['price'])?></div><a class="btn-outline" href="produto.php?id=<?=(int)$r['id']?>">Ver detalhes</a></div></article><?php endforeach; ?></div></section><?php endif; ?></div>
<?php require __DIR__.'/includes/footer-marketplace.php'; ?>
