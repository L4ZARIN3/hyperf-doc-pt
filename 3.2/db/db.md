# Componente DB minimalista

[hyperf/database](https://github.com/hyperf/database) é muito poderoso, mas é inegável que sua eficiência deixa um pouco a desejar. Aqui é fornecido um componente `hyperf/db` minimalista.

## Instalação

```bash
composer require hyperf/db
```

## Publicar a configuração do componente

O arquivo de configuração deste componente está em `config/autoload/db.php`. Se o arquivo não existir, você pode publicar o arquivo de configuração no esqueleto com o comando abaixo:

```bash
php bin/hyperf.php vendor:publish hyperf/db
```

## Configuração do componente

A configuração padrão de `config/autoload/db.php` é a seguinte. O banco de dados suporta configuração de múltiplos bancos, sendo `default` o padrão.

|        Item de configuração        |  Tipo  |       Valor padrão       |               Observação               |
|:--------------------:|:------:|:------------------:|:--------------------------------:|
|        driver        | string |         Nenhum         | Mecanismo de banco de dados  |
|         host         | string |    `localhost`     |            Endereço do banco de dados            |
|         port         |  int   |        3306        |            Endereço do banco de dados            |
|       database       | string |         Nenhum         |          Banco de dados padrão           |
|       username       | string |         Nenhum         |           Nome de usuário do banco de dados           |
|       password       | string |        null        |            Senha do banco de dados            |
|       charset        | string |        utf8        |            Codificação do banco de dados            |
|      collation       | string |  utf8_unicode_ci   |            Codificação do banco de dados            |
|      fetch_mode      |  int   | `PDO::FETCH_ASSOC` |        Tipo do conjunto de resultados da consulta PDO        |
| pool.min_connections |  int   |         1          |        Número mínimo de conexões no pool de conexões        |
| pool.max_connections |  int   |         10         |        Número máximo de conexões no pool de conexões        |
| pool.connect_timeout | float  |        10.0        |         Tempo limite de espera por conexão         |
|  pool.wait_timeout   | float  |        3.0         |             Tempo limite             |
|    pool.heartbeat    |  int   |         -1         |               Heartbeat               |
|  pool.max_idle_time  | float  |        60.0        |           Tempo máximo de ociosidade           |
|       options        | array  |                    |             Configuração do PDO             |

## Métodos suportados pelo componente

A interface específica pode ser consultada em `Hyperf\DB\ConnectionInterface`.

|      Nome do método      |   Tipo de retorno   |                  Observação                   |
|:----------------:|:--------------:|:------------------------------------:|
| beginTransaction |     `void`     |          Abre transação; suporta aninhamento de transações          |
|      commit      |     `void`     |          Confirma transação; suporta aninhamento de transações          |
|     rollBack     |     `void`     |          Reverte transação; suporta aninhamento de transações          |
|      insert      |     `int`      | Insere dados, retorna o ID da chave primária; retorna 0 para chave primária não autoincremento   |
|     execute      |     `int`      |       Executa SQL, retorna o número de linhas afetadas        |
|      query       |    `array`     |        Consulta SQL, retorna a lista do conjunto de resultados         |
|      fetch       | `array, object`|     Consulta SQL, retorna a primeira linha do conjunto de resultados       |
|      connection  |     `self`     |           Especifica o banco de dados da conexão             |

## Uso

### Usar uma instância DB

```php
<?php

use Hyperf\Context\ApplicationContext;
use Hyperf\DB\DB;

$db = ApplicationContext::getContainer()->get(DB::class);

$res = $db->query('SELECT * FROM `user` WHERE gender = ?;', [1]);

```

### Usar métodos estáticos

```php
<?php

use Hyperf\DB\DB;

$res = DB::query('SELECT * FROM `user` WHERE gender = ?;', [1]);

```

### Usar funções anônimas para personalizar métodos

> Essa forma permite que o usuário opere diretamente o `PDO` ou `MySQL` subjacente, portanto é necessário tratar os problemas de compatibilidade por conta própria

Por exemplo, se quisermos executar algumas consultas usando um `fetch mode` diferente, podemos personalizar nosso próprio método da seguinte forma

```php
<?php
use Hyperf\DB\DB;

$sql = 'SELECT * FROM `user` WHERE id = ?;';
$bindings = [2];
$mode = \PDO::FETCH_OBJ;
$res = DB::run(function (\PDO $pdo) use ($sql, $bindings, $mode) {
    $statement = $pdo->prepare($sql);

    $this->bindValues($statement, $bindings);

    $statement->execute();

    return $statement->fetchAll($mode);
});
```
