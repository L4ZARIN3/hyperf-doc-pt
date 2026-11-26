# Perguntas frequentes

## Nome curto do Swoole não desabilitado

```
[ERROR] Swoole short function names must be disabled before the server starts, please set swoole.use_shortname = 'Off' in your php.ini.
```

Você precisa adicionar o item de configuração `swoole.use_shortname = 'Off'` no seu arquivo de configuração php.ini

> Atenção: essa configuração deve ser feita no php.ini e não pode ser sobrescrita pela função ini_set()

Claro, também é possível iniciar o serviço com o comando abaixo, desabilitando a funcionalidade de nome curto do Swoole ao executar o comando PHP

```
php -d swoole.use_shortname=Off bin/hyperf.php start
```

## Perda de mensagens da fila assíncrona

Se, ao usar o componente `async-queue`, você perceber que o método em `handle` não é executado, verifique primeiro as seguintes situações:

1. Se o `Redis` é compartilhado com outras pessoas e as mensagens foram consumidas por elas
2. Se há processos locais remanescentes consumindo as mensagens

A seguir, uma solução infalível:

1. killall php
2. Alterar o `channel` da configuração do `async-queue`

## Erro `Swoole\Error: API must be called in the coroutine` ao usar o componente AMQP

Basta alterar `params.close_on_destruct` para `false` no arquivo de configuração `config/autoload/amqp.php`.

## Código não tem efeito

Quando o código modificado não tiver efeito, execute o comando abaixo

```bash
composer dump-autoload -o
```

Na fase de desenvolvimento, não defina `scan_cacheable` como `true`, pois isso fará com que, quando existir `cache do coletor`, os arquivos não sejam escaneados novamente. Além disso, o `Dockerfile` do pacote skeleton oficial habilita essa configuração por padrão; quem desenvolve em ambiente `Docker` deve ficar atento a isso.

> Quando a variável de ambiente SCAN_CACHEABLE existir, essa configuração não pode ser modificada no .env.

## Erro de sintaxe impede a inicialização do serviço

Quando, ao iniciar o projeto, for lançado um erro semelhante ao abaixo

```
Fatal error: Uncaught PhpParser\Error: Syntax error, unexpected T_STRING on line 27 in vendor/nikic/php-parser/lib/PhpParser/ParserAbstract.php:315
```

você pode executar o script `composer analyse` para fazer uma análise estática do projeto, e assim encontrar o trecho de código com problema.

Esse problema geralmente é causado pela atualização da versão 3.0.5 de [zircote/swagger](https://github.com/zircote/swagger-php); para detalhes, consulte [#834](https://github.com/zircote/swagger-php/issues/834).
Se [hyperf/swagger](https://github.com/hyperf/swagger) estiver instalado, recomenda-se fixar a versão de [zircote/swagger](https://github.com/zircote/swagger-php) em 3.0.4

## Limite de memória muito pequeno impede a execução do projeto

O `memory_limit` padrão do PHP é de apenas `128M`.

Podemos executar com `php -d memory_limit=-1 bin/hyperf.php start`, ou modificar o arquivo de configuração `php.ini`

```
# Verificar a localização do arquivo de configuração php.ini
php --ini

# Modificar a configuração memory_limit
memory_limit=-1
```

## Erro `Error while injecting dependencies into ... No entry or class found ...` ao usar `#[Inject]` em Trait

Se o Trait injeta uma propriedade por meio de `#[Inject] @var`, e ao mesmo tempo a subclasse usa com `use` uma classe de mesmo nome de um namespace diferente, o nome da classe no Trait será sobrescrito, o que fará a injeção falhar:

```php
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Di\Annotation\Inject;

trait TestTrait
{
    #[Inject]
    protected ResponseInterface $response;
}
```

Como acima, a classe Trait injeta `Hyperf\HttpServer\Contract\ResponseInterface`; se a subclasse usar a classe `ResponseInterface` de um namespace diferente, como `use Psr\Http\Message\ResponseInterface`, o nome original da classe no Trait será sobrescrito:

```php
// Usar o mesmo nome de classe sobrescreve o Trait
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    use TestTrait;
}
// Error while injecting dependencies into App\Controller\IndexController: No entry or class found for 'Psr\Http\Message\ResponseInterface'
```

O problema acima pode ser resolvido das duas formas a seguir:

- A subclasse altera o alias com `as`: `use Psr\Http\Message\ResponseInterface as PsrResponseInterface;`
- O Trait usa a restrição de tipo de propriedade acima do `PHP7.4`: `protected ResponseInterface $response;`

## Extensão Grpc ou Pcntl não instalado impede a inicialização do projeto

- O escaneamento de anotações da versão v2.2 usa a extensão `pcntl`, portanto, primeiro certifique-se de que seu `PHP` tenha essa extensão instalada.

```shell
php --ri pcntl

pcntl

pcntl support => enabled
```

- Ao habilitar `grpc`, é necessário adicionar `grpc.enable_fork_support= 1;` ao `php.ini`, para dar suporte à abertura de subprocessos.

## HTTP Server gera erro na inicialização após definir `open_websocket_protocol` como `false`: `Swoole\Server::start(): require onReceive callback`

1. Verifique se o Swoole foi compilado com http2

```shell
php --ri swoole | grep http2
http2 => enabled
```

Se não tiver, é necessário recompilar o Swoole adicionando o parâmetro `--enable-http2`.

2. Verifique se a opção `open_http2_protocol` no arquivo server.php está como `true`.

## Command não fecha normalmente

Após usar tecnologias de multiplexação como AMQP em um Command, o fechamento normal pode ser prejudicado. Nesse caso, basta adicionar o código abaixo ao final da lógica de execução.

```php
<?php
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Coordinator\Constants;

CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
```

## Componente de upload OSS gera erro de iconv

- fix aliyun oss wrong charset: https://github.com/aliyun/aliyun-oss-php-sdk/issues/101
- https://github.com/docker-library/php/issues/240#issuecomment-762438977
- https://github.com/docker-library/php/pull/1264

Ao fazer upload com o componente `aliyuncs/oss-sdk-php`, pode ocorrer erro de iconv; você pode tentar contornar das seguintes formas:

Ao usar a imagem `hyperf/hyperf:8.0-alpine-v3.12-swoole`

```
RUN apk --no-cache --allow-untrusted --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ add gnu-libiconv=1.15-r2
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so
```

Ao usar a imagem `hyperf/hyperf:8.0-alpine-v3.13-swoole`

```dockerfile
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.13/community/ gnu-libiconv=1.15-r3
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php
```

## Falha na coleta de DI

Se ocorrer uma exceção na fase de coleta de DI (como erro de namespace etc.), pode-se produzir uma saída de log no formato abaixo.

- Código de negócio: investigue os arquivos e classes relacionados ao caminho no log.
- Código do framework: envie um PR ou Issue para feedback.
- Componente de terceiros: dê feedback ao autor do componente.

```bash
[ERROR] DI Reflection Manager collecting class reflections failed. 
File: xxxx.
Exception: xxxx
```

## Versões de ambiente inconsistentes impedem a inicialização do serviço

Quando, ao iniciar o projeto, for lançado um erro semelhante ao abaixo:

```bash
Hyperf\Engine\Channel::push(mixed $data, float $timeout = -1): bool must be compatible with Swoole\Coroutine\Channel::push($data, $timeout = -1)
```

Esse problema geralmente é causado pela inconsistência entre a versão do Swoole usada na execução real e a versão do Swoole usada na instalação do framework/componentes.

Usar a mesma versão de Swoole e PHP da instalação resolve o problema.
