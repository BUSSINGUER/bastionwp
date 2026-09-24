# BastionWP 0.9.8

**Autor:** Kaio Bussinguer  
**Escopo homologado nesta versão:** WordPress single-site  
**Requisitos técnicos:** WordPress 6.5+ e PHP 8.1+

BastionWP combina controle administrativo, políticas de acesso, Hardening, diagnóstico, auditoria interna e integração com ferramentas externas. A camada `Bastion Core` é instalada como Must-Use plugin para manter restrições essenciais mesmo quando o plugin principal estiver inativo.

## Fronteira de segurança

O Gerenciador do Cliente possui uma role editorial controlada. Capabilities técnicas como `manage_options`, instalação/edição de plugins e temas, administração de usuários e `unfiltered_html` são negadas por teto de capabilities no plugin principal e no Bastion Core.

O recurso de acesso temporário **não transforma o usuário em Administrator**. Na 0.9.8 ele funciona por adaptadores explícitos; o adaptador inicial é o Site Kit. Code Snippets, Wordfence e rotas de execução de código permanecem bloqueados.

Contas WordPress que continuam com a role nativa `administrator`, acesso ao banco, SFTP/SSH ou capacidade de executar PHP permanecem acima da fronteira que um plugin WordPress pode garantir. O Status do Sistema alerta sobre Administrators adicionais.

## Menus delegados

O catálogo genérico de menus só pode reutilizar capabilities editoriais já existentes na role do cliente. Menus que exigem capabilities técnicas são marcados como incompatíveis com o modo genérico e precisam de um adaptador específico. Isso evita transformar a simples seleção de um menu em elevação de privilégio.

## Auditoria

Os Logs registram **eventos emitidos pelo próprio BastionWP**. Solicitações temporárias usam `request_id`, ator, alvo e janela temporal para reunir os eventos relacionados. Isso não é monitoramento geral de arquivos e não prova que nenhuma alteração externa ocorreu quando não há eventos.

Retenção atual: até 90 dias e no máximo 5.000 eventos. O histórico de solicitações temporárias continua limitado a 500 solicitações na option legada; o estado ativo é mantido separadamente para evitar varredura do histórico em checks de capability.

## Atualizações

O updater usa GitHub Releases e aceita somente assets instaláveis com nome esperado para a versão. Pacotes `source`, `backup` e variações ambíguas são rejeitados. Antes da instalação, a raiz extraída deve ser exatamente `bastionwp/` e o cabeçalho do plugin é validado.

O arquivo anexado à Release deve ser:

```text
bastionwp-<versão>.zip
```

O pacote de source é apenas para repositório/revisão e não deve ser usado como asset de atualização.

## Bastion Core

O Status do Sistema diferencia Core ausente, inválido, desatualizado, com falha de integridade, desabilitado, inativo e ativo. A sincronização usa o diretório MU configurado pelo WordPress, arquivo temporário exclusivo, verificação de bytes/hash e restauração da cópia anterior quando possível.

## Áreas administrativas

- Visão Geral
- Assistente
- Acessos
- Solicitações
- Hardening
- Integrações
- Status do Sistema
- Sistema — Developer/Zona de Risco, Atualizações e Logs

## Limitações conhecidas da 0.9.8

- Multisite não é homologado e a ativação é bloqueada nesse ambiente.
- O histórico de solicitações ainda usa uma option limitada e não possui transação por registro; a autorização ativa foi separada dessa estrutura, mas uma futura migração para persistência por registro continua recomendada para alta concorrência.
- Menus de plugins que dependem de `manage_options` ou outras capabilities técnicas não podem ser delegados genericamente com segurança.
- A auditoria não observa SFTP/SSH, banco direto, processos externos ou alterações arbitrárias de arquivos/plugins de terceiros.

Consulte `CHANGELOG.md` para o histórico de versões.
