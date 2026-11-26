# Modificadores (mutators)

> Este documento foi amplamente inspirado no [LearnKu](https://learnku.com). Agradecemos muito ao LearnKu pela contribuição à comunidade PHP.

Ao obter ou definir determinados valores de propriedades em uma instância de modelo, os acessores e mutators permitem formatar os valores das propriedades do modelo.

## Acessores & Mutators

### Definir um acessor

Para definir um acessor, crie um método `getFooAttribute` no modelo; o campo `Foo` a ser acessado deve usar nomenclatura「camelCase」. Neste exemplo, definiremos um acessor para a propriedade `first_name`. Quando o modelo tentar obter a propriedade `first_name`, este acessor será chamado automaticamente:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Obtém o nome do usuário.
     *
     * @param  string  $value
     * @return string
     */
    public function getFirstNameAttribute($value)
    {
        return ucfirst($value);
    }
}
```

Como você pode ver, o valor original do campo é passado ao acessor, permitindo processá-lo e retornar o resultado. Se quiser obter o valor modificado, você pode acessar a propriedade `first_name` na instância do modelo:

```php
$user = App\User::find(1);

$firstName = $user->first_name;
```

É claro que você também pode usar valores de propriedades existentes para retornar um novo valor calculado por meio do acessor:

```php
namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Obtém o nome do usuário.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### Definir um mutator

Para definir um mutator, é necessário definir o método `setFooAttribute` no modelo. O campo `Foo` a ser acessado usa nomenclatura「camelCase」. Vamos definir um mutator para a propriedade `first_name`. Quando tentarmos definir o valor da propriedade `first_name` no modelo, esse mutator será chamado automaticamente:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Define o nome do usuário.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = strtolower($value);
    }
}
```

O mutator recebe o valor que foi definido para a propriedade, permitindo que você o modifique e defina o valor na propriedade interna `$attributes` do modelo. Por exemplo, se tentarmos definir o valor da propriedade `first_name` como `Sally`:

```php
$user = App\User::find(1);

$user->first_name = 'Sally';
```

Neste exemplo, o método `setFirstNameAttribute`, ao ser chamado, recebe o valor `Sally` como parâmetro. Em seguida, o mutator aplica a função `strtolower` e define o resultado no array interno `$attributes`.

## Conversão de datas

Por padrão, o modelo converte os campos `created_at` e `updated_at` em instâncias de `Carbon`, que herda a classe nativa `DateTime` do `PHP` e oferece vários métodos úteis. Você pode adicionar outras propriedades de data definindo a propriedade `$dates` do modelo:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * As propriedades que devem ser convertidas para formato de data.
     *
     * @var array
     */
    protected $dates = [
        'seen_at',
    ];
}

```

> Dica: você pode desabilitar os timestamps padrão `created_at` e `updated_at` definindo o valor da propriedade pública `$timestamps` do modelo como false.

Quando um campo está no formato de data, você pode definir o valor como um timestamp `UNIX`, uma string de data e hora `(Y-m-d)` ou uma instância de `DateTime` / `Carbon`. O valor de data será formatado corretamente e salvo no seu banco de dados:

Como dito acima, quando a propriedade obtida estiver incluída na propriedade `$dates`, ela será automaticamente convertida em uma instância de `Carbon`, permitindo usar qualquer método de `Carbon` na propriedade:

```php
$user = App\User::find(1);

return $user->deleted_at->getTimestamp();
```

### Formato de hora

Os timestamps serão formatados no formato `Y-m-d H:i:s`. Se você precisar personalizar o formato do timestamp, pode definir a propriedade `$dateFormat` no modelo. Essa propriedade determina em que formato as propriedades de data serão salvas no banco de dados e o formato quando o modelo for serializado em array ou `JSON`:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class Flight extends Model
{
    /**
     * Esta propriedade deve ser convertida para o tipo nativo.
     *
     * @var string
     */
    protected $dateFormat = 'U';
}
```

## Conversão de tipos de atributos

A propriedade `$casts` do modelo oferece uma forma conveniente de converter propriedades em tipos de dados comuns. A propriedade `$casts` deve ser um array, cujas chaves são os nomes das propriedades que precisam ser convertidas e cujos valores são os tipos de dados desejados.
Os tipos de dados suportados para conversão são: `integer`, `real`, `float`, `double`, `decimal:<digits>`, `string`, `boolean`, `object`, `array`, `collection`, `date`, `datetime` e `timestamp`. Quando for necessário converter para o tipo `decimal`, você precisa definir o número de casas decimais, como: `decimal:2`.

Exemplo: vamos converter a propriedade `is_admin`, armazenada no banco de dados como inteiro (`0` ou `1`), em booleano:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_admin' => 'boolean',
    ];
}
```

Agora, quando você acessar a propriedade `is_admin`, embora o valor salvo no banco de dados seja do tipo inteiro, o valor de retorno sempre será convertido para o tipo booleano:

```php
$user = App\User::find(1);

if ($user->is_admin) {
    //
}
```

### Conversão de tipos personalizada

O modelo possui vários tipos de conversão comuns integrados. Porém, ocasionalmente o usuário precisa converter dados em tipos personalizados. Agora, essa necessidade pode ser atendida definindo uma classe que implemente a interface `CastsAttributes`.

A classe que implementa essa interface deve definir antecipadamente os métodos `get` e `set`. O método `get` é responsável por converter os dados brutos obtidos do banco de dados no tipo correspondente, enquanto o método `set` converte os dados no tipo correspondente do banco de dados para armazená-los. Por exemplo, abaixo reimplementamos o tipo de conversão `json` integrado na forma de uma conversão de tipo personalizada:

```php
<?php

namespace App\Casts;

use Hyperf\Contract\CastsAttributes;

class Json implements CastsAttributes
{
    /**
     * Converte os dados obtidos
     */
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value, true);
    }

    /**
     * Converte no valor que será armazenado
     */
    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }
}
```

Depois de definir a conversão de tipo personalizada, você pode anexá-la às propriedades do modelo usando o nome da classe:

```php
<?php

namespace App;

use App\Casts\Json;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Propriedades que devem sofrer conversão de tipo
     *
     * @var array
     */
    protected $casts = [
        'options' => Json::class,
    ];
}
```

#### Conversão de tipo para objetos de valor

Você não só pode converter dados em tipos de dados nativos, mas também em objetos. As duas formas de definir conversões de tipo personalizadas são muito semelhantes. Porém, o método `set` da classe de conversão personalizada que converte dados em objetos precisa retornar um array de pares chave-valor, usado para definir os valores brutos e armazenáveis no modelo correspondente.

Por exemplo, defina uma classe de conversão de tipo personalizada para converter valores de várias propriedades do modelo em um único objeto de valor `Address`. Suponha que o objeto `Address` tenha duas propriedades públicas, `lineOne` e `lineTwo`:

```php
<?php

namespace App\Casts;

use App\Address;
use Hyperf\Contract\CastsAttributes;

class AddressCaster implements CastsAttributes
{
    /**
     * Converte os dados obtidos
     */
    public function get($model, $key, $value, $attributes): Address
    {
        return new Address(
            $attributes['address_line_one'],
            $attributes['address_line_two']
        );
    }

    /**
     * Converte no valor que será armazenado
     */
    public function set($model, $key, $value, $attributes)
    {
        return [
            'address_line_one' => $value->lineOne,
            'address_line_two' => $value->lineTwo,
        ];
    }
}
```

Após a conversão de tipo para objetos de valor, qualquer alteração de dados no objeto de valor será automaticamente sincronizada de volta ao modelo antes de salvá-lo:

```php
<?php
$user = App\User::find(1);

$user->address->lineOne = 'Updated Address Value';
$user->address->lineTwo = '#10000';

$user->save();

var_dump($user->getAttributes());
//[
//    'address_line_one' => 'Updated Address Value',
//    'address_line_two' => '#10000'
//];
```

**A implementação aqui é diferente do Laravel; se ocorrer o uso abaixo, é preciso ter atenção especial**

```php
$user = App\User::find(1);

var_dump($user->getAttributes());
//[
//    'address_line_one' => 'Address Value',
//    'address_line_two' => '#10000'
//];

$user->address->lineOne = 'Updated Address Value';
$user->address->lineTwo = '#20000';

// Após modificar diretamente os campos de address, eles não entrarão em vigor imediatamente em attributes, mas os dados modificados podem ser obtidos diretamente via $user->address.
var_dump($user->getAttributes());
//[
//    'address_line_one' => 'Address Value',
//    'address_line_two' => '#10000'
//];

// Quando salvamos ou excluímos os dados, attributes passará a conter os dados modificados.
$user->save();
var_dump($user->getAttributes());
//[
//    'address_line_one' => 'Updated Address Value',
//    'address_line_two' => '#20000'
//];
```

Se, após modificar `address`, você não quiser salvar nem obter os dados de `address_line_one` por meio de `address->lineOne`, também pode usar o método abaixo

```php
$user = App\User::find(1);
$user->address->lineOne = 'Updated Address Value';
$user->syncAttributes();
var_dump($user->getAttributes());
```

É claro que, se você ainda precisar do recurso de modificar o `value` correspondente e sincronizar automaticamente a modificação em `attributes`, pode tentar a seguinte abordagem. Primeiro, implementamos um `UserInfo` que herda de `CastsValue`.

```php
namespace App\Caster;

use Hyperf\Database\Model\CastsValue;

/**
 * @property string $name
 * @property int $gender
 */
class UserInfo extends CastsValue
{
}
```

Em seguida, implementamos o `UserInfoCaster` correspondente

```php
<?php

declare(strict_types=1);

namespace App\Caster;

use Hyperf\Contract\CastsAttributes;
use Hyperf\Collection\Arr;

class UserInfoCaster implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): UserInfo
    {
        return new UserInfo($model, Arr::only($attributes, ['name', 'gender']));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return [
            'name' => $value->name,
            'gender' => $value->gender,
        ];
    }
}

```

Quando usarmos a forma abaixo para modificar o UserInfo, será possível sincronizar a modificação com os dados de attributes.

```php
/** @var User $user */
$user = User::query()->find(100);
$user->userInfo->name = 'John1';
var_dump($user->getAttributes()); // ['name' => 'John1']
```

#### Conversão de tipo de entrada

Às vezes, você pode precisar apenas converter o tipo do valor da propriedade gravado no modelo, sem processar o valor da propriedade obtido do modelo. Um exemplo típico de conversão de tipo de entrada é o「hashing」. A classe de conversão de tipo de entrada precisa implementar a interface `CastsInboundAttributes` e implementar apenas o método `set`.

```php
<?php

namespace App\Casts;

use Hyperf\Contract\CastsInboundAttributes;

class Hash implements CastsInboundAttributes
{
    /**
     * Algoritmo de hash
     *
     * @var string
     */
    protected $algorithm;

    /**
     * Cria uma nova instância da classe de conversão de tipo
     */
    public function __construct($algorithm = 'md5')
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Converte no valor que será armazenado
     */
    public function set($model, $key, $value, $attributes)
    {
        return hash($this->algorithm, $value);
    }
}
```

#### Parâmetros de conversão de tipo

Ao anexar uma conversão de tipo personalizada ao modelo, é possível especificar os parâmetros de conversão a serem passados. Os parâmetros de conversão devem ser separados do nome da classe por `:`, e múltiplos parâmetros são separados por vírgulas. Esses parâmetros serão passados ao construtor da classe de conversão de tipo:

```php
<?php
namespace App;

use App\Casts\Json;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Propriedades que devem sofrer conversão de tipo
     *
     * @var array
     */
    protected $casts = [
        'secret' => Hash::class.':sha256',
    ];
}
```

### Conversão de array & `JSON`

Quando você armazena no banco de dados dados `JSON` serializados, a conversão do tipo `array` é muito útil. Por exemplo: se o seu banco de dados tiver um campo do tipo `JSON` ou `TEXT` serializado como `JSON` e você adicionar a conversão de tipo `array` ao modelo, ao acessá-lo ele será automaticamente convertido em um array `PHP`:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Propriedades que devem sofrer conversão de tipo
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
    ];
}
```

Uma vez definida a conversão, ao acessar a propriedade `options`, ela será automaticamente desserializada do tipo `JSON` para um array `PHP`. Quando você definir o valor da propriedade `options`, o array fornecido também será automaticamente serializado no tipo `JSON` para armazenamento:

```php
$user = App\User::find(1);

$options = $user->options;

$options['key'] = 'value';

$user->options = $options;

$user->save();
```

### Conversão de tipo Date

Ao usar as propriedades `date` ou `datetime`, é possível especificar o formato da data. Esse formato será usado quando o modelo for serializado em array ou `JSON`:

```php
<?php

namespace App;

use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    /**
     * Propriedades que devem sofrer conversão de tipo
     *
     * @var array
     */
    protected $casts = [
         'created_at' => 'datetime:Y-m-d',
    ];
}
```

### Conversão de tipo em consultas

Às vezes é necessário realizar conversão de tipo em propriedades específicas durante a execução de uma consulta, por exemplo, ao obter dados de uma tabela do banco de dados. Por exemplo, consulte a seguinte consulta:

```php
use App\Post;
use App\User;

$users = User::select([
    'users.*',
    'last_posted_at' => Post::selectRaw('MAX(created_at)')
            ->whereColumn('user_id', 'users.id')
])->get();
```

No conjunto de resultados obtido por essa consulta, a propriedade `last_posted_at` será uma string. Seria mais conveniente se realizássemos a conversão de tipo `date` ao executar a consulta. Você pode fazer isso usando o método `withCasts`:

```php
$users = User::select([
    'users.*',
    'last_posted_at' => Post::selectRaw('MAX(created_at)')
            ->whereColumn('user_id', 'users.id')
])->withCasts([
    'last_posted_at' => 'date'
])->get();
```
