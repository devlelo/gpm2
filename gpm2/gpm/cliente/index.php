<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/iniciar.php';
require_once __DIR__ . '/includes/funcoes-cliente.php';

$search = trim((string)($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$categoryId = ($categoryId && $categoryId > 0) ? $categoryId : null;
$categories = [];
$products = [];
$error = '';

try {
    $categories = $pdo->query("SELECT id,name FROM categories WHERE active=1 ORDER BY name")->fetchAll();
    $columns = gpm_columns($pdo, 'products');
    $required = ['id','category_id','name','price','active'];
    foreach ($required as $col) if (!in_array($col, $columns, true)) throw new RuntimeException("Falta a coluna products.{$col}");
    $description = in_array('description',$columns,true) ? 'p.description' : "'' AS description";
    $stock = in_array('stock_quantity',$columns,true) ? 'p.stock_quantity' : '0 AS stock_quantity';
    $unit = in_array('unit',$columns,true) ? 'p.unit' : "'unidade' AS unit";
    $image = in_array('main_image',$columns,true) ? 'p.main_image' : 'NULL AS main_image';
    $featured = in_array('featured',$columns,true) ? 'p.featured DESC,' : '';
    $created = in_array('created_at',$columns,true) ? 'p.created_at DESC,' : '';
    $sql = "SELECT p.id,p.category_id,p.name,p.price,{$description},{$stock},{$unit},{$image},c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1";
    $params=[];
    if ($search !== '') {
        $sql .= " AND (p.name LIKE :term_name OR " . (in_array('description',$columns,true) ? "p.description LIKE :term_desc" : "p.name LIKE :term_name2") . ")";
        $params['term_name']='%'.$search.'%';
        if (in_array('description',$columns,true)) $params['term_desc']='%'.$search.'%';
        else $params['term_name2']='%'.$search.'%';
    }
    if ($categoryId) { $sql.=" AND p.category_id=:cat"; $params['cat']=$categoryId; }
    $sql.=" ORDER BY {$featured}{$created} p.name ASC LIMIT 60";
    $stmt=$pdo->prepare($sql); $stmt->execute($params); $products=$stmt->fetchAll();
} catch (Throwable $e) {
    error_log('GPM marketplace: '.$e->getMessage());
    $error='Não foi possível carregar o catálogo. Confirma a ligação e a estrutura da base de dados.';
}
$tituloPagina='Marketplace';
require __DIR__.'/includes/header-marketplace.php';
?>
<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">GPM · Materiais de Construção</span><h1>Encontre os materiais certos para a sua obra.</h1><p>Explore produtos, consulte preços e envie o seu pedido à equipa GPM.</p></div><form class="search-form" method="get"><input type="search" name="q" value="<?=e($search)?>" placeholder="Pesquisar produtos..."><button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Pesquisar</button></form></div></section>
<section class="container section" id="categorias"><div class="section-heading"><div><span class="eyebrow">Catálogo</span><h2>Produtos disponíveis</h2></div><span class="muted"><?=count($products)?> resultados</span></div>
<?=cliente_flash_html()?>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<div class="layout-catalog"><aside class="sidebar"><h3>Categorias</h3><ul class="category-list"><li><a class="<?=!$categoryId?'active':''?>" href="index.php<?= $search!==''?'?q='.urlencode($search):'' ?>">Todas</a></li><?php foreach($categories as $cat): ?><li><a class="<?=$categoryId===(int)$cat['id']?'active':''?>" href="index.php?categoria=<?=(int)$cat['id']?><?= $search!==''?'&q='.urlencode($search):'' ?>"><?=e($cat['name'])?></a></li><?php endforeach; ?></ul></aside>
<div id="produtos"><?php if(!$products && !$error): ?><div class="empty">Não foram encontrados produtos para esta pesquisa.</div><?php else: ?><div class="product-grid"><?php foreach($products as $p): $img=gpm_product_image($p['main_image']??''); ?><article class="product-card"><a class="product-image" href="produto.php?id=<?=(int)$p['id']?>"><img src="<?=e($img)?>" alt="<?=e($p['name'])?>"></a><div class="product-info"><span class="product-category"><?=e($p['category_name']??'GPM')?></span><h3><?=e($p['name'])?></h3><p class="product-desc"><?=e(function_exists('mb_strimwidth') ? mb_strimwidth((string)($p['description']??''),0,90,'…','UTF-8') : substr((string)($p['description']??''),0,90))?></p><div class="price"><?=money($p['price'])?></div><span class="stock <?=((float)$p['stock_quantity']<=0?'out':'')?>"><?=((float)$p['stock_quantity']>0?'Disponível':'Sem stock')?></span><div class="card-actions"><a class="btn-outline" href="produto.php?id=<?=(int)$p['id']?>">Ver detalhes</a><a class="btn" href="produto.php?id=<?=(int)$p['id']?>">Comprar</a></div></div></article><?php endforeach; ?></div><?php endif; ?></div></div></section>
<?php require __DIR__.'/includes/footer-marketplace.php'; ?>
