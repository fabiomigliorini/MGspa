# MG Agro

Controle de Safra da Fazenda (app Quasar PWA).

Mesma stack e padrões dos apps [`contas`](../contas) e [`estoque`](../estoque): Vue 3 (Composition API + `<script setup>`) + Quasar 2 + Pinia + PWA, auth SSO compartilhado e componentes em `@components` (`../components`).

> **Estado atual:** esqueleto — só infraestrutura + autenticação SSO + home placeholder. Qualquer usuário autenticado entra (sem gate por grupo de permissão ainda).

## Desenvolvimento (Docker)

```bash
./start              # docker compose up -d --build
./shell              # entra no container
yarn install         # primeira vez
quasar dev           # https://localhost:8088/
```

Porta: **8088** (dev e devServer). `.env` é symlink pra `../negocios/.env` (master compartilhado).

## Build (PWA)

```bash
./shell
quasar build -m pwa
```
