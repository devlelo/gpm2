# GPM Conecta — Arquitectura de referência

## Objetivo
Manter o sistema PHP atual funcional enquanto se prepara uma evolução gradual para Laravel. O MySQL/MariaDB existente é a fonte única de verdade. Não criar uma segunda base de dados para a versão Laravel.

## Estado observado no repositório
- A aplicação PHP principal encontra-se em `gpm2/`.
- Existe também uma segunda árvore em `gpm2/gpm/`, com outro bootstrap e outra configuração PDO.
- A configuração principal já usa PDO, prepared statements e variáveis de ambiente, mas mantém defaults locais `root`/senha vazia.
- A árvore duplicada contém credenciais MySQL hardcoded.
- O dump SQL antigo incluía registos reais de clientes e hashes de palavras-passe. A cópia versionada foi convertida em schema-only nesta branch; o histórico Git continua a exigir tratamento separado.

## Regra estrutural
Uma aplicação, uma base de dados, um contrato de dados. O PHP atual e o futuro Laravel devem apontar para o mesmo servidor e o mesmo schema, inicialmente `gpm_mvp_nova`. Ambientes local, staging e produção podem ter bases separadas por isolamento operacional, mas não se deve criar uma base paralela para a nova versão do mesmo ambiente.

## Camadas recomendadas
1. **HTTP/UI**: páginas e controllers; não contêm SQL de negócio complexo.
2. **Application/Use cases**: serviços para encomendas, stock, clientes, proformas, pagamentos e sincronização.
3. **Domain**: regras de negócio, estados e cálculos.
4. **Infrastructure**: PDO/Eloquent, gateways de pagamento, APIs de fornecedores, email e armazenamento.
5. **Database**: schema MySQL, migrações incrementais, índices, FKs e backups.

## Contratos de integração
- Cada fornecedor implementa um driver/adaptador independente; não espalhar chamadas HTTP de fornecedores pelas páginas.
- Cada gateway implementa um contrato comum: criar cobrança, consultar estado, cancelar quando suportado, validar webhook e normalizar estados.
- Nunca confiar no redirect do browser para marcar uma encomenda como paga. Apenas confirmação autenticada do gateway, verificada no servidor, pode alterar o estado.
- Usar idempotência para criação de cobranças e processamento de webhooks.
- Guardar credenciais cifradas no servidor ou num secret manager. Nunca guardar chaves em Git, frontend, logs ou JSON em texto simples.

## Evolução para Laravel
1. Congelar o contrato atual da base de dados e documentar todas as tabelas, índices e FKs.
2. Criar uma branch de migração e uma cópia de staging da base atual.
3. Criar um baseline Laravel que reconhece as tabelas existentes sem as recriar.
4. Configurar Laravel com as mesmas variáveis DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD.
5. Adicionar migrations apenas para alterações novas e incrementais.
6. Migrar um módulo de cada vez; manter compatibilidade com o PHP antigo durante a transição.
7. Fazer testes de leitura/escrita cruzada e validar contagens, IDs, relações e totais antes do corte.
8. Só remover código antigo depois de período de observação e backup verificado.

## Regras de deploy
- Deploy de código não deve executar import completo do dump SQL.
- Nunca executar `migrate:fresh`, `db:wipe`, `DROP DATABASE` ou import de dump de produção durante atualização.
- Migrations devem ser pequenas, rastreáveis, testadas em staging e preferencialmente aditivas.
- Backup antes de cada migration; guardar fora do webroot e testar restauração.
- Separar migrations de dados (backfill) de migrations estruturais quando possível.
- Para mudanças incompatíveis, usar expand → migrate/backfill → switch → contract em deploys separados.
