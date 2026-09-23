# Changelog

## 0.9.1

- Corrigido erro crítico `BastionWP::$wizard must not be accessed before initialization`.
- Removida autorreferência `$this->wizard` da própria inicialização.
- Corrigida chamada do construtor de `BastionWP_Wizard` para quatro argumentos.
- Corrigida chamada de `BastionWP_Admin` para incluir a dependência `$wizard`.
- Reforçada validação estática do grafo de dependências.
- Mantida a correção do link do Site Kit para `admin.php?page=googlesitekit-splash`.
- Bastion Core atualizado para 0.9.1.

## 0.9.0

- Assistente de configuração inicial.
- Correção visual do link do Site Kit.

## 0.8.1

- Correção crítica do Diagnostics.

## 0.8.0

- Logs e diagnóstico ampliado.
