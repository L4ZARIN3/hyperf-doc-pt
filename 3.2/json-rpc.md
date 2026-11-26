# Serviço JSON RPC

JSON RPC é um padrão de protocolo RPC leve baseado no formato JSON, fácil de usar e ler. No Hyperf, ele é implementado pelo componente [hyperf/json-rpc](https://github.com/hyperf/json-rpc), permitindo personalizar o transporte sobre o protocolo HTTP ou diretamente sobre o protocolo TCP.

## Instalação

```bash
composer require hyperf/json-rpc
```

Este componente é apenas o componente de tratamento do protocolo JSON RPC; normalmente, você ainda precisa combiná-lo com [hyperf/rpc-server](https://github.com/hyperf/rpc-server) ou [hyperf/rpc-client](https://github.com/hyperf/rpc-client) para atender aos cenários de servidor e cliente. Se usar ambos, será necessário instalar os dois:   

Para usar o servidor JSON RPC:

```bash
composer require hyperf/rpc-server
```

Para usar o cliente JSON RPC:

```bash
composer require hyperf/rpc-client
```

## Uso

Um serviço tem dois papéis: um é o `Provedor de Serviço (ServiceProvider)`, ou seja, o serviço que fornece serviços a outros serviços; o outro é o `Consumidor de Serviço (ServiceConsumer)`, ou seja, o serviço que depende de outros serviços. Um serviço pode ser, ao mesmo tempo, `Provedor de Serviço (ServiceProvider)` e `Consumidor de Serviço (ServiceConsumer)`. Os dois podem definir e restringir a chamada de interfaces diretamente por meio de um `contrato de serviço`; no Hyperf, isso pode ser entendido diretamente como uma `classe de interface (Interface)`, e normalmente essa classe de interface aparece tanto no provedor quanto no consumidor.

### Definir o provedor de serviço

Atualmente, apenas a forma de anotação é suportada para definir o `Provedor de Serviço (ServiceProvider)`; iterações futuras adicionarão a forma de configuração.   
Podemos publicar este serviço definindo diretamente uma classe com a anotação `#[RpcService]`:

```php
<?php

namespace App\JsonRpc;

use Hyperf\RpcServer\Annotation\RpcService;

/**
 * Atenção: se desejar gerenciar o serviço por meio do centro de registro, é necessário adicionar a propriedade publishTo na anotação
 */
#[RpcService(name: "CalculatorService", protocol: "jsonrpc-http", server: "jsonrpc-http")]
class CalculatorService implements CalculatorServiceInterface
{
    // Implementa um método de adição; aqui consideramos simplesmente que os parâmetros são do tipo int
    public function add(int $a, int $b): int
    {
        // Aqui está a implementação concreta do método do serviço
        return $a + $b;
    }
}
```

`#[RpcService]` possui `4` parâmetros:   
A propriedade `name` define o nome do serviço; basta definir aqui um nome globalmente exclusivo, e o Hyperf gerará o ID correspondente com base nessa propriedade para registrar no centro de registro;   
A propriedade `protocol` define o protocolo exposto pelo serviço; atualmente são suportados apenas `jsonrpc-http`, `jsonrpc` e `jsonrpc-tcp-length-check`, correspondendo respectivamente aos dois protocolos sob HTTP e TCP. O valor padrão é `jsonrpc-http`; o valor aqui corresponde à `key` do protocolo registrado em `Hyperf\Rpc\ProtocolManager`. Essencialmente, todos são o protocolo JSON RPC, e a diferença está na formatação de dados, empacotamento de dados, transportador de dados, etc.   
A propriedade `server` define o `Server` que hospedará a publicação dessa classe de serviço; o valor padrão é `jsonrpc-http`, e essa propriedade corresponde ao `name` sob `servers` no arquivo `config/autoload/server.php`, o que significa que precisamos definir um `Server` correspondente;   
A propriedade `publishTo` define o centro de registro para o qual o serviço será publicado; atualmente são suportados apenas `consul`, `nacos` ou vazio. Quando vazio, significa que o serviço não será publicado no centro de registro, mas isso também significa que você precisará lidar manualmente com a descoberta de serviços. Para usar esse recurso, é necessário instalar o componente [hyperf/service-governance](https://github.com/hyperf/service-governance) e a dependência do driver correspondente; consulte o capítulo [Registro de Serviço](service-register.md);

> Para usar a anotação `#[RpcService]`, é necessário o namespace `use Hyperf\RpcServer\Annotation\RpcService;`.

#### Definir o JSON RPC Server

HTTP Server (compatível com o protocolo `jsonrpc-http`)

```php
<?php

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Outras configurações deste arquivo foram omitidas aqui
    'servers' => [
        [
            'name' => 'jsonrpc-http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9504,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [\Hyperf\JsonRpc\HttpServer::class, 'onRequest'],
            ],
        ],
    ],
];
```

TCP Server (compatível com o protocolo `jsonrpc`)

```php
<?php

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Outras configurações deste arquivo foram omitidas aqui
    'servers' => [
        [
            'name' => 'jsonrpc',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_RECEIVE => [\Hyperf\JsonRpc\TcpServer::class, 'onReceive'],
            ],
            'settings' => [
                'open_eof_split' => true,
                'package_eof' => "\r\n",
                'package_max_length' => 1024 * 1024 * 2,
            ],
        ],
    ],
];
```

TCP Server (compatível com o protocolo `jsonrpc-tcp-length-check`)

O protocolo atual é uma extensão do protocolo `jsonrpc`; o usuário pode modificar facilmente o `settings` correspondente para usar este protocolo, conforme o exemplo abaixo.

```php
<?php

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Outras configurações deste arquivo foram omitidas aqui
    'servers' => [
        [
            'name' => 'jsonrpc',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_RECEIVE => [\Hyperf\JsonRpc\TcpServer::class, 'onReceive'],
            ],
            'settings' => [
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
                'package_max_length' => 1024 * 1024 * 2,
            ],
        ],
    ],
];
```

### Publicar no centro de registro

Atualmente, apenas a publicação de serviços em `consul` e `nacos` é suportada; outros centros de registro serão adicionados futuramente.   
Publicar um serviço no `consul` no Hyperf também é uma tarefa muito simples: referencie o componente por meio de `composer require hyperf/service-governance-consul` (se já estiver instalado, ignore esta etapa) e depois configure `drivers.consul` no arquivo de configuração `config/autoload/services.php`.
Publicar um serviço no `nacos` é semelhante: referencie o componente por meio de `composer require hyperf/service-governance-nacos` (se já estiver instalado, ignore esta etapa) e depois configure `drivers.nacos` no arquivo de configuração `config/autoload/services.php`, conforme o exemplo abaixo:

```php
<?php
return [
    'enable' => [
        'discovery' => true,
        'register' => true,
    ],
    'consumers' => [],
    'providers' => [],
    'drivers' => [
        'consul' => [
            'uri' => 'http://127.0.0.1:8500',
            'token' => '',
        ],
        'nacos' => [
            // url do servidor nacos como https://nacos.hyperf.io, prioridade maior que host:port
            // 'url' => '',
            // Informações do host do nacos
            'host' => '127.0.0.1',
            'port' => 8848,
            // Informações da conta do nacos
            'username' => null,
            'password' => null,
            'guzzle' => [
                'config' => null,
            ],
            'group_name' => 'api',
            'namespace_id' => 'namespace_id',
            'heartbeat' => 5,
        ],
    ],
];
```

Após concluir a configuração, ao iniciar o serviço, o Hyperf registrará automaticamente no centro de registro correspondente os serviços cuja anotação `#[RpcService]` tenha definido a propriedade `publishTo` como `consul` ou `nacos`.

> Atualmente, apenas os protocolos `jsonrpc` e `jsonrpc-http` são suportados para publicação no centro de registro; os demais protocolos ainda não implementam o registro de serviço

### Definir o consumidor de serviço

Um `Consumidor de Serviço (ServiceConsumer)` pode ser entendido como uma classe de cliente, mas no Hyperf você não precisa lidar com conexões e requisições; basta fazer algumas configurações de identificação.

#### Criar automaticamente a classe de consumidor proxy

Você pode fazer algumas configurações simples no arquivo de configuração `config/autoload/services.php` para criar automaticamente a classe de consumidor por meio de proxy dinâmico.

```php
<?php
return [
    // Outras configurações do mesmo nível foram omitidas aqui
    'consumers' => [
        [
            // name deve ser igual à propriedade name do provedor de serviço
            'name' => 'CalculatorService',
            // Nome da interface do serviço, opcional; o valor padrão é igual ao valor da configuração name. Se name for definido diretamente como a classe de interface, esta linha pode ser ignorada; se name for uma string, é necessário configurar service para corresponder à classe de interface
            'service' => \App\JsonRpc\CalculatorServiceInterface::class,
            // Corresponde ao ID do objeto no contêiner, opcional; o valor padrão é igual ao valor da configuração service, usado para definir a key da injeção de dependência
            'id' => \App\JsonRpc\CalculatorServiceInterface::class,
            // Protocolo de serviço do provedor de serviço, opcional; o valor padrão é jsonrpc-http
            // Opções: jsonrpc-http jsonrpc jsonrpc-tcp-length-check
            'protocol' => 'jsonrpc-http',
            // Algoritmo de balanceamento de carga, opcional; o valor padrão é random
            'load_balancer' => 'random',
            // De qual centro de registro este consumidor obterá as informações dos nós; se não configurado, não obterá informações dos nós do centro de registro
            'registry' => [
                'protocol' => 'consul',
                'address' => 'http://127.0.0.1:8500',
            ],
            // Se a configuração registry acima não for especificada, o consumo será diretamente dos nós indicados, configurando as informações dos nós do provedor de serviço por meio do parâmetro nodes abaixo
            'nodes' => [
                ['host' => '127.0.0.1', 'port' => 9504],
            ],
            // Itens de configuração que afetarão o Packer e o Transporter
            'options' => [
                'connect_timeout' => 5.0,
                'recv_timeout' => 5.0,
                'settings' => [
                    // Diferencie as configurações conforme o protocolo
                    'open_eof_split' => true,
                    'package_eof' => "\r\n",
                    // 'open_length_check' => true,
                    // 'package_length_type' => 'N',
                    // 'package_length_offset' => 0,
                    // 'package_body_offset' => 4,
                ],
                // Número de novas tentativas, valor padrão 2; não há nova tentativa em caso de timeout no recebimento do pacote. Suportado apenas pelo JsonRpcPoolTransporter por enquanto
                'retry_count' => 2,
                // Intervalo entre novas tentativas, em milissegundos
                'retry_interval' => 100,
                // Intervalo de heartbeat ao usar RPC multiplexado; null significa não disparar heartbeat
                'heartbeat' => 30,
                // As configurações abaixo são usadas quando JsonRpcPoolTransporter é utilizado
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 32,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => 60.0,
                ],
            ],
        ]
    ],
];
```

Ao iniciar a aplicação, o objeto proxy da classe de cliente será criado automaticamente e a relação de binding será adicionada ao contêiner usando o valor do item de configuração `id` (se não definido, será usado o valor do item de configuração `service`). Assim, como na classe de cliente escrita manualmente, o cliente pode ser usado diretamente injetando a interface `CalculatorServiceInterface`.

> Quando o provedor de serviço publica o nome do serviço usando o nome da classe de interface, no lado do consumidor de serviço basta definir o item de configuração `name` com o nome da classe de interface, sem precisar definir repetidamente os itens de configuração `id` e `service`.

#### Criar manualmente a classe de consumidor

Se você tiver mais necessidades em relação à classe de consumidor, poderá criá-la manualmente; basta definir uma classe e as propriedades relacionadas.

```php
<?php

namespace App\JsonRpc;

use Hyperf\RpcClient\AbstractServiceClient;

class CalculatorServiceConsumer extends AbstractServiceClient implements CalculatorServiceInterface
{
    /**
     * Define o nome do serviço do provedor de serviço correspondente
     */
    protected string $serviceName = 'CalculatorService';
    
    /**
     * Define o protocolo de serviço do provedor de serviço correspondente
     */
    protected string $protocol = 'jsonrpc-http';

    public function add(int $a, int $b): int
    {
        return $this->__request(__FUNCTION__, compact('a', 'b'));
    }
}
```

Depois, também é necessário definir no arquivo de configuração uma configuração que marque de qual centro de registro obter as informações dos nós, localizada em `config/autoload/services.php` (crie o arquivo caso não exista)

```php
<?php
return [
    // Outras configurações do mesmo nível foram omitidas aqui
    'consumers' => [
        [
            // Corresponde ao $serviceName da classe de consumidor
            'name' => 'CalculatorService',
            // De qual centro de registro este consumidor obterá as informações dos nós; se não configurado, não obterá informações dos nós do centro de registro
            'registry' => [
                'protocol' => 'consul',
                'address' => 'http://127.0.0.1:8500',
            ],
            // Se a configuração registry acima não for especificada, o consumo será diretamente dos nós indicados, configurando as informações dos nós do provedor de serviço por meio do parâmetro nodes abaixo
            'nodes' => [
                ['host' => '127.0.0.1', 'port' => 9504],
            ],
        ]
    ],
];
```


Assim, podemos consumir o serviço por meio da classe `CalculatorService`. Para tornar a lógica de relacionamento aqui mais razoável, também devemos definir em `config/autoload/dependencies.php` a relação entre `CalculatorServiceInterface` e `CalculatorServiceConsumer`, conforme o exemplo abaixo:

```php
return [
    App\JsonRpc\CalculatorServiceInterface::class => App\JsonRpc\CalculatorServiceConsumer::class,
];
```

Assim, o cliente pode ser usado injetando a interface `CalculatorServiceInterface`.

#### Reutilização de configuração

Normalmente, um consumidor de serviço consome vários provedores de serviço ao mesmo tempo. Quando descobrimos provedores de serviço por meio do centro de registro, o arquivo de configuração `config/autoload/services.php` pode conter muitas configurações `registry` repetidas. Porém, normalmente nosso centro de registro pode ser unificado, o que significa que vários consumidores de serviço obtêm as informações dos nós do mesmo centro de registro. Nesse caso, podemos gerar o arquivo de configuração por meio de `variáveis PHP` ou `laços` e outros códigos PHP.

##### Gerar configuração por meio de variáveis PHP

```php
<?php
$registry = [
   'protocol' => 'consul',
   'address' => 'http://127.0.0.1:8500',
];
return [
    // Os FooService e BarService abaixo servem apenas para exemplificar múltiplos serviços e não existem de fato nos exemplos da documentação
    'consumers' => [
        [
            'name' => 'FooService',
            'registry' => $registry,
        ],
        [
            'name' => 'BarService',
            'registry' => $registry,
        ]
    ],
];
```

##### Gerar configuração por meio de laços

```php
<?php
return [
    // Outras configurações do mesmo nível foram omitidas aqui
    'consumers' => value(function () {
        $consumers = [];
        // Aqui exemplificamos a forma de configuração que cria automaticamente a classe de consumidor proxy; por isso existem os itens de configuração name e service. Esta abordagem não é a única, apenas demonstra que é possível gerar configuração por meio de código PHP
        // As FooServiceInterface e BarServiceInterface abaixo servem apenas para exemplificar múltiplos serviços e não existem de fato nos exemplos da documentação
        $services = [
            'FooService' => App\JsonRpc\FooServiceInterface::class,
            'BarService' => App\JsonRpc\BarServiceInterface::class,
        ];
        foreach ($services as $name => $interface) {
            $consumers[] = [
                'name' => $name,
                'service' => $interface,
                'registry' => [
                   'protocol' => 'consul',
                   'address' => 'http://127.0.0.1:8500',
                ]
            ];
        }
        return $consumers;
    }),
];
```

### Retornar objetos PHP

Quando o framework importa `symfony/serializer (^5.0)` e `symfony/property-access (^5.0)`, e configura a relação de mapeamento em `dependencies.php`

```php
use Hyperf\Serializer\SerializerFactory;
use Hyperf\Serializer\Serializer;

return [
    Hyperf\Contract\NormalizerInterface::class => new SerializerFactory(Serializer::class),
];
```

`NormalizerInterface` passará a suportar a serialização e desserialização de objetos. No momento, não há suporte para arrays de objetos como `MathValue[]`.

Definir o objeto de retorno

```php
<?php

declare(strict_types=1);

namespace App\JsonRpc;

class MathValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
```

Reescrever o arquivo de interface

```php
<?php

declare(strict_types=1);

namespace App\JsonRpc;

interface CalculatorServiceInterface
{
    public function sum(MathValue $v1, MathValue $v2): MathValue;
}
```

Chamar no controlador

```php
<?php

use Hyperf\Context\ApplicationContext;
use App\JsonRpc\CalculatorServiceInterface;
use App\JsonRpc\MathValue;

$client = ApplicationContext::getContainer()->get(CalculatorServiceInterface::class);

/** @var MathValue $result */
$result = $client->sum(new MathValue(1), new MathValue(2));

var_dump($result->value);
```

### Usar JsonRpcPoolTransporter

O framework fornece um `Transporter` baseado em pool de conexões, que pode evitar efetivamente o problema de estabelecer conexões demais em cenários de alta concorrência. Aqui, é possível usar `JsonRpcPoolTransporter` substituindo `JsonRpcTransporter`.

Modifique o arquivo `dependencies.php`

```php
<?php

declare(strict_types=1);

use Hyperf\JsonRpc\JsonRpcPoolTransporter;
use Hyperf\JsonRpc\JsonRpcTransporter;

return [
    JsonRpcTransporter::class => JsonRpcPoolTransporter::class,
];

```
