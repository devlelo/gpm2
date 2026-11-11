<?php
declare(strict_types=1);

function cliente_url(string $path): string { return $path; }

function cliente_produto(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.active=1 LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function cliente_imagens(PDO $pdo, array $produto): array {
    $imgs = [];
    if (gpm_table_exists($pdo, 'product_images')) {
        $cols = gpm_columns($pdo, 'product_images');
        $fk = in_array('product_id', $cols, true) ? 'product_id' : null;
        $path = in_array('image_path', $cols, true) ? 'image_path' : (in_array('image', $cols, true) ? 'image' : null);
        if ($fk && $path) {
            $order = in_array('sort_order', $cols, true) ? 'sort_order' : (in_array('ordem', $cols, true) ? 'ordem' : 'id');
            $stmt = $pdo->prepare("SELECT `{$path}` AS image_path FROM product_images WHERE `{$fk}`=? ORDER BY `{$order}` ASC");
            $stmt->execute([(int)$produto['id']]);
            $imgs = array_column($stmt->fetchAll(), 'image_path');
        }
    } elseif (gpm_table_exists($pdo, 'produto_imagens')) {
        $stmt = $pdo->prepare("SELECT imagem AS image_path FROM produto_imagens WHERE produto_id=? ORDER BY principal DESC, ordem ASC, id ASC");
        $stmt->execute([(int)$produto['id']]);
        $imgs = array_column($stmt->fetchAll(), 'image_path');
    }
    if (!empty($produto['main_image'])) array_unshift($imgs, $produto['main_image']);
    $imgs = array_values(array_unique(array_filter($imgs)));
    return $imgs;
}

function cliente_flash_html(): string {
    $out = '';
    foreach (['success'=>'success','error'=>'error'] as $type=>$class) {
        $message = get_flash($type);
        if ($message) $out .= '<div class="alert '.$class.'">'.e($message).'</div>';
    }
    return $out;
}

function cliente_get_stock(PDO $pdo, int $id): float {
    $stmt=$pdo->prepare("SELECT stock_quantity FROM products WHERE id=?");
    $stmt->execute([$id]);
    return (float)$stmt->fetchColumn();
}
