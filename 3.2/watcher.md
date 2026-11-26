# Watcher de hot reload

> Na primeira inicialização, como não há nenhum cache, ela será relativamente lenta; na segunda inicialização, a coleta será feita dinamicamente de acordo com o horário de modificação dos arquivos, então o tempo de inicialização ainda será relativamente longo.

Além de resolver o problema de inicialização mencionado acima, o componente `Watcher` também fornece a funcionalidade de reiniciar imediatamente após a modificação de um arquivo.

## Instalação

```bash
composer require hyperf/watcher --dev
```

## Configuração

### Publicar configuração

```bash
php bin/hyperf.php vendor:publish hyperf/watcher
```

### Descrição da configuração

|      Configuração      |      Valor padrão      |                           Observação                            |
| :------------: | :--------------: | :-------------------------------------------------------: |
|     driver     | `ScanFileDriver` |                   Driver padrão de varredura agendada de arquivos                    |
|      bin       |   `PHP_BINARY`   | Script usado para iniciar o serviço, por exemplo `php -d swoole.use_shortname=Off` |
|   watch.dir    | `app`, `config`  |                         Diretórios escutados                          |
|   watch.file   |      `.env`      |                         Arquivos escutados                          |
| watch.interval |      `2000`      |                      Intervalo de varredura (milissegundos)                       |
|      ext       |  `.php`, `.env`  |                   Extensões de arquivo nos diretórios escutados                   |

## Drivers suportados

|                 Driver                  |                Observação                 |
| :-----------------------------------: | :---------------------------------: |
| Hyperf\Watcher\Driver\ScanFileDriver  |              Não requer extensão               |
|  Hyperf\Watcher\Driver\FswatchDriver  |          Requer a instalação do fswatch           |
|   Hyperf\Watcher\Driver\FindDriver    | Requer a instalação do find; no MAC é necessário instalar o gfind |
| Hyperf\Watcher\Driver\FindNewerDriver |            Requer a instalação do find            |

### Instalação do `fswatch`

Mac

```bash
brew install fswatch
```

Ubuntu/Debian

```bash
apt-get install fswatch
```

Outros

```bash
wget https://github.com/emcrisostomo/fswatch/releases/download/1.14.0/fswatch-1.14.0.tar.gz \
&& tar -xf fswatch-1.14.0.tar.gz \
&& cd fswatch-1.14.0/ \
&& ./configure \
&& make \
&& make install
```

## Inicialização

Por causa dos diretórios, é necessário executar na raiz do projeto.

```bash
php bin/hyperf.php server:watch
```

## Limitações

- Por enquanto, no ambiente Alpine Docker há um pequeno problema, que será aprimorado posteriormente.
- Excluir arquivos e modificar o `.env` exigem reinicialização manual para ter efeito.
