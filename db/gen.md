# Script de criação de modelos

Hyperf fornece comandos para criar modelos, permitindo que você crie facilmente o modelo correspondente com base na tabela de dados. O comando gera o modelo por meio de `AST`, portanto, após adicionar alguns métodos, você também pode usar o script para redefinir o modelo de forma conveniente.

```bash
php bin/hyperf.php gen:model table_name
```

## Criar modelo

Os parâmetros opcionais são os seguintes:

|        Parâmetro        |  Tipo  |              Valor padrão               |                       Observação                        |
| :----------------: | :----: | :-------------------------------: | :-----------------------------------------------: |
|       --pool       | string |             `default`             |       Pool de conexões; o script criará com base na configuração atual do pool de conexões        |
|       --path       | string |            `app/Model`            |                     Caminho do modelo                      |
|   --force-casts    |  bool  |              `false`              |             Se deve forçar a redefinição do parâmetro `casts`             |
|      --prefix      | string |             string vazia              |                      Prefixo da tabela                       |
|   --inheritance    | string |              `Model`              |                       Classe pai                        |
|       --uses       | string | `Hyperf\DbConnection\Model\Model` |              Usado em conjunto com `inheritance`              |
| --refresh-fillable |  bool  |              `false`              |             Se deve atualizar o parâmetro `fillable`              |
|  --table-mapping   | array  |               `[]`                | Adiciona relação de mapeamento de nome de tabela -> modelo, por exemplo ['users:Account'] |
|  --ignore-tables   | array  |               `[]`                |        Nomes de tabelas que não precisam gerar modelo, por exemplo ['users']        |
|  --with-comments   |  bool  |              `false`              |                 Se deve adicionar comentários de campos                  |
|  --property-case   |  int   |                `0`                |              Tipo do campo: 0 snake_case, 1 camelCase               |

Ao usar `--property-case` para converter o tipo do campo em camelCase, também é necessário adicionar manualmente `Hyperf\Database\Model\Concerns\CamelCase` ao modelo.

A configuração correspondente também pode ser definida em `databases.{pool}.commands.gen:model`, conforme abaixo

> Os hífens (traços) precisam ser convertidos em sublinhados

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
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'gender', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer'];
}
```

## Visitors

O framework fornece alguns `Visitors` para facilitar a extensão da capacidade do script pelo usuário. O uso é muito simples: basta adicionar o `Visitor` correspondente na configuração `visitors`.

```php
<?php

declare(strict_types=1);

return [
    'default' => [
        // Ignora outras configurações
        'commands' => [
            'gen:model' => [
                'visitors' => [
                    Hyperf\Database\Commands\Ast\ModelRewriteKeyInfoVisitor::class
                ],
            ],
        ],
    ],
];
```

### Visitors opcionais

- Hyperf\Database\Commands\Ast\ModelRewriteKeyInfoVisitor

Este `Visitor` pode gerar, com base na chave primária do banco de dados, os correspondentes `$incrementing`, `$primaryKey` e `$keyType`.

- Hyperf\Database\Commands\Ast\ModelRewriteSoftDeletesVisitor

Este `Visitor` pode determinar, com base na constante `DELETED_AT`, se o modelo contém um campo de exclusão lógica; se existir, adiciona o Trait `SoftDeletes` correspondente.

- Hyperf\Database\Commands\Ast\ModelRewriteTimestampsVisitor

Este `Visitor` pode determinar automaticamente, com base em `created_at` e `updated_at`, se deve habilitar a funcionalidade padrão de registrar `horários de criação e modificação`.

- Hyperf\Database\Commands\Ast\ModelRewriteGetterSetterVisitor

Este `Visitor` pode gerar os `getter` e `setter` correspondentes com base nos campos do banco de dados.

## Sobrescrever Visitor

No framework Hyperf, ao usar `gen:model`, por padrão apenas `tinyint, smallint, mediumint, int, bigint` são declarados como tipo `int`, `bool, boolean` são declarados como tipo `boolean`, e os demais tipos de dados são `string` por padrão. Isso pode ser ajustado sobrescrevendo.

Conforme abaixo:

```php
<?php

declare(strict_types=1);

namespace App\Model;

/**
 * @property int $id
 * @property int $count
 * @property string $float_num // decimal
 * @property string $str
 * @property string $json
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserExt extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_ext';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'count', 'float_num', 'str', 'json', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'count' => 'integer', 'float_num' => 'string', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

```

Nesse momento, podemos modificar essa característica reescrevendo `ModelUpdateVisitor`.

```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Kernel\Visitor;

use Hyperf\Database\Commands\Ast\ModelUpdateVisitor as Visitor;
use Hyperf\Stringable\Str;

class ModelUpdateVisitor extends Visitor
{
    /**
     * Used by `casts` attribute.
     */
    protected function formatDatabaseType(string $type): ?string
    {
        switch ($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                return 'integer';
            case 'decimal':
                // Define como decimal e configura a precisão correspondente
                return 'decimal:2';
            case 'float':
            case 'double':
            case 'real':
                return 'float';
            case 'bool':
            case 'boolean':
                return 'boolean';
            default:
                return null;
        }
    }

    /**
     * Used by `@property` docs.
     */
    protected function formatPropertyType(string $type, ?string $cast): ?string
    {
        if (! isset($cast)) {
            $cast = $this->formatDatabaseType($type) ?? 'string';
        }

        switch ($cast) {
            case 'integer':
                return 'int';
            case 'date':
            case 'datetime':
                return '\Carbon\Carbon';
            case 'json':
                return 'array';
        }

        if (Str::startsWith($cast, 'decimal')) {
            // Se o cast for decimal, então @property passa a ser string
            return 'string';
        }

        return $cast;
    }
}
```

Configure o mapeamento de relações em `dependencies.php`

```php
<?php

return [
    Hyperf\Database\Commands\Ast\ModelUpdateVisitor::class => App\Kernel\Visitor\ModelUpdateVisitor::class,
];

```

Após executar novamente `gen:model`, o modelo correspondente é o seguinte:

```php
<?php

declare (strict_types=1);

namespace App\Model;

/**
 * @property int $id 
 * @property int $count 
 * @property string $float_num 
 * @property string $str 
 * @property string $json 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class UserExt extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_ext';
    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'count', 'float_num', 'str', 'json', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'count' => 'integer', 'float_num' => 'decimal:2', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
```
