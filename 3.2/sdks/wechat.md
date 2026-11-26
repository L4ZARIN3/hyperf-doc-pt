# EasyWechat

[EasyWeChat](https://www.easywechat.com/) é um SDK de WeChat de código aberto (não é o SDK oficial do WeChat).

> Se você usa o Swoole 4.7.0 ou superior e ativou a opção native curl, pode ignorar os procedimentos deste documento.

> Como o componente usa `Curl` por padrão, precisamos alterar o `GuzzleClient` correspondente para um cliente de corrotina, ou modificar a constante [SWOOLE_HOOK_FLAGS](../coroutine.md#nível-de-runtime-hook-do-swoole)

## Substituir o `Handler`

Abaixo usamos a conta oficial como exemplo,

```php
<?php

use Hyperf\Context\ApplicationContext;
use EasyWeChat\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\CoroutineHandler;

$container = ApplicationContext::getContainer();

$app = Factory::officialAccount($config);
$handler = new CoroutineHandler();

// Define o HttpClient; algumas interfaces usam diretamente o http_client.
$config = $app['config']->get('http', []);
$config['handler'] = $stack = HandlerStack::create($handler);
$app->rebind('http_client', new Client($config));

// Algumas interfaces redefinem o Handler com base em guzzle_handler ao requisitar dados
$app['guzzle_handler'] = $handler;

// Se estiver usando OfficialAccount, também é necessário definir os seguintes parâmetros
$app->oauth->setGuzzleOptions([
    'http_errors' => false,
    'handler' => $stack,
]);
```

## Modificar `SWOOLE_HOOK_FLAGS`

Consulte [SWOOLE_HOOK_FLAGS](../coroutine.md#nível-de-runtime-hook-do-swoole)

## Como usar o EasyWeChat

O `EasyWeChat` foi projetado para a arquitetura `PHP-FPM`, portanto alguns pontos precisam ser modificados para funcionar no Hyperf. Abaixo usamos o callback de pagamento como exemplo para explicar.

1. O `EasyWeChat` já inclui análise de `XML`, então basta obtermos o `XML` original.

```php
$xml = $this->request->getBody()->getContents();
```

2. Coloque os dados XML no `Request` do `EasyWeChat`.

```php
<?php
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

$get = $this->request->getQueryParams();
$post = $this->request->getParsedBody();
$cookie = $this->request->getCookieParams();
$uploadFiles = $this->request->getUploadedFiles() ?? [];
$server = $this->request->getServerParams();
$xml = $this->request->getBody()->getContents();
$files = [];
/** @var \Hyperf\HttpMessage\Upload\UploadedFile $v */
foreach ($uploadFiles as $k => $v) {
    $files[$k] = $v->toArray();
}
$request = new Request($get, $post, [], $cookie, $files, $server, $xml);
$request->headers = new HeaderBag($this->request->getHeaders());
$app->rebind('request', $request);
// Faça algo...

```

3. Configuração do servidor

Se precisar usar a funcionalidade de configuração de servidor da plataforma pública do WeChat, você pode usar o código abaixo.

> Abaixo, `$response` é `Symfony\Component\HttpFoundation\Response`, e não `Hyperf\HttpMessage\Server\Response` 
> Portanto, basta retornar diretamente o conteúdo do `Body` para passar na verificação do WeChat.

```php
$response = $app->server->serve();

return $response->getContent();
```

## Como substituir o cache

O `EasyWeChat` usa `cache de arquivo` por padrão, mas no cenário real o `cache Redis` é mais comum. Sendo assim, você pode substituí-lo pelo componente de cache `hyperf/cache` fornecido pelo `Hyperf`. Se você ainda não instalou esse componente, execute `composer require hyperf/cache` para importá-lo. Exemplo de uso:

```php
<?php
use Psr\SimpleCache\CacheInterface;
use Hyperf\Context\ApplicationContext;
use EasyWeChat\Factory;

$app = Factory::miniProgram([]);
$app['cache'] = ApplicationContext::getContainer()->get(CacheInterface::class);
```
