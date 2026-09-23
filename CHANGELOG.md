# Changelog

## 0.9.6

- Redesenhada a seção Acessos com foco em usabilidade.
- Adicionado overview geral de usuários gerenciados e suas permissões.
- Nova ordem da seção: Usuários > Gerenciar usuário > Developer.
- Alterado botão “Carregar usuário” para “Selecionar usuário”.
- Alterado título “Ativos para este usuário” para “Menus habilitados para este usuário”.
- Menus habilitados passam a usar destaque verde.
- Cards Bloqueio total e Personalizado receberam diferenciação visual, cor e ícone.
- Menus adicionais detectados passam a usar sliders em vez de checkbox visual.
- Developer Principal movido para Zona de risco com bloqueio visual e confirmação antes de editar.
- Redesenhada a seção Hardening.
- Perfis Desenvolvimento, Staging, Produção e Produção Bloqueada aparecem em uma única linha quando houver espaço.
- Cada perfil recebeu ícone, título, descrição e indicador visual de seleção.
- Regras do perfil selecionado ficam à esquerda.
- O que muda ao aplicar o perfil fica à direita.
- Controles independentes do perfil foram movidos para uma seção própria abaixo.
- Estado atual foi movido para o final da página.
- Adicionado submenu interno: Perfil de Hardening, Ajustes adicionais e Estado atual.
- Nenhuma regra de segurança, capability, banco de dados, integração ou Bastion Core foi alterada.


## 0.9.5

- Corrigida a estrutura dos cabeçalhos das seções administrativas.
- Cabeçalhos voltam a ocupar a área superior, acima do conteúdo de cada aba.
- Corrigido aninhamento visual introduzido na 0.9.4.
- Redesenhada a seção Assistente seguindo o dashboard visual do BastionWP.
- Assistente passa a usar hero superior com progresso.
- Adicionado painel de Etapas de configuração.
- Adicionado Resumo do progresso em painel lateral.
- Adicionada Próxima recomendação dinâmica.
- Reorganizada a conclusão da configuração inicial.
- Reorganizado o card de acesso ao Site Kit.
- Mantidos os dados, formulários, nonces, actions e regras funcionais existentes.
- Nenhuma alteração em segurança, capabilities, banco, Hardening, Wordfence, Site Kit ou Bastion Core.


## 0.9.4

- Reestruturada exclusivamente a aba Visão Geral.
- Visão Geral passa a funcionar como dashboard consolidado do BastionWP.
- Adicionados cards de Bastion Core, WordPress/Ambiente, Controle de Acesso, Hardening, Atualizações e Diagnóstico.
- Adicionado painel amplo de Controle de usuários e permissões.
- Adicionado painel de Ações rápidas.
- Adicionado painel de Atividade recente usando os logs existentes do BastionWP.
- Adicionado rodapé contextual da Visão Geral.
- Todos os cards principais passam a exibir ícones ao lado esquerdo do título.
- Mantido o Design System, cores, tokens e componentes visuais da versão anterior.
- Nenhuma regra de negócio, capability, integração, banco de dados, hardening ou Bastion Core foi alterada.


## 0.9.3

- Atualização exclusivamente visual do painel administrativo.
- Implementado novo Design System do BastionWP.
- Adicionados tokens visuais centralizados.
- Novo cabeçalho global do plugin.
- Nova navegação por abas com ícones.
- Novo hero contextual por seção.
- Cards, botões, formulários, tabelas e estados visuais padronizados.
- Melhorias de responsividade.
- Melhorias visuais nas áreas Assistente, Acessos, Solicitações, Hardening, Integrações, Diagnóstico, Logs e Atualizações.
- Adicionadas ferramentas visuais de busca/seleção na lista de menus.
- Nenhuma regra de segurança, capability, banco de dados, hardening, updater, integração ou Bastion Core foi alterada.


## 0.9.2

- Adicionado sistema global de solicitações administrativas temporárias.
- Adicionado menu Administrador para Gerenciadores do Cliente.
- Solicitações são enviadas por e-mail ao Developer.
- Adicionada aba Solicitações no BastionWP.
- Aprovação por 30 minutos, 1 hora ou 2 horas.
- Adicionadas ações Negar e Encerrar agora.
- Expiração automática baseada em timestamp.
- Capabilities administrativas são concedidas somente em runtime.
- Áreas técnicas críticas permanecem protegidas durante o acesso temporário.
- Adicionado switch para desabilitar comentários.
- Adicionado switch para ocultar menu Painel dos Gerenciadores do Cliente.
- Produção/Produção Bloqueada ocultam Painel por padrão quando não existe override.
- Adicionado switch para suprimir `display_errors`.
- Corrigido diagnóstico de `WP_DEBUG_DISPLAY / display_errors`.
- Adicionado botão “Corrigir agora” quando display_errors está efetivamente ativo.
- Diagnóstico passa a explicar correção definitiva no wp-config.php.
- Bastion Core atualizado para 0.9.2.

## 0.9.1

- Hotfix crítico do Wizard.

## 0.9.0

- Assistente inicial e correção do Site Kit.

## 0.8.1

- Hotfix crítico do Diagnostics.
