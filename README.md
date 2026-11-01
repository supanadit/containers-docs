# containers-docs

Documentation site for the [containers](https://github.com/supanadit/containers)
collection, served at [docs.container.supanadit.com](https://docs.container.supanadit.com).
Plain PHP 8, no framework, no Composer — served by Apache behind Traefik,
modeled on the sibling `container-landing` app.

## Local development

```sh
cd public
php -S 127.0.0.1:8099 index.php
```

## Structure

```
app/
  content.php      # single source of truth: site meta + the docs nav tree
  markdown.php     # hand-rolled markdown -> HTML renderer
  seo.php          # shared <head> meta partial
  pages/docs.php   # docs layout: header, sidebar, content, TOC, pager
  search-index.php # /search-index.json for client-side search
  llms.php         # /llms.txt index
  llms-full.php    # /llms-full.txt — every page in one markdown document
  md.php           # /docs/<slug>.md raw markdown
  sitemap.php      # /sitemap.xml
content/           # the docs themselves, one markdown file per slug
public/
  index.php        # router
  style.css        # light default + [data-theme="dark"] variant
  main.js          # theme toggle, TOC scroll-spy, code copy, search
```

Routes: `/<slug>`, `/<slug>.md`, `/<slug>/llms.txt` (plain text),
`/<slug>/llms-full.txt` (raw markdown), `/search-index.json`, `/llms.txt`,
`/llms-full.txt`, `/sitemap.xml`. `/` redirects to the root page (`/why`).
Legacy `/docs/<slug>` URLs 301-redirect to `/<slug>`.

- **Nav & pages** are defined in `app/content.php`; each entry maps to
  `content/<slug>.md`, parsed at request time.
- **Edit content only in `content/*.md`** (and add the page to the nav tree).
- **Theme** is light by default; the toggle persists to `localStorage`.

## Build

```sh
docker build -t containers-docs .
docker compose up      # serves on port 3050
```
