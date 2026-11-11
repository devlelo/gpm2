<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pdo = db();

$empresa = [
    'nome'       => 'GPM Conecta',
    'nome_completo' => 'GPM Conecta - Soluções em Suprimentos e Procurement',
    'descricao'  => 'O seu parceiro estratégico no fornecimento de equipamentos, materiais e serviços.',
    'telefone'   => '',
    'whatsapp'   => '',
    'email'      => '',
    'endereco'   => '',
    'cidade'     => '',
];

$produtos = [];
$erroContacto = '';
$sucessoContacto = isset($_GET['contacto']) &&
    $_GET['contacto'] === 'sucesso';

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['formulario'] ?? '') === 'contacto') {

    verify_csrf();

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $assunto = trim((string) ($_POST['assunto'] ?? ''));
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));

    if (
        $nome === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $mensagem === ''
    ) {
        $erroContacto = 'Preencha o nome, um e-mail válido e a mensagem.';
    } elseif (
        mb_strlen($nome) > 150 ||
        mb_strlen($email) > 190 ||
        mb_strlen($telefone) > 40 ||
        mb_strlen($assunto) > 180 ||
        mb_strlen($mensagem) > 5000
    ) {
        $erroContacto = 'Alguns campos ultrapassam o tamanho permitido.';
    } else {
        try {
            $sql = "INSERT INTO contact_messages
                    (name, email, phone, subject, message, created_at)
                    VALUES
                    (:nome, :email, :telefone, :assunto, :mensagem, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nome'     => $nome,
                'email'    => $email,
                'telefone' => $telefone !== '' ? $telefone : null,
                'assunto'  => $assunto !== '' ? $assunto : null,
                'mensagem' => $mensagem,
            ]);

            header('Location: index.php?contacto=sucesso#contactos');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erroContacto = 'Não foi possível enviar a mensagem. Tente novamente.';
        }
    }
}

try {
    $stmt = $pdo->query(
        "SELECT id, name, slug, description, price, main_image
         FROM products
         WHERE active = 1
         ORDER BY featured DESC, created_at DESC
         LIMIT 8"
    );

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

function imagemPublica(?string $imagem): string
{
    if (!$imagem) return '';
    $imagem = str_replace('\\', '/', $imagem);
    $imagem = ltrim($imagem, '/');
    if (str_contains($imagem, '..') || preg_match('/^[a-z]+:/i', $imagem)) return '';
    $candidatos = [
        $imagem, 'uploads/produtos/' . basename($imagem), 'uploads/products/' . basename($imagem),
        'public/uploads/produtos/' . basename($imagem), 'public/uploads/products/' . basename($imagem),
    ];
    foreach ($candidatos as $caminho) {
        if (is_file(__DIR__ . '/' . $caminho)) return $caminho;
    }
    return '';
}

function precoKz(mixed $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' Kz';
}

$seoTitle = 'GPM Conecta | Parceiro Estratégico em Suprimentos e Procurement';
$seoDescription = 'GPM Conecta: procurement, representação comercial, fornecimento de equipamentos, insumos e serviços para indústria, obras e empresas em Angola.';
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">

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
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; color: #333; overflow-x: hidden; }
        
        /* Efeito Watermark para os títulos */
        .watermark {
            position: absolute;
            top: -20px;
            left: 0;
            font-size: clamp(6rem, 15vw, 14rem);
            font-weight: 900;
            color: #f1f3f6;
            z-index: 0;
            line-height: 1;
            letter-spacing: -0.05em;
            user-select: none;
            white-space: nowrap;
            overflow: hidden;
        }
        .watermark-dark {
            color: rgba(255, 255, 255, 0.03);
        }
        .section-title {
            position: relative;
            z-index: 10;
            font-size: 3rem;
            font-weight: 900;
            color: #0d233f;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }

        /* Animações de entrada */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hover nos cards de serviço */
        .service-card:hover {
            border-bottom-color: #f96b21;
        }

        /* Slider Horizontal */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* --- ANIMAÇÃO INFINITA DOS LOGOS --- */
        @keyframes scroll-logos {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .logos-track {
            display: flex;
            width: 200%;
            animation: scroll-logos 30s linear infinite;
        }
        .logos-container:hover .logos-track {
            animation-play-state: paused;
        }
        .logos-half {
            display: flex;
            width: 50%;
        }
        .logo-item {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* --- GRÁFICOS CIRCULARES ANIMADOS --- */
        .progress-circle {
            transition: stroke-dashoffset 2s ease-out;
        }

        /* Transição suave do conteúdo do projeto */
        .project-content-fade {
            transition: opacity 0.3s ease-in-out;
        }

        /* Acordeão para FAQs */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
            opacity: 0;
        }
        .accordion-content.open {
            opacity: 1;
        }
        .accordion-icon {
            transition: transform 0.3s ease;
        }
        .accordion-icon.rotate {
            transform: rotate(45deg); /* Transforma o + num X */
        }

        /* Hover dos Profissionais */
        .team-image-overlay {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease-in-out;
        }
        .team-card:hover .team-image-overlay {
            opacity: 1;
            transform: translateY(0);
        }
        .team-card:hover img {
            transform: scale(1.05);
        }
        .team-card img {
            transition: transform 0.5s ease;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="bg-[#0d233f] text-white text-[11px] py-2 border-b border-gray-700">
    <div class="max-w-site mx-auto px-6 flex justify-between items-center">
        <div class="flex items-center gap-6">
            <span><i class="fa-solid fa-phone text-[#f96b21] mr-2"></i> (+244) 923 000 111</span>
            <span class="hidden sm:inline"><i class="fa-solid fa-envelope text-[#f96b21] mr-2"></i> geral@gpmconecta.ao</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="hidden md:inline">Seg - Sáb: 08:00 - 18:00</span>
            <div class="flex gap-3 border-l border-gray-600 pl-4">
                <span class="hover:text-[#f96b21] cursor-pointer transition"><i class="fa-brands fa-facebook-f"></i></span>
                <span class="hover:text-[#f96b21] cursor-pointer transition"><i class="fa-brands fa-instagram"></i></span>
                <span class="hover:text-[#f96b21] cursor-pointer transition"><i class="fa-brands fa-linkedin-in"></i></span>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<header class="bg-white sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-site mx-auto px-6 h-20 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-2xl font-black text-[#0d233f] tracking-tighter">GPM.</span>
        </a>

        <nav class="hidden lg:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider text-[#0d233f]">
            <a href="#inicio" class="text-[#f96b21]">Início</a>
            <a href="#servicos" class="hover:text-[#f96b21] transition">Serviços</a>
            <a href="#solucoes" class="hover:text-[#f96b21] transition">Soluções</a>
            <a href="#projetos" class="hover:text-[#f96b21] transition">Projetos</a>
            <a href="#sobre" class="hover:text-[#f96b21] transition">Sobre Nós</a>
        </nav>

        <div class="flex items-center space-x-5">
            <button class="text-[#0d233f] hover:text-[#f96b21] transition"><i class="fa-solid fa-magnifying-glass"></i></button>
            <a href="cliente/index.php" class="flex items-center gap-2 text-[#0d233f] hover:text-[#f96b21] transition">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="bg-[#f96b21] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
            </a>
            <button class="lg:hidden text-[#0d233f] text-xl"><i class="fa-solid fa-bars"></i></button>
        </div>
    </div>
</header>

<main>

    <!-- Hero Section -->
    <section class="relative bg-white pt-20 pb-32 overflow-hidden" id="inicio">
        <div class="absolute top-0 right-0 w-3/4 h-full bg-gray-50 z-0 hidden md:block" style="clip-path: polygon(20% 0, 100% 0, 100% 100%, 0% 100%);"></div>
        <div class="absolute right-0 top-0 w-1/2 h-full z-0 hidden lg:block">
             <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?q=80&w=1200&auto=format&fit=crop" 
                  class="w-full h-full object-cover shadow-2xl" 
                  style="clip-path: polygon(25% 0, 100% 0, 100% 100%, 0% 100%);" 
                  alt="Fornecimento e Logística">
        </div>

        <div class="max-w-site mx-auto px-6 relative z-10">
            <div class="max-w-2xl space-y-6 reveal pt-10">
                <h1 class="text-5xl md:text-7xl font-black text-[#0d233f] leading-[1.1] uppercase tracking-tight">
                    Fornecemos <br>soluções para <br>o seu negócio
                </h1>
                <p class="text-gray-500 text-sm md:text-base max-w-md leading-relaxed">
                    Atuamos como representante comercial e especialista em procurement, conectando a sua empresa às melhores soluções nacionais e importadas. Desde materiais e equipamentos até insumos industriais e fogões profissionais.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <a href="#contactos" class="bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition shadow-lg">
                        Falar Connosco
                    </a>
                    <a href="#sobre" class="border border-[#0d233f] text-[#0d233f] hover:bg-[#0d233f] hover:text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition">
                        Saber Mais
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-24 relative overflow-hidden" id="servicos">
        <div class="watermark">SERVIÇOS</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="section-title mb-16 reveal">Serviços</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Procurement e Gestão</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Gerenciamos todo o processo de cotação e negociação para a sua empresa.</p>
                    <span class="text-[#f96b21] font-bold text-xl">+</span>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 100ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Fornecimento B2B</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Fornecimento de equipamentos, materiais, insumos e artigos profissionais.</p>
                    <span class="text-[#f96b21] font-bold text-xl">+</span>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 200ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Representação</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Representação comercial de equipamentos nacionais e importados.</p>
                    <span class="text-[#f96b21] font-bold text-xl">+</span>
                </div>
                <div class="bg-white p-8 border border-gray-100 border-b-4 border-transparent service-card shadow-sm transition-all reveal" style="transition-delay: 300ms;">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">GPM CONECTA</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-4">Importação</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Importação de insumos industriais e materiais especializados.</p>
                    <span class="text-[#f96b21] font-bold text-xl">+</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Section -->
    <section class="py-24 bg-[#f8f9fa] relative">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="relative reveal">
                <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?q=80&w=800&auto=format&fit=crop" alt="Gestão de Projetos" class="w-full h-auto object-cover shadow-xl">
            </div>
            <div class="reveal" style="transition-delay: 150ms;">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Explore a GPM</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mb-6 leading-tight">
                    Gestão completa <br>de compras
                </h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-8">
                    Seja para suprimentos industriais, materiais de construção, equipamentos especializados ou artigos para o setor alimentar (como fogões industriais), a GPM Conecta oferece um atendimento corporativo completo para suprir todas as demandas do seu projeto. Conte com a nossa expertise para simplificar suas aquisições com segurança e agilidade.
                </p>
                <a href="#contactos" class="bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-8 py-3 transition inline-block shadow-md">
                    Solicitar Cotação
                </a>
            </div>
        </div>
    </section>

    <!-- Projects Section (Slider) -->
    <section class="py-24 bg-[#0d233f] relative overflow-hidden" id="projetos">
        <div class="watermark watermark-dark">PROJETOS</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="text-4xl font-black text-white uppercase tracking-tight mb-12 reveal">Soluções e Setores</h2>

            <div class="relative reveal">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center project-content-fade" id="project-display">
                    <div class="lg:col-span-4 space-y-8">
                        <div class="space-y-6">
                            <div>
                                <span id="project-sector" class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest block mb-2"></span>
                                <h3 id="project-title" class="text-2xl font-bold text-white mb-3"></h3>
                                <p id="project-description" class="text-sm text-gray-300 leading-relaxed mb-6"></p>
                                <a href="cliente/index.php" class="inline-block bg-[#f96b21] hover:bg-[#e05812] text-white font-bold text-xs uppercase tracking-wider px-6 py-3 transition">
                                    Ver Produtos no Marketplace
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-8">
                        <img id="project-image" src="" class="w-full h-[400px] object-cover" alt="Projeto">
                    </div>
                </div>

                <div class="flex gap-2 mt-8 justify-start">
                    <button id="prev-project" class="border border-gray-600 text-gray-400 hover:text-white hover:border-[#f96b21] px-4 py-2 text-xs font-bold uppercase transition flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Anterior
                    </button>
                    <button id="next-project" class="bg-[#f96b21] hover:bg-[#e05812] text-white px-4 py-2 text-xs font-bold uppercase transition flex items-center gap-2">
                        Próximo <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Logos Strip Animada -->
    <section class="bg-[#f96b21] py-10 overflow-hidden logos-container">
        <div class="logos-track">
            <div class="logos-half">
                <div class="logo-item"><i class="fa-brands fa-aws text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-google text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-microsoft text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-salesforce text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-ibm text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-oracle text-white text-4xl opacity-90"></i></div>
            </div>
            <div class="logos-half">
                <div class="logo-item"><i class="fa-brands fa-aws text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-google text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-microsoft text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-salesforce text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-ibm text-white text-4xl opacity-90"></i></div>
                <div class="logo-item"><i class="fa-brands fa-oracle text-white text-4xl opacity-90"></i></div>
            </div>
        </div>
    </section>

    <!-- Featured Solutions -->
    <section class="py-24 relative overflow-hidden bg-white" id="solucoes">
        <div class="watermark">DESTAQUES</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="section-title mb-16 reveal">Soluções</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 relative group reveal overflow-hidden h-[400px]">
                    <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?q=80&w=1000&auto=format&fit=crop" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Equipamentos">
                    <div class="absolute inset-0 bg-[#0d233f]/80 flex flex-col justify-end p-8 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <h3 class="text-2xl font-bold text-white mb-2">Equipamentos Especializados</h3>
                        <p class="text-gray-300 text-sm">As melhores marcas para o seu negócio.</p>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-100 p-8 flex flex-col justify-center items-center text-center reveal h-[400px]" style="transition-delay: 100ms;">
                    <i class="fa-solid fa-fire-burner text-[#f96b21] text-4xl mb-4"></i>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Indústria e Restauração</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-2">Fogões e Equipamentos</h3>
                    <p class="text-xs text-gray-500">Soluções completas para cozinhas industriais e restauração.</p>
                </div>

                <div class="relative group reveal h-[250px]" style="transition-delay: 200ms;">
                    <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover" alt="Obra">
                </div>

                <div class="bg-white border border-gray-100 p-8 flex flex-col justify-center items-center text-center reveal h-[250px]" style="transition-delay: 300ms;">
                    <i class="fa-solid fa-boxes-stacked text-[#f96b21] text-4xl mb-4"></i>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Materiais</span>
                    <h3 class="text-lg font-bold text-[#0d233f] mb-2">Materiais e Insumos</h3>
                    <p class="text-xs text-gray-500">Tudo o que precisa para as suas operações.</p>
                </div>

                <div class="relative group reveal h-[250px]" style="transition-delay: 400ms;">
                    <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover" alt="Estruturas">
                </div>
            </div>
        </div>
    </section>

    <!-- Full Project Management Banner (Orange) -->
    <section class="relative py-24 bg-[#f96b21] overflow-hidden">
        <div class="max-w-site mx-auto px-6 flex flex-col md:flex-row items-center relative z-10">
            <div class="md:w-1/2 mb-10 md:mb-0 reveal">
                <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=800&auto=format&fit=crop" class="w-full shadow-2xl" alt="Equipa GPM">
            </div>
            <div class="md:w-1/2 md:pl-16 text-white reveal" style="transition-delay: 150ms;">
                <span class="text-xs font-bold tracking-widest uppercase mb-4 block text-white/80">Explore as Funcionalidades</span>
                <h2 class="text-4xl md:text-5xl font-black mb-6 leading-tight">
                    Gestão completa <br>do seu projeto
                </h2>
                <p class="text-white/90 text-sm leading-relaxed mb-8">
                    Do planeamento à entrega. Acompanhamos o processo de cotação, negociação e entrega com foco em eficiência, prazos e otimização de custos.
                </p>
                <a href="#contactos" class="bg-white text-[#f96b21] font-bold text-xs uppercase tracking-wider px-8 py-3 hover:bg-gray-100 transition inline-block shadow-md">
                    Ler Mais
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics (Contadores Animados) -->
    <section class="py-20 bg-white">
        <div class="max-w-site mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="reveal">
                <span class="block text-5xl font-black text-[#f96b21] mb-2 counter" data-target="34">0</span>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Projetos</span>
            </div>
            <div class="reveal" style="transition-delay: 100ms;">
                <span class="block text-5xl font-black text-[#f96b21] mb-2 counter" data-target="97">0</span>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Clientes Satisfeitos</span>
            </div>
            <div class="reveal" style="transition-delay: 200ms;">
                <span class="block text-5xl font-black text-[#f96b21] mb-2 counter" data-target="15">0</span>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Anos de Experiência</span>
            </div>
            <div class="reveal" style="transition-delay: 300ms;">
                <span class="block text-5xl font-black text-[#f96b21] mb-2 counter" data-target="62">0</span>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Parceiros</span>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-24 bg-[#f8f9fa]">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-[#0d233f] p-10 text-white relative reveal">
                <i class="fa-solid fa-quote-right absolute top-8 right-8 text-4xl text-white/10"></i>
                <span class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest block mb-4">Diretor de Operações</span>
                <h3 class="text-xl font-bold mb-4">A GPM Conecta simplificou as nossas aquisições.</h3>
                <p class="text-sm text-gray-300 leading-relaxed mb-6 italic">
                    "A expertise da equipa em procurement permitiu-nos otimizar custos e garantir prazos de entrega. Um parceiro essencial para o nosso negócio."
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-500 rounded-full"></div>
                    <div>
                        <h4 class="text-sm font-bold">Nathan Marshall</h4>
                        <span class="text-xs text-gray-400">Gestor de Compras</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-10 border border-gray-100 relative shadow-sm reveal" style="transition-delay: 150ms;">
                <i class="fa-solid fa-quote-right absolute top-8 right-8 text-4xl text-gray-100"></i>
                <span class="text-[10px] font-bold text-[#f96b21] uppercase tracking-widest block mb-4">Gestora de Projetos</span>
                <h3 class="text-xl font-bold text-[#0d233f] mb-4">Equipamentos e materiais de alta qualidade.</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-6 italic">
                    "Desde a representação comercial até à entrega, o atendimento é impecável. Recomendo a GPM Conecta para qualquer empresa que precise de fornecimento."
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
                    <div>
                        <h4 class="text-sm font-bold text-[#0d233f]">Emma Romero</h4>
                        <span class="text-xs text-gray-400">Gestora de Projetos</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Professionals (Com Hover Overlay) -->
    <section class="py-24 relative overflow-hidden bg-white">
        <div class="watermark">EQUIPA</div>
        <div class="max-w-site mx-auto px-6 relative z-10">
            <h2 class="section-title mb-12 reveal">Profissionais</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Profissional 1 -->
                <div class="team-card relative reveal">
                    <div class="relative overflow-hidden h-[380px]">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=400&auto=format&fit=crop" class="w-full h-full object-cover grayscale hover:grayscale-0 transition duration-500" alt="Nathan Marshall">
                        
                        <!-- Overlay de Hover -->
                        <div class="team-image-overlay absolute bottom-0 left-0 w-full bg-gradient-to-t from-[#0d233f] to-transparent p-6 text-white">
                            <h4 class="font-bold text-lg">Nathan Marshall</h4>
                            <p class="text-xs text-gray-300">Diretor de Engenharia</p>
                        </div>
                    </div>
                </div>

                <!-- Profissional 2 -->
                <div class="team-card relative reveal" style="transition-delay: 100ms;">
                    <div class="relative overflow-hidden h-[380px]">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=400&auto=format&fit=crop" class="w-full h-full object-cover grayscale hover:grayscale-0 transition duration-500" alt="Emma Romero">
                        <div class="team-image-overlay absolute bottom-0 left-0 w-full bg-gradient-to-t from-[#0d233f] to-transparent p-6 text-white">
                            <h4 class="font-bold text-lg">Emma Romero</h4>
                            <p class="text-xs text-gray-300">Gestora de Projetos</p>
                        </div>
                    </div>
                </div>

                <!-- Profissional 3 -->
                <div class="team-card relative reveal" style="transition-delay: 200ms;">
                    <div class="relative overflow-hidden h-[380px]">
                        <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=400&auto=format&fit=crop" class="w-full h-full object-cover grayscale hover:grayscale-0 transition duration-500" alt="James Carter">
                        <div class="team-image-overlay absolute bottom-0 left-0 w-full bg-gradient-to-t from-[#0d233f] to-transparent p-6 text-white">
                            <h4 class="font-bold text-lg">James Carter</h4>
                            <p class="text-xs text-gray-300">Representante Comercial</p>
                        </div>
                    </div>
                </div>

                <!-- Profissional 4 -->
                <div class="team-card relative reveal" style="transition-delay: 300ms;">
                    <div class="relative overflow-hidden h-[380px]">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=400&auto=format&fit=crop" class="w-full h-full object-cover grayscale hover:grayscale-0 transition duration-500" alt="Sofia Alves">
                        <div class="team-image-overlay absolute bottom-0 left-0 w-full bg-gradient-to-t from-[#0d233f] to-transparent p-6 text-white">
                            <h4 class="font-bold text-lg">Sofia Alves</h4>
                            <p class="text-xs text-gray-300">Gestora de Procurement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Circular Stats (Gráficos e Números Animados) -->
    <section class="py-20 bg-[#f8f9fa]">
        <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10 text-center">
            <div class="flex flex-col items-center reveal">
                <div class="relative w-32 h-32 flex items-center justify-center mb-4">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="6" />
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#f96b21" stroke-width="6" stroke-linecap="round"
                                class="progress-circle" data-percent="36" style="stroke-dasharray: 283; stroke-dashoffset: 283;" />
                    </svg>
                    <div class="absolute flex items-baseline justify-center">
                        <span class="text-2xl font-black text-[#0d233f] counter" data-target="36">0</span>
                        <span class="text-xl font-black text-[#0d233f] ml-0.5">%</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-[#f96b21] uppercase tracking-widest mb-1">PROCUREMENT</span>
                <p class="text-xs text-gray-500">Otimização de custos e prazos.</p>
            </div>
            <div class="flex flex-col items-center reveal" style="transition-delay: 100ms;">
                <div class="relative w-32 h-32 flex items-center justify-center mb-4">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="6" />
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#f96b21" stroke-width="6" stroke-linecap="round"
                                class="progress-circle" data-percent="75" style="stroke-dasharray: 283; stroke-dashoffset: 283;" />
                    </svg>
                    <div class="absolute flex items-baseline justify-center">
                        <span class="text-2xl font-black text-[#0d233f] counter" data-target="75">0</span>
                        <span class="text-xl font-black text-[#0d233f] ml-0.5">%</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-[#f96b21] uppercase tracking-widest mb-1">CLIENTES</span>
                <p class="text-xs text-gray-500">Satisfação e fidelização.</p>
            </div>
            <div class="flex flex-col items-center reveal" style="transition-delay: 200ms;">
                <div class="relative w-32 h-32 flex items-center justify-center mb-4">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="6" />
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#f96b21" stroke-width="6" stroke-linecap="round"
                                class="progress-circle" data-percent="68" style="stroke-dasharray: 283; stroke-dashoffset: 283;" />
                    </svg>
                    <div class="absolute flex items-baseline justify-center">
                        <span class="text-2xl font-black text-[#0d233f] counter" data-target="68">0</span>
                        <span class="text-xl font-black text-[#0d233f] ml-0.5">%</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-[#f96b21] uppercase tracking-widest mb-1">PROJETOS</span>
                <p class="text-xs text-gray-500">Concluídos com sucesso.</p>
            </div>
        </div>
    </section>

    <!-- FAQ Section (Antes do Contacto) -->
    <section class="py-24 bg-white" id="faqs">
        <div class="max-w-site mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] text-center mb-12 reveal">Perguntas Frequentes</h2>
            
            <div class="max-w-3xl mx-auto space-y-0 reveal">
                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Como funciona o processo de procurement?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Nós gerimos todo o ciclo de compras da sua empresa, desde a identificação da necessidade até à negociação com fornecedores e entrega final no seu armazém. Isto garante melhores preços e menos burocracia.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Que tipos de equipamentos e materiais fornecem?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Fornecemos uma vasta gama de produtos. Vai desde materiais de construção civil, insumos industriais, ferramentas, até equipamentos especializados e fogões industriais para o setor alimentar.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Fazem importação de produtos?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Sim. Atuamos como representantes comerciais e facilitamos a importação de equipamentos e materiais específicos que não estejam disponíveis no mercado nacional, tratando de toda a logística.</p>
                    </div>
                </div>

                <div class="accordion-item border-b border-gray-200">
                    <button class="accordion-header w-full flex justify-between items-center py-5 text-left font-bold text-[#0d233f] hover:text-[#f96b21] focus:outline-none transition">
                        <span>Como posso solicitar uma cotação (orçamento)?</span>
                        <span class="accordion-icon text-[#f96b21] font-bold text-xl">+</span>
                    </button>
                    <div class="accordion-content text-sm text-gray-500">
                        <p class="pb-5">Pode preencher o formulário de contacto abaixo, enviar-nos um e-mail ou contactar-nos através do WhatsApp. A nossa equipa responderá no prazo máximo de 24 horas úteis.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final Info & Form (Contactos e Dados) -->
    <section class="py-24 bg-[#f8f9fa]" id="contactos">
        <div class="max-w-site mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                
                <!-- Formulário (Esquerda) -->
                <div class="reveal">
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mb-2">Fale Connosco</h2>
                    <p class="text-gray-500 text-sm mb-8">Precisa de equipamentos ou serviços para a sua empresa? Preencha o formulário e peça já a sua cotação.</p>

                    <form method="POST" action="index.php#contactos" class="space-y-4">
                        <?php if ($sucessoContacto): ?>
                            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 text-sm" role="alert">
                                Mensagem enviada com sucesso. Obrigado!
                            </div>
                        <?php endif; ?>
                        <?php if ($erroContacto !== ''): ?>
                            <div class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 text-sm" role="alert">
                                <?= e($erroContacto) ?>
                            </div>
                        <?php endif; ?>

                        <?= csrf_field() ?>
                        <input type="hidden" name="formulario" value="contacto">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input id="nome" name="nome" type="text" maxlength="150" required placeholder="Nome *" value="<?= e((string) ($_POST['nome'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                            </div>
                            <div>
                                <input id="email" name="email" type="email" maxlength="190" required placeholder="E-mail *" value="<?= e((string) ($_POST['email'] ?? '')) ?>" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white">
                            </div>
                        </div>
                        <div>
                            <textarea id="mensagem" name="mensagem" maxlength="5000" required placeholder="Mensagem *" class="w-full text-sm border border-gray-300 focus:border-[#f96b21] focus:ring-[#f96b21] p-3 bg-white min-h-[150px]"><?= e((string) ($_POST['mensagem'] ?? '')) ?></textarea>
                        </div>
                        <button type="submit" class="bg-[#0d233f] hover:bg-[#163154] text-white font-bold text-xs uppercase tracking-wider px-8 py-4 transition w-full">
                            Enviar Mensagem
                        </button>
                    </form>
                </div>

                <!-- Dados de Contacto (Direita) -->
                <div class="reveal" style="transition-delay: 150ms;">
                    <h2 class="text-3xl md:text-4xl font-black text-[#0d233f] mb-2">Os Nossos Contactos</h2>
                    <p class="text-gray-500 text-sm mb-8">Estamos disponíveis para esclarecer qualquer dúvida sobre os nossos produtos e serviços.</p>

                    <div class="space-y-8">
                        <!-- Morada -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-location-dot text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">Localização</h3>
                                <p class="text-sm text-gray-500">Luanda, Angola<br>Coordenadas: -8.910353, 13.200112</p>
                            </div>
                        </div>

                        <!-- Telefone e Email -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-phone text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">Telefone / WhatsApp</h3>
                                <p class="text-sm text-gray-500 mb-1">(+244) 923 000 111</p>
                                <p class="text-sm text-gray-500">(+244) 931 222 333</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-envelope text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-1">E-mail</h3>
                                <p class="text-sm text-gray-500">geral@gpmconecta.ao</p>
                            </div>
                        </div>

                        <!-- Redes Sociais -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-[#f96b21]/10 text-[#f96b21] flex items-center justify-center rounded-full flex-shrink-0">
                                <i class="fa-solid fa-share-nodes text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-[#0d233f] text-lg mb-2">Redes Sociais</h3>
                                <div class="flex gap-3">
                                    <a href="#" class="w-10 h-10 bg-white border border-gray-200 flex items-center justify-center rounded text-gray-600 hover:text-[#f96b21] hover:border-[#f96b21] transition">
                                        <i class="fa-brands fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-white border border-gray-200 flex items-center justify-center rounded text-gray-600 hover:text-[#f96b21] hover:border-[#f96b21] transition">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-white border border-gray-200 flex items-center justify-center rounded text-gray-600 hover:text-[#f96b21] hover:border-[#f96b21] transition">
                                        <i class="fa-brands fa-linkedin-in"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-white border border-gray-200 flex items-center justify-center rounded text-gray-600 hover:text-[#f96b21] hover:border-[#f96b21] transition">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Localização (Mapa em Largura Total Abaixo do Contacto) -->
    <section class="w-full h-[400px] relative bg-gray-200">
        <iframe 
            src="https://maps.google.com/maps?q=-8.910353,13.200112&t=&z=15&ie=UTF8&iwloc=&output=embed" 
            width="100%" 
            height="100%" 
            style="border:0;" 
            allowfullscreen="" 
            aria-hidden="false" 
            tabindex="0"
            title="Localização da GPM Conecta">
        </iframe>
    </section>

</main>

<!-- Footer -->
<footer class="bg-[#0d233f] text-white py-16 border-t-4 border-[#f96b21]">
    <div class="max-w-site mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="md:col-span-2">
            <a href="index.php" class="flex items-center gap-2 mb-6">
                <div class="w-8 h-8 bg-[#f96b21] text-white flex items-center justify-center font-bold text-lg">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <span class="text-2xl font-black tracking-tighter">GPM.</span>
            </a>
            <p class="text-gray-400 text-sm max-w-sm leading-relaxed">
                Parceiro estratégico no fornecimento de equipamentos, materiais, insumos e serviços para empresas de diversos setores.
            </p>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Navegação</h3>
            <ul class="space-y-3 text-sm text-gray-300">
                <li><a href="#inicio" class="hover:text-white transition">Início</a></li>
                <li><a href="#servicos" class="hover:text-white transition">Serviços</a></li>
                <li><a href="#solucoes" class="hover:text-white transition">Soluções</a></li>
                <li><a href="#faqs" class="hover:text-white transition">FAQs</a></li>
                <li><a href="#contactos" class="hover:text-white transition">Contactos</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#f96b21] mb-6">Marketplace</h3>
            <ul class="space-y-3 text-sm text-gray-300">
                <li><a href="cliente/index.php" class="hover:text-white transition">Entrar</a></li>
                <li><a href="cliente/index.php" class="hover:text-white transition">Ver Catálogo</a></li>
            </ul>
        </div>
    </div>
</footer>

<!-- Scripts de Animações, Slider e Acordeão -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Animação de Revelação ao Scroll
        const reveals = document.querySelectorAll(".reveal");
        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("active");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
        reveals.forEach(reveal => revealOnScroll.observe(reveal));

        // 2. Contagem Animada de Números
        const counters = document.querySelectorAll('.counter');
        const speed = 200;
        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const updateCount = () => {
                const current = +counter.innerText;
                const inc = target / speed;
                if (current < target) {
                    counter.innerText = Math.ceil(current + inc);
                    setTimeout(updateCount, 15);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        };

        // 3. Animação dos Gráficos Circulares
        const progressCircles = document.querySelectorAll('.progress-circle');
        const animateCircle = (circle) => {
            const percent = circle.getAttribute('data-percent');
            const circumference = 283;
            const offset = circumference - (percent / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        };

        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('counter')) animateCounter(entry.target);
                    if (entry.target.classList.contains('progress-circle')) animateCircle(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(counter => statsObserver.observe(counter));
        progressCircles.forEach(circle => statsObserver.observe(circle));

        // 4. Lógica do Slider de Projetos
        const projectsData = [
            {
                sector: 'SETOR: LOGÍSTICA E OBRAS',
                title: 'Fornecimento e Equipamentos',
                description: 'Fornecemos equipamentos, materiais e insumos para obras e operações. Gerenciamos o processo de cotação, negociação e entrega com foco em eficiência, prazos e otimização de custos.',
                image: 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=1000&auto=format&fit=crop'
            },
            {
                sector: 'SETOR: INDÚSTRIA E RESTAURAÇÃO',
                title: 'Suprimentos, Insumos e Equipamentos',
                description: 'Atuamos como representante comercial e especialista em procurement, conectando a sua empresa às melhores soluções. Desde insumos industriais até fogões profissionais.',
                image: 'https://images.unsplash.com/photo-1581094794329-c8112a89af12?q=80&w=1000&auto=format&fit=crop'
            },
            {
                sector: 'SETOR: ENGENHARIA E EMPRESAS',
                title: 'Equipamentos Especializados',
                description: 'Oferecemos um atendimento corporativo completo para suprir todas as demandas do seu negócio. Desde a importação de equipamentos até à entrega final.',
                image: 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?q=80&w=1000&auto=format&fit=crop'
            }
        ];

        let currentProject = 0;
        const displayContainer = document.getElementById('project-display');
        const sectorEl = document.getElementById('project-sector');
        const titleEl = document.getElementById('project-title');
        const descEl = document.getElementById('project-description');
        const imgEl = document.getElementById('project-image');
        const prevBtn = document.getElementById('prev-project');
        const nextBtn = document.getElementById('next-project');

        function updateProject(index) {
            displayContainer.style.opacity = 0;
            setTimeout(() => {
                const project = projectsData[index];
                sectorEl.textContent = project.sector;
                titleEl.textContent = project.title;
                descEl.textContent = project.description;
                imgEl.src = project.image;
                displayContainer.style.opacity = 1;
            }, 300);
        }

        if (prevBtn && nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentProject = (currentProject + 1) % projectsData.length;
                updateProject(currentProject);
            });
            prevBtn.addEventListener('click', () => {
                currentProject = (currentProject - 1 + projectsData.length) % projectsData.length;
                updateProject(currentProject);
            });
        }

        const initialProject = projectsData[0];
        sectorEl.textContent = initialProject.sector;
        titleEl.textContent = initialProject.title;
        descEl.textContent = initialProject.description;
        imgEl.src = initialProject.image;

        // 5. Lógica do Acordeão de FAQs
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.accordion-icon');
                
                // Fecha todos os outros abertos (opcional, para efeito sanfona)
                document.querySelectorAll('.accordion-content').forEach(item => {
                    if (item !== content) {
                        item.style.maxHeight = null;
                        item.classList.remove('open');
                        item.previousElementSibling.querySelector('.accordion-icon').classList.remove('rotate');
                    }
                });

                // Alterna o atual
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                    content.classList.remove('open');
                    icon.classList.remove('rotate');
                } else {
                    content.style.maxHeight = content.scrollHeight + "px";
                    content.classList.add('open');
                    icon.classList.add('rotate');
                }
            });
        });
    });
</script>

</body>
</html>