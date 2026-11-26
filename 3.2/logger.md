# Log

O componente `hyperf/logger` é implementado com base em [psr/logger](https://github.com/php-fig/log) e, por padrão, usa o [monolog/monolog](https://github.com/Seldaek/monolog) como driver. O projeto `hyperf-skeleton` já fornece algumas configurações de log por padrão, usando `Monolog\Handler\StreamHandler` por padrão. Como o `Swoole` já aplicou tratamento de corrotina às funções `fopen`, `fwrite` etc., desde que o parâmetro `useLocking` não seja definido como `true`, é seguro para corrotinas.

## Instalação

```shell
composer require hyperf/logger
```

## Configuração

O projeto `hyperf-skeleton` já fornece algumas configurações de log por padrão. Por padrão, o arquivo de configuração de log é `config/autoload/logger.php`, conforme o exemplo abaixo:

```php
<?php

return [
    'default' => [
        'handler' => [
            'class' => \Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => \Monolog\Level::Debug,
            ],
        ],
        'formatter' => [
            'class' => \Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => null,
                'allowInlineLineBreaks' => true,
            ]
        ],
    ],
];
```

## Uso

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Hyperf\Logger\LoggerFactory;

class DemoService
{

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        // O primeiro parâmetro corresponde ao name do log, e o segundo corresponde à key dentro de config/autoload/logger.php
        $this->logger = $loggerFactory->get('log', 'default');
    }

    public function method()
    {
        // Do something.
        $this->logger->info("Your log message.");
    }
}
```

## Noções básicas sobre o monolog

Vamos ver, junto com o código, alguns conceitos básicos envolvidos no `monolog`:

```php
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// Cria um Channel; o parâmetro log é o nome do Channel
$log = new Logger('log');

// Cria dois Handlers, correspondentes às variáveis $stream e $fire
$stream = new StreamHandler('test.log', Logger::WARNING);
$fire = new FirePHPHandler();

// Define o formato de data como "Y-m-d H:i:s"
$dateFormat = "Y n j, g:i a";
// Define o formato do log como "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
$output = "%datetime%||%channel||%level_name%||%message%||%context%||%extra%\n";
// Com base no formato de data e no formato de log, cria um Formatter
$formatter = new LineFormatter($output, $dateFormat);

// Define o Formatter no Handler
$stream->setFormatter($formatter);

// Insere o Handler na fila de Handlers do Channel
$log->pushHandler($stream);
$log->pushHandler($fire);

// clone new log channel
$log2 = $log->withName('log2');

// add records to the log
$log->warning('Foo');

// add extra data to record
// 1. log context
$log->error('a new user', ['username' => 'daydaygo']);
// 2. processor
$log->pushProcessor(function ($record) {
    $record['extra']['dummy'] = 'hello';
    return $record;
});
$log->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
$log->alert('czl');
```

- Primeiro, instancia-se um `Logger` e dá-se um nome; o nome corresponde ao `channel`
- É possível vincular vários `Handler` a um `Logger`; o `Logger` registra o log e o `Handler` o processa
- O `Handler` pode especificar quais **níveis de log** devem ser processados, por exemplo `Logger::WARNING`, processando apenas logs com nível `>=Logger::WARNING`
- Quem formata o log? O `Formatter`; basta configurar o Formatter e vinculá-lo ao `Handler` correspondente
- Quais partes o log contém: `"%datetime%||%channel||%level_name%||%message%||%context%||%extra%\n"`
- Diferencie as informações adicionais `context` e `extra` adicionadas ao log: `context` é especificado adicionalmente pelo usuário ao registrar o log, sendo mais flexível; `extra` é adicionado de forma fixa pelo `Processor` vinculado ao `Logger`, sendo mais adequado para coletar algumas **informações comuns**

## Usos adicionais

### Encapsular a classe `Log`

Talvez, em alguns casos, você prefira manter o hábito de uso de log da maioria dos frameworks. Nesse caso, você pode criar uma classe `Log` em `App` e, por meio do método mágico `__callStatic` de chamada estática, obter o `Logger` e registrar logs em cada nível. Vamos demonstrar com código:

> Lembre-se de que, ao usar, não vincule $name à requisição; por exemplo, usar $request_id como nome do logger fará com que o Factory armazene objetos de log no nível de requisição, causando um grave vazamento de memória.

```php
namespace App;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Context\ApplicationContext;

class Log
{
    public static function get(string $name = 'app')
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name);
    }
}
```

Por padrão, usa-se o `Channel` chamado `app` para registrar logs; você também pode usar o método `Log::get($name)` para obter o `Logger` de um `Channel` diferente. O poderoso `contêiner (Container)` resolve tudo isso para você.

### Log em stdout

Por padrão, os logs emitidos pelos componentes do framework são fornecidos pela classe de implementação `Hyperf\Framework\Logger\StdoutLogger` da interface `Hyperf\Contract\StdoutLoggerInterface`. Essa classe de implementação serve apenas para exibir as informações relevantes via `print_r()` na `saída padrão (stdout)`, ou seja, no `terminal (Terminal)` onde o `Hyperf` foi iniciado, o que significa que o `monolog` na verdade não é usado. Então, se você quiser usar o `monolog` para manter a consistência, como proceder?

Sim, também por meio do poderoso `contêiner (Container)`.

- Primeiro, implemente uma classe `StdoutLoggerFactory`; sobre o uso de `Factory`, consulte mais detalhes no capítulo de [injeção de dependência](di.md).

```php
<?php
declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Log::get('sys');
    }
}
```

- Declare a dependência: onde `StdoutLoggerInterface` é usado, será concluído pela classe instanciada pela dependência real `StdoutLoggerFactory`

```php
// config/autoload/dependencies.php
return [
    \Hyperf\Contract\StdoutLoggerInterface::class => \App\StdoutLoggerFactory::class,
];
```

### Emitir logs em formatos diferentes em ambientes diferentes

Todos os usos acima ainda giram em torno do `Logger` do monolog; aqui vamos ver o `Handler` e o `Formatter`

```php
// config/autoload/logger.php
$appEnv = env('APP_ENV', 'dev');
if ($appEnv == 'dev') {
    $formatter = [
        'class' => \Monolog\Formatter\LineFormatter::class,
        'constructor' => [
            'format' => "||%datetime%||%channel%||%level_name%||%message%||%context%||%extra%\n",
            'allowInlineLineBreaks' => true,
            'includeStacktraces' => true,
        ],
    ];
} else {
    $formatter = [
        'class' => \Monolog\Formatter\JsonFormatter::class,
        'constructor' => [],
    ];
}

return [
    'default' => [
        'handler' => [
            'class' => \Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => 'php://stdout',
                'level' => \Monolog\Level::Info,
            ],
        ],
        'formatter' => $formatter,
    ],
];
```

- Por padrão, configura-se um `Handler` chamado `default`, incluindo as informações desse `Handler` e do seu `Formatter`
- Ao obter o `Logger`, se nenhum `Handler` for especificado, a camada inferior vinculará automaticamente o `Handler` `default` ao `Logger`
- Ambiente dev (desenvolvimento): o log usa `php://stdout` para sair na `saída padrão (stdout)`, e no `Formatter` define-se `allowInlineLineBreaks`, facilitando a visualização de logs com várias linhas
- Ambiente não-dev: o log usa `JsonFormatter` e será formatado como `json`, facilitando o envio para serviços de log de terceiros

### Rotação de arquivos de log por data

Se você deseja que os arquivos de log rotacionem por data, pode usar o `Monolog\Handler\RotatingFileHandler` já fornecido pelo `Mongolog`, conforme a configuração abaixo:

Modifique o arquivo de configuração `config/autoload/logger.php`, alterando o `Handler` para `Monolog\Handler\RotatingFileHandler::class` e o campo `stream` para `filename`.

```php
<?php

return [
    'default' => [
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => Monolog\Level::Debug,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => null,
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
```

Se você deseja um particionamento de log ainda mais granular, também pode herdar a classe `Monolog\Handler\RotatingFileHandler` e reimplementar o método `rotate()`.

### Configurar múltiplos `Handler`

O usuário pode modificar `handlers` para que o grupo de logs correspondente suporte múltiplos `handler`. Por exemplo, com a configuração abaixo, quando o usuário envia um log de nível `INFO` ou superior, o log será gravado em `hyperf.log` e em `hyperf-debug.log`.
Quando o usuário envia um log de nível `DEBUG`, o log será gravado apenas em `hyperf-debug.log`.

```php
<?php

declare(strict_types=1);

use Monolog\Handler;
use Monolog\Formatter;
use Monolog\Level;

return [
    'default' => [
        'handlers' => [
            [
                'class' => Handler\StreamHandler::class,
                'constructor' => [
                    'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                    'level' => Level::Info,
                ],
                'formatter' => [
                    'class' => Formatter\LineFormatter::class,
                    'constructor' => [
                        'format' => null,
                        'dateFormat' => null,
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ],
            [
                'class' => Handler\StreamHandler::class,
                'constructor' => [
                    'stream' => BASE_PATH . '/runtime/logs/hyperf-debug.log',
                    'level' => Level::Info,
                ],
                'formatter' => [
                    'class' => Formatter\JsonFormatter::class,
                    'constructor' => [
                        'batchMode' => Formatter\JsonFormatter::BATCH_MODE_JSON,
                        'appendNewline' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

Ou

```php

declare(strict_types=1);

use Monolog\Handler;
use Monolog\Formatter;
use Monolog\Level;

return [
    'default' => [
        'handlers' => ['single', 'daily'],
    ],

    'single' => [
        'handler' => [
            'class' => Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level' => Level::Info,
            ],
        ],
        'formatter' => [
            'class' => Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => null,
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],

    'daily' => [
        'handler' => [
            'class' => Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf-debug.log',
                'level' => Level::Info,
            ],
        ],
        'formatter' => [
            'class' => Formatter\JsonFormatter::class,
            'constructor' => [
                'batchMode' => Formatter\JsonFormatter::BATCH_MODE_JSON,
                'appendNewline' => true,
            ],
        ],
    ],
];

```

O resultado é o seguinte

```shell
==> runtime/logs/hyperf.log <==
[2019-11-08 11:11:35] hyperf.INFO: 5dc4dce791690 [] []

==> runtime/logs/hyperf-debug.log <==
{"message":"5dc4dce791690","context":[],"level":200,"level_name":"INFO","channel":"hyperf","datetime":{"date":"2019-11-08 11:11:35.597153","timezone_type":3,"timezone":"Asia/Shanghai"},"extra":[]}
{"message":"xxxx","context":[],"level":100,"level_name":"DEBUG","channel":"hyperf","datetime":{"date":"2019-11-08 11:11:35.597635","timezone_type":3,"timezone":"Asia/Shanghai"},"extra":[]}
```


### Unificar logs no nível de requisição

Às vezes, precisamos correlacionar os logs de uma mesma requisição, então podemos implementar um Processor

```php
<?php

declare(strict_types=1);

namespace App\Kernel\Log;

use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class AppendRequestIdProcessor implements ProcessorInterface
{
    public const REQUEST_ID = 'log.request.id';

    public function __invoke(array|LogRecord $record)
    {
        $record['extra']['request_id'] = Context::getOrSet(self::REQUEST_ID, uniqid());
        $record['extra']['coroutine_id'] = Coroutine::id();
        return $record;
    }
}

```

E então configure-o no nosso `logger.php`

```php
<?php

declare(strict_types=1);

use App\Kernel\Log;

return [
    'default' => [
        // Remove as demais configurações
        'processors' => [
            [
                'class' => Log\AppendRequestIdProcessor::class,
            ],
        ],
    ],
];

```
