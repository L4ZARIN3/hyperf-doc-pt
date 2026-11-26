# Descrição das versões

## Regras de versionamento

O Hyperf adota a regra de numeração x.y.z para nomear cada versão, por exemplo, a versão 1.2.3, em que 1 é x, 2 é y e 3 é z. Você pode usar essa regra de versões para elaborar seu plano de atualização do framework Hyperf.
- x representa uma versão principal; quando o núcleo do Hyperf passa por muitas refatorações e mudanças, ou quando há muitas mudanças destrutivas de API, é lançada uma versão x. Mudanças de versão x geralmente não são compatíveis com versões x anteriores, mas isso não significa necessariamente incompatibilidade total; verifique caso a caso conforme o guia de atualização da versão correspondente.
- y representa uma versão de iteração de funcionalidades principais; quando algumas APIs públicas sofrem mudanças destrutivas, incluindo alteração e remoção de APIs públicas, fazendo com que a versão anterior possa ficar incompatível, é lançada uma versão y.
- z representa uma versão de correção totalmente compatível; quando se corrigem BUGs ou problemas de segurança nas funcionalidades existentes de cada componente, opta-se por lançar uma versão z. Quando um BUG faz com que determinada funcionalidade fique completamente inutilizável, também é possível que, ao corrigir esse BUG em uma versão z, se faça uma mudança destrutiva de API; porém, como a funcionalidade já estava completamente inutilizável, esse tipo de mudança não é lançado como versão y. Além de correções de BUG, a versão z também pode incluir algumas funcionalidades ou componentes novos, que não afetam o uso do código anterior.

## Atualizar a versão

Quando você deseja atualizar a versão do Hyperf, se for uma atualização para versões x ou y, siga o guia de atualização da versão correspondente na documentação. Se você deseja atualizar a versão z, pode simplesmente executar o comando `composer update hyperf` no diretório raiz do seu projeto para atualizar os pacotes de dependência. Não recomendamos atualizar individualmente a versão de um componente, mas sim atualizar todos os componentes em conjunto para obter uma experiência mais consistente.
