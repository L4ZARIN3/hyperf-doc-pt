# box, by Hyperf

O Box se dedica a ajudar a melhorar a experiência de programação de aplicações PHP, sendo especialmente útil para aplicações Hyperf. Ele gerencia o ambiente PHP e as dependências relacionadas, além de oferecer a capacidade de empacotar aplicações PHP como programas binários e fornecer um serviço de proxy reverso para gerenciar e implantar serviços Swoole/Swow.

### Uso

#### Instalando o box

##### Mac

```bash
wget https://github.com/hyperf/box/releases/download/v0.5.5/box_x86_64_macos -O box
sudo mv ./box /usr/local/bin/box
sudo chmod 755 /usr/local/bin/box
// Certifique-se de que /usr/local/bin/box está no seu ambiente $PATH, ou coloque o `box` em qualquer caminho $PATH desejado
```

##### Linux x86_64

```bash
wget https://github.com/hyperf/box/releases/download/v0.5.5/box_x86_64_linux -O box
sudo mv ./box /usr/local/bin/box
sudo chmod 755 /usr/local/bin/box
// Certifique-se de que /usr/local/bin/box está no seu ambiente $PATH, ou coloque o `box` em qualquer caminho $PATH desejado
```
##### Linux aarch64

Atualmente não temos um Github Actions Runner AARCH64, por isso não conseguimos compilar os arquivos bin da versão AARCH64 em tempo hábil.

```bash
wget https://github.com/hyperf/box/releases/download/v0.0.3/box_php8.1_aarch64_linux -O box
sudo mv ./box /usr/local/bin/box
sudo chmod 755 /usr/local/bin/box
// Certifique-se de que /usr/local/bin/box está no seu ambiente $PATH, ou coloque o `box` em qualquer caminho $PATH desejado
```

##### Windows

```powershell
curl -o box.exe https://github.com/hyperf/box/releases/download/v0.5.5/box_x64_windows.exe
// Coloque o `box.exe` em qualquer caminho da variável de ambiente Path desejado; além disso, a versão Windows exige que se use `box.exe` em vez de `box` na linha de comando ao executar
```

#### Inicializando o Github Access Token

O Box precisa de um token de acesso do Github para solicitar a API do Github e recuperar as versões dos pacotes.

1. [Crie um Github Access Token](https://github.com/settings/tokens/new), o escopo `workflow` precisa estar marcado;
2. Execute o comando `box config set github.access-token <Your Token>` para definir seu token;
3. Divirta-se ~

#### Configurando o Box Kernel

Por padrão, o Box é suportado pelo Swow Kernel, mas também fornecemos o Swoole Kernel. Você pode alternar para o Swoole Kernel com `box config set kernel swoole`. No entanto, observe que o Swoole Kernel só oferece suporte à versão PHP 8.1 e não suporta a funcionalidade de compilação de programas binários nem o ambiente de sistema Windows.

```bash
// Definir como Swow Kernel [padrão]
box config set kernel swow

// Definir como Swoole Kernel (sem suporte a Windows)
box config set kernel swoole
```

### Comandos

- `box get pkg@version` instala um pacote a partir do remoto; `pkg` é o nome do pacote, `version` é a versão do pacote, e `box get pkg` indica instalar a versão mais recente do pkg. Por exemplo, executar `box get php@8.1` instala o PHP 8.1, e executar `box get composer` instala o bin do composer mais recente
- `box build-prepare` prepara o ambiente relacionado para os comandos `build` e `build-self`
- `box build-self` compila o próprio bin `box`
- `box build <path>` compila a aplicação Hyperf em um arquivo binário
- `box self-update` atualiza o bin `box` para a versão mais recente
- `box config list` exibe todo o conteúdo do arquivo de configuração do box
- `box config get <key>` recupera um valor do arquivo de configuração por chave
- `box config set <key> <value>` define um value no arquivo de configuração por meio de uma key
- `box config unset <key>` remove um valor de configuração por key
- `box config set-php-version <version>` define a versão atual do PHP do box; valores disponíveis: 8.0 | 8.1
- `box config get-php-version <version>` obtém a versão atual do PHP definida no box
- `box reverse-proxy -u <upsteamHost:upstreamPort>` inicia um servidor HTTP de proxy reverso para encaminhar requisições HTTP para os múltiplos servidores upstream especificados
- `box php <argument>` executa qualquer comando PHP com a versão atual do PHP do box
- `box composer <argument>` executa qualquer comando Composer com a versão atual do PHP do box; a versão do bin composer depende do último comando `get composer` executado
- `box php-cs-fixer <argument>` executa qualquer comando `php-cs-fixer` com a versão atual do PHP do box; a versão do bin composer depende do último comando `get php-cs-fixer` executado
- `box cs-fix <argument>` executa o comando `php-cs-fixer fix` com a versão atual do PHP do box; a versão do bin composer depende do último comando `get php-cs-fixer` executado
- `box phpstan <argument>` executa qualquer comando `phpstan` com a versão atual do PHP do box; a versão do bin composer depende do último comando `get phpstan` executado. Este comando só está disponível no box v0.3.0 e versões superiores
- `box pint <argument>` executa qualquer comando `pint` com a versão atual do PHP do box; a versão do bin composer depende do último comando `get pint` executado. Este comando só está disponível no box v0.3.0 e versões superiores
- `box version` exibe o número da versão atual do bin box

### Sobre o Swow-Skeleton

Quem deseja experimentar todos os recursos do Box precisa executar por meio do Swow Kernel, portanto é necessário executar seu projeto com base em [hyperf/swow-skeleton](https://github.com/hyperf/swow-skeleton). Você pode usar o comando `box composer create-project hyperf/swow-skeleton` para criar um projeto esqueleto Swow baseado no Hyperf versão 3.0.
