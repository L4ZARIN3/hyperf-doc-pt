# Task

Atualmente, o `Swoole` não consegue fazer `hook` de todas as funções bloqueantes, o que significa que algumas funções ainda podem causar `bloqueio do processo`, afetando assim o agendamento de corrotinas. Nesse caso, podemos usar o componente `Task` para simular o processamento de corrotinas, alcançando o objetivo de chamar funções bloqueantes sem bloquear o processo. Em essência, ainda se trata de executar funções bloqueantes em múltiplos processos, portanto o desempenho será claramente inferior ao das corrotinas nativas, dependendo especificamente da quantidade de `Task Worker`.

## Instalação

```bash
composer require hyperf/task
```

## Configuração

Como o Task não é um componente padrão, ao usá-lo é necessário adicionar as configurações relacionadas ao `Task` no `server.php`.

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;

return [
    // Outros itens de configuração não relacionados foram omitidos aqui
    'settings' => [
        // Quantidade de Task Worker; configure uma quantidade adequada de acordo com a configuração do seu servidor
        'task_worker_num' => 8,
        // Como o `Task` lida principalmente com métodos que não podem ser transformados em corrotinas, recomenda-se definir como `false` aqui, evitando confusão de dados em corrotinas
        'task_enable_coroutine' => false,
    ],
    'callbacks' => [
        // Task callbacks
        Event::ON_TASK => [Hyperf\Framework\Bootstrap\TaskCallback::class, 'onTask'],
        Event::ON_FINISH => [Hyperf\Framework\Bootstrap\FinishCallback::class, 'onFinish'],
    ],
];

```

## Uso

O componente Task oferece duas formas de uso: `envio ativo de método` e `envio por anotação`.

### Envio ativo de método

```php
<?php

use Hyperf\Coroutine\Coroutine;
use Hyperf\Context\ApplicationContext;
use Hyperf\Task\TaskExecutor;
use Hyperf\Task\Task;

class MethodTask
{
    public function handle($cid)
    {
        return [
            'worker.cid' => $cid,
            // Quando task_enable_coroutine é false, retorna -1; caso contrário, retorna o ID da corrotina correspondente
            'task.cid' => Coroutine::id(),
        ];
    }
}

$container = ApplicationContext::getContainer();
$exec = $container->get(TaskExecutor::class);
$result = $exec->execute(new Task([MethodTask::class, 'handle'], [Coroutine::id()]));

```

### Usando anotações

O `envio ativo de método` não é muito intuitivo; por isso, implementamos a anotação `#[Task]` correspondente e reescrevemos a chamada do método por meio de `AOP`. Quando está no processo `Worker`, o envio é feito automaticamente para o processo `Task`, e aguarda-se o retorno dos dados de forma corrotina.

```php
<?php

use Hyperf\Coroutine\Coroutine;
use Hyperf\Context\ApplicationContext;
use Hyperf\Task\Annotation\Task;

class AnnotationTask
{
    #[Task]
    public function handle($cid)
    {
        return [
            'worker.cid' => $cid,
            // Quando task_enable_coroutine=false, retorna -1; caso contrário, retorna o ID da corrotina correspondente
            'task.cid' => Coroutine::id(),
        ];
    }
}

$container = ApplicationContext::getContainer();
$task = $container->get(AnnotationTask::class);
$result = $task->handle(Coroutine::id());
```

> Ao usar a anotação `#[Task]`, é necessário `use Hyperf\Task\Annotation\Task;`

A anotação suporta os seguintes parâmetros

|   Configuração   | Tipo  | Valor padrão |                        Observação                        |
| :------: | :---: | :----: | :------------------------------------------------: |
| timeout  |  int  |   10   |                  Tempo limite de execução da tarefa                  |
| workerId |  int  |   -1   | Especifica o ID do processo Task para envio (-1 significa envio aleatório para um processo ocioso) |

## Apêndice

Lista de funções que o Swoole ainda não consegue transformar em corrotinas

- mysql, usa libmysqlclient na camada inferior, não recomendado; recomenda-se usar pdo_mysql/mysqli que já implementam corrotinas
- mongo, usa mongo-c-client na camada inferior
- pdo_pgsql
- pdo_ori
- pdo_odbc
- pdo_firebird

### MongoDB

> Como o `MongoDB` não pode receber `hook`, podemos chamá-lo por meio do `Task`. A seguir, apresentamos brevemente como chamar o `MongoDB` por meio de anotações.

A seguir, implementamos dois métodos, `insert` e `query`. Vale observar que o método `manager` não pode usar `Task`,
porque o `Task` é processado no `Processo Task` correspondente e depois retorna os dados do `Processo Task` para o `Processo Worker`.
Portanto, os parâmetros de entrada e saída do `método Task` é melhor que não carreguem nenhum `IO`, como retornar uma instância de `Redis` etc.

```php
<?php

declare(strict_types=1);

namespace App\Task;

use Hyperf\Task\Annotation\Task;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;

class MongoTask
{
    public Manager $manager;

    #[Task]
    public function insert(string $namespace, array $document)
    {
        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $bulk = new BulkWrite();
        $bulk->insert($document);

        $result = $this->manager()->executeBulkWrite($namespace, $bulk, $writeConcern);
        return $result->getUpsertedCount();
    }

    #[Task]
    public function query(string $namespace, array $filter = [], array $options = [])
    {
        $query = new Query($filter, $options);
        $cursor = $this->manager()->executeQuery($namespace, $query);
        return $cursor->toArray();
    }

    protected function manager()
    {
        if ($this->manager instanceof Manager) {
            return $this->manager;
        }
        $uri = 'mongodb://127.0.0.1:27017';
        return $this->manager = new Manager($uri, []);
    }
}

```

Uso conforme abaixo

```php
<?php
use App\Task\MongoTask;
use Hyperf\Context\ApplicationContext;

$client = ApplicationContext::getContainer()->get(MongoTask::class);
$client->insert('hyperf.test', ['id' => rand(0, 99999999)]);

$result = $client->query('hyperf.test', [], [
    'sort' => ['id' => -1],
    'limit' => 5,
]);
```

## Outras soluções

Se o mecanismo Task não conseguir atender aos requisitos de desempenho, você pode experimentar outro projeto de código aberto da organização Hyperf, o [GoTask](https://github.com/hyperf/gotask). O GoTask usa o recurso de gerenciamento de processos do Swoole para iniciar um processo Go como sidecar do processo principal do Swoole, utilizando comunicação entre processos para enviar tarefas ao sidecar para processamento e receber os valores de retorno. Pode ser entendido como um TaskWorker do Swoole na versão Go.
