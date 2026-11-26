# Cliente de corrotina Consul

O Hyperf fornece um cliente de corrotina para o [Consul](https://www.consul.io/api/index.html). Como a API do próprio Consul é relativamente simples e também suporta o método de requisição HTTP, este componente apenas simplifica um pouco o encapsulamento da API, com suporte ao cliente HTTP de corrotina fornecido por [hyperf/guzzle](https://github.com/hyperf/guzzle).

> A classe `ConsulResponse` refere-se à classe `Hyperf\Consul\ConsulResponse`

## Instalação

```bash
composer require hyperf/consul
```

## Uso

- Obter o cliente Consul correspondente; abaixo usamos o cliente KV como exemplo:

```php
use Hyperf\Consul\KV;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Context\ApplicationContext;

$container = ApplicationContext::getContainer();
$clientFactory = $container->get(ClientFactory::class);

$consulServer = 'http://127.0.0.1:8500';
$kv = new KV(function () use ($clientFactory, $consulServer) {
    return $clientFactory->create([
        'base_uri' => $consulServer,
    ]);
});
```

### Token ACL do Consul

#### Adicionar Token via Header

Você pode definir ao chamar o método passando ao Client um Header com a chave `X-Consul-Token`, conforme mostrado abaixo:

```php
use Hyperf\Consul\KV;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Context\ApplicationContext;

$container = ApplicationContext::getContainer();
$clientFactory = $container->get(ClientFactory::class);

$consulServer = 'http://127.0.0.1:8500';
$kv = new KV(function () use ($clientFactory, $consulServer) {
    return $clientFactory->create([
        'base_uri' => $consulServer,
        'headers' => [
            'X-Consul-Token' => 'your-token'
        ],
    ]);
});
```

#### Adicionar Token via Query

Você também pode definir ao chamar o método passando ao parâmetro $options um parâmetro com a chave `token`; assim o Token será enviado ao Server junto com a Query, conforme mostrado abaixo:

```php
$response = $kv->get($namespace, ['token' => 'your-token'])->json();
```

## KV

Suportado pela implementação de `Hyperf\Consul\KVInterface` em `Hyperf\Consul\KV`.

- get($key, array $options = []): ConsulResponse
- put($key, $value, array $options = []): ConsulResponse
- delete($key, array $options = []): ConsulResponse

## Agent

Suportado pela implementação de `Hyperf\Consul\AgentInterface` em `Hyperf\Consul\Agent`.

- checks(): ConsulResponse
- services(): ConsulResponse
- members(): ConsulResponse
- self(): ConsulResponse
- join($address, array $options = []): ConsulResponse
- forceLeave($node): ConsulResponse
- registerCheck($check): ConsulResponse
- deregisterCheck($checkId): ConsulResponse
- passCheck($checkId, array $options = []): ConsulResponse
- warnCheck($checkId, array $options = []): ConsulResponse
- failCheck($checkId, array $options = []): ConsulResponse
- registerService($service): ConsulResponse
- deregisterService($serviceId): ConsulResponse

## Catalog

Suportado pela implementação de `Hyperf\Consul\CatalogInterface` em `Hyperf\Consul\Catalog`.

- register($node): ConsulResponse
- deregister($node): ConsulResponse
- datacenters(): ConsulResponse
- nodes(array $options = []): ConsulResponse
- node($node, array $options = []): ConsulResponse
- services(array $options = []): ConsulResponse
- service($service, array $options = []): ConsulResponse

## Health

Suportado pela implementação de `Hyperf\Consul\HealthInterface` em `Hyperf\Consul\Health`.

- node($node, array $options = []): ConsulResponse
- checks($service, array $options = []): ConsulResponse
- service($service, array $options = []): ConsulResponse
- state($state, array $options = []): ConsulResponse

## Session

Suportado pela implementação de `Hyperf\Consul\SessionInterface` em `Hyperf\Consul\Session`.

- create($body = null, array $options = []): ConsulResponse
- destroy($sessionId, array $options = []): ConsulResponse
- info($sessionId, array $options = []): ConsulResponse
- node($node, array $options = []): ConsulResponse
- all(array $options = []): ConsulResponse
- renew($sessionId, array $options = []): ConsulResponse
