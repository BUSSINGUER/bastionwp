# BastionWP 0.6.0

**Autor:** Kaio Bussinguer

## Foco da versão

Hardening por perfil de ambiente, aplicado por hooks e capabilities nativas do WordPress.

A V0.6.0 não edita automaticamente `wp-config.php` nem arquivos de configuração
do servidor. Essa decisão reduz o risco de indisponibilidade.

## Perfis

### Desenvolvimento

Menos restritivo.

- editores de arquivos: permitidos;
- XML-RPC: permitido;
- Application Passwords: permitidas;
- alterações manuais de infraestrutura: permitidas.

### Staging

- editores de arquivos: bloqueados;
- versão WordPress no HTML: ocultada;
- erros de login: genéricos;
- REST público de usuários: bloqueado;
- XML-RPC e Application Passwords continuam disponíveis para testes.

### Produção

- editores de arquivos: bloqueados;
- XML-RPC: bloqueado;
- Application Passwords: bloqueadas;
- versão WordPress no HTML: ocultada;
- erros de login: genéricos;
- REST público de usuários: bloqueado;
- display_errors suprimido na requisição quando possível;
- manutenção manual de plugins/temas continua disponível ao Developer.

### Produção Bloqueada

Inclui as proteções de Produção e também bloqueia alterações manuais de:

- plugins;
- temas;
- WordPress core.

Atualizações automáticas executadas em background/cron continuam permitidas.

## Diagnóstico

A aba Hardening exibe:

- perfil ativo;
- HTTPS;
- WP_DEBUG;
- exibição de erros;
- XML-RPC;
- Application Passwords;
- editor de arquivos;
- estado de alterações manuais de infraestrutura.

## Compatibilidade

Desabilitar XML-RPC e Application Passwords pode afetar integrações externas.

Use Staging antes de aplicar Produção quando o site depender de integrações
remotas.

## Próxima etapa

```text
0.7.0 — integração com Wordfence
```
