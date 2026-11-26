# Apresentação da versão 3.2

O Hyperf é um framework PHP de corrotinas progressivo, de alto desempenho e alta flexibilidade, com servidor de corrotinas integrado e uma grande quantidade de componentes comuns. Seu desempenho tem uma melhoria qualitativa em relação aos frameworks tradicionais baseados em `PHP-FPM`; além de oferecer altíssimo desempenho, mantém uma extensibilidade extremamente flexível. Os componentes padrão são todos implementados com base no [padrão PSR](https://www.php-fig.org/psr) e, graças ao poderoso design de injeção de dependência, garantem que a grande maioria dos componentes ou classes seja `substituível` e `reutilizável`.

Além dos comuns `cliente MySQL` e `cliente Redis` em versão de corrotina, a biblioteca de componentes do framework também preparou para você `Eloquent ORM` em versão de corrotina, `servidor e cliente WebSocket`, `servidor e cliente JSON RPC`, `servidor e cliente gRPC`, `cliente Zipkin/Jaeger (OpenTracing)`, `cliente HTTP Guzzle`, `cliente Elasticsearch`, `cliente Consul`, `cliente ETCD`, `componente AMQP`, `centro de configuração Apollo`, `ACM (Application Configuration Management) da Alibaba Cloud`, `centro de configuração ETCD`, `limitador de taxa baseado no algoritmo de token bucket`, `pool de conexões genérico`, `circuit breaker`, `geração de documentação Swagger`, `mecanismo de views`, `gerador de ID global Snowflake` e outros componentes, poupando você do trabalho de implementar as versões de corrotina correspondentes.

O Hyperf também oferece funcionalidades muito práticas, como `contêiner de injeção de dependência baseado em PSR-11`, `anotações`, `programação orientada a aspectos AOP`, `middleware baseado em PSR-15`, `processos personalizados`, `gerenciador de eventos baseado em PSR-14`, `fila de mensagens Redis/RabbitMQ`, `cache automático de modelos`, `cache baseado em PSR-16`, `tarefas agendadas Crontab com precisão de segundos`, `internacionalização`, `validador de formulários Validation`, atendendo a ricos cenários técnicos e de negócios, prontas para uso.

# Propósito do framework

Embora o cenário atual dos frameworks desenvolvidos na linguagem PHP seja de disputa entre muitas soluções, ainda não se vê um framework perfeito que concilie um design elegante com altíssimo desempenho, nem um framework que realmente abra caminho para microsserviços em PHP. Esse é o propósito do Hyperf e de sua equipe: continuaremos investindo e nos dedicando a isso, e convidamos você a se juntar a nós para participar da construção do open source.

# Filosofia de design

`Hyperspeed + Flexibility = Hyperf`: já no nome, adotamos `altíssima velocidade` e `flexibilidade` como os genes do Hyperf.

- Quanto à altíssima velocidade, baseamo-nos nas corrotinas do Swoole e realizamos muitas otimizações no design do framework para garantir a saída de altíssimo desempenho.
- Quanto à flexibilidade, baseamo-nos no poderoso componente de injeção de dependência do Hyperf; os componentes são implementados com base nos contratos do [padrão PSR](https://www.php-fig.org/psr) e em contratos definidos pelo Hyperf, fazendo com que a grande maioria dos componentes ou classes dentro do framework seja substituível.

Com base nessas características, o Hyperf terá ricas possibilidades, como implementar serviços Web, serviços de gateway, middleware distribuído, arquitetura de microsserviços, servidores de jogos, Internet das Coisas (IOT), entre outros.

# Pronto para produção

Fizemos uma grande quantidade de testes unitários nos componentes para garantir a correção da lógica e, ao mesmo tempo, mantemos uma documentação de alta qualidade. Antes de o Hyperf ser oficialmente aberto ao público, ele já havia passado pelo teste severo de ambientes de produção, e só então o abrimos oficialmente. Até hoje, uma grande quantidade de empresas de Internet de grande e médio porte usa o Hyperf em produção.

---

# Sobre esta tradução

Esta é a tradução comunitária da documentação oficial do Hyperf para português brasileiro (pt-BR), feita a partir da documentação em chinês (zh-cn), por ser a versão mais completa e atualizada.

- Versão documentada: **Hyperf 3.2**
- Snapshot de origem: branch `master` de [hyperf/hyperf](https://github.com/hyperf/hyperf), commit `d893568` (21/11/2026)
- Changelog incluído: até **v3.2.6** (14/11/2026)
- Documentação original (zh-cn): https://github.com/hyperf/hyperf/tree/master/docs/zh-cn
- Site oficial do Hyperf: https://hyperf.wiki

Caso encontre algum trecho desatualizado ou incorreto, contribuições são bem-vindas por meio de pull requests.
