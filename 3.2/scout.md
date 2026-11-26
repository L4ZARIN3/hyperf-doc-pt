# Pesquisa full-text em modelos

## Prefácio

> O [hyperf/scout](https://github.com/hyperf/scout) deriva do [laravel/scout](https://github.com/laravel/scout). Fizemos algumas adaptações para corrotinas, mas mantivemos a mesma API. Agradecemos aqui à equipe de desenvolvimento do Laravel por implementar um componente tão poderoso e útil. Parte desta documentação foi extraída da documentação oficial do Laravel, traduzida pela organização da comunidade Laravel China.

O Hyperf/Scout oferece uma solução simples e baseada em drivers para a pesquisa full-text de modelos. Usando observadores de modelo, o Scout sincroniza automaticamente seu índice de pesquisa com os registros do modelo.

Atualmente, o Scout já vem com um driver Elasticsearch; escrever um driver personalizado é simples, e você pode estender o Scout livremente com sua própria implementação de pesquisa.

## Instalação

### Adicionar o pacote do componente e o driver Elasticsearch

```bash
composer require hyperf/scout
composer require hyperf/elasticsearch
```

Após a instalação do Scout, use o comando vendor:publish para gerar o arquivo de configuração do Scout. Esse comando gerará um arquivo de configuração scout.php no seu diretório config.

```bash
php bin/hyperf.php vendor:publish hyperf/scout
```

Por fim, adicione o trait Hyperf\Scout\Searchable ao modelo no qual você deseja fazer pesquisas. Esse trait registrará um observador de modelo para manter o modelo e todos os drivers sincronizados:

```php
<?php

namespace App;

use Hyperf\Database\Model\Model;
use Hyperf\Scout\Searchable;

class Post extends Model
{
    use Searchable;
}
```
## Configuração

### Arquivo de configuração

Gerar o arquivo de configuração

```
php bin/hyperf.php vendor:publish hyperf/scout
```

Arquivo de configuração

```php
<?php

declare(strict_types=1);

return [
    'default' => env('SCOUT_ENGINE', 'elasticsearch'),
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'prefix' => env('SCOUT_PREFIX', ''),
    'soft_delete' => false,
    'concurrency' => 100,
    'engine' => [
        'elasticsearch' => [
            'driver' => Hyperf\Scout\Provider\ElasticsearchProvider::class,
            // Se index for definido como null, cada modelo corresponderá a um índice; caso contrário, cada modelo corresponderá a um tipo
            'index' => null,
            'hosts' => [
                env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200'),
            ],
        ],
    ],
];

```
### Configurar o índice do modelo

Cada modelo é sincronizado com um determinado「índice」de pesquisa, e esse「índice」contém todos os registros pesquisáveis do modelo. Em outras palavras, você pode imaginar cada「índice」como uma tabela de dados MySQL. Por padrão, cada modelo é persistido em um índice que corresponde ao nome da「tabela」do modelo (geralmente a forma plural do nome do modelo). Você também pode personalizar o índice do modelo sobrescrevendo o método `searchableAs` no modelo:

    <?php

    namespace App;

    use Hyperf\Scout\Searchable;
    use Hyperf\Database\Model\Model;

    class Post extends Model
    {
        use Searchable;

        /**
         * Get the index name for the model.
         *
         * @return string
         */
        public function searchableAs()
        {
            return 'posts_index';
        }
    }

<a name="configuring-searchable-data"></a>

### Configurar os dados pesquisáveis

Por padrão, o「índice」lê os dados do método `toArray` do modelo para fazer a persistência. Se você quiser personalizar os dados sincronizados para o índice de pesquisa, pode sobrescrever o método `toSearchableArray` no modelo:

    <?php

    namespace App;

    use Hyperf\Scout\Searchable;
    use Hyperf\Database\Model\Model;

    class Post extends Model
    {
        use Searchable;

        /**
         * Get the indexable data array for the model.
         *
         * @return array
         */
        public function toSearchableArray()
        {
            $array = $this->toArray();

            // Customize array...

            return $array;
        }
    }

<a name="indexing"></a>
## Indexação

<a name="batch-import"></a>
### Importação em lote

Se você quiser instalar o Scout em um projeto existente, provavelmente já terá registros de banco de dados que deseja importar para o driver de pesquisa. Use o comando `import` fornecido pelo Scout para importar todos os registros existentes para o índice de pesquisa:

    php bin/hyperf.php scout:import "App\Post"

<a name="adding-records"></a>
### Adicionar registros

Quando você adiciona o Trait `Hyperf\Scout\Searchable` ao modelo, basta fazer o `save` de uma instância do modelo e ela será automaticamente adicionada ao seu índice de pesquisa. A operação de atualização do índice será realizada ao final da corrotina, sem bloquear a requisição.

    $order = new App\Order;

    // ...

    $order->save();

#### Adição em lote

Se você quiser adicionar uma coleção de modelos ao índice de pesquisa por meio do construtor de consultas do modelo, também pode encadear o método `searchable` no construtor de consultas do modelo. O `searchable` divide o resultado da consulta do construtor em blocos e adiciona os registros ao seu índice de pesquisa.

    // Adicionar usando o construtor de consultas do modelo...
    App\Order::where('price', '>', 100)->searchable();

    // Adicionar registros usando a relação do modelo...
    $user->orders()->searchable();

    // Adicionar registros usando a coleção...
    $orders->searchable();

O método `searchable` pode ser visto como uma operação de「upsert」. Em outras palavras, se o registro do modelo já estiver no seu índice, ele será atualizado. Se não existir no índice de pesquisa, será adicionado ao índice.

<a name="updating-records"></a>
### Atualizar registros

Para atualizar um modelo pesquisável, basta atualizar as propriedades da instância do modelo e fazer o `save` do modelo no banco de dados. O Scout sincronizará automaticamente a atualização com o seu índice de pesquisa:

    $order = App\Order::find(1);

    // Atualizar order...

    $order->save();

Você também pode usar o método `searchable` na consulta do modelo para atualizar uma coleção de modelos. Se o modelo não existir no índice que você está consultando, ele será criado:

    // Atualizar usando a consulta do modelo...
    App\Order::where('price', '>', 100)->searchable();

    // Você também pode atualizar usando a relação do modelo...
    $user->orders()->searchable();

    // Você também pode atualizar usando a coleção...
    $orders->searchable();

<a name="removing-records"></a>
### Remover registros

Basta usar `delete` para excluir o modelo do banco de dados e o registro será removido do índice. Essa forma de exclusão é até compatível com modelos com soft delete:

    $order = App\Order::find(1);

    $order->delete();

Se você não quiser recuperar o modelo antes de excluir o registro, pode usar o método `unsearchable` na instância de consulta do modelo ou na coleção:

    // Excluir pela consulta do modelo...
    App\Order::where('price', '>', 100)->unsearchable();

    // Excluir pela relação do modelo...
    $user->orders()->unsearchable();

    // Excluir pela coleção...
    $orders->unsearchable();

<a name="pausing-indexing"></a>
### Pausar a indexação

Você pode precisar, ao executar um lote de operações de modelo, não sincronizar os dados do modelo com o índice de pesquisa. Nesse caso, você pode usar o método `withoutSyncingToSearch`, que é seguro para corrotinas, para executar essa operação. Esse método aceita um callback executado imediatamente. Todas as operações dentro desse callback não serão sincronizadas com o índice do modelo:

    App\Order::withoutSyncingToSearch(function () {
        // Executar ações do modelo...
    });

<a name="searching"></a>
## Pesquisa

Você pode usar o método `search` para pesquisar modelos. O método `search` aceita uma string usada para pesquisar modelos. Você também precisa encadear o método `get` na consulta de pesquisa para que os modelos correspondentes sejam consultados com a instrução de pesquisa fornecida:

    $orders = App\Order::search('Star Trek')->get();
A pesquisa do Scout retorna uma coleção de modelos, então você pode retornar o resultado diretamente da rota ou do controlador, e ele será convertido automaticamente para o formato JSON:

    Route::get('/search', function () {
        return App\Order::search([])->get();
    });

Se você quiser obter o resultado bruto antes que os modelos sejam retornados, use o método `raw`:

    $orders = App\Order::search('Star Trek')->raw();

A consulta de pesquisa normalmente é executada no índice especificado pelo método [`searchableAs`](#configurar-o-índice-do-modelo) do modelo. Naturalmente, você também pode usar o método `within` para especificar o índice personalizado no qual a pesquisa deve ser feita:

    $orders = App\Order::search('Star Trek')
        ->within('tv_shows_popularity_desc')
        ->get();

<a name="where-clauses"></a>
### Instruções Where

O Scout permite adicionar instruções「where」simples às consultas de pesquisa. Atualmente, essas instruções suportam apenas verificações básicas de igualdade numérica e são usadas principalmente em consultas de pesquisa por faixa com base no ID do proprietário. Como o índice de pesquisa não é um banco de dados relacional, instruções「where」mais avançadas não são suportadas no momento:

    $orders = App\Order::search('Star Trek')->where('user_id', 1)->get();

<a name="pagination"></a>
### Paginação

Além de recuperar coleções de modelos, você também pode usar o método `paginate` para paginar os resultados da pesquisa. Esse método retorna uma instância de `Paginator` assim como a [paginação tradicional de consultas de modelo](db/paginator.md):

    $orders = App\Order::search('Star Trek')->paginate();

Você pode especificar quantos modelos recuperar por página passando a quantidade como primeiro parâmetro do método `paginate`:

    $orders = App\Order::search('Star Trek')->paginate(15);

Depois de obter o resultado da pesquisa, você pode usar o template engine de sua preferência para renderizar os links de paginação e exibir os resultados, assim como na paginação tradicional de consultas de modelo:

    <div class="container">
        @foreach ($orders as $order)
            {{ $order->price }}
        @endforeach
    </div>

    {{ $orders->links() }}

<a name="custom-engines"></a>
## Engines personalizadas

#### Escrever uma engine

Se a engine de pesquisa embutida do Scout não atender às suas necessidades, você pode escrever uma engine personalizada e registrá-la no Scout. Sua engine precisa herdar da classe abstrata `Hyperf\Scout\Engine\Engine`, que contém os cinco métodos que sua engine personalizada deve implementar:

    use Hyperf\Scout\Builder;

    abstract public function update($models);
    abstract public function delete($models);
    abstract public function search(Builder $builder);
    abstract public function paginate(Builder $builder, $perPage, $page);
    abstract public function map($results, $model);

Consultar esses métodos na classe `Hyperf\Scout\Engine\ElasticsearchEngine` será bastante útil. Essa classe será um bom ponto de partida para você aprender como implementar esses métodos em uma engine personalizada.

#### Registrar a engine

Depois de escrever sua engine personalizada, você pode especificá-la no arquivo de configuração. Por exemplo, se você escreveu uma `MySqlSearchEngine`, pode configurá-la assim no arquivo de configuração:
```php
<?php
return [
    'default' => 'mysql',
    'engine' => [
        'mysql' => [
            'driver' => MySqlSearchEngine::class,
        ],
        'elasticsearch' => [
            'driver' => \Hyperf\Scout\Provider\ElasticsearchProvider::class,
        ],
    ],
];
```

## Diferenças em relação ao laravel/scout

- O Hyperf/Scout usa corrotinas para sincronizar com eficiência o índice de pesquisa e os registros do modelo, sem depender de mecanismos de fila.
- O Hyperf/Scout fornece por padrão a engine Elasticsearch de código aberto, em vez do Algolia de código fechado.
