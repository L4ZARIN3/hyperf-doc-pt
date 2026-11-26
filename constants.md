# Classes de enumeração

Quando você precisa definir códigos de erro e mensagens de erro, talvez use a seguinte abordagem,

```php
<?php

class ErrorCode
{
    const SERVER_ERROR = 500;
    const PARAMS_INVALID = 1000;

    public static $messages = [
        self::SERVER_ERROR => 'Server Error',
        self::PARAMS_INVALID => 'Parâmetro inválido'
    ];
}

$message = ErrorCode::messages[ErrorCode::SERVER_ERROR] ?? 'Erro desconhecido';

```

Mas essa forma de implementação não é amigável: toda vez que se deseja consultar um código de erro e a mensagem correspondente, é preciso pesquisar duas vezes na `Class` atual. Por isso, o framework fornece classes de enumeração baseadas em anotações.

## Instalação

```
composer require hyperf/constants
```

## Uso

### Definir uma classe de enumeração

O comando `gen:constant` permite gerar rapidamente uma classe de enumeração.

```bash
php bin/hyperf.php gen:constant ErrorCode --type enum
```



```php
<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ErrorCode: int
{
    use EnumConstantsTrait
    #[Message("Server Error!")]
    case SERVER_ERROR = 500;

    #[Message("Erro de parâmetro do sistema")]
    case SYSTEM_INVALID = 700;
}
```

O usuário pode usar `ErrorCode::SERVER_ERROR->getMessage()` para obter a mensagem de erro correspondente.

### Definir uma classe de exceção

Se usarmos apenas a `classe de enumeração`, o tratamento de exceções ainda não será suficientemente prático. Por isso, precisamos definir nossa própria classe de exceção `BusinessException`; quando uma exceção ocorrer, ela consultará ativamente a mensagem de erro correspondente com base no código de erro.

```php
<?php

declare(strict_types=1);

namespace App\Exception;

use App\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

class BusinessException extends ServerException
{
    public function __construct(ErrorCode|int $code = 0, ?string $message = null, ?Throwable $previous = null)
    {
        if (is_null($message)) {
            if ($code instanceof ErrorCode) {
                $message = $code->getMessage();
            } else {
                $message = ErrorCode::getMessage($code);
            }
        }

        $code = $code instanceof ErrorCode ? $code->value : $code;

        parent::__construct($message, $code, $previous);
    }
}
```

### Lançar exceção

Concluídas as duas etapas acima, você já pode lançar a exceção correspondente na lógica de negócios.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constants\ErrorCode;
use App\Exception\BusinessException;

class IndexController extends AbstractController
{
    public function index()
    {
        throw new BusinessException(ErrorCode::SERVER_ERROR);
    }
}
```

### Parâmetros variáveis

Ao usar `ErrorCode::SERVER_ERROR->getMessage()` para obter a mensagem de erro correspondente, também podemos passar parâmetros variáveis para compor a mensagem de erro. Por exemplo, no caso abaixo

```php
<?php


use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ErrorCode: int
{
    use EnumConstantsTrait;
    
    #[Message("Params %s is invalid.")]
    case PARAMS_INVALID = 1000;
}

$message = ErrorCode::PARAMS_INVALID->getMessage(['user_id']);
```

### Internacionalização

Para que o componente [hyperf/constants](https://github.com/hyperf/constants) suporte internacionalização, é necessário instalar o componente [hyperf/translation](https://github.com/hyperf/translation) e configurar os arquivos de idioma, da seguinte forma:

```
composer require hyperf/translation
```

A configuração relacionada está detalhada em [Internacionalização](translation.md)

```php
<?php

// Configuração de internacionalização

return [
    'params.invalid' => 'Params :param is invalid.',
];

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ErrorCode: int
{

    #[Message("params.invalid")]
    case PARAMS_INVALID = 1000;
}

$message = ErrorCode::SERVER_ERROR->getMessage(['param' => 'user_id']);
```
