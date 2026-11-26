# Processo personalizado

O [hyperf/process](https://github.com/hyperf/process) permite adicionar um processo de trabalho personalizado pelo usuário. Essa função geralmente é usada para criar um processo de trabalho especial para monitoramento, reporte ou outras tarefas especiais. Ao iniciar o Server, o processo é criado automaticamente e a função de subprocesso especificada é executada; se o processo for encerrado inesperadamente, o Server o reinicia.

## Criando um processo personalizado

Em qualquer lugar, implemente uma subclasse que herde de `Hyperf\Process\AbstractProcess` e implemente o método da interface `handle(): void`, dentro do qual você coloca seu código de lógica. Vamos exemplificar com código:

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;

class FooProcess extends AbstractProcess
{
    public function handle(): void
    {
        // Seu código ...
    }
}
```

Com isso, a classe de processo personalizado está concluída, mas ela ainda não foi registrada no `Gerenciador de processos (ProcessManager)`. Podemos concluir o registro por meio de `arquivo de configuração` ou `anotação`, usando qualquer um dos dois modos.

### Registrando por arquivo de configuração

Basta adicionar sua classe de processo personalizado em `config/autoload/processes.php`:

```php
// config/autoload/processes.php
return [
    \App\Process\FooProcess::class,
];
```

### Registrando por anotação

Basta definir a anotação `#[Process]` na classe de processo personalizado, e o Hyperf fará a coleta e concluirá o registro automaticamente:

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: "foo_process")]
class FooProcess extends AbstractProcess
{
    public function handle(): void
    {
        // Seu código ...
    }
}
```

> Ao usar a anotação `#[Process]`, é necessário `use Hyperf\Process\Annotation\Process;` namespace;   

## Adicionando condições para iniciar o processo

Às vezes, não é em todas as situações que um processo personalizado deve ser iniciado. O início ou não de um processo personalizado pode depender de algumas configurações ou condições. Podemos implementar isso sobrescrevendo o método `isEnable(): bool` na classe de processo personalizado. Por padrão, ele retorna `true`, ou seja, o processo é iniciado junto com o serviço; se o método retornar `false`, o processo personalizado não será iniciado quando o serviço iniciar.

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: "foo_process")]
class FooProcess extends AbstractProcess
{
    public function handle(): void
    {
        // Seu código ...
    }
    
    public function isEnable($server): bool
    {
        // Não inicia junto com o serviço
        return false;   
    }
}
```

## Configurando o processo personalizado

O processo personalizado possui alguns parâmetros configuráveis, todos podendo ser definidos de duas formas: sobrescrevendo na subclasse a propriedade correspondente ao parâmetro, ou definindo a propriedade correspondente na anotação `#[Process]`.

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: "user-process", redirectStdinStdout: false, pipeType: 2, enableCoroutine: true)]
class FooProcess extends AbstractProcess
{
    /**
     * Quantidade de processos
     */
    public int $nums = 1;

    /**
     * Nome do processo
     */
    public string $name = 'user-process';

    /**
     * Redireciona a entrada e a saída padrão do processo personalizado
     */
    public bool $redirectStdinStdout = false;

    /**
     * Tipo de pipe
     */
    public int $pipeType = 2;

    /**
     * Se habilita corrotinas
     */
    public bool $enableCoroutine = true;
}
```

## Exemplo de uso

Vamos criar um subprocesso para monitorar a quantidade de filas com falha e emitir um aviso quando houver dados na fila de falhas.

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Contract\StdoutLoggerInterface;

#[Process(name: "demo_process")]
class DemoProcess extends AbstractProcess
{
    public function handle(): void
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);

        while (true) {
            $redis = $this->container->get(\Redis::class);
            $count = $redis->llen('queue:failed');

            if ($count > 0) {
                $logger->warning('The num of failed queue is ' . $count);
            }

            sleep(1);
        }
    }
}
```

Se você usar IO assíncrono e não conseguir escrever a lógica dentro de um loop, pode tentar a forma abaixo

```php
<?php
declare(strict_types=1);

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Swoole\Timer;

#[Process(name: "demo_process")]
class DemoProcess extends AbstractProcess
{
    public function handle(): void
    {
        Timer::tick(1000, function(){
            var_dump(1);
            // Do something...
        });

        while (true) {
            sleep(1);
        }
    }
}
```
