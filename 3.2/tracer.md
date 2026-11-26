# Rastreamento de cadeia de chamadas

No cenário de microsserviços, dividimos muitos serviços, o que significa que uma requisição de negócio atravessa, no mínimo, 3-4 serviços e, no máximo, dezenas ou até mais. Nessa arquitetura, quando precisamos depurar um problema, isso é extremamente difícil. Assim, precisamos de um sistema de rastreamento de cadeia de chamadas para nos ajudar a exibir dinamicamente o encadeamento das chamadas de serviço, para que possamos localizar rapidamente o ponto do problema e também otimizar os serviços com base nas informações da cadeia.   
No `Hyperf`, fornecemos o componente [hyperf/tracer](https://github.com/hyperf/tracer) para rastrear e analisar chamadas entre requisições de rede. Atualmente, de acordo com o protocolo [OpenTracing](https://opentracing.io), integramos os sistemas [Zipkin](https://zipkin.io/) e [Jaeger](https://www.jaegertracing.io/); os usuários também podem implementar soluções personalizadas de acordo com o protocolo OpenTracing.

## Instalação

### Instalar o componente via Composer

```bash
composer require hyperf/tracer
```

O componente [hyperf/tracer](https://github.com/hyperf/tracer) instala por padrão as dependências relacionadas ao [Zipkin](https://zipkin.io/). Caso queira usar o [Jaeger](https://www.jaegertracing.io/), também é necessário executar o comando abaixo para instalar as dependências correspondentes:

```bash
composer require jonahgeorge/jaeger-client-php
```

### Adicionar configuração do componente

Caso o arquivo não exista, execute o comando abaixo para adicionar o arquivo de configuração `config/autoload/opentracing.php`:

```bash
php bin/hyperf.php vendor:publish hyperf/tracer
```

## Uso

### Configuração

#### Configurar a chave de rastreamento

Por padrão, são fornecidos monitoramento ou processamento por aspecto `AOP` para chamadas `Guzzle HTTP`, chamadas `Redis` e chamadas `DB`, a fim de realizar a propagação e o rastreamento da cadeia de chamadas. Por padrão, esses rastreamentos não são ativados; você precisa alterar as chaves no item `enable` do arquivo de configuração `config/autoload/opentracing.php` para ativar o rastreamento de determinadas chamadas remotas.

```php
<?php

return [
    'enable' => [
        // Ativa ou desativa o rastreamento de chamadas Guzzle HTTP
        'guzzle' => false,
        // Ativa ou desativa o rastreamento de chamadas Redis
        'redis' => false,
        // Ativa ou desativa o rastreamento de chamadas DB
        'db' => false,
    ],
];
```

Antes de iniciar o rastreamento, também precisamos escolher o driver Tracer a ser usado e configurar o Tracer.

#### Escolher o driver do tracer

O valor correspondente a `default` no arquivo de configuração é o nome do driver usado. A configuração específica do driver é definida no item `tracer`, usando o mesmo driver da `key`.

```php
<?php

return [
    // Escolhe o driver Tracer padrão; o nome do Tracer escolhido corresponde à chave definida em tracers
    'default' => env('TRACER_DRIVER', 'staging_zipkin'),

    // Outras configurações são omitidas aqui temporariamente
    'enable' => [],

    'tracer' => [
        // Configuração do Zipkin
        'staging_zipkin' => [
            'driver' => \Hyperf\Tracer\Adapter\ZipkinTracerFactory::class,
        ],
        // Outro conjunto de configuração do Zipkin
        'producton_zipkin' => [
            'driver' => \Hyperf\Tracer\Adapter\ZipkinTracerFactory::class,
        ],
        // Configuração do Jaeger
        'jaeger' => [
            'driver' => \Hyperf\Tracer\Adapter\JaegerTracerFactory::class,
        ],
    ]
];
```

Observe que, como mostrado na configuração de exemplo, você pode configurar vários conjuntos de drivers Zipkin ou drivers Jaeger. Embora o sistema subjacente seja o mesmo, suas configurações específicas podem ser diferentes. Um cenário comum é querermos 100% de amostragem no ambiente de teste, mas 1% de amostragem no ambiente de produção; então podemos configurar dois conjuntos de drivers e escolher drivers diferentes de acordo com a variável de ambiente no item `default`.

#### Configurar o Zipkin

Ao usar o Zipkin, adicione a configuração específica do Zipkin ao item `tracer` no arquivo de configuração.

```php
<?php
use Zipkin\Samplers\BinarySampler;

return [
    // Escolhe o Tracer padrão
    'default' => env('TRACER_DRIVER', 'zipkin'),

    // A demonstração de código aqui não detalha as configurações dentro de enable
    'enable' => [],

    'tracer' => [
        // Configuração do driver Zipkin
        'zipkin' => [
            // Configuração da aplicação atual
            'app' => [
                'name' => env('APP_NAME', 'skeleton'),
                // Se ipv4 e ipv6 estiverem vazios, o componente detectará automaticamente a partir do Server
                'ipv4' => '127.0.0.1',
                'ipv6' => null,
                'port' => 9501,
            ],
            'driver' => \Hyperf\Tracer\Adapter\ZipkinTracerFactory::class,
            'options' => [
                // Endereço de endpoint do serviço Zipkin
                'endpoint_url' => env('ZIPKIN_ENDPOINT_URL', 'http://localhost:9411/api/v2/spans'),
                // Segundos de timeout da requisição
                'timeout' => env('ZIPKIN_TIMEOUT', 1),
            ],
            // Amostrador; por padrão, todas as requisições são rastreadas
            'sampler' => BinarySampler::createAsAlwaysSample(),
        ],
    ],
];
```

#### Configurar o Jaeger

Ao usar o Jaeger, adicione a configuração específica do Jaeger ao item `tracer` no arquivo de configuração.

```php
<?php
use Hyperf\Tracer\Adapter\JaegerTracerFactory;
use const Jaeger\SAMPLER_TYPE_CONST;

return [
    // Escolhe o Tracer padrão
    'default' => env('TRACER_DRIVER', 'jaeger'),

    // A demonstração de código aqui não detalha as configurações dentro de enable
    'enable' => [],

    'tracer' => [
        // Configuração do driver Jaeger
        'jaeger' => [
            'driver' => JaegerTracerFactory::class,
            // Nome do projeto
            'name' => env('APP_NAME', 'skeleton'),
            'options' => [
                // Amostrador; por padrão, todas as requisições são rastreadas
                'sampler' => [
                    'type' => SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                // Endereço de reporte
                'local_agent' => [
                    'reporting_host' => env('JAEGER_REPORTING_HOST', 'localhost'),
                    'reporting_port' => env('JAEGER_REPORTING_PORT', 5775),
                ],
            ],
        ],
    ],
];
```

Mais configurações sobre o Jaeger podem ser vistas em [[aqui](https://github.com/jonahgeorge/jaeger-client-php)].

#### Configurar a chave de rastreamento do JsonRPC

O rastreamento de chamadas do JsonRPC não está na configuração unificada; por enquanto, ainda é uma funcionalidade em versão `Beta`.

Basta configurar `aspects.php`, adicionando o seguinte `Aspect` para ativar.

> Dica: não se esqueça de adicionar o TraceMiddleware correspondente no lado oposto

```php
<?php

return [
    Hyperf\Tracer\Aspect\JsonRpcAspect::class,
];
```

#### Configurar a chave de rastreamento de corrotinas

O rastreamento de chamadas de corrotinas não está na configuração unificada; é uma funcionalidade de versão opcional.

Basta configurar `aspects.php`, adicionando o seguinte `Aspect` para ativar.

```php
<?php

return [
    Hyperf\Tracer\Aspect\CoroutineAspect::class,
];
```

### Configurar middleware ou listener

Após configurar o driver, para coletar informações ainda é necessário configurar um middleware ou um listener de eventos do ciclo de requisição para habilitar a funcionalidade de coleta.

- Adicionar middleware

Abra o arquivo `config/autoload/middlewares.php` e habilite o middleware no nó `http`.

```php
<?php

declare(strict_types=1);

return [
    'http' => [
        \Hyperf\Tracer\Middleware\TraceMiddleware::class,
    ],
];
```

- Ou adicionar listener

Abra o arquivo `config/autoload/listeners.php` e adicione o listener.

```php
<?php

declare(strict_types=1);

return [
    \Hyperf\Tracer\Listener\RequestTraceListener::class,
];
```

### Configurar Span tag

Para alguns nomes de Span Tag que o Hyperf coleta automaticamente nas informações de rastreamento, é possível alterar o nome correspondente alterando a configuração de Span Tag. Basta adicionar a configuração `tags` no arquivo de configuração `config/autolaod/opentracing.php`. A configuração de referência é a seguinte. Se o item de configuração existir, prevalece o valor do item; se não existir, prevalece o valor padrão do componente.

```php
return [
    'tags' => [
        // Cliente HTTP (Guzzle)
        'http_client' => [
            'http.url' => 'http.url',
            'http.method' => 'http.method',
            'http.status_code' => 'http.status_code',
        ],
        // Cliente Redis
        'redis' => [
            'arguments' => 'arguments',
            'result' => 'result',
        ],
        // Cliente de banco de dados (hyperf/database)
        'db' => [
            'db.query' => 'db.query',
            'db.statement' => 'db.statement',
            'db.query_time' => 'db.query_time',
        ],
    ]
];
```

### Trocar o amostrador

O amostrador padrão registra a cadeia de chamadas para todas as requisições, o que pode ter certo impacto no desempenho, especialmente no uso de memória. Por isso, só queremos rastrear a cadeia de chamadas quando desejarmos; para isso, precisamos trocar o amostrador. A troca também é simples: tomando o Zipkin como exemplo, basta alterar o valor correspondente ao item de configuração `opentracing.zipkin.sampler` para a instância do seu objeto amostrador, desde que seu objeto amostrador implemente a interface `Zipkin\Sampler`.

### Integrar o serviço de rastreamento da Alibaba Cloud

Quando usamos o serviço de rastreamento de chamadas da Alibaba Cloud, como o lado oposto também suporta o protocolo `Zipkin`, podemos simplesmente alterar o valor de `endpoint_url` no arquivo de configuração `config/autoload/opentracing.php` para o endereço da `region` correspondente da Alibaba Cloud. O endereço específico pode ser obtido no serviço de rastreamento de chamadas da Alibaba Cloud; para mais detalhes, consulte [documentação de ajuda do serviço de rastreamento de chamadas da Alibaba Cloud](https://help.aliyun.com/document_detail/100031.html?spm=a2c4g.11186623.6.547.68f974dcZlg4Mv).

### Usar outro driver Tracer

Você também pode usar qualquer outro driver Tracer compatível com o protocolo OpenTracing. No item Driver, basta preencher qualquer classe que implemente `Hyperf\Tracer\Contract\NamedFactoryInterface`. Essa interface possui apenas uma função make, cujo parâmetro é o nome do driver e que deve retornar uma instância que implemente OpenTracing\Tracer.

## Referência

- [Opentracing](https://opentracing.io)
- [Zipkin](https://zipkin.io/)
- [Jaeger](https://www.jaegertracing.io/)
- [Dapper, sistema de rastreamento para sistemas distribuídos em larga escala](https://bigbully.github.io/Dapper-translation/)
