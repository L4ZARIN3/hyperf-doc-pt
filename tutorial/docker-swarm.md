# Construção de cluster Docker Swarm

Atualmente, a tecnologia de contêineres já é bastante madura; até mesmo empresas de pequeno e médio porte podem construir facilmente seu próprio serviço de cluster Docker com base no Gitlab, no serviço de registro de imagens Aliyun e no Docker Swarm.

## Instalar o Docker

```
curl -sSL https://get.docker.com/ | sh
```

Modifique o arquivo `/lib/systemd/system/docker.service`, permitindo usar `TCP` para conectar ao `Docker`

> Basta acrescentar o `-H tcp://0.0.0.0:2375` ao final

```
ExecStart=/usr/bin/dockerd -H fd:// --containerd=/run/containerd/containerd.sock -H tcp://0.0.0.0:2375
```

Se não estiver usando a conta `root`, você pode usar o comando a seguir para que não seja necessário adicionar `sudo` a cada execução de `docker`

```
usermod -aG docker $USER
```

### Configurar o endereço de espelho do registro

Devido a problemas como a velocidade de acesso lenta em rotas internacionais, podemos configurar um endereço de espelho de registro para o Docker a fim de melhorar esses problemas de rede, como o [acelerador de imagens Docker da Aliyun](https://help.aliyun.com/document_detail/60750.html). Podemos solicitar um acelerador `Docker`, configurá-lo no arquivo `/etc/docker/daemon.json` do servidor adicionando o conteúdo abaixo e então reiniciar o `Docker`. Preencha o endereço abaixo com o endereço do acelerador que você obteve.

```json
{"registry-mirrors": ["https://xxxxx.mirror.aliyuncs.com"]}
```

## Construir o serviço Gitlab

### Instalar o Gitlab

#### Modificar a porta padrão do sshd

Primeiro precisamos modificar a porta do serviço `sshd` do servidor, alterando a porta padrão `22` para a porta `2222` (ou outra porta não utilizada), para que o `gitlab` possa usar a porta `22` para conexões `ssh`.

```
$ vim /etc/ssh/sshd_config

# Altera a porta padrão para 2222
Port 2222

# Reinicia o serviço
$ systemctl restart sshd.service
```

Faça login novamente na máquina

```
ssh -p 2222 root@host
```

#### Instalar o Gitlab

Vamos iniciar um serviço Gitlab via Docker, assim:

> O hostname é obrigatório; se não houver domínio, pode-se preencher diretamente o endereço externo

```
sudo docker run -d --hostname gitlab.xxx.cn \
--publish 443:443 --publish 80:80 --publish 22:22 \
--name gitlab --restart always --volume /srv/gitlab/config:/etc/gitlab \
--volume /srv/gitlab/logs:/var/log/gitlab \
--volume /srv/gitlab/data:/var/opt/gitlab \
gitlab/gitlab-ce:latest
```

O nome de usuário padrão é `root`; a senha inicial é obtida da seguinte forma

```shell
docker exec gitlab cat /etc/gitlab/initial_root_password
```

### Instalar o gitlab-runner

> Aqui é recomendável fazer o deploy separado do servidor `Gitlab`, fornecendo um servidor dedicado apenas para o runner.

Usamos como exemplo o modo de instalação do `CentOS`; para os demais, consulte a [documentação oficial do Gitlab](https://docs.gitlab.com/runner/install/linux-repository.html)

```
curl -L https://packages.gitlab.com/install/repositories/runner/gitlab-runner/script.rpm.sh | sudo bash
yum install gitlab-runner
```

Claro que também é possível usar o comando `curl https://setup.ius.io | sh` para atualizar para a fonte mais recente do `git`, e então instalar diretamente o git e o gitlab-runner com o yum.

```
$ curl https://setup.ius.io | sh
$ yum -y install git2u
$ git version
$ yum install gitlab-runner
```

### Registrar o gitlab-runner

Use o comando `gitlab-runner register --clone-url http://your-ip/` para registrar o gitlab-runner no Gitlab; atenção para substituir `your-ip` pelo IP da rede interna do seu Gitlab, assim:

```
$ sudo gitlab-runner register --clone-url http://your-ip/

Please enter the gitlab-ci coordinator URL (e.g. https://gitlab.com/):
http://gitlab.xxx.cc/
Please enter the gitlab-ci token for this runner:
xxxxx
Please enter the gitlab-ci description for this runner:
xxx
Please enter the gitlab-ci tags for this runner (comma separated):
builder
Please enter the executor: docker-ssh, shell, docker+machine, docker-ssh+machine, docker, parallels, ssh, virtualbox, kubernetes:
shell
```

### Modificar o número de execuções concorrentes do gitlab-runner

```
$ vim /etc/gitlab-runner/config.toml
concurrent = 5
```

### Adicionar permissões ao gitlab-runner

- Permissão para executar docker sem sudo

```shell
sudo usermod -aG docker gitlab-runner
```

- Permissão no registro de imagens

```shell
su gitlab-runner
docker login -u username your-docker-repository
```

###

### Modificar o e-mail

Se você precisar que o `Gitlab` envie e-mails (como e-mails de criação de usuário etc.), pode tentar modificar o `/srv/gitlab/config/gitlab.rb`

```
gitlab_rails['smtp_enable'] = true
gitlab_rails['smtp_address'] = "smtp.exmail.qq.com"
gitlab_rails['smtp_port'] = 465
gitlab_rails['smtp_user_name'] = "git@xxxx.com"
gitlab_rails['smtp_password'] = "xxxx"
gitlab_rails['smtp_authentication'] = "login"
gitlab_rails['smtp_enable_starttls_auto'] = true
gitlab_rails['smtp_tls'] = true
gitlab_rails['gitlab_email_from'] = 'git@xxxx.com'
gitlab_rails['smtp_domain'] = "exmail.qq.com"
```

## Inicializar o cluster Swarm

### Faça login em outra máquina e inicialize o cluster

```
$ docker swarm init
```

### Criar uma rede Overlay personalizada

A seguir são oferecidas três formas de criar a sub-rede; basta executar uma delas

1. Criar diretamente uma rede Overlay personalizada

```shell
docker network create \
--driver overlay \
--subnet 10.0.0.1/8 \
--opt encrypted \
--attachable \
default-network
```

2. Às vezes, devido a conflitos de sub-rede, o stack pode falhar ao iniciar; pode-se tentar modificar `--subnet`, porém, dessa forma, a sub-rede atual suporta apenas 65535 IPs

```shell
docker network create \
--driver overlay \
--subnet 10.1.0.1/16 \
--opt encrypted \
--attachable \
default-network
```

3. Claro que, como na maioria das vezes a sub-rede padrão da rede ingress entra em conflito com a sub-rede que criamos, podemos remover a rede ingress e criar uma nova

```shell
docker network rm ingress
docker network create --ingress --subnet 192.168.0.1/16 --driver overlay ingress
```

Depois crie uma `network` com `--subnet` igual a `10.0.0.1/8`

```shell
docker network create \
--driver overlay \
--subnet 10.0.0.1/8 \
--opt encrypted \
--attachable \
default-network
```

### Ingressar no cluster

```
# Exibe o TOKEN do nó manager
$ docker swarm join-token manager
# Adiciona o nó manager ao cluster
$ docker swarm join --token <token> ip:2377

# Exibe o TOKEN do nó worker
$ docker swarm join-token worker
# Adiciona o nó worker ao cluster
$ docker swarm join --token <token> ip:2377
```

### Configurar o gitlab-runner para deploy

> O restante é igual ao builder, mas a tag não pode ser a mesma. O ambiente de produção pode ser definido como tags e o ambiente de teste como test

## Instalar outras aplicações

A seguir, usando `Mysql` como exemplo, usamos diretamente a `network` acima, que permite a chamada mútua por nome dentro dos contêineres.

```
docker run --name mysql -v /srv/mysql:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=xxxx -p 3306:3306 --rm --network default-network -d mysql:5.7
```

## Instalar o Portainer

[Portainer](https://github.com/portainer/portainer)

```
docker service create \
    --name portainer \
    --publish 9000:9000 \
    --replicas=1 \
    --constraint 'node.role == manager' \
    --mount type=volume,src=portainer_data,dst=/data \
    --mount type=bind,src=//var/run/docker.sock,dst=/var/run/docker.sock \
    portainer/portainer
```

### Fazer backup dos dados do Portainer

> portainer_container é o nome do contêiner correspondente; preencha conforme a situação real

```
docker run -it --volumes-from portainer_container -v $(pwd):/backup --name backup --rm nginx tar -cf /backup/data.tar /data/
```

### Restaurar os dados do Portainer

Primeiro use o comando de criação para recriar o serviço portainer

Depois use o método a seguir para recarregar o backup no contêiner

```
docker run -it --volumes-from portainer_container -v $(pwd):/backup --name importer --rm nginx bash
cd /backup
tar xf data.tar -C /
```

Por fim, basta reiniciar o contêiner

## Criar um projeto Demo

Faça login no Gitlab e crie um projeto Demo. E importe nosso projeto [hyperf-skeleton](https://github.com/hyperf/hyperf-skeleton)

## Configurar o registro de imagens

> Basta usarmos diretamente o da Aliyun

Primeiro crie um namespace test_namespace, depois crie um registro de imagens demo e use o registro local.

Depois, na máquina que usamos diretamente para empacotar, faça login no Docker Registry da Aliyun

```
usermod -aG docker gitlab-runner
su gitlab-runner
docker login --username=your_name registry.cn-shanghai.aliyuncs.com
```

Modifique o .gitlab-ci.yml do nosso projeto

```
variables:
  PROJECT_NAME: demo
  REGISTRY_URL: registry.cn-shanghai.aliyuncs.com/test_namespace
```

E também o deploy.test.yml; compare cuidadosamente os arquivos a seguir.

```yml
version: '3.7'
services:
  demo:
    image: $REGISTRY_URL/$PROJECT_NAME:test
    environment:
      - "APP_PROJECT=demo"
      - "APP_ENV=test"
    ports:
      - 9501:9501
    deploy:
      replicas: 1
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 5
      update_config:
        parallelism: 2
        delay: 5s
        order: start-first
    networks:
      - default-network
    configs:
      - source: demo_v1.0
        target: /opt/www/.env
configs:
  demo_v1.0:
    external: true
networks:
  default-network:
    external: true
```

Depois, no nosso Portainer, crie o Config `demo_v1.0` correspondente. Claro que os parâmetros abaixo precisam ser ajustados conforme a situação real, pois no nosso Demo não há nenhuma operação de I/O, então basta preencher com os padrões.

```
APP_NAME=demo

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hyperf
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=

REDIS_HOST=localhost
REDIS_AUTH=
REDIS_PORT=6379
REDIS_DB=0
```

Como o gitlab-ci.yml que configuramos detecta a branch test e as tags, mesclamos o conteúdo modificado na branch test e então enviamos para o gitlab.

Em seguida, podemos acessar a porta 9501 de qualquer máquina do cluster para testar

```
curl http://127.0.0.1:9501/
```

## Instalar o gateway KONG

Normalmente, um cluster Docker Swarm não expõe acesso direto ao exterior, então podemos construir um serviço de gateway na camada superior; aqui recomendamos usar o `KONG` como gateway.
Há também outro motivo: a `Ingress network` do Docker Swarm tem falhas de projeto; quando as conexões não são reutilizadas, há um gargalo de concorrência. Para detalhes específicos, consulte o `Issue` correspondente [#35082](https://github.com/moby/moby/issues/35082)
E o `KONG`, como serviço de gateway, por padrão reutiliza as conexões de backend, o que ameniza bastante o problema acima.

### Instalar o banco de dados

```
docker run -d --name kong-database \
  --network=default-network \
  -p 5432:5432 \
  -e "POSTGRES_USER=kong" \
  -e "POSTGRES_DB=kong" \
  -e "POSTGRES_PASSWORD=kong" \
  postgres:9.6
```

### Instalar o gateway

Inicializar o banco de dados

```
docker run --rm \
  --network=default-network \
  -e "KONG_DATABASE=postgres" \
  -e "KONG_PG_HOST=kong-database" \
  -e "KONG_PG_PASSWORD=kong" \
  -e "KONG_CASSANDRA_CONTACT_POINTS=kong-database" \
  kong:latest kong migrations bootstrap
```

Iniciar

```
docker run -d --name kong \
  --network=default-network \
  -e "KONG_DATABASE=postgres" \
  -e "KONG_PG_HOST=kong-database" \
  -e "KONG_PG_PASSWORD=kong" \
  -e "KONG_CASSANDRA_CONTACT_POINTS=kong-database" \
  -e "KONG_PROXY_ACCESS_LOG=/dev/stdout" \
  -e "KONG_ADMIN_ACCESS_LOG=/dev/stdout" \
  -e "KONG_PROXY_ERROR_LOG=/dev/stderr" \
  -e "KONG_ADMIN_ERROR_LOG=/dev/stderr" \
  -e "KONG_ADMIN_LISTEN=0.0.0.0:8001, 0.0.0.0:8444 ssl" \
  -p 8000:8000 \
  -p 8443:8443 \
  -p 8001:8001 \
  -p 8444:8444 \
  kong:latest
```

### Instalar o KONG Dashboard

> No momento, o `v3.6.0` ainda não foi atualizado no `Docker`, então a versão mais recente do `KONG` pode não funcionar; pode-se usar o KONG versão 0.14.1

```
docker run --rm --network=default-network -p 8080:8080 -d --name kong-dashboard pgbi/kong-dashboard start \
  --kong-url http://kong:8001 \
  --basic-auth user1=password1 user2=password2
```

### Configurar o Service

Em seguida, basta expor o `IP` da máquina que faz o deploy do gateway `KONG` para acesso externo e então configurar o `Service` correspondente.
Se a máquina estiver exposta diretamente ao acesso externo, então é melhor abrir apenas as portas `80` e `443`, e mapear as portas `8000` e `8443` do contêiner `Kong` para as portas `80` e `443`.
Claro que, se usar serviços de balanceamento de carga como o `SLB`, também é possível, através do balanceador, mapear as portas `80` e `443` para as portas `8000` e `8443` da máquina onde está o `KONG`.

## Como usar o Linux Crontab

Embora o `Hyperf` forneça o componente `crontab`, ele pode não atender às necessidades de todos; aqui fornecemos um script para uso no `Linux` para executar o `Command` dentro do `Docker`.

```bash
#!/usr/bin/env bash
basepath=$(cd `dirname $0`; pwd)
docker pull registry-vpc.cn-shanghai.aliyuncs.com/namespace/project:latest
docker run --rm -i -v $basepath/.env:/opt/www/.env \
--entrypoint php registry-vpc.cn-shanghai.aliyuncs.com/namespace/project:latest \
/opt/www/bin/hyperf.php your_command
```

## Otimização do kernel

> O conteúdo desta subseção precisa ser verificado; use com cautela

Ao instalar o gateway `KONG`, mencionamos que a `Ingress network` tem falhas de projeto; isso pode ser tratado com a `otimização do kernel`.

- Especificar a fonte TLinux

```
tee /etc/yum.repos.d/CentOS-TLinux.repo <<-'EOF'
[Tlinux]
name=Tlinux for redhat/centos $releasever - $basearch
failovermethod=priority
gpgcheck=0
gpgkey=http://mirrors.tencentyun.com/epel/RPM-GPG-KEY-EPEL-7
enabled=1
baseurl=https://mirrors.tencent.com/tlinux/2.4/tlinux/x86_64/
EOF
```

- Instalar o kernel especificado

```
yum -y install kernel-devel-4.14.105-19.0012.tl2.x86_64 kernel-4.14.105-19.0013.tl2.x86_64 kernel-headers-4.14.105-19.0013.tl2.x86_64
```

- Tornar o kernel efetivo

```
sudo awk -F\' '$1=="menuentry " {print i++ " : " $2}' /etc/grub2.cfg
grub2-set-default 0
grub2-mkconfig -o /boot/grub2/grub.cfg
```

- Reiniciar a máquina

```
reboot
```

### Otimização de parâmetros do contêiner

> Requer suporte do Docker 19.09.0 ou superior; fica no mesmo nível da configuração image

```yaml
sysctls:
  # Seleção do modo de reutilização de conexões de rede
  - net.ipv4.vs.conn_reuse_mode=0
  # Quando o LVS encaminha um pacote e descobre que o RS de destino é inválido (removido), ele descarta o pacote, mas não remove a conexão correspondente. Quando o valor é 1, a conexão correspondente é liberada imediatamente
  - net.ipv4.vs.expire_nodest_conn=1
```

## Perguntas frequentes

### fatal: git fetch-pack: expected shallow list

Essa situação ocorre porque a versão do `git` usada pelo `gitlab-runner` é muito antiga; basta atualizar a versão do `git`, assim:

```
$ curl https://setup.ius.io | sh
$ yum remove -y git
$ yum -y install git2u
$ git version

# Reinstala o gitlab-runner e registra novamente o gitlab-runner
$ yum install gitlab-runner
```

### Após reiniciar o Service, ocorrem problemas ocasionais na rede interna em que os contêineres não conseguem alcançá-lo, por exemplo, ao acessar repetidamente a interface deste serviço em outros contêineres, aparece Connection refused

Isso ocorre por falta de IPs; pode-se modificar a sub-rede para aumentar os IPs disponíveis

Criar uma nova Network

```
docker network create \
--driver overlay \
--subnet 10.0.0.0/8 \
--opt encrypted \
--attachable \
default-network
```

Adicionar uma nova Network ao serviço

```
docker service update --network-add default-network service_name
```

Remover a Network antiga

```
docker service update --network-rm old-network service_name
```

### Ao adicionar nós a um Service, ele fica travado na fase create

O motivo e a solução são os mesmos acima

### Após modificar a senha do registro no Portainer, a atualização do Service falha

Isso ocorre porque, após a modificação no Portainer, ela não se aplica aos serviços já criados, então basta atualizar manualmente

```
docker service update --with-registry-auth service_name
```


## Apêndice

### Instalar apenas o Docker Swarm

Se você precisar apenas instalar e usar o Docker Swarm, pode seguir a documentação abaixo.

Suponha que temos três máquinas A, B e C; por padrão, usamos A como Leader

#### Instalar o Docker

Instale o Docker nas três máquinas da seguinte forma

```
curl -sSL https://get.docker.com/ | sh
```

Modifique o arquivo `/lib/systemd/system/docker.service`, permitindo usar `TCP` para conectar ao `Docker`

> Basta acrescentar o `-H tcp://0.0.0.0:2375` ao final

```
ExecStart=/usr/bin/dockerd -H fd:// --containerd=/run/containerd/containerd.sock -H tcp://0.0.0.0:2375
```

Se não estiver usando a conta `root`, você pode usar o comando a seguir para que não seja necessário adicionar `sudo` a cada execução de `docker`

```
usermod -aG docker $USER
```

#### Inicializar o Docker Swarm

Acesse a máquina A e execute o comando de inicialização

```
$ docker swarm init
```

Como na maioria das vezes a sub-rede padrão da rede ingress entra em conflito com a sub-rede que criamos, removemos a rede ingress e criamos uma nova

```shell
docker network rm ingress
docker network create --ingress --subnet 192.168.0.1/16 --driver overlay ingress
```

Depois crie uma `network` com `--subnet` igual a `10.0.0.1/8`

```shell
docker network create \
--driver overlay \
--subnet 10.0.0.1/8 \
--opt encrypted \
--attachable \
default-network
```

Execute o comando que exibe a entrada no cluster

> Como temos apenas três máquinas, é melhor declarar todas como manager

```
$ docker swarm join-token manager
```

Se posteriormente precisar adicionar novos nós worker, execute o comando a seguir para obter o script correspondente

```
$ docker swarm join-token worker
```

#### Adicionar os outros dois nós ao cluster

Vá até as máquinas B e C e execute o comando gerado

```shell
docker swarm join --token xxxx <ip>:2377
```

Volte à máquina A e execute o comando para verificar se a entrada foi bem-sucedida

```shell
docker node ls
```

Se você puder ver os nós B e C, isso indica que a entrada foi bem-sucedida

#### Usar o serviço de imagens do serviço de nuvem

Não vamos detalhar aqui como usar; por favor, faça as operações no serviço de nuvem correspondente por conta própria

Esta documentação assume que o desenvolvedor já ativou com sucesso o serviço de imagens correspondente; a documentação seguinte usa por padrão o nó de Xangai da Aliyun para explicar

[Aliyun](https://cr.console.aliyun.com/cn-shanghai/instances)

#### Fazer login na imagem

Execute a operação de login nas três máquinas A, B e C

```shell
docker login --username=xxxx registry.cn-shanghai.aliyuncs.com
```

#### Empacotar a imagem

Aqui você pode fazer o empacotamento em qualquer máquina, ou no ambiente de desenvolvimento (fora do ambiente das três máquinas acima, é necessário executar docker login para fazer login)

```shell
docker build . -t registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
docker push registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
```

#### Criar o arquivo stack yml

Volte à máquina A, vá até o diretório /opt/www/your_project e edite o arquivo deploy.yml

```shell
version: '3.7'
services:
  your_project:
    image: registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
    ports:
      - "9501:9501"
    deploy:
      replicas: 3
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 5
      update_config:
        parallelism: 2
        delay: 5s
        order: start-first
    networks:
      - default-network
    configs:
      - source: your_project_v1.1
        target: /opt/www/.env
configs:
  your_project_v1.1:
    file: /opt/www/your_project/.env
networks:
  default-network:
    external: true
```

Edite o arquivo .env e conclua a configuração; atenção, não use 127.0.0.1 para conectar a serviços como MySQL

#### Iniciar o serviço

```shell
docker pull registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
docker stack deploy -c /opt/www/your_project/deploy.yml --with-registry-auth your_project
```

Verifique se iniciou normalmente; ao executar os três comandos abaixo, todos devem ter dados correspondentes

```shell
docker stack ls
docker service ls
docker ps
```

#### Testar se o serviço está disponível

Vá até as três máquinas e faça o teste com curl em todas; se todas retornarem os dados correspondentes, isso indica que o serviço foi iniciado com sucesso

```shell
curl http://127.0.0.1:9501/
```

#### Atualizar o serviço

Empacote na máquina de desenvolvimento e envie para o registro de imagens

```shell
docker build . -t registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
docker push registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
```

Vá até a máquina A e reinicie

```shell
docker pull registry.cn-shanghai.aliyuncs.com/your_namespace/your_project:latest
docker stack deploy -c /opt/www/your_project/deploy.yml --with-registry-auth your_project
```
