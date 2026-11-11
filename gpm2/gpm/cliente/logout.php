<?php
declare(strict_types=1);
require_once __DIR__.'/includes/iniciar.php';
unset($_SESSION['gpm_customer']);
session_regenerate_id(true);
flash('success','Sessão terminada com sucesso.');
redirect('index.php');
