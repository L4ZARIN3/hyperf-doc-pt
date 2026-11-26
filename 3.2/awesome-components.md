# Biblioteca de componentes de corrotina

Todos os componentes fornecidos oficialmente já passaram pelo processo de corrotinização, podendo ser usados com segurança dentro do Hyperf ou de outros frameworks de corrotina. Graças à abertura e extensibilidade do Hyperf, a comunidade pode desenvolver ou adaptar os mais variados componentes, e por isso o Hyperf possui infinitas possibilidades.
Esta página reunirá os diversos componentes de corrotina adaptados ao Hyperf e as bibliotecas de uso comum já validadas como seguras para uso em corrotinas, para que você possa rapidamente escolher o componente adequado para atender às suas necessidades.

> A ordem dos componentes segue a ordem de inclusão

## Como enviar meu componente?

Se o componente de corrotina que você desenvolveu é compatível com o Hyperf, você pode enviar seu `Pull Request` diretamente para o branch `master` do projeto [hyperf/hyperf](https://github.com/hyperf/hyperf), ou seja, alterando a página atual `(zh-cn/awesome-components.md)`.

## Como adaptar ao Hyperf?

Fornecemos um [Guia de desenvolvimento de componentes do Hyperf](component-guide/intro.md) para ajudá-lo a desenvolver componentes do Hyperf ou adaptar o framework Hyperf.

# Lista de componentes

## Rotas

- [nikic/fastroute](https://github.com/nikic/FastRoute) Um roteador de alta velocidade de uso comum
- [lazychanger/urlrewrite](https://github.com/lazychanger/urlrewrite) Uma ferramenta de reescrita de URL baseada em PSR-7 com as mesmas regras de rota do [nikic/fastroute](https://github.com/nikic/FastRoute)

## Eventos

- [hyperf/event](https://github.com/hyperf/event) Gerenciador de eventos baseado em PSR-14 fornecido oficialmente pelo Hyperf

## Logs

- [hyperf/logger](https://github.com/hyperf/logger) Gerenciador de logs baseado em PSR-3 fornecido oficialmente pelo Hyperf, uma abstração e encapsulamento sobre o monolog

## Comandos

- [hyperf/command](https://github.com/hyperf/command) Componente de gerenciamento de comandos fornecido oficialmente pelo Hyperf, baseado na extensão [symfony/console](https://github.com/symfony/console) e com suporte a anotações
- [symfony/console](https://github.com/symfony/console) Componente independente de gerenciamento de comandos fornecido pelo Symfony

## Banco de dados

- [hyperf/database](https://github.com/hyperf/database) ORM de banco de dados derivado do Eloquent fornecido oficialmente pelo Hyperf, reutilizável em outros frameworks
- [hyperf/model-cache](https://github.com/hyperf/model-cache) Componente de cache automático de modelos baseado no componente [hyperf/database](https://github.com/hyperf/database) fornecido oficialmente pelo Hyperf
- [reasno/fastmongo](https://github.com/Reasno/fastmongo) Cliente `MongoDB` corrotinizado implementado com base em `hyperf/gotask`
- [hyperf-ext/translatable](https://github.com/hyperf-ext/translatable) Fornece capacidade multilíngue para modelos
- [233cy/hyperf-tenant](https://github.com/233cy/hyperf-tenant) Fornece distinção de campos multi-tenant para modelos

## Mecanismos de busca

- [hyperf/elasticsearch](https://github.com/hyperf/elasticsearch) Cliente de corrotina Elasticsearch fornecido oficialmente pelo Hyperf
- [liangguifeng/hyperf-scout-meilisearch](https://github.com/liangguifeng/hyperf-scout-meilisearch) Driver Meilisearch adaptado ao hyperf/scout (referência laravel/scout).
- [chungou/elasticsearch](https://github.com/kaychem/hyperf-elasticsearch) Um construtor simples de Elasticsearch

## Contêiner de injeção de dependência

- [hyperf/di](https://github.com/hyperf/di) Contêiner de injeção de dependência com suporte a anotações e AOP fornecido oficialmente pelo Hyperf
- [hyperf/pimple](https://github.com/hyperf-cloud/pimple-integration) Componente de contêiner leve em conformidade com a especificação `PSR11`, implementado com base em `pimple/pimple`. Pode reduzir o custo de usar os componentes do `Hyperf` em outros frameworks

## Serviços

- [hyperf/http-server](https://github.com/hyperf/http-server) Servidor HTTP fornecido oficialmente pelo Hyperf
- [hyperf/grpc-server](https://github.com/hyperf/grpc-server) Servidor GRPC fornecido oficialmente pelo Hyperf
- [hyperf/websocket-server](https://github.com/hyperf/websocket-server) Servidor WebSocket fornecido oficialmente pelo Hyperf
- [hyperf/rpc-server](https://github.com/hyperf/rpc-server) Servidor de abstração RPC genérico fornecido oficialmente pelo Hyperf

## Clientes

- [hyperf/consul](https://github.com/hyperf/consul) Cliente de corrotina Consul fornecido oficialmente pelo Hyperf
- [hyperf/grpc-client](https://github.com/hyperf/grpc-client) Cliente de corrotina gRPC fornecido oficialmente pelo Hyperf
- [hyperf/etcd](https://github.com/hyperf/etcd) Cliente de corrotina ETCD fornecido oficialmente pelo Hyperf
- [hyperf/rpc-client](https://github.com/hyperf/rpc-client) Cliente de corrotina de abstração RPC genérico fornecido oficialmente pelo Hyperf
- [hyperf/guzzle](https://github.com/hyperf/guzzle) Cliente de corrotina Guzzle HTTP fornecido oficialmente pelo Hyperf
- [hyperf/redis](https://github.com/hyperf/redis) Cliente de corrotina Redis fornecido oficialmente pelo Hyperf
- [hyperf/websocket-client](https://github.com/hyperf/websocket-client) Cliente de corrotina WebSocket fornecido oficialmente pelo Hyperf
- [hyperf/cache](https://github.com/hyperf/cache) Cliente de corrotina de cache baseado em PSR-16 fornecido oficialmente pelo Hyperf, com suporte ao uso via anotações
- [friendsofhyperf/http-client](https://github.com/friendsofhyperf/http-client) Cliente de corrotina Guzzle HTTP baseado no Hyperf
- [friendsofhyperf/openai-client](https://github.com/friendsofhyperf/openai-client) Cliente OpenAI baseado no Hyperf

## Filas de mensagens

- [hyperf/amqp](https://github.com/hyperf/amqp) Componente AMQP de corrotina fornecido oficialmente pelo Hyperf
- [hyperf/async-queue](https://github.com/hyperf/async-queue) Componente de fila assíncrona simples baseado em Redis fornecido oficialmente pelo Hyperf
- [hooklife/hyperf-aliyun-amqp](https://github.com/hooklife/hyperf-aliyun-amqp) Faz o componente hyperf/amqp suportar o AMQP da Alibaba Cloud

## Centro de configuração

- [hyperf/config-apollo](https://github.com/hyperf/config-apollo) Componente de integração com o centro de configuração Apollo fornecido oficialmente pelo Hyperf
- [hyperf/config-aliyun-acm](https://github.com/hyperf/config-aliyun-acm) Componente de integração com o serviço de configuração de aplicações ACM da Alibaba Cloud fornecido oficialmente pelo Hyperf
- [hyperf/config-etcd](https://github.com/hyperf/config-etcd) Componente de integração com o centro de configuração ETCD fornecido oficialmente pelo Hyperf

## RPC

- [hyperf/json-rpc](https://github.com/hyperf/json-rpc) Componente de protocolo JSON-RPC fornecido oficialmente pelo Hyperf
- [hyperf/rpc-multiplex](https://github.com/hyperf/rpc-multiplex) Componente RPC multiplexado fornecido oficialmente pelo Hyperf
- [hyperf/roc](https://github.com/hyperf/roc) Componente RPC Server multiplexado na versão Golang fornecido oficialmente pelo Hyperf
- [limingxinleo/roc-skeleton](https://github.com/limingxinleo/roc-skeleton) Pacote esqueleto de RPC Server multiplexado na versão Golang

## Governança de serviços

- [hyperf/rate-limit](https://github.com/hyperf/rate-limit) Componente de limitação de taxa baseado no algoritmo de token bucket fornecido oficialmente pelo Hyperf
- [hyperf/load-balancer](https://github.com/hyperf/load-balancer) Componente de balanceamento de carga fornecido oficialmente pelo Hyperf
- [hyperf/service-governance](https://github.com/hyperf/service-governance) Componente de governança de serviços fornecido oficialmente pelo Hyperf
- [hyperf/tracer](https://github.com/hyperf/tracer) Componente de tracing distribuído de chamadas OpenTracing fornecido oficialmente pelo Hyperf
- [hyperf/circuit-breaker](https://github.com/hyperf/circuit-breaker) Componente de circuit breaker de serviços fornecido oficialmente pelo Hyperf
- [pudongping/hyperf-throttle-requests](https://github.com/pudongping/hyperf-throttle-requests) Limitador de frequência de requisições adaptado ao framework Hyperf. Sua funcionalidade é semelhante ao middleware throttle do framework Laravel.
- [friendsofhyperf/sentry](https://github.com/friendsofhyperf/sentry) Componente [Sentry](https://sentry.io) adaptado ao framework Hyperf, usado para monitoramento de exceções e de desempenho.

## Tarefas agendadas

- [hyperf/crontab](https://github.com/hyperf/crontab) Componente de tarefas agendadas com precisão de segundos fornecido oficialmente pelo Hyperf

## Gerador de ID

- [hyperf/snowflake](https://github.com/hyperf/snowflake) Componente gerador de ID Snowflake fornecido oficialmente pelo Hyperf
- [tangwei/snowflake](https://github.com/tw2066/snowflake) Baseado no componente `hyperf/snowflake`, aprimora a manutenção do `ID da máquina de trabalho`

## Geração de documentação

- [hyperf/swagger](https://github.com/hyperf/swagger) Componente de geração automática de documentação Swagger fornecido oficialmente pelo Hyperf (beta)
- [tangwei/swagger](https://github.com/tw2066/api-docs) Um componente que gera automaticamente documentação swagger com base em tipos PHP (DTO), com varredura automática na inicialização, geração automática de rotas (UI) e validação por anotações

## Graphql

- [hyperf/graphql](https://github.com/hyperf/graphql) Componente de servidor Graphql fornecido oficialmente pelo Hyperf (beta)

## Hot update/Hot reload

- [hyperf/watcher](watcher.md) Componente oficial de hot update
- [ha-ni-cc/hyperf-watch](https://github.com/ha-ni-cc/hyperf-watch) Um componente genérico de hot update implementado com base em Swoole
- [mix-php/swoolefor](https://github.com/mix-php/swoolefor) Um componente genérico de hot update implementado pelo Mixphp
- [buexplain/go-watch](https://github.com/buexplain/go-watch) Um componente genérico de hot update implementado na linguagem Go
- [remy/nodemon](https://github.com/remy/nodemon) Um componente genérico de hot update implementado com base em node.js

> Warning: Não use a funcionalidade de `hot update/hot reload` em ambiente de produção

## Swoole

- [hyperf/task](https://github.com/hyperf/task) Componente Task fornecido oficialmente pelo Hyperf, que encapsula e abstrai o mecanismo Task do Swoole, oferecendo um uso conveniente via anotações
- [hyperf/gotask](https://github.com/hyperf/gotask) O GoTask inicia um processo Go como sidecar do processo principal do Swoole através da funcionalidade de gerenciamento de processos do Swoole, entregando tarefas ao sidecar por meio de comunicação entre processos e recebendo o valor de retorno. Pode ser entendido como um Swoole TaskWorker na versão Go

## Desenvolvimento e depuração

- [firstphp/wsdebug](https://github.com/lamplife/wsdebug) Componente de desenvolvimento e depuração para observação em tempo real de erros e exceções via `WebSocket`
- [qbhy/hyperf-multi-env](https://github.com/qbhy/hyperf-multi-env) Suporta a funcionalidade de múltiplos arquivos de configuração env semelhante ao laravel, por exemplo, através de `APP_ENV=testing` é possível carregar a configuração de `.env.testing` sobrepondo o `.env` padrão
- [qiutuleng/hyperf-dump-server](https://github.com/qiutuleng/hyperf-dump-server) Fornece uma função `dump` que permite imprimir variáveis ou dados do programa em outra janela de linha de comando, baseado no componente `Var-Dump Server` do Symfony
- [leearvin/hyperf-tinker](https://github.com/Arvin-Lee/hyperf-tinker) Fornece um shell interativo de contêiner Hyperf baseado em PsySH
- [friendsofhyperf/telescope](https://github.com/friendsofhyperf/telescope) Ferramenta de depuração adaptada ao framework Hyperf.

## Autenticação e autorização

- [fx/hyperf-http-auth](https://github.com/nfangxu/hyperf-http-auth) Reescrito a partir do componente auth do laravel, adaptado ao framework hyperf
- [96qbhy/hyperf-auth](https://github.com/qbhy/hyperf-auth) Inspirado no design do componente auth do laravel, suporta drivers jwt, session, sso (login único em múltiplos dispositivos)
- [hyperf-ext/jwt](https://github.com/hyperf-ext/jwt) Componente JWT, implementa a capacidade completa de autenticação JWT
- [hyperf-ext/auth](https://github.com/hyperf-ext/auth) Portado de `illuminate/auth`, implementa basicamente de forma completa os recursos do Laravel Auth
- [donjan-deng/hyperf-casbin](https://github.com/donjan-deng/hyperf-casbin) Framework de controle de acesso open source [Casbin](https://casbin.org/docs/zh-CN/overview) adaptado ao Hyperf

## Testes

- [hyperf/testing](https://github.com/hyperf/testing) Componente de testes unitários fornecido oficialmente pelo Hyperf
- [friendsofhyperf/pest-plugin-hyperf](https://github.com/friendsofhyperf/pest-plugin-hyperf) Plugin [Pest](https://pestphp.com/) adaptado ao Hyperf, fornecendo suporte ao ambiente de corrotina para o Pest

## Bloqueio distribuído

- [lysice/hyperf-redis-lock](https://github.com/Lysice/hyperf-redis-lock) Reescrito a partir do componente lock do Laravel, adaptado ao framework Hyperf
- [pudongping/hyperf-wise-locksmith](https://github.com/pudongping/hyperf-wise-locksmith) Biblioteca de mutex adaptada ao framework hyperf, usada para proporcionar execução ordenada de código PHP em cenários de alta concorrência. Suporta bloqueio de arquivo, bloqueio distribuído, red lock e mutex em nível de corrotina

## Transações distribuídas

- [dtm-php/dtm-client](https://github.com/dtm-php/dtm-client) Componente cliente de transações distribuídas dtm com suporte ao Hyperf

## Configuração por anotações

- [hyperf-helper/dependency](https://github.com/lazychanger/hyperf-helper-dependency) Configura rapidamente as relações de dependência usando anotações, com suporte a prioridade de dependências

## DTO

- [fatbit/form-request-param](https://github.com/duncanxia97/hyperf-form-request-param) - Componente de validação de parâmetros de requisição fortemente tipados (validação de formulário) e injeção automática baseado em `DTO`.

## SDKs de terceiros

- [yurunsoft/pay-sdk](https://github.com/Yurunsoft/PaySDK) SDK de pagamento Alipay/WeChat com suporte a corrotinas do Swoole
- [yurunsoft/yurun-oauth-login](https://github.com/Yurunsoft/YurunOAuthLogin) SDK de autorização de login de terceiros com suporte a corrotinas do Swoole (QQ, WeChat, Weibo, GitHub, Gitee etc.)
- [w7corp/wechat](sdks/wechat.md) EasyWeChat, um popular SDK não oficial do WeChat
- [yansongda/hyperf-pay](https://github.com/yansongda/hyperf-pay) Componente de pagamento com suporte a `Alipay/WeChat`, implementado com base em [yansongda/pay](https://github.com/yansongda/pay), adaptado ao framework `Hyperf`
- [alapi/hyperf-meilisearch](https://github.com/anhao/hyperf-meilisearch) Cliente meilisearch fornecido para o Hyperf Scout
- [vinchan/message-notify](https://github.com/VinchanGit/message-notify) Componente de notificação de alerta de monitoramento de exceções do Hyperf (robô de grupo DingTalk, robô de grupo Feishu, e-mail, robô de canal QQ, robô de grupo WeCom)
