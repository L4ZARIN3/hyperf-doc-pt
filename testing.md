# Testes automatizados

No Hyperf, os testes são implementados por padrão através do `phpunit`, e a partir da versão 3.1 há suporte ao framework `pest` baseado no phpunit [documentação](https://pestphp.com/docs/installation).


```shell
composer require hyperf/testing --dev
composer require pestphp/pest --dev
```

```json
"scripts": {
    "pest": "pest --colors=always",
    "test": "co-phpunit -c phpunit.xml --colors=always"
},
```

| package         | version |
| --------------- | ------- |
| phpunit/phpunit | ^10.1   |
| pestphp/pest    | ^2.8  |

## Bootstrap

O Hyperf fornece um arquivo `bootstrap.php` padrão, que permite ao usuário, ao executar os testes unitários, escanear e carregar as bibliotecas correspondentes na memória.

```php
<?php

declare(strict_types=1);

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

// Ativado por padrão; quando usar o recurso pest --parallel ou outras operações nativas em paralelo, é necessário comentar
Swoole\Runtime::enableCoroutine(true);

require BASE_PATH . '/vendor/autoload.php';

Hyperf\Di\ClassLoader::init();

$container = require BASE_PATH . '/config/container.php';

$container->get(Hyperf\Contract\ApplicationInterface::class);

```

Executar os testes unitários

```
composer test
```

## Observações

- `hyperf/testing` fornece o Trait [RunTestsInCoroutine](https://github.com/hyperf/hyperf/blob/master/src/testing/src/Concerns/RunTestsInCoroutine.php). Basta usar essa classe no `Test` específico para habilitar o ambiente de corrotina
- Ao usar o recurso do parâmetro --parallel do pest, é necessário comentar `Swoole\Runtime::enableCoroutine(true)` em `test/bootstrap.php`

## Simular requisições HTTP

Ao desenvolver interfaces, normalmente precisamos de um script de teste automatizado para garantir que as interfaces fornecidas estejam funcionando conforme o esperado. No framework Hyperf, é fornecida a classe `Hyperf\Testing\Client`, que permite simular requisições ao serviço HTTP sem iniciar o Server:

```php
<?php
use Hyperf\Testing\Client;

$client = make(Client::class);

$result = $client->get('/');
```

Como o Hyperf suporta configuração de múltiplas portas, além de validar a interface da porta padrão, como validar interfaces de outras portas?

```php
<?php

use Hyperf\Testing\Client;

$client = make(Client::class, ['server' => 'adminHttp']);

$result = $client->json('/user/0',[
    'nickname' => 'Hyperf'
]);

```

Por padrão, o framework usa `JsonPacker`, que converte diretamente o `Body` em `array`. Se você retornar diretamente uma `string`, será necessário definir o `Packer` correspondente

```php
<?php

use Hyperf\Testing\Client;
use Hyperf\Contract\PackerInterface;

$client = make(Client::class, [
    'packer' => new class() implements PackerInterface {
        public function pack($data): string
        {
            return $data;
        }

        public function unpack(string $data)
        {
            return $data;
        }
    },
]);

$result = $client->json('/user/0',[
    'nickname' => 'Hyperf'
]);
```

### Usar Cookies

```php
<?php

use Hyperf\Testing\Client;
use Hyperf\Codec\Json;

$client = make(Client::class);

$response = $client->sendRequest($client->initRequest('POST', '/request')->withCookieParams([
    'X-CODE' => $id = uniqid(),
]));

$data = Json::decode((string) $response->getBody());
```

## Exemplo

Vamos escrever uma pequena DEMO para testar.

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Testing\Client;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ExampleTest extends TestCase
{
    protected Client $client;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = make(Client::class);
    }

    public function testExample()
    {
        $this->assertTrue(true);

        $res = $this->client->get('/');

        $this->assertSame(0, $res['code']);
        $this->assertSame('Hello Hyperf.', $res['data']['message']);
        $this->assertSame('GET', $res['data']['method']);
        $this->assertSame('Hyperf', $res['data']['user']);

        $res = $this->client->get('/', ['user' => 'developer']);

        $this->assertSame(0, $res['code']);
        $this->assertSame('developer', $res['data']['user']);

        $res = $this->client->post('/', [
            'user' => 'developer',
        ]);
        $this->assertSame('Hello Hyperf.', $res['data']['message']);
        $this->assertSame('POST', $res['data']['method']);
        $this->assertSame('developer', $res['data']['user']);

        $res = $this->client->json('/', [
            'user' => 'developer',
        ]);
        $this->assertSame('Hello Hyperf.', $res['data']['message']);
        $this->assertSame('POST', $res['data']['method']);
        $this->assertSame('developer', $res['data']['user']);

        $res = $this->client->file('/', ['name' => 'file', 'file' => BASE_PATH . '/README.md']);

        $this->assertSame('Hello Hyperf.', $res['data']['message']);
        $this->assertSame('POST', $res['data']['method']);
        $this->assertSame('README.md', $res['data']['file']);
    }
}
```

## Depurar código

No cenário FPM, normalmente alteramos o código e depois abrimos o navegador para acessar a interface correspondente, por isso geralmente precisamos das funções `dd` e `dump`. Porém, o `Hyperf` roda em modo `CLI`; mesmo que essas duas funções sejam fornecidas, seria necessário reiniciar o `Server` no `CLI` e depois chamar a interface correspondente no navegador para ver o resultado. Isso na verdade não simplifica o fluxo, pelo contrário, torna-o mais trabalhoso.

A seguir, vou apresentar como depurar código rapidamente em conjunto com o `testing`, aproveitando para concluir os testes unitários.

Suponhamos que implementamos uma função de consulta de informações do usuário em `UserDao`
```php
namespace App\Service\Dao;

use App\Constants\ErrorCode;
use App\Exception\BusinessException;
use App\Model\User;

class UserDao extends Dao
{
    /**
     * @param $id
     * @param bool $throw
     * @return
     */
    public function first($id, $throw = true)
    {
        $model = User::query()->find($id);
        if ($throw && empty($model)) {
            throw new BusinessException(ErrorCode::USRE_NOT_EXIST);
        }
        return $model;
    }
}
```

Então escrevemos o teste unitário correspondente

```php
namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;
use App\Service\Dao\UserDao;
/**
 * @internal
 * @coversNothing
 */
class UserTest extends HttpTestCase
{
    public function testUserDaoFirst()
    {
        $model = \Hyperf\Context\ApplicationContext::getContainer()->get(UserDao::class)->first(1);

        var_dump($model);

        $this->assertSame(1, $model->id);
    }
}
```

Em seguida, executamos o teste unitário

```
composer test -- --filter=testUserDaoFirst
```

## Test doubles (dublês de teste)

`Gerard Meszaros` apresenta o conceito de test double em `Meszaros2007`:

Às vezes é muito difícil testar o `Sistema Sob Teste (SUT, System Under Test)`, porque ele depende de outros componentes que não podem ser usados no ambiente de teste. Isso pode ocorrer porque esses componentes não estão disponíveis, não retornam os resultados necessários para o teste, ou porque executá-los causa efeitos colaterais indesejados. Em outros casos, nossa estratégia de teste exige mais controle ou mais visibilidade sobre o comportamento interno do sistema sob teste.

Se ao escrever testes não for possível usar (ou se optar por não usar) os componentes de dependência reais (DOC), pode-se usar test doubles em seu lugar. Um test double não precisa se comportar exatamente como o componente de dependência real; ele só precisa fornecer a mesma API do componente real, para que o sistema sob teste pense que ele é o componente real!

A seguir são mostrados test doubles que injetam dependências respectivamente via construtor e via anotação `#[Inject]`

### Test double com injeção de dependência via construtor

```php
<?php

namespace App\Logic;

use App\Api\DemoApi;

class DemoLogic
{
    private DemoApi $demoApi;

    public function __construct(DemoApi $demoApi)
    {
       $this->demoApi = $demoApi;
    }

    public function test()
    {
        $result = $this->demoApi->test();

        return $result;
    }
}
```

```php
<?php

namespace App\Api;

class DemoApi
{
    public function test()
    {
        return [
            'status' => 1
        ];
    }
}
```

```php
<?php

namespace HyperfTest\Cases;

use App\Api\DemoApi;
use App\Logic\DemoLogic;
use Hyperf\Di\Container;
use HyperfTest\HttpTestCase;
use Mockery;

class DemoLogicTest extends HttpTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testIndex()
    {
        $res = $this->getContainer()->get(DemoLogic::class)->test();

        $this->assertEquals(1, $res['status']);
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        $container = Mockery::mock(Container::class);

        $apiStub = $this->createMock(DemoApi::class);

        $apiStub->method('test')->willReturn([
            'status' => 1,
        ]);

        $container->shouldReceive('get')->with(DemoLogic::class)->andReturn(new DemoLogic($apiStub));

        return $container;
    }
}
```

### Test double com injeção de dependência via anotação Inject

```php
<?php

namespace App\Logic;

use App\Api\DemoApi;
use Hyperf\Di\Annotation\Inject;

class DemoLogic
{
    #[Inject]
    private DemoApi $demoApi;

    public function test()
    {
        $result = $this->demoApi->test();

        return $result;
    }
}
```

```php
<?php

namespace App\Api;

class DemoApi
{
    public function test()
    {
        return [
            'status' => 1
        ];
    }
}
```

```php
<?php

namespace HyperfTest\Cases;

use App\Api\DemoApi;
use App\Logic\DemoLogic;
use Hyperf\Di\Container;
use Hyperf\Context\ApplicationContext;
use HyperfTest\HttpTestCase;
use Mockery;

class DemoLogicTest extends HttpTestCase
{
    /**
     * @after
     */
    public function tearDownAfterMethod()
    {
        Mockery::close();
    }

    public function testIndex()
    {
        $this->getContainer();

        $res = $this->getContainer()->get(DemoLogic::class)->test();

        $this->assertEquals(11, $res['status']);
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        $container = ApplicationContext::getContainer();

        $apiStub = $this->createMock(DemoApi::class);

        $apiStub->method('test')->willReturn([
            'status' => 11
        ]);

        $container->define(DemoApi::class, function () use ($apiStub) {
            return $apiStub;
        });
        
        return $container;
    }
}
```

# Cobertura de testes unitários

## Gerar cobertura de testes unitários usando phpdbg

Altere o conteúdo do arquivo `phpunit.xml` para o seguinte

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         bootstrap="./test/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    <php>
        <!-- other PHP.ini or environment variables -->
        <ini name="memory_limit" value="-1" />
    </php>
    <testsuites>
        <testsuite name="Tests">
            // Diretório dos casos de teste que precisam ser executados
            <directory suffix="Test.php">./test</directory>
        </testsuite>
    </testsuites>
    <coverage includeUncoveredFiles="true"
              processUncoveredFiles="true"
              pathCoverage="false"
              ignoreDeprecatedCodeUnits="true"
              disableCodeCoverageIgnore="false">
        <include>
            // Arquivos que precisam ter a cobertura de testes unitários contabilizada
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            // Arquivos que precisam ser ignorados ao gerar a cobertura de testes unitários
            <directory suffix=".php">./app/excludeFile</directory>
        </exclude>
        <report>
            <html outputDirectory="test/cover/" lowUpperBound="50" highLowerBound="90"/>
        </report>
    </coverage>
    <logging>
        <junit outputFile="test/junit.xml"/>
    </logging>

</phpunit>
```


Execute o seguinte comando

```shell
phpdbg -dmemory_limit=1024M -qrr ./vendor/bin/co-phpunit -c phpunit.xml --colors=always
```
