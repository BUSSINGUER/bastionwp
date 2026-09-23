# BastionWP 0.9.0

**Autor:** Kaio Bussinguer

## Foco da versão

Assistente de configuração inicial e preparação para beta.

## Nova aba: Assistente

O assistente organiza as principais áreas do BastionWP:

- Fundação;
- Acessos;
- Hardening;
- Wordfence;
- Atualizações;
- Diagnóstico.

O assistente não modifica configurações críticas automaticamente.

Ele funciona como uma lista de validação com:

- status OK / Atenção / Erro;
- progresso das etapas obrigatórias;
- links diretos para cada área;
- registro de conclusão;
- opção de reabrir para nova revisão.

## Site Kit

A V0.9.0 corrige o link exibido para usuários Client Manager.

Problema anterior:

```text
/wp-admin/googlesitekit-dashboard
```

Novo destino visual:

```text
/wp-admin/admin.php?page=googlesitekit-splash
```

O BastionWP modifica apenas o link do menu.

Ele não concede artificialmente permissões do Google.

### Dashboard Sharing

O Site Kit continua responsável pela autorização final.

Para um Gerenciador do Cliente visualizar dados:

1. abrir Site Kit como administrador;
2. abrir Dashboard Sharing;
3. compartilhar os serviços desejados;
4. selecionar a role Gerenciador do Cliente;
5. salvar.

Se o usuário abrir o Site Kit sem a autorização nativa, o BastionWP passa a
exibir uma orientação clara em vez de deixar a navegação cair em uma rota
inválida.

## Correção adicional

A dependência do Logger passa a ser carregada explicitamente durante a ativação
do plugin, garantindo que instalações novas da linha 0.9 também consigam criar
a tabela de logs sem depender da inicialização normal da aplicação.
