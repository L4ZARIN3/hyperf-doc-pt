# Montagem do DaoCloud Devops

Como desenvolvedor individual, usar um `Gitlab` próprio e um `cluster Docker Swarm` obviamente tem um custo inaceitável. Aqui apresentamos um serviço de `Devops`: o `DaoCloud`.

O motivo da recomendação é simples: ele é gratuito e ainda funciona normalmente.

[DaoCloud](https://dashboard.daocloud.io)

## Como usar

Você só precisa se atentar às três abas: `Projetos`, `Aplicações` e `Gerenciamento de clusters`.

### Criar um projeto

Primeiro, precisamos criar um novo projeto em `Projetos`. O DaoCloud suporta vários registros de imagens, que podem ser escolhidos conforme a necessidade.

Aqui usamos o repositório [hyperf-demo](https://github.com/limingxinleo/hyperf-demo) como exemplo de configuração. Após a criação bem-sucedida, a url correspondente aparecerá em `WebHooks` do respectivo repositório `GitHub`.

Em seguida, modificamos o `Dockerfile` do repositório, adicionando `&& apk add wget \` abaixo de `apk add`. O motivo específico não é muito claro; se o `wget` não for atualizado, haverá problemas no uso. Porém, com um Gitlab CI próprio não há nenhum problema.

A cada commit de código, o `DaoCloud` executará a operação de empacotamento correspondente no projeto que você criou.

### Criar um cluster 

Depois vamos em `Gerenciamento de clusters`, criamos um `cluster` e adicionamos um `host`.

Não entraremos em detalhes aqui; basta seguir os passos acima um a um.

### Criar uma aplicação

Clique em Aplicações -> Criar aplicação -> selecione o projeto recém-criado (é necessário ter feito pelo menos um commit de código, e o `DaoCloud` ter empacotado e gerado a imagem, para poder fazer o deploy) -> Implantar

Siga as instruções; o usuário pode escolher uma porta não utilizada para a porta do host. Como o `DaoCloud` não tem a funcionalidade `Config` do `Swarm`, mapeamos proativamente o `.env` para dentro do contêiner.

Adicione um `Volume`: diretório do contêiner `/opt/www/.env`, diretório do host é o local onde você armazena o arquivo `.env`, e "gravável" como não gravável.

Depois clique em Implantar agora.

### Teste

Acesse a porta usada há pouco a partir da máquina host e você verá os dados da interface de boas-vindas do `Hyperf`.

```
$ curl http://127.0.0.1:9501
{"code":0,"data":{"user":"Hyperf","method":"GET","message":"Hello Hyperf."}}
```
