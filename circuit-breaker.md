# Circuit breaker

## Instalação

```
composer require hyperf/circuit-breaker
```

## Por que precisamos de circuit breaker?

Em sistemas distribuídos, é comum ocorrer a indisponibilidade de todo o sistema devido à indisponibilidade de algum serviço básico; esse fenômeno é chamado de efeito avalanche de serviços. Para lidar com a avalanche de serviços, uma prática comum é a degradação de serviços. O componente [hyperf/circuit-breaker](https://github.com/hyperf/circuit-breaker) existe justamente para resolver esse problema.

## Usar o circuit breaker

O uso do circuit breaker é muito simples: basta adicionar a anotação `Hyperf\CircuitBreaker\Annotation\CircuitBreaker` para acionar o circuit breaker conforme a estratégia definida.
Por exemplo, precisamos consultar a lista de usuários em outro serviço; a lista de usuários precisa se relacionar com muitas tabelas, e a eficiência da consulta é baixa, mas, quando a concorrência não é alta, a velocidade de resposta ainda é aceitável. Uma vez que a concorrência aumente repentinamente, a velocidade de resposta ficará mais lenta e causará consultas lentas no serviço do outro lado. Nesse momento, basta configurar o tempo limite de circuit breaker `timeout` como 0,05 segundo, acionar o circuit breaker quando o contador de falhas `failCounter` passar de 1, e definir o `fallback` correspondente como o método `searchFallback` da classe `App\Service\UserService`. Assim, quando a resposta expirar e o circuit breaker for acionado, o serviço do outro lado não será mais requisitado; em vez disso, o serviço será diretamente degradado e os dados serão retornados a partir do projeto atual, ou seja, retornados conforme o método especificado em `fallback`.

```php
<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\UserServiceClient;
use Hyperf\CircuitBreaker\Annotation\CircuitBreaker;
use Hyperf\Di\Annotation\Inject;

class UserService
{
    #[Inject]
    private UserServiceClient $client;

    #[CircuitBreaker(options: ['timeout' => 0.05], failCounter: 1, successCounter: 1, fallback: [UserService::class, 'searchFallback'])]
    public function search($offset, $limit)
    {
        return $this->client->users($offset, $limit);
    }

    public function searchFallback($offset, $limit)
    {
        return [];
    }
}

```

A estratégia padrão de circuit breaker é a `estratégia de timeout`. Se você quiser implementar sua própria estratégia de circuit breaker, basta implementar um `Handler` que herde de `Hyperf\CircuitBreaker\Handler\AbstractHandler`.

```php
<?php
declare(strict_types=1);

namespace Hyperf\CircuitBreaker\Handler;

use Hyperf\CircuitBreaker\Annotation\CircuitBreaker as Annotation;
use Hyperf\CircuitBreaker\CircuitBreaker;
use Hyperf\CircuitBreaker\Exception\TimeoutException;
use Hyperf\Di\Aop\ProceedingJoinPoint;

class DemoHandler extends AbstractHandler
{
    const DEFAULT_TIMEOUT = 5;

    protected function process(ProceedingJoinPoint $proceedingJoinPoint, CircuitBreaker $breaker, Annotation $annotation)
    {
        $result = $proceedingJoinPoint->process();

        if (is_break()) {
            throw new TimeoutException('timeout, use ' . $use . 's', $result);
        }

        return $result;
    }
}

```
