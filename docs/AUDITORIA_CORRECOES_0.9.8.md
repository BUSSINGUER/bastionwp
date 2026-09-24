# BastionWP 0.9.8 — Correções pós-auditoria

**Base:** auditoria da versão 0.9.7 em 24/09/2026.  
**Objetivo:** fechar a falha crítica, reduzir superfícies de privilégio, tornar Core/updater verificáveis e alinhar a interface ao comportamento real.

## Resultado por achado

| ID | Situação na 0.9.8 | Correção / decisão |
|---|---|---|
| 01 | Corrigido | Acesso temporário não copia Administrator nem `manage_options`; usa adaptadores explícitos. Code Snippets/Wordfence são bloqueados também em REST pelo plugin e Core. |
| 02 | Corrigido | Conversão/migração remove capabilities individuais legadas do Gerenciador do Cliente. |
| 03 | Corrigido | Catálogo genérico só reutiliza capabilities editoriais já existentes. Menus técnicos são marcados como “requer adaptador”. |
| 04 | Escopo corrigido | Detalhes agora mostram apenas eventos emitidos pelo BastionWP e usam `request_id`, ator, alvo e janela temporal. O produto não promete auditoria geral de arquivos. |
| 05 | Corrigido | Provider aceita somente nomes instaláveis esperados e rejeita source/backup; upgrader valida raiz e identidade. |
| 06 | Corrigido | Core distingue instalado, íntegro, desabilitado, inativo e ativo. |
| 07 | Corrigido | Instalador respeita `WPMU_PLUGIN_DIR`, temporário exclusivo, bytes/hash, backup/restauração e OPcache. |
| 08 | Corrigido com limitação explícita | Menus que precisam capabilities técnicas não são elevados; precisam adaptador. Salvar AJAX/REST genérico não é prometido. |
| 09 | Corrigido | Consulta temporal ocorre no banco; timeline usa `request_id`; CSV respeita filtros e pagina por lotes; truncamento de tela é informado. |
| 10 | Corrigido | Schema é validado, retenção tem cron diário + limite de 5.000, falhas de gravação ficam diagnosticáveis. |
| 11 | Parcialmente mitigado | Estado ativo foi separado do histórico e histórico limitado a 500. A option histórica ainda usa read-modify-write e não é transacional. Migração para armazenamento por registro permanece recomendada. |
| 12 | Corrigido | UI diferencia plugin ativo de configuração funcional; API REST usa `rest_url()` e Wordfence usa estado `active`. |
| 13 | Corrigido | “Corrigir agora” voltou ao Status do Sistema e Hardening foi incorporado ao relatório consolidado exportável. |
| 14 | Corrigido | Dashboard/Wizard usam checks reais; warnings opcionais não bloqueiam conclusão; fonte de update exige Release válida. |
| 15 | Corrigido | Bootstrap do Hardening não traduz perfis antes de `init`; textdomain é carregado em `init`. |
| 16 | Corrigido em escopo atual | Metadados do destino, formatos `plugin/plugins`, versão em disco, validação de pacote e recuperação do Core foram reforçados. Não há rollback transacional completo de todos os dados. |
| 17 | Escopo restringido | 0.9.8 é single-site; ativação em multisite é bloqueada. |
| 18 | Corrigido | Persistência do Hardening trata valor inalterado como sucesso quando o estado final corresponde. |
| 19 | Corrigido | Filtros explícitos, busca Unicode, redirect do usuário convertido e preview de Hardening revisados. |
| 20 | Corrigido | `fputcsv()` recebe escape explícito; buffers são limpos; células com risco de fórmula são neutralizadas. |
| 21 | Fronteira explicitada | Status do Sistema alerta sobre outros Administrators. Um plugin não pode isolar absolutamente contas com execução PHP/SFTP/DB. |
| 22 | Parcialmente corrigido | README/documentação atualizados e placeholders identificados. IDs `_wpnonce` gerados pelo helper nativo podem se repetir entre formulários da mesma página; não foi encontrada falha de autorização associada. |
| 23 | Corrigido | Quando bloqueado, o BastionWP remove os métodos XML-RPC do WordPress e a UI explica que o endpoint pode ainda responder com fault. |

## Testes executados neste build

- PHP lint em todos os 20 arquivos `.php` + stub MU: aprovado.
- JavaScript syntax em 2 arquivos: aprovado.
- Teste isolado do provider GitHub: `source.zip` rejeitado; asset instalável correto aceito; versão divergente rejeitada.
- Teste isolado das capabilities temporárias: `manage_options`, `install_plugins`, `unfiltered_html`, `edit_users` e `bastionwp_manage` permanecem `false` mesmo se fornecidas por outro filtro antes do BastionWP.
- Teste isolado da política de menus: `edit_posts` delegável; `manage_options`, `install_plugins`, `unfiltered_html` e `bastionwp_manage` bloqueados.
- Teste isolado do Bastion Core REST: namespace Code Snippets e Wordfence bloqueados; `/wp/v2/posts` não é bloqueado genericamente.
- Teste isolado de saúde do Core: arquivo íntegro com `BASTIONWP_DISABLE_CORE=true` retorna estado `disabled`, não `ok`.
- Verificação estrutural do pacote: única raiz `bastionwp/`, arquivo principal presente e apenas um cabeçalho de plugin executável.

## Limitação da regressão nesta sessão

O ZIP de evidências da auditoria contém scripts e resultados, mas não inclui a instalação WordPress descartável usada pelo auditor. O ambiente atual também não possui acesso de rede para reconstruí-la a partir do WordPress.org. Por isso, os testes de correção desta sessão foram lint, análise estática e testes unitários/isolados dos pontos críticos. A instalação em um WordPress de staging continua obrigatória antes de promover esta versão a 1.0 estável.

## Recomendação de promoção

Tratar 0.9.8 como **release de segurança / beta de homologação**. Instalar primeiro em staging e repetir o cenário Code Snippets, Site Kit, Wordfence, atualização 0.9.7 → 0.9.8 e expiração do acesso temporário em um WordPress completo.
