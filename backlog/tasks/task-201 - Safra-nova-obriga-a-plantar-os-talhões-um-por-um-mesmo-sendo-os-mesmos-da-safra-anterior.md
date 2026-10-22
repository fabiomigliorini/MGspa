---
id: TASK-201
title: >-
  Safra nova obriga a plantar os talhões um por um, mesmo sendo os mesmos da
  safra anterior
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-10-07 18:37'
updated_date: '2026-10-22 19:26'
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
- [ ] #5 Safra com mais de 50 talhões plantados mostra todos na lista
- [ ] #6 Na Cultura, o plantio sem variedade aparece na quebra por variedade
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
07/10/2026 — implementado, aguardando validação.
Decisões do usuário: cópia no cadastro da safra nova; origem diz QUAIS talhões, nome/desenho/cor/área vêm do cadastro atual do talhão (tbltalhao); data de plantio opcional; variedade 'obrigatória mas pode ser dita depois'.
Backend: DDL api/database/agro_plantio_variedade_opcional.sql (codvariedade drop not null — rodado no dev, FALTA PROD); SafraStoreRequest aceita codsafraorigem; SafraController::store cria safra + PlantioService::copiarTalhoes numa transação e devolve talhoescopiados; Plantio Store/UpdateRequest com dataplantio nullable (período só se informado); PlantioController::hacolhido recusa 422 finalizar sem variedade. Pátio não é barrado.
Frontend: SafraForm 'Copiar talhões de' (só na criação, safras ativas de qualquer cultura); aviso com nº de talhões copiados; SafraDetailPage selo 'Variedade pendente' e checkbox Encerrado desabilitado sem variedade; wizard sem data obrigatória (variedade segue obrigatória).
Conferido em dev (transação desfeita): origem safra 1 → 22 talhões copiados = 22 distintos ativos; área/cor/geometria iguais ao cadastro; variedade e data nulas; finalizar sem variedade → 422; com variedade → finaliza.

09/10/2026: validado pelo usuário. Lista de 'Copiar talhões de' mostra só safras ativas — confirmado como esperado. Pendente fora do código: rodar api/database/agro_plantio_variedade_opcional.sql em PROD antes do deploy.

Teste ignorado pelo Fábio (10/10/2026): ele dispensou o teste manual dele e pediu para marcar e commitar. Critérios marcados com base no que está implementado e nos testes registrados nas notas acima.

22/10/2026 — conferência no código (só leitura). Critérios novos: #5 agro/src/stores/safra.js (carregarPlantios) lê só a 1ª página de v1/safra/{cod}/plantio, que pagina em 50 (MgModel::$perPage); a cópia de talhões cria dezenas de uma vez, e safra com mais de 50 plantios fica com a lista e o wizard cortados, diferentes dos KPIs. #6 CulturaService::resumo soma todos os plantios, mas resumoVariedades só lista variedades cadastradas; plantio sem variedade (toda safra recém-copiada) entra no total e some da quebra. Como ver: #/cultura/N depois de copiar uma safra. Volta para To Do por causa de #5 e #6; responsável mantido.

22/10/2026 (2) — #5/#6 implementados, aguardando validação. #5: carregarPlantios (stores/safra.js) lê todas as páginas com o novo utils/paginas.js::todasPaginas, que substitui as cópias de cargaListagem.js e extrato.js. #6: CulturaService::resumoVariedades acrescenta a linha 'Sem variedade' e mantém a variedade inativa que ainda tem área/colhido; a soma das linhas bate com os KPIs (conferido no dev, milho e soja, e com um plantio zerado em transação desfeita: 'Sem variedade' 4,7 ha). Como testar: #/cultura/N depois de copiar uma safra; safra com mais de 50 plantios lista todos.
<!-- SECTION:NOTES:END -->
