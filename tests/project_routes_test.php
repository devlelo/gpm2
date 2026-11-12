<?php
declare(strict_types=1);

/*
 * Regression checks for the public URL root and unified customer routes.
 * The deploy folder is named "gpm"; the repository source folder "gpm2"
 * is copied into it. There must not be a second /gpm/ URL segment.
 */
$project = __DIR__ . '/../gpm2';
$read = static function (string $path) use ($project): string {
    $file = $project . '/' . $path;
    if (!is_file($file)) {
        throw new RuntimeException("Missing project file: {$path}");
    }
    return (string) file_get_contents($file);
};

$home = $read('index.php');
$marketHeader = $read('cliente/includes/header-marketplace.php');
$customerHeader = $read('cliente/includes/header.php');
$customerLogin = $read('cliente/login.php');
$checkout = $read('cliente/finalizar-pedido.php');
$legacyChat = $read('cliente/chat.php');
$legacyOrder = $read('cliente/pedido.php');
$legacyAccess = $read('gpm/.htaccess');

$assert(str_contains($home, 'href="cliente/index.php"'), 'homepage marketplace link starts at the project root');
$assert(!str_contains($home, 'gpm/cliente/'), 'homepage has no duplicated legacy /gpm/cliente route');
$assert(!str_contains($home, 'gpm/assets/'), 'homepage assets do not point into the nested legacy tree');
$assert(str_contains($marketHeader, "rtrim(BASE_URL, '/') . '/cliente'"), 'marketplace derives its URL from canonical BASE_URL');
$assert(str_contains($marketHeader, 'href="painel-cliente/index.php"'), 'marketplace account link opens the customer panel');
$assert(str_contains($marketHeader, '<?= e(BASE_URL) ?>/assets/img/GPMlogo.png'), 'marketplace logo uses the canonical asset root');
$assert(str_contains($customerHeader, '<?= e(BASE_URL) ?>/cliente/index.php'), 'customer panel returns to the canonical marketplace');
$assert(str_contains($customerLogin, "redirect('painel-cliente/index.php')"), 'customer login redirects to the panel without duplicating /cliente');
$assert(str_contains($checkout, "redirect('painel-cliente/pedido.php?id='"), 'checkout opens order details inside the customer panel');
$assert(str_contains($legacyChat, "redirect('painel-cliente/chat.php?pedido='"), 'legacy chat route forwards to the unified customer chat');
$assert(str_contains($legacyOrder, "redirect('painel-cliente/pedido.php?id='"), 'legacy order route forwards to unified order details');
$assert(str_contains($legacyAccess, 'Require all denied'), 'nested legacy /gpm tree is blocked from HTTP access');
$assert(is_file($project . '/assets/img/sem-imagem.svg'), 'canonical product placeholder image exists');

foreach (['b.php', 'cliente/index1.php', 'cliente/index copy.php', 'cliente/index copy 2.php', 'cliente/produtos copy.php'] as $legacyPage) {
    $source = $read($legacyPage);
    $assert(str_contains($source, "header('Location: index.php', true, 301)"), "{$legacyPage} redirects to the canonical page");
}
