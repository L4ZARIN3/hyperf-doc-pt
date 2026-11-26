# Cache de modelos

Em cenários de negócio de alta frequência, podemos consultar o banco de dados com frequência para obter dados de negócio. Embora haja o suporte do índice de chave primária, isso inevitavelmente impõe um grande desafio ao desempenho do banco de dados. Para esse tipo de consulta kv, podemos facilmente aliviar a pressão sobre o banco de dados usando o `cache de modelos`. Este componente implementa o cache automático de dados de Model e, ao excluir e modificar dados de modelo, exclui e modifica automaticamente o cache correspondente. Ao executar operações de incremento e decremento, os dados em cache são automaticamente incrementados e decrementados de forma correspondente.

> O cache de modelos, por enquanto, só suporta o driver de armazenamento `Redis`; outros mecanismos de armazenamento são bem-vindos com implementações enviadas pela comunidade.

## Instalação

```bash
composer require hyperf/model-cache
```

## Configuração

A configuração do cache de modelos fica por padrão em `config/autoload/databases.php`. As propriedades de configuração são as seguintes:

|       Configuração        |  Tipo  |                    Valor padrão                     |                  Observação                   |
|:-----------------:|:------:|:---------------------------------------------:|:---------------------------------------:|
|      handler      | string | Hyperf\ModelCache\Handler\RedisHandler::class |                   Nenhum                    |
|     cache_key     | string |              `mc:%s:m:%s:%s:%s`               |  `mc:prefixo do cache:m:nome da tabela:CHAVE primária:valor da chave primária`   |
|      prefix       | string |              db connection name               |                Prefixo do cache                 |
|       pool        | string |                    default                    |                 Pool de cache                  |
|        ttl        |  int   |                     3600                      |                Tempo limite                 |
|  empty_model_ttl  |  int   |                      60                       |        Tempo limite quando nenhum dado é encontrado na consulta         |
|    load_script    |  bool  |                     true                      | No mecanismo Redis, se deve usar evalSha em vez de eval |
| use_default_value |  bool  |                     false                     |          Se deve usar o valor padrão do banco de dados           |

```php
<?php
return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8'),
        'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float)env('DB_MAX_IDLE_TIME', 60),
        ],
        'cache' => [
            'handler' => \Hyperf\ModelCache\Handler\RedisHandler::class,
            'cache_key' => 'mc:%s:m:%s:%s:%s',
            'prefix' => 'default',
            'ttl' => 3600 * 24,
            'empty_model_ttl' => 3600,
            'load_script' => true,
            'use_default_value' => false,
        ]
    ],
];
```

## Uso

O uso do cache de modelos é muito simples: basta implementar a interface `Hyperf\ModelCache\CacheableInterface` no Model correspondente. Claro que o framework já fornece a implementação correspondente; basta introduzir o Trait `Hyperf\ModelCache\Cacheable`.

```php
<?php
declare(strict_types=1);

namespace App\Models;

use Hyperf\DbConnection\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property $id
 * @property $name
 * @property $gender
 * @property $created_at
 * @property $updated_at
 */
class User extends Model implements CacheableInterface
{
    use Cacheable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'gender', 'created_at', 'updated_at'];

    protected $casts = ['id' => 'integer', 'gender' => 'integer'];
}

// Consulta um único cache
/** @var int|string $id */
$model = User::findFromCache($id);

// Consulta caches em lote, retorna Hyperf\Database\Model\Collection
/** @var array $ids */
$models = User::findManyFromCache($ids);

```

Os dados correspondentes no Redis são os seguintes, em que `HF-DATA:DEFAULT` existe como placeholder no `HASH`, *portanto o usuário não deve usar `HF-DATA` como campo do banco de dados*.

```
127.0.0.1:6379> hgetall "mc:default:m:user:id:1"
 1) "id"
 2) "1"
 3) "name"
 4) "Hyperf"
 5) "gender"
 6) "1"
 7) "created_at"
 8) "2018-01-01 00:00:00"
 9) "updated_at"
10) "2018-01-01 00:00:00"
11) "HF-DATA"
12) "DEFAULT"
```

Outro ponto que merece atenção é o mecanismo de atualização do cache: dentro do framework, é implementado o listener correspondente `Hyperf\ModelCache\Listener\DeleteCacheListener`; sempre que os dados são modificados, o framework exclui ativamente os dados de cache correspondentes.
Se você não deseja que o framework exclua automaticamente o cache correspondente, pode sobrescrever ativamente o método `deleteCache` do Model e implementar o listener correspondente por conta própria.

### Modificação ou exclusão em lote

`Hyperf\ModelCache\Cacheable` assume automaticamente o método `Model::query`; basta o usuário excluir os dados da seguinte forma para limpar automaticamente os dados de cache correspondentes.

```php
<?php
// Exclui os dados do usuário do banco de dados; o framework excluirá automaticamente os dados de cache correspondentes
User::query(true)->where('gender', '>', 1)->delete();
```

### Usar valor padrão

Quando o ambiente de produção usa o cache de modelos, se os dados de cache correspondentes já foram criados, mas nesse momento, devido a mudanças na lógica, novos campos foram adicionados e o valor padrão não é do tipo `0`, `string vazia`, `null`, isso fará com que, ao consultar dados, os dados obtidos do cache sejam inconsistentes com os dados do banco de dados.

Para esse caso, podemos alterar `use_default_value` para `true` e adicionar `Hyperf\DbConnection\Listener\InitTableCollectorListener` à configuração `listener.php`, fazendo com que a aplicação Hyperf, na inicialização, obtenha ativamente as informações dos campos do banco de dados e, ao obter dados do cache, compare com elas e corrija os dados de cache.

### Controlar o tempo de cache no modelo

Além do tempo de cache padrão `ttl` configurado em `database.php`, `Hyperf\ModelCache\Cacheable` suporta configurar um tempo de cache mais refinado para o modelo:

```php
class User extends Model implements CacheableInterface
{
    use Cacheable;
    
    /**
     * Armazena em cache por 10 minutos; se retornar null, usa o tempo limite definido no arquivo de configuração
     * @return int|null
     */
    public function getCacheTTL(): ?int
    {
        return 600;
    }
}
```

### EagerLoad

Quando usamos relações de modelo, podemos resolver o problema `N+1` por meio de `load`, mas ainda é necessário consultar o banco de dados uma vez. O cache de modelos, ao reescrever `ModelBuilder`, permite que o usuário obtenha o modelo correspondente do cache sempre que possível.

> Esta funcionalidade não suporta `morphTo` nem modelos de relação que não sejam consultas apenas com `whereIn`.

São fornecidas duas formas abaixo:

1. Configurar EagerLoadListener e usar diretamente o método `loadCache`.

Modifique a configuração `listeners.php`

```php
return [
    Hyperf\ModelCache\Listener\EagerLoadListener::class,
];
```

Carregue as relações de modelo correspondentes por meio do método `loadCache`.

```php
$books = Book::findManyFromCache([1,2,3]);
$books->loadCache(['user']);

foreach ($books as $book){
    var_dump($book->user);
}
```

2. Usar EagerLoader

```php
use Hyperf\ModelCache\EagerLoad\EagerLoader;
use Hyperf\Context\ApplicationContext;

$books = Book::findManyFromCache([1,2,3]);
$loader = ApplicationContext::getContainer()->get(EagerLoader::class);
$loader->load($books, ['user']);

foreach ($books as $book){
    var_dump($book->user);
}
```

### Adaptador de cache

Você pode implementar o adaptador de cache de acordo com sua situação real; basta implementar a interface `Hyperf\ModelCache\Handler\HandlerInterface`.

O framework fornece dois Handlers para escolha:

- Hyperf\ModelCache\Handler\RedisHandler

Usa `HASH` para armazenar o cache, o que permite tratar efetivamente `Model::increment()`; a desvantagem é que, como o tipo de dados é apenas `String`, o suporte a `null` é pior.

- Hyperf\ModelCache\Handler\RedisStringHandler

Usa `String` para armazenar o cache; como os dados são serializados, suporta todos os tipos de dados. A desvantagem é que não consegue tratar efetivamente `Model::increment()`; quando o modelo chama incremento, a consistência é resolvida excluindo o cache.
