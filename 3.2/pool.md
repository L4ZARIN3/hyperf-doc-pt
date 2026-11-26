# Pool de conexões

## Instalação

```bash
composer require hyperf/pool
```

## Por que precisamos de um pool de conexões?

Quando a concorrência é muito baixa, as conexões podem ser estabelecidas temporariamente, mas quando a vazão do serviço chega a centenas ou milhares, `estabelecer conexões (Connect)` e `destruir conexões (Close)` com frequência pode se tornar um gargalo do serviço. Então, quando o serviço inicia, primeiro estabelecemos várias conexões e as armazenamos em uma fila; quando necessário, retiramos uma da fila e a usamos, devolvendo-a à fila após o uso. O que mantém essa estrutura de dados de fila é o pool de conexões.

## Usar o pool de conexões

Para os componentes fornecidos oficialmente pelo Hyperf, o pool de conexões já está integrado, sem nenhuma percepção no uso: a camada inferior obtém e devolve as conexões automaticamente.

## Pool de conexões personalizado

Para definir um pool de conexões, primeiro é preciso implementar uma subclasse que herde de `Hyperf\Pool\Pool` e implementar o método abstrato `createConnection`, retornando um objeto que implemente a interface `Hyperf\Contract\ConnectionInterface`. Assim, seu objeto de pool de conexões estará pronto, como no exemplo abaixo:
```php
<?php
namespace App\Pool;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;

class MyConnectionPool extends Pool
{
    public function createConnection(): ConnectionInterface
    {
        return new MyConnection();
    }
}
``` 
Assim, você pode obter e devolver conexões chamando os métodos `get(): ConnectionInterface` e `release(ConnectionInterface $connection): void` no objeto `MyConnectionPool` já instanciado.   

## SimplePool

Aqui o framework fornece uma implementação de pool de conexões muito simples.

```php
<?php

use Hyperf\Pool\SimplePool\PoolFactory;
use Swoole\Coroutine\Http\Client;

$factory = $container->get(PoolFactory::class);

$pool = $factory->get('your pool name', function () use ($host, $port, $ssl) {
    return new Client($host, $port, $ssl);
}, [
    'max_connections' => 50
]);

$connection = $pool->get();

$client = $connection->getConnection(); // Ou seja, o Client acima.

// Faça algo.

$connection->release();

```

## Componente de baixa frequência

O pool de conexões inclui a interface `LowFrequencyInterface`. O componente de baixa frequência usado por padrão decide, com base na frequência de obtenção de conexões do pool, se deve liberar as conexões excedentes do pool.

Se precisarmos substituir o componente de baixa frequência correspondente, podemos substituí-lo diretamente na configuração `dependencies`. Abaixo usamos o componente de banco de dados como exemplo.

```php
<?php

declare(strict_types=1);

namespace App\Pool;

class Frequency extends \Hyperf\Pool\Frequency
{
    /**
     * Intervalo de tempo em que a frequência é calculada
     */
    protected int $time = 10;

    /**
     * Frequência que aciona a baixa frequência
     */
    protected int $lowFrequency = 5;

    /**
     * Intervalo mínimo de tempo para acionar a baixa frequência consecutivamente
     */
    protected int $lowFrequencyInterval = 60;
}

```

A alteração do mapeamento é a seguinte

```php
<?php
return [
    Hyperf\DbConnection\Frequency::class => App\Pool\Frequency::class,
];
```

### Frequência constante

Naturalmente, o framework também fornece outro componente de baixa frequência, o `ConstantFrequency`.

Quando este componente é instanciado, ele inicia um timer que, em intervalos fixos, chama o método `Pool::flushOne(false)`. Esse método retira uma conexão do pool de conexões e, após verificar que ela excede o tempo de ociosidade, a destrói.

```php
<?php
return [
    Hyperf\DbConnection\Frequency::class => Hyperf\Pool\ConstantFrequency::class,
];
```
