# Evento

Os eventos de modelo implementam a interface [psr/event-dispatcher](https://github.com/php-fig/event-dispatcher), e por padrão o suporte funcional é fornecido pelo componente [hyperf/event](https://github.com/hyperf/event).

## Eventos de execução

Durante a execução do ORM, os seguintes eventos são disparados correspondente, e você pode ouvi-los para atender às suas necessidades.

| Evento  | Descrição |
| :--------: | :----: |
| Hyperf\Database\Events\QueryExecuted| Após a execução da instrução Query |
| Hyperf\Database\Events\StatementPrepared| Após a instrução SQL ser preparada (prepared) |
| Hyperf\Database\Events\TransactionBeginning| Após o início da transação |
| Hyperf\Database\Events\TransactionCommitted| Após o commit da transação |
| Hyperf\Database\Events\TransactionRolledBack| Após o rollback da transação |

### Listener de execução de SQL

Com base nos eventos de execução do ORM acima, vamos implementar um listener que registra o SQL, para registrar e enviar ao log cada execução de SQL.
Primeiro definimos `DbQueryExecutedListener`, implementando a interface `Hyperf\Event\Contract\ListenerInterface` e definindo a anotação `Hyperf\Event\Annotation\Listener` na classe, assim o Hyperf registrará automaticamente esse listener no despachante de eventos na inicialização da aplicação e executará a lógica de escuta quando o evento for disparado. O código de exemplo é o seguinte:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Collection\Arr;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container)
    {
        // Envia para o log de nome sql correspondente; se não existir, é necessário adicionar a configuração
        // O nome sql do log aqui não é obrigatório, apenas expressa que é possível separar o log de execução de SQL do log comum
        $this->logger = $container->get(LoggerFactory::class)->get('sql');
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event)
    {
        if ($event instanceof QueryExecuted) {
            $sql = $event->sql;
            if (! Arr::isAssoc($event->bindings)) {
                foreach ($event->bindings as $key => $value) {
                    $sql = Str::replaceFirst('?', "'{$value}'", $sql);
                }
            }

            $this->logger->info(sprintf('[%s] %s', $event->time, $sql));
        }
    }
}

```

## Eventos de modelo

Os eventos de modelo não são exatamente iguais aos do `Eloquent ORM`; o `Eloquent ORM` usa `Observer` para ouvir eventos de modelo. O `Hyperf` fornece duas formas, `funções hook` e `listener de eventos`, para tratar os eventos correspondentes.

### Funções hook

|    Nome do evento    |     Momento do disparo     | Bloqueia? |               Observação                |
|:------------:|:----------------:|:--------:|:-------------------------- --:|
|   booting    |  Antes do primeiro carregamento do modelo  |    Não    |    Disparado apenas uma vez no ciclo de vida do processo         |
|    booted    |  Após o primeiro carregamento do modelo  |    Não    |    Disparado apenas uma vez no ciclo de vida do processo         |
|  retrieved   |    Após o preenchimento dos dados   |    Não    |  Disparado sempre que o modelo é consultado no DB ou no cache      |
|   creating   |    Na criação dos dados   |    Sim    |                                  |
|   created    |    Após a criação dos dados   |    Não    |                                  |
|   updating   |    Na atualização dos dados   |    Sim    |                                  |
|   updated    |    Após a atualização dos dados   |    Não    |                                  |
|    saving    | Na criação ou atualização dos dados |    Sim    |                                  |
|    saved    | Após a criação ou atualização dos dados |    Não    |                                  |
|  restoring   | Na restauração de dados com exclusão lógica |    Sim    |                                  |
|   restored   | Após a restauração de dados com exclusão lógica |    Não    |                                  |
|   deleting   |    Na exclusão dos dados   |    Sim    |                                  |
|   deleted    |    Após a exclusão dos dados   |    Não    |                                  |
| forceDeleting |  Na exclusão forçada dos dados  |    Sim    |                                  |
| forceDeleted |  Após a exclusão forçada dos dados  |    Não    |                                  |

Usar eventos para um modelo específico é muito simples: basta adicionar o método correspondente no modelo. Por exemplo, ao salvar dados abaixo, o evento `saving` é disparado, sobrescrevendo ativamente o campo `created_at`.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Events\Saving;

/**
 * @property $id
 * @property $name
 * @property $gender
 * @property $created_at
 * @property $updated_at
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'gender', 'created_at', 'updated_at'];

    protected $casts = ['id' => 'integer', 'gender' => 'integer'];

    public function saving(Saving $event)
    {
        $this->setCreatedAt('2019-01-01');
    }
}

```

### Listener de eventos

Quando você precisa ouvir todos os eventos de modelo, pode personalizar facilmente o `Listener` correspondente. Por exemplo, o listener de cache de modelo abaixo remove o cache correspondente após a modificação e exclusão do modelo.

```php
<?php

declare(strict_types=1);

namespace Hyperf\ModelCache\Listener;

use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Event;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ModelCache\CacheableInterface;

#[Listener]
class DeleteCacheListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            Deleted::class,
            Saved::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof Event) {
            $model = $event->getModel();
            if ($model instanceof CacheableInterface) {
                $model->deleteCache();
            }
        }
    }
}

```

### Observers

Graças ao componente [hyperf/model-listener](https://github.com/hyperf/blob/master/src/model-listener/), também podemos usar `Observer` para ouvir eventos de modelo.
Por meio da anotação [ModelListener](https://github.com/hyperf/hyperf/blob/master/src/model-listener/src/Annotation/ModelListener.php), podemos definir um observer de forma muito prática. O código de exemplo é o seguinte:

```php
<?php
use Hyperf\ModelListener\Annotation\ModelListener;
use App\Model\User;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Created;

/**
 * Define um observer UserObserver, que ouve os eventos do modelo User.
 * Também é possível ouvir vários modelos, basta passar vários modelos na propriedade models
 * Atenção: esta classe será registrada automaticamente no contêiner como singleton
 */
#[ModelListener(models: [ User::class ])]
class UserObserver
{
    public function creating(Creating $event)
    {
        $user = $event->getModel();
        // Disparado ao criar o usuário
    }
    
    public function created(Created $event)
    {
        $user = $event->getModel();
        // Disparado após a criação do usuário ser concluída
    }
    
    //... Omitidos os demais eventos
}
```

