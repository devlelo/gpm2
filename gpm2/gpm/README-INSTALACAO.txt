LEGADO DESATIVADO — NÃO INSTALAR
================================

Esta pasta gpm/ dentro de gpm2/ é uma árvore antiga mantida apenas para
referência histórica. O ficheiro .htaccess desta pasta bloqueia o acesso HTTP.

A aplicação canónica está na raiz de gpm2/:
- index.php
- admin/
- cliente/
- app/
- config/
- assets/
- public/

Instalação local:
1. Copia o CONTEÚDO de gpm2/ deste repositório para C:\xampp\htdocs\gpm.
2. Não copies esta pasta interna gpm/ para dentro de C:\xampp\htdocs\gpm.
3. A homepage é http://localhost/gpm/
4. O marketplace é http://localhost/gpm/cliente/
5. O admin é http://localhost/gpm/admin/

Nunca uses URLs /gpm/gpm/cliente ou /gpm/gpm/admin.
Não importes dumps SQL por cima da base de dados com dados reais.
