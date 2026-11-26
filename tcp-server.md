# Serviço TCP/UDP

O framework fornece por padrão a capacidade de criar serviços `TCP/UDP`. Basta uma configuração simples para usá-los.

## Usar o serviço TCP

### Criar a classe TcpServer

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnReceiveInterface;

class TcpServer implements OnReceiveInterface
{
    public function onReceive($server, int $fd, int $reactorId, string $data): void
    {
        $server->send($fd, 'recv:' . $data);
    }
}

```

### Criar a configuração correspondente

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Abaixo foram removidos outros itens de configuração irrelevantes
    'servers' => [
        [
            'name' => 'tcp',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9504,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_RECEIVE => [App\Controller\TcpServer::class, 'onReceive'],
            ],
            'settings' => [
                // Configure conforme necessário
            ],
        ],
    ],
];

```

### Implementar o cliente

```php
<?php

$client = new \Swoole\Client(SWOOLE_SOCK_TCP);
$client->connect('127.0.0.1', 9504);
$client->send('Hello World.');
$ret = $client->recv(); // recv:Hello World.
```

## Usar o serviço UDP

> O Docker usa o protocolo TCP para comunicação por padrão; se você precisar usar o protocolo UDP, será necessário configurar a rede do Docker para isso.  
```shell
docker run -p 9502:9502/udp <image-name>
```

### Criar a classe UdpServer

> Se não houver o arquivo de interface OnPacketInterface, você pode não implementar essa interface; o resultado da execução será o mesmo de implementá-la, bastando garantir que a configuração esteja correta.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnPacketInterface;

class UdpServer implements OnPacketInterface
{
    public function onPacket($server, $data, $clientInfo): void
    {
        var_dump($clientInfo);
        $server->sendto($clientInfo['address'], $clientInfo['port'], 'Server: ' . $data);
    }
}

```

### Criar a configuração correspondente

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Abaixo foram removidos outros itens de configuração irrelevantes
    'servers' => [
        [
            'name' => 'udp',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9505,
            'sock_type' => SWOOLE_SOCK_UDP,
            'callbacks' => [
                Event::ON_PACKET => [App\Controller\UdpServer::class, 'onPacket'],
            ],
            'settings' => [
                // Configure conforme necessário
            ],
        ],
    ],
];

```

## Eventos

|       Evento        |       Observação       |
| :---------------: | :--------------: |
| Event::ON_CONNECT | Escuta o evento de entrada de conexão |
| Event::ON_RECEIVE | Escuta o evento de recebimento de dados |
|  Event::ON_CLOSE  | Escuta o evento de fechamento de conexão |
| Event::ON_PACKET  | Evento de recebimento de dados UDP |
