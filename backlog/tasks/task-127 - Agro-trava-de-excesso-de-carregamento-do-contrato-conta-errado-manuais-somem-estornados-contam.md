---
id: TASK-127
title: >-
  Agro: trava de excesso de carregamento do contrato conta errado (manuais
  somem, estornados contam)
status: To Do
assignee: []
created_date: '2026-09-21 21:32'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 138000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
CargaService::validarOverloadContrato soma o ja entregue assim: MovimentoGrao::where(contatipo CONTRATO)->where(papel DESTINO)->when(codcarga, fn => where('codcarga','!=',$carga->codcarga))->sum('liquido'). Dois defeitos na mesma consulta: 1) falta whereNull('inativo') - um ajuste manual ESTORNADO continua contando como entregue e bloqueia carga legitima; 2) o 'codcarga != X' e SQL de tres valores: linha com codcarga NULL (todo ajuste manual) avalia NULL e fica de fora - ou seja, entregas manuais deixam de contar assim que a carga ja tem codcarga, e contam quando a carga ainda e nova. O mesmo contrato pode aprovar/reprovar dependendo de a carga ja ter sido sincronizada. Alem disso o numero diverge do 'Saldo a entregar' que o operador ve no CargaForm, que vem de ContratoResource::saldokg (carregadokg = withSum com whereNull('inativo') e TODOS os papeis). Alinhar as duas contas numa fonte unica.
<!-- SECTION:DESCRIPTION:END -->
