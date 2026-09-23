# BastionWP 0.8.0

**Autor:** Kaio Bussinguer

## Foco da versão

Logs de auditoria e diagnóstico consolidado.

## Nova aba: Logs

O BastionWP passa a registrar eventos próprios relevantes, como:

- mudança de acessos administrativos;
- alteração da política de menus de um usuário;
- troca do perfil de hardening;
- reparo/sincronização do Bastion Core;
- instalação/ativação do Wordfence;
- alteração de auto-update do Wordfence;
- alteração da fonte/canal de atualização do BastionWP;
- verificações manuais de update;
- atualizações do BastionWP;
- tentativas de acesso bloqueadas pelo plugin principal;
- exportação/limpeza dos próprios logs.

### Privacidade

Por padrão, o log NÃO armazena:

- senhas;
- tokens;
- cookies;
- chaves de API;
- licenças;
- nonces;
- IP do visitante.

Contextos passam por sanitização e chaves sensíveis são substituídas por:

```text
[redacted]
```

### Retenção

```text
90 dias
ou
5000 eventos
```

o que ocorrer primeiro.

## Exportação

Logs podem ser exportados em CSV.

O arquivo contém no máximo os 200 eventos mais recentes por exportação na V0.8.0.

## Nova aba: Diagnóstico

O relatório verifica:

- versão BastionWP;
- Bastion Core;
- tabela de logs;
- WordPress;
- PHP;
- HTTPS;
- WP_DEBUG;
- exibição de erros;
- WP-Cron;
- hardening;
- auto-update BastionWP;
- fonte GitHub;
- Wordfence;
- WAF Wordfence.

Também exibe contadores:

```text
OK
Atenções
Erros
```

## Relatório JSON

O diagnóstico pode ser baixado em JSON para suporte/auditoria.

O relatório não inclui senhas, tokens ou credenciais.

## Banco de dados

A V0.8.0 cria automaticamente:

```text
{prefixo}_bastionwp_logs
```

Exemplo comum:

```text
wp_bastionwp_logs
```

Não é necessário criar ou alterar a tabela manualmente.
