# Plugins para IDE

## Plugin para PhpStorm

### Plugin de banco de dados

Você pode instalar o plugin [Hyperf Query](https://plugins.jetbrains.com/plugin/33333-hyperf-query) no PhpStorm, que oferece suporte de integração com banco de dados para o construtor de consultas do Hyperf. Em conjunto com o DataGrip, ele fornece autocompletar para schemas, tabelas, views e colunas do banco de dados; as principais funcionalidades são:

- Autocompletar de schemas, tabelas, views e colunas em métodos do construtor de consultas e do construtor de Schema
- Autocompletar em arquivos de migração (migration)
- Verificação (Inspection) de elementos de banco de dados desconhecidos
- Suporte a aliases de tabela
- Resolução do nome da tabela dos métodos do construtor a partir do modelo
- Resolução do nome da tabela em métodos de closure de modelos relacionados
- Navegação textual (Ctrl+Click) para elementos de banco de dados
- Prefixo de tabela e filtragem de fontes de dados configuráveis

Forma de instalação: em `Preferences` > `Plugins` > `Marketplace`, pesquise por **Hyperf Query** e clique em **Install Plugin** para instalar. Após a instalação, recomenda-se primeiro concluir a configuração da fonte de dados e, nas configurações do plugin, filtrar a fonte de dados a ser usada.

> Esse projeto é um fork adaptado do [laravel-query-intellij](https://github.com/ekvedaras/laravel-query-intellij) (licença MIT), no qual o alvo de reconhecimento foi migrado de `Illuminate\Database\*` para `Hyperf\Database\*`; o código-fonte está em [hyperf-query-intellij](https://github.com/tw2066/hyperf-query-intellij).
