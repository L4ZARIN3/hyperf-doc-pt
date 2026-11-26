# Hyperf — Documentação em Português (pt-BR)

Tradução comunitária completa da documentação oficial do [Hyperf](https://hyperf.wiki) para português brasileiro, feita a partir da documentação em chinês (`zh-cn`) — a versão mais completa e atualizada mantida pelo projeto.

## Versão disponível

| Versão | Status | Fonte | Observações |
| --- | --- | --- | --- |
| [3.2](3.2/) | ✅ Traduzida | `master` @ `d893568` (21/11/2026) | 123 arquivos Markdown; changelog até **v3.2.6** (14/11/2026) |

> Apenas a versão **3.2** é mantida neste repositório. No repositório oficial, o branch `master` mantém a documentação da versão 3.2 atualizada (correções e changelog).

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

Correções de tradução, links ou trechos desatualizados são bem-vindos via pull request.

## Créditos

- Documentação original: [hyperf/hyperf/docs/zh-cn](https://github.com/hyperf/hyperf/tree/master/docs/zh-cn)
- Site oficial: [hyperf.wiki](https://hyperf.wiki)
- Hyperf é um projeto open source licenciado sob a MIT License. Esta é uma tradução comunitária não oficial.

## SEO e indexação

- `robots.txt` — aponta para o sitemap
- `sitemap.xml` — gerado por `php tools/generate-sitemap.php` (regenere após adicionar páginas)
- O site usa Docsify em **modo history** (URLs reais, sem `#/`), com fallback SPA via `404.html` para o GitHub Pages servir as subpáginas
- `vendor/docsify.min.js` — Docsify 4.13.1 com dois patches locais (suporte correto a `basePath` no modo history): os links renderizados incluem a base e o `getFile` não duplica o caminho. Não atualize a versão do Docsify sem reaplicar os patches.
