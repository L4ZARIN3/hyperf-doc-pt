# Redis

## Instalação

```shell
composer require hyperf/redis
```

## Configuração

|     Item de configuração     |  Tipo   |   Valor padrão    |              Observação              |
|:--------------:|:-------:|:-----------:|:------------------------------:|
|      host      | string  | 'localhost' |           Endereço do Redis            |
|      auth      | string  |     Nenhum      |              Senha              |
|      port      | integer |    6379     |              Porta              |
|       db       | integer |      0      |               DB               |
| cluster.enable | boolean |    false    |          Se o modo cluster está habilitado          |
|  cluster.name  | string  |    null     |             Nome do cluster             |
| cluster.seeds  |  array  |     []      | Array de endereços de conexão do cluster ['host:port'] |
|      pool      | object  |     {}      |           Configuração do pool de conexões           |
|    options     | object  |     {}      |         Opções de configuração do Redis         |

```php
<?php
return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', ''),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'cluster' => [
            'enable' => (bool) env('REDIS_CLUSTER_ENABLE', false),
            'name' => null,
            'seeds' => [],
        ],
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
        'options' => [ // Opções do cliente Redis; consulte https://github.com/phpredis/phpredis#setoption
            \Redis::OPT_PREFIX => env('REDIS_PREFIX', ''),
            // ou 'prefix' => env('REDIS_PREFIX', ''), v3.0.38 ou superior
        ],
    ],
];

```

Para publicar o arquivo de configuração completo, use o comando

```shell
php bin/hyperf.php vendor:publish hyperf/redis
```

## Uso

`hyperf/redis` implementa o proxy e o pool de conexões do `ext-redis`; o usuário pode injetar diretamente `\Hyperf\Redis\Redis` pelo contêiner de injeção de dependência para usar o cliente Redis. O que é realmente obtido é um objeto proxy de `\Redis`.

```php
<?php
use Hyperf\Context\ApplicationContext;

$container = ApplicationContext::getContainer();

$redis = $container->get(\Hyperf\Redis\Redis::class);
$result = $redis->keys('*');
```

## Configuração de múltiplos bancos

Às vezes, no uso real, um banco `Redis` não atende às necessidades, e um projeto costuma precisar configurar vários bancos. Nesse caso, precisamos modificar o arquivo de configuração `redis.php`, conforme abaixo:

```php
<?php

return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', ''),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'cluster' => [
            'enable' => (bool) env('REDIS_CLUSTER_ENABLE', false),
            'name' => null,
            'seeds' => [],
        ],
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
    ],
    // Adiciona um pool de conexões Redis chamado foo
    'foo' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', ''),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => 1,
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
    ],
];

```

### Uso por meio de classe proxy

Podemos reescrever uma classe `FooRedis` e estender a classe `Hyperf\Redis\Redis`, alterando `poolName` para o `foo` mencionado acima, para concluir a troca do pool de conexões. Exemplo:

```php
<?php
use Hyperf\Redis\Redis;

class FooRedis extends Redis
{
    // Valor da key do Pool correspondente
    protected $poolName = 'foo';
}

// Obtém pelo contêiner DI ou injeta diretamente a classe atual
$redis = $this->container->get(FooRedis::class);

$result = $redis->keys('*');

```

### Usar a classe de fábrica

Quando cada banco corresponde a um cenário de uso fixo, usar uma classe proxy é um bom método de distinção, mas às vezes os requisitos podem ser mais dinâmicos. Nesse caso, podemos usar a classe de fábrica `Hyperf\Redis\RedisFactory` para passar dinamicamente o `poolName` e obter o cliente do pool de conexões correspondente, sem precisar criar uma classe proxy para cada banco. Exemplo abaixo:

```php
<?php
use Hyperf\Redis\RedisFactory;
use Hyperf\Context\ApplicationContext;

$container = ApplicationContext::getContainer();

// Obtém pelo contêiner DI ou injeta diretamente a classe RedisFactory
$redis = $container->get(RedisFactory::class)->get('foo');
$result = $redis->keys('*');
```

## Modo sentinela

Para habilitar o modo sentinela, modifique o arquivo `.env` ou o arquivo de configuração `redis.php` conforme abaixo

Múltiplos nós sentinela são separados por `;`

```env
REDIS_HOST=
REDIS_AUTH=Senha da instância Redis
REDIS_PORT=
REDIS_DB=
REDIS_SENTINEL_ENABLE=true
REDIS_SENTINEL_PASSWORD=Senha do sentinela Redis
REDIS_SENTINEL_NODE=192.168.89.129:26381;192.168.89.129:26380;
```

```php
<?php

return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', null),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'timeout' => 30.0,
        'reserved' => null,
        'retry_interval' => 0,
        'sentinel' => [
            'enable' => (bool) env('REDIS_SENTINEL_ENABLE', false),
            'master_name' => env('REDIS_MASTER_NAME', 'mymaster'),
            'nodes' => explode(';', env('REDIS_SENTINEL_NODE', '')),
            'persistent' => false,
            'read_timeout' => 30.0,
            'auth' =>  env('REDIS_SENTINEL_PASSWORD', ''),
        ],
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
    ],
];
```

## Modo cluster

### Usar `name`

Configure `cluster`, modifique `redis.ini`, ou também pode modificar o `Dockerfile` conforme abaixo

```shell
    # - configura PHP
    && { \
        echo "upload_max_filesize=100M"; \
        echo "post_max_size=108M"; \
        echo "memory_limit=1024M"; \
        echo "date.timezone=${TIMEZONE}"; \
        echo "redis.clusters.seeds = \"mycluster[]=localhost:7000&mycluster[]=localhost:7001\""; \
        echo "redis.clusters.timeout = \"mycluster=5\""; \
        echo "redis.clusters.read_timeout = \"mycluster=10\""; \
        echo "redis.clusters.auth = \"mycluster=password\"";
    } | tee conf.d/99-overrides.ini \
```

A configuração PHP correspondente é a seguinte

```php
<?php
// Outras configurações omitidas
return [
    'default' => [
        'cluster' => [
            'enable' => true,
            'name' => 'mycluster',
            'seeds' => [],
        ],
    ],
];
```

### Usar seeds

Claro que também é possível usar seeds diretamente sem configurar name. Conforme abaixo

```php
<?php
// Outras configurações omitidas
return [
    'default' => [
        'cluster' => [
            'enable' => true,
            'name' => null,
            'seeds' => [
                '192.168.1.110:6379',
                '192.168.1.111:6379',
            ],
        ],
    ],
];
```

## Options

O usuário pode modificar `options` para definir as opções de configuração do `Redis`.

Por exemplo, alterar a serialização do `Redis` para serialização `PHP`.

```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', null),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP,
            // ou 'serializer' => \Redis::SERIALIZER_PHP, v3.0.38 ou superior
        ],
    ],
];
```

Por exemplo, definir que o `Redis` nunca atinja timeout

```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', null),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            \Redis::OPT_READ_TIMEOUT => -1,
            // ou 'read_timeout' => -1, v3.1.3 ou superior
        ],
    ],
];
```

> Em algumas versões da extensão `phpredis`, o `value` de `option` deve ser do tipo `string`.
