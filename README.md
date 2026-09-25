# BastionWP 0.9.9.4

Atualização da camada de segurança HTTP, monitoramento e compatibilidade escopada para plugins administrativos.

A versão 0.9.9.4 preserva o modelo de níveis de acesso da 0.9.9.3 e adiciona dois blocos principais:

- **Camada adicional de Segurança** — cabeçalhos HTTP, CSP Report-Only, HSTS gradual, REST API, proteção local de login, cache privado, integridade PHP, DNS/TLS e alertas.
- **Compatibilidade BastionWP para Cliente Protegido** — permite habilitar plugins administrativos quando o BastionWP consegue delimitar as requisições pertencentes ao plugin, sem transformar o usuário em Administrator e sem conceder `manage_options` globalmente.

WAF de borda, métricas de CDN/servidor e remoção de headers definidos depois do PHP continuam tratados como integrações/estado externo. O BastionWP não apresenta essas proteções como ativas sem evidência disponível no ambiente.

O Bastion Core permanece na versão interna **0.9.9.3**, pois esta release não alterou o enforcement do MU plugin.

---

# BastionWP 0.9.9.3

A versão 0.9.9.3 introduziu os níveis WordPress Nativo, Cliente Protegido e Administrador Protegido, a central de notificações, Solicitações dentro de Proteção de acesso e o desbloqueio único da Zona de risco.

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

Menus que dependem de capabilities administrativas amplas não são liberados globalmente ao Cliente Protegido. Na 0.9.9.4, o Developer pode habilitar Compatibilidade BastionWP quando a origem do plugin é reconhecida; nesse caso, a capability administrativa é concedida somente dentro das operações identificadas daquele plugin. Quando a fronteira não puder ser delimitada com segurança, use Administrador Protegido.

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

