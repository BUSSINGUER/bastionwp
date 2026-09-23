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
3. Atualizar `BASTION_CORE_VERSION`.
4. Atualizar `CHANGELOG.md`.
5. Gerar e validar o ZIP instalável.
6. Commitar as alterações.
7. Enviar para `main`.
8. Criar uma tag `vX.Y.Z`.
9. Criar GitHub Release para a tag.
10. Anexar `bastionwp-X.Y.Z.zip`.
11. Para versão estável, não marcar "pre-release".
12. Para beta, marcar "pre-release".
13. Publicar a Release.
14. Em um site de teste, usar `BastionWP > Atualizações > Verificar atualizações agora`.
15. Testar o update antes de considerar a versão liberada para produção.

## Configuração no site

Em:

```text
BastionWP > Atualizações
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
