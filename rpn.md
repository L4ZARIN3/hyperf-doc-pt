# RPN - Notação polonesa reversa

`RPN` é uma forma de expressão matemática introduzida em 1920 pelo matemático polonês Jan Łukasiewicz. Na notação polonesa reversa, todos os operadores são colocados depois dos operandos, por isso também é chamada de notação pós-fixada. A notação polonesa reversa não precisa de parênteses para indicar a prioridade dos operadores.

```bash
composer require hyperf/rpn
```

## Lógica RPN

Lógica básica

- while houver entrada
    - leia o próximo símbolo X
    - IF X é um operando
        - empilhe
    - ELSE IF X é um operador
        - há uma tabela prévia que indica que esse operador precisa de n parâmetros
        - IF houver menos de n operandos na pilha
            - (erro) o usuário não forneceu operandos suficientes
    - Else, desempilhe n operandos
    - calcule o operador.
    - empilhe o valor calculado
- IF houver apenas um valor na pilha
    - esse valor é o resultado de toda a expressão
- ELSE se houver mais de um valor
    - (erro) o usuário forneceu operandos em excesso

Exemplo

A expressão infixa `5 + ((1 + 2) * 4) - 3` é escrita como

`5 1 2 + 4 * + 3 -`

A tabela abaixo mostra o processo de avaliação dessa expressão polonesa reversa da esquerda para a direita; a coluna da pilha mostra os valores intermediários, para acompanhar o algoritmo.

| Entrada | Operação     | Pilha    | Comentário                       |
| ---- | -------- | ------- | -------------------------- |
| 5    | Empilhar     | 5       |                            |
| 1    | Empilhar     | 5, 1    |                            |
| 2    | Empilhar     | 5, 1, 2 |                            |
| +    | Operação de adição | 5, 3    | 1, 2 são desempilhados e o resultado 3 é empilhado    |
| 4    | Empilhar     | 5, 3, 4 |                            |
| *    | Operação de multiplicação | 5, 12   | 3, 4 são desempilhados e o resultado 12 é empilhado  |
| +    | Operação de adição | 17      | 5, 12 são desempilhados e o resultado 17 é empilhado |
| 3    | Empilhar     | 17, 3   |                            |
| -    | Operação de subtração | 14      | 17, 3 são desempilhados e o resultado 14 é empilhado |

Ao concluir o cálculo, há apenas um operando na pilha, que é o resultado da expressão: 14

## Uso

Calcular diretamente uma expressão RPN

```php
<?php
use Hyperf\Rpn\Calculator;

$calculator = new Calculator();
$calculator->calculate('5 1 2 + 4 * + 3 -', []); // '14'
```

Definir a precisão do cálculo

```php
<?php
use Hyperf\Rpn\Calculator;

$calculator = new Calculator();
$calculator->calculate('5 1 2 + 4 * + 3 -', [], 2); // '14.00'
```

Definir variáveis

```php
<?php
use Hyperf\Rpn\Calculator;

$calculator = new Calculator();
$calculator->calculate('[0] 1 2 + 4 * + [1] -', [5, 10]); // '7'
```

### Converter expressão infixa em expressão pós-fixada

> Ainda não há suporte para uso de variáveis

```php
<?php
use Hyperf\Rpn\Calculator;

$calculator = new Calculator();
$calculator->toRPNExpression('4 - 2 * ( 5 + 5 ) - 10'); // 4 2 5 5 + * - 10 -
```
