# Componente RPC baseado em multiplexação

Este componente é baseado no protocolo `TCP`, e o design de multiplexação foi inspirado no componente `AMQP`.

## Instalação

```
composer require hyperf/rpc-multiplex
```

## Configuração do Server

Modifique o arquivo de configuração `config/autoload/server.php`. A configuração abaixo removeu itens irrelevantes.

Nas configurações de `settings`, as regras de empacotamento não podem ser alteradas; apenas `package_max_length` pode ser modificado. Essa configuração precisa ser consistente entre `Server` e `Client`.

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;

return [
    'servers' => [
        [
            'name' => 'rpc',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_RECEIVE => [Hyperf\RpcMultiplex\TcpServer::class, 'onReceive'],
            ],
            'settings' => [
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
                'package_max_length' => 1024 * 1024 * 2,
            ],
            'options' => [
                // Sob multiplexação, evita erros de gravação múltipla de Socket entre corrotinas
                'send_channel_capacity' => 65535,
            ],
        ],
    ],
];

```

Crie o `RpcService`

```php
<?php

namespace App\RPC;

use App\JsonRpc\CalculatorServiceInterface;
use Hyperf\RpcMultiplex\Constant;
use Hyperf\RpcServer\Annotation\RpcService;

#[RpcService(name: "CalculatorService", server: "rpc", protocol: Constant::PROTOCOL_DEFAULT)]
class CalculatorService implements CalculatorServiceInterface
{
}

```

## Configuração do cliente

Modifique o arquivo de configuração `config/autoload/services.php`

```php
<?php

declare(strict_types=1);

return [
    'consumers' => [
        [
            'name' => 'CalculatorService',
            'service' => App\JsonRpc\CalculatorServiceInterface::class,
            'id' => App\JsonRpc\CalculatorServiceInterface::class,
            'protocol' => Hyperf\RpcMultiplex\Constant::PROTOCOL_DEFAULT,
            'load_balancer' => 'random',
            // De qual centro de serviços este consumidor obterá as informações dos nós; se não configurado, não obterá informações de nós do centro de serviços
            'registry' => [
                'protocol' => 'consul',
                'address' => 'http://127.0.0.1:8500',
            ],
            'nodes' => [
                ['host' => '127.0.0.1', 'port' => 9502],
            ],
            'options' => [
                'connect_timeout' => 5.0,
                'recv_timeout' => 5.0,
                'settings' => [
                    // Tamanho máximo do pacote; se for menor que o tamanho dos dados retornados pelo Server, uma exceção será lançada, então procure controlar o tamanho do pacote
                    'package_max_length' => 1024 * 1024 * 2,
                ],
                // Número de tentativas, valor padrão 2
                'retry_count' => 2,
                // Intervalo entre tentativas, em milissegundos
                'retry_interval' => 100,
                // Quantidade de clientes de multiplexação
                'client_count' => 4,
                // Intervalo de heartbeat; valor não numérico indica heartbeat desativado
                'heartbeat' => 30,
            ],
        ],
    ],
];

```

### Centro de registro

Se precisar usar o centro de registro, é necessário adicionar manualmente o seguinte listener

```php
<?php
return [
    Hyperf\RpcMultiplex\Listener\RegisterServiceListener::class,
];
```

## Uso

- Definir a interface

Por exemplo, precisamos projetar um serviço RPC para envio de SMS

```php
<?php

declare(strict_types=1);

namespace RPC\Push;

interface PushInterface
{
    public function sendSmsCode(string $mobile, string $code): bool;
}

```

- O servidor implementa a interface

```php
<?php

declare(strict_types=1);

namespace App\RPC;

use RPC\Push\PushInterface;
use Hyperf\RpcMultiplex\Constant;
use Hyperf\RpcServer\Annotation\RpcService;

#[RpcService(name: PushInterface::class, server: 'rpc', protocol: Constant::PROTOCOL_DEFAULT)]
class PushService implements PushInterface
{
    public function sendSmsCode(string $mobile, string $code): bool
    {
        // Lógica de processamento real
        return true;
    }
}
```

- Chamada do cliente

```php
<?php

use Hyperf\Context\ApplicationContext;
use RPC\Push\PushInterface;

ApplicationContext::getContainer()->get(PushInterface::class)->sendSmsCode('18600000001', '6666');

```
