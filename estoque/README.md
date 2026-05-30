# MG Estoque

Cadastro de Produtos e Controle de Estoque (app Quasar PWA).

Mesma stack e padrões do app [`contas`](../contas): Vue 3 (Composition API + `<script setup>`) + Quasar 2 + Pinia + PWA, auth SSO compartilhado e componentes em `@components` (`../components`).

## Desenvolvimento (Docker)

```bash
./start              # docker compose up -d --build
./shell              # entra no container
yarn install         # primeira vez
quasar dev           # https://localhost:8087/
```

Porta: **8087** (dev e devServer). `.env` é symlink pra `../negocios/.env` (master compartilhado).

## Build (PWA)

```bash
./shell
quasar build -m pwa
```
