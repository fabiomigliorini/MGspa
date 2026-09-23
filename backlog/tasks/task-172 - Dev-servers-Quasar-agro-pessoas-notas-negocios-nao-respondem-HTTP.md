---
id: TASK-172
title: Dev servers Quasar (agro/pessoas/notas/negocios) nao respondem HTTP
status: To Do
assignee: []
created_date: '2026-09-23 20:15'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 161000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto em 23/09/2026 ao tentar validar visualmente o TASK-171 (blocos editaveis do Patio de Cargas) via headless Chrome. Os 4 containers de dev Quasar (agro:8088, pessoas:8081, notas:8082, negocios:9900) estao rodando (docker ps confirma Up, processo 'quasar dev' vivo via ps/ss dentro do container, escutando 0.0.0.0:<porta>, CPU/memoria normais), mas qualquer requisicao HTTP (curl direto, curl com Host: sistema-dev.mgpapelaria.com.br, ou Chrome headless em http://sistema-dev.mgpapelaria.com.br:<porta>) recebe resposta vazia imediata (curl: 'Empty reply from server' / Chrome: ERR_EMPTY_RESPONSE), sem nenhum byte, em ~0ms — nao e timeout/compilacao lenta. Confirmado que NAO e um problema geral de rede: porta 80 (mgweb-mgweb-1/nginx) responde 200 normalmente no mesmo host/sessao. Afeta as 4 apps simultaneamente, entao nao parece ligado a uma mudanca de codigo especifica (testado durante o trabalho do TASK-171, que so mexeu no app agro). Suspeita: os processos 'quasar dev' foram iniciados manualmente ha muito tempo (agro esta 'Up 2 weeks') e podem ter travado/ficado num estado zumbi que ainda aceita a conexao TCP mas nao processa a requisicao HTTP — precisa investigar dentro do container (nao ha log acessivel via 'docker logs', quasar dev foi iniciado manualmente numa sessao interativa que nao esta mais anexada) ou simplesmente reiniciar os processos. Bloqueia verificacao visual de qualquer mudanca de frontend em dev ate resolver.
<!-- SECTION:DESCRIPTION:END -->
