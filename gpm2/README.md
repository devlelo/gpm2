# GPM Conecta

Aplicação PHP + MySQL/PDO da GPM Conecta. A aplicação atual e a futura versão Laravel devem partilhar a mesma base MySQL por ambiente.

## Estrutura
- `admin/`: painel administrativo.
- `cliente/`: marketplace e área de cliente.
- `app/bootstrap.php`: sessão, autenticação, CSRF e helpers.
- `config/database.php`: única configuração PDO canónica.
- `database/`: scripts de criação e migrations incrementais.
- `public/uploads/`: ficheiros públicos enviados pela aplicação.

## Base de dados
A base de referência é `gpm_mvp_nova`. O dump `gpm_mvp_nova (1).sql` na raiz contém apenas estrutura, sem registos de clientes. Não o importes sobre uma base com dados reais durante uma atualização.

Para novas alterações, consulta:
- `../docs/DATABASE.md`
- `../docs/ARCHITECTURE.md`
- `../database/migrations/`

## Configuração local (XAMPP)
Por omissão, a configuração local utiliza:
- host: `127.0.0.1`
- porta: `3306`
- base: `gpm_mvp_nova`
- utilizador: `root`
- password: vazia

Estes defaults são apenas para desenvolvimento local isolado. Em produção, definir `APP_ENV=production` e as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`. A aplicação falha de forma segura se faltarem credenciais obrigatórias.

## Segurança
Lê `../SECURITY.md` e `../docs/SECURITY_AUDIT.md`. Antes de produção, ainda é obrigatório auditar todos os endpoints, permissões, uploads, checkout, chat e executar testes no servidor real.

## Atualizações
1. Fazer backup e testar a restauração.
2. Aplicar a migration aprovada em staging.
3. Validar integridade, contagens e operações de negócio.
4. Aplicar a migration em produção.
5. Publicar o código compatível com o schema.
6. Nunca executar `migrate:fresh`, `db:wipe`, `DROP DATABASE` ou import total durante deploy.
