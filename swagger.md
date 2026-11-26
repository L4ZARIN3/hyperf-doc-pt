# Hyperf Swagger

O componente hyperf/swagger é um encapsulamento baseado em zircote/swagger-php

Se você precisar da lista completa de anotações suportadas, consulte o [namespace OpenApi\Annotations](https://github.com/zircote/swagger-php/blob/master/src/Annotations) ou o [site da documentação](https://zircote.github.io/swagger-php/guide/annotations.html#arrays-and-objects)


## Instalação

```
composer require hyperf/swagger
```

## Configuração 

```
php bin/hyperf.php vendor:publish hyperf/swagger
```

| Nome do parâmetro      | Função                                                         |
| -------- | ------------------------------------------------------------ |
| enable   | Se o gerador de documentação Swagger está habilitado                                     |
| port     | Número da porta do gerador de documentação Swagger                                    |
| json_dir | Diretório onde os arquivos JSON gerados pelo gerador de documentação Swagger são salvos                       |
| html     | Caminho onde os arquivos HTML gerados pelo gerador de documentação Swagger são salvos                       |
| url      | Caminho da URL da documentação Swagger                                         |
| auto_generate | Se a documentação Swagger é gerada automaticamente                                     |
| scan.paths | Caminho onde estão os arquivos de interface da API que precisam ser escaneados, um array | 

## Gerar documentação

Se `auto_generate` estiver configurado, a documentação será gerada automaticamente no evento de inicialização do framework, sem necessidade de chamada adicional
```shell
php bin/hyperf.php gen:swagger
```

## Uso

> O namespace SA que aparece abaixo é sempre `use Hyperf\Swagger\Annotation as SA`

O framework pode iniciar vários Servers, e a rota de cada Server pode ser diferenciada pela anotação `SA\Hyperferver`, gerando arquivos swagger diferentes (usando essa configuração como nome do arquivo)

Pode ser configurado na classe do controlador ou no método
```php
#[SA\HyperfServer('http')]
```

```php
#[SA\Post(path: '/test', summary: 'Exemplo de formulário POST', tags: ['Api/Test'])]
#[SA\RequestBody(
    description: 'Parâmetros da requisição',
    content: [
        new SA\MediaType(
            mediaType: 'application/x-www-form-urlencoded',
            schema: new SA\Schema(
                required: ['username', 'age'],
                properties: [
                    new SA\Property(property: 'username', description: 'Descrição do campo nome de usuário', type: 'string'),
                    new SA\Property(property: 'age', description: 'Descrição do campo idade', type: 'string'),
                    new SA\Property(property: 'city', description: 'Descrição do campo cidade', type: 'string'),
                ]
            ),
        ),
    ],
)]
#[SA\Response(response: 200, description: 'Descrição do valor de retorno')]
public function test()
{
}
```

```php
#[SA\Get(path: '/test', summary: 'Exemplo de GET', tags: ['Api/Test'])]
#[SA\QueryParameter(name: 'username', description: 'Descrição do campo nome de usuário', required: true, schema: new SA\Schema(type: 'string'))]
#[SA\QueryParameter(name: 'age', description: 'Descrição do campo idade', required: true, schema: new SA\Schema(type: 'string'))]
#[SA\QueryParameter(name: 'city', description: 'Descrição do campo cidade', required: false, schema: new SA\Schema(type: 'string'))]
#[SA\Response(
    response: 200,
    description: 'Descrição do valor de retorno',
    content: new SA\JsonContent(
        example: '{"code":200,"data":[]}'
    ),
)]
public function list(ConversationRequest $request): array
{
}
```

### Em conjunto com o validador

Nas anotações `SA\Property` e `SA\QueryParameter`, podemos adicionar o parâmetro `rules` e, em conjunto com `SwaggerRequest`, validar se os parâmetros são válidos no middleware.

```php
<?php
namespace App\Controller;

use App\Schema\SavedSchema;
use Hyperf\Swagger\Request\SwaggerRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Swagger\Annotation as SA;

#[SA\HyperfServer(name: 'http')]
class CardController extends Controller
{
    #[SA\Post('/user/save', summary: 'Salvar informações do usuário', tags: ['Gerenciamento de usuários'])]
    #[SA\QueryParameter(name: 'token', description: 'token de autenticação', type: 'string', rules: 'required|string')]
    #[SA\RequestBody(content: new SA\JsonContent(properties: [
        new SA\Property(property: 'nickname', description: 'Apelido', type: 'integer', rules: 'required|string'),
        new SA\Property(property: 'gender', description: 'Gênero', type: 'integer', rules: 'required|integer|in:0,1,2'),
    ]))]
    #[SA\Response(response: '200', content: new SA\JsonContent(ref: '#/components/schemas/SavedSchema'))]
    public function info(SwaggerRequest $request)
    {
        $result = $this->service->save($request->all());

        return $this->response->success($result);
    }
}
```

### Substituir o painel Swagger

Abaixo está a página front-end padrão do Swagger; se precisar personalizar, você pode modificar a configuração `swagger.html`

```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="description"
      content="SwaggerUI"
    />
    <title>SwaggerUI</title>
    <link rel="stylesheet" href="https://unpkg.hyperf.wiki/swagger-ui-dist@4.5.0/swagger-ui.css" />
  </head>
  <body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.hyperf.wiki/swagger-ui-dist@4.5.0/swagger-ui-bundle.js" crossorigin></script>
  <script src="https://unpkg.hyperf.wiki/swagger-ui-dist@4.5.0/swagger-ui-standalone-preset.js" crossorigin></script>
  <script>
    window.onload = () => {
      window.ui = SwaggerUIBundle({
        url: GetQueryString("search"),
        dom_id: '#swagger-ui',
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: "StandaloneLayout",
      });
    };
    function GetQueryString(name) {
      var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
      var r = window.location.search.substr(1).match(reg); //Obtém a string após o caractere "?" na url e faz a correspondência com regex
      var context = "";
      if (r != null)
        context = decodeURIComponent(r[2]);
      reg = null;
      r = null;
      return context == null || context == "" || context == "undefined" ? "/http.json" : context;
    }
  </script>
  </body>
</html>
```

Por exemplo, quando `unpkg.hyperf.wiki` apresentar falha, você pode substituir manualmente por `unpkg.com`, modificando manualmente a configuração `config/autoload/swagger.php`

```php
<?php

declare(strict_types=1);

return [
    'enable' => true,
    'port' => 9500,
    'json_dir' => BASE_PATH . '/storage/swagger',
    'html' => <<<'HTML'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="description"
      content="SwaggerUI"
    />
    <title>SwaggerUI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css" />
  </head>
  <body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js" crossorigin></script>
  <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-standalone-preset.js" crossorigin></script>
  <script>
    window.onload = () => {
      window.ui = SwaggerUIBundle({
        url: GetQueryString("search"),
        dom_id: '#swagger-ui',
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: "StandaloneLayout",
      });
    };
    function GetQueryString(name) {
      var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
      var r = window.location.search.substr(1).match(reg); //Obtém a string após o caractere "?" na url e faz a correspondência com regex
      var context = "";
      if (r != null)
        context = decodeURIComponent(r[2]);
      reg = null;
      r = null;
      return context == null || context == "" || context == "undefined" ? "/http.json" : context;
    }
  </script>
  </body>
</html>
HTML,
    'url' => '/swagger',
    'auto_generate' => true,
    'scan' => [
        'paths' => null,
    ],
];

```
