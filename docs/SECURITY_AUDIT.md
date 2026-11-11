# Auditoria inicial de segurança — 11/11/2026

## Escopo efetivamente verificado
Leitura do tree do repositório, dump SQL, `gpm2/app/bootstrap.php`, `gpm2/config/database.php`, `gpm2/admin/login.php`, `gpm2/cliente/login.php`, `gpm2/README.md`, `gpm2/INSTALAR.txt`, `gpm2/gpm/app/bootstrap.php` e `gpm2/gpm/config/database.php`.

Esta é uma auditoria inicial de código estático, não um pentest nem certificação de segurança. Ainda falta percorrer todos os endpoints, uploads, CRUDs, checkout, chat e regras de autorização.

## Riscos confirmados
1. O dump SQL na raiz continha nomes, NIF, telefones, emails, encomendas e hashes de passwords de clientes. Nesta branch, os INSERTs foram removidos do dump versionado. A remoção não apaga o histórico Git.
2. `gpm2/config/database.php` mantém defaults root/sem password apenas para desenvolvimento local. Nesta branch, `APP_ENV=production` exige credenciais DB explícitas e falha de forma segura se estiverem ausentes.
3. `gpm2/gpm/config/database.php` continha host, nome de BD e root/empty hardcoded. Nesta branch passou a delegar para a configuração PDO canónica em `gpm2/config/database.php`.
4. Existem duas árvores de aplicação e duas implementações de bootstrap/ligação PDO, aumentando risco de correções inconsistentes.
5. Login administrativo não mostra rate limit nem registo de tentativas no trecho analisado.
6. Login de cliente regenera ID de sessão, mas não foi possível confirmar limite de tentativas; deve ser aplicado centralmente.
7. O schema atual tem relações e índices, mas ainda não possui entidades dedicadas para gateways, transações, webhooks ou integrações de fornecedores.

## Ações já aplicadas nesta branch
- O dump SQL público foi convertido em schema-only, removendo os INSERTs com dados de utilizadores/clientes.
- As duas configurações PDO foram consolidadas numa única fonte; a configuração legada agora delega para a canónica.
- A configuração canónica exige variáveis DB explícitas em produção.
- A configuração de sessão passou a ativar strict mode e cookies apenas por HTTP.
- Criada migration aditiva para fundação de integrações de fornecedores e pagamentos.
- Criada documentação de arquitetura, contrato de base de dados, política de segurança e auditoria inicial.

## Próximas correções de aplicação
- Consolidar a aplicação em um único bootstrap e uma única configuração PDO baseada em ambiente.
- Exigir credenciais por ambiente fora do Git e falhar de forma segura se faltarem em produção.
- Aplicar rate limit de login e respostas genéricas.
- Verificar autorização por objeto em encomendas, conversas, mensagens, moradas e documentos.
- Auditar cada POST para CSRF, validação, prepared statements e transações.
- Validar uploads e impedir execução de scripts em diretórios de upload.
- Adicionar headers de segurança, política de sessão, rotação de sessão e logout completo.
- Criar testes automatizados de autenticação, autorização, stock, checkout, pagamentos e migrations.

## Limitações
Não declarar o projeto seguro para produção com base nesta etapa. É necessário executar testes funcionais e de segurança no ambiente configurado, incluindo testes de permissões e recuperação de backup.
