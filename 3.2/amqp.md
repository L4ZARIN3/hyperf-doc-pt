# Componente AMQP

[hyperf/amqp](https://github.com/hyperf/amqp) é o componente que implementa o padrão AMQP, voltado principalmente para o uso com o RabbitMQ.

## Instalação

```bash
composer require hyperf/amqp
```

## Configuração padrão

|       Configuração       |  Tipo  |  Valor padrão   |    Observação     |
|:----------------:|:------:|:---------:|:---------:|
|       host       | string | localhost |   Host    |
|       port       |  int   |   5672    |    Número da porta    |
|       user       | string |   guest   |    Nome de usuário    |
|     password     | string |   guest   |    Senha     |
|      vhost       | string |     /     |   vhost   |
| concurrent.limit |  int   |     0     | Quantidade máxima de consumo simultâneo |
|       pool       | object |           |   Configuração do pool de conexões   |
| pool.connections |  int   |     1     | Número de conexões mantidas no processo |
|      params      | object |           |   Configuração básica    |

```php
<?php

return [
    'enable' => true,
    'default' => [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
        'concurrent' => [
            'limit' => 1,
        ],
        'pool' => [
            'connections' => 1,
        ],
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            'connection_timeout' => 3.0,
            // Procure manter como o dobro do valor de heartbeat
            'read_write_timeout' => 6.0,
            'context' => null,
            'keepalive' => false,
            // Procure garantir que o tempo de consumo de cada mensagem seja menor que o tempo de heartbeat
            'heartbeat' => 3,
            'close_on_destruct' => false,
        ],
    ],
    'pool2' => [
        ...
    ]
];
```

É possível definir pools diferentes, como `default` e `pool2` acima, na função `__construct` do `producer` ou do `consumer`.

## Entregar mensagens

Use o comando `gen:producer` para criar um `producer`

```bash
php bin/hyperf.php gen:amqp-producer DemoProducer
```

No arquivo DemoProducer, podemos modificar os campos correspondentes da anotação `#[Producer]` para substituir o `exchange` e o `routingKey` correspondentes.
O `payload` é o dado finalmente entregue à fila de mensagens, portanto podemos reescrever livremente o método `__construct`, desde que ao final o `payload` seja atribuído.
Exemplo abaixo.

> Para usar a anotação `#[Producer]`, é necessário o namespace `use Hyperf\Amqp\Annotation\Producer;`;   

```php
<?php

declare(strict_types=1);

namespace App\Amqp\Producers;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use App\Models\User;

#[Producer(exchange: "hyperf", routingKey: "hyperf")]
class DemoProducer extends ProducerMessage
{
    public function __construct($id)
    {
        // Define um pool diferente
        $this->poolName = 'pool2';

        $user = User::where('id', $id)->first();
        $this->payload = [
            'id' => $id,
            'data' => $user->toArray()
        ];
    }
}

```

Obtenha uma instância de `Hyperf\Amqp\Producer` por meio do DI Container para entregar a mensagem. O exemplo abaixo, que usa diretamente `ApplicationContext` para obter `Hyperf\Amqp\Producer`, não é de fato razoável; para saber como usar o DI Container, consulte o capítulo [Injeção de Dependência](di.md).

```php
<?php
use Hyperf\Amqp\Producer;
use App\Amqp\Producers\DemoProducer;
use Hyperf\Context\ApplicationContext;

$message = new DemoProducer(1);
$producer = ApplicationContext::getContainer()->get(Producer::class);
$result = $producer->produce($message);

```

## Consumir mensagens

Use o comando `gen:amqp-consumer` para criar um `consumer`.

```bash
php bin/hyperf.php gen:amqp-consumer DemoConsumer
```

No arquivo DemoConsumer, podemos modificar os campos correspondentes da anotação `#[Consumer]` para substituir o `exchange`, o `routingKey` e a `queue` correspondentes.
O `$data` é o dado da mensagem após o parsing.
Exemplo abaixo.

> Para usar a anotação `#[Consumer]`, é necessário o namespace `use Hyperf\Amqp\Annotation\Consumer;`;   

```php
<?php

declare(strict_types=1);

namespace App\Amqp\Consumers;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

#[Consumer(exchange: "hyperf", routingKey: "hyperf", queue: "hyperf", nums: 1)]
class DemoConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        print_r($data);
        return Result::ACK;
    }
}
```

### Proibir a inicialização automática do processo de consumo

Por padrão, após usar a anotação `#[Consumer]`, o framework cria automaticamente um subprocesso para iniciar o consumidor e o reinicia caso o subprocesso termine de forma anormal.
Se estiver em fase de desenvolvimento, ao depurar o consumidor, pode ser inconveniente depurar por causa do consumo de outras mensagens.

Nesse caso, basta configurar `enable=false` na anotação `#[Consumer]` (o padrão é `true`, seguindo a inicialização do serviço) ou sobrescrever o método de classe `isEnable()` no consumidor correspondente para retornar `false`

```php
<?php

declare(strict_types=1);

namespace App\Amqp\Consumers;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

#[Consumer(exchange: "hyperf", routingKey: "hyperf", queue: "hyperf", nums: 1, enable: false)]
class DemoConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        print_r($data);
        return Result::ACK;
    }

    public function isEnable(): bool
    {
        return parent::isEnable();
    }
}
```

### Definir o número máximo de consumos

É possível modificar a propriedade `maxConsumption` da anotação `#[Consumer]` para definir o número máximo de mensagens processadas por este consumidor; ao atingir o número de consumos especificado, o processo do consumidor será reiniciado.

### Definir consumo concorrente
 
Há três lugares que influenciam os parâmetros de taxa de consumo

- É possível modificar o `nums` da anotação `#[Consumer]` para iniciar vários consumidores
- A classe base `ConsumerMessage` possui a propriedade `$qos`; é possível controlar a quantidade de mensagens obtidas do servidor a cada vez sobrescrevendo o valor de `prefetch_size` ou `prefetch_count` em `$qos`
- O parâmetro `concurrent.limit` no arquivo de configuração controla a quantidade máxima de corrotinas de consumo


### Resultado do consumo

O framework decide o comportamento de resposta da mensagem de acordo com o resultado retornado pelo método `consume` dentro de `Consumer`. Há 4 resultados de resposta, respectivamente `\Hyperf\Amqp\Result::ACK`, `\Hyperf\Amqp\Result::NACK`, `\Hyperf\Amqp\Result::REQUEUE` e `\Hyperf\Amqp\Result::DROP`, e cada valor de retorno representa o seguinte comportamento:

| Valor de retorno                       | Comportamento                                                                 |
|------------------------------|----------------------------------------------------------------------|
| \Hyperf\Amqp\Result::ACK     | Confirma que a mensagem foi consumida corretamente                                               |
| \Hyperf\Amqp\Result::NACK    | A mensagem não foi consumida corretamente; responde com o método `basic_nack`                     |
| \Hyperf\Amqp\Result::REQUEUE | A mensagem não foi consumida corretamente; responde com o método `basic_reject` e faz a mensagem retornar à fila |
| \Hyperf\Amqp\Result::DROP    | A mensagem não foi consumida corretamente; responde com o método `basic_reject`                   |

### Configuração de QOS


```php
<?php

declare(strict_types=1);

namespace App\Amqp\Consumers;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

#[Consumer(exchange: "hyperf", routingKey: "hyperf", queue: "hyperf", nums: 1)]
class DemoConsumer extends ConsumerMessage
{
    protected ?array $qos = [
        // O AMQP, por padrão, não implementa esta configuração.
        'prefetch_size' => 0,
        // Para o mesmo consumidor, o número máximo de mensagens que podem ser processadas simultaneamente.
        'prefetch_count' => 30,
        // Como o Hyperf, por padrão, faz um Channel consumir apenas uma fila, definir global como true/false tem o mesmo efeito.
        'global' => false,
    ];
    
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        print_r($data);
        return Result::ACK;
    }
}
```

### Personalizar a quantidade de processos de consumo conforme o ambiente

Na anotação `#[Consumer]`, é possível definir a quantidade de processos de consumo por meio da propriedade `nums`. Se for necessário definir quantidades diferentes de processos de consumo conforme o ambiente, isso pode ser feito sobrescrevendo o método `getNums`, conforme o exemplo abaixo:

```php
#[Consumer(
    exchange: 'hyperf',
    routingKey: 'hyperf',
    queue: 'hyperf',
    name: 'hyperf',
    nums: 1
)]
final class DemoConsumer extends ConsumerMessage
{
    public function getNums(): int
    {
        if (is_debug()) {
            return 10;
        }
        return parent::getNums();
    }
}
```

## Fila com atraso

A fila com atraso do AMQP não ordena pelo tempo de atraso; portanto, se você entregar uma tarefa com atraso de 10s e depois entregar na mesma fila uma tarefa com atraso de 5s, a segunda tarefa de 5s só será consumida após a conclusão da primeira tarefa de 10s.
Por isso, é necessário definir filas diferentes conforme o tempo. Se desejar uma fila com atraso mais flexível, você pode tentar usar a fila assíncrona (async-queue) em conjunto com o AMQP.

Além disso, o AMQP exige o download do [plugin de atraso](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases) e sua ativação para funcionar corretamente

```shell
wget https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/3.9.0/rabbitmq_delayed_message_exchange-3.9.0.ez
cp rabbitmq_delayed_message_exchange-3.9.0.ez /opt/rabbitmq/plugins/
rabbitmq-plugins enable rabbitmq_delayed_message_exchange
```

### Produtor

Use o comando `gen:amqp-producer` para criar um `producer`. Aqui usamos o tipo `direct` como exemplo; para outros tipos, como `fanout` e `topic`, basta alterar o `type` no produtor e no consumidor.

```bash
php bin/hyperf.php gen:amqp-producer DelayDirectProducer
```

No arquivo DelayDirectProducer, adicione `use ProducerDelayedMessageTrait;`, conforme o exemplo abaixo:

```php
<?php

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

#[Producer]
class DelayDirectProducer extends ProducerMessage
{
    use ProducerDelayedMessageTrait;

    protected string $exchange = 'ext.hyperf.delay';

    protected Type|string $type = Type::DIRECT;

    protected array|string $routingKey = '';

    public function __construct($data)
    {
        $this->payload = $data;
    }
}
```

### Consumidor

Use o comando `gen:amqp-consumer` para criar um `consumer`.

```bash
php bin/hyperf.php gen:amqp-consumer DelayDirectConsumer
```

No arquivo `DelayDirectConsumer`, adicione a importação `use ProducerDelayedMessageTrait, ConsumerDelayedMessageTrait;`, conforme o exemplo abaixo:

```php
<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerDelayedMessageTrait;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

#[Consumer(nums: 1)]
class DelayDirectConsumer extends ConsumerMessage
{
    use ProducerDelayedMessageTrait;
    use ConsumerDelayedMessageTrait;

    protected string $exchange = 'ext.hyperf.delay';
    
    protected string $queue = 'queue.hyperf.delay';
    
    protected Type|string $type = Type::DIRECT; //Type::FANOUT;
    
    protected array|string $routingKey = '';

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        var_dump($data, 'delay+direct consumeTime:' . (microtime(true)));
        return Result::ACK;
    }
}

```

### Produzir mensagens com atraso

> O exemplo abaixo demonstra o uso em um Command; para o uso concreto, considere a situação real

Use o comando `gen:command DelayCommand` para criar um `DelayCommand`. Como segue:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Amqp\Producer\DelayDirectProducer;
//use App\Amqp\Producer\DelayFanoutProducer;
//use App\Amqp\Producer\DelayTopicProducer;
use Hyperf\Amqp\Producer;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;

#[Command]
class DelayCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('demo:command');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        //1.delayed + direct
        $message = new DelayDirectProducer('delay+direct produceTime:'.(microtime(true)));
        //2.delayed + fanout
        //$message = new DelayFanoutProducer('delay+fanout produceTime:'.(microtime(true)));
        //3.delayed + topic
        //$message = new DelayTopicProducer('delay+topic produceTime:' . (microtime(true)));
        $message->setDelayMs(5000);
        $producer = ApplicationContext::getContainer()->get(Producer::class);
        $producer->produce($message);
    }
}

```

Execute a linha de comando para produzir a mensagem

```
php bin/hyperf.php demo:command
```

## Chamada de procedimento remoto RPC

Além do cenário típico de fila de mensagens, também podemos usar o AMQP para implementar chamadas de procedimento remoto RPC, e este componente também oferece suporte correspondente para essa implementação.

### Criar o consumidor

O consumidor usado para RPC é basicamente igual ao consumidor do cenário típico de fila de mensagens; a única diferença é que é necessário chamar o método `reply` para retornar os dados ao produtor.

```php
<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

#[Consumer(exchange: "hyperf", routingKey: "hyperf", queue: "rpc.reply", name: "ReplyConsumer", nums: 1, enable: true)]
class ReplyConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $data['message'] .= 'Reply:' . $data['message'];

        $this->reply($data, $message);

        return Result::ACK;
    }
}
```

### Iniciar uma chamada RPC

Como produtor, iniciar uma chamada de procedimento remoto RPC também é muito simples: basta obter o objeto `Hyperf\Amqp\RpcClient` por meio do contêiner de injeção de dependência e chamar o método `call` dele. O resultado retornado são os dados de reply do consumidor, conforme mostrado abaixo:

```php
<?php
use Hyperf\Amqp\Message\DynamicRpcMessage;
use Hyperf\Amqp\RpcClient;
use Hyperf\Context\ApplicationContext;

$rpcClient = ApplicationContext::getContainer()->get(RpcClient::class);
// Defina no DynamicRpcMessage o Exchange e o RoutingKey iguais aos do Consumer
$result = $rpcClient->call(new DynamicRpcMessage('hyperf', 'hyperf', ['message' => 'Hello Hyperf'])); 

// $result:
// array(1) {
//     ["message"]=>
//     string(18) "Reply:Hello Hyperf"
// }
```

### Abstrair RpcMessage

O processo de chamada RPC acima define o Exchange e o RoutingKey diretamente por meio da classe `Hyperf\Amqp\Message\DynamicRpcMessage` e transmite os dados da mensagem. No design de projetos de produção, podemos criar uma camada de abstração sobre o RpcMessage para unificar a definição de Exchange e RoutingKey.   

Podemos criar a classe RpcMessage correspondente, como `App\Amqp\FooRpcMessage`, conforme abaixo:

```php
<?php
use Hyperf\Amqp\Message\RpcMessage;

class FooRpcMessage extends RpcMessage
{

    protected string $exchange = 'hyperf';

    protected array|string $routingKey = 'hyperf';
    
    public function __construct($data)
    {
        // Dados a serem transmitidos
        $this->payload = $data;
    }

}
```

Assim, ao fazer uma chamada RPC, basta passar diretamente uma instância de `FooRpcMessage` para o método `call`, sem precisar definir Exchange e RoutingKey a cada chamada.
