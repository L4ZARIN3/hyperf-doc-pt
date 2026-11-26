# Registro de serviços

Após a divisão de serviços, a quantidade de serviços se torna muito grande, e cada serviço pode ter muitos nós de cluster para fornecê-lo. Assim, para garantir o funcionamento normal do sistema, é necessário haver um componente centralizado que integre os serviços, ou seja, que agregue os serviços dispersos por toda parte. As informações agregadas podem ser o nome do componente que fornece o serviço, o endereço, a quantidade etc. Cada componente possui um dispositivo de escuta que, quando o estado de algum serviço dentro deste componente muda, reporta ao componente centralizado para atualização do estado. Ao solicitar um serviço, o chamador primeiro obtém do componente centralizado as informações dos componentes que podem fornecer esse serviço (IP, porta etc.) e, por meio de uma estratégia padrão ou personalizada, escolhe um provedor desse serviço para acessar, realizando a chamada do serviço. Então, esse componente centralizado é geralmente chamado de `centro de serviços`. No Hyperf, implementamos o suporte a componentes de centro de serviços baseados em `Consul` e `Nacos`, e futuramente adaptaremos mais centros de serviços.

# Instalação

## Instalar a camada de acesso unificada

```bash
composer require hyperf/service-governance
```

## Escolher e instalar o adaptador correspondente

O registro de serviços suporta `Consul` e `Nacos`; importe o componente adaptador correspondente conforme necessário

- Consul

```shell
composer require hyperf/service-governance-consul
```

- Nacos

```shell
composer require hyperf/service-governance-nacos
```

# Arquivo de configuração

O componente é guiado pelo arquivo de configuração `config/autoload/services.php`, que é o seguinte:

```php
return [
    'enable' => [
        // Habilita a descoberta de serviços
        'discovery' => true,
        // Habilita o registro de serviços
        'register' => true,
    ],
    // Configurações relacionadas ao consumidor de serviços
    'consumers' => [],
    // Configurações relacionadas ao provedor de serviços
    'providers' => [],
    // Configurações relacionadas ao driver de serviços
    'drivers' => [
        'consul' => [
            'uri' => 'http://127.0.0.1:8500',
            'token' => '',
            'check' => [
                'deregister_critical_service_after' => '90m',
                'interval' => '1s',
            ],
        ],
        'nacos' => [
            // nacos server url like https://nacos.hyperf.io, Priority is higher than host:port
            // 'url' => '',
            // The nacos host info
            'host' => '127.0.0.1',
            'port' => 8848,
            // The nacos account info
            'username' => null,
            'password' => null,
            'guzzle' => [
                'config' => null,
            ],
            'group_name' => 'api',
            'namespace_id' => 'namespace_id',
            'heartbeat' => 5,
            'ephemeral' => false, // Se registra uma instância temporária
        ],
    ],
];
```

# Registrar serviço

O registro de serviço pode ser definido em uma classe por meio da anotação `#[RpcService]`, o que significa publicar esse serviço. Atualmente o Hyperf só é compatível com o protocolo JSON RPC; para detalhes, consulte também o capítulo [Serviço JSON RPC](json-rpc.md).

```php
<?php

namespace App\JsonRpc;

use Hyperf\RpcServer\Annotation\RpcService;

#[RpcService(name: "CalculatorService", protocol: "jsonrpc-http", server: "jsonrpc-http")]
class CalculatorService implements CalculatorServiceInterface
{
    // Implementa um método de adição, considerando aqui simplesmente que os parâmetros são do tipo int
    public function calculate(int $a, int $b): int
    {
        // Aqui está a implementação concreta do método de serviço
        return $a + $b;
    }
}
```

`#[RpcService]` tem `4` parâmetros:   
A propriedade `name` define o nome do serviço; defina aqui um nome globalmente único. O Hyperf gerará o ID correspondente com base nessa propriedade e o registrará no centro de serviços;   
A propriedade `protocol` define o protocolo exposto por esse serviço. Atualmente só são suportados `jsonrpc` e `jsonrpc-http`, correspondentes aos dois protocolos sob o protocolo TCP e o protocolo HTTP, respectivamente. O valor padrão é `jsonrpc-http`. O valor aqui corresponde à `key` do protocolo registrado em `Hyperf\Rpc\ProtocolManager`; essencialmente os dois são o protocolo JSON RPC, diferenciando-se em formatação de dados, empacotamento de dados, transportador de dados etc.   
A propriedade `server` define o `Server` que hospedará a publicação dessa classe de serviço. O valor padrão é `jsonrpc-http`. Essa propriedade corresponde ao `name` sob `servers` no arquivo `config/autoload/server.php`, o que significa que precisamos definir um `Server` correspondente;   
A propriedade `publishTo` define o centro de serviços para o qual o serviço será publicado. Atualmente só são suportados `consul`, `nacos` ou vazio; vazio significa não publicar o serviço no centro de serviços, mas também significa que você precisa tratar manualmente a descoberta de serviços. Para usar essa funcionalidade, é necessário instalar o componente [hyperf/service-governance](https://github.com/hyperf/service-governance) e a dependência do driver correspondente;

> Ao usar a anotação `#[RpcService]`, é necessário o namespace `use Hyperf\RpcServer\Annotation\RpcService;`.

## Personalizar adaptador de governança de serviços

Além do suporte padrão a `Consul` e `Nacos`, os usuários também podem registrar adaptadores personalizados conforme suas necessidades.

Podemos criar um FooService que implemente `Hyperf\ServiceGovernance\DriverInterface`

```php
<?php

declare(strict_types=1);

namespace App\ServiceGovernance;

use Hyperf\ServiceGovernance\DriverInterface;

class FooDriver implements DriverInterface
{
    public function getNodes(string $uri, string $name, array $metadata): array
    {
        return [];
    }

    public function register(string $name, string $host, int $port, array $metadata): void
    {
    }

    public function isRegistered(string $name, string $address, int $port, array $metadata): bool
    {
        return true;
    }
}
```

Em seguida, crie um listener e registre-o no `DriverManager`.

```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\ServiceGovernance\Listener;

use App\ServiceGovernance\FooDriver;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\ServiceGovernance\DriverManager;

#[Listener]
class RegisterDriverListener implements ListenerInterface
{
    protected DriverManager $driverManager;

    public function __construct(DriverManager $manager)
    {
        $this->driverManager = $manager;
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        $this->driverManager->register('foo', make(FooDriver::class));
    }
}

```
