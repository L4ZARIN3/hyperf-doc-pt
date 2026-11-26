# Processador de sinais

O processador de sinais escuta os processos `Worker` e os processos `personalizados` e, após a inicialização, registra-se automaticamente no gerenciador de sinais.

## Instalação

```
composer require hyperf/signal
```

## Publicar configuração

Você pode publicar o arquivo de configuração padrão em seu projeto com o comando abaixo:

```bash
php bin/hyperf.php vendor:publish hyperf/signal
```

## Adicionar processador

Abaixo escutamos o sinal `SIGTERM` do processo `Worker` e, ao receber o sinal, imprimimos o valor do sinal.

```php
<?php

declare(strict_types=1);

namespace App\Signal;

use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

#[Signal]
class TermSignalHandler implements SignalHandlerInterface
{
    public function listen(): array
    {
        return [
            [SignalHandlerInterface::WORKER, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        var_dump($signal);
    }
}

```

Como, após capturar o sinal SIGTERM recebido pelo processo Worker, ele não consegue encerrar normalmente, o usuário pode sair diretamente com `Ctrl + C` ou modificar a configuração `config/autoload/signal.php`, da seguinte forma:

> O WorkerStopHandler não é compatível com o CoroutineServer; se necessário, implemente por conta própria

```php
<?php

declare(strict_types=1);

return [
    'handlers' => [
        Hyperf\Signal\Handler\WorkerStopHandler::class => PHP_INT_MIN
    ],
    'timeout' => 5.0,
];
```

Quando o `WorkerStopHandler` é acionado, ele fecha o processo atual após o tempo configurado em [max_wait_time](https://wiki.swoole.com/#/server/setting?id=max_wait_time).

## Exemplo de configuração de listener de serviço no estilo corrotina

> Os listeners padrão acima são todos compatíveis com serviços no estilo assíncrono. Se precisar usá-los em serviços no estilo corrotina, você pode seguir a configuração personalizada abaixo

```php
<?php

declare(strict_types=1);

namespace App\Kernel\Signal;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\ProcessManager;
use Hyperf\Server\ServerManager;
use Hyperf\Signal\SignalHandlerInterface;
use Psr\Container\ContainerInterface;

class CoroutineServerStopHandler implements SignalHandlerInterface
{

    protected ContainerInterface $container;

    protected ConfigInterface $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function listen(): array
    {
        // No estilo corrotina existe apenas um processo Worker, então aqui basta escutar WORKER
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        ProcessManager::setRunning(false);

        foreach (ServerManager::list() as [$type, $server]) {
            // Encerra em loop os serviços abertos
            $server->shutdown();
        }
    }
}

```
