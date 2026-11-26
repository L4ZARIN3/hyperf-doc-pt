# Cliente de corrotina WebSocket

O Hyperf oferece um encapsulamento do WebSocket Client, permitindo acessar um WebSocket Server com base no componente [hyperf/websocket-client](https://github.com/hyperf/websocket-client);

## Instalação

```bash
composer require hyperf/websocket-client
```

## Uso

O componente oferece um `Hyperf\WebSocketClient\ClientFactory` para criar o objeto de cliente `Hyperf\WebSocketClient\Client`. Vamos demonstrar diretamente pelo código:

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;

class IndexController
{
    #[Inject]
    protected ClientFactory $clientFactory;

    public function index()
    {
        // Endereço do serviço da outra ponta; se o prefixo ws:// ou wss:// não for fornecido, ws:// é adicionado por padrão
        $host = '127.0.0.1:9502';
        // Cria o objeto Client através do ClientFactory; o objeto criado é de curto ciclo de vida
        $client = $this->clientFactory->create($host);
        // Envia uma mensagem para o servidor WebSocket
        $client->push('Enviando dados com o WebSocket Client no HttpServer.');
        // Obtém a mensagem de resposta do servidor; o servidor precisa entregar a mensagem ao fd deste cliente via push para que ela possa ser obtida. A seguir, o tempo limite é definido como 2s, e o tipo de dado recebido é um objeto Frame.
        /** @var Frame $msg */
        $msg = $client->recv(2);
        // Obtém o dado de texto: $res_msg->data
        return $msg->data;
    }
}
```

## Desativar o fechamento automático

Por padrão, o objeto `Client` criado faz `close` automático da conexão via `defer`. Se você não deseja o `close` automático, pode passar o segundo parâmetro `$autoClose` como `false` ao criar o objeto `Client`:

```php
$autoClose = false;
$client = $clientFactory->create($host, $autoClose);
```
