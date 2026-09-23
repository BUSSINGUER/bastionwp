# Changelog

## 0.5.1

- Corrigido salvamento dos menus individuais.
- Catálogo de menus agora é persistido como snapshot no painel do Developer.
- `admin-post.php` não tenta mais reconstruir `$menu/$submenu`.
- Seleções individuais permanecem salvas em `wp_usermeta`.
- Adicionada área "Ativos para este usuário".
- Checkboxes permanecem selecionados ao carregar o usuário.
- Reforçado auto-update específico do BastionWP com `auto_update_plugin`.
- Ao detectar nova Release com auto-update ativo, agenda background update nativo.
- Bastion Core atualizado para 0.5.1.

## 0.5.0

- Configuração individual de menus por usuário.
- Correção inicial de capabilities durante `admin_menu`.

## 0.4.1

- Correção de recursão crítica em `user_has_cap`.

## 0.4.0

- Sistema de atualização via GitHub Releases.

## 0.3.0

- Primeira versão do controle granular de menus.

## 0.2.0

- Developer Principal e Gerenciador do Cliente.
