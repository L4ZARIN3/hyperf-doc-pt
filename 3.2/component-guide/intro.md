# Prefácio do guia

Para ajudar os desenvolvedores a desenvolver componentes para o Hyperf de uma forma melhor e a construir o ecossistema em conjunto, fornecemos este guia para orientar os desenvolvedores no desenvolvimento de componentes. Antes de ler este guia, é necessário que você tenha feito uma leitura **completa** da documentação do Hyperf, especialmente os capítulos [Corrotina](../coroutine.md) e [Injeção de dependência](../di.md); se faltar compreensão suficiente sobre os componentes básicos do Hyperf, poderão ocorrer erros durante o desenvolvimento.

# Objetivo do desenvolvimento de componentes

No desenvolvimento tradicional sob a arquitetura PHP-FPM, quando precisamos usar uma biblioteca de terceiros para resolver nossas necessidades, normalmente introduzimos diretamente uma `biblioteca (Library)` correspondente por meio do Composer. Porém, no Hyperf, devido às duas características de `aplicação persistente` e `corrotina`, o ciclo de vida e o modelo da aplicação apresentam algumas diferenças, portanto nem todas as `bibliotecas (Library)` podem ser usadas diretamente no Hyperf; é claro que algumas `bibliotecas (Library)` bem projetadas também podem ser usadas diretamente. Lendo este guia por completo, você saberá como identificar se algumas `bibliotecas (Library)` podem ser usadas diretamente no projeto e, caso não possam, quais alterações devem ser feitas.

# Preparação para o desenvolvimento de componentes

A preparação para o desenvolvimento mencionada aqui, além das condições básicas de execução do Hyperf, está mais voltada para como organizar a estrutura do código de forma mais conveniente para o trabalho de desenvolvimento de componentes. Observe que a forma abaixo pode não se aplicar ao ambiente de desenvolvimento Windows for Docker devido ao *problema de links simbólicos que não podem ser acessados*.   
Na organização do código, recomendamos clonar, em um mesmo diretório, o esqueleto de projeto [hyperf/hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton) e o projeto de biblioteca de componentes [hyperf/hyperf](https://github.com/hyperf/hyperf). Realize as operações abaixo e obtenha a seguinte estrutura:

```bash
// Instale o skeleton e conclua a configuração
composer create-project hyperf/hyperf-skeleton 

// Clone o projeto da biblioteca de componentes hyperf; lembre-se de substituir hyperf pelo seu ID do Github, ou seja, clone o projeto que você fez Fork
git clone git@github.com:hyperf/hyperf.git
```

Obtenha a seguinte estrutura:

```
.
├── hyperf
│   ├── bin
│   └── src
└── hyperf-skeleton
    ├── app
    ├── bin
    ├── config
    ├── runtime
    ├── test
    └── vendor
```

O objetivo disso é permitir que o projeto `hyperf-skeleton` use diretamente o formato de origem `path`, fazendo com que o Composer carregue diretamente o projeto dentro da pasta `hyperf` como dependência no diretório `vendor` do projeto `hyperf-skeleton`. Adicionamos um item `repositories` no arquivo `composer.json` dentro de `hyperf-skeleton`, conforme abaixo:

```json
{
    "repositories": {
        "hyperf": {
            "type": "path",
            "url": "../hyperf/src/*"
        }
    }
}
```
Depois, dentro do projeto `hyperf-skeleton`, exclua o arquivo `composer.lock` e a pasta `vendor`, e execute `composer update` para atualizar novamente as dependências. O comando é o seguinte:

```bash
cd hyperf-skeleton
rm -rf composer.lock && rm -rf vendor && composer update
```
   
Por fim, todas as pastas de projeto dentro da pasta `hyperf-skeleton/vendor/hyperf` estarão conectadas por meio de `links simbólicos (softlink)` às pastas dentro de `hyperf`. Podemos usar o comando `ls -l` para verificar se o `link simbólico (softlink)` foi criado com sucesso:

```bash
cd vendor/hyperf/
ls -l
```

Quando virmos uma relação de links semelhante à abaixo, isso indica que o `link simbólico (softlink)` foi criado com sucesso:

```
cache -> ../../../hyperf/src/cache
command -> ../../../hyperf/src/command
config -> ../../../hyperf/src/config
contract -> ../../../hyperf/src/contract
database -> ../../../hyperf/src/database
db-connection -> ../../../hyperf/src/db-connection
devtool -> ../../../hyperf/src/devtool
di -> ../../../hyperf/src/di
dispatcher -> ../../../hyperf/src/dispatcher
event -> ../../../hyperf/src/event
exception-handler -> ../../../hyperf/src/exception-handler
framework -> ../../../hyperf/src/framework
guzzle -> ../../../hyperf/src/guzzle
http-message -> ../../../hyperf/src/http-message
http-server -> ../../../hyperf/src/http-server
logger -> ../../../hyperf/src/logger
memory -> ../../../hyperf/src/memory
paginator -> ../../../hyperf/src/paginator
pool -> ../../../hyperf/src/pool
process -> ../../../hyperf/src/process
redis -> ../../../hyperf/src/redis
server -> ../../../hyperf/src/server
testing -> ../../../hyperf/src/testing
support -> ../../../hyperf/src/support
```

Assim, alcançamos o objetivo de modificar diretamente os arquivos dentro de `vendor/hyperf` na IDE, mas o que é modificado é o código dentro de `hyperf`, de modo que, no final, podemos fazer `commit` diretamente no projeto `hyperf` e enviar um `Pull Request (PR)` para o branch principal.
