# View

O componente de view é implementado e fornecido por [hyperf/view](https://github.com/hyperf/view), atendendo às suas necessidades de renderização de views. O componente suporta por padrão cinco engines de template: `Blade`, `Smarty`, `Twig`, `Plates` e `ThinkTemplate`.

## Instalação

```bash
composer require hyperf/view
```

## Configuração

O arquivo de configuração do componente View está localizado em `config/autoload/view.php`. Se o arquivo de configuração não existir, execute o seguinte comando para gerá-lo

```bash
php bin/hyperf.php vendor:publish hyperf/view
```

A seguir, a explicação das configurações relacionadas:

|       Configuração        |  Tipo  |                Valor padrão                 |       Observação       |
|:-----------------:|:------:|:-------------------------------------:|:----------------:|
|      engine       | string | Hyperf\View\Engine\BladeEngine::class |   Engine de renderização de view   |
|       mode        | string |              Mode::TASK               |   Modo de renderização de view   |
| config.view_path  | string |                  Nenhum                   | Endereço padrão dos arquivos de view |
| config.cache_path | string |                  Nenhum                   | Endereço de cache dos arquivos de view |

Exemplo de formato do arquivo de configuração:

```php
<?php
declare(strict_types=1);

use Hyperf\View\Mode;
use Hyperf\View\Engine\BladeEngine;

return [
    // Engine de renderização utilizada
    'engine' => BladeEngine::class,
    // Se não for preenchido, o padrão é o modo Task; recomenda-se usar o modo Task
    'mode' => Mode::TASK,
    'config' => [
        // Se as pastas abaixo não existirem, crie-as você mesmo
        'view_path' => BASE_PATH . '/storage/view/',
        'cache_path' => BASE_PATH . '/runtime/view/',
    ],
];
```

### Modo Task

Ao usar o modo `Task`, é necessário introduzir o componente [hyperf/task](https://github.com/hyperf/task) e configurar `task_enable_coroutine` como `false`, caso contrário ocorrerá um problema de confusão de dados de corrotina. Para mais informações, consulte a documentação do componente [Task](task.md).

Além disso, no modo `Task`, o trabalho de renderização da view é concluído no processo `Task Worker`, enquanto o processamento da requisição, ou seja, o Controller, é concluído no processo `Worker`. As duas partes do trabalho são realizadas por processos diferentes, portanto objetos ou dados gerenciados por contexto no processo `Worker`, como `Request`, `Session` etc., não podem ser usados diretamente na página de view. Nesse caso, você precisa primeiro processar os dados ou o resultado da decisão no Controller e, em seguida, ao chamar `render`, passar os dados para a view para a renderização dos dados.

### Modo Sync

Se você usar o modo `Sync` para renderizar a view, certifique-se de que a engine relacionada seja segura para corrotinas, caso contrário ocorrerá um problema de confusão de dados. Recomenda-se usar o modo `Task`, que é mais seguro em termos de dados.

### Configurando recursos estáticos

Se você deseja que o `Swoole` gerencie os recursos estáticos, adicione as seguintes configurações em `config/autoload/server.php`.

```
return [
    'settings' => [
        ...
        // Recursos estáticos
        'document_root' => BASE_PATH . '/public',
        'enable_static_handler' => true,
    ],
];

```

## Engine de renderização de view

Atualmente, o suporte oficial abrange cinco templates: `Blade`, `Smarty`, `Twig`, `Plates` e `ThinkTemplate`. Ao instalar [hyperf/view](https://github.com/hyperf/view), nenhuma engine de template é instalada automaticamente; você precisa instalar a engine de template correspondente de acordo com suas necessidades. Antes de usar, é obrigatório instalar alguma engine de template.

### Instalando a engine Blade

```bash
composer require hyperf/view-engine
```

Para detalhes, consulte a documentação [Engine de view](view-engine.md)

Ou use

> duncan3dc/blade usa a biblioteca Support do Laravel, o que causa incompatibilidade com algumas funções; por enquanto não é recomendado

```bash
composer require duncan3dc/blade
```

### Instalando a engine Smarty

```bash
composer require smarty/smarty
```

### Instalando a engine Twig

```bash
composer require twig/twig
```

### Instalando a engine Plates

```bash
composer require league/plates
```

### Instalando a engine ThinkTemplate

```bash
composer require sy-records/think-template
```

### Integrando outros templates

Suponha que queiramos integrar uma engine de template fictícia chamada `TemplateEngine`; então precisamos criar a classe `TemplateEngine` correspondente em qualquer lugar e implementar a interface `Hyperf\View\Engine\EngineInterface`.

```php
<?php

declare(strict_types=1);

namespace App\Engine;

use Hyperf\View\Engine\EngineInterface;

class TemplateEngine implements EngineInterface
{
    public function render($template, $data, $config): string
    {
        // Instancia a instância correspondente da engine de template
        $engine = new TemplateInstance();
        // E chama o método de renderização correspondente
        return $engine->render($template, $data);
    }
}

```

Depois, modifique a configuração do componente de view:

```php
<?php

use App\Engine\TemplateEngine;

return [
    // Altere o parâmetro engine para a sua classe de engine de template personalizada
    'engine' => TemplateEngine::class,
    'mode' => Mode::TASK,
    'config' => [
        'view_path' => BASE_PATH . '/storage/view/',
        'cache_path' => BASE_PATH . '/runtime/view/',
    ],
];
```

## Uso

A seguir, usando `BladeEngine` como exemplo, primeiro crie o arquivo de view `index.blade.php` no diretório correspondente.

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hyperf</title>
</head>
<body>
Hello, {{ $name }}. You are using blade template now.
</body>
</html>
```

No controller, obtenha a instância de `Hyperf\View\Render` e chame o método `render`, passando o endereço do arquivo de view `index` e os `dados de renderização`. O endereço do arquivo ignora a extensão do arquivo de view.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\View\RenderInterface;

#[AutoController]
class ViewController
{
    public function index(RenderInterface $render)
    {
        return $render->render('index', ['name' => 'Hyperf']);
    }
}

```

Acesse a URL correspondente e você obterá a página de view mostrada abaixo:

```
Hello, Hyperf. You are using blade template now.
```
