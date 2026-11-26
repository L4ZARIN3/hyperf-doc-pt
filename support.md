# Classes auxiliares

O Hyperf oferece uma grande quantidade de classes auxiliares práticas. Aqui listaremos algumas das mais usadas e úteis, sem esgotar todas; consulte o código do componente [hyperf/support](https://github.com/hyperf/support) para obter mais informações.

## Classe auxiliar de corrotina

### Hyperf\Coroutine\Coroutine

Essa classe auxiliar serve para auxiliar em verificações ou operações relacionadas a corrotinas.

#### id(): int

Pelo método estático `id()` obtém-se o `ID da corrotina` atual; se não estiver em ambiente de corrotina, retorna `-1`. 

#### create(callable $callable): int

Pelo método estático `create(callable $callable)` é possível criar uma corrotina, e também é possível atingir o mesmo objetivo pelas funções globais `co(callable $callable)` ou `go(callable $callable)`. Esse método é um encapsulamento do método de criação de corrotina do `Swoole`; a diferença é que ele não lança exceções não capturadas — exceções não capturadas são emitidas via `Hyperf\Contract\StdoutLoggerInterface` no nível `warning`.

#### inCoroutine(): bool

Pelo método estático `inCoroutine()` verifica-se se está atualmente em ambiente de corrotina.

### Hyperf\Context\Context

Usado para tratar o contexto da corrotina; essencialmente é um encapsulamento do método `Swoole\Coroutine::getContext()`, mas com a diferença de que aqui há compatibilidade com execução em ambiente sem corrotina.

### Hyperf\Coordinator\CoordinatorManager

Essa classe auxiliar serve para orientar a corrotina a aguardar a ocorrência de eventos.

```php
<?php
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Coordinator\Constants;
use Hyperf\Coroutine\Coroutine;

Coroutine::create(function() {
    // Acorda após a conclusão de todos os callbacks do evento OnWorkerStart
    CoordinatorManager::until(Constants::WORKER_START)->yield();
    echo 'worker started';
    // Aloca recursos
    // Acorda após a conclusão de todos os callbacks do evento OnWorkerExit
    CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
    echo 'worker exited';
    // Recicla recursos
});
```
