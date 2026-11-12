<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pedidoId || $pedidoId < 1) {
    redirect('painel-cliente/pedidos.php');
}

if (!cliente_atual()) {
    $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/pedidos.php';
    redirect('login.php');
}

redirect('painel-cliente/pedido.php?id=' . $pedidoId);
