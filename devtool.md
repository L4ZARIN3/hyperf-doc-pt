# Ferramentas de desenvolvimento

## Instalação

```
composer require hyperf/devtool
```

## Comandos suportados

```bash
php bin/hyperf.php
```

Ao executar o comando acima, você pode obter todos os comandos suportados pelo Command. Entre eles, os comandos da série `gen` e o comando `vendor:publish` servem principalmente para dar suporte ao componente `devtool`

```bash
 gen
  gen:amqp-consumer  Create a new amqp consumer class
  gen:amqp-producer  Create a new amqp producer class
  gen:aspect         Create a new aspect class
  gen:command        Create a new command class
  gen:controller     Create a new controller class
  gen:job            Create a new job class
  gen:listener       Create a new listener class
  gen:middleware     Create a new middleware class
  gen:process        Create a new process class
 vendor
  vendor:publish     Publish any publishable configs from vendor packages.
```

## Abertura rápida

Foi adicionada uma funcionalidade muito simples: usar o comando interno `gen` para abrir rapidamente o arquivo criado, com suporte a `sublime`, `textmate`, `cursor`, `emacs`, `macvim`, `phpstorm`, `idea`, `vscode`, `vscode-insiders`, `vscode-remote`, `vscode-insiders-remote`, `atom`, `nova`, `netbeans`, `xdebug`.

Também é necessário adicionar este bloco de configuração em `config/autoload/devtool.php`:

```php
return [
    /**
     * Supported IDEs: "sublime", "textmate", "cursor", "emacs", "macvim", "phpstorm", "idea",
     *        "vscode", "vscode-insiders", "vscode-remote", "vscode-insiders-remote",
     *        "atom", "nova", "netbeans", "xdebug"
     */
    'ide' => env('DEVTOOL_IDE', ''),
    //...
];
```
