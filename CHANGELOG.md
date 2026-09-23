# Changelog

## 0.8.1

- Corrigido erro crítico `BastionWP::$diagnostics must not be accessed before initialization`.
- Corrigida inicialização de `BastionWP_Diagnostics`.
- Removido carregamento duplicado de `class-logger.php`.
- Removida reescrita do slug principal de menus delegados.
- Site Kit passa a usar exclusivamente seu modelo nativo de permissões.
- Adicionado modo `native_permissions_only` para Site Kit.
- BastionWP não concede capabilities artificiais do Site Kit.
- Rotas view-only do Site Kit (`dashboard` e `splash`) passam a ser reconhecidas pela política Bastion.
- Adicionada orientação de Dashboard Sharing na tela de Acessos.
- Bastion Core atualizado para 0.8.1.

## 0.8.0

- Logs e diagnóstico ampliado.

## 0.7.1

- Hardening dinâmico e tentativa inicial de correção de entry_slug.

## 0.7.0

- Integração Wordfence.
