# Paginador

Quando você precisa fazer paginação de dados, pode usar o componente [hyperf/paginator](https://github.com/hyperf/paginator) para resolver seu problema de forma muito prática. Você pode encapsular um pouco sua consulta de dados para usar melhor a funcionalidade de paginação; este componente também pode ser usado em outros frameworks.   
Normalmente, sua necessidade de paginação provavelmente estará em consultas ao banco de dados, e o componente de banco de dados [hyperf/database](https://github.com/hyperf/database) já foi integrado ao componente de paginação. Assim, você pode chamar o paginador de forma muito prática ao fazer consultas de dados para implementar a paginação; consulte o capítulo [Modelo de banco de dados - Paginação](db/paginator.md).

# Instalação

```bash
composer require hyperf/paginator
```

# Uso básico

Basta existir um conjunto de dados e a necessidade de paginação para realizar a paginação instanciando uma classe `Hyperf\Paginator\Paginator`. O construtor dessa classe recebe os parâmetros `__construct($items, int $perPage, ?int $currentPage = null, array $options = [])`. Basta passar o conjunto de dados para o parâmetro `$items` na forma de `Array` ou da classe de coleção `Hyperf\Collection\Colletion`, definir a quantidade de dados por página `$perPage` e o número da página atual `$currentPage`. O parâmetro `$options` pode definir, na forma `Key-Value`, todas as propriedades dentro da instância do paginador; consulte as propriedades internas da classe do paginador.

```php
<?php

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Paginator\Paginator;
use Hyperf\Collection\Collection;

#[AutoController]
class UserController
{
    public function index(RequestInterface $request)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 2);

        // Aqui a consulta de dados é feita com base em $currentPage e $perPage; abaixo usamos Collection como substituto
        $collection = new Collection([
            ['id' => 1, 'name' => 'Tom'],
            ['id' => 2, 'name' => 'Sam'],
            ['id' => 3, 'name' => 'Tim'],
            ['id' => 4, 'name' => 'Joe'],
        ]);

        $users = array_values($collection->forPage($currentPage, $perPage)->toArray());

        return new Paginator($users, $perPage, $currentPage);
    }
}
```

# Métodos do paginador

## Obter o número da página atual

```php
<?php
$currentPage = $paginator->currentPage();
```

## Obter a quantidade de itens da página atual

```php
<?php
$count = $paginator->count();
```

## Obter o número do primeiro item da página atual

```php
<?php
$firstItem = $paginator->firstItem();
```

## Obter o número do último item da página atual

```php
<?php
$lastItem = $paginator->lastItem();
```

## Obter se há mais páginas

```php
<?php
if ($paginator->hasMorePages()) {
    // ...
}
```

## Obter a URL da página correspondente

```php
<?php
// URL da próxima página
$nextPageUrl = $paginator->nextPageUrl();
// URL da página anterior
$previousPageUrl = $paginator->previousPageUrl();
// Obter a URL da página especificada $page
$url = $paginator->url($page);
```

## Se está na primeira página

```php
<?php
$onFirstPage = $paginator->onFirstPage();
```

## Se há mais páginas

```php
<?php
$hasMorePages = $paginator->hasMorePages();
```

## Quantidade de itens por página

```php
<?php
$perPage = $paginator->perPage();
```

## Total de dados

> Hyperf\Paginator\Paginator não tem este método; é necessário usar Hyperf\Paginator\LengthAwarePaginator

```php
<?php
$total = $paginator->total();
```
