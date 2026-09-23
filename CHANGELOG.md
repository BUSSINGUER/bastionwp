# Changelog

## 0.6.0

- Adicionada aba Hardening.
- Adicionados perfis Desenvolvimento, Staging, Produção e Produção Bloqueada.
- Bloqueio de editores de arquivos por capability.
- Bloqueio opcional de XML-RPC por perfil.
- Bloqueio opcional de Application Passwords por perfil.
- Remoção do generator WordPress em perfis protegidos.
- Mensagens de erro de login genéricas.
- Bloqueio da listagem pública de usuários pela REST API.
- Supressão de display_errors em Produção quando possível.
- Produção Bloqueada restringe alterações manuais de plugins, temas e core.
- Background auto-updates continuam permitidos em Produção Bloqueada.
- Adicionado diagnóstico do ambiente.
- Bastion Core atualizado para 0.6.0 sem adicionar lógica dinâmica de hardening.

## 0.5.1

- Corrigido salvamento dos menus individuais.
- Reforçado auto-update.

## 0.5.0

- Acesso individual por usuário.

## 0.4.1

- Correção de recursão crítica em user_has_cap.

## 0.4.0

- Atualização via GitHub Releases.
