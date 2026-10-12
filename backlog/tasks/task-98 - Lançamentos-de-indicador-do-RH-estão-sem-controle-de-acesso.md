---
id: TASK-98
title: Lançamentos de indicador do RH estão sem controle de acesso
status: Done
assignee: []
created_date: '2026-09-15 15:11'
updated_date: '2026-10-10 17:21'
labels:
  - api
dependencies: []
priority: medium
type: bug
ordinal: 97000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto ao conferir a TASK-3 (15/09/2026). E o unico metodo dos controllers de api/app/Mg/Rh sem Autorizador::autoriza(['Recursos Humanos']) — qualquer usuario autenticado consegue ler os lancamentos de qualquer indicador pelo id. Os demais metodos do IndicadorController exigem o grupo. Medium: exige login e e so leitura, mas expoe numeros de venda/meta fora do RH.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026): já estava feito, fechada. Premissa errada: IndicadorController::lancamentos chama autorizarLancamentos() (grupo RH, o próprio colaborador, mesmo setor/unidade e gestor; o resto recebe 403), desde a8901f4b8. A conferência original procurou só por Autorizador::autoriza.
<!-- SECTION:NOTES:END -->
