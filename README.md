# BastionWP 0.8.1

**Autor:** Kaio Bussinguer

Versão corretiva crítica da 0.8.0.

## Correção do erro crítico

A V0.8.0 inicializava `BastionWP_Diagnostics` passando a própria propriedade
`$this->diagnostics` antes de ela existir.

Isso provocava:

```text
Typed property BastionWP::$diagnostics must not be accessed before initialization
```

A V0.8.1 corrige o construtor para usar somente as três dependências exigidas:

```text
BastionWP_MU_Installer
BastionWP_Hardening
BastionWP_Wordfence_Integration
```

Também foi removido um `require_once` duplicado do Logger.

## Site Kit

O Google Site Kit possui seu próprio modelo de permissões.

Para usuários não administradores, o acesso correto ao dashboard é concedido
pelo recurso nativo **Dashboard Sharing** do Site Kit.

A role customizada do BastionWP possui `edit_posts`, portanto pode aparecer nas
opções de compartilhamento do Site Kit.

### Mudança de arquitetura

O BastionWP não tenta mais:

- trocar o slug principal do Site Kit;
- conceder artificialmente capabilities do Site Kit;
- contornar as verificações de autenticação e compartilhamento do Google.

Quando Site Kit for selecionado para um Gerenciador do Cliente:

1. BastionWP permite as rotas `googlesitekit-dashboard` e `googlesitekit-splash`
   dentro da política daquele usuário;
2. Site Kit continua responsável por decidir se o usuário pode acessar;
3. o Developer deve usar Dashboard Sharing no Site Kit para compartilhar os
   serviços com a role **Gerenciador do Cliente**.

Isso evita URLs quebradas como:

```text
/wp-admin/googlesitekit-dashboard
```

e preserva a segurança do modelo nativo do Site Kit.

## Logs e Diagnóstico

Os recursos da V0.8.0 permanecem incluídos:

- Logs;
- exportação CSV;
- Diagnóstico;
- exportação JSON;
- retenção automática;
- sanitização de dados sensíveis.
