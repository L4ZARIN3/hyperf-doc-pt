# Mecanismo de views

> Reescrito com base no mecanismo de templates Laravel blade, com suporte à sintaxe original do mecanismo de templates blade.

```bash
composer require hyperf/view-engine
```

## Gerar configuração

```bash
php bin/hyperf.php vendor:publish hyperf/view-engine
```

A configuração padrão é a seguinte

> Este componente recomenda o modo de renderização SYNC, que pode reduzir efetivamente a perda de comunicação entre processos

```php
return [
    'engine' => Hyperf\ViewEngine\HyperfViewEngine::class,
    'mode' => Hyperf\View\Mode::SYNC,
    'config' => [
        'view_path' => BASE_PATH . '/storage/view/',
        'cache_path' => BASE_PATH . '/runtime/view/',
    ],

    # Registro de componentes personalizados
    'components' => [
        // 'alert' => \App\View\Components\Alert::class
    ],

    # Namespace de views (usado principalmente em pacotes de extensão)
    'namespaces' => [
        // 'admin' => BASE_PATH . '/storage/view/vendor/admin',
    ],
];
```

## Uso

> Este tutorial de uso foi amplamente baseado em [LearnKu](https://learnku.com); agradecemos imensamente a contribuição do LearnKu para a comunidade PHP.

### Introdução

`Blade` é um mecanismo de templates simples e poderoso fornecido pelo `Laravel`. Diferente de outros mecanismos de templates `PHP` populares, o `Blade` não restringe o uso de código `PHP` nativo nas views.
Todos os arquivos de view `Blade` serão compilados em código `PHP` nativo e armazenados em cache; a menos que sejam modificados, não serão recompilados, o que significa que o `Blade` basicamente não adiciona nenhuma sobrecarga à sua aplicação.
Os arquivos de view `Blade` usam `.blade.php` como extensão de arquivo e, por padrão, ficam armazenados no diretório `storage/view`.

### Herança de templates

#### Definir um layout

Primeiro, vamos analisar um layout de página «principal». Como a maioria das aplicações `web` usa o mesmo tipo de layout em páginas diferentes, é muito conveniente definir uma única view de layout `Blade`:

```blade
<!-- Stored in storage/view/layouts/app.blade.php -->

<html>
    <head>
        <title>App Name - @yield('title')</title>
    </head>
    <body>
        @section('sidebar')
            This is the master sidebar.
        @show

        <div class="container">
            @yield('content')
        </div>
    </body>
</html>
```

Você pode ver que este trecho contém `HTML` comum. Mas observe as diretivas `@section` e `@yield`. Como o próprio nome `section` sugere, uma seção: a diretiva `@section` define o conteúdo da seção e a diretiva `@yield` exibe o conteúdo da seção.

Agora que já definimos o layout desta aplicação, vamos definir uma página filha que herda esse layout.

#### Herança de layout

Ao definir uma view filha, use a diretiva `@extends` do `Blade` para especificar a view que a view filha deve «herdar». Views que estendem um layout `Blade` podem injetar conteúdo nas seções do layout usando a diretiva `@section`.
Como mostrado no exemplo anterior, o conteúdo dessas seções é exibido conforme controlado pela diretiva `@yield` no layout:

```blade
<!-- Stored in storage/view/child.blade.php -->

@extends('layouts.app')

@section('title', 'Page Title')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')
    <p>This is my body content.</p>
@endsection
```

Neste exemplo, a seção `sidebar` usa a diretiva `@parent` para acrescentar (em vez de sobrescrever) conteúdo à seção `sidebar` do layout. Ao renderizar a view, a diretiva `@parent` será substituída pelo conteúdo do layout.

> Ao contrário do exemplo anterior, aqui a seção sidebar termina com @endsection em vez de @show. A diretiva @endsection apenas define uma seção, enquanto @show define e imediatamente faz o yield dessa seção.

A diretiva `@yield` também aceita um valor padrão como segundo parâmetro. Se a seção «yielded» não estiver definida, esse valor padrão será renderizado:

```blade
@yield('content', 'Hyperf')
```

As views `Blade` podem ser retornadas com a função auxiliar `Hyperf\ViewEngine\view`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use function Hyperf\ViewEngine\view;

#[AutoController(prefix: "view")]
class ViewController extends AbstractController
{
    public function child()
    {
        return (string) view('child');
    }
}

```

### Exibir dados

Você pode colocar variáveis entre chaves para exibir dados na view. Por exemplo, dada a rota abaixo:

```php
use Hyperf\HttpServer\Router\Router;
use function Hyperf\ViewEngine\view;

Router::get('greeting', function () {
    return view('welcome', ['name' => 'Samantha']);
});
```

Você pode exibir o conteúdo da variável `name` da seguinte forma:

```blade
Hello, {{ $name }}.
```

> As instruções {{ }} do Blade são automaticamente escapadas pela função htmlspecialchars do PHP para prevenir ataques XSS.

Além de exibir o conteúdo das variáveis passadas à view, você também pode exibir o resultado de qualquer função `PHP`. Na verdade, você pode colocar qualquer código `PHP` nas instruções de eco do template `Blade`:

```blade
The current UNIX timestamp is {{ time() }}.
```

#### Exibir caracteres não escapados

Por padrão, as instruções `Blade {{ }}` são automaticamente escapadas pela função `htmlspecialchars` do `PHP` para prevenir ataques `XSS`. Se você não quiser que seus dados sejam escapados, pode usar a seguinte sintaxe:

```blade
Hello, {!! $name !!}.
```

> Tenha muito cuidado ao exibir dados fornecidos pelo usuário na aplicação; use sempre que possível a sintaxe de escape e de aspas duplas para prevenir ataques XSS.

#### Renderizar JSON

Às vezes, para inicializar uma variável `JavaScript`, você pode passar um array para a view e renderizá-lo como `JSON`. Por exemplo:

```
<script>
    var app = <?php echo json_encode($array); ?>;
</script>
```

É claro que você também pode usar a diretiva `Blade` `@json` em vez de chamar manualmente o método `json_encode`. Os parâmetros da diretiva `@json` são iguais aos da função `json_encode` do `PHP`:

```html
<script>
    var app = @json($array);

    var app = @json($array, JSON_PRETTY_PRINT);
</script>
```

> Ao usar a diretiva @json, você deve renderizar como JSON apenas variáveis já existentes. Os templates Blade são baseados em expressões regulares; tentar passar uma expressão complexa para a diretiva @json pode causar erros imprevisíveis.

#### Codificação de entidades HTML

Por padrão, o `Blade` codifica entidades `HTML` duas vezes. Se você quiser desabilitar esse comportamento, pode escutar o evento `BootApplication` e chamar o método `Blade::withoutDoubleEncoding`:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\ViewEngine\Blade;
use Psr\Container\ContainerInterface;

#[Listener]
class BladeWithoutDoubleEncodingListener implements ListenerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            BootApplication::class
        ];
    }

    public function process(object $event)
    {
        Blade::withoutDoubleEncoding();
    }
}

```

#### Blade e frameworks JavaScript

Como muitos frameworks `JavaScript` também usam «chaves» para identificar expressões que serão exibidas no navegador, você pode usar o símbolo `@` para indicar que o mecanismo de renderização `Blade` deve manter o conteúdo inalterado. Por exemplo:

```blade
<h1>Laravel</h1>

Hello, @{{ name }}.
```

Neste exemplo, o símbolo `@` será removido pelo `Blade`; é claro que o `Blade` não modificará a expressão `{{ name }}`, que será renderizada pelo template `JavaScript`.
O símbolo `@` também é usado para escapar diretivas `Blade`:

```
{{-- Blade --}}
@@json()

<!-- Saída HTML -->
@json()
```

Se você exibir uma grande parte de variáveis `JavaScript` no template, pode incorporar o `HTML` na diretiva `@verbatim`; assim, você não precisa adicionar o símbolo `@` antes de cada instrução de eco do `Blade`:

```blade
@verbatim
    <div class="container">
        Hello, {{ name }}.
    </div>
@endverbatim
```

### Controle de fluxo

Além da herança de templates e da exibição de dados, o `Blade` também fornece atalhos convenientes para estruturas de controle comuns do `PHP`, como instruções condicionais e laços. Esses atalhos oferecem uma forma muito clara e concisa de escrever as estruturas de controle do `PHP` e, ao mesmo tempo, mantêm características de sintaxe semelhantes às das estruturas de controle do `PHP`.

#### Instruções If

Você pode construir instruções `if` com as diretivas `@if`, `@elseif`, `@else` e `@endif`. Essas diretivas funcionam exatamente como as instruções `PHP` correspondentes:

```blade
@if (count($records) === 1)
    I have one record!
@elseif (count($records) > 1)
    I have multiple records!
@else
    I don't have any records!
@endif
```

Por conveniência, o `Blade` também fornece a diretiva `@unless`:

```blade
@unless (is_signed_in())
    You are not signed in.
@endunless
```

Além das diretivas condicionais já discutidas, as diretivas `@isset` e `@empty` também servem como atalhos para as funções `PHP` correspondentes:

```blade
@isset($records)
    // $records está definido e não está vazio
@endisset

@empty($records)
    // $records está vazio……
@endempty
```

#### Diretivas de bloco

Você pode usar a diretiva `@hasSection` para determinar se um bloco tem conteúdo:

```blade
@hasSection('navigation')
    <div class="pull-right">
        @yield('navigation')
    </div>

    <div class="clearfix"></div>
@endif
```

Você pode usar a diretiva `@sectionMissing` para determinar se um bloco não tem conteúdo:

```blade
@sectionMissing('navigation')
    <div class="pull-right">
        @include('default-navigation')
    </div>
@endif
```

#### Diretivas de ambiente

Você pode usar a diretiva `@production` para determinar se a aplicação está em ambiente de produção:

```blade
@production
    // Conteúdo específico do ambiente de produção……
@endproduction
```

Ou você pode usar a diretiva `@env` para determinar se a aplicação está em execução no ambiente especificado:

```blade
@env('staging')
    // A aplicação está no ambiente「staging」……
@endenv

@env(['staging', 'production'])
    // A aplicação está no ambiente「staging」ou em produção……
@endenv
```

#### Instruções Switch

Você pode usar as instruções `@switch`, `@case`, `@break`, `@default` e `@endswitch` para construir instruções `Switch`:

```blade
@switch($i)
    @case(1)
        First case...
        @break

    @case(2)
        Second case...
        @break

    @default
        Default case...
@endswitch
```

#### Laços

Além das instruções condicionais, o `Blade` também fornece diretivas com a mesma funcionalidade das estruturas de laço do `PHP`. Da mesma forma, a funcionalidade dessas instruções é idêntica à da sintaxe `PHP` correspondente:

```blade
@for ($i = 0; $i < 10; $i++)
    The current value is {{ $i }}
@endfor

@foreach ($users as $user)
    <p>This is user {{ $user->id }}</p>
@endforeach

@forelse ($users as $user)
    <li>{{ $user->name }}</li>
@empty
    <p>No users</p>
@endforelse

@while (true)
    <p>I'm looping forever.</p>
@endwhile
```

> Durante o laço, você pode usar a variável de loop para obter informações valiosas sobre o loop, por exemplo, se você está na primeira ou na última iteração do laço.

Ao usar laços, você pode encerrar o laço ou pular a iteração atual:

```blade
@foreach ($users as $user)
    @if ($user->type == 1)
        @continue
    @endif

    <li>{{ $user->name }}</li>

    @if ($user->number == 5)
        @break
    @endif
@endforeach
```

Você pode declarar uma condição em uma linha separada da diretiva:

```blade
@foreach ($users as $user)
    @continue($user->type == 1)

    <li>{{ $user->name }}</li>

    @break($user->number == 5)
@endforeach
```

#### Variável Loop

Durante o laço, a variável `$loop` pode ser usada dentro do loop. Essa variável fornece acesso a informações como o índice atual do loop e se esta iteração é a primeira ou a última:

```blade
@foreach ($users as $user)
    @if ($loop->first)
        This is the first iteration.
    @endif

    @if ($loop->last)
        This is the last iteration.
    @endif

    <p>This is user {{ $user->id }}</p>
@endforeach
```

Se você estiver em um loop aninhado, pode usar a propriedade `parent` da variável `$loop` do loop para acessar o loop pai:

```blade
@foreach ($users as $user)
    @foreach ($user->posts as $post)
        @if ($loop->parent->first)
            This is first iteration of the parent loop.
        @endif
    @endforeach
@endforeach
```

A variável `$loop` também contém diversas propriedades úteis:

| Propriedade | Observação |
|:--:|:--:|
| `$loop->index` | O índice da iteração atual (começa em 0).|
| `$loop->iteration` | O número de iterações do loop atual (começa em 1).|
| `$loop->remaining` | O número de iterações restantes no loop.|
| `$loop->count` | O número de elementos do array iterado.|
| `$loop->first` | Se a iteração atual é a primeira do loop.|
| `$loop->last` | Se a iteração atual é a última do loop.|
| `$loop->even` | Se o número de iterações do loop atual é par.|
| `$loop->odd` | Se o número de iterações do loop atual é ímpar.|
| `$loop->depth` | A profundidade de aninhamento do loop atual.|
| `$loop->parent` | O loop pai em loops aninhados.|

#### Comentários

O `Blade` também permite definir comentários nas views. Porém, diferente dos comentários `HTML`, os comentários `Blade` não são incluídos no `HTML` retornado pela aplicação:

```blade
{{-- This comment will not be present in the rendered HTML --}}
```

#### PHP

Em muitos casos, é útil incorporar código `PHP` nas suas views. Você pode executar blocos de código `PHP` nativo no template usando a diretiva `@php` do `Blade`:

```blade
@php
    //
@endphp
```

> Embora o Blade forneça esse recurso, usá-lo com frequência pode incorporar lógica demais ao seu template.
#### Diretiva @once

A diretiva `@once` permite definir uma parte do conteúdo do template que será calculada apenas uma vez em cada ciclo de renderização.
Essa diretiva é útil no contexto de usar `pilhas` (stacks) para enviar um trecho específico de código `JavaScript` ao cabeçalho da página.
Por exemplo, se você quiser renderizar um `componente` específico dentro de um loop, pode querer enviar o código `JavaScript` ao cabeçalho apenas na primeira vez que o componente for renderizado:

```blade
@once
    @push('scripts')
        <script>
            // Seu código JavaScript personalizado
        </script>
    @endpush
@endonce
```

### Componentes e slots

Componentes e slots funcionam de forma semelhante a seções (Section) e layouts (Layout). No entanto, algumas pessoas podem achar os componentes e slots mais convenientes de usar. O Hyperf suporta duas formas de escrever componentes: componentes baseados em classe e componentes anônimos.

Podemos definir um componente de classe criando uma classe que herda de `\Hyperf\ViewEngine\Component\Component::class`. A seguir, mostraremos como usar componentes criando um componente `Alert` simples.

> app/View/Component/Alert.php

```php
<?php
namespace App\View\Component;
use Hyperf\ViewEngine\Component\Component;
use function Hyperf\ViewEngine\view;
class Alert extends Component
{
    public $type;
    public $message;
    public function __construct($type, $message)
    {
        $this->type = $type;
        $this->message = $message;
    }
    public function render()
    {
        return view('components.alert');
    }
}
```

> storage/view/components/alert.blade.php
```html
<div class="alert alert-{{ $type }}">
    {{ $message }}
</div>
```

#### Registrar componentes manualmente

Em `config/autoload/view.php`

```php
<?php
return [
    // ...
    'components' => [
        'alert' => \App\View\Component\Alert::class,
    ],
];
```

Ou no `ConfigProvider` do pacote de extensão

```php
<?php
class ConfigProvider
{
    public function __invoke()
    {
        return [
            // ...others config
            'view' => [
                // ...others config
                'components' => [
                    'package-alert' => \App\View\Component\Alert::class,
                ],
            ],
        ];
    }
}
```

Depois de registrar o componente, você poderá usá-lo por meio do alias da tag HTML:

```html
<x-alert/>
<x-package-alert/>
```

#### Exibir componentes

Você pode exibir um componente em qualquer template Blade usando as tags de componente do Blade. As tags de componente do Blade começam com `x-`, seguidas pelo nome do componente.

```html
<x-alert/>
<x-package-alert/>
```

#### Passar parâmetros para componentes

Você pode passar dados para componentes Blade usando atributos HTML. Valores comuns podem ser passados por meio de atributos HTML simples, enquanto expressões e variáveis PHP devem ser passadas por meio de atributos prefixados com `:`:

```html
<x-alert type="error" :message="$message"/>
```

!> Atenção: você pode definir os dados necessários ao componente no construtor da classe do componente. Todas as propriedades públicas da classe do componente serão automaticamente passadas para a view do componente. Não é necessário passá-las pelo método `render` da classe do componente. Ao renderizar o componente, o conteúdo das propriedades públicas da classe do componente pode ser obtido pelos nomes das variáveis.

#### Métodos de componentes

Além de poder acessar as propriedades públicas da classe do componente, você também pode executar qualquer método público da classe do componente na view do componente. Por exemplo, suponha que um componente tenha um método `isSelected`:

```php
    /**
     * Determina se a opção informada é a opção atual
     *
     * @param  string  $option
     * @return bool
     */
    public function isSelected($option)
    {
        return $option === $this->selected;
    }
```

Você pode executar esse método chamando uma variável com o mesmo nome do método:

```html
    <option {{ $isSelected($value) ? 'selected="selected"' : '' }} value="{{ $value }}">
        {{ $label }}
    </option>
```

#### Dependências adicionais

Se o seu componente precisar depender de outras classes, elas devem ser listadas antes de todas as propriedades de dados do componente; elas serão injetadas automaticamente pelo contêiner:
```php
    use App\AlertCreator;
    /**
     * Cria a instância do componente
     *
     * @param  \App\AlertCreator  $creator
     * @param  string  $type
     * @param  string  $message
     * @return void
     */
    public function __construct(AlertCreator $creator, $type, $message)
    {
        $this->creator = $creator;
        $this->type = $type;
        $this->message = $message;
    }
```

#### Gerenciar atributos

Já vimos como passar propriedades de dados para componentes. No entanto, às vezes podemos precisar especificar outros atributos HTML (como `class`) que não são dados necessários ao componente. Nesse caso, queremos repassar esses atributos para o elemento raiz do template do componente. Por exemplo, queremos renderizar um componente `alert` da seguinte forma:

```html
    <x-alert type="error" :message="$message" class="mt-4"/>
```

Todos os atributos que não pertencem ao construtor do componente serão automaticamente adicionados ao «pacote de atributos» do componente. Esse pacote de atributos será passado para a view do componente por meio da variável `$attributes`. Ao exibir essa variável, todos os atributos podem ser apresentados no componente:

```html
    <div {{ $attributes }}>
        <!-- Conteúdo do componente -->
    </div>
```

#### Obter atributos

Você pode usar o método `get()` para obter o valor de um atributo específico. Esse método aceita o nome do atributo como primeiro parâmetro (o segundo parâmetro é o valor padrão) e retorna o seu valor.

```html
    <div class="{{ $attributes->get("class", "default") }}">
        <!-- Conteúdo do componente -->
    </div>
```

#### Detectar atributos

Você pode usar o método `has()` para obter o valor de um atributo específico. Esse método aceita o nome do atributo como parâmetro e retorna um valor booleano.

```html
    @if($attributes->has("class"))
        <div class="{{ $attributes->get("class") }}">
            <!-- Conteúdo do componente -->
        </div>
    @endif
```

#### Mesclar atributos

Às vezes, você pode precisar especificar valores padrão para atributos ou mesclar outros valores em alguns atributos do componente. Para isso, você pode usar o método `merge` do pacote de atributos:

```html
    <div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
        {{ $message }}
    </div>
```

Suponha que usemos o componente como mostrado abaixo:

```html
    <x-alert type="error" :message="$message" class="mb-4"/>
```

O HTML final renderizado do componente será o seguinte:

```html
    <div class="alert alert-error mb-4">
        <!-- Conteúdo da variável $message -->
    </div>
```

Por padrão, apenas o atributo `class` é mesclado; os outros atributos serão simplesmente sobrescritos, resultando na seguinte situação:

```blade
// Definição
<div {{ $attributes->merge(['class' => 'alert alert-'.$type, 'other-attr' => 'foo']) }}>{{ $message }}</div>
// Uso
<x-alert type="error" :message="$message" class="mb-4" other-attr="bar"/>
// Renderização
<div class="alert alert-error mb-4" other-attr="bar"><!-- Conteúdo da variável $message --></div>
```

No caso acima, se também for necessário mesclar o atributo `other-attr`, você pode usar a forma abaixo, adicionando o segundo parâmetro `true` ao método `merge()`:

```blade
// Definição
<div {{ $attributes->merge(['class' => 'alert alert-'.$type, 'other-attr' => 'foo'], true) }}>{{ $message }}</div>
// Uso
<x-alert type="error" :message="$message" class="mb-4" other-attr="bar"/>
// Renderização
<div class="alert alert-error mb-4" other-attr="foo bar"><!-- Conteúdo da variável $message --></div>
```

#### Slots

Normalmente, você precisa passar conteúdo adicional para o componente por meio de `slots`. Suponha que o componente `alert` que criamos tenha a seguinte marcação:

```html
    <!-- /storage/view/components/alert.blade.php -->

    <div class="alert alert-danger">
        {{ $slot }}
    </div>
```

Podemos passar conteúdo para `slots` injetando conteúdo no componente:

```html
    <x-alert>
        <strong>Whoops!</strong> Something went wrong!
    </x-alert>
```

Às vezes, um componente pode precisar de vários slots diferentes em posições distintas dentro dele. Vamos modificar o componente alert para permitir a injeção de `title`.

```html
    <!-- /storage/view/components/alert.blade.php -->

    <span class="alert-title">{{ $title }}</span>

    <div class="alert alert-danger">
        {{ $slot }}
    </div>
```

Você pode usar a tag `x-slot` para definir o conteúdo de um slot nomeado. Qualquer outro conteúdo que não esteja dentro da tag `x-slot` será passado ao componente na variável `$slot`:

```html
    <x-alert>
        <x-slot name="title">
            Server Error
        </x-slot>

        <strong>Whoops!</strong> Something went wrong!
    </x-alert>
```

#### Componentes inline

Para componentes pequenos, gerenciar a classe do componente e o template da view do componente pode ser trabalhoso. Por isso, você pode retornar o conteúdo do componente a partir do método `render`:

```php
    public function render()
    {
        return <<<'blade'
            <div class="alert alert-danger">
                {{ $slot }}
            </div>
        blade;
    }
```

#### Componentes anônimos

Assim como os componentes inline, os componentes anônimos oferecem um mecanismo para gerenciar o componente por meio de um único arquivo. No entanto, os componentes anônimos usam um único arquivo de view sem classe associada. Para definir um componente anônimo, basta colocar o template Blade no diretório `/storage/view/components`.
Por exemplo, suponha que você definiu um componente em `/storage/view/components/alert.blade.php`:

```html
    <x-alert/>
```

Se o componente estiver em um subdiretório do diretório `components`, você pode usar o caractere `.` para especificar o seu caminho. Por exemplo, suponha que o componente esteja definido em `/storage/view/components/inputs/button.blade.php`; você pode renderizá-lo assim:

```html
    <x-inputs.button/>
```

#### Dados e atributos de componentes anônimos

Como componentes anônimos não têm nenhuma classe associada, você pode querer distinguir quais dados devem ser passados ao componente como variáveis e quais atributos devem ser armazenados no [pacote de atributos](#gerenciar-atributos).

Você pode usar a diretiva `@props` no nível superior do template Blade do componente para especificar quais atributos devem ser tratados como variáveis de dados. Os demais atributos do componente serão fornecidos por meio do pacote de atributos. Se você quiser especificar um valor padrão para uma variável de dados, use o nome do atributo como chave do array e o valor padrão como valor do array:

```blade
    <!-- /storage/view/components/alert.blade.php -->

    @props(['type' => 'info', 'message'])

    <div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
        {{ $message }}
    </div>
```

#### Componentes dinâmicos

Às vezes, você pode precisar renderizar um componente, mas não sabe qual será até o momento da execução. Nesse caso, você pode usar o componente integrado `dynamic-component` para renderizar um componente com base em um valor ou variável:

```html
    <x-dynamic-component :component="$componentName" class="mt-4" />
```

#### Carregamento automático de componentes

Por padrão, os componentes em `App\View\Component\` e em `components.` são registrados automaticamente. Você também pode alterar essa configuração por meio do arquivo de configuração:

> config/autoload/view.php

```php
return [
    // ...
    'autoload' => [
        'classes' => ['App\\Other\\Component\\', 'App\\Another\\Component\\'],
        'components' => ['package::components.', 'components.'],
    ],
];
```

## Namespace de views

Ao definir namespaces de views, é possível usar arquivos de view de forma conveniente no seu pacote de extensão; basta adicionar uma linha de configuração no `ConfigProvider`:

```php
<?php
class ConfigProvider
{
    public function __invoke()
    {
        return [
            // ...others config
            'view' => [
                // ...others config
                'namespaces' => [
                    'package-name' => __DIR__ . '/../views',
                ],
            ],
        ];
    }
}
```

Após instalar o pacote de extensão, você pode sobrescrever as views do pacote de extensão definindo arquivos de view com o mesmo caminho em `/storage/view/vendor/package-name` do projeto.

## Middlewares opcionais

- Hyperf\ViewEngine\Http\Middleware\ShareErrorsFromSession::class

Compartilha automaticamente os `errors` da `session` com a view; depende do componente `hyperf/session`

- Hyperf\ViewEngine\Http\Middleware\ValidationExceptionHandle::class

Captura automaticamente as exceções de `validation` e as adiciona à `session`; depende dos componentes `hyperf/session` e `hyperf/validation`

## Outros comandos

Instala automaticamente as configurações relacionadas aos componentes `view-engine`, `translation` e `validation`

```
php bin/hyperf.php view:publish
```
