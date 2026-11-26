# Cache

[hyperf/cache](https://github.com/hyperf/cache) fornece um cache de aspecto implementado com base em `Aspect`, e também fornece classes de cache que implementam `Psr\SimpleCache\CacheInterface`.

## Instalação
```
composer require hyperf/cache
```

## Configuração padrão

|  Configuração  |                  Valor padrão                  |         Observação          |
|:------:|:----------------------------------------:|:---------------------:|
| driver |  Hyperf\Cache\Driver\RedisDriver  | Driver de cache, o padrão é Redis |
| packer | Hyperf\Codec\Packer\PhpSerializerPacker |        Empacotador         |
| prefix |                   c:                   |       Prefixo do cache        |
| skip_cache_results |       []                   |       Resultados especificados que não serão cacheados   |

```php
<?php

return [
    'default' => [
        'driver' => Hyperf\Cache\Driver\RedisDriver::class,
        'packer' => Hyperf\Codec\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'skip_cache_results' => [],
    ],
];
```

## Uso

### Modo Simple Cache

Simple Cache é a especificação [PSR-16](https://www.php-fig.org/psr/psr-16/), este componente é compatível com essa especificação. Se você deseja usar uma classe de cache que implemente `Psr\SimpleCache\CacheInterface`, por exemplo, para reescrever o módulo de cache do `EasyWeChat`, basta obter `Psr\SimpleCache\CacheInterface` diretamente do contêiner de injeção de dependência, como mostrado abaixo:

```php

$cache = $container->get(\Psr\SimpleCache\CacheInterface::class);

```

### Modo anotação

O componente fornece a anotação `Hyperf\Cache\Annotation\Cacheable`, que atua em métodos de classe e permite configurar o prefixo de cache, o tempo de expiração, o listener e o grupo de cache correspondentes.
Por exemplo, o UserService fornece um método user, que pode consultar as informações do usuário correspondente ao id. Ao adicionar a anotação `Hyperf\Cache\Annotation\Cacheable`, o cache Redis correspondente será gerado automaticamente, com o valor de key `user:id` e o tempo de expiração de `9000` segundos. Na primeira consulta, os dados serão obtidos do banco de dados; nas consultas seguintes, serão obtidos do cache.

```php
<?php

namespace App\Services;

use App\Models\User;
use Hyperf\Cache\Annotation\Cacheable;

class UserService
{
    #[Cacheable(prefix: "user", ttl: 9000, listener: "user-update")]
    public function user($id)
    {
        $user = User::query()->where('id',$id)->first();

        if($user){
            return $user->toArray();
        }

        return null;
    }
}
```

### Limpar o cache gerado por `#[Cacheable]`

Fornecemos duas anotações, `CachePut` e `CacheEvict`, para implementar as operações de atualização e remoção de cache.

Claro, também podemos remover o cache por meio de eventos. Abaixo criamos um Service que fornece um método para nos ajudar a lidar com o cache.

> No entanto, recomendamos mais o uso de anotações do que de listeners

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class SystemService
{
    #[Inject]
    protected EventDispatcherInterface $dispatcher;

    public function flushCache($userId)
    {
        $this->dispatcher->dispatch(new DeleteListenerEvent('user-update', [$userId]));

        return true;
    }
}
```

Quando personalizamos o `value` do `Cacheable`, como no caso abaixo.

```php
<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Hyperf\Cache\Annotation\Cacheable;

class DemoService
{

    #[Cacheable(prefix: "cache", value: "_#{id}", listener: "user-update")]
    public function getCache(int $id)
    {
        return $id . '_' . uniqid();
    }
}
```

Então é necessário modificar correspondente a variável `$arguments` no construtor de `DeleteListenerEvent`, conforme o código abaixo.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class SystemService
{
    #[Inject]
    protected EventDispatcherInterface $dispatcher;

    public function flushCache($userId)
    {
        $this->dispatcher->dispatch(new DeleteListenerEvent('user-update', ['id' => $userId]));

        return true;
    }
}
```

## Introdução às anotações

### Cacheable

Por exemplo, na configuração abaixo, o prefixo de cache é `user`, o tempo de expiração é `7200` e o nome do evento de remoção é `USER_CACHE`. A KEY de cache correspondente gerada é `c:user:1`.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Hyperf\Cache\Annotation\Cacheable;

class UserService
{
    #[Cacheable(prefix: "user", ttl: 7200, listener: "USER_CACHE")]
    public function user(int $id): array
    {
        $user = User::query()->find($id);

        return [
            'user' => $user->toArray(),
            'uuid' => $this->unique(),
        ];
    }
}
```

Depois de definir `value`, o framework nomeará a chave `KEY` do cache de acordo com as regras definidas. Como no exemplo abaixo, quando `$user->id = 1`, a `KEY` de cache será `c:userBook:_1`

> Esta configuração também é suportada pelos outros tipos de anotações de cache descritos abaixo

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Hyperf\Cache\Annotation\Cacheable;

class UserBookService
{
    #[Cacheable(prefix: "userBook", ttl: 6666, value: "_#{user.id}")]
    public function userBook(User $user): array
    {
        return [
            'book' => $user->book->toArray(),
            'uuid' => $this->unique(),
        ];
    }
}
```

### CacheAhead

Por exemplo, na configuração abaixo, o prefixo de cache é `user`, o tempo de expiração é `7200`, a KEY de cache correspondente gerada é `c:user:1`, e entre 7200 - 600 segundos, a inicialização do cache é realizada a cada 10 segundos, até o primeiro sucesso.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Hyperf\Cache\Annotation\CacheAhead;

class UserService
{
    #[CacheAhead(prefix: "user", ttl: 7200, aheadSeconds: 600, lockSeconds: 10)]
    public function user(int $id): array
    {
        $user = User::query()->find($id);

        return [
            'user' => $user->toArray(),
            'uuid' => $this->unique(),
        ];
    }
}
```

### CachePut

`CachePut` é diferente de `Cacheable`: ele executa o corpo da função a cada chamada e depois reescreve o cache. Portanto, quando quisermos atualizar o cache, podemos chamar o método correspondente.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Hyperf\Cache\Annotation\CachePut;

class UserService
{
    #[CachePut(prefix: "user", ttl: 3601)]
    public function updateUser(int $id)
    {
        $user = User::query()->find($id);
        $user->name = 'HyperfDoc';
        $user->save();

        return [
            'user' => $user->toArray(),
            'uuid' => $this->unique(),
        ];
    }
}
```

### CacheEvict

CacheEvict é ainda mais fácil de entender: após executar o corpo do método, o cache é removido ativamente.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Cache\Annotation\CacheEvict;

class UserBookService
{
    #[CacheEvict(prefix: "userBook", value: "_#{id}")]
    public function updateUserBook(int $id)
    {
        return true;
    }
}
```

## Drivers de cache

### Driver Redis

`Hyperf\Cache\Driver\RedisDriver` armazena os dados de cache no `Redis`, sendo necessário configurar a `configuração do Redis` correspondente. Este é o modo padrão.

### Driver de memória de processo

Se você precisa armazenar dados em cache na memória, pode experimentar este driver.

A configuração é a seguinte:

```php
<?php

return [
    'memory' => [
        'driver' => Hyperf\Cache\Driver\MemoryDriver::class,
    ],
];
```

### Driver de memória de corrotina

Se você precisa armazenar dados em cache no `Context`, pode experimentar este driver. Por exemplo, no cenário de aplicação a seguir, `Demo::get` é chamado várias vezes em vários lugares, mas não se deseja consultar o `Redis` a cada vez.

```php
<?php
use Hyperf\Cache\Annotation\Cacheable;

class Demo
{    
    public function get($userId, $id)
    {
        return $this->getArray($userId)[$id] ?? 0;
    }

    #[Cacheable(prefix: "test", group: "co")]
    public function getArray(int $userId): array
    {
        return $this->redis->hGetAll($userId);
    }
}
```

A configuração correspondente é a seguinte:

```php
<?php

return [
    'co' => [
        'driver' => Hyperf\Cache\Driver\CoroutineMemoryDriver::class,
        'packer' => Hyperf\Codec\Packer\PhpSerializerPacker::class,
    ],
];
```
