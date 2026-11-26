# Serviço de log Alibaba Cloud

Ao implantar o projeto em um `Cluster Docker`, a coleta de logs costuma ser um problema bastante incômodo, mas a Alibaba Cloud oferece um `sistema de coleta de logs` muito prático. Este documento apresenta brevemente como usar a coleta de logs da Alibaba Cloud.

* [Configuração de cluster Docker Swarm](tutorial/docker-swarm.md)

## Ativando o serviço de log

O primeiro passo é ativar o `serviço de log` na Alibaba Cloud.

[Documentação do serviço de log](https://help.aliyun.com/product/28958.html)

O tutorial abaixo segue uma ordem sequencial de operações, explicando passo a passo como usar o serviço de log.

## Instalando o contêiner Logtail

[Documentação do fluxo padrão de coleta de logs do Docker](https://help.aliyun.com/document_detail/66659.html)

|                 Parâmetro                  |                     Descrição                     |
| :-----------------------------------: | :-----------------------------------------: |
|          ${your_region_name}          |    ID da região, por exemplo, a região Leste da China 1 é cn-hangzhou    |
|        ${your_aliyun_user_id}         | Identificador do usuário; substitua pelo ID da sua conta principal da Alibaba Cloud. |
| ${your_machine_group_user_defined_id} |   Identificador personalizado do grupo de máquinas do cluster; abaixo usamos Hyperf    |

```
docker run -d -v /:/logtail_host:ro -v /var/run/docker.sock:/var/run/docker.sock \
--env ALIYUN_LOGTAIL_CONFIG=/etc/ilogtail/conf/${your_region_name}/ilogtail_config.json \
--env ALIYUN_LOGTAIL_USER_ID=${your_aliyun_user_id} \
--env ALIYUN_LOGTAIL_USER_DEFINED_ID=${your_machine_group_user_defined_id} \
registry.cn-hangzhou.aliyuncs.com/log-service/logtail
```

## Configurando a coleta de logs

### Criando um Project

Faça login no serviço de log da Alibaba Cloud, clique em `Criar Project` e preencha as seguintes informações

|     Parâmetro     |     Exemplo de preenchimento     |
| :----------: | :--------------: |
| Nome do Project |      hyperf      |
|     Comentário     | Usado para demonstração do sistema de log |
|   Região   |  Leste da China 1 (Hangzhou)  |
|   Ativar serviço   |     Log detalhado     |
| Local de armazenamento do log |   Project atual   |

### Criando um Logstore

Exceto pelos parâmetros abaixo, preencha conforme a necessidade; todos os outros podem usar o padrão

|      Parâmetro      |     Exemplo de preenchimento     |
| :-----------: | :-------------: |
| Nome do Logstore | hyperf-demo-api |
|   Salvamento permanente    |      false      |
| Tempo de retenção dos dados  |       60        |

### Integrando dados

1. Selecione o arquivo Docker

2. Crie um grupo de máquinas

Se já tiver criado um grupo de máquinas, pode pular esta etapa

|      Parâmetro      |    Exemplo de preenchimento    |
| :------------: | :------------: |
|   Nome do grupo de máquinas   |     Hyperf     |
|   Identificador do grupo de máquinas   | Identificador personalizado do usuário |
| Identificador personalizado do usuário |     Hyperf     |

3. Configure o grupo de máquinas

Aplique o grupo de máquinas recém-criado

4. Configure o Logtail

Lista de permissões de `Label`; aqui você pode preencher conforme a necessidade. Abaixo configuramos de acordo com o nome do projeto, e o nome do projeto será definido ao executar o contêiner Docker.

|      Parâmetro      |                       Exemplo de preenchimento                       |    Exemplo de preenchimento     |
| :------------: | :-------------------------------------------------: | :-------------: |
|    Nome da configuração    |                   hyperf-demo-api                   |                 |
|    Caminho do log    |                /opt/www/runtime/logs                |      *.log      |
|  Lista de permissões de Label  |                      app.name                       | hyperf-demo-api |
|      Modo      |                    Modo de regex completo                     |                 |
|    Modo de linha única    |                        false                        |                 |
|    Exemplo de log    |     `[2019-03-07 11:58:57] hyperf.WARNING: xxx`     |                 |
| Expressão regular da primeira linha |         `\[\d+-\d+-\d+\s\d+:\d+:\d+\]\s.*`          |                 |
|    Extrair campos    |                        true                         |                 |
|   Expressão regular   | `\[(\d+-\d+-\d+\s\d+:\d+:\d+)\]\s(\w+)\.(\w+):(.*)` |                 |
|  Conteúdo extraído do log  |               time name level content               |                 |

5. Configuração de consulta e análise

Propriedades do índice de campos

| Nome do campo | Tipo  |  Alias   | Segmentação de chinês | Ativar estatísticas |
| :------: | :---: | :-----: | :------: | :------: |
|   name   | text  |  name   |  false   |   true   |
|  level   | text  |  level  |  false   |   true   |
|   time   | text  |  time   |  false   |  false   |
| content  | text  | content |   true   |  false   |

### Executando a imagem

Ao executar a imagem, basta definir os `labels` do Container.

|   name   |      value      |
| :------: | :-------------: |
| app.name | hyperf-demo-api |

Por exemplo, o seguinte Dockerfile

```Dockerfile
# Default Dockerfile

FROM hyperf/hyperf:7.4-alpine-v3.11-swoole
LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT" app.name="hyperf-demo-api"

# Outros conteúdos omitidos
```

## Observações

- Limitação de driver de armazenamento do Docker: atualmente só há suporte a `overlay` e `overlay2`. Para outros drivers de armazenamento, é necessário `mount` o diretório onde os logs estão localizados para o host local e depois alterar a coleta para os logs em `~/logtail_host/your_path` da máquina host.

