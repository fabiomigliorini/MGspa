---
id: TASK-201
title: >-
  Safra nova obriga a plantar os talhões um por um, mesmo sendo os mesmos da
  safra anterior
status: Done
assignee:
  - '@eduardo'
created_date: '2026-10-07 18:37'
updated_date: '2026-10-10 16:16'
labels:
  - agro
dependencies: []
priority: low
type: feature
ordinal: 214000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do usuário (07/10/2026): ao abrir uma safra nova, poder reaproveitar o layout de talhões de outra safra/fazenda. Na prática a fazenda quase não muda — troca a safra, os talhões são os mesmos — e hoje cada talhão tem que ser plantado de novo à mão na safra nova.

Como está hoje (api/app/Mg/Fazenda):
- Talhao é permanente da fazenda (talhao, area, geometria, cor, lat/long).
- Plantio liga Talhao a uma Safra (codsafra, codfazenda, codtalhao) e carrega a própria cópia de talhao, geometria, cor, areaplantada, além do que é da safra: codvariedade, dataplantio, expectativasacas, hacolhido.
- Reaproveitar o layout = criar na safra nova um Plantio para cada talhão plantado na safra de origem, copiando o que é do talhão (nome, geometria, cor, área) e deixando em branco o que é da safra (variedade, data de plantio, expectativa, colhido).

A decidir com quem prioriza: se a origem pode ser de cultura diferente (soja → milho), se a cópia é por fazenda ou da safra inteira, e se é ação na criação da safra ou botão na safra já criada (filosofia: um botão, uma ação; nada automático sem pedido).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 No cadastro da safra nova dá para escolher 'Copiar talhões de' outra safra e os talhões dela já nascem na safra nova
- [x] #2 Vêm nome, desenho, cor e área do cadastro atual do talhão na fazenda; variedade e data de plantio ficam em branco
- [x] #3 Data de plantio deixa de ser obrigatória
- [x] #4 Talhão sem variedade aparece como pendente e não pode ser finalizado até ela ser informada
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
07/10/2026 — implementado, aguardando validação.
Decisões do usuário: cópia no cadastro da safra nova; origem diz QUAIS talhões, nome/desenho/cor/área vêm do cadastro atual do talhão (tbltalhao); data de plantio opcional; variedade 'obrigatória mas pode ser dita depois'.
Backend: DDL api/database/agro_plantio_variedade_opcional.sql (codvariedade drop not null — rodado no dev, FALTA PROD); SafraStoreRequest aceita codsafraorigem; SafraController::store cria safra + PlantioService::copiarTalhoes numa transação e devolve talhoescopiados; Plantio Store/UpdateRequest com dataplantio nullable (período só se informado); PlantioController::hacolhido recusa 422 finalizar sem variedade. Pátio não é barrado.
Frontend: SafraForm 'Copiar talhões de' (só na criação, safras ativas de qualquer cultura); aviso com nº de talhões copiados; SafraDetailPage selo 'Variedade pendente' e checkbox Encerrado desabilitado sem variedade; wizard sem data obrigatória (variedade segue obrigatória).
Conferido em dev (transação desfeita): origem safra 1 → 22 talhões copiados = 22 distintos ativos; área/cor/geometria iguais ao cadastro; variedade e data nulas; finalizar sem variedade → 422; com variedade → finaliza.

Teste ignorado pelo Fábio (10/10/2026): ele dispensou o teste manual dele e pediu para marcar e commitar. Critérios marcados com base no que está implementado e nos testes registrados nas notas acima.
<!-- SECTION:NOTES:END -->
