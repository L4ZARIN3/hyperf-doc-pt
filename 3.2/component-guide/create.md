# Criar um novo componente

O `Hyperf` oferece oficialmente uma ferramenta para criar pacotes de componentes rapidamente.

```
# Cria um pacote de componente compatível com a versão mais recente do Hyperf
composer create-project hyperf/component-creator your_component dev-master

# Cria um pacote de componente compatível com a versão 2.0 do Hyperf
composer create-project hyperf/component-creator your_component "2.0.*"
```

## Usar no projeto um pacote de componente não publicado

Suponha que o diretório do projeto seja como abaixo

```
/opt/project // Diretório do projeto
/opt/your_component // Diretório do pacote de componente
```

Suponha que o nome do componente seja `your_component/your_component`

Modifique /opt/project/composer.json

> A seguir, omitimos outras configurações irrelevantes

```json
{
    "require": {
        "your_component/your_component": "dev-master"
    },
    "repositories": {
        "your_component": {
            "type": "path",
            "url": "/opt/your_component"
        }
    }
}
```

Por fim, basta executar `composer update -o` no diretório `/opt/project`.
