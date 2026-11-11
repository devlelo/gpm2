<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/iniciar.php';

/*
|--------------------------------------------------------------------------
| Se o cliente já estiver autenticado
|--------------------------------------------------------------------------
| Encaminha-o para o painel, em vez da página antiga de pedidos.
*/
if (cliente_atual()) {
    redirect('../cliente/painel-cliente/index.php');
    exit;
}

$pdo = db();
$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['password'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preenche o email e a palavra-passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Introduz um endereço de email válido.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, active
             FROM customers
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$cliente ||
            !is_string($cliente['password'] ?? null) ||
            $cliente['password'] === '' ||
            !password_verify($senha, (string)$cliente['password'])
        ) {
            $erro = 'Email ou palavra-passe incorretos.';
        } elseif (!(int) $cliente['active']) {
            $erro = 'A tua conta está desativada. Contacta a equipa GPM.';
        } else {
            /*
            |--------------------------------------------------------------------------
            | Login bem-sucedido
            |--------------------------------------------------------------------------
            */
            session_regenerate_id(true);

            $_SESSION['gpm_customer'] = [
                'id'    => (int) $cliente['id'],
                'name'  => $cliente['name'],
                'email' => $cliente['email'],
            ];

            $stmt = $pdo->prepare(
                "UPDATE customers
                 SET last_login_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([(int) $cliente['id']]);

            /*
            |--------------------------------------------------------------------------
            | Destino depois do login
            |--------------------------------------------------------------------------
            | Se veio da finalização de uma compra, mantém esse destino.
            | Caso contrário, abre o novo painel do cliente.
            */
            $destino = $_SESSION['gpm_redirect_after_login']
                ?? 'painel-cliente/index.php';

            unset($_SESSION['gpm_redirect_after_login']);

            /*
            |--------------------------------------------------------------------------
            | Destinos autorizados
            |--------------------------------------------------------------------------
            | Os destinos abaixo são relativos à pasta cliente/.
            */
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
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Entrar na conta | GPM</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

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
                        sans: ['Inter', 'sans-serif']
                    },
                    maxWidth: {
                        site: '1280px'
                    }
                }
            }
        };
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            margin: 0;
            overflow: hidden;
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="h-screen w-full flex">

    <!-- Lado esquerdo: formulário -->
    <div class="w-full lg:w-1/2 flex flex-col relative bg-white h-full overflow-y-auto">

        <div class="p-8 flex justify-start">
            <a
                href="index.php"
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-400 hover:text-[#f96b21] transition uppercase tracking-wider"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Voltar ao Marketplace
            </a>
        </div>

        <div class="flex-1 flex items-center justify-center px-8 pb-12">
            <div class="w-full max-w-sm reveal">

                <div class="mb-10">
                    <h1 class="text-3xl font-black text-[#0d233f] uppercase tracking-tight mb-2">
                        Entrar
                    </h1>

                    <p class="text-gray-500 text-sm">
                        Bem-vindo de volta. Insira as suas credenciais.
                    </p>
                </div>

                <?php if ($erro !== ''): ?>
                    <div
                        class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 text-sm mb-6"
                        role="alert"
                    >
                        <i class="fa-solid fa-circle-exclamation mr-2"></i>
                        <?= escapar($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" class="space-y-6">
                    <?= csrf_field() ?>

                    <div>
                        <label
                            for="email"
                            class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider mb-2"
                        >
                            E-mail
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= escapar($email) ?>"
                            autocomplete="email"
                            required
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            placeholder="o.seu.email@exemplo.com"
                        >
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label
                                for="password"
                                class="block text-xs font-bold text-[#0d233f] uppercase tracking-wider"
                            >
                                Palavra-passe
                            </label>

                            <a
                                href="#"
                                class="text-[10px] font-bold text-[#f96b21] hover:underline uppercase tracking-wider"
                            >
                                Esqueceu-se?
                            </a>
                        </div>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            class="w-full px-4 py-3 text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-0 bg-gray-50 transition"
                            placeholder="••••••••"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider py-4 transition flex items-center justify-center gap-2"
                    >
                        Entrar na Conta
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="mt-10 pt-6 border-t border-gray-100 text-center text-sm">
                    <p class="text-gray-500">
                        Ainda não tem conta?

                        <a
                            href="cadastro.php"
                            class="text-[#f96b21] font-bold hover:underline ml-1"
                        >
                            Criar conta
                        </a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Lado direito: imagem e apresentação -->
    <div class="hidden lg:flex w-1/2 relative bg-[#0d233f] items-center justify-center overflow-hidden">

        <img
            src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?q=80&w=1200&auto=format&fit=crop"
            alt="Construção"
            class="absolute inset-0 w-full h-full object-cover opacity-40"
        >

        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-[#0d233f] via-[#0d233f]/80 to-transparent"></div>

        <div class="relative z-10 px-16 w-full max-w-xl text-white">
            <div class="w-12 h-12 bg-[#f96b21] flex items-center justify-center font-bold text-2xl mb-8">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tight leading-[1.1] mb-6">
                A sua conta.<br>
                As suas ferramentas.
            </h2>

            <p class="text-gray-300 text-sm leading-relaxed max-w-sm">
                Aceda à sua área de cliente para acompanhar as suas encomendas,
                consultar o histórico e gerir os seus dados de forma segura e rápida.
            </p>
        </div>

        <div class="absolute -bottom-10 -right-10 text-white/5 text-[200px] font-black select-none pointer-events-none leading-none">
            GPM.
        </div>
    </div>

</body>
</html>