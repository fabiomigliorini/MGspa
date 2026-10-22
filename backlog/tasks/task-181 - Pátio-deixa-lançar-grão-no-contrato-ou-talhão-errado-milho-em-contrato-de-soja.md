---
id: TASK-181
title: >-
  Pátio deixa lançar grão no contrato ou talhão errado (milho em contrato de
  soja)
status: In Progress
assignee: []
created_date: '2026-09-28 21:11'
updated_date: '2026-10-10 19:06'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O pátio aceita contrato de outra cultura, talhão de outra safra, contrato de venda como origem, transferência de um silo para ele mesmo e tara maior que o PBT: o servidor só confere que os códigos existem (CargaSincronizarRequest, regras exists). Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fase 4), com o diagnóstico SQL no dev e na PROD antes de ligar as regras. Acréscimos de /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 5): unidade ou contrato inativo recusado como ponto novo; contrato de outra safra da mesma cultura decidido no diagnóstico.

**Estado no código em 22/10/2026 (leitura):** a descrição acima é anterior à TASK-180. Hoje o servidor já recusa tara maior que o PBT, PBT acima de 150 t e leitura fora de 0–100 (CargaSincronizarRequest), então o #4 está pronto (o servidor ainda aceita tara igual ao PBT; a tela exige menor). #2 e #5 existem só na tela: o pátio não oferece talhão de outra safra nem silo/contrato inativo, mas o servidor só confere `exists`. #1 e #3 não existem no servidor (não há `validarPontos` nem `PONTOS_POR_SENTIDO`); o SelectContrato não filtra por cultura nem operação, e a transferência não esconde no destino o silo da origem.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Contrato de outra cultura é recusado, e o select só oferece contratos da cultura da safra
- [ ] #2 Talhão de outra safra é recusado como origem
- [ ] #3 Origem e destino seguem as combinações permitidas por sentido (D2)
- [ ] #4 Tara maior que o PBT é recusada com mensagem clara
- [ ] #5 Unidade ou contrato inativo é recusado no servidor (a tela já só oferece silo e contrato ativos)
<!-- AC:END -->
