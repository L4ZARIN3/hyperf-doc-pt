# Ciclo de vida

## Ciclo de vida do framework

O Hyperf é executado sobre o [Swoole](http://github.com/swoole/swoole-src). Para compreender plenamente o ciclo de vida do Hyperf, entender o ciclo de vida do [Swoole](http://github.com/swoole/swoole-src) também é essencial.   
O gerenciamento de comandos do Hyperf é fornecido por padrão pelo [symfony/console](https://github.com/symfony/console) *(se você desejar trocar esse componente, também pode alterar o arquivo de entrada do skeleton para o componente que preferir)*. Após executar `php bin/hyperf.php start`, o controle é assumido pela classe de comando `Hyperf\Server\Command\StartServer`, e os `Server` definidos no arquivo de configuração `config/autoload/server.php` são iniciados um a um.   
Quanto ao trabalho de inicialização do contêiner de injeção de dependência, não o implementamos por meio de componentes, pois, uma vez delegado a um componente, esse acoplamento ficaria muito evidente. Portanto, por padrão, é o arquivo de entrada que carrega `config/container.php` para realizá-lo.

## Ciclo de vida da requisição e da corrotina

Ao tratar cada conexão, o Swoole cria por padrão uma corrotina para processá-la, o que se manifesta principalmente nos eventos `onRequest`, `onReceive` e `onConnect`. Assim, pode-se entender que cada requisição é uma corrotina. Como criar corrotinas também é uma operação comum, uma corrotina de requisição pode conter muitas corrotinas. Dentro do mesmo processo, as corrotinas compartilham memória, mas a ordem de escalonamento não é sequencial, e as corrotinas são essencialmente independentes entre si, sem relação pai-filho. Portanto, o tratamento do estado de cada corrotina precisa ser gerenciado por meio do [contexto da corrotina](coroutine.md#contexto-de-corrotina).   
