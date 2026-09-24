# Changelog

## 0.9.8

### Segurança e acesso temporário

- Removida a cópia ampla das capabilities da role Administrator durante acessos temporários.
- Acesso temporário passa a usar adaptadores explícitos; Site Kit é o primeiro adaptador suportado.
- `manage_options`, `unfiltered_html`, capacidades de plugins/temas/usuários e demais privilégios técnicos permanecem negados ao Gerenciador do Cliente.
- Rotas REST críticas do Code Snippets e Wordfence passam a ser bloqueadas também no Bastion Core.
- Capabilities individuais legadas são removidas ao converter ou migrar um Gerenciador do Cliente.
- Delegação genérica de menus passa a respeitar um teto de capabilities editoriais; menus técnicos exigem adaptador específico.
- A interface identifica menus que não podem ser delegados com segurança pelo mecanismo genérico.

### Bastion Core e atualização

- Bastion Core atualizado para 0.9.8.
- Status do Core agora diferencia ausente, inválido, desatualizado, integridade divergente, desabilitado, inativo e ativo.
- Instalador MU passa a respeitar `WPMU_PLUGIN_DIR`, usar temporário exclusivo, validar bytes/hash e preservar cópia anterior durante substituição.
- GitHub updater passa a aceitar somente o asset instalável esperado para a versão e rejeita source/backup/nomes ambíguos.
- Pacote extraído é validado por raiz, arquivo principal, nome, versão e Update URI antes da instalação.
- Hook pós-update reconhece formatos `plugin` e `plugins` e registra a versão encontrada no disco.
- Multisite é explicitamente não homologado nesta versão e a ativação é bloqueada nesse ambiente.

### Logs e auditoria

- Schema de logs atualizado para incluir usuário alvo e `request_id`.
- Detalhes de uma solicitação temporária passam a consultar o banco por solicitação e janela temporal antes da paginação.
- Aprovação, revogação e expiração passam a compor a timeline auditável da solicitação.
- Exportação CSV passa a respeitar filtros e percorrer todos os registros correspondentes por lotes.
- Corrigida a depreciação de `fputcsv()` no PHP 8.4 e adicionada proteção contra fórmulas em CSV.
- Schema, falhas de gravação e retenção passam a ser verificáveis pelo Status do Sistema.
- Histórico de solicitações foi limitado a 500 registros; estado ativo foi separado para evitar varredura do histórico em checks de capability.
- Textos deixam explícito que os logs mostram eventos registrados pelo BastionWP, e não um monitor geral de arquivos.

### Diagnóstico, Hardening e interface

- Diagnóstico consolidado incorpora Core, logs, Hardening, fonte de atualização, Wordfence, WAF, outros Administrators e escopo single-site.
- Restaurada a ação “Corrigir agora” para `display_errors` no Status do Sistema.
- Wizard deixa de bloquear conclusão por warnings opcionais do Wordfence e valida a fonte GitHub por Release instalável.
- Salvamentos de Hardening sem alteração deixam de gerar falso erro.
- XML-RPC passa a remover os métodos do WordPress quando bloqueado; textos esclarecem que o endpoint pode continuar respondendo com falha.
- Traduções deixam de ser solicitadas precocemente pelo bootstrap do Hardening.
- Filtros de Solicitações, busca Unicode e seleção do usuário convertido foram ajustados.
- Estados de integrações distinguem plugin ativo de configuração funcional validada.

### Limitações conhecidas

- O histórico de solicitações temporárias ainda utiliza uma option limitada e não possui transação por registro; uma migração futura para persistência individual continua recomendada para cenários de alta concorrência.
- O BastionWP não monitora alterações arbitrárias realizadas por SFTP/SSH, banco direto, processos externos ou arquivos de plugins de terceiros.
- Plugins que exigem capabilities técnicas como `manage_options` precisam de adaptadores específicos; não são delegados pelo catálogo genérico.


## 0.9.7

- Reorganizadas as seções Solicitações, Integrações, Acessos, Hardening e Diagnóstico.
- Solicitações agora usam layout em cards e linha de solicitação inspirado na referência visual.
- Botão “Ver detalhes” agora abre histórico auditável dos eventos registrados durante a janela do acesso temporário.
- Integrações agora usam layout com painel lateral e coluna de detalhes.
- Adicionados atalhos de integração para API REST, LiteSpeed, Yoast SEO, Elementor e Site Kit do Google.
- A seção Developer foi removida de Acessos e agrupada em uma nova área Sistema.
- Logs e Atualizações foram removidos do menu principal e agrupados em Sistema.
- Em Acessos, a conversão de usuário para Gerenciador do Cliente foi movida para o topo de Visão geral de usuários e permissões.
- Hardening deixou de exibir o diagnóstico interno/estado atual; esse conteúdo foi centralizado em Status do Sistema.
- O menu Diagnóstico foi renomeado visualmente para Status do Sistema.
- O ícone de Visão Geral passou para casinha e o Assistente para varinha mágica.


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
