# Construtor de recursos de API

> Extensão de recurso com suporte a retorno de respostas Grpc

## Introdução

Ao construir APIs, muitas vezes você precisa de uma camada de transformação para conectar seu modelo Model e a resposta JSON realmente retornada ao usuário. As classes de recurso permitem transformar modelos e coleções de modelos em JSON de uma forma mais intuitiva e simples.

## Instalação

```
composer require hyperf/resource
```

## Gerando recursos

Você pode usar o comando `gen:resource` para gerar uma classe de recurso. Por padrão, os recursos gerados são colocados na pasta `app/Resource` da aplicação. Os recursos herdam da classe `Hyperf\Resource\Json\JsonResource`:

```bash
php bin/hyperf.php gen:resource User
```

### Coleções de recursos

Além de gerar recursos para transformar um único modelo, você também pode gerar coleções de recursos para transformar coleções de modelos. Isso permite incluir na resposta links e outras meta-informações relacionadas ao recurso fornecido.

Você precisa adicionar a flag `--collection` ao gerar o recurso para gerar uma coleção de recursos. Alternativamente, você pode simplesmente incluir `Collection` no nome do recurso para indicar que uma coleção de recursos deve ser gerada. As coleções de recursos herdam da classe `Hyperf\Resource\Json\ResourceCollection`:

```bash
php bin/hyperf.php gen:resource Users --collection

php bin/hyperf.php gen:resource UserCollection
```

## Recursos gRPC

> Requer a instalação adicional de `hyperf/resource-grpc`

```
composer require hyperf/resource-grpc
```

```bash
php bin/hyperf.php gen:resource User --grpc
```

Os recursos gRPC precisam definir a classe `message`. Isso é feito reescrevendo o método `expect()` dessa classe de recurso.

Ao retornar do serviço gRPC, é obrigatório chamar `toMessage()`. Esse método retorna uma instância da classe `message`.

```php
<?php
namespace HyperfTest\ResourceGrpc\Stubs\Resources;

use Hyperf\ResourceGrpc\GrpcResource;
use HyperfTest\ResourceGrpc\Stubs\Grpc\HiReply;

class HiReplyResource extends GrpcResource
{
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'user' => HiUserResource::make($this->user),
        ];
    }

    public function expect(): string
    {
        return HiReply::class;
    }
}

```

A coleção de recursos gerada por padrão pode passar a suportar retorno gRPC herdando a interface `Hyperf\ResourceGrpc\GrpcResource`.

## Visão geral dos conceitos

> Esta é uma visão geral de alto nível sobre recursos e coleções de recursos. É altamente recomendável ler as demais partes desta documentação para se aprofundar em como personalizar e usar recursos da melhor forma.

Antes de se aprofundar em como escrever seus recursos de forma personalizada, vamos primeiro ver como usar recursos no framework. Uma classe de recurso representa um único modelo que precisa ser convertido para o formato JSON. Por exemplo, agora temos uma classe de recurso `User` simples:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

```

Cada classe de recurso define um método `toArray`, que, ao enviar a resposta, retorna o array de propriedades que deve ser convertido em JSON. Observe que aqui podemos usar diretamente a variável `$this` para acessar as propriedades do modelo. Isso ocorre porque a classe de recurso faz automaticamente um proxy de propriedades e métodos para o modelo subjacente, facilitando o acesso. Você pode retornar o recurso definido no controlador:

```php
<?php

namespace App\Controller;

use App\Resource\User as UserResource;
use App\Model\User;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserResource(User::first()))->toResponse();
    }
}

```

### Coleções de recursos

Você pode usar o método `collection` no controlador para criar uma instância de recurso e retornar uma coleção de múltiplos recursos ou uma resposta paginada:

```php

namespace App\Controller;

use App\Resource\User as UserResource;
use App\Model\User;

class IndexController extends AbstractController
{
    public function index()
    {
        return UserResource::collection(User::all())->toResponse();
    }
}

```

Naturalmente, usando o método acima você não poderá adicionar nenhum metadado extra para retornar junto com a coleção. Se precisar personalizar a resposta de uma coleção de recursos, você precisa criar um recurso dedicado para representar a coleção:

```bash
php bin/hyperf.php gen:resource UserCollection
```

Você pode definir facilmente, na classe de coleção de recursos gerada, qualquer metadado que deseje retornar na resposta:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray() :array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}

```

Você pode retornar a coleção de recursos definida no controlador:

```php
<?php
namespace App\Controller;

use App\Model\User;
use App\Resource\UserCollection;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserCollection(User::all()))->toResponse();
    }
}

```

### Preservando as chaves da coleção

Ao retornar uma coleção de recursos a partir de uma rota, as chaves da coleção são redefinidas para que fiquem em uma ordem numérica simples. No entanto, você pode adicionar a propriedade `preserveKeys` à classe de recurso para indicar se as chaves da coleção devem ser preservadas:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Indica se as chaves da coleção de recursos devem ser preservadas.
     *
     * @var bool
     */
    public $preserveKeys = true;

    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

```

Quando a propriedade `preserveKeys` é definida como `true`, as chaves da coleção serão preservadas:

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Resource\User as UserResource;

class IndexController extends AbstractController
{
    public function index()
    {
        return UserResource::collection(User::all()->keyBy->id)->toResponse();
    }
}

```

### Personalizando a classe de recurso base

Normalmente, a propriedade `$this->collection` da coleção de recursos é preenchida automaticamente, e o resultado é o mapeamento de cada item da coleção para sua classe de recurso individual. Assume-se que a classe de recurso individual é o nome da classe da coleção, mas sem a string `Collection` no final.

Por exemplo, `UserCollection` mapeia as instâncias de usuário fornecidas para o recurso `User`. Para personalizar esse comportamento, você pode reescrever a propriedade `$collects` da coleção de recursos:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * A propriedade collects define a classe de recurso.
     *
     * @var string
     */
    public $collects = 'App\Resource\Member';

    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}

```

## Escrevendo recursos

> Se você ainda não leu a [visão geral dos conceitos](#visão-geral-dos-conceitos), é altamente recomendável lê-la antes de continuar com esta documentação.

Em essência, a função dos recursos é muito simples. Eles só precisam converter um modelo fornecido em um array. Portanto, cada recurso contém um método `toArray` usado para converter as propriedades do seu modelo em um array amigável para API que pode ser retornado ao usuário:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

```

Você pode retornar o recurso já definido no controlador:

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Resource\User as UserResource;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserResource(User::find(1)))->toResponse();
    }
}

```

### Relacionamentos

Se você deseja incluir recursos de relacionamento na resposta, basta adicioná-los ao array retornado pelo método `toArray`. No exemplo abaixo, usaremos o método `collection` do recurso `Post` para adicionar os artigos do usuário à resposta do recurso:
```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'posts' => PostResource::collection($this->posts),
        ];
    }
}

```

> Se você só deseja adicionar o recurso de relacionamento quando o relacionamento já estiver carregado, consulte a documentação relacionada.

### Coleções de recursos

Recursos convertem um único modelo em um array, enquanto coleções de recursos convertem uma coleção de vários modelos em um array. Todos os recursos fornecem um método `collection` para gerar uma coleção de recursos 「temporária」, portanto não é necessário escrever uma classe de coleção de recursos para cada tipo de modelo:

```php
<?php
namespace App\Controller;

use App\Resource\User as UserResource;
use App\Model\User;

class IndexController extends AbstractController
{
    public function index()
    {
        return UserResource::collection(User::all())->toResponse();
    }
}

```

Para personalizar os metadados da coleção retornada, ainda é necessário definir uma coleção de recursos:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}

```

Assim como nos recursos individuais, você pode retornar diretamente a coleção de recursos no controlador:

```php
<?php
namespace App\Controller;

use App\Model\User;
use App\Resource\UserCollection;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserCollection(User::all()))->toResponse();
    }
}

```

### Empacotamento de dados

Por padrão, quando a resposta do recurso é convertida em JSON, o recurso de nível superior é empacotado na chave `data`. Portanto, uma resposta típica de coleção de recursos é exibida da seguinte forma:

```json

    {
        "data": [
            {
                "id": 1,
                "name": "Eladio Schroeder Sr.",
                "email": "therese28@example.com"
            },
            {
                "id": 2,
                "name": "Liliana Mayert",
                "email": "evandervort@example.com"
            }
        ]
    }

```

Você pode usar o método `withoutWrapping` da classe base de recurso para desativar o empacotamento do recurso de nível superior.

```php
<?php
namespace App\Controller;

use App\Model\User;
use App\Resource\UserCollection;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserCollection(User::all()))->withoutWrapping()->toResponse();
    }
}

```

> O método withoutWrapping apenas desativa o empacotamento do recurso de nível superior; ele não remove a chave data que você adicionou manualmente à coleção de recursos. Além disso, ele só tem efeito no recurso ou na coleção de recursos atual, sem afetar globalmente.

#### Empacotando recursos aninhados

Você tem total liberdade para decidir como os relacionamentos de recursos serão empacotados. Se você deseja que todas as coleções de recursos sejam empacotadas na chave `data`, independentemente do nível de aninhamento, então você precisa definir uma classe de coleção de recursos para cada recurso e empacotar a coleção retornada na chave `data`.

Naturalmente, você pode se preocupar que, assim, o recurso de nível superior seja empacotado em duas chaves `data `. Fique tranquilo: o componente nunca permitirá que seu recurso seja empacotado duas vezes, então você não precisa se preocupar que a coleção de recursos convertida seja aninhada múltiplas vezes:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}

```

#### Paginação

Ao retornar uma coleção paginada na resposta de recurso, mesmo que você chame o método `withoutWrapping`, o componente empacotará os dados do seu recurso na chave `data`. Isso porque a resposta paginada sempre terá as chaves `meta` e `links` contendo as informações do estado da paginação:

```json

    {
        "data": [
            {
                "id": 1,
                "name": "Eladio Schroeder Sr.",
                "email": "therese28@example.com"
            },
            {
                "id": 2,
                "name": "Liliana Mayert",
                "email": "evandervort@example.com"
            }
        ],
        "links":{
            "first": "/pagination?page=1",
            "last": "/pagination?page=1",
            "prev": null,
            "next": null
        },
        "meta":{
            "current_page": 1,
            "from": 1,
            "last_page": 1,
            "path": "/pagination",
            "per_page": 15,
            "to": 10,
            "total": 10
        }
    }
```

Você pode passar a instância de paginação para o método `collection` do recurso ou para uma coleção de recursos personalizada:

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Resource\UserCollection;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserCollection(User::paginate()))->toResponse();
    }
}
```

A resposta paginada sempre terá as chaves `meta` e `links` contendo as informações do estado da paginação:

```json

    {
        "data": [
            {
                "id": 1,
                "name": "Eladio Schroeder Sr.",
                "email": "therese28@example.com"
            },
            {
                "id": 2,
                "name": "Liliana Mayert",
                "email": "evandervort@example.com"
            }
        ],
        "links":{
            "first": "/pagination?page=1",
            "last": "/pagination?page=1",
            "prev": null,
            "next": null
        },
        "meta":{
            "current_page": 1,
            "from": 1,
            "last_page": 1,
            "path": "/pagination",
            "per_page": 15,
            "to": 10,
            "total": 10
        }
    }
```

### Propriedades condicionais

Às vezes, você pode querer adicionar propriedades à resposta do recurso quando uma determinada condição for atendida. Por exemplo, você pode querer adicionar um valor à resposta do recurso se o usuário atual for um 「administrador」. Nesse caso, o componente oferece alguns métodos auxiliares para ajudar a resolver o problema. O método `when` pode ser usado para adicionar propriedades condicionalmente à resposta do recurso:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'secret' => $this->when(Auth::user()->isAdmin(), 'secret-value'),
        ];
    }
}

```

No exemplo acima, apenas quando o método `isAdmin` retorna `true` a chave `secret` será finalmente retornada na resposta do recurso. Se o método retornar `false`, a chave `secret` será removida da resposta do recurso antes de ser enviada ao cliente. O método `when` permite evitar o uso de instruções condicionais para montar arrays, escrevendo seus recursos de uma forma mais elegante.

O método `when` também aceita um closure como segundo parâmetro; somente quando a condição fornecida for `true` o valor retornado será calculado a partir do closure:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'secret' => $this->when(Auth::user()->isAdmin(), function () {
                return 'secret-value';
            }),
        ];
    }
}

```

#### Mesclando dados condicionalmente

Às vezes, você pode querer adicionar múltiplas propriedades à resposta do recurso quando uma determinada condição for atendida. Nesse caso, você pode usar o método `mergeWhen` para adicionar múltiplas propriedades à resposta quando a condição fornecida for `true`:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            $this->mergeWhen(Auth::user()->isAdmin(), [
                'first-secret' => 'value',
                'second-secret' => 'value',
            ]),
        ];
    }
}

```

Da mesma forma, se a condição fornecida for `false`, essas propriedades serão removidas da resposta do recurso antes de ela ser enviada ao cliente.

> O método `mergeWhen` não deve ser usado em arrays que misturam chaves de string e numéricas. Além disso, ele também não deve ser usado em arrays com chaves numéricas fora de ordem.

### Relacionamentos condicionais

Além de adicionar propriedades condicionalmente, você também pode incluir relacionamentos na resposta do seu recurso condicionalmente, dependendo se o relacionamento do modelo já foi carregado. Isso permite que você decida no controlador quais relacionamentos de modelo carregar, de modo que seu recurso possa adicioná-los somente após o relacionamento do modelo ser carregado.

Fazer isso evita o problema de consultas 「N+1」 no seu recurso. Você deve usar o método `whenLoaded` para carregar o relacionamento condicionalmente. Para evitar carregar relacionamentos desnecessários, esse método aceita como parâmetro o nome do relacionamento, e não o relacionamento em si:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'posts' => PostResource::collection($this->whenLoaded('posts')),
        ];
    }
}

```

No exemplo acima, se o relacionamento não tiver sido carregado, a chave `posts` será removida da resposta do recurso antes de ela ser enviada ao cliente.

#### Informações condicionais da tabela intermediária

Além de incluir relacionamentos condicionalmente na resposta do seu recurso, você também pode usar o método `whenPivotLoaded` para adicionar condicionalmente dados da tabela intermediária de um relacionamento muitos-para-muitos. O primeiro parâmetro aceito pelo método `whenPivotLoaded` é o nome da tabela intermediária. O segundo parâmetro é um closure, que define o valor a ser retornado no modelo se as informações da tabela intermediária estiverem disponíveis:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'expires_at' => $this->whenPivotLoaded('role_user', function () {
                return $this->pivot->expires_at;
            }),
        ];
    }
}

```

Se a sua tabela intermediária usar um acessador diferente de `pivot`, você pode usar o método `whenPivotLoadedAs`:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'expires_at' => $this->whenPivotLoadedAs('subscription', 'role_user', function () {
                return $this->subscription->expires_at;
            }),
        ];
    }
}

```

### Adicionando metadados

Alguns padrões de API JSON exigem que você adicione metadados nas respostas de recursos e coleções de recursos. Isso geralmente inclui os `links` do recurso ou dos recursos relacionados, ou alguns metadados sobre o próprio recurso. Se você precisar retornar outros metadados sobre o recurso, basta incluí-los no método `toArray`. Por exemplo, ao transformar uma coleção de recursos, você pode precisar adicionar informações de `links`:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}

```

Ao adicionar metadados extras ao seu recurso, você não precisa se preocupar em sobrescrever as chaves `links` ou `meta` adicionadas automaticamente ao retornar uma resposta paginada. Quaisquer outros `links` que você adicionar serão mesclados com os `links` adicionados pela resposta paginada.

#### Metadados de nível superior

Às vezes, você pode querer adicionar certos metadados à resposta do recurso quando o recurso é retornado como recurso de nível superior. Isso geralmente inclui meta-informações de toda a resposta. Você pode adicionar o método `with` à classe de recurso para definir metadados. Esse método deve retornar um array de metadados; quando o recurso for renderizado como recurso de nível superior, esse array será incluído na resposta do recurso:

```php
<?php

namespace App\Resource;

use Hyperf\Resource\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }

    public function with() : array
    {
        return [
            'meta' => [
                'key' => 'value',
            ],
        ];
    }
}

```

#### Adicionando metadados ao construir o recurso

Você também pode adicionar dados de nível superior ao construir a instância de recurso no controlador. Todos os recursos podem usar o método `additional` para receber um array de dados que deve ser adicionado à resposta do recurso:

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Resource\UserCollection;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserCollection(User::all()->load('roles')))
            ->additional(['meta' => [
                'key' => 'value',
            ]])->toResponse();    
    }
}

```

## Respondendo com recursos

Como você já sabe, os recursos podem ser retornados diretamente no controlador:

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Resource\User as UserResource;

class IndexController extends AbstractController
{
    public function index()
    {
        return (new UserResource(User::find(1)))->toResponse();
    }

    public function info()
    {
        return new UserResource(User::find(1));
    }
}

```

Se você quiser definir informações de cabeçalho de resposta, código de status, etc., obtenha o objeto de resposta chamando o método `toResponse()` e faça as configurações.
