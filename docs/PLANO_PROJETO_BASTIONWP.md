# BastionWP — Plano de Projeto

## 1. Visão geral

O BastionWP será uma camada de controle, hardening e administração protegida para sites WordPress entregues a clientes.

O objetivo principal é padronizar segurança, reduzir erros humanos e impedir que usuários de cliente alterem infraestrutura crítica, plugins, temas, código PHP, ferramentas técnicas ou configurações sensíveis.

A primeira versão será totalmente local em cada site WordPress. Não haverá painel central externo, SaaS ou API de gestão remota própria nesta fase.

No futuro, o projeto poderá evoluir para um Control Center centralizado capaz de gerenciar múltiplos sites.

## 2. Problema que o projeto resolve

A entrega segura de um site WordPress exige tarefas manuais repetitivas:

- instalar e configurar segurança;
- definir usuários e permissões;
- restringir acesso técnico;
- bloquear editores de código;
- revisar XML-RPC e Application Passwords;
- proteger uploads;
- revisar debug;
- configurar backup;
- revisar atualizações;
- proteger plugins técnicos;
- revisar administradores;
- configurar Cloudflare;
- acompanhar logs e diagnóstico.

O BastionWP deverá transformar isso em um processo padronizado, repetível e controlado.

## 3. Objetivos da V1

A V1 deverá:

1. impedir que clientes alterem infraestrutura;
2. padronizar a segurança dos projetos;
3. proteger plugins e ferramentas técnicas;
4. reduzir erros humanos;
5. permitir aplicar o padrão através de um único plugin instalável.

## 4. Arquitetura

### 4.1 BastionWP Control

Plugin convencional:

```text
/wp-content/plugins/bastionwp/
```

Responsabilidades:

- interface administrativa;
- dashboard;
- configuração de perfis;
- Developer e Client Manager;
- hardening;
- plugins protegidos;
- integração Wordfence;
- diagnóstico;
- logs;
- wizard inicial;
- instalação/atualização do Bastion Core.

### 4.2 Bastion Core

MU Plugin:

```text
/wp-content/mu-plugins/bastion-core.php
```

Responsabilidades:

- enforcement de políticas críticas;
- capabilities;
- proteção de acesso técnico;
- proteção do Developer;
- bloqueio de criação indevida de administradores;
- controle de XML-RPC;
- controle de Application Passwords;
- proteção de ferramentas técnicas;
- políticas de produção.

O Bastion Core deve permanecer carregado automaticamente mesmo que o plugin visual seja desativado.

## 5. Componentes externos

### Wordfence

Responsável por firewall, malware scanning, integridade de arquivos conhecidos, segurança de login, 2FA e detecção de vulnerabilidades.

O BastionWP deverá detectar, instalar/configurar e integrar o Wordfence, sem copiar seu código.

### Backup

A V1 não terá engine própria. Deverá detectar a solução utilizada e mostrar status quando possível.

### Cloudflare

Na V1: checklist/diagnóstico. Futuramente: integração por API.

### MainWP

Não obrigatório na V1. Pode ser usado futuramente para gestão central enquanto um Control Center próprio não existir.

## 6. Modelo de acesso

### Developer

Usuário WordPress existente marcado como Developer.

Terá:

- acesso técnico completo;
- acesso ao BastionWP;
- acesso às ferramentas protegidas;
- manutenção de plugins/temas;
- alteração de configurações críticas.

A identificação deve usar `user_id`, não apenas username.

### Client Manager

Role:

```text
bastion_client_manager
```

Permitido:

- páginas;
- posts;
- mídia;
- conteúdos personalizados autorizados;
- menus, se configurado;
- produtos/pedidos quando aplicável.

Bloqueado:

- instalar/ativar/excluir/editar plugins;
- instalar/trocar/excluir/editar temas;
- PHP e Code Snippets;
- Wordfence;
- BastionWP;
- ferramentas técnicas;
- criação/promoção de administradores;
- alteração de configurações críticas;
- modificação do Developer.

## 7. Regra central de segurança

Não depender apenas de ocultação visual.

A estratégia será:

```text
Interface oculta
+
Capabilities removidas
+
Rotas protegidas
+
Enforcement pelo Bastion Core
```

## 8. Perfis de ambiente

### Desenvolvimento

- debug permitido;
- modificações permitidas;
- plugins/temas permitidos;
- hardening menos agressivo.

### Staging

- debug controlado;
- cliente restrito;
- ferramentas técnicas permitidas;
- proteção intermediária.

### Produção

- `WP_DEBUG_DISPLAY` desativado;
- editor PHP bloqueado;
- XML-RPC bloqueado quando possível;
- 2FA para Developer;
- Wordfence ativo;
- backup ativo;
- cliente restrito;
- ferramentas técnicas protegidas.

### Produção Bloqueada

Tudo de Produção, mais:

- instalação/exclusão de plugins bloqueada;
- troca de temas bloqueada;
- criação de administradores bloqueada;
- snippets bloqueados;
- alterações críticas bloqueadas.

## 9. Hardening previsto

- `DISALLOW_FILE_EDIT`;
- `DISALLOW_FILE_MODS`;
- XML-RPC;
- Application Passwords;
- execução de PHP em uploads;
- directory listing;
- versão do WordPress;
- proteção de arquivos sensíveis;
- enumeração de usuários;
- criação de administradores;
- debug público;
- exposição de logs;
- proteção do Developer;
- plugins protegidos;
- sessões administrativas;
- comentários, quando não utilizados.

## 10. Plugins protegidos

Exemplos:

- Wordfence;
- Code Snippets;
- Advanced Custom Fields;
- CPT UI;
- WP Mail SMTP;
- plugin de backup;
- MainWP Child;
- BastionWP.

Para Client Manager, plugins protegidos não poderão ser vistos, configurados, ativados, desativados, excluídos ou editados.

## 11. Logs

Eventos mínimos:

- login/logout;
- acesso bloqueado;
- plugin ativado/desativado;
- usuário criado/excluído;
- role alterada;
- Developer alterado;
- perfil alterado;
- configuração Bastion alterada;
- tentativa de modificar Developer;
- tentativa de criar Administrator.

Usar tabela própria, por exemplo:

```text
wp_bastion_logs
```

## 12. Diagnóstico

Verificar:

- WordPress;
- PHP;
- HTTPS;
- Bastion Core;
- Wordfence;
- debug;
- XML-RPC;
- Application Passwords;
- PHP em uploads;
- permissões básicas;
- backup;
- plugins protegidos;
- administradores não autorizados;
- versão BastionWP.

Estados:

```text
OK
Atenção
Crítico
Não configurado
Indisponível
```

## 13. Estrutura sugerida

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

## 14. Persistência

Opções sugeridas:

```text
bastionwp_settings
bastionwp_profile
bastionwp_developers
bastionwp_protected_plugins
bastionwp_version
```

Logs não devem ficar em `wp_options`.

## 15. Segurança do próprio plugin

Obrigatório:

- `current_user_can()`;
- capabilities próprias;
- nonces;
- sanitização;
- escaping;
- prepared SQL;
- validação de tipos;
- proteção CSRF;
- proteção contra privilege escalation;
- sem PHP arbitrário;
- sem senhas em texto puro;
- sem dependência de username;
- sem backups sensíveis no webroot.

## 16. Fora do escopo da V1

Não desenvolver agora:

- Control Center externo;
- SaaS;
- API central;
- gestão de múltiplos sites;
- firewall próprio;
- malware scanner próprio;
- banco próprio de vulnerabilidades;
- antivírus próprio;
- backup próprio;
- 2FA próprio;
- atualização remota própria;
- Cloudflare API completa.

## 17. Sequência de desenvolvimento

1. especificação funcional;
2. bootstrap;
3. estrutura de classes;
4. ativação;
5. instalador Bastion Core;
6. Developer;
7. Client Manager;
8. capabilities;
9. proteção de rotas;
10. hardening;
11. perfis;
12. painel;
13. plugins protegidos;
14. Wordfence;
15. logs;
16. diagnóstico;
17. wizard;
18. testes;
19. beta;
20. versão estável.

## 18. Versionamento

```text
0.1.0  estrutura
0.2.0  usuários e capabilities
0.3.0  controle granular de menus
0.4.0  sistema de atualização
0.5.0  hardening e perfis
0.6.0  Wordfence
0.7.0  logs e diagnóstico
0.8.0  wizard
0.9.0  beta
1.0.0  primeira versão estável
0.6.0  Wordfence
0.7.0  logs e diagnóstico
0.8.0  wizard
0.9.0  beta
1.0.0  primeira versão estável
```

## 19. Roadmap futuro

### Fase 2

- Cloudflare API;
- integração de backup;
- monitoramento externo;
- notificações;
- relatórios;
- regras remotas.

### Fase 3

Control Center próprio para:

- listar sites;
- status e alertas;
- vulnerabilidades;
- backups;
- Wordfence;
- atualizações;
- usuários;
- políticas;
- logs;
- diagnóstico.

## 20. Regra de produto

BastionWP não deve duplicar soluções maduras.

Seu papel é:

> Orquestrar, padronizar e aplicar políticas de segurança e administração WordPress.

## 21. Definição de sucesso da V1

A V1 estará pronta quando:

- instalar via um único ZIP;
- instalar/validar Bastion Core;
- definir Developer;
- criar Client Manager;
- aplicar capabilities;
- proteger rotas;
- aplicar hardening;
- suportar perfis;
- proteger plugins técnicos;
- detectar Wordfence;
- registrar eventos;
- executar diagnóstico;
- impedir Client Manager de alterar infraestrutura;
- passar por staging e beta real.
