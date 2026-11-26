# Transação distribuída

[dtm-client](https://github.com/dtm-php/dtm-client) é um componente cliente de transação distribuída DTM desenvolvido e mantido pela equipe Hyperf; em conjunto com o DTM-Server, pode realizar o gerenciamento de transações distribuídas, sendo estável e utilizável em ambiente de produção.   
[seata/seata-php](https://github.com/seata/seata-php) é um componente cliente Seata PHP desenvolvido pela equipe Hyperf e contribuído para a comunidade open source Seata; em conjunto com o Seata-Server, pode realizar o gerenciamento de transações distribuídas. Atualmente ainda está em desenvolvimento e iteração, ainda não podendo ser usado em ambiente de produção; esperamos que todos possam participar juntos para acelerar a incubação.

# Introdução ao DTM-Client

[dtm/dtm-client](https://packagist.org/packages/dtm/dtm-client) é o cliente PHP do gerenciador de transações distribuídas [DTM](https://github.com/dtm-labs/dtm), já suporta os modos de transação distribuída TCC, Saga, XA e mensagem em duas fases, e implementa respectivamente a comunicação com o DTM Server via protocolo HTTP ou gRPC. Esse cliente pode rodar com segurança em ambientes PHP-FPM e corrotina Swoole, e ainda oferece suporte a funcionalidades mais fáceis de usar para o [Hyperf](https://github.com/hyperf/hyperf).

# Sobre o DTM

O DTM é um gerenciador de transações distribuídas open source implementado na linguagem Go, que oferece recursos poderosos de transações combinadas entre linguagens e mecanismos de armazenamento. O DTM resolve elegantemente problemas difíceis de transações distribuídas, como idempotência, compensação vazia e suspensão, e também oferece uma solução de transação distribuída simples de usar, de alto desempenho e fácil de escalar horizontalmente.

## Destaques

* Extremamente fácil de começar
    - Inicia o serviço com zero configuração e fornece interfaces HTTP muito simples, reduzindo enormemente a dificuldade de começar com transações distribuídas
* Multi-linguagem
    - Adequado para empresas com stacks de múltiplas linguagens. Conveniente para uso em Go, Python, PHP, NodeJs, Ruby, C# e outras linguagens.
* Uso simples
    - Os desenvolvedores não precisam mais se preocupar com suspensão, compensação vazia, idempotência e outros problemas; a tecnologia pioneira de barreira de subtransação trata isso em seu lugar
* Fácil de fazer deploy e fácil de escalar
    - Depende apenas de MySQL/Redis; deploy simples, fácil de clusterizar e fácil de escalar horizontalmente
* Suporte a vários protocolos de transação distribuída
    - TCC, SAGA, XA, mensagem em duas fases; solução completa para diversos problemas de transações distribuídas

## Comparação

Em linguagens que não são Java, ainda não vimos um gerenciador de transações distribuídas maduro além do DTM; portanto, aqui comparamos o DTM com o Seata, o projeto open source mais maduro em Java:

|  Recurso  | DTM |                                              SEATA                                               |Observação|
|:-----:|:----:|:------------------------------------------------------------------------------------------------:|:----:|
|[Linguagens suportadas](https://dtm.pub/other/opensource.html#lang) |<span style="color:green">Go, C#, Java, Python, PHP...</span>|                            <span style="color:orange">Java, Go</span>                             |O DTM pode integrar facilmente uma nova linguagem|
|[Mecanismo de armazenamento](https://dtm.pub/other/opensource.html#store) |<span style="color:green"> Suporta banco de dados, Redis, Mongo etc. </span>|                              <span style="color:orange"> banco de dados </span>                               ||
|[Tratamento de exceções](https://dtm.pub/other/opensource.html#exception)| <span style="color:green"> A barreira de subtransação trata automaticamente </span>|                              <span style="color:orange"> Tratamento manual </span>                              |O DTM resolve idempotência, suspensão e compensação vazia|
|[Transação SAGA](https://dtm.pub/other/opensource.html#saga) |<span style="color:green"> Extremamente simples e fácil de usar </span> |                             <span style="color:orange"> Máquina de estados complexa </span>                              ||
|[Mensagem em duas fases](https://dtm.pub/other/opensource.html#msg)|<span style="color:green">✓</span>|                                 <span style="color:red">✗</span>                                 |Arquitetura de consistência final por mensagens mais simples|
|[Transação TCC](https://dtm.pub/other/opensource.html#tcc)| <span style="color:green">✓</span>|                                <span style="color:green">✓</span>                                ||
|[Transação XA](https://dtm.pub/other/opensource.html#xa)|<span style="color:green">✓</span>|                                <span style="color:green">✓</span>                                ||
|[Transação AT](https://dtm.pub/other/opensource.html#at)|<span style="color:orange"> Recomenda-se usar XA</span>|                                <span style="color:green">✓</span>                                |AT é semelhante ao XA, mas tem rollback sujo|
|[Múltiplas fontes de dados em um único serviço](https://dtm.pub/other/opensource.html#multidb)|<span style="color:green">✓</span>|                                 <span style="color:red">✗</span>                                 ||
|[Protocolo de comunicação](https://dtm.pub/other/opensource.html#protocol)|HTTP, gRPC|                                             Protocolos como Dubbo                                             |O DTM é mais amigável para cloud native|
|[Quantidade de stars](https://dtm.pub/other/opensource.html#star)|<img src="https://img.shields.io/github/stars/dtm-labs/dtm.svg?style=social" alt="github stars"/>| <img src="https://img.shields.io/github/stars/seata/seata.svg?style=social" alt="github stars"/> |O DTM lançou a versão 0.1 em 2021-06-04 e se desenvolve rapidamente|

Pelos recursos comparados acima, o DTM tem grandes vantagens em muitos aspectos. Se considerar suporte a múltiplas linguagens e a múltiplos mecanismos de armazenamento, então o DTM é, sem dúvida, a sua primeira escolha.

# Instalação

Com o Composer, é muito fácil instalar o dtm-client

```bash
composer require dtm/dtm-client
```

* Não esqueça de iniciar o DTM Server ao usar

# Configuração

## Arquivo de configuração

Se você estiver usando no framework Hyperf, após instalar o componente, pode publicar o arquivo de configuração em `./config/autoload/dtm.php` com o comando `vendor:publish` abaixo

```bash
php bin/hyperf.php vendor:publish dtm/dtm-client
```

Se você estiver usando em um framework que não é o Hyperf, pode copiar o arquivo `./vendor/dtm/dtm-client/publish/dtm.php` para o diretório de configuração correspondente.

```php
use DtmClient\Constants\Protocol;
use DtmClient\Constants\DbType;

return [
    // Protocolo de comunicação entre o cliente e o DTM Server; suporta Protocol::HTTP e Protocol::GRPC
    'protocol' => Protocol::HTTP,
    // Endereço do DTM Server
    'server' => '127.0.0.1',
    // Porta do DTM Server
    'port' => [
        'http' => 36789,
        'grpc' => 36790,
    ],
    // Configuração da barreira de subtransação
    'barrier' => [
        // Configuração da barreira de subtransação no modo DB
        'db' => [
            'type' => DbType::MySQL
        ],
        // Configuração da barreira de subtransação no modo Redis
        'redis' => [
            // Tempo de expiração dos registros da barreira de subtransação
            'expire_seconds' => 7 * 86400,
        ],
        // Classes que aplicam a barreira de subtransação fora do framework Hyperf
        'apply' => [],
    ],
    // Configuração geral do cliente Guzzle no protocolo HTTP
    'guzzle' => [
        'options' => [],
    ],
];
```

## Configurar middleware

Antes de usar, é necessário configurar o middleware `DtmClient\Middleware\DtmMiddleware` como middleware global do Server; esse middleware suporta a especificação PSR-15 e pode ser aplicado a vários frameworks que a suportam.   
A configuração de middleware no Hyperf pode ser consultada no capítulo [Documentação do Hyperf - Middleware](https://www.hyperf.wiki/2.2/#/zh-cn/middleware/middleware).

# Uso

O uso do dtm-client é muito simples; fornecemos um projeto de exemplo [dtm-php/dtm-sample](https://github.com/dtm-php/dtm-sample) para ajudar todos a entender e depurar melhor.   
Antes de usar este componente, também recomendamos fortemente que você leia primeiro a [documentação oficial do DTM](https://dtm.pub/) para um entendimento mais detalhado.

## Modo TCC

O modo TCC é uma solução de transação flexível muito popular; o conceito TCC é formado pelas iniciais das três palavras Try-Confirm-Cancel, proposto pela primeira vez por Pat Helland em 2007 em um artigo intitulado "Life beyond Distributed Transactions:an Apostate’s Opinion".

### As 3 fases do TCC

Fase Try: tenta executar, conclui todas as verificações de negócio (consistência), reserva os recursos de negócio necessários (quase isolamento)  
Fase Confirm: se todos os Try dos ramos forem bem-sucedidos, passa-se à fase Confirm. O Confirm executa de fato o negócio, sem fazer nenhuma verificação de negócio, usando apenas os recursos de negócio reservados na fase Try  
Fase Cancel: se algum Try de qualquer ramo falhar, passa-se à fase Cancel. O Cancel libera os recursos de negócio reservados na fase Try.

Se quisermos realizar um negócio semelhante a uma transferência bancária interbancária, com a transferência de saída (TransOut) e a transferência de entrada (TransIn) em microsserviços diferentes, o diagrama de sequência típico de uma transação TCC concluída com sucesso é o seguinte:

<img src="https://dtm.pub/assets/tcc_normal.dea14fb3.jpg" height=600 />

### Exemplo de código

A seguir demonstramos o uso no framework Hyperf; outros frameworks são semelhantes

```php
<?php
namespace App\Controller;

use DtmClient\TCC;
use DtmClient\TransContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Throwable;

#[Controller(prefix: '/tcc')]
class TccController
{

    protected string $serviceUri = 'http://127.0.0.1:9501';

    #[Inject]
    protected TCC $tcc;

    #[GetMapping(path: 'successCase')]
    public function successCase()
    {
        try {
            
            $this->tcc->globalTransaction(function (TCC $tcc) {
                // Cria os dados de chamada da subtransação A
                $tcc->callBranch(
                    // Parâmetros para chamar o método Try
                    ['amount' => 30],
                    // URL do método Try
                    $this->serviceUri . '/tcc/transA/try',
                    // URL do método Confirm
                    $this->serviceUri . '/tcc/transA/confirm',
                    // URL do método Cancel
                    $this->serviceUri . '/tcc/transA/cancel'
                );
                // Cria os dados de chamada da subtransação B, e assim por diante
                $tcc->callBranch(
                    ['amount' => 30],
                    $this->serviceUri . '/tcc/transB/try',
                    $this->serviceUri . '/tcc/transB/confirm',
                    $this->serviceUri . '/tcc/transB/cancel'
                );
            });
        } catch (Throwable $e) {
            var_dump($e->getMessage(), $e->getTraceAsString());
        }
        // Obtém o ID da transação global via TransContext::getGid() e retorna
        return TransContext::getGid();
    }
}
```

## Modo Saga

O modo Saga é uma das soluções mais famosas no campo de transações distribuídas e também muito popular em diversos sistemas; apareceu originalmente em 1987 no artigo [SAGAS](https://www.cs.cornell.edu/andru/cs711/2002fa/reading/sagas.pdf) publicado por Hector Garcaa-Molrna & Kenneth Salem.

Saga é uma transação de consistência final e também uma transação flexível, também chamada de transação de longa duração (Long-running-transaction). Saga é composta por uma série de transações locais. Cada transação local, após atualizar o banco de dados, publica uma mensagem ou um evento para disparar a execução da próxima transação local na transação global Saga. Se uma transação local falhar porque algumas regras de negócio não podem ser satisfeitas, o Saga executará operações de compensação de todas as transações que foram confirmadas com sucesso antes da transação que falhou. Portanto, o modo Saga, em comparação com o modo TCC, por não ter a etapa de reserva de recursos, muitas vezes se torna mais complicado ao implementar a lógica de reversão.

### Divisão de subtransações Saga

Por exemplo, se quisermos realizar um negócio semelhante a uma transferência bancária interbancária, transferindo 30 yuan da conta A para a conta B, de acordo com o princípio da transação Saga, dividimos toda a transação global nos seguintes serviços:
- Serviço de transferência de saída (TransOut), aqui será realizada a operação de deduzir 30 yuan da conta A
- Serviço de compensação da transferência de saída (TransOutCompensate), reverte a operação de saída acima, ou seja, adiciona 30 yuan à conta A
- Serviço de transferência de entrada (TransIn), aqui será realizada a adição de 30 yuan à conta B
- Serviço de compensação da transferência de entrada (TransInCompensate), reverte a operação de entrada acima, ou seja, deduz 30 yuan da conta B

A lógica de toda a transação é:

Executar a saída com sucesso => executar a entrada com sucesso => transação global concluída

Se ocorrer um erro no meio, por exemplo, erro ao transferir para a conta B, então serão chamadas as operações de compensação dos ramos já executados, ou seja:

Executar a saída com sucesso => executar a entrada com falha => executar a compensação da entrada com sucesso => executar a compensação da saída com sucesso => reversão da transação global concluída

A seguir está um diagrama de sequência típico de uma transação SAGA concluída com sucesso:

<img src="https://dtm.pub/assets/saga_normal.a2849672.jpg" height=428 />

### Exemplo de código

A seguir demonstramos o uso no framework Hyperf; outros frameworks são semelhantes

```php
namespace App\Controller;

use DtmClient\Saga;
use DtmClient\TransContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: '/saga')]
class SagaController
{

    protected string $serviceUri = 'http://127.0.0.1:9501';
    
    #[Inject]
    protected Saga $saga;

    #[GetMapping(path: 'successCase')]
    public function successCase(): string
    {
        $payload = ['amount' => 50];
        // Inicializa a transação Saga
        $this->saga->init();
        // Adiciona a subtransação de saída
        $this->saga->add(
            $this->serviceUri . '/saga/transOut', 
            $this->serviceUri . '/saga/transOutCompensate', 
            $payload
        );
        // Adiciona a subtransação de entrada
        $this->saga->add(
            $this->serviceUri . '/saga/transIn', 
            $this->serviceUri . '/saga/transInCompensate', 
            $payload
        );
        // Confirma a transação Saga
        $this->saga->submit();
        // Obtém o ID da transação global via TransContext::getGid() e retorna
        return TransContext::getGid();
    }
}
```

## Modo XA
XA é uma especificação de transação distribuída proposta pela organização X/Open; a especificação XA define principalmente a interface entre o gerenciador de transações (TM) (global) e o gerenciador de recursos (RM) (local). Bancos de dados locais como o mysql desempenham o papel de RM no XA

O XA é dividido em duas fases:

Primeira fase (prepare): todos os participantes RM preparam a execução da transação e bloqueiam os recursos necessários. Quando o participante está ready, informa ao TM que está pronto. Segunda fase (commit/rollback): quando o gerenciador de transações (TM) confirma que todos os participantes (RM) estão ready, envia o comando commit para todos os participantes.

Atualmente, os principais bancos de dados suportam basicamente transações XA, incluindo mysql, oracle, sqlserver, postgre

A seguir está um diagrama de sequência típico de uma transação XA concluída com sucesso

<img src="https://dtm.pub/assets/xa_normal.5a0ce600.jpg" height=600/>

### Exemplo de código

A seguir demonstramos o uso no framework Hyperf; outros frameworks são semelhantes
```php
<?php

namespace App\Controller;

use App\Grpc\GrpcClient;
use DtmClient\DbTransaction\DBTransactionInterface;
use DtmClient\TransContext;
use DtmClient\XA;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: '/xa')]
class XAController
{

    private GrpcClient $grpcClient;

    protected string $serviceUri = 'http://127.0.0.1:9502';

    public function __construct(
        private XA $xa,
        protected ConfigInterface $config,
    ) {
        $server = $this->config->get('dtm.server', '127.0.0.1');
        $port = $this->config->get('dtm.port.grpc', 36790);
        $hostname = $server . ':' . $port;
        $this->grpcClient = new GrpcClient($hostname);
    }


    #[GetMapping(path: 'successCase')]
    public function successCase(): string
    {
        $payload = ['amount' => 50];
        // Inicia a transação global XA
        $gid = $this->xa->generateGid();
        $this->xa->globalTransaction($gid, function () use ($payload) {
            // Chama a interface da subtransação
            $respone = $this->xa->callBranch($this->serviceUri . '/xa/api/transIn', $payload);
            // Obtém a estrutura de retorno da subtransação no modo XA http
            /* @var ResponseInterface $respone */
            $respone->getBody()->getContents();
            // Chama a interface da subtransação
            $payload = ['amount' => 10];
            $this->xa->callBranch($this->serviceUri . '/xa/api/transOut', $payload);
        });
        // Obtém o ID da transação global via TransContext::getGid() e retorna
        return TransContext::getGid();
    }

    #[RequestMapping(methods: ["GET", "POST", "PUT"], path: 'api/transIn')]
    public function transIn(RequestInterface $request): array
    {
        $content = $request->post('amount');
        $amount = $content['amount'] ?? 50;
        // Simula o método transIn em um sistema distribuído
        $this->xa->localTransaction(function (DBTransactionInterface $dbTransaction) use ($amount) {
            // Use DBTransactionInterface para lidar com a transação local do Mysql
            $dbTransaction->xaExecute('UPDATE `order` set `amount` = `amount` + ? where id = 1', [$amount]);
        });

        return ['status' => 0, 'message' => 'ok'];
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    #[RequestMapping(methods: ["GET", "POST", "PUT"], path: 'api/transOut')]
    public function transOut(RequestInterface $request): array
    {
        $content = $request->post('amount');
        $amount = $content['amount'] ?? 10;
        // Simula o método transOut em um sistema distribuído
        $this->xa->localTransaction(function (DBTransactionInterface $dbTransaction) use ($amount) {
            // Use DBTransactionInterface para lidar com a transação local do Mysql
            $dbTransaction->xaExecute('UPDATE `order` set `amount` = `amount` - ? where id = 2', [$amount]);
        });

        return ['status' => 0, 'message' => 'ok'];
    }
}

```
O código acima primeiro registra uma transação global XA e então adiciona duas subtransações transIn e transOut. Depois que todas as subtransações são executadas com sucesso, é submetido ao dtm. Após receber a transação global xa submetida, o dtm chamará o xa commit de todas as subtransações, concluindo toda a transação xa.
