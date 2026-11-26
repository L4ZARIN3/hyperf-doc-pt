# Sistema de arquivos

O componente de sistema de arquivos integra o famoso `League\Flysystem` do ecossistema PHP (que também é a biblioteca subjacente de muitos frameworks conhecidos, como o Laravel). Por meio de uma abstração razoável, o programa não precisa saber se o mecanismo de armazenamento é um disco local ou um servidor em nuvem, alcançando o desacoplamento. Este componente oferece suporte a corrotinas para serviços comuns de armazenamento em nuvem.

## Instalação

```shell
composer require hyperf/filesystem
```

O componente `League\Flysystem` teve grandes mudanças nas versões `v1.0`, `v2.0` e `v3.0`, portanto é necessário instalar o adaptador correspondente de acordo com a versão

- Adaptador Alibaba Cloud OSS

Versão `Flysystem v1.0`

```shell
composer require xxtime/flysystem-aliyun-oss
```

Versões `Flysystem v2.0` e `Flysystem v3.0`

```shell
composer require hyperf/flysystem-oss
```

- Adaptador S3

Versão `Flysystem v1.0`

```shell
composer require "league/flysystem-aws-s3-v3:^1.0"
composer require hyperf/guzzle
```

Versão `Flysystem v2.0`

```shell
composer require "league/flysystem-aws-s3-v3:^2.0"
composer require hyperf/guzzle
```

- Adaptador Qiniu

Versão `Flysystem v1.0`

```shell
composer require "overtrue/flysystem-qiniu:^1.0"
```

Versão `Flysystem v2.0`

```shell
composer require "overtrue/flysystem-qiniu:^2.0"
```

Versão `Flysystem v3.0`

```shell
composer require "overtrue/flysystem-qiniu:^3.0"
```

- Adaptador de memória

Versão `Flysystem v1.0`

```shell
composer require "league/flysystem-memory:^1.0"
```

Versão `Flysystem v2.0`

```shell
composer require "league/flysystem-memory:^2.0"
```

- Adaptador Tencent Cloud COS

Versão `Flysystem v1.0`

> A versão v2.0 do flysystem-cos não é mais recomendada; siga a documentação mais recente e altere para a versão 3.0

```shell
composer require "overtrue/flysystem-cos:^3.0"
```

Versão `Flysystem v2.0`

```shell
composer require "overtrue/flysystem-cos:^4.0"
```

Versão `Flysystem v3.0`

```shell
composer require "overtrue/flysystem-cos:^5.0"
```

Após a conclusão da instalação, execute

```bash
php bin/hyperf.php vendor:publish hyperf/filesystem
```

e será gerado o arquivo `config/autoload/file.php`. Nesse arquivo, defina o driver padrão e configure o access key, access secret e outras informações do driver correspondente para poder usá-lo.

## Uso

Basta injetar `League\Flysystem\Filesystem` via DI para usá-lo.

A API é a seguinte:

> Os exemplos a seguir são da versão Flysystem v1.0; para a versão v2.0, consulte a documentação oficial

```php
<?php

declare(strict_types=1);

namespace App\Controller;

class IndexController extends AbstractController
{
    public function example(\League\Flysystem\Filesystem $filesystem)
    {
        // Processa o upload
        $file = $this->request->file('upload');
        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream(
            'uploads/'.$file->getClientFilename(),
            $stream
        );
        fclose($stream);
        
        // Escreve arquivos
        $filesystem->write('path/to/file.txt', 'contents');

        // Adiciona arquivo local
        $stream = fopen('local/path/to/file.txt', 'r+');
        $result = $filesystem->writeStream('path/to/file.txt', $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Atualiza arquivos
        $filesystem->update('path/to/file.txt', 'new contents');

        // Verifica se um arquivo existe
        $exists = $filesystem->has('path/to/file.txt');

        // Lê arquivos
        $contents = $filesystem->read('path/to/file.txt');

        // Exclui arquivos
        $filesystem->delete('path/to/file.txt');

        // Renomeia arquivos
        $filesystem->rename('filename.txt', 'newname.txt');

        // Copia arquivos
        $filesystem->copy('filename.txt', 'duplicate.txt');

        // lista o conteúdo
        $filesystem->listContents('path', false);
    }
}
```

Em alguns momentos, você precisará usar vários meios de armazenamento ao mesmo tempo. Nesse caso, você pode injetar `Hyperf\Filesystem\FilesystemFactory` para escolher dinamicamente qual driver usar.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

class IndexController
{
    public function example(\Hyperf\Filesystem\FilesystemFactory $factory)
    {
    	$local = $factory->get('local');
        // Escreve arquivos
        $local->write('path/to/file.txt', 'contents');

        $s3 = $factory->get('s3');

        $s3->write('path/to/file.txt', 'contents');
    }
}
```

### Configurar recursos estáticos

Se você deseja acessar via http os arquivos enviados para o local, adicione a seguinte configuração no arquivo `config/autoload/server.php`.

```php
return [
    'settings' => [
        ...
        // Substitua public pelo diretório de upload
        'document_root' => BASE_PATH . '/public',
        'enable_static_handler' => true,
    ],
];

```

## Observações

1. Para o armazenamento S3, confirme a instalação do componente `hyperf/guzzle` para fornecer suporte a corrotinas. Para os armazenamentos em nuvem da Alibaba Cloud, Qiniu Cloud e Tencent Cloud, [habilite o Curl Hook](coroutine.md#nível-de-runtime-hook-do-swoole) para usar corrotinas. Devido a problemas de suporte de parâmetros do Curl Hook, use a versão Swoole 4.4.13 ou superior.
2. Soluções de armazenamento de objetos privados como minIO, ceph radosgw etc. suportam o protocolo S3 e podem usar o adaptador S3.
3. Ao usar o driver Local, o diretório raiz é o endereço configurado, e não o diretório raiz do sistema operacional. Por exemplo, se o `root` do driver Local estiver definido como `/var/www`, então `/var/www/public/file.txt` no disco local, ao ser acessado pela API do flysystem, deve usar `/public/file.txt` ou `public/file.txt`.
4. Tomando o OSS da Alibaba Cloud como exemplo, comparação de desempenho de leitura com 1 núcleo e 1 processo:

```bash
ab -k -c 10 -n 1000 http://127.0.0.1:9501/
```

CURL HOOK não habilitado:

```
Concurrency Level:      10
Time taken for tests:   202.902 seconds
Complete requests:      1000
Failed requests:        0
Keep-Alive requests:    1000
Total transferred:      146000 bytes
HTML transferred:       5000 bytes
Requests per second:    4.93 [#/sec] (mean)
Time per request:       2029.016 [ms] (mean)
Time per request:       202.902 [ms] (mean, across all concurrent requests)
Transfer rate:          0.70 [Kbytes/sec] received
```

Após habilitar o CURL HOOK:

```
Concurrency Level:      10
Time taken for tests:   9.252 seconds
Complete requests:      1000
Failed requests:        0
Keep-Alive requests:    1000
Total transferred:      146000 bytes
HTML transferred:       5000 bytes
Requests per second:    108.09 [#/sec] (mean)
Time per request:       92.515 [ms] (mean)
Time per request:       9.252 [ms] (mean, across all concurrent requests)
Transfer rate:          15.41 [Kbytes/sec] received
```

## Configuração detalhada

```php
return [
    // Basta selecionar a chave do driver correspondente em storage.
    'default' => 'local',
    'storage' => [
        'local' => [
            'driver' => \Hyperf\Filesystem\Adapter\LocalAdapterFactory::class,
            'root' => __DIR__ . '/../../runtime',
        ],
        'ftp' => [
            'driver' => \Hyperf\Filesystem\Adapter\FtpAdapterFactory::class,
            'host' => 'ftp.example.com',
            'username' => 'username',
            'password' => 'password',

            /* configurações opcionais */
            'port' => 21,
            'root' => '/path/to/root',
            'passive' => true,
            'ssl' => true,
            'timeout' => 30,
            'ignorePassiveAddress' => false,
        ],
        'memory' => [
            'driver' => \Hyperf\Filesystem\Adapter\MemoryAdapterFactory::class,
        ],
        's3' => [
            'driver' => \Hyperf\Filesystem\Adapter\S3AdapterFactory::class,
            'credentials' => [
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
            ],
            'region' => env('S3_REGION'),
            'version' => 'latest',
            'bucket_endpoint' => false,
            'use_path_style_endpoint' => false,
            'endpoint' => env('S3_ENDPOINT'),
            'bucket_name' => env('S3_BUCKET'),
        ],
        'minio' => [
            'driver' => \Hyperf\Filesystem\Adapter\S3AdapterFactory::class,
            'credentials' => [
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
            ],
            'region' => env('S3_REGION'),
            'version' => 'latest',
            'bucket_endpoint' => false,
            'use_path_style_endpoint' => true,
            'endpoint' => env('S3_ENDPOINT'),
            'bucket_name' => env('S3_BUCKET'),
        ],
        'oss' => [
            'driver' => \Hyperf\Filesystem\Adapter\AliyunOssAdapterFactory::class,
            'accessId' => env('OSS_ACCESS_ID'),
            'accessSecret' => env('OSS_ACCESS_SECRET'),
            'bucket' => env('OSS_BUCKET'),
            'endpoint' => env('OSS_ENDPOINT'),
            // 'timeout'        => 3600,
            // 'connectTimeout' => 10,
            // 'isCName'        => false,
            // 'token'          => '',
        ],
        'qiniu' => [
            'driver' => \Hyperf\Filesystem\Adapter\QiniuAdapterFactory::class,
            'accessKey' => env('QINIU_ACCESS_KEY'),
            'secretKey' => env('QINIU_SECRET_KEY'),
            'bucket' => env('QINIU_BUCKET'),
            'domain' => env('QINIU_DOMAIN'),
        ],
        'cos' => [
            'driver' => \Hyperf\Filesystem\Adapter\CosAdapterFactory::class,
            'region' => env('COS_REGION'),
            // Configuração do overtrue/flysystem-cos ^2.0 conforme abaixo
            'credentials' => [
                'appId' => env('COS_APPID'),
                'secretId' => env('COS_SECRET_ID'),
                'secretKey' => env('COS_SECRET_KEY'),
            ],
            // Configuração do overtrue/flysystem-cos ^3.0 conforme abaixo
            'app_id' => env('COS_APPID'),
            'secret_id' => env('COS_SECRET_ID'),
            'secret_key' => env('COS_SECRET_KEY'),
            // Opcional; se o bucket for de acesso privado, habilite este item
            // 'signed_url' => false,
            'bucket' => env('COS_BUCKET'),
            'read_from_cdn' => false,
            // 'timeout'         => 60,
            // 'connect_timeout' => 60,
            // 'cdn'             => '',
            // 'scheme'          => 'https',
        ],
    ],
];
```
