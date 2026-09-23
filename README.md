# BastionWP 0.7.0

**Autor:** Kaio Bussinguer

## Foco da versão

Integração operacional com Wordfence.

O BastionWP não substitui Wordfence e não incorpora seu código.

O Wordfence continua responsável por:

- firewall;
- scanner de malware;
- detecção de vulnerabilidades;
- segurança de login;
- 2FA;
- bloqueios e alertas.

O BastionWP passa a organizar a instalação e o estado dessa camada de segurança.

## Nova aba

```text
BastionWP
→ Integrações
→ Wordfence
```

## Recursos

- detecta se Wordfence está instalado;
- detecta se está ativo;
- mostra versão instalada;
- mostra estado de auto-update;
- detecta se o WAF foi carregado na requisição;
- instala o Wordfence oficial do WordPress.org;
- ativa o plugin;
- ativa auto-update por padrão quando instalado pelo BastionWP;
- permite controlar auto-update;
- apresenta checklist operacional.

## Produção Bloqueada

Se o site estiver em:

```text
Produção Bloqueada
```

o BastionWP não tenta contornar sua própria política.

Para instalar ou ativar Wordfence manualmente:

1. mudar temporariamente para Produção;
2. instalar/ativar;
3. concluir configuração;
4. retornar para Produção Bloqueada se desejado.

## Segurança

Wordfence é considerado área técnica.

Gerenciadores do Cliente não podem receber Wordfence pelo seletor de menus.

O plugin principal e o Bastion Core também bloqueiam acesso direto às rotas
administrativas conhecidas do Wordfence para Client Managers.

## Configuração interna do Wordfence

A V0.7.0 não escreve diretamente nas opções privadas do Wordfence.

Configurações como:

- licença;
- otimização do firewall;
- scan;
- alertas;
- 2FA;

devem ser concluídas pelo painel oficial do Wordfence.

Isso evita dependência de opções internas não documentadas e reduz risco de
quebra após atualizações do Wordfence.

## Próxima etapa

```text
0.8.0 — logs e diagnóstico ampliado
```
