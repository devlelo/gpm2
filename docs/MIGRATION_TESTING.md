# Teste das migrations numa cópia MySQL — GPM

Este procedimento é obrigatório antes de qualquer aplicação em produção. Os scripts abaixo são para Windows PowerShell com XAMPP. Ajusta o caminho do MySQL à instalação local.

## 1. Criar backup e cópia isolada

Abre PowerShell na pasta do projeto. Usa o dump da base de dados real, guardado fora do repositório:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -p --single-transaction --routines --triggers gpm_mvp_nova > "$env:USERPROFILE\Desktop\gpm_mvp_nova_before_security.sql"

& "C:\xampp\mysql\bin\mysql.exe" -u root -p -e "CREATE DATABASE gpm_mvp_nova_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

& "C:\xampp\mysql\bin\mysql.exe" -u root -p gpm_mvp_nova_test < "$env:USERPROFILE\Desktop\gpm_mvp_nova_before_security.sql"
```

Confirma que a cópia contém dados e tabelas esperadas. Nunca uses o nome da base de dados de produção no restante procedimento.

## 2. Aplicar migrations na cópia

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -p gpm_mvp_nova_test < "database/migrations/20261111_001_integrations_foundation.sql"
& "C:\xampp\mysql\bin\mysql.exe" -u root -p gpm_mvp_nova_test < "database/migrations/20261111_002_login_rate_limit.sql"
```

Executa pela ordem indicada. A migration 002 depende do registo de versões criado pela migration 001.

## 3. Executar smoke tests e teste de rate limit

```powershell
$env:APP_ENV = "local"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_NAME = "gpm_mvp_nova_test"
$env:DB_USER = "root"
$env:DB_PASS = ""

php tests/migration_smoke.php
php tests/login_rate_limit_integration.php
```

O teste de rate limit recusa executar se DB_NAME não terminar em _test. O teste remove apenas as linhas sintéticas criadas por ele, dentro da base de dados de teste.

## 4. Comparar dados antes e depois

Compara as contagens de users, customers, products, orders, order_items, stock_movements, conversations e messages entre backup restaurado e cópia migrada. As migrations devem apenas acrescentar tabelas; não devem alterar nem apagar esses registos.

Testa também:
- Login válido e inválido nos dois tipos de conta.
- Bloqueio após 5 falhas para a mesma combinação identificador/IP.
- Bloqueio por IP após 20 falhas em 15 minutos.
- Permissões para admin, seller e stock_manager.
- Cliente A não consegue consultar pedido/chat do cliente B.
- Checkout com stock suficiente e insuficiente.
- Cancelar pedido repõe stock uma única vez.
- Upload de JPG, PNG e WEBP válidos; rejeitar PHP renomeado, SVG, ficheiro excessivo e imagem com dimensões abusivas.

## Estado

Este roteiro e os scripts de teste estão preparados. A execução real ainda depende de uma cópia MySQL local acessível. Nenhuma migration foi aplicada à base de dados do utilizador nesta tarefa.
