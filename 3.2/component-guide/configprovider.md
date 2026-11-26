# Mecanismo ConfigProvider

O mecanismo ConfigProvider é um mecanismo muito importante para a componentização do Hyperf. O `desacoplamento entre componentes`, a `independência dos componentes` e a `reutilização dos componentes` só são possíveis graças a esse mecanismo.   

# O que é o mecanismo ConfigProvider?

Em termos simples, cada componente fornece um `ConfigProvider`, normalmente uma classe `ConfigProvider` no diretório raiz do componente. O `ConfigProvider` fornece todas as informações de configuração do componente correspondente; essas informações são carregadas pelo framework Hyperf durante a inicialização e, por fim, as informações de configuração dentro do `ConfigProvider` são mescladas na classe de implementação correspondente a `Hyperf\Contract\ConfigInterface`, realizando assim a inicialização de configuração necessária para que cada componente seja usado no framework Hyperf.   

O próprio `ConfigProvider` não possui nenhuma dependência, não herda nenhuma classe abstrata e não exige a implementação de nenhuma interface; basta fornecer um método `__invoke` que retorne um array com a estrutura de configuração correspondente.

# Como definir um ConfigProvider?

Normalmente, o `ConfigProvider` é definido no diretório raiz do componente. Uma classe `ConfigProvider` geralmente é assim:

```php
<?php

namespace Hyperf\Foo;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            // Mesclado no arquivo config/autoload/dependencies.php
            'dependencies' => [],
            // Mesclado no arquivo config/autoload/annotations.php
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            // Definição padrão de Command, mesclada em Hyperf\Contract\ConfigInterface; em outras palavras, corresponde a config/autoload/commands.php
            'commands' => [],
            // Semelhante a commands
            'listeners' => [],
            // Arquivo de configuração padrão do componente, ou seja, após executar o comando, o arquivo correspondente em source é copiado para o arquivo correspondente em destination
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'description of this config file.', // Descrição
                    // Recomenda-se colocar a configuração padrão na pasta publish, com o nome do arquivo igual ao nome do componente
                    'source' => __DIR__ . '/../publish/file.php',  // Caminho do arquivo de configuração correspondente
                    'destination' => BASE_PATH . '/config/autoload/file.php', // Copiado para este arquivo neste caminho
                ],
            ],
            // Também é possível continuar definindo outras configurações, que no final serão todas mescladas no armazenamento de configuração correspondente ao ConfigInterface
        ];
    }
}
```

## Explicação do arquivo de configuração padrão

Depois de definir `publish` no `ConfigProvider`, você pode usar o seguinte comando para gerar rapidamente o arquivo de configuração:

```bash
php bin/hyperf.php vendor:publish nome_do_pacote
```

Por exemplo, se o nome do pacote for `hyperf/amqp`, você pode executar o comando para gerar o arquivo de configuração padrão do `amqp`:
```bash
php bin/hyperf.php vendor:publish hyperf/amqp
```

Apenas criar uma classe não fará com que ela seja carregada automaticamente pelo Hyperf. Você ainda precisa adicionar algumas definições no `composer.json` do componente para informar ao Hyperf que esta é uma classe ConfigProvider que precisa ser carregada. Você precisa adicionar a configuração `extra.hyperf.config` no arquivo `composer.json` dentro do componente e especificar o namespace da classe `ConfigProvider` correspondente, conforme mostrado abaixo:

```json
{
    "name": "hyperf/foo",
    "require": {
        "php": ">=7.3"
    },
    "autoload": {
        "psr-4": {
            "Hyperf\\Foo\\": "src/"
        }
    },
    "extra": {
        "hyperf": {
            "config": "Hyperf\\Foo\\ConfigProvider"
        }
    }
}
```

Depois de definir isso, é necessário executar comandos como `composer install`, `composer update` ou `composer dump-autoload` que façam o Composer gerar novamente o arquivo `composer.lock`, para que a configuração possa ser lida normalmente.   

# Fluxo de execução do mecanismo ConfigProvider

A divisão da configuração do `ConfigProvider` não precisa ser necessariamente assim; este é um formato consagrado por convenção. Na verdade, a decisão final sobre como interpretar essas configurações também cabe ao usuário. O usuário pode ajustar o carregamento relacionado modificando o código dentro do arquivo `config/container.php` do projeto Skeleton, o que significa que o arquivo `config/container.php` determina a varredura e o carregamento do `ConfigProvider`.

# Especificações de design de componentes

Como a propriedade `extra` dentro de `composer.json` não tem outra função ou impacto quando os dados não são utilizados, as definições dentro desses componentes não causam qualquer interferência ou impacto quando usadas em outros frameworks. Portanto, o `ConfigProvider` é um mecanismo que atua apenas no framework Hyperf e não causa nenhum impacto em outros frameworks que não utilizam esse mecanismo, o que estabelece a base para a reutilização de componentes. Mas isso também exige que, ao projetar componentes, as seguintes especificações sejam seguidas:

- O design de todas as classes deve permitir o uso por meio de padrões `OOP`; todos os recursos exclusivos do Hyperf devem ser fornecidos como recursos aprimorados e em classes separadas, o que significa que, fora do framework Hyperf, o componente ainda possa ser usado por meios padrão;
- Se as dependências do componente puderem atender ao [padrão PSR](https://www.php-fig.org/psr), dê prioridade a atendê-lo e dependa das interfaces correspondentes em vez das classes de implementação; se houver funcionalidades não incluídas no [padrão PSR](https://www.php-fig.org/psr), dê prioridade a atender às interfaces da biblioteca de contratos definida pelo Hyperf [hyperf/contract](https://github.com/hyperf/contract) e dependa das interfaces correspondentes em vez das classes de implementação;
- As classes de recursos aprimorados adicionadas para implementar funcionalidades exclusivas do Hyperf geralmente também dependem de alguns componentes do Hyperf. Nesse caso, as dependências desses componentes não devem ser escritas no item `require` do `composer.json`, mas sim no item `suggest` como sugestões;
- No design do componente, não se deve realizar nenhuma injeção de dependência por meio de anotações; o modo de injeção deve usar apenas `injeção por construtor`, o que também permite o uso em `OOP`;
- No design do componente, não se deve definir nenhuma funcionalidade por meio de anotações; a definição de funcionalidades deve ser feita apenas por meio do `ConfigProvider`; 
- No design das classes, deve-se, na medida do possível, não armazenar dados de estado, pois isso fará com que a classe não possa ser fornecida como um objeto de ciclo de vida longo e também não possa usar facilmente a injeção de dependência, reduzindo o desempenho até certo ponto. Os dados de estado devem ser armazenados por meio do contexto de corrotina `Hyperf\Context\Context`;
