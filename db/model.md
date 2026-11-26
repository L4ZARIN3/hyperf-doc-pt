# Modelo

O componente de modelo deriva do [Eloquent ORM](https://laravel.com/docs/5.8/eloquent); as operações relacionadas podem ser consultadas na documentação do Eloquent ORM.

## Criar modelo

O Hyperf fornece um comando para criar modelos, com o qual você pode criar facilmente o modelo correspondente com base na tabela de dados. O comando gera o modelo via `AST`, então, depois de adicionar alguns métodos, você também pode usar o script para redefinir o modelo facilmente.

```
php bin/hyperf.php gen:model table_name
```

Os parâmetros opcionais são os seguintes:

|      Parâmetro     |  Tipo  |           Valor padrão            |                          Observação                          |
| :----------------: | :----: | :-------------------------------: | :----------------------------------------------------------: |
|       --pool       | string |             `default`             | Pool de conexões; o script criará com base na configuração atual do pool de conexões |
|       --path       | string |            `app/Model`            |                     Caminho do modelo                        |
|   --force-casts    |  bool  |              `false`              |         Se força a redefinição do parâmetro `casts`          |
|      --prefix      | string |          string vazia             |                       Prefixo da tabela                      |
|   --inheritance    | string |              `Model`              |                         Classe pai                           |
|       --uses       | string | `Hyperf\DbConnection\Model\Model` |              Usado em conjunto com `inheritance`             |
| --refresh-fillable |  bool  |              `false`              |         Se atualiza o parâmetro `fillable`                   |
|  --table-mapping   | array  |               `[]`                | Adiciona um mapeamento de nome de tabela -> modelo, por exemplo ['users:Account'] |
|  --ignore-tables   | array  |               `[]`                | Nomes de tabelas para as quais não gerar modelo, por exemplo ['users'] |
|  --with-comments   |  bool  |              `false`              |              Se adiciona comentários de campos               |
|  --property-case   |  int   |                `0`                |        Tipo de campo: 0 snake case, 1 camel case             |

Quando você usa `--property-case` para converter o tipo de campo em camel case, também é necessário adicionar manualmente `Hyperf\Database\Model\Concerns\CamelCase` ao modelo.

As configurações correspondentes também podem ser definidas em `databases.{pool}.commands.gen:model`, assim

> Os hífens precisam ser convertidos em sublinhados

```php
<?php

declare(strict_types=1);

use Hyperf\Database\Commands\ModelOption;

return [
    'default' => [
        // Ignora outras configurações
        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
                'uses' => '',
                'refresh_fillable' => true,
                'table_mapping' => [],
                'with_comments' => true,
                'property_case' => ModelOption::PROPERTY_SNAKE_CASE,
            ],
        ],
    ],
];
```

O modelo criado é o seguinte

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property $id
 * @property $name
 * @property $gender
 * @property $created_at
 * @property $updated_at
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = ['id', 'name', 'gender', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer'];
}
```

## Variáveis de membro do modelo

|   Parâmetro  |  Tipo  | Valor padrão |             Observação              |
| :----------: | :----: | :----------: | :---------------------------------: |
|  connection  | string |   default    |       Conexão de banco de dados     |
|    table     | string |   Nenhum     |       Nome da tabela de dados       |
|  primaryKey  | string |      id      |       Chave primária do modelo      |
|   keyType    | string |     int      |      Tipo da chave primária         |
|   fillable   | array  |      []      | Propriedades que permitem atribuição em massa |
|    casts     | string |   Nenhum     |    Configuração de formatação de dados |
|  timestamps  |  bool  |     true     |   Se mantém o timestamp automaticamente |
| incrementing |  bool  |     true     |   Se a chave primária é autoincremental |

### Nome da tabela de dados

Se não especificarmos a table correspondente ao modelo, ele usará a forma plural do nome da classe em "snake case" como nome da tabela. Portanto, nesse caso, o Hyperf assumirá que o modelo User armazena dados na tabela de dados users. Você pode especificar uma tabela de dados personalizada definindo a propriedade table no modelo:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected ?string $table = 'user';
}
```

### Chave primária

O Hyperf assume que cada tabela de dados tem uma coluna de chave primária chamada id. Você pode definir uma propriedade protegida $primaryKey para substituir a convenção.

Além disso, o Hyperf assume que a chave primária é um valor inteiro autoincremental, o que significa que, por padrão, a chave primária é convertida automaticamente para o tipo int. Se você quiser usar uma chave primária não incremental ou não numérica, precisa definir a propriedade pública $incrementing como false. Se a sua chave primária não for um inteiro, você precisa definir a propriedade protegida $keyType do modelo como string.

### Timestamp

Por padrão, o Hyperf espera que sua tabela de dados tenha `created_at` e `updated_at`. Se você não quiser que o Hyperf gerencie automaticamente essas duas colunas, defina a propriedade `$timestamps` no modelo como `false`:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    public bool $timestamps = false;
}
```

Se precisar personalizar o formato do timestamp, defina a propriedade `$dateFormat` no seu modelo. Essa propriedade determina como os atributos de data são armazenados no banco de dados e o formato quando o modelo é serializado em array ou JSON:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected ?string $dateFormat = 'U';
}
```

Se você não quiser manter o armazenamento no formato `datetime`, ou quiser fazer um processamento adicional no tempo, pode implementar redefinindo o método `fromDateTime($value)` dentro do modelo.   

Se você precisar personalizar o nome do campo que armazena o timestamp, pode definir os valores das constantes `CREATED_AT` e `UPDATED_AT` no modelo; se um deles for `null`, indica que você não quer que o ORM processe esse campo:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    const CREATED_AT = 'creation_date';

    const UPDATED_AT = 'last_update';
}
```

### Conexão de banco de dados

Por padrão, o modelo do Hyperf usará a conexão de banco de dados padrão `default` configurada na sua aplicação. Se quiser especificar uma conexão diferente para o modelo, defina a propriedade `$connection`: claro que `connection-name` como `key` deve existir no arquivo de configuração `databases.php`.

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected ?string $connection = 'connection-name';
}
```

### Valores de propriedade padrão

Se quiser definir valores padrão para algumas propriedades do modelo, pode definir a propriedade `$attributes` no modelo:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected array $attributes = [
        'delayed' => false,
    ];
}
```

## Consulta de modelo

```php
<?php
use App\Model\User;

/** @var User $user */
$user = User::query()->where('id', 1)->first();
$user->name = 'Hyperf';
$user->save();

```

### Recarregar o modelo

Você pode usar os métodos `fresh` e `refresh` para recarregar o modelo. O método `fresh` recupera o modelo novamente do banco de dados. As instâncias de modelo existentes não são afetadas:

```php
<?php
use App\Model\User;

/** @var User $user */
$user = User::query()->find(1);

$freshUser = $user->fresh();
```

O método `refresh` reatribui os dados novos do banco de dados à instância existente do modelo. Além disso, os relacionamentos já carregados serão recarregados:

```php
<?php
use App\Model\User;

/** @var User $user */
$user = User::query()->where('name','Hyperf')->first();

$user->name = 'Hyperf2';

$user->refresh();

echo $user->name; // Hyperf
```

### Coleção

Os métodos `all` e `get` do modelo podem consultar vários resultados, retornando uma instância de `Hyperf\Database\Model\Collection`. A classe `Collection` fornece muitas funções auxiliares para processar os resultados da consulta:

```php
$users = $users->reject(function ($user) {
    // Rejeita todos os usuários já excluídos
    return $user->deleted;
});
```

### Recuperar um único modelo

Além de recuperar todos os registros da tabela de dados especificada, você pode usar os métodos `find` ou `first` para recuperar um único registro. Esses métodos retornam uma única instância de modelo, em vez de uma coleção de modelos:

```php
<?php
use App\Model\User;

$user = User::query()->where('id', 1)->first();

$user = User::query()->find(1);
```

### Recuperar vários modelos

Claro que o método `find` não se limita a suportar um único modelo.

```php
<?php
use App\Model\User;

$users = User::query()->find([1, 2, 3]);
```

### Exceção "não encontrado"

Às vezes você quer lançar uma exceção quando o modelo não é encontrado, o que é muito útil em controladores e rotas.    
Os métodos `findOrFail` e `firstOrFail` recuperam o primeiro resultado da consulta; se não encontrado, lançarão a exceção `Hyperf\Database\Model\ModelNotFoundException`:

```php
<?php
use App\Model\User;

$model = User::findOrFail(1);
$model = User::where('age', '>', 18)->firstOrFail();
```

### Funções de agregação

Você também pode usar `count`, `sum`, `max` e outras funções de agregação fornecidas pelo construtor de consultas (Query Builder). Esses métodos retornam apenas o valor escalar apropriado em vez de uma instância de modelo:

```php
<?php
use App\Model\User;

$count = User::query()->where('gender', 1)->count();
```

## Inserir e atualizar modelos

### Inserção

Para adicionar um novo registro ao banco de dados, primeiro crie uma nova instância de modelo, defina as propriedades da instância e então chame o método `save`:

```php
use App\Model\User;

/** @var User $user */
$user = new User();

$user->name = 'Hyperf';

$user->save();
```

Neste exemplo, atribuímos o valor à propriedade `name` da instância do modelo `App\Model\User`. Ao chamar o método `save`, um novo registro será inserido. Os timestamps `created_at` e `updated_at` serão definidos automaticamente, não sendo necessário atribuí-los manualmente.

### Atualização

O método `save` também pode ser usado para atualizar um modelo que já existe no banco de dados. Para atualizar o modelo, você precisa primeiro recuperá-lo, definir as propriedades a serem atualizadas e então chamar o método `save`. Da mesma forma, o timestamp `updated_at` será atualizado automaticamente, então também não é necessário atribuí-lo manualmente:

```php
use App\Model\User;

/** @var User $user */
$user = User::query()->find(1);

$user->name = 'Hi Hyperf';

$user->save();
```

### Atualização em massa

Também é possível atualizar vários modelos que correspondem às condições de consulta. Neste exemplo, todos os usuários com `gender` igual a `1` têm `gender_show` alterado para Masculino:

```php
use App\Model\User;

User::query()->where('gender', 1)->update(['gender_show' => 'Masculino']);
```

> Na atualização em massa, os modelos atualizados não disparam os eventos `saved` e `updated`. Isso porque, na atualização em massa, o modelo não é instanciado. Além disso, os `casts` correspondentes também não são executados; por exemplo, para o formato `json` no banco de dados, com o campo `casts` marcado como `array` na classe Model, se for usada a atualização em massa, o `array` não será convertido automaticamente para o formato de string `json` no momento da inserção.

### Atribuição em massa

Você também pode usar o método `create` para salvar um novo modelo; esse método retorna uma instância de modelo. No entanto, antes de usar, você precisa especificar a propriedade `fillable` ou `guarded` no modelo, porque todos os modelos, por padrão, não permitem atribuição em massa.

Quando um usuário passa um parâmetro inesperado por meio de uma requisição HTTP, e esse parâmetro altera campos do banco de dados que você não precisa alterar. Por exemplo: um usuário mal-intencionado pode passar o parâmetro `is_admin` por meio de uma requisição HTTP e então passá-lo ao método `create`; essa operação pode fazer o usuário se promover a administrador.

Portanto, antes de começar, você deve definir quais propriedades do modelo podem ser atribuídas em massa. Você pode fazer isso através da propriedade `$fillable` no modelo. Por exemplo: permitir que a propriedade `name` do modelo `User` seja atribuída em massa:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected array $fillable = ['name'];
}
```

Depois de definirmos as propriedades que podem ser atribuídas em massa, podemos inserir novos dados no banco de dados através do método `create`. O método `create` retornará a instância do modelo salvo:

```php
use App\Model\User;

$user = User::create(['name' => 'Hyperf']);
```

Se você já tem uma instância de modelo, pode passar um array para o método fill para atribuir valores:

```php
$user->fill(['name' => 'Hyperf']);
```

### Proteção de propriedades

`$fillable` pode ser visto como uma "lista branca" de atribuição em massa; você também pode usar a propriedade `$guarded` para isso. A propriedade `$guarded` contém um array das propriedades que não permitem atribuição em massa. Ou seja, `$guarded` funcionalmente se parece mais com uma "lista negra". Atenção: você só pode usar uma das duas, `$fillable` ou `$guarded`, não ambas ao mesmo tempo. No exemplo abaixo, exceto pela propriedade `gender_show`, todas as outras propriedades podem ser atribuídas em massa:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected $guarded = ['gender_show'];
}
```

### Outros métodos de criação

`firstOrCreate` / `firstOrNew`

Aqui estão dois métodos que você pode usar para atribuição em massa: `firstOrCreate` e `firstOrNew`.

O método `firstOrCreate` corresponderá os dados no banco de dados pela coluna / valor fornecidos. Se o modelo correspondente não for encontrado no banco de dados, ele criará um registro a partir das propriedades do primeiro parâmetro e até das propriedades do segundo parâmetro e o inserirá no banco de dados.

O método `firstOrNew` tenta encontrar o registro no banco de dados pelas propriedades fornecidas, como o método `firstOrCreate`. A diferença é que, se o método `firstOrNew` não encontrar o modelo correspondente, ele retornará uma nova instância de modelo. Atenção: a instância de modelo retornada por `firstOrNew` ainda não foi salva no banco de dados; você precisa chamar manualmente o método `save` para salvá-la:

```php
<?php
use App\Model\User;

// Procura o usuário por name; se não existir, cria...
$user = User::firstOrCreate(['name' => 'Hyperf']);

// Procura o usuário por name; se não existir, cria usando as propriedades name, gender e age...
$user = User::firstOrCreate(
    ['name' => 'Hyperf'],
    ['gender' => 1, 'age' => 20]
);

//  Procura o usuário por name; se não existir, cria uma instância...
$user = User::firstOrNew(['name' => 'Hyperf']);

// Procura o usuário por name; se não existir, cria uma instância usando as propriedades name, gender e age...
$user = User::firstOrNew(
    ['name' => 'Hyperf'],
    ['gender' => 1, 'age' => 20]
);
```

### Excluir modelo

Você pode chamar o método `delete` na instância do modelo para excluí-la:

```php
use App\Model\User;

$user = User::query()->find(1);

$user->delete();
```

### Excluir modelos por consulta

Você pode excluir dados de modelos chamando o método `delete` na consulta. Neste exemplo, excluiremos todos os usuários com `gender` igual a `1`. Assim como na atualização em massa, a exclusão em massa não dispara nenhum evento de modelo para os modelos excluídos:

```php
use App\Model\User;

// Atenção: ao usar o método delete, é necessário basear-se em algumas condições de consulta para excluir dados com segurança; sem condições where, a tabela de dados inteira será excluída
User::query()->where('gender', 1)->delete(); 
```

### Excluir dados diretamente pela chave primária

No exemplo acima, antes de chamar `delete`, é preciso primeiro buscar o modelo correspondente no banco de dados. Na verdade, se você conhece a chave primária do modelo, pode excluir os dados do modelo diretamente pelo método estático `destroy`, sem primeiro buscar no banco de dados. O método `destroy` aceita não apenas uma única chave primária como parâmetro, mas também várias chaves primárias, ou um array ou coleção para armazenar várias chaves primárias:

```php
use App\Model\User;

User::destroy(1);

User::destroy([1,2,3]);
```

### Exclusão suave

Além de excluir registros do banco de dados de verdade, o `Hyperf` também pode fazer "exclusão suave" de modelos. O modelo com exclusão suave não é realmente removido do banco de dados. Na verdade, a propriedade `deleted_at` é definida no modelo e seu valor é gravado no banco de dados. Se o valor de `deleted_at` não for nulo, significa que este modelo foi suavemente excluído. Para habilitar a funcionalidade de exclusão suave do modelo, você precisa usar o trait `Hyperf\Database\Model\SoftDeletes` no modelo

> O trait `SoftDeletes` converterá automaticamente a propriedade `deleted_at` em uma instância `DateTime / Carbon`

```php
<?php

namespace App\Model;

use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
}
```

O método `restoreOrCreate` corresponderá os dados no banco de dados pela coluna / valor fornecidos. Se o modelo correspondente for encontrado no banco de dados, ele executa o método `restore` para restaurar o modelo; caso contrário, criará um registro a partir das propriedades do primeiro parâmetro e até das propriedades do segundo parâmetro e o inserirá no banco de dados.

```php
// Procura o usuário por name; se não existir, cria usando as propriedades name, gender e age...
$user = User::restoreOrCreate(
    ['name' => 'Hyperf'],
    ['gender' => 1, 'age' => 20]
);
```

## Tipo Bit

Por padrão, no processo de conversão do modelo de banco de dados para SQL no Hyperf, os valores dos parâmetros são uniformemente convertidos para o tipo String, para resolver o problema de int com números grandes e facilitar a correspondência do tipo de valor com o índice. Se você quiser que o `ORM` suporte o tipo `bit`, basta adicionar o código do listener de evento abaixo.

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Database\Connection;
use Hyperf\Database\MySqlBitConnection;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
class SupportMySQLBitListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        Connection::resolverFor('mysql', static function ($connection, $database, $prefix, $config) {
            return new MySqlBitConnection($connection, $database, $prefix, $config);
        });
    }
}

```
