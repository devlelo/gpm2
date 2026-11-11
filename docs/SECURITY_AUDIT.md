# Auditoria inicial de segurança — 11/11/2026

## Escopo efetivamente verificado
Foi feita leitura do tree e inspeção direcionada dos principais fluxos atuais: bootstrap e sessão, login administrativo e de cliente, CRUD de produtos/uploads, checkout, gestão de stock, atualização/cancelamento de pedidos, chat de cliente e equipa, e consulta de pedido do cliente. Também foram identificados os endpoints da árvore legada em gpm2/gpm.

A inspeção continua sendo estática e por amostragem de fluxos críticos. Não é pentest nem certificação; ainda falta concluir a revisão individual de todos os ficheiros PHP, inclusive a árvore legada e páginas de CRUD secundárias.

## Riscos confirmados
1. O dump SQL na raiz continha nomes, NIF, telefones, emails, encomendas e hashes de passwords de clientes. Nesta branch, os INSERTs foram removidos do dump versionado. A remoção não apaga o histórico Git.
2. `gpm2/config/database.php` mantém defaults root/sem password apenas para desenvolvimento local. Nesta branch, `APP_ENV=production` exige credenciais DB explícitas e falha de forma segura se estiverem ausentes.
3. `gpm2/gpm/config/database.php` continha host, nome de BD e root/empty hardcoded. Nesta branch passou a delegar para a configuração PDO canónica em `gpm2/config/database.php`.
4. Existem duas árvores de aplicação e duas implementações de bootstrap/ligação PDO, aumentando risco de correções inconsistentes.
5. Antes desta fase, os logins não tinham rate limit persistente.
6. O checkout atual já bloqueia linhas de produto com FOR UPDATE, verifica stock e realiza decremento condicional dentro da transação; ainda faltam testes concorrentes e proteção de reenvio/idempotência.
7. O schema atual tem relações e índices, mas ainda não possui entidades dedicadas para gateways, transações, webhooks ou integrações de fornecedores.

## Ações já aplicadas nesta branch
- O dump SQL público foi convertido em schema-only, removendo os INSERTs com dados de utilizadores/clientes.
- As duas configurações PDO foram consolidadas numa única fonte; a configuração legada agora delega para a canónica.
- A configuração canónica exige variáveis DB explícitas em produção.
- A configuração de sessão passou a ativar strict mode e cookies apenas por HTTP.
- Criada migration aditiva para fundação de integrações de fornecedores e pagamentos.
- Criada documentação de arquitetura, contrato de base de dados, política de segurança e auditoria.
- Implementado rate limit persistente de login (5 tentativas / 15 minutos por identificador+IP e 20 tentativas / 15 minutos por IP) nos logins administrativo e de cliente. A nova tabela é criada pela migration 20261111_002_login_rate_limit.sql.
- Login de cliente passou a usar mensagem genérica também para contas inativas, reduzindo enumeração de contas.
- Upload de produtos reforçado com validação MIME por conteúdo, is_uploaded_file, limite de 5 MB, dimensões máximas, nome aleatório e validação do diretório final.
- Permissões internas são agora relidas da BD em cada pedido autenticado; desativação ou alteração de perfil passa a refletir-se na sessão seguinte.
- Adicionados headers X-Content-Type-Options, X-Frame-Options, Referrer-Policy e Permissions-Policy; HSTS é enviado apenas quando HTTPS está ativo.
- A cópia duplicada/legada gpm2/gpm foi marcada como referência e bloqueada por .htaccess no Apache.
- Criado workflow GitHub Actions para lint de todos os ficheiros PHP e execução de testes de helpers.
- Criado teste read-only de smoke test para confirmar tabelas após migrations.
- Criado plano de migração Laravel sem recriação do esquema existente.

## Trabalho ainda necessário antes de declarar concluída a auditoria
- Terminar a revisão linha a linha de todos os PHP, sobretudo páginas CRUD secundárias, endpoints auxiliares e a árvore legada gpm2/gpm.
- Rever autorização por objeto em todos os pedidos, conversas, mensagens, moradas e documentos; a consulta do pedido no painel do cliente usa customer_id, mas é necessário validar todas as rotas alternativas.
- Rever cada POST para CSRF, validação, prepared statements, autorização e transações.
- Implementar testes de integração para login, permissões por perfil, isolamento de dados entre clientes, checkout concorrente, cancelamento e reposição de stock, chat e uploads.
- Avaliar reenvio duplicado do checkout e idempotência.
- Rever headers de segurança, logout completo, política de sessão e exposição da árvore legada.
- Executar as migrations numa cópia real MySQL/MariaDB e validar compatibilidade de tipos, chaves estrangeiras, engines e versão.

## Limitações
Não declarar o projeto seguro para produção com base nesta etapa. É necessário executar testes funcionais e de segurança no ambiente configurado, incluindo testes de permissões e recuperação de backup.


## Testes e validação — estado real

- O workflow de CI foi executado com sucesso: lint de todos os PHP e testes unitários passaram; as duas migrations também foram aplicadas numa instância descartável MariaDB 10.4.32.
- tests/run.php contém verificações unitárias simples para escaping, geração de chave de rate limit e token CSRF. tests/login_rate_limit_integration.php testa o limite real, mas exige uma base de dados descartável com nome terminado em _test.
- tests/migration_smoke.php verifica, em modo somente leitura, as tabelas esperadas depois da aplicação das migrations.
- A migration 20261111_001_integrations_foundation.sql e a migration 20261111_002_login_rate_limit.sql foram aplicadas com sucesso numa instância descartável MariaDB 10.4.32, restaurando o dump schema-only atual e executando o smoke test e o teste de rate limit.
- O ensaio automatizado usa o dump schema-only (sem INSERTs/dados de clientes) numa base de dados descartável. Ainda não foi testada uma cópia com os registos reais da instalação do utilizador; essa validação de integridade e contagens deve ser feita localmente conforme docs/MIGRATION_TESTING.md.
- Nenhuma alteração foi aplicada à base de dados de produção.
