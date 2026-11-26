# Controlador

Para tratar requisições HTTP por meio de controladores, é necessário vincular as rotas aos métodos do controlador por meio de `arquivo de configuração` ou `anotação`; consulte o capítulo [Rotas](router.md).   
Para `Requisição (Request)` e `Resposta (Response)`, o Hyperf fornece `Hyperf\HttpServer\Contract\RequestInterface` e `Hyperf\HttpServer\Contract\ResponseInterface` para facilitar a obtenção de parâmetros de entrada e dados de retorno. Sobre o conteúdo detalhado de [Requisição](request.md) e [Resposta](response.md), consulte os capítulos correspondentes.

## Escrever controlador

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

class IndexController
{
    // No parâmetro, obtenha os objetos relacionados definindo RequestInterface e ResponseInterface; os objetos serão injetados automaticamente pelo contêiner de injeção de dependência
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $target = $request->input('target', 'World');
        return 'Hello ' . $target;
    }
}
```

> Supomos que este `Controller` já tenha definido a rota `/` por meio de arquivo de configuração; naturalmente, você também pode usar rotas por anotação

Chame esse endereço com `cURL` para ver o conteúdo retornado.

```bash
$ curl 'http://127.0.0.1:9501/?target=Hyperf'
Hello Hyperf.
```

## Evitar confusão de dados entre corrotinas

Nos frameworks tradicionais baseados em PHP-FPM, é habitual fornecer um `AbstractController` ou outro `controlador pai abstrato` com outro nome, e então o `Controller` definido precisa herdar dele para obter alguns dados da requisição ou realizar algumas operações de retorno. No Hyperf, **isso não pode ser feito**, porque dentro do Hyperf a grande maioria dos objetos, incluindo o `Controller`, existe na forma de `singleton`, justamente para melhor reutilizar os objetos. Além disso, os dados relacionados à requisição, sob corrotinas, precisam ser armazenados no `contexto da corrotina (Context)`. Portanto, ao escrever código, não deixe de observar que **não se deve** armazenar dados relacionados a uma única requisição em propriedades de classe, incluindo propriedades não estáticas.   

Naturalmente, se realmente for necessário armazenar dados da requisição em propriedades de classe, também há solução. Podemos notar que, ao obter os objetos de `Requisição (Request)` e `Resposta (Response)`, fazemos isso injetando `Hyperf\HttpServer\Contract\RequestInterface` e `Hyperf\HttpServer\Contract\ResponseInterface`. Mas os objetos correspondentes também não são singletons? Como se garante a segurança entre corrotinas aqui? Tomando `RequestInterface` como exemplo, o objeto correspondente `Hyperf\HttpServer\Request`, internamente, ao obter o `objeto de requisição PSR-7`, sempre o faz a partir do `contexto da corrotina (Context)`. Portanto, a classe realmente usada é apenas uma classe proxy; o que é efetivamente chamado é obtido do `contexto da corrotina (Context)`.
