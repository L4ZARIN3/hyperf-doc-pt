# Paginação de consultas

Ao usar o [hyperf/database](https://github.com/hyperf/database) para consultar dados, é possível paginar os resultados de forma muito prática em conjunto com o componente [hyperf/paginator](https://github.com/hyperf/paginator).

# Como usar

Ao consultar dados por meio do [construtor de consultas](querybuilder.md) ou de um [modelo](model.md), você pode usar o método `paginate` para tratar a paginação. Esse método define automaticamente o limite e o deslocamento de acordo com a página que o usuário está visualizando; por padrão, a página atual é detectada pelo valor do parâmetro `page` presente na requisição HTTP atual:

> Como o Hyperf atualmente não suporta views, o componente de paginação ainda não oferece suporte à renderização de views; retornar diretamente o resultado da paginação produz, por padrão, saída no formato application/json.

## Paginação com o construtor de consultas

```php
<?php
// Exibe todos os usuários da aplicação, mostrando 10 registros por página
return Db::table('users')->paginate(10);
```

## Paginação com modelo 

Você pode chamar diretamente o método `paginate` por meio de método estático para paginar:

```php
<?php
// Exibe todos os usuários da aplicação, mostrando 10 registros por página
return User::paginate(10);
```

Naturalmente, você também pode definir as condições da consulta ou outros métodos de configuração da consulta:

```php
<?php 
// Exibe todos os usuários da aplicação, mostrando 10 registros por página
return User::where('gender', 1)->paginate(10);
```

## Métodos de instância do paginador

Aqui explicamos apenas o uso do paginador em consultas de banco de dados; para mais detalhes sobre o paginador, leia o capítulo [Paginação](paginator.md).
