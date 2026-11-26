# Guia de início rápido

Para que você entenda mais rapidamente o uso do `Hyperf`, este capítulo usará a `criação de um HTTP Server` como exemplo, definindo rotas e controladores para implementar um serviço `Web` simples. Mas o `Hyperf` vai além disso: governança de serviços completa, serviços `gRPC`, anotações, `AOP` e outros recursos serão abordados em capítulos específicos.

## Definir rotas de acesso

O Hyperf usa [nikic/fast-route](https://github.com/nikic/FastRoute) como componente de rota padrão e fornece o serviço; você pode definir suas rotas em `config/routes.php` de forma muito prática.   
Além disso, o framework também oferece o recurso extremamente poderoso, prático e flexível de `rotas por anotação`. Para detalhes sobre rotas, consulte o capítulo [Rotas](router.md).

### Definir rotas por arquivo de configuração

O arquivo de rotas fica em `config/routes.php` do projeto [hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton). Abaixo estão alguns exemplos de usos comuns.

```php
<?php
use Hyperf\HttpServer\Router\Router;

// Os exemplos de código abaixo fornecem três formas diferentes de definição de vinculação para cada exemplo; na configuração real, use apenas uma e defina a mesma rota apenas uma vez

// Define uma rota para requisição GET, vinculando o endereço de acesso '/get' ao método get de App\Controller\IndexController
Router::get('/get', 'App\Controller\IndexController::get');
Router::get('/get', 'App\Controller\IndexController@get');
Router::get('/get', [\App\Controller\IndexController::class, 'get']);

// Define uma rota para requisição POST, vinculando o endereço de acesso '/post' ao método post de App\Controller\IndexController
Router::post('/post', 'App\Controller\IndexController::post');
Router::post('/post', 'App\Controller\IndexController@post');
Router::post('/post', [\App\Controller\IndexController::class, 'post']);

// Define uma rota que permite requisições GET, POST e HEAD, vinculando o endereço de acesso '/multi' ao método multi de App\Controller\IndexController
Router::addRoute(['GET', 'POST', 'HEAD'], '/multi', 'App\Controller\IndexController::multi');
Router::addRoute(['GET', 'POST', 'HEAD'], '/multi', 'App\Controller\IndexController@multi');
Router::addRoute(['GET', 'POST', 'HEAD'], '/multi', [\App\Controller\IndexController::class, 'multi']);
```

### Definir rotas por anotações

O `Hyperf` oferece o recurso de [anotações](annotation.md) extremamente poderoso, prático e flexível e, sem dúvida, também fornece uma forma de definir rotas por anotação. O Hyperf fornece as anotações `#[Controller]` e `#[AutoController]` para definir um `Controller`. Aqui faremos apenas uma explicação simples; para mais detalhes, consulte o capítulo [Rotas](router.md).

### Definir rotas com a anotação `#[AutoController]`

`#[AutoController]` fornece suporte à vinculação de rotas para a grande maioria dos cenários simples de acesso. Ao usar `#[AutoController]`, o Hyperf analisará automaticamente todos os métodos `public` da classe e fornecerá os métodos de requisição `GET` e `POST`.

> Ao usar a anotação `#[AutoController]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\AutoController;`;

Controladores com nomes em camelCase são automaticamente convertidos em rotas em snake_case. Abaixo está um exemplo da correspondência entre controlador e rota real:

|      Controlador      |             Anotação             |    Rota de acesso    |
| :--------------: | :-----------------------------: | :------------: |
| MyDataController |        @AutoController()        | /my_data/index |
| MydataController |        @AutoController()        | /mydata/index  |
| MyDataController | @AutoController(prefix="/data") |  /data/index   |

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    // O Hyperf gerará automaticamente uma rota /index/index para este método, permitindo requisições via GET ou POST
    public function index(RequestInterface $request)
    {
        // Obtém o parâmetro id da requisição
        $id = $request->input('id', 1);
        return (string)$id;
    }
}
```

### Definir rotas com a anotação `#[Controller]`
`#[Controller]` existe para atender a necessidades mais detalhadas de definição de rotas. A anotação `#[Controller]` indica que a classe atual é uma `classe Controller` e deve ser usada em conjunto com a anotação `#[RequestMapping]` para definir com mais detalhes o método e o caminho da requisição.   
Também fornecemos várias `anotações Mapping` rápidas e práticas, como `#[GetMapping]`, `#[PostMapping]`, `#[PutMapping]`, `#[PatchMapping]`, `#[DeleteMapping]`, 5 anotações práticas para indicar os diferentes métodos de requisição permitidos.

> Ao usar a anotação `#[Controller]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\Controller;`;   
> Ao usar a anotação `#[RequestMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\RequestMapping;`;   
> Ao usar a anotação `#[GetMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\GetMapping;`;   
> Ao usar a anotação `#[PostMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\PostMapping;`;   
> Ao usar a anotação `#[PutMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\PutMapping;`;   
> Ao usar a anotação `#[PatchMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\PatchMapping;`;   
> Ao usar a anotação `#[DeleteMapping]`, é necessário o namespace `use Hyperf\HttpServer\Annotation\DeleteMapping;`;  

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class IndexController
{
    // O Hyperf gerará automaticamente uma rota /index/index para este método, permitindo requisições via GET ou POST
    #[RequestMapping(path: "index", methods: "get,post")]
    public function index(RequestInterface $request)
    {
        // Obtém o parâmetro id da requisição
        $id = $request->input('id', 1);
        return (string)$id;
    }
}
```

## Processar requisições HTTP

O `Hyperf` é completamente aberto; essencialmente, não determina que você deva implementar o processamento de requisições com base em um determinado padrão. Você pode usar o tradicional `padrão MVC` ou o `padrão RequestHandler` para desenvolver.   
Vamos usar o `padrão MVC` como exemplo:   
Crie uma pasta `Controller` dentro da pasta `app` e crie `IndexController.php` como abaixo. O método `index` obtém o parâmetro `id` da requisição e o converte para o tipo `string` antes de retornar ao cliente.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    // O Hyperf gerará automaticamente uma rota /index/index para este método, permitindo requisições via GET ou POST
    public function index(RequestInterface $request)
    {
        // Obtém o parâmetro id da requisição
        $id = $request->input('id', 1);
        // Converte $id para o formato string e retorna o valor de $id ao cliente com Content-Type plain/text
        return (string)$id;
    }
}
```

## Injeção automática de dependências

A injeção automática de dependências é um recurso muito poderoso fornecido pelo `Hyperf` e também a base para manter a flexibilidade do framework.   
O `Hyperf` fornece duas formas de injeção: uma é a comum injeção via construtor, e a outra é a injeção via anotação `#[Inject]`. Vejamos um exemplo e mostremos a implementação da injeção de duas formas;   
Suponha que exista uma classe `\App\Service\UserService`, com um método `getInfoById(int $id)` que recebe um `id` e retorna uma entidade de usuário. Como o valor de retorno não é o foco aqui, não entraremos em detalhes. O que nos interessa é obter `UserService` em qualquer classe e chamar seus métodos. A forma comum é instanciar a classe de serviço com `new UserService()`, mas no `Hyperf` temos uma solução melhor.

### Injeção via construtor
Basta declarar o tipo do parâmetro no construtor; o `Hyperf` injetará automaticamente o objeto ou valor correspondente.
```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use App\Service\UserService;

#[AutoController]
class IndexController
{
    private UserService $userService;
    
    // Declare o tipo do parâmetro no construtor; o Hyperf injetará automaticamente o objeto ou valor correspondente
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    // /index/info
    public function info(RequestInterface $request)
    {
        $id = $request->input('id', 1);
        return $this->userService->getInfoById((int)$id);
    }
}
```

### Injeção via anotação `#[Inject]`

Basta declarar o tipo do parâmetro com `@var` na propriedade da classe correspondente e marcar a propriedade com a anotação `#[Inject]`; o `Hyperf` injetará automaticamente o objeto ou valor correspondente.

> Ao usar a anotação `#[Inject]`, é necessário o namespace `use Hyperf\Di\Annotation\Inject;`;

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Service\UserService;

#[AutoController]
class IndexController
{

    #[Inject]
    private UserService $userService;
    
    // /index/info
    public function info(RequestInterface $request)
    {
        $id = $request->input('id', 1);
        return $this->userService->getInfoById((int)$id);
    }
}
```
   
Com os exemplos acima, é fácil perceber que `$userService` foi injetado automaticamente como o objeto da classe correspondente à propriedade, mesmo sem ter sido instanciado.   
Porém, o caso aqui ainda não demonstra verdadeiramente as vantagens e o poder da injeção automática de dependências. Suponha que `UserService` também tenha muitas dependências, e que essas dependências tenham, por sua vez, muitas outras dependências; a forma de instanciação com `new` exigiria instanciar manualmente muitos objetos e ajustar as posições corretas dos parâmetros. No `Hyperf`, não precisamos gerenciar manualmente essas dependências; basta declarar a classe que será usada no final.   
E quando `UserService` precisar passar por mudanças internas drásticas, como substituição, por exemplo, de um serviço local para um serviço remoto RPC, basta ajustar a configuração para que a classe correspondente à chave `UserService` nas dependências seja a nova classe de serviço RPC.

## Iniciar o serviço Hyperf

Como o `Hyperf` possui um servidor de corrotinas integrado, isso significa que o `Hyperf` será executado na forma de `CLI`. Portanto, após definir as rotas e o código lógico real, precisamos, no diretório raiz do projeto, executar pela linha de comando `php bin/hyperf.php start` para iniciar o serviço.   
Quando a interface `Console` exibir que o serviço foi iniciado, será possível acessá-lo normalmente via `cURL` ou navegador. Por padrão, o serviço oferecerá uma página inicial em `http://127.0.0.1:9501/`. No contexto do exemplo deste capítulo, o endereço de acesso correspondente ao exemplo acima é `http://127.0.0.1:9501/index/info?id=1`.

## Recarregar o código

Como o `Hyperf` é uma aplicação `CLI` persistente, isso significa que, uma vez iniciado o processo, o código `PHP` já analisado permanecerá persistido no processo. Ou seja, após iniciar o serviço, o código `PHP` que você modificar não alterará o serviço já iniciado. Se você deseja que o serviço recarregue o código modificado, precisa encerrar o serviço digitando `CTRL + C` no `Console` de inicialização e executar novamente o comando de inicialização `php bin/hyperf.php start` para concluir a inicialização e o recarregamento.

> Dica: você também pode configurar o comando de inicialização do Server na IDE e, assim, concluir rapidamente as operações de `iniciar serviço` ou `reiniciar serviço` diretamente pelas ações `iniciar/parar` da IDE.
> E, quando não estiver desenvolvendo views, você pode usar [TDD (Test-Driven Development)](https://baike.baidu.com/item/TDD/9064369), desenvolvimento orientado a testes, para desenvolver. Isso não só evita o incômodo de reiniciar o serviço e alternar janelas com frequência, como também garante a correção dos dados da interface.

> Além disso, no capítulo [Biblioteca de componentes de corrotina](awesome-components.md#hot-updatehot-reload) da documentação, são oferecidas várias soluções de hot update/hot reload suportadas por desenvolvedores da comunidade. Se você ainda deseja adotar uma solução de hot update/hot reload, pode se aprofundar mais.

## Escuta em múltiplas portas

O `Hyperf` suporta escutar em várias portas, mas, como os objetos em `callbacks` são obtidos diretamente do contêiner, o mesmo `Hyperf\HttpServer\Server::class` será sobrescrito no contêiner. Por isso, precisamos redefinir o `Server` nas relações de dependência para garantir o isolamento dos objetos.

> O mesmo vale para Servers como WebSocket e TCP.

`config/autoload/dependencies.php`

```php
<?php

return [
    'InnerHttp' => Hyperf\HttpServer\Server::class,
];
```

`config/autoload/server.php`

```php
<?php
return [
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
        [
            'name' => 'innerHttp',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => ['InnerHttp', 'onRequest'],
            ],
        ],
    ]
];
```

Além disso, o `arquivo de rotas` ou as `anotações` também precisam especificar o `server` correspondente, como abaixo:

- Arquivo de rotas `config/routes.php`

```php
<?php
Router::addServer('innerHttp', function () {
    Router::get('/', 'App\Controller\IndexController@index');
});
```

- Anotações

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController(server: "innerHttp")]
class IndexController
{
    public function index()
    {
        return 'Hello World.';
    }
}

```


## Eventos

Além do evento `Event::ON_REQUEST` mencionado acima, o framework também suporta outros eventos. Todos os nomes de eventos são os seguintes.

|         Nome do evento         |               Observação                |
| :---------------------: | :-------------------------------: |
|    Event::ON_REQUEST    |                                   |
|     Event::ON_START     | Este evento não é válido no modo `SWOOLE_BASE` |
| Event::ON_WORKER_START  |                                   |
|  Event::ON_WORKER_EXIT  |                                   |
| Event::ON_PIPE_MESSAGE  |                                   |
|    Event::ON_RECEIVE    |                                   |
|    Event::ON_CONNECT    |                                   |
|  Event::ON_HAND_SHAKE   |                                   |
|     Event::ON_OPEN      |                                   |
|    Event::ON_MESSAGE    |                                   |
|     Event::ON_CLOSE     |                                   |
|     Event::ON_TASK      |                                   |
|    Event::ON_FINISH     |                                   |
|   Event::ON_SHUTDOWN    |                                   |
|    Event::ON_PACKET     |                                   |
| Event::ON_MANAGER_START |                                   |
| Event::ON_MANAGER_STOP  |                                   |
