---
id: TASK-134
title: 'Agro: limpar colunas mortas de tblcarga (modelo antigo de classificacao)'
status: To Do
assignee: []
created_date: '2026-09-21 21:34'
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
