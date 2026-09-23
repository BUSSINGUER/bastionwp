# AGENTE BASTIONWP

## LEIA ESTE ARQUIVO ANTES DE ALTERAR O PROJETO

Este arquivo é o contexto mestre do projeto BastionWP.

Toda IA, agente ou desenvolvedor que trabalhar neste projeto deve ler este arquivo antes de criar, alterar, remover ou refatorar qualquer código.

Regra principal:

> NÃO recriar o plugin do zero quando o projeto já existir.

Antes de implementar qualquer coisa, inspecione a estrutura existente, identifique a versão atual e continue a partir dela.

## 1. Nome do projeto

```text
BastionWP
```

Descrição:

```text
Security & Site Control for WordPress
```

## 2. Propósito

BastionWP é um plugin de controle administrativo, hardening e padronização de segurança para sites WordPress entregues a clientes.

Ele NÃO é:

- antivírus;
- substituto do Wordfence;
- firewall completo;
- sistema de backup;
- plataforma SaaS nesta fase.

Seu papel é:

> orquestrar, padronizar, proteger e aplicar políticas administrativas e de segurança WordPress.

## 3. Contexto de uso

O projeto é utilizado por um desenvolvedor que cria e mantém sites WordPress para clientes.

Objetivos:

- manter controle técnico;
- reduzir risco de alterações indevidas;
- impedir clientes de modificar infraestrutura;
- padronizar entrega;
- aumentar segurança;
- reduzir tarefas repetitivas.

O cliente final deve continuar conseguindo administrar conteúdo.

## 4. Arquitetura obrigatória

Existem dois componentes próprios.

### BastionWP Control

Plugin normal:

```text
/wp-content/plugins/bastionwp/
```

Responsável por:

- UI;
- configurações;
- wizard;
- perfis;
- usuários;
- plugins protegidos;
- logs;
- diagnóstico;
- integrações.

### Bastion Core

MU Plugin:

```text
/wp-content/mu-plugins/bastion-core.php
```

Responsável por:

- enforcement;
- capabilities;
- proteção de acesso;
- Developer;
- Client Manager;
- políticas críticas.

Essa separação não deve ser removida sem justificativa arquitetural documentada.

## 5. Regra de segurança principal

NUNCA considerar `remove_menu_page()` ou CSS como proteção suficiente.

Proteção deve usar:

```text
capabilities
+
authorization
+
protected routes
+
Bastion Core
```

Ocultar menus é somente UX.

## 6. Papéis

### Developer

Usuário técnico autorizado.

Identificação principal:

```text
user_id
```

Não depender somente de username.

Pode:
- administrar infraestrutura;
- acessar BastionWP;
- acessar ferramentas técnicas;
- gerenciar plugins;
- gerenciar temas;
- executar manutenção.

### Client Manager

Role:

```text
bastion_client_manager
```

Pode:
- conteúdo;
- páginas;
- posts;
- mídia;
- recursos editoriais autorizados.

Não pode:
- plugins;
- temas;
- PHP;
- Code Snippets;
- Wordfence;
- BastionWP;
- configurações críticas;
- criar administradores;
- alterar Developer.

## 7. Decisões arquiteturais fixadas

### 7.1 Não usar "Super Admin" em single-site

Usar Developer como camada lógica própria.

### 7.2 Não executar PHP arbitrário

Nunca adicionar campo livre para código PHP ou `wp-config.php`.

### 7.3 Não depender de `wp-config.php` gravável permanentemente

Evitar permissões amplas desnecessárias.

### 7.4 Não criar backups sensíveis no webroot

Nunca copiar `wp-config.php` para arquivo temporário publicamente acessível.

### 7.5 Não congelar atualizações como padrão

Atualizações não devem ser desativadas globalmente sem motivo.

### 7.6 Não duplicar Wordfence

Não criar na V1:
- firewall próprio;
- malware scanner próprio;
- banco de assinaturas próprio;
- 2FA próprio.

### 7.7 Não criar backup próprio na V1

Detectar/integrar ferramenta externa.

### 7.8 Não criar Control Center agora

A V1 é local. Gestão central fica no roadmap.

## 8. Perfis oficiais

```text
Desenvolvimento
Staging
Produção
Produção Bloqueada
```

Não criar novos perfis sem atualizar documentação.

## 9. Padrão de Produção

Produção deve priorizar:

```text
DISALLOW_FILE_EDIT = true
WP_DEBUG = false
WP_DEBUG_DISPLAY = false
XML-RPC bloqueado quando possível
Application Passwords bloqueadas quando possível
PHP em uploads bloqueado
Directory Listing bloqueado
Developer protegido
Client Manager restrito
Wordfence ativo
Backup ativo
```

Produção Bloqueada também usa:

```text
DISALLOW_FILE_MODS = true
```

com aviso de impacto.

## 10. Integrações

### Wordfence

Obrigatória/recomendada no padrão.

Não incorporar o código do Wordfence.

Detectar, instalar/configurar quando apropriado e integrar status.

### Code Snippets

Não é dependência de segurança.

Se instalado:
- Developer acessa;
- Client Manager não acessa.

### ACF / CPT UI

Ferramentas técnicas que podem ser protegidas.

### Backup

Detectar solução externa.

### Cloudflare

V1:
```text
checklist/diagnóstico
```

Futuro:
```text
API
```

### MainWP

Futuro/alternativa de gestão central.

Não é requisito do core local.

## 11. Estrutura esperada

```text
bastionwp/
│
├── bastionwp.php
├── uninstall.php
│
├── includes/
│   ├── class-activator.php
│   ├── class-admin.php
│   ├── class-security.php
│   ├── class-capabilities.php
│   ├── class-users.php
│   ├── class-hardening.php
│   ├── class-plugins.php
│   ├── class-logs.php
│   ├── class-diagnostics.php
│   ├── class-profiles.php
│   └── class-mu-installer.php
│
├── integrations/
│   ├── class-wordfence.php
│   └── class-backup.php
│
├── mu/
│   └── bastion-core.php
│
├── admin/
│   ├── css/
│   ├── js/
│   └── views/
│
└── languages/
```

A estrutura pode evoluir, mas deve manter separação de responsabilidades.

## 12. Convenções de configuração

Opções sugeridas:

```text
bastionwp_settings
bastionwp_profile
bastionwp_developers
bastionwp_protected_plugins
bastionwp_version
```

Logs:

```text
wp_bastion_logs
```

Não usar `wp_options` para grandes volumes de logs.

## 13. Segurança obrigatória no código

Toda ação administrativa deve validar capability.

Usar:
```php
current_user_can(...)
```

e nonce apropriado.

Também:
- sanitização;
- escaping;
- validação;
- prepared statements;
- menor privilégio;
- CSRF protection;
- privilege escalation protection.

AJAX:
```text
nonce + capability + sanitização
```

REST futura:
```text
authentication + authorization + permission_callback
```

## 14. Nunca confiar apenas na interface

Se uma função está bloqueada:
- esconder menu;
- remover capability;
- bloquear rota;
- validar ação no backend.

Um usuário não pode contornar a proteção abrindo URL manualmente.

## 15. Fluxo da primeira instalação

```text
1. Instalar bastionwp.zip
2. Ativar
3. Diagnóstico
4. Instalar Bastion Core
5. Definir Developer
6. Criar/definir Client Manager
7. Escolher perfil
8. Configurar Wordfence
9. Configurar backup
10. Aplicar
11. Diagnóstico final
```

## 16. Fluxo obrigatório de atualização futura

ANTES DE ALTERAR O CÓDIGO:

1. ler `AGENTE_BASTIONWP.md`;
2. ler `PLANO_PROJETO_BASTIONWP.md`;
3. ler `ESPECIFICACAO_FUNCIONAL_BASTIONWP_V1.md`;
4. identificar a versão atual;
5. listar a estrutura atual;
6. localizar módulos relacionados;
7. entender o comportamento existente;
8. propor alteração mínima;
9. preservar compatibilidade;
10. implementar;
11. testar;
12. atualizar documentação;
13. incrementar versão.

## 17. Regra contra recriação

Se já existir:

```text
class-users.php
```

e a tarefa for alterar usuários, NÃO criar automaticamente:

```text
class-new-users.php
class-users-v2.php
novo-sistema-usuarios.php
```

Primeiro entender e modificar o módulo existente.

Se uma refatoração for necessária, documentar:
- motivo;
- o que será substituído;
- migração;
- impacto.

## 18. Compatibilidade

Não assumir ambiente idêntico em todos os sites.

Considerar:
- Apache;
- LiteSpeed;
- Nginx;
- Hostinger;
- diferentes versões PHP suportadas;
- plugins diferentes;
- WordPress atualizado;
- Cloudflare.

Funções dependentes do servidor devem:
- detectar compatibilidade;
- possuir fallback;
- informar estado real.

Nunca mostrar "protegido" sem validação.

## 19. Logs

Eventos críticos devem ser registrados.

Nunca armazenar:
- senhas;
- tokens completos;
- secrets;
- conteúdo sensível desnecessário.

Evitar dados pessoais excessivos.

## 20. Versionamento

Usar Semantic Versioning.

Roadmap:

```text
0.1.0 estrutura
0.2.0 usuários/capabilities
0.3.0 Bastion Core
0.4.0 hardening
0.5.0 interface
0.6.0 Wordfence
0.7.0 logs/diagnóstico
0.8.0 wizard
0.9.0 beta
1.0.0 stable
```

Toda mudança funcional relevante deve atualizar versão.

## 21. Política de remoção

Não remover Bastion Core automaticamente quando BastionWP for apenas desativado.

Remoção do Core deve exigir ação explícita.

## 22. Linguagem de segurança

Nunca afirmar:

```text
100% seguro
impossível invadir
proteção total
```

Preferir:

```text
protegido
hardening aplicado
configuração recomendada
risco reduzido
```

Segurança é redução de risco.

## 23. Control Center futuro

Não implementar agora.

Visão futura:

```text
Control Center
        |
        +-- Site A
        +-- Site B
        +-- Site C
```

Funções futuras:
- status;
- alertas;
- atualizações;
- backups;
- Wordfence;
- usuários;
- logs;
- políticas;
- diagnóstico.

A comunicação futura deverá usar autenticação forte e menor privilégio.

## 24. Checklist antes de commit importante

```text
[ ] Li AGENTE_BASTIONWP.md
[ ] Li o plano
[ ] Li a especificação
[ ] Entendi a versão atual
[ ] Não estou recriando módulo existente
[ ] Capabilities estão corretas
[ ] Nonces estão corretos
[ ] Inputs estão sanitizados
[ ] Outputs estão escapados
[ ] Nenhuma senha está em texto puro
[ ] Nenhum PHP arbitrário foi introduzido
[ ] Client Manager não ganhou privilégio indevido
[ ] Developer continua protegido
[ ] Bastion Core continua compatível
[ ] Logs não armazenam secrets
[ ] Testei URL direta
[ ] Testei privilege escalation
[ ] Atualizei documentação
[ ] Atualizei versão
```

## 25. Fonte de verdade

Em caso de conflito:

1. comportamento comprovado do código atual;
2. `AGENTE_BASTIONWP.md`;
3. especificação funcional;
4. plano de projeto;
5. histórico de conversa.

Se código e documentação divergirem, não assumir automaticamente que o código está correto. Investigar e documentar a diferença antes de alterar.

## 26. Estado atual

```text
Planejamento concluído.
Especificação funcional V1 criada.
BastionWP 0.1.1 aprovado em teste.
BastionWP 0.2.0 implementa Developer, Client Manager, capabilities e proteção de acessos.
Interface obrigatoriamente em português-BR.
Autor do plugin: Kaio Bussinguer.
Control Center adiado.
```

## 27. Próximo passo oficial

Criar:

```text
BastionWP 0.1.0
```

com:
- bootstrap;
- constantes;
- loader;
- ativação;
- estrutura de pastas;
- página administrativa inicial;
- versionamento;
- instalador inicial do Bastion Core.

Não iniciar funcionalidades avançadas antes da fundação estar estável.


## 28. Packaging decision introduced in 0.1.1

The bundled Bastion Core MUST NOT be stored as a `.php` file containing a
WordPress `Plugin Name` header inside the normal plugin package.

Reason: WordPress plugin discovery can scan PHP files in a plugin directory
and detect more than one plugin entry point.

Source template:

```text
/mu/bastion-core.stub
```

Installed target:

```text
/wp-content/mu-plugins/bastion-core.php
```

Do not revert this decision without testing the WordPress ZIP installation
and activation flow.


## 29. Regra de idioma e autoria

Todo texto visível ao usuário deve ser em português-BR, incluindo:

- descrição na lista de plugins;
- menus;
- telas;
- botões;
- avisos;
- mensagens de erro;
- textos do Bastion Core quando exibidos ao usuário.

Autor oficial exibido no plugin:

```text
Kaio Bussinguer
```

## 30. Estado da versão 0.2.0

Implementado:

- Developer Principal;
- role `bastion_client_manager`;
- capabilities iniciais;
- proteção de rotas técnicas;
- proteção de conta Developer;
- aba Acessos;
- enforcement correspondente no Bastion Core.

Próxima etapa planejada:

```text
0.3.0 — hardening e perfis iniciais
```


## 31. Decisão da versão 0.3.0 — controle granular de menus

O Client Manager não deve ter um bloqueio rígido de todos os plugins.

Modos oficiais:

```text
Bloqueio total
Personalizado
```

No modo Personalizado, o BastionWP detecta menus adicionais presentes no wp-admin do Developer e permite liberar grupos específicos.

Exemplos desejados:

```text
Site Kit
Joinchat
WooCommerce
outros plugins de operação do cliente
```

Áreas críticas que permanecem protegidas na V0.3.0:

```text
Plugins
Temas
Ferramentas
Configurações
Usuários
Atualizações
BastionWP
```

Não depender apenas da visibilidade. A whitelist deve combinar:

```text
menu visível
+
rota permitida
+
capability concedida somente na rota autorizada
```

Limitação conhecida:

Plugins que salvam configurações através de fluxos independentes como `options.php`, AJAX ou REST API podem exigir adaptadores específicos. Não conceder `manage_options` globalmente apenas para fazer um plugin funcionar.

## 32. Roadmap corrigido após 0.3.0

```text
0.3.0  controle granular de menus
0.4.0  hardening e perfis de segurança
0.5.0  interface/hardening ampliado
0.6.0  Wordfence
0.7.0  logs e diagnóstico
0.8.0  wizard
0.9.0  beta
1.0.0  stable
```


## 33. Decisão da versão 0.4.0 — sistema de atualização

Provider inicial:

```text
GitHub Releases público
```

Arquitetura obrigatória:

```text
BastionWP
  -> Update Manager
      -> GitHub Provider
```

Não espalhar chamadas à API GitHub por outros módulos.

Isso permite trocar futuramente:

```text
GitHub Provider
```

por:

```text
Bastion Server Provider
```

sem alterar a identidade do plugin.

Identidade técnica permanente:

```text
bastionwp/bastionwp.php
```

Não usar o `Source code.zip` automático do GitHub como pacote de atualização.

A Release deve possuir asset:

```text
bastionwp-X.Y.Z.zip
```

com a pasta raiz interna:

```text
bastionwp/
```

O repositório público não deve conter:

- senhas;
- tokens;
- API keys;
- credenciais de clientes;
- secrets.

A V0.4.0 usa a API pública de Releases sem token.

Auto-update usa o mecanismo nativo do WordPress (`auto_update_plugins`).

Após atualização do plugin, a versão nova deve sincronizar o Bastion Core no próximo carregamento, antes de registrar a nova versão como concluída.

Roadmap:

```text
0.4.0 sistema de atualização
0.5.0 hardening e perfis
0.6.0 Wordfence
0.7.0 logs e diagnóstico
0.8.0 wizard
0.9.0 beta
1.0.0 stable
```


## 34. INCIDENTE CRÍTICO — versões 0.3.0 e 0.4.0

### Sintoma

Ao ativar o plugin, o WordPress apresentou "Ocorreu um erro crítico neste site".

Na 0.4.0, em determinado teste, o site somente voltou após desativar/remover o Bastion Core de `mu-plugins`.

### Causa identificada

Havia um filtro:

```text
user_has_cap
```

que, dentro do próprio callback, executava novamente:

```text
user_can(...)
```

A chamada `user_can()` volta a resolver capabilities e pode disparar novamente `user_has_cap`.

Resultado:

```text
user_has_cap
  -> user_can
      -> user_has_cap
          -> user_can
              -> ...
```

Isso pode gerar recursão e esgotamento de memória.

### Regra permanente

NUNCA chamar dentro de um callback de `user_has_cap`:

```text
user_can()
current_user_can()
WP_User::has_cap()
```

para descobrir se o mesmo usuário possui outra capability.

Dentro desse filtro, usar o array `$allcaps` já fornecido pelo WordPress.

### Nova regra do MU Core

O Bastion Core deve ser mínimo.

Ele NÃO deve conceder capabilities dinâmicas para plugins de terceiros.

O Core deve manter somente:

- proteção do Developer;
- bloqueio de rotas técnicas críticas;
- ocultação de menus críticos;
- políticas estáveis e de baixo risco.

Concessões temporárias de capabilities para menus permitidos pertencem ao plugin principal.

### Testes futuros

`php -l` é obrigatório, mas NÃO é suficiente.

Antes de aprovar releases com hooks de capabilities:

- revisar chamadas recursivas;
- testar Developer;
- testar Client Manager;
- testar plugin principal ativo com Core ativo;
- testar plugin principal desativado com Core ativo;
- testar acesso direto às rotas;
- testar memória/fatal errors em staging.

### Recuperação emergencial

A partir da 0.4.1, o Core aceita:

```php
define('BASTIONWP_DISABLE_CORE', true);
```

no `wp-config.php`.

Também continua possível remover/renomear `wp-content/mu-plugins/bastion-core.php` via SFTP/SSH em emergência.


## 35. Decisão da versão 0.5.0 — acesso individual por usuário

Não criar perfis de acesso compartilhados nesta fase.

A configuração deve ser vinculada diretamente ao `user_id`.

Persistência:

```text
user_meta: bastionwp_access_mode
user_meta: bastionwp_allowed_menus
```

Cada Gerenciador do Cliente pode ter uma configuração distinta.

Exemplo:

```text
User ID 12 → Site Kit + JoinChat
User ID 19 → WooCommerce
User ID 24 → Bloqueio total
```

### Correção de menus de plugins

Problema observado:

```text
Developer marca JoinChat/Site Kit
→ salva
→ nenhum menu aparece para Client Manager
```

Causa arquitetural:

`add_menu_page()` registra o callback da página apenas quando
`current_user_can($capability)` é verdadeiro durante a construção do menu.

Portanto, alterar o `$menu` somente no final não é suficiente.

Nova regra:

Durante `admin_menu`, para um Client Manager em modo Personalizado:

```text
capabilities dos grupos permitidos
→ concedidas temporariamente
→ plugin registra menu/callback
```

Depois:

```text
menu permitido
→ capability visual alterada para read
```

E durante a rota explicitamente permitida:

```text
capabilities daquele grupo
→ concedidas somente nessa rota
```

Nunca persistir `manage_options` ou capability equivalente na role do cliente
apenas para fazer um plugin aparecer.

### AJAX/REST/options.php

Não conceder capabilities administrativas amplas genericamente nesses endpoints.

Se um plugin permitido abrir corretamente mas falhar ao salvar uma ação interna,
criar um adaptador específico e auditado para o plugin em vez de liberar
`manage_options` globalmente.

### Segurança permanente

Dentro de `user_has_cap`, continua proibido chamar:

```text
user_can()
current_user_can()
WP_User::has_cap()
```

Usar somente `$allcaps`, dados do usuário e configuração persistida.


## 36. Incidente V0.5.0 — catálogo vazio no admin-post

Problema:

`save_user_configuration()` chamava `build_catalog()` durante `admin-post.php`.

Nesse endpoint, `$menu/$submenu` não estão garantidamente montados.

Consequência:

```text
selected IDs
→ catálogo vazio
→ seleção descartada
```

Regra permanente:

- capturar catálogo em `admin_menu` no contexto do Developer;
- persistir snapshot validado;
- salvar seleção usando o snapshot;
- nunca depender de `$menu/$submenu` diretamente em `admin-post.php`.

Persistência:

```text
wp_options:
bastionwp_menu_catalog_snapshot

wp_usermeta:
bastionwp_access_mode
bastionwp_allowed_menus
```

### Auto-update

A partir da V0.5.1:

- manter opção nativa `auto_update_plugins`;
- usar `auto_update_plugin` apenas para BastionWP;
- quando uma nova Release for detectada e auto-update estiver ativo,
  agendar `wp_maybe_auto_update` para background;
- não implementar downloader/upgrader paralelo se o mecanismo nativo do
  WordPress puder realizar a atualização.


## 37. Versão 0.6.0 — Hardening por perfil

Perfis:

```text
development
staging
production
production_locked
```

Persistência:

```text
wp_options -> bastionwp_settings['profile']
```

### Regra de segurança

A V0.6.0 NÃO deve:

- editar wp-config.php automaticamente;
- criar backups de wp-config dentro do webroot;
- alterar chmod para tornar wp-config gravável;
- escrever regras Apache/Nginx sem detectar compatibilidade;
- usar código PHP arbitrário fornecido pelo usuário.

### Produção Bloqueada

Capacidades manuais removidas:

```text
install_plugins
activate_plugins
delete_plugins
update_plugins
edit_plugins
install_themes
switch_themes
delete_themes
update_themes
edit_themes
update_core
```

Exceções:

```text
WP-CLI
WP-Cron / background updates
```

Isso permite que o BastionWP continue atualizando automaticamente.

### user_has_cap

Dentro do filtro continua proibido chamar:

```text
user_can()
current_user_can()
WP_User::has_cap()
```

### Hardening server-side pendente

Implementar somente com detecção segura do servidor:

- bloqueio de PHP em uploads;
- directory listing;
- regras específicas de Apache/LiteSpeed;
- regras específicas de Nginx;
- proteção adicional de arquivos sensíveis.


## 38. Versão 0.7.0 — Integração Wordfence

Wordfence é integração externa, não dependência incorporada.

Identidade:

```text
slug: wordfence
plugin file: wordfence/wordfence.php
```

### O BastionWP pode

- detectar instalação;
- detectar ativação;
- instalar via API oficial do WordPress.org;
- ativar;
- controlar auto-update nativo;
- apresentar estado/checklist;
- proteger a área Wordfence contra Client Managers.

### O BastionWP NÃO deve

- copiar código do Wordfence;
- distribuir Wordfence dentro do ZIP do BastionWP;
- guardar licença Wordfence em código;
- alterar opções internas não documentadas;
- simular status de WAF otimizado com base apenas na existência de arquivo;
- contornar Produção Bloqueada para instalar plugins.

### Wordfence e Client Manager

Tratar como área técnica sempre bloqueada.

Não deve aparecer como opção no catálogo de menus delegáveis.

Bloquear páginas cujo `page`:

```text
começa com wordfence
é WFLS / começa com wfls_
```

tanto no plugin principal quanto no Bastion Core.

### Produção Bloqueada

Instalação/ativação manual do Wordfence deve retornar instrução para o Developer
mudar temporariamente para Produção.

Não abrir exceção silenciosa.
