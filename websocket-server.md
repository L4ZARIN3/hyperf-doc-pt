# Serviço WebSocket

Hyperf fornece um encapsulamento do WebSocket Server; é possível construir rapidamente uma aplicação WebSocket com base no componente [hyperf/websocket-server](https://github.com/hyperf/websocket-server).

## Instalação

```bash
composer require hyperf/websocket-server
```

## Configurar Server

Modifique `config/autoload/server.php` e adicione a seguinte configuração.

```php
<?php

return [
    'servers' => [
        [
            'name' => 'ws',
            'type' => Server::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                Event::ON_MESSAGE => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                Event::ON_CLOSE => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
        ],
    ],
];
```

## Configurar rotas

> No momento, só há suporte à configuração de rotas por meio de arquivo de configuração; posteriormente será fornecido o modo por anotação.

No arquivo `config/routes.php`, adicione a configuração de rota do Server `ws` correspondente. O valor `ws` aqui depende do valor `name` do WebSocket Server configurado em `config/autoload/server.php`.

```php
<?php

Router::addServer('ws', function () {
    Router::get('/', 'App\Controller\WebSocketController');
});
```

## Configurar middleware

No arquivo `config/autoload/middlewares.php`, adicione a configuração de middleware global do Server `ws` correspondente. O valor `ws` aqui depende do valor `name` do WebSocket Server configurado em `config/autoload/server.php`.

```php
<?php

return [
    'ws' => [
        yourMiddleware::class
    ]
];
```

## Criar o controlador correspondente

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Engine\WebSocket\Frame;
use Hyperf\Engine\WebSocket\Response;
use Hyperf\WebSocketServer\Constant\Opcode;
use Swoole\Server;
use Swoole\WebSocket\Server as WebSocketServer;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    public function onMessage($server, $frame): void
    {
        $response = (new Response($server))->init($frame);
        if($frame->opcode == Opcode::PING) {
            // Se usar o Server de corrotinas, após identificar que é um frame PING, é necessário tratar manualmente e retornar um frame PONG.
            // No Server de estilo assíncrono, é possível tratar diretamente pela configuração do Swoole; para detalhes, consulte https://wiki.swoole.com/#/websocket_server?id=open_websocket_ping_frame
            $response->push(new Frame(opcode: Opcode::PONG));
            return;
        }
        $response->push(new Frame(payloadData: 'Recv: ' . $frame->data));
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        var_dump('closed');
    }

    public function onOpen($server, $request): void
    {
        $response = (new Response($server))->init($request);
        $response->push(new Frame(payloadData: 'Opened'));
    }
}
```

Em seguida, inicie o Server e você verá que um WebSocket Server foi iniciado e está escutando na porta 9502. Nesse momento, você poderá conectar e transmitir dados por meio de diversos WebSocket Client.

```
$ php bin/hyperf.php start

[INFO] Worker#0 started.
[INFO] WebSocket Server listening at 0.0.0.0:9502
[INFO] HTTP Server listening at 0.0.0.0:9501
```

!> Quando escutamos simultaneamente a porta 9501 do HTTP Server e a porta 9502 do WebSocket Server, o WebSocket Client pode se conectar ao WebSocket Server pelas duas portas 9501 e 9502, ou seja, tanto `ws://0.0.0.0:9501` quanto `ws://0.0.0.0:9502` podem conectar com sucesso.

Como Swoole\WebSocket\Server herda de Swoole\Http\Server, é possível usar HTTP para acionar todos os envios (push) do WebSocket. Para entender detalhes, consulte a parte do callback onRequest na [documentação do Swoole](https://wiki.swoole.com/#/websocket_server?id=websocketserver).

Se precisar desativar, você pode modificar o arquivo `config/autoload/server.php` para adicionar o item de configuração `open_websocket_protocol` no serviço `http`.

```php
<?php
return [
    // As demais configurações deste arquivo são omitidas aqui
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
            'settings' => [
                'open_websocket_protocol' => false,
            ]
        ],
    ]
];
```

## Contexto de conexão

Os callbacks onOpen, onMessage e onClose do serviço WebSocket não são acionados na mesma corrotina, portanto não é possível usar diretamente o contexto de corrotina para armazenar informações de estado. O componente WebSocket Server fornece um contexto **no nível da conexão**, com API exatamente igual à do contexto de corrotina.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Engine\WebSocket\Frame;
use Hyperf\Engine\WebSocket\Response;
use Hyperf\WebSocketServer\Context;
use Swoole\WebSocket\Server as WebSocketServer;

class WebSocketController implements OnMessageInterface, OnOpenInterface
{
    public function onMessage($server, $frame): void
    {
        $response = (new Response($server))->init($frame);
        $response->push(new Frame(payloadData: 'Username: ' . Context::get('username')));
    }

    public function onOpen($server, $request): void
    {
        Context::set('username', $request->cookie['username']);
    }
}
```

## Configuração de múltiplos servers

```
# /etc/nginx/conf.d/ng_socketio.conf
# múltiplos ws server
upstream io_nodes {
    server ws1:9502;
    server ws2:9502;
}
server {
  listen 9502;
  # server_name your.socket.io;
  location / {
    proxy_set_header Upgrade "websocket";
    proxy_set_header Connection "upgrade";
    # proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    # proxy_set_header Host $host;
    # proxy_http_version 1.1;
    # encaminha para múltiplos ws server
    proxy_pass http://io_nodes;
  }
}
```

## Sender de mensagens

Quando queremos fechar uma conexão `WebSocket` no serviço `HTTP`, podemos usar diretamente `Hyperf\WebSocketServer\Sender`.

O `Sender` verificará se o `fd` pertence ao `Worker` atual; se pertencer, enviará os dados diretamente; se não, enviará por meio de `PipeMessage` para todos os `Worker`s exceto ele mesmo, e os outros `Worker`s farão a verificação. Se o `fd` pertencer a eles, enviarão os dados correspondentes ao cliente.

O `Sender` suporta duas `API`s, `push` e `disconnect`, conforme abaixo:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\WebSocketServer\Sender;
use function Hyperf\Coroutine\go;

#[AutoController]
class ServerController
{
    #[Inject]
    protected Sender $sender;

    public function close(int $fd)
    {
        go(function () use ($fd) {
            sleep(1);
            $this->sender->disconnect($fd);
        });

        return '';
    }

    public function send(int $fd)
    {
        $this->sender->push($fd, 'Hello World.');

        return '';
    }
}

```

## Processar requisições HTTP no serviço WebSocket

Além de podermos separar o serviço HTTP e o serviço WebSocket por portas, também podemos escutar requisições HTTP no WebSocket.

Como os itens de configuração em `server.servers.*.callbacks` são todos singletons, precisamos configurar uma instância separada em `dependencies`.

```php
<?php
return [
    'HttpServer' => Hyperf\HttpServer\Server::class,
];
```

Em seguida, modificamos a configuração `callbacks` do nosso serviço `WebSocket`; as configurações irrelevantes foram ocultadas abaixo

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;

return [
    'mode' => SWOOLE_BASE,
    'servers' => [
        [
            'name' => 'ws',
            'type' => Server::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => ['HttpServer', 'onRequest'],
                Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                Event::ON_MESSAGE => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                Event::ON_CLOSE => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
        ],
    ],
];

```

Por fim, podemos adicionar rotas `HTTP` no `ws`.
