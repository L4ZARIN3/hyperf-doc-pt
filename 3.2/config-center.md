# Introdução

Hyperf oferece suporte à configuração externalizada para sistemas distribuídos e, por padrão, adapta:

- [ctripcorp/apollo](https://github.com/ctripcorp/apollo), open source da Ctrip, cujo suporte funcional é fornecido pelo componente [hyperf/config-apollo](https://github.com/hyperf/config-apollo).
- O serviço gratuito de centro de configuração [Gerenciamento de Configuração de Aplicação (ACM, Application Config Manager)](https://help.aliyun.com/product/59604.html) fornecido pela Alibaba Cloud, cujo suporte funcional é fornecido pelo componente [hyperf/config-aliyun-acm](https://github.com/hyperf/config-aliyun-acm).
- ETCD
- Nacos
- Zookeeper

## Por que usar um centro de configuração?

Com o desenvolvimento do negócio e a evolução da arquitetura de microsserviços, a quantidade de serviços e as configurações das aplicações aumentam cada vez mais (diversos microsserviços, diversos endereços de servidores, diversos parâmetros). O formato tradicional de arquivos de configuração e o formato de banco de dados podem já não atender às exigências dos desenvolvedores quanto ao gerenciamento de configurações. Ao mesmo tempo, o gerenciamento de configurações pode envolver ACL (gerenciamento de permissões), gerenciamento de versões e rollback de configurações, validação de formato, publicação gradual de configurações, isolamento de configurações por cluster, entre outros problemas, bem como:

- Segurança: as configurações acompanham o código-fonte e são armazenadas no sistema de controle de versão, o que pode facilmente causar vazamento de configurações
- Atualidade: para alterar uma configuração, é necessário modificar cada servidor e cada aplicação e reiniciar o serviço
- Limitação: não é possível oferecer suporte a ajustes dinâmicos, como chave de log, chave de funcionalidade etc.   

Portanto, podemos usar um centro de configuração para gerenciar de forma unificada e com um método científico de gerenciamento as configurações relacionadas.

## Instalação

### Camada de acesso unificada do centro de configuração

```bash
composer require hyperf/config-center
```

### Uso do Apollo: instalação necessária

```bash
composer require hyperf/config-apollo
```

### Uso do Aliyun ACM: instalação necessária

```bash
composer require hyperf/config-aliyun-acm
```

### Uso do Etcd: instalação necessária

```bash
composer require hyperf/config-etcd
```

### Uso do Nacos: instalação necessária

```bash
composer require hyperf/config-nacos
```

#### Fluxo bidirecional gRPC

O centro de configuração tradicional do Nacos baseia-se em polling curto para sincronizar configurações, o que faz com que, dentro do intervalo de polling, o serviço não consiga obter a configuração mais recente. A versão `Nacos V2` adicionou suporte a fluxo bidirecional gRPC, caso você queira que o Nacos, ao detectar uma alteração de configuração, a envie prontamente aos serviços relacionados.

Você pode seguir os passos abaixo para habilitar o recurso de fluxo bidirecional gRPC.

- Primeiro, instalamos os componentes necessários

```shell
composer require "hyperf/http2-client:3.1.*"
composer require "hyperf/grpc:3.1.*"
```

- Modifique o item de configuração

Altere `config_center.drivers.nacos.client.grpc.enable` para `true`, conforme abaixo

```php
<?php

declare(strict_types=1);

use Hyperf\ConfigApollo\PullMode;
use Hyperf\ConfigCenter\Mode;

return [
    'enable' => (bool) env('CONFIG_CENTER_ENABLE', true),
    'driver' => env('CONFIG_CENTER_DRIVER', 'nacos'),
    'mode' => env('CONFIG_CENTER_MODE', Mode::PROCESS),
    'drivers' => [
        'nacos' => [
            'driver' => Hyperf\ConfigNacos\NacosDriver::class,
            'merge_mode' => Hyperf\ConfigNacos\Constants::CONFIG_MERGE_OVERWRITE,
            'interval' => 3,
            'default_key' => 'nacos_config',
            'listener_config' => [
                'nacos_config' => [
                    'tenant' => 'tenant', // corresponde a service.namespaceId
                    'data_id' => 'hyperf-service-config',
                    'group' => 'DEFAULT_GROUP',
                ],
            ],
            'client' => [
                // URL do servidor nacos, como https://nacos.hyperf.io; tem prioridade sobre host:port
                // 'uri' => '',
                'host' => '127.0.0.1',
                'port' => 8848,
                'username' => null,
                'password' => null,
                'guzzle' => [
                    'config' => null,
                ],
                // Suportado apenas no nacos v2.
                'grpc' => [
                    'enable' => true,
                    'heartbeat' => 10,
                ],
            ],
        ],
    ],
];

```

- Em seguida, basta iniciar o serviço

### Uso do Zookeeper: instalação necessária

```bash
composer require hyperf/config-zookeeper
```

## Acesso ao centro de configuração

### Arquivo de configuração

```php
<?php

declare(strict_types=1);

use Hyperf\ConfigCenter\Mode;

return [
    // Se o centro de configuração está habilitado
    'enable' => (bool) env('CONFIG_CENTER_ENABLE', true),
    // Tipo de driver usado, correspondente à chave sob drivers na mesma configuração
    'driver' => env('CONFIG_CENTER_DRIVER', 'apollo'),
    // Modo de execução do centro de configuração; para modelo multiprocesso recomenda-se o modo PROCESS, para modelo monoprocesso recomenda-se o modo COROUTINE
    'mode' => env('CONFIG_CENTER_MODE', Mode::PROCESS),
    'drivers' => [
        'apollo' => [
            'driver' => Hyperf\ConfigApollo\ApolloDriver::class,
            // Apollo Server
            'server' => 'http://127.0.0.1:9080',
            // Seu AppId
            'appid' => 'test',
            // Cluster onde a aplicação atual está
            'cluster' => 'default',
            // Namespace que a aplicação atual precisa acessar; é possível configurar vários
            'namespaces' => [
                'application',
            ],
            // Intervalo de atualização da configuração (segundos)
            'interval' => 5,
            // Modo estrito: quando false, os valores de configuração obtidos são todos do tipo string; quando true, os valores obtidos são convertidos para o tipo de dados do valor original da configuração
            'strict_mode' => false,
            // IP do cliente
            'client_ip' => \Hyperf\Support\Network::ip(),
            // Tempo limite para buscar a configuração
            'pullTimeout' => 10,
            // Intervalo entre buscas de configuração
            'interval_timeout' => 1,
        ],
        'nacos' => [
            'driver' => Hyperf\ConfigNacos\NacosDriver::class,
            // Modo de mesclagem de configuração; suporta sobrescrever e mesclar
            'merge_mode' => Hyperf\ConfigNacos\Constants::CONFIG_MERGE_OVERWRITE,
            'interval' => 3,
            // Se a chave de mapeamento correspondente não estiver definida, usa a chave padrão
            'default_key' => 'nacos_config',
            'listener_config' => [
                // dataId, group, tenant, type, content
                // CHAVE de configuração após mapeamento => configuração real no Nacos
                'nacos_config' => [
                    'tenant' => 'tenant', // corresponde a service.namespaceId
                    'data_id' => 'hyperf-service-config',
                    'group' => 'DEFAULT_GROUP',
                ],
                'nacos_config.data' => [
                    'data_id' => 'hyperf-service-config-yml',
                    'group' => 'DEFAULT_GROUP',
                    'type' => 'yml',
                ],
            ],
            'client' => [
                // URL do servidor nacos, como https://nacos.hyperf.io; tem prioridade sobre host:port
                // 'uri' => '',
                'host' => '127.0.0.1',
                'port' => 8848,
                'username' => null,
                'password' => null,
                'guzzle' => [
                    'config' => null,
                ],
            ],
        ],
        'aliyun_acm' => [
            'driver' => Hyperf\ConfigAliyunAcm\AliyunAcmDriver::class,
            // Intervalo de atualização da configuração (segundos)
            'interval' => 5,
            // Endereço de endpoint do ACM da Alibaba Cloud; depende da sua zona de disponibilidade
            'endpoint' => env('ALIYUN_ACM_ENDPOINT', 'acm.aliyun.com'),
            // Namespace que a aplicação atual precisa acessar
            'namespace' => env('ALIYUN_ACM_NAMESPACE', ''),
            // Data ID correspondente à sua configuração
            'data_id' => env('ALIYUN_ACM_DATA_ID', ''),
            // Group correspondente à sua configuração
            'group' => env('ALIYUN_ACM_GROUP', 'DEFAULT_GROUP'),
            // Access Key da sua conta da Alibaba Cloud
            'access_key' => env('ALIYUN_ACM_AK', ''),
            // Secret Key da sua conta da Alibaba Cloud
            'secret_key' => env('ALIYUN_ACM_SK', ''),
            'ecs_ram_role' => env('ALIYUN_ACM_RAM_ROLE', ''),
        ],
        'etcd' => [
            'driver' => Hyperf\ConfigEtcd\EtcdDriver::class,
            'packer' => Hyperf\Codec\Packer\JsonPacker::class,
            // Prefixo dos dados que precisam ser sincronizados
            'namespaces' => [
                '/application',
            ],
            // Relação de mapeamento entre `Etcd` e `Config`. Um `key` que não exista no mapeamento não será sincronizado para `Config`
            'mapping' => [
                // chave etcd => chave config
                '/application/test' => 'test',
            ],
            // Intervalo de atualização da configuração (segundos)
            'interval' => 5,
            'client' => [
                # Cliente Etcd
                'uri' => 'http://127.0.0.1:2379',
                'version' => 'v3beta',
                'options' => [
                    'timeout' => 10,
                ],
            ],
        ],
        'zookeeper' => [
            'driver' => Hyperf\ConfigZookeeper\ZookeeperDriver::class,
            'server' => env('ZOOKEEPER_SERVER', '127.0.0.1:2181'),
            'path' => env('ZOOKEEPER_CONFIG_PATH', '/conf'),
            'interval' => 5,
        ],
    ],
];
```

Caso o arquivo de configuração não exista, execute o comando `php bin/hyperf.php vendor:publish hyperf/config-center` para gerá-lo.

## Escopo da atualização de configuração

Na implementação padrão da funcionalidade, um processo `ConfigFetcherProcess` busca, conforme o `interval` configurado, as configurações do `namespace` correspondente no servidor do centro de configuração e, por meio de comunicação IPC, transmite as novas configurações obtidas para cada Worker, atualizando-as no objeto correspondente a `Hyperf\Contract\ConfigInterface`.   
Vale observar que as configurações atualizadas atualizam apenas o objeto `Config`; portanto, limitam-se a configurações da camada de aplicação ou de negócio e não envolvem alterações de configuração da camada do framework, pois mudanças nessa camada exigem reinício do serviço. Se você tiver essa necessidade, também pode atingir o objetivo implementando seu próprio `ConfigFetcherProcess`.

## Evento de atualização de configuração

Durante o funcionamento do centro de configuração, quando uma configuração é alterada, o evento `Hyperf\ConfigCenter\Event\ConfigChanged` é disparado de forma correspondente. Você pode ouvir esses eventos para atender às suas necessidades.

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\ConfigCenter\Event\ConfigChanged;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            ConfigChanged::class,
        ];
    }

    public function process(object $event)
    {
        var_dump($event);
    }
}
```
