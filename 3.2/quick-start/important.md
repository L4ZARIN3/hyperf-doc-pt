# Avisos sobre programação

Aqui reunimos diversos conhecimentos e pontos que você deve saber antes de programar com o Hyperf.

## Não é possível obter parâmetros de atributos por meio de variáveis globais

No `PHP-FPM` é possível obter os parâmetros da requisição, os parâmetros do servidor, etc. por meio de variáveis globais. No `Hyperf` e no `Swoole`, **não é possível** obter nenhum parâmetro de atributo por meio de variáveis que começam com `$_`, como `$_GET/$_POST/$_REQUEST/$_SESSION/$_COOKIE/$_SERVER`.

## Classes obtidas pelo contêiner são singletons

Tudo o que é obtido pelo contêiner de injeção de dependência é persistido dentro do processo e compartilhado entre várias corrotinas; portanto, não pode conter dados exclusivos de uma requisição ou de uma corrotina. Esse tipo de dado deve ser tratado pelo contexto da corrotina. Para mais detalhes, leia atentamente os capítulos [Injeção de dependência](../di.md) e [Corrotina](../coroutine.md).

## Deploy do projeto

> O Dockerfile oficial já executa as operações a seguir.

Ao fazer o deploy do código em produção, certifique-se de habilitar o `scan_cacheable`.

Com essa configuração habilitada, na primeira varredura são geradas as classes proxy e o cache de anotações; nas inicializações seguintes, o cache pode ser usado diretamente, otimizando enormemente o uso de memória e a velocidade de inicialização. Como a etapa de varredura é ignorada, há dependência do `Composer Class Map`, por isso é obrigatório executar `--optimize-autoloader` para otimizar o índice.

Em resumo, para atualizar o código em produção, antes de reiniciar o projeto é necessário executar os seguintes comandos:

```bash
# Otimizar o índice do Composer
composer dump-autoload -o
# Gerar as classes proxy e o cache de anotações
php bin/hyperf.php
```


## Evite alternar corrotinas em métodos mágicos

> Exceto `__call` e `__callStatic`

Evite ao máximo alternar corrotinas em `__get`, `__set` e `__isset`, pois podem ocorrer situações inesperadas

```php
<?php

require_once 'vendor/autoload.php';

use function Hyperf\Coroutine\go;

Swoole\Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);

class Foo
{
    public function __get(string $name)
    {
        sleep(1);
        return $name;
    }

    public function __set(string $name, mixed $value)
    {
        sleep(1);
        var_dump($name, $value);
    }

    public function __isset(string $name): bool
    {
        sleep(1);
        var_dump($name);
        return true;
    }
}

$foo = new Foo();
go(static function () use ($foo) {
    var_dump(isset($foo->xxx));
});

go(static function () use ($foo) {
    var_dump(isset($foo->xxx));
});

\Swoole\Event::wait();
```

Ao executar o código acima, obtemos o seguinte resultado

```shell
bool(false)
string(3) "xxx"
bool(true)
```
