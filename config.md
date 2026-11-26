# Configuração

Quando você usa um projeto criado a partir do projeto [hyperf/hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton), todos os arquivos de configuração do Hyperf ficam na pasta `config` do diretório raiz, e cada opção possui uma descrição, para que você possa consultar a qualquer momento e se familiarizar com as opções disponíveis.

# Instalação

```bash
composer require hyperf/config
```

# Estrutura dos arquivos de configuração

A estrutura abaixo é apenas a estrutura da configuração padrão fornecida pelo Hyperf-Skeleton. Na prática, devido a diferenças nas dependências ou nos componentes usados, os arquivos podem variar.
```
config
├── autoload // Os arquivos de configuração dentro desta pasta são carregados pelo próprio componente de configuração, usando o nome do arquivo da pasta como primeira chave
│   ├── amqp.php  // Usado para gerenciar o componente AMQP
│   ├── annotations.php // Usado para gerenciar anotações
│   ├── apollo.php // Usado para gerenciar o centro de configuração baseado em Apollo
│   ├── aspects.php // Usado para gerenciar aspectos AOP
│   ├── async_queue.php // Usado para gerenciar o serviço de fila simples baseado em Redis
│   ├── cache.php // Usado para gerenciar o componente de cache
│   ├── commands.php // Usado para gerenciar comandos personalizados
│   ├── consul.php // Usado para gerenciar o cliente Consul
│   ├── databases.php // Usado para gerenciar o cliente de banco de dados
│   ├── dependencies.php // Usado para gerenciar as relações de dependência e as correspondências de classes do DI
│   ├── devtool.php // Usado para gerenciar as ferramentas de desenvolvimento
│   ├── exceptions.php // Usado para gerenciar os manipuladores de exceção
│   ├── listeners.php // Usado para gerenciar os listeners de eventos
│   ├── logger.php // Usado para gerenciar logs
│   ├── middlewares.php // Usado para gerenciar middlewares
│   ├── opentracing.php // Usado para gerenciar o tracing de chamadas
│   ├── processes.php // Usado para gerenciar processos personalizados
│   ├── redis.php // Usado para gerenciar o cliente Redis
│   └── server.php // Usado para gerenciar o serviço Server
├── config.php // Usado para gerenciar configurações do usuário ou do framework; se a configuração for relativamente independente, também pode ficar na pasta autoload
├── container.php // Responsável pela inicialização do contêiner; é executado como arquivo de configuração e finalmente retorna um objeto Psr\Container\ContainerInterface
└── routes.php // Usado para gerenciar rotas
```

## Descrição da configuração de server.php

Abaixo estão as `settings` padrão fornecidas pelo `config/autoload/server.php` do Hyperf-Skeleton

```php
<?php
declare(strict_types=1);

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Outras configurações deste arquivo foram omitidas aqui
    'settings' => [
        'enable_coroutine' => true, // Ativa a corrotina interna
        'worker_num' => swoole_cpu_num(), // Define o número de processos Worker iniciados
        'pid_file' => BASE_PATH . '/runtime/hyperf.pid', // PID do processo master
        'open_tcp_nodelay' => true, // Ao enviar dados em conexões TCP, desativa o algoritmo de coalescência Nagle e envia imediatamente à conexão do cliente
        'max_coroutine' => 100000, // Define o número máximo de corrotinas do processo de trabalho atual
        'open_http2_protocol' => true, // Ativa a análise do protocolo HTTP2
        'max_request' => 100000, // Define o número máximo de tarefas do processo worker
        'socket_buffer_size' => 2 * 1024 * 1024, // Configura o tamanho do buffer da conexão do cliente
    ],
];
```

Este arquivo de configuração é usado para gerenciar o serviço Server; as opções em `settings` podem usar diretamente as opções fornecidas pelo `Swoole Server`, e outras opções podem ser consultadas na [documentação oficial do Swoole](https://wiki.swoole.com/#/server/setting) .

Se for necessário configurar o modo daemon, você pode adicionar `'daemonize' => true` em `settings`. Após executar `php bin/hyperf.php start`, o programa passará a rodar em segundo plano como daemon.

Configurações de Server individuais precisam ser adicionadas em `settings` do `servers` correspondente, por exemplo, a configuração de um TCP Server com protocolo `jsonrpc` ativando a divisão automática de pacotes por EOF e definindo a string EOF
```php
<?php

use Hyperf\Server\Server;
use Hyperf\Server\Event;

return [
    // Outras configurações deste arquivo foram omitidas aqui
    'servers' => [
        [
            'name' => 'jsonrpc',
            'type' => Server::SERVER_BASE,
            'host' => '0.0.0.0',
            'port' => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_RECEIVE => [\Hyperf\JsonRpc\TcpServer::class, 'onReceive'],
            ],
            'settings' => [
                'open_eof_split' => true, // Ativa a divisão automática de pacotes por EOF
                'package_eof' => "\r\n", // Define a string EOF
            ],
        ],
    ],
];

```

## Relação entre `config.php` e os arquivos de configuração dentro da pasta `autoload`

Tanto o `config.php` quanto os arquivos de configuração dentro da pasta `autoload` são escaneados e injetados no objeto correspondente a `Hyperf\Contract\ConfigInterface` na inicialização do serviço. A estrutura da configuração é um grande array de pares chave-valor; a diferença entre as duas formas de configuração é que nos arquivos de configuração dentro de `autoload` o nome do arquivo existe como a primeira camada de chave (Key), enquanto no `config.php` a primeira camada é a que você definiu. Vamos demonstrar com o exemplo abaixo.  
Suponhamos que exista um arquivo `config/autoload/client.php` com o seguinte conteúdo:
```php
return [
    'request' => [
        'timeout' => 10,
    ],
];
```
Então, para obter o valor de `timeout`, a chave (Key) correspondente será `client.request.timeout`;  

Suponhamos que desejamos obter o mesmo resultado com a mesma chave (Key), mas a configuração está escrita no arquivo `config/config.php`. Nesse caso, o conteúdo do arquivo deve ser o seguinte:
```php
return [
    'client' => [
        'request' => [
            'timeout' => 10,
        ],
    ],
];
```

## Usar o componente Hyperf Config

Este componente é o componente de configuração padrão fornecido oficialmente, implementado voltado à interface `Hyperf\Contract\ConfigInterface`, e o `ConfigProvider` do componente [hyperf/config](https://github.com/hyperf/config) vincula o objeto `Hyperf\Config\Config` à interface.  

### Definir configuração

Basta que as configurações em `config/config.php`, `config/autoload/server.php` e dentro da pasta `autoload` sejam escaneadas e injetadas no objeto correspondente a `Hyperf\Contract\ConfigInterface` na inicialização do serviço. Esse processo é concluído pelo `Hyperf\Config\ConfigFactory` no momento em que o objeto Config é instanciado.

### Obter configuração

O componente Config fornece três formas de obter configuração: através do objeto `Hyperf\Config\Config`, através da anotação `#[Value]` e através da função `config(string $key, $default)`.

#### Obter configuração através do objeto Config

Essa forma exige que você já tenha uma instância do objeto `Config`, cujo objeto padrão é `Hyperf\Config\Config`. Os detalhes da injeção da instância podem ser consultados no capítulo [Injeção de dependência](di.md);

```php
/**
 * @var \Hyperf\Contract\ConfigInterface
 */
// Obtém a configuração correspondente a $key através do método get(string $key, $default): mixed; o valor de $key pode localizar arrays de níveis inferiores usando o conector ., e $default é o valor padrão retornado quando o valor correspondente não existe
$config->get($key，$default);
```

#### Obter configuração através da anotação `#[Value]`

Essa forma exige que o objeto de aplicação da anotação tenha sido criado pelo componente [hyperf/di](https://github.com/hyperf/di). Os detalhes da injeção da instância podem ser consultados no capítulo [Injeção de dependência](di.md). No exemplo, supomos que `IndexController` seja uma classe `Controller` já definida, e a classe `Controller` certamente é criada pelo contêiner de `DI`;  
A string dentro de `#[Value]` corresponde ao parâmetro `$key` dentro de `$config->get($key)`, e ao criar a instância desse objeto, a configuração correspondente é injetada automaticamente na propriedade definida da classe.

```php
use Hyperf\Config\Annotation\Value;

class IndexController
{
    #[Value("config.key")]
    private $configValue;

    public function index()
    {
        return $this->configValue;
    }
}
```

#### Obter através da função config

Em qualquer lugar é possível obter a configuração correspondente através da função `config(string $key, $default)`, mas essa forma de uso significa que você tem dependência forte dos componentes [hyperf/config](https://github.com/hyperf/config) e [hyperf/support](https://github.com/hyperf/support).

### Verificar se a configuração existe

```php
/**
 * @var \Hyperf\Contract\ConfigInterface
 */
// Através do método has(): bool, verifica se o valor de $key correspondente existe na configuração; o valor de $key pode localizar arrays de níveis inferiores usando o conector .
$config->has($key);
```

## Variáveis de ambiente

Usar configurações diferentes para ambientes de execução diferentes é uma necessidade comum, por exemplo, a configuração do Redis no ambiente de teste e no ambiente de produção é diferente, e a configuração do ambiente de produção não pode ser enviada ao sistema de controle de versão do código-fonte para evitar vazamento de informações.  

No Hyperf, fornecemos a solução de variáveis de ambiente. Aproveitando a funcionalidade de análise de variáveis de ambiente fornecida por [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) e a função `env()` para obter o valor das variáveis de ambiente, resolver essa necessidade é bastante fácil.  

Em uma aplicação Hyperf recém-instalada, o diretório raiz conterá um arquivo `.env.example`. Se o Hyperf foi instalado via Composer, esse arquivo será automaticamente copiado com base em `.env.example` para um novo arquivo chamado `.env`. Caso contrário, você precisa alterar manualmente o nome do arquivo.  

Seu arquivo `.env` não deve ser enviado ao sistema de controle de versão do código-fonte da aplicação, porque cada desenvolvedor/servidor que usa sua aplicação pode precisar de uma configuração de ambiente diferente. Além disso, no caso de um invasor obter acesso ao repositório do seu código-fonte, isso causaria sérios problemas de segurança, pois todos os dados sensíveis ficariam totalmente expostos.  

> Todas as variáveis no arquivo `.env` podem ser sobrescritas por variáveis de ambiente externas (como variáveis de ambiente em nível de servidor, de sistema ou do Docker).

### Tipos de variáveis de ambiente

Todas as variáveis no arquivo `.env` serão analisadas como tipo string, por isso são fornecidos alguns valores reservados para permitir que você obtenha variáveis de mais tipos a partir da função `env()`:

| Valor .env | Valor env() |
| :------ | :----------- |
| true    | (bool) true  |
| (true)  | (bool) true  |
| false   | (bool) false |
| (false) | (bool) false |
| empty   | (string) ''  |
| (empty) | (string) ''  |
| null    | (null) null  |
| (null)  | (null) null  |

Se você precisar usar variáveis de ambiente que contenham espaços ou outros caracteres especiais, pode fazê-lo colocando o valor entre aspas duplas, por exemplo:

```dotenv
APP_NAME="Hyperf Skeleton"
```

### Ler variáveis de ambiente

Também mencionamos acima que as variáveis de ambiente podem ser obtidas através da função `env()`. No desenvolvimento de aplicações, as variáveis de ambiente devem servir apenas como um valor da configuração, sobrescrevendo o valor da configuração através do valor da variável de ambiente. Para a camada de aplicação, deve-se **usar apenas a configuração**, e não usar diretamente variáveis de ambiente.  
Vamos dar um exemplo de uso razoável:

```php
// config/config.php
return [
    'app_name' => env('APP_NAME', 'Hyperf Skeleton'),
];
```

## Publicar a configuração de componentes

O Hyperf adota um design baseado em componentes. Após adicionar alguns componentes ao projeto esqueleto, normalmente precisamos criar os arquivos de configuração correspondentes para os componentes recém-adicionados, a fim de atender ao uso do componente. O Hyperf fornece para os componentes um `mecanismo de publicação de configuração de componentes`. Através desse mecanismo, você só precisa de um comando `vendor:publish` para publicar o modelo de arquivo de configuração predefinido do componente no projeto esqueleto.  
Por exemplo, suponhamos que desejamos adicionar um componente `hyperf/foo` (esse componente na verdade não existe, é apenas um exemplo) e o arquivo de configuração correspondente a ele. Após executar `composer require hyperf/foo` para instalar, você pode executar `php bin/hyperf.php vendor:publish hyperf/foo` para publicar o arquivo de configuração predefinido do componente dentro da pasta `config/autoload` do projeto esqueleto. O conteúdo específico a ser publicado é definido e fornecido pelo componente.

## Centro de configuração

O Hyperf oferece suporte à configuração externalizada para sistemas distribuídos. Atualmente há suporte a `Apollo` de código aberto da Ctrip, ao gerenciamento de configuração de aplicações ACM da Alibaba Cloud, ao ETCD, ao Nacos e ao Zookeeper como centros de configuração.  
Os detalhes de uso do centro de configuração são descritos no capítulo [Centro de configuração](config-center.md).
