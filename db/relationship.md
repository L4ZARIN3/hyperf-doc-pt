# Relações entre modelos

## Definir relações

As relações são apresentadas na forma de métodos nas classes de modelo do `Hyperf`. Assim como o próprio modelo do `Hyperf`, as relações também podem ser usadas como um poderoso `construtor de consultas`, oferecendo recursos poderosos de encadeamento e consulta. Por exemplo, podemos adicionar uma condição de restrição no encadeamento da relação role:

```php
$user->role()->where('level', 1)->get();
```

### Um para um

Um para um é a relação mais básica. Por exemplo, um modelo `User` pode estar relacionado a um modelo `Role`. Para definir essa relação, escrevemos um método `role` no modelo `User`. Dentro do método `role`, chamamos o método `hasOne` e retornamos seu resultado:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public function role()
    {
        return $this->hasOne(Role::class, 'user_id', 'id');
    }
}
```

O primeiro parâmetro do método `hasOne` é o nome da classe do modelo relacionado. Uma vez definida a relação, podemos usar as propriedades dinâmicas do `Hyperf` para obter o registro relacionado. As propriedades dinâmicas permitem acessar o método de relação como se fosse uma propriedade definida no modelo:

```php
$role = User::query()->find(1)->role;
```

### Um para muitos

A relação「um para muitos」é usada para definir que um único modelo possui qualquer quantidade de outros modelos relacionados. Por exemplo, um autor pode ter escrito vários livros. Assim como todas as outras relações do `Hyperf`, a definição da relação um para muitos também é feita escrevendo um método no modelo do `Hyperf`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public function books()
    {
        return $this->hasMany(Book::class, 'user_id', 'id');
    }
}
```

Lembre-se de que o `Hyperf` determinará automaticamente a propriedade de chave estrangeira do modelo `Book`. Por convenção, o `Hyperf` usará a forma「snake case」do nome do modelo proprietário, acrescida do sufixo `_id`, como campo de chave estrangeira. Portanto, no exemplo acima, o `Hyperf` assumirá que a chave estrangeira de `User` no modelo `Book` é `user_id`.

Uma vez definida a relação, você pode obter a coleção de livros acessando a propriedade `books` do modelo `User`. Lembre-se de que, como o Hyperf oferece「propriedades dinâmicas」, podemos acessar os métodos de relação como se fossem propriedades do modelo:

```php
$books = User::query()->find(1)->books;

foreach ($books as $book) {
    //
}
```

É claro que, como todas as relações também podem ser usadas como construtor de consultas, você pode usar encadeamento para adicionar condições extras de restrição ao método books:

```php
$book = User::query()->find(1)->books()->where('title', 'Dominando o framework Hyperf em um mês')->first();
```

### Um para muitos (inverso)

Agora que já conseguimos obter todas as obras de um autor, vamos definir a relação inversa, que obtém o autor a partir do livro. Essa relação é o inverso da relação `hasMany` e precisa ser definida no modelo filho com o método `belongsTo`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\DbConnection\Model\Model;

class Book extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

Depois de definir essa relação, podemos obter o modelo `User` relacionado acessando a「propriedade dinâmica」author do modelo `Book`:

```php
$book = Book::find(1);

echo $book->author->name;
```

### Muitos para muitos

A relação muitos para muitos é um pouco mais complexa que as relações `hasOne` e `hasMany`. Por exemplo, um usuário pode ter muitos tipos de papéis, e esses papéis também são compartilhados por outros usuários. Por exemplo, muitos usuários podem ter o papel「administrador」. Para definir esse tipo de relação, são necessárias três tabelas de banco de dados: `users`, `roles` e `role_user`. O nome da tabela `role_user` é formado pelos dois modelos relacionados em ordem alfabética e contém os campos `user_id` e `role_id`.

A relação muitos para muitos é definida pelo resultado retornado pela chamada ao método interno `belongsToMany`. Por exemplo, definimos o método `roles` no modelo `User`:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
```

Uma vez definida a relação, você pode obter os papéis do usuário pela propriedade dinâmica `roles`:

```php
$user = User::query()->find(1);

foreach ($user->roles as $role) {
    //
}
```

É claro que, como em todos os outros modelos relacionados, você pode usar o método `roles` e aproveitar o encadeamento para adicionar condições de restrição à consulta:

```php
$roles = User::find(1)->roles()->orderBy('name')->get();
```

Como mencionado anteriormente, para determinar o nome da tabela de junção da relação, o `Hyperf` concatena os nomes dos dois modelos relacionados em ordem alfabética. É claro que você também pode não usar essa convenção, passando o segundo parâmetro ao método belongsToMany:

```php
return $this->belongsToMany(Role::class, 'role_user');
```

Além de personalizar o nome da tabela de junção, você também pode definir os nomes das chaves dos campos dessa tabela passando parâmetros adicionais ao método `belongsToMany`. O terceiro parâmetro é o nome da chave estrangeira do modelo que define essa relação na tabela de junção, e o quarto parâmetro é o nome da chave estrangeira do outro modelo na tabela de junção:

```php
return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
```

#### Obter campos da tabela intermediária

Como você acabou de entender, a relação muitos para muitos precisa do suporte de uma tabela intermediária, e o `Hyperf` oferece alguns métodos úteis para interagir com essa tabela. Por exemplo, suponha que nosso objeto `User` esteja relacionado a vários objetos `Role`. Após obter esses objetos relacionados, você pode acessar os dados da tabela intermediária pela propriedade `pivot` do modelo:

```php
$user = User::find(1);

foreach ($user->roles as $role) {
    echo $role->pivot->created_at;
}
```

Observe que cada objeto `Role` obtido receberá automaticamente a propriedade `pivot`, que representa um objeto de modelo da tabela intermediária e pode ser usado como qualquer outro modelo do `Hyperf`.

Por padrão, o objeto `pivot` contém apenas as chaves primárias dos dois modelos relacionados. Se a sua tabela intermediária tiver outros campos adicionais, você precisa indicá-los explicitamente ao definir a relação:

```php
return $this->belongsToMany(Role::class)->withPivot('column1', 'column2');
```

Se você quiser que a tabela intermediária mantenha automaticamente os timestamps `created_at` e `updated_at`, basta adicionar o método `withTimestamps` ao definir a relação:

```php
return $this->belongsToMany(Role::class)->withTimestamps();
```

#### Personalizar o nome da propriedade `pivot`

Como mencionado anteriormente, as propriedades provenientes da tabela intermediária podem ser acessadas pela propriedade `pivot`. No entanto, você pode personalizar livremente o nome dessa propriedade para refletir melhor seu uso na aplicação.

Por exemplo, se a sua aplicação tiver usuários que podem assinar, pode existir uma relação muitos para muitos entre usuários e blogs. Nesse caso, você pode querer nomear o acessor da tabela intermediária como `subscription` em vez de `pivot`. Isso pode ser feito usando o método `as` ao definir a relação:

```php
return $this->belongsToMany(Podcast::class)->as('subscription')->withTimestamps();
```

Uma vez definido, você pode acessar os dados da tabela intermediária usando o nome personalizado:

```php
$users = User::with('podcasts')->get();

foreach ($users->flatMap->podcasts as $podcast) {
    echo $podcast->subscription->created_at;
}
```

#### Filtrar relações pela tabela intermediária

Ao definir a relação, você também pode usar os métodos `wherePivot` e `wherePivotIn` para filtrar os resultados retornados por `belongsToMany`:

```php
return $this->belongsToMany('App\Role')->wherePivot('approved', 1);

return $this->belongsToMany('App\Role')->wherePivotIn('priority', [1, 2]);
```

## Pré-carregamento (eager loading)

Ao acessar as relações do `Hyperf` como propriedade, os dados relacionados são「carregados sob demanda (lazy loading)」. Isso significa que os dados relacionados só serão realmente carregados no primeiro acesso à propriedade. No entanto, o `Hyperf` consegue「pré-carregar」as relações filhas ao consultar o modelo pai. O eager loading pode amenizar o problema de consultas N + 1. Para explicar o problema de consultas N + 1, considere o caso em que o modelo `User` está relacionado a `Role`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public function role()
    {
        return $this->hasOne(Role::class, 'user_id', 'id');
    }
}
```

Agora, vamos obter todos os usuários e seus respectivos papéis

```php
$users = User::query()->get();

foreach ($users as $user){
    echo $user->role->name;
}
```

Esse laço executará uma consulta para obter todos os usuários e, em seguida, uma consulta para obter o papel de cada usuário. Se tivermos 10 pessoas, esse laço executará 11 consultas: 1 para consultar os usuários e 10 consultas adicionais para os papéis correspondentes.

Felizmente, podemos usar o eager loading para reduzir a operação a apenas 2 consultas. Ao consultar, você pode usar o método with para especificar as relações que deseja pré-carregar:

```php
$users = User::query()->with('role')->get();

foreach ($users as $user){
    echo $user->role->name;
}
```

Neste exemplo, apenas duas consultas são executadas

```
SELECT * FROM `user`;

SELECT * FROM `role` WHERE id in (1, 2, 3, ...);
```

## Relações polimórficas

As relações polimórficas permitem que o modelo alvo se relacione com vários modelos por meio de uma relação.

### Um para um (polimórfica)

#### Estrutura da tabela

A relação polimórfica um para um é semelhante à relação um para um simples; porém, o modelo alvo pode pertencer a vários modelos em uma única relação.
Por exemplo, Book e User podem compartilhar uma relação com o modelo Image. Usar uma relação polimórfica um para um permite que uma única lista de imagens seja usada simultaneamente por Book e User. Vejamos primeiro a estrutura da tabela:

```
book
  id - integer
  title - string

user 
  id - integer
  name - string

image
  id - integer
  url - string
  imageable_id - integer
  imageable_type - string
```

O campo imageable_id da tabela image representa significados diferentes conforme o imageable_type; por padrão, imageable_type é diretamente o nome da classe do modelo relacionado.

#### Exemplo de modelos

```php
<?php
namespace App\Model;

class Image extends Model
{
    public function imageable()
    {
        return $this->morphTo();
    }
}

class Book extends Model
{
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}

class User extends Model
{
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
```

#### Obter relações

Após definir os modelos conforme acima, podemos obter os modelos correspondentes por meio das relações do modelo.

Por exemplo, obtemos a imagem de um usuário.

```php
use App\Model\User;

$user = User::find(1);

$image = $user->image;
```

Ou obtemos o usuário ou livro correspondente a uma imagem. O `imageable` obterá o `User` ou `Book` correspondente de acordo com o `imageable_type`.

```php
use App\Model\Image;

$image = Image::find(1);

$imageable = $image->imageable;
```

### Um para muitos (polimórfica)

#### Exemplo de modelos

```php
<?php
namespace App\Model;

class Image extends Model
{
    public function imageable()
    {
        return $this->morphTo();
    }
}

class Book extends Model
{
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}

class User extends Model
{
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
```

#### Obter relações

Obter todas as imagens do usuário

```php
use App\Model\User;

$user = User::query()->find(1);
foreach ($user->images as $image) {
    // ...
}
```

### Personalizar o mapeamento polimórfico

Por padrão, o framework exige que `type` armazene o nome da classe do modelo correspondente, por exemplo, o `imageable_type` acima deve ser o `User::class` e o `Book::class` correspondentes. Mas, obviamente, em aplicações reais isso é bastante inconveniente. Por isso, podemos personalizar o mapeamento para desacoplar o banco de dados da estrutura interna da aplicação.

```php
use App\Model;
use Hyperf\Database\Model\Relations\Relation;
Relation::morphMap([
    'user' => Model\User::class,
    'book' => Model\Book::class,
]);
```

Como `Relation::morphMap` permanece na memória após a modificação, podemos criar o mapeamento correspondente já na inicialização do projeto. Podemos criar o seguinte listener:

```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Listener;

use App\Model;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
class MorphMapRelationListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        Relation::morphMap([
            'user' => Model\User::class,
            'book' => Model\Book::class,
        ]);
    }
}

```

### Eager loading aninhado de relações `morphTo`

Se você deseja carregar uma relação `morphTo`, bem como as relações aninhadas das diversas entidades que essa relação pode retornar, pode combinar o método `with` com o método `morphWith` da relação `morphTo`.

Por exemplo, pretendemos pré-carregar a relação book.user de image.

```php

use App\Model\Book;
use App\Model\Image;
use Hyperf\Database\Model\Relations\MorphTo;

$images = Image::query()->with([
    'imageable' => function (MorphTo $morphTo) {
        $morphTo->morphWith([
            Book::class => ['user'],
        ]);
    },
])->get();
```

As consultas SQL correspondentes são as seguintes:

```sql
// Consulta todas as imagens
select * from `images`;
// Consulta a lista de usuários correspondentes às imagens
select * from `user` where `user`.`id` in (1, 2);
// Consulta a lista de livros correspondentes às imagens
select * from `book` where `book`.`id` in (1, 2, 3);
// Consulta a lista de usuários correspondentes à lista de livros
select * from `user` where `user`.`id` in (1, 2);
```

### Consulta de relações polimórficas

Para consultar a existência da relação `MorphTo`, você pode usar o método `whereHasMorph` e seus métodos correspondentes:

O exemplo abaixo consulta a lista de imagens cujo livro ou usuário tem `ID` 1.

```php
use App\Model\Book;
use App\Model\Image;
use App\Model\User;
use Hyperf\Database\Model\Builder;

$images = Image::query()->whereHasMorph(
    'imageable',
    [
        User::class,
        Book::class,
    ],
    function (Builder $query) {
        $query->where('imageable_id', 1);
    }
)->get();
```
