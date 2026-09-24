# BastionWP — Publicação e Atualizações pelo GitHub

## Objetivo

A partir do BastionWP 0.4.0, o GitHub Releases funciona como fonte de atualização do plugin.

O repositório deve ser público enquanto a atualização não utilizar autenticação.

## Estrutura

Repositório sugerido:

```text
bastionwp
```

A pasta raiz do repositório deve conter o código-fonte do plugin.

## Regra do pacote de atualização

Não usar o ZIP automático "Source code" do GitHub como pacote do WordPress.

Anexar à Release o ZIP gerado pelo projeto:

```text
bastionwp-X.Y.Z.zip
```

Internamente:

```text
bastionwp/
├── bastionwp.php
├── includes/
├── integrations/
├── admin/
├── mu/
└── ...
```

## Publicar uma versão

1. Atualizar `Version:` no arquivo principal.
2. Atualizar `BASTIONWP_VERSION`.
3. Atualizar `BASTION_CORE_VERSION` somente quando o arquivo do Bastion Core realmente mudar.
4. Atualizar `CHANGELOG.md`.
5. Gerar e validar o ZIP instalável.
6. Confirmar que a raiz interna é exatamente `bastionwp/` e que `bastionwp/bastionwp.php` existe.
7. Commitar as alterações.
8. Enviar para `main`.
9. Criar uma tag `vX.Y.Z`.
10. Criar GitHub Release para a tag.
11. Anexar somente o asset instalável `bastionwp-X.Y.Z.zip`. O source é entregue separadamente e não deve ser usado pelo updater.
12. Incluir no corpo da Release as linhas `Requires PHP:` e `Requires at least:`.
13. Para versão estável, não marcar "pre-release".
14. Para beta, marcar "pre-release".
15. Publicar a Release.
16. Em um site de teste, usar `BastionWP > Sistema > Verificar atualizações agora`.
17. Testar o update e a sincronização do Bastion Core antes de considerar a versão liberada para produção.

## Configuração no site

Em:

```text
BastionWP > Sistema
```

preencher:

```text
Proprietário do repositório: SEU_USUARIO
Nome do repositório: bastionwp
Canal: Estável
Atualizações automáticas: Ativadas
```

## Migração futura para servidor próprio

Não trocar o slug:

```text
bastionwp/bastionwp.php
```

Uma versão futura entregue pelo GitHub deverá conter o novo Provider e novo `Update URI`.

Depois que os sites receberem essa versão, as versões seguintes poderão vir do servidor próprio sem reinstalação manual.
