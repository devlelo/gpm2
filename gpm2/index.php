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
    'provincia'  => '',
];

try {
    $stmtEmpresa = $pdo->query('SELECT company_name, phone, email, address, city, province FROM company_settings ORDER BY id LIMIT 1');
    $dadosEmpresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
    if ($dadosEmpresa) {
        $empresa['nome'] = $dadosEmpresa['company_name'] ?: $empresa['nome'];
        $empresa['nome_completo'] = $empresa['nome'];
        $empresa['telefone'] = (string)($dadosEmpresa['phone'] ?? '');
        $empresa['whatsapp'] = preg_replace('/[^0-9]/', '', $empresa['telefone']) ?: '';
        $empresa['email'] = (string)($dadosEmpresa['email'] ?? '');
        $empresa['endereco'] = (string)($dadosEmpresa['address'] ?? '');
        $empresa['cidade'] = (string)($dadosEmpresa['city'] ?? '');
        $empresa['provincia'] = (string)($dadosEmpresa['province'] ?? '');
    }
} catch (PDOException $e) {
    error_log('[GPM homepage company settings] ' . $e->getMessage());
}

$partesLocalizacao = array_values(array_filter([$empresa['endereco'], $empresa['cidade'], $empresa['provincia']]));
$localizacaoEmpresa = $partesLocalizacao ? implode(', ', array_merge($partesLocalizacao, ['Angola'])) : '';
$consultaMapaEmpresa = $localizacaoEmpresa !== '' ? $localizacaoEmpresa : 'Angola';
$urlMapaEmpresa = 'https://maps.google.com/maps?q=' . rawurlencode($consultaMapaEmpresa) . '&t=&z=6&ie=UTF8&iwloc=&output=embed';

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

// Homepage: apresentar apenas produtos com maior volume de vendas confirmadas.
try {
    $stmt = $pdo->query(
        "SELECT p.id, p.name, p.slug, p.description, p.price, p.main_image,
                SUM(oi.quantity) AS unidades_vendidas
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         INNER JOIN products p ON p.id = oi.product_id
         WHERE p.active = 1
           AND o.status IN ('confirmed', 'preparing', 'shipped', 'completed')
         GROUP BY p.id, p.name, p.slug, p.description, p.price, p.main_image
         ORDER BY unidades_vendidas DESC, p.name ASC
         LIMIT 4"
    );

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$carrinhoHome = $_SESSION['gpm_cart'] ?? [];
$quantidadeCarrinhoHome = is_array($carrinhoHome)
    ? array_sum(array_map('intval', $carrinhoHome))
    : 0;

function imagemPublica(?string $imagem): string
{
    if (!$imagem) return '';
    $imagem = ltrim(str_replace('\\', '/', trim($imagem)), '/');
    if (str_contains($imagem, '..') || preg_match('/^[a-z]+:/i', $imagem)) return '';

    // Mesmos locais de procura do marketplace, incluindo assets/uploads e storage.
    $nome = basename($imagem);
    $candidatos = array_unique([
        $imagem,
        'uploads/produtos/' . $nome,
        'uploads/products/' . $nome,
        'public/uploads/produtos/' . $nome,
        'public/uploads/products/' . $nome,
        'assets/uploads/' . $nome,
        'storage/' . $imagem,
    ]);

    foreach ($candidatos as $caminho) {
        if (is_file(__DIR__ . '/' . $caminho)) {
            return implode('/', array_map('rawurlencode', explode('/', $caminho)));
        }
    }
    return '';
}

function precoKz(mixed $valor): string
{
    return number_format((float) $valor, 2, ',', '.') . ' Kz';
}

$seoTitle = $empresa['nome'] . ' | Parceiro Estratégico em Suprimentos e Procurement';
$seoDescription = 'GPM Conecta: procurement, representação comercial, fornecimento de equipamentos, insumos e serviços para indústria, obras e empresas em Angola.';
?>
<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0A2945"><link rel="icon" type="image/png" href="assets/img/GPMlogo.png?v=3"><link rel="apple-touch-icon" href="assets/img/GPMlogo.png?v=3"><meta name="description" content="GPM Conecta. Fornecimento integrado para empresas e projetos."><title>Página inicial | GPM Conecta</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/gpm-public-home.css"><style>
/* Contraste do botão de envio do formulário da homepage */
.contact-form button[type="submit"]{background:#0A2945!important;color:#fff!important;border:1px solid #0A2945!important;opacity:1!important}
.contact-form button[type="submit"]:hover,.contact-form button[type="submit"]:focus-visible{background:#123653!important;border-color:#123653!important;color:#fff!important}
</style></head><body><div class="topline"><span>GPM Conecta · Fornecimento empresarial</span><span>Angola · Empresas & projetos</span></div>
<header id="header"><a class="brand" href="index.php" aria-label="GPM Conecta, início"><img class="brand-logo" src="assets/img/GPMlogo.png?v=3" alt=""><span class="brand-wordmark">gpm-conecta</span></a><nav class="nav" id="nav" aria-label="Navegação principal">
<a href="index.php">Início</a><a href="sobre.php">A empresa</a><a href="servicos.php">Serviços/produtos</a><a href="projetos.php">Portfólio</a><a href="localizacao.php">Localização</a><a href="contacto.php">Contacto</a><a href="cliente/index.php" class="nav-cta">Marketplace ↗</a><a href="cliente/login.php">Área do cliente</a>
</nav><button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menu" aria-expanded="false">☰</button></header><main><section class="hero" id="inicio"><div class="hero-photo"><img src="https://images.unsplash.com/photo-1494412519320-aa613dfb7738?auto=format&fit=crop&w=2200&q=85" alt="" fetchpriority="high"></div><div class="hero-grid"></div><div class="hero-content"><div class="hero-meta"><span>GPM CONECTA / APRESENTAÇÃO</span><span>Uma visão orientada<br>para empresas e projetos</span></div><h1>Tudo o que<br><span class="outline">o seu</span><br>projeto precisa.</h1><div class="hero-bottom"><p class="hero-copy">Materiais, equipamentos e soluções para empresas que precisam de construir, equipar e manter as suas operações em movimento.</p><div class="hero-actions"><a class="btn btn-gold" href="cliente/index.php">Explorar marketplace <span class="arrow">↗</span></a><a class="btn btn-ghost" href="#atuacao">Ver áreas de fornecimento <span class="arrow">↗</span></a></div></div></div><div class="hero-index">01 / 05 · GPM CONECTA</div></section><section class="intro" id="empresa"><div class="intro-inner"><div class="reveal"><p class="section-kicker">02 / A empresa</p><h2>Uma empresa.<br>Várias áreas.<br><em>Um só contacto.</em></h2></div><div class="intro-copy reveal"><p>A GPM Conecta aproxima empresas, necessidades e soluções num único ponto de contacto. Apoia a aquisição de materiais, equipamentos e artigos necessários para construir, equipar e operar instalações.</p><a class="text-link" href="#empresa">Conhecer a empresa <span>↗</span></a></div></div></section>
<section class="editorial-band"><div class="band-inner"><div class="band-number reveal">03</div><div class="band-copy reveal"><p class="section-kicker light">03 / A nossa perspetiva</p><h2>O fornecimento certo começa com coordenação.</h2><p>Da identificação da necessidade à validação de uma proposta, cada pedido deve ser tratado com informação clara, acompanhamento e atenção às condições reais de fornecimento.</p></div></div></section>
<section class="marquee"><div class="marquee-track"><span>GPM CONECTA <i>·</i> CONSTRUIR <i>·</i> EQUIPAR <i>·</i> OPERAR <i>·</i></span><span>GPM CONECTA <i>·</i> CONSTRUIR <i>·</i> EQUIPAR <i>·</i> OPERAR <i>·</i></span></div></section>
<section class="offer" id="atuacao"><div class="offer-head"><div class="reveal"><p class="section-kicker">04 / Serviços e produtos</p><h2>Onde a operação<br>ganha forma.</h2></div><p class="reveal">Uma estrutura de fornecimento para diferentes setores. Explore as áreas de atuação e apresente os produtos, materiais ou equipamentos de que a sua empresa necessita.</p></div><div class="offer-grid">
<article class="offer-tile reveal"><img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1200&q=85" alt="" loading="lazy"><div class="tile-content"><div><small>ÁREA 01</small><h3>Construção e materiais</h3></div><span class="tile-arrow">↗</span></div></article>
<article class="offer-tile reveal"><img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=85" alt="" loading="lazy"><div class="tile-content"><div><small>ÁREA 02</small><h3>Hotelaria e alojamento</h3></div><span class="tile-arrow">↗</span></div></article>
<article class="offer-tile reveal"><img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=85" alt="" loading="lazy"><div class="tile-content"><div><small>ÁREA 03</small><h3>Escritórios e empresas</h3></div><span class="tile-arrow">↗</span></div></article></div><div class="home-catalog reveal" id="catalogo"><div class="home-catalog-head"><div><p class="section-kicker">CATÁLOGO / DISPONIBILIDADE</p><h3>Produtos em destaque</h3></div><a class="text-link" href="cliente/index.php">Ver catálogo completo <span>↗</span></a></div>
<?php if ($produtos): ?><div class="home-products-grid"><?php foreach ($produtos as $produtoHome): $imagemHome = imagemPublica((string)($produtoHome['main_image'] ?? '')); ?><article class="home-product-card">
<a class="home-product-image" href="cliente/produto.php?id=<?= (int)$produtoHome['id'] ?>" aria-label="Ver <?= e($produtoHome['name']) ?>">
<img src="<?= e($imagemHome !== '' ? $imagemHome : 'assets/img/sem-imagem.svg') ?>" alt="<?= e($produtoHome['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='assets/img/sem-imagem.svg';">
</a>
<div class="home-product-info">
<span class="home-product-label">GPM CONECTA / PRODUTO</span>
<h4><?= e($produtoHome['name']) ?></h4>
<p><?= e(function_exists('mb_strimwidth') ? mb_strimwidth((string)($produtoHome['description'] ?? ''),0,92,'…','UTF-8') : substr((string)($produtoHome['description'] ?? ''),0,92)) ?></p>
<strong><?= e(precoKz($produtoHome['price'])) ?></strong>
<a href="cliente/produto.php?id=<?= (int)$produtoHome['id'] ?>">Ver produto <span aria-hidden="true">↗</span></a>
</div>
</article><?php endforeach; ?></div><?php else: ?><div class="home-catalog-empty">O catálogo está a ser atualizado. Consulte o marketplace para ver os produtos disponíveis.</div><?php endif; ?></div><p class="provisional-note">Veja os produtos disponíveis e descubra as soluções organizadas por área no marketplace da GPM.</p><p style="text-align:center;margin:35px auto 0"><a class="btn btn-gold" href="cliente/index.php">Entrar no marketplace <span class="arrow">↗</span></a></p></section>
<section class="approach"><div class="approach-inner"><div class="approach-top reveal"><div><p class="section-kicker light">05 / Como trabalhamos</p><h2>Do pedido<br>à confirmação.</h2></div><p>Uma experiência comercial começa com clareza.</p></div><div class="steps"><article class="step reveal"><span class="step-num">01</span><h3>Compreender</h3><p>Identificar a necessidade, as quantidades e o contexto de cada projeto.</p></article><article class="step reveal"><span class="step-num">02</span><h3>Verificar</h3><p>Analisar disponibilidade, especificações, condições e prazos.</p></article><article class="step reveal"><span class="step-num">03</span><h3>Apresentar</h3><p>Preparar uma proposta para análise e confirmação comercial.</p></article></div></div></section>
<section class="values" id="principios"><div class="values-inner"><div class="values-head reveal"><div><p class="section-kicker light">06 / Princípios</p><h2>O que nos<br>orienta.</h2></div><p>Uma relação comercial sólida começa com informação transparente.</p></div><div class="value-list"><article class="value-item reveal"><b>01 / CLAREZA</b><h3>Informação objetiva</h3><p>Pedidos e propostas com informação compreensível e condições explícitas.</p></article><article class="value-item reveal"><b>02 / COORDENAÇÃO</b><h3>Um ponto de contacto</h3><p>Uma abordagem organizada às necessidades de diferentes operações.</p></article><article class="value-item reveal"><b>03 / CONFIANÇA</b><h3>Validação real</h3><p>Disponibilidade, preços e prazos sujeitos a verificação antes da confirmação.</p></article></div></div></section>
<section class="contact" id="contactos"><div class="contact-inner"><div class="reveal"><p class="section-kicker">07 / Contacto</p><h2>Vamos<br><em>conversar?</em></h2></div><div class="contact-copy reveal"><p>Tem uma necessidade de fornecimento ou pretende conhecer melhor a GPM Conecta? Apresente o seu pedido através da área de contacto.</p><p class="contact-channels">PEDIDOS COMERCIAIS<br><strong>Materiais · Equipamentos · Soluções</strong></p><a class="text-link" href="cliente/index.php">Explorar marketplace <span>↗</span></a><a class="text-link" href="contacto.php">Abrir página de contacto <span>↗</span></a></div></div>
<?php if ($erroContacto !== ''): ?><p class="home-form-alert error"><?= e($erroContacto) ?></p><?php endif; ?><?php if ($sucessoContacto): ?><p class="home-form-alert success">A sua mensagem foi recebida. A equipa GPM Conecta entrará em contacto.</p><?php endif; ?>
<form class="contact-form" id="gpm-contact-form" method="post" action="index.php#contactos"><input type="hidden" name="formulario" value="contacto"><?= csrf_field() ?>
<label>Nome completo<input name="nome" maxlength="150" required autocomplete="name"></label><label>E-mail<input type="email" name="email" maxlength="190" required autocomplete="email"></label><label>Telefone<input name="telefone" maxlength="40" autocomplete="tel"></label><label>Assunto<input name="assunto" maxlength="180" placeholder="Ex.: Pedido de fornecimento"></label><label class="full">Mensagem<textarea name="mensagem" maxlength="5000" required placeholder="Descreva brevemente a sua necessidade."></textarea></label>
<div class="form-status"><?= $empresa['telefone'] !== '' ? 'Telefone: '.e($empresa['telefone']) : 'GPM Conecta · Angola' ?><?= $empresa['email'] !== '' ? ' · '.e($empresa['email']) : '' ?></div><button class="btn btn-gold" type="submit">Enviar mensagem <span class="arrow">↗</span></button></form>
</section></main><footer><div class="footer-main"><a class="brand" href="index.php"><img class="brand-logo" src="assets/img/GPMlogo.png?v=3" alt=""><span class="brand-wordmark">gpm-conecta</span></a><div class="footer-links"><div><a href="index.php">Página inicial</a><a href="sobre.php">Sobre a empresa</a><a href="servicos.php">Serviços/produtos</a></div><div><a href="projetos.php">Portfólio e projetos</a><a href="localizacao.php">Localização</a><a href="contacto.php">Contacto</a><a href="cliente/index.php">Marketplace ↗</a></div></div></div><div class="footer-bottom"><span>© <span id="year"></span> GPM Conecta. Todos os direitos reservados.</span><span>Fornecimento integrado para empresas e projetos</span><a href="#inicio">Voltar ao topo ↑</a></div></footer><script>
const header=document.getElementById('header'),toggle=document.getElementById('menuToggle'),nav=document.getElementById('nav');
const onScroll=()=>header.classList.toggle('scrolled',window.scrollY>80);window.addEventListener('scroll',onScroll,{passive:true});onScroll();
toggle.addEventListener('click',()=>{const open=header.classList.toggle('menu-open');document.body.classList.toggle('menu-open',open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');toggle.textContent=open?'×':'☰'});
nav.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{header.classList.remove('menu-open');document.body.classList.remove('menu-open');toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-label','Abrir menu');toggle.textContent='☰'}));
document.getElementById('year').textContent=new Date().getFullYear();
const items=document.querySelectorAll('.reveal');if('IntersectionObserver'in window&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){const observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target)}}),{threshold:.12});items.forEach(x=>observer.observe(x))}else items.forEach(x=>x.classList.add('visible'));
// O formulário é processado pelo PHP no servidor.

</script></body></html>