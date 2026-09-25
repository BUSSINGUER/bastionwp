# BastionWP 0.9.9.3

Atualização do modelo de proteção de usuários, notificações e políticas individuais de administração.

A versão introduz três níveis explícitos de acesso para usuários do cliente:

- **WordPress Nativo** — Editor, Autor, Assinante e outras roles não administrativas seguem as permissões padrão do WordPress.
- **Cliente Protegido** — role BastionWP de menor privilégio, com Bloqueio total ou menus personalizados seguros.
- **Administrador Protegido** — Administrator real do WordPress por escolha explícita do Developer, com bloqueios BastionWP configuráveis por usuário.

O Developer Principal permanece separado desses níveis. Um Administrator não pode ser criado pelo modo WordPress Nativo dentro do BastionWP: para manter administração real, deve ser escolhida explicitamente a opção **Administrador Protegido**.

A seção **Proteção de acesso** agora concentra Usuários, Permissões e Solicitações. Solicitações pendentes e atualizações disponíveis também aparecem na nova central de notificações do cabeçalho.

O **Cliente Protegido** continua sem receber `manage_options` ou outra capability administrativa ampla para abrir plugins. Quando um plugin exige administração global, a interface orienta usar Administrador Protegido ou uma compatibilidade BastionWP futura. Nenhum adaptador novo foi criado nesta release.

A **Zona de risco** passa a usar um único desbloqueio temporário para Developer, fonte de atualização, desativação e remoção. A interface fica desfocada e o back-end continua exigindo Developer, nonce e estado de desbloqueio válido.

O Bastion Core foi atualizado para **0.9.9.3** porque recebeu enforcement mínimo das políticas do Administrador Protegido.

---

# BastionWP 0.9.9.2

Atualização de UX, Assistente, Segurança, Proteção de acesso e Integrações, preservando a política de menor privilégio da auditoria.

---

# BastionWP 0.9.9.1

Hotfix da 0.9.9 para corrigir erro fatal durante a sincronização do Bastion Core quando `wp_tempnam()` ainda não estiver carregada pelo WordPress.

---

# BastionWP 0.9.9

**Autor:** Kaio Bussinguer  
**Escopo homologado nesta versão:** WordPress single-site  
**Requisitos técnicos:** WordPress 6.5+ e PHP 8.1+

BastionWP combina controle administrativo, políticas de acesso, Hardening, diagnóstico, auditoria interna e integração com ferramentas externas. A versão 0.9.9 mantém a fronteira de segurança consolidada na 0.9.8 e reorganiza o produto em torno de um fluxo guiado de instalação, manutenção e handoff técnico.

## Primeira configuração

Em uma instalação nova, a primeira abertura do BastionWP direciona o Developer para o Assistente em modo foco. O fluxo revisa atualização do próprio BastionWP, plugins/integracões, usuários, permissões e Hardening antes da conclusão. O Assistente pode ser pausado sem bloquear o restante do WordPress.

O preflight de Hardening tenta identificar quando uma proteção já está efetivamente aplicada por outra origem conhecida. Quando a origem puder ser identificada, o Developer pode manter a regra externa ou permitir que o BastionWP também a gerencie. O BastionWP não altera silenciosamente configurações internas de plugins de terceiros.

## Sistema

A área Sistema centraliza atualização, logs e Zona de risco. A tela padrão de Plugins não oferece a ação normal de desativação do BastionWP enquanto ele estiver ativo; o desligamento controlado ocorre pela Zona de risco, que remove o Bastion Core antes de desativar o plugin principal.

A remoção completa oferece um fluxo de handoff: os Gerenciadores do Cliente são convertidos para uma role escolhida somente depois que a exclusão física do plugin é confirmada. A limpeza opcional de dados continua separada da simples desativação.

A fonte de atualização GitHub permanece bloqueada e mascarada até desbloqueio explícito. O updater continua validando o pacote antes da instalação.

## Acessos

A aba Usuários lista todas as contas do site, exceto o Developer Principal, e mostra quais estão ou não sob gerenciamento BastionWP. A área Permissões permite escolher WordPress Nativo, Cliente Protegido ou Administrador Protegido e configura os controles correspondentes ao nível selecionado.

Menus que dependem de capabilities administrativas amplas não são liberados ao Cliente Protegido pelo catálogo genérico. Para administração completa de plugins, o Developer pode escolher explicitamente Administrador Protegido; compatibilidades específicas continuam como evolução futura.

## Fronteira de segurança

O Gerenciador do Cliente permanece com uma role editorial controlada. Capabilities técnicas como `manage_options`, instalação/edição de plugins e temas, administração de usuários e `unfiltered_html` continuam negadas. O acesso temporário não transforma o cliente em Administrator e continua baseado em adaptadores explícitos.

Contas WordPress que permanecem com a role nativa `administrator`, acesso ao banco, SFTP/SSH ou capacidade de executar PHP permanecem acima da fronteira que um plugin WordPress pode garantir.

## Auditoria e atualizações

Logs continuam registrando eventos emitidos pelo próprio BastionWP, não um monitoramento geral de arquivos. A visão geral do Sistema apresenta os 10 eventos mais recentes e a aba Logs mantém filtros, exportação, paginação e limpeza.

O updater usa GitHub Releases, rejeita pacotes source/backup ambíguos e valida identidade/estrutura do instalável. Atualizações manuais podem ser instaladas dentro do próprio BastionWP.

## Bastion Core

O Bastion Core está na versão interna 0.9.9.3 nesta release porque passou a aplicar, mesmo como MU plugin, o teto mínimo das políticas do Administrador Protegido.

## Áreas administrativas

- Visão Geral
- Assistente
- Proteção de acesso
- Segurança
- Integrações
- Status do Sistema
- Sistema

## Limitações conhecidas

- Multisite não é homologado e a ativação continua bloqueada nesse ambiente.
- A detecção da origem de uma regra de Hardening é best-effort; configurações de servidor, CDN, proxy, código personalizado ou plugins desconhecidos podem aparecer como origem não identificada.
- O histórico de solicitações temporárias ainda utiliza persistência limitada e não possui transação individual por registro.
- Adaptadores de delegação específicos ainda não existem para todo plugin que utiliza capabilities técnicas.
- A remoção controlada depende de permissões de filesystem suficientes para que o WordPress exclua os arquivos do plugin.

Consulte `CHANGELOG.md` para o histórico detalhado.

---

