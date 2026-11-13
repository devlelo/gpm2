<?php
declare(strict_types=1);

/*
 * Rota de compatibilidade: concentra o chat na área autenticada do cliente.
 * Mantém os links antigos a funcionar sem expor um segundo layout de chat.
 */
require_once __DIR__ . '/includes/iniciar.php';

$pedidoId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
if (!$pedidoId || $pedidoId < 1) {
    redirect('painel-cliente/pedidos.php');
}

if (!cliente_atual()) {
    $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/pedidos.php';
    redirect('login.php');
}

redirect('painel-cliente/chat.php?pedido=' . $pedidoId);
