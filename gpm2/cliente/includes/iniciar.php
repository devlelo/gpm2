<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

function cliente_atual(): ?array
{
    static $validated = false;
    static $clienteValidado = null;

    if ($validated) {
        return $clienteValidado;
    }

    $sessao = $_SESSION['gpm_customer'] ?? null;
    if (!is_array($sessao) || empty($sessao['id'])) {
        $validated = true;
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, active FROM customers WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int)$sessao['id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[GPM customer auth] ' . $e->getMessage());
        $validated = true;
        return null;
    }

    if (!$cliente || (int)$cliente['active'] !== 1) {
        unset($_SESSION['gpm_customer']);
        session_regenerate_id(true);
        $validated = true;
        return null;
    }

    $_SESSION['gpm_customer'] = [
        'id' => (int)$cliente['id'],
        'name' => $cliente['name'],
        'email' => $cliente['email'],
    ];

    $clienteValidado = $_SESSION['gpm_customer'];
    $validated = true;

    return $clienteValidado;
}

function exigir_cliente(): void
{
    if (!cliente_atual()) {
        header('Location: login.php');
        exit;
    }
}

function escapar(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}