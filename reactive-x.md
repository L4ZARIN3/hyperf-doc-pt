# Integração com ReactiveX

O componente [hyperf/reactive-x](https://github.com/hyperf/reactive-x) fornece a integração do ReactiveX no ambiente Swoole/Hyperf.

## História do ReactiveX

ReactiveX é a abreviação de Reactive Extensions, geralmente abreviado como Rx. Originalmente era uma extensão do LINQ, desenvolvida pela equipe liderada por Erik Meijer, arquiteto da Microsoft, e teve seu código aberto em novembro de 2012. Rx é um modelo de programação cujo objetivo é fornecer uma interface de programação consistente, ajudando desenvolvedores a lidar com fluxos de dados assíncronos de forma mais conveniente. A biblioteca Rx suporta .NET, JavaScript e C++, e tornou-se cada vez mais popular nos últimos anos; hoje já suporta quase todas as linguagens de programação populares. A maioria das bibliotecas Rx das diversas linguagens é mantida pela organização ReactiveX, sendo as mais populares RxJava/RxJS/Rx.NET. O site da comunidade é [reactivex.io](http://reactivex.io).

## O que é ReactiveX

A definição da Microsoft é que Rx é uma biblioteca de funções que permite aos desenvolvedores escrever programas assíncronos e baseados em eventos utilizando sequências observáveis e operadores de consulta no estilo LINQ. Com Rx, os desenvolvedores podem representar fluxos de dados assíncronos com Observables, consultar fluxos de dados assíncronos com operadores LINQ e parametrizar o processamento concorrente de fluxos de dados assíncronos com Schedulers. Rx pode ser definido assim: Rx = Observables + LINQ + Schedulers.

A definição dada por [Reactivex.io](http://reactivex.io) é que Rx é uma interface de programação para programação assíncrona usando fluxos de dados observáveis, e que o ReactiveX combina a essência do padrão Observer, do padrão Iterator e da programação funcional.

> As duas seções acima foram extraídas do [RxDocs](https://github.com/mcxiaoke/RxDocs).

## Considere antes de usar

### Pontos positivos

- Por meio da forma de pensar da programação reativa, é possível simplificar alguns problemas assíncronos complexos.

- Se você já tem experiência com programação reativa em outras linguagens (como RxJS/RxJava), este componente pode ajudá-lo a transplantar essa experiência para o Hyperf.

- Embora no Swoole se recomende escrever programas assíncronos com corrotinas como se escreve um programa síncrono, o Swoole ainda contém muitos eventos, e o tratamento de eventos é justamente o ponto forte do Rx.

- Se o seu negócio envolve processamento de streams, como WebSocket, gRPC streaming, etc., o Rx também pode desempenhar um papel importante.

### Pontos negativos

- A forma de pensar da programação reativa difere bastante da forma de pensar tradicional orientada a objetos, exigindo adaptação do desenvolvedor.

- O Rx oferece apenas uma forma de pensar; não há magia adicional. Problemas que podem ser resolvidos com programação reativa também podem ser resolvidos de forma tradicional.

- O RxPHP não é o destaque da família Rx.

## Instalação

```bash
composer require hyperf/reactive-x
```

## Encapsulamentos

A seguir, apresentamos alguns encapsulamentos deste componente com exemplos, mostrando o poder do Rx. Todos os exemplos podem ser encontrados em `src/Example` deste componente.

### Observable::fromEvent

`Observable::fromEvent` converte eventos do padrão PSR em sequências observáveis.

No pacote esqueleto hyperf-skeleton há, por padrão, um listener de evento que imprime instruções SQL, localizado por padrão em `app/Listener/DbQueryExecutedListener.php`. A seguir fazemos algumas otimizações nesse listener:

1. Imprimir apenas consultas SQL que ultrapassem 100ms.

2. Cada conexão imprime no máximo 1 vez por segundo, evitando que o disco seja inundado por programas problemáticos.

Sem o ReactiveX, o problema 1 ainda é fácil de resolver, mas o problema 2 exigiria um pouco mais de raciocínio. Com o ReactiveX, é possível resolver facilmente essas necessidades com o código de exemplo abaixo:

```php
<?php

declare(strict_types=1);

namespace Hyperf\ReactiveX\Example;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Logger\LoggerFactory;
use Hyperf\ReactiveX\Observable;
use Hyperf\Collection\Arr;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;

class SqlListener implements ListenerInterface
{
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql');
    }

    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
        ];
    }

    public function process(object $event)
    {
        Observable::fromEvent(QueryExecuted::class)
            ->filter(
                fn ($event) => $event->time > 100
            )
            ->groupBy(
                fn ($event) => $event->connectionName
            )
            ->flatMap(
                fn ($group) => $group->throttle(1000)
            )
            ->map(
                function ($event) {
                    $sql = $event->sql;
                    if (! Arr::isAssoc($event->bindings)) {
                        foreach ($event->bindings as $key => $value) {
                            $sql = Str::replaceFirst('?', "'{$value}'", $sql);
                        }
                    }
                    return [$event->connectionName, $event->time, $sql];
                }
            )->subscribe(
                fn ($message) => $this->logger->info(sprintf('slow log: [%s] [%s] %s', ...$message))
            );
    }
}
```

### Observable::fromChannel

Converte o Channel das corrotinas do Swoole em uma sequência observável.

O Channel nas corrotinas do Swoole é de leitura e escrita um-para-um. E se quisermos usar o Channel para fazer assinatura e publicação muitos-para-muitos no ReactiveX? Como fazer?

Consulte o exemplo abaixo.

```php
<?php

declare(strict_types=1);

use Hyperf\ReactiveX\Observable;
use Swoole\Coroutine\Channel;

$chan = new Channel(1);
$pub = Observable::fromChannel($chan)->publish();

$pub->subscribe(function ($x) {
    echo 'First Subscription:' . $x . PHP_EOL;
});
$pub->subscribe(function ($x) {
    echo 'Second Subscription:' . $x . PHP_EOL;
});
$pub->connect();

$chan->push('hello');
$chan->push('world');

// First Subscription: hello
// Second Subscription: hello
// First Subscription: world
// Second Subscription: world
```

### Observable::fromCoroutine

Cria uma ou várias corrotinas e converte o resultado da execução em uma sequência observável.

Agora fazemos duas funções competirem em corrotinas concorrentes: a que terminar primeiro retorna o seu resultado. O efeito é semelhante ao `Promise.race` do JavaScript.

```php
<?php

declare(strict_types=1);

use Hyperf\ReactiveX\Observable;
use Swoole\Coroutine\Channel;

$result = new Channel(1);
$o = Observable::fromCoroutine([function () {
    sleep(2);
    return 1;
}, function () {
    sleep(1);
    return 2;
}]);
$o->take(1)->subscribe(
    function ($x) use ($result) {
        $result->push($x);
    }
);
echo $result->pop(); // 2;
```

### Observable::fromHttpRoute

Todas as requisições HTTP são, na verdade, orientadas a eventos. Portanto, as rotas de requisição HTTP também podem ser assumidas pelo ReactiveX.

> Como vamos adicionar rotas, é imprescindível executar antes da inicialização do Server, por exemplo, em um listener do evento `BootApplication`.

Suponha que tenhamos uma rota de upload com muito tráfego, que precise ser armazenada em buffer na memória e, após dez uploads, ser gravada em lote no banco de dados.

```php
<?php

declare(strict_types=1);

namespace Hyperf\ReactiveX\Example;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\ReactiveX\Observable;
use Psr\Http\Message\RequestInterface;

class BatchSaveRoute implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event)
    {
        Observable::fromHttpRoute(['POST', 'PUT'], '/save')
            ->map(
                function (RequestInterface $request) {
                    return $request->getBody();
                }
            )
            ->bufferWithCount(10)
            ->subscribe(
                function (array $bodies) {
                    echo count($bodies); //10
                }
            );
    }
}
```

Depois de assumir a rota, se for necessário controlar a Response retornada, você pode adicionar um terceiro parâmetro em fromHttpRoute, da mesma forma que na escrita normal de rotas, como

```php
$observable = Observable::fromHttpRoute('GET', '/hello-hyperf', 'App\Controller\IndexController::hello');
```

Nesse caso, o `Observable` atua de forma semelhante a um middleware: após obter a sequência observável do objeto de requisição, ele continua passando o objeto de requisição para o `Controller` real.

### IpcSubject

A comunicação entre processos do Swoole também é orientada a eventos. Este componente, com base nos quatro tipos de [Subject](https://mcxiaoke.gitbooks.io/rxdocs/content/Subject.html) fornecidos pelo RxPHP, oferece versões correspondentes entre processos, que podem ser usadas para compartilhar informações entre processos.

Por exemplo, precisamos criar um chat baseado em WebSocket com os seguintes requisitos:

1. As mensagens do chat precisam ser compartilhadas entre os `Processos Worker`.

2. Ao fazer login pela primeira vez, o usuário vê as 5 mensagens mais recentes.

Implementamos com a versão entre processos do `ReplaySubject`.

```php
<?php

declare(strict_types=1);

namespace Hyperf\ReactiveX\Example;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\ReactiveX\Contract\BroadcasterInterface;
use Hyperf\ReactiveX\IpcSubject;
use Rx\Subject\ReplaySubject;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    private IpcSubject $subject;

    private $subscriber = [];

    public function __construct(BroadcasterInterface $broadcaster)
    {
        $relaySubject = make(ReplaySubject::class, ['bufferSize' => 5]);
        // O primeiro parâmetro é o objeto Subject original do RxPHP.
        // O segundo parâmetro é o modo de broadcast; o padrão é broadcast para todos os processos
        // O terceiro parâmetro é o ID do canal; cada canal só recebe mensagens do mesmo canal.
        $this->subject = new IpcSubject($relaySubject, $broadcaster, 1);
    }

    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $this->subject->onNext($frame->data);
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        $this->subscriber[$fd]->dispose();
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $this->subscriber[$request->fd] = $this->subject->subscribe(function ($data) use ($server, $request) {
            $server->push($request->fd, $data);
        });
    }
}

```

Para facilitar o uso, este componente encapsula, com `IpcSubject`, um "barramento de mensagens" `MessageBusInterface`. Basta injetar `MessageBusInterface` para enviar e receber informações compartilhadas entre todos os processos (incluindo processos personalizados). Funcionalidades como centro de configuração podem ser implementadas facilmente por meio dele.

```php
<?php
$bus = make(Hyperf\ReactiveX\MessageBusInterface::class);
// Broadcast de informações para todos os processos
$bus->onNext('Hello Hyperf');
// Assinar informações
$bus->subscribe(function($message){
    echo $message;
});
```

> Como o ReactiveX precisa usar o event loop, lembre-se de chamar as APIs relacionadas ao ReactiveX somente depois que o Swoole Server estiver iniciado.

## Materiais de referência

* [Documentação do Rx em chinês](https://mcxiaoke.gitbooks.io/rxdocs/content/)
* [Documentação do Rx em inglês](http://reactivex.io/)
* [Repositório do RxPHP](https://github.com/ReactiveX/RxPHP)
