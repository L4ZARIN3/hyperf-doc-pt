# Nacos

Um cliente `PHP` de corrotina para o `Nacos`, perfeitamente integrado ao centro de configuração e à governança de microsserviços do `Hyperf`.

## Instalação

```shell
composer require hyperf/nacos
```

### Publicar o arquivo de configuração

```shell
php bin/hyperf.php vendor:publish hyperf/nacos
```

```php
<?php

declare(strict_types=1);

return [
    // Desenvolvedores que não podem usar o formato IP:porta podem simplesmente configurar a url
    // 'url' => '',
    'host' => '127.0.0.1',
    'port' => 8848,
    'username' => null,
    'password' => null,
    'guzzle' => [
        'config' => null,
    ],
];

```

## Serviço e instância

O componente atual ainda mantém a funcionalidade de registro de serviço oferecida anteriormente.

Basta instalar o componente `hyperf/service-governance-nacos` e configurar os seguintes listeners e processos personalizados:

`Hyperf\ServiceGovernanceNacos\Listener\MainWorkerStartListener`
`Hyperf\ServiceGovernanceNacos\Listener\OnShutdownListener`
`Hyperf\ServiceGovernanceNacos\Process\InstanceBeatProcess`

Em seguida, adicione a configuração abaixo para escutar o evento `Shutdown`

- config/autoload/server.php

```php
<?php
use Hyperf\Server\Event;
return [
    // ...other
    'callbacks' => [
        // ...other
        Event::ON_SHUTDOWN => [Hyperf\Framework\Bootstrap\ShutdownCallback::class, 'onShutdown']
    ]
];
```

## Autenticação do serviço Alibaba Cloud

Ao usar o serviço Nacos da Alibaba Cloud, pode ser necessário usar autenticação AK e SK. O componente Nacos oferece suporte nativo a isso; basta adicionar a configuração correspondente de forma simples, como abaixo:

```php
<?php

declare(strict_types=1);

return [
    // nacos server url like https://nacos.hyperf.io, Priority is higher than host:port
    // 'uri' => 'http://127.0.0.1:8848/',
    // The nacos host info
    'host' => '127.0.0.1',
    'port' => 8848,
    // The nacos account info
    'username' => null,
    'password' => null,
    'access_key' => 'xxxx',
    'access_secret' => 'yyyy',
    'guzzle' => [
        'config' => null,
    ],
];
```
