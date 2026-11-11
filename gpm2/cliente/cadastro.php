<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

if (cliente_atual()) {
    redirect('painel-cliente/index.php');
    exit;
}

$pdo = db();
$erro = '';

$nome = '';
$email = '';
$telefone = '';
$nif = '';
$tipoCliente = 'individual';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nome = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefone = trim($_POST['phone'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $tipoCliente = $_POST['customer_type'] ?? 'individual';
    $senha = $_POST['password'] ?? '';
    $confirmarSenha = $_POST['password_confirmation'] ?? '';

    if (
        $nome === '' ||
        $email === '' ||
        $telefone === '' ||
        $senha === ''
    ) {
        $erro = 'Preenche todos os campos obrigatórios.';
    } elseif (strlen($nome) > 180) {
        $erro = 'O nome não pode ultrapassar 180 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $erro = 'Introduz um endereço de email válido.';
    } elseif (strlen($telefone) > 40) {
        $erro = 'O telefone não pode ultrapassar 40 caracteres.';
    } elseif (strlen($nif) > 50) {
        $erro = 'O NIF não pode ultrapassar 50 caracteres.';
    } elseif (!in_array($tipoCliente, ['individual', 'company'], true)) {
        $erro = 'Seleciona um tipo de cliente válido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A palavra-passe deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As palavras-passe não coincidem.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM customers
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = 'Este email já está registado. Inicia sessão.';
            } else {
                $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO customers (
                        customer_type,
                        name,
                        nif,
                        phone,
                        email,
                        password,
                        active,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
                );

                $stmt->execute([
                    $tipoCliente,
                    $nome,
                    $nif !== '' ? $nif : null,
                    $telefone,
                    $email,
                    $hashSenha,
                ]);

                $clienteId = (int) $pdo->lastInsertId();

                session_regenerate_id(true);

                $_SESSION['gpm_customer'] = [
                    'id' => $clienteId,
                    'name' => $nome,
                    'email' => $email,
                ];

                $destino = $_SESSION['gpm_redirect_after_login']
                    ?? 'painel-cliente/index.php';

                unset($_SESSION['gpm_redirect_after_login']);

                $destinosPermitidos = [
                    'finalizar-pedido.php',
                    'pedidos.php',
                    'painel-cliente/index.php',
                    'painel-cliente/pedidos.php',
                    'painel-cliente/dados.php',
                    'painel-cliente/alterar-senha.php',
                ];

                if (!in_array($destino, $destinosPermitidos, true)) {
                    $destino = 'painel-cliente/index.php';
                }

                redirect($destino);
                exit;
            }
        } catch (PDOException $e) {
            error_log('Erro no cadastro de cliente GPM: ' . $e->getMessage());

            $erro = 'Não foi possível criar a conta. Verifica os dados e tenta novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar conta | GPM</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#0d233f',
                            navyLight: '#163154',
                            orange: '#f96b21',
                            orangeHover: '#e05812',
                            offWhite: '#f8f9fa'
                        }
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'sans-serif'],
                    },
                    maxWidth: {
                        'site': '1280px'
                    }
                }
            }
        }
    </script>

    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            min-height: 100vh;
        }
        /* Animação de entrada */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards;
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="relative flex items-center justify-center p-4 py-12">

    <!-- Imagem de Fundo e Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=2000&auto=format&fit=crop" 
             alt="Fundo de Construção" 
             class="w-full h-full object-cover">
        <!-- Overlay escuro para garantir legibilidade -->
        <div class="absolute inset-0 bg-[#0d233f]/85 backdrop-blur-sm"></div>
    </div>

    <!-- Botão Voltar (Canto superior esquerdo) -->
    <div class="fixed top-6 left-6 z-20">
        <a href="index.php" class="inline-flex items-center gap-2 text-xs font-bold text-white/70 hover:text-white transition uppercase tracking-wider bg-black/20 px-4 py-2 backdrop-blur-sm">
            <i class="fa-solid fa-arrow-left"></i> Voltar ao Marketplace
        </a>
    </div>

    <!-- Formulário Centralizado (Caixa Branca) -->
    <div class="relative z-10 w-full max-w-2xl bg-white shadow-2xl p-8 md:p-12 reveal">
        
        <div class="text-center mb-10">
            <div class="w-12 h-12 bg-[#f96b21] text-white flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h1 class="text-3xl font-black text-[#0d233f] uppercase tracking-tight mb-2">Criar Conta</h1>
            <p class="text-gray-500 text-sm">Registe-se para finalizar compras e acompanhar os seus pedidos.</p>
        </div>

        <?php if ($erro !== ''): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 text-sm mb-6" role="alert">
                <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= escapar($erro) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="cadastro.php" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Tipo de Cliente -->
            <div>
                <label for="customer_type" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Tipo de Cliente *</label>
                <select id="customer_type" name="customer_type" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition">
                    <option value="individual" <?= $tipoCliente === 'individual' ? 'selected' : '' ?>>Particular</option>
                    <option value="company" <?= $tipoCliente === 'company' ? 'selected' : '' ?>>Empresa</option>
                </select>
            </div>

            <!-- Nome -->
            <div>
                <label for="name" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Nome Completo / Empresa *</label>
                <input type="text" id="name" name="name" maxlength="180" value="<?= escapar($nome) ?>" autocomplete="name" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="Ex: João Silva ou GPM Lda.">
            </div>

            <!-- NIF e Telefone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="nif" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">NIF (Opcional)</label>
                    <input type="text" id="nif" name="nif" maxlength="50" value="<?= escapar($nif) ?>" class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="Número de Contribuinte">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Telefone *</label>
                    <input type="tel" id="phone" name="phone" maxlength="40" value="<?= escapar($telefone) ?>" autocomplete="tel" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="+244 9XX XXX XXX">
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">E-mail *</label>
                <input type="email" id="email" name="email" maxlength="190" value="<?= escapar($email) ?>" autocomplete="email" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="o.seu.email@exemplo.com">
            </div>

            <!-- Palavras-passe -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="password" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Palavra-passe *</label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="••••••••">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2">Confirmar *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition" placeholder="••••••••">
                </div>
            </div>
            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Mínimo de 8 caracteres.</p>

            <!-- Botão -->
            <button type="submit" class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider py-4 transition flex items-center justify-center gap-2 mt-4">
                Criar Conta <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center text-sm">
            <p class="text-gray-500">
                Já tem uma conta?
                <a href="login.php" class="text-[#f96b21] font-bold hover:underline ml-1">Iniciar sessão</a>
            </p>
        </div>

    </div>

</body>
</html>