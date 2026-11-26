# Kafka

O `Kafka` é uma plataforma de processamento de streams de código aberto desenvolvida pela `Apache Software Foundation`, escrita em `Scala` e `Java`. O objetivo do projeto é fornecer uma plataforma unificada, de alto throughput e baixa latência para o processamento de dados em tempo real. Sua camada de persistência é, essencialmente, uma "fila de mensagens de publicação/assinatura em larga escala baseada em uma arquitetura de log de transações distribuído".

O componente [longlang/phpkafka](https://github.com/swoole/phpkafka) é fornecido pela [Longlang](http://longlang.org/) e suporta `PHP-FPM` e `Swoole`. Agradecemos à `equipe Swoole` e à `equipe ZenTao` pelas contribuições à comunidade.

## Instalação

```bash
composer require hyperf/kafka
```

## Requisitos de versão

- Kafka >= 1.0.0

## Uso

### Configuração

O arquivo de configuração do componente `kafka` fica por padrão em `config/autoload/kafka.php`. Caso esse arquivo não exista, você pode usar o comando `php bin/hyperf.php vendor:publish hyperf/kafka` para publicar o arquivo de configuração correspondente.

O arquivo de configuração padrão é o seguinte:


| Configuração                  | Tipo       | Valor padrão                  | Observação                                                                                                           |
| ----------------------------- | ---------- | ----------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| connect_timeout               | int｜float | -1                            | Tempo limite de conexão (em segundos, aceita decimais); se for -1, não há limite                                    |
| send_timeout                  | int｜float | -1                            | Tempo limite de envio (em segundos, aceita decimais); se for -1, não há limite                                      |
| recv_timeout                  | int｜float | -1                            | Tempo limite de recebimento (em segundos, aceita decimais); se for -1, não há limite                                 |
| client_id                     | stirng     | null                          | Identificador do cliente Kafka                                                                                       |
| max_write_attempts            | int        | 3                             | Número máximo de tentativas de escrita                                                                               |
| bootstrap_servers             | array      | '127.0.0.1:9092'              | Servidor de bootstrap; se esse valor for configurado, conectará automaticamente a esse servidor e atualizará os brokers automaticamente |
| acks                          | int        | 0                             | Valor de confirmações que o produtor exige do líder antes de considerar a requisição concluída. Valores permitidos: 0 significa sem confirmação, 1 significa apenas o líder, -1 significa ISR completo. |
| producer_id                   | int        | -1                            | ID do produtor                                                                                                       |
| producer_epoch                | int        | -1                            | Epoch do produtor                                                                                                    |
| partition_leader_epoch        | int        | -1                            | Epoch do líder da partição                                                                                           |
| interval                      | int｜float | 0                             | Quando nenhuma mensagem é obtida, quantos segundos aguardar antes de tentar novamente; o padrão 0 significa sem atraso (em segundos, aceita decimais) |
| session_timeout               | int｜float | 60                            | Se nenhum sinal de heartbeat for recebido após o timeout, o coordenador considerará o usuário morto. (em segundos, aceita decimais) |
| rebalance_timeout             | int｜float | 60                            | Tempo máximo que o coordenador aguarda cada membro reingressar ao rebalancear o grupo (em segundos, aceita decimais). |
| replica_id                    | int        | -1                            | ID da réplica                                                                                                        |
| rack_id                       | int        | -1                            | Número do rack                                                                                                       |
| group_retry                   | int        | 5                             | Número de novas tentativas automáticas em operações de grupo ao corresponder a códigos de erro predefinidos          |
| group_retry_sleep             | int        | 1                             | Atraso entre novas tentativas de operações de grupo, em segundos                                                      |
| group_heartbeat               | int        | 3                             | Intervalo de heartbeat do grupo, em segundos                                                                         |
| offset_retry                  | int        | 5                             | Número de novas tentativas automáticas em operações de offset ao corresponder a códigos de erro predefinidos         |
| auto_create_topic             | bool       | true                          | Define se o topic deve ser criado automaticamente                                                                     |
| partition_assignment_strategy | string     | KafkaStrategy::RANGE_ASSIGNOR | Estratégia de atribuição de partições do consumidor. Opções: atribuição por faixa (`KafkaStrategy::RANGE_ASSIGNOR`) e atribuição round-robin (`KafkaStrategy::ROUND_ROBIN_ASSIGNOR`) |
| sasl                          | array      | []                            | Informações de autenticação SASL. Se vazio, não envia informações de autenticação. Requer phpkafka >= 1.2             |
| ssl                           | array      | []                            | Informações de conexão SSL. Se vazio, não usa SSL. Requer phpkafka >= 1.2                                            |


```php
<?php

declare(strict_types=1);

use Hyperf\Kafka\Constants\KafkaStrategy;

return [
    'default' => [
        'connect_timeout' => -1,
        'send_timeout' => -1,
        'recv_timeout' => -1,
        'client_id' => '',
        'max_write_attempts' => 3,
        'bootstrap_servers' => '127.0.0.1:9092',
        'acks' => 0,
        'producer_id' => -1,
        'producer_epoch' => -1,
        'partition_leader_epoch' => -1,
        'interval' => 0,
        'session_timeout' => 60,
        'rebalance_timeout' => 60,
        'replica_id' => -1,
        'rack_id' => '',
        'group_retry' => 5,
        'group_retry_sleep' => 1,
        'group_heartbeat' => 3,
        'offset_retry' => 5,
        'auto_create_topic' => true,
        'partition_assignment_strategy' => KafkaStrategy::RANGE_ASSIGNOR,
        'sasl' => [],
        'ssl' => [],
    ],
];
```

### Criar consumidor

Por meio do comando gen:kafka-consumer, é possível gerar rapidamente um consumidor (Consumer) para consumir mensagens.

```bash
php bin/hyperf.php gen:kafka-consumer KafkaConsumer
```

Você também pode usar a anotação `Hyperf\Kafka\Annotation\Consumer` para declarar uma subclasse da classe abstrata `Hyperf/Kafka/AbstractConsumer` e concluir a definição de um `consumidor (Consumer)`. Tanto a anotação `Hyperf\Kafka\Annotation\Consumer` quanto a classe abstrata contêm as seguintes propriedades:

|    Configuração    |        Tipo        | Valor padrão da anotação ou classe abstrata |                 Observação                 |
| :--------: | :----------------: | :----------------: | :----------------------------------: |
|   topic    | string or string[] |         ''         |            topic a ser escutado            |
|  groupId   |       string       |         ''         |           groupId a ser escutado           |
|  memberId  |       string       |         ''         |           memberId a ser escutado           |
| autoCommit |       string       |         ''         |           Define se o commit é automático           |
|    name    |       string       |   KafkaConsumer    |             Nome do consumidor             |
|    nums    |        int         |         1          |            Número de processos do consumidor            |
|    pool    |       string       |      default       | Conexão correspondente ao consumidor, corresponde à key do arquivo de configuração |


```php
<?php

declare(strict_types=1);

namespace App\kafka;

use Hyperf\Kafka\AbstractConsumer;
use Hyperf\Kafka\Annotation\Consumer;
use longlang\phpkafka\Consumer\ConsumeMessage;

#[Consumer(topic: "hyperf", nums: 5, groupId: "hyperf", autoCommit: true)]
class KafkaConsumer extends AbstractConsumer
{
    public function consume(ConsumeMessage $message): string
    {
        var_dump($message->getTopic() . ':' . $message->getKey() . ':' . $message->getValue());
    }
}

```

### Enviar mensagens

Você pode chamar o método `Hyperf\Kafka\Producer::send(string $topic, ?string $value, ?string $key = null, array $headers = [], ?int $partitionIndex = null)` para enviar mensagens ao `kafka`. Abaixo, um exemplo de envio de mensagem em um `Controller`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Kafka\Producer;

#[AutoController]
class IndexController extends AbstractController
{
    public function index(Producer $producer)
    {
        $producer->send('hyperf', 'value', 'key');
    }
}

```

O método `Hyperf\Kafka\Producer::send()` aguarda o ACK; se você não precisa aguardar o ACK, pode usar o método `Hyperf\Kafka\Producer::sendAsync()` para enviar a mensagem.

### Enviar várias mensagens de uma só vez

O método `Hyperf\Kafka\Producer::sendBatch(array $messages)` envia mensagens em lote para o `kafka`. Abaixo, um exemplo de envio de mensagens em um `Controller`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Kafka\Producer;
use longlang\phpkafka\Producer\ProduceMessage;

#[AutoController]
class IndexController extends AbstractController
{
    public function index(Producer $producer)
    {
        $producer->sendBatch([
            new ProduceMessage('hyperf1', 'hyperf1_value', 'hyperf1_key'),
            new ProduceMessage('hyperf2', 'hyperf2_value', 'hyperf2_key'),
            new ProduceMessage('hyperf3', 'hyperf3_value', 'hyperf3_key'),
        ]);
    }
}

```

### Descrição da configuração SASL

| Nome do parâmetro   | Descrição                                                                | Valor padrão |
| -------- | ------------------------------------------------------------------- | ------ |
| type     | Classe correspondente à autorização SASL. PLAIN é `\longlang\phpkafka\Sasl\PlainSasl::class` | ''     |
| username | Conta                                                                | ''     |
| password | Senha                                                                | ''     |

### Descrição da configuração SSL

| Nome do parâmetro          | Descrição                                                                    | Valor padrão  |
| --------------- | ----------------------------------------------------------------------- | ------- |
| open            | Define se a criptografia de transporte SSL está habilitada                                                     | `false` |
| compression     | Define se a compressão está habilitada                                                            | `true`  |
| certFile        | Caminho onde o certificado cert é armazenado                                                        | `''`    |
| keyFile         | Caminho onde a chave privada é armazenada                                                            | `''`    |
| passphrase      | Senha do certificado cert                                                            | `''`    |
| peerName        | Nome do host do servidor. O padrão é o host da conexão                                          | `''`    |
| verifyPeer      | Define se o certificado remoto é validado                                                        | `false` |
| verifyPeerName  | Define se o nome do servidor remoto é validado                                                  | `false` |
| verifyDepth     | Se a cadeia de certificados tiver um nível muito profundo, ultrapassando o valor definido nesta opção, a validação é encerrada. Por padrão, o nível não é validado | `0`     |
| allowSelfSigned | Define se certificados autoassinados são permitidos                                                        | `false` |
| cafile          | Caminho do certificado CA                                                              | `''`    |
| capath          | Diretório do certificado CA. Todos os arquivos pem nesse caminho são verificados automaticamente                               | `''`    |


