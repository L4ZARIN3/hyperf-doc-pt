# Deploy com Supervisor

O [Supervisor](http://www.supervisord.org/) é uma ferramenta de gerenciamento de processos para sistemas `Linux/Unix`. Ele permite monitorar, iniciar, parar e reiniciar um ou vários processos com facilidade. Quando um processo gerenciado pelo [Supervisor](http://www.supervisord.org/) é encerrado acidentalmente por um `Kill`, o [Supervisor](http://www.supervisord.org/) o reinicia automaticamente, tornando muito simples a recuperação automática de processos, sem a necessidade de escrever scripts `shell` para gerenciá-los.

## Instalar o Supervisor

Aqui exemplificamos apenas a instalação no sistema `CentOS`:

```bash
# Instale o repositório epel; se já o instalou antes, pule esta etapa
yum install -y epel-release
yum install -y supervisor  
```

## Criar um arquivo de configuração

```bash
cp /etc/supervisord.conf /etc/supervisord.d/supervisord.conf
```

Edite o arquivo de configuração recém-copiado `/etc/supervisord.d/supervisord.conf`, adicione o conteúdo a seguir ao final do arquivo e salve-o:

```ini
# Crie uma nova aplicação e defina um nome; aqui definimos como hyperf
[program:hyperf]
# Define o diretório no qual o comando será executado
directory=/var/www/hyperf/
# Aqui vai o comando de inicialização do projeto que você deseja gerenciar
command=php ./bin/hyperf.php start
# Com qual usuário o processo será executado
user=root
# Inicia automaticamente a aplicação quando o supervisor iniciar
autostart=true
# Reinicia automaticamente o processo após ele sair
autorestart=true
# Quanto tempo o processo precisa permanecer em execução para ser considerado iniciado com sucesso
startsecs=1
# Número de tentativas
startretries=3
# Local de saída do log stderr
stderr_logfile=/var/www/hyperf/runtime/stderr.log
# Local de saída do log stdout
stdout_logfile=/var/www/hyperf/runtime/stdout.log
```

!> Recomenda-se aumentar também o item de configuração `minfds` do arquivo de configuração, cujo padrão é `1024`. Também se deve ajustar o [ulimit](https://wiki.swoole.com/#/other/sysctl?id=ulimit-%e8%ae%be%e7%bd%ae) do sistema, para evitar o problema `Failed to open stream: Too many open files`.

## Iniciar o Supervisor

Execute o comando abaixo para iniciar o programa Supervisor com base no arquivo de configuração:

```bash
supervisord -c /etc/supervisord.d/supervisord.conf
```

## Gerenciar o projeto com o supervisorctl

```bash
# Iniciar a aplicação hyperf
supervisorctl start hyperf
# Reiniciar a aplicação hyperf
supervisorctl restart hyperf
# Parar a aplicação hyperf
supervisorctl stop hyperf  
# Ver o status de execução de todos os projetos gerenciados
supervisorctl status
# Recarregar o arquivo de configuração
supervisorctl update
# Reiniciar todos os programas
supervisorctl reload
```
