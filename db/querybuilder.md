# Construtor de Consultas (Query Builder)

## Introdução

O construtor de consultas de banco de dados do Hyperf oferece uma interface conveniente para criar e executar consultas ao banco de dados. Ele pode ser usado para executar a maior parte das operações de banco de dados em uma aplicação e funciona em todos os sistemas de banco de dados suportados.

O construtor de consultas do Hyperf usa vinculação de parâmetros do PDO para proteger sua aplicação contra ataques de injeção de SQL. Portanto, não é necessário escapar as strings passadas como vinculação.

Aqui são fornecidos apenas alguns tutoriais comuns; para o tutorial específico, consulte o site oficial do Laravel.
[Laravel Query Builder](https://laravel.com/docs/5.8/queries)

## Obtendo resultados

```php
use Hyperf\DbConnection\Db;

$users = Db::select('SELECT * FROM user;');
$users = Db::table('user')->get();
$users = Db::table('user')->select('name', 'gender as user_gender')->get();
```

O método `Db::select()` retorna um array, enquanto o método `get` retorna `Hyperf\Collection\Collection`. Os elementos são `stdClass`, portanto você pode retornar os dados de cada elemento com o código abaixo

```php
<?php

foreach ($users as $user) {
    echo $user->name;
}
```

### Convertendo o resultado para o formato de array

Em alguns cenários, você pode querer que o resultado da consulta use `array (Array)` em vez da estrutura de objeto `stdClass`, e o `Eloquent` também removeu a forma de configurar o `FetchMode` padrão por configuração. Nesse caso, você pode usar um listener para ouvir o evento `Hyperf\Database\Events\StatementPrepared` e alterar essa configuração:

```php
<?php
declare(strict_types=1);

namespace App\Listener;

use Hyperf\Database\Events\StatementPrepared;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use PDO;

#[Listener]
class FetchModeListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            StatementPrepared::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof StatementPrepared) {
            $event->statement->setFetchMode(PDO::FETCH_ASSOC);
        }
    }
}
```

### Obtendo o valor de uma linha

Se quiser obter o valor de uma linha, você pode usar o método `first`

```php
<?php
use Hyperf\DbConnection\Db;

$row = Db::table('user')->first(); // o sql adicionará automaticamente limit 1
var_dump($row);
```

### Obtendo um único valor

Se quiser obter um único valor, você pode usar o método `value`

```php
<?php
use Hyperf\DbConnection\Db;

$id = Db::table('user')->value('id');
var_dump($id);
```

### Obtendo os valores de uma coluna

Se quiser obter uma coleção contendo os valores de uma única coluna, você pode usar o método `pluck`. No exemplo abaixo, obteremos a coleção de títulos da tabela de funções:

```php
<?php
use Hyperf\DbConnection\Db;

$names = Db::table('user')->pluck('name');

foreach ($names as $name) {
    echo $name;
}

```

Você também pode especificar chaves personalizadas para os campos na coleção retornada:

```php
<?php
use Hyperf\DbConnection\Db;

$roles = Db::table('roles')->pluck('title', 'name');

foreach ($roles as $name => $title) {
    echo $title;
}

```

### Dividindo resultados em blocos

Se você precisar processar milhares de registros do banco de dados, pode considerar usar o método `chunk`. Esse método obtém uma pequena parte do conjunto de resultados por vez e a passa para uma função `closure` para processamento. Esse método é muito útil ao escrever `Command` que processam milhares de dados. Por exemplo, podemos dividir todos os dados da tabela user em blocos que processam 100 registros por vez:

```php
<?php
use Hyperf\DbConnection\Db;

Db::table('user')->orderBy('id')->chunk(100, function ($users) {
    foreach ($users as $user) {
        //
    }
});
```

Você pode interromper a obtenção contínua dos blocos de resultado retornando `false` no closure:

```php
use Hyperf\DbConnection\Db;

Db::table('user')->orderBy('id')->chunk(100, function ($users) {

    return false;
});
```

Se você precisar atualizar registros do banco de dados durante a divisão em blocos, os resultados dos blocos podem não coincidir com o resultado esperado. Portanto, ao atualizar registros em blocos, é melhor usar o método chunkById. Esse método pagina automaticamente os resultados com base na chave primária dos registros:

```php
use Hyperf\DbConnection\Db;

Db::table('user')->where('gender', 1)->chunkById(100, function ($users) {
    foreach ($users as $user) {
        Db::table('user')
            ->where('id', $user->id)
            ->update(['update_time' => time()]);
    }
});
```

> Ao atualizar ou excluir registros dentro do callback do bloco, qualquer alteração na chave primária ou estrangeira pode afetar a consulta do bloco. Isso pode fazer com que registros não sejam incluídos no resultado do bloco.

### Consultas de agregação

O framework também fornece métodos de agregação, como `count`, `max`, `min`, `avg`, `sum`.

```php
use Hyperf\DbConnection\Db;

$count = Db::table('user')->count();
```

#### Verificando se um registro existe

Além de poder determinar se o resultado de uma condição de consulta existe pelo método `count`, você também pode usar os métodos `exists` e `doesntExist`:

```php
return Db::table('orders')->where('finalized', 1)->exists();

return Db::table('orders')->where('finalized', 1)->doesntExist();
```

## Consultas

### Especificando uma instrução Select

Claro que você nem sempre deseja obter todas as colunas de uma tabela do banco de dados. Usando o método select, você pode personalizar uma instrução de consulta select para consultar campos específicos:

```php
$users = Db::table('user')->select('name', 'email as user_email')->get();
```

O método `distinct` força o resultado da consulta a não conter duplicatas:

```php
$users = Db::table('user')->distinct()->get();
```

Se você já tem uma instância do construtor de consultas e quer adicionar um campo à instrução de consulta existente, pode usar o método addSelect:

```php
$query = Db::table('users')->select('name');

$users = $query->addSelect('age')->get();
```

## Expressões brutas

Às vezes você precisa usar expressões brutas em uma consulta, por exemplo para implementar `COUNT(0) AS count`, e para isso é necessário usar o método `raw`.

```php
use Hyperf\DbConnection\Db;

$res = Db::table('user')->select('gender', Db::raw('COUNT(0) AS `count`'))->groupBy('gender')->get();
```

### Índice forçado

Mais de 90% dos problemas de consulta lenta em bancos de dados são causados por índices inadequados. Parte dessas consultas ocorre porque o `otimizador de consultas` do servidor de banco de dados não usa o melhor índice. Nesse caso, é necessário usar o índice forçado:

```php
Db::table(Db::raw("{$table} FORCE INDEX({$index})"));
```

### Métodos nativos

Você pode usar os métodos abaixo em vez de `Db::raw` para inserir expressões nativas em várias partes da consulta.

O método `selectRaw` pode substituir `select(Db::raw(...))`. O segundo parâmetro desse método é opcional e é um array de parâmetros de vinculação:

```php
$orders = Db::table('order')
    ->selectRaw('price * ? as price_with_tax', [1.0825])
    ->get();
```

Os métodos `whereRaw` e `orWhereRaw` injetam o `where` nativo em sua consulta. O segundo parâmetro desses dois métodos também é opcional e também é um array de parâmetros de vinculação:

```php
$orders = Db::table('order')
    ->whereRaw('price > IF(state = "TX", ?, 100)', [200])
    ->get();
```

Os métodos `havingRaw` e `orHavingRaw` podem ser usados para definir uma string nativa como valor da instrução `having`:

```php
$orders = Db::table('order')
    ->select('department', Db::raw('SUM(price) as total_sales'))
    ->groupBy('department')
    ->havingRaw('SUM(price) > ?', [2500])
    ->get();
```

O método `orderByRaw` pode ser usado para definir uma string nativa como valor da cláusula `order by`:

```php
$orders = Db::table('order')
    ->orderByRaw('updated_at - created_at DESC')
    ->get();
```

## Junções de tabelas

### Cláusula Inner Join

O construtor de consultas também permite escrever o método `join`. Para executar uma `「junção interna」` básica, você pode usar o método `join` na instância do construtor de consultas. O primeiro parâmetro passado ao método `join` é o nome da tabela que você deseja juntar, e os demais parâmetros especificam as restrições de campo da junção. Você também pode juntar várias tabelas em uma única consulta:

```php
$users = Db::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->join('orders', 'users.id', '=', 'orders.user_id')
    ->select('users.*', 'contacts.phone', 'orders.price')
    ->get();
```

### Left Join

Se você quiser usar `「junção à esquerda」` ou `「junção à direita」` em vez de `「junção interna」`, pode usar os métodos `leftJoin` ou `rightJoin`. Esses dois métodos têm o mesmo uso que o método `join`:

```php
$users = Db::table('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
$users = Db::table('users')
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### Instrução Cross Join

Use o método `crossJoin` com o nome da tabela que você deseja juntar para fazer uma `「junção cruzada」`. A junção cruzada gera o produto cartesiano entre a primeira tabela e a tabela juntada:

```php
$users = Db::table('sizes')
    ->crossJoin('colours')
    ->get();
```

### Instruções Join avançadas

Você pode especificar instruções `join` mais avançadas. Por exemplo, passar um `closure` como segundo parâmetro do método `join`. Esse `closure` recebe um objeto `JoinClause`, permitindo especificar as restrições da instrução `join`:

```php
Db::table('users')
    ->join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')->orOn(...);
    })
    ->get();
```

Se você quiser usar instruções no estilo `「where」` na junção, pode usar os métodos `where` e `orWhere` na junção. Esses métodos comparam colunas com valores, e não colunas com colunas:

```php
Db::table('users')
    ->join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')
                ->where('contacts.user_id', '>', 5);
    })
    ->get();
```

### Subconsultas de junção

Você pode usar os métodos `joinSub`, `leftJoinSub` e `rightJoinSub` para associar uma consulta como subconsulta. Cada um desses métodos recebe três parâmetros: a subconsulta, o alias da tabela e o closure que define os campos de associação:

```php
$latestPosts = Db::table('posts')
    ->select('user_id', Db::raw('MAX(created_at) as last_post_created_at'))
    ->where('is_published', true)
    ->groupBy('user_id');

$users = Db::table('users')
    ->joinSub($latestPosts, 'latest_posts', function($join) {
        $join->on('users.id', '=', 'latest_posts.user_id');
    })->get();
```

## Consultas união (Union)

O construtor de consultas também oferece um atalho para `unir` duas consultas. Por exemplo, você pode primeiro criar uma consulta e depois usar o método `union` para uni-la a uma segunda consulta:

```php
$first = Db::table('users')->whereNull('first_name');

$users = Db::table('users')
    ->whereNull('last_name')
    ->union($first)
    ->get();
```

## Instruções Where

### Instruções Where simples

Ao construir uma instância de consulta `where`, você pode usar o método `where`. A forma mais básica de chamar `where` requer três parâmetros: o primeiro é o nome da coluna, o segundo é qualquer operador suportado por um sistema de banco de dados e o terceiro é o valor com o qual a coluna será comparada.

Por exemplo, abaixo está uma consulta que verifica se o valor do campo gender é igual a 1:

```php
$users = Db::table('user')->where('gender', '=', 1)->get();
```

Por conveniência, se você apenas compara de forma simples se o valor da coluna é igual ao valor fornecido, pode passar o valor diretamente como segundo parâmetro do método `where`:

```php
$users = Db::table('user')->where('gender', 1)->get();
```

Claro que você também pode usar outros operadores para escrever a cláusula where:

```php
$users = Db::table('users')->where('gender', '>=', 0)->get();

$users = Db::table('users')->where('gender', '<>', 1)->get();

$users = Db::table('users')->where('name', 'like', 'T%')->get();
```

Você também pode passar um array de condições para a função where:

```php
$users = Db::table('user')->where([
    ['status', '=', '1'],
    ['gender', '=', '1'],
])->get();
```

Você também pode usar closures para criar arrays de consulta

```php
$users = Db::table('user')->where([
    ['status', '=', '1'],
    ['gender', '=', '1'],
    [function ($query) {
        $query->where('type', 3)->orWhere('type', 6);
    }]
])->get();
```

### Instruções Or

Você pode encadear restrições `where` e também adicionar cláusulas `or` na consulta. O método `orWhere` recebe os mesmos parâmetros que o método `where`:

```php
$users = Db::table('user')
    ->where('gender', 1)
    ->orWhere('name', 'John')
    ->get();
```

### Outras instruções Where

#### whereBetween

O método `whereBetween` verifica se o valor do campo está entre dois valores fornecidos:

```php
$users = Db::table('users')->whereBetween('votes', [1, 100])->get();
```

#### whereNotBetween

O método `whereNotBetween` verifica se o valor do campo está fora de dois valores fornecidos:

```php
$users = Db::table('users')->whereNotBetween('votes', [1, 100])->get();
```

#### whereIn / whereNotIn

O método `whereIn` verifica se o valor do campo existe no array especificado:

```php
$users = Db::table('users')->whereIn('id', [1, 2, 3])->get();
```

O método `whereNotIn` verifica se o valor do campo não existe no array especificado:

```php
$users = Db::table('users')->whereNotIn('id', [1, 2, 3])->get();
```

### Agrupamento de parâmetros

Às vezes você precisa criar cláusulas `where` mais avançadas, como `「where exists」` ou agrupamentos aninhados de parâmetros. O construtor de consultas também consegue lidar com isso. Vejamos abaixo um exemplo de agrupamento de restrições entre parênteses:

```php
Db::table('users')->where('name', '=', 'John')
    ->where(function ($query) {
        $query->where('votes', '>', 100)
                ->orWhere('title', '=', 'Admin');
    })
    ->get();
```

Como você pode ver, ao passar um `Closure` para o método `where` você constrói um construtor de consultas para restringir um grupo. Esse `Closure` recebe uma instância de consulta, e você pode usá-la para definir as restrições que devem ser incluídas. O exemplo acima gerará o seguinte SQL:

```sql
select * from users where name = 'John' and (votes > 100 or title = 'Admin')
```

> Você deve chamar esse grupo com orWhere para evitar efeitos inesperados de escopos globais.

#### Instruções Where Exists

O método `whereExists` permite usar a instrução `where exists SQL`. O método `whereExists` recebe um parâmetro `Closure`; o método `whereExists` recebe um parâmetro `Closure`, e esse closure obtém uma instância do construtor de consultas, permitindo que você defina a consulta colocada na cláusula `exists`:

```php
Db::table('users')->whereExists(function ($query) {
    $query->select(Db::raw(1))
            ->from('orders')
            ->whereRaw('orders.user_id = users.id');
})
->get();
```

A consulta acima produzirá a seguinte instrução SQL:

```sql
select * from users
where exists (
    select 1 from orders where orders.user_id = users.id
)
```

#### Instruções JSON Where

O `Hyperf` também suporta consultar campos do tipo `JSON` (apenas em bancos de dados que suportam o tipo `JSON`).

```php
$users = Db::table('users')
    ->where('options->language', 'en')
    ->get();

$users = Db::table('users')
    ->where('preferences->dining->meal', 'salad')
    ->get();
```

Você também pode usar `whereJsonContains` para consultar arrays `JSON`:

```php
$users = Db::table('users')
    ->whereJsonContains('options->languages', 'en')
    ->get();
```

Você pode usar `whereJsonLength` para consultar o comprimento de um array `JSON`:

```php
$users = Db::table('users')
    ->whereJsonLength('options->languages', 0)
    ->get();

$users = Db::table('users')
    ->whereJsonLength('options->languages', '>', 1)
    ->get();
```

## Ordering, Grouping, Limit e Offset

### orderBy

O método `orderBy` permite ordenar o conjunto de resultados pelo campo fornecido. O primeiro parâmetro de `orderBy` deve ser o campo pelo qual você deseja ordenar, e o segundo parâmetro controla a direção da ordenação, podendo ser `asc` ou `desc`

```php
$users = Db::table('users')
    ->orderBy('name', 'desc')
    ->get();
```

### latest / oldest

Os métodos `latest` e `oldest` permitem ordenar facilmente por data. Por padrão, ele usa a coluna `created_at` como base de ordenação. Claro que você também pode passar um nome de coluna personalizado:

```php
$user = Db::table('users')->latest()->first();
```

### inRandomOrder

O método `inRandomOrder` é usado para ordenar os resultados aleatoriamente. Por exemplo, você pode usar esse método para encontrar um usuário de forma aleatória.

```php
$randomUser = Db::table('users')->inRandomOrder()->first();
```

### groupBy / having

Os métodos `groupBy` e `having` podem agrupar os resultados. O uso do método `having` é muito semelhante ao do método `where`:

```php
$users = Db::table('users')
    ->groupBy('account_id')
    ->having('account_id', '>', 100)
    ->get();
```

Você pode passar vários parâmetros ao método `groupBy`:

```php
$users = Db::table('users')
    ->groupBy('first_name', 'status')
    ->having('account_id', '>', 100)
    ->get();
```

> Para sintaxes having mais avançadas, consulte o método havingRaw.

### skip / take

Para limitar a quantidade de resultados retornados ou pular uma quantidade especificada de resultados, você pode usar os métodos `skip` e `take`:

```php
$users = Db::table('users')->skip(10)->take(5)->get();
```

Ou você também pode usar os métodos limit e offset:

```php
$users = Db::table('users')->offset(10)->limit(5)->get();
```

## Instruções condicionais

Às vezes você pode querer que uma cláusula só execute a consulta quando uma determinada condição for verdadeira. Por exemplo, você pode querer aplicar a instrução `where` apenas quando o valor fornecido existir na requisição. Você pode fazer isso usando o método `when`:

```php
$role = $request->input('role');

$users = Db::table('users')
    ->when($role, function ($query, $role) {
        return $query->where('role_id', $role);
    })
    ->get();
```

O método `when` só executa o closure fornecido quando o primeiro parâmetro é `true`. Se o primeiro parâmetro for `false`, esse closure não será executado

Você pode passar outro closure como terceiro parâmetro do método `when`. Esse closure será executado quando o primeiro parâmetro for `false`. Para ilustrar como usar esse recurso, vamos configurar uma ordenação padrão para uma consulta:

```php
$sortBy = null;

$users = Db::table('users')
    ->when($sortBy, function ($query, $sortBy) {
        return $query->orderBy($sortBy);
    }, function ($query) {
        return $query->orderBy('name');
    })
    ->get();
```

## Inserção

O construtor de consultas também fornece o método `insert` para inserir registros no banco de dados. O método `insert` recebe um array com nomes e valores de campos para realizar a operação de inserção:

```php
Db::table('users')->insert(
    ['email' => 'john@example.com', 'votes' => 0]
);
```

Você pode até passar um array ao método `insert` para inserir vários registros na tabela

```php
Db::table('users')->insert([
    ['email' => 'taylor@example.com', 'votes' => 0],
    ['email' => 'dayle@example.com', 'votes' => 0]
]);
```

### ID autoincremento

Se a tabela de dados tiver um `ID` autoincremento, use o método `insertGetId` para inserir o registro e retornar o valor do `ID`

```php
$id = Db::table('users')->insertGetId(
    ['email' => 'john@example.com', 'votes' => 0]
);
```

## Atualização

Claro que, além de inserir registros no banco de dados, o construtor de consultas também pode atualizar registros existentes pelo método `update`. O método `update`, assim como o método `insert`, aceita um array contendo os campos a serem atualizados e seus valores. Você pode restringir a consulta `update` por meio da cláusula `where`:

```php
Db::table('users')->where('id', 1)->update(['votes' => 1]);
```

### Atualizar ou inserir

Às vezes você pode querer atualizar um registro existente no banco de dados ou criá-lo caso não exista um registro correspondente. Nesse caso, você pode usar o método `updateOrInsert`. O método `updateOrInsert` aceita dois parâmetros: um array de condições usado para localizar o registro e um array de pares chave-valor contendo os dados do registro a ser alterado.

O método `updateOrInsert` primeiro tentará localizar um registro correspondente no banco de dados usando as chaves e os valores do primeiro parâmetro. Se o registro existir, ele atualizará o registro com os valores do segundo parâmetro. Se o registro não for encontrado, um novo registro será inserido, e os dados atualizados serão a união dos dois arrays:

```php
Db::table('users')->updateOrInsert(
    ['email' => 'john@example.com', 'name' => 'John'],
    ['votes' => '2']
);
```

### Atualizando campos JSON

Ao atualizar campos JSON, você pode usar a sintaxe -> para acessar o valor correspondente no objeto JSON; essa operação só é suportada no MySQL 5.7+:

```php
Db::table('users')->where('id', 1)->update(['options->enabled' => true]);
```

### Incremento e decremento

O construtor de consultas também fornece métodos convenientes para incrementar ou decrementar um determinado campo. Esses métodos oferecem uma interface mais expressiva e concisa do que escrever manualmente uma instrução `update`.

Os dois métodos recebem ao menos um parâmetro: a coluna que precisa ser modificada. O segundo parâmetro é opcional e controla a quantidade de incremento ou decremento da coluna:

```php
Db::table('users')->increment('votes');

Db::table('users')->increment('votes', 5);

Db::table('users')->decrement('votes');

Db::table('users')->decrement('votes', 5);
```

Você também pode especificar os campos a serem atualizados durante a operação:

```php
Db::table('users')->increment('votes', 1, ['name' => 'John']);
```

## Exclusão

O construtor de consultas também pode usar o método `delete` para excluir registros de uma tabela. Antes de usar `delete`, você pode adicionar uma cláusula `where` para restringir a instrução `delete`:

```php
Db::table('users')->delete();

Db::table('users')->where('votes', '>', 100)->delete();
```

Se você precisar esvaziar a tabela, pode usar o método `truncate`, que excluirá todas as linhas e redefinirá o `ID` autoincremento para zero:

```php
Db::table('users')->truncate();
```

## Bloqueio pessimista

O construtor de consultas também inclui algumas funções que ajudam a implementar `「bloqueio pessimista」` na instrução `select`. Se quiser implementar um `「bloqueio compartilhado」` na consulta, você pode usar o método `sharedLock`. O bloqueio compartilhado impede que as colunas de dados selecionadas sejam alteradas até que a transação seja confirmada

```php
Db::table('users')->where('votes', '>', 100)->sharedLock()->get();
```

Ou você pode usar o método `lockForUpdate`. O uso do bloqueio `「update」` evita que as linhas sejam modificadas ou selecionadas por outros bloqueios compartilhados:

```php
Db::table('users')->where('votes', '>', 100)->lockForUpdate()->get();
```

