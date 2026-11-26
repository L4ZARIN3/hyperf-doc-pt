# Hyperf — Documentação em Português (pt-BR)

Tradução comunitária completa da documentação oficial do [Hyperf](https://hyperf.wiki) para português brasileiro, feita a partir da documentação em chinês (`zh-cn`) — a versão mais completa e atualizada mantida pelo projeto.

## Versões disponíveis

| Versão | Status | Fonte | Observações |
| --- | --- | --- | --- |
| [3.2](3.2/) | ✅ Traduzida | `master` @ `d893568` (21/11/2026) | 123 arquivos Markdown; changelog até **v3.2.6** (14/11/2026) |
| 3.1 | ⏳ Não traduzida | branch `3.1` | — |
| 3.0 | ⏳ Não traduzida | branch `3.0` | — |
| 2.2 | ⏳ Não traduzida | branch `2.2` | — |
| 2.1 | ⏳ Não traduzida | branch `2.1` | — |
| 2.0 | ⏳ Não traduzida | branch `2.0` | — |
| 1.x | ⏳ Não traduzida | branches `1.1` / `1.0` | Docs em `doc/zh-cn` e `doc/zh` |

> A documentação do Hyperf é versionada por branch no repositório oficial. O branch `master` mantém a documentação da versão **3.2** atualizada (correções e changelog), enquanto branches como `3.1` e `3.0` preservam a documentação de cada série.

## Sobre a tradução da versão 3.2

- **Versão documentada:** Hyperf **3.2** (a documentação original declara "Apresentação da versão 3.2").
- **Snapshot de origem:** branch `master` do repositório [hyperf/hyperf](https://github.com/hyperf/hyperf), commit [`d893568`](https://github.com/hyperf/hyperf/commit/d89356856d90102276223062083b9d26bb1324b5), de 21/11/2026.
- **Changelog incluído:** até **v3.2.6** (14/11/2026).
- **Arquivos:** 123 arquivos Markdown, além de imagens e assets do site.

O branch `3.2` do repositório oficial está algumas atualizações atrás do `master` (changelog e página de plugins), por isso foi usado o `master`, que é a fonte viva da documentação 3.2.

## Estrutura do repositório

```
.
├── index.html          # seletor de versões
├── 3.2/
│   ├── index.html      # site Docsify da versão 3.2
│   ├── README.md       # página inicial da documentação
│   ├── summary.md      # menu lateral
│   ├── *.md            # capítulos
│   ├── changelog/      # registros de versão
│   ├── db/             # banco de dados
│   ├── quick-start/    # início rápido
│   ├── tutorial/       # tutoriais
│   └── upgrade/        # guias de atualização
└── ...
```

## Visualizar localmente

```bash
python3 -m http.server 8080
# abra http://localhost:8080/
```

Ou publique com GitHub Pages: **Settings → Pages → Deploy from a branch → `main` / `(root)`**.

## Contribuir

Correções de tradução, links ou trechos desatualizados são bem-vindos via pull request. Ao traduzir uma nova versão, crie o diretório correspondente (por exemplo, `3.1/`) mantendo a mesma estrutura e adicione o link no seletor de versões.

## Créditos

- Documentação original: [hyperf/hyperf/docs/zh-cn](https://github.com/hyperf/hyperf/tree/master/docs/zh-cn)
- Site oficial: [hyperf.wiki](https://hyperf.wiki)
- Hyperf é um projeto open source licenciado sob a MIT License. Esta é uma tradução comunitária não oficial.
