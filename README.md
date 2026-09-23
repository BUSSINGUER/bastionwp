# BastionWP 0.4.1

**Autor:** Kaio Bussinguer

Versão corretiva de segurança e estabilidade.

## Motivo da 0.4.1

As versões 0.3.0 e 0.4.0 continham uma recursão no filtro `user_has_cap`.

O código consultava `user_can()` dentro do próprio filtro `user_has_cap`,
o que podia disparar o mesmo filtro repetidamente até causar erro crítico
por consumo de memória/recursão.

A versão 0.4.1 remove esse padrão.

## Correções

- removida chamada recursiva a `user_can()` dentro de `user_has_cap`;
- capability Client Manager passa a ser verificada no array `$allcaps`;
- adicionada trava de reentrada defensiva;
- removida concessão dinâmica de capabilities do MU Core;
- Bastion Core reduzido a enforcement essencial;
- adicionado modo de emergência `BASTIONWP_DISABLE_CORE`;
- instalador do Core passa a preparar arquivo temporário antes da substituição;
- validação de sintaxe PHP do Core quando o ambiente permite;
- Bastion Core atualizado para 0.4.1.

## Modo de emergência

Se for necessário interromper temporariamente o Bastion Core:

```php
define('BASTIONWP_DISABLE_CORE', true);
```

Adicionar ao `wp-config.php` antes da linha final de encerramento das configurações.

Remover a constante depois do diagnóstico.

## Teste obrigatório

Testar em staging antes de produção:

1. dashboard como Developer;
2. login como Gerenciador do Cliente;
3. Bloqueio total;
4. Personalizado;
5. Joinchat/Site Kit;
6. rotas de Plugins/Temas/Usuários;
7. desativação do plugin principal com Core ativo;
8. reativação;
9. atualização/reparo do Core.
