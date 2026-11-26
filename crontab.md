# Tarefa agendada

Normalmente, a execução de tarefas agendadas é feita por meio do comando `crontab` do Linux, mas, na realidade, nem todos os desenvolvedores têm acesso a um servidor de produção para configurar tarefas agendadas. O componente [hyperf/crontab](https://github.com/hyperf/crontab) oferece a você uma funcionalidade de tarefa agendada em `nível de segundos`; basta uma definição simples para concluir a definição de uma tarefa agendada. 

# Instalação

```bash
composer require hyperf/crontab
```

# Uso

## Iniciar o processo do agendador de tarefas

Antes de usar o componente de tarefa agendada, é necessário registrar primeiro o processo personalizado `Hyperf\Crontab\Process\CrontabDispatcherProcess` em `config/autoload/processes.php`, da seguinte forma:

```php
<?php
// config/autoload/processes.php
return [
    Hyperf\Crontab\Process\CrontabDispatcherProcess::class,
];
```

Assim, ao iniciar o serviço, será iniciado um processo personalizado responsável pela análise, agendamento e distribuição das tarefas agendadas.   
Além disso, você também precisa definir a configuração `enable` em `config/autoload/crontab.php` como `true`, indicando que a funcionalidade de tarefa agendada está habilitada. Se o arquivo de configuração não existir, crie-o você mesmo, com a seguinte configuração:

```php
<?php
return [
    // Se a tarefa agendada está habilitada
    'enable' => true,
];
```

## Definir tarefas agendadas

### Definir por arquivo de configuração

Você pode configurar todas as suas tarefas agendadas no arquivo de configuração `config/autoload/crontab.php`; o arquivo retorna um array com estrutura `Hyperf\Crontab\Crontab[]`. Se o arquivo de configuração não existir, crie-o você mesmo:

```php
<?php
// config/autoload/crontab.php
use Hyperf\Crontab\Crontab;
return [
    'enable' => true,
    // Tarefa agendada definida por arquivo de configuração
    'crontab' => [
        // Tarefa agendada do tipo Callback (padrão)
        (new Crontab())->setName('Foo')->setRule('* * * * *')->setCallback([App\Task\FooTask::class, 'execute'])->setMemo('Esta é uma tarefa agendada de exemplo'),
        // Tarefa agendada do tipo Command
        (new Crontab())->setType('command')->setName('Bar')->setRule('* * * * *')->setCallback([
            'command' => 'swiftmailer:spool:send',
            // (optional) arguments
            'fooArgument' => 'barValue',
            // (optional) options
            '--message-limit' => 1,
            // Lembre-se de adicionar, caso contrário o processo principal será encerrado
            '--disable-event-dispatcher' => true,
        ])->setEnvironments(['develop', 'production']),
        // Tarefa agendada do tipo Closure (só é suportada em Coroutine style server)
        (new Crontab())->setType('closure')->setName('Closure')->setRule('* * * * *')->setCallback(function () {
            var_dump(date('Y-m-d H:i:s'));
        })->setEnvironments('production'),
    ],
];
```

A partir da versão 3.1 foi adicionada uma nova forma de configuração: você pode definir tarefas agendadas por meio de `config/crontabs.php`. Se o arquivo de configuração não existir, crie-o você mesmo:

```php
<?php
// config/crontabs.php
use Hyperf\Crontab\Schedule;

Schedule::command('foo:bar')->setName('foo-bar')->setRule('* * * * *');
Schedule::call([Foo::class, 'bar'])->setName('foo-bar')->setRule('* * * * *');
Schedule::call(fn() => (new Foo)->bar())->setName('foo-bar')->setRule('* * * * *');
```

### Definir por anotação

Com a anotação `#[Crontab]` é possível concluir rapidamente a definição de uma tarefa. O exemplo de definição abaixo tem o mesmo objetivo que a definição por arquivo de configuração. Define uma tarefa agendada chamada `Foo` que executa `App\Task\FooTask::execute()` uma vez por minuto.

```php
<?php
namespace App\Task;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "Foo", rule: "* * * * *", callback: "execute", memo: "Esta é uma tarefa agendada de exemplo")]
class FooTask
{
    #[Inject]
    private StdoutLoggerInterface $logger;

    public function execute()
    {
        $this->logger->info(date('Y-m-d H:i:s', time()));
    }

    #[Crontab(rule: "* * * * *", memo: "foo")]
    public function foo()
    {
        var_dump('foo');
    }
}
```

### Propriedades da tarefa

#### name

O nome da tarefa agendada, que pode ser qualquer string; os nomes das tarefas agendadas devem ser únicos entre si.

#### rule

A regra de execução da tarefa agendada. Na definição em nível de minutos, ela é igual à regra do comando `crontab` do Linux; na definição em nível de segundos, o comprimento da regra passa de 5 para 6 posições, adicionando-se à frente da regra o nó correspondente ao segundo. Ou seja, com 5 posições a execução é em nível de minutos e com 6 posições é em nível de segundos, por exemplo, `*/5 * * * * *` representa execução a cada 5 segundos. Observe que, ao definir por anotação, se a regra contiver o símbolo `\`, é necessário fazer o escape, ou seja, escrever `*\/5 * * * * *`.

#### callback

O callback de execução da tarefa agendada, ou seja, o código realmente executado pela tarefa agendada. Ao definir por arquivo de configuração, é necessário passar aqui um array `[$class, $method]`, em que `$class` é o nome completo de uma classe e `$method` é um método `public` dentro de `$class`. Ao definir por anotação, basta fornecer o nome do método `public` dentro da classe atual; se a classe atual tiver apenas um método `public`, você pode até omitir essa propriedade.

#### singleton

Resolve o problema de execução concorrente da tarefa: a tarefa será sempre executada apenas 1 vez ao mesmo tempo. Porém, isso não garante o problema de execução repetida da tarefa em cluster.

#### onOneServer

Ao fazer deploy do projeto com múltiplas instâncias, apenas uma instância será acionada.

#### mutexPool

O pool de conexões `Redis` usado pelo mutex.

#### mutexExpires

O tempo de timeout do mutex. Se a tarefa agendada terminar a execução, mas falhar ao liberar o mutex, o mutex também será liberado automaticamente após esse tempo.

#### memo

A observação da tarefa agendada. Essa propriedade é opcional e não tem nenhum significado lógico; serve apenas para o desenvolvedor consultar e ajudar a entender a tarefa agendada.

#### enable

Se a tarefa atual está em vigor.

> Além do tipo bool, também são suportados string e array

Se `enable` for `string`, será chamado o método correspondente da classe atual para determinar se esta tarefa agendada deve ser executada

```php
<?php

namespace App\Crontab;

use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(name: "Echo", rule: "* * * * *", callback: "execute", enable: "isEnable", memo: "Esta é uma tarefa agendada de exemplo")]
class EchoCrontab
{
    public function execute()
    {
        var_dump(Carbon::now()->toDateTimeString());
    }

    public function isEnable(): bool
    {
        return true;
    }
}
```

Se `enable` for `array`, será chamado `array[1]` correspondente a `array[0]` para determinar se esta tarefa agendada deve ser executada

```php
<?php

namespace App\Crontab;

class EnableChecker
{
    public function isEnable(): bool
    {
        return false;
    }
}
```

```php
<?php

namespace App\Crontab;

use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(name: "Echo", rule: "* * * * *", callback: "execute", enable: [EnableChecker::class, "isEnable"], memo: "Esta é uma tarefa agendada de exemplo")]
class EchoCrontab
{
    public function execute()
    {
        var_dump(Carbon::now()->toDateTimeString());
    }

    public function isEnable(): bool
    {
        return true;
    }
}

```

#### environments

Define o ambiente da tarefa agendada; se não for definido, valerá para todos os ambientes. Suporta receber array e string.

### Estratégia de agendamento e distribuição

No design, a tarefa agendada permite usar estratégias diferentes para agendar, distribuir e executar as tarefas. Atualmente são fornecidas apenas duas estratégias: `estratégia de execução multiprocesso` e `estratégia de execução por corrotina`, sendo o padrão a `estratégia de execução multiprocesso`. Iterações futuras adicionarão mais estratégias e mais poderosas.

> Ao usar serviços no estilo corrotina, use a estratégia de execução por corrotina.

#### Alterar a estratégia de agendamento e distribuição

Altere a instância correspondente à interface `Hyperf\Crontab\Strategy\StrategyInterface` em `config/autoload/dependencies.php` para mudar a estratégia atualmente usada. Por padrão é usada a `estratégia de execução por processo Worker`, cuja classe correspondente é `Hyperf\Crontab\Strategy\WorkerStrategy`. Se quisermos mudar a estratégia para uma nova, como `App\Crontab\Strategy\FooStrategy`, faça assim:

```php
<?php
return [
    \Hyperf\Crontab\Strategy\StrategyInterface::class => \App\Crontab\Strategy\FooStrategy::class,
];
```

##### Estratégia de execução por processo Worker [padrão]

Classe da estratégia: `Hyperf\Crontab\Strategy\WorkerStrategy`   

Por padrão usa-se esta estratégia, na qual o processo `CrontabDispatcherProcess` analisa as tarefas agendadas e, por meio de comunicação entre processos em modo round-robin, transmite as tarefas de execução para os diversos processos `Worker`, e cada processo `Worker` executa de fato as tarefas por meio de corrotinas.

##### Estratégia de execução por processo TaskWorker

Classe da estratégia: `Hyperf\Crontab\Strategy\TaskWorkerStrategy`   

Esta estratégia faz com que o processo `CrontabDispatcherProcess` analise as tarefas agendadas e, por meio de comunicação entre processos em modo round-robin, transmita as tarefas de execução para os diversos processos `TaskWorker`, e cada processo `TaskWorker` execute de fato as tarefas por meio de corrotinas. Ao usar esta estratégia, atenção se os processos `TaskWorker` estão configurados para suportar corrotinas.

##### Estratégia de execução multiprocesso

Classe da estratégia: `Hyperf\Crontab\Strategy\ProcessStrategy`   

Esta estratégia faz com que o processo `CrontabDispatcherProcess` analise as tarefas agendadas e, por meio de comunicação entre processos em modo round-robin, transmita as tarefas de execução para os diversos processos `Worker` e `TaskWorker`, e cada processo execute de fato as tarefas por meio de corrotinas. Ao usar esta estratégia, atenção se os processos `TaskWorker` estão configurados para suportar corrotinas.

##### Estratégia de execução por corrotina

Classe da estratégia: `Hyperf\Crontab\Strategy\CoroutineStrategy`   

Esta estratégia faz com que o processo `CrontabDispatcherProcess` analise as tarefas agendadas e crie, dentro do processo, uma corrotina para executar cada tarefa.

## Executar tarefas agendadas

Depois de concluir a configuração acima e definir as tarefas agendadas, basta iniciar diretamente o `Server` para que as tarefas agendadas também sejam iniciadas.   
Após iniciar, mesmo que você tenha definido tarefas agendadas com ciclo suficientemente curto, elas não começarão a ser executadas imediatamente: todas as tarefas agendadas só começarão a ser executadas no próximo ciclo de minuto. Por exemplo, se você iniciar às `10 horas, 11 minutos e 12 segundos`, as tarefas agendadas só começarão a ser executadas oficialmente às `10 horas, 12 minutos e 00 segundos`.

### Evento FailToExecute

Quando a execução de uma tarefa agendada falha, o evento `FailToExecute` é acionado. Assim, podemos escrever o listener abaixo para obter o `Crontab` e o `Throwable` correspondentes.

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Crontab\Event\FailToExecute;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class FailToExecuteCrontabListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            FailToExecute::class,
        ];
    }

    /**
     * @param FailToExecute $event
     */
    public function process(object $event)
    {
        var_dump($event->crontab->getName());
        var_dump($event->throwable->getMessage());
    }
}
```
