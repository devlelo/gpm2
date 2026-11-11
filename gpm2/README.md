# GPM — Painel administrativo inicial

Pacote inicial em PHP puro + MySQL/PDO para o sistema GPM de gestão de materiais de construção.

## Incluído
- Login e logout administrativos com sessões e `password_hash`/`password_verify`.
- Dashboard com métricas reais.
- CRUD inicial de produtos e categorias.
- Registo de entradas, saídas e ajustes de stock com transação e bloqueio do produto.
- Listagem de pedidos e alteração de estado com histórico.
- Reposição de stock ao cancelar pedidos que estejam marcados como `stock_deducted = 1`.
- Consulta de clientes e NIF.
- Listagem de conversas.
- Configuração dos dados da empresa e NIF.
- Criação de utilizadores internos.
- Proteção CSRF nos formulários de alteração e escape HTML.

## Instalação
Segue `INSTALAR.txt`. Configura a ligação em `config/database.php` antes de abrir o painel.

## Limitações desta primeira etapa
A interface do chat ainda não permite trocar mensagens. O upload de imagens, emissão de PDF, recuperação de palavra-passe, permissões granulares, auditoria completa e testes de integração serão acrescentados em etapas seguintes. Este pacote deve ser testado no ambiente real antes de uso comercial.
