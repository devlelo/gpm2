# GPM Conecta · Website institucional

Website institucional multipágina da GPM Conecta, com estrutura visual e composição baseadas diretamente no Angomarket de referência.

## Páginas
- `index.html` — página inicial estratégica.
- `sobre.html` — apresentação da empresa, posicionamento e abordagem.
- `servicos.html` — cinco áreas de serviços e produtos.
- `projetos.html` — portfólio e arquivo de projetos.
- `contacto.html` — contacto comercial e formulário preparatório.
- `catalogo.html` e `produto.html` — páginas existentes do catálogo.

## Direção visual
- O layout, a hierarquia, a composição do hero, as secções editoriais, o marquee, os cartões fotográficos, a abordagem, os princípios e o rodapé seguem o modelo do Angomarket.
- A identidade da GPM usa a logo oficial e as cores azul-marinho `#0A2945`, dourado `#CE9E53` e fundos claros.
- O CSS comum está em `gpm-site.css`.
- A navegação liga as cinco páginas institucionais.

## Formulário
O formulário valida os campos no browser e prepara o texto do pedido para cópia. Ainda não envia dados para um servidor. A integração ao backend Laravel deve incluir validação no servidor, proteção CSRF, rate limiting, armazenamento, consentimento e notificações.

## Informação institucional
Não foram inventados contactos oficiais, morada, clientes, testemunhos, estatísticas ou projetos. O portfólio só deve receber casos reais com autorização para divulgação. Disponibilidade, preços e prazos devem ser verificados antes de confirmação comercial.

## Execução
Abrir `index.html` ou servir a pasta `gpm-conecta` com um servidor estático. As fontes e imagens externas requerem ligação à internet.
