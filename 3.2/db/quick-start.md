# Início rápido

## Prefácio

> O [hyperf/database](https://github.com/hyperf/database) deriva do [illuminate/database](https://github.com/illuminate/database); fizemos algumas modificações e a maior parte das funcionalidades permanece igual. Agradecemos à equipe de desenvolvimento do Laravel por implementar um componente ORM tão poderoso e útil.

O componente [hyperf/database](https://github.com/hyperf/database) é derivado do [illuminate/database](https://github.com/illuminate/database) e passou por algumas modificações. Por design, ele pode ser usado em outros frameworks PHP-FPM ou em frameworks baseados em Swoole. No Hyperf, vale mencionar o componente [hyperf/db-connection](https://github.com/hyperf/db-connection), que, com base no [hyperf/pool](https://github.com/hyperf/pool), implementa o pool de conexões de banco de dados e faz uma nova abstração dos modelos. É por meio dele que o Hyperf consegue integrar o componente de banco de dados e o componente de eventos.

## Instalação

### Framework Hyperf

```bash
composer require hyperf/db-connection
```

### Outros frameworks

```bash
composer require hyperf/database
```

## Configuração

A configuração padrão é a seguinte. O banco de dados suporta configuração de múltiplos bancos, e o padrão é `default`.

|       Item de configuração        |  Tipo  |     Valor padrão      |        Observação        |
| :------------------: | :----: | :-------------: | :----------------: |
|        driver        | string |       Nenhum        |     Engine do banco de dados     |
|         host         | string |       Nenhum        |     Endereço do banco de dados     |
|       database       | string |       Nenhum        |   DB padrão do banco de dados    |
|       username       | string |       Nenhum        |    Nome de usuário do banco de dados    |
|       password       | string |      null       |     Senha do banco de dados     |
|       charset        | string |      utf8       |     Codificação do banco de dados     |
|      collation       | string | utf8_unicode_ci |     Codificação do banco de dados     |
|        prefix        | string |       ''        |   Prefixo dos modelos do banco de dados   |
|       timezone       | string |      null       |     Fuso horário do banco de dados     |
| pool.min_connections |  int   |        1        | Número mínimo de conexões no pool de conexões |
| pool.max_connections |  int   |       10        | Número máximo de conexões no pool de conexões |
| pool.connect_timeout | float  |      10.0       |  Tempo limite de espera por conexão  |
|  pool.wait_timeout   | float  |       3.0       |      Tempo limite      |
|    pool.heartbeat    |  int   |       -1        |        Heartbeat        |
|  pool.max_idle_time  | float  |      60.0       |    Tempo máximo de inatividade    |
|       options        | array  |                 |      Configuração do PDO      |

```php
<?php

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
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
        ]
    ],
];
```

Às vezes, o usuário precisa modificar a configuração padrão do PDO, por exemplo, para que todos os campos sejam retornados como string. Nesse caso, é necessário alterar o item de configuração do PDO `ATTR_STRINGIFY_FETCHES` para true.

```php
<?php

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
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
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            // Configuração padrão do framework
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            // Se o MySQL usado não for nativo ou se o DB fornecido pelo provedor de nuvem, como réplica/instância analítica, não suportar o protocolo de prepare do MySQL, defina este item como true
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
```

### Separação de leitura e escrita

Às vezes você quer que as instruções `SELECT` usem uma conexão de banco de dados e que as instruções `INSERT`, `UPDATE` e `DELETE` usem outra conexão. No `Hyperf`, isso pode ser feito facilmente, seja com consulta nativa, com o construtor de consultas ou com modelos.

Para entender como a separação de leitura e escrita é configurada, vejamos primeiro um exemplo:

```php
<?php

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'read' => [
            'host' => ['192.168.1.1'],
        ],
        'write' => [
            'host' => ['196.168.1.2'],
        ],
        'sticky'    => true,
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
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
    ],
];

```

Observe que, no exemplo acima, foram adicionadas três chaves ao array de configuração: `read`, `write` e `sticky`. As chaves `read` e `write` contêm, cada uma, um array com a chave `host`. Os demais dados de banco de dados de `read` e `write` ficam no array com a chave mysql.

Se quiser sobrescrever a configuração do array principal, basta modificar os arrays `read` e `write`. Assim, neste exemplo: 192.168.1.1 será o host da conexão de "leitura", e 192.168.1.2 será o host da conexão de "escrita". Essas duas conexões compartilharão as diversas configurações do array mysql, como credenciais do banco (usuário / senha), prefixo, codificação de caracteres etc.

`sticky` é um valor opcional que pode ser usado para ler imediatamente os registros já gravados no banco de dados dentro do ciclo de requisição atual. Se a opção `sticky` estiver habilitada e uma operação de "escrita" tiver sido executada no ciclo de requisição atual, qualquer operação de "leitura" usará a conexão de "escrita". Isso garante que os dados gravados no mesmo ciclo de requisição possam ser lidos imediatamente, evitando o problema de inconsistência de dados causado pelo atraso de replicação primário-secundário. Porém, habilitá-la ou não depende das necessidades da aplicação.

### Configuração de múltiplos bancos

A configuração de múltiplos bancos é a seguinte.

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
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
    ],
    'test'=>[
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST2', 'localhost'),
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
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
    ],
];

```

Ao usar, basta definir `connection` como `test` para usar a configuração de `test`, como abaixo.

```php
<?php

use Hyperf\DbConnection\Db;
// default
Db::select('SELECT * FROM user;');
Db::connection('default')->select('SELECT * FROM user;');

// test
Db::connection('test')->select('SELECT * FROM user;');
```

No modelo, basta modificar o campo `connection` para usar a configuração correspondente. Por exemplo, o `Model` abaixo usa a configuração `test`.

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

namespace App\Model;

/**
 * @property int $id
 * @property string $mobile
 * @property string $realname
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'test';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'mobile', 'realname'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer'];
}

```

## Executar instruções SQL nativas

Após configurar o banco de dados, você pode usar `Hyperf\DbConnection\Db` para consultar.

### Classe de consulta Query

Aqui se incluem principalmente instruções de consulta como `Select`, stored procedures com atributo `READS SQL DATA`, funções etc.   

O método `select` sempre retornará um array, e cada resultado do array é um objeto `StdClass`

```php
<?php

use Hyperf\DbConnection\Db;

$users = Db::select('SELECT * FROM `user` WHERE gender = ?',[1]);  //  retorna array 

foreach($users as $user){
    echo $user->name;
}

```

### Classe de execução Execute

Aqui se incluem principalmente instruções de execução como `Insert`, `Update`, `Delete`, stored procedures com atributo `MODIFIES SQL DATA` etc.

```php
<?php

use Hyperf\DbConnection\Db;

$inserted = Db::insert('INSERT INTO user (id, name) VALUES (?, ?)', [1, 'Hyperf']); // retorna bool indicando sucesso

$affected = Db::update('UPDATE user set name = ? WHERE id = ?', ['John', 1]); // retorna o número de linhas afetadas int

$affected = Db::delete('DELETE FROM user WHERE id = ?', [1]); // retorna o número de linhas afetadas int

$result = Db::statement("CALL pro_test(?, '?')", [1, 'your words']);  // retorna bool  CALL pro_test(?,?) é uma stored procedure com atributo MODIFIES SQL DATA
```

### Gerenciar transações de banco de dados automaticamente

Você pode usar o método `transaction` do `Db` para executar um conjunto de operações em uma transação de banco de dados. Se ocorrer uma exceção dentro da `Closure` da transação, a transação será revertida. Se a `Closure` da transação for executada com sucesso, a transação será confirmada automaticamente. Depois de usar `transaction`, você não precisa mais se preocupar em reverter ou confirmar manualmente:

```php
<?php
use Hyperf\DbConnection\Db;

Db::transaction(function () {
    Db::table('user')->update(['votes' => 1]);

    Db::table('posts')->delete();
});

```

### Gerenciar transações de banco de dados manualmente

Se você quiser iniciar uma transação manualmente e ter controle total sobre reversão e confirmação, pode usar `beginTransaction`, `commit`, `rollBack` do `Db`:

```php
use Hyperf\DbConnection\Db;

Db::beginTransaction();
try{

    // Do something...

    Db::commit();
} catch(\Throwable $ex){
    Db::rollBack();
}
```

## Exibir o SQL recém-executado

> O método atual só pode ser usado em ambiente de desenvolvimento; ele deve ser removido antes do deploy em produção, caso contrário causará vazamento de memória grave e confusão de dados.

Para registrar `SQL` em produção, use [escuta de eventos](event.md)

```php
<?php

use Hyperf\DbConnection\Db;
use Hyperf\Collection\Arr;
use App\Model\Book;

// Habilitar o recurso de registro de dados SQL
Db::enableQueryLog();

$book = Book::query()->find(1);

// Imprime os dados relacionados à última SQL
var_dump(Arr::last(Db::getQueryLog()));
```

## Lista de drivers

Diferentemente do [illuminate/database](https://github.com/illuminate/database), o [hyperf/database](https://github.com/hyperf/database) fornece por padrão apenas o driver MySQL; atualmente também fornece drivers como [PgSQL](https://github.com/hyperf/database-pgsql), [SQLite](https://github.com/hyperf/database-sqlite) e [SQL Server](https://github.com/hyperf/database-sqlserver-incubator).

Se o driver MySQL padrão não atender às necessidades de uso, você pode instalar o driver correspondente:

### Driver PgSql

#### Instalação

Requer `Swoole >= 5.1.0` e `--enable-swoole-pgsql` habilitado na compilação

```bash
composer require hyperf/database-pgsql
```

#### Arquivo de configuração

```php
// config/autoload/databases.php
return [
     // Outras configurações
    'pgsql'=>[
        'driver' => env('DB_DRIVER', 'pgsql'),
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', 'hyperf'),
        'port' => env('DB_PORT', 5432),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD'),
        'charset' => env('DB_CHARSET', 'utf8'),
    ]
];
```

### Driver SQLite

#### Instalação

Requer `Swoole >= 5.1.0` e `--enable-swoole-sqlite` habilitado na compilação

```bash
composer require hyperf/database-sqlite
```

#### Arquivo de configuração

```php
// config/autoload/databases.php
return [
     // Outras configurações
    'sqlite'=>[
        'driver' => env('DB_DRIVER', 'sqlite'),
        'host' => env('DB_HOST', 'localhost'),
        // :memory: é o banco de dados em memória; também é possível especificar o caminho absoluto do arquivo
        'database' => env('DB_DATABASE', ':memory:'),
        // other sqlite config
    ]
];
```

### Driver SQL Server

#### Instalação

> Fase de incubação; atualmente não é possível garantir que todas as funcionalidades funcionem; feedbacks de problemas são bem-vindos.

Requer `Swoole >= 5.1.0`, depende de pdo_odbc e exige `--with-swoole-odbc` habilitado na compilação

```bash
composer require hyperf/database-sqlserver-incubator
```

#### Arquivo de configuração

```php
// config/autoload/databases.php
return [
     // Outras configurações
    'sqlserver' => [
        'driver' => env('DB_DRIVER', 'sqlsrv'),
        'host' => env('DB_HOST', 'mssql'),
        'database' => env('DB_DATABASE', 'hyperf'),
        'port' => env('DB_PORT', 1443),
        'username' => env('DB_USERNAME', 'SA'),
        'password' => env('DB_PASSWORD'),
        'odbc_datasource_name' => 'DRIVER={ODBC Driver 18 for SQL Server};SERVER=127.0.0.1,1433;TrustServerCertificate=yes;database=hyperf',
        'odbc'  =>  true,
    ]
];
```
