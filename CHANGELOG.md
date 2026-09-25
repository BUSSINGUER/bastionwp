# Changelog

## 0.9.9.6

- Adicionada autenticação adicional do Developer para abrir e operar o BastionWP, usando a senha atual do WordPress sem armazená-la.
- Sessão BastionWP vinculada à sessão WordPress, com 15 minutos de inatividade e limite absoluto de 60 minutos.
- Adicionado bloqueio manual da sessão e renovação de inatividade somente mediante atividade no painel autenticado.
- Handlers administrativos do BastionWP passam a exigir capability, nonce e sessão Bastion autenticada.
- Corrigido o self-update: a atualização interna preserva/restaura o estado ativo do plugin e valida a versão em disco antes do redirect.
- Zona de risco removida da navegação superior de Sistema; permanece somente no final da Visão geral.
- REST API redesenhada com três modos visuais, impacto explícito e allowlist baseada nos namespaces observados.
- Adicionado dashboard visual de Monitoramento com integridade PHP, login, requisições, HTTP, DNS e TLS.
- Alertas passam a suportar Aberto, Reconhecido, Resolvido e Ignorado.
- Adicionados Ajustes personalizados seguros: headers HTTP permitidos, namespaces REST que exigem autenticação e CSS administrativo do BastionWP.
- Execução arbitrária de PHP continua não suportada.


## 0.9.9.5

- Reorganizada a área Segurança em navegação lateral: Estado de segurança, WordPress e arquivos, Login e sessão, HTTP e navegador, REST e APIs, Monitoramento e Ajustes adicionais.
- Corrigidos alinhamentos dos botões da Saúde do ambiente, cards do Assistente, níveis de acesso e menus adicionais.
- Menus adicionais passam a usar duas colunas consistentes: informações à esquerda e switch do Cliente Protegido à direita.
- Corrigida a escala tipográfica dos cards de solicitações temporárias e indicadores de Integrações.
- Perfil de Segurança efetivamente aplicado recebe identificação visual fixa, independente do perfil apenas selecionado para prévia.
- Switches de Segurança foram reduzidos, alinhados e passam a utilizar verde quando ativos, sem ícone de check do checkbox nativo.
- Adicionados tooltips contextuais para REST API, HSTS, CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy e Permissions-Policy.
- A interface de conflitos passa a separar Política do perfil, Estado efetivo e Responsável detectado, mantendo “Origem não identificada” quando a origem não pode ser provada.
- Adicionada detecção informativa de provedores conhecidos de 2FA sem afirmar que o 2FA está configurado quando isso não pode ser validado.
- Criado o sistema de Backups de configuração em Sistema, com snapshots técnicos de arquivos explicitamente suportados, SHA-256, ator, motivo e versão do BastionWP.
- Snapshots suportam criação manual, comparação com redação de linhas sensíveis, restauração atômica com validação de hash e exclusão.
- Retenção padrão limitada a 5 snapshots por arquivo.
- Bastion Core passa a exigir snapshot antes de substituição ou remoção quando o arquivo já existe.
- wp-admin/* e wp-login.php não são alvos permitidos do mecanismo de escrita/backup do BastionWP.
- Limpeza completa opcional do BastionWP também remove os snapshots técnicos.
- Autenticação própria do BastionWP não foi ativada nesta versão; permanece planejada para a próxima etapa.


## 0.9.9.4

- Nova camada de Segurança em Ajustes adicionais, com controles de cabeçalhos HTTP, REST API, login, cache privado e monitoramento.
- Adicionado rate limiting local para login como segunda camada contra brute force/credential stuffing; WAF de borda continua tratado como proteção externa recomendada.
- Adicionada detecção de proxy Cloudflare, Wordfence/WAF local e disponibilidade de scanner especializado, sem afirmar configuração que o WordPress não consegue comprovar sozinho.
- Adicionados cabeçalhos configuráveis: HSTS gradual, CSP Report-Only/efetiva, X-Content-Type-Options, Referrer-Policy, Permissions-Policy e X-Frame-Options como compatibilidade.
- Remoção best-effort de X-Powered-By e X-XSS-Protection; Server/Apache/nginx permanece classificado como responsabilidade de servidor/CDN.
- CSP Report-Only passa a receber relatórios em endpoint próprio do BastionWP e listar origens observadas antes de enforcement.
- Adicionado reforço de no-cache/no-store/private para wp-admin, login e sessões autenticadas quando o WordPress controla a resposta.
- REST API ganha inventário de namespaces, origem de plugin quando identificável e três modos: observar, proteção recomendada e allowlist avançada.
- Adicionados alertas para novo Administrator, plugin instalado, alterações PHP, PHP em uploads, picos locais de requisição, muitos 403/404/5xx, excesso de falhas de login, mudança de nameserver/DNS e certificado TLS próximo da expiração.
- Central de notificações passa a incluir alertas de segurança com resolução manual.
- Integridade PHP usa baseline de hashes e é reinicializada após atualizações conhecidas para reduzir falsos positivos.
- Criado mecanismo de Compatibilidade BastionWP para menus administrativos bloqueados do Cliente Protegido.
- Compatibilidade pode ser habilitada pelo Developer quando o BastionWP consegue identificar a origem do plugin e a capability não é uma operação de infraestrutura proibida.
- Capabilities administrativas, como manage_options, são concedidas apenas no contexto reconhecido daquele plugin (menu/admin page, AJAX, admin-post ou REST), nunca globalmente ao Cliente Protegido.
- Compatibilidades que não podem ser delimitadas com segurança continuam exigindo Administrador Protegido.
- 26 arquivos da base 0.9.9.3 permanecem byte a byte inalterados; somente arquivos inerentes à atualização foram modificados/adicionados.


## 0.9.9.3

- Novo modelo de níveis de usuário: WordPress Nativo, Cliente Protegido e Administrador Protegido.
- Administrator passa a exigir escolha explícita de Administrador Protegido na configuração BastionWP.
- Administrador Protegido utiliza a role Administrator real, com proteções individuais configuráveis por sliders.
- Adicionados bloqueios individuais para editor PHP, Code Snippets, instalação/exclusão/ativação de plugins e temas, usuários, atualizações, BastionWP e outros administradores.
- Bastion Core atualizado para 0.9.9.3 e passa a aplicar as proteções essenciais do Administrador Protegido mesmo se o plugin principal estiver indisponível.
- Cliente Protegido mantém a política de menor privilégio e não recebe capabilities administrativas globais para abrir plugins incompatíveis.
- Menus administrativos incompatíveis agora orientam o uso de Administrador Protegido ou compatibilidade BastionWP futura, sem redirecionamento enganoso para Integrações.
- Solicitações foram movidas para dentro de Proteção de acesso como terceiro accordion; links antigos continuam compatíveis.
- Adicionada central de notificações no cabeçalho para solicitações temporárias pendentes e novas versões do BastionWP.
- Atualizações podem ser dispensadas por versão na central de notificações.
- Zona de risco passa a utilizar um único desbloqueio temporário e server-side para todas as ações sensíveis.
- Corrigido o modelo de estado de Segurança para diferenciar política do perfil, responsável pela proteção e estado efetivo, incluindo Application Passwords.
- Preflight deixa de atribuir callbacks genéricos do WordPress como origem externa conhecida.
- Switches de Ajustes adicionais e proteções individuais foram ampliados para leitura visual mais clara.
- Cards de usuários passaram a usar colunas alinhadas por nível, política e acesso.
- Botões dos cards da Saúde do ambiente receberam alinhamento vertical centralizado.


## 0.9.9.2

- Corrigida validação de tags de Release para versões hotfix com quatro blocos, como 0.9.9.1.
- Assistente passa a listar pendências impeditivas e tratar atualização GitHub como recomendação não bloqueante.
- Adicionado hub de retomada/reinício quando o Assistente é pausado.
- Corrigido espaçamento do título de boas-vindas e criado ícone visual de varinha mágica.
- Hardening renomeado na interface para Segurança e Acessos para Proteção de acesso.
- Perfis recebem nomes mais intuitivos e tags de intensidade; IDs internos permanecem os mesmos.
- Regras de Segurança passam a separar estado atual e resultado do perfil selecionado.
- Etapa de Segurança do Assistente recebe prévia dinâmica completa das regras selecionadas.
- Avatar das Solicitações passa a usar o avatar real do WordPress.
- Saúde do ambiente recebe Novidades da versão, versões BastionWP/Core e remove Ações rápidas.
- Integrações corrigidas para layout lateral real e nomenclatura de compatibilidade de acesso mais clara.
- Política de menor privilégio preservada: plugins administrativos continuam sem grants globais; nenhum novo adaptador foi criado.
- Removido o botão Executar diagnóstico do cabeçalho superior.


## 0.9.9.1

- Hotfix crítico de compatibilidade do instalador Bastion Core.
- Corrigida chamada direta a `wp_tempnam()` durante migrações executadas no `init`.
- O instalador agora carrega a API de arquivos do WordPress quando necessário.
- Adicionado fallback defensivo para criação de arquivo temporário.
- Corrigidas as duas ocorrências: preparação do Bastion Core e validação opcional de sintaxe.
- Nenhuma regra de segurança, capability, acesso, Hardening, updater, onboarding ou interface da 0.9.9 foi alterada.


## 0.9.9

### Assistente de primeira instalação

- Adicionado onboarding em modo foco na primeira abertura do BastionWP após uma instalação nova.
- Nova tela de boas-vindas com resumo do produto e início guiado.
- Primeira etapa verifica a Release mais recente e tenta atualizar o BastionWP antes da configuração.
- Varredura de plugins mostra integrações de segurança detectadas e permite instalar/ativar Wordfence pelo fluxo oficial do BastionWP.
- Etapas de usuários e permissões permitem converter Gerenciadores do Cliente e definir políticas antes da conclusão.
- Hardening recebe preflight para detectar proteções externas conhecidas antes de aplicar o perfil.
- O preflight permite manter uma proteção externa detectada ou passar a regra para o BastionWP sem editar silenciosamente a configuração de outro plugin.
- O Assistente pode ser pausado e retomado depois.

### Sistema e handoff técnico

- A Zona de risco foi movida para o final da página Sistema.
- Developer Principal e Fonte de atualização passam a ocupar cards separados dentro da Zona de risco.
- Fonte GitHub fica bloqueada e mascarada até desbloqueio explícito.
- Atualizações mostram versão instalada, versão mais recente e estado desatualizado/atualizado.
- Adicionado botão para instalar uma atualização do BastionWP sem sair da tela do plugin.
- A ação padrão “Desativar” da tela Plugins é removida enquanto o BastionWP está ativo.
- Desativação controlada passa a remover o Bastion Core antes de desligar o plugin principal.
- Adicionado fluxo de remoção/handoff que exclui o plugin somente após confirmação e converte Gerenciadores do Cliente para uma role escolhida.
- Se a exclusão física falhar, o BastionWP tenta restaurar plugin/Core sem converter os usuários.
- A proteção contra desativação externa permite os contextos internos de atualização, WP-Cron e WP-CLI para não bloquear o mecanismo nativo de updates.
- Logs mostram os 10 eventos mais recentes na visão geral e o histórico completo em uma aba dedicada, com paginação.

### Acessos e compatibilidade

- A seção Usuários lista todos os usuários do site, exceto o Developer Principal.
- Cada usuário mostra se está ou não sob gerenciamento BastionWP, política aplicada e menus delegados quando aplicável.
- Usuários não gerenciados podem receber proteção diretamente pelo overview.
- A seção Permissões foi transformada em accordion independente, com somente uma seção aberta por vez.
- Bloqueio total desativa visual e funcionalmente a área de menus adicionais; Personalizado habilita a seleção.
- A mensagem técnica de adaptadores foi substituída por orientação amigável e link para Integrações.
- Menus que exigem capabilities técnicas continuam não delegáveis pelo modo genérico; a validação também ocorre no back-end.

### Integrações e Hardening

- Integrações passa a funcionar também como catálogo de compatibilidade de acesso.
- Site Kit identifica compatibilidade BastionWP disponível; plugins técnicos sem adaptador permanecem bloqueados para delegação genérica.
- Wordfence continua explicitamente bloqueado para Gerenciadores do Cliente.
- Hardening passa a respeitar decisões de propriedade das regras detectadas no preflight.
- Fontes externas são apresentadas apenas quando puderem ser identificadas com segurança; origem desconhecida permanece descrita como não identificada.

### Escopo de segurança

- As correções de segurança da versão 0.9.8 permanecem preservadas.
- O Bastion Core continua na versão interna 0.9.8 porque não houve mudança funcional no Core nesta release.
- WordPress multisite continua não homologado.


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
