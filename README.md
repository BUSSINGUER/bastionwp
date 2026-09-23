# BastionWP 0.9.6

**Autor:** Kaio Bussinguer

## Acessos e Hardening

Esta versão atualiza a organização visual das seções Acessos e Hardening.

### Acessos

Nova hierarquia:

```text
Usuários
Gerenciar usuário
Developer
```

A área passa a oferecer overview dos usuários gerenciados, visualização das
permissões e menus ativos, modos de acesso mais claros, sliders para menus
adicionais e uma Zona de risco para alteração do Developer Principal.

### Hardening

Os quatro perfis passam a aparecer em cards horizontais com ícones.

A estrutura fica:

```text
Perfil de Hardening
Regras do perfil selecionado | O que muda ao aplicar
Ajustes adicionais
Estado atual
```

Também foi adicionada navegação interna para páginas longas.

Nenhuma regra funcional ou de segurança foi alterada.


---

# BastionWP 0.9.5

**Autor:** Kaio Bussinguer

## Cabeçalhos e Assistente

A versão 0.9.5 corrige a estrutura visual dos cabeçalhos administrativos e
reorganiza somente a interface da aba Assistente.

Os cabeçalhos permanecem no topo das páginas, acima do conteúdo de cada seção.

A aba Assistente passa a apresentar:

- progresso geral no cabeçalho;
- etapas de configuração em tabela visual;
- filtros Todas / Obrigatórias / Recomendadas;
- resumo de progresso;
- próxima recomendação;
- conclusão da configuração;
- orientação do Site Kit em painel lateral.

Não existem mudanças de regras funcionais ou de segurança.


---

# BastionWP 0.9.4

**Autor:** Kaio Bussinguer

## Dashboard da Visão Geral

A versão 0.9.4 é uma atualização de estrutura/layout da aba Visão Geral.

A aba agora concentra:

- saúde do ambiente;
- Bastion Core;
- WordPress/PHP/HTTPS;
- controle de acesso;
- perfil de hardening ativo;
- estado das atualizações;
- resumo do diagnóstico;
- principais recursos de usuários e permissões;
- ações rápidas;
- atividade recente dos logs.

O Design System da 0.9.3 foi mantido. Não existem mudanças funcionais nas
demais áreas do plugin.


---

# BastionWP 0.9.3

**Autor:** Kaio Bussinguer

## Atualização visual

A versão 0.9.3 é um release exclusivamente visual sobre a base funcional 0.9.2.

Principais mudanças:

- novo Design System administrativo;
- cabeçalho global modernizado;
- navegação por abas com ícones;
- hierarquia visual e espaçamentos revisados;
- cards e formulários padronizados;
- estados de sucesso, atenção e erro mais claros;
- melhorias responsivas;
- refinamentos visuais nas telas existentes.

Não há mudanças nas regras de negócio, permissões, capabilities, Bastion Core,
hardening, integrações, banco de dados ou sistema de atualização.


---


**Autor:** Kaio Bussinguer

## Foco da versão

Solicitações globais de privilégios administrativos temporários e melhorias
operacionais no Hardening.

## Privilégios administrativos temporários

Gerenciadores do Cliente passam a receber um menu no final do painel:

```text
Administrador
```

Nesta área o usuário pode solicitar:

```text
Privilégios administrativos temporários
```

Fluxo:

1. cliente envia a solicitação;
2. BastionWP registra o chamado;
3. Developer recebe e-mail;
4. Developer abre `BastionWP -> Solicitações`;
5. aprova por:
   - 30 minutos;
   - 1 hora;
   - 2 horas;
6. ou nega;
7. acesso aprovado expira automaticamente;
8. Developer também pode encerrar antes do prazo.

## Segurança do acesso temporário

O usuário continua com a role:

```text
bastion_client_manager
```

O BastionWP não transforma permanentemente o usuário em Administrator.

Durante o período aprovado, capabilities administrativas do WordPress são
concedidas em runtime, com exclusões explícitas.

Continuam protegidas:

- Plugins;
- Temas;
- Usuários;
- Atualizações;
- BastionWP;
- Wordfence;
- Code Snippets;
- alterações de infraestrutura.

O objetivo é permitir configurações de plugins que exigem `manage_options`
sem conceder controle permanente da infraestrutura.

## E-mail

A solicitação é enviada aos e-mails dos Developers registrados no BastionWP.

Se nenhum e-mail de Developer estiver disponível, usa `admin_email` como fallback.

A entrega depende da configuração de e-mail do WordPress/servidor.

## Hardening

Nova área de Ajustes adicionais:

- Desabilitar comentários;
- Ocultar menu Painel do Gerenciador do Cliente;
- Forçar supressão de `display_errors`.

Em Produção e Produção Bloqueada, o menu Painel do Gerenciador do Cliente é
ocultado por padrão quando não existe override manual.

## Comentários

Quando desabilitados:

- fecha novos comentários;
- fecha trackbacks/pings;
- remove suporte a comentários dos post types;
- oculta menu Comentários;
- remove item Comentários da barra administrativa.

## WP_DEBUG_DISPLAY / display_errors

O diagnóstico agora separa:

```text
WP_DEBUG_DISPLAY configurado
```

de:

```text
display_errors efetivamente ativo
```

Se erros ainda estiverem sendo exibidos, a tela oferece:

```text
Corrigir agora
```

O botão ativa supressão em runtime pelo BastionWP.

O plugin NÃO edita automaticamente `wp-config.php`.

Para correção definitiva, o Developer continua recebendo a orientação para
definir:

```php
define('WP_DEBUG_DISPLAY', false);
```

no `wp-config.php`.
