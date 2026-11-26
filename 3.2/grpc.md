# Serviço gRPC

O quickstart - php da documentação oficial do gRPC facilmente induz os PHPers ao erro; seguindo a documentação do site oficial, colocar um serviço gRPC em execução já é bastante trabalhoso, muito menos todo o conjunto de serviços RPC.

Recomenda-se a leitura de [tech| Revisitando o gRPC](https://www.jianshu.com/p/f3221df39e6f), que explica os conhecimentos básicos relacionados à implementação de gRPC em PHP.

O Hyperf fez um encapsulamento mais aprofundado do suporte a gRPC. Tomando como exemplo o projeto hyperf-skeleton, explicamos detalhadamente todo o processo:

- Arquivo .proto e exemplo de configuração relacionada
- Exemplo de servidor gRPC
- Exemplo de cliente gRPC

## Arquivo .proto e exemplo de configuração relacionada

- Defina o arquivo proto `grpc.proto`

```proto3
syntax = "proto3";

package grpc;

service Hi {
    rpc SayHello (HiUser) returns (HiReply) {
    }
}

message HiUser {
    string name = 1;
    int32 sex = 2;
}

message HiReply {
    string message = 1;
    HiUser user = 2;
}
```

- Use o protoc para gerar o código de exemplo

```
# Instale o protoc usando o gerenciador de pacotes do linux; abaixo usamos alpine como exemplo, você também pode consultar o Dockerfile no hyperf-skeleton
apk add protobuf

# Use o protoc para gerar o código automaticamente
protoc --php_out=grpc/ grpc.proto

# tree grpc
grpc
├── GPBMetadata
│   └── Grpc.php
└── Grpc
    ├── HiReply.php
    └── HiUser.php
```

- Configure o composer.json para usar o autoload do código em `grpc/`. Se o arquivo proto usar uma configuração `package` diferente ou diretórios diferentes, faça os ajustes correspondentes. Após adicionar, execute `composer dump-autoload` para que o autoload tenha efeito

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "GPBMetadata\\": "grpc/GPBMetadata",
        "Grpc\\": "grpc/Grpc"
    },
    "files": [
    ]
},
```

## Exemplo de servidor gRPC

- Instale o componente

```shell
composer require hyperf/grpc-server
```

- Configuração do servidor gRPC

Arquivo `server.php` (consulte [Configuração](config.md)):

```php
'servers' => [
    ....
    [
        'name' => 'grpc',
        'type' => Server::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => 9503,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
            Event::ON_REQUEST => [\Hyperf\GrpcServer\Server::class, 'onRequest'],
        ],
    ],
],
```

- Configuração de rota do servidor gRPC

Arquivo `routes.php` (consulte [Rotas](router.md)):

```php
Router::addServer('grpc', function () {
    Router::addGroup('/grpc.Hi', function () {
        Router::post('/SayHello', 'App\Controller\HiController@sayHello');
    });
});
```

O método `sayHello` no arquivo `HiController.php`:

```php
public function sayHello(HiUser $user) 
{
    $message = new HiReply();
    $message->setMessage("Hello World");
    $message->setUser($user);
    return $message;
}
```

A correspondência entre a definição no arquivo .proto e a rota do servidor gRPC: `/{package}.{service}/{rpc}`

- Se quiser se aprofundar mais

Como o servidor gRPC processa as requisições gRPC (`vendor/hyperf/grpc-server/src/CoreMiddleware.php)`): `\Hyperf\GrpcServer\CoreMiddleware::process()` analisa o `request_uri`, ou seja, obtém as informações `/{package}.{service}/{rpc}`, e depois chama a classe de codificação/decodificação gRPC já encapsulada `\Hyperf\Grpc\Parser::deserializeMessage` para obter as informações em texto claro da requisição

Acredito que, com as informações acima, você mesmo possa descobrir como o servidor gRPC faz a resposta gRPC.

## Exemplo de cliente gRPC

Instale o componente

```shell
composer require hyperf/grpc-client
```

O código de exemplo pode ser encontrado em `GrpcController`:

```php
public function hello()
{
    // Este client é seguro para corrotinas e pode ser reutilizado
    $client = new \App\Grpc\HiClient('127.0.0.1:9503', [
        'credentials' => null,
    ]);

    $request = new \Grpc\HiUser();
    $request->setName('hyperf');
    $request->setSex(1);

    /**
     * @var \Grpc\HiReply $reply
     */
    list($reply, $status) = $client->sayHello($request);

    $message = $reply->getMessage();
    $user = $reply->getUser();
    
    var_dump(memory_get_usage(true));
    return $message;
}
```

O Hyperf já encapsulou `\Hyperf\GrpcClient\BaseClient`; basta estendê-lo conforme a necessidade, de acordo com a definição no arquivo .proto:

```php
class HiClient extends BaseClient
{
    public function sayHello(HiUser $argument)
    {
        return $this->_simpleRequest(
            '/grpc.Hi/SayHello',
            $argument,
            [HiReply::class, 'decode']
        );
    }
}
```

O cliente gRPC também suporta o modo Streaming do gRPC. Tomando como exemplo o streaming bidirecional:

```php
public function hello()
{
    $client = new RouteGuideClient('127.0.0.1:50051');

    $note = new RouteNote();

    $call = $client->routeChat();
    $call->push($note);
    $call->push($note);

    /** @var RouteNote $note */
    [$note,] = $call->recv();
    [$note,] = $call->recv();
}
```

> Observe que, no modo streaming, é obrigatório capturar manualmente a exceção de desconexão da conexão (`Hyperf\GrpcClient\Exception\GrpcClientException`) e escolher, conforme a necessidade, se deve fazer retry.

## Considerações finais

Se você é um usuário intensivo de gRPC, fique atento às futuras ferramentas de desenvolvimento do Hyperf, que poderão gerar todo o conjunto de código gRPC a partir de arquivos .proto.
