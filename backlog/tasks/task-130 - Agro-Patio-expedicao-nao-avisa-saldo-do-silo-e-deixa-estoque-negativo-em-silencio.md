---
id: TASK-130
title: >-
  Agro/Patio: expedicao nao avisa saldo do silo e deixa estoque negativo em
  silencio
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-10-10 19:06'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 141000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A store do patio ja tem saldoUnidadeOffline(cod) (snapshot do servidor + delta das cargas locais pendentes) e o snapshot saldosUnidades e baixado a cada sync - mas NENHUM componente usa: grep por saldoUnidadeOffline/saldosUnidades nos .vue nao acha nada. O CargaForm mostra 'Saldo a entregar' so para DESTINO do tipo CONTRATO. Consequencia: numa SAIDA ou TRANSFERENCIA o operador carrega 40 t de um silo com 10 t e nada avisa - nem no front nem no backend (CargaService::validar so cobra rateio e over-load de contrato). O saldo da unidade fica negativo e so aparece na tela de Estoque. Exibir o saldo da unidade na linha de ORIGEM (mesmo padrao do contrato) e decidir se o backend bloqueia ou so avisa.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Linha de ORIGEM (silo) mostra o saldo do silo na safra da carga, como a do contrato mostra o saldo a entregar
- [ ] #2 Saída maior que o saldo pede confirmação no pátio; entrada acima da capacidade também
- [ ] #3 Tela de Estoque destaca silo com saldo negativo e mostra a quebra
- [ ] #4 Saída de silo baixa o peso bruto; o destino recebe o líquido e a quebra aparece no relatório
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): o AC 'Silo inativo não recebe carga nova' saiu: a tela já só oferece silo ativo desde jun/26 (agro/src/stores/carga.js unidadesAtivas, SelectUnidade); a recusa no servidor ficou na TASK-181.
<!-- SECTION:NOTES:END -->
