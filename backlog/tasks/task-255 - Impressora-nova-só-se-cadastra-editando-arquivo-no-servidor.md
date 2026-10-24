---
id: TASK-255
title: Impressora nova só se cadastra editando arquivo no servidor
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-22 00:43'
updated_date: '2026-10-23 16:48'
labels:
  - api
dependencies: []
priority: high
type: feature
ordinal: 263000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Impressora nova só existe para o sistema editando o arquivo api/printers.json no servidor; PDV e usuário guardam a impressora como texto, e as três impressoras do cadastro de usuário (térmica, matricial, tela de negócio) estão vazias e sem uso. A impressão do DANFE ainda devolve na resposta da API o comando curl com a chave do Ably (NFePHPService::imprimir), e as 10 publicações no Ably são exec(curl) concatenando o nome.

Prioridade High: pré-requisito da impressão automática da requisição (TASK-55) e fecha a chave do Ably exposta.

Plano em backlog/docs/doc-6 (etapas 0, 1 e 8): tabela tblimpressora (chave = nome da fila no CUPS = evento do Ably, tipo térmica/A5/etiqueta), cadastro no PDV (app negocios), codimpressora no PDV, no local de estoque, em todas as rotas e no front, publicação centralizada em ImpressoraService::publicar; limpeza das impressoras do usuário (api, MGLara, quasar legado).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Etapa 0: impressoras do usuário removidas (tblusuario, api, MGLara, EscPrint, quasar legado) e o comando com a chave do Ably fora da resposta da impressão do DANFE
- [x] #2 Etapa 1: tblimpressora com cadastro no PDV (app negocios); printers.json apagado; select e PDV lendo a tabela
- [x] #3 Etapa 8: codimpressora no PDV, em todas as rotas e no front; publicações no Ably por ImpressoraService::publicar
- [x] #4 README do MGprint aponta para o cadastro de impressoras
- [ ] #5 Etapa 0: MGsis deixa de ler as impressoras do usuário (login e Perfil param de vez que as colunas caem)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Etapa 1: printers.json não é versionado (cada servidor tem o seu). Carga pelo comando php artisan impressora:importar-json (cria só as que faltam; argox = etiqueta, resto = térmica) depois do api/database/impressora.sql; apagar o arquivo do servidor depois da carga.

Etapa 8: as rotas e corpos continuam com o parâmetro chamado impressora, agora com o codimpressora; ImpressoraService::resolver aceita também o nome da fila (PWA ainda não atualizado). PDV atualizado e ainda não sincronizado usa a impressora antiga pelo nome (afterHydrate do store negocio) até a próxima sincronização.
<!-- SECTION:NOTES:END -->
