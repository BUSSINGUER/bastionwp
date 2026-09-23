# BastionWP 0.7.1

**Autor:** Kaio Bussinguer

## Foco da versão

Versão corretiva e de usabilidade.

A V0.7.1 melhora a experiência visual da aba Hardening e corrige o destino de
menus delegados em plugins cuja rota principal visível difere do slug base
registrado.

## Hardening

Melhorias adicionadas:

- painel visual mais separado;
- resumo dinâmico do que muda ao selecionar um perfil;
- Proteções efetivas atualizadas em tempo real antes de salvar;
- compatibilidade também atualizada em tempo real conforme o perfil escolhido;
- badges visuais:
  - **verde** para itens protegidos/bloqueados;
  - **vermelho** para itens permitidos/expostos.

Perfis suportados:

- Desenvolvimento
- Staging
- Produção
- Produção Bloqueada

## Correção de rotas delegadas

Alguns plugins registram um slug principal, mas usam outro slug/submenu como
página real de entrada.

Exemplo reportado:

```text
googlesitekit-dashboard
→ página de entrada real:
admin.php?page=googlesitekit-splash
```

Na V0.7.1, o BastionWP passa a armazenar também um `entry_slug` por grupo de
menu e usa esse destino preferencial quando o menu é liberado ao Gerenciador do
Cliente.

Isso reduz problemas como:

- link abrindo em `/wp-admin/slug-interno`;
- erro de “página não existe”;
- mismatch entre link visível do Developer e do Client Manager.

## Compatibilidade

A correção foi feita de forma genérica, não apenas para o Site Kit.

Sempre que existir submenu detectado, o BastionWP pode utilizá-lo como destino
preferencial do clique principal do menu delegado.
