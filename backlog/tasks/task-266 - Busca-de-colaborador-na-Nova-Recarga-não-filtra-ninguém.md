---
id: TASK-266
title: Busca de colaborador na Nova Recarga não filtra ninguém
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-04 19:43'
updated_date: '2026-11-05 21:40'
labels:
  - pessoas
dependencies: []
priority: low
type: bug
ordinal: 281000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No diálogo Nova Recarga (pessoas, período → Gerar Recarga → Nova Recarga), o campo Buscar colaborador mostrava todo mundo para qualquer texto sem número, e nome em pedaços ("luiz sec") não achava "Luiz Benedito Secolini".

Causa (pessoas/src/components/rh/DialogRecargaAvulsa.vue, computed filtradas): o CPF era comparado com os dígitos do termo; sem dígito, String(cpf).includes('') é sempre true e a linha passa. E o nome exigia o termo inteiro contíguo.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Texto sem número filtra pelo nome/setor (o CPF só entra quando há número digitado)
- [x] #2 Cada palavra digitada precisa aparecer no nome ou no setor, em qualquer ordem e posição
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Teste: pessoas /rh/8 → Gerar Recarga → aba FDF → Nova Recarga → buscar 'luiz sec' (acha só o Secolini), 'xyz' (Nenhum colaborador encontrado), parte do CPF (acha pelo CPF), nome de setor.
<!-- SECTION:NOTES:END -->
