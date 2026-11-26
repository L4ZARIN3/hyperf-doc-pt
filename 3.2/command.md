# Linha de comando

A linha de comando do Hyperf é fornecida por padrão pelo componente [hyperf/command](https://github.com/hyperf/command), e esse componente em si é uma abstração sobre o [symfony/console](https://github.com/symfony/console).

# Instalação

Normalmente esse componente já existe por padrão, mas se você deseja usá-lo em projetos que não sejam Hyperf, também pode depender do componente [hyperf/command](https://github.com/hyperf/command) através do comando abaixo:

```bash
composer require hyperf/command
```

# Visualizar a lista de comandos

Basta executar `php bin/hyperf.php` sem nenhum parâmetro para que a lista de comandos seja exibida.

# Comando personalizado

## Gerar comando

Se você tiver instalado o componente [hyperf/devtool](https://github.com/hyperf/devtool), poderá gerar um comando personalizado através do comando `gen:command`:

```bash
php bin/hyperf.php gen:command FooCommand
```
Após executar o comando acima, uma classe `FooCommand` já configurada será gerada dentro da pasta `app/Command`.

### Definir comando

Existem três formas de definir o comando correspondente a essa classe de comando: a primeira é através da propriedade `$name`, a segunda é através da passagem de parâmetro no construtor e a última é através de anotação. Vamos demonstrar com exemplos de código, supondo que desejamos definir o comando dessa classe de comando como `foo:hello`:

#### Definição pela propriedade `$name`:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

#[Command]
class FooCommand extends HyperfCommand
{
    /**
     * Linha de comando executada
     */
    protected ?string $name = 'foo:hello';
}
```

#### Definição por parâmetro no construtor:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

#[Command]
class FooCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('foo:hello');
    }
}
```

#### Definição por anotação:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

#[Command(name: "foo:hello")]
class FooCommand extends HyperfCommand
{

}
```

### Definir a lógica da classe de comando

A lógica que a classe de comando realmente executa depende do código dentro do método `handle`, ou seja, o método `handle` é a entrada do comando.

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

#[Command]
class FooCommand extends HyperfCommand
{
    /**
     * Linha de comando executada
     */
    protected ?string $name = 'foo:hello';

    public function handle()
    {
        // Através do método interno line, exibe Hello Hyperf. no Console.
        $this->line('Hello Hyperf.', 'info');
    }
}
```

### Definir os parâmetros da classe de comando

Ao escrever um comando, normalmente coletamos a entrada do usuário através de `parâmetros` e `opções`. Antes de coletar uma entrada do usuário, é necessário definir esse `parâmetro` ou `opção`.

#### Parâmetros

Suponhamos que desejamos definir um parâmetro `name` e, em seguida, passar uma string qualquer como `Hyperf` junto ao comando e executar `php bin/hyperf.php foo:hello Hyperf` para exibir `Hello Hyperf`. Vamos demonstrar com código:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class FooCommand extends HyperfCommand
{
    /**
     * Linha de comando executada
     */
    protected ?string $name = 'foo:hello';

    public function handle()
    {
        // Obtém o parâmetro name a partir de $input
        $argument = $this->input->getArgument('name') ?? 'World';
        $this->line('Hello ' . $argument, 'info');
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'Aqui está a explicação deste parâmetro']
        ];
    }
}
```

Executando `php bin/hyperf.php foo:hello Hyperf` podemos ver a saída `Hello Hyperf`.

## Introdução às configurações comuns de comandos

O código abaixo altera apenas o conteúdo de `configure` e `handle`.

### Definir Help

```php
public function configure()
{
    parent::configure();
    $this->setHelp('Demonstração de comando personalizado do Hyperf');
}

```
```bash
$ php bin/hyperf.php demo:command --help
# Saída
...
Help:
  Demonstração de comando personalizado do Hyperf
```


### Definir Description

```php
public function configure()
{
    parent::configure();
    $this->setDescription('Hyperf Demo Command');
}
```
```bash
$ php bin/hyperf.php demo:command --help
# Saída
...
Description:
  Hyperf Demo Command

```

### Definir Usage

```php
public function configure()
{
    parent::configure();
    $this->addUsage('--name código de demonstração');
}
```
```bash
$ php bin/hyperf.php demo:command --help
# Saída
...
Usage:
  demo:command
  demo:command --name código de demonstração
```

### Definir parâmetros

Os parâmetros suportam os seguintes modos.

|          Modo           | Valor |                Observação                 |
|:-----------------------:|:--:|:-----------------------------------:|
| InputArgument::REQUIRED | 1  | Parâmetro obrigatório, neste modo o campo default é inválido |
| InputArgument::OPTIONAL | 2  |    Parâmetro opcional, geralmente usado com default    |
| InputArgument::IS_ARRAY | 4  |              Tipo array               |

#### Tipo opcional

```php
public function configure()
{
    parent::configure();
    $this->addArgument('name', InputArgument::OPTIONAL, 'Nome', 'Hyperf');
}

public function handle()
{
    $this->line($this->input->getArgument('name'));
}
```
```bash
$ php bin/hyperf.php demo:command
# Saída
...
Hyperf

$ php bin/hyperf.php demo:command Swoole
# Saída
...
Swoole
```

#### Tipo array

```php
public function configure()
{
    parent::configure();
    $this->addArgument('name', InputArgument::IS_ARRAY, 'Nome');
}

public function handle()
{
    var_dump($this->input->getArgument('name'));
}
```
```bash
$ php bin/hyperf.php demo:command Hyperf Swoole
# Saída
...
array(2) {
  [0]=>
  string(6) "Hyperf"
  [1]=>
  string(6) "Swoole"
}
```

### Definir opções

As opções suportam os seguintes modos.

|            Modo             | Valor |     Observação     |
|:---------------------------:|:--:|:------------:|
|   InputOption::VALUE_NONE   | 1  | Se a opção foi passada; o campo default é inválido |
| InputOption::VALUE_REQUIRED | 2  |   Opção obrigatória   |
| InputOption::VALUE_OPTIONAL | 4  |   Opção opcional   |
| InputOption::VALUE_IS_ARRAY | 8  |   Opção array   |

#### Se a opção foi passada

```php
public function configure()
{
    parent::configure();
    $this->addOption('opt', 'o', InputOption::VALUE_NONE, 'Se deve otimizar');
}

public function handle()
{
    var_dump($this->input->getOption('opt'));
}
```
```bash
$ php bin/hyperf.php demo:command
# Saída
bool(false)

$ php bin/hyperf.php demo:command -o
# Saída
bool(true)

$ php bin/hyperf.php demo:command --opt
# Saída
bool(true)
```

### Opções obrigatórias e opcionais

`VALUE_OPTIONAL` quando usada isoladamente não difere de `VALUE_REQUIRED`

```php
public function configure()
{
    parent::configure();
    $this->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'Nome', 'Hyperf');
}

public function handle()
{
    var_dump($this->input->getOption('name'));
}
```
```bash
$ php bin/hyperf.php demo:command
# Saída
string(6) "Hyperf"

$ php bin/hyperf.php demo:command --name Swoole
# Saída
string(6) "Swoole"
```

### Opção array

`VALUE_IS_ARRAY` combinada com `VALUE_OPTIONAL` pode alcançar o efeito de passar múltiplas `Option`.

```php
public function configure()
{
    parent::configure();
    $this->addOption('name', 'N', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Nome');
}

public function handle()
{
    var_dump($this->input->getOption('name'));
}
```
```bash
$ php bin/hyperf.php demo:command
# Saída
array(0) {
}

$ php bin/hyperf.php demo:command --name Hyperf --name Swoole
# Saída
array(2) {
  [0]=>
  string(6) "Hyperf"
  [1]=>
  string(6) "Swoole"
}

```

## Configurar a linha de comando através de `$signature`

Além dos métodos de configuração acima, a linha de comando também suporta configuração através de `$signature`.

`$signature` é uma string dividida em três partes: `command`, `argument` e `option`, conforme abaixo:

```
command:name {argument?* : The argument description.} {--option=* : The option description.}
```

- `?` representa `não obrigatório`.
- `*` representa `array`.
- `?*` representa `array não obrigatório`.
- `=` representa `não Bool`.

### Exemplo

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class DebugCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    protected ?string $signature = 'test:test {id : user_id} {--name= : user_name}';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        var_dump($this->input->getArguments());
        var_dump($this->input->getOptions());
    }
}

```

# Executar comandos

!> Atenção: ao executar um comando, por padrão o despacho de eventos é acionado; é possível desativá-lo adicionando o parâmetro `--disable-event-dispatcher`.

## Executar na linha de comando

```bash
php bin/hyperf.php foo
```

## Executar outros comandos dentro de um Command

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class FooCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('foo');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('foo command');
    }

    public function handle()
    {
        $this->call('bar', [
            '--foo' => 'foo'
        ]);
    }
}
```

## Executar comandos fora de um Command

```php
$command = 'foo';

$params = ["command" => $command, "--foo" => "foo", "--bar" => "bar"];

// Você pode escolher o input/output a usar conforme sua necessidade
$input = new ArrayInput($params);
$output = new NullOutput();

/** @var \Psr\Container\ContainerInterface $container */
$container = \Hyperf\Context\ApplicationContext::getContainer();

/** @var \Symfony\Component\Console\Application $application */
$application = $container->get(\Hyperf\Contract\ApplicationInterface::class);
$application->setAutoExit(false);

// Desta forma: não expõe exceções durante a execução do comando e não impede o retorno do programa
$exitCode = $application->run($input, $output);

// Segunda forma: expõe exceções; é necessário capturar e tratar as exceções da execução, caso contrário impedirá o retorno do programa
$exitCode = $application->find($command)->run($input, $output);
```

## Comando closure

Você pode definir comandos rapidamente em `config\console.php`.

```php
use Hyperf\Command\Console;

Console::command('hello', function () {
    $this->comment('Hello, Hyperf!');
})->describe('This is a demo closure command.');
```

Definir tarefa agendada para um comando closure.

```php
use Hyperf\Command\Console;

Console::command('foo', function () {
    $this->comment('Hello, Foo!');
})->describe('This is a demo closure command.')->cron('* * * * *');

Console::command('bar', function () {
    $this->comment('Hello, Bar!');
})->describe('This is another demo closure command.')->cron('* * * * *', callback: fn($cron) => $cron->setSingleton(true));
```

## AsCommand

Você pode converter uma classe em comando através da anotação `AsCommand`.

```php
<?php

namespace App\Service;

use Hyperf\Command\Annotation\AsCommand;
use Hyperf\Command\Concerns\InteractsWithIO;

#[AsCommand(signature: 'foo:bar1', handle: 'bar1', description: 'The description of foo:bar1 command.')]
#[AsCommand(signature: 'foo', description: 'The description of foo command.')]
class FooService
{
    use InteractsWithIO;

    #[AsCommand(signature: 'foo:bar {--bar=1 : Bar Value}', description: 'The description of foo:bar command.')]
    public function bar($bar)
    {
        $this->output?->info('Bar Value: ' . $bar);

        return $bar;
    }

    public function bar1()
    {
        $this->output?->info(__METHOD__);
    }

    public function handle()
    {
        $this->output?->info(__METHOD__);
    }
}
```

```shell
$ php bin/hyperf.php

...
foo
  foo:bar                   The description of foo:bar command.
  foo:bar1                  The description of foo:bar1 command.
```
