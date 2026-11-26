# Serviço no estilo corrotina

O Hyperf usa por padrão o [estilo assíncrono do Swoole](https://wiki.swoole.com/#/server/init), que é um modelo multiprocesso, no qual os processos personalizados são executados como processos separados.

> Esse tipo, quando usa SWOOLE_BASE e não utiliza processos personalizados, roda em modelo de processo único; para detalhes, consulte a documentação oficial do Swoole.

O Hyperf também oferece o serviço no estilo corrotina, que é um modelo de processo único: todos os processos personalizados são executados no modo corrotina, sem criar processos separados.

Esses dois estilos podem ser escolhidos conforme a necessidade, **mas não é recomendável alternar sem critério um serviço que já está em uso normal**.

## Configuração

Modifique o arquivo de configuração `autoload/server.php` e defina `type` como `Hyperf\Server\CoroutineServer::class` para iniciar o estilo corrotina.

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;

return [
    'type' => Hyperf\Server\CoroutineServer::class,
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
    ],
];

```

## WebSocket

1. Como o estilo corrotina e o estilo assíncrono têm diferenças nos callbacks correspondentes, é necessário usá-los conforme a necessidade

Por exemplo, no callback `onReceive`, o estilo assíncrono usa `Swoole\Server`, e o estilo corrotina usa `Swoole\Coroutine\Server\Connection`.

```php
<?php

declare(strict_types=1);

namespace Hyperf\Contract;

use Swoole\Coroutine\Server\Connection;
use Swoole\Server as SwooleServer;

interface OnReceiveInterface
{
    /**
     * @param Connection|SwooleServer $server
     */
    public function onReceive($server, int $fd, int $reactorId, string $data): void;
}
```

2. A corrotina onde o middleware está só termina quando ocorre o `onClose`

Como a instância de banco de dados do `Hyperf` é devolvida ao pool de conexões quando a corrotina é destruída, se você usar `Database` no middleware do `WebSocket`, as conexões do pool de conexões não conseguirão ser devolvidas normalmente.
