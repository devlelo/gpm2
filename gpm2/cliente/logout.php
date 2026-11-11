<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

// Limpar os dados da sessão do cliente.
unset($_SESSION['gpm_customer']);

// Regenerar o identificador da sessão para maior segurança.
session_regenerate_id(true);

// Encaminhar o cliente para o login.
redirect('login.php');
exit;