# Objeto de requisição

O `Objeto de requisição (Request)` é implementado totalmente com base no padrão [PSR-7](https://www.php-fig.org/psr/psr-7/), e o suporte à implementação é fornecido pelo componente [hyperf/http-message](https://github.com/hyperf/http-message).

> Atenção: o padrão [PSR-7](https://www.php-fig.org/psr/psr-7/) adota o `mecanismo immutable` para `Request`, ou seja, o valor de retorno de todos os métodos que começam com `with` é um novo objeto, e o valor do objeto original não é modificado

## Instalação

Este componente é totalmente independente e aplicável a qualquer projeto de framework.

```bash
composer require hyperf/http-message
```

> Se usado em projetos de outros frameworks, apenas as APIs fornecidas pelo PSR-7 serão suportadas; consulte diretamente as especificações relacionadas ao PSR-7. As formas de uso descritas nesta documentação se referem apenas ao uso com o Hyperf.

## Obter o objeto de requisição

Você pode obter o `Hyperf\HttpServer\Request` correspondente injetando `Hyperf\HttpServer\Contract\RequestInterface` pelo contêiner. O objeto realmente injetado é um objeto proxy, e o objeto proxyado é o `Objeto de requisição (Request) PSR-7` de cada requisição, o que significa que esse objeto só pode ser obtido dentro do ciclo de vida de `onRequest`. Veja abaixo um exemplo de obtenção:

```php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    public function info(RequestInterface $request)
    {
        // ...
    }
}
```

### Injeção de dependência e parâmetros

Se você deseja obter parâmetros de rota pelos parâmetros do método do controlador, basta listar os parâmetros correspondentes após as dependências; o framework injetará automaticamente os parâmetros correspondentes nos parâmetros do método. Por exemplo, se sua rota estiver definida assim:

```php
// Modo de anotação
#[GetMapping(path: "/user/{id:\d+}")]
// Modo de configuração
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'HEAD'], '/user/{id:\d+}', [\App\Controller\IndexController::class, 'user']);
```

Então você pode obter o parâmetro `Query` `id` declarando o parâmetro `$id` nos parâmetros do método, como mostrado abaixo:

```php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    public function info(RequestInterface $request, int $id)
    {
        // ...
    }
}
```

Além de obter parâmetros de rota por injeção de dependência, você também pode obtê-los pelo método `route`, como mostrado abaixo:

```php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController
{
    public function info(RequestInterface $request)
    {
        // Retorna se existir; caso contrário, retorna o valor padrão null
        $id = $request->route('id');
        // Retorna se existir; caso contrário, retorna o valor padrão 0
        $id = $request->route('id', 0);
        // ...
    }
}
```

### Caminho e método da requisição

Além das `APIs` definidas pelo padrão [PSR-7](https://www.php-fig.org/psr/psr-7/), o `Hyperf\HttpServer\Contract\RequestInterface` também fornece diversos métodos para inspecionar a requisição. Abaixo fornecemos alguns exemplos de métodos:

#### Obter o caminho da requisição

O método `path()` retorna as informações do caminho da requisição. Ou seja, se o endereço de destino da requisição recebida for `http://domain.com/foo/bar?baz=1`, então `path()` retornará `foo/bar`:

```php
$uri = $request->path();
```

O método `is(...$patterns)` pode verificar se o caminho da requisição recebida corresponde à regra especificada. Ao usar este método, você também pode passar o caractere `*` como curinga:

```php
if ($request->is('user/*')) {
    // ...
}
```

#### Obter a URL da requisição

Você pode usar o método `url()` ou `fullUrl()` para obter a `URL` completa da requisição recebida. O método `url()` retorna a `URL` sem os `Parâmetros de Query`, enquanto o valor de retorno do método `fullUrl()` inclui os `Parâmetros de Query`:

```php
// Sem parâmetros de consulta
$url = $request->url();

// Com parâmetros de consulta
$url = $request->fullUrl();
```

#### Obter o método da requisição

O método `getMethod()` retornará o método de requisição `HTTP`. Você também pode usar o método `isMethod(string $method)` para verificar se o método de requisição `HTTP` corresponde à regra especificada:

```php
$method = $request->getMethod();

if ($request->isMethod('post')) {
    // ...
}
```

### Requisições e métodos PSR-7

O componente [hyperf/http-message](https://github.com/hyperf/http-message) é ele próprio um componente que implementa o padrão [PSR-7](https://www.php-fig.org/psr/psr-7/), e os métodos relacionados podem ser chamados pelo `Objeto de requisição (Request)` injetado.   
Se na injeção for declarada a interface `Psr\Http\Message\ServerRequestInterface` do padrão [PSR-7](https://www.php-fig.org/psr/psr-7/), o framework a converterá automaticamente para o objeto `Hyperf\HttpServer\Request`, equivalente a `Hyperf\HttpServer\Contract\RequestInterface`.   

> Recomenda-se usar `Hyperf\HttpServer\Contract\RequestInterface` para a injeção, pois assim você obtém o suporte de autocompletar da IDE para os métodos exclusivos.

## Pré-processamento e normalização de entrada

### Obter entradas

#### Obter todas as entradas

Você pode usar o método `all()` para obter todos os dados de entrada na forma de `array`:

```php
$all = $request->all();
```

#### Obter um valor de entrada específico

Obtenha `um` ou `vários` valores de entrada de qualquer forma por meio de `input(string $key, $default = null)` e `inputs(array $keys, $default = null): array`:

```php
// Retorna se existir; caso contrário, retorna null
$name = $request->input('name');
// Retorna se existir; caso contrário, retorna o valor padrão Hyperf
$name = $request->input('name', 'Hyperf');
```

Se os dados do formulário transmitidos contiverem dados no formato de「array」, você pode usar a sintaxe de「ponto」para obtê-los:

```php
$name = $request->input('products.0.name');

$names = $request->input('products.*.name');
```
#### Obter entradas a partir da string de consulta

Os métodos `input` e `inputs` podem obter dados de entrada de toda a requisição (incluindo os `Parâmetros de Query`), enquanto o método `query(?string $key = null, $default = null)` pode obter dados de entrada somente a partir da string de consulta:

```php
// Retorna se existir; caso contrário, retorna null
$name = $request->query('name');
// Retorna se existir; caso contrário, retorna o valor padrão Hyperf
$name = $request->query('name', 'Hyperf');
// Sem passar parâmetros, retorna todos os Parâmetros de Query na forma de array associativo
$name = $request->query();
```

#### Obter informações de entrada `JSON`

Se o formato dos dados do `Body` da requisição for `JSON`, desde que o `Content-Type` `Header` do `Objeto de requisição (Request)` esteja corretamente definido como `application/json`, você poderá acessar os dados `JSON` pelo método `input(string $key, $default = null)`, e até usar a sintaxe de「ponto」para ler arrays `JSON`:

```php
// Retorna se existir; caso contrário, retorna null
$name = $request->input('user.name');
// Retorna se existir; caso contrário, retorna o valor padrão Hyperf
$name = $request->input('user.name', 'Hyperf');
// Retorna todos os dados Json na forma de array
$name = $request->all();
```

#### Determinar se existe um valor de entrada

Para julgar se a requisição possui determinado valor, use o método `has($keys)`. Se o valor existir na requisição, retorna `true`; caso contrário, retorna `false`. `$keys` pode ser uma string ou um array contendo múltiplas strings; somente se todos existirem o retorno será `true`:

```php
// Julga apenas um único valor
if ($request->has('name')) {
    // ...
}
// Julga vários valores ao mesmo tempo
if ($request->has(['name', 'email'])) {
    // ...
}
```

### Cookies

#### Obter Cookies da requisição

Use o método `getCookieParams()` para obter todos os `Cookies` da requisição; o resultado é retornado na forma de um array associativo.

```php
$cookies = $request->getCookieParams();
```

Se desejar obter um determinado valor de `Cookie`, use o método `cookie(string $key, $default = null)` para obter o valor correspondente:

 ```php
// Retorna se existir; caso contrário, retorna null
$name = $request->cookie('name');
// Retorna se existir; caso contrário, retorna o valor padrão Hyperf
$name = $request->cookie('name', 'Hyperf');
 ```

### Arquivos

#### Obter arquivos enviados

Você pode usar o método `file(string $key, $default): ?Hyperf\HttpMessage\Upload\UploadedFile` para obter o objeto de arquivo enviado a partir da requisição. Se o arquivo enviado existir, o método retornará uma instância da classe `Hyperf\HttpMessage\Upload\UploadedFile`, que herda da classe `SplFileInfo` do `PHP` e também fornece vários métodos para interagir com arquivos:

```php
// Retorna um objeto Hyperf\HttpMessage\Upload\UploadedFile se existir; caso contrário, retorna null
$file = $request->file('photo');
```

#### Verificar se o arquivo existe

Você pode usar o método `hasFile(string $key): bool` para confirmar se existe um arquivo na requisição:

```php
if ($request->hasFile('photo')) {
    // ...
}
```

#### Validar o envio bem-sucedido

Além de verificar se o arquivo enviado existe, você também pode validar se o arquivo enviado é válido pelo método `isValid(): bool`:

```php
if ($request->file('photo')->isValid()) {
    // ...
}
```

#### Caminho e extensão do arquivo

A classe `UploadedFile` também contém métodos para acessar o caminho completo do arquivo e sua extensão. O método `getExtension()` determina a extensão do arquivo com base no conteúdo do arquivo. Essa extensão pode ser diferente da extensão fornecida pelo cliente:

```php
// Este caminho é o caminho temporário do arquivo enviado
$path = $request->file('photo')->getPath();

// Como o tmp_name de arquivos enviados no Swoole não mantém o nome original do arquivo, este método foi reescrito para obter a extensão do nome original do arquivo
$extension = $request->file('photo')->getExtension();
```

#### Armazenar arquivos enviados

Antes de serem armazenados manualmente, os arquivos enviados ficam em um local temporário. Se você não armazenar o arquivo, ele será removido do local temporário após o término da requisição. Portanto, pode ser necessário persistir o arquivo; use `moveTo(string $targetPath): void` para mover o arquivo temporário para o local `$targetPath` e armazená-lo de forma persistente. Exemplo de código:

```php
$file = $request->file('photo');
$file->moveTo('/foo/bar.jpg');

// Use o método isMoved(): bool para julgar se o arquivo já foi movido
if ($file->isMoved()) {
    // ...
}
```


## Eventos relacionados

Quando habilitamos `enable_request_lifecycle` na configuração do serviço, cada requisição recebida pode acionar os três eventos a seguir:

### Exemplo de configuração

> O código a seguir omite outros trechos irrelevantes

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Hyperf\Server\ServerInterface;

return [
    'servers' => [
        [
            'name' => 'http',
            'type' => ServerInterface::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
            'options' => [
                // Whether to enable request lifecycle event
                'enable_request_lifecycle' => false,
            ],
        ],
    ],
];

```

### Lista de eventos

- Hyperf\HttpServer\Event\RequestReceived

Ao receber uma requisição, este evento é acionado

- Hyperf\HttpServer\Event\RequestHandled

Ao concluir o processamento da requisição, este evento é acionado

- Hyperf\HttpServer\Event\RequestTerminated

Quando a corrotina responsável pela requisição atual é destruída, este evento é acionado
