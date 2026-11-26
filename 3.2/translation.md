# Internacionalização

O suporte do Hyperf à internacionalização é muito amigável e permite que seu projeto suporte vários idiomas.

# Instalação

```bash
composer require hyperf/translation
```

> Este componente é um componente independente, sem dependências relacionadas ao framework, e pode ser reutilizado independentemente em outros projetos ou frameworks.

# Arquivos de idioma

Os arquivos de idioma do Hyperf ficam por padrão em `storage/languages`. Você também pode alterar a pasta dos arquivos de idioma em `config/autoload/translation.php`. Cada idioma corresponde a uma subpasta, por exemplo, `en` refere-se aos arquivos de idioma em inglês e `zh_CN` refere-se aos arquivos de idioma em chinês simplificado. Você pode criar novas pastas de idioma e arquivos de idioma conforme suas necessidades reais. Exemplo:

```
/storage
    /languages
        /en
            messages.php
        /zh_CN
            messages.php
```

Todos os arquivos de idioma retornam um array cujas chaves são do tipo string:

```php
<?php
// storage/languages/en/messages.php

return [
    'welcome' => 'Welcome to our application',
];
```

## Configurar o ambiente de idioma

### Configurar o ambiente de idioma padrão

As configurações relacionadas ao componente de internacionalização são definidas no arquivo de configuração `config/autoload/translation.php`, e você pode modificá-lo conforme suas necessidades reais.

```php
<?php
// config/autoload/translation.php

return [
    // Idioma padrão
    'locale' => 'zh_CN',
    // Idioma de fallback; quando o texto do idioma padrão não é fornecido, o texto correspondente do idioma de fallback será usado
    'fallback_locale' => 'en',
    // Pasta onde os arquivos de idioma são armazenados
    'path' => BASE_PATH . '/storage/languages',
];
```

### Configurar o ambiente de idioma temporário

```php
<?php

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\TranslatorInterface;

class FooController
{
    #[Inject]
    private TranslatorInterface $translator;
    
    public function index()
    {
        // Válido apenas durante o ciclo de vida da requisição ou corrotina atual
        $this->translator->setLocale('zh_CN');
    }
}
```

# Traduzir strings

## Traduzir por meio de TranslatorInterface

Você pode traduzir strings injetando `Hyperf\Contact\TranslatorInterface` e chamando o método `trans` da instância:

```php
<?php

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\TranslatorInterface;

class FooController
{
    #[Inject]
    private TranslatorInterface $translator;
    
    public function index()
    {
        return $this->translator->trans('messages.welcome', [], 'zh_CN');
    }
}
```

## Traduzir por meio de funções globais

Você também pode traduzir strings por meio das funções globais `__()` ou `trans()`.   
O primeiro parâmetro da função usa a forma `chave` (referindo-se à chave que usa a string de tradução como chave) ou `arquivo.chave`.

```php
echo __('messages.welcome');
echo trans('messages.welcome');
```

# Definir placeholders na string de tradução

Você também pode definir placeholders nas strings de idioma; todos os placeholders usam `:` como prefixo. Por exemplo, usar o nome de usuário como placeholder:

```php
<?php
// storage/languages/en/messages.php

return [
    'welcome' => 'Welcome :name',
];
```

A substituição de placeholders usa o segundo parâmetro da função:

```php
echo __('messages.welcome', ['name' => 'Hyperf']);
```

Se o placeholder estiver todo em letras maiúsculas, ou com a primeira letra maiúscula, a string traduzida também estará na forma maiúscula correspondente:

```php
'welcome' => 'Welcome, :NAME', // Welcome, HYPERF
'goodbye' => 'Goodbye, :Name', // Goodbye, Hyperf
```

# Tratar plurais

As regras de plural variam entre idiomas. Em chinês isso talvez não seja uma preocupação, mas ao traduzir para outros idiomas precisamos lidar com as formas plurais das palavras. Podemos usar o caractere `|` (pipe) para distinguir as formas singular e plural da string:

```php
'apples' => 'There is one apple|There are many apples',
```

Também é possível especificar intervalos numéricos, criando regras de plural mais complexas:

```php
'apples' => '{0} There are none|[1,19] There are some|[20,*] There are many',
```

Depois de definir as regras de plural usando o caractere `|` (pipe), você pode usar a função global `trans_choice` para obter o texto da string de acordo com a `quantidade` fornecida. No exemplo abaixo, como a quantidade é maior que `1`, a forma plural da string de tradução será retornada:

```php
echo trans_choice('messages.apples', 10);
```

Naturalmente, além da função global `trans_choice()`, você também pode usar o método `transChoice` de `Hyperf\Contract\TranslatorInterface`.
