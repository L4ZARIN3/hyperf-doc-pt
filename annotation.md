# Anotações

As anotações são uma funcionalidade muito poderosa do Hyperf, pois através delas é possível reduzir bastante a quantidade de configuração e implementar muitos recursos muito convenientes.

## Conceito

### O que é uma anotação?

A funcionalidade de anotações fornece a capacidade de adicionar metadados estruturados e legíveis por máquina a todas as partes declarativas do código. O alvo de uma anotação pode ser uma classe, método, função, parâmetro, propriedade ou constante de classe. Através da API de reflexão é possível obter em tempo de execução os metadados definidos pela anotação. Portanto, as anotações podem se tornar uma linguagem de configuração embutida diretamente no código.

Com o uso de anotações, a implementação de funcionalidades e o uso de funcionalidades na aplicação podem ser desacoplados entre si. De certa forma, isso pode ser comparado a uma interface e sua implementação. Porém, interface e implementação estão relacionados ao código, enquanto as anotações estão relacionadas a informações adicionais e configurações declaradas. Uma interface pode ser implementada por classes, e as anotações também podem ser declaradas em métodos, funções, parâmetros, propriedades e constantes de classe. Portanto, elas são mais flexíveis que as interfaces.

Um exemplo simples de uso de anotações: substituir métodos opcionais de uma interface por anotações. Suponhamos que a interface ActionHandler represente uma operação da aplicação: parte das implementações de action handler precisa de setup, parte não precisa. Podemos usar anotações em vez de exigir que todas as classes implementem a interface ActionHandler e o método setUp(). Isso traz um benefício — as anotações podem ser usadas múltiplas vezes.

### Como as anotações funcionam?

Mencionamos que as anotações são apenas definições de metadados e precisam ser usadas em conjunto com a aplicação para funcionar. No Hyperf, os dados dentro das anotações são coletados na classe `Hyperf\Di\Annotation\AnnotationCollector` para uso da aplicação. Claro que, conforme sua situação real, também é possível coletá-los em classes personalizadas por você, e depois ler e utilizar os metadados das anotações já coletados nos locais onde as próprias anotações devem funcionar, para alcançar a implementação de funcionalidade esperada.

### Ignorar determinadas anotações

Em alguns casos podemos querer ignorar determinadas anotações, por exemplo, ao integrar algumas ferramentas de geração automática de documentação, muitas delas definem o conteúdo relacionado à estrutura da documentação por meio de anotações, e essas anotações podem não estar de acordo com a forma de uso do Hyperf. Podemos definir as anotações relevantes como ignoradas dentro de `config/autoload/annotations.php`.

```php
use JetBrains\PhpStorm\ArrayShape;

return [
    'scan' => [
        // Todas as anotações dentro do array ignore_annotations serão ignoradas pelo scanner de anotações
        'ignore_annotations' => [
            ArrayShape::class,
        ],
    ],
];
```

## Usar anotações

As anotações possuem 3 tipos de alvos de aplicação, que são `classe`, `método de classe` e `propriedade de classe`.

### Usar anotação de classe

A anotação de classe é definida dentro do bloco de comentário acima da palavra-chave `class`. Por exemplo, as comuns `Controller` e `AutoController` são exemplos clássicos de uso de anotação de classe. O exemplo de código abaixo é um exemplo de uso correto de anotação de classe, indicando que a anotação `ClassAnnotation` é aplicada à classe `Foo`.

```php
<?php
#[ClassAnnotation]
class Foo {}
```

### Usar anotação de método de classe

A anotação de método de classe é definida dentro do bloco de comentário acima do método. Por exemplo, a comum `RequestMapping` é um exemplo clássico de uso de anotação de método de classe. O exemplo de código abaixo é um exemplo de uso correto de anotação de método de classe, indicando que a anotação `MethodAnnotation` é aplicada ao método `Foo::bar()`.

```php
<?php
class Foo
{
    #[MethodAnnotation]
    public function bar()
    {
        // some code
    }
}
```

### Usar anotação de propriedade de classe

A anotação de propriedade de classe é definida dentro do bloco de comentário acima da propriedade. Por exemplo, as comuns `Value` e `Inject` são exemplos clássicos de uso de anotação de propriedade de classe. O exemplo de código abaixo é um exemplo de uso correto de anotação de propriedade de classe, indicando que a anotação `PropertyAnnotation` é aplicada à propriedade `$bar` da classe `Foo`.

```php
<?php
class Foo
{
    #[PropertyAnnotation]
    private $bar;
}
```

### Passagem de parâmetros em anotações

- Passar o parâmetro principal único `#[DemoAnnotation('value')]`
- Passar parâmetros de string `#[DemoAnnotation(key1: 'value1', key2: 'value2')]`
- Passar parâmetros de array `#[DemoAnnotation(key: ['value1', 'value2'])]`

## Anotação personalizada

### Criar uma classe de anotação

```php
<?php
namespace App\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Foo extends AbstractAnnotation
{
    public function __construct(public array $bar, public int $baz = 0)
    {
    }
}
```

Usar a classe de anotação:

```php
<?php
use App\Annotation\Foo;

#[Foo(bar: [1, 2], baz: 3)]
class IndexController extends AbstractController
{
    // Utiliza os dados da anotação
}
```

Vamos observar que, no exemplo de código acima, as classes de anotação herdam a classe abstrata `Hyperf\Di\Annotation\AbstractAnnotation`. Para uma classe de anotação, isso não é obrigatório, mas para as classes de anotação do Hyperf, herdar a interface `Hyperf\Di\Annotation\AnnotationInterface` é obrigatório. O papel da classe abstrata aqui é fornecer uma forma de definição extremamente simples; essa classe abstrata já implementou para você funcionalidades muito convenientes como `distribuição automática dos parâmetros da anotação para as propriedades da classe` e `coleta automática no AnnotationCollector conforme as regras de acordo com o local de uso da anotação`.

### Coletor de anotações personalizado

O fluxo específico de execução da coleta de anotações também é implementado dentro da classe de anotação, e os métodos relacionados são definidos pela `Hyperf\Di\Annotation\AnnotationInterface`. Essa interface exige a implementação dos 3 métodos abaixo, e você pode implementar a lógica correspondente conforme sua necessidade:

- `public function collectClass(string $className): void;` Este método é acionado quando uma anotação definida em uma classe é escaneada
- `public function collectMethod(string $className, ?string $target): void;` Este método é acionado quando uma anotação definida em um método de classe é escaneada
- `public function collectProperty(string $className, ?string $target): void` Este método é acionado quando uma anotação definida em uma propriedade de classe é escaneada

Como o framework implementa a funcionalidade de cache do coletor de anotações, é necessário que você configure o coletor personalizado em `annotations.scan.collectors`, para que o framework possa armazenar automaticamente em cache as anotações coletadas e reutilizá-las na próxima inicialização.
Se o coletor correspondente não for configurado, a anotação personalizada só terá efeito na primeira inicialização do `server` e não terá efeito nas inicializações seguintes.

```php
<?php

return [
    // Observe que nos arquivos de configuração dentro de config/autoload não existe a camada annotations
    'annotations' => [
        'scan' => [
            'collectors' => [
                CustomCollector::class,
            ],
        ],
    ],
];

```

### Utilizar os dados das anotações

Quando não há um método personalizado de coleta de anotações, por padrão os metadados das anotações são coletados de forma unificada na classe `Hyperf\Di\Annotation\AnnotationCollector`. Através dos métodos estáticos dessa classe é possível obter facilmente os metadados correspondentes para julgamentos lógicos ou implementações.

### Funcionalidade ClassMap

O framework fornece a configuração `class_map`, que permite ao usuário substituir diretamente as classes que precisam ser carregadas.

Por exemplo, a seguir implementamos uma funcionalidade que copia automaticamente o contexto da corrotina:

Primeiro, implementamos uma classe `Coroutine` usada para copiar o contexto. Nela, o método `create()` pode copiar o contexto da corrotina pai para a corrotina filha.

Para evitar conflitos de nomenclatura, a convenção é usar `class_map` como nome de pasta, seguido pela pasta e arquivo do namespace a ser substituído.

Ex.: `class_map/Hyperf/Coroutine/Coroutine.php`

[Coroutine.php](https://github.com/hyperf/biz-skeleton/blob/master/app/Kernel/Context/Coroutine.php)

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

namespace App\Kernel\Context;

use App\Kernel\Log\AppendRequestIdProcessor;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Coroutine as Co;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Coroutine
{
    protected LoggerInterface $logger;

    public function __construct(protected ContainerInterface $container)
    {
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    /**
     * @return int Returns the coroutine ID of the coroutine just created.
     *             Returns -1 when coroutine create failed.
     */
    public function create(callable $callable): int
    {
        $id = Co::id();
        $coroutine = Co::create(function () use ($callable, $id) {
            try {
                // Shouldn't copy all contexts to avoid socket already been bound to another coroutine.
                Context::copy($id, [
                    AppendRequestIdProcessor::REQUEST_ID,
                    ServerRequestInterface::class,
                ]);
                $callable();
            } catch (Throwable $throwable) {
                $this->logger->warning((string) $throwable);
            }
        });

        try {
            return $coroutine->getId();
        } catch (Throwable $throwable) {
            $this->logger->warning((string) $throwable);
            return -1;
        }
    }
}

```

Em seguida, implementamos um objeto idêntico a `Hyperf\Coroutine\Coroutine`. Nele, o método `create()` é substituído pela implementação que fizemos acima.

[Coroutine.php](https://github.com/hyperf/biz-skeleton/blob/master/app/Kernel/ClassMap/Coroutine.php)

`class_map/Hyperf/Coroutine/Coroutine.php`

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

namespace Hyperf\Coroutine;

use App\Kernel\Context\Coroutine as Go;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Coroutine as Co;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Engine\Exception\RunningInNonCoroutineException;
use Throwable;

class Coroutine
{
    /**
     * Returns the current coroutine ID.
     * Returns -1 when running in non-coroutine context.
     */
    public static function id(): int
    {
        return Co::id();
    }

    public static function defer(callable $callable): void
    {
        Co::defer(static function () use ($callable) {
            try {
                $callable();
            } catch (Throwable $exception) {
                di()->get(StdoutLoggerInterface::class)->error((string) $exception);
            }
        });
    }

    public static function sleep(float $seconds): void
    {
        usleep(intval($seconds * 1000 * 1000));
    }

    /**
     * Returns the parent coroutine ID.
     * Returns 0 when running in the top level coroutine.
     * @throws RunningInNonCoroutineException when running in non-coroutine context
     * @throws CoroutineDestroyedException when the coroutine has been destroyed
     */
    public static function parentId(?int $coroutineId = null): int
    {
        return Co::pid($coroutineId);
    }

    /**
     * @return int Returns the coroutine ID of the coroutine just created.
     *             Returns -1 when coroutine create failed.
     */
    public static function create(callable $callable): int
    {
        return di()->get(Go::class)->create($callable);
    }

    public static function inCoroutine(): bool
    {
        return Co::id() > 0;
    }

    public static function stats(): array
    {
        return Co::stats();
    }

    public static function exists(int $id): bool
    {
        return Co::exists($id);
    }
}

```

Em seguida, configuramos o `class_map`, conforme abaixo:

```php
<?php

declare(strict_types=1);

use Hyperf\Coroutine\Coroutine;

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
        'class_map' => [
            // Nome da classe a ser mapeada => endereço do arquivo onde a classe está
            Coroutine::class => BASE_PATH . '/class_map/Hyperf/Coroutine/Coroutine.php',
        ],
    ],
];

```

Assim, métodos como `co()` e `parallel()` poderão obter automaticamente os dados do contexto da corrotina pai, como `Request`.
