# BastionWP 0.9.1

**Autor:** Kaio Bussinguer

Versão corretiva crítica da 0.9.0.

## Correção do erro crítico do Wizard

A V0.9.0 continha uma autorreferência durante a inicialização:

```php
$this->wizard = new BastionWP_Wizard(
    $this->mu_installer,
    $this->hardening,
    $this->wordfence,
    $this->diagnostics,
    $this->wizard
);
```

A propriedade `$this->wizard` era acessada antes de existir.

Isso causava:

```text
Typed property BastionWP::$wizard must not be accessed before initialization
```

A V0.9.1 corrige a inicialização para os quatro argumentos realmente exigidos:

```php
$this->wizard = new BastionWP_Wizard(
    $this->mu_installer,
    $this->hardening,
    $this->wordfence,
    $this->diagnostics
);
```

## Segunda correção preventiva

O construtor de `BastionWP_Admin` exige sete dependências.

Na V0.9.0, o `$wizard` não estava sendo passado.

A V0.9.1 também corrige isso:

```php
$this->admin = new BastionWP_Admin(
    $this->mu_installer,
    $this->users,
    $this->update_manager,
    $this->hardening,
    $this->wordfence,
    $this->diagnostics,
    $this->wizard
);
```

## Site Kit

A correção da V0.9.0 permanece:

```text
/wp-admin/admin.php?page=googlesitekit-splash
```

para o link visual do Gerenciador do Cliente.

A autorização final continua sendo controlada pelo Dashboard Sharing nativo
do Site Kit.

## Validação reforçada

A partir desta versão, o build passa a verificar explicitamente:

- quantidade de argumentos de `BastionWP_Diagnostics`;
- quantidade de argumentos de `BastionWP_Wizard`;
- quantidade de argumentos de `BastionWP_Admin`;
- ausência de autorreferência durante inicialização de typed properties;
- ordem de criação das dependências.

Isso complementa o `php -l`, que valida apenas sintaxe.
