# Gerenciamento de sessão Session

HTTP é um protocolo sem estado, ou seja, o servidor não mantém nenhum estado das transações com o cliente. Por isso, ao desenvolver aplicações HTTP Server, normalmente usamos Session para compartilhar dados do usuário entre várias requisições. Você pode usar [hyperf/session](https://github.com/hyperf/session) para implementar a funcionalidade de Session. O componente Session atualmente possui apenas dois drivers de armazenamento adaptados: `arquivo` e `Redis`, sendo `arquivo` o driver padrão. Em ambiente de produção, recomendamos fortemente usar `Redis` como driver de armazenamento, pois tem melhor desempenho e se adequa mais ao uso em arquitetura de cluster.

# Instalação

```bash
composer require hyperf/session
```

# Configuração

A configuração do componente Session é armazenada no arquivo `config/autoload/session.php`. Se o arquivo não existir, você pode usar o comando `php bin/hyperf.php vendor:publish hyperf/session` para publicar o arquivo de configuração do componente Session no Skeleton.

## Configurando o middleware de Session

Antes de usar Session, você precisa configurar o middleware `Hyperf\Session\Middleware\SessionMiddleware` como middleware global do HTTP Server, para que o componente possa intervir no fluxo da requisição e realizar o processamento correspondente. O exemplo do arquivo de configuração `config/autoload/middlewares.php` é o seguinte:

```php
<?php

return [
    // O http aqui corresponde ao nome padrão do server; se você precisar usar Session em outro server, é necessário configurar o middleware global correspondente
    'http' => [
        \Hyperf\Session\Middleware\SessionMiddleware::class,
    ],
];
```

## Configurando o driver de armazenamento

Altere o driver de armazenamento de Session por meio da configuração `handler` no arquivo de configuração, e os itens de configuração específicos do Handler correspondente são determinados pelos diferentes itens de configuração dentro de `options`.

### Usando o driver de armazenamento em arquivo

> O driver de armazenamento em arquivo é o driver padrão, mas recomenda-se usar o driver Redis em ambiente de produção

Quando o valor de `handler` é `Hyperf\Session\Handler\FileHandler`, isso indica o uso do driver de armazenamento `arquivo`. Todos os arquivos de dados de Session serão gerados e armazenados na pasta correspondente ao valor de configuração `options.path`; a pasta configurada por padrão é a pasta `runtime/session` no diretório raiz.

### Usando o driver Redis

Antes de usar o driver de armazenamento `Redis`, você precisa instalar o componente [hyperf/redis](https://github.com/hyperf/redis). Quando o valor de `handler` é `Hyperf\Session\Handler\RedisHandler`, isso indica o uso do driver de armazenamento `Redis`. Você pode ajustar a conexão `Redis` que o driver deve usar por meio do valor de configuração `options.connection`. A conexão aqui corresponde ao nome da key na configuração `config/autoload/redis.php` do componente [hyperf/redis](https://github.com/hyperf/redis),

# Uso

## Obtendo o objeto Session

O objeto Session pode ser obtido injetando `Hyperf\Contract\SessionInterface`, e então você pode chamar os métodos definidos na interface para usá-lo:

```php
<?php

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\SessionInterface;

class IndexController
{
    #[Inject]
    private SessionInterface $session;

    public function index()
    {
        // Use diretamente por meio de $this->session
    } 
}
```

## Armazenando dados

Quando você quiser armazenar dados na Session, pode fazer isso chamando o método `set(string $name, $value): void`:

```php
<?php

$this->session->set('foo', 'bar');
```

## Obtendo dados

Quando você quiser obter dados da Session, pode fazer isso chamando o método `get(string $name, $default = null)`:

```php
<?php

$this->session->get('foo', $default = null);
```

### Obtendo todos os dados

Você pode obter de uma vez todos os dados armazenados na Session chamando o método `all(): array`:

```php
<?php

$data = $this->session->all();
```

## Verificando se existe um valor na Session

Para determinar se existe um valor na Session, você pode usar o método `has(string $name): bool`. Se o valor existir e não for null, o método `has` retornará `true`:

```php
<?php

if ($this->session->has('foo')) {
    //
}
```

## Obtendo e removendo um dado

Chamando o método `remove(string $name)`, você pode obter e remover um dado da Session usando apenas um método:

```php
<?php

$data = $this->session->remove('foo');
```

## Removendo um ou mais dados

Chamando o método `forget(string|array $name): void`, você pode remover um ou mais dados da Session usando apenas um método. Quando um string é passado, indica remover apenas um dado; quando um array de strings de key é passado, indica remover vários dados:

```php
<?php

$this->session->forget('foo');
$this->session->forget(['foo', 'bar']);
```

## Limpando os dados atuais da Session

Quando você quiser limpar todos os dados da Session atual, pode fazer isso chamando o método `clear(): void`:

```php
<?php

$this->session->clear();
```

## Obtendo o ID da Session atual

Quando você quiser obter o ID da Session atual para lidar com alguma lógica por conta própria, pode fazer isso chamando o método `getId(): string`:

```php
<?php

$sessionId = $this->session->getId();
```
