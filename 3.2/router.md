# Rota

Por padrão, as rotas são suportadas por [nikic/fast-route](https://github.com/nikic/FastRoute) e integradas ao `Hyperf` pelo componente [hyperf/http-server](https://github.com/hyperf/http-server); as rotas `RPC` são de responsabilidade do componente [hyperf/rpc-server](https://github.com/hyperf/rpc-server) correspondente.

## Rota HTTP

### Definir rotas por arquivo de configuração

No esqueleto [hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton), por padrão todas as definições de rota são feitas no arquivo `config/routes.php`. Naturalmente, se você tiver muitas rotas, também pode estender esse arquivo para atender às suas necessidades, mas o `Hyperf` também suporta `rotas por anotação`, e recomendamos mais o uso de `rotas por anotação`, especialmente quando há muitas rotas.   

#### Definir rotas por closure

Para construir uma rota mais básica, basta um URI e um `closure (Closure)`; vamos demonstrar diretamente pelo código:

```php
<?php
use Hyperf\HttpServer\Router\Router;

Router::get('/hello-hyperf', function () {
    return 'Hello Hyperf.';
});
```

Você pode acessar essa rota solicitando `http://host:port/hello-hyperf` pelo navegador ou pela linha de comando `cURL`.

#### Definir rotas padrão

As chamadas rotas padrão são aquelas tratadas por um `Controlador (Controller)` e uma `Ação (Action)`; se você usar o modo `Manipulador de requisição (Request Handler)` é semelhante. Vamos demonstrar pelo código:

```php
<?php
use Hyperf\HttpServer\Router\Router;

// Qualquer uma das três formas abaixo produz o mesmo efeito
Router::get('/hello-hyperf', 'App\Controller\IndexController::hello');
Router::get('/hello-hyperf', 'App\Controller\IndexController@hello');
Router::get('/hello-hyperf', [App\Controller\IndexController::class, 'hello']);
```

Essa rota define a vinculação do caminho `/hello-hyperf` ao método `hello` de `App\Controller\IndexController`.

#### Métodos de rota disponíveis

O roteador fornece diversos métodos para ajudar você a registrar rotas de qualquer requisição HTTP:

```php
use Hyperf\HttpServer\Router\Router;

// Registra uma rota com o HTTP METHOD igual ao nome do método
Router::get($uri, $callback);
Router::post($uri, $callback);
Router::put($uri, $callback);
Router::patch($uri, $callback);
Router::delete($uri, $callback);
Router::head($uri, $callback);

// Registra uma rota para qualquer HTTP METHOD
Router::addRoute($httpMethod, $uri, $callback);
```

Às vezes você pode precisar registrar uma rota que responda simultaneamente a requisições de vários HTTP METHOD; isso pode ser definido pelo método `addRoute`:

```php
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST','PUT','DELETE'], $uri, $callback);
```

#### Forma de definição de grupos de rotas

As rotas reais são `group/route`, ou seja, `/user/index`, `/user/store`, `/user/update`, `/user/delete` 

```php
Router::addGroup('/user/',function (){
    Router::get('index','App\Controller\UserController@index');
    Router::post('store','App\Controller\UserController@store');
    Router::get('update','App\Controller\UserController@update');
    Router::post('delete','App\Controller\UserController@delete');
});

```

### Definir rotas por anotação

O `Hyperf` oferece a funcionalidade muito conveniente de [rotas por anotação](annotation.md); você pode definir diretamente em qualquer classe as anotações `#[Controller]` ou `#[AutoController]` para concluir a definição de uma rota.

!> As classes de anotação apresentadas a seguir pertencem ao namespace `use Hyperf\HttpServer\Annotation\`, como `Hyperf\HttpServer\Annotation\AutoController`

#### Parâmetros da anotação

Tanto `#[Controller]` quanto `#[AutoController]` fornecem os parâmetros `prefix` e `server`.

`prefix` indica o prefixo das rotas de todos os métodos desse controlador. Por padrão, a parte após `\Controller\` no namespace da classe do controlador é usada como prefixo de rota em notação snake case (SnakeCase),

por exemplo, para `App\Controller\Demo\UserController`, o prefix padrão é `demo/user`; se o path de um método da classe for `index`, a rota final será `/demo/user/index`.

!> Observe que `prefix` nem sempre é válido: quando o path de um método da classe começa com `/`, isso indica que o caminho é definido a partir do início do `URI`, ou seja, o valor de prefix será ignorado

`server` indica em qual `HTTP Server` essa rota é definida. Como o Hyperf suporta iniciar vários `HTTP Server` ao mesmo tempo, ao definir rotas você pode usar esse parâmetro para distinguir para qual `Server` a rota é definida. O padrão é `http`.

|                 Controlador                 |               Anotação               |         Rota de acesso        |
|:------------------------------------:|:-------------------------------:|:------------------:|
|   App\Controller\MyDataController    |        @AutoController()        |   /my_data/index   |
|   App\Controller\MydataController    |        @AutoController()        |   /mydata/index    |
|   App\Controller\MyDataController    | @AutoController(prefix="/data") |    /data/index     |
| App\Controller\Demo\MydataController |        @AutoController()        | /demo/mydata/index |
| App\Controller\Demo\MyDataController | @AutoController(prefix="/data") |    /data/index     |



|                 Controlador                 |                                        Anotação                                         |         Rota de acesso         |
|:------------------------------------:|:---------------------------------------------------------------------------------:|:-------------------:|
|   App\Controller\MyDataController    |        @Controller() + @RequestMapping(path: "index", methods: "get,post")        |   /my_data/index    |
| App\Controller\Demo\MyDataController |        @Controller() + @RequestMapping(path: "index", methods: "get,post")        | /demo/my_data/index |
| App\Controller\Demo\MyDataController | @Controller(prefix="/data") + @RequestMapping(path: "index", methods: "get,post") |     /data/index     |
|   App\Controller\MyDataController    |       @Controller() + @RequestMapping(path: "/index", methods: "get,post")        |       /index        |

#### Anotação `#[AutoController]`

`#[AutoController]` oferece suporte à vinculação de rotas para a grande maioria dos cenários de acesso simples. Ao usar `#[AutoController]`, o `Hyperf` analisará automaticamente todos os métodos `public` da classe e fornecerá os dois modos de requisição `GET` e `POST`.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class UserController
{
    // O Hyperf gerará automaticamente uma rota /user/index para este método, permitindo requisições via GET ou POST
    public function index(RequestInterface $request)
    {
        // Obtém o parâmetro id da requisição
        $id = $request->input('id', 1);
        return (string)$id;
    }
}
```

#### Anotação `#[Controller]`

`#[Controller]` existe para atender a necessidades mais detalhadas de definição de rotas. A anotação `#[Controller]` indica que a classe atual é uma classe `Controller` e deve ser usada em conjunto com a anotação `#[RequestMapping]` para definir com mais detalhes o método e o caminho da requisição.   
Também fornecemos várias anotações `Mapping` rápidas e práticas, como `#[GetMapping]`, `#[PostMapping]`, `#[PutMapping]`, `#[PatchMapping]` e `#[DeleteMapping]`, 5 anotações convenientes para indicar os diferentes métodos de requisição permitidos.

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class UserController
{
    // O Hyperf gerará automaticamente uma rota /user/index para este método, permitindo requisições via GET ou POST
    #[RequestMapping(path: "index", methods: "get,post")]
    public function index(RequestInterface $request)
    {
        // Obtém o parâmetro id da requisição
        $id = $request->input('id', 1);
        return (string)$id;
    }
}
```

### Parâmetros de rota

> Os parâmetros de rota definidos por este framework devem ser consistentes com o nome da chave e o tipo dos parâmetros do controlador, caso contrário o controlador não conseguirá receber os parâmetros correspondentes

```php
Router::get('/user/{id}', 'App\Controller\UserController::info');
```

```php
public function info(int $id)
{
    $user = User::find($id);
    return $user->toArray();
}
```

Obter pelo método `route`

```php
public function index(RequestInterface $request)
{
        // Retorna se existir; caso contrário, retorna o valor padrão null
        $id = $request->route('id');
        // Retorna se existir; caso contrário, retorna o valor padrão 0
        $id = $request->route('id', 0);
}
```

#### Parâmetros obrigatórios

Podemos fazer algumas definições de parâmetros em `$uri`, declarando parâmetros por meio de `{}`; por exemplo, `/user/{id}` declara que o valor `id` é um parâmetro obrigatório.

#### Parâmetros opcionais

Às vezes você pode querer que esse parâmetro seja opcional; para isso, use `[]` para declarar que o parâmetro entre colchetes é opcional, como em `/user/[{id}]`.

#### Validar parâmetros

Você também pode validar parâmetros usando expressões regulares. Veja alguns exemplos
```php
use Hyperf\HttpServer\Router\Router;

// Pode corresponder a /user/42, mas não a /user/xyz
Router::addRoute('GET', '/user/{id:\d+}', 'handler');

// Pode corresponder a /user/foobar, mas não a /user/foo/bar
Router::addRoute('GET', '/user/{name}', 'handler');

// Também pode corresponder a /user/foo/bar
Router::addRoute('GET', '/user/{name:.+}', 'handler');

// Esta rota
Router::addRoute('GET', '/user/{id:\d+}[/{name}]', 'handler');
// É equivalente às duas rotas abaixo
Router::addRoute('GET', '/user/{id:\d+}', 'handler');
Router::addRoute('GET', '/user/{id:\d+}/{name}', 'handler');

// Vários opcionais aninhados também são permitidos
Router::addRoute('GET', '/user[/{id:\d+}[/{name}]]', 'handler');

// Esta é uma rota inválida, pois a parte opcional só pode aparecer no final
Router::addRoute('GET', '/user[/{id:\d+}]/{name}', 'handler');
```

#### Obter informações de rota

Se o componente devtool estiver instalado, você pode usar o comando `php bin/hyperf.php describe:routes` para obter as informações da lista de rotas,
e ele fornece a opção path para facilitar a obtenção das informações de uma única rota; o comando correspondente é `php bin/hyperf.php describe:routes --path=/foo/bar`.

## Exceções HTTP

Quando uma rota não é correspondida, como em exceções HTTP do tipo `rota não encontrada (404)` e `método de requisição não permitido (405)`, o Hyperf lançará de forma unificada uma subclasse da classe de exceção `Hyperf\HttpMessage\Exception\HttpException`. Você precisa gerenciar essas exceções pelo mecanismo ExceptionHandler e fazer o tratamento de resposta correspondente. Por padrão, você pode usar diretamente o `Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler` fornecido pelo componente para capturar e tratar as exceções; observe que esse manipulador de exceções precisa ser configurado por você mesmo no arquivo de configuração `config/autoload/exceptions.php`, garantindo que a ordem da cadeia entre vários manipuladores de exceções esteja correta.   
Quando você precisar personalizar o tratamento das respostas para situações de exceção HTTP como `rota não encontrada (404)` e `método de requisição não permitido (405)`, você pode implementar seu próprio manipulador de exceções com base no código de `HttpExceptionHandler` e configurar seu próprio manipulador. Sobre a lógica e as instruções de uso dos manipuladores de exceções, consulte [Tratamento de exceções](exception-handler.md)
