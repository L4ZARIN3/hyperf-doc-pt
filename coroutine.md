# Corrotina

## Conceito

O Hyperf roda sobre as corrotinas do `Swoole 5` e do `Swow`, e esse é um dos grandes fatores que permitem ao Hyperf oferecer alto desempenho.

### O modelo de funcionamento do PHP-FPM

Antes de falarmos sobre o que é uma corrotina, vamos falar primeiro sobre o modelo de funcionamento da arquitetura tradicional `PHP-FPM`. O `PHP-FPM` é um gerenciador `FastCGI` multiprocesso, sendo o modelo de execução usado pela grande maioria das aplicações `PHP`. Suponhamos que usemos o `Nginx` para fornecer o serviço `HTTP` (o mesmo vale para o `Apache`): todas as requisições iniciadas pelos clientes chegam primeiro ao `Nginx`, que então encaminha a requisição, via protocolo `FastCGI`, para o `PHP-FPM` processar. Os `processos Worker` do `PHP-FPM` obtêm o processamento da requisição CGI de forma preemptiva. Esse processamento significa aguardar a análise do script `PHP`, aguardar o retorno do resultado do processamento do negócio e, ao concluir, reciclar o subprocesso. Todo esse processo é uma espera bloqueante, o que significa que a quantidade de requisições que o `PHP-FPM` consegue processar é igual à quantidade de processos que ele possui. Suponhamos que o `PHP-FPM` tenha `200` `processos Worker` e que uma requisição consuma `1` segundo; de forma simples, teoricamente o servidor inteiro conseguirá processar no máximo `200` requisições, ou seja, o `QPS` será de `200/s`. Em cenários de alta concorrência, esse desempenho costuma ser insuficiente. Ainda que se possa usar o `Nginx` como balanceamento de carga em conjunto com vários servidores `PHP-FPM` para fornecer o serviço, devido ao modelo de trabalho com espera bloqueante do `PHP-FPM`, uma requisição ocupará ao menos uma conexão `MySQL`, e com múltiplos nós em alta concorrência serão geradas inúmeras conexões `MySQL`. Como o valor padrão do número máximo de conexões do `MySQL` é `100`, embora seja possível alterá-lo, é evidente que esse modelo não consegue lidar bem com cenários de alta concorrência.

### Sistema assíncrono e não bloqueante

Em cenários de alta concorrência, o modelo assíncrono e não bloqueante mostra vantagens evidentes. A vantagem intuitiva é que o `processo Worker` deixa de processar uma requisição de forma síncrona e bloqueante e passa a poder processar várias requisições ao mesmo tempo, sem espera de `I/O`, com capacidade de concorrência extremamente forte, podendo iniciar ou manter uma grande quantidade de requisições simultaneamente. A desvantagem mais intuitiva, que todos provavelmente já conhecem, são os callbacks intermináveis: a lógica de negócio precisa ser implementada dentro da função de callback correspondente. Se a lógica de negócio envolver vários pedidos de `I/O`, existirão muitas camadas de funções de callback. Abaixo segue um exemplo de pseudocódigo no estilo de callback assíncrono do `Swoole 1.x`.

```php
$db = new swoole_mysql();
$config = array(
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'test',
    'password' => 'test',
    'database' => 'test',
);

$db->connect($config, function ($db, $r) {
    // Consulta um registro na tabela users
    $sql = 'select * from users where id = 1';
    $db->query($sql, function(swoole_mysql $db, $r) {
        if ($r !== false) {
            // Após a consulta bem-sucedida, altera um registro
            $updateSql = 'update users set name="new name" where id = 1';
            $db->query($updateSql, function (swoole_mysql $db, $r) {
                $rows = $db->affected_rows;
                if ($r === true) {
                    return $this->response->end('Atualizado com sucesso');
                }
            });
        }
        $db->close();
    });
});
```

> Atenção: módulos assíncronos como o `MySQL` foram removidos na [4.3.0](https://wiki.swoole.com/#/version/bc?id=430) e transferidos para o [swoole_async](https://github.com/swoole/ext-async).

Pelo trecho de código acima, é possível perceber que praticamente cada operação exige uma função de callback. Em cenários de negócio complexos, o aninhamento de callbacks e a estrutura do código certamente o deixarão frustrado. Na verdade, não é difícil perceber que essa forma de escrever lembra um pouco a forma de escrever métodos assíncronos em `JavaScript`, e o `JavaScript` também ofereceu várias soluções para isso (soluções que, claro, vêm de outras linguagens de programação), como `Promise`, `yield + generator` e `async/await`. O `Promise` é uma forma de encapsular callbacks, enquanto `yield + generator` e `async/await` exigem adicionar explicitamente algumas marcações sintáticas ao código. Comparadas às funções de callback, todas são soluções muito boas, mas você precisa dedicar tempo para entender seu mecanismo de implementação e sua sintaxe.   
As corrotinas do `Swoole` também são uma solução para os callbacks assíncronos. Na linguagem `PHP`, as corrotinas do `Swoole` e o `yield + generator` são ambas soluções de corrotina. As soluções de corrotina permitem escrever código assíncrono de forma quase idêntica ao código síncrono. A diferença explícita é que, no mecanismo de corrotina de `yield + generator`, cada chamada de código de operação de `I/O` precisa ter `yield` adicionado antes para realizar a troca de corrotina, em cada nível de chamada, caso contrário ocorrerão erros inesperados. Já a solução de corrotina do `Swoole` é muito mais sofisticada em comparação: ao encontrar `I/O`, a camada inferior realiza automaticamente a troca implícita de corrotina, sem necessidade de adicionar qualquer sintaxe extra, sem precisar colocar `yield` antes do código, e o processo de troca de corrotina ocorre de forma silenciosa, aliviando enormemente a carga mental de manter um sistema assíncrono.

### O que é uma corrotina?

Já sabemos que as corrotinas resolvem bem o problema de desenvolvimento de sistemas assíncronos e não bloqueantes. Mas o que é, afinal, uma corrotina? Por definição, **uma corrotina é uma thread leve, agendada e gerenciada pelo código do usuário, e não pelo núcleo do sistema operacional, ou seja, em espaço de usuário**. Pode-se entendê-la diretamente como uma implementação não padronizada de thread, mas quando ocorre a troca é o próprio usuário quem decide, e não o sistema operacional, ao distribuir tempo de `CPU`. Concretamente, cada `processo Worker` do `Swoole` possui um agendador de corrotinas para agendar as corrotinas, e o momento da troca de corrotina é quando se encontra uma operação de `I/O` ou uma troca explícita no código. Dentro do processo, as corrotinas são executadas em modo single-thread, o que significa que em um mesmo instante apenas uma corrotina está em execução dentro de um processo, e o momento da troca é claro, dispensando o tratamento de problemas de diversos locks de sincronização típicos da programação multithread.   
O código dentro de uma única corrotina continua sendo executado de forma serial. Pensando em um serviço HTTP com corrotinas, cada requisição é uma corrotina. Por exemplo, suponha que `Corrotina A` tenha sido criada para a `Requisição A` e `Corrotina B` para a `Requisição B`. Então, ao processar a `Corrotina A`, o código chega a uma instrução de consulta ao `MySQL`; nesse momento a `Corrotina A` dispara uma troca de corrotina e passa a aguardar o retorno do dispositivo de `I/O`. Então ocorre a troca para a `Corrotina B`, e começa-se a processar a lógica da `Corrotina B`. Quando se encontra novamente uma operação de `I/O`, dispara-se outra troca de corrotina, e volta-se a executar a `Corrotina A` a partir do ponto onde ela havia sido interrompida, e assim por diante: ao encontrar uma operação de `I/O`, troca-se para outra corrotina para continuar a execução, em vez de ficar bloqueado esperando.   
Aqui é possível perceber um problema: **a operação de consulta ao `MySQL` dentro da `Corrotina A` precisa ser uma operação assíncrona e não bloqueante, caso contrário, devido ao bloqueio, o agendador de corrotinas não conseguirá trocar para outra corrotina e continuar a execução**. Esse é também um dos problemas a serem evitados na programação com corrotinas.

### Quais são as diferenças entre corrotinas e threads comuns?

Diz-se que a corrotina é uma thread leve. Tanto corrotinas quanto threads são adequadas para cenários de múltiplas tarefas. Nesse aspecto, corrotinas e threads são bastante semelhantes: ambas têm seu próprio contexto e podem compartilhar variáveis globais. A diferença, porém, é que, em um mesmo instante, várias threads podem estar em execução, mas no caso das corrotinas do `Swoole` só pode haver uma, e as demais corrotinas ficam em estado de pausa. Além disso, threads comuns são preemptivas: qual thread obtém recursos é decidido pelo sistema operacional, enquanto as corrotinas são cooperativas, e o direito de execução é distribuído pelo próprio espaço de usuário.

## Cuidados na programação com corrotinas

### Não pode haver código bloqueante

O bloqueio de código dentro de uma corrotina impede que o agendador de corrotinas troque para outra corrotina e continue a execução, portanto jamais podemos ter código bloqueante dentro de uma corrotina. Suponhamos que tenhamos iniciado `4` `Worker` para processar requisições `HTTP` (normalmente a quantidade de `Worker` iniciados é igual ao número de núcleos de `CPU` ou o dobro). Se houver bloqueio no código, e supondo teoricamente que cada requisição bloqueie por `1` segundo, então o `QPS` do sistema também degradará para `4/s`, o que sem dúvida é uma degradação para uma situação semelhante à do `PHP-FPM`. Por isso, absolutamente não podemos ter código bloqueante dentro de corrotinas.   

Então, quais são, afinal, os códigos bloqueantes? Podemos considerar de forma simples que a grande maioria dos clientes de `MySQL`, `Redis`, `Memcache`, `MongoDB`, `HTTP`, `Socket` etc. que você conhece e que não são funções assíncronas fornecidas pelo `Swoole`, além de operações de arquivo, `sleep/usleep` e similares, são funções bloqueantes. Isso cobre praticamente todas as operações do dia a dia. Como resolver então? O `Swoole` fornece clientes de corrotina para `MySQL`, `PostgreSQL`, `Redis`, `HTTP` e `Socket`, e a partir do `Swoole 4.1` oferece o método de corrotinização em uma linha `\Swoole\Runtime::enableCoroutine()`. Basta executar essa linha de código antes de usar corrotinas e o `Swoole` transformará todas as operações de `socket` que usam `php_stream` em `I/O` assíncrono agendado por corrotina. Pode-se entender que, com exceção de `curl`, a grande maioria das operações nativas é aplicável. Sobre essa parte, consulte a [documentação do Swoole](https://wiki.swoole.com/#/runtime) para obter informações mais específicas.  

No `Hyperf` já cuidamos de tudo isso para você; basta você se preocupar com os códigos bloqueantes que o `\Swoole\Runtime::enableCoroutine()` ainda não consegue corrotinizar.

### Não se pode armazenar estado em variáveis globais

Em aplicações persistentes do `Swoole`, as variáveis globais dentro de um `Worker` são compartilhadas dentro desse `Worker`. E, pela introdução sobre corrotinas, sabemos que dentro de um mesmo `Worker` existirão várias corrotinas e ocorrerão trocas de corrotina, o que significa que um `Worker` processará simultaneamente, em um período de tempo, o código de várias corrotinas (ou, diretamente, de várias requisições). Isso significa que, se forem usadas variáveis globais para armazenar estado, elas poderão ser usadas por várias corrotinas, ou seja, diferentes requisições podem misturar dados. Aqui, variáveis globais referem-se a variáveis que começam com `$_`, como `$_GET/$_POST/$_REQUEST/$_SESSION/$_COOKIE/$_SERVER`, variáveis `global` e propriedades estáticas `static`.    
Então, o que fazer quando precisamos usar esses recursos?   

Quanto às variáveis globais, todas surgem junto com uma `Requisição (Request)`, e no `Hyperf` a `Requisição (Request)` e a `Resposta (Response)` são tratadas pelo [hyperf/http-message](https://github.com/hyperf/http-message) por meio da implementação do [PSR-7](https://www.php-fig.org/psr/psr-7/). Portanto, todos os valores das variáveis globais podem ser obtidos no objeto de `Requisição (Request)`;   

Quanto às variáveis `global` e às variáveis `static`, no modo `PHP-FPM` elas basicamente vivem dentro do ciclo de vida de uma requisição. Já no `Hyperf`, por ser uma aplicação `CLI`, existem dois tipos de ciclo de vida longo: o `ciclo global` e o `ciclo de requisição (ciclo de corrotina)`.   
- Ciclo global: basta criar uma variável estática para uso global. A variável estática significa que, após o serviço ser iniciado, qualquer corrotina e lógica de código compartilha os dados dessa variável estática, o que significa que os dados armazenados não podem ser especialmente destinados a uma requisição ou a uma corrotina específica;
- Ciclo de corrotina: como o `Hyperf` cria automaticamente uma corrotina para cada requisição, um ciclo de corrotina também pode ser entendido aqui como um ciclo de requisição. Dentro da corrotina, todos os dados de estado devem ser armazenados na classe `Hyperf\Context\Context`, usando `get` e `set` dessa classe para ler e armazenar dados de qualquer estrutura. Os dados lidos ou armazenados nessa classe `Context (contexto de corrotina)` durante a execução de qualquer corrotina são restritos à corrotina correspondente, e ao final da corrotina os dados de contexto relacionados também são automaticamente destruídos.

### Limite do número máximo de corrotinas

Para o `Swoole Server`, define-se o parâmetro `max_coroutine` pelo método `set`, usado para configurar o número máximo de corrotinas que um processo `Worker` pode ter. Como, com o aumento do número de corrotinas processadas pelo processo `Worker`, a memória correspondente ocupada também aumenta, para evitar ultrapassar o limite `memory_limit` do `PHP`, defina esse valor com base no resultado de testes de carga do negócio real. O valor padrão do `Swoole` é `100000` (quando a versão do `Swoole` é inferior a `v4.4.0-beta`, o valor padrão é `3000`). No projeto `hyperf-skeleton`, o valor padrão é `100000`.

## Usando corrotinas

### Criando uma corrotina

Basta usar a função `Hyperf\Coroutine\co(callable $callable)` ou `Hyperf\Coroutine\go(callable $callable)`, ou ainda `Hyperf\Coroutine\Coroutine::create(callable $callable)`, para criar uma corrotina. Dentro da corrotina é possível usar os métodos e clientes relacionados a corrotinas.

### Verificando se está atualmente em um ambiente de corrotina

Em algumas situações queremos verificar se algo está sendo executado dentro de um ambiente de corrotina, o que serve de base de decisão para códigos compatíveis com ambientes de corrotina e sem corrotina. Podemos obter o resultado pelo método `Hyperf\Coroutine\Coroutine::inCoroutine(): bool`.

### Obtendo o ID da corrotina atual

Em algumas situações precisamos fazer alguma lógica com base no `ID da corrotina`, como lógicas de `contexto de corrotina`. Podemos obter o `ID da corrotina` atual por meio de `Hyperf\Coroutine\Coroutine::id(): int`; caso não esteja em ambiente de corrotina, retornará `-1`.

### Canal Channel

Semelhante ao `chan` da linguagem `Go`, o `Channel` pode oferecer suporte ao modelo de múltiplos produtores e múltiplos consumidores de corrotina. A camada inferior implementa automaticamente a troca e o agendamento de corrotinas. O `Channel` é semelhante aos arrays do `PHP`: ocupa apenas memória, sem qualquer outra solicitação de recursos extra, todas as operações são operações de memória, sem consumo de `I/O`, e seu uso é semelhante ao da fila `SplQueue`.   
O `Channel` é usado principalmente para comunicação entre corrotinas. Quando queremos retornar alguns dados de uma corrotina para outra, podemos transmiti-los pelo `Channel`.   

Principais métodos:   
- `Channel->push`: quando há outras corrotinas na fila aguardando `pop` de dados, desperta automaticamente, em ordem, uma corrotina consumidora. Quando a fila está cheia, faz `yield` automaticamente para ceder o controle, aguardando que outras corrotinas consumam os dados
- `Channel->pop`: quando a fila está vazia, faz `yield` automaticamente, aguardando que outras corrotinas produzam dados. Após consumir os dados, novos dados podem ser escritos na fila, despertando automaticamente, em ordem, uma corrotina produtora.

Abaixo segue um exemplo simples de comunicação entre corrotinas:

```php
<?php
co(function () {
    $channel = new \Swoole\Coroutine\Channel();
    co(function () use ($channel) {
        $channel->push('data');
    });
    $data = $channel->pop();
});
```

### Recurso Defer

Quando queremos executar algum código ao final da corrotina, podemos usar a função `defer(callable $callable)` ou `Hyperf\Coroutine::defer(callable $callable)` para armazenar um trecho de função na forma de uma `pilha (stack)`. As funções dentro da `pilha (stack)` serão executadas uma a uma ao final da corrotina atual, seguindo o fluxo `último a entrar, primeiro a sair`.

### Recurso WaitGroup

O `WaitGroup` é um recurso derivado do `Channel`. Se já tivemos contato com a linguagem `Go`, conhecemos o recurso `WaitGroup`. No `Hyperf`, o `WaitGroup` serve para fazer a corrotina principal ficar bloqueada aguardando até que todas as subcorrotinas relacionadas tenham concluído suas tarefas, e só então continuar a execução. A espera bloqueante mencionada aqui vale apenas para a corrotina principal (ou seja, a corrotina atual) e não bloqueia o processo atual.      
Vamos demonstrar esse recurso com um trecho de código:   

```php
<?php
$wg = new \Hyperf\Coroutine\WaitGroup();
// Incrementa o contador em dois
$wg->add(2);
// Cria a corrotina A
co(function () use ($wg) {
    // some code
    // Decrementa o contador em um
    $wg->done();
});
// Cria a corrotina B
co(function () use ($wg) {
    // some code
    // Decrementa o contador em um
    $wg->done();
});
// Aguarda a conclusão da execução das corrotinas A e B
$wg->wait();
```

> Atenção: o próprio `WaitGroup` também precisa estar dentro de uma corrotina para ser usado

### Recurso Parallel

O recurso `Parallel` é uma forma de uso mais conveniente abstraída pelo Hyperf com base no recurso `WaitGroup`. Vamos demonstrá-la com um trecho de código.

```php
<?php
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;

$parallel = new Parallel();
$parallel->add(function () {
    sleep(1);
    return Coroutine::id();
});
$parallel->add(function () {
    sleep(1);
    return Coroutine::id();
});

try{
    // O resultado de $results é [1, 2]
   $results = $parallel->wait(); 
} catch(ParallelExecutionException $e){
    // $e->getResults() obtém o valor de retorno dentro da corrotina.
    // $e->getThrowables() obtém as exceções ocorridas dentro da corrotina.
}
```
> Atenção: a exceção `Hyperf\Coroutine\Exception\ParallelExecutionException` só é lançada na versão 1.1.6 e em versões mais recentes

Pelo código acima, podemos ver que foram necessários apenas `1` segundo para obter os `ID` de duas corrotinas diferentes. Ao chamar `add(callable $callable)`, a classe `Parallel` cria automaticamente uma corrotina para a tarefa e a adiciona ao agendamento do `WaitGroup`.    
Além disso, podemos simplificar ainda mais o código acima com a função `parallel(array $callables)`, alcançando o mesmo objetivo. Abaixo está o código simplificado.

```php
<?php
use Hyperf\Coroutine\Coroutine;

// Você também pode usar chaves no array de parâmetros passado para distinguir as subcorrotinas; o resultado retornado também retornará o resultado correspondente de acordo com a chave
$result = parallel([
    function () {
        sleep(1);
        return Coroutine::id();
    },
    function () {
        sleep(1);
        return Coroutine::id();
    }
]);
```

> Atenção: o próprio `Parallel` também precisa estar dentro de uma corrotina para ser usado

#### Limitando o número máximo de corrotinas executando ao mesmo tempo no Parallel

Quando adicionamos muitas tarefas ao `Parallel`, supondo que sejam tarefas de requisição, disparar todas as requisições de uma vez pode fazer com que o serviço do outro lado não consiga processar tantas requisições recebidas de uma só vez, com risco de indisponibilidade. Por isso é necessário proteger adequadamente o outro lado. Mas também queremos acelerar o tempo dessas requisições por meio do mecanismo `Parallel`. Para isso, podemos passar o primeiro parâmetro ao instanciar o objeto `Parallel` para definir o número máximo de corrotinas em execução. Por exemplo, se quisermos definir o número máximo de corrotinas como `5`, isso significa que no `Parallel` haverá no máximo `5` corrotinas em execução, e somente quando uma das `5` terminar é que a próxima corrotina será iniciada, até que todas as corrotinas concluam suas tarefas. Segue um exemplo de código:

```php
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;

$parallel = new Parallel(5);
for ($i = 0; $i < 20; $i++) {
    $parallel->add(function () {
        sleep(1);
        return Coroutine::id();
    });
} 

try{
   $results = $parallel->wait(); 
} catch(ParallelExecutionException $e){
    // $e->getResults() obtém o valor de retorno dentro da corrotina.
    // $e->getThrowables() obtém as exceções ocorridas dentro da corrotina.
}
```

### Controle de execução de corrotinas Concurrent

O `Hyperf\Coroutine\Concurrent` é implementado com base em `Swoole\Coroutine\Channel` e serve para controlar o número máximo de corrotinas executando simultaneamente dentro de um bloco de código.

No exemplo a seguir, ao executar simultaneamente `10` subcorrotinas, haverá bloqueio dentro do laço, mas apenas a corrotina atual será bloqueada, até que uma posição seja liberada e o laço continue executando a próxima subcorrotina.

```php
<?php

use Hyperf\Coroutine\Concurrent;

$concurrent = new Concurrent(10);

for ($i = 0; $i < 15; ++$i) {
    $concurrent->create(function () {
        // Do something...
    });
}
```

### Contexto de corrotina

Como dentro de um mesmo processo as corrotinas compartilham memória, mas a execução e a troca de corrotinas não são sequenciais, é difícil controlarmos qual é a corrotina atual **(na verdade é possível, mas normalmente ninguém faz isso)**. Por isso precisamos que, ao ocorrer a troca de corrotina, o contexto correspondente também possa ser trocado ao mesmo tempo.
No `Hyperf`, implementar o gerenciamento de contexto de corrotina é muito simples: basta usar os métodos estáticos `set(string $id, $value)`, `get(string $id, $default = null)`, `has(string $id)` e `override(string $id, \Closure $closure)` da classe `Hyperf\Context\Context` para concluir o gerenciamento dos dados de contexto. Os valores definidos e obtidos por esses métodos são restritos à corrotina atual, e ao final da corrotina o contexto correspondente também é liberado automaticamente, sem necessidade de gerenciamento manual e sem risco de vazamento de memória.

#### Hyperf\Context\Context::set()

Chame o método `set(string $id, $value)` para armazenar um valor no contexto da corrotina atual, assim:

```php
<?php
use Hyperf\Context\Context;

// Armazena a string bar com a chave foo no contexto da corrotina atual
$foo = Context::set('foo', 'bar');
// O método set retorna o value como valor de retorno do método, portanto o valor de $foo é bar
```

#### Hyperf\Context\Context::get()

Chame o método `get(string $id, $default = null)` para obter do contexto da corrotina atual um valor armazenado com a `chave` `$id`; se não existir, retorna `$default`, assim:

```php
<?php
use Hyperf\Context\Context;

// Obtém do contexto da corrotina atual o valor com chave foo; se não existir, retorna a string bar
$foo = Context::get('foo', 'bar');
```

#### Hyperf\Context\Context::has()

Chame o método `has(string $id)` para verificar se existe no contexto da corrotina atual um valor armazenado com a `chave` `$id`; se existir, retorna `true`, caso contrário retorna `false`, assim:

```php
<?php
use Hyperf\Context\Context;

// Verifica no contexto da corrotina atual se o valor com chave foo existe
$foo = Context::has('foo');
```

#### Hyperf\Context\Context::override()

Quando precisamos fazer algum processamento de contexto mais complexo, por exemplo, verificar primeiro se uma `chave` existe, se existir obter o `value` e fazer alguma modificação nele e depois definir o `value` de volta no contêiner de contexto, haverá condições de verificação bastante complexas. Nesse caso, podemos simplesmente chamar o método `override` para implementar essa lógica, assim:

```php
<?php
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Context\Context;

// Obtém o objeto $request do contexto da corrotina, define o Header com chave foo e depois o salva novamente no contexto da corrotina
$request = Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) {
    return $request->withAddedHeader('foo', 'bar');
});
```

### Nível de Runtime Hook do Swoole

O framework fornece na função de entrada a constante `SWOOLE_HOOK_FLAGS`. Se você precisar alterar o nível de `Runtime Hook` de todo o projeto, por exemplo, para suportar `corrotina CURL` e a versão do `Swoole` for anterior a `v4.5.4`, pode modificar o código aqui, assim.

```php
<?php
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL);
``` 

!> Se a versão do Swoole for >= `v4.5.4`, não é necessário fazer nenhuma alteração.
