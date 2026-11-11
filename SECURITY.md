# Política de segurança — GPM Conecta

## Reporte
Não publique vulnerabilidades, credenciais, dados pessoais ou dumps de produção em issues públicas. Contacte os responsáveis do projeto em privado.

## Segredos
- Não versionar `.env`, credenciais MySQL, chaves de API, tokens, certificados ou backups.
- Produção deve usar utilizador MySQL dedicado, com privilégios mínimos e sem permissões administrativas.
- Rodar qualquer segredo que tenha sido publicado, mesmo que o ficheiro tenha sido removido posteriormente.

## Dados
- Não versionar dados reais de clientes, encomendas ou hashes de passwords.
- Usar dados sintéticos em desenvolvimento e testes.
- Backups cifrados, acesso restrito e retenção definida.

## Aplicação
- Prepared statements em todas as queries com input externo.
- CSRF em operações mutáveis; GET nunca altera estado.
- Cookies de sessão Secure/HttpOnly/SameSite, regeneração no login e expiração adequada.
- Limite de tentativas de login, mensagens de erro genéricas e logs sem passwords/tokens.
- Autorização por recurso e por papel no servidor; ocultar botões não é controlo de acesso.
- Uploads validados por MIME real, extensão allowlist, tamanho e nome aleatório; armazenar fora do webroot sempre que possível.
- Escape contextual de saída HTML e CSP/headers de segurança.
- Webhooks devem validar assinatura, timestamp, replay e idempotência.

## Atualizações
Nunca substituir a base de dados com um dump durante deploy. Executar apenas migrations incrementais aprovadas após backup.
