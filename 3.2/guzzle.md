# Cliente Guzzle HTTP

O componente [hyperf/guzzle](https://github.com/hyperf/guzzle) realiza processamento de corrotina com base no Guzzle, substituindo o cliente HTTP do Swoole como driver de corrotina dentro do Guzzle, para alcançar a corrotinização do cliente HTTP.

## Instalação

```bash
composer require hyperf/guzzle
```

## Uso

Basta definir `Hyperf\Guzzle\CoroutineHandler` deste componente como handler no cliente Guzzle para que ele passe a rodar de forma corrotinizada. Para facilitar a criação de objetos Guzzle de corrotina, fornecemos uma classe de fábrica `Hyperf\Guzzle\ClientFactory` para criar clientes de forma conveniente. O exemplo de código é o seguinte:

```php
<?php 
use Hyperf\Guzzle\ClientFactory;

class Foo
{
    private ClientFactory $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    
    public function bar()
    {
        // $options é equivalente ao parâmetro $config do construtor de GuzzleHttp\Client
        $options = [];
        // $client é um objeto GuzzleHttp\Client corrotinizado
        $client = $this->clientFactory->create($options);
    }
}
```

### Usando a versão ^7.0

A dependência do componente em relação ao `Guzzle` foi alterada de `^6.3` para `^6.3 | ^7.0`; por padrão, já é possível instalar a versão `^7.0`, mas os seguintes componentes entram em conflito com `^7.0`.

- hyperf/metric

Você pode executar ativamente as seguintes operações para resolver o conflito

```
composer require "promphp/prometheus_client_php:2.2.1"
```

- overtrue/flysystem-cos

Como a biblioteca de dependência depende de `guzzlehttp/guzzle-services`, que não suporta `^7.0`, por enquanto não é possível resolver.

## Usando configuração do Swoole

Às vezes queremos modificar diretamente a configuração do `Swoole`, por isso também fornecemos os itens de configuração relacionados. No entanto, essa configuração não tem efeito no `Cliente Curl Guzzle`, então use com cuidado.

> Essa configuração substitui a configuração original; por exemplo, o timeout abaixo será substituído por 10.

```php
<?php
use GuzzleHttp\Client;
use Hyperf\Guzzle\CoroutineHandler;
use GuzzleHttp\HandlerStack;

$client = new Client([
    'base_uri' => 'http://127.0.0.1:8080',
    'handler' => HandlerStack::create(new CoroutineHandler()),
    'timeout' => 5,
    'swoole' => [
        'timeout' => 10,
        'socket_buffer_size' => 1024 * 1024 * 2,
    ],
]);

$response = $client->get('/');

```

## Pool de conexões

Além de implementar `Hyperf\Guzzle\CoroutineHandler`, o Hyperf também implementa `Hyperf\Guzzle\PoolHandler` com base em `Hyperf\Pool\SimplePool`.

### Motivo

Em termos simples, o número de conexões TCP do host tem um limite superior. Quando nossa concorrência ultrapassa esse limite, as requisições não conseguem ser estabelecidas normalmente. Além disso, após o encerramento de uma conexão TCP, há ainda uma fase TIME-WAIT, portanto a conexão não pode ser liberada em tempo real. Isso faz com que a concorrência real possa ficar bem abaixo do limite TCP. Por isso, precisamos de um pool de conexões para manter essa fase, minimizando o impacto causado pelo TIME-WAIT e permitindo a reutilização das conexões TCP.

### Uso

```php
<?php
use GuzzleHttp\Client;
use Hyperf\Coroutine\Coroutine;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\PoolHandler;
use Hyperf\Guzzle\RetryMiddleware;

$handler = null;
if (Coroutine::inCoroutine()) {
    $handler = make(PoolHandler::class, [
        'option' => [
            'max_connections' => 50,
        ],
    ]);
}

// Middleware de retry padrão
$retry = make(RetryMiddleware::class, [
    'retries' => 1,
    'delay' => 10,
]);

$stack = HandlerStack::create($handler);
$stack->push($retry->getMiddleware(), 'retry');

$client = make(Client::class, [
    'config' => [
        'handler' => $stack,
    ],
]);
```

Além disso, o framework também fornece `HandlerStackFactory` para facilitar a criação do `$stack` acima.

```php
<?php
use Hyperf\Guzzle\HandlerStackFactory;
use GuzzleHttp\Client;

$factory = new HandlerStackFactory();
$stack = $factory->create();

$client = make(Client::class, [
    'config' => [
        'handler' => $stack,
    ],
]);
```

## Usando `ClassMap` para substituir `GuzzleHttp\Client`

Se um componente de terceiros não fornecer uma interface para substituir o `Handler`, também podemos usar o recurso `ClassMap` para substituir diretamente o `Client` e alcançar o objetivo de corrotinizar o cliente.

> Claro, também é possível usar SWOOLE_HOOK para atingir o mesmo objetivo.

O exemplo de código é o seguinte:

class_map/GuzzleHttp/Client.php

```php
<?php
namespace GuzzleHttp;

use GuzzleHttp\Psr7;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Coroutine\Coroutine;

class Client implements ClientInterface
{
    // O código omite outros trechos inalterados

    public function __construct(array $config = [])
    {
        $inCoroutine = Coroutine::inCoroutine();
        if (!isset($config['handler'])) {
            // O Handler correspondente pode ser escolhido conforme a necessidade: CoroutineHandler ou PoolHandler
            $config['handler'] = HandlerStack::create($inCoroutine ? new CoroutineHandler() : null);
        } elseif ($inCoroutine && $config['handler'] instanceof HandlerStack) {
            $config['handler']->setHandler(new CoroutineHandler());
        } elseif (!is_callable($config['handler'])) {
            throw new \InvalidArgumentException('handler must be a callable');
        }

        // Convert the base_uri to a UriInterface
        if (isset($config['base_uri'])) {
            $config['base_uri'] = Psr7\uri_for($config['base_uri']);
        }

        $this->configureDefaults($config);
    }
}

```

config/autoload/annotations.php

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;

return [
    'scan' => [
        // ...
        'class_map' => [
            Client::class => BASE_PATH . '/class_map/GuzzleHttp/Client.php',
        ],
    ],
];
```
