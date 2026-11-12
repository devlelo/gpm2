# GPM Conecta

Aplicação PHP + MySQL/PDO.

## Instalação no XAMPP

O código-fonte está dentro de `gpm2/` neste repositório. Para instalação local, copia **o conteúdo** dessa pasta para:

`C:\\xampp\\htdocs\\gpm`

A raiz pública da aplicação será:

- Homepage: `http://localhost/gpm/`
- Marketplace: `http://localhost/gpm/cliente/`
- Painel administrativo: `http://localhost/gpm/admin/`

Não cries `htdocs/gpm/gpm`. A subpasta interna `gpm2/gpm/` é legado bloqueado por `.htaccess` e não faz parte das rotas públicas.

Preserva a configuração da base de dados e os uploads existentes ao atualizar. Não importes um dump completo por cima de uma base com dados reais.
