---
id: TASK-206
title: >-
  Gerente precisa ler todas as ocorrências do dia em vez de receber só o que
  foge do padrão
status: To Do
assignee: []
created_date: '2026-10-09 00:20'
labels:
  - negocios
dependencies:
  - TASK-205
priority: low
type: feature
ordinal: 218000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 08/10/2026, para um segundo momento (depois da TASK-205). Em vez de o gerente conferir linha a linha o livro de ocorrências, uma auditoria diária por LLM entrega um parecer curto por filial com só o que destoa, analisando padrões de comportamento dos usuários.

Proposta discutida:
- A conta fica com o SQL, a interpretação com a LLM: o sistema monta o pacote do dia (ocorrências + comparação de cada usuário com ele mesmo nos últimos 30 dias e com os colegas da filial + diferença de caixa + regras da casa: 5% à vista, desconto do cadastro, significado dos motivos).
- O parecer cita as ocorrências pelo número (#codocorrencia), para o gerente verificar e a LLM não inventar caso; tom de indício ('vale conversar'), nunca acusação.
- Usuários pseudonimizados (USR-12) no envio; nome só na tela do gerente.
- Uma chamada por filial por dia, em lote (batch, metade do preço): ~US$ 0,25–0,50 por filial/dia.
- Antes de automatizar: teste manual — consulta SQL monta o 'documento do dia' de um dia passado em que se sabe que houve desvio, cola no Claude e avalia se o parecer aponta.
- Não existe integração com LLM no código hoje; a TASK-200 (ler borderô pela foto) também precisa, a configuração do provedor serve às duas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Teste manual: consulta do documento do dia rodada num dia passado conhecido e parecer avaliado pelo Fábio
<!-- AC:END -->
