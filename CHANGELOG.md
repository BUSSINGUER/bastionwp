# Changelog

## 0.5.0

- Configuração de menus passa a ser individual por usuário.
- Removido conceito de política global compartilhada para Client Managers.
- Adicionado seletor de usuário na tela Acessos.
- Cada usuário pode usar Bloqueio total ou Personalizado.
- Corrigido fluxo de registro de menus de plugins terceiros.
- Capabilities selecionadas são concedidas temporariamente durante `admin_menu`.
- Capabilities do plugin são concedidas novamente somente nas rotas autorizadas.
- Nenhuma capability administrativa ampla é persistida na role do cliente.
- Configuração antiga é migrada para os usuários existentes.
- Bastion Core atualizado para 0.5.0 e continua propositalmente mínimo.

## 0.4.1

- Corrigida recursão crítica em `user_has_cap`.
- Bastion Core simplificado.
- Adicionado modo de emergência do Core.

## 0.4.0

- Sistema de atualização via GitHub Releases.

## 0.3.0

- Primeira versão do controle granular de menus.

## 0.2.0

- Developer Principal e Gerenciador do Cliente.

## 0.1.1

- Correção do pacote instalável.

## 0.1.0

- Fundação inicial.
