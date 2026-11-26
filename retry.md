# Retry

A comunicação de rede é naturalmente instável, portanto, em sistemas distribuídos, é necessário um bom design de tolerância a falhas. Repetir tentativas sem critério é muito perigoso. Quando há problemas de comunicação, repetir cada requisição uma vez equivale a aumentar a carga de IO do sistema em 100%, o que facilita a ocorrência de acidentes em cascata. O retry também deve considerar a causa do erro: se for um problema que não pode ser resolvido por meio de novas tentativas, então o retry apenas desperdiça recursos. Além disso, se a interface repetida não for idempotente, pode causar inconsistência de dados e outros problemas.

Este componente fornece mecanismos de retry variados, capazes de atender às necessidades de retry de diversos cenários.


## Instalação

```bash
composer require hyperf/retry
```

## Hello World

Adicione a anotação `#[Retry]` no método que precisa de retry.

```php
/**
 * Repetir este método em caso de exceção
 */
#[Retry]
public function foo()
{
    // Faz uma chamada remota
}
```

A política padrão de Retry atende à maioria das necessidades diárias de retry e não repete tentativas em excesso, evitando causar um efeito cascata.

## Personalização avançada

Este componente alcança a capacidade de plug-and-play combinando várias políticas de retry. Cada política se concentra em um aspecto diferente do processo de retry, como a decisão de repetir, o intervalo entre tentativas, o tratamento do resultado, etc. Ajustando as políticas usadas na anotação, é possível configurar uma faceta de retry adequada a qualquer cenário.

Recomenda-se construir seu próprio alias de anotação de acordo com a necessidade específica do negócio. A seguir demonstramos como criar uma nova anotação com número máximo de tentativas igual a 3.

> Na anotação `Retry` padrão, você pode controlar o número máximo de tentativas com `#[Retry(maxAttempts=3)]`. Para fins de demonstração, finja que isso não existe.

Primeiro, crie uma `classe de anotação` e herde de `\Hyperf\Retry\Annotations\AbstractRetry` .

```php
<?php

declare(strict_types=1);

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

#[Attribute(Attribute::TARGET_METHOD)]
class MyRetry extends \Hyperf\Retry\Annotation\AbstractRetry
{
}
```

Conforme sua necessidade, sobrescreva a propriedade `$policies`. Para limitar o número de tentativas, é necessário usar `MaxAttemptsRetryPolicy`. `MaxAttemptsRetryPolicy` também precisa de um parâmetro, que é o limite máximo de tentativas, `$maxAttempts`. Adicione essas duas propriedades à classe acima.

```php
<?php

declare(strict_types=1);

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

#[Attribute(Attribute::TARGET_METHOD)]
class MyRetry extends \Hyperf\Retry\Annotation\AbstractRetry
{
    public $policies = [
        MaxAttemptsRetryPolicy::class,
    ];
    public $maxAttempts = 3;
}
```

Agora, a anotação `#[MyRetry]` fará com que qualquer método seja executado em loop três vezes. Precisamos também adicionar uma nova política `ClassifierRetryPolicy` para controlar que tipo de erro pode ser repetido. Após adicionar `ClassifierRetryPolicy`, por padrão só haverá retry quando for lançado um `Throwable`.

```php
<?php

declare(strict_types=1);

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

#[Attribute(Attribute::TARGET_METHOD)]
class MyRetry extends \Hyperf\Retry\Annotation\AbstractRetry
{
    public $policies = [
        MaxAttemptsRetryPolicy::class,
        ClassifierRetryPolicy::class,
    ];
    public $maxAttempts = 3;
}
```

Você pode continuar aprimorando essa anotação até que ela atenda às suas necessidades personalizadas. Por exemplo, para configurar que apenas a `TimeoutException` personalizada do usuário seja repetida e usar um intervalo variável de pelo menos 100 milissegundos entre tentativas, faça assim:

```php
<?php

declare(strict_types=1);

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

#[Attribute(Attribute::TARGET_METHOD)]
class MyRetry extends \Hyperf\Retry\Annotation\Retry
{
    public $policies = [
        MaxAttemptsRetryPolicy::class,
        ClassifierRetryPolicy::class,
        SleepRetryPolicy::class,
    ];
    public $maxAttempts = 3;
    public $base = 100;
    public $strategy = \Hyperf\Retry\BackoffStrategy::class;
    public $retryThrowables = [\App\Exception\TimeoutException::class];
}
```

Desde que o arquivo seja escaneado pelo Hyperf, você poderá usar a anotação `#[MyRetry]` no método para repetir erros de timeout.

## Configuração padrão

As propriedades padrão completas da anotação `#[Retry]` são as seguintes:

```php
/**
 * Array of retry policies. Think of these as stacked middlewares.
 * @var string[]
 */
public $policies = [
    FallbackRetryPolicy::class,
    ClassifierRetryPolicy::class,
    BudgetRetryPolicy::class,
    MaxAttemptsRetryPolicy::class,
    SleepRetryPolicy::class,
];

/**
 * The algorithm for retry intervals.
 */
public string $sleepStrategyClass = SleepStrategyInterface::class;

/**
 * Max Attampts.
 */
public int $maxAttempts = 10;

/**
 * Retry Budget.
 * ttl: Seconds of token lifetime.
 * minRetriesPerSec: Base retry token generation speed.
 * percentCanRetry: Generate new token at this ratio of the request volume.
 *
 * @var array|RetryBudgetInterface
 */
public $retryBudget = [
    'ttl' => 10,
    'minRetriesPerSec' => 1,
    'percentCanRetry' => 0.2,
];

/**
 * Base time inteval (ms) for each try. For backoff strategy this is the interval for the first try
 * while for flat strategy this is the interval for every try.
 */
public int $base = 0;

/**
 * Configures a Predicate which evaluates if an exception should be retried.
 * The Predicate must return true if the exception should be retried, otherwise it must return false.
 *
 * @var callable|string
 */
public $retryOnThrowablePredicate = '';

/**
 * Configures a Predicate which evaluates if an result should be retried.
 * The Predicate must return true if the result should be retried, otherwise it must return false.
 *
 * @var callable|string
 */
public $retryOnResultPredicate = '';

/**
 * Configures a list of Throwable classes that are recorded as a failure and thus are retried.
 * Any Throwable matching or inheriting from one of the list will be retried, unless ignored via ignoreExceptions.
 *
 * Ignoring an Throwable has priority over retrying an exception.
 *
 * @var array<string|\Throwable>
 */
public $retryThrowables = [\Throwable::class];

/**
 * Configures a list of error classes that are ignored and thus are not retried.
 * Any exception matching or inheriting from one of the list will not be retried, even if marked via retryExceptions.
 *
 * @var array<string|\Throwable>
 */
public $ignoreThrowables = [];

/**
 * The fallback callable when all attempts exhausted.
 *
 * @var callable|string
 */
public $fallback = '';
```

## Políticas opcionais

### Política de número máximo de tentativas `MaxAttemptsRetryPolicy`

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| maxAttempts |  int | Número máximo de tentativas |


### Política de classificação de erros `ClassifierRetryPolicy`

Usa um classificador para determinar se o erro pode ser repetido.

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| ignoreThrowables |  array | Nome da classe `Throwable` a ser ignorada. Tem prioridade sobre `retryThrowables` |
| retryThrowables | array | Nome da classe `Throwable` que precisa de retry. Tem prioridade sobre `retryOnThrowablePredicate` |
| retryOnThrowablePredicate | callable | Usa uma função para determinar se o `Throwable` pode ser repetido. Se puder, retorne true; caso contrário, deve retornar false. |
| retryOnResultPredicate | callable | Usa uma função para determinar se o valor de retorno pode ser repetido. Se puder, retorne true; caso contrário, deve retornar false. |

### Política de fallback `FallbackRetryPolicy`

Executa o método alternativo depois que os recursos de retry se esgotam.

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| fallback |  callable | Método fallback |

Além de aceitar códigos reconhecidos por `is_callable`, `fallback` também aceita o formato `class@method`; o framework obterá a `class` correspondente do `Container` e então executará o método `method` dela.

### Política de sleep `SleepRetryPolicy`

Fornece dois tipos de estratégia de intervalo entre tentativas: intervalo de comprimento fixo (FlatStrategy) e intervalo de comprimento variável (BackoffStrategy).

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| base |  int | Tempo base de sleep (milissegundos)|
| strategy |  string | Nome de qualquer classe que implemente `Hyperf\Retry\SleepStrategyInterface`, como `Hyperf\Retry\BackoffStrategy` |

### Política de timeout `TimeoutRetryPolicy`

Sai da sessão de retry quando a duração total da execução ultrapassa o tempo definido.

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| timeout |  float | Tempo de timeout (segundos)|

### Política de circuit breaker `CircuitBreakerRetryPolicy`

Quando a sessão de retry é encerrada por falha, marca diretamente como circuit breaker por um período, sem fazer nenhuma nova tentativa.

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| circuitBreakerState.resetTimeout |  float | Tempo necessário para recuperação (segundos)|

### Política de orçamento `BudgetRetryPolicy`

Cada ponto com a anotação `#[Retry]` gera um token bucket correspondente. Sempre que o método anotado é chamado, um token com tempo de expiração (ttl) é colocado no token bucket. Se ocorrer um erro que possa ser repetido, antes do retry é necessário consumir a quantidade correspondente de tokens (percentCanRetry); caso contrário, não haverá retry (e o erro continua a se propagar). Por exemplo, quando percentCanRetry=0.2, cada retry consome 5 tokens. Assim, ao encontrar um par fora do ar, haverá no máximo 20% de consumo extra de retry, o que deve ser aceitável para a maioria dos sistemas.

Para atender a alguns métodos de baixa frequência de uso, a cada segundo também é gerada uma certa quantidade de tokens de "base" (minRetriesPerSec), garantindo a estabilidade do sistema.

|    Parâmetro    | Tipo | Descrição |
| ---------- | --- | --- |
| retryBudget.ttl |  int | Tempo de expiração do token de recuperação (segundos)|
| retryBudget.minRetriesPerSec |  int | Número mínimo de retries de "base" por segundo|
| retryBudget.percentCanRetry |  float | Percentual do número de retries em relação ao total de requisições |

> O token bucket do componente de retry não é compartilhado entre workers, portanto o número final de retries deve ser multiplicado pela quantidade de workers.

## Aliases de anotação

Como a configuração da anotação de retry é relativamente complexa, são fornecidos alguns aliases predefinidos para facilitar a escrita.

* `#[RetryThrowable]` Repete apenas `Throwable`. Igual ao `#[Retry]` padrão.

* `#[RetryFalsy]` Repete apenas erros em que o valor de retorno é fracamente igual a false ($result == false), sem repetir exceções.

* `#[BackoffRetryThrowable]` Versão do `#[RetryThrowable]` com intervalo variável, com intervalo de retry de pelo menos 100 milissegundos.

* `#[BackoffRetryFalsy]` Versão do `#[RetryFalsy]` com intervalo variável, com intervalo de retry de pelo menos 100 milissegundos.

## Chamada encadeada Fluent

Além de usar este componente por anotação, você também pode usá-lo por meio de funções PHP comuns.

```php
<?php

$result = \Hyperf\Retry\Retry::with(
    new \Hyperf\Retry\Policy\ClassifierRetryPolicy(), //Por padrão, repete todos os Throwable
    new \Hyperf\Retry\Policy\MaxAttemptsRetryPolicy(5) //Repete no máximo 5 vezes
)->call(function(){
    if (rand(1, 100) >= 20){
        return true;
    }
    throw new Exception;
});
```
Para melhorar a legibilidade, você também pode usar a seguinte forma fluente.

```php
<?php

$result = \Hyperf\Retry\Retry::whenReturns(false) // Repete quando retornar false
    ->max(3) // No máximo 3 vezes
    ->inSeconds(5) // No máximo 5 segundos
    ->sleep(1) // Intervalo de 1 milissegundo
    ->fallback(function(){return true;}) // Função fallback
    ->call(function(){
        if (rand(1, 100) >= 20){
            return true;
        }
        return false;
    });
```
