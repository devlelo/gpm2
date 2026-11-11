<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('carrinho.php');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

if ($id && isset($_SESSION['gpm_cart'][$id])) {
    unset($_SESSION['gpm_cart'][$id]);

    flash('success', 'Produto removido do carrinho.');
}

redirect('carrinho.php');
