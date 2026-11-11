<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/cliente/includes/iniciar.php';

$cliente = cliente_atual();

if (!$cliente) {
    $_SESSION['gpm_redirect_after_login'] = 'painel-cliente/index.php';
    header('Location: ../login.php');
    exit;
}

if (!function_exists('pc_e')) {
    function pc_e($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$tituloPagina = $tituloPagina ?? 'Painel do Cliente';
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= pc_e($tituloPagina) ?> | GPM</title>

    <style>
        :root {
            --gpm-accent: #FEA619;
            --gpm-dark: #172033;
            --gpm-muted: #697386;
            --gpm-border: #e7eaf0;
            --gpm-bg: #f5f7fb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--gpm-bg);
            color: var(--gpm-dark);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        a { color: inherit; }

        .gpm-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #fff;
            border-bottom: 1px solid var(--gpm-border);
        }

        .gpm-header-inner {
            max-width: 1160px;
            margin: auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .gpm-logo {
            font-size: 25px;
            font-weight: 900;
            text-decoration: none;
        }

        .gpm-logo span { color: var(--gpm-accent); }

        .gpm-nav {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }

        .gpm-nav a {
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .gpm-nav a:hover,
        .gpm-nav a.active {
            background: #fff1d8;
        }

        .gpm-nav .gpm-sair {
            border: 1px solid var(--gpm-border);
        }

        .gpm-menu-btn {
            display: none;
            background: white;
            border: 1px solid var(--gpm-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 20px;
        }

        .gpm-main {
            max-width: 1160px;
            min-height: 55vh;
            margin: 30px auto 45px;
            padding: 0 20px;
        }

        .gpm-footer {
            background: var(--gpm-dark);
            color: #e8ebf1;
        }

        .gpm-footer-inner {
            max-width: 1160px;
            margin: auto;
            padding: 28px 20px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .gpm-footer p {
            margin: 5px 0;
            color: #c5cad5;
            font-size: 13px;
        }

        .gpm-footer-links {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .gpm-footer a {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
        }

        .gpm-card {
            background: #fff;
            border: 1px solid var(--gpm-border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 16px #17203308;
        }

        .gpm-title {
            margin: 0 0 8px;
            font-size: 27px;
        }

        .gpm-muted {
            color: var(--gpm-muted);
            font-size: 14px;
        }

        .gpm-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .gpm-stat {
            margin-top: 7px;
            font-size: 28px;
            font-weight: 800;
        }

        .gpm-btn {
            display: inline-block;
            padding: 11px 15px;
            background: var(--gpm-accent);
            color: #172033;
            border: 0;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .gpm-btn-secundario {
            background: #fff;
            border: 1px solid var(--gpm-border);
        }

        .gpm-table-wrap { overflow-x: auto; }

        .gpm-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        .gpm-table th,
        .gpm-table td {
            padding: 13px 10px;
            border-bottom: 1px solid var(--gpm-border);
            text-align: left;
            font-size: 14px;
        }

        .gpm-table th {
            color: var(--gpm-muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        .gpm-badge {
            display: inline-block;
            padding: 5px 9px;
            background: #fff0d4;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .gpm-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 15px;
        }

        .gpm-field label {
            font-size: 13px;
            font-weight: bold;
        }

        .gpm-field input,
        .gpm-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7dce5;
            border-radius: 8px;
            background: #fff;
            font: inherit;
        }

        .gpm-alert {
            padding: 12px;
            border-radius: 8px;
            margin: 14px 0;
        }

        .gpm-alert-erro {
            background: #fff0f0;
            color: #9b1c1c;
        }

        .gpm-alert-sucesso {
            background: #e9f9ef;
            color: #176536;
        }

        @media (max-width: 760px) {
            .gpm-header-inner { flex-wrap: wrap; }

            .gpm-menu-btn { display: block; }

            .gpm-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .gpm-nav.aberto { display: flex; }

            .gpm-nav a { padding: 12px; }

            .gpm-grid { grid-template-columns: 1fr; }

            .gpm-main {
                margin-top: 22px;
                padding: 0 14px;
            }

            .gpm-title { font-size: 24px; }

            .gpm-footer-inner { flex-direction: column; }
        }
    </style>
</head>
<body>

<header class="gpm-header">
    <div class="gpm-header-inner">
        <a href="index.php" class="gpm-logo">G<span>PM</span></a>

        <button type="button"
                class="gpm-menu-btn"
                id="gpm-menu-btn"
                aria-label="Abrir menu"
                aria-expanded="false">☰</button>


<nav class="gpm-nav" id="gpm-nav">
    <a class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>"
       href="index.php">Painel</a>

    <a class="<?= in_array($paginaAtual, ['pedidos.php', 'pedido.php'], true) ? 'active' : '' ?>"
       href="pedidos.php">Minhas compras</a>

    <a class="<?= $paginaAtual === 'dados.php' ? 'active' : '' ?>"
       href="dados.php">Meus dados</a>

    <a class="<?= $paginaAtual === 'alterar-senha.php' ? 'active' : '' ?>"
       href="alterar-senha.php">Segurança</a>

    <a href="../index.php">Voltar ao marketplace</a>

    <a class="gpm-sair" href="../logout.php">Sair</a>
</nav>

    </div>
</header>

<main class="gpm-main">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botao = document.getElementById('gpm-menu-btn');
    const menu = document.getElementById('gpm-nav');

    botao.addEventListener('click', function () {
        const aberto = menu.classList.toggle('aberto');
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
});
</script>