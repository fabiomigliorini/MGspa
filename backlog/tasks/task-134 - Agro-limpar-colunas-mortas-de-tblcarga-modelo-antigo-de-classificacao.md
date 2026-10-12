---
id: TASK-134
title: 'Agro: limpar colunas mortas de tblcarga (modelo antigo de classificacao)'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-21 21:34'
updated_date: '2026-09-30 12:21'
labels:
  - agro
dependencies: []
priority: low
type: chore
ordinal: 145000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
tblcarga (agro_grao.sql) ainda tem as colunas do modelo antigo de 3 parametros fixos: umidade, impureza, avariados, descontoumidade, descontoimpureza, descontoavariados - substituidas por tblcargaclassificacao (uma linha por parametro). Nenhuma delas esta no $fillable do model Carga nem e lida em qualquer lugar: ficam NULL para sempre e induzem ao erro quem consultar o banco direto. Na mesma tabela, 'aprovado' (timestamp, 'comprador aprovou (saida)') esta no $fillable e no $casts mas nao e escrito nem lido por ninguem - o fluxo de aprovacao nunca foi implementado. Decidir entre implementar o aprovado ou remover tudo num DDL de limpeza.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Rodar de novo os scripts antigos do agro não recria colunas nem derruba tabelas
- [x] #2 Coluna aprovado removida do banco e do código
- [x] #3 Data de embarque antiga (dataembarque) preservada em embarqueinicio/embarquefim e a coluna removida
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
29/09/2026: scripts do agro movidos para api/database/agro/ (01-29, ordem de replay) e reescritos com guarda — cada um e um bloco DO atomico que pula o que ja foi feito; agro_grao.sql (13) nao dropa mais tblcarga/ponto/movimento/silo. Testado em 4 bases: instalacao do zero, base de junho com contratos, base de julho com classificacao e dev; todas terminam no mesmo esquema e a 2a rodada sai toda 'pulado'. Novos: 28 (dataembarque vira embarqueinicio/fim e sai) e 29 (drop aprovado). Codigo: aprovado saiu de Carga.php; dataembarque de Contrato.php e ContratoStoreRequest. Dev aplicado.

30/09/2026: kit rodado na PROD pelo usuario. Diagnostico: 01-24 ja feitos; aplicados 25 (classificacao por cultura; PROD ja estava na norma), 26, 27, 28, 29 e 30 (2o reboque). Tudo sem erro.
<!-- SECTION:NOTES:END -->
