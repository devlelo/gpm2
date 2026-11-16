<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

verify_csrf();

unset($_SESSION['gpm_customer']);
session_regenerate_id(true);

redirect('login.php');
