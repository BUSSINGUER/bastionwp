# BastionWP — Especificação Funcional V1

## 1. Objetivo

Este documento define o comportamento funcional da primeira versão do BastionWP.

A implementação deve seguir este documento. Alterações de escopo devem ser registradas antes de alterar código.

## 2. Navegação principal

```text
BastionWP
├── Visão Geral
├── Segurança
├── Acessos
├── WordPress
├── Plugins
├── Logs
├── Backup
├── Diagnóstico
└── Configurações
```

Somente Developer poderá acessar o BastionWP.

## 3. Visão Geral

Mostrar rapidamente o estado de segurança.

### Cards

**Bastion Core**
- Ativo
- Ausente
- Erro
- Versão incompatível

Ações:
```text
Instalar / Reparar / Atualizar
```

**WordPress**
- versão;
- atualização disponível;
- ambiente.

**PHP**
- versão atual;
- compatibilidade;
- estado.

**HTTPS**
- ativo;
- problema;
- não detectado.

**Wordfence**
- instalado;
- ativo;
- firewall;
- 2FA Developer;
- último scan, se disponível.

**Backup**
- solução detectada;
- status;
- último backup, se disponível.

**Hardening**
- configurações corretas;
- alertas;
- falhas.

Estados visuais:

```text
OK
Atenção
Crítico
Não configurado
Indisponível
```

Não usar score numérico na V1.

## 4. Segurança

### 4.1 Desativar editor de arquivos PHP

Label:
```text
Desativar editor de arquivos PHP
```

Defaults:
```text
Produção: ON
Produção Bloqueada: ON
Desenvolvimento: configurável
```

Efeito:
```php
define('DISALLOW_FILE_EDIT', true);
```

Critérios:
- editor de tema inacessível;
- editor de plugin inacessível;
- URL direta também bloqueada.

### 4.2 Bloquear modificações de plugins e temas

Label:
```text
Bloquear instalação, exclusão e atualização de plugins/temas
```

Defaults:
```text
Produção: OFF
Produção Bloqueada: ON
```

Efeito:
```php
define('DISALLOW_FILE_MODS', true);
```

A interface deve informar que isso também afeta atualizações via painel.

### 4.3 XML-RPC

Label:
```text
Bloquear XML-RPC
```

Padrão:
```text
ON
```

Exceção: integração dependente de XML-RPC.

Critérios:
- métodos autenticados bloqueados;
- pingbacks revisados;
- diagnóstico mostra estado.

### 4.4 Application Passwords

Label:
```text
Bloquear Application Passwords
```

Padrão:
```text
ON
```

Exceção: integrações REST autenticadas.

### 4.5 PHP em uploads

Label:
```text
Bloquear execução de PHP em uploads
```

Padrão:
```text
ON
```

Implementação conforme servidor:
- Apache/LiteSpeed: regra apropriada;
- Nginx: diagnóstico/instrução quando não puder ser aplicado localmente;
- nunca informar sucesso sem validação.

### 4.6 Directory Listing

Label:
```text
Bloquear listagem de diretórios
```

Padrão:
```text
ON
```

### 4.7 Ocultar versão pública do WordPress

Padrão:
```text
ON
```

Observação: hardening secundário, não substitui atualização.

### 4.8 Reduzir enumeração de usuários

Padrão:
```text
ON
```

Não quebrar REST API necessária.

### 4.9 Comentários

Label:
```text
Desativar comentários
```

Padrão:
```text
OFF
```

Pode ser habilitado em sites institucionais que não usam comentários.

### 4.10 Debug

**WP_DEBUG**
- Produção: OFF
- Desenvolvimento/Staging: configurável

**WP_DEBUG_DISPLAY**
- Produção: OFF

**WP_DEBUG_LOG**
- Padrão: OFF

Quando ativado:
- exibir aviso;
- verificar exposição pública do log;
- permitir diagnóstico.

## 5. Perfis

### Desenvolvimento

```text
DISALLOW_FILE_EDIT      OFF
DISALLOW_FILE_MODS      OFF
WP_DEBUG                ON
WP_DEBUG_DISPLAY        OFF
XML-RPC                 configurável
Application Passwords  configurável
Client Manager          restrito
```

### Staging

```text
DISALLOW_FILE_EDIT      ON
DISALLOW_FILE_MODS      OFF
WP_DEBUG                ON
WP_DEBUG_DISPLAY        OFF
XML-RPC                 bloqueado
Application Passwords  bloqueadas
```

### Produção

```text
DISALLOW_FILE_EDIT      ON
DISALLOW_FILE_MODS      OFF
WP_DEBUG                OFF
WP_DEBUG_DISPLAY        OFF
XML-RPC                 bloqueado
Application Passwords  bloqueadas
PHP em uploads          protegido
Directory Listing       bloqueado
```

### Produção Bloqueada

Tudo de Produção mais:

```text
DISALLOW_FILE_MODS      ON
instalar plugins        bloqueado
excluir plugins         bloqueado
trocar temas            bloqueado
criar Administrator     bloqueado
Code Snippets           bloqueado
ferramentas técnicas    bloqueadas
```

## 6. Acessos

### 6.1 Developer

Tela:
```text
Developer Principal
```

Funções:
- pesquisar usuário;
- selecionar usuário existente;
- definir Developer;
- remover status de Developer;
- preparar suporte a múltiplos Developers no futuro.

Persistir por:
```text
user_id
```

Critérios:
- somente Developer acessa BastionWP;
- Client Manager não edita Developer;
- Client Manager não exclui Developer;
- Client Manager não altera role de Developer.

### 6.2 Client Manager

Role:
```text
bastion_client_manager
```

Capabilities básicas:
- read;
- upload_files;
- edit_posts;
- edit_pages;
- publish_posts;
- publish_pages;
- delete_posts;
- delete_pages;
- capacidades específicas do projeto.

Não incluir:
- manage_options;
- install_plugins;
- activate_plugins;
- edit_plugins;
- delete_plugins;
- install_themes;
- switch_themes;
- edit_themes;
- delete_themes;
- update_core;
- update_plugins;
- update_themes;
- create_users;
- promote_users;
- delete_users.

## 7. Administradores

Tela:
```text
Usuários com privilégios elevados
```

Mostrar:
- usuário;
- ID;
- role;
- status Developer;
- status protegido;
- alertas.

Alerta:
```text
Administrator não autorizado detectado
```

Ações:
- revisar;
- rebaixar;
- marcar como Developer.

Nunca alterar automaticamente sem confirmação.

## 8. Proteção do Developer

Switch:
```text
Proteger contas Developer
```

Padrão:
```text
ON
```

Bloquear para não Developers:
- exclusão;
- downgrade de role;
- alteração de e-mail;
- alteração de senha;
- remoção de status Developer.

Toda tentativa deve ser logada.

## 9. Plugins

### 9.1 Plugins protegidos

Lista dos plugins instalados com opção:
```text
[ ] Proteger
```

Sugestões iniciais:
- BastionWP;
- Wordfence;
- Code Snippets;
- Advanced Custom Fields;
- CPT UI;
- plugin de backup;
- MainWP Child.

Quando protegido, Client Manager:
- não vê configurações;
- não abre rotas;
- não ativa/desativa;
- não exclui;
- não edita.

### 9.2 Ferramentas técnicas

Detectar:
- Code Snippets;
- ACF;
- CPT UI;
- WP Mail SMTP;
- Query Monitor;
- cache;
- backup;
- Wordfence.

Cada integração pode definir proteção padrão.

## 10. Wordfence

Estados:
- não instalado;
- instalado inativo;
- ativo;
- erro;
- desatualizado.

Ações:
- instalar;
- ativar;
- abrir Wordfence;
- mostrar status.

Dados desejados quando disponíveis:
- firewall;
- 2FA;
- último scan;
- problemas.

Não duplicar scanner/firewall.

## 11. Backup

V1:
- detectar plugins conhecidos;
- mostrar solução;
- mostrar status quando disponível;
- permitir registrar manualmente backup externo.

Não criar engine própria.

## 12. Logs

Tabela sugerida:
```text
wp_bastion_logs
```

Campos:
```text
id
timestamp
user_id
event_type
severity
message
ip_hash/opcional
metadata_json
```

Eventos:
- login;
- logout;
- acesso negado;
- plugin ativado/desativado/excluído;
- usuário criado/excluído;
- role alterada;
- Developer alterado;
- perfil alterado;
- configuração alterada;
- tentativa de modificar Developer;
- tentativa de criar Administrator.

Retenção inicial:
```text
90 dias
```

Limpeza programada.

Não armazenar senhas, tokens ou secrets.

## 13. Diagnóstico

Itens mínimos:
```text
BastionWP
Bastion Core
WordPress
PHP
HTTPS
DISALLOW_FILE_EDIT
DISALLOW_FILE_MODS
WP_DEBUG
WP_DEBUG_DISPLAY
XML-RPC
Application Passwords
PHP em uploads
Directory Listing
Wordfence
Backup
Developers
Administrators
Plugins protegidos
```

Cada item retorna:
```text
OK
Atenção
Crítico
Não configurado
Indisponível
```

## 14. Wizard inicial

1. Bem-vindo
2. Diagnóstico do ambiente
3. Instalar Bastion Core
4. Selecionar Developer
5. Criar/definir Client Manager
6. Selecionar perfil
7. Wordfence
8. Backup
9. Resumo
10. Aplicar configuração

Perfis disponíveis:
- Desenvolvimento;
- Staging;
- Produção;
- Produção Bloqueada.

## 15. Configurações avançadas

Proibições explícitas:
- não expor PHP arbitrário;
- não criar campo livre para `wp-config.php`;
- não executar snippets dentro do BastionWP;
- não criar backups temporários do `wp-config.php` no webroot.

## 16. Bastion Core

O instalador deverá:

1. verificar `wp-content/mu-plugins`;
2. criar diretório se necessário;
3. instalar arquivo controlado;
4. verificar versão;
5. validar versão/checksum;
6. permitir reparo;
7. nunca aceitar código arbitrário enviado pelo usuário.

O Core deverá aplicar políticas sem depender da UI.

## 17. Segurança das ações

Toda mutação administrativa deve validar:
```php
current_user_can(...)
```

e nonce apropriado.

Também:
- sanitização;
- validação;
- escaping;
- prepared queries;
- menor privilégio;
- proteção CSRF;
- proteção contra privilege escalation.

AJAX:
```text
nonce + capability + sanitização
```

## 18. Desinstalação

Tela de remoção:
```text
Ao remover BastionWP:
[ ] remover configurações
[ ] remover logs
[ ] remover Client Manager
[ ] remover Bastion Core
```

Por padrão, não remover Bastion Core automaticamente por simples desativação.

## 19. Critérios de aceite da V1

A V1 pode ir para beta quando:

- ativa sem erro;
- Core instala e valida;
- Developer funciona;
- Client Manager funciona;
- Client Manager não acessa Plugins;
- URL direta também é bloqueada;
- Client Manager não modifica Developer;
- perfis funcionam;
- hardening funciona;
- Wordfence é detectado;
- plugins protegidos funcionam;
- logs funcionam;
- diagnóstico funciona;
- wizard funciona;
- nenhuma ação crítica depende apenas de CSS;
- nenhuma função executa PHP arbitrário;
- não há credenciais em texto puro;
- testes de privilege escalation foram executados.

## 20. Testes obrigatórios

### Segurança
- CSRF;
- privilege escalation;
- capability bypass;
- URL direta;
- AJAX sem nonce;
- AJAX sem capability;
- manipulação de parâmetros;
- tentativa de alterar Developer;
- tentativa de criar Administrator;
- tentativa de desativar plugin protegido.

### Compatibilidade
- WordPress atual;
- PHP suportado;
- Apache/LiteSpeed;
- Hostinger;
- Cloudflare;
- Wordfence;
- Code Snippets;
- ACF;
- Elementor.

### Recuperação
- desativar BastionWP;
- reativar;
- Core ausente;
- Core corrompido;
- Wordfence ausente;
- configuração incompleta.

## 21. Fora do escopo da V1

- SaaS;
- dashboard central;
- API externa;
- Cloudflare API completa;
- firewall próprio;
- malware scanner próprio;
- backup próprio;
- 2FA próprio;
- antivírus;
- atualização remota própria.
