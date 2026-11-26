# Jet

Jet é um cliente RPC de modelo unificado, com adaptação integrada do protocolo JSONRPC. Este componente pode ser usado em todos os ambientes PHP, incluindo PHP-FPM e Swoole ou Hyperf. (No ambiente Hyperf, ainda é recomendado usar diretamente o componente `hyperf/json-rpc` como cliente)

> No futuro, os protocolos gRPC e Tars também serão integrados.

# Instalação

```bash
composer require hyperf/jet
```

# Início rápido

## Registrar protocolo

> O registro do protocolo não é uma etapa obrigatória, mas você pode gerenciar todos os protocolos por meio do ProtocolManager.

Você pode usar a classe `Hyperf\Jet\ProtocolManager` para registrar e gerenciar qualquer protocolo. Cada protocolo conterá alguns componentes básicos, como Transporter, Packer, DataFormatter e PathGenerator. Você pode registrar um protocolo JSONRPC da seguinte forma:

```php
<?php

use Hyperf\Jet\DataFormatter\DataFormatter;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\Transporter\StreamSocketTransporter;

ProtocolManager::register($protocol = 'jsonrpc', [
    ProtocolManager::TRANSPORTER => new StreamSocketTransporter(),
    ProtocolManager::PACKER => new JsonEofPacker(),
    ProtocolManager::PATH_GENERATOR => new PathGenerator(),
    ProtocolManager::DATA_FORMATTER => new DataFormatter(),
]);
```

## Registrar serviço

> O registro do serviço não é uma etapa obrigatória, mas você pode gerenciar todos os serviços por meio do ServiceManager.

Depois de registrar um protocolo em `Hyperf\Jet\ProtocolManager`, você pode vincular o protocolo a qualquer serviço por meio de `Hyperf\Jet\ServiceManager`, da seguinte forma:

```php
<?php
use Hyperf\Jet\ServiceManager;

// Vincula CalculatorService ao protocolo jsonrpc e define informações estáticas de nós
ServiceManager::register($service = 'CalculatorService', $protocol = 'jsonrpc', [
    ServiceManager::NODES => [
        [$host = '127.0.0.1', $port = 9503],
    ],
]);
```

## Chamar método RPC

### Chamar por meio do ClientFactory

Depois de registrar o protocolo e o serviço, você pode obter o cliente do seu serviço por meio de `Hyperf/Jet/ClientFactory`, conforme mostrado abaixo:

```php
<?php
use Hyperf\Jet\ClientFactory;

$clientFactory = new ClientFactory();
$client = $clientFactory->create($service = 'CalculatorService', $protocol = 'jsonrpc');
```

Depois de ter o objeto client, você pode chamar qualquer método remoto por meio dele, da seguinte forma:

```php
// Chama o método remoto `add` com os parâmetros `1` e `2`
// $result é o valor de retorno do método remoto
$result = $client->add(1, 2);
```

Quando você chama um método remoto inexistente, o cliente lançará uma exceção `Hyperf\Jet\Exception\ServerException`.

### Chamar por meio de um cliente personalizado

Você pode criar uma subclasse de `Hyperf\Jet\AbstractClient` como classe de cliente personalizada para concluir a chamada de métodos remotos. Por exemplo, se você deseja definir uma classe de cliente para o protocolo `jsonrpc` do serviço `CalculatorService`, primeiro defina uma classe `CalculatorService`, conforme mostrado abaixo:

```php
<?php

use Hyperf\Jet\AbstractClient;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\Transporter\StreamSocketTransporter;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;

/**
 * @method int add(int $a, int $b);
 */
class CalculatorService extends AbstractClient
{
    // Define `CalculatorService` como valor padrão do parâmetro $service
    public function __construct(
        string $service = 'CalculatorService',
        TransporterInterface $transporter = null,
        PackerInterface $packer = null,
        ?DataFormatterInterface $dataFormatter = null,
        ?PathGeneratorInterface $pathGenerator = null
    ) {
        // Aqui o transporter é especificado; você ainda pode obtê-lo por meio do ProtocolManager ou passá-lo pelo construtor
        $transporter = new StreamSocketTransporter('127.0.0.1', 9503);
        // Aqui o packer é especificado; você ainda pode obtê-lo por meio do ProtocolManager ou passá-lo pelo construtor
        $packer = new JsonEofPacker();
        parent::__construct($service, $transporter, $packer, $dataFormatter, $pathGenerator);
    }
}
```

Agora, você pode chamar métodos remotos diretamente por meio dessa classe, da seguinte forma:

```php
// Chama o método remoto `add` com os parâmetros `1` e `2`
// $result é o valor de retorno do método remoto
$client = new CalculatorService();
$result = $client->add(1, 2);
```
