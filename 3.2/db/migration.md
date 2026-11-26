# Migração de banco de dados

A migração de banco de dados pode ser entendida como o gerenciamento de versões da estrutura do banco de dados, podendo resolver efetivamente o gerenciamento da estrutura do banco de dados entre membros de uma equipe.

> A localização da declaração dos scripts relacionados foi movida do componente database para o componente devtool; portanto, no ambiente online `--no-dev`, é necessário escrever manualmente os comandos executáveis na configuração `autoload/commands.php`.

# Gerar migração

Use `gen:migration` para gerar um arquivo de migração; após o comando vem um parâmetro de nome de arquivo, geralmente o que essa migração pretende fazer.

```bash
php bin/hyperf.php gen:migration create_users_table
```

O arquivo de migração gerado fica na pasta `migrations` no diretório raiz; cada arquivo de migração contém um timestamp para que o programa de migração determine a ordem das migrações.

A opção `--table` pode ser usada para especificar o nome da tabela de dados; o nome de tabela especificado será gerado por padrão no arquivo de migração.   
A opção `--create` também serve para especificar o nome da tabela de dados, mas a diferença em relação a `--table` é que essa opção gera um arquivo de migração de criação da tabela, enquanto `--table` é para um arquivo de migração de alteração da tabela.

```bash
php bin/hyperf.php gen:migration create_users_table --table=users
php bin/hyperf.php gen:migration create_users_table --create=users
```

# Estrutura da migração

A classe de migração contém por padrão `2` métodos: `up` e `down`.   
O método `up` serve para adicionar novas tabelas, campos ou índices ao banco de dados, e o método `down` é a operação inversa do `up`, contrária às operações em `up`, para ser executada no momento da reversão.

```php
<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('true', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('true');
    }
}
```

# Executar migrações

Execute o comando `migrate` para executar todos os arquivos de migração ainda não concluídos:

```bash
php bin/hyperf.php migrate
```

## Forçar a execução da migração

Algumas operações de migração são destrutivas, o que significa que podem causar perda de dados. Para impedir que alguém execute esses comandos no ambiente de produção, o sistema pedirá sua confirmação antes de executar esses comandos; mas se você quiser ignorar essas confirmações e forçar a execução do comando, pode usar o sinalizador `--force`:

```bash
php bin/hyperf.php migrate --force
```

## Reverter migrações

Se você quiser reverter a última migração, pode usar o comando `migrate:rollback` para reverter a última migração; atenção que uma migração pode conter vários arquivos de migração:

```bash
php bin/hyperf.php migrate:rollback
```

Você também pode adicionar o parâmetro `step` após o comando `migrate:rollback` para definir o número de reversões; por exemplo, o comando a seguir reverte as últimas 5 migrações:

```bash
php bin/hyperf.php migrate:rollback --step=5
```

Se você quiser reverter todas as migrações, pode usar `migrate:reset` para reverter:

```bash
php bin/hyperf.php migrate:reset
```

## Reverter e migrar

O comando `migrate:refresh` não apenas reverte as migrações como também executa em seguida o comando `migrate`, permitindo reconstruir eficientemente certas migrações:

```bash
php bin/hyperf.php migrate:refresh

// Reconstrói a estrutura do banco de dados e executa o preenchimento de dados
php bin/hyperf.php migrate:refresh --seed
```

Use o parâmetro `--step` para especificar o número de reversões e reconstruções; por exemplo, o comando a seguir reverte e reexecuta as últimas 5 migrações:

```bash
php bin/hyperf.php migrate:refresh --step=5
```

## Reconstruir o banco de dados

O comando `migrate:fresh` pode reconstruir eficientemente todo o banco de dados; esse comando primeiro exclui todos os bancos de dados e depois executa o comando `migrate`:

```bash
php bin/hyperf.php migrate:fresh

// Reconstrói a estrutura do banco de dados e executa o preenchimento de dados
php bin/hyperf.php migrate:fresh --seed
```

# Tabelas de dados

Nos arquivos de migração, a classe `Hyperf\Database\Schema\Schema` é usada principalmente para definir tabelas de dados e gerenciar o fluxo de migração.

## Criar tabela de dados

Use o método `create` para criar uma nova tabela de banco de dados. O método `create` aceita dois parâmetros: o primeiro é o nome da tabela de dados e o segundo é uma `função anônima (Closure)`, que receberá um objeto `Hyperf\Database\Schema\Blueprint` usado para definir a nova tabela de dados:

```php
<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }
}
```

Você pode usar os comandos a seguir no construtor de estrutura do banco de dados para definir as opções da tabela:

```php
// Especifica o mecanismo de armazenamento da tabela
$table->engine = 'InnoDB';
// Especifica o conjunto de caracteres padrão da tabela de dados
$table->charset = 'utf8';
// Especifica a regra de ordenação padrão da tabela de dados
$table->collation = 'utf8_unicode_ci';
// Cria uma tabela temporária
$table->temporary();
```

## Renomear tabela de dados

Se você quiser renomear uma tabela de dados, pode usar o método `rename`:

```php
Schema::rename($from, $to);
```

### Renomear tabela de dados com chave estrangeira

Antes de renomear a tabela, você deve verificar se todas as restrições de chave estrangeira na tabela têm nomes explícitos no arquivo de migração, em vez de deixar o programa de migração definir um nome por convenção; caso contrário, o nome da restrição de chave estrangeira fará referência ao nome antigo da tabela.

## Excluir tabela de dados

Para excluir uma tabela de dados existente, use os métodos `drop` ou `dropIfExists`:

```php
Schema::drop('users');

Schema::dropIfExists('users');
```

## Verificar se a tabela de dados ou o campo existe

Você pode usar os métodos `hasTable` e `hasColumn` para verificar se a tabela de dados ou o campo existe:

```php
if (Schema::hasTable('users')) {
    //
}

if (Schema::hasColumn('name', 'email')) {
    //
}
```

## Opções de conexão do banco de dados

Se estiver gerenciando vários bancos de dados ao mesmo tempo e migrações diferentes corresponderem a conexões de banco de dados diferentes, podemos então, no arquivo de migração, redefinir a propriedade de classe `$connection` da classe pai para definir conexões de banco de dados diferentes:

```php
<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    // Aqui corresponde à key de conexão em config/autoload/databases.php
    protected $connection = 'foo';
    
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }
}
```

# Campos

## Criar campo

Defina na `função anônima (Closure)` do segundo parâmetro do método `table` ou `create` a definição ou alteração que este arquivo de migração deve executar; por exemplo, o código abaixo define um campo de string `name`:

```php
<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{   
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('name');
        });
    }
}
```

## Métodos disponíveis para definição de campos

| Comando                                  | Descrição                                                                 |
| ---------------------------------------- | ------------------------------------------------------------------------- |
| $table->bigIncrements('id');             | ID incremental (chave primária), equivalente a"UNSIGNED BIG INTEGER"    |
| $table->bigInteger('votes');             | Equivalente a BIGINT                                                      |
| $table->binary('data');                  | Equivalente a BLOB                                                        |
| $table->boolean('confirmed');            | Equivalente a BOOLEAN                                                     |
| $table->char('name', 100);               | Equivalente a CHAR com comprimento                                        |
| $table->date('created_at');              | Equivalente a DATE                                                        |
| $table->dateTime('created_at');          | Equivalente a DATETIME                                                    |
| $table->dateTimeTz('created_at');        | Equivalente a DATETIME com fuso horário                                   |
| $table->decimal('amount', 8, 2);         | Equivalente a DECIMAL com precisão e escala                               |
| $table->double('amount', 8, 2);          | Equivalente a DOUBLE com precisão e escala                                |
| $table->enum('level', ['easy', 'hard']); | Equivalente a ENUM                                                        |
| $table->float('amount', 8, 2);           | Equivalente a FLOAT com precisão e escala                                 |
| $table->geometry('positions');           | Equivalente a GEOMETRY                                                    |
| $table->geometryCollection('positions'); | Equivalente a GEOMETRYCOLLECTION                                          |
| $table->increments('id');                | ID incremental (chave primária), equivalente a"UNSIGNED INTEGER"        |
| $table->integer('votes');                | Equivalente a INTEGER                                                     |
| $table->ipAddress('visitor');            | Equivalente a endereço IP                                                 |
| $table->json('options');                 | Equivalente a JSON                                                        |
| $table->jsonb('options');                | Equivalente a JSONB                                                       |
| $table->lineString('positions');         | Equivalente a LINESTRING                                                  |
| $table->longText('description');         | Equivalente a LONGTEXT                                                    |
| $table->macAddress('device');            | Equivalente a endereço MAC                                                |
| $table->mediumIncrements('id');          | ID incremental (chave primária), equivalente a"UNSIGNED MEDIUM INTEGER" |
| $table->mediumInteger('votes');          | Equivalente a MEDIUMINT                                                   |
| $table->mediumText('description');       | Equivalente a MEDIUMTEXT                                                  |
| $table->morphs('taggable');              | Equivalente a adicionar taggable_id incremental e taggable_type de string |
| $table->multiLineString('positions');    | Equivalente a MULTILINESTRING                                             |
| $table->multiPoint('positions');         | Equivalente a MULTIPOINT                                                  |
| $table->multiPolygon('positions');       | Equivalente a MULTIPOLYGON                                                |
| $table->nullableMorphs('taggable');      | Equivalente à versão anulável do campo morphs()                           |
| $table->nullableTimestamps();            | Equivalente à versão anulável do campo timestamps()                       |
| $table->point('position');               | Equivalente a POINT                                                       |
| $table->polygon('positions');            | Equivalente a POLYGON                                                     |
| $table->rememberToken();                 | Equivalente ao campo remember_token do tipo VARCHAR (100) anulável        |
| $table->smallIncrements('id');           | ID incremental (chave primária), equivalente a"UNSIGNED SMALL INTEGER"  |
| $table->smallInteger('votes');           | Equivalente a SMALLINT                                                    |
| $table->softDeletes();                   | Equivalente a adicionar um campo deleted_at anulável para exclusão suave  |
| $table->softDeletesTz();                 | Equivalente a adicionar um campo deleted_at anulável e com fuso horário para exclusão suave |
| $table->string('name', 100);             | Equivalente a VARCHAR com comprimento                                     |
| $table->text('description');             | Equivalente a TEXT                                                        |
| $table->time('sunrise');                 | Equivalente a TIME                                                        |
| $table->timeTz('sunrise');               | Equivalente a TIME com fuso horário                                       |
| $table->timestamp('added_on');           | Equivalente a TIMESTAMP                                                   |
| $table->timestampTz('added_on');         | Equivalente a TIMESTAMP com fuso horário                                  |
| $table->timestamps();                    | Equivalente a created_at e updated_at TIMESTAMP anuláveis                 |
| $table->timestampsTz();                  | Equivalente a created_at e updated_at TIMESTAMP anuláveis e com fuso horário |
| $table->tinyIncrements('id');            | Equivalente a UNSIGNED TINYINT com incremento automático                  |
| $table->tinyInteger('votes');            | Equivalente a TINYINT                                                     |
| $table->unsignedBigInteger('votes');     | Equivalente a Unsigned BIGINT                                             |
| $table->unsignedDecimal('amount', 8, 2); | Equivalente a UNSIGNED DECIMAL com precisão e escala                      |
| $table->unsignedInteger('votes');        | Equivalente a Unsigned INT                                                |
| $table->unsignedMediumInteger('votes');  | Equivalente a Unsigned MEDIUMINT                                          |
| $table->unsignedSmallInteger('votes');   | Equivalente a Unsigned SMALLINT                                           |
| $table->unsignedTinyInteger('votes');    | Equivalente a Unsigned TINYINT                                            |
| $table->uuid('id');                      | Equivalente a UUID                                                        |
| $table->year('birth_year');              | Equivalente a YEAR                                                        |
| $table->comment('Table Comment');        | Define o comentário da tabela, equivalente a COMMENT                      |

## Modificar campo

### Pré-requisitos

Antes de modificar um campo, certifique-se de adicionar a dependência `doctrine/dbal` ao arquivo `composer.json`. A biblioteca Doctrine DBAL é usada para determinar o estado atual do campo e criar a consulta SQL necessária para fazer os ajustes especificados no campo:

```bash
composer require "doctrine/dbal:^3.0"
```

### Atualizar propriedades do campo

O método `change` pode modificar o tipo de um campo existente para um novo tipo ou modificar outras propriedades.

```php
<?php

Schema::create('users', function (Blueprint $table) {
    // Altera o comprimento do campo para 50
    $table->string('name', 50)->change();
});
```

Ou modificar o campo para `anulável`:

```php
<?php

Schema::table('users', function (Blueprint $table) {
    // Altera o comprimento do campo para 50 e permite nulo
    $table->string('name', 50)->nullable()->change();
});
```

> Apenas os tipos de campo a seguir podem ser "modificados": bigInteger, binary, boolean, date, dateTime, dateTimeTz, decimal, integer, json, longText, mediumText, smallInteger, string, text, time, unsignedBigInteger, unsignedInteger e unsignedSmallInteger.

### Renomear campo

Você pode usar o método `renameColumn` para renomear um campo:

```php
<?php

Schema::table('users', function (Blueprint $table) {
    // Renomeia o campo de from para to
    $table->renameColumn('from', 'to')->change();
});
```

> Atualmente não há suporte para renomear campos do tipo enum.

### Excluir campo

Você pode usar o método `dropColumn` para excluir um campo:

```php
<?php

Schema::table('users', function (Blueprint $table) {
    // Exclui o campo name
    $table->dropColumn('name');
    // Exclui vários campos
    $table->dropColumn(['name', 'age']);
});
```

#### Aliases de comandos disponíveis

| Comando                      | Descrição                                      |
| ---------------------------- | ---------------------------------------------- |
| $table->dropRememberToken(); | Exclui o campo remember_token.                 |
| $table->dropSoftDeletes();   | Exclui o campo deleted_at.                     |
| $table->dropSoftDeletesTz(); | Alias do método dropSoftDeletes().             |
| $table->dropTimestamps();    | Exclui os campos created_at e updated_at.      |
| $table->dropTimestampsTz();  | Alias do método dropTimestamps().              |

## Índices

### Criar índice

###  Índice único
Use o método `unique` para criar um índice único:

```php
<?php

// Cria o índice no momento da definição
$table->string('name')->unique();
// Cria o índice após definir o campo
$table->unique('name');
```

#### Índice composto

```php
<?php

// Cria um índice composto
$table->index(['account_id', 'created_at'], 'index_account_id_and_created_at');
```

#### Definir o nome do índice

O programa de migração gera automaticamente um nome de índice razoável; cada método de índice aceita um segundo parâmetro opcional para especificar o nome do índice:

```php
<?php

// Define o nome do índice único como unique_name
$table->unique('name', 'unique_name');
// Define um índice composto com o nome index_account_id_and_created_at
$table->index(['account_id', 'created_at'], '');
```

##### Tipos de índice disponíveis

| Comando                               | Descrição             |
| ------------------------------------- | --------------------- |
| $table->primary('id');                | Adiciona chave primária |
| $table->primary(['id', 'parent_id']); | Adiciona chave composta |
| $table->unique('email');              | Adiciona índice único  |
| $table->index('state');               | Adiciona índice comum  |
| $table->spatialIndex('location');     | Adiciona índice espacial |

### Renomear índice

Você pode usar o método `renameIndex` para renomear um índice:

```php
<?php

$table->renameIndex('from', 'to');
```

### Excluir índice

Você pode usar os métodos abaixo para excluir índices; por padrão, o programa de migração simplesmente concatena o nome do banco de dados, o nome do campo do índice e o tipo de índice como nome. Exemplos:

| Comando                                                | Descrição                              |
| ------------------------------------------------------ | -------------------------------------- |
| $table->dropPrimary('users_id_primary');               | Exclui a chave primária da tabela users |
| $table->dropUnique('users_email_unique');              | Exclui o índice único da tabela users  |
| $table->dropIndex('geo_state_index');                  | Exclui o índice básico da tabela geo   |
| $table->dropSpatialIndex('geo_location_spatialindex'); | Exclui o índice espacial da tabela geo |

Você também pode passar um array de campos para o método `dropIndex`; o programa de migração gerará o nome do índice com base no nome da tabela, nos campos e no tipo de chave:

```php
<?php

Schema:table('users', function (Blueprint $table) {
    $table->dropIndex(['account_id', 'created_at']);
});
```

### Restrições de chave estrangeira

Também podemos criar restrições de chave estrangeira no nível do banco de dados através dos métodos `foreign`, `references` e `on`. Por exemplo, fazemos a tabela `posts` definir um campo `user_id` que referencia o campo `id` da tabela `users`:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->unsignedInteger('user_id');

    $table->foreign('user_id')->references('id')->on('users');
});
```

Também é possível especificar as operações desejadas para as propriedades `on delete` e `on update`:

```php
$table->foreign('user_id')
      ->references('id')->on('users')
      ->onDelete('cascade');
```

Você pode usar o método `dropForeign` para excluir uma chave estrangeira. A restrição de chave estrangeira usa a mesma convenção de nomenclatura dos índices, acrescida do sufixo `_foreign`:

```php
$table->dropForeign('posts_user_id_foreign');
```

Ou passe um array de campos para que o programa de migração gere o nome conforme as regras de convenção:

```php
$table->dropForeign(['user_id'']);
```

Você pode usar os métodos a seguir no arquivo de migração para habilitar ou desabilitar restrições de chave estrangeira:

```php
// Habilita as restrições de chave estrangeira
Schema::enableForeignKeyConstraints();
// Desabilita as restrições de chave estrangeira
Schema::disableForeignKeyConstraints();
```
