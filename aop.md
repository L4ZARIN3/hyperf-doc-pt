# AOP Programação Orientada a Aspectos

## Conceito

AOP é a abreviação de `Aspect Oriented Programming`, que significa: `programação orientada a aspectos`, uma técnica que realiza a manutenção unificada das funcionalidades do programa por meio de proxy dinâmico e outras técnicas. AOP é a continuação da OOP, também é um conteúdo importante no Hyperf e um paradigma derivado da programação funcional. Com AOP, é possível isolar cada parte da lógica de negócio, reduzindo o acoplamento entre as partes da lógica de negócio, aumentando a reutilização do programa e, ao mesmo tempo, melhorando a eficiência do desenvolvimento.

Em termos simples, no Hyperf é possível, por meio de `aspecto (Aspect)`, intervir no fluxo de execução de qualquer método de qualquer classe, alterando ou reforçando a funcionalidade do método original — isso é AOP.

> Atenção: "qualquer classe" aqui referido não é, em sentido estrito, todas as classes; as próprias classes usadas para implementar a funcionalidade AOP no início da inicialização do Hyperf não podem ser interceptadas.

## Introdução

Em relação à forma de uso da funcionalidade AOP implementada em outros frameworks, simplificamos ainda mais o uso dessa funcionalidade, sem fazer divisões muito detalhadas, existindo apenas uma forma genérica: `ao redor (Around)`:

- `Aspecto (Aspect)` é a classe de definição do entrelaçamento no fluxo, incluindo o alvo a ser interceptado e a implementação do processamento de modificação e reforço do método original
- `Classe proxy (ProxyClass)`, cada classe alvo interceptada gerará, no final, uma classe proxy, para atingir o objetivo de executar os métodos do `Aspecto (Aspect)`

## Definir um aspecto (Aspect)

Cada `Aspecto (Aspect)` deve implementar a interface `Hyperf\Di\Aop\AroundInterface` e fornecer as propriedades `public` `$classes` e `$annotations`. Para facilitar o uso, podemos herdar de `Hyperf\Di\Aop\AbstractAspect` para simplificar o processo de definição. Vamos descrever com código.

```php
<?php
namespace App\Aspect;

use App\Service\SomeClass;
use App\Annotation\SomeAnnotation;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class FooAspect extends AbstractAspect
{
    // As classes ou Traits a serem interceptados, podendo ser vários, também é possível especificar um método concreto com ::, e usar * para correspondência aproximada
    public array $classes = [
        SomeClass::class,
        'App\Service\SomeClass::someMethod',
        'App\Service\SomeClass::*Method',
    ];

    // As anotações a serem interceptadas; na prática, são interceptadas as classes que usam essas anotações, somente anotações de classe e de método de classe podem ser interceptadas
    public array $annotations = [
        SomeAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // Após a interceptação do aspecto, a execução do método correspondente será de responsabilidade deste ponto
        // $proceedingJoinPoint é o join point; por meio do método process() dessa classe, o método original é chamado e o resultado é obtido
        // Realiza algum processamento antes da chamada
        $result = $proceedingJoinPoint->process();
        // Realiza algum processamento após a chamada
        return $result;
    }
}
```

Cada `Aspecto (Aspect)` deve definir a anotação `#[Aspect]` ou ser configurado em `config/autoload/aspects.php` para funcionar.

> Ao usar a anotação `#[Aspect]`, é necessário o namespace `use Hyperf\Di\Annotation\Aspect;`;

Você também pode usar as propriedades da própria anotação `#[Aspect]` para concluir a configuração do alvo de interceptação; a forma de anotação abaixo atinge o mesmo objetivo do exemplo acima:

```php
<?php
namespace App\Aspect;

use App\Service\SomeClass;
use App\Annotation\SomeAnnotation;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[
    Aspect(
        classes: [
            SomeClass::class,
            "App\Service\SomeClass::someMethod",
            "App\Service\SomeClass::*Method"
        ],
        annotations: [
            SomeAnnotation::class
        ]
    )
]
class FooAspect extends AbstractAspect
{
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // Após a interceptação do aspecto, a execução do método correspondente será de responsabilidade deste ponto
        // $proceedingJoinPoint é o join point; por meio do método process() dessa classe, o método original é chamado e o resultado é obtido
        // Realiza algum processamento antes da chamada
        $result = $proceedingJoinPoint->process();
        // Realiza algum processamento após a chamada
        return $result;
    }
}
```

## Alterar ou reforçar o método original

Além disso, você também pode atender às necessidades de negócio obtendo a instância original, a reflexão do método, os parâmetros submetidos, as anotações etc.:

```php
<?php
namespace App\Aspect;

use App\Service\SomeClass;
use App\Annotation\SomeAnnotation;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class FooAspect extends AbstractAspect
{
    public array $classes = [
        SomeClass::class,
        'App\Service\SomeClass::someMethod',
        'App\Service\SomeClass::*Method',
    ];

    public array $annotations = [
        SomeAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // Obtém o protótipo de reflexão do método atual
        /** @var \ReflectionMethod **/
        $reflect = $proceedingJoinPoint->getReflectMethod();

        // Obtém os parâmetros submetidos ao chamar o método
        $arguments = $proceedingJoinPoint->getArguments(); // array

        // Obtém a instância da classe original e chama outros métodos da classe original
        $originalInstance = $proceedingJoinPoint->getInstance();
        $originalInstance->yourFunction();

        // Obtém os metadados das anotações
        /** @var \Hyperf\Di\Aop\AnnotationMetadata **/
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();

        // Chama o método original sem a influência da classe proxy
        $proceedingJoinPoint->processOriginalMethod();

        // Não executa o método original, realizando outras operações
        $result = date('YmdHis', time() - 86400);
        return $result;
    }
}
```

> Atenção: a classe obtida por `getInstance` é a classe proxy; seus métodos ainda serão afetados por outros aspectos, e chamadas aninhadas entre si causarão loop infinito e esgotamento de memória.

## Cache de classes proxy

Todas as classes afetadas pelo AOP gerarão o `cache de classe proxy` correspondente na pasta `./runtime/container/proxy/`. A geração automática na inicialização depende do valor do item de configuração `scan_cacheable` no arquivo `config/config.php`, cujo valor padrão é `false`. Se esse item for `true`, o Hyperf não escaneará nem gerará o cache de classes proxy, usando diretamente os arquivos de cache existentes como classes proxy finais. Se esse item for `false`, o Hyperf escaneará o domínio de escaneamento de anotações a cada inicialização da aplicação e gerará automaticamente o cache de classes proxy correspondente; quando o código mudar, o cache de classes proxy também será gerado novamente automaticamente.

Normalmente, no ambiente de desenvolvimento, esse valor é `false`, o que facilita o desenvolvimento e a depuração. Já no deploy em ambiente de produção, podemos querer que o Hyperf gere antecipadamente todas as classes proxy, em vez de gerá-las dinamicamente no uso. Isso pode ser feito com o comando `php bin/hyperf.php` para gerar todas as classes proxy e, em seguida, alterar o valor desse item de configuração para `true` por meio da variável de ambiente `SCAN_CACHEABLE`, com o objetivo de reduzir o tempo de inicialização e o uso de memória da aplicação.

Com base no acima exposto, se você usar tecnologias de virtualização como Docker ou Kubernetes para fazer o deploy da sua aplicação, pode gerar o cache de classes proxy correspondente já na fase de build da imagem e gravá-lo na imagem; ao executar a instância da imagem, o tempo de inicialização e a memória da aplicação serão bastante reduzidos.
