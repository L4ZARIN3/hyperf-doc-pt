# Limitador de taxa por token bucket

## Instalação

```bash
composer require hyperf/rate-limit
```

## Configuração

### Publicar configuração

```bash
php bin/hyperf.php vendor:publish hyperf/rate-limit
```

### Descrição da configuração

|  Configuração          | Valor padrão | Tipo |       Observação        |
|:--------------:|:------:|:--------------:|:-------------------:|
| create         | 1      |int| Número de tokens gerados por segundo      |
| consume        | 1      |int| Número de tokens consumidos por requisição  |
| capacity       | 2      |int| Capacidade máxima do token bucket      |
| limitCallback  | `[]`   |null\|callable| Método de callback ao acionar a limitação de taxa  |
| waitTimeout    | 1      |int| Tempo limite de espera na fila        |
| key            | URL da requisição atual     |callable\|string| Chave da limitação de taxa        |

## Usar o limitador de taxa

O componente fornece a anotação `Hyperf\RateLimit\Annotation\RateLimit`, que atua em classes e métodos de classe e pode substituir o arquivo de configuração. Por exemplo,

```php
<?php

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

#[Controller(prefix: "rate-limit")]
class RateLimitController
{
    #[RequestMapping(path: "test")]
    #[RateLimit(create: 1, capacity: 3)]
    public function test()
    {
        return ["QPS 1, pico 3"];
    }

    #[RequestMapping(path: "test2")]
    #[RateLimit(create: 2, consume: 2, capacity: 4)]
    public function test2()
    {
        return ["QPS 2, pico 2"];
    }
}
``` 
Prioridade de configuração `anotação do método > anotação da classe > arquivo de configuração > configuração padrão`

## Acionar a limitação de taxa
Quando a limitação de taxa é acionada, por padrão é lançada a exceção `Hyperf\RateLimit\Exception\RateLimitException`

É possível tratar por meio do [tratamento de exceções](exception-handler.md) ou configurando o callback de limitação de taxa `limitCallback`.

Por exemplo:
```php
<?php

namespace App\Controller;

use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

#[Controller(prefix: "rate-limit")]
#[RateLimit(limitCallback: [RateLimitController::class, "limitCallback"])]
class RateLimitController
{
    #[RequestMapping(path: "test")]
    #[RateLimit(create: 1, capacity: 3)]
    public function test()
    {
        return ["QPS 1, pico 3"];
    }
    
    public static function limitCallback(float $seconds, ProceedingJoinPoint $proceedingJoinPoint)
    {
        // $seconds é o intervalo até a próxima geração de Token, em segundos
        // $proceedingJoinPoint é o ponto de junção executado nesta requisição
        // Você pode continuar a execução chamando `$proceedingJoinPoint->process()`, ou tratar por conta própria
        return $proceedingJoinPoint->process();
    }
}
```

## Personalizar a chave da limitação de taxa por token bucket

A chave padrão é baseada na `url` da requisição atual; quando um usuário aciona a limitação de taxa, os outros usuários também têm suas requisições a essa `url` limitadas;

Se precisar de limitação de taxa com granularidades diferentes, como por dimensão de usuário, você pode limitar pela `ID` do usuário, fazendo com que o usuário A seja limitado enquanto o usuário B faça requisições normalmente:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;

class TestController
{
    /**
     * @RateLimit(create=1, capacity=3, key={TestController::class, "getUserId"})
     */
    public function test()
    {
        return ["QPS 1, pico 3"];
    }

    public static function getUserId(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        // Da mesma forma, é possível limitar por dimensões como número de celular, endereço IP etc.
        return $request->input('user_id');
    }
}
```
