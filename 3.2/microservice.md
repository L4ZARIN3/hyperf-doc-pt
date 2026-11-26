# Microsserviços

Microsserviços são serviços pequenos e autônomos que trabalham em conjunto.

## Pequenos, focados em fazer bem uma coisa

Com a iteração dos requisitos e o acréscimo de novas funcionalidades, a base de código tende a ficar cada vez maior. Embora desejemos fortemente uma modularização clara em uma base de código enorme, na prática os limites entre os módulos são difíceis de delimitar, e aos poucos códigos de funcionalidades semelhantes aparecem por toda a base de código, de modo que, ao iterar, fica difícil saber onde fazer a modificação, e corrigir `Bug` e adicionar novas características e funcionalidades torna-se cada vez mais difícil.   
Em um sistema monolítico, normalmente criam-se algumas camadas de abstração ou implementa-se a modularização para garantir a `coesão` do código, evitando os problemas mencionados acima.

> Segundo a exposição de Robert C. Martin sobre o [Princípio da Responsabilidade Única (Single Responsibility Principle)](https://pt.wikipedia.org/wiki/Princ%C3%ADpio_da_responsabilidade_%C3%BAnica): “*Reúna as coisas que mudam pelo mesmo motivo e separe as coisas que mudam por motivos diferentes.*” Essa exposição enfatiza bem o conceito de coesão.

Os microsserviços aplicam essa ideia a serviços independentes: os limites dos serviços são determinados com base nos limites do negócio, e cada serviço se concentra nas coisas dentro do seu limite, o que permite evitar muitos problemas derivados de uma base de código muito grande.   
Então, quão pequeno deve ser um microsserviço? Tão pequeno quanto necessário, mas não pequeno demais. Como medir se um sistema foi dividido suficientemente bem? Quando, diante desse sistema, você não tiver mais o desejo de "dividi-lo por ser grande demais", então ele já deve ser suficientemente pequeno. Quanto menores os serviços, mais evidentes são as vantagens e desvantagens da `arquitetura de microsserviços (Microservice)`; quanto menores os serviços usados, mais benefícios traz a independência, mas gerenciar uma grande quantidade de serviços também será mais complexo.

## Autonomia

Um microsserviço é uma entidade independente, pode ser implantado de forma independente e também pode existir como um processo do sistema operacional. Há isolamento entre os serviços, e os serviços se comunicam por chamadas de rede, reforçando o isolamento entre eles e evitando acoplamento forte. Os serviços devem poder ser modificados de forma independente uns dos outros, e o deploy de um serviço não deve provocar alterações no `consumidor do serviço (Service Consumer)` desse serviço. Isso exige que consideremos o que deve ser exposto e o que deve ser ocultado pelo `provedor de serviços (Service Provider)`; se exposto demais, o `consumidor do serviço (Service Consumer)` ficará acoplado à implementação interna do serviço, o que gerará trabalho extra de coordenação entre os serviços e reduzirá a autonomia do serviço.

## Principais benefícios

### Heterogeneidade de tecnologias

Em um sistema em que vários serviços colaboram entre si, é possível escolher, em cada serviço, a tecnologia mais adequada para ele; e, como os serviços se comunicam por rede, a implementação do serviço não fica limitada à linguagem ou ao framework do sistema. Isso significa que, quando uma parte do sistema precisar de melhoria de desempenho, é possível reconstruir a implementação dessa parte com uma stack tecnológica de melhor desempenho.

### Elasticidade

Um conceito-chave para implementar sistemas elásticos é o `anteparo (Bulkhead)`: se um componente ou um serviço do sistema ficar indisponível, mas isso não causar falha em cascata, as outras partes do sistema ainda podem funcionar normalmente. O `limite de serviço` dos microsserviços é, obviamente, um `anteparo (Bulkhead)`. Em sistemas de `arquitetura monolítica (Monolithic architecture)`, ou seja, em sistemas sob a arquitetura tradicional `PHP-FPM`, se alguma parte ficar indisponível, na maioria dos casos todas as funcionalidades ficarão indisponíveis. Embora seja possível reduzir a probabilidade de indisponibilidade total do sistema implantando-o em vários nós com tecnologias como balanceamento de carga, em sistemas de `arquitetura de microsserviços (Microservice)` a própria arquitetura consegue tratar bem a indisponibilidade de serviços e a degradação de funcionalidades.

### Escala

Um sistema de `arquitetura monolítica (Monolithic architecture)` só pode ser escalado como um todo, mesmo que apenas uma pequena parte do sistema tenha problemas de desempenho. Usando vários serviços menores, é possível escalar apenas os serviços que precisam de escala, o que permite executar os serviços que não precisam de escala em servidores mais baratos, economizando custos.

### Deploy simplificado

Em um sistema de `arquitetura monolítica (Monolithic architecture)` com uma enorme quantidade de código, mesmo que se altere apenas uma linha de código, é necessário fazer o deploy de todo o sistema novamente para publicar a mudança. Esse tipo de deploy tem grande impacto e alto risco, de modo que as partes interessadas não ousam fazê-lo facilmente; assim, na prática, a frequência de deploy se torna muito baixa, e entre uma versão e outra acumulam-se muitas funcionalidades ou `Bugfix`, publicando-se de uma vez uma grande quantidade de mudanças no ambiente de produção. Porém, quanto maior a diferença entre dois lançamentos, maior a possibilidade de erro.   
Claro, no desenvolvimento sob a arquitetura tradicional `PHP-FPM`, talvez não tenhamos esse problema, porque o hot reload existe naturalmente, mas há prós e contras ao mesmo tempo.

### Alinhamento com a estrutura organizacional

Na `arquitetura monolítica (Monolithic architecture)`, e quando a estrutura da equipe também é "distribuída" (em locais diferentes), os conflitos de código causados pelos commits de muitos engenheiros e a comunicação de iteração à distância tornam a manutenção do sistema mais complexa. Todos sabemos que uma equipe de tamanho adequado trabalhando em uma base de código pequena obtém maior produtividade; assim, a divisão e a atribuição de serviços permitem delimitar bem as responsabilidades relacionadas.

### Composabilidade

O principal benefício alegado pelos `sistemas distribuídos` e pela `arquitetura orientada a serviços (SOA)` é a facilidade de reutilizar funcionalidades existentes; na `arquitetura de microsserviços (Microservice)`, a divisão de serviços em granularidade mais fina evidencia ainda mais essa vantagem.

### Alta capacidade de refatoração

Se você está diante de um sistema de `arquitetura monolítica (Monolithic architecture)` de grande porte, com código confuso e feio, todos têm medo de refatorá-lo. Mas quando você está diante de um serviço de pequena escala e granularidade fina, refatorar um serviço ou até reescrever um serviço correspondente é algo relativamente viável. Em um sistema de `arquitetura monolítica (Monolithic architecture)` de grande porte, ao excluir centenas de linhas de código em um dia, você pode ter certeza de que não causará nenhum problema? Mas em uma boa `arquitetura de microsserviços (Microservice)`, acredito que você também conseguirá excluir um serviço inteiro com tranquilidade.

## Não existe bala de prata No Silver Bullet

Embora a `arquitetura de microsserviços (Microservice)` traga muitos benefícios, **microsserviços não são uma bala de prata !!!** Você precisará enfrentar a complexidade que todos os sistemas distribuídos precisam enfrentar; talvez seja necessário fazer muito trabalho em deploy, testes e monitoramento, muito trabalho nas chamadas entre serviços e na confiabilidade dos serviços, e talvez até lidar com problemas como transações distribuídas ou relacionados ao CAP. Embora o `Hyperf` já resolva muitos problemas para você, antes de implementar a `arquitetura de microsserviços (Microservice)` sua equipe precisa acumular conhecimento suficiente sobre sistemas distribuídos, para enfrentar muitos problemas que talvez você nunca tenha enfrentado ou nem considerado na `arquitetura monolítica (Monolithic architecture)`.


*| Parte do conteúdo deste capítulo foi traduzida de 《Building Microservices》, de Sam Newman*
