# Injeção de dependência

## Introdução

O Hyperf usa por padrão o [hyperf/di](https://github.com/hyperf/di) como contêiner de gerenciamento de injeção de dependência do framework. Embora, por design, permitamos que você substitua por outro contêiner de gerenciamento de injeção de dependência, não recomendamos fortemente a troca desse componente.   
O [hyperf/di](https://github.com/hyperf/di)
é um componente poderoso para gerenciar as dependências de classes e realizar a injeção automática. A diferença em relação aos contêineres de injeção de dependência tradicionais é que ele é mais adequado ao uso em aplicações de ciclo de vida longo, oferece suporte a [anotações e injeção por anotações](annotation.md)
e fornece recursos extremamente poderosos de [AOP (programação orientada a aspectos)](aop.md). Esses recursos e sua facilidade de uso são a saída central do Hyperf, e acreditamos com confiança que este componente é o mais excelente.

## Instalação

Esse componente existe por padrão no projeto [hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton) como componente principal. Caso queira usá-lo em outro framework, você pode instalá-lo com o comando abaixo.

```bash
composer require hyperf/di
```

## Vinculação de relações entre objetos

### Injeção de objetos simples

Normalmente, as relações entre classes e a injeção não precisam ser definidas explicitamente; o Hyperf faz tudo isso silenciosamente para você. Vamos usar alguns exemplos de código para explicar os usos relacionados.      
Suponha que precisemos chamar o método `getInfoById(int $id)` da classe `UserService` dentro de `IndexController`.

```php
<?php
namespace App\Service;

class UserService
{
    public function getInfoById(int $id)
    {
        // Suponha que exista uma entidade Info
        return (new Info())->fill($id);    
    }
}
```

#### Injeção via método construtor

```php
<?php
namespace App\Controller;

use App\Service\UserService;

class IndexController
{
    private UserService $userService;
    
    // A injeção automática é feita declarando o tipo do parâmetro no construtor
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function index()
    {
        $id = 1;
        // Usar diretamente
        return $this->userService->getInfoById($id);    
    }
}
```

> Observe que, ao usar a injeção via construtor, o chamador, ou seja, `IndexController`, precisa ser um objeto criado pelo DI para que a injeção automática funcione. Como o Controller é criado pelo DI por padrão, é possível usar a injeção via construtor diretamente.

Quando você deseja definir uma dependência opcional, pode declarar o parâmetro como `nullable` ou definir seu valor padrão como `null`. Isso significa que, se o parâmetro não for encontrado no contêiner DI ou não puder ter o objeto correspondente criado, nenhuma exceção será lançada e o valor `null` será injetado diretamente.

```php
<?php
namespace App\Controller;

use App\Service\UserService;

class IndexController
{
    private ?UserService $userService;
    
    // Ao definir o parâmetro como nullable, indicamos que ele é um parâmetro opcional
    public function __construct(?UserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function index()
    {
        $id = 1;
        if ($this->userService instanceof UserService) {
            // $userService só está disponível quando o valor existe
            return $this->userService->getInfoById($id);    
        }
        return null;
    }
}
```

#### Injeção via anotação `#[Inject]`

```php
<?php
namespace App\Controller;

use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;

class IndexController
{

    #[Inject]
    private UserService $userService;
    
    public function index()
    {
        $id = 1;
        // Usar diretamente
        return $this->userService->getInfoById($id);    
    }
}
```

> A injeção via anotação `#[Inject]` pode atuar em objetos (singletons) criados pelo DI e também em objetos criados com a palavra-chave `new`;

> Ao usar a anotação `#[Inject]`, é necessário o namespace `use Hyperf\Di\Annotation\Inject;`;

##### Parâmetro Required

A anotação `#[Inject]` possui um parâmetro `required`, cujo valor padrão é `true`. Ao definir esse parâmetro como `false`, indica-se que a propriedade do membro é uma dependência opcional. Quando o objeto indicado em `@var` não existir no contêiner DI ou não puder ser criado, nenhuma exceção será lançada; em vez disso, será injetado um `null`, como abaixo:

```php
<?php
namespace App\Controller;

use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;

class IndexController
{
    /**
     * Injeta, via anotação `#[Inject]`, o objeto do tipo da propriedade declarado pela anotação
     * Quando UserService não existir no contêiner DI ou não puder ser criado, injeta null
     */
    #[Inject(required: false)]
    private ?UserService $userService;
    
    public function index()
    {
        $id = 1;
        if ($this->userService instanceof UserService) {
            // $userService só está disponível quando o valor existe
            return $this->userService->getInfoById($id);    
        }
        return null;
    }
}
```

### Injeção de objetos abstratos

Com base no exemplo acima, do ponto de vista racional, o Controller não deveria depender diretamente da classe `UserService`, mas provavelmente de uma interface `UserServiceInterface`. Nesse caso, podemos usar `config/autoload/dependencies.php` para vincular a relação entre os objetos e atingir o objetivo. Vamos explicar novamente com código.

Defina uma interface:

```php
<?php
namespace App\Service;

interface UserServiceInterface
{
    public function getInfoById(int $id);
}
```

`UserService` implementa a interface:

```php
<?php
namespace App\Service;

class UserService implements UserServiceInterface
{
    public function getInfoById(int $id)
    {
        // Suponha que exista uma entidade Info
        return (new Info())->fill($id);    
    }
}
```

Configure a relação em `config/autoload/dependencies.php`:

```php
<?php
return [
    \App\Service\UserServiceInterface::class => \App\Service\UserService::class
];
```

Após essa configuração, é possível injetar o objeto `UserService` diretamente por meio de `UserServiceInterface`. Aqui usamos apenas a injeção por anotação como exemplo; a injeção via construtor funciona da mesma forma:

```php
<?php
namespace App\Controller;

use App\Service\UserServiceInterface;
use Hyperf\Di\Annotation\Inject;

class IndexController
{
    #[Inject]
    private UserServiceInterface $userService;
    
    public function index()
    {
        $id = 1;
        // Usar diretamente
        return $this->userService->getInfoById($id);    
    }
}
```

### Injeção de objetos de fábrica

Suponha que a implementação de `UserService` seja um pouco mais complexa e que, ao criar o objeto `UserService`, o construtor precise receber alguns parâmetros que não são injetados diretamente. Suponha que precisemos obter um valor da configuração e que `UserService`
precise decidir, com base nesse valor, se o modo de cache será habilitado (aliás, o Hyperf oferece o recurso mais prático de [cache de modelos](db/model-cache.md)).

Precisamos criar uma fábrica para gerar o objeto `UserService`:

```php
<?php 
namespace App\Service;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class UserServiceFactory
{
    // Implemente um método __invoke() para produzir o objeto; os parâmetros do método recebem automaticamente a instância atual do contêiner e um array de parâmetros
    public function __invoke(ContainerInterface $container, array $parameters = [])
    {
        $config = $container->get(ConfigInterface::class);
        // Suponha que a chave da configuração correspondente seja cache.enable
        $enableCache = $config->get('cache.enable', false);
        return make(UserService::class, compact('enableCache'));
    }
}
```

`UserService` também pode fornecer um parâmetro no construtor para receber o valor correspondente:

```php
<?php
namespace App\Service;

class UserService implements UserServiceInterface
{
    private bool $enableCache;
    
    public function __construct(bool $enableCache)
    {
        // Recebe o valor e armazena na propriedade da classe
        $this->enableCache = $enableCache;
    }
    
    public function getInfoById(int $id)
    {
        return (new Info())->fill($id);    
    }
}
```

Ajuste a relação de vinculação em `config/autoload/dependencies.php`:

```php
<?php
return [
    \App\Service\UserServiceInterface::class => \App\Service\UserServiceFactory::class
];
```

Assim, ao injetar `UserServiceInterface`, o contêiner delegará ao `UserServiceFactory` a criação do objeto.

> É claro que, nesse cenário, pode-se usar a anotação `#[Value]` para injetar a configuração de forma mais prática, sem construir uma classe de fábrica; isto é apenas um exemplo

### Carregamento sob demanda (lazy loading)

A injeção de dependência de ciclo de vida longo do Hyperf é concluída na inicialização do projeto. Isso significa que classes de ciclo de vida longo precisam observar:

* No momento do construtor, ainda não há ambiente de corrotina. Se for injetada uma classe que possa acionar a troca de corrotina, o framework falhará na inicialização.

* No construtor, deve-se evitar dependência circular (um exemplo típico é `Listener` e `EventDispatcherInterface`), caso contrário a inicialização também falhará.

Atualmente, a solução é: injetar apenas `Psr\Container\ContainerInterface` na instância e, para os outros componentes, obtê-los via `container` quando não estiver em execução do construtor. Porém, o PSR-11 afirma:

> "O usuário não deve passar o contêiner como parâmetro para um objeto e depois obter as dependências do objeto por meio do contêiner dentro do objeto. Isso é usar o contêiner como um localizador de serviços, e o localizador de serviços é um antipadrão"

Ou seja, embora essa abordagem seja eficaz, do ponto de vista dos padrões de projeto ela não é recomendada.

Outra solução é usar o padrão de proxy preguiçoso (lazy proxy), comum em PHP: injetar um objeto proxy e instanciar o objeto de destino somente no momento do uso. O componente Hyperf DI projetou o recurso de injeção lazy loading.

Adicione o arquivo `config/lazy_loader.php` e vincule a relação de lazy loading:

```php
<?php
return [
    /**
     * Formato: nome da classe proxy => nome da classe original
     * A classe proxy não existe neste momento; o Hyperf a gerará automaticamente na pasta runtime.
     * O nome e o namespace da classe proxy podem ser definidos livremente.
     */
    'App\Service\LazyUserService' => \App\Service\UserServiceInterface::class
];
```

Assim, ao injetar `App\Service\LazyUserService`, o contêiner criará um `proxy de lazy loading` e o injetará no objeto de destino.

```php
use App\Service\LazyUserService;

class Foo{
    public $service;
    public function __construct(LazyUserService $service){
        $this->service = $service;
    }
}
````

Você também pode injetar o proxy de lazy loading pela anotação `#[Inject(lazy: true)]`. Com a anotação, não é necessário criar arquivo de configuração para implementar o lazy loading.

```php
use Hyperf\Di\Annotation\Inject;
use App\Service\UserServiceInterface;

class Foo
{
    /**
     * @var UserServiceInterface
     */
    #[Inject(lazy: true)]
    public $service;
}
````

Atenção: o objeto proxy só instanciará o objeto alvo a partir do contêiner quando o proxy executar as operações abaixo.

```php
// Chamada de método
$proxy->someMethod();

// Leitura de propriedade
echo $proxy->someProperty;

// Escrita de propriedade
$proxy->someProperty = 'foo';

// Verificar se a propriedade existe
isset($proxy->someProperty);

// Remover propriedade
unset($proxy->someProperty);
```

### Peso de vinculação

A partir da versão v3.0.17, foi adicionado o recurso de peso. É possível injetar o objeto de maior peso de acordo com o peso. Por exemplo, as duas configurações `ConfigProvider` abaixo

```php
<?php
use FooInterface;
use Foo;

return [
    'dependencies' => [
        FooInterface::class => new PriorityDefinition(Foo::class, 1),
    ]
];
```

```php
<?php
use FooInterface;
use Foo2;

return [
    'dependencies' => [
        FooInterface::class => Foo2::class,
    ]
];
```

Quando `PriorityDefinition` não é usado, o peso é 0. Portanto, o que é vinculado a `FooInterface` é `Foo`.

## Objetos de ciclo de vida curto

Objetos criados com a palavra-chave `new` são, sem dúvida, de ciclo de vida curto. Então, se você deseja criar um objeto de ciclo de vida curto mas também usar o `recurso de injeção automática de dependências do construtor`?
Nesse caso, podemos usar a função `make(string $name, array $parameters = [])` para criar a instância correspondente a `$name`. Exemplo de código:

```php
$userService = make(UserService::class, ['enableCache' => true]);
```

> Observe que apenas o objeto correspondente a `$name` é de ciclo de vida curto; todas as dependências desse objeto são obtidas pelo método `get()`, ou seja, são objetos de ciclo de vida longo. Pode-se entender que esse objeto é uma cópia superficial

## Obter o objeto do contêiner

Às vezes, ao implementar requisitos mais dinâmicos, podemos querer obter diretamente o objeto `Container` (contêiner). Na grande maioria dos casos, a classe de entrada do framework (como classes de comando, controladores, provedores de serviços RPC etc.) é criada e mantida pelo `Container` (contêiner), o que significa que a maior parte do código de negócio que você escreve está sob o gerenciamento do `Container` (contêiner). Isso também significa que, na maioria dos casos, você pode obter o objeto de contêiner `Hyperf\Di\Container` declarando `Psr\Container\ContainerInterface` no `Constructor` (construtor) ou injetando a interface `Psr\Container\ContainerInterface` com a anotação `#[Inject]`. Vamos demonstrar com código:

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Psr\Container\ContainerInterface;

class IndexController
{
    private ContainerInterface $container;
    
    // A injeção automática é feita declarando o tipo do parâmetro no construtor
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
```   

Em alguns casos mais extremos e dinâmicos, ou quando não estiver sob o gerenciamento do `Container` (contêiner), se quiser obter o objeto `Container` (contêiner), você também pode usar o método `\Hyperf\Context\ApplicationContext::getContainer()` para obter o objeto `Container` (contêiner).

```php
$container = \Hyperf\Context\ApplicationContext::getContainer();
```

## Adaptador de escaneamento

Por padrão, usa-se `Hyperf\Di\ScanHandler\PcntlScanHandler`.

- Hyperf\Di\ScanHandler\PcntlScanHandler

Usa fork de subprocesso via Pcntl para escanear as anotações; suporta apenas ambiente Linux

- Hyperf\Di\ScanHandler\NullScanHandler

Não realiza a operação de escaneamento de anotações

- Hyperf\Di\ScanHandler\ProcScanHandler

Usa proc_open para criar um subprocesso e escanear as anotações; suporta Linux e Windows (Swow)

### Trocar o adaptador de escaneamento

Basta modificar ativamente o trecho de código `Hyperf\Di\ClassLoader::init()` no arquivo `bin/hyperf.php` para trocar o adaptador.

```php
Hyperf\Di\ClassLoader::init(handler: new Hyperf\Di\ScanHandler\ProcScanHandler());
```

## Observações

### O contêiner gerencia apenas objetos de ciclo de vida longo

Em outras palavras, os objetos gerenciados no contêiner são **todos singletons**. Esse design é mais eficiente para aplicações de ciclo de vida longo, reduzindo uma grande quantidade de criação e destruição de objetos sem sentido. Esse design também significa que todos os objetos que precisam ser gerenciados pelo contêiner DI **não podem conter** valores de `estado`.   
`Estado` pode ser entendido diretamente como valores que mudam conforme a requisição. Na verdade, na programação com [corrotinas](coroutine.md), esses valores de estado também devem ser armazenados no `contexto da corrotina`, ou seja, em `Hyperf\Context\Context`.

### Ordem de sobrescrita da injeção com #[Inject]

A ordem de sobrescrita de `#[Inject]` é: subclasse sobrescreve `Trait`, que sobrescreve a classe pai. Ou seja, a variável `foo` de `Origin` abaixo é o `Foo1` injetado nela mesma.

Da mesma forma, se `Origin` não tiver a variável `$foo`, `$foo` será injetada pelo primeiro `Trait`, com a classe injetada `Foo2`.

```php
use Hyperf\Di\Annotation\Inject;

class ParentClass
{
    /**
     * @var Foo4 
     */
    #[Inject]
    protected $foo;
}

trait Foo1
{
    /**
     * @var Foo2 
     */
    #[Inject]
    protected $foo;
}

trait Foo2
{
    /**
     * @var Foo3
     */
    #[Inject]
    protected $foo;
}

class Origin extends ParentClass
{
    use Foo1;
    use Foo2;

    /**
     * @var Foo1
     */
    #[Inject]
    protected $foo;
}
```
