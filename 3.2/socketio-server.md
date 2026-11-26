# Serviço Socket.io

Socket.io é um framework e protocolo de comunicação em tempo real na camada de aplicação muito popular, que permite implementar facilmente resposta (ack), agrupamento e broadcast. O hyperf/socketio-server suporta o protocolo de transporte WebSocket do Socket.io.

## Instalação

```bash
composer require hyperf/socketio-server
```

O componente hyperf/socketio-server é implementado com base em WebSocket; certifique-se de que o servidor já tenha adicionado a configuração do `Serviço WebSocket`.

```php
// config/autoload/server.php
[
    'name' => 'socket-io',
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
```

## Início rápido

### Servidor

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\SocketIOServer\Annotation\Event;
use Hyperf\SocketIOServer\Annotation\SocketIONamespace;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Hyperf\Codec\Json;

#[SocketIONamespace("/")]
class WebSocketController extends BaseNamespace
{
    /**
     * @param string $data
     */
    #[Event("event")]
    public function onEvent(Socket $socket, $data)
    {
        // Resposta
        return 'Event Received: ' . $data;
    }

    /**
     * @param string $data
     */
    #[Event("join-room")]
    public function onJoinRoom(Socket $socket, $data)
    {
        // Adiciona o usuário atual à sala
        $socket->join($data);
        // Envia para os outros usuários da sala (exceto o usuário atual)
        $socket->to($data)->emit('event', $socket->getSid() . "has joined {$data}");
        // Transmite para todos na sala (incluindo o usuário atual)
        $this->emit('event', 'There are ' . count($socket->getAdapter()->clients($data)) . " players in {$data}");
    }

    /**
     * @param string $data
     */
    #[Event("say")]
    public function onSay(Socket $socket, $data)
    {
        $data = Json::decode($data);
        $socket->to($data['room'])->emit('event', $socket->getSid() . " say: {$data['message']}");
    }
}

```

> Cada socket entra automaticamente na sala com o nome do seu próprio `sid` (`$socket->getSid()`); para enviar uma mensagem privada, basta enviá-la para o `sid` correspondente.

> O framework dispara automaticamente os dois eventos `connect` e `disconnect`.

### Cliente

Como o servidor implementa apenas a comunicação WebSocket, o cliente precisa adicionar `{transports:["websocket"]}`.

```html
<script src="https://cdn.bootcdn.net/ajax/libs/socket.io/2.3.0/socket.io.js"></script>
<script>
    var socket = io('ws://127.0.0.1:9502', { transports: ["websocket"] });
    socket.on('connect', data => {
        socket.emit('event', 'hello, hyperf', console.log);
        socket.emit('join-room', 'room1', console.log);
        setInterval(function () {
            socket.emit('say', '{"room":"room1", "message":"Hello Hyperf."}');
        }, 1000);
    });
    socket.on('event', console.log);
</script>
```

## Lista de APIs

### API de Socket

Envia por meio da SocketAPI para o Socket de destino, ou fala na sala com a identidade do Socket de destino. Precisa ser usada dentro do callback de evento.

```php
<?php
#[Event("SomeEvent")]
function onSomeEvent(\Hyperf\SocketIOServer\Socket $socket){

  // sending to the client
  // Envia o evento hello para a conexão
  $socket->emit('hello', 'can you hear me?', 1, 2, 'abc');

  // sending to all clients except sender
  // Envia o evento broadcast para todas as conexões, mas não inclui a conexão atual.
  $socket->broadcast->emit('broadcast', 'hello friends!');

  // sending to all clients in 'game' room except sender
  // Envia o evento nice game para todas as conexões da sala game, mas não inclui a conexão atual.
  $socket->to('game')->emit('nice game', "let's play a game");

  // sending to all clients in 'game1' and/or in 'game2' room, except sender
  // Envia o evento nice game para a união de todas as conexões das salas game1 e game2, mas não inclui a conexão atual.
  $socket->to('game1')->to('game2')->emit('nice game', "let's play a game (too)");

  // WARNING: `$socket->to($socket->getSid())->emit()` will NOT work, as it will send to everyone in the room
  // named `$socket->getSid()` but the sender. Please use the classic `$socket->emit()` instead.
  // Atenção: ao enviar para si mesmo, não adicione to, pois $socket->to() sempre exclui a própria conexão. Basta usar $socket->emit() diretamente.

  // sending with acknowledgement
  // Envia a mensagem e aguarda e recebe a resposta do cliente.
  $reply = $socket->emit('question', 'do you think so?')->reply();

  // sending without compression
  // Envio sem compressão
  $socket->compress(false)->emit('uncompressed', "that's rough");
}
```
### API global

Obtém diretamente do contêiner o singleton do SocketIO. Este singleton pode transmitir globalmente ou se comunicar com uma sala específica ou com um indivíduo. Quando nenhum namespace é especificado, o namespace padrão é '/'.

```php
<?php
$io = \Hyperf\Context\ApplicationContext::getContainer()->get(\Hyperf\SocketIOServer\SocketIO::class);

// sending to all clients in 'game' room, including sender
// Envia o evento bigger-announcement para todas as conexões da sala game.
$io->in('game')->emit('big-announcement', 'the game will start soon');

// sending to all clients in namespace 'myNamespace', including sender
// Envia o evento bigger-announcement para todas as conexões do namespace /myNamespace
$io->of('/myNamespace')->emit('bigger-announcement', 'the tournament will start soon');

// sending to a specific room in a specific namespace, including sender
// Envia o evento event para todas as conexões da sala room do namespace /myNamespace
$io->of('/myNamespace')->to('room')->emit('event', 'message');

// sending to individual socketid (private message)
// Envia para um único socketId
$io->to('socketId')->emit('hey', 'I just met you');

// sending to all clients on this node (when using multiple nodes)
// Envia para todas as conexões desta máquina
$io->local->emit('hi', 'my lovely babies');

// sending to all connected clients
// Envia para todas as conexões
$io->emit('an event sent to all connected clients');
```

### API de namespace

Igual à API global, mas com o namespace já restrito.
```php
// Os pseudocódigos abaixo são equivalentes
$foo->emit();
$io->of('/foo')->emit();

/**
 * O uso dentro da classe também é equivalente
 */
#[SocketIONamespace("/foo")]
class FooNamespace extends BaseNamespace {
    public function onEvent(){
        $this->emit(); 
        $this->io->of('/foo')->emit();
    }
}
```

## Tutorial avançado

### Definir o namespace do Socket.io

O Socket.io implementa multiplexação por meio de namespaces personalizados. (Atenção: não é o namespace do PHP)

1. Você pode mapear o controlador para o namespace xxx por meio de `#[SocketIONamespace("/xxx")]`,

2. Também pode usar

```php
<?php
use Hyperf\SocketIOServer\Collector\SocketIORouter;
use App\Controller\WebSocketController;
SocketIORouter::addNamespace('/xxx' , WebSocketController::class);
```

para adicionar na rota.

### Habilitar Session

Instale e configure o componente hyperf/session e o middleware correspondente e, em seguida, use `SessionAspect` para interceptar o SocketIO e utilizar a Session.

```php
<?php
// config/autoload/aspect.php
return [
    \Hyperf\SocketIOServer\Aspect\SessionAspect::class,
];
```

> Swoole 4.4.17 e versões anteriores conseguem apenas ler o Cookie criado pelo HTTP; Swoole 4.4.18 e versões superiores podem criar o Cookie durante o handshake do WebSocket

### Ajustar o adaptador de salas

A funcionalidade de sala padrão é implementada por meio do adaptador Redis, que pode se adaptar a cenários multiprocesso e até distribuídos.

1. Pode ser substituído pelo adaptador de memória, aplicável apenas a cenários com um único worker.

```php
<?php
// config/autoload/dependencies.php
return [
    \Hyperf\SocketIOServer\Room\AdapterInterface::class => \Hyperf\SocketIOServer\Room\MemoryAdapter::class,
];
```

2. Pode ser substituído pelo adaptador nulo, o que reduz o consumo quando a funcionalidade de salas não é necessária.

```php
<?php
// config/autoload/dependencies.php
return [
    \Hyperf\SocketIOServer\Room\AdapterInterface::class => \Hyperf\SocketIOServer\Room\NullAdapter::class,
];
```

### Ajustar o SocketID (`sid`)

Por padrão, o SocketID usa o formato `ServerID#FD`, que pode se adaptar a cenários distribuídos.

1. Pode ser substituído pelo uso direto do Fd.

```php
<?php
// config/autoload/dependencies.php
return [
    \Hyperf\SocketIOServer\SidProvider\SidProviderInterface::class => \Hyperf\SocketIOServer\SidProvider\LocalSidProvider::class,
];
```

2. Também pode ser substituído pelo SessionID.

```php
<?php
// config/autoload/dependencies.php
return [
    \Hyperf\SocketIOServer\SidProvider\SidProviderInterface::class => \Hyperf\SocketIOServer\SidProvider\SessionSidProvider::class,
];
```

### Outros métodos de despacho de eventos

1. É possível registrar eventos manualmente, sem usar anotações.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\SocketIOServer\Socket;
use Hyperf\WebSocketServer\Sender;

class WebSocketController extends BaseNamespace
{
    public function __construct(Sender $sender, SidProviderInterface $sidProvider) {
        parent::__construct($sender,$sidProvider);
        $this->on('event', [$this, 'echo']);
    }

    public function echo(Socket $socket, $data)
    {
        $socket->emit('event', $data);
    }
}
```

2. É possível adicionar a anotação `#[Event]` na classe do controlador e despachar usando o nome do método como nome do evento. Nesse caso, deve-se atentar para o fato de que outros métodos públicos podem entrar em conflito com nomes de eventos.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\SocketIOServer\Annotation\SocketIONamespace;
use Hyperf\SocketIOServer\Annotation\Event;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

#[SocketIONamespace("/")]
#[Event]
class WebSocketController extends BaseNamespace
{
    public function echo(Socket $socket, $data)
    {
        $socket->emit('event', $data);
    }
}
```

### Modificar os parâmetros básicos do `SocketIO`

Parâmetros padrão do framework:

|          Configuração          | Tipo  | Valor padrão |
| :--------------------: | :---: | :----: |
|      $pingTimeout      |  int  |  100   |
|     $pingInterval      |  int  | 10000  |
| $clientCallbackTimeout |  int  | 10000  |

Às vezes, devido ao grande volume de mensagens enviadas ou a uma rede mais lenta, o `PONG` não consegue ser retornado em tempo hábil dentro de 100ms, o que faz a conexão cair. Nesse caso, podemos sobrescrever por meio da seguinte abordagem:

```php
<?php

declare(strict_types=1);

namespace App\Kernel;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\SocketIOServer\Parser\Decoder;
use Hyperf\SocketIOServer\Parser\Encoder;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\SocketIOServer\SocketIO;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;

class SocketIOFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $io = new SocketIO(
            $container->get(StdoutLoggerInterface::class),
            $container->get(Sender::class),
            $container->get(Decoder::class),
            $container->get(Encoder::class),
            $container->get(SidProviderInterface::class)
        );

        // Sobrescreve o parâmetro pingTimeout
        $io->setPingTimeout(10000);

        return $io;
    }
}

```

Depois, basta adicionar o mapeamento correspondente em `dependencies.php`.

```php
return [
    Hyperf\SocketIOServer\SocketIO::class => App\Kernel\SocketIOFactory::class,
];
```

### Autenticação Auth

Você pode usar um middleware para interceptar o handshake do WebSocket e implementar a funcionalidade de autenticação, conforme abaixo:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebSocketAuthMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Pseudocódigo: intercepta a requisição de handshake e implementa a verificação de permissão por meio do método isAuth
        if (! $this->isAuth($request)) {
            return $this->container->get(\Hyperf\HttpServer\Contract\ResponseInterface::class)->raw('Forbidden');
        }

        return $handler->handle($request);
    }
}
```

E então basta configurar o middleware acima no WebSocket Server correspondente.

### Obter o objeto de requisição original

Após o estabelecimento da conexão, às vezes é necessário obter informações da requisição, como o IP do cliente, Cookie etc. O objeto de requisição original já foi preservado no [contexto de conexão](websocket-server.md#contexto-de-conexão); você pode obtê-lo no callback de evento da seguinte forma:

```php
public function onEvent($socket, $data)
{
    $request = Hyperf\WebSocketServer\Context::get(
        Psr\Http\Message\ServerRequestInterface::class
    );
}
```

### Configuração de proxy Nginx

O uso do `Nginx` como proxy reverso para o `Socket.io` tem algumas diferenças em relação ao `WebSocket`
```nginx
server {
    location ^~/socket.io/ {
        # Executa o acesso por proxy ao servidor real
        proxy_pass http://hyperf;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```
