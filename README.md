# BastionWP 0.5.0

**Autor:** Kaio Bussinguer

BastionWP é uma camada de controle administrativo e proteção para sites WordPress gerenciados.

## Foco da versão 0.5.0

A política de acesso aos menus agora é configurada diretamente por usuário.

Não existem perfis compartilhados.

Exemplo:

```text
João
- Site Kit
- JoinChat

Maria
- WooCommerce

Carlos
- Bloqueio total
```

## Correção principal

Nas versões anteriores, marcar um plugin como permitido podia não fazer o menu aparecer.

Motivo:

Plugins WordPress podem exigir uma capability durante `admin_menu` para registrar
o callback da página administrativa.

A V0.5.0 agora:

1. identifica as capabilities do menu selecionado;
2. concede essas capabilities temporariamente durante `admin_menu`;
3. permite que o plugin registre sua página/callback;
4. altera a capability visual do menu para `read`;
5. volta a conceder as capabilities do plugin somente dentro das rotas que o
   Developer autorizou para aquele usuário.

Não existe concessão global permanente de `manage_options`.

## Configuração

```text
BastionWP
→ Acessos
→ Menus e áreas permitidas por usuário
```

Selecione:

```text
Usuário que será configurado
```

Depois escolha:

```text
Bloqueio total
```

ou:

```text
Personalizado para este usuário
```

Marque os menus desejados e salve.

## Migração

A configuração global da V0.3/V0.4 é migrada uma única vez para os Gerenciadores
do Cliente existentes, preservando seleções anteriores sempre que possível.

## Limitação conhecida

Alguns plugins utilizam:

- admin-ajax.php;
- REST API;
- options.php;

para salvar configurações.

A V0.5.0 não concede capabilities amplas nesses endpoints genéricos.

O menu e a página principal devem passar a funcionar, mas ações internas de um
plugin específico podem exigir um adaptador futuro.

Isso é intencional para evitar transformar uma liberação de menu em privilégio
administrativo global.
