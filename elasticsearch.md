# Elasticsearch

[hyperf/elasticsearch](https://github.com/hyperf/elasticsearch) consiste principalmente em um encapsulamento de fábrica para criar objetos de cliente do [elasticsearch-php](https://github.com/elastic/elasticsearch-php). O [elasticsearch-php](https://github.com/elastic/elasticsearch-php) usa por padrão o cliente `Guzzle Ring`; em [hyperf/guzzle](https://github.com/hyperf/guzzle) implementamos uma versão de `Handler` para corrotinas, portanto é possível usar diretamente `Hyperf\Elasticsearch\ClientBuilderFactory` para criar um novo `Builder`.

## Instalação

```bash
composer require hyperf/elasticsearch
```
## Uso

### Criar o cliente com `ClientBuilderFactory`

```php
<?php

use Hyperf\Elasticsearch\ClientBuilderFactory;

// Se criado em ambiente de corrotina, o Handler da versão corrotina é usado automaticamente; em ambiente sem corrotina, nada muda
$builder = $this->container->get(ClientBuilderFactory::class)->create();

$client = $builder->setHosts(['http://127.0.0.1:9200'])->build();

$info = $client->info();
```

### Criar o cliente por conta própria

```php
<?php

use Elasticsearch\ClientBuilder;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Swoole\Coroutine;

$builder = ClientBuilder::create();
if (Coroutine::getCid() > 0) {
    $handler = make(PoolHandler::class, [
        'option' => [
            'max_connections' => 50,
        ],
    ]);
    $builder->setHandler($handler);
}

$client = $builder->setHosts(['http://127.0.0.1:9200'])->build();

$info = $client->info();
```

### Como definir usuário e senha

Quando o mecanismo de busca exige usuário e senha, por exemplo, se você adquiriu a versão Enterprise do `Elasticsearch`, pode usar o seguinte `host` para acessar o mecanismo de busca.

```
http://username:password@xxxx.aliyuncs.com:9200
```
