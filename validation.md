# Validador

## Prefácio

> O [hyperf/validation](https://github.com/hyperf/validation) deriva do [illuminate/validation](https://github.com/illuminate/validation); fizemos algumas modificações nele, mas mantivemos as mesmas regras de validação. Agradecemos à equipe de desenvolvimento do Laravel por implementar um componente de validação tão poderoso e útil.

## Instalação

### Importar o pacote do componente

```bash
composer require hyperf/validation
```

### Adicionar middleware

Você precisa adicionar, no arquivo de configuração `config/autoload/middlewares.php`, a configuração de um middleware global `Hyperf\Validation\Middleware\ValidationMiddleware` para o Server que utiliza o componente de validação. Abaixo está um exemplo de como adicionar o middleware global correspondente ao Server `http`:

```php
<?php
return [
    // A string http abaixo corresponde ao valor da propriedade name de cada server em config/autoload/server.php, ou seja, a configuração de middleware correspondente se aplica apenas a esse Server
    'http' => [
        // Configure seus middlewares globais dentro do array, na ordem do próprio array
        \Hyperf\Validation\Middleware\ValidationMiddleware::class
        // Outros middlewares foram omitidos aqui
    ],
];
```

> Se o middleware global não for configurado corretamente, o uso do `FormRequest` (requisição de formulário) pode não funcionar.

### Adicionar handler de exceções

O handler de exceções trata principalmente a exceção `Hyperf\Validation\ValidationException`; fornecemos um `Hyperf\Validation\ValidationExceptionHandler` para tratá-la. Você precisa adicionar manualmente esse handler de exceções ao arquivo `config/autoload/exceptions.php` do seu projeto; naturalmente, você também pode personalizar seu próprio handler de exceções.

```php
<?php
return [
    'handler' => [
        // Aqui corresponde ao nome do seu Server atual
        'http' => [
            \Hyperf\Validation\ValidationExceptionHandler::class,
        ],
    ],
];
```

### Publicar os arquivos de idioma do validador

Como existe funcionalidade multilíngue, este componente depende do componente [hyperf/translation](https://github.com/hyperf/translation). Caso você ainda não tenha adicionado o arquivo de configuração do componente Translation, execute primeiro o comando abaixo para publicar o arquivo de configuração do componente Translation; se já publicou ou adicionou manualmente, basta publicar os arquivos de idioma do componente de validação:

Publicar os arquivos do componente Translation:

```bash
php bin/hyperf.php vendor:publish hyperf/translation
```

Publicar os arquivos do componente de validação:

```bash
php bin/hyperf.php vendor:publish hyperf/validation
```

A execução dos comandos acima publicará o arquivo de idioma `validation.php` do validador no diretório de idiomas correspondente; `en` refere-se ao arquivo de idioma inglês e `zh_CN` ao arquivo de chinês simplificado. Você pode modificar e personalizar o conteúdo do arquivo `validation.php` conforme suas necessidades reais.

```shell
/storage
    /languages
        /en
            validation.php
        /zh_CN
            validation.php

```

## Uso

### Validação por requisição de formulário

Para cenários de validação complexos, você pode criar um `FormRequest` (requisição de formulário); a requisição de formulário é uma classe de requisição personalizada que contém a lógica de validação. Você pode executar o comando abaixo para criar uma classe de validação de formulário chamada `FooRequest`:

```bash
php bin/hyperf.php gen:request FooRequest
```

A classe de validação de formulário será gerada no diretório `app\Request`; se esse diretório não existir, ele será criado automaticamente ao executar o comando.   
Em seguida, adicionamos algumas regras de validação ao método `rules` dessa classe:

```php
/**
 * Obtém as regras de validação aplicadas à requisição
 */
public function rules(): array
{
    return [
        'foo' => 'required|max:255',
        'bar' => 'required',
    ];
}
```

Então, como as regras de validação passam a valer? Tudo o que você precisa fazer é declarar essa classe de requisição como parâmetro no método do controlador por meio de type hint. Assim, a requisição de formulário recebida será validada antes de o método do controlador ser invocado; isso significa que você não precisa escrever nenhuma lógica de validação no controlador e desacopla muito bem essas duas partes do código:

```php
<?php
namespace App\Controller;

use App\Request\FooRequest;

class IndexController
{
    public function index(FooRequest $request)
    {
        // A requisição recebida passou na validação...
        
        // Obtém os dados que passaram na validação...
        $validated = $request->validated();
    }
}
```

Se a validação falhar, o validador lançará uma exceção `Hyperf\Validation\ValidationException`; você pode tratar essa exceção adicionando uma classe de handler de exceções personalizada. Ao mesmo tempo, também fornecemos um handler de exceções `Hyperf\Validation\ValidationExceptionHandler` para tratá-la, e você pode simplesmente configurar o handler de exceções que fornecemos. Porém, o handler padrão pode não atender às suas necessidades; conforme o caso, você pode personalizar o comportamento após uma falha de validação por meio de um handler de exceções personalizado.

#### Personalizar mensagens de erro

Você pode personalizar as mensagens de erro usadas pela requisição de formulário sobrescrevendo o método `messages`; esse método deve retornar um array de pares propriedade/regra e suas mensagens de erro correspondentes:

```php
/**
 * Obtém as mensagens de erro das regras de validação definidas
 */
public function messages(): array
{
    return [
        'foo.required' => 'foo is required',
        'bar.required'  => 'bar is required',
    ];
}
```

#### Personalizar atributos de validação

Se você deseja substituir a parte `:attribute` das mensagens de validação por um nome de atributo personalizado, pode especificar nomes personalizados sobrescrevendo o método `attributes`. Esse método retorna um array de pares chave-valor com nomes de atributos e seus nomes personalizados correspondentes:

```php
/**
 * Obtém os atributos personalizados dos erros de validação
 */
public function attributes(): array
{
    return [
        'foo' => 'foo of request',
    ];
}
```

### Criar um validador manualmente

Se você não quiser usar o recurso de validação automática do `FormRequest` (requisição de formulário), pode obter a fábrica de validadores injetando a interface `ValidatorFactoryInterface` e, em seguida, criar manualmente uma instância de validador por meio do método `make`:

```php
<?php

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class IndexController
{
    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;

    public function foo(RequestInterface $request)
    {
        $validator = $this->validationFactory->make(
            $request->all(),
            [
                'foo' => 'required',
                'bar' => 'required',
            ],
            [
                'foo.required' => 'foo is required',
                'bar.required' => 'bar is required',
            ]
        );

        if ($validator->fails()){
            // Handle exception
            $errorMessage = $validator->errors()->first();  
        }
        // Do something
    }
}
```

O primeiro parâmetro passado ao método `make` são os dados a serem validados e o segundo parâmetro são as regras de validação desses dados.

#### Personalizar mensagens de erro

Se necessário, você também pode usar mensagens de erro personalizadas em vez dos valores padrão para a validação. Há várias maneiras de especificar mensagens personalizadas. Primeiro, você pode passar as mensagens personalizadas como terceiro parâmetro para o método `make`:

```php
<?php
$messages = [
    'required' => 'The :attribute field is required.',
];

$validator = $this->validationFactory->make($request->all(), $rules, $messages);
```

Neste exemplo, o placeholder `:attribute` será substituído pelo nome real do campo validado. Além disso, você também pode usar outros placeholders nas mensagens de validação. Por exemplo:

```php
$messages = [
    'same'    => 'The :attribute and :other must match.',
    'size'    => 'The :attribute must be exactly :size.',
    'between' => 'The :attribute value :input is not between :min - :max.',
    'in'      => 'The :attribute must be one of the following types: :values',
];
```

#### Especificar mensagens personalizadas para um atributo específico

Às vezes você pode querer personalizar a mensagem de erro apenas para um campo específico. Basta usar um «ponto» após o nome do atributo para especificar a regra de validação:

```php
$messages = [
    'email.required' => 'We need to know your e-mail address!',
];
```

#### Especificar mensagens personalizadas em arquivos PHP

Na maioria dos casos, você provavelmente especificará mensagens personalizadas em arquivos, em vez de passá-las diretamente ao `Validator`. Para isso, coloque suas mensagens no array `custom` dentro do arquivo de idioma `storage/languages/xx/validation.php`.

#### Especificar atributos personalizados em arquivos PHP

Se você deseja substituir a parte `:attribute` das mensagens de validação por um nome de atributo personalizado, pode especificar nomes personalizados no array `attributes` do arquivo de idioma `storage/languages/xx/validation.php`:

```php
'attributes' => [
    'email' => 'email address',
],
```

### Hooks pós-validação

O validador também permite adicionar funções de callback permitidas após uma validação bem-sucedida, para que você realize a próxima etapa de validação ou até mesmo adicione mais mensagens de erro à coleção de mensagens. Para usá-lo, basta chamar o método `after` na instância de validação:

```php
<?php

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class IndexController
{
    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;

    public function foo(RequestInterface $request)
    {
        $validator = $this->validationFactory->make(
            $request->all(),
            [
                'foo' => 'required',
                'bar' => 'required',
            ],
            [
                'foo.required' => 'foo is required',
                'bar.required' => 'bar is required',
            ]
        );

        $validator->after(function ($validator) {
            if ($this->somethingElseIsInvalid()) {
                $validator->errors()->add('field', 'Something is wrong with this field!');
            }
        });
        
        if ($validator->fails()) {
            //
        }
    }
}
```

## Tratar mensagens de erro

Chamar o método `errors` em uma instância de `Validator` retorna uma instância de `Hyperf\Support\MessageBag`, que possui diversos métodos convenientes para tratar mensagens de erro.

### Ver a primeira mensagem de erro de um campo específico

Para ver a primeira mensagem de erro de um campo específico, use o método `first`:

```php
$errors = $validator->errors();

echo $errors->first('foo');
```

### Ver todas as mensagens de erro de um campo específico

Se você precisa obter um array com todas as mensagens de erro de um campo específico, use o método `get`:

```php
foreach ($errors->get('foo') as $message) {
    //
}
```

Se quiser validar campos de array de um formulário, você pode usar `*` para obter todas as mensagens de erro de cada elemento do array:

```php
foreach ($errors->get('foo.*') as $message) {
    //
}
```

### Ver todas as mensagens de erro de todos os campos

Se você quiser obter todas as mensagens de erro de todos os campos, use o método `all`:

```php
foreach ($errors->all() as $message) {
    //
}
```

### Verificar se um campo específico possui mensagens de erro

O método `has` pode ser usado para verificar se um campo específico possui mensagens de erro:

```php
if ($errors->has('foo')) {
    //
}
```

### Cenários

O validador adiciona a funcionalidade de cenários, permitindo modificar as regras de validação conforme a necessidade de forma bastante conveniente.

> Este recurso requer versão deste componente maior ou igual a 2.2.7

Crie um `SceneRequest` da seguinte forma:

```php
<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class SceneRequest extends FormRequest
{
    protected array $scenes = [
        'foo' => ['username'],
        'bar' => ['username', 'password'],
        'tar' => ['username' => 'string|required', 'password'],
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => 'required',
            'gender' => 'required',
        ];
    }
}
```

Quando usamos normalmente, todas as regras de validação são aplicadas, ou seja, tanto `username` quanto `gender` são obrigatórios.

Podemos definir cenários para que esta requisição valide apenas `username` como obrigatório.

Se configuramos `Hyperf\Validation\Middleware\ValidationMiddleware` e injetamos `SceneRequest` no método, os parâmetros de entrada serão validados diretamente no middleware, de modo que o valor do cenário não terá efeito; portanto, precisamos obter o `SceneRequest` correspondente no contêiner dentro do método para trocar o cenário.

```php
<?php

namespace App\Controller;

use App\Request\DebugRequest;
use App\Request\SceneRequest;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController(prefix: 'foo')]
class FooController extends Controller
{
    public function scene()
    {
        $request = $this->container->get(SceneRequest::class);
        $request->scene('foo')->validateResolved();

        return $this->response->success($request->all());
    }
}
```

É claro que também podemos trocar o cenário por meio da anotação `Scene`:

```php
<?php

namespace App\Controller;

use App\Request\DebugRequest;
use App\Request\SceneRequest;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Validation\Annotation\Scene;

#[AutoController(prefix: 'foo')]
class FooController extends Controller
{
    #[Scene(scene:'bar1')]
    public function bar1(SceneRequest $request)
    {
        return $this->response->success($request->all());
    }

    #[Scene(scene:'bar2', argument: 'request')] // Vinculado a $request
    public function bar2(SceneRequest $request)
    {
        return $this->response->success($request->all());
    }

    #[Scene(scene:'bar3', argument: 'request')]
    #[Scene(scene:'bar3', argument: 'req')] // Suporta múltiplos parâmetros
    public function bar3(SceneRequest $request, DebugRequest $req)
    {
        return $this->response->success($request->all());
    }

    #[Scene()] // O scene padrão é o nome do método, equivalente a #[Scene(scene: 'bar1')]
    public function bar1(SceneRequest $request)
    {
        return $this->response->success($request->all());
    }
}
```
## Regras de validação

Abaixo está a lista das regras válidas e suas funções:

##### accepted

O campo validado deve ter o valor `yes`, `on`, `1` ou `true`, o que é útil ao «aceitar os termos de serviço».

##### accepted_if:anotherfield,value,…
Se outro campo em validação for igual ao valor especificado, o campo em validação deve ser `yes`, `on`, `1` ou `true`, o que é útil para validar a aceitação dos «termos de serviço» ou campos semelhantes.

##### declined
O campo em validação deve ser `no`, `off`, `0` ou `false`.

##### declined_if:anotherfield,value,…
Se o valor de outro campo em validação for igual ao valor especificado, o valor do campo em validação deve ser `no`, `off`, `0` ou `false`.

##### active_url

O campo validado deve ter um valor com registro `A` ou `AAAA`, com base na função `PHP` `dns_get_record`.

##### after:date

O campo validado deve ser um valor posterior à data informada; a data será passada pela função PHP strtotime:

```php
'start_date' => 'required|date|after:tomorrow'
```

Você pode especificar outro campo para comparação com a data, em vez de passar uma string de data para o strtotime executar:

```php
'finish_date' => 'required|date|after:start_date'
```

##### after_or_equal:date

O campo validado deve ser um valor maior ou igual à data informada; para mais informações, consulte a regra after:date.

##### alpha

O campo validado deve conter letras (incluindo chinês). Para restringir esta regra de validação a caracteres no intervalo ASCII (a-z e A-Z), você pode fornecer a opção ascii para a regra de validação:

```php
'username' => 'alpha:ascii',
```

##### alpha_dash

O campo validado pode conter letras (incluindo chinês) e números, além de hífen e sublinhado. Para restringir esta regra de validação a caracteres no intervalo ASCII (a-z e A-Z), você pode fornecer a opção ascii para a regra de validação:

```php
'username' => 'alpha_dash:ascii',
```

##### alpha_num

O campo validado deve conter letras (incluindo chinês) ou números. Para restringir esta regra de validação a caracteres no intervalo ASCII (a-z e A-Z), você pode fornecer a opção ascii para a regra de validação:

```php
'username' => 'alpha_num:ascii',
```

#### ascii

O campo em validação deve conter exclusivamente caracteres ASCII de 7 bits.

##### array

O campo validado deve ser um array PHP.

##### required_array_keys:foo,bar,…

O campo validado deve ser um array e deve conter pelo menos as chaves especificadas.

##### bail

Se a primeira regra de validação falhar, a execução das demais regras de validação é interrompida.

##### before:date

Em contraste com after:date, o campo validado deve ser um valor anterior à data especificada; a data será passada para a função PHP strtotime.

##### before_or_equal:date

O campo validado deve ser menor ou igual à data informada. A data será passada para a função strtotime do PHP.

##### between:min,max

O tamanho do campo validado deve estar entre o valor mínimo e o valor máximo informados; strings, números, arrays e arquivos podem usar esta regra da mesma forma que a regra size:

'name' => 'required|between:1,20'

##### boolean

O campo validado deve poder ser convertido em valor booleano, aceitando entradas como true, false, 1, 0, "1" e "0".

##### boolean:strict

O campo validado deve poder ser convertido em valor booleano, aceitando apenas true e false.

##### confirmed

O campo validado deve ter um campo correspondente foo_confirmation; por exemplo, se o campo validado for password, deve ser informado um campo password_confirmation que corresponda a ele.

##### date

O campo validado deve ser uma data válida com base na função strtotime do PHP

##### date_equals:date

O campo validado deve ser igual à data informada; a data será passada para a função strtotime do PHP.

##### date_format:format

O campo validado deve corresponder ao formato especificado; você pode usar as funções PHP date ou date_format para validar o campo.

##### decimal:min,max

O campo validado deve ser do tipo numérico e deve conter a quantidade de casas decimais especificada:

```php
// Deve ter exatamente duas casas decimais (por exemplo 9.99)...
'price' => 'decimal:2'

// Deve ter de 2 a 4 casas decimais...
'price' => 'decimal:2,4'
```

##### lowercase

O campo validado deve estar em minúsculas.

##### uppercase

O campo validado deve estar em maiúsculas.

##### mac_address

O campo validado deve ser um endereço MAC.

##### max_digits:value

O número inteiro validado deve ter no máximo o comprimento value.

##### min_digits:value

O número inteiro validado deve ter pelo menos _value_ dígitos.

##### exclude

Os métodos `validate` e `validated` excluirão o campo atualmente validado.

##### exclude_if:anotherfield,value
Se `anotherfield` for igual a `value`, os métodos `validate` e `validated` excluirão o campo atualmente validado.

Em alguns cenários complexos, você também pode usar o método `Rule::excludeIf`, que precisa retornar um valor booleano ou uma função anônima. Se retornar uma função anônima, essa função deve retornar `true` ou `false` para decidir se o campo validado deve ser excluído:

```php
use Hyperf\Validation\Rule;

$this->validationFactory->make($request->all(), [
    'role_id' => Rule::excludeIf($request->user()->is_admin),
]);

$this->validationFactory->make($request->all(), [
    'role_id' => Rule::excludeIf(fn () => $request->user()->is_admin),
]);
```

##### prohibited

O campo que precisa ser validado não deve existir ou deve estar vazio. Um campo será considerado «vazio» se atender a uma das seguintes condições:

1. O valor é `null`.
2. O valor é uma string vazia.
3. O valor é um array vazio ou um objeto contável vazio.
4. O valor é um arquivo enviado por upload, mas o caminho do arquivo está vazio.

##### prohibited_if:anotherfield,value,…

Se o campo `anotherfield` for igual a qualquer `value`, o campo que precisa ser validado não deve existir ou deve estar vazio. Um campo será considerado «vazio» se atender a uma das seguintes condições:

1. O valor é `null`.
2. O valor é uma string vazia.
3. O valor é um array vazio ou um objeto contável vazio.
4. O valor é um arquivo enviado por upload, mas o caminho do arquivo está vazio.

Se você precisar de lógica de proibição condicional complexa, pode usar o método `Rule::prohibitedIf`. Esse método aceita um valor booleano ou um closure. Quando recebe um closure, ele deve retornar `true` ou `false` para indicar se o campo validado deve ser proibido:


```php
use Hyperf\Validation\Rule;

$this->validationFactory->make($request->all(), [
    'role_id' => Rule::prohibitedIf($request->user()->is_admin),
]);

$this->validationFactory->make($request->all(), [
    'role_id' => Rule::prohibitedIf(fn () => $request->user()->is_admin),
]);
```


##### missing

O campo validado não deve existir nos dados de entrada.

##### missing_if:anotherfield,value,…

Se o campo `_anotherfield_` for igual a qualquer `_value_`, o campo validado não deve existir.

##### missing_unless:anotherfield,value

O campo validado não deve existir, a menos que o campo `_anotherfield_` seja igual a qualquer `_value_`.

##### missing_with:foo,bar,…

Se qualquer outro campo especificado existir, o campo validado não deve existir.

##### missing_with_all:foo,bar,…

Se todos os outros campos especificados existirem, o campo validado não deve existir.

##### multiple_of:value

O campo validado deve ser múltiplo de `_value_`.

##### doesnt_start_with:foo,bar,…

O campo validado não pode começar com um dos valores informados.

##### doesnt_end_with:foo,bar,…

O campo validado não pode terminar com um dos valores informados.

##### different:field

O campo validado deve ter um valor diferente do campo especificado.

##### digits:value

O campo validado deve ser numérico e ter o comprimento especificado por `value`.

##### digits_between:min,max

O comprimento numérico do campo validado deve estar entre o valor mínimo e o valor máximo.

##### dimensions

As dimensões da imagem validada devem atender às restrições especificadas pelos parâmetros desta regra:

```php
'avatar' => 'dimensions:min_width=100,min_height=200'
```

As restrições válidas incluem: `min_width`, `max_width`, `min_height`, `max_height`, `width`, `height`, `ratio`.

A restrição `ratio` define a proporção largura/altura, que pode ser representada pela expressão `3/2` ou pelo número de ponto flutuante `1.5`:

```php
'avatar' => 'dimensions:ratio=3/2'
```

Como esta regra exige vários parâmetros, você pode usar o método `Rule::dimensions` para construir a regra:

```php
use Hyperf\Validation\Rule;

public function rules(): array
{
return [
           'avatar' => [
              'required',
              Rule::dimensions()->maxWidth(1000)->maxHeight(500)->ratio(3 / 2),
           ],
       ];
}
```
##### distinct

Ao lidar com arrays, o campo validado não pode conter valores duplicados:

```php
'foo.*.id' => 'distinct'
```

##### email

O campo validado deve ser um endereço de e-mail com formato válido.

##### exists:table,column

O campo validado deve existir na tabela de dados especificada.

Uso básico:

```php
'state' => 'exists:states'
```

Se a opção `column` não for especificada, o nome do campo será usado.

Especificar um nome de coluna personalizado:

```php
'state' => 'exists:states,abbreviation'
```

Às vezes, você pode precisar especificar a conexão de banco de dados a ser usada para a consulta `exists`; isso pode ser feito prefixando o nome da tabela com a conexão de banco de dados usando `.`, ou especificando o nome da classe do modelo para resolução automática:

```php
// Forma com conexão de banco de dados prefixada
'email' => 'exists:connection.staff,email'

// Forma com resolução automática pelo nome da classe do modelo
'email' => 'exists:StaffModel::class,email'
```

Se você quiser personalizar a consulta executada pela regra de validação, pode usar a classe `Rule` para definir a regra. Neste exemplo, também especificamos as regras de validação em forma de array, em vez de usar o caractere `|` para delimitá-las:

```php
use Hyperf\Validation\Rule;

$validator = $this->validationFactory->make($data, [
    'email' => [
        'required',
        Rule::exists('staff')->where(function ($query) {
            $query->where('account_id', 1);
        }),
    ],
]);
```

##### file

O campo validado deve ser um arquivo enviado com sucesso.

##### filled

Se o campo validado existir, ele não pode estar vazio.

##### gt:field

O campo validado deve ser maior que o campo `field` informado; os dois campos devem ter o mesmo tipo, aplicável a strings, números, arrays e arquivos, semelhante à regra `size`

##### gte:field

O campo validado deve ser maior ou igual ao campo `field` informado; os dois campos devem ter o mesmo tipo, aplicável a strings, números, arrays e arquivos, semelhante à regra `size`

##### image

O arquivo validado deve ser uma imagem (`jpeg`, `png`, `bmp`, `gif` ou `svg`)

##### in:foo,bar…

O valor do campo validado deve estar na lista informada. Como essa regra frequentemente exige que façamos `implode` em arrays, podemos usar `Rule::in` para construir esta regra:

```php
use Hyperf\Validation\Rule;

$validator = $this->validationFactory->make($data, [
    'zones' => [
        'required',
        Rule::in(['first-zone', 'second-zone']),
    ],
]);
```

##### in_array:anotherfield

O campo validado deve existir no valor de outro campo.

##### integer

O campo validado deve ser do tipo inteiro (tanto o tipo String quanto o tipo Integer passam na validação).

##### integer:strict

O campo validado deve ser do tipo inteiro (apenas o tipo Integer passa na validação).

##### ip

O campo validado deve ser um endereço IP.

##### ipv4

O campo validado deve ser um endereço IPv4.

##### ipv6

O campo validado deve ser um endereço IPv6.

##### json

O campo validado deve ser uma string JSON válida

##### lt:field

O campo validado deve ser menor que o campo `field` informado; os dois campos devem ter o mesmo tipo, aplicável a strings, números, arrays e arquivos, semelhante à regra `size`

##### lte:field

O campo validado deve ser menor ou igual ao campo `field` informado; os dois campos devem ter o mesmo tipo, aplicável a strings, números, arrays e arquivos, semelhante à regra `size`

##### max:value

O campo validado deve ser menor ou igual ao valor máximo, com o mesmo uso da regra `size` para campos de string, numéricos, array e arquivo.

##### mimetypes：text/plain…

O arquivo validado deve corresponder a um dos tipos de arquivo `MIME` informados:

```php
'video' => 'mimetypes:video/avi,video/mpeg,video/quicktime'
```

Para determinar o tipo `MIME` do arquivo enviado, o componente lerá o conteúdo do arquivo para inferir o tipo `MIME`, o que pode diferir do tipo `MIME` do cliente.

##### mimes:foo,bar,…

O tipo `MIME` do arquivo validado deve ser um dos tipos de extensão listados nesta regra
Uso básico da regra `MIME`:

```php
'photo' => 'mimes:jpeg,bmp,png'
```

Embora você informe apenas a extensão, esta regra na verdade valida o tipo `MIME` do arquivo obtido pela leitura do seu conteúdo.
A lista completa de tipos `MIME` e suas extensões correspondentes pode ser encontrada aqui: [mime types](http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types)
##### min:value

Em contraste com `max:value`, o campo validado deve ser maior ou igual ao valor mínimo; para campos de string, numéricos, array e arquivo, o uso é o mesmo da regra `size`.

##### not_in:foo,bar,…

O valor do campo validado não pode estar na lista informada; semelhante à regra `in`, podemos usar o método `Rule::notIn` para construir a regra:

```php
use Hyperf\Validation\Rule;

$validator = $this->validationFactory->make($data, [
    'toppings' => [
        'required',
        Rule::notIn(['sprinkles', 'cherries']),
    ],
]);
```

##### not_regex:pattern

O campo validado não pode corresponder à expressão regular informada

Observação: ao usar os padrões `regex/not_regex`, a regra deve ser colocada em um array e não se pode usar o separador de pipe, especialmente quando a expressão regular contém o caractere pipe.

##### nullable

O campo validado pode ser `null`, o que é útil ao validar dados primitivos que podem ser `null`, como inteiros ou strings.

##### numeric

O campo validado deve ser numérico

##### present

O campo validado deve estar presente nos dados de entrada, mas pode estar vazio.

##### regex:pattern

O campo validado deve corresponder à expressão regular informada.
Esta regra usa internamente a função `preg_match` do `PHP`. Portanto, o padrão especificado precisa seguir o formato exigido pela função `preg_match` e conter delimitadores válidos. Por exemplo:

```php
 'email' => 'regex:/^.+@.+$/i'
```

Observação: ao usar os padrões `regex/not_regex`, a regra deve ser colocada em um array e não se pode usar o separador de pipe, especialmente quando a expressão regular contém o caractere pipe.

##### required

O valor do campo validado não pode estar vazio; o valor do campo é considerado vazio nas seguintes situações:
O valor é `null`
O valor é uma string vazia
O valor é um array vazio ou um objeto `Countable` vazio
O valor é um arquivo enviado por upload, mas o caminho está vazio

##### required_if:anotherfield,value,…

O campo validado deve existir e não pode estar vazio quando `anotherfield` for igual ao valor especificado `value`.
Se você quiser construir condições mais complexas para a regra `required_if`, pode usar o método `Rule::requiredIf`, que recebe um valor booleano ou um closure. Quando recebe um closure, ele retorna `true` ou `false` para indicar se o campo validado é obrigatório:

```php
use Hyperf\Validation\Rule;

$validator = $this->validationFactory->make($request->all(), [
    'role_id' => Rule::requiredIf($request->user()->is_admin),
]);

$validator = $this->validationFactory->make($request->all(), [
    'role_id' => Rule::requiredIf(function () use ($request) {
        return $request->user()->is_admin;
    }),
]);
```

##### required_unless:anotherfield,value,…

A menos que o campo `anotherfield` seja igual a `value`, o campo validado não pode estar vazio.

##### required_with:foo,bar,…

O campo validado só é obrigatório quando qualquer um dos outros campos especificados existir.

##### required_with_all:foo,bar,…

O campo validado só é obrigatório quando todos os campos especificados existirem.

##### required_without:foo,bar,…

O campo validado só é obrigatório quando qualquer um dos campos especificados não existir.

##### required_without_all:foo,bar,…

O campo validado só é obrigatório quando nenhum dos campos especificados existir.

##### same:field

O campo informado e o campo validado devem coincidir.

##### size:value

O campo validado deve ter um tamanho/dimensão que corresponda ao valor informado `value`; para strings, `value` é o número correspondente de caracteres; para valores numéricos, `value` é o valor inteiro informado; para arrays, `value` é o comprimento do array; para arquivos, `value` é a quantidade correspondente de quilobytes do arquivo (KB).

##### starts_with:foo,bar,...

O campo validado deve começar com um dos valores informados.

##### string

O campo validado deve ser uma string; se o campo puder estar vazio, é necessário atribuir a regra `nullable` a ele.

##### timezone

O texto validado deve ser um identificador de fuso horário válido com base na função `PHP` `timezone_identifiers_list`

##### unique:table,column,except,idColumn

O campo validado deve ser único na tabela de dados informada; se a opção `column` não for especificada, o nome do campo será usado como `column` padrão.

1. Especificar um nome de coluna personalizado:

```php
'email' => 'unique:users,email_address'
```

2. Personalizar a conexão de banco de dados:
   Às vezes, você pode precisar personalizar a conexão de banco de dados gerada pelo validador; como visto acima, definir `unique:users` como regra de validação usará a conexão de banco de dados padrão para consultar o banco de dados. Para sobrescrever a conexão padrão, use "." após o nome da tabela de dados para especificar a conexão, ou especifique o nome da classe do modelo para resolução automática:

```php
// Forma com conexão de banco de dados prefixada
'email' => 'unique:connection.users,email_address'

// Forma com resolução automática pelo nome da classe do modelo
'email' => 'unique:UserModel::class,email_address'
```

3. Forçar uma regra de unicidade que ignore um `ID` informado:
   Às vezes, você pode querer ignorar um `ID` informado na verificação de unicidade. Por exemplo, considere uma interface de «atualização de atributos» contendo nome de usuário, endereço de e-mail e localização; você validará que o endereço de e-mail é único. Porém, se o usuário alterar apenas o campo nome de usuário e não alterar o campo de e-mail, você não quer lançar um erro de validação porque o usuário já possui esse endereço de e-mail; você só quer lançar um erro de validação se o e-mail informado pelo usuário já estiver em uso por outra pessoa.

Para dizer ao validador para ignorar o `ID` do usuário, você pode usar a classe `Rule` para definir esta regra; também especificamos as regras de validação em forma de array, em vez de usar `|` para delimitar as regras:

```php
use Hyperf\Validation\Rule;

$validator = $this->validationFactory->make($data, [
    'email' => [
        'required',
        Rule::unique('users')->ignore($user->id),
    ],
]);
```

Além de passar o valor da chave primária da instância do modelo para o método `ignore`, você também pode passar a instância inteira do modelo. O componente extrairá automaticamente o valor da chave primária da instância do modelo:

```php
Rule::unique('users')->ignore($user)
```

Se a sua tabela de dados usar um campo de chave primária diferente de `id`, você pode especificar o nome do campo ao chamar o método `ignore`:

```php
'email' => Rule::unique('users')->ignore($user->id, 'user_id')
```

Por padrão, a regra `unique` verifica a unicidade da coluna que corresponde ao nome da propriedade a ser validada. No entanto, você pode especificar um nome de coluna diferente como segundo parâmetro do método `unique`:

```php
Rule::unique('users', 'email_address')->ignore($user->id),
```

4. Adicionar cláusulas `where` extras:

Ao usar o método `where` para personalizar a consulta, você também pode especificar restrições extras de consulta; por exemplo, vamos adicionar uma restrição que valida `account_id` como 1:

```php
'email' => Rule::unique('users')->where(function ($query) {
    $query->where('account_id', 1);
})
```

##### url

O campo validado deve ser uma URL válida.

##### uuid

O campo validado deve ser um identificador único global (UUID) válido no padrão RFC 4122 (versão 1, 3, 4 ou 5).

##### sometimes

Adicionar regras condicionais
Validar quando existir

Em alguns cenários, você pode querer fazer a verificação de validação apenas quando um campo específico existir. Para fazer isso rapidamente, adicione a regra `sometimes` à lista de regras:

```php
$validator = $this->validationFactory->make($data, [
    'email' => 'sometimes|required|email',
]);
```

No exemplo acima, o campo `email` só será validado quando existir no array `$data`.

Observação: se você tentar validar um campo que sempre existe, mas pode estar vazio, consulte as observações sobre campos opcionais.

Validação com condições complexas

Às vezes você pode querer adicionar regras de validação com base em lógica condicional mais complexa. Por exemplo, você pode querer exigir que um campo específico seja obrigatório apenas quando o valor de outro campo for maior que 100, ou pode precisar que dois campos tenham valores específicos apenas quando outro campo existir. Adicionar essa regra de validação não é nada complicado. Primeiro, crie uma regra estática que nunca muda na instância de `Validator`:

```php
$validator = $this->validationFactory->make($data, [
    'email' => 'required|email',
    'games' => 'required|numeric',
]);
```

Vamos supor que nossa aplicação Web atenda colecionadores de jogos. Se um colecionador de jogos se registrar em nossa aplicação e possuir mais de 100 jogos, queremos que ele explique por que tem tantos jogos; por exemplo, talvez ele administre uma loja de jogos usados, ou talvez apenas goste de colecionar. Para adicionar essa condição, podemos usar o método `sometimes` na instância de `Validator`:

```php
$v->sometimes('reason', 'required|max:500', function($input) {
    return $input->games >= 100;
});
```

O primeiro parâmetro passado ao método `sometimes` é o nome do campo que precisa de validação condicional, o segundo parâmetro é a regra que queremos adicionar e, se o closure passado como terceiro parâmetro retornar `true`, a regra é adicionada. Esse método torna simples construir validações condicionais complexas, e você pode até adicionar validação condicional para vários campos de uma vez:

```php
$v->sometimes(['reason', 'cost'], 'required', function($input) {
    return $input->games >= 100;
});
```

Observação: o parâmetro `$input` passado ao closure é uma instância de `Hyperf\Support\Fluent`, que pode ser usado para acessar entradas e arquivos.

### Validar entradas de array

Validar campos de entrada de array de formulário deixa de ser algo doloroso; por exemplo, se a requisição HTTP recebida contiver o campo `photos[profile]`, você pode validá-lo assim:

```php
$validator = $this->validationFactory->make($request->all(), [
    'photos.profile' => 'required|image',
]);
```

Também podemos validar cada elemento de um array; por exemplo, para validar se cada email em uma entrada de array informada é único, você pode fazer assim (esse tipo de campo de array enviado é um array bidimensional, como `person[][email]` ou `person[test][email]`):

```php
$validator = $this->validationFactory->make($request->all(), [
    'person.*.email' => 'email|unique:users',
    'person.*.first_name' => 'required_with:person.*.last_name',
]);
```

Da mesma forma, nos arquivos de idioma você também pode usar o caractere `*` para especificar mensagens de validação, permitindo usar uma única definição de mensagem de validação para regras baseadas em campos de array:

```php
'custom' => [
    'person.*.email' => [
        'unique' => 'O endereço de e-mail de cada pessoa deve ser único',
    ]
],
```

### Regras de validação personalizadas

#### Registrar regras de validação personalizadas

O componente `Validation` usa o mecanismo de eventos para implementar regras de validação personalizadas; definimos o evento `ValidatorFactoryResolved`. Tudo o que você precisa fazer é definir um listener de `ValidatorFactoryResolved` e, no listener, implementar o registro do validador, conforme o exemplo abaixo.

```php
namespace App\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Event\ValidatorFactoryResolved;
use Hyperf\Validation\Validator;

#[Listener]
class ValidatorFactoryResolvedListener implements ListenerInterface
{

    public function listen(): array
    {
        return [
            ValidatorFactoryResolved::class,
        ];
    }

    public function process(object $event): void
    {
        /**  @var ValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $event->validatorFactory;
        // Registrou o validador foo
        $validatorFactory->extend('foo', function (string $attribute, mixed $value, array $parameters, Validator $validator): bool {
            return $value == 'foo';
        });
        // Ao criar uma regra de validação personalizada, às vezes você pode precisar definir um placeholder personalizado para a mensagem de erro; aqui estendemos o placeholder :foo
        $validatorFactory->replacer('foo', function (string $message, string $attribute, string $rule, array $parameters): array|string {
            return str_replace(':foo', $attribute, $message);
        });
    }
}
```

#### Personalizar mensagens de erro

Você também precisa definir mensagens de erro para as regras personalizadas. Você pode fazer isso usando um array de mensagens personalizadas inline ou adicionando entradas no arquivo de idioma de validação. As mensagens devem ser colocadas no primeiro nível do array, e não dentro do array custom, que é usado apenas para armazenar mensagens de erro específicas de atributos. Tomando o validador personalizado `foo` da seção anterior como exemplo:

`storage/languages/en/validation.php` adicione o conteúdo abaixo ao array do arquivo

```php
    'foo' => 'The :attribute must be foo',
```

`storage/languages/zh_CN/validation.php` adicione o conteúdo abaixo ao array do arquivo

```php    
    'foo' => ' :attribute deve ser foo',
```

#### Uso de validador personalizado

```php
<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class DemoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Usa o validador foo
            'name' => 'foo'
        ];
    }
}
```
