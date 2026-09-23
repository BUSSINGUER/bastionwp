# BastionWP 0.5.1

**Autor:** Kaio Bussinguer

Versão corretiva da 0.5.0.

## Correção principal

Na V0.5.0, o formulário de acesso individual era enviado para `admin-post.php`.

Nesse contexto, o WordPress não monta o catálogo administrativo `$menu/$submenu`
como em uma página normal do painel.

O código tentava reconstruir o catálogo durante o salvamento. O catálogo podia
ficar vazio e, com isso, os IDs dos menus marcados eram descartados.

Sintoma:

```text
Developer seleciona JoinChat/Site Kit
→ salva
→ modo Personalizado é salvo
→ menus selecionados não são persistidos
→ usuário continua sem menus adicionais
```

A V0.5.1 corrige isso armazenando um snapshot do catálogo em uma requisição
administrativa normal do Developer e usando esse snapshot no salvamento.

## Banco de dados

Não é necessário editar o banco manualmente.

A política individual é armazenada pelo WordPress em `wp_usermeta`.

O catálogo detectado é armazenado em `wp_options`.

## Interface

Ao carregar um Gerenciador do Cliente, a tela agora mostra:

```text
Ativos para este usuário
```

com os menus atualmente salvos para ele.

As caixas de seleção também permanecem marcadas após salvar/recarregar.

## Atualização automática

A V0.5.1 reforça o auto-update de duas maneiras:

1. mantém o BastionWP na lista nativa de plugins com auto-update;
2. usa `auto_update_plugin` somente para o BastionWP.

Quando uma nova Release é detectada e o auto-update está ativado, o BastionWP
também agenda uma execução do mecanismo nativo de background update.

O update ainda depende de WP-Cron e das permissões de escrita do servidor.
Portanto não é garantido que aconteça no mesmo segundo da publicação da Release,
mas não deve exigir que o Developer clique manualmente em "Atualizar agora".
