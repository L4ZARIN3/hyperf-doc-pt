# Empacotador Phar

## Instalação

```bash
composer require hyperf/phar
```

## Uso

- Empacotamento padrão

```shell
php bin/hyperf.php phar:build
```

- Especificar o nome do pacote

```shell
php bin/hyperf.php phar:build --name=your_project.phar
```

- Especificar a versão do pacote

```shell
php bin/hyperf.php phar:build --phar-version=1.0.1
```

- Especificar o arquivo de inicialização

```shell
php bin/hyperf.php phar:build --bin=bin/hyperf.php
```

- Especificar o diretório de empacotamento

```shell
php bin/hyperf.php phar:build --path=BASE_PATH
```

- Mapear arquivos externos

> Requer hyperf/phar versão >= v2.1.7

O comando abaixo permite que o pacote `phar` leia o arquivo `.env` do mesmo diretório, facilitando a distribuição do `phar` para diversos ambientes

```shell
php bin/hyperf.php phar:build -M .env
```

## Execução

```shell
php your_project.phar start
```

## Observações

Após o empacotamento, ele roda na forma de pacote `phar`. Diferentemente da execução no modo código-fonte, o diretório `runtime` dentro do pacote `phar` não é gravável,
por isso precisamos redefinir a localização de alguns diretórios graváveis.

> Modifique conforme a situação real

- pid_file

Modifique a configuração `server.php`.

```php
<?php

return [
    'settings' => [
        'pid_file' => '/tmp/runtime/hyperf.pid',
    ],
];
```

- logger

Modifique a configuração `logger.php`

```php
<?php
return [
    'default' => [
        'handler' => [
            'class' => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => '/tmp/runtime/logs/hyperf.log',
                'level' => Monolog\Logger::INFO,
            ],
        ],
    ],
];
```

- scan_cacheable

O empacotador Phar define proativamente `scan_cacheable` na configuração `config.php` como `true`.

Naturalmente, também é possível modificar proativamente essa configuração para `true`.
