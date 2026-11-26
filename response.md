# Resposta

No Hyperf, você pode usar a classe de interface `Hyperf\HttpServer\Contract\ResponseInterface` para injetar o objeto proxy `Response` e processar a resposta; por padrão ele retorna um objeto `Hyperf\HttpServer\Response`, que pode chamar diretamente todos os métodos de `Psr\Http\Message\ResponseInterface`.

> Atenção: o padrão PSR-7 adota um design imutável (immutable) para a Resposta (Response); todos os métodos que começam com with retornam um novo objeto e não modificam o valor do objeto original

## Retornar formato Json

`Hyperf\HttpServer\Contract\ResponseInterface` fornece o método `json($data)` para retornar rapidamente o formato `Json` e definir o `Content-Type` como `application/json`. `$data` aceita um array ou um objeto que implemente a interface `Hyperf\Contract\Arrayable`.

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class IndexController
{
    public function json(ResponseInterface $response): Psr7ResponseInterface
    {
        $data = [
            'key' => 'value'
        ];
        return $response->json($data);
    }
}
```

## Retornar formato Xml

`Hyperf\HttpServer\Contract\ResponseInterface` fornece o método `xml($data)` para retornar rapidamente o formato `XML` e definir o `Content-Type` como `application/xml`. `$data` aceita um array ou um objeto que implemente a interface `Hyperf\Contract\Xmlable`.

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class IndexController
{
    public function xml(ResponseInterface $response): Psr7ResponseInterface
    {
        $data = [
            'key' => 'value'
        ];
        return $response->xml($data);
    }
}
```

## Retornar formato Raw

`Hyperf\HttpServer\Contract\ResponseInterface` fornece o método `raw($data)` para retornar rapidamente o formato `raw` e definir o `Content-Type` como `plain/text`. `$data` aceita uma string ou um objeto que implemente o método `__toString()`.

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class IndexController
{
    public function raw(ResponseInterface $response): Psr7ResponseInterface
    {
        return $response->raw('Hello Hyperf.');
    }
}
```

## Retornar view

Consulte a documentação da seção [view](view.md)

## Redirecionamento

`Hyperf\HttpServer\Contract\ResponseInterface` fornece `redirect(string $toUrl, int $status = 302, string $schema = 'http')`, que retorna um objeto `Psr7ResponseInterface` com o estado de redirecionamento já definido.

Método `redirect`:   

|  Parâmetro  |  Tipo  | Valor padrão |                                                      Observação                                                      |
|:------:|:------:|:------:|:--------------------------------------------------------------------------------------------------------------:|
| toUrl  | string |   nenhum   | Se o parâmetro não contiver `http://` ou `https://`, a URL correspondente será montada automaticamente com base no Host do serviço atual, e o protocolo será montado conforme o parâmetro `$schema` |
| status |  int   |  302   |                                                   Código de status da resposta                                                   |
| schema | string |  http  |                 Tem efeito quando `$toUrl` não contém `http://` ou `https://`; só pode receber `http` ou `https`                  |

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class IndexController
{
    public function redirect(ResponseInterface $response): Psr7ResponseInterface
    {
        // O método redirect() retorna um objeto Psr\Http\Message\ResponseInterface, que precisa ser retornado com return
        return $response->redirect('/anotherUrl');
    }
}
```

## Configuração de Cookie

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Hyperf\HttpMessage\Cookie\Cookie;

class IndexController
{
    public function cookie(ResponseInterface $response): Psr7ResponseInterface
    {
        $cookie = new Cookie('key', 'value');
        return $response->withCookie($cookie)->withContent('Hello Hyperf.');
    }
}
```

## Codificação de transferência em partes Chunk

`Hyperf\HttpServer\Contract\ResponseInterface` fornece `write(string $data)` para enviar o conteúdo correspondente ao navegador em partes e definir `Transfer-Encoding` como `chunked`. `$data` aceita uma string ou um objeto que implemente o método `__toString()`.

```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Swoole\Coroutine;
use Hyperf\Engine\Http\EventStream;

class IndexController
{
    public function index(ResponseInterface $response)
    {
       $response
            ->withStatus(200)
            ->withHeader('X-Event-Mode', 'Enabled') // ⭐ Header personalizado
            ->withHeader('X-Stream-Time', '5s');
        $streamer = new EventStream($this->response->getConnection(), $response);
        $startTime = time();
        $totalSteps = 5;
        $streamer->write("data: --- 🚀 EventStream iniciado (total de {$totalSteps} etapas) ---\n\n");
        for ($i = 1; $i <= $totalSteps; ++$i) {
            Coroutine::sleep(1);
            $elapsed = time() - $startTime;
            $message = "data: 【{$i}º segundo】Bloco de dados enviado. Tempo decorrido: {$elapsed} segundos\n\n";
            $streamer->write($message);
        }
        $streamer->write("data: --- ✅ EventStream encerrado ---\n\n");
        $streamer->end();

        return 'Hello Hyperf';
    }
}
```

!> Atenção: após chamar `write` para enviar dados em partes, se você usar `return` para retornar dados novamente, os dados não serão retornados normalmente. Ou seja, no exemplo acima, `Hello Hyperf` não será exibido, apenas `data: 【{$i}º segundo】Bloco de dados enviado. Tempo decorrido: {$elapsed} segundos\n\n`.

## Download de arquivo

`Hyperf\HttpServer\Contract\ResponseInterface` fornece `download(string $file, string $name = '')`, que retorna um objeto `Psr7ResponseInterface` com o estado de download de arquivo já definido.

Se a requisição contiver o cabeçalho `if-match` ou `if-none-match`, o Hyperf também fará a comparação com o `ETag` conforme o padrão do protocolo; se forem iguais, retornará uma resposta com status `304`.

Método `download`:   

| Parâmetro |  Tipo  | Valor padrão |                                Observação                                 |
|:----:|:------:|:------:|:-------------------------------------------------------------------:|
| file | string |   nenhum   | Caminho absoluto do arquivo a ser retornado para download; use a constante BASE_PATH para localizar a raiz do projeto |
| name | string |   nenhum   |         Nome do arquivo no download do cliente; se vazio, será usado o nome original do arquivo          |


```php
<?php
namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class IndexController
{
    public function index(ResponseInterface $response): Psr7ResponseInterface
    {
        return $response->download(BASE_PATH . '/public/file.csv', 'filename.csv');
    }
}
```
