# Instalação

## Requisitos do servidor

O Hyperf tem alguns requisitos de ambiente de sistema. Ao usar o driver de rede Swoole, ele só pode ser executado em ambientes Linux e Mac, mas, graças ao desenvolvimento da tecnologia de virtualização Docker, no Windows também é possível usar o Docker for Windows como ambiente de execução. Em geral, no ambiente Mac, recomendamos mais o deploy em ambiente local, para evitar que o compartilhamento de disco lento do Docker torne a inicialização do Hyperf lenta. Ao usar o driver de rede Swow, é possível executar em Windows, Linux e Mac.

O projeto [hyperf/hyperf-docker](https://github.com/hyperf/hyperf-docker) já preparou para você Dockerfiles de diversas versões, ou você pode executar diretamente com base na imagem já construída [hyperf/hyperf](https://hub.docker.com/r/hyperf/hyperf).   

Quando você não quiser usar o Docker como base do ambiente de execução, também pode considerar usar o [Box](../eco/box.md) como ambiente base de execução. Se você preferir montar o ambiente por conta própria, precisa garantir que seu ambiente de execução atenda aos seguintes requisitos:   

 - PHP >= 8.2
 - Qualquer um dos seguintes mecanismos de rede
   - [Extensão PHP Swoole](https://github.com/swoole/swoole-src) >= 5.0, com `Short Name` desativado
   - [Extensão PHP Swow](https://github.com/swow/swow) >= 1.4
 - Extensão PHP JSON
 - Extensão PHP Pcntl (somente ao usar o mecanismo Swoole)
 - Extensão PHP OpenSSL (caso precise usar HTTPS)
 - Extensão PHP PDO (caso precise usar o cliente MySQL)
 - Extensão PHP Redis (caso precise usar o cliente Redis)
 - Extensão PHP Protobuf (caso precise usar o servidor ou cliente gRPC)

## Instalar o Hyperf

O Hyperf usa o [Composer](https://getcomposer.org) para gerenciar as dependências do projeto. Antes de usar o Hyperf, certifique-se de que o Composer já esteja instalado em seu ambiente de execução.

### Criar um projeto via `Composer`

Já preparamos um projeto esqueleto para você, com alguns componentes comuns integrados, além dos arquivos e da estrutura de configuração relacionados. Trata-se de uma base de projeto Web que pode ser rapidamente usada para o desenvolvimento de negócios. Na instalação, você pode selecionar as dependências dos componentes conforme suas próprias necessidades.   
Execute o comando abaixo para criar um projeto skeleton no local atual

Baseado no driver Swoole:   
```
composer create-project hyperf/hyperf-skeleton 
```
Baseado no driver Swow:   
```
composer create-project hyperf/swow-skeleton 
```

> Durante a instalação, para opções que você não conhece, basta pressionar Enter, evitando o problema de o serviço não iniciar por ter adicionado automaticamente alguns listeners sem configurá-los corretamente.

### Desenvolvimento com Docker

Supondo que seu ambiente local não atenda aos requisitos do Hyperf, ou que você não esteja muito familiarizado com a configuração do ambiente, você pode usar os métodos abaixo para executar e desenvolver projetos Hyperf:

- Iniciar o contêiner

Você pode mapear para o diretório correspondente na máquina host conforme a situação real; abaixo usamos `/workspace/skeleton` como exemplo

> Se a opção selinux-enabled estiver ativada ao iniciar o docker, o acesso aos recursos da máquina host de dentro do contêiner ficará restrito, então ao iniciar o contêiner você pode adicionar as opções --privileged -u root

```shell
docker run --name hyperf \
-v /workspace/skeleton:/data/project \
-w /data/project \
-p 9501:9501 -it \
--privileged -u root \
--entrypoint /bin/sh \
hyperf/hyperf:8.1-alpine-v3.18-swoole
```

- Criar o projeto

```shell
composer create-project hyperf/hyperf-skeleton
```

- Iniciar o projeto

```shell
cd hyperf-skeleton
php bin/hyperf.php start
```

Em seguida, você poderá ver o código instalado em `/workspace/skeleton/hyperf-skeleton` na máquina host.
Como o Hyperf é um framework CLI persistente, após modificar o código, basta encerrar a instância do processo atual com `CTRL + C` e executar novamente o comando de inicialização `php bin/hyperf.php start`.

## Extensões com problemas de compatibilidade

Como o Hyperf é implementado com base nas corrotinas do Swoole, e o recurso de corrotinas trazido pelo Swoole 4 é algo inédito no PHP, ainda existem problemas de compatibilidade com várias extensões.   
As extensões abaixo (incluindo, mas não se limitando a) causarão certos problemas de compatibilidade e não podem ser usadas em conjunto nem coexistir:

- xhprof
- xdebug (disponível quando a versão do PHP >= 8.2 e a versão do Swoole for maior ou igual a 5.0.2)
- blackfire
- trace
- uopz
