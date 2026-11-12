# GPM Conecta — Direção visual aprovada

## Aprovação
- Studio: Business Studio.
- Produto: website institucional + marketplace B2B público, com futura área autenticada de cliente e backoffice.
- Direção aprovada pelo utilizador: **04. Neo-Industrial Field Manual**.
- Estado: fingerprint bloqueado para a primeira implementação visual.

## Fingerprint do estilo

### Princípio visual
A GPM deve parecer um sistema técnico de fornecimento e coordenação operacional, não uma loja alimentar nem um e-commerce genérico. A linguagem inspira-se em manuais de campo industriais, fichas de especificação, inventários e documentação de operações.

### Tipografia
- IBM Plex Sans Condensed: títulos, nomes de áreas e chamadas de ação; pesos 500–700.
- IBM Plex Mono: códigos, referências, metadados, navegação curta, índices e informação técnica.
- IBM Plex Sans: texto corrido e leitura prolongada.
- Hierarquia por escala, peso, alinhamento e largura; evitar serifas editoriais.

### Paleta oficial da marca GPM Conecta
- Branco: #FFFFFF, fundo principal e superfícies de leitura.
- Azul-marinho: #0A2945, cor estrutural dominante para cabeçalho, hero, títulos, texto de maior ênfase e superfícies institucionais.
- Castanho claro / dourado: #CE9E53, acento de marca para chamadas à ação, destaques, indicadores e elementos de foco sobre fundos escuros.
- Cinza azulado #F3F5F7 e cinzas de apoio são apenas neutros funcionais para separar superfícies, bordas e metadados; não substituem nem competem com as três cores oficiais.
- Texto sobre dourado deve usar azul-marinho. Dourado não deve ser usado como texto pequeno sobre branco, devido ao contraste insuficiente.
- Estados funcionais devem combinar cor e texto; nunca depender apenas da cor.
- A paleta oficial substitui integralmente a paleta experimental anterior. Não usar carvão, marfim técnico ou amarelo-lima como cores da marca.

### Composição e grelha
- Grelha técnica modular com margens consistentes, colunas assimétricas e áreas de informação alinhadas.
- Hierarquia inspirada em folhas de especificação: código de secção, título, nota curta, tabela/linha operacional.
- Navegação compacta e funcional.
- Categorias como linhas de índice e não como cartões repetidos.
- Marketplace preparado para pesquisa, filtros e comparação, com densidade controlada.
- Linhas e bordas são estruturais, nunca o pequeno traço decorativo imediatamente antes de uma label.

### Imagem e grafismo
- Primeira entrega não usa fotografias genéricas ou imagens de stock sem confirmação de ativos reais.
- Grafismos técnicos CSS/SVG próprios podem representar relações de fornecimento e organização.
- Quando a GPM disponibilizar fotografias reais de produtos, armazéns, instalações ou equipas, serão integradas com proporções e legendas técnicas.
- Não usar ilustrações pseudo-3D, gradientes decorativos ou ícones emoji.

### Navegação e componentes
- Cabeçalho escuro e compacto, com navegação textual e acesso ao catálogo.
- CTAs retangulares, de alto contraste e sem arredondamento decorativo.
- Listas, tabelas, índices e painéis técnicos substituem a repetição de cartões.
- Controlos com estados hover, focus-visible, active e disabled.
- Pesquisa e seleção de áreas devem funcionar no frontend; não simular resultados de produtos inexistentes.

### Motion
- Microinterações curtas de foco, seleção e abertura do menu.
- Sem parallax, cursores especiais, animação excessiva ou efeitos de brilho.
- Respeitar prefers-reduced-motion.

### Responsividade
- Desktop: grelha assimétrica e composição técnica ampla.
- Tablet: colunas reduzidas, sem perder índices e hierarquia.
- Mobile: navegação recolhida, grelha linear, alvos tácteis confortáveis e tabelas convertidas em blocos legíveis.
- Sem overflow horizontal.

## Auditoria de originalidade
Projetos comparados: portfólio `index (6).html`, Oxe Café e Aresta.
- Evitar paleta papel + azul elétrico do portfólio; a GPM usa branco, azul-marinho #0A2945 e dourado #CE9E53.
- Evitar madeira/castanho e laranja da Oxe.
- Evitar papel mineral + oliva, serif editorial e hero imobiliário assimétrico da Aresta.
- Evitar hero genérico com fotografia dominante, três cartões de serviços, testemunhos fabricados e grelha de cartões repetitiva.

## Conceito distintivo
**A folha de operações da cadeia de fornecimento torna-se a interface.** A página organiza a GPM como uma central de coordenação: áreas, catálogo, processo de validação e acesso comercial são apresentados como informação operacional clara.

## Anti-patterns proibidos
- Paletas fora das cores oficiais da GPM, incluindo azul elétrico, amarelo-lima e gradientes roxos.
- Tratar o dourado como estética de luxo genérica; deve funcionar como acento institucional da marca, não como tema decorativo dominante.
- Serif editorial ou estética de luxo.
- Fotografia genérica de aperto de mãos, escritórios ou armazéns não confirmados.
- Cartões arredondados e sombras suaves como linguagem dominante.
- Ícones emoji.
- Pequeno traço horizontal decorativo imediatamente antes de labels.
- Hifens/travessões decorativos entre frases.
- Números de clientes, certificações, estatísticas, testemunhos, moradas, preços ou marcas de fornecedores inventados.

## Arquitetura de experiência da primeira entrega
1. Apresentação institucional objetiva.
2. Navegação por áreas de fornecimento.
3. Entrada para catálogo público, com estado vazio honesto enquanto os produtos reais não forem carregados.
4. Explicação do processo: pedido, consulta, proposta, confirmação.
5. Convite para contacto, sem formulário falso ou CTA sem destino.
6. Acesso futuro de clientes e funcionários será ligado após autenticação e backend.

## Limites desta primeira implementação
O protótipo é frontend estático. Não cria contas, não grava pedidos, não envia mensagens, não consulta fornecedores e não processa pagamentos. Estas funções serão implementadas com backend e integrações reais nas fases seguintes.
